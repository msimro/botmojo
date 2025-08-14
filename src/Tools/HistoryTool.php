<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Tools
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Tools;

use BotMojo\Core\AbstractTool;
use BotMojo\Exceptions\BotMojoException;

/**
 * History Tool
 *
 * Manages conversation history for the BotMojo system.
 * Handles caching and retrieval of conversation context.
 */
class HistoryTool extends AbstractTool
{
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['cache_dir'];
    
    /**
     * Maximum history entries to keep
     *
     * @var int
     */
    private const MAX_HISTORY_ENTRIES = 10;
    
    /**
     * Current conversation ID
     *
     * @var string|null
     */
    private ?string $conversationId = null;
    
    /**
     * Database tool for entity storage
     *
     * @var DatabaseTool
     */
    private DatabaseTool $dbTool;
    
    /**
     * Constructor
     *
     * @param DatabaseTool $dbTool The database tool for storage
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(DatabaseTool $dbTool, array $config = [])
    {
        $this->dbTool = $dbTool;
        
        // Set default cache directory if not provided
        if (!isset($config['cache_dir'])) {
            $config['cache_dir'] = defined('CACHE_DIR') ? CACHE_DIR : __DIR__ . '/../../cache';
        }
        
        parent::__construct($config);
    }
    
    /**
     * Validate the configuration
     *
     * Ensure that all required configuration parameters are present.
     *
     * @throws BotMojoException If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new BotMojoException(
                    "Missing required configuration: {$key}",
                    ['tool' => 'HistoryTool']
                );
            }
        }
        
        // Ensure cache directory exists and is writable
        $cacheDir = $this->config['cache_dir'];
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                throw new BotMojoException(
                    "Failed to create cache directory: {$cacheDir}",
                    ['tool' => 'HistoryTool']
                );
            }
        }
        
        if (!is_writable($cacheDir)) {
            throw new BotMojoException(
                "Cache directory is not writable: {$cacheDir}",
                ['tool' => 'HistoryTool']
            );
        }
    }
    
    /**
     * Set the conversation ID
     *
     * @param string|null $conversationId The conversation ID
     *
     * @return void
     */
    public function setConversationId(?string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }
    
    /**
     * Get the conversation ID
     *
     * Generate a new ID if one doesn't exist yet.
     *
     * @return string The conversation ID
     */
    public function getConversationId(): string
    {
        if ($this->conversationId === null) {
            $this->conversationId = 'conv_' . time() . rand(100, 999);
        }
        
        return $this->conversationId;
    }
    
    /**
     * Get the conversation history
     *
     * @param string|null $conversationId The conversation ID (optional)
     *
     * @return array<array<string, mixed>> The conversation history
     */
    public function getHistory(?string $conversationId = null): array
    {
        $convId = $conversationId ?? $this->getConversationId();
        $filePath = $this->getHistoryFilePath($convId);
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }
        
        $history = json_decode($content, true);
        if (!is_array($history)) {
            return [];
        }
        
        return $history;
    }
    
    /**
     * Add an entry to the conversation history
     *
     * @param array<string, mixed> $request  The user request
     * @param array<string, mixed> $response The system response
     * @param string|null          $conversationId The conversation ID (optional)
     *
     * @throws BotMojoException If the history cannot be saved
     * @return void
     */
    public function addToHistory(array $request, array $response, ?string $conversationId = null): void
    {
        $convId = $conversationId ?? $this->getConversationId();
        $history = $this->getHistory($convId);
        
        // Add the new entry
        $history[] = [
            'request' => $request,
            'response' => $response,
            'timestamp' => time()
        ];
        
        // Limit the history size
        if (count($history) > self::MAX_HISTORY_ENTRIES) {
            $history = array_slice($history, -self::MAX_HISTORY_ENTRIES);
        }
        
        // Save the history
        $filePath = $this->getHistoryFilePath($convId);
        $content = json_encode($history, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filePath, $content) === false) {
            throw new BotMojoException(
                "Failed to save conversation history",
                ['conversationId' => $convId]
            );
        }
    }
    
    /**
     * Clear the conversation history
     *
     * @param string|null $conversationId The conversation ID (optional)
     *
     * @return bool True if successful
     */
    public function clearHistory(?string $conversationId = null): bool
    {
        $convId = $conversationId ?? $this->getConversationId();
        $filePath = $this->getHistoryFilePath($convId);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Get the file path for a conversation history
     *
     * @param string $conversationId The conversation ID
     *
     * @return string The file path
     */
    private function getHistoryFilePath(string $conversationId): string
    {
        return $this->config['cache_dir'] . '/' . $conversationId . '.json';
    }
}

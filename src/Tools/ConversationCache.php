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
use BotMojo\Services\LoggerService;
use BotMojo\Exceptions\BotMojoException;

/**
 * Conversation Cache
 *
 * Advanced conversation history and context management system.
 */
class ConversationCache extends AbstractTool
{
    private LoggerService $logger;
    private string $cacheDir;
    
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->logger = new LoggerService('ConversationCache');
        $this->cacheDir = $config['cache_dir'] ?? dirname(__DIR__, 2) . '/cache';
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get conversation history
     *
     * @param string $conversationId Conversation ID
     *
     * @return array<string, mixed> Conversation data
     */
    public function getConversation(string $conversationId): array
    {
        $filePath = $this->getCacheFilePath($conversationId);
        
        if (!file_exists($filePath)) {
            return [
                'id' => $conversationId,
                'messages' => [],
                'created_at' => time(),
                'updated_at' => time()
            ];
        }
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (!$data) {
            $this->logger->warning('Failed to decode conversation cache', [
                'conversation_id' => $conversationId
            ]);
            return $this->getConversation($conversationId);
        }
        
        return $data;
    }
    
    /**
     * Save conversation to cache
     *
     * @param string $conversationId Conversation ID
     * @param array  $conversationData Conversation data
     *
     * @return bool Success
     */
    public function saveConversation(string $conversationId, array $conversationData): bool
    {
        $filePath = $this->getCacheFilePath($conversationId);
        $conversationData['updated_at'] = time();
        
        $json = json_encode($conversationData, JSON_PRETTY_PRINT);
        $result = file_put_contents($filePath, $json);
        
        if ($result === false) {
            $this->logger->error('Failed to save conversation cache', [
                'conversation_id' => $conversationId
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Add message to conversation
     *
     * @param string $conversationId Conversation ID
     * @param array  $message Message data
     *
     * @return bool Success
     */
    public function addMessage(string $conversationId, array $message): bool
    {
        $conversation = $this->getConversation($conversationId);
        $conversation['messages'][] = array_merge($message, [
            'timestamp' => time()
        ]);
        
        return $this->saveConversation($conversationId, $conversation);
    }
    
    /**
     * Get cache file path for conversation
     *
     * @param string $conversationId Conversation ID
     *
     * @return string File path
     */
    private function getCacheFilePath(string $conversationId): string
    {
        return $this->cacheDir . "/conv_{$conversationId}.json";
    }
}

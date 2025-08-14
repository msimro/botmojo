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

use BotMojo\Services\LoggerService;
use BotMojo\Exceptions\BotMojoException;

/**
 * Tool Manager
 *
 * Advanced tool orchestration and security management system.
 * Manages tool access control, instantiation, and monitoring.
 */
class ToolManager
{
    private LoggerService $logger;
    
    /**
     * Tool instances cache
     *
     * @var array<string, object>
     */
    private array $tools = [];
    
    /**
     * Tool permissions per agent
     *
     * @var array<string, array<string>>
     */
    private array $permissions = [
        'MemoryAgent' => ['database', 'search', 'notes'],
        'FinanceAgent' => ['database', 'calendar', 'notes'],
        'HealthAgent' => ['database', 'fitness', 'meditation'],
        'WeatherAgent' => ['database', 'weather'],
        'GeneralistAgent' => ['database', 'search', 'calendar', 'contacts', 'notes'],
    ];
    
    /**
     * Available tool classes
     *
     * @var array<string, string>
     */
    private array $toolClasses = [
        'database' => DatabaseTool::class,
        'gemini' => GeminiTool::class,
        'search' => SearchTool::class,
        'weather' => WeatherTool::class,
        'calendar' => CalendarTool::class,
        'contacts' => ContactsTool::class,
        'fitness' => FitnessTool::class,
        'meditation' => MeditationTool::class,
        'notes' => NotesTool::class,
    ];
    
    public function __construct()
    {
        $this->logger = new LoggerService('ToolManager');
    }
    
    /**
     * Get a tool instance for an agent
     *
     * @param string $toolName Tool name
     * @param string $agentName Agent requesting the tool
     *
     * @throws BotMojoException If tool access is denied
     * @return object Tool instance
     */
    public function getTool(string $toolName, string $agentName): object
    {
        // Check permissions
        if (!$this->hasPermission($agentName, $toolName)) {
            $this->logger->warning('Tool access denied', [
                'agent' => $agentName,
                'tool' => $toolName
            ]);
            throw new BotMojoException("Agent {$agentName} not authorized to use {$toolName}");
        }
        
        // Return cached instance if available
        $cacheKey = "{$toolName}_{$agentName}";
        if (isset($this->tools[$cacheKey])) {
            return $this->tools[$cacheKey];
        }
        
        // Create new tool instance
        $tool = $this->createTool($toolName);
        $this->tools[$cacheKey] = $tool;
        
        $this->logger->info('Tool accessed', [
            'agent' => $agentName,
            'tool' => $toolName
        ]);
        
        return $tool;
    }
    
    /**
     * Check if agent has permission to use tool
     *
     * @param string $agentName Agent name
     * @param string $toolName Tool name
     *
     * @return bool Has permission
     */
    private function hasPermission(string $agentName, string $toolName): bool
    {
        return in_array($toolName, $this->permissions[$agentName] ?? []);
    }
    
    /**
     * Create a tool instance
     *
     * @param string $toolName Tool name
     *
     * @throws BotMojoException If tool class not found
     * @return object Tool instance
     */
    private function createTool(string $toolName): object
    {
        if (!isset($this->toolClasses[$toolName])) {
            throw new BotMojoException("Tool class not found: {$toolName}");
        }
        
        $className = $this->toolClasses[$toolName];
        
        // Get configuration for the tool
        $config = $this->getToolConfig($toolName);
        
        return new $className($config);
    }
    
    /**
     * Get configuration for a tool
     *
     * @param string $toolName Tool name
     *
     * @return array<string, mixed> Tool configuration
     */
    private function getToolConfig(string $toolName): array
    {
        // Would load from environment or config files
        $configs = [
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'db',
                'name' => $_ENV['DB_NAME'] ?? 'db',
                'user' => $_ENV['DB_USER'] ?? 'db',
                'pass' => $_ENV['DB_PASS'] ?? 'db',
            ],
            'gemini' => [
                'api_key' => $_ENV['API_KEY'] ?? '',
                'model' => $_ENV['DEFAULT_MODEL'] ?? 'gemini-2.5-flash-lite',
            ],
            'weather' => [
                'api_key' => $_ENV['WEATHER_API_KEY'] ?? '',
            ],
        ];
        
        return $configs[$toolName] ?? [];
    }
}

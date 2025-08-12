<?php
/**
 * ToolManager - Central Tool Management Service
 * 
 * This class manages tool instantiation, access control, and provides
 * a central point for tool configuration and monitoring.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-08
 */
class ToolManager {
    /** @var array Tool class registrations */
    private array $tools = [];
    
    /** @var array Instantiated tool objects */
    private array $toolInstances = [];
    
    /** @var array Agent tool access permissions */
    private array $agentPermissions = [];
    
    /**
     * Constructor - Initialize and register all available tools
     */
    public function __construct() {
        // Register available tools
        $this->registerTool('weather', WeatherTool::class);
        $this->registerTool('search', SearchTool::class);
        $this->registerTool('calendar', CalendarTool::class);
        $this->registerTool('database', DatabaseTool::class);
        $this->registerTool('conversation', ConversationCache::class);
        $this->registerTool('prompt', PromptBuilder::class);
        
        // Register new domain-specific tools
        $this->registerTool('fitness', FitnessTool::class);
        $this->registerTool('meditation', MeditationTool::class);
        $this->registerTool('contacts', ContactsTool::class);
        $this->registerTool('notes', NotesTool::class);
        
        // Configure default agent permissions
        $this->configureAgentPermissions('GeneralistAgent', ['weather', 'search', 'calendar', 'conversation']);
        $this->configureAgentPermissions('FinanceAgent', ['database', 'search', 'calendar']);
        $this->configureAgentPermissions('MemoryAgent', ['database', 'conversation']);
        $this->configureAgentPermissions('PlannerAgent', ['calendar', 'database', 'search']);
        
        // Configure permissions for new domain-specific agents
        $this->configureAgentPermissions('HealthAgent', ['database', 'search', 'calendar', 'weather']);
        $this->configureAgentPermissions('SpiritualAgent', ['database', 'search', 'conversation']);
        $this->configureAgentPermissions('SocialAgent', ['database', 'calendar', 'search', 'conversation']);
        $this->configureAgentPermissions('LearningAgent', ['database', 'search', 'calendar', 'conversation']);
        
        // Give API orchestrator access to all tools
        $this->configureAgentPermissions('APIOrchestrator', array_keys($this->tools));
    }
    
    /**
     * Register a tool with the manager
     * 
     * @param string $toolName Unique name for the tool
     * @param string $className Fully qualified class name
     * @return void
     */
    public function registerTool(string $toolName, string $className): void {
        $this->tools[$toolName] = $className;
    }
    
    /**
     * Configure which tools an agent can access
     * 
     * @param string $agentName Name of the agent
     * @param array $allowedTools List of tool names the agent can access
     * @return void
     */
    public function configureAgentPermissions(string $agentName, array $allowedTools): void {
        $this->agentPermissions[$agentName] = array_flip($allowedTools);
    }
    
    /**
     * Get a specific tool instance, checking permissions if agent is specified
     * 
     * @param string $toolName Name of the tool to retrieve
     * @param string|null $agentName Optional agent name for permission check
     * @param array $constructorParams Optional parameters for tool constructor
     * @return object|null Tool instance or null if not permitted/available
     */
    public function getTool(string $toolName, ?string $agentName = null, array $constructorParams = []): ?object {
        // Check permissions if agent is specified
        if ($agentName && !$this->hasToolAccess($agentName, $toolName)) {
            error_log("ToolManager: {$agentName} attempted to access {$toolName} without permission");
            return null;
        }
        
        // Return existing instance if already created
        if (isset($this->toolInstances[$toolName])) {
            return $this->toolInstances[$toolName];
        }
        
        // Create new instance if tool exists
        $className = $this->tools[$toolName] ?? null;
        if (!$className || !class_exists($className)) {
            error_log("ToolManager: Tool {$toolName} not found or class {$className} does not exist");
            return null;
        }
        
        try {
            // Prepare constructor parameters for specific tools
            if ($toolName === 'weather' && empty($constructorParams)) {
                // Pass the OpenWeatherMap API key for weather tool
                $constructorParams = [defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : ''];
            } else if ($toolName === 'search' && empty($constructorParams)) {
                // Pass Google Search API keys
                $apiKey = defined('GOOGLE_SEARCH_API_KEY') ? GOOGLE_SEARCH_API_KEY : '';
                $searchEngineId = defined('GOOGLE_SEARCH_CX') ? GOOGLE_SEARCH_CX : '';
                $constructorParams = [$apiKey, $searchEngineId];
            }
            
            // Create instance with provided constructor parameters
            if (empty($constructorParams)) {
                $instance = new $className();
            } else {
                $reflection = new ReflectionClass($className);
                $instance = $reflection->newInstanceArgs($constructorParams);
            }
            
            // Store instance for reuse
            $this->toolInstances[$toolName] = $instance;
            return $instance;
        } catch (Exception $e) {
            error_log("ToolManager: Failed to instantiate {$toolName}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if an agent has permission to use a specific tool
     * 
     * @param string $agentName Name of the agent
     * @param string $toolName Name of the tool
     * @return bool True if agent has access to the tool
     */
    public function hasToolAccess(string $agentName, string $toolName): bool {
        // Check for wildcard access
        if ($agentName === 'GeneralistAgent') {
            return true; // GeneralistAgent has access to all tools
        }
        
        // Check for specific tool permission
        return isset($this->agentPermissions[$agentName][$toolName]);
    }
    
    /**
     * Get list of all tools available to an agent
     * 
     * @param string $agentName Name of the agent
     * @return array List of tool names available to the agent
     */
    public function getAvailableTools(string $agentName): array {
        if ($agentName === 'GeneralistAgent') {
            return array_keys($this->tools); // All tools
        }
        
        return array_keys($this->agentPermissions[$agentName] ?? []);
    }
    
    /**
     * Get all tool instances, initializing them if needed
     * Only for administrative use
     * 
     * @return array Associative array of tool name => tool instance
     */
    public function getAllTools(): array {
        // Initialize any tools that haven't been created yet
        foreach ($this->tools as $toolName => $className) {
            if (!isset($this->toolInstances[$toolName])) {
                $this->getTool($toolName);
            }
        }
        
        return $this->toolInstances;
    }
}

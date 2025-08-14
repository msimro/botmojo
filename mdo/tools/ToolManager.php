<?php
/**
 * ToolManager - Central Tool Management Service for MDO
 * 
 * This class manages tool instantiation, access control, and provides
 * a central point for tool configuration and monitoring.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
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
        // Register available tools - using main codebase tools
        $this->registerTool('weather', 'WeatherTool');
        $this->registerTool('search', 'SearchTool');
        $this->registerTool('calendar', 'CalendarTool');
        $this->registerTool('database', 'DatabaseTool');
        
        // Register domain-specific tools from main codebase
        $this->registerTool('fitness', 'FitnessTool');
        $this->registerTool('meditation', 'MeditationTool');
        $this->registerTool('contacts', 'ContactsTool');
        $this->registerTool('notes', 'NotesTool');
        $this->registerTool('conversation', 'ConversationCache');
        $this->registerTool('prompt', 'PromptBuilder');
        $this->registerTool('response', 'ToolResponseHandler');
        
        // Configure default agent permissions
        $this->configureAgentPermissions('GeneralistAgent', ['weather', 'search', 'calendar', 'database', 'conversation']);
        $this->configureAgentPermissions('FinanceAgent', ['database', 'search', 'calendar', 'conversation']);
        $this->configureAgentPermissions('MemoryAgent', ['database', 'conversation']);
        $this->configureAgentPermissions('PlannerAgent', ['calendar', 'database', 'conversation']);
        
        // Configure new domain-specific agent permissions
        $this->configureAgentPermissions('HealthAgent', ['fitness', 'database', 'calendar', 'weather', 'conversation']);
        $this->configureAgentPermissions('SpiritualAgent', ['meditation', 'database', 'calendar', 'conversation']);
        $this->configureAgentPermissions('SocialAgent', ['contacts', 'calendar', 'database', 'conversation']);
        $this->configureAgentPermissions('LearningAgent', ['notes', 'search', 'database', 'conversation']);
        $this->configureAgentPermissions('PlannerAgent', ['calendar', 'database', 'search']);
        
        // Configure permissions for new domain-specific agents
        $this->configureAgentPermissions('HealthAgent', ['database', 'search', 'calendar', 'weather', 'fitness']);
        $this->configureAgentPermissions('SpiritualAgent', ['database', 'search', 'meditation']);
        $this->configureAgentPermissions('SocialAgent', ['database', 'calendar', 'search', 'contacts']);
        $this->configureAgentPermissions('LearningAgent', ['database', 'search', 'calendar', 'notes']);
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
     * @return object|null Tool instance or null if not permitted/available
     */
    public function getTool(string $toolName, ?string $agentName = null): ?object {
        // Check permissions if agent is specified
        if ($agentName && !$this->hasToolAccess($agentName, $toolName)) {
            return null;
        }
        
        // Return existing instance if already created
        if (isset($this->toolInstances[$toolName])) {
            return $this->toolInstances[$toolName];
        }
        
        // Create new instance if tool exists
        $className = $this->tools[$toolName] ?? null;
        if (!$className || !class_exists($className)) {
            // For POC, just return a mock tool
            return new MockTool($toolName);
        }
        
        try {
            // Create instance
            $instance = new $className();
            
            // Store instance for reuse
            $this->toolInstances[$toolName] = $instance;
            return $instance;
        } catch (Exception $e) {
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
        return isset($this->agentPermissions[$agentName][$toolName]);
    }
    
    /**
     * Get list of all tools available to an agent
     * 
     * @param string $agentName Name of the agent
     * @return array List of tool names available to the agent
     */
    public function getAvailableTools(string $agentName): array {
        return array_keys($this->agentPermissions[$agentName] ?? []);
    }
}

/**
 * Mock tool for demonstration purposes
 */
class MockTool {
    private string $name;
    
    public function __construct(string $name) {
        $this->name = $name;
    }
    
    public function execute(array $params): string {
        return "Executed {$this->name} with params: " . json_encode($params);
    }
}

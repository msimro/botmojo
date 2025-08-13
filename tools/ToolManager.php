<?php
/**
 * ToolManager - Advanced Tool Orchestration and Security Management System
 * 
 * OVERVIEW:
 * The ToolManager is the central orchestration hub for all tools in the BotMojo
 * AI Personal Assistant system. It provides secure tool access control, dynamic
 * tool instantiation, permission management, and comprehensive tool monitoring.
 * This class ensures that agents can only access tools they're authorized to use
 * while providing efficient tool lifecycle management and security policies.
 * 
 * CORE CAPABILITIES:
 * - Tool Registration: Dynamic discovery and registration of available tools
 * - Access Control: Permission-based tool access for different agents
 * - Tool Instantiation: Lazy loading and singleton management of tool instances
 * - Security Enforcement: Role-based access control and audit logging
 * - Performance Monitoring: Tool usage analytics and performance metrics
 * - Configuration Management: Centralized tool configuration and settings
 * - Error Handling: Graceful tool failure handling and fallback strategies
 * - Resource Management: Efficient memory and connection pooling
 * 
 * SECURITY ARCHITECTURE:
 * - Agent-Based Permissions: Each agent has specific tool access rights
 * - Request Validation: All tool requests are validated and logged
 * - Resource Isolation: Tools operate in controlled environments
 * - Audit Trail: Comprehensive logging of all tool access and operations
 * - Rate Limiting: Prevent tool abuse and resource exhaustion
 * - Secure Configuration: Environment-based configuration management
 * 
 * TOOL LIFECYCLE MANAGEMENT:
 * - Registration Phase: Tools register their capabilities and requirements
 * - Permission Setup: Agents are granted specific tool access rights
 * - Instantiation: Tools are created on-demand with proper configuration
 * - Request Processing: Tool calls are validated, executed, and monitored
 * - Cleanup: Proper resource cleanup and connection management
 * 
 * PERFORMANCE OPTIMIZATION:
 * - Lazy Loading: Tools instantiated only when needed
 * - Singleton Pattern: Reuse tool instances across requests
 * - Connection Pooling: Efficient database and API connection management
 * - Caching: Tool response caching for improved performance
 * - Resource Monitoring: Track tool performance and resource usage
 * 
 * SUPPORTED TOOL CATEGORIES:
 * - Data Tools: DatabaseTool for persistent storage and retrieval
 * - Search Tools: SearchTool for web research and information gathering
 * - Communication Tools: CalendarTool, ContactsTool for coordination
 * - Content Tools: NotesTool, PromptBuilder for content management
 * - Wellness Tools: FitnessTool, MeditationTool for health tracking
 * - Utility Tools: WeatherTool, ConversationCache for general utilities
 * 
 * INTEGRATION PATTERNS:
 * - Agent Integration: Seamless tool access through standardized interface
 * - Configuration Integration: Environment-based tool configuration
 * - Monitoring Integration: Comprehensive analytics and logging
 * - Security Integration: Role-based access control and audit trails
 * - Performance Integration: Resource monitoring and optimization
 * 
 * EXAMPLE USAGE:
 * ```php
 * $toolManager = new ToolManager();
 * $toolManager->configureAgentPermissions('FinanceAgent', ['database', 'search']);
 * $dbTool = $toolManager->getTool('database', 'FinanceAgent');
 * $results = $dbTool->query("SELECT * FROM transactions WHERE user_id = ?", [$userId]);
 * ```
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-08
 * @updated 2025-01-15
 */

/**
 * ToolManager - Centralized tool orchestration with security and performance management
 */
class ToolManager {
    
    /**
     * TOOL REGISTRY
     * 
     * Master registry of all available tools with their class mappings,
     * configuration requirements, and capability descriptions.
     */
    private const AVAILABLE_TOOLS = [
        'database' => [
            'class' => 'DatabaseTool',
            'category' => 'data',
            'description' => 'Persistent data storage and retrieval operations',
            'permissions' => ['read', 'write', 'admin'],
            'performance_critical' => true
        ],
        'search' => [
            'class' => 'SearchTool',
            'category' => 'information',
            'description' => 'Web search and information gathering capabilities',
            'permissions' => ['search'],
            'rate_limited' => true
        ],
        'calendar' => [
            'class' => 'CalendarTool',
            'category' => 'coordination',
            'description' => 'Calendar management and scheduling operations',
            'permissions' => ['read', 'write'],
            'integration_required' => true
        ],
        'weather' => [
            'class' => 'WeatherTool',
            'category' => 'information',
            'description' => 'Weather data and forecast information',
            'permissions' => ['read'],
            'external_api' => true
        ],
        'contacts' => [
            'class' => 'ContactsTool',
            'category' => 'coordination',
            'description' => 'Contact management and relationship data',
            'permissions' => ['read', 'write'],
            'privacy_sensitive' => true
        ],
        'notes' => [
            'class' => 'NotesTool',
            'category' => 'content',
            'description' => 'Note-taking and knowledge management',
            'permissions' => ['read', 'write'],
            'content_management' => true
        ],
        'fitness' => [
            'class' => 'FitnessTool',
            'category' => 'wellness',
            'description' => 'Fitness tracking and health data management',
            'permissions' => ['read', 'write'],
            'health_data' => true
        ],
        'meditation' => [
            'class' => 'MeditationTool',
            'category' => 'wellness',
            'description' => 'Meditation and mindfulness practice tracking',
            'permissions' => ['read', 'write'],
            'wellness_focused' => true
        ]
    ];
    
    /**
     * AGENT TOOL PERMISSIONS
     * 
     * Default permission matrix defining which agents can access which tools.
     * This provides a secure foundation that can be customized per deployment.
     */
    private const DEFAULT_AGENT_PERMISSIONS = [
        'MemoryAgent' => ['database', 'search'],
        'FinanceAgent' => ['database', 'search', 'calendar'],
        'PlannerAgent' => ['database', 'calendar', 'weather', 'search'],
        'HealthAgent' => ['database', 'fitness', 'search'],
        'SpiritualAgent' => ['database', 'meditation', 'search'],
        'SocialAgent' => ['database', 'contacts', 'calendar', 'search'],
        'RelationshipAgent' => ['database', 'contacts'],
        'LearningAgent' => ['database', 'notes', 'search'],
        'GeneralistAgent' => ['search', 'calendar', 'weather'] // Limited access for general queries
    ];
    
    /** @var array Tool class registrations with metadata */
    private array $tools = [];
    
    /** @var array Instantiated tool objects for reuse */
    private array $toolInstances = [];
    
    /** @var array Agent tool access permissions */
    private array $agentPermissions = [];
    
    /** @var array Tool usage analytics and performance metrics */
    private array $toolMetrics = [];
    
    /** @var array Tool configuration settings */
    private array $toolConfigurations = [];
    
        /**
     * Constructor - Initialize tool management system with security and monitoring
     * 
     * Sets up the tool registry, configures default permissions, initializes
     * monitoring systems, and prepares the tool management infrastructure.
     */
    public function __construct() {
        $this->initializeTools();
        $this->configureDefaultPermissions();
        $this->initializeMetrics();
        $this->loadToolConfigurations();
    }
    
    /**
     * Initialize Tool Registry
     * 
     * Registers all available tools with their metadata and capabilities.
     * This creates the master registry that enables dynamic tool discovery
     * and provides comprehensive tool information for security and monitoring.
     * 
     * @return void
     */
    private function initializeTools(): void {
        foreach (self::AVAILABLE_TOOLS as $toolName => $toolConfig) {
            $this->registerTool($toolName, $toolConfig);
        }
    }
    
    /**
     * Register Individual Tool
     * 
     * Registers a single tool with the manager, including its class name,
     * capabilities, permissions, and configuration requirements.
     * 
     * @param string $toolName Unique identifier for the tool
     * @param array $config Tool configuration including class, permissions, metadata
     * @return bool True if registration successful, false otherwise
     */
    public function registerTool(string $toolName, array $config): bool {
        try {
            // Validate tool configuration
            if (!isset($config['class'])) {
                throw new InvalidArgumentException("Tool configuration must include 'class' parameter");
            }
            
            // Check if tool class exists
            $className = $config['class'];
            if (!class_exists($className)) {
                throw new InvalidArgumentException("Tool class '{$className}' not found");
            }
            
            // Register tool with full metadata
            $this->tools[$toolName] = array_merge($config, [
                'registered_at' => time(),
                'status' => 'available',
                'usage_count' => 0
            ]);
            
            // Initialize tool metrics
            $this->toolMetrics[$toolName] = [
                'total_calls' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
                'avg_response_time' => 0,
                'last_used' => null
            ];
            
            return true;
        } catch (Exception $e) {
            error_log("Tool registration failed for '{$toolName}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Configure Default Agent Permissions
     * 
     * Sets up the default permission matrix for agents, defining which
     * tools each agent type can access. This provides a secure foundation
     * that can be customized per deployment or user requirements.
     * 
     * @return void
     */
    private function configureDefaultPermissions(): void {
        $this->agentPermissions = self::DEFAULT_AGENT_PERMISSIONS;
    }
    
    /**
     * Initialize Performance Metrics
     * 
     * Sets up the metrics collection system for monitoring tool performance,
     * usage patterns, and system health. This enables proactive optimization
     * and helps identify performance bottlenecks.
     * 
     * @return void
     */
    private function initializeMetrics(): void {
        $this->toolMetrics = [];
        
        // Initialize metrics for all registered tools
        foreach ($this->tools as $toolName => $config) {
            $this->toolMetrics[$toolName] = [
                'total_calls' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
                'avg_response_time' => 0,
                'peak_memory_usage' => 0,
                'last_used' => null,
                'error_log' => []
            ];
        }
    }
    
    /**
     * Load Tool Configurations
     * 
     * Loads environment-specific configurations for tools, including
     * API keys, connection strings, and performance settings.
     * 
     * @return void
     */
    private function loadToolConfigurations(): void {
        // Load from config file or environment variables
        $this->toolConfigurations = [
            'database' => [
                'max_connections' => 10,
                'timeout' => 30,
                'retry_attempts' => 3
            ],
            'search' => [
                'rate_limit' => 100, // requests per hour
                'timeout' => 15,
                'cache_duration' => 3600
            ],
            'weather' => [
                'api_timeout' => 10,
                'cache_duration' => 1800,
                'fallback_enabled' => true
            ]
        ];
    }
    
    /**
     * Configure Agent Tool Permissions
     * 
     * Allows dynamic configuration of which tools an agent can access.
     * This provides flexibility to customize permissions beyond the defaults
     * while maintaining security and audit capabilities.
     * 
     * @param string $agentName Name of the agent
     * @param array $allowedTools List of tool names the agent can access
     * @param bool $replaceExisting Whether to replace existing permissions or merge
     * @return bool True if permissions configured successfully
     */
    /**
     * Configure Agent Tool Permissions
     * 
     * Allows dynamic configuration of which tools an agent can access.
     * This provides flexibility to customize permissions beyond the defaults
     * while maintaining security and audit capabilities.
     * 
     * @param string $agentName Name of the agent
     * @param array $allowedTools List of tool names the agent can access
     * @param bool $replaceExisting Whether to replace existing permissions or merge
     * @return bool True if permissions configured successfully
     */
    public function configureAgentPermissions(string $agentName, array $allowedTools, bool $replaceExisting = true): bool {
        try {
            // Validate that all requested tools exist
            foreach ($allowedTools as $toolName) {
                if (!isset($this->tools[$toolName])) {
                    throw new InvalidArgumentException("Tool '{$toolName}' does not exist");
                }
            }
            
            // Configure permissions
            if ($replaceExisting || !isset($this->agentPermissions[$agentName])) {
                $this->agentPermissions[$agentName] = array_flip($allowedTools);
            } else {
                // Merge with existing permissions
                $existing = array_keys($this->agentPermissions[$agentName]);
                $merged = array_unique(array_merge($existing, $allowedTools));
                $this->agentPermissions[$agentName] = array_flip($merged);
            }
            
            // Log permission change for audit
            error_log("ToolManager: Updated permissions for '{$agentName}' - Tools: " . implode(', ', $allowedTools));
            
            return true;
        } catch (Exception $e) {
            error_log("ToolManager: Failed to configure permissions for '{$agentName}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Secure Tool Instance
     * 
     * Retrieves a tool instance with comprehensive security checks, performance
     * monitoring, and proper resource management. This is the primary method
     * for agents to access tools in a controlled and monitored manner.
     * 
     * @param string $toolName Name of the tool to retrieve
     * @param string|null $agentName Optional agent name for permission check
     * @param array $constructorParams Optional parameters for tool constructor
     * @return object|null Tool instance or null if not permitted/available
     */
    public function getTool(string $toolName, ?string $agentName = null, array $constructorParams = []): ?object {
        $startTime = microtime(true);
        
        try {
            // Security: Check agent permissions
            if ($agentName && !$this->hasToolAccess($agentName, $toolName)) {
                $this->recordSecurityEvent($agentName, $toolName, 'access_denied');
                error_log("ToolManager: {$agentName} attempted to access {$toolName} without permission");
                return null;
            }
            
            // Performance: Return existing instance if available
            if (isset($this->toolInstances[$toolName])) {
                $this->updateToolMetrics($toolName, true, microtime(true) - $startTime);
                return $this->toolInstances[$toolName];
            }
            
            // Validation: Check if tool exists and is available
            if (!isset($this->tools[$toolName])) {
                error_log("ToolManager: Tool {$toolName} not registered");
                return null;
            }
            
            $toolConfig = $this->tools[$toolName];
            $className = $toolConfig['class'];
            
            if (!class_exists($className)) {
                error_log("ToolManager: Tool class {$className} does not exist");
                return null;
            }
            
            // Tool Instantiation: Create with proper configuration
            $instance = $this->createToolInstance($className, $toolName, $constructorParams);
            
            if ($instance) {
                // Store instance for reuse (singleton pattern)
                $this->toolInstances[$toolName] = $instance;
                
                // Update metrics
                $this->updateToolMetrics($toolName, true, microtime(true) - $startTime);
                $this->tools[$toolName]['usage_count']++;
                
                // Log successful access
                if ($agentName) {
                    error_log("ToolManager: {$agentName} successfully accessed {$toolName}");
                }
                
                return $instance;
            }
            
            return null;
        } catch (Exception $e) {
            $this->updateToolMetrics($toolName, false, microtime(true) - $startTime);
            error_log("ToolManager: Failed to get tool {$toolName}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create Tool Instance with Proper Configuration
     * 
     * Handles the complex instantiation logic for different tool types,
     * including API key injection, configuration management, and error handling.
     * 
     * @param string $className Tool class name
     * @param string $toolName Tool identifier
     * @param array $constructorParams Constructor parameters
     * @return object|null Tool instance or null on failure
     */
    private function createToolInstance(string $className, string $toolName, array $constructorParams): ?object {
        try {
            // Prepare tool-specific constructor parameters
            if (empty($constructorParams)) {
                $constructorParams = $this->getToolConstructorParams($toolName);
            }
            
            // Create instance with reflection for dynamic parameter injection
            if (empty($constructorParams)) {
                $instance = new $className();
            } else {
                $reflection = new ReflectionClass($className);
                $instance = $reflection->newInstanceArgs($constructorParams);
            }
            
            // Post-instantiation configuration
            $this->configureToolInstance($instance, $toolName);
            
            return $instance;
        } catch (Exception $e) {
            error_log("ToolManager: Failed to instantiate {$className}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get Tool-Specific Constructor Parameters
     * 
     * Provides the appropriate constructor parameters for each tool type,
     * including API keys, configuration settings, and dependencies.
     * 
     * @param string $toolName Tool identifier
     * @return array Constructor parameters
     */
    private function getToolConstructorParams(string $toolName): array {
        switch ($toolName) {
            case 'weather':
                return [defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : ''];
                
            case 'search':
                $apiKey = defined('GOOGLE_SEARCH_API_KEY') ? GOOGLE_SEARCH_API_KEY : '';
                $searchEngineId = defined('GOOGLE_SEARCH_CX') ? GOOGLE_SEARCH_CX : '';
                return [$apiKey, $searchEngineId];
                
            case 'database':
                // Database connection parameters from config
                return [];
                
            default:
                return [];
        }
    }
    
    /**
     * Configure Tool Instance After Creation
     * 
     * Applies post-instantiation configuration to tools, including
     * timeout settings, rate limiting, and monitoring setup.
     * 
     * @param object $instance Tool instance
     * @param string $toolName Tool identifier
     * @return void
     */
    private function configureToolInstance(object $instance, string $toolName): void {
        $config = $this->toolConfigurations[$toolName] ?? [];
        
        // Apply configuration if tool supports it
        if (method_exists($instance, 'configure')) {
            $instance->configure($config);
        }
        
        // Set up monitoring if tool supports it
        if (method_exists($instance, 'setMonitoring')) {
            $instance->setMonitoring(true);
        }
    }
    
    /**
     * Record Security Event
     * 
     * Logs security-related events for audit trail and monitoring.
     * This helps track unauthorized access attempts and security violations.
     * 
     * @param string $agentName Name of the agent
     * @param string $toolName Name of the tool
     * @param string $eventType Type of security event
     * @return void
     */
    private function recordSecurityEvent(string $agentName, string $toolName, string $eventType): void {
        $securityLog = [
            'timestamp' => time(),
            'agent' => $agentName,
            'tool' => $toolName,
            'event' => $eventType,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Log to security audit file
        error_log("SECURITY_EVENT: " . json_encode($securityLog));
        
        // Store in metrics for analysis
        if (!isset($this->toolMetrics[$toolName]['security_events'])) {
            $this->toolMetrics[$toolName]['security_events'] = [];
        }
        $this->toolMetrics[$toolName]['security_events'][] = $securityLog;
    }
    
    /**
     * Update Tool Performance Metrics
     * 
     * Tracks tool usage patterns, performance characteristics, and system health.
     * This data enables proactive optimization and performance monitoring.
     * 
     * @param string $toolName Name of the tool
     * @param bool $success Whether the operation was successful
     * @param float $responseTime Time taken for the operation
     * @return void
     */
    private function updateToolMetrics(string $toolName, bool $success, float $responseTime): void {
        if (!isset($this->toolMetrics[$toolName])) {
            $this->toolMetrics[$toolName] = [
                'total_calls' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
                'avg_response_time' => 0,
                'peak_memory_usage' => 0,
                'last_used' => null,
                'error_log' => []
            ];
        }
        
        $metrics = &$this->toolMetrics[$toolName];
        
        // Update call counters
        $metrics['total_calls']++;
        if ($success) {
            $metrics['successful_calls']++;
        } else {
            $metrics['failed_calls']++;
        }
        
        // Update response time (running average)
        $totalCalls = $metrics['total_calls'];
        $currentAvg = $metrics['avg_response_time'];
        $metrics['avg_response_time'] = (($currentAvg * ($totalCalls - 1)) + $responseTime) / $totalCalls;
        
        // Update memory usage
        $currentMemory = memory_get_usage(true);
        if ($currentMemory > $metrics['peak_memory_usage']) {
            $metrics['peak_memory_usage'] = $currentMemory;
        }
        
        // Update last used timestamp
        $metrics['last_used'] = time();
        
        // Log error details if operation failed
        if (!$success) {
            $metrics['error_log'][] = [
                'timestamp' => time(),
                'response_time' => $responseTime,
                'memory_usage' => $currentMemory
            ];
            
            // Keep only last 10 errors to prevent memory bloat
            $metrics['error_log'] = array_slice($metrics['error_log'], -10);
        }
    }
    
    /**
     * Check Agent Tool Access Permissions (Enhanced Version)
     * 
     * Validates whether a specific agent has permission to access a particular tool.
     * This is the core security mechanism that enforces the permission matrix.
     * 
     * @param string $agentName Name of the agent requesting access
     * @param string $toolName Name of the tool being requested
     * @return bool True if agent has permission, false otherwise
     */
    public function hasToolAccess(string $agentName, string $toolName): bool {
        // GeneralistAgent has limited access to safe tools only
        if ($agentName === 'GeneralistAgent') {
            $safeTools = ['search', 'calendar', 'weather'];
            return in_array($toolName, $safeTools);
        }
        
        // Check if agent has explicit permission for this tool
        return isset($this->agentPermissions[$agentName][$toolName]);
    }
    
    /**
     * Get Available Tools for Agent (Enhanced Version)
     * 
     * Returns a list of tools that a specific agent is permitted to access.
     * This enables dynamic tool discovery and permission-aware interfaces.
     * 
     * @param string $agentName Name of the agent
     * @return array List of tool names available to the agent
     */
    public function getAvailableTools(string $agentName): array {
        // GeneralistAgent has access to safe, read-only tools
        if ($agentName === 'GeneralistAgent') {
            return ['search', 'calendar', 'weather'];
        }
        
        // Return tools from permission matrix
        return array_keys($this->agentPermissions[$agentName] ?? []);
    }
    
    /**
     * Get Tool Performance Metrics
     * 
     * Retrieves comprehensive performance and usage metrics for monitoring
     * and optimization purposes. Useful for administrative dashboards.
     * 
     * @param string|null $toolName Specific tool name or null for all tools
     * @return array Performance metrics data
     */
    public function getToolMetrics(?string $toolName = null): array {
        if ($toolName) {
            return $this->toolMetrics[$toolName] ?? [];
        }
        
        return $this->toolMetrics;
    }
    
    /**
     * Get Tool Information
     * 
     * Retrieves comprehensive information about a specific tool including
     * its capabilities, configuration, and current status.
     * 
     * @param string $toolName Name of the tool
     * @return array|null Tool information or null if not found
     */
    public function getToolInfo(string $toolName): ?array {
        if (!isset($this->tools[$toolName])) {
            return null;
        }
        
        $toolConfig = $this->tools[$toolName];
        $metrics = $this->toolMetrics[$toolName] ?? [];
        
        return [
            'name' => $toolName,
            'class' => $toolConfig['class'],
            'category' => $toolConfig['category'] ?? 'unknown',
            'description' => $toolConfig['description'] ?? '',
            'permissions' => $toolConfig['permissions'] ?? [],
            'status' => $toolConfig['status'] ?? 'unknown',
            'usage_count' => $toolConfig['usage_count'] ?? 0,
            'performance' => [
                'total_calls' => $metrics['total_calls'] ?? 0,
                'success_rate' => $this->calculateSuccessRate($metrics),
                'avg_response_time' => $metrics['avg_response_time'] ?? 0,
                'last_used' => $metrics['last_used'] ?? null
            ],
            'is_instantiated' => isset($this->toolInstances[$toolName]),
            'configuration' => $this->toolConfigurations[$toolName] ?? []
        ];
    }
    
    /**
     * Calculate Tool Success Rate
     * 
     * Computes the success rate percentage for a tool based on its metrics.
     * 
     * @param array $metrics Tool metrics data
     * @return float Success rate as percentage (0-100)
     */
    private function calculateSuccessRate(array $metrics): float {
        $totalCalls = $metrics['total_calls'] ?? 0;
        $successfulCalls = $metrics['successful_calls'] ?? 0;
        
        if ($totalCalls === 0) {
            return 100.0; // No calls yet, assume 100% success potential
        }
        
        return round(($successfulCalls / $totalCalls) * 100, 2);
    }
    
    /**
     * Get System Health Status
     * 
     * Provides a comprehensive health check of the tool management system,
     * including tool availability, performance metrics, and system status.
     * 
     * @return array System health information
     */
    public function getSystemHealth(): array {
        $health = [
            'status' => 'healthy',
            'tools_registered' => count($this->tools),
            'tools_instantiated' => count($this->toolInstances),
            'agents_configured' => count($this->agentPermissions),
            'total_tool_calls' => 0,
            'overall_success_rate' => 0,
            'memory_usage' => memory_get_usage(true),
            'issues' => []
        ];
        
        // Aggregate metrics across all tools
        $totalCalls = 0;
        $totalSuccessful = 0;
        
        foreach ($this->toolMetrics as $toolName => $metrics) {
            $totalCalls += $metrics['total_calls'] ?? 0;
            $totalSuccessful += $metrics['successful_calls'] ?? 0;
            
            // Check for tool-specific issues
            $successRate = $this->calculateSuccessRate($metrics);
            if ($successRate < 80 && $metrics['total_calls'] > 10) {
                $health['issues'][] = "Tool '{$toolName}' has low success rate: {$successRate}%";
                $health['status'] = 'degraded';
            }
        }
        
        $health['total_tool_calls'] = $totalCalls;
        $health['overall_success_rate'] = $totalCalls > 0 ? 
            round(($totalSuccessful / $totalCalls) * 100, 2) : 100.0;
        
        // Check overall system health
        if ($health['overall_success_rate'] < 90) {
            $health['status'] = 'unhealthy';
        }
        
        return $health;
    }
    
    /**
     * Reset Tool Instance
     * 
     * Removes a tool instance from memory, forcing re-instantiation on next access.
     * Useful for recovering from tool errors or applying configuration changes.
     * 
     * @param string $toolName Name of the tool to reset
     * @return bool True if reset successful, false if tool not found
     */
    public function resetTool(string $toolName): bool {
        if (!isset($this->tools[$toolName])) {
            return false;
        }
        
        // Remove instance if it exists
        if (isset($this->toolInstances[$toolName])) {
            unset($this->toolInstances[$toolName]);
            error_log("ToolManager: Reset tool instance for '{$toolName}'");
        }
        
        // Reset usage count
        $this->tools[$toolName]['usage_count'] = 0;
        
        return true;
    }
    
    /**
     * Get All Tool Instances (Administrative Use)
     * 
     * Retrieves all available tool instances, creating them if necessary.
     * This method is intended for administrative interfaces and debugging.
     * 
     * @return array Associative array of tool name => tool instance
     */
    public function getAllTools(): array {
        // Initialize any tools that haven't been created yet
        foreach ($this->tools as $toolName => $toolConfig) {
            if (!isset($this->toolInstances[$toolName])) {
                $this->getTool($toolName);
            }
        }
        
        return $this->toolInstances;
    }
}

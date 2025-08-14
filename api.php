<?php
/**
 * BotMojo API Orchestrator - Triage-First Agent System Entry Point
 * 
 * =====================================================================
 * ARCHITECTURAL OVERVIEW
 * =====================================================================
 * 
 * This file serves as the central orchestrator for the BotMojo AI Personal 
 * Assistant system. It implements a sophisticated triage-first, agent-based 
 * architecture that intelligently processes user queries through specialized 
 * AI agents.
 * 
 * CORE ARCHITECTURE PRINCIPLES:
 * - Triage-First Design: Every request is analyzed by AI before processing
 * - Agent-Based Processing: Specialized agents handle domain-specific tasks
 * - Component Assembly: Multiple agents contribute to unified entity creation
 * - Unified Storage: All data stored in flexible JSON-based entity system
 * - Conversation Context: Maintains history for contextual understanding
 * 
 * PROCESSING WORKFLOW:
 * 1. 📥 INPUT PHASE: Receive and validate user query via HTTP POST
 * 2. 🤖 TRIAGE PHASE: AI analyzes intent and creates execution plan
 * 3. 🎯 ROUTING PHASE: Tasks distributed to appropriate specialized agents
 * 4. 🔧 PROCESSING PHASE: Agents create domain-specific components
 * 5. 📦 ASSEMBLY PHASE: Components combined into unified entity
 * 6. 💾 STORAGE PHASE: Entity persisted to database with relationships
 * 7. 📝 HISTORY PHASE: Conversation context updated and cached
 * 8. ✅ RESPONSE PHASE: Enhanced response returned to client
 * 
 * AGENT ECOSYSTEM:
 * - MemoryAgent: Knowledge graph and relationship management
 * - PlannerAgent: Scheduling, tasks, and goal management  
 * - FinanceAgent: Financial tracking and expense analysis
 * - HealthAgent: Wellness, fitness, and medical data
 * - SpiritualAgent: Meditation, mindfulness, and spiritual practices
 * - SocialAgent: Social events and communication patterns
 * - RelationshipAgent: Entity relationship analysis and creation
 * - LearningAgent: Educational content and skill development
 * - GeneralistAgent: Fallback for general queries and conversation
 * 
 * TECHNOLOGY STACK:
 * - PHP 8.3 with strict typing and modern OOP practices
 * - Google Gemini 1.5-flash for AI-powered triage analysis
 * - MySQL with JSON columns for flexible entity storage
 * - File-based conversation caching for context preservation
 * - DDEV for local development environment
 * 
 * SECURITY FEATURES:
 * - Input validation and sanitization
 * - Prepared database statements
 * - CORS headers for cross-origin requests
 * - Error handling without information leakage
 * - Tool permission system for agent access control
 * 
 * @author BotMojo Development Team
 * @version 2.0.0
 * @since 2025-08-14
 * @license MIT
 * 
 * @see config.php Configuration constants and utility functions
 * @see src/Core/Orchestrator.php Main request orchestration logic
 * @see src/Agents/ Specialized AI agent implementations
 * @see src/Tools/ Core tool and service classes
 * @see index.php Frontend chat interface
 * @see dashboard.php Data visualization interface
 * 
 * =====================================================================
 */

// =====================================================================
// DEPENDENCY LOADING AND INITIALIZATION
// =====================================================================

declare(strict_types=1);

// Enable error reporting for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Load core configuration and initialize the application environment
 * This includes database constants, API keys, and utility functions
 */
require_once 'config.php';

// Import required classes
use BotMojo\Core\ServiceContainer;
use BotMojo\Core\Orchestrator;
use BotMojo\Exceptions\BotMojoException;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\GeminiTool;
use BotMojo\Tools\HistoryTool;
use BotMojo\Tools\PromptBuilder;

// =====================================================================
// HTTP HEADERS AND CORS CONFIGURATION
// =====================================================================

/**
 * Configure HTTP response headers for JSON API and cross-origin support
 * 
 * CORS POLICY:
 * - Allows all origins for development (should be restricted in production)
 * - Supports POST, GET, and OPTIONS methods
 * - Accepts Content-Type header for JSON requests
 * 
 * SECURITY NOTE: In production, replace * with specific allowed origins
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // TODO: Restrict in production
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, must-revalidate');

/**
 * Handle preflight CORS requests (OPTIONS method)
 * Browser sends OPTIONS request before actual request for CORS validation
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

/**
 * =====================================================================
 * MAIN REQUEST PROCESSING PIPELINE
 * =====================================================================
 * 
 * All request processing is wrapped in a try-catch block to ensure
 * graceful error handling and consistent response format.
 */
try {
    // =====================================================================
    // PHASE 1: INPUT VALIDATION AND PARAMETER EXTRACTION
    // =====================================================================
    
    /**
     * Parse and validate incoming JSON request data
     * 
     * EXPECTED REQUEST FORMAT:
     * {
     *     "query": "User's natural language input",
     *     "conversation_id": "unique_conversation_identifier", 
     *     "debug_mode": boolean (optional)
     * }
     * 
     * VALIDATION RULES:
     * - Query must be present and non-empty string
     * - Conversation ID defaults to 'default_conversation' if not provided
     * - Debug mode can override global DEBUG_MODE setting
     */
    
    // Capture raw input stream for debugging and parsing
    $rawInput = file_get_contents('php://input');
    
    // Always log input for debugging during this issue
    error_log("🔍 API Input Received: " . $rawInput);
    error_log("🔍 Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("🔍 Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    
    // Parse JSON input with error checking
    $input = json_decode($rawInput, true);
    
    // Validate input format
    if (!is_array($input)) {
        throw new BotMojoException(
            "Invalid request format: JSON object expected", 
            ['input' => $rawInput]
        );
    }
    
    // Extract and validate required parameters
    if (!isset($input['query']) || empty(trim($input['query']))) {
        throw new BotMojoException(
            "Missing required parameter: query", 
            ['input' => $input]
        );
    }
    
    // Set default conversation ID if not provided
    if (!isset($input['conversation_id']) || empty($input['conversation_id'])) {
        $input['conversation_id'] = 'default_conversation';
    }
    
    // Allow request-level debug mode override
    $debugMode = defined('DEBUG_MODE') ? DEBUG_MODE : false;
    if (isset($input['debug_mode']) && is_bool($input['debug_mode'])) {
        $debugMode = $input['debug_mode'];
    }
    
    // Validate query length for security
    if (strlen($input['query']) > 2000) {
        throw new BotMojoException(
            'Query too long. Please limit input to 2000 characters.',
            ['query_length' => strlen($input['query'])]
        );
    }
    
    // Sanitize conversation ID to prevent path traversal attacks
    $input['conversation_id'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['conversation_id']);
    if (empty($input['conversation_id'])) {
        $input['conversation_id'] = 'default_conversation';
    }
    
    // Log processed parameters in debug mode
    if ($debugMode) {
        error_log("✅ Validated Parameters:");
        error_log("   - User Query: " . substr($input['query'], 0, 100) . (strlen($input['query']) > 100 ? '...' : ''));
        error_log("   - Conversation ID: " . $input['conversation_id']);
        error_log("   - Debug Mode: " . ($debugMode ? 'enabled' : 'disabled'));
    }
    
    // =====================================================================
    // PHASE 2: CORE SYSTEM INITIALIZATION
    // =====================================================================
    
    /**
     * Initialize core system components and tools
     * 
     * TOOL ARCHITECTURE:
     * - ServiceContainer: Dependency injection container
     * - DatabaseTool: Entity storage and retrieval operations
     * - GeminiTool: Interface to Google Gemini AI API
     * - HistoryTool: Conversation history management
     * - PromptBuilder: Dynamic AI prompt assembly from templates
     * 
     * All tools are initialized once and reused throughout the request
     * lifecycle to optimize performance and maintain consistency.
     */
    
    // Initialize the service container
    $container = new ServiceContainer();
    
    // Create tool instances
    $dbTool = new DatabaseTool([
        'host' => $_ENV['DB_HOST'] ?? 'db', 
        'database' => $_ENV['DB_NAME'] ?? 'db', 
        'user' => $_ENV['DB_USER'] ?? 'db', 
        'password' => $_ENV['DB_PASS'] ?? 'db'
    ]);
    
    $geminiTool = new GeminiTool(['api_key' => $_ENV['API_KEY'] ?? '']);
    
    $historyTool = new HistoryTool($dbTool);
    
    $promptBuilder = new PromptBuilder(['prompt_dir' => __DIR__ . '/prompts']);
    
    // Register tools in the service container
    $container->set('tool.database', function() use ($dbTool) {
        return $dbTool;
    });
    
    $container->set('tool.gemini', function() use ($geminiTool) {
        return $geminiTool;
    });
    
    $container->set('tool.history', function() use ($historyTool) {
        return $historyTool;
    });
    
    $container->set('tool.prompt_builder', function() use ($promptBuilder) {
        return $promptBuilder;
    });
    
    // Register additional tools
    $container->set('tool.weather', function() {
        return new \BotMojo\Tools\WeatherTool(['api_key' => $_ENV['WEATHER_API_KEY'] ?? '']);
    });
    
    $container->set('tool.calendar', function() {
        return new \BotMojo\Tools\CalendarTool();
    });
    
    $container->set('tool.contacts', function() {
        return new \BotMojo\Tools\ContactsTool();
    });
    
    $container->set('tool.search', function() {
        return new \BotMojo\Tools\SearchTool();
    });
    
    $container->set('tool.fitness', function() {
        return new \BotMojo\Tools\FitnessTool();
    });
    
    $container->set('tool.meditation', function() {
        return new \BotMojo\Tools\MeditationTool();
    });
    
    $container->set('tool.notes', function() {
        return new \BotMojo\Tools\NotesTool();
    });
    
    // Register agents
    $container->set('agent.memory', function() use ($container) {
        return new \BotMojo\Agents\MemoryAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.finance', function() use ($container) {
        return new \BotMojo\Agents\FinanceAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.health', function() use ($container) {
        return new \BotMojo\Agents\HealthAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.learning', function() use ($container) {
        return new \BotMojo\Agents\LearningAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.planner', function() use ($container) {
        return new \BotMojo\Agents\PlannerAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.relationship', function() use ($container) {
        return new \BotMojo\Agents\RelationshipAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.social', function() use ($container) {
        return new \BotMojo\Agents\SocialAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.spiritual', function() use ($container) {
        return new \BotMojo\Agents\SpiritualAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    $container->set('agent.generalist', function() use ($container) {
        return new \BotMojo\Agents\GeneralistAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    if ($debugMode) {
        error_log("� Service container initialized with core tools and agents");
    }
    
    // =====================================================================
    // PHASE 3: REQUEST PROCESSING THROUGH ORCHESTRATOR
    // =====================================================================
    
    /**
     * Use the Orchestrator to process the request
     * 
     * The Orchestrator handles:
     * 1. Triaging the request with AI
     * 2. Routing tasks to appropriate agents
     * 3. Assembling results into a unified response
     * 4. Updating conversation history
     */
    
    // Initialize the orchestrator
    $orchestrator = new Orchestrator($container);
    
    // Process the request
    $response = $orchestrator->handleRequest($input);
    
    // Add debug information if in debug mode
    if ($debugMode) {
        $response['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'input' => $input,
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ];
    }
    
    // Return the response as JSON
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (BotMojoException $e) {
    // Handle BotMojo-specific exceptions
    $errorResponse = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 400
    ];
    
    header('Content-Type: application/json');
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    exit;
    
    // Remove markdown code block markers if present
    if (strpos($responseText, '```json') !== false) {
        $responseText = preg_replace('/```json\s*|\s*```/', '', $responseText);
    } elseif (strpos($responseText, '```') !== false) {
        $responseText = preg_replace('/```\s*|\s*```/', '', $responseText);
    }
    
    $responseText = trim($responseText);
    
    // Parse the structured JSON response from AI
    $triageResponse = json_decode($responseText, true);
    
    // Validate JSON parsing
    if (!$triageResponse) {
        throw new Exception('Invalid JSON response from AI model: ' . $responseText);
    }
    
    // Extract the user-facing response message
    $suggestedResponse = $triageResponse['suggested_response'] ?? 'I understand your request and will process it.';
    
    // =============================================================================
    // COMPONENT PROCESSING - AGENT-BASED TASK EXECUTION
    // =============================================================================
    
    // Initialize container for assembled components
    $assembledComponents = [];
    
    // Generate unique identifier for the new entity
    $entityId = generateUUID();
    $entityType = $triageResponse['target_entity']['type'] ?? 'general';
    $entityName = $triageResponse['target_entity']['alias'] ?? 'Untitled Entity';
    
    // Process each component task assigned by the triage AI
    if (isset($triageResponse['component_tasks']) && is_array($triageResponse['component_tasks'])) {
        foreach ($triageResponse['component_tasks'] as $task) {
            // Extract task details
            $targetAgent = $task['target_agent'] ?? '';
            $componentName = $task['component_name'] ?? '';
            $componentData = $task['component_data'] ?? [];
            
            // Route to appropriate specialized agent
            switch ($targetAgent) {
                case 'FinanceAgent':
                    $agent = new FinanceAgent($toolManager);
                    break;
                case 'MemoryAgent':
                    $agent = new MemoryAgent($toolManager);
                    break;
                case 'PlannerAgent':
                    $agent = new PlannerAgent($toolManager);
                    break;
                case 'HealthAgent':
                    $agent = new HealthAgent($toolManager);
                    break;
                case 'SpiritualAgent':
                    $agent = new SpiritualAgent($toolManager);
                    break;
                case 'SocialAgent':
                    $agent = new SocialAgent($toolManager);
                    break;
                case 'RelationshipAgent':
                    $agent = new RelationshipAgent($toolManager);
                    break;
                case 'LearningAgent':
                    $agent = new LearningAgent($toolManager);
                    break;
                case 'GeneralistAgent':
                    $agent = new GeneralistAgent($toolManager);
                    break;
                default:
                    // Fallback to GeneralistAgent for unknown agent types
                    $agent = new GeneralistAgent($toolManager);
                    $componentName = 'general_component';
                    break;
            }
            
            // Create component using the selected agent with full context
            if ($agent && method_exists($agent, 'createComponent')) {
                // Enhance component data with triage context for intelligent processing
                $enhancedComponentData = array_merge($componentData, [
                    'triage_summary' => $triageResponse['triage_summary'] ?? '',
                    'original_query' => $userQuery,
                    'conversation_id' => $conversationId,
                    'full_triage_response' => $triageResponse,
                    'entity_id' => $entityId,  // Pass the entity ID to the agent
                    'user_id' => $userId       // Pass the user ID to the agent
                ]);
                
                $assembledComponents[$componentName] = $agent->createComponent($enhancedComponentData);
            }
        }
    }
    
    // =============================================================================
    // ENTITY STORAGE - DATABASE PERSISTENCE
    // =============================================================================
    
    // Save entity to database if we have components or target entity information
    if (!empty($assembledComponents) || isset($triageResponse['target_entity'])) {
        // Create the comprehensive entity data structure
        $entityData = [
            'triage_summary' => $triageResponse['triage_summary'] ?? '',   // AI's understanding summary
            'original_query' => $userQuery,                                // User's original input
            'components' => $assembledComponents,                          // All agent-created components
            'created_at' => date('Y-m-d H:i:s'),                          // Creation timestamp
            'conversation_id' => $conversationId                          // Link to conversation
        ];
        
        // Persist entity to database
        $saved = $databaseTool->saveNewEntity(
            $entityId,
            $userId,
            $entityType,
            $entityName,
            json_encode($entityData)
        );
        
        // Log any database save failures
        if (!$saved) {
            error_log("Failed to save entity to database");
        }
    }
    
    // =====================================================================
    // PHASE 3: REQUEST PROCESSING THROUGH ORCHESTRATOR
    // =====================================================================
    
    /**
     * Use the Orchestrator to process the request
     * 
     * The Orchestrator handles:
     * 1. Triaging the request with AI
     * 2. Routing tasks to appropriate agents
     * 3. Assembling results into a unified response
     * 4. Updating conversation history
     */
    
    // Initialize the orchestrator
    $orchestrator = new Orchestrator($container);
    
    // Process the request
    $response = $orchestrator->handleRequest($input);
    
    // Add debug information if in debug mode
    if ($debugMode) {
        $response['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'input' => $input,
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ];
    }
    
    // Return the response as JSON
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (BotMojoException $e) {
    // Handle BotMojo-specific exceptions
    $errorResponse = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 400
    ];
    
    // Add context data in debug mode
    if ($debugMode) {
        $errorResponse['context'] = $e->getContext();
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    http_response_code(400);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Handle general exceptions
    $errorResponse = [
        'status' => 'error',
        'message' => 'Internal server error: ' . $e->getMessage(),
        'code' => 500
    ];
    
    // Add detailed error info in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorResponse['detail'] = $e->getMessage();
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    http_response_code(500);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}

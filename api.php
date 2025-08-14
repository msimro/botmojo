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
 * @author AI Personal Assistant Development Team
 * @version 1.1
 * @since 2025-08-07
 * @license MIT
 * 
 * @see config.php Configuration constants and utility functions
 * @see agents/ Specialized AI agent implementations
 * @see tools/ Core tool and service classes
 * @see index.php Frontend chat interface
 * @see dashboard.php Data visualization interface
 * 
 * =====================================================================
 */

// =====================================================================
// DEPENDENCY LOADING AND INITIALIZATION
// =====================================================================

/**
 * Load core configuration and initialize the application environment
 * This includes database constants, API keys, and utility functions
 */
require_once 'config.php';

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
    
    // Log raw input in debug mode for troubleshooting
    if (DEBUG_MODE) {
        error_log("🔍 API Input Received: " . $rawInput);
        error_log("🔍 Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("🔍 Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    }
    
    // Parse JSON input with error checking
    $input = json_decode($rawInput, true);
    
    // Validate JSON parsing was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(
            'Invalid JSON input received. Error: ' . json_last_error_msg() . 
            '. Raw input: ' . substr($rawInput, 0, 100) . '...'
        );
    }
    
    // Ensure input is an array/object and contains required fields
    if (!$input || !is_array($input) || !isset($input['query'])) {
        throw new Exception(
            'Invalid request format. Expected JSON object with "query" field. ' .
            'Received: ' . gettype($input) . 
            (is_array($input) ? ' with keys: ' . implode(', ', array_keys($input)) : '')
        );
    }
    
    // Extract and validate core parameters
    $userQuery = trim((string)$input['query']);
    $conversationId = $input['conversation_id'] ?? 'default_conversation';
    $userId = DEFAULT_USER_ID; // Single-user mode for now, multi-user ready
    
    // Debug mode can be overridden per request for testing
    $debugModeOverride = isset($input['debug_mode']) ? (bool)$input['debug_mode'] : null;
    $isDebugMode = ($debugModeOverride !== null) ? $debugModeOverride : DEBUG_MODE;
    
    // Validate query content
    if (empty($userQuery)) {
        throw new Exception('Query cannot be empty. Please provide a meaningful input.');
    }
    
    // Security: Prevent extremely long queries that might cause issues
    if (strlen($userQuery) > 2000) {
        throw new Exception('Query too long. Please limit input to 2000 characters.');
    }
    
    // Sanitize conversation ID to prevent path traversal attacks
    $conversationId = preg_replace('/[^a-zA-Z0-9_-]/', '', $conversationId);
    if (empty($conversationId)) {
        $conversationId = 'default_conversation';
    }
    
    // Log processed parameters in debug mode
    if ($isDebugMode) {
        error_log("✅ Validated Parameters:");
        error_log("   - User Query: " . substr($userQuery, 0, 100) . (strlen($userQuery) > 100 ? '...' : ''));
        error_log("   - Conversation ID: " . $conversationId);
        error_log("   - User ID: " . $userId);
        error_log("   - Debug Mode: " . ($isDebugMode ? 'enabled' : 'disabled'));
    }
    
    // =====================================================================
    // PHASE 2: CORE SYSTEM INITIALIZATION
    // =====================================================================
    
    /**
     * Initialize core system components and tools
     * 
     * TOOL ARCHITECTURE:
     * - PromptBuilder: Dynamic AI prompt assembly from templates
     * - ConversationCache: File-based conversation history management
     * - DatabaseTool: Entity storage and retrieval operations
     * - ToolManager: Centralized tool access and permission control
     * 
     * All tools are initialized once and reused throughout the request
     * lifecycle to optimize performance and maintain consistency.
     */
    
    // Initialize prompt management system for dynamic AI prompt generation
    $promptBuilder = new PromptBuilder(PROMPTS_DIR);
    if ($isDebugMode) {
        error_log("🔧 PromptBuilder initialized with directory: " . PROMPTS_DIR);
    }
    
    // Initialize conversation context management
    $conversationCache = new ConversationCache(CACHE_DIR);
    if ($isDebugMode) {
        error_log("💬 ConversationCache initialized with directory: " . CACHE_DIR);
    }
    
    // Initialize database operations handler
    $databaseTool = new DatabaseTool();
    if ($isDebugMode) {
        error_log("🗄️ DatabaseTool initialized with connection to: " . DB_NAME);
    }
    
    // Initialize centralized tool management system
    $toolManager = new ToolManager();
    if ($isDebugMode) {
        error_log("🛠️ ToolManager initialized with agent permission system");
    }
    
    // =====================================================================
    // PHASE 3: AI-POWERED TRIAGE AND INTENT ANALYSIS
    // =====================================================================
    
    /**
     * Execute the triage-first analysis using Google Gemini AI
     * 
     * TRIAGE PROCESS:
     * 1. Retrieve conversation history for context
     * 2. Build dynamic prompt with agent definitions and output format
     * 3. Include user profile and conversation context
     * 4. Send to Gemini AI for intelligent analysis
     * 5. Parse structured JSON response with execution plan
     * 6. Validate and prepare for agent routing
     * 
     * The triage system is the core innovation that enables intelligent
     * request routing without hardcoded rules or complex routing logic.
     */
    
    // Retrieve conversation history to provide context for better understanding
    $conversationHistory = $conversationCache->getHistory($conversationId);
    $historyLength = is_array($conversationHistory) ? count($conversationHistory) : 0;
    
    if ($isDebugMode) {
        error_log("📚 Retrieved conversation history: {$historyLength} messages");
    }
    
    // Build the comprehensive triage prompt using template system
    $triagePrompt = $promptBuilder->build('base/triage_agent_base.txt', [
        'agent_definitions' => 'components/agent_definitions.txt',
        'output_format' => 'formats/triage_json_output.txt', 
        'user_profile' => 'components/user_profile.txt'
    ]);
    
    if ($isDebugMode) {
        error_log("📝 Triage prompt built with " . strlen($triagePrompt) . " characters");
    }
    
    // Inject conversation history into the prompt template
    $triagePrompt = $promptBuilder->replacePlaceholders($triagePrompt, [
        'conversation_history' => $conversationHistory
    ]);
    
    // Create the complete prompt with user's current input
    $fullPrompt = $triagePrompt . "\n\nUser Input: " . $userQuery;
    
    if ($isDebugMode) {
        error_log("🎯 Sending triage request to Gemini AI (" . strlen($fullPrompt) . " chars)");
    }
    
    // Execute AI analysis through Gemini API
    $geminiResponse = callGeminiAPI($fullPrompt);
    
    // Validate that we received a valid response from Gemini
    if (!$geminiResponse || !isset($geminiResponse['text'])) {
        throw new Exception(
            'Failed to get valid response from AI model. ' .
            'Response: ' . json_encode($geminiResponse)
        );
    }
    
    if ($isDebugMode) {
        error_log("🤖 Gemini AI response received (" . strlen($geminiResponse['text']) . " chars)");
    }
    if (!$geminiResponse || !isset($geminiResponse['text'])) {
        throw new Exception('Failed to get response from AI model.');
    }
    
    // Clean the response text to handle markdown code blocks
    $responseText = trim($geminiResponse['text']);
    
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
    
    // =============================================================================
    // CONVERSATION HISTORY - CACHE UPDATE
    // =============================================================================
    
    // Save this conversation turn to history cache
    $conversationCache->appendToHistory($conversationId, $userQuery, $suggestedResponse);
    
    // =============================================================================
    // RESPONSE GENERATION - SUCCESS OUTPUT
    // =============================================================================
    
    // Include tool response handler
    require_once 'tools/ToolResponseHandler.php';
    
    // Enhance the response with all tool data from all components
    $enhancedResponse = enhanceResponseWithToolData($suggestedResponse, $assembledComponents, $userQuery);
    
    // Return successful response with enhanced message
    $responseData = [
        'success' => true,
        'response' => $enhancedResponse,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Include debug data if in development mode or explicitly requested
    if ($isDebugMode) {
        $responseData['triage_data'] = $triageResponse;
        $responseData['debug'] = [
            'original_response' => $suggestedResponse,
            'enhanced_response' => $enhancedResponse,
            'has_weather_component' => !empty($assembledComponents['general_component']['tool_insights']['weather']),
            'components' => array_keys($assembledComponents),
            'execution_results' => $executionResults ?? [],
            'component_data' => $assembledComponents
        ];
    }
    
    echo json_encode($responseData);
    
} catch (Exception $e) {
    // =============================================================================
    // ERROR HANDLING - FAILURE RESPONSE
    // =============================================================================
    
    // Set HTTP error status code
    http_response_code(500);
    
    // Return structured error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log the error for debugging and monitoring
    error_log("API Error: " . $e->getMessage());
}

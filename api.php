<?php
/**
 * API Orchestrator - Main Entry Point for AI Personal Assistant
 * 
 * This file serves as the central orchestrator for the AI Personal Assistant.
 * It implements the triage-first, agent-based architecture by:
 * 1. Receiving user queries via HTTP POST
 * 2. Using AI to analyze and create execution plans
 * 3. Routing tasks to specialized agents
 * 4. Assembling components into unified entities
 * 5. Storing results in the database
 * 6. Managing conversation history
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */

// Load configuration and initialize autoloader
require_once 'config.php';

// =============================================================================
// HTTP HEADERS AND CORS SETUP
// =============================================================================
// Set appropriate headers for JSON API responses and CORS support
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight CORS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // =============================================================================
    // INPUT VALIDATION AND SETUP
    // =============================================================================
    
    // Parse incoming JSON request
    $rawInput = file_get_contents('php://input');
    
    // Debug logging if in debug mode
    if (DEBUG_MODE) {
        error_log("Raw input received: " . $rawInput);
    }
    
    $input = json_decode($rawInput, true);
    
    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    // Validate required input parameters
    if (!$input || !isset($input['query'])) {
        throw new Exception('Invalid input. Query is required. Received: ' . print_r($input, true));
    }
    
    // Extract and sanitize input parameters
    $userQuery = trim($input['query']);
    $conversationId = $input['conversation_id'] ?? 'default_conversation';
    $userId = DEFAULT_USER_ID;
    
    // Check if the request includes debug mode override
    $debugModeOverride = isset($input['debug_mode']) ? (bool)$input['debug_mode'] : null;
    $isDebugMode = ($debugModeOverride !== null) ? $debugModeOverride : DEBUG_MODE;
    
    // Ensure query is not empty
    if (empty($userQuery)) {
        throw new Exception('Query cannot be empty.');
    }
    
    // =============================================================================
    // TOOL INITIALIZATION
    // =============================================================================
    
    // Initialize core tools for processing
    $promptBuilder = new PromptBuilder(PROMPTS_DIR);          // For dynamic prompt assembly
    $conversationCache = new ConversationCache(CACHE_DIR);    // For conversation history
    $databaseTool = new DatabaseTool();                       // For entity storage
    
    // Initialize Tool Manager for centralized tool access
    $toolManager = new ToolManager();
    
    // =============================================================================
    // TRIAGE PHASE - AI ANALYSIS AND PLANNING
    // =============================================================================
    
    // Retrieve conversation history for context
    $conversationHistory = $conversationCache->getHistory($conversationId);
    
    // Build the triage prompt using dynamic template assembly
    $triagePrompt = $promptBuilder->build('base/triage_agent_base.txt', [
        'agent_definitions' => 'components/agent_definitions.txt',
        'output_format' => 'formats/triage_json_output.txt',
        'user_profile' => 'components/user_profile.txt'
    ]);
    
    // Replace conversation history placeholder with actual content
    $triagePrompt = $promptBuilder->replacePlaceholders($triagePrompt, [
        'conversation_history' => $conversationHistory
    ]);
    
    // Append the current user query to the prompt
    $fullPrompt = $triagePrompt . "\n\nUser Input: " . $userQuery;
    
    // Send prompt to Gemini AI for triage analysis
    $geminiResponse = callGeminiAPI($fullPrompt);
    
    // Validate AI response
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

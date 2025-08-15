<?php
declare(strict_types=1);

/**
 * BotMojo API Orchestrator - Triage-First Agent System Entry Point
 */

// Bootstrap error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// Convert errors to exceptions
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
require_once 'config.php';

// Import required classes
use BotMojo\Core\ServiceContainer;
use BotMojo\Core\Orchestrator;
use BotMojo\Exceptions\BotMojoException;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\GeminiTool;
use BotMojo\Tools\HistoryTool;
use BotMojo\Tools\PromptBuilder;

// Clear all output buffers at start
while (ob_get_level()) ob_end_clean();

// Start fresh output buffer
ob_start();

// Set headers if not already sent
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *'); // TODO: Restrict in production
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Cache-Control: no-cache, must-revalidate');
}

// Initialize logger
$logger = new \BotMojo\Services\LoggerService('api');

try {
    // Log incoming request
    $logger->info('Incoming API request', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);

    /**
     * Handle preflight CORS requests
     */
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }

    // Capture raw input stream
    $rawInput = file_get_contents('php://input');
    
    // Log input for debugging
    $logger->debug("Raw API Input", ['input' => $rawInput]);
    
    // Parse JSON input
    $input = json_decode($rawInput, true);
    
    // Validate input format
    if (!is_array($input)) {
        throw new BotMojoException(
            'Invalid or empty request body',
            400,
            null,
            ['input' => $rawInput]
        );
    }
    
    // Validate required parameters
    if (!isset($input['query']) || empty(trim($input['query']))) {
        throw new BotMojoException(
            'Missing required parameter: query',
            400,
            null,
            ['input' => $input]
        );
    }
    
    // Set default conversation ID
    if (!isset($input['conversation_id']) || empty($input['conversation_id'])) {
        $input['conversation_id'] = 'default_conversation';
    }
    
    // Get debug mode setting
    $debugMode = $_ENV['DEBUG_MODE'] ?? false;
    if (isset($input['debug_mode']) && is_bool($input['debug_mode'])) {
        $debugMode = $input['debug_mode'];
    }
    
    // Validate query length
    if (strlen($input['query']) > 2000) {
        throw new BotMojoException(
            'Query too long. Please limit input to 2000 characters.',
            400,
            null,
            ['query_length' => strlen($input['query'])]
        );
    }
    
    // Sanitize conversation ID
    $input['conversation_id'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['conversation_id']);
    if (empty($input['conversation_id'])) {
        $input['conversation_id'] = 'default_conversation';
    }
    
    // Initialize service container
    $container = new ServiceContainer();
    
    // Initialize and register core tools
    $dbTool = new DatabaseTool([
        'host' => $_ENV['DB_HOST'] ?? 'db',
        'database' => $_ENV['DB_NAME'] ?? 'db',
        'user' => $_ENV['DB_USER'] ?? 'db',
        'password' => $_ENV['DB_PASS'] ?? 'db'
    ]);
    
    $geminiTool = new GeminiTool(['api_key' => $_ENV['API_KEY'] ?? '']);
    $historyTool = new HistoryTool($dbTool);
    $promptBuilder = new PromptBuilder(['prompt_dir' => __DIR__ . '/prompts']);
    
    // Register tools
    $container->set('tool.database', $dbTool);
    $container->set('tool.gemini', $geminiTool);
    $container->set('tool.history', $historyTool);
    $container->set('tool.prompt_builder', $promptBuilder);
    
    // Register agents
    $container->set('agent.memory', function() use ($container) {
        return new \BotMojo\Agents\MemoryAgent(
            $container->get('tool.database'),
            $container->get('tool.gemini')
        );
    });
    
    // Initialize orchestrator
    $orchestrator = new Orchestrator($container);
    
    // Process the request
    $response = $orchestrator->handleRequest($input);
    
    // Add debug information if needed
    if ($debugMode) {
        $response['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'input' => $input,
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ];
    }
    
    // Return success response
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (BotMojoException $e) {
    // Handle BotMojo-specific exceptions
    $errorResponse = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 400,
        'success' => false
    ];
    
    if ($debugMode) {
        $errorResponse['debug'] = [
            'context' => $e->getContext(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
    
    http_response_code(400);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    // Handle all other errors
    $errorResponse = [
        'status' => 'error',
        'message' => 'Internal Server Error',
        'code' => 500,
        'success' => false
    ];
    
    if ($debugMode) {
        $errorResponse['debug'] = [
            'message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'file' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->getFile()),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
    }
    
    // Log the error
    $logger->error('Uncaught exception', [
        'exception_class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}

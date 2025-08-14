<?php

/**
 * BotMojo AI Personal Assistant - OOP Implementation
 *
 * This is the fully refactored OOP version of the BotMojo API.
 * It preserves all the functionality of the original implementation
 * while providing a more maintainable, extensible architecture.
 *
 * @category   API
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

// Autoloading (will use Composer's autoloader when available)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Manual autoloading as fallback
    spl_autoload_register(function ($class) {
        // Convert namespace to file path
        $prefix = 'BotMojo\\';
        $baseDir = __DIR__ . '/src/';
        
        // Check if the class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Convert namespace separator to directory separator
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Load configuration
require_once __DIR__ . '/config.php';

// Import core classes
use BotMojo\Core\ServiceContainer;
use BotMojo\Core\Orchestrator;
use BotMojo\Agents\MemoryAgent;
use BotMojo\Tools\GeminiTool;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\HistoryTool;
use BotMojo\Exceptions\BotMojoException;

// Set response content type and CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // TODO: Restrict in production
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, must-revalidate');

// Handle preflight CORS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Main request processing
try {
    // Get request data
    $rawInput = file_get_contents('php://input');
    
    // Log raw input in debug mode
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("🔍 API Input Received: " . $rawInput);
        error_log("🔍 Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("🔍 Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    }
    
    // Parse JSON input
    $input = json_decode($rawInput, true);
    
    // Validate JSON parsing
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new BotMojoException(
            'Invalid JSON input received: ' . json_last_error_msg(),
            ['raw_input' => substr($rawInput, 0, 100) . '...']
        );
    }
    
    // Ensure input is valid
    if (!$input || !is_array($input) || !isset($input['query'])) {
        throw new BotMojoException(
            'Invalid request format. Expected JSON object with "query" field.',
            ['received' => gettype($input)]
        );
    }
    
    // Extract and validate core parameters
    $input['query'] = trim((string)$input['query']);
    $input['conversation_id'] = $input['conversation_id'] ?? 'default_conversation';
    $input['user_id'] = $input['user_id'] ?? ($GLOBALS['config']['DEFAULT_USER_ID'] ?? 1);
    
    // Sanitize conversation ID to prevent path traversal
    $input['conversation_id'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['conversation_id']);
    if (empty($input['conversation_id'])) {
        $input['conversation_id'] = 'default_conversation';
    }
    
    // Validate query content
    if (empty($input['query'])) {
        throw new BotMojoException('Query cannot be empty.');
    }
    
    // Security: Prevent extremely long queries
    if (strlen($input['query']) > 2000) {
        throw new BotMojoException('Query too long. Please limit input to 2000 characters.');
    }
    
    // Create and configure the service container
    $container = new ServiceContainer();
    
    // Register configuration
    $container->set('config', fn() => $GLOBALS['config'] ?? []);
    
    // Get database credentials from config or environment
    $dbHost = $GLOBALS['config']['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $dbUser = $GLOBALS['config']['DB_USER'] ?? getenv('DB_USER') ?? 'root';
    $dbPassword = $GLOBALS['config']['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
    $dbName = $GLOBALS['config']['DB_NAME'] ?? getenv('DB_NAME') ?? 'botmojo';
    
    // Get Gemini API key from config or environment
    $geminiApiKey = $GLOBALS['config']['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?? '';
    
    // Register tools
    $container->set('tool.gemini', function() use ($geminiApiKey) {
        $tool = new GeminiTool();
        $tool->initialize(['api_key' => $geminiApiKey]);
        return $tool;
    });
    
    $container->set('tool.database', function() use ($dbHost, $dbUser, $dbPassword, $dbName) {
        $tool = new DatabaseTool();
        $tool->initialize([
            'host' => $dbHost,
            'user' => $dbUser,
            'password' => $dbPassword,
            'database' => $dbName
        ]);
        return $tool;
    });
    
    $container->set('tool.history', function() {
        $tool = new HistoryTool();
        $tool->initialize(['cache_dir' => __DIR__ . '/cache']);
        return $tool;
    });
    
    // Register PromptBuilder as a tool
    $container->set('tool.prompt_builder', function() {
        $builder = new \BotMojo\Tools\PromptBuilder();
        $builder->initialize([
            'prompt_dir' => __DIR__ . '/prompts'
        ]);
        return $builder;
    });
    
    // Register agents
    $container->set('agent.memory', function($c) {
        return new MemoryAgent(
            $c->get('tool.database'),
            $c->get('tool.gemini')
        );
    });
    
    // Add more agents as they are implemented
    // Example:
    // $container->set('agent.planner', function($c) {
    //     return new \BotMojo\Agents\PlannerAgent(
    //         $c->get('tool.database'),
    //         $c->get('tool.gemini')
    //     );
    // });
    
    // Create orchestrator and handle request
    $orchestrator = new Orchestrator($container);
    $response = $orchestrator->handleRequest($input);
    
} catch (BotMojoException $e) {
    // Handle BotMojo-specific exceptions
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'context' => $e->getContext(),
        'trace' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getTraceAsString() : null
    ];
    http_response_code(400);
} catch (\Exception $e) {
    // Handle general exceptions
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getTraceAsString() : null
    ];
    http_response_code(500);
}

// Send response
echo json_encode($response);

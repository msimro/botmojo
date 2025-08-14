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

// Define DEBUG_MODE if not already defined
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // Set to false in production
}

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
    
    // DDEV specific database credentials
    $dbHost = 'db';  // Standard DDEV database host
    $dbUser = 'db';  // Standard DDEV database user
    $dbPassword = 'db';  // Standard DDEV database password
    $dbName = 'db';  // Use the standard DDEV database name
    
    // Override with config values if present
    if (defined('DB_HOST')) {
        $dbHost = DB_HOST;
    }
    if (defined('DB_USER')) {
        $dbUser = DB_USER;
    }
    if (defined('DB_PASS')) {
        $dbPassword = DB_PASS;
    }
    if (defined('DB_NAME')) {
        $dbName = DB_NAME;
    }
    
    // Log database configuration in debug mode
    if (DEBUG_MODE) {
        error_log("🗄️ Database configuration:");
        error_log("   - Host: {$dbHost}");
        error_log("   - User: {$dbUser}");
        error_log("   - Password: " . (empty($dbPassword) ? "(empty)" : "(set)"));
        error_log("   - Database: {$dbName}");
    }
    
    // Get Gemini API key from all possible sources
    $geminiApiKey = null;
    $geminiModel = null;
    
    // Check defined constants first
    if (defined('GEMINI_API_KEY')) {
        $geminiApiKey = GEMINI_API_KEY;
    }
    // Check global config array
    elseif (isset($GLOBALS['config']['GEMINI_API_KEY'])) {
        $geminiApiKey = $GLOBALS['config']['GEMINI_API_KEY'];
    }
    // Check environment variables
    elseif (getenv('GEMINI_API_KEY')) {
        $geminiApiKey = getenv('GEMINI_API_KEY');
    }
    
    // Get the model in the same way
    if (defined('GEMINI_MODEL')) {
        $geminiModel = GEMINI_MODEL;
    }
    elseif (isset($GLOBALS['config']['GEMINI_MODEL'])) {
        $geminiModel = $GLOBALS['config']['GEMINI_MODEL'];
    }
    elseif (getenv('GEMINI_MODEL')) {
        $geminiModel = getenv('GEMINI_MODEL');
    }
    
    // Register tools
    $container->set('tool.gemini', function() use ($geminiApiKey, $geminiModel) {
        $tool = new GeminiTool();
        // Ensure we have a valid API key before initializing
        if (!$geminiApiKey) {
            // For development/testing, use a placeholder
            error_log("⚠️ WARNING: No Gemini API key found. Using placeholder for development.");
            $tool->initialize(['api_key' => 'placeholder-api-key-for-development']);
        } else {
            $config = ['api_key' => $geminiApiKey];
            // Add model if available
            if ($geminiModel) {
                $config['model'] = $geminiModel;
                error_log("🤖 Using Gemini model: {$geminiModel}");
            }
            $tool->initialize($config);
        }
        return $tool;
    });
    
    $container->set('tool.database', function() use ($dbHost, $dbUser, $dbPassword, $dbName) {
        $tool = new DatabaseTool();
        
        // Ensure we have all required database settings
        $config = [
            'host' => $dbHost ?: 'db',         // Fallback to standard DDEV host
            'user' => $dbUser ?: 'db',         // Fallback to standard DDEV user
            'password' => $dbPassword ?: 'db', // Fallback to standard DDEV password
            'database' => $dbName ?: 'db'      // Fallback to standard DDEV database
        ];
        
        if (DEBUG_MODE) {
            error_log("🔌 Initializing DatabaseTool with host={$config['host']}, user={$config['user']}, database={$config['database']}");
        }
        
        $tool->initialize($config);
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

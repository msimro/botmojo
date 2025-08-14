<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection using both PDO directly
 * and the DatabaseTool class.
 */

// Include configuration
require_once 'config.php';

// For manual autoloading
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

// Import classes
use BotMojo\Tools\DatabaseTool;
use BotMojo\Exceptions\BotMojoException;

// Set header for plain text output
header('Content-Type: text/plain');

echo "=================================================\n";
echo "DATABASE CONNECTION TEST\n";
echo "=================================================\n\n";

// Display configuration values
echo "Using the following database configuration:\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
echo "Password: " . (empty(DB_PASS) ? "(empty)" : "(set)") . "\n";
echo "Database: " . DB_NAME . "\n\n";

// Test 1: Direct PDO connection
echo "TEST 1: Direct PDO Connection\n";
echo "-------------------------------------------------\n";
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_NAME
    );
    
    echo "Connecting with DSN: " . $dsn . "\n";
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "SUCCESS: Direct PDO connection established.\n";
    
    // Try a simple query
    $stmt = $pdo->query("SELECT 'PDO Test' AS result");
    $result = $stmt->fetch();
    echo "Query result: " . $result['result'] . "\n\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Test 2: Using DatabaseTool
echo "TEST 2: DatabaseTool Connection\n";
echo "-------------------------------------------------\n";
try {
    $databaseTool = new DatabaseTool();
    echo "Initializing DatabaseTool with configuration...\n";
    
    $config = [
        'host' => DB_HOST,
        'user' => DB_USER,
        'password' => DB_PASS,
        'database' => DB_NAME
    ];
    
    echo "Configuration keys: " . implode(', ', array_keys($config)) . "\n";
    
    $databaseTool->initialize($config);
    echo "DatabaseTool initialized successfully.\n";
    
    // Try a simple query
    $result = $databaseTool->query("SELECT 'DatabaseTool Test' AS result");
    echo "Query result: " . $result[0]['result'] . "\n\n";
    
} catch (BotMojoException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Context: " . json_encode($e->getContext()) . "\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "=================================================\n";
echo "TEST COMPLETED\n";
echo "=================================================\n";

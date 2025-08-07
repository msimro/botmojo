<?php
// Direct API test with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API directly...\n\n";

// Simulate a POST request to api.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate JSON input
$testInput = json_encode([
    'query' => 'Hello test',
    'conversation_id' => 'test_conv_123'
]);

// Mock the php://input
function mockInput() {
    global $testInput;
    return $testInput;
}

// Temporarily replace file_get_contents for testing
if (!function_exists('file_get_contents_original')) {
    function file_get_contents_original($filename, $use_include_path = false, $context = null, $offset = 0, $length = null) {
        if ($filename === 'php://input') {
            global $testInput;
            return $testInput;
        }
        return call_user_func_array('file_get_contents', func_get_args());
    }
}

echo "Input data: $testInput\n\n";

// Now include and run the API
try {
    ob_start();
    include 'api.php';
    $output = ob_get_clean();
    echo "API Output:\n";
    echo $output;
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>

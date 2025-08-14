<?php
/**
 * Gemini List Models Test Script
 * 
 * This script lists available Gemini models.
 */

// Include configuration
require_once 'config.php';

// Set header for plain text output
header('Content-Type: text/plain');

echo "=================================================\n";
echo "GEMINI AVAILABLE MODELS\n";
echo "=================================================\n\n";

// Display configuration values
echo "Using API Key: " . substr(GEMINI_API_KEY, 0, 5) . "..." . substr(GEMINI_API_KEY, -5) . "\n\n";

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode(GEMINI_API_KEY);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Enable verbose output for debugging
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);

echo "HTTP Status Code: {$httpCode}\n";

if ($curlError) {
    echo "CURL Error: {$curlError}\n";
}

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['models']) && is_array($data['models'])) {
        echo "Available Models:\n";
        echo "----------------\n";
        
        foreach ($data['models'] as $model) {
            echo "Name: " . $model['name'] . "\n";
            echo "Display Name: " . $model['displayName'] . "\n";
            
            if (isset($model['supportedGenerationMethods']) && is_array($model['supportedGenerationMethods'])) {
                echo "Supported Methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
            }
            
            echo "----------------\n";
        }
    } else {
        echo "No models found in the response.\n";
    }
} else {
    echo "\nAPI call failed with status code {$httpCode}.\n";
    echo "Response: " . $response . "\n";
}

echo "\n=================================================\n";
echo "TEST COMPLETED\n";
echo "=================================================\n";

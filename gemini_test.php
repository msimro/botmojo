<?php
/**
 * Gemini API Test Script
 * 
 * This script tests the Gemini API connection directly without any abstractions.
 */

// Include configuration
require_once 'config.php';

// Set header for plain text output
header('Content-Type: text/plain');

echo "=================================================\n";
echo "GEMINI API CONNECTION TEST\n";
echo "=================================================\n\n";

// Display configuration values
echo "Using the following API configuration:\n";
echo "API Key: " . substr(GEMINI_API_KEY, 0, 5) . "..." . substr(GEMINI_API_KEY, -5) . "\n";
echo "Model: " . GEMINI_MODEL . "\n";
echo "API URL: " . GEMINI_API_URL . "\n\n";

echo "TEST: Direct API Call\n";
echo "-------------------------------------------------\n";

$prompt = "Hello, tell me a short joke please.";

echo "Sending prompt: {$prompt}\n\n";

// Build the payload
$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'topP' => 0.8,
        'topK' => 40,
        'maxOutputTokens' => 1024,
    ]
]);

// Make the API call - note we need to use the full model name with "models/" prefix
$fullModelName = 'models/' . GEMINI_MODEL;
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/{$fullModelName}:generateContent";
$ch = curl_init($apiUrl . '?key=' . urlencode(GEMINI_API_KEY));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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

echo "\nVerbose CURL Log:\n";
echo $verboseLog;

echo "\nRaw Response:\n";
echo $response;

if ($httpCode === 200) {
    echo "\n\nParsed Response:\n";
    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "Generated Content: " . $data['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "Could not find expected 'text' field in response.\n";
        echo "Response structure: " . json_encode($data, JSON_PRETTY_PRINT);
    }
} else {
    echo "\nAPI call failed with status code {$httpCode}.\n";
}

echo "\n=================================================\n";
echo "TEST COMPLETED\n";
echo "=================================================\n";

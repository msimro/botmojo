<?php
// Simple Gemini API test
require_once 'config.php';

echo "Testing Gemini API connection...\n";
echo "API Key: " . substr(GEMINI_API_KEY, 0, 10) . "...\n";
echo "API URL: " . GEMINI_API_URL . "\n\n";

$response = callGeminiAPI("Hello, please respond with just 'API test successful'");

if ($response && isset($response['text'])) {
    echo "✅ API Response: " . $response['text'] . "\n";
} else {
    echo "❌ API call failed\n";
    echo "Response: " . print_r($response, true) . "\n";
}
?>

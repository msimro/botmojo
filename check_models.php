<?php
// Check available Gemini models
require_once __DIR__ . '/vendor/autoload.php';

$apiKey = getenv('GEMINI_API_KEY') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : null);
if (!$apiKey) {
    die("No API key found. Set GEMINI_API_KEY environment variable or constant.\n");
}

$client = new GuzzleHttp\Client([
    'base_uri' => 'https://generativelanguage.googleapis.com/v1/',
    'timeout' => 30
]);

try {
    $response = $client->get('models?key=' . urlencode($apiKey));
    $data = json_decode($response->getBody(), true);
    
    echo "Available Gemini Models:\n";
    echo "====================\n\n";
    
    foreach ($data['models'] ?? [] as $model) {
        echo "Name: {$model['name']}\n";
        echo "Display Name: {$model['displayName']}\n";
        echo "Description: {$model['description']}\n";
        echo "Supported Methods: " . implode(", ", $model['supportedMethods'] ?? []) . "\n";
        echo "Input Token Limit: {$model['inputTokenLimit']}\n";
        echo "Output Token Limit: {$model['outputTokenLimit']}\n";
        echo "Temperature Range: {$model['temperatureMin']} - {$model['temperatureMax']}\n";
        echo "--------------------------------------------------\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

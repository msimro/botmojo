<?php
/**
 * Configuration File for AI Personal Assistant - Core v1
 * 
 * This file contains all configuration constants, database settings,
 * API configurations, and utility functions used throughout the application.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================
// MySQL database connection settings for DDEV local environment
define('DB_HOST', 'db');                         // Database host (db for DDEV)
define('DB_USER', 'db');                         // Database username (db for DDEV)
define('DB_PASS', 'db');                         // Database password (db for DDEV)
define('DB_NAME', 'assistant_core_v1');          // Database name

// =============================================================================
// GOOGLE GEMINI API CONFIGURATION
// =============================================================================
// API settings for Google's Gemini AI model integration
define('GEMINI_API_KEY', 'AIzaSyCQA9i6FdiGTvpw7EWVsp3vYJF-agcTp10');  // Replace with your actual API key
define('GEMINI_MODEL', 'gemini-1.5-flash');            // AI model version (fast response)
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// =============================================================================
// APPLICATION CONFIGURATION
// =============================================================================
// Core application settings and directory paths
define('DEFAULT_USER_ID', 'default_user_001');  // Default user identifier for single-user setup
define('CACHE_DIR', __DIR__ . '/cache');         // Conversation cache storage directory
define('PROMPTS_DIR', __DIR__ . '/prompts');     // AI prompt templates directory

// =============================================================================
// DEVELOPMENT & DEBUGGING SETTINGS
// =============================================================================
// Error reporting configuration (set to false in production)
define('DEBUG_MODE', true);  // Enable detailed error reporting and debug output

// Configure PHP error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);           // Report all PHP errors
    ini_set('display_errors', 1);     // Display errors in browser
}

// =============================================================================
// AUTOLOADER CONFIGURATION
// =============================================================================
/**
 * Automatic class loading for agents and tools
 * This autoloader searches for PHP class files in the agents/ and tools/ directories
 * and includes them automatically when a class is instantiated
 */
spl_autoload_register(function ($class_name) {
    // Define directories where classes can be found
    $directories = ['agents', 'tools'];
    
    // Search each directory for the requested class file
    foreach ($directories as $directory) {
        $file = __DIR__ . '/' . $directory . '/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;  // Stop searching once file is found and loaded
        }
    }
});

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Generate a RFC 4122 compliant UUID (Universally Unique Identifier)
 * Used for creating unique entity IDs in the database
 * 
 * @return string A properly formatted UUID string
 */
function generateUUID(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),  // 32 bits for "time_low"
        mt_rand(0, 0xffff),                       // 16 bits for "time_mid"
        mt_rand(0, 0x0fff) | 0x4000,            // 16 bits for "time_hi_and_version", version 4
        mt_rand(0, 0x3fff) | 0x8000,            // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff) // 48 bits for "node"
    );
}

/**
 * Make HTTP POST request to Google Gemini API
 * Handles the communication with Google's Generative AI service
 * 
 * @param string $prompt The text prompt to send to the AI model
 * @return array|null Returns array with 'text' key containing AI response, or null on failure
 */
function callGeminiAPI(string $prompt): ?array {
    // Prepare the request payload according to Gemini API specification
    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt  // The actual prompt text for the AI
                    ]
                ]
            ]
        ]
    ];
    
    // Set up HTTP headers for the API request
    $headers = [
        'Content-Type: application/json',           // JSON content type
        'x-goog-api-key: ' . GEMINI_API_KEY        // Google API authentication key
    ];
    
    // Initialize and configure cURL for HTTP request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL);              // API endpoint
    curl_setopt($ch, CURLOPT_POST, true);                       // POST method
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); // JSON payload
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);             // HTTP headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);             // Return response as string
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);            // Skip SSL verification (for local dev)
    
    // Execute the request and capture response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Handle HTTP errors
    if ($httpCode !== 200) {
        error_log("Gemini API Error: HTTP $httpCode - $response");
        return null;
    }
    
    // Parse JSON response and extract the generated text
    $decoded = json_decode($response, true);
    
    // Check if response has the expected structure and return the text
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        return ['text' => $decoded['candidates'][0]['content']['parts'][0]['text']];
    }
    
    return null;  // Return null if response format is unexpected
}

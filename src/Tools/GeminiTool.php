<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Tools
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Tools;

use BotMojo\Core\AbstractTool;
use BotMojo\Exceptions\BotMojoException;
use Exception;

/**
 * Gemini Tool
 *
 * Provides access to Google's Gemini AI API for generating content,
 * processing requests, and performing AI operations.
 */
class GeminiTool extends AbstractTool
{
    /**
     * API endpoint for Gemini
     *
     * @var string
     */
    private const API_ENDPOINT_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';
    
    /**
     * Default model to use if none specified
     * 
     * @var string
     */
    private const DEFAULT_MODEL = 'gemini-2.5-flash-lite';
    
    /**
     * Fallback models in order of preference
     * 
     * @var array<string>
     */
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash-lite',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
        'gemini-2.0-flash-lite'
    ];
    
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['api_key'];
    
    /**
     * Validate the configuration
     *
     * Ensure that all required configuration parameters are present.
     *
     * @throws BotMojoException If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                // Special handling for development environments
                if (defined('DEBUG_MODE') && DEBUG_MODE && $key === 'api_key') {
                    // In debug mode, we'll allow a placeholder for development
                    if ($this->config[$key] === 'placeholder-api-key-for-development') {
                        error_log("⚠️ Using placeholder Gemini API key. Content generation will be simulated.");
                        // Continue with validation
                        continue;
                    }
                }
                
                throw new BotMojoException(
                    "Missing required configuration: {$key}",
                    ['tool' => 'GeminiTool']
                );
            }
        }
    }
    
    /**
     * Generate content using Gemini AI
     *
     * @param string $prompt The prompt to send to Gemini
     *
     * @throws BotMojoException If the API request fails
     * @return string The generated content
     */
    public function generateContent(string $prompt): string
    {
        $apiKey = $this->getConfig('api_key');
        
        // Development fallback for testing without an API key
        if ($apiKey === 'placeholder-api-key-for-development') {
            return $this->generateDevelopmentResponse($prompt);
        }
        
        // Get the configured model, or use default
        $model = $this->getConfig('model', self::DEFAULT_MODEL);
        
        // First try with the configured model
        try {
            return $this->callGeminiAPI($prompt, $apiKey, $model);
        } catch (BotMojoException $e) {
            // Check if it's a server error (like 503) or model not found error (404)
            if (strpos($e->getMessage(), 'HTTP code 5') !== false || 
                strpos($e->getMessage(), 'HTTP code 404') !== false) {
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("⚠️ Primary model {$model} failed. Trying fallback models...");
                }
                
                // Try fallback models if the primary model failed
                foreach (self::FALLBACK_MODELS as $fallbackModel) {
                    // Skip if it's the same as the one we just tried
                    if ($fallbackModel === $model) {
                        continue;
                    }
                    
                    try {
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            error_log("🔄 Trying fallback model: {$fallbackModel}");
                        }
                        return $this->callGeminiAPI($prompt, $apiKey, $fallbackModel);
                    } catch (BotMojoException $fallbackError) {
                        // Continue to the next fallback model
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            error_log("❌ Fallback model {$fallbackModel} also failed: " . $fallbackError->getMessage());
                        }
                    }
                }
            }
            
            // If we reach here, all models failed or it was a non-server error
            throw $e;
        }
    }
    
    /**
     * Make the actual API call to Gemini
     *
     * @param string $prompt The prompt to send
     * @param string $apiKey The API key to use
     * @param string $model  The model to use
     *
     * @throws BotMojoException If the API call fails
     * @return string The generated content
     */
    private function callGeminiAPI(string $prompt, string $apiKey, string $model): string
    {
        // Ensure model name has the required 'models/' prefix
        if (strpos($model, 'models/') !== 0) {
            $model = 'models/' . $model;
        }
        
        // Build the complete API URL with the model
        $modelName = ltrim($model, 'models/');
        $apiUrl = self::API_ENDPOINT_BASE . $modelName . ':generateContent';
        $url = $apiUrl . '?key=' . urlencode($apiKey);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("🔗 Using Gemini model: " . $modelName);
            error_log("📝 Prompt length: " . strlen($prompt) . " characters");
        }
        
        // Build standard payload with safety settings
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
        
        try {
            // Initialize cURL session
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Handle connection errors
            if ($curlError) {
                throw new BotMojoException(
                    "CURL error: " . $curlError,
                    ['url' => $apiUrl]
                );
            }
            
            // Handle HTTP errors
            if ($httpCode !== 200) {
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("❌ Gemini API error. Response: " . $response);
                }
                
                throw new BotMojoException(
                    "Gemini API error: HTTP code {$httpCode}",
                    ['response' => $response, 'model' => $modelName]
                );
            }
            
            // Parse and extract response
            $data = json_decode($response, true);
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("✅ Gemini response received (" . strlen($text) . " characters)");
                }
                
                return $text;
            }
            
            // Handle unexpected response format
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("❓ Unexpected Gemini API response format: " . $response);
            }
            
            throw new BotMojoException(
                "Unexpected Gemini API response format",
                ['response' => $response, 'model' => $modelName]
            );
            
        } catch (Exception $e) {
            // Pass through BotMojoExceptions
            if ($e instanceof BotMojoException) {
                throw $e;
            }
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("❌ Gemini API exception: " . $e->getMessage());
            }
            
            throw new BotMojoException(
                "Failed to generate content: " . $e->getMessage(),
                ['model' => $modelName],
                0,
                $e
            );
        }
    }
    
    /**
     * Generate a development response for testing without an API key
     *
     * @param string $prompt The user prompt
     *
     * @return string A simulated response
     */
    private function generateDevelopmentResponse(string $prompt): string
    {
        // For triage requests, return a simple JSON plan
        if (strpos($prompt, 'triage') !== false) {
            return json_encode([
                'triage_summary' => 'Development mode - simulated triage',
                'suggested_response' => 'I\'m running in development mode without a Gemini API key. This is a simulated response.',
                'tasks' => [
                    [
                        'agent' => 'memory',
                        'data' => [
                            'operation' => 'retrieve',
                            'search' => substr($prompt, -100)
                        ]
                    ]
                ]
            ], JSON_PRETTY_PRINT);
        }
        
        // For other requests, return a simple text response
        return "I'm running in development mode without a valid Gemini API key. " .
               "To use BotMojo fully, please set up a valid API key in config.php or as an environment variable.";
    }
}

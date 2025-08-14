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
    private const DEFAULT_MODEL = 'gemini-pro';
    
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
        $model = $this->getConfig('model', self::DEFAULT_MODEL);
        
        // Development fallback for testing without an API key
        if ($apiKey === 'placeholder-api-key-for-development') {
            return $this->generateDevelopmentResponse($prompt);
        }
        
        // Build the complete API URL with the model
        $url = self::API_ENDPOINT_BASE . $model . ':generateContent?key=' . urlencode($apiKey);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("🔗 Gemini API URL (without key): " . self::API_ENDPOINT_BASE . $model . ':generateContent');
            error_log("📝 Prompt length: " . strlen($prompt) . " characters");
        }
        
        $payload = json_encode([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topP' => 0.8,
                'topK' => 40,
                'maxOutputTokens' => 2048,
            ]
        ]);
        
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new BotMojoException(
                    "Gemini API error: HTTP code {$httpCode}",
                    ['response' => $response, 'url' => self::API_ENDPOINT_BASE . $model . ':generateContent']
                );
            }
            
            $data = json_decode($response, true);
            
            // Extract text from the response
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
            
            throw new BotMojoException(
                "Unexpected Gemini API response format",
                ['response' => $response]
            );
            
        } catch (Exception $e) {
            throw new BotMojoException(
                "Failed to generate content: " . $e->getMessage(),
                ['prompt' => $prompt],
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
        if (strpos($prompt, 'create a JSON plan') !== false) {
            return json_encode([
                'tasks' => [
                    [
                        'agent' => 'MemoryAgent',
                        'data' => [
                            'operation' => 'retrieve',
                            'entity_type' => 'generic',
                            'search' => 'development mode'
                        ]
                    ]
                ],
                'response' => "I'm running in development mode without a Gemini API key. This is a simulated response to your query: \"" . substr($prompt, -100) . "\"",
                'intent' => 'information_retrieval',
                'timestamp' => time()
            ], JSON_PRETTY_PRINT);
        }
        
        // For other requests, return a simple text response
        return "I'm running in development mode without a Gemini API key. This is a simulated response to your prompt: \"" . substr($prompt, 0, 100) . "...\"";
    }
}

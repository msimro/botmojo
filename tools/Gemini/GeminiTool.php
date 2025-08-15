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
use BotMojo\Exceptions\ApiException;
use BotMojo\Exceptions\ConfigurationException;
use BotMojo\Core\LoggerService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
    private const API_ENDPOINT_BASE = 'https://generativelanguage.googleapis.com/v1beta/';

    /**
     * Token usage tracking
     *
     * @var array<string, int>
     */
    private array $tokenUsage = [
        'prompt' => 0,
        'completion' => 0,
        'total' => 0
    ];
    
    /**
     * Default model to use if none specified
     * 
     * @var string
     */
    private const DEFAULT_MODEL = 'gemini-2.5-flash-lite';
    
    /**
     * Available models and their endpoints
     * 
     * @var array<string, string>
     */
    private const MODELS = [
        'gemini-2.5-flash-lite' => 'gemini-2.5-flash-lite',
        'gemini-1.5-flash' => 'gemini-1.5-flash',
        'gemini-1.5-pro' => 'gemini-1.5-pro',
        'gemini-2.0-flash-lite' => 'gemini-2.0-flash-lite',
        'gemini-1.0-pro' => 'gemini-1.0-pro'
    ];

    /**
     * Model names without any prefix
     *
     * @var array<string>
     */
    private const MODEL_NAMES = [
        'gemini-2.5-flash-lite',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
        'gemini-2.0-flash-lite',
        'gemini-1.0-pro'
    ];
    
    /**
     * Default safety settings
     * 
     * @var array<string, string>
     */
    private const DEFAULT_SAFETY_SETTINGS = [
        'HARM_CATEGORY_HARASSMENT' => 'BLOCK_MEDIUM_AND_ABOVE',
        'HARM_CATEGORY_HATE_SPEECH' => 'BLOCK_MEDIUM_AND_ABOVE',
        'HARM_CATEGORY_SEXUALLY_EXPLICIT' => 'BLOCK_MEDIUM_AND_ABOVE',
        'HARM_CATEGORY_DANGEROUS_CONTENT' => 'BLOCK_MEDIUM_AND_ABOVE'
    ];
    
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['api_key'];
    
    /**
     * Logger service
     */
    private LoggerService $logger;
    
    /**
     * Initialize the tool with configuration
     *
     * @param array<string, mixed> $config Configuration parameters
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        // Initialize logger
        $this->logger = new LoggerService('GeminiTool');
        
        $this->validateConfig();
    }
    
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
        // Check for API key in configuration or environment constants
        if (!isset($this->config['api_key']) || empty($this->config['api_key'])) {
            // Check if the API key is defined as a constant in config.php
            if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
                $this->config['api_key'] = GEMINI_API_KEY;
                
                $this->logger->info('Using API key from GEMINI_API_KEY constant');
            }
        }
        
        // Now proceed with normal validation
        $missingKeys = [];
        
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                // Special handling for development environments
                if (defined('DEBUG_MODE') && DEBUG_MODE && $key === 'api_key') {
                    // In debug mode, we'll allow a placeholder for development
                    if ($this->config[$key] === 'placeholder-api-key-for-development') {
                        $this->logger->warning("Using placeholder Gemini API key. Content generation will be simulated.");
                        // Continue with validation
                        continue;
                    }
                }
                
                $missingKeys[] = $key;
            }
        }
        
        if (!empty($missingKeys)) {
            $message = "Missing required configuration: " . implode(', ', $missingKeys);
            $this->logger->error($message, ['tool' => 'GeminiTool', 'missing_keys' => $missingKeys]);
            
            throw new ConfigurationException(
                $message,
                500,
                null,
                ['missing_keys' => $missingKeys, 'tool' => 'GeminiTool']
            );
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
                // Try alternative model if gemini-pro fails
                if ($model === 'gemini-2.5-flash-lite') {
                    try {
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            error_log("🔄 Trying alternative model: gemini-1.5-pro");
                        }
                        return $this->callGeminiAPI($prompt, $apiKey, 'gemini-1.5-pro');
                    } catch (BotMojoException $fallbackError) {
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            error_log("❌ Alternative model also failed: " . $fallbackError->getMessage());
                            error_log("🔄 Trying final fallback model: gemini-1.0-pro");
                        }
                        // Try one last time with gemini-1.0-pro
                        try {
                            return $this->callGeminiAPI($prompt, $apiKey, 'gemini-1.0-pro');
                        } catch (BotMojoException $finalError) {
                            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                                error_log("❌ Final fallback model also failed: " . $finalError->getMessage());
                            }
                            throw $finalError;
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
    private function callGeminiAPI(string $prompt, string $apiKey, string $model, array $extraParts = []): string
    {
        // Validate the model name
        if (!in_array($model, self::MODEL_NAMES, true)) {
            throw new BotMojoException(
                'Invalid model specified',
                400,
                null,
                [
                    'model' => $model,
                    'available_models' => self::MODEL_NAMES
                ]
            );
        }
        
        // Build the complete API URL with the model
        $apiUrl = self::API_ENDPOINT_BASE . 'models/' . $model . ':generateContent';
        $url = $apiUrl . '?key=' . urlencode($apiKey);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("🔗 Using Gemini model: " . $model);
            error_log("📝 Prompt length: " . strlen($prompt) . " characters");
        }

        // Build the parts array
        $parts = [['text' => $prompt]];
        
        // Add any extra parts (e.g., images)
        if (!empty($extraParts)) {
            foreach ($extraParts as $type => $data) {
                if ($type === 'image') {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $data['mime_type'],
                            'data' => $data['data']
                        ]
                    ];
                }
            }
        }
        
            // Build standard payload with safety settings
        $generationConfig = [
            'temperature' => $this->config['temperature'] ?? 0.4,
            'topP' => $this->config['top_p'] ?? 0.8,
            'topK' => $this->config['top_k'] ?? 40,
            'maxOutputTokens' => $this->config['max_output_tokens'] ?? 1024,
        ];

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => $generationConfig,
            'safetySettings' => $this->buildSafetySettings()
        ];

        // Convert to JSON
        $payload = json_encode($payload);        try {
            // Use Guzzle HTTP client
            $client = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);
            
            // Execute the request
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => $payload
            ]);
            
            // Get response data
            $httpCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            
            // Handle HTTP errors
            if ($httpCode !== 200) {
                // Debug information is always useful here
                error_log("❌ Gemini API error. HTTP Code: " . $httpCode);
                error_log("❌ Response: " . $responseBody);
                error_log("❌ API Key used: " . substr($apiKey, 0, 5) . "..." . substr($apiKey, -5));
                error_log("❌ Model: " . $model);
                
                // Try to parse the error response
                $errorData = json_decode($responseBody, true);
                $errorMessage = "HTTP code {$httpCode}";
                
                if (is_array($errorData) && isset($errorData['error'])) {
                    if (isset($errorData['error']['message'])) {
                        $errorMessage = $errorData['error']['message'];
                    }
                    
                    if (isset($errorData['error']['status'])) {
                        $errorMessage .= " (Status: " . $errorData['error']['status'] . ")";
                    }
                }
                
                throw new BotMojoException(
                    "Gemini API error: " . $errorMessage,
                    $httpCode,
                    null,
                    [
                        'response' => $responseBody, 
                        'model' => $model,
                        'http_code' => $httpCode,
                        'api_key_valid' => !empty($apiKey)
                    ]
                );
            }
            
            // Parse and extract response
            $data = json_decode($responseBody, true);
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("✅ Gemini response received (" . strlen($text) . " characters)");
                }
                
                return $text;
            }
            
            // Handle unexpected response format
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("❓ Unexpected Gemini API response format: " . $responseBody);
            }
            
            throw new BotMojoException(
                "Unexpected Gemini API response format",
                500,
                null,
                ['response' => $responseBody, 'model' => $model]
            );
            
        } catch (RequestException $e) {
            // Handle Guzzle-specific exceptions
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("❌ Guzzle request error: " . $e->getMessage());
            }
            
            throw new BotMojoException(
                "Failed to connect to Gemini API: " . $e->getMessage(),
                500,
                $e,
                ['url' => $url, 'model' => $model]
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
                500,
                $e,
                ['model' => $model]
            );
        }
    }
    
    /**
     * Build safety settings array for API requests
     *
     * @return array<array<string, string>> Formatted safety settings
     */
    private function buildSafetySettings(): array
    {
        $settings = $this->config['safety_settings'] ?? self::DEFAULT_SAFETY_SETTINGS;
        $safetySettings = [];

        foreach ($settings as $category => $threshold) {
            $safetySettings[] = [
                'category' => $category,
                'threshold' => $threshold
            ];
        }

        return $safetySettings;
    }

    /**
     * Generate content from an image
     *
     * @param string $prompt    The text prompt
     * @param string $imagePath The path to the image file
     * @throws BotMojoException If the image file is invalid or API call fails
     * @return string The generated content
     */
    public function generateFromImage(string $prompt, string $imagePath): string
    {
        if (!file_exists($imagePath)) {
            throw new BotMojoException(
                'Image file not found',
                400,
                null,
                ['path' => $imagePath]
            );
        }

        $mimeType = mime_content_type($imagePath);
        if (!str_starts_with($mimeType, 'image/')) {
            throw new BotMojoException(
                'Invalid file type. Only images are supported.',
                400,
                null,
                ['mime_type' => $mimeType]
            );
        }

        // Read and encode image
        $imageData = base64_encode(file_get_contents($imagePath));

        // Get API key and make call
        $apiKey = $this->getConfig('api_key');

        if ($apiKey === 'placeholder-api-key-for-development') {
            return $this->generateDevelopmentResponse($prompt . ' [Image analysis request]');
        }

        try {
            return $this->callGeminiAPI(
                $prompt,
                $apiKey,
                'gemini-pro-vision',
                [
                    'image' => [
                        'mime_type' => $mimeType,
                        'data' => $imageData
                    ]
                ]
            );
        } catch (Exception $e) {
            throw new BotMojoException(
                'Failed to generate content from image',
                500,
                $e,
                [
                    'image_path' => $imagePath,
                    'mime_type' => $mimeType
                ]
            );
        }
    }

    /**
     * Stream generated content
     *
     * @param string   $prompt   The text prompt
     * @param callable $callback Function to call with each chunk of text
     * @throws BotMojoException If streaming fails
     */
    public function streamGeneration(string $prompt, callable $callback): void
    {
        $apiKey = $this->getConfig('api_key');

        if ($apiKey === 'placeholder-api-key-for-development') {
            $callback($this->generateDevelopmentResponse($prompt));
            return;
        }

        $model = $this->getConfig('model', self::DEFAULT_MODEL);
        
        // Ensure we have a valid model name
        if (!isset(self::MODELS[$model])) {
            throw new BotMojoException(
                'Invalid model specified',
                400,
                null,
                [
                    'model' => $model,
                    'available_models' => array_keys(self::MODELS)
                ]
            );
        }

        // Get the API URL with the model path
        $url = self::API_ENDPOINT_BASE . 'models/' . $model . ':streamGenerateContent?key=' . urlencode($apiKey);

        $generationConfig = [
            'temperature' => $this->config['temperature'] ?? 0.4,
            'topP' => $this->config['top_p'] ?? 0.8,
            'topK' => $this->config['top_k'] ?? 40,
            'maxOutputTokens' => $this->config['max_output_tokens'] ?? 1024,
        ];

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => $generationConfig,
            'safetySettings' => $this->buildSafetySettings()
        ];

        try {
            $client = new Client([
                'timeout' => 0,  // No timeout for streaming
                'connect_timeout' => 10
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream'
                ],
                'json' => $payload,
                'stream' => true
            ]);

            $buffer = '';
            foreach ($response->getBody() as $chunk) {
                $buffer .= $chunk;
                
                if (($pos = strrpos($buffer, "\n")) !== false) {
                    $complete = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    foreach (explode("\n", $complete) as $line) {
                        if (trim($line) === '') {
                            continue;
                        }
                        
                        $data = json_decode($line, true);
                        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                            $callback($data['candidates'][0]['content']['parts'][0]['text']);
                        }
                    }
                }
            }

            // Process any remaining data
            if (trim($buffer) !== '') {
                $data = json_decode($buffer, true);
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $callback($data['candidates'][0]['content']['parts'][0]['text']);
                }
            }

        } catch (Exception $e) {
            throw new BotMojoException(
                'Streaming generation failed',
                500,
                $e,
                [
                    'model' => $model,
                    'error' => $e->getMessage()
                ]
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

    /**
     * Get token usage statistics
     *
     * @return array<string, int> Token usage stats
     */
    public function getTokenUsage(): array
    {
        return $this->tokenUsage;
    }

    /**
     * Reset token usage statistics
     */
    public function resetTokenUsage(): void
    {
        $this->tokenUsage = [
            'prompt' => 0,
            'completion' => 0,
            'total' => 0
        ];
    }
}

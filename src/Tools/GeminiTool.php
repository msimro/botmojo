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
    private const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
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
        $url = self::API_ENDPOINT . '?key=' . urlencode($apiKey);
        
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
                    ['response' => $response]
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
}

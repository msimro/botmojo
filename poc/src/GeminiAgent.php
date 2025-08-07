<?php

class GeminiAgent
{
    private string $apiKey;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=';

    public function __construct(string $apiKey)
    {
        if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
            throw new Exception("Gemini API key is not set in config.php");
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Sends a prompt to the Gemini API and returns the text response.
     */
    public function generateText(string $prompt): string
    {
        $payload = json_encode([
            'contents' => [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]);

        $ch = curl_init(self::API_URL . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "API Call Failed: " . $error;
        }

        $result = json_decode($response, true);

        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No content received from API.';
    }
}
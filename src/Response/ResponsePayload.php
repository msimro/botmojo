<?php

declare(strict_types=1);

namespace BotMojo\Response;

class ResponsePayload extends Response
{
    private array $components = [];
    private ?string $conversationId = null;
    private array $debug = [];

    public function addComponent(string $type, array $data): void
    {
        $this->components[] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    public function setConversationId(string $id): void
    {
        $this->conversationId = $id;
    }

    public function addDebugInfo(string $key, mixed $value): void
    {
        $this->debug[$key] = $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'components' => $this->components,
            'conversation_id' => $this->conversationId,
            'debug' => $this->debug
        ]);
    }

    public function send(): void
    {
        // Set appropriate headers
        header('Content-Type: application/json');
        header('X-Conversation-ID: ' . ($this->conversationId ?? 'none'));
        
        // Set HTTP status code
        http_response_code($this->statusCode);
        
        // Send JSON response
        echo json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}

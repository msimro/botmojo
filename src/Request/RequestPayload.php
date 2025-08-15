<?php

declare(strict_types=1);

namespace BotMojo\Request;

use BotMojo\Request\Exception\ValidationException;

class RequestPayload
{
    private string $intent = '';
    private array $context = [];
    private array $payload = [];
    private ?string $userId = null;
    private ?string $sessionId = null;
    private array $metadata = [];
    private RequestValidator $validator;

    public function __construct()
    {
        $this->validator = new RequestValidator();
    }

    /**
     * Load data from the request
     *
     * @throws ValidationException
     */
    public function load(): void
    {
        // Get raw input
        $input = file_get_contents('php://input');
        $jsonData = json_decode($input, true) ?? [];
        
        // Merge with POST data
        $data = array_merge($_POST, $jsonData);
        
        // Validate the data
        $this->validator->validate($data);
        
        // Set the properties
        $this->intent = $data['intent'];
        $this->context = $data['context'] ?? [];
        $this->payload = $data['payload'];
        $this->userId = $data['user_id'] ?? null;
        $this->sessionId = $data['session_id'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withIntent(string $intent): self
    {
        $clone = clone $this;
        $clone->intent = $intent;
        return $clone;
    }

    public function withContext(array $context): self
    {
        $clone = clone $this;
        $clone->context = $context;
        return $clone;
    }

    public function withPayload(array $payload): self
    {
        $clone = clone $this;
        $clone->payload = $payload;
        return $clone;
    }

    public function withUserId(?string $userId): self
    {
        $clone = clone $this;
        $clone->userId = $userId;
        return $clone;
    }

    public function withSessionId(?string $sessionId): self
    {
        $clone = clone $this;
        $clone->sessionId = $sessionId;
        return $clone;
    }

    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = $metadata;
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'context' => $this->context,
            'payload' => $this->payload,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'metadata' => $this->metadata,
        ];
    }
}

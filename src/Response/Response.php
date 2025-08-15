<?php

declare(strict_types=1);

namespace BotMojo\Response;

abstract class Response implements ResponseInterface
{
    protected array $data = [];
    protected int $statusCode = 200;
    protected array $metadata = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->statusCode,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }

    abstract public function send(): void;
}

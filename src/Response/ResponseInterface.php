<?php

declare(strict_types=1);

namespace BotMojo\Response;

interface ResponseInterface
{
    /**
     * Get response data
     */
    public function getData(): array;

    /**
     * Get response status code
     */
    public function getStatusCode(): int;

    /**
     * Get response metadata
     */
    public function getMetadata(): array;

    /**
     * Set response data
     */
    public function setData(array $data): void;

    /**
     * Set response status code
     */
    public function setStatusCode(int $code): void;

    /**
     * Set response metadata
     */
    public function setMetadata(array $metadata): void;

    /**
     * Convert response to array
     */
    public function toArray(): array;

    /**
     * Send the response
     */
    public function send(): void;
}

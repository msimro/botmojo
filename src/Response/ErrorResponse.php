<?php

declare(strict_types=1);

namespace BotMojo\Response;

class ErrorResponse extends Response
{
    private string $error;
    private ?string $errorCode;
    private array $details = [];

    public function __construct(string $error, ?string $errorCode = null, array $details = [])
    {
        $this->error = $error;
        $this->errorCode = $errorCode;
        $this->details = $details;
        $this->statusCode = 400;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'details' => $this->details
        ]);
    }

    public function send(): void
    {
        header('Content-Type: application/json');
        http_response_code($this->statusCode);
        
        echo json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}

<?php

declare(strict_types=1);

namespace BotMojo\Request;

use BotMojo\Request\Exception\ValidationException;

class RequestValidator
{
    /**
     * Validate request data
     *
     * @param array $data Request data to validate
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $this->validateIntent($data);
        $this->validateContext($data);
        $this->validatePayload($data);
    }

    /**
     * Validate request intent
     */
    private function validateIntent(array $data): void
    {
        if (!isset($data['intent']) || !is_string($data['intent'])) {
            throw new ValidationException('Intent must be a non-empty string');
        }

        if (empty(trim($data['intent']))) {
            throw new ValidationException('Intent cannot be empty');
        }
    }

    /**
     * Validate request context
     */
    private function validateContext(array $data): void
    {
        if (isset($data['context']) && !is_array($data['context'])) {
            throw new ValidationException('Context must be an array');
        }
    }

    /**
     * Validate request payload
     */
    private function validatePayload(array $data): void
    {
        if (!isset($data['payload']) || !is_array($data['payload'])) {
            throw new ValidationException('Payload must be an array');
        }
    }
}

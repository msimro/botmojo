<?php

declare(strict_types=1);

namespace BotMojo\Response;

class ResponseFactory
{
    /**
     * Create a success response
     */
    public static function success(array $data = [], array $metadata = []): ResponsePayload
    {
        $response = new ResponsePayload();
        $response->setStatusCode(200);
        $response->setData($data);
        $response->setMetadata($metadata);
        
        return $response;
    }

    /**
     * Create an error response
     */
    public static function error(
        string $message,
        ?string $errorCode = null,
        array $details = [],
        int $statusCode = 400
    ): ErrorResponse {
        $response = new ErrorResponse($message, $errorCode, $details);
        $response->setStatusCode($statusCode);
        
        return $response;
    }

    /**
     * Create a validation error response
     */
    public static function validationError(array $errors): ErrorResponse
    {
        return self::error(
            'Validation failed',
            'VALIDATION_ERROR',
            ['validation_errors' => $errors],
            422
        );
    }

    /**
     * Create an unauthorized error response
     */
    public static function unauthorized(string $message = 'Unauthorized'): ErrorResponse
    {
        return self::error($message, 'UNAUTHORIZED', [], 401);
    }

    /**
     * Create a not found error response
     */
    public static function notFound(string $message = 'Not found'): ErrorResponse
    {
        return self::error($message, 'NOT_FOUND', [], 404);
    }
}

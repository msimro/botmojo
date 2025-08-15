<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Exceptions
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Exceptions;

use Exception;

/**
 * API Exception
 *
 * Exception for API-related errors with HTTP status codes
 */
class ApiException extends BotMojoException
{
    /**
     * HTTP status code
     */
    private int $httpStatusCode;

    /**
     * Constructor
     *
     * @param string               $message        The exception message
     * @param int                  $httpStatusCode HTTP status code
     * @param array<string, mixed> $context        Additional context data
     * @param int                  $code           The exception code
     * @param Exception|null       $previous       The previous exception
     */
    public function __construct(
        string $message,
        int $httpStatusCode = 500,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * Get HTTP status code
     *
     * @return int The HTTP status code
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}

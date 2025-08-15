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
 * Validation Exception
 *
 * Exception for input validation errors
 */
class ValidationException extends BotMojoException
{
    /**
     * Validation errors
     *
     * @var array<string, string>
     */
    private array $validationErrors;

    /**
     * Constructor
     *
     * @param string                $message          The exception message
     * @param array<string, string> $validationErrors Array of field => error message
     * @param array<string, mixed>  $context          Additional context data
     * @param int                   $code             The exception code
     * @param Exception|null        $previous         The previous exception
     */
    public function __construct(
        string $message,
        array $validationErrors = [],
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get validation errors
     *
     * @return array<string, string> The validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}

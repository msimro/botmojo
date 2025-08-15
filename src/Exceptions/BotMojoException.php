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

/**
 * BotMojo Exception
 *
 * Base exception class for all BotMojo-specific exceptions
 */
class BotMojoException extends \Exception
{
    /**
     * Additional context data
     *
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string          $message  The error message
     * @param int            $code     The error code (optional)
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     * @param array          $context  Additional context data (optional)
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get context data
     *
     * @return array<string, mixed> The context data
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

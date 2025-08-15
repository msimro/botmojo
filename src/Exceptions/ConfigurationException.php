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
 * Configuration Exception
 *
 * Exception for configuration-related errors
 */
class ConfigurationException extends BotMojoException
{
    /**
     * Missing configuration keys
     *
     * @var array<string>
     */
    private array $missingKeys = [];

    /**
     * Constructor
     *
     * @param string          $message     The error message
     * @param int            $code        The error code (optional)
     * @param \Throwable|null $previous    The previous throwable used for exception chaining
     * @param array          $context     Additional context data (optional)
     * @param array<string>  $missingKeys Array of missing configuration keys (optional)
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        array $missingKeys = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->missingKeys = $missingKeys;
    }

    /**
     * Get missing configuration keys
     *
     * @return array<string> The missing keys
     */
    public function getMissingKeys(): array
    {
        return $this->missingKeys;
    }
}

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
    private array $missingKeys;

    /**
     * Constructor
     *
     * @param string               $message     The exception message
     * @param array<string>        $missingKeys Array of missing configuration keys
     * @param array<string, mixed> $context     Additional context data
     * @param int                  $code        The exception code
     * @param Exception|null       $previous    The previous exception
     */
    public function __construct(
        string $message,
        array $missingKeys = [],
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
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

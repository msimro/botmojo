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
 * BotMojo Exception
 *
 * Base exception class for all BotMojo-specific exceptions
 */
class BotMojoException extends Exception
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
     * @param string               $message  The exception message
     * @param array<string, mixed> $context  Additional context data
     * @param int                  $code     The exception code
     * @param Exception|null       $previous The previous exception
     */
    public function __construct(
        string $message,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
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

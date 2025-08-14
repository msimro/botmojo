<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Core
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use BotMojo\Exceptions\BotMojoException;
use Exception;

/**
 * Logger Service
 *
 * Provides structured logging functionality using Monolog
 * with context-aware error handling
 */
class LoggerService
{
    /**
     * The Monolog logger instance
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param string $name The logger name
     * @param string $logPath The path to store log files (defaults to project logs directory)
     */
    public function __construct(string $name = 'BotMojo', ?string $logPath = null)
    {
        $this->logger = new Logger($name);
        
        // Use project logs directory if no path specified
        if ($logPath === null) {
            $logPath = dirname(__DIR__, 2) . '/logs';
        }
        
        // Ensure logs directory exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        // Add rotating file handler for daily logs
        $fileHandler = new RotatingFileHandler(
            $logPath . '/botmojo.log',
            0, // Keep all log files
            Logger::DEBUG
        );
        
        // Custom formatter for better readability
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        $fileHandler->setFormatter($formatter);
        
        $this->logger->pushHandler($fileHandler);
        
        // Add console handler for development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $consoleHandler = new StreamHandler('php://stderr', Logger::DEBUG);
            $consoleHandler->setFormatter($formatter);
            $this->logger->pushHandler($consoleHandler);
        }
    }

    /**
     * Log an info message
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log a debug message
     *
     * @param string $message The log message
     * @param array<string, mixed> $context Additional context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log an exception with full context
     *
     * @param Exception $exception The exception to log
     * @param string $message Optional custom message
     */
    public function logException(Exception $exception, string $message = ''): void
    {
        $context = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        // Add BotMojo-specific context if available
        if ($exception instanceof BotMojoException) {
            $context['botmojo_context'] = $exception->getContext();
        }

        $logMessage = $message ?: 'Exception occurred: ' . $exception->getMessage();
        $this->logger->error($logMessage, $context);
    }

    /**
     * Get the underlying Monolog logger
     *
     * @return Logger The Monolog logger instance
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}

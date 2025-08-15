<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Services
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use BotMojo\Exceptions\BotMojoException;
use Throwable;

/**
 * Logger Service
 *
 * PSR-3 compliant logger service using Monolog with enhanced context handling
 * and structured logging capabilities.
 */
class LoggerService implements LoggerInterface
{
    /**
     * The Monolog logger instance
     */
    private Logger $logger;

    /**
     * Log levels mapping
     *
     * @var array<string, Level>
     */
    private array $levelMap = [
        LogLevel::EMERGENCY => Level::Emergency,
        LogLevel::ALERT     => Level::Alert,
        LogLevel::CRITICAL  => Level::Critical,
        LogLevel::ERROR     => Level::Error,
        LogLevel::WARNING   => Level::Warning,
        LogLevel::NOTICE    => Level::Notice,
        LogLevel::INFO      => Level::Info,
        LogLevel::DEBUG     => Level::Debug
    ];

    /**
     * Constructor
     *
     * @param string      $logPath  The path to store log files
     * @param string|int  $level    The minimum logging level (can be PSR-3 level string or Monolog Level)
     * @param string      $name     The logger name
     * @param bool        $console  Whether to enable console logging in development
     */
    public function __construct(
        string $logPath,
        string|int $level = LogLevel::DEBUG,
        string $name = 'BotMojo',
        bool $console = false
    ) {
        $this->logger = new Logger($name);
        
        // Ensure logs directory exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        // Convert PSR-3 level to Monolog Level if needed
        $logLevel = is_string($level) ? $this->levelMap[$level] : Level::from($level);
        
        // Add rotating file handler for daily logs
        $fileHandler = new RotatingFileHandler(
            $logPath . '/botmojo-' . date('Y-m-d') . '.log',
            30, // Keep last 30 days of logs
            $logLevel
        );
        
        // Custom formatter for better readability
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s.u'
        );
        $formatter->setMaxNormalizeDepth(5); // Prevent deep array serialization
        $formatter->setJsonPrettyPrint(true); // Pretty print JSON context
        
        $fileHandler->setFormatter($formatter);
        $this->logger->pushHandler($fileHandler);
        
        // Add console handler for development
        if ($console) {
            $consoleHandler = new StreamHandler('php://stderr', $logLevel);
            $consoleHandler->setFormatter($formatter);
            $this->logger->pushHandler($consoleHandler);
        }
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $this->normalizeContext($context));
    }

    /**
     * Action must be taken immediately.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $this->normalizeContext($context));
    }

    /**
     * Critical conditions.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $this->normalizeContext($context));
    }

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $this->normalizeContext($context));
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $this->normalizeContext($context));
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $this->normalizeContext($context));
    }

    /**
     * Interesting events.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $this->normalizeContext($context));
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $this->normalizeContext($context));
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->normalizeContext($context));
    }

    /**
     * Log an exception with full context
     *
     * @param Throwable $exception The exception to log
     * @param string    $message   Optional custom message
     * @param string    $level     Log level to use
     * @param array<string, mixed> $additional Additional context to include
     */
    public function logException(
        Throwable $exception,
        string $message = '',
        string $level = LogLevel::ERROR,
        array $additional = []
    ): void {
        $context = [
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        ];

        // Add BotMojo-specific context if available
        if ($exception instanceof BotMojoException) {
            $context['botmojo_context'] = $exception->getContext();
        }

        // Add any additional context
        if (!empty($additional)) {
            $context['additional'] = $additional;
        }

        $logMessage = $message ?: sprintf(
            'Exception occurred: [%s] %s',
            get_class($exception),
            $exception->getMessage()
        );

        $this->log($level, $logMessage, $context);
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

    /**
     * Normalize context array for consistent logging
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        // Add timestamp for all logs
        $context['timestamp'] = microtime(true);
        
        // Add process ID for tracking
        $context['pid'] = getmypid();
        
        // Add memory usage if in debug mode
        if ($this->logger->isHandling(Level::Debug)) {
            $context['memory'] = [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true)
            ];
        }
        
        return $context;
    }
}

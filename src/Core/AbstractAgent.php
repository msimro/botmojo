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

/**
 * Abstract Agent
 *
 * Base implementation for all specialized agents in the BotMojo system.
 * Provides common functionality and enforces the AgentInterface contract.
 */
abstract class AbstractAgent implements AgentInterface
{
    /**
     * Service container instance
     *
     * @var ServiceContainer
     */
    protected ServiceContainer $container;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected \Psr\Log\LoggerInterface $logger;

    /**
     * Agent configuration
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param ServiceContainer $container Service container for dependency injection
     * @param array<string, mixed> $config Agent configuration
     */
    public function __construct(ServiceContainer $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
        $this->logger = $container->get('logger');
    }

    /**
     * Process a task from the execution plan
     *
     * Default implementation that delegates to createComponent
     *
     * @param array<string, mixed> $taskData Data specific to this task from the execution plan
     *
     * @return array<string, mixed> The result of processing the task
     */
    public function process(array $taskData): array
    {
        $this->log('process_start', $taskData);
        $result = $this->createComponent($taskData);
        $this->log('process_complete', $result);
        return $result;
    }
    
    /**
     * Log agent activity
     *
     * Records the agent's activity for debugging and auditing purposes.
     *
     * @param string              $action The action being performed
     * @param array<string, mixed> $data   The data related to the action
     *
     * @return void
     */
    protected function log(string $action, array $data): void
    {
        $context = [
            'agent' => static::class,
            'action' => $action,
            'data' => $data
        ];
        $this->logger->info(sprintf('[Agent] %s: %s', static::class, $action), $context);
        // For now, it's a placeholder for future implementation
        
        // Example:
        // $this->logTool->log(static::class, $action, $data);
    }
}

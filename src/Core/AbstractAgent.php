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
        return $this->createComponent($taskData);
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
        // In a more complete implementation, this would use a logging tool
        // For now, it's a placeholder for future implementation
        
        // Example:
        // $this->logTool->log(static::class, $action, $data);
    }
}

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
 * Agent Interface
 *
 * Defines the contract for all specialized agents in the BotMojo system.
 * Agents are responsible for processing domain-specific tasks and generating
 * components for the final response.
 */
interface AgentInterface
{
    /**
     * Process a task from the execution plan
     *
     * Takes a task data array from the AI-generated plan and processes it
     * according to the agent's domain expertise.
     *
     * @param array<string, mixed> $taskData Data specific to this task from the execution plan
     *
     * @return array<string, mixed> The result of processing the task
     */
    public function process(array $taskData): array;
    
    /**
     * Create a domain-specific component for the response
     *
     * Generate a component that will be included in the assembled response.
     *
     * @param array<string, mixed> $data Data needed to create the component
     *
     * @return array<string, mixed> The component data structure
     */
    public function createComponent(array $data): array;
}

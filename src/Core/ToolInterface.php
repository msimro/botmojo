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
 * Tool Interface
 *
 * Defines the contract for all tools in the BotMojo system.
 * Tools provide specialized functionality that can be used by agents
 * or the orchestrator to perform specific tasks.
 */
interface ToolInterface
{
    /**
     * Initialize the tool with configuration
     *
     * Set up the tool with any required configuration parameters.
     *
     * @param array<string, mixed> $config Configuration parameters for the tool
     *
     * @return void
     */
    public function initialize(array $config): void;
}

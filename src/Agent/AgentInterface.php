<?php

declare(strict_types=1);

namespace BotMojo\Agent;

interface AgentInterface
{
    /**
     * Process a task with given data
     */
    public function process(array $data): array;

    /**
     * Get the agent's unique identifier
     */
    public function getId(): string;

    /**
     * Get the agent's capabilities
     */
    public function getCapabilities(): array;
}

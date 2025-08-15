<?php

declare(strict_types=1);

namespace BotMojo\Tool;

interface ToolInterface
{
    /**
     * Execute the tool with given parameters
     */
    public function execute(array $params): mixed;

    /**
     * Get the tool's unique identifier
     */
    public function getId(): string;

    /**
     * Get the tool's capabilities
     */
    public function getCapabilities(): array;

    /**
     * Initialize the tool with configuration
     */
    public function initialize(array $config): void;
}

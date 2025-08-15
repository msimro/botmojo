<?php

declare(strict_types=1);

namespace BotMojo\Orchestration;

use BotMojo\Config\Config;
use BotMojo\Request\RequestPayload;
use BotMojo\Orchestration\Exception\OrchestrationException;

class Orchestrator
{
    private Config $config;
    private array $agents = [];
    private array $tools = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->loadAgents();
        $this->loadTools();
    }

    /**
     * Process an incoming request through the appropriate agents
     */
    public function process(RequestPayload $request): array
    {
        $intent = $request->getIntent();
        $data = $request->getPayload();
        $context = $request->getContext();

        // Determine which agents to use based on intent
        $activeAgents = $this->selectAgents($intent);
        
        // Process through each agent
        $components = [];
        foreach ($activeAgents as $agent) {
            $components[] = $agent->process([
                'data' => $data,
                'context' => $context,
                'intent' => $intent
            ]);
        }

        // Combine components into final response
        return $this->assembleResponse($components);
    }

    private function loadAgents(): void
    {
        // Load agent configurations and instantiate agents
        $agentConfigs = $this->config->get('agents', []);
        foreach ($agentConfigs as $name => $config) {
            $class = $config['class'];
            $this->agents[$name] = new $class($this->tools, $config);
        }
    }

    private function loadTools(): void
    {
        // Load tool configurations and instantiate tools
        $toolConfigs = $this->config->get('tools', []);
        foreach ($toolConfigs as $name => $config) {
            $class = $config['class'];
            $this->tools[$name] = new $class($config);
        }
    }

    private function selectAgents(string $intent): array
    {
        // Logic to select appropriate agents based on intent
        // For now, return all agents
        return $this->agents;
    }

    private function assembleResponse(array $components): array
    {
        // Combine all component responses into a single response
        return [
            'components' => $components,
            'timestamp' => time(),
            'status' => 'success'
        ];
    }
}

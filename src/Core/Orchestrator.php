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

use Exception;

/**
 * Orchestrator
 *
 * Coordinates the entire request processing workflow:
 * - Triage the user request using AI
 * - Execute the plan with specialized agents
 * - Assemble the components into a unified response
 * - Update conversation history
 */
class Orchestrator
{
    /**
     * Service container for dependency injection
     *
     * @var ServiceContainer
     */
    private ServiceContainer $container;
    
    /**
     * Constructor
     *
     * @param ServiceContainer $container The service container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }
    
    /**
     * Handle an incoming user request
     *
     * Process a request through the complete workflow and return a response.
     *
     * @param array<string, mixed> $input The user request data
     *
     * @throws Exception If the request cannot be processed
     * @return array<string, mixed> The processed response
     */
    public function handleRequest(array $input): array
    {
        // 1. Triage Phase - Analyze user query and create execution plan
        $plan = $this->triageRequest($input);
        
        // 2. Routing & Processing Phase - Execute the plan with appropriate agents
        $results = $this->executeTaskPlan($plan);
        
        // 3. Assembly Phase - Combine components into unified response
        $response = $this->assembleResponse($results, $plan);
        
        // 4. Storage & History Phase
        $this->updateHistory($input, $response);
        
        return $response;
    }
    
    /**
     * Triage a user request using AI
     *
     * Use AI to analyze the request and generate an execution plan.
     *
     * @param array<string, mixed> $input The user request data
     *
     * @throws Exception If triage fails
     * @return array<string, mixed> The execution plan
     */
    private function triageRequest(array $input): array
    {
        $userQuery = $input['query'] ?? '';
        $userId = $input['user_id'] ?? null;
        
        // Get the Gemini tool for AI processing
        $geminiTool = $this->container->get('tool.gemini');
        
        // Build the triage prompt
        // In a more complete implementation, this would use PromptBuilder
        $prompt = "Based on the query: '{$userQuery}', create a JSON plan..."; // Your full prompt
        
        // Generate the plan
        $planJson = $geminiTool->generateContent($prompt);
        $plan = json_decode($planJson, true);
        
        // Validate the plan
        if (!$plan || !isset($plan['tasks'])) {
            throw new Exception("Failed to generate a valid plan from AI triage.");
        }
        
        return $plan;
    }
    
    /**
     * Execute the task plan with appropriate agents
     *
     * Route each task to the appropriate agent and collect results.
     *
     * @param array<string, mixed> $plan The execution plan
     *
     * @throws Exception If an agent is not found
     * @return array<string, mixed> Results from all agents
     */
    private function executeTaskPlan(array $plan): array
    {
        $results = [];
        
        foreach ($plan['tasks'] as $task) {
            $agentName = $task['agent'];
            $agentKey = 'agent.' . strtolower($agentName);
            
            // Check if the agent is registered
            if (!$this->container->has($agentKey)) {
                throw new Exception("Agent '{$agentName}' not registered.");
            }
            
            // Get the agent and process the task
            $agent = $this->container->get($agentKey);
            $results[$agentName] = $agent->process($task['data']);
        }
        
        return $results;
    }
    
    /**
     * Assemble the final response from agent results
     *
     * Combine all agent outputs into a unified response structure.
     *
     * @param array<string, mixed> $results Agent processing results
     * @param array<string, mixed> $plan    The execution plan
     *
     * @return array<string, mixed> The assembled response
     */
    private function assembleResponse(array $results, array $plan): array
    {
        return [
            'status' => 'success',
            'plan' => $plan,
            'components' => $results,
            'response' => $plan['response'] ?? 'I processed your request.',
            'timestamp' => time()
        ];
    }
    
    /**
     * Update conversation history
     *
     * Store the request and response in the conversation history.
     *
     * @param array<string, mixed> $request  The original request
     * @param array<string, mixed> $response The generated response
     *
     * @return void
     */
    private function updateHistory(array $request, array $response): void
    {
        if ($this->container->has('tool.history')) {
            $historyTool = $this->container->get('tool.history');
            $historyTool->addToHistory($request, $response);
        }
    }
}

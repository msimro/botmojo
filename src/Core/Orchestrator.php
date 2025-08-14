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
        
        // Get prompt builder if available
        $promptBuilder = null;
        if ($this->container->has('tool.prompt_builder')) {
            $promptBuilder = $this->container->get('tool.prompt_builder');
        }
        
        // Build the triage prompt
        $prompt = '';
        
        // Use prompt builder if available
        if ($promptBuilder) {
            try {
                $prompt = $promptBuilder->build('base/triage_agent_base.txt', [
                    'agents' => 'components/agent_definitions.txt',
                    'output_format' => 'formats/triage_json_output.txt'
                ]);
                
                $prompt = $promptBuilder->replacePlaceholders($prompt, [
                    'date' => date('Y-m-d'),
                    'time' => date('H:i:s')
                ]);
                
                $prompt .= "\n\nUser Input: " . $userQuery;
                
            } catch (Exception $e) {
                // Fallback to basic prompt if prompt builder fails
                error_log("⚠️ PromptBuilder failed: " . $e->getMessage() . ". Using fallback prompt.");
                $prompt = $this->getFallbackPrompt($userQuery);
            }
        } else {
            // Use fallback prompt if no prompt builder
            $prompt = $this->getFallbackPrompt($userQuery);
        }
        
        // Generate the plan
        $responseText = $geminiTool->generateContent($prompt);
        
        // Try to parse the response as JSON
        $plan = json_decode($responseText, true);
        
        // Handle text responses that might not be proper JSON
        if (!$plan || !is_array($plan)) {
            // Try to extract JSON from text (in case AI wrapped it in explanatory text)
            if (preg_match('/```(?:json)?(.*?)```/s', $responseText, $matches)) {
                $jsonContent = trim($matches[1]);
                $plan = json_decode($jsonContent, true);
            }
        }
        
        // Final validation of the plan
        if (!$plan || !is_array($plan)) {
            // Create a simple fallback plan
            error_log("⚠️ Failed to parse plan from AI response. Creating fallback plan.");
            error_log("AI Response: " . substr($responseText, 0, 500) . "...");
            
            $plan = [
                'tasks' => [
                    [
                        'agent' => 'memory',
                        'data' => [
                            'operation' => 'retrieve',
                            'search' => $userQuery
                        ]
                    ]
                ],
                'response' => "I processed your request: \"{$userQuery}\"",
                'intent' => 'information_retrieval'
            ];
        }
        
        // Ensure plan has all required components
        if (!isset($plan['tasks']) || !is_array($plan['tasks'])) {
            $plan['tasks'] = [
                [
                    'agent' => 'memory',
                    'data' => [
                        'operation' => 'retrieve',
                        'search' => $userQuery
                    ]
                ]
            ];
        }
        
        if (!isset($plan['response'])) {
            $plan['response'] = "I processed your request: \"{$userQuery}\"";
        }
        
        return $plan;
    }
    
    /**
     * Get a fallback prompt for triage
     *
     * Simple prompt template for when the prompt builder isn't available.
     *
     * @param string $userQuery The user's query
     *
     * @return string The fallback prompt
     */
    private function getFallbackPrompt(string $userQuery): string
    {
        return "You are BotMojo, an AI personal assistant. " .
               "Based on the query: '{$userQuery}', create a JSON plan that includes:\n" .
               "1. An array of tasks for specialized agents (memory, planner, finance, health, etc.)\n" .
               "2. A natural language response to the user\n" .
               "3. The detected intent of the query\n\n" .
               "Format your response as valid JSON with 'tasks', 'response', and 'intent' keys.\n" .
               "Each task should have an 'agent' field and a 'data' object with operation details.";
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
            
            // Normalize agent name for service container key
            // Convert MemoryAgent, memory_agent, or memory to agent.memory
            $normalizedName = strtolower($agentName);
            $normalizedName = preg_replace('/[^a-z0-9]/', '', $normalizedName);
            $normalizedName = preg_replace('/agent$/', '', $normalizedName);
            $agentKey = 'agent.' . $normalizedName;
            
            // Try to get the agent
            if (!$this->container->has($agentKey)) {
                // If not found, try fallback to GeneralistAgent
                error_log("⚠️ Agent '{$agentName}' not found. Using GeneralistAgent as fallback.");
                
                if ($this->container->has('agent.generalist')) {
                    $agent = $this->container->get('agent.generalist');
                } else {
                    // If no GeneralistAgent, use MemoryAgent
                    if ($this->container->has('agent.memory')) {
                        $agent = $this->container->get('agent.memory');
                    } else {
                        throw new Exception("Agent '{$agentName}' not registered and no fallback agent available.");
                    }
                }
            } else {
                $agent = $this->container->get($agentKey);
            }
            
            // Process the task
            try {
                $results[$agentName] = $agent->process($task['data'] ?? []);
            } catch (Exception $e) {
                error_log("🔴 Error processing task for agent '{$agentName}': " . $e->getMessage());
                $results[$agentName] = [
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
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

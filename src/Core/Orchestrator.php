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
use BotMojo\Exceptions\BotMojoException;

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
     * Last input received (for error reporting)
     *
     * @var array<string, mixed>|null
     */
    private ?array $lastInput = null;
    
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
     * Handle a user request
     *
     * Process the user request by analyzing intent, routing to agents,
     * and constructing a response.
     *
     * @param array<string, mixed> $input The user input data
     *
     * @throws BotMojoException If processing fails
     * @return array<string, mixed> The response data
     */
    public function handleRequest(array $input): array
    {
        try {
            // Store input for potential use in error handling
            $this->lastInput = $input;
            
            // First, triage the request to determine intent and create a plan
            $plan = $this->triageRequest($input);
            
            // Then execute the plan using appropriate agents
            $components = $this->executeTaskPlan($plan);
            
            // Finally, assemble the response
            $response = $this->assembleResponse($components, $plan);
            
            // Add timestamp
            $response['timestamp'] = time();
            
            return $response;
            
        } catch (BotMojoException $e) {
            // Re-throw BotMojo exceptions
            throw $e;
        } catch (\Exception $e) {
            // Wrap general exceptions in BotMojoException
            throw new BotMojoException(
                "Error processing request: " . $e->getMessage(),
                ['input' => $this->lastInput],
                0,
                $e
            );
        }
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
            // Use suggested_response from AI if available, otherwise fallback
            if (isset($plan['suggested_response'])) {
                $plan['response'] = $plan['suggested_response'];
            } else {
                $plan['response'] = "I processed your request: \"{$userQuery}\"";
            }
        }
        
        if (!isset($plan['intent'])) {
            $plan['intent'] = 'general_request';
        }
        
        return $plan;
    }
    
    /**
     * Get a fallback prompt for triage
     *
     * This is used when the prompt builder is not available.
     *
     * @param string $userQuery The user query
     *
     * @return string The fallback prompt
     */
    private function getFallbackPrompt(string $userQuery): string
    {
        return "You are an AI assistant that analyzes user requests and creates execution plans. " .
            "Create a JSON plan with 'tasks', 'response', and 'intent' fields for this query: " .
            "\"{$userQuery}\". Tasks should include 'agent' and 'data' fields.";
    }
    
    /**
     * Execute the task plan with appropriate agents
     *
     * @param array<string, mixed> $plan The execution plan
     *
     * @return array<string, mixed> The components created by agents
     */
    private function executeTaskPlan(array $plan): array
    {
        $results = [];
        $tasks = $plan['tasks'] ?? [];
        
        foreach ($tasks as $index => $task) {
            $agentName = $task['agent'] ?? '';
            $data = $task['data'] ?? [];
            
            // Skip if agent name is missing
            if (empty($agentName)) {
                continue;
            }
            
            // Build the full service name for the agent
            $serviceName = "agent.{$agentName}";
            
            // Skip if agent is not registered
            if (!$this->container->has($serviceName)) {
                error_log("⚠️ Agent '{$agentName}' not found in container.");
                continue;
            }
            
            try {
                // Get the agent from the container
                $agent = $this->container->get($serviceName);
                
                // Process the task with the agent
                $taskResults = $agent->process($data);
                
                // Store the results
                $results[$agentName] = $taskResults;
                
            } catch (Exception $e) {
                error_log("⚠️ Error executing task with agent '{$agentName}': " . $e->getMessage());
                // Continue with other tasks
            }
        }
        
        return $results;
    }
    
    /**
     * Assemble the final response from components
     *
     * @param array<string, mixed> $components The components created by agents
     * @param array<string, mixed> $plan       The execution plan
     *
     * @return array<string, mixed> The assembled response
     */
    private function assembleResponse(array $components, array $plan): array
    {
        return [
            'status' => 'success',
            'plan' => $plan,
            'components' => $components,
            'response' => $plan['response'] ?? 'I understand your request and have processed it.',
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

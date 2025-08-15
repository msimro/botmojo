<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Agents
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Agents;

use BotMojo\Core\AgentInterface;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\GeminiTool;
use BotMojo\Services\LoggerService;

/**
 * Planner Agent
 *
 * Handles planning, scheduling, and task management.
 */
class PlannerAgent implements AgentInterface
{
    private DatabaseTool $database;
    private GeminiTool $gemini;
    private LoggerService $logger;

    public function __construct(DatabaseTool $database, GeminiTool $gemini)
    {
        $this->database = $database;
        $this->gemini = $gemini;
        $this->logger = new LoggerService('PlannerAgent');
    }

    public function process(array $data): array
    {
        return $this->createComponent($data);
    }

    public function createComponent(array $data): array
    {
        $this->logger->info('Processing planning task', ['data' => $data]);

        $operation = $data['operation'] ?? 'plan';
        
        switch ($operation) {
            case 'plan':
                return $this->createPlan($data);
            case 'schedule':
                return $this->scheduleTask($data);
            default:
                return [
                    'type' => 'planning_component',
                    'operation' => $operation,
                    'message' => 'Planning operation processed',
                    'data' => $data
                ];
        }
    }

    private function createPlan(array $data): array
    {
        return [
            'type' => 'plan',
            'goal' => $data['goal'] ?? 'undefined',
            'steps' => $data['steps'] ?? [],
            'timeline' => $data['timeline'] ?? 'flexible',
            'created_at' => time()
        ];
    }

    private function scheduleTask(array $data): array
    {
        return [
            'type' => 'scheduled_task',
            'task' => $data['task'] ?? 'undefined',
            'scheduled_for' => $data['when'] ?? time(),
            'priority' => $data['priority'] ?? 'medium'
        ];
    }
}

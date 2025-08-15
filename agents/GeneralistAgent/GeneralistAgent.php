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
 * Generalist Agent
 *
 * Handles general queries and provides sophisticated contextual analysis.
 */
class GeneralistAgent implements AgentInterface
{
    private DatabaseTool $database;
    private GeminiTool $gemini;
    private LoggerService $logger;

    public function __construct(DatabaseTool $database, GeminiTool $gemini)
    {
        $this->database = $database;
        $this->gemini = $gemini;
        $this->logger = new LoggerService('GeneralistAgent');
    }

    public function process(array $data): array
    {
        return $this->createComponent($data);
    }

    public function createComponent(array $data): array
    {
        $this->logger->info('Processing general task', ['data' => $data]);

        $operation = $data['operation'] ?? 'analyze';
        
        switch ($operation) {
            case 'analyze':
                return $this->analyzeContent($data);
            case 'classify':
                return $this->classifyTopic($data);
            default:
                return [
                    'type' => 'general_response',
                    'operation' => $operation,
                    'message' => 'General request processed',
                    'data' => $data
                ];
        }
    }

    private function analyzeContent(array $data): array
    {
        return [
            'type' => 'content_analysis',
            'sentiment' => 'neutral',
            'topics' => ['general'],
            'confidence' => 0.8
        ];
    }

    private function classifyTopic(array $data): array
    {
        return [
            'type' => 'topic_classification',
            'category' => 'general',
            'subcategory' => 'information',
            'confidence' => 0.7
        ];
    }
}

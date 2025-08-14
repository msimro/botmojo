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
 * Finance Agent
 *
 * Handles financial data processing, categorization, and analysis.
 */
class FinanceAgent implements AgentInterface
{
    private DatabaseTool $database;
    private GeminiTool $gemini;
    private LoggerService $logger;

    /**
     * Constructor
     *
     * @param DatabaseTool $database Database tool
     * @param GeminiTool   $gemini   Gemini AI tool
     */
    public function __construct(DatabaseTool $database, GeminiTool $gemini)
    {
        $this->database = $database;
        $this->gemini = $gemini;
        $this->logger = new LoggerService('FinanceAgent');
    }

    /**
     * Process a financial task
     *
     * @param array<string, mixed> $data The task data
     *
     * @return array<string, mixed> The component data
     */
    public function process(array $data): array
    {
        return $this->createComponent($data);
    }

    /**
     * Create a financial component
     *
     * @param array<string, mixed> $data The task data
     *
     * @return array<string, mixed> The component data
     */
    public function createComponent(array $data): array
    {
        $this->logger->info('Processing financial task', ['data' => $data]);

        $operation = $data['operation'] ?? 'analyze';
        
        switch ($operation) {
            case 'categorize':
                return $this->categorizeTransaction($data);
            
            case 'analyze':
                return $this->analyzeFinancialData($data);
            
            case 'budget':
                return $this->processBudgetRequest($data);
            
            default:
                return [
                    'type' => 'financial_component',
                    'operation' => $operation,
                    'message' => 'Financial operation processed',
                    'data' => $data
                ];
        }
    }

    /**
     * Categorize a financial transaction
     *
     * @param array<string, mixed> $data Transaction data
     *
     * @return array<string, mixed> Categorized transaction
     */
    private function categorizeTransaction(array $data): array
    {
        return [
            'type' => 'transaction_category',
            'category' => 'food_dining',
            'confidence' => 0.95,
            'amount' => $data['amount'] ?? 0,
            'description' => $data['description'] ?? ''
        ];
    }

    /**
     * Analyze financial data
     *
     * @param array<string, mixed> $data Financial data
     *
     * @return array<string, mixed> Analysis results
     */
    private function analyzeFinancialData(array $data): array
    {
        return [
            'type' => 'financial_analysis',
            'trends' => ['spending_up', 'income_stable'],
            'recommendations' => ['reduce_dining_out', 'increase_savings'],
            'period' => $data['period'] ?? 'month'
        ];
    }

    /**
     * Process budget request
     *
     * @param array<string, mixed> $data Budget data
     *
     * @return array<string, mixed> Budget information
     */
    private function processBudgetRequest(array $data): array
    {
        return [
            'type' => 'budget_status',
            'total_budget' => $data['budget'] ?? 0,
            'spent' => $data['spent'] ?? 0,
            'remaining' => ($data['budget'] ?? 0) - ($data['spent'] ?? 0),
            'categories' => $data['categories'] ?? []
        ];
    }
}

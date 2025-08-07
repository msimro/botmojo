<?php
/**
 * FinanceAgent - Financial Data Component Creator
 * 
 * This agent specializes in processing financial information and creating
 * standardized financial components for entities. It handles expenses,
 * income, transfers, and other monetary transactions.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class FinanceAgent {
    
    /**
     * Create a financial component from provided data
     * Processes financial information and returns a standardized component structure
     * 
     * @param array $data Raw financial data from the triage system
     * @return array Standardized financial component with normalized fields
     */
    public function createComponent(array $data): array {
        return [
            // Core financial information
            'amount' => (float)($data['amount'] ?? 0),                    // Transaction amount (normalized to float)
            'currency' => $data['currency'] ?? 'USD',                     // Currency code (default: USD)
            'category' => $data['category'] ?? 'Uncategorized',           // Expense/income category
            'type' => $data['type'] ?? 'expense',                         // Transaction type: income, expense, transfer
            
            // Additional details
            'description' => $data['description'] ?? '',                  // Human-readable description
            'date' => $data['date'] ?? date('Y-m-d'),                    // Transaction date (default: today)
            'payment_method' => $data['payment_method'] ?? 'unknown'      // How payment was made (cash, card, etc.)
        ];
    }
}

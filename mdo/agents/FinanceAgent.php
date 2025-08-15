<?php
class FinanceAgent {
    public function execute(array $task) {
        $intent = $task['intent'];
        $params = $task['parameters'];
        
        if ($intent === 'CREATE') {
            $amount = $params['amount'] ?? 0;
            $description = $params['description'] ?? 'Unspecified';
            // Placeholder for a real DB call to INSERT into the 'transactions' table.
            return "FinanceAgent: Successfully logged expense of \${$amount} for '{$description}'.";
        }
        return "FinanceAgent: Can't handle intent '{$intent}'.";
    }
}
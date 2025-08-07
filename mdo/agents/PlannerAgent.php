<?php
class PlannerAgent {
    public function execute(array $task) {
        $intent = $task['intent'];
        $params = $task['parameters'];
        
        if ($intent === 'CREATE') {
            $title = $params['task_title'] ?? 'Unnamed Task';
            $dueDate = $params['due_date'] ?? 'today';
            // Placeholder for a real DB call to INSERT into the 'tasks' table.
            return "PlannerAgent: Successfully scheduled task '{$title}' for {$dueDate}.";
        }
        return "PlannerAgent: Can't handle intent '{$intent}'.";
    }
}
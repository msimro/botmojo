<?php
class GeneralistAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a general question';
        // Placeholder for calling another LLM or knowledge base.
        return "GeneralistAgent: Answering question about '{$queryPart}'.";
    }
}
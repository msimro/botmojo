<?php
class GeneralistAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a general question';
        $toolResults = $task['tool_results'] ?? [];
        
        $response = [
            "message" => "GeneralistAgent: Answering question about '{$queryPart}'."
        ];
        
        // Process tool results if available
        if (!empty($toolResults)) {
            foreach ($toolResults as $toolName => $result) {
                if ($toolName === 'weather' && isset($result['location'], $result['temperature'], $result['condition'])) {
                    $response['weather_insight'] = "The weather in {$result['location']} is {$result['condition']} with a temperature of {$result['temperature']}.";
                } elseif ($toolName === 'search' && isset($result['results'])) {
                    $response['search_insight'] = "Found information: " . json_encode($result['results']);
                } else {
                    $response['tool_data'][$toolName] = $result;
                }
            }
        }
        
        return $response;
    }
}
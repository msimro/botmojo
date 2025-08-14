<?php
class SearchTool {
    /**
     * Perform a search based on parameters
     * 
     * @param array $params Parameters from the triage agent
     * @return array Search results
     */
    public function execute(array $params): array {
        // In a real implementation, this would call an external API
        $query = $params['query'] ?? 'Unknown query';
        
        return [
            'tool' => 'search',
            'query' => $query,
            'results' => [
                [
                    'title' => 'Sample search result 1',
                    'snippet' => 'This is a sample search result for the query: ' . $query,
                    'url' => 'https://example.com/result1'
                ],
                [
                    'title' => 'Sample search result 2',
                    'snippet' => 'Another relevant search result for: ' . $query,
                    'url' => 'https://example.com/result2'
                ]
            ]
        ];
    }
}

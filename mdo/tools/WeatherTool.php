<?php
/**
 * WeatherTool - Sample tool implementation for MDO
 * 
 * This class demonstrates how tools can be implemented in the MDO system
 */
class WeatherTool {
    /**
     * Get weather information based on parameters
     * 
     * @param array $params Parameters from the triage agent
     * @return array Weather data
     */
    public function execute(array $params): array {
        // In a real implementation, this would call an external API
        $location = $params['location'] ?? 'Unknown location';
        
        return [
            'tool' => 'weather',
            'location' => $location,
            'temperature' => '72°F',
            'condition' => 'Sunny',
            'forecast' => 'Clear skies expected for the next 24 hours'
        ];
    }
}

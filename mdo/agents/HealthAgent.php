<?php
class HealthAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a health-related question';
        $toolResults = $task['tool_results'] ?? [];
        
        $response = [
            "message" => "HealthAgent: Processing health request about '{$queryPart}'."
        ];
        
        // Process tool results if available
        if (!empty($toolResults)) {
            // Process fitness data if available
            if (isset($toolResults['fitness'])) {
                $fitnessData = $toolResults['fitness'];
                $response['fitness_insight'] = "Here's your fitness data: " . json_encode($fitnessData);
                
                // Add specific analysis for fitness data
                if (isset($fitnessData['daily_steps'])) {
                    $averageSteps = array_sum($fitnessData['daily_steps']) / count($fitnessData['daily_steps']);
                    $response['steps_analysis'] = "Your average daily steps: " . round($averageSteps) . 
                                                 " steps. " . ($averageSteps >= 8000 ? 
                                                 "Great job staying active!" : 
                                                 "Consider aiming for 8,000+ steps daily for better health.");
                }
            }
            
            // Process weather data for health recommendations
            if (isset($toolResults['weather']) && isset($toolResults['weather']['temperature'], $toolResults['weather']['condition'])) {
                $weather = $toolResults['weather'];
                $healthTip = "";
                
                // Weather-based health recommendations
                if (strtolower($weather['condition']) === 'sunny' && $weather['temperature'] > 85) {
                    $healthTip = "It's hot outside! Remember to stay hydrated and use sun protection.";
                } elseif (strtolower($weather['condition']) === 'rainy') {
                    $healthTip = "Rainy day - good for indoor exercises. Consider yoga or home workouts.";
                } elseif ($weather['temperature'] < 40) {
                    $healthTip = "It's cold today - dress in layers for outdoor activities and warm up properly before exercising.";
                } else {
                    $healthTip = "Weather conditions are favorable for outdoor activities today.";
                }
                
                $response['weather_health_recommendation'] = $healthTip;
            }
            
            // Process database results for health records
            if (isset($toolResults['database'])) {
                $response['health_records'] = "Retrieved your health records: " . json_encode($toolResults['database']);
            }
            
            // Process search results for health information
            if (isset($toolResults['search']) && isset($toolResults['search']['results'])) {
                $response['health_information'] = "Health information found: " . json_encode($toolResults['search']['results']);
            }
            
            // Store any other tool results
            foreach ($toolResults as $toolName => $result) {
                if (!in_array($toolName, ['fitness', 'weather', 'database', 'search'])) {
                    $response['tool_data'][$toolName] = $result;
                }
            }
        }
        
        // Generate health component
        $response['health_component'] = [
            'health_status' => "Based on available data, here's your health assessment",
            'health_metrics' => $task['parameters']['metrics'] ?? [],
            'recommendations' => [
                "Stay hydrated",
                "Aim for 7-8 hours of sleep",
                "Consider regular exercise"
            ],
            'disclaimer' => "This information is not a substitute for professional medical advice."
        ];
        
        return $response;
    }
}

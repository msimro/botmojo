<?php
/**
 * PlannerAgent Tool Integration
 * 
 * This file adds the necessary functionality to the PlannerAgent
 * to use the CalendarTool, WeatherTool, and SearchTool.
 */

// Create a new method for PlannerAgent class (add to PlannerAgent.php)

/**
 * Enhance planning with contextual tools
 * Uses tools like Weather and Calendar to provide enhanced planning features
 * 
 * @param array $planningData Extracted planning data
 * @return array Enhanced planning data with tool insights
 */
private function enhancePlanningWithTools(array $planningData): array {
    // Initialize enhanced data
    $enhanced = $planningData;
    $enhanced['tool_insights'] = [];
    
    try {
        // Use CalendarTool for better date parsing
        if (!empty($planningData['original_text'])) {
            $calendarTool = new CalendarTool();
            $dateText = $planningData['original_text'];
            
            // Extract date text from context if possible
            if (preg_match('/(?:schedule|on|for|at|by)\s+([^.!?]+)/i', $dateText, $matches)) {
                $dateText = $matches[1];
            }
            
            $parsedDate = $calendarTool->parseNaturalDate($dateText);
            if ($parsedDate['success']) {
                // Use the parsed date if confidence is high
                if ($parsedDate['confidence'] > 70) {
                    $enhanced['start_date'] = $parsedDate['datetime']->format('Y-m-d H:i:s');
                    $enhanced['tool_insights']['calendar'] = [
                        'parsed_date' => $parsedDate['date_string'],
                        'parsed_time' => $parsedDate['time_string'],
                        'confidence' => $parsedDate['confidence'] . '%',
                        'description' => $parsedDate['relative_description']
                    ];
                }
            }
        }
        
        // Use WeatherTool for location-based planning
        if (!empty($enhanced['location'])) {
            $weatherTool = new WeatherTool();
            $location = $enhanced['location'];
            
            // If location contains an online meeting platform, skip weather check
            if (!preg_match('/\b(zoom|teams|skype|meet|webex)\b/i', $location)) {
                $weather = $weatherTool->getCurrentWeather($location);
                
                // If start_date is tomorrow or later, get forecast instead
                if (!empty($enhanced['start_date'])) {
                    $today = new DateTime('today');
                    $eventDate = new DateTime($enhanced['start_date']);
                    
                    if ($eventDate > $today) {
                        $daysDiff = $today->diff($eventDate)->days;
                        if ($daysDiff <= 5) { // Only if within forecast range
                            $forecast = $weatherTool->getForecast($location, $daysDiff);
                            if (!empty($forecast['days'])) {
                                $weatherInfo = $forecast['days'][0];
                                $enhanced['tool_insights']['weather'] = [
                                    'forecast' => $weatherInfo['primary_condition'],
                                    'temperature' => $weatherInfo['temperature_avg'] . '°C',
                                    'location' => $location,
                                    'type' => 'forecast'
                                ];
                            }
                        }
                    }
                }
                
                // Use current weather as fallback
                if (empty($enhanced['tool_insights']['weather']) && !empty($weather)) {
                    $enhanced['tool_insights']['weather'] = [
                        'current' => $weather['description'],
                        'temperature' => $weather['temperature'] . '°C',
                        'location' => $location,
                        'type' => 'current'
                    ];
                }
            }
        }
        
        // Use SearchTool for contextual information
        if (!empty($enhanced['title']) && strlen($enhanced['description']) < 100) {
            $searchTool = new SearchTool();
            $searchQuery = $enhanced['title'] . ' best practices';
            $results = $searchTool->search($searchQuery, 1);
            
            if (!empty($results['results'])) {
                $enhanced['tool_insights']['search'] = [
                    'query' => $searchQuery,
                    'info' => substr($results['results'][0]['snippet'], 0, 200),
                    'source' => $results['results'][0]['url']
                ];
                
                // Use search results to enhance description if needed
                if (empty($enhanced['description'])) {
                    $enhanced['description'] = 'This ' . $enhanced['type'] . ' might involve: ' . 
                        substr($results['results'][0]['snippet'], 0, 100) . '...';
                }
            }
        }
    } catch (Exception $e) {
        // Log errors but don't break the agent
        error_log('PlannerAgent tool integration error: ' . $e->getMessage());
    }
    
    return $enhanced;
}

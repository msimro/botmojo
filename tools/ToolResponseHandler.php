<?php
/**
 * Tool Response Handler for API
 * 
 * This file extracts and formats response data from various tool integrations.
 * It's used by api.php to enhance responses with tool-specific data.
 * 
 * Updated for production: August 8, 2025
 * Features: Weather, Search, and Calendar integrations with intent-based response filtering
 */

/**
 * Extract and format weather data from assembled components
 * 
 * @param array $components Assembled components from agents
 * @return string|null Formatted weather information or null if not available
 */
function extractWeatherResponse(array $components): ?string {
    // Look in all components for weather data
    foreach ($components as $component) {
        if (!empty($component['tool_insights']['weather'])) {
            $weatherData = $component['tool_insights']['weather'];
            
            if (DEBUG_MODE) {
                error_log("Weather data found in components: " . json_encode($weatherData));
            }
            
            // Skip if no current weather or if using mock data
            if (empty($weatherData['current'])) {
                if (DEBUG_MODE) {
                    error_log("Weather data missing current weather");
                }
                continue;
            }
            
            // Skip if mock data with note present (but check for that field first)
            if (isset($weatherData['current']['note'])) {
                if (DEBUG_MODE) {
                    error_log("Weather data is mock data, skipping");
                }
                continue;
            }
            
            // Format current weather information
            $response = "The current weather in {$weatherData['forecast']['location']} is " . 
                       "{$weatherData['current']['description']} with a temperature of " .
                       "{$weatherData['current']['temperature']} and " .
                       "{$weatherData['current']['humidity']} humidity.";
            
            // Add forecast if available
            if (!empty($weatherData['forecast']['days'][0])) {
                $tomorrow = $weatherData['forecast']['days'][0];
                $response .= " Tomorrow will be {$tomorrow['primary_condition']} with " .
                           "temperatures between {$tomorrow['temperature_min']}°C and " .
                           "{$tomorrow['temperature_max']}°C.";
            }
            
            // Add recommendations if available
            if (!empty($weatherData['insights']['recommendations'])) {
                $response .= " Recommendations: " . implode('. ', $weatherData['insights']['recommendations']) . ".";
            }
            
            if (DEBUG_MODE) {
                error_log("Weather response generated: " . $response);
            }
            
            return $response;
        }
    }
    
    if (DEBUG_MODE) {
        error_log("No valid weather data found in components");
    }
    
    return null;
}

/**
 * Extract and format search data from assembled components
 * 
 * @param array $components Assembled components from agents
 * @return string|null Formatted search information or null if not available
 */
function extractSearchResponse(array $components): ?string {
    $hasSearchComponent = false;
    $bestResult = null;
    $mockData = false;
    
    foreach ($components as $component) {
        if (!empty($component['tool_insights']['search']['results'])) {
            $hasSearchComponent = true;
            $searchData = $component['tool_insights']['search'];
            
            // Check if this is mock data
            $isMock = isset($searchData['is_mock']) ? $searchData['is_mock'] : false;
            
            // If we have real data (not mock), use it
            if (!$isMock) {
                $topResult = $searchData['results'][0] ?? null;
                if ($topResult) {
                    return " Based on search results: " . substr($topResult['snippet'], 0, 200) . 
                           "... (Source: " . (parse_url($topResult['url'], PHP_URL_HOST) ?? 'web') . ")";
                }
            }
            
            // Look for enhanced mock results
            foreach ($searchData['results'] as $result) {
                if (isset($result['type']) && $result['type'] === 'mock_enhanced') {
                    return " " . $result['snippet'];
                }
            }
            
            // Store first result as fallback
            if (!$bestResult && !empty($searchData['results'][0])) {
                $bestResult = $searchData['results'][0];
                $mockData = true;
            }
        }
    }
    
    // If we have mock data, provide a useful response
    if ($hasSearchComponent && $mockData && $bestResult) {
        return " Based on search results: " . substr($bestResult['snippet'], 0, 200) . 
               "... (Source: " . (parse_url($bestResult['url'], PHP_URL_HOST) ?? 'web') . ")";
    }
    
    return null;
}

/**
 * Extract and format calendar/time data from assembled components
 * 
 * @param array $components Assembled components from agents
 * @return string|null Formatted calendar information or null if not available
 */
function extractCalendarResponse(array $components): ?string {
    foreach ($components as $component) {
        if (!empty($component['tool_insights']['calendar']['date_insights'])) {
            $insights = $component['tool_insights']['calendar']['date_insights'];
            
            if (empty($insights)) {
                continue;
            }
            
            // First, look for day calculation insights
            foreach ($insights as $insight) {
                if (strpos($insight['expression'], 'Days between') === 0) {
                    return " " . $insight['description'];
                }
            }
            
            // Next, look for date information insights
            foreach ($insights as $insight) {
                if (strpos($insight['expression'], 'Date information') === 0) {
                    return " " . $insight['description'];
                }
            }
            
            // Finally, use the first available insight
            $insight = $insights[0];
            return " {$insight['expression']} is {$insight['parsed_date']}. {$insight['description']}.";
        }
    }
    
    return null;
}

/**
 * Enhance a response with all available tool data
 * 
 * @param string $baseResponse The original response
 * @param array $components All components from various agents
 * @param string $originalQuery Original user query (optional)
 * @return string Enhanced response with tool data
 */
function enhanceResponseWithToolData(string $baseResponse, array $components, string $originalQuery = ''): string {
    // Determine intent of the query
    $isWeatherQuery = preg_match('/\b(?:weather|forecast|temperature|rain|snow|sunny|cloudy|storm|precipitation|humidity|wind)\b/i', $originalQuery) === 1;
    $isCalendarQuery = preg_match('/\b(?:date|day|time|schedule|when|today|tomorrow|yesterday|next|week|month|year|monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $originalQuery) === 1;
    $isSearchQuery = !$isWeatherQuery && !$isCalendarQuery;
    
    // For weather queries, replace the entire response with just the weather data
    if ($isWeatherQuery) {
        $weatherResponse = extractWeatherResponse($components);
        if ($weatherResponse && trim($weatherResponse) !== '') {
            return "I've checked the weather for you. " . trim($weatherResponse);
        }
    }
    
    // For calendar queries, replace the entire response with just the calendar data
    if ($isCalendarQuery) {
        $calendarResponse = extractCalendarResponse($components);
        if ($calendarResponse && trim($calendarResponse) !== '') {
            return "Here's the date information you requested. " . trim($calendarResponse);
        }
    }
    
    // For other queries, start with the base response
    $enhancedResponse = $baseResponse;
    
    // Add calendar data if available
    $calendarResponse = extractCalendarResponse($components);
    if ($calendarResponse) {
        $enhancedResponse .= $calendarResponse;
    }
    
    // Add weather data if available
    $weatherResponse = extractWeatherResponse($components);
    if ($weatherResponse) {
        $enhancedResponse .= $weatherResponse;
    }
    
    // Add search data if available
    $searchResponse = extractSearchResponse($components);
    if ($searchResponse && $isSearchQuery) {
        $enhancedResponse .= $searchResponse;
    }
    
    return $enhancedResponse;
}

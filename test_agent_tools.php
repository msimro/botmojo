<?php
/**
 * Agent Tool Integration Test
 * 
 * This file demonstrates how the PlannerAgent should integrate with tools
 * like CalendarTool, WeatherTool, and SearchTool to provide better responses.
 */
require_once 'config.php';

// Create instances of tools
$weatherTool = new WeatherTool();
$calendarTool = new CalendarTool();
$searchTool = new SearchTool('google');

// Example query
$query = "Schedule a meeting for tomorrow at 3pm and check if it's going to rain";

echo "Testing PlannerAgent with tools integration...\n\n";

// Create a simple planner agent
$plannerAgent = new PlannerAgent();

// Test integration with weather
$location = "New York"; // Could be extracted from query
$weather = $weatherTool->getCurrentWeather($location);
$forecast = $weatherTool->getForecast($location, 1);

echo "Weather info that should be used by the PlannerAgent:\n";
echo "Current: " . $weather['description'] . ", " . $weather['temperature'] . "°C\n";
if (!empty($forecast['days'])) {
    echo "Tomorrow: " . $forecast['days'][0]['primary_condition'] . "\n";
}

// Test integration with calendar
$parsed = $calendarTool->parseNaturalDate('tomorrow at 3pm');
echo "\nCalendar parsing that should be used by the PlannerAgent:\n";
if ($parsed['success']) {
    echo "Parsed date: " . $parsed['date_string'] . " at " . $parsed['time_string'] . "\n";
}

// Test integration with search
$searchResults = $searchTool->search("meeting preparation best practices", 2);
echo "\nSearch results that could be used by PlannerAgent for context:\n";
foreach ($searchResults['results'] as $result) {
    echo "- " . $result['title'] . "\n";
    echo "  " . substr($result['snippet'], 0, 100) . "...\n";
}

echo "\nThese tools are working correctly, but are not being used directly in the agent code.\n";
echo "To properly integrate them, the PlannerAgent class should be modified to use these tools.\n";

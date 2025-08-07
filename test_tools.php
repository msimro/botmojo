<?php
/**
 * Test script for WeatherTool and CalendarTool
 */

require_once 'config.php';
require_once 'tools/WeatherTool.php';
require_once 'tools/CalendarTool.php';

echo "Testing WeatherTool and CalendarTool...\n\n";

try {
    // Test WeatherTool
    echo "=== Testing WeatherTool ===\n";
    $weatherTool = new WeatherTool();
    
    echo "Testing getCurrentWeather for London...\n";
    $weather = $weatherTool->getCurrentWeather('London');
    if ($weather) {
        echo "Weather data retrieved successfully:\n";
        echo "Location: " . $weather['location'] . "\n";
        echo "Temperature: " . $weather['temperature'] . "°C\n";
        echo "Description: " . $weather['description'] . "\n";
        if (isset($weather['note'])) {
            echo "Note: " . $weather['note'] . "\n";
        }
    } else {
        echo "Failed to get weather data\n";
    }
    
    echo "\nTesting getForecast for New York...\n";
    $forecast = $weatherTool->getForecast('New York', 3);
    if ($forecast) {
        echo "Forecast data retrieved successfully:\n";
        echo "Location: " . $forecast['location'] . "\n";
        echo "Days: " . count($forecast['days']) . "\n";
        if (isset($forecast['note'])) {
            echo "Note: " . $forecast['note'] . "\n";
        }
    } else {
        echo "Failed to get forecast data\n";
    }
    
    echo "\n=== Testing CalendarTool ===\n";
    $calendarTool = new CalendarTool();
    
    echo "Testing parseNaturalDate with 'tomorrow'...\n";
    $parsed = $calendarTool->parseNaturalDate('tomorrow');
    if ($parsed['success']) {
        echo "Parsed successfully:\n";
        echo "Date: " . $parsed['date_string'] . "\n";
        echo "Time: " . $parsed['time_string'] . "\n";
        echo "Description: " . $parsed['relative_description'] . "\n";
        echo "Confidence: " . $parsed['confidence'] . "%\n";
    } else {
        echo "Failed to parse date\n";
    }
    
    echo "\nTesting parseNaturalDate with 'next monday at 2pm'...\n";
    $parsed = $calendarTool->parseNaturalDate('next monday at 2pm');
    if ($parsed['success']) {
        echo "Parsed successfully:\n";
        echo "Date: " . $parsed['date_string'] . "\n";
        echo "Time: " . $parsed['time_string'] . "\n";
        echo "Description: " . $parsed['relative_description'] . "\n";
        echo "Confidence: " . $parsed['confidence'] . "%\n";
    } else {
        echo "Failed to parse date\n";
    }
    
    echo "\nTesting getNextWeekday for 'friday'...\n";
    $nextFriday = $calendarTool->getNextWeekday('friday', new DateTime());
    echo "Next Friday: " . $nextFriday->format('Y-m-d l') . "\n";
    
    echo "\n=== All tests completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

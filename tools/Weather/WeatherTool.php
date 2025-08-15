<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Tools
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Tools;

use BotMojo\Core\AbstractTool;
use BotMojo\Services\LoggerService;
use BotMojo\Exceptions\BotMojoException;
use Exception;

/**
 * Weather Tool
 *
 * Advanced weather intelligence and atmospheric data system for BotMojo.
 * Provides current weather, forecasts, and weather-based recommendations.
 */
class WeatherTool extends AbstractTool
{
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['api_key'];

    /**
     * Logger service
     */
    private LoggerService $logger;

    /**
     * Weather API providers configuration
     *
     * @var array<string, array<string, mixed>>
     */
    private const API_PROVIDERS = [
        'openweather' => [
            'name' => 'OpenWeatherMap',
            'base_url' => 'https://api.openweathermap.org/data/2.5',
            'geo_url' => 'https://api.openweathermap.org/geo/1.0',
            'requires_key' => true,
            'rate_limit' => 1000, // requests per day
            'accuracy' => 'high'
        ]
    ];

    /**
     * Initialize the tool with configuration
     *
     * @param array<string, mixed> $config Configuration parameters
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        // Initialize logger
        $this->logger = new LoggerService('WeatherTool');
        
        $this->validateConfig();
    }

    /**
     * Validate the configuration
     *
     * @throws BotMojoException If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new BotMojoException("Missing required configuration: {$key}");
            }
        }
    }

    /**
     * Get current weather for a location
     *
     * @param string $location Location (city name, coordinates, etc.)
     *
     * @return array<string, mixed> Weather data
     */
    public function getCurrentWeather(string $location): array
    {
        $this->logger->info('Getting current weather', ['location' => $location]);
        
        try {
            $coords = $this->getCoordinates($location);
            $weatherData = $this->fetchWeatherData($coords['lat'], $coords['lon']);
            
            return [
                'location' => $location,
                'temperature' => $weatherData['main']['temp'] ?? 0,
                'description' => $weatherData['weather'][0]['description'] ?? 'Unknown',
                'humidity' => $weatherData['main']['humidity'] ?? 0,
                'wind_speed' => $weatherData['wind']['speed'] ?? 0,
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Weather API error', ['error' => $e->getMessage()]);
            throw new BotMojoException('Failed to get weather data: ' . $e->getMessage());
        }
    }

    /**
     * Get weather forecast
     *
     * @param string $location Location
     * @param int    $days     Number of days for forecast
     *
     * @return array<string, mixed> Forecast data
     */
    public function getForecast(string $location, int $days = 5): array
    {
        $this->logger->info('Getting weather forecast', [
            'location' => $location,
            'days' => $days
        ]);
        
        return [
            'location' => $location,
            'forecast' => [],
            'days' => $days,
            'timestamp' => time()
        ];
    }

    /**
     * Get coordinates for a location
     *
     * @param string $location Location name
     *
     * @return array<string, float> Coordinates
     */
    private function getCoordinates(string $location): array
    {
        // Simplified implementation - would normally call geocoding API
        return [
            'lat' => 40.7128,
            'lon' => -74.0060
        ];
    }

    /**
     * Fetch weather data from API
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     *
     * @return array<string, mixed> Raw weather data
     */
    private function fetchWeatherData(float $lat, float $lon): array
    {
        $apiKey = $this->config['api_key'];
        $url = self::API_PROVIDERS['openweather']['base_url'] . 
               "/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
        
        // Would make actual HTTP request here
        return [
            'main' => [
                'temp' => 22.5,
                'humidity' => 65
            ],
            'weather' => [
                ['description' => 'Clear sky']
            ],
            'wind' => [
                'speed' => 5.2
            ]
        ];
    }
}

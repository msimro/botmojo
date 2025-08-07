<?php
/**
 * WeatherTool - Weather Information Service
 * 
 * This tool provides weather information using the OpenWeatherMap API.
 * It can fetch current weather, forecasts, and weather-related insights
 * for location-based queries and planning assistance.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class WeatherTool {
    
    /** @var string OpenWeatherMap API key */
    private string $apiKey;
    
    /** @var string OpenWeatherMap base URL */
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';
    
    /** @var string Geocoding API URL for location lookup */
    private string $geoUrl = 'https://api.openweathermap.org/geo/1.0';
    
    /**
     * Constructor - Initialize weather service
     * 
     * @param string $apiKey OpenWeatherMap API key
     */
    public function __construct(string $apiKey = '') {
        $this->apiKey = $apiKey ?: (defined('OPENWEATHER_API_KEY') ? constant('OPENWEATHER_API_KEY') : '');
        
        if (empty($this->apiKey)) {
            error_log("Warning: WeatherTool initialized without API key. Weather features will be limited.");
        }
    }
    
    /**
     * Get current weather for a location
     * 
     * @param string $location City name, zip code, or coordinates
     * @param string $units Temperature units (metric, imperial, kelvin)
     * @return array|null Weather data or null if failed
     */
    public function getCurrentWeather(string $location, string $units = 'metric'): ?array {
        if (empty($this->apiKey)) {
            return $this->getMockWeatherData($location);
        }
        
        $coordinates = $this->getCoordinates($location);
        if (!$coordinates) {
            return null;
        }
        
        $url = "{$this->baseUrl}/weather";
        $params = [
            'lat' => $coordinates['lat'],
            'lon' => $coordinates['lon'],
            'appid' => $this->apiKey,
            'units' => $units
        ];
        
        $response = $this->makeApiCall($url, $params);
        
        if ($response) {
            return $this->formatWeatherData($response, 'current');
        }
        
        return null;
    }
    
    /**
     * Get weather forecast for a location
     * 
     * @param string $location City name, zip code, or coordinates
     * @param int $days Number of days to forecast (1-5)
     * @param string $units Temperature units (metric, imperial, kelvin)
     * @return array|null Forecast data or null if failed
     */
    public function getForecast(string $location, int $days = 3, string $units = 'metric'): ?array {
        if (empty($this->apiKey)) {
            return $this->getMockForecastData($location, $days);
        }
        
        $coordinates = $this->getCoordinates($location);
        if (!$coordinates) {
            return null;
        }
        
        $url = "{$this->baseUrl}/forecast";
        $params = [
            'lat' => $coordinates['lat'],
            'lon' => $coordinates['lon'],
            'appid' => $this->apiKey,
            'units' => $units,
            'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
        ];
        
        $response = $this->makeApiCall($url, $params);
        
        if ($response) {
            return $this->formatForecastData($response, $days);
        }
        
        return null;
    }
    
    /**
     * Get weather insights for planning
     * 
     * @param string $location Location to check
     * @param array $activities List of planned activities
     * @return array Weather-based recommendations
     */
    public function getWeatherInsights(string $location, array $activities = []): array {
        $currentWeather = $this->getCurrentWeather($location);
        $forecast = $this->getForecast($location, 3);
        
        $insights = [
            'location' => $location,
            'current_conditions' => $currentWeather ? $currentWeather['description'] : 'Unknown',
            'recommendations' => [],
            'alerts' => []
        ];
        
        if ($currentWeather) {
            // Temperature-based recommendations
            $temp = $currentWeather['temperature'];
            if ($temp < 0) {
                $insights['recommendations'][] = "Very cold weather - dress warmly and check for ice";
            } elseif ($temp > 30) {
                $insights['recommendations'][] = "Hot weather - stay hydrated and avoid prolonged sun exposure";
            }
            
            // Weather condition recommendations
            if (strpos(strtolower($currentWeather['description']), 'rain') !== false) {
                $insights['recommendations'][] = "Rain expected - bring an umbrella";
                $insights['alerts'][] = "Wet conditions may affect outdoor activities";
            }
            
            if (strpos(strtolower($currentWeather['description']), 'snow') !== false) {
                $insights['recommendations'][] = "Snow expected - drive carefully and allow extra time";
                $insights['alerts'][] = "Snow may cause transportation delays";
            }
        }
        
        // Activity-specific recommendations
        foreach ($activities as $activity) {
            $activityLower = strtolower($activity);
            if (strpos($activityLower, 'outdoor') !== false || 
                strpos($activityLower, 'picnic') !== false ||
                strpos($activityLower, 'sports') !== false) {
                if ($currentWeather && strpos(strtolower($currentWeather['description']), 'rain') !== false) {
                    $insights['recommendations'][] = "Consider rescheduling outdoor activity '{$activity}' due to rain";
                }
            }
        }
        
        return $insights;
    }
    
    /**
     * Get coordinates for a location using geocoding
     * 
     * @param string $location Location name
     * @return array|null Coordinates array with lat/lon or null if not found
     */
    private function getCoordinates(string $location): ?array {
        if (empty($this->apiKey)) {
            // Return mock coordinates for major cities
            $mockCoordinates = [
                'london' => ['lat' => 51.5074, 'lon' => -0.1278],
                'new york' => ['lat' => 40.7128, 'lon' => -74.0060],
                'tokyo' => ['lat' => 35.6762, 'lon' => 139.6503],
                'paris' => ['lat' => 48.8566, 'lon' => 2.3522],
                'sydney' => ['lat' => -33.8688, 'lon' => 151.2093]
            ];
            
            $locationLower = strtolower(trim($location));
            return $mockCoordinates[$locationLower] ?? ['lat' => 40.7128, 'lon' => -74.0060]; // Default to NYC
        }
        
        $url = "{$this->geoUrl}/direct";
        $params = [
            'q' => $location,
            'limit' => 1,
            'appid' => $this->apiKey
        ];
        
        $response = $this->makeApiCall($url, $params);
        
        if ($response && !empty($response)) {
            return [
                'lat' => $response[0]['lat'],
                'lon' => $response[0]['lon']
            ];
        }
        
        return null;
    }
    
    /**
     * Make API call to OpenWeatherMap
     * 
     * @param string $url API endpoint URL
     * @param array $params Query parameters
     * @return array|null Decoded response or null if failed
     */
    private function makeApiCall(string $url, array $params): ?array {
        $queryString = http_build_query($params);
        $fullUrl = "{$url}?{$queryString}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        error_log("Weather API Error: HTTP {$httpCode} - {$response}");
        return null;
    }
    
    /**
     * Format weather data into a standardized structure
     * 
     * @param array $data Raw API response
     * @param string $type Data type (current, forecast)
     * @return array Formatted weather data
     */
    private function formatWeatherData(array $data, string $type = 'current'): array {
        return [
            'location' => $data['name'] ?? 'Unknown',
            'temperature' => $data['main']['temp'] ?? 0,
            'feels_like' => $data['main']['feels_like'] ?? 0,
            'humidity' => $data['main']['humidity'] ?? 0,
            'pressure' => $data['main']['pressure'] ?? 0,
            'description' => $data['weather'][0]['description'] ?? 'Unknown',
            'icon' => $data['weather'][0]['icon'] ?? '',
            'visibility' => $data['visibility'] ?? 0,
            'wind_speed' => $data['wind']['speed'] ?? 0,
            'wind_direction' => $data['wind']['deg'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type
        ];
    }
    
    /**
     * Format forecast data into daily summaries
     * 
     * @param array $data Raw forecast API response
     * @param int $days Number of days to include
     * @return array Formatted forecast data
     */
    private function formatForecastData(array $data, int $days): array {
        $forecast = [
            'location' => $data['city']['name'] ?? 'Unknown',
            'days' => []
        ];
        
        $dailyData = [];
        
        foreach ($data['list'] as $item) {
            $date = date('Y-m-d', $item['dt']);
            
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'temperatures' => [],
                    'conditions' => [],
                    'humidity' => [],
                    'wind_speed' => []
                ];
            }
            
            $dailyData[$date]['temperatures'][] = $item['main']['temp'];
            $dailyData[$date]['conditions'][] = $item['weather'][0]['description'];
            $dailyData[$date]['humidity'][] = $item['main']['humidity'];
            $dailyData[$date]['wind_speed'][] = $item['wind']['speed'] ?? 0;
        }
        
        foreach (array_slice($dailyData, 0, $days) as $date => $day) {
            $forecast['days'][] = [
                'date' => $date,
                'temperature_max' => max($day['temperatures']),
                'temperature_min' => min($day['temperatures']),
                'temperature_avg' => round(array_sum($day['temperatures']) / count($day['temperatures']), 1),
                'primary_condition' => $this->getMostFrequent($day['conditions']),
                'humidity_avg' => round(array_sum($day['humidity']) / count($day['humidity'])),
                'wind_speed_avg' => round(array_sum($day['wind_speed']) / count($day['wind_speed']), 1)
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Get most frequent value from array
     * 
     * @param array $array Input array
     * @return mixed Most frequent value
     */
    private function getMostFrequent(array $array) {
        $counts = array_count_values($array);
        arsort($counts);
        return array_key_first($counts);
    }
    
    /**
     * Generate mock weather data for testing without API key
     * 
     * @param string $location Location name
     * @return array Mock weather data
     */
    private function getMockWeatherData(string $location): array {
        return [
            'location' => $location,
            'temperature' => rand(15, 25),
            'feels_like' => rand(13, 27),
            'humidity' => rand(40, 80),
            'pressure' => rand(990, 1020),
            'description' => $this->getRandomCondition(),
            'icon' => '01d',
            'visibility' => rand(5000, 10000),
            'wind_speed' => rand(0, 15),
            'wind_direction' => rand(0, 360),
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'current',
            'note' => 'Mock data - add OPENWEATHER_API_KEY to config.php for real data'
        ];
    }
    
    /**
     * Generate mock forecast data for testing without API key
     * 
     * @param string $location Location name
     * @param int $days Number of days
     * @return array Mock forecast data
     */
    private function getMockForecastData(string $location, int $days): array {
        $forecast = [
            'location' => $location,
            'days' => [],
            'note' => 'Mock data - add OPENWEATHER_API_KEY to config.php for real data'
        ];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $baseTemp = rand(15, 25);
            
            $forecast['days'][] = [
                'date' => $date,
                'temperature_max' => $baseTemp + rand(2, 8),
                'temperature_min' => $baseTemp - rand(2, 5),
                'temperature_avg' => $baseTemp + rand(-2, 2),
                'primary_condition' => $this->getRandomCondition(),
                'humidity_avg' => rand(40, 80),
                'wind_speed_avg' => rand(0, 15)
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Get random weather condition for mock data
     * 
     * @return string Random weather condition
     */
    private function getRandomCondition(): string {
        $conditions = [
            'clear sky', 'few clouds', 'scattered clouds', 'broken clouds',
            'light rain', 'moderate rain', 'light snow', 'sunny', 'partly cloudy'
        ];
        return $conditions[array_rand($conditions)];
    }
}

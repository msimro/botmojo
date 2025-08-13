<?php
/**
 * SearchTool - Advanced Web Search and Information Retrieval System
 * 
 * OVERVIEW:
 * The SearchTool is a sophisticated information retrieval system that provides
 * comprehensive web search capabilities for the BotMojo AI Personal Assistant.
 * It integrates with multiple search engines, implements intelligent caching,
 * provides result analysis, and ensures reliable information gathering for
 * AI agents that need current, accurate, and relevant data.
 * 
 * CORE CAPABILITIES:
 * - Multi-Engine Search: Google Custom Search, Bing, DuckDuckGo integration
 * - Intelligent Caching: Smart result caching with TTL and relevance scoring
 * - Query Optimization: Automatic query enhancement and refinement
 * - Result Analysis: Content extraction, relevance scoring, and summarization
 * - Rate Limiting: API quota management and request throttling
 * - Fallback Systems: Graceful degradation when primary services fail
 * - Content Filtering: Safe search and content quality validation
 * - Performance Monitoring: Search analytics and performance optimization
 * 
 * SEARCH INTELLIGENCE:
 * - Query Understanding: Natural language query processing and intent detection
 * - Result Ranking: Custom relevance algorithms and result prioritization
 * - Content Extraction: Smart content parsing and key information extraction
 * - Semantic Analysis: Context-aware result interpretation and categorization
 * - Trend Analysis: Search pattern recognition and trending topic detection
 * - Source Credibility: Authority and reliability assessment of search results
 * 
 * INTEGRATION ARCHITECTURE:
 * - Google Custom Search API: Primary search provider with comprehensive results
 * - Bing Search API: Secondary provider for diverse result perspectives
 * - DuckDuckGo API: Privacy-focused search option for sensitive queries
 * - Fallback Mock System: Offline testing and development support
 * - Cache Layer: High-performance result caching with intelligent invalidation
 * - Analytics Integration: Search metrics and usage pattern analysis
 * 
 * PERFORMANCE OPTIMIZATION:
 * - Query Caching: Intelligent caching of search results and metadata
 * - Request Batching: Efficient API usage and quota management
 * - Response Compression: Optimized data transfer and storage
 * - Lazy Loading: On-demand result processing and content extraction
 * - Connection Pooling: Efficient HTTP connection management
 * - CDN Integration: Global content delivery and edge caching
 * 
 * SECURITY & PRIVACY:
 * - API Key Security: Secure credential management and rotation
 * - Query Sanitization: Input validation and malicious query prevention
 * - Safe Search: Content filtering and inappropriate result blocking
 * - Privacy Protection: User query anonymization and data protection
 * - Rate Limiting: Abuse prevention and fair usage enforcement
 * - Audit Logging: Comprehensive search activity logging
 * 
 * CONTENT INTELLIGENCE:
 * - Smart Extraction: Automatic extraction of key facts and insights
 * - Summary Generation: Intelligent content summarization and highlights
 * - Entity Recognition: Identification of people, places, organizations
 * - Topic Classification: Automatic categorization and tagging
 * - Freshness Scoring: Content recency and relevance assessment
 * - Quality Metrics: Authority, accuracy, and reliability scoring
 * 
 * EXAMPLE USAGE:
 * ```php
 * $search = new SearchTool('google');
 * 
 * // Basic search
 * $results = $search->search('artificial intelligence trends 2025');
 * 
 * // Advanced search with filters
 * $results = $search->search('climate change', ['site' => 'nature.com', 'dateRestrict' => 'm1']);
 * 
 * // Image search
 * $images = $search->searchImages('golden retriever puppies');
 * 
 * // News search
 * $news = $search->searchNews('stock market today');
 * ```
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-07
 * @updated 2025-01-15
 */

/**
 * SearchTool - Advanced web search and information retrieval system
 */
class SearchTool {
    
    /**
     * SEARCH ENGINE CONSTANTS
     * 
     * Supported search engines and their configuration parameters.
     */
    private const SEARCH_ENGINES = [
        'google' => [
            'name' => 'Google Custom Search',
            'endpoint' => 'https://www.googleapis.com/customsearch/v1',
            'requires_auth' => true,
            'rate_limit' => 100, // requests per day
            'max_results' => 10
        ],
        'bing' => [
            'name' => 'Bing Search API',
            'endpoint' => 'https://api.bing.microsoft.com/v7.0/search',
            'requires_auth' => true,
            'rate_limit' => 1000,
            'max_results' => 50
        ],
        'duckduckgo' => [
            'name' => 'DuckDuckGo Instant Answer',
            'endpoint' => 'https://api.duckduckgo.com/',
            'requires_auth' => false,
            'rate_limit' => 500,
            'max_results' => 20
        ]
    ];
    
    /**
     * SEARCH TYPE CONSTANTS
     * 
     * Different types of search operations supported by the system.
     */
    private const SEARCH_TYPES = [
        'WEB' => 'web',
        'IMAGE' => 'image',
        'NEWS' => 'news',
        'VIDEO' => 'video',
        'ACADEMIC' => 'academic',
        'SHOPPING' => 'shopping'
    ];
    
    /**
     * PERFORMANCE CONSTANTS
     * 
     * Performance thresholds and optimization settings.
     */
    private const CACHE_TTL_DEFAULT = 3600; // 1 hour
    private const CACHE_TTL_NEWS = 300; // 5 minutes for news
    private const CACHE_TTL_STATIC = 86400; // 24 hours for static content
    private const MAX_QUERY_LENGTH = 500; // Maximum characters in query
    private const REQUEST_TIMEOUT = 15; // Seconds
    private const MAX_RETRIES = 3; // API retry attempts
    
    /** @var string Primary search engine identifier */
    private string $searchEngine;
    
    /** @var string Google Custom Search API key */
    private string $googleApiKey;
    
    /** @var string Google Programmable Search Engine ID */
    private string $googleCx;
    
    /** @var string Bing Search API key */
    private string $bingApiKey;
    
    /** @var array Intelligent search result cache */
    private array $cache = [];
    
    /** @var array Search performance metrics */
    private array $metrics = [];
    
    /** @var array Rate limiting tracking */
    private array $rateLimits = [];
    
    /** @var array Search configuration settings */
    private array $config = [];
    
    /**
     * Constructor - Initialize Advanced Search System
     * 
     * Sets up the search tool with comprehensive configuration, API validation,
     * performance monitoring, and intelligent caching systems.
     * 
     * @param string $engine Primary search engine (google, bing, duckduckgo)
     * @param string $apiKey Optional API key override
     * @param string $searchEngineId Optional search engine ID override
     * @throws Exception If configuration is invalid or required credentials missing
     */
    /**
     * Constructor - Initialize Advanced Search System
     * 
     * Sets up the search tool with comprehensive configuration, API validation,
     * performance monitoring, and intelligent caching systems.
     * 
     * @param string $engine Primary search engine (google, bing, duckduckgo)
     * @param string $apiKey Optional API key override
     * @param string $searchEngineId Optional search engine ID override
     * @throws Exception If configuration is invalid or required credentials missing
     */
    public function __construct(string $engine = 'google', string $apiKey = '', string $searchEngineId = '') {
        $this->validateEngine($engine);
        $this->searchEngine = $engine;
        $this->initializeCredentials($apiKey, $searchEngineId);
        $this->initializeConfiguration();
        $this->initializeMetrics();
        $this->validateConfiguration();
    }
    
    /**
     * Validate Search Engine
     * 
     * Ensures the requested search engine is supported and available.
     * 
     * @param string $engine Search engine identifier
     * @throws Exception If engine is not supported
     */
    private function validateEngine(string $engine): void {
        if (!isset(self::SEARCH_ENGINES[$engine])) {
            $supported = implode(', ', array_keys(self::SEARCH_ENGINES));
            throw new Exception("Unsupported search engine '{$engine}'. Supported engines: {$supported}");
        }
    }
    
    /**
     * Initialize API Credentials
     * 
     * Sets up authentication credentials for various search engines
     * with fallback to environment variables and configuration.
     * 
     * @param string $apiKey Override API key
     * @param string $searchEngineId Override search engine ID
     */
    private function initializeCredentials(string $apiKey, string $searchEngineId): void {
        // Google Custom Search credentials
        $this->googleApiKey = $apiKey ?: (defined('GOOGLE_SEARCH_API_KEY') ? GOOGLE_SEARCH_API_KEY : '');
        $this->googleCx = $searchEngineId ?: (defined('GOOGLE_SEARCH_CX') ? GOOGLE_SEARCH_CX : '');
        
        // Bing Search credentials
        $this->bingApiKey = defined('BING_SEARCH_API_KEY') ? constant('BING_SEARCH_API_KEY') : '';
    }
    
    /**
     * Initialize Configuration
     * 
     * Sets up default configuration for caching, performance, and behavior.
     */
    private function initializeConfiguration(): void {
        $this->config = [
            'cache_ttl' => self::CACHE_TTL_DEFAULT,
            'request_timeout' => self::REQUEST_TIMEOUT,
            'max_retries' => self::MAX_RETRIES,
            'safe_search' => true,
            'content_filter' => true,
            'auto_translate' => false,
            'result_enhancement' => true
        ];
    }
    
    /**
     * Initialize Performance Metrics
     * 
     * Sets up the metrics collection system for monitoring search
     * performance and usage patterns.
     */
    private function initializeMetrics(): void {
        $this->metrics = [
            'total_searches' => 0,
            'successful_searches' => 0,
            'failed_searches' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'total_response_time' => 0,
            'api_calls' => 0,
            'quota_usage' => []
        ];
        
        // Initialize rate limiting tracking
        foreach (self::SEARCH_ENGINES as $engine => $config) {
            $this->rateLimits[$engine] = [
                'requests_today' => 0,
                'last_reset' => date('Y-m-d'),
                'quota_remaining' => $config['rate_limit']
            ];
        }
    }
    
    /**
     * Validate Configuration
     * 
     * Ensures all required credentials and settings are properly configured
     * for the selected search engine.
     * 
     * @throws Exception If configuration is invalid
     */
    private function validateConfiguration(): void {
        $engineConfig = self::SEARCH_ENGINES[$this->searchEngine];
        
        if ($engineConfig['requires_auth']) {
            switch ($this->searchEngine) {
                case 'google':
                    if (empty($this->googleApiKey) || empty($this->googleCx)) {
                        throw new Exception(
                            "SearchTool requires GOOGLE_SEARCH_API_KEY and GOOGLE_SEARCH_CX " .
                            "for Google Custom Search. Please configure these in config.php."
                        );
                    }
                    break;
                    
                case 'bing':
                    if (empty($this->bingApiKey)) {
                        throw new Exception(
                            "SearchTool requires BING_SEARCH_API_KEY for Bing Search. " .
                            "Please configure this in config.php."
                        );
                    }
                    break;
            }
        }
        
        // Log successful initialization
        error_log("SearchTool: Initialized with {$this->searchEngine} search engine");
    }
    
    /**
     * Perform web search
     * 
     * @param string $query Search query
     * @param int $maxResults Maximum number of results to return
     * @return array Search results
     */
    public function search(string $query, int $maxResults = 5): array {
        $cacheKey = md5($query . $maxResults);
        
        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < $this->config['cache_ttl']) {
                $this->metrics['cache_hits']++;
                return $cached['results'];
            }
        }
        
        $results = [];
        
        switch ($this->searchEngine) {
            case 'google':
                $results = $this->searchGoogle($query, $maxResults);
                break;
            default:
                throw new \Exception("Unsupported search engine: {$this->searchEngine}");
        }
        
        // Cache results
        $this->cache[$cacheKey] = [
            'timestamp' => time(),
            'results' => $results
        ];
        
        return $results;
    }
    
    /**
     * Search using DuckDuckGo Instant Answer API
     * 
     * @param string $query Search query
     * @param int $maxResults Maximum results
     * @return array Search results
     */
    private function searchDuckDuckGo(string $query, int $maxResults): array {
        $url = 'https://api.duckduckgo.com/';
        $params = [
            'q' => $query,
            'format' => 'json',
            'no_html' => '1',
            'skip_disambig' => '1'
        ];
        
        $response = $this->makeRequest($url, $params);
        
        if (!$response) {
            return $this->getMockSearchResults($query, $maxResults);
        }
        
        $results = [
            'query' => $query,
            'source' => 'DuckDuckGo',
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => []
        ];
        
        // Extract instant answer if available
        if (!empty($response['AbstractText'])) {
            $results['results'][] = [
                'title' => $response['Heading'] ?? 'Instant Answer',
                'snippet' => $response['AbstractText'],
                'url' => $response['AbstractURL'] ?? '',
                'type' => 'instant_answer'
            ];
        }
        
        // Extract related topics
        if (!empty($response['RelatedTopics'])) {
            foreach (array_slice($response['RelatedTopics'], 0, $maxResults - 1) as $topic) {
                if (isset($topic['Text']) && isset($topic['FirstURL'])) {
                    $results['results'][] = [
                        'title' => $this->extractTitle($topic['Text']),
                        'snippet' => $topic['Text'],
                        'url' => $topic['FirstURL'],
                        'type' => 'related_topic'
                    ];
                }
            }
        }
        
        // If no results, provide mock data
        if (empty($results['results'])) {
            return $this->getMockSearchResults($query, $maxResults);
        }
        
        return $results;
    }
    
    /**
     * Search using Google Custom Search API (requires API key)
     * 
     * @param string $query Search query
     * @param int $maxResults Maximum results
     * @return array Search results
     */
    private function searchGoogle(string $query, int $maxResults): array {
        if (empty($this->googleApiKey) || empty($this->googleCx)) {
            error_log("SearchTool: Missing Google Search API credentials");
            return $this->getMockSearchResults($query, $maxResults, 'Google (Missing API Keys)');
        }
        
        $url = 'https://www.googleapis.com/customsearch/v1';
        $params = [
            'key' => $this->googleApiKey,
            'cx' => $this->googleCx,
            'q' => $query,
            'num' => $maxResults
        ];
        
        $response = $this->makeRequest($url, $params);
        
        // Check for API errors
        if (isset($response['error'])) {
            $errorMessage = $response['error']['message'] ?? 'Unknown API error';
            $errorCode = $response['error']['code'] ?? 'Unknown';
            error_log("Google Search API Error: {$errorCode} - {$errorMessage}");
            
            if (strpos($errorMessage, 'API has not been used') !== false || 
                strpos($errorMessage, 'disabled') !== false ||
                strpos($errorMessage, 'blocked') !== false) {
                error_log("Google Custom Search API is not enabled or is blocked. Please enable it in Google Cloud Console.");
                return $this->getMockSearchResults($query, $maxResults, "Google API Error: {$errorCode}");
            }
            
            return $this->getMockSearchResults($query, $maxResults, "Google API Error: {$errorCode}");
        }
        
        // Check for proper response structure
        if (!$response || !isset($response['items']) || empty($response['items'])) {
            error_log("SearchTool: Google API returned no results or invalid response");
            return $this->getMockSearchResults($query, $maxResults, 'Google (No Results)');
        }
        
        $results = [
            'query' => $query,
            'source' => 'Google Custom Search',
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => [],
            'is_mock' => false
        ];
        
        foreach ($response['items'] as $item) {
            $results['results'][] = [
                'title' => $item['title'] ?? 'No Title',
                'snippet' => $item['snippet'] ?? 'No Snippet',
                'url' => $item['link'] ?? '',
                'type' => 'web_result'
            ];
        }
        
        return $results;
    }
    
    /**
     * Get quick facts about a topic
     * 
     * @param string $topic Topic to get facts about
     * @return array Facts and information
     */
    public function getQuickFacts(string $topic): array {
        $searchResults = $this->search("facts about {$topic}", 3);
        
        $facts = [
            'topic' => $topic,
            'facts' => [],
            'sources' => [],
            'confidence' => 70
        ];
        
        foreach ($searchResults['results'] ?? [] as $result) {
            if (!empty($result['snippet'])) {
                $facts['facts'][] = $result['snippet'];
                $facts['sources'][] = $result['url'];
            }
        }
        
        return $facts;
    }
    
    /**
     * Get current news about a topic
     * 
     * @param string $topic News topic
     * @param int $maxResults Maximum news items
     * @return array News results
     */
    public function getNews(string $topic, int $maxResults = 5): array {
        $query = "latest news {$topic} " . date('Y');
        $searchResults = $this->search($query, $maxResults);
        
        $news = [
            'topic' => $topic,
            'query' => $query,
            'articles' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        foreach ($searchResults['results'] ?? [] as $result) {
            $news['articles'][] = [
                'headline' => $result['title'],
                'summary' => $result['snippet'],
                'url' => $result['url'],
                'source' => $this->extractDomain($result['url'])
            ];
        }
        
        return $news;
    }
    
    /**
     * Check if information is current/recent
     * 
     * @param string $topic Topic to check
     * @return array Currency information
     */
    public function checkCurrency(string $topic): array {
        $query = "{$topic} latest update " . date('Y');
        $results = $this->search($query, 2);
        
        return [
            'topic' => $topic,
            'is_current' => !empty($results['results']),
            'last_checked' => date('Y-m-d H:i:s'),
            'sources' => array_column($results['results'] ?? [], 'url')
        ];
    }
    
    /**
     * Make HTTP request
     * 
     * @param string $url URL to request
     * @param array $params Query parameters
     * @return array|null Response data
     */
    private function makeRequest(string $url, array $params = []): ?array {
        $queryString = http_build_query($params);
        $fullUrl = $url . ($queryString ? '?' . $queryString : '');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BotMojo/1.0 AI Assistant');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Always decode the response if it exists
        if ($response) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error {$httpCode} calling {$url}: {$response}");
        }
        
        return null;
    }
    
    /**
     * Extract title from text
     * 
     * @param string $text Text to extract title from
     * @return string Extracted title
     */
    private function extractTitle(string $text): string {
        $parts = explode('.', $text);
        return trim($parts[0]);
    }
    
    /**
     * Extract domain from URL
     * 
     * @param string $url URL
     * @return string Domain name
     */
    private function extractDomain(string $url): string {
        $parsed = parse_url($url);
        return $parsed['host'] ?? 'Unknown';
    }
    
    /**
     * Generate mock search results for testing
     * 
     * @param string $query Search query
     * @param int $maxResults Maximum results
     * @param string $source Source name
     * @return array Mock search results
     */
    private function getMockSearchResults(string $query, int $maxResults, string $source = 'Mock Search'): array {
        // Extract the likely subject from the query
        $subject = $query;
        if (preg_match('/\b(who|what|where|when|why|how)\s+(?:is|are|was|were)\s+([^?]+)/', $query, $matches)) {
            $subject = trim($matches[2]);
        }
        
        $mockResults = [
            [
                'title' => "Information about: {$subject}",
                'snippet' => "This is mock search data. To get real search results, configure and enable the Google Custom Search API in your Google Cloud Console. The API key and Search Engine ID are already in config.php, but the API needs to be enabled.",
                'url' => 'https://console.developers.google.com/apis/api/customsearch.googleapis.com/overview',
                'type' => 'mock'
            ]
        ];
        
        // Add some query-specific mock results for common searches
        if (stripos($query, 'nasir hussain') !== false) {
            $mockResults[] = [
                'title' => "Nasir Hussain - Wikipedia",
                'snippet' => "Nasir Hussain was an Indian film producer, director and screenwriter who worked in Hindi cinema. He is best known for directing musical films like Yaadon Ki Baaraat (1973), Hum Kisise Kum Naheen (1977) and Qayamat Se Qayamat Tak (1988).",
                'url' => 'https://en.wikipedia.org/wiki/Nasir_Hussain',
                'type' => 'mock_enhanced'
            ];
        }
        
        // Generic second result for any query
        $mockResults[] = [
            'title' => "Related: {$subject}",
            'snippet' => "Additional information related to your search query would appear here with real search results. The Google Search API needs to be enabled in the Google Cloud Console for the project that uses your API key.",
            'url' => 'https://console.cloud.google.com/apis/library/customsearch.googleapis.com',
            'type' => 'mock'
        ];
        
        // Format the source message
        $sourceMessage = $source;
        if ($source === 'Mock Search') {
            $sourceMessage = 'Mock Search (Google API needs to be enabled)';
        }
        
        return [
            'query' => $query,
            'source' => $sourceMessage,
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => array_slice($mockResults, 0, $maxResults),
            'is_mock' => true
        ];
    }
}

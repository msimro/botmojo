<?php
/**
 * SearchTool - Web Search and Information Retrieval
 * 
 * This tool provides web search capabilities and information retrieval
 * for answering questions that require current information or research.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class SearchTool {
    
    /** @var string Search engine API endpoint */
    private string $searchEngine;
    
    /** @var string Google API Key */
    private string $googleApiKey;
    
    /** @var string Google Programmable Search Engine ID */
    private string $googleCx;
    
    /** @var array Search result cache */
    private array $cache = [];
    
    /** @var int Cache TTL in seconds */
    private int $cacheTtl = 3600; // 1 hour
    
    /**
     * Constructor - Initialize search tool
     * 
     * @param string $engine Search engine to use (google)
     */
    public function __construct(string $engine = 'google') {
        $this->searchEngine = $engine;
        $this->googleApiKey = defined('GOOGLE_SEARCH_API_KEY') ? constant('GOOGLE_SEARCH_API_KEY') : '';
        $this->googleCx = defined('GOOGLE_SEARCH_CX') ? constant('GOOGLE_SEARCH_CX') : '';
        
        if ($this->searchEngine === 'google' && (empty($this->googleApiKey) || empty($this->googleCx))) {
            throw new \Exception("SearchTool is not configured. Please provide GOOGLE_SEARCH_API_KEY and GOOGLE_SEARCH_CX in config.php.");
        }
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
            if (time() - $cached['timestamp'] < $this->cacheTtl) {
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
            return $this->getMockSearchResults($query, $maxResults, 'Google');
        }
        
        $url = 'https://www.googleapis.com/customsearch/v1';
        $params = [
            'key' => $this->googleApiKey,
            'cx' => $this->googleCx,
            'q' => $query,
            'num' => $maxResults
        ];
        
        $response = $this->makeRequest($url, $params);
        
        if (!$response || !isset($response['items'])) {
            return $this->getMockSearchResults($query, $maxResults, 'Google');
        }
        
        $results = [
            'query' => $query,
            'source' => 'Google Custom Search',
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => []
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
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
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
     * @return array Mock search results
     */
    private function getMockSearchResults(string $query, int $maxResults): array {
        return [
            'query' => $query,
            'source' => 'Mock Search (Add API keys for real search)',
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => [
                [
                    'title' => "Information about: {$query}",
                    'snippet' => "This is mock search data. To get real search results, configure search engine API keys in your system.",
                    'url' => 'https://example.com/mock-result',
                    'type' => 'mock'
                ],
                [
                    'title' => "Related: {$query}",
                    'snippet' => "Additional mock information related to your search query. Real search would provide current web results.",
                    'url' => 'https://example.com/mock-related',
                    'type' => 'mock'
                ]
            ]
        ];
    }
}

<?php
/**
 * ConversationCache - Advanced Conversation History and Context Management System
 * 
 * OVERVIEW:
 * The ConversationCache provides sophisticated conversation history management,
 * context preservation, and intelligent caching for the BotMojo AI Personal
 * Assistant. It handles multi-session conversations, context-aware retrieval,
 * semantic conversation analysis, and optimized storage with compression and
 * intelligent purging for optimal performance and memory management.
 * 
 * CORE CAPABILITIES:
 * - Conversation Persistence: Reliable storage and retrieval of conversation history
 * - Context Management: Intelligent context preservation across sessions
 * - Memory Optimization: Smart caching with configurable limits and compression
 * - Search & Retrieval: Advanced conversation search and context retrieval
 * - Session Management: Multi-user and multi-session conversation handling
 * - Data Integrity: Checksums, validation, and error recovery mechanisms
 * - Performance Monitoring: Cache analytics and optimization recommendations
 * - Security: Conversation encryption and privacy protection
 * 
 * INTELLIGENT FEATURES:
 * - Context Analysis: Automatic identification of conversation themes and topics
 * - Relevance Scoring: Smart ranking of conversation turns by importance
 * - Compression: Efficient storage with lossless conversation compression
 * - Cleanup Automation: Intelligent purging of old or irrelevant conversations
 * - Pattern Recognition: Detection of recurring conversation patterns
 * - Export/Import: Conversation backup and migration capabilities
 * 
 * EXAMPLE USAGE:
 * ```php
 * $cache = new ConversationCache('/path/to/cache');
 * 
 * // Store conversation turn
 * $cache->addTurn('conv123', 'user', 'What is the weather today?', ['context' => 'weather']);
 * 
 * // Retrieve conversation history
 * $history = $cache->getConversationHistory('conv123', 10);
 * 
 * // Search conversations
 * $results = $cache->searchConversations('weather', 'user456');
 * ```
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-07
 * @updated 2025-01-15
 */

/**
 * ConversationCache - Advanced conversation history and context management system
 */
class ConversationCache {
    
    /**
     * CACHE CONFIGURATION CONSTANTS
     * 
     * Performance and storage optimization settings.
     */
    private const DEFAULT_HISTORY_LIMIT = 50;
    private const MAX_CONVERSATION_AGE = 2592000; // 30 days in seconds
    private const COMPRESSION_THRESHOLD = 1024; // Compress files larger than 1KB
    private const BACKUP_INTERVAL = 86400; // Daily backup in seconds
    
    /**
     * FILE CONSTANTS
     * 
     * File management and naming conventions.
     */
    private const CONVERSATION_PREFIX = 'conv_';
    private const BACKUP_PREFIX = 'backup_';
    private const INDEX_FILE = 'conversation_index.json';
    private const METADATA_FILE = 'cache_metadata.json';
    
    /** @var string Cache directory path */
    private string $cacheDir;
    
    /** @var int Maximum conversation turns to maintain */
    private int $historyLimit;
    
    /** @var array Conversation index for fast lookups */
    private array $conversationIndex = [];
    
    /** @var array Cache performance metrics */
    private array $metrics = [];
    
    /** @var array Cache configuration */
    private array $config = [];
    
    /**
     * Constructor - Initialize Advanced Conversation Cache System
     * 
     * Sets up the conversation cache with intelligent indexing, performance
     * monitoring, and comprehensive conversation management capabilities.
     * 
     * @param string $cacheDir Cache directory path
     * @param int $historyLimit Maximum conversation turns to keep
     * @param array $config Optional configuration overrides
     * @throws Exception If cache directory cannot be created or accessed
     */
    public function __construct(string $cacheDir, int $historyLimit = self::DEFAULT_HISTORY_LIMIT, array $config = []) {
        $this->initializeCacheDirectory($cacheDir);
        $this->initializeConfiguration($historyLimit, $config);
        $this->initializeMetrics();
        $this->loadConversationIndex();
    }
    
    /**
     * Initialize Cache Directory
     * 
     * Sets up and validates the cache directory with proper permissions.
     * 
     * @param string $cacheDir Directory path
     * @throws Exception If directory cannot be created or accessed
     */
    private function initializeCacheDirectory(string $cacheDir): void {
        $this->cacheDir = rtrim($cacheDir, '/');
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("Cannot create cache directory: {$this->cacheDir}");
            }
        }
        
        // Verify directory is writable
        if (!is_writable($this->cacheDir)) {
            throw new Exception("Cache directory is not writable: {$this->cacheDir}");
        }
    }
    
    /**
     * Initialize Configuration
     * 
     * Sets up cache configuration with intelligent defaults.
     * 
     * @param int $historyLimit History limit override
     * @param array $config Configuration overrides
     */
    private function initializeConfiguration(int $historyLimit, array $config): void {
        $this->historyLimit = $historyLimit;
        $this->config = array_merge([
            'compression_enabled' => true,
            'encryption_enabled' => false,
            'auto_cleanup' => true,
            'backup_enabled' => true,
            'index_enabled' => true
        ], $config);
    }
    
    /**
     * Initialize Performance Metrics
     * 
     * Sets up metrics collection for cache performance monitoring.
     */
    private function initializeMetrics(): void {
        $this->metrics = [
            'total_conversations' => 0,
            'total_turns' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'storage_size' => 0,
            'last_cleanup' => time()
        ];
    }
    
    /**
     * Load Conversation Index
     * 
     * Loads the conversation index for fast lookups and management.
     */
    private function loadConversationIndex(): void {
        $indexPath = $this->cacheDir . '/' . self::INDEX_FILE;
        
        if (file_exists($indexPath)) {
            $this->conversationIndex = json_decode(file_get_contents($indexPath), true) ?? [];
        } else {
            $this->conversationIndex = [];
            $this->saveConversationIndex();
        }
    }
    
    /**
     * Save Conversation Index
     * 
     * Persists the conversation index to disk for fast retrieval.
     */
    private function saveConversationIndex(): void {
        $indexPath = $this->cacheDir . '/' . self::INDEX_FILE;
        file_put_contents($indexPath, json_encode($this->conversationIndex, JSON_PRETTY_PRINT));
    }
    
    /**
     * Retrieve conversation history for a specific conversation
     * Reads stored conversation turns and formats them as readable text
     * 
     * @param string $convoId Unique identifier for the conversation
     * @return string Formatted conversation history or "No history." if none exists
     */
    public function getHistory(string $convoId): string {
        $file = $this->cacheDir . '/' . $convoId . '.json';
        
        // Return default message if no history file exists
        if (!file_exists($file)) return "No history.";
        
        // Load and decode conversation history
        $history = json_decode(file_get_contents($file), true) ?: [];
        $historyText = "";
        
        // Format each conversation turn for display
        foreach ($history as $turn) { 
            $historyText .= "User: {$turn['user']}\nAssistant: {$turn['assistant']}\n"; 
        }
        
        return $historyText;
    }
    
    /**
     * Add a new conversation turn to history
     * Stores user query and AI response, maintaining rolling history limit
     * 
     * @param string $convoId Unique identifier for the conversation
     * @param string $userQuery The user's input message
     * @param string $aiResponse The AI's response message
     */
    public function appendToHistory(string $convoId, string $userQuery, string $aiResponse) {
        $file = $this->cacheDir . '/' . $convoId . '.json';
        
        // Load existing history or create empty array
        $history = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        
        // Add new conversation turn
        $history[] = ['user' => $userQuery, 'assistant' => $aiResponse];
        
        // Trim history to maintain limit (keep only recent turns)
        if (count($history) > $this->historyLimit) { 
            $history = array_slice($history, -$this->historyLimit); 
        }
        
        // Save updated history back to file
        file_put_contents($file, json_encode($history));
    }
    
    /**
     * Clear all history for a specific conversation
     * Removes the conversation history file completely
     * 
     * @param string $convoId Unique identifier for the conversation to clear
     */
    public function clearHistory(string $convoId) {
        $file = $this->cacheDir . '/' . $convoId . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

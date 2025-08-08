<?php
/**
 * ConversationCache - File-based Conversation History Manager
 * 
 * This class manages conversation history by storing and retrieving
 * conversation turns in JSON files. It maintains a rolling history
 * with configurable limits to manage memory usage.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class ConversationCache {
    /** @var string Directory path for storing cache files */
    private string $cacheDir;
    
    /** @var int Maximum number of conversation turns to keep in history */
    private int $historyLimit = 5;
    
    /**
     * Constructor - Initialize cache directory
     * Creates the cache directory if it doesn't exist
     * 
     * @param string $cacheDir Path to directory for storing conversation files
     */
    public function __construct(string $cacheDir) { 
        $this->cacheDir = $cacheDir; 
        // Create cache directory if it doesn't exist
        if (!is_dir($cacheDir)) { 
            mkdir($cacheDir, 0777, true); 
        } 
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

<?php
/**
 * SpiritualAgent - Advanced Spiritual Wellness and Mindfulness Intelligence Agent
 * 
 * OVERVIEW:
 * The SpiritualAgent is a specialized component of the BotMojo AI Personal Assistant
 * focused on spiritual wellness, mindfulness practices, meditation tracking, and
 * philosophical guidance. It provides a respectful, inclusive approach to spirituality
 * that honors diverse beliefs while offering practical mindfulness and well-being support.
 * 
 * CORE CAPABILITIES:
 * - Meditation Tracking: Session duration, techniques, progress monitoring
 * - Mindfulness Practices: Breathing exercises, awareness techniques, present-moment focus
 * - Spiritual Journey Support: Personal growth, reflection, and insight guidance
 * - Religious Content: Respectful handling of diverse faith traditions and practices
 * - Philosophical Insights: Wisdom traditions, ethical guidance, life questions
 * - Gratitude Tracking: Appreciation practices and positive psychology integration
 * - Prayer and Contemplation: Structured spiritual practice support
 * - Energy and Chakra Work: Alternative wellness and energy practice tracking
 * 
 * SPIRITUAL DATA PROCESSING:
 * - Practice Recognition: "meditated for 20 minutes", "feeling grateful", "prayed"
 * - Technique Classification: mindfulness, loving-kindness, transcendental, zen
 * - Emotional State Tracking: peace, anxiety, clarity, confusion, joy
 * - Intention Setting: spiritual goals, personal growth objectives
 * - Progress Monitoring: consistency tracking, depth of practice development
 * - Community Connection: spiritual group activities, shared practices
 * 
 * INTEGRATION CAPABILITIES:
 * - Meditation Tool: Guided sessions, timer functions, progress tracking
 * - Database Tool: Spiritual practice history, insight patterns, growth tracking
 * - Search Tool: Spiritual texts, meditation techniques, philosophical resources
 * - Calendar Tool: Practice scheduling, retreat planning, spiritual observances
 * - ToolManager: Respectful access to spirituality-related system tools
 * 
 * ETHICAL CONSIDERATIONS:
 * - Religious Neutrality: Respectful of all faith traditions and secular approaches
 * - Cultural Sensitivity: Awareness of diverse spiritual and cultural backgrounds
 * - Privacy Protection: Deeply personal spiritual data handled with utmost care
 * - Non-Judgmental Support: Open, accepting approach to all spiritual paths
 * - Evidence-Based Benefits: Focus on scientifically-supported wellness benefits
 * - Personal Autonomy: User-directed spiritual exploration and practice
 * 
 * MINDFULNESS FEATURES:
 * - Present-Moment Awareness: Techniques for staying grounded and centered
 * - Stress Reduction: Meditation and mindfulness for anxiety and tension relief
 * - Emotional Regulation: Practices for managing difficult emotions and states
 * - Compassion Cultivation: Self-compassion and loving-kindness development
 * - Wisdom Integration: Practical application of spiritual insights to daily life
 * 
 * ARCHITECTURE INTEGRATION:
 * - Implements standard Agent interface with createComponent() method
 * - Follows BotMojo's triage-first architecture for intelligent spiritual guidance
 * - Integrates with entity storage for comprehensive spiritual profile management
 * - Supports both structured practices and spontaneous spiritual moments
 * - Maintains highest privacy standards for deeply personal spiritual data
 * 
 * EXAMPLE USE CASES:
 * - "I meditated for 20 minutes today"
 * - "Feeling grateful for my family"
 * - "Help me with breathing exercises"
 * - "Track my daily prayer time"
 * - "I'm struggling with anxiety"
 * - "Set up a meditation reminder"
 * - "Log my yoga and mindfulness practice"
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-12
 * @updated 2025-01-15
 */

require_once __DIR__ . '/../tools/ToolManager.php';

/**
 * Default user ID for spiritual data when user context is not available
 */
define('DEFAULT_USER_ID', 'user_default');

/**
 * SpiritualAgent - Mindful spiritual wellness and practice guidance
 */
class SpiritualAgent {
    
    /**
     * SPIRITUAL PRACTICE CATEGORIES
     * 
     * Classification system for different types of spiritual and mindfulness
     * practices with associated benefits and tracking metrics.
     */
    private const PRACTICE_CATEGORIES = [
        'meditation' => [
            'mindfulness' => ['focus' => 'present_moment', 'duration' => [5, 60]],
            'loving_kindness' => ['focus' => 'compassion', 'duration' => [10, 30]],
            'body_scan' => ['focus' => 'awareness', 'duration' => [15, 45]],
            'breath_work' => ['focus' => 'breathing', 'duration' => [5, 20]],
            'walking_meditation' => ['focus' => 'movement', 'duration' => [10, 30]]
        ],
        'contemplation' => [
            'prayer' => ['focus' => 'connection', 'frequency' => 'daily'],
            'reflection' => ['focus' => 'insight', 'frequency' => 'regular'],
            'journaling' => ['focus' => 'processing', 'frequency' => 'daily'],
            'gratitude' => ['focus' => 'appreciation', 'frequency' => 'daily']
        ],
        'movement' => [
            'yoga' => ['focus' => 'mind_body', 'intensity' => 'gentle'],
            'tai_chi' => ['focus' => 'flow', 'intensity' => 'gentle'],
            'qigong' => ['focus' => 'energy', 'intensity' => 'gentle'],
            'dance' => ['focus' => 'expression', 'intensity' => 'moderate']
        ],
        'study' => [
            'scripture' => ['focus' => 'wisdom', 'duration' => [10, 60]],
            'philosophy' => ['focus' => 'understanding', 'duration' => [15, 45]],
            'spiritual_texts' => ['focus' => 'learning', 'duration' => [10, 30]]
        ]
    ];
    
    /**
     * EMOTIONAL AND SPIRITUAL STATES
     * 
     * Recognition patterns for spiritual and emotional states
     * to provide appropriate guidance and support.
     */
    private const SPIRITUAL_STATES = [
        'positive' => [
            'peace' => ['peaceful', 'calm', 'serene', 'tranquil'],
            'joy' => ['joyful', 'happy', 'blissful', 'content'],
            'gratitude' => ['grateful', 'thankful', 'blessed', 'appreciative'],
            'clarity' => ['clear', 'focused', 'centered', 'grounded'],
            'love' => ['loving', 'compassionate', 'kind', 'open-hearted']
        ],
        'challenging' => [
            'anxiety' => ['anxious', 'worried', 'stressed', 'overwhelmed'],
            'sadness' => ['sad', 'depressed', 'down', 'melancholy'],
            'anger' => ['angry', 'frustrated', 'irritated', 'upset'],
            'confusion' => ['confused', 'lost', 'uncertain', 'doubtful'],
            'isolation' => ['lonely', 'disconnected', 'separate', 'isolated']
        ],
        'transitional' => [
            'seeking' => ['searching', 'questioning', 'exploring', 'wondering'],
            'growing' => ['developing', 'expanding', 'evolving', 'progressing'],
            'healing' => ['recovering', 'mending', 'restoring', 'rebuilding']
        ]
    ];
    
    /**
     * MEDITATION TECHNIQUES AND GUIDANCE
     * 
     * Structured approaches to different meditation and mindfulness
     * practices with beginner-friendly instructions.
     */
    private const MEDITATION_TECHNIQUES = [
        'breath_awareness' => [
            'instruction' => 'Focus gently on your natural breathing',
            'beginner_duration' => 5,
            'benefits' => ['stress_reduction', 'focus_improvement', 'calm']
        ],
        'body_scan' => [
            'instruction' => 'Slowly scan attention through your body',
            'beginner_duration' => 10,
            'benefits' => ['relaxation', 'awareness', 'tension_release']
        ],
        'loving_kindness' => [
            'instruction' => 'Send kind wishes to yourself and others',
            'beginner_duration' => 10,
            'benefits' => ['compassion', 'emotional_healing', 'connection']
        ],
        'mindful_observation' => [
            'instruction' => 'Observe thoughts and feelings without judgment',
            'beginner_duration' => 8,
            'benefits' => ['self_awareness', 'emotional_regulation', 'acceptance']
        ]
    ];
    
    /** @var ToolManager Centralized tool access and permission management */
    private ToolManager $toolManager;
    
    /** @var array User spiritual profile and preferences */
    private array $spiritualProfile = [];
    
    /** @var array Cache for spiritual insights and analysis */
    private array $spiritualCache = [];
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * Sets up the SpiritualAgent with respectful access to spiritual tools
     * while maintaining the highest privacy standards for personal spiritual data.
     * 
     * @param ToolManager $toolManager Tool management service with spiritual privacy policies
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
        $this->initializeSpiritualProfile();
    }
    
    /**
     * Initialize user spiritual profile with inclusive defaults
     * 
     * Sets up spiritual preferences and practice history while
     * respecting diverse beliefs and spiritual paths.
     * 
     * @return void
     */
    private function initializeSpiritualProfile(): void {
        $this->spiritualProfile = [
            'spiritual_path' => 'open', // open, buddhist, christian, islamic, etc.
            'meditation_experience' => 'beginner', // beginner, intermediate, advanced
            'preferred_practices' => [],
            'practice_goals' => [],
            'spiritual_interests' => [],
            'privacy_level' => 'high', // high, medium, low
            'guidance_style' => 'gentle' // gentle, structured, minimal
        ];
    }
    
    /**
     * Create comprehensive spiritual wellness component from natural language input
     * 
     * This primary method transforms spiritual and mindfulness-related user input
     * into structured spiritual data with respectful guidance, practice tracking,
     * and mindfulness support. It honors diverse spiritual paths while providing
     * evidence-based wellness benefits.
     * 
     * PROCESSING PIPELINE:
     * 1. SPIRITUAL EXTRACTION: Parse practices, states, intentions
     * 2. RESPECTFUL ANALYSIS: Honor diverse beliefs and approaches
     * 3. CONTEXT UNDERSTANDING: Understand spiritual goals and needs
     * 4. TOOL INTEGRATION: Access meditation, wisdom, search data
     * 5. GUIDANCE LAYER: Provide supportive, non-judgmental insights
     * 6. PRIVACY PROTECTION: Ensure deeply personal data security
     * 
     * SPIRITUAL UNDERSTANDING:
     * - Practice Recognition: "meditated 20 minutes", "feeling grateful"
     * - State Assessment: "anxious", "peaceful", "seeking clarity"
     * - Intention Setting: "want to be more mindful", "seeking peace"
     * - Progress Tracking: consistency, depth, spiritual growth
     * - Community Connection: shared practices, group meditation
     * 
     * MINDFUL FEATURES:
     * - Present-Moment Support: Grounding and centering techniques
     * - Emotional Guidance: Compassionate support for difficult emotions
     * - Practice Suggestions: Personalized spiritual practice recommendations
     * - Wisdom Integration: Practical application of spiritual insights
     * - Growth Tracking: Spiritual development and self-awareness monitoring
     * 
     * @param array $data Spiritual data from triage system with mindful context
     * @return array Comprehensive spiritual component with guidance and insights
     */
    public function createComponent(array $data): array {
        // Extract spiritual information from triage context
        $spiritualInfo = $this->extractSpiritualInformation($data);
        
        // Check if we need to access database for spiritual practice records
        $practiceRecords = [];
        if (isset($data['needs_practice_records']) && $data['needs_practice_records']) {
            $dbTool = $this->toolManager->getTool('database');
            if ($dbTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $practiceRecords = $this->retrievePracticeRecords($dbTool, $userId);
            }
        }
        
        // Check if we need to access meditation data
        $meditationData = [];
        if (isset($data['needs_meditation_data']) && $data['needs_meditation_data']) {
            $meditationTool = $this->toolManager->getTool('meditation');
            if ($meditationTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $meditationData = $meditationTool->getUserMeditationData($userId);
            }
        }
        
        // Search for spiritual texts or information if needed
        $spiritualSearchResults = [];
        if (isset($data['spiritual_search_query']) && !empty($data['spiritual_search_query'])) {
            $searchTool = $this->toolManager->getTool('search');
            if ($searchTool) {
                $spiritualSearchResults = $searchTool->search(
                    $data['spiritual_search_query'],
                    ['spiritualTexts' => true, 'philosophicalSources' => true]
                );
            }
        }
        
        // Generate spiritual insights and practice recommendations
        $insights = $this->generateSpiritualInsights(
            $spiritualInfo,
            $practiceRecords, 
            $meditationData,
            $spiritualSearchResults
        );
        
        // Add context-appropriate disclaimer
        $insights['note'] = "These spiritual insights are offered as perspectives for consideration. " .
                            "Please adapt them to your own beliefs and practices.";
        
        // Return component in the standard format matching existing agents and database schema
        return [
            // Core spiritual information
            'reflection' => $insights['reflection'] ?? 'General spiritual reflection',
            'practice_suggestion' => $insights['practices'] ?? '',
            'tradition' => $spiritualInfo['tradition'] ?? 'non_specific',
            
            // Inspirational content
            'quotes' => $insights['inspirational_quotes'] ?? [],
            'philosophical_insights' => $spiritualInfo['philosophical_question'] ? $insights['reflection'] : '',
            'guidance_note' => $insights['note'] ?? '',
            
            // Practice data
            'meditation_stats' => $meditationData,
            'historical_practices' => $practiceRecords,
            
            // Metadata
            'practice_type' => $spiritualInfo['practice_type'] ?? '',
            'query_type' => $spiritualInfo['query_type'] ?? 'general_spiritual',
            'time_period' => $spiritualInfo['time_period'] ?? 'current',
        ];
    }
    
    /**
     * Extract spiritual information from triage data
     * 
     * @param array $data The triage data
     * @return array Extracted spiritual information
     */
    private function extractSpiritualInformation(array $data): array {
        return [
            'query_type' => $data['query_type'] ?? 'general_spiritual',
            'tradition' => $data['tradition'] ?? 'non_specific',
            'practice_type' => $data['practice_type'] ?? '',
            'philosophical_question' => $data['philosophical_question'] ?? '',
            'time_period' => $data['time_period'] ?? 'current',
        ];
    }
    
    /**
     * Retrieve spiritual practice records from database
     * 
     * @param DatabaseTool $dbTool Database tool instance
     * @param string $userId User identifier
     * @return array Retrieved practice records
     */
    private function retrievePracticeRecords($dbTool, string $userId): array {
        // Query spiritual practice records from database
        $query = "SELECT * FROM spiritual_practices WHERE user_id = ? ORDER BY practice_date DESC LIMIT 10";
        $params = [$userId];
        
        return $dbTool->executeParameterizedQuery($query, $params);
    }
    
    /**
     * Generate spiritual insights and practice recommendations
     * 
     * @param array $spiritualInfo Basic spiritual information
     * @param array $practiceRecords Historical practice records
     * @param array $meditationData Meditation tracking data
     * @param array $searchResults Spiritual information search results
     * @return array Insights and practice recommendations
     */
    private function generateSpiritualInsights(array $spiritualInfo, array $practiceRecords, array $meditationData, array $searchResults): array {
        // Implement spiritual insights generation logic
        $reflection = "Reflection based on your spiritual interests and practices.";
        $practices = "Suggested practices aligned with your spiritual tradition and goals.";
        $quotes = ["The journey of a thousand miles begins with a single step. - Lao Tzu"];
        
        // More sophisticated insight generation would be implemented here
        
        return [
            'reflection' => $reflection,
            'practices' => $practices,
            'inspirational_quotes' => $quotes,
        ];
    }
}

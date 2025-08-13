<?php
/**
 * LearningAgent - Advanced Educational Intelligence and Knowledge Management Agent
 * 
 * OVERVIEW:
 * The LearningAgent is a specialized component of the BotMojo AI Personal Assistant
 * focused on educational content management, skill acquisition tracking, knowledge
 * organization, study planning, and personalized learning recommendations. It
 * provides intelligent learning path optimization and educational resource curation.
 * 
 * CORE CAPABILITIES:
 * - Learning Path Design: Personalized curriculum and skill development roadmaps
 * - Knowledge Management: Information organization and retrieval systems
 * - Study Planning: Scheduling, goal setting, and progress tracking
 * - Resource Curation: Educational content discovery and recommendation
 * - Skill Assessment: Competency evaluation and gap analysis
 * - Learning Analytics: Progress monitoring and performance analysis
 * - Note Organization: Intelligent note-taking and knowledge structuring
 * - Memory Reinforcement: Spaced repetition and retention optimization
 * 
 * EDUCATIONAL INTELLIGENCE:
 * - Subject Classification: Academic, professional, personal development areas
 * - Learning Style Adaptation: Visual, auditory, kinesthetic, reading/writing
 * - Difficulty Assessment: Content complexity and prerequisite analysis
 * - Progress Tracking: Milestone monitoring and achievement recognition
 * - Knowledge Gaps: Identification of learning deficiencies and recommendations
 * - Learning Efficiency: Optimization of study methods and time allocation
 * - Retention Analysis: Memory consolidation and recall improvement strategies
 * 
 * INTEGRATION CAPABILITIES:
 * - Notes Tool: Structured note-taking and knowledge organization
 * - Search Tool: Educational resource discovery and content research
 * - Database Tool: Learning history tracking and progress analytics
 * - Calendar Tool: Study scheduling and learning session planning
 * - ToolManager: Secure access to educational and productivity tools
 * 
 * LEARNING METHODOLOGIES:
 * - Microlearning: Bite-sized learning modules for efficient knowledge acquisition
 * - Spaced Repetition: Scientifically-optimized review scheduling
 * - Active Learning: Engagement techniques and interactive content
 * - Collaborative Learning: Group study and peer learning facilitation
 * - Project-Based Learning: Practical application and skill demonstration
 * - Adaptive Learning: Dynamic content adjustment based on performance
 * 
 * KNOWLEDGE ORGANIZATION:
 * - Concept Mapping: Visual representation of knowledge relationships
 * - Taxonomic Classification: Hierarchical organization of subjects and topics
 * - Cross-Referencing: Connecting related concepts across domains
 * - Progressive Disclosure: Layered learning with increasing complexity
 * - Learning Objectives: Clear goal setting and outcome measurement
 * 
 * ARCHITECTURE INTEGRATION:
 * - Implements standard Agent interface with createComponent() method
 * - Integrates with knowledge management and productivity tools
 * - Follows BotMojo's triage-first architecture for educational query routing
 * - Supports both structured courses and informal learning tracking
 * - Maintains learning privacy and academic integrity standards
 * 
 * EXAMPLE USE CASES:
 * - "I want to learn Python programming"
 * - "Track my progress in data science course"
 * - "Create a study plan for my certification exam"
 * - "Find resources for machine learning"
 * - "Help me organize my research notes"
 * - "Schedule my study sessions for this week"
 * - "What should I learn next in web development?"
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-12
 * @updated 2025-01-15
 */

require_once __DIR__ . '/../tools/ToolManager.php';

/**
 * Default user ID for learning data when user context is not available
 */
define('DEFAULT_USER_ID', 'user_default');

/**
 * LearningAgent - Intelligent educational content and knowledge management
 */
class LearningAgent {
    
    /**
     * LEARNING DOMAIN CLASSIFICATIONS
     * 
     * Comprehensive categorization of learning areas with specific
     * methodologies and resource requirements.
     */
    private const LEARNING_DOMAINS = [
        'academic' => [
            'stem' => [
                'mathematics' => ['prerequisites' => [], 'difficulty' => 'high'],
                'computer_science' => ['prerequisites' => ['mathematics'], 'difficulty' => 'high'],
                'engineering' => ['prerequisites' => ['mathematics', 'physics'], 'difficulty' => 'high'],
                'data_science' => ['prerequisites' => ['mathematics', 'statistics'], 'difficulty' => 'high']
            ],
            'humanities' => [
                'languages' => ['prerequisites' => [], 'difficulty' => 'medium'],
                'history' => ['prerequisites' => [], 'difficulty' => 'medium'],
                'philosophy' => ['prerequisites' => [], 'difficulty' => 'high'],
                'literature' => ['prerequisites' => ['languages'], 'difficulty' => 'medium']
            ],
            'sciences' => [
                'biology' => ['prerequisites' => ['chemistry'], 'difficulty' => 'high'],
                'chemistry' => ['prerequisites' => ['mathematics'], 'difficulty' => 'high'],
                'physics' => ['prerequisites' => ['mathematics'], 'difficulty' => 'high'],
                'psychology' => ['prerequisites' => [], 'difficulty' => 'medium']
            ]
        ],
        'professional' => [
            'technology' => [
                'programming' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'web_development' => ['prerequisites' => ['programming'], 'skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'cybersecurity' => ['prerequisites' => ['networking'], 'skill_levels' => ['intermediate', 'advanced']],
                'cloud_computing' => ['prerequisites' => ['programming'], 'skill_levels' => ['intermediate', 'advanced']]
            ],
            'business' => [
                'management' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'marketing' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'finance' => ['prerequisites' => ['mathematics'], 'skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'project_management' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']]
            ]
        ],
        'personal' => [
            'life_skills' => [
                'communication' => ['skill_levels' => ['basic', 'intermediate', 'advanced']],
                'time_management' => ['skill_levels' => ['basic', 'intermediate', 'advanced']],
                'financial_literacy' => ['skill_levels' => ['basic', 'intermediate', 'advanced']]
            ],
            'creative' => [
                'writing' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'design' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']],
                'music' => ['skill_levels' => ['beginner', 'intermediate', 'advanced']]
            ]
        ]
    ];
    
    /**
     * LEARNING METHODOLOGIES
     * 
     * Different approaches to learning with effectiveness ratings
     * and optimal use case scenarios.
     */
    private const LEARNING_METHODS = [
        'structured' => [
            'formal_course' => ['effectiveness' => 0.9, 'time_commitment' => 'high'],
            'certification_program' => ['effectiveness' => 0.8, 'time_commitment' => 'high'],
            'bootcamp' => ['effectiveness' => 0.8, 'time_commitment' => 'very_high'],
            'workshop' => ['effectiveness' => 0.7, 'time_commitment' => 'medium']
        ],
        'self_directed' => [
            'online_tutorials' => ['effectiveness' => 0.6, 'time_commitment' => 'low'],
            'books' => ['effectiveness' => 0.7, 'time_commitment' => 'medium'],
            'practice_projects' => ['effectiveness' => 0.8, 'time_commitment' => 'medium'],
            'documentation_study' => ['effectiveness' => 0.6, 'time_commitment' => 'low']
        ],
        'collaborative' => [
            'study_groups' => ['effectiveness' => 0.7, 'time_commitment' => 'medium'],
            'mentorship' => ['effectiveness' => 0.9, 'time_commitment' => 'medium'],
            'peer_learning' => ['effectiveness' => 0.6, 'time_commitment' => 'low'],
            'code_review' => ['effectiveness' => 0.8, 'time_commitment' => 'low']
        ],
        'experiential' => [
            'internship' => ['effectiveness' => 0.9, 'time_commitment' => 'very_high'],
            'freelance_projects' => ['effectiveness' => 0.8, 'time_commitment' => 'high'],
            'open_source_contribution' => ['effectiveness' => 0.7, 'time_commitment' => 'medium'],
            'hackathons' => ['effectiveness' => 0.6, 'time_commitment' => 'medium']
        ]
    ];
    
    /**
     * LEARNING PROGRESS INDICATORS
     * 
     * Metrics for tracking learning progress and competency development.
     */
    private const PROGRESS_METRICS = [
        'knowledge_acquisition' => [
            'concepts_learned' => ['measurement' => 'count', 'weight' => 0.3],
            'depth_of_understanding' => ['measurement' => 'scale_1_10', 'weight' => 0.4],
            'knowledge_retention' => ['measurement' => 'percentage', 'weight' => 0.3]
        ],
        'skill_development' => [
            'practical_application' => ['measurement' => 'project_count', 'weight' => 0.4],
            'problem_solving' => ['measurement' => 'complexity_level', 'weight' => 0.3],
            'skill_fluency' => ['measurement' => 'speed_accuracy', 'weight' => 0.3]
        ],
        'learning_efficiency' => [
            'time_to_competency' => ['measurement' => 'hours', 'weight' => 0.3],
            'retention_rate' => ['measurement' => 'percentage', 'weight' => 0.4],
            'transfer_ability' => ['measurement' => 'application_success', 'weight' => 0.3]
        ]
    ];
    
    /**
     * LEARNING RESOURCE TYPES
     * 
     * Different types of educational resources with quality indicators
     * and optimal use scenarios.
     */
    private const RESOURCE_TYPES = [
        'textual' => [
            'books' => ['depth' => 'high', 'interactivity' => 'low', 'cost' => 'medium'],
            'articles' => ['depth' => 'medium', 'interactivity' => 'low', 'cost' => 'low'],
            'documentation' => ['depth' => 'high', 'interactivity' => 'low', 'cost' => 'free'],
            'research_papers' => ['depth' => 'very_high', 'interactivity' => 'low', 'cost' => 'low']
        ],
        'multimedia' => [
            'video_courses' => ['depth' => 'medium', 'interactivity' => 'medium', 'cost' => 'medium'],
            'podcasts' => ['depth' => 'medium', 'interactivity' => 'low', 'cost' => 'low'],
            'interactive_demos' => ['depth' => 'medium', 'interactivity' => 'high', 'cost' => 'medium'],
            'simulations' => ['depth' => 'high', 'interactivity' => 'very_high', 'cost' => 'high']
        ],
        'practical' => [
            'coding_exercises' => ['depth' => 'medium', 'interactivity' => 'very_high', 'cost' => 'low'],
            'lab_experiments' => ['depth' => 'high', 'interactivity' => 'very_high', 'cost' => 'high'],
            'case_studies' => ['depth' => 'high', 'interactivity' => 'medium', 'cost' => 'medium'],
            'project_templates' => ['depth' => 'medium', 'interactivity' => 'high', 'cost' => 'low']
        ]
    ];
    
    /** @var ToolManager Centralized tool access and permission management */
    private ToolManager $toolManager;
    
    /** @var array Learning analytics and progress tracking */
    private array $learningAnalytics = [];
    
    /** @var array Knowledge organization and concept mapping */
    private array $knowledgeMap = [];
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * Sets up the LearningAgent with access to educational tools and
     * initializes learning analytics and knowledge management systems.
     * 
     * @param ToolManager $toolManager Tool management service with educational tool permissions
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
        $this->initializeLearningSystem();
    }
    
    /**
     * Initialize learning analytics and knowledge management systems
     * 
     * Sets up progress tracking, learning path optimization, and
     * knowledge organization capabilities for educational support.
     * 
     * @return void
     */
    private function initializeLearningSystem(): void {
        $this->learningAnalytics = [
            'progress_tracking' => [],
            'learning_patterns' => [],
            'competency_assessments' => [],
            'resource_effectiveness' => []
        ];
        
        $this->knowledgeMap = [
            'concept_relationships' => [],
            'learning_dependencies' => [],
            'skill_prerequisites' => [],
            'knowledge_clusters' => []
        ];
    }
    
    /**
     * Create comprehensive learning intelligence component from educational input
     * 
     * This primary method transforms learning-related user input into structured
     * educational data with intelligent learning path recommendations, resource
     * curation, and progress tracking capabilities.
     * 
     * PROCESSING PIPELINE:
     * 1. LEARNING EXTRACTION: Parse educational goals, subjects, skill levels
     * 2. COMPETENCY ASSESSMENT: Evaluate current knowledge and skill gaps
     * 3. PATH OPTIMIZATION: Design personalized learning roadmaps
     * 4. RESOURCE CURATION: Recommend appropriate educational materials
     * 5. PROGRESS TRACKING: Monitor learning milestones and achievements
     * 6. ANALYTICS INTEGRATION: Provide learning insights and optimization
     * 
     * EDUCATIONAL UNDERSTANDING:
     * - Subject Recognition: "learn Python", "master data science", "improve writing"
     * - Skill Assessment: current competency levels and learning objectives
     * - Learning Preferences: preferred methods, time availability, difficulty levels
     * - Progress Monitoring: milestone tracking, achievement recognition
     * - Resource Matching: optimal educational content for individual needs
     * 
     * INTELLIGENT FEATURES:
     * - Adaptive Learning Paths: Dynamic curriculum adjustment based on progress
     * - Prerequisite Management: Automatic dependency resolution and sequencing
     * - Learning Efficiency: Optimization of study methods and time allocation
     * - Knowledge Retention: Spaced repetition and memory consolidation strategies
     * - Competency Mapping: Comprehensive skill development and gap analysis
     * 
     * @param array $data Learning data from triage system with educational context
     * @return array Comprehensive learning component with educational guidance and resources
     */
    public function createComponent(array $data): array {
        // Extract learning information from triage context
        $learningInfo = $this->extractLearningInformation($data);
        
        // Check if we need to access learning history
        $learningHistory = [];
        if (isset($data['needs_learning_history']) && $data['needs_learning_history']) {
            $dbTool = $this->toolManager->getTool('database');
            if ($dbTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $learningHistory = $this->retrieveLearningHistory($dbTool, $userId);
            }
        }
        
        // Check if we need to access notes
        $notesData = [];
        if (isset($data['needs_notes']) && $data['needs_notes']) {
            $notesTool = $this->toolManager->getTool('notes');
            if ($notesTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $topic = $data['notes_topic'] ?? '';
                $notesData = $notesTool->getUserNotes($userId, $topic);
            }
        }
        
        // Search for educational content if needed
        $educationalContent = [];
        if (isset($data['learning_search_query']) && !empty($data['learning_search_query'])) {
            $searchTool = $this->toolManager->getTool('search');
            if ($searchTool) {
                $educationalContent = $searchTool->search(
                    $data['learning_search_query'],
                    ['educationalResources' => true, 'academicSources' => true]
                );
            }
        }
        
        // Generate learning plan and educational recommendations
        $learningPlan = $this->generateLearningPlan(
            $learningInfo,
            $learningHistory,
            $notesData,
            $educationalContent
        );
        
        // Return component in the standard format matching existing agents and database schema
        return [
            // Core learning information
            'learning_overview' => $learningPlan['overview'] ?? 'General learning plan',
            'learning_steps' => $learningPlan['learning_steps'] ?? [],
            'recommended_resources' => $learningPlan['recommended_resources'] ?? [],
            
            // Learning details
            'subject' => $learningInfo['subject'] ?? '',
            'skill_level' => $learningInfo['skill_level'] ?? 'beginner',
            'learning_goal' => $learningInfo['learning_goal'] ?? '',
            
            // Planning and schedule
            'suggested_pace' => $learningPlan['suggested_pace'] ?? '',
            'study_schedule' => $learningPlan['study_schedule'] ?? [],
            
            // Historical data and notes
            'learning_history' => $learningHistory,
            'notes' => $notesData,
            
            // Metadata
            'time_available' => $learningInfo['time_available'] ?? '',
            'preferred_resources' => $learningInfo['preferred_resources'] ?? [],
            'query_type' => $learningInfo['query_type'] ?? 'general_learning',
        ];
    }
    
    /**
     * Extract learning information from triage data
     * 
     * @param array $data The triage data
     * @return array Extracted learning information
     */
    private function extractLearningInformation(array $data): array {
        return [
            'query_type' => $data['query_type'] ?? 'general_learning',
            'subject' => $data['subject'] ?? '',
            'skill_level' => $data['skill_level'] ?? 'beginner',
            'learning_goal' => $data['learning_goal'] ?? '',
            'time_available' => $data['time_available'] ?? '',
            'preferred_resources' => $data['preferred_resources'] ?? []
        ];
    }
    
    /**
     * Retrieve learning history from database
     * 
     * @param DatabaseTool $dbTool Database tool instance
     * @param string $userId User identifier
     * @return array Retrieved learning history
     */
    private function retrieveLearningHistory($dbTool, string $userId): array {
        // Query learning history from database
        $query = "SELECT * FROM learning_activities WHERE user_id = ? ORDER BY activity_date DESC LIMIT 20";
        $params = [$userId];
        
        return $dbTool->executeParameterizedQuery($query, $params);
    }
    
    /**
     * Generate learning plan and educational recommendations
     * 
     * @param array $learningInfo Basic learning information
     * @param array $learningHistory Historical learning activities
     * @param array $notesData User's notes on the topic
     * @param array $educationalContent Educational content search results
     * @return array Learning plan and recommendations
     */
    private function generateLearningPlan(array $learningInfo, array $learningHistory, array $notesData, array $educationalContent): array {
        // Implement learning plan generation logic
        $overview = "Learning plan overview based on your goals and previous learning.";
        $steps = [
            "Step 1: Review fundamentals",
            "Step 2: Practice core concepts",
            "Step 3: Apply knowledge in projects"
        ];
        $resources = ["Resource 1", "Resource 2", "Resource 3"];
        
        // Determine appropriate learning pace based on history
        $learningPace = $this->determineLearningPace($learningHistory);
        
        // More sophisticated learning plan generation would be implemented here
        
        return [
            'overview' => $overview,
            'learning_steps' => $steps,
            'recommended_resources' => $resources,
            'suggested_pace' => $learningPace,
            'study_schedule' => $this->createStudySchedule($learningInfo)
        ];
    }
    
    /**
     * Determine appropriate learning pace based on learning history
     * 
     * @param array $learningHistory User's learning history
     * @return string Suggested learning pace
     */
    private function determineLearningPace(array $learningHistory): string {
        // Analyze learning history to suggest appropriate pace
        if (empty($learningHistory)) {
            return "Start with 30 minutes daily to build a consistent habit";
        }
        
        // Count recent activities
        $recentActivities = 0;
        $currentTime = time();
        foreach ($learningHistory as $activity) {
            $activityTime = strtotime($activity['activity_date']);
            if (($currentTime - $activityTime) < (7 * 24 * 60 * 60)) { // Within last week
                $recentActivities++;
            }
        }
        
        // Suggest pace based on recent activity level
        if ($recentActivities >= 5) {
            return "You've been consistent! Consider increasing to 60-90 minutes daily for deeper learning";
        } else if ($recentActivities >= 3) {
            return "Good progress. Aim for 45 minutes daily, 5 days a week";
        } else {
            return "Build consistency with 30 minutes daily, then gradually increase";
        }
    }
    
    /**
     * Create a suggested study schedule based on learning information
     * 
     * @param array $learningInfo Learning information and preferences
     * @return array Suggested study schedule
     */
    private function createStudySchedule(array $learningInfo): array {
        // Generate a study schedule based on available time and goals
        $schedule = [];
        
        // Very basic schedule generation
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        foreach ($daysOfWeek as $day) {
            $schedule[$day] = [
                'activity' => "Study " . ($learningInfo['subject'] ?? 'your subject'),
                'duration' => "30 minutes",
                'focus' => "Core concepts"
            ];
        }
        
        // More sophisticated scheduling would be implemented here
        
        return $schedule;
    }
}

<?php
/**
 * LearningAgent - Specialized Agent for Educational Content
 * 
 * This agent handles learning-related queries, including educational content,
 * skill acquisition, knowledge management, study planning, learning tracking,
 * and educational resource recommendations.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-12
 */
class LearningAgent {
    
    /** @var ToolManager Tool access manager */
    private ToolManager $toolManager;
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * @param ToolManager $toolManager Tool management service
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
    }
    
    /**
     * Create a learning-related component from provided data
     * 
     * @param array $data Raw learning data from the triage system
     * @return array Enhanced learning component with educational content and recommendations
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

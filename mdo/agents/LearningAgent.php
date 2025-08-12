<?php
class LearningAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a learning or educational question';
        $toolResults = $task['tool_results'] ?? [];
        
        $response = [
            "message" => "LearningAgent: Processing learning request about '{$queryPart}'."
        ];
        
        // Process tool results if available
        if (!empty($toolResults)) {
            // Process notes data if available
            if (isset($toolResults['notes'])) {
                $notesData = $toolResults['notes'];
                $response['notes_insight'] = "Here are your learning notes: " . json_encode($notesData);
                
                // Add specific analysis for notes
                if (is_array($notesData)) {
                    $noteCount = count($notesData);
                    $response['notes_summary'] = "You have {$noteCount} notes on this topic.";
                    
                    // Extract topics from notes
                    $topics = [];
                    foreach ($notesData as $note) {
                        if (isset($note['topic'])) {
                            $topics[$note['topic']] = ($topics[$note['topic']] ?? 0) + 1;
                        }
                    }
                    
                    if (!empty($topics)) {
                        $response['topic_breakdown'] = "Your notes cover these topics: " . 
                                                      implode(", ", array_map(
                                                          function($topic, $count) { 
                                                              return "{$topic} ({$count})"; 
                                                          }, 
                                                          array_keys($topics), 
                                                          array_values($topics)
                                                      ));
                    }
                }
            }
            
            // Process calendar data for learning schedules
            if (isset($toolResults['calendar'])) {
                $response['learning_schedule'] = "Your learning schedule: " . json_encode($toolResults['calendar']);
            }
            
            // Process database results for learning history
            if (isset($toolResults['database'])) {
                $response['learning_history'] = "Retrieved your learning history: " . json_encode($toolResults['database']);
            }
            
            // Process search results for educational content
            if (isset($toolResults['search']) && isset($toolResults['search']['results'])) {
                $response['educational_content'] = "Educational content found: " . json_encode($toolResults['search']['results']);
            }
            
            // Store any other tool results
            foreach ($toolResults as $toolName => $result) {
                if (!in_array($toolName, ['notes', 'calendar', 'database', 'search'])) {
                    $response['tool_data'][$toolName] = $result;
                }
            }
        }
        
        // Generate learning component
        $subject = $task['parameters']['subject'] ?? '';
        $skillLevel = $task['parameters']['skill_level'] ?? 'beginner';
        $learningGoal = $task['parameters']['learning_goal'] ?? '';
        
        $learningSteps = [
            "Step 1: Review fundamentals and build a solid foundation",
            "Step 2: Practice core concepts through exercises and applications",
            "Step 3: Test understanding by teaching concepts to others or creating projects"
        ];
        
        $resources = [
            "beginner" => ["Introductory textbooks", "Basic online tutorials", "Foundation courses"],
            "intermediate" => ["Practice exercises", "Project-based learning", "Community forums"],
            "advanced" => ["Research papers", "Advanced case studies", "Expert discussions"]
        ];
        
        $response['learning_component'] = [
            'learning_overview' => $subject ? "Learning plan for {$subject}" : "General learning approach",
            'learning_steps' => $learningSteps,
            'recommended_resources' => $resources[$skillLevel] ?? $resources['beginner'],
            'subject' => $subject,
            'skill_level' => $skillLevel,
            'learning_goal' => $learningGoal,
            'suggested_pace' => "Start with 30 minutes daily to build consistency, then gradually increase"
        ];
        
        return $response;
    }
}

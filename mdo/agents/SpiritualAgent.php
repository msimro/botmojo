<?php
class SpiritualAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a spiritual or mindfulness question';
        $toolResults = $task['tool_results'] ?? [];
        
        $response = [
            "message" => "SpiritualAgent: Processing spiritual request about '{$queryPart}'."
        ];
        
        // Process tool results if available
        if (!empty($toolResults)) {
            // Process meditation data if available
            if (isset($toolResults['meditation'])) {
                $meditationData = $toolResults['meditation'];
                $response['meditation_insight'] = "Here's your meditation data: " . json_encode($meditationData);
                
                // Add specific analysis for meditation data
                if (isset($meditationData['streak'])) {
                    $response['streak_encouragement'] = "You've maintained a meditation streak of {$meditationData['streak']} days. " . 
                                                       ($meditationData['streak'] > 5 ? 
                                                       "Excellent consistency!" : 
                                                       "Keep going to build a lasting habit.");
                }
                
                if (isset($meditationData['total_minutes'])) {
                    $response['meditation_progress'] = "You've meditated for a total of {$meditationData['total_minutes']} minutes.";
                }
            }
            
            // Process database results for spiritual practice records
            if (isset($toolResults['database'])) {
                $response['spiritual_records'] = "Retrieved your spiritual practice records: " . json_encode($toolResults['database']);
            }
            
            // Process search results for spiritual or philosophical information
            if (isset($toolResults['search']) && isset($toolResults['search']['results'])) {
                $response['spiritual_information'] = "Spiritual information found: " . json_encode($toolResults['search']['results']);
            }
            
            // Store any other tool results
            foreach ($toolResults as $toolName => $result) {
                if (!in_array($toolName, ['meditation', 'database', 'search'])) {
                    $response['tool_data'][$toolName] = $result;
                }
            }
        }
        
        // Generate spiritual component
        $tradition = $task['parameters']['tradition'] ?? 'non_specific';
        $practiceType = $task['parameters']['practice_type'] ?? 'mindfulness';
        
        $quotes = [
            "The present moment is the only moment available to us, and it is the door to all moments. - Thich Nhat Hanh",
            "Peace comes from within. Do not seek it without. - Buddha",
            "The wound is the place where the Light enters you. - Rumi"
        ];
        
        $practices = [
            "mindfulness" => "Focus on your breath for 5-10 minutes each day",
            "loving-kindness" => "Practice sending good wishes to yourself and others",
            "gratitude" => "Write down three things you're grateful for each day",
            "body-scan" => "Progressively relax each part of your body from head to toe"
        ];
        
        $response['spiritual_component'] = [
            'reflection' => "Taking time for spiritual practice helps cultivate inner peace and resilience",
            'practice_suggestion' => $practices[$practiceType] ?? $practices['mindfulness'],
            'tradition' => $tradition,
            'quotes' => $quotes,
            'guidance_note' => "These spiritual insights are offered as perspectives for consideration. Please adapt them to your own beliefs and practices."
        ];
        
        return $response;
    }
}

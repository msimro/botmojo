<?php
class SocialAgent {
    public function execute(array $task) {
        $queryPart = $task['original_query_part'] ?? 'a social or relationship question';
        $toolResults = $task['tool_results'] ?? [];
        
        $response = [
            "message" => "SocialAgent: Processing social request about '{$queryPart}'."
        ];
        
        // Process tool results if available
        if (!empty($toolResults)) {
            // Process contacts data if available
            if (isset($toolResults['contacts'])) {
                $contactsData = $toolResults['contacts'];
                $response['contacts_insight'] = "Here's your contacts data: " . json_encode($contactsData);
                
                // Add specific analysis for contacts
                if (is_array($contactsData)) {
                    $contactCount = count($contactsData);
                    $response['contacts_summary'] = "You have {$contactCount} contacts in your network.";
                    
                    // Example of relationship analysis
                    $relationshipTypes = [];
                    foreach ($contactsData as $contact) {
                        $relationshipType = $contact['relationship'] ?? 'unspecified';
                        $relationshipTypes[$relationshipType] = ($relationshipTypes[$relationshipType] ?? 0) + 1;
                    }
                    
                    $response['relationship_breakdown'] = "Your network includes: " . 
                                                         implode(", ", array_map(
                                                             function($type, $count) { 
                                                                 return "{$count} {$type}" . ($count > 1 ? 's' : ''); 
                                                             }, 
                                                             array_keys($relationshipTypes), 
                                                             array_values($relationshipTypes)
                                                         ));
                }
            }
            
            // Process calendar data for social events
            if (isset($toolResults['calendar'])) {
                $response['social_events'] = "Your upcoming social events: " . json_encode($toolResults['calendar']);
            }
            
            // Process database results for relationship records
            if (isset($toolResults['database'])) {
                $response['relationship_records'] = "Retrieved your relationship records: " . json_encode($toolResults['database']);
            }
            
            // Process search results for social or event information
            if (isset($toolResults['search']) && isset($toolResults['search']['results'])) {
                $response['social_information'] = "Social information found: " . json_encode($toolResults['search']['results']);
            }
            
            // Store any other tool results
            foreach ($toolResults as $toolName => $result) {
                if (!in_array($toolName, ['contacts', 'calendar', 'database', 'search'])) {
                    $response['tool_data'][$toolName] = $result;
                }
            }
        }
        
        // Generate social component
        $communicationTips = [
            "Be an active listener by maintaining eye contact and asking clarifying questions",
            "Express appreciation and gratitude in your relationships regularly",
            "Schedule regular check-ins with important people in your life"
        ];
        
        $relationshipFocus = $task['parameters']['relationship_focus'] ?? '';
        $eventType = $task['parameters']['event_type'] ?? '';
        
        $response['social_component'] = [
            'relationship_insights' => "Maintaining strong social connections contributes significantly to overall wellbeing",
            'communication_tips' => $communicationTips,
            'event_recommendations' => $eventType ? "Consider organizing a {$eventType} to strengthen your social bonds" : 
                                      "Regular social gatherings can help maintain and strengthen your relationships",
            'relationship_focus' => $relationshipFocus,
            'event_type' => $eventType
        ];
        
        return $response;
    }
}

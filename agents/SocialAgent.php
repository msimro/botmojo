<?php
/**
 * SocialAgent - Specialized Agent for Social Interactions
 * 
 * This agent handles social-related queries, including relationship management,
 * social event planning, communication patterns, networking, and 
 * interpersonal dynamics.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-12
 */
class SocialAgent {
    
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
     * Create a social-related component from provided data
     * 
     * @param array $data Raw social data from the triage system
     * @return array Enhanced social component with relationship insights and event planning
     */
    public function createComponent(array $data): array {
        // Extract social information from triage context
        $socialInfo = $this->extractSocialInformation($data);
        
        // Process and save any relationships found in the data
        $this->processAndSaveRelationships($data);
        
        // Check if we need to access contacts database
        $contactsData = [];
        if (isset($data['needs_contacts']) && $data['needs_contacts']) {
            $contactsTool = $this->toolManager->getTool('contacts');
            if ($contactsTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $contactsData = $contactsTool->getUserContacts($userId);
            }
        }
        
        // Check if we need to access calendar for social events
        $socialEvents = [];
        if (isset($data['needs_event_data']) && $data['needs_event_data']) {
            $calendarTool = $this->toolManager->getTool('calendar');
            if ($calendarTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $startDate = $data['event_start_date'] ?? date('Y-m-d');
                $endDate = $data['event_end_date'] ?? date('Y-m-d', strtotime('+30 days'));
                $socialEvents = $calendarTool->getEvents(
                    $userId, 
                    $startDate, 
                    $endDate,
                    ['category' => 'social']
                );
            }
        }
        
        // Generate relationship insights and social recommendations
        $socialAnalysis = $this->analyzeSocialData(
            $socialInfo,
            $contactsData,
            $socialEvents
        );
        
        // Return component in the standard format matching existing agents and database schema
        return [
            // Core social information
            'relationship_insights' => $socialAnalysis['relationship_insights'] ?? 'General social insights',
            'communication_tips' => $socialAnalysis['communication_tips'] ?? [],
            'event_recommendations' => $socialAnalysis['event_recommendations'] ?? '',
            
            // Contact and event data
            'contacts' => $this->sanitizeContactsData($contactsData),
            'upcoming_events' => $socialEvents,
            
            // Context specifics
            'relationship_focus' => $socialInfo['relationship_focus'] ?? '',
            'communication_context' => $socialInfo['communication_context'] ?? '',
            'event_type' => $socialInfo['event_type'] ?? '',
            
            // Metadata
            'query_type' => $socialInfo['query_type'] ?? 'general_social',
            'time_period' => $socialInfo['time_period'] ?? 'current',
        ];
    }
    
    /**
     * Extract social information from triage data
     * 
     * @param array $data The triage data
     * @return array Extracted social information
     */
    private function extractSocialInformation(array $data): array {
        return [
            'query_type' => $data['query_type'] ?? 'general_social',
            'relationship_focus' => $data['relationship_focus'] ?? '',
            'event_type' => $data['event_type'] ?? '',
            'communication_context' => $data['communication_context'] ?? '',
            'time_period' => $data['time_period'] ?? 'current',
        ];
    }
    
    /**
     * Process and save relationships found in the data
     * 
     * @param array $data The input data containing relationship information
     * @return void
     */
    private function processAndSaveRelationships(array $data): void {
        error_log("SocialAgent: Starting to process relationships from input data");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            error_log("SocialAgent: Database tool not available for relationship processing");
            return;
        }
        
        // Get user ID
        $userId = $data['user_id'] ?? DEFAULT_USER_ID;
        
        // Get original query to extract relationship information
        $originalQuery = $data['original_query'] ?? '';
        $triageSummary = $data['triage_summary'] ?? '';
        
        // First, identify people mentioned in the query
        $peopleNames = $this->extractPeopleNames($originalQuery, $triageSummary);
        error_log("SocialAgent: Extracted people: " . json_encode($peopleNames));
        
        if (empty($peopleNames)) {
            error_log("SocialAgent: No people identified in the query");
            return;
        }
        
        // For each person, try to identify relationships
        foreach ($peopleNames as $personName) {
            // First ensure the person entity exists
            $personId = $this->findOrCreatePerson($dbTool, $userId, $personName);
            
            if (!$personId) {
                error_log("SocialAgent: Failed to create or find person entity for '{$personName}'");
                continue;
            }
            
            // Extract relationships for this person
            $relationships = $this->extractRelationshipsForPerson($personName, $originalQuery, $triageSummary);
            error_log("SocialAgent: Extracted " . count($relationships) . " relationships for '{$personName}'");
            
            // Process each relationship
            foreach ($relationships as $relationship) {
                $targetName = $relationship['target'] ?? '';
                $relationType = $relationship['type'] ?? '';
                
                if (empty($targetName) || empty($relationType)) {
                    continue;
                }
                
                // Create the target entity if needed
                $targetType = $relationship['target_type'] ?? 'person';
                $targetId = $this->findOrCreateEntity($dbTool, $userId, $targetName, $targetType);
                
                if (!$targetId) {
                    error_log("SocialAgent: Failed to create target entity for '{$targetName}'");
                    continue;
                }
                
                // Create the relationship
                $relationshipId = 'rel_' . md5($personId . $targetId . $relationType . time());
                $metadata = !empty($relationship['metadata']) ? json_encode($relationship['metadata']) : null;
                $strength = $relationship['strength'] ?? 1.0;
                
                $result = $dbTool->createRelationship(
                    $relationshipId,
                    $userId,
                    $personId,
                    $targetId,
                    $relationType,
                    $strength,
                    $metadata
                );
                
                if ($result) {
                    error_log("SocialAgent: Created relationship '{$relationType}' from '{$personName}' to '{$targetName}'");
                } else {
                    error_log("SocialAgent: Failed to create relationship '{$relationType}' from '{$personName}' to '{$targetName}'");
                }
            }
        }
    }
    
    /**
     * Extract people names from text
     * 
     * @param string $query Original query
     * @param string $summary Triage summary
     * @return array List of people names
     */
    private function extractPeopleNames(string $query, string $summary): array {
        $people = [];
        
        // Simple extraction - look for proper nouns
        preg_match_all('/\b[A-Z][a-z]+ [A-Z][a-z]+\b/', $query, $matches);
        if (!empty($matches[0])) {
            $people = array_merge($people, $matches[0]);
        }
        
        // Check for names in the format "My friend X" or "X is my"
        preg_match_all('/\b(?:my|our) (?:friend|colleague|neighbor|boss|partner) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches);
        if (!empty($matches[1])) {
            $people = array_merge($people, $matches[1]);
        }
        
        preg_match_all('/\b([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:is|was) (?:my|our)\b/i', $query, $matches);
        if (!empty($matches[1])) {
            $people = array_merge($people, $matches[1]);
        }
        
        // Extract from summary if available
        if (!empty($summary)) {
            preg_match_all('/\b[A-Z][a-z]+ [A-Z][a-z]+\b/', $summary, $matches);
            if (!empty($matches[0])) {
                $people = array_merge($people, $matches[0]);
            }
        }
        
        // Remove duplicates and return
        return array_unique($people);
    }
    
    /**
     * Find or create a person entity
     * 
     * @param DatabaseTool $dbTool Database tool
     * @param string $userId User ID
     * @param string $name Person name
     * @return string|null Entity ID or null on failure
     */
    private function findOrCreatePerson(DatabaseTool $dbTool, string $userId, string $name): ?string {
        // Search for existing person
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ? AND type = 'person'";
        $results = $dbTool->executeParameterizedQuery($query, [$userId, $name]);
        
        if (!empty($results)) {
            return $results[0]['id'];
        }
        
        // Create new person entity
        $personId = 'entity_' . md5($name . '_' . time());
        $personData = json_encode([
            'name' => $name,
            'type' => 'person',
            'attributes' => [],
            'notes' => 'Created by SocialAgent for relationship tracking',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $result = $dbTool->saveNewEntity($personId, $userId, 'person', $name, $personData);
        
        return $result ? $personId : null;
    }
    
    /**
     * Find or create any entity
     * 
     * @param DatabaseTool $dbTool Database tool
     * @param string $userId User ID
     * @param string $name Entity name
     * @param string $type Entity type
     * @return string|null Entity ID or null on failure
     */
    private function findOrCreateEntity(DatabaseTool $dbTool, string $userId, string $name, string $type): ?string {
        // Search for existing entity
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
        $results = $dbTool->executeParameterizedQuery($query, [$userId, $name]);
        
        if (!empty($results)) {
            return $results[0]['id'];
        }
        
        // Create new entity
        $entityId = 'entity_' . md5($name . '_' . $type . '_' . time());
        $entityData = json_encode([
            'name' => $name,
            'type' => $type,
            'attributes' => [],
            'notes' => 'Created by SocialAgent for relationship target',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $result = $dbTool->saveNewEntity($entityId, $userId, $type, $name, $entityData);
        
        return $result ? $entityId : null;
    }
    
    /**
     * Extract relationships for a specific person
     * 
     * @param string $personName Person name
     * @param string $query Original query
     * @param string $summary Triage summary
     * @return array List of relationships
     */
    private function extractRelationshipsForPerson(string $personName, string $query, string $summary): array {
        $relationships = [];
        
        // Check for employment relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?works at ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = [
                'target' => $matches[1],
                'type' => 'employee_of',
                'target_type' => 'organization',
                'strength' => 0.8
            ];
        }
        
        // Check for job title
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?(?:is|as) a(?:n)? ([a-z]+(?: [a-z]+){0,3}(?:manager|director|engineer|developer|designer|consultant|specialist|analyst))\b/i', $query, $matches)) {
            $jobTitle = $matches[1];
            // This doesn't create a relationship but could be used for attributes
        }
        
        // Check for location relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?lives in ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = [
                'target' => $matches[1],
                'type' => 'lives_in',
                'target_type' => 'location',
                'strength' => 0.9
            ];
        }
        
        // Check for family relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?married to ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = [
                'target' => $matches[1],
                'type' => 'married_to',
                'target_type' => 'person',
                'strength' => 1.0
            ];
        }
        
        // Check for children
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?(?:has|have) (?:two|three|[0-9]+) (?:children|kids)(?: named)? ([A-Za-z, ]+)\b/i', $query, $matches)) {
            $childrenText = $matches[1];
            // Extract individual names
            preg_match_all('/\b([A-Z][a-z]+)\b/', $childrenText, $childMatches);
            if (!empty($childMatches[1])) {
                foreach ($childMatches[1] as $childName) {
                    $relationships[] = [
                        'target' => $childName,
                        'type' => 'parent_of',
                        'target_type' => 'person',
                        'strength' => 1.0
                    ];
                }
            }
        }
        
        // Check for parents
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?(?:parent|father|mother) (?:is|are) ([A-Za-z ]+)\b/i', $query, $matches)) {
            $parentName = $matches[1];
            $relationships[] = [
                'target' => $parentName,
                'type' => 'child_of',
                'target_type' => 'person',
                'strength' => 1.0
            ];
        }
        
        // Check for siblings
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?(?:brother|sister|sibling) (?:is|are) ([A-Za-z ]+)\b/i', $query, $matches)) {
            $siblingName = $matches[1];
            $relationships[] = [
                'target' => $siblingName,
                'type' => 'sibling_of',
                'target_type' => 'person',
                'strength' => 0.9
            ];
        }
        
        // Check for friendship
        if (preg_match('/\bmy friend ' . preg_quote($personName, '/') . '\b/i', $query)) {
            $relationships[] = [
                'target' => 'me',
                'type' => 'friend_of',
                'target_type' => 'person',
                'strength' => 0.8
            ];
        }
        
        return $relationships;
    }
    
    
    /**
     * Analyze social data and generate insights and recommendations
     * 
     * @param array $socialInfo Basic social information
     * @param array $contactsData User's contact information
     * @param array $events Social event data
     * @return array Social insights and recommendations
     */
    private function analyzeSocialData(array $socialInfo, array $contactsData, array $events): array {
        // Retrieve relationship data from database
        $dbTool = $this->toolManager->getTool('database');
        $relationshipData = [];
        
        if ($dbTool) {
            $userId = $socialInfo['user_id'] ?? DEFAULT_USER_ID;
            
            // Get entities representing people
            $people = $dbTool->findEntitiesByType($userId, 'person');
            
            // For each person, get their relationships
            foreach ($people as $person) {
                $personId = $person['id'];
                $personName = $person['primary_name'];
                
                // Get all relationships for this person
                $relationships = $dbTool->findRelationships($personId);
                
                if (!empty($relationships)) {
                    $relationshipData[$personName] = $relationships;
                }
            }
            
            // Also look for specific relationship types if focus is specified
            if (!empty($socialInfo['relationship_focus'])) {
                $focusType = $this->mapRelationshipFocusToType($socialInfo['relationship_focus']);
                $focusedRelationships = $dbTool->findRelationshipsByType($focusType);
                
                if (!empty($focusedRelationships)) {
                    $relationshipData['focused_relationships'] = $focusedRelationships;
                }
            }
        }
        
        // Generate insights based on relationship data
        $relationshipInsights = $this->generateRelationshipInsights($relationshipData);
        
        // Generate event recommendations based on relationships and calendar
        $eventRecommendations = $this->generateEventRecommendations($relationshipData, $events);
        
        // Communication tips based on relationship types and context
        $communicationTips = $this->generateCommunicationTips($socialInfo);
        
        return [
            'relationship_insights' => $relationshipInsights,
            'event_recommendations' => $eventRecommendations,
            'communication_tips' => $communicationTips,
            'relationship_data' => $relationshipData
        ];
    }
    
    /**
     * Map relationship focus terms to database relationship types
     * 
     * @param string $focus Relationship focus from query
     * @return string Database relationship type
     */
    private function mapRelationshipFocusToType(string $focus): string {
        $focusMap = [
            'family' => 'family_member',
            'work' => 'professional',
            'colleague' => 'colleague_of',
            'friend' => 'friend_of',
            'romantic' => 'romantic_partner',
            'business' => 'business_contact',
            'neighbor' => 'neighbor_of',
            'acquaintance' => 'acquaintance_of'
        ];
        
        $focus = strtolower(trim($focus));
        return $focusMap[$focus] ?? 'general';
    }
    
    /**
     * Generate relationship insights based on relationship data
     * 
     * @param array $relationshipData Relationship data from database
     * @return string Relationship insights
     */
    private function generateRelationshipInsights(array $relationshipData): string {
        if (empty($relationshipData)) {
            return "No relationship data available for analysis. Consider adding more social connections to your profile.";
        }
        
        $insights = "Relationship Insights:\n";
        
        // Count types of relationships
        $typeCount = [];
        foreach ($relationshipData as $person => $relationships) {
            if ($person === 'focused_relationships') continue;
            
            foreach ($relationships as $relationship) {
                $type = $relationship['type'] ?? 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
        }
        
        // Generate insights based on relationship types
        if (!empty($typeCount)) {
            $insights .= "- Your social network consists of: ";
            $typePhrases = [];
            
            foreach ($typeCount as $type => $count) {
                $typePhrases[] = "$count " . str_replace('_', ' ', $type) . " relationship" . ($count > 1 ? 's' : '');
            }
            
            $insights .= implode(', ', $typePhrases) . ".\n";
        }
        
        // Add focused relationship insights if available
        if (isset($relationshipData['focused_relationships'])) {
            $focusedCount = count($relationshipData['focused_relationships']);
            $focusType = $relationshipData['focused_relationships'][0]['type'] ?? 'unknown';
            
            $insights .= "- You have $focusedCount " . str_replace('_', ' ', $focusType) . 
                        " relationship" . ($focusedCount > 1 ? 's' : '') . " in your network.\n";
        }
        
        return $insights;
    }
    
    /**
     * Generate event recommendations based on relationships and calendar
     * 
     * @param array $relationshipData Relationship data from database
     * @param array $events Calendar events
     * @return string Event recommendations
     */
    private function generateEventRecommendations(array $relationshipData, array $events): string {
        if (empty($relationshipData) && empty($events)) {
            return "Consider planning social events to build your network and strengthen connections.";
        }
        
        $recommendations = "Event Recommendations:\n";
        
        // Check if there are upcoming social events
        if (!empty($events)) {
            $eventCount = count($events);
            $recommendations .= "- You have $eventCount upcoming social " . 
                              ($eventCount > 1 ? "events" : "event") . " in your calendar.\n";
        } else {
            $recommendations .= "- No upcoming social events found in your calendar.\n";
        }
        
        // Recommend events based on relationship types
        $typeCount = [];
        foreach ($relationshipData as $person => $relationships) {
            if ($person === 'focused_relationships') continue;
            
            foreach ($relationships as $relationship) {
                $type = $relationship['type'] ?? 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
        }
        
        // Generate event type recommendations
        if (!empty($typeCount)) {
            // Recommend family events if family relationships exist
            if (isset($typeCount['family_member']) && $typeCount['family_member'] > 0) {
                $recommendations .= "- Consider planning a family gathering to strengthen family bonds.\n";
            }
            
            // Recommend professional networking if work relationships exist
            if ((isset($typeCount['professional']) && $typeCount['professional'] > 0) || 
                (isset($typeCount['colleague_of']) && $typeCount['colleague_of'] > 0)) {
                $recommendations .= "- A professional networking event could help expand your career connections.\n";
            }
            
            // Recommend friend gatherings if friend relationships exist
            if (isset($typeCount['friend_of']) && $typeCount['friend_of'] > 0) {
                $recommendations .= "- Plan a casual get-together with friends to maintain these important relationships.\n";
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate communication tips based on relationship context
     * 
     * @param array $socialInfo Social information from query
     * @return array Communication tips
     */
    private function generateCommunicationTips(array $socialInfo): array {
        $generalTips = [
            "Be an active listener by maintaining eye contact and asking clarifying questions",
            "Express appreciation and gratitude in your relationships regularly",
            "Follow up after important conversations"
        ];
        
        $contextSpecificTips = [];
        
        // Add context-specific tips based on relationship focus
        if (!empty($socialInfo['relationship_focus'])) {
            $focus = strtolower($socialInfo['relationship_focus']);
            
            switch ($focus) {
                case 'family':
                    $contextSpecificTips[] = "Create regular family rituals to maintain close connections";
                    $contextSpecificTips[] = "Practice patience and understanding with family members";
                    break;
                    
                case 'work':
                case 'colleague':
                    $contextSpecificTips[] = "Maintain professional boundaries while being personable";
                    $contextSpecificTips[] = "Clearly communicate expectations and deadlines";
                    break;
                    
                case 'friend':
                    $contextSpecificTips[] = "Make time for one-on-one conversations to deepen friendships";
                    $contextSpecificTips[] = "Remember important details about your friends' lives";
                    break;
                    
                case 'romantic':
                    $contextSpecificTips[] = "Schedule regular date nights to maintain connection";
                    $contextSpecificTips[] = "Practice open and honest communication about needs and feelings";
                    break;
            }
        }
        
        // Add communication context tips
        if (!empty($socialInfo['communication_context'])) {
            $context = strtolower($socialInfo['communication_context']);
            
            switch ($context) {
                case 'conflict':
                    $contextSpecificTips[] = "Use 'I' statements instead of accusatory language";
                    $contextSpecificTips[] = "Take breaks if conversations become too heated";
                    break;
                    
                case 'negotiation':
                    $contextSpecificTips[] = "Focus on mutual interests rather than positions";
                    $contextSpecificTips[] = "Be prepared to compromise for win-win outcomes";
                    break;
                    
                case 'feedback':
                    $contextSpecificTips[] = "Be specific and constructive with feedback";
                    $contextSpecificTips[] = "Balance critical feedback with positive observations";
                    break;
            }
        }
        
        // Combine general and specific tips
        return array_merge($generalTips, $contextSpecificTips);
    }
    
    /**
     * Sanitize contacts data to remove sensitive information
     * 
     * @param array $contacts Contacts data to sanitize
     * @return array Sanitized contacts data
     */
    private function sanitizeContactsData(array $contacts): array {
        $sanitized = [];
        
        foreach ($contacts as $contact) {
            // Remove sensitive identifiers but keep relevant contact data
            $sanitizedContact = $contact;
            
            // Mask phone numbers, emails, etc.
            if (isset($sanitizedContact['phone'])) {
                $sanitizedContact['phone'] = $this->maskPhoneNumber($sanitizedContact['phone']);
            }
            
            if (isset($sanitizedContact['email'])) {
                $sanitizedContact['email'] = $this->maskEmail($sanitizedContact['email']);
            }
            
            $sanitized[] = $sanitizedContact;
        }
        
        return $sanitized;
    }
    
    /**
     * Mask a phone number for privacy
     * 
     * @param string $phone Phone number to mask
     * @return string Masked phone number
     */
    private function maskPhoneNumber(string $phone): string {
        // Keep only last 4 digits visible
        $length = strlen($phone);
        if ($length <= 4) return $phone;
        
        $maskedPart = str_repeat('*', $length - 4);
        $visiblePart = substr($phone, -4);
        
        return $maskedPart . $visiblePart;
    }
    
    /**
     * Mask an email address for privacy
     * 
     * @param string $email Email to mask
     * @return string Masked email
     */
    private function maskEmail(string $email): string {
        // Keep domain but mask username portion
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        $username = $parts[0];
        $domain = $parts[1];
        
        $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        
        return $maskedUsername . '@' . $domain;
    }
}

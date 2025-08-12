<?php
/**
 * SocialAgent - Specialized Agent for Social Interactions (MDO Version)
 * 
 * This agent handles social-related queries, including relationship management,
 * social event planning, and communication patterns.
 * Simplified implementation for the MDO environment.
 */
class SocialAgent {
    var $toolManager;
    
    /**
     * Constructor - Initialize with tool manager for tool access
     * 
     * @param ToolManager $toolManager Tool management service
     */
    public function __construct($toolManager = null) {
        $this->toolManager = $toolManager;
    }
    
    /**
     * Process input data and generate a response
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function process($data) {
        error_log("SocialAgent: Starting processing");
        
        // Extract social information from the data
        $socialInfo = $this->extractSocialInformation($data);
        
        // Process and save any relationships found in the data
        if (isset($data['relationship_focus']) && !empty($data['relationship_focus'])) {
            $this->processAndSaveRelationships($data);
        }
        
        // Determine task type based on query info
        $taskType = $socialInfo['query_type'] ?? 'general_social';
        
        // Process based on task type
        switch ($taskType) {
            case 'event_planning':
                return $this->processSocialEvent($data);
            case 'relationship_query':
                return $this->processRelationshipQuery($data);
            case 'communication_advice':
                return $this->processCommunicationAdvice($data);
            case 'networking':
                return $this->processNetworking($data);
            default:
                return $this->processGeneralSocial($data);
        }
    }
    
    /**
     * Extract social information from triage data
     */
    public function extractSocialInformation($data) {
        return array(
            'query_type' => isset($data['query_type']) ? $data['query_type'] : 'general_social',
            'relationship_focus' => isset($data['relationship_focus']) ? $data['relationship_focus'] : '',
            'event_type' => isset($data['event_type']) ? $data['event_type'] : '',
            'communication_context' => isset($data['communication_context']) ? $data['communication_context'] : '',
            'time_period' => isset($data['time_period']) ? $data['time_period'] : 'current',
        );
    }
    
    /**
     * Process and save relationships found in the data
     */
    public function processAndSaveRelationships($data) {
        error_log("SocialAgent: Starting to process relationships from input data");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            error_log("SocialAgent: Database tool not available for relationship processing");
            return;
        }
        
        // Get user ID
        $userId = isset($data['user_id']) ? $data['user_id'] : 'user_default';
        
        // Get original query to extract relationship information
        $originalQuery = isset($data['original_query']) ? $data['original_query'] : '';
        $triageSummary = isset($data['triage_summary']) ? $data['triage_summary'] : '';
        
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
                $targetName = isset($relationship['target']) ? $relationship['target'] : '';
                $relationType = isset($relationship['type']) ? $relationship['type'] : '';
                
                if (empty($targetName) || empty($relationType)) {
                    continue;
                }
                
                // Create the target entity if needed
                $targetType = isset($relationship['target_type']) ? $relationship['target_type'] : 'person';
                $targetId = $this->findOrCreateEntity($dbTool, $userId, $targetName, $targetType);
                
                if (!$targetId) {
                    error_log("SocialAgent: Failed to create target entity for '{$targetName}'");
                    continue;
                }
                
                // Create the relationship
                $relationshipId = 'rel_' . md5($personId . $targetId . $relationType . time());
                $metadata = !empty($relationship['metadata']) ? json_encode($relationship['metadata']) : null;
                $strength = isset($relationship['strength']) ? $relationship['strength'] : 1.0;
                
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
     */
    public function extractPeopleNames($query, $summary) {
        $people = array();
        
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
     */
    public function findOrCreatePerson($dbTool, $userId, $name) {
        // Search for existing person
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ? AND type = 'person'";
        $results = $dbTool->executeParameterizedQuery($query, array($userId, $name));
        
        if (!empty($results)) {
            return $results[0]['id'];
        }
        
        // Create new person entity
        $personId = 'entity_' . md5($name . '_' . time());
        $personData = json_encode(array(
            'name' => $name,
            'type' => 'person',
            'attributes' => array(),
            'notes' => 'Created by SocialAgent for relationship tracking',
            'created_at' => date('Y-m-d H:i:s')
        ));
        
        $result = $dbTool->saveNewEntity($personId, $userId, 'person', $name, $personData);
        
        return $result ? $personId : null;
    }
    
    /**
     * Find or create any entity
     */
    public function findOrCreateEntity($dbTool, $userId, $name, $type) {
        // Search for existing entity
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
        $results = $dbTool->executeParameterizedQuery($query, array($userId, $name));
        
        if (!empty($results)) {
            return $results[0]['id'];
        }
        
        // Create new entity
        $entityId = 'entity_' . md5($name . '_' . $type . '_' . time());
        $entityData = json_encode(array(
            'name' => $name,
            'type' => $type,
            'attributes' => array(),
            'notes' => 'Created by SocialAgent for relationship target',
            'created_at' => date('Y-m-d H:i:s')
        ));
        
        $result = $dbTool->saveNewEntity($entityId, $userId, $type, $name, $entityData);
        
        return $result ? $entityId : null;
    }
    
    /**
     * Extract relationships for a specific person
     */
    public function extractRelationshipsForPerson($personName, $query, $summary) {
        $relationships = array();
        
        // Check for employment relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?works at ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = array(
                'target' => $matches[1],
                'type' => 'employee_of',
                'target_type' => 'organization',
                'strength' => 0.8
            );
        }
        
        // Check for location relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?lives in ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = array(
                'target' => $matches[1],
                'type' => 'lives_in',
                'target_type' => 'location',
                'strength' => 0.9
            );
        }
        
        // Check for family relationships
        if (preg_match('/\b' . preg_quote($personName, '/') . '.+?married to ([A-Z][a-z]+(?: [A-Z][a-z]+)?)\b/i', $query, $matches)) {
            $relationships[] = array(
                'target' => $matches[1],
                'type' => 'married_to',
                'target_type' => 'person',
                'strength' => 1.0
            );
        }
        
        // Check for friendship
        if (preg_match('/\bmy friend ' . preg_quote($personName, '/') . '\b/i', $query)) {
            $relationships[] = array(
                'target' => 'me',
                'type' => 'friend_of',
                'target_type' => 'person',
                'strength' => 0.8
            );
        }
        
        return $relationships;
    }
    
    /**
     * Process general social query
     */
    public function processGeneralSocial($data) {
        return array(
            'status' => 'success',
            'message' => 'Social query processed',
            'response' => 'I\'ve processed your social query.'
        );
    }
    
    /**
     * Process social event planning
     */
    public function processSocialEvent($data) {
        return array(
            'status' => 'success',
            'message' => 'Social event planning processed',
            'response' => 'I\'ve helped plan your social event.'
        );
    }
    
    /**
     * Process relationship query
     */
    public function processRelationshipQuery($data) {
        return array(
            'status' => 'success',
            'message' => 'Relationship query processed',
            'response' => 'I\'ve processed your relationship query.'
        );
    }
    
    /**
     * Process communication advice
     */
    public function processCommunicationAdvice($data) {
        return array(
            'status' => 'success',
            'message' => 'Communication advice processed',
            'response' => 'Here\'s my advice for your communication situation.'
        );
    }
    
    /**
     * Process networking query
     */
    public function processNetworking($data) {
        return array(
            'status' => 'success',
            'message' => 'Networking query processed',
            'response' => 'I\'ve processed your networking query.'
        );
    }
}

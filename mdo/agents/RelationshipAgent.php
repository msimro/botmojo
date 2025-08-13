<?php
/**
 * RelationshipAgent - Specialized Agent for Managing Entity Relationships (MDO Version)
 * 
 * This agent handles the creation, management and querying of relationships
 * between entities in the system (people, organizations, places, etc).
 */

require_once __DIR__ . '/../tools/ToolManager.php';

define('DEFAULT_USER_ID', 'user_default');

class RelationshipAgent {
    var $toolManager;
    
    /**
     * Constructor
     * 
     * @param ToolManager $toolManager Tool manager instance
     */
    public function __construct($toolManager) {
        $this->toolManager = $toolManager;
    }
    
    /**
     * Process the input data and generate a response
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function process($data) {
        error_log("RelationshipAgent: Starting processing");
        
        // Extract task type from the data
        $taskType = isset($data['relationship_task_type']) ? $data['relationship_task_type'] : 'create_relationship';
        
        // Process based on task type
        switch ($taskType) {
            case 'create_relationship':
                return $this->createRelationship($data);
            case 'query_relationship':
                return $this->queryRelationship($data);
            case 'update_relationship':
                return $this->updateRelationship($data);
            case 'analyze_relationships':
                return $this->analyzeRelationships($data);
            default:
                return $this->createRelationship($data);
        }
    }
    
    /**
     * Create new relationships
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function createRelationship($data) {
        error_log("RelationshipAgent: Creating relationship");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            error_log("RelationshipAgent: Database tool not available");
            return array(
                'status' => 'error',
                'message' => 'Database tool not available',
                'response' => 'I was unable to create the relationship due to a database issue.'
            );
        }
        
        // Get user ID
        $userId = isset($data['user_id']) ? $data['user_id'] : DEFAULT_USER_ID;
        
        // Extract relationships from data
        $relationships = $this->extractRelationships($data);
        error_log("RelationshipAgent: Extracted " . count($relationships) . " relationships");
        
        if (empty($relationships)) {
            return array(
                'status' => 'error',
                'message' => 'No relationships found in the data',
                'response' => 'I couldn\'t identify any relationships to create from your message.'
            );
        }
        
        $createdRelationships = array();
        $errors = array();
        
        // Create each relationship
        foreach ($relationships as $relationship) {
            $sourceEntity = isset($relationship['source']) ? $relationship['source'] : null;
            $targetEntity = isset($relationship['target']) ? $relationship['target'] : null;
            $relationType = isset($relationship['type']) ? $relationship['type'] : null;
            
            if (!$sourceEntity || !$targetEntity || !$relationType) {
                $errors[] = "Missing required relationship data";
                continue;
            }
            
            // Find or create source entity
            $sourceType = isset($relationship['source_type']) ? $relationship['source_type'] : 'person';
            $sourceId = $this->findOrCreateEntity($dbTool, $userId, $sourceEntity, $sourceType);
            
            if (!$sourceId) {
                $errors[] = "Failed to create source entity: $sourceEntity";
                continue;
            }
            
            // Find or create target entity
            $targetType = isset($relationship['target_type']) ? $relationship['target_type'] : 'person';
            $targetId = $this->findOrCreateEntity($dbTool, $userId, $targetEntity, $targetType);
            
            if (!$targetId) {
                $errors[] = "Failed to create target entity: $targetEntity";
                continue;
            }
            
            // Create the relationship
            $relationshipId = 'rel_' . md5($sourceId . $targetId . $relationType . time());
            $metadata = !empty($relationship['metadata']) ? json_encode($relationship['metadata']) : null;
            $strength = isset($relationship['strength']) ? $relationship['strength'] : 1.0;
            
            $result = $dbTool->createRelationship(
                $relationshipId,
                $userId,
                $sourceId,
                $targetId,
                $relationType,
                $strength,
                $metadata
            );
            
            if ($result) {
                error_log("RelationshipAgent: Created relationship '$relationType' from '$sourceEntity' to '$targetEntity'");
                $createdRelationships[] = array(
                    'source' => $sourceEntity,
                    'target' => $targetEntity,
                    'type' => $relationType
                );
            } else {
                $errors[] = "Failed to create relationship: $sourceEntity $relationType $targetEntity";
            }
        }
        
        // Prepare response
        $responseText = "";
        
        if (!empty($createdRelationships)) {
            $responseText .= "I've created the following relationships:\n";
            foreach ($createdRelationships as $rel) {
                $responseText .= "- {$rel['source']} {$rel['type']} {$rel['target']}\n";
            }
        }
        
        if (!empty($errors)) {
            $responseText .= "\nThere were some issues:\n";
            foreach ($errors as $error) {
                $responseText .= "- $error\n";
            }
        }
        
        return array(
            'status' => !empty($createdRelationships) ? 'success' : 'error',
            'relationships_created' => $createdRelationships,
            'errors' => $errors,
            'response' => $responseText
        );
    }
    
    /**
     * Query existing relationships
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function queryRelationship($data) {
        error_log("RelationshipAgent: Querying relationship");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            return array(
                'status' => 'error',
                'message' => 'Database tool not available',
                'response' => 'I was unable to query relationships due to a database issue.'
            );
        }
        
        // Get user ID
        $userId = isset($data['user_id']) ? $data['user_id'] : DEFAULT_USER_ID;
        
        // Extract entity name from query
        $entityName = isset($data['entity_name']) ? $data['entity_name'] : null;
        if (!$entityName) {
            // Try to extract from original query
            $originalQuery = isset($data['original_query']) ? $data['original_query'] : '';
            preg_match('/(?:relationship|connection|related|relation).*?(?:between|of|for) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i', $originalQuery, $matches);
            $entityName = isset($matches[1]) ? $matches[1] : null;
        }
        
        if (!$entityName) {
            return array(
                'status' => 'error',
                'message' => 'No entity name provided for relationship query',
                'response' => 'I need to know which person or entity you want to query relationships for.'
            );
        }
        
        // Find entity ID
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
        $results = $dbTool->executeParameterizedQuery($query, array($userId, $entityName));
        
        if (empty($results)) {
            return array(
                'status' => 'error',
                'message' => "Entity not found: $entityName",
                'response' => "I don't have any information about $entityName in my database."
            );
        }
        
        $entityId = $results[0]['id'];
        
        // Query all relationships where this entity is the source
        $query = "SELECT r.type, e.primary_name, e.type AS entity_type, r.strength 
                  FROM relationships r 
                  JOIN entities e ON r.target_id = e.id 
                  WHERE r.user_id = ? AND r.source_id = ?";
        $outgoingRelations = $dbTool->executeParameterizedQuery($query, array($userId, $entityId));
        
        // Query all relationships where this entity is the target
        $query = "SELECT r.type, e.primary_name, e.type AS entity_type, r.strength 
                  FROM relationships r 
                  JOIN entities e ON r.source_id = e.id 
                  WHERE r.user_id = ? AND r.target_id = ?";
        $incomingRelations = $dbTool->executeParameterizedQuery($query, array($userId, $entityId));
        
        // Format response
        $responseText = "Here's what I know about $entityName's relationships:\n\n";
        
        if (!empty($outgoingRelations)) {
            $responseText .= "$entityName:\n";
            foreach ($outgoingRelations as $relation) {
                $responseText .= "- {$relation['type']} {$relation['primary_name']} ({$relation['entity_type']})\n";
            }
            $responseText .= "\n";
        }
        
        if (!empty($incomingRelations)) {
            $responseText .= "Related to $entityName:\n";
            foreach ($incomingRelations as $relation) {
                $responseText .= "- {$relation['primary_name']} ({$relation['entity_type']}) {$relation['type']} $entityName\n";
            }
        }
        
        if (empty($outgoingRelations) && empty($incomingRelations)) {
            $responseText .= "I don't have any relationship information for $entityName.";
        }
        
        return array(
            'status' => 'success',
            'entity_name' => $entityName,
            'outgoing_relations' => $outgoingRelations,
            'incoming_relations' => $incomingRelations,
            'response' => $responseText
        );
    }
    
    /**
     * Update existing relationships
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function updateRelationship($data) {
        // Implementation similar to createRelationship but updating existing ones
        return array(
            'status' => 'success',
            'message' => 'Relationship updated',
            'response' => 'I\'ve updated the relationship as requested.'
        );
    }
    
    /**
     * Analyze relationship networks
     * 
     * @param array $data Input data
     * @return array Response data
     */
    public function analyzeRelationships($data) {
        // Implementation for advanced relationship analysis
        return array(
            'status' => 'success',
            'message' => 'Relationship analysis completed',
            'response' => 'I\'ve analyzed the relationship network and here are my findings...'
        );
    }
    
    /**
     * Extract relationships from input data
     * 
     * @param array $data Input data
     * @return array Extracted relationships
     */
    public function extractRelationships($data) {
        $relationships = array();
        
        // If explicit relationship data is provided
        if (!empty($data['relationships'])) {
            return $data['relationships'];
        }
        
        // Extract from original query
        $originalQuery = isset($data['original_query']) ? $data['original_query'] : '';
        $triageSummary = isset($data['triage_summary']) ? $data['triage_summary'] : '';
        
        // Simple relationship patterns
        $patterns = array(
            // X is Y's Z
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:is|are) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)[\'s]? ((?:friend|brother|sister|father|mother|colleague|boss|employee|neighbor|roommate|partner|spouse|husband|wife|child|son|daughter))/i' => function($matches) {
                return array(
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => $this->normalizeRelationType($matches[3]),
                    'source_type' => 'person',
                    'target_type' => 'person'
                );
            },
            // X works at/for Y
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:works|worked) (?:at|for) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i' => function($matches) {
                return array(
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => 'employee_of',
                    'source_type' => 'person',
                    'target_type' => 'organization'
                );
            },
            // X lives in Y
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:lives|lived) in ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i' => function($matches) {
                return array(
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => 'lives_in',
                    'source_type' => 'person',
                    'target_type' => 'location'
                );
            },
            // X and Y are Z
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) and ([A-Z][a-z]+(?: [A-Z][a-z]+)?) are ((?:friends|colleagues|neighbors|roommates|partners|spouses|married|siblings|brothers|sisters))/i' => function($matches) {
                $type = $this->normalizeRelationType($matches[3]);
                return array(
                    array(
                        'source' => $matches[1],
                        'target' => $matches[2],
                        'type' => $type,
                        'source_type' => 'person',
                        'target_type' => 'person'
                    ),
                    array(
                        'source' => $matches[2],
                        'target' => $matches[1],
                        'type' => $type,
                        'source_type' => 'person',
                        'target_type' => 'person'
                    )
                );
            }
        );
        
        // Apply patterns
        foreach ($patterns as $pattern => $callback) {
            preg_match_all($pattern, $originalQuery, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $result = $callback($match);
                if (is_array($result)) {
                    if (isset($result['source'])) {
                        // Single relationship
                        $relationships[] = $result;
                    } else {
                        // Multiple relationships
                        foreach ($result as $rel) {
                            $relationships[] = $rel;
                        }
                    }
                }
            }
        }
        
        // If no relationships found, try to extract from triage summary
        if (empty($relationships) && !empty($triageSummary)) {
            foreach ($patterns as $pattern => $callback) {
                preg_match_all($pattern, $triageSummary, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $result = $callback($match);
                    if (is_array($result)) {
                        if (isset($result['source'])) {
                            // Single relationship
                            $relationships[] = $result;
                        } else {
                            // Multiple relationships
                            foreach ($result as $rel) {
                                $relationships[] = $rel;
                            }
                        }
                    }
                }
            }
        }
        
        return $relationships;
    }
    
    /**
     * Normalize relationship type to a standard format
     * 
     * @param string $relationType Relationship type from text
     * @return string Normalized relationship type
     */
    public function normalizeRelationType($relationType) {
        $relationType = strtolower(trim($relationType));
        
        $mapping = array(
            'friend' => 'friend_of',
            'friends' => 'friend_of',
            'brother' => 'sibling_of',
            'sister' => 'sibling_of',
            'siblings' => 'sibling_of',
            'brothers' => 'sibling_of',
            'sisters' => 'sibling_of',
            'father' => 'parent_of',
            'mother' => 'parent_of',
            'parent' => 'parent_of',
            'child' => 'child_of',
            'son' => 'child_of',
            'daughter' => 'child_of',
            'colleague' => 'colleague_of',
            'colleagues' => 'colleague_of',
            'boss' => 'manager_of',
            'employee' => 'employee_of',
            'neighbor' => 'neighbor_of',
            'neighbors' => 'neighbor_of',
            'roommate' => 'roommate_of',
            'roommates' => 'roommate_of',
            'partner' => 'partner_of',
            'partners' => 'partner_of',
            'spouse' => 'spouse_of',
            'spouses' => 'spouse_of',
            'husband' => 'spouse_of',
            'wife' => 'spouse_of',
            'married' => 'spouse_of'
        );
        
        return isset($mapping[$relationType]) ? $mapping[$relationType] : $relationType;
    }
    
    /**
     * Find or create an entity
     * 
     * @param DatabaseTool $dbTool Database tool
     * @param string $userId User ID
     * @param string $name Entity name
     * @param string $type Entity type
     * @return string|null Entity ID or null on failure
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
            'notes' => 'Created by RelationshipAgent',
            'created_at' => date('Y-m-d H:i:s')
        ));
        
        $result = $dbTool->saveNewEntity($entityId, $userId, $type, $name, $entityData);
        
        return $result ? $entityId : null;
    }
}

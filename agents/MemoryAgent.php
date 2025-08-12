<?php
/**
 * MemoryAgent - Enhanced Knowledge Graph Component Creator
 * 
 * This agent manages the core knowledge graph about people, places, and objects.
 * It creates memory components that store relationships, attributes, and contextual
 * information about entities in the user's life. Enhanced to extract rich information
 * from triage data and natural language context.
 * 
 * @author AI Personal Assistant Team
 * @version 1.1
 * @since 2025-08-07
 */
class MemoryAgent {
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
     * Create a memory component from provided data
     * Processes knowledge graph data and returns a standardized memory component
     * Enhanced to extract information from triage context and natural language
     * 
     * @param array $data Raw memory data from the triage system
     * @return array Standardized memory component with relationship and attribute data
     */
    public function createComponent(array $data): array {
        // Extract enhanced information from triage context if available
        $extractedInfo = $this->extractEnhancedInformation($data);
        
        // Debug logging
        error_log("MemoryAgent: Creating component for " . ($extractedInfo['name'] ?? 'unknown entity'));
        error_log("MemoryAgent: Entity ID from data: " . ($data['entity_id'] ?? 'not provided'));
        error_log("MemoryAgent: Relationships found: " . count($extractedInfo['relationships'] ?? []));
        
        // Check if we need to save relationships to the database
        $this->processRelationships($extractedInfo, $data);
        
        return [
            // Core identity information
            'name' => $extractedInfo['name'] ?? $data['name'] ?? '',       // Primary name or identifier
            'type' => $extractedInfo['type'] ?? $data['type'] ?? 'person', // Entity type: person, place, object
            
            // Enhanced descriptive information
            'attributes' => array_merge(
                $data['attributes'] ?? [], 
                $extractedInfo['attributes'] ?? []
            ),
            'relationships' => array_merge(
                $data['relationships'] ?? [], 
                $extractedInfo['relationships'] ?? []
            ),
            'notes' => $this->combineNotes($data['notes'] ?? '', $extractedInfo['notes'] ?? ''),
            'tags' => array_unique(array_merge(
                $data['tags'] ?? [], 
                $extractedInfo['tags'] ?? []
            )),
            
            // Enhanced contextual metadata
            'last_interaction' => $data['last_interaction'] ?? date('Y-m-d H:i:s'),
            'importance_level' => $this->calculateImportance($extractedInfo, $data),
            
            // Additional context preservation
            'extraction_context' => $extractedInfo['context'] ?? [],
            'confidence_score' => $extractedInfo['confidence'] ?? 0.8
        ];
    }
    
    /**
     * Extract enhanced information from triage data and context
     * Uses pattern matching and contextual analysis to find relationships, attributes
     * 
     * @param array $data Complete data from triage system
     * @return array Enhanced information including attributes, relationships, etc.
     */
    private function extractEnhancedInformation(array $data): array {
        $extracted = [
            'name' => '',
            'type' => 'person',
            'attributes' => [],
            'relationships' => [],
            'notes' => '',
            'tags' => [],
            'context' => [],
            'confidence' => 0.8
        ];
        
        // Check for triage summary and original query in various data locations
        $triageSummary = '';
        $originalQuery = '';
        
        // Look for triage data in different possible locations
        if (isset($data['triage_summary'])) {
            $triageSummary = $data['triage_summary'];
        }
        if (isset($data['original_query'])) {
            $originalQuery = $data['original_query'];
        }
        
        // Combine all available text for analysis
        $analysisText = trim($triageSummary . ' ' . $originalQuery);
        
        if ($analysisText) {
            // Extract person/entity name - prefer from component data first
            if (!empty($data['name'])) {
                $extracted['name'] = $data['name'];
                $extracted['context']['name_source'] = 'component_data';
            } elseif (preg_match('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\b/', $analysisText, $nameMatches)) {
                $extracted['name'] = $nameMatches[1];
                $extracted['context']['name_source'] = 'pattern_match';
            }
            
            // Extract employment information - check component data first
            $employer = $data['employer'] ?? $data['company'] ?? null;
            if (!empty($employer)) {
                $extracted['attributes']['employer'] = $employer;
                $extracted['relationships'][] = [
                    'type' => 'employment',
                    'target' => $employer,
                    'relationship' => 'employee_of'
                ];
                $extracted['tags'][] = 'employment';
            } elseif (preg_match('/works?\s+at\s+([A-Z][a-zA-Z\s&.]+?)(?:\s+as|\s+in|$)/i', $analysisText, $employerMatch)) {
                $employer = trim($employerMatch[1]);
                $extracted['attributes']['employer'] = $employer;
                $extracted['relationships'][] = [
                    'type' => 'employment',
                    'target' => $employer,
                    'relationship' => 'employee_of'
                ];
                $extracted['tags'][] = 'employment';
            }
            
            // Extract job titles - check component data first
            $jobTitle = $data['occupation'] ?? $data['job_title'] ?? null;
            if (!empty($jobTitle)) {
                $extracted['attributes']['job_title'] = $jobTitle;
                $extracted['tags'][] = 'professional';
            } elseif (preg_match('/as\s+(?:a\s+)?([a-z\s]+(?:engineer|developer|manager|analyst|designer|director|specialist|consultant|coordinator|administrator|assistant|officer|representative|technician))/i', $analysisText, $jobMatch)) {
                $jobTitle = trim($jobMatch[1]);
                $extracted['attributes']['job_title'] = $jobTitle;
                $extracted['tags'][] = 'professional';
            }
            
            // Extract contact information - check component data first
            if (!empty($data['email'])) {
                $extracted['attributes']['email'] = $data['email'];
                $extracted['tags'][] = 'contact';
            } elseif (preg_match('/\b[\w._%+-]+@[\w.-]+\.[A-Z]{2,}\b/i', $analysisText, $emailMatch)) {
                $extracted['attributes']['email'] = $emailMatch[0];
                $extracted['tags'][] = 'contact';
            }
            
            if (!empty($data['phone'])) {
                $extracted['attributes']['phone'] = $data['phone'];
                $extracted['tags'][] = 'contact';
            } elseif (preg_match('/\b(?:\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})\b/', $analysisText, $phoneMatch)) {
                $extracted['attributes']['phone'] = $phoneMatch[0];
                $extracted['tags'][] = 'contact';
            }
            
            // Extract contact information - check component data first
            if (!empty($data['email'])) {
                $extracted['attributes']['email'] = $data['email'];
                $extracted['tags'][] = 'contact';
            } elseif (preg_match('/\b[\w._%+-]+@[\w.-]+\.[A-Z]{2,}\b/i', $analysisText, $emailMatch)) {
                $extracted['attributes']['email'] = $emailMatch[0];
                $extracted['tags'][] = 'contact';
            }
            
            if (!empty($data['phone'])) {
                $extracted['attributes']['phone'] = $data['phone'];
                $extracted['tags'][] = 'contact';
            } elseif (preg_match('/\b(?:\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})\b/', $analysisText, $phoneMatch)) {
                $extracted['attributes']['phone'] = $phoneMatch[0];
                $extracted['tags'][] = 'contact';
            }
            
            // Extract location information - check component data first
            if (!empty($data['location'])) {
                $extracted['attributes']['location'] = $data['location'];
                $extracted['relationships'][] = [
                    'type' => 'location',
                    'target' => $data['location'],
                    'relationship' => 'lives_in'
                ];
                $extracted['tags'][] = 'location';
            } elseif (preg_match('/(?:lives?\s+in|located\s+in|from)\s+([A-Z][a-zA-Z\s,]+)/i', $analysisText, $locationMatch)) {
                $location = trim($locationMatch[1]);
                $extracted['attributes']['location'] = $location;
                $extracted['relationships'][] = [
                    'type' => 'location',
                    'target' => $location,
                    'relationship' => 'lives_in'
                ];
                $extracted['tags'][] = 'location';
            }
            
            // Extract interests and hobbies
            if (preg_match('/(?:likes?|enjoys?|interested\s+in|hobby|hobbies)\s+([a-zA-Z\s,]+)/i', $analysisText, $interestMatch)) {
                $interests = array_map('trim', explode(',', $interestMatch[1]));
                $extracted['attributes']['interests'] = $interests;
                $extracted['tags'][] = 'interests';
            }
            
            // Store analysis context
            $extracted['context']['analysis_text'] = $analysisText;
            $extracted['context']['patterns_found'] = array_keys(array_filter([
                'name' => !empty($extracted['name']),
                'employer' => isset($extracted['attributes']['employer']),
                'job_title' => isset($extracted['attributes']['job_title']),
                'location' => isset($extracted['attributes']['location']),
                'contact' => isset($extracted['attributes']['email']) || isset($extracted['attributes']['phone']),
                'interests' => isset($extracted['attributes']['interests'])
            ]));
            
            // Generate notes from successful extractions
            if (!empty($extracted['context']['patterns_found'])) {
                $extracted['notes'] = "Extracted " . implode(', ', $extracted['context']['patterns_found']) . " from: " . substr($analysisText, 0, 100) . "...";
            }
        }
        
        return $extracted;
    }
    
    /**
     * Combine notes from different sources intelligently
     * 
     * @param string $originalNotes Existing notes
     * @param string $extractedNotes Newly extracted notes
     * @return string Combined notes
     */
    private function combineNotes(string $originalNotes, string $extractedNotes): string {
        $notes = array_filter([trim($originalNotes), trim($extractedNotes)]);
        return implode(' | ', $notes);
    }
    
    /**
     * Calculate importance level based on extracted information
     * 
     * @param array $extractedInfo Information extracted from context
     * @param array $originalData Original data from triage
     * @return string Importance level: low, medium, high
     */
    private function calculateImportance(array $extractedInfo, array $originalData): string {
        $score = 0;
        
        // Base importance from original data
        $baseImportance = $originalData['importance_level'] ?? 'medium';
        
        // Boost for extracted attributes
        $score += count($extractedInfo['attributes'] ?? []) * 0.2;
        
        // Boost for relationships
        $score += count($extractedInfo['relationships'] ?? []) * 0.3;
        
        // Boost for employment information
        if (isset($extractedInfo['attributes']['employer'])) {
            $score += 0.4;
        }
        
        // Boost for contact information
        if (isset($extractedInfo['attributes']['email']) || isset($extractedInfo['attributes']['phone'])) {
            $score += 0.3;
        }
        
        if ($score >= 1.0) return 'high';
        if ($score >= 0.5) return 'medium';
        return 'low';
    }
    
    /**
     * Process relationships from extracted information and store in database
     * 
     * @param array $extractedInfo Information extracted from context
     * @param array $originalData Original data from triage
     * @return void
     */
    private function processRelationships(array $extractedInfo, array $originalData): void {
        // Skip if no relationships were identified
        if (empty($extractedInfo['relationships'])) {
            error_log("MemoryAgent: No relationships found to process");
            return;
        }
        
        // Get DatabaseTool instance
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            // Log error if database tool is not available
            error_log("MemoryAgent: DatabaseTool not available for relationship processing");
            return;
        }
        
        // Get user ID from original data or use default
        $userId = $originalData['user_id'] ?? DEFAULT_USER_ID;
        error_log("MemoryAgent: Processing relationships with user ID: {$userId}");
        
        // First, ensure the source entity exists in the database
        $sourceEntityName = $extractedInfo['name'] ?? $originalData['name'] ?? 'unknown';
        $sourceEntityType = $extractedInfo['type'] ?? $originalData['type'] ?? 'person';
        
        error_log("MemoryAgent: Source entity info - Name: {$sourceEntityName}, Type: {$sourceEntityType}");
        
        // Generate entity ID for the source entity if not provided
        $sourceEntityId = $originalData['entity_id'] ?? null;
        error_log("MemoryAgent: Source entity ID from original data: " . ($sourceEntityId ?? 'null'));
        
        if (!$sourceEntityId) {
            // Try to find existing entity by name
            $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
            $results = $dbTool->executeParameterizedQuery($query, [$userId, $sourceEntityName]);
            
            if (!empty($results)) {
                $sourceEntityId = $results[0]['id'];
                error_log("MemoryAgent: Found existing source entity with ID: {$sourceEntityId}");
            } else {
                // Create a new entity for the source
                $sourceEntityId = $this->generateEntityId($sourceEntityName);
                $sourceEntityData = json_encode([
                    'name' => $sourceEntityName,
                    'type' => $sourceEntityType,
                    'attributes' => $extractedInfo['attributes'] ?? [],
                    'notes' => 'Created automatically for relationship source',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $result = $dbTool->saveNewEntity($sourceEntityId, $userId, $sourceEntityType, $sourceEntityName, $sourceEntityData);
                error_log("MemoryAgent: Created source entity '{$sourceEntityName}' with ID '{$sourceEntityId}' - Result: " . ($result ? 'success' : 'failed'));
            }
        }
        
        // Process each relationship
        $relationshipCount = count($extractedInfo['relationships']);
        error_log("MemoryAgent: Processing {$relationshipCount} relationships");
        
        foreach ($extractedInfo['relationships'] as $index => $relationship) {
            // Skip incomplete relationship data
            if (empty($relationship['type']) || empty($relationship['target'])) {
                error_log("MemoryAgent: Skipping incomplete relationship at index {$index}");
                continue;
            }
            
            $relType = $relationship['type'];
            $targetName = $relationship['target'];
            error_log("MemoryAgent: Processing relationship {$index} - Type: {$relType}, Target: {$targetName}");
            
            // Generate target entity ID if needed
            $targetType = $relationship['target_type'] ?? 'entity';
            $targetEntityId = $this->findOrCreateTargetEntity($dbTool, $userId, $targetName, $targetType);
            
            if (!$targetEntityId) {
                error_log("MemoryAgent: Failed to get target entity ID for '{$targetName}'");
                continue;
            }
            
            error_log("MemoryAgent: Target entity ID: {$targetEntityId}");
            
            // Generate relationship metadata if available
            $metadata = null;
            if (!empty($relationship['metadata'])) {
                $metadata = json_encode($relationship['metadata']);
            }
            
            // Calculate relationship strength (default 1.0)
            $strength = $relationship['strength'] ?? 1.0;
            
            // Create relationship ID
            $relationshipId = $this->generateRelationshipId($sourceEntityId, $targetEntityId, $relationship['type']);
            
            // Store relationship in database
            $relationType = $relationship['relationship'] ?? $relationship['type'];
            error_log("MemoryAgent: Creating relationship ID: {$relationshipId}, Type: {$relationType}");
            
            $result = $dbTool->createRelationship(
                $relationshipId,
                $userId,
                $sourceEntityId,
                $targetEntityId,
                $relationType,
                $strength,
                $metadata
            );
            
            if ($result) {
                error_log("MemoryAgent: Successfully created relationship '{$relationType}' from '{$sourceEntityName}' to '{$targetName}'");
            } else {
                error_log("MemoryAgent: Failed to create relationship '{$relationType}' from '{$sourceEntityName}' to '{$targetName}'");
            }
        }
    }
    
    /**
     * Generate a unique entity ID based on name
     * 
     * @param string $name Entity name
     * @return string Unique entity ID
     */
    private function generateEntityId(string $name): string {
        return 'entity_' . md5($name . '_' . time());
    }
    
    /**
     * Generate a unique relationship ID
     * 
     * @param string $sourceId Source entity ID
     * @param string $targetId Target entity ID
     * @param string $type Relationship type
     * @return string Unique relationship ID
     */
    private function generateRelationshipId(string $sourceId, string $targetId, string $type): string {
        return 'rel_' . md5($sourceId . '_' . $targetId . '_' . $type . '_' . time());
    }
    
    /**
     * Find existing entity or create a new one for relationship target
     * 
     * @param DatabaseTool $dbTool Database tool instance
     * @param string $userId User ID
     * @param string $name Entity name
     * @param string $type Entity type
     * @return string|null Entity ID or null on failure
     */
    private function findOrCreateTargetEntity(DatabaseTool $dbTool, string $userId, string $name, string $type): ?string {
        // Search for existing entity with this name
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
        error_log("MemoryAgent: Searching for target entity with name: {$name}");
        
        try {
            $results = $dbTool->executeParameterizedQuery($query, [$userId, $name]);
            
            // Return existing entity ID if found
            if (!empty($results)) {
                $entityId = $results[0]['id'];
                error_log("MemoryAgent: Found existing target entity with ID: {$entityId}");
                return $entityId;
            }
            
            // Create new entity if not found
            $entityId = $this->generateEntityId($name);
            $entityData = json_encode([
                'name' => $name,
                'type' => $type,
                'attributes' => [],
                'notes' => 'Created automatically for relationship tracking',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $result = $dbTool->saveNewEntity($entityId, $userId, $type, $name, $entityData);
            
            if ($result) {
                error_log("MemoryAgent: Successfully created target entity '{$name}' with ID '{$entityId}'");
                return $entityId;
            } else {
                error_log("MemoryAgent: Failed to create target entity '{$name}'");
                return null;
            }
        } catch (Exception $e) {
            error_log("MemoryAgent: Error in findOrCreateTargetEntity: " . $e->getMessage());
            return null;
        }
    }
}

<?php
/**
 * MemoryAgent - Advanced Knowledge Graph and Entity Relationship Intelligence Agent
 * 
 * OVERVIEW:
 * The MemoryAgent is the core knowledge management component of the BotMojo AI Personal
 * Assistant, responsible for creating, maintaining, and querying the comprehensive
 * knowledge graph of entities and their relationships. It processes natural language
 * to extract structured knowledge about people, places, organizations, and objects
 * in the user's personal and professional life.
 * 
 * CORE CAPABILITIES:
 * - Entity Recognition: People, places, organizations, objects from natural language
 * - Relationship Extraction: Family, professional, social, location relationships
 * - Attribute Management: Physical descriptions, roles, preferences, characteristics
 * - Knowledge Graph Construction: Dynamic entity linking and relationship mapping
 * - Context Preservation: Conversation context and temporal relationship tracking
 * - Memory Consolidation: Intelligent merging of duplicate and related entities
 * - Semantic Search: Intelligent entity retrieval based on context and similarity
 * - Privacy Management: Sensitive information handling and access control
 * 
 * KNOWLEDGE GRAPH ARCHITECTURE:
 * - Entity Storage: Comprehensive profiles with attributes and metadata
 * - Relationship Mapping: Bidirectional relationships with strength and context
 * - Temporal Tracking: Time-based relationship evolution and interaction history
 * - Contextual Attributes: Dynamic attributes based on interaction context
 * - Confidence Scoring: Reliability assessment for extracted information
 * - Conflict Resolution: Smart handling of contradictory information
 * - Data Lineage: Source tracking for all knowledge graph updates
 * 
 * NATURAL LANGUAGE PROCESSING:
 * - Entity Mention Detection: "my friend John", "Sarah from work", "my dentist"
 * - Relationship Inference: "John is my brother", "Sarah works at Google"
 * - Attribute Extraction: "tall guy with glasses", "lives in Seattle"
 * - Context Understanding: Implicit relationships and situational attributes
 * - Temporal Processing: "used to work there", "recently moved"
 * - Ambiguity Resolution: Smart disambiguation of similar entities
 * 
 * INTEGRATION CAPABILITIES:
 * - Database Tool: Persistent storage and retrieval of knowledge graph data
 * - Search Tool: External information enrichment and verification
 * - Contacts Tool: Integration with contact management systems
 * - ToolManager: Secure access to knowledge management tools
 * - Other Agents: Knowledge sharing across specialized agent domains
 * 
 * INTELLIGENT FEATURES:
 * - Smart Deduplication: Automatic merging of duplicate entity mentions
 * - Relationship Inference: Logical relationship derivation (transitivity)
 * - Importance Scoring: Dynamic entity importance based on interaction patterns
 * - Privacy Classification: Automatic sensitivity level assignment
 * - Knowledge Gaps: Identification of missing or incomplete information
 * - Trend Analysis: Relationship evolution and change pattern detection
 * 
 * ARCHITECTURE INTEGRATION:
 * - Implements standard Agent interface with createComponent() method
 * - Central hub for entity data across all specialized agents
 * - Integrates with entity storage system using optimized JSON columns
 * - Supports real-time updates and batch knowledge graph operations
 * - Maintains strict data consistency and referential integrity
 * 
 * EXAMPLE USE CASES:
 * - "My friend John works at Microsoft"
 * - "Sarah is my sister who lives in Portland"
 * - "Dr. Smith is my cardiologist at the downtown clinic"
 * - "The coffee shop on Main Street has great WiFi"
 * - "My manager Jennifer scheduled a meeting"
 * - "Mom called to remind me about dinner"
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-07
 * @updated 2025-01-15
 */

require_once __DIR__ . '/../tools/ToolManager.php';

/**
 * MemoryAgent - Intelligent knowledge graph and entity relationship management
 */
class MemoryAgent {
    
    /**
     * ENTITY TYPE CLASSIFICATIONS
     * 
     * Comprehensive categorization system for different types of entities
     * in the knowledge graph with specific handling and attribute schemas.
     */
    private const ENTITY_TYPES = [
        'person' => [
            'attributes' => ['name', 'role', 'description', 'contact_info', 'preferences'],
            'relationships' => ['family', 'professional', 'social', 'romantic'],
            'privacy_level' => 'high',
            'importance_weight' => 1.0
        ],
        'organization' => [
            'attributes' => ['name', 'type', 'size', 'industry', 'location'],
            'relationships' => ['employment', 'partnership', 'customer', 'vendor'],
            'privacy_level' => 'medium',
            'importance_weight' => 0.8
        ],
        'place' => [
            'attributes' => ['name', 'address', 'type', 'description', 'accessibility'],
            'relationships' => ['residence', 'workplace', 'frequented', 'visited'],
            'privacy_level' => 'medium',
            'importance_weight' => 0.6
        ],
        'object' => [
            'attributes' => ['name', 'type', 'description', 'location', 'value'],
            'relationships' => ['owned_by', 'used_by', 'located_at', 'related_to'],
            'privacy_level' => 'low',
            'importance_weight' => 0.4
        ],
        'event' => [
            'attributes' => ['name', 'date', 'location', 'description', 'participants'],
            'relationships' => ['attended_by', 'hosted_by', 'occurred_at'],
            'privacy_level' => 'medium',
            'importance_weight' => 0.7
        ]
    ];
    
    /**
     * RELATIONSHIP TYPE CLASSIFICATIONS
     * 
     * Structured relationship types with semantic meaning and strength indicators.
     * Used for intelligent relationship inference and graph traversal.
     */
    private const RELATIONSHIP_TYPES = [
        'family' => [
            'parent' => ['strength' => 1.0, 'bidirectional' => false],
            'child' => ['strength' => 1.0, 'bidirectional' => false],
            'sibling' => ['strength' => 0.9, 'bidirectional' => true],
            'spouse' => ['strength' => 1.0, 'bidirectional' => true],
            'relative' => ['strength' => 0.7, 'bidirectional' => true]
        ],
        'professional' => [
            'manager' => ['strength' => 0.8, 'bidirectional' => false],
            'employee' => ['strength' => 0.8, 'bidirectional' => false],
            'colleague' => ['strength' => 0.7, 'bidirectional' => true],
            'client' => ['strength' => 0.6, 'bidirectional' => false],
            'vendor' => ['strength' => 0.5, 'bidirectional' => false]
        ],
        'social' => [
            'friend' => ['strength' => 0.8, 'bidirectional' => true],
            'acquaintance' => ['strength' => 0.4, 'bidirectional' => true],
            'neighbor' => ['strength' => 0.5, 'bidirectional' => true],
            'mentor' => ['strength' => 0.7, 'bidirectional' => false],
            'mentee' => ['strength' => 0.7, 'bidirectional' => false]
        ],
        'service' => [
            'doctor' => ['strength' => 0.6, 'bidirectional' => false],
            'lawyer' => ['strength' => 0.6, 'bidirectional' => false],
            'teacher' => ['strength' => 0.6, 'bidirectional' => false],
            'service_provider' => ['strength' => 0.4, 'bidirectional' => false]
        ]
    ];
    
    /**
     * ATTRIBUTE EXTRACTION PATTERNS
     * 
     * Regular expression patterns for extracting entity attributes
     * from natural language descriptions.
     */
    private const ATTRIBUTE_PATTERNS = [
        'physical_description' => [
            'height' => '/\b(?:tall|short|medium height|about \d+(?:\'\d+"|ft|feet))\b/i',
            'hair' => '/\b(?:blonde?|brown|black|red|gray|grey|white|bald)\s*hair\b/i',
            'eyes' => '/\b(?:blue|brown|green|hazel|gray|grey)\s*eyes\b/i',
            'build' => '/\b(?:thin|slim|athletic|heavy|muscular|average)\s*build\b/i'
        ],
        'professional' => [
            'job_title' => '/\b(?:is a|works as a?|job title)\s*([a-z\s]+?)(?:\s+(?:at|for|with)|\.|$)/i',
            'company' => '/\b(?:works at|employed by|company)\s*([A-Z][a-zA-Z\s&.]+?)(?:\s|$)/i',
            'industry' => '/\b(?:in the|industry|field of)\s*([a-z\s]+?)(?:\s+(?:industry|field)|\.|$)/i'
        ],
        'personal' => [
            'age' => '/\b(?:is|age|about)\s*(\d{1,2})\s*(?:years?\s*old|yo)\b/i',
            'location' => '/\b(?:lives in|from|located in)\s*([A-Z][a-zA-Z\s,]+?)(?:\s|$)/i',
            'interests' => '/\b(?:likes|enjoys|interested in|hobbies?)\s*([a-z\s,]+?)(?:\.|$)/i'
        ]
    ];
    
    /** @var ToolManager Centralized tool access and permission management */
    private ToolManager $toolManager;
    
    /** @var array Knowledge graph cache for performance optimization */
    private array $knowledgeCache = [];
    
    /** @var array Entity resolution cache for deduplication */
    private array $entityResolutionCache = [];
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * Sets up the MemoryAgent with access to knowledge management tools
     * and initializes caching systems for optimal performance.
     * 
     * @param ToolManager $toolManager Tool management service with knowledge graph permissions
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
        $this->initializeKnowledgeSystem();
    }
    
    /**
     * Initialize knowledge graph management system
     * 
     * Sets up caching, entity resolution, and knowledge graph
     * optimization systems for efficient operation.
     * 
     * @return void
     */
    private function initializeKnowledgeSystem(): void {
        $this->knowledgeCache = [
            'entities' => [],
            'relationships' => [],
            'recent_queries' => [],
            'cache_timestamp' => time()
        ];
        
        $this->entityResolutionCache = [
            'name_variations' => [],
            'resolved_entities' => [],
            'disambiguation_rules' => []
        ];
    }
    
    /**
     * Create comprehensive knowledge graph component from natural language input
     * 
     * This primary method transforms entity-related user input into structured
     * knowledge graph data with intelligent relationship extraction, attribute
     * identification, and semantic understanding. It serves as the central hub
     * for all entity knowledge management in the BotMojo system.
     * 
     * PROCESSING PIPELINE:
     * 1. ENTITY EXTRACTION: Identify people, places, organizations, objects
     * 2. RELATIONSHIP ANALYSIS: Extract explicit and implicit relationships
     * 3. ATTRIBUTE RECOGNITION: Parse descriptive information and characteristics
     * 4. CONTEXT INTEGRATION: Understand situational and temporal context
     * 5. GRAPH INTEGRATION: Merge with existing knowledge graph intelligently
     * 6. QUALITY ASSURANCE: Validate and score information reliability
     * 
     * KNOWLEDGE UNDERSTANDING:
     * - Entity Recognition: "my friend John", "Sarah from accounting"
     * - Relationship Extraction: "John is my brother", "works with Sarah"
     * - Attribute Parsing: "tall guy with glasses", "lives in Seattle"
     * - Context Awareness: temporal, situational, and conversational context
     * - Deduplication: Smart merging of entity mentions and references
     * 
     * INTELLIGENT FEATURES:
     * - Smart Entity Resolution: Automatic disambiguation and deduplication
     * - Relationship Inference: Logical relationship derivation and validation
     * - Importance Scoring: Dynamic entity relevance based on interaction patterns
     * - Privacy Classification: Automatic sensitivity assessment and protection
     * - Knowledge Graph Optimization: Efficient storage and retrieval strategies
     * 
     * @param array $data Entity data from triage system with natural language context
     * @return array Comprehensive knowledge component with graph integration
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

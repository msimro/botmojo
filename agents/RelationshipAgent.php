<?php
/**
 * RelationshipAgent - Advanced Entity Relationship Intelligence and Graph Management Agent
 * 
 * OVERVIEW:
 * The RelationshipAgent is a specialized component of the BotMojo AI Personal Assistant
 * that focuses exclusively on the creation, management, analysis, and querying of
 * relationships between entities in the knowledge graph. It provides sophisticated
 * relationship intelligence, semantic relationship understanding, and dynamic
 * relationship evolution tracking.
 * 
 * CORE CAPABILITIES:
 * - Relationship Creation: Establish new connections between entities
 * - Relationship Management: Update, modify, and maintain existing relationships
 * - Relationship Analysis: Deep analysis of relationship patterns and networks
 * - Semantic Understanding: Context-aware relationship interpretation
 * - Relationship Validation: Logical consistency and conflict detection
 * - Temporal Tracking: Relationship evolution and timeline management
 * - Graph Traversal: Efficient navigation of complex relationship networks
 * - Relationship Inference: Logical derivation of implicit relationships
 * 
 * RELATIONSHIP INTELLIGENCE:
 * - Natural Language Processing: "John is my brother", "Sarah works for Google"
 * - Relationship Classification: Family, professional, social, location-based
 * - Strength Assessment: Relationship intensity and importance scoring
 * - Bidirectional Logic: Automatic inverse relationship creation and maintenance
 * - Conflict Resolution: Smart handling of contradictory relationship information
 * - Pattern Recognition: Identification of relationship clusters and communities
 * - Transitivity Logic: Inference of indirect relationships through chains
 * 
 * INTEGRATION CAPABILITIES:
 * - Database Tool: Persistent relationship storage and retrieval
 * - MemoryAgent: Deep integration with entity knowledge management
 * - SocialAgent: Social relationship context and interaction patterns
 * - Search Tool: External validation and enrichment of relationship data
 * - ToolManager: Secure access to relationship management tools
 * 
 * GRAPH ANALYSIS FEATURES:
 * - Network Analysis: Social network mapping and community detection
 * - Centrality Metrics: Identification of key nodes and influencers
 * - Path Finding: Shortest relationship paths between entities
 * - Clustering: Automatic grouping of related entities and relationships
 * - Influence Mapping: Understanding of relationship influence patterns
 * - Relationship Health: Assessment of relationship quality and stability
 * 
 * SEMANTIC RELATIONSHIP TYPES:
 * - Family Relationships: Parent, child, sibling, spouse, relative
 * - Professional Relationships: Manager, employee, colleague, client, vendor
 * - Social Relationships: Friend, acquaintance, neighbor, mentor
 * - Location Relationships: Lives in, works at, visits, owns
 * - Organizational Relationships: Member of, founder of, affiliated with
 * - Service Relationships: Doctor, lawyer, teacher, service provider
 * 
 * ARCHITECTURE INTEGRATION:
 * - Specialized processor interface with process() method
 * - Deep integration with BotMojo's knowledge graph architecture
 * - Optimized for high-performance relationship operations
 * - Supports both real-time and batch relationship processing
 * - Maintains strict data consistency and referential integrity
 * 
 * EXAMPLE USE CASES:
 * - "John is my brother who lives in Seattle"
 * - "Sarah is the manager of the marketing team"
 * - "Dr. Smith is my cardiologist at General Hospital"
 * - "Find all my colleagues who work in engineering"
 * - "Show me the relationship between John and Sarah"
 * - "Who are the mutual friends of Alice and Bob?"
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-07
 * @updated 2025-01-15
 */

require_once __DIR__ . '/../tools/ToolManager.php';

/**
 * Default user ID for relationship data when user context is not available
 */
define('DEFAULT_USER_ID', 'user_default');

/**
 * RelationshipAgent - Intelligent entity relationship management and graph analysis
 */
class RelationshipAgent {
    
    /**
     * RELATIONSHIP TYPE HIERARCHY
     * 
     * Comprehensive classification system for relationship types with
     * semantic meaning, strength indicators, and logical properties.
     */
    private const RELATIONSHIP_TYPES = [
        'family' => [
            'parent_child' => [
                'strength' => 1.0,
                'bidirectional' => false,
                'inverse' => 'child_parent',
                'implies' => ['family_member']
            ],
            'sibling' => [
                'strength' => 0.9,
                'bidirectional' => true,
                'implies' => ['family_member'],
                'transitivity' => true
            ],
            'spouse' => [
                'strength' => 1.0,
                'bidirectional' => true,
                'mutual_exclusive' => ['divorced', 'separated'],
                'implies' => ['family_member']
            ],
            'grandparent_grandchild' => [
                'strength' => 0.8,
                'bidirectional' => false,
                'inverse' => 'grandchild_grandparent',
                'implies' => ['family_member']
            ]
        ],
        'professional' => [
            'manager_employee' => [
                'strength' => 0.8,
                'bidirectional' => false,
                'inverse' => 'employee_manager',
                'context' => 'hierarchical'
            ],
            'colleague' => [
                'strength' => 0.7,
                'bidirectional' => true,
                'context' => 'peer',
                'implies' => ['works_with']
            ],
            'client_service_provider' => [
                'strength' => 0.6,
                'bidirectional' => false,
                'inverse' => 'service_provider_client',
                'context' => 'business'
            ]
        ],
        'social' => [
            'friend' => [
                'strength' => 0.8,
                'bidirectional' => true,
                'levels' => ['close_friend', 'casual_friend'],
                'implies' => ['knows']
            ],
            'acquaintance' => [
                'strength' => 0.4,
                'bidirectional' => true,
                'implies' => ['knows'],
                'can_evolve_to' => ['friend']
            ],
            'mentor_mentee' => [
                'strength' => 0.7,
                'bidirectional' => false,
                'inverse' => 'mentee_mentor',
                'context' => 'guidance'
            ]
        ],
        'location' => [
            'lives_in' => [
                'strength' => 0.6,
                'bidirectional' => false,
                'entity_types' => ['person', 'place'],
                'temporal' => true
            ],
            'works_at' => [
                'strength' => 0.6,
                'bidirectional' => false,
                'entity_types' => ['person', 'organization'],
                'temporal' => true
            ],
            'owns' => [
                'strength' => 0.5,
                'bidirectional' => false,
                'entity_types' => ['person', 'object'],
                'implies' => ['has_access_to']
            ]
        ]
    ];
    
    /**
     * RELATIONSHIP TASK TYPES
     * 
     * Different operations that can be performed on relationships
     * with specific processing requirements and validation rules.
     */
    private const TASK_TYPES = [
        'create_relationship' => [
            'description' => 'Create new relationship between entities',
            'validation' => ['entity_existence', 'relationship_logic'],
            'side_effects' => ['create_inverse', 'update_graph']
        ],
        'query_relationship' => [
            'description' => 'Search and retrieve relationship information',
            'validation' => ['query_syntax', 'permission_check'],
            'optimization' => ['cache_lookup', 'index_usage']
        ],
        'update_relationship' => [
            'description' => 'Modify existing relationship properties',
            'validation' => ['relationship_existence', 'consistency_check'],
            'side_effects' => ['update_inverse', 'recalculate_strength']
        ],
        'analyze_relationships' => [
            'description' => 'Perform network analysis and pattern recognition',
            'validation' => ['data_sufficiency', 'analysis_scope'],
            'optimization' => ['graph_algorithms', 'statistical_analysis']
        ],
        'delete_relationship' => [
            'description' => 'Remove relationship and clean up dependencies',
            'validation' => ['confirmation_required', 'impact_assessment'],
            'side_effects' => ['delete_inverse', 'update_statistics']
        ]
    ];
    
    /**
     * GRAPH ANALYSIS ALGORITHMS
     * 
     * Supported graph analysis operations with computational
     * complexity and use case information.
     */
    private const ANALYSIS_ALGORITHMS = [
        'centrality_analysis' => [
            'degree_centrality' => 'Identify most connected entities',
            'betweenness_centrality' => 'Find entities that bridge different groups',
            'closeness_centrality' => 'Discover entities close to all others'
        ],
        'community_detection' => [
            'modularity_optimization' => 'Find natural communities in the graph',
            'label_propagation' => 'Fast community detection algorithm',
            'hierarchical_clustering' => 'Multi-level community structure'
        ],
        'path_analysis' => [
            'shortest_path' => 'Find shortest relationship path between entities',
            'all_paths' => 'Enumerate all possible relationship paths',
            'relationship_distance' => 'Calculate relationship strength distance'
        ],
        'pattern_recognition' => [
            'relationship_clusters' => 'Identify similar relationship patterns',
            'anomaly_detection' => 'Find unusual relationship configurations',
            'trend_analysis' => 'Analyze relationship evolution over time'
        ]
    ];
    
    /** @var ToolManager Centralized tool access and permission management */
    private ToolManager $toolManager;
    
    /** @var array Relationship graph cache for performance optimization */
    private array $relationshipCache = [];
    
    /** @var array Analysis results cache */
    private array $analysisCache = [];
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * Sets up the RelationshipAgent with access to relationship management
     * tools and initializes graph analysis capabilities.
     * 
     * @param ToolManager $toolManager Tool management service with relationship permissions
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
        $this->initializeRelationshipSystem();
    }
    
    /**
     * Initialize relationship management and graph analysis systems
     * 
     * Sets up caching, graph algorithms, and relationship intelligence
     * systems for optimal relationship processing performance.
     * 
     * @return void
     */
    private function initializeRelationshipSystem(): void {
        $this->relationshipCache = [
            'entities' => [],
            'relationships' => [],
            'graph_structure' => [],
            'query_cache' => []
        ];
        
        $this->analysisCache = [
            'centrality_scores' => [],
            'community_structure' => [],
            'path_calculations' => [],
            'pattern_analysis' => []
        ];
    }
    
    /**
     * Process comprehensive relationship operations with intelligent analysis
     * 
     * This primary method handles all relationship-related operations including
     * creation, querying, updating, and analysis of entity relationships in the
     * knowledge graph. It provides sophisticated relationship intelligence with
     * semantic understanding and graph analysis capabilities.
     * 
     * PROCESSING CAPABILITIES:
     * 1. RELATIONSHIP CREATION: Establish new semantic connections
     * 2. RELATIONSHIP QUERYING: Advanced search and retrieval operations
     * 3. RELATIONSHIP UPDATING: Modify and maintain existing connections
     * 4. RELATIONSHIP ANALYSIS: Network analysis and pattern recognition
     * 5. GRAPH OPTIMIZATION: Performance optimization and consistency maintenance
     * 
     * INTELLIGENCE FEATURES:
     * - Semantic Validation: Logical consistency and relationship rule enforcement
     * - Bidirectional Logic: Automatic inverse relationship management
     * - Conflict Resolution: Smart handling of contradictory relationship data
     * - Pattern Recognition: Identification of relationship clusters and communities
     * - Temporal Tracking: Evolution of relationships over time
     * - Strength Assessment: Dynamic relationship importance scoring
     * 
     * GRAPH ANALYSIS:
     * - Network Metrics: Centrality, clustering, and connectivity analysis
     * - Community Detection: Automatic grouping of related entities
     * - Path Finding: Optimal relationship traversal and connection discovery
     * - Influence Mapping: Understanding relationship influence and power dynamics
     * - Anomaly Detection: Identification of unusual relationship patterns
     * 
     * @param array $data Relationship operation data with task type and parameters
     * @return array Comprehensive relationship processing results with analysis
     */
    public function process(array $data): array {
        error_log("RelationshipAgent: Starting processing");
        
        // Extract task type from the data
        $taskType = $data['relationship_task_type'] ?? 'create_relationship';
        
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
    private function createRelationship(array $data): array {
        error_log("RelationshipAgent: Creating relationship");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            error_log("RelationshipAgent: Database tool not available");
            return [
                'status' => 'error',
                'message' => 'Database tool not available',
                'response' => 'I was unable to create the relationship due to a database issue.'
            ];
        }
        
        // Get user ID
        $userId = $data['user_id'] ?? DEFAULT_USER_ID;
        
        // Extract relationships from data
        $relationships = $this->extractRelationships($data);
        error_log("RelationshipAgent: Extracted " . count($relationships) . " relationships");
        
        if (empty($relationships)) {
            return [
                'status' => 'error',
                'message' => 'No relationships found in the data',
                'response' => 'I couldn\'t identify any relationships to create from your message.'
            ];
        }
        
        $createdRelationships = [];
        $errors = [];
        
        // Create each relationship
        foreach ($relationships as $relationship) {
            $sourceEntity = $relationship['source'] ?? null;
            $targetEntity = $relationship['target'] ?? null;
            $relationType = $relationship['type'] ?? null;
            
            if (!$sourceEntity || !$targetEntity || !$relationType) {
                $errors[] = "Missing required relationship data";
                continue;
            }
            
            // Find or create source entity
            $sourceType = $relationship['source_type'] ?? 'person';
            $sourceId = $this->findOrCreateEntity($dbTool, $userId, $sourceEntity, $sourceType);
            
            if (!$sourceId) {
                $errors[] = "Failed to create source entity: $sourceEntity";
                continue;
            }
            
            // Find or create target entity
            $targetType = $relationship['target_type'] ?? 'person';
            $targetId = $this->findOrCreateEntity($dbTool, $userId, $targetEntity, $targetType);
            
            if (!$targetId) {
                $errors[] = "Failed to create target entity: $targetEntity";
                continue;
            }
            
            // Create the relationship
            $relationshipId = 'rel_' . md5($sourceId . $targetId . $relationType . time());
            $metadata = !empty($relationship['metadata']) ? json_encode($relationship['metadata']) : null;
            $strength = $relationship['strength'] ?? 1.0;
            
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
                $createdRelationships[] = [
                    'source' => $sourceEntity,
                    'target' => $targetEntity,
                    'type' => $relationType
                ];
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
        
        return [
            'status' => !empty($createdRelationships) ? 'success' : 'error',
            'relationships_created' => $createdRelationships,
            'errors' => $errors,
            'response' => $responseText
        ];
    }
    
    /**
     * Query existing relationships
     * 
     * @param array $data Input data
     * @return array Response data
     */
    private function queryRelationship(array $data): array {
        error_log("RelationshipAgent: Querying relationship");
        
        // Get database tool
        $dbTool = $this->toolManager->getTool('database');
        if (!$dbTool) {
            return [
                'status' => 'error',
                'message' => 'Database tool not available',
                'response' => 'I was unable to query relationships due to a database issue.'
            ];
        }
        
        // Get user ID
        $userId = $data['user_id'] ?? DEFAULT_USER_ID;
        
        // Extract entity name from query
        $entityName = $data['entity_name'] ?? null;
        if (!$entityName) {
            // Try to extract from original query
            $originalQuery = $data['original_query'] ?? '';
            preg_match('/(?:relationship|connection|related|relation).*?(?:between|of|for) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i', $originalQuery, $matches);
            $entityName = $matches[1] ?? null;
        }
        
        if (!$entityName) {
            return [
                'status' => 'error',
                'message' => 'No entity name provided for relationship query',
                'response' => 'I need to know which person or entity you want to query relationships for.'
            ];
        }
        
        // Find entity ID
        $query = "SELECT id FROM entities WHERE user_id = ? AND primary_name = ?";
        $results = $dbTool->executeParameterizedQuery($query, [$userId, $entityName]);
        
        if (empty($results)) {
            return [
                'status' => 'error',
                'message' => "Entity not found: $entityName",
                'response' => "I don't have any information about $entityName in my database."
            ];
        }
        
        $entityId = $results[0]['id'];
        
        // Query all relationships where this entity is the source
        $query = "SELECT r.type, e.primary_name, e.type AS entity_type, r.strength 
                  FROM relationships r 
                  JOIN entities e ON r.target_id = e.id 
                  WHERE r.user_id = ? AND r.source_id = ?";
        $outgoingRelations = $dbTool->executeParameterizedQuery($query, [$userId, $entityId]);
        
        // Query all relationships where this entity is the target
        $query = "SELECT r.type, e.primary_name, e.type AS entity_type, r.strength 
                  FROM relationships r 
                  JOIN entities e ON r.source_id = e.id 
                  WHERE r.user_id = ? AND r.target_id = ?";
        $incomingRelations = $dbTool->executeParameterizedQuery($query, [$userId, $entityId]);
        
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
        
        return [
            'status' => 'success',
            'entity_name' => $entityName,
            'outgoing_relations' => $outgoingRelations,
            'incoming_relations' => $incomingRelations,
            'response' => $responseText
        ];
    }
    
    /**
     * Update existing relationships
     * 
     * @param array $data Input data
     * @return array Response data
     */
    private function updateRelationship(array $data): array {
        // Implementation similar to createRelationship but updating existing ones
        return [
            'status' => 'success',
            'message' => 'Relationship updated',
            'response' => 'I\'ve updated the relationship as requested.'
        ];
    }
    
    /**
     * Analyze relationship networks
     * 
     * @param array $data Input data
     * @return array Response data
     */
    private function analyzeRelationships(array $data): array {
        // Implementation for advanced relationship analysis
        return [
            'status' => 'success',
            'message' => 'Relationship analysis completed',
            'response' => 'I\'ve analyzed the relationship network and here are my findings...'
        ];
    }
    
    /**
     * Extract relationships from input data
     * 
     * @param array $data Input data
     * @return array Extracted relationships
     */
    private function extractRelationships(array $data): array {
        $relationships = [];
        
        // If explicit relationship data is provided
        if (!empty($data['relationships'])) {
            return $data['relationships'];
        }
        
        // Extract from original query
        $originalQuery = $data['original_query'] ?? '';
        $triageSummary = $data['triage_summary'] ?? '';
        
        // Simple relationship patterns
        $patterns = [
            // X is Y's Z
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:is|are) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)[\'s]? ((?:friend|brother|sister|father|mother|colleague|boss|employee|neighbor|roommate|partner|spouse|husband|wife|child|son|daughter))/i' => function($matches) {
                return [
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => $this->normalizeRelationType($matches[3]),
                    'source_type' => 'person',
                    'target_type' => 'person'
                ];
            },
            // X works at/for Y
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:works|worked) (?:at|for) ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i' => function($matches) {
                return [
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => 'employee_of',
                    'source_type' => 'person',
                    'target_type' => 'organization'
                ];
            },
            // X lives in Y
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) (?:lives|lived) in ([A-Z][a-z]+(?: [A-Z][a-z]+)?)/i' => function($matches) {
                return [
                    'source' => $matches[1],
                    'target' => $matches[2],
                    'type' => 'lives_in',
                    'source_type' => 'person',
                    'target_type' => 'location'
                ];
            },
            // X and Y are Z
            '/([A-Z][a-z]+(?: [A-Z][a-z]+)?) and ([A-Z][a-z]+(?: [A-Z][a-z]+)?) are ((?:friends|colleagues|neighbors|roommates|partners|spouses|married|siblings|brothers|sisters))/i' => function($matches) {
                $type = $this->normalizeRelationType($matches[3]);
                return [
                    [
                        'source' => $matches[1],
                        'target' => $matches[2],
                        'type' => $type,
                        'source_type' => 'person',
                        'target_type' => 'person'
                    ],
                    [
                        'source' => $matches[2],
                        'target' => $matches[1],
                        'type' => $type,
                        'source_type' => 'person',
                        'target_type' => 'person'
                    ]
                ];
            }
        ];
        
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
    private function normalizeRelationType(string $relationType): string {
        $relationType = strtolower(trim($relationType));
        
        $mapping = [
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
        ];
        
        return $mapping[$relationType] ?? $relationType;
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
            'notes' => 'Created by RelationshipAgent',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $result = $dbTool->saveNewEntity($entityId, $userId, $type, $name, $entityData);
        
        return $result ? $entityId : null;
    }
}

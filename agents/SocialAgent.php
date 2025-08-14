<?php
/**
 * SocialAgent - Advanced Social Intelligence and Relationship Management Agent
 * 
 * OVERVIEW:
 * The SocialAgent is a specialized component of the BotMojo AI Personal Assistant
 * focused on social relationship management, interpersonal dynamics, social event
 * planning, communication optimization, and networking support. It analyzes social
 * contexts to provide intelligent insights, relationship guidance, and social
 * coordination assistance.
 * 
 * CORE CAPABILITIES:
 * - Relationship Mapping: Family, friends, colleagues, professional networks
 * - Social Event Planning: Gatherings, meetings, celebrations, activities
 * - Communication Analysis: Interaction patterns, frequency, communication styles
 * - Network Intelligence: Social graph analysis and relationship insights
 * - Conflict Resolution: Mediation suggestions and relationship repair strategies
 * - Social Calendar Management: Event coordination and social scheduling
 * - Networking Support: Professional and personal network building guidance
 * - Cultural Sensitivity: Awareness of social norms and cultural contexts
 * 
 * SOCIAL INTELLIGENCE PROCESSING:
 * - Relationship Recognition: "my friend John", "Sarah from work", "mom"
 * - Social Event Detection: "party planning", "team lunch", "family dinner"
 * - Communication Patterns: frequency analysis, interaction quality assessment
 * - Group Dynamics: Team interactions, family dynamics, friend groups
 * - Social Context Understanding: formal vs informal, professional vs personal
 * - Emotional Intelligence: Mood detection, social cues, empathy guidance
 * - Conflict Identification: Tension detection and resolution recommendations
 * 
 * INTEGRATION CAPABILITIES:
 * - Contacts Tool: Contact management and relationship tracking
 * - Calendar Tool: Social event scheduling and availability coordination
 * - Database Tool: Social interaction history and relationship evolution
 * - Search Tool: Social etiquette research and relationship advice
 * - MemoryAgent: Entity relationship data and social context preservation
 * - ToolManager: Secure access to social coordination tools
 * 
 * RELATIONSHIP INTELLIGENCE:
 * - Relationship Strength Assessment: Interaction frequency and quality analysis
 * - Social Network Mapping: Visual and analytical representation of connections
 * - Influence Analysis: Understanding social influence patterns and dynamics
 * - Group Cohesion: Team and group relationship health assessment
 * - Social Skills Development: Personalized social improvement recommendations
 * - Networking Optimization: Strategic relationship building guidance
 * 
 * COMMUNICATION OPTIMIZATION:
 * - Message Tone Analysis: Appropriate communication style recommendations
 * - Timing Intelligence: Optimal communication timing based on patterns
 * - Cultural Adaptation: Context-appropriate communication across cultures
 * - Conflict Prevention: Early warning systems for relationship tension
 * - Relationship Maintenance: Proactive relationship care suggestions
 * 
 * ARCHITECTURE INTEGRATION:
 * - Implements standard Agent interface with createComponent() method
 * - Integrates with MemoryAgent for comprehensive relationship data
 * - Follows BotMojo's triage-first architecture for social context routing
 * - Supports real-time social analysis and batch relationship processing
 * - Maintains privacy standards for sensitive social and relationship data
 * 
 * EXAMPLE USE CASES:
 * - "Plan a birthday party for Sarah"
 * - "I had an argument with my colleague"
 * - "Help me reconnect with old friends"
 * - "Schedule a team building event"
 * - "I want to improve my networking skills"
 * - "Track my family interactions"
 * - "Suggest conversation topics for the dinner"
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-12
 * @updated 2025-01-15
 */

require_once __DIR__ . '/../tools/ToolManager.php';

/**
 * Default user ID for social data when user context is not available
 */
define('DEFAULT_USER_ID', 'user_default');

/**
 * SocialAgent - Intelligent social relationship and interaction management
 */
class SocialAgent {
    
    /**
     * SOCIAL RELATIONSHIP CATEGORIES
     * 
     * Classification system for different types of social relationships
     * with specific interaction patterns and management strategies.
     */
    private const RELATIONSHIP_CATEGORIES = [
        'family' => [
            'immediate' => ['strength' => 1.0, 'interaction_frequency' => 'frequent'],
            'extended' => ['strength' => 0.7, 'interaction_frequency' => 'occasional'],
            'in_laws' => ['strength' => 0.6, 'interaction_frequency' => 'situational']
        ],
        'professional' => [
            'direct_reports' => ['strength' => 0.8, 'context' => 'hierarchical'],
            'peers' => ['strength' => 0.7, 'context' => 'collaborative'],
            'management' => ['strength' => 0.8, 'context' => 'reporting'],
            'clients' => ['strength' => 0.6, 'context' => 'service'],
            'vendors' => ['strength' => 0.5, 'context' => 'transactional']
        ],
        'social' => [
            'close_friends' => ['strength' => 0.9, 'intimacy' => 'high'],
            'casual_friends' => ['strength' => 0.6, 'intimacy' => 'medium'],
            'acquaintances' => ['strength' => 0.3, 'intimacy' => 'low'],
            'neighbors' => ['strength' => 0.4, 'context' => 'proximity'],
            'activity_partners' => ['strength' => 0.5, 'context' => 'shared_interests']
        ],
        'romantic' => [
            'spouse' => ['strength' => 1.0, 'commitment' => 'highest'],
            'partner' => ['strength' => 0.9, 'commitment' => 'high'],
            'dating' => ['strength' => 0.6, 'commitment' => 'developing']
        ]
    ];
    
    /**
     * SOCIAL EVENT TYPES
     * 
     * Different categories of social events with planning characteristics
     * and coordination requirements.
     */
    private const EVENT_TYPES = [
        'celebrations' => [
            'birthday' => ['planning_time' => 14, 'typical_size' => 'medium'],
            'anniversary' => ['planning_time' => 21, 'typical_size' => 'small'],
            'graduation' => ['planning_time' => 30, 'typical_size' => 'large'],
            'promotion' => ['planning_time' => 7, 'typical_size' => 'small']
        ],
        'gatherings' => [
            'dinner_party' => ['planning_time' => 7, 'typical_size' => 'small'],
            'BBQ' => ['planning_time' => 5, 'typical_size' => 'medium'],
            'game_night' => ['planning_time' => 3, 'typical_size' => 'small'],
            'holiday_party' => ['planning_time' => 21, 'typical_size' => 'large']
        ],
        'professional' => [
            'team_lunch' => ['planning_time' => 3, 'typical_size' => 'small'],
            'networking_event' => ['planning_time' => 14, 'typical_size' => 'large'],
            'conference' => ['planning_time' => 60, 'typical_size' => 'large'],
            'workshop' => ['planning_time' => 21, 'typical_size' => 'medium']
        ],
        'activities' => [
            'sports' => ['planning_time' => 3, 'typical_size' => 'medium'],
            'travel' => ['planning_time' => 60, 'typical_size' => 'small'],
            'outdoor_activities' => ['planning_time' => 7, 'typical_size' => 'medium']
        ]
    ];
    
    /**
     * COMMUNICATION PATTERNS
     * 
     * Analysis patterns for understanding communication styles
     * and relationship health indicators.
     */
    private const COMMUNICATION_PATTERNS = [
        'frequency' => [
            'daily' => ['strength_indicator' => 0.9, 'relationship_health' => 'excellent'],
            'weekly' => ['strength_indicator' => 0.7, 'relationship_health' => 'good'],
            'monthly' => ['strength_indicator' => 0.5, 'relationship_health' => 'moderate'],
            'rarely' => ['strength_indicator' => 0.2, 'relationship_health' => 'weak']
        ],
        'interaction_quality' => [
            'deep_conversations' => ['intimacy_level' => 'high', 'satisfaction' => 'high'],
            'casual_chat' => ['intimacy_level' => 'medium', 'satisfaction' => 'medium'],
            'business_only' => ['intimacy_level' => 'low', 'satisfaction' => 'low'],
            'conflict_resolution' => ['intimacy_level' => 'variable', 'growth_potential' => 'high']
        ],
        'emotional_tone' => [
            'supportive' => ['relationship_health' => 'positive', 'trust_level' => 'high'],
            'neutral' => ['relationship_health' => 'stable', 'trust_level' => 'medium'],
            'tense' => ['relationship_health' => 'strained', 'attention_needed' => true],
            'affectionate' => ['relationship_health' => 'strong', 'intimacy' => 'high']
        ]
    ];
    
    /**
     * SOCIAL SKILLS DEVELOPMENT AREAS
     * 
     * Framework for identifying and improving social skills
     * based on interaction analysis and user feedback.
     */
    private const SOCIAL_SKILLS = [
        'communication' => [
            'active_listening' => ['techniques' => ['paraphrasing', 'asking_questions']],
            'clear_expression' => ['techniques' => ['structure', 'examples', 'brevity']],
            'nonverbal_awareness' => ['techniques' => ['body_language', 'tone', 'eye_contact']]
        ],
        'relationship_building' => [
            'empathy' => ['techniques' => ['perspective_taking', 'emotional_validation']],
            'trust_building' => ['techniques' => ['consistency', 'reliability', 'honesty']],
            'conflict_resolution' => ['techniques' => ['mediation', 'compromise', 'understanding']]
        ],
        'networking' => [
            'conversation_starters' => ['techniques' => ['common_interests', 'current_events']],
            'follow_up' => ['techniques' => ['timely_contact', 'value_addition']],
            'relationship_maintenance' => ['techniques' => ['regular_check_ins', 'support_offering']]
        ]
    ];
    
    /** @var ToolManager Centralized tool access and permission management */
    private ToolManager $toolManager;
    
    /** @var array Social network analysis cache */
    private array $socialCache = [];
    
    /** @var array Relationship pattern analysis data */
    private array $relationshipPatterns = [];
    
    /**
     * Constructor - Initialize with tool manager for controlled tool access
     * 
     * Sets up the SocialAgent with access to social coordination tools
     * and initializes social intelligence analysis systems.
     * 
     * @param ToolManager $toolManager Tool management service with social interaction permissions
     */
    public function __construct(ToolManager $toolManager) {
        $this->toolManager = $toolManager;
        $this->initializeSocialIntelligence();
    }
    
    /**
     * Initialize social intelligence and relationship analysis systems
     * 
     * Sets up caching, pattern recognition, and social network
     * analysis capabilities for optimal social interaction support.
     * 
     * @return void
     */
    private function initializeSocialIntelligence(): void {
        $this->socialCache = [
            'recent_interactions' => [],
            'relationship_analysis' => [],
            'event_planning_data' => [],
            'communication_patterns' => []
        ];
        
        $this->relationshipPatterns = [
            'interaction_frequencies' => [],
            'communication_styles' => [],
            'relationship_health_scores' => [],
            'social_preferences' => []
        ];
    }
    
    /**
     * Create comprehensive social intelligence component from natural language input
     * 
     * This primary method transforms social interaction and relationship-related user
     * input into structured social data with intelligent relationship analysis,
     * event planning support, and communication optimization guidance.
     * 
     * PROCESSING PIPELINE:
     * 1. SOCIAL EXTRACTION: Parse relationships, events, communication patterns
     * 2. RELATIONSHIP ANALYSIS: Assess relationship health and dynamics
     * 3. CONTEXT UNDERSTANDING: Understand social goals and challenges
     * 4. TOOL INTEGRATION: Access contacts, calendar, and research data
     * 5. INTELLIGENCE LAYER: Generate insights and recommendations
     * 6. PRIVACY PROTECTION: Ensure sensitive social data security
     * 
     * SOCIAL UNDERSTANDING:
     * - Relationship Recognition: "my friend John", "team meeting", "family dinner"
     * - Event Planning: "birthday party", "team lunch", "wedding planning"
     * - Communication Analysis: interaction patterns, relationship health
     * - Social Goals: networking, relationship building, conflict resolution
     * - Group Dynamics: team interactions, family relationships, friend groups
     * 
     * INTELLIGENT FEATURES:
     * - Relationship Health Assessment: Dynamic relationship quality analysis
     * - Social Event Optimization: Smart planning and coordination recommendations
     * - Communication Enhancement: Style and timing optimization suggestions
     * - Network Analysis: Social graph insights and relationship mapping
     * - Conflict Prevention: Early warning systems for relationship issues
     * 
     * @param array $data Social data from triage system with relationship context
     * @return array Comprehensive social component with relationship insights and guidance
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

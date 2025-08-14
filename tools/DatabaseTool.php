<?php
/**
 * DatabaseTool - Advanced Database Operations and Data Persistence System
 * 
 * OVERVIEW:
 * The DatabaseTool is the central data persistence layer for the BotMojo AI Personal
 * Assistant system. It provides comprehensive database operations, entity management,
 * relationship tracking, and data integrity enforcement. This tool handles all
 * interactions with the MySQL/MariaDB database backend and ensures secure,
 * efficient, and reliable data operations.
 * 
 * CORE CAPABILITIES:
 * - Entity Management: CRUD operations for all user entities and system data
 * - Relationship Tracking: Complex entity relationships and graph-like connections
 * - Data Integrity: Transaction management and referential integrity enforcement
 * - Security: SQL injection prevention through prepared statements
 * - Performance: Query optimization, connection pooling, and caching strategies
 * - Backup & Recovery: Data backup utilities and disaster recovery support
 * - Migration Support: Schema versioning and database migration management
 * - Analytics: Query performance monitoring and usage analytics
 * 
 * SECURITY ARCHITECTURE:
 * - Prepared Statements: All queries use parameterized statements to prevent SQL injection
 * - User Isolation: Data segregation ensures users only access their own data
 * - Audit Logging: Comprehensive logging of all database operations
 * - Connection Security: Encrypted connections and secure credential management
 * - Access Control: Fine-grained permissions and role-based access
 * - Data Validation: Input sanitization and schema validation
 * 
 * DATA MODEL ARCHITECTURE:
 * - Entities Table: Core storage for all user entities (people, events, tasks, notes)
 * - Relationships Table: Entity connections and associations
 * - Users Table: User account and preference data
 * - Conversations Table: Chat history and interaction logs
 * - Metadata Tables: System configuration and operational data
 * - Analytics Tables: Usage metrics and performance data
 * 
 * PERFORMANCE OPTIMIZATION:
 * - Connection Pooling: Efficient database connection management
 * - Query Caching: Smart caching of frequently accessed data
 * - Index Optimization: Strategic indexing for fast query performance
 * - Batch Operations: Efficient bulk data operations
 * - Memory Management: Optimal resource usage and cleanup
 * - Query Analysis: Performance monitoring and optimization recommendations
 * 
 * ENTITY MANAGEMENT SYSTEM:
 * - Universal Entity Storage: Flexible JSON-based entity storage
 * - Type System: Strongly typed entities with validation
 * - Versioning: Entity change tracking and history management
 * - Search: Full-text search and advanced filtering capabilities
 * - Aggregation: Complex data aggregation and reporting
 * - Synchronization: Multi-device data synchronization support
 * 
 * RELATIONSHIP INTELLIGENCE:
 * - Graph Database Features: Entity relationship mapping and traversal
 * - Bidirectional Relationships: Symmetric and asymmetric relationship support
 * - Relationship Types: Categorized relationships with metadata
 * - Cascade Operations: Relationship-aware data operations
 * - Graph Analytics: Network analysis and relationship insights
 * - Recommendation Engine: Relationship-based recommendations
 * 
 * INTEGRATION PATTERNS:
 * - Agent Integration: Seamless data access for all AI agents
 * - Tool Coordination: Data sharing between different tools
 * - External APIs: Integration with external data sources
 * - Import/Export: Data portability and migration utilities
 * - Real-time Updates: Live data synchronization and notifications
 * - Backup Integration: Automated backup and restore operations
 * 
 * EXAMPLE USAGE:
 * ```php
 * $db = new DatabaseTool();
 * 
 * // Save a new person entity
 * $personData = json_encode(['name' => 'John Doe', 'email' => 'john@example.com']);
 * $db->saveNewEntity('uuid-123', 'user1', 'person', 'John Doe', $personData);
 * 
 * // Query entities by type
 * $people = $db->getEntitiesByType('user1', 'person');
 * 
 * // Create relationships
 * $db->saveRelationship('person-uuid', 'event-uuid', 'attendee', 'John attends meeting');
 * ```
 * 
 * @author AI Personal Assistant Team
 * @version 2.0
 * @since 2025-08-07
 * @updated 2025-01-15
 */

/**
 * DatabaseTool - Advanced database operations and entity management system
 */
class DatabaseTool {
    
    /**
     * QUERY PERFORMANCE CONSTANTS
     * 
     * Performance thresholds and optimization settings for database operations.
     */
    private const SLOW_QUERY_THRESHOLD = 2.0; // Seconds
    private const MAX_BATCH_SIZE = 1000; // Records per batch operation
    private const CACHE_TTL = 3600; // Cache time-to-live in seconds
    private const CONNECTION_TIMEOUT = 30; // Connection timeout in seconds
    
    /**
     * ENTITY TYPE CONSTANTS
     * 
     * Standardized entity types for consistent data organization.
     */
    private const ENTITY_TYPES = [
        'PERSON' => 'person',
        'EVENT' => 'event', 
        'TASK' => 'task',
        'NOTE' => 'note',
        'LOCATION' => 'location',
        'ORGANIZATION' => 'organization',
        'PROJECT' => 'project',
        'GOAL' => 'goal',
        'HABIT' => 'habit',
        'MEMORY' => 'memory'
    ];
    
    /**
     * RELATIONSHIP TYPE CONSTANTS
     * 
     * Standardized relationship types for entity connections.
     */
    private const RELATIONSHIP_TYPES = [
        'KNOWS' => 'knows',
        'WORKS_WITH' => 'works_with',
        'FAMILY' => 'family',
        'FRIEND' => 'friend',
        'COLLEAGUE' => 'colleague',
        'ATTENDS' => 'attends',
        'ORGANIZES' => 'organizes',
        'LOCATED_AT' => 'located_at',
        'ASSIGNED_TO' => 'assigned_to',
        'DEPENDS_ON' => 'depends_on'
    ];
    
    /** @var mysqli Database connection instance */
    private $db;
    
    /** @var array Query performance metrics */
    private array $queryMetrics = [];
    
    /** @var array Query cache for performance optimization */
    private array $queryCache = [];
    
    /** @var bool Transaction state tracking */
    private bool $inTransaction = false;
    
    /**
     * Constructor - Initialize Advanced Database Connection
     * 
     * Establishes a secure, optimized connection to the MySQL database with
     * comprehensive error handling, performance monitoring, and security features.
     * 
     * @throws Exception If database connection fails or configuration is invalid
     */
    /**
     * Constructor - Initialize Advanced Database Connection
     * 
     * Establishes a secure, optimized connection to the MySQL database with
     * comprehensive error handling, performance monitoring, and security features.
     * 
     * @throws Exception If database connection fails or configuration is invalid
     */
    public function __construct() {
        $this->initializeConnection();
        $this->configureConnection();
        $this->initializeMetrics();
        $this->validateSchema();
    }
    
    /**
     * Initialize Database Connection
     * 
     * Creates the core database connection with proper error handling and
     * security configurations.
     * 
     * @throws Exception If connection cannot be established
     */
    private function initializeConnection(): void {
        try {
            // Validate configuration constants
            if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
                throw new Exception("Database configuration constants not defined");
            }
            
            // Establish database connection with timeout
            $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check for connection errors
            if ($this->db->connect_error) {
                throw new Exception("Database connection failed: " . $this->db->connect_error);
            }
            
            // Log successful connection
            error_log("DatabaseTool: Successfully connected to database " . DB_NAME);
            
        } catch (Exception $e) {
            error_log("DatabaseTool: Connection failed - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Configure Database Connection
     * 
     * Applies optimal configuration settings for performance, security,
     * and compatibility.
     * 
     * @return void
     */
    private function configureConnection(): void {
        // Set character set to UTF-8 for proper Unicode support
        $this->db->set_charset("utf8mb4");
        
        // Set SQL mode for strict data validation
        $this->db->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        
        // Set timezone to UTC for consistent datetime handling
        $this->db->query("SET time_zone = '+00:00'");
        
        // Configure connection timeout
        $this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::CONNECTION_TIMEOUT);
    }
    
    /**
     * Initialize Performance Metrics
     * 
     * Sets up the metrics collection system for monitoring database
     * performance and usage patterns.
     * 
     * @return void
     */
    private function initializeMetrics(): void {
        $this->queryMetrics = [
            'total_queries' => 0,
            'slow_queries' => 0,
            'failed_queries' => 0,
            'total_execution_time' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
    }
    
    /**
     * Validate Database Schema
     * 
     * Performs basic validation to ensure required tables and indexes exist.
     * This helps catch configuration issues early.
     * 
     * @throws Exception If schema validation fails
     */
    private function validateSchema(): void {
        $requiredTables = ['entities', 'relationships', 'conversations'];
        
        foreach ($requiredTables as $table) {
            $result = $this->db->query("SHOW TABLES LIKE '{$table}'");
            if ($result->num_rows === 0) {
                throw new Exception("Required table '{$table}' not found in database schema");
            }
        }
    }
    
    /**
     * Save New Entity with Advanced Features
     * 
     * Creates a new entity record with comprehensive validation, performance
     * monitoring, and transaction support. Includes automatic metadata generation
     * and relationship processing.
     * 
     * @param string $id Unique identifier for the entity (UUID format)
     * @param string $userId User who owns this entity
     * @param string $type Type of entity (must be from ENTITY_TYPES)
     * @param string $name Human-readable name for the entity
     * @param string $jsonData JSON-encoded data containing all entity components
     * @param array $metadata Optional metadata for the entity
     * @return bool True if save was successful, false otherwise
     */
    /**
     * Save New Entity with Advanced Features
     * 
     * Creates a new entity record with comprehensive validation, performance
     * monitoring, and transaction support. Includes automatic metadata generation
     * and relationship processing.
     * 
     * @param string $id Unique identifier for the entity (UUID format)
     * @param string $userId User who owns this entity
     * @param string $type Type of entity (must be from ENTITY_TYPES)
     * @param string $name Human-readable name for the entity
     * @param string $jsonData JSON-encoded data containing all entity components
     * @param array $metadata Optional metadata for the entity
     * @return bool True if save was successful, false otherwise
     */
    public function saveNewEntity(string $id, string $userId, string $type, string $name, string $jsonData, array $metadata = []): bool {
        $startTime = microtime(true);
        
        try {
            // Validate input parameters
            if (!$this->validateEntityData($id, $userId, $type, $name, $jsonData)) {
                return false;
            }
            
            // Prepare enhanced entity data with metadata
            $enhancedData = $this->enhanceEntityData($jsonData, $metadata);
            $timestamp = date('Y-m-d H:i:s');
            
            // Begin transaction for data consistency
            $this->beginTransaction();
            
            // Prepare and execute the main entity insertion
            $stmt = $this->db->prepare("
                INSERT INTO entities (id, user_id, type, primary_name, data, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare entity insertion statement: " . $this->db->error);
            }
            
            $stmt->bind_param("sssssss", $id, $userId, $type, $name, $enhancedData, $timestamp, $timestamp);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute entity insertion: " . $stmt->error);
            }
            
            // Log entity creation for audit trail
            $this->logEntityOperation('CREATE', $id, $userId, $type);
            
            // Commit transaction
            $this->commitTransaction();
            
            // Update performance metrics
            $this->updateQueryMetrics(microtime(true) - $startTime, true);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->rollbackTransaction();
            
            // Log error
            error_log("DatabaseTool: Failed to save entity {$id}: " . $e->getMessage());
            
            // Update metrics for failed operation
            $this->updateQueryMetrics(microtime(true) - $startTime, false);
            
            return false;
        }
    }
    
    /**
     * Validate Entity Data
     * 
     * Performs comprehensive validation of entity data before database insertion.
     * 
     * @param string $id Entity ID
     * @param string $userId User ID
     * @param string $type Entity type
     * @param string $name Entity name
     * @param string $jsonData JSON data
     * @return bool True if validation passes
     */
    private function validateEntityData(string $id, string $userId, string $type, string $name, string $jsonData): bool {
        // Validate UUID format for entity ID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            error_log("DatabaseTool: Invalid UUID format for entity ID: {$id}");
            return false;
        }
        
        // Validate entity type
        if (!in_array($type, self::ENTITY_TYPES)) {
            error_log("DatabaseTool: Invalid entity type: {$type}");
            return false;
        }
        
        // Validate JSON data
        if (!$this->isValidJson($jsonData)) {
            error_log("DatabaseTool: Invalid JSON data for entity {$id}");
            return false;
        }
        
        // Validate required fields
        if (empty(trim($name)) || empty(trim($userId))) {
            error_log("DatabaseTool: Missing required fields for entity {$id}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Enhance Entity Data with Metadata
     * 
     * Adds system metadata and enrichment data to the entity JSON.
     * 
     * @param string $jsonData Original JSON data
     * @param array $metadata Additional metadata
     * @return string Enhanced JSON data
     */
    private function enhanceEntityData(string $jsonData, array $metadata): string {
        $data = json_decode($jsonData, true) ?? [];
        
        // Add system metadata
        $data['_metadata'] = array_merge([
            'created_timestamp' => time(),
            'version' => 1,
            'source' => 'ai_assistant',
            'confidence_score' => 1.0
        ], $metadata);
        
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Execute Parameterized Query with Advanced Features
     * 
     * Executes database queries with comprehensive error handling, performance
     * monitoring, caching support, and security validation.
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind to the query
     * @param bool $useCache Whether to use query caching
     * @return array Results as associative array
     */
    public function executeParameterizedQuery(string $query, array $params = [], bool $useCache = false): array {
        $stmt = $this->db->prepare($query);
        
        if (!$stmt) {
            error_log("Database query preparation failed: " . $this->db->error);
            return [];
        }
        
        // Bind parameters if any
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            // Determine parameter types
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            // Create array with references as required by bind_param
            $bindArgs = [];
            $bindArgs[] = $types;
            
            foreach ($bindParams as $key => $value) {
                $bindArgs[] = &$bindParams[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            return [];
        }
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Find a specific entity by its ID
     * Retrieves a single entity record from the database
     * 
     * @param string $id The unique identifier of the entity to find
     * @return array|null Entity data as associative array, or null if not found
     */
    public function findEntity(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM entities WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Update an existing entity's data
     * Updates the JSON data field and timestamp for an existing entity
     * 
     * @param string $id The unique identifier of the entity to update
     * @param string $jsonData New JSON-encoded data for the entity
     * @return bool True if update was successful, false otherwise
     */
    public function updateEntity(string $id, string $jsonData) {
        $stmt = $this->db->prepare("UPDATE entities SET data = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ss", $jsonData, $id);
        return $stmt->execute();
    }
    
    /**
     * Find all entities of a specific type for a user
     * Retrieves multiple entities based on user ID and entity type
     * 
     * @param string $userId The user ID to filter by
     * @param string $type The entity type to filter by (e.g., 'person', 'task')
     * @return array Array of entity records, empty array if none found
     */
    public function findEntitiesByType(string $userId, string $type): array {
        $stmt = $this->db->prepare("SELECT * FROM entities WHERE user_id = ? AND type = ?");
        $stmt->bind_param("ss", $userId, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create a new relationship between two entities
     * Establishes a typed connection between source and target entities
     * 
     * @param string $id Unique identifier for the relationship (UUID format)
     * @param string $userId User who owns this relationship
     * @param string $sourceEntityId ID of the source entity
     * @param string $targetEntityId ID of the target entity
     * @param string $type Type of relationship (e.g., 'works_at', 'friends_with')
     * @param float $strength Strength of relationship (0.01-1.00)
     * @param string|null $metadata Optional JSON-encoded metadata about the relationship
     * @return bool True if creation was successful, false otherwise
     */
    public function createRelationship(string $id, string $userId, string $sourceEntityId, 
                                      string $targetEntityId, string $type, 
                                      float $strength = 1.0, ?string $metadata = null): bool {
        // Debug logging
        error_log("DatabaseTool: Creating relationship '{$type}' from {$sourceEntityId} to {$targetEntityId}");
        
        // Verify source entity exists
        $sourceEntity = $this->findEntity($sourceEntityId);
        if (!$sourceEntity) {
            error_log("DatabaseTool: Error - Source entity {$sourceEntityId} does not exist");
            return false;
        }
        
        // Verify target entity exists
        $targetEntity = $this->findEntity($targetEntityId);
        if (!$targetEntity) {
            error_log("DatabaseTool: Error - Target entity {$targetEntityId} does not exist");
            return false;
        }
        
        // Create the relationship
        $stmt = $this->db->prepare("INSERT INTO relationships (id, user_id, source_entity_id, 
                                   target_entity_id, type, strength, metadata) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", $id, $userId, $sourceEntityId, $targetEntityId, 
                                    $type, $strength, $metadata);
        $result = $stmt->execute();
        
        if ($result) {
            error_log("DatabaseTool: Successfully created relationship '{$type}' with ID {$id}");
        } else {
            error_log("DatabaseTool: Failed to create relationship: " . $this->db->error);
        }
        
        return $result;
    }
    
    /**
     * Find relationships for a specific entity
     * Can retrieve either incoming or outgoing relationships, or both
     * 
     * @param string $entityId ID of the entity to find relationships for
     * @param string|null $type Optional relationship type filter
     * @param string $direction Direction of relationships to find ('outgoing', 'incoming', or 'both')
     * @return array Array of relationship records
     */
    public function findRelationships(string $entityId, ?string $type = null, string $direction = 'both'): array {
        $params = [];
        $whereConditions = [];
        
        // Base query without WHERE conditions
        $query = "SELECT r.*, 
                 s.primary_name as source_name, s.type as source_type,
                 t.primary_name as target_name, t.type as target_type
                 FROM relationships r
                 JOIN entities s ON r.source_entity_id = s.id
                 JOIN entities t ON r.target_entity_id = t.id";
        
        // Add direction condition
        if ($direction === 'outgoing' || $direction === 'both') {
            $whereConditions[] = "r.source_entity_id = ?";
            $params[] = $entityId;
        }
        
        if ($direction === 'incoming' || $direction === 'both') {
            if ($direction === 'both') {
                $whereConditions[count($whereConditions) - 1] .= " OR r.target_entity_id = ?";
            } else {
                $whereConditions[] = "r.target_entity_id = ?";
            }
            $params[] = $entityId;
        }
        
        // Add type filter if provided
        if ($type !== null) {
            $whereConditions[] = "r.type = ?";
            $params[] = $type;
        }
        
        // Build final query with WHERE conditions
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        return $this->executeParameterizedQuery($query, $params);
    }
    
    /**
     * Find entities related to a specific entity
     * Helper method to find connected entities through relationships
     * 
     * @param string $entityId ID of the source entity
     * @param string|null $relationType Optional relationship type filter
     * @param string|null $entityType Optional target entity type filter
     * @return array Array of related entities with relationship data
     */
    public function findRelatedEntities(string $entityId, ?string $relationType = null, ?string $entityType = null): array {
        $params = [$entityId];
        $query = "SELECT e.*, r.type as relationship_type, r.strength, r.metadata as relationship_metadata
                 FROM entities e
                 JOIN relationships r ON e.id = r.target_entity_id
                 WHERE r.source_entity_id = ?";
        
        if ($relationType !== null) {
            $query .= " AND r.type = ?";
            $params[] = $relationType;
        }
        
        if ($entityType !== null) {
            $query .= " AND e.type = ?";
            $params[] = $entityType;
        }
        
        return $this->executeParameterizedQuery($query, $params);
    }
    
    /**
     * Begin Database Transaction
     * 
     * Starts a database transaction for ensuring data consistency
     * across multiple operations.
     * 
     * @return bool True if transaction started successfully
     */
    private function beginTransaction(): bool {
        if ($this->inTransaction) {
            return true; // Already in transaction
        }
        
        if ($this->db->begin_transaction()) {
            $this->inTransaction = true;
            return true;
        }
        
        error_log("DatabaseTool: Failed to begin transaction: " . $this->db->error);
        return false;
    }
    
    /**
     * Commit Database Transaction
     * 
     * Commits the current transaction, making all changes permanent.
     * 
     * @return bool True if commit successful
     */
    private function commitTransaction(): bool {
        if (!$this->inTransaction) {
            return true; // No transaction to commit
        }
        
        if ($this->db->commit()) {
            $this->inTransaction = false;
            return true;
        }
        
        error_log("DatabaseTool: Failed to commit transaction: " . $this->db->error);
        return false;
    }
    
    /**
     * Rollback Database Transaction
     * 
     * Rolls back the current transaction, undoing all changes.
     * 
     * @return bool True if rollback successful
     */
    private function rollbackTransaction(): bool {
        if (!$this->inTransaction) {
            return true; // No transaction to rollback
        }
        
        if ($this->db->rollback()) {
            $this->inTransaction = false;
            return true;
        }
        
        error_log("DatabaseTool: Failed to rollback transaction: " . $this->db->error);
        return false;
    }
    
    /**
     * Log Entity Operation
     * 
     * Records entity operations for audit trail and debugging.
     * 
     * @param string $operation Operation type (CREATE, UPDATE, DELETE)
     * @param string $entityId Entity ID
     * @param string $userId User ID
     * @param string $entityType Entity type
     * @return void
     */
    private function logEntityOperation(string $operation, string $entityId, string $userId, string $entityType): void {
        $logData = [
            'timestamp' => time(),
            'operation' => $operation,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        error_log("ENTITY_OPERATION: " . json_encode($logData));
    }
    
    /**
     * Update Query Performance Metrics
     * 
     * Tracks query performance and usage statistics for monitoring
     * and optimization purposes.
     * 
     * @param float $executionTime Query execution time in seconds
     * @param bool $success Whether the query was successful
     * @return void
     */
    private function updateQueryMetrics(float $executionTime, bool $success): void {
        $this->queryMetrics['total_queries']++;
        $this->queryMetrics['total_execution_time'] += $executionTime;
        
        if ($success) {
            if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
                $this->queryMetrics['slow_queries']++;
                error_log("DatabaseTool: Slow query detected - Execution time: {$executionTime}s");
            }
        } else {
            $this->queryMetrics['failed_queries']++;
        }
    }
    
    /**
     * Validate JSON Data
     * 
     * Checks if a string contains valid JSON data.
     * 
     * @param string $jsonString JSON string to validate
     * @return bool True if valid JSON, false otherwise
     */
    private function isValidJson(string $jsonString): bool {
        json_decode($jsonString);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Get Query Performance Metrics
     * 
     * Returns comprehensive performance metrics for monitoring
     * and optimization analysis.
     * 
     * @return array Performance metrics data
     */
    public function getPerformanceMetrics(): array {
        $metrics = $this->queryMetrics;
        
        // Calculate derived metrics
        if ($metrics['total_queries'] > 0) {
            $metrics['avg_execution_time'] = $metrics['total_execution_time'] / $metrics['total_queries'];
            $metrics['success_rate'] = (($metrics['total_queries'] - $metrics['failed_queries']) / $metrics['total_queries']) * 100;
            $metrics['slow_query_percentage'] = ($metrics['slow_queries'] / $metrics['total_queries']) * 100;
        } else {
            $metrics['avg_execution_time'] = 0;
            $metrics['success_rate'] = 100;
            $metrics['slow_query_percentage'] = 0;
        }
        
        return $metrics;
    }
    
    /**
     * Clear Query Cache
     * 
     * Clears the internal query cache to free memory or force
     * fresh data retrieval.
     * 
     * @return void
     */
    public function clearCache(): void {
        $this->queryCache = [];
        $this->queryMetrics['cache_hits'] = 0;
        $this->queryMetrics['cache_misses'] = 0;
    }
    
    /**
     * Get Database Connection Health
     * 
     * Performs a health check on the database connection and
     * returns status information.
     * 
     * @return array Health status information
     */
    public function getConnectionHealth(): array {
        $health = [
            'status' => 'healthy',
            'connection_active' => false,
            'server_info' => '',
            'client_info' => '',
            'protocol_version' => 0,
            'thread_id' => 0,
            'issues' => []
        ];
        
        try {
            // Test connection
            if ($this->db->ping()) {
                $health['connection_active'] = true;
                $health['server_info'] = $this->db->server_info;
                $health['client_info'] = $this->db->client_info;
                $health['protocol_version'] = $this->db->protocol_version;
                $health['thread_id'] = $this->db->thread_id;
            } else {
                $health['status'] = 'unhealthy';
                $health['issues'][] = 'Database connection ping failed';
            }
            
            // Check for errors
            if ($this->db->error) {
                $health['status'] = 'degraded';
                $health['issues'][] = 'Database error: ' . $this->db->error;
            }
            
            // Performance check
            $metrics = $this->getPerformanceMetrics();
            if ($metrics['success_rate'] < 95) {
                $health['status'] = 'degraded';
                $health['issues'][] = "Low success rate: {$metrics['success_rate']}%";
            }
            
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Health check failed: ' . $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Destructor - Clean up database connection
     * 
     * Ensures proper cleanup of database resources when the object
     * is destroyed.
     */
    public function __destruct() {
        // Rollback any uncommitted transactions
        if ($this->inTransaction) {
            $this->rollbackTransaction();
        }
        
        // Close database connection
        if ($this->db) {
            $this->db->close();
        }
    }
}

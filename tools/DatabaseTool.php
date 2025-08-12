<?php
/**
 * DatabaseTool - Database Operations Handler
 * 
 * This class handles all database operations for the AI Personal Assistant.
 * It provides methods for CRUD operations on entities and manages MySQL connections.
 * Uses mysqli for database connectivity with prepared statements for security.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class DatabaseTool {
    /** @var mysqli Database connection instance */
    private $db;
    
    /**
     * Constructor - Initialize database connection
     * Establishes connection to MySQL database using configuration constants
     * 
     * @throws Exception If database connection fails
     */
    public function __construct() {
        // Establish database connection using config constants
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check for connection errors and terminate if failed
        if ($this->db->connect_error) { 
            die("DB Connection Failed: " . $this->db->connect_error); 
        }
        
        // Set character set to UTF-8 for proper Unicode support
        $this->db->set_charset("utf8mb4");
    }
    
    /**
     * Save a new entity to the database
     * Creates a new record in the entities table with the provided data
     * 
     * @param string $id Unique identifier for the entity (UUID format)
     * @param string $userId User who owns this entity
     * @param string $type Type of entity (e.g., 'person', 'event', 'task')
     * @param string $name Human-readable name for the entity
     * @param string $jsonData JSON-encoded data containing all entity components
     * @return bool True if save was successful, false otherwise
     */
    public function saveNewEntity(string $id, string $userId, string $type, string $name, string $jsonData) {
        $stmt = $this->db->prepare("INSERT INTO entities (id, user_id, type, primary_name, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $userId, $type, $name, $jsonData);
        return $stmt->execute();
    }
    
    /**
     * Execute a parameterized query and return results as an array
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind to the query
     * @return array Results as associative array
     */
    public function executeParameterizedQuery(string $query, array $params = []): array {
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
}

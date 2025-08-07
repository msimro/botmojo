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
}

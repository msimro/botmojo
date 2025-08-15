<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Agents
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Agents;

use BotMojo\Core\AgentInterface;
use BotMojo\Exceptions\BotMojoException;
use BotMojo\Tools\DatabaseTool;
use BotMojo\Tools\GeminiTool;
use BotMojo\Services\LoggerService;

/**
 * Memory Agent
 *
 * Manages knowledge and memory for the BotMojo system.
 * Handles creating, retrieving, and updating memory entities.
 */
class MemoryAgent implements AgentInterface
{
    /**
     * Database tool for data persistence
     *
     * @var DatabaseTool
     */
    private DatabaseTool $dbTool;
    
    /**
     * Gemini tool for AI operations
     *
     * @var GeminiTool
     */
    private GeminiTool $geminiTool;
    
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;
    
    /**
     * Constructor
     *
     * @param DatabaseTool $dbTool     The database tool
     * @param GeminiTool   $geminiTool The Gemini AI tool
     */
    public function __construct(DatabaseTool $dbTool, GeminiTool $geminiTool)
    {
        $this->dbTool = $dbTool;
        $this->geminiTool = $geminiTool;
        $this->logger = new LoggerService('MemoryAgent');
    }
    
    /**
     * Process a memory task
     *
     * @param array<string, mixed> $taskData The task data
     *
     * @return array<string, mixed> The processing result
     */
    public function process(array $taskData): array
    {
        $operation = $taskData['operation'] ?? 'retrieve';
        
        switch ($operation) {
            case 'create':
                return $this->createMemory($taskData);
            case 'retrieve':
                return $this->retrieveMemory($taskData);
            case 'update':
                return $this->updateMemory($taskData);
            case 'delete':
                return $this->deleteMemory($taskData);
            default:
                $exception = new BotMojoException("Unknown memory operation: {$operation}");
                $exception->setContext(['taskData' => $taskData]);
                throw $exception;
        }
    }
    
    /**
     * Create a memory component for the response
     *
     * @param array<string, mixed> $data The component data
     *
     * @return array<string, mixed> The memory component
     */
    public function createComponent(array $data): array
    {
        // In a more complex implementation, this would create a structured component
        // based on the data and the agent's domain expertise
        
        return [
            'type' => 'memory',
            'content' => $data['content'] ?? null,
            'entities' => $data['entities'] ?? [],
            'relationships' => $data['relationships'] ?? []
        ];
    }
    
    /**
     * Create a new memory entity
     *
     * @param array<string, mixed> $data The memory data
     *
     * @return array<string, mixed> The created memory
     */
    private function createMemory(array $data): array
    {
        // Extract entity data
        $entityType = $data['entity_type'] ?? 'generic';
        $entityName = $data['entity_name'] ?? 'Unnamed Entity';
        $entityData = $data['entity_data'] ?? [];
        $userId = $data['user_id'] ?? 'default_user';
        
        // Generate a UUID for the entity
        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        // Format data for storage
        $jsonData = json_encode($entityData);
        
        // Insert into database
        $this->dbTool->insert('entities', [
            'id' => $id,
            'user_id' => $userId,
            'type' => $entityType,
            'primary_name' => $entityName,
            'data' => $jsonData
        ]);
        
        // Handle relationships if present
        if (isset($data['relationships']) && is_array($data['relationships'])) {
            foreach ($data['relationships'] as $relationship) {
                $targetId = $relationship['target_id'] ?? null;
                $relationType = $relationship['type'] ?? 'related';
                
                if ($targetId) {
                    $this->dbTool->insert('relationships', [
                        'source_id' => $id,
                        'target_id' => $targetId,
                        'type' => $relationType
                    ]);
                }
            }
        }
        
        return [
            'id' => $id,
            'type' => $entityType,
            'name' => $entityName,
            'data' => $entityData,
            'message' => "Created new {$entityType} entity: {$entityName}"
        ];
    }
    
    /**
     * Retrieve memory entities
     *
     * @param array<string, mixed> $data The query parameters
     *
     * @return array<string, mixed> The retrieved memories
     */
    private function retrieveMemory(array $data): array
    {
        $entityType = $data['entity_type'] ?? null;
        $entityName = $data['entity_name'] ?? null;
        $searchTerm = $data['search'] ?? null;
        
        $where = [];
        $params = [];
        
        if ($entityType) {
            $where[] = 'type = ?';
            $params[] = $entityType;
        }
        
        if ($entityName) {
            $where[] = 'primary_name = ?';
            $params[] = $entityName;
        }
        
        if ($searchTerm) {
            $where[] = '(primary_name LIKE ? OR data LIKE ?)';
            $params[] = "%{$searchTerm}%";
            $params[] = "%{$searchTerm}%";
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM entities {$whereClause} ORDER BY created_at DESC LIMIT 10";
        
        $entities = $this->dbTool->query($query, $params);
        
        // Parse JSON data
        foreach ($entities as &$entity) {
            $entity['data'] = json_decode($entity['data'], true);
        }
        
        return [
            'entities' => $entities,
            'count' => count($entities),
            'message' => "Retrieved " . count($entities) . " entities"
        ];
    }
    
    /**
     * Update a memory entity
     *
     * @param array<string, mixed> $data The update data
     *
     * @return array<string, mixed> The update result
     */
    private function updateMemory(array $data): array
    {
        $entityId = $data['entity_id'] ?? null;
        
        if (!$entityId) {
            $exception = new BotMojoException("Missing entity ID for update operation");
            $exception->setContext(['data' => $data]);
            throw $exception;
        }
        
        // Get current entity
        $entities = $this->dbTool->query(
            "SELECT * FROM entities WHERE id = ?",
            [$entityId]
        );
        
        if (empty($entities)) {
            $exception = new BotMojoException("Entity not found for update: {$entityId}");
            $exception->setContext(['data' => $data]);
            throw $exception;
        }
        
        $entity = $entities[0];
        $entityData = json_decode($entity['data'], true) ?? [];
        
        // Update data with new values
        if (isset($data['entity_data']) && is_array($data['entity_data'])) {
            $entityData = array_merge($entityData, $data['entity_data']);
        }
        
        // Update name if provided
        $updateData = [
            'data' => json_encode($entityData),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (isset($data['entity_name'])) {
            $updateData['name'] = $data['entity_name'];
        }
        
        // Update in database
        $this->dbTool->update(
            'entities',
            $updateData,
            'id = ?',
            [$entityId]
        );
        
        return [
            'id' => $entityId,
            'data' => $entityData,
            'message' => "Updated entity: {$entity['name']}"
        ];
    }
    
    /**
     * Delete a memory entity
     *
     * @param array<string, mixed> $data The delete parameters
     *
     * @return array<string, mixed> The delete result
     */
    private function deleteMemory(array $data): array
    {
        $entityId = $data['entity_id'] ?? null;
        
        if (!$entityId) {
            $exception = new BotMojoException("Missing entity ID for delete operation");
            $exception->setContext(['data' => $data]);
            throw $exception;
        }
        
        // Get entity before deletion
        $entities = $this->dbTool->query(
            "SELECT * FROM entities WHERE id = ?",
            [$entityId]
        );
        
        if (empty($entities)) {
            $exception = new BotMojoException("Entity not found for deletion: {$entityId}");
            $exception->setContext(['data' => $data]);
            throw $exception;
        }
        
        $entity = $entities[0];
        
        // Delete relationships first
        $this->dbTool->query(
            "DELETE FROM relationships WHERE source_id = ? OR target_id = ?",
            [$entityId, $entityId]
        );
        
        // Delete the entity
        $this->dbTool->query(
            "DELETE FROM entities WHERE id = ?",
            [$entityId]
        );
        
        return [
            'id' => $entityId,
            'message' => "Deleted entity: {$entity['name']}"
        ];
    }
}

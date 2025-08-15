<?php
/**
 * MemoryAgent - Knowledge Graph Component Manager (MDO Version)
 * 
 * This agent manages entities and relationships in the knowledge graph.
 * Simplified implementation for the MDO environment.
 */
class MemoryAgent {
    private $toolManager;
    
    /**
     * Constructor - Initialize with tool manager for tool access
     * 
     * @param ToolManager $toolManager Tool management service
     */
    public function __construct($toolManager = null) {
        $this->toolManager = $toolManager;
    }
    
    /**
     * Execute a memory task based on intent
     * 
     * @param array $task Task specification with intent and parameters
     * @return string|array Response from the memory operation
     */
    public function execute(array $task) {
        $intent = $task['intent'];
        $params = $task['parameters'];
        
        // Handle different memory operations based on intent
        switch ($intent) {
            case 'CREATE':
            case 'UPDATE':
                return $this->createOrUpdateEntity($params);
                
            case 'RETRIEVE':
                return $this->retrieveEntity($params);
                
            case 'CREATE_RELATIONSHIP':
                return $this->createRelationship($params);
                
            case 'RETRIEVE_RELATIONSHIPS':
                return $this->retrieveRelationships($params);
                
            default:
                return "MemoryAgent: Can't handle intent '{$intent}'.";
        }
    }
    
    /**
     * Create or update an entity in the knowledge graph
     * 
     * @param array $params Entity parameters
     * @return string Operation result message
     */
    private function createOrUpdateEntity(array $params): string {
        $alias = $params['person_alias'] ?? $params['entity_alias'] ?? 'unknown';
        $name = $params['name'] ?? 'unnamed';
        $type = $params['type'] ?? 'person';
        $attributes = $params['attributes'] ?? [];
        
        // Process relationships if present
        if (!empty($params['relationships'])) {
            $this->processRelationships($alias, $params['relationships']);
        }
        
        // In a real implementation, we would save to database here
        return "MemoryAgent: Processed CREATE/UPDATE for entity '{$alias}' with name '{$name}'.";
    }
    
    /**
     * Retrieve an entity from the knowledge graph
     * 
     * @param array $params Retrieval parameters
     * @return array Entity data
     */
    private function retrieveEntity(array $params): array {
        $alias = $params['person_alias'] ?? $params['entity_alias'] ?? null;
        $entityId = $params['entity_id'] ?? null;
        
        // In a real implementation, we would fetch from database here
        return [
            'entity_id' => $entityId ?? 'ent_' . uniqid(),
            'name' => $alias ?? 'Sample Entity',
            'type' => 'person',
            'attributes' => [
                'last_interaction' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Create relationships between entities
     * 
     * @param array $params Relationship parameters
     * @return string Operation result message
     */
    private function createRelationship(array $params): string {
        $sourceId = $params['source_id'] ?? null;
        $targetId = $params['target_id'] ?? null;
        $relationType = $params['relationship_type'] ?? 'generic';
        
        if (!$sourceId || !$targetId) {
            return "MemoryAgent: Missing source or target ID for relationship creation.";
        }
        
        // Get database tool if available
        $dbTool = $this->getDbTool();
        if ($dbTool) {
            // Execute database operation to create relationship
            $dbTool->execute([
                'operation' => 'create',
                'table' => 'relationships',
                'data' => [
                    'source_id' => $sourceId,
                    'target_id' => $targetId,
                    'type' => $relationType,
                    'strength' => $params['strength'] ?? 1.0
                ]
            ]);
        }
        
        return "MemoryAgent: Created '{$relationType}' relationship between entities.";
    }
    
    /**
     * Retrieve relationships for an entity
     * 
     * @param array $params Retrieval parameters
     * @return array Relationship data
     */
    private function retrieveRelationships(array $params): array {
        $entityId = $params['entity_id'] ?? null;
        $relationType = $params['relationship_type'] ?? null;
        
        if (!$entityId) {
            return ['error' => 'Missing entity ID for relationship retrieval'];
        }
        
        // In a real implementation, we would fetch from database here
        return [
            'entity_id' => $entityId,
            'relationships' => [
                [
                    'relationship_id' => 'rel_' . uniqid(),
                    'type' => 'works_at',
                    'target_id' => 'ent_company_' . uniqid(),
                    'target_name' => 'Sample Company',
                    'strength' => 0.8
                ]
            ]
        ];
    }
    
    /**
     * Process a list of relationships for an entity
     * 
     * @param string $sourceAlias Source entity alias
     * @param array $relationships List of relationships to process
     * @return void
     */
    private function processRelationships(string $sourceAlias, array $relationships): void {
        // Get database tool if available
        $dbTool = $this->getDbTool();
        if (!$dbTool) {
            return;
        }
        
        // Source entity ID (in a real implementation, we would look this up)
        $sourceId = 'ent_' . md5($sourceAlias);
        
        foreach ($relationships as $relationship) {
            // Skip incomplete relationship data
            if (empty($relationship['type']) || empty($relationship['target'])) {
                continue;
            }
            
            // Get or create target entity
            $targetName = $relationship['target'];
            $targetAlias = strtolower(str_replace(' ', '_', $targetName));
            $targetId = 'ent_' . md5($targetAlias);
            
            // Ensure target entity exists
            $dbTool->execute([
                'operation' => 'create',
                'table' => 'entities',
                'data' => [
                    'entity_id' => $targetId,
                    'name' => $targetName,
                    'type' => $relationship['target_type'] ?? 'entity'
                ]
            ]);
            
            // Create relationship
            $dbTool->execute([
                'operation' => 'create',
                'table' => 'relationships',
                'data' => [
                    'relationship_id' => 'rel_' . uniqid(),
                    'source_id' => $sourceId,
                    'target_id' => $targetId,
                    'type' => $relationship['relationship'] ?? $relationship['type'],
                    'strength' => $relationship['strength'] ?? 1.0
                ]
            ]);
        }
    }
    
    /**
     * Get database tool instance
     * 
     * @return DatabaseTool|null Database tool or null if not available
     */
    private function getDbTool() {
        if ($this->toolManager) {
            return $this->toolManager->getTool('database');
        }
        
        // Fallback for MDO simplified environment
        if (class_exists('DatabaseTool')) {
            return new DatabaseTool();
        }
        
        return null;
    }
}
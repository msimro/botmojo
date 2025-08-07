<?php
/**
 * MemoryAgent - Knowledge Graph Component Creator
 * 
 * This agent manages the core knowledge graph about people, places, and objects.
 * It creates memory components that store relationships, attributes, and contextual
 * information about entities in the user's life.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class MemoryAgent {
    
    /**
     * Create a memory component from provided data
     * Processes knowledge graph data and returns a standardized memory component
     * 
     * @param array $data Raw memory data from the triage system
     * @return array Standardized memory component with relationship and attribute data
     */
    public function createComponent(array $data): array {
        return [
            // Core identity information
            'name' => $data['name'] ?? '',                                 // Primary name or identifier
            'type' => $data['type'] ?? 'person',                          // Entity type: person, place, object
            
            // Descriptive information
            'attributes' => $data['attributes'] ?? [],                    // Key-value pairs of attributes
            'relationships' => $data['relationships'] ?? [],              // Connections to other entities
            'notes' => $data['notes'] ?? '',                             // Free-form notes and observations
            'tags' => $data['tags'] ?? [],                               // Categorization tags
            
            // Contextual metadata
            'last_interaction' => $data['last_interaction'] ?? date('Y-m-d H:i:s'), // When last mentioned/interacted
            'importance_level' => $data['importance_level'] ?? 'medium'    // Importance rating: low, medium, high
        ];
    }
}

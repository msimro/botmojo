<?php
/**
 * HealthAgent - Specialized Agent for Health and Wellness
 * 
 * This agent handles health-related queries, including health tracking,
 * medical information, wellness recommendations, fitness data, nutrition,
 * and overall well-being management.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-12
 */
class HealthAgent {
    
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
     * Create a health-related component from provided data
     * 
     * @param array $data Raw health data from the triage system
     * @return array Enhanced health component with analysis and recommendations
     */
    public function createComponent(array $data): array {
        // Extract health information from triage context
        $healthInfo = $this->extractHealthInformation($data);
        
        // Check if we need to access database for health records
        $healthRecords = [];
        if (isset($data['needs_health_records']) && $data['needs_health_records']) {
            $dbTool = $this->toolManager->getTool('database');
            if ($dbTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $healthRecords = $this->retrieveHealthRecords($dbTool, $userId);
            }
        }
        
        // Check if we need to access fitness data
        $fitnessData = [];
        if (isset($data['needs_fitness_data']) && $data['needs_fitness_data']) {
            $fitnessTool = $this->toolManager->getTool('fitness');
            if ($fitnessTool) {
                $userId = $data['full_triage_response']['user_id'] ?? DEFAULT_USER_ID;
                $fitnessData = $fitnessTool->getUserFitnessData($userId);
            }
        }
        
        // Search for health information if needed
        $healthSearchResults = [];
        if (isset($data['health_search_query']) && !empty($data['health_search_query'])) {
            $searchTool = $this->toolManager->getTool('search');
            if ($searchTool) {
                $healthSearchResults = $searchTool->search(
                    $data['health_search_query'],
                    ['medicalDatabase' => true, 'verifiedSourcesOnly' => true]
                );
            }
        }
        
        // Generate health analysis and recommendations
        $analysis = $this->analyzeHealthData(
            $healthInfo,
            $healthRecords, 
            $fitnessData,
            $healthSearchResults
        );
        
        // Add medical disclaimer
        $analysis['disclaimer'] = "This information is not a substitute for professional medical advice. " .
                                  "Please consult with a healthcare provider for medical concerns.";
        
        return [
            'type' => 'health_component',
            'analysis' => $analysis,
            'data' => [
                'health_info' => $healthInfo,
                'health_records' => $this->sanitizeHealthRecords($healthRecords),
                'fitness_data' => $fitnessData,
                'health_search_results' => $healthSearchResults
            ]
        ];
    }
    
    /**
     * Extract health-related information from triage data
     * 
     * @param array $data The triage data
     * @return array Extracted health information
     */
    private function extractHealthInformation(array $data): array {
        return [
            'query_type' => $data['query_type'] ?? 'general_health',
            'health_topic' => $data['health_topic'] ?? '',
            'symptom_description' => $data['symptom_description'] ?? '',
            'time_period' => $data['time_period'] ?? 'current',
            'specific_metrics' => $data['specific_metrics'] ?? []
        ];
    }
    
    /**
     * Retrieve health records from database
     * 
     * @param DatabaseTool $dbTool Database tool instance
     * @param string $userId User identifier
     * @return array Retrieved health records
     */
    private function retrieveHealthRecords($dbTool, string $userId): array {
        // Query health records from database
        $query = "SELECT * FROM health_records WHERE user_id = ? ORDER BY record_date DESC LIMIT 10";
        $params = [$userId];
        
        return $dbTool->executeParameterizedQuery($query, $params);
    }
    
    /**
     * Analyze health data and generate recommendations
     * 
     * @param array $healthInfo Basic health information
     * @param array $healthRecords Historical health records
     * @param array $fitnessData Fitness tracking data
     * @param array $searchResults Health information search results
     * @return array Analysis and recommendations
     */
    private function analyzeHealthData(array $healthInfo, array $healthRecords, array $fitnessData, array $searchResults): array {
        // Implement health data analysis logic
        $summary = "Health analysis based on provided information.";
        $details = "Detailed health information based on records and search results.";
        $recommendations = ["Stay hydrated", "Maintain regular exercise", "Ensure adequate sleep"];
        
        // More sophisticated analysis would be implemented here
        
        return [
            'summary' => $summary,
            'details' => $details,
            'recommendations' => $recommendations,
        ];
    }
    
    /**
     * Sanitize health records to remove sensitive information
     * 
     * @param array $records Health records to sanitize
     * @return array Sanitized health records
     */
    private function sanitizeHealthRecords(array $records): array {
        $sanitized = [];
        
        foreach ($records as $record) {
            // Remove sensitive identifiers but keep relevant health data
            $sanitizedRecord = $record;
            unset($sanitizedRecord['record_id']);
            unset($sanitizedRecord['user_id']);
            unset($sanitizedRecord['insurance_number']);
            // Redact other sensitive fields as needed
            
            $sanitized[] = $sanitizedRecord;
        }
        
        return $sanitized;
    }
}

<?php
/**
 * MemoryAgent - Enhanced Knowledge Graph Component Creator
 * 
 * This agent manages the core knowledge graph about people, places, and objects.
 * It creates memory components that store relationships, attributes, and contextual
 * information about entities in the user's life. Enhanced to extract rich information
 * from triage data and natural language context.
 * 
 * @author AI Personal Assistant Team
 * @version 1.1
 * @since 2025-08-07
 */
class MemoryAgent {
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
     * Create a memory component from provided data
     * Processes knowledge graph data and returns a standardized memory component
     * Enhanced to extract information from triage context and natural language
     * 
     * @param array $data Raw memory data from the triage system
     * @return array Standardized memory component with relationship and attribute data
     */
    public function createComponent(array $data): array {
        // Extract enhanced information from triage context if available
        $extractedInfo = $this->extractEnhancedInformation($data);
        
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
}

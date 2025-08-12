<?php
/**
 * SocialAgent - Specialized Agent for Social Interactions
 * 
 * This agent handles social-related queries, including relationship management,
 * social event planning, communication patterns, networking, and 
 * interpersonal dynamics.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-12
 */
class SocialAgent {
    
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
     * Create a social-related component from provided data
     * 
     * @param array $data Raw social data from the triage system
     * @return array Enhanced social component with relationship insights and event planning
     */
    public function createComponent(array $data): array {
        // Extract social information from triage context
        $socialInfo = $this->extractSocialInformation($data);
        
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
     * Analyze social data and generate insights and recommendations
     * 
     * @param array $socialInfo Basic social information
     * @param array $contactsData User's contact information
     * @param array $events Social event data
     * @return array Social insights and recommendations
     */
    private function analyzeSocialData(array $socialInfo, array $contactsData, array $events): array {
        // Implement social data analysis logic
        $relationshipInsights = "Insights about your social connections based on your data.";
        $eventRecommendations = "Suggested social events based on your calendar and preferences.";
        $communicationTips = ["Be clear and direct", "Practice active listening", "Follow up after important conversations"];
        
        // More sophisticated analysis would be implemented here
        
        return [
            'relationship_insights' => $relationshipInsights,
            'event_recommendations' => $eventRecommendations,
            'communication_tips' => $communicationTips,
        ];
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

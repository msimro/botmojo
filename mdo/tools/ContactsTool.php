<?php
/**
 * ContactsTool - Contact Management System for MDO
 */
class ContactsTool {
    public function execute(array $params) {
        $userId = $params['user_id'] ?? 'default_user';
        $requestType = $params['request_type'] ?? 'get_contacts';
        
        // Simplified mock data for POC
        if ($requestType === 'get_contacts') {
            $filters = $params['filters'] ?? [];
            
            $contacts = [
                [
                    'name' => 'Alex Smith',
                    'relationship' => 'friend',
                    'contact_frequency' => 'weekly',
                    'last_contact' => date('Y-m-d', strtotime('-3 days')),
                    'phone' => '555-123-4567',
                    'email' => 'alex@example.com',
                    'birthday' => '1985-06-15'
                ],
                [
                    'name' => 'Jordan Lee',
                    'relationship' => 'colleague',
                    'contact_frequency' => 'daily',
                    'last_contact' => date('Y-m-d', strtotime('-1 day')),
                    'phone' => '555-987-6543',
                    'email' => 'jordan@example.com'
                ],
                [
                    'name' => 'Taylor Johnson',
                    'relationship' => 'family',
                    'contact_frequency' => 'monthly',
                    'last_contact' => date('Y-m-d', strtotime('-2 weeks')),
                    'phone' => '555-456-7890',
                    'email' => 'taylor@example.com',
                    'birthday' => '1970-11-08'
                ]
            ];
            
            // Apply filters if provided
            if (!empty($filters)) {
                $filteredContacts = [];
                
                foreach ($contacts as $contact) {
                    $matchesAllFilters = true;
                    
                    foreach ($filters as $key => $value) {
                        if (!isset($contact[$key]) || $contact[$key] != $value) {
                            $matchesAllFilters = false;
                            break;
                        }
                    }
                    
                    if ($matchesAllFilters) {
                        $filteredContacts[] = $contact;
                    }
                }
                
                return $filteredContacts;
            }
            
            return $contacts;
        } elseif ($requestType === 'update_contact') {
            $contactData = $params['contact_data'] ?? [];
            $name = $contactData['name'] ?? 'Unknown Contact';
            
            return [
                'status' => 'success',
                'message' => "Updated contact information for {$name}",
                'updated_at' => date('Y-m-d H:i:s')
            ];
        } elseif ($requestType === 'get_insights') {
            return [
                'frequent_contacts' => ['Jordan Lee', 'Alex Smith'],
                'neglected_relationships' => ['Taylor Johnson'],
                'upcoming_birthdays' => [
                    [
                        'name' => 'Alex Smith',
                        'date' => '1985-06-15',
                        'days_away' => 45
                    ]
                ],
                'communication_patterns' => [
                    'most_active_day' => 'Tuesday',
                    'average_response_time' => '3 hours'
                ]
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Invalid request type'
        ];
    }
}

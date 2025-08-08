<?php
/**
 * PlannerAgent - Enhanced Time Management and Scheduling Component Creator
 * 
 * This agent specializes in managing time, schedules, tasks, and goals with
 * intelligent natural language date/time parsing and context-aware scheduling.
 * It creates planning components for events, tasks, reminders, and 
 * time-based activities with comprehensive scheduling information.
 * 
 * @author AI Personal Assistant Team
 * @version 1.1
 * @since 2025-08-07
 */
class PlannerAgent {
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
     * Create a planning component from provided data
     * Processes scheduling and task data into a standardized planning component
     * Enhanced with intelligent date/time parsing and context extraction
     * 
     * @param array $data Raw planning data from the triage system
     * @return array Standardized planning component with comprehensive scheduling info
     */
    public function createComponent(array $data): array {
        // Extract enhanced planning information from triage context
        $extractedInfo = $this->extractPlanningInformation($data);
        
        // Enhance planning with tools (Weather, Calendar, Search)
        $enhancedInfo = $this->enhancePlanningWithTools($extractedInfo);
        
        return [
            // Core planning information
            'title' => $enhancedInfo['title'] ?? $data['title'] ?? '',
            'description' => $this->combineDescriptions($data['description'] ?? '', $enhancedInfo['description'] ?? ''),
            'type' => $enhancedInfo['type'] ?? $data['type'] ?? 'task',
            
            // Enhanced time parsing
            'start_date' => $enhancedInfo['start_date'] ?? $data['start_date'] ?? null,
            'end_date' => $enhancedInfo['end_date'] ?? $data['end_date'] ?? null,
            'due_date' => $enhancedInfo['due_date'] ?? $data['due_date'] ?? null,
            'parsed_time_context' => $enhancedInfo['time_context'] ?? [],
            
            // Smart priority detection
            'priority' => $this->determinePriority($enhancedInfo, $data),
            'status' => $data['status'] ?? 'pending',
            
            // Location and people extraction
            'location' => $enhancedInfo['location'] ?? $data['location'] ?? '',
            'attendees' => array_merge($data['attendees'] ?? [], $enhancedInfo['attendees'] ?? []),
            
            // Advanced scheduling features
            'reminders' => $this->generateSmartReminders($enhancedInfo),
            'recurrence' => $enhancedInfo['recurrence'] ?? $data['recurrence'] ?? null,
            'estimated_duration' => $enhancedInfo['duration'] ?? $data['estimated_duration'] ?? null,
            
            // Enhanced context
            'natural_language_input' => $enhancedInfo['original_text'] ?? '',
            'parsing_confidence' => $enhancedInfo['confidence'] ?? 0.8,
            'extracted_entities' => $enhancedInfo['entities'] ?? [],
            'suggested_tags' => $enhancedInfo['tags'] ?? [],
            
            // Tool insights
            'tool_insights' => $enhancedInfo['tool_insights'] ?? []
        ];
    }
    
    /**
     * Extract enhanced planning information from triage data and natural language
     * Parses dates, times, durations, priorities, and context from user input
     * 
     * @param array $data Complete data from triage system
     * @return array Enhanced planning information
     */
    private function extractPlanningInformation(array $data): array {
        $extracted = [
            'title' => '',
            'description' => '',
            'type' => 'task',
            'start_date' => null,
            'end_date' => null,
            'due_date' => null,
            'time_context' => [],
            'location' => '',
            'attendees' => [],
            'recurrence' => null,
            'duration' => null,
            'priority_indicators' => [],
            'entities' => [],
            'tags' => [],
            'confidence' => 0.8,
            'original_text' => ''
        ];
        
        // Get text to analyze
        $triageSummary = $data['triage_summary'] ?? '';
        $originalQuery = $data['original_query'] ?? '';
        $analysisText = trim($triageSummary . ' ' . $originalQuery);
        $extracted['original_text'] = $analysisText;
        
        if ($analysisText) {
            // Extract event/task title
            $extracted['title'] = $this->extractTitle($analysisText, $data);
            
            // Parse date and time information
            $timeInfo = $this->parseDateTime($analysisText);
            $extracted = array_merge($extracted, $timeInfo);
            
            // Extract location information
            $extracted['location'] = $this->extractLocation($analysisText);
            
            // Extract attendees/participants
            if (!empty($data['attendees'])) {
                $extracted['attendees'] = $data['attendees'];
            } else {
                $extracted['attendees'] = $this->extractAttendees($analysisText);
            }
            
            // Determine event type
            $extracted['type'] = $this->determineEventType($analysisText);
            
            // Extract recurrence patterns
            $extracted['recurrence'] = $this->extractRecurrence($analysisText);
            
            // Extract duration
            $extracted['duration'] = $this->extractDuration($analysisText);
            
            // Extract priority indicators
            $extracted['priority_indicators'] = $this->extractPriorityIndicators($analysisText);
            
            // Generate contextual tags
            $extracted['tags'] = $this->generateTags($extracted, $analysisText);
            
            // Generate description from context
            if (empty($extracted['description'])) {
                $extracted['description'] = $this->generateDescription($extracted, $analysisText);
            }
        }
        
        return $extracted;
    }
    
    /**
     * Extract event/task title from natural language
     */
    private function extractTitle(string $text, array $data): string {
        // Check component data first
        if (!empty($data['title'])) {
            return $data['title'];
        }
        
        // Pattern matching for common task/event patterns
        if (preg_match('/(?:schedule|book|set up|plan|organize|create|add)\s+(?:a\s+)?(?:meeting|appointment|call|event|task)?\s*(?:for|with|to|about)?\s*([^.!?]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/(?:remind me to|need to|have to|should)\s+([^.!?]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Extract quoted titles
        if (preg_match('/"([^"]+)"/', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Fallback to first meaningful phrase
        if (preg_match('/\b([A-Z][^.!?]+)/', $text, $matches)) {
            return trim($matches[1]);
        }
        
        return 'Planning Item';
    }
    
    /**
     * Parse date and time information from natural language
     * Handles relative dates, specific dates, times, and ranges
     */
    private function parseDateTime(string $text): array {
        $timeInfo = [
            'start_date' => null,
            'end_date' => null,
            'due_date' => null,
            'time_context' => []
        ];
        
        $currentDate = new DateTime('2025-08-07'); // Current date from context
        
        // Parse specific dates (YYYY-MM-DD, MM/DD/YYYY, etc.)
        if (preg_match('/\b(\d{4}[-\/]\d{1,2}[-\/]\d{1,2})\b/', $text, $matches)) {
            $timeInfo['start_date'] = $matches[1];
            $timeInfo['time_context']['specific_date'] = $matches[1];
        } elseif (preg_match('/\b(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})\b/', $text, $matches)) {
            $date = DateTime::createFromFormat('m/d/Y', $matches[1]);
            if ($date) {
                $timeInfo['start_date'] = $date->format('Y-m-d');
                $timeInfo['time_context']['specific_date'] = $matches[1];
            }
        }
        
        // Parse relative dates
        if (preg_match('/\b(today|tomorrow|yesterday)\b/i', $text, $matches)) {
            $relativeDate = strtolower($matches[1]);
            $date = clone $currentDate;
            
            switch ($relativeDate) {
                case 'today':
                    $timeInfo['start_date'] = $date->format('Y-m-d');
                    break;
                case 'tomorrow':
                    $date->add(new DateInterval('P1D'));
                    $timeInfo['start_date'] = $date->format('Y-m-d');
                    break;
                case 'yesterday':
                    $date->sub(new DateInterval('P1D'));
                    $timeInfo['start_date'] = $date->format('Y-m-d');
                    break;
            }
            $timeInfo['time_context']['relative_date'] = $relativeDate;
        }
        
        // Parse "next week", "this Friday", etc.
        if (preg_match('/\b(?:next|this)\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday|week|month)\b/i', $text, $matches)) {
            $timeInfo['time_context']['relative_period'] = $matches[0];
            // Could implement more sophisticated date calculation here
        }
        
        // Parse specific times (3:30 PM, 15:30, etc.)
        if (preg_match('/\b(\d{1,2}):(\d{2})\s*(am|pm|AM|PM)?\b/', $text, $matches)) {
            $hour = intval($matches[1]);
            $minute = intval($matches[2]);
            $ampm = strtolower($matches[3] ?? '');
            
            if ($ampm === 'pm' && $hour < 12) $hour += 12;
            if ($ampm === 'am' && $hour === 12) $hour = 0;
            
            $timeString = sprintf('%02d:%02d:00', $hour, $minute);
            $timeInfo['time_context']['time'] = $timeString;
            
            // Combine with date if available
            if ($timeInfo['start_date']) {
                $timeInfo['start_date'] = $timeInfo['start_date'] . ' ' . $timeString;
            }
        }
        
        // Parse due dates
        if (preg_match('/\b(?:due|deadline|by)\s+([^.!?]+)/i', $text, $matches)) {
            $dueText = trim($matches[1]);
            // Could parse the due date text further
            $timeInfo['time_context']['due_context'] = $dueText;
        }
        
        // Parse duration/time ranges
        if (preg_match('/\b(?:from|between)\s+([^-]+)\s*[-–—]\s*([^.!?]+)/i', $text, $matches)) {
            $timeInfo['time_context']['time_range'] = [
                'start' => trim($matches[1]),
                'end' => trim($matches[2])
            ];
        }
        
        return $timeInfo;
    }
    
    /**
     * Extract location information from text
     */
    private function extractLocation(string $text): string {
        // Pattern matching for common location phrases
        if (preg_match('/\b(?:at|in|from|to)\s+([A-Z][a-zA-Z\s,\d-]+(?:Room|Building|Office|Street|Ave|Avenue|Blvd|Boulevard|Center|Hall|Conference|Zoom|Teams|Skype))/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Online meeting patterns
        if (preg_match('/\b(zoom|teams|skype|google meet|webex|slack)\b/i', $text, $matches)) {
            return ucfirst(strtolower($matches[1])) . ' Meeting';
        }
        
        return '';
    }
    
    /**
     * Extract attendees/participants from text
     */
    private function extractAttendees(string $text): array {
        $attendees = [];
        
        // Pattern for "with [names]" - stop at time/location words
        if (preg_match('/\bwith\s+([A-Z][a-zA-Z\s]+?)(?:\s+(?:tomorrow|today|at|in|for|on)|\s*$)/i', $text, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 1 && preg_match('/^[A-Z]/', $name)) {
                $attendees[] = $name;
            }
        }
        
        return array_unique($attendees);
    }
    
    /**
     * Determine event type from context
     */
    private function determineEventType(string $text): string {
        $patterns = [
            'meeting' => '/\b(?:meeting|call|discussion|sync|standup|one-on-one)\b/i',
            'appointment' => '/\b(?:appointment|doctor|dentist|consultation|visit)\b/i',
            'event' => '/\b(?:event|conference|seminar|workshop|training|presentation)\b/i',
            'reminder' => '/\b(?:remind|reminder|note|remember)\b/i',
            'deadline' => '/\b(?:deadline|due|submit|deliver|finish)\b/i',
            'goal' => '/\b(?:goal|objective|target|achieve|complete)\b/i'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $text)) {
                return $type;
            }
        }
        
        return 'task';
    }
    
    /**
     * Extract recurrence patterns
     */
    private function extractRecurrence(string $text): ?string {
        $patterns = [
            'daily' => '/\b(?:daily|every day|each day)\b/i',
            'weekly' => '/\b(?:weekly|every week|each week)\b/i',
            'monthly' => '/\b(?:monthly|every month|each month)\b/i',
            'yearly' => '/\b(?:yearly|annually|every year|each year)\b/i'
        ];
        
        foreach ($patterns as $recurrence => $pattern) {
            if (preg_match($pattern, $text)) {
                return $recurrence;
            }
        }
        
        return null;
    }
    
    /**
     * Extract duration information
     */
    private function extractDuration(string $text): ?int {
        // Pattern for explicit durations
        if (preg_match('/\b(\d+)\s*(minutes?|mins?|hours?|hrs?)\b/i', $text, $matches)) {
            $number = intval($matches[1]);
            $unit = strtolower($matches[2]);
            
            if (in_array($unit, ['hour', 'hours', 'hr', 'hrs'])) {
                return $number * 60; // Convert to minutes
            } else {
                return $number; // Already in minutes
            }
        }
        
        return null;
    }
    
    /**
     * Extract priority indicators from text
     */
    private function extractPriorityIndicators(string $text): array {
        $indicators = [];
        
        $urgentPatterns = ['/\b(?:urgent|asap|immediately|critical|emergency)\b/i'];
        $highPatterns = ['/\b(?:important|priority|high priority|crucial|vital)\b/i'];
        $lowPatterns = ['/\b(?:low priority|when possible|eventually|sometime)\b/i'];
        
        foreach ($urgentPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $indicators[] = 'urgent';
            }
        }
        
        foreach ($highPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $indicators[] = 'high';
            }
        }
        
        foreach ($lowPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $indicators[] = 'low';
            }
        }
        
        return array_unique($indicators);
    }
    
    /**
     * Generate contextual tags based on extracted information
     */
    private function generateTags(array $extracted, string $text): array {
        $tags = [];
        
        // Type-based tags
        $tags[] = $extracted['type'];
        
        // Time-based tags
        if (!empty($extracted['time_context'])) {
            $tags[] = 'scheduled';
        }
        
        if ($extracted['recurrence']) {
            $tags[] = 'recurring';
        }
        
        // Location-based tags
        if ($extracted['location']) {
            if (preg_match('/\b(?:zoom|teams|skype|online)\b/i', $extracted['location'])) {
                $tags[] = 'online';
            } else {
                $tags[] = 'in-person';
            }
        }
        
        // Content-based tags
        if (preg_match('/\b(?:work|business|professional|office|client|project)\b/i', $text)) {
            $tags[] = 'work';
        }
        
        if (preg_match('/\b(?:personal|family|home|friend|social)\b/i', $text)) {
            $tags[] = 'personal';
        }
        
        return array_unique($tags);
    }
    
    /**
     * Generate description from extracted context
     */
    private function generateDescription(array $extracted, string $text): string {
        $parts = [];
        
        if ($extracted['location']) {
            $parts[] = "Location: " . $extracted['location'];
        }
        
        if (!empty($extracted['attendees'])) {
            $attendees = is_array($extracted['attendees']) ? $extracted['attendees'] : [$extracted['attendees']];
            $parts[] = "Attendees: " . implode(', ', $attendees);
        }
        
        if ($extracted['duration']) {
            $parts[] = "Duration: " . $extracted['duration'] . " minutes";
        }
        
        if ($extracted['recurrence']) {
            $parts[] = "Recurrence: " . ucfirst($extracted['recurrence']);
        }
        
        return implode(' | ', $parts);
    }
    
    /**
     * Combine descriptions intelligently
     */
    private function combineDescriptions(string $original, string $extracted): string {
        $descriptions = array_filter([trim($original), trim($extracted)]);
        return implode(' | ', $descriptions);
    }
    
    /**
     * Determine priority based on extracted information
     */
    private function determinePriority(array $extractedInfo, array $originalData): string {
        // Check original data first
        if (!empty($originalData['priority'])) {
            return $originalData['priority'];
        }
        
        // Use priority indicators
        if (!empty($extractedInfo['priority_indicators'])) {
            if (in_array('urgent', $extractedInfo['priority_indicators'])) {
                return 'urgent';
            } elseif (in_array('high', $extractedInfo['priority_indicators'])) {
                return 'high';
            } elseif (in_array('low', $extractedInfo['priority_indicators'])) {
                return 'low';
            }
        }
        
        // Default priority based on type
        $typeDefaults = [
            'deadline' => 'high',
            'meeting' => 'medium',
            'appointment' => 'medium',
            'reminder' => 'low',
            'goal' => 'medium'
        ];
        
        $type = $extractedInfo['type'] ?? 'task';
        return $typeDefaults[$type] ?? 'medium';
    }
    
    /**
     * Generate smart reminders based on event type and timing
     */
    private function generateSmartReminders(array $extractedInfo): array {
        $reminders = [];
        
        $type = $extractedInfo['type'] ?? 'task';
        
        // Default reminders based on event type
        switch ($type) {
            case 'meeting':
            case 'appointment':
                $reminders = ['15 minutes before', '1 day before'];
                break;
            case 'deadline':
                $reminders = ['1 day before', '1 week before'];
                break;
            case 'event':
                $reminders = ['1 hour before', '1 day before'];
                break;
            default:
                $reminders = ['1 day before'];
        }
        
        return $reminders;
    }
    
    /**
     * Enhance planning with contextual tools
     * Uses tools like Weather, Calendar, and Search to provide enhanced planning features
     * 
     * @param array $planningData Extracted planning data
     * @return array Enhanced planning data with tool insights
     */
    private function enhancePlanningWithTools(array $planningData): array {
        // Initialize enhanced data
        $enhanced = $planningData;
        $enhanced['tool_insights'] = [];
        
        try {
            // Use CalendarTool for better date parsing
            if (!empty($planningData['original_text'])) {
                // Get calendar tool through ToolManager
                $calendarTool = $this->toolManager->getTool('calendar', 'PlannerAgent');
                
                if ($calendarTool) {
                    $dateText = $planningData['original_text'];
                    
                    // Extract date text from context if possible
                    if (preg_match('/(?:schedule|on|for|at|by)\s+([^.!?]+)/i', $dateText, $matches)) {
                        $dateText = $matches[1];
                    }
                    
                    $parsedDate = $calendarTool->parseNaturalDate($dateText);
                    if ($parsedDate['success']) {
                        // Use the parsed date if confidence is high
                        if ($parsedDate['confidence'] > 70) {
                            $enhanced['start_date'] = $parsedDate['datetime']->format('Y-m-d H:i:s');
                            $enhanced['tool_insights']['calendar'] = [
                                'parsed_date' => $parsedDate['date_string'],
                                'parsed_time' => $parsedDate['time_string'],
                                'confidence' => $parsedDate['confidence'] . '%',
                                'description' => $parsedDate['relative_description']
                            ];
                        }
                    }
                }
            }
            
            // Use WeatherTool for location-based planning
            if (!empty($enhanced['location'])) {
                // Get weather tool through ToolManager
                $weatherTool = $this->toolManager->getTool('weather', 'PlannerAgent');
                
                if ($weatherTool) {
                    $location = $enhanced['location'];
                    
                    // If location contains an online meeting platform, skip weather check
                    if (!preg_match('/\b(zoom|teams|skype|meet|webex)\b/i', $location)) {
                        $weather = $weatherTool->getCurrentWeather($location);
                        
                        // If start_date is tomorrow or later, get forecast instead
                        if (!empty($enhanced['start_date'])) {
                            $today = new DateTime('today');
                            $eventDate = new DateTime($enhanced['start_date']);
                            
                            if ($eventDate > $today) {
                                $daysDiff = $today->diff($eventDate)->days;
                                if ($daysDiff <= 5) { // Only if within forecast range
                                    $forecast = $weatherTool->getForecast($location, $daysDiff);
                                    if (!empty($forecast['days'])) {
                                        $weatherInfo = $forecast['days'][0];
                                        $enhanced['tool_insights']['weather'] = [
                                            'forecast' => $weatherInfo['primary_condition'],
                                            'temperature' => $weatherInfo['temperature_avg'] . '°C',
                                            'location' => $location,
                                            'type' => 'forecast'
                                        ];
                                    }
                                }
                            }
                        }
                        
                        // Use current weather as fallback
                        if (empty($enhanced['tool_insights']['weather']) && !empty($weather)) {
                            $enhanced['tool_insights']['weather'] = [
                                'current' => $weather['description'],
                                'temperature' => $weather['temperature'] . '°C',
                                'location' => $location,
                                'type' => 'current'
                            ];
                        }
                    }
                }
            }
            
            // Use SearchTool for contextual information
            if (!empty($enhanced['title']) && strlen($enhanced['description']) < 100) {
                // Get search tool through ToolManager
                $searchTool = $this->toolManager->getTool('search', 'PlannerAgent');
                
                if ($searchTool) {
                    $searchQuery = $enhanced['title'] . ' best practices';
                    $results = $searchTool->search($searchQuery, 1);
                    
                    if (!empty($results['results'])) {
                        $enhanced['tool_insights']['search'] = [
                            'query' => $searchQuery,
                            'info' => substr($results['results'][0]['snippet'], 0, 200),
                            'source' => $results['results'][0]['url']
                        ];
                        
                        // Use search results to enhance description if needed
                        if (empty($enhanced['description'])) {
                            $enhanced['description'] = 'This ' . $enhanced['type'] . ' might involve: ' . 
                                substr($results['results'][0]['snippet'], 0, 100) . '...';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Log errors but don't break the agent
            error_log('PlannerAgent tool integration error: ' . $e->getMessage());
        }
        
        return $enhanced;
    }
}

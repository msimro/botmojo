<?php
/**
 * PlannerAgent - Time Management and Scheduling Component Creator
 * 
 * This agent specializes in managing time, schedules, tasks, and goals.
 * It creates planning components for events, tasks, reminders, and 
 * time-based activities with comprehensive scheduling information.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class PlannerAgent {
    
    /**
     * Create a planning component from provided data
     * Processes scheduling and task data into a standardized planning component
     * 
     * @param array $data Raw planning data from the triage system
     * @return array Standardized planning component with comprehensive scheduling info
     */
    public function createComponent(array $data): array {
        return [
            // Core planning information
            'title' => $data['title'] ?? '',                              // Task/event title
            'description' => $data['description'] ?? '',                  // Detailed description
            'type' => $data['type'] ?? 'task',                           // Planning type: task, event, goal, reminder
            
            // Time-related fields
            'start_date' => $data['start_date'] ?? null,                 // When it starts (for events)
            'end_date' => $data['end_date'] ?? null,                     // When it ends (for events)
            'due_date' => $data['due_date'] ?? null,                     // When it's due (for tasks)
            
            // Priority and status management
            'priority' => $data['priority'] ?? 'medium',                 // Priority level: low, medium, high, urgent
            'status' => $data['status'] ?? 'pending',                    // Current status: pending, in_progress, completed, cancelled
            
            // Location and people
            'location' => $data['location'] ?? '',                       // Where it takes place
            'attendees' => $data['attendees'] ?? [],                     // Who's involved (for events)
            
            // Advanced scheduling features
            'reminders' => $data['reminders'] ?? [],                     // Reminder settings
            'recurrence' => $data['recurrence'] ?? null,                 // Repeat pattern: daily, weekly, monthly, yearly
            'estimated_duration' => $data['estimated_duration'] ?? null   // Expected duration in minutes
        ];
    }
}

<?php
/**
 * FitnessTool - Health and Fitness Data Manager for MDO
 */
class FitnessTool {
    public function execute(array $params) {
        $userId = $params['user_id'] ?? 'default_user';
        $requestType = $params['request_type'] ?? 'get_data';
        
        // Simplified mock data for POC
        if ($requestType === 'get_data') {
            return [
                'daily_steps' => [
                    date('Y-m-d') => 8432,
                    date('Y-m-d', strtotime('-1 day')) => 7689,
                    date('Y-m-d', strtotime('-2 days')) => 9125
                ],
                'exercise_minutes' => [
                    date('Y-m-d') => 45,
                    date('Y-m-d', strtotime('-1 day')) => 30,
                    date('Y-m-d', strtotime('-2 days')) => 60
                ],
                'heart_rate' => [
                    'resting' => 68,
                    'average' => 72
                ],
                'sleep' => [
                    date('Y-m-d') => 7.5,
                    date('Y-m-d', strtotime('-1 day')) => 6.8,
                    date('Y-m-d', strtotime('-2 days')) => 8.2
                ]
            ];
        } elseif ($requestType === 'record_activity') {
            $activityType = $params['activity_type'] ?? 'walking';
            $duration = $params['duration'] ?? 30;
            $intensity = $params['intensity'] ?? 'moderate';
            
            return [
                'status' => 'success',
                'message' => "Recorded {$activityType} activity for {$duration} minutes at {$intensity} intensity",
                'recorded_at' => date('Y-m-d H:i:s')
            ];
        } elseif ($requestType === 'get_trends') {
            return [
                'steps_trend' => [
                    'average' => 7500,
                    'trend' => 'increasing',
                    'change_percent' => 5.2
                ],
                'sleep_trend' => [
                    'average_hours' => 7.2,
                    'trend' => 'stable',
                    'change_percent' => 0.5
                ],
                'exercise_trend' => [
                    'average_minutes' => 35,
                    'trend' => 'increasing',
                    'change_percent' => 15.3
                ]
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Invalid request type'
        ];
    }
}

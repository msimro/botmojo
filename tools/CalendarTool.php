<?php
/**
 * CalendarTool - Date, Time and Calendar Operations
 * 
 * This tool provides comprehensive date/time manipulation, calendar operations,
 * and intelligent date parsing for scheduling and time-based queries.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class CalendarTool {
    
    /** @var DateTimeZone Default timezone */
    private DateTimeZone $timezone;
    
    /** @var array Days of the week */
    private array $daysOfWeek = [
        'sunday', 'monday', 'tuesday', 'wednesday', 
        'thursday', 'friday', 'saturday'
    ];
    
    /** @var array Months of the year */
    private array $months = [
        'january', 'february', 'march', 'april', 'may', 'june',
        'july', 'august', 'september', 'october', 'november', 'december'
    ];
    
    /**
     * Constructor - Initialize calendar tool
     * 
     * @param string $timezone Timezone identifier (default: system timezone)
     */
    public function __construct(string $timezone = '') {
        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }
        
        try {
            $this->timezone = new DateTimeZone($timezone);
        } catch (Exception $e) {
            $this->timezone = new DateTimeZone('UTC');
            error_log("CalendarTool: Invalid timezone '{$timezone}', falling back to UTC");
        }
    }
    
    /**
     * Parse natural language date/time expressions
     * 
     * @param string $input Natural language date/time string
     * @param DateTime|null $baseDate Base date for relative calculations
     * @return array Parsed date information
     */
    public function parseNaturalDate(string $input, ?DateTime $baseDate = null): array {
        if (!$baseDate) {
            $baseDate = new DateTime('now', $this->timezone);
        }
        
        $input = strtolower(trim($input));
        $result = [
            'parsed_input' => $input,
            'success' => false,
            'datetime' => null,
            'date_string' => '',
            'time_string' => '',
            'relative_description' => '',
            'confidence' => 0,
            'components' => []
        ];
        
        // Handle today/tomorrow/yesterday
        if ($this->matchesPattern($input, ['today', 'now'])) {
            $result['datetime'] = clone $baseDate;
            $result['relative_description'] = 'today';
            $result['confidence'] = 100;
        } elseif ($this->matchesPattern($input, ['tomorrow'])) {
            $result['datetime'] = (clone $baseDate)->modify('+1 day');
            $result['relative_description'] = 'tomorrow';
            $result['confidence'] = 100;
        } elseif ($this->matchesPattern($input, ['yesterday'])) {
            $result['datetime'] = (clone $baseDate)->modify('-1 day');
            $result['relative_description'] = 'yesterday';
            $result['confidence'] = 100;
        }
        
        // Handle next/last + day of week
        foreach ($this->daysOfWeek as $day) {
            if (strpos($input, "next {$day}") !== false) {
                $result['datetime'] = $this->getNextWeekday($day, $baseDate);
                $result['relative_description'] = "next {$day}";
                $result['confidence'] = 95;
                break;
            } elseif (strpos($input, "last {$day}") !== false) {
                $result['datetime'] = $this->getLastWeekday($day, $baseDate);
                $result['relative_description'] = "last {$day}";
                $result['confidence'] = 95;
                break;
            } elseif (strpos($input, $day) !== false && !isset($result['datetime'])) {
                $result['datetime'] = $this->getNextWeekday($day, $baseDate);
                $result['relative_description'] = "next {$day}";
                $result['confidence'] = 80;
            }
        }
        
        // Handle "in X days/weeks/months"
        if (preg_match('/in (\d+) (day|days|week|weeks|month|months)/', $input, $matches)) {
            $number = (int)$matches[1];
            $unit = $matches[2];
            
            $result['datetime'] = clone $baseDate;
            if (strpos($unit, 'day') === 0) {
                $result['datetime']->modify("+{$number} days");
                $result['relative_description'] = "in {$number} " . ($number === 1 ? 'day' : 'days');
            } elseif (strpos($unit, 'week') === 0) {
                $result['datetime']->modify("+{$number} weeks");
                $result['relative_description'] = "in {$number} " . ($number === 1 ? 'week' : 'weeks');
            } elseif (strpos($unit, 'month') === 0) {
                $result['datetime']->modify("+{$number} months");
                $result['relative_description'] = "in {$number} " . ($number === 1 ? 'month' : 'months');
            }
            $result['confidence'] = 90;
        }
        
        // Extract time information
        $timeMatches = [];
        if (preg_match('/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $input, $timeMatches)) {
            $hour = (int)$timeMatches[1];
            $minute = (int)$timeMatches[2];
            $ampm = strtolower($timeMatches[3] ?? '');
            
            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            } elseif ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }
            
            if ($result['datetime']) {
                $result['datetime']->setTime($hour, $minute);
            } else {
                $result['datetime'] = clone $baseDate;
                $result['datetime']->setTime($hour, $minute);
            }
            
            $result['time_string'] = sprintf("%02d:%02d", $hour, $minute);
            $result['confidence'] = min($result['confidence'] + 10, 100);
        } elseif (preg_match('/(\d{1,2})\s*(am|pm)/i', $input, $timeMatches)) {
            $hour = (int)$timeMatches[1];
            $ampm = strtolower($timeMatches[2]);
            
            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            } elseif ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }
            
            if ($result['datetime']) {
                $result['datetime']->setTime($hour, 0);
            } else {
                $result['datetime'] = clone $baseDate;
                $result['datetime']->setTime($hour, 0);
            }
            
            $result['time_string'] = sprintf("%02d:00", $hour);
            $result['confidence'] = min($result['confidence'] + 10, 100);
        }
        
        // Fallback: try PHP's strtotime
        if (!$result['datetime']) {
            $timestamp = strtotime($input, $baseDate->getTimestamp());
            if ($timestamp !== false) {
                $result['datetime'] = new DateTime();
                $result['datetime']->setTimestamp($timestamp);
                $result['datetime']->setTimezone($this->timezone);
                $result['relative_description'] = 'parsed automatically';
                $result['confidence'] = 60;
            }
        }
        
        if ($result['datetime']) {
            $result['success'] = true;
            $result['date_string'] = $result['datetime']->format('Y-m-d');
            if (empty($result['time_string'])) {
                $result['time_string'] = $result['datetime']->format('H:i');
            }
        }
        
        return $result;
    }
    
    /**
     * Get the next occurrence of a specific weekday
     * 
     * @param string $dayName Name of the day (e.g., 'monday')
     * @param DateTime $from Starting date
     * @return DateTime Next occurrence of the weekday
     */
    public function getNextWeekday(string $dayName, DateTime $from): DateTime {
        $dayName = strtolower($dayName);
        $targetDay = array_search($dayName, $this->daysOfWeek);
        
        if ($targetDay === false) {
            throw new InvalidArgumentException("Invalid day name: {$dayName}");
        }
        
        $currentDay = (int)$from->format('w'); // 0 = Sunday, 6 = Saturday
        $daysUntilTarget = ($targetDay - $currentDay + 7) % 7;
        
        if ($daysUntilTarget === 0) {
            $daysUntilTarget = 7; // If it's the same day, get next week's occurrence
        }
        
        $nextOccurrence = clone $from;
        $nextOccurrence->modify("+{$daysUntilTarget} days");
        
        return $nextOccurrence;
    }
    
    /**
     * Get the last occurrence of a specific weekday
     * 
     * @param string $dayName Name of the day (e.g., 'monday')
     * @param DateTime $from Starting date
     * @return DateTime Last occurrence of the weekday
     */
    public function getLastWeekday(string $dayName, DateTime $from): DateTime {
        $dayName = strtolower($dayName);
        $targetDay = array_search($dayName, $this->daysOfWeek);
        
        if ($targetDay === false) {
            throw new InvalidArgumentException("Invalid day name: {$dayName}");
        }
        
        $currentDay = (int)$from->format('w');
        $daysSinceTarget = ($currentDay - $targetDay + 7) % 7;
        
        if ($daysSinceTarget === 0) {
            $daysSinceTarget = 7; // If it's the same day, get last week's occurrence
        }
        
        $lastOccurrence = clone $from;
        $lastOccurrence->modify("-{$daysSinceTarget} days");
        
        return $lastOccurrence;
    }
    
    /**
     * Calculate time until a specific date/time
     * 
     * @param DateTime $targetDate Target date
     * @param DateTime|null $from Starting date (default: now)
     * @return array Time difference information
     */
    public function getTimeUntil(DateTime $targetDate, ?DateTime $from = null): array {
        if (!$from) {
            $from = new DateTime('now', $this->timezone);
        }
        
        $interval = $from->diff($targetDate);
        $isPast = $interval->invert === 1;
        
        return [
            'target_date' => $targetDate->format('Y-m-d H:i:s'),
            'from_date' => $from->format('Y-m-d H:i:s'),
            'is_past' => $isPast,
            'days' => $interval->days,
            'hours' => $interval->h,
            'minutes' => $interval->i,
            'seconds' => $interval->s,
            'total_seconds' => abs($targetDate->getTimestamp() - $from->getTimestamp()),
            'human_readable' => $this->formatTimeDifference($interval, $isPast)
        ];
    }
    
    /**
     * Check if a date falls on a weekend
     * 
     * @param DateTime $date Date to check
     * @return bool True if weekend, false otherwise
     */
    public function isWeekend(DateTime $date): bool {
        $dayOfWeek = (int)$date->format('w');
        return $dayOfWeek === 0 || $dayOfWeek === 6; // Sunday or Saturday
    }
    
    /**
     * Get business days between two dates
     * 
     * @param DateTime $startDate Start date
     * @param DateTime $endDate End date
     * @return int Number of business days
     */
    public function getBusinessDays(DateTime $startDate, DateTime $endDate): int {
        $businessDays = 0;
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            if (!$this->isWeekend($current)) {
                $businessDays++;
            }
            $current->modify('+1 day');
        }
        
        return $businessDays;
    }
    
    /**
     * Generate calendar view for a specific month
     * 
     * @param int $year Year
     * @param int $month Month (1-12)
     * @return array Calendar data
     */
    public function getMonthCalendar(int $year, int $month): array {
        $firstDay = new DateTime("{$year}-{$month}-01", $this->timezone);
        $lastDay = clone $firstDay;
        $lastDay->modify('last day of this month');
        
        $calendar = [
            'year' => $year,
            'month' => $month,
            'month_name' => $firstDay->format('F'),
            'first_day' => $firstDay->format('Y-m-d'),
            'last_day' => $lastDay->format('Y-m-d'),
            'days_in_month' => (int)$lastDay->format('d'),
            'first_day_of_week' => (int)$firstDay->format('w'),
            'weeks' => []
        ];
        
        $current = clone $firstDay;
        $current->modify('-' . $calendar['first_day_of_week'] . ' days');
        
        $week = [];
        for ($i = 0; $i < 42; $i++) { // 6 weeks × 7 days
            $week[] = [
                'date' => $current->format('Y-m-d'),
                'day' => (int)$current->format('d'),
                'is_current_month' => (int)$current->format('m') === $month,
                'is_weekend' => $this->isWeekend($current),
                'is_today' => $current->format('Y-m-d') === date('Y-m-d')
            ];
            
            if (count($week) === 7) {
                $calendar['weeks'][] = $week;
                $week = [];
            }
            
            $current->modify('+1 day');
        }
        
        return $calendar;
    }
    
    /**
     * Get date suggestions based on context
     * 
     * @param string $context Context string (e.g., "meeting", "deadline", "vacation")
     * @param DateTime|null $from Base date for suggestions
     * @return array Array of suggested dates with reasons
     */
    public function getSuggestedDates(string $context, ?DateTime $from = null): array {
        if (!$from) {
            $from = new DateTime('now', $this->timezone);
        }
        
        $suggestions = [];
        $context = strtolower($context);
        
        // Context-specific suggestions
        if (strpos($context, 'meeting') !== false) {
            // Suggest next few business days
            $current = clone $from;
            $current->modify('+1 day');
            $count = 0;
            
            while ($count < 5) {
                if (!$this->isWeekend($current)) {
                    $suggestions[] = [
                        'date' => $current->format('Y-m-d'),
                        'reason' => 'Good for business meetings',
                        'confidence' => 90
                    ];
                    $count++;
                }
                $current->modify('+1 day');
            }
        } elseif (strpos($context, 'vacation') !== false || strpos($context, 'holiday') !== false) {
            // Suggest upcoming weekends and longer periods
            $current = clone $from;
            
            for ($i = 0; $i < 8; $i++) {
                $current->modify('+1 week');
                $weekend = clone $current;
                $weekend->modify('next saturday');
                
                $suggestions[] = [
                    'date' => $weekend->format('Y-m-d'),
                    'reason' => 'Weekend start for vacation',
                    'confidence' => 80
                ];
            }
        } elseif (strpos($context, 'deadline') !== false) {
            // Suggest dates with buffer time
            $suggestions[] = [
                'date' => (clone $from)->modify('+3 days')->format('Y-m-d'),
                'reason' => 'Short-term deadline',
                'confidence' => 85
            ];
            
            $suggestions[] = [
                'date' => (clone $from)->modify('+1 week')->format('Y-m-d'),
                'reason' => 'One week deadline',
                'confidence' => 90
            ];
            
            $suggestions[] = [
                'date' => (clone $from)->modify('+2 weeks')->format('Y-m-d'),
                'reason' => 'Two week deadline',
                'confidence' => 85
            ];
        } else {
            // Generic suggestions
            for ($i = 1; $i <= 7; $i++) {
                $suggestionDate = clone $from;
                $suggestionDate->modify("+{$i} days");
                
                $suggestions[] = [
                    'date' => $suggestionDate->format('Y-m-d'),
                    'reason' => $this->isWeekend($suggestionDate) ? 'Weekend day' : 'Weekday',
                    'confidence' => $this->isWeekend($suggestionDate) ? 70 : 80
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Check if input matches any pattern
     * 
     * @param string $input Input string
     * @param array $patterns Patterns to match
     * @return bool True if any pattern matches
     */
    private function matchesPattern(string $input, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (strpos($input, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Format time difference in human readable format
     * 
     * @param DateInterval $interval Date interval
     * @param bool $isPast Whether the date is in the past
     * @return string Human readable format
     */
    private function formatTimeDifference(DateInterval $interval, bool $isPast): string {
        $parts = [];
        
        if ($interval->days > 0) {
            $parts[] = $interval->days . ' ' . ($interval->days === 1 ? 'day' : 'days');
        }
        
        if ($interval->h > 0) {
            $parts[] = $interval->h . ' ' . ($interval->h === 1 ? 'hour' : 'hours');
        }
        
        if ($interval->i > 0 && $interval->days === 0) {
            $parts[] = $interval->i . ' ' . ($interval->i === 1 ? 'minute' : 'minutes');
        }
        
        if (empty($parts)) {
            return 'less than a minute' . ($isPast ? ' ago' : '');
        }
        
        $result = implode(', ', array_slice($parts, 0, 2));
        return $result . ($isPast ? ' ago' : '');
    }
}

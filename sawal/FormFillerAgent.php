<?php
class FormFillerAgent {
    // This is the main state-management function.
    public function processTurn(array $conversationState, string $userAnswer): array {
        $formDef = $conversationState['form_definition'];
        $currentQuestionIndex = $conversationState['current_question_index'];
        $currentQuestion = $formDef['questions'][$currentQuestionIndex];

        // --- FIX: Check if we are currently waiting for a confirmation first ---
        if ($conversationState['is_confirming']) {
            $conversationState['is_confirming'] = false; // Reset confirmation flag regardless of answer
            if (strtolower(trim($userAnswer)) === 'yes') {
                // Confirmed! Save the temp answer permanently.
                $conversationState['answers'][$currentQuestion['id']] = $conversationState['temp_answer'];
                unset($conversationState['temp_answer']);
                return $this->moveToNextQuestion($conversationState);
            } else {
                // Not confirmed. Re-ask the original question.
                unset($conversationState['temp_answer']);
                $conversationState['next_prompt'] = "My mistake. Let's try again. " . $this->substitutePlaceholders($currentQuestion['question_text'], $conversationState['answers']);
                return $conversationState;
            }
        }

        // --- 1. Handle skippable questions ---
        if (strtolower(trim($userAnswer)) === 'skip' && !$currentQuestion['required']) {
            $conversationState['answers'][$currentQuestion['id']] = null;
            return $this->moveToNextQuestion($conversationState);
        }

        // --- 2. Validate the user's answer ---
        $isValid = $this->validateAnswer($userAnswer, $currentQuestion);
        if (!$isValid) {
            $conversationState['next_prompt'] = $currentQuestion['error_message'] ?? "I'm sorry, that's not a valid answer. " . $this->substitutePlaceholders($currentQuestion['question_text'], $conversationState['answers']);
            return $conversationState;
        }

        // --- 3. Store the validated answer ---
        $answerToStore = $this->formatAnswer($userAnswer, $currentQuestion['type']);
        
        // --- 4. Check if we need to confirm this answer ---
        if (isset($currentQuestion['confirmation_needed']) && $currentQuestion['confirmation_needed']) {
            $conversationState['is_confirming'] = true;
            $conversationState['temp_answer'] = $answerToStore; // Store validated answer temporarily
            $conversationState['next_prompt'] = "Just to confirm, you said '{$userAnswer}'. Is that correct? (Yes/No)";
            return $conversationState;
        }
        
        // --- 5. If no confirmation was needed, save answer and move on ---
        $conversationState['answers'][$currentQuestion['id']] = $answerToStore;
        return $this->moveToNextQuestion($conversationState);
    }

    private function moveToNextQuestion(array $state): array {
        $state['current_question_index']++;
        if ($state['current_question_index'] >= count($state['form_definition']['questions'])) {
            // Form is complete!
            $state['is_complete'] = true;
            $summary = "Great, I have all the information! Here's a summary:\n\n";
            foreach($state['answers'] as $key => $value) {
                if (is_bool($value)) {
                    $displayValue = $value ? 'Yes' : 'No';
                } else {
                    $displayValue = $value ?? 'skipped';
                }
                $summary .= "- {$key}: " . $displayValue . "\n";
            }
            $state['next_prompt'] = $summary . "\nShall I save this information? (Yes/No)";
        } else {
            // Ask the next question
            $nextQuestionText = $state['form_definition']['questions'][$state['current_question_index']]['question_text'];
            $state['next_prompt'] = $this->substitutePlaceholders($nextQuestionText, $state['answers']);
        }
        return $state;
    }
    
    // New helper function to handle placeholder replacement
    private function substitutePlaceholders(string $text, array $answers): string {
        foreach($answers as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }
    
    // New helper function to format answers to correct type
    private function formatAnswer(string $answer, string $type) {
        if ($type === 'boolean') {
            return strtolower(trim($answer)) === 'yes';
        }
        if ($type === 'number') {
            return (float)$answer;
        }
        return $answer;
    }

    private function validateAnswer(string $answer, array $questionDef): bool {
        // Required check
        if ($questionDef['required'] && empty(trim($answer))) {
            return false;
        }
        // Regex check
        if (isset($questionDef['validation_regex']) && !empty(trim($answer))) {
            // Error suppression operator @ to prevent warnings on invalid patterns
            return @preg_match($questionDef['validation_regex'], $answer);
        }
        // Type-specific checks
        if ($questionDef['type'] === 'number' && !empty(trim($answer)) && !is_numeric($answer)) {
            return false;
        }
        if ($questionDef['type'] === 'boolean' && !in_array(strtolower(trim($answer)), ['yes', 'no'])) {
            return false;
        }
        return true;
    }
}
<?php
header('Content-Type: application/json');
require_once 'FormFillerAgent.php';

$request_body = json_decode(file_get_contents('php://input'), true);
$formName = $request_body['form_name'] ?? null;
$userAnswer = $request_body['answer'] ?? '';
$conversationState = $request_body['state'] ?? null;

$agent = new FormFillerAgent();
$responseState = [];

try {
    if (!$conversationState) {
        // --- This is the FIRST turn in the conversation ---
        $formFile = __DIR__ . '/forms/' . $formName . '.json';
        if (!file_exists($formFile)) {
            throw new Exception("Form '{$formName}' not found.");
        }
        $formDef = json_decode(file_get_contents($formFile), true);

        // Initialize the state
        $responseState = [
            'form_definition' => $formDef,
            'current_question_index' => 0,
            'answers' => [],
            'is_complete' => false,
            'is_confirming' => false,
            'next_prompt' => $formDef['questions'][0]['question_text']
        ];

    } else {
        // --- This is a SUBSEQUENT turn ---
        if ($conversationState['is_complete']) {
            // Handle the final "save?" confirmation
            if(strtolower(trim($userAnswer)) === 'yes') {
                 // In a real app, this would trigger the MemoryAgent to save $conversationState['answers']
                $conversationState['next_prompt'] = "Perfect! I've saved the new client information.";
                $conversationState['is_terminated'] = true;
            } else {
                $conversationState['next_prompt'] = "Okay, I've discarded the information. Let me know if you need anything else!";
                $conversationState['is_terminated'] = true;
            }
            $responseState = $conversationState;
        } else {
            // Process the user's answer and get the new state
            $responseState = $agent->processTurn($conversationState, $userAnswer);
        }
    }
    
    echo json_encode($responseState);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
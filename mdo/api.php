<?php
// --- SETUP & CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once 'config.php';

// --- AGENT AUTOLOADER ---
spl_autoload_register(function ($className) {
    if (file_exists(__DIR__ . '/agents/' . $className . '.php')) {
        require_once __DIR__ . '/agents/' . $className . '.php';
    } else if (file_exists(__DIR__ . '/tools/' . $className . '.php')) {
        require_once __DIR__ . '/tools/' . $className . '.php';
    }
});

// --- HELPER FUNCTIONS ---
// The makeGeminiCall function is IDENTICAL to the previous version.
function makeGeminiCall(string $prompt) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['responseMimeType' => 'application/json']
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) throw new Exception("cURL Error: " . $error);
    $result = json_decode($response, true);
    if (isset($result['error'])) throw new Exception("Gemini API Error: " . $result['error']['message']);
    return json_decode($result['candidates'][0]['content']['parts'][0]['text'] ?? '{}', true);
}

try {
    $userInput = $_POST['query'] ?? null;
    if (!$userInput) throw new Exception("No query provided.");

    // Initialize the Tool Manager
    $toolManager = new ToolManager();

    // 1. Triage Stage
    $triagePrompt = file_get_contents('gemini_triage_prompt.txt');
    $fullPrompt = $triagePrompt . "\n\nUser Input: \"{$userInput}\"";
    $executionPlan = makeGeminiCall($fullPrompt);

    if (empty($executionPlan) || !isset($executionPlan['tasks'])) {
        throw new Exception("Triage Agent failed to create a valid plan.");
    }
    
    // ** Extract the user-facing response **
    $userMessage = $executionPlan['suggested_response'] ?? "Okay, I'll take care of that.";

    // 2. Routing & Execution Stage
    $results = [];
    foreach ($executionPlan['tasks'] as $task) {
        $agentName = $task['target_agent'] ?? 'GeneralistAgent';
        $taskResult = ["message" => "ERROR: Agent '{$agentName}' not found or failed to execute."];
        
        // Check for agent existence
        if (class_exists($agentName)) {
            $agent = new $agentName();
            
            // Process tool usage if specified
            $toolResults = [];
            if (isset($task['tools']) && is_array($task['tools'])) {
                foreach ($task['tools'] as $toolRequest) {
                    $toolName = $toolRequest['tool_name'] ?? '';
                    $toolParams = $toolRequest['tool_parameters'] ?? [];
                    
                    // Get tool via ToolManager
                    $tool = $toolManager->getTool($toolName, $agentName);
                    
                    if ($tool) {
                        // Execute tool and store results
                        $toolResults[$toolName] = $tool->execute($toolParams);
                    } else {
                        $toolResults[$toolName] = "Tool not available or permission denied";
                    }
                }
            }
            
            // Add tool results to task parameters for agent use
            $task['tool_results'] = $toolResults;
            
            // Execute agent with updated task
            $taskResult = $agent->execute($task);
        }
        
        $results[] = [
            'task_id' => $task['task_id'], 
            'executed_by' => $agentName, 
            'result' => $taskResult
        ];
    }
    
    // 3. Response Stage
    $finalResponse = [
        'ai_message_to_user' => $userMessage,
        'triage_plan' => $executionPlan,
        'execution_results' => $results
    ];
    echo json_encode($finalResponse, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
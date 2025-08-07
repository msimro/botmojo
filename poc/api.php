<?php
// --- SETUP & CONFIGURATION ---

// Enable full error reporting to catch any PHP issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Include necessary configuration
require_once 'config.php';


// --- LOGGING FUNCTION ---

/**
 * Writes a message to the debug.log file with a timestamp.
 * Make sure this file is writable by the web server (e.g., chmod 666 debug.log).
 * @param string $message The message to log.
 */
function write_log($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}


// --- HELPER FUNCTIONS (The System's "Tools") ---

/**
 * Gets a singleton database connection.
 * @return mysqli The database connection object.
 */
function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/**
 * Placeholder for creating/updating an entity in the database.
 * @param array $parameters Extracted parameters from the Triage Agent.
 * @return string A confirmation message.
 */
function executeCreateTask(array $parameters) {
    $personAlias = $parameters['person_alias'] ?? 'unknown';
    $name = $parameters['name'] ?? 'unnamed';
    // In a real app, you would perform a database INSERT or UPDATE here.
    return "ACTION: Successfully processed CREATE/UPDATE for entity '{$personAlias}' with name '{$name}'.";
}

/**
 * Placeholder for reading data from the database.
 * @param array $parameters Extracted parameters from the Triage Agent.
 * @return string A confirmation message.
 */
function executeReadTask(array $parameters) {
    $personAlias = $parameters['person_alias'] ?? 'unknown';
    $attribute = $parameters['attribute'] ?? 'info';
    // In a real app, you would perform a database SELECT here.
    return "ACTION: Successfully processed READ for '{$attribute}' on entity '{$personAlias}'.";
}

/**
 * Placeholder for handling a generic chat question.
 * @param string $queryPart The part of the query for the chat.
 * @return string A confirmation message.
 */
function executeChatTask(string $queryPart) {
    // In a real app, you might call Gemini again here without the Triage prompt.
    return "ACTION: Processed generic CHAT for query: '{$queryPart}'";
}

/**
 * Makes the API call to Gemini, with extensive logging for debugging.
 * @param string $prompt The full prompt including instructions and user input.
 * @return array The decoded JSON execution plan from Gemini, or an empty array on failure.
 */
function makeGeminiCall(string $prompt) {
    write_log("--- Starting Gemini Call ---");
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['responseMimeType' => 'application/json']
    ]);
    write_log("Payload Sent: " . $payload);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    
    $raw_response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // --- CRITICAL DEBUGGING SECTION ---
    if ($curl_error) {
        write_log("FATAL: cURL Error: " . $curl_error);
        throw new Exception("Network error calling Gemini API: " . $curl_error);
    }
    
    write_log("Raw Response from Gemini: " . $raw_response);
    
    $result = json_decode($raw_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        write_log("FATAL: Could not decode the main raw response from Gemini. It's not valid JSON.");
        return []; // Return empty on decode failure
    }

    if (isset($result['error'])) {
        write_log("FATAL: Google API returned an error: " . json_encode($result['error']));
        return [];
    }

    $triage_json_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$triage_json_text) {
        write_log("FATAL: Could not find the 'text' part containing the triage plan in Gemini's response.");
        return [];
    }
    
    write_log("Extracted Triage JSON Text: " . $triage_json_text);
    $executionPlan = json_decode($triage_json_text, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        write_log("FATAL: The 'text' part from Gemini was not valid JSON. Model may have failed to follow instructions.");
        return [];
    }

    write_log("Successfully decoded execution plan.");
    return $executionPlan;
}


// --- MAIN API ORCHESTRATION ---

try {
    $userInput = $_POST['query'] ?? null;
    if (!$userInput) {
        throw new Exception("No query provided.");
    }
    
    write_log("User Input Received: " . $userInput);
    
    // 1. Triage Stage: Get the structured execution plan from Gemini.
    $triagePrompt = file_get_contents('gemini_triage_prompt.txt');
    $fullPrompt = $triagePrompt . "\n\nUser Input: \"{$userInput}\"";
    
    $executionPlan = makeGeminiCall($fullPrompt);

    if (empty($executionPlan) || !isset($executionPlan['tasks'])) {
        write_log("ERROR: Final execution plan was empty or invalid after processing.");
        throw new Exception("Failed to get a valid execution plan from the Triage Agent.");
    }

    // 2. Execution Stage: Loop through the plan and execute each task.
    $results = [];
    foreach ($executionPlan['tasks'] as $task) {
        $taskResult = null;
        switch ($task['intent']) {
            case 'CREATE':
            case 'UPDATE':
                $taskResult = executeCreateTask($task['parameters']);
                break;
            case 'READ':
                $taskResult = executeReadTask($task['parameters']);
                break;
            case 'CHAT':
                $taskResult = executeChatTask($task['original_query_part']);
                break;
            default:
                $taskResult = "ACTION: No handler defined for intent '{$task['intent']}'.";
                break;
        }
        $results[] = [
            'task_id' => $task['task_id'],
            'result' => $taskResult
        ];
    }
    
    // 3. Response Stage: Send a structured response back to the frontend.
    $finalResponse = [
        'received_query' => $userInput,
        'triage_plan' => $executionPlan,
        'execution_results' => $results
    ];

    echo json_encode($finalResponse, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Gracefully catch any exception and return a clean JSON error.
    write_log("CATCH BLOCK ERROR: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
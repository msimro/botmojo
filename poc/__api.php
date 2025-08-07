<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'src/GeminiAgent.php';
require_once 'src/DataRetriever.php';

$userQuery = $_POST['query'] ?? '';

if (empty($userQuery)) {
    echo json_encode(['error' => 'No query provided.']);
    exit;
}

try {
    $agent = new GeminiAgent(GEMINI_API_KEY);
    $retriever = new DataRetriever();

    // === Stage 1: Entity Extraction ===
    $extractionPrompt = "From the following user query, extract the full name of the person they are asking about. Respond with only the name and nothing else. If no person is mentioned, respond with 'NONE'. Query: \"{$userQuery}\"";
    $entityName = trim($agent->generateText($extractionPrompt));

    if ($entityName === 'NONE' || empty($entityName)) {
         echo json_encode(['answer' => "I'm not sure who you're asking about. Please be more specific."]);
         exit;
    }

    // === Stage 2: Retrieval (RAG) ===
    $contextBlob = $retriever->getContextForPerson($entityName);

    // === Stage 3: Synthesis ===
    $synthesisPrompt = "You are a helpful personal assistant. Based *only* on the following context data, answer the user's original query in a friendly, conversational tone. Do not invent any information not present in the context.\n\nCONTEXT:\n---\n{$contextBlob}\n---\n\nORIGINAL QUERY: {$userQuery}\n\nANSWER:";
    $finalAnswer = $agent->generateText($synthesisPrompt);

    echo json_encode(['answer' => $finalAnswer]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
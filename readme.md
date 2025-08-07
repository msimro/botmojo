# Project Brief: AI Personal Assistant "Core v1"

## 1. Project Overview & Core Philosophy

This project is the foundational core of an intelligent, modular, multi-agent personal assistant. It is designed not as a simple chatbot, but as a sophisticated system for understanding and managing a user's life data.

The core architectural philosophy is **Triage-First, Agent-Based Component Assembly**.

- **Triage-First:** Every user input is first sent to a specialized AI **Triage Agent**. Its sole purpose is to analyze the user's intent and create a structured JSON execution plan. It does not act directly.
- **Agent-Based:** The execution plan assigns each task to a specific, specialized **Agent** (e.g., `MemoryAgent`, `PlannerAgent`). This keeps logic modular and clean.
- **Component Assembly:** We use a unified data model centered around **Entities** (e.g., a person, an event). Each agent is responsible for creating its own data **Component** (e.g., `finance_component`), which are then assembled into a single, context-rich JSON object and saved to a unified `entities` table in the database.

## 2. Technical Stack

- **Backend:** PHP
- **Database:** MySQL
- **AI Model:** Google Gemini (specifically `gemini-1.5-flash` for its speed and function-calling capabilities)
- **Memory/Cache:** A simple file-based cache for this POC.

## 3. Directory Structure

```
/assistant_core_v1/
├── agents/
│   ├── MemoryAgent.php
│   ├── PlannerAgent.php
│   ├── FinanceAgent.php
│   └── GeneralistAgent.php
├── tools/
│   ├── DatabaseTool.php
│   ├── PromptBuilder.php
│   └── ConversationCache.php
├── prompts/
│   ├── base/
│   │   └── triage_agent_base.txt
│   ├── components/
│   │   └── agent_definitions.txt
│   └── formats/
│       └── triage_json_output.txt
├── cache/
│   └── (conversation cache files will be created here)
├── config.php
├── database.sql
├── api.php
└── index.php
```

## 4. Database Schema (`database.sql`)

This schema uses a unified `entities` table for maximum flexibility, with a separate table for defining relationships between them.

```sql
CREATE DATABASE `assistant_core_v1`;
USE `assistant_core_v1`;

-- The single, unified table for all "things" in your life.
CREATE TABLE `entities` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL, -- For future multi-user support
  `type` VARCHAR(50) NOT NULL, -- e.g., 'person', 'event', 'task'
  `primary_name` VARCHAR(255),  -- A searchable, top-level name
  `data` JSON NOT NULL,          -- This holds all the agent-generated components
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_primary_name` (`primary_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The "verbs" that connect entities.
CREATE TABLE `relationships` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL,
  `source_entity_id` VARCHAR(36) NOT NULL,
  `target_entity_id` VARCHAR(36) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  FOREIGN KEY (`source_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 5. Prompt Engine: Dynamic Assembly

We use a `PromptBuilder` tool to dynamically construct prompts from smaller, reusable component files. This allows for flexibility and easy maintenance.

### `prompts/base/triage_agent_base.txt`
```text
You are a Triage Agent for a highly intelligent personal assistant. Your primary goal is to analyze the user's raw text input and create a structured JSON plan. After creating the plan, you must ALSO provide a natural language response to the user, as if you have already completed the tasks. Use the conversation history for context.

### CURRENT CONTEXT (Recent Conversation)
{{conversation_history}}

### AVAILABLE AGENTS & CAPABILITIES
{{agent_definitions}}

### JSON OUTPUT STRUCTURE
{{output_format}}

Now, analyze the following NEW user input.
```

### `prompts/components/agent_definitions.txt`
```text
1.  **MemoryAgent**: Manages the core knowledge graph about people, places, and objects.
2.  **PlannerAgent**: Manages time, schedules, tasks, and goals.
3.  **FinanceAgent**: Manages financial data like income and expenses.
4.  **GeneralistAgent**: A fallback for generic chat and simple questions.
```

### `prompts/formats/triage_json_output.txt`
```text
Respond with ONLY a valid JSON object following this exact structure.

{
  "triage_summary": "A brief, one-sentence summary of the user's overall goal.",
  "suggested_response": "A brief, friendly, and natural confirmation message for the user, written in the first person as the assistant.",
  "target_entity": {
    "alias": "A temporary name for the entity being discussed, e.g., 'the dinner event'.",
    "type": "The entity type, e.g., 'event', 'person', 'task'."
  },
  "component_tasks": [
    {
      "task_id": 1,
      "original_query_part": "The part of the query this task corresponds to.",
      "target_agent": "The agent responsible for creating the component (e.g., 'FinanceAgent').",
      "component_name": "The name of the component this agent will create (e.g., 'finance_component').",
      "component_data": { "key": "value" }
    }
  ]
}
```

## 6. Core Classes: Agents, Tools, and Orchestrator

### Tools (`tools/`)
Tools are single-purpose classes that perform a specific function and are injected into agents.

#### `tools/DatabaseTool.php`
```php
<?php
class DatabaseTool {
    private $db;
    public function __construct() {
        // In a real app, inject credentials. For POC, read from config.
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) { die("DB Connection Failed: " . $this->db->connect_error); }
        $this->db->set_charset("utf8mb4");
    }
    public function saveNewEntity(string $id, string $userId, string $type, string $name, string $jsonData) {
        $stmt = $this->db->prepare("INSERT INTO entities (id, user_id, type, primary_name, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $userId, $type, $name, $jsonData);
        return $stmt->execute();
    }
    // Add other methods like findEntity, updateEntity as needed.
}
```

#### `tools/PromptBuilder.php`
```php
<?php
class PromptBuilder {
    private string $basePath;
    public function __construct(string $promptsDirectory) { $this->basePath = rtrim($promptsDirectory, '/'); }
    public function build(string $baseTemplateName, array $components): string {
        $baseTemplate = file_get_contents($this->basePath . '/' . $baseTemplateName);
        foreach ($components as $placeholder => $componentFile) {
            $componentContent = file_get_contents($this->basePath . '/' . $componentFile);
            $baseTemplate = str_replace("{{{$placeholder}}}", $componentContent, $baseTemplate);
        }
        return $baseTemplate;
    }
}
```

#### `tools/ConversationCache.php`
```php
<?php
class ConversationCache {
    private string $cacheDir;
    private int $historyLimit = 5; // Keep last 5 turns
    public function __construct(string $cacheDir) { $this->cacheDir = $cacheDir; if (!is_dir($cacheDir)) { mkdir($cacheDir, 0777, true); } }
    public function getHistory(string $convoId): string {
        $file = $this->cacheDir . '/' . $convoId . '.json';
        if (!file_exists($file)) return "No history.";
        $history = json_decode(file_get_contents($file), true) ?: [];
        $historyText = "";
        foreach ($history as $turn) { $historyText .= "User: {$turn['user']}\nAssistant: {$turn['assistant']}\n"; }
        return $historyText;
    }
    public function appendToHistory(string $convoId, string $userQuery, string $aiResponse) {
        $file = $this->cacheDir . '/' . $convoId . '.json';
        $history = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $history[] = ['user' => $userQuery, 'assistant' => $aiResponse];
        if (count($history) > $this->historyLimit) { $history = array_slice($history, -$this->historyLimit); }
        file_put_contents($file, json_encode($history));
    }
}
```

### Agents (`agents/`)
Agents are simple classes that receive a task from the orchestrator and use their injected tools to perform it.

#### `agents/FinanceAgent.php`
```php
<?php
class FinanceAgent {
    // This agent doesn't need any tools for this simple component creation
    public function createComponent(array $data): array {
        return [
            'amount' => (float)($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? 'USD',
            'category' => $data['category'] ?? 'Uncategorized'
        ];
    }
}
// Create MemoryAgent.php and PlannerAgent.php with similar `createComponent` methods.
```

### Orchestrator (`api.php`)
This file is the main entry point. It coordinates the tools and agents to process a user request.

```php
<?php
// Full api.php code from previous answers would go here.
// Key logic steps:
// 1. Autoload classes from agents/ and tools/
// 2. Instantiate all necessary tools (PromptBuilder, DatabaseTool, ConversationCache)
// 3. Get user input and conversation ID
// 4. Use ConversationCache to get history
// 5. Use PromptBuilder to assemble the full prompt
// 6. Call Gemini API to get the execution plan
// 7. Extract the user-facing 'suggested_response'
// 8. Loop through 'component_tasks' in the plan:
//    a. Instantiate the 'target_agent'
//    b. Call agent's 'createComponent' method
//    c. Assemble all components into a final JSON data object
// 9. Use DatabaseTool to save the new entity with the assembled data
// 10. Use ConversationCache to save the current turn
// 11. Send the final JSON response (including 'suggested_response') to the frontend.
```

This brief should give your IDE copilot all the necessary context to understand our project's architecture, data structures, and overall flow, enabling it to provide relevant and accurate code suggestions.
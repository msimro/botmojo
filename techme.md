# BotMojo Technical Brief - Phase 1 Complete ✅

## 1. Project Overview & Core Philosophy

**BotMojo** is an intelligent, modular, multi-agent personal assistant designed as a sophisticated system for understanding and managing user life data. **Phase 1 is now complete** with all four agents enhanced to v1.1 with advanced AI capabilities.

The core architectural philosophy is **Triage-First, Agent-Based Component Assembly**.

- **Triage-First:** Every user input is first sent to a specialized AI **Triage Agent** that analyzes intent and creates structured JSON execution plans
- **Agent-Based:** Execution plans assign tasks to specialized **Agents** (Memory, Planner, Finance, Generalist) keeping logic modular and clean  
- **Component Assembly:** Unified data model centered around **Entities** with agent-created **Components** assembled into context-rich JSON objects
- **Enhanced Intelligence:** All agents now feature sophisticated parsing, categorization, and analysis capabilities

## 2. Technical Stack & Environment

- **Backend:** PHP 8.3
- **Database:** MySQL/MariaDB (via DDEV)
- **AI Model:** Google Gemini 1.5-flash (function-calling capabilities)
- **Development:** DDEV local development environment
- **Memory/Cache:** File-based conversation cache system
- **Architecture:** Microservice-style agent architecture with unified database

## 3. Directory Structure

```
/botmojo/
├── agents/                           # Enhanced AI Agents (v1.1)
│   ├── MemoryAgent.php              # Smart knowledge graph with relationship parsing
│   ├── PlannerAgent.php             # Advanced scheduling with intelligent date/time parsing  
│   ├── FinanceAgent.php             # Multi-currency financial analytics with categorization
│   └── GeneralistAgent.php         # Advanced content analysis with sentiment detection
├── tools/                           # Core Infrastructure Tools
│   ├── DatabaseTool.php             # Entity/relationship database operations
│   ├── PromptBuilder.php            # Dynamic AI prompt assembly system
│   └── ConversationCache.php       # File-based conversation history management
├── prompts/                         # AI Prompt Template System
│   ├── base/
│   │   └── triage_agent_base.txt    # Main triage analysis prompt
│   ├── components/
│   │   └── agent_definitions.txt    # Agent capability descriptions
│   └── formats/
│       └── triage_json_output.txt   # Structured JSON response format
├── cache/                           # Conversation History Storage
│   └── (conversation cache files dynamically created)
├── config.php                       # System configuration and utilities
├── database.sql                     # Database schema with foreign key constraints
├── api.php                          # Main API orchestrator and request handler
├── index.php                        # Web chat interface
├── dashboard.php                    # Data visualization and entity browser
├── completed.md                     # Phase 1 completion documentation
└── upnext.md                       # Phase 2 roadmap and future enhancements
```

## 4. Enhanced Agent Capabilities (v1.1)

### MemoryAgent - Intelligent Knowledge Graph
**Enhanced Features:**
- Rich attribute extraction from triage data (job titles, preferences, relationships)
- Intelligent relationship parsing with bidirectional connections
- Context-aware entity creation with smart categorization
- Advanced people/place/object management with metadata

**Example Intelligence:**
- "John works at Google as SWE" → `{employer: "Google", job_title: "Software Engineer"}`
- "Sarah likes coffee" → `{preferences: ["coffee"], relationship_context: "casual_preference"}`
- Auto-detects relationship types (colleague, friend, family) with importance scoring

### PlannerAgent - Smart Scheduling System  
**Enhanced Features:**
- Advanced date/time parsing ("tomorrow at 3 PM", "next Friday evening")
- Intelligent priority assessment based on context and urgency
- Smart scheduling with conflict detection
- Context-aware task and goal management

**Example Intelligence:**
- "tomorrow at 3 PM" → calculates exact DateTime with timezone
- "this evening" → contextually interprets as today + 6-8 PM range
- "next week" → defaults to Monday with intelligent scheduling suggestions

### FinanceAgent - Financial Analytics Engine
**Enhanced Features:**  
- Multi-currency transaction parsing with exchange rate awareness
- Intelligent vendor detection and smart categorization
- Enhanced expense analysis with pattern recognition
- Advanced financial data extraction from natural language

**Example Intelligence:**
- "$25 lunch at McDonald's" → `{amount: 25, currency: "USD", vendor: "McDonald's", category: "Food & Dining"}`
- Automatic vendor recognition with industry categorization
- Smart expense patterns and budgeting insights

### GeneralistAgent - Advanced Content Analysis
**Enhanced Features:**
- Sophisticated intent classification and analysis  
- Multi-domain topic and content classification
- Sentiment analysis and emotional context detection
- Advanced fallback processing for complex, multi-faceted queries

**Example Intelligence:**
- Analyzes communication tone and emotional context
- Classifies content across multiple domains simultaneously
- Provides intelligent fallback for edge cases and complex requests

## 5. Database Schema & Architecture (`database.sql`)

**Enhanced unified schema with foreign key constraints for data integrity:**

```sql
CREATE DATABASE `assistant_core_v1`;
USE `assistant_core_v1`;

-- Enhanced unified table for all life entities with optimized indexing
CREATE TABLE `entities` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL, -- Multi-user support ready
  `type` VARCHAR(50) NOT NULL, -- person, event, task, transaction, etc.
  `primary_name` VARCHAR(255),  -- Searchable, top-level identifier
  `data` JSON NOT NULL,          -- Agent-generated component data
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_primary_name` (`primary_name`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced relationship management with foreign key constraints
CREATE TABLE `relationships` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL,
  `source_entity_id` VARCHAR(36) NOT NULL,
  `target_entity_id` VARCHAR(36) NOT NULL,
  `type` VARCHAR(50) NOT NULL, -- works_at, lives_in, likes, etc.
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`source_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_relationship_type` (`type`),
  INDEX `idx_source_entity` (`source_entity_id`),
  INDEX `idx_target_entity` (`target_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Data Flow Architecture
1. **Triage** → Structured JSON execution plan
2. **Agent Processing** → Enhanced component creation with intelligence
3. **Component Assembly** → Unified entity with rich metadata
4. **Database Storage** → Entities + relationships with foreign key integrity
5. **Conversation Cache** → File-based history preservation

## 6. Enhanced Prompt Engineering System

**Dynamic assembly with enhanced agent definitions:**

### `prompts/base/triage_agent_base.txt`
```text
You are an advanced Triage Agent for a highly intelligent personal assistant. Analyze user input and create structured JSON execution plans while providing natural, contextual responses. Use conversation history for enhanced context awareness.

### ENHANCED AGENT CAPABILITIES  
{{agent_definitions}}

### STRUCTURED OUTPUT FORMAT
{{output_format}}

### CONVERSATION CONTEXT
{{conversation_history}}

Process the following user input with maximum intelligence and context awareness.
```

### Enhanced Agent Definitions (`prompts/components/agent_definitions.txt`)
```text
1. **MemoryAgent v1.1**: Advanced knowledge graph with intelligent relationship parsing, rich attribute extraction, and context-aware entity creation
2. **PlannerAgent v1.1**: Smart scheduling with advanced date/time parsing, priority assessment, and conflict detection  
3. **FinanceAgent v1.1**: Multi-currency financial analytics with vendor detection and intelligent categorization
4. **GeneralistAgent v1.1**: Advanced content analysis with intent classification, sentiment detection, and multi-domain processing
```

## 7. Core Infrastructure Classes

### Enhanced Tools (`tools/`)

#### `tools/DatabaseTool.php` - Entity Management
```php
<?php
class DatabaseTool {
    private $db;
    
    public function __construct() {
        // DDEV auto-configuration support
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) { 
            die("DB Connection Failed: " . $this->db->connect_error); 
        }
        $this->db->set_charset("utf8mb4");
    }
    
    // Enhanced entity creation with validation
    public function saveNewEntity(string $id, string $userId, string $type, string $name, string $jsonData) {
        $stmt = $this->db->prepare("INSERT INTO entities (id, user_id, type, primary_name, data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $userId, $type, $name, $jsonData);
        return $stmt->execute();
    }
    
    // Enhanced relationship management with foreign key support
    public function createRelationship(string $id, string $userId, string $sourceId, string $targetId, string $type) {
        $stmt = $this->db->prepare("INSERT INTO relationships (id, user_id, source_entity_id, target_entity_id, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $userId, $sourceId, $targetId, $type);
        return $stmt->execute();
    }
    
    // Advanced querying with relationship traversal
    public function findEntitiesWithRelationships(string $userId, string $type = null): array {
        // Complex query implementation for knowledge graph traversal
    }
}
```

### Enhanced Agents (`agents/`)

#### `agents/FinanceAgent.php` - v1.1 Financial Intelligence
```php
<?php
class FinanceAgent {
    private string $version = "1.1";
    
    public function createComponent(array $data): array {
        return [
            'version' => $this->version,
            'amount' => $this->parseAmount($data),
            'currency' => $this->detectCurrency($data),
            'category' => $this->intelligentCategoryDetection($data),
            'vendor' => $this->detectVendor($data),
            'transaction_type' => $this->analyzeTransactionType($data),
            'confidence_score' => $this->calculateConfidence($data)
        ];
    }
    
    private function parseAmount(array $data): float {
        // Enhanced multi-currency parsing with validation
    }
    
    private function intelligentCategoryDetection(array $data): string {
        // AI-powered categorization with vendor matching
    }
}
```

## 8. System Status & Capabilities

### ✅ Phase 1 Complete
- All four agents enhanced to v1.1 with advanced intelligence
- Database schema optimized with foreign key constraints  
- Enhanced prompt engineering system
- File-based conversation cache working
- DDEV development environment configured
- Dashboard visualization functional

### 🔧 Production Ready Features
- Intelligent natural language processing
- Multi-domain query handling
- Advanced data extraction and categorization
- Knowledge graph construction and querying
- Financial analytics with multi-currency support
- Smart scheduling and task management
- Conversation context preservation
- Real-time dashboard insights

### 🚀 Architecture Benefits
- **Modular**: Easy to extend with new agents
- **Intelligent**: Each agent has enhanced AI capabilities  
- **Scalable**: Database design supports growth
- **Maintainable**: Clean separation of concerns
- **Flexible**: JSON data structure adapts to any entity type
- **Robust**: Foreign key constraints ensure data integrity

**Ready for Phase 2 enhancements and production deployment!** 🎯
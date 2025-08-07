# AI Personal Assistant - Core v1

## Setup Instructions

### 1. Database Setup
1. Create a MySQL database named `assistant_core_v1`
2. Import the database schema:
   ```bash
   mysql -u your_username -p assistant_core_v1 < database.sql
   ```

### 2. Configuration
1. Edit `config.php` and update the following:
   - Database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
   - Google Gemini API key (GEMINI_API_KEY)

### 3. Web Server Setup
1. Place the project folder in your web server directory
2. Ensure PHP has the following extensions enabled:
   - mysqli
   - curl
   - json
3. Make sure the `cache/` directory is writable by the web server

### 4. Getting Your Gemini API Key
1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Create a new API key
3. Copy the key and paste it in `config.php`

### 5. Testing
1. Open `index.php` in your web browser
2. Try some example queries:
   - "I spent $25 on lunch at McDonald's today"
   - "Schedule a meeting with John tomorrow at 3 PM"
   - "Remember that Sarah likes coffee"

## Project Structure

```
/assistant_core_v1/
├── agents/                    # Agent classes for different domains
│   ├── MemoryAgent.php       # Manages people, places, objects
│   ├── PlannerAgent.php      # Manages time, schedules, tasks
│   ├── FinanceAgent.php      # Manages financial data
│   └── GeneralistAgent.php  # Fallback for general queries
├── tools/                    # Tool classes for common functionality
│   ├── DatabaseTool.php      # Database operations
│   ├── PromptBuilder.php     # Dynamic prompt assembly
│   └── ConversationCache.php # Conversation history management
├── prompts/                  # Prompt templates
│   ├── base/
│   │   └── triage_agent_base.txt
│   ├── components/
│   │   └── agent_definitions.txt
│   └── formats/
│       └── triage_json_output.txt
├── cache/                    # Conversation cache files
├── config.php               # Configuration and utilities
├── database.sql             # Database schema
├── api.php                  # Main API orchestrator
└── index.php               # Web interface
```

## How It Works

1. **Triage-First Architecture**: Every user input goes to a Triage Agent that analyzes intent and creates a structured execution plan
2. **Agent-Based Processing**: The execution plan assigns tasks to specialized agents (Memory, Planner, Finance, Generalist)
3. **Component Assembly**: Each agent creates data components that are assembled into a unified entity
4. **Unified Storage**: All entities are stored in a single `entities` table with JSON data structure

## Architecture Philosophy

- **Modular**: Each agent handles a specific domain
- **Flexible**: Easy to add new agents and capabilities
- **Unified**: Single data model for all entities
- **Intelligent**: AI-driven triage and component creation

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check your database credentials in `config.php`
   - Ensure MySQL is running
   - Verify the database exists

2. **Gemini API Error**
   - Check your API key in `config.php`
   - Ensure you have API quota available
   - Check your internet connection

3. **Cache Directory Issues**
   - Ensure the `cache/` directory is writable
   - Check file permissions

4. **JSON Parse Errors**
   - This usually indicates an issue with the AI model response
   - Check the debug output when DEBUG_MODE is enabled
   - Verify your prompt templates are properly formatted

### Debug Mode

Set `DEBUG_MODE` to `true` in `config.php` to:
- See detailed error messages
- View the triage data in API responses
- Enable PHP error reporting

## Next Steps

This is the foundational core (v1). Future enhancements could include:
- Multi-user support
- Advanced relationship mapping
- Integration with external APIs
- Real-time notifications
- Mobile app interface
- Advanced analytics and insights

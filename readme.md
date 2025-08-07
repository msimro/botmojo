# BotMojo - AI Personal Assistant ✅ Phase 1 Complete

**Intelligent, modular, multi-agent personal assistant with enhanced AI capabilities**

## 🚀 Quick Start (DDEV)

### 1. DDEV Setup
```bash
ddev start
ddev import-db --src=database.sql
```

### 2. Configuration
1. Edit `config.php` and update:
   - Google Gemini API key (GEMINI_API_KEY)
   - Database is auto-configured via DDEV

### 3. Getting Your Gemini API Key
1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Create a new API key
3. Copy the key and paste it in `config.php`

### 4. Testing
1. Open `index.php` in your web browser
2. Try example queries:
   - "I spent $25 on lunch at McDonald's today"
   - "Schedule a meeting with John tomorrow at 3 PM"
   - "Remember that Sarah likes coffee"

## ✨ What's New in Phase 1

### 🤖 Enhanced Agents (All v1.1)
- **MemoryAgent**: Intelligent knowledge graph with rich relationship parsing
- **PlannerAgent**: Advanced date/time parsing and smart scheduling
- **FinanceAgent**: Multi-currency support with intelligent categorization
- **GeneralistAgent**: Advanced content analysis with sentiment detection

### 🏗️ Architecture Highlights
- **Triage-First**: AI-driven intent analysis and execution planning
- **Agent-Based**: Specialized processing with modular design
- **Unified Database**: Flexible entities/relationships schema
- **File-Based Cache**: Conversation history preservation

## 📁 Project Structure

```
/botmojo/
├── agents/                    # Enhanced AI agents (v1.1)
│   ├── MemoryAgent.php       # Smart knowledge graph with relationships
│   ├── PlannerAgent.php      # Advanced scheduling and task management
│   ├── FinanceAgent.php      # Multi-currency financial analytics
│   └── GeneralistAgent.php  # Intelligent content analysis
├── tools/                    # Core utility classes
│   ├── DatabaseTool.php      # Database operations and entity management
│   ├── PromptBuilder.php     # Dynamic AI prompt assembly
│   └── ConversationCache.php # File-based conversation history
├── prompts/                  # AI prompt templates
│   ├── base/
│   │   └── triage_agent_base.txt      # Main triage prompt template
│   ├── components/
│   │   └── agent_definitions.txt      # Agent capability descriptions
│   └── formats/
│       └── triage_json_output.txt     # JSON response format spec
├── cache/                    # Conversation cache files
├── config.php               # Configuration and utilities
├── database.sql             # Database schema
├── api.php                  # Main API orchestrator
├── index.php               # Chat interface
├── dashboard.php           # Data visualization dashboard
├── completed.md            # Phase 1 completion summary
└── upnext.md              # Phase 2 roadmap
```

## 🎯 System Capabilities

### Intelligent Processing
- Natural language understanding and intent analysis
- Smart data extraction and categorization
- Context-aware entity creation and relationship mapping
- Multi-domain query handling with specialized agents

### Financial Intelligence
- Multi-currency transaction parsing
- Intelligent vendor detection and categorization
- Smart expense analysis and tracking
- Enhanced financial data extraction

### Memory & Knowledge
- Rich attribute extraction from conversations
- Intelligent relationship parsing and storage
- Context-aware entity creation
- Bidirectional relationship handling

### Planning & Scheduling
- Advanced date/time parsing ("tomorrow at 3", "next Friday")
- Intelligent priority assessment
- Context-aware scheduling
- Task and goal management

## How It Works
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

## 🔧 Troubleshooting

### DDEV Issues
```bash
ddev restart        # Restart DDEV containers
ddev describe       # Check DDEV status
ddev logs           # View container logs
```

### Common Issues

1. **Database Connection Failed**
   - Run `ddev restart` to restart containers
   - Check `ddev describe` for database credentials
   - Ensure DDEV is running with `ddev start`

2. **Gemini API Error**
   - Check your API key in `config.php`
   - Ensure you have API quota available
   - Check your internet connection

3. **Cache Directory Issues**
   - DDEV handles permissions automatically
   - Try `ddev restart` if cache issues persist

4. **Agent Processing Errors**
   - Check agent version (should be v1.1)
   - Enable `DEBUG_MODE` in `config.php`
   - View detailed error output in browser console

### Debug Mode

Set `DEBUG_MODE` to `true` in `config.php` to:
- See detailed error messages and triage data
- View agent processing steps
- Enable comprehensive PHP error reporting
- Monitor AI model responses

## 📊 Development Tools

- **Dashboard**: View entities and relationships at `/dashboard.php`
- **API Testing**: Direct API access at `/api.php`
- **Cache Management**: Files stored in `/cache/` directory
- **Database**: Access via `ddev mysql` command

## 🚀 What's Next

Phase 1 is complete with all four agents enhanced and intelligent. See `upnext.md` for Phase 2 roadmap including:
- Weather integration and location awareness
- Advanced analytics and pattern recognition
- Mobile API and webhook integrations
- Machine learning for better categorization

**Ready for production use!** 🎯

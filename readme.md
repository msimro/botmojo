# BotMojo - AI Personal Assistant ✅ Phase 2 Complete

**Intelligent, modular, multi-agent personal assistant with enhanced AI capabilities and proper OOP architecture**

> 💡 **For Developers**: Check out [.github/copilot-instructions.md](.github/copilot-instructions.md) for project architecture and development guidelines.

## 🚀 Quick Start (DDEV)

### 1. DDEV Setup
```bash
ddev start
ddev import-db --src=database.sql
```

### 2. Configuration
1. Copy `.env.example` to `.env` and add:
   - Google Gemini API key (API_KEY)
   - Database is auto-configured via DDEV

### 3. Getting Your Gemini API Key
1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Create a new API key
3. Copy the key and paste it in `.env`

### 4. Testing
1. Open `index.php` in your web browser
2. Try example queries:
   - "I spent $25 on lunch at McDonald's today"
   - "Schedule a meeting with John tomorrow at 3 PM"
   - "Remember that Sarah likes coffee"

## ✨ What's New in Phase 2

### 🏗️ Architecture Improvements
- **Modern OOP Structure**: Fully refactored with namespaces and PSR standards
- **Dependency Injection**: ServiceContainer for managing component dependencies
- **Interface-Based Design**: Clear contracts for Agents and Tools
- **Strict Typing**: Type safety with PHP 8 strict typing enforcement
- **Environment Variables**: Configuration via .env file for improved security

### 🤖 Enhanced Agents
- **MemoryAgent**: Intelligent knowledge graph with rich relationship parsing
- **PlannerAgent**: Advanced date/time parsing and smart scheduling
- **FinanceAgent**: Multi-currency support with intelligent categorization
- **GeneralistAgent**: Advanced content analysis with sentiment detection

## 📁 Project Structure

```
/botmojo/
├── src/                      # Main source code with namespaces
│   ├── Core/                 # Core system components
│   │   ├── AbstractAgent.php # Base agent functionality
│   │   ├── AbstractTool.php  # Base tool functionality
│   │   ├── AgentInterface.php # Agent contract
│   │   ├── ToolInterface.php # Tool contract
│   │   ├── Orchestrator.php  # Main request coordinator
│   │   └── ServiceContainer.php # Dependency injection container
│   ├── Agents/               # Enhanced AI agents
│   │   ├── MemoryAgent.php   # Smart knowledge graph with relationships
│   │   └── [Other agents]    # Additional specialized agents
│   ├── Tools/                # Utility tools
│   │   ├── DatabaseTool.php  # Database operations and entity management
│   │   ├── GeminiTool.php    # Google Gemini AI API integration
│   │   ├── HistoryTool.php   # Conversation history management
│   │   └── PromptBuilder.php # Dynamic AI prompt assembly
│   └── Exceptions/           # Custom exception handling
│       └── BotMojoException.php # Domain-specific exceptions
├── prompts/                  # AI prompt templates
│   ├── base/
│   │   └── triage_agent_base.txt      # Main triage prompt template
│   ├── components/
│   │   └── agent_definitions.txt      # Agent capability descriptions
│   └── formats/
│       └── triage_json_output.txt     # JSON response format spec
├── cache/                    # Conversation cache files
├── .env.example             # Environment variable template
├── .env                     # Active environment configuration (ignored by git)
├── config.php               # Configuration and utilities
├── database.sql             # Database schema
├── api.php                  # Main API orchestrator
├── index.php                # Chat interface
└── dashboard.php            # Data visualization dashboard
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

## 🧠 Technical Implementation

### Dependency Injection
- `ServiceContainer` manages all component dependencies
- Lazy-loading of services for improved performance
- Clean separation of concerns with interface-based design

### OOP Architecture
- `AbstractAgent` provides shared functionality for all agents
- `AbstractTool` provides common functionality for all tools
- Interfaces define clear contracts for components
- PSR-compliant namespacing structure

### API Integration
- Google Gemini AI with model fallback strategy
- Structured error handling with context preservation
- JSON-based communication with clear data structures

### Configuration Management
- Environment-based configuration with .env file
- Separation of settings from codebase
- Secure credential handling

## 📊 Future Development

### Phase 3 Goals
- Implement additional specialized agents
- Add comprehensive unit and integration testing
- Enhance web interface with modern framework
- Implement caching and performance optimizations
- Add OAuth-based authentication and multi-user support

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

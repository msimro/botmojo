# BotMojo - AI Personal Assistant

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

BotMojo is a sophisticated, triage-first AI personal assistant built with PHP. It processes natural language queries through specialized agents that handle domain-specific tasks, creating a comprehensive personal assistance system.

## 🚀 Features

- **Triage-First Architecture**: AI analyzes every request before processing
- **Agent-Based System**: Specialized agents for different domains (memory, planning, finance, health, etc.)
- **Component Assembly**: Multiple agents contribute to unified entity creation
- **Modern PHP**: Built with PHP 8+, strict typing, and PSR standards
- **Professional Logging**: Structured logging with Monolog
- **Flexible Storage**: JSON-based entity system with MySQL backend
- **Environment-Based Configuration**: Secure configuration with .env files

## 📋 Requirements

- PHP 8.0 or higher
- MySQL/MariaDB
- Composer
- Google Gemini API key

### Optional Development Tools
- DDEV for local development environment

## 🛠 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/msimro/botmojo.git
cd botmojo
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file with your configuration:
```env
# Google Gemini API Key - Required for AI functionality
API_KEY=your_gemini_api_key_here

# Database Configuration
DB_HOST=localhost
DB_NAME=botmojo
DB_USER=your_db_user
DB_PASS=your_db_password

# Debug Mode - Set to false for production
DEBUG_MODE=false

# Default Model
DEFAULT_MODEL=gemini-2.5-flash-lite
```

### 4. Database Setup
Import the database schema:
```bash
mysql -u your_user -p your_database < docs/database.sql
```

### 5. Directory Permissions
Ensure logs and cache directories are writable:
```bash
mkdir -p logs cache
chmod 755 logs cache
```

## 🏗 Architecture

### Core Components

- **`api.php`**: Main API orchestrator and entry point
- **`src/Core/`**: Core framework classes (Orchestrator, ServiceContainer, etc.)
- **`src/Agents/`**: Specialized AI agents for different domains
- **`src/Tools/`**: Utility classes and external integrations
- **`src/Exceptions/`**: Custom exception hierarchy
- **`prompts/`**: AI prompt templates and components

### Agent Ecosystem

- **MemoryAgent**: Knowledge graph and relationship management
- **PlannerAgent**: Scheduling, tasks, and goal management
- **FinanceAgent**: Financial tracking and expense analysis
- **HealthAgent**: Wellness, fitness, and medical data
- **SpiritualAgent**: Meditation, mindfulness, and spiritual practices
- **SocialAgent**: Social events and communication patterns
- **RelationshipAgent**: Entity relationship analysis and creation
- **LearningAgent**: Educational content and skill development
- **GeneralistAgent**: Fallback for general queries and conversation

## 🔧 Usage

### Basic API Request
```bash
curl -X POST http://your-domain.com/api.php \
  -H "Content-Type: application/json" \
  -d '{"query": "Remember that I have a meeting with John tomorrow at 3 PM"}'
```

### Frontend Interface
Access the web interface by visiting `index.php` in your browser for a chat-based interaction.

### Dashboard
View stored entities and relationships at `dashboard.php`.

## 🔌 API Reference

### POST /api.php

**Request Body:**
```json
{
  "query": "Your natural language query",
  "conversation_id": "optional_conversation_id"
}
```

**Response:**
```json
{
  "status": "success",
  "response": "AI-generated response",
  "entities_created": [],
  "conversation_id": "unique_conversation_id",
  "processing_time": 1.23
}
```

## 🧪 Development

### DDEV Setup (Recommended)
```bash
ddev start
ddev import-db --src=docs/database.sql
```

### Code Quality
The project follows PSR standards and uses strict typing. Key dependencies:

- **vlucas/phpdotenv**: Environment variable management
- **nesbot/carbon**: Advanced date/time handling
- **guzzlehttp/guzzle**: HTTP client for API requests
- **monolog/monolog**: Professional logging

## 📁 Project Structure

```
botmojo/
├── api.php                 # Main API endpoint
├── index.php              # Web interface
├── dashboard.php          # Data visualization
├── config.php             # Configuration loader
├── composer.json          # Dependencies
├── .env.example           # Environment template
├── src/
│   ├── Core/              # Framework core
│   ├── Agents/            # Specialized AI agents
│   ├── Tools/             # Utility classes
│   └── Exceptions/        # Custom exceptions
├── prompts/               # AI prompt templates
├── docs/                  # Documentation
├── logs/                  # Application logs
└── cache/                 # Conversation cache
```

## 🛡 Security

- Environment variables for sensitive configuration
- Input validation and sanitization
- Structured error handling with context
- No hardcoded API keys or credentials
- Secure database interactions

## 📝 Logging

Logs are stored in the `logs/` directory with daily rotation:
- Application events and errors
- API request/response logging
- Agent processing details
- Exception tracking with full context

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR coding standards
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- [Documentation](docs/)
- [API Reference](docs/api.md)
- [Architecture Guide](docs/architecture.md)

## 🆘 Support

For support, please create an issue on GitHub or contact the development team.

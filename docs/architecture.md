# BotMojo Architecture Overview

BotMojo is a sophisticated AI Personal Assistant that uses a triage-first, agent-based architecture for processing natural language queries through specialized agents. This document outlines the complete system architecture and component interactions.

## System Components Overview

### 1. Entry Points & Initial Request

```plaintext
├── index.php (Web Interface)
│   └── Handles web-based chat interface
│       ├── User submits message
│       └── AJAX call to api.php
│
└── api.php (API Endpoint)
    └── Receives POST request with:
        ├── User message
        ├── Session ID
        └── Conversation context
```

### 2. Configuration Management

```plaintext
├── .env
│   └── Environment-specific sensitive values
│       ├── Database credentials
│       ├── API keys (Gemini, etc.)
│       └── Environment settings
│
├── config/
│   ├── default.php (Base configuration)
│   ├── tools/*.php (Tool-specific configs)
│   └── schema.php (Configuration validation)
│
└── tools/*/Config/
    ├── defaults.php (Tool defaults)
    └── schema.json (Tool config schema)
```

### 3. Bootstrap Process

```plaintext
Bootstrap::init()
├── 1. Early Setup
│   ├── Load composer autoloader
│   ├── Load .env
│   └── Initialize error handlers
│
├── 2. Configuration
│   ├── Load base config
│   ├── Load tool configs
│   └── Validate configurations
│
├── 3. Core Services
│   ├── Initialize ServiceContainer
│   ├── Setup Database connection
│   └── Initialize Logger
│
└── 4. System Setup
    ├── Initialize Tools
    └── Prepare Agent System
```

### 4. Payload Construction

```plaintext
RequestPayload
├── User Input
│   ├── Raw message
│   ├── Session information
│   └── Client metadata
│
├── Context
│   ├── Conversation history
│   ├── User preferences
│   └── Active session state
│
└── System State
    ├── Available agents
    ├── Active tools
    └── Resource limits
```

### 5. Processing Pipeline

```plaintext
Orchestrator
├── 1. Triage Phase
│   ├── Analyze input with Gemini
│   ├── Determine intent
│   └── Create execution plan
│
├── 2. Agent Selection
│   ├── Identify primary agent
│   ├── Select supporting agents
│   └── Prepare agent context
│
├── 3. Tool Preparation
│   ├── Load required tools
│   ├── Validate permissions
│   └── Initialize connections
│
├── 4. Processing
│   ├── Primary agent processing
│   ├── Supporting agent input
│   └── Tool operations
│
└── 5. Response Assembly
    ├── Collect agent outputs
    ├── Merge responses
    └── Format final response
```

### 6. Data & State Management

```plaintext
State Management
├── Memory System
│   ├── Short-term context
│   ├── Conversation history
│   └── User preferences
│
├── Database Layer
│   ├── Entity storage
│   ├── Relationship mapping
│   └── Transaction management
│
└── Cache Layer
    ├── Session data
    ├── Computed results
    └── Tool states
```

### 7. Response Processing

```plaintext
Response Processing
├── Format Response
│   ├── Structure data
│   ├── Add metadata
│   └── Include debug info
│
├── Update State
│   ├── Save conversation
│   ├── Update context
│   └── Log interaction
│
└── Client Delivery
    ├── Format for interface
    └── Send response
```

## Complete System Flow

```plaintext
User Input → Entry Point
  │
  ▼
Bootstrap Process
  │
  ▼
Configuration Loading
  │
  ▼
Payload Building
  ├── User input
  ├── Context
  └── System state
  │
  ▼
Orchestration
  ├── Triage
  ├── Agent selection
  ├── Tool preparation
  └── Processing
  │
  ▼
Response Assembly
  ├── Collect results
  ├── Merge responses
  └── Format output
  │
  ▼
State Updates
  ├── Save context
  ├── Update memory
  └── Log interaction
  │
  ▼
Client Response
```

## Key System Features

1. **Modular Architecture**
   - Pluggable agent system
   - Extensible tool framework
   - Configurable components

2. **Security Implementation**
   - Input validation
   - Authentication layers
   - Permission management
   - Data sanitization
   - Secure configuration

3. **Error Handling**
   - Comprehensive error catching
   - Graceful degradation
   - Detailed logging
   - User-friendly messages

4. **State Management**
   - Persistent storage
   - Session handling
   - Context preservation
   - Cache management

5. **Performance Considerations**
   - Efficient data loading
   - Response caching
   - Optimized processing
   - Resource management

## Technical Requirements

- PHP 8.3+
- MySQL/MariaDB
- Google Gemini 1.5
- Composer
- DDEV (development)

## Development Guidelines

1. **Code Organization**
   - Follow PSR standards
   - Use strict typing
   - Implement interfaces
   - Document thoroughly

2. **Security Practices**
   - Validate all inputs
   - Escape outputs
   - Use prepared statements
   - Implement CORS policies

3. **Performance Best Practices**
   - Cache when possible
   - Optimize database queries
   - Minimize API calls
   - Use async where appropriate

4. **Testing Requirements**
   - Unit tests for components
   - Integration tests for flows
   - End-to-end testing
   - Performance benchmarks

## Deployment Considerations

1. **Environment Setup**
   - Configure .env properly
   - Set up logging
   - Configure caching
   - Set security headers

2. **Monitoring**
   - Error tracking
   - Performance metrics
   - Usage statistics
   - Resource monitoring

3. **Maintenance**
   - Regular backups
   - Log rotation
   - Cache clearing
   - Security updates

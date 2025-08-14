# BotMojo OOP Architecture

## Overview

This directory contains the OOP implementation of the BotMojo AI Personal Assistant. The new architecture follows SOLID principles, modern PHP standards, and provides a more maintainable, extensible foundation.

## Key Components

### Core

- **ServiceContainer**: Central dependency injection container that manages service instances
- **Orchestrator**: Coordinates the entire request processing workflow
- **AgentInterface**: Contract for all specialized agents
- **ToolInterface**: Contract for all tools providing functionality
- **AbstractAgent**: Base implementation for specialized agents
- **AbstractTool**: Base implementation for tools

### Agents

Specialized components that handle domain-specific tasks:

- **MemoryAgent**: Knowledge graph and relationship management
- **PlannerAgent**: Scheduling, tasks, and goal management (coming soon)
- **FinanceAgent**: Financial tracking and expense analysis (coming soon)
- **HealthAgent**: Wellness, fitness, and medical data (coming soon)
- **SpiritualAgent**: Meditation, mindfulness, and spiritual practices (coming soon)
- **SocialAgent**: Social events and communication patterns (coming soon)
- **RelationshipAgent**: Entity relationship analysis and creation (coming soon)
- **LearningAgent**: Educational content and skill development (coming soon)
- **GeneralistAgent**: Fallback for general queries and conversation (coming soon)

### Tools

Shared utility classes used by multiple agents:

- **DatabaseTool**: Entity storage and retrieval operations
- **GeminiTool**: AI-powered content generation and analysis
- **HistoryTool**: Conversation context management
- **PromptBuilder**: Template-based prompt assembly

### Exceptions

- **BotMojoException**: Custom exception class with context support

## Architecture Benefits

1. **Dependency Injection**: Services are centrally managed and dynamically composed
2. **Interface Segregation**: Clear contracts between components
3. **Single Responsibility**: Each class has a focused purpose
4. **Open/Closed**: System can be extended without modifying existing code
5. **Testability**: Components can be tested in isolation

## Workflow

1. **Input Phase**: Receive and validate user query
2. **Triage Phase**: AI analyzes intent and creates execution plan
3. **Routing Phase**: Tasks distributed to appropriate specialized agents
4. **Processing Phase**: Agents create domain-specific components
5. **Assembly Phase**: Components combined into unified response
6. **Storage Phase**: Data persisted to database
7. **History Phase**: Conversation context updated and cached

## Testing

Use the `oop_test.php` file to test the new OOP implementation alongside the original version.

## Next Steps

1. Implement remaining agent classes
2. Add unit tests for core components
3. Complete the migration from the original architecture

## Developer Notes

- The project follows PSR-4 autoloading standards
- All classes include proper PHPDoc comments
- Strict typing is enforced throughout the codebase

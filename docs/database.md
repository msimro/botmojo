# BotMojo Database Schema

## Overview
BotMojo uses a MySQL database to store conversations, user preferences, and agent states. This document describes the database schema and relationships.

## Tables

### conversations
Stores chat history and conversation context.
```sql
CREATE TABLE conversations (
    id BINARY(16) PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    user_input TEXT NOT NULL,
    agent_response TEXT NOT NULL,
    agent_name VARCHAR(50) NOT NULL,
    confidence DECIMAL(5,4),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
);
```

### user_preferences
Stores user-specific settings and preferences.
```sql
CREATE TABLE user_preferences (
    id BINARY(16) PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    preferences JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
);
```

### agent_states
Stores agent-specific context and memory.
```sql
CREATE TABLE agent_states (
    id BINARY(16) PRIMARY KEY,
    agent_name VARCHAR(50) NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    state JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_agent_session (agent_name, session_id),
    INDEX idx_agent (agent_name),
    INDEX idx_session (session_id)
);
```

### system_logs
Stores system-level logs and errors.
```sql
CREATE TABLE system_logs (
    id BINARY(16) PRIMARY KEY,
    level VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created (created_at)
);
```

## Indexes and Performance
- All tables use UUID (BINARY 16) as primary keys
- Appropriate indexes on frequently queried columns
- JSON columns for flexible metadata storage
- Timestamp fields for auditing and cleanup

## Backup and Maintenance
- Daily backups at 2 AM UTC
- Weekly optimization of tables
- Monthly archival of logs older than 30 days
- Quarterly review of index performance

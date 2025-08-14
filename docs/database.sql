-- BotMojo Database Schema - Phase 1 Complete
-- Enhanced schema with optimizations for intelligent agent system

CREATE DATABASE IF NOT EXISTS `assistant_core_v1`;
USE `assistant_core_v1`;

-- Enhanced unified table for all life entities with comprehensive indexing
CREATE TABLE `entities` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL DEFAULT 'default_user', -- Multi-user support ready
  `type` VARCHAR(50) NOT NULL, -- person, event, task, transaction, place, etc.
  `primary_name` VARCHAR(255),  -- Searchable, top-level identifier
  `data` JSON NOT NULL,         -- Enhanced agent-generated component data (v1.1)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Enhanced indexing for performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_primary_name` (`primary_name`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_updated_at` (`updated_at`),
  INDEX `idx_user_type` (`user_id`, `type`),
  
  -- Full-text search on primary_name for intelligent querying
  FULLTEXT KEY `ft_primary_name` (`primary_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced relationship management with foreign key constraints and metadata
CREATE TABLE `relationships` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL DEFAULT 'default_user',
  `source_entity_id` VARCHAR(36) NOT NULL,
  `target_entity_id` VARCHAR(36) NOT NULL,
  `type` VARCHAR(50) NOT NULL, -- works_at, lives_in, likes, scheduled_for, etc.
  `strength` DECIMAL(3,2) DEFAULT 1.00, -- Relationship strength (0.01-1.00)
  `metadata` JSON NULL, -- Additional relationship context
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign key constraints for data integrity
  FOREIGN KEY (`source_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  
  -- Enhanced indexing for relationship queries
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_relationship_type` (`type`),
  INDEX `idx_source_entity` (`source_entity_id`),
  INDEX `idx_target_entity` (`target_entity_id`),
  INDEX `idx_strength` (`strength`),
  INDEX `idx_user_type` (`user_id`, `type`),
  INDEX `idx_bidirectional` (`source_entity_id`, `target_entity_id`),
  
  -- Prevent duplicate relationships
  UNIQUE KEY `unique_relationship` (`user_id`, `source_entity_id`, `target_entity_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agent processing log for debugging and analytics
CREATE TABLE `agent_logs` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL DEFAULT 'default_user',
  `agent_name` VARCHAR(50) NOT NULL,
  `agent_version` VARCHAR(10) NOT NULL DEFAULT '1.1',
  `input_data` JSON NOT NULL,
  `output_data` JSON NOT NULL,
  `processing_time_ms` INT UNSIGNED,
  `status` ENUM('success', 'error', 'warning') DEFAULT 'success',
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_agent_name` (`agent_name`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_agent_version` (`agent_name`, `agent_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversation analytics for learning and improvement
CREATE TABLE `conversation_analytics` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `user_id` VARCHAR(36) NOT NULL DEFAULT 'default_user',
  `conversation_id` VARCHAR(36) NOT NULL,
  `user_input` TEXT NOT NULL,
  `triage_summary` TEXT,
  `agents_used` JSON, -- Array of agents that processed this input
  `entities_created` INT DEFAULT 0,
  `entities_updated` INT DEFAULT 0,
  `relationships_created` INT DEFAULT 0,
  `processing_success` BOOLEAN DEFAULT TRUE,
  `user_satisfaction` TINYINT NULL, -- 1-5 rating if provided
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_conversation_id` (`conversation_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_processing_success` (`processing_success`),
  
  -- Full-text search on user input for pattern analysis
  FULLTEXT KEY `ft_user_input` (`user_input`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing enhanced agents
INSERT INTO `entities` (`id`, `user_id`, `type`, `primary_name`, `data`) VALUES
(
  'sample-person-001', 
  'default_user', 
  'person', 
  'John Smith',
  JSON_OBJECT(
    'version', '1.1',
    'memory_component', JSON_OBJECT(
      'full_name', 'John Smith',
      'job_title', 'Software Engineer',
      'employer', 'Google',
      'preferences', JSON_ARRAY('coffee', 'tech'),
      'attributes', JSON_OBJECT('experience_level', 'senior')
    )
  )
),
(
  'sample-event-001',
  'default_user',
  'event',
  'Team Meeting',
  JSON_OBJECT(
    'version', '1.1',
    'planner_component', JSON_OBJECT(
      'title', 'Team Meeting',
      'scheduled_time', '2025-08-08 15:00:00',
      'duration_minutes', 60,
      'priority', 'high',
      'location', 'Conference Room A'
    )
  )
),
(
  'sample-transaction-001',
  'default_user',
  'transaction',
  'McDonald\'s Lunch',
  JSON_OBJECT(
    'version', '1.1',
    'finance_component', JSON_OBJECT(
      'amount', 25.00,
      'currency', 'USD',
      'category', 'Food & Dining',
      'vendor', 'McDonald\'s',
      'transaction_type', 'expense',
      'payment_method', 'credit_card'
    )
  )
);

-- Sample relationships
INSERT INTO `relationships` (`id`, `user_id`, `source_entity_id`, `target_entity_id`, `type`, `strength`) VALUES
('rel-001', 'default_user', 'sample-person-001', 'sample-event-001', 'scheduled_for', 1.00),
('rel-002', 'default_user', 'sample-person-001', 'sample-transaction-001', 'paid_by', 0.80);

-- Views for common queries
CREATE VIEW `entity_summary` AS
SELECT 
  e.type,
  COUNT(*) as count,
  MIN(e.created_at) as first_created,
  MAX(e.updated_at) as last_updated
FROM entities e 
GROUP BY e.type;

CREATE VIEW `relationship_summary` AS
SELECT 
  r.type,
  COUNT(*) as count,
  AVG(r.strength) as avg_strength,
  MIN(r.created_at) as first_created
FROM relationships r 
GROUP BY r.type;

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

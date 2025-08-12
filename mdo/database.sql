CREATE DATABASE `multi_agent_poc`;
USE `multi_agent_poc`;

-- For MemoryAgent
CREATE TABLE `entities` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL,
  `data` JSON NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `relationships` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `source_entity_id` VARCHAR(36) NOT NULL,
  `target_entity_id` VARCHAR(36) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  FOREIGN KEY (`source_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_entity_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- For PlannerAgent
CREATE TABLE `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `due_date` DATETIME,
  `status` VARCHAR(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- For FinanceAgent
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `category` VARCHAR(100),
  `transaction_date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
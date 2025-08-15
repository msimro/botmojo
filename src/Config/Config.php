<?php

declare(strict_types=1);

namespace BotMojo\Config;

use BotMojo\Config\Exception\ConfigException;

/**
 * Configuration Manager
 * 
 * Handles loading and validation of configuration files
 */
class Config
{
    private array $config = [];
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->loadConfig();
    }

    /**
     * Load configuration from files
     */
    private function loadConfig(): void
    {
        // Load default configuration
        $defaultConfig = require $this->configPath . '/default.php';
        $this->config = $defaultConfig;

        // Load tool configurations
        $toolsPath = $this->configPath . '/tools';
        if (is_dir($toolsPath)) {
            foreach (glob($toolsPath . '/*.php') as $file) {
                $toolName = basename($file, '.php');
                $toolConfig = require $file;
                $this->config['tools'][$toolName] = $toolConfig;
            }
        }
    }

    /**
     * Get configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = $this->config;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $config = &$this->config;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                $config = &$config[$part];
            }
        }
    }
}

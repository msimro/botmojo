<?php

/**
 * BotMojo - Personal AI Assistant
 *
 * @category   Tools
 * @package    BotMojo
 * @author     BotMojo Team
 * @license    MIT
 */

declare(strict_types=1);

namespace BotMojo\Tools;

use BotMojo\Core\AbstractTool;
use BotMojo\Exceptions\BotMojoException;

/**
 * Prompt Builder
 *
 * Manages prompt templates and assembly for AI interactions.
 * Supports component-based prompt assembly with placeholders.
 */
class PromptBuilder extends AbstractTool
{
    /**
     * Required configuration keys
     *
     * @var array<string>
     */
    private const REQUIRED_CONFIG = ['prompt_dir'];
    
    /**
     * Validate the configuration
     *
     * Ensure that all required configuration parameters are present.
     *
     * @throws BotMojoException If configuration is invalid
     * @return void
     */
    protected function validateConfig(): void
    {
        foreach (self::REQUIRED_CONFIG as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                throw new BotMojoException(
                    "Missing required configuration: {$key}",
                    ['tool' => 'PromptBuilder']
                );
            }
        }
        
        // Ensure prompt directory exists
        $promptDir = $this->config['prompt_dir'];
        if (!is_dir($promptDir)) {
            throw new BotMojoException(
                "Prompt directory not found: {$promptDir}",
                ['tool' => 'PromptBuilder']
            );
        }
    }
    
    /**
     * Build a prompt from components
     *
     * Assembles a prompt from a base template and component files.
     *
     * @param string              $baseTemplate   Path to the base template (relative to prompt_dir)
     * @param array<string, string> $components     Component files to include
     *
     * @throws BotMojoException If a component file cannot be read
     * @return string The assembled prompt
     */
    public function build(string $baseTemplate, array $components = []): string
    {
        $promptDir = $this->config['prompt_dir'];
        $baseTemplatePath = $promptDir . '/' . $baseTemplate;
        
        // Ensure base template exists
        if (!file_exists($baseTemplatePath)) {
            throw new BotMojoException(
                "Base template not found: {$baseTemplatePath}",
                ['tool' => 'PromptBuilder']
            );
        }
        
        // Read base template
        $prompt = file_get_contents($baseTemplatePath);
        if ($prompt === false) {
            throw new BotMojoException(
                "Failed to read base template: {$baseTemplatePath}",
                ['tool' => 'PromptBuilder']
            );
        }
        
        // Add components
        foreach ($components as $placeholder => $componentFile) {
            $componentPath = $promptDir . '/' . $componentFile;
            
            if (!file_exists($componentPath)) {
                throw new BotMojoException(
                    "Component file not found: {$componentPath}",
                    ['tool' => 'PromptBuilder']
                );
            }
            
            $componentContent = file_get_contents($componentPath);
            if ($componentContent === false) {
                throw new BotMojoException(
                    "Failed to read component file: {$componentPath}",
                    ['tool' => 'PromptBuilder']
                );
            }
            
            $prompt = str_replace("{{" . $placeholder . "}}", $componentContent, $prompt);
        }
        
        return $prompt;
    }
    
    /**
     * Replace placeholders in a prompt
     *
     * Replaces {{placeholder}} markers with actual values.
     *
     * @param string              $prompt      The prompt with placeholders
     * @param array<string, mixed> $values      Values to replace placeholders with
     *
     * @return string The prompt with replaced placeholders
     */
    public function replacePlaceholders(string $prompt, array $values): string
    {
        foreach ($values as $placeholder => $value) {
            $prompt = str_replace("{{" . $placeholder . "}}", (string)$value, $prompt);
        }
        
        return $prompt;
    }
}

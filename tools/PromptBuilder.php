<?php
/**
 * PromptBuilder - Dynamic AI Prompt Assembly Tool
 * 
 * This class handles the dynamic construction of AI prompts by combining
 * base templates with reusable components. It enables modular prompt design
 * and easy maintenance of prompt templates.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */
class PromptBuilder {
    /** @var string Base path to the prompts directory */
    private string $basePath;
    
    /**
     * Constructor - Initialize with prompts directory path
     * 
     * @param string $promptsDirectory Path to directory containing prompt templates
     */
    public function __construct(string $promptsDirectory) { 
        $this->basePath = rtrim($promptsDirectory, '/'); 
    }
    
    /**
     * Build a complete prompt by combining base template with components
     * Reads a base template file and replaces placeholders with component content
     * 
     * @param string $baseTemplateName Relative path to base template file
     * @param array $components Associative array mapping placeholder names to component file paths
     * @return string Complete assembled prompt text
     */
    public function build(string $baseTemplateName, array $components): string {
        // Load the base template content
        $baseTemplate = file_get_contents($this->basePath . '/' . $baseTemplateName);
        
        // Replace each placeholder with its corresponding component content
        foreach ($components as $placeholder => $componentFile) {
            $componentContent = file_get_contents($this->basePath . '/' . $componentFile);
            $baseTemplate = str_replace("{{{$placeholder}}}", $componentContent, $baseTemplate);
        }
        
        return $baseTemplate;
    }
    
    /**
     * Replace placeholders in a template with provided values
     * Simple string replacement for dynamic content injection
     * 
     * @param string $template The template string containing placeholders
     * @param array $placeholders Associative array mapping placeholder names to replacement values
     * @return string Template with placeholders replaced by actual values
     */
    public function replacePlaceholders(string $template, array $placeholders): string {
        foreach ($placeholders as $placeholder => $value) {
            $template = str_replace("{{{$placeholder}}}", $value, $template);
        }
        return $template;
    }
}

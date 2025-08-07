<?php
/**
 * GeneralistAgent - Enhanced General Purpose Component Creator
 * 
 * This agent serves as an intelligent fallback for complex queries, general chat,
 * information requests, and content analysis that doesn't fit into specialized 
 * agent categories. It provides contextual analysis, topic classification,
 * and intelligent response generation.
 * 
 * @author AI Personal Assistant Team
 * @version 1.1
 * @since 2025-08-07
 */
class GeneralistAgent {
    
    /**
     * Create a general-purpose component from provided data
     * Processes complex content with intelligent analysis and classification
     * 
     * @param array $data Raw general data from the triage system
     * @return array Enhanced general component with intelligent analysis
     */
    public function createComponent(array $data): array {
        // Extract enhanced information from triage context
        $extractedInfo = $this->extractGeneralInformation($data);
        
        return [
            // Core content information (enhanced)
            'content' => $this->generateContent($extractedInfo, $data),
            'type' => $this->determineContentType($extractedInfo, $data),
            'topic' => $this->classifyTopic($extractedInfo, $data),
            'subtopics' => $extractedInfo['subtopics'] ?? [],
            
            // Enhanced contextual analysis
            'context' => $this->generateContext($extractedInfo, $data),
            'intent_analysis' => $extractedInfo['intent'] ?? [],
            'complexity_level' => $this->assessComplexity($extractedInfo),
            'domain_expertise_required' => $extractedInfo['expertise_domains'] ?? [],
            
            // Response strategy
            'response_type' => $this->determineResponseType($extractedInfo, $data),
            'suggested_actions' => $this->suggestActions($extractedInfo),
            'followup_potential' => $extractedInfo['followup_potential'] ?? [],
            
            // Intelligence metadata
            'confidence_level' => $this->calculateConfidence($extractedInfo),
            'processing_approach' => $extractedInfo['approach'] ?? 'conversational',
            'key_entities' => $extractedInfo['entities'] ?? [],
            'sentiment_analysis' => $extractedInfo['sentiment'] ?? 'neutral',
            
            // Enhanced metadata
            'natural_language_input' => $extractedInfo['original_text'] ?? '',
            'parsing_details' => $extractedInfo['parsing_details'] ?? [],
            'suggested_tags' => $extractedInfo['tags'] ?? []
        ];
    }
    
    /**
     * Extract enhanced information from triage data and natural language
     * Performs comprehensive analysis of general queries and requests
     * 
     * @param array $data Complete data from triage system
     * @return array Enhanced general information with intelligent analysis
     */
    private function extractGeneralInformation(array $data): array {
        $extracted = [
            'content' => '',
            'type' => 'general_query',
            'topic' => 'general',
            'subtopics' => [],
            'intent' => [],
            'complexity' => 'medium',
            'expertise_domains' => [],
            'entities' => [],
            'sentiment' => 'neutral',
            'approach' => 'conversational',
            'tags' => [],
            'parsing_details' => [],
            'original_text' => '',
            'followup_potential' => []
        ];
        
        // Get text to analyze
        $triageSummary = $data['triage_summary'] ?? '';
        $originalQuery = $data['original_query'] ?? '';
        $analysisText = trim($triageSummary . ' ' . $originalQuery);
        $extracted['original_text'] = $analysisText;
        
        if ($analysisText) {
            // Analyze query intent
            $extracted['intent'] = $this->analyzeIntent($analysisText);
            
            // Classify topics and domains
            $topicInfo = $this->classifyTopicsAndDomains($analysisText);
            $extracted = array_merge($extracted, $topicInfo);
            
            // Extract entities and concepts
            $extracted['entities'] = $this->extractEntities($analysisText);
            
            // Analyze sentiment and tone
            $extracted['sentiment'] = $this->analyzeSentiment($analysisText);
            
            // Assess complexity and expertise needs
            $extracted['complexity'] = $this->assessQueryComplexity($analysisText);
            $extracted['expertise_domains'] = $this->identifyExpertiseDomains($analysisText);
            
            // Determine processing approach
            $extracted['approach'] = $this->determineProcessingApproach($analysisText);
            
            // Generate contextual tags
            $extracted['tags'] = $this->generateContextualTags($extracted, $analysisText);
            
            // Identify followup potential
            $extracted['followup_potential'] = $this->identifyFollowupPotential($analysisText);
            
            // Store parsing details
            $extracted['parsing_details'] = [
                'intent_detected' => !empty($extracted['intent']),
                'topics_identified' => count($extracted['subtopics']),
                'entities_found' => count($extracted['entities']),
                'complexity_assessed' => true
            ];
        }
        
        return $extracted;
    }
    
    /**
     * Analyze user intent from query text
     */
    private function analyzeIntent(string $text): array {
        $intents = [];
        
        // Information seeking patterns
        if (preg_match('/\b(?:what|how|why|when|where|who|explain|tell me|help me understand)\b/i', $text)) {
            $intents[] = 'information_seeking';
        }
        
        // Question patterns
        if (preg_match('/\?/', $text)) {
            $intents[] = 'questioning';
        }
        
        // Learning and education
        if (preg_match('/\b(?:learn|understand|study|teach|educate|guide)\b/i', $text)) {
            $intents[] = 'learning';
        }
        
        // Problem solving
        if (preg_match('/\b(?:solve|fix|troubleshoot|resolve|help)\b/i', $text)) {
            $intents[] = 'problem_solving';
        }
        
        // Comparison and analysis
        if (preg_match('/\b(?:compare|versus|vs|difference|better|best|pros|cons)\b/i', $text)) {
            $intents[] = 'comparison';
        }
        
        // Conversational
        if (preg_match('/\b(?:chat|talk|discuss|conversation)\b/i', $text)) {
            $intents[] = 'conversational';
        }
        
        return array_unique($intents);
    }
    
    /**
     * Classify topics and identify domains
     */
    private function classifyTopicsAndDomains(string $text): array {
        $result = [
            'topic' => 'general',
            'subtopics' => [],
            'domain_categories' => []
        ];
        
        // Technology domains
        $techPatterns = [
            'artificial intelligence' => '/\b(?:ai|artificial intelligence|machine learning|deep learning|neural networks?)\b/i',
            'programming' => '/\b(?:programming|coding|software|development|code|python|javascript|java)\b/i',
            'web development' => '/\b(?:web development|html|css|react|angular|vue|frontend|backend)\b/i',
            'data science' => '/\b(?:data science|analytics|statistics|big data|database)\b/i',
            'cybersecurity' => '/\b(?:security|cybersecurity|encryption|hacking|vulnerability)\b/i'
        ];
        
        // Science domains
        $sciencePatterns = [
            'physics' => '/\b(?:physics|quantum|relativity|mechanics|thermodynamics)\b/i',
            'chemistry' => '/\b(?:chemistry|chemical|molecule|reaction|organic)\b/i',
            'biology' => '/\b(?:biology|genetics|evolution|ecology|organism)\b/i',
            'medicine' => '/\b(?:medicine|medical|health|disease|treatment|diagnosis)\b/i',
            'mathematics' => '/\b(?:math|mathematics|algebra|calculus|geometry|statistics)\b/i'
        ];
        
        // Business and finance
        $businessPatterns = [
            'business' => '/\b(?:business|entrepreneur|startup|marketing|sales|strategy)\b/i',
            'finance' => '/\b(?:finance|investment|stocks|trading|economics|money)\b/i',
            'management' => '/\b(?:management|leadership|team|project|planning)\b/i'
        ];
        
        // Lifestyle and personal
        $lifestylePatterns = [
            'health' => '/\b(?:health|fitness|exercise|nutrition|wellness|diet)\b/i',
            'travel' => '/\b(?:travel|vacation|tourism|destination|trip)\b/i',
            'food' => '/\b(?:food|cooking|recipe|cuisine|restaurant|meal)\b/i',
            'entertainment' => '/\b(?:movie|music|book|game|entertainment|hobby)\b/i'
        ];
        
        // Weather and environment
        $environmentPatterns = [
            'weather' => '/\b(?:weather|temperature|rain|snow|sunny|cloudy|forecast)\b/i',
            'environment' => '/\b(?:environment|climate|pollution|sustainability|green)\b/i'
        ];
        
        $allPatterns = array_merge($techPatterns, $sciencePatterns, $businessPatterns, $lifestylePatterns, $environmentPatterns);
        
        foreach ($allPatterns as $topic => $pattern) {
            if (preg_match($pattern, $text)) {
                $result['subtopics'][] = $topic;
                
                // Determine main topic if first match
                if ($result['topic'] === 'general') {
                    $result['topic'] = $topic;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Extract entities and key concepts
     */
    private function extractEntities(string $text): array {
        $entities = [];
        
        // Extract proper nouns (potential entities)
        if (preg_match_all('/\b[A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*\b/', $text, $matches)) {
            foreach ($matches[0] as $entity) {
                if (strlen($entity) > 2 && !in_array(strtolower($entity), ['I', 'The', 'What', 'How', 'Why', 'When', 'Where', 'Who'])) {
                    $entities[] = $entity;
                }
            }
        }
        
        // Extract technical terms
        $technicalTerms = ['API', 'CPU', 'GPU', 'RAM', 'SSD', 'URL', 'HTTP', 'HTTPS', 'SQL', 'JSON', 'XML', 'HTML', 'CSS'];
        foreach ($technicalTerms as $term) {
            if (stripos($text, $term) !== false) {
                $entities[] = $term;
            }
        }
        
        return array_unique($entities);
    }
    
    /**
     * Analyze sentiment and emotional tone
     */
    private function analyzeSentiment(string $text): string {
        // Positive indicators
        $positiveCount = preg_match_all('/\b(?:great|excellent|amazing|wonderful|fantastic|love|like|happy|excited|please|thanks|thank you)\b/i', $text);
        
        // Negative indicators
        $negativeCount = preg_match_all('/\b(?:bad|terrible|awful|hate|dislike|angry|frustrated|confused|difficult|problem|issue|wrong)\b/i', $text);
        
        // Neutral/question indicators
        $questionCount = preg_match_all('/\b(?:what|how|why|when|where|who|can|could|would|should)\b/i', $text);
        
        if ($positiveCount > $negativeCount && $positiveCount > 0) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount && $negativeCount > 0) {
            return 'negative';
        } elseif ($questionCount > 0) {
            return 'inquisitive';
        }
        
        return 'neutral';
    }
    
    /**
     * Assess query complexity level
     */
    private function assessQueryComplexity(string $text): string {
        $complexityScore = 0;
        
        // Length factor
        $wordCount = str_word_count($text);
        if ($wordCount > 20) $complexityScore += 2;
        elseif ($wordCount > 10) $complexityScore += 1;
        
        // Technical terms
        if (preg_match('/\b(?:algorithm|framework|methodology|paradigm|architecture|implementation|optimization)\b/i', $text)) {
            $complexityScore += 3;
        }
        
        // Multiple questions
        $questionCount = substr_count($text, '?');
        $complexityScore += $questionCount;
        
        // Abstract concepts
        if (preg_match('/\b(?:concept|theory|principle|philosophy|abstraction|methodology)\b/i', $text)) {
            $complexityScore += 2;
        }
        
        if ($complexityScore >= 5) return 'high';
        if ($complexityScore >= 3) return 'medium';
        return 'low';
    }
    
    /**
     * Identify domains requiring specific expertise
     */
    private function identifyExpertiseDomains(string $text): array {
        $domains = [];
        
        $expertiseDomains = [
            'technical' => '/\b(?:programming|software|algorithm|database|api|framework)\b/i',
            'scientific' => '/\b(?:research|study|experiment|hypothesis|theory|analysis)\b/i',
            'medical' => '/\b(?:medical|health|disease|treatment|diagnosis|symptoms)\b/i',
            'legal' => '/\b(?:law|legal|contract|regulation|compliance|rights)\b/i',
            'financial' => '/\b(?:investment|trading|finance|tax|accounting|economics)\b/i',
            'academic' => '/\b(?:education|university|research|thesis|academic|scholarly)\b/i'
        ];
        
        foreach ($expertiseDomains as $domain => $pattern) {
            if (preg_match($pattern, $text)) {
                $domains[] = $domain;
            }
        }
        
        return array_unique($domains);
    }
    
    /**
     * Determine processing approach based on query characteristics
     */
    private function determineProcessingApproach(string $text): string {
        // Educational/tutorial approach
        if (preg_match('/\b(?:learn|understand|explain|how to|tutorial|guide)\b/i', $text)) {
            return 'educational';
        }
        
        // Technical/analytical approach
        if (preg_match('/\b(?:technical|detail|specification|architecture|implementation)\b/i', $text)) {
            return 'technical';
        }
        
        // Conversational approach
        if (preg_match('/\b(?:chat|talk|discuss|opinion|think|feel)\b/i', $text)) {
            return 'conversational';
        }
        
        // Informational approach
        if (preg_match('/\b(?:what|information|facts|data|research)\b/i', $text)) {
            return 'informational';
        }
        
        return 'conversational'; // Default
    }
    
    /**
     * Generate contextual tags for better organization
     */
    private function generateContextualTags(array $extracted, string $text): array {
        $tags = [];
        
        // Add intent tags
        $tags = array_merge($tags, $extracted['intent'] ?? []);
        
        // Add topic tags
        $tags = array_merge($tags, $extracted['subtopics'] ?? []);
        
        // Add complexity tag
        $tags[] = $extracted['complexity'] . '_complexity';
        
        // Add sentiment tag
        $tags[] = $extracted['sentiment'];
        
        // Add approach tag
        $tags[] = $extracted['approach'];
        
        // Add domain tags
        $tags = array_merge($tags, $extracted['expertise_domains'] ?? []);
        
        return array_unique($tags);
    }
    
    /**
     * Identify potential for followup questions/conversations
     */
    private function identifyFollowupPotential(string $text): array {
        $potential = [];
        
        // Broad topics that invite followups
        if (preg_match('/\b(?:overview|introduction|basics|fundamentals)\b/i', $text)) {
            $potential[] = 'deep_dive_opportunities';
        }
        
        // Comparison questions
        if (preg_match('/\b(?:compare|versus|difference|better)\b/i', $text)) {
            $potential[] = 'detailed_comparison';
        }
        
        // How-to questions
        if (preg_match('/\b(?:how to|tutorial|guide|steps)\b/i', $text)) {
            $potential[] = 'step_by_step_guidance';
        }
        
        // Complex topics
        if (preg_match('/\b(?:machine learning|artificial intelligence|quantum|blockchain)\b/i', $text)) {
            $potential[] = 'technical_deep_dive';
        }
        
        return array_unique($potential);
    }
    
    /**
     * Generate enhanced content description
     */
    private function generateContent(array $extractedInfo, array $data): string {
        // Use provided content first
        if (!empty($data['content'])) {
            return $data['content'];
        }
        
        // Generate content based on analysis
        $parts = [];
        
        if (!empty($extractedInfo['intent'])) {
            $parts[] = "Intent: " . implode(', ', $extractedInfo['intent']);
        }
        
        if (!empty($extractedInfo['subtopics'])) {
            $parts[] = "Topics: " . implode(', ', $extractedInfo['subtopics']);
        }
        
        return implode(' | ', $parts) ?: 'General query';
    }
    
    /**
     * Determine content type with intelligence
     */
    private function determineContentType(array $extractedInfo, array $data): string {
        // Use component data type if available
        if (!empty($data['type'])) {
            return $data['type'];
        }
        
        // Determine based on intent analysis
        $intents = $extractedInfo['intent'] ?? [];
        
        if (in_array('learning', $intents)) return 'educational_request';
        if (in_array('problem_solving', $intents)) return 'support_request';
        if (in_array('comparison', $intents)) return 'analysis_request';
        if (in_array('information_seeking', $intents)) return 'information_request';
        if (in_array('conversational', $intents)) return 'conversation';
        
        return 'general_query';
    }
    
    /**
     * Classify main topic intelligently
     */
    private function classifyTopic(array $extractedInfo, array $data): string {
        // Use component data topic if available
        if (!empty($data['topic'])) {
            return $data['topic'];
        }
        
        // Use extracted topic
        return $extractedInfo['topic'] ?? 'general';
    }
    
    /**
     * Generate enhanced context
     */
    private function generateContext(array $extractedInfo, array $data): string {
        $contextParts = [];
        
        // Use provided context first
        if (!empty($data['context'])) {
            $contextParts[] = $data['context'];
        }
        
        // Add extracted context
        if (!empty($extractedInfo['complexity'])) {
            $contextParts[] = "Complexity: " . $extractedInfo['complexity'];
        }
        
        if (!empty($extractedInfo['expertise_domains'])) {
            $contextParts[] = "Domains: " . implode(', ', $extractedInfo['expertise_domains']);
        }
        
        return implode(' | ', $contextParts);
    }
    
    /**
     * Assess overall complexity
     */
    private function assessComplexity(array $extractedInfo): string {
        return $extractedInfo['complexity'] ?? 'medium';
    }
    
    /**
     * Determine response type
     */
    private function determineResponseType(array $extractedInfo, array $data): string {
        // Use component data response_type if available
        if (!empty($data['response_type'])) {
            return $data['response_type'];
        }
        
        // Determine based on approach
        $approach = $extractedInfo['approach'] ?? 'conversational';
        
        switch ($approach) {
            case 'educational': return 'explanatory';
            case 'technical': return 'detailed_technical';
            case 'informational': return 'factual';
            case 'conversational': return 'conversational';
            default: return 'informational';
        }
    }
    
    /**
     * Suggest actions based on analysis
     */
    private function suggestActions(array $extractedInfo): array {
        $actions = [];
        
        // Based on intent
        $intents = $extractedInfo['intent'] ?? [];
        
        if (in_array('learning', $intents)) {
            $actions[] = 'provide_educational_resources';
            $actions[] = 'break_down_complex_concepts';
        }
        
        if (in_array('problem_solving', $intents)) {
            $actions[] = 'analyze_problem';
            $actions[] = 'suggest_solutions';
        }
        
        if (in_array('comparison', $intents)) {
            $actions[] = 'create_comparison_table';
            $actions[] = 'highlight_key_differences';
        }
        
        // Based on complexity
        if ($extractedInfo['complexity'] === 'high') {
            $actions[] = 'simplify_explanation';
            $actions[] = 'provide_analogies';
        }
        
        return array_unique($actions);
    }
    
    /**
     * Calculate confidence level
     */
    private function calculateConfidence(array $extractedInfo): string {
        $confidence = 0.5; // Base confidence
        
        // Boost for clear intent
        if (!empty($extractedInfo['intent'])) {
            $confidence += 0.2;
        }
        
        // Boost for identified topics
        if (!empty($extractedInfo['subtopics'])) {
            $confidence += 0.2;
        }
        
        // Boost for clear entities
        if (!empty($extractedInfo['entities'])) {
            $confidence += 0.1;
        }
        
        if ($confidence >= 0.8) return 'high';
        if ($confidence >= 0.6) return 'medium';
        return 'low';
    }
}

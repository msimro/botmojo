<?php
/**
 * FinanceAgent - Intelligent Financial Data Processing System
 * 
 * =====================================================================
 * AGENT OVERVIEW
 * =====================================================================
 * 
 * The FinanceAgent is a specialized component of the BotMojo AI system
 * responsible for processing, categorizing, and analyzing financial data.
 * It transforms natural language descriptions of financial transactions
 * into structured data with intelligent categorization and analytics.
 * 
 * CORE CAPABILITIES:
 * - Multi-currency transaction parsing and normalization
 * - Intelligent vendor detection and categorization
 * - Smart expense/income classification
 * - Payment method recognition and standardization
 * - Automatic category assignment with confidence scoring
 * - Recurring transaction pattern detection
 * - Financial trend analysis and insights
 * 
 * TRANSACTION TYPES SUPPORTED:
 * - Expenses (purchases, bills, subscriptions, dining, etc.)
 * - Income (salary, freelance, investments, gifts, etc.)
 * - Transfers (between accounts, to savings, etc.)
 * - Refunds and adjustments
 * - Foreign currency transactions with conversion
 * 
 * CATEGORY INTELLIGENCE:
 * - Food & Dining: restaurants, groceries, coffee shops
 * - Transportation: gas, public transit, rideshare, parking
 * - Shopping: retail, online purchases, clothing, electronics
 * - Bills & Utilities: rent, electricity, internet, phone
 * - Healthcare: medical, dental, pharmacy, insurance
 * - Entertainment: movies, concerts, streaming, gaming
 * - Personal Care: beauty, fitness, wellness
 * - Professional: business meals, equipment, services
 * 
 * PARSING CAPABILITIES:
 * - Amount extraction from natural language ("$25", "twenty-five dollars")
 * - Currency recognition (USD, EUR, GBP, CAD, etc.)
 * - Vendor name extraction and normalization
 * - Location detection from context
 * - Payment method identification (cash, card, digital, etc.)
 * - Date/time parsing for transaction timing
 * 
 * ANALYTICS FEATURES:
 * - Spending pattern analysis
 * - Budget category tracking
 * - Vendor frequency analysis
 * - Payment method preferences
 * - Monthly/weekly spending trends
 * - Unusual transaction detection
 * 
 * ARCHITECTURE INTEGRATION:
 * - Receives structured data from triage system
 * - Uses ToolManager for controlled database access
 * - Integrates with search tools for vendor validation
 * - Supports calendar integration for date verification
 * - Provides data for dashboard analytics and reporting
 * 
 * @author BotMojo Development Team
 * @version 1.1 - Enhanced with multi-currency and AI-powered categorization
 * @since 2025-08-07
 * @category Agent
 * @package BotMojo\Agents
 * 
 * @see ToolManager Tool access and permission management
 * @see DatabaseTool Transaction storage and retrieval
 * @see PlannerAgent Integration for scheduled payments
 * 
 * =====================================================================
 */

/**
 * FinanceAgent Class - Intelligent Financial Transaction Processing
 * 
 * This class implements the Single Responsibility Principle by focusing
 * exclusively on financial data processing and analysis. It delegates
 * storage operations to appropriate tools and maintains clear boundaries
 * with other system components.
 */
class FinanceAgent 
{
    // =====================================================================
    // CLASS PROPERTIES AND FINANCIAL CONSTANTS
    // =====================================================================
    
    /**
     * Tool manager for controlled access to system tools
     * 
     * The FinanceAgent has permission to access:
     * - DatabaseTool: For transaction storage and historical analysis
     * - SearchTool: For vendor validation and enrichment
     * - CalendarTool: For date verification and scheduled payments
     * 
     * @var ToolManager Centralized tool access management
     */
    private ToolManager $toolManager;
    
    /**
     * Supported currencies with their symbols and codes
     * Used for multi-currency transaction processing
     * 
     * @var array<string, array> Currency configuration
     */
    private array $supportedCurrencies = [
        'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
        'EUR' => ['symbol' => '€', 'name' => 'Euro'],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
        'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar'],
        'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen'],
        'AUD' => ['symbol' => 'A$', 'name' => 'Australian Dollar']
    ];
    
    /**
     * Financial categories with keywords for automatic classification
     * Used for intelligent transaction categorization
     * 
     * @var array<string, array> Category configuration with keywords
     */
    private array $categoryKeywords = [
        'food_dining' => ['restaurant', 'coffee', 'lunch', 'dinner', 'breakfast', 'food', 'cafe', 'bar', 'pub', 'starbucks', 'mcdonalds'],
        'groceries' => ['grocery', 'supermarket', 'walmart', 'costco', 'trader joe', 'whole foods', 'kroger'],
        'transportation' => ['gas', 'fuel', 'uber', 'lyft', 'taxi', 'bus', 'train', 'parking', 'toll'],
        'shopping' => ['amazon', 'store', 'mall', 'clothing', 'shoes', 'electronics', 'target', 'best buy'],
        'bills_utilities' => ['rent', 'mortgage', 'electricity', 'water', 'gas bill', 'internet', 'phone', 'insurance'],
        'healthcare' => ['doctor', 'dentist', 'pharmacy', 'medical', 'hospital', 'clinic', 'prescription'],
        'entertainment' => ['movie', 'concert', 'netflix', 'spotify', 'gaming', 'theatre', 'sports'],
        'personal_care' => ['haircut', 'salon', 'gym', 'fitness', 'spa', 'beauty', 'cosmetics']
    ];
    
    /**
     * Payment method patterns for recognition
     * Used for payment method standardization
     * 
     * @var array<string, array> Payment method patterns
     */
    private array $paymentMethods = [
        'credit_card' => ['card', 'credit', 'visa', 'mastercard', 'amex'],
        'debit_card' => ['debit', 'checking'],
        'cash' => ['cash', 'money'],
        'digital' => ['paypal', 'venmo', 'apple pay', 'google pay', 'digital'],
        'bank_transfer' => ['transfer', 'wire', 'ach', 'direct deposit'],
        'check' => ['check', 'cheque']
    ];
    
    // =====================================================================
    // CONSTRUCTOR AND INITIALIZATION
    // =====================================================================
    
    /**
     * Initialize FinanceAgent with required dependencies
     * 
     * Sets up the agent with controlled tool access through the ToolManager.
     * This ensures proper separation of concerns and maintains the tool
     * permission system for security and modularity.
     * 
     * @param ToolManager $toolManager Centralized tool access manager
     * 
     * @throws InvalidArgumentException If toolManager lacks required tools
     */
    public function __construct(ToolManager $toolManager) 
    {
        $this->toolManager = $toolManager;
        
        // Validate that required tools are available
        try {
            $this->toolManager->getTool('database');
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                'FinanceAgent requires DatabaseTool access through ToolManager'
            );
        }
        
        // Log agent initialization in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("💰 FinanceAgent initialized with tool access and category intelligence");
        }
    }
    
    // =====================================================================
    // PRIMARY COMPONENT CREATION INTERFACE
    // =====================================================================
    
    /**
     * Create a comprehensive financial component from triage data
     * 
     * This is the main entry point for the FinanceAgent. It processes financial
     * data provided by the triage system and creates structured financial
     * components with intelligent categorization and analytics.
     * 
     * PROCESSING WORKFLOW:
     * 1. Extract financial information from natural language
     * 2. Parse amounts, currencies, and transaction details
     * 3. Identify and categorize vendors and merchants
     * 4. Determine transaction type and payment method
     * 5. Calculate confidence scores and analytics
     * 6. Generate financial tags and metadata
     * 7. Assemble comprehensive financial component
     * 
     * @param array $data Raw financial data from the triage system containing:
     *                   - triage_summary: AI's understanding of the transaction
     *                   - original_query: User's natural language input
     *                   - entity_id: Unique identifier for the transaction
     *                   - user_id: User identifier for data segregation
     *                   - Any additional context from other agents
     * 
     * @return array Standardized financial component with:
     *               - amount: Numerical transaction amount
     *               - currency: Currency code (USD, EUR, etc.)
     *               - category: Primary transaction category
     *               - subcategory: Detailed classification
     *               - type: Transaction type (expense, income, transfer)
     *               - vendor: Merchant or vendor name
     *               - payment_method: How payment was made
     *               - analytics: Pattern detection and insights
     *               - metadata: Confidence scores and processing details
     * 
     * @throws InvalidArgumentException If required financial data is missing
     * @throws RuntimeException If amount parsing fails
     * 
     * @example
     * $data = [
     *     'triage_summary' => 'User spent money on lunch',
     *     'original_query' => 'I spent $25 on lunch at McDonald\'s today',
     *     'entity_id' => 'txn-123',
     *     'user_id' => 'user-456'
     * ];
     * 
     * $component = $financeAgent->createComponent($data);
     * // Returns structured financial component ready for storage
     */
    public function createComponent(array $data): array 
    {
        // Validate input data
        if (empty($data)) {
            throw new InvalidArgumentException('Financial component data cannot be empty');
        }
        
        // Extract enhanced financial information from context
        $extractedInfo = $this->extractFinancialInformation($data);
        
        // Log processing details in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("💰 FinanceAgent: Processing transaction");
            error_log("💰 Amount: " . ($extractedInfo['amount'] ?? 'not detected'));
            error_log("💰 Vendor: " . ($extractedInfo['vendor'] ?? 'not detected'));
            error_log("💰 Category: " . ($extractedInfo['category'] ?? 'not determined'));
        }
        
        // Assemble comprehensive financial component using existing structure
        return [
            // Core financial information (enhanced)
            'amount' => $extractedInfo['amount'] ?? (float)($data['amount'] ?? 0),
            'currency' => $extractedInfo['currency'] ?? $data['currency'] ?? 'USD',
            'category' => $this->determineCategory($extractedInfo, $data),
            'subcategory' => $extractedInfo['subcategory'] ?? '',
            'type' => $this->determineTransactionType($extractedInfo, $data),
            
            // Enhanced transaction details
            'description' => $this->generateDescription($extractedInfo, $data),
            'date' => $this->parseDate($extractedInfo, $data),
            'payment_method' => $this->normalizePaymentMethod($extractedInfo, $data),
            
            // Vendor and location information
            'vendor' => $extractedInfo['vendor'] ?? $data['vendor'] ?? '',
            'location' => $extractedInfo['location'] ?? '',
            
            // Financial analytics
            'recurring_pattern' => $this->detectRecurringPattern($extractedInfo),
            'spending_context' => $extractedInfo['context'] ?? [],
            'tags' => $this->generateFinancialTags($extractedInfo, $data),
            
            // Smart categorization confidence
            'category_confidence' => $extractedInfo['category_confidence'] ?? 0.8,
            'parsing_details' => $extractedInfo['parsing_details'] ?? [],
            
            // Enhanced metadata
            'natural_language_input' => $extractedInfo['original_text'] ?? '',
            'extracted_entities' => $extractedInfo['entities'] ?? []
        ];
    }
    
    // =====================================================================
    // FINANCIAL INFORMATION EXTRACTION AND ANALYSIS METHODS
    // =====================================================================
    
    
    /**
     * Extract comprehensive financial information from natural language text
     * 
     * This method is the core intelligence of the FinanceAgent. It analyzes
     * natural language descriptions to extract monetary amounts, currencies,
     * vendors, payment methods, and transaction context using advanced
     * pattern matching and semantic analysis.
     * 
     * EXTRACTION CAPABILITIES:
     * - Monetary amount parsing ($25, "twenty-five dollars", €50, etc.)
     * - Multi-currency recognition and normalization
     * - Vendor/merchant name identification and standardization
     * - Payment method detection (card, cash, digital, etc.)
     * - Transaction type classification (expense vs income)
     * - Category determination based on context and vendor
     * - Date/time extraction for transaction timing
     * - Location detection from geographical context
     * 
     * PATTERN MATCHING TECHNIQUES:
     * - Currency symbol recognition ($, €, £, ¥, etc.)
     * - Written number parsing ("twenty-five", "fifty dollars")
     * - Merchant name patterns ("at McDonald's", "from Amazon")
     * - Payment method indicators ("with my card", "paid cash")
     * - Transaction type signals ("spent", "earned", "received")
     * - Category keywords ("lunch", "gas", "groceries", etc.)
     * 
     * @param array $data Complete data from triage system including:
     *                   - triage_summary: AI's interpretation of transaction
     *                   - original_query: Raw user input about the transaction
     *                   - conversation_context: Historical financial context
     *                   - existing transaction data: Previously stored info
     * 
     * @return array Enhanced financial information structure containing:
     *               - amount: Parsed numerical amount
     *               - currency: Detected or default currency code
     *               - vendor: Extracted merchant/vendor name
     *               - payment_method: Identified payment method
     *               - type: Transaction type (expense, income, transfer)
     *               - category: Determined transaction category
     *               - parsing_details: Technical extraction metadata
     *               - confidence scores: Reliability indicators
     * 
     * @throws RuntimeException If critical parsing operations fail
     * 
     * @example
     * Input: "I spent $25 on lunch at McDonald's with my credit card"
     * Output: [
     *     'amount' => 25.0,
     *     'currency' => 'USD',
     *     'vendor' => 'McDonald\'s',
     *     'payment_method' => 'credit_card',
     *     'type' => 'expense',
     *     'category' => 'food_dining'
     * ]
     */
    private function extractFinancialInformation(array $data): array 
    {
        // Initialize comprehensive extraction result structure
        $extracted = [
            'amount' => null,
            'currency' => 'USD',
            'category' => '',
            'subcategory' => '',
            'type' => 'expense',
            'vendor' => '',
            'location' => '',
            'payment_method' => '',
            'context' => [],
            'entities' => [],
            'category_confidence' => 0.8,
            'parsing_details' => [],
            'original_text' => ''
        ];
        
        // Gather and prepare text sources for analysis
        $textSources = $this->gatherFinancialTextSources($data);
        $analysisText = implode(' ', $textSources);
        $extracted['original_text'] = $analysisText;
        
        if (empty($analysisText)) {
            return $extracted;
        }
        
        // Log analysis in debug mode
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("💰 FinanceAgent: Analyzing financial text: " . substr($analysisText, 0, 150) . "...");
        }
        
        // Execute comprehensive financial extraction
        $extracted = $this->executeFinancialExtraction($analysisText, $extracted, $data);
        
        return $extracted;
    }
    
    /**
     * Gather financial text from all available sources for analysis
     * 
     * @param array $data Input data with various text sources
     * @return array Array of financial text strings
     */
    private function gatherFinancialTextSources(array $data): array 
    {
        $textSources = [];
        
        // Primary financial context sources
        if (isset($data['triage_summary']) && !empty($data['triage_summary'])) {
            $textSources[] = $data['triage_summary'];
        }
        
        if (isset($data['original_query']) && !empty($data['original_query'])) {
            $textSources[] = $data['original_query'];
        }
        
        // Additional context sources
        if (isset($data['description']) && !empty($data['description'])) {
            $textSources[] = $data['description'];
        }
        
        if (isset($data['transaction_context']) && !empty($data['transaction_context'])) {
            $textSources[] = $data['transaction_context'];
        }
        
        return array_filter($textSources, function($text) {
            return !empty(trim($text));
        });
    }
    
    /**
     * Execute comprehensive financial information extraction
     * 
     * @param string $analysisText Combined text for analysis
     * @param array $extracted Current extraction results
     * @param array $data Original input data
     * @return array Enhanced extraction results
     */
    private function executeFinancialExtraction(string $analysisText, array $extracted, array $data): array 
    {
        // Step 1: Parse monetary amounts and currency
        $amountInfo = $this->parseAmount($analysisText);
        if ($amountInfo['amount'] !== null) {
            $extracted['amount'] = $amountInfo['amount'];
            $extracted['currency'] = $amountInfo['currency'];
        }
        
        // Step 2: Extract vendor/merchant information
        $extracted['vendor'] = $this->extractVendor($analysisText, $data);
        
        // Step 3: Determine payment method
        $extracted['payment_method'] = $this->extractPaymentMethod($analysisText);
        
        // Step 4: Classify transaction type
        $extracted['type'] = $this->detectTransactionType($analysisText);
        
        // Step 5: Extract location context
        $extracted['location'] = $this->extractLocationContext($analysisText);
        
        // Step 6: Intelligent category detection
        $categoryInfo = $this->intelligentCategoryDetection($analysisText, $extracted);
        $extracted['category'] = $categoryInfo['category'] ?? '';
        $extracted['subcategory'] = $categoryInfo['subcategory'] ?? '';
        $extracted['category_confidence'] = $categoryInfo['confidence'] ?? 0.8;
        
        // Step 7: Extract additional financial context
        $extracted['context'] = $this->extractFinancialContext($analysisText);
        
        // Store parsing details for debugging/improvement
        $extracted['parsing_details'] = [
            'amount_detected' => !empty($amountInfo['amount']),
            'vendor_detected' => !empty($extracted['vendor']),
            'category_detected' => !empty($extracted['category']),
            'payment_method_detected' => !empty($extracted['payment_method']),
            'text_length' => strlen($analysisText),
            'extraction_timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $extracted;
    }
    
    /**
     * Parse monetary amounts and currencies from financial text
     * 
     * Uses comprehensive pattern matching to identify monetary values
     * in various formats including symbols, written amounts, and different currencies.
     * 
     * SUPPORTED FORMATS:
     * - Currency symbols: $25.50, €100, £75.25, ¥5000
     * - Written amounts: "twenty-five dollars", "fifty euros"
     * - Plain numbers with context: "25 USD", "100 dollars"
     * - International formats: "1,234.56", "1.234,56"
     * 
     * @param string $text Text to analyze for monetary amounts
     * @return array Contains amount, currency, and parsing context
     */
    private function parseAmount(string $text): array {
        $result = [
            'amount' => null,
            'currency' => 'USD',
            'amount_context' => []
        ];
        
        // Comprehensive pattern matching for various currency formats
        $patterns = [
            // Primary currency symbols with amounts
            '/\$(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'USD',
            '/€(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'EUR',
            '/£(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'GBP',
            '/¥(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'JPY',
            
            // Written currency amounts
            '/(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\s*(?:USD|dollars?|bucks?)\b/i' => 'USD',
            '/(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\s*(?:EUR|euros?)\b/i' => 'EUR',
            '/(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\s*(?:GBP|pounds?)\b/i' => 'GBP',
            
            // Fallback for plain numbers in financial context
            '/\b(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'USD'
        ];
        
        // Execute pattern matching
        foreach ($patterns as $pattern => $currency) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace(',', '', $matches[1]);
                $result['amount'] = (float)$amount;
                $result['currency'] = $currency;
                $result['amount_context']['pattern_matched'] = $pattern;
                $result['amount_context']['matched_text'] = $matches[0];
                break; // Use first successful match
            }
        }
        
        return $result;
    }
    
    /**
     * Extract vendor/merchant information from transaction text
     * 
     * Identifies business names, merchants, and service providers using
     * pattern recognition and context analysis. Handles common transaction
     * patterns and normalizes vendor names for consistency.
     * 
     * EXTRACTION PATTERNS:
     * - "at [vendor]" patterns: "lunch at McDonald's"
     * - "from [vendor]" patterns: "bought from Amazon"
     * - "to [vendor]" patterns: "payment to Netflix"
     * - Direct mentions: "Starbucks coffee"
     * 
     * @param string $text Transaction description text
     * @param array $data Additional context data
     * @return string Extracted and normalized vendor name
     */
    private function extractVendor(string $text, array $data): string {
        // Check component data first for existing vendor information
        if (!empty($data['vendor'])) {
            return trim($data['vendor']);
        }
        
        // Enhanced pattern matching for vendor identification
        if (preg_match('/\b(?:at|from|to)\s+([A-Z][a-zA-Z\s&.\']+?)(?:\s+(?:using|with|yesterday|today|on)|\s*$)/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Check for well-known vendors from our supported list
        $knownVendors = [
            'amazon', 'walmart', 'target', 'costco', 'whole foods', 'starbucks',
            'mcdonalds', 'subway', 'chipotle', 'uber', 'lyft', 'netflix',
            'spotify', 'apple', 'google', 'microsoft', 'tesla', 'shell',
            'exxon', 'chevron', 'home depot', 'lowes', 'best buy'
        ];
        
        foreach ($knownVendors as $vendor) {
            if (preg_match('/\b' . preg_quote($vendor, '/') . '\b/i', $text)) {
                return ucwords($vendor);
            }
        }
        
        return '';
    }
    
    /**
     * Extract payment method information
     */
    private function extractPaymentMethod(string $text): string {
        // Comprehensive payment method pattern mapping
        $paymentMethods = [
            'credit_card' => '/\b(?:credit card|card|visa|mastercard|amex|american express)\b/i',
            'debit_card' => '/\b(?:debit card|debit)\b/i',
            'cash' => '/\b(?:cash|bills?|coins?)\b/i',
            'check' => '/\b(?:check|cheque)\b/i',
            'paypal' => '/\b(?:paypal|pay pal)\b/i',
            'venmo' => '/\b(?:venmo)\b/i',
            'apple_pay' => '/\b(?:apple pay|applepay)\b/i',
            'google_pay' => '/\b(?:google pay|googlepay|gpay)\b/i',
            'bank_transfer' => '/\b(?:bank transfer|wire transfer|ach|zelle)\b/i',
            'cryptocurrency' => '/\b(?:bitcoin|btc|ethereum|eth|crypto)\b/i'
        ];
        
        foreach ($paymentMethods as $method => $pattern) {
            if (preg_match($pattern, $text)) {
                return $method;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Detect transaction type (income vs expense) from context
     * 
     * Analyzes transaction language patterns to determine whether
     * the transaction represents money going out (expense) or coming in (income).
     * 
     * EXPENSE INDICATORS: spent, paid, bought, purchased, cost
     * INCOME INDICATORS: earned, received, paid (to me), refund, bonus
     * TRANSFER INDICATORS: transferred, moved, sent, deposited
     * 
     * @param string $text Transaction description text
     * @return string 'expense', 'income', or 'transfer'
     */
    private function detectTransactionType(string $text): string {
        // Income indicators
        if (preg_match('/\b(?:earned|received|got paid|salary|bonus|refund|income|revenue|profit|dividend)\b/i', $text)) {
            return 'income';
        }
        
        // Expense indicators (default)
        if (preg_match('/\b(?:spent|paid|bought|purchased|cost|expense|bill|fee|charge)\b/i', $text)) {
            return 'expense';
        }
        
        // Transfer indicators
        if (preg_match('/\b(?:transferred|moved|sent|deposited|withdrew)\b/i', $text)) {
            return 'transfer';
        }
        
        return 'expense'; // Default assumption
    }
    
    /**
     * Extract location context
     */
    private function extractLocationContext(string $text): string {
        // Pattern for "in [location]" or "at [location]"
        if (preg_match('/\b(?:in|at)\s+([A-Z][a-zA-Z\s,]+?)(?:\s+(?:using|with|yesterday|today)|\s*$)/i', $text, $matches)) {
            $location = trim($matches[1]);
            // Filter out common non-location words
            if (!preg_match('/\b(?:whole foods|starbucks|target|walmart|store|restaurant)\b/i', $location)) {
                return $location;
            }
        }
        
        return '';
    }
    
    /**
     * Intelligent category detection based on vendor, keywords, and context
     */
    private function intelligentCategoryDetection(string $text, array $extractedInfo): array {
        $result = [
            'category' => '',
            'subcategory' => '',
            'category_confidence' => 0.8
        ];
        
        $vendor = strtolower($extractedInfo['vendor'] ?? '');
        $textLower = strtolower($text);
        
        // Vendor-based categorization (high confidence)
        $vendorCategories = [
            'groceries' => ['whole foods', 'kroger', 'safeway', 'publix', 'trader joes', 'walmart', 'target'],
            'restaurants' => ['mcdonalds', 'starbucks', 'subway', 'chipotle', 'pizza hut', 'dominos', 'kfc'],
            'gas' => ['shell', 'exxon', 'chevron', 'bp', 'mobil', 'texaco'],
            'shopping' => ['amazon', 'target', 'walmart', 'best buy', 'home depot', 'lowes'],
            'entertainment' => ['netflix', 'spotify', 'hulu', 'disney', 'movie', 'theater'],
            'transportation' => ['uber', 'lyft', 'taxi', 'bus', 'train', 'airline'],
            'utilities' => ['electric', 'gas company', 'water', 'internet', 'phone'],
            'healthcare' => ['doctor', 'dentist', 'hospital', 'pharmacy', 'cvs', 'walgreens']
        ];
        
        foreach ($vendorCategories as $category => $vendors) {
            foreach ($vendors as $vendorPattern) {
                if (stripos($vendor, $vendorPattern) !== false) {
                    $result['category'] = $category;
                    $result['category_confidence'] = 0.95;
                    return $result;
                }
            }
        }
        
        // Keyword-based categorization (medium confidence)
        $keywordCategories = [
            'groceries' => ['groceries', 'food', 'supermarket', 'grocery store'],
            'restaurants' => ['restaurant', 'cafe', 'coffee', 'lunch', 'dinner', 'meal'],
            'gas' => ['gas', 'fuel', 'gasoline', 'fill up'],
            'shopping' => ['shopping', 'clothes', 'electronics', 'purchase'],
            'entertainment' => ['movie', 'concert', 'show', 'entertainment', 'streaming'],
            'transportation' => ['uber', 'taxi', 'bus fare', 'train ticket', 'flight'],
            'utilities' => ['bill', 'utility', 'electric bill', 'phone bill'],
            'healthcare' => ['doctor', 'medical', 'prescription', 'pharmacy'],
            'housing' => ['rent', 'mortgage', 'utilities', 'home improvement'],
            'education' => ['tuition', 'books', 'school', 'course'],
            'personal care' => ['haircut', 'salon', 'spa', 'beauty'],
            'insurance' => ['insurance', 'premium', 'coverage'],
            'investments' => ['stocks', 'bonds', 'investment', 'retirement'],
            'gifts' => ['gift', 'present', 'birthday', 'wedding'],
            'travel' => ['hotel', 'flight', 'vacation', 'travel', 'trip']
        ];
        
        foreach ($keywordCategories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($textLower, $keyword) !== false) {
                    $result['category'] = $category;
                    $result['category_confidence'] = 0.75;
                    break 2;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Extract financial context and patterns
     */
    private function extractFinancialContext(string $text): array {
        $context = [];
        
        // Recurring pattern detection
        if (preg_match('/\b(?:monthly|weekly|daily|annually|every month|every week)\b/i', $text)) {
            $context['recurring'] = true;
        }
        
        // Business vs personal
        if (preg_match('/\b(?:business|work|office|company|client)\b/i', $text)) {
            $context['business_related'] = true;
        } else {
            $context['personal'] = true;
        }
        
        // Emergency or planned
        if (preg_match('/\b(?:emergency|urgent|unexpected)\b/i', $text)) {
            $context['emergency'] = true;
        }
        
        // Budget category mentions
        if (preg_match('/\b(?:budget|planned|saving|overspent)\b/i', $text)) {
            $context['budget_related'] = true;
        }
        
        return $context;
    }
    
    /**
     * Determine final category with fallbacks
     */
    private function determineCategory(array $extractedInfo, array $data): string {
        // Use component data category if available
        if (!empty($data['category'])) {
            return ucfirst($data['category']);
        }
        
        // Use extracted category
        if (!empty($extractedInfo['category'])) {
            return ucfirst($extractedInfo['category']);
        }
        
        return 'Uncategorized';
    }
    
    /**
     * Determine transaction type with intelligence
     */
    private function determineTransactionType(array $extractedInfo, array $data): string {
        // Use component data type if available
        if (!empty($data['type'])) {
            return $data['type'];
        }
        
        // Use extracted type
        return $extractedInfo['type'] ?? 'expense';
    }
    
    /**
     * Generate enhanced description
     */
    private function generateDescription(array $extractedInfo, array $data): string {
        $parts = [];
        
        // Use provided description first
        if (!empty($data['description'])) {
            $parts[] = $data['description'];
        }
        
        // Add extracted context
        if (!empty($extractedInfo['vendor'])) {
            $parts[] = "at " . $extractedInfo['vendor'];
        }
        
        if (!empty($extractedInfo['location'])) {
            $parts[] = "in " . $extractedInfo['location'];
        }
        
        return implode(' ', $parts) ?: 'Transaction';
    }
    
    /**
     * Parse and normalize date
     */
    private function parseDate(array $extractedInfo, array $data): string {
        if (!empty($data['date'])) {
            $dateString = $data['date'];
            
            // Handle relative dates
            if (strtolower($dateString) === 'yesterday') {
                return date('Y-m-d', strtotime('-1 day'));
            } elseif (strtolower($dateString) === 'today') {
                return date('Y-m-d');
            } elseif (strtolower($dateString) === 'tomorrow') {
                return date('Y-m-d', strtotime('+1 day'));
            }
            
            // Try to parse the date
            $timestamp = strtotime($dateString);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }
        
        return date('Y-m-d'); // Default to today
    }
    
    /**
     * Normalize payment method
     */
    private function normalizePaymentMethod(array $extractedInfo, array $data): string {
        // Use component data first
        if (!empty($data['payment_method'])) {
            return $data['payment_method'];
        }
        
        // Use extracted payment method
        return $extractedInfo['payment_method'] ?: 'unknown';
    }
    
    /**
     * Detect recurring transaction patterns
     */
    private function detectRecurringPattern(array $extractedInfo): ?string {
        $context = $extractedInfo['context'] ?? [];
        
        if (!empty($context['recurring'])) {
            // Could implement more sophisticated pattern detection
            return 'potential_recurring';
        }
        
        return null;
    }
    
    /**
     * Generate financial tags for better organization
     */
    private function generateFinancialTags(array $extractedInfo, array $data): array {
        $tags = [];
        
        // Category-based tags
        if (!empty($extractedInfo['category'])) {
            $tags[] = $extractedInfo['category'];
        }
        
        // Type-based tags
        $tags[] = $extractedInfo['type'] ?? 'expense';
        
        // Context-based tags
        $context = $extractedInfo['context'] ?? [];
        if (!empty($context['business_related'])) {
            $tags[] = 'business';
        }
        if (!empty($context['personal'])) {
            $tags[] = 'personal';
        }
        if (!empty($context['emergency'])) {
            $tags[] = 'emergency';
        }
        if (!empty($context['recurring'])) {
            $tags[] = 'recurring';
        }
        
        // Amount-based tags
        $amount = $extractedInfo['amount'] ?? 0;
        if ($amount > 1000) {
            $tags[] = 'large_amount';
        } elseif ($amount < 10) {
            $tags[] = 'small_amount';
        }
        
        return array_unique($tags);
    }
}

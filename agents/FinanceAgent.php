<?php
/**
 * FinanceAgent - Enhanced Financial Data Component Creator
 * 
 * This agent specializes in processing financial information with intelligent
 * parsing, category detection, and financial analytics. It handles expenses,
 * income, transfers, and other monetary transactions with smart categorization
 * and trend analysis.
 * 
 * @author AI Personal Assistant Team
 * @version 1.1
 * @since 2025-08-07
 */
class FinanceAgent {
    
    /**
     * Create a financial component from provided data
     * Processes financial information with enhanced parsing and analytics
     * 
     * @param array $data Raw financial data from the triage system
     * @return array Standardized financial component with enhanced analytics
     */
    public function createComponent(array $data): array {
        // Extract enhanced financial information from triage context
        $extractedInfo = $this->extractFinancialInformation($data);
        
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
    
    /**
     * Extract enhanced financial information from triage data and natural language
     * Parses amounts, currencies, vendors, categories, and payment methods
     * 
     * @param array $data Complete data from triage system
     * @return array Enhanced financial information
     */
    private function extractFinancialInformation(array $data): array {
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
        
        // Get text to analyze
        $triageSummary = $data['triage_summary'] ?? '';
        $originalQuery = $data['original_query'] ?? '';
        $analysisText = trim($triageSummary . ' ' . $originalQuery);
        $extracted['original_text'] = $analysisText;
        
        if ($analysisText) {
            // Extract monetary amounts and currencies
            $amountInfo = $this->parseAmount($analysisText);
            $extracted = array_merge($extracted, $amountInfo);
            
            // Extract vendor/merchant information
            $extracted['vendor'] = $this->extractVendor($analysisText, $data);
            
            // Extract payment method
            $extracted['payment_method'] = $this->extractPaymentMethod($analysisText);
            
            // Detect transaction type (income vs expense)
            $extracted['type'] = $this->detectTransactionType($analysisText);
            
            // Extract location context
            $extracted['location'] = $this->extractLocationContext($analysisText);
            
            // Intelligent category detection
            $categoryInfo = $this->intelligentCategoryDetection($analysisText, $extracted);
            $extracted = array_merge($extracted, $categoryInfo);
            
            // Extract financial context and patterns
            $extracted['context'] = $this->extractFinancialContext($analysisText);
            
            // Store parsing details for debugging/improvement
            $extracted['parsing_details'] = [
                'amount_detected' => !empty($amountInfo['amount']),
                'vendor_detected' => !empty($extracted['vendor']),
                'category_detected' => !empty($extracted['category']),
                'payment_method_detected' => !empty($extracted['payment_method'])
            ];
        }
        
        return $extracted;
    }
    
    /**
     * Parse monetary amounts and currencies from text
     */
    private function parseAmount(string $text): array {
        $result = [
            'amount' => null,
            'currency' => 'USD',
            'amount_context' => []
        ];
        
        // Pattern for various currency formats
        $patterns = [
            // $45.50, $1,234.56
            '/\$(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'USD',
            // €45.50, €1,234.56
            '/€(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'EUR',
            // £45.50, £1,234.56
            '/£(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'GBP',
            // 45.50 USD, 1234.56 dollars
            '/(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\s*(?:USD|dollars?|bucks?)\b/i' => 'USD',
            // Plain numbers when context suggests money
            '/\b(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)\b/' => 'USD' // fallback
        ];
        
        foreach ($patterns as $pattern => $currency) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace(',', '', $matches[1]);
                $result['amount'] = (float)$amount;
                $result['currency'] = $currency;
                $result['amount_context']['pattern_matched'] = $pattern;
                break; // Use first match
            }
        }
        
        return $result;
    }
    
    /**
     * Extract vendor/merchant information
     */
    private function extractVendor(string $text, array $data): string {
        // Check component data first
        if (!empty($data['vendor'])) {
            return $data['vendor'];
        }
        
        // Pattern matching for "at [vendor]"
        if (preg_match('/\b(?:at|from|to)\s+([A-Z][a-zA-Z\s&.\']+?)(?:\s+(?:using|with|yesterday|today|on)|\s*$)/i', $text, $matches)) {
            return trim($matches[1]);
        }
        
        // Pattern for well-known stores/restaurants
        $knownVendors = [
            'amazon', 'walmart', 'target', 'costco', 'whole foods', 'starbucks',
            'mcdonalds', 'subway', 'chipotle', 'uber', 'lyft', 'netflix',
            'spotify', 'apple', 'google', 'microsoft', 'tesla', 'shell',
            'exxon', 'chevron', 'home depot', 'lowes', 'best buy'
        ];
        
        foreach ($knownVendors as $vendor) {
            if (preg_match('/\b' . preg_quote($vendor) . '\b/i', $text)) {
                return ucwords($vendor);
            }
        }
        
        return '';
    }
    
    /**
     * Extract payment method information
     */
    private function extractPaymentMethod(string $text): string {
        $paymentMethods = [
            'credit card' => '/\b(?:credit card|card|visa|mastercard|amex|american express)\b/i',
            'debit card' => '/\b(?:debit card|debit)\b/i',
            'cash' => '/\b(?:cash|bills?|coins?)\b/i',
            'check' => '/\b(?:check|cheque)\b/i',
            'paypal' => '/\b(?:paypal|pay pal)\b/i',
            'venmo' => '/\b(?:venmo)\b/i',
            'apple pay' => '/\b(?:apple pay|applepay)\b/i',
            'google pay' => '/\b(?:google pay|googlepay|gpay)\b/i',
            'bank transfer' => '/\b(?:bank transfer|wire transfer|ach)\b/i'
        ];
        
        foreach ($paymentMethods as $method => $pattern) {
            if (preg_match($pattern, $text)) {
                return $method;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Detect transaction type (income vs expense)
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

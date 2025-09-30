<?php namespace ProcessWire;

/**
 * Anti-Spam Configuration File
 * 
 * Central configuration for contact form anti-spam settings.
 * Include this file in your AJAX handler and templates for consistent settings.
 * 
 * Usage:
 *   require_once(__DIR__ . '/antispam-config.php');
 *   $config = getContactAntiSpamConfig();
 */

/**
 * Get anti-spam configuration
 * 
 * @return array Configuration array
 */
function getContactAntiSpamConfig() {
    return [
        // ===================================================================
        // VALIDATION RULES
        // ===================================================================
        
        // Message validation
        'min_sentences' => 3,              // Minimum sentences required
        'min_message_length' => 30,        // Minimum characters in message
        'max_message_length' => 4000,      // Maximum characters in message
        
        // Name validation
        'min_name_length' => 2,            // Minimum characters in name
        'max_name_length' => 80,           // Maximum characters in name
        'vowel_ratio_threshold' => 0.2,    // Minimum vowel ratio (20%)
        'max_consonant_run' => 4,          // Max consecutive consonants
        
        // Email validation
        'check_mx_records' => true,        // Verify email domain has MX records
        'check_a_records' => true,         // Fallback to A records if no MX
        
        // ===================================================================
        // RATE LIMITING & COOLDOWNS
        // ===================================================================
        
        'name_cooldown_days' => 30,        // Days before same name can submit again
        'rate_limit_minutes' => 5,         // Minutes between submissions (same IP/email)
        'min_form_fill_time' => 3,         // Minimum seconds to fill form (bot detection)
        
        // ===================================================================
        // RECAPTCHA SETTINGS
        // ===================================================================
        
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => '6LcZRT4rAAAAANpfPst5iQls8giUv8-1FdsllfgJ',  // CHANGE THIS
        'recaptcha_secret' => 'YOUR_SECRET_KEY_HERE',                          // CHANGE THIS
        'recaptcha_version' => 2,          // 2 or 3
        'recaptcha_score_threshold' => 0.5, // For v3 only (0.0-1.0)
        
        // ===================================================================
        // BLOCKED DOMAINS
        // ===================================================================
        
        'blocked_domains' => [
            // Obvious fake/test domains
            'example.com',
            'test.com',
            'testing.com',
            'fake.com',
            'spam.com',
            
            // Temporary/disposable email services
            'tempmail.com',
            'guerrillamail.com',
            'guerrillamailblock.com',
            'mailinator.com',
            'maildrop.cc',
            '10minutemail.com',
            '10minutemail.net',
            'trashmail.com',
            'throwaway.email',
            'getnada.com',
            'temp-mail.org',
            'yopmail.com',
            'fakeinbox.com',
            'discard.email',
            'emailondeck.com',
            'spambox.us',
            'mintemail.com',
            'mytemp.email',
            'mohmal.com',
            'emailfake.com',
            
            // Add your custom blocked domains here
            // 'your-blocked-domain.com',
        ],
        
        // ===================================================================
        // BLOCKED EMAIL ADDRESSES (Exact match, case-insensitive)
        // ===================================================================
        
        'blocked_emails' => [
            'test@example.com',
            'testing@test.com',
            'admin@test.com',
            'noreply@example.com',
            'fake@fake.com',
            
            // Add specific email addresses to block
        ],
        
        // ===================================================================
        // DANGEROUS PATTERNS (SQL Injection & RCE Detection)
        // ===================================================================
        
        'dangerous_patterns' => [
            // SQL Injection patterns
            '/select\s*\(/i',
            '/insert\s+into/i',
            '/update\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/union\s+select/i',
            '/or\s+1\s*=\s*1/i',
            '/and\s+1\s*=\s*1/i',
            '/sleep\s*\(/i',
            '/benchmark\s*\(/i',
            '/waitfor\s+delay/i',
            '/information_schema/i',
            '/load_file\s*\(/i',
            '/into\s+outfile/i',
            '/into\s+dumpfile/i',
            '/;[\s]*--/i',
            '/--[\s]/i',
            '/#[\s]/i',
            '/\/\*/i',
            '/\*\//i',
            '/0x[0-9a-f]+/i',
            '/char\s*\(/i',
            '/concat\s*\(/i',
            '/group_concat/i',
            '/[\'\"]\s*\+\s*[\'\"]/i',
            '/[\'\"]\s*\|\|\s*[\'\"]/i',
            '/xp_cmdshell/i',
            
            // Code injection patterns
            '/\$\{/i',
            '/<\?php/i',
            '/<\?=/i',
            '/<script/i',
            '/javascript:/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/base64_decode\s*\(/i',
            '/gzinflate\s*\(/i',
            '/str_rot13\s*\(/i',
            '/assert\s*\(/i',
            '/create_function\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/file_put_contents\s*\(/i',
            '/fopen\s*\(/i',
            '/readfile\s*\(/i',
            '/include\s*\(/i',
            '/require\s*\(/i',
            
            // Shell injection
            '/\|\|/i',
            '/&&/i',
            '/;\s*\w+/i',
            '/`.*`/i',
            '/\$\(.*\)/i',
        ],
        
        // ===================================================================
        // TURKISH LANGUAGE SUPPORT
        // ===================================================================
        
        'turkish_vowels' => 'aeıioöuüAEIİOÖUÜ',
        'turkish_consonants' => 'bcçdfgğhjklmnprsştvyzBCÇDFGĞHJKLMNPRSŞTVYZ',
        
        // ===================================================================
        // LOGGING SETTINGS
        // ===================================================================
        
        'log_spam_attempts' => true,       // Log all blocked submissions
        'log_successful_submissions' => true, // Log successful submissions
        'log_admin_actions' => true,       // Log block/unblock actions
        'log_errors' => true,              // Log errors
        
        'log_names' => [
            'spam' => 'contact-spam',
            'success' => 'contact-submissions',
            'admin' => 'contact-admin',
            'error' => 'contact-errors',
        ],
        
        // ===================================================================
        // EMAIL NOTIFICATION SETTINGS
        // ===================================================================
        
        'send_admin_notification' => true,
        'admin_email' => 'admin@example.com',  // CHANGE THIS
        'notification_subject' => 'New Contact Form Submission',
        'notification_from_email' => 'noreply@yourdomain.com', // CHANGE THIS
        
        // ===================================================================
        // HONEYPOT SETTINGS
        // ===================================================================
        
        'honeypot_enabled' => true,
        'honeypot_fields' => [
            'website',      // Hidden text field
            'company',      // Hidden text field (position absolute)
            'url',          // Alternative name
            'phone_alt',    // Alternative name
        ],
        
        // ===================================================================
        // DATABASE SETTINGS
        // ===================================================================
        
        'table_submissions' => 'contact_submissions',
        'table_blocked_names' => 'blocked_names',
        
        // ===================================================================
        // ADVANCED SETTINGS
        // ===================================================================
        
        'strict_mode' => false,            // Extra strict validation (may cause false positives)
        'auto_block_threshold' => 5,       // Auto-block name after N spam attempts
        'enable_ip_blocking' => false,     // Enable IP-based blocking (requires additional table)
        'max_submissions_per_ip_per_day' => 10,
        
        // Trust client validation (skip some server checks if client validated)
        'trust_client_validation' => false,
        
        // ===================================================================
        // FEATURE FLAGS
        // ===================================================================
        
        'features' => [
            'name_cooldown' => true,
            'rate_limiting' => true,
            'honeypot' => true,
            'recaptcha' => true,
            'mx_validation' => true,
            'pattern_detection' => true,
            'blocked_names' => true,
            'auto_blocking' => false,
        ],
    ];
}

/**
 * Get JavaScript configuration for client-side validation
 * 
 * @return string JavaScript object literal
 */
function getContactAntiSpamConfigJS() {
    $config = getContactAntiSpamConfig();
    
    // Build client-safe config (no secrets)
    $jsConfig = [
        'minSentences' => $config['min_sentences'],
        'minMessageLength' => $config['min_message_length'],
        'maxMessageLength' => $config['max_message_length'],
        'minNameLength' => $config['min_name_length'],
        'maxNameLength' => $config['max_name_length'],
        'rateLimitMinutes' => $config['rate_limit_minutes'],
        'nameCooldownDays' => $config['name_cooldown_days'],
        'blockedDomains' => $config['blocked_domains'],
        'vowels' => $config['turkish_vowels'],
        'recaptchaSiteKey' => $config['recaptcha_site_key'],
    ];
    
    return json_encode($jsConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Get pattern matching function for dangerous content
 * 
 * @param string $str String to check
 * @return bool True if dangerous pattern found
 */
function hasDangerousPattern($str) {
    $config = getContactAntiSpamConfig();
    
    foreach ($config['dangerous_patterns'] as $pattern) {
        if (preg_match($pattern, $str)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if email domain is blocked
 * 
 * @param string $email Email address
 * @return bool True if blocked
 */
function isBlockedEmailDomain($email) {
    $config = getContactAntiSpamConfig();
    
    $email = strtolower(trim($email));
    
    // Check exact email match
    if (in_array($email, $config['blocked_emails'])) {
        return true;
    }
    
    // Extract domain
    if (strpos($email, '@') === false) {
        return false;
    }
    
    $domain = explode('@', $email)[1];
    
    return in_array($domain, $config['blocked_domains']);
}

/**
 * Validate email MX records
 * 
 * @param string $email Email address
 * @return bool True if valid
 */
function validateEmailMX($email) {
    $config = getContactAntiSpamConfig();
    
    if (!$config['check_mx_records']) {
        return true; // Skip if disabled
    }
    
    $domain = explode('@', $email)[1] ?? '';
    
    if (empty($domain)) {
        return false;
    }
    
    // Check MX records
    if (checkdnsrr($domain, 'MX')) {
        return true;
    }
    
    // Fallback to A records if enabled
    if ($config['check_a_records'] && checkdnsrr($domain, 'A')) {
        return true;
    }
    
    return false;
}

// Export for use in templates
if (defined('PROCESSWIRE')) {
    // Make available as ProcessWire variable
    wire('config')->contactAntiSpam = getContactAntiSpamConfig();
}
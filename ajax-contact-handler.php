<?php namespace ProcessWire;

/**
 * Advanced Anti-Spam Contact Form Handler for ProcessWire
 * 
 * This file should be included in your AJAX handler or templates/ajax.php
 * 
 * Database tables needed:
 * - contact_submissions: Stores all submissions with anti-spam metadata
 * - blocked_names: Stores blocked names/patterns
 * 
 * Usage in ajax.php:
 * if($input->get('action') === 'contact') {
 *     require_once('./ajax-contact-handler.php');
 *     exit;
 * }
 */

// Anti-spam configuration
$antispamConfig = [
    'min_sentences' => 3,
    'min_message_length' => 30,
    'max_message_length' => 4000,
    'min_name_length' => 2,
    'max_name_length' => 80,
    'name_cooldown_days' => 30,
    'rate_limit_minutes' => 5,
    'recaptcha_secret' => '6LcZRT4rAAAAAJ_YOUR_SECRET_KEY_HERE', // Replace with your actual secret
    
    // Dangerous patterns for SQL injection and RCE detection
    'dangerous_patterns' => [
        '/select\s*\(/i',
        '/sleep\s*\(/i',
        '/union\s+select/i',
        '/or\s+1\s*=\s*1/i',
        '/information_schema/i',
        '/load_file/i',
        '/outfile/i',
        '/;\s*--/i',
        '/--\s/i',
        '/\/\*/i',
        '/\*\//i',
        '/\$\{/i',
        '/<\?php/i',
        '/<script/i',
        '/javascript:/i',
        '/eval\s*\(/i',
        '/exec\s*\(/i',
        '/[\'\"]\s*\+\s*[\'\"]/i',
        '/[\'\"]\s*\|\|\s*[\'\"]/i',
        '/base64_decode/i',
        '/system\s*\(/i',
        '/passthru/i',
        '/shell_exec/i',
    ],
    
    'blocked_domains' => [
        'example.com', 'test.com', 'testing.com', 'fake.com', 'spam.com',
        'tempmail.com', 'guerrillamail.com', 'mailinator.com', '10minutemail.com',
        'trashmail.com', 'yopmail.com', 'throwaway.email', 'getnada.com'
    ],
    
    'turkish_vowels' => 'aeıioöuüAEIİOÖUÜ'
];

/**
 * Utility class for anti-spam validation
 */
class ContactAntiSpam {
    private $config;
    private $db;
    private $log;
    
    public function __construct($config, $database) {
        $this->config = $config;
        $this->db = $database;
        $this->log = wire('log');
    }
    
    /**
     * Normalize string (trim, collapse whitespace, NFC normalization)
     */
    public function normalize($str) {
        $str = trim($str);
        $str = preg_replace('/\s+/', ' ', $str);
        if (function_exists('normalizer_normalize')) {
            $str = normalizer_normalize($str, Normalizer::FORM_C);
        }
        return $str;
    }
    
    /**
     * Check if string contains dangerous patterns
     */
    public function hasDangerousPattern($str) {
        foreach ($this->config['dangerous_patterns'] as $pattern) {
            if (preg_match($pattern, $str)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Count sentences in text
     */
    public function countSentences($text) {
        $normalized = $this->normalize($text);
        $segments = preg_split('/[.!?]+/', $normalized);
        $validSegments = array_filter($segments, function($s) {
            return mb_strlen(trim($s)) >= 2;
        });
        return count($validSegments);
    }
    
    /**
     * Validate human-like name
     */
    public function validateName($name) {
        $normalized = $this->normalize($name);
        
        // Length check
        if (mb_strlen($normalized) < $this->config['min_name_length'] || 
            mb_strlen($normalized) > $this->config['max_name_length']) {
            return ['valid' => false, 'reason' => 'Name length invalid (2-80 characters required)'];
        }
        
        // Only allow letters, spaces, apostrophes, periods, hyphens
        if (!preg_match('/^[\p{L}\s\'\.\-]+$/u', $normalized)) {
            return ['valid' => false, 'reason' => 'Name contains invalid characters'];
        }
        
        // Check for digits
        if (preg_match('/\d/', $normalized)) {
            return ['valid' => false, 'reason' => 'Name cannot contain numbers'];
        }
        
        // Check for suspicious patterns (all uppercase random letters)
        if (preg_match('/^[A-Z]{6,}$/', str_replace(' ', '', $normalized))) {
            return ['valid' => false, 'reason' => 'Please use your real name'];
        }
        
        // Check for gibberish (low vowel ratio)
        $nameWithoutSpaces = preg_replace('/[\s\'\.\-]/', '', $normalized);
        if (mb_strlen($nameWithoutSpaces) >= 6) {
            $vowelCount = 0;
            $vowels = $this->config['turkish_vowels'];
            for ($i = 0; $i < mb_strlen($nameWithoutSpaces); $i++) {
                $char = mb_substr($nameWithoutSpaces, $i, 1);
                if (mb_strpos($vowels, $char) !== false) {
                    $vowelCount++;
                }
            }
            
            $vowelRatio = $vowelCount / mb_strlen($nameWithoutSpaces);
            
            if ($vowelRatio < 0.2) {
                return ['valid' => false, 'reason' => 'Please use your real name (invalid name format)'];
            }
            
            // Check for excessive consonant runs
            if (preg_match('/[^aeıioöuüAEIİOÖUÜ\s]{5,}/', $nameWithoutSpaces)) {
                return ['valid' => false, 'reason' => 'Please use your real name'];
            }
        }
        
        return ['valid' => true, 'normalized' => $normalized];
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        $normalized = strtolower(trim($email));
        
        // Basic format check
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'reason' => 'Invalid email format'];
        }
        
        // Check for dangerous patterns
        if ($this->hasDangerousPattern($normalized)) {
            return ['valid' => false, 'reason' => 'Email contains invalid characters'];
        }
        
        // Extract domain
        $parts = explode('@', $normalized);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Invalid email format'];
        }
        
        $domain = $parts[1];
        
        // Check blocked domains
        if (in_array($domain, $this->config['blocked_domains'])) {
            return ['valid' => false, 'reason' => 'This email domain is not accepted. Please use your real email'];
        }
        
        // Check for common typos
        if (strpos($domain, '..') !== false || $domain[0] === '.' || substr($domain, -1) === '.') {
            return ['valid' => false, 'reason' => 'Invalid email domain'];
        }
        
        // Optional: DNS MX record check
        if (function_exists('checkdnsrr')) {
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                return ['valid' => false, 'reason' => 'Email domain does not exist or has no mail server'];
            }
        }
        
        return ['valid' => true, 'normalized' => $normalized];
    }
    
    /**
     * Validate message
     */
    public function validateMessage($message) {
        $normalized = $this->normalize($message);
        
        // Length check
        if (mb_strlen($normalized) < $this->config['min_message_length']) {
            return ['valid' => false, 'reason' => 'Message is too short. Minimum ' . $this->config['min_message_length'] . ' characters and 3 sentences required'];
        }
        
        if (mb_strlen($normalized) > $this->config['max_message_length']) {
            return ['valid' => false, 'reason' => 'Message is too long'];
        }
        
        // Check for dangerous patterns
        if ($this->hasDangerousPattern($normalized)) {
            return ['valid' => false, 'reason' => 'Message contains invalid content'];
        }
        
        // Count sentences
        $sentenceCount = $this->countSentences($normalized);
        if ($sentenceCount < $this->config['min_sentences']) {
            return ['valid' => false, 'reason' => 'Please write at least ' . $this->config['min_sentences'] . ' sentences. Current: ' . $sentenceCount];
        }
        
        // Check if message is just numbers
        if (preg_match('/^\d+$/', $normalized)) {
            return ['valid' => false, 'reason' => 'Message cannot be only numbers'];
        }
        
        // Check for repetitive content
        if (preg_match('/^(.)\1{20,}$/', str_replace(' ', '', $normalized))) {
            return ['valid' => false, 'reason' => 'Invalid message content'];
        }
        
        return ['valid' => true, 'normalized' => $normalized];
    }
    
    /**
     * Check if name was used recently (30-day cooldown)
     */
    public function checkNameCooldown($name) {
        $normalized = mb_strtolower($this->normalize($name));
        $cooldownSeconds = $this->config['name_cooldown_days'] * 24 * 60 * 60;
        $cutoffDate = date('Y-m-d H:i:s', time() - $cooldownSeconds);
        
        $query = $this->db->prepare(
            "SELECT created_at FROM contact_submissions 
             WHERE LOWER(name) = ? AND created_at > ? 
             LIMIT 1"
        );
        $query->execute([$normalized, $cutoffDate]);
        
        if ($query->rowCount() > 0) {
            $row = $query->fetch();
            $lastUsed = strtotime($row['created_at']);
            $daysRemaining = ceil(($cooldownSeconds - (time() - $lastUsed)) / (24 * 60 * 60));
            
            return [
                'allowed' => false, 
                'reason' => "This name was recently used to submit a message. Please use your real full name. ({$daysRemaining} days remaining)"
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check if name is in blocked list
     */
    public function checkBlockedName($name) {
        $normalized = mb_strtolower($this->normalize($name));
        
        $query = $this->db->prepare(
            "SELECT reason FROM blocked_names WHERE LOWER(name) = ? LIMIT 1"
        );
        $query->execute([$normalized]);
        
        if ($query->rowCount() > 0) {
            $row = $query->fetch();
            return [
                'allowed' => false,
                'reason' => 'This name is blocked: ' . ($row['reason'] ?: 'Spam prevention')
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check IP-based rate limiting
     */
    public function checkRateLimit($ip, $email) {
        $limitSeconds = $this->config['rate_limit_minutes'] * 60;
        $cutoffDate = date('Y-m-d H:i:s', time() - $limitSeconds);
        
        // Check by IP + email combination
        $query = $this->db->prepare(
            "SELECT created_at FROM contact_submissions 
             WHERE (ip_address = ? OR email = ?) AND created_at > ? 
             ORDER BY created_at DESC LIMIT 1"
        );
        $query->execute([$ip, $email, $cutoffDate]);
        
        if ($query->rowCount() > 0) {
            $row = $query->fetch();
            $lastSubmit = strtotime($row['created_at']);
            $minutesRemaining = ceil(($limitSeconds - (time() - $lastSubmit)) / 60);
            
            return [
                'allowed' => false,
                'reason' => "Please wait {$minutesRemaining} minutes before submitting again"
            ];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Verify reCAPTCHA
     */
    public function verifyRecaptcha($response, $remoteIp) {
        if (empty($response)) {
            return ['valid' => false, 'reason' => 'reCAPTCHA verification required'];
        }
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->config['recaptcha_secret'],
            'response' => $response,
            'remoteip' => $remoteIp
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            $this->log->save('contact-errors', 'reCAPTCHA verification failed - network error');
            return ['valid' => false, 'reason' => 'reCAPTCHA verification failed'];
        }
        
        $resultJson = json_decode($result, true);
        
        if (!$resultJson['success']) {
            $this->log->save('contact-errors', 'reCAPTCHA verification failed: ' . json_encode($resultJson));
            return ['valid' => false, 'reason' => 'reCAPTCHA verification failed'];
        }
        
        // Optional: Check score for v3 (if using v3, scores below 0.5 are suspicious)
        // if (isset($resultJson['score']) && $resultJson['score'] < 0.5) {
        //     return ['valid' => false, 'reason' => 'Suspicious activity detected'];
        // }
        
        return ['valid' => true];
    }
    
    /**
     * Save submission to database
     */
    public function saveSubmission($data) {
        $query = $this->db->prepare(
            "INSERT INTO contact_submissions 
             (name, email, phone, message, ip_address, user_agent, page_url, flags, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        $flags = json_encode([
            'client_validated' => $data['client_validated'] ?? false,
            'recaptcha_verified' => true,
            'submission_time' => time()
        ]);
        
        return $query->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['message'],
            $data['ip'],
            $data['user_agent'],
            $data['page_url'] ?? '',
            $flags
        ]);
    }
    
    /**
     * Log suspicious activity
     */
    public function logSuspicious($type, $data, $ip) {
        $this->log->save('contact-spam', sprintf(
            'Blocked %s: %s | IP: %s | Data: %s',
            $type,
            $data['reason'] ?? 'Unknown',
            $ip,
            json_encode($data)
        ));
    }
}

// ============================================================================
// MAIN HANDLER
// ============================================================================

header('Content-Type: application/json');

try {
    // Get ProcessWire database instance
    $database = wire('database');
    
    // Initialize anti-spam validator
    $antiSpam = new ContactAntiSpam($antispamConfig, $database);
    
    // Get client IP
    $clientIp = wire('session')->getIP();
    
    // Get POST data
    $name = wire('input')->post('name', 'text');
    $email = wire('input')->post('email', 'email');
    $phone = wire('input')->post('phone', 'text');
    $message = wire('input')->post('message', 'textarea');
    $pageUrl = wire('input')->post('page', 'text');
    $website = wire('input')->post('website', 'text');
    $company = wire('input')->post('company', 'text');
    $formTimestamp = wire('input')->post('form_timestamp', 'int');
    $recaptchaResponse = wire('input')->post('g-recaptcha-response', 'text');
    $clientValidated = wire('input')->post('client_validated', 'int');
    
    // HONEYPOT CHECK
    if (!empty($website) || !empty($company)) {
        $antiSpam->logSuspicious('honeypot', ['website' => $website, 'company' => $company], $clientIp);
        echo json_encode(['success' => false, 'message' => 'Invalid submission']);
        exit;
    }
    
    // TIME-BASED CHECK (form filled too quickly - under 3 seconds is suspicious)
    if ($formTimestamp > 0) {
        $timeDiff = time() - $formTimestamp;
        if ($timeDiff < 3) {
            $antiSpam->logSuspicious('too_fast', ['time_diff' => $timeDiff], $clientIp);
            echo json_encode(['success' => false, 'message' => 'Please take more time to fill out the form']);
            exit;
        }
    }
    
    // VALIDATE NAME
    $nameValidation = $antiSpam->validateName($name);
    if (!$nameValidation['valid']) {
        $antiSpam->logSuspicious('invalid_name', ['name' => $name, 'reason' => $nameValidation['reason']], $clientIp);
        echo json_encode(['success' => false, 'message' => $nameValidation['reason']]);
        exit;
    }
    $name = $nameValidation['normalized'];
    
    // CHECK BLOCKED NAMES
    $blockedCheck = $antiSpam->checkBlockedName($name);
    if (!$blockedCheck['allowed']) {
        $antiSpam->logSuspicious('blocked_name', ['name' => $name, 'reason' => $blockedCheck['reason']], $clientIp);
        echo json_encode(['success' => false, 'message' => 'This name cannot be used']);
        exit;
    }
    
    // CHECK NAME COOLDOWN (30 days)
    $cooldownCheck = $antiSpam->checkNameCooldown($name);
    if (!$cooldownCheck['allowed']) {
        $antiSpam->logSuspicious('name_cooldown', ['name' => $name, 'reason' => $cooldownCheck['reason']], $clientIp);
        echo json_encode(['success' => false, 'message' => $cooldownCheck['reason']]);
        exit;
    }
    
    // VALIDATE EMAIL
    $emailValidation = $antiSpam->validateEmail($email);
    if (!$emailValidation['valid']) {
        $antiSpam->logSuspicious('invalid_email', ['email' => $email, 'reason' => $emailValidation['reason']], $clientIp);
        echo json_encode(['success' => false, 'message' => $emailValidation['reason']]);
        exit;
    }
    $email = $emailValidation['normalized'];
    
    // VALIDATE MESSAGE
    $messageValidation = $antiSpam->validateMessage($message);
    if (!$messageValidation['valid']) {
        $antiSpam->logSuspicious('invalid_message', ['message' => substr($message, 0, 100), 'reason' => $messageValidation['reason']], $clientIp);
        echo json_encode(['success' => false, 'message' => $messageValidation['reason']]);
        exit;
    }
    $message = $messageValidation['normalized'];
    
    // CHECK RATE LIMITING
    $rateLimitCheck = $antiSpam->checkRateLimit($clientIp, $email);
    if (!$rateLimitCheck['allowed']) {
        $antiSpam->logSuspicious('rate_limit', ['ip' => $clientIp, 'email' => $email], $clientIp);
        echo json_encode(['success' => false, 'message' => $rateLimitCheck['reason']]);
        exit;
    }
    
    // VERIFY reCAPTCHA
    $recaptchaCheck = $antiSpam->verifyRecaptcha($recaptchaResponse, $clientIp);
    if (!$recaptchaCheck['valid']) {
        $antiSpam->logSuspicious('recaptcha_failed', ['ip' => $clientIp], $clientIp);
        echo json_encode(['success' => false, 'message' => $recaptchaCheck['reason']]);
        exit;
    }
    
    // ALL VALIDATIONS PASSED - Save to database
    $saved = $antiSpam->saveSubmission([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'ip' => $clientIp,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'page_url' => $pageUrl,
        'client_validated' => $clientValidated
    ]);
    
    if ($saved) {
        // Optional: Send email notification
        $adminEmail = wire('config')->adminEmail ?? 'admin@example.com';
        $subject = 'New Contact Form Submission';
        $body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message\n\nIP: $clientIp";
        
        mail($adminEmail, $subject, $body, "From: $email\r\nReply-To: $email");
        
        // Log success
        wire('log')->save('contact-submissions', "New submission from: $name ($email)");
        
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save message. Please try again.']);
    }
    
} catch (Exception $e) {
    wire('log')->save('contact-errors', 'Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
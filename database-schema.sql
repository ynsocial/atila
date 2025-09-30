-- ============================================================================
-- ProcessWire Contact Form Anti-Spam Database Schema
-- ============================================================================
-- 
-- This schema creates two tables:
-- 1. contact_submissions: Stores all contact form submissions with anti-spam metadata
-- 2. blocked_names: Stores names that are permanently blocked from submitting
--
-- Install: Run this SQL in your ProcessWire database (usually via phpMyAdmin or CLI)
-- ============================================================================

-- Drop tables if they exist (CAUTION: removes all data!)
-- DROP TABLE IF EXISTS contact_submissions;
-- DROP TABLE IF EXISTS blocked_names;

-- ============================================================================
-- Table: contact_submissions
-- ============================================================================
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Form fields
    name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    message TEXT NOT NULL,
    
    -- Anti-spam metadata
    ip_address VARCHAR(45) NOT NULL COMMENT 'Client IP (supports IPv6)',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT 'Browser user agent',
    page_url VARCHAR(500) DEFAULT NULL COMMENT 'Page where form was submitted',
    
    -- Flags for tracking validation methods
    flags JSON DEFAULT NULL COMMENT 'JSON: client_validated, recaptcha_verified, etc.',
    
    -- Status tracking
    status ENUM('pending', 'read', 'replied', 'spam', 'deleted') DEFAULT 'pending',
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_name (name),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at),
    INDEX idx_status (status),
    
    -- Composite index for rate limiting queries
    INDEX idx_rate_limit (ip_address, email, created_at),
    
    -- Composite index for name cooldown queries
    INDEX idx_name_cooldown (name, created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Contact form submissions with anti-spam tracking';


-- ============================================================================
-- Table: blocked_names
-- ============================================================================
CREATE TABLE IF NOT EXISTS blocked_names (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Name to block (exact match, case-insensitive)
    name VARCHAR(80) NOT NULL UNIQUE,
    
    -- Reason for blocking
    reason VARCHAR(255) DEFAULT NULL,
    
    -- Who added this block (admin username or 'system')
    added_by VARCHAR(50) DEFAULT 'system',
    
    -- When blocked
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    
    -- Unique index on name (case-insensitive)
    UNIQUE INDEX idx_name_unique (name),
    
    INDEX idx_created (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Blocked names for spam prevention';


-- ============================================================================
-- Sample Data: Add some common spam names to blocked list
-- ============================================================================
INSERT INTO blocked_names (name, reason, added_by) VALUES
('JYupWMLW', 'Detected gibberish spam pattern', 'system'),
('test', 'Generic test name', 'system'),
('admin', 'Reserved name', 'system'),
('asdfgh', 'Common spam pattern', 'system'),
('qwerty', 'Common spam pattern', 'system'),
('xxxxxx', 'Common spam pattern', 'system')
ON DUPLICATE KEY UPDATE reason=VALUES(reason);


-- ============================================================================
-- Cleanup procedure: Delete old submissions (optional)
-- ============================================================================
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS cleanup_old_contact_submissions(IN days_old INT)
BEGIN
    DELETE FROM contact_submissions 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY)
    AND status IN ('spam', 'deleted');
    
    SELECT ROW_COUNT() as deleted_rows;
END$$

DELIMITER ;

-- Usage example:
-- CALL cleanup_old_contact_submissions(90);  -- Delete spam/deleted entries older than 90 days


-- ============================================================================
-- View: Recent submissions for monitoring
-- ============================================================================
CREATE OR REPLACE VIEW recent_contact_submissions AS
SELECT 
    id,
    name,
    email,
    phone,
    LEFT(message, 100) as message_preview,
    ip_address,
    status,
    created_at
FROM contact_submissions
ORDER BY created_at DESC
LIMIT 100;


-- ============================================================================
-- View: Spam statistics
-- ============================================================================
CREATE OR REPLACE VIEW contact_spam_stats AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_submissions,
    SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    COUNT(DISTINCT ip_address) as unique_ips
FROM contact_submissions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;


-- ============================================================================
-- Indexes for performance optimization
-- ============================================================================

-- If you have a large table, consider adding these additional indexes:
-- ALTER TABLE contact_submissions ADD INDEX idx_composite_search (status, created_at, email);
-- ALTER TABLE contact_submissions ADD INDEX idx_full_text_message FULLTEXT(message);


-- ============================================================================
-- Grant permissions (adjust username as needed)
-- ============================================================================
-- GRANT SELECT, INSERT, UPDATE, DELETE ON contact_submissions TO 'your_pw_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON blocked_names TO 'your_pw_user'@'localhost';


-- ============================================================================
-- Verification: Check that tables were created successfully
-- ============================================================================
SELECT 
    'contact_submissions' as table_name,
    COUNT(*) as row_count 
FROM contact_submissions
UNION ALL
SELECT 
    'blocked_names' as table_name,
    COUNT(*) as row_count 
FROM blocked_names;
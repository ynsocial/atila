<?php namespace ProcessWire;

/**
 * Blocked Names Management Script for ProcessWire
 * 
 * This script provides CLI and web-based management for blocked names.
 * 
 * CLI Usage (via ProcessWire wire CLI):
 *   php manage-blocked-names.php block "JYupWMLW" "spam burst"
 *   php manage-blocked-names.php unblock "JYupWMLW"
 *   php manage-blocked-names.php list
 *   php manage-blocked-names.php purge --days=90
 * 
 * Web Usage:
 *   Access via browser (PROTECTED - add authentication!)
 *   ?action=block&name=JYupWMLW&reason=spam
 *   ?action=unblock&name=JYupWMLW
 *   ?action=list
 *   ?action=purge&days=90
 */

// Include ProcessWire bootstrap if running standalone
if (!defined('PROCESSWIRE')) {
    require_once('./index.php');
}

/**
 * Blocked Names Manager Class
 */
class BlockedNamesManager {
    private $db;
    private $log;
    
    public function __construct() {
        $this->db = wire('database');
        $this->log = wire('log');
    }
    
    /**
     * Block a name
     */
    public function blockName($name, $reason = 'Manual block', $addedBy = 'admin') {
        $name = trim($name);
        
        if (empty($name)) {
            return ['success' => false, 'message' => 'Name cannot be empty'];
        }
        
        if (mb_strlen($name) > 80) {
            return ['success' => false, 'message' => 'Name too long (max 80 characters)'];
        }
        
        try {
            // Check if already blocked
            $query = $this->db->prepare("SELECT id FROM blocked_names WHERE LOWER(name) = LOWER(?)");
            $query->execute([$name]);
            
            if ($query->rowCount() > 0) {
                return ['success' => false, 'message' => "Name '{$name}' is already blocked"];
            }
            
            // Insert new block
            $stmt = $this->db->prepare(
                "INSERT INTO blocked_names (name, reason, added_by, created_at) VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$name, $reason, $addedBy]);
            
            $this->log->save('contact-admin', "Blocked name: {$name} | Reason: {$reason} | By: {$addedBy}");
            
            return ['success' => true, 'message' => "Successfully blocked: {$name}"];
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error blocking name: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Unblock a name
     */
    public function unblockName($name) {
        $name = trim($name);
        
        if (empty($name)) {
            return ['success' => false, 'message' => 'Name cannot be empty'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM blocked_names WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$name]);
            
            if ($stmt->rowCount() > 0) {
                $this->log->save('contact-admin', "Unblocked name: {$name}");
                return ['success' => true, 'message' => "Successfully unblocked: {$name}"];
            } else {
                return ['success' => false, 'message' => "Name '{$name}' was not blocked"];
            }
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error unblocking name: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * List all blocked names
     */
    public function listBlocked($limit = 100) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, reason, added_by, created_at 
                 FROM blocked_names 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $results, 'count' => count($results)];
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error listing blocked names: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Purge old submissions
     */
    public function purgeOldSubmissions($days = 90, $statusFilter = ['spam', 'deleted']) {
        try {
            $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
            
            $stmt = $this->db->prepare(
                "DELETE FROM contact_submissions 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                 AND status IN ({$placeholders})"
            );
            
            $params = array_merge([$days], $statusFilter);
            $stmt->execute($params);
            
            $deletedCount = $stmt->rowCount();
            
            $this->log->save('contact-admin', "Purged {$deletedCount} old submissions (>{$days} days old)");
            
            return ['success' => true, 'message' => "Purged {$deletedCount} old submissions", 'count' => $deletedCount];
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error purging submissions: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get submission statistics
     */
    public function getStats($days = 30) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read,
                    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(DISTINCT email) as unique_emails
                 FROM contact_submissions
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            $stmt->execute([$days]);
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $stats];
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error getting stats: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Find duplicate submissions (potential spam)
     */
    public function findDuplicates($days = 7) {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    name, 
                    email, 
                    COUNT(*) as submission_count,
                    GROUP_CONCAT(id ORDER BY created_at DESC) as submission_ids,
                    MIN(created_at) as first_submission,
                    MAX(created_at) as last_submission
                 FROM contact_submissions
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY name, email
                 HAVING COUNT(*) > 1
                 ORDER BY submission_count DESC, last_submission DESC"
            );
            $stmt->execute([$days]);
            
            $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $duplicates, 'count' => count($duplicates)];
            
        } catch (PDOException $e) {
            $this->log->save('contact-errors', "Error finding duplicates: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// ============================================================================
// CLI Handler
// ============================================================================

if (php_sapi_name() === 'cli') {
    $manager = new BlockedNamesManager();
    
    $action = $argv[1] ?? 'help';
    
    switch ($action) {
        case 'block':
            $name = $argv[2] ?? '';
            $reason = $argv[3] ?? 'Manual CLI block';
            $result = $manager->blockName($name, $reason, 'cli');
            echo $result['message'] . "\n";
            exit($result['success'] ? 0 : 1);
            
        case 'unblock':
            $name = $argv[2] ?? '';
            $result = $manager->unblockName($name);
            echo $result['message'] . "\n";
            exit($result['success'] ? 0 : 1);
            
        case 'list':
            $result = $manager->listBlocked(1000);
            if ($result['success']) {
                echo "Blocked Names ({$result['count']}):\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($result['data'] as $item) {
                    printf("%-25s | %-30s | %-10s | %s\n", 
                        $item['name'], 
                        substr($item['reason'], 0, 30),
                        $item['added_by'],
                        $item['created_at']
                    );
                }
            } else {
                echo "Error: {$result['message']}\n";
                exit(1);
            }
            break;
            
        case 'purge':
            $days = 90;
            foreach ($argv as $arg) {
                if (strpos($arg, '--days=') === 0) {
                    $days = (int)substr($arg, 7);
                }
            }
            $result = $manager->purgeOldSubmissions($days);
            echo $result['message'] . "\n";
            exit($result['success'] ? 0 : 1);
            
        case 'stats':
            $days = 30;
            foreach ($argv as $arg) {
                if (strpos($arg, '--days=') === 0) {
                    $days = (int)substr($arg, 7);
                }
            }
            $result = $manager->getStats($days);
            if ($result['success']) {
                echo "Statistics (Last {$days} days):\n";
                echo str_repeat('-', 40) . "\n";
                foreach ($result['data'] as $key => $value) {
                    printf("%-20s: %s\n", ucfirst(str_replace('_', ' ', $key)), $value);
                }
            }
            break;
            
        case 'duplicates':
            $days = 7;
            foreach ($argv as $arg) {
                if (strpos($arg, '--days=') === 0) {
                    $days = (int)substr($arg, 7);
                }
            }
            $result = $manager->findDuplicates($days);
            if ($result['success']) {
                echo "Duplicate Submissions (Last {$days} days): {$result['count']}\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($result['data'] as $item) {
                    printf("%-25s | %-30s | Count: %d | IDs: %s\n",
                        $item['name'],
                        $item['email'],
                        $item['submission_count'],
                        $item['submission_ids']
                    );
                }
            }
            break;
            
        case 'help':
        default:
            echo "Blocked Names Manager - CLI Commands\n";
            echo str_repeat('=', 80) . "\n\n";
            echo "Usage:\n";
            echo "  php manage-blocked-names.php <command> [arguments]\n\n";
            echo "Commands:\n";
            echo "  block <name> [reason]     Block a name from submissions\n";
            echo "  unblock <name>            Remove a name from block list\n";
            echo "  list                      List all blocked names\n";
            echo "  purge [--days=90]         Delete old spam/deleted submissions\n";
            echo "  stats [--days=30]         Show submission statistics\n";
            echo "  duplicates [--days=7]     Find duplicate submissions\n";
            echo "  help                      Show this help message\n\n";
            echo "Examples:\n";
            echo "  php manage-blocked-names.php block \"JYupWMLW\" \"Spam pattern\"\n";
            echo "  php manage-blocked-names.php unblock \"JYupWMLW\"\n";
            echo "  php manage-blocked-names.php purge --days=90\n";
            echo "  php manage-blocked-names.php stats --days=7\n\n";
            break;
    }
    
    exit(0);
}

// ============================================================================
// Web Handler (PROTECT THIS WITH AUTHENTICATION!)
// ============================================================================

if (php_sapi_name() !== 'cli') {
    // IMPORTANT: Add authentication check here!
    // Example: if (!wire('user')->isSuperuser()) die('Access denied');
    
    // For demonstration only - REMOVE IN PRODUCTION
    if (!wire('user')->isLoggedin() || !wire('user')->isSuperuser()) {
        die('Access denied. Superuser login required.');
    }
    
    header('Content-Type: application/json');
    
    $manager = new BlockedNamesManager();
    $action = wire('input')->get('action', 'text');
    
    switch ($action) {
        case 'block':
            $name = wire('input')->get('name', 'text');
            $reason = wire('input')->get('reason', 'text') ?: 'Web admin block';
            $result = $manager->blockName($name, $reason, wire('user')->name);
            echo json_encode($result);
            break;
            
        case 'unblock':
            $name = wire('input')->get('name', 'text');
            $result = $manager->unblockName($name);
            echo json_encode($result);
            break;
            
        case 'list':
            $limit = wire('input')->get('limit', 'int') ?: 100;
            $result = $manager->listBlocked($limit);
            echo json_encode($result);
            break;
            
        case 'purge':
            $days = wire('input')->get('days', 'int') ?: 90;
            $result = $manager->purgeOldSubmissions($days);
            echo json_encode($result);
            break;
            
        case 'stats':
            $days = wire('input')->get('days', 'int') ?: 30;
            $result = $manager->getStats($days);
            echo json_encode($result);
            break;
            
        case 'duplicates':
            $days = wire('input')->get('days', 'int') ?: 7;
            $result = $manager->findDuplicates($days);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
    
    exit;
}
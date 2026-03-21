<?php
/**
 * Audit Logger - Comprehensive Activity Tracking System
 * Tracks all user activities and admin actions with detailed logging
 */

class AuditLogger {
    private static $conn = null;
    private static $userId = null;
    private static $userRole = null;
    
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_VIEW = 'VIEW';
    const ACTION_EXPORT = 'EXPORT';
    const ACTION_IMPORT = 'IMPORT';
    const ACTION_VERIFY = 'VERIFY';
    const ACTION_BAN = 'BAN';
    const ACTION_PASSWORD_RESET = 'PASSWORD_RESET';
    
    /**
     * Initialize audit logger
     */
    public static function init($conn, $userId = null) {
        self::$conn = $conn;
        self::$userId = $userId;
        
        if ($userId) {
            $stmt = $conn->prepare("SELECT role FROM users WHERE id_pendaftar = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            self::$userRole = $result['role'] ?? 'guest';
        }
    }
    
    /**
     * Log an action to audit trail
     */
    public static function log($action, $entity, $entityId, $details = []) {
        if (!self::$conn) return false;
        
        try {
            $logData = [
                'user_id' => self::$userId,
                'user_role' => self::$userRole,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $sql = "
                INSERT INTO audit_logs_enhanced 
                (user_id, user_role, action, entity, entity_id, details, ip_address, user_agent, timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = self::$conn->prepare($sql);
            return $stmt->execute([
                $logData['user_id'],
                $logData['user_role'],
                $logData['action'],
                $logData['entity'],
                $logData['entity_id'],
                $logData['details'],
                $logData['ip_address'],
                $logData['user_agent'],
                $logData['timestamp']
            ]);
            
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log data modification with before/after comparison
     */
    public static function logModification($entity, $entityId, $oldData, $newData, $action = self::ACTION_UPDATE) {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return self::log($action, $entity, $entityId, [
            'changes' => $changes,
            'modified_fields' => count($changes)
        ]);
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }
    
    /**
     * Query audit logs
     */
    public static function getLog($filters = [], $limit = 100, $offset = 0) {
        if (!self::$conn) return [];
        
        $sql = "SELECT * FROM audit_logs_enhanced WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity'])) {
            $sql .= " AND entity = ?";
            $params[] = $filters['entity'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(timestamp) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(timestamp) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $stmt = self::$conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get audit log statistics
     */
    public static function getStatistics($dateFrom = null, $dateTo = null) {
        if (!self::$conn) return [];
        
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM audit_logs_enhanced
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(timestamp) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(timestamp) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " GROUP BY action ORDER BY count DESC";
        
        try {
            $stmt = self::$conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear old logs (for maintenance)
     */
    public static function purgeOldLogs($daysOld = 90) {
        if (!self::$conn) return false;
        
        try {
            $sql = "DELETE FROM audit_logs_enhanced WHERE DATE(timestamp) < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = self::$conn->prepare($sql);
            return $stmt->execute([$daysOld]);
        } catch (Exception $e) {
            return false;
        }
    }
}

// Create the audit logs table if it doesn't exist
function createAuditTable($conn) {
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS audit_logs_enhanced (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                user_role VARCHAR(50),
                action VARCHAR(50),
                entity VARCHAR(100),
                entity_id INT,
                details JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_entity (entity),
                INDEX idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (Exception $e) {
        error_log("Failed to create audit table: " . $e->getMessage());
        return false;
    }
}
?>

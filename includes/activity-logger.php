<?php
/**
 * Simple Activity Logger
 * Add this to your config/functions.php or include separately
 */

/**
 * Log user activity
 * 
 * @param PDO $pdo - Database connection
 * @param int|null $user_id - User ID (null for failed logins)
 * @param string $email - User email
 * @param string $action - Action performed (e.g., 'login', 'logout', 'register')
 * @param string $status - Status: 'success' or 'failed'
 * @return bool - True on success, false on failure
 */
function logActivity($pdo, $user_id, $email, $action, $status = 'success') {
    try {
        // Get client IP
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        // Get user agent
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, user_email, activity_log_action, activity_log_status, activity_log_ip_address, activity_log_user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$user_id, $email, $action, $status, $ip, $user_agent]);
        
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

?>
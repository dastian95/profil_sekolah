<?php
/**
 * Simple File-based Rate Limiter
 * 
 * @param string $action The action name (e.g., 'login', 'verify')
 * @param int $limit Number of allowed attempts
 * @param int $window Time window in seconds
 * @return bool True if allowed, False if limit exceeded
 */
function checkRateLimit($action, $limit, $window) {
    $ip = $_SERVER['REMOTE_ADDR'];
    // Use system temp dir to store rate limit files
    $file = sys_get_temp_dir() . '/rl_' . md5($ip . '_' . $action) . '.json';
    
    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true);
        if (!is_array($attempts)) $attempts = [];
    }
    
    $now = time();
    // Filter out attempts older than the window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
        return $timestamp > ($now - $window);
    });
    
    if (count($attempts) >= $limit) {
        // Update file to prune old entries even if blocked (optional, but keeps file clean)
        file_put_contents($file, json_encode(array_values($attempts)));
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    file_put_contents($file, json_encode(array_values($attempts)));
    return true;
}

/**
 * Get remaining attempts for an action
 */
function getRemainingAttempts($action, $limit, $window) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $file = sys_get_temp_dir() . '/rl_' . md5($ip . '_' . $action) . '.json';
    
    $attempts = [];
    if (file_exists($file)) {
        $attempts = json_decode(file_get_contents($file), true);
        if (!is_array($attempts)) $attempts = [];
    }
    
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
        return $timestamp > ($now - $window);
    });
    
    return max(0, $limit - count($attempts));
}
?>
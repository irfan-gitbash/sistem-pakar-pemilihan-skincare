<?php
// Replace the current session_start() with a check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if user is already logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']) && isset($_SESSION['last_activity']);
}

// Verify session timeout (30 minutes)
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Require authentication for admin pages
function requireAuth() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        header('Location: login.php');
        exit();
    }
}

// Log admin activity
// Log admin activity
function logActivity($db, $action, $table_name, $record_id = null, $details = null) {
    try {
        $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
        $stmt = $db->prepare("INSERT INTO activity_log (admin_id, action, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $action, $table_name, $record_id, $details]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}


// Sanitize and validate input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)));
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

// Check if request is POST and has valid CSRF token
function isValidPostRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token'])) {
            http_response_code(403);
            die('CSRF token missing');
        }
        verifyCSRFToken($_POST['csrf_token']);
        return true;
    }
    return false;
}

// Function to get admin details
function getAdminDetails($db, $admin_id) {
    try {
        $stmt = $db->prepare("SELECT id, username, created_at FROM admin WHERE id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting admin details: " . $e->getMessage());
        return false;
    }
}

// Function to get recent activity logs
function getRecentActivityLogs($db, $limit = 10) {
    try {
        $stmt = $db->prepare("
            SELECT al.*, a.username 
            FROM activity_log al 
            LEFT JOIN admin a ON al.admin_id = a.id 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting activity logs: " . $e->getMessage());
        return [];
    }
}

// Function to format activity log message
function formatActivityLog($log) {
    $action = ucfirst($log['action']);
    $table = ucfirst(str_replace('_', ' ', $log['table_name']));
    $time = date('d M Y H:i:s', strtotime($log['created_at']));
    $admin = $log['username'] ?? 'Unknown';
    
    return "{$action} on {$table} by {$admin} at {$time}";
}

// Function to check if password meets requirements
function isPasswordStrong($password) {
    // At least 8 characters long
    // Contains at least one uppercase letter
    // Contains at least one lowercase letter
    // Contains at least one number
    // Contains at least one special character
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}
?>

<?php
/**
 * Authentication and Session Management Functions
 * College Study Material & Practice Portal
 */

// Include helper utilities if not loaded
require_once __DIR__ . '/helpers.php';

/**
 * Checks if user is authenticated and attempts remember-me automatic login
 */
function check_login() {
    global $pdo;
    
    // 1. If session already contains user ID, user is logged in
    if (!empty($_SESSION['user_id'])) {
        return true;
    }
    
    // 2. Check if remember cookie exists
    if (!empty($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];
        
        // Find matching active token in database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active'");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Set login sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Re-generate session ID for security
            session_regenerate_id(true);
            return true;
        } else {
            // Clear invalid cookie
            setcookie('remember_me', '', time() - 3600, BASE_URL);
        }
    }
    
    return false;
}

/**
 * Authenticates user credentials and establishes a session
 */
function login($email, $password, $remember = false) {
    global $pdo;
    
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return "Invalid email address or password.";
    }
    
    if ($user['status'] !== 'active') {
        return "Your account is currently inactive. Please contact the administrator.";
    }
    
    // Establish sessions
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    session_regenerate_id(true);
    
    // Handle "Remember Me"
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        
        // Update user record with remember token
        $upd = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $upd->execute([$token, $user['id']]);
        
        // Save remember cookie for 30 days
        setcookie('remember_me', $token, time() + (86400 * 30), BASE_URL, '', false, true);
    }
    
    return true; // Success
}

/**
 * Destroys authentication sessions and clears remember-me tokens
 */
function logout() {
    global $pdo;
    
    if (!empty($_SESSION['user_id'])) {
        // Clear remember token in DB
        $upd = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $upd->execute([$_SESSION['user_id']]);
    }
    
    // Clear cookies
    setcookie('remember_me', '', time() - 3600, BASE_URL);
    
    // Destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Restricts page access to authorized roles only
 */
function require_role($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    // 1. Ensure user is logged in
    if (!check_login()) {
        set_flash_message('danger', 'You must log in to view that page.');
        redirect(BASE_URL . 'index.php');
    }
    
    // 2. Validate user role
    $user_role = $_SESSION['user_role'] ?? '';
    if (!in_array($user_role, $roles)) {
        set_flash_message('danger', 'You are not authorized to access that section.');
        
        // Redirect to their default dashboard
        if ($user_role === 'admin') {
            redirect(BASE_URL . 'admin/dashboard.php');
        } elseif ($user_role === 'staff') {
            redirect(BASE_URL . 'staff/dashboard.php');
        } elseif ($user_role === 'student') {
            redirect(BASE_URL . 'student/dashboard.php');
        } else {
            redirect(BASE_URL . 'index.php');
        }
    }
}

/**
 * Fetches profile details of the current logged-in user
 */
function get_current_user_profile() {
    global $pdo;
    
    if (!check_login()) return null;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    
    if ($role === 'student') {
        $stmt = $pdo->prepare("
            SELECT u.email, u.role, u.status, s.*, b.name as branch_name, b.code as branch_code, sem.number as semester_no 
            FROM users u
            JOIN students s ON u.id = s.user_id
            JOIN branches b ON s.branch_id = b.id
            JOIN semesters sem ON s.semester_id = sem.id
            WHERE u.id = ?
        ");
    } elseif ($role === 'staff') {
        $stmt = $pdo->prepare("
            SELECT u.email, u.role, u.status, sf.*
            FROM users u
            JOIN staff sf ON u.id = sf.user_id
            WHERE u.id = ?
        ");
    } else {
        // Admin
        $stmt = $pdo->prepare("SELECT id, email, role, status FROM users WHERE id = ?");
    }
    
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

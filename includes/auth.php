<?php
/**
 * Authentication Functions
 * Handles user login, session management, and access control
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        header('Location: ' . url('login.php'));
        exit();
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            return [
                'success' => true,
                'role' => $user['role']
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Invalid username or password'
    ];
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: ' . url('login.php'));
    exit();
}
?>

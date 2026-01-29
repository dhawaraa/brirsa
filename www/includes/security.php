<?php
/**
 * Security Utilities
 * CSRF Protection, Session Hardening, and Secure Output
 */

/**
 * Initialize secure session
 */
function secure_session_start()
{
    // RBAC_SECURITY_V6
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Get CSRF token
 */
function get_csrf_token()
{
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token)
{
    if (!$token || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF for POST requests
 */
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!validate_csrf_token($token)) {
            header('HTTP/1.1 403 Forbidden');
            die('ความปลอดภัยล้มเหลว: CSRF Token ไม่ถูกต้อง (Security Error: Invalid CSRF Token)');
        }
    }
}

/**
 * Check if current user has one of the allowed roles
 * @param array|string $allowed_roles
 */
function check_role($allowed_roles)
{
    secure_session_start();

    if (!isset($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit;
    }

    $current_role = $_SESSION['admin_role'] ?? 'super_admin';
    if ($current_role === '') {
        $current_role = 'super_admin';
    }

    // Super Admin always passes
    if ($current_role === 'super_admin') {
        return;
    }

    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($current_role, $allowed_roles)) {
        header('HTTP/1.1 403 Forbidden');
        // If not enough permission, go to dashboard
        if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php') {
            header('Location: dashboard.php?error=permission');
            exit;
        }
        die('ท่านไม่มีสิทธิ์เข้าถึงหน้านี้ (Access Denied)');
    }
}

/**
 * Check if current user has a specific role
 */
function is_role($role)
{
    secure_session_start();
    $current = $_SESSION['admin_role'] ?? 'super_admin';
    if ($current === '') {
        $current = 'super_admin';
    }

    // Super admin always has the power of other roles for UI purposes
    if ($current === 'super_admin') {
        return true;
    }

    return $current === $role;
}

/**
 * Escaping for HTML Output
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Output CSRF hidden input field
 */
function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . e(get_csrf_token()) . '">';
}

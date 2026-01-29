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
    if (session_status() === PHP_SESSION_NONE) {
        // Force session cookies to be HttpOnly and use Lax SameSite
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');

        // Use secure cookies if on HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

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

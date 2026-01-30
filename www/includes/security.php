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
        // Set secure session cookie parameters
        $secure = isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
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
    secure_session_start();
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token)
{
    secure_session_start();
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
        secure_session_start();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!validate_csrf_token($token)) {
            // Log for debugging on live host
            $debug_info = [
                'received_token' => $token ? 'present' : 'missing',
                'session_token' => isset($_SESSION['csrf_token']) ? 'present' : 'missing',
                'session_id' => session_id(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ];
            error_log("CSRF Validation Failed: " . json_encode($debug_info));

            if (
                strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
                strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/api/') !== false
            ) {

                throw new Exception('ความปลอดภัยล้มเหลว: CSRF Token ไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บและลองใหม่อีกครั้ง');
            } else {
                header('HTTP/1.1 403 Forbidden');
                die('ความปลอดภัยล้มเหลว: CSRF Token ไม่ถูกต้อง กรุณารีเฟรชหน้าเว็บและลองใหม่อีกครั้ง');
            }
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

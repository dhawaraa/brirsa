<?php
/**
 * Admin Password Reset Tool
 * 🛡️ For emergency use only. 
 * HOW TO USE:
 * 1. Upload this file to your server (e.g., in the www/ directory)
 * 2. Access it via browser: yoursite.com/fix_admin.php
 * 3. After success, DELETE THIS FILE IMMEDIATELY for security.
 */

require_once 'includes/config.php';

$pdo = getDB();
$username = 'admin';
$new_password = 'mcubri@2025';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $username]);

    if ($stmt->rowCount() > 0) {
        echo "<h2 style='color: green;'>✅ Password updated successfully!</h2>";
        echo "<p>Username: <b>$username</b></p>";
        echo "<p>Password: <b>$new_password</b></p>";
        echo "<hr><p style='color: red;'>⚠️ IMPORTANT: Please delete this file (fix_admin.php) from your server right now!</p>";
    } else {
        // Maybe the user doesn't exist? Try to insert
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, 'ผู้ดูแลระบบ') ON DUPLICATE KEY UPDATE password = ?");
        $stmt->execute([$username, $hashed_password, $hashed_password]);
        echo "<h2 style='color: blue;'>ℹ️ User 'admin' was created/updated with the new password.</h2>";
        echo "<hr><p style='color: red;'>⚠️ IMPORTANT: Please delete this file (fix_admin.php) from your server right now!</p>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}

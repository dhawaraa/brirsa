<?php
require_once 'includes/config.php';
require_once 'includes/security.php';
secure_session_start();

echo "<h1>Debug Info</h1>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "SERVER name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";

try {
    $pdo = getDB();
    echo "\nDatabase: Connected\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM admins");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Admins Table Columns:\n";
    foreach ($cols as $c) {
        echo "- " . $c['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
echo "<hr>";
echo "<a href='admin/dashboard.php'>Go to Dashboard</a>";

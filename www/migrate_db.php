<?php
require_once 'includes/config.php';
try {
    $pdo = getDB();
    $pdo->exec("ALTER TABLE requests MODIFY COLUMN status VARCHAR(50)");
    echo "Successfully updated database schema: changed 'status' column to VARCHAR(50)";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
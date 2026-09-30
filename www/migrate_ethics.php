<?php
/**
 * Migration: Add file_ethics column to requests table
 */
require_once 'includes/config.php';

try {
    $pdo = getDB();
    
    // Check if column already exists
    $stmt = $pdo->query("DESCRIBE requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('file_ethics', $columns)) {
        // Add after file_participation
        $pdo->exec("ALTER TABLE requests ADD COLUMN file_ethics VARCHAR(255) NULL AFTER file_participation");
        echo "Column 'file_ethics' added successfully.\n";
    } else {
        echo "Column 'file_ethics' already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

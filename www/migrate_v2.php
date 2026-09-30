<?php
/**
 * Migration: Add contributors and file_ethics columns to requests table if missing
 */
require_once 'includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE requests");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $updated = false;

    // 1. Check & Add 'contributors' column
    if (!in_array('contributors', $columns)) {
        // Add contributors TEXT NULL after plagiarism_percent
        $pdo->exec("ALTER TABLE requests ADD COLUMN contributors TEXT NULL AFTER plagiarism_percent");
        echo "Column 'contributors' added successfully.\n";
        $updated = true;
    } else {
        echo "Column 'contributors' already exists.\n";
    }

    // 2. Check & Add 'file_ethics' column (just in case it was missed)
    if (!in_array('file_ethics', $columns)) {
        // Add file_ethics VARCHAR(255) NULL after file_participation
        $pdo->exec("ALTER TABLE requests ADD COLUMN file_ethics VARCHAR(255) NULL AFTER file_participation");
        echo "Column 'file_ethics' added successfully.\n";
        $updated = true;
    } else {
        echo "Column 'file_ethics' already exists.\n";
    }

    if ($updated) {
        echo "Database migration completed successfully.\n";
    } else {
        echo "Database schema is already up to date. No changes needed.\n";
    }

} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
?>

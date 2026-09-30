<?php
/**
 * Migration: Create popups table for Admin Popup Management
 */
require_once 'includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    
    // Create popups table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS popups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title_th VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) NULL,
        content_th TEXT NULL,
        content_en TEXT NULL,
        image_url VARCHAR(255) NULL,
        link_url VARCHAR(255) NULL,
        link_text_th VARCHAR(100) NULL,
        link_text_en VARCHAR(100) NULL,
        start_date DATE NULL,
        end_date DATE NULL,
        target_page VARCHAR(50) DEFAULT 'home', -- 'home' or 'all'
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'popups' created or already exists.\n";

    // Ensure uploads/popups directory exists
    $popupUploadDir = __DIR__ . '/uploads/popups';
    if (!file_exists($popupUploadDir)) {
        if (@mkdir($popupUploadDir, 0777, true)) {
            echo "Directory 'uploads/popups/' created successfully.\n";
        } else {
            echo "Notice: Could not automatically create 'uploads/popups/'. Please ensure folder permissions.\n";
        }
    } else {
        echo "Directory 'uploads/popups/' already exists.\n";
    }

    echo "\nPopup system migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
?>

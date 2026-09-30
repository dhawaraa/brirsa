<?php
/**
 * Migration: Create request_evaluations table for Checklist Research Cert V2
 */
require_once 'includes/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();

    $sql = "CREATE TABLE IF NOT EXISTS request_evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        eval_date DATE NULL,
        evaluator_name VARCHAR(255) NULL,
        director_name VARCHAR(255) NULL,
        
        -- หมวด ก: ความครบถ้วนของเอกสาร (pass, fail, edit)
        doc_report VARCHAR(20) DEFAULT 'pass',
        doc_plagiarism VARCHAR(20) DEFAULT 'pass',
        doc_cert VARCHAR(20) DEFAULT 'pass',
        doc_participation VARCHAR(20) DEFAULT 'pass',
        doc_ethics VARCHAR(20) DEFAULT 'pass',
        
        -- ส่วนที่ ๓: คุณภาพงานวิจัย (pass, fail, edit)
        q_consistent VARCHAR(20) DEFAULT 'pass',
        q_literature VARCHAR(20) DEFAULT 'pass',
        q_methodology VARCHAR(20) DEFAULT 'pass',
        q_tool_validity VARCHAR(20) DEFAULT 'pass',
        q_knowledge VARCHAR(20) DEFAULT 'pass',
        
        -- การใช้ AI (pass, fail, edit)
        ai_acceptable_use VARCHAR(20) DEFAULT 'pass',
        ai_verified_citations VARCHAR(20) DEFAULT 'pass',
        ai_researcher_analysis VARCHAR(20) DEFAULT 'pass',
        
        -- ข้อสังเกตและรายการที่ต้องแก้ไข
        issue_1 TEXT NULL,
        issue_2 TEXT NULL,
        issue_3 TEXT NULL,
        issue_4 TEXT NULL,
        additional_comments TEXT NULL,
        
        -- ผลการพิจารณา: certified, certified_with_condition, rejected
        result_decision VARCHAR(50) DEFAULT 'certified',
        deadline_date DATE NULL,
        rejection_reason TEXT NULL,
        
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_request_id (request_id),
        CONSTRAINT fk_eval_request FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'request_evaluations' created or already exists.\n";
    echo "Checklist v2 evaluation migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}

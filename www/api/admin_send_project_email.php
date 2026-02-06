<?php
/**
 * API: Send Project Info Email from Admin
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../includes/config.php';
require_once '../includes/mail_config.php';
require_once '../includes/mail_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    verify_csrf();

    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('ไม่พบรหัสข้อมูลโครงการ');
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if (!$req) {
        throw new Exception('ไม่พบข้อมูลโครงการในระบบ');
    }

    // Detect language from applicant name
    $lang = detectLanguageFromName($req['name']);

    // Get subject and body based on detected language
    $subject = getEmailSubject('project_info', $lang, ['request_id' => $req['request_id']]);
    $body = getProjectInfoEmailBody($req, $lang);

    // Set sender name based on language
    $fromName = ($lang === 'en') ? 'Buddhist Research Institute, MCU' : 'สถาบันวิจัยพุทธศาสตร์ มจร';

    if (sendMail($req['email'], $subject, $body, $fromName)) {
        echo json_encode(['success' => true, 'message' => 'ส่งอีเมลเรียบร้อยแล้ว', 'language' => $lang]);
    } else {
        throw new Exception('ไม่สามารถส่งอีเมลได้ กรุณาตรวจสอบการตั้งค่าเมล');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

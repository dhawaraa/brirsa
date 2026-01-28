<?php
/**
 * API: Update Request/Resubmit Correction
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $id = intval($_POST['id'] ?? 0);
    $request_id = sanitize($_POST['request_id'] ?? '');

    if (!$id || !$request_id) {
        throw new Exception('Invalid request ID');
    }

    // Get form data (email is needed early for ownership check)
    $email = sanitize($_POST['email'] ?? '');

    $pdo = getDB();

    // Verify ownership and status
    $stmt = $pdo->prepare("SELECT status, email, name, project_title, fiscal_year, affiliation, academic_position, branch, tel, journal_name, plagiarism_percent, file_report FROM requests WHERE id = ? AND request_id = ?");
    $stmt->execute([$id, $request_id]);
    $current = $stmt->fetch();

    if (!$current) {
        throw new Exception('ไม่พบข้อมูลคำขอ');
    }

    // Check if email matches (security)
    if ($current['email'] !== $email) {
        throw new Exception('ข้อมูลอีเมลไม่ถูกต้อง');
    }

    $allowed_statuses = ['correction', 'ch_edit'];
    if (!in_array($current['status'], $allowed_statuses)) {
        throw new Exception('สถานะปัจจุบันไม่สามารถแก้ไขข้อมูลได้');
    }

    $isReportOnly = ($current['status'] === 'correction');

    // Handle form data and validation based on status
    if ($isReportOnly) {
        // For 'correction' status, only file_report is expected to be updated.
        // Other fields retain their original values.
        $name = $current['name'];
        $project_title = $current['project_title'];
        $fiscal_year = $current['fiscal_year'];
        $affiliation = $current['affiliation'];
        $academic_position = $current['academic_position'];
        $branch = $current['branch'];
        $tel = $current['tel'];
        $journal_name = $current['journal_name'];
        $plagiarism_percent = $current['plagiarism_percent'];

        if (!isset($_FILES['file_report']) || $_FILES['file_report']['error'] !== UPLOAD_ERR_OK) {
            // If they already have a report, maybe they are just updating other minor files (if we allowed it)
            // But usually for status 3 they MUST upload a new report.
            if (empty($current['file_report'])) {
                throw new Exception('กรุณาอัปโหลดไฟล์รายงานวิจัยฉบับสมบูรณ์');
            }
        }
    } else {
        // For 'ch_edit' status, all fields can be updated.
        $name = sanitize($_POST['name'] ?? '');
        $project_title = sanitize($_POST['project_title'] ?? '');
        $fiscal_year = sanitize($_POST['fiscal_year'] ?? '');
        $affiliation = sanitize($_POST['affiliation'] ?? '');
        $academic_position = sanitize($_POST['academic_position'] ?? '');
        $branch = sanitize($_POST['branch'] ?? '');
        $tel = sanitize($_POST['tel'] ?? '');
        $journal_name = sanitize($_POST['journal_name'] ?? '');
        $plagiarism_percent = floatval($_POST['plagiarism_percent'] ?? 0);

        // Validate required fields for full edit
        if (empty($name) || empty($project_title) || empty($fiscal_year) || empty($affiliation) || empty($tel)) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }
        if ($plagiarism_percent > 15) {
            throw new Exception('เปอร์เซ็นต์การคัดลอกต้องไม่เกิน 15%');
        }
    }

    // Handle file uploads (optional replacement)
    $uploadDir = UPLOAD_DIR . $request_id . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileFields = $isReportOnly ? ['file_report'] : ['file_report', 'file_self_assess', 'file_sp10', 'file_sp10_1', 'file_cert', 'file_plagiarism', 'file_participation'];
    $uploadedFiles = [];

    foreach ($fileFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];

            // Validate
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($file['tmp_name']) !== 'application/pdf') {
                throw new Exception("ไฟล์ {$field} ต้องเป็น PDF เท่านั้น");
            }
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception("ไฟล์ {$field} มีขนาดเกิน 50MB");
            }

            $filename = $field . '_' . time() . '.pdf';
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $uploadedFiles[$field] = 'uploads/' . $request_id . '/' . $filename;
            }
        }
    }

    // Build update query dynamically
    $sql = "UPDATE requests SET 
            name = ?, project_title = ?, fiscal_year = ?, affiliation = ?, 
            academic_position = ?, branch = ?, tel = ?, journal_name = ?, 
            plagiarism_percent = ?, status = 'resubmitted'";
    $params = [
        $name,
        $project_title,
        $fiscal_year,
        $affiliation,
        $academic_position,
        $branch,
        $tel,
        $journal_name,
        $plagiarism_percent
    ];

    foreach ($uploadedFiles as $field => $path) {
        $sql .= ", $field = ?";
        $params[] = $path;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Send Google Chat notification
    require_once '../includes/notifier.php';
    sendGoogleChat('update', [
        'id' => $id,
        'request_id' => $request_id,
        'name' => $name,
        'project_title' => $project_title,
        'affiliation' => $affiliation,
        'academic_position' => $academic_position,
        'branch' => $branch,
        'email' => $current['email'],
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

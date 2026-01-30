<?php
/**
 * API: Submit Registration Form
 */
header('Content-Type: application/json; charset=utf-8');
ob_start(); // Start output buffering to catch any accidental output

require_once '../includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    verify_csrf();

    // Get form data
    $name = sanitize($_POST['name'] ?? '');
    $project_title = sanitize($_POST['project_title'] ?? '');
    $fiscal_year = sanitize($_POST['fiscal_year'] ?? '');
    $affiliation = sanitize($_POST['affiliation'] ?? '');
    $academic_position = sanitize($_POST['academic_position'] ?? '');
    $branch = sanitize($_POST['branch'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $journal_name = sanitize($_POST['journal_name'] ?? '');
    $plagiarism_percent = floatval($_POST['plagiarism_percent'] ?? 0);

    // Validate required fields
    if (empty($name) || empty($project_title) || empty($fiscal_year) || empty($affiliation) || empty($tel) || empty($email)) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    // Validate plagiarism percent
    if ($plagiarism_percent > 15) {
        throw new Exception('เปอร์เซ็นต์การคัดลอกต้องไม่เกิน 15%');
    }

    $pdo = getDB();

    // Check duplicate project title
    $stmt = $pdo->prepare("SELECT id FROM requests WHERE project_title = ?");
    $stmt->execute([$project_title]);
    if ($stmt->fetch()) {
        throw new Exception('ชื่อโครงการนี้มีอยู่ในระบบแล้ว');
    }

    // Check duplicate submission in same fiscal year
    $stmt = $pdo->prepare("SELECT id FROM requests WHERE name = ? AND fiscal_year = ?");
    $stmt->execute([$name, $fiscal_year]);
    if ($stmt->fetch()) {
        throw new Exception("คุณ $name ได้ส่งคำขอในปีงบประมาณ $fiscal_year แล้ว");
    }

    // Generate unique request ID
    $request_id = generateRequestId();

    // Create upload directory for this request
    $uploadDir = UPLOAD_DIR . $request_id . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Handle file uploads
    $fileFields = ['file_report', 'file_self_assess', 'file_sp10', 'file_sp10_1', 'file_cert', 'file_plagiarism', 'file_participation'];
    $uploadedFiles = [];

    foreach ($fileFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];

            // Validate file type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            if ($mimeType !== 'application/pdf') {
                throw new Exception("ไฟล์ {$field} ต้องเป็น PDF เท่านั้น");
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception("ไฟล์ {$field} มีขนาดเกิน 50MB");
            }

            // Generate safe filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $field . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $uploadedFiles[$field] = 'uploads/' . $request_id . '/' . $filename;
            } else {
                throw new Exception("ไม่สามารถอัปโหลดไฟล์ {$field} ได้");
            }
        } else {
            throw new Exception("กรุณาอัปโหลดไฟล์ให้ครบถ้วน");
        }
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO requests (
            request_id, name, project_title, fiscal_year, affiliation, 
            academic_position, branch, tel, email, journal_name, 
            plagiarism_percent, status,
            file_report, file_self_assess, file_sp10, file_sp10_1, file_cert, file_plagiarism,
            file_participation
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, 'checking',
            ?, ?, ?, ?, ?, ?,
            ?
        )
    ");

    $stmt->execute([
        $request_id,
        $name,
        $project_title,
        $fiscal_year,
        $affiliation,
        $academic_position,
        $branch,
        $tel,
        $email,
        $journal_name,
        $plagiarism_percent,
        $uploadedFiles['file_report'] ?? null,
        $uploadedFiles['file_self_assess'] ?? null,
        $uploadedFiles['file_sp10'] ?? null,
        $uploadedFiles['file_sp10_1'] ?? null,
        $uploadedFiles['file_cert'] ?? null,
        $uploadedFiles['file_plagiarism'] ?? null,
        $uploadedFiles['file_participation'] ?? null
    ]);

    $lastId = $pdo->lastInsertId();

    // Send Google Chat notification
    require_once '../includes/notifier.php';
    sendGoogleChat('new', [
        'id' => $lastId,
        'request_id' => $request_id,
        'name' => $name,
        'project_title' => $project_title,
        'affiliation' => $affiliation,
        'academic_position' => $academic_position,
        'branch' => $branch,
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Send notification email to researcher
    require_once '../includes/mail_config.php';
    $baseUrl = "https://newbri.mcu.ac.th/app/rsa";
    $subject = "ยืนยันการรับข้อมูลขอรับรองงานวิจัย - รหัสคำขอ $request_id";

    // File Link helper
    $fileLinks = "";
    $fileLabels = [
        'file_report' => '1. รายงานวิจัยฉบับสมบูรณ์',
        'file_self_assess' => '2. แบบประเมินตนเอง',
        'file_sp10' => '3. บันทึกข้อความ สวพ.10',
        'file_sp10_1' => '4. แบบฟอร์มตรวจ สวพ.10.1',
        'file_cert' => '5. เอกสารรับรองการตีพิมพ์',
        'file_plagiarism' => '6. หลักฐานตรวจอักขราวิสุทธิ์',
        'file_participation' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย'
    ];

    foreach ($fileLabels as $key => $label) {
        if (isset($uploadedFiles[$key])) {
            $fileLinks .= "<tr>
                <td style='padding: 8px; border-bottom: 1px dotted #eee; color: #666;'>$label</td>
                <td style='padding: 8px; border-bottom: 1px dotted #eee;'>
                    <a href='$baseUrl/{$uploadedFiles[$key]}' style='color: #003366; text-decoration: none;'>📂 คลิกเพื่อเปิดไฟล์</a>
                </td>
            </tr>";
        }
    }

    $body = "
        <div style='font-family: \"Sarabun\", sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #003366; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>ระบบขอรับรองงานวิจัย</h2>
                <p style='margin: 5px 0 0; opacity: 0.8;'>สถาบันวิจัยพุทธศาสตร์ มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย</p>
            </div>
            
            <div style='padding: 25px;'>
                <p>เรียน คุณ <strong>$name</strong></p>
                <p>ระบบได้รับคำขอของท่านเรียบร้อยแล้ว กรุณาเก็บ <strong>รหัสคำขอ</strong> นี้ไว้เพื่อใช้ในการติดตามสถานะ:</p>
                
                <div style='background-color: #f8f9fa; border: 2px dashed #003366; border-radius: 5px; padding: 15px; text-align: center; margin: 20px 0;'>
                    <span style='font-size: 14px; color: #666; display: block;'>รหัสคำขอของคุณคือ</span>
                    <strong style='font-size: 28px; color: #d63384; letter-spacing: 2px;'>$request_id</strong>
                </div>

                <h3 style='color: #003366; border-bottom: 2px solid #003366; padding-bottom: 5px;'>รายละเอียดที่ส่งมา</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; width: 150px; color: #666;'>ชื่อโครงการ:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>$project_title</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>ปีงบประมาณ:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$fiscal_year</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>สังกัด:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$affiliation</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>ตำแหน่งที่ขอ:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$academic_position</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>สาขาวิชา:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$branch</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>เบอร์โทรศัพท์:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$tel</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>อีเมล:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$email</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>วารสารที่ตีพิมพ์:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$journal_name</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #eee; color: #666;'>% การคัดลอก:</td>
                        <td style='padding: 8px; border-bottom: 1px solid #eee;'>$plagiarism_percent%</td>
                    </tr>
                </table>

                <h3 style='color: #003366; border-bottom: 2px solid #003366; padding-bottom: 5px;'>เอกสารแนบ</h3>
                <table style='width: 100%; border-collapse: collapse; font-size: 13px;'>
                    $fileLinks
                </table>

                <div style='background-color: #fff9db; border: 1px solid #fab005; border-radius: 8px; padding: 15px; margin-top: 25px;'>
                    <h4 style='color: #e67700; margin: 0 0 10px 0; font-size: 15px;'>⚠️ สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล</h4>
                    <ul style='margin: 0 0 10px 0; padding-left: 20px; color: #8a5e00; font-size: 13px;'>
                        <li style='margin-bottom: 5px;'>ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม</li>
                        <li>พร้อมบันทึกข้อความ สวพ.10 จำนวน 1 ฉบับ</li>
                    </ul>
                    <p style='margin: 0; color: #8a5e00; font-size: 13px; font-weight: bold;'>มายัง สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย จ.พระนครศรีอยุธยา</p>
                </div>

                <div style='margin-top: 30px; text-align: left;'>
                    <a href='$baseUrl/status.php?id=$request_id' 
                       style='background-color: #003366; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;'>
                       🔍 ติดตามสถานะได้ที่นี่
                    </a>
                </div>
            </div>
            
            <div style='background-color: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; color: #777;'>
                <p style='margin: 0;'>อีเมลนี้เป็นอีเมลตอบกลับอัตโนมัติ กรุณาอย่าตอบกลับอีเมลนี้</p>
                <p style='margin: 5px 0 0;'>© " . date('Y') . " สถาบันวิจัยพุทธศาสตร์ มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย</p>
            </div>
        </div>
    ";
    sendMail($email, $subject, $body);

    // Clear output buffer to discard any accidental output/warnings
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'request_id' => $request_id,
        'message' => 'ส่งข้อมูลสำเร็จ'
    ]);

} catch (Exception $e) {
    // Clear output buffer on error as well
    if (ob_get_length())
        ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

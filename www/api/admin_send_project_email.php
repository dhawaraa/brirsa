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

    $request_id = $req['request_id'];
    $baseUrl = "https://newbri.mcu.ac.th/app/rsa";

    $subject = "แจ้งข้อมูลการขอรับรองงานวิจัยของคุณ - {$request_id}";

    $body = "
        <div style='font-family: \"Sarabun\", sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #003366 0%, #0052a3 100%); color: white; padding: 30px; text-align: center;'>
                <h2 style='margin: 0; font-size: 24px; letter-spacing: 0.5px;'>สรุปข้อมูลโครงการวิจัย</h2>
                <p style='margin: 8px 0 0; opacity: 0.9; font-size: 14px;'>สถาบันวิจัยพุทธศาสตร์ | ระบบขอรับรองงานวิจัย</p>
            </div>
            
            <div style='padding: 30px;'>
                <p style='font-size: 16px;'>เรียน คุณ <strong>{$req['name']}</strong></p>
                <p style='color: #666;'>สถาบันวิจัยพุทธศาสตร์ ได้ส่งข้อมูลการขอรับรองงานวิจัยของคุณในระบบ กรุณาใช้รหัสคำขอด้านล่าง เพื่อติดตามสถานะปัจจุบัน:</p>
                
                <div style='background-color: #f0f7ff; border: 2px solid #003366; border-radius: 10px; padding: 20px; text-align: center; margin: 25px 0;'>
                    <span style='font-size: 13px; color: #003366; display: block; margin-bottom: 5px; font-weight: bold; text-transform: uppercase;'>รหัสคำขอของคุณ (Request ID)</span>
                    <strong style='font-size: 32px; color: #d63384; letter-spacing: 3px; font-family: monospace;'>$request_id</strong>
                </div>

                <h3 style='color: #003366; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; font-size: 18px; margin-top: 30px;'>ข้อมูลโครงการวิจัย</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; width: 140px; color: #888;'>วันที่ส่งคำขอ:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-weight: bold;'>" . formatThaiDate($req['created_at']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>ชื่อโครงการ:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-weight: bold; color: #003366;'>" . htmlspecialchars($req['project_title']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>ชื่อผู้ขอรับรอง:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['name']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>สังกัด:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['affiliation']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>ตำแหน่งที่ขอ:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['academic_position'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>สาขาวิชา:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['branch'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>อีเมล:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['email']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Plagiarism (%):</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: " . ($req['plagiarism_percent'] > 15 ? '#d32f2f' : '#388e3c') . "; font-weight: bold;'>{$req['plagiarism_percent']}%</td>
                    </tr>
                </table>

                <div style='margin-top: 40px; text-align: left;'>
                    <p style='font-size: 13px; color: #666; margin-bottom: 15px;'>ท่านสามารถตรวจสอบความคืบหน้าของคำขอได้ตลอดเวลาผ่านลิงก์ด้านล่างนี้</p>
                    <a href='$baseUrl/status.php?id=$request_id' 
                       style='background-color: #003366; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; box-shadow: 0 4px 12px rgba(0,51,102,0.2); font-size: 16px; text-align: center;'>
                       🔍 ติดตามสถานะได้ที่นี่
                    </a>
                </div>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;'>
                <p style='margin: 0; font-size: 11px; color: #999;'>นี่คืออีเมลอัตโนมัติจากระบบ กรุณาอย่าตอบกลับอีเมลนี้</p>
                <p style='margin: 5px 0 0; font-size: 11px; color: #999;'>&copy; " . date('Y') . " สถาบันวิจัยพุทธศาสตร์ มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย</p>
            </div>
        </div>";

    if (sendMail($req['email'], $subject, $body)) {
        echo json_encode(['success' => true, 'message' => 'ส่งอีเมลเรียบร้อยแล้ว']);
    } else {
        throw new Exception('ไม่สามารถส่งอีเมลได้ กรุณาตรวจสอบการตั้งค่าเมล');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

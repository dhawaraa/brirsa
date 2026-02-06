<?php
/**
 * Mail Helper Functions
 * Language detection and bilingual email templates
 */

/**
 * Detect language from name
 * @param string $name - The applicant's name
 * @return string - 'th' or 'en'
 */
function detectLanguageFromName($name)
{
    // Check if name contains Thai characters (Unicode range: 0E00-0E7F)
    if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $name)) {
        return 'th';
    }
    return 'en';
}

/**
 * Get email subject based on type and language
 */
function getEmailSubject($type, $lang, $data = [])
{
    $subjects = [
        'project_info' => [
            'th' => "แจ้งข้อมูลการขอรับรองงานวิจัยของคุณ - {$data['request_id']}",
            'en' => "Your Research Accreditation Request Information - {$data['request_id']}"
        ],
        'submission_confirm' => [
            'th' => "ยืนยันการส่งคำขอรับรองงานวิจัย - {$data['request_id']}",
            'en' => "Research Accreditation Request Confirmation - {$data['request_id']}"
        ],
        'status_update' => [
            'th' => "อัปเดตสถานะคำขอรับรองงานวิจัย - {$data['request_id']}",
            'en' => "Research Accreditation Request Status Update - {$data['request_id']}"
        ]
    ];

    return $subjects[$type][$lang] ?? $subjects[$type]['th'];
}

/**
 * Get project info email body
 */
function getProjectInfoEmailBody($req, $lang = 'th')
{
    $baseUrl = "https://newbri.mcu.ac.th/app/rsa";
    $request_id = $req['request_id'];

    if ($lang === 'en') {
        return "
        <div style='font-family: \"Sarabun\", sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #003366 0%, #0052a3 100%); color: white; padding: 30px; text-align: center;'>
                <h2 style='margin: 0; font-size: 24px; letter-spacing: 0.5px;'>Research Project Information</h2>
                <p style='margin: 8px 0 0; opacity: 0.9; font-size: 14px;'>Buddhist Research Institute | Research Accreditation System</p>
            </div>
            
            <div style='padding: 30px;'>
                <p style='font-size: 16px;'>Dear <strong>{$req['name']}</strong>,</p>
                <p style='color: #666;'>The Buddhist Research Institute has sent you information about your research accreditation request. Please use the request ID below to track your current status:</p>
                
                <div style='background-color: #f0f7ff; border: 2px solid #003366; border-radius: 10px; padding: 20px; text-align: center; margin: 25px 0;'>
                    <span style='font-size: 13px; color: #003366; display: block; margin-bottom: 5px; font-weight: bold; text-transform: uppercase;'>Your Request ID</span>
                    <strong style='font-size: 32px; color: #d63384; letter-spacing: 3px; font-family: monospace;'>$request_id</strong>
                </div>

                <h3 style='color: #003366; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; font-size: 18px; margin-top: 30px;'>Research Project Information</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; width: 140px; color: #888;'>Submission Date:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-weight: bold;'>" . formatThaiDate($req['created_at']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Project Title:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-weight: bold; color: #003366;'>" . htmlspecialchars($req['project_title']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Applicant Name:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['name']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Affiliation:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['affiliation']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Academic Position:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['academic_position'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Field of Study:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['branch'] ?: '-') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Email:</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0;'>" . htmlspecialchars($req['email']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: #888;'>Plagiarism (%):</td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #f0f0f0; color: " . ($req['plagiarism_percent'] > 15 ? '#d32f2f' : '#388e3c') . "; font-weight: bold;'>{$req['plagiarism_percent']}%</td>
                    </tr>
                </table>

                <div style='margin-top: 40px; text-align: left;'>
                    <p style='font-size: 13px; color: #666; margin-bottom: 15px;'>You can check the progress of your request at any time through the link below:</p>
                    <a href='$baseUrl/status.php?id=$request_id&lang=en' 
                       style='background-color: #003366; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; box-shadow: 0 4px 12px rgba(0,51,102,0.2); font-size: 16px; text-align: center;'>
                       🔍 Check Status Here
                    </a>
                </div>
            </div>
            
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;'>
                <p style='margin: 0; font-size: 11px; color: #999;'>This is an automated email from the system. Please do not reply to this email.</p>
                <p style='margin: 5px 0 0; font-size: 11px; color: #999;'>&copy; " . date('Y') . " Buddhist Research Institute, Mahachulalongkornrajavidyalaya University</p>
            </div>
        </div>";
    }

    // Thai version (original)
    return "
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
}

/**
 * Get status update email body
 */
function getStatusUpdateEmailBody($request, $statusLabel, $statusColor, $correctionNote, $newStatus, $lang = 'th')
{
    $baseUrl = "https://newbri.mcu.ac.th/app/rsa";
    $request_id = $request['request_id'];
    $langParam = ($lang === 'en') ? '&lang=en' : '';

    if ($lang === 'en') {
        // English version
        $noteLabel = match ($newStatus) {
            'ch_edit' => 'Data Correction Note:',
            'correction' => 'Research Report Correction Note:',
            'rejected' => 'Rejection Reason:',
            default => 'Additional Note from Staff:'
        };

        $correctionSection = '';
        if (in_array($newStatus, ['correction', 'ch_edit', 'rejected']) && !empty($correctionNote)) {
            $correctionSection = "
                <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px;'>
                    <strong style='color: #856404; display: block; margin-bottom: 5px;'>$noteLabel</strong>
                    <div style='color: #666; font-size: 14px; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($correctionNote)) . "</div>
                </div>";
        }

        return "
            <div style='font-family: sans-serif; line-height: 1.6; color: #333;'>
                <div style='background: #f8f9fa; padding: 20px; border-bottom: 3px solid $statusColor;'>
                    <h2 style='margin: 0; color: $statusColor;'>Accreditation Request Status Update</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #dee2e6; border-top: none;'>
                    <p>Dear <strong>{$request['name']}</strong>,</p>
                    <p>Your request ID <strong>{$request_id}</strong> has been updated to:</p>
                    <div style='background: $statusColor; color: white; padding: 10px 20px; display: inline-block; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                        $statusLabel
                    </div>
                    $correctionSection
                    <p style='margin-top: 30px; text-align: left;'>
                        <a href='$baseUrl/status.php?id=$request_id$langParam' 
                           style='display: inline-block; padding: 12px 30px; background-color: #1a237e; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-size: 16px; min-width: 200px; text-align: center;'>
                           Check Status Here
                        </a>
                    </p>
                    <p style='margin-top: 20px; color: #666; font-size: 13px;'>
                        If the button above doesn't work, you can copy the link below and paste it into your browser:<br>
                        <span style='color: #1a237e; word-break: break-all;'>$baseUrl/status.php?id=$request_id$langParam</span>
                    </p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center;'>This is an automated notification from the Research Accreditation System, Buddhist Research Institute, MCU</p>
                </div>
            </div>";
    }

    // Thai version
    $noteLabel = match ($newStatus) {
        'ch_edit' => 'บันทึกแจ้งแก้ไขข้อมูล:',
        'correction' => 'บันทึกแจ้งแก้ไขรายงานวิจัย:',
        'rejected' => 'เหตุผลการไม่รับรอง:',
        default => 'บันทึกเพิ่มเติมจากเจ้าหน้าที่:'
    };

    $correctionSection = '';
    if (in_array($newStatus, ['correction', 'ch_edit', 'rejected']) && !empty($correctionNote)) {
        $correctionSection = "
            <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px;'>
                <strong style='color: #856404; display: block; margin-bottom: 5px;'>$noteLabel</strong>
                <div style='color: #666; font-size: 14px; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($correctionNote)) . "</div>
            </div>";
    }

    return "
        <div style='font-family: sans-serif; line-height: 1.6; color: #333;'>
            <div style='background: #f8f9fa; padding: 20px; border-bottom: 3px solid $statusColor;'>
                <h2 style='margin: 0; color: $statusColor;'>อัปเดตสถานะคำขอรับรอง</h2>
            </div>
            <div style='padding: 20px; border: 1px solid #dee2e6; border-top: none;'>
                <p>เรียน คุณ <strong>{$request['name']}</strong></p>
                <p>คำขอรหัส <strong>$request_id</strong> ของคุณมีการอัปเดตสถานะเป็น:</p>
                <div style='background: $statusColor; color: white; padding: 10px 20px; display: inline-block; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                    $statusLabel
                </div>
                $correctionSection
                <p style='margin-top: 30px; text-align: left;'>
                    <a href='$baseUrl/status.php?id=$request_id' 
                       style='display: inline-block; padding: 12px 30px; background-color: #1a237e; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-size: 16px; min-width: 200px; text-align: center;'>
                       หน้าตรวจสอบสถานะคลิกที่นี่
                    </a>
                </p>
                <p style='margin-top: 20px; color: #666; font-size: 13px;'>
                    หากปุ่มด้านบนไม่ทำงาน ท่านสามารถคัดลอกลิงก์ด้านล่างไปวางในบราวเซอร์ได้:<br>
                    <span style='color: #1a237e; word-break: break-all;'>$baseUrl/status.php?id=$request_id</span>
                </p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>นี่คือการแจ้งเตือนอัตโนมัติจากระบบขอรับรองงานวิจัย สถาบันวิจัยพุทธศาสตร์ มจร</p>
            </div>
        </div>";
}

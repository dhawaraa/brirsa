<?php
/**
 * Test Email Functionality
 */
require_once '../includes/mail_config.php';

echo "<h1>Email Test</h1>";

$to = "bri.it@mcu.ac.th";
$subject = "ทดสอบระบบอีเมล - " . date('Y-m-d H:i:s');
$body = "
    <h2>ทดสอบการส่งอีเมลระบบรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ</h2>
    <p>ขณะนี้ระบบอีเมลของคุณ (bri.it@mcu.ac.th) พร้อมใช้งานแล้วครับ</p>
    <p>ทดสอบส่งเมื่อ: " . date('d/m/Y H:i:s') . "</p>
    <hr>
    <p>สถาบันวิจัยพุทธศาสตร์ มจร</p>
";

echo "กำลังทดสอบส่งอีเมลไปยัง $to ...<br>";

if (sendMail($to, $subject, $body)) {
    echo "<h3 style='color: green;'>✅ ส่งอีเมลสำเร็จ! กรุณาตรวจสอบใน Inbox หรือ Spam ครับ</h3>";
} else {
    echo "<h3 style='color: red;'>❌ ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบ Error Log</h3>";
}

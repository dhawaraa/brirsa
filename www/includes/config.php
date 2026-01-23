<?php
/**
 * Database Configuration
 * ระบบขอรับรองงานวิจัย - สถาบันวิจัยพุทธศาสตร์ มจร
 */

// Database settings
define('DB_HOST', 'brirscer-db');
define('DB_NAME', 'brirscer_db');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');

// Site settings
define('SITE_NAME', 'ระบบขอรับรองงานวิจัย');
define('SITE_SUBTITLE', 'สถาบันวิจัยพุทธศาสตร์ | มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย');
define('LOGO_URL', 'https://newbri.mcu.ac.th/wp-content/uploads/2025/10/mcu-logo-Large-Phone.png');

// Upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// รายชื่อสังกัด
$AFFILIATIONS = [
    "คณะพุทธศาสตร์",
    "คณะครุศาสตร์",
    "คณะมนุษยศาสตร์",
    "คณะสังคมศาสตร์",
    "บัณฑิตวิทยาลัย",
    "วิทยาลัยพุทธศาสตร์นานาชาติ",
    "วิทยาลัยธรรมทูต",
    "สถาบันวิจัยพุทธศาสตร์",
    "วิทยาเขตหนองคาย",
    "วิทยาเขตนครศรีธรรมราช",
    "วิทยาเขตเชียงใหม่",
    "วิทยาเขตขอนแก่น",
    "วิทยาเขตนครราชสีมา",
    "วิทยาเขตอุบลราชธานี",
    "วิทยาเขตแพร่",
    "วิทยาเขตสุรินทร์",
    "วิทยาเขตพะเยา",
    "วิทยาเขตบาฬีศึกษาพุทธโฆส",
    "วิทยาเขตนครสวรรค์",
    "วิทยาเขตนครน่านเฉลิมพระเกียรติฯ",
    "วิทยาลัยสงฆ์ลำพูน",
    "วิทยาลัยสงฆ์เลย",
    "วิทยาลัยสงฆ์นครพนม",
    "วิทยาลัยสงฆ์พุทธชินราช",
    "วิทยาลัยสงฆ์บุรีรัมย์",
    "วิทยาลัยสงฆ์ปัตตานี",
    "วิทยาลัยสงฆ์พุทธโสธร",
    "วิทยาลัยสงฆ์เชียงราย",
    "วิทยาลัยสงฆ์นครลำปาง",
    "วิทยาลัยสงฆ์ศรีสะเกษ",
    "วิทยาลัยสงฆ์พุทธปัญญาศรีทวารวดี",
    "วิทยาลัยสงฆ์ชัยภูมิ",
    "วิทยาลัยสงฆ์พ่อขุนผาเมือง",
    "วิทยาลัยสงฆ์ร้อยเอ็ด",
    "วิทยาลัยสงฆ์ราชบุรี",
    "วิทยาลัยสงฆ์สุพรรณบุรีศรีสุวรรณภูมิ",
    "วิทยาลัยสงฆ์พิจิตร",
    "วิทยาลัยสงฆ์สุราษฎร์ธานี",
    "วิทยาลัยสงฆ์ระยอง",
    "วิทยาลัยสงฆ์มหาสารคาม",
    "วิทยาลัยสงฆ์เพชรบุรี",
    "วิทยาลัยสงฆ์อุทัยธานี",
    "วิทยาลัยสงฆ์กาญจนบุรี ศรีไพบุลย์",
    "วิทยาลัยสงฆ์ชลบุรี",
    "วิทยาลัยสงฆ์จันทบุรี",
    "วิทยาลัยสงฆ์กำแพงเพชร",
    "วิทยาลัยสงฆ์ตาก",
    "หน่วยวิทยบริการจังหวัดอุตรดิตถ์",
    "หน่วยวิทยบริการจังหวัดสระแก้ว",
    "หน่วยวิทยบริการจังหวัดสงขลา",
    "หน่วยวิทยบริการจังหวัดสมุทรสงคราม",
    "หน่วยวิทยบริการจังหวัดกาฬสินธุ์",
    "ห้องเรียนจังหวัดสิงห์บุรี",
    "มหาวชิราลงกรณบาลีเถรวาทราชวิทยาลัย"
];

// สถานะและสี
$STATUS_OPTIONS = [
    'checking' => ['label' => '1. เจ้าหน้าที่ตรวจสอบเอกสาร', 'color' => 'blue', 'icon' => 'file-text'],
    'committee' => ['label' => '2. เสนอกรรมการพิจารณา', 'color' => 'purple', 'icon' => 'users'],
    'correction' => ['label' => '3. ส่งกลับให้นักวิจัยแก้ไข', 'color' => 'orange', 'icon' => 'edit-3'],
    'resubmitted' => ['label' => '3.5 นักวิจัยส่งข้อมูลแก้ไขแล้ว', 'color' => 'blue', 'icon' => 'refresh-cw'],
    'rejected' => ['label' => '4. ไม่รับรอง', 'color' => 'red', 'icon' => 'x'],
    'approved' => ['label' => '5. อนุมัติรับรอง', 'color' => 'green', 'icon' => 'check-circle']
];

// Database connection
function getDB()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Generate unique request ID
function generateRequestId()
{
    return 'RSA-' . strtoupper(substr(uniqid(), -6));
}

// Format Thai date
function formatThaiDate($date)
{
    $timestamp = strtotime($date);
    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $day = date('j', $timestamp);
    $month = $thaiMonths[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    return "$day $month $year";
}

// Sanitize input
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

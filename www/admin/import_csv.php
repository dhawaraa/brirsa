<?php
/**
 * CSV Import for Historical Data
 * นำเข้าข้อมูลย้อนหลังจาก Google Sheets
 */
$pageTitle = "นำเข้าข้อมูล CSV";
include 'includes/header.php';

$pdo = getDB();

// Access Control
check_role('super_admin');

// Status mapping from old system to new system
$statusMapping = [
    'เจ้าหน้าที่ตรวจสอบเอกสาร' => 'checking',
    'เสนอคณะกรรมการพิจารณา' => 'committee',
    'นักวิจัยดำเนินการแก้ไข' => 'correction',
    'อนุมัติออกใบรับรอง' => 'approved'
];

$step = $_GET['step'] ?? 'upload';
$previewData = [];
$stats = ['total' => 0, 'valid' => 0, 'duplicate' => 0, 'invalid' => 0];
$errors = [];
$success = '';
$error = '';

// Step 2: Process uploaded CSV and show preview
if ($step === 'preview' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        $step = 'upload';
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $error = "ไฟล์มีขนาดใหญ่เกิน 10MB";
        $step = 'upload';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $error = "กรุณาอัปโหลดไฟล์ .csv เท่านั้น";
        $step = 'upload';
    } else {
        // Set locale to handle Thai characters and UTF-8 CSV
        setlocale(LC_ALL, 'th_TH.UTF-8');

        // Parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            // Read header
            $headers = fgetcsv($handle);

            // Helper function to normalize strings for comparison (remove all whitespace, dots, etc.)
            function normalizeForCompare($str)
            {
                // Remove all whitespace (including non-breaking spaces) and common separators
                $str = preg_replace('/[\s\x{00A0}]+/', '', $str);
                return mb_strtolower($str, 'UTF-8');
            }

            // Get existing name+project combinations to check duplicates
            // We'll fetch all and normalize them in PHP for better matching
            $existingCombosRaw = $pdo->query("SELECT name, project_title FROM requests")->fetchAll(PDO::FETCH_ASSOC);
            $existingCombos = [];
            foreach ($existingCombosRaw as $row) {
                $existingCombos[] = normalizeForCompare($row['name']) . '|||' . normalizeForCompare($row['project_title']);
            }

            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $stats['total']++;

                // Map CSV columns to array
                $data = array_combine($headers, $row);

                // Validate and prepare data
                $record = [
                    'row' => $rowNum,
                    'request_id' => trim($data['form_id'] ?? ''),
                    'name' => trim($data['ชื่อ-นามสกุล'] ?? ''),
                    'fiscal_year' => trim($data['ปีงบประมาณ'] ?? ''),
                    'project_title' => trim($data['ชื่อโครงการวิจัย'] ?? ''),
                    'affiliation' => trim($data['สังกัด'] ?? ''),
                    'academic_position' => trim($data['ตำแหน่งทางวิชาการที่ขอ'] ?? ''),
                    'branch' => trim($data['สาขาที่ขอ'] ?? ''),
                    'tel' => trim($data['เบอร์โทรศัพท์'] ?? ''),
                    'email' => trim($data['อีเมล'] ?? ''),
                    'journal_name' => trim($data['ชื่อวารสารที่ตีพิมพ์'] ?? ''),
                    'plagiarism_percent' => trim($data['จำนวน % ของการคัดลอก (ไม่เกิน 15%)'] ?? ''),
                    'status_text' => trim($data['สถานะดำเนินการ'] ?? ''),
                    'created_at' => trim($data['วันที่ส่งข้อมูล'] ?? ''),
                    'file_report' => trim($data['แนบไฟล์รายงานวิจัยฉบับสมบูรณ์ (PDF)'] ?? $data['แนบไฟล์รายงานวิจัยฉบับสมบูรณณ์ (PDF)'] ?? ''),
                    'file_self_assess' => trim($data['แนบไฟล์แบบประเมินตนเองสำหรับการยื่นขอรับรองงานวิจัย'] ?? ''),
                    'file_sp10' => trim($data['แนบไฟล์บันทึกข้อความขอรับรองงานวิจัย สพ.10'] ?? ''),
                    'file_sp10_1' => trim($data['แนบไฟล์แบบฟอร์มตรวจเอกสาร สพ.10.1'] ?? ''),
                    'file_cert' => trim($data['แนบไฟล์เอกสารรับรองการตีพิมพ์ผลงาน'] ?? ''),
                    'file_plagiarism' => trim($data['แนบหลักฐานตรวจสอบการคัดลอกผลงานวิจัย (อักขราวิสุทธิ์)'] ?? ''),
                    'file_participation' => trim($data['แนบไฟล์หลักฐานการมีส่วนร่วมในงานวิจัย'] ?? $data['7. หลักฐานการมีส่วนร่วมในงานวิจัย'] ?? ''),
                    'file_rs_cert' => trim($data['ดาวน์โหลดใบรับรอง'] ?? ''),
                    'valid' => true,
                    'error_msg' => ''
                ];

                // Map status (Default to checking if blank)
                if (empty($record['status_text'])) {
                    $record['status'] = 'checking';
                    $record['status_text'] = 'เจ้าหน้าที่ตรวจสอบเอกสาร'; // For display in preview
                } else {
                    $record['status'] = $statusMapping[$record['status_text']] ?? 'checking';
                }

                // Validate required fields (only name and project_title)
                if (empty($record['name']) || empty($record['project_title'])) {
                    $record['valid'] = false;
                    $record['error_msg'] = 'ขาดชื่อหรือชื่อโครงการ';
                    $stats['invalid']++;
                }

                // Check duplicate by normalized name + project_title combination
                $currentCombo = normalizeForCompare($record['name']) . '|||' . normalizeForCompare($record['project_title']);
                if (in_array($currentCombo, $existingCombos)) {
                    $record['valid'] = false;
                    $record['error_msg'] = 'ชื่อ+โครงการซ้ำ (Skip)';
                    $stats['duplicate']++;
                } elseif ($record['valid']) {
                    $stats['valid']++;
                    // Add this combo to the list to catch duplicates within the same CSV file
                    $existingCombos[] = $currentCombo;
                }

                $previewData[] = $record;
            }
            fclose($handle);

            // Store preview data in session
            $_SESSION['csv_preview'] = $previewData;
            $_SESSION['csv_stats'] = $stats;
        }
    }
}

// Step 3: Confirm and import
if ($step === 'import' && isset($_SESSION['csv_preview'])) {
    $previewData = $_SESSION['csv_preview'];
    $imported = 0;
    $skipped = 0;
    $failed = 0;
    $failedDetails = [];

    foreach ($previewData as $record) {
        if (!$record['valid']) {
            $skipped++;
            continue;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO requests (
                    request_id, name, project_title, fiscal_year, affiliation,
                    academic_position, branch, tel, email, journal_name,
                    plagiarism_percent, status, created_at,
                    file_report, file_self_assess, file_sp10, file_sp10_1,
                    file_cert, file_plagiarism, file_participation, file_rs_cert
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Handle NOT NULL constraints for blank CSV data
            $reqId = $record['request_id'] ?: 'RSA-' . strtoupper(substr(uniqid(), -8));
            $projTitle = $record['project_title'] ?: '(ไม่ได้ระบุชื่อโครงการ)';
            $fYear = $record['fiscal_year'] ?: date('Y') + 543;
            $affil = $record['affiliation'] ?: 'ไม่ระบุ';
            $tel = $record['tel'] ?: '-';
            $email = $record['email'] ?: 'no-email@example.com';

            // Format date for MySQL
            $createdAt = $record['created_at'];
            if ($createdAt) {
                $timestamp = strtotime(str_replace('/', '-', $createdAt));
                if ($timestamp) {
                    $createdAt = date('Y-m-d H:i:s', $timestamp);
                } else {
                    $createdAt = date('Y-m-d H:i:s');
                }
            } else {
                $createdAt = date('Y-m-d H:i:s');
            }

            $stmt->execute([
                $reqId,
                $record['name'],
                $projTitle,
                $fYear,
                $affil,
                $record['academic_position'],
                $record['branch'],
                $tel,
                $email,
                $record['journal_name'],
                $record['plagiarism_percent'] ?: null,
                $record['status'],
                $createdAt,
                $record['file_report'] ?: null,
                $record['file_self_assess'] ?: null,
                $record['file_sp10'] ?: null,
                $record['file_sp10_1'] ?: null,
                $record['file_cert'] ?: null,
                $record['file_plagiarism'] ?: null,
                $record['file_participation'] ?: null,
                $record['file_rs_cert'] ?: null
            ]);

            $imported++;
        } catch (Exception $e) {
            $failed++;
            $failedDetails[] = "แถว " . $record['row'] . ": " . $e->getMessage();
        }
    }

    $success = "นำเข้าข้อมูลสำเร็จ: {$imported} รายการ | ข้าม: {$skipped} รายการ" . ($failed > 0 ? " | ล้มเหลว: {$failed} รายการ" : "");
    if (!empty($failedDetails)) {
        $_SESSION['import_errors'] = array_slice($failedDetails, 0, 10); // Show first 10 errors
    }

    // Clear session
    unset($_SESSION['csv_preview']);
    unset($_SESSION['csv_stats']);
    $step = 'upload';
}

// Load preview from session if exists
if ($step === 'preview' && isset($_SESSION['csv_preview'])) {
    $previewData = $_SESSION['csv_preview'];
    $stats = $_SESSION['csv_stats'];
}
?>

<div class="mb-6">
    <h3 class="text-2xl font-bold dark:text-white">นำเข้าข้อมูล CSV</h3>
    <p class="text-gray-500 dark:text-gray-400">นำเข้าข้อมูลรายการยื่นขอรับรองย้อนหลังจาก Google Sheets</p>
</div>

<?php if ($success): ?>
    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
        <div class="font-bold"><?php echo $success; ?></div>
        <?php if (!empty($_SESSION['import_errors'])): ?>
            <div class="mt-2 text-xs">
                <p class="font-bold">ตัวอย่างข้อผิดพลาด:</p>
                <ul class="list-disc list-inside">
                    <?php foreach ($_SESSION['import_errors'] as $msg): ?>
                        <li><?php echo htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-1">...และอื่นๆ</p>
            </div>
            <?php unset($_SESSION['import_errors']); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($step === 'upload'): ?>
    <!-- Step 1: Upload Form -->
    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <h4 class="text-lg font-bold mb-4 dark:text-white">อัปโหลดไฟล์ CSV</h4>

        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <h5 class="font-bold text-blue-800 dark:text-blue-300 mb-2">📋 คำแนะนำการเตรียมไฟล์:</h5>
            <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-2 list-disc list-inside">
                <li>Export ข้อมูลจาก Google Sheets เป็นไฟล์ <strong>.csv (Comma Separated Values)</strong></li>
                <li><strong>แถวแรก (Header)</strong> ต้องมีชื่อคอลัมน์ตรงตามนี้ทุกประการ (เรียงลำดับอย่างไรก็ได้):
                    <div
                        class="mt-2 p-3 bg-gray-900 text-gray-300 rounded overflow-x-auto font-mono text-[10px] leading-relaxed">
                        form_id, วันที่ส่งข้อมูล, ชื่อ-นามสกุล, ปีงบประมาณ, ชื่อโครงการวิจัย, สังกัด,
                        ตำแหน่งทางวิชาการที่ขอ, สาขาที่ขอ, เบอร์โทรศัพท์, อีเมล, ชื่อวารสารที่ตีพิมพ์, จำนวน % ของการคัดลอก
                        (ไม่เกิน 15%), สถานะดำเนินการ, แนบไฟล์รายงานวิจัยฉบับสมบูรณ์ (PDF),
                        แนบไฟล์แบบประเมินตนเองสำหรับการยื่นขอรับรองงานวิจัย, แนบไฟล์บันทึกข้อความขอรับรองงานวิจัย สพ.10,
                        แนบไฟล์แบบฟอร์มตรวจเอกสาร สพ.10.1, แนบไฟล์เอกสารรับรองการตีพิมพ์ผลงาน,
                        แนบหลักฐานตรวจสอบการคัดลอกผลงานวิจัย (อักขราวิสุทธิ์), แนบไฟล์หลักฐานการมีส่วนร่วมในงานวิจัย,
                        ดาวน์โหลดใบรับรอง
                    </div>
                </li>
                <li>ระบบจะ Skip รายการที่มี <strong>ชื่อ + ชื่อโครงการ</strong> ซ้ำกับในฐานข้อมูลโดยอัตโนมัติ</li>
                <li>ฟิลด์ <strong>สถานะดำเนินการ</strong> ต้องเป็นค่าใดค่าหนึ่งดังนี้:
                    <span class="font-bold underline">เจ้าหน้าที่ตรวจสอบเอกสาร</span>,
                    <span class="font-bold underline">เสนอคณะกรรมการพิจารณา</span>,
                    <span class="font-bold underline">นักวิจัยดำเนินการแก้ไข</span> หรือ
                    <span class="font-bold underline">อนุมัติออกใบรับรอง</span>
                </li>
            </ul>
        </div>

        <form method="POST" action="?step=preview" enctype="multipart/form-data" class="space-y-4">
            <?php csrf_field(); ?>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="csv_file">
                    เลือกไฟล์ CSV
                </label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required
                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไฟล์ CSV (ขนาดไม่เกิน 10MB)</p>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    อัปโหลดและ Preview
                </button>
                <a href="requests.php"
                    class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>

<?php elseif ($step === 'preview' && !empty($previewData)): ?>
    <!-- Step 2: Preview Data -->
    <div class="mb-4 p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <h4 class="text-lg font-bold mb-3 dark:text-white">สถิติข้อมูล</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?php echo $stats['total']; ?>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</div>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    <?php echo $stats['valid']; ?>
                </div>
                <div class="text-xs text-green-600 dark:text-green-400">พร้อม Import</div>
            </div>
            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    <?php echo $stats['duplicate']; ?>
                </div>
                <div class="text-xs text-yellow-600 dark:text-yellow-400">ซ้ำ (Skip)</div>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    <?php echo $stats['invalid']; ?>
                </div>
                <div class="text-xs text-red-600 dark:text-red-400">ข้อมูลไม่ครบ</div>
            </div>
        </div>
    </div>

    <div class="mb-4 p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <h4 class="text-lg font-bold mb-4 dark:text-white">ตัวอย่างข้อมูล (
            <?php echo count($previewData); ?> รายการ)
        </h4>

        <div class="relative overflow-x-auto max-h-96">
            <table class="w-full text-xs text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th class="px-3 py-2">รหัส</th>
                        <th class="px-3 py-2">ชื่อ-นามสกุล</th>
                        <th class="px-3 py-2">โครงการ</th>
                        <th class="px-3 py-2">ปีงบ</th>
                        <th class="px-3 py-2">สถานะ</th>
                        <th class="px-3 py-2">ผลการตรวจสอบ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData as $idx => $record): ?>
                        <tr
                            class="border-b dark:border-gray-700 <?php echo $record['valid'] ? 'bg-white dark:bg-gray-800' : 'bg-red-50 dark:bg-red-900/10'; ?>">
                            <td class="px-3 py-2">
                                <?php echo $idx + 1; ?>
                            </td>
                            <td class="px-3 py-2 font-mono">
                                <?php echo htmlspecialchars($record['request_id']); ?>
                            </td>
                            <td class="px-3 py-2">
                                <?php echo htmlspecialchars($record['name']); ?>
                            </td>
                            <td class="px-3 py-2 max-w-xs truncate"
                                title="<?php echo htmlspecialchars($record['project_title']); ?>">
                                <?php echo htmlspecialchars($record['project_title']); ?>
                            </td>
                            <td class="px-3 py-2">
                                <?php echo htmlspecialchars($record['fiscal_year']); ?>
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    class="text-xs px-2 py-1 rounded <?php echo $record['valid'] ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo htmlspecialchars($record['status_text']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <?php if ($record['valid']): ?>
                                    <span class="text-green-600 dark:text-green-400">✓ พร้อม</span>
                                <?php else: ?>
                                    <span class="text-red-600 dark:text-red-400">✗
                                        <?php echo $record['error_msg']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex gap-2">
        <form method="POST" action="?step=import">
            <?php csrf_field(); ?>
            <button type="submit"
                class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700">
                ยืนยันการ Import (
                <?php echo $stats['valid']; ?> รายการ)
            </button>
        </form>
        <a href="import_csv.php"
            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
            ยกเลิก
        </a>
    </div>

<?php endif; ?>

<?php
include 'includes/footer.php';
?>
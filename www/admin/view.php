<?php
/**
 * Admin Request Details V2
 */
$pageTitle = "ตรวจสอบข้อมูลคำขอ";

require_once '../includes/config.php';

$id = intval($_GET['id'] ?? 0);
$pdo = getDB();

// Fetch request data
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: requests.php');
    exit;
}

$message = '';
$messageType = '';

// Handle POST actions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_status':
                $newStatus = sanitize($_POST['status'] ?? '');
                $correctionNote = sanitize($_POST['correction_note'] ?? '');

                if ($newStatus === 'correction' && empty($correctionNote)) {
                    throw new Exception('กรุณาระบุบันทึกแจ้งแก้ไขก่อนเปลี่ยนสถานะ');
                }

                $stmt = $pdo->prepare("UPDATE requests SET status = ?, correction_note = ? WHERE id = ?");
                $stmt->execute([$newStatus, $correctionNote, $id]);

                // Send Email Notification
                require_once '../includes/mail_config.php';
                $statusLabel = $STATUS_OPTIONS[$newStatus]['label'] ?? $newStatus;
                $subject = "อัปเดตสถานะคำขอรับรองงานวิจัย - รหัส {$request['request_id']} ($statusLabel)";

                $statusColor = $STATUS_OPTIONS[$newStatus]['color'] === 'blue' ? '#003366' :
                    ($STATUS_OPTIONS[$newStatus]['color'] === 'green' ? '#28a745' :
                        ($STATUS_OPTIONS[$newStatus]['color'] === 'orange' ? '#fd7e14' :
                            ($STATUS_OPTIONS[$newStatus]['color'] === 'purple' ? '#6f42c1' :
                                ($STATUS_OPTIONS[$newStatus]['color'] === 'red' ? '#dc3545' : '#6c757d'))));

                $body = "
                    <div style='font-family: sans-serif; line-height: 1.6; color: #333;'>
                        <div style='background: #f8f9fa; padding: 20px; border-bottom: 3px solid $statusColor;'>
                            <h2 style='margin: 0; color: $statusColor;'>อัปเดตสถานะคำขอรับรอง</h2>
                        </div>
                        <div style='padding: 20px; border: 1px solid #dee2e6; border-top: none;'>
                            <p>เรียน คุณ <strong>{$request['name']}</strong></p>
                            <p>คำขอรหัส <strong>{$request['request_id']}</strong> ของคุณมีการอัปเดตสถานะเป็น:</p>
                            <div style='background: $statusColor; color: white; padding: 10px 20px; display: inline-block; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                                $statusLabel
                            </div>";

                if ($newStatus === 'correction' && !empty($correctionNote)) {
                    $body .= "
                            <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px;'>
                                <strong style='color: #856404;'>บันทึกเพิ่มเติมจากเจ้าหน้าที่:</strong><br>
                                " . nl2br($correctionNote) . "
                            </div>";
                }

                $body .= "
                            <p style='margin-top: 25px;'>
                                ท่านสามารถตรวจสอบรายละเอียดเพิ่มเติมและดาวน์โหลดเอกสาร (ถ้ามี) ได้ที่:<br>
                                <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}/status.php' 
                                   style='color: #1a237e; text-decoration: underline; font-weight: bold;'>
                                   หน้าตรวจสอบสถานะคลิกที่นี่
                                </a>
                            </p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 25px 0;'>
                            <p style='font-size: 12px; color: #666;'>นี่คือการแจ้งเตือนอัตโนมัติจากระบบขอรับรองงานวิจัย สถาบันวิจัยพุทธศาสตร์ มจร</p>
                        </div>
                    </div>";

                sendMail($request['email'], $subject, $body);

                $message = 'อัปเดตสถานะเรียบร้อยแล้ว';
                $messageType = 'success';
                break;

            case 'upload_cert':
            case 'upload_correction':
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = UPLOAD_DIR . $request['request_id'] . '/';
                    if (!file_exists($uploadDir))
                        mkdir($uploadDir, 0755, true);

                    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                    $filename = ($action === 'upload_cert' ? 'cert_' : 'correction_') . time() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;

                    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                        $dbPath = 'uploads/' . $request['request_id'] . '/' . $filename;
                        $sql = $action === 'upload_cert' ? "UPDATE requests SET file_rs_cert = ? WHERE id = ?" : "UPDATE requests SET file_correction = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$dbPath, $id]);
                        $message = 'อัปโหลดไฟล์สำเร็จ';
                        $messageType = 'success';
                    }
                }
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: requests.php?deleted=1');
                exit;
        }
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    $messageType = 'danger';
}

// Refresh data
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

$statusInfo = $STATUS_OPTIONS[$request['status']] ?? $STATUS_OPTIONS['checking'];

include 'includes/header.php';
?>

<div class="mb-4">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
            <li class="inline-flex items-center">
                <a href="dashboard.php"
                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <a href="requests.php"
                        class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">รายการยื่นขอ</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 9 4-4-4-4" />
                    </svg>
                    <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                        <?php echo $request['request_id']; ?>
                    </span>
                </div>
            </li>
        </ol>
    </nav>
</div>

<?php if ($message): ?>
    <div class="p-4 mb-4 text-sm text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-800 rounded-lg bg-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-50 dark:bg-gray-800 dark:text-<?php echo $messageType === 'success' ? 'green' : 'red'; ?>-400"
        role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: Request Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4 border-b pb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลโครงการวิจัย</h3>
                <?php
                $colorClass = match ($statusInfo['color']) {
                    'blue' => 'bg-blue-100 text-blue-800',
                    'green' => 'bg-green-100 text-green-800',
                    'orange' => 'bg-orange-100 text-orange-800',
                    'red' => 'bg-red-100 text-red-800',
                    'purple' => 'bg-purple-100 text-purple-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                ?>
                <span class="text-sm font-medium px-2.5 py-0.5 rounded <?php echo $colorClass; ?>">
                    <?php echo $statusInfo['label']; ?>
                </span>
            </div>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">รหัสคำขอ</dt>
                    <dd class="text-lg font-bold text-mcu-blue dark:text-blue-500 font-mono">
                        <?php echo $request['request_id']; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">วันที่ส่ง</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo formatThaiDate($request['created_at']); ?>
                    </dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อโครงการวิจัย</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">
                        <?php echo htmlspecialchars($request['project_title']); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล ผู้ขอ</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($request['name']); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">สังกัด</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($request['affiliation']); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">อีเมล</dt>
                    <dd class="text-blue-600 dark:text-blue-400 underline">
                        <?php echo $request['email']; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">โทรศัพท์</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo $request['tel']; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เปอร์เซ็นต์การคัดลอก (Plagiarism)
                    </dt>
                    <dd
                        class="font-bold <?php echo $request['plagiarism_percent'] > 15 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo $request['plagiarism_percent']; ?> %
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Files Section -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เอกสารประกอบการยื่นขอ</h3>
            <div class="space-y-3">
                <?php
                $files = [
                    'file_report' => '1. รายงานวิจัยฉบับสมบูรณ์',
                    'file_self_assess' => '2. แบบประเมินตนเอง',
                    'file_sp10' => '3. บันทึกข้อความ สวพ.10',
                    'file_sp10_1' => '4. แบบฟอร์มตรวจ สวพ.10.1',
                    'file_cert' => '5. เอกสารรับรองการตีพิมพ์',
                    'file_plagiarism' => '6. หลักฐานตรวจอักขราวิสุทธิ์',
                    'file_participation' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย'
                ];
                foreach ($files as $field => $label):
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z">
                                </path>
                                <path d="M3 8V4a1 1 0 011-1h2v10H4a1 1 0 01-1-1V8z"></path>
                            </svg>
                            <span class="text-sm text-gray-900 dark:text-white">
                                <?php echo $label; ?>
                            </span>
                        </div>
                        <?php if ($request[$field]): ?>
                            <a href="../<?php echo $request[$field]; ?>" target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">เปิดไฟล์</a>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 italic">ไม่ได้อัปโหลด</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Management -->
    <div class="space-y-6">
        <!-- Status Update -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ดำเนินการอัปเดตสถานะ</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">เปลี่ยนสถานะเป็น</label>
                    <select name="status" id="statusSelect"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <?php foreach ($STATUS_OPTIONS as $key => $opt): ?>
                            <option value="<?php echo $key; ?>" <?php echo $request['status'] === $key ? 'selected' : ''; ?>>
                                <?php echo $opt['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="correctionArea"
                    class="mb-4 <?php echo $request['status'] === 'correction' ? '' : 'hidden'; ?>">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">บันทึกแจ้งแก้ไข
                        (ส่งถึงนักวิจัย)</label>
                    <textarea name="correction_note" rows="3"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($request['correction_note'] ?? ''); ?></textarea>
                </div>
                <button type="submit"
                    class="w-full text-white bg-mcu-blue hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">
                    บันทึกสถานะ
                </button>
            </form>
        </div>

        <!-- Admin File Upload -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">แนบไฟล์จากเจ้าหน้าที่</h3>

            <!-- Certificate -->
            <div class="mb-6 pb-6 border-b dark:border-gray-700">
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">ใบรับรองงานวิจัย (Official)</p>
                <?php if ($request['file_rs_cert']): ?>
                    <div class="flex items-center justify-between mb-2 p-2 bg-green-50 rounded border border-green-200">
                        <span class="text-xs text-green-700 truncate max-w-[150px]">
                            <?php echo basename($request['file_rs_cert']); ?>
                        </span>
                        <a href="../<?php echo $request['file_rs_cert']; ?>" target="_blank"
                            class="text-blue-600 text-xs">เปิดดู</a>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_cert">
                    <input type="file" name="file" accept=".pdf"
                        class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 mb-2"
                        required>
                    <button type="submit"
                        class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-xs px-3 py-2">อัปโหลด</button>
                </form>
            </div>

            <!-- Correction Letter -->
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">เอกสารแจ้งแก้ไข (ถ้ามี)</p>
                <?php if ($request['file_correction']): ?>
                    <div class="flex items-center justify-between mb-2 p-2 bg-orange-50 rounded border border-orange-200">
                        <span class="text-xs text-orange-700 truncate max-w-[150px]">
                            <?php echo basename($request['file_correction']); ?>
                        </span>
                        <a href="../<?php echo $request['file_correction']; ?>" target="_blank"
                            class="text-blue-600 text-xs">เปิดดู</a>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_correction">
                    <input type="file" name="file" accept=".pdf"
                        class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 mb-2"
                        required>
                    <button type="submit"
                        class="text-white bg-orange-500 hover:bg-orange-600 font-medium rounded-lg text-xs px-3 py-2">อัปโหลด</button>
                </form>
            </div>
        </div>

        <!-- Delete Action -->
        <div class="p-6 bg-red-50 border border-red-200 rounded-lg shadow-sm dark:bg-red-900/20 dark:border-red-800">
            <h3 class="text-sm font-bold text-red-800 dark:text-red-400 mb-2">Danger Zone</h3>
            <p class="text-xs text-red-600 dark:text-red-300 mb-4">การลบข้อมูลนี้จะไม่สามารถย้อนกลับได้</p>
            <form method="POST" onsubmit="return confirm('ยืนยันการลบคำขอนี้?');">
                <input type="hidden" name="action" value="delete">
                <button type="submit"
                    class="w-full text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    ลบคำขอนี้
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('statusSelect').addEventListener('change', function () {
        const area = document.getElementById('correctionArea');
        if (this.value === 'correction') {
            area.classList.remove('hidden');
        } else {
            area.classList.add('hidden');
        }
    });
</script>

<?php
include 'includes/footer.php';
?>
```
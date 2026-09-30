<?php
/**
 * Admin Request Details V2
 */
$pageTitle = "ตรวจสอบข้อมูลคำขอ";

require_once '../includes/config.php';

// Access Control
check_role(['super_admin', 'data_admin']);

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

if (isset($_GET['updated'])) {
    $message = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
    if (isset($_GET['preset_status']) && $_GET['preset_status'] === 'approved') {
        $message = 'อัปโหลดใบรับรองสำเร็จ! กรุณากดปุ่ม "บันทึกสถานะ" เพื่อเสร็จสิ้นขั้นตอนการอนุมัติ';
    }
    $messageType = 'success';
}

// Handle POST actions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_status':
                $newStatus = sanitize($_POST['status'] ?? '');
                $correctionNote = sanitize($_POST['correction_note'] ?? '');

                if (in_array($newStatus, ['correction', 'ch_edit', 'rejected']) && empty($correctionNote)) {
                    $label = match ($newStatus) {
                        'ch_edit' => 'บันทึกแจ้งแก้ไขข้อมูล',
                        'correction' => 'บันทึกแจ้งแก้ไขรายงานวิจัย',
                        'rejected' => 'เหตุผล',
                        default => 'บันทึกแจ้งแก้ไข'
                    };
                    throw new Exception("กรุณาระบุ $label ก่อนเปลี่ยนสถานะ");
                }

                if ($newStatus === 'approved' && empty($request['file_rs_cert'])) {
                    throw new Exception("ไม่สามารถอนุมัติได้: กรุณาอัปโหลดใบรับรองงานวิจัยก่อน");
                }

                if (isset($_FILES['correction_file']) && $_FILES['correction_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = UPLOAD_DIR . $request['request_id'] . '/';
                    if (!file_exists($uploadDir))
                        mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['correction_file']['name'], PATHINFO_EXTENSION);
                    $filename = 'correction_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['correction_file']['tmp_name'], $targetPath)) {
                        $dbPath = 'uploads/' . $request['request_id'] . '/' . $filename;
                        $stmt = $pdo->prepare("UPDATE requests SET file_correction = ? WHERE id = ?");
                        $stmt->execute([$dbPath, $id]);
                    }
                }

                $stmt = $pdo->prepare("UPDATE requests SET status = ?, correction_note = ? WHERE id = ?");
                $stmt->execute([$newStatus, $correctionNote, $id]);

                // Send Email Notification
                require_once '../includes/mail_config.php';
                require_once '../includes/mail_helper.php';

                // Detect language from applicant name
                $lang = detectLanguageFromName($request['name']);

                // Get status label based on language
                $statusLabel = ($lang === 'en' && isset($STATUS_OPTIONS[$newStatus]['label_en']))
                    ? $STATUS_OPTIONS[$newStatus]['label_en']
                    : ($STATUS_OPTIONS[$newStatus]['label'] ?? $newStatus);

                // Get bilingual subject
                $subject = getEmailSubject('status_update', $lang, ['request_id' => $request['request_id']]);
                $subject .= " ($statusLabel)";

                $statusColor = $STATUS_OPTIONS[$newStatus]['color'] === 'blue' ? '#003366' :
                    ($STATUS_OPTIONS[$newStatus]['color'] === 'green' ? '#28a745' :
                        ($STATUS_OPTIONS[$newStatus]['color'] === 'orange' ? '#fd7e14' :
                            ($STATUS_OPTIONS[$newStatus]['color'] === 'purple' ? '#6f42c1' :
                                ($STATUS_OPTIONS[$newStatus]['color'] === 'red' ? '#dc3545' : '#6c757d'))));

                // Get bilingual email body
                $body = getStatusUpdateEmailBody($request, $statusLabel, $statusColor, $correctionNote, $newStatus, $lang);

                // Set sender name based on language
                $fromName = ($lang === 'en') ? 'Buddhist Research Institute, MCU' : 'สถาบันวิจัยพุทธศาสตร์ มจร';

                sendMail($request['email'], $subject, $body, $fromName);

                header("Location: view.php?id=$id&updated=1");
                exit;
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
                        $redirectUrl = "view.php?id=$id&updated=1";
                        if ($action === 'upload_cert') {
                            $redirectUrl .= "&preset_status=approved";
                        }
                        header("Location: $redirectUrl");
                        exit;
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
                        ตรวจสอบ: <?php echo $request['request_id']; ?>
                    </span>
                </div>
            </li>
        </ol>
    </nav>
    <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="checklist_view.php?id=<?php echo $id; ?>"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            แบบตรวจรับรอง (Checklist V2)
        </a>
        <a href="edit.php?id=<?php echo $id; ?>"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors">
            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m14.3 4.8 2.9 2.9M7 7H4a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h11c.6 0 1-.4 1-1v-4.5m2.4-10a2 2 0 0 1 0 3l-6.8 6.8L8 14l.7-3.6 6.9-6.8a2 2 0 0 1 2.8 0Z" />
            </svg>
            แก้ไขข้อมูล
        </a>
    </div>
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
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ตำแหน่งทางวิชาการที่ขอ</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($request['academic_position'] ?: '-'); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">สาขาที่ขอ</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($request['branch'] ?: '-'); ?>
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
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">วารสารที่ตีพิมพ์</dt>
                    <dd class="text-gray-900 dark:text-white">
                        <?php echo htmlspecialchars($request['journal_name'] ?: '-'); ?>
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

                <!-- Research Contributors Section -->
                <div class="md:col-span-2 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <dt class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        สัดส่วนการมีส่วนร่วมในงานวิจัย
                    </dt>
                    <dd class="text-gray-900 dark:text-white mt-1">
                        <?php
                        $contributors = json_decode($request['contributors'] ?? '[]', true);
                        if (empty($contributors)):
                        ?>
                            <span class="text-sm text-gray-500 italic">ไม่มีข้อมูลผู้ร่วมวิจัย</span>
                        <?php else: ?>
                            <div class="relative overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 font-bold whitespace-nowrap">ชื่อผู้ร่วมงาน</th>
                                            <th scope="col" class="px-4 py-3 font-bold text-center whitespace-nowrap">สัดส่วนร้อยละ</th>
                                            <th scope="col" class="px-4 py-3 font-bold whitespace-nowrap">บทบาท</th>
                                            <th scope="col" class="px-4 py-3 font-bold">รายละเอียดบทบาท/หน้าที่</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <?php foreach ($contributors as $c): ?>
                                            <tr class="bg-white dark:bg-gray-800">
                                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                    <?php echo htmlspecialchars($c['name']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-center font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                                    <?php echo $c['percent']; ?> %
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($c['role'] ?? '') === 'หัวหน้าโครงการวิจัย' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                                        <?php echo htmlspecialchars($c['role'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-normal break-words">
                                                    <?php echo htmlspecialchars($c['description'] ?? '-'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
                    'file_cert' => '5. บทความวิจัยที่ได้รับการตีพิมพ์',
                    'file_plagiarism' => '6. หลักฐานตรวจอักขราวิสุทธิ์',
                    'file_participation' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย',
                    'file_ethics' => '8. เอกสารรับรองจริยธรรมการวิจัย',
                    'file_explanation' => '9. ไฟล์ชี้แจงการแก้ไข'
                ];
                foreach ($files as $field => $label):
                    if ($field === 'file_explanation' && empty($request[$field]))
                        continue;
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
                            <?php
                            $fileUrl = ensureSecureUrl($request[$field]);
                            if (!preg_match('~^(?:f|ht)tps?://~i', $fileUrl)) {
                                $fileUrl = '../' . $fileUrl;
                            }
                            ?>
                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank"
                                class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                เปิดอ่านออนไลน์
                            </a>
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
            <form method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update_status">
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">เปลี่ยนสถานะเป็น</label>
                    <select name="status" id="statusSelect"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <?php
                        $presetStatus = $_GET['preset_status'] ?? '';
                        foreach ($STATUS_OPTIONS as $key => $opt):
                            $isSelected = ($presetStatus === $key) || (empty($presetStatus) && $request['status'] === $key);
                            ?>
                            <option value="<?php echo $key; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                <?php echo $opt['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="correctionArea"
                    class="mb-4 <?php echo in_array($request['status'], ['correction', 'ch_edit', 'rejected']) ? '' : 'hidden'; ?>">
                    <label id="noteLabel" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        <?php
                        echo match ($request['status']) {
                            'ch_edit' => 'บันทึกแจ้งแก้ไขข้อมูล (ส่งถึงนักวิจัย)',
                            'correction' => 'บันทึกแจ้งแก้ไขรายงานวิจัย (ส่งถึงนักวิจัย)',
                            'rejected' => 'เหตุผล (ส่งถึงนักวิจัย)',
                            default => 'บันทึกแจ้งแก้ไข (ส่งถึงนักวิจัย)'
                        };
                        ?>
                    </label>
                    <textarea name="correction_note" rows="3"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($request['correction_note'] ?? ''); ?></textarea>

                    <!-- Moved Correction Upload (Only for Status 3) -->
                    <div id="correctionUploadArea"
                        class="mt-4 <?php echo ($request['status'] === 'correction') ? '' : 'hidden'; ?>">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">เอกสารแจ้งแก้ไขรายงานวิจัย
                            (ถ้ามี)</p>
                        <?php if ($request['file_correction']): ?>
                            <div
                                class="flex items-center justify-between mb-2 p-2 bg-orange-50 rounded border border-orange-200">
                                <span class="text-[10px] text-orange-700 truncate max-w-[150px]">
                                    <?php echo basename($request['file_correction']); ?>
                                </span>
                                <?php
                                $corrUrl = ensureSecureUrl($request['file_correction']);
                                if (!preg_match('~^(?:f|ht)tps?://~i', $corrUrl)) {
                                    $corrUrl = '../' . $corrUrl;
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($corrUrl); ?>" target="_blank"
                                    class="text-blue-600 text-[10px] font-bold">เปิดอ่าน</a>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="correction_file" accept=".pdf"
                            class="block w-full text-[10px] text-gray-900 border border-gray-300 rounded cursor-pointer bg-gray-50 focus:outline-none mb-2">
                        <p class="text-[9px] text-gray-400">* ไฟล์จะถูกอัปโหลดพร้อมกับการบันทึกสถานะ</p>
                    </div>
                </div>
                <div id="certWarning" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs text-red-600 font-bold flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        กรุณาอัปโหลดใบรับรองก่อนทำการอนุมัติ
                    </p>
                </div>
                <button type="submit" id="saveStatusBtn"
                    class="w-full text-white bg-mcu-blue hover:bg-blue-800 disabled:bg-gray-400 disabled:cursor-not-allowed focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">
                    บันทึกสถานะ
                </button>
            </form>
        </div>

        <!-- Admin File Upload (Certificate Only) -->
        <div id="adminCertArea"
            class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 <?php echo ($request['status'] === 'approved') ? '' : 'hidden'; ?>">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ออกใบรับรอง</h3>

            <!-- Certificate -->
            <div class="mb-4">
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">ใบรับรองงานวิจัย (Official)</p>
                <?php if ($request['file_rs_cert']): ?>
                    <div class="flex items-center justify-between mb-2 p-2 bg-green-50 rounded border border-green-200">
                        <span class="text-xs text-green-700 truncate max-w-[150px]">
                            <?php echo basename($request['file_rs_cert']); ?>
                        </span>
                        <?php
                        $certUrl = ensureSecureUrl($request['file_rs_cert']);
                        if (!preg_match('~^(?:f|ht)tps?://~i', $certUrl)) {
                            $certUrl = '../' . $certUrl;
                        }
                        ?>
                        <a href="<?php echo htmlspecialchars($certUrl); ?>" target="_blank"
                            class="text-blue-600 text-xs font-bold">เปิดอ่านออนไลน์</a>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="upload_cert">
                    <input type="file" name="file" accept=".pdf"
                        class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 mb-2"
                        required>
                    <button type="submit"
                        class="text-white bg-green-600 hover:bg-green-700 font-medium rounded-lg text-xs px-3 py-2">อัปโหลดใบรับรอง</button>
                </form>
            </div>
        </div>

        <!-- Delete Action -->
        <div class="p-6 bg-red-50 border border-red-200 rounded-lg shadow-sm dark:bg-red-900/20 dark:border-red-800">
            <h3 class="text-sm font-bold text-red-800 dark:text-red-400 mb-2">Danger Zone</h3>
            <p class="text-xs text-red-600 dark:text-red-300 mb-4">การลบข้อมูลนี้จะไม่สามารถย้อนกลับได้</p>
            <form method="POST" onsubmit="return confirm('ยืนยันการลบคำขอนี้?');">
                <?php csrf_field(); ?>
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
    const hasCert = <?php echo !empty($request['file_rs_cert']) ? 'true' : 'false'; ?>;

    function updateStatusUI() {
        const statusSelect = document.getElementById('statusSelect');
        const area = document.getElementById('correctionArea');
        const label = document.getElementById('noteLabel');
        const upload = document.getElementById('correctionUploadArea');
        const certArea = document.getElementById('adminCertArea');
        const warning = document.getElementById('certWarning');
        const saveBtn = document.getElementById('saveStatusBtn');

        const val = statusSelect.value;

        // Handling Note Area and Labels
        if (['correction', 'ch_edit', 'rejected'].includes(val)) {
            area.classList.remove('hidden');
            if (val === 'ch_edit') {
                label.textContent = 'บันทึกแจ้งแก้ไขข้อมูล (ส่งถึงนักวิจัย)';
                upload.classList.add('hidden');
            } else if (val === 'correction') {
                label.textContent = 'บันทึกแจ้งแก้ไขรายงานวิจัย (ส่งถึงนักวิจัย)';
                upload.classList.remove('hidden');
            } else if (val === 'rejected') {
                label.textContent = 'เหตุผล (ส่งถึงนักวิจัย)';
                upload.classList.add('hidden');
            }
            certArea.classList.add('hidden');
            warning.classList.add('hidden');
            saveBtn.disabled = false;
        } else {
            area.classList.add('hidden');
            if (val === 'approved') {
                certArea.classList.remove('hidden');
                if (!hasCert) {
                    warning.classList.remove('hidden');
                    saveBtn.disabled = true;
                } else {
                    warning.classList.add('hidden');
                    saveBtn.disabled = false;
                }
            } else {
                certArea.classList.add('hidden');
                warning.classList.add('hidden');
                saveBtn.disabled = false;
            }
        }
    }

    document.getElementById('statusSelect').addEventListener('change', updateStatusUI);
    // Initialize UI on load
    updateStatusUI();
</script>

<?php
include 'includes/footer.php';
?>
```
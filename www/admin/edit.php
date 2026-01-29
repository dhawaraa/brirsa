<?php
/**
 * Admin Edit Request - ระบบขอรับรองงานวิจัย
 */
$pageTitle = "แก้ไขข้อมูลคำขอ";

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_request') {
    try {
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

        // Update basic info
        $sql = "UPDATE requests SET 
                name = ?, project_title = ?, fiscal_year = ?, affiliation = ?, 
                academic_position = ?, branch = ?, tel = ?, email = ?, 
                journal_name = ?, plagiarism_percent = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
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
            $id
        ]);

        // Handle File Uploads
        $uploadDir = UPLOAD_DIR . $request['request_id'] . '/';
        if (!file_exists($uploadDir))
            mkdir($uploadDir, 0755, true);

        $fileFields = [
            'file_report',
            'file_self_assess',
            'file_sp10',
            'file_sp10_1',
            'file_cert',
            'file_plagiarism',
            'file_participation',
            'file_rs_cert',
            'file_correction'
        ];

        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $filename = $field . '_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
                    $dbPath = 'uploads/' . $request['request_id'] . '/' . $filename;
                    $stmt = $pdo->prepare("UPDATE requests SET $field = ? WHERE id = ?");
                    $stmt->execute([$dbPath, $id]);
                }
            }
        }

        $message = "บันทึกข้อมูลเรียบร้อยแล้ว";
        $messageType = "success";

        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

    } catch (Exception $e) {
        $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $messageType = "danger";
    }
}

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
                    <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">แก้ไข:
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

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <input type="hidden" name="action" value="edit_request">

    <div class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-start">
        <!-- Left: Basic Info & Actions (70%) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Project Info -->
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b pb-4">ข้อมูลโครงการและผู้ขอ
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อโครงการวิจัย</label>
                        <textarea name="project_title" rows="2" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($request['project_title']); ?></textarea>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($request['name']); ?>"
                            required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">สังกัด</label>
                        <select name="affiliation" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <?php foreach ($AFFILIATIONS as $aff): ?>
                                <option value="<?php echo htmlspecialchars($aff); ?>" <?php echo $request['affiliation'] === $aff ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aff); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ปีงบประมาณ</label>
                        <input type="text" name="fiscal_year"
                            value="<?php echo htmlspecialchars($request['fiscal_year']); ?>" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ตำแหน่งวิชาการ</label>
                        <input type="text" name="academic_position"
                            value="<?php echo htmlspecialchars($request['academic_position']); ?>"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">สาขา</label>
                        <input type="text" name="branch" value="<?php echo htmlspecialchars($request['branch']); ?>"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">เบอร์โทรศัพท์</label>
                        <input type="text" name="tel" value="<?php echo htmlspecialchars($request['tel']); ?>" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">อีเมล</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($request['email']); ?>"
                            required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อวารสารที่ตีพิมพ์</label>
                        <input type="text" name="journal_name"
                            value="<?php echo htmlspecialchars($request['journal_name']); ?>"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">เปอร์เซ็นต์การคัดลอก
                            (%)</label>
                        <input type="number" step="0.01" name="plagiarism_percent"
                            value="<?php echo $request['plagiarism_percent']; ?>"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Save Action Block -->
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <button type="submit"
                        class="w-full md:w-auto text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-bold rounded-lg text-sm px-10 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 shadow-lg transition-all transform hover:scale-[1.02]">
                        บันทึกการเปลี่ยนแปลงทั้งหมด
                    </button>
                    <a href="requests.php"
                        class="text-sm text-gray-500 hover:text-gray-700 hover:underline">ยกเลิกและย้อนกลับ</a>
                </div>
                <div
                    class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg dark:bg-gray-700 dark:border-gray-600">
                    <p class="text-[11px] text-orange-800 dark:text-orange-300 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <strong>หมายเหตุ:</strong> การแก้ไขข้อมูลในหน้านี้จะมีผลโดยตรงต่อฐานข้อมูล
                        โปรดตรวจสอบความถูกต้องก่อนกดบันทึก
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Files Section (30%) -->
        <div class="lg:col-span-3 space-y-6 text-xs">
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ไฟล์เอกสารประกอบ</h3>
                <div class="space-y-4">
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
                        <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700 border dark:border-gray-600">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <?php echo $label; ?>
                            </label>
                            <div class="flex items-center justify-between gap-2">
                                <input type="file" name="<?php echo $field; ?>" accept=".pdf"
                                    class="block w-full text-[10px] text-gray-900 border border-gray-300 rounded cursor-pointer bg-white dark:text-gray-400 focus:outline-none dark:bg-gray-800 dark:border-gray-600">
                                <?php if ($request[$field]): ?>
                                    <a href="../<?php echo $request[$field]; ?>" target="_blank"
                                        class="shrink-0 p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200"
                                        title="ดูไฟล์ปัจจุบัน">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
<?php
/**
 * หน้าแก้ไขข้อมูล/ส่งไฟล์แก้ไข - ระบบขอรับรองงานวิจัย
 */
require_once 'includes/config.php';

$requestId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';

if (!$requestId || !$email) {
    header('Location: status.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE request_id = ? AND email = ?");
    $stmt->execute([$requestId, $email]);
    $request = $stmt->fetch();

    if (!$request) {
        die('ไม่พบข้อมูลคำขอ หรืออีเมลไม่ถูกต้อง');
    }

    $allowed_statuses = ['correction', 'ch_edit'];
    if (!in_array($request['status'], $allowed_statuses)) {
        die('สถานะปัจจุบันไม่สามารถแก้ไขข้อมูลได้ (หรืออยู่นอกระยะเวลาที่กำหนด)');
    }

    // Status 3 (correction) is Report Only. Status 1.5 (ch_edit) is Full Edit.
    $isReportOnly = ($request['status'] === 'correction');

} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');
}

$pageTitle = 'แก้ไขข้อมูล/ส่งไฟล์แก้ไข';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Success Modal -->
        <div id="successModal"
            class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4 hidden animate-fade-in">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative">
                <div class="bg-blue-600 h-2"></div>
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="check-circle" class="w-12 h-12 text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">อัปเดตข้อมูลสำเร็จ!</h3>
                    <p class="text-gray-600 mb-6">ระบบได้รับข้อมูลที่แก้ไขของท่านเรียบร้อยแล้ว
                        และจะดำเนินการตรวจสอบให้อีกครั้ง</p>
                    <a href="status.php?id=<?php echo $requestId; ?>"
                        class="block w-full py-3 bg-mcu-blue text-white font-bold rounded-lg hover:bg-opacity-90 text-center">
                        กลับไปหน้าตรวจสอบสถานะ
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading Modal -->
        <div id="loadingModal"
            class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4 hidden">
            <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-sm text-center">
                <div class="mb-4 flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-mcu-blue"></div>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">กำลังอัปเดตข้อมูล...</h3>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                    <div id="progressBar" class="bg-mcu-blue h-2.5 rounded-full transition-all duration-300"
                        style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-xs text-gray-400 text-right">0%</p>
            </div>
        </div>

        <!-- Correction Note -->
        <?php if ($request['status'] === 'correction'): ?>
            <div class="mb-8 bg-orange-50 border-l-4 border-orange-500 p-6 rounded-r-xl shadow-sm">
                <h3 class="text-orange-800 font-bold flex items-center mb-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                    สิ่งที่ต้องแก้ไข (จากเจ้าหน้าที่)
                </h3>
                <div class="text-gray-700 bg-white p-4 rounded border border-orange-100 whitespace-normal text-left">
                    <?php echo nl2br(htmlspecialchars(trim($request['correction_note']))); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="bg-mcu-blue px-8 py-6 text-white flex justify-between items-center">
                <h2 class="text-xl font-bold flex items-center">
                    <i data-lucide="edit" class="w-6 h-6 mr-3"></i>
                    แก้ไขข้อมูลและส่งไฟล์ใหม่
                </h2>
                <span class="text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full uppercase font-mono">
                    ID:
                    <?php echo $requestId; ?>
                </span>
            </div>

            <form id="editForm" action="api/update.php" method="POST" enctype="multipart/form-data"
                class="p-8 space-y-6">
                <?php csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">

                <!-- Personal Info -->
                <?php if (!$isReportOnly): ?>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name" required class="input-field"
                                value="<?php echo htmlspecialchars($request['name']); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโครงการวิจัย <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="project_title" required class="input-field"
                                value="<?php echo htmlspecialchars($request['project_title']); ?>">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ปีงบประมาณ <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="fiscal_year" required class="input-field"
                                value="<?php echo htmlspecialchars($request['fiscal_year']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สังกัด <span
                                    class="text-red-500">*</span></label>
                            <select name="affiliation" required class="input-field">
                                <?php foreach ($AFFILIATIONS as $aff): ?>
                                    <option value="<?php echo htmlspecialchars($aff); ?>" <?php echo $request['affiliation'] === $aff ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($aff); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่งทางวิชาการที่ขอ</label>
                            <select name="academic_position" class="input-field">
                                <option value="">-- เลือก --</option>
                                <option <?php echo $request['academic_position'] === 'ผู้ช่วยศาสตราจารย์' ? 'selected' : ''; ?>>ผู้ช่วยศาสตราจารย์</option>
                                <option <?php echo $request['academic_position'] === 'รองศาสตราจารย์' ? 'selected' : ''; ?>>
                                    รองศาสตราจารย์</option>
                                <option <?php echo $request['academic_position'] === 'ศาสตราจารย์' ? 'selected' : ''; ?>>
                                    ศาสตราจารย์</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สาขาที่ขอ</label>
                            <input type="text" name="branch" class="input-field"
                                value="<?php echo htmlspecialchars($request['branch']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์ <span
                                    class="text-red-500">*</span></label>
                            <input type="tel" name="tel" required class="input-field"
                                value="<?php echo htmlspecialchars($request['tel']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล <span
                                    class="text-red-500">*</span></label>
                            <input type="email" name="email" required class="input-field bg-gray-50"
                                value="<?php echo htmlspecialchars($request['email']); ?>" readonly>
                            <p class="text-xs text-gray-400 mt-1">* ไม่สามารถแก้ไขอีเมลได้</p>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($request['email']); ?>">
                <?php endif; ?>

                <!-- File Uploads -->
                <div class="border-t pt-6">
                    <h3 class="font-bold text-gray-700 mb-2 flex items-center">
                        <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                        เอกสารแนบ (อัปโหลดใหม่เพื่อเปลี่ยนไฟล์)
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">* หากไม่ต้องการเปลี่ยนไฟล์เดิม ให้ปล่อยว่างไว้</p>
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                        <?php
                        $fileFields = $isReportOnly ? [
                            ['name' => 'file_report', 'label' => '1. รายงานวิจัยฉบับสมบูรณ์ (PDF)'],
                            ['name' => 'file_explanation', 'label' => '2. แนบไฟล์ชี้แจงการแก้ไข (PDF)']
                        ] : [
                            ['name' => 'file_report', 'label' => '1. รายงานวิจัยฉบับสมบูรณ์ (PDF)'],
                            ['name' => 'file_self_assess', 'label' => '2. แบบประเมินตนเอง'],
                            ['name' => 'file_sp10', 'label' => '3. บันทึกข้อความ สวพ.10'],
                            ['name' => 'file_sp10_1', 'label' => '4. แบบฟอร์มตรวจ สวพ.10.1'],
                            ['name' => 'file_cert', 'label' => '5. เอกสารรับรองการตีพิมพ์'],
                            ['name' => 'file_plagiarism', 'label' => '6. หลักฐานตรวจอักขราวิสุทธิ์'],
                            ['name' => 'file_participation', 'label' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย']
                        ];
                        foreach ($fileFields as $field):
                            $exists = !empty($request[$field['name']]);
                            ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo $field['label']; ?>
                                    <?php if (!$exists): ?>
                                        <span class="text-red-500">*</span>
                                    <?php endif; ?>
                                </label>
                                <div class="flex items-center file-group">
                                    <label
                                        class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-600 px-3 py-2 rounded-l hover:bg-gray-100 text-sm whitespace-nowrap flex items-center">
                                        <i data-lucide="cloud-upload" class="w-4 h-4 mr-2"></i>
                                        เลือกไฟล์ใหม่
                                        <input type="file" name="<?php echo $field['name']; ?>" accept=".pdf" class="hidden"
                                            onchange="updateFileName(this)">
                                    </label>
                                    <div
                                        class="flex-1 border border-l-0 border-gray-300 py-2 px-3 bg-white text-gray-400 text-sm truncate rounded-r file-name">
                                        ไม่ได้เลือกไฟล์ใหม่
                                    </div>
                                </div>
                                <?php if ($exists): ?>
                                    <p class="text-[10px] mt-1 truncate text-gray-400">
                                        ไฟล์ปัจจุบัน:
                                        <?php echo basename($request[$field['name']]); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Journal Info -->
                <?php if (!$isReportOnly): ?>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวารสารที่ตีพิมพ์ <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="journal_name" required class="input-field"
                                value="<?php echo htmlspecialchars($request['journal_name']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">จำนวน % การคัดลอก (ไม่เกิน 15%)
                                <span class="text-red-500">*</span></label>
                            <input type="number" name="plagiarism_percent" required max="15" step="0.01" class="input-field"
                                value="<?php echo htmlspecialchars($request['plagiarism_percent']); ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Agreement & Captcha -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="flex items-start gap-3 mb-4">
                        <input type="checkbox" name="agreement" id="agreement" required
                            class="mt-1 w-4 h-4 text-mcu-blue rounded">
                        <label for="agreement" class="text-sm text-gray-700">
                            ข้าพเจ้าตรวจสอบการแก้ไขเรียบร้อยแล้ว และยืนยันข้อมูลที่ปรับปรุงเป็นความจริง
                        </label>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-gray-200 px-4 py-2 rounded font-bold">
                            <span id="captchaNum1"></span> + <span id="captchaNum2"></span> = ?
                        </div>
                        <input type="number" id="captchaAnswer" class="w-24 p-2 border rounded text-center"
                            placeholder="คำตอบ" required>
                        <input type="hidden" id="captchaExpected" name="captcha_expected">
                        <button type="button" onclick="generateCaptcha()" class="p-2 text-gray-500 hover:text-gray-700">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3.5 rounded-lg font-bold text-white shadow-lg bg-mcu-blue hover:bg-opacity-90 transition-colors flex items-center justify-center">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    บันทึกและส่งการแก้ไข
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    function updateFileName(input) {
        const fileGroup = input.closest('.file-group');
        const fileNameDisplay = fileGroup.querySelector('.file-name');
        const fileName = input.files[0]?.name || 'ไม่ได้เลือกไฟล์ใหม่';

        fileNameDisplay.textContent = fileName;

        if (input.files[0]) {
            fileNameDisplay.classList.remove('text-gray-400');
            fileNameDisplay.classList.add('text-gray-900');
        } else {
            fileNameDisplay.classList.remove('text-gray-900');
            fileNameDisplay.classList.add('text-gray-400');
        }

        if (input.files[0] && input.files[0].type !== 'application/pdf') {
            alert('กรุณาเลือกไฟล์ PDF เท่านั้น');
            input.value = '';
            fileNameDisplay.textContent = 'ไม่ได้เลือกไฟล์ใหม่';
            fileNameDisplay.classList.remove('text-gray-900');
            fileNameDisplay.classList.add('text-gray-400');
        }
    }

    let captchaA, captchaB;
    function generateCaptcha() {
        captchaA = Math.floor(Math.random() * 10);
        captchaB = Math.floor(Math.random() * 10);
        document.getElementById('captchaNum1').textContent = captchaA;
        document.getElementById('captchaNum2').textContent = captchaB;
        document.getElementById('captchaExpected').value = captchaA + captchaB;
        document.getElementById('captchaAnswer').value = '';
    }
    generateCaptcha();

    document.getElementById('editForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const answer = parseInt(document.getElementById('captchaAnswer').value);
        const expected = parseInt(document.getElementById('captchaExpected').value);
        if (answer !== expected) {
            alert('เลขยืนยันตัวตนไม่ถูกต้อง');
            generateCaptcha();
            return;
        }

        const loadingModal = document.getElementById('loadingModal');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        loadingModal.classList.remove('hidden');

        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            if (progress > 90) progress = 90;
            progressBar.style.width = progress + '%';
            progressText.textContent = progress + '%';
        }, 300);

        try {
            const formData = new FormData(this);
            const response = await fetch('api/update_request.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            clearInterval(interval);

            if (result.success) {
                progressBar.style.width = '100%';
                progressText.textContent = '100%';
                setTimeout(() => {
                    loadingModal.classList.add('hidden');
                    document.getElementById('successModal').classList.remove('hidden');
                }, 500);
            } else {
                loadingModal.classList.add('hidden');
                alert('เกิดข้อผิดพลาด: ' + result.message);
            }
        } catch (error) {
            clearInterval(interval);
            loadingModal.classList.add('hidden');
            alert('เกิดข้อผิดพลาด: ' + error.message);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
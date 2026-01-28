<?php
/**
 * หน้าส่งข้อมูลขอรับรอง - ระบบขอรับรองงานวิจัย
 */
require_once 'includes/config.php';
$pageTitle = 'ส่งข้อมูลขอรับรอง';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Success Modal (hidden by default) -->
        <div id="successModal"
            class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4 hidden animate-fade-in">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative">
                <div class="bg-green-600 h-2"></div>
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="check-circle" class="w-12 h-12 text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">ดำเนินการสำเร็จ!</h3>
                    <p class="text-gray-600 mb-4">ระบบได้บันทึกข้อมูลเรียบร้อยแล้ว</p>
                    <p class="text-sm text-gray-500 mb-4">รหัสคำขอของคุณ: <strong id="requestIdDisplay"
                            class="text-mcu-blue"></strong></p>
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 text-left">
                        <h4 class="text-blue-800 font-bold text-sm mb-2">สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล:</h4>
                        <ul class="text-blue-700 text-xs space-y-1 list-decimal list-inside mb-2">
                            <li>ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม</li>
                            <li>พร้อมบันทึกข้อความ สวพ.10 จำนวน 1 ฉบับ</li>
                        </ul>
                        <p class="text-blue-700 text-xs font-semibold">มายัง สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย
                            จ.พระนครศรีอยุธยา</p>
                    </div>
                    <a id="successStatusLink" href="status.php"
                        class="block w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 text-center">
                        ตรวจสอบสถานะ
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
                <h3 class="text-lg font-bold text-gray-800 mb-2">กำลังส่งข้อมูล...</h3>
                <p class="text-sm text-gray-500 mb-4">กรุณารอสักครู่ ห้ามปิดหน้านี้</p>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                    <div id="progressBar" class="bg-mcu-blue h-2.5 rounded-full transition-all duration-300"
                        style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-xs text-gray-400 text-right">0%</p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="bg-mcu-blue px-8 py-6 text-white">
                <h2 class="text-xl font-bold flex items-center">
                    <i data-lucide="send" class="w-6 h-6 mr-3"></i>
                    แบบฟอร์มขอใบรับรองงานวิจัย
                </h2>
            </div>

            <form id="registrationForm" action="api/submit.php" method="POST" enctype="multipart/form-data"
                class="p-8 space-y-6">
                <!-- Personal Info -->
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ชื่อ-นามสกุล <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required class="input-field" placeholder="เช่น พระมหาสมชาย สมใจ">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ชื่อโครงการวิจัย <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="project_title" required class="input-field"
                            placeholder="ระบุชื่อโครงการวิจัย">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ปีงบประมาณ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="fiscal_year" required class="input-field" placeholder="เช่น 2568">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            สังกัด <span class="text-red-500">*</span>
                        </label>
                        <select name="affiliation" required class="input-field">
                            <option value="">-- เลือกสังกัด --</option>
                            <?php foreach ($AFFILIATIONS as $aff): ?>
                                <option value="<?php echo htmlspecialchars($aff); ?>">
                                    <?php echo htmlspecialchars($aff); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ตำแหน่งทางวิชาการที่ขอ
                        </label>
                        <select name="academic_position" class="input-field">
                            <option value="">-- เลือก --</option>
                            <option>ผู้ช่วยศาสตราจารย์</option>
                            <option>รองศาสตราจารย์</option>
                            <option>ศาสตราจารย์</option>
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สาขาที่ขอ</label>
                        <input type="text" name="branch" class="input-field" placeholder="เช่น พระพุทธศาสนา">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="tel" required class="input-field" placeholder="เช่น 081-234-5678">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required class="input-field" placeholder="example@mcu.ac.th">
                    </div>
                </div>

                <!-- File Uploads -->
                <div class="border-t pt-6">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                        <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                        เอกสารแนบ (ต้องเป็นไฟล์ PDF เท่านั้น)
                    </h3>
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                        <?php
                        $fileFields = [
                            ['name' => 'file_report', 'label' => '1. รายงานวิจัยฉบับสมบูรณ์'],
                            ['name' => 'file_self_assess', 'label' => '2. แบบประเมินตนเอง'],
                            ['name' => 'file_sp10', 'label' => '3. บันทึกข้อความ สวพ.10'],
                            ['name' => 'file_sp10_1', 'label' => '4. แบบฟอร์มตรวจ สวพ.10.1'],
                            ['name' => 'file_cert', 'label' => '5. เอกสารรับรองการตีพิมพ์'],
                            ['name' => 'file_plagiarism', 'label' => '6. หลักฐานตรวจอักขราวิสุทธิ์'],
                            ['name' => 'file_participation', 'label' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย']
                        ];
                        foreach ($fileFields as $field):
                            ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo $field['label']; ?> <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center file-group">
                                    <label
                                        class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-600 px-3 py-2 rounded-l hover:bg-gray-100 text-sm whitespace-nowrap flex items-center">
                                        <i data-lucide="cloud-upload" class="w-4 h-4 mr-2"></i>
                                        เลือกไฟล์ (PDF)
                                        <input type="file" name="<?php echo $field['name']; ?>" accept=".pdf" required
                                            class="hidden" onchange="updateFileName(this)">
                                    </label>
                                    <div
                                        class="flex-1 border border-l-0 border-gray-300 py-2 px-3 bg-white text-gray-400 text-sm truncate rounded-r file-name">
                                        ยังไม่เลือกไฟล์
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Journal Info -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ชื่อวารสารที่ตีพิมพ์ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="journal_name" required class="input-field"
                            placeholder="ระบุชื่อวารสาร">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            จำนวน % การคัดลอก (ไม่เกิน 15%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="plagiarism_percent" required max="15" step="0.01" class="input-field"
                            placeholder="เช่น 12.50">
                    </div>
                </div>

                <!-- Agreement & Captcha -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <!-- Instruction Box -->
                    <div class="bg-red-50 border-2 border-red-500 p-4 rounded-lg mb-6">
                        <h4 class="text-red-700 font-bold mb-2 flex items-center">
                            <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                            สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล
                        </h4>
                        <ul class="text-red-600 text-sm space-y-1 list-decimal list-inside mb-2">
                            <li>ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม</li>
                            <li>พร้อมบันทึกข้อความ สวพ.10 จำนวน 1 ฉบับ</li>
                        </ul>
                        <p class="text-red-600 text-sm font-semibold">มายัง สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย
                            จ.พระนครศรีอยุธยา</p>
                    </div>

                    <div class="flex items-start gap-3 mb-4">
                        <input type="checkbox" name="agreement" id="agreement" required
                            class="mt-1 w-4 h-4 text-mcu-pink rounded">
                        <label for="agreement" class="text-sm text-gray-700 cursor-pointer">
                            ข้าพเจ้าได้ดำเนินการตรวจสอบเอกสารข้างต้นเรียบร้อยแล้ว
                            จึงขอนำส่งข้อมูลเพื่อให้สถาบันวิจัยพุทธศาสตร์รับรองผลงานวิจัยต่อไป
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
                    class="w-full py-3.5 rounded-lg font-bold text-white shadow-lg bg-mcu-pink hover:bg-pink-700 transition-colors flex items-center justify-center">
                    <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                    ยืนยันการส่งข้อมูล
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    // Update file name display
    function updateFileName(input) {
        const fileGroup = input.closest('.file-group');
        const fileNameDisplay = fileGroup.querySelector('.file-name');
        const fileName = input.files[0]?.name || 'ยังไม่เลือกไฟล์';

        fileNameDisplay.textContent = fileName;

        if (input.files[0]) {
            fileNameDisplay.classList.remove('text-gray-400');
            fileNameDisplay.classList.add('text-gray-900');
        } else {
            fileNameDisplay.classList.remove('text-gray-900');
            fileNameDisplay.classList.add('text-gray-400');
        }

        // Validate PDF
        if (input.files[0] && input.files[0].type !== 'application/pdf') {
            alert('กรุณาเลือกไฟล์ PDF เท่านั้น');
            input.value = '';
            fileNameDisplay.textContent = 'ยังไม่เลือกไฟล์';
            fileNameDisplay.classList.remove('text-gray-900');
            fileNameDisplay.classList.add('text-gray-400');
        }
    }

    // Captcha
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

    // Form submission
    document.getElementById('registrationForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Validate captcha
        const answer = parseInt(document.getElementById('captchaAnswer').value);
        const expected = parseInt(document.getElementById('captchaExpected').value);
        if (answer !== expected) {
            alert('เลขยืนยันตัวตนไม่ถูกต้อง');
            generateCaptcha();
            return;
        }

        // Show loading
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
            const response = await fetch('api/submit.php', {
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
                    document.getElementById('requestIdDisplay').textContent = result.request_id;
                    document.getElementById('successStatusLink').href = 'status.php?id=' + result.request_id;
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
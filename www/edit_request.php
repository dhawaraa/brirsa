<?php
/**
 * หน้าแก้ไขข้อมูล/ส่งไฟล์แก้ไข - ระบบรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ
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
$noIndex = true;
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
                    <h3 class="text-2xl font-bold text-gray-900 mb-2" data-th="อัปเดตข้อมูลสำเร็จ!"
                        data-en="Update Successful!">อัปเดตข้อมูลสำเร็จ!</h3>
                    <p class="text-gray-600 mb-6"
                        data-th="ระบบได้รับข้อมูลที่แก้ไขของท่านเรียบร้อยแล้ว และจะดำเนินการตรวจสอบให้อีกครั้ง"
                        data-en="Your updated information has been received and will be reviewed again.">
                        ระบบได้รับข้อมูลที่แก้ไขของท่านเรียบร้อยแล้ว
                        และจะดำเนินการตรวจสอบให้อีกครั้ง</p>
                    <a href="status.php?id=<?php echo $requestId; ?>"
                        class="block w-full py-3 bg-mcu-blue text-white font-bold rounded-lg hover:bg-opacity-90 text-center"
                        data-th="กลับไปหน้าตรวจสอบสถานะ" data-en="Back to Status Page">
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
                <h3 class="text-lg font-bold text-gray-800 mb-2" data-th="กำลังอัปเดตข้อมูล..." data-en="Updating...">
                    กำลังอัปเดตข้อมูล...</h3>
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
                    <span data-th="สิ่งที่ต้องแก้ไข (จากเจ้าหน้าที่)"
                        data-en="Required Corrections (from Staff)">สิ่งที่ต้องแก้ไข (จากเจ้าหน้าที่)</span>
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
                    <span data-th="แก้ไขข้อมูลและส่งไฟล์ใหม่"
                        data-en="Edit Information and Upload New Files">แก้ไขข้อมูลและส่งไฟล์ใหม่</span>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="ชื่อ-นามสกุล"
                                    data-en="Full Name">ชื่อ-นามสกุล</span> <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required class="input-field"
                                value="<?php echo htmlspecialchars($request['name']); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="ชื่อโครงการวิจัย"
                                    data-en="Research Project Title">ชื่อโครงการวิจัย</span> <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="project_title" required class="input-field"
                                value="<?php echo htmlspecialchars($request['project_title']); ?>">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="ปีงบประมาณ"
                                    data-en="Fiscal Year">ปีงบประมาณ</span> <span class="text-red-500">*</span></label>
                            <input type="text" name="fiscal_year" required class="input-field"
                                value="<?php echo htmlspecialchars($request['fiscal_year']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="สังกัด"
                                    data-en="Affiliation">สังกัด</span> <span class="text-red-500">*</span></label>
                            <select name="affiliation" required class="input-field">
                                <?php foreach ($AFFILIATIONS as $aff): ?>
                                    <option value="<?php echo htmlspecialchars($aff); ?>" <?php echo $request['affiliation'] === $aff ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($aff); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span
                                    data-th="ตำแหน่งทางวิชาการที่ขอ"
                                    data-en="Applied Academic Position">ตำแหน่งทางวิชาการที่ขอ</span></label>
                            <select name="academic_position" class="input-field">
                                <option value="" data-th="-- เลือก --" data-en="-- Select --">-- เลือก --</option>
                                <option value="ผู้ช่วยศาสตราจารย์" data-th="ผู้ช่วยศาสตราจารย์"
                                    data-en="Assistant Professor" <?php echo $request['academic_position'] === 'ผู้ช่วยศาสตราจารย์' ? 'selected' : ''; ?>>
                                    ผู้ช่วยศาสตราจารย์</option>
                                <option value="รองศาสตราจารย์" data-th="รองศาสตราจารย์" data-en="Associate Professor" <?php echo $request['academic_position'] === 'รองศาสตราจารย์' ? 'selected' : ''; ?>>
                                    รองศาสตราจารย์</option>
                                <option value="ศาสตราจารย์" data-th="ศาสตราจารย์" data-en="Professor" <?php echo $request['academic_position'] === 'ศาสตราจารย์' ? 'selected' : ''; ?>>
                                    ศาสตราจารย์</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="สาขาที่ขอ"
                                    data-en="Field of Study">สาขาที่ขอ</span></label>
                            <input type="text" name="branch" class="input-field"
                                value="<?php echo htmlspecialchars($request['branch']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="เบอร์โทรศัพท์"
                                    data-en="Phone Number">เบอร์โทรศัพท์</span> <span class="text-red-500">*</span></label>
                            <input type="tel" name="tel" required class="input-field"
                                value="<?php echo htmlspecialchars($request['tel']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="อีเมล"
                                    data-en="Email">อีเมล</span> <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required class="input-field bg-gray-50"
                                value="<?php echo htmlspecialchars($request['email']); ?>" readonly>
                            <p class="text-xs text-gray-400 mt-1" data-th="* ไม่สามารถแก้ไขอีเมลได้"
                                data-en="* Email cannot be changed">* ไม่สามารถแก้ไขอีเมลได้</p>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($request['email']); ?>">
                <?php endif; ?>

                <!-- File Uploads -->
                <div class="border-t pt-6">
                    <h3 class="font-bold text-gray-700 mb-2 flex items-center">
                        <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                        <span data-th="เอกสารแนบ (อัปโหลดใหม่เพื่อเปลี่ยนไฟล์)"
                            data-en="Attachments (Upload new files to replace)">เอกสารแนบ
                            (อัปโหลดใหม่เพื่อเปลี่ยนไฟล์)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mb-4" data-th="* หากไม่ต้องการเปลี่ยนไฟล์เดิม ให้ปล่อยว่างไว้"
                        data-en="* Leave blank if you don't want to replace existing files">*
                        หากไม่ต้องการเปลี่ยนไฟล์เดิม ให้ปล่อยว่างไว้</p>
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                        <?php
                        $fileFields = $isReportOnly ? [
                            ['name' => 'file_report', 'label_th' => '1. รายงานวิจัยฉบับสมบูรณ์ (PDF)', 'label_en' => '1. Complete Research Report (PDF)'],
                            ['name' => 'file_explanation', 'label_th' => '2. แนบไฟล์ชี้แจงการแก้ไข (PDF)', 'label_en' => '2. Correction Explanation File (PDF)']
                        ] : [
                            ['name' => 'file_report', 'label_th' => '1. รายงานวิจัยฉบับสมบูรณ์ (PDF)', 'label_en' => '1. Complete Research Report (PDF)'],
                            ['name' => 'file_self_assess', 'label_th' => '2. แบบประเมินตนเอง', 'label_en' => '2. Self-Assessment Form'],
                            ['name' => 'file_sp10', 'label_th' => '3. บันทึกข้อความ สวพ.10', 'label_en' => '3. Memo Form SP.10'],
                            ['name' => 'file_sp10_1', 'label_th' => '4. แบบฟอร์มตรวจ สวพ.10.1', 'label_en' => '4. Inspection Form SP.10.1'],
                            ['name' => 'file_cert', 'label_th' => '5. บทความวิจัยที่ได้รับการตีพิมพ์', 'label_en' => '5. Publication Certificate'],
                            ['name' => 'file_plagiarism', 'label_th' => '6. หลักฐานตรวจอักขราวิสุทธิ์', 'label_en' => '6. Plagiarism Check Report'],
                            ['name' => 'file_participation', 'label_th' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย', 'label_en' => '7. Research Participation Evidence'],
                            ['name' => 'file_ethics', 'label_th' => '8. เอกสารรับรองจริยธรรมการวิจัย', 'label_en' => '8. Research Ethics Certification']
                        ];
                        foreach ($fileFields as $field):
                            $exists = !empty($request[$field['name']]);
                            ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span data-th="<?php echo htmlspecialchars($field['label_th']); ?>"
                                        data-en="<?php echo htmlspecialchars($field['label_en']); ?>">
                                        <?php echo htmlspecialchars($field['label_th']); ?>
                                    </span>
                                    <?php if (!$exists): ?>
                                        <span class="text-red-500">*</span>
                                    <?php endif; ?>
                                </label>
                                <div class="flex items-center file-group">
                                    <label
                                        class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-600 px-3 py-2 rounded-l hover:bg-gray-100 text-sm whitespace-nowrap flex items-center">
                                        <i data-lucide="cloud-upload" class="w-4 h-4 mr-2"></i>
                                        <span data-th="เลือกไฟล์ใหม่" data-en="Choose New File">เลือกไฟล์ใหม่</span>
                                        <input type="file" name="<?php echo $field['name']; ?>" accept=".pdf" class="hidden"
                                            onchange="updateFileName(this)">
                                    </label>
                                    <div class="flex-1 border border-l-0 border-gray-300 py-2 px-3 bg-white text-gray-400 text-sm truncate rounded-r file-name"
                                        data-th="ไม่ได้เลือกไฟล์ใหม่" data-en="No new file selected">
                                        ไม่ได้เลือกไฟล์ใหม่
                                    </div>
                                </div>
                                <?php if ($exists): ?>
                                    <p class="text-[10px] mt-1 truncate text-gray-400">
                                        <span data-th="ไฟล์ปัจจุบัน:" data-en="Current file:">ไฟล์ปัจจุบัน:</span>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span data-th="ชื่อวารสารที่ตีพิมพ์"
                                    data-en="Journal Name">ชื่อวารสารที่ตีพิมพ์</span> <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="journal_name" required class="input-field"
                                value="<?php echo htmlspecialchars($request['journal_name']); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><span
                                    data-th="จำนวน % การคัดลอก (ไม่เกิน 15%)" data-en="Plagiarism % (max 15%)">จำนวน %
                                    การคัดลอก (ไม่เกิน 15%)</span>
                                <span class="text-red-500">*</span></label>
                            <input type="number" name="plagiarism_percent" required max="15" step="0.01" class="input-field"
                                value="<?php echo htmlspecialchars($request['plagiarism_percent']); ?>">
                        </div>
                    </div>

                    <!-- สัดส่วนการมีส่วนร่วมในงานวิจัย (Repeater Field) -->
                    <div class="mt-6 p-6 bg-gray-50/50 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-gray-800 flex items-center dark:text-white">
                                <i data-lucide="users" class="w-5 h-5 mr-2 text-indigo-600"></i>
                                <span data-th="สัดส่วนการมีส่วนร่วมในงานวิจัย" data-en="Research Contribution Proportion">สัดส่วนการมีส่วนร่วมในงานวิจัย</span> <span class="text-red-500">*</span>
                            </h4>
                            <button type="button" id="addContributorBtn"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                                <i data-lucide="plus" class="w-3.5 h-3.5 mr-1"></i>
                                <span data-th="เพิ่มผู้ร่วมงาน" data-en="Add Contributor">เพิ่มผู้ร่วมงาน</span>
                            </button>
                        </div>

                        <div id="contributorsContainer" class="space-y-4">
                            <?php
                            $contributors = json_decode($request['contributors'] ?? '[]', true);
                            foreach ($contributors as $c):
                            ?>
                                <div class="contributor-row grid grid-cols-1 md:grid-cols-12 gap-3 p-4 bg-white rounded-lg border border-gray-200 relative group dark:bg-gray-800 dark:border-gray-700">
                                    <!-- ชื่อผู้ร่วมงาน -->
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="ชื่อผู้ร่วมงาน" data-en="Name">ชื่อผู้ร่วมงาน</label>
                                        <input type="text" class="contrib-name input-field text-sm p-2" required placeholder="ระบุชื่อ-นามสกุล" data-placeholderTh="ระบุชื่อ-นามสกุล" data-placeholderEn="Enter name" value="<?php echo htmlspecialchars($c['name']); ?>">
                                    </div>
                                    
                                    <!-- ปริมาณงานร้อยละ -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="ปริมาณงานร้อยละ" data-en="Contribution %">ปริมาณงานร้อยละ</label>
                                        <input type="number" class="contrib-percent input-field text-sm p-2" required min="0" max="100" placeholder="เช่น 50" data-placeholderTh="เช่น 50" data-placeholderEn="e.g. 50" value="<?php echo $c['percent']; ?>">
                                    </div>

                                    <!-- บทบาท -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="บทบาท" data-en="Role">บทบาท</label>
                                        <select class="contrib-role input-field text-sm p-2">
                                            <option value="หัวหน้าโครงการวิจัย" <?php echo ($c['role'] ?? '') === 'หัวหน้าโครงการวิจัย' ? 'selected' : ''; ?> data-th="หัวหน้าโครงการวิจัย" data-en="Principal Investigator">หัวหน้าโครงการวิจัย</option>
                                            <option value="ผู้ร่วมโครงการวิจัย" <?php echo ($c['role'] ?? '') === 'ผู้ร่วมโครงการวิจัย' ? 'selected' : ''; ?> data-th="ผู้ร่วมโครงการวิจัย" data-en="Co-Investigator">ผู้ร่วมโครงการวิจัย</option>
                                        </select>
                                    </div>

                                    <!-- รายละเอียดบทบาท/หน้าที่ -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="รายละเอียดบทบาท/หน้าที่" data-en="Responsibility">รายละเอียดบทบาท/หน้าที่</label>
                                        <input type="text" class="contrib-desc input-field text-sm p-2" required placeholder="เช่น ออกแบบการทดลอง" data-placeholderTh="เช่น ออกแบบการทดลอง" data-placeholderEn="e.g. Design experiment" value="<?php echo htmlspecialchars($c['description'] ?? ''); ?>">
                                    </div>

                                    <!-- ลบ -->
                                    <div class="md:col-span-1 flex items-end justify-center pb-1">
                                        <button type="button" class="remove-contrib-btn text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="ลบผู้ร่วมงาน">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <input type="hidden" name="contributors" id="contributors_hidden" value="[]">
                    </div>
                <?php endif; ?>

                <!-- Agreement & Captcha -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="flex items-start gap-3 mb-4">
                        <input type="checkbox" name="agreement" id="agreement" required
                            class="mt-1 w-4 h-4 text-mcu-blue rounded">
                        <label for="agreement" class="text-sm text-gray-700"
                            data-th="ข้าพเจ้าตรวจสอบการแก้ไขเรียบร้อยแล้ว และยืนยันข้อมูลที่ปรับปรุงเป็นความจริง"
                            data-en="I have reviewed the corrections and confirm that the updated information is accurate.">
                            ข้าพเจ้าตรวจสอบการแก้ไขเรียบร้อยแล้ว และยืนยันข้อมูลที่ปรับปรุงเป็นความจริง
                        </label>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-gray-200 px-4 py-2 rounded font-bold">
                            <span id="captchaNum1"></span> + <span id="captchaNum2"></span> = ?
                        </div>
                        <input type="number" id="captchaAnswer" class="w-24 p-2 border rounded text-center"
                            data-placeholderth="คำตอบ" data-placeholderen="Ans" placeholder="คำตอบ" required>
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
                    <span data-th="บันทึกและส่งการแก้ไข"
                        data-en="Save and Submit Corrections">บันทึกและส่งการแก้ไข</span>
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

    // Repeater Field logic for Research Contributors (only if not report-only)
    const addContributorBtn = document.getElementById('addContributorBtn');
    const contributorsContainer = document.getElementById('contributorsContainer');

    if (addContributorBtn && contributorsContainer) {
        // Bind remove button for existing rows
        document.querySelectorAll('.remove-contrib-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                btn.closest('.contributor-row').remove();
            });
        });

        addContributorBtn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'contributor-row grid grid-cols-1 md:grid-cols-12 gap-3 p-4 bg-white rounded-lg border border-gray-200 relative group dark:bg-gray-800 dark:border-gray-700';
            row.innerHTML = `
                <!-- ชื่อผู้ร่วมงาน -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="ชื่อผู้ร่วมงาน" data-en="Name">ชื่อผู้ร่วมงาน</label>
                    <input type="text" class="contrib-name input-field text-sm p-2" required placeholder="ระบุชื่อ-นามสกุล" data-placeholderTh="ระบุชื่อ-นามสกุล" data-placeholderEn="Enter name">
                </div>
                
                <!-- ปริมาณงานร้อยละ -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="ปริมาณงานร้อยละ" data-en="Contribution %">ปริมาณงานร้อยละ</label>
                    <input type="number" class="contrib-percent input-field text-sm p-2" required min="0" max="100" placeholder="เช่น 50" data-placeholderTh="เช่น 50" data-placeholderEn="e.g. 50">
                </div>

                <!-- บทบาท -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="บทบาท" data-en="Role">บทบาท</label>
                    <select class="contrib-role input-field text-sm p-2">
                        <option value="หัวหน้าโครงการวิจัย" data-th="หัวหน้าโครงการวิจัย" data-en="Principal Investigator">หัวหน้าโครงการวิจัย</option>
                        <option value="ผู้ร่วมโครงการวิจัย" data-th="ผู้ร่วมโครงการวิจัย" data-en="Co-Investigator">ผู้ร่วมโครงการวิจัย</option>
                    </select>
                </div>

                <!-- รายละเอียดบทบาท/หน้าที่ -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-1 dark:text-gray-400" data-th="รายละเอียดบทบาท/หน้าที่" data-en="Responsibility">รายละเอียดบทบาท/หน้าที่</label>
                    <input type="text" class="contrib-desc input-field text-sm p-2" required placeholder="เช่น ออกแบบการทดลอง" data-placeholderTh="เช่น ออกแบบการทดลอง" data-placeholderEn="e.g. Design experiment">
                </div>

                <!-- ลบ -->
                <div class="md:col-span-1 flex items-end justify-center pb-1">
                    <button type="button" class="remove-contrib-btn text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="ลบผู้ร่วมงาน">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            `;
            contributorsContainer.appendChild(row);
            
            // Bind remove button
            row.querySelector('.remove-contrib-btn').addEventListener('click', function() {
                row.remove();
            });

            // Initialize Lucide icons inside the new row
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Apply translations
            LanguageCore.apply(LanguageCore.currentLang);
        });
    }

    document.getElementById('editForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const answer = parseInt(document.getElementById('captchaAnswer').value);
        const expected = parseInt(document.getElementById('captchaExpected').value);
        if (answer !== expected) {
            alert('เลขยืนยันตัวตนไม่ถูกต้อง');
            generateCaptcha();
            return;
        }

        // Compile contributors repeater data to JSON (if present)
        if (addContributorBtn) {
            const contributorRows = document.querySelectorAll('.contributor-row');
            if (contributorRows.length === 0) {
                alert('กรุณากรอกข้อมูลสัดส่วนการมีส่วนร่วมในงานวิจัยอย่างน้อย 1 รายการ');
                return;
            }

            const contributors = [];
            contributorRows.forEach(row => {
                const name = row.querySelector('.contrib-name').value.trim();
                const percent = row.querySelector('.contrib-percent').value.trim();
                const role = row.querySelector('.contrib-role').value;
                const desc = row.querySelector('.contrib-desc').value.trim();
                if (name || percent || desc) {
                    contributors.push({
                        name: name,
                        percent: percent ? parseFloat(percent) : 0,
                        role: role,
                        description: desc
                    });
                }
            });
            document.getElementById('contributors_hidden').value = JSON.stringify(contributors);
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
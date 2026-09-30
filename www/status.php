<?php
/**
 * หน้าตรวจสอบสถานะ - ระบบรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ
 */
require_once 'includes/config.php';
$pageTitle = 'ตรวจสอบสถานะ';

// Get search ID from URL
$searchId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$result = null;
$error = null;

if ($searchId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE request_id = ?");
        $stmt->execute([$searchId]);
        $result = $stmt->fetch();

        if (!$result) {
            $error = 'ไม่พบข้อมูลที่ตรงกับรหัสคำขอนี้';
        }
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาดในการค้นหา';
    }
}

$noIndex = true;
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow">
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800" data-th="ตรวจสอบสถานะคำขอ" data-en="Check Request Status">
                ตรวจสอบสถานะคำขอ</h2>
            <p class="text-gray-500 mt-2" data-th="กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ"
                data-en="Please enter your request ID to check status">กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ</p>
        </div>

        <!-- Search Form -->
        <form method="GET" class="flex gap-2 max-w-xl mx-auto mb-10">
            <div class="relative flex-1">
                <input type="text" name="id" value="<?php echo htmlspecialchars($searchId); ?>"
                    data-placeholderTh="กรอกรหัสคำขอ (เช่น RSA-123456)..."
                    data-placeholderEn="Enter Request ID (e.g. RSA-123456)..."
                    placeholder="กรอกรหัสคำขอ (เช่น RSA-123456)..."
                    class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-mcu-blue outline-none">
                <div class="absolute left-3 top-3.5 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </div>
            </div>
            <button type="submit" class="px-6 py-3 bg-mcu-blue text-white rounded-lg hover:bg-opacity-90 transition"
                data-th="ค้นหา" data-en="Search">
                ค้นหา
            </button>
        </form>

        <!-- Results -->
        <div class="space-y-4">
            <?php if ($error): ?>
                <div class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg border border-dashed">
                    <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                    <p data-th="<?php echo $error; ?>"
                        data-en="<?php echo $error === 'ไม่พบข้อมูลที่ตรงกับรหัสคำขอนี้' ? 'No data found for this ID' : 'Search error'; ?>">
                        <?php echo $error; ?>
                    </p>
                </div>
            <?php elseif ($result): ?>
                <?php
                $statusInfo = $STATUS_OPTIONS[$result['status']] ?? $STATUS_OPTIONS['checking'];
                $statusLabelsEn = [
                    'resubmitted' => '0. Resubmitted by Researcher',
                    'checking' => '1. Staff Reviewing Documents',
                    'ch_edit' => '1.5 Document Correction Required',
                    'committee' => '2. Committee Reviewing',
                    'correction' => '3. Research Report Correction Required',
                    'rejected' => '4. Not Accredited',
                    'approved' => '5. Approved'
                ];
                $statusLabelEn = $statusLabelsEn[$result['status']] ?? 'Checking';
                ?>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-<?php echo $statusInfo['color']; ?>-500">
                    <!-- Status Header (Prominent) -->
                    <div class="mb-6 border-b pb-4 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span
                                class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold border shadow-sm bg-<?php echo $statusInfo['color']; ?>-100 text-<?php echo $statusInfo['color']; ?>-800 border-<?php echo $statusInfo['color']; ?>-300"
                                data-th="<?php echo $statusInfo['label']; ?>" data-en="<?php echo $statusLabelEn; ?>">
                                <i data-lucide="<?php echo $statusInfo['icon'] ?? 'info'; ?>" class="w-5 h-5 mr-2"></i>
                                <?php echo $statusInfo['label']; ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                <span data-th="ส่งเมื่อ:" data-en="Submitted on:">ส่งเมื่อ:</span>
                                <span data-th="<?php echo formatThaiDate($result['created_at']); ?>"
                                    data-en="<?php echo date('M d, Y', strtotime($result['created_at'])); ?>">
                                    <?php echo formatThaiDate($result['created_at']); ?>
                                </span>
                            </span>
                        </div>

                        <h4 class="font-bold text-xl md:text-2xl text-gray-900 leading-tight mb-3">
                            <?php echo htmlspecialchars($result['project_title']); ?>
                        </h4>

                        <div class="flex flex-wrap items-center gap-y-2 text-sm text-gray-600">
                            <div class="flex items-center mr-4">
                                <span class="font-semibold text-gray-500 mr-2" data-th="รหัสคำขอ:"
                                    data-en="Request ID:">รหัสคำขอ:</span>
                                <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-mcu-blue font-bold">
                                    <?php echo $result['request_id']; ?>
                                </span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-semibold text-gray-500 mr-2" data-th="ผู้วิจัย:"
                                    data-en="Researcher:">ผู้วิจัย:</span>
                                <span class="text-gray-800"><?php echo htmlspecialchars($result['name']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="grid md:grid-cols-2 gap-4 text-sm mb-4 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="text-gray-500" data-th="สังกัด:" data-en="Affiliation:">สังกัด:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['affiliation']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500" data-th="ปีงบประมาณ:" data-en="Fiscal Year:">ปีงบประมาณ:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['fiscal_year']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500" data-th="ตำแหน่งที่ขอ:"
                                data-en="Position Applied:">ตำแหน่งที่ขอ:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['academic_position'] ?: '-'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500" data-th="วารสาร:" data-en="Journal:">วารสาร:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['journal_name'] ?: '-'); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($result['status'] === 'approved' && $result['file_rs_cert']): ?>
                        <!-- Approved - Download Certificate -->
                        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex justify-between items-center">
                            <span class="text-green-800 font-bold flex items-center" data-th="ได้รับการรับรองแล้ว"
                                data-en="Accreditation Approved">
                                <i data-lucide="award" class="w-5 h-5 mr-2"></i>
                                ได้รับการรับรองแล้ว
                            </span>
                            <a href="<?php echo htmlspecialchars(ensureSecureUrl($result['file_rs_cert'])); ?>" target="_blank"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded hover:bg-green-700 flex items-center shadow">
                                <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                <span data-th="ดาวน์โหลดใบรับรอง" data-en="Download Certificate">ดาวน์โหลดใบรับรอง</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($result['status'], ['correction', 'ch_edit'])): ?>
                        <!-- Correction Required -->
                        <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                            <div class="flex items-center gap-2 mb-3 text-orange-800 font-bold">
                                <i data-lucide="<?php echo ($result['status'] === 'ch_edit') ? 'edit' : 'edit-3'; ?>"
                                    class="w-5 h-5"></i>
                                <span
                                    data-th="<?php echo ($result['status'] === 'ch_edit') ? 'บันทึกแจ้งแก้ไขข้อมูลเอกสาร' : 'สิ่งที่ต้องแก้ไข (รายงานวิจัย)'; ?>"
                                    data-en="<?php echo ($result['status'] === 'ch_edit') ? 'Document Correction Notice' : 'Report Correction Required'; ?>">
                                    <?php echo ($result['status'] === 'ch_edit') ? 'บันทึกแจ้งแก้ไขข้อมูลเอกสาร' : 'สิ่งที่ต้องแก้ไข (รายงานวิจัย)'; ?>
                                </span>
                            </div>

                            <?php if ($result['correction_note']): ?>
                                <div
                                    class="text-sm text-gray-800 bg-white p-3 rounded border border-orange-100 mb-3 whitespace-normal text-left">
                                    <?php echo nl2br(htmlspecialchars(trim($result['correction_note']))); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($result['file_correction']): ?>
                                <div class="flex justify-between items-center gap-2">
                                    <a href="edit_request.php?id=<?php echo $result['request_id']; ?>&email=<?php echo urlencode($result['email']); ?>"
                                        class="inline-flex items-center px-4 py-2 bg-mcu-blue text-white text-sm font-bold rounded hover:bg-opacity-90 shadow-sm transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                                        <span data-th="ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข"
                                            data-en="Submit Corrections">ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข</span>
                                    </a>
                                    <a href="<?php echo htmlspecialchars(ensureSecureUrl($result['file_correction'])); ?>"
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-sm font-bold rounded hover:bg-orange-600 shadow-sm transition-colors">
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                        <span data-th="ดาวน์โหลดเอกสารแจ้งแก้ไข"
                                            data-en="Download Instructions">ดาวน์โหลดเอกสารแจ้งแก้ไข</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-end">
                                    <a href="edit_request.php?id=<?php echo $result['request_id']; ?>&email=<?php echo urlencode($result['email']); ?>"
                                        class="inline-flex items-center px-4 py-2 bg-mcu-blue text-white text-sm font-bold rounded hover:bg-opacity-90 shadow-sm transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                                        <span data-th="ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข"
                                            data-en="Submit Corrections">ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($result['status'] === 'rejected'): ?>
                        <!-- Rejected -->
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-2 text-red-800 font-bold">
                                <i data-lucide="x-circle" class="w-5 h-5"></i>
                                <span data-th="ไม่ผ่านการรับรอง" data-en="Accreditation Rejected">ไม่ผ่านการรับรอง</span>
                            </div>
                            <?php if ($result['correction_note']): ?>
                                <div class="text-sm text-gray-700 mt-2">
                                    <?php echo nl2br(htmlspecialchars($result['correction_note'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!$searchId): ?>
                <!-- No search yet -->
                <div class="text-center py-12">
                    <i data-lucide="file-search" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <p class="text-gray-500" data-th="กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ"
                        data-en="Please enter your request ID to check status">กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
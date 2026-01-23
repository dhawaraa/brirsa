<?php
/**
 * หน้าตรวจสอบสถานะ - ระบบขอรับรองงานวิจัย
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

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow">
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">ตรวจสอบสถานะคำขอ</h2>
            <p class="text-gray-500 mt-2">กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ</p>
        </div>

        <!-- Search Form -->
        <form method="GET" class="flex gap-2 max-w-xl mx-auto mb-10">
            <div class="relative flex-1">
                <input type="text" name="id" value="<?php echo htmlspecialchars($searchId); ?>"
                    placeholder="กรอกรหัสคำขอ (เช่น RSA-123456)..."
                    class="w-full pl-10 pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-mcu-blue outline-none">
                <div class="absolute left-3 top-3.5 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </div>
            </div>
            <button type="submit" class="px-6 py-3 bg-mcu-blue text-white rounded-lg hover:bg-opacity-90 transition">
                ค้นหา
            </button>
        </form>

        <!-- Results -->
        <div class="space-y-4">
            <?php if ($error): ?>
                <div class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg border border-dashed">
                    <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                    <p>
                        <?php echo $error; ?>
                    </p>
                </div>
            <?php elseif ($result): ?>
                <?php
                $statusInfo = $STATUS_OPTIONS[$result['status']] ?? $STATUS_OPTIONS['checking'];
                ?>
                <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-<?php echo $statusInfo['color']; ?>-500">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-lg text-gray-800">
                                <?php echo htmlspecialchars($result['project_title']); ?>
                            </h4>
                            <p class="text-sm text-gray-600 mt-1">
                                รหัส: <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">
                                    <?php echo $result['request_id']; ?>
                                </span>
                                | ผู้วิจัย:
                                <?php echo htmlspecialchars($result['name']); ?>
                            </p>
                            <p class="text-gray-500 text-xs mt-1">
                                ส่งเมื่อ:
                                <?php echo formatThaiDate($result['created_at']); ?>
                            </p>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold border bg-<?php echo $statusInfo['color']; ?>-100 text-<?php echo $statusInfo['color']; ?>-800 border-<?php echo $statusInfo['color']; ?>-200">
                            <?php echo $statusInfo['label']; ?>
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="grid md:grid-cols-2 gap-4 text-sm mb-4 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="text-gray-500">สังกัด:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['affiliation']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">ปีงบประมาณ:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['fiscal_year']); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">ตำแหน่งที่ขอ:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['academic_position'] ?: '-'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">วารสาร:</span>
                            <span class="ml-2">
                                <?php echo htmlspecialchars($result['journal_name'] ?: '-'); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($result['status'] === 'approved' && $result['file_rs_cert']): ?>
                        <!-- Approved - Download Certificate -->
                        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex justify-between items-center">
                            <span class="text-green-800 font-bold flex items-center">
                                <i data-lucide="award" class="w-5 h-5 mr-2"></i>
                                ได้รับการรับรองแล้ว
                            </span>
                            <a href="<?php echo htmlspecialchars($result['file_rs_cert']); ?>" target="_blank"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded hover:bg-green-700 flex items-center shadow">
                                <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                ดาวน์โหลดใบรับรอง
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($result['status'] === 'correction'): ?>
                        <!-- Correction Required -->
                        <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                            <div class="flex items-center gap-2 mb-3 text-orange-800 font-bold">
                                <i data-lucide="edit-3" class="w-5 h-5"></i>
                                สิ่งที่ต้องแก้ไข
                            </div>

                            <?php if ($result['correction_note']): ?>
                                <div
                                    class="text-sm text-gray-800 bg-white p-3 rounded border border-orange-100 mb-3 whitespace-pre-wrap">
                                    <?php echo nl2br(htmlspecialchars($result['correction_note'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($result['file_correction']): ?>
                                <div class="flex justify-between items-center gap-2">
                                    <a href="edit_request.php?id=<?php echo $result['request_id']; ?>&email=<?php echo urlencode($result['email']); ?>"
                                        class="inline-flex items-center px-4 py-2 bg-mcu-blue text-white text-sm font-bold rounded hover:bg-opacity-90 shadow-sm transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                                        ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข
                                    </a>
                                    <a href="<?php echo htmlspecialchars($result['file_correction']); ?>" target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-sm font-bold rounded hover:bg-orange-600 shadow-sm transition-colors">
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                        ดาวน์โหลดเอกสารแจ้งแก้ไข
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-end">
                                    <a href="edit_request.php?id=<?php echo $result['request_id']; ?>&email=<?php echo urlencode($result['email']); ?>"
                                        class="inline-flex items-center px-4 py-2 bg-mcu-blue text-white text-sm font-bold rounded hover:bg-opacity-90 shadow-sm transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4 mr-2"></i>
                                        ส่งข้อมูล/ไฟล์ที่แจ้งแก้ไข
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
                                ไม่ผ่านการรับรอง
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
                    <p class="text-gray-500">กรุณากรอกรหัสคำขอเพื่อตรวจสอบสถานะ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
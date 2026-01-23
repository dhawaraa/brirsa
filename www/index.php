<?php
/**
 * หน้าหลัก - ระบบขอรับรองงานวิจัย
 */
require_once 'includes/config.php';
$pageTitle = 'หน้าหลัก';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Section -->
<div class="bg-white py-12 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold mb-4">
            Research Accreditation System
        </span>
        <h2 class="text-3xl md:text-5xl font-bold text-mcu-blue mb-4">
            <?php echo SITE_NAME; ?>
        </h2>
        <p class="text-lg text-gray-600">
            <?php echo SITE_SUBTITLE; ?>
        </p>
    </div>
</div>

<!-- Process Steps -->
<div class="bg-gray-50 py-12 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h3 class="text-2xl font-bold text-gray-800">ขั้นตอนการดำเนินการ</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php
            $steps = [
                ['title' => '1. เจ้าหน้าที่ตรวจสอบ', 'desc' => 'ตรวจสอบความถูกต้องเบื้องต้น', 'icon' => 'file-text', 'color' => 'blue'],
                ['title' => '2. เสนอกรรมการ', 'desc' => 'พิจารณาเนื้อหาโดยละเอียด', 'icon' => 'users', 'color' => 'purple'],
                ['title' => '3. ส่งกลับแก้ไข', 'desc' => 'กรณีต้องปรับปรุงข้อมูล', 'icon' => 'edit-3', 'color' => 'orange'],
                ['title' => '4. ไม่รับรอง', 'desc' => 'ไม่ผ่านเกณฑ์การประเมิน', 'icon' => 'x', 'color' => 'red'],
                ['title' => '5. อนุมัติรับรอง', 'desc' => 'ออกใบรับรองฉบับสมบูรณ์', 'icon' => 'check-circle', 'color' => 'green']
            ];
            foreach ($steps as $step):
                ?>
                <div
                    class="p-4 rounded-lg border bg-<?php echo $step['color']; ?>-50 text-<?php echo $step['color']; ?>-700 border-<?php echo $step['color']; ?>-200 flex flex-col items-center justify-center text-center shadow-sm h-full">
                    <div class="mb-3 p-2 bg-white rounded-full shadow-sm">
                        <i data-lucide="<?php echo $step['icon']; ?>" class="w-6 h-6"></i>
                    </div>
                    <h4 class="font-bold text-sm mb-1">
                        <?php echo $step['title']; ?>
                    </h4>
                    <p class="text-xs opacity-75">
                        <?php echo $step['desc']; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Related Documents -->
<div class="bg-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="mb-8 border-l-4 border-mcu-blue pl-4">
            <h3 class="text-2xl font-bold text-gray-800">เอกสารที่เกี่ยวข้อง</h3>
            <p class="text-sm text-gray-500">ดาวน์โหลดแบบฟอร์มและคู่มือการใช้งาน</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <?php
            $documents = [
                ['title' => 'ประกาศการตรวจสอบ', 'url' => 'https://newbri.mcu.ac.th/?p=2878', 'icon' => 'megaphone', 'color' => 'blue'],
                ['title' => 'ระบบตรวจสอบอักขราวิสุทธิ์', 'url' => 'https://app.akarawisut.com', 'icon' => 'file-search', 'color' => 'indigo'],
                ['title' => 'บันทึกข้อความ สวพ.10', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2025/12/สวพ-10-แบบฟอร์ม-บันทึกส่งขอใบรับรองผลงานวิจัย.doc', 'icon' => 'folder-open', 'color' => 'yellow'],
                ['title' => 'แบบฟอร์มตรวจ สวพ.10.1', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2025/12/สวพ-10.1-แบบฟอร์มตรวจสอบเอกสาร.docx', 'icon' => 'file-text', 'color' => 'pink'],
                ['title' => 'แบบประเมินตนเอง', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2025/12/แบบประเมินตนเองสำหรับการยื่นขอรับรองงานวิจัย.docx', 'icon' => 'clipboard-check', 'color' => 'green'],
                ['title' => 'หลักฐานการมีส่วนร่วมในงานวิจัย', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2026/01/ใบแสดงหลักฐานการมีส่วนร่วมในผลงานวิจัย.docx', 'icon' => 'users', 'color' => 'orange']
            ];
            foreach ($documents as $doc):
                ?>
                <a href="<?php echo $doc['url']; ?>" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center justify-center text-center p-6 bg-white rounded-xl border border-gray-200 hover:shadow-lg hover:border-pink-300 transition-all cursor-pointer group min-h-[180px]">
                    <div
                        class="w-14 h-14 bg-<?php echo $doc['color']; ?>-500 rounded-full flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform shadow-sm">
                        <i data-lucide="<?php echo $doc['icon']; ?>" class="w-7 h-7"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-700 group-hover:text-mcu-blue leading-snug">
                        <?php echo $doc['title']; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Contact Section -->
<div class="bg-gray-50 py-12 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex-1">
            <h3 class="text-2xl font-bold text-mcu-blue mb-4">ติดต่อประสานงาน</h3>
            <p class="font-bold text-lg mb-2">(นายเดชฤทธิ์ โอฐสู)</p>
            <p class="text-gray-600 mb-1">สถาบันวิจัยพุทธศาสตร์ อาคารวิทยบริการ ชั้น 5</p>
            <p class="text-gray-600">มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย ตำบลลำไทร อำเภอวังน้อย จังหวัดพระนครศรีอยุธยา
                13170</p>
        </div>
        <div>
            <a href="https://line.me/ti/p/~noomnoieiei" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center px-6 py-3 bg-green-500 text-white font-bold rounded-full shadow-lg hover:bg-green-600 transition transform hover:scale-105">
                <i data-lucide="message-circle" class="w-5 h-5 mr-2"></i>
                ติดต่อสอบถามผ่าน LINE
            </a>
        </div>
    </div>
</div>

<!-- CTA Buttons -->
<div class="bg-white py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="register.php" class="btn-pink text-center flex items-center justify-center">
                <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                ส่งข้อมูลขอรับรอง
            </a>
            <a href="status.php" class="btn-primary text-center flex items-center justify-center">
                <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                ตรวจสอบสถานะ
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
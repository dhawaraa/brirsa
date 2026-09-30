<?php
/**
 * หน้าหลัก - ระบบรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ
 */
require_once 'includes/config.php';
$pageTitle = 'หน้าหลัก';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Section -->
<div class="bg-white py-12 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold mb-4"
            data-th="Research Accreditation System" data-en="Research Accreditation System">
            Research Accreditation System
        </span>
        <h2 class="text-3xl md:text-5xl font-bold text-mcu-blue my-5 md:my-6 leading-snug" data-th="<?php echo SITE_NAME; ?>"
            data-en="Research Accreditation System">
            <?php echo SITE_NAME; ?>
        </h2>
        <p class="text-lg text-gray-600" data-th="<?php echo SITE_SUBTITLE; ?>"
            data-en="Buddhist Research Institute, MCU">
            <?php echo SITE_SUBTITLE; ?>
        </p>
    </div>
</div>

<!-- Process Steps -->
<div class="bg-gray-50 py-12 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h3 class="text-2xl font-bold text-gray-800" data-th="ขั้นตอนการดำเนินการ" data-en="Operating Process">
                ขั้นตอนการดำเนินการ</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php
            $steps = [
                ['title_th' => '1. เจ้าหน้าที่ตรวจสอบ', 'title_en' => '1. Staff Review', 'desc_th' => 'ตรวจสอบความถูกต้องเบื้องต้น', 'desc_en' => 'Preliminary document check', 'icon' => 'file-text', 'color' => 'blue'],
                ['title_th' => '2. เสนอกรรมการ', 'title_en' => '2. Committee Review', 'desc_th' => 'พิจารณาเนื้อหาโดยละเอียด', 'desc_en' => 'Detailed ethical review', 'icon' => 'users', 'color' => 'purple'],
                ['title_th' => '3. ส่งกลับแก้ไข', 'title_en' => '3. Request Correction', 'desc_th' => 'กรณีต้องปรับปรุงข้อมูล', 'desc_en' => 'Updates required', 'icon' => 'edit-3', 'color' => 'orange'],
                ['title_th' => '4. ไม่รับรอง', 'title_en' => '4. Rejected', 'desc_th' => 'ไม่ผ่านเกณฑ์การประเมิน', 'desc_en' => 'Not meeting criteria', 'icon' => 'x', 'color' => 'red'],
                ['title_th' => '5. อนุมัติรับรอง', 'title_en' => '5. Approved', 'desc_th' => 'ออกใบรับรองฉบับสมบูรณ์', 'desc_en' => 'Certificate issued', 'icon' => 'check-circle', 'color' => 'green']
            ];
            foreach ($steps as $step):
                ?>
                <div
                    class="p-4 rounded-lg border bg-<?php echo $step['color']; ?>-50 text-<?php echo $step['color']; ?>-700 border-<?php echo $step['color']; ?>-200 flex flex-col items-center justify-center text-center shadow-sm h-full">
                    <div class="mb-3 p-2 bg-white rounded-full shadow-sm">
                        <i data-lucide="<?php echo $step['icon']; ?>" class="w-6 h-6"></i>
                    </div>
                    <h4 class="font-bold text-sm mb-1" data-th="<?php echo $step['title_th']; ?>"
                        data-en="<?php echo $step['title_en']; ?>">
                        <?php echo $step['title_th']; ?>
                    </h4>
                    <p class="text-xs opacity-75" data-th="<?php echo $step['desc_th']; ?>"
                        data-en="<?php echo $step['desc_en']; ?>">
                        <?php echo $step['desc_th']; ?>
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
            <h3 class="text-2xl font-bold text-gray-800" data-th="เอกสารที่เกี่ยวข้อง" data-en="Related Documents">
                เอกสารที่เกี่ยวข้อง</h3>
            <p class="text-sm text-gray-500" data-th="ดาวน์โหลดแบบฟอร์มและคู่มือการใช้งาน"
                data-en="Download forms and manuals">ดาวน์โหลดแบบฟอร์มและคู่มือการใช้งาน</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            <?php
            $documents = [
                ['title' => 'ประกาศการตรวจสอบ', 'title_en' => 'Examination Announcement', 'url' => 'https://newbri.mcu.ac.th/?p=2878', 'icon' => 'megaphone', 'color' => 'blue'],
                ['title' => 'ระบบตรวจสอบอักขราวิสุทธิ์', 'title_en' => 'Plagiarism Check System', 'url' => 'https://app.akarawisut.com', 'icon' => 'file-search', 'color' => 'indigo'],
                ['title' => 'บันทึกข้อความ สวพ.10', 'title_en' => 'Official Memo (BRI.10)', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2025/12/สวพ-10-แบบฟอร์ม-บันทึกส่งขอใบรับรองผลงานวิจัย.doc', 'icon' => 'folder-open', 'color' => 'yellow'],
                ['title' => 'แบบฟอร์มตรวจ สวพ.10.1', 'title_en' => 'Checklist Form (BRI.10.1)', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2026/05/สวพ-10.1-แบบฟอร์มตรวจสอบเอกสาร.docx', 'icon' => 'file-text', 'color' => 'pink'],
                ['title' => 'แบบประเมินตนเอง', 'title_en' => 'Self-Assessment Form', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2026/05/แบบประเมินตนเองการยื่นขอรับรองงานวิจัย.docx', 'icon' => 'clipboard-check', 'color' => 'green'],
                ['title' => 'หลักฐานการมีส่วนร่วมในงานวิจัย', 'title_en' => 'Research Participation Evidence', 'url' => 'https://newbri.mcu.ac.th/wp-content/uploads/2026/01/ใบแสดงหลักฐานการมีส่วนร่วมในผลงานวิจัย.docx', 'icon' => 'users', 'color' => 'orange']
            ];
            foreach ($documents as $doc):
                ?>
                <a href="<?php echo $doc['url']; ?>" target="_blank" rel="noopener noreferrer"
                    class="flex flex-col items-center justify-center text-center p-6 bg-white rounded-xl border border-gray-200 hover:shadow-lg hover:border-pink-300 transition-all cursor-pointer group min-h-[180px]">
                    <div
                        class="w-14 h-14 bg-<?php echo $doc['color']; ?>-500 rounded-full flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform shadow-sm">
                        <i data-lucide="<?php echo $doc['icon']; ?>" class="w-7 h-7"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-700 group-hover:text-mcu-blue leading-snug"
                        data-th="<?php echo $doc['title']; ?>" data-en="<?php echo $doc['title_en'] ?? $doc['title']; ?>">
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
            <h3 class="text-2xl font-bold text-mcu-blue mb-4" data-th="ติดต่อประสานงาน" data-en="Contact Us">
                ติดต่อประสานงาน</h3>
            <p class="font-bold text-lg mb-2" data-th="(นายเดชฤทธิ์ โอฐสู)" data-en="(Mr. Dechrit Otsoo)">(นายเดชฤทธิ์
                โอฐสู)</p>
            <p class="text-gray-600 mb-1" data-th="สถาบันวิจัยพุทธศาสตร์ อาคารวิทยบริการ ชั้น 5"
                data-en="Buddhist Research Institute, Academic Resource Center, 5th Floor">สถาบันวิจัยพุทธศาสตร์
                อาคารวิทยบริการ ชั้น 5</p>
            <p class="text-gray-600"
                data-th="มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย ตำบลลำไทร อำเภอวังน้อย จังหวัดพระนครศรีอยุธยา 13170"
                data-en="Mahachulalongkornrajavidyalaya University, Lam Sai, Wang Noi, Phra Nakhon Si Ayutthaya 13170">
                มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย ตำบลลำไทร อำเภอวังน้อย จังหวัดพระนครศรีอยุธยา
                13170</p>
        </div>
        <div>
            <a href="https://line.me/ti/p/~noomnoieiei" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center px-6 py-3 bg-green-500 text-white font-bold rounded-full shadow-lg hover:bg-green-600 transition transform hover:scale-105">
                <i data-lucide="message-circle" class="w-5 h-5 mr-2"></i>
                <span data-th="ติดต่อสอบถามผ่าน LINE" data-en="Contact via LINE">ติดต่อสอบถามผ่าน LINE</span>
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
                <span data-th="ส่งข้อมูลขอรับรอง" data-en="Submit for Accreditation">ส่งข้อมูลขอรับรอง</span>
            </a>
            <a href="status.php" class="btn-primary text-center flex items-center justify-center">
                <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                <span data-th="ตรวจสอบสถานะ" data-en="Check Status">ตรวจสอบสถานะ</span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
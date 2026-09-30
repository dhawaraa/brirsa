<?php
/**
 * หน้าส่งข้อมูลขอรับรอง - ระบบรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ
 */
require_once 'includes/config.php';
$pageTitle = 'ส่งข้อมูลขอรับรอง';
$noIndex = true;
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
                    <h3 class="text-2xl font-bold text-gray-900 mb-2" data-th="ดำเนินการสำเร็จ!" data-en="Success!">
                        ดำเนินการสำเร็จ!</h3>
                    <p class="text-gray-600 mb-4" data-th="ระบบได้บันทึกข้อมูลเรียบร้อยแล้ว"
                        data-en="Your data has been successfully saved.">ระบบได้บันทึกข้อมูลเรียบร้อยแล้ว</p>
                    <p class="text-sm text-gray-500 mb-4">
                        <span data-th="รหัสคำขอของคุณ:" data-en="Your Request ID:">รหัสคำขอของคุณ:</span>
                        <strong id="requestIdDisplay" class="text-mcu-blue"></strong>
                    </p>
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 text-left">
                        <h4 class="text-blue-800 font-bold text-sm mb-2" data-th="สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล:"
                            data-en="Required follow-up actions:">สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล:</h4>
                        <ul class="text-blue-700 text-xs space-y-1 list-decimal list-inside mb-2">
                            <li data-th="ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม"
                                data-en="Submit 1 hard copy of the complete research report">
                                ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม</li>
                            <li data-th="พร้อมบันทึกข้อความ สวพ.10 จำนวน 1 ฉบับ"
                                data-en="Include 1 copy of the official memo (BRI.10)">พร้อมบันทึกข้อความ สวพ.10 จำนวน 1
                                ฉบับ</li>
                        </ul>
                        <p class="text-blue-700 text-xs font-semibold"
                            data-th="มายัง สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย จ.พระนครศรีอยุธยา"
                            data-en="To: Buddhist Research Institute, MCU, Wang Noi, Ayutthaya">มายัง
                            สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย
                            จ.พระนครศรีอยุธยา</p>
                    </div>
                    <a id="successStatusLink" href="status.php" data-th="ตรวจสอบสถานะ" data-en="Check Status"
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
                <h3 class="text-lg font-bold text-gray-800 mb-2" data-th="กำลังส่งข้อมูล..." data-en="Sending Data...">
                    กำลังส่งข้อมูล...</h3>
                <p class="text-sm text-gray-500 mb-4" data-th="กรุณารอสักครู่ ห้ามปิดหน้านี้"
                    data-en="Please wait, do not close this page">กรุณารอสักครู่ ห้ามปิดหน้านี้</p>
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
                <h2 class="text-xl font-bold flex items-center" data-th="แบบฟอร์มขอใบรับรองงานวิจัย"
                    data-en="Research Accreditation Request Form">
                    <i data-lucide="send" class="w-6 h-6 mr-3"></i>
                    แบบฟอร์มขอใบรับรองงานวิจัย
                </h2>
            </div>

            <form id="registrationForm" action="api/submit.php" method="POST" enctype="multipart/form-data"
                class="p-8 space-y-6">
                <?php csrf_field(); ?>
                <!-- Personal Info -->
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="ชื่อ-นามสกุล" data-en="Full Name">ชื่อ-นามสกุล</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required class="input-field"
                            data-placeholderTh="เช่น พระมหาสมชาย สมใจ" data-placeholderEn="e.g. Mr. John Doe"
                            placeholder="เช่น พระมหาสมชาย สมใจ">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="ชื่อโครงการวิจัย" data-en="Research Project Title">ชื่อโครงการวิจัย</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="project_title" required class="input-field"
                            data-placeholderTh="ระบุชื่อโครงการวิจัย" data-placeholderEn="Enter project title"
                            placeholder="ระบุชื่อโครงการวิจัย">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="ปีงบประมาณ" data-en="Fiscal Year">ปีงบประมาณ</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="text" name="fiscal_year" required class="input-field"
                            data-placeholderTh="เช่น 2568" data-placeholderEn="e.g. 2025" placeholder="เช่น 2568">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="สังกัด" data-en="Affiliation">สังกัด</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <select name="affiliation" required class="input-field" id="affiliation-select">
                            <option value="" data-th="-- เลือกสังกัด --" data-en="-- Select Affiliation --">--
                                เลือกสังกัด --</option>
                            <option value="คณะพุทธศาสตร์" data-th="คณะพุทธศาสตร์" data-en="Faculty of Buddhism">
                                คณะพุทธศาสตร์</option>
                            <option value="คณะครุศาสตร์" data-th="คณะครุศาสตร์" data-en="Faculty of Education">
                                คณะครุศาสตร์</option>
                            <option value="คณะมนุษยศาสตร์" data-th="คณะมนุษยศาสตร์" data-en="Faculty of Humanities">
                                คณะมนุษยศาสตร์</option>
                            <option value="คณะสังคมศาสตร์" data-th="คณะสังคมศาสตร์"
                                data-en="Faculty of Social Sciences">คณะสังคมศาสตร์</option>
                            <option value="บัณฑิตวิทยาลัย" data-th="บัณฑิตวิทยาลัย" data-en="Graduate School">
                                บัณฑิตวิทยาลัย</option>
                            <option value="วิทยาลัยพุทธศาสตร์นานาชาติ" data-th="วิทยาลัยพุทธศาสตร์นานาชาติ"
                                data-en="International Buddhist Studies College">วิทยาลัยพุทธศาสตร์นานาชาติ</option>
                            <option value="วิทยาลัยธรรมทูต" data-th="วิทยาลัยธรรมทูต" data-en="Dhammaduta College">
                                วิทยาลัยธรรมทูต</option>
                            <option value="สถาบันวิจัยพุทธศาสตร์" data-th="สถาบันวิจัยพุทธศาสตร์"
                                data-en="Buddhist Research Institute">สถาบันวิจัยพุทธศาสตร์</option>
                            <option value="วิทยาเขตหนองคาย" data-th="วิทยาเขตหนองคาย" data-en="Nong Khai Campus">
                                วิทยาเขตหนองคาย</option>
                            <option value="วิทยาเขตนครศรีธรรมราช" data-th="วิทยาเขตนครศรีธรรมราช"
                                data-en="Nakhon Si Thammarat Campus">วิทยาเขตนครศรีธรรมราช</option>
                            <option value="วิทยาเขตเชียงใหม่" data-th="วิทยาเขตเชียงใหม่" data-en="Chiang Mai Campus">
                                วิทยาเขตเชียงใหม่</option>
                            <option value="วิทยาเขตขอนแก่น" data-th="วิทยาเขตขอนแก่น" data-en="Khon Kaen Campus">
                                วิทยาเขตขอนแก่น</option>
                            <option value="วิทยาเขตนครราชสีมา" data-th="วิทยาเขตนครราชสีมา"
                                data-en="Nakhon Ratchasima Campus">วิทยาเขตนครราชสีมา</option>
                            <option value="วิทยาเขตอุบลราชธานี" data-th="วิทยาเขตอุบลราชธานี"
                                data-en="Ubon Ratchathani Campus">วิทยาเขตอุบลราชธานี</option>
                            <option value="วิทยาเขตแพร่" data-th="วิทยาเขตแพร่" data-en="Phrae Campus">วิทยาเขตแพร่
                            </option>
                            <option value="วิทยาเขตสุรินทร์" data-th="วิทยาเขตสุรินทร์" data-en="Surin Campus">
                                วิทยาเขตสุรินทร์</option>
                            <option value="วิทยาเขตพะเยา" data-th="วิทยาเขตพะเยา" data-en="Phayao Campus">วิทยาเขตพะเยา
                            </option>
                            <option value="วิทยาเขตบาฬีศึกษาพุทธโฆส" data-th="วิทยาเขตบาฬีศึกษาพุทธโฆส"
                                data-en="Pali Studies Buddhagosa Campus">วิทยาเขตบาฬีศึกษาพุทธโฆส</option>
                            <option value="วิทยาเขตนครสวรรค์" data-th="วิทยาเขตนครสวรรค์" data-en="Nakhon Sawan Campus">
                                วิทยาเขตนครสวรรค์</option>
                            <option value="วิทยาเขตนครน่านเฉลิมพระเกียรติฯ" data-th="วิทยาเขตนครน่านเฉลิมพระเกียรติฯ"
                                data-en="Nan Campus">วิทยาเขตนครน่านเฉลิมพระเกียรติฯ</option>
                            <option value="วิทยาลัยสงฆ์ลำพูน" data-th="วิทยาลัยสงฆ์ลำพูน"
                                data-en="Lamphun Buddhist College">วิทยาลัยสงฆ์ลำพูน</option>
                            <option value="วิทยาลัยสงฆ์เลย" data-th="วิทยาลัยสงฆ์เลย" data-en="Loei Buddhist College">
                                วิทยาลัยสงฆ์เลย</option>
                            <option value="วิทยาลัยสงฆ์นครพนม" data-th="วิทยาลัยสงฆ์นครพนม"
                                data-en="Nakhon Phanom Buddhist College">วิทยาลัยสงฆ์นครพนม</option>
                            <option value="วิทยาลัยสงฆ์พุทธชินราช" data-th="วิทยาลัยสงฆ์พุทธชินราช"
                                data-en="Buddhachinaraj Buddhist College">วิทยาลัยสงฆ์พุทธชินราช</option>
                            <option value="วิทยาลัยสงฆ์บุรีรัมย์" data-th="วิทยาลัยสงฆ์บุรีรัมย์"
                                data-en="Buriram Buddhist College">วิทยาลัยสงฆ์บุรีรัมย์</option>
                            <option value="วิทยาลัยสงฆ์ปัตตานี" data-th="วิทยาลัยสงฆ์ปัตตานี"
                                data-en="Pattani Buddhist College">วิทยาลัยสงฆ์ปัตตานี</option>
                            <option value="วิทยาลัยสงฆ์พุทธโสธร" data-th="วิทยาลัยสงฆ์พุทธโสธร"
                                data-en="Buddhasothorn Buddhist College">วิทยาลัยสงฆ์พุทธโสธร</option>
                            <option value="วิทยาลัยสงฆ์เชียงราย" data-th="วิทยาลัยสงฆ์เชียงราย"
                                data-en="Chiang Rai Buddhist College">วิทยาลัยสงฆ์เชียงราย</option>
                            <option value="วิทยาลัยสงฆ์นครลำปาง" data-th="วิทยาลัยสงฆ์นครลำปาง"
                                data-en="Lampang Buddhist College">วิทยาลัยสงฆ์นครลำปาง</option>
                            <option value="วิทยาลัยสงฆ์ศรีสะเกษ" data-th="วิทยาลัยสงฆ์ศรีสะเกษ"
                                data-en="Si Saket Buddhist College">วิทยาลัยสงฆ์ศรีสะเกษ</option>
                            <option value="วิทยาลัยสงฆ์พุทธปัญญาศรีทวารวดี" data-th="วิทยาลัยสงฆ์พุทธปัญญาศรีทวารวดี"
                                data-en="Buddhapanya Dvaravati Buddhist College">วิทยาลัยสงฆ์พุทธปัญญาศรีทวารวดี
                            </option>
                            <option value="วิทยาลัยสงฆ์ชัยภูมิ" data-th="วิทยาลัยสงฆ์ชัยภูมิ"
                                data-en="Chaiyaphum Buddhist College">วิทยาลัยสงฆ์ชัยภูมิ</option>
                            <option value="วิทยาลัยสงฆ์พ่อขุนผาเมือง" data-th="วิทยาลัยสงฆ์พ่อขุนผาเมือง"
                                data-en="Pho Khun Pha Muang Buddhist College">วิทยาลัยสงฆ์พ่อขุนผาเมือง</option>
                            <option value="วิทยาลัยสงฆ์ร้อยเอ็ด" data-th="วิทยาลัยสงฆ์ร้อยเอ็ด"
                                data-en="Roi Et Buddhist College">วิทยาลัยสงฆ์ร้อยเอ็ด</option>
                            <option value="วิทยาลัยสงฆ์ราชบุรี" data-th="วิทยาลัยสงฆ์ราชบุรี"
                                data-en="Ratchaburi Buddhist College">วิทยาลัยสงฆ์ราชบุรี</option>
                            <option value="วิทยาลัยสงฆ์สุพรรณบุรีศรีสุวรรณภูมิ"
                                data-th="วิทยาลัยสงฆ์สุพรรณบุรีศรีสุวรรณภูมิ" data-en="Suphanburi Buddhist College">
                                วิทยาลัยสงฆ์สุพรรณบุรีศรีสุวรรณภูมิ</option>
                            <option value="วิทยาลัยสงฆ์พิจิตร" data-th="วิทยาลัยสงฆ์พิจิตร"
                                data-en="Phichit Buddhist College">วิทยาลัยสงฆ์พิจิตร</option>
                            <option value="วิทยาลัยสงฆ์สุราษฎร์ธานี" data-th="วิทยาลัยสงฆ์สุราษฎร์ธานี"
                                data-en="Surat Thani Buddhist College">วิทยาลัยสงฆ์สุราษฎร์ธานี</option>
                            <option value="วิทยาลัยสงฆ์ระยอง" data-th="วิทยาลัยสงฆ์ระยอง"
                                data-en="Rayong Buddhist College">วิทยาลัยสงฆ์ระยอง</option>
                            <option value="วิทยาลัยสงฆ์มหาสารคาม" data-th="วิทยาลัยสงฆ์มหาสารคาม"
                                data-en="Maha Sarakham Buddhist College">วิทยาลัยสงฆ์มหาสารคาม</option>
                            <option value="วิทยาลัยสงฆ์เพชรบุรี" data-th="วิทยาลัยสงฆ์เพชรบุรี"
                                data-en="Phetchaburi Buddhist College">วิทยาลัยสงฆ์เพชรบุรี</option>
                            <option value="วิทยาลัยสงฆ์อุทัยธานี" data-th="วิทยาลัยสงฆ์อุทัยธานี"
                                data-en="Uthai Thani Buddhist College">วิทยาลัยสงฆ์อุทัยธานี</option>
                            <option value="วิทยาลัยสงฆ์กาญจนบุรี ศรีไพบุลย์" data-th="วิทยาลัยสงฆ์กาญจนบุรี ศรีไพบุลย์"
                                data-en="Kanchanaburi Buddhist College">วิทยาลัยสงฆ์กาญจนบุรี ศรีไพบุลย์</option>
                            <option value="วิทยาลัยสงฆ์ชลบุรี" data-th="วิทยาลัยสงฆ์ชลบุรี"
                                data-en="Chonburi Buddhist College">วิทยาลัยสงฆ์ชลบุรี</option>
                            <option value="วิทยาลัยสงฆ์จันทบุรี" data-th="วิทยาลัยสงฆ์จันทบุรี"
                                data-en="Chanthaburi Buddhist College">วิทยาลัยสงฆ์จันทบุรี</option>
                            <option value="วิทยาลัยสงฆ์กำแพงเพชร" data-th="วิทยาลัยสงฆ์กำแพงเพชร"
                                data-en="Kamphaeng Phet Buddhist College">วิทยาลัยสงฆ์กำแพงเพชร</option>
                            <option value="วิทยาลัยสงฆ์ตาก" data-th="วิทยาลัยสงฆ์ตาก" data-en="Tak Buddhist College">
                                วิทยาลัยสงฆ์ตาก</option>
                            <option value="หน่วยวิทยบริการจังหวัดอุตรดิตถ์" data-th="หน่วยวิทยบริการจังหวัดอุตรดิตถ์"
                                data-en="Uttaradit Academic Service Unit">หน่วยวิทยบริการจังหวัดอุตรดิตถ์</option>
                            <option value="หน่วยวิทยบริการจังหวัดสระแก้ว" data-th="หน่วยวิทยบริการจังหวัดสระแก้ว"
                                data-en="Sa Kaeo Academic Service Unit">หน่วยวิทยบริการจังหวัดสระแก้ว</option>
                            <option value="หน่วยวิทยบริการจังหวัดสงขลา" data-th="หน่วยวิทยบริการจังหวัดสงขลา"
                                data-en="Songkhla Academic Service Unit">หน่วยวิทยบริการจังหวัดสงขลา</option>
                            <option value="หน่วยวิทยบริการจังหวัดสมุทรสงคราม"
                                data-th="หน่วยวิทยบริการจังหวัดสมุทรสงคราม"
                                data-en="Samut Songkhram Academic Service Unit">หน่วยวิทยบริการจังหวัดสมุทรสงคราม
                            </option>
                            <option value="หน่วยวิทยบริการจังหวัดกาฬสินธุ์" data-th="หน่วยวิทยบริการจังหวัดกาฬสินธุ์"
                                data-en="Kalasin Academic Service Unit">หน่วยวิทยบริการจังหวัดกาฬสินธุ์</option>
                            <option value="ห้องเรียนจังหวัดสิงห์บุรี" data-th="ห้องเรียนจังหวัดสิงห์บุรี"
                                data-en="Sing Buri Classroom">ห้องเรียนจังหวัดสิงห์บุรี</option>
                            <option value="มหาวชิราลงกรณบาลีเถรวาทราชวิทยาลัย"
                                data-th="มหาวชิราลงกรณบาลีเถรวาทราชวิทยาลัย"
                                data-en="Mahavajiralongkorn Pali Theravada University">
                                มหาวชิราลงกรณบาลีเถรวาทราชวิทยาลัย</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" data-th="ตำแหน่งทางวิชาการที่ขอ"
                            data-en="Applied Academic Position">
                            ตำแหน่งทางวิชาการที่ขอ
                        </label>
                        <select name="academic_position" class="input-field">
                            <option value="" data-th="-- เลือก --" data-en="-- Select --">-- เลือก --</option>
                            <option data-th="ผู้ช่วยศาสตราจารย์" data-en="Assistant Professor">ผู้ช่วยศาสตราจารย์
                            </option>
                            <option data-th="รองศาสตราจารย์" data-en="Associate Professor">รองศาสตราจารย์</option>
                            <option data-th="ศาสตราจารย์" data-en="Professor">ศาสตราจารย์</option>
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" data-th="สาขาที่ขอ"
                            data-en="Field of Study">สาขาที่ขอ</label>
                        <input type="text" name="branch" class="input-field" data-placeholderTh="เช่น พระพุทธศาสนา"
                            data-placeholderEn="e.g. Buddhism" placeholder="เช่น พระพุทธศาสนา">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="เบอร์โทรศัพท์" data-en="Phone Number">เบอร์โทรศัพท์</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="tel" required class="input-field" data-placeholderTh="เช่น 081-234-5678"
                            data-placeholderEn="e.g. 081-234-5678" placeholder="เช่น 081-234-5678">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="อีเมล" data-en="Email Address">อีเมล</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required class="input-field"
                            data-placeholderTh="example@mcu.ac.th" data-placeholderEn="example@mcu.ac.th"
                            placeholder="example@mcu.ac.th">
                    </div>
                </div>

                <!-- File Uploads -->
                <div class="border-t pt-6">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                        <i data-lucide="paperclip" class="w-5 h-5 mr-2"></i>
                        <span data-th="เอกสารแนบ (ต้องเป็นไฟล์ PDF เท่านั้น)" data-en="Attachments (PDF only)">เอกสารแนบ
                            (ต้องเป็นไฟล์ PDF เท่านั้น)</span>
                    </h3>
                    <div class="grid md:grid-cols-2 gap-x-8 gap-y-4">
                        <?php
                        $fileFields = [
                            ['name' => 'file_report', 'label_th' => '1. รายงานวิจัยฉบับสมบูรณ์', 'label_en' => '1. Complete Research Report'],
                            ['name' => 'file_self_assess', 'label_th' => '2. แบบประเมินตนเอง', 'label_en' => '2. Self-Assessment Form'],
                            ['name' => 'file_sp10', 'label_th' => '3. บันทึกข้อความ สวพ.10', 'label_en' => '3. Official Memo (BRI.10)'],
                            ['name' => 'file_sp10_1', 'label_th' => '4. แบบฟอร์มตรวจ สวพ.10.1', 'label_en' => '4. Checklist Form (BRI.10.1)'],
                            ['name' => 'file_cert', 'label_th' => '5. บทความวิจัยที่ได้รับการตีพิมพ์', 'label_en' => '5. Publication Certificate'],
                            ['name' => 'file_plagiarism', 'label_th' => '6. หลักฐานตรวจอักขราวิสุทธิ์', 'label_en' => '6. Plagiarism Check Evidence'],
                            ['name' => 'file_participation', 'label_th' => '7. หลักฐานการมีส่วนร่วมในงานวิจัย', 'label_en' => '7. Research Contribution Evidence'],
                            ['name' => 'file_ethics', 'label_th' => '8. เอกสารรับรองจริยธรรมการวิจัย', 'label_en' => '8. Research Ethics Certification']
                        ];
                        foreach ($fileFields as $field):
                            ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span data-th="<?php echo $field['label_th']; ?>"
                                        data-en="<?php echo $field['label_en']; ?>">
                                        <?php echo $field['label_th']; ?>
                                    </span> <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center file-group">
                                    <label
                                        class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-600 px-3 py-2 rounded-l hover:bg-gray-100 text-sm whitespace-nowrap flex items-center">
                                        <i data-lucide="cloud-upload" class="w-4 h-4 mr-2"></i>
                                        <span data-th="เลือกไฟล์ (PDF)" data-en="Choose File (PDF)">เลือกไฟล์ (PDF)</span>
                                        <input type="file" name="<?php echo $field['name']; ?>" accept=".pdf" required
                                            class="hidden" onchange="updateFileName(this)">
                                    </label>
                                    <div class="flex-1 border border-l-0 border-gray-300 py-2 px-3 bg-white text-gray-400 text-sm truncate rounded-r file-name"
                                        data-th="ยังไม่เลือกไฟล์" data-en="No file chosen">
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
                            <span data-th="ชื่อวารสารที่ตีพิมพ์" data-en="Journal Name">ชื่อวารสารที่ตีพิมพ์</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="journal_name" required class="input-field"
                            data-placeholderTh="ระบุชื่อวารสาร" data-placeholderEn="Enter journal name"
                            placeholder="ระบุชื่อวารสาร">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span data-th="จำนวน % การคัดลอก (ไม่เกิน 15%)"
                                data-en="Plagiarism Percentage (max 15%)">จำนวน % การคัดลอก (ไม่เกิน 15%)</span> <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="number" name="plagiarism_percent" required max="15" step="0.01" class="input-field"
                            data-placeholderTh="เช่น 12.50" data-placeholderEn="e.g. 12.50" placeholder="เช่น 12.50">
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
                        <!-- Dynamic rows will be inserted here -->
                    </div>

                    <input type="hidden" name="contributors" id="contributors_hidden" value="[]">
                </div>

                <!-- Agreement & Captcha -->
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <!-- Instruction Box -->
                    <div class="bg-red-50 border-2 border-red-500 p-4 rounded-lg mb-6">
                        <h4 class="text-red-700 font-bold mb-2 flex items-center">
                            <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                            <span data-th="สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล"
                                data-en="Post-Submission Instructions">สิ่งที่ต้องปฏิบัติภายหลังส่งข้อมูล</span>
                        </h4>
                        <ul class="text-red-600 text-sm space-y-1 list-decimal list-inside mb-2">
                            <li data-th="ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม"
                                data-en="Submit 1 hard copy of the complete research report">
                                ส่งเล่มเอกสารรายงานวิจัยฉบับสมบูรณ์ ฉบับจริง จำนวน 1 เล่ม</li>
                            <li data-th="พร้อมบันทึกข้อความ สวพ.10 จำนวน 1 ฉบับ"
                                data-en="Include 1 copy of the official memo (BRI.10)">พร้อมบันทึกข้อความ สวพ.10 จำนวน 1
                                ฉบับ</li>
                        </ul>
                        <p class="text-red-600 text-sm font-semibold"
                            data-th="มายัง สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย จ.พระนครศรีอยุธยา"
                            data-en="To: Buddhist Research Institute, MCU, Wang Noi, Ayutthaya">มายัง
                            สถาบันวิจัยพุทธศาสตร์ มจร อ.วังน้อย
                            จ.พระนครศรีอยุธยา</p>
                    </div>

                    <div class="flex items-start gap-3 mb-4">
                        <input type="checkbox" name="agreement" id="agreement" required
                            class="mt-1 w-4 h-4 text-mcu-pink rounded">
                        <label for="agreement" class="text-sm text-gray-700 cursor-pointer"
                            data-th="ข้าพเจ้าได้ดำเนินการตรวจสอบเอกสารข้างต้นเรียบร้อยแล้ว จึงขอนำส่งข้อมูลเพื่อให้สถาบันวิจัยพุทธศาสตร์รับรองผลงานวิจัยต่อไป"
                            data-en="I have verified the above documents and hereby submit the information for research accreditation by the Buddhist Research Institute.">
                            ข้าพเจ้าได้ดำเนินการตรวจสอบเอกสารข้างต้นเรียบร้อยแล้ว
                            จึงขอนำส่งข้อมูลเพื่อให้สถาบันวิจัยพุทธศาสตร์รับรองผลงานวิจัยต่อไป
                        </label>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-gray-200 px-4 py-2 rounded font-bold">
                            <span id="captchaNum1"></span> + <span id="captchaNum2"></span> = ?
                        </div>
                        <input type="number" id="captchaAnswer" class="w-24 p-2 border rounded text-center"
                            data-placeholderTh="คำตอบ" data-placeholderEn="Ans" placeholder="คำตอบ" required>
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
                    <span data-th="ยืนยันการส่งข้อมูล" data-en="Confirm Submission">ยืนยันการส่งข้อมูล</span>
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
            const msg = LanguageCore.currentLang === 'th' ? 'กรุณาเลือกไฟล์ PDF เท่านั้น' : 'Please select PDF files only';
            alert(msg);
            input.value = '';
            fileNameDisplay.textContent = LanguageCore.currentLang === 'th' ? 'ยังไม่เลือกไฟล์' : 'No file chosen';
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

    // Repeater Field logic for Research Contributors
    const contributorsContainer = document.getElementById('contributorsContainer');
    const addContributorBtn = document.getElementById('addContributorBtn');

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

    // Form submission
    document.getElementById('registrationForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Validate captcha
        const answer = parseInt(document.getElementById('captchaAnswer').value);
        const expected = parseInt(document.getElementById('captchaExpected').value);
        if (answer !== expected) {
            const msg = LanguageCore.currentLang === 'th' ? 'เลขยืนยันตัวตนไม่ถูกต้อง' : 'Invalid Identification Number';
            alert(msg);
            generateCaptcha();
            return;
        }

        // Compile contributors repeater data to JSON
        const contributorRows = document.querySelectorAll('.contributor-row');
        if (contributorRows.length === 0) {
            const msg = LanguageCore.currentLang === 'th' 
                ? 'กรุณากรอกข้อมูลสัดส่วนการมีส่วนร่วมในงานวิจัยอย่างน้อย 1 รายการ' 
                : 'Please add at least one research contributor';
            alert(msg);
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
                    // Force translate newly shown modal
                    LanguageCore.apply(LanguageCore.currentLang);
                }, 500);
            } else {
                loadingModal.classList.add('hidden');
                const msg = LanguageCore.currentLang === 'th' ? 'เกิดข้อผิดพลาด: ' : 'Error: ';
                alert(msg + result.message);
            }
        } catch (error) {
            clearInterval(interval);
            loadingModal.classList.add('hidden');
            const msg = LanguageCore.currentLang === 'th' ? 'เกิดข้อผิดพลาด: ' : 'Error: ';
            alert(msg + error.message);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
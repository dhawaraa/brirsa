<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Navbar -->
<nav class="border-b sticky top-0 z-40 shadow-sm bg-white border-gray-200">
    <div class="max-w-7xl mx-auto px-4 h-20 flex justify-between items-center">
        <!-- Logo -->
        <a href="index.php" class="flex items-center hover:opacity-80 transition-opacity">
            <div class="h-12 mr-3 flex items-center">
                <img src="<?php echo LOGO_URL; ?>" class="h-full w-auto object-contain" alt="MCU Logo">
            </div>
            <div>
                <h1 class="text-xl font-bold leading-none mb-1 text-mcu-blue" data-th="ระบบรับรองงานวิจัย"
                    data-en="Research Accreditation System">
                    ระบบรับรองงานวิจัย
                </h1>
                <p class="text-xs text-gray-500" data-th="สถาบันวิจัยพุทธศาสตร์ มจร"
                    data-en="Buddhist Research Institute, MCU">สถาบันวิจัยพุทธศาสตร์ มจร</p>
            </div>
        </a>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center justify-between flex-1 ml-8">
            <!-- Main Navigation -->
            <div class="flex items-center space-x-2 mx-auto">
                <a href="index.php" data-th="หน้าหลัก" data-en="Home"
                    class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'index' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    หน้าหลัก
                </a>
                <a href="register.php" data-th="ส่งข้อมูลขอรับรอง" data-en="Submit Request"
                    class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'register' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    ส่งข้อมูลขอรับรอง
                </a>
                <a href="status.php" data-th="ตรวจสอบสถานะ" data-en="Check Status"
                    class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'status' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    ตรวจสอบสถานะ
                </a>
            </div>

            <!-- Right Side: Language + Staff -->
            <div class="flex items-center space-x-3">
                <!-- Language Toggle -->
                <div class="flex items-center space-x-1">
                    <button onclick="LanguageCore.switch('th')" data-lang="th" class="lang-btn">TH</button>
                    <div class="text-gray-300 text-xs">|</div>
                    <button onclick="LanguageCore.switch('en')" data-lang="en" class="lang-btn">EN</button>
                </div>
                <div class="h-6 w-px bg-gray-300"></div>
                <!-- Staff Button -->
                <a href="admin/" data-th="เจ้าหน้าที่" data-en="Staff Only"
                    class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300 flex items-center hover:bg-gray-50">
                    <i data-lucide="lock" class="w-4 h-4 mr-2"></i>
                    เจ้าหน้าที่
                </a>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobileMenuBtn" class="md:hidden p-2">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white border-t p-4 space-y-2">
        <!-- Language Toggle Mobile -->
        <div class="flex items-center justify-end space-x-2 mb-4 pb-2 border-b">
            <span class="text-xs text-gray-400" data-th="ภาษา:" data-en="Language:">ภาษา:</span>
            <button onclick="LanguageCore.switch('th')" data-lang="th" class="lang-btn">TH</button>
            <button onclick="LanguageCore.switch('en')" data-lang="en" class="lang-btn">EN</button>
        </div>
        <a href="index.php" data-th="หน้าหลัก" data-en="Home"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'index' ? 'bg-gray-100 font-medium' : ''; ?>">
            หน้าหลัก
        </a>
        <a href="register.php" data-th="ส่งข้อมูลขอรับรอง" data-en="Submit Request"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'register' ? 'bg-gray-100 font-medium' : ''; ?>">
            ส่งข้อมูลขอรับรอง
        </a>
        <a href="status.php" data-th="ตรวจสอบสถานะ" data-en="Check Status"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'status' ? 'bg-gray-100 font-medium' : ''; ?>">
            ตรวจสอบสถานะ
        </a>
        <a href="admin/" data-th="เข้าสู่ระบบเจ้าหน้าที่" data-en="Staff Login"
            class="block px-4 py-3 rounded text-mcu-blue hover:bg-blue-50">
            เข้าสู่ระบบเจ้าหน้าที่
        </a>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', function () {
        const menu = document.getElementById('mobileMenu');
        menu.classList.toggle('hidden');
    });
</script>
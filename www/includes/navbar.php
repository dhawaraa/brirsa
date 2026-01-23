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
                <h1 class="text-xl font-bold leading-none mb-1 text-mcu-blue">
                    <?php echo SITE_NAME; ?>
                </h1>
                <p class="text-xs text-gray-500">สถาบันวิจัยพุทธศาสตร์ มจร</p>
            </div>
        </a>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center space-x-2">
            <a href="index.php"
                class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'index' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                หน้าหลัก
            </a>
            <a href="register.php"
                class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'register' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                ส่งข้อมูลขอรับรอง
            </a>
            <a href="status.php"
                class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentPage === 'status' ? 'bg-mcu-blue text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                ตรวจสอบสถานะ
            </a>
            <div class="h-6 w-px bg-gray-300 mx-2"></div>
            <a href="admin/"
                class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300 flex items-center hover:bg-gray-50">
                <i data-lucide="lock" class="w-4 h-4 mr-2"></i>
                เจ้าหน้าที่
            </a>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobileMenuBtn" class="md:hidden p-2">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white border-t p-4 space-y-2">
        <a href="index.php"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'index' ? 'bg-gray-100 font-medium' : ''; ?>">
            หน้าหลัก
        </a>
        <a href="register.php"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'register' ? 'bg-gray-100 font-medium' : ''; ?>">
            ส่งข้อมูลขอรับรอง
        </a>
        <a href="status.php"
            class="block px-4 py-3 rounded hover:bg-gray-100 <?php echo $currentPage === 'status' ? 'bg-gray-100 font-medium' : ''; ?>">
            ตรวจสอบสถานะ
        </a>
        <a href="admin/" class="block px-4 py-3 rounded text-mcu-blue hover:bg-blue-50">
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
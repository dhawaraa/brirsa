<?php
/**
 * Admin Dashboard - ระบบจัดการหลังบ้าน (Flowbite)
 */
$pageTitle = "ภาพรวมระบบ";
include 'includes/header.php';

$pdo = getDB();

// Month helper arrays
$monthNamesFull = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];

// Retrieve and sanitize active filters (Default to 'all' to show all-time summary on first load)
$filterYear = isset($_GET['year']) ? sanitize($_GET['year']) : 'all';
$filterMonth = isset($_GET['month']) ? sanitize($_GET['month']) : 'all';

// Build dynamic WHERE clause
$conditions = [];
$params = [];

if ($filterYear !== 'all' && $filterYear !== '') {
    $conditions[] = "(YEAR(created_at) + 543) = ?";
    $params[] = (int)$filterYear;
}
if ($filterMonth !== 'all' && $filterMonth !== '') {
    $conditions[] = "MONTH(created_at) = ?";
    $params[] = (int)$filterMonth;
}

$whereClause = "";
if (count($conditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $conditions);
}

// 1. Basic statistics
$stats = [
    'total' => $pdo->prepare("SELECT COUNT(*) FROM requests" . $whereClause),
    'checking' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='checking'" : " WHERE status='checking'")),
    'ch_edit' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='ch_edit'" : " WHERE status='ch_edit'")),
    'committee' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='committee'" : " WHERE status='committee'")),
    'correction' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='correction'" : " WHERE status='correction'")),
    'resubmitted' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='resubmitted'" : " WHERE status='resubmitted'")),
    'rejected' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='rejected'" : " WHERE status='rejected'")),
    'approved' => $pdo->prepare("SELECT COUNT(*) FROM requests" . ($whereClause ? $whereClause . " AND status='approved'" : " WHERE status='approved'")),
];

foreach ($stats as $key => $stmt) {
    $stmt->execute($params);
    $stats[$key] = $stmt->fetchColumn();
}

$years = $pdo->query("SELECT DISTINCT (YEAR(created_at) + 543) as year FROM requests ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years))
    $years = [date('Y') + 543];

// 2. Monthly Stats for the selected filters
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $currentConditions = [];
    $currentParams = [];
    
    if ($filterYear !== 'all' && $filterYear !== '') {
        $currentConditions[] = "(YEAR(created_at) + 543) = ?";
        $currentParams[] = (int)$filterYear;
    }
    
    $currentConditions[] = "MONTH(created_at) = ?";
    $currentParams[] = $m;
    
    if ($filterMonth !== 'all' && $filterMonth !== '') {
        $currentConditions[] = "MONTH(created_at) = ?";
        $currentParams[] = (int)$filterMonth;
    }
    
    $whereMonthly = " WHERE " . implode(" AND ", $currentConditions);
    
    $count = $pdo->prepare("SELECT COUNT(*) FROM requests" . $whereMonthly);
    $count->execute($currentParams);
    $monthlyData[$m] = $count->fetchColumn();
}
$maxMonthly = max($monthlyData) ?: 1;

// 3. Affiliation statistics
$affStatsQuery = "SELECT affiliation, COUNT(*) as count FROM requests" . $whereClause . " GROUP BY affiliation ORDER BY count DESC";
$affStatsStmt = $pdo->prepare($affStatsQuery);
$affStatsStmt->execute($params);
$affiliationData = $affStatsStmt->fetchAll();
$activeAffiliations = array_filter($affiliationData, function($row) { return $row['count'] > 0; });
$maxAffCount = count($activeAffiliations) > 0 ? max(array_column($activeAffiliations, 'count')) : 1;

// 4. Branch statistics
$branchStatsQuery = "SELECT branch, COUNT(*) as count FROM requests" . $whereClause . " GROUP BY branch ORDER BY count DESC";
$branchStatsStmt = $pdo->prepare($branchStatsQuery);
$branchStatsStmt->execute($params);
$branchData = $branchStatsStmt->fetchAll();
$activeBranches = array_filter($branchData, function($row) { return $row['count'] > 0; });
$maxBranchCount = count($activeBranches) > 0 ? max(array_column($activeBranches, 'count')) : 1;

// 5. Academic Position statistics (for Pie chart)
$posStatsQuery = "SELECT academic_position, COUNT(*) as count FROM requests" . $whereClause . " GROUP BY academic_position";
$posStatsStmt = $pdo->prepare($posStatsQuery);
$posStatsStmt->execute($params);
$positionData = $posStatsStmt->fetchAll();

$posTotal = 0;
$posCounts = [
    'ผู้ช่วยศาสตราจารย์' => 0,
    'รองศาสตราจารย์' => 0,
    'ศาสตราจารย์' => 0,
    'อื่นๆ/ไม่ได้ระบุ' => 0
];
foreach ($positionData as $row) {
    $pos = trim($row['academic_position'] ?: '');
    if ($pos === '') {
        $pos = 'อื่นๆ/ไม่ได้ระบุ';
    }
    if (isset($posCounts[$pos])) {
        $posCounts[$pos] += $row['count'];
    } else {
        $posCounts['อื่นๆ/ไม่ได้ระบุ'] += $row['count'];
    }
    $posTotal += $row['count'];
}

$posColors = [
    'ผู้ช่วยศาสตราจารย์' => ['stroke' => 'text-blue-500', 'bg' => 'bg-blue-500'],
    'รองศาสตราจารย์' => ['stroke' => 'text-purple-500', 'bg' => 'bg-purple-500'],
    'ศาสตราจารย์' => ['stroke' => 'text-pink-500', 'bg' => 'bg-pink-500'],
    'อื่นๆ/ไม่ได้ระบุ' => ['stroke' => 'text-gray-400', 'bg' => 'bg-gray-400']
];

$accumulatedPercentage = 0;
$slices = [];
foreach ($posCounts as $label => $count) {
    if ($posTotal > 0 && $count > 0) {
        $percentage = ($count / $posTotal) * 100;
        $dashoffset = 100 - $accumulatedPercentage;
        $slices[] = [
            'label' => $label,
            'count' => $count,
            'percentage' => $percentage,
            'dasharray' => "$percentage " . (100 - $percentage),
            'dashoffset' => $dashoffset,
            'color' => $posColors[$label]
        ];
        $accumulatedPercentage += $percentage;
    }
}

$monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">ภาพรวมระบบ</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ยินดีต้อนรับกลับมา, ข้อมูลล่าสุด ณ วันที่
            <?php echo formatThaiDate(date('Y-m-d H:i:s')); ?>
        </p>
    </div>
    <?php if (is_role('super_admin') || is_role('data_admin') || is_role('executive')): ?>
        <form method="GET" action="dashboard.php" class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full md:w-auto">
            <!-- ปีที่ส่งคำขอ -->
            <div class="flex items-center space-x-2">
                <label for="year" class="text-xs font-bold text-gray-500 uppercase dark:text-gray-400 whitespace-nowrap">ปีที่ส่งคำขอ</label>
                <select name="year" id="year"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="all" <?php echo $filterYear === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $filterYear == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- เดือน -->
            <div class="flex items-center space-x-2">
                <label for="month" class="text-xs font-bold text-gray-500 uppercase dark:text-gray-400 whitespace-nowrap">เดือน</label>
                <select name="month" id="month"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="all" <?php echo $filterMonth === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $filterMonth == $m ? 'selected' : ''; ?>>
                            <?php echo $monthNamesFull[$m]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- ปุ่มค้นหา -->
            <button type="submit"
                class="px-5 py-2 text-sm font-medium text-white bg-mcu-blue rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none transition-all text-center">
                ค้นหา
            </button>
            <!-- ปุ่มล้างค่า -->
            <a href="dashboard.php"
                class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 text-center transition-all">
                ล้างค่า
            </a>
        </form>
    <?php endif; ?>
</div>

<!-- Statistics Cards (Softer Design) -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <!-- Total -->
    <?php $canViewRequests = is_role('super_admin') || is_role('data_admin'); ?>
    <<?php echo $canViewRequests ? 'a href="requests.php?status=all"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-indigo-600 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded dark:bg-indigo-900/40">TOTAL</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total']); ?></h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">คำขอทั้งหมด</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>

    <!-- Checking -->
    <<?php echo $canViewRequests ? 'a href="requests.php?status=checking"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-amber-400 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded dark:bg-amber-900/40">CHECK</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo number_format($stats['checking'] + $stats['resubmitted']); ?>
        </h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">รอการตรวจสอบ/ตรวจซ้ำ</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>

    <!-- Committee -->
    <<?php echo $canViewRequests ? 'a href="requests.php?status=committee"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-purple-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded dark:bg-purple-900/40">BOARD</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['committee']); ?>
        </h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">เสนอกรรมการ</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>

    <!-- Correction -->
    <<?php echo $canViewRequests ? 'a href="requests.php?status=correction"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-orange-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded dark:bg-orange-900/40">FIX</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php echo number_format($stats['correction'] + $stats['ch_edit']); ?>
        </h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">อยู่ระหว่างนักวิจัยแก้ไข</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>

    <!-- Rejected -->
    <<?php echo $canViewRequests ? 'a href="requests.php?status=rejected"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-red-600 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-red-50 dark:bg-red-900/30 rounded-lg">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded dark:bg-red-900/40">REJECT</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['rejected']); ?>
        </h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">ไม่รับรอง</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>

    <!-- Approved -->
    <<?php echo $canViewRequests ? 'a href="requests.php?status=approved"' : 'div'; ?>
        class="block p-4 bg-white border-l-4 border-emerald-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all
        <?php echo $canViewRequests ? 'hover:shadow-md hover:scale-[1.02]' : ''; ?>">
        <div class="flex items-center justify-between mb-2">
            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span
                class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded dark:bg-emerald-900/40">DONE</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['approved']); ?>
        </h3>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">อนุมัติรับรอง</p>
    </<?php echo $canViewRequests ? 'a' : 'div'; ?>>
</div>


<!-- 1. Chart: Academic Position (ตำแหน่งทางวิชาการที่ขอ) - Donut/Pie Chart -->
<div class="grid grid-cols-1 gap-8 mb-8">
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">สัดส่วนตามตำแหน่งทางวิชาการที่ขอ</h3>
        <div class="flex flex-col md:flex-row items-center justify-around space-y-6 md:space-y-0">
            <!-- Donut Chart SVG -->
            <div class="relative w-48 h-48 flex-shrink-0">
                <?php if ($posTotal === 0): ?>
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400">ไม่มีข้อมูล</div>
                <?php else: ?>
                    <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
                        <!-- Background Circle -->
                        <circle cx="18" cy="18" r="15.915" fill="transparent" stroke="#f3f4f6" stroke-width="4.5" class="dark:stroke-gray-700"></circle>
                        
                        <?php foreach ($slices as $slice): ?>
                            <circle cx="18" cy="18" r="15.915" fill="transparent" 
                                stroke="currentColor"
                                class="<?php echo $slice['color']['stroke']; ?> transition-all duration-1000 ease-out" 
                                stroke-width="4" 
                                stroke-dasharray="<?php echo $slice['dasharray']; ?>" 
                                stroke-dashoffset="<?php echo $slice['dashoffset']; ?>"
                                style="stroke-linecap: round;"></circle>
                        <?php endforeach; ?>
                    </svg>
                    <!-- Center Inner Circle to create Donut hole -->
                    <div class="absolute inset-5 bg-white dark:bg-gray-800 rounded-full flex flex-col items-center justify-center shadow-inner">
                        <span class="text-2xl font-extrabold text-gray-900 dark:text-white"><?php echo $posTotal; ?></span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase">คำขอทั้งหมด</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Legend Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full md:w-auto">
                <?php if ($posTotal > 0): ?>
                    <?php foreach ($posCounts as $label => $count): 
                        if ($count === 0) continue;
                        $pct = ($count / $posTotal) * 100;
                        $col = $posColors[$label];
                    ?>
                        <div class="flex items-center space-x-3 bg-gray-50 dark:bg-gray-700/30 p-3 rounded-xl border border-gray-100 dark:border-gray-700/50">
                            <span class="w-4 h-4 rounded-full <?php echo $col['bg']; ?> flex-shrink-0"></span>
                            <div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($label); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo $count; ?> คำขอ (<?php echo number_format($pct, 1); ?>%)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. Grid for Affiliation & Branch bar charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Chart: Affiliation (สังกัด) -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สัดส่วนคำขอตามสังกัด</h3>
        <div class="space-y-4 flex-1 overflow-y-auto max-h-[350px] pr-2">
            <?php if (empty($activeAffiliations)): ?>
                <div class="text-center text-gray-400 py-10">ไม่มีข้อมูล</div>
            <?php else: ?>
                <?php foreach ($activeAffiliations as $row): 
                    $pct = ($row['count'] / $maxAffCount) * 100;
                    $sharePct = $stats['total'] > 0 ? ($row['count'] / $stats['total']) * 100 : 0;
                ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1 font-semibold text-gray-700 dark:text-gray-300">
                            <span><?php echo htmlspecialchars($row['affiliation'] ?: 'ไม่ระบุ'); ?></span>
                            <span><?php echo $row['count']; ?> คำขอ (<?php echo number_format($sharePct, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chart: Branch (สาขาที่ขอ) -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สัดส่วนตามสาขาที่ขอ</h3>
        <div class="space-y-4 flex-1 overflow-y-auto max-h-[350px] pr-2">
            <?php if (empty($activeBranches)): ?>
                <div class="text-center text-gray-400 py-10">ไม่มีข้อมูล</div>
            <?php else: ?>
                <?php foreach ($activeBranches as $row): 
                    $pct = ($row['count'] / $maxBranchCount) * 100;
                    $sharePct = $stats['total'] > 0 ? ($row['count'] / $stats['total']) * 100 : 0;
                ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1 font-semibold text-gray-700 dark:text-gray-300">
                            <span><?php echo htmlspecialchars($row['branch'] ?: 'ไม่ระบุ'); ?></span>
                            <span><?php echo $row['count']; ?> คำขอ (<?php echo number_format($sharePct, 1); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 3. Grid for Monthly Trend & Status Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mb-8 items-stretch">
    <!-- Left: Monthly Trend (70%) -->
    <div class="lg:col-span-7">
        <div
            class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 h-full flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">สถิติการยื่นคำขอรายเดือน</h3>
                    <p class="text-sm text-gray-500 mt-1"><?php echo $filterYear === 'all' ? 'ข้อมูลทั้งหมดทุกปีงบประมาณ' : 'ประจำปีพุทธศักราช ' . $filterYear; ?></p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="flex items-center text-xs font-medium text-gray-500">
                        <span class="w-3 h-3 bg-mcu-blue rounded-sm me-1.5"></span> จำนวนคำขอ
                    </span>
                </div>
            </div>

            <div class="flex-1 flex items-end justify-between space-x-2 md:space-x-4 min-h-[300px] pb-2">
                <?php for ($m = 1; $m <= 12; $m++):
                    $height = ($monthlyData[$m] / $maxMonthly) * 100;
                    ?>
                    <div class="flex-1 flex flex-col items-center group">
                        <div class="relative w-full flex flex-col items-center">
                            <!-- Tooltip -->
                            <div
                                class="absolute -top-10 bg-gray-900 text-white text-[11px] px-2 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-20 shadow-xl">
                                <?php echo $monthlyData[$m]; ?> รายการ
                                <div class="absolute bottom-[-4px] left-1/2 -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45">
                                </div>
                            </div>

                            <!-- Bar -->
                            <div
                                class="w-full max-w-[40px] bg-blue-50 dark:bg-blue-900/10 rounded-t-xl relative h-64 flex flex-col justify-end">
                                <!-- Number Label -->
                                <?php if ($monthlyData[$m] > 0): ?>
                                    <div class="absolute w-full text-center font-bold text-[10px] text-blue-700 dark:text-blue-400 mb-1"
                                        style="bottom: <?php echo max($height, 5); ?>%; transform: translateY(-4px);">
                                        <?php echo $monthlyData[$m]; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="w-full bg-gradient-to-t from-blue-700 to-indigo-500 rounded-t-xl transition-all duration-700 ease-out group-hover:from-blue-600 group-hover:to-indigo-400"
                                    style="height: <?php echo max($height, $monthlyData[$m] > 0 ? 5 : 0); ?>%">
                                    <div
                                        class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="text-[11px] text-gray-400 mt-4 font-bold group-hover:text-mcu-blue transition-colors">
                            <?php echo $monthNames[$m]; ?>
                        </span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Right: Status Distribution (30%) -->
    <div class="lg:col-span-3">
        <div
            class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 h-full flex flex-col">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">สถานะปัจจุบัน</h3>
            <p class="text-sm text-gray-500 mb-8">(Distribution)</p>

            <div class="space-y-6 flex-1">
                <?php
                $flow = [
                    ['label' => 'ตรวจสอบ/ตรวจซ้ำ', 'count' => $stats['checking'] + $stats['resubmitted'], 'color' => 'amber'],
                    ['label' => 'เสนอกรรมการ', 'count' => $stats['committee'], 'color' => 'purple'],
                    ['label' => 'อยู่ระหว่างแก้ไข', 'count' => $stats['correction'] + $stats['ch_edit'], 'color' => 'orange'],
                    ['label' => 'อนุมัติรับรอง', 'count' => $stats['approved'], 'color' => 'emerald'],
                    ['label' => 'ไม่รับรอง', 'count' => $stats['rejected'], 'color' => 'rose'],
                ];
                foreach ($flow as $item):
                    $pct = $stats['total'] > 0 ? ($item['count'] / $stats['total']) * 100 : 0;
                    $colorClass = [
                        'amber' => 'bg-amber-500 text-amber-600',
                        'purple' => 'bg-purple-500 text-purple-600',
                        'orange' => 'bg-orange-500 text-orange-600',
                        'emerald' => 'bg-emerald-500 text-emerald-600',
                        'rose' => 'bg-rose-500 text-rose-600'
                    ][$item['color']];
                    $parts = explode(' ', $colorClass);
                    $bg = $parts[0];
                    $txt = $parts[1];
                    ?>
                    <div>
                        <div class="flex justify-between text-[12px] mb-2 font-bold">
                            <span class="text-gray-600 dark:text-gray-400"><?php echo $item['label']; ?></span>
                            <span class="<?php echo $txt; ?>"><?php echo number_format($pct, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 dark:bg-gray-700/50 overflow-hidden p-0.5">
                            <div class="<?php echo $bg; ?> h-full rounded-full transition-all duration-1000 shadow-sm"
                                style="width: <?php echo $pct; ?>%"></div>
                        </div>
                        <div class="mt-1 text-right">
                            <span class="text-[10px] text-gray-400"><?php echo number_format($item['count']); ?>
                                รายการ</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
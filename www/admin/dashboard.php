<?php
/**
 * Admin Dashboard - ระบบจัดการหลังบ้าน (Flowbite)
 */
$pageTitle = "ภาพรวมระบบ";
include 'includes/header.php';

$pdo = getDB();

// 1. Basic statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn(),
    'checking' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='checking'")->fetchColumn(),
    'committee' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='committee'")->fetchColumn(),
    'correction' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='correction'")->fetchColumn(),
    'resubmitted' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='resubmitted'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='rejected'")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='approved'")->fetchColumn(),
];

// 2. Monthly Stats for the current year
$currentYear = date('Y');
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $monthStr = sprintf("%04d-%02d", $currentYear, $m);
    $count = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $count->execute([$monthStr]);
    $monthlyData[$m] = $count->fetchColumn();
}
$maxMonthly = max($monthlyData) ?: 1;

// 3. Yearly Stats
$yearlyStats = $pdo->query("SELECT fiscal_year, COUNT(*) as count FROM requests GROUP BY fiscal_year ORDER BY fiscal_year DESC")->fetchAll();

// 4. Recent Requests
$recentStmt = $pdo->query("SELECT * FROM requests ORDER BY created_at DESC LIMIT 5");
$recentRequests = $recentStmt->fetchAll();

$monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">ภาพรวมระบบ</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ยินดีต้อนรับกลับมา, ข้อมูลล่าสุด ณ วันที่
            <?php echo formatThaiDate(date('Y-m-d H:i:s')); ?></p>
    </div>
    <div class="flex space-x-3">
        <a href="requests.php"
            class="px-5 py-2.5 text-sm font-medium text-white bg-mcu-blue rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none transition-all">
            ดูรายการทั้งหมด
        </a>
    </div>
</div>

<!-- Premium Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total -->
    <div
        class="relative overflow-hidden p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 group hover:shadow-lg transition-all duration-300">
        <div
            class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 dark:bg-blue-900/20 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors">
        </div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span
                    class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md dark:bg-blue-900/40">TOTAL</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total']); ?>
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">คำขอทั้งหมด</p>
        </div>
    </div>

    <!-- Checking -->
    <div
        class="relative overflow-hidden p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 group hover:shadow-lg transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-yellow-50 dark:bg-yellow-900/20 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span
                    class="text-xs font-bold text-yellow-600 bg-yellow-50 px-2 py-1 rounded-md dark:bg-yellow-900/40">NEW</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php echo number_format($stats['checking']); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">รอตรวจสอบ</p>
        </div>
    </div>

    <!-- Approved -->
    <div
        class="relative overflow-hidden p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 group hover:shadow-lg transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 dark:bg-green-900/20 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span
                    class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-md dark:bg-green-900/40">DONE</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php echo number_format($stats['approved']); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">อนุมัติรับรอง</p>
        </div>
    </div>

    <!-- Correction -->
    <div
        class="relative overflow-hidden p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 group hover:shadow-lg transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-50 dark:bg-orange-900/20 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <span
                    class="text-xs font-bold text-orange-600 bg-orange-50 px-2 py-1 rounded-md dark:bg-orange-900/40">FIX</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php echo number_format($stats['correction'] + $stats['resubmitted']); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แจ้งแก้ไข</p>
        </div>
    </div>

    <!-- Rejected -->
    <div
        class="relative overflow-hidden p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 group hover:shadow-lg transition-all duration-300">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-50 dark:bg-red-900/20 rounded-full blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span
                    class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded-md dark:bg-red-900/40">REJECT</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php echo number_format($stats['rejected']); ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ไม่รับรอง/ยกเลิก</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Trends and Charts -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Monthly Trend Chart -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white italic">สถิติการยื่นคำขอรายเดือน (ปี
                    <?php echo $currentYear + 543; ?>)</h3>
                <span class="text-xs font-medium text-gray-500">หน่วย: จำนวนคำขอ</span>
            </div>
            <div class="flex items-end justify-between space-x-2 h-48 px-2 overflow-x-auto">
                <?php for ($m = 1; $m <= 12; $m++):
                    $height = ($monthlyData[$m] / $maxMonthly) * 100;
                    ?>
                    <div class="flex flex-col items-center flex-1 group">
                        <div class="relative flex flex-col items-center w-full">
                            <div
                                class="absolute -top-8 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                <?php echo $monthlyData[$m]; ?> รายการ
                            </div>
                            <div class="w-full bg-blue-100 dark:bg-blue-900/30 rounded-t-lg transition-all duration-500 hover:bg-mcu-blue dark:hover:bg-blue-600"
                                style="height: <?php echo max($height, 5); ?>%"></div>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-2 font-medium"><?php echo $monthNames[$m]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">คำ ขอล่าสุด</h3>
                <a href="requests.php" class="text-sm text-blue-600 hover:underline">ดูทั้งหมด</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3">รหัส/ผู้ขอ</th>
                            <th class="px-6 py-3">โครงการ</th>
                            <th class="px-6 py-3">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php foreach ($recentRequests as $req):
                            $st = $STATUS_OPTIONS[$req['status']] ?? $STATUS_OPTIONS['checking'];
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-mcu-blue dark:text-blue-400">
                                        <?php echo $req['request_id']; ?></div>
                                    <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($req['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 truncate max-w-[200px]"
                                    title="<?php echo htmlspecialchars($req['project_title']); ?>">
                                    <?php echo htmlspecialchars($req['project_title']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-[11px] font-bold px-2 py-0.5 rounded-full 
                                    <?php echo $st['color'] === 'blue' ? 'bg-blue-100 text-blue-700' :
                                        ($st['color'] === 'green' ? 'bg-green-100 text-green-700' :
                                            ($st['color'] === 'orange' ? 'bg-orange-100 text-orange-700' :
                                                ($st['color'] === 'red' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'))); ?>">
                                        <?php echo $st['label']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Yearly Stats and Distributions -->
    <div class="space-y-8">
        <!-- Yearly Breakdown -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 underline decoration-mcu-blue decoration-2">
                สถิติรายปีงบประมาณ</h3>
            <div class="space-y-4">
                <?php foreach ($yearlyStats as $y): ?>
                    <div
                        class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:translate-x-1 transition-transform">
                        <div
                            class="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold shadow-md shadow-blue-200 dark:shadow-none">
                            <?php echo $y['fiscal_year']; ?>
                        </div>
                        <div class="ms-4 flex-1">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">ประจำปี
                                    <?php echo $y['fiscal_year']; ?></span>
                                <span class="text-sm font-extrabold text-blue-600"><?php echo $y['count']; ?> รายการ</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2 dark:bg-gray-600">
                                <div class="bg-blue-600 h-1.5 rounded-full"
                                    style="width: <?php echo ($y['count'] / $stats['total']) * 100; ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($yearlyStats)): ?>
                    <p class="text-center text-gray-400 py-4 italic text-sm">ยังไม่มีข้อมูลสถิติรายปี</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Workflow Status Distribution -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 italic">สถานะปัจจุบัน (Distribution)</h3>
            <div class="space-y-4">
                <?php
                $flow = [
                    ['label' => 'ตรวจสอบเบื้องต้น', 'count' => $stats['checking'], 'color' => 'blue'],
                    ['label' => 'เสนอกรรมการ', 'count' => $stats['committee'], 'color' => 'purple'],
                    ['label' => 'อยู่ระหว่างแก้ไข', 'count' => $stats['correction'] + $stats['resubmitted'], 'color' => 'orange'],
                    ['label' => 'อนุมัติเรียบร้อย', 'count' => $stats['approved'], 'color' => 'green'],
                    ['label' => 'ไม่รับรอง/ยกเลิก', 'count' => $stats['rejected'], 'color' => 'red'],
                ];
                foreach ($flow as $item):
                    $pct = $stats['total'] > 0 ? ($item['count'] / $stats['total']) * 100 : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-[11px] mb-1 font-bold uppercase tracking-wider">
                            <span class="text-gray-500"><?php echo $item['label']; ?></span>
                            <span
                                class="text-<?php echo $item['color']; ?>-600"><?php echo number_format($pct, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 dark:bg-gray-700 overflow-hidden">
                            <div class="bg-<?php echo $item['color']; ?>-500 h-2 rounded-full transition-all duration-1000"
                                style="width: <?php echo $pct; ?>%"></div>
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
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
    'ch_edit' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='ch_edit'")->fetchColumn(),
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

// 3. Recent Requests
$recentStmt = $pdo->query("SELECT * FROM requests ORDER BY created_at DESC LIMIT 5");
$recentRequests = $recentStmt->fetchAll();

$monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">ภาพรวมระบบ</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ยินดีต้อนรับกลับมา, ข้อมูลล่าสุด ณ วันที่
            <?php echo formatThaiDate(date('Y-m-d H:i:s')); ?>
        </p>
    </div>
    <div class="flex space-x-3">
        <a href="requests.php"
            class="px-5 py-2.5 text-sm font-medium text-white bg-mcu-blue rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none transition-all">
            ดูรายการทั้งหมด
        </a>
    </div>
</div>

<!-- Statistics Cards (Softer Design) -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <!-- Total -->
    <a href="requests.php?status=all"
        class="block p-4 bg-white border-l-4 border-indigo-600 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>

    <!-- Checking -->
    <a href="requests.php?status=checking"
        class="block p-4 bg-white border-l-4 border-amber-400 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>

    <!-- Committee -->
    <a href="requests.php?status=committee"
        class="block p-4 bg-white border-l-4 border-purple-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>

    <!-- Correction -->
    <a href="requests.php?status=correction"
        class="block p-4 bg-white border-l-4 border-orange-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>

    <!-- Rejected -->
    <a href="requests.php?status=rejected"
        class="block p-4 bg-white border-l-4 border-red-600 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>

    <!-- Approved -->
    <a href="requests.php?status=approved"
        class="block p-4 bg-white border-l-4 border-emerald-500 rounded-xl shadow-sm dark:bg-gray-800 transition-all hover:shadow-md hover:scale-[1.02]">
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
    </a>
</div>


<div class="grid grid-cols-1 lg:grid-cols-10 gap-8 mb-8 items-stretch">
    <!-- Left: Monthly Trend (70%) -->
    <div class="lg:col-span-7">
        <div
            class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 h-full flex flex-col">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">สถิติการยื่นคำขอรายเดือน</h3>
                    <p class="text-sm text-gray-500 mt-1">ประจำปีพุทธศักราช <?php echo $currentYear + 543; ?></p>
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
                                class="w-full max-w-[40px] bg-blue-50 dark:bg-blue-900/10 rounded-t-xl relative overflow-hidden h-64 flex flex-col justify-end">
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

<!-- Recent Activity (Full Width) -->
<div
    class="bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden mb-8">
    <div
        class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">คำขอล่าสุด</h3>
        <a href="requests.php"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
            ดูรายการทั้งหมด
            <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">รหัสคำขอ</th>
                    <th class="px-6 py-4">ชื่อผู้ยื่นขอ</th>
                    <th class="px-6 py-4">ชื่อโครงการวิจัย</th>
                    <th class="px-6 py-4">สถานะ</th>
                    <th class="px-6 py-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($recentRequests as $req):
                    $st = $STATUS_OPTIONS[$req['status']] ?? $STATUS_OPTIONS['checking'];
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div
                                class="font-extrabold text-blue-700 dark:text-blue-400 group-hover:scale-105 transition-transform origin-left">
                                <?php echo $req['request_id']; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-gray-200">
                                <?php echo htmlspecialchars($req['name']); ?>
                            </div>
                            <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($req['affiliation']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="truncate max-w-[400px] font-medium"
                                title="<?php echo htmlspecialchars($req['project_title']); ?>">
                                <?php echo htmlspecialchars($req['project_title']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold shadow-sm
                            <?php
                            switch ($st['color']) {
                                case 'blue':
                                    echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
                                    break;
                                case 'green':
                                    echo 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
                                    break;
                                case 'orange':
                                    echo 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300';
                                    break;
                                case 'red':
                                    echo 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                            }
                            ?>">
                                <span class="w-1.5 h-1.5 rounded-full me-1.5 <?php
                                switch ($st['color']) {
                                    case 'blue':
                                        echo 'bg-blue-500';
                                        break;
                                    case 'green':
                                        echo 'bg-emerald-500';
                                        break;
                                    case 'orange':
                                        echo 'bg-orange-500';
                                        break;
                                    case 'red':
                                        echo 'bg-rose-500';
                                        break;
                                    default:
                                        echo 'bg-gray-500';
                                }
                                ?>"></span>
                                <?php echo $st['label']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="view.php?id=<?php echo $req['id']; ?>"
                                class="text-blue-600 hover:text-blue-800 font-bold text-xs p-2 bg-blue-50 rounded-lg dark:bg-blue-900/20 dark:hover:bg-blue-900/40">ตรวจสอบ</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
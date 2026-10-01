<?php
/**
 * Admin Checklists List V2 (Overview of evaluations)
 */
$pageTitle = "แบบตรวจรับรองงานวิจัย";
require_once '../includes/config.php';
$pdo = getDB();

// Access Control
check_role(['super_admin', 'data_admin', 'committee']);

$filterSearch = sanitize($_GET['search'] ?? '');
$filterDecision = sanitize($_GET['decision'] ?? 'all');
$filterYear = sanitize($_GET['year'] ?? '');
$filterScope = sanitize($_GET['scope'] ?? 'active'); // 'active' (default: เฉพาะงานที่ต้องตรวจ) or 'all' (รวมงานที่เสร็จสิ้นแล้ว)

// Pagination
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 15;
if (!in_array($limit, [15, 30, 50, 100])) $limit = 15;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query conditions
$where = " WHERE 1=1";
$params = [];

// ถ้าเลือก scope เป็น active (ค่าเริ่มต้น) จะซ่อน 4.ไม่รับรอง (rejected) และ 5.อนุมัติรับรอง (approved)
if ($filterScope === 'active' && $filterDecision === 'all') {
    $where .= " AND (r.status NOT IN ('approved', 'rejected') OR r.status IS NULL)";
}

if ($filterSearch) {
    $where .= " AND (r.name LIKE ? OR r.project_title LIKE ? OR r.request_id LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}

if ($filterDecision === 'none') {
    $where .= " AND e.id IS NULL";
} elseif ($filterDecision !== 'all' && in_array($filterDecision, ['certified', 'certified_with_condition', 'rejected'])) {
    $where .= " AND e.result_decision = ?";
    $params[] = $filterDecision;
}

if ($filterYear) {
    $where .= " AND (YEAR(r.created_at) + 543) = ?";
    $params[] = $filterYear;
}

// Count
$countSql = "SELECT COUNT(*) FROM requests r LEFT JOIN request_evaluations e ON r.id = e.request_id" . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Data
$sql = "SELECT r.*, e.id as eval_id, e.result_decision, e.eval_date, e.evaluator_name, e.updated_at as eval_updated_at 
        FROM requests r 
        LEFT JOIN request_evaluations e ON r.id = e.request_id" . $where . " 
        ORDER BY r.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT (YEAR(created_at) + 543) as year FROM requests ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div>
        <h3 class="text-2xl font-bold dark:text-white flex items-center gap-2">
            <span>แบบตรวจรับรองงานวิจัย</span>
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
            ระบบประเมินคุณภาพงานวิจัยและการตรวจสอบความครบถ้วนของเอกสาร (พบ <?php echo number_format($totalRecords); ?> รายการ)
        </p>
    </div>
</div>

<!-- Filter Bar -->
<div class="p-6 mb-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div class="sm:col-span-2">
                <label for="search" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ค้นหานักวิจัย/โครงการ/รหัส</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($filterSearch); ?>"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="ชื่อ, โครงการ, รหัสคำขอ...">
            </div>
            <div>
                <label for="scope" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ขอบเขตรายการ</label>
                <select name="scope" id="scope"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="active" <?php echo $filterScope === 'active' ? 'selected' : ''; ?>>⚡ เฉพาะงานที่ต้องตรวจ</option>
                    <option value="all" <?php echo $filterScope === 'all' ? 'selected' : ''; ?>>📋 รวมที่ปิดงานแล้ว</option>
                </select>
            </div>
            <div>
                <label for="decision" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ผลการประเมิน</label>
                <select name="decision" id="decision"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="all" <?php echo $filterDecision === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <option value="none" <?php echo $filterDecision === 'none' ? 'selected' : ''; ?>>ยังไม่ตรวจประเมิน</option>
                    <option value="certified" <?php echo $filterDecision === 'certified' ? 'selected' : ''; ?>>รับรอง (ไม่มีแก้ไข)</option>
                    <option value="certified_with_condition" <?php echo $filterDecision === 'certified_with_condition' ? 'selected' : ''; ?>>รับรอง (ต้องแก้ไขก่อน)</option>
                    <option value="rejected" <?php echo $filterDecision === 'rejected' ? 'selected' : ''; ?>>ไม่รับรอง</option>
                </select>
            </div>
            <div>
                <label for="year" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ปีที่ส่งคำขอ</label>
                <select name="year" id="year"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $filterYear == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 flex-1">
                    กรอง
                </button>
                <a href="checklists.php"
                    class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-4 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">ล้าง</a>
            </div>
        </form>
    </div>

    <!-- Data Table wrapper -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <!-- Table Header Controls -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <span>แสดง</span>
                <select onchange="window.location.href = 'checklists.php?search=<?php echo urlencode($filterSearch); ?>&scope=<?php echo urlencode($filterScope); ?>&decision=<?php echo urlencode($filterDecision); ?>&year=<?php echo urlencode($filterYear); ?>&limit=' + this.value"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
            </select>
            <span>รายการต่อหน้า</span>
        </div>
    </div>

    <!-- The actual table -->
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">รหัสคำขอ</th>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">ผู้ขอรับรอง</th>
                    <th scope="col" class="px-6 py-3 min-w-[240px]">โครงการวิจัย</th>
                    <th scope="col" class="px-6 py-3 text-center whitespace-nowrap">สถานะคำขอ</th>
                    <th scope="col" class="px-6 py-3 text-center whitespace-nowrap">ผลการประเมิน</th>
                    <th scope="col" class="px-6 py-3 text-center whitespace-nowrap">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center text-gray-500 dark:text-gray-400 italic">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>ไม่พบข้อมูลที่ค้นหา</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-mcu-blue dark:text-blue-400">
                                    <a href="checklist_view.php?id=<?php echo $row['id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($row['request_id']); ?>
                                    </a>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo formatThaiDate($row['created_at']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 dark:text-white font-bold text-base whitespace-nowrap">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </div>
                                <div class="text-xs text-gray-500 whitespace-nowrap">
                                    <?php echo htmlspecialchars(($row['academic_position'] ? $row['academic_position'] . ' • ' : '') . $row['affiliation']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-md line-clamp-2 text-gray-700 dark:text-gray-300 font-medium" title="<?php echo htmlspecialchars($row['project_title']); ?>">
                                    <?php echo htmlspecialchars($row['project_title']); ?>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">ปีที่ส่งคำขอ (พ.ศ.) <?php echo date('Y', strtotime($row['created_at'])) + 543; ?> | ปีงบประมาณ <?php echo $row['fiscal_year']; ?></div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <?php
                                $st = $row['status'] ?? 'checking';
                                $statusInfo = $STATUS_OPTIONS[$st] ?? ['label' => $st, 'color' => 'gray'];
                                $colorClass = match ($statusInfo['color']) {
                                    'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                                    'green' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                                    'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
                                    'red' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                                    'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                };
                                ?>
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap inline-block <?php echo $colorClass; ?>">
                                    <?php echo htmlspecialchars($statusInfo['label']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <?php if (!empty($row['eval_id'])): ?>
                                    <?php if ($row['result_decision'] === 'certified'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5 me-1 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            รับรองแล้ว
                                        </span>
                                    <?php elseif ($row['result_decision'] === 'certified_with_condition'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5 me-1 text-amber-600 dark:text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                            รับรอง (ต้องแก้ไข)
                                        </span>
                                    <?php elseif ($row['result_decision'] === 'rejected'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5 me-1 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            ไม่รับรอง
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 me-1 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        ยังไม่ตรวจ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center justify-center space-x-1.5 flex-nowrap">
                                    <a href="checklist_view.php?id=<?php echo $row['id']; ?>"
                                        class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition-colors whitespace-nowrap"
                                        title="แบบตรวจรับรอง">
                                        <svg class="w-3.5 h-3.5 me-1 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6z" />
                                            <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        ตรวจรับรอง
                                    </a>
                                    <a href="checklist_print.php?id=<?php echo $row['id']; ?>" target="_blank"
                                        class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40 transition-colors whitespace-nowrap"
                                        title="พิมพ์แบบตรวจ / PDF">
                                        <svg class="w-3.5 h-3.5 me-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        พิมพ์
                                    </a>
                                    <a href="view.php?id=<?php echo $row['id']; ?>"
                                        class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors whitespace-nowrap"
                                        title="ดูคำขอ">
                                        <svg class="w-3.5 h-3.5 me-1 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        ดูข้อมูล
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex flex-col md:flex-row items-center justify-between gap-4">
            <span class="text-sm text-gray-700 dark:text-gray-400">
                แสดง <span class="font-semibold text-gray-900 dark:text-white"><?php echo $offset + 1; ?></span> ถึง
                <span class="font-semibold text-gray-900 dark:text-white"><?php echo min($offset + $limit, $totalRecords); ?></span> จาก
                <span class="font-semibold text-gray-900 dark:text-white"><?php echo $totalRecords; ?></span> รายการ
            </span>
            <ul class="inline-flex -space-x-px text-sm">
                <?php if ($page > 1): ?>
                    <li>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($filterSearch); ?>&decision=<?php echo urlencode($filterDecision); ?>&year=<?php echo urlencode($filterYear); ?>&limit=<?php echo $limit; ?>"
                            class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">ก่อนหน้า</a>
                    </li>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p == 1 || $p == $totalPages || abs($p - $page) <= 2): ?>
                        <li>
                            <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($filterSearch); ?>&decision=<?php echo urlencode($filterDecision); ?>&year=<?php echo urlencode($filterYear); ?>&limit=<?php echo $limit; ?>"
                                class="flex items-center justify-center px-3 h-8 leading-tight <?php echo $p == $page ? 'text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white font-bold' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php elseif (abs($p - $page) == 3): ?>
                        <li><span class="flex items-center justify-center px-3 h-8 text-gray-400 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($filterSearch); ?>&decision=<?php echo urlencode($filterDecision); ?>&year=<?php echo urlencode($filterYear); ?>&limit=<?php echo $limit; ?>"
                            class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">ถัดไป</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

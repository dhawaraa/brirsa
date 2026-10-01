<?php
/**
 * Admin Request List V2
 */
$pageTitle = "รายการยื่นขอรับรอง";
require_once '../includes/config.php';
$pdo = getDB();

// Access Control
check_role(['super_admin', 'data_admin', 'committee']);


// Filters
$filterStatus = $_GET['status'] ?? 'all';
$filterSearch = sanitize($_GET['search'] ?? '');
$filterYear = sanitize($_GET['year'] ?? '');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'bulk_delete' && !empty($_POST['selected_ids'])) {
        if (!is_role('super_admin') && !is_role('data_admin')) {
            die('ท่านไม่มีสิทธิ์ลบข้อมูล');
        }
        $ids = $_POST['selected_ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM requests WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        header("Location: requests.php?deleted=" . count($ids));
        exit;
    }
}

include 'includes/header.php';

// Pagination
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
if (!in_array($limit, [10, 15, 50, 100]))
    $limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base Conditions
$whereSql = " WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $whereSql .= " AND status = ?";
    $params[] = $filterStatus;
}
if ($filterSearch) {
    $whereSql .= " AND (name LIKE ? OR project_title LIKE ? OR request_id LIKE ?)";
    $searchTerm = "%$filterSearch%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if ($filterYear) {
    $whereSql .= " AND (YEAR(created_at) + 543) = ?";
    $params[] = $filterYear;
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM requests" . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Data Query
$sql = "SELECT * FROM requests" . $whereSql . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT (YEAR(created_at) + 543) as year FROM requests ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Build query string for filters to use in pagination links
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);
$exportUrl = "export_requests.php?" . $queryString;
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div>
        <h3 class="text-2xl font-bold dark:text-white">รายการยื่นขอรับรอง</h3>
        <p class="text-gray-500 dark:text-gray-400">จัดการข้อมูลและอัปเดตสถานะการขอรับรอง (พบ
            <?php echo $totalRecords; ?> รายการ)
        </p>
    </div>
    <div class="flex space-x-2">
        <a href="<?php echo $exportUrl; ?>" target="_blank"
            class="flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:bg-green-500 dark:hover:bg-green-600 transition-all">
            <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 15v2a3 3 0 003 3h10a3 3 0 003-3v-2M12 4v12l-4-4m4 4l4-4" />
            </svg>
            Export Excel
        </a>
        <?php if (is_role('super_admin') || is_role('data_admin')): ?>
            <button type="submit" form="bulkActionForm" name="action" value="bulk_delete" id="bulkDeleteBtn"
                class="hidden items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 transition-all"
                onclick="return confirm('ยืนยันการลบรายการที่เลือกทั้งหมด?');">
                <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12H6Z" />
                </svg>
                ลบรายการที่เลือก (<span id="selectedCount">0</span>)
            </button>
        <?php endif; ?>
    </div>
</div>
<?php if (isset($_GET['deleted'])): ?>
    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
        ลบข้อมูลเรียบร้อยแล้ว <?php echo intval($_GET['deleted']); ?> รายการ
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="p-6 mb-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="search"
                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ค้นหานักวิจัย/โครงการ</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($filterSearch); ?>"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="ชื่อ, โครงการ, รหัสคำขอ...">
        </div>
        <div>
            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">สถานะ</label>
            <select name="status" id="status"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="all">ทั้งหมด</option>
                <?php foreach ($STATUS_OPTIONS as $key => $opt): ?>
                    <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>>
                        <?php echo $opt['label']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="year" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ปีที่ส่งคำขอ</label>
            <select name="year" id="year"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">ทั้งหมด</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filterYear === $y ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 flex-1">
                กรองข้อมูล
            </button>
            <a href="requests.php"
                class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">ล้าง</a>
        </div>
    </form>
</div>

<!-- Data Table wrapper -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
    <!-- Table Header Controls -->
    <div
        class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
            <span>แสดง</span>
            <select onchange="window.location.href = 'requests.php?<?php echo $queryString; ?>&limit=' + this.value"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
            </select>
            <span>รายการต่อหน้า</span>
        </div>
    </div>

    <!-- The actual table -->
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-3 py-4 border-b dark:border-gray-700">
                        <div class="flex items-center">
                            <input id="checkbox-all" type="checkbox"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 font-bold border-b dark:border-gray-700">รหัส/วันที่</th>
                    <th scope="col" class="px-6 py-4 font-bold border-b dark:border-gray-700">รายละเอียดผู้ขอ</th>
                    <th scope="col" class="px-6 py-4 font-bold border-b dark:border-gray-700">โครงการ</th>
                    <th scope="col" class="px-6 py-4 font-bold border-b dark:border-gray-700">สถานะ</th>
                    <th scope="col" class="px-6 py-4 font-bold border-b dark:border-gray-700 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <form id="bulkActionForm" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="bulk_delete">
                    <?php foreach ($requests as $r):
                        $statusInfo = $STATUS_OPTIONS[$r['status']] ?? $STATUS_OPTIONS['checking'];
                        $colorClass = match ($statusInfo['color']) {
                            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                            'green' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                            'red' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        };
                        ?>
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-3 py-4">
                                <div class="flex items-center">
                                    <input name="selected_ids[]" value="<?php echo $r['id']; ?>" type="checkbox"
                                        class="request-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-mcu-blue dark:text-blue-400">
                                    <?php echo $r['request_id']; ?>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php echo formatThaiDate($r['created_at']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 dark:text-white font-bold text-base">
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($r['affiliation']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-gray-700 dark:text-gray-300"
                                    title="<?php echo htmlspecialchars($r['project_title']); ?>">
                                    <?php echo htmlspecialchars($r['project_title']); ?>
                                </div>
                                <div class="text-[10px] text-gray-400">ปีที่ส่งคำขอ (พ.ศ.)
                                    <?php echo date('Y', strtotime($r['created_at'])) + 543; ?> | ปีงบประมาณ
                                    <?php echo $r['fiscal_year']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full <?php echo $colorClass; ?>">
                                    <?php echo $statusInfo['label']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="view.php?id=<?php echo $r['id']; ?>"
                                         class="inline-flex items-center px-2 py-1.5 text-[11px] font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition-colors"
                                         title="ตรวจสอบ">
                                         <svg class="w-3.5 h-3.5 me-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                             fill="none" viewBox="0 0 24 24">
                                             <path stroke="currentColor" stroke-width="2"
                                                 d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6z" />
                                             <path stroke="currentColor" stroke-width="2"
                                                 d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                         </svg>
                                         ตรวจ
                                     </a>
                                     <?php if (is_role('super_admin') || is_role('data_admin')): ?>
                                         <a href="edit.php?id=<?php echo $r['id']; ?>"
                                             class="inline-flex items-center px-2 py-1.5 text-[11px] font-medium text-orange-600 bg-orange-50 rounded-lg hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/40 transition-colors"
                                             title="แก้ไข">
                                             <svg class="w-3.5 h-3.5 me-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                 fill="none" viewBox="0 0 24 24">
                                                 <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                     stroke-width="2"
                                                     d="m14.3 4.8 2.9 2.9M7 7H4a1 1 0 0 0-1 1v10c0 .6.4 1 1 1h11c.6 0 1-.4 1-1v-4.5m2.4-10a2 2 0 0 1 0 3l-6.8 6.8L8 14l.7-3.6 6.9-6.8a2 2 0 0 1 2.8 0Z" />
                                             </svg>
                                             แก้ไข
                                         </a>
                                     <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-gray-500 dark:text-gray-400 italic">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-4 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    ไม่พบข้อมูลที่ตรงตามเงื่อนไข
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </form>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('checkbox-all').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.request-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkDeleteButton();
    });

    document.querySelectorAll('.request-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
    });

    function updateBulkDeleteButton() {
        const selectedCount = document.querySelectorAll('.request-checkbox:checked').length;
        const btn = document.getElementById('bulkDeleteBtn');
        const countDisplay = document.getElementById('selectedCount');

        if (selectedCount > 0) {
            btn.classList.remove('hidden');
            btn.classList.add('inline-flex');
            countDisplay.textContent = selectedCount;
        } else {
            btn.classList.add('hidden');
            btn.classList.remove('inline-flex');
        }
    }
</script>
<!-- Pagination Footer -->
<?php if ($totalPages > 1): ?>
    <div
        class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 text-sm">
        <div class="text-gray-500 dark:text-gray-400">
            แสดงหน้า <span class="font-bold text-gray-900 dark:text-white"><?php echo $page; ?></span> จากทั้งหมด <span
                class="font-bold text-gray-900 dark:text-white"><?php echo $totalPages; ?></span> หน้า
        </div>
        <nav aria-label="Page navigation">
            <ul class="inline-flex -space-x-px rtl:space-x-reverse text-sm h-8">
                <?php if ($page > 1): ?>
                    <li>
                        <a href="requests.php?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>"
                            class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">ย้อนกลับ</a>
                    </li>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                    $activeClass = $i == $page ? "text-blue-600 border border-gray-300 bg-blue-50 dark:bg-gray-700 dark:text-white" : "leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white";
                    ?>
                    <li>
                        <a href="requests.php?<?php echo $queryString; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                            class="flex items-center justify-center px-3 h-8 <?php echo $activeClass; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li>
                        <a href="requests.php?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>"
                            class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">ถัดไป</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
<?php
/**
 * Admin: Send Project Info Email
 */
$pageTitle = "ส่งข้อมูลโครงการวิจัยทางอีเมล";
require_once '../includes/config.php';
$pdo = getDB();

// Filters
$filterStatus = sanitize($_GET['status'] ?? 'all');
$filterSearch = sanitize($_GET['search'] ?? '');
$filterYear = sanitize($_GET['year'] ?? '');

// Pagination
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
if (!in_array($limit, [5, 15, 50, 100]))
    $limit = 5;
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
    $whereSql .= " AND fiscal_year = ?";
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

$years = $pdo->query("SELECT DISTINCT fiscal_year FROM requests ORDER BY fiscal_year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Build query string for filters
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);

include 'includes/header.php';
?>

<div class="mb-6">
    <h3 class="text-2xl font-bold dark:text-white">ส่งข้อมูลโครงการวิจัยทางอีเมล</h3>
    <p class="text-gray-500 dark:text-gray-400">ค้นหาและส่งสรุปข้อมูลโครงการวิจัยไปยังอีเมลของนักวิจัย</p>
</div>

<!-- Search Bar -->
<div class="p-6 mb-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="search"
                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ค้นหานักวิจัย/โครงการ/รหัสคำขอ</label>
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
            <label for="year" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ปีงบประมาณ</label>
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
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 flex-1 transition-all">
                กรองข้อมูล
            </button>
            <a href="send_info.php"
                class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 transition-all">ล้าง</a>
        </div>
    </form>
</div>

<!-- Results Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
    <!-- Table Header Controls -->
    <div
        class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
            <span>แสดง</span>
            <select onchange="window.location.href = 'send_info.php?<?php echo $queryString; ?>&limit=' + this.value"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
            </select>
            <span>รายการต่อหน้า</span>
        </div>
        <div class="text-xs text-gray-500">
            พบทั้งหมด <span class="font-bold text-mcu-blue"><?php echo $totalRecords; ?></span> รายการ
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-4">รหัส / วันที่ส่ง</th>
                    <th scope="col" class="px-6 py-4">ข้อมูลนักวิจัย / โครงการ</th>
                    <th scope="col" class="px-6 py-4">อีเมล</th>
                    <th scope="col" class="px-6 py-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-gray-50 rounded-full mb-4">
                                    <i data-lucide="search-x" class="w-12 h-12 opacity-20"></i>
                                </div>
                                <span class="text-lg font-medium">ไม่พบข้อมูลที่ต้องการค้นหา</span>
                                <p class="text-sm">ลองเปลี่ยนคำค้นหา หรือป้อนข้อมูลให้ถูกต้อง</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-extrabold text-mcu-blue dark:text-blue-400">
                                    <?php echo $req['request_id']; ?>
                                </div>
                                <div class="text-[11px] text-gray-400 mt-1">ส่งเมื่อ:
                                    <?php echo formatThaiDate($req['created_at']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($req['name']); ?>
                                </div>
                                <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5"
                                    title="<?php echo htmlspecialchars($req['project_title']); ?>">
                                    <?php echo htmlspecialchars($req['project_title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($req['email']); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="sendEmail('<?php echo $req['id']; ?>', this)"
                                    class="inline-flex items-center px-4 py-2.5 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 shadow-sm transition-all hover:scale-105">
                                    <i data-lucide="mail" class="w-4 h-4 me-2"></i>
                                    ส่งสรุปข้อมูลไปยังเมล
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

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
                            <a href="send_info.php?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>"
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
                            <a href="send_info.php?<?php echo $queryString; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                                class="flex items-center justify-center px-3 h-8 <?php echo $activeClass; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li>
                            <a href="send_info.php?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>"
                                class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">ถัดไป</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
    async function sendEmail(id, btn) {
        if (!confirm('ยืนยันระบบจะส่งสรุปข้อมูลโครงการวิจัยไปยังอีเมลนักวิจัยท่านนี้?\n\n* ข้อมูลที่จะส่งคือรายละเอียดโครงการและรหัสคำขอ')) return;

        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 me-2 animate-spin"></i> กำลังส่ง...';
        lucide.createIcons();

        try {
            const formData = new URLSearchParams();
            formData.append('id', id);
            formData.append('csrf_token', '<?php echo get_csrf_token(); ?>');

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 15000); // 15s timeout

            const response = await fetch('../api/admin_send_project_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const text = await response.text();
            let res;
            try {
                res = JSON.parse(text);
            } catch (e) {
                console.error('Server response:', text);
                throw new Error('เซิร์ฟเวอร์ตอบกลับผิดพลาด หรือการเชื่อมต่อขาดหาย (Server Error)');
            }

            if (res.success) {
                btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'opacity-50', 'cursor-not-allowed');
                btn.classList.add('bg-blue-600');
                btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 me-2"></i> ส่งเรียบร้อย';
                setTimeout(() => {
                    btn.classList.remove('bg-blue-600');
                    btn.classList.add('bg-green-600', 'hover:bg-green-700');
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                    if (window.lucide) lucide.createIcons();
                }, 3000);
            } else {
                alert('เกิดข้อผิดพลาด: ' + (res.message || 'ไม่ทราบสาเหตุ'));
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = originalContent;
            }
        } catch (e) {
            let msg = e.message;
            if (e.name === 'AbortError') msg = 'การเชื่อมต่อหมดเวลา (Timeout) กรุณาลองใหม่อีกครั้ง';
            alert('ไม่สามารถส่งเมลได้: ' + msg);
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = originalContent;
        }
        if (window.lucide) lucide.createIcons();
    }
</script>

<?php include 'includes/footer.php'; ?>
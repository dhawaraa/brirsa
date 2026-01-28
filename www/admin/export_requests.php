<?php
/**
 * Export Requests to Excel
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized");
}

require_once '../includes/config.php';
$pdo = getDB();

// Filters (Same logic as requests.php)
$filterStatus = $_GET['status'] ?? 'all';
$filterSearch = sanitize($_GET['search'] ?? '');
$filterYear = sanitize($_GET['year'] ?? '');

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

// Data Query (All matches, no limit)
$sql = "SELECT * FROM requests" . $whereSql . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Excel Headers
$filename = "requests_export_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// HTML Table for Excel with UTF-8 BOM
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Thai support
?>
<table border="1">
    <thead>
        <tr style="background-color: #f3f4f6;">
            <th>ลำดับ</th>
            <th>รหัสคำขอ</th>
            <th>วันที่ยื่น</th>
            <th>ปีงบประมาณ</th>
            <th>ชื่อผู้ขอ</th>
            <th>สังกัด</th>
            <th>ชื่อโครงการ</th>
            <th>สถานะ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $index => $r):
            $statusLabel = $STATUS_OPTIONS[$r['status']]['label'] ?? $r['status'];
            ?>
            <tr>
                <td>
                    <?php echo $index + 1; ?>
                </td>
                <td>
                    <?php echo $r['request_id']; ?>
                </td>
                <td>
                    <?php echo formatThaiDate($r['created_at']); ?>
                </td>
                <td>
                    <?php echo $r['fiscal_year']; ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($r['name']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($r['affiliation']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($r['project_title']); ?>
                </td>
                <td>
                    <?php echo $statusLabel; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
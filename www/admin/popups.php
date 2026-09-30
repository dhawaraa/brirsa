<?php
/**
 * Admin Popup Management - ระบบจัดการ Popup ประชาสัมพันธ์
 */
$pageTitle = "จัดการ Popup ประชาสัมพันธ์";
include 'includes/header.php';

// Check permissions
if (!is_role('super_admin') && !is_role('data_admin')) {
    header('Location: dashboard.php?error=permission');
    exit;
}

$pdo = getDB();

// Ensure popups table exists
try {
    $pdo->query("SELECT 1 FROM popups LIMIT 1");
} catch (Exception $e) {
    // Auto-create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS popups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title_th VARCHAR(255) NOT NULL,
        title_en VARCHAR(255) NULL,
        content_th TEXT NULL,
        content_en TEXT NULL,
        image_url VARCHAR(255) NULL,
        link_url VARCHAR(255) NULL,
        link_text_th VARCHAR(100) NULL,
        link_text_en VARCHAR(100) NULL,
        start_date DATE NULL,
        end_date DATE NULL,
        target_page VARCHAR(50) DEFAULT 'home',
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}

$success = '';
$error = '';

$uploadDir = __DIR__ . '/../uploads/popups/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add Popup
    if ($action === 'add_popup') {
        $titleTh = trim($_POST['title_th'] ?? '');
        $titleEn = '';
        $contentTh = '';
        $contentEn = '';
        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkTextTh = trim($_POST['link_text_th'] ?? '');
        $linkTextEn = '';
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $targetPage = $_POST['target_page'] ?? 'home';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Auto assign highest sort order
        $maxSortStmt = $pdo->query("SELECT MAX(sort_order) AS max_sort FROM popups");
        $maxSortRow = $maxSortStmt->fetch();
        $sortOrder = ($maxSortRow && $maxSortRow['max_sort'] !== null) ? ((int)$maxSortRow['max_sort'] + 1) : 0;

        $imageUrl = null;

        // Image upload handling
        if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['popup_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $newFilename = 'popup_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                    $imageUrl = 'uploads/popups/' . $newFilename;
                } else {
                    $error = 'ไม่สามารถอัปโหลดรูปภาพได้ กรุณาตรวจสอบสิทธิ์ของโฟลเดอร์ uploads/popups/';
                }
            } else {
                $error = 'ประเภทไฟล์รูปภาพไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP เท่านั้น)';
            }
        }

        if (!$error) {
            if (empty($titleTh)) {
                $error = 'กรุณาระบุหัวข้อประกาศ';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO popups (title_th, title_en, content_th, content_en, image_url, link_url, link_text_th, link_text_en, start_date, end_date, target_page, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titleTh, $titleEn, $contentTh, $contentEn, $imageUrl, $linkUrl, $linkTextTh, $linkTextEn, $startDate, $endDate, $targetPage, $isActive, $sortOrder]);
                    $success = 'สร้าง Popup ประชาสัมพันธ์ใหม่เรียบร้อยแล้ว';
                } catch (PDOException $e) {
                    $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                }
            }
        }
    }

    // 2. Edit Popup
    if ($action === 'edit_popup') {
        $popupId = (int)($_POST['popup_id'] ?? 0);
        $titleTh = trim($_POST['title_th'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkTextTh = trim($_POST['link_text_th'] ?? '');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $targetPage = $_POST['target_page'] ?? 'home';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($titleTh)) {
            $error = 'กรุณาระบุหัวข้อประกาศ';
        } else {
            try {
                // Fetch current popup
                $stmt = $pdo->prepare("SELECT * FROM popups WHERE id = ?");
                $stmt->execute([$popupId]);
                $currentPopup = $stmt->fetch();

                if (!$currentPopup) {
                    $error = 'ไม่พบข้อมูล Popup ที่ต้องการแก้ไข';
                } else {
                    $imageUrl = $currentPopup['image_url'];

                    // Check if user chose to remove existing image
                    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                        if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) {
                            @unlink(__DIR__ . '/../' . $imageUrl);
                        }
                        $imageUrl = null;
                    }

                    // Check new image upload
                    if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['popup_image'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                        if (in_array($ext, $allowedExts)) {
                            $newFilename = 'popup_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                                // Remove old file
                                if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) {
                                    @unlink(__DIR__ . '/../' . $imageUrl);
                                }
                                $imageUrl = 'uploads/popups/' . $newFilename;
                            } else {
                                $error = 'ไม่สามารถอัปโหลดรูปภาพใหม่ได้';
                            }
                        } else {
                            $error = 'ประเภทไฟล์รูปภาพไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP เท่านั้น)';
                        }
                    }

                    if (!$error) {
                        $stmt = $pdo->prepare("UPDATE popups SET title_th = ?, image_url = ?, link_url = ?, link_text_th = ?, start_date = ?, end_date = ?, target_page = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$titleTh, $imageUrl, $linkUrl, $linkTextTh, $startDate, $endDate, $targetPage, $isActive, $popupId]);
                        $success = 'อัปเดตข้อมูล Popup เรียบร้อยแล้ว';
                    }
                }
            } catch (PDOException $e) {
                $error = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
            }
        }
    }

    // 3. Toggle Status
    if ($action === 'toggle_status') {
        $popupId = (int)($_POST['popup_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0);

        try {
            $stmt = $pdo->prepare("UPDATE popups SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $popupId]);
            $success = 'ปรับเปลี่ยนสถานะการแสดงผลเรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $error = 'ไม่สามารถปรับสถานะได้: ' . $e->getMessage();
        }
    }

    // 4. Delete Popup
    if ($action === 'delete_popup') {
        $popupId = (int)($_POST['popup_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT image_url FROM popups WHERE id = ?");
            $stmt->execute([$popupId]);
            $item = $stmt->fetch();
            if ($item && $item['image_url']) {
                if (file_exists(__DIR__ . '/../' . $item['image_url'])) {
                    @unlink(__DIR__ . '/../' . $item['image_url']);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM popups WHERE id = ?");
            $stmt->execute([$popupId]);
            $success = 'ลบ Popup ประชาสัมพันธ์เรียบร้อยแล้ว';
        } catch (PDOException $e) {
            $error = 'ไม่สามารถลบข้อมูลได้: ' . $e->getMessage();
        }
    }

    // 5. Reorder Popups (AJAX Drag & Drop)
    if ($action === 'reorder_popups') {
        header('Content-Type: application/json');
        $order = $_POST['order'] ?? [];
        if (is_array($order) && !empty($order)) {
            try {
                $total = count($order);
                $stmt = $pdo->prepare("UPDATE popups SET sort_order = ? WHERE id = ?");
                foreach ($order as $index => $id) {
                    // Highest sort_order appears first (or top of list)
                    $newRank = $total - $index;
                    $stmt->execute([$newRank, (int)$id]);
                }
                echo json_encode(['status' => 'success', 'message' => 'บันทึกลำดับเรียบร้อยแล้ว']);
                exit;
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Invalid order data']);
    }
}

// Fetch all popups
$popups = $pdo->query("SELECT * FROM popups ORDER BY sort_order DESC, created_at DESC")->fetchAll();
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
function openAddModal() {
    var modal = document.getElementById('addPopupModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeAddModal() {
    var modal = document.getElementById('addPopupModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function openEditModal(data) {
    document.getElementById('edit_popup_id').value = data.id;
    document.getElementById('edit_title_th').value = data.title_th || '';
    document.getElementById('edit_link_url').value = data.link_url || '';
    document.getElementById('edit_link_text_th').value = data.link_text_th || '';
    document.getElementById('edit_start_date').value = data.start_date || '';
    document.getElementById('edit_end_date').value = data.end_date || '';
    document.getElementById('edit_target_page').value = data.target_page || 'home';
    document.getElementById('edit_is_active').checked = (data.is_active == 1);

    var previewBox = document.getElementById('edit_image_preview_box');
    var previewImg = document.getElementById('edit_image_preview');
    if (data.image_url) {
        previewImg.src = '../' + data.image_url;
        previewBox.classList.remove('hidden');
    } else {
        previewBox.classList.add('hidden');
        previewImg.src = '';
    }

    var editModal = document.getElementById('editPopupModal');
    if (editModal) {
        editModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeEditModal() {
    var editModal = document.getElementById('editPopupModal');
    if (editModal) {
        editModal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modals when clicking on background
window.addEventListener('click', function(event) {
    var addModal = document.getElementById('addPopupModal');
    var editModal = document.getElementById('editPopupModal');
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
});

// Close modals on ESC key
window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddModal();
        closeEditModal();
    }
});

// Initialize Sortable for Drag and Drop
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.getElementById('sortable-popup-list');
    if (tbody) {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-blue-50',
            onEnd: function() {
                var rows = tbody.querySelectorAll('tr[data-id]');
                var order = [];
                rows.forEach(function(row) {
                    order.push(row.getAttribute('data-id'));
                });

                // Send AJAX request to reorder
                var formData = new FormData();
                formData.append('action', 'reorder_popups');
                order.forEach(function(id) {
                    formData.append('order[]', id);
                });
                var csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
                formData.append('csrf_token', csrfToken);

                fetch('popups.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(res) {
                    if (res.status === 'success') {
                        // Update index badges
                        var indexCells = tbody.querySelectorAll('.row-index');
                        indexCells.forEach(function(cell, idx) {
                            cell.textContent = idx + 1;
                        });
                        showToast('บันทึกการจัดเรียงลำดับเรียบร้อยแล้ว');
                    } else {
                        alert('ไม่สามารถบันทึกลำดับได้: ' + res.message);
                    }
                })
                .catch(function(err) {
                    console.error(err);
                });
            }
        });
    }
});

function showToast(msg) {
    var toast = document.createElement('div');
    toast.className = 'fixed bottom-5 right-5 z-50 bg-gray-900 text-white text-sm px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2 transition-opacity duration-300';
    toast.innerHTML = '<svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> ' + msg;
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() { toast.remove(); }, 300);
    }, 2000);
}
</script>

<!-- Page Header -->
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            จัดการ Popup ประชาสัมพันธ์
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            สร้างและควบคุมหน้าต่างแจ้งเตือน/ป้ายประกาศข่าวสาร (สามารถคลิกลากไอคอน <span class="inline-block px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">☰</span> ขึ้น-ลง เพื่อเรียงลำดับได้ทันที)
        </p>
    </div>
    <div>
        <button type="button" onclick="openAddModal()"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-mcu-blue rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 shadow-sm transition cursor-pointer">
            <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            สร้าง Popup ใหม่
        </button>
    </div>
</div>

<!-- Alerts -->
<?php if ($success): ?>
    <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800 flex items-center" role="alert">
        <svg class="flex-shrink-0 inline w-5 h-5 me-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800 flex items-center" role="alert">
        <svg class="flex-shrink-0 inline w-5 h-5 me-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<!-- Popup List Table -->
<div class="bg-white dark:bg-gray-800 relative shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th scope="col" class="px-3 py-3 w-12 text-center">ย้าย</th>
                    <th scope="col" class="px-3 py-3 w-16 text-center">ลำดับ</th>
                    <th scope="col" class="px-4 py-3 w-28 text-center">รูปภาพ (1:1)</th>
                    <th scope="col" class="px-4 py-3">หัวข้อประกาศ / ลิงก์</th>
                    <th scope="col" class="px-4 py-3">หน้าที่แสดง & ช่วงเวลา</th>
                    <th scope="col" class="px-4 py-3 text-center">สถานะ</th>
                    <th scope="col" class="px-4 py-3 text-center w-32">จัดการ</th>
                </tr>
            </thead>
            <tbody id="sortable-popup-list" class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($popups)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <p class="text-base font-semibold">ยังไม่มีรายการ Popup ในระบบ</p>
                                <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "สร้าง Popup ใหม่" ด้านบนเพื่อเริ่มสร้างประกาศ</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($popups as $index => $popup): ?>
                        <tr data-id="<?php echo $popup['id']; ?>" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-3 py-3 text-center">
                                <span class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 text-lg select-none px-2 py-1 inline-block" title="คลิกลากเพื่อจัดลำดับ">
                                    ☰
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center font-medium text-gray-900 dark:text-white">
                                <span class="row-index"><?php echo $index + 1; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($popup['image_url'] && file_exists(__DIR__ . '/../' . $popup['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($popup['image_url']); ?>" alt="Banner" class="w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm mx-auto aspect-square">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 text-xs mx-auto border border-dashed border-gray-300 dark:border-gray-600 aspect-square">
                                        ไม่มีรูป
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900 dark:text-white text-base">
                                    <?php echo htmlspecialchars($popup['title_th']); ?>
                                </div>

                                <?php if ($popup['link_url']): ?>
                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        <a href="<?php echo htmlspecialchars($popup['link_url']); ?>" target="_blank" class="hover:underline font-medium">
                                            <?php echo htmlspecialchars($popup['link_text_th'] ?: 'ดูรายละเอียด'); ?>
                                            <span class="text-gray-400 text-[11px] font-normal">(<?php echo htmlspecialchars($popup['link_url']); ?>)</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div class="mb-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium <?php echo $popup['target_page'] === 'home' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300'; ?>">
                                        <?php echo $popup['target_page'] === 'home' ? 'เฉพาะหน้าแรก' : 'ทุกหน้าเว็บไซต์'; ?>
                                    </span>
                                </div>
                                <div class="text-gray-500 dark:text-gray-400 space-y-0.5 text-[11px]">
                                    <div>
                                        เริ่ม: <?php echo $popup['start_date'] ? date('d/m/Y', strtotime($popup['start_date'])) : 'ทันที'; ?>
                                    </div>
                                    <div>
                                        สิ้นสุด: <?php echo $popup['end_date'] ? date('d/m/Y', strtotime($popup['end_date'])) : 'ไม่มีกำหนด'; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" class="inline">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $popup['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $popup['is_active'] ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400'; ?>" title="คลิกเพื่อสลับสถานะ">
                                        <span class="w-2 h-2 rounded-full <?php echo $popup['is_active'] ? 'bg-green-500' : 'bg-gray-400'; ?>"></span>
                                        <?php echo $popup['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($popup), ENT_QUOTES, 'UTF-8'); ?>)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg dark:text-blue-400 dark:hover:bg-gray-700 transition" title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Popup นี้?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_popup">
                                        <input type="hidden" name="popup_id" value="<?php echo $popup['id']; ?>">
                                        <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg dark:text-red-400 dark:hover:bg-gray-700 transition" title="ลบ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== MODAL: ADD POPUP ==================== -->
<div id="addPopupModal" tabindex="-1" style="display: none;" class="fixed inset-0 z-[9999] w-full h-full p-4 md:p-6 overflow-x-hidden overflow-y-auto bg-black/60 backdrop-blur-sm items-center justify-center">
    <div class="relative w-full max-w-3xl max-h-full my-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Modal header -->
            <div class="flex items-center justify-between px-6 py-4 border-b rounded-t dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-mcu-blue dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    สร้าง Popup ประชาสัมพันธ์ใหม่
                </h3>
                <button type="button" onclick="closeAddModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 14 14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add_popup">

                <!-- 1. Header Title -->
                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">หัวข้อประกาศ <span class="text-red-500">*</span></label>
                    <input type="text" name="title_th" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="เช่น ขอเชิญชวนส่งข้อเสนอโครงการวิจัย">
                </div>

                <!-- 2. Banner Image Upload (Focused 1:1) -->
                <div class="bg-blue-50/50 dark:bg-gray-750 p-5 rounded-2xl border-2 border-dashed border-blue-200 dark:border-gray-600">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <label class="text-sm font-bold text-gray-900 dark:text-white">รูปภาพประกาศ (Banner Image)</label>
                    </div>
                    <input type="file" name="popup_image" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-xs text-blue-800 dark:text-blue-300 mt-2 font-medium">
                        ✨ <strong>แนะนำขนาด:</strong> อัตราส่วน <strong>1:1 (สี่เหลี่ยมจัตุรัส เช่น 800x800 px)</strong> หรือรองรับไฟล์ JPG, PNG, GIF, WEBP
                    </p>
                </div>

                <!-- 3. Link Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">ลิงก์ปุ่มกด (Action URL เมื่อคลิก)</label>
                        <input type="url" name="link_url" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://example.com (เว้นว่างได้)">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">ข้อความบนปุ่ม</label>
                        <input type="text" name="link_text_th" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="เช่น ดูรายละเอียดเพิ่มเติม">
                    </div>
                </div>

                <!-- 4. Display Schedule & Targeting -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">วันที่เริ่มต้นแสดง</label>
                        <input type="date" name="start_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">วันที่สิ้นสุดแสดง</label>
                        <input type="date" name="end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">หน้าที่แสดงผล</label>
                        <select name="target_page" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="home">เฉพาะหน้าแรก (Home)</option>
                            <option value="all">ทุกหน้าเว็บไซต์ (All Pages)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input id="add_is_active" type="checkbox" name="is_active" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="add_is_active" class="ms-2.5 text-sm font-semibold text-gray-900 dark:text-gray-300">เปิดใช้งานทันที (Active)</label>
                </div>

                <!-- Modal footer -->
                <div class="flex items-center justify-end space-x-3 pt-5 border-t border-gray-200 rounded-b dark:border-gray-700">
                    <button type="button" onclick="closeAddModal()" class="text-gray-600 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-xl border border-gray-300 text-sm font-medium px-6 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600">ยกเลิก</button>
                    <button type="submit" class="text-white bg-mcu-blue hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-xl text-sm px-6 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 shadow-md">บันทึก Popup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL: EDIT POPUP ==================== -->
<div id="editPopupModal" tabindex="-1" style="display: none;" class="fixed inset-0 z-[9999] w-full h-full p-4 md:p-6 overflow-x-hidden overflow-y-auto bg-black/60 backdrop-blur-sm items-center justify-center">
    <div class="relative w-full max-w-3xl max-h-full my-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-2xl shadow-2xl dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Modal header -->
            <div class="flex items-center justify-between px-6 py-4 border-b rounded-t dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    แก้ไข Popup ประชาสัมพันธ์
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" onclick="closeEditModal()">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 14 14">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="edit_popup">
                <input type="hidden" name="popup_id" id="edit_popup_id">

                <!-- 1. Header Title -->
                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">หัวข้อประกาศ <span class="text-red-500">*</span></label>
                    <input type="text" name="title_th" id="edit_title_th" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- 2. Banner Image Upload (Focused 1:1) -->
                <div class="bg-blue-50/50 dark:bg-gray-750 p-5 rounded-2xl border-2 border-dashed border-blue-200 dark:border-gray-600">
                    <label class="block mb-2 text-sm font-bold text-gray-900 dark:text-white">รูปภาพประกาศ (Banner Image)</label>
                    <div id="edit_image_preview_box" class="mb-3 hidden">
                        <img id="edit_image_preview" src="" class="h-36 w-36 rounded-2xl border border-gray-300 object-cover shadow-sm aspect-square">
                        <label class="inline-flex items-center mt-2 text-xs font-medium text-red-600 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" class="w-4 h-4 mr-1.5 rounded text-red-600"> ลบรูปภาพปัจจุบันนี้
                        </label>
                    </div>
                    <input type="file" name="popup_image" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-xs text-blue-800 dark:text-blue-300 mt-2 font-medium">
                        ✨ <strong>แนะนำขนาด:</strong> อัตราส่วน <strong>1:1 (สี่เหลี่ยมจัตุรัส เช่น 800x800 px)</strong> หรือรองรับไฟล์ JPG, PNG, GIF, WEBP
                    </p>
                </div>

                <!-- 3. Link Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">ลิงก์ปุ่มกด (Action URL เมื่อคลิก)</label>
                        <input type="url" name="link_url" id="edit_link_url" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">ข้อความบนปุ่ม</label>
                        <input type="text" name="link_text_th" id="edit_link_text_th" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <!-- 4. Display Schedule & Targeting -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">วันที่เริ่มต้นแสดง</label>
                        <input type="date" name="start_date" id="edit_start_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">วันที่สิ้นสุดแสดง</label>
                        <input type="date" name="end_date" id="edit_end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold text-gray-900 dark:text-white">หน้าที่แสดงผล</label>
                        <select name="target_page" id="edit_target_page" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="home">เฉพาะหน้าแรก (Home)</option>
                            <option value="all">ทุกหน้าเว็บไซต์ (All Pages)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input id="edit_is_active" type="checkbox" name="is_active" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="edit_is_active" class="ms-2.5 text-sm font-semibold text-gray-900 dark:text-gray-300">เปิดใช้งาน (Active)</label>
                </div>

                <!-- Modal footer -->
                <div class="flex items-center justify-end space-x-3 pt-5 border-t border-gray-200 rounded-b dark:border-gray-700">
                    <button type="button" onclick="closeEditModal()" class="text-gray-600 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-xl border border-gray-300 text-sm font-medium px-6 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600">ยกเลิก</button>
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-xl text-sm px-6 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 shadow-md">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

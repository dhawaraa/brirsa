<?php
/**
 * Admin User Management V3
 */
$pageTitle = "จัดการผู้ใช้งาน";
include 'includes/header.php';

$pdo = getDB();

// Handle Actions
$success = '';
$error = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = sanitize($_POST['name'] ?? '');

    if ($username && $password && $name) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $name]);
            $success = "เพิ่มผู้ใช้งานสำเร็จ";
        } catch (PDOException $e) {
            $error = "ไม่สามารถเพิ่มผู้ใช้งานได้ (อาจมีชื่อผู้ใช้งานนี้แล้ว)";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_admin') {
    $id = (int) $_POST['admin_id'];
    $username = sanitize($_POST['username'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($id && $username && $name) {
        try {
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET username = ?, name = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $name, $hashedPassword, $id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE admins SET username = ?, name = ? WHERE id = ?");
                $stmt->execute([$username, $name, $id]);
            }
            $success = "อัปเดตข้อมูลผู้ใช้งานสำเร็จ";
        } catch (PDOException $e) {
            $error = "ไม่สามารถอัปเดตข้อมูลได้ (อาจมีชื่อผู้ใช้งานนี้แล้ว)";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_admin') {
    $id = (int) $_POST['admin_id'];

    // Check if trying to delete self
    if ($id == $_SESSION['admin_id']) {
        $error = "ไม่สามารถลบบัญชีของตัวเองได้";
    } else {
        // Check if this is the last admin
        $countStmt = $pdo->query("SELECT COUNT(*) FROM admins");
        $adminCount = $countStmt->fetchColumn();

        if ($adminCount <= 1) {
            $error = "ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                $success = "ลบผู้ใช้งานสำเร็จ";
            } catch (PDOException $e) {
                $error = "ไม่สามารถลบผู้ใช้งานได้";
            }
        }
    }
}

// Get all admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h3 class="text-2xl font-bold dark:text-white">จัดการผู้ใช้งาน</h3>
        <p class="text-gray-500 dark:text-gray-400">จัดการสิทธิ์และบัญชีผู้ดูแลระบบ</p>
    </div>
    <button data-modal-target="add-user-modal" data-modal-toggle="add-user-modal"
        class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
        type="button">
        เพิ่มผู้ใช้งาน
    </button>
</div>

<?php if ($success): ?>
    <div id="alert-3"
        class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
        role="alert">
        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
            viewBox="0 0 20 20">
            <path
                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 14a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3ZM11 11a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0v4Z" />
        </svg>
        <span class="sr-only">Info</span>
        <div class="ms-3 text-sm font-medium">
            <?php echo $success; ?>
        </div>
        <button type="button"
            class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
            data-dismiss-target="#alert-3" aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
            </svg>
        </button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div id="alert-2"
        class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
        role="alert">
        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
            viewBox="0 0 20 20">
            <path
                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 14a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3ZM11 11a1 1 0 0 1-2 0V7a1 1 0 0 1 2 0v4Z" />
        </svg>
        <span class="sr-only">Info</span>
        <div class="ms-3 text-sm font-medium">
            <?php echo $error; ?>
        </div>
        <button type="button"
            class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
            data-dismiss-target="#alert-2" aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
            </svg>
        </button>
    </div>
<?php endif; ?>

<!-- Admin Table -->
<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">ชื่อ-นามสกุล</th>
                <th scope="col" class="px-6 py-3">ชื่อผู้ใช้งาน</th>
                <th scope="col" class="px-6 py-3">วันที่เพิ่ม</th>
                <th scope="col" class="px-6 py-3 text-center">การจัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <?php echo htmlspecialchars($admin['name']); ?>
                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                            <span class="ml-2 text-xs text-blue-500 font-bold">(คุณ)</span>
                        <?php endif; ?>
                    </th>
                    <td class="px-6 py-4">
                        <?php echo htmlspecialchars($admin['username']); ?>
                    </td>
                    <td class="px-6 py-4 text-xs font-mono">
                        <?php echo $admin['created_at']; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)"
                                class="font-medium text-blue-600 dark:text-blue-500 hover:underline">แก้ไข</button>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <button
                                    onclick="openDeleteModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name']); ?>')"
                                    class="font-medium text-red-600 dark:text-red-500 hover:underline">ลบ</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div id="add-user-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    เพิ่มผู้ดูแลระบบใหม่
                </h3>
                <button type="button"
                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-hide="add-user-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <form class="space-y-4" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="add_admin">
                    <div>
                        <label for="name"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" id="name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            placeholder="ระบุชื่อจริง..." required />
                    </div>
                    <div>
                        <label for="username"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อผู้ใช้งาน
                            (Username)</label>
                        <input type="text" name="username" id="username"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            placeholder="admin_user" required />
                    </div>
                    <div>
                        <label for="password"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">รหัสผ่าน</label>
                        <input type="password" name="password" id="password" placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            required />
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">บันทึกข้อมูล</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    แก้ไขข้อมูลผู้ดูแลระบบ
                </h3>
                <button type="button"
                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-hide="edit-user-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <form class="space-y-4" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_admin">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    <div>
                        <label for="edit_name"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" id="edit_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            required />
                    </div>
                    <div>
                        <label for="edit_username"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" id="edit_username"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"
                            required />
                    </div>
                    <div>
                        <label for="edit_password"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">รหัสผ่านใหม่</label>
                        <input type="password" name="password" id="edit_password" placeholder="••••••••"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">บันทึกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-user-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    ยืนยันการลบผู้ใช้งาน
                </h3>
                <button type="button"
                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-hide="delete-user-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    <p class="mb-2">คุณต้องการลบผู้ใช้งาน <strong id="delete_user_name"
                            class="text-gray-900 dark:text-white"></strong> ใช่หรือไม่?</p>
                    <p class="text-red-600 dark:text-red-400 font-medium">⚠️ การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
                </div>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_admin">
                    <input type="hidden" name="admin_id" id="delete_admin_id">
                    <div class="flex gap-2">
                        <button type="button" data-modal-hide="delete-user-modal"
                            class="flex-1 text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">ยกเลิก</button>
                        <button type="submit"
                            class="flex-1 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900">ลบผู้ใช้งาน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditModal(admin) {
        document.getElementById('edit_admin_id').value = admin.id;
        document.getElementById('edit_name').value = admin.name;
        document.getElementById('edit_username').value = admin.username;
        document.getElementById('edit_password').value = '';

        // Use Flowbite's modal API
        const $modalElement = document.getElementById('edit-user-modal');
        const modal = new Modal($modalElement);
        modal.show();
    }

    function openDeleteModal(id, name) {
        document.getElementById('delete_admin_id').value = id;
        document.getElementById('delete_user_name').textContent = name;

        // Use Flowbite's modal API
        const $modalElement = document.getElementById('delete-user-modal');
        const modal = new Modal($modalElement);
        modal.show();
    }
</script>

<?php
include 'includes/footer.php';
?>
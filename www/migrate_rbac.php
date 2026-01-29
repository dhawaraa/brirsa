<?php
/**
 * RBAC Diagnostic & Repair Tool
 * Use this script to fix the 'Unknown column role' error and verify system state.
 */
require_once 'includes/config.php';
require_once 'includes/security.php';
secure_session_start();

echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><title>RBAC Repair Tool</title>";
echo "<style>body{font-family:sans-serif;line-height:1.6;padding:20px;background:#f4f4f9;} .card{background:#white;padding:20px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);max-width:800px;margin:auto;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;} pre{background:#eee;padding:10px;border-radius:4px;overflow-x:auto;}</style></head><body>";
echo "<div class='card'><h1>🛠️ RBAC Repair & Diagnostic Tool</h1>";

try {
    $pdo = getDB();
    echo "<p class='success'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ (Database Connected)</p>";

    // 1. Check table existence
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('admins', $tables)) {
        die("<p class='error'>❌ ไม่พบตาราง 'admins' ในฐานข้อมูลของคุณ! กรุณาตรวจสอบชื่อตาราง</p>");
    }
    echo "<p class='success'>✅ พบตาราง 'admins'</p>";

    // 2. Describe table
    echo "<h3>โครงสร้างตาราง 'admins' ปัจจุบัน:</h3><pre>";
    $stmt = $pdo->query("DESC admins");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasRole = false;
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'role') {
            $hasRole = true;
        }
    }
    echo "</pre>";

    // 3. Repair - Add role if missing
    if (!$hasRole) {
        echo "<p class='info'>⏳ กำลังเพิ่มคอลัมน์ 'role'...</p>";
        try {
            $pdo->exec("ALTER TABLE admins ADD COLUMN role VARCHAR(20) DEFAULT 'super_admin' AFTER name");
            echo "<p class='success'>✅ เพิ่มคอลัมน์ 'role' สำเร็จเรียบร้อยแล้ว!</p>";
            $hasRole = true;
        } catch (Exception $e) {
            echo "<p class='error'>❌ เกิดข้อผิดพลาดในการเพิ่มคอลัมน์: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='success'>✅ คอลัมน์ 'role' มีอยู่แล้วในระบบ</p>";
    }

    // 4. Force Update existing records
    if ($hasRole) {
        $affected = $pdo->exec("UPDATE admins SET role = 'super_admin' WHERE role IS NULL OR role = ''");
        echo "<p class='success'>✅ อัปเดตข้อมูลผู้ใช้งานเก่าเป็น 'super_admin' จำนวน $affected รายการ</p>";
    }

    // 5. Session Status
    echo "<h3>สถานะ Session ปัจจุบัน:</h3><pre>";
    echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'Not set') . "\n";
    echo "Current Role: " . ($_SESSION['admin_role'] ?? 'Not set') . "\n";
    echo "</pre>";

    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
        echo "<p class='error'>⚠️ คำเตือน: สิทธิ์ใน Session ยังไม่เป็น super_admin</p>";
        echo "<p><b>กรุณา <a href='admin/logout.php'>Logout</a> แล้ว Login ใหม่</b> เพื่อให้สิทธิ์ล่าสุดทำงานครับ</p>";
    } else {
        echo "<p class='success'>✅ สิทธิ์ของคุณถูกต้องแล้วครับ</p>";
    }

    echo "<hr><p style='text-align:center;'><a href='admin/users.php' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>กลับไปหน้าจัดการผู้ใช้งาน</a></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Critical Error: " . $e->getMessage() . "</p>";
}

echo "</div></body></html>";

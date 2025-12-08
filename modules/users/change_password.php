<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';

checkRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_POST['id_user'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($password)) {
        set_notification('error', '❌ Password tidak boleh kosong.');
        header('Location: index.php');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Gunakan prepared statement untuk mencegah SQL injection
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id_user=?");
    $stmt->bind_param("si", $hashed_password, $id_user);

    if ($stmt->execute()) {
        set_notification('success', '✅ Password berhasil diubah.');
    } else {
        set_notification('error', '❌ Gagal mengubah password.');
    }

    $stmt->close();
    header('Location: index.php');
    exit;
}

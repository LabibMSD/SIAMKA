<?php
session_start();
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';
checkRole(['admin']);

$id_user = $_GET['id_user'];

// Soft delete (tandai waktu penghapusan)
$now = date('Y-m-d H:i:s');
$query = "UPDATE users SET deleted_at='$now' WHERE id_user='$id_user'";

if (mysqli_query($conn, $query)) {
    set_notification('success', '🗑️ User berhasil dihapus (soft delete).');
} else {
    set_notification('error', '❌ Gagal menghapus user.');
}

header('Location: index.php');
exit;
?>

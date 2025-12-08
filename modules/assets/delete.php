<?php
session_start();
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';
checkRole(['admin']);

// Pastikan parameter id_aset ada
if (!isset($_GET['id_aset']) || !is_numeric($_GET['id_aset'])) {
    set_notification('error', '❌ ID aset tidak valid.');
    header('Location: index.php');
    exit;
}

$id_aset = intval($_GET['id_aset']);

// Soft delete (tandai waktu penghapusan)
$now = date('Y-m-d H:i:s');
$query = "UPDATE assets SET deleted_at = '$now' WHERE id_aset = '$id_aset'";

if (mysqli_query($conn, $query)) {
    set_notification('success', '🗑️ Aset berhasil dihapus (soft delete).');
} else {
    set_notification('error', '❌ Gagal menghapus aset.');
}

header('Location: index.php');
exit;
?>

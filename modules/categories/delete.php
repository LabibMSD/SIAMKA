<?php
session_start();
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';
checkRole(['admin']);

$id = $_GET['id'] ?? null;

if (!$id) {
    set_notification('error', '⚠️ ID kategori tidak ditemukan.');
    header('Location: index.php');
    exit;
}

// Cek apakah kategori masih digunakan oleh aset
$check = $conn->prepare("SELECT COUNT(*) AS total FROM assets WHERE id_kategori = ?");
$check->bind_param('i', $id);
$check->execute();
$total = $check->get_result()->fetch_assoc()['total'];

if ($total > 0) {
    set_notification('error', '❌ Kategori tidak dapat dihapus karena masih digunakan oleh aset.');
    header('Location: index.php');
    exit;
}

// Hapus kategori
$stmt = $conn->prepare("DELETE FROM categories WHERE id_kategori = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    set_notification('success', '🗑️ Kategori berhasil dihapus.');
} else {
    set_notification('error', '❌ Gagal menghapus kategori.');
}

header('Location: index.php');
exit;
?>

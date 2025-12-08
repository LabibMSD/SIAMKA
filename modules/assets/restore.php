<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
include '../../includes/notification_helper.php';
require_once '../../includes/auth_check.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Update deleted_at jadi NULL (restore)
    $restore = mysqli_query($conn, "UPDATE assets SET deleted_at=NULL WHERE id_aset=$id");

    if ($restore) {
        set_notification('succes', '🗑️ Assets berhasil dihapus (Permanen)');
    } else {
        set_notification('error', '❌ Gagal menghapus Aseets.');
    }
}

header("Location: trash.php");
exit;

if (mysqli_query($conn, $query)) {
    set_notification('success', '🗑️ User berhasil dihapus (soft delete).');
} else {
    set_notification('error', '❌ Gagal menghapus user.');
}

header('Location: restore.php');
exit;
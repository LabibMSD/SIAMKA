<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
include '../../includes/notification_helper.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $delete = mysqli_query($conn, "DELETE FROM assets WHERE id_aset=$id");

    if ($delete) {
            set_notification('success', '🗑️ Assets berhasil dihapus (Permanen).');
        } else {
            set_notification('error', '❌ Gagal menghapus Assets.');
    }
}

header("Location: trash.php");
exit;

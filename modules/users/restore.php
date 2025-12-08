<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

$id_user = $_GET['id_user'] ?? null;
if ($id_user) {
    $stmt = $conn->prepare("UPDATE users SET deleted_at = NULL WHERE id_user = ?");
    $stmt->bind_param('i', $id_user);
    $stmt->execute();
    set_notification('success', 'User berhasil direstore.');
}
header("Location: deleted_users.php");
exit;

<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

$id = $_GET['id'] ?? null;
if (!$id) {
  set_notification("error", "ID jadwal tidak ditemukan.");
  header("Location: schedule.php");
  exit;
}

$id_jadwal = intval($id);

// Hapus jadwal dari database
$delete = $conn->prepare("DELETE FROM maintenance_schedule WHERE id_jadwal = ?");
$delete->bind_param("i", $id_jadwal);

if ($delete->execute()) {
  set_notification("success", "✅ Jadwal maintenance berhasil dihapus.");
} else {
  set_notification("error", "❌ Gagal menghapus jadwal.");
}

header("Location: schedule.php");
exit;

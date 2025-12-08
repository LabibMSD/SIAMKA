<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

if (isset($_GET['id'])) {
    $id_jadwal = intval($_GET['id']);

    // Periksa apakah jadwal ada dan statusnya masih terjadwal
    $check = $conn->prepare("SELECT status FROM maintenance_schedule WHERE id_jadwal = ?");
    $check->bind_param("i", $id_jadwal);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        set_notification("error", "Jadwal tidak ditemukan.");
        header("Location: schedule.php");
        exit;
    }

    $jadwal = $result->fetch_assoc();
    if ($jadwal['status'] !== 'terjadwal') {
        set_notification("error", "Jadwal ini sudah tidak bisa dibatalkan (status: " . ucfirst($jadwal['status']) . ").");
        header("Location: schedule.php");
        exit;
    }

    // Update status menjadi dibatalkan
    $update = $conn->prepare("UPDATE maintenance_schedule SET status = 'dibatalkan' WHERE id_jadwal = ?");
    $update->bind_param("i", $id_jadwal);

    if ($update->execute()) {
        set_notification("success", "✅ Jadwal maintenance berhasil dibatalkan.");
    } else {
        set_notification("error", "❌ Terjadi kesalahan saat membatalkan jadwal.");
    }

    header("Location: schedule.php");
    exit;
} else {
    set_notification("error", "ID jadwal tidak ditemukan.");
    header("Location: schedule.php");
    exit;
}

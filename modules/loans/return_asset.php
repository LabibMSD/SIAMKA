<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id_peminjaman = $_POST['id_peminjaman'] ?? $_GET['id_peminjaman'] ?? null;
$id_user = $_SESSION['id_user'] ?? null;
$feedback = $_POST['feedback'] ?? '';

if ($id_peminjaman && $id_user) {
    // 1️⃣ Ambil id_aset dari tabel loans
    $stmt = $conn->prepare("SELECT id_aset FROM loans WHERE id_peminjaman = ? AND id_user = ?");
    $stmt->bind_param('ii', $id_peminjaman, $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();

    if ($loan) {
        $id_aset = $loan['id_aset'];

        // 2️⃣ Update status peminjaman menjadi returned dan simpan feedback
        $stmt = $conn->prepare("UPDATE loans SET status = 'returned', returned_at = NOW(), feedback = ? WHERE id_peminjaman = ? AND id_user = ?");
        $stmt->bind_param('sii', $feedback, $id_peminjaman, $id_user);
        $stmt->execute();

        // 3️⃣ Ubah status aset menjadi 'available'
        $stmt = $conn->prepare("UPDATE assets SET status = 'tersedia' WHERE id_aset = ?");
        $stmt->bind_param('i', $id_aset);
        $stmt->execute();

        set_notification('success', 'Aset berhasil dikembalikan dan status aset diperbarui.');
    } else {
        set_notification('error', 'Data peminjaman tidak ditemukan.');
    }
} else {
    set_notification('error', 'Parameter tidak valid.');
}

header("Location: my_loans.php");
exit;

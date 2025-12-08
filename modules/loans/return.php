<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// ✅ Validasi parameter ID
if (!isset($_GET['id_peminjaman'])) {
    set_notification('error', '❌ ID peminjaman tidak ditemukan.');
    header('Location: admin_loans.php');
    exit;
}

$id_peminjaman = intval($_GET['id_peminjaman']);

// 🔍 Ambil data pinjaman
$q = mysqli_query($conn, "SELECT id_aset, status FROM loans WHERE id_peminjaman = $id_peminjaman");
$loan = mysqli_fetch_assoc($q);

if (!$loan) {
    set_notification('error', '❌ Data peminjaman tidak ditemukan.');
    header('Location: admin_loans.php');
    exit;
}

if ($loan['status'] !== 'approved') {
    set_notification('warning', '⚠️ Hanya peminjaman dengan status disetujui yang bisa dikembalikan.');
    header('Location: admin_loans.php');
    exit;
}

$id_aset = $loan['id_aset'];

// 🔄 Update status loan & aset
mysqli_begin_transaction($conn);
try {
    $updateLoan = mysqli_query($conn, "
        UPDATE loans 
        SET status = 'returned', end_date = CURDATE()
        WHERE id_peminjaman = $id_peminjaman
    ");

    $updateAsset = mysqli_query($conn, "
        UPDATE assets 
        SET status = 'tersedia'
        WHERE id_aset = $id_aset
    ");

    if ($updateLoan && $updateAsset) {
        mysqli_commit($conn);
        set_notification('success', '✅ Aset berhasil ditandai sebagai dikembalikan.');
    } else {
        throw new Exception('Gagal memperbarui status pinjaman atau aset.');
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_notification('error', '❌ Terjadi kesalahan: ' . $e->getMessage());
}

header('Location: admin_loans.php');
exit;
?>

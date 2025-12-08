<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

/**
 * Fungsi untuk memeriksa role user
 * @param array $allowed_roles daftar role yang diizinkan
 */
function checkRole($allowed_roles = [])
{
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        echo "<div style='font-family: sans-serif; text-align:center; margin-top:100px;'>";
        echo "<h2 style='color:#c0392b;'>🚫 Akses Ditolak</h2>";
        echo "<p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>";
        echo "<a href='../dashboard/" . $_SESSION['role'] . ".php' style='color:#007bff;'>Kembali ke Dashboard</a><br>";
        echo "<a href='../auth/logout.php' style='color:#555;'>Keluar</a>";
        echo "</div>";
        exit;
    }
}
?>

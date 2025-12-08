<?php
if (!defined("SECURE")) {
    die("Direct access not allowed");
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notification_helper.php';

if (!function_exists('display_notification')) {
    function display_notification($type = 'info', $message = '')
    {
        if (empty($message)) return;
        echo "<div class='alert alert-{$type} mt-2'>{$message}</div>";
    }
}

$current_page = basename($_SERVER["PHP_SELF"]);
$user_role = $_SESSION["role"] ?? "user";

// Count pending loans
$notif_loans = 0;
$notif_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM loans WHERE status = 'Pending'");
if ($notif_query) {
    $notif_loans = mysqli_fetch_assoc($notif_query)['total'];
}

// Count pending damage reports
$notif_reports = 0;
$report_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM damage_reports WHERE status = 'baru'");
if ($report_query) {
    $notif_reports = mysqli_fetch_assoc($report_query)['total'];
}
?>

<!-- ================================================
     🔷 SIDEBAR - MODERN NAVIGATION
     ================================================ -->
<aside class="sidebar-main" role="navigation" aria-label="Sidebar navigation">

    <!-- SIDEBAR HEADER: USER PROFILE -->
    <div class="sidebar-header">
        <img src="<?= !empty($_SESSION['foto'])
                        ? BASE_URL . 'uploads/users/' . $_SESSION['foto']
                        : BASE_URL . 'assets/images/default-user.png'; ?>"
            alt="<?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>"
            class="sidebar-user-avatar">

        <h3 class="sidebar-user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></h3>
        <p class="sidebar-user-role"><?= strtoupper($_SESSION['role'] ?? 'Guest') ?></p>
    </div>

    <!-- SIDEBAR NAVIGATION -->
    <nav class="sidebar-nav">

        <!-- COMMON: Dashboard -->
        <div class="sidebar-nav-section">
            <a href="<?= BASE_URL ?>modules/dashboard/<?= $user_role ?>.php"
                class="nav-link <?= $current_page == $user_role . '.php' ? 'active' : '' ?>"
                title="Kembali ke dashboard">
                <i class="fa-solid fa-gauge"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>

        <!-- ADMIN MENU -->
        <?php if ($user_role == "admin"): ?>
            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Management</p>

                <a href="<?= BASE_URL ?>modules/users/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : '' ?>"
                    title="Kelola pengguna sistem">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-text">Kelola User</span>
                </a>

                <a href="<?= BASE_URL ?>modules/categories/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : '' ?>"
                    title="Kelola kategori aset">
                    <i class="fa-solid fa-tags"></i>
                    <span class="nav-text">Kategori</span>
                </a>

                <a href="<?= BASE_URL ?>modules/assets/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/assets/') !== false ? 'active' : '' ?>"
                    title="Kelola daftar aset">
                    <i class="fa-solid fa-box"></i>
                    <span class="nav-text">Kelola Aset</span>
                </a>
            </div>

            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Operations</p>

                <a href="<?= BASE_URL ?>modules/loans/admin_loans.php"
                    class="nav-link <?= $current_page == 'admin_loans.php' ? 'active' : '' ?>"
                    title="Kelola peminjaman aset">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="nav-text">Kelola Peminjaman</span>
                    <?php if ($notif_loans > 0): ?>
                        <span class="nav-badge"><?= $notif_loans ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>modules/damage/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/damage/') !== false ? 'active' : '' ?>"
                    title="Kelola kerusakan aset">
                    <i class="fa-solid fa-wrench"></i>
                    <span class="nav-text">Kelola Kerusakan</span>
                    <?php if ($notif_reports > 0): ?>
                        <span class="nav-badge"><?= $notif_reports ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>modules/maintenance/schedule.php"
                    class="nav-link <?= $current_page == 'schedule.php' ? 'active' : '' ?>"
                    title="Jadwal maintenance">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span class="nav-text">Maintenance</span>
                </a>
            </div>

            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Reports</p>

                <a href="<?= BASE_URL ?>modules/reports/loans_report.php"
                    class="nav-link <?= $current_page == 'loans_report.php' ? 'active' : '' ?>"
                    title="Laporan peminjaman">
                    <i class="fa-solid fa-file-lines"></i>
                    <span class="nav-text">Laporan Peminjaman</span>
                </a>

                <a href="<?= BASE_URL ?>modules/reports/summary.php"
                    class="nav-link <?= $current_page == 'summary.php' ? 'active' : '' ?>"
                    title="Ringkasan laporan">
                    <i class="fa-solid fa-chart-column"></i>
                    <span class="nav-text">Laporan</span>
                </a>
            </div>

            <!-- USER (PENGGUNA) MENU -->
        <?php elseif ($user_role == "pengguna"): ?>
            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Aset</p>

                <a href="<?= BASE_URL ?>modules/loans/available.php"
                    class="nav-link <?= $current_page == 'available.php' ? 'active' : '' ?>"
                    title="Lihat aset yang tersedia">
                    <i class="fa-solid fa-box-open"></i>
                    <span class="nav-text">Aset Tersedia</span>
                </a>

                <a href="<?= BASE_URL ?>modules/loans/my_loans.php"
                    class="nav-link <?= $current_page == 'my_loans.php' ? 'active' : '' ?>"
                    title="Peminjaman saya">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span class="nav-text">Peminjaman Saya</span>
                </a>
            </div>

            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Reports</p>

                <a href="<?= BASE_URL ?>modules/damage/report.php"
                    class="nav-link <?= $current_page == 'report.php' ? 'active' : '' ?>"
                    title="Buat laporan kerusakan">
                    <i class="fa-solid fa-tools"></i>
                    <span class="nav-text">Laporan Kerusakan</span>
                </a>

                <a href="<?= BASE_URL ?>modules/damage/my_reports.php"
                    class="nav-link <?= $current_page == 'my_reports.php' ? 'active' : '' ?>"
                    title="Riwayat laporan saya">
                    <i class="fa-solid fa-history"></i>
                    <span class="nav-text">Riwayat Laporan</span>
                </a>
            </div>

            <!-- MANAGEMENT (MANAJEMEN) MENU -->
        <?php elseif ($user_role == "manajemen"): ?>
            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Data</p>

                <a href="<?= BASE_URL ?>modules/assets/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/assets/') !== false ? 'active' : '' ?>"
                    title="Data aset">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span class="nav-text">Data Aset</span>
                </a>

                <a href="<?= BASE_URL ?>modules/loans/admin_loans.php"
                    class="nav-link <?= $current_page == 'admin_loans.php' ? 'active' : '' ?>"
                    title="Data peminjaman">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="nav-text">Data Peminjaman</span>
                    <?php if ($notif_loans > 0): ?>
                        <span class="nav-badge"><?= $notif_loans ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>modules/damage/index.php"
                    class="nav-link <?= $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/damage/') !== false ? 'active' : '' ?>"
                    title="Lihat laporan kerusakan">
                    <i class="fa-solid fa-wrench"></i>
                    <span class="nav-text">Data Kerusakan</span>
                    <?php if ($notif_reports > 0): ?>
                        <span class="nav-badge"><?= $notif_reports ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>modules/maintenance/schedule.php"
                    class="nav-link <?= $current_page == 'schedule.php' ? 'active' : '' ?>"
                    title="Jadwal maintenance">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span class="nav-text">Maintenance</span>
                </a>
            </div>

            <div class="sidebar-nav-section">
                <p class="sidebar-nav-section-title">Analytics</p>

                <a href="<?= BASE_URL ?>modules/reports/summary.php"
                    class="nav-link <?= $current_page == 'summary.php' ? 'active' : '' ?>"
                    title="Laporan dan statistik">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span class="nav-text">Laporan & Statistik</span>
                </a>

                <a href="<?= BASE_URL ?>modules/reports/loans_report.php"
                    class="nav-link <?= $current_page == 'loans_report.php' ? 'active' : '' ?>"
                    title="Laporan peminjaman">
                    <i class="fa-solid fa-file-lines"></i>
                    <span class="nav-text">Laporan Peminjaman</span>
                </a>
            </div>
        <?php endif; ?>

    </nav>

</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">

    <?php display_notification(); ?>
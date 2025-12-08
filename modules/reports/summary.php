<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/pdf_generator.php';

// 🔒 Hanya admin & manajemen
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manajemen') {
    echo "<div class='alert alert-danger m-4'>Anda tidak memiliki akses ke halaman ini.</div>";
    exit;
}

// 📄 Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']); // contoh: summary.php

// 📅 Filter tanggal
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$query = "
    SELECT 
        COUNT(DISTINCT a.id_aset) AS total_aset,
        COUNT(DISTINCT dr.id_laporan) AS total_kerusakan,
        SUM(CASE WHEN dr.status = 'selesai' THEN 1 ELSE 0 END) AS laporan_selesai,
        SUM(m.biaya) AS total_biaya
    FROM assets a
    LEFT JOIN damage_reports dr ON dr.id_aset = a.id_aset
    LEFT JOIN maintenance_history m ON m.id_aset = a.id_aset
    WHERE 1=1
";

if ($start_date && $end_date) {
    $query .= " AND DATE(dr.tanggal_lapor) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                AND '" . $conn->real_escape_string($end_date) . "'";
}

$result = $conn->query($query);
$data = $result->fetch_assoc();

// 📄 Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $report_title = "Laporan Ringkasan Sistem Aset";

    ob_start(); ?>
    <table class="report-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Aset</td>
                <td><?= $data['total_aset'] ?? 0 ?></td>
            </tr>
            <tr>
                <td>Total Laporan Kerusakan</td>
                <td><?= $data['total_kerusakan'] ?? 0 ?></td>
            </tr>
            <tr>
                <td>Laporan Selesai</td>
                <td><?= $data['laporan_selesai'] ?? 0 ?></td>
            </tr>
            <tr>
                <td>Total Biaya Maintenance</td>
                <td>Rp <?= number_format($data['total_biaya'] ?? 0, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
<?php
    $content_html = ob_get_clean();
    generate_pdf($content_html, 'laporan_ringkasan_' . date('Ymd_His') . '.pdf', $report_title);
    exit;
}

// ====================
// 🧱 Tampilan Dashboard - ALIGNED WITH DESIGN SYSTEM
// ====================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-chart-line me-2"></i>Laporan Ringkasan</h1>
                <p class="page-subtitle">Ringkasan data keseluruhan sistem manajemen aset.</p>
            </div>
        </div>

        <!-- 🔹 Navigasi antar laporan -->
        <div class="report-nav mb-3 text-center">
            <a href="summary.php"
                class="btn <?= $current_page === 'summary.php' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm me-2">
                <i class="fas fa-chart-pie"></i> Ringkasan
            </a>
            <a href="asset_usage.php"
                class="btn <?= $current_page === 'asset_usage.php' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm me-2">
                <i class="fas fa-cogs"></i> Pemakaian Aset
            </a>
            <a href="maintenance_cost.php"
                class="btn <?= $current_page === 'maintenance_cost.php' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                <i class="fas fa-tools"></i> Biaya Maintenance
            </a>
        </div>

        <!-- Filter Form -->
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <div class="d-flex gap-2">
                                <a href="?export=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"
                                    class="btn btn-danger flex-fill">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 🔹 Ringkasan Data -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg, 1rem);">
            <div class="card" style="text-align: center; padding: var(--spacing-lg, 1rem);">
                <h5 style="margin-bottom: var(--spacing-md, 0.75rem); color: var(--text-muted, #6b7280); font-weight: 500;">Total Aset</h5>
                <h2 style="font-weight: 700; color: var(--primary-blue, #0f5a92); font-size: var(--font-size-2xl, 1.5rem); margin: 0;"><?= $data['total_aset'] ?? 0 ?></h2>
            </div>
            <div class="card" style="text-align: center; padding: var(--spacing-lg, 1rem);">
                <h5 style="margin-bottom: var(--spacing-md, 0.75rem); color: var(--text-muted, #6b7280); font-weight: 500;">Total Laporan</h5>
                <h2 style="font-weight: 700; color: var(--accent-yellow, #ffc107); font-size: var(--font-size-2xl, 1.5rem); margin: 0;"><?= $data['total_kerusakan'] ?? 0 ?></h2>
            </div>
            <div class="card" style="text-align: center; padding: var(--spacing-lg, 1rem);">
                <h5 style="margin-bottom: var(--spacing-md, 0.75rem); color: var(--text-muted, #6b7280); font-weight: 500;">Laporan Selesai</h5>
                <h2 style="font-weight: 700; color: var(--success-green, #10b981); font-size: var(--font-size-2xl, 1.5rem); margin: 0;"><?= $data['laporan_selesai'] ?? 0 ?></h2>
            </div>
            <div class="card" style="text-align: center; padding: var(--spacing-lg, 1rem);">
                <h5 style="margin-bottom: var(--spacing-md, 0.75rem); color: var(--text-muted, #6b7280); font-weight: 500;">Total Biaya</h5>
                <h2 style="font-weight: 700; color: var(--danger-red, #ef4444); font-size: var(--font-size-2xl, 1.5rem); margin: 0;">Rp <?= number_format($data['total_biaya'] ?? 0, 0, ',', '.') ?></h2>
            </div>
        </div>
    </div>
</main>

<!-- 🔹 Style tambahan -->
<style>
    .report-nav .btn {
        border-radius: 20px;
        font-weight: 500;
        padding: 6px 14px;
        transition: 0.2s ease-in-out;
    }

    .report-nav .btn i {
        margin-right: 4px;
    }

    .report-nav .btn:hover {
        transform: translateY(-2px);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>
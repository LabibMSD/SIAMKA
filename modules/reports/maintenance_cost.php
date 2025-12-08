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
$current_page = basename($_SERVER['PHP_SELF']);

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 🧩 Filter tanggal & nama aset
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$nama_aset  = $_GET['nama_aset'] ?? '';

// 🧾 Ambil semua nama aset untuk dropdown
$aset_result = $conn->query("SELECT id_aset, nama_aset FROM assets WHERE deleted_at IS NULL ORDER BY nama_aset ASC");

// Count total records for pagination
$count_query = "
    SELECT COUNT(DISTINCT a.id_aset) AS total
    FROM maintenance_history m
    JOIN assets a ON m.id_aset = a.id_aset
    WHERE 1=1
";

if ($start_date && $end_date) {
    $count_query .= " AND DATE(m.tanggal_selesai) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                AND '" . $conn->real_escape_string($end_date) . "'";
}

if (!empty($nama_aset)) {
    $count_query .= " AND a.id_aset = '" . $conn->real_escape_string($nama_aset) . "'";
}

$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// 🔍 Query utama
$query = "
    SELECT 
        a.kode_aset,
        a.nama_aset,
        COUNT(m.id_history) AS jumlah_perbaikan,
        SUM(m.biaya) AS total_biaya,
        MAX(m.tanggal_perawatan) AS terakhir_diperbaiki
    FROM maintenance_history m
    JOIN assets a ON m.id_aset = a.id_aset
    WHERE 1=1
";

if ($start_date && $end_date) {
    $query .= " AND DATE(m.tanggal_selesai) BETWEEN '" . $conn->real_escape_string($start_date) . "' 
                AND '" . $conn->real_escape_string($end_date) . "'";
}

if (!empty($nama_aset)) {
    $query .= " AND a.id_aset = '" . $conn->real_escape_string($nama_aset) . "'";
}

$query .= " GROUP BY a.id_aset ORDER BY total_biaya DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// 📤 Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=maintenance_cost_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Kode Aset', 'Nama Aset', 'Jumlah Perbaikan', 'Total Biaya', 'Terakhir Diperbaiki']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['kode_aset'],
            $row['nama_aset'],
            $row['jumlah_perbaikan'],
            'Rp ' . number_format($row['total_biaya'] ?? 0, 0, ',', '.'),
            $row['terakhir_diperbaiki'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// 📄 Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $report_title = "Laporan Biaya Maintenance Aset";
    ob_start(); ?>
    <table class="report-table">
        <thead>
            <tr>
                <th>Kode Aset</th>
                <th>Nama Aset</th>
                <th>Jumlah Perbaikan</th>
                <th>Total Biaya (Rp)</th>
                <th>Terakhir Diperbaiki</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                    <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                    <td><?= $row['jumlah_perbaikan'] ?></td>
                    <td>Rp <?= number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['terakhir_diperbaiki'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php
    $content_html = ob_get_clean();
    generate_pdf($content_html, 'laporan_biaya_maintenance_' . date('Ymd_His') . '.pdf', $report_title);
    exit;
}

// ====================
// 🧱 Tampilan Dashboard
// ====================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>
<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-tools me-2"></i>Laporan Biaya Maintenance</h1>
                <p class="page-subtitle">Lihat dan ekspor laporan biaya maintenance aset berdasarkan filter yang dipilih.</p>
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
                    <div class="col-12 col-md-4">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Nama Aset</label>
                        <select name="nama_aset" class="form-select">
                            <option value="">-- Semua Aset --</option>
                            <?php while ($row = $aset_result->fetch_assoc()): ?>
                                <option value="<?= $row['id_aset'] ?>" <?= ($nama_aset == $row['id_aset']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nama_aset']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <div class="d-flex gap-2">
                                <a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&nama_aset=<?= urlencode($nama_aset) ?>" class="btn btn-success flex-fill">
                                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                                </a>
                                <a href="?export=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&nama_aset=<?= urlencode($nama_aset) ?>"
                                    class="btn btn-danger flex-fill">
                                    <i class="fa-solid fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Biaya Maintenance</h3>
            </div>
            <div class="card-body">
                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>Kode Aset</th>
                                <th>Nama Aset</th>
                                <th>Jumlah Perbaikan</th>
                                <th>Total Biaya</th>
                                <th>Terakhir Diperbaiki</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td class="text-center"><?= $row['jumlah_perbaikan'] ?></td>
                                        <td class="text-end">Rp <?= number_format($row['total_biaya'] ?? 0, 0, ',', '.') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row['terakhir_diperbaiki'] ?? '-') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Tidak ada data ditemukan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Previous button
                        if ($page > 1) {
                            $prev_page = $page - 1;
                            $prev_url = "?page=$prev_page&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&nama_aset=" . urlencode($nama_aset);
                            echo "<a href='$prev_url' class='prev'>« Previous</a>";
                        }

                        // Page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            $first_url = "?page=1&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&nama_aset=" . urlencode($nama_aset);
                            echo "<a href='$first_url'>1</a>";
                            if ($start_page > 2) echo "<span class='dots'>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $page_url = "?page=$i&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&nama_aset=" . urlencode($nama_aset);
                            $active_class = ($i == $page) ? 'active' : '';
                            echo "<a href='$page_url' class='$active_class'>$i</a>";
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
                            $last_url = "?page=$total_pages&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&nama_aset=" . urlencode($nama_aset);
                            echo "<a href='$last_url'>$total_pages</a>";
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $next_page = $page + 1;
                            $next_url = "?page=$next_page&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&nama_aset=" . urlencode($nama_aset);
                            echo "<a href='$next_url' class='next'>Next »</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
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

    /* Responsive button layout */
    @media (max-width: 767.98px) {
        .d-flex.flex-column.flex-md-row .btn {
            width: 100% !important;
            margin-bottom: 0.5rem;
        }

        .d-flex.flex-column.flex-md-row .btn:last-child {
            margin-bottom: 0;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>
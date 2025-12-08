<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';

// 🔒 Hanya admin & manajemen
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manajemen') {
    echo "<div class='alert alert-danger m-4'>Anda tidak memiliki akses ke halaman ini.</div>";
    exit;
}

// 📅 Filter data
$status_filter = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT 
            l.id_peminjaman,
            u.nama AS nama_user,
            a.nama_aset,
            a.kode_aset,
            c.nama_kategori,
            l.start_date,
            l.end_date,
            l.status
        FROM loans l
        LEFT JOIN users u ON l.id_user = u.id_user
        LEFT JOIN assets a ON l.id_aset = a.id_aset
        LEFT JOIN categories c ON a.id_kategori = c.id_kategori
        WHERE 1=1";

if ($status_filter) {
    $sql .= " AND l.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($start_date && $end_date) {
    $sql .= " AND l.start_date BETWEEN '" . $conn->real_escape_string($start_date) . "' 
              AND '" . $conn->real_escape_string($end_date) . "'";
}
$sql .= " ORDER BY l.start_date DESC";
$result = $conn->query($sql);

// 📤 Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=loan_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nama User', 'Nama Aset', 'Kategori', 'Tanggal Mulai', 'Tanggal Selesai', 'Status']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id_peminjaman'],
            $row['nama_user'],
            $row['nama_aset'],
            $row['nama_kategori'],
            $row['start_date'],
            $row['end_date'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// 📄 Export PDF (pakai template SIAMKA)
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $report_title = "Laporan Peminjaman Aset";

    require_once '../../includes/pdf_generator.php';

    // 🔹 Bangun konten tabel HTML
    ob_start();
?>
    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama User</th>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_peminjaman']) ?></td>
                        <td><?= htmlspecialchars($row['nama_user']) ?></td>
                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['start_date']) ?></td>
                        <td><?= htmlspecialchars($row['end_date'] ?? '-') ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data peminjaman</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php
    $content_html = ob_get_clean();

    // 🔹 Panggil fungsi generate_pdf dari template
    generate_pdf($content_html, 'laporan_peminjaman_' . date('Ymd_His') . '.pdf', $report_title);
    exit;
}

// ==========================
// 🧱 TAMPILAN WEB DASHBOARD
// ==========================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-file-lines me-2"></i>Laporan Peminjaman</h1>
                <p class="page-subtitle">Lihat dan ekspor laporan peminjaman aset berdasarkan filter yang dipilih.</p>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card card-shadow mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="returned" <?= $status_filter == 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <div class="d-flex gap-2">
                                <a href="?export=csv&status=<?= urlencode($status_filter) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success flex-fill">
                                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                                </a>
                                <a href="?export=pdf&status=<?= urlencode($status_filter) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-danger flex-fill">
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
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Peminjaman</h3>
            </div>
            <div class="card-body">
                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>ID</th>
                                <th>Nama User</th>
                                <th>Nama Aset</th>
                                <th>Kategori</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_peminjaman']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['start_date']) ?></td>
                                        <td><?= htmlspecialchars($row['end_date'] ?? '-') ?></td>
                                        <td>
                                            <?php
                                            $status = $row['status'];
                                            $badge_class = [
                                                'pending' => 'warning',
                                                'approved' => 'primary',
                                                'rejected' => 'danger',
                                                'returned' => 'success'
                                            ][$status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($status) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada data peminjaman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 🔹 Style tambahan -->
<style>
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
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

// 🧩 Filter
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$status     = $_GET['status'] ?? '';
$user_id    = $_GET['user_id'] ?? '';

// 🔍 Ambil daftar user untuk filter
$users = $conn->query("SELECT id_user, nama FROM users ORDER BY nama ASC");

// 🧾 Query utama
$query = "
    SELECT 
        a.id_aset,
        a.nama_aset,
        a.kode_aset,
        u.nama AS pengguna,
        l.start_date,
        l.end_date,
        l.status
    FROM loans l
    JOIN assets a ON l.id_aset = a.id_aset
    LEFT JOIN users u ON l.id_user = u.id_user
    WHERE 1=1
";

if ($start_date && $end_date) {
    $query .= " AND DATE(l.start_date) BETWEEN '" . $conn->real_escape_string($start_date) . "'
                AND '" . $conn->real_escape_string($end_date) . "'";
}
if ($status) {
    $query .= " AND l.status = '" . $conn->real_escape_string($status) . "'";
}
if ($user_id) {
    $query .= " AND l.id_user = '" . $conn->real_escape_string($user_id) . "'";
}

$query .= " ORDER BY l.start_date DESC";
$result = $conn->query($query);

// 📤 Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=asset_usage_report_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Kode', 'Nama Aset', 'Pengguna', 'Tanggal Pinjam', 'Tanggal Kembali', 'Status']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['kode_aset'],
            $row['nama_aset'],
            $row['pengguna'] ?? '-',
            $row['start_date'],
            $row['end_date'] ?? '-',
            ucfirst($row['status'])
        ]);
    }
    fclose($output);
    exit;
}

// 📄 Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $report_title = "Laporan Pemakaian Aset";
    ob_start(); ?>
    <table class="report-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Aset</th>
                <th>Pengguna</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                    <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                    <td><?= htmlspecialchars($row['pengguna']) ?></td>
                    <td><?= htmlspecialchars($row['start_date']) ?></td>
                    <td><?= htmlspecialchars($row['end_date'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php
    $content_html = ob_get_clean();
    generate_pdf($content_html, 'laporan_pemakaian_aset_' . date('Ymd_His') . '.pdf', $report_title);
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
                <h1 class="page-title"><i class="fa-solid fa-cogs me-2"></i>Laporan Pemakaian Aset</h1>
                <p class="page-subtitle">Lihat dan ekspor laporan pemakaian aset berdasarkan filter yang dipilih.</p>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="returned" <?= $status == 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">User</label>
                        <select name="user_id" class="form-select">
                            <option value="">Semua</option>
                            <?php while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id_user'] ?>" <?= $user_id == $u['id_user'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nama']) ?>
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
                                <a href="?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&status=<?= urlencode($status) ?>&user_id=<?= urlencode($user_id) ?>" class="btn btn-success flex-fill">
                                    <i class="fa-solid fa-file-csv me-1"></i> CSV
                                </a>
                                <a href="?export=pdf&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&status=<?= urlencode($status) ?>&user_id=<?= urlencode($user_id) ?>"
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
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Pemakaian Aset</h3>
            </div>
            <div class="card-body">
                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>Kode</th>
                                <th>Nama Aset</th>
                                <th>Pengguna</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td><?= htmlspecialchars($row['pengguna'] ?? '-') ?></td>
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
                                    <td colspan="6" class="text-center text-muted">Tidak ada data ditemukan</td>
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
                            $prev_url = "?page=$prev_page&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&status=" . urlencode($status) . "&user_id=" . urlencode($user_id);
                            echo "<a href='$prev_url' class='prev'>« Previous</a>";
                        }

                        // Page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            $first_url = "?page=1&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&status=" . urlencode($status) . "&user_id=" . urlencode($user_id);
                            echo "<a href='$first_url'>1</a>";
                            if ($start_page > 2) echo "<span class='dots'>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $page_url = "?page=$i&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&status=" . urlencode($status) . "&user_id=" . urlencode($user_id);
                            $active_class = ($i == $page) ? 'active' : '';
                            echo "<a href='$page_url' class='$active_class'>$i</a>";
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
                            $last_url = "?page=$total_pages&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&status=" . urlencode($status) . "&user_id=" . urlencode($user_id);
                            echo "<a href='$last_url'>$total_pages</a>";
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $next_page = $page + 1;
                            $next_url = "?page=$next_page&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&status=" . urlencode($status) . "&user_id=" . urlencode($user_id);
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
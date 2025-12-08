<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 🔍 Ambil filter dari query string
$tanggal_filter = $_GET['tanggal'] ?? '';
$aset_filter = $_GET['aset'] ?? '';

// 🔧 Query dasar
$query = "
    SELECT 
        mh.*, 
        a.nama_aset
    FROM maintenance_history mh
    LEFT JOIN assets a ON mh.id_aset = a.id_aset
    WHERE 1=1
";

$params = [];
$types = '';

if ($tanggal_filter !== '') {
    $query .= " AND DATE(mh.tanggal_perawatan) = ? ";
    $params[] = $tanggal_filter;
    $types .= 's';
}

if ($aset_filter !== '') {
    $query .= " AND a.nama_aset LIKE ? ";
    $params[] = "%$aset_filter%";
    $types .= 's';
}

$query .= " ORDER BY mh.tanggal_perawatan DESC";

// Count total records for pagination
$count_query = "
    SELECT COUNT(*) AS total
    FROM maintenance_history mh
    LEFT JOIN assets a ON mh.id_aset = a.id_aset
    WHERE 1=1
";

$count_params = [];
$count_types = '';

if ($tanggal_filter !== '') {
    $count_query .= " AND DATE(mh.tanggal_perawatan) = ? ";
    $count_params[] = $tanggal_filter;
    $count_types .= 's';
}

if ($aset_filter !== '') {
    $count_query .= " AND a.nama_aset LIKE ? ";
    $count_params[] = "%$aset_filter%";
    $count_types .= 's';
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Add LIMIT and OFFSET to main query
$query .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-screwdriver-wrench"></i> Riwayat Maintenance</h1>
                <p class="page-subtitle">Lihat semua catatan pemeliharaan aset</p>
            </div>
        </div>

        <!-- FILTER CARD -->
        <div class="card card-shadow">
            <div class="card-body">
                <form method="get" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="aset" value="<?= htmlspecialchars($aset_filter) ?>" class="form-control" placeholder="Cari nama aset...">
                    </div>
                    <div class="filter-group">
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" class="form-control">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <a href="history.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- DATA CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-history"></i> Daftar Riwayat</h3>
                <div class="card-actions">
                    <a href="schedule.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card-body">
                <?php display_notification(); ?>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Aset</th>
                                <th>Tanggal Perawatan</th>
                                <th>Teknisi</th>
                                <th>Biaya</th>
                                <th>Kondisi Setelah</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $no = 1;
                                while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $status = strtolower($row['status_aset_setelah_perawatan']);
                                    $badge_class = [
                                        'baik' => 'success',
                                        'rusak ringan' => 'warning',
                                        'rusak berat' => 'danger'
                                    ][$status] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td><?= date('d M Y', strtotime($row['tanggal_perawatan'])) ?></td>
                                        <td><?= htmlspecialchars($row['teknisi']) ?></td>
                                        <td>Rp<?= number_format($row['biaya'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate" title="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>">
                                            <?= htmlspecialchars($row['deskripsi'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-inbox"></i>
                                            <p>Tidak ada data riwayat maintenance</p>
                                        </div>
                                    </td>
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
                            $prev_url = "?page=$prev_page&aset=" . urlencode($aset_filter) . "&tanggal=" . urlencode($tanggal_filter);
                            echo "<a href='$prev_url' class='prev'>« Previous</a>";
                        }

                        // Page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            $first_url = "?page=1&aset=" . urlencode($aset_filter) . "&tanggal=" . urlencode($tanggal_filter);
                            echo "<a href='$first_url'>1</a>";
                            if ($start_page > 2) echo "<span class='dots'>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $page_url = "?page=$i&aset=" . urlencode($aset_filter) . "&tanggal=" . urlencode($tanggal_filter);
                            $active_class = ($i == $page) ? 'active' : '';
                            echo "<a href='$page_url' class='$active_class'>$i</a>";
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
                            $last_url = "?page=$total_pages&aset=" . urlencode($aset_filter) . "&tanggal=" . urlencode($tanggal_filter);
                            echo "<a href='$last_url'>$total_pages</a>";
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $next_page = $page + 1;
                            $next_url = "?page=$next_page&aset=" . urlencode($aset_filter) . "&tanggal=" . urlencode($tanggal_filter);
                            echo "<a href='$next_url' class='next'>Next »</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>
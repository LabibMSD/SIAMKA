<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// 🔍 Ambil filter dari query string
$status_filter = $_GET['status'] ?? '';
$user_filter   = $_GET['user'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';

// 🔧 Query dasar
$query = "
    SELECT 
        l.*, 
        u.nama AS nama_user, 
        a.nama_aset, 
        a.kode_aset
    FROM loans l
    LEFT JOIN users u ON l.id_user = u.id_user
    LEFT JOIN assets a ON l.id_aset = a.id_aset
    WHERE 1=1
";

// Tambahkan filter dinamis
$params = [];
$types = '';

if ($status_filter !== '') {
    $query .= " AND l.status = ? ";
    $params[] = $status_filter;
    $types .= 's';
}

if ($user_filter !== '') {
    $query .= " AND u.nama_lengkap LIKE ? ";
    $params[] = "%$user_filter%";
    $types .= 's';
}

if ($tanggal_filter !== '') {
    $query .= " AND DATE(l.start_date) = ? ";
    $params[] = $tanggal_filter;
    $types .= 's';
}

$query .= " ORDER BY l.created_at DESC";

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
                <h1 class="page-title"><i class="fa-solid fa-clipboard-list me-2"></i>Kelola Peminjaman</h1>
                <p class="page-subtitle">Kelola semua peminjaman aset dan statusnya dari sini.</p>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Peminjaman</h3>
            </div>
            <div class="card-body">
                <?php display_notification(); ?>

                <!-- Filter Form -->
                <form method="get" class="filter-form mb-4">
                    <div class="filter-group">
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="returned" <?= $status_filter == 'returned' ? 'selected' : '' ?>>Returned</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" name="user" value="<?= htmlspecialchars($user_filter) ?>" class="form-control" placeholder="Cari nama pengguna...">
                    </div>
                    <div class="filter-group">
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal_filter) ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </form>

                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama User</th>
                                <th>Nama Aset</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Feedback</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $no = 1;
                                while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                                        <td><?= htmlspecialchars($row['start_date']) ?></td>
                                        <td><?= htmlspecialchars($row['end_date']) ?></td>
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
                                        <td>
                                            <?php if ($status == 'returned' && !empty($row['feedback'])): ?>
                                                <span title="<?= htmlspecialchars($row['feedback']) ?>"><?= htmlspecialchars(substr($row['feedback'], 0, 50)) ?><?php if (strlen($row['feedback']) > 50) echo '...'; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status == 'pending'): ?>
                                                <a href="approve.php?id_peminjaman=<?= $row['id_peminjaman'] ?>" class="btn btn-success btn-sm">
                                                    <i class="fa-solid fa-check"></i> Approve
                                                </a>
                                                <a href="reject.php?id_peminjaman=<?= $row['id_peminjaman'] ?>" class="btn btn-danger btn-sm">
                                                    <i class="fa-solid fa-xmark"></i> Reject
                                                </a>
                                                <a href="return.php?id_peminjaman=<?= $row['id_peminjaman'] ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-undo"></i> Return
                                                </a>

                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-info-circle"></i> Tidak ada data peminjaman.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</main>

<?php include '../../includes/footer.php'; ?>
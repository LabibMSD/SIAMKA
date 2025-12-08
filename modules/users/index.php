<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';
checkRole(['admin']);

$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM users WHERE deleted_at IS NULL";
$params = [];
$types = '';

if ($role_filter !== '') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if ($search !== '') {
    $query .= " AND (nama LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$query .= " ORDER BY id_user DESC";
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
                <h1 class="page-title"><i class="fa-solid fa-users"></i> Manajemen Pengguna</h1>
                <p class="page-subtitle">Kelola semua pengguna dan hak akses sistem</p>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Pengguna</h3>
                <div class="card-actions">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Tambah User
                    </a>
                    <a href="deleted_users.php" class="btn btn-outline-danger">
                        <i class="fa-solid fa-user-slash"></i> User Terhapus
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php display_notification(); ?>

                <!-- Filter Form -->
                <form method="get" class="filter-form mb-4">
                    <div class="filter-group">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari nama atau email...">
                    </div>
                    <div class="filter-group">
                        <select name="role" class="form-control">
                            <option value="">Semua Role</option>
                            <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="manajemen" <?= $role_filter == 'manajemen' ? 'selected' : '' ?>>Manajemen</option>
                            <option value="pengguna" <?= $role_filter == 'pengguna' ? 'selected' : '' ?>>Pengguna</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                </form>

                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $no = 1;
                                while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $role = $row['role'];
                                    $badge_class = [
                                        'admin' => 'danger',
                                        'manajemen' => 'warning',
                                        'pengguna' => 'primary'
                                    ][$role] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($role) ?></span>
                                        </td>
                                        <td>
                                            <a href="edit.php?id_user=<?= $row['id_user'] ?>" class="btn btn-primary" title="Edit">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a href="delete.php?id_user=<?= $row['id_user'] ?>" class="btn" style="background-color: var(--danger-red, #ef4444); color: var(--text-light, #ffffff);" onclick="return confirm('Hapus user ini?')" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-inbox"></i>
                                            <p>Tidak ada pengguna</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>



<?php include '../../includes/footer.php'; ?>
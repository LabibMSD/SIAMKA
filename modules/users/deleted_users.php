<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';
checkRole(['admin']);

$query = "SELECT * FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC";
$result = $conn->query($query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="dashboard-container">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-user-slash me-2"></i>Pengguna Terhapus</h1>
                <p class="page-subtitle">Kelola pengguna yang telah dihapus</p>
            </div>
            <div class="page-header-meta">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <?php display_notification(); ?>

        <div class="card card-shadow">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Dihapus Pada</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $no = 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= ucfirst($row['role']) ?></td>
                                    <td><?= htmlspecialchars($row['deleted_at']) ?></td>
                                    <td class="text-center">
                                        <a href="restore.php?id_user=<?= $row['id_user'] ?>" class="btn btn-sm btn-success me-1"
                                            onclick="return confirm('Kembalikan user ini?')">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </a>
                                        <a href="delete_permanent.php?id_user=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Hapus permanen user ini? Tindakan tidak bisa dibatalkan!')">
                                            <i class="fa-solid fa-trash"></i> Hapus Permanen
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-circle-info me-1"></i> Tidak ada user terhapus.
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
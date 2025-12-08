<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';
checkRole(['admin']);

$selected_kategori = $_GET['kategori'] ?? '';

// 🔹 Query data kategori + jumlah aset
$query = "SELECT c.*, COUNT(a.id_aset) AS total_aset
          FROM categories c
          LEFT JOIN assets a ON c.id_kategori = a.id_kategori
          WHERE 1=1";
$params = [];
$types = '';

if ($selected_kategori !== '') {
    $query .= " AND c.id_kategori = ?";
    $params[] = $selected_kategori;
    $types .= 'i';
}

$query .= " GROUP BY c.id_kategori ORDER BY c.nama_kategori ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 🔹 Data untuk dropdown kategori
$kategori_result = $conn->query("SELECT id_kategori, nama_kategori FROM categories ORDER BY nama_kategori ASC");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-tags"></i> Manajemen Kategori Aset</h1>
                <p class="page-subtitle">Kelola daftar kategori dan pengelompokan aset</p>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Kategori</h3>
                <div class="card-actions">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Tambah Kategori
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php display_notification(); ?>

                <!-- Filter Form -->
                <form method="get" class="filter-form mb-4">
                    <div class="filter-group">
                        <select name="kategori" class="form-control" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php $kategori_result->data_seek(0);
                            while ($kat = $kategori_result->fetch_assoc()): ?>
                                <option value="<?= $kat['id_kategori'] ?>"
                                    <?= $selected_kategori == $kat['id_kategori'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>

                <!-- Data Table -->
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Aset</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $no = 1;
                                while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_kategori']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['deskripsi']) ?: '-' ?></td>
                                        <td><span class="badge bg-primary"><?= $row['total_aset'] ?></span></td>
                                        <td>
                                            <a href="edit.php?id=<?= $row['id_kategori'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $row['id_kategori'] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Yakin hapus kategori ini?')" title="Hapus">
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
                                            <p>Tidak ada kategori</p>
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
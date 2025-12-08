<?php
session_start();
define("SECURE", true);

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

checkRole(['admin']);
$page_title = "Aset Terhapus";

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Hitung total aset terhapus
$countQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM assets 
    WHERE deleted_at IS NOT NULL
    AND (nama_aset LIKE '%$search%' OR kode_aset LIKE '%$search%')
");
$total_assets = mysqli_fetch_assoc($countQuery)['total'];
$total_pages = ceil($total_assets / $limit);

// Ambil data aset terhapus
$query = mysqli_query($conn, "
    SELECT * FROM assets 
    WHERE deleted_at IS NOT NULL 
    AND (nama_aset LIKE '%$search%' OR kode_aset LIKE '%$search%')
    ORDER BY deleted_at DESC 
    LIMIT $limit OFFSET $offset
");
?>

<main class="main-content">
  <div class="dashboard-container">
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title">🗑️ Aset Terhapus</h1>
        <p class="page-subtitle">Kelola aset yang telah dihapus</p>
      </div>
      <div class="page-header-meta">
        <a href="index.php" class="btn btn-primary">← Kembali ke Aset Aktif</a>
      </div>
    </div>

    <?php display_notification(); ?>

    <div class="card card-shadow">
      <!-- Search Bar -->
      <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Cari aset terhapus..." value="<?= htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-secondary">Cari</button>
      </form>

      <!-- Grid Aset -->
      <div class="asset-grid">
        <?php if (mysqli_num_rows($query) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($query)): ?>
            <div class="asset-card">
              <img src="../../assets/uploads/assets/<?= htmlspecialchars($row['foto'] ?? 'default.jpg'); ?>"
                alt="<?= htmlspecialchars($row['nama_aset']); ?>">

              <div class="asset-info">
                <h3><?= htmlspecialchars($row['nama_aset']); ?></h3>
                <p><strong>Kode:</strong> <?= htmlspecialchars($row['kode_aset']); ?></p>
                <p><strong>Lokasi:</strong> <?= htmlspecialchars($row['lokasi']); ?></p>
                <p><strong>Harga:</strong> Rp <?= number_format($row['harga'], 0, ',', '.'); ?></p>
                <p><strong>Dihapus:</strong> <?= htmlspecialchars($row['deleted_at']); ?></p>
              </div>

              <div class="asset-actions">
                <a href="restore.php?id=<?= $row['id_aset']; ?>" class="btn-action view"
                  onclick="return confirm('Pulihkan aset ini?')">Restore</a>
                <a href="delete_permanent.php?id=<?= $row['id_aset']; ?>" class="btn-action delete"
                  onclick="return confirm('Hapus aset ini secara permanen?')">Delete Permanen</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="no-data">Tidak ada aset yang dihapus.</p>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
            class="<?= $i == $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    </div> <!-- card -->
  </div> <!-- dashboard-container -->
</main>

<link rel="stylesheet" href="../../assets/assets.css">

<?php include '../../includes/footer.php'; ?>
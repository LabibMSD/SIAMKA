<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/notification_helper.php';
display_notification();

checkRole(['admin', 'manajemen']);

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & Filter
$search   = isset($_GET['search']) ? $_GET['search'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$kondisi  = isset($_GET['kondisi']) ? $_GET['kondisi'] : '';
$status   = isset($_GET['status']) ? $_GET['status'] : '';

// Base query
$where = "WHERE a.deleted_at IS NULL AND (a.nama_aset LIKE '%$search%' OR a.kode_aset LIKE '%$search%')";
if ($kategori != '') $where .= " AND a.id_kategori = '$kategori'";
if ($kondisi != '') $where .= " AND a.kondisi = '$kondisi'";
if ($status != '') $where .= " AND a.status = '$status'";

// Hitung total aset
$countQuery = mysqli_query($conn, "
  SELECT COUNT(*) AS total 
  FROM assets a 
  LEFT JOIN categories c ON a.id_kategori = c.id_kategori 
  $where
");
$total_assets = mysqli_fetch_assoc($countQuery)['total'];
$total_pages = ceil($total_assets / $limit);

// Ambil data aset
$query = mysqli_query($conn, "
  SELECT a.*, c.nama_kategori 
  FROM assets a 
  LEFT JOIN categories c ON a.id_kategori = c.id_kategori 
  $where 
  ORDER BY a.id_aset DESC 
  LIMIT $limit OFFSET $offset
");
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-box"></i> Manajemen Aset</h1>
        <p class="page-subtitle">Kelola semua aset kampus dari sini. Cari, filter, dan kelola status aset dengan mudah.</p>
      </div>
      <div class="page-header-meta">
        <a href="add.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Aset</a>
        <a href="trash.php" class="btn btn-secondary"><i class="fa-solid fa-trash"></i> Terhapus</a>
      </div>
    </div>

    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-box"></i> Daftar Aset</h3>
      </div>

      <div class="card-body">
        <!-- Search & Filter -->
        <form method="GET" id="filterForm" class="filter-form mb-4">
          <div class="filter-group">
            <input type="text" name="search" class="form-control"
              placeholder="Cari nama atau kode aset..."
              value="<?= htmlspecialchars($search); ?>">
          </div>

          <div class="filter-group">
            <select name="kategori" class="form-select">
              <option value="">-- Kategori --</option>
              <?php
              $kategori_q = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");
              while ($k = mysqli_fetch_assoc($kategori_q)) {
                $selected = ($kategori == $k['id_kategori']) ? 'selected' : '';
                echo "<option value='{$k['id_kategori']}' $selected>{$k['nama_kategori']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="filter-group">
            <select name="kondisi" class="form-select">
              <option value="">-- Kondisi --</option>
              <option value="baik" <?= $kondisi == 'baik' ? 'selected' : '' ?>>Baik</option>
              <option value="rusak" <?= $kondisi == 'rusak' ? 'selected' : '' ?>>Rusak</option>
              <option value="hilang" <?= $kondisi == 'hilang' ? 'selected' : '' ?>>Hilang</option>
            </select>
          </div>

          <div class="filter-group">
            <select name="status" class="form-select">
              <option value="">-- Status --</option>
              <option value="Tersedia" <?= $status == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
              <option value="Dipinjam" <?= $status == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
              <option value="maintenance" <?= $status == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            </select>
          </div>

          <div class="filter-group">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fa-solid fa-magnifying-glass"></i> Cari
            </button>
          </div>
        </form>

        <script>
          document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('#filterForm select').forEach(select => {
              select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
              });
            });
          });
        </script>

        <!-- Grid Aset -->
        <div class="asset-grid">
          <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
              <div class="asset-card">
                <div class="card card-asset">
                  <div class="card-image-wrapper">
                    <img src="../../assets/uploads/assets/<?= htmlspecialchars($row['foto']); ?>"
                      alt="<?= htmlspecialchars($row['nama_aset']); ?>"
                      class="card-image">
                    <div class="card-badge-group">
                      <?php
                      $status_color = $row['status'] === 'Tersedia' ? 'success' : ($row['status'] === 'Dipinjam' ? 'warning' : 'secondary');
                      ?>
                      <span class="badge badge-<?= $status_color ?>"><?= htmlspecialchars($row['status']); ?></span>
                    </div>
                  </div>

                  <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($row['nama_aset']); ?></h5>
                    <p class="card-meta"><strong>Kode:</strong> <?= htmlspecialchars($row['kode_aset']); ?></p>
                    <p class="card-meta"><strong>Kategori:</strong> <?= htmlspecialchars($row['nama_kategori']); ?></p>
                    <p class="card-meta"><strong>Kondisi:</strong> <?= htmlspecialchars($row['kondisi']); ?></p>
                  </div>

                  <div class="card-footer flex-wrap">
                    <a href="detail.php?id=<?= $row['id_aset']; ?>" class="btn btn-primary" title="Detail">
                      <i class="fa-solid fa-eye"></i> Lihat
                    </a>
                    <a href="edit.php?id=<?= $row['id_aset']; ?>" class="btn btn-secondary" title="Edit">
                      <i class="fa-solid fa-edit"></i> Edit
                    </a>
                    <a href="delete.php?id_aset=<?= $row['id_aset']; ?>"
                      class="btn"
                      style="background-color: var(--danger-red, #ef4444); color: var(--text-light, #ffffff);"
                      onclick="return confirm('Yakin ingin menghapus aset ini?')"
                      title="Hapus">
                      <i class="fa-solid fa-trash"></i> Hapus
                    </a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
              <i class="fa-solid fa-inbox"></i>
              <p>Tidak ada aset ditemukan</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination">
            <?php
            // Previous button
            if ($page > 1) {
              $prev_page = $page - 1;
              $prev_url = "?page=$prev_page&search=" . urlencode($search) . "&kategori=" . urlencode($kategori) . "&kondisi=" . urlencode($kondisi) . "&status=" . urlencode($status);
              echo "<a href='$prev_url' class='prev'>« Previous</a>";
            }

            // Page numbers
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
              $first_url = "?page=1&search=" . urlencode($search) . "&kategori=" . urlencode($kategori) . "&kondisi=" . urlencode($kondisi) . "&status=" . urlencode($status);
              echo "<a href='$first_url'>1</a>";
              if ($start_page > 2) echo "<span class='dots'>...</span>";
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
              $page_url = "?page=$i&search=" . urlencode($search) . "&kategori=" . urlencode($kategori) . "&kondisi=" . urlencode($kondisi) . "&status=" . urlencode($status);
              $active_class = ($i == $page) ? 'active' : '';
              echo "<a href='$page_url' class='$active_class'>$i</a>";
            }

            if ($end_page < $total_pages) {
              if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
              $last_url = "?page=$total_pages&search=" . urlencode($search) . "&kategori=" . urlencode($kategori) . "&kondisi=" . urlencode($kondisi) . "&status=" . urlencode($status);
              echo "<a href='$last_url'>$total_pages</a>";
            }

            // Next button
            if ($page < $total_pages) {
              $next_page = $page + 1;
              $next_url = "?page=$next_page&search=" . urlencode($search) . "&kategori=" . urlencode($kategori) . "&kondisi=" . urlencode($kondisi) . "&status=" . urlencode($status);
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
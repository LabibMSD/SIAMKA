<?php
define("SECURE", true);
session_start();

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Hanya pengguna yang bisa buka halaman ini
checkRole(['pengguna']);

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>alert('ID aset tidak ditemukan!'); window.location='available.php';</script>";
  exit;
}

$id = intval($_GET['id']);

// Ambil data aset
$query = "
    SELECT a.*, k.nama_kategori 
    FROM assets a
    LEFT JOIN categories k ON a.id_kategori = k.id_kategori
    WHERE a.id_aset = $id AND a.deleted_at IS NULL
";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
  echo "<script>alert('Data aset tidak ditemukan!'); window.location='available.php';</script>";
  exit;
}

$asset = $result->fetch_assoc();
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-file-contract"></i> Detail Aset</h1>
        <p class="page-subtitle">Informasi lengkap mengenai aset yang tersedia</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><?= htmlspecialchars($asset['nama_aset']); ?></h3>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col">
            <?php if (!empty($asset['foto'])): ?>
              <img src="../../assets/uploads/assets/<?= htmlspecialchars($asset['foto']); ?>"
                alt="<?= htmlspecialchars($asset['nama_aset']); ?>"
                class="img-fluid rounded shadow-sm"
                style="max-height:350px; width:100%; object-fit:cover;">
            <?php else: ?>
              <img src="../../assets/no-image.png" alt="Tidak ada foto"
                class="img-fluid rounded shadow-sm"
                style="max-height:350px; width:100%; object-fit:cover;">
            <?php endif; ?>
          </div>
          <div class="col">
            <div class="details-grid">
              <div class="detail-item row">
                <label class="detail-label col">Kode Aset</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['kode_aset']); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Nama Aset</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['nama_aset']); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Kategori</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['nama_kategori'] ?? '-'); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Kondisi</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['kondisi'] ?? '-'); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Status</label>
                <p class="detail-value col"><span class="badge bg-success"><?= htmlspecialchars($asset['status']); ?></span></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Lokasi</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['lokasi']); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Harga</label>
                <p class="detail-value col">Rp <?= number_format($asset['harga'], 0, ',', '.'); ?></p>
              </div>
              <div class="detail-item row">
                <label class="detail-label col">Tanggal Perolehan</label>
                <p class="detail-value col"><?= htmlspecialchars($asset['tanggal_perolehan']); ?></p>
              </div>
              <div class="detail-item row full-width">
                <label class="detail-label col">Keterangan</label>
                <p class="detail-value col"><?= nl2br(htmlspecialchars($asset['keterangan'] ?? '-')); ?></p>
              </div>
            </div>

            <div class="form-actions mt-4 row">
              <a href="request.php?id_aset=<?= $asset['id_aset']; ?>" class="btn btn-primary">
                <i class="fa-solid fa-box-arrow-in-right"></i> Pinjam Aset
              </a>
              <a href="available.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
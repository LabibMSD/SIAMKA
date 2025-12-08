<?php
define("SECURE", true);
session_start();

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Hak akses: admin, manajemen, dan pengguna bisa melihat
checkRole(['admin', 'manajemen', 'pengguna']);

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>alert('ID aset tidak ditemukan!'); window.location='index.php';</script>";
  exit;
}

$id = intval($_GET['id']);

// Ambil data aset dengan join kategori
$query = "
    SELECT a.*, k.nama_kategori 
    FROM assets a
    LEFT JOIN categories k ON a.id_kategori = k.id_kategori
    WHERE a.id_aset = $id AND a.deleted_at IS NULL
";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
  echo "<script>alert('Data aset tidak ditemukan!'); window.location='index.php';</script>";
  exit;
}

$asset = $result->fetch_assoc();
?>

<main class="main-content">
  <div class="page-header">
    <h2>📋 Detail Aset</h2>
    <p class="text-muted">Informasi lengkap mengenai aset yang dipilih.</p>
  </div>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert success">
      <?= htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <div class="card asset-detail-card">
    <div class="card-header">
      <h3><?= htmlspecialchars($asset['nama_aset']); ?></h3>
    </div>
 
    <div class="card-body asset-detail">
      <div class="asset-photo">
        <?php if (!empty($asset['foto'])): ?>
          <img src="../../assets/uploads/assets/<?= htmlspecialchars($asset['foto']); ?>"
            alt="<?= htmlspecialchars($asset['nama_aset']); ?>">
        <?php else: ?>
          <img src="../../assets/no-image.png" alt="Tidak ada foto">
        <?php endif; ?>
      </div>

      <div class="asset-info">
        <table class="table-detail">
          <tr>
            <th>Kode Aset</th>
            <td><?= htmlspecialchars($asset['kode_aset']); ?></td>
          </tr>
          <tr>
            <th>Nama Aset</th>
            <td><?= htmlspecialchars($asset['nama_aset']); ?></td>
          </tr>
          <tr>
            <th>Kategori</th>
            <td><?= htmlspecialchars($asset['nama_kategori'] ?? '-'); ?></td>
          </tr>
          <tr>
            <th>Kondisi</th>
            <td><?= htmlspecialchars($asset['kondisi'] ?? '-'); ?></td>
          </tr>
          <tr>
            <th>Status</th>
            <td><?= htmlspecialchars($asset['status']); ?></td>
          </tr>
          <tr>
            <th>Lokasi</th>
            <td><?= htmlspecialchars($asset['lokasi']); ?></td>
          </tr>
          <tr>
            <th>Harga</th>
            <td>Rp <?= number_format($asset['harga'], 0, ',', '.'); ?></td>
          </tr>
          <tr>
            <th>Tanggal Perolehan</th>
            <td><?= htmlspecialchars($asset['tanggal_perolehan']); ?></td>
          </tr>
          <tr>
            <th>Keterangan</th>
            <td><?= nl2br(htmlspecialchars($asset['keterangan'] ?? '-')); ?></td>
          </tr>
        </table>

        <div class="action-buttons">
          <a href="edit.php?id=<?= $asset['id_aset']; ?>&from=detail" class="btn btn-primary">✏️ Edit</a>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="delete.php?id_aset=<?= $asset['id_aset']; ?>" class="btn btn-danger"
              onclick="return confirm('Yakin ingin menghapus aset ini?')">🗑 Hapus</a>
          <?php endif; ?>
          <a href="index.php" class="btn btn-secondary">⬅ Kembali</a>
        </div>
      </div>
    </div>
  </div>
</main>


<?php require_once '../../includes/footer.php'; ?>
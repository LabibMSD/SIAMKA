<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// 📦 Ambil daftar aset
$aset_result = $conn->query("SELECT id_aset, nama_aset, kode_aset FROM assets WHERE deleted_at IS NULL ORDER BY nama_aset");
$aset_list = $aset_result->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-calendar-plus"></i> Tambah Jadwal Maintenance</h1>
        <p class="page-subtitle">Buat jadwal pemeliharaan baru untuk aset</p>
      </div>
    </div>

    <!-- FORM CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Form Jadwal Baru</h3>
        <div class="card-actions">
          <a href="schedule.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST" action="add_schedule.php" class="form-vertical">
          <div class="form-group">
            <label class="form-label fw-semibold">Pilih Aset <span class="text-danger">*</span></label>
            <select name="id_aset" class="form-control" required>
              <option value="">-- Pilih Aset --</option>
              <?php foreach ($aset_list as $aset): ?>
                <option value="<?= $aset['id_aset']; ?>">
                  <?= htmlspecialchars($aset['nama_aset']) ?> (<?= htmlspecialchars($aset['kode_aset']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label fw-semibold">Tanggal Maintenance <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_jadwal" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="4" placeholder="Contoh: Perawatan rutin bulanan, pengecekan fungsi, dll..."></textarea>
          </div>

          <div class="form-actions">
            <a href="schedule.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-save"></i> Simpan Jadwal
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
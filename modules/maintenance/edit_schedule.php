<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// --- Validasi ID Jadwal ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
  set_notification("error", "ID jadwal tidak ditemukan.");
  header("Location: add_schedule.php");
  exit;
}

$id_jadwal = intval($_GET['id']);

// --- Ambil Data Jadwal yang Akan Diedit ---
$query = "
    SELECT 
        ms.id_jadwal,
        ms.id_aset,
        ms.tanggal_jadwal,
        ms.keterangan,
        ms.status,
        a.nama_aset
    FROM maintenance_schedule ms
    LEFT JOIN assets a ON ms.id_aset = a.id_aset
    WHERE ms.id_jadwal = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_jadwal);
$stmt->execute();
$result = $stmt->get_result();
$jadwal = $result->fetch_assoc();

if (!$jadwal) {
  set_notification("error", "Data jadwal tidak ditemukan.");
  header("Location: add_schedule.php");
  exit;
}

// --- Ambil Semua Aset Aktif untuk Dropdown ---
$aset_result = $conn->query("SELECT id_aset, nama_aset FROM assets WHERE deleted_at IS NULL AND status != 'maintenance'");

// --- Proses Update Jadwal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_aset = $_POST['id_aset'];
  $tanggal_jadwal = $_POST['tanggal_jadwal'];
  $keterangan = $_POST['keterangan'];
  $status = $_POST['status'];

  $update = $conn->prepare("
        UPDATE maintenance_schedule 
        SET id_aset = ?, tanggal_jadwal = ?, keterangan = ?, status = ?
        WHERE id_jadwal = ?
    ");
  $update->bind_param("isssi", $id_aset, $tanggal_jadwal, $keterangan, $status, $id_jadwal);

  if ($update->execute()) {
    set_notification("success", "✅ Jadwal maintenance berhasil diperbarui.");
    header("Location: schedule.php");
    exit;
  } else {
    set_notification("error", "❌ Gagal memperbarui jadwal: " . $conn->error);
  }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-pen-to-square"></i> Edit Jadwal Maintenance</h1>
        <p class="page-subtitle">Perbarui detail jadwal pemeliharaan</p>
      </div>
    </div>

    <!-- FORM CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Form Edit Jadwal</h3>
        <div class="card-actions">
          <a href="schedule.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST" class="form-vertical">
          <div class="form-group">
            <label class="form-label fw-semibold">Pilih Aset <span class="text-danger">*</span></label>
            <select name="id_aset" class="form-control" required>
              <option value="">-- Pilih Aset --</option>
              <?php while ($a = $aset_result->fetch_assoc()): ?>
                <option value="<?= $a['id_aset']; ?>" <?= $a['id_aset'] == $jadwal['id_aset'] ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($a['nama_aset']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label fw-semibold">Tanggal Jadwal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_jadwal" class="form-control" value="<?= $jadwal['tanggal_jadwal']; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label fw-semibold">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="4"><?= htmlspecialchars($jadwal['keterangan']); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label fw-semibold">Status Jadwal <span class="text-danger">*</span></label>
            <select name="status" class="form-control" required>
              <option value="terjadwal" <?= $jadwal['status'] === 'terjadwal' ? 'selected' : ''; ?>>Terjadwal</option>
              <option value="selesai" <?= $jadwal['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
              <option value="dibatalkan" <?= $jadwal['status'] === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
            </select>
          </div>

          <div class="form-actions">
            <a href="schedule.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-save"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
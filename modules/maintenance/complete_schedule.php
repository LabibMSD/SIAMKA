<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']); // hanya admin & manajemen

// Pastikan parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
  set_notification("error", "ID jadwal tidak ditemukan.");
  header("Location: add_schedule.php");
  exit;
}

$id_jadwal = intval($_GET['id']);

// Ambil data jadwal maintenance
$query = "
  SELECT 
    ms.id_jadwal,
    ms.id_aset,
    ms.id_petugas,
    ms.tanggal_jadwal,
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tanggal_perawatan = date('Y-m-d'); // tanggal hari ini
  $biaya = $_POST['biaya'] ?? 0;
  $teknisi = trim($_POST['teknisi'] ?? '');
  $deskripsi = $_POST['deskripsi'] ?? '';
  $status_aset = $_POST['status_aset'] ?? 'baik';

  // Validasi teknisi wajib diisi
  if ($teknisi === '') {
    set_notification("error", "Nama teknisi wajib diisi.");
    header("Location: complete_schedule.php?id=" . $id_jadwal);
    exit;
  }

  // Insert ke maintenance_history
  $insert = $conn->prepare("
        INSERT INTO maintenance_history (id_aset, tanggal_perawatan, biaya, teknisi, deskripsi, status_aset_setelah_perawatan)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
  $insert->bind_param("isdsss", $jadwal['id_aset'], $tanggal_perawatan, $biaya, $teknisi, $deskripsi, $status_aset);
  $insert->execute();

  // Update status jadwal
  $update = $conn->prepare("UPDATE maintenance_schedule SET status = 'selesai' WHERE id_jadwal = ?");
  $update->bind_param("i", $id_jadwal);
  $update->execute();

  set_notification("success", "Jadwal maintenance telah diselesaikan dan dicatat dalam riwayat.");
  header("Location: add_schedule.php");
  exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-check"></i> Selesaikan Jadwal Maintenance</h1>
        <p class="page-subtitle">Tandai jadwal pemeliharaan sebagai selesai</p>
      </div>
    </div>

    <!-- FORM CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-tools"></i> Detail Jadwal Maintenance</h3>
        <div class="card-actions">
          <a href="schedule.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST" class="form-vertical">
          <!-- INFO SECTION -->
          <div class="form-section mb-4">
            <h5 class="section-title">Informasi Jadwal</h5>

            <div class="form-group">
              <label class="form-label fw-semibold">Nama Aset</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($jadwal['nama_aset']); ?>" readonly>
            </div>

            <div class="form-group">
              <label class="form-label fw-semibold">Tanggal Jadwal</label>
              <input type="text" class="form-control" value="<?= date('d M Y', strtotime($jadwal['tanggal_jadwal'])); ?>" readonly>
            </div>
          </div>

          <!-- MAINTENANCE DETAILS -->
          <div class="form-section">
            <h5 class="section-title">Detail Pemeliharaan</h5>

            <div class="form-group">
              <label class="form-label fw-semibold">Nama Teknisi <span class="text-danger">*</span></label>
              <input type="text" name="teknisi" class="form-control" placeholder="Masukkan nama teknisi" required>
            </div>

            <div class="form-group">
              <label class="form-label fw-semibold">Biaya Perawatan (Rp)</label>
              <input type="number" name="biaya" class="form-control" step="0.01" min="0" placeholder="Masukkan biaya perawatan">
            </div>

            <div class="form-group">
              <label class="form-label fw-semibold">Deskripsi / Catatan</label>
              <textarea name="deskripsi" class="form-control" rows="3" placeholder="Catatan hasil maintenance, apa yang diperbaiki, dll..."></textarea>
            </div>

            <div class="form-group">
              <label class="form-label fw-semibold">Status Aset Setelah Perawatan <span class="text-danger">*</span></label>
              <select name="status_aset" class="form-control" required>
                <option value="">-- Pilih Status --</option>
                <option value="baik">Baik</option>
                <option value="rusak ringan">Rusak Ringan</option>
                <option value="rusak berat">Rusak Berat</option>
              </select>
            </div>
          </div>

          <div class="form-actions">
            <a href="schedule.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-check"></i> Tandai Selesai
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/notification_helper.php';

// Role yang boleh request peminjaman
checkRole(['pengguna']);

// pastikan session aktif
$id_user = $_SESSION['id_user'] ?? null;
if (!$id_user) {
  set_notification('error', 'Session pengguna tidak ditemukan. Silakan login ulang.');
  header('Location: ../../login.php');
  exit;
}

$id_aset = isset($_GET['id_aset']) ? intval($_GET['id_aset']) : 0;

// 🔹 Ambil data aset dulu
$query = $conn->prepare("SELECT * FROM assets WHERE id_aset = ? AND status = 'tersedia'");
$query->bind_param("i", $id_aset);
$query->execute();
$result = $query->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
  set_notification('error', '⚠️ Aset tidak tersedia untuk dipinjam.');
  header('Location: available.php');
  exit;
}

// 🔹 Proses form (HARUS sebelum output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_user = $_SESSION['id_user'];
  $start_date = $_POST['start_date'] ?? null;
  $end_date = $_POST['end_date'] ?? null;

  if (empty($start_date) || empty($end_date)) {
    set_notification('error', '⚠️ Tanggal pinjam dan kembali harus diisi.');
  } else {
    $stmt = $conn->prepare("INSERT INTO loans (id_user, id_aset, start_date, end_date, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $id_user, $id_aset, $start_date, $end_date);

    if ($stmt->execute()) {
      set_notification('success', '✅ Permintaan peminjaman berhasil dikirim dan menunggu persetujuan.');
      header('Location: available.php');
      exit;
    } else {
      set_notification('error', '❌ Gagal mengirim permintaan peminjaman.');
    }
  }
}

// 🔹 HTML baru mulai di bawah ini
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-clipboard-list"></i> Request Peminjaman Aset</h1>
        <p class="page-subtitle">Ajukan permintaan peminjaman untuk aset pilihan Anda</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-form"></i> Form Request Peminjaman</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <div class="asset-info-section mb-4 p-4 bg-light rounded">
          <h5 class="mb-3"><i class="fa-solid fa-box"></i> Informasi Aset</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <p><small class="text-muted">Kode Aset</small></p>
              <p class="fw-bold"><?= htmlspecialchars($asset['kode_aset']) ?></p>
            </div>
            <div class="col-md-6">
              <p><small class="text-muted">Nama Aset</small></p>
              <p class="fw-bold"><?= htmlspecialchars($asset['nama_aset']) ?></p>
            </div>
            <div class="col-md-6">
              <p><small class="text-muted">Kondisi</small></p>
              <p class="fw-bold"><?= htmlspecialchars($asset['kondisi']) ?></p>
            </div>
            <div class="col-md-6">
              <p><small class="text-muted">Lokasi</small></p>
              <p class="fw-bold"><?= htmlspecialchars($asset['lokasi']) ?></p>
            </div>
          </div>
        </div>

        <form method="post">
          <div class="form-group">
            <label class="form-label">Tanggal Pinjam</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Tanggal Kembali</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-paper-plane"></i> Kirim Permintaan
            </button>
            <a href="available.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark"></i> Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../includes/notification_helper.php';
include '../../config/config.php';
include '../../config/database.php';

checkRole(['admin']);

// Pastikan id kategori ada
if (!isset($_GET['id'])) {
  die("❌ ID kategori tidak ditemukan.");
}

$id_kategori = $_GET['id'];

// Ambil data kategori
$stmt = $conn->prepare("SELECT * FROM categories WHERE id_kategori = ?");
$stmt->bind_param("i", $id_kategori);
$stmt->execute();
$result = $stmt->get_result();
$kategori = $result->fetch_assoc();

if (!$kategori) {
  die("❌ Data kategori tidak ditemukan.");
}

// === PROSES POST DITARUH SEBELUM OUTPUT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_kategori = trim($_POST['nama_kategori']);
  $deskripsi = trim($_POST['deskripsi']);

  // Cek nama unik
  $check = $conn->prepare("SELECT id_kategori FROM categories WHERE nama_kategori = ? AND id_kategori != ?");
  $check->bind_param("si", $nama_kategori, $id_kategori);
  $check->execute();
  $check_result = $check->get_result();

  if ($check_result->num_rows > 0) {
    set_notification('error', '❌ Nama kategori sudah digunakan oleh kategori lain.');
  } else {
    $update = $conn->prepare("UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id_kategori = ?");
    $update->bind_param("ssi", $nama_kategori, $deskripsi, $id_kategori);

    if ($update->execute()) {
      set_notification('success', '✅ Data kategori berhasil diperbarui.');
    } else {
      set_notification('error', '❌ Gagal memperbarui data kategori.');
    }
  }

  // Redirect sebelum ada HTML
  header('Location: index.php');
  exit;
}

// === BARU MULAI OUTPUT HTML ===
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-tag-pen"></i> Edit Kategori</h1>
        <p class="page-subtitle">Perbarui informasi kategori</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-form"></i> Form Edit Kategori</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">Nama Kategori</label>
            <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($kategori['nama_kategori']); ?>" placeholder="Masukkan nama kategori" required>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan kategori ini..."><?= htmlspecialchars($kategori['deskripsi']); ?></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-check"></i> Simpan
            </button>
            <a href="index.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-xmark"></i> Batal
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
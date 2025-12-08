<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include helper & config, tapi JANGAN include header/sidebar dulu
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../includes/notification_helper.php';
include '../../config/config.php';
include '../../config/database.php';

// Batasi akses hanya untuk admin
checkRole(['admin']);

// Proses tambah kategori (sebelum output HTML!)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama_kategori']);
  $desc = trim($_POST['deskripsi']);

  if ($nama === '') {
    set_notification('error', '❌ Nama kategori wajib diisi.');
    header('Location: add.php');
    exit;
  } else {
    $stmt = $conn->prepare("INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)");
    $stmt->bind_param('ss', $nama, $desc);

    if ($stmt->execute()) {
      set_notification('success', '✅ Kategori baru berhasil ditambahkan!');
    } else {
      set_notification('error', '❌ Gagal menyimpan data kategori.');
    }

    header('Location: index.php');
    exit;
  }
}

// BARU include tampilan, setelah semua kemungkinan header selesai
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-tag-plus"></i> Tambah Kategori</h1>
        <p class="page-subtitle">Buat kategori baru untuk pengelompokan aset</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-form"></i> Form Tambah Kategori</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">Nama Kategori</label>
            <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Elektronik, Perabotan, dll" required>
          </div>

          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan kategori ini..."></textarea>
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
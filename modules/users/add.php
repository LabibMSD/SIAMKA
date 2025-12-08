<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include file logika (tidak menghasilkan output HTML)
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../includes/notification_helper.php';
include '../../config/config.php';
include '../../config/database.php';

// Batasi akses hanya admin
checkRole(['admin']);

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $no_telp = trim($_POST['no_telp']);
  $role = $_POST['role'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // Cek apakah email sudah terdaftar
  $check = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email' AND deleted_at IS NULL");

  if (mysqli_num_rows($check) > 0) {
    set_notification('error', '❌ Email sudah terdaftar. Gunakan email lain.');
    header('Location: add.php');
    exit;
  } else {
    $query = "INSERT INTO users (nama, email, no_telp, role, password) 
                  VALUES ('$nama','$email','$no_telp','$role','$password')";

    if (mysqli_query($conn, $query)) {
      set_notification('success', '✅ User baru berhasil ditambahkan!');
    } else {
      set_notification('error', '❌ Gagal menambahkan user.');
    }

    header('Location: index.php');
    exit;
  }
}

// Setelah semua logika selesai, baru tampilkan tampilan
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-user-plus"></i> Tambah User</h1>
        <p class="page-subtitle">Daftarkan pengguna baru ke dalam sistem</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-form"></i> Form Tambah User</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Masukkan alamat email" required>
          </div>

          <div class="form-group">
            <label class="form-label">No. Telp</label>
            <input type="text" name="no_telp" class="form-control" placeholder="Masukkan nomor telepon">
          </div>

          <div class="form-group">
            <label class="form-label">Role/Peran</label>
            <select name="role" class="form-control" required>
              <option value="">-- Pilih Role --</option>
              <option value="admin">Admin</option>
              <option value="manajemen">Manajemen</option>
              <option value="pengguna">Pengguna</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
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
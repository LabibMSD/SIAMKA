<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include file penting (yang tidak menghasilkan output)
include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../includes/notification_helper.php';
include '../../config/config.php';
include '../../config/database.php';

// Batasi akses hanya admin
checkRole(['admin']);

// Pastikan id_user ada
if (!isset($_GET['id_user'])) {
  die("❌ ID user tidak ditemukan.");
}

$id_user = $_GET['id_user'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id_user='$id_user' AND deleted_at IS NULL"));
if (!$user) {
  die("❌ Data user tidak ditemukan.");
}

// Proses form sebelum HTML ditampilkan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama  = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $role  = $_POST['role'];
  $password = trim($_POST['password'] ?? '');
  $confirm_password = trim($_POST['confirm_password'] ?? '');

  $errors = [];

  // Cek email unik
  $check = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email' AND id_user!='$id_user' AND deleted_at IS NULL");
  if (mysqli_num_rows($check) > 0) {
    $errors[] = '❌ Email sudah digunakan oleh user lain.';
  }

  // Validasi password jika diisi
  if (!empty($password)) {
    if (strlen($password) < 6) {
      $errors[] = '❌ Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
      $errors[] = '❌ Password dan konfirmasi password tidak cocok.';
    }
  }

  if (!empty($errors)) {
    foreach ($errors as $error) {
      set_notification('error', $error);
    }
  } else {
    // Update user data
    $update_fields = "nama='$nama', email='$email', role='$role'";
    if (!empty($password)) {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $update_fields .= ", password='$hashed_password'";
    }

    $update = mysqli_query($conn, "UPDATE users SET $update_fields WHERE id_user='$id_user'");
    if ($update) {
      set_notification('success', '✅ Data user berhasil diperbarui.');
    } else {
      set_notification('error', '❌ Gagal memperbarui data user.');
    }
  }

  // Redirect sebelum ada output HTML
  header('Location: index.php');
  exit;
}

// Baru tampilkan HTML setelah semua proses selesai
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-user-pen"></i> Edit User</h1>
        <p class="page-subtitle">Perbarui informasi pengguna</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-form"></i> Form Edit User</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']); ?>" placeholder="Masukkan nama lengkap" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" placeholder="Masukkan alamat email" required>
          </div>

          <div class="form-group">
            <label class="form-label">Role/Peran</label>
            <select name="role" class="form-control" required>
              <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
              <option value="manajemen" <?= $user['role'] == 'manajemen' ? 'selected' : ''; ?>>Manajemen</option>
              <option value="pengguna" <?= $user['role'] == 'pengguna' ? 'selected' : ''; ?>>Pengguna</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Password Baru (Opsional)</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password baru (minimal 6 karakter)">
            <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi password baru">
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

<script>
  // Simple password confirmation validation
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.querySelector('input[name="password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');

    form.addEventListener('submit', function(e) {
      if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Password dan konfirmasi password tidak cocok!');
        return false;
      }
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>
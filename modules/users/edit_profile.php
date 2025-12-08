<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();
include '../../includes/auth_check.php';
include '../../config/config.php';
include '../../config/database.php';

$id_user = $_SESSION['user_id'];
$message = "";

$result = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_user'");
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telp = trim($_POST['no_telp']);

    // 📸 Upload Foto Profil
    $foto_name = $user['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $upload_dir = '../../uploads/users/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES['foto']['name']);
        $target_path = $upload_dir . $file_name;
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed_ext)) {
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_path)) {
                // hapus foto lama jika bukan default
                if (!empty($user['foto']) && file_exists($upload_dir . $user['foto']) && $user['foto'] != 'default.png') {
                    unlink($upload_dir . $user['foto']);
                }

                mysqli_query($conn, "UPDATE users SET foto='$file_name' WHERE id_user='$id_user'");
                $_SESSION['foto'] = $file_name;
                $foto_name = $file_name;
            }
        } else {
            $_SESSION['flash'] = "⚠️ Format file tidak valid (Gunakan JPG, PNG, atau GIF).";
        }
    }

    // 📧 Update Data Profil
    $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email' AND id_user != '$id_user'");
    if (mysqli_num_rows($check_email) > 0) {
        $_SESSION['flash'] = "❌ Email sudah digunakan oleh pengguna lain.";
    } else {
        mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', no_telp='$no_telp' WHERE id_user='$id_user'");
        $_SESSION['nama'] = $nama;
        $_SESSION['flash'] = "✅ Profil berhasil diperbarui.";
    }

    // 🔒 Ganti Password (opsional)
    if (!empty($_POST['password_lama']) && !empty($_POST['password_baru'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        if (password_verify($password_lama, $user['password'])) {
            $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id_user='$id_user'");
            $_SESSION['flash'] .= "<br>🔒 Password berhasil diganti.";
        } else {
            $_SESSION['flash'] .= "<br>⚠️ Password lama salah.";
        }
    }

    header("Location: profile.php");
    exit;
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar.php'; ?>

<main class="main-content py-4 bg-light" style="min-height:100vh;">
  <div class="container">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
      <div class="card-header bg-dark text-white text-center">
        <h4 class="mb-0">Edit Profil</h4>
      </div>
      <div class="card-body">
        <?php if (!empty($_SESSION['flash'])): ?>
          <div class="alert alert-info"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <div class="text-center mb-3">
            <img id="preview-img" 
              src="<?= !empty($_SESSION['foto']) 
                ? BASE_URL . 'uploads/users/' . $_SESSION['foto'] 
                : BASE_URL . 'assets/img/default-user.png' ?>"
              alt="Foto Profil"
              class="rounded-circle border"
              width="120" height="120"
              style="object-fit: cover;">
            <div class="mt-2">
              <input type="file" id="foto" name="foto" accept="image/*" class="form-control form-control-sm" style="max-width:250px; margin:auto;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">No. Telepon</label>
            <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp']); ?>">
          </div>

          <hr>

          <h5 class="text-muted">Ubah Password</h5>

          <div class="mb-3">
            <label class="form-label">Password Lama</label>
            <input type="password" name="password_lama" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password_baru" class="form-control">
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-dark px-4">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<!-- 🧩 JS Preview Foto -->
<script>
const fotoInput = document.getElementById('foto');
const previewImg = document.getElementById('preview-img');

fotoInput.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(evt) {
      previewImg.src = evt.target.result; // tampilkan preview foto baru
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php include '../../includes/footer.php'; ?>

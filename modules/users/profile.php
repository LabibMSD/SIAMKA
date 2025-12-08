<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

include '../../includes/auth_check.php';
include '../../config/config.php';
include '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$id_user = $_SESSION['user_id'];

// Ambil data user (termasuk foto)
$query = "SELECT nama, email, no_telp, role, foto FROM users WHERE id_user = '$id_user'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>

<main class="main-content d-flex justify-content-center align-items-center" style="min-height: 100vh; background-color: #f8f9fa;">

  <div class="card shadow border-0 text-center p-4" style="max-width: 500px; width: 100%; border-radius: 16px;">
    <!-- Foto Profil -->
    <?php
    $foto_path = !empty($user['foto'])
        ? BASE_URL . 'uploads/users/' . $user['foto']
        : BASE_URL . 'assets/images/default-user.png';
    ?>
    <img src="<?= $foto_path; ?>"
         alt="Foto Profil"
         class="rounded-circle mx-auto mb-3"
         width="130" height="130"
         style="object-fit: cover; border: 4px solid gold; box-shadow: 0 0 10px rgba(218,165,32,0.4);">

    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['nama']); ?></h4>
    <small class="text-muted d-block mb-3"><?= strtoupper($user['role']); ?></small>

    <hr class="my-3">

    <div class="text-start px-4">
      <p class="mb-2"><i class="fa-solid fa-envelope text-primary me-2"></i> <?= htmlspecialchars($user['email']); ?></p>
      <p class="mb-2"><i class="fa-solid fa-phone text-success me-2"></i> <?= htmlspecialchars($user['no_telp']); ?></p>
    </div>

    <a href="edit_profile.php" class="btn btn-primary mt-3 px-4">
      <i class="fa-solid fa-pen me-1"></i> Edit Profil
    </a>
  </div>

  <!-- ✅ Flash Message (animasi toast) -->
  <?php if (!empty($_SESSION['flash'])): ?>
    <div id="toast" class="text-white fw-semibold" style="
      position: fixed;
      top: 20%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.9);
      background: #1e3a5f;
      padding: 14px 24px;
      border-radius: 12px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
      z-index: 9999;
      font-family: 'Segoe UI', sans-serif;
      font-size: 15px;
      text-align: center;
      opacity: 0;
      transition: all 0.5s ease;
    ">
      <?= $_SESSION['flash']; unset($_SESSION['flash']); ?>
    </div>

    <script>
      const toast = document.getElementById('toast');
      if (toast) {
        setTimeout(() => {
          toast.style.opacity = '1';
          toast.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 100);
        setTimeout(() => {
          toast.style.opacity = '0';
          toast.style.transform = 'translate(-50%, -50%) scale(0.9)';
          setTimeout(() => toast.remove(), 500);
        }, 2500);
      }
    </script>
  <?php endif; ?>

</main>

<?php include '../../includes/footer.php'; ?>

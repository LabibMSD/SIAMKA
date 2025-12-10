<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['pengguna', 'admin', 'manajemen']);

$id_user = $_SESSION['id_user'];

// Ambil id_aset dari URL jika ada
$id_aset_preset = isset($_GET['id_aset']) ? (int)$_GET['id_aset'] : null;

// Jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_aset = $_POST['id_aset'] ?? '';
  $deskripsi = trim($_POST['deskripsi'] ?? '');

  if (empty($id_aset) || empty($deskripsi)) {
    set_notification("Semua field wajib diisi!", "danger");
  } else {
    $stmt = $conn->prepare("
            INSERT INTO damage_reports (id_user, id_aset, tanggal_lapor, deskripsi, status)
            VALUES (?, ?, CURDATE(), ?, 'baru')
        ");
    $stmt->bind_param("iis", $id_user, $id_aset, $deskripsi);

    if ($stmt->execute()) {
      // Setelah laporan kerusakan tersimpan, otomatis tandai pinjaman terkait sebagai returned
      // Cari pinjaman aktif milik user untuk aset ini
      $loanStmt = $conn->prepare("SELECT id_peminjaman FROM loans WHERE id_user = ? AND id_aset = ? AND status IN ('approved','overdue') LIMIT 1");
      if ($loanStmt) {
        $loanStmt->bind_param('ii', $id_user, $id_aset);
        $loanStmt->execute();
        $loanRes = $loanStmt->get_result();
        if ($loanRow = $loanRes->fetch_assoc()) {
          $id_peminjaman = $loanRow['id_peminjaman'];

          // Update loan status menjadi returned
          $updLoan = $conn->prepare("UPDATE loans SET status = 'returned', returned_at = NOW() WHERE id_peminjaman = ? AND id_user = ?");
          if ($updLoan) {
            $updLoan->bind_param('ii', $id_peminjaman, $id_user);
            $updLoan->execute();
            $updLoan->close();
          }

          // Update status aset menjadi tersedia (atau atur sesuai kebijakan)
          $updAsset = $conn->prepare("UPDATE assets SET status = 'tersedia' WHERE id_aset = ?");
          if ($updAsset) {
            $updAsset->bind_param('i', $id_aset);
            $updAsset->execute();
            $updAsset->close();
          }
        }
        $loanStmt->close();
      }

      set_notification("Laporan kerusakan berhasil dikirim dan aset otomatis dikembalikan.", "success");
      header("Location: my_reports.php");
      exit;
    } else {
      set_notification("Gagal mengirim laporan: " . $conn->error, "danger");
    }
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
        <h1 class="page-title"><i class="fa-solid fa-screwdriver-wrench"></i> Lapor Kerusakan Aset</h1>
        <p class="page-subtitle">Laporkan kerusakan pada aset yang sedang Anda pinjam</p>
      </div>
    </div>

    <!-- FORM CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-plus"></i> Form Laporan Kerusakan</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <form method="POST">
          <div class="grid grid-2 gap-4">
            <div class="form-group">
              <label for="id_aset" class="form-label">Pilih Aset (yang sedang dipinjam)</label>
              <select name="id_aset" id="id_aset" class="form-control" required>
                <option value="">-- Pilih Aset --</option>
                <?php
                // 🔹 Hanya ambil aset yang sedang dipinjam user dengan status approved
                $query = "
                    SELECT a.id_aset, a.nama_aset, a.kode_aset
                    FROM loans l
                    JOIN assets a ON l.id_aset = a.id_aset
                    WHERE l.id_user = ? AND l.status = 'approved'
                    ORDER BY a.nama_aset ASC
                ";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_user);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                  while ($row = $result->fetch_assoc()):
                ?>
                    <option value="<?= $row['id_aset'] ?>" <?= ($id_aset_preset === $row['id_aset']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($row['nama_aset']) ?> (<?= htmlspecialchars($row['kode_aset']) ?>)
                    </option>
                  <?php
                  endwhile;
                else:
                  ?>
                  <option value="">Tidak ada aset yang sedang dipinjam</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="form-group">
              <!-- Empty column for spacing -->
            </div>
          </div>

          <div class="form-group">
            <label for="deskripsi" class="form-label">Deskripsi Kerusakan</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="6" placeholder="Jelaskan kondisi atau kerusakan aset secara detail..." required></textarea>
          </div>

          <div class="form-actions">
            <a href="my_reports.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-list"></i> Lihat Laporan Saya
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-paper-plane"></i> Kirim Laporan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
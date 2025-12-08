<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['pengguna', 'manajemen']);

$id_user = $_SESSION['id_user'];

// Ambil laporan langsung dari database (tanpa cache)
$query = "
  SELECT 
      d.id_laporan,
      d.id_aset,
      d.tanggal_lapor,
      d.deskripsi,
      d.status,
      a.nama_aset,
      a.kode_aset
  FROM damage_reports d
  LEFT JOIN assets a ON d.id_aset = a.id_aset
  WHERE d.id_user = ?
  ORDER BY d.tanggal_lapor DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-clipboard-list"></i> Laporan Kerusakan Saya</h1>
        <p class="page-subtitle">Pantau status laporan kerusakan aset yang telah Anda ajukan</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Laporan Saya</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-dark">
                <tr>
                  <th>No</th>
                  <th>Kode Aset</th>
                  <th>Nama Aset</th>
                  <th>Tanggal Lapor</th>
                  <th>Deskripsi</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1;
                while ($row = $result->fetch_assoc()): ?>
                  <?php
                  $status_class = [
                    'baru' => 'warning',
                    'diproses' => 'primary',
                    'selesai' => 'success'
                  ][$row['status']] ?? 'secondary';
                  ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['kode_aset'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_aset'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['tanggal_lapor']) ?></td>
                    <td class="text-start text-truncate" title="<?= htmlspecialchars($row['deskripsi']) ?>">
                      <?= nl2br(htmlspecialchars(substr($row['deskripsi'], 0, 100))) ?>
                      <?= strlen($row['deskripsi']) > 100 ? '...' : '' ?>
                    </td>
                    <td>
                      <span class="badge bg-<?= $status_class ?>">
                        <?= ucfirst($row['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-circle-info"></i>
            <p>Belum ada laporan kerusakan aset.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
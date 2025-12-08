<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// === Ambil Data Jadwal Maintenance ===
$query = "
  SELECT 
    ms.id_jadwal,
    ms.tanggal_jadwal,
    ms.keterangan,
    ms.status,
    a.id_aset,
    a.nama_aset,
    a.kode_aset,
    c.nama_kategori AS kategori,
    u.nama AS petugas
  FROM maintenance_schedule ms
  LEFT JOIN assets a ON ms.id_aset = a.id_aset
  LEFT JOIN categories c ON a.id_kategori = c.id_kategori
  LEFT JOIN users u ON ms.id_petugas = u.id_user
  WHERE a.deleted_at IS NULL
  ORDER BY ms.tanggal_jadwal DESC
";

$result = $conn->query($query);
$schedules = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-calendar-days"></i> Jadwal Maintenance Aset</h1>
        <p class="page-subtitle">Kelola jadwal pemeliharaan semua aset</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list-check"></i> Daftar Jadwal</h3>
        <div class="card-actions">
          <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manajemen'): ?>
            <a href="add_schedule.php" class="btn btn-primary">
              <i class="fa-solid fa-plus"></i> Tambah Jadwal
            </a>
          <?php endif; ?>
          <a href="history.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-history"></i> Riwayat
          </a>
        </div>
      </div>

      <div class="card-body">
        <?php display_notification(); ?>

        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Aset</th>
                <th>Kategori</th>
                <th>Petugas</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($schedules) > 0): ?>
                <?php $no = 1;
                foreach ($schedules as $row): ?>
                  <?php
                  $badge_status = match ($row['status']) {
                    'selesai' => 'success',
                    'dibatalkan' => 'danger',
                    default => 'warning',
                  };
                  ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_aset']); ?></strong></td>
                    <td><?= htmlspecialchars($row['kategori']); ?></td>
                    <td><?= htmlspecialchars($row['petugas'] ?? '-'); ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_jadwal'])); ?></td>
                    <td class="text-truncate" title="<?= htmlspecialchars($row['keterangan'] ?? '') ?>"><?= htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
                    <td>
                      <span class="badge bg-<?= $badge_status; ?>"><?= ucfirst($row['status']); ?></span>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'terjadwal'): ?>
                        <a href="edit_schedule.php?id=<?= $row['id_jadwal']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="complete_schedule.php?id=<?= $row['id_jadwal']; ?>"
                          class="btn btn-sm btn-outline-success"
                          onclick="return confirm('Tandai sebagai selesai?');" title="Selesai">
                          <i class="fa-solid fa-check"></i>
                        </a>
                        <a href="cancel_schedule.php?id=<?= $row['id_jadwal']; ?>"
                          class="btn btn-sm btn-outline-danger"
                          onclick="return confirm('Batalkan jadwal?');" title="Batalkan">
                          <i class="fa-solid fa-xmark"></i>
                        </a>
                      <?php else: ?>
                        <span class="text-muted text-sm">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8">
                    <div class="empty-state">
                      <i class="fa-solid fa-inbox"></i>
                      <p>Tidak ada jadwal maintenance</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
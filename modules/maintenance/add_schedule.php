<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// 🧩 Proses tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_petugas      = $_SESSION['user_id']; // ambil ID dari user login
  $id_aset         = $_POST['id_aset'];
  $tanggal_jadwal  = $_POST['tanggal_jadwal'];
  $keterangan      = $_POST['keterangan'];

  $query = "
    INSERT INTO maintenance_schedule (id_petugas, id_aset, tanggal_jadwal, keterangan, status)
    VALUES ('$id_petugas', '$id_aset', '$tanggal_jadwal', '$keterangan', 'terjadwal')
  ";
  $result = mysqli_query($conn, $query);

  if ($result) {
    $_SESSION['success_message'] = "✅ Jadwal maintenance berhasil ditambahkan.";
    header("Location: add_schedule.php");
    exit;
  } else {
    $_SESSION['error_message'] = "❌ Gagal menambahkan jadwal: " . mysqli_error($conn);
  }
}

// Ambil data aset yang masih aktif
$aset = mysqli_query($conn, "SELECT id_aset, nama_aset FROM assets WHERE status != 'maintenance'");

// 🔍 Ambil semua aset + tanggal terakhir maintenance
$query = "
    SELECT 
        a.id_aset,
        a.nama_aset,
        a.kode_aset,
        c.nama_kategori AS kategori,
        a.status,
        a.kondisi,
        MAX(mh.tanggal_perawatan) AS terakhir_maintenance
    FROM assets a
    LEFT JOIN categories c ON a.id_kategori = c.id_kategori
    LEFT JOIN maintenance_history mh ON a.id_aset = mh.id_aset
    WHERE a.deleted_at IS NULL
    GROUP BY a.id_aset
    ORDER BY a.nama_aset ASC
";

$result = $conn->query($query);

// Hitung jumlah aset yang lewat 30 hari maintenance
$butuh_maintenance = 0;
$data_aset = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $hari_terakhir = $row['terakhir_maintenance'];
    if ($hari_terakhir) {
      $selisih = (new DateTime())->diff(new DateTime($hari_terakhir))->days;
    } else {
      $selisih = 999;
    }
    if ($selisih > 30) $butuh_maintenance++;
    $data_aset[] = $row + ['selisih' => $selisih];
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
        <h1 class="page-title"><i class="fa-solid fa-calendar-check"></i> Jadwal Maintenance Aset</h1>
        <p class="page-subtitle">Kelola jadwal pemeliharaan dan perawatan aset</p>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Aset & Maintenance</h3>
        <div class="card-actions">
          <a href="add_schedule_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Tambah Jadwal
          </a>
          <a href="schedule.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
        </div>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <!-- STATUS PEMELIHARAAN -->
        <?php if ($butuh_maintenance > 0): ?>
          <div class="alert alert-warning d-flex align-items-center">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <div><strong><?= $butuh_maintenance; ?></strong> aset perlu maintenance (lebih dari 30 hari)</div>
          </div>
        <?php else: ?>
          <div class="alert alert-success d-flex align-items-center">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div>Semua aset dalam kondisi baik dan maintenance masih terjaga</div>
          </div>
        <?php endif; ?>

        <!-- TABEL DATA -->
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Kondisi</th>
                <th>Terakhir Maintenance</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($data_aset) > 0): ?>
                <?php $no = 1;
                foreach ($data_aset as $row): ?>
                  <?php
                  $selisih = $row['selisih'];
                  $status = $row['status'];
                  $kondisi = $row['kondisi'];

                  $badge_status = match ($status) {
                    'tersedia' => 'bg-success',
                    'dipinjam' => 'bg-secondary',
                    'maintenance' => 'bg-warning',
                    default => 'bg-light',
                  };

                  $badge_kondisi = match ($kondisi) {
                    'baik' => 'bg-success',
                    'rusak' => 'bg-danger',
                    'hilang' => 'bg-dark',
                    default => 'bg-secondary',
                  };
                  ?>

                  <tr>
                    <td><?= $no++; ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_aset']); ?></strong></td>
                    <td><?= htmlspecialchars($row['kategori']); ?></td>
                    <td><span class="badge <?= $badge_status; ?>"><?= ucfirst($status); ?></span></td>
                    <td><span class="badge <?= $badge_kondisi; ?>"><?= ucfirst($kondisi); ?></span></td>
                    <td>
                      <?php if ($row['terakhir_maintenance']): ?>
                        <?= date('d M Y', strtotime($row['terakhir_maintenance'])); ?>
                        <?php if ($selisih > 30): ?>
                          <span class="text-danger fw-semibold">(<?= $selisih; ?> hari lalu)</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-danger">Belum Pernah</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($status !== 'dipinjam'): ?>
                        <a href="add_schedule_form.php?id_aset=<?= $row['id_aset']; ?>" class="btn btn-sm btn-primary">
                          <i class="fa-solid fa-screwdriver-wrench"></i> Jadwalkan
                        </a>
                      <?php else: ?>
                        <span class="text-muted">Sedang Dipinjam</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fa-solid fa-inbox"></i>
                      <p>Tidak ada aset ditemukan</p>
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
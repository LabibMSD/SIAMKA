  <?php
  define("SECURE", true);
  if (session_status() === PHP_SESSION_NONE) session_start();

  require_once '../../includes/auth_check.php';
  require_once '../../includes/role_check.php';
  require_once '../../config/config.php';
  require_once '../../config/database.php';
  require_once '../../includes/notification_helper.php';

  checkRole(['pengguna', 'staff', 'manajemen']);

  // 🔹 Ambil semua peminjaman milik user
  $id_user = $_SESSION['id_user'];

  // Cek pinjaman yang hampir jatuh tempo (2 hari lagi)
  $notif_query = "
    SELECT COUNT(*) AS count_due
    FROM loans
    WHERE id_user = ?
    AND status = 'approved'
    AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)
  ";
  $notif_stmt = $conn->prepare($notif_query);
  $notif_stmt->bind_param("i", $id_user);
  $notif_stmt->execute();
  $notif_result = $notif_stmt->get_result()->fetch_assoc();
  $count_due = $notif_result['count_due'] ?? 0;

  // Ambil semua pinjaman user
  $query = "
      SELECT 
          l.*, 
          a.nama_aset, 
          a.kode_aset, 
          a.lokasi, 
          a.kondisi
      FROM loans l
      LEFT JOIN assets a ON l.id_aset = a.id_aset
      WHERE l.id_user = ?
      ORDER BY l.created_at DESC
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
          <h1 class="page-title"><i class="fa-solid fa-clipboard-check"></i> Peminjaman Saya</h1>
          <p class="page-subtitle">Kelola dan pantau semua peminjaman aset Anda di sini.</p>
        </div>
      </div>

      <div class="card card-shadow">
        <div class="card-header">
          <h3 class="card-title"><i class="fa-solid fa-list"></i> Riwayat Peminjaman</h3>
        </div>
        <div class="card-body">
          <?php display_notification(); ?>

          <?php if ($count_due > 0): ?>
            <div class="alert alert-warning alert-dismissible mb-3" role="alert">
              <i class="fa-solid fa-exclamation-triangle me-2"></i>
              Anda memiliki <strong><?= $count_due ?></strong> peminjaman yang akan jatuh tempo dalam 2 hari!
              <button type="button" class="alert-close" aria-label="Tutup" onclick="this.parentElement.style.display='none';"></button>
            </div>
          <?php endif; ?>

          <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Kode Aset</th>
                    <th>Nama Aset</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1;
                  while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $status = $row['status'];
                    $today = date('Y-m-d');
                    $end_date = $row['end_date'];

                    // Deteksi status terlambat
                    if ($status === 'approved' && $end_date < $today) {
                      $status_display = 'overdue';
                      $badge_class = 'danger';
                      $label = 'overdue';
                    } else {
                      $badge_class = [
                        'pending' => 'warning',
                        'approved' => 'primary',
                        'rejected' => 'danger',
                        'returned' => 'success'
                      ][$status] ?? 'secondary';
                      $status_display = $status;
                      $label = ucfirst($status);
                    }
                    ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['kode_aset']) ?></td>
                      <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                      <td><?= htmlspecialchars($row['start_date']) ?></td>
                      <td><?= htmlspecialchars($row['end_date']) ?></td>
                      <td><span class="badge bg-<?= $badge_class ?>"><?= $label ?></span></td>
                      <td>
                        <?php if ($status_display === 'pending'): ?>
                          <button class="btn btn-sm btn-outline-secondary" disabled>Menunggu Persetujuan</button>

                        <?php elseif ($status_display === 'approved'): ?>
                          <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#returnModal" data-id="<?php echo htmlspecialchars($row['id_peminjaman']); ?>">
                            <i class="fa-solid fa-undo"></i> Kembalikan
                          </button>

                        <?php elseif ($status_display === 'overdue'): ?>
                          <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#returnModal" data-id="<?php echo htmlspecialchars($row['id_peminjaman']); ?>">
                            <i class="fa-solid fa-undo"></i> Kembalikan
                          </button>

                        <?php elseif ($status_display === 'rejected'): ?>
                          <button class="btn btn-sm btn-outline-danger" disabled>Ditolak</button>

                        <?php elseif ($status_display === 'returned'): ?>
                          <button class="btn btn-sm btn-outline-dark" disabled>Selesai</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-inbox"></i>
              <p>Belum ada riwayat peminjaman</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal for Return Feedback -->
  <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="returnModalLabel">Kembalikan Aset</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="returnForm" method="POST" action="return_asset.php">
          <div class="modal-body">
            <p>Apakah Anda yakin ingin mengembalikan aset ini?</p>
            <div class="mb-3">
              <label for="feedback" class="form-label">Feedback (Opsional)</label>
              <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Berikan feedback tentang kondisi aset yang dipinjam..."></textarea>
            </div>
            <input type="hidden" id="id_peminjaman" name="id_peminjaman" value="">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Kembalikan Aset</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Handle modal data
    const returnModal = document.getElementById('returnModal');
    returnModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const idPeminjaman = button.getAttribute('data-id');
      const idInput = document.getElementById('id_peminjaman');
      idInput.value = idPeminjaman;
    });
  </script>

  <?php include '../../includes/footer.php'; ?>
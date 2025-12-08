<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['pengguna', 'admin', 'manajemen']);

$id_user = $_SESSION['id_user'];

// === Card Statistik User ===
$peminjamanAktif = $conn->query("
  SELECT COUNT(*) AS total
  FROM loans
  WHERE id_user = $id_user AND status = 'approved'
")->fetch_assoc()['total'] ?? 0;

$totalHistory = $conn->query("
  SELECT COUNT(*) AS total
  FROM loans
  WHERE id_user = $id_user
")->fetch_assoc()['total'] ?? 0;

$laporanKerusakan = $conn->query("
  SELECT COUNT(*) AS total
  FROM damage_reports
  WHERE id_user = $id_user
")->fetch_assoc()['total'] ?? 0;

$peminjamanSelesai = $conn->query("
  SELECT COUNT(*) AS total
  FROM loans
  WHERE id_user = $id_user AND status = 'returned'
")->fetch_assoc()['total'] ?? 0;

// === Aset Yang Sedang Dipinjam dengan Countdown ===
$asetDipinjam = [];
$result = $conn->query("
  SELECT 
    l.id_peminjaman,
    l.start_date,
    l.end_date,
    l.status,
    a.id_aset,
    a.kode_aset,
    a.nama_aset,
    a.foto,
    c.nama_kategori,
    DATEDIFF(l.end_date, CURDATE()) AS days_remaining,
    TIMESTAMPDIFF(HOUR, NOW(), l.end_date) AS hours_remaining
  FROM loans l
  JOIN assets a ON l.id_aset = a.id_aset
  JOIN categories c ON a.id_kategori = c.id_kategori
  WHERE l.id_user = $id_user 
    AND l.status = 'approved'
  ORDER BY l.end_date ASC
");
while ($row = $result->fetch_assoc()) {
  $asetDipinjam[] = $row;
}

// === Notifikasi Deadline < 3 Hari ===
$notifikasiDeadline = [];
foreach ($asetDipinjam as $item) {
  if ($item['days_remaining'] <= 3 && $item['days_remaining'] >= 0) {
    $notifikasiDeadline[] = $item;
  }
}

// === History Peminjaman Terbaru ===
$historyPeminjaman = [];
$result = $conn->query("
  SELECT 
    l.id_peminjaman,
    l.start_date,
    l.end_date,
    l.returned_at,
    l.status,
    a.kode_aset,
    a.nama_aset,
    a.foto,
    c.nama_kategori
  FROM loans l
  JOIN assets a ON l.id_aset = a.id_aset
  JOIN categories c ON a.id_kategori = c.id_kategori
  WHERE l.id_user = $id_user
  ORDER BY l.created_at DESC
  LIMIT 5
");
while ($row = $result->fetch_assoc()) {
  $historyPeminjaman[] = $row;
}



// === Laporan Kerusakan User ===
$laporanKerusakanList = [];
$result = $conn->query("
  SELECT 
    dr.id_laporan,
    dr.deskripsi,
    dr.status,
    dr.tanggal_lapor,
    a.kode_aset,
    a.nama_aset,
    a.foto
  FROM damage_reports dr
  JOIN assets a ON dr.id_aset = a.id_aset
  WHERE dr.id_user = $id_user
  ORDER BY dr.tanggal_lapor DESC
  LIMIT 5
");
while ($row = $result->fetch_assoc()) {
  $laporanKerusakanList[] = $row;
}

// === Chart Data: Aktivitas Peminjaman Per Bulan (6 bulan terakhir) ===
$bulanLabels = [];
$peminjamanData = [];
$result = $conn->query("
  SELECT 
    DATE_FORMAT(start_date, '%b %Y') AS bulan,
    COUNT(*) AS total
  FROM loans
  WHERE id_user = $id_user
    AND start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(start_date, '%Y-%m')
  ORDER BY start_date ASC
");
while ($row = $result->fetch_assoc()) {
  $bulanLabels[] = $row['bulan'];
  $peminjamanData[] = $row['total'];
}

// === Chart Data: Status Peminjaman ===
$statusLabels = [];
$statusData = [];
$statusColors = [
  'pending' => '#fbbf24',
  'approved' => '#3b82f6',
  'returned' => '#10b981',
  'rejected' => '#ef4444'
];
$result = $conn->query("
  SELECT 
    status,
    COUNT(*) AS total
  FROM loans
  WHERE id_user = $id_user
  GROUP BY status
");
while ($row = $result->fetch_assoc()) {
  $statusLabels[] = ucfirst($row['status']);
  $statusData[] = $row['total'];
}

?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title">Dashboard Saya</h1>
        <p class="page-subtitle">Selamat datang kembali, <?= htmlspecialchars($_SESSION['nama']) ?>! 👋 Kelola peminjaman dan laporan Anda di sini.</p>
      </div>
      <div class="page-header-meta">
        <div class="meta-item">
          <span class="meta-label">Hari ini</span>
          <span class="meta-value"><?= date('d M Y, H:i') ?> WIB</span>
        </div>
      </div>
    </div>

    <!-- DEADLINE WARNING NOTIFICATION -->
    <?php if (count($notifikasiDeadline) > 0): ?>
      <div class="alert alert-warning alert-dismissible" role="alert">
        <div class="alert-content">
          <h5 class="alert-title">
            <i class="fa-solid fa-bell"></i> Peringatan Deadline!
          </h5>
          <p class="alert-message">Anda memiliki <strong><?= count($notifikasiDeadline) ?> peminjaman</strong> yang akan jatuh tempo dalam 3 hari:</p>
          <ul class="alert-list">
            <?php foreach ($notifikasiDeadline as $notif): ?>
              <li>
                <strong><?= htmlspecialchars($notif['nama_aset']) ?></strong>
                - Deadline: <?= date('d/m/Y H:i', strtotime($notif['end_date'])) ?>
                <?php if ($notif['days_remaining'] == 0): ?>
                  <span class="badge badge-danger">HARI INI!</span>
                <?php else: ?>
                  <span class="badge badge-warning"><?= $notif['days_remaining'] ?> hari lagi</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <button type="button" class="alert-close" aria-label="Tutup" onclick="this.parentElement.style.display='none';"></button>
      </div>
    <?php endif; ?>

    <!-- STAT CARDS GRID -->
    <div class="stat-cards-grid">
      <!-- Active Loans Card -->
      <div class="stat-card stat-card-loans">
        <div class="stat-card-icon">
          <i class="fa-solid fa-clipboard-list"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Peminjaman Aktif</span>
          <span class="stat-value"><?= $peminjamanAktif ?></span>
          <span class="stat-footer">Sedang dipinjam</span>
        </div>
      </div>

      <!-- Total History Card -->
      <div class="stat-card stat-card-history">
        <div class="stat-card-icon">
          <i class="fa-solid fa-history"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Total History</span>
          <span class="stat-value"><?= $totalHistory ?></span>
          <span class="stat-footer"><?= $peminjamanSelesai ?> selesai</span>
        </div>
      </div>

      <!-- Damage Reports Card -->
      <div class="stat-card stat-card-damage">
        <div class="stat-card-icon">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Laporan Kerusakan</span>
          <span class="stat-value"><?= $laporanKerusakan ?></span>
          <span class="stat-footer">Total laporan</span>
        </div>
      </div>

      <!-- Available Assets Card -->
      <div class="stat-card stat-card-assets">
        <div class="stat-card-icon">
          <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Aset Tersedia</span>
          <span class="stat-value">
            <?php
            $tersedia = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE deleted_at IS NULL AND status='tersedia'")->fetch_assoc()['total'];
            echo $tersedia;
            ?>
          </span>
          <span class="stat-footer">Siap dipinjam</span>
        </div>
      </div>
    </div>

    <!-- ACTIVE LOANS SECTION -->
    <div class="card card-shadow">
      <div class="card-header">
        <div class="card-header-content">
          <h3 class="card-title">
            <i class="fa-solid fa-clipboard-list"></i> Aset Yang Sedang Dipinjam
          </h3>
        </div>
        <span class="badge badge-primary"><?= count($asetDipinjam) ?> Item</span>
      </div>
      <div class="card-body">
        <?php if (count($asetDipinjam) > 0): ?>
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Aset</th>
                  <th>Kategori</th>
                  <th>Tanggal Pinjam</th>
                  <th>Deadline</th>
                  <th>Countdown</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1;
                foreach ($asetDipinjam as $item): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td>
                      <div class="d-flex align-items-center">
                        <?php if ($item['foto']): ?>
                          <img src="../../assets/uploads/assets/<?= htmlspecialchars($item['foto']) ?>"
                            alt="Foto"
                            class="me-3"
                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                          <div class="me-3 d-flex align-items-center justify-content-center bg-secondary text-white"
                            style="width: 50px; height: 50px; border-radius: 8px;">
                            <i class="bi bi-image"></i>
                          </div>
                        <?php endif; ?>
                        <div>
                          <div class="fw-bold"><?= htmlspecialchars($item['nama_aset']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($item['kode_aset']) ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-info">
                        <?= htmlspecialchars($item['nama_kategori']) ?>
                      </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($item['start_date'])) ?></td>
                    <td>
                      <?= date('d/m/Y', strtotime($item['end_date'])) ?><br>
                      <small class="text-muted"><?= date('', strtotime($item['end_date'])) ?> </small>
                    </td>
                    <td>
                      <?php
                      $days = $item['days_remaining'];
                      $hours = $item['hours_remaining'] % 24;

                      if ($days < 0) {
                        echo '<div class="text-danger fw-bold">';
                        echo '<i class="bi bi-exclamation-circle"></i> TERLAMBAT<br>';
                        echo '<small>' . abs($days) . ' hari</small>';
                        echo '</div>';
                      } elseif ($days == 0) {
                        if ($hours > 0) {
                          echo '<div class="text-warning fw-bold">';
                          echo '<i class="bi bi-hourglass-split"></i> HARI INI<br>';
                          echo '<small>' . $hours . ' jam lagi</small>';
                          echo '</div>';
                        } else {
                          echo '<div class="text-danger fw-bold">';
                          echo '<i class="bi bi-alarm"></i> SEGERA!';
                          echo '</div>';
                        }
                      } elseif ($days <= 3) {
                        echo '<div class="text-warning fw-bold">';
                        echo '<i class="bi bi-clock"></i> ' . $days . ' hari lagi<br>';
                        echo '<small>' . $hours . ' jam</small>';
                        echo '</div>';
                      } else {
                        echo '<div class="text-success">';
                        echo '<i class="bi bi-check-circle"></i> ' . $days . ' hari lagi';
                        echo '</div>';
                      }
                      ?>
                    </td>
                    <td>
                      <span class="badge bg-primary">Dipinjam</span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>Anda tidak memiliki peminjaman aktif saat ini</p>
            <a href="../../modules/loans/available.php" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-plus"></i> Ajukan Peminjaman
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- STATUS CHART -->
    <div class="charts-grid">
      <!-- Status Chart -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-pie-chart"></i> Status Peminjaman Saya
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($statusData) > 0): ?>
            <canvas id="statusChart" style="max-height: 300px;"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-chart-pie"></i>
              <p>Belum ada data</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- HISTORY & REPORTS -->
    <div class="charts-grid">
      <!-- History Section -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-clock-rotate-left"></i> History Peminjaman
            </h3>
          </div>
          <a href="../../modules/loans/my_loans.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div class="card-body">
          <?php if (count($historyPeminjaman) > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Aset</th>
                    <th>Periode</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($historyPeminjaman as $history): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if ($history['foto']): ?>
                            <img src="../../assets/uploads/assets/<?= htmlspecialchars($history['foto']) ?>"
                              alt="Foto"
                              class="me-2"
                              style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px;">
                          <?php else: ?>
                            <div class="me-2 d-flex align-items-center justify-content-center bg-light"
                              style="width: 35px; height: 35px; border-radius: 4px;">
                              <i class="fa-solid fa-image"></i>
                            </div>
                          <?php endif; ?>
                          <div>
                            <div class="fw-bold small"><?= htmlspecialchars($history['nama_aset']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($history['kode_aset']) ?> • <?= htmlspecialchars($history['nama_kategori']) ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <small class="text-muted">
                          <?= date('d/m/Y', strtotime($history['start_date'])) ?> -
                          <?= date('d/m/Y', strtotime($history['end_date'])) ?>
                        </small>
                      </td>
                      <td class="text-center">
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        switch ($history['status']) {
                          case 'pending':
                            $statusClass = 'warning';
                            $statusText = 'Pending';
                            break;
                          case 'approved':
                            $statusClass = 'primary';
                            $statusText = 'Dipinjam';
                            break;
                          case 'returned':
                            $statusClass = 'success';
                            $statusText = 'Dikembalikan';
                            break;
                          case 'rejected':
                            $statusClass = 'danger';
                            $statusText = 'Ditolak';
                            break;
                        }
                        ?>
                        <span class="badge bg-<?= $statusClass ?>">
                          <?= $statusText ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-inbox"></i>
              <p>Belum ada history peminjaman</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Damage Reports -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-file-lines"></i> Laporan Kerusakan Saya
            </h3>
          </div>
          <a href="../../modules/damage/my_reports.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
        <div class="card-body">
          <?php if (count($laporanKerusakanList) > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Aset</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($laporanKerusakanList as $laporan): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <?php if ($laporan['foto']): ?>
                            <img src="../../assets/uploads/assets/<?= htmlspecialchars($laporan['foto']) ?>"
                              alt="Foto"
                              class="me-2"
                              style="width: 35px; height: 35px; object-fit: cover; border-radius: 4px;">
                          <?php else: ?>
                            <div class="me-2 d-flex align-items-center justify-content-center bg-light"
                              style="width: 35px; height: 35px; border-radius: 4px;">
                              <i class="fa-solid fa-image"></i>
                            </div>
                          <?php endif; ?>
                          <div>
                            <div class="fw-bold small"><?= htmlspecialchars($laporan['nama_aset']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($laporan['kode_aset']) ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <p class="mb-0 small">
                          <?= htmlspecialchars(substr($laporan['deskripsi'], 0, 60)) ?>
                          <?= strlen($laporan['deskripsi']) > 60 ? '...' : '' ?>
                        </p>
                        <small class="text-muted">
                          <i class="fa-solid fa-calendar"></i>
                          <?= date('d/m/Y', strtotime($laporan['tanggal_lapor'])) ?>
                        </small>
                      </td>
                      <td class="text-center">
                        <?php
                        $statusClass = $laporan['status'] === 'selesai' ? 'success' : ($laporan['status'] === 'diproses' ? 'warning' : 'secondary');
                        $statusText = ucfirst($laporan['status']);
                        ?>
                        <span class="badge bg-<?= $statusClass ?>">
                          <?= $statusText ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-clipboard-check"></i>
              <p class="mb-2">Belum ada laporan kerusakan</p>
              <a href="../../modules/damage/report.php" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-plus"></i> Buat Laporan
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ACTIVITY CHART -->
    <div class="card card-shadow">
      <div class="card-header">
        <div class="card-header-content">
          <h3 class="card-title">
            <i class="fa-solid fa-chart-line"></i> Aktivitas Peminjaman (6 Bulan Terakhir)
          </h3>
        </div>
      </div>
      <div class="card-body">
        <?php if (count($peminjamanData) > 0): ?>
          <canvas id="activityChart" style="max-height: 350px;"></canvas>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-bar-chart"></i>
            <p>Belum ada aktivitas peminjaman dalam 6 bulan terakhir</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Chart 1: Status Peminjaman (Doughnut)
  <?php if (count($statusData) > 0): ?>
    const ctxStatus = document.getElementById('statusChart');
    new Chart(ctxStatus, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
          data: <?= json_encode($statusData) ?>,
          backgroundColor: ['#ffc107', '#0f5a92', '#28a745', '#dc3545'],
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: {
                size: 11
              },
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            titleFont: {
              size: 13
            },
            bodyFont: {
              size: 12
            },
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                let value = context.parsed || 0;
                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                let percentage = ((value / total) * 100).toFixed(1);
                return label + ': ' + value + ' (' + percentage + '%)';
              }
            }
          }
        }
      }
    });
  <?php endif; ?>

  // Chart 2: Activity Chart (Line)
  <?php if (count($peminjamanData) > 0): ?>
    const ctxActivity = document.getElementById('activityChart');
    new Chart(ctxActivity, {
      type: 'line',
      data: {
        labels: <?= json_encode($bulanLabels) ?>,
        datasets: [{
          label: 'Jumlah Peminjaman',
          data: <?= json_encode($peminjamanData) ?>,
          borderColor: '#0f5a92',
          backgroundColor: 'rgba(15, 90, 146, 0.05)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 6,
          pointHoverRadius: 8,
          pointBackgroundColor: '#0f5a92',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointHoverBackgroundColor: '#0f5a92',
          pointHoverBorderColor: '#fff',
          pointHoverBorderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            titleFont: {
              size: 14
            },
            bodyFont: {
              size: 13
            },
            displayColors: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: {
                size: 11
              }
            },
            grid: {
              color: 'rgba(0,0,0,0.05)',
              drawBorder: false
            }
          },
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              font: {
                size: 11
              }
            }
          }
        }
      }
    });
  <?php endif; ?>

  // Auto-dismiss alert after 10 seconds
  <?php if (count($notifikasiDeadline) > 0): ?>
    setTimeout(function() {
      const alert = document.querySelector('.alert-warning');
      if (alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }
    }, 10000);
  <?php endif; ?>

  // Add hover effect to cards
  document.querySelectorAll('.hover-shadow').forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px)';
      this.style.boxShadow = '0 0.5rem 1rem rgba(0,0,0,0.15)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '';
    });
  });
</script>

<style>
  .hover-shadow {
    transition: all 0.3s ease;
  }

  .hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
  }

  .list-group-item {
    border-left: 0;
    border-right: 0;
    border-radius: 0 !important;
  }

  .list-group-item:first-child {
    border-top: 0;
  }

  .list-group-item:last-child {
    border-bottom: 0;
  }

  .card-header {
    border-bottom: 2px solid #f0f0f0;
  }
</style>

<?php require_once '../../includes/footer.php'; ?>
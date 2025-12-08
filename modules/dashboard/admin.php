<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['admin']);

// === Kartu Statistik ===
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$totalAssets = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE deleted_at IS NULL")->fetch_assoc()['total'] ?? 0;
$peminjamanAktif = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status='approved'")->fetch_assoc()['total'] ?? 0;
$damagePending = $conn->query("SELECT COUNT(*) AS total FROM damage_reports WHERE status='diproses'")->fetch_assoc()['total'] ?? 0;

// === Statistik Tambahan ===
$asetTersedia = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE deleted_at IS NULL AND status='tersedia'")->fetch_assoc()['total'] ?? 0;
$maintenanceScheduled = $conn->query("
  SELECT COUNT(*) AS total
  FROM maintenance_schedule
  WHERE status = 'terjadwal'
")->fetch_assoc()['total'] ?? 0;


// === Chart Data: Peminjaman per Bulan (12 bulan terakhir) ===
$peminjamanData = [];
$bulanLabels = [];
$result = $conn->query("
  SELECT 
    DATE_FORMAT(start_date, '%Y-%m') AS periode,
    DATE_FORMAT(start_date, '%b %Y') AS label,
    COUNT(*) AS total
  FROM loans
  WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY periode
  ORDER BY periode ASC
");
while ($row = $result->fetch_assoc()) {
  $bulanLabels[] = $row['label'];
  $peminjamanData[] = $row['total'];
}

// === Chart Data: Aset per Kategori ===
$kategoriLabels = [];
$kategoriData = [];
$result = $conn->query("
  SELECT c.nama_kategori, COUNT(a.id_aset) AS total
  FROM assets a
  JOIN categories c ON a.id_kategori = c.id_kategori
  WHERE a.deleted_at IS NULL
  GROUP BY c.nama_kategori
  ORDER BY total DESC
");
while ($row = $result->fetch_assoc()) {
  $kategoriLabels[] = $row['nama_kategori'];
  $kategoriData[] = $row['total'];
}

// === Chart Data: Status Aset ===
$statusLabels = [];
$statusData = [];
$result = $conn->query("
  SELECT 
    CASE 
      WHEN status = 'tersedia' THEN 'Tersedia'
      WHEN status = 'dipinjam' THEN 'Dipinjam'
      WHEN status = 'rusak' THEN 'Rusak'
      ELSE 'Lainnya'
    END AS status_label,
    COUNT(*) AS total
  FROM assets
  WHERE deleted_at IS NULL
  GROUP BY status
");
while ($row = $result->fetch_assoc()) {
  $statusLabels[] = $row['status_label'];
  $statusData[] = $row['total'];
}

// === Peminjaman Jatuh Tempo & Terlambat ===
$dueToday = [];
$overdue = [];

$result = $conn->query("
  SELECT 
    l.id_peminjaman, 
    a.kode_aset,
    a.nama_aset,
    u.nama AS peminjam,
    u.email,
    l.start_date,
    l.end_date,
    DATEDIFF(CURDATE(), l.end_date) AS days_overdue
  FROM loans l
  JOIN assets a ON l.id_aset = a.id_aset
  JOIN users u ON l.id_user = u.id_user
  WHERE l.status = 'approved'
    AND l.end_date <= CURDATE()
  ORDER BY l.end_date ASC
  LIMIT 10
");

while ($row = $result->fetch_assoc()) {
  if ($row['days_overdue'] > 0) {
    $overdue[] = $row;
  } else {
    $dueToday[] = $row;
  }
}

// === Maintenance Mendatang ===
$upcomingMaintenance = [];
$result = $conn->query("
  SELECT 
    ms.id_jadwal,
    a.kode_aset,
    a.nama_aset,
    ms.tanggal_jadwal,
    ms.keterangan,
    ms.status
  FROM maintenance_schedule ms
  JOIN assets a ON ms.id_aset = a.id_aset
  WHERE ms.status = 'terjadwal'
    AND ms.tanggal_jadwal >= CURDATE()
  ORDER BY ms.tanggal_jadwal ASC
  LIMIT 5
");

while ($row = $result->fetch_assoc()) {
  $upcomingMaintenance[] = $row;
}

// Hitung aset yang butuh maintenance (lebih dari 30 hari)
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $hari_terakhir = $row['terakhir_maintenance'];
    $selisih = $hari_terakhir ? (new DateTime())->diff(new DateTime($hari_terakhir))->days : 999;

    if ($selisih > 30) {
      $upcomingMaintenance[] = [
        'nama_aset' => $row['nama_aset'],
        'tanggal_maintenance' => $hari_terakhir ?: 'Belum Pernah',
        'biaya' => 0,
        'deskripsi' => $hari_terakhir
          ? "Terakhir maintenance $selisih hari lalu"
          : "Belum pernah di-maintenance"
      ];
    }
  }
}


// === Laporan Kerusakan Terbaru ===
$recentDamageReports = [];
$result = $conn->query("
  SELECT 
    dr.id_laporan,
    a.id_aset,
    a.nama_aset,
    u.nama AS pelapor,
    dr.deskripsi,
    dr.status,
    dr.tanggal_lapor
  FROM damage_reports dr
  JOIN assets a ON dr.id_aset = a.id_aset
  JOIN users u ON dr.id_user = u.id_user
  ORDER BY dr.tanggal_lapor DESC
  LIMIT 5
");
while ($row = $result->fetch_assoc()) {
  $recentDamageReports[] = $row;
}

?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>! Kelola semua aspek sistem SIAMKA dari sini.</p>
      </div>
      <div class="page-header-meta">
        <div class="meta-item">
          <span class="meta-label">Terakhir diperbarui</span>
          <span class="meta-value"><?= date('d M Y, H:i') ?> WIB</span>
        </div>
      </div>
    </div>

    <!-- STAT CARDS GRID -->
    <div class="stat-cards-grid">
      <!-- Users Card -->
      <div class="stat-card stat-card-users">
        <div class="stat-card-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Total Users</span>
          <span class="stat-value"><?= $totalUsers ?></span>
          <span class="stat-footer">Semua pengguna sistem</span>
        </div>
      </div>

      <!-- Assets Card -->
      <div class="stat-card stat-card-assets">
        <div class="stat-card-icon">
          <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Total Aset</span>
          <span class="stat-value"><?= $totalAssets ?></span>
          <span class="stat-footer"><?= $asetTersedia ?> tersedia</span>
        </div>
      </div>

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

      <!-- Damage Reports Card -->
      <div class="stat-card stat-card-damage">
        <div class="stat-card-icon">
          <i class="fa-solid fa-wrench"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Damage Pending</span>
          <span class="stat-value"><?= $damagePending ?></span>
          <span class="stat-footer">Perlu ditangani</span>
        </div>
      </div>

      <!-- Maintenance Card -->
      <div class="stat-card stat-card-maintenance">
        <div class="stat-card-icon">
          <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Maintenance</span>
          <span class="stat-value"><?= $maintenanceScheduled ?></span>
          <span class="stat-footer">Terjadwal</span>
        </div>
      </div>

      <!-- Overdue Card -->
      <div class="stat-card stat-card-overdue">
        <div class="stat-card-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Terlambat</span>
          <span class="stat-value"><?= count($overdue) ?></span>
          <span class="stat-footer">Perlu pengembalian</span>
        </div>
      </div>
    </div>

    <!-- CHARTS SECTION -->
    <div class="charts-grid">
      <!-- Main Loans Chart -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-chart-line"></i> Trend Peminjaman (12 Bulan Terakhir)
            </h3>
          </div>
          <span class="badge badge-primary"><?= array_sum($peminjamanData) ?> Total</span>
        </div>
        <div class="card-body">
          <?php if (count($peminjamanData) > 0): ?>
            <canvas id="peminjamanChart" style="max-height: 350px;"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-chart-line"></i>
              <p>Belum ada data peminjaman</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Asset Status Pie Chart -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-pie-chart"></i> Status Aset
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($statusData) > 0): ?>
            <canvas id="statusChart"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-pie-chart"></i>
              <p>Tidak ada data status aset</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- SECONDARY CHARTS -->
    <div class="charts-grid">
      <!-- Category Distribution -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-bars"></i> Distribusi Aset per Kategori
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($kategoriData) > 0): ?>
            <canvas id="kategoriChart"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-bars"></i>
              <p>Tidak ada data kategori aset</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Overdue Loans Table -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title text-danger">
              <i class="fa-solid fa-triangle-exclamation"></i> Peminjaman Terlambat
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($overdue) > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Aset</th>
                    <th>Peminjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Terlambat</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($overdue as $o): ?>
                    <tr>
                      <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($o['nama_aset']) ?></div>
                        <div class="text-muted text-sm"><?= htmlspecialchars($o['kode_aset']) ?></div>
                      </td>
                      <td>
                        <div><?= htmlspecialchars($o['peminjam']) ?></div>
                        <div class="text-muted text-sm"><?= htmlspecialchars($o['email']) ?></div>
                      </td>
                      <td><?= date('d/m/Y', strtotime($o['end_date'])) ?></td>
                      <td>
                        <span class="badge badge-danger">
                          <?= $o['days_overdue'] ?> hari
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-check-circle"></i>
              <p>Tidak ada peminjaman yang terlambat</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- INFO TABLES -->
    <div class="charts-grid">
      <!-- Due Today Table -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-calendar-days"></i> Jatuh Tempo Hari Ini
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($dueToday) > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Aset</th>
                    <th>Peminjam</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dueToday as $d): ?>
                    <tr>
                      <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($d['nama_aset']) ?></div>
                        <div class="text-muted text-sm"><?= htmlspecialchars($d['kode_aset']) ?></div>
                      </td>
                      <td><?= htmlspecialchars($d['peminjam']) ?></td>
                      <td>
                        <span class="badge badge-warning">
                          <?= date('H:i', strtotime($d['end_date'])) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-calendar-check"></i>
              <p>Tidak ada jatuh tempo hari ini</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Upcoming Maintenance Table -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance Mendatang
            </h3>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($upcomingMaintenance) > 0): ?>
            <div class="table-wrapper">
              <table class="table">
                <thead>
                  <tr>
                    <th>Aset</th>
                    <th>Tanggal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($upcomingMaintenance as $m): ?>
                    <tr>
                      <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($m['nama_aset']) ?></div>
                        <div class="text-muted text-sm"><?= htmlspecialchars(substr($m['keterangan'], 0, 40)) ?>...</div>
                      </td>
                      <td><?= date('d/m/Y', strtotime($m['tanggal_jadwal'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-calendar-xmark"></i>
              <p>Tidak ada maintenance terjadwal</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RECENT DAMAGE REPORTS -->
    <div class="card card-shadow">
      <div class="card-header">
        <div class="card-header-content">
          <h3 class="card-title">
            <i class="fa-solid fa-triangle-exclamation"></i> Laporan Kerusakan Terbaru
          </h3>
        </div>
        <a href="../../damage_reports/" class="btn btn-sm btn-outline-primary">
          Lihat Semua <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <div class="card-body">
        <?php if (count($recentDamageReports) > 0): ?>
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Aset</th>
                  <th>Pelapor</th>
                  <th>Deskripsi</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentDamageReports as $dr): ?>
                  <tr>
                    <td><?= date('d/m/Y', strtotime($dr['tanggal_lapor'])) ?></td>
                    <td>
                      <div class="font-weight-bold"><?= htmlspecialchars($dr['nama_aset']) ?></div>
                      <div class="text-muted text-sm"><?= htmlspecialchars($dr['id_aset']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($dr['pelapor']) ?></td>
                    <td><?= htmlspecialchars(substr($dr['deskripsi'], 0, 50)) ?>...</td>
                    <td>
                      <?php
                      $statusClass = $dr['status'] === 'diproses' ? 'warning' : ($dr['status'] === 'selesai' ? 'success' : 'secondary');
                      ?>
                      <span class="badge badge-<?= $statusClass ?>">
                        <?= ucfirst($dr['status']) ?>
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
            <p>Tidak ada laporan kerusakan</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Chart 1: Peminjaman per Bulan (Line Chart)
  const ctx1 = document.getElementById('peminjamanChart');
  new Chart(ctx1, {
    type: 'line',
    data: {
      labels: <?= json_encode($bulanLabels) ?>,
      datasets: [{
        label: 'Total Peminjaman',
        data: <?= json_encode($peminjamanData) ?>,
        borderColor: '#0f5a92',
        backgroundColor: 'rgba(15, 90, 146, 0.05)',
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: '#0f5a92',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
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
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          },
          grid: {
            color: 'rgba(0,0,0,0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  // Chart 2: Status Aset (Doughnut Chart)
  const ctx2 = document.getElementById('statusChart');
  new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($statusLabels) ?>,
      datasets: [{
        data: <?= json_encode($statusData) ?>,
        backgroundColor: ['#0f5a92', '#ffc107', '#dc3545', '#6c757d'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 15,
            font: {
              size: 12
            }
          }
        }
      }
    }
  });

  // Chart 3: Kategori Aset (Bar Chart)
  const ctx3 = document.getElementById('kategoriChart');
  new Chart(ctx3, {
    type: 'bar',
    data: {
      labels: <?= json_encode($kategoriLabels) ?>,
      datasets: [{
        label: 'Jumlah Aset',
        data: <?= json_encode($kategoriData) ?>,
        backgroundColor: [
          'rgba(15, 90, 146, 0.8)',
          'rgba(255, 193, 7, 0.8)',
          'rgba(220, 53, 69, 0.8)',
          'rgba(108, 117, 125, 0.8)',
          'rgba(15, 90, 146, 0.6)'
        ],
        borderColor: [
          '#0f5a92',
          '#ffc107',
          '#dc3545',
          '#6c757d',
          '#0f5a92'
        ],
        borderWidth: 2,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 12
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          },
          grid: {
            color: 'rgba(0,0,0,0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
</script>

<?php require_once '../../includes/footer.php'; ?>
<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['manajemen', 'admin']);

// === Card Statistik Management ===

// Total Aset Kampus
$totalAset = $conn->query("
  SELECT COUNT(*) AS total
  FROM assets
  WHERE deleted_at IS NULL
")->fetch_assoc()['total'] ?? 0;

// Nilai Total Aset (SUM harga)
$nilaiTotalAset = $conn->query("
  SELECT COALESCE(SUM(harga), 0) AS total
  FROM assets
  WHERE deleted_at IS NULL
")->fetch_assoc()['total'] ?? 0;

// Utilisasi Aset (%)
$asetDipinjam = $conn->query("
  SELECT COUNT(*) AS total
  FROM assets
  WHERE deleted_at IS NULL AND status = 'dipinjam'
")->fetch_assoc()['total'] ?? 0;
$utilisasiPersen = $totalAset > 0 ? round(($asetDipinjam / $totalAset) * 100, 2) : 0;

// Total Maintenance Cost
$totalMaintenanceCost = $conn->query("
  SELECT COALESCE(SUM(biaya), 0) AS total
  FROM maintenance_history
")->fetch_assoc()['total'] ?? 0;

// Maintenance Cost (Current Year)
$maintenanceCostThisYear = $conn->query("
  SELECT COALESCE(SUM(biaya), 0) AS total
  FROM maintenance_history
  WHERE YEAR(tanggal_perawatan) = YEAR(CURDATE())
")->fetch_assoc()['total'] ?? 0;

// === Additional Statistics ===
$asetTersedia = $conn->query("
  SELECT COUNT(*) AS total 
  FROM assets 
  WHERE deleted_at IS NULL AND status = 'tersedia'
")->fetch_assoc()['total'];

$asetRusak = $conn->query("
  SELECT COUNT(*) AS total 
  FROM assets 
  WHERE deleted_at IS NULL AND status = 'rusak'
")->fetch_assoc()['total'];

$totalPeminjaman = $conn->query("
  SELECT COUNT(*) AS total 
  FROM loans
")->fetch_assoc()['total'];

$peminjamanAktif = $conn->query("
  SELECT COUNT(*) AS total 
  FROM loans 
  WHERE status = 'approved'
")->fetch_assoc()['total'];

// === Chart Data: Trend Peminjaman (12 Bulan Terakhir - Line Chart) ===
$trendLabels = [];
$trendData = [];
$result = $conn->query("
  SELECT 
    DATE_FORMAT(start_date, '%b %Y') AS bulan,
    COUNT(*) AS total
  FROM loans
  WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY DATE_FORMAT(start_date, '%Y-%m')
  ORDER BY start_date ASC
");
while ($row = $result->fetch_assoc()) {
  $trendLabels[] = $row['bulan'];
  $trendData[] = $row['total'];
}

// === Chart Data: Kondisi Aset (Doughnut) ===
$kondisiLabels = [];
$kondisiData = [];
$kondisiColors = [];
$result = $conn->query("
  SELECT 
    CASE 
      WHEN status = 'tersedia' THEN 'Tersedia'
      WHEN status = 'dipinjam' THEN 'Dipinjam'
      WHEN status = 'rusak' THEN 'Rusak'
      ELSE 'Lainnya'
    END AS kondisi,
    COUNT(*) AS total
  FROM assets
  WHERE deleted_at IS NULL
  GROUP BY status
  ORDER BY total DESC
");
while ($row = $result->fetch_assoc()) {
  $kondisiLabels[] = $row['kondisi'];
  $kondisiData[] = $row['total'];
}

// === Chart Data: Top 10 Aset Paling Sering Dipinjam (Bar Chart) ===
$top10Labels = [];
$top10Data = [];
$result = $conn->query("
  SELECT 
    a.nama_aset,
    a.kode_aset,
    COUNT(l.id_peminjaman) AS total_peminjaman
  FROM assets a
  LEFT JOIN loans l ON a.id_aset = l.id_aset
  WHERE a.deleted_at IS NULL
  GROUP BY a.id_aset
  ORDER BY total_peminjaman DESC
  LIMIT 10
");
while ($row = $result->fetch_assoc()) {
  $top10Labels[] = $row['kode_aset'];
  $top10Data[] = $row['total_peminjaman'];
}

// === Summary Report Data ===

// Peminjaman per Status
$peminjamanPending = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status = 'pending'")->fetch_assoc()['total'];
$peminjamanApproved = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status = 'approved'")->fetch_assoc()['total'];
$peminjamanReturned = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status = 'returned'")->fetch_assoc()['total'];
$peminjamanRejected = $conn->query("SELECT COUNT(*) AS total FROM loans WHERE status = 'rejected'")->fetch_assoc()['total'];

// Damage Reports
$damageReportsPending = $conn->query("SELECT COUNT(*) AS total FROM damage_reports WHERE status = 'pending'")->fetch_assoc()['total'];
$damageReportsProcessed = $conn->query("SELECT COUNT(*) AS total FROM damage_reports WHERE status = 'diproses'")->fetch_assoc()['total'];
$damageReportsCompleted = $conn->query("SELECT COUNT(*) AS total FROM damage_reports WHERE status = 'selesai'")->fetch_assoc()['total'];

// Maintenance
$maintenanceScheduled = $conn->query("SELECT COUNT(*) AS total FROM maintenance_schedule WHERE status = 'scheduled'")->fetch_assoc()['total'];
$maintenanceInProgress = $conn->query("SELECT COUNT(*) AS total FROM maintenance_schedule WHERE status = 'in_progress'")->fetch_assoc()['total'];
$maintenanceCompleted = $conn->query("SELECT COUNT(*) AS total FROM maintenance_schedule WHERE status = 'completed'")->fetch_assoc()['total'];

// Kategori Aset
$kategoriData = [];
$result = $conn->query("
  SELECT 
    c.nama_kategori,
    COUNT(a.id_aset) AS total,
    COALESCE(SUM(a.harga), 0) AS total_nilai
  FROM categories c
  LEFT JOIN assets a ON c.id_kategori = a.id_kategori AND a.deleted_at IS NULL
  GROUP BY c.id_kategori
  ORDER BY total DESC
");
while ($row = $result->fetch_assoc()) {
  $kategoriData[] = $row;
}

// Asset Age Analysis (Aset berdasarkan umur)
$assetAge = [];
$result = $conn->query("
  SELECT 
    CASE 
      WHEN TIMESTAMPDIFF(YEAR, tanggal_perolehan, CURDATE()) < 1 THEN '< 1 Tahun'
      WHEN TIMESTAMPDIFF(YEAR, tanggal_perolehan, CURDATE()) BETWEEN 1 AND 3 THEN '1-3 Tahun'
      WHEN TIMESTAMPDIFF(YEAR, tanggal_perolehan, CURDATE()) BETWEEN 4 AND 5 THEN '4-5 Tahun'
      ELSE '> 5 Tahun'
    END AS umur_kategori,
    COUNT(*) AS total,
    COALESCE(SUM(harga), 0) AS total_nilai
  FROM assets
  WHERE deleted_at IS NULL
  GROUP BY umur_kategori
  ORDER BY 
    CASE umur_kategori
      WHEN '< 1 Tahun' THEN 1
      WHEN '1-3 Tahun' THEN 2
      WHEN '4-5 Tahun' THEN 3
      ELSE 4
    END
");
while ($row = $result->fetch_assoc()) {
  $assetAge[] = $row;
}

// Peminjaman Terlambat
$peminjamanTerlambat = $conn->query("
  SELECT COUNT(*) AS total 
  FROM loans 
  WHERE status = 'approved' AND end_date < NOW()
")->fetch_assoc()['total'];

// Average Loan Duration
$avgLoanDuration = $conn->query("
  SELECT AVG(DATEDIFF(end_date, start_date)) AS avg_days
  FROM loans
  WHERE status IN ('returned', 'approved')
")->fetch_assoc()['avg_days'] ?? 0;

?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title">Dashboard Manajemen</h1>
        <p class="page-subtitle">Laporan komprehensif dan analisis aset sistem SIAMKA</p>
      </div>
      <div class="page-header-meta">
        <div class="meta-item">
          <span class="meta-label">Terakhir diperbarui</span>
          <span class="meta-value"><?= date('d M Y, H:i') ?> WIB</span>
        </div>
      </div>
    </div>

    <!-- STAT CARDS GRID -->
    <div class="stat-cards">
      <!-- Total Assets Card -->
      <div class="stat-card stat-card-assets">
        <div class="stat-card-icon">
          <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Total Aset Kampus</span>
          <span class="stat-value"><?= number_format($totalAset) ?></span>
          <span class="stat-footer"><?= $asetTersedia ?> tersedia, <?= $asetRusak ?> rusak</span>
        </div>
      </div>

      <!-- Asset Value Card -->
      <div class="stat-card stat-card-assets">
        <div class="stat-card-icon">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Nilai Total Aset</span>
          <span class="stat-value">Rp <?= number_format($nilaiTotalAset, 0, ',', '.') ?></span>
          <span class="stat-footer">Total investasi aset</span>
        </div>
      </div>

      <!-- Utilization Card -->
      <div class="stat-card stat-card-loans">
        <div class="stat-card-icon">
          <i class="fa-solid fa-gauge-high"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Utilisasi Aset</span>
          <span class="stat-value"><?= $utilisasiPersen ?>%</span>
          <span class="stat-footer"><?= $asetDipinjam ?>/<?= $totalAset ?> digunakan</span>
        </div>
      </div>

      <!-- Maintenance Cost Card -->
      <div class="stat-card stat-card-damage">
        <div class="stat-card-icon">
          <i class="fa-solid fa-tools"></i>
        </div>
        <div class="stat-card-content">
          <span class="stat-label">Total Maintenance</span>
          <span class="stat-value">Rp <?= number_format($totalMaintenanceCost, 0, ',', '.') ?></span>
          <span class="stat-footer">Tahun ini: Rp <?= number_format($maintenanceCostThisYear, 0, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="charts-grid">
      <!-- Trend Peminjaman -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-chart-line"></i> Trend Peminjaman (12 Bulan Terakhir)
            </h3>
          </div>
          <div>
            <span class="badge badge-primary"><?= array_sum($trendData) ?> Total</span>
            <span class="badge badge-success"><?= $peminjamanAktif ?> Aktif</span>
          </div>
        </div>
        <div class="card-body">
          <?php if (count($trendData) > 0): ?>
            <canvas id="trendChart" style="max-height: 350px;"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-chart-line"></i>
              <p>Belum ada data peminjaman</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Kondisi Aset -->
      <div class="card card-shadow">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa-solid fa-pie-chart"></i> Kondisi Aset
          </h3>
        </div>
        <div class="card-body">
          <?php if (count($kondisiData) > 0): ?>
            <canvas id="kondisiChart" style="max-height: 300px;"></canvas>
          <?php else: ?>
            <div class="empty-state">
              <i class="fa-solid fa-pie-chart"></i>
              <p>Tidak ada data</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top 10 Aset Chart -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fa-solid fa-bar-chart"></i> Top 10 Aset Paling Sering Dipinjam
        </h3>
      </div>
      <div class="card-body">
        <?php if (count($top10Data) > 0): ?>
          <canvas id="top10Chart" style="max-height: 350px;"></canvas>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-bar-chart"></i>
            <p>Belum ada data peminjaman</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SUMMARY REPORT CARDS -->
    <div class="charts-grid">
      <!-- Loan Statistics -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-arrow-repeat"></i> Statistik Peminjaman
            </h3>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <tbody>
                <tr>
                  <td>Pending</td>
                  <td class="text-end"><span class="badge badge-warning"><?= $peminjamanPending ?></span></td>
                </tr>
                <tr>
                  <td>Approved</td>
                  <td class="text-end"><span class="badge badge-success"><?= $peminjamanApproved ?></span></td>
                </tr>
                <tr>
                  <td>Returned</td>
                  <td class="text-end"><span class="badge badge-info"><?= $peminjamanReturned ?></span></td>
                </tr>
                <tr>
                  <td>Rejected</td>
                  <td class="text-end"><span class="badge badge-danger"><?= $peminjamanRejected ?></span></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            <small class="text-muted">
              <i class="fa-solid fa-clock"></i> Rata-rata durasi: <?= round($avgLoanDuration, 1) ?> hari
              <?php if ($peminjamanTerlambat > 0): ?>
                <br><span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Terlambat: <?= $peminjamanTerlambat ?></span>
              <?php endif; ?>
            </small>
          </div>
        </div>
      </div>

      <!-- Damage & Maintenance -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-tools"></i> Damage & Maintenance
            </h3>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <tbody>
                <tr>
                  <td>Damage Pending</td>
                  <td class="text-end"><span class="badge badge-warning"><?= $damageReportsPending ?></span></td>
                </tr>
                <tr>
                  <td>Damage Processing</td>
                  <td class="text-end"><span class="badge badge-info"><?= $damageReportsProcessed ?></span></td>
                </tr>
                <tr>
                  <td>Damage Completed</td>
                  <td class="text-end"><span class="badge badge-success"><?= $damageReportsCompleted ?></span></td>
                </tr>
                <tr>
                  <td>Maintenance Scheduled</td>
                  <td class="text-end"><span class="badge badge-primary"><?= $maintenanceScheduled ?></span></td>
                </tr>
                <tr>
                  <td>Maintenance In Progress</td>
                  <td class="text-end"><span class="badge badge-warning"><?= $maintenanceInProgress ?></span></td>
                </tr>
                <tr>
                  <td>Maintenance Completed</td>
                  <td class="text-end"><span class="badge badge-success"><?= $maintenanceCompleted ?></span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ASSET ANALYSIS -->
    <div class="charts-grid">
      <!-- Asset Categories -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-collection"></i> Kategori Aset
            </h3>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Kategori</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-end">Nilai</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($kategoriData, 0, 8) as $kat): ?>
                  <tr>
                    <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
                    <td class="text-center"><span class="badge badge-info"><?= $kat['total'] ?></span></td>
                    <td class="text-end">Rp <?= number_format($kat['total_nilai'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Asset Age Analysis -->
      <div class="card card-shadow">
        <div class="card-header">
          <div class="card-header-content">
            <h3 class="card-title">
              <i class="fa-solid fa-hourglass-split"></i> Analisis Umur Aset
            </h3>
          </div>
        </div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Kategori Umur</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-end">Nilai</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assetAge as $age): ?>
                  <tr>
                    <td><?= htmlspecialchars($age['umur_kategori']) ?></td>
                    <td class="text-center"><span class="badge badge-info"><?= $age['total'] ?></span></td>
                    <td class="text-end">Rp <?= number_format($age['total_nilai'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- KEY INSIGHTS -->
    <div class="card card-shadow">
      <div class="card-header">
        <div class="card-header-content">
          <h3 class="card-title">
            <i class="fa-solid fa-lightbulb"></i> Key Insights & Recommendations
          </h3>
        </div>
        <button class="btn btn-sm btn-outline-primary">
          <i class="fa-solid fa-download"></i> Export Report
        </button>
      </div>
      <div class="card-body">
        <div class="insights-list">
          <div class="insight-item">
            <strong>Utilisasi Aset:</strong>
            <?php if ($utilisasiPersen < 30): ?>
              Rendah (<?= $utilisasiPersen ?>%). Pertimbangkan program promosi untuk meningkatkan penggunaan aset.
            <?php elseif ($utilisasiPersen < 70): ?>
              Sedang (<?= $utilisasiPersen ?>%). Utilisasi aset dalam kondisi optimal.
            <?php else: ?>
              Tinggi (<?= $utilisasiPersen ?>%). Pertimbangkan penambahan aset untuk memenuhi demand.
            <?php endif; ?>
          </div>
          <div class="insight-item">
            <strong>Maintenance Cost:</strong>
            Total biaya maintenance mencapai Rp <?= number_format($totalMaintenanceCost, 0, ',', '.') ?>.
            Tahun ini sudah dikeluarkan Rp <?= number_format($maintenanceCostThisYear, 0, ',', '.') ?>.
          </div>
          <div class="insight-item">
            <strong>Asset Condition:</strong>
            <?= $asetRusak ?> aset dalam kondisi rusak.
            <?php if ($asetRusak > 0): ?>
              Prioritaskan perbaikan atau penggantian.
            <?php else: ?>
              Semua aset dalam kondisi baik.
            <?php endif; ?>
          </div>
          <div class="insight-item">
            <strong>Overdue Loans:</strong>
            <?php if ($peminjamanTerlambat > 0): ?>
              Terdapat <?= $peminjamanTerlambat ?> peminjaman yang terlambat. Diperlukan tindak lanjut.
            <?php else: ?>
              Tidak ada peminjaman yang terlambat. Sistem berjalan dengan baik.
            <?php endif; ?>
          </div>
          <div class="insight-item">
            <strong>Rata-rata Durasi Peminjaman:</strong>
            <?= round($avgLoanDuration, 1) ?> hari.
            <?php if ($avgLoanDuration > 7): ?>
              Pertimbangkan kebijakan maksimal durasi peminjaman.
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Additional Statistics Cards -->
  <div class="stats-overview">
    <div class="stat-overview-card">
      <div class="stat-overview-icon">
        <i class="fa-solid fa-journal-text"></i>
      </div>
      <div class="stat-overview-content">
        <span class="stat-overview-label">Total Peminjaman</span>
        <span class="stat-overview-value"><?= number_format($totalPeminjaman) ?></span>
      </div>
    </div>

    <div class="stat-overview-card">
      <div class="stat-overview-icon">
        <i class="fa-solid fa-check-circle"></i>
      </div>
      <div class="stat-overview-content">
        <span class="stat-overview-label">Peminjaman Selesai</span>
        <span class="stat-overview-value"><?= number_format($peminjamanReturned) ?></span>
      </div>
    </div>

    <div class="stat-overview-card">
      <div class="stat-overview-icon">
        <i class="fa-solid fa-arrow-repeat"></i>
      </div>
      <div class="stat-overview-content">
        <span class="stat-overview-label">Aset Dipinjam</span>
        <span class="stat-overview-value"><?= number_format($asetDipinjam) ?></span>
      </div>
    </div>

    <div class="stat-overview-card">
      <div class="stat-overview-icon">
        <i class="fa-solid fa-exclamation-triangle"></i>
      </div>
      <div class="stat-overview-content">
        <span class="stat-overview-label">Peminjaman Terlambat</span>
        <span class="stat-overview-value text-danger"><?= number_format($peminjamanTerlambat) ?></span>
      </div>
    </div>
  </div>
  </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Chart 1: Trend Peminjaman (Line Chart)
  <?php if (count($trendData) > 0): ?>
    const ctxTrend = document.getElementById('trendChart');
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
          label: 'Jumlah Peminjaman',
          data: <?= json_encode($trendData) ?>,
          borderColor: '#667eea',
          backgroundColor: 'rgba(102, 126, 234, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 6,
          pointHoverRadius: 8,
          pointBackgroundColor: '#667eea',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointHoverBackgroundColor: '#667eea',
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
              size: 14,
              weight: 'bold'
            },
            bodyFont: {
              size: 13
            },
            displayColors: false,
            callbacks: {
              label: function(context) {
                return 'Peminjaman: ' + context.parsed.y + ' transaksi';
              }
            }
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

  // Chart 2: Kondisi Aset (Doughnut Chart)
  <?php if (count($kondisiData) > 0): ?>
    const ctxKondisi = document.getElementById('kondisiChart');
    new Chart(ctxKondisi, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($kondisiLabels) ?>,
        datasets: [{
          data: <?= json_encode($kondisiData) ?>,
          backgroundColor: [
            'rgba(16, 185, 129, 0.8)', // Tersedia - Green
            'rgba(59, 130, 246, 0.8)', // Dipinjam - Blue
            'rgba(239, 68, 68, 0.8)', // Rusak - Red
            'rgba(156, 163, 175, 0.8)' // Lainnya - Gray
          ],
          borderColor: [
            'rgba(16, 185, 129, 1)',
            'rgba(59, 130, 246, 1)',
            'rgba(239, 68, 68, 1)',
            'rgba(156, 163, 175, 1)'
          ],
          borderWidth: 2,
          hoverOffset: 15
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
                size: 12
              },
              usePointStyle: true,
              pointStyle: 'circle'
            }
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
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                let value = context.parsed || 0;
                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                let percentage = ((value / total) * 100).toFixed(1);
                return label + ': ' + value + ' aset (' + percentage + '%)';
              }
            }
          }
        }
      }
    });
  <?php endif; ?>

  // Chart 3: Top 10 Aset (Horizontal Bar Chart)
  <?php if (count($top10Data) > 0): ?>
    const ctxTop10 = document.getElementById('top10Chart');

    // Generate gradient colors
    const gradientColors = [
      'rgba(102, 126, 234, 0.8)',
      'rgba(240, 147, 251, 0.8)',
      'rgba(250, 112, 154, 0.8)',
      'rgba(254, 225, 64, 0.8)',
      'rgba(79, 172, 254, 0.8)',
      'rgba(16, 185, 129, 0.8)',
      'rgba(251, 146, 60, 0.8)',
      'rgba(168, 85, 247, 0.8)',
      'rgba(236, 72, 153, 0.8)',
      'rgba(52, 211, 153, 0.8)'
    ];

    new Chart(ctxTop10, {
      type: 'bar',
      data: {
        labels: <?= json_encode($top10Labels) ?>,
        datasets: [{
          label: 'Jumlah Peminjaman',
          data: <?= json_encode($top10Data) ?>,
          backgroundColor: gradientColors,
          borderColor: gradientColors.map(color => color.replace('0.8', '1')),
          borderWidth: 2,
          borderRadius: 8,
          barThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
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
              title: function(context) {
                return 'Kode Aset: ' + context[0].label;
              },
              label: function(context) {
                return 'Dipinjam: ' + context.parsed.x + ' kali';
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: {
                size: 11
              }
            },
            grid: {
              color: 'rgba(0,0,0,0.05)'
            }
          },
          y: {
            grid: {
              display: false
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

  // Print styling
  window.addEventListener('beforeprint', function() {
    document.querySelectorAll('.card').forEach(card => {
      card.style.pageBreakInside = 'avoid';
    });
  });
</script>

<style>
  /* Custom Styles */
  .border-start {
    border-left-width: 4px !important;
  }

  .sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
  }

  /* Print Styles */
  @media print {
    .main-content {
      margin: 0 !important;
      padding: 20px !important;
    }

    .btn,
    .sidebar,
    .navbar {
      display: none !important;
    }

    .card {
      page-break-inside: avoid;
      box-shadow: none !important;
      border: 1px solid #ddd !important;
    }

    .row {
      page-break-inside: avoid;
    }

    h2,
    h5,
    h6 {
      page-break-after: avoid;
    }
  }

  /* Scrollbar Custom */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #555;
  }

  /* Hover Effects */
  .table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
    transition: background-color 0.2s ease;
  }

  /* Card Animations */
  .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .card:hover {
    transform: translateY(-2px);
  }

  /* Progress Bar Animation */
  .progress-bar {
    transition: width 1s ease-in-out;
  }

  /* Badge Styles */
  .badge {
    font-weight: 600;
    padding: 0.35em 0.65em;
  }

  /* Alert Custom */
  .alert {
    border-left: 4px solid;
  }

  .alert-info {
    border-left-color: #3b82f6;
  }

  /* Enhanced Mobile Responsiveness */
  @media (max-width: 1200px) {
    .stat-cards-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }

    .charts-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }

    .stats-overview-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 1rem;
    }

    .dashboard-container {
      padding: 1rem;
    }

    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .page-header-content {
      width: 100%;
    }

    .page-header-actions {
      width: 100%;
      justify-content: space-between;
    }

    .header-actions {
      flex-direction: column;
      gap: 0.5rem;
      width: 100%;
    }

    .header-actions .btn {
      width: 100%;
      justify-content: center;
    }

    .stat-cards-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .charts-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .stats-overview-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .stats-overview {
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }

    .section-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .section-actions {
      width: 100%;
    }

    .section-actions .btn {
      width: 100%;
      justify-content: center;
    }

    .card-body {
      padding: 1rem;
    }

    .card-header {
      padding: 1rem;
    }

    .card-header-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }

    .stat-card-content h3 {
      font-size: 1.75rem;
    }

    .stat-card-content p {
      font-size: 0.875rem;
    }

    .stats-card-body {
      padding: 1rem;
    }

    .stats-metrics-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }

    .metric-item {
      padding: 0.75rem;
    }

    .metric-value {
      font-size: 1.25rem;
    }

    .metric-label {
      font-size: 0.75rem;
    }

    .categories-list {
      max-height: 300px;
      overflow-y: auto;
    }

    .category-item {
      padding: 0.75rem;
    }

    .category-name {
      font-size: 0.875rem;
    }

    .category-count {
      font-size: 0.75rem;
    }

    .category-value {
      font-size: 0.875rem;
    }

    .table-wrapper {
      font-size: 0.875rem;
    }

    .table th,
    .table td {
      padding: 0.75rem 0.5rem;
    }

    canvas {
      max-height: 250px !important;
    }

    .insights-list {
      gap: 1rem;
    }

    .insight-item {
      padding: 1rem;
      font-size: 0.875rem;
    }

    h1 {
      font-size: 1.75rem;
    }

    h2 {
      font-size: 1.5rem;
    }

    h3 {
      font-size: 1.25rem;
    }

    .page-title-text p {
      font-size: 0.875rem;
    }

    .header-stat-value {
      font-size: 0.875rem;
    }
  }

  @media (max-width: 576px) {
    .main-content {
      padding: 0.5rem;
    }

    .dashboard-container {
      padding: 0.5rem;
    }

    .page-title-wrapper {
      gap: 0.75rem;
    }

    .page-title-icon {
      width: 40px;
      height: 40px;
    }

    .page-title-icon i {
      font-size: 1.25rem;
    }

    .page-title {
      font-size: 1.5rem;
    }

    .page-subtitle {
      font-size: 0.8rem;
    }

    .stat-card {
      padding: 1rem;
    }

    .stat-card-header {
      margin-bottom: 0.75rem;
    }

    .stat-card-icon {
      width: 48px;
      height: 48px;
    }

    .stat-card-icon i {
      font-size: 1.5rem;
    }

    .stat-card-content h3 {
      font-size: 1.5rem;
    }

    .stat-card-content p {
      font-size: 0.8rem;
    }

    .stat-card-footer {
      margin-top: 0.75rem;
    }

    .stat-card-footer span {
      font-size: 0.75rem;
    }

    .stat-card-badge {
      top: 1rem;
      right: 1rem;
    }

    .stat-card-badge .badge {
      font-size: 0.7rem;
      padding: 0.25rem 0.5rem;
    }

    .stats-overview {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }

    .stat-overview-card {
      padding: 1rem;
    }

    .stat-overview-icon {
      width: 40px;
      height: 40px;
    }

    .stat-overview-icon i {
      font-size: 1.25rem;
    }

    .stat-overview-content span:first-child {
      font-size: 0.8rem;
    }

    .stat-overview-content span:last-child {
      font-size: 1.25rem;
    }

    .stats-metrics-grid {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }

    .tab-switcher {
      flex-direction: column;
      gap: 0.5rem;
    }

    .tab-btn {
      width: 100%;
      justify-content: center;
    }

    .maintenance-tabs {
      gap: 1rem;
    }

    .tab-metrics {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }

    .table {
      font-size: 0.8rem;
    }

    .table th,
    .table td {
      padding: 0.5rem 0.25rem;
    }

    .progress-container {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.25rem;
    }

    .progress-text {
      font-size: 0.7rem;
    }

    .insights-list {
      gap: 0.75rem;
    }

    .insight-item {
      padding: 0.75rem;
      font-size: 0.8rem;
    }

    canvas {
      max-height: 200px !important;
    }

    .empty-state {
      padding: 2rem 1rem;
    }

    .empty-state i {
      font-size: 3rem;
    }

    .empty-state p {
      font-size: 0.875rem;
    }

    .alert {
      padding: 0.75rem;
      font-size: 0.8rem;
    }

    .badge {
      font-size: 0.7rem;
      padding: 0.25rem 0.5rem;
    }
  }

  @media (max-width: 480px) {
    .dashboard-container {
      padding: 0.25rem;
    }

    .page-header {
      padding: 1rem;
    }

    .stat-card {
      padding: 0.75rem;
    }

    .stats-card {
      padding: 0.75rem;
    }

    .card {
      margin-bottom: 1rem;
    }

    .card-body,
    .card-header {
      padding: 0.75rem;
    }

    .btn {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
    }

    .btn i {
      margin-right: 0.5rem;
    }
  }

  /* Touch-friendly interactions */
  @media (hover: none) and (pointer: coarse) {
    .btn:hover {
      transform: none;
    }

    .card:hover {
      transform: none;
    }

    .stat-card:hover {
      transform: none;
    }

    .hover-lift:hover {
      transform: none;
    }

    .hover-scale:hover {
      transform: none;
    }
  }

  /* High contrast mode support */
  @media (prefers-contrast: high) {

    .stat-card,
    .card,
    .stats-card {
      border: 2px solid currentColor;
    }

    .btn {
      border: 2px solid currentColor;
    }
  }

  /* Reduced motion support */
  @media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
    }

    .animate-fade-in-up,
    .animate-fade-in,
    .animate-slide-in-left,
    .animate-slide-in-right,
    .animate-pulse,
    .animate-glow {
      animation: none;
    }
  }
</style>

<?php require_once '../../includes/footer.php'; ?>
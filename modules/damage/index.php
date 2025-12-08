<?php
define("SECURE", true);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/notification_helper.php';

checkRole(['admin', 'manajemen']);

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- FILTER ---
$status_filter = $_GET['status'] ?? '';
$asset_filter = $_GET['id_aset'] ?? '';

// Query utama
$query = "
  SELECT dr.*, a.nama_aset, a.kode_aset, u.nama
  FROM damage_reports dr
  LEFT JOIN assets a ON dr.id_aset = a.id_aset
  LEFT JOIN users u ON dr.id_user = u.id_user
  WHERE 1=1
";

if (!empty($status_filter)) {
  $query .= " AND dr.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($asset_filter)) {
  $query .= " AND dr.id_aset = " . intval($asset_filter);
}

$query .= " ORDER BY dr.tanggal_lapor DESC";

// Count total records for pagination
$count_query = "
  SELECT COUNT(*) AS total
  FROM damage_reports dr
  LEFT JOIN assets a ON dr.id_aset = a.id_aset
  LEFT JOIN users u ON dr.id_user = u.id_user
  WHERE 1=1
";

if (!empty($status_filter)) {
  $count_query .= " AND dr.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($asset_filter)) {
  $count_query .= " AND dr.id_aset = " . intval($asset_filter);
}

$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Add LIMIT and OFFSET to main query
$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Daftar aset untuk filter
$assets = $conn->query("SELECT id_aset, nama_aset FROM assets ORDER BY nama_aset ASC");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title"><i class="fa-solid fa-screwdriver-wrench"></i> Manajemen Laporan Kerusakan</h1>
        <p class="page-subtitle">Pantau dan kelola semua laporan kerusakan aset</p>
      </div>
    </div>

    <!-- FILTERS AND SEARCH -->
    <div class="card card-shadow mb-4">
      <div class="card-body">
        <div class="grid grid-4 gap-3">
          <div class="form-group">
            <label class="form-label">Cari Aset</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari berdasarkan kode atau nama aset...">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="statusFilter" class="form-control">
              <option value="">Semua Status</option>
              <option value="baru">Baru</option>
              <option value="diproses">Diproses</option>
              <option value="selesai">Selesai</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Aset</label>
            <select name="id_aset" id="assetFilter" class="form-control">
              <option value="">Semua Aset</option>
              <?php
              $assets->data_seek(0);
              while ($row = $assets->fetch_assoc()): ?>
                <option value="<?= $row['id_aset'] ?>">
                  <?= htmlspecialchars($row['nama_aset']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group d-flex align-items-end">
            <button type="button" id="applyFilters" class="btn btn-primary w-100">
              <i class="fa-solid fa-filter"></i> Terapkan Filter
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card card-shadow">
      <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Laporan</h3>
      </div>
      <div class="card-body">
        <?php display_notification(); ?>

        <!-- Table Section -->
        <div class="table-responsive">
          <table class="table table-hover" id="reportsTable">
            <thead class="table-dark">
              <tr>
                <th>No</th>
                <th>Kode Aset</th>
                <th>Nama Aset</th>
                <th>Pelapor</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php $no = 1;
                while ($row = $result->fetch_assoc()): ?>
                  <?php
                  $status_class = [
                    'baru' => 'warning',
                    'diproses' => 'primary',
                    'selesai' => 'success'
                  ][$row['status']] ?? 'secondary';
                  ?>
                  <tr class="report-row" data-status="<?= $row['status'] ?>" data-asset-id="<?= $row['id_aset'] ?>" data-asset-name="<?= htmlspecialchars($row['nama_aset']) ?>" data-asset-code="<?= htmlspecialchars($row['kode_aset']) ?>">
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['kode_aset']) ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_aset']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_lapor']) ?></td>
                    <td class="text-truncate" title="<?= htmlspecialchars($row['deskripsi']) ?>"><?= substr(htmlspecialchars($row['deskripsi']), 0, 50) ?>...</td>
                    <td>
                      <span class="badge bg-<?= $status_class ?>"><?= ucfirst($row['status']) ?></span>
                    </td>
                    <td>
                      <a href="update_status.php?id=<?= $row['id_laporan'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr id="noResultsRow">
                  <td colspan="8">
                    <div class="empty-state">
                      <i class="fa-solid fa-inbox"></i>
                      <p>Tidak ada laporan kerusakan</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination">
            <?php
            // Previous button
            if ($page > 1) {
              $prev_page = $page - 1;
              $prev_url = "?page=$prev_page&status=" . urlencode($status_filter) . "&id_aset=" . urlencode($asset_filter);
              echo "<a href='$prev_url' class='prev'>« Previous</a>";
            }

            // Page numbers
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1) {
              $first_url = "?page=1&status=" . urlencode($status_filter) . "&id_aset=" . urlencode($asset_filter);
              echo "<a href='$first_url'>1</a>";
              if ($start_page > 2) echo "<span class='dots'>...</span>";
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
              $page_url = "?page=$i&status=" . urlencode($status_filter) . "&id_aset=" . urlencode($asset_filter);
              $active_class = ($i == $page) ? 'active' : '';
              echo "<a href='$page_url' class='$active_class'>$i</a>";
            }

            if ($end_page < $total_pages) {
              if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
              $last_url = "?page=$total_pages&status=" . urlencode($status_filter) . "&id_aset=" . urlencode($asset_filter);
              echo "<a href='$last_url'>$total_pages</a>";
            }

            // Next button
            if ($page < $total_pages) {
              $next_page = $page + 1;
              $next_url = "?page=$next_page&status=" . urlencode($status_filter) . "&id_aset=" . urlencode($asset_filter);
              echo "<a href='$next_url' class='next'>Next »</a>";
            }
            ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const assetFilter = document.getElementById('assetFilter');
    const applyFilters = document.getElementById('applyFilters');
    const tableRows = document.querySelectorAll('.report-row');
    const noResultsRow = document.getElementById('noResultsRow');

    function filterReports() {
      const searchTerm = searchInput.value.toLowerCase();
      const selectedStatus = statusFilter.value;
      const selectedAsset = assetFilter.value;
      let visibleCount = 0;

      tableRows.forEach(row => {
        const assetName = row.dataset.assetName.toLowerCase();
        const assetCode = row.dataset.assetCode.toLowerCase();
        const status = row.dataset.status;
        const assetId = row.dataset.assetId;

        const matchesSearch = assetName.includes(searchTerm) || assetCode.includes(searchTerm);
        const matchesStatus = !selectedStatus || status === selectedStatus;
        const matchesAsset = !selectedAsset || assetId === selectedAsset;

        if (matchesSearch && matchesStatus && matchesAsset) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      // Show/hide no results row
      if (visibleCount === 0 && tableRows.length > 0) {
        noResultsRow.style.display = '';
      } else {
        noResultsRow.style.display = 'none';
      }
    }

    searchInput.addEventListener('input', filterReports);
    statusFilter.addEventListener('change', filterReports);
    assetFilter.addEventListener('change', filterReports);
    applyFilters.addEventListener('click', filterReports);
  });
</script>

<?php include '../../includes/footer.php'; ?>
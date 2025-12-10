<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/role_check.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

checkRole(['pengguna']);

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 🔎 Ambil filter kategori (opsional)
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

// 🔧 Base query untuk count
$countQuery = "
    SELECT COUNT(*) AS total
    FROM assets a
    LEFT JOIN categories k ON a.id_kategori = k.id_kategori
    WHERE a.status = 'Tersedia'
      AND a.kondisi != 'Hilang'
      AND a.deleted_at IS NULL
";

if (!empty($kategori)) {
    $countQuery .= " AND a.id_kategori = " . intval($kategori);
}

// Hitung total aset
$totalResult = mysqli_query($conn, $countQuery);
$total_assets = mysqli_fetch_assoc($totalResult)['total'];
$total_pages = ceil($total_assets / $limit);

// 🔧 Query aset tersedia dengan pagination
$query = "
    SELECT a.*, k.nama_kategori
    FROM assets a
    LEFT JOIN categories k ON a.id_kategori = k.id_kategori
    WHERE a.status = 'Tersedia'
      AND a.kondisi != 'Hilang'
      AND a.deleted_at IS NULL
";

if (!empty($kategori)) {
    $query .= " AND a.id_kategori = " . intval($kategori);
}

$query .= " ORDER BY a.nama_aset ASC LIMIT $limit OFFSET $offset";

// Jalankan query
$result = mysqli_query($conn, $query);

// 🔽 Ambil daftar kategori untuk dropdown filter
$kategori_query = mysqli_query($conn, "SELECT id_kategori, nama_kategori FROM categories");
?>

<main class="main-content">
    <div class="dashboard-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title"><i class="fa-solid fa-box-open"></i> Aset Tersedia untuk Dipinjam</h1>
                <p class="page-subtitle">Lihat daftar aset yang dapat dipinjam dan ajukan permintaan</p>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card card-shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-list"></i> Daftar Aset Tersedia</h3>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-group">
                        <div class="search-box">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari aset berdasarkan nama atau kode..." class="form-control">
                        </div>
                        <select name="kategori" id="kategoriSelect" class="form-control">
                            <option value="">-- Semua Kategori --</option>
                            <?php while ($kat = mysqli_fetch_assoc($kategori_query)): ?>
                                <option value="<?= $kat['id_kategori'] ?>" <?= ($kategori == $kat['id_kategori']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Grid Aset -->
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="grid-3">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="card card-shadow asset-card" data-category="<?= $row['id_kategori'] ?>" style="transition: all var(--transition-base);">
                                <div class="asset-image">
                                    <img src="<?= BASE_URL ?>assets/uploads/assets/<?= htmlspecialchars($row['foto'] ?: 'default.png') ?>"
                                        alt="<?= htmlspecialchars($row['nama_aset']); ?>"
                                        style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title asset-title" style="margin-bottom: var(--spacing-sm);"><?= htmlspecialchars($row['nama_aset']); ?></h5>
                                    <div class="asset-details" style="margin-bottom: var(--spacing-lg);">
                                        <p style="margin: 0; font-size: var(--font-size-sm); color: var(--text-muted);"><strong>Kode:</strong> <?= htmlspecialchars($row['kode_aset']); ?></p>
                                        <p style="margin: 0; font-size: var(--font-size-sm); color: var(--text-muted);"><strong>Kategori:</strong> <?= htmlspecialchars($row['nama_kategori']); ?></p>
                                        <p style="margin: 0; font-size: var(--font-size-sm); color: var(--text-muted);"><strong>Kondisi:</strong> <?= htmlspecialchars($row['kondisi']); ?></p>
                                    </div>
                                    <div class="asset-actions" style="display: flex; gap: var(--spacing-sm);">
                                        <a href="detail.php?id=<?= $row['id_aset']; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </a>
                                        <a href="request.php?id_aset=<?= $row['id_aset']; ?>"
                                            class="btn btn-sm btn-primary"
                                            title="Pinjam">
                                            <i class="fa-solid fa-box-arrow-in-right"></i> Pinjam
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Tidak ada aset yang tersedia untuk dipinjam</p>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Previous button
                        if ($page > 1) {
                            $prev_page = $page - 1;
                            $prev_url = "?page=$prev_page&kategori=" . urlencode($kategori);
                            echo "<a href='$prev_url' class='prev'>« Previous</a>";
                        }

                        // Page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            $first_url = "?page=1&kategori=" . urlencode($kategori);
                            echo "<a href='$first_url'>1</a>";
                            if ($start_page > 2) echo "<span class='dots'>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $page_url = "?page=$i&kategori=" . urlencode($kategori);
                            $active_class = ($i == $page) ? 'active' : '';
                            echo "<a href='$page_url' class='$active_class'>$i</a>";
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo "<span class='dots'>...</span>";
                            $last_url = "?page=$total_pages&kategori=" . urlencode($kategori);
                            echo "<a href='$last_url'>$total_pages</a>";
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $next_page = $page + 1;
                            $next_url = "?page=$next_page&kategori=" . urlencode($kategori);
                            echo "<a href='$next_url' class='next'>Next »</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const kategoriSelect = document.getElementById('kategoriSelect');
        const assetCards = document.querySelectorAll('.asset-card');

        function filterAssets() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = kategoriSelect.value;

            assetCards.forEach(card => {
                const name = card.querySelector('.asset-title').textContent.toLowerCase();
                const code = card.querySelector('.asset-details p:first-child').textContent.toLowerCase();
                const category = card.dataset.category;

                const matchesSearch = name.includes(searchTerm) || code.includes(searchTerm);
                const matchesCategory = !selectedCategory || category === selectedCategory;

                card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterAssets);
        kategoriSelect.addEventListener('change', filterAssets);
    });
</script>
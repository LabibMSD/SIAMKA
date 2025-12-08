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

// 🔎 Ambil filter kategori (opsional)
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

// 🔧 Query aset tersedia (kecuali yang hilang)
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

$query .= " ORDER BY a.nama_aset ASC";

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
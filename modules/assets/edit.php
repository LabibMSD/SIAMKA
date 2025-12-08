<?php
define("SECURE", true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/auth_check.php';
include '../../includes/role_check.php';
include '../../config/config.php';
include '../../config/database.php';

include '../../includes/notification_helper.php';
display_notification();

// Batasi akses: hanya admin & manajemen
checkRole(['admin', 'manajemen']);

// Ambil ID aset
if (!isset($_GET['id'])) {
    echo "<script>alert('ID aset tidak ditemukan'); window.location='index.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$aset_q = mysqli_query($conn, "SELECT * FROM assets WHERE id_aset=$id AND deleted_at IS NULL");
if (mysqli_num_rows($aset_q) == 0) {
    echo "<script>alert('Data aset tidak ditemukan'); window.location='index.php';</script>";
    exit;
}
$data = mysqli_fetch_assoc($aset_q);

$error = '';
$success = '';
$kondisi = $data['kondisi'] ?? ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_aset = mysqli_real_escape_string($conn, $_POST['nama_aset']);
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $kondisi = mysqli_real_escape_string($conn, $_POST['kondisi']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $tanggal_perolehan = mysqli_real_escape_string($conn, $_POST['tanggal_perolehan']);

    // --- Validasi wajib isi ---
    if (empty($nama_aset) || empty($id_kategori) || empty($lokasi) || empty($kondisi) || empty($status) || empty($harga) || empty($tanggal_perolehan)) {
        set_notification("error", "⚠️ Semua field wajib diisi!");
    } else {
        // --- Upload Foto ---
        $foto = $_FILES['foto']['name'];
        $tmp_name = $_FILES['foto']['tmp_name'];
        $upload_dir = '../../assets/uploads/assets/';
        $max_size = 2 * 1024 * 1024;
        $allowed_types = ['jpg', 'jpeg', 'png'];
        $new_filename = $data['foto']; // tetap pakai foto lama

        if (!empty($foto)) {
            $ext = strtolower(pathinfo($foto, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_types)) {
                set_notification("error", "❌ Format foto tidak valid (hanya JPG/PNG).");
            } elseif ($_FILES['foto']['size'] > $max_size) {
                set_notification("error", "❌ Ukuran foto maksimal 2MB.");
            } else {
                $new_filename = uniqid('AST_', true) . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                    // Hapus foto lama jika bukan default
                    if ($data['foto'] && $data['foto'] !== 'default.png') {
                        @unlink($upload_dir . $data['foto']);
                    }
                } else {
                    set_notification("error", "❌ Gagal mengunggah foto baru.");
                }
            }
        }

        // --- Hanya lanjut jika tidak ada notifikasi error ---
        if (!isset($_SESSION['notification']) || $_SESSION['notification']['type'] !== 'error') {
            $sql = "UPDATE assets SET 
                        nama_aset='$nama_aset',
                        id_kategori='$id_kategori',
                        lokasi='$lokasi',
                        kondisi='$kondisi',
                        status='$status',
                        harga='$harga',
                        tanggal_perolehan='$tanggal_perolehan',
                        keterangan='$keterangan',
                        foto='$new_filename',
                        updated_at=NOW()
                    WHERE id_aset=$id";

            if (mysqli_query($conn, $sql)) {
                set_notification("success", "✅ Perubahan data aset berhasil disimpan!");
                header("Location: index.php");
                exit();
            } else {
                set_notification("error", "❌ Gagal menyimpan perubahan: " . mysqli_error($conn));
            }
        }
    }

    // Jika ada error, update variabel $data supaya form tidak reset
    $data = array_merge($data, [
        'nama_aset' => $nama_aset,
        'id_kategori' => $id_kategori,
        'lokasi' => $lokasi,
        'kondisi' => $kondisi,
        'status' => $status,
        'harga' => $harga,
        'tanggal_perolehan' => $tanggal_perolehan,
        'keterangan' => $keterangan,
        'foto' => $new_filename
    ]);
}



include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
    <div class="card">
        <h1 class="page-title">✏️ Edit Data Aset</h1>

        <?php if ($error): ?>
            <div class="alert danger"><?= htmlspecialchars($error); ?></div>
        <?php elseif ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-group">
                <label>Kode Aset</label>
                <input type="text" name="kode_aset" value="<?= htmlspecialchars($data['kode_aset']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Nama Aset</label>
                <input type="text" name="nama_aset" value="<?= htmlspecialchars($data['nama_aset']); ?>" required>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php
                    $q = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");
                    while ($k = mysqli_fetch_assoc($q)) {
                        $sel = $k['id_kategori'] == $data['id_kategori'] ? 'selected' : '';
                        echo "<option value='{$k['id_kategori']}' $sel>{$k['nama_kategori']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Lokasi</label>
                <input type="text" name="lokasi" value="<?= htmlspecialchars($data['lokasi']); ?>" required>
            </div>

            <div class="form-group">
                <label>Kondisi</label>
                <select name="kondisi">
                    <option value="">-- Kondisi --</option>
                    <option value="baik" <?= $kondisi == 'baik' ? 'selected' : '' ?>>Baik</option>
                    <option value="rusak" <?= $kondisi == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                    <option value="hilang" <?= $kondisi == 'hilang' ? 'selected' : '' ?>>Hilang</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="Tersedia" <?= $data['status'] == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                    <option value="Dipinjam" <?= $data['status'] == 'Dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                    <option value="maintenance" <?= $data['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label>Harga</label>
                <input type="number" name="harga" value="<?= htmlspecialchars($data['harga']); ?>" required>
            </div>

            <div class="form-group">
                <label>Tanggal Perolehan</label>
                <input type="date" name="tanggal_perolehan" value="<?= htmlspecialchars($data['tanggal_perolehan']); ?>" required>
            </div>

            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="4"><?= htmlspecialchars($data['keterangan']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Foto Aset Saat Ini</label><br>
                <img id="previewImg" src="../../assets/uploads/assets/<?= $data['foto']; ?>" alt="Foto Aset"
                    style="max-width:200px; height:auto; border:1px solid #ccc; padding:2px; margin-bottom:10px;">
                <br>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png" onchange="previewNewImage(event)">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                <a href="index.php" class="btn btn-secondary">↩️ Kembali</a>
            </div>
        </form>
    </div>
</main>

<script>
    // Preview foto baru jika dipilih
    function previewNewImage(event) {
        const input = event.target;
        const preview = document.getElementById('previewImg');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>
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

checkRole(['admin', 'manajemen']);

// Auto-generate kode aset
$query = mysqli_query($conn, "SELECT kode_aset FROM assets ORDER BY id_aset DESC LIMIT 1");
$last = mysqli_fetch_assoc($query);
$kode_aset = $last ? 'AST-' . date('Y') . '-' . str_pad((int)substr($last['kode_aset'], -4) + 1, 4, '0', STR_PAD_LEFT)
  : 'AST-' . date('Y') . '-0001';

// Variabel default
$data = [
  'nama_aset' => '',
  'id_kategori' => '',
  'lokasi' => '',
  'kondisi' => '',
  'status' => '',
  'harga' => '',
  'tanggal_perolehan' => '',
  'keterangan' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Isi ulang variabel agar tidak hilang
  foreach ($data as $key => $value) {
    $data[$key] = mysqli_real_escape_string($conn, $_POST[$key] ?? '');
  }

  $foto = $_FILES['foto']['name'] ?? '';
  $tmp_name = $_FILES['foto']['tmp_name'] ?? '';
  $upload_dir = '../../assets/uploads/assets/';
  $max_size = 2 * 1024 * 1024; // 2MB
  $allowed_types = ['jpg', 'jpeg', 'png'];
  $new_filename = 'default.png';

  $upload_error = false;

  if (!empty($foto)) {
    $ext = strtolower(pathinfo($foto, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
      set_notification("error", "❌ Format foto tidak valid (hanya JPG/PNG).");
      $upload_error = true;
    } elseif ($_FILES['foto']['size'] > $max_size) {
      set_notification("error", "❌ Ukuran foto maksimal 2MB.");
      $upload_error = true;
    } else {
      $new_filename = uniqid('AST_', true) . '.' . $ext;
      if (!move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
        set_notification("error", "❌ Gagal mengunggah foto.");
        $upload_error = true;
      }
    }
  }

  // Jika tidak ada error upload
  if (!$upload_error) {
    $sql = "INSERT INTO assets 
                (kode_aset, nama_aset, id_kategori, lokasi, kondisi, status, harga, tanggal_perolehan, keterangan, foto)
                VALUES 
                ('$kode_aset', '{$data['nama_aset']}', '{$data['id_kategori']}', '{$data['lokasi']}', '{$data['kondisi']}', '{$data['status']}', '{$data['harga']}', '{$data['tanggal_perolehan']}', '{$data['keterangan']}', '$new_filename')";

    if (mysqli_query($conn, $sql)) {
      set_notification('success', '✅ Aset berhasil ditambahkan!');
      header("Location: index.php");
      exit;
    } else {
      set_notification('error', '❌ Gagal menyimpan data aset: ' . mysqli_error($conn));
    }
  }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="main-content">
  <div class="dashboard-container">
    <div class="page-header">
      <div class="page-header-content">
        <h1 class="page-title">🆕 Tambah Aset Baru</h1>
        <p class="page-subtitle">Tambahkan aset baru ke dalam sistem</p>
      </div>
    </div>

    <?php display_notification(); ?>

    <div class="card card-shadow">
      <form action="" method="POST" enctype="multipart/form-data" class="form-container">
        <div class="form-group">
          <label>Kode Aset</label>
          <input type="text" name="kode_aset" value="<?= htmlspecialchars($kode_aset); ?>" readonly>
        </div>

        <div class="form-group">
          <label>Nama Aset</label>
          <input type="text" name="nama_aset" value="<?= htmlspecialchars($data['nama_aset']); ?>" placeholder="Masukkan nama aset" required>
        </div>

        <div class="form-group">
          <label>Kategori</label>
          <select name="id_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <?php
            $q = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori");
            while ($k = mysqli_fetch_assoc($q)) {
              $selected = ($data['id_kategori'] == $k['id_kategori']) ? 'selected' : '';
              echo "<option value='{$k['id_kategori']}' $selected>{$k['nama_kategori']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" name="lokasi" value="<?= htmlspecialchars($data['lokasi']); ?>" placeholder="Masukkan lokasi aset" required>
        </div>

        <div class="form-group">
          <label>Kondisi</label>
          <select name="kondisi" required>
            <option value="baik" <?= $data['kondisi'] == 'baik' ? 'selected' : '' ?>>Baik</option>
            <option value="rusak" <?= $data['kondisi'] == 'rusak' ? 'selected' : '' ?>>Rusak</option>
            <option value="hilang" <?= $data['kondisi'] == 'hilang' ? 'selected' : '' ?>>Hilang</option>
          </select>
        </div>

        <div class="form-group">
          <label>Status</label>
          <select name="status" required>
            <option value="Tersedia" <?= $data['status'] == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
            <option value="Dipinjam" <?= $data['status'] == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
            <option value="Maintenance" <?= $data['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
          </select>
        </div>

        <div class="form-group">
          <label>Harga</label>
          <input type="number" name="harga" value="<?= htmlspecialchars($data['harga']); ?>" placeholder="Masukkan harga aset" required>
        </div>

        <div class="form-group">
          <label>Tanggal Perolehan</label>
          <input type="date" name="tanggal_perolehan" value="<?= htmlspecialchars($data['tanggal_perolehan']); ?>" required>
        </div>

        <div class="form-group">
          <label>Keterangan</label>
          <textarea name="keterangan" rows="4" placeholder="Tuliskan detail atau kondisi aset..."><?= htmlspecialchars($data['keterangan']); ?></textarea>
        </div>

        <div class="form-group">
          <label>Foto Aset</label>
          <input type="file" name="foto" accept=".jpg,.jpeg,.png" onchange="previewImage(event)">
          <div id="fotoPreview" style="margin-top:10px;">
            <img id="previewImg" src="../../assets/uploads/assets/default.png" alt="Preview Foto"
              style="max-width:200px; height:auto; display:none; border:1px solid #ccc; padding:2px;">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">💾 Simpan</button>
          <a href="index.php" class="btn btn-secondary">↩️ Kembali</a>
        </div>
      </form>
    </div> <!-- card -->
  </div> <!-- dashboard-container -->
</main>

<script>
  function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      }
      reader.readAsDataURL(input.files[0]);
    } else {
      preview.src = '';
      preview.style.display = 'none';
    }
  }
</script>

<?php include '../../includes/footer.php'; ?>
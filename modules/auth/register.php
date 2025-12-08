<?php
session_start();
include '../../config/database.php';
include '../../includes/functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp  = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $password = $_POST['password'];

    // Role default untuk pendaftar baru
    $role = 'pengguna';

    // Cek apakah email sudah terdaftar
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "<div class='alert error'>⚠️ Email sudah terdaftar!</div>";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (nama, email, no_telp, password, role, status) 
                VALUES ('$nama', '$email', '$no_telp', '$hashedPassword', '$role', 'aktif')";

        if ($conn->query($sql)) {
            $message = "<div class='alert success'>✅ Registrasi berhasil! Silakan login.</div>";
            header("refresh:2; url=login.php");
        } else {
            $message = "<div class='alert error'>❌ Gagal menyimpan data.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar - SIAMKA</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
</head>

<body>
    <div class="overlay"></div>
    <div class="login-container">
        <img src="../../assets/images/icons/logo.png" alt="Logo SIAMKA">
        <h1>Registrasi Akun</h1>
        <p>Buat akun baru SIAMKA</p>

        <form method="POST">
            <?= $message ?>
            <input type="text" name="nama" placeholder="Nama Lengkap" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="no_telp" placeholder="Nomor Telepon" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Daftar</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            Login disini!
            <a href="login.php" style="color:#007bff; text-decoration:none; font-weight:500;">
                Klik disini
            </a>
        </p>
    </div>
</body>

</html>
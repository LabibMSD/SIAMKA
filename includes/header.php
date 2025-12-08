<?php
if (!defined("SECURE")) {
    die("Direct access not allowed");
}

require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? "Dashboard") ?> - <?= SITE_NAME ?></title>

    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- 🎨 MODERN DESIGN SYSTEM CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/design-system.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/navbar-sidebar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard-modern.css">

    <!-- Legacy CSS for backward compatibility -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/global.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/assets.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <!-- Additional CSS per page -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL . $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">

        <!-- ================================================
             🔷 NAVBAR / TOP HEADER - STICKY & MODERN
             ================================================ -->
        <nav class="navbar-main" role="navigation" aria-label="Main navigation">
            <div class="navbar-content">

                <!-- LEFT: BRAND / LOGO -->
                <a href="<?= BASE_URL ?>modules/dashboard/<?= $_SESSION['role'] ?? 'user' ?>.php" class="navbar-brand">
                    <!-- Logo Image -->
                    <img src="<?= BASE_URL ?>assets/images/logo2.png" alt="SIAMKA Logo">

                    <!-- Brand Text -->
                    <div class="navbar-brand-text">
                        <span class="main"><?= SITE_NAME ?></span>
                        <span class="sub">Asset Management</span>
                    </div>
                </a>

                <!-- RIGHT: ACTIONS & USER INFO -->
                <div class="navbar-right">

                    <!-- USER INFO -->
                    <div class="user-info">
                        <!-- User Avatar -->
                        <img src="<?= !empty($_SESSION['foto'])
                                        ? BASE_URL . 'uploads/users/' . $_SESSION['foto']
                                        : BASE_URL . 'assets/images/default-user.png'; ?>"
                            alt="<?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>"
                            class="user-avatar"
                            title="<?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>">

                        <!-- User Text Info (visible on desktop) -->
                        <div class="user-info-text">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></span>
                            <span class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Guest') ?></span>
                        </div>
                    </div>

                    <!-- NAVBAR ACTIONS -->
                    <div class="navbar-actions">
                        <!-- Profile Button -->
                        <a href="<?= BASE_URL ?>modules/users/profile.php"
                            class="btn btn-profile"
                            title="Lihat profil">
                            <i class="fa-solid fa-user"></i>
                            <span class="d-none d-sm-inline">Profile</span>
                        </a>

                        <!-- Logout Button -->
                        <a href="<?= BASE_URL ?>modules/auth/logout.php"
                            class="btn btn-logout"
                            title="Keluar">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="d-none d-sm-inline">Logout</span>
                        </a>
                    </div>

                    <!-- SIDEBAR TOGGLE (Mobile) -->
                    <button class="sidebar-toggle"
                        aria-label="Toggle sidebar"
                        title="Buka/Tutup menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                </div>

            </div>
        </nav>

        <!-- Alert Container -->
        <div class="alerts-container"></div>
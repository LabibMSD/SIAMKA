-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 08 Des 2025 pada 14.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siamka`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `assets`
--

CREATE TABLE `assets` (
  `id_aset` int(11) NOT NULL,
  `kode_aset` varchar(50) NOT NULL,
  `nama_aset` varchar(100) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `kondisi` enum('baik','rusak','hilang') DEFAULT 'baik',
  `status` enum('tersedia','dipinjam','maintenance','dihapus') DEFAULT 'tersedia',
  `harga` decimal(12,2) DEFAULT NULL,
  `tanggal_perolehan` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.png',
  `keterangan` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `assets`
--

INSERT INTO `assets` (`id_aset`, `kode_aset`, `nama_aset`, `id_kategori`, `lokasi`, `kondisi`, `status`, `harga`, `tanggal_perolehan`, `foto`, `keterangan`, `deleted_at`, `created_at`, `updated_at`) VALUES
(30, 'AST-2025-0001', 'Stop kontak', 13, 'Kelas D2.11', 'baik', 'dipinjam', 25.00, '2025-12-15', 'AST_6935819eb5e7c4.48048605.jpg', 'Aman', NULL, '2025-12-07 13:31:10', '2025-12-08 13:01:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(13, 'Elektronik', 'Ini elektronik'),
(14, 'Perabotan', 'Ini deskripsi untuk perabotan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `damage_reports`
--

CREATE TABLE `damage_reports` (
  `id_laporan` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_aset` int(11) DEFAULT NULL,
  `tanggal_lapor` date DEFAULT curdate(),
  `deskripsi` text DEFAULT NULL,
  `status` enum('baru','diproses','selesai') DEFAULT 'baru'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `damage_reports`
--

INSERT INTO `damage_reports` (`id_laporan`, `id_user`, `id_aset`, `tanggal_lapor`, `deskripsi`, `status`) VALUES
(1, 19, 30, '2025-12-08', 'rusak stop kontak nya', 'diproses');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loans`
--

CREATE TABLE `loans` (
  `id_peminjaman` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_aset` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','returned') DEFAULT 'pending',
  `feedback` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loans`
--

INSERT INTO `loans` (`id_peminjaman`, `id_user`, `id_aset`, `start_date`, `end_date`, `returned_at`, `status`, `feedback`, `created_at`) VALUES
(1, 19, 30, '2025-12-07', '2025-12-31', '2025-12-08 19:54:12', 'returned', '', '2025-12-07 13:47:17'),
(2, 19, 30, '2025-12-08', '2025-12-16', NULL, 'approved', '', '2025-12-08 12:55:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `maintenance_history`
--

CREATE TABLE `maintenance_history` (
  `id_history` int(11) NOT NULL,
  `id_aset` int(11) DEFAULT NULL,
  `tanggal_perawatan` date DEFAULT NULL,
  `biaya` decimal(12,2) DEFAULT NULL,
  `teknisi` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status_aset_setelah_perawatan` enum('baik','rusak','hilang') DEFAULT 'baik'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `maintenance_history`
--

INSERT INTO `maintenance_history` (`id_history`, `id_aset`, `tanggal_perawatan`, `biaya`, `teknisi`, `deskripsi`, `status_aset_setelah_perawatan`) VALUES
(1, 30, '2025-12-08', 0.00, 'gg', '', 'baik');

-- --------------------------------------------------------

--
-- Struktur dari tabel `maintenance_schedule`
--

CREATE TABLE `maintenance_schedule` (
  `id_jadwal` int(11) NOT NULL,
  `id_petugas` int(11) DEFAULT NULL,
  `id_aset` int(11) DEFAULT NULL,
  `tanggal_jadwal` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('terjadwal','selesai','dibatalkan') DEFAULT 'terjadwal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `maintenance_schedule`
--

INSERT INTO `maintenance_schedule` (`id_jadwal`, `id_petugas`, `id_aset`, `tanggal_jadwal`, `keterangan`, `status`) VALUES
(1, 18, 30, '2025-12-08', '', 'selesai');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','pengguna','manajemen') DEFAULT 'pengguna',
  `no_telp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `no_telp`, `foto`, `status`, `deleted_at`) VALUES
(17, 'BANG OGA', 'yoga@siamka.com', '$2y$10$yQG9TxpKZE4NUuFMy7IiH.0yniGIHEVBEaRX97JLDo70yS6W5lN1m', 'admin', '0000000000', '692d85a71f25b.png', 'aktif', '2025-12-02 06:54:38'),
(18, 'PAK OGA', 'manajemen@siamka.com', '$2y$10$hdzkz/lTGdrf7om2.pFQOeajYD6F6PEDjrNvGq2fcdoh2Zoqt2d0u', 'manajemen', '081443239444', '1765113755_Manajemen.jpeg', 'aktif', NULL),
(19, 'OGA', 'pengguna@siamka.com', '$2y$10$zJG6ldr4fPuc7dP9rBLBbOEaHuTuCie5VokXi0sY8yZYLodIz9K6C', 'pengguna', '081332482333', '1765114292_Pengguna.jpeg', 'aktif', NULL),
(37, 'alif', 'alif123@siamka.com', '$2y$10$8jPyQvVVWhxXwcXzKtGto.nZQvGcCVB/PKnbwCUdhLZYMLc0DHije', 'pengguna', '081332129234', '1761817564_WhatsApp Image 2023-10-10 at 21.36.29_5c935d45.jpg', 'aktif', '2025-12-02 06:49:42'),
(46, 'MAS YOGA', 'admin@siamka.com', '$2y$10$zXxhhBiHvMLX5TbTly9GxurfN41gZsV9TM0o43Qss7Naa17zrVPgC', 'admin', '0000000000', '1765111533_Admin.jpeg', 'aktif', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id_aset`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `damage_reports`
--
ALTER TABLE `damage_reports`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_admin` (`id_petugas`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `assets`
--
ALTER TABLE `assets`
  MODIFY `id_aset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `damage_reports`
--
ALTER TABLE `damage_reports`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `loans`
--
ALTER TABLE `loans`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `maintenance_history`
--
ALTER TABLE `maintenance_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_category` FOREIGN KEY (`id_kategori`) REFERENCES `categories` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `damage_reports`
--
ALTER TABLE `damage_reports`
  ADD CONSTRAINT `fk_damage_asset` FOREIGN KEY (`id_aset`) REFERENCES `assets` (`id_aset`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_damage_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_asset` FOREIGN KEY (`id_aset`) REFERENCES `assets` (`id_aset`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_loans_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD CONSTRAINT `fk_history_asset` FOREIGN KEY (`id_aset`) REFERENCES `assets` (`id_aset`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD CONSTRAINT `fk_petugas_user` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_admin` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_asset` FOREIGN KEY (`id_aset`) REFERENCES `assets` (`id_aset`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

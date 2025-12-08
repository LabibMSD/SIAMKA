-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 08 Des 2025 pada 15.21
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
(30, 'AST-2025-0001', 'Stop kontak', 13, 'Kelas D2.11', 'baik', 'dipinjam', 25.00, '2025-12-15', 'AST_6935819eb5e7c4.48048605.jpg', 'Aman', NULL, '2025-12-07 13:31:10', '2025-12-07 13:55:30'),
(31, 'AST-2025-0002', 'Laptop', 13, 'Lab informatika', 'baik', 'tersedia', 10.00, '2025-12-10', 'AST_69358e26b7bb57.35611803.jpg', 'Baik', NULL, '2025-12-07 14:24:38', '2025-12-08 14:17:39'),
(32, 'AST-2025-0003', 'keyboard', 13, 'Lab informatika', 'baik', 'tersedia', 10.00, '2025-12-18', 'AST_69358e8b027240.20015324.jpg', 'Sedikit Berfungsi', NULL, '2025-12-07 14:26:19', '2025-12-07 14:26:19'),
(33, 'AST-2025-0004', 'Pensil 2B', 15, 'Ruang TU', 'baik', 'tersedia', 5.00, '2025-12-09', 'AST_69358f03b5e6a1.93177645.jpg', 'Baik', NULL, '2025-12-07 14:28:19', '2025-12-07 14:28:19'),
(34, 'AST-2025-0005', 'Kertas Portofolio', 15, 'Ruang TU', 'baik', 'tersedia', 3.00, '2025-12-17', 'AST_69358f64e8d689.94088275.jpg', 'Baik', NULL, '2025-12-07 14:29:56', '2025-12-07 14:49:37'),
(35, 'AST-2025-0006', 'Papan Ajar', 15, 'Lab informatika', 'baik', 'tersedia', 10.00, '2025-12-11', 'AST_69358fa7ce7302.43482463.jpg', 'Baik', NULL, '2025-12-07 14:31:03', '2025-12-07 14:31:03'),
(36, 'AST-2025-0007', 'Meja + Kursi', 14, 'Lab informatika', 'baik', 'tersedia', 15.00, '2025-12-11', 'AST_693590273afc80.69378955.jpg', 'Aman', NULL, '2025-12-07 14:33:11', '2025-12-07 14:33:11'),
(37, 'AST-2025-0008', 'AC', 14, 'Ruang Inventaris Kampus', 'baik', 'tersedia', 50.00, '2025-12-10', 'AST_6935908e688530.19996659.jpg', 'Baik', NULL, '2025-12-07 14:34:54', '2025-12-07 14:34:54'),
(38, 'AST-2025-0009', 'HDMI', 14, 'Lab informatika', 'baik', 'dipinjam', 10.00, '2025-12-12', 'AST_6935915644e7d2.71776851.jpg', 'Baik', NULL, '2025-12-07 14:37:12', '2025-12-07 14:49:00'),
(39, 'AST-2025-0010', 'Obeng', 14, 'Ruang Inventaris Kampus', 'baik', 'tersedia', 5.00, '2025-12-12', 'AST_693591adb6f4d1.96891632.jpg', 'Baik', NULL, '2025-12-07 14:39:41', '2025-12-07 14:39:41'),
(40, 'AST-2025-0011', 'Spidol', 15, 'Ruang TU', 'baik', 'tersedia', 8.00, '2025-12-12', 'AST_693591f3e20d32.36299581.jpg', 'Baik', NULL, '2025-12-07 14:40:51', '2025-12-07 14:40:51'),
(41, 'AST-2025-0012', 'Penghapus Papan', 15, 'Ruang TU', 'baik', 'tersedia', 5.00, '2025-12-13', 'AST_69359246879357.69758244.png', 'Baik', NULL, '2025-12-07 14:42:14', '2025-12-07 14:42:14'),
(42, 'AST-2025-0013', 'TANGGA', 14, 'Ruang Inventaris Kampus', 'baik', 'tersedia', 10.00, '2025-12-12', 'AST_6936caeb244643.88747282.jpg', 'Baik', NULL, '2025-12-08 12:56:11', '2025-12-08 12:56:11'),
(43, 'AST-2025-0014', 'Spiker', 13, 'Ruang TU', 'baik', 'tersedia', 5.00, '2025-12-09', 'AST_6936cb1b90ab15.68176872.jpg', 'Baik', NULL, '2025-12-08 12:56:59', '2025-12-08 12:56:59'),
(44, 'AST-2025-0015', 'Mouse', 13, 'Lab informatika', 'baik', 'tersedia', 3.00, '2025-12-09', 'AST_6936cb776020c8.84796718.png', 'Baik', NULL, '2025-12-08 12:58:31', '2025-12-08 12:58:31'),
(45, 'AST-2025-0016', 'Stabilo', 15, 'Ruang TU', 'baik', 'tersedia', 3.00, '2025-12-10', 'AST_6936cba4a7a751.39560809.jpg', 'Baik', NULL, '2025-12-08 12:59:16', '2025-12-08 12:59:16'),
(46, 'AST-2025-0017', 'Hardisk', 13, 'Lab informatika', 'baik', 'tersedia', 5.00, '2025-12-09', 'AST_6936cbd6518855.79309671.jpg', 'Baik', NULL, '2025-12-08 13:00:06', '2025-12-08 13:00:06'),
(47, 'AST-2025-0018', 'Penggaris', 15, 'Ruang TU', 'baik', 'tersedia', 2.00, '2025-12-09', 'AST_6936cc0364a357.69706669.jpg', 'Aman', NULL, '2025-12-08 13:00:51', '2025-12-08 13:00:51'),
(48, 'AST-2025-0019', 'Komputer', 13, 'Lab informatika', 'baik', 'tersedia', 20.00, '2025-12-09', 'AST_6936cc46a2dc60.95393923.jpg', 'Baik', NULL, '2025-12-08 13:01:58', '2025-12-08 13:01:58'),
(49, 'AST-2025-0020', 'Penghapus Buku', 15, 'Ruang TU', 'baik', 'tersedia', 2.00, '2025-12-10', 'AST_6936cc71bab255.98094577.jpg', 'Baik', NULL, '2025-12-08 13:02:41', '2025-12-08 13:02:41');

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
(14, 'Perabotan', 'Ini deskripsi untuk perabotan'),
(15, 'Alat Tulis', 'Aman');

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
(1, 19, 30, '2025-12-07', 'stop kontak tidak berfungsi', 'baru');

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
(1, 19, 30, '2025-12-07', '2025-12-31', NULL, 'approved', '', '2025-12-07 13:47:17'),
(2, 19, 38, '2025-12-13', '2025-12-18', NULL, 'approved', '', '2025-12-07 14:47:19'),
(3, 19, 31, '2025-12-19', '2025-12-20', '2025-12-08 21:17:39', 'returned', 'laptop nya ngelaq parah', '2025-12-07 14:47:36'),
(4, 19, 34, '2025-12-10', '2025-12-12', '2025-12-07 21:49:37', 'returned', '', '2025-12-07 14:47:56');

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
(18, 'PAK OGA', 'manajemen@siamka.com', '$2y$10$hdzkz/lTGdrf7om2.pFQOeajYD6F6PEDjrNvGq2fcdoh2Zoqt2d0u', 'manajemen', '0881026489264', '1765113755_Manajemen.jpeg', 'aktif', NULL),
(19, 'Diego Costa', 'pengguna@siamka.com', '$2y$10$zJG6ldr4fPuc7dP9rBLBbOEaHuTuCie5VokXi0sY8yZYLodIz9K6C', 'pengguna', '082131868592', '1765199148_diego.jpg', 'aktif', NULL),
(37, 'alif', 'alif123@siamka.com', '$2y$10$8jPyQvVVWhxXwcXzKtGto.nZQvGcCVB/PKnbwCUdhLZYMLc0DHije', 'pengguna', '081332129234', '1761817564_WhatsApp Image 2023-10-10 at 21.36.29_5c935d45.jpg', 'aktif', '2025-12-02 06:49:42'),
(46, 'MAS YOGA', 'admin@siamka.com', '$2y$10$zXxhhBiHvMLX5TbTly9GxurfN41gZsV9TM0o43Qss7Naa17zrVPgC', 'admin', '085236313701', '1765111533_Admin.jpeg', 'aktif', NULL);

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
  MODIFY `id_aset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `damage_reports`
--
ALTER TABLE `damage_reports`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `loans`
--
ALTER TABLE `loans`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `maintenance_history`
--
ALTER TABLE `maintenance_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT;

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

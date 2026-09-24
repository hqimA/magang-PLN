-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 24, 2026 at 06:46 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_magangpln`
--

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` bigint NOT NULL,
  `plat_nomor` varchar(15) NOT NULL,
  `merk_tipe` varchar(100) NOT NULL,
  `tahun_pembuatan` int NOT NULL,
  `transmisi` enum('MANUAL','OTOMATIS') NOT NULL,
  `jenis_bbm` enum('BENSIN','SOLAR','DIESEL','LISTRIK') NOT NULL,
  `kategori_penggunaan` enum('PEJABAT','TEKNISI','ANGKUT_BARANG','MOTOR_OPERASIONAL') NOT NULL,
  `kilometer_terakhir` int NOT NULL DEFAULT '0',
  `tanggal_pembelian` date NOT NULL,
  `status_perawatan` enum('BAIK','PERLU_SERVIS','SEDANG_SERVIS','RUSAK') DEFAULT 'BAIK',
  `id_pengelola` bigint NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_kerusakan`
--

CREATE TABLE `laporan_kerusakan` (
  `id` bigint NOT NULL,
  `id_kendaraan` bigint NOT NULL,
  `id_pelapor` bigint NOT NULL,
  `tanggal_kejadian` timestamp NOT NULL,
  `lokasi_kejadian` varchar(255) NOT NULL,
  `deskripsi_kerusakan` text NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `tingkat_kerusakan` enum('RINGAN','SEDANG','BERAT') NOT NULL,
  `status_penanganan` enum('DILAPORKAN','SEDANG_DIPERBAIKI','SELESAI') DEFAULT 'DILAPORKAN'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` bigint NOT NULL,
  `id_pengguna` bigint NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `sudah_dibaca` tinyint(1) DEFAULT '0',
  `tipe_referensi` varchar(50) DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_servis`
--

CREATE TABLE `pengajuan_servis` (
  `id` bigint NOT NULL,
  `id_kendaraan` bigint NOT NULL,
  `id_pengaju` bigint NOT NULL,
  `jenis_pengajuan` enum('RUTIN','DARURAT') NOT NULL,
  `deskripsi_keluhan` text NOT NULL,
  `estimasi_biaya` decimal(12,2) DEFAULT NULL,
  `status_persetujuan` enum('MENUNGGU','DISETUJUI','DITOLAK') DEFAULT 'MENUNGGU',
  `alasan_penolakan` text,
  `id_disetujui_oleh` bigint DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id` bigint NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `kata_sandi` varchar(255) NOT NULL,
  `peran` enum('ADMIN','PENGELOLA') NOT NULL,
  `dibuat_pada` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rincian_sparepart`
--

CREATE TABLE `rincian_sparepart` (
  `id` bigint NOT NULL,
  `id_riwayat_servis` bigint NOT NULL,
  `nama_sparepart` varchar(150) NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_perbaikan`
--

CREATE TABLE `riwayat_perbaikan` (
  `id` bigint NOT NULL,
  `id_laporan_kerusakan` bigint NOT NULL,
  `tanggal_perbaikan` date NOT NULL,
  `nama_bengkel` varchar(150) NOT NULL,
  `ringkasan_perbaikan` text NOT NULL,
  `total_biaya_perbaikan` decimal(12,2) NOT NULL,
  `id_pembuat` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_servis`
--

CREATE TABLE `riwayat_servis` (
  `id` bigint NOT NULL,
  `id_pengajuan` bigint DEFAULT NULL,
  `id_kendaraan` bigint NOT NULL,
  `tanggal_servis` date NOT NULL,
  `kilometer_servis` int NOT NULL,
  `nama_bengkel` varchar(150) NOT NULL,
  `total_biaya` decimal(12,2) NOT NULL DEFAULT '0.00',
  `foto_nota` varchar(255) DEFAULT NULL,
  `target_kilometer_berikutnya` int DEFAULT NULL,
  `id_pembuat` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plat_nomor` (`plat_nomor`),
  ADD KEY `fk_kendaraan_pengelola` (`id_pengelola`);

--
-- Indexes for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_laporan_kendaraan` (`id_kendaraan`),
  ADD KEY `fk_laporan_pelapor` (`id_pelapor`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifikasi_pengguna` (`id_pengguna`);

--
-- Indexes for table `pengajuan_servis`
--
ALTER TABLE `pengajuan_servis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengajuan_kendaraan` (`id_kendaraan`),
  ADD KEY `fk_pengajuan_pengaju` (`id_pengaju`),
  ADD KEY `fk_pengajuan_disetujui` (`id_disetujui_oleh`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rincian_sparepart`
--
ALTER TABLE `rincian_sparepart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rincian_servis` (`id_riwayat_servis`);

--
-- Indexes for table `riwayat_perbaikan`
--
ALTER TABLE `riwayat_perbaikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_perbaikan_laporan` (`id_laporan_kerusakan`),
  ADD KEY `fk_perbaikan_pembuat` (`id_pembuat`);

--
-- Indexes for table `riwayat_servis`
--
ALTER TABLE `riwayat_servis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_riwayat_pengajuan` (`id_pengajuan`),
  ADD KEY `fk_riwayat_kendaraan` (`id_kendaraan`),
  ADD KEY `fk_riwayat_pembuat` (`id_pembuat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan_servis`
--
ALTER TABLE `pengajuan_servis`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rincian_sparepart`
--
ALTER TABLE `rincian_sparepart`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riwayat_perbaikan`
--
ALTER TABLE `riwayat_perbaikan`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riwayat_servis`
--
ALTER TABLE `riwayat_servis`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `fk_kendaraan_pengelola` FOREIGN KEY (`id_pengelola`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `laporan_kerusakan`
--
ALTER TABLE `laporan_kerusakan`
  ADD CONSTRAINT `fk_laporan_kendaraan` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_laporan_pelapor` FOREIGN KEY (`id_pelapor`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notifikasi_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pengajuan_servis`
--
ALTER TABLE `pengajuan_servis`
  ADD CONSTRAINT `fk_pengajuan_disetujui` FOREIGN KEY (`id_disetujui_oleh`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengajuan_kendaraan` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengajuan_pengaju` FOREIGN KEY (`id_pengaju`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `rincian_sparepart`
--
ALTER TABLE `rincian_sparepart`
  ADD CONSTRAINT `fk_rincian_servis` FOREIGN KEY (`id_riwayat_servis`) REFERENCES `riwayat_servis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_perbaikan`
--
ALTER TABLE `riwayat_perbaikan`
  ADD CONSTRAINT `fk_perbaikan_laporan` FOREIGN KEY (`id_laporan_kerusakan`) REFERENCES `laporan_kerusakan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_perbaikan_pembuat` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_servis`
--
ALTER TABLE `riwayat_servis`
  ADD CONSTRAINT `fk_riwayat_kendaraan` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_pembuat` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_pengajuan` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan_servis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

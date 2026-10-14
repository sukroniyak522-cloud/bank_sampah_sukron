-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bank_sampah_sukron`
--
CREATE DATABASE IF NOT EXISTS `bank_sampah_sukron` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bank_sampah_sukron`;
--

-- --------------------------------------------------------

--
-- Table structure for table `sampah`
--

DROP TABLE IF EXISTS `sampah`;
CREATE TABLE `sampah` (
  `id_sampah` int(11) NOT NULL,
  `jenis_sampah` varchar(100) NOT NULL,
  `harga_per_kg` int(11) NOT NULL,
  `stok_kg` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sampah`
--

INSERT INTO `sampah` (`id_sampah`, `jenis_sampah`, `harga_per_kg`, `stok_kg`) VALUES
(2, 'botol plastik', 4500, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tarik_saldo`
--

DROP TABLE IF EXISTS `tarik_saldo`;
CREATE TABLE `tarik_saldo` (
  `id_tarik` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nominal` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `tanggal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tarik_saldo`
--

INSERT INTO `tarik_saldo` (`id_tarik`, `id_user`, `nominal`, `status`, `tanggal`) VALUES
(5, 1, 2000, 'sukses', '2026-09-24 13:59:04');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_sampah` int(11) NOT NULL,
  `tipe` enum('setor','jual_pengepul') NOT NULL,
  `berat_kg` bigint(200) NOT NULL,
  `total_rp` bigint(200) NOT NULL,
  `tanggal` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_user`, `id_sampah`, `tipe`, `berat_kg`, `total_rp`, `tanggal`, `status`) VALUES
(22, 1, 2, 'setor', 2, 9000, '2026-09-28 14:56:59', 'pending'),
(23, 1, 2, 'setor', 5, 22500, '2026-09-29 10:56:23', 'pending'),
(24, 1, 2, 'setor', 1, 4500, '2026-09-29 10:57:16', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','guru','pengepul','admin') NOT NULL,
  `saldo` bigint(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `saldo`) VALUES
(1, 'Udin', 'udin', 'sukron', 'siswa', 25000),
(3, 'Rapi', 'rapi', 'sukron', 'guru', 0),
(4, 'Sukron', 'sukron', 'sukron', 'admin', 18000),
(5, 'Budi', 'budi', 'sukron', 'pengepul', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sampah`
--
ALTER TABLE `sampah`
  ADD PRIMARY KEY (`id_sampah`);

--
-- Indexes for table `tarik_saldo`
--
ALTER TABLE `tarik_saldo`
  ADD PRIMARY KEY (`id_tarik`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_sampah` (`id_sampah`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sampah`
--
ALTER TABLE `sampah`
  MODIFY `id_sampah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tarik_saldo`
--
ALTER TABLE `tarik_saldo`
  MODIFY `id_tarik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

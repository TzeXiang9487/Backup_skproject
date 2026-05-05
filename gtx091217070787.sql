-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 02:57 AM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 5.6.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gtx091217070787`
--

-- --------------------------------------------------------

--
-- Table structure for table `calon`
--

CREATE TABLE `calon` (
  `idCalon` varchar(15) NOT NULL,
  `namaCalon` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `calon`
--

INSERT INTO `calon` (`idCalon`, `namaCalon`) VALUES
('C01', 'Hollow Knight'),
('C02', 'NineSols'),
('C03', 'Cuphead');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `idKelas` varchar(10) NOT NULL,
  `kelas` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`idKelas`, `kelas`) VALUES
('K01', '4T1'),
('K02', '4T2');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `noKP` varchar(15) NOT NULL,
  `katalaluan` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`noKP`, `katalaluan`) VALUES
('090214-07-1234', '123'),
('090505-07-4455', '123'),
('090629-07-8899', '123'),
('090715-07-3344', '123'),
('090803-07-5522', '123'),
('090909-09-0909', '12345'),
('090917-07-2211', '123'),
('091028-07-5678', '123'),
('091102-07-6633', '123'),
('091120-07-9988', '123'),
('091217-07-0787', 'xiang1217'),
('091231-07-7766', '123');

-- --------------------------------------------------------

--
-- Table structure for table `pengundi`
--

CREATE TABLE `pengundi` (
  `noKP` varchar(15) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `idKelas` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pengundi`
--

INSERT INTO `pengundi` (`noKP`, `nama`, `idKelas`) VALUES
('090214-07-1234', 'Lee Wei Jie', 'K01'),
('090505-07-4455', 'Chong Yi Hong', 'K02'),
('090629-07-8899', 'Goh Jia Wei', 'K01'),
('090715-07-3344', 'Lim Jia Hui', 'K01'),
('090803-07-5522', 'Chan Li Ting', 'K02'),
('090909-09-0909', 'tang yong ting', 'K01'),
('090917-07-2211', 'Ong Wei Han', 'K02'),
('091028-07-5678', 'Tan Yi Xin', 'K02'),
('091102-07-6633', 'Teh Yu En', 'K01'),
('091120-07-9988', 'Ng Xuan Yu', 'K01'),
('091217-07-0787', 'Goh Tze Xiang', 'K01'),
('091231-07-7766', 'Wong Zhi Yuan', 'K01');

-- --------------------------------------------------------

--
-- Table structure for table `pengundian`
--

CREATE TABLE `pengundian` (
  `noKP` varchar(15) NOT NULL,
  `tarikh` date NOT NULL,
  `idCalon` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pengundian`
--

INSERT INTO `pengundian` (`noKP`, `tarikh`, `idCalon`) VALUES
('090214-07-1234', '2025-08-18', 'C01'),
('090214-07-1234', '2026-02-06', 'C01'),
('090629-07-8899', '2025-08-20', 'C01'),
('091120-07-9988', '2025-08-19', 'C01'),
('091231-07-7766', '2025-08-20', 'C01'),
('090715-07-3344', '2025-08-19', 'C02'),
('091028-07-5678', '2025-08-18', 'C02'),
('091102-07-6633', '2025-08-21', 'C02'),
('090505-07-4455', '2025-08-19', 'C03'),
('090803-07-5522', '2025-08-20', 'C03'),
('090917-07-2211', '2025-08-21', 'C03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calon`
--
ALTER TABLE `calon`
  ADD PRIMARY KEY (`idCalon`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`idKelas`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`noKP`);

--
-- Indexes for table `pengundi`
--
ALTER TABLE `pengundi`
  ADD PRIMARY KEY (`noKP`),
  ADD KEY `idKelas` (`idKelas`);

--
-- Indexes for table `pengundian`
--
ALTER TABLE `pengundian`
  ADD PRIMARY KEY (`noKP`,`tarikh`),
  ADD KEY `idCalon` (`idCalon`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `pengguna_ibfk_1` FOREIGN KEY (`noKP`) REFERENCES `pengundi` (`noKP`) ON DELETE CASCADE;

--
-- Constraints for table `pengundi`
--
ALTER TABLE `pengundi`
  ADD CONSTRAINT `pengundi_ibfk_1` FOREIGN KEY (`idKelas`) REFERENCES `kelas` (`idKelas`);

--
-- Constraints for table `pengundian`
--
ALTER TABLE `pengundian`
  ADD CONSTRAINT `pengundian_ibfk_1` FOREIGN KEY (`noKP`) REFERENCES `pengundi` (`noKP`),
  ADD CONSTRAINT `pengundian_ibfk_2` FOREIGN KEY (`idCalon`) REFERENCES `calon` (`idCalon`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

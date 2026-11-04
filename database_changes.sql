-- Tabel pendukung cetak hasil laboratorium & respons time
CREATE TABLE IF NOT EXISTS `detail_cetak_lab` (
  `no_rawat` varchar(17) NOT NULL,
  `tgl_registrasi` varchar(25) DEFAULT '',
  `tgl_periksa` varchar(25) DEFAULT '',
  `saran` varchar(300) NOT NULL DEFAULT '',
  `kesan` varchar(300) NOT NULL DEFAULT '',
  `tgl_selesai` varchar(20) DEFAULT '',
  `tgl_validasi` varchar(20) DEFAULT '',
  `no_lab` varchar(50) DEFAULT '',
  `respons_time` varchar(50) DEFAULT '',
  `jenis_layanan` varchar(50) DEFAULT '',
  `petugas_pelaksana` varchar(100) DEFAULT '',
  PRIMARY KEY (`no_rawat`),
  CONSTRAINT `detail_cetak_lab_ibfk_1` FOREIGN KEY (`no_rawat`) REFERENCES `reg_periksa` (`no_rawat`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Tabel pendukung rincian pembayaran kasir rawat jalan & cicilan bertahap
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mlite_billing_rincian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kd_billing` varchar(100) NOT NULL,
  `no_rawat` varchar(17) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tagihan` double NOT NULL DEFAULT 0,
  `bayar` double NOT NULL DEFAULT 0,
  `sisa` double NOT NULL DEFAULT 0,
  `tgl_billing` date NOT NULL,
  `jam_billing` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kd_billing` (`kd_billing`),
  KEY `no_rawat` (`no_rawat`),
  KEY `kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Kolom No. SIP pada tabel petugas untuk cetak hasil laboratorium & layanan klinis
-- --------------------------------------------------------
ALTER TABLE `petugas` ADD COLUMN `no_sip` varchar(120) NOT NULL DEFAULT '' AFTER `status`;


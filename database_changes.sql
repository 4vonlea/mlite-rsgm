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

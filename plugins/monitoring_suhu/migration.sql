CREATE TABLE IF NOT EXISTS `mlite_monitoring_suhu_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_unit` varchar(30) NOT NULL,
  `nama_unit` varchar(120) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_kode_unit` (`kode_unit`),
  KEY `idx_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mlite_monitoring_suhu_akses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hak_akses` enum('input','view') NOT NULL DEFAULT 'input',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_unit_user` (`unit_id`, `user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mlite_monitoring_suhu_ruangan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_id` int(11) NOT NULL,
  `kode_ruangan` varchar(40) NOT NULL,
  `nama_ruangan` varchar(120) NOT NULL,
  `jenis_objek` varchar(50) NOT NULL DEFAULT 'Ruangan',
  `lokasi` varchar(150) DEFAULT '',
  `mode_ukur` enum('suhu','kelembaban','keduanya') NOT NULL DEFAULT 'keduanya',
  `suhu_min` decimal(5,2) DEFAULT NULL,
  `suhu_max` decimal(5,2) DEFAULT NULL,
  `kelembaban_min` decimal(5,2) DEFAULT NULL,
  `kelembaban_max` decimal(5,2) DEFAULT NULL,
  `kode_alat` varchar(100) DEFAULT '',
  `keterangan` text DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_unit_kode` (`unit_id`, `kode_ruangan`),
  KEY `idx_unit_aktif` (`unit_id`, `aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mlite_monitoring_suhu_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruangan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `shift` enum('Pagi','Siang','Sore','Malam') NOT NULL DEFAULT 'Pagi',
  `suhu` decimal(5,2) DEFAULT NULL,
  `kelembaban` decimal(5,2) DEFAULT NULL,
  `status_suhu` enum('Normal','Rendah','Tinggi','N/A') NOT NULL DEFAULT 'N/A',
  `status_kelembaban` enum('Normal','Rendah','Tinggi','N/A') NOT NULL DEFAULT 'N/A',
  `status_keseluruhan` enum('Normal','Peringatan') NOT NULL DEFAULT 'Normal',
  `tindakan_perbaikan` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `petugas_id` int(11) NOT NULL,
  `petugas_nama` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ruang_tgl` (`ruangan_id`, `tanggal`, `jam`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status` (`status_keseluruhan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

<?php

return [
    'name'          =>  'JKN Mobile',
    'description'   =>  'Modul mLITE JKN Mobile API',
    'author'        =>  'Basoro',
    'category'      =>  'bridging', 
    'version'       =>  '1.0',
    'compatibility' =>  '6.*.*',
    'icon'          =>  'tasks',
    'pages'         =>  ['JKN Mobile' => 'jknmobile'],
    'install'       =>  function () use ($core) {
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'x_username', 'jkn')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'x_password', 'mobile')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'header_token', 'X-Token')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'header_username', 'X-Username')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'header_password', 'X-Password')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'BpjsConsID', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'BpjsSecretKey', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'BpjsUserKey', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'BpjsAntrianUrl', 'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kd_pj_bpjs', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'exclude_taskid', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'display', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kdprop', '1')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kdkab', '1')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kdkec', '1')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kdkel', '1')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'perusahaan_pasien', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'suku_bangsa', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'bahasa_pasien', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'cacat_fisik', '')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'kirimantrian', 'tidak')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'auto_pasien_baru', 'ya')");
      $core->db()->pdo()->exec("INSERT INTO `mlite_settings` (`module`, `field`, `value`) VALUES ('jkn_mobile', 'pesan_pasien_baru_nonaktif', 'Pasien baru harap mendaftar terlebih dahulu di loket admisi RS untuk pembuatan Nomor Rekam Medis.')");
      $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `mlite_jkn_pasien_baru` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `temp_norm` varchar(20) NOT NULL,
        `no_peserta` varchar(25) NOT NULL,
        `no_ktp` varchar(20) NOT NULL,
        `no_kk` varchar(20) DEFAULT '',
        `nm_pasien` varchar(100) NOT NULL,
        `jk` enum('L','P') DEFAULT 'L',
        `tgl_lahir` date DEFAULT NULL,
        `no_tlp` varchar(40) DEFAULT '',
        `alamat` varchar(255) DEFAULT '',
        `kodeprop` varchar(20) DEFAULT '',
        `namaprop` varchar(100) DEFAULT '',
        `kodedati2` varchar(20) DEFAULT '',
        `namadati2` varchar(100) DEFAULT '',
        `kodekec` varchar(20) DEFAULT '',
        `namakec` varchar(100) DEFAULT '',
        `kodekel` varchar(20) DEFAULT '',
        `namakel` varchar(100) DEFAULT '',
        `rt` varchar(10) DEFAULT '',
        `rw` varchar(10) DEFAULT '',
        `status_verifikasi` enum('Menunggu','Terverifikasi','Batal') NOT NULL DEFAULT 'Menunggu',
        `no_rkm_medis_resmi` varchar(15) DEFAULT NULL,
        `verified_by` varchar(50) DEFAULT NULL,
        `verified_at` datetime DEFAULT NULL,
        `tgl_input` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_temp_norm` (`temp_norm`),
        KEY `idx_no_peserta` (`no_peserta`),
        KEY `idx_no_ktp` (`no_ktp`),
        KEY `idx_status` (`status_verifikasi`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `mlite_jkn_booking_baru` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `temp_norm` varchar(20) NOT NULL,
        `tanggal_booking` date NOT NULL,
        `jam_booking` time NOT NULL,
        `tanggal_periksa` date NOT NULL,
        `kd_dokter` varchar(20) NOT NULL,
        `kd_poli` varchar(10) NOT NULL,
        `no_reg` varchar(10) NOT NULL,
        `kd_pj` char(3) DEFAULT 'BPJ',
        `waktu_kunjungan` datetime DEFAULT NULL,
        `kodebooking` varchar(100) NOT NULL,
        `nomor_referensi` varchar(60) DEFAULT '',
        `jenis_kunjungan` varchar(10) DEFAULT '1',
        `status` enum('Belum','Terdaftar','Batal') NOT NULL DEFAULT 'Belum',
        `tgl_input` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_kodebooking` (`kodebooking`),
        KEY `idx_temp_tgl` (`temp_norm`, `tanggal_periksa`),
        KEY `idx_poli_dokter_tgl` (`tanggal_periksa`, `kd_poli`, `kd_dokter`, `status`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'uninstall'     =>  function () use ($core) {
      $core->db()->pdo()->exec("DELETE FROM `mlite_settings` WHERE `module` = 'jkn_mobile'");
    }
];

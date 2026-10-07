<?php
$pdo = new PDO('mysql:host=localhost;dbname=mlitersgm', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql1 = "
CREATE TABLE IF NOT EXISTS `ans4_rencana_anestesi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  
  `premedikasi` enum('Y','N') DEFAULT NULL,
  `premedikasi_ket` text DEFAULT NULL,
  
  `ga_masker` enum('Y','N') DEFAULT NULL,
  `ga_intubasi` enum('Y','N') DEFAULT NULL,
  `ga_lma` enum('Y','N') DEFAULT NULL,
  `ga_tiva` enum('Y','N') DEFAULT NULL,
  
  `analgetik` enum('Y','N') DEFAULT NULL,
  `analgetik_ket` text DEFAULT NULL,
  
  `pelumpuh_otot` enum('Y','N') DEFAULT NULL,
  `pelumpuh_otot_ket` text DEFAULT NULL,
  
  `maintenance_catatan` text DEFAULT NULL,
  `maintenance_inhalasi` enum('Y','N') DEFAULT NULL,
  `inhalasi_o2` enum('Y','N') DEFAULT NULL,
  `inhalasi_isofluran` enum('Y','N') DEFAULT NULL,
  `inhalasi_halotan` enum('Y','N') DEFAULT NULL,
  `inhalasi_sevofluran` enum('Y','N') DEFAULT NULL,
  
  `maintenance_intravena` enum('Y','N') DEFAULT NULL,
  `intravena_1` varchar(100) DEFAULT NULL,
  `intravena_2` varchar(100) DEFAULT NULL,
  `intravena_3` varchar(100) DEFAULT NULL,
  `intravena_4` varchar(100) DEFAULT NULL,
  `intravena_5` varchar(100) DEFAULT NULL,
  
  `teknik_regional` enum('Y','N') DEFAULT NULL,
  `teknik_sab` enum('Y','N') DEFAULT NULL,
  `teknik_lokal` enum('Y','N') DEFAULT NULL,
  `teknik_epidural` enum('Y','N') DEFAULT NULL,
  `teknik_pnb` enum('Y','N') DEFAULT NULL,
  `teknik_lokal_ket1` varchar(100) DEFAULT NULL,
  `teknik_lokal_ket2` varchar(100) DEFAULT NULL,
  
  `makan_terakhir` varchar(20) DEFAULT NULL,
  `minum_terakhir` varchar(20) DEFAULT NULL,
  
  `eval_td` varchar(50) DEFAULT NULL,
  `eval_hr` varchar(50) DEFAULT NULL,
  `eval_rr` varchar(50) DEFAULT NULL,
  `eval_sb` varchar(50) DEFAULT NULL,
  `eval_spo2` varchar(50) DEFAULT NULL,
  
  `masalah_induksi` enum('Ada','Tidak Ada') DEFAULT NULL,
  `masalah_induksi_ket` text DEFAULT NULL,
  `perubahan_rencana` enum('Ada','Tidak Ada') DEFAULT NULL,
  `perubahan_rencana_ket` text DEFAULT NULL,
  `persiapan_darah` enum('Ada','Tidak Ada') DEFAULT NULL,
  `persiapan_darah_ket` text DEFAULT NULL,
  
  `tilik_identifikasi` enum('Y','N') DEFAULT NULL,
  `tilik_persetujuan` enum('Y','N') DEFAULT NULL,
  `tilik_puasa` enum('Y','N') DEFAULT NULL,
  `tilik_mesin` enum('Y','N') DEFAULT NULL,
  `tilik_suction` enum('Y','N') DEFAULT NULL,
  `tilik_obat` enum('Y','N') DEFAULT NULL,
  `tilik_antibiotik` enum('Y','N') DEFAULT NULL,
  `tilik_pulse_oxy` enum('Y','N') DEFAULT NULL,
  
  `tilik_ekg` enum('Y','N') DEFAULT NULL,
  `tilik_sabuk` enum('Y','N') DEFAULT NULL,
  `tilik_stetoskop` enum('Y','N') DEFAULT NULL,
  `tilik_nibp` enum('Y','N') DEFAULT NULL,
  `tilik_termometer` enum('Y','N') DEFAULT NULL,
  `tilik_selimut` enum('Y','N') DEFAULT NULL,
  `tilik_urine` enum('Y','N') DEFAULT NULL,
  `tilik_penghangat` enum('Y','N') DEFAULT NULL,
  
  `tilik_pasca_induksi` enum('Y','N') DEFAULT NULL,
  `tilik_tekanan_bantalan` enum('Y','N') DEFAULT NULL,
  `tilik_mata` enum('Y','N') DEFAULT NULL,
  
  `induksi_tiba` enum('Tidur sadar','Menangis','Tidak Sadar') DEFAULT NULL,
  `induksi_td` varchar(50) DEFAULT NULL,
  `induksi_hr` varchar(50) DEFAULT NULL,
  `induksi_rr` varchar(50) DEFAULT NULL,
  `induksi_sb` varchar(50) DEFAULT NULL,
  
  `jalan_napas` enum('Awake','Non Apnea','Apnea') DEFAULT NULL,
  `jn_lma` enum('Y','N') DEFAULT NULL,
  `jn_lma_no` varchar(50) DEFAULT NULL,
  `jn_lma_cuff` varchar(50) DEFAULT NULL,
  
  `jn_ett_kink` enum('Y','N') DEFAULT NULL,
  `jn_ett_nonkink` enum('Y','N') DEFAULT NULL,
  `jn_ett_oral_nasal` enum('Oral','Nasal') DEFAULT NULL,
  `jn_ett_no` varchar(50) DEFAULT NULL,
  `jn_ett_cuff` varchar(50) DEFAULT NULL,
  `jn_ett_batas` varchar(50) DEFAULT NULL,
  
  `jn_endotrakial` enum('Y','N') DEFAULT NULL,
  `jn_endo_kanan_kiri` enum('Kanan','Kiri') DEFAULT NULL,
  `jn_endo_no` varchar(50) DEFAULT NULL,
  
  `jn_masker` enum('Y','N') DEFAULT NULL,
  `jn_ngt` enum('Y','N') DEFAULT NULL,
  `jn_tampon` enum('Y','N') DEFAULT NULL,
  
  `reg_sab` enum('Y','N') DEFAULT NULL,
  `reg_cse` enum('Y','N') DEFAULT NULL,
  `reg_epidural` enum('Y','N') DEFAULT NULL,
  `reg_caudal` enum('Y','N') DEFAULT NULL,
  `reg_pnb` enum('Y','N') DEFAULT NULL,
  `reg_ivr` enum('Y','N') DEFAULT NULL,
  
  `reg_jenis_jarum` varchar(100) DEFAULT NULL,
  `reg_blok_vth` varchar(100) DEFAULT NULL,
  `reg_jenis_pnb` varchar(100) DEFAULT NULL,
  `reg_obat` varchar(100) DEFAULT NULL,
  `reg_dosis` varchar(50) DEFAULT NULL,
  `reg_vol` varchar(50) DEFAULT NULL,
  `reg_obat_ket` varchar(100) DEFAULT NULL,
  `reg_epidural_ket` varchar(100) DEFAULT NULL,
  
  `kateter_perifer1` varchar(100) DEFAULT NULL,
  `kateter_perifer2` varchar(100) DEFAULT NULL,
  `kateter_cvc` varchar(100) DEFAULT NULL,
  
  `posisi_supine` enum('Y','N') DEFAULT NULL,
  `posisi_prone` enum('Y','N') DEFAULT NULL,
  `posisi_trendelenberg` enum('Y','N') DEFAULT NULL,
  `posisi_lithotomi` enum('Y','N') DEFAULT NULL,
  `posisi_lateral` enum('Y','N') DEFAULT NULL,
  `posisi_lainnya` varchar(100) DEFAULT NULL,
  
  `tanggal` date DEFAULT NULL,
  `jam` varchar(20) DEFAULT NULL,
  `dokter_nama` varchar(150) DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$sql2 = "
CREATE TABLE IF NOT EXISTS `ans4_evaluasi_premedikasi_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ans4` int(11) NOT NULL,
  `obat_premedikasi` varchar(150) DEFAULT NULL,
  `dosis` varchar(100) DEFAULT NULL,
  `jam` varchar(50) DEFAULT NULL,
  `pelaksana` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ans4` (`id_ans4`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$pdo->exec($sql1);
$pdo->exec($sql2);
echo "Tables created successfully.";
?>

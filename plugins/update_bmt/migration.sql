-- =========================================================
-- MIGRASI DATABASE: TABEL PENDUKUNG UPDATE BMT (mLITE)
-- Database Target: mlitersgm
-- Total Tabel: 36
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Struktur tabel `ans1_tindakan_pembiusan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans1_tindakan_pembiusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `tanggal_jam` datetime NOT NULL,
  `pemberi_informasi` varchar(100) DEFAULT NULL,
  `penerima_informasi_1` varchar(100) DEFAULT NULL,
  `penerima_informasi_2` varchar(100) DEFAULT NULL,
  `status_fisik_asa` varchar(255) DEFAULT NULL,
  `dasar_diagnosis_klinis` enum('0','1') DEFAULT '0',
  `dasar_diagnosis_laboratorium` enum('0','1') DEFAULT '0',
  `dasar_diagnosis_radiologis` enum('0','1') DEFAULT '0',
  `dasar_diagnosis_ekg` enum('0','1') DEFAULT '0',
  `tindakan_umum_intubasi` enum('0','1') DEFAULT '0',
  `tindakan_umum_lma` enum('0','1') DEFAULT '0',
  `tindakan_umum_face_mask` enum('0','1') DEFAULT '0',
  `tindakan_umum_tiva` enum('0','1') DEFAULT '0',
  `tindakan_regional_spinal` enum('0','1') DEFAULT '0',
  `tindakan_regional_epidural` enum('0','1') DEFAULT '0',
  `tindakan_regional_blok_perifer` enum('0','1') DEFAULT '0',
  `risiko_shock` enum('0','1') DEFAULT '0',
  `risiko_henti_jantung` enum('0','1') DEFAULT '0',
  `risiko_meninggal` enum('0','1') DEFAULT '0',
  `komplikasi_bius_umum` enum('0','1') DEFAULT '0',
  `komplikasi_bu_sistem_pernapasan` enum('0','1') DEFAULT '0',
  `komplikasi_bu_jantung` enum('0','1') DEFAULT '0',
  `komplikasi_bu_sistem_saraf` enum('0','1') DEFAULT '0',
  `komplikasi_bu_tindakan_laringoskopi` enum('0','1') DEFAULT '0',
  `komplikasi_bu_suhu_tubuh` enum('0','1') DEFAULT '0',
  `komplikasi_bu_efek_merugikan` enum('0','1') DEFAULT '0',
  `komplikasi_bu_cedera_akibat_posisi` enum('0','1') DEFAULT '0',
  `komplikasi_bu_muntah` enum('0','1') DEFAULT '0',
  `komplikasi_bu_perut_kembung` enum('0','1') DEFAULT '0',
  `komplikasi_bu_tenggorokan_serak` enum('0','1') DEFAULT '0',
  `komplikasi_bius_regional` enum('0','1') DEFAULT '0',
  `komplikasi_br_segera` enum('0','1') DEFAULT '0',
  `komplikasi_br_penurunan_tekanan_darah` enum('0','1') DEFAULT '0',
  `komplikasi_br_anestesi_spinal_total` enum('0','1') DEFAULT '0',
  `komplikasi_br_reaksi_toksik` enum('0','1') DEFAULT '0',
  `komplikasi_br_reaksi_alergi` enum('0','1') DEFAULT '0',
  `komplikasi_br_lanjutan` enum('0','1') DEFAULT '0',
  `komplikasi_br_nyeri_kepala` enum('0','1') DEFAULT '0',
  `komplikasi_br_nyeri_punggung` enum('0','1') DEFAULT '0',
  `komplikasi_br_tidak_bisa_berkemih` enum('0','1') DEFAULT '0',
  `komplikasi_br_infeksi` enum('0','1') DEFAULT '0',
  `komplikasi_br_cedera_saraf` enum('0','1') DEFAULT '0',
  `komplikasi_br_pendarahan` enum('0','1') DEFAULT '0',
  `prognosis` varchar(255) DEFAULT NULL,
  `alternatif_tindakan` varchar(255) DEFAULT NULL,
  `lain_lain` varchar(255) DEFAULT NULL,
  `tandai_1` enum('0','1') DEFAULT '0',
  `tandai_2` enum('0','1') DEFAULT '0',
  `tandai_3` enum('0','1') DEFAULT '0',
  `tandai_4` enum('0','1') DEFAULT '0',
  `tandai_5` enum('0','1') DEFAULT '0',
  `tandai_6` enum('0','1') DEFAULT '0',
  `tandai_7` enum('0','1') DEFAULT '0',
  `tandai_8` enum('0','1') DEFAULT '0',
  `tandai_9` enum('0','1') DEFAULT '0',
  `tandai_10` enum('0','1') DEFAULT '0',
  `tata_cara_tindakan` varchar(255) DEFAULT NULL,
  `indikasi_dan_tujuan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans2_persetujuan_pembiusan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans2_persetujuan_pembiusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `jenis_pernyataan` enum('PERSETUJUAN','PENOLAKAN') NOT NULL DEFAULT 'PERSETUJUAN',
  `pihak_nama` varchar(100) DEFAULT NULL,
  `pihak_umur` varchar(20) DEFAULT NULL,
  `pihak_jk` enum('L','P') DEFAULT NULL,
  `pihak_alamat` text DEFAULT NULL,
  `pihak_sebagai` varchar(50) DEFAULT NULL,
  `pasien_nama_isian` varchar(100) DEFAULT NULL,
  `pasien_umur_isian` varchar(20) DEFAULT NULL,
  `pasien_jk_isian` enum('L','P') DEFAULT NULL,
  `pasien_alamat_isian` text DEFAULT NULL,
  `alasan_penolakan` text DEFAULT NULL,
  `tanggal_jam` datetime DEFAULT NULL,
  `saksi_nama` varchar(100) DEFAULT NULL,
  `pihak_rs_nama` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans3_assesmen_prasedasi_anestesi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans3_assesmen_prasedasi_anestesi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `diagnosa_prabedah` text DEFAULT NULL,
  `rencana_tindakan` text DEFAULT NULL,
  `tipe_tindakan` enum('Elektif','Emergensi/CITO') DEFAULT NULL,
  `anamnese_dari` enum('Pasien','Keluarga pasien') DEFAULT NULL,
  `riwayat_anestesi` enum('Ada','Tidak') DEFAULT NULL,
  `riwayat_anestesi_ket` varchar(255) DEFAULT NULL,
  `komplikasi` varchar(255) DEFAULT NULL,
  `obat_sedang_dikonsumsi` text DEFAULT NULL,
  `riwayat_alergi` enum('Ada','Tidak') DEFAULT NULL,
  `riwayat_alergi_ket` varchar(255) DEFAULT NULL,
  `bb` varchar(20) DEFAULT NULL,
  `tb` varchar(20) DEFAULT NULL,
  `tanda_vital_td` text DEFAULT NULL,
  `tanda_vital_nadi` text DEFAULT NULL,
  `tanda_vital_rr` text DEFAULT NULL,
  `tanda_vital_suhu` text DEFAULT NULL,
  `tanda_vital_vas` text DEFAULT NULL,
  `bebas` enum('Ya','Tidak') DEFAULT NULL,
  `protusi_mandibula` enum('Ya','Tidak') DEFAULT NULL,
  `jarak_mentohyoid` enum('Normal','Tidak') DEFAULT NULL,
  `jarak_mentohyoid_ket` varchar(50) DEFAULT NULL,
  `jarak_thyrohyoid` enum('Pendek','Tidak') DEFAULT NULL,
  `jarak_thyrohyoid_ket` varchar(50) DEFAULT NULL,
  `buka_mulut` enum('Normal','Tidak') DEFAULT NULL,
  `mallampathy` enum('I','II','III','IV') DEFAULT NULL,
  `leher` enum('Pendek','Tidak') DEFAULT NULL,
  `gerak_leher` enum('Bebas','Tidak') DEFAULT NULL,
  `obesitas` enum('Ya','Tidak') DEFAULT NULL,
  `massa` enum('Ya','Tidak') DEFAULT NULL,
  `gigi_palsu` enum('Ya','Tidak') DEFAULT NULL,
  `sulit_ventilasi` enum('Ya','Tidak') DEFAULT NULL,
  `pernafasan_dbn` enum('Y','N') DEFAULT NULL,
  `pernafasan_asma` enum('Y','N') DEFAULT NULL,
  `pernafasan_pneumonia` enum('Y','N') DEFAULT NULL,
  `pernafasan_ispa` enum('Y','N') DEFAULT NULL,
  `pernafasan_tuberkulosis` enum('Y','N') DEFAULT NULL,
  `pernafasan_lainnya` varchar(100) DEFAULT NULL,
  `pernafasan_keterangan` text DEFAULT NULL,
  `kardiovaskular_dbn` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_ekg_abnormal` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_hipertensi` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_disritma` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_murmur` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_angina` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_pacemaker` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_chf` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_penyakit_katup` enum('Y','N') DEFAULT NULL,
  `kardiovaskular_lainnya` varchar(100) DEFAULT NULL,
  `kardiovaskular_keterangan` text DEFAULT NULL,
  `neuro_dbn` enum('Y','N') DEFAULT NULL,
  `neuro_sakit_kepala` enum('Y','N') DEFAULT NULL,
  `neuro_penurunan_kesadaran` enum('Y','N') DEFAULT NULL,
  `neuro_distropi` enum('Y','N') DEFAULT NULL,
  `neuro_parese` enum('Y','N') DEFAULT NULL,
  `neuro_parastesia` enum('Y','N') DEFAULT NULL,
  `neuro_plegi` enum('Y','N') DEFAULT NULL,
  `neuro_kejang` enum('Y','N') DEFAULT NULL,
  `neuro_lainnya` varchar(100) DEFAULT NULL,
  `neuro_keterangan` text DEFAULT NULL,
  `renal_endokrin_dbn` enum('Y','N') DEFAULT NULL,
  `renal_diabetes` enum('Y','N') DEFAULT NULL,
  `renal_tiroid` enum('Y','N') DEFAULT NULL,
  `renal_gagal_ginjal` enum('Y','N') DEFAULT NULL,
  `renal_lainnya` varchar(100) DEFAULT NULL,
  `renal_keterangan` text DEFAULT NULL,
  `hepato_dbn` enum('Y','N') DEFAULT NULL,
  `hepato_sirosis` enum('Y','N') DEFAULT NULL,
  `hepato_hepatitis` enum('Y','N') DEFAULT NULL,
  `hepato_obstruksi` enum('Y','N') DEFAULT NULL,
  `hepato_ikterus` enum('Y','N') DEFAULT NULL,
  `hepato_mual_muntah` enum('Y','N') DEFAULT NULL,
  `hepato_lainnya` varchar(100) DEFAULT NULL,
  `hepato_keterangan` text DEFAULT NULL,
  `lainnya_organ_dbn` enum('Y','N') DEFAULT NULL,
  `lainnya_neonat` enum('Y','N') DEFAULT NULL,
  `lainnya_geriatri` enum('Y','N') DEFAULT NULL,
  `lainnya_hamil` enum('Y','N') DEFAULT NULL,
  `lainnya_kanker` enum('Y','N') DEFAULT NULL,
  `lainnya_anemia` enum('Y','N') DEFAULT NULL,
  `lainnya_dehidrasi` enum('Y','N') DEFAULT NULL,
  `lainnya_merokok` enum('Y','N') DEFAULT NULL,
  `lainnya_alkohol` enum('Y','N') DEFAULT NULL,
  `lainnya_pendarahan` enum('Y','N') DEFAULT NULL,
  `lainnya_lainnya` varchar(100) DEFAULT NULL,
  `lainnya_organ_keterangan` text DEFAULT NULL,
  `lab_hb` text DEFAULT NULL,
  `lab_hct` text DEFAULT NULL,
  `lab_ct` text DEFAULT NULL,
  `lab_pt` text DEFAULT NULL,
  `lab_leukosit` text DEFAULT NULL,
  `lab_trombosit` text DEFAULT NULL,
  `lab_bt` text DEFAULT NULL,
  `lab_aptt` text DEFAULT NULL,
  `lab_ureum` text DEFAULT NULL,
  `lab_creatinin` text DEFAULT NULL,
  `lab_sgot` text DEFAULT NULL,
  `lab_sgpt` text DEFAULT NULL,
  `lab_albumin` text DEFAULT NULL,
  `lab_globulin` text DEFAULT NULL,
  `lab_bilirubin_direct` text DEFAULT NULL,
  `lab_bilirubin_indirect` text DEFAULT NULL,
  `lab_na` text DEFAULT NULL,
  `lab_k` text DEFAULT NULL,
  `lab_cl` text DEFAULT NULL,
  `lab_gds` text DEFAULT NULL,
  `lab_t3` text DEFAULT NULL,
  `lab_tsh` text DEFAULT NULL,
  `lab_t4` text DEFAULT NULL,
  `lab_pco2` text DEFAULT NULL,
  `lab_be` text DEFAULT NULL,
  `lab_po2` text DEFAULT NULL,
  `lab_sao2` text DEFAULT NULL,
  `lab_lainlain` text DEFAULT NULL,
  `penunjang_ekg` text DEFAULT NULL,
  `penunjang_radiologi` text DEFAULT NULL,
  `penunjang_lainlain` text DEFAULT NULL,
  `keterangan_lain` text DEFAULT NULL,
  `ps_asa` text DEFAULT NULL,
  `rencana_sedasi` text DEFAULT NULL,
  `instruksi_puasa` enum('Y','N') DEFAULT NULL,
  `instruksi_puasa_jam` text DEFAULT NULL,
  `instruksi_persiapan_darah` enum('Ya','Tidak') DEFAULT NULL,
  `instruksi_persiapan_darah_cc` text DEFAULT NULL,
  `instruksi_obat_dihentikan` text DEFAULT NULL,
  `instruksi_lain1` text DEFAULT NULL,
  `instruksi_lain2` text DEFAULT NULL,
  `instruksi_lain3` text DEFAULT NULL,
  `instruksi_lain4` text DEFAULT NULL,
  `instruksi_lain5` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` varchar(20) DEFAULT NULL,
  `dokter_nama` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans4_evaluasi_premedikasi_detail`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans4_evaluasi_premedikasi_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ans4` int(11) NOT NULL,
  `obat_premedikasi` varchar(150) DEFAULT NULL,
  `dosis` varchar(100) DEFAULT NULL,
  `jam` varchar(50) DEFAULT NULL,
  `pelaksana` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ans4` (`id_ans4`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans4_rencana_anestesi`
-- --------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans6_protokol_nyeri_detail`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans6_protokol_nyeri_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ans6` int(11) NOT NULL,
  `tanggal` varchar(50) DEFAULT NULL,
  `obat` varchar(255) DEFAULT NULL,
  `tanda_tangan_dokter` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ans6` (`id_ans6`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans6_status_post_anestesi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans6_status_post_anestesi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `diagnosa_pascabedah` varchar(255) DEFAULT NULL,
  `dr_anestesi` varchar(150) DEFAULT NULL,
  `jenis_anestesi` varchar(150) DEFAULT NULL,
  `tindakan` varchar(150) DEFAULT NULL,
  `perawat_pacu` varchar(150) DEFAULT NULL,
  `vital_nadi` text DEFAULT NULL,
  `vital_suhu` text DEFAULT NULL,
  `vital_sistolik` text DEFAULT NULL,
  `vital_diastolik` text DEFAULT NULL,
  `vital_saturasi` text DEFAULT NULL,
  `aldrete_jam_pasca_15` varchar(50) DEFAULT NULL,
  `aldrete_jam_pasca_30` varchar(50) DEFAULT NULL,
  `aldrete_jam_pasca_45` varchar(50) DEFAULT NULL,
  `aldrete_jam_1` varchar(50) DEFAULT NULL,
  `aldrete_jam_2` varchar(50) DEFAULT NULL,
  `aldrete_jam_3` varchar(50) DEFAULT NULL,
  `aldrete_jam_4` varchar(50) DEFAULT NULL,
  `aldrete_jam_pindah` varchar(50) DEFAULT NULL,
  `steward_jam_pasca_15` varchar(50) DEFAULT NULL,
  `steward_jam_pasca_30` varchar(50) DEFAULT NULL,
  `steward_jam_pasca_45` varchar(50) DEFAULT NULL,
  `steward_jam_1` varchar(50) DEFAULT NULL,
  `steward_jam_2` varchar(50) DEFAULT NULL,
  `steward_jam_3` varchar(50) DEFAULT NULL,
  `steward_jam_4` varchar(50) DEFAULT NULL,
  `steward_jam_pindah` varchar(50) DEFAULT NULL,
  `instruksi_prosedur_infus` varchar(255) DEFAULT NULL,
  `instruksi_prosedur_puasa` varchar(255) DEFAULT NULL,
  `instruksi_obs_td` char(1) DEFAULT NULL,
  `instruksi_obs_respirasi` char(1) DEFAULT NULL,
  `instruksi_obs_nadi` char(1) DEFAULT NULL,
  `instruksi_obs_kesadaran` char(1) DEFAULT NULL,
  `instruksi_obs_urin` char(1) DEFAULT NULL,
  `instruksi_obat_a` varchar(255) DEFAULT NULL,
  `instruksi_obat_b` varchar(255) DEFAULT NULL,
  `instruksi_obat_c` varchar(255) DEFAULT NULL,
  `instruksi_posisi_supine` char(1) DEFAULT NULL,
  `instruksi_posisi_setengah_duduk` char(1) DEFAULT NULL,
  `instruksi_posisi_lainnya` varchar(255) DEFAULT NULL,
  `tanda_tangan_dokter` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ans7_serah_terima`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans7_serah_terima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `diagnosa_medis` varchar(255) DEFAULT NULL,
  `tindakan_operasi` varchar(255) DEFAULT NULL,
  `dokter_operator` varchar(150) DEFAULT NULL,
  `jenis_anestesi_ga` varchar(20) DEFAULT NULL,
  `jenis_anestesi_ra` varchar(20) DEFAULT NULL,
  `jenis_anestesi_lokal` varchar(20) DEFAULT NULL,
  `dokter_anestesi` varchar(150) DEFAULT NULL,
  `kondisi_tanggal` varchar(50) DEFAULT NULL,
  `kondisi_pukul` varchar(50) DEFAULT NULL,
  `td` varchar(20) DEFAULT NULL,
  `nadi` varchar(20) DEFAULT NULL,
  `rr` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `mual_muntah` varchar(10) DEFAULT NULL,
  `respon_nyeri` varchar(10) DEFAULT NULL,
  `skala_nyeri` varchar(20) DEFAULT NULL,
  `alat_drain` varchar(10) DEFAULT NULL,
  `alat_ngt` varchar(10) DEFAULT NULL,
  `alat_dc` varchar(10) DEFAULT NULL,
  `alat_irigasi` varchar(10) DEFAULT NULL,
  `score_aldrete` varchar(10) DEFAULT NULL,
  `score_steward` varchar(10) DEFAULT NULL,
  `infuse_1` varchar(150) DEFAULT NULL,
  `infuse_1_jumlah` varchar(50) DEFAULT NULL,
  `infuse_1_jam` varchar(50) DEFAULT NULL,
  `infuse_2` varchar(150) DEFAULT NULL,
  `infuse_2_jumlah` varchar(50) DEFAULT NULL,
  `infuse_2_jam` varchar(50) DEFAULT NULL,
  `tranfusi_1` varchar(150) DEFAULT NULL,
  `tranfusi_1_jumlah` varchar(50) DEFAULT NULL,
  `tranfusi_2` varchar(150) DEFAULT NULL,
  `tranfusi_2_jumlah` varchar(50) DEFAULT NULL,
  `antibiotik` varchar(150) DEFAULT NULL,
  `antibiotik_jam` varchar(50) DEFAULT NULL,
  `analgesik` varchar(150) DEFAULT NULL,
  `analgesik_jam` varchar(50) DEFAULT NULL,
  `obat_lain` varchar(255) DEFAULT NULL,
  `advis` varchar(255) DEFAULT NULL,
  `status_rm` varchar(10) DEFAULT NULL,
  `foto_rontgen` varchar(10) DEFAULT NULL,
  `usg` varchar(10) DEFAULT NULL,
  `barang_lain` varchar(255) DEFAULT NULL,
  `jenis_jaringan` varchar(255) DEFAULT NULL,
  `periksa_pa` varchar(10) DEFAULT NULL,
  `periksa_kultur` varchar(10) DEFAULT NULL,
  `jaringan_dibawakan` varchar(10) DEFAULT NULL,
  `serah_tanggal` varchar(50) DEFAULT NULL,
  `serah_pukul` varchar(50) DEFAULT NULL,
  `keluarga_pasien` varchar(150) DEFAULT NULL,
  `perawat_rr` varchar(150) DEFAULT NULL,
  `perawat_ruang` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `asesmen_keperawatan_gigi_ugd`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asesmen_keperawatan_gigi_ugd` (
  `no_rawat` varchar(20) NOT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tanggal` datetime NOT NULL,
  `cara_datang` enum('Sendiri','Ambulans','Diantar Keluarga','Diantar Polisi') DEFAULT 'Sendiri',
  `diantar_oleh` varchar(50) DEFAULT NULL,
  `rujukan` enum('Tidak','Ya') DEFAULT 'Tidak',
  `rujukan_rs` varchar(50) DEFAULT NULL,
  `rujukan_klinik` varchar(50) DEFAULT NULL,
  `rujukan_puskesmas` varchar(50) DEFAULT NULL,
  `rujukan_dokter` varchar(50) DEFAULT NULL,
  `diagnosis_rujukan` varchar(100) DEFAULT NULL,
  `jenis_kasus` enum('Kasus Gigi dan Mulut','Kasus Umum','Trauma','Non Trauma') DEFAULT 'Kasus Gigi dan Mulut',
  `kiriman_dari` enum('Puskesmas','Dokter') DEFAULT NULL,
  `kiriman_dari_ket` varchar(50) DEFAULT NULL,
  `asuransi` enum('Asuransi','Umum','BPJS') DEFAULT 'Umum',
  `alamat_kontak` varchar(150) DEFAULT NULL,
  `nama_pengantar` varchar(100) DEFAULT NULL,
  `hub_dengan_pasien` varchar(50) DEFAULT NULL,
  `sumber_data` enum('Auto Anamnesis','Allo Anamnesis') DEFAULT 'Auto Anamnesis',
  `kategori_pasien` enum('Pasien Anak (0- <18 tahun)','Pasien Dewasa (18-65 tahun)','Pasien Geriatri (>65 tahun)') DEFAULT 'Pasien Dewasa (18-65 tahun)',
  `tanggal_kunjungan` date DEFAULT NULL,
  `pukul_kunjungan` time DEFAULT NULL,
  `triase` enum('Merah','Kuning','Hijau','Hitam') DEFAULT 'Hijau',
  `jam_mulai_triase` time DEFAULT NULL,
  `jam_selesai_triase` time DEFAULT NULL,
  `petugas_triase` varchar(100) DEFAULT NULL,
  `cara_masuk` enum('Jalan Kaki','Kursi Roda','Brankar','Digendong') DEFAULT 'Jalan Kaki',
  `keluhan_utama` text DEFAULT NULL,
  `riwayat_penyakit_sekarang` text DEFAULT NULL,
  `penyakit_jantung` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `hipertensi` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `diabetes` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `hepatitis` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `haemophilia` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `tuberculosis` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `hiv_aids` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `penyakit_lainnya` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `penyakit_lainnya_ket` varchar(100) DEFAULT NULL,
  `penyakit_menular` enum('Menular','Tidak Menular') DEFAULT 'Tidak Menular',
  `alergi_obat` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `alergi_obat_ket` varchar(100) DEFAULT NULL,
  `alergi_makanan` enum('Tidak Ada','Ada') DEFAULT 'Tidak Ada',
  `alergi_makanan_ket` varchar(100) DEFAULT NULL,
  `keadaan_umum` varchar(50) DEFAULT NULL,
  `kesadaran` varchar(50) DEFAULT NULL,
  `td` varchar(20) DEFAULT NULL,
  `nadi` varchar(20) DEFAULT NULL,
  `rr` varchar(20) DEFAULT NULL,
  `saturasi` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `gol_darah` varchar(5) DEFAULT NULL,
  `bb` varchar(10) DEFAULT NULL,
  `tb` varchar(10) DEFAULT NULL,
  `status_pernikahan` enum('Lajang','Menikah','Cerai') DEFAULT 'Lajang',
  `tinggal_bersama` enum('Ya','Tidak') DEFAULT 'Ya',
  `tinggal_bersama_ket` varchar(100) DEFAULT NULL,
  `riwayat_kebiasaan_merokok` enum('Ya','Tidak') DEFAULT 'Tidak',
  `riwayat_kebiasaan_alkohol` enum('Ya','Tidak') DEFAULT 'Tidak',
  `riwayat_kebiasaan_napza` enum('Ya','Tidak') DEFAULT 'Tidak',
  `riwayat_kebiasaan_lainnya` enum('Ya','Tidak') DEFAULT 'Tidak',
  `ket_riwayat_kebiasaan` varchar(100) DEFAULT NULL,
  `hambatan_fisik_pendengaran` enum('Ya','Tidak') DEFAULT 'Tidak',
  `hambatan_fisik_bicara` enum('Ya','Tidak') DEFAULT 'Tidak',
  `hambatan_fisik_penglihatan` enum('Ya','Tidak') DEFAULT 'Tidak',
  `hambatan_kognitif` varchar(100) DEFAULT NULL,
  `dirawat_sesama_gender` enum('Ya','Tidak') DEFAULT 'Tidak',
  `pantangan_makanan` enum('Ya','Tidak') DEFAULT 'Tidak',
  `pantangan_makanan_ket` varchar(100) DEFAULT NULL,
  `hari_tindakan` enum('Ya','Tidak') DEFAULT 'Tidak',
  `hari_tindakan_ket` varchar(100) DEFAULT NULL,
  `bahasa_digunakan` varchar(50) DEFAULT NULL,
  `ekonomi` enum('Sangat Tinggi','Tinggi','Sedang','Rendah') DEFAULT 'Sedang',
  `mst_penurunan_bb` int(11) DEFAULT 0,
  `mst_asupan_makanan` int(11) DEFAULT 0,
  `mst_diagnosis_khusus` enum('Ya','Tidak') DEFAULT 'Tidak',
  `strong_kurus` int(11) DEFAULT 0,
  `strong_penurunan_bb` int(11) DEFAULT 0,
  `strong_kondisi` int(11) DEFAULT 0,
  `strong_penyakit` int(11) DEFAULT 0,
  `asesmen_jatuh_a` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_b` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_hasil` enum('Tidak Berisiko','Risiko Rendah','Risiko Tinggi') DEFAULT 'Tidak Berisiko',
  `asesmen_jatuh_tindakan_tidak` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_tindakan_edukasi` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_tindakan_kalung` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_tindakan_alat` enum('Ya','Tidak') DEFAULT 'Tidak',
  `asesmen_jatuh_tindakan_dampingi` enum('Ya','Tidak') DEFAULT 'Tidak',
  `penunjang_lab` enum('Ya','Tidak') DEFAULT 'Tidak',
  `penunjang_rontgen` enum('Ya','Tidak') DEFAULT 'Tidak',
  `penunjang_lain` enum('Ya','Tidak') DEFAULT 'Tidak',
  `penunjang_lain_ket` varchar(100) DEFAULT NULL,
  `masalah_keperawatan` text DEFAULT NULL,
  `rencana_tindak_lanjut` text DEFAULT NULL,
  `evaluasi_kontrol` enum('Ya','Tidak') DEFAULT 'Tidak',
  `evaluasi_kontrol_tgl` date DEFAULT NULL,
  `evaluasi_rujuk` enum('Ya','Tidak') DEFAULT 'Tidak',
  `evaluasi_rujuk_ke` varchar(100) DEFAULT NULL,
  `evaluasi_rawat_inap` enum('Ya','Tidak') DEFAULT 'Tidak',
  `evaluasi_rawat_inap_ruangan` varchar(100) DEFAULT NULL,
  `nip` varchar(20) DEFAULT NULL,
  `nip_dpjp` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`no_rawat`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `asesmen_medis_umum_irna`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asesmen_medis_umum_irna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `sumber_data` varchar(100) DEFAULT NULL,
  `kategori_pasien` varchar(100) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `waktu_masuk` time DEFAULT NULL,
  `keluhan_utama` text DEFAULT NULL,
  `riwayat_penyakit_sekarang` text DEFAULT NULL,
  `riwayat_penyakit_dahulu` text DEFAULT NULL,
  `riwayat_penyakit_keluarga` text DEFAULT NULL,
  `riwayat_alergi` varchar(100) DEFAULT 'Tidak',
  `riwayat_alergi_keterangan` varchar(255) DEFAULT NULL,
  `obat_rutin` text DEFAULT NULL,
  `penyakit_menular` varchar(255) DEFAULT NULL,
  `penyakit_jantung` varchar(100) DEFAULT 'Tidak Ada',
  `hipertensi` varchar(100) DEFAULT 'Tidak Ada',
  `diabetes` varchar(100) DEFAULT 'Tidak Ada',
  `haemophilia` varchar(100) DEFAULT 'Tidak Ada',
  `gangguan_ginjal` varchar(100) DEFAULT 'Tidak Ada',
  `gangguan_ginjal_ket` varchar(255) DEFAULT NULL,
  `penyakit_sistemik_lainnya` varchar(100) DEFAULT 'Tidak Ada',
  `penyakit_sistemik_lainnya_ket` varchar(255) DEFAULT NULL,
  `riwayat_operasi` varchar(100) DEFAULT 'Tidak Ada',
  `riwayat_operasi_ket` varchar(255) DEFAULT NULL,
  `anak_ke` int(3) DEFAULT NULL,
  `dari_saudara` int(3) DEFAULT NULL,
  `cara_kelahiran` varchar(50) DEFAULT NULL,
  `umur_kelahiran` varchar(50) DEFAULT NULL,
  `kelainan_bawaan` varchar(100) DEFAULT NULL,
  `kelainan_bawaan_ket` varchar(100) DEFAULT NULL,
  `imunisasi_bcg` tinyint(1) DEFAULT 0,
  `imunisasi_polio` int(3) DEFAULT NULL,
  `imunisasi_dpt` int(3) DEFAULT NULL,
  `imunisasi_campak` int(3) DEFAULT NULL,
  `imunisasi_hepatitis_b` int(3) DEFAULT NULL,
  `imunisasi_lainnya` int(3) DEFAULT NULL,
  `tumbuh_gigi_pertama` varchar(50) DEFAULT NULL,
  `mulai_bisa_duduk` varchar(50) DEFAULT NULL,
  `berjalan_sendiri` varchar(50) DEFAULT NULL,
  `mulai_bisa_bicara` varchar(50) DEFAULT NULL,
  `mulai_bisa_membaca` varchar(50) DEFAULT NULL,
  `geriatri_penglihatan` tinyint(1) DEFAULT 0,
  `geriatri_pendengaran` tinyint(1) DEFAULT 0,
  `geriatri_berkemih` tinyint(1) DEFAULT 0,
  `geriatri_daya_ingat` tinyint(1) DEFAULT 0,
  `geriatri_bicara` tinyint(1) DEFAULT 0,
  `geriatri_gigi_palsu` tinyint(1) DEFAULT 0,
  `geriatri_lainnya` varchar(255) DEFAULT NULL,
  `keadaan_umum` varchar(100) DEFAULT NULL,
  `kesadaran` varchar(100) DEFAULT NULL,
  `tekanan_darah` varchar(20) DEFAULT NULL,
  `frekuensi_nadi` varchar(20) DEFAULT NULL,
  `frekuensi_napas` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `berat_badan` varchar(20) DEFAULT NULL,
  `tinggi_badan` varchar(20) DEFAULT NULL,
  `golongan_darah` varchar(10) DEFAULT NULL,
  `nyeri_keluhan` varchar(100) DEFAULT 'Tidak',
  `nyeri_lokasi` varchar(100) DEFAULT NULL,
  `nyeri_durasi` varchar(100) DEFAULT NULL,
  `nyeri_frekuensi` varchar(100) DEFAULT NULL,
  `nyeri_karakteristik` varchar(100) DEFAULT NULL,
  `nyeri_menjalar` varchar(100) DEFAULT 'Tidak',
  `nyeri_menjalar_ket` varchar(100) DEFAULT NULL,
  `nyeri_memberat` varchar(100) DEFAULT NULL,
  `nyeri_hilang` varchar(100) DEFAULT NULL,
  `nyeri_tindak_lanjut` varchar(255) DEFAULT NULL,
  `nyeri_konsul_ke` varchar(100) DEFAULT NULL,
  `skala_nyeri` int(2) DEFAULT NULL,
  `penunjang_lab` tinyint(1) DEFAULT 0,
  `penunjang_rontgen` tinyint(1) DEFAULT 0,
  `penunjang_lainnya` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `rencana_tindak_lanjut` text DEFAULT NULL,
  `tanggal_ttd` date DEFAULT NULL,
  `jam_ttd` time DEFAULT NULL,
  `nip_dpjp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bm1_informasi_tindakan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bm1_informasi_tindakan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `pemberi_informasi` varchar(150) DEFAULT NULL,
  `penerima_informasi` varchar(150) DEFAULT NULL,
  `nama_1` varchar(150) DEFAULT NULL,
  `nama_2` varchar(150) DEFAULT NULL,
  `info_1_isi` text DEFAULT NULL,
  `info_1_tanda` char(1) DEFAULT NULL,
  `info_2_isi` text DEFAULT NULL,
  `info_2_tanda` char(1) DEFAULT NULL,
  `info_3_isi` text DEFAULT NULL,
  `info_3_tanda` char(1) DEFAULT NULL,
  `info_4_isi` text DEFAULT NULL,
  `info_4_tanda` char(1) DEFAULT NULL,
  `info_5_isi` text DEFAULT NULL,
  `info_5_tanda` char(1) DEFAULT NULL,
  `info_6_isi` text DEFAULT NULL,
  `info_6_tanda` char(1) DEFAULT NULL,
  `info_7_isi` text DEFAULT NULL,
  `info_7_tanda` char(1) DEFAULT NULL,
  `info_8_isi` text DEFAULT NULL,
  `info_8_tanda` char(1) DEFAULT NULL,
  `info_9_isi` text DEFAULT NULL,
  `info_9_tanda` char(1) DEFAULT NULL,
  `info_10_isi` text DEFAULT NULL,
  `info_10_tanda` char(1) DEFAULT NULL,
  `info_11_isi` text DEFAULT NULL,
  `info_11_tanda` char(1) DEFAULT NULL,
  `info_12_isi` text DEFAULT NULL,
  `info_12_tanda` char(1) DEFAULT NULL,
  `tgl_dokumen` date DEFAULT NULL,
  `waktu_dokumen` varchar(10) DEFAULT NULL,
  `ttd_dokter` varchar(150) DEFAULT NULL,
  `ttd_penerima` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bm2_persetujuan_tindakan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bm2_persetujuan_tindakan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `tipe_form` varchar(20) DEFAULT 'PERSETUJUAN',
  `nama_pj` varchar(150) DEFAULT NULL,
  `umur_pj` varchar(50) DEFAULT NULL,
  `jk_pj` varchar(20) DEFAULT NULL,
  `alamat_pj` varchar(255) DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT NULL,
  `alasan_penolakan` varchar(255) DEFAULT NULL,
  `nama_pasien_form` varchar(150) DEFAULT NULL,
  `umur_pasien_form` varchar(50) DEFAULT NULL,
  `jk_pasien_form` varchar(20) DEFAULT NULL,
  `alamat_pasien_form` varchar(255) DEFAULT NULL,
  `tgl_dokumen` date DEFAULT NULL,
  `waktu_dokumen` varchar(20) DEFAULT NULL,
  `ttd_menyatakan` varchar(150) DEFAULT NULL,
  `ttd_saksi` varchar(150) DEFAULT NULL,
  `ttd_pihak_rs` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bm4_laporan_pembedahan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bm4_laporan_pembedahan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `waktu_mulai` varchar(20) DEFAULT NULL,
  `waktu_selesai` varchar(20) DEFAULT NULL,
  `lama_pembedahan` varchar(50) DEFAULT NULL,
  `tgl_pembedahan` date DEFAULT NULL,
  `sifat_pembedahan` varchar(20) DEFAULT NULL,
  `anestesi` varchar(20) DEFAULT NULL,
  `dpjp_bedah` varchar(150) DEFAULT NULL,
  `dpjp_anestesi` varchar(150) DEFAULT NULL,
  `diagnosis_pra_bedah` text DEFAULT NULL,
  `operator` varchar(150) DEFAULT NULL,
  `penata_anestesi` varchar(150) DEFAULT NULL,
  `diagnosis_pasca_bedah` text DEFAULT NULL,
  `asisten` varchar(150) DEFAULT NULL,
  `instrumen` varchar(150) DEFAULT NULL,
  `tindakan_pembedahan` text DEFAULT NULL,
  `jenis_pembedahan` varchar(50) DEFAULT NULL,
  `profilaksis` varchar(10) DEFAULT NULL,
  `jenis_antibiotik` varchar(150) DEFAULT NULL,
  `diberikan_jam` varchar(50) DEFAULT NULL,
  `uraian_pembedahan` text DEFAULT NULL,
  `jumlah_perdarahan` varchar(100) DEFAULT NULL,
  `jaringan_ke_patologi` varchar(10) DEFAULT NULL,
  `macam_jaringan` varchar(255) DEFAULT NULL,
  `label_ditempel` varchar(20) DEFAULT NULL,
  `menggunakan_implant` varchar(10) DEFAULT NULL,
  `no_register_implant` varchar(150) DEFAULT NULL,
  `ttd_perawat` varchar(150) DEFAULT NULL,
  `ttd_operator` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bmi1_persetujuan_anestesi_lokal`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bmi1_persetujuan_anestesi_lokal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `pemberi_informasi` varchar(150) DEFAULT NULL,
  `penerima_informasi` varchar(150) DEFAULT NULL,
  `info_1_isi` text DEFAULT NULL,
  `info_1_tanda` char(1) DEFAULT NULL,
  `info_2_isi` text DEFAULT NULL,
  `info_2_tanda` char(1) DEFAULT NULL,
  `info_3_isi` text DEFAULT NULL,
  `info_3_tanda` char(1) DEFAULT NULL,
  `info_4_isi` text DEFAULT NULL,
  `info_4_tanda` char(1) DEFAULT NULL,
  `info_5_isi` text DEFAULT NULL,
  `info_5_tanda` char(1) DEFAULT NULL,
  `info_6_isi` text DEFAULT NULL,
  `info_6_tanda` char(1) DEFAULT NULL,
  `info_7_isi` text DEFAULT NULL,
  `info_7_tanda` char(1) DEFAULT NULL,
  `info_8_isi` text DEFAULT NULL,
  `info_8_tanda` char(1) DEFAULT NULL,
  `info_9_isi` text DEFAULT NULL,
  `info_9_tanda` char(1) DEFAULT NULL,
  `info_10_isi` text DEFAULT NULL,
  `info_10_tanda` char(1) DEFAULT NULL,
  `info_11_isi` text DEFAULT NULL,
  `info_11_tanda` char(1) DEFAULT NULL,
  `ttd_dokter_pemberi` varchar(150) DEFAULT NULL,
  `ttd_pasien_penerima` varchar(150) DEFAULT NULL,
  `nama_pj` varchar(150) DEFAULT NULL,
  `umur_pj` varchar(50) DEFAULT NULL,
  `jk_pj` varchar(20) DEFAULT NULL,
  `alamat_pj` varchar(255) DEFAULT NULL,
  `tindakan_disetujui` varchar(255) DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT NULL,
  `nama_pasien_form` varchar(150) DEFAULT NULL,
  `umur_pasien_form` varchar(50) DEFAULT NULL,
  `jk_pasien_form` varchar(20) DEFAULT NULL,
  `alamat_pasien_form` varchar(255) DEFAULT NULL,
  `kota_ttd` varchar(150) DEFAULT 'Banjarmasin',
  `tgl_dokumen` date DEFAULT NULL,
  `waktu_dokumen` varchar(20) DEFAULT NULL,
  `ttd_menyatakan` varchar(150) DEFAULT NULL,
  `ttd_dokter_pelaksana` varchar(150) DEFAULT NULL,
  `ttd_saksi_1` varchar(150) DEFAULT NULL,
  `ttd_saksi_2` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bmi4_pra_anestesi_lokal`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bmi4_pra_anestesi_lokal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `rencana_tindakan` varchar(255) DEFAULT NULL,
  `hari_tindakan` varchar(50) DEFAULT NULL,
  `tanggal_tindakan` date DEFAULT NULL,
  `riwayat_operasi` varchar(10) DEFAULT NULL,
  `riwayat_operasi_ket` varchar(150) DEFAULT NULL,
  `penyakit_dahulu` varchar(255) DEFAULT NULL,
  `hipertensi` varchar(10) DEFAULT NULL,
  `hipertensi_ket` varchar(150) DEFAULT NULL,
  `pengobatan_hipertensi` varchar(150) DEFAULT NULL,
  `asma` varchar(10) DEFAULT NULL,
  `asma_serangan_terakhir` varchar(150) DEFAULT NULL,
  `asma_pengobatan` varchar(150) DEFAULT NULL,
  `merokok` varchar(10) DEFAULT NULL,
  `terakhir_merokok` varchar(150) DEFAULT NULL,
  `alergi` varchar(10) DEFAULT NULL,
  `alergi_obat` varchar(150) DEFAULT NULL,
  `alergi_makanan` varchar(150) DEFAULT NULL,
  `gastritis` varchar(10) DEFAULT NULL,
  `gastritis_ket` varchar(150) DEFAULT NULL,
  `gastritis_pengobatan` varchar(150) DEFAULT NULL,
  `angina` varchar(10) DEFAULT NULL,
  `kejang` varchar(10) DEFAULT NULL,
  `diabetes` varchar(10) DEFAULT NULL,
  `pengobatan_diabetes` varchar(150) DEFAULT NULL,
  `lain_lain_penyakit` varchar(255) DEFAULT NULL,
  `obat_dikonsumsi` varchar(255) DEFAULT NULL,
  `kesadaran` varchar(150) DEFAULT NULL,
  `skor_nyeri` varchar(10) DEFAULT NULL,
  `tingkat_nyeri` varchar(50) DEFAULT NULL,
  `tanda_vital_td` varchar(50) DEFAULT NULL,
  `tanda_vital_n` varchar(50) DEFAULT NULL,
  `buka_mulut` varchar(20) DEFAULT NULL,
  `gigi` varchar(50) DEFAULT NULL,
  `leher` varchar(50) DEFAULT NULL,
  `paru` varchar(150) DEFAULT NULL,
  `jantung` varchar(150) DEFAULT NULL,
  `abdomen` varchar(150) DEFAULT NULL,
  `ekstremitas` varchar(150) DEFAULT NULL,
  `lain_lain_fisik` varchar(150) DEFAULT NULL,
  `lab` varchar(150) DEFAULT NULL,
  `xray` varchar(150) DEFAULT NULL,
  `lain_lain_penunjang` varchar(150) DEFAULT NULL,
  `hasil_konsultasi` varchar(255) DEFAULT NULL,
  `premedikasi` varchar(150) DEFAULT NULL,
  `preoperatif_lain` varchar(150) DEFAULT NULL,
  `jenis_anestesi_lokal` varchar(150) DEFAULT NULL,
  `rencana_penanganan_nyeri` varchar(150) DEFAULT NULL,
  `perawatan_pasca_operatif` varchar(150) DEFAULT NULL,
  `surat_ijin_anestesi` varchar(10) DEFAULT NULL,
  `edukasi` varchar(10) DEFAULT NULL,
  `ttd_dpjp` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bmi5_bedah_minor`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bmi5_bedah_minor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `lama_pembedahan` varchar(50) DEFAULT NULL,
  `tanggal_pembedahan` date DEFAULT NULL,
  `sifat_pembedahan` varchar(50) DEFAULT NULL,
  `anestesi` varchar(50) DEFAULT NULL,
  `dpjp_bedah` varchar(100) DEFAULT NULL,
  `operator1` varchar(100) DEFAULT NULL,
  `operator2` varchar(100) DEFAULT NULL,
  `asisten` varchar(100) DEFAULT NULL,
  `diagnosis_pra_bedah` text DEFAULT NULL,
  `diagnosis_pasca_bedah` text DEFAULT NULL,
  `tindakan_pembedahan` text DEFAULT NULL,
  `jenis_pembedahan` varchar(100) DEFAULT NULL,
  `profilaksis` varchar(50) DEFAULT NULL,
  `jenis_antibiotik` varchar(100) DEFAULT NULL,
  `diberikan_jam` varchar(50) DEFAULT NULL,
  `teknik_anestesi` varchar(100) DEFAULT NULL,
  `jenis_anestesi` varchar(100) DEFAULT NULL,
  `lokasi_anestesi` varchar(100) DEFAULT NULL,
  `obat_anestesi` varchar(100) DEFAULT NULL,
  `respon_hipersensitivitas` varchar(50) DEFAULT NULL,
  `respon_hiper_ket` varchar(255) DEFAULT NULL,
  `kejadian_toksikasi` varchar(50) DEFAULT NULL,
  `kejadian_toksi_ket` varchar(255) DEFAULT NULL,
  `pra_pukul` varchar(50) DEFAULT NULL,
  `pra_kesadaran` varchar(100) DEFAULT NULL,
  `pra_td` varchar(50) DEFAULT NULL,
  `pra_nadi` varchar(50) DEFAULT NULL,
  `pra_respirasi` varchar(50) DEFAULT NULL,
  `durante_pukul` varchar(50) DEFAULT NULL,
  `durante_kesadaran` varchar(100) DEFAULT NULL,
  `durante_td` varchar(50) DEFAULT NULL,
  `durante_nadi` varchar(50) DEFAULT NULL,
  `durante_respirasi` varchar(50) DEFAULT NULL,
  `pasca_pukul` varchar(50) DEFAULT NULL,
  `pasca_kesadaran` varchar(100) DEFAULT NULL,
  `pasca_td` varchar(50) DEFAULT NULL,
  `pasca_nadi` varchar(50) DEFAULT NULL,
  `pasca_respirasi` varchar(50) DEFAULT NULL,
  `uraian_pembedahan` text DEFAULT NULL,
  `jumlah_perdarahan` varchar(100) DEFAULT NULL,
  `jaringan_patologi` varchar(50) DEFAULT NULL,
  `macam_jaringan` varchar(255) DEFAULT NULL,
  `label_ditempel` varchar(255) DEFAULT NULL,
  `menggunakan_implant` varchar(50) DEFAULT NULL,
  `no_register_implant` varchar(255) DEFAULT NULL,
  `ttd_perawat` varchar(100) DEFAULT NULL,
  `ttd_operator` varchar(100) DEFAULT NULL,
  `created_at` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `edukasi_pasien_terintegrasi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `edukasi_pasien_terintegrasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `jenis_kelamin` varchar(5) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `hambatan_emosi` tinyint(1) DEFAULT 0,
  `hambatan_motivasi` tinyint(1) DEFAULT 0,
  `hambatan_tidak_ada` tinyint(1) DEFAULT 0,
  `pantangan_hari` varchar(100) DEFAULT NULL,
  `pantangan_makan` varchar(100) DEFAULT NULL,
  `pantangan_perawatan` varchar(100) DEFAULT NULL,
  `keterbatasan_fisik` tinyint(1) DEFAULT 0,
  `keterbatasan_kognitif` tinyint(1) DEFAULT 0,
  `keterbatasan_tidak_ada` tinyint(1) DEFAULT 0,
  `kemampuan_bisa_membaca` tinyint(1) DEFAULT 0,
  `kemampuan_tidak_bisa_membaca` tinyint(1) DEFAULT 0,
  `ketersediaan_menerima_edukasi_ya` tinyint(1) DEFAULT 0,
  `ketersediaan_menerima_edukasi_tidak` tinyint(1) DEFAULT 0,
  `pendidikan_terakhir` varchar(100) DEFAULT NULL,
  `bahasa_indonesia` tinyint(1) DEFAULT 0,
  `bahasa_lainnya` varchar(100) DEFAULT NULL,
  `kebutuhan_penerjemah_tidak` tinyint(1) DEFAULT 0,
  `kebutuhan_penerjemah_ya` varchar(100) DEFAULT NULL,
  `nama_perawat` varchar(100) DEFAULT NULL,
  `nip_perawat` varchar(20) DEFAULT NULL,
  `ttd_perawat` varchar(100) DEFAULT NULL,
  `tanggal_pengkajian` date DEFAULT NULL,
  `jam_pengkajian` time DEFAULT NULL,
  `kebutuhan_edukasi` text DEFAULT NULL,
  `created_at` varchar(20) DEFAULT NULL,
  `updated_at` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `edukasi_pasien_terintegrasi_detail`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `edukasi_pasien_terintegrasi_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_edukasi` int(11) NOT NULL,
  `tanggal_jam` datetime DEFAULT NULL,
  `isi_kebutuhan_edukasi` text DEFAULT NULL,
  `kemampuan_pra_edukasi` varchar(20) DEFAULT NULL,
  `sasaran_edukasi` varchar(20) DEFAULT NULL,
  `metode_edukasi` varchar(50) DEFAULT NULL,
  `evaluasi` varchar(50) DEFAULT NULL,
  `nama_ttd_pemberi` varchar(100) DEFAULT NULL,
  `nama_ttd_penerima` varchar(100) DEFAULT NULL,
  `created_at` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_edukasi` (`id_edukasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri01b_hak_kewajiban_pasien`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri01b_hak_kewajiban_pasien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) DEFAULT NULL,
  `tanggal_jam` datetime DEFAULT NULL,
  `nama_pasien_pj` varchar(100) DEFAULT NULL,
  `nama_pemberi_edukasi` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri02_ringkasan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri02_ringkasan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) DEFAULT NULL,
  `dirawat_ke` varchar(50) DEFAULT NULL,
  `pendidikan` varchar(50) DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `saksi` varchar(100) DEFAULT NULL,
  `wali_adalah` varchar(100) DEFAULT NULL,
  `dikirim_oleh` varchar(100) DEFAULT NULL,
  `status_perkawinan` varchar(50) DEFAULT NULL,
  `cara_penerimaan` varchar(50) DEFAULT NULL,
  `peserta_phb` varchar(50) DEFAULT NULL,
  `ruang_rawat` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `mutasi_ruang` varchar(100) DEFAULT NULL,
  `mutasi_kelas` varchar(50) DEFAULT NULL,
  `bagian` varchar(100) DEFAULT NULL,
  `sebab_dirawat` text DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `tgl_keluar` date DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `lama_dirawat` varchar(50) DEFAULT NULL,
  `diagnosis_masuk` text DEFAULT NULL,
  `diagnosis_akhir` text DEFAULT NULL,
  `kode_akhir` varchar(50) DEFAULT NULL,
  `diagnosis_tambahan_1` text DEFAULT NULL,
  `kode_tambahan_1` varchar(50) DEFAULT NULL,
  `diagnosis_tambahan_2` text DEFAULT NULL,
  `kode_tambahan_2` varchar(50) DEFAULT NULL,
  `komplikasi` text DEFAULT NULL,
  `kode_komplikasi` varchar(50) DEFAULT NULL,
  `penyebab_luar` text DEFAULT NULL,
  `nama_operasi` text DEFAULT NULL,
  `gol_operasi` varchar(50) DEFAULT NULL,
  `jenis_anestesi` varchar(50) DEFAULT NULL,
  `tgl_operasi` date DEFAULT NULL,
  `kode_operasi` varchar(50) DEFAULT NULL,
  `infeksi_nosokomial` text DEFAULT NULL,
  `penyebab_infeksi` text DEFAULT NULL,
  `imunisasi_didapat` text DEFAULT NULL,
  `imunisasi_selama_dirawat` text DEFAULT NULL,
  `transfusi_darah` varchar(10) DEFAULT NULL,
  `transfusi_1` varchar(50) DEFAULT NULL,
  `transfusi_2` varchar(50) DEFAULT NULL,
  `transfusi_3` varchar(50) DEFAULT NULL,
  `keadaan_keluar` varchar(50) DEFAULT NULL,
  `sebab_kematian` text DEFAULT NULL,
  `cara_keluar` varchar(100) DEFAULT NULL,
  `dokter_ruang` varchar(100) DEFAULT NULL,
  `petugas_admissi` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri03_serah_terima`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri03_serah_terima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `diagnosa_medis` varchar(255) DEFAULT NULL,
  `asal_ruangan` varchar(100) DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `daftar_obat` text DEFAULT NULL,
  `daftar_alat` text DEFAULT NULL,
  `pemeriksaan_penunjang` text DEFAULT NULL,
  `catatan_khusus` text DEFAULT NULL,
  `perawat_asal` varchar(100) DEFAULT NULL,
  `perawat_penerima` varchar(100) DEFAULT NULL,
  `ruangan_penerima` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri04_transfer_internal`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri04_transfer_internal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) DEFAULT NULL,
  `unit_tujuan` varchar(100) DEFAULT NULL,
  `petugas_dihubungi` varchar(100) DEFAULT NULL,
  `waktu_hub_tgl` date DEFAULT NULL,
  `waktu_hub_jam` time DEFAULT NULL,
  `waktu_transfer_tgl` date DEFAULT NULL,
  `waktu_transfer_jam` time DEFAULT NULL,
  `alasan_transfer` varchar(50) DEFAULT NULL,
  `alasan_lainnya` varchar(100) DEFAULT NULL,
  `dpjp` varchar(100) DEFAULT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `ruang_kamar` varchar(100) DEFAULT NULL,
  `waktu_pindah_tgl` date DEFAULT NULL,
  `waktu_pindah_jam` time DEFAULT NULL,
  `pindah_ke_ruang` varchar(100) DEFAULT NULL,
  `diagnosis_masuk` varchar(255) DEFAULT NULL,
  `diagnosis_sekarang` varchar(255) DEFAULT NULL,
  `keluhan_utama` text DEFAULT NULL,
  `riwayat_penyakit` text DEFAULT NULL,
  `tensi` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `nadi` varchar(20) DEFAULT NULL,
  `pernafasan` varchar(20) DEFAULT NULL,
  `keadaan_umum` text DEFAULT NULL,
  `pemeriksaan_penunjang` text DEFAULT NULL,
  `tindakan_medis` text DEFAULT NULL,
  `pemberian_terapi` text DEFAULT NULL,
  `alat_terpasang` text DEFAULT NULL,
  `obat_dibawa` text DEFAULT NULL,
  `dokumen_disertakan` text DEFAULT NULL,
  `informasi_diberikan` text DEFAULT NULL,
  `kategori_transfer` varchar(20) DEFAULT NULL,
  `pre_keadaan_umum` varchar(100) DEFAULT NULL,
  `pre_kesadaran` varchar(100) DEFAULT NULL,
  `pre_tensi` varchar(20) DEFAULT NULL,
  `pre_suhu` varchar(20) DEFAULT NULL,
  `pre_nadi` varchar(20) DEFAULT NULL,
  `pre_pernafasan` varchar(20) DEFAULT NULL,
  `pre_catatan` text DEFAULT NULL,
  `post_keadaan_umum` varchar(100) DEFAULT NULL,
  `post_kesadaran` varchar(100) DEFAULT NULL,
  `post_tensi` varchar(20) DEFAULT NULL,
  `post_suhu` varchar(20) DEFAULT NULL,
  `post_nadi` varchar(20) DEFAULT NULL,
  `post_pernafasan` varchar(20) DEFAULT NULL,
  `post_catatan` text DEFAULT NULL,
  `waktu_serah_tgl` date DEFAULT NULL,
  `waktu_serah_jam` time DEFAULT NULL,
  `petugas_menyerahkan` varchar(100) DEFAULT NULL,
  `petugas_menerima` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri09_status_fungsional`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri09_status_fungsional` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `ruangan` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `s1` tinyint(4) DEFAULT NULL,
  `s2` tinyint(4) DEFAULT NULL,
  `s3` tinyint(4) DEFAULT NULL,
  `s4` tinyint(4) DEFAULT NULL,
  `s5` tinyint(4) DEFAULT NULL,
  `s6` tinyint(4) DEFAULT NULL,
  `s7` tinyint(4) DEFAULT NULL,
  `s8` tinyint(4) DEFAULT NULL,
  `s9` tinyint(4) DEFAULT NULL,
  `s10` tinyint(4) DEFAULT NULL,
  `total_skor` tinyint(4) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `petugas` varchar(100) DEFAULT NULL,
  `created_at` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri11b_pemberian_makan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri11b_pemberian_makan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `dpjp_utama` varchar(100) DEFAULT NULL,
  `nutrisionis` varchar(100) DEFAULT NULL,
  `kamar_kelas` varchar(100) DEFAULT NULL,
  `tanggal_rawat` date DEFAULT NULL,
  `diagnosa` varchar(255) DEFAULT NULL,
  `bentuk_makanan` varchar(255) DEFAULT NULL,
  `diet` varchar(255) DEFAULT NULL,
  `diet_lainnya` varchar(100) DEFAULT NULL,
  `detail_pemberian` text DEFAULT NULL,
  `ttd_nutrisionis` varchar(100) DEFAULT NULL,
  `ttd_perawat` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri14_rekonsiliasi_obat`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri14_rekonsiliasi_obat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `tanggal_mrs` date DEFAULT NULL,
  `dpjp` varchar(100) DEFAULT NULL,
  `riwayat_alergi` varchar(255) DEFAULT NULL,
  `admisi_detail` text DEFAULT NULL,
  `admisi_apoteker` varchar(100) DEFAULT NULL,
  `transfer_ruang_asal` varchar(100) DEFAULT NULL,
  `transfer_ruang_tujuan` varchar(100) DEFAULT NULL,
  `transfer_tanggal` date DEFAULT NULL,
  `transfer_detail` text DEFAULT NULL,
  `transfer_apoteker` varchar(100) DEFAULT NULL,
  `pulang_detail` text DEFAULT NULL,
  `pulang_apoteker` varchar(100) DEFAULT NULL,
  `tanggal_krs` date DEFAULT NULL,
  `status_krs` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri15_instruksi_medis`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri15_instruksi_medis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `riwayat_alergi` varchar(10) DEFAULT NULL,
  `alergi_terhadap` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri15a_instruksi_medis_farmakologi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri15a_instruksi_medis_farmakologi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `riwayat_alergi` varchar(255) DEFAULT NULL,
  `detail_obat` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri15b_jadwal_obat`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri15b_jadwal_obat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `detail_obat` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri18_edukasi_pasien`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri18_edukasi_pasien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `pengkajian` text DEFAULT NULL,
  `pelaksanaan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri19_discharge_planning`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri19_discharge_planning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `ri20_resume_pulang`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ri20_resume_pulang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `rj02a_hak_kewajiban`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rj02a_hak_kewajiban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `rj07_asesmen_ulang_nyeri`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rj07_asesmen_ulang_nyeri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `rj10_edukasi_terintegrasi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rj10_edukasi_terintegrasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `rj11_pemberian_informasi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rj11_pemberian_informasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `form_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `surat_pulang_aps`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `surat_pulang_aps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `nama_pihak` varchar(100) NOT NULL,
  `jk_pihak` enum('L','P') NOT NULL,
  `no_identitas_pihak` varchar(50) NOT NULL,
  `alamat_pihak` text NOT NULL,
  `selaku_pihak` varchar(50) NOT NULL,
  `alasan_pulang` text NOT NULL,
  `tgl_surat` date NOT NULL,
  `saksi_rs` varchar(100) DEFAULT NULL,
  `saksi_pasien` varchar(100) DEFAULT NULL,
  `pembuat_pernyataan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `lampiran_cairan_infus`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_cairan_infus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `ruangan` varchar(100) DEFAULT NULL,
  `kd_dokter` varchar(20) DEFAULT NULL,
  `dokter` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `detail_cairan` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `lampiran_form_a_mpp`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_form_a_mpp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `tgl_hari` varchar(50) DEFAULT NULL,
  `nama_mpp` varchar(150) DEFAULT NULL,
  `nip_mpp` varchar(50) DEFAULT NULL,
  `skrining_risiko_tinggi` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_potensi_komplain` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_kognitif_rendah` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_penyakit_kronis` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_gangguan_mental` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_biaya_tinggi` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_sistem_pembiayaan` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_los_memanjang` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_rencana_pemulangan` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_multiple_diagnosis` enum('Ya','Tidak') DEFAULT 'Tidak',
  `skrining_kasus_kompleks` enum('Ya','Tidak') DEFAULT 'Tidak',
  `fisik_fungsional` varchar(100) DEFAULT NULL,
  `kognitif` varchar(100) DEFAULT NULL,
  `pemahaman_kesehatan` varchar(100) DEFAULT NULL,
  `kemampuan_menerima_perubahan` varchar(100) DEFAULT NULL,
  `dukungan_keluarga` varchar(100) DEFAULT NULL,
  `kepatuhan_pasien` varchar(100) DEFAULT NULL,
  `perencanaan_lanjutan` text DEFAULT NULL,
  `finansial_status` varchar(100) DEFAULT NULL,
  `asesmen_lain_lain` text DEFAULT NULL,
  `masalah_biaya` text DEFAULT NULL,
  `masalah_keluarga` text DEFAULT NULL,
  `masalah_pemulangan` text DEFAULT NULL,
  `masalah_pemahaman` text DEFAULT NULL,
  `masalah_ketidakpatuhan` text DEFAULT NULL,
  `rencana_edukasi_ppa` enum('Ya','Tidak') DEFAULT 'Tidak',
  `rencana_koordinasi_bpjs` enum('Ya','Tidak') DEFAULT 'Tidak',
  `rencana_koordinasi_dpjp` enum('Ya','Tidak') DEFAULT 'Tidak',
  `rencana_lain_4` text DEFAULT NULL,
  `rencana_lain_5` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `lampiran_asesmen_ulang_status_fungsional`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_asesmen_ulang_status_fungsional` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `ruangan` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `s1` tinyint(4) DEFAULT NULL,
  `s2` tinyint(4) DEFAULT NULL,
  `s3` tinyint(4) DEFAULT NULL,
  `s4` tinyint(4) DEFAULT NULL,
  `s5` tinyint(4) DEFAULT NULL,
  `s6` tinyint(4) DEFAULT NULL,
  `s7` tinyint(4) DEFAULT NULL,
  `s8` tinyint(4) DEFAULT NULL,
  `s9` tinyint(4) DEFAULT NULL,
  `s10` tinyint(4) DEFAULT NULL,
  `total_skor` tinyint(4) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `petugas` varchar(150) DEFAULT NULL,
  `nip_petugas` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `lampiran_form_b_mpp`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_form_b_mpp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `tgl_pelaksanaan_1` varchar(50) DEFAULT NULL,
  `catatan_pelaksanaan` text DEFAULT NULL,
  `tgl_monitoring_2` varchar(50) DEFAULT NULL,
  `catatan_monitoring` text DEFAULT NULL,
  `tgl_fasilitasi_3` varchar(50) DEFAULT NULL,
  `catatan_fasilitasi` text DEFAULT NULL,
  `tgl_advokasi_4` varchar(50) DEFAULT NULL,
  `catatan_advokasi` text DEFAULT NULL,
  `tgl_hasil_5` varchar(50) DEFAULT NULL,
  `catatan_hasil` text DEFAULT NULL,
  `tgl_terminasi_6` varchar(50) DEFAULT NULL,
  `catatan_terminasi` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  `nama_mpp` varchar(150) DEFAULT NULL,
  `nip_mpp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `lampiran_kebutuhan_privasi_pasien`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_kebutuhan_privasi_pasien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `keluarga_dihubungi` varchar(255) DEFAULT NULL,
  `diagnosis_medis` varchar(255) DEFAULT NULL,
  `privasi_lawan_jenis` enum('Ya','Tidak') DEFAULT 'Tidak',
  `keterangan_lawan_jenis` text DEFAULT NULL,
  `privasi_orang_lain` enum('Ya','Tidak') DEFAULT 'Tidak',
  `keterangan_orang_lain` text DEFAULT NULL,
  `privasi_bagian_tubuh` enum('Ya','Tidak') DEFAULT 'Tidak',
  `keterangan_bagian_tubuh` text DEFAULT NULL,
  `privasi_informasi_penyakit` enum('Ya','Tidak') DEFAULT 'Tidak',
  `keterangan_informasi_penyakit` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  `nama_petugas` varchar(150) DEFAULT NULL,
  `nip_petugas` varchar(50) DEFAULT NULL,
  `nama_pasien_keluarga` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
-- --------------------------------------------------------
-- Struktur tabel `lampiran_meninggalkan_rs_tertentu`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_meninggalkan_rs_tertentu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `nama_pembuat` varchar(150) DEFAULT NULL,
  `jk_pembuat` enum('Laki-laki','Perempuan') DEFAULT 'Laki-laki',
  `no_identitas_pembuat` varchar(50) DEFAULT NULL,
  `alamat_pembuat` text DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT 'Diri Sendiri',
  `hubungan_detail` varchar(100) DEFAULT NULL,
  `ruang_perawatan` varchar(100) DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  `saksi_rs` varchar(150) DEFAULT NULL,
  `nip_saksi_rs` varchar(50) DEFAULT NULL,
  `saksi_pasien` varchar(150) DEFAULT NULL,
  `pembuat_pernyataan_ttd` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
-- --------------------------------------------------------
-- Struktur tabel `lampiran_penghentian_pengobatan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penghentian_pengobatan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `nama_pembuat` varchar(150) DEFAULT NULL,
  `umur_pembuat` varchar(10) DEFAULT NULL,
  `alamat_pembuat` text DEFAULT NULL,
  `no_telp_pembuat` varchar(50) DEFAULT NULL,
  `bukti_diri_pembuat` varchar(100) DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT 'Diri Sendiri',
  `hubungan_detail` varchar(100) DEFAULT NULL,
  `bukti_diri_pasien` varchar(100) DEFAULT NULL,
  `perawatan_kelas_kamar` varchar(150) DEFAULT NULL,
  `alasan_penghentian` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  `jam_ttd` varchar(20) DEFAULT NULL,
  `nama_petugas` varchar(150) DEFAULT NULL,
  `nip_petugas` varchar(50) DEFAULT NULL,
  `pembuat_pernyataan_ttd` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 44. LAMPIRAN - FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ)
-- ============================================================
CREATE TABLE IF NOT EXISTS `lampiran_profil_ringkas_medis_rajal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal_formulir` date DEFAULT NULL,
  `diagnosa_dpjp` longtext DEFAULT NULL,
  `riwayat_alergi` longtext DEFAULT NULL,
  `diagnosa_ranap` longtext DEFAULT NULL,
  `operasi_tindakan` longtext DEFAULT NULL,
  `obat_obatan` longtext DEFAULT NULL,
  `keterangan_tambahan` text DEFAULT NULL,
  `nama_dokter` varchar(150) DEFAULT NULL,
  `nip_dokter` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 45. LAMPIRAN - FORMULIR SKRINING DARI LUAR RUMAH SAKIT
-- ============================================================
CREATE TABLE IF NOT EXISTS `lampiran_skrining_luar_rs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal_skrining` date DEFAULT NULL,
  `jam_skrining` time DEFAULT NULL,
  `status_pembayaran` varchar(50) DEFAULT NULL,
  `asal_rujukan` varchar(255) DEFAULT NULL,
  `alasan_dirujuk` text DEFAULT NULL,
  `diagnosa` text DEFAULT NULL,
  `keadaan_umum` varchar(100) DEFAULT NULL,
  `kesadaran` varchar(50) DEFAULT NULL,
  `tensi` varchar(20) DEFAULT NULL,
  `nadi` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `respirasi` varchar(20) DEFAULT NULL,
  `terapi` text DEFAULT NULL,
  `tindakan` text DEFAULT NULL,
  `tujuan` varchar(255) DEFAULT NULL,
  `tujuan_lainnya` varchar(255) DEFAULT NULL,
  `keterangan_status` enum('TERIMA','TOLAK') DEFAULT 'TERIMA',
  `alasan_keterangan` text DEFAULT NULL,
  `nama_petugas` varchar(150) DEFAULT NULL,
  `nip_petugas` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_skrining` (`tanggal_skrining`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- 47. LAMPIRAN - INTERVENSI DAN PENGKAJIAN ULANG NYERI (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_intervensi_pengkajian_nyeri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `instalasi_unit` varchar(150) DEFAULT NULL,
  `detail_pengkajian` text DEFAULT NULL,
  `catatan_tambahan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 48. LAMPIRAN - SURAT PERMINTAAN SECOND OPINION (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_permintaan_second_opinion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `nama_pembuat` varchar(150) DEFAULT NULL,
  `jk_pembuat` enum('L','P') DEFAULT 'L',
  `no_identitas_pembuat` varchar(50) DEFAULT NULL,
  `umur_pembuat` varchar(20) DEFAULT NULL,
  `alamat_pembuat` text DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT 'Diri sendiri',
  `hubungan_detail` varchar(100) DEFAULT NULL,
  `dokter_tujuan` varchar(150) DEFAULT NULL,
  `rs_tujuan` varchar(200) DEFAULT NULL,
  `dokumen_penunjang` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_ttd` date DEFAULT NULL,
  `nama_petugas` varchar(150) DEFAULT NULL,
  `nip_petugas` varchar(50) DEFAULT NULL,
  `nama_saksi` varchar(150) DEFAULT NULL,
  `pembuat_pernyataan_ttd` varchar(150) DEFAULT NULL,
  `tgl_pengembalian_dokumen` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 49. LAMPIRAN - PENILAIAN KEJADIAN PHLEIBITIS (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penilaian_kejadian_phlebitis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `lokasi_iv_line` varchar(100) DEFAULT NULL,
  `tgl_pemasangan` date DEFAULT NULL,
  `tipe_kanul` varchar(50) DEFAULT NULL,
  `detail_penilaian` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 50. LAMPIRAN - PERMINTAAN PELAYANAN KEROHANIAN (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_permintaan_pelayanan_kerohanian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `nama_pemohon` varchar(150) DEFAULT NULL,
  `tgl_lahir_pemohon` date DEFAULT NULL,
  `umur_pemohon` varchar(50) DEFAULT NULL,
  `alamat_pemohon` text DEFAULT NULL,
  `hubungan_pasien` varchar(100) DEFAULT NULL,
  `agama_kepercayaan` varchar(150) DEFAULT NULL,
  `kebutuhan_khusus` text DEFAULT NULL,
  `nama_rohaniawan` varchar(150) DEFAULT NULL,
  `kota` varchar(50) DEFAULT 'Banjarmasin',
  `tanggal_surat` date DEFAULT NULL,
  `nama_perawat` varchar(150) DEFAULT NULL,
  `nip_perawat` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 51. LAMPIRAN - FORMULIR MONITORING PASIEN KATETER URIN (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_monitoring_pasien_kateter_urin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `ruang` varchar(150) DEFAULT NULL,
  `bulan_tahun` varchar(50) DEFAULT NULL,
  `tgl_mrs` date DEFAULT NULL,
  `diagnosa` varchar(255) DEFAULT NULL,
  `jenis_cath` varchar(50) DEFAULT NULL,
  `jenis_cath_lainnya` varchar(100) DEFAULT NULL,
  `no_cath` varchar(20) DEFAULT NULL,
  `no_cath_lainnya` varchar(50) DEFAULT NULL,
  `detail_monitoring` longtext DEFAULT NULL,
  `nama_icn` varchar(150) DEFAULT NULL,
  `nip_icn` varchar(50) DEFAULT NULL,
  `nama_head_nurse` varchar(150) DEFAULT NULL,
  `nip_head_nurse` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 52. LAMPIRAN - FORMULIR KONSULTASI INTERNAL (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_konsultasi_internal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `ts_bagian` varchar(150) DEFAULT NULL,
  `instalasi` varchar(150) DEFAULT NULL,
  `diagnosa_sementara` text DEFAULT NULL,
  `konsultasi_diminta` text DEFAULT NULL,
  `tgl_konsul` date DEFAULT NULL,
  `kd_dokter_pengirim` varchar(20) DEFAULT NULL,
  `nama_dokter_pengirim` varchar(150) DEFAULT NULL,
  `jawaban_hasil_pemeriksaan` text DEFAULT NULL,
  `jawaban_diagnosa` text DEFAULT NULL,
  `jawaban_nasihat` text DEFAULT NULL,
  `tgl_jawaban` date DEFAULT NULL,
  `kd_dokter_konsulen` varchar(20) DEFAULT NULL,
  `nama_dokter_konsulen` varchar(150) DEFAULT NULL,
  `status_konsul` enum('Menunggu Jawaban','Sudah Dijawab') DEFAULT 'Menunggu Jawaban',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 53. LAMPIRAN - FORMULIR MONITORING PASIEN SELAMA TRANSFER (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_monitoring_pasien_selama_transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `tanggal_transfer` date DEFAULT NULL,
  `status_kegawatdaruratan` enum('Merah','Kuning','Hijau','Hitam') DEFAULT 'Kuning',
  `jenis_kasus` enum('Trauma','Non Trauma') DEFAULT 'Non Trauma',
  `diagnosa` text DEFAULT NULL,
  `monitoring_keterangan` text DEFAULT NULL,
  `petugas_transfer` varchar(150) DEFAULT NULL,
  `nip_petugas_transfer` varchar(50) DEFAULT NULL,
  `jam_berangkat` time DEFAULT NULL,
  `jam_tiba` time DEFAULT NULL,
  `detail_monitoring` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_transfer` (`tanggal_transfer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- --------------------------------------------------------
-- 54. LAMPIRAN - FORMULIR PENILAIAN DEKUBITUS (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penilaian_dekubitus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `tanggal_penilaian` date DEFAULT NULL,
  `detail_penilaian` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_penilaian` (`tanggal_penilaian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 55. LAMPIRAN - FORMULIR PENILAIAN KEJADIAN INFEKSI LUKA OPERASI (ILO) (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penilaian_kejadian_ilo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `tanggal_penilaian` date DEFAULT NULL,
  `detail_penilaian` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_penilaian` (`tanggal_penilaian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 56. LAMPIRAN - FORMULIR PENILAIAN KEJADIAN INFEKSI SALURAN KEMIH (ISK) (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penilaian_kejadian_isk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kamar_inap` varchar(150) DEFAULT NULL,
  `tanggal_penilaian` date DEFAULT NULL,
  `detail_penilaian` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_penilaian` (`tanggal_penilaian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 57. LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_surat_pernyataan_bersedia_menunggu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `nama_pj` varchar(100) NOT NULL,
  `tgl_lahir_pj` date DEFAULT NULL,
  `jk_pj` enum('Laki-laki','Perempuan') DEFAULT 'Laki-laki',
  `alamat_pj` text DEFAULT NULL,
  `no_identitas_pj` varchar(30) DEFAULT NULL,
  `hubungan` varchar(50) DEFAULT 'Diri Saya Sendiri',
  `nama_pasien` varchar(100) DEFAULT NULL,
  `tgl_lahir_pasien` date DEFAULT NULL,
  `jk_pasien` enum('Laki-laki','Perempuan') DEFAULT 'Laki-laki',
  `alamat_pasien` text DEFAULT NULL,
  `ruang_menunggu` varchar(150) DEFAULT 'ruang perawatan biasa / ruang perawatan intensif',
  `tanggal_surat` date DEFAULT NULL,
  `kd_dokter` varchar(20) DEFAULT NULL,
  `nama_dokter` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_surat` (`tanggal_surat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 58. LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_penundaan_kelambatan_pelayanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `instalasi_unit` varchar(100) DEFAULT NULL,
  `kd_dokter_pengirim` varchar(20) DEFAULT NULL,
  `nama_dokter_pengirim` varchar(100) DEFAULT NULL,
  `jenis_pelayanan` varchar(150) DEFAULT NULL,
  `status_penerima` enum('Pasien Sendiri','Keluarga','Penanggung Jawab Pasien') DEFAULT 'Pasien Sendiri',
  `nama_penerima` varchar(100) DEFAULT NULL,
  `alamat_penerima` text DEFAULT NULL,
  `no_identitas_penerima` varchar(30) DEFAULT NULL,
  `sebab_kerusakan_alat` tinyint(1) DEFAULT 0,
  `sebab_kondisi_umum_pasien` tinyint(1) DEFAULT 0,
  `sebab_penundaan_penjadwalan` tinyint(1) DEFAULT 0,
  `sebab_pemadaman_listrik` tinyint(1) DEFAULT 0,
  `sebab_tempat_tidur_penuh` tinyint(1) DEFAULT 0,
  `sebab_faktor_penyulit` tinyint(1) DEFAULT 0,
  `keterangan_faktor_penyulit` varchar(255) DEFAULT NULL,
  `solusi_dijadwalkan_ulang` tinyint(1) DEFAULT 0,
  `jadwal_akan_datang` varchar(150) DEFAULT NULL,
  `solusi_dirujuk` tinyint(1) DEFAULT 0,
  `tujuan_rujukan` varchar(150) DEFAULT NULL,
  `solusi_kembali_dokter_pengirim` tinyint(1) DEFAULT 0,
  `kd_dokter_dpjp` varchar(20) DEFAULT NULL,
  `nama_dokter_dpjp` varchar(100) DEFAULT NULL,
  `kd_pemberi_informasi` varchar(20) DEFAULT NULL,
  `nama_pemberi_informasi` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 59. LAMPIRAN - PERSETUJUAN UNTUK TESTING HIV (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_persetujuan_testing_hiv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kode_klien` varchar(50) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_pelaksanaan` date DEFAULT NULL,
  `nama_pasien` varchar(100) DEFAULT NULL,
  `kd_konselor` varchar(20) DEFAULT NULL,
  `nama_konselor` varchar(100) DEFAULT NULL,
  `status_persetujuan` enum('Setuju','Menolak') DEFAULT 'Setuju',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------
-- 60. LAMPIRAN - VCT PRA TESTING HIV (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_vct_pra_testing_hiv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `kode_klien` varchar(50) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_tes_klien` date DEFAULT NULL,
  `tempat_tes` varchar(100) DEFAULT NULL,
  `jenis_klien` varchar(20) DEFAULT NULL,
  `status_hubungan` varchar(30) DEFAULT NULL,
  `jumlah_anak` varchar(20) DEFAULT NULL,
  `umur_klien` varchar(20) DEFAULT NULL,
  `pendidikan` varchar(30) DEFAULT NULL,
  `pekerjaan` varchar(50) DEFAULT NULL,
  `pekerjaan_lain` varchar(100) DEFAULT NULL,
  `pasangan_tetap` varchar(10) DEFAULT NULL,
  `status_pasangan` varchar(20) DEFAULT NULL,
  `tgl_tes_terakhir` date DEFAULT NULL,
  `detail_pajanan` text DEFAULT NULL,
  `tes_ulang_window` varchar(10) DEFAULT 'TIDAK',
  `tgl_tes_ulang` date DEFAULT NULL,
  `berisiko_orang_hiv` varchar(10) DEFAULT NULL,
  `klien_hamil` varchar(10) DEFAULT NULL,
  `trimester_kehamilan` varchar(20) DEFAULT NULL,
  `kb_teratur` varchar(10) DEFAULT NULL,
  `riwayat_ims` varchar(10) DEFAULT NULL,
  `gejala_tb` varchar(10) DEFAULT NULL,
  `indikasi_bunuh_diri` varchar(10) DEFAULT NULL,
  `riwayat_melukai_diri` varchar(10) DEFAULT NULL,
  `mencederai_orang_lain` varchar(10) DEFAULT NULL,
  `kekerasan_pasangan` varchar(10) DEFAULT NULL,
  `dukungan_personal` varchar(10) DEFAULT NULL,
  `catatan_penyesuaian` text DEFAULT NULL,
  `intervensi_konselor` text DEFAULT NULL,
  `catatan_lainnya` text DEFAULT NULL,
  `hasil_pemeriksaan` varchar(50) DEFAULT NULL,
  `tanggal_mengetahui` date DEFAULT NULL,
  `kd_konselor` varchar(20) DEFAULT NULL,
  `nama_konselor` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 61. LAMPIRAN - PERSETUJUAN PROSEDUR TINDAKAN RADIOLOGI (Rajal)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_persetujuan_tindakan_radiologi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `nama_penanggung_jawab` varchar(150) DEFAULT NULL,
  `jk_penanggung_jawab` enum('L','P') DEFAULT 'L',
  `tmp_lahir_penanggung_jawab` varchar(100) DEFAULT NULL,
  `tgl_lahir_penanggung_jawab` date DEFAULT NULL,
  `usia_penanggung_jawab` varchar(20) DEFAULT NULL,
  `no_identitas_penanggung_jawab` varchar(50) DEFAULT NULL,
  `jenis_identitas_penanggung_jawab` varchar(30) DEFAULT 'KTP',
  `alamat_penanggung_jawab` text DEFAULT NULL,
  `kelurahan_penanggung_jawab` varchar(100) DEFAULT NULL,
  `kecamatan_penanggung_jawab` varchar(100) DEFAULT NULL,
  `kabupaten_penanggung_jawab` varchar(100) DEFAULT NULL,
  `provinsi_penanggung_jawab` varchar(100) DEFAULT NULL,
  `hubungan_pasien` varchar(50) DEFAULT 'Diri Sendiri',
  `hubungan_pasien_lainnya` varchar(100) DEFAULT NULL,
  `tindakan_radiologi` text DEFAULT NULL,
  `nama_pasien_terhadap` varchar(150) DEFAULT NULL,
  `jk_pasien_terhadap` enum('L','P') DEFAULT 'L',
  `tmp_lahir_pasien_terhadap` varchar(100) DEFAULT NULL,
  `tgl_lahir_pasien_terhadap` date DEFAULT NULL,
  `usia_pasien_terhadap` varchar(20) DEFAULT NULL,
  `alamat_pasien_terhadap` text DEFAULT NULL,
  `kelurahan_pasien_terhadap` varchar(100) DEFAULT NULL,
  `kecamatan_pasien_terhadap` varchar(100) DEFAULT NULL,
  `kabupaten_pasien_terhadap` varchar(100) DEFAULT NULL,
  `provinsi_pasien_terhadap` varchar(100) DEFAULT NULL,
  `kota_surat` varchar(100) DEFAULT 'Banjarmasin',
  `tanggal_surat` date DEFAULT NULL,
  `jam_surat` time DEFAULT NULL,
  `nama_yang_menyatakan` varchar(150) DEFAULT NULL,
  `kd_dokter` varchar(20) DEFAULT NULL,
  `nama_dokter` varchar(150) DEFAULT NULL,
  `nama_saksi_1` varchar(150) DEFAULT NULL,
  `nama_saksi_2` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 62. LAMPIRAN - EWS DEWASA DAN ANAK (Ranap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lampiran_ews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `ruangan` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `detail_dewasa` longtext DEFAULT NULL,
  `detail_anak` longtext DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `kd_petugas` varchar(20) DEFAULT NULL,
  `nama_petugas` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 63. PENANDAAN TEPAT LOKASI BEDAH MULUT (Bedah - Form Wajib)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `penandaan_lokasi_bedah_mulut` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `jenis_tindakan` text DEFAULT NULL,
  `gigi_centang` text DEFAULT NULL,
  `markers_anatomi` longtext DEFAULT NULL,
  `gambar_penandaan` longtext DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `nama_pasien_keluarga` varchar(150) DEFAULT NULL,
  `hubungan_pasien` varchar(50) DEFAULT 'Pasien',
  `kd_dokter` varchar(20) DEFAULT NULL,
  `nama_dokter` varchar(150) DEFAULT NULL,
  `nama_saksi` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 64. ANS5 - STATUS ANESTESI (Bedah - Form Wajib)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ans5_status_anestesi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `diagnosa_prabedah` text DEFAULT NULL,
  `diagnosa_pascabedah` text DEFAULT NULL,
  `status_fisik` varchar(10) DEFAULT NULL,
  `gol_darah` varchar(5) DEFAULT NULL,
  `rh` varchar(5) DEFAULT NULL,
  `bb` varchar(10) DEFAULT NULL,
  `tb` varchar(10) DEFAULT NULL,
  `tindakan` text DEFAULT NULL,
  `dr_anestesi` varchar(150) DEFAULT NULL,
  `kd_dokter_anestesi` varchar(20) DEFAULT NULL,
  `penata_anestesi` varchar(150) DEFAULT NULL,
  `dr_operator` varchar(150) DEFAULT NULL,
  `kd_dokter_operator` varchar(20) DEFAULT NULL,
  `waktu_mulai_pembiusan` varchar(10) DEFAULT NULL,
  `waktu_selesai_pembiusan` varchar(10) DEFAULT NULL,
  `lama_pembiusan` varchar(50) DEFAULT NULL,
  `preinduksi_td_sistolik` varchar(10) DEFAULT NULL,
  `preinduksi_td_diastolik` varchar(10) DEFAULT NULL,
  `preinduksi_nadi` varchar(10) DEFAULT NULL,
  `preinduksi_rr` varchar(10) DEFAULT NULL,
  `preinduksi_suhu` varchar(10) DEFAULT NULL,
  `preinduksi_vas` varchar(10) DEFAULT NULL,
  `grid_data` longtext DEFAULT NULL,
  `masalah` text DEFAULT NULL,
  `prosedur_loading_cairan` text DEFAULT NULL,
  `prosedur_premedikasi` text DEFAULT NULL,
  `prosedur_maintenance` text DEFAULT NULL,
  `jenis_anestesi_general` enum('Y','N') DEFAULT 'N',
  `jenis_anestesi_general_tipe` varchar(100) DEFAULT NULL,
  `jenis_anestesi_regional` enum('Y','N') DEFAULT 'N',
  `jenis_anestesi_regional_tipe` varchar(100) DEFAULT NULL,
  `jenis_anestesi_lain` varchar(255) DEFAULT NULL,
  `jalan_napas_nasal` enum('Y','N') DEFAULT 'N',
  `jalan_napas_facemask` enum('Y','N') DEFAULT 'N',
  `jalan_napas_lma` enum('Y','N') DEFAULT 'N',
  `jalan_napas_orotrakeal` enum('Y','N') DEFAULT 'N',
  `jalan_napas_lain` varchar(255) DEFAULT NULL,
  `posisi_supine` enum('Y','N') DEFAULT 'N',
  `posisi_lateral` enum('Y','N') DEFAULT 'N',
  `posisi_prone` enum('Y','N') DEFAULT 'N',
  `posisi_lithotomi` enum('Y','N') DEFAULT 'N',
  `posisi_lain` varchar(255) DEFAULT NULL,
  `penyulit_pembedahan` text DEFAULT NULL,
  `keadaan_akhir_pembedahan` text DEFAULT NULL,
  `dokter_anestesi_ttd` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bm5_checklist_keselamatan_operasi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bm5_checklist_keselamatan_operasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,

  -- SIGN IN (Sebelum Induksi)
  `sign_in_tanggal` date DEFAULT NULL,
  `sign_in_jam` varchar(10) DEFAULT NULL,
  `sign_in_identitas` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_in_informed_consent` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_in_tanda_operasi` enum('Ya','Tidak Diperlukan') DEFAULT 'Ya',
  `sign_in_riwayat_alergi` enum('Ya','Tidak') DEFAULT 'Tidak',
  `sign_in_alergi_ket` varchar(255) DEFAULT NULL,
  `sign_in_persiapan_alat` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_in_pemeriksaan_penunjang` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_in_dr_operator` varchar(150) DEFAULT NULL,
  `sign_in_perawat` varchar(150) DEFAULT NULL,

  -- TIME OUT (Sebelum Insisi)
  `time_out_tanggal` date DEFAULT NULL,
  `time_out_jam` varchar(10) DEFAULT NULL,
  `time_out_perkenalan_tim` enum('Ya','Tidak') DEFAULT 'Ya',
  `time_out_konfirmasi_pasien` enum('Ya','Tidak') DEFAULT 'Ya',
  `time_out_antibiotik_profilaksis` enum('Ya','Tidak Perlu') DEFAULT 'Ya',
  `time_out_tindakan_kritis` enum('Ya','Tidak') DEFAULT 'Ya',
  `time_out_estimasi_waktu` varchar(100) DEFAULT NULL,
  `time_out_antisipasi_perdarahan` varchar(255) DEFAULT NULL,
  `time_out_sterilitas` enum('Ya','Tidak') DEFAULT 'Ya',
  `time_out_masalah_peralatan` varchar(255) DEFAULT NULL,
  `time_out_hasil_radiologi` enum('Ya','Tidak Diperlukan') DEFAULT 'Ya',
  `time_out_dr_operator` varchar(150) DEFAULT NULL,
  `time_out_perawat` varchar(150) DEFAULT NULL,

  -- SIGN OUT (Sebelum Pasien Meninggalkan Ruang Operasi)
  `sign_out_tanggal` date DEFAULT NULL,
  `sign_out_jam` varchar(10) DEFAULT NULL,
  `sign_out_konfirmasi_tindakan` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_out_nama_tindakan` varchar(255) DEFAULT NULL,
  `sign_out_kelengkapan_alat` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_out_pelabelan_spesimen` enum('Ya','Tidak','Tidak Ada') DEFAULT 'Ya',
  `sign_out_masalah_peralatan` varchar(255) DEFAULT NULL,
  `sign_out_rencana_kontrol` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_out_komplikasi_tindakan` enum('Ya','Tidak') DEFAULT 'Ya',
  `sign_out_dr_operator` varchar(150) DEFAULT NULL,
  `sign_out_perawat` varchar(150) DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `bmi3_asesmen_bedah_minor`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bmi3_asesmen_bedah_minor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal_operasi` date DEFAULT NULL,
  `jam_operasi` time DEFAULT NULL,

  -- Anamnesis
  `jenis_anamnesis` enum('Autoanamnesis','Alloanamnesis') DEFAULT 'Autoanamnesis',
  `anamnesis` text DEFAULT NULL,
  `keluhan_utama` text DEFAULT NULL,

  -- Riwayat Penyakit Pasien
  `rp_hipertensi` enum('0','1') DEFAULT '0',
  `rp_diabetes` enum('0','1') DEFAULT '0',
  `rp_gastritis` enum('0','1') DEFAULT '0',
  `rp_darah` enum('0','1') DEFAULT '0',
  `rp_jantung` enum('0','1') DEFAULT '0',
  `rp_hati` enum('0','1') DEFAULT '0',
  `rp_ginjal` enum('0','1') DEFAULT '0',
  `rp_pengobatan_panjang` enum('0','1') DEFAULT '0',
  `rp_operasi` enum('0','1') DEFAULT '0',
  `rp_alergi_obat` enum('0','1') DEFAULT '0',
  `rp_alergi_makanan` enum('0','1') DEFAULT '0',
  `rp_sistemik_lain` enum('0','1') DEFAULT '0',
  `rp_tidak_ada` enum('0','1') DEFAULT '1',
  `rp_keterangan` varchar(255) DEFAULT NULL,

  -- Riwayat Alergi
  `riwayat_alergi` text DEFAULT NULL,

  -- Riwayat Pengobatan
  `pengobatan_pengencer_darah` enum('0','1') DEFAULT '0',
  `pengobatan_osteoporosis_kanker` enum('0','1') DEFAULT '0',
  `pengobatan_antibiotik` enum('0','1') DEFAULT '0',
  `pengobatan_analgetik` enum('0','1') DEFAULT '0',
  `pengobatan_keterangan` varchar(255) DEFAULT NULL,

  -- Riwayat Penyakit Keluarga
  `rpk_hipertensi` enum('0','1') DEFAULT '0',
  `rpk_diabetes` enum('0','1') DEFAULT '0',
  `rpk_gastritis` enum('0','1') DEFAULT '0',
  `rpk_darah` enum('0','1') DEFAULT '0',
  `rpk_jantung` enum('0','1') DEFAULT '0',
  `rpk_hati` enum('0','1') DEFAULT '0',
  `rpk_ginjal` enum('0','1') DEFAULT '0',
  `rpk_pengobatan_panjang` enum('0','1') DEFAULT '0',
  `rpk_operasi` enum('0','1') DEFAULT '0',
  `rpk_alergi_obat` enum('0','1') DEFAULT '0',
  `rpk_alergi_makanan` enum('0','1') DEFAULT '0',
  `rpk_sistemik_lain` enum('0','1') DEFAULT '0',
  `rpk_tidak_ada` enum('0','1') DEFAULT '1',
  `rpk_keterangan` varchar(255) DEFAULT NULL,

  -- Pemeriksaan Fisik
  `fisik_ekstraoral_check` enum('0','1') DEFAULT '0',
  `fisik_ekstraoral` text DEFAULT NULL,
  `fisik_intraoral_check` enum('0','1') DEFAULT '0',
  `fisik_intraoral` text DEFAULT NULL,

  -- Pemeriksaan Penunjang, Diagnosis, Rencana
  `pemeriksaan_penunjang` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `rencana_tindakan` text DEFAULT NULL,

  -- Dokter Gigi
  `kd_dokter` varchar(20) DEFAULT NULL,
  `nm_dokter` varchar(150) DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_operasi` (`tanggal_operasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;






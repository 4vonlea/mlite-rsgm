# Database Changelog — mlitersgm

File ini mencatat semua perubahan struktur database yang dilakukan selama development lokal.
Gunakan file ini sebagai referensi saat deployment ke production.

---

## Format Entry

```
## [YYYY-MM-DD] vX.X — [Keterangan Fitur/Fix]
Tabel yang diubah, SQL yang perlu dijalankan, alasan perubahan.
```

## [2026-10-25] v1.44 — Form RME Bedah: BM3 - Asesmen Pra Bedah Mayor (Gigi dan Mulut) [2 Halaman]

### Tabel Baru: `bm3_asesmen_pra_bedah_mayor`

**SQL untuk production:**
```sql
CREATE TABLE IF NOT EXISTS `bm3_asesmen_pra_bedah_mayor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `no_rkm_medis` varchar(15) NOT NULL,
  `tanggal_pengkajian` datetime NOT NULL,
  `kd_dokter` varchar(20) NOT NULL,
  `keluhan_utama` text DEFAULT NULL,
  `alergi_obat` varchar(255) DEFAULT NULL,
  `merokok` varchar(50) DEFAULT NULL,
  `riwayat_penyakit` text DEFAULT NULL,
  `keadaan_umum` varchar(100) DEFAULT NULL,
  `kesadaran` varchar(50) DEFAULT NULL,
  `gcs_e` varchar(10) DEFAULT NULL,
  `gcs_v` varchar(10) DEFAULT NULL,
  `gcs_m` varchar(10) DEFAULT NULL,
  `td` varchar(20) DEFAULT NULL,
  `nadi` varchar(20) DEFAULT NULL,
  `pernafasan` varchar(20) DEFAULT NULL,
  `suhu` varchar(20) DEFAULT NULL,
  `spo2` varchar(20) DEFAULT NULL,
  `lokalis_gigi` text DEFAULT NULL,
  `ket_lokalis` text DEFAULT NULL,
  `pemeriksaan_generalis` text DEFAULT NULL,
  `lab_dpl` varchar(255) DEFAULT NULL,
  `lab_hemostatis` varchar(255) DEFAULT NULL,
  `lab_lain` varchar(255) DEFAULT NULL,
  `kesan_lab` text DEFAULT NULL,
  `rad_toraks` varchar(255) DEFAULT NULL,
  `rad_panoramik` varchar(255) DEFAULT NULL,
  `rad_ctscan` varchar(255) DEFAULT NULL,
  `rad_lain` varchar(255) DEFAULT NULL,
  `kesan_rad` text DEFAULT NULL,
  `ekg_dilakukan` varchar(255) DEFAULT NULL,
  `kesan_ekg` varchar(255) DEFAULT NULL,
  `penunjang_lain` varchar(255) DEFAULT NULL,
  `kesan_penunjang_lain` varchar(255) DEFAULT NULL,
  `diagnosis_pra_bedah` text DEFAULT NULL,
  `rencana_operasi` text DEFAULT NULL,
  `rencana_anestesi` varchar(50) DEFAULT NULL,
  `evaluasi_masalah` text DEFAULT NULL,
  `evaluasi_potensi` text DEFAULT NULL,
  `evaluasi_antisipasi` text DEFAULT NULL,
  `rencana_kerja_terapi` text DEFAULT NULL,
  `catatan_penting` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `kd_dokter` (`kd_dokter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Keterangan:**
Menyimpan formulir Asesmen Pra Bedah Mayor (BM3) pada Unit Bedah Sentral (UBS) RSGM Gusti Hasan Aman. Formulir ini menghasilkan output cetak & PDF 2 halaman pas (A4 Portrait) yang mencakup identitas, anamnesis, TTV & GCS, status lokalis odontogram & gambar anatomi tubuh/kepala, pemeriksaan penunjang (lab/radiologi/EKG), asesmen diagnosis, rencana anestesi, evaluasi pra bedah, rencana terapi, serta verifikasi operator/DPJP.

---

## [2026-10-25] v1.43 — Form RME Bedah: BMI3 - Asesmen Bedah Minor (Gigi dan Mulut)

### Tabel Baru: `bmi3_asesmen_bedah_minor`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Implementasi formulir Asesmen Bedah Minor (Gigi dan Mulut) sesuai formulir fisik RSGM Gusti Hasan Aman kode BMI3.

**File yang dibuat / dimodifikasi:**
- `themes/admin/index.html` — Registrasi menu di Rekam Medis -> Form RME -> Bedah -> Form Wajib -> BMI3 - ASESMEN BEDAH MINOR
- `plugins/update_bmt/Admin.php` — Controller methods (`anyBmi3manage`, `anyBmi3form`, `anyBmi3save`, `anyBmi3hapus`, `anyBmi3cetak`)
- `plugins/update_bmt/view/admin/bmi3/manage.html` — Manajemen data DataTables
- `plugins/update_bmt/view/admin/bmi3/form.html` — Form input interaktif & modal cari pasien
- `plugins/update_bmt/view/admin/bmi3/cetak.html` — Output cetak dan PDF mPDF Portrait A4
- `plugins/update_bmt/migration.sql` — Skrip DDL migrasi database

---

## [2026-10-25] v1.42 — Form RME Bedah: BM5 - Checklist Keselamatan Pasien Operasi

### Tabel Baru: `bm5_checklist_keselamatan_operasi`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Implementasi formulir keselamatan pasien bedah (WHO Surgical Safety Checklist) sesuai standar akreditasi rumah sakit dan formulir fisik RSGM Gusti Hasan Aman kode BM5 (Sign In, Time Out, Sign Out).

**File yang dibuat / dimodifikasi:**
- `themes/admin/index.html` — Registrasi menu di Rekam Medis -> Form RME -> Bedah -> Form Wajib -> BM5
- `plugins/update_bmt/Admin.php` — Controller navigation dan methods (`anyBm5manage`, `anyBm5form`, `anyBm5save`, `anyBm5hapus`, `anyBm5cetak`)
- `plugins/update_bmt/view/admin/bm5/manage.html` — DataTables list management & actions
- `plugins/update_bmt/view/admin/bm5/form.html` — Form input interaktif dengan modal pasien dan pencarian dokter
- `plugins/update_bmt/view/admin/bm5/cetak.html` — Output cetak dan PDF mPDF Landscape A4 presisi sesuai scan fisik
- `plugins/update_bmt/migration.sql` — Skrip DDL migrasi database

---

## [2026-07-23] v1.1 — Fitur Validasi Resep & Penyerahan Obat (Apotek Ralan)

### Tabel: `resep_obat`

**SQL untuk production:**
```sql
ALTER TABLE resep_obat
  ADD COLUMN petugas_validasi VARCHAR(100) NULL AFTER jam_penyerahan;

ALTER TABLE resep_obat
  ADD COLUMN petugas_penyerahan VARCHAR(100) NULL AFTER petugas_validasi;

ALTER TABLE resep_obat
  ADD COLUMN catatan_skrining TEXT NULL AFTER petugas_penyerahan;
```

**Kolom baru:**
- `petugas_validasi` — Nama apoteker yang melakukan validasi resep
- `petugas_penyerahan` — Nama petugas yang melakukan penyerahan obat ke pasien
- `catatan_skrining` — Catatan skrining apoteker atau catatan serah terima

**Alasan:**
Menambahkan fitur skrining resep farmasi klinis dengan dialog validasi dan tanda serah terima obat.
Kolom ini menyimpan jejak audit (audit trail) proses farmasi rawat jalan.

**File kode yang diubah:**
- `plugins/apotek_ralan/Admin.php` — postValidasiResep(): simpan nama petugas & catatan
- `plugins/apotek_ralan/Admin.php` — anyRincian(): tambah SELECT eksplisit resep_obat.*
- `plugins/apotek_ralan/view/admin/rincian.html` — tambah modal validasi & penyerahan + info petugas
- `plugins/apotek_ralan/js/admin/apotek_ralan.js` — handler modal baru

---

## [2026-07-23] v1.0 — Fix Kompatibilitas MySQL 8 Strict Mode

### Tidak ada perubahan struktur tabel

**Perubahan sesi MySQL (dijalankan otomatis lewat kode):**
```sql
SET SESSION sql_mode = (SELECT REPLACE(REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''));
```

**Alasan:**
MySQL 8 strict mode memblokir nilai tanggal `0000-00-00` yang digunakan sistem mLITE
sebagai penanda status "belum diproses". Fix dilakukan per-session lewat QueryWrapper.php.

**File kode yang diubah:**
- `systems/lib/QueryWrapper.php` — connect(): tambah SET SESSION sql_mode

**Catatan production:**
Jika production menggunakan MySQL 5.7 atau mode non-strict, perubahan ini tidak berpengaruh negatif.

---

## [2026-10-12] v1.2 — Migrasi Tabel Pendukung Formulir Rekam Medis (update_bmt)

### Tabel Baru (36 Tabel Pendukung):
- **Anestesi & Bedah:** `ans1_tindakan_pembiusan`, `ans2_persetujuan_pembiusan`, `ans3_assesmen_prasedasi_anestesi`, `ans4_rencana_anestesi`, `ans4_evaluasi_premedikasi_detail`, `ans6_status_post_anestesi`, `ans6_protokol_nyeri_detail`, `ans7_serah_terima`, `bm1_informasi_tindakan`, `bm2_persetujuan_tindakan`, `bm4_laporan_pembedahan`, `bmi1_persetujuan_anestesi_lokal`, `bmi4_pra_anestesi_lokal`, `bmi5_bedah_minor`.
- **Rawat Inap & Lampiran:** `asesmen_medis_umum_irna`, `ri01b_hak_kewajiban_pasien`, `ri02_ringkasan`, `ri03_serah_terima`, `ri04_transfer_internal`, `ri09_status_fungsional`, `ri11b_pemberian_makan`, `ri14_rekonsiliasi_obat`, `ri15_instruksi_medis`, `ri15a_instruksi_medis_farmakologi`, `ri15b_jadwal_obat`, `ri18_edukasi_pasien`, `ri19_discharge_planning`, `ri20_resume_pulang`, `surat_pulang_aps`.
- **Rawat Jalan & UGD:** `asesmen_keperawatan_gigi_ugd`, `rj02a_hak_kewajiban`, `rj07_asesmen_ulang_nyeri`, `rj10_edukasi_terintegrasi`, `rj11_pemberian_informasi`.
- **Edukasi:** `edukasi_pasien_terintegrasi`, `edukasi_pasien_terintegrasi_detail`.

**Cara Migrasi ke Server / Production:**
1. Menggunakan file SQL: Import `migration_tabel_pendukung_bmt.sql` via phpMyAdmin atau terminal MySQL:
   ```bash
   mysql -u root -p mlitersgm < migration_tabel_pendukung_bmt.sql
   ```
2. Menggunakan Web Runner: Buka URL `http://<server-host>/mlite-rsgm/migrate_bmt.php` di browser.
3. Menggunakan CLI: Jalankan perintah:
   ```bash
   php migrate_bmt.php
   ```
4. Atau re-install plugin `update_bmt` melalui menu mLITE: **Pengaturan -> Modul**.

---

## [2026-10-12] v1.3 — Modul Lampiran Cairan Infus (Rawat Inap)

### Tabel Baru: `lampiran_cairan_infus`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir pemantauan pemberian cairan infus dan transfusi darah pasien rawat inap (RM.RI Lampiran Cairan Infus) lengkap dengan rincian botol, jenis cairan, tetesan, jam mulai, tgl/jam stop/habis, keterangan, nama petugas, serta output cetak dan PDF.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - CAIRAN INFUS" di sub-menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyCairaninfusmanage()`, `getCairaninfusform()`, `postCairaninfussave()`, `getCairaninfushapus()`, `getCairaninfuscetak()`
- `plugins/update_bmt/view/admin/lampiran_cairan_infus/manage.html` — Tampilan manajemen data
- `plugins/update_bmt/view/admin/lampiran_cairan_infus/form.html` — Formulir input & dinamis grid cairan
- `plugins/update_bmt/view/admin/lampiran_cairan_infus/cetak.html` — Layout cetak & PDF sesuai format rumah sakit
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Pengecekan tabel migrasi otomatis

---

## [2026-10-12] v1.4 — Modul Lampiran - Form A Manajer Pelayanan Pasien (MPP Ranap)

### Tabel Baru: `lampiran_form_a_mpp`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir FORM A MANAJER PELAYANAN PASIEN (MPP) untuk pasien rawat inap yang mencakup:
1. Skrining / Identifikasi Pasien (11 poin kriteria skrining MPP).
2. Asesmen MPP (Fisik Fungsional, Kognitif, Pemahaman Kesehatan, Dukungan Keluarga, Kepatuhan, Rujukan Komunitas, Asuransi/Finansial, dll).
3. Identifikasi Masalah Pasien (Biaya, Dukungan Keluarga, Pemulangan/Rujukan, Pemahaman, Ketidakpatuhan).
4. Perencanaan MPP (Edukasi PPA, Koordinasi BPJS, DPJP INA-CBGs, Rencana Tindakan Tambahan).
5. Output gabungan 2 halaman cetak & PDF dalam satu berkas terpadu (RM.RI Lampiran Form A MPP).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORM A MANAJER PELAYANAN PASIEN" di menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyFormamppmanage()`, `getFormamppform()`, `postFormamppsave()`, `getFormampphapus()`, `getFormamppcetak()`
- `plugins/update_bmt/view/admin/lampiran_form_a_mpp/manage.html` — Tampilan manajemen data
- `plugins/update_bmt/view/admin/lampiran_form_a_mpp/form.html` — Formulir input asesmen dan perencanaan MPP
- `plugins/update_bmt/view/admin/lampiran_form_a_mpp/cetak.html` — Layout cetak & PDF 2 halaman dalam 1 dokumen terpadu
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Pengecekan tabel migrasi otomatis

---

## [2026-10-12] v1.5 — Modul Lampiran - Form Asesmen Ulang Status Fungsional (Barthel Index Ranap)

### Tabel Baru: `lampiran_asesmen_ulang_status_fungsional`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir FORM ASESMEN ULANG STATUS FUNGSIONAL (BARTHEL INDEX) untuk pasien rawat inap yang mencakup:
1. 10 Item Penilaian Barthel Index (Makan, Mandi, Perawatan Diri, Berpakaian, Buang Air Kecil, Buang Air Besar, Penggunaan Toilet, Transfer, Mobilitas, Naik Turun Tangga).
2. Perhitungan otomatis Total Skor (0 - 20) dan Kategori Tingkat Ketergantungan (Mandiri, Ketergantungan Ringan, Sedang, Berat, Total).
3. Pengisian Ruangan, Tanggal, Jam, dan Petugas Pemeriksa.
4. Output cetak dan PDF sesuai standar rumah sakit (Kop RSGM Gusti Hasan Aman, No RM 6 kotak, footer RSGM/Ed-Rev/02/2023 dan RM.RI Lampiran).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORM ASESMEN ULANG STATUS FUNGSIONAL" di menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyStatusfungsionalulangmanage()`, `getStatusfungsionalulangform()`, `postStatusfungsionalulangsave()`, `getStatusfungsionalulanghapus()`, `getStatusfungsionalulangcetak()`
- `plugins/update_bmt/view/admin/lampiran_asesmen_ulang_status_fungsional/manage.html` — Tampilan manajemen data
- `plugins/update_bmt/view/admin/lampiran_asesmen_ulang_status_fungsional/form.html` — Formulir input & auto-hitung skor Barthel Index
- `plugins/update_bmt/view/admin/lampiran_asesmen_ulang_status_fungsional/cetak.html` — Layout cetak & PDF sesuai format rumah sakit
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Pengecekan tabel migrasi otomatis

---

## [2026-10-13] v1.6 — Modul Lampiran - Form B Catatan Implementasi Manajer Pelayanan Pasien (MPP Ranap)

### Tabel Baru: `lampiran_form_b_mpp`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir FORMULIR B CATATAN IMPLEMENTASI MANAJER PELAYANAN PASIEN (MPP) untuk pasien rawat inap yang mencakup:
1. Poin I: Pelaksanaan Rencana Pelayanan Pasien (Tgl/Jam & Catatan).
2. Poin II: Monitoring Pelayanan (Tgl/Jam & Catatan).
3. Poin III: Fasilitasi Pelayanan (Tgl/Jam & Catatan).
4. Poin IV: Advokasi Pelayanan Pasien (Tgl/Jam & Catatan).
5. Poin V: Hasil Pelayanan (Tgl/Jam & Catatan).
6. Poin VI: Terminasi Pelayanan Pasien, Catatan Kepuasan Pasien/Keluarga Dengan MPP (Tgl/Jam & Catatan).
7. Tanda Tangan Manajer Pelayanan Pasien (Kota, Tanggal, Nama Jelas, NIP/NIK).
8. Output cetak dan PDF sesuai standar rumah sakit (Kop RSGM Gusti Hasan Aman, No RM 6 kotak, footer RSGM/Ed-Rev/02/2023 dan RM.RI Lampiran) lengkap dengan preview toolbar.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORM B - CATATAN IMPLEMENTASI MANAJER PELAYANAN PASIEN MPP" di menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyFormbmppmanage()`, `getFormbmppform()`, `postFormbmppsave()`, `getFormbmpphapus()`, `getFormbmppcetak()`
- `plugins/update_bmt/view/admin/lampiran_form_b_mpp/manage.html` — Tampilan manajemen data
- `plugins/update_bmt/view/admin/lampiran_form_b_mpp/form.html` — Formulir input catatan implementasi MPP
- `plugins/update_bmt/view/admin/lampiran_form_b_mpp/cetak.html` — Layout cetak & PDF sesuai format rumah sakit
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Pengecekan tabel migrasi otomatis

---

## [2026-10-13] v1.7 — Modul Lampiran - Formulir Identifikasi Kebutuhan Privasi Pasien (Ranap)

### Tabel Baru: `lampiran_kebutuhan_privasi_pasien`

**SQL untuk production:**
```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Alasan:**
Menambahkan formulir FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN untuk rawat inap yang mencakup:
1. Identitas pasien (Nama, No RM, Tanggal Lahir, Alamat, Jenis Kelamin, Diagnosis Medis, Keluarga yang bisa dihubungi).
2. Kebutuhan privasi pasien saat wawancara klinis, pemeriksaan, prosedur/tindakan, pengobatan dan transportasi:
   - Privasi terhadap lawan jenis (Ya/Tidak + keterangan/catatan).
   - Privasi terhadap orang lain (Ya/Tidak + keterangan/catatan).
   - Privasi terhadap bagian tubuh tertentu (Ya/Tidak + keterangan/catatan).
   - Privasi terhadap informasi penyakitnya (Ya/Tidak + keterangan/catatan).
3. Tanda tangan Petugas RS dan Pasien/Keluarga (Kota, Tanggal, Nama Jelas, NIP/NIK).
4. Catatan kaki *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"*.
5. Output cetak dan PDF presisi 1 lembar sesuai standar dokumen rumah sakit (Kop RSGM Gusti Hasan Aman, footer RSGM/Ed-Rev/02/2023 dan RM.RI Lampiran) dilengkapi toolbar aksi cetak dan download.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN" di menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyKebutuhanprivasimanage()`, `getKebutuhanprivasiform()`, `postKebutuhanprivasisave()`, `getKebutuhanprivasihapus()`, `getKebutuhanprivasicetak()`
- `plugins/update_bmt/view/admin/lampiran_kebutuhan_privasi_pasien/manage.html` — Tampilan tabel kelola data
- `plugins/update_bmt/view/admin/lampiran_kebutuhan_privasi_pasien/form.html` — Formulir input kebutuhan privasi pasien
- `plugins/update_bmt/view/admin/lampiran_kebutuhan_privasi_pasien/cetak.html` — Template cetak & PDF sesuai contoh dokumen RS
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Validasi migrasi tabel otomatis
- `CHECKLIST_FITUR.md` — Pembaruan checklist status fitur

---

## [2026-10-13] v1.8 — Modul Lampiran - Formulir Meninggalkan Rumah Sakit Selama Periode Tertentu (Ranap)

### Tabel Baru: `lampiran_meninggalkan_rs_tertentu`

**SQL untuk production:**
```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Alasan:**
Menambahkan formulir FORMULIR MENINGGALKAN RUMAH SAKIT SELAMA PERIODE WAKTU TERTENTU untuk rawat inap yang mencakup:
1. Identitas pembuat pernyataan (Nama, Jenis Kelamin, No Kartu Identitas/KTP, Alamat, Selaku Diri Sendiri/Keluarga/Kerabat).
2. Identitas pasien terkait (Nama, Jenis Kelamin, Tempat/Tgl Lahir, No Rekam Medis).
3. Pernyataan izin meninggalkan ruang perawatan rumah sakit sementara waktu (Ruang Perawatan, Alasan/Keperluan, Periode Tanggal Mulai s/d Selesai).
4. Pelepasan tanggung jawab risiko keselamatan terhadap rumah sakit.
5. Tanda tangan 3 pihak: Saksi Rumah Sakit, Saksi Pasien, dan Pembuat Pernyataan.
6. Output cetak dan PDF presisi 1 lembar sesuai standar format rumah sakit lengkap dengan Kop RSGM Gusti Hasan Aman dan preview toolbar.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORMULIR MENINGGALKAN RUMAH SAKIT SELAMA PERIODE TERTENTU" di menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyMeninggalkanrsmanage()`, `getMeninggalkanrsform()`, `postMeninggalkanrssave()`, `getMeninggalkanrshapus()`, `getMeninggalkanrscetak()`
- `plugins/update_bmt/view/admin/lampiran_meninggalkan_rs_tertentu/manage.html` — Tampilan manajemen data
- `plugins/update_bmt/view/admin/lampiran_meninggalkan_rs_tertentu/form.html` — Formulir input izin meninggalkan RS
- `plugins/update_bmt/view/admin/lampiran_meninggalkan_rs_tertentu/cetak.html` — Layout cetak & PDF sesuai contoh dokumen RS
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Validasi migrasi tabel otomatis (42 tabel aktif)
- `CHECKLIST_FITUR.md` — Pembaruan checklist status fitur (69.4% selesai)

---

## [2026-10-13] v1.9 — Modul Lampiran - Formulir Penghentian Pengobatan (Ranap)

### Tabel Baru: `lampiran_penghentian_pengobatan`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir FORMULIR PENGHENTIAN PENGOBATAN untuk rawat inap yang mencakup:
1. Identitas pembuat pernyataan (Nama, Umur dalam tahun, Alamat Rumah, No. Telepon / Hp, Bukti Diri KTP/SIM/Pasport, Hubungan dengan Pasien).
2. Klausul pernyataan penghentian pengobatan terhadap diri sendiri/istri/suami/anak/orang tua pasien.
3. Identitas pasien (Nama, Umur, Alamat Rumah, Bukti Diri KTP/SIM/Pasport, Perawatan / Kelas / Kamar).
4. Penjelasan medis dan pelepasan tanggung jawab risiko medis atas kesadaran penuh tanpa paksaan.
5. Tanggal & jam tanda tangan, serta tanda tangan dua kolom: Petugas RS dan Pembuat Pernyataan.
6. Catatan kaki & kode formulir standar RSGM (`RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran`).
7. Output cetak dan PDF presisi 1 lembar A4 sesuai contoh fisik formulir RSGM.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORMULIR PENGHENTIAN PENGOBATAN" di sub-menu Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyPenghentianpengobatanmanage()`, `getPenghentianpengobatanform()`, `postPenghentianpengobatansave()`, `getPenghentianpengobatanhapus()`, `getPenghentianpengobatancetak()`
- `plugins/update_bmt/view/admin/lampiran_penghentian_pengobatan/manage.html` — Tampilan manajemen data (DataTables & tombol cetak/pdf/edit/hapus)
- `plugins/update_bmt/view/admin/lampiran_penghentian_pengobatan/form.html` — Formulir input & pencarian AJAX otomatis pasien & kamar ranap
- `plugins/update_bmt/view/admin/lampiran_penghentian_pengobatan/cetak.html` — Layout cetak & PDF single-page A4 sesuai dokumen referensi
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Validasi migrasi tabel otomatis (43 tabel aktif)
- `CHECKLIST_FITUR.md` — Pembaruan checklist status fitur (70.4% selesai, 76/108 form)

---

## [2026-10-13] v1.10 — Modul Lampiran - Formulir Profil Ringkas Medis Rawat Jalan / PRMRJ (Rajal & Ranap)

### Tabel Baru: `lampiran_profil_ringkas_medis_rajal`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ) yang mencakup 2 halaman lengkap sesuai standar akreditasi dan berkas rekam medis RSGM:
1. **Halaman 1:**
   - Kop resmi RSGM Gusti Hasan Aman & kotak No. RM 6-digit `[ ][ ][ ][ ][ ][ ]`.
   - Data demografi pasien (Nama, Tempat/Tanggal Lahir, No. Rekam Medis, Jenis Kelamin).
   - Tabel Diagnosa, DPJP (Nama, Tgl Mulai, Tgl Akhir, TTD), DPJP Utama (Nama, Tgl Mulai, Tgl Akhir, TTD), dan Keterangan.
   - Tabel Riwayat Alergi (Tanggal & Nama Alergi Obat/Makanan/Zat lain).
2. **Halaman 2:**
   - Header ringkas pasien & kotak No. RM.
   - Tabel Diagnosa Rawat Inap (Jika Ada) dengan tanggal.
   - Tabel Operasi / Tindakan dengan tanggal.
   - Tabel Terapi Obat-obatan (Tanggal Mulai, Nama Obat, Dosis, Frekuensi, Tanggal Selesai).
   - Catatan kaki & kode formulir RSGM (`RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran Hal 2-2`).
3. Output cetak dan PDF presisi 2 halaman A4 dengan action toolbar.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Tambah menu "LAMPIRAN - FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ)" di sub-menu Rajal dan Ranap
- `plugins/update_bmt/Admin.php` — Tambah handler `anyPrmrjmanage()`, `getPrmrjform()`, `postPrmrjsave()`, `getPrmrjhapus()`, `getPrmrjcetak()`
- `plugins/update_bmt/view/admin/lampiran_prmrj/manage.html` — Tampilan manajemen data (DataTables, badge ringkasan & aksi)
- `plugins/update_bmt/view/admin/lampiran_prmrj/form.html` — Formulir dinamis 5 tabel multi-baris & auto-search AJAX pasien
- `plugins/update_bmt/view/admin/lampiran_prmrj/cetak.html` — Output cetak & PDF 2 halaman A4 presisi sesuai dokumen acuan
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — Skrip DDL tabel
- `migrate_bmt.php` — Validasi migrasi tabel otomatis (44 tabel aktif)
- `CHECKLIST_FITUR.md` — Pembaruan checklist status fitur (72.2% selesai, 78/108 form)

---

## [2026-10-13] v1.11 — Modul Lampiran - Formulir Skrining Dari Luar Rumah Sakit (Ranap)

### Tabel Baru: `lampiran_skrining_luar_rs`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir resmi akreditasi RSGM: **FORMULIR SKRINING DARI LUAR RUMAH SAKIT** (Kategori Lampiran Rawat Inap):
1. **Header & Kop:** Kop resmi RSGM Gusti Hasan Aman dan kotak judul dokumen.
2. **Bagian I (Identitas Pasien & Rujukan):** No. RM kotak 6 digit `[ ][ ][ ][ ][ ][ ]`, Nama Pasien (L/P), Tanggal Lahir & Umur, Status Pembayaran, Asal Rujukan, Alasan di rujuk.
3. **Bagian II (Diagnosa):** Diagnosa kerja / diagnosa rujukan.
4. **Bagian III (Keadaan Pasien):** Keadaan umum, Kesadaran, Tanda-tanda vital (Tensi, Nadi, Suhu, Pernapasan).
5. **Bagian IV (Penatalaksanaan):** Terapi medikamentosa dan Tindakan.
6. **Bagian V (Tujuan):** Checklist tujuan spesialis (UGD, Orthodonsia, Pedodonsia, Bedah Mulut, Oral Medicine, Periodonsia, Prosthodonsia, Konservasi Gigi, Lainnya).
7. **Bagian VI (Keterangan & Petugas Jaga):** Status keputusan (TERIMA / TOLAK) dengan alasan, serta tanda tangan & identitas petugas jaga.
8. **Catatan Kaki:** Catatan resmi dan kode formulir RSGM (`RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran`).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form Lampiran -> Ranap -> "LAMPIRAN - FORMULIR SKRINING DARI LUAR RUMAH SAKIT"
- `plugins/update_bmt/Admin.php` — Handler `anySkriningluarrsmanage()`, `getSkriningluarrsform()`, `postSkriningluarrssave()`, `getSkriningluarrshapus()`, `getSkriningluarrscetak()`
- `plugins/update_bmt/view/admin/lampiran_skrining_luar_rs/manage.html` — Tampilan manajemen data (DataTables, badge status & tombol aksi)
- `plugins/update_bmt/view/admin/lampiran_skrining_luar_rs/form.html` — Formulir input interaktif dengan auto-search AJAX pasien & checklist poli tujuan
- `plugins/update_bmt/view/admin/lampiran_skrining_luar_rs/cetak.html` — Output cetak & PDF 1 halaman A4 presisi sesuai dokumen acuan
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database
- `migrate_bmt.php` — Validasi migrasi tabel otomatis (45 tabel aktif)
- `CHECKLIST_FITUR.md` — Pembaruan checklist status fitur (73.1% selesai, 79/108 form)

---

## [2026-10-13] v1.12 — Modul Lampiran - Formulir Stabilisasi Pasien Sebelum Transfer (Ranap)

### Tabel Baru: `lampiran_stabilisasi_pasien`

**SQL untuk production:**
```sql
CREATE TABLE IF NOT EXISTS `lampiran_stabilisasi_pasien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `tanggal_stabilisasi` date DEFAULT NULL,
  `jam_stabilisasi` time DEFAULT NULL,
  `nama_perawat` varchar(150) DEFAULT NULL,
  `nip_perawat` varchar(50) DEFAULT NULL,
  `nama_dokter` varchar(150) DEFAULT NULL,
  `nip_dokter` varchar(50) DEFAULT NULL,
  `diagnosa` text DEFAULT NULL,
  `keadaan_umum` enum('Ringan','Sedang','Berat') DEFAULT 'Sedang',
  `gcs_e` varchar(10) DEFAULT NULL,
  `gcs_v` varchar(10) DEFAULT NULL,
  `gcs_m` varchar(10) DEFAULT NULL,
  `kesadaran_kualitatif` enum('Compos Mentis','Apatis','Delirium','Somnolen','Stupor','Koma') DEFAULT 'Compos Mentis',
  `pernafasan` enum('Spontan','Dengan bantuan O2','ETT','Tracheostomi','Ambu bag') DEFAULT 'Spontan',
  `pernafasan_o2` varchar(50) DEFAULT NULL,
  `alat_iv_line` tinyint(1) DEFAULT 0,
  `alat_iv_line_jalur` varchar(100) DEFAULT NULL,
  `alat_ngt` tinyint(1) DEFAULT 0,
  `alat_drain` tinyint(1) DEFAULT 0,
  `alat_kateter` tinyint(1) DEFAULT 0,
  `alat_lain_lain` varchar(255) DEFAULT NULL,
  `monitoring_tanda_vital` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal_stabilisasi` (`tanggal_stabilisasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Alasan:**
Menambahkan formulir rekam medis akreditasi RSGM: **FORMULIR STABILISASI PASIEN SEBELUM TRANSFER** (Kategori Lampiran Rawat Inap):
1. **Header & Kop Surat:** Kop resmi RSGM Gusti Hasan Aman dan bingkai formulir presisi.
2. **Identitas Pasien & Tim Medis:** Nama Pasien, Umur & Jenis Kelamin, Diagnosa, Nama Perawat, Nama Dokter.
3. **Kondisi Klinis Pasien:**
   - Keadaan umum: Ringan / Sedang / Berat
   - Kesadaran Kuantitatif: GCS E... V... M...
   - Kesadaran Kualitatif: Compos Mentis, Apatis, Delirium, Somnolen, Stupor, Koma
   - Pernafasan: Spontan, Dengan bantuan O2 (Liter/menit), ETT, Tracheostomi, Ambu bag
   - Pasien menggunakan: IV Line (... jalur), Kateter, NGT, Drain, Lain-lain
4. **Monitoring Tanda-Tanda Vital:**
   - Tabel observasi multi-baris dinamis (Jam, TD / Mm Hg, HR / Kali/mnt, RR / Kali/mnt, T / °C, Keterangan: tindakan atau pemberian obat).
5. **Catatan & Footer Resmi Dokumen:**
   - Catatan kerja sama pengisian form, kode dokumen `RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran`.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - FORMULIR STABILISASI PASIEN SEBELUM TRANSFER"
- `plugins/update_bmt/Admin.php` — Handler `anyStabilisasipasienmanage()`, `getStabilisasipasienform()`, `postStabilisasipasiensave()`, `getStabilisasipasienhapus()`, `getStabilisasipasiencetak()`
- `plugins/update_bmt/view/admin/lampiran_stabilisasi_pasien/manage.html` — Tampilan manajemen data (DataTables, badge observasi, tombol aksi)
- `plugins/update_bmt/view/admin/lampiran_stabilisasi_pasien/form.html` — Formulir input interaktif dengan auto-search AJAX pasien, GCS, alat medis, dan tabel dinamis TTV
- `plugins/update_bmt/view/admin/lampiran_stabilisasi_pasien/cetak.html` — Cetak & PDF 1 halaman A4 presisi sesuai dokumen acuan fisik
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database
- `migrate_bmt.php` — Validasi migrasi tabel otomatis (46 tabel aktif)
- `CHECKLIST_FITUR.md` & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (74.1% selesai, 80/108 form)

---

## [2026-10-13] v1.13 — Modul Lampiran - Intervensi dan Pengkajian Ulang Nyeri (Ranap)

### Tabel Baru: `lampiran_intervensi_pengkajian_nyeri`

**SQL untuk production:**
```sql
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
```

**Alasan:**
Menambahkan formulir rekam medis akreditasi RSGM: **LAMPIRAN - INTERVENSI DAN PENGKAJIAN ULANG NYERI** (Kategori Lampiran Rawat Inap):
1. **Header & Kop Surat:** Kop resmi RSGM Gusti Hasan Aman dan identitas pasien (No RM 6 kotak, Nama Pasien L/P, Tanggal Lahir & Umur).
2. **Petunjuk Pengisian Resmi:** Huruf cetak/tulisan jelas, baik dan lengkap, serta pengisian sesuai keterangan waktu yang ditentukan.
3. **Instalasi / Unit / Ruangan:** Lokasi perawatan pasien (Ruang Rawat Inap / Kamar / Bed).
4. **Pedoman Klinis (3 Kolom):**
   - **Asesmen Nyeri:** Skala Numerik (NRS 0-10), Kategori (*Tidak Nyeri, Nyeri Ringan, Nyeri Sedang, Nyeri Berat*), visual Wong-Baker FACES, dan catatan skala FLACC untuk anak <6 tahun.
   - **Intervensi Non Farmakologi:** 1. Dingin, 2. Panas, 3. Posisi, 4. Pijat, 5. Musik, 6. TENS, 7. Relaksasi dan Pernapasan.
   - **Waktu Pengkajian Ulang:** Sebelum pasien pulang rajal, 15 menit setelah injeksi, 1 jam setelah oral, 1x/shift (skor 1-3), setiap 3 jam (skor 4-6), setiap 1 jam (skor 7-10), dihentikan jika skor 0.
5. **Lembar Observasi Intervensi dan Pengkajian Ulang Nyeri:**
   - Multi-baris dinamis (*Tgl/Jam, Skor Nyeri, TD, Nadi, Napas, Suhu, Intervensi Farmakologi [Nama Obat, Dosis & Frekuensi, Rute], Intervensi Non Farmakologi, Perawat/Perawat Gigi [Nama, Paraf], Waktu Kaji Ulang*).
6. **Catatan Kaki & Footer Dokumen Resmi:**
   - Catatan kerja sama pengisian form, kode dokumen `RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran`.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - INTERVENSI DAN PENGKAJIAN ULANG NYERI"
- `plugins/update_bmt/Admin.php` — Handler `anyIntervensinyerimanage()`, `getIntervensinyeriform()`, `postIntervensinyerisave()`, `getIntervensinyerihapus()`, `getIntervensinyericetak()`
- `plugins/update_bmt/view/admin/lampiran_intervensi_pengkajian_nyeri/manage.html` — Tampilan kelola data (DataTables, badge catatan, tombol aksi)
- `plugins/update_bmt/view/admin/lampiran_intervensi_pengkajian_nyeri/form.html` — Formulir interaktif dengan AJAX cari pasien, visual skala nyeri & dynamic rows
- `plugins/update_bmt/view/admin/lampiran_intervensi_pengkajian_nyeri/cetak.html` — Output cetak dan PDF presisi format dokumen fisik
- `CHECKLIST_FITUR.md` & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (75.0% selesai, 81/108 form)

---

## [2026-10-18] v1.14 — Modul Lampiran - Penilaian Kejadian Phlebitis (Visual Infusion Phlebitis / VIP Ranap)

### Tabel Baru: `lampiran_penilaian_kejadian_phlebitis`

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur:**
Menambahkan formulir rekam medis akreditasi RSGM: **LAMPIRAN - PENILAIAN KEJADIAN PHLEIBITIS (VISUAL INFUSION PHLEIBITIS / VIP)** (Kategori Lampiran Rawat Inap):
1. **Header & Kop Resmi RSGM:** Kop surat RSGM Gusti Hasan Aman dan kotak identitas pasien (No RM 6 kotak berdigit, Nama Pasien L/P, Tanggal Lahir & Umur).
2. **Sub-header Petunjuk:** Keterangan penanggung jawab "Diisi Oleh Perawat".
3. **Tabel Observasi Harian Presisi 9 Hari (VIP Grid):**
   - 9 kolom hari perawatan (`1` s/d `9`) dilengkapi tanggal observasi harian.
   - 6 parameter klinis Visual Infusion Phlebitis (VIP Jackson):
     - Parameter 1: IV Line nampak sehat (Skor 0).
     - Parameter 2: Salah satu tanda jelas: Sedikit nyeri / sedikit kemerahan dekat IV Line (Skor 1).
     - Parameter 3: Dua dari tanda jelas: Nyeri pada IV Line, kemerahan, pembengkakan (Skor 2).
     - Parameter 4: Semua tanda jelas: Nyeri sepanjang kanul, kemerahan, pembengkakan (Skor 3).
     - Parameter 5: Semua tanda jelas: Nyeri, kemerahan, bengkak, vena teraba keras (Skor 4/5).
     - Parameter 6: Semua tanda jelas: Nyeri, kemerahan, bengkak, vena keras, periksia/demam (Skor 5/6).
   - Tanda centang (`✓`) tercetak otomatis pada parameter yang teridentifikasi setiap hari pengamatan.
   - Baris **TOTAL SKOR** otomatis terakumulasi per hari perawatan.
   - Baris **Nama & Paraf yang Melakukan Penilaian** per hari perawatan.
4. **Pedoman Keterangan Skala & Tindakan Intervensi Klinis:**
   - Skala Phlebitis: 0 (Tidak ada tanda), 1-2 (Tahap awal), 3-4 (Awal trombophlebitis), 5 (Stadium lanjut).
   - Tindakan Intervensi: 1. Observasi kanul, 2. Resite kanul, 3. Resite kanul dan pertimbangkan perawatan, 4. Memulai perawatan.
5. **Catatan Kaki Resmi & Kode Dokumen:**
   - Catatan kerja sama warna merah: *"Terima kasih atas kerja sama anda telah mengisi formulir ini dengan benar dan jelas"*.
   - Kode formulir akreditasi: `RSGM/Ed-Rev/02/2023` & `RM.RI Lampiran`.
6. **Form Interaktif Modern:**
   - AJAX pencarian pasien instan by No Rawat / No RM.
   - Tab interaktif Hari Ke-1 s/d 9 dengan auto-kalkulasi skor, live badge derajat phlebitis, rekomendasi tindakan perawat.
   - Tombol auto-fill tanggal berurutan dan copy nama petugas ke seluruh hari.
7. **Output Cetak & PDF:**
   - Tampilan cetak presisi 1 lembar kertas A4 potrait identik dengan template fisik rumah sakit.
   - Dilengkapi toolbar preview layar (Cetak & Download PDF via mPDF).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - PENILAIAN KEJADIAN PHLEIBITIS"
- `plugins/update_bmt/Admin.php` — Handler `anyPenilaianphlebitismanage()`, `getPenilaianphlebitisform()`, `postPenilaianphlebitissave()`, `getPenilaianphlebitishapus()`, `getPenilaianphlebitiscetak()`, dan navigasi
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_phlebitis/manage.html` — Tampilan manajemen data (DataTables, badge pemantauan, skor & status VIP, tombol aksi)
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_phlebitis/form.html` — Formulir interaktif pemantauan 9 hari, AJAX cari pasien, auto-skor, dan panduan klinis
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_phlebitis/cetak.html` — Layout cetak dan PDF presisi format dokumen fisik RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-48)
- `migrate_bmt.php` — Skrip validasi migrasi tabel otomatis (48 dari 48 tabel aktif)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (76.9% selesai, 83/108 form)

---

## [2026-10-18] v1.15 — Modul Lampiran - Permintaan Pelayanan Kerohanian (Ranap)

### Tabel Baru: `lampiran_permintaan_pelayanan_kerohanian`

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur:**
Menambahkan formulir rekam medis akreditasi RSGM: **LAMPIRAN - PERMINTAAN PELAYANAN KEROHANIAN** (Kategori Lampiran Rawat Inap):
1. **Header & Kop Resmi RSGM:** Kop surat RSGM Gusti Hasan Aman dan kotak identitas pasien (No RM 6 kotak berdigit, Nama Pasien L/P, Tanggal Lahir).
2. **Baris Petunjuk Resmi:** Teks merah miring: `* Harap pengisian dilakukan dengan tulisan/cetak yang jelas, benar dan lengkap`.
3. **Judul Formulir Resmi:** Teks tebal bergaris bawah `PERMINTAAN PELAYANAN KEROHANIAN`.
4. **Identitas Pemohon (Yang Bertandatangan):**
   - Nama pemohon.
   - Tanggal lahir & umur pemohon.
   - Alamat lengkap pemohon.
   - Hubungan dengan pasien (Diri Sendiri, Suami, Istri, Ayah Kandung, Ibu Kandung, Anak, dll).
5. **Pernyataan Pelayanan Kerohanian:**
   - Pernyataan resmi: *"Dengan ini menyatakan permintaan pendampingan pelayanan kerohanian agama/ kepercayaan [agama] Kepada RSGM Gusti Hasan Aman terhadap pasien :"*
   - Pilihan agama/kepercayaan (Islam, Kristen Protestan, Kristen Katolik, Hindu, Buddha, Khonghucu, Penghayat Kepercayaan).
6. **Identitas Pasien Terkait:**
   - Nama pasien, Tanggal lahir & umur pasien, Nomor Rekam Medis (No.RM), dan Alamat lengkap pasien dengan garis titik-titik isian.
7. **Kalimat Penutup & Tanda Tangan 2 Kolom:**
   - Kalimat penutup: *"Demikian surat permohonan permintaan pelayanan kerohanian ini saya buat, atas perhatiannya saya ucapkan terimakasih."*
   - Tempat & tanggal pengesahan: `Banjarmasin, [tanggal surat]`.
   - 2 kolom tanda tangan: **Perawat** (kiri) & **Yang membuat pernyataan** (kanan).
8. **Catatan Kaki Resmi & Kode Dokumen:**
   - Catatan kerja sama warna merah: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"*.
   - Kode formulir akreditasi: `RSGM/Ed-Rev/02/2023` (kiri bawah) & `RM.RI Lampiran` (kanan bawah).
9. **Form Interaktif Modern:**
   - AJAX pencarian pasien instan by No Rawat / No RM.
   - Tombol shortcut *"Pemohon adalah Pasien Sendiri"* untuk otomatis menyalin data pasien ke formulir pemohon.
   - Auto-kalkulasi umur dari tanggal lahir.
   - Auto-suggest petugas/perawat penanggung jawab dari master data rumah sakit.
10. **Output Cetak & PDF:**
    - Tampilan cetak presisi 1 lembar kertas A4 potrait identik 1:1 dengan template fisik dokumen rumah sakit.
    - Dilengkapi toolbar preview layar (Cetak & Download PDF via mPDF).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - PERMINTAAN PELAYANAN KEROHANIAN"
- `plugins/update_bmt/Admin.php` — Handler `anyPelayanankerohanianmanage()`, `getPelayanankerohanianform()`, `postPelayanankerohaniansave()`, `getPelayanankerohanihapus()`, `getPelayanankerohanicetak()`, dan navigasi
- `plugins/update_bmt/view/admin/lampiran_permintaan_pelayanan_kerohanian/manage.html` — Tampilan manajemen data (DataTables, badge agama/kepercayaan, detail kamar & pemohon, tombol cetak, PDF, edit, hapus)
- `plugins/update_bmt/view/admin/lampiran_permintaan_pelayanan_kerohanian/form.html` — Formulir interaktif dengan AJAX pencarian pasien, copy data pasien, auto umur, dan auto-suggest perawat
- `plugins/update_bmt/view/admin/lampiran_permintaan_pelayanan_kerohanian/cetak.html` — Layout cetak dan PDF presisi format dokumen fisik RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-49)
- `migrate_bmt.php` — Skrip validasi migrasi tabel otomatis (49 dari 49 tabel aktif)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (77.8% selesai, 84/108 form)

---

## [2026-10-18] v1.16 — Modul RML - Monitoring Pasien Kateter Urine (Ranap)

### Tabel Baru: `lampiran_monitoring_pasien_kateter_urin`

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur:**
Menambahkan formulir rekam medis akreditasi RSGM: **RML - MONITORING PASIEN KATETER URINE** (`FORMULIR MONITORING PASIEN TERPASANG KATETER URIN`) (Kategori Lampiran Rawat Inap):
1. **Header & Kop Resmi RSGM:** Kop surat RSGM Gusti Hasan Aman dan kotak identitas pasien (No RM 6 kotak berdigit, Nama Pasien L/P, Tanggal Lahir).
2. **Judul Formulir Resmi:** `FORMULIR MONITORING PASIEN TERPASANG KATETER URIN`.
3. **Data Identitas Rawat & Diagnosa:**
   - Ruang perawatan, Periode Bulan / Tahun, Tanggal MRS (Masuk Rumah Sakit), dan Diagnosa klinis.
4. **Spesifikasi Kateter Terpasang:**
   - Jenis Kateter: Silikon, Folley, atau lainnya.
   - Ukuran / No. Kateter: 6, 8, 10, 12, 14, 16, 18, 20, 22, atau nomor khusus.
5. **Grid Pemantauan Bundel Pencegahan & Gejala ISK 31 Hari:**
   - **I. Item Pencegahan ISK (13 Parameter Bundel):**
     1. Pasang
     2. Lepas
     3. Cuci tangan sesuai 5 moment
     4. Pemasangan dengan teknik aseptik
     5. Fiksasi dg baik
     6. Urin bag di bwh bladder
     7. Posisi kateter baik/tdk tertekuk, terlipat
     8. Urin bag tidak menyentuh lantai
     9. Tidak bladder training dgn klem
     10. Tidak membuka sambungan antara cath dan selang urin bag
     11. Perineal hygiene dg air dan sabun 2x/hari
     12. Gelas ukur terpisah antar pasien
     13. Ada indikasi pemakaian kateter urin
     *(Penandaan: `✓` bila dilakukan, `-` bila tidak dilakukan)*
   - **II. Gejala ISK (8 Parameter Deteksi Klinis):**
     - a.1 Demam ≥38° C
     - a.2 Nyeri supra-pubic
     - a.3 Urgency/anyang2an
     - a.4 Frequency/sering kencing
     - a.5 Dysuria/nyeri saatkencing
     - a.6 Nyeri costovertebral angle
     - b. Kuman biakan urine ≥10³ atau ≥10⁵/ml
     - c. Pyuria (≥10 leukosit urin)
     *(Penandaan: `✓` bila ditemukan gejala, `0` bila tidak ditemukan / normal)*
   - **Kolom Tanggal 1 s/d 31, Kolom Total (hari), dan Kolom Keterangan (Ketr).**
6. **Formula Perhitungan Angka Kejadian ISK (PPI KARS):**
   - Rumus: *(Jumlah pasien ISK / Jumlah hari pemasangan kateter urin) x 1000*.
   - Auto-kalkulasi hari pemasangan dan status deteksi gejala.
7. **Pengesahan Petugas (2 Kolom):**
   - Dibuat oleh: **ICN (Infection Control Nurse)**.
   - Mengetahui: **Head Nurse (Kepala Ruangan)**.
8. **Form Interaktif Modern:**
   - AJAX pencarian pasien cepat (No. Rawat / No. RM).
   - Fitur Quick Actions Harian: *"Bundel Terpenuhi (✓)"*, *"Gejala ISK Normal (0)"*, dan *"Kosongkan"*.
   - Auto-serialize JSON grid 31 hari ke database.
9. **Output Cetak & PDF Presisi:**
   - Layout landscape A4 presisi 1 lembar identik 1:1 dengan form fisik rumah sakit.
   - Dilengkapi toolbar preview layar (Cetak & Download PDF via mPDF).

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "RML - MONITORING PASIEN KATETER URINE"
- `plugins/update_bmt/Admin.php` — Handler `anyMonitoringkateterurinmanage()`, `getMonitoringkateterurinform()`, `postMonitoringkateterurinsave()`, `getMonitoringkateterurinhapus()`, `getMonitoringkateterurincetak()`, dan navigasi
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_kateter_urin/manage.html` — Tampilan manajemen data (DataTables, badge spesifikasi kateter, ICN/Head Nurse, aksi)
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_kateter_urin/form.html` — Formulir interaktif 31 hari pemantauan, auto-kalkulasi, AJAX pasien, quick fill
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_kateter_urin/cetak.html` — Layout cetak & PDF landscape A4 presisi sesuai dokumen fisik RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-50)
- `migrate_bmt.php` — Skrip validasi migrasi tabel otomatis (50 dari 50 tabel aktif)
- `seed_kateter_urin.php` — Skrip generator data sampel pemantauan kateter urin
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (78.7% selesai, 85/108 form)

---

## [2026-10-18] v1.17 — Modul Lampiran - Formulir Konsultasi Internal (Ranap)

### Tabel Baru: `lampiran_konsultasi_internal` (Tabel ke-51 dari 51 tabel aktif `update_bmt`)

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur Implementasi:**
1. **Standar Akreditasi KARS Pelayanan Terintegrasi (PAP & MKE):**
   - Mengakomodasi formulir rekam medis resmi: **`RSGM/Ed-Rev/02/2023 - RM.RI Lampiran`**.
   - Menyediakan pencatatan konsultasi klinis internal antar dokter penanggung jawab pelayanan (DPJP) dengan dokter spesialis konsulen.
2. **Struktur Formulir 2 Bagian Terintegrasi:**
   - **Bagian 1 (Permintaan Konsultasi / Diisi DPJP):**
     - Yth. T.S. Bagian / Spesialisasi (disediakan datalist Sp.BMM, Sp.PD, Sp.An, Sp.KG, Sp.Perio, Sp.KGA, Sp.Ort, Sp.RKG, DPJP).
     - Di Instalasi / Unit (IRNA, IBS, IRJ, IGD, ICU/HCU).
     - Identitas Pasien & Ruangan / Kamar Inap.
     - Diagnosa Sementara & Konsultasi yang Diminta.
     - Pengesahan DPJP: Kota, Tanggal Permintaan, Nama & Tanda Tangan Dokter yang Merawat.
   - **Bagian 2 (Jawaban Konsultasi / Boxed JAWABAN):**
     - Hasil Pemeriksaan Konsulen (temuan klinis, anamnesis, fisik, penunjang).
     - Diagnosa Konsulen (definitif/konfirmasi).
     - Nasihat / Rekomendasi Terapi & Tindakan Bersama.
     - Pengesahan Konsulen: Kota, Tanggal Jawaban, Nama & Tanda Tangan Dokter Konsulen.
     - Catatan khusus kiri bawah: *"Form ini untuk Rekam Medis"*.
3. **Formulir Interaktif & Cerdas:**
   - AJAX pencarian pasien instan via No. Rawat / No. RM dengan auto-fill nama, JK, tanggal lahir, umur, dan kamar rawat inap.
   - Auto-toggle status konsultasi menjadi *"Sudah Dijawab"* saat kolom jawaban diisi.
   - Datalist dokter aktif untuk pemilihan dokter pengirim dan konsulen.
4. **Output Cetak & PDF Presisi 1:1 Identik:**
   - Layout Portrait A4 berbingkai tunggal (*outer border*) persis template fisik rumah sakit.
   - KOP RSGM Gusti Hasan Aman lengkap dengan kotak digit No. RM (6 kotak) & identitas pasien.
   - Baris petunjuk merah: `* Harap pengisian dilakukan dengan tulisan/cetak yang jelas, benar dan lengkap`.
   - Box JAWABAN dengan garis pembatas tebal dan footer resmi rumah sakit.
   - Cetak langsung browser dan download PDF otomatis via mPDF.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - KONSULTASI INTERNAL"
- `plugins/update_bmt/Admin.php` — Handler `anyKonsultasiinternalmanage()`, `getKonsultasiinternalform()`, `postKonsultasiinternalsave()`, `getKonsultasiinternalhapus()`, `getKonsultasiinternalcetak()`, dan registrasi di `navigation()`
- `plugins/update_bmt/view/admin/lampiran_konsultasi_internal/manage.html` — Tampilan manajemen data (DataTables, status badge Menunggu/Sudah Dijawab, DPJP, konsulen, aksi)
- `plugins/update_bmt/view/admin/lampiran_konsultasi_internal/form.html` — Form input interaktif AJAX pasien, datalist spesialisasi & instalasi, auto-status
- `plugins/update_bmt/view/admin/lampiran_konsultasi_internal/cetak.html` — Template cetak & PDF A4 portrait presisi sesuai dokumen fisik RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-51)
- `migrate_bmt.php` — Skrip validasi migrasi tabel otomatis (51 dari 51 tabel aktif)
- `seed_konsultasi_internal.php` — Skrip seeder data sampel realistis (Bedah Mulut ke Anestesi, Bedah Mulut ke Penyakit Dalam, Ortho ke Bedah Mulut)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (79.6% selesai, 86/108 form, 33/50 lampiran)

---

## [2026-10-18] v1.18 — Modul Lampiran - Monitoring Pasien Selama Transfer (Ranap)

### Tabel Baru: `lampiran_monitoring_pasien_selama_transfer` (Tabel ke-52 dari 52 tabel aktif `update_bmt`)

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur Implementasi:**
1. **Standar Akreditasi KARS Pelayanan Transfer & Rujukan Pasien (ARK & PAP):**
   - Mengakomodasi formulir rekam medis resmi: **`RSGM/Ed-Rev/02/2023 - RM.RI Lampiran`**.
   - Menyediakan pencatatan parameter observasi tanda vital, tindakan gawat darurat, dan terapi obat/cairan selama proses pemindahan/transfer pasien (antar unit, intra-hospital, IGD ke IBS, IRNA ke IBS, atau rujukan).
2. **Struktur Formulir Komprehensif Presisi 1:1:**
   - **Header Informasi Transfer:**
     - Tanggal Transfer & Ruangan/Kamar Inap Asal.
     - **Status Kegawatdaruratan / Triase**: Merah (🟥 Resusitasi/Gawat Darurat), Kuning (🟨 Urgent/Mendesak), Hijau (🟩 Stabil/Non Urgent), Hitam (⬛ Meninggal/Paliatif Terminal).
     - **Jenis Kasus**: Trauma / Non Trauma.
     - **Petugas Transfer**: Nama & NIP pendamping medis/paramedis.
     - **Waktu Transfer**: Jam Berangkat & Jam Tiba di unit penerima.
     - **Diagnosa & Monitoring**: Indikasi klinis serta tujuan pemindahan.
   - **Grid Observasi Vital (10 Kolom Standar Medis):**
     1. Jam Obs.
     2. GCS/Pupil Rx (misal: E4V5M6 / 3mm (+/+))
     3. Tensi (BP mmHg)
     4. RR (x/menit)
     5. SPO2 (%)
     6. Temp (°C)
     7. Jam Tindakan
     8. Hal-Hal Penting / Tindakan Medis Dilakukan
     9. Jam Terapi
     10. Terapi (Obat / Cairan Diberikan)
   - Dipad secara otomatis hingga 15 baris pada tampilan cetak agar sesuai proporsi lembar cetak fisik rumah sakit.
   - **Pengesahan Petugas Transfer:**
     - Kotak tanda tangan petugas transfer dengan nama terang.
     - Catatan klinis serah terima kiri bawah.
     - Catatan kaki merah italic: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"*.
     - Footer resmi: `RSGM/Ed-Rev/02/2023` (kiri) & `RM.RI Lampiran` (kanan).
3. **Antarmuka Interaktif & Cerdas:**
   - AJAX pencarian pasien instan via No. Rawat / No. RM dengan auto-fill identitas dan kamar rawat inap.
   - Dynamic rows table dengan tombol tambah baris (+ Tambah Baris Observasi) dan hapus baris.
   - Serialisasi otomatis ke JSON pada kolom `detail_monitoring`.
   - DataTables management dengan badge triase warna-warni dan tombol Aksi (Edit, Cetak HTML, Download PDF mPDF, Hapus).
4. **Output Cetak & PDF mPDF 1 Halaman A4 Presisi:**
   - Tampilan cetak berbingkai (*single outer border*) sesuai format fisik RSGM Gusti Hasan Aman.
   - KOP RSGM lengkap dengan 6 kotak digit nomor rekam medis.
   - Tersedia tombol cetak langsung browser dan tombol unduh PDF resmi.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - MONITORING PASIEN SELAMA TRANSFER"
- `plugins/update_bmt/Admin.php` — Handler `anyMonitoringpasienselamatransfermanage()`, `getMonitoringpasienselamatransferform()`, `postMonitoringpasienselamatransfersave()`, `getMonitoringpasienselamatransferhapus()`, `getMonitoringpasienselamatransfercetak()`, dan registrasi navigasi
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_selama_transfer/manage.html` — View kelola DataTables dengan nomor urut dinamis `{$value.nomor}` dan badge status kegawatdaruratan
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_selama_transfer/form.html` — Form input interaktif AJAX pasien, radio button triase & jenis kasus, dinamis 10-kolom tabel observasi
- `plugins/update_bmt/view/admin/lampiran_monitoring_pasien_selama_transfer/cetak.html` — Template cetak HTML & PDF mPDF A4 portrait presisi 1:1 fisik form RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-52)
- `migrate_bmt.php` — Validasi migrasi otomatis 52 dari 52 tabel aktif
- `seed_monitoring_transfer.php` — Skrip seeder data sampel 3 kasus realistis (Kuning - Trauma Cito ORIF, Merah - Non Trauma Angina Ludwig, Hijau - Non Trauma Pindah Paviliun VIP)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (80.6% selesai, 87/108 form, 34/50 lampiran)

---

## [2026-10-18] v1.19 — Modul Lampiran - Penilaian Dekubitus / Norton Scale (Ranap)

### Tabel Baru: `lampiran_penilaian_dekubitus` (Tabel ke-53 dari 53 tabel aktif `update_bmt`)

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur Implementasi:**
1. **Standar Akreditasi KARS Pelayanan Asuhan Pasien & Keselamatan Pasien (SKP 6 & PAP):**
   - Mengakomodasi formulir rekam medis resmi: **`RSGM/Ed-Rev/02/2023 - RM.RI Lampiran`**.
   - Menyediakan instrumen evaluasi risiko luka tekan/dekubitus menggunakan **Skala Norton (Decubitus Risk Assessment)** yang dievaluasi per hari perawatan (Hari Ke-1 s/d Ke-9).
2. **Struktur Formulir Komprehensif Presisi 1:1:**
   - **Header Identitas:**
     - KOP RSGM Gusti Hasan Aman & 6 kotak digit nomor rekam medis.
     - Nama Pasien, Jenis Kelamin (L/P), dan Tanggal Lahir bergaris titik-titik.
     - Judul: `PENILAIAN DEKUBITUS (DECUBITUS RISK ASSESSMENT)`.
     - Subheader: `Diisi Oleh Perawat:`.
   - **Tabel Skala Norton 12 Kolom:**
     - Kolom: `No` (4%), `Komponen` (34%), `Skor` (6%), dan `Skor Hari Perawatan Ke` (9 kolom hari 1..9, masing-masing 6.2%).
     - 5 Komponen Norton:
       1. **Kondisi fisik umum**: Baik (4), Lumayan (3), Buruk (2), Sangat buruk (1).
       2. **Kesadaran**: Komposmentis (4), Apatis (3), Konfus/saporis (2), Stupor/koma (1).
       3. **Aktivitas**: Ambulan (4), Ambulan dengan bantuan (3), Hanya bisa duduk (2), Tiduran (1).
       4. **Mobilitas**: Bergerak bebas (4), Sedikit terbatas (3), Sangat terbatas (2), Tidak bisa bergerak (1).
       5. **Inkontinensia**: Tidak (4), Kadang-kadang (3), Sering inkontinensia urine (2), Sering inkontinensia alvi dan urine (1).
     - Baris `TOTAL SKOR`: Total skor komponen hari 1..9 (rentang skor 5 s/d 20).
     - Baris `Nama & Paraf yang Melakukan Penilaian`: Nama perawat penilai tiap hari.
   - **Keterangan & Footer:**
     - Catatan kaki kanan merah: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"* + kotak paraf.
     - Aturan risiko kiri: `*) Resiko dekubitus bila total skor ≤ 14`.
     - Kode formulir: `RSGM/Ed-Rev/02/2023` (kiri) & `RM.RI Lampiran` (kanan).
3. **Antarmuka Interaktif & Cerdas:**
   - AJAX pencarian pasien instan via No. Rawat / No. RM dengan auto-fill identitas dan kamar rawat inap.
   - Tab navigasi interaktif Hari Ke-1 s/d Ke-9 dengan badge skor dinamis dan warna indikator risiko.
   - Tab khusus **Matrix Preview 9 Hari** yang otomatis tersinkronisasi live saat perawat mengisi atau mengubah skor.
   - DataTables management dengan badge risiko (Merah: Resiko Dekubitus ≤ 14, Hijau: Tidak Berisiko > 14), status hari terisi, dan tombol Aksi (Edit, Cetak HTML, Unduh PDF mPDF, Hapus).
4. **Output Cetak & PDF mPDF 1 Halaman A4 Presisi:**
   - Tampilan cetak berbingkai tunggal (*single outer border*) sesuai format fisik RSGM.
   - KOP RSGM lengkap dengan 6 kotak digit nomor rekam medis.
   - Tersedia tombol cetak langsung browser dan tombol unduh PDF resmi via mPDF.

**File kode yang diubah/dibuat:**
- `themes/admin/index.html` — Menu navigasi Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> "LAMPIRAN - PENILAIAN DEKUBITUS"
- `plugins/update_bmt/Admin.php` — Handler `anyPenilaiandekubitusmanage()`, `getPenilaiandekubitusform()`, `postPenilaiandekubitussave()`, `getPenilaiandekubitushapus()`, `getPenilaiandekubituscetak()`, dan registrasi navigasi
- `plugins/update_bmt/view/admin/lampiran_penilaian_dekubitus/manage.html` — View kelola DataTables dengan nomor urut dinamis `{$value.nomor}` dan badge skor & risiko
- `plugins/update_bmt/view/admin/lampiran_penilaian_dekubitus/form.html` — Form input interaktif AJAX pasien, tab evaluasi 9 hari, tab matrix live preview, perhitungan skor otomatis
- `plugins/update_bmt/view/admin/lampiran_penilaian_dekubitus/cetak.html` — Template cetak HTML & PDF mPDF A4 portrait presisi 1:1 fisik form RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-53)
- `migrate_bmt.php` — Validasi migrasi otomatis 53 dari 53 tabel aktif
- `seed_penilaian_dekubitus.php` — Skrip seeder data sampel 3 kasus klinis realistis (Pasien Bedah Fraktur Maksilofasial Tirah Baring Ketat Skor 11-14, Pasien Sepsis Odontogenik Skor 6-10, Pasien Mandiri Odontektomi Elektif Skor 17-20)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (81.5% selesai, 88/108 form, 35/50 lampiran)

---

## [2026-10-18] v1.20 — Modul Lampiran - Penilaian Kejadian Infeksi Luka Operasi (ILO) & Perbaikan Menu Dropdown Ranap Overflow

### 1. Perbaikan Menu Navigasi (Dropdown Submenu CSS Overflow)
- **Masalah:** Menu dropdown bertingkat pada `Rekam Medis -> Form RME -> Ranap -> Form Lampiran` yang berisi > 19 item formulir terpotong di tepi bawah layar monitor / laptop (*vertical viewport clipping*) sehingga item-item terbaru di bagian bawah tidak terlihat dan tidak dapat di-scroll.
- **Solusi:**
  - Ditambahkan `max-height: 80vh; overflow-y: auto; overflow-x: hidden;` serta styling *thin scrollbar* pada selector `.dropdown-submenu > .dropdown-menu` di file [themes/admin/css/style.css](file:///Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/themes/admin/css/style.css) dan [themes/admin/index.html](file:///Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/themes/admin/index.html).
  - Cache RainTPL dibersihkan (`admin/tmp/*.html`) sehingga menu langsung responsif dan dapat di-scroll dengan lancar pada seluruh resolusi layar.

### 2. Tabel Baru: `lampiran_penilaian_kejadian_ilo` (Tabel ke-54 dari 54 tabel aktif `update_bmt`)

**SQL untuk production:**
```sql
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
```

**Alasan & Fitur Implementasi:**
1. **Standar Pencegahan & Pengendalian Infeksi (PPI) dan Keselamatan Pasien (SKP):**
   - Mengakomodasi formulir rekam medis resmi: **`RSGM/Ed-Rev/02/2023 - RM.RI Lampiran`**.
   - Menyediakan pemantauan harian luka post operasi (Hari Ke-1 s/d Ke-10) oleh perawat untuk deteksi dini tanda-tanda infeksi luka operasi (ILO).
2. **Struktur Formulir Komprehensif Presisi 1:1:**
   - **Header Identitas:**
     - KOP RSGM Gusti Hasan Aman & 6 kotak digit nomor rekam medis.
     - Nama Pasien, Jenis Kelamin (L/P), dan Tanggal Lahir bergaris titik-titik.
     - Judul: `PENILAIAN KEJADIAN INFEKSI LUKA OPERASI (ILO)`.
     - Subheader: `Diisi Oleh Perawat`.
   - **Tabel Penilaian ILO 13 Kolom:**
     - Kolom: `No.` (4%), `Parameter` (44%), `Skor` (6%), dan `Skor Hari Perawatan Ke -` (10 kolom hari 1..10, masing-masing 4.6%).
     - 6 Parameter Klinis:
       1. **Proses penyembuhan normal** (Skor: 0)
       2. **Proses penyembuhan normal dengan kemerahan sedang** (a. Ditemukan beberapa kemerahan, b. Kemerahan, c. Erythema sedang) (Skor: 1)
       3. **Erythema dengan tanda inflamasi** (a. Pada satu tempat, b. Di sekitar luka jahitan, c. Sepanjang luka, d. Di sekeliling luka) (Skor: 2)
       4. **Luka bersih atau ditemukan cairan haemoserosis** (a. Hanya pada satu tempat < 2 cm, b. Di sepanjang luka > 2 cm, c. Ditemukan banyak haemoseus, d. Prolonged > 3 hari) (Skor: 3)
       5. **Pus** (a. Hanya pada suatu tempat < 2 cm, b. Di sepanjang luka > 2 cm) (Skor: 4)
       6. **Adanya infeksi yang dalam dengan atau tanpa kerusakan jaringan, hematoma requiring** (Skor: 5)
     - Baris `TOTAL SKOR`: Total skor evaluasi harian (rentang skor 0 s/d 5).
     - Baris `Nama & Paraf yang Melakukan Penilaian`: Nama perawat penilai tiap hari.
   - **Keterangan Hasil Skor & Footer:**
     - `0   = Tidak Ada Tanda Infeksi`
     - `1-2 = Tahap Awal Infeksi`
     - `3-4 = Awal Infeksi`
     - `5   = Stadium Lanjut Infeksi`
     - Catatan kaki: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"*.
     - Kode formulir: `RSGM/Ed-Rev/02/2023` (kiri) & `RM.RI Lampiran` (kanan).
3. **Antarmuka Interaktif & Cerdas:**
   - AJAX pencarian pasien instan via No. Rawat / No. RM dengan auto-fill identitas dan kamar rawat inap.
   - Tab navigasi interaktif Hari Ke-1 s/d Ke-10 dengan radio button parameter, auto skor, dan auto badge status infeksi.
   - Quick-select modal petugas / perawat.
   - Live visual 10-day matrix table matching print layout.
   - DataTables management dengan badge kategori infeksi (Hijau: Tidak Ada Tanda Infeksi, Biru: Tahap Awal Infeksi, Kuning: Awal Infeksi, Merah: Stadium Lanjut Infeksi), status hari terisi, dan tombol Aksi (Edit, Cetak HTML, Unduh PDF mPDF, Hapus).
4. **Output Cetak & PDF mPDF 1 Halaman A4 Presisi:**
   - Tampilan cetak berbingkai tunggal (*single outer border*) sesuai format fisik RSGM.
   - KOP RSGM lengkap dengan 6 kotak digit nomor rekam medis.
   - Tersedia tombol cetak langsung browser dan tombol unduh PDF resmi via mPDF.

**File kode yang diubah/dibuat:**
- `themes/admin/css/style.css` & `themes/admin/index.html` — Perbaikan dropdown-submenu overflow & penambahan menu "LAMPIRAN - PENILAIAN KEJADIAN INFEKSI LUKA OPERASI (ILO)"
- `plugins/update_bmt/Admin.php` — Handler `anyPenilaianilomanage()`, `getPenilaianiloform()`, `postPenilaianilosave()`, `getPenilaianilohapus()`, `getPenilaianilocetak()`, dan registrasi navigasi
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_ilo/manage.html` — View kelola DataTables dengan nomor urut dinamis `{$value.nomor}` dan badge skor & status infeksi
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_ilo/form.html` — Form input interaktif AJAX pasien, tab evaluasi 10 hari, live matrix preview, perhitungan skor otomatis
- `plugins/update_bmt/view/admin/lampiran_penilaian_kejadian_ilo/cetak.html` — Template cetak HTML & PDF mPDF A4 portrait presisi 1:1 fisik form RSGM
- `migration_tabel_pendukung_bmt.sql` & `plugins/update_bmt/migration.sql` — DDL tabel database pendukung (Tabel ke-54)
- `migrate_bmt.php` — Validasi migrasi otomatis 54 dari 54 tabel aktif
- `seed_penilaian_kejadian_ilo.php` — Skrip seeder data sampel 3 kasus klinis realistis (Pasien Bedah Mulut Normal Skor 0, Pasien Kista Mandibula Tahap Awal Infeksi Skor 1-2, Pasien Rekonstruksi Fraktur Maksilofasial Pus Awal Infeksi Skor 4)
- `CHECKLIST_FITUR.md`, `format.py`, & `hasil_pengecekan_form_mlite.md` — Pembaruan checklist status fitur (82.4% selesai, 89/108 form, 36/50 lampiran)

---

## [1.21.0] - 2026-10-18
### Added - LAMPIRAN - PENILAIAN KEJADIAN INFEKSI SALURAN KEMIH (ISK) (Rawat Inap)
1. **Tabel Database Baru:**
   - Dibuat tabel `lampiran_penilaian_kejadian_isk` (Tabel ke-55 pada `migration_tabel_pendukung_bmt.sql` dan Tabel ke-56 pada `plugins/update_bmt/migration.sql`) dengan kolom: `id`, `no_rawat`, `no_rkm_medis`, `kamar_inap`, `tanggal_penilaian`, `detail_penilaian` (JSON 9 hari), `catatan`, `created_at`, `updated_at`.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM (Digabung Jadi Satu):**
   - Menggabungkan seluruh kriteria dari dokumen 2 halaman menjadi 1 halaman A4 portrait utuh dan rapi tanpa terpotong di tengah baris parameter.
   - **4 Parameter Kriteria Klinis ISK:**
     1. **Parameter 1 (Skor: 0):**
        - Pasien terpasang kateter urin saat sampel urin dan minimal satu gejala (Demam > 38°C, Nyeri suprapubik/costovertebral) Dan kultur urin positif ≥ 150 CFU/ml ≤ 2 spesies mikroorganisme; ATAU kateter terlepas < 48 jam sebelum sampel urin dan ada tanda/gejala (Demam, inkontinensia urgency/frekuensi, disuria, nyeri).
     2. **Parameter 2 (Skor: 1):**
        - Pasien terpasang kateter urin saat sampel urin dan tanda/gejala Dan hasil urin ditemukan: leukosit/nitrat, pyuria (≥ 10 WBC/mm³ atau ≥ 3 WBC/HPF), atau mikroorganisme gram & kultur ≥ 10³ dan ≤ 10⁵ CFU/ml ≤ 2 spesies; ATAU kateter lepas < 48 jam dan ada gejala.
     3. **Parameter 3 (Skor: 2):**
        - Pasien ≤ 1 tahun yang lalu dengan/tanpa riwayat kateter urin mempunyai tanda/gejala (Demam > 38°C, Hipotermia < 36°C, Bradikardia, Dysuria, Letargi, Vomitting) Dan kultur urin positif ≥ 10⁵ CFU/ml ≤ 2 spesies.
     4. **Parameter 4 (Skor: 3):**
        - Pasien ≤ 1 tahun yang lalu dengan/tanpa riwayat kateter urin mempunyai tanda/gejala Dan pemeriksaan urin: leukosit/nitrat, pyuria ≥ 10 WBC/mm³, atau mikroorganisme gram & kultur ≥ 10³ dan < 10⁵ CFU/ml.
   - **Matriks 9 Hari Perawatan:**
     - Kolom Hari Ke-1 s/d Ke-9 dengan subkolom tanggal pemeriksaan.
     - Baris `TOTAL SKOR`: Perhitungan otomatis total skor harian.
     - Baris `Nama & Paraf yang Melakukan Penilaian`: Identitas perawat evaluator.
     - Kode formulir: `RSGM/Ed-Rev/02/2023` (kiri) & `RM.RI Lampiran` (kanan).
3. **Fitur Pengelolaan & Antarmuka Interaktif:**
   - Navigasi menu: `Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> LAMPIRAN - PENILAIAN KEJADIAN INFEKSI SALURAN KEMIH (ISK)`.
   - Form input interaktif AJAX pasien, tab 9 hari evaluasi, auto-score, badge interpretasi, dan visual preview matriks 9 hari dinamis.
   - DataTables management dengan nomor urut dinamis, status hari terisi, skor & interpretasi klinis terakhir, serta filter pencarian instan.
4. **Cetak & Export PDF mPDF:**
   - Output cetak browser & mPDF presisi 1:1, berbingkai tunggal, KOP RSGM lengkap dengan 6 kotak digit nomor rekam medis.
5. **Seeder Data Sampel:**
---

## [1.22.0] - 2026-10-18
### Added - LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU (Rawat Inap)
1. **Tabel Database Baru:**
   - Dibuat tabel `lampiran_surat_pernyataan_bersedia_menunggu` (Tabel ke-56 pada `migration_tabel_pendukung_bmt.sql` dan Tabel ke-57 pada `plugins/update_bmt/migration.sql`) dengan kolom: `id`, `no_rawat`, `no_rkm_medis`, `nama_pj`, `tgl_lahir_pj`, `jk_pj`, `alamat_pj`, `no_identitas_pj`, `hubungan`, `nama_pasien`, `tgl_lahir_pasien`, `jk_pasien`, `alamat_pasien`, `ruang_menunggu`, `tanggal_surat`, `kd_dokter`, `nama_dokter`, `catatan`, `created_at`, `updated_at`.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM:**
   - Header: Kop resmi RSGM Gusti Hasan Aman lengkap dengan lambang Pemprov Kalsel, identitas rumah sakit, dan logo GHA.
   - Judul Dokumen: `SURAT PERNYATAAN BERSEDIA MENUNGGU` (huruf kapital, tebal, di tengah).
   - Bagian 1: Identitas Pembuat Pernyataan / Penanggung Jawab (`Nama`, `Tanggal Lahir`, `Jenis Kelamin`, `Alamat`, `No Identitas`).
   - Klausul Kesediaan: *"Menyatakan bahwa bersedia menunggu di IGD sampai adanya ketersediaan ruangan di ruang perawatan biasa / ruang perawatan intensif"*.
   - Bagian 2: Subjek Pasien dengan garis bawah dinamis pada hubungan terpilih:
     `Terhadap diri saya sendiri / suami / istri / orang tua / anak saya dengan :`
     diikuti rincian identitas pasien (`Nama`, `Tanggal Lahir`, `Jenis Kelamin`, `Alamat`).
   - Pernyataan Penutup: *"Demikian persetujuan ini saya buat dengan penuh kesadaran dan tanpa paksaan."*
   - Tempat & Tanggal: `Banjarmasin, [Tanggal Surat]` rata kanan di atas kolom Dokter.
   - Kolom Tanda Tangan: 2 kolom sejajar (`Yang membuat pernyataan` dan `Dokter yang merawat`) dilengkapi garis titik-titik dan nama tercetak jelas.
   - Catatan Kaki: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"* disertai ikon kotak &#9634;.
   - Kode Formulir: `RSGM/Ed-Rev/02/2023` (kiri) & `RM.RI Lampiran` (kanan).
3. **Fitur Pengelolaan & Antarmuka Interaktif:**
   - Navigasi menu: `Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU`.
   - Pencarian AJAX pasien cepat via No. Rawat / No. RM dengan auto-fill data pasien dan data penanggung jawab dari registrasi.
   - Tombol instan *"Salin dari Data Pasien"* jika pasien membuat pernyataan untuk diri sendiri.
   - Dropdown dokter DPJP otomatis terintegrasi tabel `dokter`.
   - DataTables management view dengan nomor urut dinamis `{$value.nomor}`, badge hubungan keluarga, badge ruang perawatan tujuan, dan tombol Aksi (Edit, Cetak HTML, Unduh PDF mPDF, Hapus).
4. **Cetak & Export PDF mPDF:**
   - Output cetak browser & mPDF presisi 1:1, berbingkai tunggal (*single outer border*), path lokal gambar kop untuk mencegah deadlock pada built-in PHP server.
5. **Seeder Data Sampel:**
   - Dijalankan `seed_surat_pernyataan_bersedia_menunggu.php` dengan 3 kasus realistis (Anak - AHMAD SYAFIQ RIFANI dengan PJ Orang Tua, Pasien Sendiri - Jhon Doe, Pasien Istri - Jane Doe dengan PJ Suami).

---

## [1.23.0] - 2026-10-18
### Added - LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN (Rawat Inap)
1. **Tabel Database Baru:**
   - Dibuat tabel `lampiran_penundaan_kelambatan_pelayanan` (Tabel ke-57 pada `migration_tabel_pendukung_bmt.sql` dan Tabel ke-58 pada `plugins/update_bmt/migration.sql`) dengan kolom: `id`, `no_rawat`, `no_rkm_medis`, `tanggal`, `jam`, `instalasi_unit`, `kd_dokter_pengirim`, `nama_dokter_pengirim`, `jenis_pelayanan`, `status_penerima`, `nama_penerima`, `alamat_penerima`, `no_identitas_penerima`, `sebab_kerusakan_alat`, `sebab_kondisi_umum_pasien`, `sebab_penundaan_penjadwalan`, `sebab_pemadaman_listrik`, `sebab_tempat_tidur_penuh`, `sebab_faktor_penyulit`, `keterangan_faktor_penyulit`, `solusi_dijadwalkan_ulang`, `jadwal_akan_datang`, `solusi_dirujuk`, `tujuan_rujukan`, `solusi_kembali_dokter_pengirim`, `kd_dokter_dpjp`, `nama_dokter_dpjp`, `kd_pemberi_informasi`, `nama_pemberi_informasi`, `catatan`, `created_at`, `updated_at`.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM:**
   - Header Dokumen:
     - Sisi kiri: Kop resmi RSGM Gusti Hasan Aman lengkap dengan lambang Pemprov Kalsel, nama instansi, alamat, kontak, dan logo GHA.
     - Sisi kanan: Identitas Pasien terbingkai kotak dengan 6 kotak digit nomor rekam medis (`NO. RM: [ ][ ][ ][ ][ ][ ]`), Nama Pasien + Jenis Kelamin (L/P), dan Tanggal Lahir.
   - Kotak Petunjuk: `* Harap diisi / tempelkan stiker jika ada` dan `* Beri tanda (v) pada kotak yang sesuai`.
   - Banner Judul: `INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN` (huruf kapital tebal berbingkai).
   - Grid Informasi:
     - `Tanggal : [tanggal_indo]` | `Jam : [jam_wita] WITA`
     - `Instalasi / Unit : [instalasi_unit]` | `Dokter yang mengirim : [nama_dokter_pengirim]`
     - `Jenis Pelayanan : [jenis_pelayanan]`
   - Bagian 1: Pernyataan Penerima Informasi (`Pasien Sendiri`, `Keluarga`, `Penanggung Jawab Pasien`) disertai rincian Nama, Alamat, dan No. Identitas.
   - Bagian 2 (Penyebab Penundaan): Matriks 2 kolom checkbox:
     - Kolom Kiri: `Kerusakan Alat`, `Kondisi Umum Pasien`, `Penundaan Penjadwalan`, `Pemadaman Instalasi Listrik`.
     - Kolom Kanan: `Tempat Tidur Tidak Tersedia`, `Faktor Penyulit : [keterangan_faktor_penyulit]`.
   - Bagian 3 (Persetujuan Alternatif Solusi):
     - `Dijadwalkan ulang dan menjadi prioritas` & `jadwal yang akan datang : [jadwal_akan_datang]`.
     - `Dirujuk ke layanan kesehatan lain, ke [tujuan_rujukan]`.
     - `Dikembalikan kepada dokter pengirim`.
   - Lokasi & Waktu: `Banjarmasin, [tanggal_indo] Jam : [jam_wita] WITA` di sebelah kanan atas kolom tanda tangan.
   - Tanda Tangan 3 Kolom:
     - Kolom 1: `Menyetujui, Pasien / Keluarga Pasien`
     - Kolom 2: `Mengetahui DPJP`
     - Kolom 3: `Pemberi Informasi`
   - Catatan Kaki Resmi: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"* disertai simbol kotak &#9634;.
   - Kode Formulir: `RSGM/Ed-Rev/02/2023` (kiri bawah) & `RM.RI Lampiran` (kanan bawah).
3. **Fitur Pengelolaan & Antarmuka Interaktif:**
   - Navigasi menu: `Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN`.
   - Form AJAX autocomplete pencarian pasien instan via No. Rawat / No. RM.
   - Tombol instan *"Salin dari Data Pasien"* untuk pengisian otomatis penerima informasi jika pasien sendiri.
   - Dropdown dokter pengirim, dokter DPJP, dan petugas pemberi informasi.
   - DataTables management view dengan nomor urut dinamis `{$value.nomor}`, label status hubungan, badge penyebab penundaan (merah/kuning/biru), rincian solusi alternatif, dan tombol Aksi (Edit, Cetak HTML, Unduh PDF mPDF, Hapus).
4. **Cetak & Export PDF mPDF:**
   - Output cetak browser & mPDF presisi 1:1, berbingkai tunggal (*single outer border*), path lokal gambar kop untuk mencegah deadlock pada server.
5. **Seeder Data Sampel:**
   - Dijalankan `seed_penundaan_kelambatan_pelayanan.php` dengan 3 kasus realistis (Pasien Anak IBS Odontektomi ditunda karena demam akut & batuk berdahak pra-induksi, Pasien Radiologi CBCT 3D ditunda karena kerusakan sensor gantry alat, Pasien Rawat Inap Konservasi ditunda karena kamar sedang disterilisasi).

---

## [1.24.0] - 2026-10-18
### Added - LAMPIRAN - PERSETUJUAN UNTUK TESTING HIV (Rawat Inap)
1. **Tabel Database Baru:**
   - Dibuat tabel `lampiran_persetujuan_testing_hiv` (Tabel ke-58 pada `migration_tabel_pendukung_bmt.sql` dan Tabel ke-59 pada `plugins/update_bmt/migration.sql`) dengan kolom: `id`, `no_rawat`, `no_rkm_medis`, `kode_klien`, `tanggal`, `tanggal_pelaksanaan`, `nama_pasien`, `kd_konselor`, `nama_konselor`, `status_persetujuan`, `catatan`, `created_at`, `updated_at`.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM:**
   - Header Dokumen:
     - Sisi kiri: Kop resmi RSGM Gusti Hasan Aman lengkap dengan lambang Pemprov Kalsel, nama instansi, alamat, kontak, dan logo GHA.
     - Sisi kanan: Identitas Pasien dengan 6 kotak digit nomor rekam medis (`No RM : [ ][ ][ ][ ][ ][ ]`), Nama Pasien + Jenis Kelamin (L/P), dan Tanggal Lahir.
   - Judul Dokumen: `PERSETUJUAN UNTUK TESTING HIV` (huruf kapital tebal di tengah).
   - Metadata Klien VCT: `Kode Klien : [kode_klien]` dan `Tanggal : [tanggal_indo]`.
   - Klausul 1: Hak kerahasiaan & hak menarik persetujuan sebelum pemeriksaan dilangsungkan.
   - Klausul 2: Poin materi informasi & konseling pra-tes HIV:
     - a. Keberadaan dan kegunaan dari testing HIV
     - b. Tujuan dan kegunaan dari testing HIV
     - c. Apa yang dapat dan tidak dapat diberitahukan dari testing HIV
     - d. Keuntungan serta resiko dari testing HIV dan dari mengetahui hasil testing saya
     - e. Pemahaman dari positif, negatif, false negatif, false positif, dan hasil tes intermediate serta dampak dari masa jendela
     - f. Pengukuran untuk pencegahan dari pemaparan dan penularan akan HIV
   - Klausul 3: Kesepakatan sukarela menjalani tes dengan hasil rahasia bagi diri sendiri dan persetujuan menerima konseling pasca tes.
   - Klausul 4: Jaminan bahwa pelayanan kesehatan tidak dipengaruhi keputusan terhadap tes.
   - Klausul 5: Kesempatan bertanya dan telah mendapatkan jawaban yang memuaskan.
   - Klausul 6: Pemberian izin pelaksanaan tes pada tanggal tertentu (`[tanggal_pelaksanaan_indo]`).
   - Kolom Tanda Tangan: 2 kolom sejajar (`Pasien` di kiri dan `Konselor` di kanan) disertai garis titik-titik dan nama terang di dalam kurung.
   - Catatan Kaki: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"* (cetak miring kanan bawah).
   - Kode Formulir: `RSGM/Ed-Rev/02/2023` (kiri bawah) & `RM.RI Lampiran` (kanan bawah).
3. **Fitur Pengelolaan & Antarmuka Interaktif:**
   - Navigasi menu: `Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> LAMPIRAN - PERSETUJUAN UNTUK TESTING HIV`.
   - Form AJAX autocomplete pencarian pasien instan via No. Rawat / No. RM.
   - Generator otomatis nomor `Kode Klien` format `VCT/YYYY/MM/XXX`.
   - Dropdown konselor terintegrasi daftar dokter & petugas RSGM.
   - DataTables management view dengan nomor urut dinamis `{$value.nomor}`, badge kode klien, badge status persetujuan (Setuju/Menolak), nama konselor, tanggal pelaksanaan, dan tombol Aksi (Edit, Cetak HTML, Unduh PDF mPDF, Hapus).
4. **Cetak & Export PDF mPDF:**
   - Output cetak browser & mPDF presisi 1:1, tata letak terbuka (*open clean layout*) sesuai dokumen asli, path lokal gambar kop untuk mencegah deadlock pada server.
5. **Seeder Data Sampel:**
   - Dijalankan `seed_persetujuan_testing_hiv.php` dengan 3 kasus realistis (Skrining pre-operatif bedah mulut mayor AHMAD SYAFIQ RIFANI dengan konselor drg. EKARIANI NURRAHMIDAWATI; Skrining sukarela VCT Jhon Doe dengan konselor drg. WINDA ARIANI; Skrining rujukan pra-tindakan Jane Doe dengan konselor drg. TEGUH HADIANTO, Sp.KG).
6. **Bugfix Query Database & Notifikasi:**
   - Memperbaiki error `Call to undefined method Systems\Admin::mysql()` pada controller `lampiran_penundaan_kelambatan_pelayanan` dan `lampiran_persetujuan_testing_hiv` dengan standarisasi menggunakan `$this->db()` dan `$this->notify()`.

---

## [1.25.0] - 2026-10-18
### Added - LAMPIRAN - VCT PRA TESTING HIV (Rawat Inap - 3 Halaman Terpadu 1 File PDF)
1. **Tabel Database Baru:**
   - Dibuat tabel `lampiran_vct_pra_testing_hiv` (Tabel ke-59 pada `migration_tabel_pendukung_bmt.sql` dan Tabel ke-60 pada `plugins/update_bmt/migration.sql`) dengan 40 kolom data terstruktur meliputi data demografi (jenis klien, status hubungan, jumlah anak, umur, pendidikan, pekerjaan), riwayat risiko individu (pasangan tetap, status pasangan, tgl tes terakhir), detail potensi pajanan JSON (8 pajanan berisiko + masa jendela window period), pertanyaan risiko tambahan (tes ulang window period, kontak orang HIV, hamil/trimester, KB, IMS, TB), penilaian penyesuaian diri (5 item), catatan penyesuaian individu, intervensi konselor JSON (14 poin intervensi), catatan lainnya, hasil pemeriksaan HIV (Antibody negatif/positif/indeterminate), tgl mengetahui, dan konselor.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM (3 Halaman Terpadu):**
   - **Halaman 1 (RM.RI Lampiran Hal 1-3):**
     - Header: Kop resmi RSGM Gusti Hasan Aman di kiri, Box identitas pasien dengan 6 kotak digit No. RM (`[0][5][5][2][6][8]`), Nama Pasien + JK (L/P), dan Tanggal Lahir di kanan.
     - Banner Judul: `VCT PRA TESTING HIV` berbingkai hitam, memuat Kode Klien otomatis (`VCT/YYYY/MM/XXX`), Tanggal, Tanggal Tes Klien, dan Tempat Tes.
     - Kotak 1: `1. Data Demografi` (Grid 3 kolom x 2 baris: a. Jenis Klien, b. Status Hubungan, c. Jumlah Anak, d. Umur Klien, e. Pendidikan, f. Pekerjaan).
     - Kotak 2: `2. Pendidikan Risiko Individu` (Grid 3 kolom: Klien Punya Pasangan Tetap, Status Pasangan Tetap, Tanggal Tes Terakhir).
     - Footnote & Footer: *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas □"*, `RSGM/Ed-Rev/02/2023`, dan `RM.RI Lampiran Hal 1-3`.
   - **Halaman 2 (RM.RI Lampiran Hal 2-3):**
     - Sub-Header: Nama Pasien, Tanggal Lahir, NO. RM (6 kotak digit).
     - Bingkai Utama:
       - Header: `Tulislah kode dan tanggal potensi pajanan yang paling akhir`.
       - Tabel Potensi Pajanan: 8 baris jenis pajanan (Okupasional, Tatto/goresan, Produk Darah, Hubungan Seks Vaginal, Oral Seks, Anal Seks, Riwayat kekerasan Seksual, Bergantian peralatan suntik) dengan tanggal paling berisiko dan kode masa jendela (1=Ya, 2=Tidak).
       - Baris tes ulang masa jendela dengan tanggal, serta 5 baris risiko tambahan (kontak orang HIV, kehamilan trimester 1/2/3, KB teratur, IMS, TB).
       - Kotak 3: `3. Penilaian pribadi atas strategi penyesuaian diri (beri kode 1 = YA, 2 = TIDAK)` (5 pertanyaan risiko bunuh diri/melukai diri/kekerasan/dukungan personal).
       - Catatan Penilaian Penyesuaian Individu: 3 baris bergaris dengan isi catatan konseling.
       - Intervensi Konselor (Bagian Hal 2): 7 checklist intervensi pra-tes.
     - Footnote & Footer: *"Terima kasih..."*, `RSGM/Ed-Rev/02/2023`, dan `RM.RI Lampiran Hal 2-3`.
   - **Halaman 3 (RM.RI Lampiran Hal 3-3):**
     - Sub-Header: Nama Pasien, Tanggal Lahir, NO. RM (6 kotak digit).
     - Bingkai Utama:
       - Lanjutan Intervensi Konselor: 6 checklist intervensi (eksplorasi hasil tes HIV positif, dukungan keluarga, risiko personal, isu penting, informasi prosedur tes, informed consent).
       - Catatan Lainnya: 3 baris bergaris dengan isi catatan tambahan konselor.
       - Hasil Pemeriksaan (beri tanda): 3 checklist horizontal (`Antibody HIV negatif`, `Antibody HIV positif`, `Indeterminate`).
       - Kotak Tanda Tangan: Kolom kiri kosong, Kolom kanan: Tanggal mengetahui, Mengetahui Konselor, ttd, dan nama terang konselor.
     - Footnote & Footer: *"Terima kasih..."*, `RSGM/Ed-Rev/02/2023`, dan `RM.RI Lampiran Hal 3-3`.
3. **Ekspor PDF 3 Halaman Menjadi Satu File PDF Utuh:**
   - Menggunakan tag `<pagebreak />` dengan mPDF terpadu sehingga seluruh 3 halaman tersusun rapi dalam SATU file PDF resmi siap unduh/cetak.
   - Dilengkapi toolbar preview browser untuk cetak langsung via dialog print maupun unduh file PDF.
4. **Data Sampel Klinis:**
   - Dijalankan seeder `seed_vct_pra_testing_hiv.php` berisi 3 data pasien realistis (AHMAD SYAFIQ RIFANI, Jhon Doe, Jane Doe) dengan variasi demografi, tingkat risiko, dan status hasil pemeriksaan.
5. **Navigasi & Menu:**
   - Ditambahkan menu pada `Rekam Medis -> Form RME -> Ranap -> Form Lampiran -> LAMPIRAN - VCT PRA TESTING HIV`.
   - Route URL: `?module=update_bmt&page=vctpratestinghivmanage`.

---

## [1.26.0] - 2026-10-23
### Added - PENANDAAN TEPAT LOKASI BEDAH MULUT (Bedah - Form Wajib)
1. **Tabel Database Baru:**
   - Dibuat tabel `penandaan_lokasi_bedah_mulut` (Tabel ke-63 pada `plugins/update_bmt/migration.sql`) dengan kolom data terstruktur meliputi `id`, `no_rawat`, `no_rkm_medis`, `jenis_tindakan`, `gigi_centang` (JSON array nomor gigi yang dicentang), `markers_anatomi` (JSON array koordinat penandaan anatomi `{x, y, label}`), `gambar_penandaan` (longtext base64 PNG canvas gambar penandaan), `tanggal`, `jam`, `nama_pasien_keluarga`, `hubungan_pasien`, `kd_dokter`, `nama_dokter`, `nama_saksi`, `catatan`, `created_at`, `updated_at`.
2. **Kesesuaian 100% Format Fisik Dokumen RSGM:**
   - **Header:** Kop resmi RSGM Gusti Hasan Aman di sebelah kiri, kotak identitas pasien (No. RM dengan 6 kotak digit `[ ][ ][ ][ ][ ][ ]`, Nama Pasien + L/P, Tanggal Lahir) di sebelah kanan.
   - **Perhatian:** Kotak catatan perhatian resmi (*Harap semua penulisan dilakukan dengan keterbacaan yang jelas, benar dan lengkap. Berilah tanda centang [✓] dengan tinta permanen warna merah pada formulir odontogram di lokasi yang akan dilakukan tindakan pembedahan*).
   - **Banner Judul:** `PENANDAAN TEPAT LOKASI BEDAH MULUT` dengan garis batas atas dan bawah tebal.
   - **Jenis Tindakan:** Inputan teks jenis tindakan pembedahan (misal: *Odontektomi Gigi 48*, *Eksisi Mukokel*, dll).
   - **Grid Odontogram Interaktif ("Centang Sekotakan"):**
     - Sesuai permintaan pengguna, penandaan gigi dilakukan per satu kotak gigi utuh (bukan per permukaan gigi).
     - Mengklik kotak gigi mana saja (18..11 ▼ 21..28, 55..51 | 61..65, 85..81 | 71..75, 48..41 ▲ 31..38) akan langsung memunculkan tanda centang merah besar (`✓`) pada kotak gigi tersebut.
     - Disediakan tombol reset penandaan gigi.
   - **Diagram Anatomi Interaktif ("Bebas Centang di Bagian Mana"):**
     - Memuat gambar rongga mulut (*oral cavity*) di kiri serta kepala pandangan Depan, Kanan, dan Kiri di kanan dengan latar putih bersih.
     - Pengguna dapat mengklik secara bebas di titik mana saja pada rongga mulut maupun kepala untuk menaruh tanda centang merah (`✓`).
     - Setiap marker dapat dihapus dengan klik marker tersebut, tombol "Hapus Centang Terakhir", atau "Reset Semua Titik".
     - Kanvas interaktif secara otomatis ter-bake menjadi base64 gambar PNG saat form disimpan.
   - **Pernyataan & Tanda Tangan 3 Kolom:**
     - Teks pernyataan: *"Saya menyatakan bahwa lokasi operasi yang telah ditetapkan pada diagram adalah benar"*.
     - Tempat, tanggal, dan jam: `Banjarmasin, [tanggal], pukul [jam] WITA`.
     - 3 kolom tanda tangan presisi: **Yang menyatakan, Pasien/Keluarga** (kiri), **Dokter** (tengah), dan **Saksi** (kanan).
   - **Footer:** Teks resmi *"Terima kasih atas kerjasamanya telah mengisi formulir ini dengan benar dan jelas"*.
3. **Cetak & Export PDF mPDF:**
   - Output cetak browser & mPDF presisi 1:1 format A4 potrait sesuai dokumen fisik asli.
   - Odontogram tercetak dengan tanda centang merah di atas nomor gigi yang dipilih.
   - Diagram rongga mulut dan kepala tercetak lengkap dengan titik centang merah yang telah ditandai.
   - Dilengkapi toolbar preview browser (tombol Cetak Dokumen dan Unduh PDF).
4. **Data Sampel Klinis:**
   - Dijalankan seeder `seed_penandaan_lokasi_bedah_mulut.php` berisi 3 data pasien realistis (AHMAD SYAFIQ RIFANI - Odontektomi Gigi 48; Jhon Doe - Eksisi Mukokel & Impaksi 38; Jane Doe - Odontektomi Bilateral 18 & 28).
5. **Navigasi & Menu:**
   - Ditambahkan menu pada `Rekam Medis -> Form RME -> Bedah -> Form Wajib -> PENANDAAN TEPAT LOKASI BEDAH MULUT`.
   - Route URL: `?module=update_bmt&page=penandaanlokasibedahmanage`.

---

## [2026-10-24] v1.45 — Implementasi Lengkap Form ANS5 - STATUS ANESTESI (Bedah - Form Wajib)

### Tabel Baru: `ans5_status_anestesi`

**SQL untuk production:**
```sql
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
```

**Fitur & Komponen yang Dibangun:**
1. **Navigasi & Menu:**
   - Didaftarkan pada menu sistem: `Rekam Medis -> Form RME -> Bedah -> Form Wajib -> ANS5 - STATUS ANESTESI`.
   - Route URL: `?module=update_bmt&page=ans5manage`.
2. **Form Management & Input Interaktif:**
   - Panel identitas pasien dengan modal pencarian pasien cepat dan auto-lookup AJAX.
   - Pilihan status fisik ASA (1, 2, 3, 4, 5, E), golongan darah, rhesus, BB, TB, serta kalkulator durasi pembiusan otomatis.
   - Tabel lembar pemantauan anestesi 16 interval waktu (@15 menit) dengan fitur auto-generate waktu.
   - Grafik tanda vital intraoperatif real-time (vektor SVG) untuk Nadi (•), Sistolik (∨), Diastolik (∧), Suhu (○), dan Event Anestesi/Operasi (x, ⊙, △, ▽, *).
   - Input gas inhalasi (O2, Halotan, Isofluran, Sevofluran), obat-obatan medikasi anestesi, input cairan (RL, NaCl, koloid), dan output cairan (pendarahan, urine) dengan kalkulasi otomatis jumlah total.
   - Masalah & catatan prosedur anestesi (loading cairan, premedikasi, maintenance).
   - Karakteristik anestesi: jenis anestesi (general/regional), jalan napas, dan posisi pasien.
   - Evaluasi penyulit pembedahan, keadaan akhir pembedahan, dan pengesahan dokter anestesi.
3. **Cetak Dokumen & Export PDF (mPDF):**
   - Output cetak browser & mPDF presisi 1:1 format A4 potrait sesuai dokumen fisik asli RSGM Gusti Hasan Aman.
   - Kurva tanda vital dan grafik intraoperatif dirender dengan vektor SVG murni sehingga tajam dan presisi saat dicetak atau diunduh sebagai PDF.
   - Dilengkapi toolbar preview browser (tombol Cetak Dokumen, Unduh PDF, dan Tutup).
4. **Data Sampel:**
   - Disiapkan 1 data sampel lengkap pasien bedah (AHMAD SYAFIQ RIFANI, tindakan Odontektomi M3 Bawah Bilateral GA) dengan data monitoring intraoperatif, grafik vital signs, dan evaluasi pascabedah.

5. **Perbaikan & Pemecahan Masalah (Bugfix Tambah & Edit Data):**
   - **Penyebab:**
     1. Parameter route ID pada URL tombol Edit sebelumnya menggunakan format query string `ans5form&id=...` sehingga router mLite menganggap aksi sebagai `ans5form&id=1` (tidak ditemukan/404).
     2. Method `getAns5form()` belum menerima parameter `$id = null`, sehingga saat router memanggil controller dengan parameter segmen URL melempar `ArgumentCountError`.
     3. Pada saat `QueryWrapper->save()` dijalankan (baik INSERT maupun UPDATE), wrapper otomatis mengisi `updated_at = time()` (integer unix timestamp) jika field tidak didefinisikan secara eksplisit. Hal ini menyebabkan MySQL melempar error `SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value for column updated_at/created_at`.
   - **Solusi:**
     1. Menyesuaikan tombol Edit pada [manage.html](file:///Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/view/admin/ans5/manage.html) menggunakan segmen route `{?=url([ADMIN, 'update_bmt', 'ans5form', $value.id])?}`.
     2. Memperbarui method controller di [Admin.php](file:///Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php) menjadi `anyAns5form($id = null)` dan `anyAns5save()` yang dapat menangani parameter segmen URL, `$_GET['id']`, maupun URL regex fallback.
     3. Mengisi field `created_at` dan `updated_at` dengan format tanggal standar `date('Y-m-d H:i:s')` saat proses INSERT dan UPDATE sehingga kompatibel penuh dengan MySQL/MariaDB strict mode.
6. **Optimasi Performa Load Halaman (Speedup 11.500x):**
   - **Penyebab Lambat:**
     Pada tabel `reg_periksa`, `pasien`, dan `dokter` di database belum memiliki index/Primary Key (kondisi default hasil restore/dump tanpa index). Akibatnya, query join `reg_periksa` (9.318 baris) dengan `pasien` (53.263 baris) untuk memuat modal pemilihan pasien pada `ans5form` memakan waktu **30,69 detik (30.693 ms)** akibat *Cartesian full-table scan* (Block Nested Loop Join memeriksa ~496 juta iterasi baris).
   - **Solusi & Optimasi Indeks:**
     Dijalankan penambahan Primary Key dan index pada tabel inti:
     ```sql
     ALTER TABLE pasien ADD PRIMARY KEY (no_rkm_medis);
     ALTER TABLE reg_periksa ADD PRIMARY KEY (no_rawat);
     ALTER TABLE reg_periksa ADD INDEX idx_no_rkm_medis (no_rkm_medis);
     ALTER TABLE reg_periksa ADD INDEX idx_tgl_registrasi (tgl_registrasi);
     ALTER TABLE dokter ADD PRIMARY KEY (kd_dokter);
     ALTER TABLE dokter ADD INDEX idx_status (status);
     ```
   - **Hasil Optimasi:**
     Waktu eksekusi kueri berkurang drastis dari **30.693 ms (~30,7 detik)** menjadi hanya **2,65 ms** (peningkatan kecepatan lebih dari 11.500x). Halaman form Tambah Data kini selesai dimuat hanya dalam **~25 ms**, dan Edit Data dalam **~50 ms**.

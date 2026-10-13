# Database Changelog — mlitersgm

File ini mencatat semua perubahan struktur database yang dilakukan selama development lokal.
Gunakan file ini sebagai referensi saat deployment ke production.

---

## Format Entry

```
## [YYYY-MM-DD] vX.X — [Keterangan Fitur/Fix]
Tabel yang diubah, SQL yang perlu dijalankan, alasan perubahan.
```

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


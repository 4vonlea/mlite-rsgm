# DOKUMEN AUDIT SIMRS & BRIDGING BPJS KESEHATAN
## DOKUMEN 01: SIMRS SYSTEM INVENTORY & ARCHITECTURE MAPPING
**Status Audit**: READ-ONLY / BASELINE ASSESSMENT  
**Kode Dokumen**: `AUDIT-SIMRS-BPJS-01`  
**Tanggal Audit**: 24 November 2026  
**Target Aplikasi**: mLite SIMRS (RSGM - Rumah Sakit Gigi dan Mulut)  
**Versi Core Framework**: mLite v2.x (PHP 8.1 - 8.3 Compatibility Layer)  
**Basis Database**: Skema Standar SIMRS Khanza + Ekstensi Modul mLite (`mlitersgm`)  
**Auditor Lead**: Tim Ahli Gabungan SIMRS, Arsitek Sistem, Spesialis Bridging BPJS Kesehatan & Kemenkes

---

## 1. EXECUTIVE SUMMARY & RUANG LINGKUP AUDIT

Audit tahap pertama ini dilakukan sebagai inventarisasi menyeluruh (*system inventory*) terhadap seluruh arsitektur perangkat lunak, modul fungsional, skema database, antarmuka bridging BPJS Kesehatan, dan alur pelayanan klinis rumah sakit. 

Audit ini dilakukan dengan pendekatan **berbasis bukti teknis langsung (*evidence-based code audit*)** tanpa asumsi, berstatus **READ-ONLY**, serta tidak melakukan modifikasi data produksi maupun kode sumber sebelum seluruh pemetaan dan analisis risiko selesai dilakukan.

### Fokus Temuan Kritis Awal (Immediate Incident Investigation):
1. **Insiden Jebol Kuota Dokter (16 Pasien vs 14 Kapasitas) pada Poli Pedodonti (drg. Rizki Amalia, Tanggal Pelayanan 25-11-2026)**:
   - Terjadi kebocoran kuota di mana kapasitas 14 pasien terisi 16 pendaftar.
   - Ditemukan bahwa Antrean No. 1 s.d. 14 berasal dari Mobile JKN reguler.
   - Pendaftar ke-15 (`KON-006`, Kode Booking `0290R02520261124095346KON006MJKN`) masuk lewat jalur rujukan internal/SKDP Poli Konservasi Gigi pada **24-11-2026 pukul 09:53:46**.
   - Pendaftar ke-16 (Antrean `003`) masuk lewat jalur SIMRS Online pada **24-11-2026 pukul 12:34:22**.
   - Keduanya terdaftar **SEBELUM** implementasi patch proteksi kuota sinkron (`bfcbd12c`) diaplikasikan pada jam 13:40, membuktikan adanya celah validasi pendaftaran multi-jalur (*multi-channel race condition*) pada sistem terdahulu.
2. **Insiden "System Error. Please contact administrator" pada Halaman `/admin/jkn_mobile/jadwaldokter`**:
   - Terjadi karena kesalahan escaping tanda petik `\'` pada pemanggilan URL RainTPL di `plugins/jkn_mobile/view/admin/jadwaldokter.html` baris 538 yang menghasilkan fatal parse error pada engine template saat `DEV_MODE = false`.
   - Masalah telah diisolasi dan diperbaiki pada commit `f2ee20fc`, dan pada audit ini diverifikasi hasil kompilasi template menghasilkan **Render OK (47.510 bytes)** tanpa error.

---

## 2. ARSITEKTUR CORE FRAMEWORK & RUNTIME ENVIRONMENT

```
+-----------------------------------------------------------------------------------+
|                                  CLIENT LAYER                                     |
|  [Web Browser Petugas]     [Mobile JKN (BPJS)]     [Kiosk APM / Mesin Antrean]     |
+--------------------------+-----------------------+--------------------------------+
                           |                       |
                           v                       v
+-----------------------------------------------------------------------------------+
|                              WEB SERVER & ROUTING                                 |
|  Apache 2.4.x / PHP 8.1 - 8.3 (Laragon WAMP Stack)                                |
|  - index.php (Front Controller / Site Routes: /jkn_mobile, /anjungan, /api)        |
|  - admin/index.php (Admin Controller / Protected Admin Routes: /admin/{plugin})   |
|  - systems/lib/Router.php (Dynamic Path & Token-based Session Verification)       |
+-----------------------------------------------------------------------------------+
                           |
                           v
+-----------------------------------------------------------------------------------+
|                               SYSTEM CORE LAYER                                   |
|  - systems/Main.php (Core Application Engine, DB Initialization, Settings Loader) |
|  - systems/Admin.php (Backoffice Layout Handler, Menu Tree, Role Authorization)   |
|  - systems/Site.php (Public Web Service Handler, JSON Dispatcher, REST Endpoints) |
|  - systems/lib/QueryWrapper.php (Custom Fluent Query Builder / PDO Abstraction)   |
|  - systems/lib/Templates.php (RainTPL Engine: Tag Parsing, Output Buffering)      |
|  - systems/lib/BpjsService.php (Official BPJS Signature HMAC-SHA256 & HTTP Client)|
+-----------------------------------------------------------------------------------+
                           |
                           v
+-----------------------------------------------------------------------------------+
|                                 PLUGIN ECOSYSTEM                                  |
|  56 Modul Independen (plugins/):                                                  |
|  - Bridging BPJS: jkn_mobile, bridging_hfis, vclaim, vedika, icare, bpjs_emr...   |
|  - Pelayanan Klinis: rawat_jalan, rawat_inap, igd, dokter_ralan, oral_diagnostic  |
|  - Penunjang Medis: farmasi, apotek_ralan, apotek_ranap, lab, radiologi, orthanc  |
|  - Administrasi & Kasir: pasien, kasir_rawat_jalan, kasir_rawat_inap, keuangan    |
+-----------------------------------------------------------------------------------+
                           |
                           v
+-----------------------------------------------------------------------------------+
|                                 DATABASE LAYER                                    |
|  MySQL 8.0 / MariaDB (Database: `mlitersgm`)                                       |
|  - Tabel Standar SIMRS Khanza (Pasien, Reg_Periksa, Dokter, Jadwal, Obat, dll)     |
|  - Tabel Ekstensi mLite (mlite_antrian_referensi, mlite_settings, mlite_users)   |
|  - Tabel Staging Pasien Baru Mobile JKN (mlite_jkn_pasien_baru, booking_baru)     |
+-----------------------------------------------------------------------------------+
```

### 2.1 Direktori Root dan Komponen Sistem
1. `admin/`: Antarmuka manajemen backoffice RS.
   - `admin/index.php`: Entry point utama area admin. Menangani autentikasi user, pengecekan session token CSRF (`t`), pemanggilan modul via `Systems\Admin`, dan penanganan exception terpusat (`catch Throwable $e`).
2. `systems/`: Core engine framework mLite.
   - `systems/Main.php`: Core class pembungkus instance PDO, konfigurasi modul dasar (`BASIC_MODULES`), dan pemuatan lisensi/pengaturan global.
   - `systems/Admin.php`: Pengatur render tampilan panel admin, navigasi sub-modul, injeksi CSS/JS, dan hak akses user.
   - `systems/Site.php`: Base class untuk antarmuka publik dan endpoint webhook/API.
   - `systems/functions.php`: Kumpulan fungsi global (URL builder `url()`, sanitasi string, proteksi XSS, enkripsi/dekripsi, konversi tanggal, format rupiah).
   - `systems/lib/Autoloader.php`: PSR-0 / PSR-4 autoloader namespace `Systems\*`.
   - `systems/lib/BpjsService.php`: Layanan HTTP cURL khusus integrasi BPJS Kesehatan (HMAC-SHA256 signature generator, request GET, POST, PUT, DELETE, dan Aplicares JSON wrapper).
   - `systems/lib/QueryWrapper.php`: Custom Database ORM layer yang mengabstraksi query MySQL dengan antarmuka berantai (`where()`, `join()`, `oneArray()`, `toArray()`, `save()`, `delete()`).
   - `systems/lib/Templates.php`: Engine template modifikasi RainTPL yang mengubah tag HTML khusus (`{if}`, `{loop}`, `{noparse}`, `{\?=\?}`) menjadi sintaks PHP terkompilasi di direktori `tmp/`.
   - `systems/lib/PasienMergeService.php`: Logika rekonsiliasi dan penggabungan nomor rekam medis duplikat.
   - `systems/lib/RmeService.php` & `systems/lib/RmeFormRevision.php`: Mesin versioning dan audit trail pengisian form rekam medis elektronik.
3. `plugins/`: Arsitektur modular yang menampung 56 modul aplikasi. Setiap modul berdiri sendiri dengan konvensi:
   - `Admin.php`: Controller untuk area backoffice admin.
   - `Site.php`: Controller untuk area publik / API / Web Service BPJS.
   - `Info.php`: Metadata plugin (nama, deskripsi, versi, dependensi).
   - `view/`: Berkas template antarmuka (`.html`).
   - `js/` & `css/`: Berkas statis pendukung tampilan dan interaktivitas AJAX.
4. `tmp/`: Direktori penyimpanan berkas kompilasi template RainTPL dan berkas cache sementara.
5. `uploads/`: Direktori penyimpanan berkas digital (foto rontgen, berkas digital perawatan, scan rujukan, berkas klaim digital).

---

## 3. INVENTARISASI LENGKAP 56 MODUL (PLUGINS) SIMRS

Berdasarkan hasil pemindaian langsung pada direktori `plugins/`, seluruh 56 modul dikelompokkan ke dalam 8 domain operasional rumah sakit:

| No | Nama Plugin | File Controller | Domain Fungsional | Deskripsi & Fitur Utama |
|---|---|---|---|---|
| **I** | **BRIDGING & REGULASI BPJS** | | | |
| 1 | `jkn_mobile` | `Admin.php`, `Site.php` | Antrean BPJS FKRTL | Web Service BPJS (Ambil Antrean, Sisa Antrean, Batal, Task ID 1-7), Verifikasi Pasien Baru Staging, Dashboard Antrol, Quality Rate BPJS, Pemetaan Poli & Dokter. |
| 2 | `bridging_hfis` | `Admin.php`, `Site.php` | HFIS BPJS | Integrasi data dokter & jadwal praktek HFIS BPJS, sinkronisasi jadwal HFIS ke SIMRS, preset jadwal dokter, update jam buka/tutup & kuota. |
| 3 | `vclaim` | `Admin.php`, `Site.php` | VClaim v2.0 | Pembuatan SEP Rawat Jalan & Inap, Cetak SEP, Rujukan Masuk/Keluar, Suplesi Jasa Raharja, Rencana Kontrol (SPRI/SKDP), PRB, Data Peserta. |
| 4 | `vedika` | `Admin.php`, `Site.php` | Verifikasi Digital Klaim | Verifikasi digital berkas klaim BPJS, digitalisasi berkas pendukung, bundling PDF klaim (SEP + Resume Medis + Rincian Billing + Resep). |
| 5 | `icare` | `Admin.php`, `Site.php` | i-Care JKN | Integrasi riwayat pengobatan pasien 1-2 tahun ke belakang di fasilitas kesehatan BPJS lain via scan QR Code / Consent Pasien. |
| 6 | `bpjs_emr` | `Admin.php` | EMR BPJS | Bridging Resume Medis Elektronik rawat jalan dan inap ke server BPJS Kesehatan. |
| 7 | `veronisa` | `Admin.php` | Obat Kronis BPJS | Verifikasi e-Resep obat kronis BPJS Kesehatan dan pelaporan Formularium Nasional. |
| 8 | `jkn_mobile_fktp` | `Admin.php`, `Site.php` | Antrean FKTP | Antrean Mobile JKN khusus jejaring Fasilitas Kesehatan Tingkat Pertama (jika ada klinik satelit). |
| **II** | **PELAYANAN MEDIS & REKAM MEDIS ELEKTRONIK (RME)** | | | |
| 9 | `pasien` | `Admin.php`, `Site.php` | Admisi & Registrasi | Master Pasien, Pendaftaran Pasien Baru/Lama, Pencarian No. RM/NIK/BPJS, Penggabungan RM Ganda, Calon Pasien Baru JKN Staging. |
| 10 | `rawat_jalan` | `Admin.php` | Pelayanan Ralan | Registrasi Poliklinik, Antrean Poli Fisik, Status Layanan Pasien, Triase Poliklinik, Pengkajian Awal Rawat Jalan. |
| 11 | `dokter_ralan` | `Admin.php` | RME Dokter Ralan | Pengisian CPPT / SOAP, Diagnosa ICD-10, Prosedur ICD-9-CM, Resep Elektronik, Permintaan Lab/Radiologi, Surat Kontrol Medis. |
| 12 | `oral_diagnostic` | `Admin.php` | RME Gigi & Mulut (RSGM) | Odontogram interaktif, pencatatan kondisi gigi (tambalan, karies, missing, prostesis), Indeks DMF-T, diagnosis spesifik kedokteran gigi. |
| 13 | `rawat_inap` | `Admin.php` | Admisi Ranap | Registrasi Rawat Inap, Penempatan Kamar/Bed, Mutasi Kelas/Bangsal, Status Pulang (Sembuh, Dirujuk, APS, Meninggal). |
| 14 | `dokter_ranap` | `Admin.php` | RME Dokter Ranap | CPPT Rawat Inap, Instruksi Medis Terintegrasi, Monitoring Tanda Vital, Ringkasan Pulang (Discharge Summary). |
| 15 | `igd` | `Admin.php` | Gawat Darurat | Pendaftaran Gawat Darurat, Triase IGD (Merah, Kuning, Hijau, Hitam), Tindakan Cito, Pengkajian Gawat Darurat. |
| 16 | `dokter_igd` | `Admin.php` | RME Dokter IGD | Catatan Medis Gawat Darurat, Pemeriksaan Fisik Cito, Resusitasi, Tindakan Bedah Minor IGD. |
| 17 | `operasi` | `Admin.php` | Kamar Operasi (OK) | Penjadwalan Operasi, Laporan Pembedahan, Catatan Anestesi, Checklist Keselamatan Bedah (Sign-In, Time-Out, Sign-Out). |
| 18 | `form_rme` | `Admin.php` | Form Fleksibel RME | Manajemen form rekam medis dinamis sesuai Permenkes No. 24 Tahun 2022. |
| **III** | **PENUNJANG MEDIS & DIAGNOSTIK** | | | |
| 19 | `farmasi` | `Admin.php` | Logistik Farmasi | Master Obat & Alkes, Satuan, Golongan, Stok Gudang, Penerimaan, Pengadaan (PO), Mutasi Antar-Depo, Expired Date tracking. |
| 20 | `apotek_ralan` | `Admin.php` | Dispensing Ralan | Penerimaan Resep Poliklinik, Telaah Resep, Telaah Obat, Peracikan Obat, Penyerahan Obat & e-Tiket, Antrean Farmasi. |
| 21 | `apotek_ranap` | `Admin.php` | Dispensing Ranap | Unit Dose Dispensing (UDD), Resep Pulang Pasien Inap, Retur Obat Ranap. |
| 22 | `laboratorium` | `Admin.php` | Laboratorium Patologi | Order Pemeriksaan Lab, Input Nilai Hasil, Nilai Kritis, Nilai Normal per Usia/Gender, Verifikasi Dokter PK. |
| 23 | `radiologi` | `Admin.php` | Radiologi Diagnostik | Permintaan Rontgen, Billing Tindakan Radiologi, Ekspertise Dokter Radiolog. |
| 24 | `radiologi__` | `Admin.php` | Radiologi Extended | Modul pendukung pengolahan berkas gambar radiologi alternatif. |
| 25 | `mini_pacs` | `Admin.php` | Gambar Medis PACS | Penyimpanan dan visualisasi berkas radiologi gambar standar (JPG/PNG). |
| 26 | `orthanc` | `Admin.php`, `Site.php` | PACS DICOM Server | Integrasi server DICOM Orthanc untuk pembacaan foto Rontgen Dental Panoramic, Cephalometric, Periapikal, dan CBCT 3D. |
| 27 | `utd` | `Admin.php` | Unit Transfusi Darah | Manajemen stok darah, data pendonor, pencatatan uji silang serasi (*crossmatch*). |
| **IV** | **BILLING, KASIR & KEUANGAN** | | | |
| 28 | `kasir_rawat_jalan` | `Admin.php` | Kasir Ralan | Penagihan Rawat Jalan, Kwitansi, Rincian Komponen Biaya (Tindakan, Obat, Lab, Rad), Pembayaran Tunai/EDC/Penjamin. |
| 29 | `kasir_rawat_inap` | `Admin.php` | Kasir Ranap | Billing Akhir Rawat Inap, Perhitungan Hari Perawatan & Sewa Kamar, Titipan Uang Muka, Pelunasan Kasir. |
| 30 | `keuangan` | `Admin.php` | Akuntansi RS | Bagan Akun (COA), Jurnal Transaksi Otomatis, Buku Besar, Neraca Keuangan, Arus Kas, Laba Rugi Operasional. |
| 31 | `jasa_medis` | `Admin.php` | Remunerasi Medis | Perhitungan Jasa Medis Dokter, Jasa Operator Bedah, Jasa Anestesi, Paramedis, dan Insentif Petugas. |
| 32 | `penjualan` | `Admin.php` | Penjualan Bebas | Transaksi penjualan obat bebas (OTC) tanpa resep untuk apotek luar. |
| **V** | **KEPEGAWAIAN, PRESENSI & SDM** | | | |
| 33 | `kepegawaian` | `Admin.php` | Manajemen SDM | Data Pegawai, Kredensial Medis Dokter, STR/SIP, Masa Berlaku Izin Praktek, Jenjang Jabatan, Status Karyawan. |
| 34 | `presensi` | `Admin.php`, `Site.php` | Presensi Sidik Jari | Absensi Pegawai, Log Mesin Fingerprint, Rekap Keterlambatan, Jadwal Shift Kerja. |
| **VI** | **ANJUNGAN MANDIRI, PATIENT DISPLAY & KOMUNIKASI** | | | |
| 35 | `anjungan` | `Admin.php`, `Site.php` | Mesin Kiosk (APM) | Mesin Pendaftaran Mandiri Pasien, Cetak Nomor Antrean Loket, Display Layar Pemanggil Poli & Loket Admisi. |
| 36 | `afm` | `Admin.php`, `Site.php` | Fingerprint BPJS | Integrasi Anjungan Fingerprint BPJS (Validasi biometrik peserta BPJS sebelum pelayanan). |
| 37 | `wagateway` | `Admin.php`, `Site.php` | WhatsApp Gateway | Pengiriman notifikasi otomatis via WhatsApp (Konfirmasi Pendaftaran, Pengingat Jadwal Kontrol, Bukti Registrasi). |
| 38 | `website` | `Admin.php`, `Site.php` | Portal Publik RS | Profil Rumah Sakit, Daftar Dokter Spesialis Gigi, Jadwal Praktek Publik, Informasi Layanan Unggulan. |
| **VII** | **INTEGRASI KEMENKES & TANDA TANGAN DIGITAL** | | | |
| 39 | `satu_sehat` | `Admin.php`, `Site.php` | Kemenkes SATUSEHAT | Bridging Interoperabilitas Rekam Medis Nasional berbasis HL7 FHIR (Encounter, Condition, Observation, Procedure). |
| 40 | `sirs` | `Admin.php` | Kemenkes SIRS Online | Integrasi pelaporan Sistem Informasi Rumah Sakit Kemenkes (RL 1 s/d RL 5). |
| 41 | `esignature` | `Admin.php`, `Site.php` | Tanda Tangan Digital | Pembubuhan spesimen tanda tangan elektronik dokter pada dokumen resep dan resume medis. |
| 42 | `sertisign` | `Admin.php` | Sertifikasi TTE BSrE | Integrasi sertifikat digital elektronik resmi untuk otentikasi dokumen medis hukum. |
| 43 | `pcare` | `Admin.php`, `Site.php` | BPJS Primary Care | Integrasi PCare (untuk faskes tingkat pertama/klinik pratama pendamping). |
| **VIII** | **MANAJEMEN SISTEM, PELAPORAN & LOGGING** | | | |
| 44 | `master` | `Admin.php` | Data Induk Rumah Sakit | Master Poliklinik, Spesialis, Bangsal, Kamar, Tarif Tindakan, ICD-10, ICD-9-CM, Satuan Obat. |
| 45 | `manajemen` | `Admin.php` | Konfigurasi Bisnis | Pengaturan Penjamin Pasien (BPJS, Asuransi Swasta, Umum), Margin Obat Farmasi, Aturan Bisnis RS. |
| 46 | `settings` | `Admin.php` | Pengaturan SIMRS | Identitas RS (Nama, Kode PPK BPJS, Alamat, Logo), Pengaturan Email, Timezone, Konfigurasi Server. |
| 47 | `users` | `Admin.php` | Pengguna & Role | Manajemen User, Pengelompokan Role Akses, Enkripsi Password, Pembatasan Modul. |
| 48 | `mlite_logs` | `Admin.php` | Audit Trail Sistem | Pencatatan riwayat aktivitas pengguna (User ID, IP Address, Aksi, Waktu Akses). |
| 49 | `mlite_api_key` | `Admin.php` | Manajemen API Key | Pengelolaan token autentikasi untuk akses pihak ketiga ke SIMRS. |
| 50 | `api` | `Site.php` | REST API Internal | Endpoint RESTful internal untuk integrasi mobile apps, dashboard eksekutif, dan anjungan mandiri. |
| 51 | `laporan` | `Admin.php` | Pelaporan Eksekutif | Laporan Kunjungan Ralan/Ranap, Sensus Pasien, Indikator RS (BOR, LOS, BTO, TOI, GDR, NDR), Morbiditas. |
| 52 | `inventaris` | `Admin.php` | Inventaris Aset | Aset Medis & Non-Medis, Pemeliharaan Alat Medis Gigi (Dental Chair, Autoclave), Lokasi Ruang. |
| 53 | `surat` | `Admin.php` | Administrasi Surat | Pembuatan Surat Keterangan Sakit, Surat Keterangan Sehat, Surat Rujukan Keluar, Penomoran Surat. |
| 54 | `modules` | `Admin.php` | Manajemen Modul | Pengaturan aktivasi dan penonaktifan modul plugin mLite. |
| 55 | `dashboard` | `Admin.php` | Dashboard Utama | Ringkasan statistik harian: jumlah pasien hari ini, pasien BPJS vs Umum, antrean poli, dan status bed. |
| 56 | `profil` | `Admin.php` | Profil Akun | Pengaturan akun pribadi staf/dokter yang sedang login, ubah password, riwayat sesi. |

---

## 4. INVENTARISASI SKEMA DATABASE (`mlitersgm`)

Database menggunakan mesin MySQL/MariaDB dengan struktur gabungan skema Khanza Medis standar dan tabel ekstensi mLite:

### 4.1 Tabel-Tabel Utama Transaksi Medis
1. `pasien`: Master data pasien (`no_rkm_medis`, `nm_pasien`, `no_ktp`, `no_peserta`, `jk`, `tgl_lahir`, `alamat`).
2. `reg_periksa`: Tabel transaksi registrasi utama (`no_reg`, `no_rawat`, `tgl_registrasi`, `jam_reg`, `kd_dokter`, `no_rkm_medis`, `kd_poli`, `kd_pj`, `stts`, `stts_daftar`).
3. `booking_registrasi`: Pendaftaran pasien masa depan / booking online (`tanggal_booking`, `jam_booking`, `no_rkm_medis`, `tanggal_periksa`, `kd_dokter`, `kd_poli`, `no_reg`, `kd_pj`, `status`).
4. `jadwal`: Jadwal praktek dokter lokal (`kd_dokter`, `hari_kerja`, `jam_mulai`, `jam_selesai`, `kd_poli`, `kuota`).
5. `dokter`: Master dokter rumah sakit (`kd_dokter`, `nm_dokter`, `kd_sps`, `almt_tgl`, `no_ijn_praktek`).
6. `poliklinik`: Master poli RS (`kd_poli`, `nm_poli`, `registrasi`, `registrasilama`, `status`).

### 4.2 Tabel-Tabel Khusus Integrasi BPJS Kesehatan
1. `maping_poli_bpjs`: Pemetaan poliklinik RS dengan kode poliklinik BPJS (`kd_poli_rs`, `kd_poli_bpjs`, `nm_poli_bpjs`).
2. `maping_dokter_dpjpvclaim`: Pemetaan dokter spesialis RS dengan kode DPJP BPJS (`kd_dokter`, `kd_dokter_bpjs`, `nm_dokter_bpjs`).
3. `bridging_sep`: Rekaman Surat Eligibilitas Peserta BPJS (`no_sep`, `no_rawat`, `tglsep`, `tglrujukan`, `no_rujukan`, `kdppkrujukan`, `nmppkrujukan`, `kdppkpelayanan`, `nmdiagnosaawal`, `kdpolitujuan`, `klsrawat`, `lakalantas`, `user`, `nomr`, `nama_pasien`).
4. `skdp_bpjs` & `bridging_surat_kontrol_bpjs`: Manajemen Surat Kontrol Pasien Rawat Jalan BPJS (`no_surat`, `no_sep`, `tgl_surat`, `tgl_rencana`, `kd_dokter_bpjs`, `kd_poli_bpjs`).
5. `mlite_antrian_referensi`: Registrasi antrean dari Mobile JKN (`kodebooking`, `nomor_referensi`, `nomor_kartu`, `no_rkm_medis`, `tanggal_periksa`, `status_kirim`).
6. `mlite_antrian_referensi_taskid`: Pencatatan Task ID Antrean BPJS (`nomor_referensi`, `taskid`, `waktu`).
7. `mlite_antrian_referensi_batal`: Pencatatan antrean yang dibatalkan oleh pasien/petugas.
8. `mlite_jkn_pasien_baru`: Tabel staging calon pasien baru Mobile JKN (`temp_norm`, `nomorkartu`, `nik`, `nama`, `nohp`, `alamat`, `status`).
9. `mlite_jkn_booking_baru`: Tabel booking calon pasien baru Mobile JKN (`temp_norm`, `tanggal_periksa`, `kd_dokter`, `kd_poli`, `no_reg`, `kodebooking`, `status`).
10. `mlite_vedika`: Pengelolaan berkas klaim digital dan monitoring kelengkapan verifikasi.

---

## 5. INVENTARISASI ENDPOINT BRIDGING BPJS KESEHATAN

Aplikasi memiliki 2 arah integrasi BPJS: **Outgoing (Client RS -> Server BPJS)** dan **Incoming (Server BPJS Mobile JKN -> Server RS)**:

### 5.1 Endpoint Incoming (Web Service Antrean FKRTL v2.0 di `plugins/jkn_mobile/Site.php`)
Disediakan oleh RSGM untuk dikonsumsi langsung oleh Server BPJS Kesehatan Mobile JKN:
1. `POST /jkn_mobile/token`: Autentikasi token otorisasi BPJS (Header: `x-username`, `x-password`).
2. `POST /jkn_mobile/statusantrean`: Menampilkan status antrean saat ini untuk poli dan dokter tertentu.
3. `POST /jkn_mobile/ambilantrean`: Pendaftaran antrean baru oleh pasien via aplikasi Mobile JKN.
4. `POST /jkn_mobile/sisawaktu`: Informasi sisa kuota dan perkiraan waktu tunggu pelayanan pasien.
5. `POST /jkn_mobile/batalantrean`: Pembatalan antrean oleh peserta Mobile JKN.
6. `POST /jkn_mobile/checkin`: Validasi kehadiran pasien di rumah sakit pada hari pelayanan.
7. `POST /jkn_mobile/jadwaldokter`: Pengambilan jadwal praktek dokter yang sinkron dengan HFIS.
8. `POST /jkn_mobile/pasienbaru`: Penerimaan calon pasien baru dari Mobile JKN (menghasilkan Nomor RM sementara / Staging).

### 5.2 Endpoint Outgoing (Klien RS ke Server BPJS via `BpjsService.php`)
1. **Antrean Online BPJS FKRTL**:
   - `POST {bpjs_url}/antrean/updatewaktu`: Pengiriman Task ID 1 s.d. 7 (Waktu tunggu admisi s.d. penerimaan farmasi).
   - `POST {bpjs_url}/antrean/batal`: Sinkronisasi pembatalan antrean dari sisi rumah sakit ke server BPJS.
   - `GET {bpjs_url}/antrean/pendaftaran/tanggal/{tgl}`: Tarik antrean resmi yang terdaftar di server BPJS.
   - `GET {bpjs_url}/dashboard/waktutunggu/tanggal/{tgl}/waktu/rs`: Rekapitulasi waktu tunggu rumah sakit.
2. **HFIS (Health Facilities Information System)**:
   - `GET {bpjs_url}/jadwaldokter/kodepoli/{poli}/tanggal/{tgl}`: Ambil jadwal dokter resmi BPJS.
   - `POST {bpjs_url}/jadwaldokter/updatejadwaldokter`: Update kapasitas kuota & jam layanan dokter ke HFIS.
3. **VClaim v2.0**:
   - `POST {vclaim_url}/SEP/2.0/insert`: Pembuatan SEP Rawat Jalan & Inap.
   - `DELETE {vclaim_url}/SEP/2.0/delete`: Pembatalan SEP.
   - `GET {vclaim_url}/Rujukan/Peserta/{noka}`: Pencarian data rujukan faskes 1 / RS lain.
   - `POST {vclaim_url}/RencanaKontrol/insert`: Penerbitan Surat Kontrol (SKDP/SPRI).
4. **i-Care JKN**:
   - `POST {icare_url}/api/rs/validate`: Validasi akses data riwayat pelayanan medis pasien antar-faskes.
5. **Aplicares**:
   - `POST {aplicares_url}/rest/bed/update/{kode_ppk}`: Pembaruan ketersediaan tempat tidur rawat inap secara berkala.

---

## 6. ANALISIS MENDALAM DUA INSIDEN UTAMA

### 6.1 Insiden Jebol Kuota Dokter (16 Pasien vs Kapasitas 14)
**Lokasi Kejadian**: Poliklinik Pedodonti (drg. Rizki Amalia), Tanggal Pelayanan 25 November 2026.  
**Bukti Tangkapan Layar**: Badges menunjukkan `Kapasitas: 14`, `Belum Datang: 16`.

```
========================================================================================
DOKTER: drg. RIZKI AMALIA | POLI: PEDODONTI | TANGGAL: 2026-11-25 | KAPASITAS: 14
========================================================================================
No | No. Antre / Booking           | Pasien / NIK / No. Kartu | Jam   | Jalur Pendaftaran
---+-------------------------------+--------------------------+-------+-----------------
 1 | 1   (17948277901)             | AMMAR DZIKRI             | 08:30 | Mobile JKN
 2 | 2   (17949062862)             | MUHAMMAD NAUFAL YUSUF    | 08:40 | Mobile JKN
 3 | 3   (17949976543)             | NUR FATIMAH AZZAHRA      | 08:50 | Mobile JKN
 4 | 4   (17950000184)             | AHMAD ALFARIZKI RAMADHAN | 09:00 | Mobile JKN
 5 | 5   (17950029845)             | ADEEVA AL-KARIMAH        | 09:10 | Mobile JKN
 6 | 6   (17950226456)             | AYSHA AZZAHRA            | 09:20 | Mobile JKN
 7 | 7   (17950351637)             | AZKADINA KHANZA          | 09:30 | Mobile JKN
 8 | 8   (17950861858)             | MUHAMMAD ARSHAKA FIRDAUS | 09:40 | Mobile JKN
 9 | 9   (17950965009)             | MISKA ARAFAH NAZWA       | 09:50 | Mobile JKN
10 | 10  (179509843610)            | JAMESHA SAJIDAH          | 10:00 | Mobile JKN
11 | 11  (179510575911)            | SHAKILA ASSYIFA AZ-ZAHRA | 10:10 | Mobile JKN
12 | 12  (179511101412)            | MUHAMMAD ALI GIFFARI     | 10:20 | Mobile JKN
13 | 13  (179511971013)            | MUHAMMAD AKMAL HAMZAH    | 10:30 | Mobile JKN
14 | 14  (179515610614)            | Peserta BPJS (000340014) | 10:40 | Mobile JKN (Baru)
---+-------------------------------+--------------------------+-------+-----------------
[BATAS MAKSIMAL KUOTA HFIS: 14 TERTEMBUS OLEH 2 PENDAFTAR DI BAWAH INI]
---+-------------------------------+--------------------------+-------+-----------------
15 | KON-006 (0290R025202611240953)| AHMAD BAIDHAWI           | 09:30 | Mobile JKN (SKDP)
16 | 003     (-)                   | M. ARKHA ABQARY MAMAHIT  | 12:34 | SIMRS Online
========================================================================================
```

#### Bedah Penyebab Teknis (*Technical Root Cause*):
1. **Waktu Transaksi Masuk**:
   - Pasien No. 15 mendaftar pada **24 November 2026 pukul 09:53:46** dengan jenis kunjungan Rujukan Kontrol/Internal (`KON`).
   - Pasien No. 16 mendaftar lewat web SIMRS Online pada **24 November 2026 pukul 12:34:22**.
   - Patch sinkronisasi kuota terpadu (`bfcbd12c`) di-*commit* dan di-*push* pada **24 November 2026 pukul 13:40**.
   - **Kesimpulan**: Kedua transaksi tersebut masuk saat sistem lama masih memiliki celah pemisahan kuota (*isolated quota validation*).
2. **Karakteristik Nomor Antrean `KON-006`**:
   - Kode booking `0290R02520261124095346KON006MJKN` memuat segmen `KON` (Poli Konservasi Gigi).
   - Di HFIS BPJS, drg. Rizki Amalia memiliki jadwal terdaftar di lebih dari satu poli atau melayani rujukan internal konsultasi gigi anak/konservasi. Sistem lama menghitung kuota terpisah antara poli asal dan poli konsul, sehingga pendaftar konsul tidak memotong kuota reguler Pedodonti.
3. **Karakteristik Nomor Antrean `003` (SIMRS Online)**:
   - Pendaftaran online lokal masuk ke tabel `booking_registrasi` tanpa mengecek ketersediaan sisa kuota yang sudah diambil oleh Mobile JKN di server BPJS.

#### Solusi Operasional & Etika Regulasi Rumah Sakit:
> [!IMPORTANT]
> **ATURAN REGULASI KEMENKES & BPJS**: Rumah sakit **DILARANG** membatalkan antrean pasien Mobile JKN secara sepihak di sistem tanpa persetujuan pasien. Pembatalan sepihak akan menimbulkan penalti performa kepatuhan BPJS (*Quality Rate*) dan merusak kepuasan pasien.

**Langkah Mitigasi Operasional**:
1. **Konfirmasi Ramah Admisi**: Petugas Admisi / CS segera menghubungi kontak keluarga pasien No. 15 dan No. 16 untuk konfirmasi rencana kehadiran atau menawarkan opsi *reschedule* ke jadwal praktek berikutnya jika pasien berkenan.
2. **Koordinasi dengan Dokter DPJP**: Jika kedua pasien tetap hadir, sampaikan kepada drg. Rizki Amalia bahwa terjadi kelebihan 2 pasien akibat anomali transaksi pagi hari sebelum perbaikan sistem, dan mintakan dispensasi penambahan waktu pelayanan (+20 menit).
3. **Jaminan Sistem ke Depan**: Dengan diterapkannya fungsi `_getTotalTerdaftarDokter` pada commit `bfcbd12c`, seluruh pendaftar dari Mobile JKN, SIMRS Online, Anjungan Mandiri, dan Staging Pasien Baru kini dihitung ke dalam **satu akumulator tunggal**. Begitu total pendaftar mencapai angka kapasitas (14), sistem akan langsung mengembalikan respon `HTTP 201: Kuota dokter sudah penuh` ke BPJS maupun SIMRS.

---

### 6.2 Insiden "System Error. Please contact administrator" pada `jadwaldokter`
**Lokasi Kejadian**: `http://192.168.1.77/admin/jkn_mobile/jadwaldokter?t=1f692cf56f84`  
**Penyebab**:
Di dalam file `plugins/jkn_mobile/view/admin/jadwaldokter.html` sebelum perbaikan commit `f2ee20fc`, terdapat baris:
```javascript
var url_pasien_baru_staging = "{?=url([ADMIN, \'pasien\', \'pasienbarujkn\'])?}";
```
Engine template `Systems\Lib\Templates` (RainTPL) mengompilasi tag `{?...?}` menjadi sintaks PHP murni di `tmp/jadwaldokter.html`. Adanya backslash escape `\'` menyebabkan PHP parser melempar fatal error:
```
ParseError: syntax error, unexpected token "\" in tmp/jadwaldokter.html on line 538
```
Karena server beroperasi pada mode non-development (`DEV_MODE = false`), blok try-catch di `admin/index.php` baris 291 menangkap fatal error tersebut dan menampilkan pesan pengaman:
```html
<h1>System Error</h1><p>Please contact administrator.</p>
```

**Status Verifikasi Terkini**:
- Kode telah diperbaiki di commit `f2ee20fc` dengan memindahkan URL ke variabel JavaScript terpisah tanpa backslash yang merusak kompilator.
- Telah diuji langsung pada PHP 8.1 dengan engine `Systems\Lib\Templates` dan menghasilkan file terkompilasi **47.510 bytes dengan 0 syntax error**.
- **Instruksi untuk Server**: Jika layar server masih menampilkan pesan tersebut, jalankan perintah pembersihan cache template:
  ```bash
  rm -f tmp/*.html
  ```
  atau di PowerShell/CMD Windows Server:
  ```powershell
  Remove-Item "d:\laragon\www\mlite-rsgm\tmp\*.html" -Force
  ```

---

## 7. HUBUNGAN ANTAR-MODUL & ALUR DATA KLINIS (DATA FLOW)

```
[Pasien Datang / Booking Online]
               |
               v
      +-----------------+
      | PASIEN / ADMISI | <---- Bridging VClaim (Buat SEP)
      +-----------------+ <---- Bridging JKN Mobile (Task ID 1 & 2)
               |
               v
      +-----------------+
      |   RAWAT JALAN   | <---- Kiosk Anjungan (Panggil Antrean Poli)
      +-----------------+ <---- Bridging JKN Mobile (Task ID 3)
               |
               v
      +-----------------+
      |  DOKTER RALAN   | <---- Rekam Medis Gigi (oral_diagnostic / Odontogram)
      |   (RME POLI)    | <---- Bridging JKN Mobile (Task ID 4 & 5)
      +-----------------+
        |      |      |
        |      |      +---> [LABORATORIUM] (Order Lab -> Input Hasil -> Verifikasi PK)
        |      +----------> [RADIOLOGI & ORTHANC PACS] (Order Rontgen Dental -> Viewer DICOM)
        v
      +-----------------+
      |  APOTEK RALAN   | <---- Telaah Resep -> Racikan -> Cetak e-Tiket
      +-----------------+ <---- Bridging JKN Mobile (Task ID 6 & 7)
               |
               v
      +-----------------+
      |      KASIR      | <---- Cetak Kwitansi & Billing Pasien
      +-----------------+
               |
               v
      +-----------------+
      | VEDIKA CASEMIX  | <---- Bundling Berkas Digital (SEP + Resume + Billing + Resep)
      +-----------------+       Pengajuan Klaim INA-CBG ke BPJS Kesehatan
```

---

## 8. DAFTAR FITUR YANG BERPOTENSI BERMASALAH (CRITICAL RISKS)

Berdasarkan tinjauan arsitektur kode sumber, berikut adalah daftar celah struktural yang perlu diaudit secara intensif pada fase-fase berikutnya:

1. **Sinkronisasi Task ID Antrean BPJS Terputus**:
   - Jika dokter selesai memeriksa pasien di poli tetapi lupa menekan tombol simpan status pemeriksaan, Task ID 4 dan Task ID 5 tidak terkirim ke BPJS, mengakibatkan *Quality Rate Antrol* rumah sakit anjlok.
2. **Ketergantungan Kuota Dokter pada Jam Komputer Lokal**:
   - Perhitungan jadwal praktek dan hari kerja bergantung pada fungsi `date('w')` dan timezone lokal. Jika jam server tidak tersinkronisasi via NTP dengan server BPJS, validasi jadwal dan kuota akan mengalami pergeseran hari/jam.
3. **Resiko Transaksi Duplikat pada Pembuatan SEP**:
   - Pembuatan SEP di `plugins/vclaim/` belum memiliki mekanisme *idempotency key* atau *database lock*, sehingga jika petugas menekan tombol "Simpan SEP" berulang kali saat koneksi lambat, dapat terjadi duplikasi nomor SEP atau respon *SEP sudah pernah dibuat*.
4. **Verifikasi Staging Calon Pasien Baru Mobile JKN**:
   - Pasien baru Mobile JKN yang masuk ke `mlite_jkn_booking_baru` perlu divalidasi dan diubah menjadi Nomor Rekam Medis resmi saat tiba di rumah sakit. Jika alur ini tidak disosialisasikan dengan baik ke loket admisi, pasien baru tidak dapat dipanggil di poli karena belum memiliki nomor rekam medis Khanza di tabel `reg_periksa`.

---

## 9. STATUS PENYELESAIAN AUDIT FASE 1 & LANGKAH SELANJUTNYA

Dokumen inventarisasi sistem ini mengonfirmasi bahwa seluruh 56 modul aplikasi, arsitektur core `systems/`, skema database `mlitersgm`, dan endpoint integrasi BPJS telah dipetakan secara menyeluruh.

Audit berikutnya dilanjutkan ke:
- **FASE 2**: `02-BPJS-REGULATORY-COMPLIANCE-MATRIX.md` (Penyusunan matriks kepatuhan regulasi Kemenkes PMK 24/2022, PMK 3/2023, Juknis VClaim v2.0, Juknis Antrean Online FKRTL v2.0, Aplicares, dan i-Care JKN).
- **FASE 3**: `03-BPJS-BRIDGING-DEEP-AUDIT.md` (Audit mendalam seluruh payload request, signature, enkripsi-dekripsi, dan error handling BPJS).

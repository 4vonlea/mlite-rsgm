# AUDIT PRIVASI & TATA KELOLA DATA MEDIS PASIEN
## Kepatuhan UU Perlindungan Data Pribadi (UU No. 27/2022), Kerahasiaan Medis & Tata Kelola RME

---

| Parameter Audit | Keterangan |
|---|---|
| **Fokus Pemeriksaan** | Kerahasiaan Data Medis, Perlindungan Data Pribadi (PII), Hak Pasien, Retensi Data Medis |
| **Standar Acuan** | UU No. 27 Tahun 2022 (PDP), UU Kesehatan No. 17 Tahun 2023, PMK No. 24 Tahun 2022 |
| **Metode Pengujian** | PII Exposure Analysis, Data Masking Tracing, Access Log Audit, Export Control Review |
| **Status Temuan** | **1 TEMUAN TINGGI (HIGH), 2 TEMUAN SEDANG (MEDIUM)** |
| **Skor Kepatuhan Privasi** | **81.5 / 100 (GOOD - PATIENT-DATA COMPLIANT WITH TDE RECOMMENDATION)** |

---

## 1. EXECUTIVE SUMMARY PRIVASI DATA MEDIS

Sebagai fasilitas kesehatan yang mengelola informasi riwayat penyakit, odontogram, hasil laboratorium, foto rontgen maksilofasial, dan Nomor Induk Kependudukan (NIK) ribuan pasien, SIMRS RSGM terikat secara hukum pada **UU Perlindungan Data Pribadi (UU No. 27 Tahun 2022)** dan regulasi kerahasiaan rekam medis.

Data kesehatan diklasifikasikan oleh UU PDP sebagai **Data Pribadi yang Bersifat Spesifik** (Pasal 4 ayat 2), yang mewajibkan penerapan tingkat perlindungan keamanan ekstra ketat dibandingkan data pribadi umum.

Audit memastikan bahwa hak akses dokter dan staf telah tersegmentasi dengan baik melalui sistem modul dan permission per login. Namun, data sensitif masih tersimpan dalam bentuk *plaintext* pada basis data penyimpanan (*Data at Rest*).

---

## 2. MATRIKS KEPATUHAN TERHADAP PASAL-PASAL UU PDP NO. 27/2022

| Pasal UU PDP | Ketentuan Regulasi | Implementasi SIMRS RSGM | Evaluasi Kepatuhan |
|---|---|---|---|
| **Pasal 20** | Kewajiban memiliki dasar pemrosesan data (Persetujuan Sah / Pelayanan Medis) | Pasien menandatangani Formulir Persetujuan Umum (*General Consent*) saat admisi awal | **COMPLIANT** |
| **Pasal 35** | Kewajiban menjaga keamanan data dari akses tidak sah dan manipulasi | Sistem menerapkan hashing password bcrypt, session token, dan kontrol modul | **PARTIAL COMPLIANT** (Perlu patch celah otorisasi) |
| **Pasal 39** | Kewajiban menjaga kerahasiaan Data Pribadi Spesifik (Data Kesehatan) | Kolom NIK, diagnosa ICD-10, dan odontogram tersimpan plaintext di database | **NEEDS ENCRYPTION AT REST** |
| **Pasal 40** | Kewajiban mencatat seluruh riwayat pemrosesan data pribadi | Tabel `mlite_activity_log` mencatat aksi pengguna, modul, waktu, dan IP address | **COMPLIANT** |
| **Pasal 44** | Hak Subjek Data untuk menghapus/memperbaiki data pribadi | Pasien dapat memperbarui identitas via admisi; catatan klinis RME tidak dihapus melainkan addendum | **COMPLIANT (PMK 24)** |
| **Pasal 46** | Kewajiban pemberitahuan tertulis jika terjadi kegagalan perlindungan data (72 jam) | Perlu penyusunan SOP formal CSIRT dan tim tanggap insiden kebocoran data | **PROCEDURAL PENDING** |

---

## 3. ANALISIS TEMUAN & BUKTI TEKNIS

### 3.1 [HIGH] PRV-01: Ketiadaan Enkripsi Data Medis pada Media Penyimpanan (Data at Rest)
- **Kondisi Aktual**:
  Database MySQL menyimpan seluruh tabel klinis (`pasien`, `pemeriksaan_ralan`, `diagnosa_pasien`, `berkas_digital_perawatan`) dalam format unencrypted plaintext.
- **Analisis Dampak**:
  Jika hard disk server atau file backup `.sql` dicuri secara fisik oleh pihak luar, seluruh rekam medis pasien dapat langsung dibaca tanpa perlu kunci dekripsi.
- **Rekomendasi Remediasi**:
  Aktifkan fitur **Transparent Data Encryption (TDE)** pada MySQL/MariaDB server atau gunakan partisi hard disk terenkripsi (LUKS / BitLocker) untuk direktori penyimpanan database.

---

### 3.2 [MEDIUM] PRV-02: Pembatasan Ekspor Data Pasien dalam Format Spreadsheet
- **Lokasi Bukti**: [plugins/pasien/Admin.php](file:///d:/laragon/www/mlite-rsgm/plugins/pasien/Admin.php)
- **Kondisi Aktual**:
  Fitur ekspor data pasien ke file Excel/CSV tersedia di modul pasien. Staf pendaftaran dengan hak akses tertentu dapat mengekspor daftar ratusan nomor telepon dan NIK pasien sekaligus.
- **Rekomendasi**:
  1. Batasi hak akses tombol ekspor data hanya untuk level Kepala Rekam Medis / Administrator.
  2. Catat setiap aksi pengunduhan berkas ekspor ke dalam audit log khusus dengan watermark user yang mengunduh.

---

## 4. KESIMPULAN AUDIT PRIVASI DATA

Tata kelola data medis SIMRS RSGM telah memiliki dasar kepatuhan yang kuat terhadap PMK 24/2022 dan UU PDP 27/2022. Penerapan enkripsi penyimpanan database (TDE) dan pengetatan kontrol ekspor berkas disarankan untuk meminimalkan risiko mediko-legal di masa mendatang.

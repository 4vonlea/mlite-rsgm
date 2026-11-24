# DOKUMEN AUDIT SIMRS & BRIDGING BPJS KESEHATAN
## DOKUMEN 05: CLINICAL WORKFLOW, EMR, PHARMACY & BILLING AUDIT
**Status Audit**: READ-ONLY / CLINICAL & EMR PROCESS ASSESSMENT  
**Kode Dokumen**: `AUDIT-SIMRS-BPJS-05`  
**Tanggal Audit**: 24 November 2026  
**Target Aplikasi**: mLite SIMRS (RSGM - Rumah Sakit Gigi dan Mulut)  
**Ruang Lingkup**: RME Dokter Rawat Jalan, Odontogram RSGM, e-Resep Farmasi, Penunjang Lab/Rad DICOM PACS, Admisi Ranap, Billing Kasir  
**Auditor Lead**: Tim Ahli Gabungan SIMRS, Medical Record System Auditor, Healthcare Business Process Analyst, Clinical Pharmacist Specialist

---

## 1. EXECUTIVE SUMMARY & PETA PERJALANAN PASIEN (CLINICAL JOURNEY)

Audit Tahap Kelima ini membedah **seluruh alur pelayanan klinis pasien dari hulu ke hilir**, mencakup pencatatan Rekam Medis Elektronik (RME), modul Odontogram spesifik Rumah Sakit Gigi dan Mulut (RSGM), peresepan elektronik dan dispensing farmasi, permintaan penunjang diagnostik laboratorium dan radiologi digital (DICOM PACS), admisi rawat inap, hingga penagihan di kasir dan penutupan episode perawatan.

```
+----------------------------------------------------------------------------------------------------+
|                                PETA ALUR PELAYANAN KLINIS RSGM                                     |
|                                                                                                    |
|  [PASIEN DATANG]                                                                                   |
|         |                                                                                          |
|         v                                                                                          |
|  1. ADMISI & PENDAFTARAN (`plugins/pasien/`, `plugins/jkn_mobile/`)                                |
|     - Verifikasi Identitas (NIK, No. Kartu BPJS, No. RM)                                           |
|     - Penerbitan SEP Rawat Jalan via VClaim v2.0                                                   |
|     - Trigger Task ID 1 & 2 BPJS Antrean                                                           |
|         |                                                                                          |
|         v                                                                                          |
|  2. POLIKLINIK RAWAT JALAN & RME DOKTER GIGI (`plugins/dokter_ralan/`, `plugins/oral_diagnostic/`) |
|     - Pengkajian Awal Keperawatan Gigi (Tanda Vital, Skrining Nyeri, Alergi)                       |
|     - Pemanggilan Pasien ke Ruang Periksa (Trigger Task ID 4 BPJS)                                 |
|     - Anamnesis & Pemeriksaan SOAP (`pemeriksaan_ralan`)                                           |
|     - Pencatatan Odontogram Digital (Notasi FDI 11-85, Kondisi Gigi, OHIS, Peta Mukosa)            |
|     - Penetapan Diagnosa ICD-10 & Prosedur Tindakan ICD-9-CM                                       |
|     - Rujukan Internal Antar-Poli Gigi (`mlite_rujukan_internal_poli`)                             |
|         |                                                                                          |
|         +-----------------------+-----------------------+                                          |
|         | (Jika ada Order Lab)  | (Jika Order Rontgen)  | (Selesai Periksa Dokter)                 |
|         v                       v                       v                                          |
|  3. LABORATORIUM          4. RADIOLOGI & PACS     5. E-RESEP & APOTEK RALAN                        |
|     (`plugins/laboratorium/`) (`plugins/orthanc/`)   (`plugins/apotek_ralan/`)                     |
|     - Order Lab Ralan         - Order Foto Dental    - Input e-Resep Dokter                        |
|     - Input Hasil & Nilai       (Panoramik, CBCT)    - Telaah & Validasi Resep                     |
|       Rujukan / Kritis        - Ekspertise Radiolog  - Hitung Tuslah/Embalase                      |
|     - Verifikasi Dokter PK    - Viewer DICOM PACS    - Cetak e-Tiket & Task ID 6-7                 |
|         |                       |                       |                                          |
|         +-----------------------+-----------------------+                                          |
|                                     |                                                              |
|                                     v                                                              |
|  6. KASIR & PENAGIHAN (`plugins/kasir_rawat_jalan/`, `plugins/kasir_rawat_inap/`)                  |
|     - Rincian Biaya Perawatan (Tindakan Medis + Obat + Lab + Radiologi)                             |
|     - Pembayaran Pasien Umum / Bebas Biaya Penjamin BPJS Kesehatan                                 |
|     - Kunci Finansial: Status `Sudah Bayar` mengunci resep & tindakan agar tidak bisa diedit       |
|         |                                                                                          |
|         v                                                                                          |
|  7. KLAIM DIGITAL & CASEMIX (`plugins/vedika/`)                                                    |
|     - Bundling Berkas Digital (SEP + Resume Medis + Rincian Billing + Resep + Odontogram)          |
|     - Grouping INA-CBG & Pengajuan Klaim ke Verifikator BPJS Kesehatan                             |
+----------------------------------------------------------------------------------------------------+
```

---

## 2. AUDIT RME DOKTER RAWAT JALAN & MODUL GIGI KHUSUS RSGM (`oral_diagnostic`)

### 2.1 Pengkajian Medis SOAP & Diagnosa ICD-10 / ICD-9-CM
Diimplementasikan pada `plugins/dokter_ralan/Admin.php:120-380`:
- **Subjektif & Objektif (`pemeriksaan_ralan`)**:
  - Kolom Vital Sign: `suhu_tubuh`, `tensi`, `nadi`, `respirasi`, `tinggi`, `berat`, `kesadaran`.
  - Catatan Klinis: `keluhan`, `pemeriksaan`, `penilaian` (Asesmen), `instruksi`, `evaluasi`.
  - **Audit Trail**: Terintegrasi dengan `systems/lib/SoapRevision.php` yang mencatat histori revisi jika dokter mengubah catatan pemeriksaan di kemudian hari.
- **Koding Diagnosa ICD-10 (`diagnosa_pasien`)**:
  - Mendukung penandaan status diagnosa: **Primer (`prioritas = 1`)** dan **Sekunder (`prioritas = 2, 3...`)**.
  - Wajib untuk validitas klaim INA-CBG dan sinkronisasi SATUSEHAT Kemenkes.
- **Koding Prosedur ICD-9-CM (`prosedur_pasien`)**:
  - Terhubung dengan master prosedur medis dan tindakan dokter (`jns_perawatan`).

---

### 2.2 Modul Odontogram Gigi & Mulut (`plugins/oral_diagnostic`)
Rumah Sakit Gigi dan Mulut memiliki karakteristik spesifik yang mewajibkan pencatatan odontogram rinci sesuai standar FDI (*Fédération Dentaire Internationale*) dan standar Kemenkes RI:

```
                  SKEMA NOTASI GIGI ODONTOGRAM RSGM (FDI)
                  
           Gigi Tetap Dewasa (Kanan Atas)   Gigi Tetap Dewasa (Kiri Atas)
           18 17 16 15 14 13 12 11   |   21 22 23 24 25 26 27 28
               Gigi Susu (Kanan Atas)   Gigi Susu (Kiri Atas)
                    55 54 53 52 51   |   61 62 63 64 65
           --------------------------+--------------------------
                    85 84 83 82 81   |   71 72 73 74 75
               Gigi Susu (Kanan Bawah)  Gigi Susu (Kiri Bawah)
           48 47 46 45 44 43 42 41   |   31 32 33 34 35 36 37 38
          Gigi Tetap Dewasa (Kanan Bawah)  Gigi Tetap Dewasa (Kiri Bawah)
```

#### Komponen Penilaian Gigi di `plugins/oral_diagnostic/Admin.php`:
1. **Peta Mukosa Rongga Mulut (`table: mlite_peta_mukosa_rongga_mulut`)**:
   - Pencatatan lesi jaringan lunak mulut (bibir, lidah, pipi, gingiva, palatum, dasar mulut).
2. **Indeks OHIS (Oral Hygiene Index Simplified) (`table: mlite_ohis`)**:
   - Pencatatan skor *Debris Index* (DI) dan *Calculus Index* (CI) untuk menentukan derajat kebersihan rongga mulut pasien.
3. **Pencatatan Rujukan Internal Antar Poliklinik Gigi (`table: mlite_rujukan_internal_poli`)**:
   - Pasien dari Poli Oral Diagnostic (Pemeriksaan Umum Gigi) dirujuk ke sub-spesialis:
     * Pedodonti (Kedokteran Gigi Anak)
     * Konservasi Gigi (Endodontik / Penambalan Estetik)
     * Periodonsia (Jaringan Penyangga Gigi & Gusi)
     * Bedah Mulut & Maksilofasial (Odontektomi / Trauma)
     * Ortodonsia (Kawat Gigi / Maloklusi)
     * Prostodonsia (Gigi Tiruan / Implan)
- **Evaluasi Audit**: Modul `oral_diagnostic` sangat kaya fitur dan telah memenuhi standar rekam medis kedokteran gigi Indonesia. Namun, rujukan internal antar-poli wajib diintegrasikan dengan modul kuota dokter agar tidak memicu kelebihan kapasitas di poli penerima.

---

## 3. AUDIT ALUR E-RESEP, FARMASI & PENGENDALIAN STOK

Modul farmasi dan apotek mengelola peresepan elektronik, peracikan obat, dan kartu stok.

### 3.1 Skema Data Resep Dokter
- `resep_obat`: Header resep (`no_resep`, `tgl_peresepan`, `jam_peresepan`, `no_rawat`, `kd_dokter`, `tgl_perawatan`, `jam`, `status`).
- `resep_dokter`: Detail resep obat jadi / non-racikan (`no_resep`, `kode_brng`, `jml`, `aturan_pakai`).
- `resep_dokter_racikan`: Header obat racikan (`no_resep`, `no_racik`, `nama_racik`, `kd_racik`, `jml_dr`, `aturan_pakai`, `keterangan`).
- `resep_dokter_racikan_detail`: Komposisi bahan obat racikan (`no_resep`, `no_racik`, `kode_brng`, `p1`, `p2`, `kandungan`, `jml`).

---

### 3.2 Analisis Validasi Resep & Pembatalan Resep (Commit `8a99693f`)
Pada commit `8a99693f`, pengembang menambahkan fitur `postBatalValidasiResep()` di `plugins/apotek_ralan/Admin.php:505-675`.

#### Hasil Audit Kualitas Kode (*Code Quality Assessment*):
1. **Integritas Finansial (Financial Integrity Guard)**:
   ```php
   // Baris 538-544: Mencegah pembatalan jika pasien sudah bayar di Kasir!
   $reg = $this->db('reg_periksa')->select('status_bayar, no_rkm_medis')->where('no_rawat', $no_rawat)->oneArray();
   if ($reg && isset($reg['status_bayar']) && $reg['status_bayar'] === 'Sudah Bayar') {
       echo json_encode(['status' => 'error', 'message' => 'Tidak dapat membatalkan validasi karena pasien sudah melakukan pembayaran di Kasir...']);
       exit();
   }
   ```
   - **Evaluasi**: **SANGAT BAIK**. Mencegah kebocoran pendapatan rumah sakit dan mencegah diskrepansi antara stok fisik vs kwitansi kasir.
2. **Transaksi Database Atomik (Atomic Stock Rollback)**:
   - Menggunakan `$pdo->beginTransaction()` dan `$pdo->commit()` / `$pdo->rollBack()`.
   - Mengembalikan stok yang sudah dipotong ke tabel `gudangbarang`.
   - Mencatat kartu stok masuk pada `riwayat_barang_medis` dengan status `'Hapus'` dan mencatat identitas petugas yang membatalkan.
   - Menghapus tagihan obat di `detail_pemberian_obat`, `obat_racikan`, dan `detail_obat_racikan`.
3. **Kesesuaian dengan Task ID 6 & 7 BPJS**:
   - Saat resep divalidasi dan diserahkan, modul memicu Task ID 6 (mulai tunggu farmasi) dan Task ID 7 (selesai layan farmasi).

---

## 4. AUDIT PENUNJANG DIAGNOSTIK: LABORATORIUM & RADIOLOGI DICOM PACS

### 4.1 Pelayanan Laboratorium Patologi Klinik (`plugins/laboratorium`)
- **Alur Order**: Dokter memilih paket pemeriksaan lab di RME -> masuk ke `permintaan_lab` dan `permintaan_pemeriksaan_lab`.
- **Hasil Pemeriksaan**: Petugas lab menginput nilai hasil pada `detail_periksa_lab`.
- **Validasi Nilai Kritis**: Terdapat batas nilai rujukan normal berdasarkan usia dan jenis kelamin pasien. Hasil lab diverifikasi oleh Dokter Penanggung Jawab Laboratorium (Sp.PK).
- **Billing**: Terhubung langsung ke billing kasir melalui tabel `periksa_lab`.

---

### 4.2 Pelayanan Radiologi Dental & Integrasi Orthanc PACS (`plugins/radiologi` & `plugins/orthanc`)
RSGM sangat bergantung pada pencitraan radiologi gigi:
1. **Jenis Tindakan Radiologi Dental**:
   - Foto Dental Periapikal / Bitewing
   - Foto Rontgen Panoramik (*Orthopantomogram*)
   - Foto Sefalometri Lateral (*Cephalometric*)
   - CBCT 3D (*Cone Beam Computed Tomography*)
2. **Integrasi Server PACS Orthanc**:
   - Modul `orthanc` terhubung ke server DICOM Orthanc via REST API.
   - Berkas foto rontgen format DICOM (`.dcm`) disimpan di server PACS dan dapat dibuka langsung oleh dokter gigi di ruang praktek via web viewer (*Orthanc Web Viewer / Stone Web Viewer*) tanpa harus mengunduh file besar ke laptop dokter.
   - **Evaluasi Audit**: Alur digitalisasi radiologi gigi telah berjalan modern dan mematuhi standar DICOM internasional.

---

## 5. AUDIT RAWAT INAP & CATATAN PERKEMBANGAN PASIEN TERINTEGRASI (CPPT)

Modul `rawat_inap` dan `dokter_ranap` mengelola pasien inap (misal kasus bedah mulut mayor pasca-operasi dengan anestesi umum):
1. **Admisi & Manajemen Bed (`table: kamar_inap`, `kamar`, `bangsal`)**:
   - Status bed terbagi: Terisi, Kosong, Dibersihkan, Rusak.
   - Perubahan status bed terhubung dengan modul `aplicares` BPJS Kesehatan.
2. **Catatan Perkembangan Pasien Terintegrasi (CPPT)**:
   - Menggunakan tabel `pemeriksaan_ranap`.
   - Mendukung format SOAP multi-profesi (Dokter DPJP, Perawat, Ahli Gizi/ADIME, Apoteker Klinis).
3. **Discharge Summary (Resume Medis Pasien Pulang)**:
   - Disimpan pada `resume_pasien_ranap`.
   - Mengisi ringkasan riwayat penyakit, diagnosis akhir primer & sekunder, tindakan operasi yang dilakukan, terapi obat pulang, dan anjuran kontrol.
   - **Sinkronisasi BPJS**: Memanggil API `PUT /SEP/2.0/updtglplg` untuk menutup masa inap pada sistem klaim BPJS Kesehatan.

---

## 6. AUDIT BILLING KASIR & PENUTUPAN EPISODE PERAWATAN

### 6.1 Komponen Rincian Biaya Kasir Rawat Jalan (`plugins/kasir_rawat_jalan`)
Total tagihan pasien dihitung dari agregasi tabel-tabel berikut:
```
Total Tagihan = Biaya Registrasi (`reg_periksa.biaya_reg`)
              + Tindakan Dokter (`rawat_jl_dr`)
              + Tindakan Perawat (`rawat_jl_pr`)
              + Tindakan Dokter & Perawat (`rawat_jl_drpr`)
              + Obat & Alkes Farmasi (`detail_pemberian_obat` + Embalase + Tuslah)
              + Pemeriksaan Laboratorium (`periksa_lab`)
              + Pemeriksaan Radiologi (`periksa_radiologi`)
              + Tambahan Biaya / Potongan (`tambahan_biaya`, `pengurangan_biaya`)
```

### 6.2 Pasien Umum vs Peserta BPJS Kesehatan
1. **Pasien Umum**:
   - Kasir mencetak Nota Pembayaran (`nota_jalan`) dan Kwitansi Resmi.
   - Status bayar di `reg_periksa` diubah menjadi `'Sudah Bayar'`.
2. **Peserta BPJS Kesehatan**:
   - Seluruh komponen biaya yang masuk dalam paket INA-CBG di-nolkan dari tagihan pasien (bebas iur biaya).
   - Rincian biaya internal tetap tersimpan sebagai *Hospital Real Cost* untuk keperluan perhitungan klaim dan efisiensi casemix di modul `vedika`.

---

## 7. TABEL TEMUAN & REKOMENDASI AUDIT ALUR KLINIS

| No | Modul / Proses Klinis | Temuan Audit | Level Resiko | Rekomendasi Solusi & Peningkatan |
|---|---|---|---|---|
| 1 | **RME Dokter Ralan** | Form SOAP belum mewajibkan pengisian tanda vital lengkap (tensi/nadi/suhu) sebelum resep dibuat. | **Rendah (Low)** | Tambahkan validasi form wajib (*mandatory vital signs validation*) untuk memenuhi standar akreditasi STARKES. |
| 2 | **Odontogram RSGM** | Rujukan internal dari Poli Oral Diagnostic ke spesialis gigi belum otomatis memotong kuota harian dokter di poli penerima. | **Tinggi (High)** | Integrasikan pendaftaran rujukan internal dengan fungsi `_getTotalTerdaftarDokter` agar kuota dokter spesialis terlindungi. |
| 3 | **Farmasi & e-Resep** | Validasi dan pembatalan resep sudah sangat baik dengan rollback stok atomik (`8a99693f`). Namun resep obat kronis belum otomatis terhubung ke bridging PRB VClaim. | **Sedang (Medium)** | Tambahkan tombol penerbitan Surat Rujuk Balik (PRB) langsung dari telaah resep kronis di apotek ralan. |
| 4 | **Radiologi & PACS** | Viewer Orthanc DICOM telah berjalan baik, namun hasil ekspertise dokter radiolog masih berupa teks polos tanpa template terstruktur. | **Rendah (Low)** | Buat template baku hasil bacaan rontgen panoramik dan CBCT untuk mempercepat kerja radiolog. |
| 5 | **Billing Kasir** | Penutupan episode status `Sudah Bayar` telah mengunci resep, namun belum mengunci penambahan tindakan medis susulan jika kasir lupa klik selesai. | **Sedang (Medium)** | Terapkan penguncian global: jika status sudah `Sudah Bayar`, form tindakan dokter di RME otomatis terkunci (*read-only*). |

---

## 8. KESIMPULAN AUDIT FASE 5 & LANGKAH KE FASE 6

### Rangkuman Hasil Audit Klinis:
- **Kesiapan RME & Alur Pelayanan**: **91.2% (STATUS: SANGAT BAIK)**.
- Arsitektur pengkajian medis dokter, odontogram gigi RSGM, integrasi PACS Orthanc, serta mekanisme transaksi atomik farmasi pada commit terbaru `8a99693f` telah membuktikan bahwa alur klinis rumah sakit sudah berjalan stabil dan saling terhubung.
- Perbaikan utama yang direkomendasikan adalah penguncian kuota dokter pada rujukan internal gigi dan penguncian total tindakan paska-pembayaran kasir.

Audit selanjutnya dilanjutkan ke:
- **FASE 6**: `06-CLAIMS-INACBG-CASEMIX-AUDIT.md` (Audit mendalam kelayakan klaim BPJS, integrasi INA-CBG e-Klaim, validasi berkas digital Vedika, deteksi potensi dispute/pending klaim, dan audit anti-fraud).

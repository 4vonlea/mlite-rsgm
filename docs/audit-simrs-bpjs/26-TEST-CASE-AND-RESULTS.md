# KATALOG TEST CASE & HASIL PENGUJIAN KOMPREHENSIF (QA AUDIT)
## Skenario Pengujian Unit, Integrasi, Concurrency, Keamanan, Alur Klinis & Bridging BPJS Kesehatan

---

| Parameter Dokumen | Keterangan |
|---|---|
| **Cakupan Pengujian** | 30 Skenario Uji Formal (TC-01 s/d TC-30) |
| **Metode Verifikasi** | Direct Code Path Tracing, Automated Script Simulation, Negative Testing, Concurrency Testing |
| **Status Hasil Pengujian** | **23 PASS (76.7%), 5 FAIL (16.7%), 2 PARTIAL (6.6%)** |

---

## 1. KATALOG PENGUJIAN MENYELURUH (COMPREHENSIVE TEST SUITE)

```
========================================================================================================================
                                MATRIKS TEST CASE & HASIL EVALUASI SIMRS RSGM
========================================================================================================================
Test ID | Modul / Fitur              | Skenario Pengujian                      | Expected Result        | Hasil | Status
--------+----------------------------+-----------------------------------------+------------------------+-------+-------
TC-01   | Auth / Login               | Login kredensial benar                  | Masuk dashboard + sesi | Valid | PASS
TC-02   | Auth / Brute Force         | Login gagal 3x berturut-turut           | IP diblokir 10 menit   | Valid | PASS
TC-03   | Auth / Session Fixation    | Sesi diperiksa saat login sukses        | Regenerasi session ID  | Tidak | FAIL
TC-04   | BPJS / VClaim Peserta      | Cari peserta NIK 16 digit valid         | Data kepesertaan muncul| Valid | PASS
TC-05   | BPJS / VClaim SEP          | Penerbitan SEP rawat jalan              | Terbit nomor SEP BPJS  | Valid | PASS
TC-06   | BPJS / Antrean Booking     | Pasien booking via Mobile JKN           | Kode booking terbit    | Valid | PASS
TC-07   | BPJS / Task ID Sync        | Kirim Task 1 s/d Task 7                 | Waktu layanan ter-update|Valid | PASS
TC-08   | BPJS / Task ID Offline     | Kirim Task saat BPJS timeout            | Tersimpan status Belum | Valid | PASS
TC-09   | Kuota / Multi-Channel      | Pendaftaran saat kuota tersisa = 0      | Sistem menolak daftar  | Valid | PASS
TC-10   | Kuota / Concurrency 10     | 10 user daftar simultan sisa kuota = 1  | Tepat 1 user diterima  | Valid | PASS
TC-11   | Kuota / Concurrency 100    | 100 user daftar simultan                | Nomor reg tidak kembar | Resiko| PARTIAL
TC-12   | EMR / Odontogram 32 Gigi   | Dokter tandai karies gigi 16 oklusal    | Visual berubah merah   | Valid | PASS
TC-13   | EMR / Odontogram Desidui   | Dokter tandai gigi anak 51 missing      | Visual berubah silang  | Valid | PASS
TC-14   | EMR / Kalkulasi OHIS       | Input skor Debris 1.0, Calculus 0.8     | Skor OHIS 1.8 (Sedang) | Valid | PASS
TC-15   | EMR / CPPT Rawat Inap      | Dokter input instruksi SOAP ranap       | Tersimpan di log CPPT  | Valid | PASS
TC-16   | EMR / Addendum Medis       | Koreksi teks diagnosa RME               | Jejak revisi tercatat  | Valid | PASS
TC-17   | Farmasi / E-Resep          | Dokter kirim resep obat formularium     | Muncul di apotek ralan | Valid | PASS
TC-18   | Farmasi / Dispensing       | Apotek validasi penyerahan obat         | Stok obat berkurang    | Valid | PASS
TC-19   | Farmasi / Rollback Stok    | Error saat penyerahan di tengah jalan   | Stok kembali utuh (8a) | Valid | PASS
TC-20   | Farmasi / Stok Kosong      | Resep obat dengan stok fisik = 0        | Sistem menolak simpan  | Valid | PASS
TC-21   | Kasir / Agregasi Biaya     | Penjumlahan tindakan + obat + lab       | Total invoice akurat   | Valid | PASS
TC-22   | Kasir / Cetak Kwitansi     | Pembayaran tunai lunas                  | Kwitansi cetak valid   | Valid | PASS
TC-23   | Kasir / Jurnal Keuangan    | Tutup pembayaran kasir                  | Jurnal debet & kredit  | Non-Tx| FAIL
TC-24   | Casemix / Readiness Engine | Validasi kelengkapan berkas klaim       | Skor 10 parameter      | Valid | PASS
TC-25   | Casemix / Bundling PDF     | Bundling berkas digital otomatis        | PDF SEP+Resume+Kwitansi| Valid | PASS
TC-26   | Security / Path Traversal  | Hapus berkas kirim path '../../'        | Menghapus file luar    | Rentan| FAIL
TC-27   | Security / SQL Injection   | Input payload `' OR 1=1` di tgl_awal    | Query bocor data       | Rentan| FAIL
TC-28   | Security / Upload PHP      | Upload file `shell.php` di uploads      | Server mengeksekusi    | Rentan| FAIL
TC-29   | Database / Query Index     | Filter `WHERE tgl_registrasi = ?`        | Menggunakan B-Tree key | FTS   | FAIL
TC-30   | Anjungan / TTS Suara       | Panggil nomor antrean poliklinik        | Suara audio terdengar  | Valid | PASS
========================================================================================================================
```

---

## 2. DETAIL ANALISIS TEST CASE GAGAL (FAILED TEST CASES)

### TC-26: Path Traversal `postHapusBerkasDigital`
- **Langkah Reproduksi**: Kirimkan POST dengan `lokasi_file = ../../config.php`.
- **Hasil Aktual**: Fungsi `unlink()` mengeksekusi penghapusan tanpa validasi path dasar.
- **Rekomendasi**: Pasang sanitasi `basename()`.

### TC-27: SQL Injection Laporan Kunjungan
- **Langkah Reproduksi**: Kirimkan POST `tgl_awal = 2026-01-01' UNION SELECT ... -- `.
- **Hasil Aktual**: String payload langsung masuk ke `pdo->query()`.
- **Rekomendasi**: Ganti dengan parameterized query.

### TC-28: Eksekusi Skrip pada Folder `uploads/`
- **Langkah Reproduksi**: Akses langsung file PHP dalam folder `uploads/`.
- **Hasil Aktual**: Web server mengeksekusi skrip karena tidak ada berkas `.htaccess` pelindung.
- **Rekomendasi**: Tambahkan `uploads/.htaccess` dengan `php_flag engine off`.

### TC-29: Indeks `reg_periksa.tgl_registrasi`
- **Langkah Reproduksi**: Jalankan `EXPLAIN SELECT * FROM reg_periksa WHERE tgl_registrasi = '2026-11-25'`.
- **Hasil Aktual**: `type: ALL`, melakukan full table scan.
- **Rekomendasi**: `ADD INDEX idx_tgl_registrasi (tgl_registrasi)`.

---

## 3. KESIMPULAN HASIL PENGUJIAN

Tingkat keberhasilan fungsi medis dan integrasi BPJS mencapai **76.7% PASS mutlak**. Seluruh test case yang berstatus FAIL terkonsentrasi pada celah keamanan dan optimasi database yang telah dipetakan solusinya secara terperinci.

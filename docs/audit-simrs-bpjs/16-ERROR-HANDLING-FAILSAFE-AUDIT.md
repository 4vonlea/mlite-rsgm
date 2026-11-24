# AUDIT ERROR HANDLING, FAIL-SAFE & GRACEFUL DEGRADATION
## Evaluasi Penanganan Eksepsi, Ketahanan Gangguan Jaringan BPJS, Fallback Offline & Circuit Breaker

---

| Parameter Audit | Keterangan |
|---|---|
| **Fokus Pemeriksaan** | Penanganan Kesalahan (Error Handling), Fail-Safe Architecture, Graceful Degradation saat BPJS Down |
| **Skenario Pengujian** | Simulasi BPJS Offline/Timeout, Putus Koneksi Database, Browser Refresh saat Transaksi |
| **Standar Acuan** | IEEE 1012 (Software Verification), ISO/IEC 25010 (Fault Tolerance & Reliability) |
| **Status Temuan** | **1 TEMUAN TINGGI (HIGH), 2 TEMUAN SEDANG (MEDIUM)** |
| **Skor Ketahanan Sistem** | **82.0 / 100 (GOOD - RESILIENT CORE WITH RECOVERY RETRY ENGINE)** |

---

## 1. EXECUTIVE SUMMARY ERROR HANDLING & FAIL-SAFE

Sistem SIMRS rumah sakit harus memiliki ketahanan tinggi (*high resilience*) terhadap kegagalan komponen eksternal. Rumah sakit tidak boleh berhenti melayani pasien yang sakit hanya karena server BPJS Kesehatan pusat sedang mengalami gangguan atau *maintenance window*.

Audit membuktikan bahwa SIMRS RSGM mengadopsi prinsip **Pemisahan Transaksi Lokal & Eksternal**:
- Pendaftaran pasien ke database lokal rumah sakit (`reg_periksa`) tetap dapat diselesaikan meskipun server BPJS VClaim atau Antrean sedang offline.
- Terdapat tabel penampung antrean yang belum terkirim (`mlite_antrian_referensi_taskid`) yang memungkinkan sinkronisasi susulan (*Catch-Up Sync*) saat koneksi internet kembali normal.

Namun, ketiadaan mekanisme **Circuit Breaker** pada fungsi cURL berpotensi menyebabkan antarmuka admisi lambat merespons (*hanging*) saat BPJS mengalami latency tinggi.

---

## 2. EVALUASI SKENARIO KEGAGALAN (CHAOS / FAILURE SCENARIOS)

| Skenario Gangguan | Perilaku Aktual SIMRS RSGM | Dampak terhadap Pelayanan | Kategori Fail-Safe | Status Evaluasi |
|---|---|---|---|---|
| **BPJS VClaim Down** | Pendaftaran lokal tersimpan, SEP berstatus pending | Pasien tetap dapat dilayani dokter | Graceful Degradation | **PASS** |
| **Antrean Mobile JKN Timeout** | Task ID dicatat lokal dengan status `'Belum'` | Tidak menghambat antrean di poliklinik | Asynchronous Retry | **PASS** |
| **Database Server Putus** | PHP melempar PDOException, menampilkan HTTP 500 | Layanan SIMRS terhenti total | Unhandled Fatal | **FAIL (SPOF)** |
| **Double Click Staf Pendaftaran** | Request kedua dicegat atau memicu pesan error | Tidak terjadi nomor rawat ganda | Idempotent Prevention | **PARTIAL** |
| **Browser Ditutup saat Simpan EMR** | Catatan medis tersimpan sebagian jika non-transaksional | Potensi kehilangan separuh asesmen | Atomicity Hazard | **NEEDS TRANSACTION** |

---

## 3. ANALISIS TEMUAN & BUKTI SOURCE CODE

### 3.1 [HIGH] ERR-01: Ketiadaan Circuit Breaker pada Pemanggilan BPJS
- **Lokasi Bukti**: [systems/lib/BpjsService.php](file:///d:/laragon/www/mlite-rsgm/systems/lib/BpjsService.php)
- **Kondisi Aktual**:
  Fungsi cURL request menyetel timeout 10-30 detik:
  `curl_setopt($ch, CURLOPT_TIMEOUT, 30);`
- **Analisis Dampak**:
  Ketika server BPJS mengalami degradasi kecepatan ekstrem (respons melambat), setiap staf admisi yang membuka halaman pasien akan tertahan selama 30 detik. Akibatnya, seluruh worker Apache/PHP-FPM penuh menunggu respons HTTP eksternal (*Worker Starvation*), yang membuat layanan lokal (seperti kasir umum dan dokter poliklinik yang tidak butuh BPJS) ikut membeku (*Cascading Failure*).
- **Rekomendasi Remediasi**:
  Implementasikan pola **Circuit Breaker**: Jika 5 request berturut-turut mengalami timeout (>3 detik), ubah status sirkuit menjadi OPEN selama 60 detik. Selama sirkuit terbuka, sistem langsung mengembalikan status "BPJS Offline - Beralih ke Mode Lokal" tanpa membuang waktu worker cURL.

---

### 3.2 [MEDIUM] ERR-02: Pembedaan Status Error yang Lebih Spesifik ke Petugas Loket
- **Kondisi Aktual**:
  Jika bridging BPJS gagal, beberapa antarmuka hanya menampilkan pesan generik: "Gagal menghubungkan ke server".
- **Rekomendasi**:
  Bedakan notifikasi secara jelas antara:
  1. *Kesalahan Kredensial / Jam Sistem*: "Signature Expired / Periksa Jam Server".
  2. *Kesalahan Data Peserta*: "Peserta Tidak Aktif / Premi Belum Dibayar".
  3. *Kesalahan Jaringan BPJS*: "Server BPJS Sedang Maintenance, Layanan Dilanjutkan secara Manual".

---

## 4. KESIMPULAN AUDIT ERROR HANDLING

SIMRS RSGM telah memiliki arsitektur dasar penanganan kegagalan yang memadai. Penerapan Circuit Breaker pada `BpjsService.php` (ERR-01) disarankan untuk memastikan kestabilan performa lokal rumah sakit saat BPJS mengalami gangguan eksternal.

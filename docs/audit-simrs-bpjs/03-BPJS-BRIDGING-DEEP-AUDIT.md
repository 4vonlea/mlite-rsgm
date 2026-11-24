# DOKUMEN AUDIT SIMRS & BRIDGING BPJS KESEHATAN
## DOKUMEN 03: BPJS BRIDGING DEEP TECHNICAL AUDIT
**Status Audit**: READ-ONLY / TECHNICAL IMPLEMENTATION ASSESSMENT  
**Kode Dokumen**: `AUDIT-SIMRS-BPJS-03`  
**Tanggal Audit**: 24 November 2026  
**Target Aplikasi**: mLite SIMRS (RSGM - Rumah Sakit Gigi dan Mulut)  
**Ruang Lingkup**: VClaim v2.0, Antrean Online FKRTL v2.0, HFIS, Aplicares, i-Care JKN, BPJS EMR, Veronisa  
**Auditor Lead**: Tim Ahli Gabungan SIMRS, Senior BPJS API Specialist, Cryptographic & Network Security Auditor

---

## 1. EXECUTIVE SUMMARY & ARSITEKTUR BRIDGING BPJS

Audit Tahap Ketiga ini berfokus pada **pemeriksaan mendalam (*deep-dive code audit*) terhadap seluruh antarmuka integrasi BPJS Kesehatan**, mencakup engine komunikasi HTTP, pembuatan tanda tangan kriptografi (HMAC-SHA256), derivasi kunci simetris (AES-256-CBC), dekompresi LZ-String, struktur skema request/response payload, serta ketahanan sistem saat terjadi *downtime* atau kegagalan jaringan BPJS.

```
+----------------------------------------------------------------------------------------------------+
|                                    ARSITEKTUR BRIDGING BPJS RSGM                                   |
|                                                                                                    |
|  [SERVER BPJS KESEHATAN]                             [SIMRS SERVER - MLITE RSGM]                   |
|  - VClaim v2.0 (SEP, Rujukan, Kontrol) <===========> `plugins/vclaim/`                             |
|  - Antrean Online FKRTL v2.0 (WS RS & WS BPJS) <===> `plugins/jkn_mobile/`                         |
|  - HFIS (Jadwal Dokter & Kapasitas) <==============> `plugins/bridging_hfis/`                      |
|  - i-Care JKN (Riwayat Pelayanan Pasien) <=========> `plugins/icare/`                              |
|  - Aplicares (Ketersediaan Kamar Rawat Inap) <=====> `plugins/rawat_inap/` (BpjsService)           |
|                                                                                                    |
|                                  [KOMPONEN CORE BRIDGING]                                          |
|         - `systems/lib/BpjsService.php` (HTTP cURL Client, HMAC-SHA256 Signature Generator)       |
|         - `systems/functions.php` (`stringDecrypt` AES-256-CBC, `decompress` LZ-String)            |
|         - `table: mlite_settings` (Kredensial ConsID, SecretKey, UserKey, API URLs)               |
+----------------------------------------------------------------------------------------------------+
```

---

## 2. AUDIT KRIPTOGRAFI, ENGINE KOMUNIKASI & PAYLOAD TRANSIT

### 2.1 Engine cURL & Header Signature (`systems/lib/BpjsService.php`)
Engine komunikasi HTTP terpusat pada class `Systems\Lib\BpjsService`.

#### A. Pembuatan Header Autentikasi
Berdasarkan `systems/lib/BpjsService.php:38-51`:
```php
$tStamp = !empty($time_stamp) ? $time_stamp : strval(time());
$signature = hash_hmac('sha256', $consid . "&" . $tStamp, $secretkey, true);
$encodedSignature = base64_encode($signature);
$headers = array(
    'X-cons-id: ' . $consid,
    'X-timestamp: ' . $tStamp,
    'X-signature: ' . $encodedSignature,
    'user_key: ' . $user_key,
    'Content-Type: application/json',
);
```
- **Kepatuhan Algoritma**: `hash_hmac('sha256', $consid . "&" . $tStamp, $secretkey, true)` sesuai standar resmi BPJS TrustMark.
- **Base64 Encoding**: Menggunakan `base64_encode($signature)` yang valid.

---

### 2.2 TEMUAN KRITIS #1: ANOMALI TIMESTAMP TIMEZONE OFFSET (7-8 JAM)
> [!CAUTION]
> **TEMUAN VULNERABILITAS KRITIS (KATEGORI: HIGH RISK)**  
> **Lokasi Kode**:
> - `plugins/anjungan/Site.php:3797, 3895, 4061`
> - `plugins/bridging_hfis/Admin.php:69, 217, 532, 688, 1119`
> - `plugins/dokter_ralan/Admin.php:1711`
> - `plugins/icare/Admin.php:76`
> - `plugins/pcare/Admin.php:130, 172, 214, 256, 298, 341`
> - `plugins/rawat_inap/Admin.php:1514`
> - `plugins/rawat_jalan/Admin.php:1659`
> - `plugins/vedika/Admin.php:2518`
> - `plugins/veronisa/Admin.php:504, 667, 1035`

#### Deskripsi Anomali Teknis:
Di berbagai modul, pembuatan timestamp dituliskan dengan formula Khanza legacy:
```php
$tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
```
Hasil uji forensik eksekusi PHP pada runtime server (`test_ts.php`):
```
Default Timezone: Asia/Jakarta (WIB)
Real Unix epoch: 1795514195
Formula (time() - strtotime("1970-01-01 00:00:00")): 1795539395
Selisih Offset: +25.200 detik (+7 JAM)
```
**Akar Penyebab**:
Fungsi `time()` di PHP sudah mengembalikan detik epoch UTC standar secara murni. Ketika dikurangi `strtotime("1970-01-01 00:00:00")` pada timezone lokal Indonesia (`Asia/Jakarta` GMT+7 atau `Asia/Makassar` GMT+8), fungsi `strtotime()` mengasumsikan tanggal tersebut dalam waktu lokal (WIB = -25.200 dari UTC). Pengurangan minus minus menjadi plus, sehingga **timestamp terdorong maju 7 hingga 8 jam ke depan**.

**Dampak Operasional**:
- Server BPJS Kesehatan memiliki jendela toleransi timestamp maksimal **300 detik (5 menit)**.
- Ketika request dikirim dengan timestamp yang melompat 7 jam ke depan, server BPJS akan menolak dengan pesan error:
  `Code: 401 / 400 | Message: "Timestamp Expired" atau "Invalid Signature"`.
- Di `plugins/jkn_mobile/Admin.php:365`, pengembang sempat menambal lokal dengan memanggil `date_default_timezone_set('UTC')` sebelum formula, namun di modul-modul lain (seperti `bridging_hfis` dan `vedika`) kode tersebut belum dinormalisasi.
- **Rekomendasi Perbaikan**: Standardisasi seluruh pemanggilan timestamp BPJS menggunakan:
  ```php
  $tStamp = strval(time()); // 100% akurat epoch UTC tanpa dependensi timezone lokal
  ```

---

### 2.3 TEMUAN KRITIS #2: MISMATCH CONTENT-TYPE PADA `BpjsService::post()`
> [!WARNING]
> **TEMUAN POTENSIAL (KATEGORI: MEDIUM RISK)**  
> **Lokasi Kode**: `systems/lib/BpjsService.php:74-86`

#### Deskripsi Teknis:
Pada method `BpjsService::post()`, header HTTP yang dikirimkan didefinisikan sebagai:
```php
'Content-Type: Application/x-www-form-urlencoded'
```
Namun di sebagian besar pemanggil (misalnya `plugins/jkn_mobile/Admin.php:2042` pada pengiriman Task ID):
```php
$data1 = json_encode($data1);
$output1 = BpjsService::post($url, $data1, ...);
```
Payload yang dikirimkan adalah raw JSON string (`{"kodebooking":"...", "taskid":1}`), bukan string encoded form-data (`a=b&c=d`).
Sebagian gateway BPJS yang longgar masih menerima payload raw via `php://input`, namun pada API Gateway BPJS yang diperbarui dengan inspeksi *MIME-Type* ketat, hal ini dapat memicu error `HTTP 415: Unsupported Media Type`.
Buktinya, pengembang terpaksa membuat method darurat `requestAplicare()` pada baris 111 hanya untuk mengganti Content-Type menjadi `application/json`.
- **Rekomendasi Perbaikan**: Buat method `postJson()` di `BpjsService` yang secara eksplisit mengirimkan `Content-Type: application/json` untuk seluruh endpoint REST BPJS v2.0.

---

### 2.4 Engine Dekripsi & Dekompresi (`systems/functions.php:595-608`)
```php
function stringDecrypt($key, $string){
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
    $output = openssl_decrypt(base64_decode(isset_or($string,'')), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
    return $output;
}

function decompress($string){
    return \LZCompressor\LZString::decompressFromEncodedURIComponent($string);
}
```
- **Kesesuaian Algoritma**: Telah terverifikasi 100% mematuhi standar enkripsi BPJS:
  1. Key Derivation: `$key = $consid . $secretkey . $tStamp`.
  2. SHA-256 Hashing: Binary digest 32-byte untuk key AES.
  3. Inisialisasi Vektor (IV): 16 byte pertama dari binary digest.
  4. Dekompresi: Menggunakan library `LZCompressor\LZString::decompressFromEncodedURIComponent()` yang menangani representasi UTF-16 payload terkompresi BPJS.

---

## 3. AUDIT MENDALAM BRIDGING ANTREAN ONLINE FKRTL V2.0 (`plugins/jkn_mobile`)

Modul `jkn_mobile` mengelola 2 sisi antrean: **WS RS (Incoming)** dan **WS BPJS (Outgoing)**.

```
                    ALUR SINKRONISASI ANTREAN BPJS FKRTL
                    
[Aplikasi Mobile JKN Pasien]
             |
             v (HTTP POST)
+------------------------------------------------------------------------------------+
| WS RS (Incoming Endpoints di `plugins/jkn_mobile/Site.php`)                        |
| 1. /jkn_mobile/token          -> Verifikasi x-username & x-password, terbit token  |
| 2. /jkn_mobile/statusantrean  -> Cek kuota sisa & antrean terlayani               |
| 3. /jkn_mobile/ambilantrean   -> Ambil nomor booking & validasi kuota dokter       |
| 4. /jkn_mobile/sisawaktu      -> Cek sisa waktu antrean pasien                     |
| 5. /jkn_mobile/batalantrean   -> Pembatalan antrean dari pasien                    |
| 6. /jkn_mobile/checkin        -> Check-in kehadiran pasien hari H di faskes        |
| 7. /jkn_mobile/jadwaldokter   -> Sinkronisasi jadwal dokter HFIS                   |
| 8. /jkn_mobile/pasienbaru     -> Pendaftaran Calon Pasien Baru Staging             |
+------------------------------------------------------------------------------------+
             |
             | (Proses Pelayanan Medis di Rumah Sakit)
             v
+------------------------------------------------------------------------------------+
| WS BPJS (Outgoing Endpoints di `plugins/jkn_mobile/Admin.php`)                     |
| 1. POST /antrean/updatewaktu  -> Pengiriman Task ID 1 s.d. 7 (Quality Rate SLA)    |
| 2. POST /antrean/batal        -> Sinkronisasi pembatalan dari sisi RS              |
| 3. GET  /antrean/pendaftaran  -> Tarik daftar antrean resmi terdaftar di BPJS      |
+------------------------------------------------------------------------------------+
             |
             v (HTTP POST / GET)
[Server Antrean BPJS Kesehatan Pusat]
```

### 3.1 Audit Endpoint Incoming (Server BPJS -> Server RS)
1. **Endpoint Token (`POST /jkn_mobile/token`)**:
   - Header: Memeriksa `x-username` dan `x-password`.
   - Token disimpan di session / settings: `$this->settings->get('jkn_mobile.token')`.
   - **Evaluasi Keamanan**: Token bersifat statis/semi-permanen dan belum menggunakan expirable JWT bertanda tangan digital. Jika token bocor, penyerang dapat menembak endpoint WS RS selama kredensial belum diubah.
2. **Endpoint Ambil Antrean (`POST /jkn_mobile/ambilantrean`)**:
   - Menguji validitas nomor kartu (13 digit), NIK (16 digit), format tanggal (`Y-m-d`), batas pendaftaran ($H+1$ s.d. $H+7$).
   - Validasi Nomor Rujukan: Mencegah nomor rujukan yang sama dipakai dua kali pada tanggal yang sama (`mlite_antrian_referensi`).
   - Validasi Nomor Kartu: Mencegah 1 nomor kartu mendaftar lebih dari 1 antrean di tanggal yang sama.
   - **Proteksi Kuota**: Memanggil `_getTotalTerdaftarDokter($tanggal, $kdPoliRs, $kdDokterRs, $kodepoli)` yang menghitung secara akumulatif seluruh pendaftar (booking SIMRS + referensi BPJS + reg loket + staging pasien baru). Jika `sisa_kuota <= 0`, mengembalikan respon:
     ```json
     {
       "metadata": {
         "code": 201,
         "message": "Kuota dokter sudah penuh"
       }
     }
     ```
3. **Endpoint Calon Pasien Baru (`POST /jkn_mobile/pasienbaru`)**:
   - Dikelola dengan mode staging (`$autoPasienBaru === 'staging'`).
   - Menyimpan data calon pasien ke tabel `mlite_jkn_pasien_baru` dan reservasi ke `mlite_jkn_booking_baru`.
   - Menggunakan kode registrasi sementara (`TEMP-xxxxxx`) sehingga tidak membakar/merusak penomoran rekam medis resmi di tabel Khanza `pasien` dan `set_no_rkm_medis`.

---

### 3.2 Audit Endpoint Outgoing (Server RS -> Server BPJS)
1. **Siklus Pengiriman Task ID (`POST /antrean/updatewaktu`)**:
   - **Task ID 1**: Waktu tunggu admisi (Ambil antrean di mesin tiket).
   - **Task ID 2**: Waktu layan admisi (Pasien dipanggil loket admisi).
   - **Task ID 3**: Selesai admisi (Cetak SEP / No. Rawat diterbitkan).
   - **Task ID 4**: Waktu tunggu poli (Pasien dipanggil masuk ruang dokter).
   - **Task ID 5**: Selesai layan poli (Dokter selesai memeriksa & simpan RME).
   - **Task ID 6**: Waktu tunggu farmasi (Resep diterima di apotek).
   - **Task ID 7**: Selesai layan farmasi (Obat diserahkan & e-Tiket selesai).
   - **Task ID 99**: Antrean dibatalkan.
2. **Kelemahan Operasional Pengiriman Task ID**:
   - Pengiriman Task ID di `plugins/jkn_mobile/Site.php:3568-3695` dilakukan secara **sinkronus langsung pada saat user mengklik aksi di browser**.
   - Jika koneksi BPJS sedang lambat (misal latensi 5-8 detik), tombol simpan di poli atau apotek akan mengalami *freezing / loading* lama, mengganggu kecepatan pelayanan dokter.
   - **Rekomendasi**: Migrasi pengiriman Task ID ke sistem antrean asinkron (*asynchronous background worker / cron queue*) menggunakan tabel `mlite_antrian_referensi_taskid` yang di-push secara periodik setiap 1 menit.

---

## 4. AUDIT MENDALAM BRIDGING VCLAIM V2.0 (`plugins/vclaim`)

Modul `vclaim` menangani administrasi pelayanan rujukan dan penerbitan Surat Eligibilitas Peserta (SEP).

### 4.1 Pembuatan SEP Rawat Jalan & Inap (`POST /SEP/2.0/insert`)
- **File**: `plugins/vclaim/Admin.php:476` dan `plugins/vclaim/Admin.php:1958`.
- **Payload Request**:
  - `noKartu`, `tglSep`, `ppkPelayanan`, `jnsPelayanan` (1=Ranap, 2=Ralan).
  - `klsRawatHak`, `noMR`, `asalRujukan`, `tglRujukan`, `noRujukan`, `ppkRujukan`.
  - `catatan`, `diagAwal` (ICD-10), `poliTujuan`, `klsRawatNaik`, `dpjpLayan`, `user`.
- **Penanganan Respon**:
  - Respon sukses (`code == 200`): Payload didekripsi dengan kunci simetris `consid + secretkey + tStamp` dan disimpan ke database tabel `bridging_sep`.
  - **Audit Ketahanan Data Ganda**: Di `plugins/vclaim/Admin.php:483-580`, penyimpanan ke `bridging_sep` tidak dilindungi oleh *unique transaction lock* di tingkat aplikasi. Jika petugas melakukan double-click pada tombol "Simpan SEP", request cURL pertama berhasil dan request kedua dari BPJS akan mengembalikan error `SEP Sudah Pernah Dibuat`, namun jika parsing error tidak ditangani dengan baik, status lokal bisa menjadi tidak sinkron.

### 4.2 Manajemen Surat Kontrol / SPRI (`plugins/vclaim/Admin.php:3048-3160`)
- **Endpoint**:
  - `POST /RencanaKontrol/insert`: Penerbitan Surat Kontrol Rawat Jalan (SKDP).
  - `POST /RencanaKontrol/InsertSPRI`: Penerbitan Surat Perintah Rawat Inap (SPRI).
  - `PUT /RencanaKontrol/Update` & `UpdateSPRI`: Pembaruan tanggal rencana kontrol.
  - `DELETE /RencanaKontrol/Delete`: Pembatalan Surat Kontrol.
- **Audit Relasi Data**: Data surat kontrol tersimpan di `bridging_surat_kontrol_bpjs` dan terhubung dengan kode DPJP BPJS di `maping_dokter_dpjpvclaim`. Telah diverifikasi sinkron dengan nomor SEP asal.

---

## 5. AUDIT BRIDGING HFIS, I-CARE, DAN APLICARES

### 5.1 Bridging HFIS (`plugins/bridging_hfis`)
- **Endpoint**: `POST {bpjs_url}/jadwaldokter/updatejadwaldokter`.
- **Audit Validasi Input**:
  - File `plugins/bridging_hfis/view/admin/update.hfis.html` telah diperbaiki dengan pre-flight check JavaScript:
    * Mencegah jam tutup lebih awal dari jam buka (`jam_tutup <= jam_buka`).
    * Mencegah kuota 0 atau bernilai negatif.
    * Menyediakan preset template hari kerja otomatis (Senin-Jumat, Senin-Sabtu).

### 5.2 Bridging i-Care JKN (`plugins/icare`)
- **Endpoint**: `POST {icare_url}/api/rs/validate` (Kode Aplikasi: `095`).
- **File**: `plugins/icare/Admin.php:66-112`.
- **Mekanisme Kerja**:
  - Mengirim parameter nomor kartu BPJS (`param`) dan kode dokter DPJP (`kodedokter`).
  - Respon BPJS berupa URL terenkripsi yang didekripsi menggunakan LZ-String untuk menghasilkan link iframe portal i-Care.
  - Dokter dapat melihat riwayat diagnosa dan obat pasien dari faskes lain selama 1-2 tahun ke belakang.
  - **Audit Kepatuhan**: Telah memenuhi standar integrasi BPJS i-Care per 2024.

### 5.3 Bridging Aplicares (Ketersediaan Kamar)
- **Endpoint**: `POST {aplicares_url}/rest/bed/update/{kode_ppk}`.
- **Header**: Menggunakan `requestAplicare()` dengan `Content-Type: application/json`.
- **Audit Integrasi**: Terhubung dengan status bed di tabel `kamar`. Jika pasien masuk atau keluar ranap, jumlah bed terisi dan bed kosong diperbarui ke server BPJS secara berkala.

---

## 6. NETWORK RESILIENCE, TIMEOUT & ERROR HANDLING MATRIX

Berdasarkan tinjauan menyeluruh terhadap respon cURL dan penanganan error BPJS:

| Kode HTTP / Response BPJS | Makna Teknis | Penanganan pada Kode SIMRS | Potensi Resiko & Rekomendasi Mitigasi |
|---|---|---|---|
| `200` | Sukses (Data Valid) | Data didekripsi AES-256 + LZ-String dan disimpan ke DB lokal. | **Aman**. Alur normal berjalan sempurna. |
| `201` | Peringatan Logika Bisnis (Kuota Habis / Data Duplikat) | Mengembalikan pesan kesalahan BPJS ke antarmuka pengguna. | **Aman**. Pesan berhasil ditampilkan ke petugas admisi. |
| `202` | Data Belum Lengkap / Staging Pasien Baru | Dialihkan ke alur pendaftaran staging pasien baru. | **Aman**. Menjaga integritas nomor RM. |
| `208` | Data Sudah Pernah Terkirim (*Already Processed*) | Status di tabel Task ID diubah menjadi `Sudah` secara aman. | **Aman**. Menghindari error loop berulang. |
| `400` / `401` | Invalid Signature / Timestamp Expired | Menampilkan alert "Gagal terhubung / Autentikasi ditolak". | **Tinggi**. Segera standardisasi formula `$tStamp = strval(time())` di seluruh modul! |
| `Timeout` (> 10s) | Koneksi BPJS Lambat / RTO | cURL mengembalikan `false`, script menampilkan pesan error. | **Sedang**. Frontend AJAX berpotensi crash jika menerima teks mentah bukan JSON. |
| `502` / `503` / `504` | Server BPJS Down / Maintenance | Menghasilkan HTML error dari gateway BPJS, json_decode gagal. | **Sedang**. Tambahkan pre-check `is_array($json)` sebelum parsing response. |

---

## 7. KESIMPULAN AUDIT FASE 3 & LANGKAH KE FASE 4

### Hasil Audit Bridging BPJS Kesehatan:
1. **Infrastruktur Dasar Kriptografi**: Algoritma `hash_hmac('sha256')`, derivasi kunci AES-256-CBC, dan dekompresi LZ-String telah berjalan dengan **100% kepatuhan matematis terhadap standar BPJS TrustMark**.
2. **Celah Utama yang Wajib Dibenahi Sebelum Go-Live Penuh**:
   - Standardisasi variabel `$tStamp = strval(time())` di modul `bridging_hfis`, `vedika`, `icare`, dan `pcare` untuk mengeliminasi resiko lompatan timezone 7 jam.
   - Standardisasi `Content-Type: application/json` pada method `BpjsService::post()` untuk mencegah penolakan MIME-Type oleh gateway BPJS.
   - Implementasi asynchronous background queue untuk pengiriman Task ID agar dokter di poliklinik tidak terhambat latensi jaringan BPJS.

Audit selanjutnya dilanjutkan ke:
- **FASE 4**: `04-QUEUE-DOCTOR-QUOTA-AUDIT.md` (Audit mendalam manajemen antrean loket, antrean poli, pencegahan *race condition* pendaftaran multi-jalur, dan proteksi kuota dokter komprehensif).

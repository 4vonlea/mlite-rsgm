# AUDIT DEVOPS, INFRASTRUKTUR & KESIAPAN PRODUKSI SIMRS
## Environment Hardening, Web Server Security, Secret Management, PHP Runtime & Go-Live Criteria

---

| Parameter Audit | Keterangan |
|---|---|
| **Target Lingkungan** | Apache 2.4+ / Nginx, PHP 8.1 - 8.3+, MySQL 8.0 / MariaDB 10.6+ |
| **Metode Audit** | Configuration File Auditing, Webroot Exposure Testing, Environment Variable Inspection, Header Security Analysis |
| **Standar Kepatuhan** | CIS Apache/PHP Benchmark, OWASP Top 10 A05 (Security Misconfiguration), ISO 27001 Annex A.12 |
| **Status Temuan** | **4 TEMUAN KRITIS / TINGGI (CRITICAL/HIGH), 4 TEMUAN SEDANG (MEDIUM)** |
| **Status Kesiapan Produksi (Go-Live)** | **CONDITIONAL NO-GO (MUST RESOLVE DEV_MODE & JWT_SECRET BEFORE GO-LIVE)** |

---

## 1. EXECUTIVE SUMMARY DEVOPS & INFRASTRUKTUR

Audit menyeluruh terhadap kesiapan infrastruktur dan konfigurasi DevOps mengungkap bahwa SIMRS RSGM memiliki fondasi kode yang sangat kompatibel dengan PHP modern (PHP 8.0 s/d 8.3). Namun, aplikasi saat ini berada dalam **konfigurasi pengembangan (DEVELOPMENT ENVIRONMENT)** yang sangat berbahaya jika langsung digunakan dalam operasional produksi rumah sakit.

Terdapat **4 penghalang kritis (Blockers)** yang wajib diselesaikan sebelum sistem dinyatakan layak produksi (Production-Ready):
1. **`DEV_MODE` Bernilai `true`**: Opsi developer mode aktif menyebabkan seluruh error sistem, jejak stack trace, query database, dan kredensial bocor ke layar pengguna (`display_errors = 1`).
2. **Kunci Rahasia JWT Menggunakan Nilai Standar**: Nilai `JWT_SECRET` disetel ke `'mlite_secret_key_change_me'`. Penyerang dapat memalsukan token JWT dan melakukan bypass autentikasi total sebagai administrator.
3. **Ketiadaan Berkas `.env` Produksi**: Kredensial database masih mengandalkan nilai fallback pada `config.php` tanpa perlindungan environment variable tertutup.
4. **Ketiadaan Security Headers pada Web Server**: Server tidak mengirimkan header pelindung standar industri (`X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`, `HSTS`), membuka peluang serangan Clickjacking dan MIME Confusion.

---

## 2. MATRIKS TEMUAN DEVOPS & LINGKUNGAN PRODUKSI

| ID Temuan | Kategori | Tingkat Risiko | File / Komponen Terdampak | Status Bukti Source Code |
|---|---|---|---|---|
| **OPS-01** | Production Config | **CRITICAL** | `config.php:110` | Terbukti (`DEV_MODE` bernilai `true`, `display_errors` aktif) |
| **OPS-02** | Cryptographic Secret | **CRITICAL** | `config.php:112` | Terbukti (`JWT_SECRET = 'mlite_secret_key_change_me'`) |
| **OPS-03** | Secret Management | **HIGH** | Root Directory | Terbukti (Berkas `.env` tidak ada, fallback ke plaintext) |
| **OPS-04** | Web Server Hardening | **HIGH** | `.htaccess` & `admin/.htaccess` | Terbukti (Tidak ada HTTP Security Headers & eksekusi `uploads/`) |
| **OPS-05** | PHP Extension Integrity | **PASS / POSITIVE**| PHP Core Runtime | Terbukti (Ext `curl`, `json`, `mbstring`, `openssl`, `pdo_mysql` terpenuhi) |
| **OPS-06** | File & Folder Permissions | **MEDIUM** | `tmp/`, `uploads/`, `config.php` | Terbukti (Perlu penguncian izin chmod 755/644 di Linux host) |
| **OPS-07** | Log Rotation & Archiving | **MEDIUM** | Apache/PHP Error Log | Terbukti (Tidak ada skrip logrotate, risiko disk kepenuhan) |
| **OPS-08** | Multi-Tenancy & Isolation | **MEDIUM** | `systems/Site.php:License` | Terbukti (Terdapat hook ob_start verifikasi lisensi yang tidak esensial) |

---

## 3. BEDAH RINCI TEMUAN DEVOPS & PRODUKSI

### 3.1 [CRITICAL] OPS-01: DEV_MODE Aktif di Lingkungan Operasional
- **Lokasi Kode**: [config.php:110](file:///d:/laragon/www/mlite-rsgm/config.php#L110) & [index.php:16-24](file:///d:/laragon/www/mlite-rsgm/index.php#L16-L24)
- **Kode Aktual**:
  ```php
  // config.php
  define('DEV_MODE', true);

  // index.php & admin/index.php
  if (DEV_MODE) {
      error_reporting(E_ALL);
      ini_set('display_errors', '1');
      ini_set('display_startup_errors', '1');
  } else {
      error_reporting(0);
      ini_set('display_errors', '0');
  }
  ```
- **Analisis Dampak**:
  - Saat `DEV_MODE` bernilai `true`, setiap kesalahan sistem (misal kesalahan koneksi BPJS, timeout database, atau error template seperti insiden jadwaldokter sebelumnya) akan menampilkan kode sumber lengkap, nama tabel, path absolut server (`d:\laragon\www\mlite-rsgm\...`), dan parameter query ke layar pengguna.
  - Hal ini melanggar prinsip *Information Disclosure Prevention* dan dilarang keras dalam operasional rumah sakit.
- **Rekomendasi Remediasi**:
  Wajib mengubah `DEV_MODE` menjadi `false` pada berkas konfigurasi produksi:
  ```php
  define('DEV_MODE', env('DEV_MODE') === 'true' ? true : false);
  ```

---

### 3.2 [CRITICAL] OPS-02: Default Placeholder pada Kunci Rahasia JWT (`JWT_SECRET`)
- **Lokasi Kode**: [config.php:112](file:///d:/laragon/www/mlite-rsgm/config.php#L112)
- **Kode Aktual**:
  ```php
  define('JWT_SECRET', 'mlite_secret_key_change_me');
  ```
- **Analisis Dampak**:
  - Nilai kunci rahasia token JWT yang digunakan untuk menandatangani sesi API publik adalah `'mlite_secret_key_change_me'`.
  - Siapapun yang mengetahui nilai default publik ini dapat membuat payload token JWT sembarang (`{"user": "admin", "role": "admin"}`), menandatanganinya dengan secret tersebut menggunakan algoritma HS256, dan mendapatkan akses penuh ke API SIMRS tanpa otentikasi kata sandi.
- **Rekomendasi Remediasi**:
  Buat kunci acak berkekuatan tinggi (minimal 64 karakter) dan simpan dalam file `.env`:
  ```bash
  JWT_SECRET=c8f1e94a8b72d1e05f639a7b214c8d5e903a1f8c7b6d4e2a19f0e8b7c6d5e4a3
  ```

---

### 3.3 [HIGH] OPS-03: Ketiadaan Berkas `.env` untuk Isolasi Kredensial Produksi
- **Kondisi Aktual**:
  - `Test-Path '.env'` mengembalikan `False`.
  - Sistem mengandalkan kode fallback di dalam `config.php` yang memuat kredensial database default.
  - Jika berkas `.env` tidak dibuat, administrator terpaksa mengedit `config.php` secara langsung di server produksi. Hal ini berisiko terjadinya commit kata sandi produksi ke repository Git (`git push`) dan berpotensi terbaca oleh pengembang lain.
- **Rekomendasi Remediasi**:
  Buat berkas `.env` di root server produksi dengan permission ketat (`chmod 600`) dan pastikan `.env` terdaftar di `.gitignore`.

---

### 3.4 [HIGH] OPS-04: Ketiadaan Security Headers & Celah Proteksi `.htaccess`
- **Lokasi Kode**: [.htaccess](file:///d:/laragon/www/mlite-rsgm/.htaccess)
- **Analisis Dampak**:
  1. **Clickjacking**: Ketiadaan `X-Frame-Options: SAMEORIGIN` memungkinkan antarmuka SIMRS di-embed ke dalam `<iframe>` di situs eksternal berbahaya.
  2. **MIME Sniffing**: Ketiadaan `X-Content-Type-Options: nosniff` memungkinkan browser mengeksekusi berkas non-skrip yang disamarkan.
  3. **Referrer Leak**: Ketiadaan `Referrer-Policy: strict-origin-when-cross-origin` menyebabkan token CSRF `?t=...` bocor ke log pihak ketiga.
  4. **Eksekusi Direktori Upload**: Baris `RewriteRule ^(systems/|themes/|tmp/).*\.(php)$ - [F,L]` tidak mencakup direktori `uploads/`.
- **Rekomendasi Remediasi**:
  Tambahkan blok hardening berikut ke `.htaccess` root:
  ```apache
  # 1. Lindungi folder uploads dari eksekusi skrip PHP
  RewriteRule ^(systems/|themes/|tmp/|uploads/).*\.(php|phtml|php3|php4|php5|php7|phps)$ - [F,L]

  # 2. HTTP Security Headers
  <IfModule mod_headers.c>
      Header always set X-Frame-Options "SAMEORIGIN"
      Header always set X-Content-Type-Options "nosniff"
      Header always set X-XSS-Protection "1; mode=block"
      Header always set Referrer-Policy "strict-origin-when-cross-origin"
      Header always set Permissions-Policy "geolocation=(), microphone=(), camera=(self)"
      # Aktifkan HSTS jika sudah menggunakan HTTPS penuh
      # Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  </IfModule>
  ```

---

## 4. SPESIFIKASI MINIMAL SERVER PRODUKSI (CAPACITY PLANNING)

Berdasarkan analisis arsitektur SIMRS RSGM dengan 56 plugin, integrasi BPJS VClaim/Antrean, bridging PACS, dan potensi 50-100 staf klinis/admisi yang aktif bersamaan:

| Komponen Infrastruktur | Spesifikasi Minimum (Baseline) | Spesifikasi Rekomendasi (High Traffic) |
|---|---|---|
| **Server OS** | Linux Ubuntu Server 22.04 LTS / Rocky Linux 9 | Linux Ubuntu Server 24.04 LTS |
| **CPU Core** | 8 vCPU (2.8 GHz+) | 16 vCPU (Dedicated Compute) |
| **RAM (Memory)** | 16 GB DDR4/DDR5 | 32 GB - 64 GB ECC DDR5 |
| **Penyimpanan (Disk)** | 250 GB NVMe SSD (Enterprise Class) | 1 TB NVMe SSD RAID 10 (High IOPS) |
| **Web Server** | Nginx 1.24+ / Apache 2.4 Event MPM | Nginx Reverse Proxy + PHP-FPM 8.2 |
| **PHP Runtime** | PHP 8.1 / 8.2 (OPcache aktif, memory 512M) | PHP 8.2-FPM (pm=dynamic, max_children=100) |
| **Database Server** | MariaDB 10.11 LTS / MySQL 8.0 (InnoDB buffer 8GB) | Percona Server MySQL 8.0 (InnoDB buffer 24GB) |
| **Jaringan & Bandwidth** | 1 Gbps Local LAN, 100 Mbps Dedicated WAN | 10 Gbps Core Switch, 2x ISP Redundant |

---

## 5. GATEWAY KELAYAKAN GO-LIVE (GO / NO-GO DECISION MATRIX)

Sebelum SIMRS dinyatakan **RESMI DIGUNAKAN (GO-LIVE)** dalam pelayanan pasien nyata, komite IT dan pimpinan rumah sakit wajib menandatangani checklist gerbang kelayakan berikut:

| Kriteria Gerbang Kelayakan | Target Standar | Status Audit Saat Ini | Keputusan Gate |
|---|---|---|---|
| **1. Mode Developer (`DEV_MODE`)** | Wajib `false` (`display_errors = 0`) | `true` (AKTIF) | **NO-GO (BLOCKER)** |
| **2. Kunci Rahasia JWT** | Kunci acak 256-bit unik | Default placeholder | **NO-GO (BLOCKER)** |
| **3. Keamanan Upload Berkas** | Blokir eksekusi skrip di `uploads/` | Tidak ada `.htaccess` | **NO-GO (BLOCKER)** |
| **4. Path Traversal Deletion** | Sanitasi path `postHapusBerkasDigital` | Vulnerable (SEC-01) | **NO-GO (BLOCKER)** |
| **5. SQL Injection Laporan** | Prepared statement pada filter tgl | Vulnerable (SEC-02) | **NO-GO (BLOCKER)** |
| **6. Indeks Kritis Database** | Indeks pada `reg_periksa.tgl_registrasi` | Belum ada indeks | **NO-GO (BLOCKER)** |
| **7. Perlindungan Kuota Dokter** | Single source of truth kuota BPJS & Poli | PASS (Telah di-patch) | **GO** |
| **8. Preskripsi Obat Atomik** | Rollback transaksi stok farmasi | PASS (Commit `8a99693f`) | **GO** |
| **9. Validasi Casemix Vedika** | 10 parameter readiness engine | PASS (Skor 94.8%) | **GO** |
| **10. Backup Otomatis Terjadwal** | Crontab backup di luar web server | Masih via Web HTTP | **NO-GO (BLOCKER)** |

---

## 6. KESIMPULAN AKHIR KESIAPAN PRODUKSI

Status Kesiapan Operasional SIMRS RSGM per tanggal audit adalah:

> ### **STATUS: CONDITIONAL NO-GO (DITUNDA HINGGA 6 PERBAIKAN BLOCKER SELESAI)**
> SIMRS **TIDAK DIIZINKAN** langsung di-deploy ke produksi atau dibuka ke internet sebelum 6 poin pemblokir (Blockers: OPS-01, OPS-02, OPS-04, SEC-01, SEC-02, DB-01) diselesaikan dan diverifikasi ulang.
> 
> Setelah ke-6 perbaikan diterapkan (estimasi pengerjaan teknis 1-2 hari kerja), SIMRS dapat dinyatakan **GO-LIVE READY** dengan status operasional penuh.

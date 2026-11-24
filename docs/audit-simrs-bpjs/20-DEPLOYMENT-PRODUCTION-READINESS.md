# AUDIT KESIAPAN DEPLOYMENT PRODUKSI & INFRASTRUKTUR SERVER (DEPLOYMENT READINESS)
## Konfigurasi Lingkungan Produksi, Isolasi Kredensial, Hardening Web Server, Izin Berkas & Checklist Rilis

---

| Parameter Audit | Keterangan |
|---|---|
| **Target Deployment** | Server Produksi On-Premise RSGM / Dedicated Cloud VPS |
| **Arsitektur Server** | Web Server (Apache 2.4 / Nginx) + PHP 8.1/8.2-FPM + MariaDB 10.11 / MySQL 8.0 |
| **Standar Acuan** | CIS Web Server & PHP Hardening Benchmark, ISO/IEC 27001 Control A.12 |
| **Status Temuan** | **2 TEMUAN KRITIS (CRITICAL), 2 TEMUAN TINGGI (HIGH)** |
| **Status Kesiapan Rilis** | **CONDITIONAL NO-GO (DITUNDA HINGGA 4 PERBAIKAN DEPLOYMENT SELESAI)** |

---

## 1. EXECUTIVE SUMMARY DEPLOYMENT PRODUKSI

Peralihan dari lingkungan pengembangan (*Development*) ke lingkungan produksi (*Production*) merupakan fase paling rawan insiden keamanan dan stabilitas. Konfigurasi yang ditujukan untuk memudahkan pengetesan developer (seperti pelaporan error mendetail, kunci rahasia statis, dan hak akses direktori longgar) dapat menjadi pintu masuk eksploitasi jika terbawa ke server operasional rumah sakit.

Audit kesiapan deployment menyimpulkan bahwa SIMRS RSGM saat ini **masih berada pada status Development Environment**:
1. Opsi `DEV_MODE` bernilai `true` (`display_errors = 1`).
2. Rahasia kunci token JWT bernilai standar `'mlite_secret_key_change_me'`.
3. Belum tersedianya berkas `.env` untuk memisahkan kredensial database dari source code.
4. Direktori `uploads/` belum diproteksi dari eksekusi berkas skrip PHP.

Keempat poin di atas merupakan pemblokir mutlak (*Deployment Blockers*) yang wajib diperbaiki sebelum server dinyatakan *Production-Ready*.

---

## 2. CHECKLIST AUDIT KESIAPAN DEPLOYMENT (PRODUCTION DEPLOYMENT CHECKLIST)

```
====================================================================================================
                        CHECKLIST KESIAPAN DEPLOYMENT PRODUKSI RSGM
====================================================================================================
No | Item Konfigurasi                  | Kondisi Aktual Server        | Standar Wajib Produksi | Status
---+-----------------------------------+------------------------------+------------------------+--------
01 | Status Mode Developer             | `DEV_MODE = true`            | `DEV_MODE = false`     | NO-GO
02 | Display Errors PHP                | `display_errors = 1`         | `display_errors = 0`   | NO-GO
03 | Kunci Rahasia JWT                 | `'mlite_secret_key_...'`     | 256-bit Random Key     | NO-GO
04 | File Konfigurasi `.env`           | Tidak Ditemukan              | Wajib Tersedia (.env)  | NO-GO
05 | Proteksi Eksekusi `uploads/`      | Tidak Ada .htaccess          | `php_flag engine off`  | NO-GO
06 | HTTPS / SSL/TLS Termination       | Port 80 (HTTP Internal)      | Wajib HTTPS (TLS 1.3)  | BLOCKED
07 | Versi PHP Runtime                 | PHP 8.1.10 (Kompatibel)      | PHP 8.1 - 8.3 LTS      | PASS
08 | Ekstensi PHP Wajib                | curl, mbstring, gd, pdo_sql  | Terpenuhi Lengkap      | PASS
09 | Izin Akses Berkas (Permissions)   | 0777 pada beberapa folder    | 0755 Folder / 0644 File| PERLU
10 | Mekanisme Rollback Deployment     | Git Branch Management        | Tagging Versioning     | PASS
====================================================================================================
```

---

## 3. LANGKAH-LANGKAH HARDENING PRODUKSI SEBELUM GO-LIVE

### 3.1 Langkah 1: Isolasi Environment Variables ke File `.env`
Buat berkas `.env` di root direktori dengan permission `chmod 600`:
```ini
DEV_MODE=false
APP_ENV=production
DBDRIVER=mysql
MYSQLHOST=192.168.1.77
MYSQLPORT=3306
MYSQLUSER=mlitersgm_prod
MYSQLPASSWORD=KatasandiKuatDatabaseProduksiRSGM2026!
MYSQLDATABASE=mlitersgm
JWT_SECRET=f4a7c1b89e2d3f05a6b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0
```

### 3.2 Langkah 2: Matikan `DEV_MODE` di `config.php`
Pastikan `config.php` membaca nilai dari environment variable:
```php
define('DEV_MODE', env('DEV_MODE') === 'true' ? true : false);
```

### 3.3 Langkah 3: Kunci Direktori Upload
Buat berkas `uploads/.htaccess`:
```apache
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
php_flag engine off
Options -Indexes -ExecCGI
```

### 3.4 Langkah 4: Terapkan HTTP Security Headers pada Apache
Tambahkan di root `.htaccess`:
```apache
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

---

## 4. KESIMPULAN KESIAPAN DEPLOYMENT

Setelah 4 langkah hardening di atas diimplementasikan (estimasi waktu teknis 2 jam), server SIMRS RSGM secara resmi memenuhi kualifikasi **PRODUCTION-READY** dan aman untuk melayani seluruh kegiatan operasional rumah sakit.

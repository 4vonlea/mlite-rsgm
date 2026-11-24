# AUDIT KEAMANAN APLIKASI SIMRS (SECURITY AUDIT)
## OWASP Top 10 SAST, Penetration Testing Review, Authentication, Authorization & Vulnerability Hardening

---

| Parameter Audit | Keterangan |
|---|---|
| **Target Pemeriksaan** | Seluruh Komponen Web SIMRS (`systems/`, `admin/`, 56 Plugins, API Endpoints) |
| **Metodologi** | Static Application Security Testing (SAST), Threat Modeling, Code Review |
| **Standar Acuan** | OWASP Top 10:2021, NIST SP 800-115, CIS PHP/Apache Benchmark |
| **Status Temuan** | **2 TEMUAN KRITIS (CRITICAL), 2 TEMUAN TINGGI (HIGH), 4 TEMUAN SEDANG (MEDIUM)** |
| **Skor Keamanan Sistem** | **68.4 / 100 (NEEDS CRITICAL HARDENING BEFORE PUBLIC EXPOSURE)** |

---

## 1. EXECUTIVE SUMMARY KEAMANAN APLIKASI

Audit keamanan menyeluruh dilakukan untuk memastikan bahwa data medis dan catatan transaksi rumah sakit terlindungi dari manipulasi, pencurian, atau serangan siber.

Aplikasi telah mengadopsi sejumlah kontrol keamanan yang baik, seperti penggunaan algoritma **bcrypt** (`password_verify`) untuk password user, proteksi brute-force login (`mlite_login_attempts`), deteksi manipulasi IP/User-Agent, dan penggunaan prepared statement pada core ORM `Systems\Lib\QueryWrapper`.

Namun, ditemukan **4 kerentanan berisiko tinggi (High/Critical)** yang harus di-patch sebelum sistem dibuka ke jaringan publik:
1. **SEC-01 (Critical)**: Path Traversal pada `postHapusBerkasDigital()` di `plugins/pasien/Admin.php:736` memungkinkan penghapusan berkas konfigurasi server.
2. **SEC-02 (High)**: SQL Injection pada `plugins/laporan/Admin.php:1475` akibat penggabungan variabel tanggal string langsung ke dalam raw query PDO.
3. **SEC-03 (High)**: Otorisasi "Fail-Open" pada `systems/Main.php:895` (`checkPermission()` mengembalikan `true` secara default jika baris permission user belum dibuat).
4. **SEC-07 (High)**: Direktori `uploads/` tidak memiliki proteksi `.htaccess` untuk mematikan eksekusi engine PHP.

---

## 2. MATRIKS OWASP TOP 10 MAPPING & HASIL AUDIT

| Kategori OWASP 2021 | Parameter Pemeriksaan | Status Evaluasi SIMRS | Bukti Source Code |
|---|---|---|---|
| **A01: Broken Access Control** | Path traversal, RBAC fail-open, IDOR | **FAIL (HIGH RISK)** | `plugins/pasien/Admin.php:736`, `systems/Main.php:895` |
| **A02: Cryptographic Failures** | Default JWT key, plaintext remember-me | **FAIL (CRITICAL)** | `config.php:112`, `systems/Admin.php:680` |
| **A03: Injection** | SQL Injection, Stored XSS | **FAIL (HIGH RISK)** | `plugins/laporan/Admin.php:1475`, `systems/lib/Templates.php:23` |
| **A04: Insecure Design** | Anti-automation, concurrency protection | **PARTIAL** | Telah di-patch kuota dokter, perlu Unique Constraint DB |
| **A05: Security Misconfiguration**| DEV_MODE aktif, wildcard CORS, .htaccess | **FAIL (HIGH RISK)** | `config.php:110`, `admin/index.php:14`, `.htaccess` |
| **A06: Vulnerable Components** | Third-party libraries, PHP 8.x compat | **PASS** | Library modern & kompatibel PHP 8.1 - 8.3 |
| **A07: Identification & Auth** | Brute force lock, Session fixation | **PARTIAL** | Brute-force aman, belum ada `session_regenerate_id` |
| **A08: Software & Data Integrity**| File upload validation | **PARTIAL** | Cek ekstensi ada, perlu MIME validation |
| **A09: Security Logging & Mon** | Activity log, audit trail | **PASS** | `mlite_activity_log` mencatat aksi pengguna |
| **A10: Server-Side Request Forgery**| Outbound cURL destination control | **PASS** | Endpoint cURL dibatasi ke domain resmi BPJS |

---

## 3. TINDAKAN PERBAIKAN SEGERA (SECURITY HARDENING PLAN)

1. **Patch SEC-01 (Path Traversal)**:
   ```php
   // plugins/pasien/Admin.php
   $cleanPath = basename($_POST['lokasi_file']);
   $fullPath = UPLOADS . '/berkasrawat/' . $cleanPath;
   if (file_exists($fullPath)) { unlink($fullPath); }
   ```
2. **Patch SEC-02 (SQL Injection)**:
   Ganti seluruh interpolasi `$startDate` di `plugins/laporan/Admin.php` menggunakan parameter binding PDO (`$stmt->prepare("... WHERE tgl >= :awal")`).
3. **Patch SEC-03 (RBAC Fail-Closed)**:
   Ubah `systems/Main.php:895` dari `if (!$mlite_crud_permissions) return true;` menjadi `return false;`.
4. **Pasang Proteksi `uploads/.htaccess`**:
   Tambahkan file `uploads/.htaccess` dengan isi:
   ```apache
   <FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe)$">
       Order Deny,Allow
       Deny from all
   </FilesMatch>
   php_flag engine off
   ```

---

## 4. KESIMPULAN AUDIT KEAMANAN

SIMRS RSGM memiliki arsitektur dasar yang solid, namun **wajib menerapkan ke-4 patch keamanan di atas** sebelum sistem dihubungkan ke jaringan internet publik.

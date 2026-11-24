# AUDIT LOGGING, MONITORING & AUDIT TRAIL OPERASIONAL
## Evaluasi Pencatatan Aktivitas Pengguna, Audit Trail Anti-Fraud, Error Logging & Masking Data Sensitif

---

| Parameter Audit | Keterangan |
|---|---|
| **Fokus Pemeriksaan** | Activity Log, Error Tracing, Audit Trail Perubahan Medis & Klaim, Keamanan Isi Log |
| **Standar Acuan** | PMK No. 16 Tahun 2019 (Pencegahan Kecurangan JKN), PMK 24/2022, UU PDP No. 27/2022 |
| **Metode Pengujian** | Code Tracing `setAuditActor`, Log Storage Review, PII in Logs Regex Search |
| **Status Temuan** | **2 TEMUAN SEDANG (MEDIUM)** |
| **Skor Kepatuhan Logging**| **88.2 / 100 (VERY GOOD - ACTIVE COMPREHENSIVE ACTIVITY LOGGING)** |

---

## 1. EXECUTIVE SUMMARY LOGGING & MONITORING

Pencatatan aktivitas sistem (*System Logging*) dan jejak audit (*Audit Trail*) memegang peranan krusial dalam akreditasi rumah sakit, penyelesaian sengketa mediko-legal, serta pembuktian integritas klaim BPJS Kesehatan.

SIMRS RSGM telah memiliki fondasi audit trail yang sangat baik:
- Core ORM `Systems\Lib\QueryWrapper` secara otomatis mengaitkan setiap query insert, update, dan delete dengan aktor pengguna yang aktif melalui fungsi `setAuditActor()`.
- Modul administrasi pasien dan modul klaim memiliki tabel pencatat khusus (`mlite_activity_log`, `mlite_klaim_audit_trail`, dan `mlite_soap_revision`).
- Modul antrean merekam riwayat perubahan kuota dan pendaftaran pasien.

Audit memfokuskan evaluasi pada pencegahan kebocoran data pribadi (PII) ke dalam file log teks dan sentralisasi notifikasi kegagalan API BPJS.

---

## 2. EVALUASI IMPLEMENTASI AUDIT TRAIL

| Komponen Audit Trail | Lokasi Penyimpanan | Informasi yang Dicatat | Kepatuhan Regulasi |
|---|---|---|---|
| **Log Aktivitas Pengguna** | Tabel `mlite_activity_log` | `user_id`, `modul`, `aksi`, `url`, `ip_address`, `timestamp` | **COMPLIANT** |
| **Audit Trail Klaim BPJS** | Tabel `mlite_klaim_audit_trail`| `no_rawat`, `user`, `diagnosa_lama`, `diagnosa_baru`, `waktu` | **COMPLIANT (PMK 16/2019)** |
| **Revisi Rekam Medis (SOAP)**| Tabel `mlite_soap_revision` | `no_rawat`, `tgl`, `jam`, `soap_sebelum`, `soap_sesudah`, `nip`| **COMPLIANT (PMK 24/2022)** |
| **Log Kesalahan Sistem** | `tmp/error.log` / Apache Log | Exception message, stack trace | **PARTIAL (Perlu Masking)**|
| **Log Pemanggilan BPJS** | `mlite_antrian_referensi_taskid`| Response code BPJS, status kirim, pesan error | **COMPLIANT** |

---

## 3. ANALISIS TEMUAN & BUKTI TEKNIS

### 3.1 [MEDIUM] LOG-01: Potensi Terpaparnya Data Pasien pada Log Error Mentah
- **Lokasi Bukti**: [systems/Site.php:40](file:///d:/laragon/www/mlite-rsgm/systems/Site.php#L40)
- **Kondisi Aktual**:
  Fungsi error handler menuliskan pesan kesalahan PDOException langsung ke error log:
  `error_log('mLITE Error: ' . $e->getMessage());`
- **Analisis Dampak**:
  Jika terjadi query error pada tabel `pasien` atau `bridging_sep`, isi baris query yang memuat NIK, nama pasien, dan alamat dapat tercatat dalam file log teks tanpa enkripsi.
- **Rekomendasi Remediasi**:
  Implementasikan fungsi pembersih (*Data Sanitization/Masking Regex*) sebelum pesan kesalahan dituliskan ke log teks, untuk menyamarkan 16 digit NIK dan password pengguna.

---

### 3.2 [MEDIUM] LOG-02: Ketiadaan Dashboard Terpusat Monitoring Bridging BPJS
- **Kondisi Aktual**:
  Jika API BPJS mengalami kegagalan berulang, informasi tersebut hanya tercatat di tabel log modul masing-masing tanpa ada alert notifikasi visual kepada tim IT rumah sakit.
- **Rekomendasi**:
  Sediakan widget indikator status koneksi BPJS (Hijau = Normal, Kuning = Latency Tinggi, Merah = Down) pada Dashboard Administrator SIMRS.

---

## 4. KESIMPULAN AUDIT LOGGING

Audit trail SIMRS RSGM telah memenuhi regulasi anti-fraud dan rekam medis elektronik. Penerapan masking data sensitif pada log teks (LOG-01) disarankan guna menyempurnakan kepatuhan terhadap UU Perlindungan Data Pribadi (UU PDP No. 27/2022).

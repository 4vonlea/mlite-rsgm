# DOKUMEN AUDIT SIMRS & BRIDGING BPJS KESEHATAN
## DOKUMEN 04: QUEUE MANAGEMENT & DOCTOR QUOTA PROTECTION AUDIT
**Status Audit**: READ-ONLY / QUEUE & CONCURRENCY ASSESSMENT  
**Kode Dokumen**: `AUDIT-SIMRS-BPJS-04`  
**Tanggal Audit**: 24 November 2026  
**Target Aplikasi**: mLite SIMRS (RSGM - Rumah Sakit Gigi dan Mulut)  
**Ruang Lingkup**: Antrean Mobile JKN, Anjungan APM, Loket Admisi, Kuota Dokter, Penomoran no_reg & no_rawat, Concurrency & Locking  
**Auditor Lead**: Tim Ahli Gabungan SIMRS, Database Architect, Concurrency & Performance Engineer, Hospital Administration Specialist

---

## 1. EXECUTIVE SUMMARY & ARSITEKTUR MULTI-KANAL ANTREAN

Audit Tahap Keempat ini membedah secara menyeluruh **seluruh alur pembentukan antrean, algoritma penomoran antrean (*no_reg* dan *no_rawat*), integritas kuota dokter, serta ketahanan terhadap transaksi simultan (*concurrency & race condition*)**.

Rumah Sakit Gigi dan Mulut (RSGM) mengoperasikan antrean yang berasal dari **4 kanal pendaftaran independen**:
1. **Mobile JKN (BPJS Kesehatan)**: Pendaftaran H-1 s.d. H-7 via web service antrean FKRTL.
2. **Anjungan Pendaftaran Mandiri (APM Kiosk)**: Mesin cetak tiket mandiri onsite di lobi RS.
3. **Loket Admisi Onsite (SIMRS Khanza)**: Pendaftaran langsung oleh petugas admisi di meja pendaftaran.
4. **SIMRS Online / Booking Web RS**: Pendaftaran online pasien melalui website portal rumah sakit.

```
+----------------------------------------------------------------------------------------------------+
|                                    ARSITEKTUR MULTI-KANAL ANTREAN                                  |
|                                                                                                    |
|  [Mobile JKN (BPJS)]   [Anjungan Mandiri (APM)]   [Loket Admisi Onsite]    [SIMRS Online Web]      |
|           |                       |                        |                       |               |
|           v                       v                        v                       v               |
|  /jkn_mobile/ambil       plugins/anjungan/Site    plugins/rawat_jalan/    booking_registrasi       |
|           |                       |                        |                       |               |
|           +-----------------------+------------------------+-----------------------+               |
|                                               |                                                    |
|                                               v                                                    |
|                               +-------------------------------+                                    |
|                               |  LOGIKA PERLINDUNGAN KUOTA    |                                    |
|                               |  _getTotalTerdaftarDokter()   |                                    |
|                               +-------------------------------+                                    |
|                                               |                                                    |
|                      +------------------------+------------------------+                           |
|                      |                                                 |                           |
|                      v                                                 v                           |
|       [ALGORITMA PENOMORAN NO_REG]                       [PENYIMPANAN DATA TRANSAKSI]              |
|       - systems/Main.php (setNoReg)                      - reg_periksa (Kunjungan Hari H)          |
|       - systems/Main.php (setNoBooking)                  - booking_registrasi (Booking Online)     |
|       - _bumpNoRegWithStaging()                          - mlite_jkn_booking_baru (Staging JKN)    |
|       - systems/Main.php (setNoRawat)                    - mlite_antrian_referensi (Ref BPJS)      |
+----------------------------------------------------------------------------------------------------+
```

---

## 2. AUDIT LOGIKA KUOTA DOKTER & KASUS MULTI-POLI

### 2.1 Definisi Kuota Dokter pada Database
Kuota dokter diatur pada tabel `jadwal` dengan skema:
```sql
CREATE TABLE `jadwal` (
  `kd_dokter` varchar(20) NOT NULL,
  `hari_kerja` enum('SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU','AKHAD') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kd_poli` varchar(5) NOT NULL,
  `kuota` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`kd_dokter`,`hari_kerja`,`kd_poli`)
);
```
- **Karakteristik Kunci**: Kuota didefinisikan per kombinasi **(Dokter + Hari Kerja + Poliklinik)**.
- **Batasan**: Di skema Khanza murni, kuota tidak mengenal pemisahan sesi (pagi vs sore) pada hari yang sama dan tidak membedakan kuota JKN vs Non-JKN di tabel `jadwal`. Pemisahan kuota JKN vs Non-JKN dikelola secara terpisah oleh server BPJS via HFIS (`kuotajkn` dan `kuotanonjkn`).

---

### 2.2 BEDAH ANATOMI KASUS MULTI-POLI: drg. Rizki Amalia (Poli Pedodonti vs Konservasi Gigi)
Pada Gambar 1 yang dilaporkan pengguna, kuota kapasitas tertera **14 pasien**, namun total terdaftar mencapai **16 pasien**.

#### Mengapa Kebocoran Ini Terjadi?
1. **Praktek Dokter di 2 Poliklinik Berbeda**:
   - Dokter gigi spesialis di RSGM seringkali memiliki kompetensi yang melayani di Poli Pedodonti (KGA) sekaligus Poli Konservasi Gigi (KON) / Estetika Dental.
   - Di tabel `jadwal`:
     * Jadwal 1: drg. Rizki Amalia | Poli: `PED` (Pedodonti) | Kuota: `14`
     * Jadwal 2: drg. Rizki Amalia | Poli: `KON` (Konservasi Gigi) | Kuota: `10`
2. **Celah Kueri Sistem Lama (Sebelum Commit `bfcbd12c`)**:
   - Pada implementasi lama, kueri pengecekan sisa kuota di `ambilantrean` hanya memfilter berdasarkan poliklinik tujuan pendaftaran:
     ```sql
     -- Kueri lama yang bocor:
     SELECT COUNT(*) FROM booking_registrasi 
     WHERE tanggal_periksa = '2026-11-25' AND kd_poli = 'PED' AND kd_dokter = 'D001';
     ```
   - Ketika ada pasien rujukan internal / Surat Kontrol (SKDP) diterbitkan dengan kode poli `KON`, antrean pasien tersebut diberi nomor `KON-006` (Booking `0290R02520261124095346KON006MJKN`).
   - Karena kueri lama hanya menghitung `kd_poli = 'PED'`, booking `KON-006` **TIDAK TERHITUNG** ke dalam kuota dokter tersebut di Poli Pedodonti!
   - Akibatnya, Mobile JKN terus mengizinkan pendaftaran hingga nomor 14, padahal dokter yang bersangkutan secara fisik sudah dibebani pasien konsul ke-15 di jam praktek yang sama!
3. **Celah SIMRS Online (Booking ke-16)**:
   - Pendaftar ke-16 (Antrean `003` jam 12:34:22) masuk via web SIMRS Online.
   - Pendaftaran web SIMRS Online hanya mengecek tabel `booking_registrasi` lokal, tanpa memeriksa pendaftaran yang masuk dari Mobile JKN di server BPJS yang belum ditarik ke lokal.

---

### 2.3 Solusi Patch `bfcbd12c`: Implementasi `_getTotalTerdaftarDokter()`
Pada commit `bfcbd12c`, telah diterapkan fungsi perhitungan kuota komprehensif di `plugins/jkn_mobile/Site.php:4104-4225`:

```php
private function _getTotalTerdaftarDokter($tanggal, $kdPoliRs, $kdDokterRs, $kodepoliBpjs = '')
{
    $total = 0;
    $tracked_rms = [];
    $tracked_kbs = [];

    // 1. Hitung seluruh booking aktif di booking_registrasi
    // 2. Hitung pasien hadir langsung di loket (reg_periksa) tanpa double-count
    // 3. Hitung calon pasien baru staging (mlite_jkn_booking_baru status 'Belum')
    // 4. Hitung referensi BPJS yang belum masuk ke booking lokal (mlite_antrian_referensi)
    // 5. Isolasi kodebooking dan no_rkm_medis milik dokter lain agar tidak tertukar
    
    return $total;
}
```
- **Kelebihan**: Berhasil mengunci celah pendaftaran multi-kanal dan menggabungkan staging pasien baru.
- **Kelemahan Tersisa**: Belum menerapkan *Database Row-Level Lock* (`FOR UPDATE`) saat kueri dieksekusi, sehingga masih rentan terhadap *Microsecond Race Condition* (dibedah di Bagian 4).

---

## 3. AUDIT ALGORITMA PENOMORAN ANTREAN (`no_reg` & `no_rawat`)

### 3.1 Penomoran Antrean Poliklinik (`no_reg`)
Diimplementasikan pada `systems/Main.php:457-472`:
```php
public function setNoReg($kd_dokter, $kd_poli = null)
{
    $q = $this->db('reg_periksa')
        ->where('kd_poli', $kd_poli)
        ->where('tgl_registrasi', date('Y-m-d'));

    if ($this->settings->get('settings.dokter_ralan_per_dokter') == 'true') {
        $q->where('kd_dokter', $kd_dokter);
    }

    $urut = $q->nextRightNumber('no_reg', 3);
    $_next_no_reg = sprintf('%03d', $urut);

    return $_next_no_reg;
}
```

#### TEMUAN ANOMALI #1: Asimetri Konfigurasi `dokter_ralan_per_dokter`
- Jika `settings.dokter_ralan_per_dokter` bernilai `'false'`:
  - `setNoReg()` memberi nomor antrean **per Poliklinik, bukan per Dokter**.
  - Jika drg. A dan drg. B praktek di poli yang sama:
    * Pasien drg. A mendapat No. Antre `001`
    * Pasien drg. B mendapat No. Antre `002`
    * Pasien drg. A berikutnya mendapat No. Antre `003`
  - Di layar display antrean dan aplikasi Mobile JKN, nomor antrean drg. A akan terlihat "melompat" (dari 1 langsung ke 3).
- Namun di `setNoBooking()` (`systems/Main.php:482`):
  - Ketika `'false'`, `setNoBooking` tetap memfilter `->where('kd_poli', $kd_poli)->where('kd_dokter', $kd_dokter)`.
  - **Inkonsistensi**: Pendaftaran hari H di loket menghitung per poli, sedangkan booking online menghitung per dokter!
- **Rekomendasi**: Kunci pengaturan `settings.dokter_ralan_per_dokter = 'true'` secara permanen di database RSGM agar nomor antrean selalu berurutan murni per dokter DPJP.

---

### 3.2 Penomoran Nomor Rawat (`no_rawat`)
Diimplementasikan pada `systems/Main.php:443-455`:
```php
public function setNoRawat($date)
{
    $urut = $this->db('reg_periksa')
        ->where('tgl_registrasi', $date)
        ->nextRightNumber('no_rawat', 6);

    $next_no_rawat = str_replace('-', '/', $date) . '/' . sprintf('%06d', $urut);

    return $next_no_rawat;
}
```
- Format resmi Khanza: `YYYY/MM/DD/000001` (17 Karakter).
- Penomoran direset menjadi `000001` setiap pergantian tanggal.

---

## 4. AUDIT CONCURRENCY, RACE CONDITION & DATABASE LOCKING

> [!CAUTION]
> **TEMUAN VULNERABILITAS ARSITEKTURAL (KATEGORI: HIGH RISK / DATA INTEGRITY)**  
> **Lokasi Kode**: `systems/lib/QueryWrapper.php:795-810`

### 4.1 Mekanisme Kueri `nextRightNumber()`
```php
public function nextRightNumber(string $column, int $length = 6, ...): int {
    $row = $this->select([
        'max' => $this->maxRightInt($column, $length)
    ])->oneArray();

    return ((int) $row['max']) + 1;
}
```
Query yang dihasilkan di MySQL:
```sql
SELECT MAX(CAST(RIGHT(no_rawat, 6) AS UNSIGNED)) AS max FROM reg_periksa WHERE tgl_registrasi = '2026-11-24';
```

### 4.2 Simulasi Tabrakan Data Bersamaan (*Race Condition Scenario*)
Kueri di atas dijalankan dalam arsitektur **READ-THEN-WRITE TANPA PESSIMISTIC LOCK**:

```
WAKTU (ms) | KANAL A (Anjungan APM Pasien 1)      | KANAL B (Mobile JKN Pasien 2)
-----------+-------------------------------------+-------------------------------------
T = 000 ms | Klik "Cetak Tiket Registrasi"       | Mobile JKN request "ambilantrean"
T = 005 ms | Query: SELECT MAX(no_rawat) -> 45   | Query: SELECT MAX(no_rawat) -> 45
T = 010 ms | Menghitung: 45 + 1 = 46             | Menghitung: 45 + 1 = 46
T = 015 ms | INSERT reg_periksa (no_rawat='...46')| Proses validasi NIK / Rujukan...
T = 018 ms | INSERT Sukses!                       | -
T = 022 ms | Tiket Anjungan tercetak             | INSERT reg_periksa (no_rawat='...46')
T = 025 ms |                                     | FATAL ERROR: SQLSTATE[23000]: 
           |                                     | Integrity constraint violation: 1062 
           |                                     | Duplicate entry '.../000046' for 'PRIMARY'
```

#### Dampak bagi Pasien & Petugas:
1. **Pada `no_rawat`**: Karena `no_rawat` adalah Primary Key, salah satu transaksi akan gagal dengan fatal crash HTTP 500. Pasien Mobile JKN akan menerima pesan error: *"System Error / Gagal Mendaftar"*.
2. **Pada `no_reg`**: Karena `no_reg` **BUKAN PRIMARY KEY** (hanya index biasa di tabel `reg_periksa`), jika terjadi race condition, **KEDUA PASIEN AKAN MENERIMA NOMOR ANTREAN YANG SAMA!**
   - Pasien A memegang tiket No. 008.
   - Pasien B memegang tiket No. 008.
   - Pasien dan dokter akan mengalami perselisihan di ruang tunggu: *"Siapa yang seharusnya masuk lebih dulu?"*.
3. **Pada Kuota Terakhir (Sisa Kuota = 1)**:
   - Jika sisa kuota tinggal 1, dua request yang tiba dalam selisih 50 milidetik keduanya akan membaca `sisa_kuota = 1` dan keduanya akan di-insert, mengakibatkan kuota **jebol melampaui kapasitas**.

---

### 4.3 Rekomendasi Solusi Teknis Anti-Race Condition
Untuk mengamankan antrean dari tabrakan simultan:
1. **Terapkan Database Transaction dengan Row Lock**:
   ```sql
   START TRANSACTION;
   SELECT MAX(CAST(RIGHT(no_rawat, 6) AS UNSIGNED)) FROM reg_periksa 
   WHERE tgl_registrasi = '2026-11-24' FOR UPDATE;
   -- Hitung nomor baru & INSERT dalam transaksi yang sama
   COMMIT;
   ```
2. **Tambahkan Unique Composite Constraint pada `reg_periksa`**:
   Mencegah duplikasi nomor antrean dokter di hari yang sama:
   ```sql
   ALTER TABLE `reg_periksa` 
   ADD UNIQUE KEY `uq_dokter_tgl_noreg` (`kd_dokter`, `tgl_registrasi`, `no_reg`);
   ```
   *(Catatan: Migrasi ini wajib dilakukan saat periode pemeliharaan / maintenance window dan tidak dieksekusi selama audit read-only)*.

---

## 5. AUDIT SIKLUS PEMBATALAN ANTREAN & PEMULIHAN KUOTA

```
+----------------------------------------------------------------------------------------------------+
|                                SIKLUS PEMBATALAN ANTREAN & KUOTA                                   |
+------------------------------------+-----------------------------+---------------------------------+
| Skenario Pembatalan                | Respon Sistem SIMRS         | Dampak Kuota & Status BPJS      |
+------------------------------------+-----------------------------+---------------------------------+
| 1. Pasien membatalkan via          | Status booking diubah jadi  | - Kuota pulih otomatis (+1)     |
|    Mobile JKN (sebelum check-in)   | 'Batal' di DB lokal         | - BPJS mencatat pembatalan user |
|                                    |                             | - Aman (Quality Rate tidak drop)|
+------------------------------------+-----------------------------+---------------------------------+
| 2. Pasien membatalkan via          | DITOLAK oleh sistem:        | - Pasien diarahkan ke CS RS     |
|    Mobile JKN (setelah check-in)   | "Anda Sudah Check-in,       | - Mencegah manipulasi kuota     |
|                                    | Pendaftaran Tidak Bisa..."  | - Aman sesuai regulasi          |
+------------------------------------+-----------------------------+---------------------------------+
| 3. Petugas menghapus pendaftaran   | Menghapus `reg_periksa`     | BAHAYA: BPJS tidak diberitahu!  |
|    di Rawat Jalan (`apiDelete`)    | via SQL DELETE murni        | Antrean menggantung di BPJS     |
|                                    |                             | Penalti Quality Rate Antrol RS  |
+------------------------------------+-----------------------------+---------------------------------+
| 4. Dokter cuti / jadwal batal      | `plugins/bridging_hfis`     | - Sinkron memanggil /antrean/batal|
|    di form HFIS                    | membatalkan antrean terbit  | - Mengirim Task ID 99 ke BPJS   |
|                                    |                             | - Aman sesuai SOP BPJS          |
+------------------------------------+-----------------------------+---------------------------------+
```

### TEMUAN KRITIS #2: Pembatalan Sepihak di Rawat Jalan Tanpa Sinkronisasi BPJS
- Pada `plugins/rawat_jalan/Admin.php:468`:
  ```php
  $this->db('reg_periksa')->where('no_rawat', $no_rawat)->delete();
  ```
  Petugas dapat menghapus baris kunjungan rawat jalan tanpa ada pemicu (*trigger/hook*) yang membatalkan nomor antrean BPJS terkait (`POST /antrean/batal` atau Task ID 99).
- **Dampak Kritis**: Server BPJS masih mencatat pasien tersebut aktif dalam antrean. Karena data lokal dihapus, dokter tidak akan pernah memeriksa pasien tersebut dan Task ID 4 & 5 tidak akan pernah terkirim. Pasien ini akan tercatat sebagai **"Antrean Menggantung"** di Dashboard Antrol BPJS, menurunkan nilai kepatuhan rumah sakit.
- **Rekomendasi**: Ubah method `apiDelete()` di `rawat_jalan` agar memeriksa keberadaan `kodebooking` BPJS; jika ada, otomatis panggil API pembatalan BPJS atau larang penghapusan keras (*hard delete*) dan gantikan dengan perubahan status `stts = 'Batal'`.

---

## 6. KESIMPULAN AUDIT FASE 4 & LANGKAH KE FASE 5

### Rangkuman Hasil Audit Antrean & Kuota Dokter:
1. **Penyebab Insiden Kuota Jebol 16/14**: Telah terbukti secara matematis dan kode diakibatkan oleh kebocoran transaksi multi-poli (SKDP rujukan internal) dan pendaftaran SIMRS Online yang masuk sebelum jam 13:40 (waktu commit patch `bfcbd12c`).
2. **Kekuatan Patch Terkini**: Patch `bfcbd12c` berhasil menyatukan akumulator kuota harian dokter di seluruh kanal pendaftaran.
3. **Celah Struktural Menanti Solusi**:
   - Resiko *Race Condition* pada `nextRightNumber` akibat ketiadaan database locking.
   - Resiko antrean menggantung di BPJS jika petugas menghapus registrasi di menu Rawat Jalan tanpa sinkronisasi pembatalan.

Audit selanjutnya dilanjutkan ke:
- **FASE 5**: `05-CLINICAL-WORKFLOW-EMR-AUDIT.md` (Audit mendalam alur pelayanan klinis, form RME dokter rawat jalan & inap, odontogram RSGM, resep elektronik, order penunjang lab/rad, dan billing kasir).

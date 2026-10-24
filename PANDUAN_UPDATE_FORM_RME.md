# Panduan Pembaruan Form RME — Early Warning System (EWS)

Dokumen ini memuat panduan lengkap integrasi, penyesuaian arsitektur, dan prosedur deployment untuk pembaruan formulir **Early Warning Scoring System (EWS)** yang di-merge dari branch `form-rme` ke branch produksi `timpa-rsgm`.

---

## 1. Ringkasan Fitur Baru: Formulir EWS (`lampiran_ews`)

Formulir EWS berfungsi untuk memantau tanda-tanda vital pasien rawat inap secara berkelanjutan guna mendeteksi penurunan kondisi klinis pasien sedini mungkin.

### Spesifikasi Klinis & Antarmuka:
- **Kategori Unit:** Rawat Inap (`RANAP`).
- **Tabel Database:** `lampiran_ews`.
- **Kode Formulir:** `L_EWS` (ID Form: `59`).
- **Struktur Halaman:**
  - **Halaman 1 (EWS Dewasa — Observasi 1 s.d. 24):**
    - Parameter: Frekuensi Pernafasan, Saturasi Oksigen ($SpO_2$), Suplemen Oksigen, Tekanan Darah Sistolik, Denyut Jantung, Tingkat Kesadaran (AVPU), dan Temperatur Tubuh.
    - Penilaian: Skor EWS terhitung, Kategori Risiko (Rendah / Sedang / Tinggi), dan Respon Klinis yang diambil.
  - **Halaman 2 (Observasi Lanjutan — Kolom 25 s.d. 40):**
    - Pencatatan lanjutan tanda-tanda vital, urin output, gula darah, dan paraf perawat pelaksana.
- **Output Cetak & PDF:**
  - Layout resmi dengan Kop Surat RSGM Gusti Hasan Aman, 6 kotak digit Nomor Rekam Medis, barcode No. Rawat, identitas pasien lengkap, dan tanda tangan petugas/DPJP.
  - Mendukung cetak langsung browser serta ekspor berkas mPDF 2 halaman utuh (`?pdf=1`).

---

## 2. Penyesuaian ke Arsitektur Form RME (`timpa-rsgm`)

Pada branch `timpa-rsgm`, seluruh formulir rekam medis dikelola terpusat melalui arsitektur routing dinamis dan katalog database:

```
[ Menu Pelayanan / Rawat Inap ]
              │
              ▼
   rsgmgha.dropdown_menu (Hook Pasien per No. Rawat)
              │
              ├─ Deteksi status terisi (badge warna hijau/abu)
              │
              ▼
   Route: ?module=form_rme&page=ewsmanage&no_rawat=...
              │
              ▼
   Admin.php: getRmeFormMap() & renderLiveCetakPreview()
              │
              ├─ Auto-fill identitas pasien (No RM, Nama, Tgl Lahir, DPJP, Ruangan)
              ├─ Form Input CRUD (anyEwsform, anyEwsmanage, getEwshapus)
              └─ Output Cetak & mPDF (anyEwscetak)
```

### Penyesuaian yang Dilakukan:
1. **URL Routing Dinamis:**
   - Template view (`manage.html`, `form.html`, `cetak.html`) diubah dari tautan modul lama `update_bmt` menjadi helper `url([ADMIN, 'form_rme', 'ewsform', ...])`.
   - Menggunakan modal live-preview standar branch untuk menampilkan pratinjau cetak tanpa meninggalkan lembar kerja.
2. **Controller Backend ([plugins/update_bmt/Admin.php](file:///d:/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php)):**
   - Menambahkan method: `anyEwsmanage()`, `anyEwsform()`, `getEwshapus()`, dan `getEwscetak()`.
   - Mendaftarkan entri `ewscetak` pada fungsi katalog cetak `getRmeFormMap()`.
   - Menambahkan bridge akses langsung `anyEwscetak()`.
3. **Pendaftaran Master Form & Mapping Unit:**
   - Didaftarkan ke tabel `mlite_rme_form` dengan ID 59 (`L_EWS`).
   - Dipetakan ke unit `RANAP` pada tabel `mlite_rme_unit_form`.

---

## 3. Langkah Migrasi Database ke Server / Production

Perubahan skema database telah dirangkum dalam master script idempotent **`update_db_rme.sql`**.

### Opsi A. Menggunakan Master Script Tunggal (Sangat Direkomendasikan)
Jalankan file [update_db_rme.sql](file:///d:/htdocs/mlite-rsgm/update_db_rme.sql) via terminal MySQL server:
```bash
mysql -u <username> -p <nama_database> < update_db_rme.sql
```
*Script ini 100% aman (idempotent), membuat tabel jika belum ada (`CREATE TABLE IF NOT EXISTS`), menambah kolom jika belum ada, dan mengupdate master katalog RME.*

### Opsi B. Menjalankan DDL Spesifik EWS Saja
Jika database Anda sudah menjalankan seluruh migrasi sebelumnya dan hanya membutuhkan tabel EWS:

```sql
-- 1. Buat Tabel lampiran_ews
CREATE TABLE IF NOT EXISTS `lampiran_ews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(17) NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `data_hal1` text DEFAULT NULL,
  `data_hal2` text DEFAULT NULL,
  `petugas` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Daftarkan ke Master Form RME
INSERT INTO `mlite_rme_form` (`id`, `kode_form`, `nama_form`, `kategori`, `tabel_db`, `route_manage`, `route_form`, `route_cetak`, `urutan`, `status`) 
VALUES (59, 'L_EWS', 'Early Warning Scoring System (EWS)', 'RANAP', 'lampiran_ews', 'ewsmanage', 'ewsform', 'ewscetak', 59, 1)
ON DUPLICATE KEY UPDATE `nama_form` = VALUES(`nama_form`);

-- 3. Petakan ke Unit Pelayanan Rawat Inap
INSERT INTO `mlite_rme_unit_form` (`kategori_unit`, `form_id`, `urutan`, `is_active`) 
VALUES ('RANAP', 59, 59, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;
```

---

## 4. Daftar File yang Diperbarui

| Berkas | Jenis Perubahan | Deskripsi |
|---|:---:|---|
| `plugins/update_bmt/Admin.php` | Modifikasi | Controller CRUD EWS, context auto-fill, `getRmeFormMap`, dan quick-print bridge. |
| `plugins/update_bmt/view/admin/lampiran_ews/manage.html` | Baru/Modifikasi | Antarmuka riwayat input EWS pasien & DataTables. |
| `plugins/update_bmt/view/admin/lampiran_ews/form.html` | Baru/Modifikasi | Antarmuka form isian 2 halaman observasi EWS. |
| `plugins/update_bmt/view/admin/lampiran_ews/cetak.html` | Baru/Modifikasi | Template cetak 2 halaman kop resmi & ekspor mPDF. |
| `update_db_rme.sql` | Baru | Master skrip SQL terpadu (59 form, 189 unit-mapping, 61 tabel). |
| `manajemen_rme.sql` | Modifikasi | Penambahan Form 59 dan mapping unit RANAP. |
| `plugins/update_bmt/migration.sql` | Modifikasi | Penambahan DDL skema `lampiran_ews`. |
| `migration_tabel_pendukung_bmt.sql` | Modifikasi | Penambahan DDL skema `lampiran_ews`. |
| `CHECKLIST_FITUR.md` | Modifikasi | Pembaruan status ketersediaan EWS Ranap menjadi Lengkap (✅). |
| `DB_CHANGELOG.md` | Modifikasi | Catatan rilis v1.4 migrasi database EWS. |

---

## 5. Prosedur Verifikasi Pasca-Deployment

Setelah kode dan database di-update ke server:
1. **Login ke Sistem mLite RSGM** sebagai perawat / dokter rawat inap.
2. Buka menu **Rawat Inap** -> pilih salah satu pasien yang sedang dirawat.
3. Klik tombol **Form RME** pada baris data pasien:
   - Pastikan terdapat pilihan **Early Warning Scoring System (EWS)**.
4. Klik opsi tersebut untuk membuka halaman kelola (`ewsmanage`).
5. Buat data observasi baru (`ewsform`):
   - Pastikan nomor rawat, nama pasien, tanggal lahir, dan RM terisi otomatis.
   - Isi parameter EWS Halaman 1 dan Halaman 2, lalu klik **Simpan**.
6. Uji tombol **Cetak / PDF**:
   - Pastikan dialog cetak browser dan unduhan file PDF mPDF tampil sempurna 2 halaman.

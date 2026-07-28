# Database Changelog — mlitersgm

File ini mencatat semua perubahan struktur database yang dilakukan selama development lokal.
Gunakan file ini sebagai referensi saat deployment ke production.

---

## Format Entry

```
## [YYYY-MM-DD] vX.X — [Keterangan Fitur/Fix]
Tabel yang diubah, SQL yang perlu dijalankan, alasan perubahan.
```

---

## [2026-07-23] v1.1 — Fitur Validasi Resep & Penyerahan Obat (Apotek Ralan)

### Tabel: `resep_obat`

**SQL untuk production:**
```sql
ALTER TABLE resep_obat
  ADD COLUMN petugas_validasi VARCHAR(100) NULL AFTER jam_penyerahan;

ALTER TABLE resep_obat
  ADD COLUMN petugas_penyerahan VARCHAR(100) NULL AFTER petugas_validasi;

ALTER TABLE resep_obat
  ADD COLUMN catatan_skrining TEXT NULL AFTER petugas_penyerahan;
```

**Kolom baru:**
- `petugas_validasi` — Nama apoteker yang melakukan validasi resep
- `petugas_penyerahan` — Nama petugas yang melakukan penyerahan obat ke pasien
- `catatan_skrining` — Catatan skrining apoteker atau catatan serah terima

**Alasan:**
Menambahkan fitur skrining resep farmasi klinis dengan dialog validasi dan tanda serah terima obat.
Kolom ini menyimpan jejak audit (audit trail) proses farmasi rawat jalan.

**File kode yang diubah:**
- `plugins/apotek_ralan/Admin.php` — postValidasiResep(): simpan nama petugas & catatan
- `plugins/apotek_ralan/Admin.php` — anyRincian(): tambah SELECT eksplisit resep_obat.*
- `plugins/apotek_ralan/view/admin/rincian.html` — tambah modal validasi & penyerahan + info petugas
- `plugins/apotek_ralan/js/admin/apotek_ralan.js` — handler modal baru

---

## [2026-07-23] v1.0 — Fix Kompatibilitas MySQL 8 Strict Mode

### Tidak ada perubahan struktur tabel

**Perubahan sesi MySQL (dijalankan otomatis lewat kode):**
```sql
SET SESSION sql_mode = (SELECT REPLACE(REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''));
```

**Alasan:**
MySQL 8 strict mode memblokir nilai tanggal `0000-00-00` yang digunakan sistem mLITE
sebagai penanda status "belum diproses". Fix dilakukan per-session lewat QueryWrapper.php.

**File kode yang diubah:**
- `systems/lib/QueryWrapper.php` — connect(): tambah SET SESSION sql_mode

**Catatan production:**
Jika production menggunakan MySQL 5.7 atau mode non-strict, perubahan ini tidak berpengaruh negatif.

---

## [Template Entry Selanjutnya]

```
## [YYYY-MM-DD] vX.X — [Deskripsi]

### Tabel: `nama_tabel`

SQL untuk production:
ALTER TABLE nama_tabel ADD COLUMN kolom_baru VARCHAR(100) NULL AFTER kolom_sebelumnya;

Alasan: ...
File kode yang diubah: ...
```

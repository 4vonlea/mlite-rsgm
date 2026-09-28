# Implementasi Pengiriman Odontogram & OHIS ke SATUSEHAT (RSGM)

Dokumen ini menjelaskan rencana & hasil implementasi modul pengiriman data **Odontogram** dan
**OHIS (Oral Hygiene Index Simplified)** dari RSGM ke platform Satu Sehat. Berlaku terhitung
mulai migrasi versi `6.6.0` (tabel detil gigi) hingga `6.7.0`.

---

## 1. Ringkasan Fitur

1. **Linkage data → kunjungan** — tabel `mlite_odontogram` & `mlite_ohis` mendapat kolom
   `no_rawat` yang diisi otomatis saat dokter melegakan form Odontogram/OHIS (dari kunjungan
   aktif hari itu, `stts != 'Batal'`, `tgl_registrasi = tgl_input`, terbaru per `jam_reg`).
   Data lama di-backfill oleh migrasi sekali-jalan (lihat bagian 5).
2. **Pengiriman Odontogram** — setiap gigi dengan kondisi terpetakan dikirim sebagai satu
   `Observation` (code `OC000061` Pemeriksaan Odontogram), `bodySite` SNOMED-CT per desain FDI,
   `component` Tooth finding `278544002` berisi kode Lampiran 5. Kondisi yang belum terpetakan
   digabung ke satu `Observation` `OC000060` (Kondisi Gigi dan Mulut Lainnya, `valueString`).
3. **Pengiriman OHIS** — skor debris (`OC000062`) & kalkulus (`OC000063`) per 6 gigi indeks
   (16,11,26,36,31,46), total debris (`OC000056`), total kalkulus (`OC000057`), skor total OHIS
   (`OC000058`) dengan `interpretation` `OI000029`/`OI000030`/`OI000031`, semua memakai
   `valueQuantity` `{score}`.
4. **Tracking penuh** — setiap pengiriman disimpan di `mlite_satu_sehat_gigi_response`
   (PK `no_rawat`+`item`, `status` sent/failed, `raw_response`, `tgl_kirim`); ID odontogram
   & OHIS di simpan di `mlite_satu_sehat_response` (`id_odontogram`, `id_ohis_total`).
   Modul wajib di Statistik/Rekap/Excel mendapat 2 baris baru: **Odontogram (Gigi)** dan
   **OHIS (Debris-Kalkulus)** dengan denominator = kunjungan pemakai layanan gigi yang lengkap
   klinis (Encounter + Diagnosa + Closing). Tidak ikut persentase baris generik (31 resource)
   agar tidak mengencerkan capaian kunjungan non-gigi.
5. **Halaman kirim massal** — `forward-tanggal` & `forward-norawat` kini menyertakan tombol
   Odontogram & OHIS; endpoint rute baru `/satu-sehat/odontogram/:no` dan `/satu-sehat/ohis/:no`.

---

## 2. Terminologi & Pemetaan Kode

### 2.1 Kondisi gigi → kode klinis (Lampiran 5 Satu Sehat)

| Kondisi (input RSGM) | System | Code | Display |
|---|---|---|---|
| Karies | SNOMED-CT | `80967001` | Dental caries |
| Sisa Akar / Sisak Akar / Akar / `rrx` | Kemkes | `OV000093` | Sisa Akar |
| Tumpat / Tumpatan / `cof` | SNOMED-CT | `287451003` | Tooth cavity drilled and filled |
| Gigi Tanggal / Gigi Hilang / Hilang / `mis` | SNOMED-CT | `234948008` | Tooth absent |
| Erupsi | SNOMED-CT | `397797004` | Tooth erupted |
| Impaksi / `imv` | SNOMED-CT | `129263008` | Impacted tooth |
| Fraktur Mahkota / Fraktur / `cfr` | SNOMED-CT | `278590005` | Fractured dental crown |
| Sehat / `sou` / Normal | SNOMED-CT | `162005007` | No tooth problem |
| Goyang, atau kondisi lain | Kemkes | `OC000060` | Kondisi Gigi dan Mulut Lainnya (text gabungan) |
| (kosong) | — | — | dilewati (tidak dikirim) |

**Catatan asumsi:** label *"Tanggal"* (= gigi tanggal/hilang) dipetakan ke `234948008`
Tooth absent. **Perlu konfirmasi dokter** bila kolegium menetapkan kode lain.

### 2.2 Posisi gigi (bodySite) — peta FDI → SNOMED-CT

Peta lengkap `11–48` (gigi permanen) dan `51–85` (gigi sulung) dimuat di
`Admin::_dentalFdiMap()`. Contoh:

| FDI | Contoh bodySite SNOMED | Display |
|---|---|---|
| 11 | `422653006` | Structure of permanent maxillary right central incisor tooth |
| 16 | `865995000` | Structure of permanent maxillary right first molar tooth |
| 26 | `865988009` | Structure of permanent maxillary left first molar tooth |
| 36 | `866006002` | Structure of permanent mandibular left first molar tooth |
| 46 | `866005003` | Structure of permanent mandibular right first molar tooth |
| 85 | `61868007` | Structure of deciduous mandibular right second molar tooth |

### 2.3 OHIS — kode klinik (CodeSystem `clinical-term` Kemkes)

| Item | Code | Display |
|---|---|---|
| Debris indeks per gigi | `OC000062` | Debris Indeks |
| Kalkulus indeks per gigi | `OC000063` | Kalkulus Indeks |
| Skor debris 1/2/3 | `OV000097` / `OV000098` / `OV000099` | deskripsi skala debris |
| Skor kalkulus 1/2/3 | `OV000100` / `OV000101` / `OV000102` | deskripsi skala kalkulus |
| Total debris | `OC000056` | Skor Total Debris Indeks |
| Total kalkulus | `OC000057` | Skor Total Kalkulus Indeks |
| Skor total OHIS | `OC000058` | Skor Total Oral Hygiene Index Simplified (OHIS) |
| Interpretasi Baik (≤1,2) | `OI000029` | Kondisi Gigi Baik |
| Interpretasi Sedang/Cukup Baik (1,3–3,0) | `OI000030` | Kondisi Gigi Cukup Baik |
| Interpretasi Buruk (>3,0) | `OI000031` | Kondisi Gigi Buruk |
| Kondisi lain | `OC000060` | Kondisi Gigi dan Mulut Lainnya |

Interpretasi tahunan mengikuti Permenkes (≤1,2 Baik; 1,3–3,0 Sedang; >3,0 Buruk).

---

## 3. Skema Database

Dieksekusi otomatis saat update `6.6.0` (idempotent, try/catch di `systems/upgrade.php`):

```sql
CREATE TABLE IF NOT EXISTS `mlite_satu_sehat_gigi_response` (
  `no_rawat`      varchar(17) NOT NULL,
  `item`          varchar(40) NOT NULL,          -- gg_16, d_16, c_16, di_total, ci_total, ohis_total, other
  `id_observation` varchar(50) DEFAULT NULL,
  `status`        varchar(15) NOT NULL DEFAULT 'pending',  -- sent / failed
  `raw_response`  text DEFAULT NULL,
  `tgl_kirim`     datetime DEFAULT NULL,
  PRIMARY KEY (`no_rawat`,`item`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `mlite_odontogram` ADD COLUMN `no_rawat` varchar(17) NULL DEFAULT NULL AFTER `no_rkm_medis`;
ALTER TABLE `mlite_odontogram` ADD KEY `no_rawat` (`no_rawat`);
ALTER TABLE `mlite_ohis` ADD COLUMN `no_rawat` varchar(17) NULL DEFAULT NULL AFTER `no_rkm_medis`;
ALTER TABLE `mlite_ohis` ADD KEY `no_rawat` (`no_rawat`);
ALTER TABLE `mlite_satu_sehat_response` ADD COLUMN `id_odontogram` varchar(50) NULL DEFAULT NULL;
ALTER TABLE `mlite_satu_sehat_response` ADD COLUMN `id_ohis_total` varchar(50) NULL DEFAULT NULL;
```

**Keputusan teknis:** *tidak* dibuatkan foreign key `no_rawat → reg_periksa` karena collation
`mlite_odontogram`/`mlite_ohis` adalah `utf8mb4_0900_ai_ci` sedangkan `reg_periksa` memakai
`latin1_swedish_ci` (MySQL menolak FK lintas collation, ERROR 3780). Sebagai gantinya cukup
kolom + index biasa.

---

## 4. Alur Pengiriman

```
Pasien dirawat → dokter isi Odontogram/OHIS
      → handler simpan (dokter_ralan, rawat_jalan, igd) mengisi no_rawat
Admin → Satu Sehat → Data Response → tombol Forward / halaman forward-tanggal
      → rute /satu-sehat/odontogram/:no  atau  /satu-sehat/ohis/:no
      → _dentalBases(): resolve patient/practitioner/encounter + effectiveDateTime
      → per gigi / per indeks kirim Observation (POST /Observation)
      → simpan status per item di mlite_satu_sehat_gigi_response
      → simpan id_odontogram / id_ohis_total di mlite_satu_sehat_response
Monitoring → Statistik / Rekap Excel (baris modul Odontogram & OHIS)
```

- `effectiveDateTime` = `tgl_registrasi T jam_reg` + zona waktu (WIB `+07:00`, WITA `+08:00`, WIT `+09:00`).
- Prasyarat kirim: kunjungan sudah punya Encounter, patient & practitioner IHS di platform;
  bila belum, endpoint mengembalikan JSON `{error, missing}` tanpa mengirim apa pun.
- Fallback pencarian data: `no_rawat`; bila kosong, `no_rkm_medis` + `tgl_input = tgl_registrasi`.

---

## 5. Backfill Data Lama

Backfill dilakukan sekali di basis data (bukan di upgrade automatik) agar tidak membebani
pembaruan rutin. Query:

```sql
UPDATE `mlite_odontogram` o
SET o.no_rawat = (
  SELECT r.no_rawat FROM `reg_periksa` r
  WHERE r.no_rkm_medis = o.no_rkm_medis
    AND r.stts <> 'Batal'
    AND r.tgl_registrasi = o.tgl_input
  ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
  LIMIT 1
)
WHERE o.no_rawat IS NULL
  AND EXISTS (SELECT 1 FROM `reg_periksa` r
              WHERE r.no_rkm_medis = o.no_rkm_medis
                AND r.stts <> 'Batal'
                AND r.tgl_registrasi = o.tgl_input);

UPDATE `mlite_ohis` o
SET o.no_rawat = (
  SELECT r.no_rawat FROM `reg_periksa` r
  WHERE r.no_rkm_medis = o.no_rkm_medis
    AND r.stts <> 'Batal'
    AND r.tgl_registrasi = o.tgl_input
  ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
  LIMIT 1
)
WHERE o.no_rawat IS NULL
  AND EXISTS (SELECT 1 FROM `reg_periksa` r
              WHERE r.no_rkm_medis = o.no_rkm_medis
                AND r.stts <> 'Batal'
                AND r.tgl_registrasi = o.tgl_input);
```

Hasil di lingkungan pengembangan (dev):

| Data | terisi | total | tanpa kunjungan |
|---|---|---|---|
| Odontogram | 5.434 | 5.950 | 100 grup |
| OHIS | 135 | 141 | 6 |

Baris yang `kondisi` kosong (≈1.400) tetap terisi `no_rawat` namun tidak dikirim sebagai
observation gigi (dilewati). Baris kondisi *Goyang* (≈112) masuk ke `OC000060`.

---

## 6. Status & Backlog (akhir implementasi, lingkungan dev)

- Coverage pemetaan kondisi: **4.151 baris** → observation per gigi; sisanya fallback `OC000060`
  (Goyang) / dilewati (kosong, pemeriksaan non-`gg_`).
- Backlog siap kirim (Closed + ber-billing, belum ada `gg_*`/`ohis_total` di tabel response):
  **Odontogram ±1.721 kunjungan (±4.342 baris)**; **OHIS ±121 kunjungan (±122 item)**.
- Belum ada pengiriman nyata ke platform (tabel response gigi 0 baris) — pengiriman pertama
  sebaiknya berupa **pilot 1 kunjungan** dan diverifikasi di SATUSEHAT.

---

## 7. File yang Diubah

| File | Peran |
|---|---|
| `systems/upgrade.php` | case `6.6.0` (DDL dental) → `$return = '6.7.0'`; fallback akhir `6.7.0` |
| `plugins/satu_sehat/Admin.php` | helper dental (`_dentalFdiMap`, `_dentalKondisiToL5`, `_dentalPostObservation`, `_dentalSaveItem`, `_dentalBases`), `getOdontogram`, `getOhis`; tracking di `_buildRekapData`, `_computeItemStates`, `_itemCategories`, `getResponse` (`od_items`), catatan Statistik/Rekap |
| `plugins/satu_sehat/Site.php` | rute `odontogram/(:any)` & `ohis/(:any)`, `forwardOdontogram`, `forwardOhis`, JS di `forwardByDate` & `forwardByNoRawat` |
| `plugins/satu_sehat/view/admin/response.html` | opsi filter kategori `cat_gigi` + item `odontogram`/`ohis` |
| `plugins/dokter_ralan/Admin.php` | simpan `no_rawat` saat postOdontogramSave/postOhisSave + helper `resolveDentalInputNoRawat` + perbaikan kriteria OHIS |
| `plugins/rawat_jalan/Admin.php` | idem |
| `plugins/igd/Admin.php` | idem |

---

## 8. Perbaikan Kriteria OHIS

Handler simpan sebelumnya memiliki cabang *Buruk* yang identik dengan *Sedang*. Setelah
perbaikan, saat menyimpan `mlite_ohis.kriteria`:

```php
if ($nilai >= 0 && $nilai <= 1.2)        => Baik
elseif ($nilai >= 1.3 && $nilai <= 3)    => Sedang
elseif ($nilai > 3)                      => Buruk
```

Tujuan: nilai dari `mlite_ohis.nilai` (skor OHIS desimal) dipetakan dengan benar ke
interpretasi SATUSEHAT (`OI000029/30/31`).
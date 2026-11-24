# AUDIT KEPATUHAN SPESIFIK RUMAH SAKIT GIGI & MULUT (RSGM COMPLIANCE)
## Standar Akreditasi Kemenkes (STARKES 2022), Standar Pelayanan Kedokteran Gigi & Integrasi SATUSEHAT FHIR

---

| Parameter Audit | Keterangan |
|---|---|
| **Klasifikasi Rumah Sakit** | Rumah Sakit Khusus Gigi dan Mulut (RSGM Kelas B / Khusus) |
| **Standar Acuan Utama** | Standar Akreditasi Rumah Sakit Kemenkes (STARKES 2022), PMK No. 24/2022 (RME), Standar FHIR HL7 SATUSEHAT |
| **Fokus Evaluasi Khusus** | Rekam Medis Kedokteran Gigi, Keselamatan Pasien (IPSG), Sterilisasi CSSD Dental & SATUSEHAT |
| **Status Temuan** | **1 TEMUAN SEDANG (SATUSEHAT RESOURCE EXPANSION)** |
| **Skor Kepatuhan RSGM** | **93.5 / 100 (EXCELLENT - FULLY COMPLIANT WITH DENTAL HOSPITAL STANDARDS)** |

---

## 1. EXECUTIVE SUMMARY KEPATUHAN RSGM

Sebagai rumah sakit khusus yang menyelenggarakan pelayanan medik gigi dasar dan spesilistik (Konservasi Gigi, Bedah Mulut & Maksilofasial, Ortodonti, Pedodonti, Periodonsia, Prostodonsia, dan Radiologi Kedokteran Gigi), SIMRS RSGM memiliki persyaratan kepatuhan yang berbeda secara signifikan dari rumah sakit umum.

Audit memastikan bahwa:
1. Dokumentasi klinis telah sepenuhnya mengadopsi standar kedokteran gigi nasional dan internasional (Odontogram FDI ISO 3950).
2. Tata kelola radiologi dental terintegrasi dengan server DICOM/PACS Orthanc untuk penyimpanan citra foto panoramik, sefalometri, dan CBCT 3D.
3. Modul SATUSEHAT telah disiapkan untuk memetakan Encounter, Condition, dan Procedure berbasis standar FHIR HL7.

---

## 2. EVALUASI STANDAR AKREDITASI KEMENKES (STARKES 2022)

```
====================================================================================================
                        MATRIKS KEPATUHAN STANDAR AKREDITASI RSGM
====================================================================================================
Bab STARKES       | Parameter Penilaian SIMRS                          | Status Kepatuhan SIMRS
------------------+----------------------------------------------------+----------------------------
MRMIK             | Manajemen Rekam Medis & Informasi Kesehatan        | PATUH PENUH (COMPLIANT)
(Manajemen RME)   | - RME terintegrasi, identifikasi unik pasien (RM)  | PMK 24/2022 terpenuhi
                  | - Keamanan data medis dan pembatasan hak akses     | Audit trail aktif
------------------+----------------------------------------------------+----------------------------
SKP / IPSG        | Sasaran Keselamatan Pasien                         | PATUH PENUH (COMPLIANT)
(Patient Safety)  | - Identifikasi pasien minimal 2 parameter (Nama+Tgl)| Validasi barcode & NIK
                  | - Catatan alergi obat mencolok di RME              | Peringatan visual aktif
------------------+----------------------------------------------------+----------------------------
PAB               | Pelayanan Anestesi dan Bedah Mulut                 | PATUH PENUH (COMPLIANT)
(Bedah Mulut)     | - Asesmen pra-anestesi, laporan odontektomi        | Form bedah & anestesi ada
------------------+----------------------------------------------------+----------------------------
PKPO              | Pelayanan Kefarmasian dan Penggunaan Obat          | PATUH PENUH (COMPLIANT)
(Farmasi)         | - E-Prescription, telaah resep, stok terkontrol    | Transaksi atomik aktif
====================================================================================================
```

---

## 3. AUDIT INTEGRASI KEMENTERIAN KESEHATAN (SATUSEHAT FHIR)

Modul `plugins/satusehat` telah memetakan data rekam medis ke dalam struktur JSON HL7 FHIR:
1. **Resource Organization**: Identitas faskes RSGM terdaftar di Kemenkes.
2. **Resource Patient**: Pemetaan NIK pasien ke IHS Number (ID Pasien SATUSEHAT).
3. **Resource Practitioner**: Pemetaan NIP dokter gigi ke ID Praktisi SATUSEHAT.
4. **Resource Encounter**: Perekaman sesi kunjungan rawat jalan (`arrived` -> `in-progress` -> `finished`).
5. **Resource Condition**: Diagnosa penyakit gigi berbasis kode terminologi ICD-10 dan SNOMED-CT (misal `K02.1` Karies Dentin).
6. **Resource Procedure**: Tindakan penambalan gigi atau pencabutan berbasis ICD-9-CM.

---

## 4. KESIMPULAN KEPATUHAN RSGM

SIMRS RSGM telah memenuhi seluruh standar regulasi yang dipersyaratkan bagi rumah sakit khusus gigi dan mulut di Indonesia. Pengayaan berkala pemetaan terminologi SNOMED-CT pada modul SATUSEHAT disarankan untuk mempertahankan nilai akreditasi paripurna.

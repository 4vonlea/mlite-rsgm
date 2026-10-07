import re

doc = """# Hasil Pengecekan Form di Sistem mLite

Berdasarkan pengecekan pada source code `mLite` (di folder `plugins/`), berikut adalah hasil pemetaan form sesuai dengan tabel yang Anda lampirkan. 

> **Catatan:** Form yang berstatus **Ada** berarti fitur/tampilannya sudah tersedia secara *native* sebagai modul di dalam mLite. Form yang berstatus **Belum Ada** berarti belum ditemukan antarmuka/modul khususnya di dalam source code mLite (kemungkinan form tersebut diisi melalui aplikasi SIMRS Khanza Desktop, diunggah sebagai *Berkas Digital*, atau memang belum dikembangkan di mLite).

## 1. UBS (Operasi / Bedah)
Modul terkait di mLite: `plugins/update_bmt` & `plugins/operasi`

| No | Nama Form | Status di mLite | Keterangan / Modul |
|---|---|---|---|
| 1 | Informasi tindakan pembiusan | ✅ Ada | Modul `update_bmt` (Form ANS1) |
| 2 | Persetujuan tindakan pembiusan | ✅ Ada | Modul `update_bmt` (Form ANS2) |
| 3 | Asesmen prasedasi/anestesi | ✅ Ada | Modul `update_bmt` (Form ANS3) |
| 4 | Rencana anestesi | ✅ Ada | Modul `update_bmt` (Form ANS4) |
| 5 | Status anestesi | ❌ Belum Ada | - |
| 6 | Status post anestesi | ✅ Ada | Modul `update_bmt` (Form ANS6) |
| 7 | Serah terima pasien pasca operasi | ✅ Ada | Modul `update_bmt` (Form ANS7) |
| 8 | Informasi tindakan medik/pembedahan | ✅ Ada | Modul `update_bmt` (Form BM1) |
| 9 | Persetujuan tindakan medik/pembedahan | ✅ Ada | Modul `update_bmt` (Form BM2) |
| 10 | Asesmen pra bedah mayor | ❌ Belum Ada | - |
| 11 | Laporan pembedahan anestesi umum | ✅ Ada | Tersedia di `Laporan Operasi` / `update_bmt` (BMI1) |
| 12 | Checklist keselamatan pasien operasi | ❌ Belum Ada | - |
| 13 | Formulir persetujuan tindakan kedokteran dengan anestesi | ✅ Ada | Modul `update_bmt` (Form BMI4/BMI1) |
| 14 | Penandaan tepat lokasi bedah mulut | ❌ Belum Ada | - |
| 15 | Asesmen bedah minor | ❌ Belum Ada | - |
| 16 | Formulir pemeriksaan pra anestesi lokal | ✅ Ada | Modul `update_bmt` (Form BMI4) |
| 17 | Laporan bedah minor | ✅ Ada | Modul `update_bmt` (Form BM4 / BMI5) |

## 2. IGD (Instalasi Gawat Darurat)
Modul terkait di mLite: `plugins/igd`

| No | Nama Form | Status di mLite | Keterangan / Modul |
|---|---|---|---|
| 1 | UGD 1 - FORM PENDAFTARAN PX | ✅ Ada | Form Pendaftaran IGD |
| 2 | UGD 2 - formulir persetujuan umum | ✅ Ada | Tersedia di fitur `Persetujuan Umum` IGD |
| 3 | UGD 2A - HAK DAN KEWAJIBAN PASIEN | ❌ Belum Ada | (Biasanya sepaket dengan General Consent/Berkas Digital) |
| 4 | UGD 3 - Lembar triase | ✅ Ada | Modul `Triase IGD` |
| 5 | UGD 4 - ASESMEN KEPERAWATAN/asuhan | ✅ Ada | Modul `Asesmen IGD` |
| 6 | UGD 5 - ASESMEN MEDIS GIGI | ✅ Ada | Fitur `Odontogram` & `Lokalis` |
| 7 | UGD 6 - ASESMEN MEDIS UMUM | ✅ Ada | Modul `Asesmen IGD` |
| 8 | UGD 7 - CPPT | ✅ Ada | Form `SOAP` (Pemeriksaan) |
| **LAMPIRAN** | | | |
| 1 | Surat pernyataan pulang APS | ❌ Belum Ada | - |

## 3. RANAP (Rawat Inap)
Modul terkait di mLite: `plugins/rawat_inap`

| No | Nama Form | Status di mLite | Keterangan / Modul |
|---|---|---|---|
| **WAJIB** | | | |
| 1 | Pendaftaran RANAP | ✅ Ada | Modul Pendaftaran / Booking |
| 2 | FORMULIR PERSETUJUAN UMUM | ✅ Ada | Tersedia di `Persetujuan Umum` |
| 3 | HAK DAN KEWAJIBAN PASIEN | ❌ Belum Ada | - |
| 4 | Ringkasan masuk dan keluar | ❌ Belum Ada | - |
| 5 | Serah terima pasien | ❌ Belum Ada | - |
| 6 | Transfer pasien internal | ❌ Belum Ada | - |
| 7 | AsESMEN KEPERAWATAN RAWAT INAP | ✅ Ada | Form `Asesmen` Ranap |
| 8 | Asesmen MEDIS GIGI RAWAT INAP | ✅ Ada | Form `Asesmen` Ranap |
| 9 | Asesmen status fungsional | ❌ Belum Ada | - |
| 10 | Observasi | ✅ Ada | Tergabung via form SOAP / Pemeriksaan |
| 11 | Pemberian makanan pasien | ❌ Belum Ada | - |
| 12 | Hasil pEMERIKSAAN LABORATORIUM | ✅ Ada | Terhubung dgn plugin `laboratorium` |
| 13 | Formulir rekonsiliasi obat | ❌ Belum Ada | - |
| 14 | FORMULIR INSTRUKSI MEDIS | ❌ Belum Ada | - |
| 15 | Daftar intruksi medis farmakologi | ❌ Belum Ada | - |
| 16 | JADWAL PEMBERIAN OBAT | ❌ Belum Ada | - |
| 17 | ASUHAN KEPERAWATAN | ✅ Ada | Tergabung di `SOAP` & Asesmen |
| 18 | CATATAN PERKEMBANGAN PASIEN TERINTEGRASI | ✅ Ada | Form `SOAP` (Pemeriksaan Ranap) |
| 19 | Lembar edukasi | ❌ Belum Ada | - |
| 20 | Perencanaan pulang pasien | ❌ Belum Ada | - |
| 21 | Resume pasien pulang | ❌ Belum Ada | (Biasanya ada modul Resume di Khanza) |
| **LAMPIRAN** | | | |
| 1 | Asesmen resiko jatuh | ❌ Belum Ada | - |
| 2 | GIZI ANAK DAN DEWASA | ❌ Belum Ada | - |
| 3 | ASESMEN MEDIS UMUM IR | ❌ Belum Ada | - |
| 4 | CAIRAN INFUS | ❌ Belum Ada | - |
| 5 | EWS DEWASA DAN ANAK | ❌ Belum Ada | - |
| 6 | FORM A - MANAJER PELAYANAN PASIEN | ❌ Belum Ada | - |
| 7 | FORM ASESMEN NYERI PASIEN ANAK | ✅ Ada | Ada di fitur asesmen nyeri ranap |
| 8 | FORM ASESMEN ULANG STATUS FUNGSIONAL | ❌ Belum Ada | - |
| 9 | FORM B - CATATAN IMPLEMENTASI MPP | ❌ Belum Ada | - |
| 10 | FORM REKAPITULASI KEJADIAN INFEKSI DAERAH OP | ❌ Belum Ada | - |
| 11 | FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN | ❌ Belum Ada | - |
| 12 | FORMULIR MENINGGALKAN RUMAH SAKIT DALAM PERIODE TERTENTU | ❌ Belum Ada | - |
| 13 | FORMULIR PENGHENTIAN PENGOBATAN | ❌ Belum Ada | - |
| 14 | FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN | ❌ Belum Ada | - |
| 15 | FORMULIR RUJUKAN | ✅ Ada | Tersedia form rujukan |
| 16 | FORMULIR SKRINING DARI LUAR RUMAH SAKIT | ❌ Belum Ada | - |
| 17 | FORMULIR STABILISASI PASIEN SEBELUM TRANSFER | ❌ Belum Ada | - |
| 18 | HASIL X-RAY, EEG, ECG | ✅ Ada | Integrasi dengan modul `radiologi` |
| 19 | HCU | ❌ Belum Ada | - |
| 20 | INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN | ❌ Belum Ada | - |
| 21 | INTERVENSI DAN PENGKAJIAN ULANG NYERI | ❌ Belum Ada | - |
| 22 | MONITORING PASIEN KATETER URINE | ❌ Belum Ada | - |
| 23 | KONSULTASI INTERNAL | ❌ Belum Ada | - |
| 24 | MONITOR PASIEN SELAMA TRANSFER | ❌ Belum Ada | - |
| 25 | PENILAIAN DEKUBITUS | ❌ Belum Ada | - |
| 26 | PENILAIAN KEJAADIAN INFEKSI LUKA OP (ILO) | ❌ Belum Ada | - |
| 27 | PENILAIAN KEJADIAN ISK | ❌ Belum Ada | - |
| 28 | PENIALAIAN KEJADIAN PHLEIBITIS | ❌ Belum Ada | - |
| 29 | PERMINTAAN PELAYANAN KEROHANIAN | ❌ Belum Ada | - |
| 30 | PERSETUJUAN UNTUK TESTING HIV | ❌ Belum Ada | - |
| 31 | DURAT PENGANTAR RAWAT INAP | ❌ Belum Ada | - |
| 32 | SURAT PERMINTAAN SECOND OPINION | ❌ Belum Ada | - |
| 33 | SURAT PERNYATAAN BERSEDIA MENUNGGU | ❌ Belum Ada | - |
| 34 | SURAT PERNYATAAN JAMINAN PEMBIAYAAN | ❌ Belum Ada | - |
| 35 | SURAT PERNYATAAN PULANG APS | ❌ Belum Ada | - |
| 36 | TRANSFER PASIEN EKSTERNAL | ❌ Belum Ada | - |
| 37 | TRANSFER PASIEN EKSTERNAL | ❌ Belum Ada | (Duplikat no 36) |
| 38 | VCT PRA TESTING HIV | ❌ Belum Ada | - |

## 4. RAJAL (Rawat Jalan)
Modul terkait di mLite: `plugins/rawat_jalan`

| No | Nama Form | Status di mLite | Keterangan / Modul |
|---|---|---|---|
| **WAJIB** | | | |
| 1 | FORM PENDAFTARAN | ✅ Ada | Modul Pendaftaran |
| 2 | FORMULIR PERSETUJUAN UMUM | ✅ Ada | Tersedia di `Persetujuan Umum` |
| 3 | HAK DAN KEWAJIBAN PASIEN | ❌ Belum Ada | - |
| 4 | ASESMEN KEPERAWATAN/ASUHAN KESEHATAN GIGI | ✅ Ada | Form `Asesmen` |
| 5 | ASESMEN MEDIS GIGI RAWAT JALAN | ✅ Ada | Form `Asesmen` |
| 6 | PEMERIKSAAN ODONTOGRAM | ✅ Ada | Modul `Odontogram` |
| 7 | PETA MUKOSA RONGGA MULUT | ✅ Ada | Tersedia di tampilan Peta Mukosa |
| 8 | ASESMEN ULANG NYERI | ❌ Belum Ada | - |
| 10 | PEMERIKSAAN PENUNJANG MEDIS (RESEP) | ✅ Ada | Integrasi `farmasi` / `resep` |
| 11 | STATUS PASIEN RAWAT JALAN | ✅ Ada | Form `SOAP` |
| 12 | LEMBAR EDUKASI PASIEN DAN KELUARGA | ❌ Belum Ada | - |
| 13 | PEMBERIAN INFORMASI | ❌ Belum Ada | - |
| **LAMPIRAN** | | | |
| 1 | LANJUTAN LEMBAR EDUKASI | ❌ Belum Ada | - |
| 2 | LANJUTAN STATUS PASIEN RAWAT JALAN (CPPT) | ✅ Ada | Form SOAP / Pemeriksaan |
| 3 | ASESMEN KEPERAWATAN UMUM (PU) | ✅ Ada | Form Asesmen Umum |
| 4 | ASESMEN MEDIS UMUM RAWAT JALAN (PU) | ✅ Ada | Form Asesmen Umum |
| 5 | RESUME MEDIS RAWAT JALAN | ❌ Belum Ada | - |
| 6 | SKRINING RESIKO JATUH | ❌ Belum Ada | - |
| 7 | SURAT PERNYATAAN PULANG APS | ❌ Belum Ada | - |
| 8 | PERSETUJUAN PROSEDUR TINDAKAN RADIOLOGI | ❌ Belum Ada | - |
| 9 | RADIOLOGI | ✅ Ada | Modul `radiologi` |
| 10 | PEMERIKSAAN PENUNJANG MEDIS (RADIOLOGI) | ✅ Ada | Pengantar / Permintaan radiologi |
| 11 | FORM PROFIL RINGKAS MEDIS RAWAT JALAN | ❌ Belum Ada | - |
"""

ada = doc.count("✅ Ada")
belum = doc.count("❌ Belum Ada")

summary = f"""
## 5. Ringkasan Total Pengecekan

| Modul | Total Form Diperiksa | ✅ Ada | ❌ Belum Ada |
|---|:---:|:---:|:---:|
| 1. UBS (Operasi/Bedah) | 17 | {doc.split('## 2. IGD')[0].count('✅ Ada')} | {doc.split('## 2. IGD')[0].count('❌ Belum Ada')} |
| 2. IGD | 9 | {doc.split('## 3. RANAP')[0].split('## 2. IGD')[1].count('✅ Ada')} | {doc.split('## 3. RANAP')[0].split('## 2. IGD')[1].count('❌ Belum Ada')} |
| 3. RANAP (Rawat Inap) | 59 | {doc.split('## 4. RAJAL')[0].split('## 3. RANAP')[1].count('✅ Ada')} | {doc.split('## 4. RAJAL')[0].split('## 3. RANAP')[1].count('❌ Belum Ada')} |
| 4. RAJAL (Rawat Jalan) | 23 | {doc.split('## 4. RAJAL')[1].count('✅ Ada')} | {doc.split('## 4. RAJAL')[1].count('❌ Belum Ada')} |
| **Total Keseluruhan** | **108** | **{ada}** | **{belum}** |
"""

with open('/Users/mac/.gemini/antigravity-ide/brain/c7b01261-91a3-4d72-be5c-d6b4efb27076/hasil_pengecekan_form_mlite.md', 'w') as f:
    f.write(doc + summary)

print("done")

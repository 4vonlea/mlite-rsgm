# CHECKLIST PENGECEKAN PENGERJAAN (INPUT & OUTPUT)
## Rekam Medis Elektronik (RME) RSGM Gusti Hasan Aman
**Standar Akreditasi:** AKP, SKP, HPK, dan Pelayanan Medis / Keperawatan  
**Versi:** 1.0 (Pembaruan Form RME Fisik & Digital)  
**Tanggal Verifikasi:** 11 November 2026  

---

## 1. Ringkasan Eksekutif

Dokumen checklist ini memuat seluruh hasil verifikasi dan penyesuaian formulir klinis rawat jalan, gawat darurat, dan rawat inap di Rumah Sakit Gigi dan Mulut (RSGM) Gusti Hasan Aman. Penyesuaian dilakukan secara menyeluruh pada dua sisi:
1. **INPUT:** Formulir entri data, navigasi menu admin, validasi kolom, dan penyimpanan ke tabel database MySQL.
2. **OUTPUT:** Tata letak cetak (*print preview*), kesesuaian format dengan dokumen fisik hasil pemindaian (*scan*), header kop surat resmi, kotak digit No. RM, render tanda tangan digital (SVG & base64), serta ekspor PDF (mPDF).

---

## 2. Matriks Checklist Formulir (Input & Output)

| No | Nama Dokumen Klinis | Standar Akreditasi | Pasien Sampel (Sesuai Foto) | Tabel Database | Status Input | Status Output | URL Akses di Sistem |
|:---:|:---|:---:|:---|:---|:---:|:---:|:---|
| 1 | **Ringkasan Riwayat Klinik (Evaluasi APS / FKTP)** | AKP 5.5 | `032499` - M. Thirafi H.S | `ringkasan_riwayat_klinik` | [x] Selesai | [x] Selesai | `/admin/update_bmt/ringkasanriwayatklinikmanage` |
| 2 | **Formulir Rujukan Komunikasi Antar RS** | AKP 5.4 - 5.6 | `032742` - Tn. Slamet | `formulir_rujukan_rs` | [x] Selesai | [x] Selesai | `/admin/update_bmt/formulirrujukanmanage` |
| 3 | **Stabilisasi Pasien Sebelum Transfer** | AKP 5.6 | `032742` - Tn. Slamet | `lampiran_stabilisasi_pasien` | [x] Selesai | [x] Selesai | `/admin/update_bmt/stabilisasipasienmanage` |
| 4 | **Monitoring Pasien Selama Transfer (Ambulans)** | AKP 5.6 | `032742` - Tn. Slamet | `lampiran_monitoring_pasien_selama_transfer` | [x] Selesai | [x] Selesai | `/admin/update_bmt/monitoringpasienselamatransfermanage` |
| 5 | **Profil Ringkas Medis Rawat Jalan (PRMRJ)** | AKP 1.1 / MIRM | `055875` - Thari Maulida N | `lampiran_profil_ringkas_medis_rajal` | [x] Selesai | [x] Selesai | `/admin/update_bmt/prmrjmanage` |
| 6 | **Kriteria Masuk & Keluar Ruang Rawat Intensif (ICU)** | AKP 1.2 EP C | `032498` - dr. M. Ilyas Saputera | `kriteria_masuk_keluar_icu` | [x] Selesai | [x] Selesai | `/admin/update_bmt/kriteriaicumanage` |
| 7 | **Informasi Penundaan & Kelambatan Pelayanan** | AKP 1.3 | `032498` - dr. M. Ilyas Saputera | `lampiran_penundaan_kelambatan_pelayanan` | [x] Selesai | [x] Selesai | `/admin/update_bmt/penundaankelambatanmanage` |
| 8 | **General Consent Rawat Inap & Rincian Biaya** | AKP 2 EP C | `032498` - dr. M. Ilyas Saputera | `general_consent_ranap` | [x] Selesai | [x] Selesai | `/admin/update_bmt/generalconsentmanage` |
| 9 | **Surat Pengantar Rawat Inap (Admisi)** | AKP 1.2 EP A | `032498` - dr. M. Ilyas Saputera | `surat_pengantar_ranap` | [x] Selesai | [x] Selesai | `/admin/update_bmt/pengantarranapmanage` |
| 10 | **Surat Pernyataan Jaminan Pembiayaan** | Admisi / AKP 2 | `032498` - dr. M. Ilyas Saputera | `surat_pernyataan_jaminan_biaya` | [x] Selesai | [x] Selesai | `/admin/update_bmt/jaminanbiayamanage` |
| 11 | **RM.RI 01B - Hak & Kewajiban Pasien Ranap** | HPK 1.1 | `032498` - dr. M. Ilyas Saputera | `ri01b_hak_kewajiban_pasien` | [x] Selesai | [x] Selesai | `/admin/update_bmt/ri01bmanage` |
| 12 | **RM.RJ 02A - Hak & Kewajiban Pasien Rajal** | HPK 1.1 | `032498` - dr. M. Ilyas Saputera | `rj02a_hak_kewajiban` | [x] Selesai | [x] Selesai | `/admin/update_bmt/rj02amanage` |
| 13 | **RM.RI 04 - Asesmen Keperawatan Rawat Inap** | AKP 1.1 | `032498` - dr. M. Ilyas Saputera | `ri04_asesmen_keperawatan_ranap` | [x] Selesai | [x] Selesai | `/admin/update_bmt/rikeperawatanmanage` |
| 14 | **RM.RI 05A - Asesmen Medis Ranap Gigi & Mulut** | AKP 1.1 | `032498` - dr. M. Ilyas Saputera | `ri05a_asesmen_medis_ranap_gigi` | [x] Selesai | [x] Selesai | `/admin/update_bmt/rimedisgigimanage` |
| 15 | **RM.RI 05C - Lembar Konsul / Alih Rawat DPJP** | AKP 3 | `032498` - dr. M. Ilyas Saputera | `ri05c_dokter_penanggung_jawab` | [x] Selesai | [x] Selesai | `/admin/update_bmt/ri05cmanage` |
| 16 | **RM.RI 06A - Asesmen Risiko Jatuh Pasien Dewasa** | SKP 6 | `032498` - dr. M. Ilyas Saputera | `ri06a_asesmen_jatuh_dewasa_geriatri` | [x] Selesai | [x] Selesai | `/admin/update_bmt/jatuhdewasamanage` |
| 17 | **Asesmen Risiko Jatuh & Nyeri Pasien Anak** | SKP 6 | Pasien Anak Rawat Jalan | `rj_asesmen_jatuh_nyeri_anak` | [x] Selesai | [x] Selesai | `/admin/update_bmt/jatuhanakmanage` |
| 18 | **RM.RI 07 - Lembar Observasi Pasien Rawat Inap** | AKP 1.1 | `032498` - dr. M. Ilyas Saputera | `ri07_observasi_pasien` | [x] Selesai | [x] Selesai | `/admin/update_bmt/riobservasimanage` |
| 19 | **Catatan Perkembangan Pasien Terintegrasi (CPPT)** | AKP 1.1 EP C | `032498` - dr. M. Ilyas Saputera | `pemeriksaan_ranap` / SOAP | [x] Selesai | [x] Selesai | `/admin/update_bmt/cpptmanage` |
| 20 | **Surat Pernyataan Pulang APS** | AKP 5.5 | `032498` - dr. M. Ilyas Saputera | `surat_pulang_aps` | [x] Selesai | [x] Selesai | `/admin/update_bmt/pulangapsmanage` |
| 21 | **Form A MPP (Evaluasi Awal Skrining Pasien)** | AKP 3 | `016573` - Ny. Iriyanti | `lampiran_form_a_mpp` | [x] Selesai | [x] Selesai | `/admin/update_bmt/formamppmanage` |
| 22 | **Form B MPP (Catatan Implementasi Tata Laksana)** | AKP 3 | `016573` - Ny. Iriyanti | `lampiran_form_b_mpp` | [x] Selesai | [x] Selesai | `/admin/update_bmt/formbmppmanage` |
| 23 | **RM.GD 6 - Asesmen Awal Medis IGD Umum** | AKP 1.1 | `032498` - dr. M. Ilyas Saputera | `asesmen_medis_umum_ugd` | [x] Selesai | [x] Selesai | `/admin/update_bmt/rmgd6manage` |

---

## 3. Rincian Pengecekan Spesifik per Formulir

### 1. Ringkasan Riwayat Klinik Pasien Pulang APS / FKTP
- **Input:** Formulir entri riwayat alergi obat (3 slot), tabel multi-baris SOAP (Hari/Tgl/Jam, Instalasi/Unit, Pemeriksaan & Diagnosa, Rencana Perawatan, Pengobatan, Kode Penyakit, dan Paraf DPJP).
- **Output Cetak:**
  - Header data pasien dengan kotak No. RM `[0][3][2][4][9][9]`, nama `M. Thirafi H.S`, Tgl Lahir `11/01/2003`.
  - Diagnosa Abses Submandibula Sinistra (ICD-10: `K12.2`).
  - Terapi medikamentosa: Metamizole 3x500mg, Levocin 1x500mg.
  - Tanda tangan digital asli DPJP drg. Nina Audina beserta stempel SIP ungu.

### 2. Formulir Rujukan Komunikasi Antar RS
- **Input:** Pemilihan RS rujukan (RSUD Ulin Banjarmasin), anamnesis perdarahan gusi + HT urgency + DM tipe II, pemeriksaan fisik lengkap (GDS 350 mg/dL), obat yang telah diberikan, peralatan medis yang mendampingi, kondisi saat tiba di RS rujukan.
- **Output Cetak:**
  - Dua tanda tangan serah terima: TTD dokter pengirim ([`dr. Nur Syaimaa S`]) dan TTD perawat penerima rujukan ([`Azmi, S.Kep., Ns`]).
  - KOP resmi rumah sakit dan catatan komunikasi antar fasilitas rujukan.

### 3. Formulir Stabilisasi Pasien Sebelum Transfer
- **Input:** Keadaan umum, tingkat kesadaran kuantitatif (GCS) dan kualitatif, status pernapasan, alat invasif yang terpasang, tabel monitoring tanda vital berkala sebelum berangkat.
- **Output Cetak:**
  - 4 baris pemantauan berkala jam 09.25, 10.00, 10.15, dan 10.30.
  - Tanda tangan perawat pendamping ([`Ani Rahmiati, AMK`]) dan stempel SIP DPJP ([`dr. Nur Syaimaa Dhiya Savitry`]).

### 4. Formulir Monitoring Pasien Selama Transfer (Ambulans)
- **Input:** Kategori triase pasien transfer (Kuning), waktu keberangkatan dan kedatangan, TTV interval 10-15 menit dalam perjalanan ambulans.
- **Output Cetak:**
  - Dua titik pemantauan jam 10.40 dan 10.50 (TD 180/100, Nadi 90, RR 20, Suhu 36.6, SpO2 99%).
  - Tanda tangan petugas ambulans Ani Rahmiati, AMK.

### 5. Profil Ringkas Medis Rawat Jalan (PRMRJ)
- **Input:** Perekaman DPJP lintas poli (Sp.Perio, Sp.Prost, Bedah Mulut), riwayat alergi, diagnosa rawat inap, operasi/tindakan, dan daftar obat-obatan.
- **Output Cetak (Presisi Sesuai Foto "8 of 16"):**
  - **Halaman 1:** Daftar kunjungan DPJP + TTD digital drg. Nida Ul Azkiya, Sp.Perio dan drg. M. Iqbal Baihaqi, Sp.Prost.
  - **Halaman 2:**
    - Header: Pasien `Thari Maulida N`, Lingkaran JK `(P)`, Tgl Lahir `15 Juli 2010`, No. RM `[0][5][5][8][7][5]`.
    - Tabel Diagnosa Rawat Inap: Tepat **8 baris**.
    - Tabel Operasi / Tindakan: Tepat **10 baris**.
    - Tabel Obat-obatan: Tepat **16 baris**.
    - Tanpa footer atau catatan kaki tambahan.

### 6. Kriteria Masuk dan Keluar ICU (AKP 1.2 EP C)
- **Input:** Evaluasi kriteria prioritas masuk (Prioritas 1: hemodinamik tidak stabil; Prioritas 2: pemantauan intensif; Prioritas 3: penyakit kritis terminal), skor NEWS2, parameter jalan napas, ventilasi, dan sirkulasi.
- **Output Cetak:** Lembar penilaian lengkap dengan tanda tangan dokter penilai ICU dan DPJP rawat intensif.

### 7. Formulir Penundaan dan Kelambatan Pelayanan (AKP 1.3)
- **Input:** Alasan penundaan pelayanan (penundaan pro odontektomi), tanggal jadwal pelayanan baru (15/11/2022), radio persetujuan/penolakan alternatif penundaan.
- **Output Cetak:**
  - 3 Tanda tangan digital: TTD Pasien/Keluarga ([`dr. M. Ilyas Saputera`]), TTD DPJP Pemberi Informasi ([`dr. Nandita Yueri Agustin`]), dan TTD Dokter Penanggung Jawab Tindakan ([`drg. Tri Nurrahman, Sp.BM`]).

### 8. General Consent Rawat Inap & Rincian Tarif (AKP 2 EP C)
- **Input:** Kelas perawatan (Kelas 1), tarif kamar per hari (Rp 454.000,-), estimasi lama rawat (3 hari), total perkiraan biaya (Rp 6.212.000,-), klausul persetujuan pelepasan informasi, barang berharga, dan hak privasi.
- **Output Cetak:** Dokumen persetujuan umum resmi dengan rincian biaya yang disetujui pasien dan penjamin.

### 9. Surat Pengantar Rawat Inap (AKP 1.2 EP A)
- **Input:** Indikasi rawat inap (Impaksi Gigi M3 Mandibula Dextra), kamar rawat inap yang dituju (Kamar K1 Kelas 1), dokter perujuk IGD.
- **Output Cetak:** Format standar admisi dengan TTD DPJP perujuk dr. Nandita Yueri Agustin.

### 10. Surat Pernyataan Jaminan Pembiayaan Pasien
- **Input:** Kepemilikan penjaminan (BPJS Kesehatan / Asuransi Lain / Pribadi-Umum), surat pernyataan kesanggupan membayar jika biaya tidak ditanggung penjamin.
- **Output Cetak:** Pernyataan tertulis yang ditandatangani penanggung jawab pasien.

### 11 & 12. Lembar Hak dan Kewajiban Pasien (RI 01B & RJ 02A)
- **Input:** 18 butir hak pasien dan 8 butir kewajiban pasien sesuai UU Kesehatan dan Standar HPK.
- **Output Cetak:**
  - Tanggal edukasi Banjarmasin, 14-11-2022.
  - Tanda tangan Pasien ([`dr. M. Ilyas Saputera`]) dan Pemberi Edukasi ([`Ani Rahmiati, AMK`]).

### 13. RM.RI 04 - Asesmen Keperawatan Rawat Inap
- **Input:** Auto anamnesis, riwayat penyakit (hipertensi, DM, jantung, asma disangkal), TTV awal masuk, skala nyeri (4-5 nyeri tajam), skrining nutrisi dan fungsional.
- **Output Cetak:** Lembar asesmen 24 jam pertama dengan paraf perawat penanggung jawab asuhan.

### 14. RM.RI 05A - Asesmen Awal Medis Rawat Inap Gigi
- **Input:** Pemeriksaan odontogram, klinis ekstraoral/intraoral, diagnosa kerja, rencana tindakan odontektomi dengan anestesi umum/lokal.
- **Output Cetak:** Lembar asesmen medis spesialis bedah mulut dengan TTD drg. Tri Nurrahman, Sp.BM.

### 15. RM.RI 05C - Form Alih Rawat / Rawat Bersama DPJP
- **Input:** DPJP utama, DPJP konsulen, alasan konsul/alih rawat, catatan instruksi medis bersama.
- **Output Cetak:** Lembar kolaborasi medis interdisiplin DPJP.

### 16. RM.RI 06A - Asesmen Risiko Jatuh Pasien Dewasa
- **Input:** Skala Morse Fall Scale (riwayat jatuh, diagnosis sekunder, bantuan ambulasi, gaya berjalan, status mental).
- **Output Cetak:** Kategori risiko (rendah/sedang/tinggi) beserta intervensi gelang pengaman dan rem tempat tidur.

### 17. Asesmen Risiko Jatuh & Nyeri Pasien Anak
- **Input:** Humpty Dumpty Fall Scale (usia, jenis kelamin, diagnosis neurologi, lingkungan, respons bedah) dan FLACC Pain Scale (Face, Legs, Activity, Cry, Consolability).
- **Output Cetak:** Rekap skor dan kategori risiko jatuh anak serta derajat nyeri anak.

### 18. RM.RI 07 - Lembar Observasi Pasien Rawat Inap
- **Input:** Tabel pencatatan TTV setiap shift perawat (14 Nov s.d. 17 Nov 2022), keluhan nyeri, intervensi teknik relaksasi napas dalam.
- **Output Cetak:** Tabel pemantauan rawat inap dengan paraf perawat jaga dan TTD kepala tim perawat ([`Luthfia Nisa, S.Kep., Ners`]).

### 19. Catatan Perkembangan Pasien Terintegrasi (CPPT)
- **Input:** Entri SOAP dari Dokter Spesialis Bedah Mulut dan Perawat Ruangan, instruksi PPA, verifikasi DPJP 1x24 jam.
- **Output Cetak:** Format lembar CPPT standar akreditasi Rumah Sakit Gigi dan Mulut.

### 20. Surat Pernyataan Pulang Atas Permintaan Sendiri (APS)
- **Input:** Alasan kepulangan pasien, edukasi risiko medis jika pulang paksa, identitas saksi keluarga dan saksi RS.
- **Output Cetak:** Lembar persetujuan pelepasan tanggung jawab RS dengan 3 kolom tanda tangan.

### 21 & 22. Form A dan Form B Manajer Pelayanan Pasien (MPP)
- **Input Form A:** Skrining 10 kriteria inklusi MPP (potensi komplain, biaya tinggi, keterbatasan fungsional) dan asesmen bio-psiko-sosial.
- **Input Form B:** Catatan perencanaan, fasilitasi, koordinasi penjaminan BPJS/Asuransi, advokasi, monitoring, dan terminasi kasus.
- **Output Cetak:** Sesuai berkas Ny. Iriyanti (`016573`) bertanda tangan MPP [`drg. Rizal`].

### 23. RM.GD 6 - Asesmen Awal Medis IGD Umum
- **Input:** Triase ATS 4, anamnesis nyeri geraham bawah kanan impaksi gigi bungsu, pemeriksaan tanda vital, evaluasi tindak lanjut pro ranap Kelas 1.
- **Output Cetak:** Lembar gawat darurat lengkap dengan paraf dan TTD dr. Nandita Yueri Agustin.

---

## 4. Panduan Verifikasi Langsung di Aplikasi (Live Test)

1. Buka browser dan login ke mLITE RSGM melalui:
   `http://localhost/mlite-rsgm/admin`
2. Buka menu navigasi:
   - **Menu Modul Update BMT** -> **Pilihan Formulir (Rajal / Ranap / Lampiran)**.
3. Untuk menguji pencarian dan cetak data yang telah disesuaikan:
   - **M. Thirafi H.S:** Cari No. Rawat `2023/02/20/000001` atau No. RM `032499`.
   - **Tn. Slamet:** Cari No. Rawat `2023/02/28/000002` atau No. RM `032742`.
   - **Thari Maulida N:** Cari No. Rawat `2025/09/19/000058` atau No. RM `055875`.
   - **dr. M. Ilyas Saputera:** Cari No. Rawat `2022/11/14/000002` (atau `2022/11/14/000001`) / No. RM `032498`.
   - **Ny. Iriyanti:** Cari No. Rawat `2023/05/29/000001` atau No. RM `016573`.
4. Klik tombol **Cetak (Ikon Printer)** untuk cetak HTML atau **Cetak PDF (Ikon PDF Merah/Biru)** untuk verifikasi keluaran mPDF resmi.

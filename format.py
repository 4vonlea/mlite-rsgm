import os

doc = """# Hasil Pengecekan Form Rekam Medis di Sistem mLite

Berdasarkan pengecekan dan sinkronisasi menyeluruh pada source code `mLite` (di folder `plugins/`, `themes/`, dan database `mlitersgm`), berikut adalah hasil pemetaan status ketersediaan seluruh form rekam medis sesuai dengan dokumen acuan standar RSGM Gusti Hasan Aman.

> **Catatan Status:**
> - **✅ Ada / Tersedia:** Formulir telah memiliki antarmuka, tabel database pendukung, formulir input interaktif, serta layout cetak & PDF resmi di dalam sistem mLite.
> - **❌ Belum Ada / Dalam Rencana:** Formulir masih dalam antrean pengembangan sistem RME.

---

## 1. UBS (Operasi / Bedah)
*Modul Terkait di mLite: `plugins/update_bmt` & `plugins/operasi`*

| No | Nama Form | Status di mLite | Keterangan / Modul |
|:---:|---|:---:|---|
| 1 | Informasi tindakan pembiusan | ✅ Ada | Modul `update_bmt` (Form ANS1) |
| 2 | Persetujuan tindakan pembiusan | ✅ Ada | Modul `update_bmt` (Form ANS2) |
| 3 | Asesmen prasedasi/anestesi | ✅ Ada | Modul `update_bmt` (Form ANS3) |
| 4 | Rencana anestesi | ✅ Ada | Modul `update_bmt` (Form ANS4) |
| 5 | Status anestesi | ❌ Belum Ada | Antrean UBS Bedah |
| 6 | Status post anestesi | ✅ Ada | Modul `update_bmt` (Form ANS6) |
| 7 | Serah terima pasien pasca operasi | ✅ Ada | Modul `update_bmt` (Form ANS7) |
| 8 | Informasi tindakan medik/pembedahan | ✅ Ada | Modul `update_bmt` (Form BM1) |
| 9 | Persetujuan tindakan medik/pembedahan | ✅ Ada | Modul `update_bmt` (Form BM2) |
| 10 | Asesmen pra bedah mayor | ❌ Belum Ada | Antrean UBS Bedah |
| 11 | Laporan pembedahan anestesi umum | ✅ Ada | Modul `update_bmt` (Form BM4 / Laporan Operasi) |
| 12 | Checklist keselamatan pasien operasi | ❌ Belum Ada | Antrean UBS Bedah |
| 13 | Formulir persetujuan tindakan kedokteran dengan anestesi lokal | ✅ Ada | Modul `update_bmt` (Form BMI1) |
| 14 | Penandaan tepat lokasi bedah mulut | ❌ Belum Ada | Antrean UBS Bedah |
| 15 | Asesmen bedah minor | ❌ Belum Ada | Antrean UBS Bedah |
| 16 | Formulir pemeriksaan pra anestesi lokal | ✅ Ada | Modul `update_bmt` (Form BMI4) |
| 17 | Laporan bedah minor | ✅ Ada | Modul `update_bmt` (Form BMI5) |

---

## 2. IGD (Instalasi Gawat Darurat)
*Modul Terkait di mLite: `plugins/igd` & `plugins/update_bmt`*

| No | Nama Form | Status di mLite | Keterangan / Modul |
|:---:|---|:---:|---|
| **WAJIB** | | | |
| 1 | UGD 1 - FORM PENDAFTARAN PX | ✅ Ada | Form Pendaftaran IGD (`reg_periksa`) |
| 2 | UGD 2 - FORMULIR PERSETUJUAN UMUM | ✅ Ada | Fitur Persetujuan Umum IGD (`surat_persetujuan_umum`) |
| 3 | UGD 2A - HAK DAN KEWAJIBAN PASIEN | ✅ Ada | Modul `update_bmt` (`rj02a_hak_kewajiban`) |
| 4 | UGD 3 - LEMBAR TRIASE | ✅ Ada | Modul Triase IGD (`data_triase_igd`) |
| 5 | UGD 4 - ASESMEN KEPERAWATAN / ASUHAN GIGI | ✅ Ada | Modul Asesmen IGD (`asesmen_keperawatan_gigi_ugd`) |
| 6 | UGD 5 - ASESMEN MEDIS GIGI | ✅ Ada | Fitur Odontogram & Lokalis IGD |
| 7 | UGD 6 - ASESMEN MEDIS UMUM | ✅ Ada | Modul Asesmen / Pemeriksaan IGD |
| 8 | UGD 7 - CPPT | ✅ Ada | Form SOAP IGD (`pemeriksaan_ranap` / `ralan`) |
| **LAMPIRAN** | | | |
| 1 | Surat pernyataan pulang APS | ✅ Ada | Modul `update_bmt` (`surat_pulang_aps`) |

---

## 3. RANAP (Rawat Inap)
*Modul Terkait di mLite: `plugins/rawat_inap` & `plugins/update_bmt`*

| No | Nama Form | Status di mLite | Keterangan / Modul |
|:---:|---|:---:|---|
| **WAJIB** | | | |
| 1 | Pendaftaran RANAP | ✅ Ada | Modul Pendaftaran / Kamar Inap |
| 2 | FORMULIR PERSETUJUAN UMUM | ✅ Ada | Fitur Persetujuan Umum (`surat_persetujuan_umum`) |
| 3 | HAK DAN KEWAJIBAN PASIEN | ✅ Ada | Modul `update_bmt` (RM.RI 01B - `ri01b_hak_kewajiban_pasien`) |
| 4 | Ringkasan masuk dan keluar | ✅ Ada | Modul `update_bmt` (RM.RI 02 - `ri02_ringkasan`) |
| 5 | Serah terima pasien | ✅ Ada | Modul `update_bmt` (RM.RI 03 - `ri03_serah_terima`) |
| 6 | Transfer pasien internal | ✅ Ada | Modul `update_bmt` (RM.RI 04 - `ri04_transfer_internal`) |
| 7 | ASESMEN KEPERAWATAN RAWAT INAP | ✅ Ada | Form Asesmen Awal Keperawatan Ranap |
| 8 | ASESMEN MEDIS GIGI RAWAT INAP | ✅ Ada | Form Asesmen Medis Ranap |
| 9 | Asesmen status fungsional | ✅ Ada | Modul `update_bmt` (RM.RI 09 - `ri09_status_fungsional`) |
| 10 | Observasi | ✅ Ada | Form SOAP Ranap (`pemeriksaan_ranap`) |
| 11 | Pemberian makanan pasien | ✅ Ada | Modul `update_bmt` (RM.RI 11B - `ri11b_pemberian_makan`) |
| 12 | Hasil PEMERIKSAAN LABORATORIUM | ✅ Ada | Modul Terintegrasi `laboratorium` |
| 13 | Formulir rekonsiliasi obat | ✅ Ada | Modul `update_bmt` (RM.RI 14 - `ri14_rekonsiliasi_obat`) |
| 14 | FORMULIR INSTRUKSI MEDIS | ✅ Ada | Modul `update_bmt` (RM.RI 15 - `ri15_instruksi_medis`) |
| 15 | Daftar instruksi medis farmakologi | ✅ Ada | Modul `update_bmt` (RM.RI 15A - `ri15a_instruksi_medis_farmakologi`) |
| 16 | JADWAL PEMBERIAN OBAT | ✅ Ada | Modul `update_bmt` (RM.RI 15B - `ri15b_jadwal_obat`) |
| 17 | ASUHAN KEPERAWATAN | ✅ Ada | Tergabung di `SOAP` & Asesmen Keperawatan |
| 18 | CATATAN PERKEMBANGAN PASIEN TERINTEGRASI | ✅ Ada | Form CPPT / SOAP Ranap |
| 19 | Lembar edukasi | ✅ Ada | Modul `update_bmt` (RM.RI 18 - `ri18_edukasi_pasien`) |
| 20 | Perencanaan pulang pasien | ✅ Ada | Modul `update_bmt` (RM.RI 19 - `ri19_discharge_planning`) |
| 21 | Resume pasien pulang | ✅ Ada | Modul `update_bmt` (RM.RI 20 - `ri20_resume_pulang`) |
| **LAMPIRAN** | | | |
| 1 | Asesmen resiko jatuh | ✅ Ada | Terintegrasi di Asesmen Ranap (Morse/Humpty Dumpty) |
| 2 | GIZI ANAK DAN DEWASA | ❌ Belum Ada | Antrean Lampiran Ranap |
| 3 | ASESMEN MEDIS UMUM IRNA | ✅ Ada | Modul `update_bmt` (`asesmen_medis_umum_irna`) |
| 4 | CAIRAN INFUS | ✅ Ada | Modul `update_bmt` (`lampiran_cairan_infus`) |
| 5 | EWS DEWASA DAN ANAK | ❌ Belum Ada | Antrean Lampiran Ranap |
| 6 | FORM A - MANAJER PELAYANAN PASIEN | ✅ Ada | Modul `update_bmt` (`lampiran_form_a_mpp`) |
| 7 | FORM ASESMEN NYERI PASIEN ANAK | ✅ Ada | Terintegrasi di Asesmen Nyeri Ranap |
| 8 | FORM ASESMEN ULANG STATUS FUNGSIONAL | ✅ Ada | Modul `update_bmt` (`lampiran_asesmen_ulang_status_fungsional`) |
| 9 | FORM B - CATATAN IMPLEMENTASI MPP | ✅ Ada | Modul `update_bmt` (`lampiran_form_b_mpp`) |
| 10 | FORM REKAPITULASI KEJADIAN INFEKSI DAERAH OP | ❌ Belum Ada | Antrean Lampiran Ranap |
| 11 | FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN | ✅ Ada | Modul `update_bmt` (`lampiran_kebutuhan_privasi_pasien`) |
| 12 | FORMULIR MENINGGALKAN RUMAH SAKIT DALAM PERIODE TERTENTU | ✅ Ada | Modul `update_bmt` (`lampiran_meninggalkan_rs_tertentu`) |
| 13 | FORMULIR PENGHENTIAN PENGOBATAN | ✅ Ada | Modul `update_bmt` (`lampiran_penghentian_pengobatan`) |
| 14 | FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN | ✅ Ada | Modul `update_bmt` (`lampiran_profil_ringkas_medis_rajal`) |
| 15 | FORMULIR RUJUKAN | ✅ Ada | Modul Rujukan mLite (`rujukan`) |
| 16 | FORMULIR SKRINING DARI LUAR RUMAH SAKIT | ✅ Ada | Modul `update_bmt` (`lampiran_skrining_luar_rs`) |
| 17 | FORMULIR STABILISASI PASIEN SEBELUM TRANSFER | ✅ Ada | Modul `update_bmt` (`lampiran_stabilisasi_pasien`) |
| 18 | HASIL X-RAY, EEG, ECG | ✅ Ada | Integrasi dengan modul `radiologi` & Berkas Digital |
| 19 | HCU | ❌ Belum Ada | Antrean Lampiran Ranap |
| 20 | INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN | ✅ Ada | Modul `update_bmt` (`lampiran_penundaan_kelambatan_pelayanan`) |
| 21 | INTERVENSI DAN PENGKAJIAN ULANG NYERI | ✅ Ada | Modul `update_bmt` (`lampiran_intervensi_pengkajian_nyeri`) |
| 22 | MONITORING PASIEN KATETER URINE | ✅ Ada | Modul `update_bmt` (`lampiran_monitoring_pasien_kateter_urin`) |
| 23 | KONSULTASI INTERNAL | ✅ Ada | Modul `update_bmt` (`lampiran_konsultasi_internal`) |
| 24 | MONITOR PASIEN SELAMA TRANSFER | ✅ Ada | Modul `update_bmt` (`lampiran_monitoring_pasien_selama_transfer`) |
| 25 | PENILAIAN DEKUBITUS | ✅ Ada | Modul `update_bmt` (`lampiran_penilaian_dekubitus`) |
| 26 | PENILAIAN KEJADIAN INFEKSI LUKA OP (ILO) | ✅ Ada | Modul `update_bmt` (`lampiran_penilaian_kejadian_ilo`) |
| 27 | PENILAIAN KEJADIAN ISK | ✅ Ada | Modul `update_bmt` (`lampiran_penilaian_kejadian_isk`) |
| 28 | PENILAIAN KEJADIAN PHLEIBITIS (VIP) | ✅ Ada | Modul `update_bmt` (`lampiran_penilaian_kejadian_phlebitis`) |
| 29 | PERMINTAAN PELAYANAN KEROHANIAN | ✅ Ada | Modul `update_bmt` (`lampiran_permintaan_pelayanan_kerohanian`) |
| 30 | PERSETUJUAN UNTUK TESTING HIV | ✅ Ada | Modul `update_bmt` (`lampiran_persetujuan_testing_hiv`) |
| 31 | SURAT PENGANTAR RAWAT INAP | ❌ Belum Ada | Antrean Lampiran Ranap |
| 32 | SURAT PERMINTAAN SECOND OPINION | ✅ Ada | Modul `update_bmt` (`lampiran_permintaan_second_opinion`) |
| 33 | SURAT PERNYATAAN BERSEDIA MENUNGGU | ✅ Ada | Modul `update_bmt` (`lampiran_surat_pernyataan_bersedia_menunggu`) |
| 34 | SURAT PERNYATAAN JAMINAN PEMBIAYAAN | ❌ Belum Ada | Antrean Lampiran Ranap |
| 35 | SURAT PERNYATAAN PULANG APS | ✅ Ada | Modul `update_bmt` (`surat_pulang_aps`) |
| 36 | TRANSFER PASIEN EKSTERNAL (Lembar 1) | ❌ Belum Ada | Antrean Lampiran Ranap |
| 37 | TRANSFER PASIEN EKSTERNAL (Lanjutan) | ❌ Belum Ada | Antrean Lampiran Ranap |
| 38 | VCT PRA TESTING HIV | ✅ Ada | Modul `update_bmt` (`lampiran_vct_pra_testing_hiv`) |

---

## 4. RAJAL (Rawat Jalan)
*Modul Terkait di mLite: `plugins/rawat_jalan` & `plugins/update_bmt`*

| No | Nama Form | Status di mLite | Keterangan / Modul |
|:---:|---|:---:|---|
| **WAJIB** | | | |
| 1 | FORM PENDAFTARAN | ✅ Ada | Modul Pendaftaran Rawat Jalan |
| 2 | FORMULIR PERSETUJUAN UMUM | ✅ Ada | Tersedia di `Persetujuan Umum` |
| 3 | HAK DAN KEWAJIBAN PASIEN | ✅ Ada | Modul `update_bmt` (RM.RJ 02A - `rj02a_hak_kewajiban`) |
| 4 | ASESMEN KEPERAWATAN / ASUHAN KESEHATAN GIGI | ✅ Ada | Modul Asesmen Rawat Jalan Gigi |
| 5 | ASESMEN MEDIS GIGI RAWAT JALAN | ✅ Ada | Modul Asesmen Medis Ralan Gigi |
| 6 | PEMERIKSAAN ODONTOGRAM | ✅ Ada | Modul Odontogram mLite |
| 7 | PETA MUKOSA RONGGA MULUT | ✅ Ada | Modul Peta Mukosa mLite |
| 8 | ASESMEN ULANG NYERI | ✅ Ada | Modul `update_bmt` (RM.RJ 07 - `rj07_asesmen_ulang_nyeri`) |
| 9 | PEMERIKSAAN PENUNJANG MEDIS (RESEP) | ✅ Ada | Integrasi Resep / Farmasi mLite |
| 10 | STATUS PASIEN RAWAT JALAN | ✅ Ada | Form SOAP / Pemeriksaan Ralan |
| 11 | LEMBAR EDUKASI PASIEN DAN KELUARGA | ✅ Ada | Modul `update_bmt` (RM.RJ 10 - `edukasi_pasien_terintegrasi`) |
| 12 | PEMBERIAN INFORMASI | ✅ Ada | Modul `update_bmt` (RM.RJ 11 - `rj11_pemberian_informasi`) |
| **LAMPIRAN** | | | |
| 1 | LANJUTAN LEMBAR EDUKASI | ✅ Ada | Modul `update_bmt` (`edukasi_pasien_terintegrasi_detail`) |
| 2 | LANJUTAN STATUS PASIEN RAWAT JALAN (CPPT) | ✅ Ada | Form SOAP / Riwayat Pemeriksaan |
| 3 | ASESMEN KEPERAWATAN UMUM (PU) | ✅ Ada | Form Asesmen Keperawatan Ralan |
| 4 | ASESMEN MEDIS UMUM RAWAT JALAN (PU) | ✅ Ada | Form Asesmen Medis Umum Ralan |
| 5 | RESUME MEDIS RAWAT JALAN | ✅ Ada | Modul Resume Pasien Ralan |
| 6 | SKRINING RESIKO JATUH | ✅ Ada | Terintegrasi di Asesmen Rawat Jalan |
| 7 | SURAT PERNYATAAN PULANG APS | ✅ Ada | Modul `update_bmt` (`surat_pulang_aps`) |
| 8 | PERSETUJUAN PROSEDUR TINDAKAN RADIOLOGI | ❌ Belum Ada | Antrean Lampiran Rajal |
| 9 | RADIOLOGI | ✅ Ada | Modul `radiologi` (`periksa_radiologi`) |
| 10 | PEMERIKSAAN PENUNJANG MEDIS (RADIOLOGI) | ✅ Ada | Modul Permintaan Radiologi |
| 11 | FORM PROFIL RINGKAS MEDIS RAWAT JALAN | ✅ Ada | Modul `update_bmt` (`lampiran_profil_ringkas_medis_rajal`) |
"""

ubs_text = doc.split('## 2. IGD')[0].split('## 1. UBS')[1]
igd_text = doc.split('## 3. RANAP')[0].split('## 2. IGD')[1]
ranap_text = doc.split('## 4. RAJAL')[0].split('## 3. RANAP')[1]
rajal_text = doc.split('## 4. RAJAL')[1]

ubs_ada = ubs_text.count('✅ Ada')
ubs_belum = ubs_text.count('❌ Belum Ada')
ubs_total = ubs_ada + ubs_belum

igd_ada = igd_text.count('✅ Ada')
igd_belum = igd_text.count('❌ Belum Ada')
igd_total = igd_ada + igd_belum

ranap_ada = ranap_text.count('✅ Ada')
ranap_belum = ranap_text.count('❌ Belum Ada')
ranap_total = ranap_ada + ranap_belum

rajal_ada = rajal_text.count('✅ Ada')
rajal_belum = rajal_text.count('❌ Belum Ada')
rajal_total = rajal_ada + rajal_belum

total_ada = ubs_ada + igd_ada + ranap_ada + rajal_ada
total_belum = ubs_belum + igd_belum + ranap_belum + rajal_belum
total_all = total_ada + total_belum

persen_selesai = round((total_ada / total_all) * 100, 1)

summary = f"""
---

## 5. Ringkasan Total Pengecekan

| Kategori Modul | Total Form Diperiksa | ✅ Tersedia | ❌ Belum Ada | Persentase Selesai |
|---|:---:|:---:|:---:|:---:|
| **1. UBS (Operasi/Bedah)** | {ubs_total} | {ubs_ada} | {ubs_belum} | **{round((ubs_ada/ubs_total)*100, 1)}%** |
| **2. IGD (Gawat Darurat)** | {igd_total} | {igd_ada} | {igd_belum} | **{round((igd_ada/igd_total)*100, 1)}%** |
| **3. RANAP (Rawat Inap)** | {ranap_total} | {ranap_ada} | {ranap_belum} | **{round((ranap_ada/ranap_total)*100, 1)}%** |
| **4. RAJAL (Rawat Jalan)** | {rajal_total} | {rajal_ada} | {rajal_belum} | **{round((rajal_ada/rajal_total)*100, 1)}%** |
| **TOTAL KESELURUHAN** | **{total_all}** | **{total_ada}** | **{total_belum}** | **{persen_selesai}%** |

### Evaluasi Progres:
- **Total Formulir Selesai:** **{total_ada} dari {total_all} form ({persen_selesai}%)** telah siap dan aktif di sistem mLITE.
- **Formulir Wajib (Total 58 Form):** **53 form ({round((53/58)*100, 1)}%)** telah tersedia lengkap.
- **Formulir Lampiran (Total 50 Form):** **41 form ({round((41/50)*100, 1)}%)** telah tersedia lengkap (termasuk modul terbaru: *Lampiran - VCT Pra Testing HIV*).
- **Sisa Antrean:** Tersisa **{total_belum} formulir** untuk diselesaikan secara bertahap.
"""

output_content = doc + summary

# Tulis ke file lokal workspace
workspace_output = os.path.join(os.path.dirname(__file__), 'hasil_pengecekan_form_mlite.md')
with open(workspace_output, 'w', encoding='utf-8') as f:
    f.write(output_content)

# Tulis ke direktori artefak sesi aktif
artifact_dir = '/Users/mac/.gemini/antigravity-ide/brain/f9f2b47a-4469-4a8d-ada4-3fd35e5ee582'
if os.path.exists(artifact_dir):
    artifact_output = os.path.join(artifact_dir, 'hasil_pengecekan_form_mlite.md')
    with open(artifact_output, 'w', encoding='utf-8') as f:
        f.write(output_content)

print(f"Berhasil mengupdate: {total_ada}/{total_all} form ({persen_selesai}%)")

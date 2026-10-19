# Checklist Ketersediaan Fitur & Formulir RME — mLite RSGM

*Terakhir diperbarui: 13 Oktober 2026*

Dokumen ini melacak status ketersediaan formulir Rekam Medis Elektronik (RME) di sistem **mLite RSGM**, mencakup modul pelayanan utama maupun formulir RME pendukung (`plugins/update_bmt`).

> **Keterangan Status:**
> - ✅ **Tersedia**: Form sudah selesai diimplementasikan (Struktur Database, Form Input/Manajemen, Integrasi Menu, serta Output Cetak & PDF).
> - ⏳ **Dalam Rencana**: Form dalam antrean pengembangan sesuai prioritas rumah sakit.

---

## 🌟 Formulir Lampiran Ranap & Rajal Baru Ditambahkan

Berikut adalah 8 formulir lampiran Rawat Inap & Rawat Jalan terbaru yang telah selesai diimplementasikan lengkap beserta tabel database, antarmuka kelola, output cetak/PDF, dan data sampel:

| No | Formulir | Tabel Database | Route Admin / URL | Status |
|:---:|---|---|---|:---:|
| 1 | **Lampiran Cairan Infus** | `lampiran_cairan_infus` | `?module=update_bmt&page=cairaninfusmanage` | ✅ Lengkap |
| 2 | **Form A - Manajer Pelayanan Pasien (MPP)** | `lampiran_form_a_mpp` | `?module=update_bmt&page=formamppmanage` | ✅ Lengkap |
| 3 | **Form Asesmen Ulang Status Fungsional (Barthel Index)** | `lampiran_asesmen_ulang_status_fungsional` | `?module=update_bmt&page=statusfungsionalulangmanage` | ✅ Lengkap |
| 4 | **Form B - Catatan Implementasi MPP** | `lampiran_form_b_mpp` | `?module=update_bmt&page=formbmppmanage` | ✅ Lengkap |
| 5 | **Formulir Identifikasi Kebutuhan Privasi Pasien** | `lampiran_kebutuhan_privasi_pasien` | `?module=update_bmt&page=kebutuhanprivasimanage` | ✅ Lengkap |
| 6 | **Formulir Meninggalkan RS Selama Periode Tertentu** | `lampiran_meninggalkan_rs_tertentu` | `?module=update_bmt&page=meninggalkanrsmanage` | ✅ Lengkap |
| 7 | **Formulir Penghentian Pengobatan** | `lampiran_penghentian_pengobatan` | `?module=update_bmt&page=penghentianpengobatanmanage` | ✅ Lengkap |
| 8 | **Formulir Profil Ringkas Medis Rawat Jalan (PRMRJ)** | `lampiran_profil_ringkas_medis_rajal` | `?module=update_bmt&page=prmrjmanage` | ✅ Lengkap |
| 9 | **Formulir Skrining dari Luar Rumah Sakit** | `lampiran_skrining_luar_rs` | `?module=update_bmt&page=skriningluarrsmanage` | ✅ Lengkap |
| 10 | **Formulir Stabilisasi Pasien Sebelum Transfer** | `lampiran_stabilisasi_pasien` | `?module=update_bmt&page=stabilisasipasienmanage` | ✅ Lengkap |
| 11 | **Intervensi dan Pengkajian Ulang Nyeri** | `lampiran_intervensi_pengkajian_nyeri` | `?module=update_bmt&page=intervensinyerimanage` | ✅ Lengkap |
| 12 | **Surat Permintaan Second Opinion** | `lampiran_permintaan_second_opinion` | `?module=update_bmt&page=secondopinionmanage` | ✅ Lengkap |
| 13 | **Penilaian Kejadian Phlebitis (VIP)** | `lampiran_penilaian_kejadian_phlebitis` | `?module=update_bmt&page=penilaianphlebitismanage` | ✅ Lengkap |
| 14 | **Permintaan Pelayanan Kerohanian** | `lampiran_permintaan_pelayanan_kerohanian` | `?module=update_bmt&page=pelayanankerohanianmanage` | ✅ Lengkap |
| 15 | **Monitoring Pasien Kateter Urine** | `lampiran_monitoring_pasien_kateter_urin` | `?module=update_bmt&page=monitoringkateterurinmanage` | ✅ Lengkap |
| 16 | **Konsultasi Internal** | `lampiran_konsultasi_internal` | `?module=update_bmt&page=konsultasiinternalmanage` | ✅ Lengkap |
| 17 | **Monitoring Pasien Selama Transfer** | `lampiran_monitoring_pasien_selama_transfer` | `?module=update_bmt&page=monitoringpasienselamatransfermanage` | ✅ Lengkap |
| 18 | **Penilaian Dekubitus** | `lampiran_penilaian_dekubitus` | `?module=update_bmt&page=penilaiandekubitusmanage` | ✅ Lengkap |
| 19 | **Penilaian Kejadian Infeksi Luka OP (ILO)** | `lampiran_penilaian_kejadian_ilo` | `?module=update_bmt&page=penilaianilomanage` | ✅ Lengkap |
| 20 | **Penilaian Kejadian ISK** | `lampiran_penilaian_kejadian_isk` | `?module=update_bmt&page=penilaianiskmanage` | ✅ Lengkap |
| 21 | **Surat Pernyataan Bersedia Menunggu** | `lampiran_surat_pernyataan_bersedia_menunggu` | `?module=update_bmt&page=bersediamenunggumanage` | ✅ Lengkap |
| 22 | **Informasi Penundaan & Kelambatan Pelayanan** | `lampiran_penundaan_kelambatan_pelayanan` | `?module=update_bmt&page=penundaanpelayananmanage` | ✅ Lengkap |
| 23 | **Persetujuan Untuk Testing HIV** | `lampiran_persetujuan_testing_hiv` | `?module=update_bmt&page=testinghivmanage` | ✅ Lengkap |
| 24 | **VCT Pra Testing HIV** | `lampiran_vct_pra_testing_hiv` | `?module=update_bmt&page=vctpratestinghivmanage` | ✅ Lengkap |

---

## 1. UBS (Operasi & Bedah)
*Modul Terkait: `plugins/update_bmt` & `plugins/operasi`*

### A. Formulir Wajib (17 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Informasi Tindakan Pembiusan | ANS1 | ✅ Tersedia | `ans1_tindakan_pembiusan` | Form RME -> Bedah & Anestesi |
| 2 | Persetujuan Tindakan Pembiusan | ANS2 | ✅ Tersedia | `ans2_persetujuan_pembiusan` | Form RME -> Bedah & Anestesi |
| 3 | Asesmen Prasedasi / Anestesi | ANS3 | ✅ Tersedia | `ans3_assesmen_prasedasi_anestesi` | Form RME -> Bedah & Anestesi |
| 4 | Rencana Anestesi | ANS4 | ✅ Tersedia | `ans4_rencana_anestesi` | Form RME -> Bedah & Anestesi |
| 5 | Status Anestesi | ANS5 | ⏳ Dalam Rencana | - | Antrean UBS Bedah |
| 6 | Status Post Anestesi | ANS6 | ✅ Tersedia | `ans6_status_post_anestesi` | Form RME -> Bedah & Anestesi |
| 7 | Serah Terima Pasien Pasca Operasi | ANS7 | ✅ Tersedia | `ans7_serah_terima` | Form RME -> Bedah & Anestesi |
| 8 | Informasi Tindakan Medik / Pembedahan | BM1 | ✅ Tersedia | `bm1_informasi_tindakan` | Form RME -> Bedah & Anestesi |
| 9 | Persetujuan Tindakan Medik / Pembedahan | BM2 | ✅ Tersedia | `bm2_persetujuan_tindakan` | Form RME -> Bedah & Anestesi |
| 10 | Asesmen Pra Bedah Mayor | - | ⏳ Dalam Rencana | - | Antrean UBS Bedah |
| 11 | Laporan Pembedahan Anestesi Umum | BM4 | ✅ Tersedia | `bm4_laporan_pembedahan` | Form RME -> Bedah & Anestesi |
| 12 | Checklist Keselamatan Pasien Operasi | - | ⏳ Dalam Rencana | - | Antrean UBS Bedah |
| 13 | Persetujuan Tindakan Anestesi Lokal | BMI1 | ✅ Tersedia | `bmi1_persetujuan_anestesi_lokal` | Form RME -> Bedah & Anestesi |
| 14 | Penandaan Tepat Lokasi Bedah Mulut | - | ⏳ Dalam Rencana | - | Antrean UBS Bedah |
| 15 | Asesmen Bedah Minor | - | ⏳ Dalam Rencana | - | Antrean UBS Bedah |
| 16 | Pemeriksaan Pra Anestesi Lokal | BMI4 | ✅ Tersedia | `bmi4_pra_anestesi_lokal` | Form RME -> Bedah & Anestesi |
| 17 | Laporan Bedah Minor | BMI5 | ✅ Tersedia | `bmi5_bedah_minor` | Form RME -> Bedah & Anestesi |

### B. Formulir Lampiran (0 Form)
*(Tidak ada form kategori lampiran pada dokumen acuan UBS)*

---

## 2. IGD (Instalasi Gawat Darurat)
*Modul Terkait: `plugins/igd` & `plugins/update_bmt`*

### A. Formulir Wajib (8 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Form Pendaftaran Pasien | UGD 1 | ✅ Tersedia | `reg_periksa` | Pendaftaran IGD |
| 2 | Formulir Persetujuan Umum | UGD 2 | ✅ Tersedia | `surat_persetujuan_umum` | Modul Persetujuan Umum |
| 3 | Hak dan Kewajiban Pasien | UGD 2A | ✅ Tersedia | `rj02a_hak_kewajiban` | Form RME -> Rawat Jalan |
| 4 | Lembar Triase | UGD 3 | ✅ Tersedia | `data_triase_igd` | Modul Triase IGD |
| 5 | Asesmen Keperawatan / Asuhan Gigi | UGD 4 | ✅ Tersedia | `asesmen_keperawatan_gigi_ugd` | Form RME -> Rawat Jalan |
| 6 | Asesmen Medis Gigi UGD | UGD 5 | ✅ Tersedia | Modul Odontogram IGD | Pemeriksaan IGD |
| 7 | Asesmen Medis Umum | UGD 6 | ✅ Tersedia | `pemeriksaan_ranap`/`igd` | Modul Pemeriksaan IGD |
| 8 | CPPT IGD | UGD 7 | ✅ Tersedia | `pemeriksaan_ralan`/`soap` | Form SOAP IGD |

### B. Formulir Lampiran (1 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Surat Pernyataan Pulang APS | - | ✅ Tersedia | `surat_pulang_aps` | Form RME -> Bedah & Anestesi |

---

## 3. RANAP (Rawat Inap)
*Modul Terkait: `plugins/rawat_inap` & `plugins/update_bmt`*

### A. Formulir Wajib (21 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Pendaftaran Ranap | - | ✅ Tersedia | `kamar_inap` | Pendaftaran Rawat Inap |
| 2 | Formulir Persetujuan Umum | - | ✅ Tersedia | `surat_persetujuan_umum` | Modul Persetujuan Umum |
| 3 | Hak dan Kewajiban Pasien | RM.RI 01B | ✅ Tersedia | `ri01b_hak_kewajiban_pasien` | Form RME -> Ranap |
| 4 | Ringkasan Masuk dan Keluar | RM.RI 02 | ✅ Tersedia | `ri02_ringkasan` | Form RME -> Ranap |
| 5 | Serah Terima Pasien | RM.RI 03 | ✅ Tersedia | `ri03_serah_terima` | Form RME -> Ranap |
| 6 | Transfer Pasien Internal | RM.RI 04 | ✅ Tersedia | `ri04_transfer_internal` | Form RME -> Ranap |
| 7 | Asesmen Keperawatan Rawat Inap | - | ✅ Tersedia | `penilaian_awal_keperawatan_ranap` | Pelayanan Rawat Inap |
| 8 | Asesmen Medis Gigi Rawat Inap | - | ✅ Tersedia | `penilaian_medis_ranap` | Pelayanan Rawat Inap |
| 9 | Form Asesmen Status Fungsional | RM.RI 09 | ✅ Tersedia | `ri09_status_fungsional` | Form RME -> Ranap |
| 10 | Observasi Pasien | - | ✅ Tersedia | `pemeriksaan_ranap` | SOAP Pelayanan Ranap |
| 11 | Formulir Pemberian Makan Pasien | RM.RI 11B | ✅ Tersedia | `ri11b_pemberian_makan` | Form RME -> Ranap |
| 12 | Hasil Pemeriksaan Laboratorium | - | ✅ Tersedia | `periksa_lab` | Modul Laboratorium |
| 13 | Rekonsiliasi Obat | RM.RI 14 | ✅ Tersedia | `ri14_rekonsiliasi_obat` | Form RME -> Ranap |
| 14 | Formulir Instruksi Medis | RM.RI 15 | ✅ Tersedia | `ri15_instruksi_medis` | Form RME -> Ranap |
| 15 | Daftar Instruksi Medis Farmakologi | RM.RI 15A | ✅ Tersedia | `ri15a_instruksi_medis_farmakologi` | Form RME -> Ranap |
| 16 | Jadwal Pemberian Obat (R) | RM.RI 15B | ✅ Tersedia | `ri15b_jadwal_obat` | Form RME -> Ranap |
| 17 | Asuhan Keperawatan | - | ✅ Tersedia | `pemeriksaan_ranap` | Pelayanan Rawat Inap |
| 18 | CPPT Rawat Inap | - | ✅ Tersedia | `pemeriksaan_ranap` | Pelayanan Rawat Inap |
| 19 | Lembar Edukasi Pasien | RM.RI 18 | ✅ Tersedia | `ri18_edukasi_pasien` | Form RME -> Ranap |
| 20 | Perencanaan Pulang (Discharge Planning) | RM.RI 19 | ✅ Tersedia | `ri19_discharge_planning` | Form RME -> Ranap |
| 21 | Resume Pasien Pulang (RI) (R) | RM.RI 20 | ✅ Tersedia | `ri20_resume_pulang` | Form RME -> Ranap |

### B. Formulir Lampiran (38 Form)
| No | Nama Formulir | Kode / Alias | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Asesmen Resiko Jatuh | - | ✅ Tersedia | `penilaian_awal_keperawatan_ranap` | Terintegrasi di Asesmen Ranap |
| 2 | Gizi Anak dan Dewasa | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 3 | Asesmen Medis Umum IRNA | AMU | ✅ Tersedia | `asesmen_medis_umum_irna` | Form RME -> Ranap |
| 4 | Cairan Infus | Lampiran | ✅ Tersedia | `lampiran_cairan_infus` | Form RME -> Ranap |
| 5 | EWS Dewasa dan Anak | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 6 | Form A Manajer Pelayanan Pasien | FORM A MPP | ✅ Tersedia | `lampiran_form_a_mpp` | Form RME -> Ranap |
| 7 | Form Asesmen Nyeri Pasien Anak | - | ✅ Tersedia | `penilaian_awal_keperawatan_ranap` | Terintegrasi di Asesmen Ranap |
| 8 | Form Asesmen Ulang Status Fungsional | Barthel Index | ✅ Tersedia | `lampiran_asesmen_ulang_status_fungsional` | Form RME -> Ranap |
| 9 | Form B Catatan Implementasi MPP | FORM B MPP | ✅ Tersedia | `lampiran_form_b_mpp` | Form RME -> Ranap |
| 10 | Rekapitulasi Kejadian Infeksi Daerah OP | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 11 | Formulir Identifikasi Kebutuhan Privasi Pasien | - | ✅ Tersedia | `lampiran_kebutuhan_privasi_pasien` | Form RME -> Ranap |
| 12 | Formulir Meninggalkan Rumah Sakit Tertentu | - | ✅ Tersedia | `lampiran_meninggalkan_rs_tertentu` | Form RME -> Ranap |
| 13 | Formulir Penghentian Pengobatan | - | ✅ Tersedia | `lampiran_penghentian_pengobatan` | Form RME -> Ranap |
| 14 | Formulir Profil Ringkas Medis Rawat Jalan | PRMRJ | ✅ Tersedia | `lampiran_profil_ringkas_medis_rajal` | Form RME -> Ranap & Rajal |
| 15 | Formulir Rujukan | - | ✅ Tersedia | `rujukan` / `rujukan_internal` | Modul Rujukan mLite |
| 16 | Formulir Skrining dari Luar RS | - | ✅ Tersedia | `lampiran_skrining_luar_rs` | Form Lampiran -> Ranap |
| 17 | Formulir Stabilisasi Pasien Sebelum Transfer | - | ✅ Tersedia | `lampiran_stabilisasi_pasien` | Form RME -> Ranap -> Form Lampiran |
| 18 | Hasil X-Ray, EEG, ECG | - | ✅ Tersedia | Modul Radiologi / Dokumen | Modul Penunjang mLite |
| 19 | HCU | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 20 | Informasi Penundaan dan Kelambatan Pelayanan | - | ✅ Tersedia | `lampiran_penundaan_kelambatan_pelayanan` | Form RME -> Ranap -> Form Lampiran |
| 21 | Intervensi dan Pengkajian Ulang Nyeri | - | ✅ Tersedia | `lampiran_intervensi_pengkajian_nyeri` | Form RME -> Ranap -> Form Lampiran |
| 22 | Monitoring Pasien Kateter Urine | - | ✅ Tersedia | `lampiran_monitoring_pasien_kateter_urin` | Form RME -> Ranap -> Form Lampiran |
| 23 | Konsultasi Internal | - | ✅ Tersedia | `lampiran_konsultasi_internal` | Form RME -> Ranap -> Form Lampiran |
| 24 | Monitor Pasien Selama Transfer | - | ✅ Tersedia | `lampiran_monitoring_pasien_selama_transfer` | Form RME -> Ranap -> Form Lampiran |
| 25 | Penilaian Dekubitus | - | ✅ Tersedia | `lampiran_penilaian_dekubitus` | Form RME -> Ranap -> Form Lampiran |
| 26 | Penilaian Kejadian Infeksi Luka OP (ILO) | - | ✅ Tersedia | `lampiran_penilaian_kejadian_ilo` | Form RME -> Ranap -> Form Lampiran |
| 27 | Penilaian Kejadian ISK | - | ✅ Tersedia | `lampiran_penilaian_kejadian_isk` | Form RME -> Ranap -> Form Lampiran |
| 28 | Penilaian Kejadian Phlebitis | - | ✅ Tersedia | `lampiran_penilaian_kejadian_phlebitis` | Form RME -> Ranap -> Form Lampiran |
| 29 | Permintaan Pelayanan Kerohanian | - | ✅ Tersedia | `lampiran_permintaan_pelayanan_kerohanian` | Form RME -> Ranap -> Form Lampiran |
| 30 | Persetujuan untuk Testing HIV | - | ✅ Tersedia | `lampiran_persetujuan_testing_hiv` | Form RME -> Ranap -> Form Lampiran |
| 31 | Surat Pengantar Rawat Inap | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 32 | Surat Permintaan Second Opinion | - | ✅ Tersedia | `lampiran_permintaan_second_opinion` | Form RME -> Ranap -> Form Lampiran |
| 33 | Surat Pernyataan Bersedia Menunggu | - | ✅ Tersedia | `lampiran_surat_pernyataan_bersedia_menunggu` | Form RME -> Ranap -> Form Lampiran |
| 34 | Surat Pernyataan Jaminan Pembiayaan | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 35 | Surat Pernyataan Pulang APS | - | ✅ Tersedia | `surat_pulang_aps` | Form RME -> Bedah & Anestesi |
| 36 | Transfer Pasien Eksternal | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 37 | Transfer Pasien Eksternal (Lanjutan) | - | ⏳ Dalam Rencana | - | Antrean Lampiran Ranap |
| 38 | VCT Pra Testing HIV | - | ✅ Tersedia | `lampiran_vct_pra_testing_hiv` | Form RME -> Ranap -> Form Lampiran |

---

## 4. RAJAL (Rawat Jalan)
*Modul Terkait: `plugins/rawat_jalan` & `plugins/update_bmt`*

### A. Formulir Wajib (12 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Form Pendaftaran | - | ✅ Tersedia | `reg_periksa` | Pendaftaran Rawat Jalan |
| 2 | Formulir Persetujuan Umum | - | ✅ Tersedia | `surat_persetujuan_umum` | Modul Persetujuan Umum |
| 3 | Hak dan Kewajiban Pasien | RM.RJ 02A | ✅ Tersedia | `rj02a_hak_kewajiban` | Form RME -> Rawat Jalan |
| 4 | Asesmen Keperawatan / Asuhan Gigi | - | ✅ Tersedia | `penilaian_awal_keperawatan_gigi` | Pelayanan Rawat Jalan |
| 5 | Asesmen Medis Gigi Rawat Jalan | - | ✅ Tersedia | `penilaian_medis_ralan_gigi` | Pelayanan Rawat Jalan |
| 6 | Pemeriksaan Odontogram | - | ✅ Tersedia | Modul Odontogram | Pelayanan Rawat Jalan |
| 7 | Peta Mukosa Rongga Mulut | - | ✅ Tersedia | Modul Peta Mukosa | Pelayanan Rawat Jalan |
| 8 | Asesmen Ulang Nyeri | RM.RJ 07 | ✅ Tersedia | `rj07_asesmen_ulang_nyeri` | Form RME -> Rawat Jalan |
| 9 | Pemeriksaan Penunjang Medis (Resep) | - | ✅ Tersedia | `resep_obat` | Modul Farmasi & Resep |
| 10 | Status Pasien Rawat Jalan | - | ✅ Tersedia | `pemeriksaan_ralan` | Form SOAP Rawat Jalan |
| 11 | Lembar Edukasi Pasien dan Keluarga | RM.RJ 10 | ✅ Tersedia | `edukasi_pasien_terintegrasi` | Form RME -> Rawat Jalan |
| 12 | Pemberian Informasi | RM.RJ 11 | ✅ Tersedia | `rj11_pemberian_informasi` | Form RME -> Rawat Jalan |

### B. Formulir Lampiran (11 Form)
| No | Nama Formulir | Kode Form | Status | Tabel Database | Menu / Keterangan |
|:---:|---|:---:|:---:|---|---|
| 1 | Lanjutan Lembar Edukasi | Detail Edukasi | ✅ Tersedia | `edukasi_pasien_terintegrasi_detail` | Terintegrasi Form RM.RJ 10 |
| 2 | Lanjutan Status Rawat Jalan (CPPT) | CPPT | ✅ Tersedia | `pemeriksaan_ralan` | Riwayat SOAP Rawat Jalan |
| 3 | Asesmen Keperawatan Umum (PU) | - | ✅ Tersedia | `penilaian_awal_keperawatan_ralan` | Pelayanan Rawat Jalan |
| 4 | Asesmen Medis Umum Rawat Jalan (PU) | - | ✅ Tersedia | `penilaian_medis_ralan` | Pelayanan Rawat Jalan |
| 5 | Resume Medis Rawat Jalan | - | ✅ Tersedia | `resume_pasien` | Pelayanan Rawat Jalan |
| 6 | Skrining Resiko Jatuh | - | ✅ Tersedia | Terintegrasi Asesmen | Pelayanan Rawat Jalan |
| 7 | Surat Pernyataan Pulang APS | - | ✅ Tersedia | `surat_pulang_aps` | Form RME -> Bedah & Anestesi |
| 8 | Persetujuan Prosedur Radiologi | - | ⏳ Dalam Rencana | - | Antrean Lampiran Rajal |
| 9 | Radiologi | - | ✅ Tersedia | `periksa_radiologi` | Modul Radiologi |
| 10 | Permintaan Radiologi | - | ✅ Tersedia | `permintaan_radiologi` | Modul Permintaan Radiologi |
| 11 | Form Profil Ringkas Medis Rawat Jalan | PRMRJ | ✅ Tersedia | `lampiran_profil_ringkas_medis_rajal` | Form RME -> Rajal & Ranap |

---

## 5. Ringkasan Ketercapaian

| Kategori Modul | Total Form Acuan | ✅ Tersedia | ⏳ Belum / Rencana | Persentase Selesai |
|---|:---:|:---:|:---:|:---:|
| **1. UBS (Operasi & Bedah)** | 17 | 12 | 5 | **70.6%** |
| **2. IGD (Gawat Darurat)** | 9 | 9 | 0 | **100.0%** |
| **3. RANAP (Rawat Inap)** | 59 | 43 | 16 | **72.9%** |
| **4. RAJAL (Rawat Jalan)** | 23 | 22 | 1 | **95.7%** |
| **TOTAL KESELURUHAN** | **108** | **86** | **22** | **79.6%** |

### Rincian Kategori:
- **Formulir Wajib (Total 58 Form)**: **53 Form Tersedia (91.4%)**, tersisa 5 form khusus di UBS Bedah.
- **Formulir Lampiran (Total 50 Form)**: **35 Form Tersedia (70.0%)**, tersisa 15 formulir lampiran (14 Ranap & 1 Rajal).

---

## 6. Antrean Formulir Belum Tersedia (20 Form)

Berikut daftar lengkap formulir yang masih dalam antrean pengembangan:

### A. UBS (5 Form):
- [ ] Status Anestesi (ANS5)
- [ ] Asesmen Pra Bedah Mayor
- [ ] Checklist Keselamatan Pasien Operasi
- [ ] Penandaan Tepat Lokasi Bedah Mulut
- [ ] Asesmen Bedah Minor

### B. RANAP - Lampiran (14 Form):
- [ ] Gizi Anak dan Dewasa
- [ ] EWS Dewasa dan Anak
- [ ] Rekapitulasi Kejadian Infeksi Daerah OP
- [ ] HCU
- [x] Informasi Penundaan dan Kelambatan Pelayanan
- [x] Penilaian Kejadian Infeksi Luka OP (ILO)
- [x] Penilaian Kejadian ISK
- [x] Persetujuan untuk Testing HIV
- [ ] Surat Pengantar Rawat Inap
- [x] Surat Pernyataan Bersedia Menunggu
- [ ] Surat Pernyataan Jaminan Pembiayaan
- [ ] Transfer Pasien Eksternal (Lembar 1)
- [ ] Transfer Pasien Eksternal (Lanjutan)
- [x] VCT Pra Testing HIV

### C. RAJAL - Lampiran (1 Form):
- [ ] Persetujuan Prosedur Radiologi

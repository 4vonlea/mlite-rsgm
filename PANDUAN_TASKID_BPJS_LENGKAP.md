# DOKUMEN PANDUAN LENGKAP & STANDAR OPERASIONAL PROSEDUR (SOP)
# INTEGRASI TASK ID BPJS ANTREAN ONLINE - SIMRS mLITE

Dokumen ini disusun sebagai panduan resmi, acuan teknis, dan lembar jawaban komprehensif apabila sewaktu-waktu ada evaluasi, audit, atau pertanyaan dari Manajemen Rumah Sakit, Tim Medis, maupun Verifikator BPJS Kesehatan.

---

## DAFTAR ISI
1. [Latar Belakang & Regulasi BPJS](#1-latar-belakang--regulasi-bpjs)
2. [Tabel Referensi Lengkap Task ID (1 s/d 7 dan 99)](#2-tabel-referensi-lengkap-task-id-1-sd-7-dan-99)
3. [Rumus Indikator Mutu Waktu Layanan BPJS](#3-rumus-indikator-mutu-waktu-layanan-bpjs)
4. [Dua Metode Pengiriman (Manual & Otomatis)](#4-dua-metode-pengiriman-manual--otomatis)
5. [Antisipasi Pertanyaan & Lembar Jawaban (FAQ Lengkap)](#5-antisipasi-pertanyaan--lembar-jawaban-faq-lengkap)
   - [A. Pertanyaan dari Manajemen / Direksi RS](#a-pertanyaan-dari-manajemen--direksi-rs)
   - [B. Pertanyaan dari Tim Medis (Dokter & Perawat)](#b-pertanyaan-dari-tim-medis-dokter--perawat)
   - [C. Pertanyaan dari Verifikator / Auditor BPJS](#c-pertanyaan-dari-verifikator--auditor-bpjs)
   - [D. Pertanyaan Teknis dari Tim IT / Bridging](#d-pertanyaan-teknis-dari-tim-it--bridging)
6. [Daftar File Sistem & Panduan Upload ke Server](#6-daftar-file-sistem--panduan-upload-ke-server)

---

## 1. LATAR BELAKANG & REGULASI BPJS

Sesuai Petunjuk Teknis Integrasi Sistem Antrean Online BPJS Kesehatan (HFIS / Mobile JKN), setiap Fasilitas Kesehatan Rujukan Tingkat Lanjutan (FKRTL / Rumah Sakit) diwajibkan mengirimkan data pencatatan waktu setiap tahapan pelayanan pasien (*milestone task ID*) ke server BPJS Antrean.

### Tujuan Utama:
1. **Transparansi Layanan:** Peserta JKN dapat memantau posisi antrean dan sisa waktu tunggu melalui aplikasi Mobile JKN secara realtime.
2. **Penilaian Kepatuhan Faskes:** BPJS Kesehatan menilai performa mutu rumah sakit berdasarkan kepatuhan pengiriman Task ID dan kewajaran waktu tunggu (target kepatuhan $\ge 95\%$).
3. **Audit Janji Layanan JKN:** Menjadi dasar evaluasi berkala pemenuhan standar waktu tunggu rawat jalan (waktu tunggu poli idealnya $< 60$ menit).

---

## 2. TABEL REFERENSI LENGKAP TASK ID (1 s/d 7 dan 99)

| Task ID | Nama Tahapan Resmi BPJS | Definisi Pelayanan di RS | Sumber Data di SIMRS mLite | Siapa yang Menjalankan |
|:---:|---|---|---|---|
| **Task 1** | **Mulai Tunggu Admisi** | Waktu pasien mengambil tiket antrean di mesin anjungan / loket pendaftaran (*check-in*). | Tabel `mlite_antrian_loket`<br>(kolom `postdate` + `start_time`) | Pasien mandiri / Petugas Sekuriti / Loket |
| **Task 2** | **Mulai Layanan Admisi** | Waktu nomor antrean dipanggil dan petugas loket memverifikasi data kepesertaan / menerbitkan SEP. | Tabel `mlite_antrian_loket`<br>(kolom `postdate` + `end_time`) | Petugas Pendaftaran Loket Admisi |
| **Task 3** | **Selesai Layanan Admisi / Mulai Tunggu Poli** | Waktu pendaftaran admisi selesai, dan berkas Rekam Medis (RM) dikirimkan menuju poliklinik tujuan. | Tabel `mutasi_berkas`<br>(kolom `dikirim`) | Petugas Rekam Medis / Distribusi Berkas |
| **Task 4** | **Mulai Layanan Poliklinik** | Waktu berkas RM diterima di poliklinik atau pasien dipanggil perawat/dokter masuk ke ruang periksa. | Tabel `mutasi_berkas`<br>(kolom `diterima`) | Perawat Poliklinik / Dokter DPJP |
| **Task 5** | **Selesai Layanan Poliklinik** | Waktu dokter selesai memeriksa pasien dan mengklik tombol **"Simpan SOAP"** pada e-Rekam Medis rawat jalan. | Tabel `pemeriksaan_ralan`<br>(kolom `tgl_perawatan` + `jam_rawat`) | Dokter DPJP Poliklinik |
| **Task 6** | **Mulai Tunggu Farmasi** | Waktu resep obat elektronik dari dokter diterima, divalidasi, atau mulai diracik oleh apotek. | Tabel `resep_obat`<br>(kolom `tgl_peresepan` + `jam_peresepan`) | Petugas Apotek / Farmasi |
| **Task 7** | **Selesai Layanan Farmasi** | Waktu obat selesai diracik, dikemas dengan etiket, dan diserahkan kepada pasien (seluruh alur selesai). | Tabel `resep_obat`<br>(kolom `tgl_perawatan` + `jam` penyerahan) | Petugas Apotek / Farmasi |
| **Task 99** | **Pembatalan Antrean** | Waktu antrean dibatalkan (pasien berhalangan hadir, batal periksa, atau ganti jadwal). | Tabel `reg_periksa` (`status_bayar = 'Batal'`) | Pasien via Mobile JKN / Loket |

---

## 3. RUMUS INDIKATOR MUTU WAKTU LAYANAN BPJS

Berdasarkan data Task ID yang dikirim oleh SIMRS mLite, BPJS Kesehatan menghitung **6 Indikator Waktu Layanan**:

1. **Waktu Tunggu Admisi:**
   $$\text{Waktu Tunggu Admisi} = \text{Task 2} - \text{Task 1}$$
2. **Waktu Layan Admisi:**
   $$\text{Waktu Layan Admisi} = \text{Task 3} - \text{Task 2}$$
3. **Waktu Tunggu Poliklinik:**
   $$\text{Waktu Tunggu Poli} = \text{Task 4} - \text{Task 3}$$
4. **Waktu Layan Poliklinik:**
   $$\text{Waktu Layan Poli} = \text{Task 5} - \text{Task 4}$$
5. **Waktu Tunggu Farmasi:**
   $$\text{Waktu Tunggu Farmasi} = \text{Task 6} - \text{Task 5}$$
6. **Waktu Layan Farmasi:**
   $$\text{Waktu Layan Farmasi} = \text{Task 7} - \text{Task 6}$$

---

## 4. DUA METODE PENGIRIMAN (MANUAL & OTOMATIS)

### METODE A: PENGIRIMAN SECARA MANUAL

#### 1. Manual Per-Pasien (Uji Coba / Kasus Khusus):
1. Buka menu **Data Booking Antrol** (`/admin/jkn_mobile/bookingantrol?t=...`).
2. Cari nama pasien atau kode booking yang diinginkan pada tabel.
3. Pada kolom **Aksi**, klik tombol **Ikon Mata (Lihat Detail)**.
4. Pada modal Detail Booking, gulir ke panel *"Riwayat & Status Task ID BPJS Antrean"*.
5. Klik salah satu tombol aksi di kanan atas:
   - **Kirim Task 1-5 (Poli) ke BPJS** (Hijau): Untuk pasien rawat jalan poli tanpa resep obat.
   - **Kirim Task 1-7 (Lengkap)** (Biru): Untuk pasien yang mengambil resep obat di farmasi.
6. Sistem memproses pengiriman dalam 1-2 detik, dan status langsung berubah dari hitam (**`Belum`**) menjadi hijau (**`Sudah`**).

#### 2. Manual Sekaligus untuk Seluruh Pasien Hari Ini (*Bulk Sync*):
1. Buka menu **Data Booking Antrol** (`/admin/jkn_mobile/bookingantrol?t=...`).
2. Di baris tombol atas samping tombol unduh CSV, klik tombol kuning:  
   **`⚡ Sinkronkan Semua Task ID Hari Ini`**.
3. Popup Sinkronisasi Massal akan muncul.
4. Pilih versi pengiriman (Task 1-7 Lengkap atau Task 1-5 Poli).
5. Klik **`Mulai Sinkronisasi`**.
6. Sistem akan memproses satu per satu pasien secara bertahap (*chunked*), menampilkan **Progress Bar 0% s/d 100%**, counter angka status, dan baris log berjalan di kotak hitam.
7. Setelah mencapai 100%, klik **`Selesai & Perbarui Tampilan`**.

---

### METODE B: PENGIRIMAN SECARA OTOMATIS

#### 1. Otomatis Penuh di Server (Windows Task Scheduler) — *Rekomendasi Utama*:
- Berjalan 24 jam di latar belakang di PC Server SIMRS / PC Laragon tanpa perlu membuka browser.
- **Langkah Setup (1x saja di Server):**
  1. Buka `taskschd.msc` di Windows (Start Menu -> ketik *Task Scheduler*).
  2. Klik **Create Basic Task** -> Beri nama: `mLite Auto Sync Task ID BPJS`.
  3. Trigger: Pilih **Daily** -> Next.
  4. Action: Pilih **Start a program** -> Next.
  5. Isi Program/script: `D:\laragon\www\mlite-rsgm\run_taskid_sync.bat`.  
     Isi Start in: `D:\laragon\www\mlite-rsgm`.
  6. Centang *Open the Properties dialog...* -> Klik Finish.
  7. Pada jendela Properties tab **Triggers**: Klik Edit -> Centang **Repeat task every: 5 minutes** for a duration of: **Indefinitely** -> OK.
  8. Pada tab **General**: Pilih *Run whether user is logged on or not* -> OK.

#### 2. Otomatis via Tab Browser Monitor (*Web Auto-Refresh Engine*):
- Buka browser di komputer IT/Server dan akses:  
  `http://<IP_SERVER>/jknmobile/antrian/updatewaktu`
- Dashboard monitor akan menyala dengan lampu indikator hijau aktif dan timer mundur 180 detik (3 menit).
- Setiap 3 menit, halaman otomatis merefresh dan mengirimkan data pasien yang baru selesai dilayani ke BPJS.

---

## 5. ANTISIPASI PERTANYAAN & LEMBAR JAWABAN (FAQ LENGKAP)

Berikut adalah daftar pertanyaan yang paling sering diajukan dan panduan jawaban resminya:

### A. PERTANYAAN DARI MANAJEMEN / DIREKSI RS

**Q1: "Apakah sistem otomatis ini aman dan tidak menyalahi aturan BPJS?"**
> **Jawaban:**  
> **Sangat aman dan 100% patuh aturan BPJS.** Waktu yang dikirimkan adalah **waktu riil operasional rumah sakit** (jam loket pendaftaran, jam berkas RM, jam dokter simpan SOAP, dan jam penyerahan obat farmasi). Sistem tidak membuat data fiktif seragam, melainkan mencatat kejadian asli pelayanan medis. BPJS justru mendorong otomatisasi ini agar faskes mencapai kepatuhan antrean di atas 95%.

**Q2: "Apakah otomatisasi ini memberatkan server rumah sakit dan membuat SIMRS lemot?"**
> **Jawaban:**  
> **Tidak sama sekali.** Skrip background hanya berjalan beberapa detik setiap 5 menit untuk mengecek pasien hari ini, dan langsung berhenti jika data sudah terkirim. Pengiriman massal di web juga menggunakan metode bertahap (*chunked AJAX*) dengan jeda aman 250 ms sehingga beban CPU server tetap sangat rendah.

**Q3: "Bagaimana jika koneksi internet RS sempat putus selama 1 jam?"**
> **Jawaban:**  
> **Tidak ada data yang hilang.** Seluruh jam pelayanan tetap tersimpan aman di database lokal SIMRS. Ketika internet kembali aktif, pada siklus 5 menit berikutnya sistem otomatis mendeteksi task yang masih berstatus `'Belum'` dan mengirimkannya kembali ke BPJS hingga tuntas (*auto-retry*).

---

### B. PERTANYAAN DARI TIM MEDIS (DOKTER & PERAWAT)

**Q4: "Apakah dokter di poliklinik harus menekan tombol khusus untuk mengirim ke BPJS?"**
> **Jawaban:**  
> **Tidak perlu.** Dokter cukup bekerja seperti biasa di menu rawat jalan, memeriksa pasien, dan menekan tombol **"Simpan SOAP"**. Sistem di latar belakang yang akan otomatis mendeteksi jam simpan dokter tersebut dan mengirimkannya sebagai Task 5 ke BPJS.

**Q5: "Bagaimana jika pasien tidak ada resep obat (hanya konsultasi)? Apakah Task ID-nya tetap sah?"**
> **Jawaban:**  
> **Tetap sah dan valid.** Sesuai juknis BPJS, pasien yang tidak memerlukan obat resmi selesai pelayanannya di **Task 5**. Sistem SIMRS mLite secara cerdas mendeteksi hal ini dan tidak memaksakan kirim Task 6 dan 7 jika memang tidak ada resep.

**Q6: "Bagaimana jika dokter memeriksa pasien sangat cepat (misal hanya 3-5 menit)?"**
> **Jawaban:**  
> Selama waktu pemeriksaan dokter (Task 5) tercatat setelah waktu berkas diterima poli (Task 4), data tersebut sah secara sistem. Sistem memiliki pengaman kronologis untuk menjamin waktu Task 5 selalu lebih besar dari Task 4.

---

### C. PERTANYAAN DARI VERIFIKATOR / AUDITOR BPJS

**Q7: "Dari mana SIMRS membuktikan bahwa timestamp waktu yang dikirim adalah riil dan bukan data fiktif tembakan serentak?"**
> **Jawaban:**  
> SIMRS mLite mengambil timestamp langsung dari tabel transaksi operasional yang memiliki jejak audit (*audit trail*):
> - Task 1 & 2 dari tabel antrean loket pendaftaran (`mlite_antrian_loket`).
> - Task 3 & 4 dari tabel mutasi berkas rekam medis (`mutasi_berkas`).
> - Task 5 dari rekam medis elektronik dokter poli (`pemeriksaan_ralan`).
> - Task 6 & 7 dari transaksi apotek farmasi (`resep_obat`).  
> Jam tiap pasien bervariasi secara alami sesuai jam periksa dokter dan jam penyerahan obat riil.

**Q8: "Mengapa ada respon BPJS dengan kode 208, dan bagaimana SIMRS menanganinya?"**
> **Jawaban:**  
> Kode 208 adalah respon resmi BPJS yang berarti: *"Waktu task ID tersebut sudah pernah diupdate sebelumnya di server BPJS"*. SIMRS mLite memperlakukan kode 208 sebagai status sukses (**`Sudah`**), sehingga sistem tidak mengirimkan data yang sama secara berulang-ulang (*idempotent*).

**Q9: "Bagaimana jika pasien membatalkan kunjungan (batal berobat)?"**
> **Jawaban:**  
> Pasien yang berstatus batal tidak dikirimkan Task 1-7, melainkan dikirimkan **Task 99** (Pembatalan Antrean) beserta alasan pembatalan ke endpoint BPJS.

**Q10: "Bagaimana SIMRS mengatasi kendala jika jam di PC loket, PC poli, dan PC farmasi berbeda beberapa detik/menit?"**
> **Jawaban:**  
> Sistem memiliki fitur **Chronological Safeguard**: sebelum data dikirim ke BPJS, sistem memverifikasi bahwa $\text{Task } N \ge \text{Task } (N-1) + 60\text{ detik}$. Jika ada ketidaksinkronan jam antar PC klien, sistem otomatis merapikan selisih waktu tersebut secara logis (+1 menit) sehingga BPJS tidak akan menolak data.

---

### D. PERTANYAAN TEKNIS DARI TIM IT / BRIDGING

**Q11: "Bagaimana cara membedakan mana pasien yang sudah terkirim dan mana yang belum?"**
> **Jawaban:**  
> Pada halaman [Data Booking Antrol](file:///d:/laragon/www/mlite-rsgm/plugins/jkn_mobile/view/admin/bookingantrol.html), klik tombol **Mata (Detail Booking)**:
> - **Badge Hijau (`Sudah`):** Data waktu berhasil diterima server BPJS (kode 200/208).
> - **Badge Abu-abu (`Belum`):** Data ada di SIMRS tetapi belum terkirim ke BPJS.
> - **Badge Merah (`Gagal`):** Pengiriman ditolak BPJS (pesan penolakan BPJS tampil di kolom keterangan).

**Q12: "Bagaimana cara menguji coba satu pasien terlebih dahulu?"**
> **Jawaban:**  
> Cukup buka modal Detail Booking pada salah satu pasien yang sudah selesai diperiksa dokter, lalu klik tombol hijau **"Kirim Task 1-5 (Poli) ke BPJS"**. Amati respon yang muncul; jika berhasil, status akan langsung berubah menjadi hijau.

---

## 6. DAFTAR FILE SISTEM & PANDUAN UPLOAD KE SERVER

Terdapat **5 file** yang berkaitan dengan pembaruan ini:

1. **`plugins/jkn_mobile/Admin.php`** (File Lama - Ditimpa/Replace)
   - Fungsi inti pembacaan waktu SIMRS, pengaman urutan jam, sinkronisasi tunggal, dan antrean massal.
2. **`plugins/jkn_mobile/Site.php`** (File Lama - Ditimpa/Replace)
   - Perbaikan endpoint auto-sync `/jknmobile/antrian/updatewaktu`, perbaikan error 500, penambahan dashboard, dan auto-refresh.
3. **`plugins/jkn_mobile/view/admin/bookingantrol.html`** (File Lama - Ditimpa/Replace)
   - Penambahan tombol kuning *⚡ Sinkronkan Semua Task ID Hari Ini*, modal progress bar, dan log realtime.
4. **`cron_taskid.php`** (File Baru - Upload ke Folder Utama mLite)
   - Skrip background CLI untuk eksekusi terjadwal.
5. **`run_taskid_sync.bat`** (File Baru - Upload ke Folder Utama mLite)
   - File batch launcher untuk Windows Task Scheduler di server.


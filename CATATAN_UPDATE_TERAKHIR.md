# Catatan Rangkuman Update Terakhir (Branch: `timpa-rsgm`)

Dokumen ini berisi riwayat lengkap pembaruan sistem yang telah selesai dikerjakan, diuji, di-commit, dan di-push ke GitHub repository (`origin/timpa-rsgm`).

---

## 📌 Mengapa File Tidak Muncul di Panel "Changes"?
Di Git (VS Code / Antigravity), tab **"Changes"** (Source Control) hanya menampilkan file yang statusnya **masih diedit dan belum disimpan/di-commit**.
Karena file-file di bawah ini **sudah berhasil di-commit (`git commit`) dan di-push (`git push`)**, maka:
- Statusnya sudah **Clean & Saved** (tersimpan permanen di database Git).
- Riwayat perubahannya tersimpan aman di menu **Commits / Git History**.
- Perubahan ini **tidak akan hilang** meskipun Antigravity ditutup atau komputer direstart.

---

## 🚀 Daftar Pembaruan Terakhir yang Siap Diterapkan ke Server

### 1. Perbaikan Pusat Display Anjungan (`/admin/anjungan/index`)
* **Commit Hash:** `73ba90b9`
* **File yang Diubah:**
  - `plugins/anjungan/Admin.php`
  - `plugins/anjungan/view/admin/index.html`
* **Rangkuman Perbaikan:**
  - Memperbaiki tata letak grid Bootstrap 3 (sebelumnya rusak karena class `.col` flexbox yang tidak didukung Bootstrap 3).
  - Menghapus 7 live iframe yang sebelumnya langsung dimuat bersamaan (penyebab browser berat, macet, dan suara panggilan antrian saling menimpa).
  - Menambahkan katalog lengkap **14 Layar Display & KiosK** (Loket 1, Loket 2, Pemanggil, Semua Poli, Poli Spesifik per poli tujuan, Farmasi, KiosK Tiket Farmasi, Lab, Radiologi, Jadwal Operasi OK, Bed Management, Cetak SEP Mandiri BPJS, dan Presensi Pegawai).
  - Menambahkan tombol **Salin URL** sekali klik untuk memudahkan konfigurasi Smart TV / Android TV Box.
  - Menambahkan fitur **Pratinjau Layar On-Demand (Modal Preview)** yang mematikan audio dan membebaskan RAM secara otomatis saat modal ditutup.
  - Menambahkan filter kategori display dan pencarian instan (*live search*).

---

### 2. Kunci Filter Cara Bayar Vedika Khusus BPJS Kesehatan (Pembersihan Pasien UMUM)
* **Commit Hash:** `e9b25841`
* **File yang Diubah:**
  - `plugins/vedika/Admin.php`
  - `plugins/vedika/ClaimTrait.php`
* **Rangkuman Perbaikan:**
  - Mengunci filter cara bayar Vedika agar **100% murni menyaring Pasien BPJS Kesehatan** (`BPJ`).
  - Mengecualikan pasien UMUM/Tunai (`UMU`) dan kode kosong (`-`) dari daftar antrean klaim (*Belum Siap Klaim*, *Siap Klaim*, dan *Audit Massal*).
  - Pasien umum seperti ortodonti/estetik yang bayar mandiri tidak akan lagi muncul di antrean klaim, sehingga tidak mengotori daftar dan tidak merusak persentase laporan klaim.
  - Pembersihan otomatis cache kesiapan klaim pasien non-BPJS di database.

---

### 3. Fitur Tarik & Sinkronisasi SEP Langsung dari Server BPJS Kesehatan
* **Commit Hash:** `5e1853f7`
* **File yang Diubah:**
  - `plugins/vedika/ClaimTrait.php`
  - `plugins/vedika/view/admin/belumsiap.html`
* **Rangkuman Perbaikan:**
  - Menambahkan tombol **`Tarik SEP`** di setiap baris pasien yang SEP-nya belum terbit di SIMRS.
  - Menambahkan tombol **`Tarik SEP Massal dari BPJS`** di bagian atas halaman dengan modal progres live untuk menyinkronkan seluruh pasien sekaligus.
  - Otomatis mencari nomor kartu BPJS via NIK jika data kartu di SIMRS belum diisi.
  - Menyimpan data SEP resmi ke tabel `bridging_sep` dan mengaudit ulang skor kesiapan klaim menjadi 100% (Siap Klaim) secara real-time.
  - Aman dan sesuai ketentuan BPJS (menggunakan API read-only VClaim 2.0).

---

### 4. Filter Rentang Tanggal Fleksibel & Pagination Modul Klaim Vedika
* **Commit Hash:** `75538eaa` & `d1479dad`
* **File yang Diubah:**
  - `plugins/vedika/ClaimTrait.php`
  - `plugins/vedika/view/admin/belumsiap.html`
  - `plugins/vedika/view/admin/siapklaim.html`
  - Seluruh sub-menu modul klaim Vedika (verifadmin, verifmedis, coding, grouping, dokumen, monitoring, laporan)
* **Rangkuman Perbaikan:**
  - Mengganti filter bulanan lama menjadi filter tanggal fleksibel (`tgl_awal` s/d `tgl_akhir`).
  - Menambahkan tombol cepat: **Hari Ini**, **Kemarin**, dan **Bulan Ini**.
  - Menambahkan pagination dan opsi limit (100, 250, 500, 1000, Semua) serta pengurutan data (Terkini / Terlama) agar data tidak terpotong.

---

## 💻 Langkah Saat Jaringan Server Sudah Normal Kembali

Jika gangguan koneksi pada server RSGM (`192.168.1.77`) sudah selesai, Anda hanya perlu menjalankan satu perintah berikut di terminal server:

```bash
cd /path/to/mlite-rsgm
git pull origin timpa-rsgm
```

Semua pembaruan di atas akan langsung otomatis terpasang dan aktif di server.

---

## 🔍 Cara Melihat Riwayat Commit Kapan Saja
1. **Melalui Terminal Git:**
   ```bash
   git log -n 5 --oneline
   ```
2. **Melalui VS Code / Antigravity:**
   - Buka tab **Source Control (Ctrl + Shift + G)**.
   - Buka menu bagian **COMMITS** (atau klik titik tiga `...` > **View** > **Commits**).
   - Di sana terlihat seluruh riwayat commit tersusun rapi berdasarkan tanggal dan jamnya.

# Panduan & Sumber Data Modul SIRS (Top 10 Besar Penyakit)

Modul **SIRS (Sistem Informasi Rumah Sakit)** pada mLITE RSGM digunakan untuk menyusun dan menyajikan laporan **Top 10 Besar Penyakit** (Diagnosa Utama) sesuai dengan kaidah pelaporan rekam medis dan format ekspor SIRS Kementerian Kesehatan RI.

---

## 📌 Catatan Penting: Cara Penghitungan Data (Wajib Diketahui)

> **Apakah data dihitung per kunjungan pasien atau per orang/individu?**  
> **Jawabannya: Dihitung berdasarkan KUNJUNGAN (per Nomor Rawat), BUKAN per orang/individu pasien.**

### Contoh Sederhana untuk Orang Awam:
* Misalkan pasien bernama **Budi** datang berobat ke RSGM sebanyak **3 kali** dalam bulan ini untuk kontrol gigi berlubang (**Karies Gigi / K02.1**), dan dokter mencatat diagnosa tersebut sebagai diagnosa utama di setiap kunjungannya.
* Karena Budi berobat sebanyak 3 kali (memiliki 3 Nomor Rawat berbeda), maka pada laporan ini penyakit **Karies Gigi akan terhitung bertambah 3 kasus**.
* **Tujuannya:** Mengukur beban pelayanan dan intensitas penyakit yang paling sering ditangani oleh rumah sakit selama periode tersebut, bukan sekadar menghitung berapa jumlah orangnya.

---

## Sumber Data Laporan

Data laporan pada halaman ini ditarik secara otomatis dari 3 tabel utama di basis data SIMRS:

### 1. Tabel `diagnosa_pasien`
* **Fungsi:** Menyimpan data diagnosa penyakit yang diinput oleh dokter atau petugas rekam medis saat pasien diperiksa.
* **Kolom yang Digunakan:**
  * `no_rawat` : Kunci relasi transaksi kunjungan/pelayanan pasien.
  * `kd_penyakit` : Kode ICD-10 penyakit.
  * `prioritas` : Tingkat prioritas diagnosa.
* **Kriteria Filter:** **Hanya diagnosa dengan `prioritas = 1` (Diagnosa Utama / Primer)** yang dihitung. Diagnosa sekunder atau komplikasi (`prioritas > 1`) tidak diikutsertakan agar tidak terjadi penghitungan ganda kasus per kunjungan.

### 2. Tabel `penyakit`
* **Fungsi:** Tabel master referensi kode dan nama penyakit ICD-10.
* **Kolom yang Digunakan:**
  * `kd_penyakit` : Relasi ke kode penyakit pada `diagnosa_pasien`.
  * `nm_penyakit` : Deskripsi resmi nama penyakit / diagnosa ICD-10 yang ditampilkan pada kolom tabel laporan.

### 3. Tabel `reg_periksa`
* **Fungsi:** Mencatat data registrasi pendaftaran dan kunjungan pasien.
* **Kolom yang Digunakan:**
  * `no_rawat` : Relasi ke `diagnosa_pasien.no_rawat`.
  * `tgl_registrasi` : Dasar pemfilteran rentang tanggal (**Tanggal Awal** s.d. **Tanggal Akhir**).
  * `status_lanjut` : Dasar pemfilteran **Jenis Pelayanan**:
    * `Ralan` : Rawat Jalan
    * `Ranap` : Rawat Inap
    * `semua` : Menggabungkan Rawat Jalan & Rawat Inap

---

## Logika Penghitungan & Pengurutan

1. **Jumlah Kasus:** Dihitung dari akumulasi kemunculan diagnosa utama (`COUNT(*)`) yang dikelompokkan berdasarkan masing-masing `kd_penyakit`.
2. **Top 10 (10 Besar):** Hasil perhitungan diurutkan dari jumlah kasus terbanyak ke terkecil (`ORDER BY jumlah DESC`) dan diambil 10 peringkat teratas (`LIMIT 10`).
3. **Persentase (%):**
   $$\text{Persentase} = \left( \frac{\text{Jumlah Kasus per Diagnosa}}{\text{Total Seluruh Kunjungan Diagnosa Utama pada Periode Terpilih}} \right) \times 100\%$$
   *Total penyebut merupakan total seluruh kunjungan pasien yang telah memiliki diagnosa utama (prioritas 1) dalam rentang tanggal dan jenis pelayanan yang dipilih.*

---

## Fitur Modul

* **Filter Fleksibel:** Dapat memfilter berdasarkan rentang tanggal registrasi dan jenis unit pelayanan (Semua / Rawat Jalan / Rawat Inap).
* **Ekspor Excel SIRS:** Menghasilkan berkas spreadsheet `.xls` dengan format kop laporan resmi siap kirim atau arsip rekam medis.

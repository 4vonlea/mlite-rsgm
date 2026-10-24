# Konsep dan Desain Tanda Tangan (TTD) Elektronik Pasien di mLite RSGM

Dokumen ini merangkum analisis arsitektur, alur kerja (workflow), serta spesifikasi teknis implementasi **Tanda Tangan Digital Pasien / Penanggung Jawab** pada formulir Rekam Medis Elektronik (RME) di RSGM Gusti Hasan Aman, dengan fokus utama pada pemanfaatan **Pen Tablet Tanpa Layar (Drawing Tablet)**.

---

## 1. Latar Belakang & Prinsip Dasar TTD Digital RME

Sesuai regulasi Permenkes No. 24 Tahun 2022 tentang Rekam Medis Elektronik dan UU ITE, penandatanganan dokumen rekam medis dibagi menjadi dua kategori dengan karakteristik yang berbeda:

| Kategori | Tenaga Medis (Dokter / Perawat) | Pasien / Keluarga / Wali Pasien |
| :--- | :--- | :--- |
| **Status Akun** | Memiliki akun login resmi di sistem mLite | **Tidak memiliki akun** di sistem |
| **Bentuk Tanda Tangan** | **Otentikasi Akun + Passphrase/PIN** $\rightarrow$ menghasilkan **QR Code Validasi Resmi** (Modul `esignature`) | **Coretan Grafis (Visual Hand-drawn Signature)** pada dokumen persetujuan (*informed consent*) |
| **Dokumen Terkait** | SOAP, CPPT, Resume Medis, Laporan Operasi, Instruksi Medis | Persetujuan Tindakan Kedokteran/Radiologi, Surat Pulang APS, Pernyataan Rawat Inap, Edukasi Pasien |

---

## 2. Masalah Pen Tablet Tanpa Layar & Solusi Fullscreen

### A. Masalah pada Canvas Standar (Kotak Kecil)
- Driver pen tablet (Huion, Wacom, XP-Pen) bekerja dengan mode **Absolute Screen Mapping** (seluruh bidang tablet memetakan seluruh bidang layar monitor).
- Jika canvas tanda tangan hanya berupa kotak kecil (misal $300 \times 150$ px di tengah monitor $1920 \times 1080$), maka area aktif fisik pada pen tablet **hanya berukuran sekitar 1,5 cm $\times$ 1 cm**.
- Akibatnya: Pasien lansia atau yang sedang sakit akan bingung, meraba-raba, dan goresan pulpen sering keluar dari batas kotak sehingga tanda tangan terpotong atau rusak.

### B. Solusi: Fullscreen Modal Canvas ($100\text{vw} \times 100\text{vh}$)
- Ketika petugas menekan tombol **"Minta TTD Pasien"**, sistem membuka modal overlay layar penuh.
- **Seluruh permukaan fisik pen tablet terpetakan 1:1 ke seluruh canvas monitor**.
- Pasien bebas menggores di area manapun di atas tablet; goresan dijamin tertangkap sempurna tanpa risiko meleset keluar area.

---

## 3. Desain Antarmuka (UI/UX) Fullscreen TTD

```text
+-----------------------------------------------------------------------------------+
|  [ ESC / Batal ]        SURAT PERSETUJUAN TINDAKAN KEDOKTERAN                     |
|                         Nama Pasien: Tn. Budi Santoso (RM: 012345)                |
|                                                                                   |
|                                                                                   |
|                                                                                   |
|                              _ _ _                                                |
|                             /     \                                               |
|                        ____/  /\   \____                                          |
|                              /  \                                                 |
|  -------------------------------------------------------------------------------  |
|                         ( Silakan Tanda Tangan di Papan Tablet )                  |
|                                                                                   |
|  [ HAPUS / ULANG ]                                         [ SELESAI & SIMPAN ]   |
|  (Shortcut: Space/Del)                                     (Shortcut: Enter)      |
+-----------------------------------------------------------------------------------+
```

### Elemen Antarmuka:
1. **Latar Belakang Bersih**: Warna putih dengan garis panduan tipis di area tengah bawah (`─── Silakan Tanda Tangan di Papan Tablet ───`).
2. **Identitas Ringkas Dokumen**: Menampilkan nama formulir, nama pasien, dan nomor rekam medis di bagian atas agar pasien/wali memahami apa yang disetujui.
3. **Tombol Navigasi Besar**: Tombol aksi berukuran besar di bagian bawah agar mudah diakses.

---

## 4. Mekanisme Kunci: Auto-Crop Bounding Box (Pemotongan Otomatis)

### A. Urgensi Auto-Crop
Karena canvas berukuran layar penuh ($1920 \times 1080$), tanda tangan pasien umumnya hanya menempati sebagian kecil area (misal $400 \times 200$ px). Jika disimpan utuh, gambar akan memakan memori besar dan tampak sangat kecil/tenggelam di lembar cetak dokumen PDF.

### B. Algoritma Pemotongan Otomatis (JavaScript)
Saat tombol *"Simpan"* ditekan, algoritma membaca data pixel canvas (`getImageData`):
1. Telusuri nilai alpha pixel untuk menemukan batas goresan tinta:
   - `minX` (koordinat pixel paling kiri)
   - `minY` (koordinat pixel paling atas)
   - `maxX` (koordinat pixel paling kanan)
   - `maxY` (koordinat pixel paling bawah)
2. Berikan batas padding aman (misal 15–20 pixel di sekeliling goresan).
3. Salin (*crop*) potongan tersebut ke canvas sementara dan ekspor menjadi **PNG transparan**.
4. Hasil ekspor adalah gambar tanda tangan padat yang siap ditempel proporsional pada dokumen cetak PDF / HTML.

```javascript
// Contoh Logika Auto-Crop Bounding Box
function getTrimmedCanvas(canvas, padding = 15) {
    const ctx = canvas.getContext('2d');
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const pixels = imgData.data;
    let minX = canvas.width, minY = canvas.height, maxX = 0, maxY = 0;
    let hasSignature = false;

    for (let y = 0; y < canvas.height; y++) {
        for (let x = 0; x < canvas.width; x++) {
            const alpha = pixels[(y * canvas.width + x) * 4 + 3];
            if (alpha > 0) {
                hasSignature = true;
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
            }
        }
    }

    if (!hasSignature) return null; // Canvas kosong

    minX = Math.max(0, minX - padding);
    minY = Math.max(0, minY - padding);
    maxX = Math.min(canvas.width, maxX + padding);
    maxY = Math.min(canvas.height, maxY + padding);

    const trimmedWidth = maxX - minX;
    const trimmedHeight = maxY - minY;

    const trimmedCanvas = document.createElement('canvas');
    trimmedCanvas.width = trimmedWidth;
    trimmedCanvas.height = trimmedHeight;
    const trimmedCtx = trimmedCanvas.getContext('2d');

    trimmedCtx.drawImage(canvas, minX, minY, trimmedWidth, trimmedHeight, 0, 0, trimmedWidth, trimmedHeight);
    return trimmedCanvas.toDataURL('image/png');
}
```

---

## 5. Kendali Ganda: Pasien & Petugas (Ergonomi & Efisiensi)

Pasien sering kali canggung menekan tombol di layar monitor menggunakan pena tablet. Oleh karena itu, diterapkan sistem kendali ganda:

1. **Aksi oleh Pasien**: Menggoreskan tanda tangan pada papan pen tablet fisik.
2. **Aksi oleh Petugas (via Keyboard Komputer)**:
   - **`Enter`** : Menyimpan tanda tangan langsung ke dokumen.
   - **`Space`** atau **`Delete`** : Membersihkan (*Clear*) canvas jika pasien ingin mengulang.
   - **`Escape`** : Menutup modal/batal.

Petugas cukup mengarahkan pasien: *"Silakan tanda tangan di papan hitam ini, Pak/Bu."* Setelah pasien meletakkan pena, petugas cukup menekan tombol `Enter` di keyboard komputer tanpa meminta pasien mencari letak tombol simpan.

---

## 6. Konfigurasi Kehalusan Goresan (Signature Smoothing)

Menggunakan library terintegrasi `signature_pad.js` dengan konfigurasi optimasi pen tablet:
- `minWidth`: `1.5` (ketebalan minimum saat goresan cepat).
- `maxWidth`: `3.5` (ketebalan maksimum saat tekanan lambat).
- `penColor`: `'#000080'` (warna biru tinta dokumen resmi) atau `'#000000'`.
- `velocityFilterWeight`: `0.7` (menghaluskan getaran tangan pasien agar tanda tangan tampak alami layaknya tinta basah di atas kertas).

---

## 7. Skema Penyimpanan Data & Cetak Dokumen

1. **Penyimpanan di Database / Berkas**:
   - Disimpan sebagai path gambar PNG transparan di server:
     `webapps/berkas/ttd_pasien/{no_rawat}_{kode_form}_{id}.png`
   - Atau disimpan sebagai kolom `ttd_pasien` (MEDIUMTEXT base64 data URL) pada tabel form terkait.
2. **Integrasi ke Template Cetak & mPDF**:
   - Pada file `cetak.html` formulir, kolom tanda tangan pasien/keluarga otomatis memuat:
     ```html
     <div class="ttd-box">
       {if: !empty($data.ttd_pasien)}
         <img src="{$data.ttd_pasien}" style="max-height: 80px; max-width: 180px; object-fit: contain;">
       {else}
         <div style="height: 80px;"></div>
       {/if}
       <p>( {$data.nama_penanggung_jawab} )</p>
     </div>
     ```

---

## 8. Opsi Pengembangan Lanjutan (Hybrid Mode)

Di masa mendatang, sistem dapat dikombinasikan dengan metode alternatif:
1. **Mode Tablet Poli (Touchscreen Kiosk)**: Menggunakan 1 tablet Android murah (Samsung Tab A9) per poli/nurse station yang membuka URL formulir TTD pasien via Wi-Fi lokal.
2. **Scan QR to Sign**: Menampilkan QR code di layar PC $\rightarrow$ Pasien scan menggunakan HP pribadi $\rightarrow$ Pasien membaca ringkasan persetujuan dan tanda tangan langsung di layar HP miliknya.

---

*Disiapkan untuk pengembangan Rekam Medis Elektronik (RME) mLite RSGM Gusti Hasan Aman.*

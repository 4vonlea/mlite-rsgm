-- -------------------------------------------------------------
-- Migrasi Penambahan Kolom kasus_poli pada pemeriksaan_ralan
-- Rumah Sakit Gigi dan Mulut (RSGM) / SIMKES Khanza - mLITE
-- -------------------------------------------------------------
-- Deskripsi:
-- Digunakan untuk mencatat status jenis kasus (Lama / Baru)
-- secara independen per catatan SOAP di rawat jalan, sementara
-- status kunjungan (stts_daftar) dan status poli kunjungan (status_poli)
-- tetap tersimpan di tabel reg_periksa.
-- -------------------------------------------------------------

ALTER TABLE `pemeriksaan_ralan` 
ADD COLUMN `kasus_poli` ENUM('Lama','Baru') NOT NULL DEFAULT 'Lama' AFTER `nip`;

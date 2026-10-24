<?php
/**
 * Seeder Data Sampel Penandaan Tepat Lokasi Bedah Mulut
 */

$pdo = new PDO("mysql:host=127.0.0.1;dbname=mlitersgm", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
ini_set('serialize_precision', '-1');

// Koordinat marker = persen terhadap gambar assets/img/penandaan_lokasi_mulut_kepala_clean.png (820x240)
// Mulut: x 1-37%, Depan: x 40-56%, Kanan: x 60-76%, Kiri: x 79-93%

// Pastikan tabel sudah ada
$pdo->exec("CREATE TABLE IF NOT EXISTS `penandaan_lokasi_bedah_mulut` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_rawat` varchar(20) NOT NULL,
  `no_rkm_medis` varchar(15) DEFAULT NULL,
  `jenis_tindakan` text DEFAULT NULL,
  `gigi_centang` text DEFAULT NULL,
  `markers_anatomi` longtext DEFAULT NULL,
  `gambar_penandaan` longtext DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  `nama_pasien_keluarga` varchar(150) DEFAULT NULL,
  `hubungan_pasien` varchar(50) DEFAULT 'Pasien',
  `kd_dokter` varchar(20) DEFAULT NULL,
  `nama_dokter` varchar(150) DEFAULT NULL,
  `nama_saksi` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `no_rawat` (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

// Kosongkan data lama jika ada
$pdo->exec("TRUNCATE TABLE `penandaan_lokasi_bedah_mulut`");

$samples = [
    [
        'no_rawat' => '2025/08/13/000040',
        'no_rkm_medis' => '055268',
        'jenis_tindakan' => 'Odontektomi Gigi 48 (M3 Bawah Kanan) dan Enukleasi Kista Folikular',
        'gigi_centang' => '48',
        'markers_anatomi' => json_encode([
            ['x' => 14.39, 'y' => 75.0],  // Regio molar rahang bawah kanan (48) pada gambar mulut
            ['x' => 68.29, 'y' => 50.0]   // Angulus mandibula kanan (kepala tampak Kanan)
        ]),
        'gambar_penandaan' => '',
        'tanggal' => '2026-10-22',
        'jam' => '09:30:00',
        'nama_pasien_keluarga' => 'Muhammad Rifani (Ayah)',
        'hubungan_pasien' => 'Ayah',
        'kd_dokter' => '196311041993011002',
        'nama_dokter' => 'drg. R. HARRY DHARMAWAN S, M.Kes',
        'nama_saksi' => 'Ns. Siti Aminah, S.Kep',
        'catatan' => 'Verifikasi site marking telah dikonfirmasi bersama pasien dan keluarga sebelum induksi anestesi.'
    ],
    [
        'no_rawat' => '2024/12/02/000001',
        'no_rkm_medis' => '000001',
        'jenis_tindakan' => 'Eksisi Mukokel Bibir Bawah Kiri dan Ekstraksi Gigi Impaksi 38',
        'gigi_centang' => '38',
        'markers_anatomi' => json_encode([
            ['x' => 21.71, 'y' => 88.33], // Mukosa labial bibir bawah kiri
            ['x' => 23.41, 'y' => 75.0],  // Regio molar rahang bawah kiri (38)
            ['x' => 85.98, 'y' => 50.0]   // Angulus mandibula kiri (kepala tampak Kiri)
        ]),
        'gambar_penandaan' => '',
        'tanggal' => '2026-10-23',
        'jam' => '10:15:00',
        'nama_pasien_keluarga' => 'Jhon Doe',
        'hubungan_pasien' => 'Pasien',
        'kd_dokter' => '195707081982031014',
        'nama_dokter' => 'Prof. Dr. drg. ROSIHAN ADHANI, S.Sos, M.S',
        'nama_saksi' => 'Ns. Hendra Wijaya, S.Kep',
        'catatan' => 'Tanda centang telah diverifikasi tepat pada mukosa labial inferior sinistra dan regio 38.'
    ],
    [
        'no_rawat' => '2024/12/02/000003',
        'no_rkm_medis' => '000002',
        'jenis_tindakan' => 'Odontektomi Gigi 18 dan 28 (Molar 3 Rahang Atas Bilateral)',
        'gigi_centang' => '18,28',
        'markers_anatomi' => json_encode([
            ['x' => 14.02, 'y' => 35.42], // Regio molar rahang atas kanan (18)
            ['x' => 23.78, 'y' => 35.42], // Regio molar rahang atas kiri (28)
            ['x' => 44.88, 'y' => 40.83], // Pipi kanan (kepala tampak Depan)
            ['x' => 49.02, 'y' => 40.83]  // Pipi kiri (kepala tampak Depan)
        ]),
        'gambar_penandaan' => '',
        'tanggal' => '2026-10-23',
        'jam' => '13:00:00',
        'nama_pasien_keluarga' => 'Jane Doe',
        'hubungan_pasien' => 'Pasien',
        'kd_dokter' => '196909202000031005',
        'nama_dokter' => 'drg. TEGUH HADIANTO, Sp.KG',
        'nama_saksi' => 'Ns. Dewi Anggraini, S.Kep',
        'catatan' => 'Penandaan tepat di regio gigi 18 dan 28 rahang atas kanan dan kiri.'
    ]
];

$stmt = $pdo->prepare("INSERT INTO `penandaan_lokasi_bedah_mulut` 
    (`no_rawat`, `no_rkm_medis`, `jenis_tindakan`, `gigi_centang`, `markers_anatomi`, `gambar_penandaan`, `tanggal`, `jam`, `nama_pasien_keluarga`, `hubungan_pasien`, `kd_dokter`, `nama_dokter`, `nama_saksi`, `catatan`, `created_at`, `updated_at`) 
    VALUES (:no_rawat, :no_rkm_medis, :jenis_tindakan, :gigi_centang, :markers_anatomi, :gambar_penandaan, :tanggal, :jam, :nama_pasien_keluarga, :hubungan_pasien, :kd_dokter, :nama_dokter, :nama_saksi, :catatan, NOW(), NOW())");

foreach ($samples as $s) {
    $stmt->execute([
        ':no_rawat' => $s['no_rawat'],
        ':no_rkm_medis' => $s['no_rkm_medis'],
        ':jenis_tindakan' => $s['jenis_tindakan'],
        ':gigi_centang' => $s['gigi_centang'],
        ':markers_anatomi' => $s['markers_anatomi'],
        ':gambar_penandaan' => $s['gambar_penandaan'],
        ':tanggal' => $s['tanggal'],
        ':jam' => $s['jam'],
        ':nama_pasien_keluarga' => $s['nama_pasien_keluarga'],
        ':hubungan_pasien' => $s['hubungan_pasien'],
        ':kd_dokter' => $s['kd_dokter'],
        ':nama_dokter' => $s['nama_dokter'],
        ':nama_saksi' => $s['nama_saksi'],
        ':catatan' => $s['catatan']
    ]);
    echo "Seeded: {$s['no_rawat']} - {$s['jenis_tindakan']}\n";
}

echo "Seeder Selesai! Berhasil memasukkan " . count($samples) . " data sampel Penandaan Tepat Lokasi Bedah Mulut.\n";

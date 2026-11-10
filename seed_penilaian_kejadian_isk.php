<?php
define('BASE_DIR', __DIR__);
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME . ';charset=utf8mb4', DBUSER, DBPASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Bersihkan data lama jika ada
    $pdo->exec("TRUNCATE TABLE `lampiran_penilaian_kejadian_isk`");

    // Case 1: AHMAD SYAFIQ RIFANI
    $detail1 = [
        1 => [
            'tgl' => '2026-10-10',
            'param_1' => true,
            'param_2' => false,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 0,
            'petugas' => 'Ns. Siti Rahmah, S.Kep'
        ],
        2 => [
            'tgl' => '2026-10-11',
            'param_1' => false,
            'param_2' => true,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 1,
            'petugas' => 'Ns. Siti Rahmah, S.Kep'
        ],
        3 => [
            'tgl' => '2026-10-12',
            'param_1' => false,
            'param_2' => true,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 1,
            'petugas' => 'Ns. Ahmad Fauzi, S.Kep'
        ],
        4 => [
            'tgl' => '2026-10-13',
            'param_1' => true,
            'param_2' => false,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 0,
            'petugas' => 'Ns. Ahmad Fauzi, S.Kep'
        ],
        5 => [
            'tgl' => '2026-10-14',
            'param_1' => true,
            'param_2' => false,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 0,
            'petugas' => 'Ns. Siti Rahmah, S.Kep'
        ]
    ];

    // Case 2: Jhon Doe
    $detail2 = [
        1 => [
            'tgl' => '2026-10-11',
            'param_1' => false,
            'param_2' => true,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 1,
            'petugas' => 'Ns. Dewi Lestari, S.Kep'
        ],
        2 => [
            'tgl' => '2026-10-12',
            'param_1' => false,
            'param_2' => true,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 1,
            'petugas' => 'Ns. Dewi Lestari, S.Kep'
        ],
        3 => [
            'tgl' => '2026-10-13',
            'param_1' => false,
            'param_2' => true,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 1,
            'petugas' => 'Ns. Budi Santoso, A.Md.Kep'
        ],
        4 => [
            'tgl' => '2026-10-14',
            'param_1' => true,
            'param_2' => false,
            'param_3' => false,
            'param_4' => false,
            'total_skor' => 0,
            'petugas' => 'Ns. Budi Santoso, A.Md.Kep'
        ]
    ];

    // Case 3: Jane Doe (Pediatrik)
    $detail3 = [
        1 => [
            'tgl' => '2026-10-12',
            'param_1' => false,
            'param_2' => false,
            'param_3' => true,
            'param_4' => false,
            'total_skor' => 2,
            'petugas' => 'Ns. Fitriani, S.Kep'
        ],
        2 => [
            'tgl' => '2026-10-13',
            'param_1' => false,
            'param_2' => false,
            'param_3' => false,
            'param_4' => true,
            'total_skor' => 3,
            'petugas' => 'Ns. Fitriani, S.Kep'
        ],
        3 => [
            'tgl' => '2026-10-14',
            'param_1' => false,
            'param_2' => false,
            'param_3' => true,
            'param_4' => false,
            'total_skor' => 2,
            'petugas' => 'Ns. Fitriani, S.Kep'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO `lampiran_penilaian_kejadian_isk` 
        (`no_rawat`, `no_rkm_medis`, `kamar_inap`, `tanggal_penilaian`, `detail_penilaian`, `catatan`, `created_at`, `updated_at`) 
        VALUES (:no_rawat, :no_rkm_medis, :kamar_inap, :tanggal_penilaian, :detail_penilaian, :catatan, NOW(), NOW())");

    $stmt->execute([
        ':no_rawat' => '2025/08/13/000040',
        ':no_rkm_medis' => '055268',
        ':kamar_inap' => 'Ruang Bedah Mulut VIP 01',
        ':tanggal_penilaian' => '2026-10-10',
        ':detail_penilaian' => json_encode($detail1),
        ':catatan' => 'Pasien pasca tindakan bedah mulut terpasang kateter urin selama 2 hari. Pada hari ke-3 kateter dilepas, hidrasi adekuat, keluhan miksi membaik.'
    ]);

    $stmt->execute([
        ':no_rawat' => '2024/12/02/000001',
        ':no_rkm_medis' => '000001',
        ':kamar_inap' => 'Ruang Rawat Inap Kelas 1 / Bed 02',
        ':tanggal_penilaian' => '2026-10-11',
        ':detail_penilaian' => json_encode($detail2),
        ':catatan' => 'Pemantauan tanda infeksi saluran kemih harian. Diberikan antibiotik profilaksis dan kultur urin lanjutan.'
    ]);

    $stmt->execute([
        ':no_rawat' => '2024/12/02/000003',
        ':no_rkm_medis' => '000002',
        ':kamar_inap' => 'Ruang Rawat Inap VIP Melati 03',
        ':tanggal_penilaian' => '2026-10-12',
        ':detail_penilaian' => json_encode($detail3),
        ':catatan' => 'Pasien anak balita dengan febris dan dugaan ISK. Respon terapi antibiotik baik, klinis aktif kembali.'
    ]);

    echo "Seeder Penilaian Kejadian ISK berhasil! 3 data sampel berhasil diisi.\n";

} catch (Exception $e) {
    echo "Error Seeder: " . $e->getMessage() . "\n";
}

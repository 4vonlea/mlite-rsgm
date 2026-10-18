<?php
/**
 * Seeder Data Sampel Penilaian Kejadian Infeksi Luka Operasi (ILO)
 */

$pdo = new PDO("mysql:host=localhost;dbname=mlitersgm", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Kosongkan tabel sampel jika sudah ada
$pdo->exec("TRUNCATE TABLE `lampiran_penilaian_kejadian_ilo`");

$cases = [
    [
        'no_rawat' => '2025/08/13/000040',
        'no_rkm_medis' => '055268',
        'kamar_inap' => 'Ruang Bedah Mulut VIP 01',
        'tanggal_penilaian' => '2026-10-10',
        'catatan' => 'Pasien post odontektomi M3 impaksi kelas IIA. Tidak ditemukan tanda infeksi, luka bersih dan granulasi baik.',
        'detail' => [
            1 => ['tgl' => '2026-10-10', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Dewi Anggraini, S.Kep'],
            2 => ['tgl' => '2026-10-11', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Dewi Anggraini, S.Kep'],
            3 => ['tgl' => '2026-10-12', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Dewi Anggraini, S.Kep'],
            4 => ['tgl' => '2026-10-13', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Hendra Wijaya, S.Kep'],
            5 => ['tgl' => '2026-10-14', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Hendra Wijaya, S.Kep']
        ]
    ],
    [
        'no_rawat' => '2024/12/02/000001',
        'no_rkm_medis' => '000001',
        'kamar_inap' => 'Ruang Rawat Inap Kelas 1 / Bed 02',
        'tanggal_penilaian' => '2026-10-11',
        'catatan' => 'Pasien post enukleasi kista mandibula. Hari ke-2 dan ke-3 terdapat sedikit kemerahan di sekitar jahitan, hari ke-4 membaik pasca perawatan antiseptik.',
        'detail' => [
            1 => ['tgl' => '2026-10-11', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Budi Santoso, S.Kep'],
            2 => ['tgl' => '2026-10-12', 'parameter' => 1, 'total_skor' => 1, 'petugas' => 'Ns. Budi Santoso, S.Kep'],
            3 => ['tgl' => '2026-10-13', 'parameter' => 2, 'total_skor' => 2, 'petugas' => 'Ns. Budi Santoso, S.Kep'],
            4 => ['tgl' => '2026-10-14', 'parameter' => 1, 'total_skor' => 1, 'petugas' => 'Ns. Ratna Sari, S.Kep'],
            5 => ['tgl' => '2026-10-15', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Ratna Sari, S.Kep'],
            6 => ['tgl' => '2026-10-16', 'parameter' => 0, 'total_skor' => 0, 'petugas' => 'Ns. Ratna Sari, S.Kep']
        ]
    ],
    [
        'no_rawat' => '2024/12/02/000003',
        'no_rkm_medis' => '000002',
        'kamar_inap' => 'Ruang Rawat Inap VIP Melati 03',
        'tanggal_penilaian' => '2026-10-12',
        'catatan' => 'Pasien post rekonstruksi fraktur maksilofasial. Hari ke-4 keluar sedikit pus pada salah satu titik jahitan (<2 cm). DPJP telah menginstruksikan kultur & sensitivitas serta penggantian antibiotik oral ke IV.',
        'detail' => [
            1 => ['tgl' => '2026-10-12', 'parameter' => 1, 'total_skor' => 1, 'petugas' => 'Ns. Siti Nurhaliza, S.Kep'],
            2 => ['tgl' => '2026-10-13', 'parameter' => 2, 'total_skor' => 2, 'petugas' => 'Ns. Siti Nurhaliza, S.Kep'],
            3 => ['tgl' => '2026-10-14', 'parameter' => 3, 'total_skor' => 3, 'petugas' => 'Ns. Siti Nurhaliza, S.Kep'],
            4 => ['tgl' => '2026-10-15', 'parameter' => 4, 'total_skor' => 4, 'petugas' => 'Ns. Siti Nurhaliza, S.Kep']
        ]
    ]
];

$stmt = $pdo->prepare("INSERT INTO `lampiran_penilaian_kejadian_ilo` 
    (`no_rawat`, `no_rkm_medis`, `kamar_inap`, `tanggal_penilaian`, `detail_penilaian`, `catatan`, `created_at`, `updated_at`) 
    VALUES (:no_rawat, :no_rkm_medis, :kamar_inap, :tanggal_penilaian, :detail_penilaian, :catatan, NOW(), NOW())");

foreach ($cases as $c) {
    $stmt->execute([
        ':no_rawat' => $c['no_rawat'],
        ':no_rkm_medis' => $c['no_rkm_medis'],
        ':kamar_inap' => $c['kamar_inap'],
        ':tanggal_penilaian' => $c['tanggal_penilaian'],
        ':detail_penilaian' => json_encode($c['detail']),
        ':catatan' => $c['catatan']
    ]);
    echo "Seeded: {$c['no_rawat']} - {$c['kamar_inap']}\n";
}

echo "Seeder Selesai! Berhasil memasukkan " . count($cases) . " data sampel ILO.\n";

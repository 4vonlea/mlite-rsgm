<?php
/**
 * Seeder data sampel untuk lampiran_penilaian_dekubitus
 */
define('BASE_DIR', __DIR__);
require_once __DIR__ . '/config.php';

$db_host = defined('DBHOST') ? DBHOST : '127.0.0.1';
$db_port = defined('DBPORT') ? DBPORT : '3306';
$db_user = defined('DBUSER') ? DBUSER : 'root';
$db_pass = defined('DBPASS') ? DBPASS : '';
$db_name = defined('DBNAME') ? DBNAME : 'mlitersgm';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Bersihkan data lama jika ada
    $pdo->exec("DELETE FROM lampiran_penilaian_dekubitus");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'kamar_inap' => 'Ruang Diamond / Bed 03',
            'tanggal_penilaian' => '2025-10-10',
            'detail_penilaian' => json_encode([
                1 => [
                    'tgl' => '2025-10-10',
                    'kondisi_fisik' => 2,
                    'kesadaran' => 3,
                    'aktivitas' => 1,
                    'mobilitas' => 2,
                    'inkontinensia' => 3,
                    'total_skor' => 11,
                    'perawat' => 'Muhammad Raihan, A.Md.Kes'
                ],
                2 => [
                    'tgl' => '2025-10-11',
                    'kondisi_fisik' => 2,
                    'kesadaran' => 3,
                    'aktivitas' => 1,
                    'mobilitas' => 2,
                    'inkontinensia' => 3,
                    'total_skor' => 11,
                    'perawat' => 'Muhammad Raihan, A.Md.Kes'
                ],
                3 => [
                    'tgl' => '2025-10-12',
                    'kondisi_fisik' => 2,
                    'kesadaran' => 4,
                    'aktivitas' => 1,
                    'mobilitas' => 2,
                    'inkontinensia' => 3,
                    'total_skor' => 12,
                    'perawat' => 'MUHAMMAD ZAIDI, S.ST'
                ],
                4 => [
                    'tgl' => '2025-10-13',
                    'kondisi_fisik' => 3,
                    'kesadaran' => 4,
                    'aktivitas' => 2,
                    'mobilitas' => 2,
                    'inkontinensia' => 3,
                    'total_skor' => 14,
                    'perawat' => 'ARDA WIJAYA, A.Md.Tem'
                ]
            ]),
            'catatan' => 'Pasien pasca operasi fraktur maksilofasial dengan tirah baring ketat. Terpasang kasur anti-dekubitus aktif, alih baring miring kanan-kiri setiap 2 jam, perawatan kulit kering sacrum.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'no_rawat' => '2024/12/02/000001',
            'no_rkm_medis' => '000001',
            'kamar_inap' => 'Ruang Melati / Bed 01',
            'tanggal_penilaian' => '2025-10-12',
            'detail_penilaian' => json_encode([
                1 => [
                    'tgl' => '2025-10-12',
                    'kondisi_fisik' => 1,
                    'kesadaran' => 2,
                    'aktivitas' => 1,
                    'mobilitas' => 1,
                    'inkontinensia' => 1,
                    'total_skor' => 6,
                    'perawat' => 'MUHAMMAD ZAIDI, S.ST'
                ],
                2 => [
                    'tgl' => '2025-10-13',
                    'kondisi_fisik' => 2,
                    'kesadaran' => 2,
                    'aktivitas' => 1,
                    'mobilitas' => 1,
                    'inkontinensia' => 2,
                    'total_skor' => 8,
                    'perawat' => 'Muhammad Raihan, A.Md.Kes'
                ],
                3 => [
                    'tgl' => '2025-10-14',
                    'kondisi_fisik' => 2,
                    'kesadaran' => 3,
                    'aktivitas' => 1,
                    'mobilitas' => 2,
                    'inkontinensia' => 2,
                    'total_skor' => 10,
                    'perawat' => 'MUHAMMAD ZAIDI, S.ST'
                ]
            ]),
            'catatan' => 'Pasien infeksi berat dengan penurunan kesadaran. Risiko tinggi dekubitus (skor < 12). Dilakukan edukasi reposisi berkala dan kebersihan linen.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'no_rawat' => '2024/12/02/000003',
            'no_rkm_medis' => '000002',
            'kamar_inap' => 'Ruang Mawar / Bed 04',
            'tanggal_penilaian' => '2025-10-14',
            'detail_penilaian' => json_encode([
                1 => [
                    'tgl' => '2025-10-14',
                    'kondisi_fisik' => 3,
                    'kesadaran' => 4,
                    'aktivitas' => 3,
                    'mobilitas' => 3,
                    'inkontinensia' => 4,
                    'total_skor' => 17,
                    'perawat' => 'ARDA WIJAYA, A.Md.Tem'
                ],
                2 => [
                    'tgl' => '2025-10-15',
                    'kondisi_fisik' => 4,
                    'kesadaran' => 4,
                    'aktivitas' => 4,
                    'mobilitas' => 4,
                    'inkontinensia' => 4,
                    'total_skor' => 20,
                    'perawat' => 'ARDA WIJAYA, A.Md.Tem'
                ]
            ]),
            'catatan' => 'Pasien post odontektomi elektif hari ke-2. Kondisi umum stabil, mobilisasi mandiri baik, tidak berisiko dekubitus (Skor 20).',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO lampiran_penilaian_dekubitus (
            no_rawat, no_rkm_medis, kamar_inap, tanggal_penilaian,
            detail_penilaian, catatan, created_at, updated_at
        ) VALUES (
            :no_rawat, :no_rkm_medis, :kamar_inap, :tanggal_penilaian,
            :detail_penilaian, :catatan, :created_at, :updated_at
        )
    ");

    foreach ($samples as $sample) {
        $stmt->execute($sample);
    }

    echo "Berhasil mengisi " . count($samples) . " sampel data penilaian dekubitus!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

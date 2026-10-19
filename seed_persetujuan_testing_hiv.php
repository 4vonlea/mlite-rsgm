<?php
define('BASE_DIR', __DIR__);
require_once __DIR__ . '/config.php';

$db_host = defined('DBHOST') ? DBHOST : 'localhost';
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
    $pdo->exec("TRUNCATE TABLE `lampiran_persetujuan_testing_hiv`");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'kode_klien' => 'VCT/2026/10/001',
            'tanggal' => '2026-10-16',
            'tanggal_pelaksanaan' => '2026-10-17',
            'nama_pasien' => 'AHMAD SYAFIQ RIFANI',
            'kd_konselor' => '199403032202201',
            'nama_konselor' => 'drg. EKARIANI NURRAHMIDAWATI',
            'status_persetujuan' => 'Setuju',
            'catatan' => 'Skrining pre-operatif tindakan bedah mulut mayor. Telah diberikan konseling pra-tes secara lengkap dan pasien memahami seluruh penjelasan yang diberikan.'
        ],
        [
            'no_rawat' => '2025/02/23/000004',
            'no_rkm_medis' => '000001',
            'kode_klien' => 'VCT/2026/10/002',
            'tanggal' => '2026-10-16',
            'tanggal_pelaksanaan' => '2026-10-17',
            'nama_pasien' => 'Jhon Doe',
            'kd_konselor' => '198902052015032002',
            'nama_konselor' => 'drg. WINDA ARIANI',
            'status_persetujuan' => 'Setuju',
            'catatan' => 'Konseling sukarela (VCT) atas inisiatif mandiri pasien dan skrining kesehatan rutin rawat inap.'
        ],
        [
            'no_rawat' => '2025/04/14/000001',
            'no_rkm_medis' => '000002',
            'kode_klien' => 'VCT/2026/10/003',
            'tanggal' => '2026-10-17',
            'tanggal_pelaksanaan' => '2026-10-18',
            'nama_pasien' => 'Jane Doe',
            'kd_konselor' => '199003092019031006',
            'nama_konselor' => 'drg. TEGUH HADIANTO, Sp.KG',
            'status_persetujuan' => 'Setuju',
            'catatan' => 'Skrining rujukan pra-tindakan konservasi gigi dan perawatan endodontik khusus bedah.'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `lampiran_persetujuan_testing_hiv` (
            `no_rawat`, `no_rkm_medis`, `kode_klien`, `tanggal`, `tanggal_pelaksanaan`,
            `nama_pasien`, `kd_konselor`, `nama_konselor`, `status_persetujuan`, `catatan`
        ) VALUES (
            :no_rawat, :no_rkm_medis, :kode_klien, :tanggal, :tanggal_pelaksanaan,
            :nama_pasien, :kd_konselor, :nama_konselor, :status_persetujuan, :catatan
        )
    ");

    $count = 0;
    foreach ($samples as $s) {
        $stmt->execute($s);
        $count++;
    }

    echo "Berhasil mengisi {$count} data sampel ke tabel `lampiran_persetujuan_testing_hiv`!\n";

} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage() . "\n";
    exit(1);
}

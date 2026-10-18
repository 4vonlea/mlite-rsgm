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
    $pdo->exec("TRUNCATE TABLE `lampiran_surat_pernyataan_bersedia_menunggu`");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'nama_pj' => 'Muhammad Ridwan, S.Kom',
            'tgl_lahir_pj' => '1982-08-15',
            'jk_pj' => 'Laki-laki',
            'alamat_pj' => 'Komp. Keruwing Indah Blok Gelatik A16 No. 30 RT. 38, Banjarmasin',
            'no_identitas_pj' => '6371011508820003',
            'hubungan' => 'Anak',
            'nama_pasien' => 'AHMAD SYAFIQ RIFANI',
            'tgl_lahir_pasien' => '2018-06-19',
            'jk_pasien' => 'Laki-laki',
            'alamat_pasien' => 'Komp. Keruwing Indah Blok Gelatik A16 No. 30 RT. 38, Banjarmasin',
            'ruang_menunggu' => 'ruang perawatan biasa / ruang perawatan intensif',
            'tanggal_surat' => '2026-10-15',
            'kd_dokter' => '199403032202201',
            'nama_dokter' => 'drg. EKARIANI NURRAHMIDAWATI',
            'catatan' => 'Pasien anak observasi pasca trauma gigi & bibir di IGD, menunggu konfirmasi bed rawat inap anak/VIP.'
        ],
        [
            'no_rawat' => '2025/02/23/000004',
            'no_rkm_medis' => '000001',
            'nama_pj' => 'Jhon Doe',
            'tgl_lahir_pj' => '1990-05-12',
            'jk_pj' => 'Laki-laki',
            'alamat_pj' => 'Jl. Ahmad Yani Km. 4.5 No. 12 RT. 05, Banjarmasin',
            'no_identitas_pj' => '6371021205900002',
            'hubungan' => 'Diri Saya Sendiri',
            'nama_pasien' => 'Jhon Doe',
            'tgl_lahir_pasien' => '1990-05-12',
            'jk_pasien' => 'Laki-laki',
            'alamat_pasien' => 'Jl. Ahmad Yani Km. 4.5 No. 12 RT. 05, Banjarmasin',
            'ruang_menunggu' => 'ruang perawatan biasa',
            'tanggal_surat' => '2026-10-16',
            'kd_dokter' => '198902052015032002',
            'nama_dokter' => 'drg. WINDA ARIANI',
            'catatan' => 'Pasien menyatakan bersedia menunggu ruang perawatan kelas 1 yang sedang disiapkan/dibersihkan.'
        ],
        [
            'no_rawat' => '2025/04/14/000001',
            'no_rkm_medis' => '000002',
            'nama_pj' => 'Hendra Setiawan',
            'tgl_lahir_pj' => '1988-11-20',
            'jk_pj' => 'Laki-laki',
            'alamat_pj' => 'Jl. Sultan Adam Komp. Mandiri Lestari No. 42 Banjarmasin',
            'no_identitas_pj' => '6371032011880005',
            'hubungan' => 'Istri',
            'nama_pasien' => 'Jane Doe',
            'tgl_lahir_pasien' => '1992-07-14',
            'jk_pasien' => 'Perempuan',
            'alamat_pasien' => 'Jl. Sultan Adam Komp. Mandiri Lestari No. 42 Banjarmasin',
            'ruang_menunggu' => 'ruang perawatan biasa / ruang perawatan intensif',
            'tanggal_surat' => '2026-10-17',
            'kd_dokter' => '196909202000031005',
            'nama_dokter' => 'drg. TEGUH HADIANTO, Sp.KG',
            'catatan' => 'Suami pasien menyetujui pasien diobservasi di ruang transit IGD selama menunggu kamar rawat inap.'
        ]
    ];

    $sql = "INSERT INTO `lampiran_surat_pernyataan_bersedia_menunggu` 
        (`no_rawat`, `no_rkm_medis`, `nama_pj`, `tgl_lahir_pj`, `jk_pj`, `alamat_pj`, `no_identitas_pj`, `hubungan`, `nama_pasien`, `tgl_lahir_pasien`, `jk_pasien`, `alamat_pasien`, `ruang_menunggu`, `tanggal_surat`, `kd_dokter`, `nama_dokter`, `catatan`) 
        VALUES 
        (:no_rawat, :no_rkm_medis, :nama_pj, :tgl_lahir_pj, :jk_pj, :alamat_pj, :no_identitas_pj, :hubungan, :nama_pasien, :tgl_lahir_pasien, :jk_pasien, :alamat_pasien, :ruang_menunggu, :tanggal_surat, :kd_dokter, :nama_dokter, :catatan)";

    $stmt = $pdo->prepare($sql);

    foreach ($samples as $idx => $sample) {
        $stmt->execute($sample);
        echo "Berhasil memasukkan data sampel #" . ($idx + 1) . ": {$sample['nama_pj']} -> {$sample['nama_pasien']} ({$sample['hubungan']})\n";
    }

    echo "\nSeed data LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU berhasil dimasukkan!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

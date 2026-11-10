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
    $pdo->exec("TRUNCATE TABLE `lampiran_penundaan_kelambatan_pelayanan`");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'tanggal' => '2026-10-16',
            'jam' => '09:15:00',
            'instalasi_unit' => 'Instalasi Bedah Sentral (IBS)',
            'kd_dokter_pengirim' => '199403032202201',
            'nama_dokter_pengirim' => 'drg. EKARIANI NURRAHMIDAWATI',
            'jenis_pelayanan' => 'Operasi Odontektomi M3 Impaksi Bilateral dengan General Anestesi',
            'status_penerima' => 'Keluarga',
            'nama_penerima' => 'Muhammad Ridwan, S.Kom',
            'alamat_penerima' => 'Komp. Keruwing Indah Blok Gelatik A16 No. 30 RT. 38, Banjarmasin',
            'no_identitas_penerima' => '6371011508820003',
            'sebab_kerusakan_alat' => 0,
            'sebab_kondisi_umum_pasien' => 1,
            'sebab_penundaan_penjadwalan' => 0,
            'sebab_pemadaman_listrik' => 0,
            'sebab_tempat_tidur_penuh' => 0,
            'sebab_faktor_penyulit' => 1,
            'keterangan_faktor_penyulit' => 'Pasien demam akut suhu 38.8 C dan batuk berdahak pra-induksi, risiko spasme saluran napas',
            'solusi_dijadwalkan_ulang' => 1,
            'jadwal_akan_datang' => 'Senin, 20 Oktober 2026 pukul 08:30 WITA di IBS (Prioritas 1)',
            'solusi_dirujuk' => 0,
            'tujuan_rujukan' => '',
            'solusi_kembali_dokter_pengirim' => 0,
            'kd_dokter_dpjp' => '199403032202201',
            'nama_dokter_dpjp' => 'drg. EKARIANI NURRAHMIDAWATI',
            'kd_pemberi_informasi' => '199312102022032003',
            'nama_pemberi_informasi' => 'dr. DESY PUSPITA SARI',
            'catatan' => 'Keluarga memahami alasan penundaan medis demi keselamatan pasien (patient safety). Pasien diterapi antipiretik & ekspektoran.'
        ],
        [
            'no_rawat' => '2025/02/23/000004',
            'no_rkm_medis' => '000001',
            'tanggal' => '2026-10-16',
            'jam' => '11:30:00',
            'instalasi_unit' => 'Instalasi Radiologi',
            'kd_dokter_pengirim' => '198902052015032002',
            'nama_dokter_pengirim' => 'drg. WINDA ARIANI',
            'jenis_pelayanan' => 'Pemeriksaan Radiologi CBCT 3D Mandibula',
            'status_penerima' => 'Pasien Sendiri',
            'nama_penerima' => 'Jhon Doe',
            'alamat_penerima' => 'Jl. Ahmad Yani Km. 4.5 No. 12 RT. 05, Banjarmasin',
            'no_identitas_penerima' => '6371021205900002',
            'sebab_kerusakan_alat' => 1,
            'sebab_kondisi_umum_pasien' => 0,
            'sebab_penundaan_penjadwalan' => 0,
            'sebab_pemadaman_listrik' => 0,
            'sebab_tempat_tidur_penuh' => 0,
            'sebab_faktor_penyulit' => 0,
            'keterangan_faktor_penyulit' => '',
            'solusi_dijadwalkan_ulang' => 1,
            'jadwal_akan_datang' => 'Sabtu, 17 Oktober 2026 pukul 10:00 WITA',
            'solusi_dirujuk' => 0,
            'tujuan_rujukan' => '',
            'solusi_kembali_dokter_pengirim' => 0,
            'kd_dokter_dpjp' => '198902052015032002',
            'nama_dokter_dpjp' => 'drg. WINDA ARIANI',
            'kd_pemberi_informasi' => '',
            'nama_pemberi_informasi' => 'Muhammad Raihan, A.Md.Kes',
            'catatan' => 'Unit scanner CBCT memerlukan kalibrasi sensor oleh tim elektromedik vendor.'
        ],
        [
            'no_rawat' => '2025/04/14/000001',
            'no_rkm_medis' => '000002',
            'tanggal' => '2026-10-17',
            'jam' => '08:45:00',
            'instalasi_unit' => 'Instalasi Rawat Inap',
            'kd_dokter_pengirim' => '196909202000031005',
            'nama_dokter_pengirim' => 'drg. TEGUH HADIANTO, Sp.KG',
            'jenis_pelayanan' => 'Perawatan Saluran Akar Kompleks & Observasi Rawat Inap',
            'status_penerima' => 'Penanggung Jawab Pasien',
            'nama_penerima' => 'Hendra Setiawan',
            'alamat_penerima' => 'Jl. Sultan Adam Komp. Mandiri Lestari No. 42 Banjarmasin',
            'no_identitas_penerima' => '6371032011880005',
            'sebab_kerusakan_alat' => 0,
            'sebab_kondisi_umum_pasien' => 0,
            'sebab_penundaan_penjadwalan' => 0,
            'sebab_pemadaman_listrik' => 0,
            'sebab_tempat_tidur_penuh' => 1,
            'sebab_faktor_penyulit' => 0,
            'keterangan_faktor_penyulit' => '',
            'solusi_dijadwalkan_ulang' => 1,
            'jadwal_akan_datang' => 'Sabtu, 17 Oktober 2026 pukul 14:00 WITA (Ruang Diamond Bed 02)',
            'solusi_dirujuk' => 0,
            'tujuan_rujukan' => '',
            'solusi_kembali_dokter_pengirim' => 0,
            'kd_dokter_dpjp' => '196909202000031005',
            'nama_dokter_dpjp' => 'drg. TEGUH HADIANTO, Sp.KG',
            'kd_pemberi_informasi' => '',
            'nama_pemberi_informasi' => 'MUHAMMAD ZAIDI, S.ST',
            'catatan' => 'Pasien dipersilakan menunggu di ruang transit ber-AC sementara kamar rawat inap sedang disterilisasi.'
        ]
    ];

    $sql = "INSERT INTO `lampiran_penundaan_kelambatan_pelayanan` 
        (`no_rawat`, `no_rkm_medis`, `tanggal`, `jam`, `instalasi_unit`, `kd_dokter_pengirim`, `nama_dokter_pengirim`, `jenis_pelayanan`, `status_penerima`, `nama_penerima`, `alamat_penerima`, `no_identitas_penerima`, `sebab_kerusakan_alat`, `sebab_kondisi_umum_pasien`, `sebab_penundaan_penjadwalan`, `sebab_pemadaman_listrik`, `sebab_tempat_tidur_penuh`, `sebab_faktor_penyulit`, `keterangan_faktor_penyulit`, `solusi_dijadwalkan_ulang`, `jadwal_akan_datang`, `solusi_dirujuk`, `tujuan_rujukan`, `solusi_kembali_dokter_pengirim`, `kd_dokter_dpjp`, `nama_dokter_dpjp`, `kd_pemberi_informasi`, `nama_pemberi_informasi`, `catatan`) 
        VALUES 
        (:no_rawat, :no_rkm_medis, :tanggal, :jam, :instalasi_unit, :kd_dokter_pengirim, :nama_dokter_pengirim, :jenis_pelayanan, :status_penerima, :nama_penerima, :alamat_penerima, :no_identitas_penerima, :sebab_kerusakan_alat, :sebab_kondisi_umum_pasien, :sebab_penundaan_penjadwalan, :sebab_pemadaman_listrik, :sebab_tempat_tidur_penuh, :sebab_faktor_penyulit, :keterangan_faktor_penyulit, :solusi_dijadwalkan_ulang, :jadwal_akan_datang, :solusi_dirujuk, :tujuan_rujukan, :solusi_kembali_dokter_pengirim, :kd_dokter_dpjp, :nama_dokter_dpjp, :kd_pemberi_informasi, :nama_pemberi_informasi, :catatan)";

    $stmt = $pdo->prepare($sql);

    foreach ($samples as $idx => $sample) {
        $stmt->execute($sample);
        echo "Berhasil memasukkan data sampel #" . ($idx + 1) . ": {$sample['no_rawat']} ({$sample['instalasi_unit']} - {$sample['jenis_pelayanan']})\n";
    }

    echo "\nSeed data LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN berhasil dimasukkan!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

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
    $pdo->exec("DELETE FROM `lampiran_persetujuan_tindakan_radiologi` WHERE no_rawat IN ('2025/09/29/000001', '2025/09/29/000051')");

    $samples = [
        [
            'no_rawat' => '2025/09/29/000001',
            'no_rkm_medis' => '055215',
            'nama_penanggung_jawab' => 'TSULAIKHA SAFRIANI',
            'jk_penanggung_jawab' => 'P',
            'tmp_lahir_penanggung_jawab' => 'Banjarmasin',
            'tgl_lahir_penanggung_jawab' => '1992-08-17',
            'usia_penanggung_jawab' => '33',
            'no_identitas_penanggung_jawab' => '6371015708920002',
            'jenis_identitas_penanggung_jawab' => 'KTP',
            'alamat_penanggung_jawab' => 'Jl. Tejosari Raya Perumahan 2B Residence No.15 RT.5 RW.10',
            'kelurahan_penanggung_jawab' => 'Loktabat Selatan',
            'kecamatan_penanggung_jawab' => 'Banjarbaru Selatan',
            'kabupaten_penanggung_jawab' => 'Kota Banjarbaru',
            'provinsi_penanggung_jawab' => 'Kalimantan Selatan',
            'hubungan_pasien' => 'Diri Sendiri',
            'hubungan_pasien_lainnya' => '',
            'tindakan_radiologi' => 'Foto Panoramik Digital dan Dental Periapikal Gigi 36 & 37',
            'nama_pasien_terhadap' => 'TSULAIKHA SAFRIANI',
            'jk_pasien_terhadap' => 'P',
            'tmp_lahir_pasien_terhadap' => 'Banjarmasin',
            'tgl_lahir_pasien_terhadap' => '1992-08-17',
            'usia_pasien_terhadap' => '33',
            'alamat_pasien_terhadap' => 'Jl. Tejosari Raya Perumahan 2B Residence No.15 RT.5 RW.10',
            'kelurahan_pasien_terhadap' => 'Loktabat Selatan',
            'kecamatan_pasien_terhadap' => 'Banjarbaru Selatan',
            'kabupaten_pasien_terhadap' => 'Kota Banjarbaru',
            'provinsi_pasien_terhadap' => 'Kalimantan Selatan',
            'kota_surat' => 'Banjarmasin',
            'tanggal_surat' => '2025-09-29',
            'jam_surat' => '10:15:00',
            'nama_yang_menyatakan' => 'TSULAIKHA SAFRIANI',
            'kd_dokter' => '199106142020122014',
            'nama_dokter' => 'drg. YUNIAR ELSA DWINURIA',
            'nama_saksi_1' => 'Rina Agustina, A.Md.Rad',
            'nama_saksi_2' => 'M. Rizky Ramadhan'
        ],
        [
            'no_rawat' => '2025/09/29/000051',
            'no_rkm_medis' => '055923',
            'nama_penanggung_jawab' => 'Hj. Siti Aminah',
            'jk_penanggung_jawab' => 'P',
            'tmp_lahir_penanggung_jawab' => 'Banjarmasin',
            'tgl_lahir_penanggung_jawab' => '1978-05-12',
            'usia_penanggung_jawab' => '47',
            'no_identitas_penanggung_jawab' => '6371025205780001',
            'jenis_identitas_penanggung_jawab' => 'KTP',
            'alamat_penanggung_jawab' => 'Jl Melati 22 Sungai Andai RT. 14 RW. 02',
            'kelurahan_penanggung_jawab' => 'Sungai Andai',
            'kecamatan_penanggung_jawab' => 'Banjarmasin Utara',
            'kabupaten_penanggung_jawab' => 'Kota Banjarmasin',
            'provinsi_penanggung_jawab' => 'Kalimantan Selatan',
            'hubungan_pasien' => 'Orang Tua',
            'hubungan_pasien_lainnya' => '',
            'tindakan_radiologi' => 'CBCT 3D (Cone Beam Computed Tomography) Maxillofacial dan Foto Sefalometri Lateral',
            'nama_pasien_terhadap' => 'HADIJAH',
            'jk_pasien_terhadap' => 'P',
            'tmp_lahir_pasien_terhadap' => 'Banjarmasin',
            'tgl_lahir_pasien_terhadap' => '2007-02-09',
            'usia_pasien_terhadap' => '18',
            'alamat_pasien_terhadap' => 'Jl Melati 22 Sungai Andai RT. 14 RW. 02',
            'kelurahan_pasien_terhadap' => 'Sungai Andai',
            'kecamatan_pasien_terhadap' => 'Banjarmasin Utara',
            'kabupaten_pasien_terhadap' => 'Kota Banjarmasin',
            'provinsi_pasien_terhadap' => 'Kalimantan Selatan',
            'kota_surat' => 'Banjarmasin',
            'tanggal_surat' => '2025-09-29',
            'jam_surat' => '11:45:00',
            'nama_yang_menyatakan' => 'Hj. Siti Aminah',
            'kd_dokter' => '198702272019032020',
            'nama_dokter' => 'Dr. drg. SHERLI DIANA, Sp.KG',
            'nama_saksi_1' => 'Ahmad Fauzi, A.Md.Kes',
            'nama_saksi_2' => 'Hj. Siti Aminah'
        ]
    ];

    $sql = "INSERT INTO `lampiran_persetujuan_tindakan_radiologi` (
        no_rawat, no_rkm_medis,
        nama_penanggung_jawab, jk_penanggung_jawab, tmp_lahir_penanggung_jawab, tgl_lahir_penanggung_jawab,
        usia_penanggung_jawab, no_identitas_penanggung_jawab, jenis_identitas_penanggung_jawab,
        alamat_penanggung_jawab, kelurahan_penanggung_jawab, kecamatan_penanggung_jawab,
        kabupaten_penanggung_jawab, provinsi_penanggung_jawab,
        hubungan_pasien, hubungan_pasien_lainnya,
        tindakan_radiologi,
        nama_pasien_terhadap, jk_pasien_terhadap, tmp_lahir_pasien_terhadap, tgl_lahir_pasien_terhadap,
        usia_pasien_terhadap, alamat_pasien_terhadap, kelurahan_pasien_terhadap, kecamatan_pasien_terhadap,
        kabupaten_pasien_terhadap, provinsi_pasien_terhadap,
        kota_surat, tanggal_surat, jam_surat,
        nama_yang_menyatakan, kd_dokter, nama_dokter, nama_saksi_1, nama_saksi_2,
        created_at, updated_at
    ) VALUES (
        :no_rawat, :no_rkm_medis,
        :nama_penanggung_jawab, :jk_penanggung_jawab, :tmp_lahir_penanggung_jawab, :tgl_lahir_penanggung_jawab,
        :usia_penanggung_jawab, :no_identitas_penanggung_jawab, :jenis_identitas_penanggung_jawab,
        :alamat_penanggung_jawab, :kelurahan_penanggung_jawab, :kecamatan_penanggung_jawab,
        :kabupaten_penanggung_jawab, :provinsi_penanggung_jawab,
        :hubungan_pasien, :hubungan_pasien_lainnya,
        :tindakan_radiologi,
        :nama_pasien_terhadap, :jk_pasien_terhadap, :tmp_lahir_pasien_terhadap, :tgl_lahir_pasien_terhadap,
        :usia_pasien_terhadap, :alamat_pasien_terhadap, :kelurahan_pasien_terhadap, :kecamatan_pasien_terhadap,
        :kabupaten_pasien_terhadap, :provinsi_pasien_terhadap,
        :kota_surat, :tanggal_surat, :jam_surat,
        :nama_yang_menyatakan, :kd_dokter, :nama_dokter, :nama_saksi_1, :nama_saksi_2,
        NOW(), NOW()
    )";

    $stmt = $pdo->prepare($sql);
    foreach ($samples as $s) {
        $stmt->execute($s);
        echo "Inserted sampel persetujuan tindakan radiologi untuk no_rawat: {$s['no_rawat']}\n";
    }

    echo "Selesai seeding data!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

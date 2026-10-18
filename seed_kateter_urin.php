<?php
/**
 * Seeder data sampel untuk lampiran_monitoring_pasien_kateter_urin
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
    $pdo->exec("DELETE FROM lampiran_monitoring_pasien_kateter_urin");

    // Helper membuat grid 31 hari
    function makeGrid($startDay, $endDay, $isGejala = false) {
        $keysPencegahan = [
            'pasang', 'lepas', 'cuci_tangan', 'teknik_aseptik', 'fiksasi',
            'urin_bag_bwh_bladder', 'posisi_kateter', 'urin_bag_lantai',
            'tidak_bladder_training', 'tidak_buka_sambungan', 'perineal_hygiene',
            'gelas_ukur_terpisah', 'ada_indikasi'
        ];

        $keysGejala = [
            'gejala_demam', 'gejala_nyeri_suprapubic', 'gejala_urgency',
            'gejala_frequency', 'gejala_dysuria', 'gejala_nyeri_costovertebral',
            'gejala_kuman_biakan', 'gejala_pyuria'
        ];

        $grid = [];

        foreach ($keysPencegahan as $k) {
            $row = [];
            $cnt = 0;
            for ($d = 1; $d <= 31; $d++) {
                if ($d >= $startDay && $d <= $endDay) {
                    if ($k == 'pasang') {
                        $val = ($d == $startDay) ? '✓' : '';
                    } elseif ($k == 'lepas') {
                        $val = ($d == $endDay) ? '✓' : '';
                    } else {
                        $val = '✓';
                    }
                    if ($val === '✓') $cnt++;
                } else {
                    $val = '';
                }
                $row['d' . $d] = $val;
            }
            $row['total'] = $cnt > 0 ? (string)$cnt : '';
            $row['ketr'] = ($k == 'pasang') ? "Pasang Tgl {$startDay}" : (($k == 'lepas') ? "Lepas Tgl {$endDay}" : "SOP Terpenuhi");
            $grid[$k] = $row;
        }

        foreach ($keysGejala as $k) {
            $row = [];
            $cnt = 0;
            for ($d = 1; $d <= 31; $d++) {
                if ($d >= $startDay && $d <= $endDay) {
                    $val = '0'; // normal / tidak ada gejala
                } else {
                    $val = '';
                }
                $row['d' . $d] = $val;
            }
            $row['total'] = '0';
            $row['ketr'] = 'Tidak ditemukan gejala ISK';
            $grid[$k] = $row;
        }

        return json_encode($grid);
    }

    $samples = [
        [
            'no_rawat' => '2025/09/29/000006',
            'no_rkm_medis' => '039836',
            'ruang' => 'Ruang Melati / Bed 02',
            'bulan_tahun' => 'Oktober 2026',
            'tgl_mrs' => '2026-10-01',
            'diagnosa' => 'Post Bedah Tumor Odontogenik Mandibula + Retensio Urin',
            'jenis_cath' => 'Folley',
            'jenis_cath_lainnya' => '',
            'no_cath' => '16',
            'no_cath_lainnya' => '',
            'detail_monitoring' => makeGrid(1, 5),
            'nama_icn' => 'Ns. Siti Rahmah, S.Kep',
            'nip_icn' => '198804152012012003',
            'nama_head_nurse' => 'Ns. Hendra Kurniawan, S.Kep',
            'nip_head_nurse' => '198205102008011005',
            'catatan' => 'Kateter terpasang selama 5 hari pasca operasi bedah mulut mayor. Bundel pencegahan ISK terlaksana 100%, kateter berhasil dilepas pada hari ke-5 tanpa komplikasi ISK.'
        ],
        [
            'no_rawat' => '2025/09/29/000002',
            'no_rkm_medis' => '052168',
            'ruang' => 'Ruang Mawar / Bed 04',
            'bulan_tahun' => 'Oktober 2026',
            'tgl_mrs' => '2026-10-05',
            'diagnosa' => 'Fraktur Mandibula Dextra Simpleks / Monitoring Urin Output',
            'jenis_cath' => 'Silikon',
            'jenis_cath_lainnya' => '',
            'no_cath' => '14',
            'no_cath_lainnya' => '',
            'detail_monitoring' => makeGrid(5, 8),
            'nama_icn' => 'Ns. Dewi Sartika, S.Kep',
            'nip_icn' => '199102202015022004',
            'nama_head_nurse' => 'Ns. Ahmad Ridwan, S.Kep',
            'nip_head_nurse' => '198411032009021002',
            'catatan' => 'Pemasangan kateter urin silikon no. 14 dengan teknik aseptik ketat. Pemantauan cairan urine lancar, warna kuning jernih, tanda ISK nihil.'
        ],
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'ruang' => 'Ruang Anggrek / Bed 01',
            'bulan_tahun' => 'Agustus 2026',
            'tgl_mrs' => '2026-08-13',
            'diagnosa' => 'Rekonstruksi Maksilofasial / Post General Anesthesia',
            'jenis_cath' => 'Folley',
            'jenis_cath_lainnya' => '',
            'no_cath' => '18',
            'no_cath_lainnya' => '',
            'detail_monitoring' => makeGrid(13, 16),
            'nama_icn' => 'Ns. Rina Agustina, S.Kep',
            'nip_icn' => '198908122014032001',
            'nama_head_nurse' => 'Ns. Hendra Kurniawan, S.Kep',
            'nip_head_nurse' => '198205102008011005',
            'catatan' => 'Pemasangan kateter urin folley no. 18 selama 4 hari rawat inap. Pasien kooperatif, kebersihan perineal terjaga, kateter dilepas tgl 16 Agustus 2026 dalam kondisi baik.'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO lampiran_monitoring_pasien_kateter_urin 
        (no_rawat, no_rkm_medis, ruang, bulan_tahun, tgl_mrs, diagnosa, jenis_cath, jenis_cath_lainnya, no_cath, no_cath_lainnya, detail_monitoring, nama_icn, nip_icn, nama_head_nurse, nip_head_nurse, catatan, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($samples as $s) {
        $stmt->execute([
            $s['no_rawat'],
            $s['no_rkm_medis'],
            $s['ruang'],
            $s['bulan_tahun'],
            $s['tgl_mrs'],
            $s['diagnosa'],
            $s['jenis_cath'],
            $s['jenis_cath_lainnya'],
            $s['no_cath'],
            $s['no_cath_lainnya'],
            $s['detail_monitoring'],
            $s['nama_icn'],
            $s['nip_icn'],
            $s['nama_head_nurse'],
            $s['nip_head_nurse'],
            $s['catatan']
        ]);
        echo "Berhasil insert sampel: {$s['no_rawat']} ({$s['ruang']})\n";
    }

    echo "Selesai seeding data sampel kateter urin!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

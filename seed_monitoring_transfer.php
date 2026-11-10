<?php
/**
 * Seeder data sampel untuk lampiran_monitoring_pasien_selama_transfer
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
    $pdo->exec("DELETE FROM lampiran_monitoring_pasien_selama_transfer");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'kamar_inap' => 'Ruang Diamond / Bed 03',
            'tanggal_transfer' => '2025-10-14',
            'status_kegawatdaruratan' => 'Kuning',
            'jenis_kasus' => 'Trauma',
            'diagnosa' => 'Fraktur Dentoalveolar Regio Anterior Maksila + Ruptur Mukosa Labial ec KLL',
            'monitoring_keterangan' => 'Transfer pasien dari Ruang Rawat Inap (IRNA) ke Kamar Operasi IBS untuk tindakan debridement dan reduksi fiksasi luka traumatik maksilofasial.',
            'petugas_transfer' => 'Muhammad Raihan, A.Md.Kes',
            'nip_petugas_transfer' => '0000',
            'jam_berangkat' => '08:30:00',
            'jam_tiba' => '08:45:00',
            'detail_monitoring' => json_encode([
                [
                    'jam_obs' => '08:30',
                    'gcs_pupil_rx' => 'E4V5M6 / 3mm (+/+)',
                    'tensi' => '120/80',
                    'rr' => '18',
                    'spo2' => '99',
                    'temp' => '36.6',
                    'jam_tind' => '08:30',
                    'hal_penting_tindakan' => 'Pasien dipindahkan ke brankar, fiksasi strap terpasang aman',
                    'jam_terapi' => '08:30',
                    'terapi_obat_cairan' => 'IVFD RL 20 tpm, O2 nasal kanul 3 lpm'
                ],
                [
                    'jam_obs' => '08:38',
                    'gcs_pupil_rx' => 'E4V5M6 / 3mm (+/+)',
                    'tensi' => '118/78',
                    'rr' => '18',
                    'spo2' => '99',
                    'temp' => '36.5',
                    'jam_tind' => '08:35',
                    'hal_penting_tindakan' => 'Observasi patensi jalan nafas & perdarahan intraoral selama perjalanan koridor lt.2',
                    'jam_terapi' => '-',
                    'terapi_obat_cairan' => '-'
                ],
                [
                    'jam_obs' => '08:45',
                    'gcs_pupil_rx' => 'E4V5M6 / 3mm (+/+)',
                    'tensi' => '122/80',
                    'rr' => '19',
                    'spo2' => '98',
                    'temp' => '36.7',
                    'jam_tind' => '08:45',
                    'hal_penting_tindakan' => 'Serah terima pasien dengan perawat IBS (Ruang Pre-Op), tanda vital stabil',
                    'jam_terapi' => '08:45',
                    'terapi_obat_cairan' => 'Injeksi Cefazolin 1 gr IV profilaksis pre-op'
                ]
            ]),
            'catatan' => 'Transfer berjalan lancar tanpa kendala. Status respirasi dan hemodinamik stabil selama pemindahan.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'no_rawat' => '2024/12/02/000001',
            'no_rkm_medis' => '000001',
            'kamar_inap' => 'Ruang Melati / Bed 01',
            'tanggal_transfer' => '2025-10-15',
            'status_kegawatdaruratan' => 'Merah',
            'jenis_kasus' => 'Non Trauma',
            'diagnosa' => 'Angina Ludwig + Obstruksi Parsial Jalan Nafas ec Abses Submandibula & Sublingual Dextra',
            'monitoring_keterangan' => 'Transfer cito dari IGD ke IBS/ICU untuk intubasi endotrakeal fiberoptik dan dekompresi darurat jalan nafas.',
            'petugas_transfer' => 'MUHAMMAD ZAIDI, S.ST',
            'nip_petugas_transfer' => '170',
            'jam_berangkat' => '10:15:00',
            'jam_tiba' => '10:25:00',
            'detail_monitoring' => json_encode([
                [
                    'jam_obs' => '10:15',
                    'gcs_pupil_rx' => 'E3V4M6 / 3mm (+/+)',
                    'tensi' => '135/85',
                    'rr' => '26',
                    'spo2' => '94',
                    'temp' => '38.4',
                    'jam_tind' => '10:15',
                    'hal_penting_tindakan' => 'Posisi semi-fowler, standby suction mobile & emergency bag valve mask',
                    'jam_terapi' => '10:15',
                    'terapi_obat_cairan' => 'O2 NRM 10 lpm, IVFD Asering 20 tpm'
                ],
                [
                    'jam_obs' => '10:20',
                    'gcs_pupil_rx' => 'E3V4M6 / 3mm (+/+)',
                    'tensi' => '138/88',
                    'rr' => '24',
                    'spo2' => '96',
                    'temp' => '38.3',
                    'jam_tind' => '10:18',
                    'hal_penting_tindakan' => 'Suction berkala lendir rongga mulut, stridor inspiratoir berkurang',
                    'jam_terapi' => '10:20',
                    'terapi_obat_cairan' => 'Dexamethasone 10 mg IV bolus perlahan'
                ],
                [
                    'jam_obs' => '10:25',
                    'gcs_pupil_rx' => 'E3V4M6 / 3mm (+/+)',
                    'tensi' => '130/82',
                    'rr' => '22',
                    'spo2' => '97',
                    'temp' => '38.2',
                    'jam_tind' => '10:25',
                    'hal_penting_tindakan' => 'Tiba di ruang operasi, hand-over darurat langsung ke dokter spesialis anestesi jaga',
                    'jam_terapi' => '-',
                    'terapi_obat_cairan' => 'Terapi lanjut sesuai protokol penanganan airway darurat IBS'
                ]
            ]),
            'catatan' => 'Transfer cito prioritas merah. Airway terjaga dengan suctioning intermiten dan terapi oksigen konsentrasi tinggi.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'no_rawat' => '2024/12/02/000003',
            'no_rkm_medis' => '000002',
            'kamar_inap' => 'Ruang Mawar / Bed 04',
            'tanggal_transfer' => '2025-10-16',
            'status_kegawatdaruratan' => 'Hijau',
            'jenis_kasus' => 'Non Trauma',
            'diagnosa' => 'Post Odontektomi M3 Impaksi Mandibula Bilateral Hari Ke-2 (Kondisi Klinis Membaik)',
            'monitoring_keterangan' => 'Transfer antar ruangan dari Ruang Rawat Inap Kelas 3 ke Ruang Rawat Inap VIP / Paviliun atas permintaan keluarga.',
            'petugas_transfer' => 'ARDA WIJAYA, A.Md.Tem',
            'nip_petugas_transfer' => '166',
            'jam_berangkat' => '14:00:00',
            'jam_tiba' => '14:15:00',
            'detail_monitoring' => json_encode([
                [
                    'jam_obs' => '14:00',
                    'gcs_pupil_rx' => 'E4V5M6 / 3mm (+/+)',
                    'tensi' => '115/75',
                    'rr' => '18',
                    'spo2' => '99',
                    'temp' => '36.4',
                    'jam_tind' => '14:00',
                    'hal_penting_tindakan' => 'Pasien ditransfer dengan kursi roda, berkas RM dan obat oral dibawa lengkap',
                    'jam_terapi' => '14:00',
                    'terapi_obat_cairan' => 'Asam Mefenamat 500 mg tab PO post makan siang'
                ],
                [
                    'jam_obs' => '14:15',
                    'gcs_pupil_rx' => 'E4V5M6 / 3mm (+/+)',
                    'tensi' => '118/76',
                    'rr' => '18',
                    'spo2' => '99',
                    'temp' => '36.5',
                    'jam_tind' => '14:15',
                    'hal_penting_tindakan' => 'Pasien menempati bed di Paviliun VIP 102, serah terima lancar dengan perawat ruangan',
                    'jam_terapi' => '-',
                    'terapi_obat_cairan' => '-'
                ]
            ]),
            'catatan' => 'Transfer elektif stabil tanpa keluhan. Nyeri post-op minimal VAS 2/10.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO lampiran_monitoring_pasien_selama_transfer (
            no_rawat, no_rkm_medis, kamar_inap, tanggal_transfer,
            status_kegawatdaruratan, jenis_kasus, diagnosa, monitoring_keterangan,
            petugas_transfer, nip_petugas_transfer, jam_berangkat, jam_tiba,
            detail_monitoring, catatan, created_at, updated_at
        ) VALUES (
            :no_rawat, :no_rkm_medis, :kamar_inap, :tanggal_transfer,
            :status_kegawatdaruratan, :jenis_kasus, :diagnosa, :monitoring_keterangan,
            :petugas_transfer, :nip_petugas_transfer, :jam_berangkat, :jam_tiba,
            :detail_monitoring, :catatan, :created_at, :updated_at
        )
    ");

    foreach ($samples as $sample) {
        $stmt->execute($sample);
    }

    echo "Berhasil mengisi " . count($samples) . " sampel data monitoring pasien selama transfer!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

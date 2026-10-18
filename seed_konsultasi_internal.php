<?php
/**
 * Seeder data sampel untuk lampiran_konsultasi_internal
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
    $pdo->exec("DELETE FROM lampiran_konsultasi_internal");

    $samples = [
        [
            'no_rawat' => '2025/08/13/000040',
            'no_rkm_medis' => '055268',
            'kamar_inap' => 'Ruang Diamond / Bed 03',
            'ts_bagian' => 'Spesialis Anestesiologi & Terapi Intensif (Sp.An)',
            'instalasi' => 'Instalasi Bedah Sentral (IBS)',
            'diagnosa_sementara' => 'Impaksi Gigi Molar Tiga Mandibula Bilateral Klas II Posisi B & Kista Dentigerous',
            'konsultasi_diminta' => "Mohon evaluasi toleransi pembiusan umum (GA) pre-operatif untuk rencana odontektomi dan enukleasi kista mandibula pada hari Rabu. Pasien memiliki riwayat asma intermiten sejak anak-anak.",
            'tgl_konsul' => '2025-10-12',
            'kd_dokter_pengirim' => '196311041993011002',
            'nama_dokter_pengirim' => 'drg. R. HARRY DHARMAWAN S, M.Kes',
            'jawaban_hasil_pemeriksaan' => "Keadaan umum baik, kesadaran compos mentis. Hemodinamik stabil TD 115/75 mmHg, Nadi 82x/m, SpO2 99% room air. Paru: vesikuler normal, wheezing (-), ronki (-). Laboratorium: Hb 13.8 g/dL, Leukosit 7.200/uL, Trombosit 245.000/uL, BT/CT normal, GDS 98 mg/dL. Foto Thorax: Cor dan pulmo dalam batas normal.",
            'jawaban_diagnosa' => 'Status Fisik ASA II (Riwayat Asma Bronkiale Terkontrol Tanpa Serangan Akut)',
            'jawaban_nasihat' => "ACC Operasi dengan Pembiusan Umum (General Anesthesia).\nAnjuran:\n1) Puasa 6 jam pre-op (boleh minum air putih bening sampai 2 jam pre-op).\n2) Inhaler Salbutamol dibawa ke ruang operasi.\n3) Premedikasi Deksametason 5 mg IV 30 menit sebelum induksi.\n4) Pasang IV line kateter no. 18G dan sedia emergency airway equipment.",
            'tgl_jawaban' => '2025-10-13',
            'kd_dokter_konsulen' => '195707081982031014',
            'nama_dokter_konsulen' => 'dr. Hendra Setiawan, Sp.An, KIC',
            'status_konsul' => 'Sudah Dijawab',
            'catatan' => 'Toleransi operasi baik, rencana masuk kamar operasi IBS pk 08.30 WITA.'
        ],
        [
            'no_rawat' => '2024/12/02/000001',
            'no_rkm_medis' => '000001',
            'kamar_inap' => 'Ruang Melati / Bed 01',
            'ts_bagian' => 'Spesialis Penyakit Dalam (Sp.PD)',
            'instalasi' => 'Instalasi Rawat Inap (IRNA)',
            'diagnosa_sementara' => 'Abses Submandibula Sinistra ec Gangren Radiks Gigi 36 dengan Hiperglikemia Reaktif',
            'konsultasi_diminta' => "Mohon evaluasi regulasi gula darah dan tatalaksana komorbiditas metabolik sebelum dilakukan tindakan insisi drainase abses submandibula dan ekstraksi fokus infeksi gigi 36.",
            'tgl_konsul' => '2025-10-14',
            'kd_dokter_pengirim' => '196909202000031005',
            'nama_dokter_pengirim' => 'drg. TEGUH HADIANTO, Sp.KG',
            'jawaban_hasil_pemeriksaan' => "Kondisi klinis febris (Suhu 38.2 °C), edema submandibula sinistra (+), nyeri tekan (+), trismus 2 jari. GDS awal 265 mg/dL, HbA1c 8.1%. Fungsi ginjal: Ureum 28 mg/dL, Creatinin 0.9 mg/dL. Elektrolit dalam batas normal.",
            'jawaban_diagnosa' => 'Diabetes Melitus Tipe 2 Tidak Terkontrol + Sepsis Odontogenik Ringan',
            'jawaban_nasihat' => "1) Regulasi cepat GDS dengan Insulin sliding scale (Novorapid) target GDS < 200 mg/dL sebelum tindakan bedah.\n2) Hidrasi cairan kristaloid NaCl 0.9% 20 tpm.\n3) Teruskan Ceftriaxone 2x1 gr IV dan Metronidazole 3x500 mg drip infus.\n4) Insisi drainase abses dapat segera dilaksanakan cito jika airway terancam dengan monitoring ketat hemodinamik.",
            'tgl_jawaban' => '2025-10-14',
            'kd_dokter_konsulen' => '197103182000031003',
            'nama_dokter_konsulen' => 'dr. Ahmad Fauzi, Sp.PD, FINASIM',
            'status_konsul' => 'Sudah Dijawab',
            'catatan' => 'Tindakan insisi drainase dijadwalkan hari ini setelah GDS stabil di bawah 200 mg/dL.'
        ],
        [
            'no_rawat' => '2024/12/02/000003',
            'no_rkm_medis' => '000002',
            'kamar_inap' => 'Ruang Mawar / Bed 04',
            'ts_bagian' => 'Spesialis Bedah Mulut & Maksilofasial (Sp.BMM)',
            'instalasi' => 'Instalasi Bedah Sentral (IBS)',
            'diagnosa_sementara' => 'Fraktur Parasimfisis Mandibula Dextra ec Trauma Kecelakaan Lalu Lintas',
            'konsultasi_diminta' => "Mohon evaluasi dan alih rawat bersama untuk tindakan ORIF (Open Reduction Internal Fixation) rekonstruksi mandibula serta reposisi dan fiksasi oklusi intermaksiler.",
            'tgl_konsul' => '2025-10-17',
            'kd_dokter_pengirim' => '19681130201701211001',
            'nama_dokter_pengirim' => 'drg. DIANA WIBOWO, Sp.Ort',
            'jawaban_hasil_pemeriksaan' => '',
            'jawaban_diagnosa' => '',
            'jawaban_nasihat' => '',
            'tgl_jawaban' => null,
            'kd_dokter_konsulen' => null,
            'nama_dokter_konsulen' => '',
            'status_konsul' => 'Menunggu Jawaban',
            'catatan' => 'Menunggu visitasi dan evaluasi dari DPJP Bedah Mulut.'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO lampiran_konsultasi_internal (
            no_rawat, no_rkm_medis, kamar_inap, ts_bagian, instalasi,
            diagnosa_sementara, konsultasi_diminta, tgl_konsul,
            kd_dokter_pengirim, nama_dokter_pengirim,
            jawaban_hasil_pemeriksaan, jawaban_diagnosa, jawaban_nasihat,
            tgl_jawaban, kd_dokter_konsulen, nama_dokter_konsulen,
            status_konsul, catatan, created_at, updated_at
        ) VALUES (
            :no_rawat, :no_rkm_medis, :kamar_inap, :ts_bagian, :instalasi,
            :diagnosa_sementara, :konsultasi_diminta, :tgl_konsul,
            :kd_dokter_pengirim, :nama_dokter_pengirim,
            :jawaban_hasil_pemeriksaan, :jawaban_diagnosa, :jawaban_nasihat,
            :tgl_jawaban, :kd_dokter_konsulen, :nama_dokter_konsulen,
            :status_konsul, :catatan, NOW(), NOW()
        )
    ");

    $count = 0;
    foreach ($samples as $s) {
        $stmt->execute([
            ':no_rawat' => $s['no_rawat'],
            ':no_rkm_medis' => $s['no_rkm_medis'],
            ':kamar_inap' => $s['kamar_inap'],
            ':ts_bagian' => $s['ts_bagian'],
            ':instalasi' => $s['instalasi'],
            ':diagnosa_sementara' => $s['diagnosa_sementara'],
            ':konsultasi_diminta' => $s['konsultasi_diminta'],
            ':tgl_konsul' => $s['tgl_konsul'],
            ':kd_dokter_pengirim' => $s['kd_dokter_pengirim'],
            ':nama_dokter_pengirim' => $s['nama_dokter_pengirim'],
            ':jawaban_hasil_pemeriksaan' => $s['jawaban_hasil_pemeriksaan'],
            ':jawaban_diagnosa' => $s['jawaban_diagnosa'],
            ':jawaban_nasihat' => $s['jawaban_nasihat'],
            ':tgl_jawaban' => $s['tgl_jawaban'],
            ':kd_dokter_konsulen' => $s['kd_dokter_konsulen'],
            ':nama_dokter_konsulen' => $s['nama_dokter_konsulen'],
            ':status_konsul' => $s['status_konsul'],
            ':catatan' => $s['catatan']
        ]);
        $count++;
    }

    echo "SUCCESS: Berhasil menginput {$count} data sampel formulir konsultasi internal!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

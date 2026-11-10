<?php
define('INDEX', true);
define('BASE_DIR', __DIR__ . '/..');
define('ADMIN', 'admin');

require_once(BASE_DIR . '/config.php');
require_once(BASE_DIR . '/systems/lib/Autoloader.php');

$core = new \Systems\Site();

$forms = [
    // 24 Lampiran
    "lampiran_asesmen_ulang_status_fungsional",
    "lampiran_cairan_infus",
    "lampiran_form_a_mpp",
    "lampiran_form_b_mpp",
    "lampiran_intervensi_pengkajian_nyeri",
    "lampiran_kebutuhan_privasi_pasien",
    "lampiran_konsultasi_internal",
    "lampiran_meninggalkan_rs_tertentu",
    "lampiran_monitoring_pasien_kateter_urin",
    "lampiran_monitoring_pasien_selama_transfer",
    "lampiran_penghentian_pengobatan",
    "lampiran_penilaian_dekubitus",
    "lampiran_penilaian_kejadian_ilo",
    "lampiran_penilaian_kejadian_isk",
    "lampiran_penilaian_kejadian_phlebitis",
    "lampiran_penundaan_kelambatan_pelayanan",
    "lampiran_permintaan_pelayanan_kerohanian",
    "lampiran_permintaan_second_opinion",
    "lampiran_persetujuan_testing_hiv",
    "lampiran_prmrj",
    "lampiran_skrining_luar_rs",
    "lampiran_stabilisasi_pasien",
    "lampiran_surat_pernyataan_bersedia_menunggu",
    "lampiran_vct_pra_testing_hiv",

    // 6 Anestesi
    "ans1", "ans2", "ans3", "ans4", "ans6", "ans7",

    // 7 Bedah
    "bm1", "bm2", "bm4", "bmi1", "bmi4", "bmi5", "pulangaps",

    // 3 IGD / Terintegrasi
    "akgmu", "edukasipasienter", "asesmen_medis_umum_irna",

    // 13 Ranap
    "ri01b", "ri02", "ri03", "ri04", "ri09", "ri11b", "ri14", "ri15", "ri15a", "ri15b", "ri18", "ri19", "ri20",

    // 4 Rajal
    "rj02a", "rj07", "rj10", "rj11"
];

$tpl = new \Systems\Lib\Templates(BASE_DIR . '/plugins/update_bmt/view/admin/');

echo "=== TESTING ALL 57 FORMS TEMPLATE COMPILATION ===\n\n";

$dummyData = [
    'id' => 1,
    'no_rawat' => '2026/10/18/000001',
    'no_rkm_medis' => '000001',
    'nm_pasien' => 'PASIEN TEST OTOMATIS',
    'nama_pasien' => 'PASIEN TEST OTOMATIS',
    'jk' => 'L',
    'tgl_lahir' => '1990-01-01',
    'umur' => '36 Th',
    'kop_img' => BASE_DIR . '/assets/img/KOP-SURAT.png',
    'is_mpdf' => false,
    'rm0' => '0', 'rm1' => '0', 'rm2' => '0', 'rm3' => '0', 'rm4' => '0', 'rm5' => '1',
    'tanggal' => '2026-10-18',
    'tanggal_jam' => '2026-10-18 10:00:00'
];

$passCount = 0;
$failCount = 0;

foreach ($forms as $f) {
    // determine filenames
    $prefix = in_array($f, ['ri15a', 'ri15b', 'ri18', 'ri19', 'ri20', 'rj02a', 'rj07', 'rj10', 'rj11']) ? ($f . '_') : '';
    $manageFile = "{$f}/{$prefix}manage.html";
    $cetakFile = "{$f}/{$prefix}cetak.html";

    // Test manage
    $mngOk = false;
    try {
        $mngOut = $tpl->draw($manageFile, [
            'data' => [$dummyData],
            'list' => [$dummyData],
            'asesmen' => [$dummyData],
            'edukasi' => [$dummyData]
        ]);
        $mngOk = strlen($mngOut) > 0;
    } catch (\Throwable $e) {
        $mngErr = $e->getMessage();
    }

    // Test cetak
    $ctkOk = false;
    try {
        $ctkOut = $tpl->draw($cetakFile, [
            'data' => $dummyData,
            'p' => $dummyData,
            'd' => $dummyData,
            'detail' => [],
            'rows' => [],
            'katRows' => [],
            'p1' => [],
            'p2' => [],
            'kop_img' => $dummyData['kop_img'],
            'is_mpdf' => false
        ]);
        $ctkOk = strlen($ctkOut) > 0;
    } catch (\Throwable $e) {
        $ctkErr = $e->getMessage();
    }

    if ($mngOk && $ctkOk) {
        $passCount++;
        echo sprintf("[PASS] %-38s => Manage OK (%d B), Cetak OK (%d B)\n", $f, strlen($mngOut), strlen($ctkOut));
    } else {
        $failCount++;
        echo sprintf("[FAIL] %-38s => Manage: %s, Cetak: %s\n", $f, $mngOk ? 'OK' : ($mngErr ?? 'ERR'), $ctkOk ? 'OK' : ($ctkErr ?? 'ERR'));
    }
}

echo "\nSummary: {$passCount} Passed, {$failCount} Failed out of " . count($forms) . " forms.\n";

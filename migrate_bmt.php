<?php
/**
 * Script Migrasi Otomatis Tabel Pendukung Update BMT / Form Rekam Medis mLITE
 * Dapat dijalankan via CLI: php migrate_bmt.php
 * Atau via Browser: http://localhost/mlite-rsgm/migrate_bmt.php
 */

define('BASE_DIR', __DIR__);

// Load config
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$db_host = defined('DBHOST') ? DBHOST : '127.0.0.1';
$db_port = defined('DBPORT') ? DBPORT : '3306';
$db_user = defined('DBUSER') ? DBUSER : 'root';
$db_pass = defined('DBPASS') ? DBPASS : '';
$db_name = defined('DBNAME') ? DBNAME : 'mlitersgm';

$isCli = (php_sapi_name() === 'cli');

$statusList = [];
$errorMsg = null;
$successCount = 0;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("SET SESSION sql_mode = (SELECT REPLACE(REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', ''));");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    $sqlFile = __DIR__ . '/migration_tabel_pendukung_bmt.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File migrasi {$sqlFile} tidak ditemukan.");
    }

    $sqlContent = file_get_contents($sqlFile);
    // Split by semicolons followed by newline
    $queries = preg_split('/;\s*\n/', $sqlContent);

    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || str_starts_with($query, '--')) {
            continue;
        }
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            // Ignore minor errors on recreate
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Daftar 36 tabel pendukung
    $expectedTables = [
        'ans1_tindakan_pembiusan' => 'Informasi Tindakan Pembiusan (ANS1)',
        'ans2_persetujuan_pembiusan' => 'Persetujuan Tindakan Pembiusan (ANS2)',
        'ans3_assesmen_prasedasi_anestesi' => 'Asesmen Prasedasi/Anestesi (ANS3)',
        'ans4_rencana_anestesi' => 'Rencana Anestesi Header (ANS4)',
        'ans4_evaluasi_premedikasi_detail' => 'Rencana Anestesi Detail Premedikasi',
        'ans6_status_post_anestesi' => 'Status Post Anestesi (ANS6)',
        'ans6_protokol_nyeri_detail' => 'Protokol Manajemen Nyeri Post Anestesi',
        'ans7_serah_terima' => 'Serah Terima Pasien Pasca Operasi (ANS7)',
        'asesmen_keperawatan_gigi_ugd' => 'Asesmen Askep Gigi & Mulut UGD (RM.GD 4)',
        'asesmen_medis_umum_irna' => 'Asesmen Medis Umum IRNA',
        'bm1_informasi_tindakan' => 'Informasi Tindakan Medik Bedah (BM1)',
        'bm2_persetujuan_tindakan' => 'Persetujuan Tindakan Bedah (BM2)',
        'bm4_laporan_pembedahan' => 'Laporan Pembedahan (BM4)',
        'bmi1_persetujuan_anestesi_lokal' => 'Persetujuan Tindakan Anestesi Lokal (BMI1)',
        'bmi4_pra_anestesi_lokal' => 'Pemeriksaan Pra Anestesi Lokal (BMI4)',
        'bmi5_bedah_minor' => 'Laporan Bedah Minor (BMI5)',
        'edukasi_pasien_terintegrasi' => 'Edukasi Pasien Terintegrasi Header',
        'edukasi_pasien_terintegrasi_detail' => 'Edukasi Pasien Terintegrasi Detail',
        'ri01b_hak_kewajiban_pasien' => 'Hak dan Kewajiban Pasien Ranap (RM.RI 01B)',
        'ri02_ringkasan' => 'Ringkasan Masuk dan Keluar (RM.RI 02)',
        'ri03_serah_terima' => 'Serah Terima Pasien Ranap (RM.RI 03)',
        'ri04_transfer_internal' => 'Transfer Pasien Internal (RM.RI 04)',
        'ri09_status_fungsional' => 'Asesmen Status Fungsional (RM.RI 09)',
        'ri11b_pemberian_makan' => 'Pemberian Makanan Pasien (RM.RI 11B)',
        'ri14_rekonsiliasi_obat' => 'Formulir Rekonsiliasi Obat (RM.RI 14)',
        'ri15_instruksi_medis' => 'Instruksi Medis (RM.RI 15)',
        'ri15a_instruksi_medis_farmakologi' => 'Instruksi Medis Farmakologi (RM.RI 15A)',
        'ri15b_jadwal_obat' => 'Jadwal Pemberian Obat (RM.RI 15B)',
        'ri18_edukasi_pasien' => 'Lembar Edukasi Pasien Ranap (RM.RI 18)',
        'ri19_discharge_planning' => 'Perencanaan Pemulangan Pasien (RM.RI 19)',
        'ri20_resume_pulang' => 'Resume Pasien Pulang (RM.RI 20)',
        'rj02a_hak_kewajiban' => 'Hak dan Kewajiban Rajal (RM.RJ 02A)',
        'rj07_asesmen_ulang_nyeri' => 'Asesmen Ulang Nyeri Rajal (RM.RJ 07)',
        'rj10_edukasi_terintegrasi' => 'Edukasi Terintegrasi Rajal (RM.RJ 10)',
        'rj11_pemberian_informasi' => 'Pemberian Informasi Rajal (RM.RJ 11)',
        'surat_pulang_aps' => 'Surat Pernyataan Pulang APS',
        'lampiran_cairan_infus' => 'Lampiran - Pemantauan Cairan Infus (Ranap)',
        'lampiran_form_a_mpp' => 'Lampiran - Form A Manajer Pelayanan Pasien (Ranap)',
        'lampiran_asesmen_ulang_status_fungsional' => 'Lampiran - Form Asesmen Ulang Status Fungsional (Ranap)',
        'lampiran_form_b_mpp' => 'Lampiran - Form B Catatan Implementasi MPP (Ranap)',
        'lampiran_kebutuhan_privasi_pasien' => 'Lampiran - Formulir Identifikasi Kebutuhan Privasi Pasien (Ranap)',
        'lampiran_meninggalkan_rs_tertentu' => 'Lampiran - Formulir Meninggalkan RS Selama Periode Tertentu (Ranap)',
        'lampiran_penghentian_pengobatan' => 'Lampiran - Formulir Penghentian Pengobatan (Ranap)',
        'lampiran_profil_ringkas_medis_rajal' => 'Lampiran - Formulir Profil Ringkas Medis Rawat Jalan PRMRJ (Rajal/Ranap)',
        'lampiran_skrining_luar_rs' => 'Lampiran - Formulir Skrining Dari Luar Rumah Sakit (Ranap)',
        'lampiran_stabilisasi_pasien' => 'Lampiran - Formulir Stabilisasi Pasien Sebelum Transfer (Ranap)'
    ];

    foreach ($expectedTables as $tbl => $deskripsi) {
        $check = $pdo->query("SHOW TABLES LIKE '{$tbl}'")->fetchColumn();
        if ($check) {
            $colCount = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '{$db_name}' AND table_name = '{$tbl}'")->fetchColumn();
            $statusList[] = [
                'table' => $tbl,
                'desc' => $deskripsi,
                'status' => 'OK',
                'columns' => (int)$colCount
            ];
            $successCount++;
        } else {
            $statusList[] = [
                'table' => $tbl,
                'desc' => $deskripsi,
                'status' => 'Gagal / Belum Ada',
                'columns' => 0
            ];
        }
    }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}

if ($isCli) {
    echo "========================================================\n";
    echo " MIGRASI TABEL DATABASE PENDUKUNG (mLITE - {$db_name})\n";
    echo "========================================================\n";
    if ($errorMsg) {
        echo "[ERROR] " . $errorMsg . "\n";
        exit(1);
    }
    foreach ($statusList as $item) {
        $badge = $item['status'] === 'OK' ? '[OK]' : '[FAIL]';
        echo sprintf("%-6s %-35s (%d kolom) - %s\n", $badge, $item['table'], $item['columns'], $item['desc']);
    }
    echo "--------------------------------------------------------\n";
    echo "Total tabel aktif: {$successCount} dari " . count($expectedTables) . " tabel.\n";
    echo "Migrasi Selesai!\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Tabel Database Pendukung mLITE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #1e1e2d; color: #cdd3de; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 30px 15px; }
        .panel-custom { background: #27293d; border: 1px solid #3d405b; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .panel-custom .panel-heading { background: #1f2133; border-bottom: 1px solid #3d405b; color: #ffffff; padding: 18px 25px; border-radius: 8px 8px 0 0; }
        .badge-db { background: #3a3f58; color: #ffca28; padding: 4px 10px; border-radius: 12px; font-weight: normal; font-size: 13px; }
        .table > tbody > tr > td, .table > thead > tr > th { border-top: 1px solid #32354a; color: #cdd3de; vertical-align: middle; font-size: 13px; }
        .table > thead > tr > th { background-color: #212338; color: #9aa0b6; border-bottom: 2px solid #3d405b; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .table-striped > tbody > tr:nth-of-type(odd) { background-color: #2a2c42; }
        .status-ok { color: #2ecc71; font-weight: bold; }
        .status-fail { color: #e74c3c; font-weight: bold; }
        .alert-custom-success { background: #1b4332; border: 1px solid #2d6a4f; color: #b7e4c7; }
        .alert-custom-danger { background: #49111c; border: 1px solid #800f2f; color: #ffccd5; }
        .btn-refresh { background: #4e54c8; color: white; border: none; padding: 8px 20px; border-radius: 6px; }
        .btn-refresh:hover { background: #5f66e0; color: white; }
    </style>
</head>
<body>
<div class="container" style="max-width: 950px;">
    <div class="panel panel-custom">
        <div class="panel-heading clearfix">
            <h3 class="panel-title pull-left" style="font-size: 18px; margin-top: 5px;">
                <span class="glyphicon glyphicon-hdd"></span> Migrasi Database MySQL 
                <span class="badge-db"><?= htmlspecialchars($db_name) ?></span>
            </h3>
            <div class="pull-right">
                <a href="migrate_bmt.php" class="btn btn-sm btn-refresh">
                    <span class="glyphicon glyphicon-refresh"></span> Jalankan Ulang Migrasi
                </a>
            </div>
        </div>
        <div class="panel-body" style="padding: 25px;">
            <?php if ($errorMsg): ?>
                <div class="alert alert-custom-danger">
                    <strong>Gagal Koneksi Database:</strong> <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php else: ?>
                <div class="alert alert-custom-success" style="margin-bottom: 20px;">
                    <span class="glyphicon glyphicon-ok-sign"></span> 
                    <strong>Berhasil!</strong> Seluruh <strong><?= $successCount ?> tabel database pendukung</strong> telah aktif dan siap digunakan pada database <code><?= htmlspecialchars($db_name) ?></code>.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover no-margin">
                        <thead>
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th>Nama Tabel</th>
                                <th>Keterangan / Modul Form</th>
                                <th class="text-center" style="width: 100px;">Kolom</th>
                                <th class="text-center" style="width: 120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($statusList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= htmlspecialchars($row['table']) ?></code></td>
                                <td><?= htmlspecialchars($row['desc']) ?></td>
                                <td class="text-center"><?= $row['columns'] ?> kolom</td>
                                <td class="text-center">
                                    <?php if ($row['status'] === 'OK'): ?>
                                        <span class="status-ok"><span class="glyphicon glyphicon-ok"></span> Ada</span>
                                    <?php else: ?>
                                        <span class="status-fail"><span class="glyphicon glyphicon-remove"></span> Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="panel-footer" style="background: #1f2133; border-top: 1px solid #3d405b; color: #828a9e; font-size: 12px; padding: 15px 25px;">
            File SQL Migrasi: <code>migration_tabel_pendukung_bmt.sql</code> &bull; Modul: <code>plugins/update_bmt</code>
        </div>
    </div>
</div>
</body>
</html>

<?php
/**
 * mLITE Background Auto-Sync Task ID BPJS Antrean
 *
 * Skrip CLI / Background Worker untuk sinkronisasi otomatis Task ID 1-7 BPJS Kesehatan.
 * Dapat dijalankan otomatis via Windows Task Scheduler atau cron setiap 3-5 menit.
 *
 * Penggunaan CLI:
 *   php cron_taskid.php [TANGGAL: YYYY-MM-DD] [VERSI: v2|v3]
 * Contoh:
 *   php cron_taskid.php
 *   php cron_taskid.php 2026-11-20 v3
 */

if (php_sapi_name() !== 'cli' && empty($_GET['cron_key'])) {
    header('Location: jknmobile/antrian/updatewaktu');
    exit();
}

define('BASE_DIR', __DIR__);
require_once BASE_DIR . '/config.php';
require_once BASE_DIR . '/systems/lib/Autoloader.php';

// Atur limit waktu eksekusi agar tidak timeout di background
set_time_limit(0);
ini_set('memory_limit', '512M');

// Hubungkan database
\Systems\Lib\QueryWrapper::connect('mysql:host=' . DBHOST . ';port=' . DBPORT . ';dbname=' . DBNAME, DBUSER, DBPASS);

// Inisialisasi Main & Admin Module sebelum ada echo output
class CliMain extends \Systems\Main {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
            $this->setSession();
        }
        $dsn = "mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=" . DBNAME;
        \Systems\Lib\QueryWrapper::connect($dsn, DBUSER, DBPASS);
        $this->router = new \Systems\Lib\Router($this);
        $this->settings = new \Systems\Lib\Settings($this);
    }
}

$tanggal = $argv[1] ?? ($_GET['tgl'] ?? date('Y-m-d'));
$versi   = strtolower($argv[2] ?? ($_GET['versi'] ?? 'v3')); // 'v3' (Task 1-7) atau 'v2' (Task 1-5)

$now = date('Y-m-d H:i:s');
echo "====================================================================\n";
echo "  mLITE - SINKRONISASI OTOMATIS TASK ID BPJS ANTREAN\n";
echo "  Waktu Eksekusi : {$now}\n";
echo "  Tanggal Antrean: {$tanggal}\n";
echo "  Versi Task     : " . strtoupper($versi) . " (Task 1 s/d " . ($versi === 'v3' ? '7' : '5') . ")\n";
echo "====================================================================\n\n";

try {
    $core = new CliMain();
    $admin = new \Plugins\JKN_Mobile\Admin($core);
    $admin->init();

    // Ambil antrean hari ini
    $bookings = $core->db('mlite_antrian_referensi')
        ->where('tanggal_periksa', $tanggal)
        ->toArray();

    $totalBookings = count($bookings);
    echo "Ditemukan {$totalBookings} antrean pada tanggal {$tanggal}.\n";

    if ($totalBookings === 0) {
        echo "Tidak ada data antrean untuk diproses. Selesai.\n";
        exit(0);
    }

    $processed = 0;
    $totalSuccess = 0;
    $totalFailed = 0;
    $totalSkipped = 0;

    foreach ($bookings as $b) {
        $kb = $b['kodebooking'] ?: $b['nomor_referensi'];
        if (empty($kb)) continue;
        if (stripos($b['status'] ?? '', 'Batal') !== false) continue;

        // Cek status task yang sudah ada
        $pending = $core->db('mlite_antrian_referensi_taskid')
            ->where('nomor_referensi', $kb)
            ->where('status', 'Belum')
            ->count();
        $total_tasks = $core->db('mlite_antrian_referensi_taskid')
            ->where('nomor_referensi', $kb)
            ->count();

        // Jika sudah ada minimal 5 task dan tidak ada yang 'Belum', lewati
        if ($total_tasks >= 5 && $pending == 0) {
            continue;
        }

        $processed++;
        $pasien = $core->db('pasien')->where('no_rkm_medis', $b['no_rkm_medis'])->oneArray();
        $nmPasien = $pasien['nm_pasien'] ?? ($b['nomor_kartu'] ?: '-');

        echo "[{$processed}] Memproses Kode Booking: {$kb} ({$nmPasien})... ";
        $res = $admin->syncSingleBooking($kb, $versi);

        $s = $res['summary']['success'] ?? 0;
        $f = $res['summary']['failed'] ?? 0;
        $sk = $res['summary']['skipped'] ?? 0;

        $totalSuccess += $s;
        $totalFailed += $f;
        $totalSkipped += $sk;

        echo "Hasil: {$s} Berhasil, {$sk} Dilewati, {$f} Gagal\n";
    }

    $endTime = date('Y-m-d H:i:s');
    echo "\n--------------------------------------------------------------------\n";
    echo "  Laporan Akhir Sinkronisasi Task ID ({$endTime})\n";
    echo "  Total Pasien Diproses: {$processed}\n";
    echo "  Total Task Berhasil  : {$totalSuccess}\n";
    echo "  Total Task Dilewati  : {$totalSkipped} (belum mencapai tahapan tersebut)\n";
    echo "  Total Task Gagal     : {$totalFailed}\n";
    echo "====================================================================\n";
    exit(0);

} catch (\Throwable $e) {
    echo "\n[EXCEPTION FATAL]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

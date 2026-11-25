<?php

namespace Plugins\Bridging_Hfis;

use Systems\AdminModule;
use Systems\Lib\BpjsService;

class Admin extends AdminModule
{
    protected $consid;
    protected $secretkey;
    protected $user_key;
    protected $bpjsurl;

    public function init()
    {
        $this->consid = $this->settings->get('jkn_mobile.BpjsConsID');
        $this->secretkey = $this->settings->get('jkn_mobile.BpjsSecretKey');
        $this->bpjsurl = $this->settings->get('jkn_mobile.BpjsAntrianUrl');
        $this->user_key = $this->settings->get('jkn_mobile.BpjsUserKey');
    }

    public function navigation()
    {
        return [
            'Manage' => 'manage',
            'Update HFIS' => 'updatehfis',
            'Kelola Cuti Dokter' => 'cutidokter',
            'Lihat HFIS' => 'lihathfis',
            'Jadwal SIMRS' => 'jadwaldokter',
        ];
    }

    public function getManage()
    {
        $this->_addHeaderFiles();
        $sub_modules = [
            ['name' => 'Bridging Update HFIS', 'url' => url([ADMIN, 'bridging_hfis', 'updatehfis']), 'icon' => 'calendar-check-o', 'desc' => 'Kirim / Sinkronisasi Jadwal Dokter SIMRS ke BPJS HFIS'],
            ['name' => 'Kelola Cuti Dokter', 'url' => url([ADMIN, 'bridging_hfis', 'cutidokter']), 'icon' => 'calendar-times-o', 'desc' => 'Pencatatan Cuti Dokter, Sinkronisasi Otomatis ke BPJS HFIS & Pemulihan Jadwal'],
            ['name' => 'Bridging Lihat HFIS', 'url' => url([ADMIN, 'bridging_hfis', 'lihathfis']), 'icon' => 'search', 'desc' => 'Tarik & Lihat Data Jadwal Dokter Resmi dari Server BPJS HFIS'],
            ['name' => 'Jadwal Dokter SIMRS', 'url' => url([ADMIN, 'bridging_hfis', 'jadwaldokter']), 'icon' => 'user-md', 'desc' => 'Kelola & Lihat Jadwal Praktek Dokter di Basis Data SIMRS'],
        ];

        $total_poli = $this->db('maping_poli_bpjs')->group('kd_poli_bpjs')->count();
        $total_dokter = $this->db('maping_dokter_dpjpvclaim')->count();
        $total_jadwal = $this->db('jadwal')->count();

        return $this->draw('manage.html', [
            'sub_modules' => htmlspecialchars_array($sub_modules),
            'consid' => $this->consid,
            'bpjsurl' => $this->bpjsurl,
            'has_secret' => !empty($this->secretkey),
            'has_userkey' => !empty($this->user_key),
            'total_poli' => $total_poli,
            'total_dokter' => $total_dokter,
            'total_jadwal' => $total_jadwal,
        ]);
    }

    public function anyPingHfis()
    {
        header('Content-Type: application/json');
        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Kredensial BPJS Antrean belum lengkap di Pengaturan JKN Mobile.']);
            exit();
        }

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Jakarta');

        // Gunakan endpoint referensi poliklinik resmi BPJS untuk health-check / ping
        $url = $this->bpjsurl . 'ref/poli';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);

        $code = $json['metadata']['code'] ?? 500;
        $message = $json['metadata']['message'] ?? ($output ?: 'Tidak ada balasan dari server BPJS');

        echo json_encode([
            'status' => ($code == '200') ? 'success' : 'error',
            'code' => $code,
            'message' => $message,
            'url' => $url,
            'consid' => $this->consid,
            'timestamp' => $tStamp
        ]);
        exit();
    }

    public function getUpdateHfis()
    {
        $this->_addHeaderFiles();
        $dokter = $this->db('maping_dokter_dpjpvclaim')->asc('nm_dokter_bpjs')->toArray();
        $poli = $this->db('maping_poli_bpjs')
            ->select(['kd_poli_bpjs', 'nm_poli_bpjs'])
            ->group('kd_poli_bpjs')
            ->asc('nm_poli_bpjs')
            ->toArray();
        return $this->draw('update.hfis.html', [
            'dokter' => htmlspecialchars_array($dokter),
            'poli' => htmlspecialchars_array($poli)
        ]);
    }

    public function anyGetJadwalHfis()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? $_GET['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? $_GET['kd_poli'] ?? '';

        if (empty($kd_dokter) || empty($kd_poli)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter dan Poliklinik BPJS harus dipilih']);
            exit();
        }

        $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
        if (!$dokterrs) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter tidak ditemukan di tabel mapping SIMRS']);
            exit();
        }

        $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
        $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
        if (empty($kd_poli_rs_list)) {
            $kd_poli_rs_list = [$kd_poli];
        }

        $jadwalList = $this->db('jadwal')
            ->where('kd_dokter', $dokterrs['kd_dokter'])
            ->where('kd_poli', 'IN', $kd_poli_rs_list)
            ->toArray();

        $dayMap = [
            'SENIN' => 1,
            'SELASA' => 2,
            'RABU' => 3,
            'KAMIS' => 4,
            'JUMAT' => 5,
            'SABTU' => 6,
            'AKHAD' => 0,
            'MINGGU' => 0
        ];

        $formatted = [];
        foreach ($jadwalList as $j) {
            $hk = strtoupper(trim($j['hari_kerja']));
            $dayNum = $dayMap[$hk] ?? null;
            if ($dayNum !== null) {
                $jamMulai = substr($j['jam_mulai'], 0, 5);
                $jamSelesai = substr($j['jam_selesai'], 0, 5);
                $isSore = intval(substr($jamMulai, 0, 2)) >= 14;

                $formatted[] = [
                    'hari_kerja' => $hk,
                    'day_num' => $dayNum,
                    'sesi' => $isSore ? 'sore' : 'pagi',
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'kuota' => intval($j['kuota'] ?? 0)
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'dokter' => $dokterrs,
            'count' => count($formatted),
            'jadwal' => $formatted
        ]);
        exit();
    }

    public function anyTarikJadwalBpjs()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? $_GET['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? $_GET['kd_poli'] ?? '';
        $simpan_ke_simrs = isset($_POST['simpan_ke_simrs']) ? (bool)$_POST['simpan_ke_simrs'] : true;

        if (empty($kd_dokter) || empty($kd_poli)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter dan Poliklinik BPJS harus dipilih.']);
            exit();
        }

        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            echo json_encode(['status' => 'error', 'message' => 'Kredensial BPJS Antrean (ConsID / SecretKey / URL) belum diisi di Pengaturan JKN Mobile.']);
            exit();
        }

        // Cari data mapping dokter dan poli di SIMRS lokal
        $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
        $polirs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->oneArray();
        $kd_dokter_rs = $dokterrs['kd_dokter'] ?? null;
        $kd_poli_rs = $polirs['kd_poli_rs'] ?? null;

        // Ambil 7 hari rentang minggu ini (Senin s/d Minggu)
        $monday = date('Y-m-d', strtotime('monday this week'));
        $dayMap = [
            'SENIN' => 1,
            'SELASA' => 2,
            'RABU' => 3,
            'KAMIS' => 4,
            'JUMAT' => 5,
            'SABTU' => 6,
            'AKHAD' => 0,
            'MINGGU' => 0
        ];

        $jadwalDitemukan = [];
        $uniqueKeys = [];
        $hariHariDisimpan = [];

        for ($i = 0; $i < 7; $i++) {
            $tgl = date('Y-m-d', strtotime("$monday +$i days"));
            date_default_timezone_set('UTC');
            $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
            $key = $this->consid . $this->secretkey . $tStamp;
            date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Jakarta');

            $url = $this->bpjsurl . 'jadwaldokter/kodepoli/' . $kd_poli . '/tanggal/' . $tgl;
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
            $json = json_decode($output, true);

            if (!empty($json['metadata']['code']) && $json['metadata']['code'] == 200 && !empty($json['response'])) {
                $dec = stringDecrypt($key, $json['response']);
                if (!empty($dec)) {
                    $decomp = \LZCompressor\LZString::decompressFromEncodedURIComponent($dec);
                    $list = json_decode($decomp, true);
                    if (is_array($list)) {
                        foreach ($list as $item) {
                            $kodedokterBpjs = (string)($item['kodedokter'] ?? '');
                            $kdTrim = ltrim($kodedokterBpjs, '0');
                            $targetTrim = ltrim((string)$kd_dokter, '0');

                            if ($kodedokterBpjs == (string)$kd_dokter || ($kdTrim !== '' && $kdTrim == $targetTrim)) {
                                $namahari = strtoupper(trim($item['namahari'] ?? ''));
                                $dayNum = $dayMap[$namahari] ?? (date('N', strtotime($tgl)));
                                if ($dayNum == 7) $dayNum = 0;

                                $jadwalStr = $item['jadwal'] ?? '';
                                $jamMulai = '08:00';
                                $jamSelesai = '12:00';
                                if (strpos($jadwalStr, '-') !== false) {
                                    $parts = explode('-', $jadwalStr, 2);
                                    $jamMulai = substr(trim($parts[0]), 0, 5);
                                    $jamSelesai = substr(trim($parts[1]), 0, 5);
                                }

                                $isSore = intval(substr($jamMulai, 0, 2)) >= 14;
                                $sesi = $isSore ? 'sore' : 'pagi';
                                $kuota = intval($item['kapasitaspasien'] ?? 0);

                                $uKey = $dayNum . '|' . $sesi;
                                if (!isset($uniqueKeys[$uKey])) {
                                    $uniqueKeys[$uKey] = true;
                                    $jadwalDitemukan[] = [
                                        'hari_kerja' => $namahari ?: (array_search($dayNum, $dayMap) ?: 'SENIN'),
                                        'day_num' => $dayNum,
                                        'sesi' => $sesi,
                                        'jam_mulai' => $jamMulai,
                                        'jam_selesai' => $jamSelesai,
                                        'kuota' => $kuota,
                                        'namadokter' => $item['namadokter'] ?? '',
                                        'namapoli' => $item['namapoli'] ?? ''
                                    ];

                                    // Simpan ke database SIMRS jika dokter dan poli terpetakan
                                    if ($simpan_ke_simrs && $kd_dokter_rs && $kd_poli_rs) {
                                        $hariDb = $namahari ?: 'SENIN';
                                        $checkJadwal = $this->db('jadwal')
                                            ->where('kd_dokter', $kd_dokter_rs)
                                            ->where('hari_kerja', $hariDb)
                                            ->where('kd_poli', $kd_poli_rs)
                                            ->oneArray();
                                        if ($checkJadwal) {
                                            $this->db('jadwal')
                                                ->where('kd_dokter', $kd_dokter_rs)
                                                ->where('hari_kerja', $hariDb)
                                                ->where('kd_poli', $kd_poli_rs)
                                                ->update([
                                                    'jam_mulai' => $jamMulai . ':00',
                                                    'jam_selesai' => $jamSelesai . ':00',
                                                    'kuota' => $kuota,
                                                ]);
                                        } else {
                                            $this->db('jadwal')->save([
                                                'kd_dokter' => $kd_dokter_rs,
                                                'hari_kerja' => $hariDb,
                                                'kd_poli' => $kd_poli_rs,
                                                'jam_mulai' => $jamMulai . ':00',
                                                'jam_selesai' => $jamSelesai . ':00',
                                                'kuota' => $kuota,
                                            ]);
                                        }
                                        $hariHariDisimpan[] = $hariDb;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($jadwalDitemukan),
            'jadwal' => $jadwalDitemukan,
            'tersimpan_ke_simrs' => !empty($hariHariDisimpan),
            'hari_tersimpan' => $hariHariDisimpan,
            'message' => count($jadwalDitemukan) > 0
                ? ('Berhasil menarik ' . count($jadwalDitemukan) . ' jadwal praktek dokter resmi dari server BPJS HFIS' . (!empty($hariHariDisimpan) ? ' & otomatis disinkronkan ke SIMRS.' : '.'))
                : 'Tidak ditemukan jadwal aktif untuk dokter ini di server BPJS HFIS pada poli yang dipilih.'
        ]);
        exit();
    }

    public function postUpdateBridgeHfis()
    {
        header('Content-Type: application/json');
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        if ($data !== null) {
            $kd_dokter = $data['kd_dokter'] ?? '';
            $kd_poli = $data['kd_poli'] ?? '';
            $jadwal_input = $data['jadwal'] ?? [];

            if (empty($kd_dokter) || empty($kd_poli)) {
                echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Dokter dan Poliklinik harus dipilih.']);
                exit();
            }

            if (empty($jadwal_input)) {
                echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Pilih minimal satu hari praktek dan atur jam buka/tutup.']);
                exit();
            }

            $kole = [];
            foreach ($jadwal_input as $value) {
                $hari = strtok($value['hari'], '|');
                $kole[] = array('hari' => $hari, 'buka' => $value['buka'], 'tutup' => $value['tutup']);
            }

            $jsonbody = array(
                'kodepoli' => $kd_poli,
                'kodesubspesialis' => $kd_poli,
                'kodedokter' => $kd_dokter,
                'jadwal' => $kole
            );

            $dataJson = json_encode($jsonbody);
            $ret = $this->postUpdateJadwalHfis($dataJson);
            $message = substr($ret, strpos($ret, "|") + 1);
            $code = strtok($ret, '|');

            if ($code == '200') {
                $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
                if ($dokterrs) {
                    $polirs = $this->db('maping_poli_bpjs')
                        ->where('kd_poli_bpjs', $kd_poli)
                        ->oneArray();
                    $kd_poli_rs = $polirs['kd_poli_rs'] ?? null;

                    $day = array(
                        '0' => 'AKHAD',
                        '1' => 'SENIN',
                        '2' => 'SELASA',
                        '3' => 'RABU',
                        '4' => 'KAMIS',
                        '5' => 'JUMAT',
                        '6' => 'SABTU'
                    );

                    // Catat hari-hari yang aktif dikirim dalam batch ini
                    $activeDaysInInput = [];
                    foreach ($jadwal_input as $val) {
                        $h = strtok($val['hari'], '|');
                        if (isset($day[$h])) {
                            $activeDaysInInput[] = $day[$h];
                        }
                    }

                    // Sinkronkan jadwal lokal SIMRS: hapus hari kerja lokal yang tidak lagi dicentang/dikirim (ditutup di HFIS)
                    if ($kd_poli_rs && !empty($activeDaysInInput)) {
                        $existingJadwal = $this->db('jadwal')
                            ->where('kd_dokter', $dokterrs['kd_dokter'])
                            ->where('kd_poli', $kd_poli_rs)
                            ->toArray();
                        foreach ($existingJadwal as $ej) {
                            $ejHari = strtoupper(trim($ej['hari_kerja']));
                            if (!in_array($ejHari, $activeDaysInInput)) {
                                $this->db('jadwal')
                                    ->where('kd_dokter', $dokterrs['kd_dokter'])
                                    ->where('kd_poli', $kd_poli_rs)
                                    ->where('hari_kerja', $ej['hari_kerja'])
                                    ->delete();
                            }
                        }
                    }

                    foreach ($jadwal_input as $value) {
                        $jenis = substr($value['hari'], strpos($value['hari'], "|") + 1);
                        if ($jenis == '') {
                            $jenis = 'pagi';
                        }

                        // Fallback to direct mapping if clinic name does not end in 'pagi' / 'sore'
                        $polirsSesi = $this->db('maping_poli_bpjs')
                            ->join('poliklinik', 'maping_poli_bpjs.kd_poli_rs = poliklinik.kd_poli')
                            ->where('maping_poli_bpjs.kd_poli_bpjs', $kd_poli)
                            ->like('poliklinik.nm_poli', '%' . $jenis)
                            ->oneArray();

                        $targetPoliRs = $polirsSesi['kd_poli'] ?? $polirsSesi['kd_poli_rs'] ?? $kd_poli_rs;
                        if (!$targetPoliRs) continue;

                        $hari = strtok($value['hari'], '|');
                        $namahari = $day[$hari] ?? 'SENIN';

                        if ($value['buka'] == '' && $value['tutup'] == '') {
                            $checkDulu = $this->db('jadwal')
                                ->where('kd_dokter', $dokterrs['kd_dokter'])
                                ->where('hari_kerja', $namahari)
                                ->where('kd_poli', $targetPoliRs)
                                ->oneArray();
                            if ($checkDulu) {
                                $this->db('jadwal')
                                    ->where('kd_dokter', $dokterrs['kd_dokter'])
                                    ->where('hari_kerja', $namahari)
                                    ->where('kd_poli', $targetPoliRs)
                                    ->delete();
                            }
                        } else {
                            $checkJadwal = $this->db('jadwal')
                                ->where('kd_dokter', $dokterrs['kd_dokter'])
                                ->where('hari_kerja', $namahari)
                                ->where('kd_poli', $targetPoliRs)
                                ->oneArray();
                            if ($checkJadwal) {
                                $kuotaSimpan = intval($value['kuota'] ?? 0);
                                if ($kuotaSimpan <= 0 && !empty($checkJadwal['kuota']) && intval($checkJadwal['kuota']) > 0) {
                                    $kuotaSimpan = intval($checkJadwal['kuota']);
                                }
                                $this->db('jadwal')
                                    ->where('kd_dokter', $dokterrs['kd_dokter'])
                                    ->where('hari_kerja', $namahari)
                                    ->where('kd_poli', $targetPoliRs)
                                    ->update([
                                        'jam_mulai' => $value['buka'] . (strlen($value['buka']) == 5 ? ':00' : ''),
                                        'jam_selesai' => $value['tutup'] . (strlen($value['tutup']) == 5 ? ':00' : ''),
                                        'kuota' => $kuotaSimpan,
                                    ]);
                            } else {
                                $this->db('jadwal')->save([
                                    'kd_dokter' => $dokterrs['kd_dokter'],
                                    'hari_kerja' => $namahari,
                                    'kd_poli' => $targetPoliRs,
                                    'jam_mulai' => $value['buka'] . (strlen($value['buka']) == 5 ? ':00' : ''),
                                    'jam_selesai' => $value['tutup'] . (strlen($value['tutup']) == 5 ? ':00' : ''),
                                    'kuota' => intval($value['kuota'] ?? 0),
                                ]);
                            }
                        }
                    }
                }
                echo json_encode(['status' => 'success', 'code' => 200, 'message' => 'Berhasil Simpan Jadwal ke BPJS HFIS & Basis Data SIMRS!']);
            } else {
                echo json_encode(['status' => 'error', 'code' => $code, 'message' => $message]);
            }
        } else {
            echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Format data JSON tidak valid']);
        }
        exit();
    }

    function postUpdateJadwalHfis($data)
    {
        $url = $this->bpjsurl . 'jadwaldokter/updatejadwaldokter';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        $code = $json['metadata']['code'] ?? 500;
        $message = $json['metadata']['message'] ?? ($output ?: 'Tidak ada balasan dari BPJS');
        return $code . '|' . $message;
    }

    private function _initCutiTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `mlite_bridging_hfis_cuti` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `kd_dokter_bpjs` VARCHAR(50) NOT NULL,
          `nm_dokter_bpjs` VARCHAR(150) NOT NULL,
          `kd_poli_bpjs` VARCHAR(50) NOT NULL,
          `nm_poli_bpjs` VARCHAR(150) NOT NULL,
          `tgl_mulai` DATE NOT NULL,
          `tgl_selesai` DATE NOT NULL,
          `alasan` VARCHAR(255) DEFAULT '',
          `ada_pengganti` ENUM('0','1') DEFAULT '0',
          `kd_dokter_pengganti` VARCHAR(50) DEFAULT NULL,
          `nm_dokter_pengganti` VARCHAR(150) DEFAULT NULL,
          `status` ENUM('aktif','selesai','dibatalkan') DEFAULT 'aktif',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `idx_dokter_tgl` (`kd_dokter_bpjs`, `tgl_mulai`, `tgl_selesai`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $this->db()->pdo()->exec($sql);
        } catch (\Throwable $e) {
            error_log("bridging_hfis _initCutiTable error: " . $e->getMessage());
        }
    }

    public function anyCekPasienHariIni()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? $_GET['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? $_GET['kd_poli'] ?? '';
        $tanggal = $_POST['tanggal'] ?? $_GET['tanggal'] ?? date('Y-m-d');

        if (empty($kd_dokter) || empty($kd_poli)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter dan Poliklinik BPJS harus dipilih.']);
            exit();
        }

        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            echo json_encode(['status' => 'error', 'message' => 'Kredensial BPJS Antrean belum lengkap.']);
            exit();
        }

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid . $this->secretkey . $tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Jakarta');

        $pasienList = [];
        try {
            $urlAntrean = $this->bpjsurl . 'antrean/pendaftaran/tanggal/' . $tanggal;
            $outAntrean = BpjsService::get($urlAntrean, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
            $jsonAntrean = json_decode($outAntrean, true);

            if (!empty($jsonAntrean['response'])) {
                $decAntrean = stringDecrypt($key, $jsonAntrean['response']);
                if (!empty($decAntrean)) {
                    $decompAntrean = \LZCompressor\LZString::decompressFromEncodedURIComponent($decAntrean);
                    $listAntrean = json_decode($decompAntrean, true);
                    if (is_array($listAntrean)) {
                        foreach ($listAntrean as $ba) {
                            $st = strtolower($ba['status'] ?? '');
                            if ($st === 'batal' || $st === 'batal antrean' || $st === 'batal_antrean') {
                                continue;
                            }
                            $kd_dok = (string)($ba['kodedokter'] ?? '');
                            $kd_dok_trim = ltrim($kd_dok, '0');
                            $targetTrim = ltrim((string)$kd_dokter, '0');

                            if ($kd_dok === (string)$kd_dokter || ($kd_dok_trim !== '' && $kd_dok_trim === $targetTrim)) {
                                $norm = $ba['norekammedis'] ?? '';
                                $noka = $ba['nokapst'] ?? '';
                                $pName = '';
                                if (!empty($norm)) {
                                    $pRow = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
                                    if ($pRow) $pName = $pRow['nm_pasien'];
                                }
                                if (empty($pName) && !empty($noka)) {
                                    $pRow = $this->db('pasien')->where('no_peserta', $noka)->oneArray();
                                    if ($pRow) $pName = $pRow['nm_pasien'];
                                }
                                if (empty($pName)) {
                                    $pName = !empty($ba['namapasien']) ? $ba['namapasien'] : ('Peserta BPJS (' . ($noka ?: $norm) . ')');
                                }

                                $pasienList[] = [
                                    'kodebooking' => $ba['kodebooking'] ?? '',
                                    'noantrean' => !empty($ba['noantrean']) ? $ba['noantrean'] : ($ba['angkaantrean'] ?? '-'),
                                    'norm' => $norm ?: '-',
                                    'noka' => $noka ?: '-',
                                    'namapasien' => $pName,
                                    'nohp' => $ba['nohp'] ?? '-',
                                    'jampraktek' => !empty($ba['estimasidilayani']) ? $ba['estimasidilayani'] : ($ba['jampraktek'] ?? '-'),
                                    'status' => $ba['status'] ?? 'Terdaftar'
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("bridging_hfis anyCekPasienHariIni error: " . $e->getMessage());
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($pasienList),
            'data' => $pasienList,
            'tanggal' => $tanggal,
            'kd_dokter' => $kd_dokter,
            'kd_poli' => $kd_poli
        ]);
        exit();
    }

    public function anyTutupHariIni()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? '';
        $batal_antrean = isset($_POST['batal_antrean']) ? intval($_POST['batal_antrean']) : 1;
        $alasan_batal = $_POST['alasan_batal'] ?? 'Dokter berhalangan hadir / izin sakit';

        if (empty($kd_dokter) || empty($kd_poli)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter dan Poliklinik BPJS harus dipilih.']);
            exit();
        }

        $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
        $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
        $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
        if (empty($kd_poli_rs_list)) $kd_poli_rs_list = [$kd_poli];

        $dayMap = [
            1 => 'SENIN',
            2 => 'SELASA',
            3 => 'RABU',
            4 => 'KAMIS',
            5 => 'JUMAT',
            6 => 'SABTU',
            7 => 'AKHAD'
        ];
        $todayNum = intval(date('N'));
        $todayName = $dayMap[$todayNum] ?? 'SENIN';

        // 1. Ambil jadwal dokter dari SIMRS
        $jadwalList = [];
        if ($dokterrs) {
            $jadwalList = $this->db('jadwal')
                ->where('kd_dokter', $dokterrs['kd_dokter'])
                ->where('kd_poli', 'IN', $kd_poli_rs_list)
                ->toArray();
        }

        // 2. Susun jadwal yang dikirim ke BPJS DENGAN MENGECUALIKAN HARI INI
        $jadwalBpjs = [];
        foreach ($jadwalList as $j) {
            $hk = strtoupper(trim($j['hari_kerja']));
            if ($hk === $todayName) {
                // Hari ini sengaja dilewati (DITUTUP)
                continue;
            }
            $dayNum = array_search($hk, $dayMap);
            if ($dayNum !== false && $dayNum > 0 && $dayNum < 7) {
                $jamMulai = substr($j['jam_mulai'], 0, 5);
                $jamSelesai = substr($j['jam_selesai'], 0, 5);
                $jadwalBpjs[] = [
                    'hari' => (string)$dayNum,
                    'buka' => $jamMulai,
                    'tutup' => $jamSelesai
                ];
            }
        }

        // Kirim update jadwal ke server BPJS HFIS sesuai spesifikasi API BPJS
        $payload = [
            'kodepoli' => $kd_poli,
            'kodesubspesialis' => $kd_poli,
            'kodedokter' => (int)$kd_dokter,
            'jadwal' => $jadwalBpjs
        ];

        $updateResult = $this->postUpdateJadwalHfis(json_encode($payload));
        $resParts = explode('|', $updateResult, 2);
        $resCode = intval($resParts[0] ?? 500);
        $resMsg = $resParts[1] ?? 'Tidak ada balasan BPJS';

        if ($resCode != 200 && $resCode != 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengupdate jadwal tutup ke server BPJS: ' . $resMsg
            ]);
            exit();
        }

        // 3. Batalkan antrean pasien terdaftar jika diminta (Sesuai Aturan Resmi BPJS via antrean/batal)
        $batalCount = 0;
        $batalErrors = [];
        if ($batal_antrean == 1) {
            date_default_timezone_set('UTC');
            $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
            $key = $this->consid . $this->secretkey . $tStamp;
            date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Jakarta');

            try {
                $urlAntrean = $this->bpjsurl . 'antrean/pendaftaran/tanggal/' . date('Y-m-d');
                $outAntrean = BpjsService::get($urlAntrean, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                $jsonAntrean = json_decode($outAntrean, true);

                if (!empty($jsonAntrean['response'])) {
                    $decAntrean = stringDecrypt($key, $jsonAntrean['response']);
                    if (!empty($decAntrean)) {
                        $decompAntrean = \LZCompressor\LZString::decompressFromEncodedURIComponent($decAntrean);
                        $listAntrean = json_decode($decompAntrean, true);
                        if (is_array($listAntrean)) {
                            foreach ($listAntrean as $ba) {
                                $st = strtolower($ba['status'] ?? '');
                                if ($st === 'batal' || $st === 'batal antrean' || $st === 'batal_antrean') {
                                    continue;
                                }
                                $kd_dok = (string)($ba['kodedokter'] ?? '');
                                $kd_dok_trim = ltrim($kd_dok, '0');
                                $targetTrim = ltrim((string)$kd_dokter, '0');

                                if ($kd_dok === (string)$kd_dokter || ($kd_dok_trim !== '' && $kd_dok_trim === $targetTrim)) {
                                    $kb = $ba['kodebooking'] ?? '';
                                    if (!empty($kb)) {
                                        $urlBatal = $this->bpjsurl . 'antrean/batal';
                                        $dataBatal = json_encode([
                                            'kodebooking' => $kb,
                                            'keterangan' => $alasan_batal
                                        ]);
                                        $outBatal = BpjsService::post($urlBatal, $dataBatal, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                                        $jsonBatal = json_decode($outBatal, true);
                                        if (!empty($jsonBatal['metadata']['code']) && $jsonBatal['metadata']['code'] == 200) {
                                            $batalCount++;
                                        } else {
                                            $batalErrors[] = $kb . ' (' . ($jsonBatal['metadata']['message'] ?? 'gagal') . ')';
                                        }

                                        // Update status di basis data SIMRS jika ada
                                        try {
                                            $this->db('booking_registrasi')
                                                ->where('tanggal_periksa', date('Y-m-d'))
                                                ->where('kd_dokter', $dokterrs['kd_dokter'])
                                                ->update(['status' => 'Batal']);
                                        } catch (\Throwable $eDb) {}
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $eBatal) {
                error_log("bridging_hfis auto batal error: " . $eBatal->getMessage());
            }
        }

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => "Pelayanan dokter hari ini ({$todayName}, " . date('d-m-Y') . ") berhasil DITUTUP di server BPJS HFIS." .
                ($batal_antrean ? " Berhasil membatalkan {$batalCount} antrean pasien terdaftar di server BPJS secara resmi." : ""),
            'hari_ditutup' => $todayName,
            'batal_count' => $batalCount,
            'batal_errors' => $batalErrors
        ]);
        exit();
    }

    public function anyPulihkanJadwal()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? '';

        if (empty($kd_dokter) || empty($kd_poli)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter dan Poliklinik BPJS harus dipilih.']);
            exit();
        }

        $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
        $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
        $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
        if (empty($kd_poli_rs_list)) $kd_poli_rs_list = [$kd_poli];

        if (!$dokterrs) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter tidak ditemukan di tabel mapping SIMRS.']);
            exit();
        }

        $jadwalList = $this->db('jadwal')
            ->where('kd_dokter', $dokterrs['kd_dokter'])
            ->where('kd_poli', 'IN', $kd_poli_rs_list)
            ->toArray();

        if (empty($jadwalList)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Belum ada master jadwal dokter tersimpan di database SIMRS lokal. Silakan gunakan tombol "Tarik Jadwal Resmi BPJS" atau tentukan jadwal di form terlebih dahulu.'
            ]);
            exit();
        }

        $dayMap = [
            'SENIN' => 1,
            'SELASA' => 2,
            'RABU' => 3,
            'KAMIS' => 4,
            'JUMAT' => 5,
            'SABTU' => 6,
            'AKHAD' => 7
        ];

        $jadwalBpjs = [];
        $hariDipulihkan = [];
        foreach ($jadwalList as $j) {
            $hk = strtoupper(trim($j['hari_kerja']));
            $dayNum = $dayMap[$hk] ?? null;
            if ($dayNum && $dayNum > 0 && $dayNum < 7) {
                $jamMulai = substr($j['jam_mulai'], 0, 5);
                $jamSelesai = substr($j['jam_selesai'], 0, 5);
                $jadwalBpjs[] = [
                    'hari' => (string)$dayNum,
                    'buka' => $jamMulai,
                    'tutup' => $jamSelesai
                ];
                $hariDipulihkan[] = $hk;
            }
        }

        $payload = [
            'kodepoli' => $kd_poli,
            'kodesubspesialis' => $kd_poli,
            'kodedokter' => (int)$kd_dokter,
            'jadwal' => $jadwalBpjs
        ];

        $updateResult = $this->postUpdateJadwalHfis(json_encode($payload));
        $resParts = explode('|', $updateResult, 2);
        $resCode = intval($resParts[0] ?? 500);
        $resMsg = $resParts[1] ?? 'Tidak ada balasan BPJS';

        if ($resCode == 200 || $resCode == 1) {
            echo json_encode([
                'status' => 'success',
                'code' => 200,
                'message' => "Jadwal normal dokter {$dokterrs['nm_dokter_bpjs']} berhasil DIPULIHKAN ke server BPJS HFIS (" . count($jadwalBpjs) . " hari: " . implode(', ', array_unique($hariDipulihkan)) . ").",
                'count' => count($jadwalBpjs),
                'hari' => array_unique($hariDipulihkan)
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal memulihkan jadwal ke server BPJS: ' . $resMsg
            ]);
        }
        exit();
    }

    public function getCutiDokter()
    {
        $this->_initCutiTable();
        $this->_addHeaderFiles();

        $dokter = $this->db('maping_dokter_dpjpvclaim')->asc('nm_dokter_bpjs')->toArray();
        $poli = $this->db('maping_poli_bpjs')
            ->select(['kd_poli_bpjs', 'nm_poli_bpjs'])
            ->group('kd_poli_bpjs')
            ->asc('nm_poli_bpjs')
            ->toArray();

        $cutiList = $this->db('mlite_bridging_hfis_cuti')->desc('id')->toArray();

        return $this->draw('cuti.dokter.html', [
            'dokter' => htmlspecialchars_array($dokter),
            'poli' => htmlspecialchars_array($poli),
            'cuti_list' => htmlspecialchars_array($cutiList),
            'today' => date('Y-m-d')
        ]);
    }

    public function postSimpanCutiDokter()
    {
        header('Content-Type: application/json');
        $this->_initCutiTable();

        $kd_dokter = $_POST['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? '';
        $tgl_mulai = $_POST['tgl_mulai'] ?? '';
        $tgl_selesai = $_POST['tgl_selesai'] ?? '';
        $alasan = $_POST['alasan'] ?? 'Cuti Tahunan';
        $ada_pengganti = isset($_POST['ada_pengganti']) ? intval($_POST['ada_pengganti']) : 0;
        $kd_dokter_pengganti = $_POST['kd_dokter_pengganti'] ?? '';

        if (empty($kd_dokter) || empty($kd_poli) || empty($tgl_mulai) || empty($tgl_selesai)) {
            echo json_encode(['status' => 'error', 'message' => 'Dokter, Poliklinik, dan Rentang Tanggal Cuti wajib diisi lengkap.']);
            exit();
        }

        if (strtotime($tgl_selesai) < strtotime($tgl_mulai)) {
            echo json_encode(['status' => 'error', 'message' => 'Tanggal Selesai Cuti tidak boleh mendahului Tanggal Mulai Cuti.']);
            exit();
        }

        $docRow = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
        $poliRow = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->oneArray();

        $nm_dokter = $docRow ? $docRow['nm_dokter_bpjs'] : $kd_dokter;
        $nm_poli = $poliRow ? $poliRow['nm_poli_bpjs'] : $kd_poli;

        $nm_pengganti = null;
        if ($ada_pengganti && !empty($kd_dokter_pengganti)) {
            $docP = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter_pengganti)->oneArray();
            $nm_pengganti = $docP ? $docP['nm_dokter_bpjs'] : $kd_dokter_pengganti;
        }

        $this->db('mlite_bridging_hfis_cuti')->save([
            'kd_dokter_bpjs' => $kd_dokter,
            'nm_dokter_bpjs' => $nm_dokter,
            'kd_poli_bpjs' => $kd_poli,
            'nm_poli_bpjs' => $nm_poli,
            'tgl_mulai' => $tgl_mulai,
            'tgl_selesai' => $tgl_selesai,
            'alasan' => $alasan,
            'ada_pengganti' => $ada_pengganti ? '1' : '0',
            'kd_dokter_pengganti' => $ada_pengganti ? $kd_dokter_pengganti : null,
            'nm_dokter_pengganti' => $ada_pengganti ? $nm_pengganti : null,
            'status' => 'aktif'
        ]);

        $pesanBpjs = "";
        $today = date('Y-m-d');
        // Jika cuti mencakup hari ini dan tanpa dokter pengganti, otomatis tutup hari ini di server BPJS HFIS
        if ($today >= $tgl_mulai && $today <= $tgl_selesai && !$ada_pengganti) {
            try {
                $dayMap = [1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS', 5 => 'JUMAT', 6 => 'SABTU', 7 => 'AKHAD'];
                $todayNum = intval(date('N'));
                $todayName = $dayMap[$todayNum] ?? 'SENIN';

                $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
                $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
                if (empty($kd_poli_rs_list)) $kd_poli_rs_list = [$kd_poli];

                $jadwalList = $this->db('jadwal')
                    ->where('kd_dokter', $docRow ? $docRow['kd_dokter'] : '')
                    ->where('kd_poli', 'IN', $kd_poli_rs_list)
                    ->toArray();

                $jadwalBpjs = [];
                foreach ($jadwalList as $j) {
                    $hk = strtoupper(trim($j['hari_kerja']));
                    if ($hk === $todayName) continue;
                    $dNum = array_search($hk, $dayMap);
                    if ($dNum && $dNum > 0 && $dNum < 7) {
                        $jm = substr($j['jam_mulai'], 0, 5);
                        $js = substr($j['jam_selesai'], 0, 5);
                        $jadwalBpjs[] = [
                            'hari' => $dNum . '|' . (intval(substr($jm, 0, 2)) >= 14 ? 'sore' : 'pagi'),
                            'buka' => $jm,
                            'tutup' => $js,
                            'kuota' => intval($j['kuota'] ?? 0)
                        ];
                    }
                }
                $payload = ['kd_dokter' => $kd_dokter, 'kd_poli' => $kd_poli, 'jadwal' => $jadwalBpjs];
                $this->postUpdateJadwalHfis(json_encode($payload));
                $pesanBpjs = " Jadwal dokter untuk hari ini ({$todayName}) otomatis dinonaktifkan di server BPJS HFIS.";
            } catch (\Throwable $eCutiSync) {
                error_log("bridging_hfis postSimpanCutiDokter sync error: " . $eCutiSync->getMessage());
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Jadwal cuti dokter {$nm_dokter} periode {$tgl_mulai} s/d {$tgl_selesai} berhasil dicatat di SIMRS.{$pesanBpjs}"
        ]);
        exit();
    }

    public function postAkhiriCutiDokter()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        $pulihkan_ke_bpjs = isset($_POST['pulihkan']) ? intval($_POST['pulihkan']) : 1;

        $cuti = $this->db('mlite_bridging_hfis_cuti')->where('id', $id)->oneArray();
        if (!$cuti) {
            echo json_encode(['status' => 'error', 'message' => 'Data cuti tidak ditemukan.']);
            exit();
        }

        $this->db('mlite_bridging_hfis_cuti')->where('id', $id)->update(['status' => 'selesai']);

        $pesanPulihkan = "";
        if ($pulihkan_ke_bpjs) {
            $kd_dokter = $cuti['kd_dokter_bpjs'];
            $kd_poli = $cuti['kd_poli_bpjs'];
            $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
            $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
            $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
            if (empty($kd_poli_rs_list)) $kd_poli_rs_list = [$kd_poli];

            if ($dokterrs) {
                $jadwalList = $this->db('jadwal')
                    ->where('kd_dokter', $dokterrs['kd_dokter'])
                    ->where('kd_poli', 'IN', $kd_poli_rs_list)
                    ->toArray();

                $dayMap = ['SENIN' => 1, 'SELASA' => 2, 'RABU' => 3, 'KAMIS' => 4, 'JUMAT' => 5, 'SABTU' => 6];
                $jadwalBpjs = [];
                foreach ($jadwalList as $j) {
                    $hk = strtoupper(trim($j['hari_kerja']));
                    $dayNum = $dayMap[$hk] ?? null;
                    if ($dayNum) {
                        $jm = substr($j['jam_mulai'], 0, 5);
                        $js = substr($j['jam_selesai'], 0, 5);
                        $jadwalBpjs[] = [
                            'hari' => $dayNum . '|' . (intval(substr($jm, 0, 2)) >= 14 ? 'sore' : 'pagi'),
                            'buka' => $jm,
                            'tutup' => $js,
                            'kuota' => intval($j['kuota'] ?? 0)
                        ];
                    }
                }
                if (!empty($jadwalBpjs)) {
                    $payload = ['kd_dokter' => $kd_dokter, 'kd_poli' => $kd_poli, 'jadwal' => $jadwalBpjs];
                    $this->postUpdateJadwalHfis(json_encode($payload));
                    $pesanPulihkan = " Jadwal dokter normal berhasil dipulihkan kembali ke server BPJS HFIS.";
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Masa cuti dokter {$cuti['nm_dokter_bpjs']} berhasil diselesaikan.{$pesanPulihkan}"
        ]);
        exit();
    }

    public function postBatalkanCutiDokter()
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        $pulihkan_ke_bpjs = isset($_POST['pulihkan']) ? intval($_POST['pulihkan']) : 1;

        $cuti = $this->db('mlite_bridging_hfis_cuti')->where('id', $id)->oneArray();
        if (!$cuti) {
            echo json_encode(['status' => 'error', 'message' => 'Data cuti tidak ditemukan.']);
            exit();
        }

        $this->db('mlite_bridging_hfis_cuti')->where('id', $id)->update(['status' => 'dibatalkan']);

        $pesanPulihkan = "";
        if ($pulihkan_ke_bpjs) {
            $kd_dokter = $cuti['kd_dokter_bpjs'];
            $kd_poli = $cuti['kd_poli_bpjs'];
            $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
            $polirsList = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli)->toArray();
            $kd_poli_rs_list = array_values(array_filter(array_column($polirsList, 'kd_poli_rs')));
            if (empty($kd_poli_rs_list)) $kd_poli_rs_list = [$kd_poli];

            if ($dokterrs) {
                $jadwalList = $this->db('jadwal')
                    ->where('kd_dokter', $dokterrs['kd_dokter'])
                    ->where('kd_poli', 'IN', $kd_poli_rs_list)
                    ->toArray();

                $dayMap = ['SENIN' => 1, 'SELASA' => 2, 'RABU' => 3, 'KAMIS' => 4, 'JUMAT' => 5, 'SABTU' => 6];
                $jadwalBpjs = [];
                foreach ($jadwalList as $j) {
                    $hk = strtoupper(trim($j['hari_kerja']));
                    $dayNum = $dayMap[$hk] ?? null;
                    if ($dayNum) {
                        $jm = substr($j['jam_mulai'], 0, 5);
                        $js = substr($j['jam_selesai'], 0, 5);
                        $jadwalBpjs[] = [
                            'hari' => $dayNum . '|' . (intval(substr($jm, 0, 2)) >= 14 ? 'sore' : 'pagi'),
                            'buka' => $jm,
                            'tutup' => $js,
                            'kuota' => intval($j['kuota'] ?? 0)
                        ];
                    }
                }
                if (!empty($jadwalBpjs)) {
                    $payload = ['kd_dokter' => $kd_dokter, 'kd_poli' => $kd_poli, 'jadwal' => $jadwalBpjs];
                    $this->postUpdateJadwalHfis(json_encode($payload));
                    $pesanPulihkan = " Jadwal dokter normal berhasil dipulihkan kembali ke server BPJS HFIS.";
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Pengajuan cuti dokter {$cuti['nm_dokter_bpjs']} berhasil DIBATALKAN.{$pesanPulihkan}"
        ]);
        exit();
    }

    public function getLihatHfis()
    {
        $this->_addHeaderFiles();
        $poli = $this->db('maping_poli_bpjs')
            ->select(['kd_poli_bpjs', 'nm_poli_bpjs'])
            ->group('kd_poli_bpjs')
            ->asc('nm_poli_bpjs')
            ->toArray();
        return $this->draw('jadwal.hfis.html', [
            'poli' => htmlspecialchars_array($poli),
            'tgl_default' => date('Y-m-d')
        ]);
    }

    public function anyHfis()
    {
        header('Content-Type: application/json');
        $kodepoli = $_POST['poli'] ?? '';
        $tanggal = $_POST['tgl'] ?? date('Y-m-d');

        if (empty($kodepoli)) {
            echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Poliklinik BPJS belum dipilih.']);
            exit();
        }

        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Kredensial BPJS Antrean (ConsID / SecretKey / URL) belum diisi di Pengaturan JKN Mobile.']);
            exit();
        }

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid . $this->secretkey . $tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Jakarta');

        $url = $this->bpjsurl . 'jadwaldokter/kodepoli/' . $kodepoli . '/tanggal/' . $tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);

        $code = $json['metadata']['code'] ?? 500;
        $message = $json['metadata']['message'] ?? ($output ?: 'Tidak ada balasan dari server BPJS');
        $data = [];

        if ($code == '200' && !empty($json['response'])) {
            $stringDecrypt = stringDecrypt($key, $json['response']);
            if (!empty($stringDecrypt)) {
                $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
                $data = json_decode($decompress, true);
                if (!is_array($data)) {
                    $data = [];
                }
            }

            // Hitung kuota dan ambil daftar pasien yang sudah terdaftar di BPJS & SIMRS
            if (is_array($data) && count($data) > 0) {
                try {
                    $mapPoli = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kodepoli)->toArray();
                    $list_kd_poli_rs = array_values(array_filter(array_column($mapPoli, 'kd_poli_rs')));

                    // 1. Tarik antrean resmi yang sudah terdaftar di Server BPJS untuk tanggal tersebut
                    $bpjs_antrean_by_dokter = [];
                    try {
                        $urlAntrean = $this->bpjsurl . 'antrean/pendaftaran/tanggal/' . $tanggal;
                        $outAntrean = BpjsService::get($urlAntrean, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                        $jsonAntrean = json_decode($outAntrean, true);
                        if (!empty($jsonAntrean['response'])) {
                            $decAntrean = stringDecrypt($key, $jsonAntrean['response']);
                            if (!empty($decAntrean)) {
                                $decompAntrean = \LZCompressor\LZString::decompressFromEncodedURIComponent($decAntrean);
                                $listAntrean = json_decode($decompAntrean, true);
                                if (is_array($listAntrean)) {
                                    foreach ($listAntrean as $ba) {
                                        $st = strtolower($ba['status'] ?? '');
                                        if ($st === 'batal' || $st === 'batal antrean' || $st === 'batal_antrean') {
                                            continue;
                                        }
                                        $kd_dok = (string)($ba['kodedokter'] ?? '');
                                        if ($kd_dok !== '') {
                                            $bpjs_antrean_by_dokter[$kd_dok][] = $ba;
                                            $kd_dok_trim = ltrim($kd_dok, '0');
                                            if ($kd_dok_trim !== '' && $kd_dok_trim !== $kd_dok) {
                                                $bpjs_antrean_by_dokter[$kd_dok_trim][] = $ba;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $eAntrean) {
                        error_log("bridging_hfis anyHfis fetch antrean BPJS error: " . $eAntrean->getMessage());
                    }

                    foreach ($data as &$item) {
                        $kd_dokter_bpjs = (string)($item['kodedokter'] ?? '');
                        $nm_dokter_hfis = $item['namadokter'] ?? '';

                        // Pencarian mapping dokter dengan beberapa skema fallback
                        $mapDokter = null;
                        $kd_dokter_rs = null;

                        // Skema 1: Kode BPJS persis
                        if ($kd_dokter_bpjs !== '') {
                            $mapDokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter_bpjs)->oneArray();
                        }
                        // Skema 2: Kode BPJS tanpa leading zero
                        if (!$mapDokter && $kd_dokter_bpjs !== '') {
                            $kd_dok_trim = ltrim($kd_dokter_bpjs, '0');
                            if ($kd_dok_trim !== '' && $kd_dok_trim !== $kd_dokter_bpjs) {
                                $mapDokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dok_trim)->oneArray();
                            }
                        }
                        // Skema 3: Kode BPJS cocok dengan kd_dokter RS
                        if (!$mapDokter && $kd_dokter_bpjs !== '') {
                            $mapDokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $kd_dokter_bpjs)->oneArray();
                        }
                        // Skema 4: Pencocokan nama dokter jika kode belum terpetakan
                        if (!$mapDokter && !empty($nm_dokter_hfis)) {
                            $clean = preg_replace('/^(drg\.|dr\.|prof\.|drg\s+|dr\s+)/i', '', $nm_dokter_hfis);
                            $clean = preg_replace('/,.*$/', '', $clean);
                            $words = array_values(array_filter(preg_split('/[\s,\.]+/', $clean), function($w) {
                                return strlen($w) >= 3 && !in_array(strtoupper($w), ['SP', 'KG', 'BM', 'PROS', 'PERIO', 'KGA', 'ORT', 'RAD', 'KES', 'MARS', 'DRG', 'DR']);
                            }));
                            if (!empty($words)) {
                                $q = $this->db('maping_dokter_dpjpvclaim');
                                foreach ($words as $w) {
                                    $q->like('nm_dokter_bpjs', '%' . $w . '%');
                                }
                                $mapDokter = $q->oneArray();

                                if (!$mapDokter) {
                                    $q2 = $this->db('dokter');
                                    foreach ($words as $w) {
                                        $q2->like('nm_dokter', '%' . $w . '%');
                                    }
                                    $dRow = $q2->oneArray();
                                    if ($dRow) {
                                        $kd_dokter_rs = $dRow['kd_dokter'];
                                    }
                                }
                            }
                        }

                        if ($mapDokter) {
                            $kd_dokter_rs = $mapDokter['kd_dokter'];
                        }

                        $item['kd_dokter_rs'] = $kd_dokter_rs;
                        $item['kd_poli_rs'] = !empty($list_kd_poli_rs) ? $list_kd_poli_rs[0] : '';
                        $item['nm_dokter_rs'] = $mapDokter ? $mapDokter['nm_dokter_bpjs'] : ($nm_dokter_hfis ?: '');

                        $pasien_final = [];
                        $tracked_rms = [];
                        $tracked_bookings = [];

                        // 1. Ambil dari server BPJS Antrean jika ada antrean terdaftar untuk dokter ini
                        $candidates_bpjs = [];
                        if (isset($bpjs_antrean_by_dokter[$kd_dokter_bpjs])) {
                            $candidates_bpjs = array_merge($candidates_bpjs, $bpjs_antrean_by_dokter[$kd_dokter_bpjs]);
                        }
                        $kd_dok_trim = ltrim($kd_dokter_bpjs, '0');
                        if ($kd_dok_trim !== '' && isset($bpjs_antrean_by_dokter[$kd_dok_trim])) {
                            $candidates_bpjs = array_merge($candidates_bpjs, $bpjs_antrean_by_dokter[$kd_dok_trim]);
                        }

                        $unique_bpjs = [];
                        foreach ($candidates_bpjs as $cb) {
                            $kb = $cb['kodebooking'] ?? '';
                            if ($kb !== '' && !isset($unique_bpjs[$kb])) {
                                $unique_bpjs[$kb] = $cb;
                            }
                        }

                        foreach ($unique_bpjs as $kb => $ba) {
                            $norm = $ba['norekammedis'] ?? '';
                            $noka = $ba['nokapst'] ?? '';
                            $nik = $ba['nik'] ?? '';
                            $pName = '';

                            if (!empty($norm)) {
                                $pRow = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
                                if ($pRow) {
                                    $pName = $pRow['nm_pasien'];
                                }
                            }
                            if (empty($pName) && !empty($noka)) {
                                $pRow = $this->db('pasien')->where('no_peserta', $noka)->oneArray();
                                if ($pRow) {
                                    $pName = $pRow['nm_pasien'];
                                    if (empty($norm)) $norm = $pRow['no_rkm_medis'];
                                }
                            }
                            if (empty($pName) && !empty($nik)) {
                                $pRow = $this->db('pasien')->where('no_ktp', $nik)->oneArray();
                                if ($pRow) {
                                    $pName = $pRow['nm_pasien'];
                                    if (empty($norm)) $norm = $pRow['no_rkm_medis'];
                                }
                            }
                            if (empty($pName)) {
                                $pName = !empty($ba['namapasien']) ? $ba['namapasien'] : ('Peserta BPJS (' . ($noka ?: ($norm ?: $kb)) . ')');
                            }

                            $no_reg = !empty($ba['noantrean']) ? $ba['noantrean'] : (!empty($ba['angkaantrean']) ? $ba['angkaantrean'] : $kb);
                            $raw_jam = !empty($ba['estimasidilayani']) ? $ba['estimasidilayani'] : (!empty($ba['jampraktek']) ? $ba['jampraktek'] : '-');
                            $jam = $raw_jam;
                            if (is_numeric($raw_jam) && strlen((string)$raw_jam) >= 12) {
                                $jam = date('H:i', intval($raw_jam / 1000));
                            }

                            // Tentukan status pasien baru vs lama
                            $is_baru = false;
                            if (empty($norm) || $norm === '-') {
                                $is_baru = true;
                            } elseif (isset($ba['pasienbaru']) && ($ba['pasienbaru'] == 1 || $ba['pasienbaru'] === '1')) {
                                $is_baru = true;
                            } else {
                                $cntPrev = $this->db('reg_periksa')->where('no_rkm_medis', $norm)->where('tgl_registrasi', '<', $tanggal)->count();
                                $is_baru = ($cntPrev == 0);
                            }

                            // Cek apakah sudah hadir di SIMRS pada tanggal ini
                            $is_hadir = false;
                            $jam_hadir = '-';
                            $status_label = $ba['status'] ?? 'Belum dilayani';
                            $no_rawat = '-';

                            if (!empty($norm) && $norm !== '-') {
                                $regCek = $this->db('reg_periksa')->where('no_rkm_medis', $norm)->where('tgl_registrasi', $tanggal)->oneArray();
                                if ($regCek) {
                                    $is_hadir = true;
                                    $jam_hadir = substr($regCek['jam_reg'], 0, 5);
                                    $no_rawat = $regCek['no_rawat'];
                                    if ($regCek['stts'] === 'Sudah') {
                                        $status_label = 'Selesai Dilayani';
                                    } elseif ($regCek['stts'] === 'Batal') {
                                        $status_label = 'Batal';
                                    } else {
                                        $status_label = 'Hadir (Check-In)';
                                    }
                                }
                            }

                            if (!$is_hadir && !empty($kb)) {
                                $t3 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kb)->where('taskid', 3)->oneArray();
                                if ($t3 && !empty($t3['waktu'])) {
                                    $is_hadir = true;
                                    $jam_hadir = date('H:i', intval($t3['waktu'] / 1000));
                                    $status_label = 'Hadir (Check-In)';
                                }
                            }

                            $pasien_final[] = [
                                'no_reg' => $no_reg,
                                'no_rawat' => $no_rawat,
                                'no_rkm_medis' => $norm ?: '-',
                                'nm_pasien' => $pName,
                                'png_jawab' => 'BPJS Kesehatan',
                                'jam' => $jam,
                                'raw_jam' => $raw_jam,
                                'status' => $status_label,
                                'is_hadir' => $is_hadir,
                                'jam_hadir' => $jam_hadir,
                                'is_pasien_baru' => $is_baru,
                                'tipe' => $is_hadir ? 'Registrasi Hari H (Hadir)' : 'Mobile JKN (BPJS)',
                                'sumber' => !empty($ba['sumberdata']) ? $ba['sumberdata'] : 'Mobile JKN (BPJS)',
                                'kodebooking' => $kb,
                                'kd_dokter' => $kd_dokter_rs,
                                'kd_poli' => !empty($list_kd_poli_rs) ? $list_kd_poli_rs[0] : ''
                            ];

                            $tracked_bookings[$kb] = true;
                            if (!empty($norm) && $norm !== '-') {
                                $tracked_rms[$norm] = true;
                            }
                        }

                        // 2. Ambil dari booking_registrasi SIMRS (Mobile JKN ke pasien_final, SIMRS Online/Surat Kontrol dipisah ke pasien_simrs_online)
                        $pasien_simrs_online = [];
                        if ($kd_dokter_rs) {
                            $qBook = $this->db('booking_registrasi')
                                ->leftJoin('pasien', 'pasien.no_rkm_medis = booking_registrasi.no_rkm_medis')
                                ->leftJoin('penjab', 'penjab.kd_pj = booking_registrasi.kd_pj')
                                ->select([
                                    'no_reg' => 'booking_registrasi.no_reg',
                                    'no_rawat' => "'-'",
                                    'no_rkm_medis' => 'booking_registrasi.no_rkm_medis',
                                    'nm_pasien' => 'pasien.nm_pasien',
                                    'no_peserta' => 'pasien.no_peserta',
                                    'png_jawab' => 'penjab.png_jawab',
                                    'jam' => 'booking_registrasi.jam_booking',
                                    'status' => 'booking_registrasi.status',
                                    'kd_poli' => 'booking_registrasi.kd_poli'
                                ])
                                ->where('booking_registrasi.tanggal_periksa', $tanggal)
                                ->where('booking_registrasi.kd_dokter', $kd_dokter_rs)
                                ->where('booking_registrasi.status', '!=', 'Batal');

                            if (!empty($list_kd_poli_rs)) {
                                $qBook->in('booking_registrasi.kd_poli', array_merge($list_kd_poli_rs, [$kodepoli]));
                            }
                            $pasien_booking = $qBook->asc('booking_registrasi.no_reg')->toArray();

                            foreach ($pasien_booking as $pb) {
                                $rm = $pb['no_rkm_medis'] ?? '';
                                if (!empty($rm) && isset($tracked_rms[$rm])) {
                                    continue;
                                }

                                $ref = $this->db('mlite_antrian_referensi')
                                    ->where('no_rkm_medis', $rm)
                                    ->where('tanggal_periksa', $tanggal)
                                    ->oneArray();
                                if (!$ref && !empty($pb['no_peserta']) && $pb['no_peserta'] !== '0') {
                                    $ref = $this->db('mlite_antrian_referensi')
                                        ->where('nomor_kartu', $pb['no_peserta'])
                                        ->where('tanggal_periksa', $tanggal)
                                        ->oneArray();
                                }

                                // Lewati jika referensi BPJS sudah berstatus Batal
                                if ($ref && ($ref['status_kirim'] ?? '') === 'Batal') {
                                    continue;
                                }

                                $kb = $ref['kodebooking'] ?? '';
                                if (!empty($kb) && isset($tracked_bookings[$kb])) {
                                    continue;
                                }

                                $cntPrev = $this->db('reg_periksa')->where('no_rkm_medis', $rm)->where('tgl_registrasi', '<', $tanggal)->count();
                                $is_baru = ($cntPrev == 0);

                                $raw_jam = $pb['jam'] ?? '-';
                                $jam = $raw_jam;
                                if (is_numeric($raw_jam) && strlen((string)$raw_jam) >= 12) {
                                    $jam = date('H:i', intval($raw_jam / 1000));
                                }

                                $is_hadir = ($pb['status'] === 'Terdaftar');
                                $jam_hadir = $is_hadir ? substr($pb['jam'] ?? '', 0, 5) : '-';

                                $pb['tipe'] = $ref ? 'Mobile JKN (BPJS)' : 'Booking Online';
                                $pb['sumber'] = $ref ? 'Mobile JKN (BPJS)' : 'SIMRS Online';
                                $pb['kodebooking'] = $kb;
                                $pb['png_jawab'] = $pb['png_jawab'] ?: 'Umum / BPJS';
                                $pb['nm_pasien'] = $pb['nm_pasien'] ?: ('Pasien RM ' . $rm);
                                $pb['jam'] = $jam;
                                $pb['raw_jam'] = $raw_jam;
                                $pb['is_hadir'] = $is_hadir;
                                $pb['jam_hadir'] = $jam_hadir;
                                $pb['status'] = $is_hadir ? 'Hadir (Check-In)' : 'Belum Datang';
                                $pb['is_pasien_baru'] = $is_baru;
                                $pb['kd_dokter'] = $kd_dokter_rs;
                                $pb['kd_poli'] = !empty($list_kd_poli_rs) ? $list_kd_poli_rs[0] : '';

                                if ($ref) {
                                    $pasien_final[] = $pb;
                                    if (!empty($rm)) $tracked_rms[$rm] = true;
                                    if (!empty($kb)) $tracked_bookings[$kb] = true;
                                } else {
                                    $pasien_simrs_online[] = $pb;
                                }
                            }
                        }

                        // 3. Ambil dari reg_periksa (pasien hari H / yang sudah datang registrasi)
                        if ($kd_dokter_rs) {
                            $qReg = $this->db('reg_periksa')
                                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                                ->leftJoin('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
                                ->select([
                                    'no_reg' => 'reg_periksa.no_reg',
                                    'no_rawat' => 'reg_periksa.no_rawat',
                                    'no_rkm_medis' => 'reg_periksa.no_rkm_medis',
                                    'nm_pasien' => 'pasien.nm_pasien',
                                    'png_jawab' => 'penjab.png_jawab',
                                    'jam' => 'reg_periksa.jam_reg',
                                    'status' => 'reg_periksa.stts'
                                ])
                                ->where('reg_periksa.tgl_registrasi', $tanggal)
                                ->where('reg_periksa.kd_dokter', $kd_dokter_rs)
                                ->where('reg_periksa.stts', '!=', 'Batal');

                            if (!empty($list_kd_poli_rs)) {
                                $qReg->in('reg_periksa.kd_poli', $list_kd_poli_rs);
                            }
                            $pasien_reg = $qReg->asc('reg_periksa.no_reg')->toArray();

                            foreach ($pasien_reg as $pr) {
                                $rm = $pr['no_rkm_medis'] ?? '';
                                $jam_hadir = substr($pr['jam'], 0, 5);
                                $sttsLabel = 'Hadir (Check-In)';
                                if ($pr['status'] === 'Sudah') {
                                    $sttsLabel = 'Selesai Dilayani';
                                } elseif ($pr['status'] === 'Batal') {
                                    $sttsLabel = 'Batal';
                                }

                                if (!empty($rm) && isset($tracked_rms[$rm])) {
                                    foreach ($pasien_final as &$pf) {
                                        if (($pf['no_rkm_medis'] ?? '') === $rm) {
                                            $pf['no_rawat'] = $pr['no_rawat'];
                                            $pf['no_reg'] = $pr['no_reg'];
                                            $pf['jam_hadir'] = $jam_hadir;
                                            $pf['status'] = $sttsLabel;
                                            $pf['is_hadir'] = true;
                                            if (($pf['tipe'] ?? '') !== 'Mobile JKN (BPJS)' && strpos($pf['sumber'] ?? '', 'Mobile JKN') === false) {
                                                $pf['tipe'] = 'Registrasi Hari H (Hadir)';
                                            }
                                            break;
                                        }
                                    }
                                } else {
                                    $cntPrev = $this->db('reg_periksa')->where('no_rkm_medis', $rm)->where('tgl_registrasi', '<', $tanggal)->count();
                                    $is_baru = ($cntPrev == 0);

                                    $pr['tipe'] = 'Registrasi Hari H (Hadir)';
                                    $pr['sumber'] = 'Loket / Mandiri RS';
                                    $pr['kodebooking'] = '';
                                    $pr['png_jawab'] = $pr['png_jawab'] ?: 'Umum / BPJS';
                                    $pr['nm_pasien'] = $pr['nm_pasien'] ?: ('Pasien RM ' . $rm);
                                    $pr['is_hadir'] = true;
                                    $pr['jam_hadir'] = $jam_hadir;
                                    $pr['status'] = $sttsLabel;
                                    $pr['is_pasien_baru'] = $is_baru;
                                    $pr['kd_dokter'] = $kd_dokter_rs;
                                    $pr['kd_poli'] = !empty($list_kd_poli_rs) ? $list_kd_poli_rs[0] : '';
                                    $pasien_final[] = $pr;
                                    if (!empty($rm)) $tracked_rms[$rm] = true;
                                }
                            }
                        }

                        // Filter pasien_simrs_online agar pasien yang sudah hadir di reg_periksa tidak tampil ganda
                        if (!empty($pasien_simrs_online)) {
                            $pasien_simrs_online = array_values(array_filter($pasien_simrs_online, function ($so) use ($tracked_rms) {
                                $srm = $so['no_rkm_medis'] ?? '';
                                return ($srm === '' || !isset($tracked_rms[$srm]));
                            }));
                        }

                        $kuota_bpjs = intval($item['kapasitaspasien'] ?? 0);
                        $terdaftar = count($pasien_final);
                        $sisa_kuota = max(0, $kuota_bpjs - $terdaftar);

                        $item['kuota_bpjs'] = $kuota_bpjs;
                        $item['terdaftar'] = $terdaftar;
                        $item['sisa_kuota'] = $sisa_kuota;
                        $item['pasien_list'] = $pasien_final;
                        $item['pasien_simrs_online'] = $pasien_simrs_online;
                    }
                } catch (\Throwable $e) {
                    // Fallback jika query lokal gagal, tetap tampilkan jadwal BPJS
                    foreach ($data as &$item) {
                        $kuota_bpjs = intval($item['kapasitaspasien'] ?? 0);
                        $item['kuota_bpjs'] = $kuota_bpjs;
                        $item['terdaftar'] = 0;
                        $item['sisa_kuota'] = $kuota_bpjs;
                        $item['pasien_list'] = [];
                    }
                }
            }
        }

        echo json_encode([
            'status' => ($code == '200') ? 'success' : 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'tanggal' => $tanggal,
            'kodepoli' => $kodepoli
        ]);
        exit();
    }

    public function postTandaiHadirPasien()
    {
        header('Content-Type: application/json');
        $kodebooking = trim($_POST['kodebooking'] ?? '');
        $norm = trim($_POST['no_rkm_medis'] ?? '');
        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $kd_dokter = trim($_POST['kd_dokter'] ?? '');
        $kd_poli = trim($_POST['kd_poli'] ?? '');
        $no_reg = trim($_POST['no_reg'] ?? '1');

        if (empty($norm) || $norm === '-') {
            if (!empty($kodebooking)) {
                $ref = $this->db('mlite_antrian_referensi')->where('kodebooking', $kodebooking)->oneArray();
                if ($ref && !empty($ref['no_rkm_medis'])) {
                    $norm = $ref['no_rkm_medis'];
                }
            }
        }

        if (empty($norm) || $norm === '-') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Pasien belum memiliki No. Rekam Medis (RM) di SIMRS. Harap daftarkan identitas pasien baru terlebih dahulu di loket admisi pendaftaran.'
            ]);
            exit();
        }

        $pasien = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
        if (!$pasien) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data pasien dengan No. RM ' . $norm . ' tidak ditemukan di database SIMRS.'
            ]);
            exit();
        }

        $now_time = date('H:i:s');
        $start_time = date('H:i:s', strtotime('-3 minutes'));

        // Cek apakah sudah ada di reg_periksa
        $reg = $this->db('reg_periksa')
            ->where('no_rkm_medis', $norm)
            ->where('tgl_registrasi', $tanggal)
            ->oneArray();

        if (!$reg) {
            $booking = $this->db('booking_registrasi')
                ->where('no_rkm_medis', $norm)
                ->where('tanggal_periksa', $tanggal)
                ->oneArray();

            if ($booking) {
                if (empty($kd_dokter)) $kd_dokter = $booking['kd_dokter'];
                if (empty($kd_poli)) $kd_poli = $booking['kd_poli'];
                if (empty($no_reg) || $no_reg === '1') $no_reg = $booking['no_reg'];
            }

            $tglLahirStr = $pasien['tgl_lahir'] ?? '';
            $umur = "0";
            $sttsumur = "Th";
            if (!empty($tglLahirStr) && $tglLahirStr !== '0000-00-00') {
                $diff = (new \DateTime(date('Y-m-d')))->diff(new \DateTime($tglLahirStr));
                if ($diff->y > 0) { $umur = (string)$diff->y; $sttsumur = "Th"; }
                elseif ($diff->m > 0) { $umur = (string)$diff->m; $sttsumur = "Bl"; }
                else { $umur = (string)$diff->d; $sttsumur = "Hr"; }
            }

            $cntPrev = $this->db('reg_periksa')->where('no_rkm_medis', $norm)->where('tgl_registrasi', '<', $tanggal)->count();
            $stts_daftar = ($cntPrev == 0) ? 'Baru' : 'Lama';

            $no_rawat = $this->core->setNoRawat($tanggal);

            $this->db('reg_periksa')->save([
                'no_reg' => $no_reg,
                'no_rawat' => $no_rawat,
                'tgl_registrasi' => $tanggal,
                'jam_reg' => $now_time,
                'kd_dokter' => $kd_dokter,
                'no_rkm_medis' => $norm,
                'kd_poli' => $kd_poli,
                'p_jawab' => $pasien['namakeluarga'] ?? '-',
                'almt_pj' => $pasien['alamatpj'] ?? '-',
                'hubunganpj' => $pasien['keluarga'] ?? '-',
                'biaya_reg' => 0,
                'stts' => 'Belum',
                'stts_daftar' => $stts_daftar,
                'status_lanjut' => 'Ralan',
                'kd_pj' => $booking['kd_pj'] ?? ($pasien['kd_pj'] ?? 'BPJ'),
                'umurdaftar' => $umur,
                'sttsumur' => $sttsumur,
                'status_bayar' => 'Belum Bayar',
                'status_poli' => 'Lama'
            ]);

            if ($booking) {
                $this->db('booking_registrasi')
                    ->where('no_rkm_medis', $norm)
                    ->where('tanggal_periksa', $tanggal)
                    ->update(['status' => 'Terdaftar']);
            }
        } else {
            $no_reg = $reg['no_reg'];
        }

        // Catat kehadiran di mlite_antrian_loket dengan type Poli
        $cekLok = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $norm)->where('postdate', $tanggal)->oneArray();
        if (!$cekLok) {
            $this->db('mlite_antrian_loket')->save([
                'type' => 'Poli',
                'noantrian' => $no_reg,
                'postdate' => $tanggal,
                'start_time' => $start_time,
                'end_time' => $now_time,
                'no_rkm_medis' => $norm,
                'status' => 2
            ]);
        } else {
            $this->db('mlite_antrian_loket')->where('kd', $cekLok['kd'])->save([
                'end_time' => $now_time,
                'status' => 2
            ]);
        }

        // Simpan Task ID 1, 2, 3 jika ada kodebooking
        if (!empty($kodebooking)) {
            $nowMs = time() * 1000;
            $startMs = (time() - 180) * 1000;
            if (!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kodebooking)->where('taskid', 1)->oneArray()) {
                $this->db('mlite_antrian_referensi_taskid')->save([
                    'tanggal_periksa' => $tanggal,
                    'nomor_referensi' => $kodebooking,
                    'taskid' => 1,
                    'waktu' => $startMs,
                    'status' => 'Belum',
                    'keterangan' => ''
                ]);
            }
            if (!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kodebooking)->where('taskid', 2)->oneArray()) {
                $this->db('mlite_antrian_referensi_taskid')->save([
                    'tanggal_periksa' => $tanggal,
                    'nomor_referensi' => $kodebooking,
                    'taskid' => 2,
                    'waktu' => $nowMs,
                    'status' => 'Belum',
                    'keterangan' => ''
                ]);
            }
            if (!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kodebooking)->where('taskid', 3)->oneArray()) {
                $this->db('mlite_antrian_referensi_taskid')->save([
                    'tanggal_periksa' => $tanggal,
                    'nomor_referensi' => $kodebooking,
                    'taskid' => 3,
                    'waktu' => $nowMs,
                    'status' => 'Belum',
                    'keterangan' => ''
                ]);
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Pasien ' . $pasien['nm_pasien'] . ' (RM: ' . $norm . ') berhasil ditandai Hadir / Check-In pada pukul ' . substr($now_time, 0, 5) . '!',
            'jam_hadir' => substr($now_time, 0, 5)
        ]);
        exit();
    }

    public function postSalinJadwalHfis()
    {
        header('Content-Type: application/json');
        $kd_dokter_bpjs = $_POST['kodedokter'] ?? '';
        $kd_poli_bpjs = $_POST['kodepoli'] ?? '';
        $hari_bpjs = strtoupper(trim($_POST['hari'] ?? ''));
        $jadwal_jam = $_POST['jadwal'] ?? '';
        $kuota = intval($_POST['kuota'] ?? 0);

        $mapDokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter_bpjs)->oneArray();
        $mapPoli = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli_bpjs)->oneArray();

        if (!$mapDokter || !$mapPoli) {
            echo json_encode(['status' => 'error', 'message' => 'Mapping Dokter atau Poli tidak ditemukan di basis data SIMRS.']);
            exit();
        }

        $kd_dokter = $mapDokter['kd_dokter'];
        $kd_poli = $mapPoli['kd_poli_rs'];

        // Parse format e.g. "10:00-12:00"
        $jam_mulai = '08:00:00';
        $jam_selesai = '14:00:00';
        if (strpos($jadwal_jam, '-') !== false) {
            $parts = explode('-', $jadwal_jam, 2);
            $jm = trim($parts[0]);
            $js = trim($parts[1]);
            $jam_mulai = $jm . (strlen($jm) == 5 ? ':00' : '');
            $jam_selesai = $js . (strlen($js) == 5 ? ':00' : '');
        }

        $check = $this->db('jadwal')
            ->where('kd_dokter', $kd_dokter)
            ->where('hari_kerja', $hari_bpjs)
            ->where('kd_poli', $kd_poli)
            ->oneArray();

        if ($check) {
            $this->db('jadwal')
                ->where('kd_dokter', $kd_dokter)
                ->where('hari_kerja', $hari_bpjs)
                ->where('kd_poli', $kd_poli)
                ->update([
                    'jam_mulai' => $jam_mulai,
                    'jam_selesai' => $jam_selesai,
                    'kuota' => $kuota
                ]);
        } else {
            $this->db('jadwal')->save([
                'kd_dokter' => $kd_dokter,
                'hari_kerja' => $hari_bpjs,
                'kd_poli' => $kd_poli,
                'jam_mulai' => $jam_mulai,
                'jam_selesai' => $jam_selesai,
                'kuota' => $kuota
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Jadwal dokter {$mapDokter['nm_dokter_bpjs']} hari {$hari_bpjs} ({$jam_mulai} - {$jam_selesai}, Kuota: {$kuota}) berhasil disalin ke basis data SIMRS lokal!"
        ]);
        exit();
    }

    public function postSalinSemuaJadwalHfis()
    {
        header('Content-Type: application/json');
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        $items = $data['items'] ?? [];
        $kodepoli = $data['kodepoli'] ?? '';

        if (empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada data jadwal untuk disalin.']);
            exit();
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($items as $item) {
            $kd_dokter_bpjs = $item['kodedokter'] ?? '';
            $kd_poli_bpjs = $item['kodepoli'] ?? $kodepoli;
            $hari_bpjs = strtoupper(trim($item['namahari'] ?? $item['hari'] ?? ''));
            $jadwal_jam = $item['jadwal'] ?? '';
            $kuota = intval($item['kapasitaspasien'] ?? $item['kuota'] ?? 0);

            $mapDokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter_bpjs)->oneArray();
            $mapPoli = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kd_poli_bpjs)->oneArray();

            if (!$mapDokter || !$mapPoli) {
                $gagal++;
                continue;
            }

            $kd_dokter = $mapDokter['kd_dokter'];
            $kd_poli = $mapPoli['kd_poli_rs'];

            $jam_mulai = '08:00:00';
            $jam_selesai = '14:00:00';
            if (strpos($jadwal_jam, '-') !== false) {
                $parts = explode('-', $jadwal_jam, 2);
                $jm = trim($parts[0]);
                $js = trim($parts[1]);
                $jam_mulai = $jm . (strlen($jm) == 5 ? ':00' : '');
                $jam_selesai = $js . (strlen($js) == 5 ? ':00' : '');
            }

            $check = $this->db('jadwal')
                ->where('kd_dokter', $kd_dokter)
                ->where('hari_kerja', $hari_bpjs)
                ->where('kd_poli', $kd_poli)
                ->oneArray();

            if ($check) {
                $this->db('jadwal')
                    ->where('kd_dokter', $kd_dokter)
                    ->where('hari_kerja', $hari_bpjs)
                    ->where('kd_poli', $kd_poli)
                    ->update([
                        'jam_mulai' => $jam_mulai,
                        'jam_selesai' => $jam_selesai,
                        'kuota' => $kuota
                    ]);
            } else {
                $this->db('jadwal')->save([
                    'kd_dokter' => $kd_dokter,
                    'hari_kerja' => $hari_bpjs,
                    'kd_poli' => $kd_poli,
                    'jam_mulai' => $jam_mulai,
                    'jam_selesai' => $jam_selesai,
                    'kuota' => $kuota
                ]);
            }
            $berhasil++;
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Berhasil menyalin {$berhasil} jadwal dokter dari BPJS HFIS ke basis data SIMRS lokal!" . ($gagal > 0 ? " ({$gagal} dilewati karena dokter/poli belum di-mapping)." : "")
        ]);
        exit();
    }

    public function getJadwalDokter()
    {
        $this->_addHeaderFiles();
        $poli = $this->db('poliklinik')->where('status', '1')->asc('nm_poli')->toArray();
        $dokter = $this->db('dokter')->where('status', '1')->asc('nm_dokter')->toArray();
        return $this->draw('jadwal.dokter.html', [
            'poli' => htmlspecialchars_array($poli),
            'dokter' => htmlspecialchars_array($dokter)
        ]);
    }

    public function postListJadwalDokter()
    {
        header('Content-Type: application/json');
        $dr = $_POST['dr'] ?? '-';
        $poli = $_POST['poli'] ?? '-';
        $hari = $_POST['hari'] ?? '-';

        $query = $this->db('jadwal')
            ->join('dokter', 'dokter.kd_dokter = jadwal.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli = jadwal.kd_poli');

        if ($dr != '-' && !empty($dr)) {
            $query->where('jadwal.kd_dokter', $dr);
        }
        if ($poli != '-' && !empty($poli)) {
            $query->where('jadwal.kd_poli', $poli);
        }
        if ($hari != '-' && !empty($hari)) {
            $query->where('jadwal.hari_kerja', $hari);
        }

        $rows = $query->asc('poliklinik.nm_poli')->asc('dokter.nm_dokter')->asc('jadwal.hari_kerja')->asc('jadwal.jam_mulai')->toArray();

        echo json_encode([
            'status' => 'success',
            'code' => 200,
            'count' => count($rows),
            'data' => $rows
        ]);
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES . '/bridging_hfis/js/admin/scripts.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
        $this->core->addJS(url([ADMIN, 'bridging_hfis', 'javascript']), 'footer');
    }
}

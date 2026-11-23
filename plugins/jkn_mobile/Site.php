<?php

namespace Plugins\JKN_Mobile;

use Systems\SiteModule;
use Systems\Lib\BpjsService;
use LZCompressor\LZString;

class Site extends SiteModule
{

    protected $consid;
    protected $secretkey;
    protected $bpjsurl;
    protected $user_key;

    public function init()
    {
        $this->consid = $this->settings->get('jkn_mobile.BpjsConsID');
        $this->secretkey = $this->settings->get('jkn_mobile.BpjsSecretKey');
        $this->bpjsurl = $this->settings->get('jkn_mobile.BpjsAntrianUrl');
        $this->user_key = $this->settings->get('jkn_mobile.BpjsUserKey');
    }

    public function routes()
    {

        $this->route('jknmobile', 'getIndex');
        $this->route('jknmobile/token', 'getToken');
        $this->route('jknmobile/antrian/ambil', 'getAmbilAntrian');
        $this->route('jknmobile/antrian/status', 'getStatusAntrian');
        $this->route('jknmobile/antrian/ambilfarmasi', 'getAmbilAntrianFarmasi');
        $this->route('jknmobile/antrian/statusfarmasi', 'getStatusAntrianFarmasi');
        $this->route('jknmobile/antrian/sisa', 'getSisaAntrian');
        $this->route('jknmobile/antrian/batal', 'getBatalAntrian');
        $this->route('jknmobile/antrian/checkin', 'getPasienCheckIn');
        $this->route('jknmobile/pasien/baru', 'getPasienBaru');
        $this->route('jknmobile/operasi/rs', 'getOperasiRS');
        $this->route('jknmobile/operasi/pasien', 'getOperasiPasien');
        $this->route('jknmobile/antrian/add', '_getAntreanAdd');
        $this->route('jknmobile/antrian/add/(:str)', '_getAntreanAdd');
        $this->route('jknmobile/antrian-alt/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile/antrian-ausy/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile/antrian-kci/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile/antrian/updatewaktu', '_getAntreanUpdateWaktu');
        $this->route('jknmobile/antrian/updatewaktu/(:str)', '_getAntreanUpdateWaktu');
        $this->route('jknmobile/antrian-ausy/updatewaktu/(:str)', '_getAntreanUpdateWaktuAusy');
        $this->route('jknmobile/antrian-kci/updatewaktu/(:str)', '_getAntreanUpdateWaktuAusy');
        $this->route('jknmobile/antrian/waktutunggu/(:str)/(:str)/(:str)', '_getAntreanWaktuTunggu');
        $this->route('jknmobile/antrian/tanggaltunggu/(:str)/(:str)', '_getAntreanWaktuTungguTanggal');
        $this->route('jknmobile/antrian/listtask/(:str)', '_getAntreanGetListTask');
        $this->route('jknmobile/jadwal/(:str)/(:str)', '_getJadwal');

        $this->route('jknmobile/aplicare', 'getAplicareManajemen');
        $this->route('jknmobile/aplicare/(:str)', 'getAplicare');
        $this->route('jknmobile/aplicare/(:str)/(:str)', 'getAplicare');

        /* Start Old school routing */
        $this->route('jknmobile_v2', 'getIndex');
        $this->route('jknmobile_v2/token', 'getToken');
        $this->route('jknmobile_v2/antrian/ambil', 'getAmbilAntrian');
        $this->route('jknmobile_v2/antrian/status', 'getStatusAntrian');
        $this->route('jknmobile_v2/antrian/ambilfarmasi', 'getAmbilAntrianFarmasi');
        $this->route('jknmobile_v2/antrian/statusfarmasi', 'getStatusAntrianFarmasi');
        $this->route('jknmobile_v2/antrian/sisa', 'getSisaAntrian');
        $this->route('jknmobile_v2/antrian/batal', 'getBatalAntrian');
        $this->route('jknmobile_v2/pasien/baru', 'getPasienBaru');
        $this->route('jknmobile_v2/pasien/checkin', 'getPasienCheckIn');
        $this->route('jknmobile_v2/operasi/rs', 'getOperasiRS');
        $this->route('jknmobile_v2/operasi/pasien', 'getOperasiPasien');
        $this->route('jknmobile_v2/antrian/add', '_getAntreanAdd');
        $this->route('jknmobile_v2/antrian/add/(:str)', '_getAntreanAdd');
        $this->route('jknmobile_v2/antrian-alt/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile_v2/antrian-ausy/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile_v2/antrian-kci/add/(:str)', '_getAntreanAddAusy');
        $this->route('jknmobile_v2/antrian/updatewaktu', '_getAntreanUpdateWaktu');
        $this->route('jknmobile_v2/antrian/updatewaktu/(:str)', '_getAntreanUpdateWaktu');
        $this->route('jknmobile_v2/antrian-ausy/updatewaktu/(:str)', '_getAntreanUpdateWaktuAusy');
        $this->route('jknmobile_v2/antrian-kci/updatewaktu/(:str)', '_getAntreanUpdateWaktuAusy');
        $this->route('jknmobile_v2/antrian/waktutunggu/(:str)/(:str)/(:str)', '_getAntreanWaktuTunggu');
        $this->route('jknmobile_v2/antrian/tanggaltunggu/(:str)/(:str)', '_getAntreanWaktuTungguTanggal');
        $this->route('jknmobile_v2/antrian/listtask/(:str)', '_getAntreanGetListTask');
        $this->route('jknmobile_v2/jadwal/(:str)/(:str)', '_getJadwal');
        $this->route('jknmobile_v2/antrian/pertanggal/(:str)', 'getListAntrol');

        $this->route('jknmobile_v2/aplicare', 'getAplicareManajemen');
        $this->route('jknmobile_v2/aplicare/(:str)', 'getAplicare');
        $this->route('jknmobile_v2/aplicare/(:str)/(:str)', 'getAplicare');
        /* End Old school routing */

    }

    public function getIndex()
    {
        $referensi_poli = $this->db('maping_poli_bpjs')->toArray();
        $kelas = [];
        $sql = "SELECT kamar.kelas, kamar.kd_bangsal FROM kamar JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal WHERE kamar.statusdata = '1'";
        // $sql .= " AND kamar.kelas = 'Kelas $kelas' AND kamar.kd_bangsal LIKE '%$bangsal%'";
        $sql .= " GROUP BY kamar.kd_bangsal, kamar.kelas ";
        $query = $this->db()->pdo()->prepare($sql);
        $query->execute();
        $bedlist = $query->fetchAll();
        foreach ($bedlist as $value) {
          $kelas[] = $value;
        }

        echo $this->draw('index.html', ['referensi_poli' => $referensi_poli, 'kelas' => $kelas]);
        exit();
    }

    private function _getRequestHeader($name)
    {
        $target = strtolower(trim((string)$name));
        if ($target === '') {
            return null;
        }
        $headers = [];
        if (function_exists('apache_request_headers')) {
            $raw = apache_request_headers();
            if (is_array($raw)) {
                $headers = $raw;
            }
        } elseif (function_exists('getallheaders')) {
            $raw = getallheaders();
            if (is_array($raw)) {
                $headers = $raw;
            }
        }
        foreach ($headers as $k => $v) {
            if (strtolower(trim((string)$k)) === $target) {
                return $v;
            }
        }
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $target));
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }
        return null;
    }

    private function _jsonResponse(array $response, $httpCode = 200)
    {
        if (!headers_sent()) {
            header("Content-Type: application/json; charset=utf-8");
            http_response_code((int)$httpCode);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getToken()
    {
        echo $this->_resultToken();
        exit();
    }

    private function _resultToken()
    {
        header("Content-Type: application/json; charset=utf-8");
        $response = array();

        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $passwordHeader = $this->settings->get('jkn_mobile.header_password');
        $usernameValue = $this->settings->get('jkn_mobile.x_username');
        $passwordValue = $this->settings->get('jkn_mobile.x_password');

        $reqUser = $this->_getRequestHeader($usernameHeader);
        $reqPass = $this->_getRequestHeader($passwordHeader);

        $usernameMatch = ($reqUser !== null && (string)$reqUser === (string)$usernameValue);
        $passwordMatch = ($reqPass !== null && (string)$reqPass === (string)$passwordValue);

        if ($usernameMatch && $passwordMatch) {
            $response = array(
                'response' => array(
                    'token' => $this->_getToken()
                ),
                'metadata' => array(
                    'message' => 'Ok',
                    'code' => 200
                )
            );
            $this->_jsonResponse($response, 200);
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Username atau Password Tidak Sesuai',
                    'code' => 201
                )
            );
            $this->_jsonResponse($response, 201);
        }
    }

    public function getAmbilAntrian()
    {
        echo $this->_resultAmbilAntrian();
        exit();
    }

    private function _resultAmbilAntrian()
    {
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');

        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {

                $nomorkartu = trim((string)($decode['nomorkartu'] ?? ''));
                $nik = trim((string)($decode['nik'] ?? ''));
                $tanggal = trim((string)($decode['tanggalperiksa'] ?? ''));
                $kodepoli = trim((string)($decode['kodepoli'] ?? ''));
                $kodedokter = trim((string)($decode['kodedokter'] ?? ''));
                $nomorreferensi = trim((string)($decode['nomorreferensi'] ?? ''));
                $jeniskunjungan = isset($decode['jeniskunjungan']) ? (int)$decode['jeniskunjungan'] : 1;

                $day = array(
                    'Sun' => 'AKHAD',
                    'Mon' => 'SENIN',
                    'Tue' => 'SELASA',
                    'Wed' => 'RABU',
                    'Thu' => 'KAMIS',
                    'Fri' => 'JUMAT',
                    'Sat' => 'SABTU'
                );
                $validDateFormat = ($tanggal !== '' && preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggal));
                $hari = $validDateFormat ? ($day[date('D', strtotime($tanggal))] ?? '') : '';

                $cek_rujukan = null;
                if ($nomorreferensi !== '') {
                    $cek_rujukan = $this->db('bridging_sep')->where('no_rujukan', $nomorreferensi)->group('tglrujukan')->oneArray();
                }

                $h1 = date('Y-m-d');
                $_h1 = date('d-m-Y', strtotime($h1));
                $h7 = strtotime('+8 days', strtotime($h1));
                if (!empty($cek_rujukan['tglrujukan'])) {
                    $h7 = strtotime('+90 days', strtotime($cek_rujukan['tglrujukan']));
                }
                $h7 = date('Y-m-d', $h7);
                $_h7 = date('d-m-Y', strtotime($h7));

                $data_pasien = ($nomorkartu !== '') ? $this->db('pasien')->where('no_peserta', $nomorkartu)->oneArray() : null;
                $poli = ($kodepoli !== '') ? $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kodepoli)->oneArray() : null;
                $dokter = ($kodedokter !== '') ? $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kodedokter)->oneArray() : null;

                $cek_kuota = false;
                if ($validDateFormat && $kodepoli !== '') {
                    if (strtotime($tanggal) == strtotime(date('Y-m-d'))) {
                        $sql = "SELECT jadwal.kuota - (SELECT COUNT(reg_periksa.tgl_registrasi)
                            FROM reg_periksa WHERE reg_periksa.tgl_registrasi = :tgl
                            AND reg_periksa.kd_dokter = jadwal.kd_dokter AND reg_periksa.kd_poli = jadwal.kd_poli AND reg_periksa.stts != 'Batal') as sisa_kuota,
                            jadwal.kd_dokter, jadwal.kd_poli, jadwal.jam_mulai as jam_mulai, poliklinik.nm_poli, dokter.nm_dokter, jadwal.kuota
                            FROM jadwal
                            INNER JOIN maping_poli_bpjs ON maping_poli_bpjs.kd_poli_rs = jadwal.kd_poli
                            INNER JOIN maping_dokter_dpjpvclaim ON maping_dokter_dpjpvclaim.kd_dokter = jadwal.kd_dokter
                            INNER JOIN poliklinik ON poliklinik.kd_poli = jadwal.kd_poli
                            INNER JOIN dokter ON dokter.kd_dokter = jadwal.kd_dokter
                            WHERE jadwal.hari_kerja = :hari AND maping_poli_bpjs.kd_poli_bpjs = :kdpoli";
                    } else {
                        $sql = "SELECT jadwal.kuota - (SELECT COUNT(booking_registrasi.tanggal_periksa)
                            FROM booking_registrasi WHERE booking_registrasi.tanggal_periksa = :tgl
                            AND booking_registrasi.kd_dokter = jadwal.kd_dokter AND booking_registrasi.kd_poli = jadwal.kd_poli AND booking_registrasi.status != 'Batal') as sisa_kuota,
                            jadwal.kd_dokter, jadwal.kd_poli, jadwal.jam_mulai as jam_mulai, poliklinik.nm_poli, dokter.nm_dokter, jadwal.kuota
                            FROM jadwal
                            INNER JOIN maping_poli_bpjs ON maping_poli_bpjs.kd_poli_rs = jadwal.kd_poli
                            INNER JOIN maping_dokter_dpjpvclaim ON maping_dokter_dpjpvclaim.kd_dokter = jadwal.kd_dokter
                            INNER JOIN poliklinik ON poliklinik.kd_poli = jadwal.kd_poli
                            INNER JOIN dokter ON dokter.kd_dokter = jadwal.kd_dokter
                            WHERE jadwal.hari_kerja = :hari AND maping_poli_bpjs.kd_poli_bpjs = :kdpoli";
                    }

                    $params = [':tgl' => $tanggal, ':hari' => $hari, ':kdpoli' => $kodepoli];
                    if ($kodedokter !== '') {
                        $sql .= " AND maping_dokter_dpjpvclaim.kd_dokter_bpjs = :kddokter";
                        $params[':kddokter'] = $kodedokter;
                    }
                    $sql .= " GROUP BY jadwal.kd_dokter, jadwal.kd_poli, jadwal.jam_mulai, jadwal.kuota, poliklinik.nm_poli, dokter.nm_dokter HAVING sisa_kuota > 0 ORDER BY sisa_kuota DESC LIMIT 1";

                    $stmtKuota = $this->db()->pdo()->prepare($sql);
                    $stmtKuota->execute($params);
                    $cek_kuota = $stmtKuota->fetch(\PDO::FETCH_ASSOC);
                }

                $jadwal = null;
                if ($validDateFormat && $kodepoli !== '') {
                    $jadwal_q = $this->db('jadwal')
                        ->join('maping_dokter_dpjpvclaim', 'maping_dokter_dpjpvclaim.kd_dokter=jadwal.kd_dokter')
                        ->join('maping_poli_bpjs', 'maping_poli_bpjs.kd_poli_rs=jadwal.kd_poli')
                        ->where('maping_poli_bpjs.kd_poli_bpjs', $kodepoli)
                        ->where('hari_kerja', $hari);
                    if ($kodedokter !== '') {
                        $jadwal_q->where('maping_dokter_dpjpvclaim.kd_dokter_bpjs', $kodedokter);
                    }
                    $jadwal = $jadwal_q->oneArray();
                }

                $cek_referensi = ($nomorreferensi !== '' && $tanggal !== '')
                    ? $this->db('mlite_antrian_referensi')->where('nomor_referensi', $nomorreferensi)->where('tanggal_periksa', $tanggal)->oneArray()
                    : null;
                $cek_referensi_noka = ($nomorkartu !== '' && $tanggal !== '')
                    ? $this->db('mlite_antrian_referensi')->where('nomor_kartu', $nomorkartu)->where('tanggal_periksa', $tanggal)->oneArray()
                    : null;

                $errors = [];
                if (!empty($cek_referensi['tanggal_periksa'])) {
                    $errors[] = 'Anda sudah terdaftar dalam antrian menggunakan nomor rujukan yang sama ditanggal ' . $tanggal;
                }
                if (!empty($cek_referensi_noka['tanggal_periksa'])) {
                    $errors[] = 'Anda sudah terdaftar dalam antrian ditanggal ' . $cek_referensi_noka['tanggal_periksa'] . '. Silahkan pilih tanggal lain.';
                }
                if ($nomorkartu === '') {
                    $errors[] = 'Nomor kartu tidak boleh kosong';
                }
                if ($nomorkartu !== '' && mb_strlen($nomorkartu, 'UTF-8') < 13) {
                    $errors[] = 'Nomor kartu harus 13 digit';
                }
                if ($nomorkartu !== '' && !ctype_digit($nomorkartu)) {
                    $errors[] = 'Nomor kartu harus mengandung angka saja!!';
                }
                if ($nik === '') {
                    $errors[] = 'NIK tidak boleh kosong';
                }
                if ($nik !== '' && mb_strlen($nik, 'UTF-8') < 16) {
                    $errors[] = 'Nomor KTP harus 16 digit atau format tidak sesuai';
                }
                if ($nik !== '' && !ctype_digit($nik)) {
                    $errors[] = 'NIK harus mengandung angka saja!!';
                }
                if ($tanggal === '') {
                    $errors[] = 'Anda belum memilih tanggal periksa';
                }
                if ($tanggal !== '' && !$validDateFormat) {
                    $errors[] = 'Format tanggal periksa tidak sesuai';
                }
                if ($validDateFormat && ($tanggal < $h1 || $tanggal > $h7)) {
                    $errors[] = 'Tanggal periksa bisa dilakukan tanggal ' . $_h1 . ' hingga tanggal ' . $_h7;
                }
                if ($kodepoli === '') {
                    $errors[] = 'Kode poli tidak boleh kosong';
                }
                if ($kodepoli !== '' && empty($poli)) {
                    $errors[] = 'Kode poli tidak ditemukan';
                }
                if ($kodedokter === '') {
                    $errors[] = 'Kode dokter tidak boleh kosong';
                }
                if ($kodedokter !== '' && empty($dokter)) {
                    $errors[] = 'Kode dokter tidak ditemukan';
                }
                if (isset($decode['jeniskunjungan']) && ($jeniskunjungan < 1 || $jeniskunjungan > 4)) {
                    $errors[] = 'Jenis kunjungan tidak ditemukan';
                }

                if (!empty($errors)) {
                    $response = array(
                        'metadata' => array(
                            'message' => implode(', ', $errors),
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    if (!empty($cek_kuota) && (int)$cek_kuota['sisa_kuota'] > 0) {
                        if (empty($data_pasien)) {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Data pasien ini tidak ditemukan',
                                    'code' => 202
                                )
                            );
                            $httpCode = 202;
                        } else {
                            // Cek apakah pasien sudah terdaftar di booking_registrasi pada tanggal yang sama
                            $existingBooking = $this->db('booking_registrasi')
                                ->where('no_rkm_medis', $data_pasien['no_rkm_medis'])
                                ->where('tanggal_periksa', $tanggal)
                                ->oneArray();

                            if (!empty($existingBooking) && ($existingBooking['status'] ?? '') === 'Batal') {
                                $this->db('booking_registrasi')
                                    ->where('no_rkm_medis', $data_pasien['no_rkm_medis'])
                                    ->where('tanggal_periksa', $tanggal)
                                    ->delete();
                                $existingBooking = null;
                            }

                            if (!empty($existingBooking)) {
                                $response = array(
                                    'metadata' => array(
                                        'message' => 'Anda sudah terdaftar dalam antrian ditanggal ' . $tanggal . '. Silahkan pilih tanggal lain.',
                                        'code' => 201
                                    )
                                );
                                $httpCode = 201;
                            } else {
                                $kdDokterRs = $cek_kuota['kd_dokter'] ?: ($jadwal['kd_dokter'] ?? $dokter['kd_dokter']);
                                $kdPoliRs = $cek_kuota['kd_poli'] ?: ($jadwal['kd_poli'] ?? $poli['kd_poli_rs']);

                                $no_reg = $this->core->setNoBooking($kdDokterRs, $tanggal, $kdPoliRs);
                                if (strtotime($tanggal) == strtotime(date('Y-m-d'))) {
                                    $no_reg_today = $this->core->setNoReg($kdDokterRs, $kdPoliRs);
                                    if ((int)$no_reg_today > (int)$no_reg) {
                                        $no_reg = $no_reg_today;
                                    }
                                }

                                $no_urut_reg = (int)substr((string)$no_reg, 0, 3);
                                $minutes = $no_urut_reg * 10;
                                $jamMulaiEst = date('H:i:s', strtotime('+' . $minutes . ' minutes', strtotime($cek_kuota['jam_mulai'])));
                                $keterangan = 'Peserta harap datang 30 menit lebih awal.';
                                $estimasi = strtotime($tanggal . ' ' . $jamMulaiEst) * 1000;
                                if (strtotime($tanggal) == strtotime(date('Y-m-d'))) {
                                    $jamNowEst = date('H:i:s', strtotime('+' . $minutes . ' minutes', strtotime(date('H:i:s'))));
                                    $estimasi = strtotime($tanggal . ' ' . $jamNowEst) * 1000;
                                }

                                $pasienbaru = ($data_pasien['tgl_daftar'] == $tanggal) ? 1 : 0;

                                $query = $this->db('booking_registrasi')->save([
                                    'tanggal_booking' => date('Y-m-d'),
                                    'jam_booking' => date('H:i:s'),
                                    'no_rkm_medis' => $data_pasien['no_rkm_medis'],
                                    'tanggal_periksa' => $tanggal,
                                    'kd_dokter' => $kdDokterRs,
                                    'kd_poli' => $kdPoliRs,
                                    'no_reg' => $no_reg,
                                    'kd_pj' => $this->settings->get('jkn_mobile.kd_pj_bpjs'),
                                    'limit_reg' => 1,
                                    'waktu_kunjungan' => $tanggal . ' ' . $jamMulaiEst,
                                    'status' => 'Belum'
                                ]);

                                if ($query) {
                                    $kodebooking = $this->settings->get('settings.ppk_bpjs') . date('YmdHis') . $kodepoli . $no_reg . 'MJKN';
                                    $sisaKuota = max(0, (int)$cek_kuota['sisa_kuota'] - 1);
                                    $totalKuota = (int)$cek_kuota['kuota'];
                                    $namaDokter = !empty($jadwal['nm_dokter_bpjs']) ? $jadwal['nm_dokter_bpjs'] : ($dokter['nm_dokter_bpjs'] ?? $cek_kuota['nm_dokter']);

                                    $response = array(
                                        'response' => array(
                                            'nomorantrean' => $kodepoli . '-' . $no_reg,
                                            'angkaantrean' => (int)$no_reg,
                                            'kodebooking' => $kodebooking,
                                            'pasienbaru' => (int)$pasienbaru,
                                            'norm' => (string)$data_pasien['no_rkm_medis'],
                                            'namapoli' => (string)$cek_kuota['nm_poli'],
                                            'namadokter' => (string)$namaDokter,
                                            'estimasidilayani' => (int)$estimasi,
                                            'sisakuotajkn' => $sisaKuota,
                                            'kuotajkn' => $totalKuota,
                                            'sisakuotanonjkn' => $sisaKuota,
                                            'kuotanonjkn' => $totalKuota,
                                            'keterangan' => $keterangan
                                        ),
                                        'metadata' => array(
                                            'message' => 'Ok',
                                            'code' => 200
                                        )
                                    );
                                    $httpCode = 200;

                                    if ($nomorreferensi !== '') {
                                        $this->db('mlite_antrian_referensi')->save([
                                            'tanggal_periksa' => $tanggal,
                                            'no_rkm_medis' => $data_pasien['no_rkm_medis'],
                                            'nomor_kartu' => $nomorkartu,
                                            'nomor_referensi' => $nomorreferensi,
                                            'kodebooking' => $kodebooking,
                                            'jenis_kunjungan' => $jeniskunjungan,
                                            'status_kirim' => 'Belum',
                                            'keterangan' => ''
                                        ]);
                                    }
                                } else {
                                    $response = array(
                                        'metadata' => array(
                                            'message' => 'Maaf Terjadi Kesalahan, Hubungi layanan pelanggan Rumah Sakit..',
                                            'code' => 201
                                        )
                                    );
                                    $httpCode = 201;
                                }
                            }
                        }
                    } else {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Jadwal tidak tersedia atau kuota penuh! Silahkan pilih tanggal lain!',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile AmbilAntrian error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat memproses antrean. Silakan hubungi petugas Faskes.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }

        $this->_jsonResponse($response, $httpCode);
    }

    public function getStatusAntrian()
    {
        echo $this->_resultStatusAntrian();
        exit();
    }

    private function _resultStatusAntrian()
    {
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
                $tanggalperiksa = trim((string)($decode['tanggalperiksa'] ?? ''));
                $kodepoli = trim((string)($decode['kodepoli'] ?? ''));
                $kodedokter = trim((string)($decode['kodedokter'] ?? ''));

                $day = array(
                    'Sun' => 'AKHAD',
                    'Mon' => 'SENIN',
                    'Tue' => 'SELASA',
                    'Wed' => 'RABU',
                    'Thu' => 'KAMIS',
                    'Fri' => 'JUMAT',
                    'Sat' => 'SABTU'
                );

                $kdpoli = ($kodepoli !== '') ? $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $kodepoli)->oneArray() : null;
                $kddokter = ($kodedokter !== '') ? $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kodedokter)->oneArray() : null;

                if (empty($kdpoli)) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Poli Tidak Ditemukan',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if ($kodedokter !== '' && empty($kddokter)) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Dokter Tidak Ditemukan',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalperiksa)) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    $hari = $day[date('D', strtotime($tanggalperiksa))] ?? '';
                    $jadwalCheck = $this->db('jadwal')
                        ->join('maping_dokter_dpjpvclaim', 'maping_dokter_dpjpvclaim.kd_dokter=jadwal.kd_dokter')
                        ->join('maping_poli_bpjs', 'maping_poli_bpjs.kd_poli_rs=jadwal.kd_poli')
                        ->where('maping_poli_bpjs.kd_poli_bpjs', $kodepoli)
                        ->where('hari_kerja', $hari);
                    if ($kodedokter !== '') {
                        $jadwalCheck->where('maping_dokter_dpjpvclaim.kd_dokter_bpjs', $kodedokter);
                    }
                    $jadwalRow = $jadwalCheck->oneArray();

                    if (empty($jadwalRow)) {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Jadwal Praktek Tidak Ditemukan Pada Hari ' . $hari . ' Tanggal ' . $tanggalperiksa,
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else if (strtotime(date("Y-m-d")) > strtotime($tanggalperiksa)) {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Tanggal Periksa Tidak Berlaku Mundur',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else {
                        $kdDokterRs = !empty($kddokter['kd_dokter']) ? $kddokter['kd_dokter'] : $jadwalRow['kd_dokter'];
                        $kdPoliRs = !empty($jadwalRow['kd_poli']) ? $jadwalRow['kd_poli'] : $kdpoli['kd_poli_rs'];
                        $totalKuota = (int)($jadwalRow['kuota'] ?? 0);

                        $isToday = ($tanggalperiksa === date('Y-m-d'));
                        $table = $isToday ? 'reg_periksa' : 'booking_registrasi';

                        $q = $this->db($table)
                            ->select(['no_reg'])
                            ->where('kd_dokter', $kdDokterRs)
                            ->where('kd_poli', $kdPoliRs);

                        if ($isToday) {
                            $q->where('stts', 'Belum')
                              ->where('tgl_registrasi', $tanggalperiksa);
                        } else {
                            $q->where('status', 'Belum')
                              ->where('tanggal_periksa', $tanggalperiksa);
                        }

                        $max_antrian = $q
                            ->asc('no_reg')
                            ->limit(1)
                            ->oneArray();

                        if ($isToday) {
                            $stmtData = $this->db()->pdo()->prepare(
                                "SELECT poliklinik.nm_poli, COUNT(reg_periksa.kd_poli) as total_antrean, dokter.nm_dokter,
                                IFNULL(SUM(CASE WHEN reg_periksa.stts = 'Belum' THEN 1 ELSE 0 END), 0) as sisa_antrean
                                FROM reg_periksa
                                INNER JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
                                INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                                WHERE reg_periksa.tgl_registrasi = :tgl AND reg_periksa.kd_poli = :kdpoli AND reg_periksa.kd_dokter = :kddokter AND reg_periksa.stts != 'Batal'"
                            );
                        } else {
                            $stmtData = $this->db()->pdo()->prepare(
                                "SELECT poliklinik.nm_poli, COUNT(booking_registrasi.kd_poli) as total_antrean, dokter.nm_dokter,
                                IFNULL(SUM(CASE WHEN booking_registrasi.status = 'Belum' THEN 1 ELSE 0 END), 0) as sisa_antrean
                                FROM booking_registrasi
                                INNER JOIN poliklinik ON poliklinik.kd_poli = booking_registrasi.kd_poli
                                INNER JOIN dokter ON booking_registrasi.kd_dokter = dokter.kd_dokter
                                WHERE booking_registrasi.tanggal_periksa = :tgl AND booking_registrasi.kd_poli = :kdpoli AND booking_registrasi.kd_dokter = :kddokter AND booking_registrasi.status != 'Batal'"
                            );
                        }
                        $stmtData->execute([':tgl' => $tanggalperiksa, ':kdpoli' => $kdPoliRs, ':kddokter' => $kdDokterRs]);
                        $data = $stmtData->fetch(\PDO::FETCH_ASSOC) ?: [];

                        $totalAntrean = (int)($data['total_antrean'] ?? 0);
                        $sisaAntrean = (int)($data['sisa_antrean'] ?? 0);
                        $sisaKuota = max(0, $totalKuota - $totalAntrean);
                        $namaPoli = !empty($data['nm_poli']) ? $data['nm_poli'] : ($kdpoli['nm_poli_bpjs'] ?? '');
                        $namaDokter = !empty($kddokter['nm_dokter_bpjs']) ? $kddokter['nm_dokter_bpjs'] : ($data['nm_dokter'] ?? ($jadwalRow['nm_dokter_bpjs'] ?? ''));
                        $antreanPanggil = ($sisaAntrean > 0 && !empty($max_antrian['no_reg'])) ? ('A-' . $max_antrian['no_reg']) : 'A-0';

                        $response = array(
                            'response' => array(
                                'namapoli' => (string)$namaPoli,
                                'namadokter' => (string)$namaDokter,
                                'totalantrean' => $totalAntrean,
                                'sisaantrean' => $sisaAntrean,
                                'antreanpanggil' => $antreanPanggil,
                                'sisakuotajkn' => $sisaKuota,
                                'kuotajkn' => $totalKuota,
                                'sisakuotanonjkn' => $sisaKuota,
                                'kuotanonjkn' => $totalKuota,
                                'keterangan' => 'Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke layanan pelanggan, Terima Kasih ..'
                            ),
                            'metadata' => array(
                                'message' => 'Ok',
                                'code' => 200
                            )
                        );
                        $httpCode = 200;
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile StatusAntrian error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat mengecek status antrean. Silakan hubungi petugas Faskes.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }

        $this->_jsonResponse($response, $httpCode);
    }

    public function getAmbilAntrianFarmasi()
    {
        echo $this->_resultAmbilAntrianFarmasi();
        exit();
    }

    private function _resultAmbilAntrianFarmasi()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        if ($reqToken === null || $reqToken === '' || $reqToken === false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired or missing',
                    'code' => 201
                )
            );
            $httpCode = 201;
        } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
            if (empty($decode['kodebooking'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Booking tidak boleh kosong',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if (strpos($decode['kodebooking'], "'") !== false || strpos($decode['kodebooking'], "\\") !== false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Booking salah',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else {
                $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                $booking_registrasi = [];
                $pasien = [];
                if ($referensi) {
                    $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                    if (!empty($pasien['no_rkm_medis'])) {
                        $booking_registrasi = $this->db('booking_registrasi')
                            ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                            ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                            ->oneArray();
                    }
                }
                if (!$booking_registrasi) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data Booking tidak ditemukan',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    if ($booking_registrasi['status'] == 'Batal') {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Booking Anda Sudah Dibatalkan.',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else if ($booking_registrasi['status'] == 'Belum') {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Anda Belum Melakukan Checkin.',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else if ($booking_registrasi['status'] == 'Terdaftar') {
                        $reg_periksa = $this->db('reg_periksa')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])->oneArray();
                        $resep = !empty($reg_periksa['no_rawat'])
                            ? $this->db('resep_obat')->where('no_rawat', $reg_periksa['no_rawat'])->where('status', 'ralan')->oneArray()
                            : null;

                        $get_mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('postdate', $booking_registrasi['tanggal_periksa'])->where('no_rkm_medis', $pasien['no_rkm_medis'])->oneArray();

                        if (!$resep) {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Anda tidak memiliki resep dari dokter yang anda tuju, silahkan konfirmasi petugas poli',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else {
                            $resep_racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep['no_resep'])->oneArray();
                            $jenis_resep = $resep_racikan ? 'Racikan' : 'Non Racikan';
                            $response = array(
                                'response' => array(
                                    'jenisresep' => $jenis_resep,
                                    'nomorantrean' => (int)($get_mlite_antrian_loket['noantrian'] ?? 0),
                                    'keterangan' => "Resep dibuat secara elektronik di poli"
                                ),
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );
                            $httpCode = 200;
                        }
                    }
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getStatusAntrianFarmasi()
    {
        echo $this->_resultStatusAntrianFarmasi();
        exit();
    }

    private function _resultStatusAntrianFarmasi()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        if ($reqToken === null || $reqToken === '' || $reqToken === false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired or missing',
                    'code' => 201
                )
            );
            $httpCode = 201;
        } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
            if (empty($decode['kodebooking'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Booking tidak boleh kosong',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if (strpos($decode['kodebooking'], "'") !== false || strpos($decode['kodebooking'], "\\") !== false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Booking salah',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else {
                $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                $booking_registrasi = [];
                $pasien = [];
                if ($referensi) {
                    $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                    if (!empty($pasien['no_rkm_medis'])) {
                        $booking_registrasi = $this->db('booking_registrasi')
                            ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                            ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                            ->oneArray();
                    }
                }
                if (!$booking_registrasi) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data Booking tidak ditemukan',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    if ($booking_registrasi['status'] == 'Batal') {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Booking Anda Sudah Dibatalkan.',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else if ($booking_registrasi['status'] == 'Belum') {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Anda Belum Melakukan Checkin.',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else if ($booking_registrasi['status'] == 'Terdaftar') {
                        $reg_periksa = $this->db('reg_periksa')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])->oneArray();
                        $resep = !empty($reg_periksa['no_rawat'])
                            ? $this->db('resep_obat')->where('no_rawat', $reg_periksa['no_rawat'])->where('status', 'ralan')->oneArray()
                            : null;

                        $mlite_antrian_loket = $this->db('mlite_antrian_loket')
                            ->select(['count' => 'COUNT(DISTINCT noantrian)'])
                            ->where('type', 'Apotek')
                            ->where('postdate', $booking_registrasi['tanggal_periksa'])
                            ->oneArray();

                        $mlite_antrian_loket_sisaantrean = $this->db('mlite_antrian_loket')
                            ->select(['count' => 'COUNT(DISTINCT noantrian)'])
                            ->where('type', 'Apotek')
                            ->where('postdate', $booking_registrasi['tanggal_periksa'])
                            ->where('end_time', '<>', '00:00:00')
                            ->oneArray();

                        if (!$resep) {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Anda tidak memiliki resep dari dokter yang anda tuju, silahkan konfirmasi petugas poli',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else {
                            $resep_racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep['no_resep'])->oneArray();
                            $jenis_resep = $resep_racikan ? 'Racikan' : 'Non Racikan';
                            $totFarmasi = (int)($mlite_antrian_loket['count'] ?? 0);
                            $sisaFarmasi = (int)($mlite_antrian_loket_sisaantrean['count'] ?? 0);
                            $panggil = max(0, $totFarmasi - $sisaFarmasi);
                            $response = array(
                                'response' => array(
                                    'jenisresep' => $jenis_resep,
                                    'totalantrean' => $totFarmasi,
                                    'sisaantrean' => $sisaFarmasi,
                                    'antreanpanggil' => $panggil,
                                    'keterangan' => ""
                                ),
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );
                            $httpCode = 200;
                        }
                    }
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getSisaAntrian()
    {
        echo $this->_resultSisaAntrian();
        exit();
    }

    private function _resultSisaAntrian()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
                if (empty($decode['kodebooking'])) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Kode Booking tidak boleh kosong',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if (strpos($decode['kodebooking'], "'") !== false || strpos($decode['kodebooking'], "\\") !== false) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Format Kode Booking salah',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                    $booking_registrasi = [];
                    $pasien = [];
                    if ($referensi) {
                        $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                        if (!empty($pasien['no_rkm_medis'])) {
                            $booking_registrasi = $this->db('booking_registrasi')
                                ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                                ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                                ->oneArray();
                        }
                    }
                    if (!$booking_registrasi) {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Data Booking tidak ditemukan',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else {
                        if ($booking_registrasi['status'] == 'Belum') {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Anda belum melakukan checkin, Silahkan checkin terlebih dahulu',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else if ($booking_registrasi['status'] == 'Batal') {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Data Booking Sudah Dibatalkan',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else if ($booking_registrasi['status'] == 'Terdaftar') {
                            $noreg = $this->db('reg_periksa')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])->oneArray();

                            $max_antrian = $this->db('reg_periksa')
                                ->select(['no_reg'])
                                ->where('stts', 'Belum')
                                ->where('kd_dokter', $booking_registrasi['kd_dokter'])
                                ->where('kd_poli', $booking_registrasi['kd_poli'])
                                ->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])
                                ->orderByRightNumber('no_reg', 3, 'ASC')
                                ->limit(1)
                                ->oneArray();

                            $stmtSisa = $this->db()->pdo()->prepare(
                                "SELECT reg_periksa.kd_poli, poliklinik.nm_poli, dokter.nm_dokter,
                                    reg_periksa.no_reg, COUNT(reg_periksa.no_rawat) as total_antrean,
                                    IFNULL(SUM(CASE WHEN reg_periksa.stts = 'Belum' THEN 1 ELSE 0 END), 0) as sisa_antrean
                                FROM reg_periksa
                                INNER JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
                                INNER JOIN dokter ON dokter.kd_dokter = reg_periksa.kd_dokter
                                WHERE reg_periksa.kd_dokter = :kddokter AND reg_periksa.kd_poli = :kdpoli AND reg_periksa.tgl_registrasi = :tgl"
                            );
                            $stmtSisa->execute([
                                ':kddokter' => $booking_registrasi['kd_dokter'],
                                ':kdpoli' => $booking_registrasi['kd_poli'],
                                ':tgl' => $booking_registrasi['tanggal_periksa']
                            ]);
                            $data = $stmtSisa->fetch(\PDO::FETCH_ASSOC);

                            if (!empty($data['nm_poli'])) {
                                $sisaAntrean = max(0, (int)($data['sisa_antrean'] ?? 0));
                                $response = array(
                                    'response' => array(
                                        'nomorantrean' => 'A-' . ($noreg['no_reg'] ?? $booking_registrasi['no_reg']),
                                        'namapoli' => (string)$data['nm_poli'],
                                        'namadokter' => (string)$data['nm_dokter'],
                                        'sisaantrean' => $sisaAntrean,
                                        'antreanpanggil' => 'A-' . (!empty($max_antrian['no_reg']) ? $max_antrian['no_reg'] : '000'),
                                        'waktutunggu' => (int)($sisaAntrean * 10 * 60),
                                        'keterangan' => "Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke layanan pelanggan, Terima Kasih .."
                                    ),
                                    'metadata' => array(
                                        'message' => 'Ok',
                                        'code' => 200
                                    )
                                );
                                $httpCode = 200;
                            } else {
                                $response = array(
                                    'metadata' => array(
                                        'message' => 'Antrean Tidak Ditemukan !',
                                        'code' => 201
                                    )
                                );
                                $httpCode = 201;
                            }
                        }
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile SisaAntrian error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat mengecek sisa antrean.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getBatalAntrian()
    {
        echo $this->_resultBatalAntrian();
        exit();
    }

    private function _resultBatalAntrian()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
                if (empty($decode['kodebooking'])) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Kode Booking tidak boleh kosong',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if (strpos($decode['kodebooking'], "'") !== false || strpos($decode['kodebooking'], "\\") !== false) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Format Kode Booking salah',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if (empty($decode['keterangan'])) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Keterangan tidak boleh kosong',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else if (strpos($decode['keterangan'], "'") !== false || strpos($decode['keterangan'], "\\") !== false) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Format Keterangan salah',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                } else {
                    $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                    $booking_registrasi = [];
                    $pasien = [];
                    if ($referensi) {
                        $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                        if (!empty($pasien['no_rkm_medis'])) {
                            $booking_registrasi = $this->db('booking_registrasi')
                                ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                                ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                                ->oneArray();
                        }
                    }
                    if (!$booking_registrasi) {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Data Booking tidak ditemukan',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else {
                        if (strtotime(date("Y-m-d")) > strtotime($booking_registrasi['tanggal_periksa'])) {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Pembatalan Antrean tidak berlaku mundur',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else if ($booking_registrasi['status'] == 'Terdaftar') {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Anda Sudah Checkin, Pendaftaran Tidak Bisa Dibatalkan',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        } else if ($booking_registrasi['status'] == 'Belum') {
                            $this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->delete();
                            if (!$this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->oneArray()) {
                                $response = array(
                                    'metadata' => array(
                                        'message' => 'Ok',
                                        'code' => 200
                                    )
                                );
                                $this->db('mlite_antrian_referensi_batal')->save([
                                    'tanggal_batal' => date('Y-m-d'),
                                    'nomor_referensi' => $referensi['nomor_referensi'],
                                    'kodebooking' => $decode['kodebooking'],
                                    'keterangan' => $decode['keterangan']
                                ]);
                                $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->delete();
                                $httpCode = 200;
                            } else {
                                $response = array(
                                    'metadata' => array(
                                        'message' => "Maaf Terjadi Kesalahan, Hubungi Administrator..",
                                        'code' => 201
                                    )
                                );
                                $httpCode = 201;
                            }
                        }
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile BatalAntrian error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat membatalkan antrean.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getPasienBaru()
    {
        echo $this->_resultPasienBaru();
        exit();
    }

    private function _resultPasienBaru()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
                if (empty($decode['nomorkartu'])) {
                    $response = array('metadata' => array('message' => 'Nomor Kartu tidak boleh kosong', 'code' => 201));
                } else if (mb_strlen($decode['nomorkartu'], 'UTF-8') <> 13) {
                    $response = array('metadata' => array('message' => 'Nomor Kartu harus 13 digit', 'code' => 201));
                } else if (!preg_match("/^[0-9]{13}$/", $decode['nomorkartu'])) {
                    $response = array('metadata' => array('message' => 'Format Nomor Kartu tidak sesuai', 'code' => 201));
                } else if (empty($decode['nik'])) {
                    $response = array('metadata' => array('message' => 'NIK tidak boleh kosong', 'code' => 201));
                } else if (strlen($decode['nik']) <> 16) {
                    $response = array('metadata' => array('message' => 'NIK harus 16 digit', 'code' => 201));
                } else if (!preg_match("/^[0-9]{16}$/", $decode['nik'])) {
                    $response = array('metadata' => array('message' => 'Format NIK tidak sesuai', 'code' => 201));
                } else if (empty($decode['nomorkk'])) {
                    $response = array('metadata' => array('message' => 'Nomor KK tidak boleh kosong', 'code' => 201));
                } else if (strlen($decode['nomorkk']) <> 16) {
                    $response = array('metadata' => array('message' => 'Nomor KK harus 16 digit', 'code' => 201));
                } else if (!preg_match("/^[0-9]{16}$/", $decode['nomorkk'])) {
                    $response = array('metadata' => array('message' => 'Format Nomor KK tidak sesuai', 'code' => 201));
                } else if (empty($decode['nama'])) {
                    $response = array('metadata' => array('message' => 'Nama tidak boleh kosong', 'code' => 201));
                } else if (strpos($decode['nama'], "'") !== false || strpos($decode['nama'], "\\") !== false) {
                    $response = array('metadata' => array('message' => 'Format Nama salah', 'code' => 201));
                } else if (empty($decode['jeniskelamin'])) {
                    $response = array('metadata' => array('message' => 'Jenis Kelamin tidak boleh kosong', 'code' => 201));
                } else if (!in_array($decode['jeniskelamin'], ['L', 'P'], true)) {
                    $response = array('metadata' => array('message' => 'Jenis Kelamin tidak ditemukan', 'code' => 201));
                } else if (empty($decode['tanggallahir'])) {
                    $response = array('metadata' => array('message' => 'Tanggal Lahir tidak boleh kosong', 'code' => 201));
                } else if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $decode['tanggallahir'])) {
                    $response = array('metadata' => array('message' => 'Format Tanggal Lahir tidak sesuai, format yang benar adalah yyyy-mm-dd', 'code' => 201));
                } else if (empty($decode['nohp'])) {
                    $response = array('metadata' => array('message' => 'No.HP tidak boleh kosong', 'code' => 201));
                } else if (strpos($decode['nohp'], "'") !== false || strpos($decode['nohp'], "\\") !== false) {
                    $response = array('metadata' => array('message' => 'Format No.HP salah', 'code' => 201));
                } else if (empty($decode['alamat'])) {
                    $response = array('metadata' => array('message' => 'Alamat tidak boleh kosong', 'code' => 201));
                } else if (strpos($decode['alamat'], "'") !== false || strpos($decode['alamat'], "\\") !== false) {
                    $response = array('metadata' => array('message' => 'Format Alamat salah', 'code' => 201));
                } else if (empty($decode['kodeprop'])) {
                    $response = array('metadata' => array('message' => 'Kode Propinsi tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['namaprop'])) {
                    $response = array('metadata' => array('message' => 'Nama Propinsi tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['kodedati2'])) {
                    $response = array('metadata' => array('message' => 'Kode Dati II tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['namadati2'])) {
                    $response = array('metadata' => array('message' => 'Nama Dati II tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['kodekec'])) {
                    $response = array('metadata' => array('message' => 'Kode Kecamatan tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['namakec'])) {
                    $response = array('metadata' => array('message' => 'Nama Kecamatan tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['kodekel'])) {
                    $response = array('metadata' => array('message' => 'Kode Kelurahan tidak boleh kosong', 'code' => 201));
                } else if (empty($decode['namakel'])) {
                    $response = array('metadata' => array('message' => 'Nama Kelurahan tidak boleh kosong', 'code' => 201));
                } else if (!isset($decode['rw']) || trim((string)$decode['rw']) === '') {
                    $response = array('metadata' => array('message' => 'RW tidak boleh kosong', 'code' => 201));
                } else if (!isset($decode['rt']) || trim((string)$decode['rt']) === '') {
                    $response = array('metadata' => array('message' => 'RT tidak boleh kosong', 'code' => 201));
                } else {
                    if ($this->db('pasien')->where('no_peserta', $decode['nomorkartu'])->oneArray()) {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Data pasien ini sudah terdaftar',
                                'code' => 201
                            )
                        );
                        $httpCode = 201;
                    } else {
                        $date = date('Y-m-d');
                        $noRmBaru = $this->core->setNoRM();
                        $patientData = [
                            'no_rkm_medis' => $noRmBaru,
                            'nm_pasien' => $decode['nama'],
                            'no_ktp' => $decode['nik'],
                            'jk' => $decode['jeniskelamin'],
                            'tmp_lahir' => '-',
                            'tgl_lahir' => $decode['tanggallahir'],
                            'nm_ibu' => '-',
                            'alamat' => $decode['alamat'],
                            'gol_darah' => '-',
                            'pekerjaan' => '-',
                            'stts_nikah' => 'JOMBLO',
                            'agama' => '-',
                            'tgl_daftar' => $date,
                            'no_tlp' => $decode['nohp'],
                            'umur' => $this->_setUmur($decode['tanggallahir']),
                            'pnd' => '-',
                            'keluarga' => 'AYAH',
                            'namakeluarga' => '-',
                            'kd_pj' => $this->settings->get('jkn_mobile.kd_pj_bpjs') ?: 'BPJ',
                            'no_peserta' => $decode['nomorkartu'],
                            'kd_kel' => $this->settings->get('jkn_mobile.kdkel'),
                            'kd_kec' => $this->settings->get('jkn_mobile.kdkec'),
                            'kd_kab' => $this->settings->get('jkn_mobile.kdkab'),
                            'pekerjaanpj' => '-',
                            'alamatpj' => '-',
                            'kelurahanpj' => '-',
                            'kecamatanpj' => '-',
                            'kabupatenpj' => '-',
                            'perusahaan_pasien' => $this->settings->get('jkn_mobile.perusahaan_pasien'),
                            'suku_bangsa' => $this->settings->get('jkn_mobile.suku_bangsa'),
                            'bahasa_pasien' => $this->settings->get('jkn_mobile.bahasa_pasien'),
                            'cacat_fisik' => $this->settings->get('jkn_mobile.cacat_fisik'),
                            'email' => '',
                            'nip' => '',
                            'kd_prop' => $this->settings->get('jkn_mobile.kdprop'),
                            'propinsipj' => '-'
                        ];

                        $query = $this->db('pasien')->save($patientData);

                        if ($query) {
                            $stmtSetRm = $this->db()->pdo()->prepare("UPDATE set_no_rkm_medis SET no_rkm_medis = ?");
                            $stmtSetRm->execute([$noRmBaru]);
                            $response = array(
                                'response' => array(
                                    'norm' => (string)$noRmBaru
                                ),
                                'metadata' => array(
                                    'message' => 'Pasien berhasil mendapatkan nomor RM, silahkan lanjutkan ke booking. Pasien tidak perlu ke admisi.',
                                    'code' => 200
                                )
                            );
                            $httpCode = 200;
                        } else {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Gagal menyimpan data pasien baru, silahkan hubungi petugas Faskes.',
                                    'code' => 201
                                )
                            );
                            $httpCode = 201;
                        }
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile PasienBaru error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat mendaftarkan pasien baru.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getPasienCheckIn()
    {
        echo $this->_resultPasienCheckIn();
        exit();
    }

    private function _resultPasienCheckIn()
    {
        date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        try {
            if ($reqToken === null || $reqToken === '' || $reqToken === false) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token expired or missing',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
                if (empty($decode['kodebooking'])) {
                    $response = array('metadata' => array('message' => 'Kode Booking tidak boleh kosong', 'code' => 201));
                } else if (strpos($decode['kodebooking'], "'") !== false || strpos($decode['kodebooking'], "\\") !== false) {
                    $response = array('metadata' => array('message' => 'Format Kode Booking salah', 'code' => 201));
                } else if (empty($decode['waktu']) || !is_numeric($decode['waktu'])) {
                    $response = array('metadata' => array('message' => 'Waktu tidak boleh kosong', 'code' => 201));
                } else {
                    $waktuSec = (int)floor(((float)$decode['waktu']) / 1000);
                    $tanggal = date("Y-m-d", $waktuSec);
                    $jam = date("H:i:s", $waktuSec);

                    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggal)) {
                        $response = array('metadata' => array('message' => 'Format Tanggal Checkin tidak sesuai, format yang benar adalah yyyy-mm-dd', 'code' => 201));
                    } else if (strtotime(date("Y-m-d")) > strtotime($tanggal)) {
                        $response = array('metadata' => array('message' => 'Waktu Checkin tidak berlaku mundur', 'code' => 201));
                    } else {
                        $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                        $booking_registrasi = [];
                        $pasien = [];
                        if ($referensi) {
                            $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                            if (!empty($pasien['no_rkm_medis'])) {
                                $booking_registrasi = $this->db('booking_registrasi')
                                    ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                                    ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                                    ->oneArray();
                            }
                        }
                        if (!$booking_registrasi) {
                            $response = array('metadata' => array('message' => 'Data Booking tidak ditemukan', 'code' => 201));
                        } else if ($booking_registrasi['status'] == 'Terdaftar') {
                            $response = array('metadata' => array('message' => 'Anda Sudah Checkin', 'code' => 201));
                        } else if ($booking_registrasi['status'] == 'Belum') {
                            $day = array(
                                'Sun' => 'AKHAD', 'Mon' => 'SENIN', 'Tue' => 'SELASA',
                                'Wed' => 'RABU', 'Thu' => 'KAMIS', 'Fri' => 'JUMAT', 'Sat' => 'SABTU'
                            );
                            $hari = $day[date('D', strtotime($tanggal))] ?? '';
                            $cekjam = $this->db("jadwal")
                                ->where("kd_poli", $booking_registrasi['kd_poli'])
                                ->where('kd_dokter', $booking_registrasi['kd_dokter'])
                                ->where('hari_kerja', $hari)
                                ->oneArray();
                            $stmtInt = $this->db()->pdo()->prepare("SELECT (TO_DAYS(?) - TO_DAYS(?))");
                            $stmtInt->execute([$booking_registrasi['tanggal_periksa'], $tanggal]);
                            $interval = $stmtInt->fetch(\PDO::FETCH_NUM);

                            $isExpired = ($interval && (int)$interval[0] <= 0 && !empty($cekjam['jam_selesai']) && strtotime($jam) >= strtotime($cekjam['jam_selesai']));
                            if ($isExpired) {
                                $response = array(
                                    'metadata' => array(
                                        'message' => 'Checkin Anda sudah expired, maksimal 1 hari sebelum tanggal periksa dan sebelum jam pelayanan berakhir. Silahkan konfirmasi ke layanan pelanggan',
                                        'code' => 201
                                    )
                                );
                            } else {
                                $cek_stts_daftar = $this->db('reg_periksa')->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])->count();
                                $stts_daftar = ($cek_stts_daftar > 0) ? 'Lama' : 'Baru';

                                $biaya_reg_row = $this->db('poliklinik')->where('kd_poli', $booking_registrasi['kd_poli'])->oneArray();
                                $biaya_reg = ($stts_daftar === 'Lama')
                                    ? ($biaya_reg_row['registrasilama'] ?? 0)
                                    : ($biaya_reg_row['registrasi'] ?? 0);

                                $cek_status_poli = $this->db('reg_periksa')
                                    ->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])
                                    ->where('kd_poli', $booking_registrasi['kd_poli'])
                                    ->count();
                                $status_poli = ($cek_status_poli > 0) ? 'Lama' : 'Baru';

                                $tglLahirStr = $this->core->getPasienInfo('tgl_lahir', $booking_registrasi['no_rkm_medis']);
                                $umur = "0";
                                $sttsumur = "Th";
                                if (!empty($tglLahirStr) && $tglLahirStr !== '0000-00-00') {
                                    $tglLahirObj = new \DateTime($tglLahirStr);
                                    $todayObj = new \DateTime(date('Y-m-d'));
                                    $diff = $todayObj->diff($tglLahirObj);
                                    if ($diff->y > 0) {
                                        $umur = (string)$diff->y;
                                        $sttsumur = "Th";
                                    } elseif ($diff->m > 0) {
                                        $umur = (string)$diff->m;
                                        $sttsumur = "Bl";
                                    } else {
                                        $umur = (string)$diff->d;
                                        $sttsumur = "Hr";
                                    }
                                }

                                $this->db('booking_registrasi')
                                    ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                                    ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                                    ->update(['status' => 'Terdaftar']);

                                $insert = $this->db('reg_periksa')->save([
                                    'no_reg' => $booking_registrasi['no_reg'],
                                    'no_rawat' => $this->core->setNoRawat($booking_registrasi['tanggal_periksa']),
                                    'tgl_registrasi' => $booking_registrasi['tanggal_periksa'],
                                    'jam_reg' => date('H:i:s'),
                                    'kd_dokter' => $booking_registrasi['kd_dokter'],
                                    'no_rkm_medis' => $booking_registrasi['no_rkm_medis'],
                                    'kd_poli' => $booking_registrasi['kd_poli'],
                                    'p_jawab' => $this->core->getPasienInfo('namakeluarga', $booking_registrasi['no_rkm_medis']),
                                    'almt_pj' => $this->core->getPasienInfo('alamatpj', $booking_registrasi['no_rkm_medis']),
                                    'hubunganpj' => $this->core->getPasienInfo('keluarga', $booking_registrasi['no_rkm_medis']),
                                    'biaya_reg' => $biaya_reg,
                                    'stts' => 'Belum',
                                    'stts_daftar' => $stts_daftar,
                                    'status_lanjut' => 'Ralan',
                                    'kd_pj' => $booking_registrasi['kd_pj'],
                                    'umurdaftar' => $umur,
                                    'sttsumur' => $sttsumur,
                                    'status_bayar' => 'Belum Bayar',
                                    'status_poli' => $status_poli
                                ]);

                                if ($insert) {
                                    if ($interval && (int)$interval[0] <= 0) {
                                        $reg_periksa = $this->db('reg_periksa')
                                            ->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])
                                            ->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])
                                            ->oneArray();
                                        $mlite_antrian_loket = $this->db('mlite_antrian_loket')
                                            ->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])
                                            ->where('postdate', $booking_registrasi['tanggal_periksa'])
                                            ->oneArray();
                                        if ($stts_daftar === 'Baru') {
                                            $result = $this->db('mlite_antrian_loket')
                                                ->select('noantrian')
                                                ->where('type', 'Loket')
                                                ->where('postdate', date('Y-m-d'))
                                                ->desc('start_time')
                                                ->oneArray();
                                            $noantrian = !empty($result['noantrian']) ? (int)$result['noantrian'] : 0;
                                            $next_antrian = $noantrian + 1;
                                            if (empty($mlite_antrian_loket['postdate'])) {
                                                $this->db('mlite_antrian_loket')->save([
                                                    'type' => 'Loket',
                                                    'noantrian' => $next_antrian,
                                                    'no_rkm_medis' => $booking_registrasi['no_rkm_medis'],
                                                    'postdate' => date('Y-m-d'),
                                                    'start_time' => date('H:i:s'),
                                                    'end_time' => '00:00:00',
                                                    'status' => '0',
                                                    'loket' => '0'
                                                ]);
                                            }
                                        }
                                        if (!empty($reg_periksa['no_rawat']) && !$this->db('mutasi_berkas')->where('no_rawat', $reg_periksa['no_rawat'])->oneArray()) {
                                            $this->db('mutasi_berkas')->save([
                                                'no_rawat' => $reg_periksa['no_rawat'],
                                                'status' => 'Sudah Dikirim',
                                                'dikirim' => date('Y-m-d H:i:s'),
                                                'diterima' => '0000-00-00 00:00:00',
                                                'kembali' => '0000-00-00 00:00:00',
                                                'tidakada' => '0000-00-00 00:00:00',
                                                'ranap' => '0000-00-00 00:00:00'
                                            ]);
                                        }
                                    }
                                    $response = array(
                                        'metadata' => array(
                                            'message' => 'Ok',
                                            'code' => 200
                                        )
                                    );
                                    $httpCode = 200;
                                } else {
                                    $response = array(
                                        'metadata' => array(
                                            'message' => "Maaf terjadi kesalahan, hubungi Administrator..",
                                            'code' => 201
                                        )
                                    );
                                    $httpCode = 201;
                                }
                            }
                        }
                    }
                }
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Access denied',
                        'code' => 201
                    )
                );
                $httpCode = 201;
            }
        } catch (\Throwable $e) {
            error_log('JKN Mobile CheckIn error: ' . $e->getMessage());
            $response = array(
                'metadata' => array(
                    'message' => 'Terjadi kesalahan saat memproses check-in.',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getOperasiRS()
    {
        echo $this->_resultOperasiRS();
        exit();
    }

    private function _resultOperasiRS()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        if ($reqToken === null || $reqToken === '' || $reqToken === false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired or missing',
                    'code' => 201
                )
            );
            $httpCode = 201;
        } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
            $tanggalawal = trim((string)($decode['tanggalawal'] ?? ''));
            $tanggalakhir = trim((string)($decode['tanggalakhir'] ?? ''));
            $errors = [];

            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalawal)) {
                $errors[] = 'Format tanggal awal jadwal operasi tidak sesuai';
            }
            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $tanggalakhir)) {
                $errors[] = 'Format tanggal akhir jadwal operasi tidak sesuai';
            }
            if (empty($errors) && $tanggalawal > $tanggalakhir) {
                $errors[] = 'Format tanggal awal harus lebih kecil dari tanggal akhir';
            }
            if (!empty($errors)) {
                $response = array(
                    'metadata' => array(
                        'message' => implode(', ', $errors),
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else {
                $stmtOp = $this->db()->pdo()->prepare(
                    "SELECT booking_operasi.no_rawat AS kodebooking, booking_operasi.tanggal AS tanggaloperasi, paket_operasi.nm_perawatan AS jenistindakan, maping_poli_bpjs.kd_poli_bpjs AS kodepoli, poliklinik.nm_poli AS namapoli, booking_operasi.status AS terlaksana, pasien.no_peserta AS nopeserta
                    FROM pasien, booking_operasi, paket_operasi, reg_periksa, jadwal, poliklinik, maping_poli_bpjs
                    WHERE booking_operasi.no_rawat = reg_periksa.no_rawat
                    AND pasien.no_rkm_medis = reg_periksa.no_rkm_medis
                    AND booking_operasi.kode_paket = paket_operasi.kode_paket
                    AND booking_operasi.kd_dokter = jadwal.kd_dokter
                    AND jadwal.kd_poli = poliklinik.kd_poli
                    AND jadwal.kd_poli = maping_poli_bpjs.kd_poli_rs
                    AND booking_operasi.tanggal BETWEEN :tglawal AND :tglakhir
                    GROUP BY booking_operasi.no_rawat"
                );
                $stmtOp->execute([':tglawal' => $tanggalawal, ':tglakhir' => $tanggalakhir]);
                $rows = $stmtOp->fetchAll(\PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $data_array = [];
                    foreach ($rows as $data) {
                        $data_array[] = array(
                            'kodebooking' => (string)$data['kodebooking'],
                            'tanggaloperasi' => (string)$data['tanggaloperasi'],
                            'jenistindakan' => (string)$data['jenistindakan'],
                            'kodepoli' => (string)$data['kodepoli'],
                            'namapoli' => (string)$data['namapoli'],
                            'terlaksana' => ($data['terlaksana'] == 'Menunggu') ? 0 : 1,
                            'nopeserta' => (string)$data['nopeserta'],
                            'lastupdate' => (int)(strtotime(date('Y-m-d H:i:s')) * 1000)
                        );
                    }
                    $response = array(
                        'response' => array('list' => $data_array),
                        'metadata' => array('message' => 'Ok', 'code' => 200)
                    );
                    $httpCode = 200;
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Maaf tidak ada daftar booking operasi.',
                            'code' => 201
                        )
                    );
                    $httpCode = 201;
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function getOperasiPasien()
    {
        echo $this->_resultOperasiPasien();
        exit();
    }

    private function _resultOperasiPasien()
    {
        header("Content-Type: application/json; charset=utf-8");
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        if (!is_array($decode)) {
            $decode = [];
        }
        $response = array();
        $httpCode = 201;

        $tokenHeader = $this->settings->get('jkn_mobile.header_token');
        $usernameHeader = $this->settings->get('jkn_mobile.header_username');
        $reqToken = $this->_getRequestHeader($tokenHeader);
        $reqUser = $this->_getRequestHeader($usernameHeader);

        if ($reqToken === null || $reqToken === '' || $reqToken === false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired or missing',
                    'code' => 201
                )
            );
            $httpCode = 201;
        } else if ((string)$reqToken === (string)$this->_getToken() && $reqUser !== null && (string)$reqUser === (string)$this->settings->get('jkn_mobile.x_username')) {
            $nopeserta = trim((string)($decode['nopeserta'] ?? ''));
            $cek_nopeserta = ($nopeserta !== '') ? $this->db('pasien')->where('no_peserta', $nopeserta)->oneArray() : null;
            $errors = [];

            if (empty($cek_nopeserta)) {
                $errors[] = 'Nomor peserta tidak ditemukan';
            }
            if ($nopeserta !== '' && !ctype_digit($nopeserta)) {
                $errors[] = 'Nomor kartu harus mengandung angka saja!!';
            }
            if ($nopeserta !== '' && mb_strlen($nopeserta, 'UTF-8') < 13) {
                $errors[] = 'Nomor kartu harus 13 digit';
            }
            if (!empty($errors)) {
                $response = array(
                    'metadata' => array(
                        'message' => implode(', ', $errors),
                        'code' => 201
                    )
                );
                $httpCode = 201;
            } else {
                $stmtOp = $this->db()->pdo()->prepare(
                    "SELECT booking_operasi.no_rawat AS kodebooking, booking_operasi.tanggal AS tanggaloperasi, paket_operasi.nm_perawatan AS jenistindakan, maping_poli_bpjs.kd_poli_bpjs AS kodepoli, poliklinik.nm_poli AS namapoli, booking_operasi.status AS terlaksana
                    FROM pasien, booking_operasi, paket_operasi, reg_periksa, jadwal, poliklinik, maping_poli_bpjs
                    WHERE booking_operasi.no_rawat = reg_periksa.no_rawat
                    AND pasien.no_rkm_medis = reg_periksa.no_rkm_medis
                    AND booking_operasi.kode_paket = paket_operasi.kode_paket
                    AND booking_operasi.kd_dokter = jadwal.kd_dokter
                    AND jadwal.kd_poli = poliklinik.kd_poli
                    AND jadwal.kd_poli = maping_poli_bpjs.kd_poli_rs
                    AND pasien.no_peserta = :nopeserta
                    GROUP BY booking_operasi.no_rawat"
                );
                $stmtOp->execute([':nopeserta' => $nopeserta]);
                $rows = $stmtOp->fetchAll(\PDO::FETCH_ASSOC);

                $data_array = [];
                foreach ($rows as $data) {
                    $data_array[] = array(
                        'kodebooking' => (string)$data['kodebooking'],
                        'tanggaloperasi' => (string)$data['tanggaloperasi'],
                        'jenistindakan' => (string)$data['jenistindakan'],
                        'kodepoli' => (string)$data['kodepoli'],
                        'namapoli' => (string)$data['namapoli'],
                        'terlaksana' => ($data['terlaksana'] == 'Menunggu') ? 0 : 1
                    );
                }
                $response = array(
                    'response' => array('list' => $data_array),
                    'metadata' => array('message' => 'Ok', 'code' => 200)
                );
                $httpCode = 200;
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            $httpCode = 201;
        }
        $this->_jsonResponse($response, $httpCode);
    }

    public function _getJadwal($kodepoli, $tanggal)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone'));

        $url = $this->bpjsurl.'jadwaldokter/kodepoli/'.$kodepoli.'/tanggal/'.$tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        $code = $json['metadata']['code'];
        $message = $json['metadata']['message'];
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
        if(!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        }
        if($json != null) {
          echo '{
                  "metaData": {
                      "code": "'.htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'",
                      "message": "'.htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"
                  },
                  "response": '.$decompress.'}';
        } else {
          echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        exit();
    }

    public function _getUpdateJadwal($data = [])
    {
        $data = [
            'kodepoli' => 'ANA',
            'kodesubspesialis' => '1',
            'kodedokter' => '123456',
            'jadwal' => [
              array(
                'hari' => '1',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '2',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '3',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '4',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '5',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '6',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
            ]
        ];

        $data = json_encode($data);
        $url = $this->bpjsurl.'updatejadwaldokter';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode(htmlspecialchars_array($json));
        exit();
    }

    public function _getAntreanAdd()
    {
        echo '
        <head>
        <meta http-equiv="refresh" content="30">
        </head>
        <title>Tambah antrian BPJS</title>
        </head>
        <body>
        <form action="" method="">
          Pilih tanggal: <input type="date" name="tgl">
          <input type="submit" value="Go">
        </form>
        ';
        $date = date('Y-m-d');
      	$perpage = 10;
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }

        $exclude_taskid = str_replace(",","','", $this->settings->get('jkn_mobile.exclude_taskid'));
        $checkAntrian = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim','Belum')->limit(10)->toArray();
        if (!$checkAntrian) {
          echo 'Disini '.'<br>';
          $exclude_taskid = str_replace(",","','", $this->settings->get('jkn_mobile.exclude_taskid'));
          $sqlquere = "SELECT pasien.no_peserta,pasien.no_rkm_medis,reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.kd_poli,reg_periksa.kd_pj
            FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis=pasien.no_rkm_medis INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter INNER JOIN poliklinik ON reg_periksa.kd_poli=poliklinik.kd_poli WHERE reg_periksa.tgl_registrasi='$date' AND reg_periksa.kd_poli NOT IN ('$exclude_taskid') AND reg_periksa.stts NOT IN ('Batal','Dirujuk','Dirawat')
            ORDER BY concat(reg_periksa.tgl_registrasi,' ',reg_periksa.jam_reg)";
          $query = $this->db()->pdo()->prepare($sqlquere);
          $query->execute();
          $query = $query->fetchAll(\PDO::FETCH_ASSOC);

          foreach ($query as $q) {
            echo 'Load Data '.$q['no_rkm_medis'].'<br>';
            $checkPJ = $this->db('mlite_antrian_referensi')->where('tanggal_periksa',  $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
            if(!$checkPJ) {
              $checkPJ = $this->db('mlite_antrian_referensi')->where('tanggal_periksa',  $date)->where('nomor_kartu', $q['no_peserta'])->oneArray();
            }
            if(!$checkPJ) {
              echo $q['no_rkm_medis'].' '.$q['no_peserta'].' Tidak Ada Data di mlite_antrian_ref di tanggal: '.$date.'<br>';
              $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $q['kd_poli'])->oneArray();
              $jenispasien = 'NON JKN';
              $jeniskunjungan = 3;
              if($q['kd_pj'] == $this->settings->get('jkn_mobile.kd_pj_bpjs')) {
                $jenispasien = 'JKN';
              }

              $nomorreferensi = convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];
              if($jenispasien == 'JKN') {
                $bridging_sep = $this->db('bridging_sep')->where('no_rawat', $q['no_rawat'])->oneArray();
                $nomorreferensi = $bridging_sep['no_rujukan'];
              }

              $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
              $kodebooking = $kode_ppk.convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];

              echo 'code...<br>';

              $this->db('mlite_antrian_referensi')->save([
                'tanggal_periksa' => $q['tgl_registrasi'],
                'no_rkm_medis' => $q['no_rkm_medis'],
                'nomor_kartu' => $q['no_peserta'],
                'nomor_referensi' => $nomorreferensi,
                'kodebooking' => $kodebooking,
                'jenis_kunjungan' => $jeniskunjungan,
                'status_kirim' => 'Belum'
              ]);
              echo $nomorreferensi.' <br>';

              $checkRM = $this->db('mlite_antrian_referensi')->where('no_rkm_medis', $q['no_rkm_medis'])->where('kodebooking', $kodebooking)->oneArray();
              if ($checkRM) {
                echo 'Berhasil Simpan '.$q['no_rkm_medis'].'<br>';
              }
            }
            echo '-----------------------------------------------------<br>';

          }
        }

        echo 'Menjalankan WS tambah antrian<br>';
        echo '-------------------------------------<br>';

        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];
        if($checkAntrian){
          foreach ($checkAntrian as $q) {
            $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
            $pasien = $this->db('pasien')->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
            $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
            $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
            $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();
            $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
            $minutes = $no_urut_reg * 10;
            $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));
            $jenispasien = 'NON JKN';
            if($reg_periksa['kd_pj'] == $this->settings->get('jkn_mobile.kd_pj_bpjs')) {
              $jenispasien = 'JKN';
            }
            $pasienbaru = '1';
            if($reg_periksa['stts_daftar'] == 'Lama') {
              $pasienbaru = '0';
            }

            $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_peserta'])->oneArray();
            if($jenispasien == 'NON JKN') {
              $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_rkm_medis'])->oneArray();
            }

            $nomorkartu = $q['nomor_kartu'];
            if($jenispasien == 'NON JKN') {
              $nomorkartu = '';
            }

            $nik = $pasien['no_ktp'];
            if($jenispasien == 'NON JKN') {
              $nik = '';
            }

            $nohp = $pasien['no_tlp'];
            if(empty($pasien['no_tlp'])) {
              $nohp = '0000000000';
            }
            if($jenispasien == 'NON JKN') {
              $nohp = '';
            }

            $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
            $kodebooking = $kode_ppk.convertNorawat($q['no_rawat']).''.$reg_periksa['no_reg'];
            $nomorreferensi = '';

            $data = [
              'kodebooking' => $q['kodebooking'],
              'jenispasien' => $jenispasien,
              'nomorkartu' => $nomorkartu,
              'nik' => $nik,
              'nohp' => $nohp,
              'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
              'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
              'pasienbaru' => $pasienbaru,
              'norm' => $q['no_rkm_medis'],
              'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
              'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
              'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
              'jampraktek' => substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5),
              'jeniskunjungan' => $q['jenis_kunjungan'],
              'nomorreferensi' => $q['nomor_referensi'],
              'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
              'angkaantrean' => $reg_periksa['no_reg'],
              'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kouta['jam_mulai']) * 1000,
              'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
              'kuotajkn' => intval($jadwaldokter['kuota']),
              'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
              'kuotanonjkn' => intval($jadwaldokter['kuota']),
              'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
            ];
            echo 'Request:<br>';
            echo "<pre>".print_r($data,true)."</pre>";
            $data = json_encode($data);
            $url = $this->bpjsurl.'antrean/add';
            $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
            $data = json_decode($output, true);
            echo 'Response:<br>';
            echo json_encode(htmlspecialchars_array($data))." ";
            echo $data['metadata']['code'];
            if($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208){

              $resep_obat = $this->db('resep_obat')
              ->where('tgl_peresepan', $q['tgl_registrasi'])
              ->where('no_rawat', $q['no_rawat'])
              ->where('status', 'ralan')
              ->oneArray();

              $jenisresep = 'Non Racikan';

              $resep_dokter_racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep_obat['no_resep'])->oneArray();
              if(!empty($resep_dokter_racikan)) {
                $jenisresep = 'Racikan';
              }

              $mlite_antrian_loket = $this->db('mlite_antrian_loket')
                ->where('type', 'Apotek')
                ->where('postdate', $q['tgl_registrasi'])
                ->where('no_rkm_medis', $q['no_rkm_medis'])
                ->where('end_time', '<>', '00:00:00')
                ->oneArray();

              $data_antrian_farmasi = [
                'kodebooking' => $q['kodebooking'],
                'jenisresep' => $jenisresep,
                'nomorantrean' => $mlite_antrian_loket['noantrian'],
                'keterangan' => 'Resep dibuat secara elektronik di poli.'
              ];

              echo 'Request:<br>';
              echo "<pre>".print_r($data_antrian_farmasi,true)."</pre>";
              $data_antrian_farmasi = json_encode($data_antrian_farmasi);
              $url = $this->bpjsurl.'antrean/farmasi/add';
              $output_farmasi = BpjsService::post($url, $data_antrian_farmasi, $this->consid, $this->secretkey, $this->user_key, NULL);
              $data_farmasi = json_decode($output_farmasi, true);
              echo 'Response:<br>';
              if(isset($data_farmasi['metadata']['code']) == 200 || isset($data_farmasi['metadata']['code']) == 208){
                echo 'Sukses kirim antrian farmasi';
              } else {
                echo 'Gagal kirim antrian farmasi. '.$data_farmasi['metadata']['code'].': '.$data_farmasi['metadata']['message'];
              }

              if($jenispasien == 'JKN') {
                if(!$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $reg_periksa['tgl_registrasi'])->where('nomor_kartu', $nomorkartu)->oneArray()) {
                $this->db('mlite_antrian_referensi')->save([
                  'tanggal_periksa' => $q['tgl_registrasi'],
                  'nomor_kartu' => $q['no_peserta'],
                  'nomor_referensi' => $nomorreferensi,
                  'kodebooking' => $kodebooking,
                  'jenis_kunjungan' => $jeniskunjungan,
                  'status_kirim' => 'Sudah',
                  'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                ]);
                } else {
                  $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                  'status_kirim' => 'Sudah',
                  'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                }
              }
              if($jenispasien == 'NON JKN') {
                if(!$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $reg_periksa['tgl_registrasi'])->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray()) {
                $this->db('mlite_antrian_referensi')->save([
                  'tanggal_periksa' => $q['tgl_registrasi'],
                  'nomor_kartu' => $q['no_rkm_medis'],
                  'nomor_referensi' => convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$reg_periksa['no_reg'],
                  'kodebooking' => $kodebooking,
                  'jenis_kunjungan' => $jeniskunjungan,
                  'status_kirim' => 'Sudah',
                  'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                ]);
                } else {
                  $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                    'status_kirim' => 'Sudah',
                    'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                }
              }
            }
            if($data['metadata']['code'] == 201){
              if($jenispasien == 'JKN') {
                if(!$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $reg_periksa['tgl_registrasi'])->where('nomor_kartu', $nomorkartu)->oneArray()) {
                  $this->db('mlite_antrian_referensi')->save([
                    'tanggal_periksa' => $q['tgl_registrasi'],
                    'nomor_kartu' => $q['no_peserta'],
                    'nomor_referensi' => $nomorreferensi,
                    'kodebooking' => $kodebooking,
                    'jenis_kunjungan' => $jeniskunjungan,
                    'status_kirim' => 'Gagal',
                    'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                } else {
                  $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                    'status_kirim' => 'Gagal',
                    'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                }
              }
              if($jenispasien == 'NON JKN') {
                if(!$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $reg_periksa['tgl_registrasi'])->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray()) {
                  $this->db('mlite_antrian_referensi')->save([
                    'tanggal_periksa' => $q['tgl_registrasi'],
                    'nomor_kartu' => $q['no_rkm_medis'],
                    'nomor_referensi' => $q['nomor_referensi'],
                    'kodebooking' => $kodebooking,
                    'jenis_kunjungan' => $jeniskunjungan,
                    'status_kirim' => 'Gagal',
                    'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                } else {
                  $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                    'status_kirim' => 'Gagal',
                    'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                  ]);
                }
              }
            }
            echo '<br>-------------------------------------<br><br>';
          }
        }
        echo '
        </body>
        </html>
        ';
        exit();
    }

    public function _getAntreanAddAusy()
    {
        $slug = parseURL();
        $date = date('Y-m-d');
        $page = 0;
        $offset = 1;
      	$perpage = 10;
        if(!empty($slug['3'])) {
          $page = $slug['3'];
          $offset = ($page - 1) * $perpage;
        }
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }
      
      	$checkAntrian = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim','Belum')->limit(10)->toArray();
        if (!$checkAntrian) {
          	echo 'Disini '.'<br>';
            # code...
            $exclude_taskid = str_replace(",","','", $this->settings->get('jkn_mobile.exclude_taskid'));
          	$sqlquere = "SELECT pasien.no_peserta,pasien.no_rkm_medis,reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.kd_poli,reg_periksa.kd_pj
            FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis=pasien.no_rkm_medis INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter INNER JOIN poliklinik ON reg_periksa.kd_poli=poliklinik.kd_poli WHERE reg_periksa.tgl_registrasi='$date' AND reg_periksa.kd_poli NOT IN ('$exclude_taskid') AND reg_periksa.stts NOT IN ('Batal','Dirujuk','Dirawat')
            ORDER BY concat(reg_periksa.tgl_registrasi,' ',reg_periksa.jam_reg)";
            $query = $this->db()->pdo()->prepare($sqlquere);
            $query->execute();
        	$query = $query->fetchAll(\PDO::FETCH_ASSOC);
          	foreach ($query as $q) {
              echo 'Load Data '.$q['no_rkm_medis'].'<br>';
              $checkPJ = $this->db('mlite_antrian_referensi')->where('tanggal_periksa',  $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
              if(!$checkPJ) {
                $checkPJ = $this->db('mlite_antrian_referensi')->where('tanggal_periksa',  $date)->where('nomor_kartu', $q['no_peserta'])->oneArray();
              }
              if(!$checkPJ) {
                  echo $q['no_rkm_medis'].' '.$q['no_peserta'].' Tidak Ada Data di mlite_antrian_ref di tanggal: '.$date.'<br>';
                  $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $q['kd_poli'])->oneArray();
                  $jenispasien = 'NON JKN';
                  $jeniskunjungan = 3;
                  if($q['kd_pj'] == $this->settings->get('jkn_mobile.kd_pj_bpjs')) {
                    $jenispasien = 'JKN';
                  }

                  $nomorreferensi = convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];
                  if($jenispasien == 'JKN') {
                      $bridging_sep = $this->db('bridging_sep')->where('no_rawat', $q['no_rawat'])->oneArray();
                      $nomorreferensi = $bridging_sep['no_rujukan'];
                  }
                
                  $q['no_reg'] = str_replace('OP','',$q['no_reg']);
                  $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
              	  $kodebooking = $kode_ppk.convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];

                    
                    echo 'code...<br>';
                    
                    $this->db('mlite_antrian_referensi')->save([
                        'tanggal_periksa' => $q['tgl_registrasi'],
                        'no_rkm_medis' => $q['no_rkm_medis'],
                        'nomor_kartu' => $q['no_peserta'],
                        'nomor_referensi' => $nomorreferensi,
                        'kodebooking' => $kodebooking,
                        'jenis_kunjungan' => $jeniskunjungan,
                        'status_kirim' => 'Belum'
                    ]);
                    echo $nomorreferensi.' <br>';
                
                  
                  $checkRM = $this->db('mlite_antrian_referensi')->where('no_rkm_medis', $q['no_rkm_medis'])->where('kodebooking', $kodebooking)->oneArray();
                  if ($checkRM) {
                      echo 'Berhasil Simpan '.$q['no_rkm_medis'].'<br>';
                  }
              }
                        	echo '-----------------------------------------------------<br>';
	        }

        }
        echo 'Menjalankan WS tambah antrian tanggal '.$date.'<br>';
        echo '-------------------------------------<br>';

        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];
		if($checkAntrian){
        foreach ($checkAntrian as $q) {
            //if(!$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_peserta'])->oneArray() || !$this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_rkm_medis'])->oneArray()) {
              $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
              $pasien = $this->db('pasien')->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
              $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
              $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
              $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();
              $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
              $minutes = $no_urut_reg * 10;
              $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));
              $jenispasien = 'NON JKN';
              if($reg_periksa['kd_pj'] == $this->settings->get('jkn_mobile.kd_pj_bpjs')) {
                $jenispasien = 'JKN';
              }
              $pasienbaru = '1';
              if($reg_periksa['stts_daftar'] == 'Lama') {
                $pasienbaru = '0';
              }

              $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_peserta'])->oneArray();
              if($jenispasien == 'NON JKN') {
                $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_kartu', $q['no_rkm_medis'])->oneArray();
              }

              $nomorkartu = $q['nomor_kartu'];
              if($jenispasien == 'NON JKN') {
                $nomorkartu = '';
              }

              $nik = $pasien['no_ktp'];
              if($jenispasien == 'NON JKN') {
                $nik = '';
              }

              $nohp = $pasien['no_tlp'];
              if(empty($pasien['no_tlp'])) {
                $nohp = '0000000000';
              }
              if($jenispasien == 'NON JKN') {
                $nohp = '';
              }

              $reg_periksa['no_reg'] = str_replace('OP','',$reg_periksa['no_reg']);
                $data = [
                    'kodebooking' => $q['kodebooking'],
                    'jenispasien' => $jenispasien,
                    'nomorkartu' => $nomorkartu,
                    'nik' => $nik,
                    'nohp' => $nohp,
                    'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
                    'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
                    'pasienbaru' => $pasienbaru,
                    'norm' => $q['no_rkm_medis'],
                    'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
                    'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
                    'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
                    'jampraktek' => substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5),
                    'jeniskunjungan' => $q['jenis_kunjungan'],
                    'nomorreferensi' => $q['nomor_referensi'],
                    'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
                    'angkaantrean' => $reg_periksa['no_reg'],
                    'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kouta['jam_mulai']) * 1000,
                    'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                    'kuotajkn' => intval($jadwaldokter['kuota']),
                    'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                    'kuotanonjkn' => intval($jadwaldokter['kuota']),
                    'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
                ];
                echo 'Request:<br>';
                echo "<pre>".print_r($data,true)."</pre>";
                $data = json_encode($data);
                $url = $this->bpjsurl.'antrean/add';
                $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                $data = json_decode($output, true);
                echo 'Response:<br>';
                echo json_encode(htmlspecialchars_array($data))." ";
                echo $data['metadata']['code'];
                if($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208){
                  if($jenispasien == 'JKN') {
                    
                      $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                          'status_kirim' => 'Sudah',
                          'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                      ]);
                    
                  }
                  if($jenispasien == 'NON JKN') {
                    
                      $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                          'status_kirim' => 'Sudah',
                          'keterangan' => $data['metadata']['message']
                      ]);
                    
                  }
                }
              	if($data['metadata']['code'] == 201){
                  if($jenispasien == 'JKN') {
                    
                      $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                          'status_kirim' => 'Gagal',
                          'keterangan' => htmlspecialchars($data['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                      ]);
                    
                  }
                  if($jenispasien == 'NON JKN') {
                    
                      $this->db('mlite_antrian_referensi')->where('nomor_referensi', $q['nomor_referensi'])->save([
                          'status_kirim' => 'Gagal',
                          'keterangan' => $data['metadata']['message']
                      ]);
                    
                  }
                }
                echo '<br>-------------------------------------<br><br>';
        }}
      	$_page = $page + 1;
      	$page_ = $page - 1;
        
        exit();
    }

    public function _getAntreanBatal()
    {
        echo '
        <head>
         <meta http-equiv="refresh" content="30">
        </head>
        <title>Batal Antrian BPJS</title>
        </head>
        <body>
        <form action="" method="">
          Pilih tanggal: <input type="date" name="tgl">
          <input type="submit" value="Go">
        </form>
        ';

        $date = date('Y-m-d');
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }

        $query = $this->db('mlite_antrian_referensi_batal')
          ->where('tanggal_batal', $date)
          ->toArray();

        echo 'Menjalankan WS batal antrian Mobile JKN BPJS<br>';
        echo '-------------------------------------<br>';

        foreach ($query as $q) {
            $data = [
                'kodebooking' => $q['nomor_referensi'],
                'keterangan' => $q['keterangan']
            ];
            $data = json_encode($data);
            echo 'Request:<br>';
            echo $data;

            echo '<br>';
            $url = $this->bpjsurl.'antrean/batal';
            $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
            $json = json_decode($output, true);
            echo 'Response:<br>';
            echo json_encode(htmlspecialchars_array($json));

            echo '<br>-------------------------------------<br><br>';
        }

        echo '
        </body>
        </html>
        ';

        exit();
    }

    public function _getAntreanUpdateWaktu()
    {
        $date = trim($_GET['tgl'] ?? date('Y-m-d'));
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $format = strtolower(trim($_GET['format'] ?? 'html'));
        $versi = strtolower(trim($_GET['versi'] ?? 'v3'));
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 25;
        $refresh = isset($_GET['refresh']) ? max(10, (int)$_GET['refresh']) : 180; // default auto-refresh 3 menit

        $admin = new \Plugins\JKN_Mobile\Admin($this->core);
        $admin->init();

        if ($format === 'json') {
            $_REQUEST['tanggal'] = $date;
            $_REQUEST['versi'] = $versi;
            $_REQUEST['limit'] = $limit;
            $admin->anyAutoSyncTaskID();
            exit();
        }

        // Ambil data antrean hari ini
        $bookings = $this->db('mlite_antrian_referensi')
            ->where('tanggal_periksa', $date)
            ->toArray();

        $total_antrol = count($bookings);
        $processed = 0;
        $cycle_results = [];
        $total_success = 0;
        $total_fail = 0;
        $total_skip = 0;

        foreach ($bookings as $b) {
            $kb = $b['kodebooking'] ?: $b['nomor_referensi'];
            if (empty($kb)) continue;
            if (stripos($b['status'] ?? '', 'Batal') !== false) continue;

            // Cek apakah antrean ini masih butuh sync
            $pending_count = $this->db('mlite_antrian_referensi_taskid')
                ->where('nomor_referensi', $kb)
                ->where('status', 'Belum')
                ->count();
            $total_tasks = $this->db('mlite_antrian_referensi_taskid')
                ->where('nomor_referensi', $kb)
                ->count();

            // Jika sudah ada 5/7 task dan tidak ada yang 'Belum', lewati
            if ($total_tasks >= 5 && $pending_count == 0) {
                continue;
            }

            // Jalankan sinkronisasi
            $res = $admin->syncSingleBooking($kb, $versi);
            $processed++;
            $s = $res['summary']['success'] ?? 0;
            $f = $res['summary']['failed'] ?? 0;
            $sk = $res['summary']['skipped'] ?? 0;
            $total_success += $s;
            $total_fail += $f;
            $total_skip += $sk;

            $pasien = $this->db('pasien')->where('no_rkm_medis', $b['no_rkm_medis'])->oneArray();
            $nm = $pasien['nm_pasien'] ?? ($b['nomor_kartu'] ?: '-');

            $cycle_results[] = [
                'kodebooking' => $kb,
                'no_rkm_medis' => $b['no_rkm_medis'],
                'nm_pasien' => $nm,
                'summary' => $res['summary'] ?? [],
                'results' => $res['results'] ?? []
            ];

            if ($processed >= $limit) {
                break;
            }
        }

        // Hitung statistik keseluruhan hari ini
        $all_tasks_stmt = $this->db()->pdo()->prepare("SELECT status, count(*) as jml FROM mlite_antrian_referensi_taskid WHERE tanggal_periksa = ? GROUP BY status");
        $all_tasks_stmt->execute([$date]);
        $stats = ['Sudah' => 0, 'Belum' => 0, 'Gagal' => 0];
        while ($st = $all_tasks_stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats[$st['status']] = (int)$st['jml'];
        }

        $now_str = date('Y-m-d H:i:s');
        $next_refresh = $refresh;

        echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="' . $next_refresh . '">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Background Auto-Sync Task ID BPJS Antrean - mLite</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; font-size: 13px; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 16px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .header h1 { font-size: 20px; font-weight: 700; color: #38bdf8; display: flex; align-items: center; gap: 8px; }
        .header .meta { font-size: 12px; color: #94a3b8; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .card { background: #1e293b; border-radius: 8px; padding: 16px; border: 1px solid #334155; }
        .card .title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600; margin-bottom: 6px; }
        .card .value { font-size: 24px; font-weight: 700; color: #f8fafc; }
        .card.card-green .value { color: #4ade80; }
        .card.card-yellow .value { color: #facc15; }
        .card.card-red .value { color: #f87171; }
        .card.card-blue .value { color: #38bdf8; }
        .controls { background: #1e293b; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; border: 1px solid #334155; }
        .controls form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        input[type="date"], select { background: #0f172a; border: 1px solid #475569; color: #f8fafc; padding: 6px 10px; border-radius: 4px; font-size: 12px; }
        button, .btn { background: #0284c7; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        button:hover, .btn:hover { background: #0369a1; }
        .panel { background: #1e293b; border-radius: 8px; border: 1px solid #334155; overflow: hidden; margin-bottom: 20px; }
        .panel-heading { background: #334155; padding: 10px 16px; font-weight: 600; font-size: 13px; color: #f8fafc; display: flex; justify-content: space-between; align-items: center; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #1e293b; color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-green { background: #15803d; color: #dcfce7; }
        .badge-yellow { background: #a16207; color: #fef9c3; }
        .badge-red { background: #b91c1c; color: #fee2e2; }
        .badge-gray { background: #475569; color: #e2e8f0; }
        .pill-task { display: inline-block; width: 18px; height: 18px; line-height: 18px; text-align: center; border-radius: 50%; font-size: 9px; font-weight: bold; margin-right: 3px; }
        .pill-done { background: #22c55e; color: #000; }
        .pill-skip { background: #475569; color: #cbd5e1; }
        .pill-fail { background: #ef4444; color: #fff; }
        .pulse { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px #22c55e; margin-right: 6px; animation: pulse-anim 2s infinite; }
        @keyframes pulse-anim { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><span class="pulse"></span> Background Auto-Sync Task ID BPJS Antrean</h1>
            <div class="meta">Server Sync Engine • mLite RSGM • Eksekusi Terakhir: <b>' . $now_str . '</b></div>
        </div>
        <div class="meta" style="text-align:right;">
            Auto-refresh dalam: <b id="countdownTimer">' . $next_refresh . 's</b> • Siklus: <b>' . ($refresh / 60) . ' Menit</b><br>
            <a href="' . url([ADMIN, 'jkn_mobile', 'bookingantrol']) . '" class="btn" style="background:#475569; padding:4px 10px; margin-top:4px;">&larr; Kembali ke Admin</a>
        </div>
    </div>

    <div class="cards">
        <div class="card card-blue">
            <div class="title">Total Antrean Hari Ini</div>
            <div class="value">' . $total_antrol . '</div>
        </div>
        <div class="card card-green">
            <div class="title">Task ID Terkirim (Sudah)</div>
            <div class="value">' . $stats['Sudah'] . '</div>
        </div>
        <div class="card card-yellow">
            <div class="title">Task ID Menunggu (Belum)</div>
            <div class="value">' . $stats['Belum'] . '</div>
        </div>
        <div class="card card-red">
            <div class="title">Task ID Gagal Kirim</div>
            <div class="value">' . $stats['Gagal'] . '</div>
        </div>
    </div>

    <div class="controls">
        <form method="GET" action="">
            <label style="font-weight:600; color:#cbd5e1;">Tanggal Antrean:</label>
            <input type="date" name="tgl" value="' . htmlspecialchars($date) . '">
            <label style="font-weight:600; color:#cbd5e1;">Interval Auto-Refresh:</label>
            <select name="refresh">
                <option value="60"' . ($refresh == 60 ? ' selected' : '') . '>1 Menit</option>
                <option value="120"' . ($refresh == 120 ? ' selected' : '') . '>2 Menit</option>
                <option value="180"' . ($refresh == 180 ? ' selected' : '') . '>3 Menit (Direkomendasikan)</option>
                <option value="300"' . ($refresh == 300 ? ' selected' : '') . '>5 Menit</option>
            </select>
            <button type="submit">Terapkan &amp; Sinkronkan Sekarang</button>
        </form>
        <div>
            <span class="badge badge-green">Engine Aktif</span>
            <a href="?tgl=' . urlencode($date) . '&format=json" target="_blank" style="color:#38bdf8; margin-left:10px; text-decoration:none; font-size:11px;">[Output JSON API]</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <span>Aktivitas Sinkronisasi Siklus Ini (' . $processed . ' Pasien Diproses)</span>
            <span style="font-size:11px; font-weight:normal; color:#cbd5e1;">
                +' . $total_success . ' Task Sukses, ' . $total_skip . ' Dilewati (Belum ada waktu), ' . $total_fail . ' Gagal
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Kode Booking</th>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Rincian Task ID (1 s/d 7)</th>
                        <th>Status Siklus Ini</th>
                    </tr>
                </thead>
                <tbody>';

        if (empty($cycle_results)) {
            echo '<tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                <b style="color:#f8fafc; font-size:14px;">Semua Antrean Sudah Tersinkronkan!</b><br>
                Tidak ada data antrean yang tertunda untuk tanggal ' . htmlspecialchars($date) . '. Sistem akan terus memantau setiap siklus refresh.
            </td></tr>';
        } else {
            $no = 1;
            foreach ($cycle_results as $cr) {
                echo '<tr>';
                echo '<td>' . $no++ . '</td>';
                echo '<td><b style="color:#38bdf8;">' . htmlspecialchars($cr['kodebooking']) . '</b></td>';
                echo '<td>' . htmlspecialchars($cr['no_rkm_medis']) . '</td>';
                echo '<td>' . htmlspecialchars($cr['nm_pasien']) . '</td>';
                echo '<td><div style="display:flex; gap:4px; align-items:center;">';
                if (!empty($cr['results'])) {
                    foreach ($cr['results'] as $r) {
                        $cls = ($r['status'] === 'Sudah') ? 'pill-done' : (($r['status'] === 'Gagal') ? 'pill-fail' : 'pill-skip');
                        $tTitle = 'Task ' . $r['taskid'] . ': ' . ($r['message'] ?? $r['status']);
                        echo '<span class="pill-task ' . $cls . '" title="' . htmlspecialchars($tTitle) . '">' . $r['taskid'] . '</span>';
                    }
                } else {
                    echo '<span style="color:#94a3b8; font-size:11px;">Tidak ada perubahan</span>';
                }
                echo '</div></td>';
                $s_badge = ($cr['summary']['failed'] > 0) ? 'badge-red' : (($cr['summary']['success'] > 0) ? 'badge-green' : 'badge-gray');
                $s_label = ($cr['summary']['failed'] > 0) ? 'Ada Gagal' : (($cr['summary']['success'] > 0) ? 'Tersinkron' : 'Belum Ada Aksi');
                echo '<td><span class="badge ' . $s_badge . '">' . $s_label . '</span></td>';
                echo '</tr>';
            }
        }

        echo '</tbody>
            </table>
        </div>
    </div>
</div>

<script>
    var timeLeft = ' . $next_refresh . ';
    var timerEl = document.getElementById("countdownTimer");
    setInterval(function() {
        timeLeft--;
        if (timeLeft <= 0) {
            timerEl.innerText = "Merefresh...";
        } else {
            timerEl.innerText = timeLeft + "s";
        }
    }, 1000);
</script>
</body>
</html>';

        exit();
    }

        public function _getAntreanUpdateWaktuAusy()
    {
        //header("Refresh:55");
        $date = date('Y-m-d');
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }

        $slug = parseURL();
        $page = '';
        $offset = 1;
      	$perpage = 10;
        if(!empty($slug['3'])) {
          $page = $slug['3'];
          $offset = ($page - 1) * $perpage;
        }
        $query = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa',$date)->where('nomor_referensi','!=','')->where('status','Belum')->where('keterangan','')->limit(5)->toArray();
        if (!$query) {
            # code...
            $sql = "SELECT * FROM mlite_antrian_referensi WHERE status_kirim = 'Sudah' AND tanggal_periksa = '$date' AND updatewaktu IS NULL LIMIT $perpage";
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute();
            $query = $stmt->fetchAll();

            echo 'Menjalankan WS taskid (1) mulai tunggu admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 1)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                        $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                    if($mlite_antrian_loket){
                        date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 1,
                            'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 1)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 1 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (2) mulai pelayanan admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 2)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                    if($mlite_antrian_loket){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 2,
                            'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 2)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 2 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (3) mulai tunggu poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', 3)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                  	echo $q['no_rkm_medis'].'<br>';
                    $mutasi_berkas = $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '>', '1970-01-01 00:00:00')->oneArray();
                    if($mutasi_berkas){
                            echo 'A ';
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 3,
                            'waktu' => strtotime($mutasi_berkas['dikirim']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 3)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (4) mulai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 4)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mutasi_berkas = $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $reg_periksa['no_rawat'])->where('diterima', '>', '1970-01-01 00:00:00')->oneArray();
                    if($mutasi_berkas){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 4,
                            'waktu' => strtotime($mutasi_berkas['diterima']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Terima'.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (5) selesai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 5)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                    if($pemeriksaan_ralan){
                        echo 'A';
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 5,
                            'waktu' => strtotime($pemeriksaan_ralan['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (6) permintaan resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 6)->oneArray()) {
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();

                    if($resep_obat){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 6,
                            'waktu' => strtotime($resep_obat['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 6)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 6 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (7) validasi resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 7)->oneArray()) {
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $reg_periksa['no_rawat'])->where('concat(tgl_perawatan," ",jam)', '<>', 'concat(tgl_peresepan," ",jam_peresepan)')->oneArray();
                    if($resep_obat){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 7,
                            'waktu' => strtotime($resep_obat['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 7)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 7 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }
          	foreach ($query as $q) {
                $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('nomor_kartu', $q['nomor_kartu'])->save('updatewaktu','Ya');
            }

        } else { // Ketika Gagal Kirim , Kirim Lagi *****************************************************************
            echo 'Menjalankan WS taskid (1) mulai tunggu admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {

                $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)->oneArray();
                if($mlite_antrian_loket){

                    $data = [
                        'kodebooking' => $q['nomor_referensi'],
                        'taskid' => 1,
                        'waktu' => $q['waktu'],
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }

            }

            echo 'Menjalankan WS taskid (2) mulai pelayanan admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {

                $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)->oneArray();
                if($mlite_antrian_loket){
                    $data = [
                        'kodebooking' => $q['nomor_referensi'],
                        'taskid' => 2,
                        'waktu' => $q['waktu']
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }

            }

            echo 'Menjalankan WS taskid (3) mulai tunggu poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {

                $mutasi_berkas = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)->oneArray();
                if($mutasi_berkas){
                    $data = [
                        'kodebooking' => $mutasi_berkas['nomor_referensi'],
                        'taskid' => 3,
                        'waktu' => $mutasi_berkas['waktu']
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }

            }

            echo 'Menjalankan WS taskid (4) mulai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {

                $mutasi_berkas = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)->oneArray();
                if($mutasi_berkas){
                    $data = [
                        'kodebooking' => $mutasi_berkas['nomor_referensi'],
                        'taskid' => 4,
                        'waktu' => $mutasi_berkas['waktu']
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }
            }

            echo 'Menjalankan WS taskid (5) selesai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {

                $pemeriksaan_ralan = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)->oneArray();
                if($pemeriksaan_ralan){
                    $data = [
                        'kodebooking' => $pemeriksaan_ralan['nomor_referensi'],
                        'taskid' => 5,
                        'waktu' => $pemeriksaan_ralan['waktu']
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }

            }

            echo 'Menjalankan WS taskid (6) permintaan resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                $resep_obat = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)->oneArray();

                if($resep_obat){
                    $data = [
                        'kodebooking' => $q['nomor_referensi'],
                        'taskid' => 6,
                        'waktu' => $q['waktu'],
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }
            }

            echo 'Menjalankan WS taskid (7) validasi resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                $resep_obat = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)->oneArray();
                if($resep_obat){
                    $data = [
                        'kodebooking' => $q['nomor_referensi'],
                        'taskid' => 7,
                        'waktu' => $q['waktu']
                    ];
                    $data = json_encode($data);
                    echo 'Request:<br>';
                    echo $data;

                    echo '<br>';
                    $url = $this->bpjsurl.'antrean/updatewaktu';
                    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                    $json = json_decode($output, true);
                    echo 'Response:<br>';
                    echo json_encode(htmlspecialchars_array($json));
                    if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208){
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    } else {
                        $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => htmlspecialchars($json['metadata']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    ]);
                    }
                    echo '<br>-------------------------------------<br><br>';
                }
            }
        }

        exit();
    }

    public function _getAntreanGetListTask()
    {
        header('Content-Type: application/json');
        $slug = parseURL();
        $data = [
            'kodebooking' => $slug['3']
        ];

        $data = json_encode($data);
        $url = $this->bpjsurl.'antrean/getlisttask';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        $decompress = '""';
        if ($json['metadata']['code'] == 200) {
            date_default_timezone_set('UTC');
            $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
            $key = $this->consid.$this->secretkey.$tStamp;
            # code...
            $stringDecrypt = stringDecrypt($key, $json['response']);
            if(!empty($stringDecrypt)) {
                $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
            }
        }
        echo json_decode(json_encode($decompress,JSON_PRETTY_PRINT));
        exit();
    }

    public function _getAntreanWaktuTunggu()
    {
        $slug = parseURL();
        $url = $this->bpjsurl.'dashboard/waktutunggu/bulan/'.$slug[3].'/tahun/'.$slug[4].'/waktu/'.$slug[5];
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode(htmlspecialchars_array($json));
        exit();
    }

    public function _getAntreanWaktuTungguTanggal()
    {
        $slug = parseURL();
        $url = $this->bpjsurl.'dashboard/waktutunggu/tanggal/'.$slug[3].'/waktu/'.$slug[4];
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode(htmlspecialchars_array($json));
        exit();
    }

    function getListAntrol($tanggal) {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone'));
        $url = $this->bpjsurl.'antrean/pendaftaran/tanggal/'.$tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        $code = $json['metadata']['code'];
        $message = $json['metadata']['message'];
        $decompress = '""';
        if ($code == 200) {
            # code...
            $stringDecrypt = stringDecrypt($key, $json['response']);
            if(!empty($stringDecrypt)) {
              $decompress = decompress($stringDecrypt);
            }
        }
        if($json != null) {
          echo '{
                  "metaData": {
                      "code": "'.$code.'",
                      "message": "'.$message.'"
                  },
                  "response": '.$decompress.'}';
        } else {
          echo '{
                  "metaData": {
                      "code": "5000",
                      "message": "ERROR"
                  },
                  "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
        }
        exit();

    }

    public function getAplicareManajemen()
    {
        exit();
    }

    public function getAplicare()
    {
        $slug = parseURL();
        echo $this->_resultBed($slug[2],isset_or($slug[3],''));
        //slug[2] => kelas BPJS , slug[3] => kode ruang / kode bangsal
        exit();
    }

    private function checkBed($kelas,$bangsal = ''){
        $bed = array();
        $sql = "SELECT bangsal.nm_bangsal ,kamar.kd_bangsal, COUNT(kamar.kd_kamar) as jml , SUM(IF(kamar.status = 'ISI',1,0)) as isi , SUM(IF(kamar.status = 'KOSONG',1,0)) as kosong  FROM kamar JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal WHERE kamar.statusdata = '1'";
        $sql .= " AND kamar.kelas = 'Kelas $kelas' AND kamar.kd_bangsal LIKE '%$bangsal%'";
        $sql .= " GROUP BY kamar.kd_bangsal ";
        $query = $this->db()->pdo()->prepare($sql);
        $query->execute();
        $bedlist = $query->fetchAll();
        foreach ($bedlist as $value) {
            switch ($kelas) {
                case '1':
                    $value['kelas'] = 'KL1';
                    break;
                case '2':
                    $value['kelas'] = 'KL2';
                    break;
                case '3':
                    $value['kelas'] = 'KL3';
                    break;
                case 'vip':
                    $value['kelas'] = 'VIP';
                    break;
                case 'icu':
                    $value['kelas'] = 'ICU';
                    break;
                case 'nicu':
                    $value['kelas'] = 'NIC';
                    break;
                case 'picu':
                    $value['kelas'] = 'PIC';
                    break;
                case 'hcu':
                    $value['kelas'] = 'HCU';
                    break;

                default:
                    # code...
                    break;
            }
            $bed[] = $value;
        }
        return $bed;
    }

    private function _resultBed($slug,$slug2 = '')
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
        $BpjsApiUrl = parse_url($this->settings->get('settings.BpjsApiUrl'));
        $url = $BpjsApiUrl['scheme'].'://'.$BpjsApiUrl['host'];
        if ($slug == 'listkamar') {
            $url .= "/aplicaresws/rest/bed/read/".$kode_ppk."/1/100";
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
            echo $output;
        }
        if ($slug == 'delkamar') {
            $whatIWant = substr($slug2, strpos($slug2, "-") + 1);
            $first = strtok($slug2, '-');
            $url .= "/aplicaresws/rest/bed/delete/".$kode_ppk;
            $beds = array('kodekelas'=>$first,'koderuang'=>$whatIWant);
            $data = array(
                'kodekelas'=>$beds['kodekelas'],
                'koderuang'=>$beds['koderuang']
            );
            $postdata = json_encode($data);
            $output = BpjsService::postAplicare($url, $postdata, $this->consid, $this->secretkey, $this->user_key, $tStamp);
            echo $output;
            echo '<br>';
        }
        if ($slug == 'addkamar') {
            $whatIWant = substr($slug2, strpos($slug2, "-") + 1);
            $first = strtok($slug2, '-');
            $bed = $this->checkBed($first,$whatIWant);
            $url .= "/aplicaresws/rest/bed/create/".$kode_ppk;
            foreach ($bed as $value) {
                $data = array(
                    'kodekelas'=>$value['kelas'],
                    'koderuang'=>$value['kd_bangsal'],
                    'namaruang'=>$value['nm_bangsal'],
                    'kapasitas'=>$value['jml'],
                    'tersedia'=>$value['kosong'],
                    'tersediapria'=>"0",
                    'tersediawanita'=>"0",
                    'tersediapriawanita'=>$value['kosong']
                );
                $postdata = json_encode($data);
                $output = BpjsService::postAplicare($url, $postdata, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                echo $output;
            }
        }
        if ($slug != 'listkamar') {
            $bed = $this->checkBed($slug);
            $url .= "/aplicaresws/rest/bed/update/".$kode_ppk;
            foreach ($bed as $value) {
                $data = array(
                    'kodekelas'=>$value['kelas'],
                    'koderuang'=>$value['kd_bangsal'],
                    'namaruang'=>$value['nm_bangsal'],
                    'kapasitas'=>$value['jml'],
                    'tersedia'=>$value['kosong'],
                    'tersediapria'=>"0",
                    'tersediawanita'=>"0",
                    'tersediapriawanita'=>$value['kosong']
                );
                $postdata = json_encode($data);
                $output = BpjsService::postAplicare($url, $postdata, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                echo $output;
            }
        }
        exit();
    }

    private function _getToken()
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(['username' => $this->settings->get('jkn_mobile.x_username'), 'password' => $this->settings->get('jkn_mobile.x_password'), 'date' => strtotime(date('Y-m-d')) * 1000]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }

    private function _getErrors($error)
    {
        $errors = $error;
        return $errors;
    }

    private function _setUmur($tanggal)
    {
        list($cY, $cm, $cd) = explode('-', date('Y-m-d'));
        list($Y, $m, $d) = explode('-', date('Y-m-d', strtotime($tanggal)));
        $umur = $cY - $Y;
        return $umur;
    }

    private function is_jwt_valid($jwt, $secret = 'abC123!') {
        // split the jwt
        $tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signature_provided = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
        $expiration = json_decode($payload)->exp;
        $is_token_expired = ($expiration - time()) < 0;

        // build a signature based on the header and payload using the secret
        $base64_url_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64_url_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64_url_header . "." . $base64_url_payload, $secret , true);
        $base64_url_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // verify it matches the signature provided in the jwt
        $is_signature_valid = ($base64_url_signature === $signature_provided);

        if ($is_token_expired || !$is_signature_valid) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}

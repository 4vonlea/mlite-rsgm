<?php

namespace Plugins\JKN_Mobile;

use Systems\AdminModule;
use Systems\Lib\BpjsService;
use LZCompressor\LZString;

class Admin extends AdminModule
{

    protected $consid;
    protected $secretkey;
    protected $bpjsurl;
    protected $user_key;
    protected $assign;

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
            'Kelola' => 'manage',
            'WS BPJS' => 'wsbpjs',
            'WS RS' => 'wsrs',
            'Katalog' => 'index',
            'Mapping Poliklinik' => 'mappingpoli',
            'Add Mapping Poliklinik' => 'addmappingpoli',
            'Mapping Dokter' => 'mappingdokter',
            'Add Mapping Dokter' => 'addmappingdokter',
            'Jadwal Dokter HFIS' => 'jadwaldokter',
            'Data Booking Antrol' => 'bookingantrol',
            'Task ID' => 'taskid',
            'Quality Rate' => 'qrantrol',
            'Dashboard Antrol BPJS' => 'antrol',
            'Pengaturan' => 'settings',
        ];
    }

    public function getManage()
    {
      $sub_modules = [
        ['name' => 'Katalog', 'url' => url([ADMIN, 'jkn_mobile', 'index']), 'icon' => 'tasks', 'desc' => 'Index JKN Mobile'],
        ['name' => 'WS BPJS', 'url' => url([ADMIN, 'jkn_mobile', 'wsbpjs']), 'icon' => 'tasks', 'desc' => 'WS BPJS JKN Mobile'],
        ['name' => 'WS RS', 'url' => url([ADMIN, 'jkn_mobile', 'wsrs']), 'icon' => 'tasks', 'desc' => 'WS RS JKN Mobile'],
        ['name' => 'Mapping Poliklinik', 'url' => url([ADMIN, 'jkn_mobile', 'mappingpoli']), 'icon' => 'tasks', 'desc' => 'Mapping Poliklinik JKN Mobile'],
        ['name' => 'Add Mapping Poliklinik', 'url' => url([ADMIN, 'jkn_mobile', 'addmappingpoli']), 'icon' => 'tasks', 'desc' => 'Add mapping poliklinik JKN Mobile'],
        ['name' => 'Mapping Dokter', 'url' => url([ADMIN, 'jkn_mobile', 'mappingdokter']), 'icon' => 'tasks', 'desc' => 'Mapping Dokter JKN Mobile'],
        ['name' => 'Add Mapping Dokter', 'url' => url([ADMIN, 'jkn_mobile', 'addmappingdokter']), 'icon' => 'tasks', 'desc' => 'Add Mapping Dokter JKN Mobile'],
        ['name' => 'Jadwal Dokter HFIS', 'url' => url([ADMIN, 'jkn_mobile', 'jadwaldokter']), 'icon' => 'tasks', 'desc' => 'Jadwal Dokter HFIS JKN Mobile'],
        ['name' => 'Data Booking Antrol', 'url' => url([ADMIN, 'jkn_mobile', 'bookingantrol']), 'icon' => 'list', 'desc' => 'Booking Antrol JKN Mobile'],
        ['name' => 'Task ID', 'url' => url([ADMIN, 'jkn_mobile', 'taskid']), 'icon' => 'tasks', 'desc' => 'Task ID JKN Mobile'],
        ['name' => 'Log Task ID', 'url' => url([ADMIN, 'jkn_mobile', 'logtaskid']), 'icon' => 'tasks', 'desc' => 'Log Task ID JKN Mobile'],
        ['name' => 'Quality Rate', 'url' => url([ADMIN, 'jkn_mobile', 'qrantrol']), 'icon' => 'tasks', 'desc' => 'Quality Rate Antrian Online BPJS'],
        ['name' => 'Dashboard Antrol BPJS', 'url' => url([ADMIN, 'jkn_mobile', 'antrol']), 'icon' => 'tasks', 'desc' => 'Antrian Online BPJS'],
        ['name' => 'Dashboard Antrol Local BPJS', 'url' => url([ADMIN, 'jkn_mobile', 'antrollocal']), 'icon' => 'tasks', 'desc' => 'Antrian Online Local BPJS'],
        ['name' => 'Pengaturan', 'url' => url([ADMIN, 'jkn_mobile', 'settings']), 'icon' => 'tasks', 'desc' => 'Pengaturan JKN Mobile'],
      ];
      return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules)]);
    }

    public function getIndex()
    {
        return $this->draw('index.html');
    }

    public function getWSBpjs()
    {
        $this->getCssCard();
        $parsedown = new \Systems\Lib\Parsedown();
        $readme_file = MODULES . '/jkn_mobile/README.md';
        $readme =  $parsedown->text($this->tpl->noParse(file_get_contents($readme_file)));
        return $this->draw('wsbpjs.html', ['readme' => $readme]);
    }

    public function getWSRS()
    {
        $this->getCssCard();
        $referensi_poli = $this->db('maping_poli_bpjs')->toArray();
        $referensi_dokter = $this->db('maping_dokter_dpjpvclaim')->toArray();
        return $this->draw('wsrs.html', [
            'referensi_poli' => $referensi_poli,
            'referensi_dokter' => $referensi_dokter,
            'header_username' => $this->settings->get('jkn_mobile.header_username'),
            'header_password' => $this->settings->get('jkn_mobile.header_password'),
            'x_username' => $this->settings->get('jkn_mobile.x_username'),
            'x_password' => $this->settings->get('jkn_mobile.x_password'),
            'header_token' => $this->settings->get('jkn_mobile.header_token'),
            'token' => $this->settings->get('jkn_mobile.token')
        ]);
    }

    public function postWsRequest()
    {
        $endpoint = $_POST['endpoint'];
        $method = $_POST['method'];
        $data = $_POST['data'] ?? null;

        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid . $this->secretkey . $tStamp;

        $url = $this->bpjsurl . $endpoint;

        $output = '';
        if ($method == 'GET') {
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        } elseif ($method == 'POST') {
             $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        } elseif ($method == 'PUT') {
             $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        } elseif ($method == 'DELETE') {
             $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        }

        $json = json_decode($output, true);

        // Jika json tidak berbentuk array (gagal decode / response kosong) jadikan array kosong agar tidak error di htmlspecialchars_array
        if (!is_array($json)) {
            $json = [];
        }

        if (isset($json['response']) && !is_array($json['response'])) {
             $stringDecrypt = stringDecrypt($key, $json['response']);
             $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
             if ($decompress) {
                 $json['response'] = json_decode($decompress, true);
             }
        }

        echo json_encode(htmlspecialchars_array($json));
        exit();
    }

    public function getRefPoliFinger()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        $url = $this->bpjsurl.'ref/poli/fp';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
        if(!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        }
        echo $decompress;
        exit();
    }

    public function getOnsite($no_peserta, $jenispasien,$tgl)
    {
      if ($jenispasien == 'BPJ') {
        $url = url([ADMIN, 'vclaim', 'caribynokartu', 'PCare', $no_peserta, 'yes']);
      } else {
        $carinorkmmedis = $this->db('pasien')->select('no_rkm_medis')->where('no_peserta',$no_peserta)->oneArray();
        $no_rawat = $this->db('reg_periksa')->where('no_rkm_medis',$carinorkmmedis['no_rkm_medis'])->where('tgl_registrasi',$tgl)->oneArray();
        $url = $this->settings->get('settings.ppk_bpjs').''.convertNorawat($no_rawat['no_rawat']).''.$no_rawat['no_reg'];
      }
      echo $this->draw('form.onsite.html', ['no_peserta' => $no_peserta, 'json' => $url , 'jenispasien' => $jenispasien]);
      exit();
    }

    public function getRefPoli()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->bpjsurl.'ref/poli';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode(htmlspecialchars_array($json));
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

    public function getMappingPoli()
    {
        $this->_addHeaderFiles();
        return $this->draw('mappingpoli.html', ['row' => $this->db('maping_poli_bpjs')->toArray()]);
    }

    public function getAddMappingPoli()
    {
        $this->_addHeaderFiles();
        $this->assign['poliklinik'] = $this->db('poliklinik')->where('status','1')->toArray();
        return $this->draw('form.mappingpoli.html', ['row' => htmlspecialchars_array($this->assign)]);
    }

    public function postPoliklinik_Save()
    {

        $location = url([ADMIN, 'jkn_mobile', 'addmappingpoli']);

        unset($_POST['save']);

        $query = $this->db('maping_poli_bpjs')->save([
            'kd_poli_rs' => $_POST['kd_poli_rs'],
            'kd_poli_bpjs' => $_POST['poli_kode'],
            'nm_poli_bpjs' => $_POST['poli_nama']
        ]);

        if ($query) {
            $this->notify('success', 'Simpan maping poli bpjs sukes');
        } else {
            $this->notify('failure', 'Simpan maping poli bpjs gagal');
        }

        redirect($location, $_POST);
    }

    public function getPoliklinik_Delete($id)
    {
        if ($this->db('maping_poli_bpjs')->where('kd_poli_rs', $id)->delete()) {
            $this->notify('success', 'Hapus maping poli bpjs sukses');
        } else {
            $this->notify('failure', 'Hapus maping poli bpjs gagal');
        }
        redirect(url([ADMIN, 'jkn_mobile', 'mappingpoli']));
    }

    public function getRefDokter()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        $url = $this->bpjsurl.'ref/dokter';
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        //echo json_encode(htmlspecialchars_array($json));
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

    public function getMappingDokter()
    {
        $this->_addHeaderFiles();
        return $this->draw('mappingdokter.html', ['row' => $this->db('maping_dokter_dpjpvclaim')->toArray()]);
    }


    public function getAddMappingDokter()
    {
        $this->_addHeaderFiles();
        $this->assign['dokter'] = $this->db('dokter')->where('status','1')->toArray();
        return $this->draw('form.mappingdokter.html', ['row' => htmlspecialchars_array($this->assign)]);
    }

    public function postDokter_Save()
    {

        $location = url([ADMIN, 'jkn_mobile', 'addmappingdokter']);

        unset($_POST['save']);

        $query = $this->db('maping_dokter_dpjpvclaim')->save([
            'kd_dokter' => $_POST['kd_dokter'],
            'kd_dokter_bpjs' => $_POST['dokter_kode'],
            'nm_dokter_bpjs' => $_POST['dokter_nama']
        ]);

        if ($query) {
            $this->notify('success', 'Simpan maping poli bpjs sukes');
        } else {
            $this->notify('failure', 'Simpan maping poli bpjs gagal');
        }

        redirect($location, $_POST);
    }

    public function getDokter_Delete($id)
    {
        if ($this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $id)->delete()) {
            $this->notify('success', 'Hapus maping poli bpjs sukses');
        } else {
            $this->notify('failure', 'Hapus maping poli bpjs gagal');
        }
        redirect(url([ADMIN, 'jkn_mobile', 'mappingdokter']));
    }

    public function getJadwalDokter()
    {
        $this->_addHeaderFiles();
        $this->getCssCard();
        $poli = $this->db('maping_poli_bpjs')
            ->select(['kd_poli_bpjs', 'nm_poli_bpjs'])
            ->group('kd_poli_bpjs')
            ->asc('nm_poli_bpjs')
            ->toArray();
        return $this->draw('jadwaldokter.html', [
            'poli' => htmlspecialchars_array($poli),
            'tgl_default' => date('Y-m-d')
        ]);
    }

    public function postHfis() {
        header('Content-Type: application/json');
        $kodepoli = $_POST['poli'] ?? '';
        $tanggal = $_POST['tgl'] ?? date('Y-m-d');

        if (empty($kodepoli)) {
            echo json_encode(['status' => 'error', 'code' => 400, 'message' => 'Poliklinik BPJS belum dipilih.']);
            exit();
        }

        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            echo json_encode(['status' => 'error', 'code' => 500, 'message' => 'Kredensial BPJS Antrean (ConsID / SecretKey / URL) belum diatur di Pengaturan JKN Mobile.']);
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

                    $day_num = date('w', strtotime($tanggal));
                    $day_names = ['0' => 'AKHAD', '1' => 'SENIN', '2' => 'SELASA', '3' => 'RABU', '4' => 'KAMIS', '5' => 'JUMAT', '6' => 'SABTU'];
                    $hari_nama = $day_names[(string)$day_num] ?? 'SENIN';

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
                        error_log("postHfis fetch antrean BPJS error: " . $eAntrean->getMessage());
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
                        $item['is_mapped'] = !empty($kd_dokter_rs);

                        // Cek apakah jadwal ada di tabel jadwal lokal SIMRS
                        $item['jadwal_simrs'] = null;
                        $item['is_sync_simrs'] = false;
                        if ($kd_dokter_rs && !empty($list_kd_poli_rs)) {
                            $js = $this->db('jadwal')
                                ->where('kd_dokter', $kd_dokter_rs)
                                ->where('hari_kerja', $hari_nama)
                                ->in('kd_poli', $list_kd_poli_rs)
                                ->oneArray();
                            if ($js) {
                                $item['jadwal_simrs'] = $js;
                                $item['is_sync_simrs'] = true;
                            }
                        }

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
                            $jam = !empty($ba['estimasidilayani']) ? $ba['estimasidilayani'] : (!empty($ba['jampraktek']) ? $ba['jampraktek'] : '-');

                            $pasien_final[] = [
                                'no_reg' => $no_reg,
                                'no_rawat' => '-',
                                'no_rkm_medis' => $norm ?: '-',
                                'nm_pasien' => $pName,
                                'png_jawab' => 'BPJS Kesehatan',
                                'jam' => $jam,
                                'status' => $ba['status'] ?? 'Terdaftar BPJS',
                                'tipe' => 'Mobile JKN (BPJS)',
                                'sumber' => !empty($ba['sumberdata']) ? $ba['sumberdata'] : 'Mobile JKN (BPJS)',
                                'kodebooking' => $kb
                            ];

                            $tracked_bookings[$kb] = true;
                            if (!empty($norm) && $norm !== '-') {
                                $tracked_rms[$norm] = true;
                            }
                        }

                        // 2. Ambil dari booking_registrasi SIMRS (booking online / antrol)
                        if ($kd_dokter_rs) {
                            $qBook = $this->db('booking_registrasi')
                                ->leftJoin('pasien', 'pasien.no_rkm_medis = booking_registrasi.no_rkm_medis')
                                ->leftJoin('penjab', 'penjab.kd_pj = booking_registrasi.kd_pj')
                                ->select([
                                    'no_reg' => 'booking_registrasi.no_reg',
                                    'no_rawat' => "'-'",
                                    'no_rkm_medis' => 'booking_registrasi.no_rkm_medis',
                                    'nm_pasien' => 'pasien.nm_pasien',
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

                                $kb = $ref['kodebooking'] ?? '';
                                if (!empty($kb) && isset($tracked_bookings[$kb])) {
                                    continue;
                                }

                                $pb['tipe'] = $ref ? 'Mobile JKN (BPJS)' : 'Booking Online';
                                $pb['sumber'] = $ref ? 'Mobile JKN (BPJS)' : 'SIMRS Online';
                                $pb['kodebooking'] = $kb;
                                $pb['png_jawab'] = $pb['png_jawab'] ?: 'Umum / BPJS';
                                $pb['nm_pasien'] = $pb['nm_pasien'] ?: ('Pasien RM ' . $rm);
                                $pasien_final[] = $pb;

                                if (!empty($rm)) $tracked_rms[$rm] = true;
                                if (!empty($kb)) $tracked_bookings[$kb] = true;
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
                                if (!empty($rm) && isset($tracked_rms[$rm])) {
                                    foreach ($pasien_final as &$pf) {
                                        if (($pf['no_rkm_medis'] ?? '') === $rm) {
                                            $pf['no_rawat'] = $pr['no_rawat'];
                                            $pf['no_reg'] = $pr['no_reg'];
                                            $pf['jam'] = $pr['jam'];
                                            $pf['status'] = $pr['status'];
                                            $pf['tipe'] = 'Registrasi Hari H (Hadir)';
                                            break;
                                        }
                                    }
                                } else {
                                    $pr['tipe'] = 'Registrasi Hari H';
                                    $pr['sumber'] = 'Loket / Mandiri RS';
                                    $pr['kodebooking'] = '';
                                    $pr['png_jawab'] = $pr['png_jawab'] ?: 'Umum / BPJS';
                                    $pr['nm_pasien'] = $pr['nm_pasien'] ?: ('Pasien RM ' . $rm);
                                    $pasien_final[] = $pr;
                                    if (!empty($rm)) $tracked_rms[$rm] = true;
                                }
                            }
                        }

                        $kapasitas = intval($item['kapasitaspasien'] ?? 0);
                        $terdaftar = count($pasien_final);
                        $sisa = max(0, $kapasitas - $terdaftar);

                        $item['terdaftar'] = $terdaftar;
                        $item['sisa_kuota'] = $sisa;
                        $item['pasien_list'] = $pasien_final;
                    }
                } catch (\Throwable $e) {
                    error_log("postHfis Error: " . $e->getMessage());
                }
            }
        }

        $total_dokter = is_array($data) ? count($data) : 0;
        $total_kapasitas = 0;
        $total_terdaftar = 0;
        $total_sisa = 0;

        if (is_array($data)) {
            foreach ($data as $d) {
                $total_kapasitas += intval($d['kapasitaspasien'] ?? 0);
                $total_terdaftar += intval($d['terdaftar'] ?? 0);
                $total_sisa += intval($d['sisa_kuota'] ?? 0);
            }
        }

        echo json_encode([
            'status' => ($code == 200) ? 'success' : 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'summary' => [
                'total_dokter' => $total_dokter,
                'total_kapasitas' => $total_kapasitas,
                'total_terdaftar' => $total_terdaftar,
                'total_sisa' => $total_sisa
            ]
        ]);
        exit();
    }

    public function postSyncJadwalHfis()
    {
        header('Content-Type: application/json');
        $kd_dokter = $_POST['kd_dokter'] ?? '';
        $kd_poli = $_POST['kd_poli'] ?? '';
        $hari = strtoupper(trim($_POST['hari'] ?? ''));
        $jam_raw = trim($_POST['jam'] ?? '');
        $kuota = intval($_POST['kuota'] ?? 0);

        if (empty($kd_dokter) || empty($kd_poli) || empty($hari) || empty($jam_raw)) {
            echo json_encode(['status' => 'error', 'message' => 'Data dokter, poli, hari, atau jam tidak lengkap.']);
            exit();
        }

        $parts = explode('-', $jam_raw);
        $jam_mulai = trim($parts[0] ?? '08:00:00');
        $jam_selesai = trim($parts[1] ?? '14:00:00');
        if (strlen($jam_mulai) === 5) $jam_mulai .= ':00';
        if (strlen($jam_selesai) === 5) $jam_selesai .= ':00';

        $check = $this->db('jadwal')
            ->where('kd_dokter', $kd_dokter)
            ->where('hari_kerja', $hari)
            ->where('kd_poli', $kd_poli)
            ->oneArray();

        if ($check) {
            $this->db('jadwal')
                ->where('kd_dokter', $kd_dokter)
                ->where('hari_kerja', $hari)
                ->where('kd_poli', $kd_poli)
                ->update([
                    'jam_mulai' => $jam_mulai,
                    'jam_selesai' => $jam_selesai,
                    'kuota' => $kuota
                ]);
            $msg = "Jadwal dokter berhasil diperbarui di SIMRS.";
        } else {
            $this->db('jadwal')->save([
                'kd_dokter' => $kd_dokter,
                'hari_kerja' => $hari,
                'jam_mulai' => $jam_mulai,
                'jam_selesai' => $jam_selesai,
                'kd_poli' => $kd_poli,
                'kuota' => $kuota
            ]);
            $msg = "Jadwal dokter berhasil disalin ke jadwal SIMRS.";
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit();
    }

    public function anyTaskID()
    {
      try {
        $this->_addHeaderFiles();
        $this->getCssCard();
        $date = date('Y-m-d');
        if(isset($_POST['periode_antrol']) && $_POST['periode_antrol'] !='')
          $date = $_POST['periode_antrol'];

        $exclude_setting = $this->settings->get('jkn_mobile.exclude_taskid');
        $exclude_list = array_values(array_filter(array_map('trim', explode(',', (string)$exclude_setting))));
        if (!empty($exclude_list)) {
            $placeholders = implode(',', array_fill(0, count($exclude_list), '?'));
            $sql = "SELECT pasien.no_peserta,pasien.no_rkm_medis,pasien.no_ktp,pasien.no_tlp,pasien.nm_pasien,reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.kd_poli,poliklinik.nm_poli,reg_periksa.stts_daftar,reg_periksa.stts
            FROM reg_periksa 
            INNER JOIN pasien ON reg_periksa.no_rkm_medis=pasien.no_rkm_medis 
            INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter 
            INNER JOIN poliklinik ON reg_periksa.kd_poli=poliklinik.kd_poli 
            WHERE reg_periksa.tgl_registrasi=? AND reg_periksa.kd_poli NOT IN ($placeholders)
            ORDER BY concat(reg_periksa.tgl_registrasi,' ',reg_periksa.jam_reg)";
            $params = array_merge([$date], $exclude_list);
        } else {
            $sql = "SELECT pasien.no_peserta,pasien.no_rkm_medis,pasien.no_ktp,pasien.no_tlp,pasien.nm_pasien,reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.kd_poli,poliklinik.nm_poli,reg_periksa.stts_daftar,reg_periksa.stts
            FROM reg_periksa 
            INNER JOIN pasien ON reg_periksa.no_rkm_medis=pasien.no_rkm_medis 
            INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter 
            INNER JOIN poliklinik ON reg_periksa.kd_poli=poliklinik.kd_poli 
            WHERE reg_periksa.tgl_registrasi=?
            ORDER BY concat(reg_periksa.tgl_registrasi,' ',reg_periksa.jam_reg)";
            $params = [$date];
        }
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $query = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($query as $q) {
            $no_rawat = $q['no_rawat'] ?? '';
            $batal = (($q['stts'] ?? '') == 'Batal') ? ($q['tgl_registrasi'].' '.date('H:i:s')) : '0000-00-00 00:00:00';

            $mlite_antrian_referensi = null;
            if (!empty($q['no_peserta'])) {
                $mlite_antrian_referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $q['tgl_registrasi'])->where('nomor_kartu', $q['no_peserta'])->oneArray();
            }
            if (!$mlite_antrian_referensi && !empty($q['no_rkm_medis'])) {
                $mlite_antrian_referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $q['tgl_registrasi'])->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
            }

            $mutasi_berkas = !empty($no_rawat) ? $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $no_rawat)->where('dikirim', '>', '1970-01-01 00:00:00')->oneArray() : null;
            $mutasi_berkas2 = !empty($no_rawat) ? $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $no_rawat)->where('diterima', '>', '1970-01-01 00:00:00')->oneArray() : null;
            $pemeriksaan_ralan = !empty($no_rawat) ? $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $no_rawat)->oneArray() : null;
            $resep_obat = !empty($no_rawat) ? $this->db('resep_obat')->select(['datajam' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $no_rawat)->oneArray() : null;
            $resep_obat2 = !empty($no_rawat) ? $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $no_rawat)->where('concat(tgl_perawatan," ",jam)', '<>', 'concat(tgl_peresepan," ",jam_peresepan)')->oneArray() : null;

            $mlite_antrian_loket = !empty($q['no_rkm_medis']) ? $this->db('mlite_antrian_loket')->where('postdate', $date)->where('type','Loket')->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray() : null;
            $task1 = '';
            $task2 = '';
            if(!empty($mlite_antrian_loket['start_time'])) {
              $task1 = $mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time'];
            }
            if(!empty($mlite_antrian_loket['end_time'])) {
              $task2 = $mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time'];
            }

            $q['nomor_referensi'] = $mlite_antrian_referensi['kodebooking'] ?? '';
            $q['status_kirim'] = $mlite_antrian_referensi['status_kirim'] ?? '';

            $q['task1'] = '';
            $q['task2'] = '';
            $q['task3'] = '';
            $q['task4'] = '';
            $q['task5'] = '';
            $q['task6'] = '';
            $q['task7'] = '';
            $q['task99'] = '';

            if(!empty($q['nomor_referensi'])) {
              $taskid_records = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['nomor_referensi'])->toArray();
              $taskid_map = [];
              foreach ($taskid_records as $tr) {
                $taskid_map[$tr['taskid']] = $tr['waktu'];
              }
              if (!empty($taskid_map['1']) && is_numeric($taskid_map['1'])) {
                $q['task1'] = date('Y-m-d H:i:s', (int)($taskid_map['1'] / 1000));
              }
              if (!empty($taskid_map['2']) && is_numeric($taskid_map['2'])) {
                $q['task2'] = date('Y-m-d H:i:s', (int)($taskid_map['2'] / 1000));
              }
              if (!empty($taskid_map['3']) && is_numeric($taskid_map['3'])) {
                $q['task3'] = date('Y-m-d H:i:s', (int)($taskid_map['3'] / 1000));
              }
              if (!empty($taskid_map['4']) && is_numeric($taskid_map['4'])) {
                $q['task4'] = date('Y-m-d H:i:s', (int)($taskid_map['4'] / 1000));
              }
              if (!empty($taskid_map['5']) && is_numeric($taskid_map['5'])) {
                $q['task5'] = date('Y-m-d H:i:s', (int)($taskid_map['5'] / 1000));
              }
              if (!empty($taskid_map['6']) && is_numeric($taskid_map['6'])) {
                $q['task6'] = date('Y-m-d H:i:s', (int)($taskid_map['6'] / 1000));
              }
              if (!empty($taskid_map['7']) && is_numeric($taskid_map['7'])) {
                $q['task7'] = date('Y-m-d H:i:s', (int)($taskid_map['7'] / 1000));
              }
              if (!empty($taskid_map['99']) && is_numeric($taskid_map['99'])) {
                $q['task99'] = date('Y-m-d H:i:s', (int)($taskid_map['99'] / 1000));
              }
            }

            if(empty($q['task4'])) {
              $q['task4'] = !empty($mutasi_berkas2['diterima']) ? $mutasi_berkas2['diterima'] : '';
            }
            if(empty($q['task5'])) {
              $q['task5'] = !empty($pemeriksaan_ralan['datajam']) ? $pemeriksaan_ralan['datajam'] : '';
            }
            if(empty($q['task3'])) {
              if (!empty($mutasi_berkas['dikirim'])) {
                $q['task3'] = $mutasi_berkas['dikirim'];
              } elseif (!empty($q['task4'])) {
                $q['task3'] = date('Y-m-d H:i:s', strtotime($q['task4']) - 300);
              } elseif (!empty($q['task5'])) {
                $q['task3'] = date('Y-m-d H:i:s', strtotime($q['task5']) - 600);
              } else {
                $q['task3'] = '';
              }
            }
            if(empty($q['task4']) && !empty($q['task5'])) {
              $q['task4'] = date('Y-m-d H:i:s', strtotime($q['task5']) - 300);
            }
            if(!empty($q['task3']) && !empty($q['task4']) && strtotime($q['task3']) >= strtotime($q['task4'])) {
              $q['task3'] = date('Y-m-d H:i:s', strtotime($q['task4']) - 300);
            }

            if(empty($q['task1']) || empty($q['task2'])) {
              if(!empty($task1) && !empty($task2)) {
                if(empty($q['task1'])) $q['task1'] = $task1;
                if(empty($q['task2'])) $q['task2'] = $task2;
              } elseif(!empty($q['task3'])) {
                $t3_time = strtotime($q['task3']);
                if(empty($q['task2'])) $q['task2'] = date('Y-m-d H:i:s', $t3_time - 180);
                if(empty($q['task1'])) $q['task1'] = date('Y-m-d H:i:s', $t3_time - 600);
              } else {
                if(empty($q['task1'])) $q['task1'] = '';
                if(empty($q['task2'])) $q['task2'] = '';
              }
            }

            if(empty($q['task6'])) {
              $q['task6'] = !empty($resep_obat2['datajam']) ? $resep_obat2['datajam'] : '';
            }
            if(empty($q['task7'])) {
              $q['task7'] = !empty($resep_obat['datajam']) ? $resep_obat['datajam'] : '';
            }
            if(empty($q['task99'])) {
              $q['task99'] = $batal;
            }
            $rows[] = $q;
        }

        $taskid = $rows;
        return $this->draw('taskid.html', ['taskid' => $taskid, 'date' => $date, 'tgl' => $date]);
      } catch (\Throwable $e) {
        error_log("anyTaskID Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        $msg = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile() . ':' . $e->getLine());
        return '<div class="row" style="margin-top:15px;"><div class="col-xs-12">' .
               '<div class="panel panel-danger">' .
               '<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-exclamation-triangle"></i> Gagal Memuat Halaman Task ID JKN</h3></div>' .
               '<div class="panel-body">' .
               '<div class="alert alert-danger" style="margin-bottom:10px;"><strong>Error:</strong> ' . $msg . '</div>' .
               '<p class="text-muted"><small>Lokasi: ' . $file . '</small></p>' .
               '<a href="' . url([ADMIN, 'jkn_mobile', 'taskid']) . '" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Muat Ulang</a>' .
               '</div></div></div></div>';
      }
    }

    public function getTaskIDInput($no_rawat = '')
    {
      try {
        if (empty($no_rawat) && !empty($_GET['no_rawat'])) {
          $no_rawat = $_GET['no_rawat'];
        }

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['modal']);

        $no_rawat = trim((string)$no_rawat);
        if (empty($no_rawat)) {
          if ($isAjax) {
            echo '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title text-warning"><i class="fa fa-info-circle"></i> Informasi Task ID</h4></div>' .
                 '<div class="modal-body"><div class="alert alert-warning"><i class="fa fa-info-circle"></i> Nomor Rawat belum ditentukan atau pasien belum memiliki Nomor Rawat di SIMRS.</div></div>' .
                 '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>';
            exit();
          } else {
            $this->_addHeaderFiles();
            return '<div class="row" style="margin-top:20px;"><div class="col-md-8 col-md-offset-2"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><i class="fa fa-info-circle text-info"></i> Input Task ID</h3></div><div class="panel-body"><div class="alert alert-warning"><i class="fa fa-info-circle"></i> Nomor Rawat tidak valid atau belum ditentukan. Silakan pilih pasien dari tabel Booking Antrol atau Antrean SIMRS.</div><a href="' . url([ADMIN, 'jkn_mobile', 'bookingantrol']) . '" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Kembali ke Booking Antrol</a> <a href="' . url([ADMIN, 'jkn_mobile', 'taskid']) . '" class="btn btn-default"><i class="fa fa-list"></i> Lihat Data Task ID</a></div></div></div></div>';
          }
        }

        $no_rawat = revertNorawat($no_rawat);
        $pasien = $this->db('reg_periksa')
          ->select([
            'no_rawat' => 'no_rawat',
            'no_rkm_medis' => 'reg_periksa.no_rkm_medis',
            'nm_pasien' => 'nm_pasien',
            'kd_pj' => 'reg_periksa.kd_pj',
            'tgl_registrasi' => 'tgl_registrasi',
            'jam_reg' => 'jam_reg'
          ])
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->where('no_rawat', $no_rawat)
          ->oneArray();

        if (!$pasien) {
          if ($isAjax) {
            echo '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title text-warning"><i class="fa fa-exclamation-triangle"></i> Pasien Tidak Ditemukan</h4></div>' .
                 '<div class="modal-body"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Data pendaftaran SIMRS untuk No. Rawat <b>' . htmlspecialchars($no_rawat) . '</b> tidak ditemukan. Pastikan pasien sudah teregistrasi di SIMRS.</div></div>' .
                 '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>';
            exit();
          } else {
            $this->_addHeaderFiles();
            return '<div class="row" style="margin-top:20px;"><div class="col-md-8 col-md-offset-2"><div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title"><i class="fa fa-exclamation-triangle text-danger"></i> Pasien Tidak Ditemukan</h3></div><div class="panel-body"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Data pendaftaran SIMRS untuk No. Rawat <b>' . htmlspecialchars($no_rawat) . '</b> tidak ditemukan.</div><a href="' . url([ADMIN, 'jkn_mobile', 'bookingantrol']) . '" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Kembali ke Booking Antrol</a> <a href="' . url([ADMIN, 'jkn_mobile', 'taskid']) . '" class="btn btn-default"><i class="fa fa-list"></i> Lihat Data Task ID</a></div></div></div></div>';
          }
        }

        $mlite_antrian_referensi = null;
        if (!empty($pasien['no_rkm_medis']) && !empty($pasien['tgl_registrasi'])) {
          $mlite_antrian_referensi = $this->db('mlite_antrian_referensi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $pasien['tgl_registrasi'])->oneArray();
        }
        $nomor_referensi = '';
        $kode_booking = '';
        if($mlite_antrian_referensi) {
          $nomor_referensi = $mlite_antrian_referensi['nomor_referensi'] ?? '';
          $kode_booking = $mlite_antrian_referensi['kodebooking'] ?? '';
        }

        $taskid_records = !empty($kode_booking) ? $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->toArray() : [];
        $taskid_map = [];
        foreach ($taskid_records as $tr) {
          $taskid_map[$tr['taskid']] = $tr['waktu'];
        }

        $taskid = [];
        $taskid['1'] = (!empty($taskid_map['1']) && is_numeric($taskid_map['1'])) ? date('Y-m-d H:i:s', (int)($taskid_map['1'] / 1000)) : '';
        $taskid['2'] = (!empty($taskid_map['2']) && is_numeric($taskid_map['2'])) ? date('Y-m-d H:i:s', (int)($taskid_map['2'] / 1000)) : '';
        $taskid['3'] = (!empty($taskid_map['3']) && is_numeric($taskid_map['3'])) ? date('Y-m-d H:i:s', (int)($taskid_map['3'] / 1000)) : '';
        $taskid['4'] = (!empty($taskid_map['4']) && is_numeric($taskid_map['4'])) ? date('Y-m-d H:i:s', (int)($taskid_map['4'] / 1000)) : '';
        $taskid['5'] = (!empty($taskid_map['5']) && is_numeric($taskid_map['5'])) ? date('Y-m-d H:i:s', (int)($taskid_map['5'] / 1000)) : '';
        $taskid['6'] = (!empty($taskid_map['6']) && is_numeric($taskid_map['6'])) ? date('Y-m-d H:i:s', (int)($taskid_map['6'] / 1000)) : '';
        $taskid['7'] = (!empty($taskid_map['7']) && is_numeric($taskid_map['7'])) ? date('Y-m-d H:i:s', (int)($taskid_map['7'] / 1000)) : '';
        $taskid['99'] = (!empty($taskid_map['99']) && is_numeric($taskid_map['99'])) ? date('Y-m-d H:i:s', (int)($taskid_map['99'] / 1000)) : '';

        $mlite_antrian_loket = !empty($pasien['no_rkm_medis']) ? $this->db('mlite_antrian_loket')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('postdate', $pasien['tgl_registrasi'])->oneArray() : null;
        $berkas_dikirim = !empty($pasien['no_rawat']) ? $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $pasien['no_rawat'])->where('dikirim', '>', '1970-01-01 00:00:00')->oneArray() : null;
        $berkas_diterima = !empty($pasien['no_rawat']) ? $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $pasien['no_rawat'])->where('diterima', '>', '1970-01-01 00:00:00')->oneArray() : null;
        $pemeriksaan_ralan = !empty($pasien['no_rawat']) ? $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $pasien['no_rawat'])->oneArray() : null;
        $resep_obat = !empty($pasien['no_rawat']) ? $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)', 'datajam2' => 'concat(tgl_perawatan," ",jam)', 'no_resep' => 'no_resep'])->where('no_rawat', $pasien['no_rawat'])->oneArray() : null;

        $jenisresep = 'Non racikan';
        if($resep_obat && !empty($resep_obat['no_resep'])) {
          $resep_dokter_racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep_obat['no_resep'])->oneArray();
          if(!empty($resep_dokter_racikan)) {
            $jenisresep = 'Racikan';
          }
        }

        if(empty($taskid['4'])) {
          $taskid['4'] = !empty($berkas_diterima['diterima']) ? $berkas_diterima['diterima'] : '';
        }
        if(empty($taskid['5'])) {
          $taskid['5'] = !empty($pemeriksaan_ralan['datajam']) ? $pemeriksaan_ralan['datajam'] : '';
        }
        if(empty($taskid['3'])) {
          if (!empty($berkas_dikirim['dikirim'])) {
            $taskid['3'] = $berkas_dikirim['dikirim'];
          } elseif (!empty($taskid['4'])) {
            // Jika berkas sudah diterima di poli tapi RM belum klik kirim, otomatis 5 menit sebelum diterima
            $taskid['3'] = date('Y-m-d H:i:s', strtotime($taskid['4']) - 300);
          } elseif (!empty($taskid['5'])) {
            // Jika dokter sudah mulai periksa, otomatis 10 menit sebelum periksa
            $taskid['3'] = date('Y-m-d H:i:s', strtotime($taskid['5']) - 600);
          } else {
            $taskid['3'] = '';
          }
        }
        if(empty($taskid['4']) && !empty($taskid['5'])) {
          $taskid['4'] = date('Y-m-d H:i:s', strtotime($taskid['5']) - 300);
        }
        if(!empty($taskid['3']) && !empty($taskid['4']) && strtotime($taskid['3']) >= strtotime($taskid['4'])) {
          $taskid['3'] = date('Y-m-d H:i:s', strtotime($taskid['4']) - 300);
        }

        if(empty($taskid['1']) || empty($taskid['2'])) {
          if(!empty($mlite_antrian_loket['start_time']) && !empty($mlite_antrian_loket['end_time'])) {
            if(empty($taskid['1'])) {
              $taskid['1'] = $mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time'];
            }
            if(empty($taskid['2'])) {
              $taskid['2'] = $mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time'];
            }
          } elseif(!empty($taskid['3'])) {
            $t3_time = strtotime($taskid['3']);
            if(empty($taskid['2'])) {
              $taskid['2'] = date('Y-m-d H:i:s', $t3_time - 180);
            }
            if(empty($taskid['1'])) {
              $taskid['1'] = date('Y-m-d H:i:s', $t3_time - 600);
            }
          } else {
            if(empty($taskid['1'])) {
              $taskid['1'] = '';
            }
            if(empty($taskid['2'])) {
              $taskid['2'] = '';
            }
          }
        }
        if(empty($taskid['6'])) {
          $taskid['6'] = !empty($resep_obat['datajam']) ? $resep_obat['datajam'] : '';
        }
        if(empty($taskid['7'])) {
          $taskid['7'] = !empty($resep_obat['datajam2']) ? $resep_obat['datajam2'] : '';
        }
        if(empty($taskid['99'])) {
          $taskid['99'] = '';
        }
        $status_antrol = !empty($nomor_referensi) ? $this->db('mlite_antrian_referensi')->where('nomor_referensi', $nomor_referensi)->where('status_kirim', 'Sudah')->oneArray() : null;
        $status_kirim_antrol = 'Belum';
        if($status_antrol && !empty($status_antrol['status_kirim'])) {
          $status_kirim_antrol = $status_antrol['status_kirim'];
        }

        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_GET['modal']);
        if (!$isAjax) {
          $this->_addHeaderFiles();
          $this->getCssCard();
          return '<div class="row" style="margin-top:15px;"><div class="col-md-10 col-md-offset-1"><div class="panel panel-default" style="border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.08);"><div class="panel-body">' . 
                 $this->draw('taskid.input.html', ['pasien' => $pasien, 'taskid' => $taskid, 'nomor_referensi' => $nomor_referensi, 'kode_booking' => $kode_booking, 'status_kirim_antrol' => $status_kirim_antrol]) . 
                 '</div></div></div></div>';
        }
        echo $this->draw('taskid.input.html', ['pasien' => $pasien, 'taskid' => $taskid, 'nomor_referensi' => $nomor_referensi, 'kode_booking' => $kode_booking, 'status_kirim_antrol' => $status_kirim_antrol]);
        exit();
      } catch (\Throwable $e) {
        error_log("getTaskIDInput error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        echo '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title text-danger">Error Memuat Task ID</h4></div><div class="modal-body"><div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div><small>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</small></div>';
        exit();
      }
    }

    public function postSaveTaskIDInput()
    {
      $nomor_referensi = trim($_POST['nomor_referensi'] ?? '');
      if (!empty($nomor_referensi)) {
        $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $nomor_referensi)->where('status','!=','Sudah')->delete();
      }

      if (!empty($_POST['taskid99'])) {
        $this->db('mlite_antrian_referensi_taskid')
          ->save([
            'tanggal_periksa' => $_POST['tgl_registrasi'],
            'nomor_referensi' => $_POST['nomor_referensi'],
            'taskid' => 99,
            'waktu' => strtotime($_POST['taskid99']) * 1000,
            'status' => 'Belum',
            'keterangan' => ''
          ]);
      } else {
        if(!empty($_POST['taskid1'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','1')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 1,
              'waktu' => strtotime($_POST['taskid1']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid2'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','2')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 2,
              'waktu' => strtotime($_POST['taskid2']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid3'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','3')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 3,
              'waktu' => strtotime($_POST['taskid3']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid4'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','4')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 4,
              'waktu' => strtotime($_POST['taskid4']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid5'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','5')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 5,
              'waktu' => strtotime($_POST['taskid5']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid6'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','6')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 6,
              'waktu' => strtotime($_POST['taskid6']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
  
        if(!empty($_POST['taskid7'])) {
          if(!$this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('taskid','7')->where('status','Sudah')->oneArray()){
            $this->db('mlite_antrian_referensi_taskid')
            ->save([
              'tanggal_periksa' => $_POST['tgl_registrasi'],
              'nomor_referensi' => $_POST['nomor_referensi'],
              'taskid' => 7,
              'waktu' => strtotime($_POST['taskid7']) * 1000,
              'status' => 'Belum',
              'keterangan' => ''
            ]);
          }
        }
      }


      $this->notify('success', 'Data Task ID berhasil disimpan');
      $redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url([ADMIN, 'jkn_mobile', 'taskid']);
      redirect($redirectUrl);
    }

    public function getKirimAntrian($no_rawat = '')
    {
      if (empty($no_rawat) && !empty($_GET['no_rawat'])) {
        $no_rawat = $_GET['no_rawat'];
      }
      $no_rawat = trim((string)$no_rawat);
      if (empty($no_rawat)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No. Rawat tidak valid atau kosong.']);
        exit();
      }

      $no_rawat = revertNorawat($no_rawat);
      $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)
        ->oneArray();
      $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();
      $date = $reg_periksa['tgl_registrasi'];
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

      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
      $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();

      $kd_dokter_bpjs = $maping_dokter_dpjpvclaim ? $maping_dokter_dpjpvclaim['kd_dokter_bpjs'] : '';
      $nm_dokter_bpjs = $maping_dokter_dpjpvclaim ? $maping_dokter_dpjpvclaim['nm_dokter_bpjs'] : '';
      $kd_poli_bpjs = $maping_poli_bpjs ? $maping_poli_bpjs['kd_poli_bpjs'] : '';
      $nm_poli_bpjs = $maping_poli_bpjs ? $maping_poli_bpjs['nm_poli_bpjs'] : '';

      $jam_mulai = ($jadwaldokter && !empty($jadwaldokter['jam_mulai'])) ? $jadwaldokter['jam_mulai'] : '08:00:00';
      $jam_selesai = ($jadwaldokter && !empty($jadwaldokter['jam_selesai'])) ? $jadwaldokter['jam_selesai'] : '14:00:00';
      $kuota = ($jadwaldokter && !empty($jadwaldokter['kuota'])) ? intval($jadwaldokter['kuota']) : 30;

      $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
      $minutes = intval($no_urut_reg) * 10;
      $cek_kuota['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jam_mulai)));
      // Auto-heal setting kd_pj_bpjs jika di server masih strip (-)
      if ($this->settings->get('jkn_mobile.kd_pj_bpjs') == '-' || $this->settings->get('jkn_mobile.kd_pj_bpjs') == '') {
        $this->db('mlite_settings')->where('module', 'jkn_mobile')->where('field', 'kd_pj_bpjs')->save(['value' => 'BPJ']);
      }

      $jenispasien = 'NON JKN';
      $bpj_codes = array_filter([$this->settings->get('jkn_mobile.kd_pj_bpjs'), $this->settings->get('manajemen.penjab_bpjs'), 'BPJ']);
      if(in_array($reg_periksa['kd_pj'], $bpj_codes) || stripos($reg_periksa['kd_pj'], 'BPJ') !== false) {
        $jenispasien = 'JKN';
      }
      $pasienbaru = '1';
      if($reg_periksa['stts_daftar'] == 'Lama') {
        $pasienbaru = '0';
      }

      $nomorkartu = $pasien['no_peserta'];
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

      $jeniskunjungan = 1; // Default rujukan FKTP
      $nomorreferensi = $this->settings->get('settings.ppk_bpjs').''.convertNorawat($reg_periksa['no_rawat']).''.$reg_periksa['no_reg'];

      if($jenispasien == 'JKN') {
          $bridging_sep = $this->db('bridging_sep')->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
          if ($bridging_sep) {
            // Tentukan jenis kunjungan & nomor referensi dari data SEP
            if (!empty($bridging_sep['noskdp']) || $bridging_sep['tujuankunjungan'] == '1') {
              $jeniskunjungan = 3; // Kontrol (SKDP)
              $nomorreferensi = !empty($bridging_sep['noskdp']) ? $bridging_sep['noskdp'] : $bridging_sep['no_rujukan'];
            } elseif ($bridging_sep['tujuankunjungan'] == '2') {
              $jeniskunjungan = 2; // Rujukan Internal
              $nomorreferensi = !empty($bridging_sep['noskdp']) ? $bridging_sep['noskdp'] : $bridging_sep['no_rujukan'];
            } elseif ($bridging_sep['asal_rujukan'] == '2. Faskes 2(RS)') {
              $jeniskunjungan = 4; // Rujukan Antar RS (Faskes 2)
              $nomorreferensi = $bridging_sep['no_rujukan'];
            } else {
              $jeniskunjungan = 1; // Rujukan FKTP (Faskes 1)
              $nomorreferensi = $bridging_sep['no_rujukan'];
            }

            if (empty($kd_dokter_bpjs)) {
              $kd_dokter_bpjs = $bridging_sep['kddpjplayanan'] ?: $bridging_sep['kddpjp'];
              $nm_dokter_bpjs = $bridging_sep['nmdpjplayanan'] ?: $bridging_sep['nmdpdjp'];
            }
            if (empty($kd_poli_bpjs)) {
              $kd_poli_bpjs = $bridging_sep['kdpolitujuan'];
              $nm_poli_bpjs = $bridging_sep['nmpolitujuan'];
            }
          } else {
            $bridging_sep_internal = $this->db('bridging_sep_internal')->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
            if ($bridging_sep_internal) {
              if (!empty($bridging_sep_internal['noskdp']) || $bridging_sep_internal['tujuankunjungan'] == '1') {
                $jeniskunjungan = 3;
                $nomorreferensi = !empty($bridging_sep_internal['noskdp']) ? $bridging_sep_internal['noskdp'] : $bridging_sep_internal['no_rujukan'];
              } elseif ($bridging_sep_internal['tujuankunjungan'] == '2') {
                $jeniskunjungan = 2;
                $nomorreferensi = !empty($bridging_sep_internal['noskdp']) ? $bridging_sep_internal['noskdp'] : $bridging_sep_internal['no_rujukan'];
              } elseif ($bridging_sep_internal['asal_rujukan'] == '2. Faskes 2(RS)') {
                $jeniskunjungan = 4;
                $nomorreferensi = $bridging_sep_internal['no_rujukan'];
              } else {
                $jeniskunjungan = 1;
                $nomorreferensi = $bridging_sep_internal['no_rujukan'];
              }

              if (empty($kd_dokter_bpjs)) {
                $kd_dokter_bpjs = $bridging_sep_internal['kddpjplayanan'] ?: $bridging_sep_internal['kddpjp'];
                $nm_dokter_bpjs = $bridging_sep_internal['nmdpjplayanan'] ?: $bridging_sep_internal['nmdpdjp'];
              }
              if (empty($kd_poli_bpjs)) {
                $kd_poli_bpjs = $bridging_sep_internal['kdpolitujuan'];
                $nm_poli_bpjs = $bridging_sep_internal['nmpolitujuan'];
              }
            }
          }

          if (empty($nomorreferensi)) {
            $surat_pri = $this->db('bridging_surat_pri_bpjs')->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
            if ($surat_pri && !empty($surat_pri['no_surat'])) {
              $nomorreferensi = $surat_pri['no_surat'];
              $jeniskunjungan = 3;
            }
          }

          if (empty($nomorreferensi)) {
            echo json_encode([
              'status' => 'error',
              'message' => 'Pasien BPJS/JKN ini belum memiliki SEP atau Nomor Rujukan. Silakan terbitkan SEP terlebih dahulu di menu Rawat Jalan (tombol BPJS) sebelum mengirim Antrol.'
            ]);
            exit();
          }
      }

      if (empty($kd_dokter_bpjs) || empty($kd_poli_bpjs)) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Data mapping Dokter BPJS atau Poli BPJS belum tersedia. Silakan lengkapi Mapping Dokter/Poli di menu Bridging.'
        ]);
        exit();
      }

      $kodebooking = $this->settings->get('settings.ppk_bpjs').''.convertNorawat($reg_periksa['no_rawat']).''.$kd_poli_bpjs.''.$reg_periksa['no_reg'];

      $data = [
          'kodebooking' => $kodebooking,
          'jenispasien' => $jenispasien,
          'nomorkartu' => $nomorkartu,
          'nik' => $nik,
          'nohp' => $nohp,
          'kodepoli' => $kd_poli_bpjs,
          'namapoli' => $nm_poli_bpjs,
          'pasienbaru' => intval($pasienbaru),
          'norm' => $reg_periksa['no_rkm_medis'],
          'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
          'kodedokter' => intval($kd_dokter_bpjs),
          'namadokter' => $nm_dokter_bpjs,
          'jampraktek' => substr($jam_mulai,0,5).'-'.substr($jam_selesai,0,5),
          'jeniskunjungan' => intval($jeniskunjungan),
          'nomorreferensi' => $nomorreferensi,
          'nomorantrean' => $kd_poli_bpjs.'-'.$reg_periksa['no_reg'],
          'angkaantrean' => intval(ltrim($reg_periksa['no_reg'],'0')),
          'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kuota['jam_mulai']) * 1000,
          'sisakuotajkn' => max(0, $kuota - intval(ltrim($reg_periksa['no_reg'],'0'))),
          'kuotajkn' => $kuota,
          'sisakuotanonjkn' => max(0, $kuota - intval(ltrim($reg_periksa['no_reg'],'0'))),
          'kuotanonjkn' => $kuota,
          'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
      ];
      $data = json_encode($data);
      $url = $this->bpjsurl.'antrean/add';
      $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
      $data = json_decode($output, true);
      $code = isset($data['metadata']['code']) ? $data['metadata']['code'] : (isset($data['metaData']['code']) ? $data['metaData']['code'] : 500);
      $msg = isset($data['metadata']['message']) ? $data['metadata']['message'] : (isset($data['metaData']['message']) ? $data['metaData']['message'] : 'Gagal mengirim antrean ke server BPJS.');

      if($code == 200 || $code == 208) {
        $response = [
            'status' => 'success',
            'nomor_referensi' => $nomorreferensi,
            'kode_booking' => $kodebooking,
            'message' => 'Antrean berhasil dikirim ke BPJS (' . $code . ': ' . $msg . ')'
        ];
        $this->db('mlite_antrian_referensi')->save([
            'tanggal_periksa' => $reg_periksa['tgl_registrasi'],
            'no_rkm_medis' => $reg_periksa['no_rkm_medis'],
            'nomor_kartu' => $nomorkartu,
            'nomor_referensi' => $nomorreferensi,
            'kodebooking' => $kodebooking,
            'jenis_kunjungan' => $jeniskunjungan,
            'status_kirim' => 'Sudah',
            'keterangan' => $code . ': ' . $msg
        ]);
        echo json_encode(htmlspecialchars_array($response));
      } else {
        echo json_encode([
            'status' => 'error',
            'code' => $code,
            'message' => $code . ': ' . $msg
        ]);
      }
      exit();
    }

    private function _decodeBpjsResponse($output)
    {
      $json = json_decode((string) $output, true);

      if (!is_array($json)) {
        $json = [];
      }

      if (!isset($json['metadata']) || !is_array($json['metadata'])) {
        $json['metadata'] = [];
      }

      if (!isset($json['metadata']['code'])) {
        $json['metadata']['code'] = '5000';
      }

      if (!isset($json['metadata']['message'])) {
        $json['metadata']['message'] = 'Response BPJS kosong atau tidak valid.';
      }

      return $json;
    }

    public function getUpdateWaktu($nomor_referensi = '', $kode_booking = '', $versi = '')
    {
      $nomor_referensi = trim((string) $nomor_referensi);
      $kode_booking = trim((string) $kode_booking);
      $versi = strtolower(trim((string) $versi));

      if ($nomor_referensi === '' || $kode_booking === '') {
        $this->notify('failure', 'Nomor referensi/kode booking tidak ditemukan. Silakan kirim antrean terlebih dahulu.');
        redirect(url([ADMIN, 'jkn_mobile', 'taskid']));
      }

      if (!in_array($versi, ['batal', 'v2', 'v3'], true)) {
        $this->notify('failure', 'Versi update waktu tidak dikenali.');
        redirect(url([ADMIN, 'jkn_mobile', 'taskid']));
      }

      $mlite_antrian_referensi = $this->db('mlite_antrian_referensi')->where('nomor_referensi', $nomor_referensi)->where('kodebooking', $kode_booking)->oneArray();

      if (!$mlite_antrian_referensi) {
        $this->notify('failure', 'Data referensi antrean tidak ditemukan.');
        redirect(url([ADMIN, 'jkn_mobile', 'taskid']));
      }

      $reg_periksa = $this->db('reg_periksa')
        ->where('tgl_registrasi', $mlite_antrian_referensi['tanggal_periksa'])
        ->where('no_rkm_medis', $mlite_antrian_referensi['no_rkm_medis'])
        ->oneArray();

      if (!$reg_periksa) {
        $this->notify('failure', 'Data registrasi pasien tidak ditemukan.');
        redirect(url([ADMIN, 'jkn_mobile', 'taskid']));
      }

      $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)', 'no_resep' => 'no_resep'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
      $jenisresep = 'Non racikan';
      if($resep_obat) {
        $resep_dokter_racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep_obat['no_resep'])->oneArray();
        if(!empty($resep_dokter_racikan)) {
          $jenisresep = 'Racikan';
        }
      }

      // Pastikan task 1-7 yang ada di SIMRS otomatis tersimpan di database jika belum ada
      $task_resolved = $this->_resolveTaskTimes(
          $reg_periksa['no_rawat'] ?? '',
          $reg_periksa['no_rkm_medis'] ?? '',
          $mlite_antrian_referensi['tanggal_periksa'],
          $kode_booking,
          $nomor_referensi,
          []
      );
      foreach ($task_resolved as $tid => $rData) {
          if (!empty($rData['waktu'])) {
              $waktu_millis = is_numeric($rData['waktu']) ? (int)$rData['waktu'] : strtotime($rData['waktu']) * 1000;
              $exist = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', $tid)->oneArray();
              if (!$exist) {
                  $this->db('mlite_antrian_referensi_taskid')->save([
                      'tanggal_periksa' => $mlite_antrian_referensi['tanggal_periksa'],
                      'nomor_referensi' => $kode_booking,
                      'taskid' => (int)$tid,
                      'waktu' => $waktu_millis,
                      'status' => 'Belum',
                      'keterangan' => $rData['source'] ?? ''
                  ]);
              }
          }
      }

      $taskid1 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '1')->where('status','Belum')->oneArray();
      $taskid2 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '2')->where('status','Belum')->oneArray();
      $taskid3 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '3')->where('status','Belum')->oneArray();
      $taskid4 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '4')->where('status','Belum')->oneArray();
      $taskid5 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '5')->where('status','Belum')->oneArray();
      $taskid6 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '6')->where('status','Belum')->oneArray();
      $taskid7 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '7')->where('status','Belum')->oneArray();
      $taskid99 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kode_booking)->where('taskid', '99')->where('status','Belum')->oneArray();

      if ($versi == 'batal') {
        $data1 = [
          'kodebooking' => $mlite_antrian_referensi['kodebooking'],
          'taskid' => 99,
          'waktu' => $taskid99['waktu']
        ];
        $data1 = json_encode($data1);
        echo 'Request:<br>';
        echo $data1;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output1 = BpjsService::post($url, $data1, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json1 = $this->_decodeBpjsResponse($output1);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json1));
        if(($json1['metadata']['code'] ?? null) == 200){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 99)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json1['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 99)
          ->save([
            'status' => 'Gagal',
            'keterangan' => $json1['metadata']['code'].' : '.$json1['metadata']['message']
          ]);
        }
      } else {

        echo 'Menjalankan WS taskid (1) mulai tunggu admisi<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data1 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 1,
            'waktu' => $taskid1['waktu'],
        ];
        $data1 = json_encode($data1);
        echo 'Request:<br>';
        echo $data1;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output1 = BpjsService::post($url, $data1, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json1 = $this->_decodeBpjsResponse($output1);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json1));
        if(($json1['metadata']['code'] ?? null) == 200 || ($json1['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 1)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json1['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 1)
          ->save([
            'keterangan' => $json1['metadata']['code'].' : '.$json1['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (2) mulai pelayanan admisi<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data2 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 2,
            'waktu' => $taskid2['waktu'],
        ];
        $data2 = json_encode($data2);
        echo 'Request:<br>';
        echo $data2;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output2 = BpjsService::post($url, $data2, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json2 = $this->_decodeBpjsResponse($output2);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json2));
        if(($json2['metadata']['code'] ?? null) == 200 || ($json2['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 2)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json2['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 2)
          ->save([
            'keterangan' => $json2['metadata']['code'].' : '.$json2['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (3) mulai tunggu poli<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data3 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 3,
            'waktu' => $taskid3['waktu'],
        ];
        $data3 = json_encode($data3);
        echo 'Request:<br>';
        echo $data3;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output3 = BpjsService::post($url, $data3, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json3 = $this->_decodeBpjsResponse($output3);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json3));
        if(($json3['metadata']['code'] ?? null) == 200 || ($json3['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 3)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json3['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 3)
          ->save([
            'keterangan' => $json3['metadata']['code'].' : '.$json3['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (4) mulai pelayanan poli<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data4 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 4,
            'waktu' => $taskid4['waktu'],
        ];
        $data4 = json_encode($data4);
        echo 'Request:<br>';
        echo $data4;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output4 = BpjsService::post($url, $data4, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json4 = $this->_decodeBpjsResponse($output4);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json4));
        if(($json4['metadata']['code'] ?? null) == 200 || ($json4['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 4)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json4['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 4)
          ->save([
            'keterangan' => $json4['metadata']['code'].' : '.$json4['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (5) selesai pelayanan poli<br>';
        echo '-------------------------------------<br>';
        $data = [];
        if($versi == 'v2') {
          $data5 = [
              'kodebooking' => $mlite_antrian_referensi['kodebooking'],
              'taskid' => 5,
              'waktu' => $taskid5['waktu']
          ];
        }
        if($versi == 'v3') {
          $data5 = [
              'kodebooking' => $mlite_antrian_referensi['kodebooking'],
              'taskid' => 5,
              'waktu' => $taskid5['waktu'],
              'jenisresep' => $jenisresep
          ];
        }
        $data5 = json_encode($data5);
        echo 'Request:<br>';
        echo $data5;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output5 = BpjsService::post($url, $data5, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json5 = $this->_decodeBpjsResponse($output5);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json5));
        if(($json5['metadata']['code'] ?? null) == 200 || ($json5['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 5)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json5['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 5)
          ->save([
            'keterangan' => $json5['metadata']['code'].' : '.$json5['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (6) permintaan resep apotek<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data6 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 6,
            'waktu' => $taskid6['waktu'],
        ];
        $data6 = json_encode($data6);
        echo 'Request:<br>';
        echo $data6;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output6 = BpjsService::post($url, $data6, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json6 = $this->_decodeBpjsResponse($output6);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json6));
        if(($json6['metadata']['code'] ?? null) == 200 || ($json6['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 6)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json6['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 6)
          ->save([
            'keterangan' => $json6['metadata']['code'].' : '.$json6['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
  
        echo 'Menjalankan WS taskid (7) selesai pelayanan apotek<br>';
        echo '-------------------------------------<br>';
        $data = [];
        $data7 = [
            'kodebooking' => $mlite_antrian_referensi['kodebooking'],
            'taskid' => 7,
            'waktu' => $taskid7['waktu'],
        ];
        $data7 = json_encode($data7);
        echo 'Request:<br>';
        echo $data7;
        echo '<br>';
        $url = $this->bpjsurl.'antrean/updatewaktu';
        $output7 = BpjsService::post($url, $data7, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json7 = $this->_decodeBpjsResponse($output7);
        echo 'Response:<br>';
        echo json_encode(htmlspecialchars_array($json7));
        if(($json7['metadata']['code'] ?? null) == 200 || ($json7['metadata']['code'] ?? null) == 208){
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 7)
          ->save([
            'status' => 'Sudah',
            'keterangan' => $json7['metadata']['message']
          ]);
        } else {
          $this->db('mlite_antrian_referensi_taskid')
          ->where('nomor_referensi', $kode_booking)
          ->where('taskid', 7)
          ->save([
            'keterangan' => $json7['metadata']['code'].' : '.$json7['metadata']['message']
          ]);
        }
        echo '<br>-------------------------------------<br><br>';
      }
      
      exit();
    }

    public function anyLogTaskID()
    {
      $this->_addHeaderFiles();
      $this->getCssCard();
      $date = date('Y-m');
      if(isset($_POST['periode_antrol']) && $_POST['periode_antrol'] !='')
        $date = $_POST['periode_antrol'];
      $query = $this->db()->pdo()->prepare("SELECT COUNT(*) as jumlah, keterangan FROM mlite_antrian_referensi WHERE tanggal_periksa LIKE ? GROUP BY keterangan");
      $query->execute([$date.'%']);
      $query = $query->fetchAll(\PDO::FETCH_ASSOC);

      $rows = [];
      $a = [];
      $a['jumlah'] = 0;
      $a['keterangan'] = '';
      foreach ($query as $q) {
        if (strpos($q['keterangan'], 'Rujukan nomor ') !== false) {
          $s = explode(" ", $q['keterangan']);
          unset($s[2]);
          $s = implode(" ", $s);
          $a['keterangan'] = $s;
          $a['jumlah'] = $a['jumlah'] + 1;
        } else if ($q['keterangan'] == null){
          $q['keterangan'] = 'NULL';
          $rows[] = $q;
        } else {
          $rows[] = $q;
        }
      }
      $rows[] = $a;

      $taskid = $rows;
      return $this->draw('logtaskid.html', ['taskid' => $taskid,'tgl'=>$date]);
    }

    public function anyAntrolLocal()
    {
        $this->_addHeaderFiles();
        $this->getCssCard();
        $date = date('Y-m-d');
        if (isset($_POST['periode_antrol']) && $_POST['periode_antrol'] != '')
            $date = $_POST['periode_antrol'];
        $query = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim', 'Gagal')->toArray();
        $rows = [];
        foreach ($query as $q) {
            $no_rawat = $this->db('reg_periksa')->select(['no_rawat' => 'reg_periksa.no_rawat'])->where('no_rkm_medis', $q['no_rkm_medis'])->where('tgl_registrasi', $date)->oneArray();
            $q['nm_pasien'] = $this->core->getPasienInfo('nm_pasien', $q['no_rkm_medis']);
            $rawat_num = $no_rawat['no_rawat'] ?? '';
            $q['no_rawat'] = $rawat_num;
            $q['no_rawat_conv'] = convertNorawat($rawat_num);
            $rows[] = $q;
        }

        $taskid = $rows;
        return $this->draw('antrollocal.html', ['taskid' => $taskid, 'tgl' => $date]);
    }

    public function getSettings()
    {
        if ($this->core->getUserInfo('role') != 'admin') {
            $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
            redirect(url([ADMIN, 'jkn_mobile', 'index']));
        }
        $this->_addHeaderFiles();
        $this->assign['title'] = 'Pengaturan Modul JKN Mobile';
        $this->assign['propinsi'] = $this->db('propinsi')->where('kd_prop', $this->settings->get('jkn_mobile.kdprop'))->oneArray();
        $this->assign['kabupaten'] = $this->db('kabupaten')->where('kd_kab', $this->settings->get('jkn_mobile.kdkab'))->oneArray();
        $this->assign['kecamatan'] = $this->db('kecamatan')->where('kd_kec', $this->settings->get('jkn_mobile.kdkec'))->oneArray();
        $this->assign['kelurahan'] = $this->db('kelurahan')->where('kd_kel', $this->settings->get('jkn_mobile.kdkel'))->oneArray();
        $this->assign['suku_bangsa'] = $this->db('suku_bangsa')->toArray();
        $this->assign['bahasa_pasien'] = $this->db('bahasa_pasien')->toArray();
        $this->assign['cacat_fisik'] = $this->db('cacat_fisik')->toArray();
        $this->assign['perusahaan_pasien'] = $this->db('perusahaan_pasien')->toArray();
        $this->assign['poliklinik'] = $this->_getPoliklinik($this->settings->get('jkn_mobile.display'));
        $this->assign['exclude_taskid'] = $this->_getPoliklinik($this->settings->get('jkn_mobile.exclude_taskid'));
        $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();

        $this->assign['jkn_mobile'] = htmlspecialchars_array($this->settings('jkn_mobile'));
        return $this->draw('settings.html', ['settings' => htmlspecialchars_array($this->assign)]);
    }

    public function postSaveSettings()
    {
        if ($this->core->getUserInfo('role') != 'admin') {
            $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
            redirect(url([ADMIN, 'jkn_mobile', 'index']));
        }
        if (isset($_POST['jkn_mobile']['display']) && is_array($_POST['jkn_mobile']['display'])) {
            $_POST['jkn_mobile']['display'] = implode(',', $_POST['jkn_mobile']['display']);
        } else {
            $_POST['jkn_mobile']['display'] = '';
        }
        
        if (isset($_POST['jkn_mobile']['exclude_taskid']) && is_array($_POST['jkn_mobile']['exclude_taskid'])) {
            $_POST['jkn_mobile']['exclude_taskid'] = implode(',', $_POST['jkn_mobile']['exclude_taskid']);
        } else {
            $_POST['jkn_mobile']['exclude_taskid'] = '';
        }

        foreach ($_POST['jkn_mobile'] as $key => $val) {
            $this->settings('jkn_mobile', $key, $val);
        }
        $this->notify('success', 'Pengaturan telah disimpan');
        redirect(url([ADMIN, 'jkn_mobile', 'settings']));
    }

    private function _getPoliklinik($kd_poli = null)
    {
        $result = [];
        $rows = $this->db('poliklinik')->toArray();

        if (!$kd_poli) {
            $kd_poliArray = [];
        } else {
            $kd_poliArray = explode(',', $kd_poli);
        }

        foreach ($rows as $row) {
            if (empty($kd_poliArray)) {
                $attr = '';
            } else {
                if (in_array($row['kd_poli'], $kd_poliArray)) {
                    $attr = 'selected';
                } else {
                    $attr = '';
                }
            }
            $result[] = ['kd_poli' => $row['kd_poli'], 'nm_poli' => $row['nm_poli'], 'attr' => $attr];
        }
        return $result;
    }

    public function anyAntrol()
    {
        $this->getCssCard();
        $tgl_kunjungan = date('Y-m-d');
        $bulan = substr($tgl_kunjungan, 5, 2);
        $tahun = substr($tgl_kunjungan, 0, 4);
        $tanggal = substr($tgl_kunjungan, 8, 2);
        $depanUrlTanggal = $this->bpjsurl . 'dashboard/waktutunggu/tanggal/';
        $depanUrlBulan = $this->bpjsurl . 'dashboard/waktutunggu/bulan/';
        if (isset($_POST['periode'])) {
            $waktu = $_POST['waktu'];
            $tgl_kunjungan = $_POST['periode'];
            $tgl_kunjungan = preg_replace('/\s+/', '', $tgl_kunjungan);
            $bulan = substr($tgl_kunjungan, 5, 2);
            $tahun = substr($tgl_kunjungan, 0, 4);
            $tanggal = substr($tgl_kunjungan, 8, 2);
            if ($_POST['rute'] == 'tanggal') {
                $url = $depanUrlTanggal . $tahun . '-' . $bulan . '-' . $tanggal . '/waktu/' . $waktu;
            } else {
                $url = $depanUrlBulan . $bulan . '/tahun/' . $tahun . '/waktu/' . $waktu;
            }
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
            $json = json_decode($output, true);
            $response = [];
            if($json && isset($json['metadata']['code']) && $json['metadata']['code'] == '200') {
              $response = $json['response']['list'];
            }
            $this->assign['list'] = $response;

            echo $this->draw('antrol.display.html', ['row' => htmlspecialchars_array($this->assign)]);
        } else {
            $url = $depanUrlTanggal . $tahun . '-' . $bulan . '-' . $tanggal . '/waktu/server';
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
            $json = json_decode($output, true);
            $response = [];
            if($json && isset($json['metadata']['code']) && $json['metadata']['code'] == '200') {
              $response = $json['response']['list'];
            }
            $this->assign['list'] = $response;

            return $this->draw('antrol.html', ['row' => htmlspecialchars_array($this->assign)]);
        }
        exit();
    }

    public function getQrAntrol(){
        $this->_addHeaderFiles();
        $this->getCssCard();
        $this->core->addJS(url(BASE_DIR.'/assets/jscripts/Chart.bundle.min.js'));
        if (isset($_GET['tgl'])) {
            $tanggal = $_GET['tgl'];
          } else {
            $tanggal = date('Y-m-d');
          }
        $sql = "SELECT * FROM bridging_sep WHERE tglsep = ? AND jnspelayanan = '2' AND kdpolitujuan NOT IN ('IGD','HDL')";
        $query = $this->db()->pdo()->prepare($sql);
        $query->execute([$tanggal]);
        $sep_terbit = $query->fetchAll();
        $jml_sep = 1;
        $jml_antrol = 1;
        $taskid1 = 0;
        $taskid2 = 0;
        $taskid3 = 0;
        $taskid4 = 0;
        $taskid5 = 0;
        $taskid6 = 0;
        $taskid7 = 0;
        foreach ($sep_terbit as $valuex) {
            $nomor_referensi = '';
            $cari_antrol = $this->db('mlite_antrian_referensi')->where('nomor_referensi',$valuex['no_rujukan'])->where('tanggal_periksa',$tanggal)->where('kodebooking','!=','')->where('status_kirim','Sudah')->oneArray();
            if (!$cari_antrol) {
                if ($valuex['noskdp'] != '') {
                    $cari_antrol_kontrol = $this->db('mlite_antrian_referensi')->where('nomor_referensi',$valuex['noskdp'])->where('tanggal_periksa',$tanggal)->where('kodebooking','!=','')->where('status_kirim','Sudah')->oneArray();
                    if ($cari_antrol_kontrol) {
                        $nomor_referensi = $cari_antrol_kontrol['kodebooking'];
                        $jml_antrol = $jml_antrol + 1;
                    }
                }
            } else {
                $nomor_referensi = $cari_antrol['nomor_referensi'];
                $jml_antrol = $jml_antrol + 1;
            }
            $task = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi',$nomor_referensi)->where('status','Sudah')->toArray();
            foreach ($task as $value) {
                switch ($value['taskid']) {
                    case '1':
                        $taskid1++;
                        break;
                    case '2':
                        $taskid2++;
                        break;
                    case '3':
                        $taskid3++;
                        break;
                    case '4':
                        $taskid4++;
                        break;
                    case '5':
                        $taskid5++;
                        break;
                    case '6':
                        $taskid6++;
                        break;
                    case '7':
                        $taskid7++;
                        break;

                    default:
                        break;
                }
            }
            $jml_sep = $jml_sep + 1;
        }
        $qr_manual = $jml_antrol / $jml_sep * 100;
        $stats['tanggal'] = dateIndonesia($tanggal);
        $stats['jml_sep'] = $jml_sep;
        $stats['jml_antrol'] = $jml_antrol;
        $stats['qr_manual'] = number_format($qr_manual,2);
        $stats['taskid1'] = $taskid1;
        $stats['taskid2'] = $taskid2;
        $stats['taskid3'] = $taskid3;
        $stats['taskid4'] = $taskid4;
        $stats['taskid5'] = $taskid5;
        $stats['taskid6'] = $taskid6;
        $stats['taskid7'] = $taskid7;
        return $this->draw('manage.qurate.html', ['stats' => $stats]);
    }

    public function getAjax()
    {
        header('Content-type: text/html');
        $show = isset($_GET['show']) ? $_GET['show'] : "";
        switch($show){
        	default:
          break;
        	case "propinsi":
          $propinsi = $this->db('propinsi')->toArray();
          foreach ($propinsi as $row) {
            echo '<tr class="pilihpropinsi" data-kdprop="'.$row['kd_prop'].'" data-namaprop="'.$row['nm_prop'].'">';
      			echo '<td>'.$row['kd_prop'].'</td>';
      			echo '<td>'.$row['nm_prop'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kabupaten":
          $kabupaten = $this->db('kabupaten')->toArray();
          foreach ($kabupaten as $row) {
            echo '<tr class="pilihkabupaten" data-kdkab="'.$row['kd_kab'].'" data-namakab="'.$row['nm_kab'].'">';
      			echo '<td>'.$row['kd_kab'].'</td>';
      			echo '<td>'.$row['nm_kab'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kecamatan":
          $kecamatan = $this->db('kecamatan')->toArray();
          foreach ($kecamatan as $row) {
            echo '<tr class="pilihkecamatan" data-kdkec="'.$row['kd_kec'].'" data-namakec="'.$row['nm_kec'].'">';
      			echo '<td>'.$row['kd_kec'].'</td>';
      			echo '<td>'.$row['nm_kec'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kelurahan":
          // Alternative SQL join in Datatables
          $id_table = 'kd_kel';
          $columns = array(
                       'kd_kel',
                       'nm_kel'
                     );
          //$action = '"Test" as action';
          // gunakan join disini
          $from = 'kelurahan';

          $id_table = $id_table != '' ? $id_table . ',' : '';
          // custom SQL
          $sql = "SELECT {$id_table} ".implode(',', $columns)." FROM {$from}";

          // search
          if (isset($_GET['search']['value']) && $_GET['search']['value'] != '') {
              $search = $_GET['search']['value'];
              $where  = '';
              // create parameter pencarian kesemua kolom yang tertulis
              // di $columns
              for ($i=0; $i < count($columns); $i++) {
                  $where .= $columns[$i] . ' LIKE "%'.$search.'%"';

                  // agar tidak menambahkan 'OR' diakhir Looping
                  if ($i < count($columns)-1) {
                      $where .= ' OR ';
                  }
              }

              $sql .= ' WHERE ' . $where;
          }

          //SORT Kolom
          $sortColumn = isset($_GET['order'][0]['column']) ? $_GET['order'][0]['column'] : 0;
          $sortDir    = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';

          $sortColumn = $columns[$sortColumn];

          $sql .= " ORDER BY {$sortColumn} {$sortDir}";

          $query = $this->db()->pdo()->prepare($sql);
          $query->execute();
          $query = $query->fetchAll();

          // var_dump($sql);
          //$count = $database->query($sql);
          // hitung semua data
          $totaldata = count($query);

          // memberi Limit
          $start  = isset($_GET['start']) ? $_GET['start'] : 0;
          $length = isset($_GET['length']) ? $_GET['length'] : 10;


          $sql .= " LIMIT {$start}, {$length}";

          $data = $this->db()->pdo()->prepare($sql);
          $data->execute();
          $data = $data->fetchAll();

          // create json format
          $datatable['draw']            = isset($_GET['draw']) ? intval(htmlspecialchars($_GET['draw'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) : 1;
        $datatable['recordsTotal']    = $totaldata;
        $datatable['recordsFiltered'] = $totaldata;
        $datatable['data']            = array();

          foreach ($data as $row) {

              $fields = array();
              $fields['0'] = $row['kd_kel'];
              $fields['1'] = '<span class="pilihkelurahan" data-kdkel="'.$row['kd_kel'].'" data-namakel="'.$row['nm_kel'].'">'.$row['nm_kel'].'</span>';
              $datatable['data'][] = $fields;

          }

          echo json_encode(htmlspecialchars_array($datatable));

          break;

        }
        exit();
    }

    public function getBookingAntrol()
    {
        return $this->anyBookingAntrol();
    }

    public function anyBookingAntrol()
    {
        $this->_addHeaderFiles();

        $tgl_awal = $_REQUEST['tgl_awal'] ?? date('Y-m-01');
        $tgl_akhir = $_REQUEST['tgl_akhir'] ?? date('Y-m-d');
        $status_filter = $_REQUEST['status'] ?? 'Semua';
        $kd_poli_filter = $_REQUEST['kd_poli'] ?? 'Semua';
        $keyword = trim($_REQUEST['keyword'] ?? '');
        $periode_preset = $_REQUEST['preset'] ?? '';

        if ($periode_preset === 'hari_ini') {
            $tgl_awal = date('Y-m-d');
            $tgl_akhir = date('Y-m-d');
        } elseif ($periode_preset === 'besok') {
            $tgl_awal = date('Y-m-d', strtotime('+1 day'));
            $tgl_akhir = date('Y-m-d', strtotime('+1 day'));
        } elseif ($periode_preset === 'minggu_ini') {
            $tgl_awal = date('Y-m-d', strtotime('-7 days'));
            $tgl_akhir = date('Y-m-d');
        } elseif ($periode_preset === 'bulan_ini') {
            $tgl_awal = date('Y-m-01');
            $tgl_akhir = date('Y-m-t');
        } elseif ($periode_preset === 'semua') {
            $tgl_awal = '';
            $tgl_akhir = '';
        }

        $poliklinik = $this->db('poliklinik')->where('status', '1')->asc('nm_poli')->toArray();

        $where_ar = [];
        $params_ar = [];

        if (!empty($tgl_awal) && !empty($tgl_akhir)) {
            $where_ar[] = "tanggal_periksa BETWEEN ? AND ?";
            $params_ar[] = $tgl_awal;
            $params_ar[] = $tgl_akhir;
        } elseif (!empty($tgl_awal)) {
            $where_ar[] = "tanggal_periksa >= ?";
            $params_ar[] = $tgl_awal;
        } elseif (!empty($tgl_akhir)) {
            $where_ar[] = "tanggal_periksa <= ?";
            $params_ar[] = $tgl_akhir;
        }

        $where_ar_sql = !empty($where_ar) ? "WHERE " . implode(" AND ", $where_ar) : "";

        $sql = "SELECT 
            ar.tanggal_periksa,
            ar.kodebooking,
            ar.nomor_kartu,
            ar.nomor_referensi,
            ar.jenis_kunjungan,
            ar.status_kirim,
            ar.keterangan as ar_keterangan,
            ar.no_rkm_medis as ar_no_rkm_medis,
            p.no_rkm_medis,
            p.nm_pasien,
            p.jk,
            p.no_ktp,
            p.no_tlp,
            p.alamat,
            br.no_reg,
            br.jam_booking,
            br.waktu_kunjungan,
            br.status as booking_status,
            COALESCE(br.kd_dokter, rp.kd_dokter) as kd_dokter,
            d.nm_dokter,
            COALESCE(br.kd_poli, rp.kd_poli) as kd_poli,
            pl.nm_poli,
            pj.png_jawab,
            rp.no_rawat,
            rp.stts as reg_status,
            rp.jam_reg,
            batal.tanggal_batal,
            batal.keterangan as batal_keterangan
        FROM (
            SELECT 
                kodebooking,
                MAX(tanggal_periksa) as tanggal_periksa,
                MAX(no_rkm_medis) as no_rkm_medis,
                MAX(nomor_kartu) as nomor_kartu,
                MAX(nomor_referensi) as nomor_referensi,
                MAX(jenis_kunjungan) as jenis_kunjungan,
                MAX(status_kirim) as status_kirim,
                MAX(keterangan) as keterangan
            FROM mlite_antrian_referensi
            $where_ar_sql
            GROUP BY kodebooking
        ) ar
        LEFT JOIN pasien p ON p.no_rkm_medis = ar.no_rkm_medis
        LEFT JOIN booking_registrasi br ON br.no_rkm_medis = ar.no_rkm_medis AND br.tanggal_periksa = ar.tanggal_periksa
        LEFT JOIN reg_periksa rp ON rp.no_rkm_medis = ar.no_rkm_medis AND rp.tgl_registrasi = ar.tanggal_periksa
        LEFT JOIN dokter d ON d.kd_dokter = COALESCE(br.kd_dokter, rp.kd_dokter)
        LEFT JOIN poliklinik pl ON pl.kd_poli = COALESCE(br.kd_poli, rp.kd_poli)
        LEFT JOIN penjab pj ON pj.kd_pj = COALESCE(br.kd_pj, rp.kd_pj)
        LEFT JOIN (
            SELECT kodebooking, MAX(tanggal_batal) as tanggal_batal, MAX(keterangan) as keterangan 
            FROM mlite_antrian_referensi_batal 
            GROUP BY kodebooking
        ) batal ON batal.kodebooking = ar.kodebooking
        ORDER BY ar.tanggal_periksa DESC, br.no_reg ASC";

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params_ar);
        $raw_rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $all_refs = [];
        $all_no_rawat = [];
        $all_no_rm = [];
        foreach ($raw_rows as $rr) {
            if (!empty($rr['kodebooking'])) $all_refs[] = $rr['kodebooking'];
            if (!empty($rr['nomor_referensi'])) $all_refs[] = $rr['nomor_referensi'];
            if (!empty($rr['no_rawat'])) $all_no_rawat[] = $rr['no_rawat'];
            $rm = $rr['no_rkm_medis'] ?: ($rr['ar_no_rkm_medis'] ?? '');
            if (!empty($rm)) $all_no_rm[] = $rm;
        }
        $all_refs = array_values(array_unique(array_filter($all_refs)));
        $all_no_rawat = array_values(array_unique(array_filter($all_no_rawat)));
        $all_no_rm = array_values(array_unique(array_filter($all_no_rm)));

        $task_map = [];
        if (!empty($all_refs)) {
            $chunks = array_chunk($all_refs, 500);
            foreach ($chunks as $chunk) {
                $in_placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $task_stmt = $this->db()->pdo()->prepare("SELECT nomor_referensi, taskid, waktu, status, keterangan FROM mlite_antrian_referensi_taskid WHERE nomor_referensi IN ($in_placeholders)");
                $task_stmt->execute($chunk);
                $task_results = $task_stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($task_results as $tr) {
                    $ref = $tr['nomor_referensi'];
                    $tid = (string)$tr['taskid'];
                    $task_map[$ref][$tid] = $tr;
                }
            }
        }

        // Cache batch data operasional SIMRS untuk mempercepat penghitungan task 1-7 tanpa N+1 query
        $op_cache = [
            'mutasi_berkas' => [],
            'pemeriksaan_ralan' => [],
            'resep_obat' => [],
            'loket' => []
        ];

        if (!empty($all_no_rawat)) {
            $chunks_nr = array_chunk($all_no_rawat, 500);
            foreach ($chunks_nr as $cnr) {
                $ph = implode(',', array_fill(0, count($cnr), '?'));
                try {
                    $mb_stmt = $this->db()->pdo()->prepare("SELECT no_rawat, dikirim, diterima FROM mutasi_berkas WHERE no_rawat IN ($ph)");
                    $mb_stmt->execute($cnr);
                    foreach ($mb_stmt->fetchAll(\PDO::FETCH_ASSOC) as $mb) {
                        $op_cache['mutasi_berkas'][$mb['no_rawat']] = $mb;
                    }
                } catch (\Throwable $e) {}

                try {
                    $pr_stmt = $this->db()->pdo()->prepare("SELECT no_rawat, concat(tgl_perawatan,' ',jam_rawat) as datajam FROM pemeriksaan_ralan WHERE no_rawat IN ($ph)");
                    $pr_stmt->execute($cnr);
                    foreach ($pr_stmt->fetchAll(\PDO::FETCH_ASSOC) as $pr) {
                        $op_cache['pemeriksaan_ralan'][$pr['no_rawat']] = $pr;
                    }
                } catch (\Throwable $e) {}

                try {
                    $ro_stmt = $this->db()->pdo()->prepare("SELECT no_rawat, concat(tgl_peresepan,' ',jam_peresepan) as datajam, concat(tgl_perawatan,' ',jam) as datajam2 FROM resep_obat WHERE no_rawat IN ($ph)");
                    $ro_stmt->execute($cnr);
                    foreach ($ro_stmt->fetchAll(\PDO::FETCH_ASSOC) as $ro) {
                        $op_cache['resep_obat'][$ro['no_rawat']] = $ro;
                    }
                } catch (\Throwable $e) {}
            }
        }

        if (!empty($all_no_rm)) {
            $chunks_rm = array_chunk($all_no_rm, 500);
            foreach ($chunks_rm as $crm) {
                $ph = implode(',', array_fill(0, count($crm), '?'));
                try {
                    $lk_stmt = $this->db()->pdo()->prepare("SELECT no_rkm_medis, postdate, start_time, end_time FROM mlite_antrian_loket WHERE no_rkm_medis IN ($ph)");
                    $lk_stmt->execute($crm);
                    foreach ($lk_stmt->fetchAll(\PDO::FETCH_ASSOC) as $lk) {
                        $key = $lk['no_rkm_medis'] . '_' . $lk['postdate'];
                        $op_cache['loket'][$key] = $lk;
                    }
                } catch (\Throwable $e) {}
            }
        }

        $rows = [];
        $total_all = 0;
        $total_belum = 0;
        $total_terdaftar = 0;
        $total_batal = 0;

        foreach ($raw_rows as $row) {
            $is_batal = ($row['status_kirim'] === 'Batal' || 
                         $row['booking_status'] === 'Batal' || 
                         $row['reg_status'] === 'Batal' || 
                         !empty($row['tanggal_batal']));

            $is_terdaftar = (!$is_batal && (!empty($row['no_rawat']) || $row['booking_status'] === 'Terdaftar'));

            if ($is_batal) {
                $status_label = 'Batal';
                $status_class = 'danger';
            } elseif ($is_terdaftar) {
                $status_label = 'Terdaftar';
                $status_class = 'success';
            } else {
                $status_label = 'Belum';
                $status_class = 'warning';
            }

            $total_all++;
            if ($status_label === 'Batal') $total_batal++;
            elseif ($status_label === 'Terdaftar') $total_terdaftar++;
            else $total_belum++;

            if ($status_filter !== 'Semua' && $status_filter !== $status_label) {
                continue;
            }

            if ($kd_poli_filter !== 'Semua' && $row['kd_poli'] !== $kd_poli_filter) {
                continue;
            }

            if (!empty($keyword)) {
                $haystack = strtolower(
                    ($row['nm_pasien'] ?? '') . ' ' .
                    ($row['no_rkm_medis'] ?? '') . ' ' .
                    ($row['ar_no_rkm_medis'] ?? '') . ' ' .
                    ($row['kodebooking'] ?? '') . ' ' .
                    ($row['nomor_referensi'] ?? '') . ' ' .
                    ($row['nomor_kartu'] ?? '') . ' ' .
                    ($row['no_ktp'] ?? '') . ' ' .
                    ($row['nm_dokter'] ?? '') . ' ' .
                    ($row['nm_poli'] ?? '')
                );
                if (strpos($haystack, strtolower($keyword)) === false) {
                    continue;
                }
            }

            $kb = $row['kodebooking'];
            $row['status_label'] = $status_label;
            $row['status_class'] = $status_class;

            // Hitung & selaraskan Task ID 1-7 (menggabungkan BPJS dan data operasional SIMRS seperti /admin/jkn_mobile/taskid)
            $row_tasks = $this->_resolveTaskTimes(
                $row['no_rawat'] ?? '',
                $row['no_rkm_medis'] ?: $row['ar_no_rkm_medis'],
                $row['tanggal_periksa'],
                $kb,
                $row['nomor_referensi'] ?? '',
                $task_map,
                $op_cache
            );
            $row['task_resolved'] = $row_tasks;

            // Buat HTML Pill Task ID 1-7
            $rawatParam = !empty($row['no_rawat']) ? convertNoRawat($row['no_rawat']) : '';
            $pills_html = '<div class="task-strip">';
            for ($i = 1; $i <= 7; $i++) {
                $si = (string)$i;
                $t = $row_tasks[$si];
                $has_waktu = !empty($t['waktu']);
                $jam = $has_waktu ? date('H:i:s', strtotime($t['waktu'])) : '';

                if ($t['status'] === 'Sudah') {
                    $cls = 'task-done';
                    $title_t = "Task {$i}: Terkirim ke BPJS ({$jam})";
                } elseif ($has_waktu) {
                    $cls = 'task-simrs';
                    $title_t = "Task {$i}: Selesai di SIMRS ({$jam}) - " . ($t['source'] ?: 'Tercatat');
                } elseif ($t['status'] === 'Gagal') {
                    $cls = 'task-fail';
                    $title_t = "Task {$i}: Gagal Kirim";
                } else {
                    $cls = 'task-wait';
                    $title_t = "Task {$i}: Belum Dilayani";
                }

                $onclickAttr = !empty($rawatParam) ? 'onclick="bukaModalTaskID(\'' . $rawatParam . '\')"' : 'onclick="bukaDetailAntrol(\'' . $kb . '\')"';
                $pills_html .= '<span class="task-pill ' . $cls . '" data-toggle="tooltip" data-placement="top" title="' . htmlspecialchars($title_t, ENT_QUOTES, 'UTF-8') . '" ' . $onclickAttr . '>' . $i . '</span>';
            }
            $pills_html .= '</div>';
            $row['task_pills_html'] = $pills_html;

            $jk_map = [
                '1' => 'Rujukan FKTP',
                '2' => 'Rujukan Internal',
                '3' => 'Kontrol (SKDP)',
                '4' => 'Rujukan Antar RS'
            ];
            $row['jenis_kunjungan_label'] = $jk_map[$row['jenis_kunjungan']] ?? 'Rujukan';

            $rows[] = $row;
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->_exportBookingAntrolCsv($rows, $tgl_awal, $tgl_akhir);
            exit();
        }

        return $this->draw('bookingantrol.html', [
            'rows' => $rows,
            'poliklinik' => $poliklinik,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'status_filter' => $status_filter,
            'kd_poli_filter' => $kd_poli_filter,
            'keyword' => $keyword,
            'preset' => $periode_preset,
            'total_all' => $total_all,
            'total_belum' => $total_belum,
            'total_terdaftar' => $total_terdaftar,
            'total_batal' => $total_batal,
        ]);
    }

    public function getDetailBookingAntrol($kodebooking = '')
    {
        header('Content-Type: application/json');
        $kodebooking = trim($kodebooking ?: ($_GET['kodebooking'] ?? $_POST['kodebooking'] ?? ''));

        if (empty($kodebooking)) {
            echo json_encode(['status' => 'error', 'message' => 'Kode booking tidak boleh kosong.']);
            exit();
        }

        $sql = "SELECT 
            ar.tanggal_periksa,
            ar.kodebooking,
            ar.nomor_kartu,
            ar.nomor_referensi,
            ar.jenis_kunjungan,
            ar.status_kirim,
            ar.keterangan as ar_keterangan,
            ar.no_rkm_medis as ar_no_rkm_medis,
            p.no_rkm_medis,
            p.nm_pasien,
            p.jk,
            p.no_ktp,
            p.no_tlp,
            p.alamat,
            p.tgl_lahir,
            br.no_reg,
            br.tanggal_booking,
            br.jam_booking,
            br.waktu_kunjungan,
            br.status as booking_status,
            COALESCE(br.kd_dokter, rp.kd_dokter) as kd_dokter,
            d.nm_dokter,
            COALESCE(br.kd_poli, rp.kd_poli) as kd_poli,
            pl.nm_poli,
            pj.png_jawab,
            rp.no_rawat,
            rp.stts as reg_status,
            rp.jam_reg,
            batal.tanggal_batal,
            batal.keterangan as batal_keterangan
        FROM (
            SELECT 
                kodebooking,
                MAX(tanggal_periksa) as tanggal_periksa,
                MAX(no_rkm_medis) as no_rkm_medis,
                MAX(nomor_kartu) as nomor_kartu,
                MAX(nomor_referensi) as nomor_referensi,
                MAX(jenis_kunjungan) as jenis_kunjungan,
                MAX(status_kirim) as status_kirim,
                MAX(keterangan) as keterangan
            FROM mlite_antrian_referensi
            WHERE kodebooking = ?
            GROUP BY kodebooking
        ) ar
        LEFT JOIN pasien p ON p.no_rkm_medis = ar.no_rkm_medis
        LEFT JOIN booking_registrasi br ON br.no_rkm_medis = ar.no_rkm_medis AND br.tanggal_periksa = ar.tanggal_periksa
        LEFT JOIN reg_periksa rp ON rp.no_rkm_medis = ar.no_rkm_medis AND rp.tgl_registrasi = ar.tanggal_periksa
        LEFT JOIN dokter d ON d.kd_dokter = COALESCE(br.kd_dokter, rp.kd_dokter)
        LEFT JOIN poliklinik pl ON pl.kd_poli = COALESCE(br.kd_poli, rp.kd_poli)
        LEFT JOIN penjab pj ON pj.kd_pj = COALESCE(br.kd_pj, rp.kd_pj)
        LEFT JOIN (
            SELECT kodebooking, MAX(tanggal_batal) as tanggal_batal, MAX(keterangan) as keterangan 
            FROM mlite_antrian_referensi_batal 
            WHERE kodebooking = ?
            GROUP BY kodebooking
        ) batal ON batal.kodebooking = ar.kodebooking";

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute([$kodebooking, $kodebooking]);
        $detail = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$detail) {
            echo json_encode(['status' => 'error', 'message' => 'Data booking tidak ditemukan di database.']);
            exit();
        }

        $task_stmt = $this->db()->pdo()->prepare("SELECT taskid, waktu, status, keterangan FROM mlite_antrian_referensi_taskid WHERE nomor_referensi = ? OR nomor_referensi = ? ORDER BY taskid ASC");
        $task_stmt->execute([$kodebooking, $detail['nomor_referensi'] ?? '']);
        $task_list = $task_stmt->fetchAll(\PDO::FETCH_ASSOC);
        $tasks_bpjs = [];
        foreach ($task_list as $t) {
            $tasks_bpjs[(string)$t['taskid']] = $t;
        }

        $resolved = $this->_resolveTaskTimes(
            $detail['no_rawat'] ?? '',
            $detail['no_rkm_medis'] ?: $detail['ar_no_rkm_medis'],
            $detail['tanggal_periksa'],
            $kodebooking,
            $detail['nomor_referensi'] ?? '',
            [$kodebooking => $tasks_bpjs]
        );

        $task_definitions = [
            '1' => 'Mulai Tunggu Admisi / Check-in',
            '2' => 'Mulai Dilayani Admisi',
            '3' => 'Selesai Admisi / Tunggu Poli',
            '4' => 'Dipanggil Masuk Poli',
            '5' => 'Selesai Pelayanan Poli / Dokter',
            '6' => 'Tunggu Farmasi / Resep Diterima',
            '7' => 'Obat Diserahkan ke Pasien',
            '99' => 'Pembatalan Antrean'
        ];

        $formatted_tasks = [];
        foreach ($task_definitions as $id => $nama_task) {
            $t = $resolved[$id] ?? null;
            $waktu_raw = $t['waktu'] ?? '';
            $status_t = $t['status'] ?? 'Belum';
            $source_t = $t['source'] ?? '';
            $ket_t = $t['keterangan'] ?? '';

            $formatted_tasks[] = [
                'taskid' => $id,
                'nama_task' => $nama_task,
                'waktu' => !empty($waktu_raw) ? $waktu_raw : '-',
                'status' => $status_t,
                'keterangan' => !empty($source_t) ? $source_t . (!empty($ket_t) ? ' (' . $ket_t . ')' : '') : ($ket_t ?: '-')
            ];
        }

        $jk_map = [
            '1' => 'Rujukan FKTP (Faskes 1)',
            '2' => 'Rujukan Internal',
            '3' => 'Kontrol (SKDP)',
            '4' => 'Rujukan Antar RS (Faskes 2)'
        ];
        $detail['jenis_kunjungan_label'] = $jk_map[$detail['jenis_kunjungan']] ?? 'Rujukan';

        $is_batal = ($detail['status_kirim'] === 'Batal' || 
                     $detail['booking_status'] === 'Batal' || 
                     $detail['reg_status'] === 'Batal' || 
                     !empty($detail['tanggal_batal']));
        $is_terdaftar = (!$is_batal && (!empty($detail['no_rawat']) || $detail['booking_status'] === 'Terdaftar'));

        if ($is_batal) {
            $detail['status_final'] = 'Batal';
        } elseif ($is_terdaftar) {
            $detail['status_final'] = 'Terdaftar';
        } else {
            $detail['status_final'] = 'Belum';
        }

        echo json_encode([
            'status' => 'success',
            'data' => $detail,
            'tasks' => $formatted_tasks
        ]);
        exit();
    }

    public function syncSingleBooking($kodebooking, $versi = 'v3')
    {
        $kodebooking = trim($kodebooking);
        $versi = strtolower(trim($versi));
        if (empty($kodebooking)) {
            return ['status' => 'error', 'message' => 'Kode booking tidak boleh kosong.'];
        }

        if (empty($this->consid) || empty($this->secretkey) || empty($this->bpjsurl)) {
            return ['status' => 'error', 'message' => 'Kredensial BPJS Antrean (ConsID / SecretKey / URL) belum diatur di Pengaturan JKN Mobile.'];
        }

        $antrol = $this->db('mlite_antrian_referensi')->where('kodebooking', $kodebooking)->oneArray();
        if (!$antrol) {
            $antrol = $this->db('mlite_antrian_referensi')->where('nomor_referensi', $kodebooking)->oneArray();
        }

        $no_rawat = '';
        $no_rkm_medis = $antrol['no_rkm_medis'] ?? '';
        $tgl_periksa = $antrol['tanggal_periksa'] ?? date('Y-m-d');

        if (!empty($no_rkm_medis)) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rkm_medis)->where('tgl_registrasi', $tgl_periksa)->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        if (empty($no_rawat) && !empty($no_rkm_medis)) {
            $br = $this->db('booking_registrasi')->where('tanggal_periksa', $tgl_periksa)->where('no_rkm_medis', $no_rkm_medis)->oneArray();
            if ($br) {
                $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rkm_medis)->where('tgl_registrasi', $tgl_periksa)->oneArray();
                if ($reg) $no_rawat = $reg['no_rawat'];
            }
        }

        // Ambil task yang sudah tercatat di database lokal
        $task_stmt = $this->db()->pdo()->prepare("SELECT nomor_referensi, taskid, waktu, status, keterangan FROM mlite_antrian_referensi_taskid WHERE nomor_referensi = ?");
        $task_stmt->execute([$kodebooking]);
        $existing_tasks = [];
        while ($r = $task_stmt->fetch(\PDO::FETCH_ASSOC)) {
            $existing_tasks[(string)$r['taskid']] = $r;
        }

        // Resolve semua waktu dari operasional SIMRS (loket, berkas RM, periksa dokter, resep apotek)
        $resolved = $this->_resolveTaskTimes(
            $no_rawat,
            $no_rkm_medis,
            $tgl_periksa,
            $kodebooking,
            $antrol['nomor_referensi'] ?? '',
            [$kodebooking => $existing_tasks]
        );

        // Pastikan waktu yang ditemukan di SIMRS tersimpan di database lokal
        foreach ($resolved as $tid => $rData) {
            if (!empty($rData['waktu'])) {
                $waktu_millis = is_numeric($rData['waktu']) ? (int)$rData['waktu'] : strtotime($rData['waktu']) * 1000;
                if (!isset($existing_tasks[$tid])) {
                    $this->db('mlite_antrian_referensi_taskid')->save([
                        'tanggal_periksa' => $tgl_periksa,
                        'nomor_referensi' => $kodebooking,
                        'taskid' => (int)$tid,
                        'waktu' => $waktu_millis,
                        'status' => 'Belum',
                        'keterangan' => $rData['source'] ?? ''
                    ]);
                    $existing_tasks[$tid] = [
                        'nomor_referensi' => $kodebooking,
                        'taskid' => (int)$tid,
                        'waktu' => $waktu_millis,
                        'status' => 'Belum',
                        'keterangan' => $rData['source'] ?? ''
                    ];
                }
            }
        }

        // Tentukan jenis resep jika ada
        $jenisresep = 'Non racikan';
        if (!empty($no_rawat)) {
            $resep = $this->db('resep_obat')->where('no_rawat', $no_rawat)->oneArray();
            if ($resep) {
                $racikan = $this->db('resep_dokter_racikan')->where('no_resep', $resep['no_resep'])->oneArray();
                if ($racikan) $jenisresep = 'Racikan';
            }
        }

        // Task yang akan dikirim:
        // v2: 1, 2, 3, 4, 5
        // v3: 1, 2, 3, 4, 5, 6, 7
        $tasks_to_send = ($versi === 'v3') ? [1, 2, 3, 4, 5, 6, 7] : [1, 2, 3, 4, 5];

        // Pengaman Kronologis: Pastikan waktu Task N >= Task (N-1) + 60s
        $last_valid_millis = null;
        foreach ($tasks_to_send as $tid) {
            $stid = (string)$tid;
            if (isset($existing_tasks[$stid]) && !empty($existing_tasks[$stid]['waktu'])) {
                $cur_millis = is_numeric($existing_tasks[$stid]['waktu']) ? (int)$existing_tasks[$stid]['waktu'] : strtotime($existing_tasks[$stid]['waktu']) * 1000;
                if ($last_valid_millis !== null && $cur_millis <= $last_valid_millis) {
                    $cur_millis = $last_valid_millis + 60000; // geser 1 menit ke depan
                    $existing_tasks[$stid]['waktu'] = $cur_millis;
                    $this->db('mlite_antrian_referensi_taskid')
                        ->where('nomor_referensi', $kodebooking)
                        ->where('taskid', (int)$tid)
                        ->save(['waktu' => $cur_millis]);
                }
                $last_valid_millis = $cur_millis;
            }
        }

        $results = [];
        $success_count = 0;
        $fail_count = 0;
        $skip_count = 0;

        $url = $this->bpjsurl . 'antrean/updatewaktu';

        foreach ($tasks_to_send as $tid) {
            $stid = (string)$tid;
            $tData = $existing_tasks[$stid] ?? null;

            if (!$tData || empty($tData['waktu'])) {
                $results[] = [
                    'taskid' => $tid,
                    'status' => 'Dilewati',
                    'message' => 'Waktu tahapan belum tercatat di SIMRS'
                ];
                $skip_count++;
                // Berhenti mengirim task berikutnya jika tahapan saat ini belum ada
                break;
            }

            // Jika status sudah 'Sudah', tidak perlu dikirim ulang kecuali gagal sebelumnya
            if (($tData['status'] ?? '') === 'Sudah') {
                $results[] = [
                    'taskid' => $tid,
                    'status' => 'Sudah',
                    'message' => 'Sudah tersinkron ke BPJS (' . ($tData['keterangan'] ?? 'Sukses') . ')'
                ];
                $success_count++;
                continue;
            }

            $waktu_millis = is_numeric($tData['waktu']) ? (int)$tData['waktu'] : strtotime($tData['waktu']) * 1000;

            $payload = [
                'kodebooking' => $kodebooking,
                'taskid' => (int)$tid,
                'waktu' => (int)$waktu_millis
            ];

            if ($tid == 5 && $versi === 'v3') {
                $payload['jenisresep'] = $jenisresep;
            }

            $json_payload = json_encode($payload);
            $output = BpjsService::post($url, $json_payload, $this->consid, $this->secretkey, $this->user_key, NULL);
            $res = $this->_decodeBpjsResponse($output);

            $code = $res['metadata']['code'] ?? 500;
            $msg = $res['metadata']['message'] ?? 'Tidak ada balasan dari server BPJS';

            // BPJS code 200 = Sukses, 208 = Sudah pernah di-update / waktu sudah ada
            if ($code == 200 || $code == 208) {
                $this->db('mlite_antrian_referensi_taskid')
                    ->where('nomor_referensi', $kodebooking)
                    ->where('taskid', $tid)
                    ->save([
                        'status' => 'Sudah',
                        'keterangan' => $msg
                    ]);
                $results[] = [
                    'taskid' => $tid,
                    'status' => 'Sudah',
                    'code' => $code,
                    'message' => $msg
                ];
                $success_count++;
            } else {
                $this->db('mlite_antrian_referensi_taskid')
                    ->where('nomor_referensi', $kodebooking)
                    ->where('taskid', $tid)
                    ->save([
                        'status' => 'Gagal',
                        'keterangan' => $code . ' : ' . $msg
                    ]);
                $results[] = [
                    'taskid' => $tid,
                    'status' => 'Gagal',
                    'code' => $code,
                    'message' => $msg
                ];
                $fail_count++;
                // Berhenti mengirim task berikutnya jika task saat ini gagal
                break;
            }
        }

        return [
            'status' => 'success',
            'kodebooking' => $kodebooking,
            'message' => "Task ID: {$success_count} Berhasil, {$fail_count} Gagal, {$skip_count} Belum Selesai.",
            'summary' => [
                'success' => $success_count,
                'failed' => $fail_count,
                'skipped' => $skip_count
            ],
            'results' => $results
        ];
    }

    public function postKirimTaskIDAjax()
    {
        header('Content-Type: application/json');
        $kodebooking = trim($_POST['kodebooking'] ?? '');
        $versi = strtolower(trim($_POST['versi'] ?? 'v2')); // 'v2' (task 1-5) or 'v3' (task 1-7)
        $res = $this->syncSingleBooking($kodebooking, $versi);
        echo json_encode($res);
        exit();
    }

    public function getSyncTaskQueueAjax()
    {
        header('Content-Type: application/json');
        $tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
        if (empty($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $bookings = $this->db('mlite_antrian_referensi')
            ->where('tanggal_periksa', $tanggal)
            ->toArray();

        if (empty($bookings)) {
            echo json_encode([
                'status' => 'success',
                'total' => 0,
                'queue' => [],
                'message' => "Tidak ada data antrean pada tanggal {$tanggal}."
            ]);
            exit();
        }

        $task_stmt = $this->db()->pdo()->prepare("SELECT nomor_referensi, taskid, status FROM mlite_antrian_referensi_taskid WHERE tanggal_periksa = ?");
        $task_stmt->execute([$tanggal]);
        $task_summary = [];
        while ($row = $task_stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ref = $row['nomor_referensi'];
            if (!isset($task_summary[$ref])) {
                $task_summary[$ref] = ['total' => 0, 'sudah' => 0, 'belum' => 0];
            }
            $task_summary[$ref]['total']++;
            if ($row['status'] === 'Sudah') {
                $task_summary[$ref]['sudah']++;
            } else {
                $task_summary[$ref]['belum']++;
            }
        }

        // Cache nama pasien
        $rm_list = array_filter(array_unique(array_column($bookings, 'no_rkm_medis')));
        $pasien_map = [];
        if (!empty($rm_list)) {
            $in_clause = implode(',', array_fill(0, count($rm_list), '?'));
            $p_stmt = $this->db()->pdo()->prepare("SELECT no_rkm_medis, nm_pasien FROM pasien WHERE no_rkm_medis IN ($in_clause)");
            $p_stmt->execute(array_values($rm_list));
            while ($p = $p_stmt->fetch(\PDO::FETCH_ASSOC)) {
                $pasien_map[$p['no_rkm_medis']] = $p['nm_pasien'];
            }
        }

        $queue = [];
        foreach ($bookings as $b) {
            $kb = $b['kodebooking'] ?: $b['nomor_referensi'];
            if (empty($kb)) continue;

            $status_b = $b['status'] ?? '';
            if (stripos($status_b, 'Batal') !== false) {
                continue;
            }

            $ts = $task_summary[$kb] ?? ($task_summary[$b['nomor_referensi']] ?? ['total' => 0, 'sudah' => 0, 'belum' => 0]);

            // Jika sudah ada minimal 5 task dan tidak ada yang berstatus belum/gagal, lewati
            if ($ts['total'] >= 5 && $ts['belum'] == 0 && $ts['sudah'] >= 5) {
                continue;
            }

            $nm_pasien = $pasien_map[$b['no_rkm_medis']] ?? ($b['nomor_kartu'] ?: '-');

            $queue[] = [
                'kodebooking' => $kb,
                'no_rkm_medis' => $b['no_rkm_medis'],
                'nm_pasien' => $nm_pasien,
                'tanggal_periksa' => $b['tanggal_periksa'],
                'task_sudah' => $ts['sudah'],
                'task_belum' => $ts['belum']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'total' => count($queue),
            'queue' => $queue,
            'message' => "Ditemukan " . count($queue) . " antrean yang perlu disinkronkan."
        ]);
        exit();
    }

    public function anyAutoSyncTaskID()
    {
        header('Content-Type: application/json');
        $tanggal = trim($_REQUEST['tanggal'] ?? ($_REQUEST['tgl'] ?? date('Y-m-d')));
        $versi = trim($_REQUEST['versi'] ?? 'v3');
        $limit = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 50;

        $bookings = $this->db('mlite_antrian_referensi')
            ->where('tanggal_periksa', $tanggal)
            ->toArray();

        $processed = 0;
        $total_success_tasks = 0;
        $total_failed_tasks = 0;
        $results = [];

        foreach ($bookings as $b) {
            $kb = $b['kodebooking'] ?: $b['nomor_referensi'];
            if (empty($kb)) continue;
            if (stripos($b['status'] ?? '', 'Batal') !== false) continue;

            $pending = $this->db('mlite_antrian_referensi_taskid')
                ->where('nomor_referensi', $kb)
                ->where('status', 'Belum')
                ->count();
            $count_total = $this->db('mlite_antrian_referensi_taskid')
                ->where('nomor_referensi', $kb)
                ->count();

            if ($count_total >= 5 && $pending == 0) {
                continue;
            }

            $res = $this->syncSingleBooking($kb, $versi);
            $processed++;
            $total_success_tasks += $res['summary']['success'] ?? 0;
            $total_failed_tasks += $res['summary']['failed'] ?? 0;
            $results[] = [
                'kodebooking' => $kb,
                'no_rkm_medis' => $b['no_rkm_medis'],
                'summary' => $res['summary'] ?? []
            ];

            if ($processed >= $limit) {
                break;
            }
        }

        echo json_encode([
            'status' => 'success',
            'tanggal' => $tanggal,
            'processed_patients' => $processed,
            'total_success_tasks' => $total_success_tasks,
            'total_failed_tasks' => $total_failed_tasks,
            'details' => $results
        ]);
        exit();
    }

    private function _exportBookingAntrolCsv($rows, $tgl_awal, $tgl_akhir)
    {
        $filename = "booking_antrol_" . ($tgl_awal ?: 'all') . "_sd_" . ($tgl_akhir ?: 'all') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'No',
            'Tanggal Periksa',
            'Kode Booking',
            'No. RM',
            'Nama Pasien',
            'No. Kartu BPJS',
            'NIK / KTP',
            'No. Telepon',
            'No. Rujukan',
            'Jenis Kunjungan',
            'Poliklinik',
            'Dokter DPJP',
            'No. Antrean',
            'Jam Kunjungan',
            'No. Rawat',
            'Status Booking'
        ]);

        $no = 1;
        foreach ($rows as $r) {
            fputcsv($output, [
                $no++,
                $r['tanggal_periksa'],
                $r['kodebooking'],
                $r['no_rkm_medis'] ?: $r['ar_no_rkm_medis'],
                $r['nm_pasien'] ?: '-',
                $r['nomor_kartu'] ?: '-',
                $r['no_ktp'] ?: '-',
                $r['no_tlp'] ?: '-',
                $r['nomor_referensi'] ?: '-',
                $r['jenis_kunjungan_label'],
                $r['nm_poli'] ?: '-',
                $r['nm_dokter'] ?: '-',
                $r['no_reg'] ?: '-',
                $r['waktu_kunjungan'] ?: $r['jam_booking'] ?: '-',
                $r['no_rawat'] ?: '-',
                $r['status_label']
            ]);
        }
        fclose($output);
        exit();
    }

    /**
     * Hitung dan selaraskan waktu Task ID 1-7 serta 99
     * Menggabungkan data dari BPJS (mlite_antrian_referensi_taskid) dan rekaman operasional SIMRS
     * (mlite_antrian_loket, mutasi_berkas, pemeriksaan_ralan, resep_obat, dll.)
     */
    private function _resolveTaskTimes($no_rawat, $no_rkm_medis, $tanggal_periksa, $kodebooking, $nomor_referensi, $task_map, $op_cache = null)
    {
        $tasks = [
            '1' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '2' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '3' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '4' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '5' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '6' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '7' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => ''],
            '99' => ['waktu' => '', 'status' => 'Belum', 'source' => '', 'keterangan' => '']
        ];

        // 1. Ambil dari database task BPJS jika sudah tercatat
        $bpjs_records = $task_map[$kodebooking] ?? ($task_map[$nomor_referensi] ?? []);
        foreach ($bpjs_records as $tid => $data) {
            $tid_str = (string)$tid;
            if (isset($tasks[$tid_str])) {
                $waktu_str = '';
                if (!empty($data['waktu'])) {
                    if (is_numeric($data['waktu'])) {
                        $waktu_str = date('Y-m-d H:i:s', (int)($data['waktu'] / 1000));
                    } else {
                        $waktu_str = $data['waktu'];
                    }
                }
                $tasks[$tid_str]['waktu'] = $waktu_str;
                $tasks[$tid_str]['status'] = $data['status'] ?? 'Belum';
                $tasks[$tid_str]['source'] = 'Tersimpan / BPJS';
                $tasks[$tid_str]['keterangan'] = $data['keterangan'] ?? '';
            }
        }

        // 2. Ambil dari rekaman operasional SIMRS (seperti di /admin/jkn_mobile/taskid dan getTaskIDInput)
        if (!empty($no_rawat)) {
            // Berkas RM (mutasi_berkas) -> Task 3 & 4
            $berkas = null;
            if ($op_cache !== null && isset($op_cache['mutasi_berkas'][$no_rawat])) {
                $berkas = $op_cache['mutasi_berkas'][$no_rawat];
            } else {
                $berkas = $this->db('mutasi_berkas')->select(['dikirim', 'diterima'])->where('no_rawat', $no_rawat)->oneArray();
            }
            $berkas_dikirim = (!empty($berkas['dikirim']) && $berkas['dikirim'] > '1970-01-01 00:00:00') ? $berkas['dikirim'] : '';
            $berkas_diterima = (!empty($berkas['diterima']) && $berkas['diterima'] > '1970-01-01 00:00:00') ? $berkas['diterima'] : '';

            // Pemeriksaan Ralan (pemeriksaan_ralan) -> Task 5
            $pemeriksaan = null;
            if ($op_cache !== null && isset($op_cache['pemeriksaan_ralan'][$no_rawat])) {
                $pemeriksaan = $op_cache['pemeriksaan_ralan'][$no_rawat];
            } else {
                $pemeriksaan = $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $no_rawat)->oneArray();
            }
            $jam_periksa = !empty($pemeriksaan['datajam']) ? $pemeriksaan['datajam'] : '';

            // Resep Obat (resep_obat) -> Task 6 & 7
            $resep = null;
            if ($op_cache !== null && isset($op_cache['resep_obat'][$no_rawat])) {
                $resep = $op_cache['resep_obat'][$no_rawat];
            } else {
                $resep = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)', 'datajam2' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $no_rawat)->oneArray();
            }
            $jam_resep = !empty($resep['datajam']) ? $resep['datajam'] : '';
            $jam_obat_selesai = (!empty($resep['datajam2']) && $resep['datajam2'] > '1970-01-01 00:00:00') ? $resep['datajam2'] : '';

            // Antrean Loket -> Task 1 & 2
            $loket = null;
            if (!empty($no_rkm_medis) && !empty($tanggal_periksa)) {
                $loket_key = $no_rkm_medis . '_' . $tanggal_periksa;
                if ($op_cache !== null && isset($op_cache['loket'][$loket_key])) {
                    $loket = $op_cache['loket'][$loket_key];
                } else {
                    $loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $no_rkm_medis)->where('postdate', $tanggal_periksa)->oneArray();
                }
            }

            // Task 5: Selesai Pelayanan Poli
            if (empty($tasks['5']['waktu']) && !empty($jam_periksa)) {
                $tasks['5']['waktu'] = $jam_periksa;
                $tasks['5']['source'] = 'Pemeriksaan Poli';
            }

            // Task 4: Dipanggil Masuk Poli
            if (empty($tasks['4']['waktu'])) {
                if (!empty($berkas_diterima)) {
                    $tasks['4']['waktu'] = $berkas_diterima;
                    $tasks['4']['source'] = 'Berkas Diterima Poli';
                } elseif (!empty($tasks['5']['waktu'])) {
                    $tasks['4']['waktu'] = date('Y-m-d H:i:s', strtotime($tasks['5']['waktu']) - 300);
                    $tasks['4']['source'] = 'Estimasi Masuk Poli';
                }
            }

            // Task 3: Selesai Admisi / Tunggu Poli
            if (empty($tasks['3']['waktu'])) {
                if (!empty($berkas_dikirim)) {
                    $tasks['3']['waktu'] = $berkas_dikirim;
                    $tasks['3']['source'] = 'Berkas Dikirim RM';
                } elseif (!empty($tasks['4']['waktu'])) {
                    $tasks['3']['waktu'] = date('Y-m-d H:i:s', strtotime($tasks['4']['waktu']) - 300);
                    $tasks['3']['source'] = 'Estimasi Tunggu Poli';
                } elseif (!empty($tasks['5']['waktu'])) {
                    $tasks['3']['waktu'] = date('Y-m-d H:i:s', strtotime($tasks['5']['waktu']) - 600);
                    $tasks['3']['source'] = 'Estimasi Tunggu Poli';
                }
            }

            // Koreksi jika Task 3 >= Task 4
            if (!empty($tasks['3']['waktu']) && !empty($tasks['4']['waktu']) && strtotime($tasks['3']['waktu']) >= strtotime($tasks['4']['waktu'])) {
                $tasks['3']['waktu'] = date('Y-m-d H:i:s', strtotime($tasks['4']['waktu']) - 300);
            }

            // Task 1 & 2: Mulai & Selesai Admisi Loket
            if (empty($tasks['1']['waktu']) || empty($tasks['2']['waktu'])) {
                if (!empty($loket['start_time']) && !empty($loket['end_time'])) {
                    if (empty($tasks['1']['waktu'])) {
                        $tasks['1']['waktu'] = $loket['postdate'] . ' ' . $loket['start_time'];
                        $tasks['1']['source'] = 'Loket Mulai';
                    }
                    if (empty($tasks['2']['waktu'])) {
                        $tasks['2']['waktu'] = $loket['postdate'] . ' ' . $loket['end_time'];
                        $tasks['2']['source'] = 'Loket Selesai';
                    }
                } elseif (!empty($tasks['3']['waktu'])) {
                    $t3_time = strtotime($tasks['3']['waktu']);
                    if (empty($tasks['2']['waktu'])) {
                        $tasks['2']['waktu'] = date('Y-m-d H:i:s', $t3_time - 180);
                        $tasks['2']['source'] = 'Estimasi Dilayani Admisi';
                    }
                    if (empty($tasks['1']['waktu'])) {
                        $tasks['1']['waktu'] = date('Y-m-d H:i:s', $t3_time - 600);
                        $tasks['1']['source'] = 'Estimasi Tunggu Admisi';
                    }
                }
            }

            // Task 6 & 7: Farmasi
            if (empty($tasks['6']['waktu']) && !empty($jam_resep)) {
                $tasks['6']['waktu'] = $jam_resep;
                $tasks['6']['source'] = 'Resep Diterima Farmasi';
            }
            if (empty($tasks['7']['waktu']) && !empty($jam_obat_selesai)) {
                $tasks['7']['waktu'] = $jam_obat_selesai;
                $tasks['7']['source'] = 'Obat Diserahkan Farmasi';
            }
        }

        return $tasks;
    }

    public function getModalAntrol($noref = '')
    {
        $this->tpl->set('noref',$noref);
        echo $this->tpl->draw(MODULES . '/jkn_mobile/view/admin/batalantrol.html', true);
        exit();
    }

    public function postHapusAntrol()
    {
        $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $_POST['kodebooking'])->oneArray();
        $booking_registrasi = [];
        $pasien = [];
        if($referensi) {
            $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
            $booking_registrasi = $this->db('booking_registrasi')
            ->where('no_rkm_medis', $pasien['no_rkm_medis'])
            ->where('tanggal_periksa', $referensi['tanggal_periksa'])
            ->oneArray();
        }
        if(!$booking_registrasi) {
          date_default_timezone_set('UTC');
          $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
          $key = $this->consid.$this->secretkey.$tStamp;

          $data = [
              'kodebooking' => $_POST['kodebooking'],
              'keterangan' => $_POST['keterangan']
          ];

          $data = json_encode($data);
          $url = $this->bpjsurl.'antrean/batal';
          $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
          $json = json_decode($output, true);
          $notif = json_encode($json);
          if ($json == NULL) {
            $notif = 'Data Booking di JKN Mobile Tidak Ada!';
          } else if ($json['metadata']['code'] == 200) {
            $this->db('mlite_antrian_referensi_batal')->save([
              'tanggal_batal' => date('Y-m-d'),
              'nomor_referensi' => $referensi['nomor_referensi'],
              'kodebooking' => $_POST['kodebooking'],
              'keterangan' => $_POST['keterangan']
            ]);
            $this->db('mlite_antrian_referensi')->where('kodebooking', $_POST['kodebooking'])->update([
              'status_kirim' => 'Batal',
              'keterangan' => $_POST['keterangan']
            ]);
            $notif = 'Berhasil Dibatalkan di JKN Mobile';
          }
          // $notif = 'Data Booking tidak ditemukan';
        }else{
            if(date("Y-m-d")>$booking_registrasi['tanggal_periksa']){
                $notif = 'Pembatalan Antrean tidak berlaku mundur';
            }else if($booking_registrasi['status']=='Terdaftar'){
                $notif = 'Pasien Sudah Checkin, Pendaftaran Tidak Bisa Dibatalkan';
            }else if($booking_registrasi['status']=='Belum'){
                $batal = $this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->delete();
                if(!$this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->oneArray()){
                    $this->db('mlite_antrian_referensi_batal')->save([
                        'tanggal_batal' => date('Y-m-d'),
                        'nomor_referensi' => $referensi['nomor_referensi'],
                        'kodebooking' => $_POST['kodebooking'],
                        'keterangan' => $_POST['keterangan']
                    ]);
                    $this->db('mlite_antrian_referensi')->where('kodebooking', $_POST['kodebooking'])->delete();
                    if (!$this->db('mlite_antrian_referensi')->where('kodebooking', $_POST['kodebooking'])->oneArray()) {
                      date_default_timezone_set('UTC');
                      $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
                      $key = $this->consid.$this->secretkey.$tStamp;

                      $data = [
                          'kodebooking' => $_POST['kodebooking'],
                          'keterangan' => $_POST['keterangan']
                      ];

                      $data = json_encode($data);
                      $url = $this->bpjsurl.'antrean/batal';
                      $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                      $json = json_decode($output, true);
                      if ($json == NULL) {
                          $notif = 'Data Booking di JKN Mobile Tidak Ada!<br>Berhasil Dibatalkan di SIMRS';
                      } else if ($json['metadata']['code'] == 200) {
                          $notif = 'Berhasil Dibatalkan di JKN Mobile';
                      }
                    }
                }else{
                    $notif = 'Maaf Terjadi Kesalahan, Hubungi Admnistrator..';
                }
            }
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            $is_success = (strpos($notif, 'Berhasil') !== false);
            echo json_encode([
                'status' => $is_success ? 'success' : 'error',
                'message' => strip_tags($notif)
            ]);
            exit();
        }

        return $this->draw('hapusantrol.html', ['row' => $this->db('mlite_antrian_referensi')->toArray(), 'notif' => $notif]);
    }

    public function postSaveTaskID1()
    {

    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/jkn_mobile/js/admin/jkn_mobile.js');
        exit();
    }

    public function getCssCard()
    {
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addJS(url([ADMIN, 'jkn_mobile', 'javascript']), 'footer');
    }

}

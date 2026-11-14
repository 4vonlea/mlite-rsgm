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
        $this->getCssCard();
        $poli = $this->db('maping_poli_bpjs')->toArray();
        return $this->draw('jadwaldokter.html',['poli'=>$poli]);
    }

    public function postHfis() {
        $kodepoli = $_POST['poli'];
        // $_POST['tanggal'] = date('Y-m-d');
        $tanggal = $_POST['tgl'];
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid . $this->secretkey . $tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone'));

        $url = $this->bpjsurl . 'jadwaldokter/kodepoli/'.$kodepoli.'/tanggal/'.$tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        
        if (isset($json['metadata']['code'])) {
             $code = $json['metadata']['code'];
             $message = $json['metadata']['message'];
             
             if ($code == '200' && isset($json['response'])) {
                 $stringDecrypt = stringDecrypt($key, $json['response']);
                 $decompress = '';
                 if (!empty($stringDecrypt)) {
                     $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
                 }
                 $response = json_decode($decompress, true);
             } else {
                 $response = []; // Empty array if not found or error
             }
        } else {
             $response = []; // Empty array if invalid response
        }
        
        echo json_encode(htmlspecialchars_array($response));
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

    public function getTaskIDInput($no_rawat)
    {
      try {
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
          echo '<div class="alert alert-danger">Data pasien tidak ditemukan.</div>';
          exit();
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
      $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $_POST['nomor_referensi'])->where('status','!=','Sudah')->delete();

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


      redirect(url([ADMIN, 'jkn_mobile', 'taskid']));
    }

    public function getKirimAntrian($no_rawat)
    {

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
        $this->_addHeaderFiles();
        return $this->draw('bookingantrol.html', ['row' => $this->db('mlite_antrian_referensi')->toArray()]);
    }

    public function getModalAntrol($noref)
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
        //exit();
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

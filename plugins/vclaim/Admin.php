<?php

namespace Plugins\Vclaim;

use Systems\AdminModule;
use Systems\Lib\BpjsService;
use Systems\Lib\QRCode;
use LZCompressor\LZString;

class Admin extends AdminModule
{

  protected $consid;
  protected $secretkey;
  protected $user_key;
  protected $api_url;

  public function init()
  {
    $this->consid = $this->settings->get('settings.BpjsConsID');
    $this->secretkey = $this->settings->get('settings.BpjsSecretKey');
    $this->user_key = $this->settings->get('settings.BpjsUserKey');
    $this->api_url = $this->settings->get('settings.BpjsApiUrl');
  }

  public function navigation()
  {
    return [
      'Kelola'   => 'manage',
      'Referensi' => 'referensi',
      'Peserta' => 'peserta',
      'Rencana Kontrol' => 'rencanakontrol',
      'SEP' => 'sep',
      'Rujukan' => 'rujukan',
      'PRB' => 'prb',
      'Lembar Pengajuan Klaim' => 'lpk',
      'Monitoring' => 'monitoring',
      'Mapping Poli' => 'mappingpoli',
      'Mapping Dokter' => 'mappingdokter'
    ];
  }

  public function getManage()
  {
    $parsedown = new \Systems\Lib\Parsedown();
    $readme_file = MODULES . '/vclaim/README.md';
    $readme =  $parsedown->text($this->tpl->noParse(file_get_contents($readme_file)));
    return $this->draw('manage.html', ['readme' => $readme]);
  }

  public function getReferensi()
  {
    return $this->draw('referensi.html');
  }

  public function getPeserta()
  {
    $this->_addHeaderFiles();
    return $this->draw('peserta.html');
  }

  public function getSEP()
  {
    $this->_addHeaderFiles();
    return $this->draw('sep.html');
  }

  public function getPRB()
  {
    $this->_addHeaderFiles();
    return $this->draw('prb.html');
  }

  public function getRujukan()
  {
    $this->_addHeaderFiles();
    return $this->draw('rujukan.html');
  }

  public function getRencanaKontrol()
  {
    $this->_addHeaderFiles();
    return $this->draw('rencana.kontrol.html');
  }

  public function getLPK()
  {
    return $this->draw('lpk.html');
  }

  public function getMonitoring()
  {
    return $this->draw('monitoring.html');
  }

  public function postTarikSep()
  {
    $no_sep = $_POST['no_sep'] ?? '';
    $no_rawat = $_POST['no_rawat'] ?? '';
    if (empty($no_sep)) {
      echo json_encode(['status' => 'error', 'message' => 'Nomor SEP tidak valid']);
      exit();
    }
    $saved = $this->_pullAndSaveSep($no_sep, $no_rawat);
    if ($saved) {
      echo json_encode(['status' => 'success', 'message' => 'SEP ' . $no_sep . ' berhasil disimpan ke database SIMRS!', 'no_sep' => $no_sep]);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menarik data SEP dari server BPJS']);
    }
    exit();
  }

  protected function _getSafeUserDb($table = 'bridging_sep')
  {
    $username = $this->core->getUserInfo('username', null, true) ?: '';
    $fullname = $this->core->getUserInfo('fullname', null, true) ?: '';
    $userVal = !empty($username) ? $username : $fullname;
    if (empty($userVal)) {
      $userVal = 'admin';
    }

    // Auto widen column once if needed
    static $widened = false;
    if (!$widened) {
      $widened = true;
      try {
        $pdo = \Systems\Lib\QueryWrapper::pdo();
        if ($pdo) {
          $pdo->exec("ALTER TABLE `bridging_sep` MODIFY `user` VARCHAR(100) NULL");
          $pdo->exec("ALTER TABLE `bridging_sep_internal` MODIFY `user` VARCHAR(100) NULL");
        }
      } catch (\Throwable $e) {}
    }

    // Check column length dynamically
    static $colLimits = [];
    if (!isset($colLimits[$table])) {
      $colLimits[$table] = 20; // safe default
      try {
        $pdo = \Systems\Lib\QueryWrapper::pdo();
        if ($pdo) {
          $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'user'");
          if ($stmt && $row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (preg_match('/varchar\((\d+)\)/i', $row['Type'], $m)) {
              $colLimits[$table] = (int)$m[1];
            }
          }
        }
      } catch (\Throwable $e) {}
    }

    return mb_substr($userVal, 0, $colLimits[$table], 'UTF-8');
  }

  protected function _cleanSepRecord(array $input, $table = 'bridging_sep')
  {
    $user_db = $this->_getSafeUserDb($table);

    // Normalise tglpulang
    $tglpulang = isset_or($input['tglpulang'], '');
    if (empty($tglpulang) || $tglpulang == '0000-00-00' || $tglpulang == '0000-00-00 00:00:00') {
      $tglpulang = '1900-01-01 00:00:00';
    }

    // Normalise tglkkl
    $tglkkl = isset_or($input['tglkkl'], '');
    if (empty($tglkkl) || $tglkkl == '0000-00-00') {
      $tglkkl = '1900-01-01';
    }

    // Normalise tglsep & tglrujukan
    $tglsep = !empty($input['tglsep']) ? $input['tglsep'] : date('Y-m-d');
    $tglrujukan = !empty($input['tglrujukan']) ? $input['tglrujukan'] : $tglsep;

    return [
      'no_sep' => mb_substr(isset_or($input['no_sep'], ''), 0, 40, 'UTF-8'),
      'no_rawat' => mb_substr(isset_or($input['no_rawat'], ''), 0, 17, 'UTF-8'),
      'tglsep' => $tglsep,
      'tglrujukan' => $tglrujukan,
      'no_rujukan' => mb_substr(isset_or($input['no_rujukan'], isset_or($input['norujukan'], '')), 0, 40, 'UTF-8'),
      'kdppkrujukan' => mb_substr(isset_or($input['kdppkrujukan'], ''), 0, 12, 'UTF-8'),
      'nmppkrujukan' => mb_substr(isset_or($input['nmppkrujukan'], ''), 0, 200, 'UTF-8'),
      'kdppkpelayanan' => mb_substr(isset_or($input['kdppkpelayanan'], ''), 0, 12, 'UTF-8'),
      'nmppkpelayanan' => mb_substr(isset_or($input['nmppkpelayanan'], ''), 0, 200, 'UTF-8'),
      'jnspelayanan' => isset_or($input['jnspelayanan'], '2') == '1' ? '1' : '2',
      'catatan' => mb_substr(isset_or($input['catatan'], ''), 0, 100, 'UTF-8'),
      'diagawal' => mb_substr(isset_or($input['diagawal'], ''), 0, 10, 'UTF-8'),
      'nmdiagnosaawal' => mb_substr(isset_or($input['nmdiagnosaawal'], ''), 0, 400, 'UTF-8'),
      'kdpolitujuan' => mb_substr(isset_or($input['kdpolitujuan'], ''), 0, 15, 'UTF-8'),
      'nmpolitujuan' => mb_substr(isset_or($input['nmpolitujuan'], ''), 0, 50, 'UTF-8'),
      'klsrawat' => in_array(isset_or($input['klsrawat'], ''), ['1', '2', '3']) ? $input['klsrawat'] : '3',
      'klsnaik' => isset_or($input['klsnaik'], ''),
      'pembiayaan' => isset_or($input['pembiayaan'], ''),
      'pjnaikkelas' => mb_substr(isset_or($input['pjnaikkelas'], ''), 0, 100, 'UTF-8'),
      'lakalantas' => in_array(isset_or($input['lakalantas'], ''), ['0', '1', '2', '3']) ? $input['lakalantas'] : '0',
      'user' => $user_db,
      'nomr' => mb_substr(isset_or($input['nomr'], ''), 0, 15, 'UTF-8'),
      'nama_pasien' => mb_substr(isset_or($input['nama_pasien'], ''), 0, 100, 'UTF-8'),
      'tanggal_lahir' => !empty($input['tanggal_lahir']) ? $input['tanggal_lahir'] : '1900-01-01',
      'peserta' => mb_substr(isset_or($input['peserta'], ''), 0, 100, 'UTF-8'),
      'jkel' => in_array(isset_or($input['jkel'], isset_or($input['jenis_kelamin'], '')), ['L', 'P']) ? isset_or($input['jkel'], isset_or($input['jenis_kelamin'], '')) : 'L',
      'no_kartu' => mb_substr(isset_or($input['no_kartu'], ''), 0, 25, 'UTF-8'),
      'tglpulang' => $tglpulang,
      'asal_rujukan' => isset_or($input['asal_rujukan'], '1. Faskes 1') == '2. Faskes 2(RS)' ? '2. Faskes 2(RS)' : '1. Faskes 1',
      'eksekutif' => isset_or($input['eksekutif'], '0. Tidak') == '1.Ya' ? '1.Ya' : '0. Tidak',
      'cob' => isset_or($input['cob'], '0. Tidak') == '1.Ya' ? '1.Ya' : '0. Tidak',
      'notelep' => mb_substr(isset_or($input['notelep'], ''), 0, 40, 'UTF-8'),
      'katarak' => isset_or($input['katarak'], '0. Tidak') == '1.Ya' ? '1.Ya' : '0. Tidak',
      'tglkkl' => $tglkkl,
      'keterangankkl' => mb_substr(isset_or($input['keterangankkl'], '-'), 0, 100, 'UTF-8'),
      'suplesi' => isset_or($input['suplesi'], '0. Tidak') == '1.Ya' ? '1.Ya' : '0. Tidak',
      'no_sep_suplesi' => mb_substr(isset_or($input['no_sep_suplesi'], '-'), 0, 40, 'UTF-8'),
      'kdprop' => mb_substr(isset_or($input['kdprop'], '-'), 0, 10, 'UTF-8'),
      'nmprop' => mb_substr(isset_or($input['nmprop'], '-'), 0, 50, 'UTF-8'),
      'kdkab' => mb_substr(isset_or($input['kdkab'], '-'), 0, 10, 'UTF-8'),
      'nmkab' => mb_substr(isset_or($input['nmkab'], '-'), 0, 50, 'UTF-8'),
      'kdkec' => mb_substr(isset_or($input['kdkec'], '-'), 0, 10, 'UTF-8'),
      'nmkec' => mb_substr(isset_or($input['nmkec'], '-'), 0, 50, 'UTF-8'),
      'noskdp' => mb_substr(isset_or($input['noskdp'], '0'), 0, 40, 'UTF-8'),
      'kddpjp' => mb_substr(isset_or($input['kddpjp'], '-'), 0, 10, 'UTF-8'),
      'nmdpdjp' => mb_substr(isset_or($input['nmdpdjp'], '-'), 0, 100, 'UTF-8'),
      'tujuankunjungan' => in_array(isset_or($input['tujuankunjungan'], isset_or($input['tujuanKunj'], '0')), ['0', '1', '2']) ? isset_or($input['tujuankunjungan'], isset_or($input['tujuanKunj'], '0')) : '0',
      'flagprosedur' => isset_or($input['flagprosedur'], isset_or($input['flagProcedure'], '')),
      'penunjang' => isset_or($input['penunjang'], isset_or($input['kdPenunjang'], '')),
      'asesmenpelayanan' => isset_or($input['asesmenpelayanan'], isset_or($input['assesmentPel'], '')),
      'kddpjplayanan' => mb_substr(isset_or($input['kddpjplayanan'], isset_or($input['kddpjppelayanan'], '-')), 0, 10, 'UTF-8'),
      'nmdpjplayanan' => mb_substr(isset_or($input['nmdpjplayanan'], isset_or($input['nmdpjppelayanan'], '-')), 0, 100, 'UTF-8')
    ];
  }

  protected function _saveSepLocal(array $dataSep, $table = 'bridging_sep')
  {
    $record = $this->_cleanSepRecord($dataSep, $table);
    if (empty($record['no_sep'])) {
      return false;
    }
    $exist = $this->db($table)->where('no_sep', $record['no_sep'])->oneArray();
    if ($exist) {
      return $this->db($table)->where('no_sep', $record['no_sep'])->update($record);
    }
    return $this->db($table)->save($record);
  }

  protected function _pullAndSaveSep($no_sep, $no_rawat = '')
  {
    if (empty($no_sep)) {
      return false;
    }
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $url = $this->api_url . 'SEP/' . $no_sep;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    if (!$data || !isset($data['metaData']['code']) || $data['metaData']['code'] != 200) {
      return false;
    }
    $stringDecrypt = stringDecrypt($key, $data['response']);
    if (empty($stringDecrypt)) {
      return false;
    }
    $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
    $sepObj = json_decode($decompress, true);
    if (!is_array($sepObj)) {
      return false;
    }
    $sep = $sepObj['sep'] ?? ($sepObj['response'] ?? $sepObj);
    if (empty($sep['noSep'])) {
      return false;
    }

    $no_kartu = $sep['peserta']['noKartu'] ?? ($sep['noKartu'] ?? '');
    $tglsep = $sep['tglSep'] ?? date('Y-m-d');

    // Look up no_rawat if not provided
    if (empty($no_rawat) && !empty($no_kartu)) {
      $reg = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->where('pasien.no_peserta', $no_kartu)
        ->where('reg_periksa.tgl_registrasi', $tglsep)
        ->desc('reg_periksa.jam_reg')
        ->oneArray();
      if ($reg) {
        $no_rawat = $reg['no_rawat'];
      }
    }

    $jenis_pelayanan = '2';
    if (($sep['jnsPelayanan'] ?? '') == 'Rawat Inap' || ($sep['jnsPelayanan'] ?? '') == '1') {
      $jenis_pelayanan = '1';
    }

    $diag_raw = $sep['diagnosa'] ?? '';
    $diag_parts = explode(' - ', $diag_raw, 2);
    $diag_code = trim($diag_parts[0] ?? '');
    $diag_name = trim($diag_parts[1] ?? $diag_raw);

    $nm_poli = $sep['poli'] ?? '';
    $map_poli = $this->db('maping_poli_bpjs')->where('nm_poli_bpjs', $nm_poli)->oneArray();
    $kd_poli = $map_poli['kd_poli_bpjs'] ?? '';

    $dpjp = $sep['dpjp'] ?? [];
    $kd_dpjp = is_array($dpjp) ? ($dpjp['kdDPJP'] ?? '') : '';
    $nm_dpjp = is_array($dpjp) ? ($dpjp['nmDPJP'] ?? '') : '';

    $kls_rawat = '3';
    $kls_rawat_raw = $sep['kelasRawat'] ?? ($sep['klsRawat']['klsRawatHak'] ?? '3');
    if (stripos($kls_rawat_raw, '1') !== false) $kls_rawat = '1';
    elseif (stripos($kls_rawat_raw, '2') !== false) $kls_rawat = '2';

    $dataInsert = [
      'no_sep' => $sep['noSep'],
      'no_rawat' => $no_rawat,
      'tglsep' => $tglsep,
      'tglrujukan' => $sep['tglRujukan'] ?? $tglsep,
      'no_rujukan' => $sep['noRujukan'] ?? '',
      'kdppkrujukan' => $sep['provPerujuk']['kdProvider'] ?? ($this->settings->get('settings.ppk_bpjs')),
      'nmppkrujukan' => $sep['provPerujuk']['nmProvider'] ?? ($this->settings->get('settings.nama_instansi')),
      'kdppkpelayanan' => $this->settings->get('settings.ppk_bpjs'),
      'nmppkpelayanan' => $this->settings->get('settings.nama_instansi'),
      'jnspelayanan' => $jenis_pelayanan,
      'catatan' => $sep['catatan'] ?? '',
      'diagawal' => $diag_code,
      'nmdiagnosaawal' => $diag_name,
      'kdpolitujuan' => $kd_poli,
      'nmpolitujuan' => $nm_poli,
      'klsrawat' => $kls_rawat,
      'klsnaik' => '',
      'pembiayaan' => '',
      'pjnaikkelas' => '',
      'lakalantas' => '0',
      'nomr' => $sep['peserta']['noMr'] ?? ($sep['noMr'] ?? ''),
      'nama_pasien' => $sep['peserta']['nama'] ?? '',
      'tanggal_lahir' => $sep['peserta']['tglLahir'] ?? '',
      'peserta' => $sep['peserta']['jnsPeserta'] ?? '',
      'jkel' => ($sep['peserta']['kelamin'] ?? 'L') == 'P' ? 'P' : 'L',
      'no_kartu' => $no_kartu,
      'tglpulang' => '1900-01-01 00:00:00',
      'asal_rujukan' => '1. Faskes 1',
      'eksekutif' => ($sep['poliEksekutif'] ?? '0') == '1' ? '1.Ya' : '0. Tidak',
      'cob' => '0. Tidak',
      'notelep' => $sep['peserta']['noTelepon'] ?? ($sep['noTelp'] ?? '-'),
      'katarak' => '0. Tidak',
      'tglkkl' => '1900-01-01',
      'keterangankkl' => '-',
      'suplesi' => '0. Tidak',
      'no_sep_suplesi' => '-',
      'kdprop' => '-',
      'nmprop' => '-',
      'kdkab' => '-',
      'nmkab' => '-',
      'kdkec' => '-',
      'nmkec' => '-',
      'noskdp' => '0',
      'kddpjp' => $kd_dpjp,
      'nmdpdjp' => $nm_dpjp,
      'tujuankunjungan' => '0',
      'flagprosedur' => '',
      'penunjang' => '',
      'asesmenpelayanan' => '',
      'kddpjplayanan' => $kd_dpjp,
      'nmdpjplayanan' => $nm_dpjp
    ];

    return $this->_saveSepLocal($dataInsert, 'bridging_sep');
  }

  protected function _findRecentSepFromBpjs($noKartu, $tgl)
  {
    if (empty($noKartu) || empty($tgl)) {
      return null;
    }
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $url = $this->api_url . 'monitoring/HistoriPelayanan/NoKartu/' . $noKartu . '/tglMulai/' . $tgl . '/tglAkhir/' . $tgl;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    if (!$data || !isset($data['metaData']['code']) || $data['metaData']['code'] != 200) {
      return null;
    }
    $stringDecrypt = stringDecrypt($key, $data['response']);
    if (empty($stringDecrypt)) {
      return null;
    }
    $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
    $json = json_decode($decompress, true);
    $list = $json['histori'] ?? ($json['list'] ?? []);
    if (!empty($list) && is_array($list)) {
      foreach ($list as $item) {
        if (!empty($item['noSep'])) {
          return $item['noSep'];
        }
      }
    }
    return null;
  }

  public function postSaveSEP()
  {

    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $_POST['kdppkpelayanan'] = $this->settings->get('settings.ppk_bpjs');
    $_POST['nmppkpelayanan'] = $this->settings->get('settings.nama_instansi');
    $userFullName = $this->core->getUserInfo('fullname', null, true);
    $userName = $this->core->getUserInfo('username', null, true);
    $_POST['sep_user'] = !empty($userFullName) ? $userFullName : (!empty($userName) ? $userName : 'Admin');

    $data = [
      'request' => [
        't_sep' => [
          'noKartu' => $_POST['no_kartu'],
          'tglSep' => $_POST['tglsep'],
          'ppkPelayanan' => $_POST['kdppkpelayanan'],
          'jnsPelayanan' => $_POST['jnspelayanan'],
          'klsRawat' => [
            'klsRawatHak' => $_POST['klsrawat'],
            'klsRawatNaik' => '',
            'pembiayaan' => '',
            'penanggungJawab' => ''
          ],
          'noMR' => $_POST['nomr'],
          'rujukan' => [
            'asalRujukan' => $_POST['asal_rujukan'],
            'tglRujukan' => $_POST['tglrujukan'],
            'noRujukan' => $_POST['norujukan'],
            'ppkRujukan' => $_POST['kdppkrujukan']
          ],
          'catatan' => $_POST['catatan'],
          'diagAwal' => $_POST['diagawal'],
          'poli' => [
            'tujuan' => $_POST['kdpolitujuan'],
            'eksekutif' => $_POST['eksekutif']
          ],
          'cob' => [
            'cob' => $_POST['cob']
          ],
          'katarak' => [
            'katarak' => $_POST['katarak']
          ],
          'jaminan' => [
            'lakaLantas' => $_POST['lakalantas'],
            'noLP' => $_POST['noLp'],
            'penjamin' => [
              'tglKejadian' => $_POST['tglkkl'],
              'keterangan' => $_POST['keterangankkl'],
              'suplesi' => [
                'suplesi' => $_POST['suplesi'],
                'noSepSuplesi' => $_POST['no_sep_suplesi'],
                'lokasiLaka' => [
                  'kdPropinsi' => $_POST['kdprop'],
                  'kdKabupaten' => $_POST['kdkab'],
                  'kdKecamatan' => $_POST['kdkec']
                ]
              ]
            ]
          ],
          'tujuanKunj' => $_POST['tujuanKunj'],
          'flagProcedure' => $_POST['flagProcedure'],
          'kdPenunjang' => $_POST['kdPenunjang'],
          'assesmentPel' => $_POST['assesmentPel'],
          'skdp' => [
            'noSurat' => $_POST['noskdp'],
            'kodeDPJP' => $_POST['kddpjp']
          ],
          'dpjpLayan' => $_POST['kddpjppelayanan'],
          'noTelp' => $_POST['notelep'],
          'user' => mb_substr($_POST['sep_user'], 0, 50, 'UTF-8')
        ]
      ]
    ];

    $data = json_encode($data);
    // echo $data;

    $url = $this->api_url . 'SEP/2.0/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);

    if ($data == NULL) {

      echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
    } else if ($data['metaData']['code'] == 200) {

      $code = $data['metaData']['code'];
      $message = $data['metaData']['message'];
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      if ($data != null) {
        $data = '{
            	"metaData": {
            		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
            	},
            	"response": ' . $decompress . '}';

        $data = json_decode($data, true);

        $_POST['sep_no_sep'] = $data['response']['sep']['noSep'] ?? ($data['response']['noSep'] ?? '');

        $pdo = \Systems\Lib\QueryWrapper::pdo();
        $inTransaction = false;
        if ($pdo && !$pdo->inTransaction()) {
          $pdo->beginTransaction();
          $inTransaction = true;
        }

        try {
          if ($_POST['jnspelayanan'] == 1 && !empty($_POST['noskdp'])) {
            $this->db('bridging_surat_pri_bpjs')->where('no_surat', $_POST['noskdp'])->update([
              'no_sep' => $_POST['sep_no_sep']
            ]);
          }

          $dataSepToSave = $_POST;
          $dataSepToSave['no_sep'] = $_POST['sep_no_sep'];
          $simpan_sep = $this->_saveSepLocal($dataSepToSave, 'bridging_sep');

          if ($simpan_sep) {
            if (!empty($_POST['prolanis_prb'])) {
              $this->db('bpjs_prb')->save([
                'no_sep' => $_POST['sep_no_sep'],
                'prb' => $_POST['prolanis_prb']
              ]);
            }
            echo htmlspecialchars($_POST['sep_no_sep'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          } else {
            $simpan_sep_int = $this->_saveSepLocal($dataSepToSave, 'bridging_sep_internal');
            if (!empty($_POST['prolanis_prb'])) {
              $this->db('bpjs_prb')->save([
                'no_sep' => $_POST['sep_no_sep'],
                'prb' => $_POST['prolanis_prb']
              ]);
            }
            echo htmlspecialchars($_POST['sep_no_sep'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          }
          if ($inTransaction && $pdo) {
            $pdo->commit();
          }
          $this->_autoKirimAntreanBpjs($_POST);
        } catch (\Throwable $e) {
          if ($inTransaction && $pdo) {
            $pdo->rollBack();
          }
          // Fallback save directly to avoid leaving BPJS SEP unrecorded locally
          try {
            $dataSepToSave = $_POST;
            $dataSepToSave['no_sep'] = $_POST['sep_no_sep'];
            $this->_saveSepLocal($dataSepToSave, 'bridging_sep');
            echo htmlspecialchars($_POST['sep_no_sep'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          } catch (\Throwable $ex) {
            echo 'Gagal menyimpan SEP ke database lokal SIMRS: ' . $e->getMessage();
          }
        }
      } else {
        echo '{
            	"metaData": {
            		"code": "5000",
            		"message": "ERROR"
            	},
            	"response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
      }
    } else {
      $msg = isset_or($data['metaData']['message'], '');
      $existingNoSep = null;

      // Cek apakah BPJS merespon pesan berisi No. SEP (misal: peserta sudah pernah diterbitkan SEP)
      if (preg_match('/([0-9]{4}[A-Za-z][0-9]{8}[A-Za-z0-9]{5,7})/i', $msg, $matches)) {
        $existingNoSep = $matches[1];
      }

      // Jika pesan BPJS menyebutkan sudah pernah dibuatkan SEP namun no SEP tidak tertulis di teks
      if (!$existingNoSep && (stripos($msg, 'sudah pernah') !== false || stripos($msg, 'dalam proses pelayanan') !== false)) {
        $existingNoSep = $this->_findRecentSepFromBpjs($_POST['no_kartu'], $_POST['tglsep']);
      }

      if ($existingNoSep) {
        $saved = $this->_pullAndSaveSep($existingNoSep, $_POST['no_rawat']);
        if ($saved) {
          echo htmlspecialchars($existingNoSep, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          exit();
        }
      }

      echo htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    exit();
  }

  public function postHapusSEP()
  {
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $data = [
      'request' => [
        't_sep' => [
          'noSep' => $_POST['no_sep'],
          'user' => $_POST['sep_user']
        ]
      ]
    ];

    $data = json_encode($data);

    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/2.0/delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);

    if ($data == NULL) {

      echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
    } else if ($data['metaData']['code'] == 200) {
      $hapus_sep = $this->db('bridging_sep')->where('no_sep', $_POST['no_sep'])->delete();
      $hapus_sep_internal = $this->db('bridging_sep_internal')->where('no_sep', $_POST['no_sep'])->delete();
      $hapus_prb = $this->db('bpjs_prb')->where('no_sep', $_POST['no_sep'])->delete();
      echo htmlspecialchars($data['metaData']['message'] . '!! Menghapus data SEP dengan nomor ' . $_POST['no_sep'] . '....', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    } else {
      echo htmlspecialchars($data['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCetakSEP($no_sep)
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $data_sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
    if (!$data_sep) {
      $data_sep = $this->db('bridging_sep_internal')->where('no_sep', $no_sep)->oneArray();
    }
    if (!$data_sep) {
      $this->_pullAndSaveSep($no_sep);
      $data_sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
    }
    if (!$data_sep) {
      echo '<div style="padding:20px;font-family:sans-serif;text-align:center;"><h3>Data SEP tidak ditemukan di database SIMRS maupun server BPJS</h3><p>Nomor SEP: ' . htmlspecialchars($no_sep) . '</p><button onclick="window.close()" style="padding:8px 16px;cursor:pointer;margin-top:10px;">Tutup</button></div>';
      exit();
    }
    $batas_rujukan = strtotime('+87 days', strtotime(isset_or($data_sep['tglrujukan'], '')));

    $qr = QRCode::getMinimumQRCode(isset_or($data_sep['no_sep'], ''), QR_ERROR_CORRECT_LEVEL_L);
    //$qr=QRCode::getMinimumQRCode('Petugas: '.$this->core->getUserInfo('fullname', null, true).'; Lokasi: '.UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf',QR_ERROR_CORRECT_LEVEL_L);
    $im = $qr->createImage(4, 4);
    imagepng($im, BASE_DIR .'/'.ADMIN.'/tmp/qrcode.png');
    imagedestroy($im);
    $image = "../../".ADMIN."/tmp/qrcode.png";

    $data_sep['qrCode'] = $image;
    $data_sep['batas_rujukan'] = date('Y-m-d', $batas_rujukan);
    $potensi_prb = $this->db('bpjs_prb')->where('no_sep', $no_sep)->oneArray();
    $data_sep['potensi_prb'] = isset_or($potensi_prb['prb']);

    $poliklinik = $this->db('poliklinik')->toArray();
    $dokter = $this->db('dokter')->toArray();
    echo $this->draw('cetak.sep.html', ['data_sep' => htmlspecialchars_array($data_sep), 'poliklinik' => htmlspecialchars_array($poliklinik), 'dokter' => htmlspecialchars_array($dokter)]);
    exit();
  }

  public function getCetakSEPInternal($no_sep)
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $data_sep = $this->db('bridging_sep_internal')->where('no_sep', $no_sep)->oneArray();
    $batas_rujukan = strtotime('+87 days', strtotime($data_sep['tglrujukan']));

    $qr = QRCode::getMinimumQRCode($data_sep['no_sep'], QR_ERROR_CORRECT_LEVEL_L);
    //$qr=QRCode::getMinimumQRCode('Petugas: '.$this->core->getUserInfo('fullname', null, true).'; Lokasi: '.UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf',QR_ERROR_CORRECT_LEVEL_L);
    $im = $qr->createImage(4, 4);
    imagepng($im, BASE_DIR .'/'.ADMIN.'/tmp/qrcode.png');
    imagedestroy($im);
    $image = "../../".ADMIN."/tmp/qrcode.png";

    $data_sep['qrCode'] = $image;
    $data_sep['batas_rujukan'] = date('Y-m-d', $batas_rujukan);
    $potensi_prb = $this->db('bpjs_prb')->where('no_sep', $no_sep)->oneArray();
    $data_sep['potensi_prb'] = $potensi_prb['prb'];

    echo $this->draw('cetak.sep.internal.html', ['data_sep' => htmlspecialchars_array($data_sep)]);
    exit();
  }

  public function getCetakSRB($no_sep)
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $data_sep = $this->db('bridging_srb_bpjs')
    ->join('bridging_sep', 'bridging_sep.no_sep=bridging_srb_bpjs.no_sep')
    ->where('bridging_sep.no_sep', $no_sep)->oneArray();
    $obat_srb = $this->db('bridging_srb_bpjs_obat')->where('no_srb',$data_sep['no_srb'])->toArray();

    echo $this->draw('cetak.srb.html', ['data_sep' => htmlspecialchars_array($data_sep),'obat_srb'=>htmlspecialchars_array($obat_srb)]);
    exit();
  }

  public function __postSyncSEP()
  {
    $_POST['kdppkpelayanan'] = $this->settings->get('settings.ppk_bpjs');
    $_POST['nmppkpelayanan'] = $this->settings->get('settings.nama_instansi');
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $data = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->where('no_peserta', $_POST['no_kartu'])
      ->where('tgl_registrasi', $_POST['tglsep'])
      ->oneArray();

    if (!$data) {

      echo 'Data pasien tidak ditemukan!';
    } else {

      $_POST['no_rawat'] = $data['no_rawat'];
      $simpan_sep = $this->_saveSepLocal($_POST, 'bridging_sep');
      // $simpan_prb = $this->db('bpjs_prb')->save([
      //   'no_sep' => $_POST['sep_no_sep'],
      //   'prb' => $_POST['prolanis_prb']
      // ]);
      // if($simpan_sep) {
      //   echo $_POST['sep_no_sep'];
      // }
      // echo $_POST['sep_no_sep'];
      // echo '0186R0020920V003231';
    }

    exit();
  }

  public function postSyncSEP()
  {
    $date = date('Y-m-d');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    header('Content-type: text/html');
    $url = $this->settings->get('settings.BpjsApiUrl') . 'SEP/' . $_POST['no_sep'];
    $consid = $this->settings->get('settings.BpjsConsID');
    $secretkey = $this->settings->get('settings.BpjsSecretKey');
    $userkey = $this->settings->get('settings.BpjsUserKey');
    $output = BpjsService::get($url, NULL, $consid, $secretkey, $userkey, $tStamp);
    $data = json_decode($output, true);
    // print_r($output);
    $code = $data['metaData']['code'];
    $message = $data['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $data['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($data != null) {
      $data = '{
          "metaData": {
            "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          },
          "response": ' . $decompress . '}';
      $data = json_decode($data, true);
    } else {
        $code = "5000"; // Default error code
        $data = [
            "metaData" => [
              "code" => "5000",
              "message" => "ERROR"
            ],
            "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
    }

    $jenis_pelayanan = '2';
    if ($data['response']['jnsPelayanan'] == 'Rawat Inap') {
      $jenis_pelayanan = '1';
    }
    // echo json_encode(htmlspecialchars_array($data));
    $data_rujukan = [];
    $no_telp = "00000000";
    if ($data['response']['noRujukan'] == "") {
      $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
      $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
      $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
      $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
      $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
      $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
    } else {
      $url_rujukan = $this->settings->get('settings.BpjsApiUrl') . 'Rujukan/' . $data['response']['noRujukan'];
      if ($_POST['asal_rujukan'] == 2) {
        $url_rujukan = $this->settings->get('settings.BpjsApiUrl') . 'Rujukan/RS/' . $data['response']['noRujukan'];
      }
      $rujukan = BpjsService::get($url_rujukan, NULL, $consid, $secretkey, $userkey, $tStamp);
      $data_rujukan = json_decode($rujukan, true);
      // print_r($rujukan);

      $code = $data_rujukan['metaData']['code'];
      $message = $data_rujukan['metaData']['message'];
      $stringDecrypt = stringDecrypt($key, $data_rujukan['response']);
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      if ($data_rujukan != null) {
      $data_rujukan = '{
          "metaData": {
            "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          },
          "response": ' . $decompress . '}';
      $data_rujukan = json_decode($data_rujukan, true);
    } else {
      $data_rujukan = [
          "metaData" => [
            "code" => "5000",
            "message" => "ERROR"
          ],
          "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
    }

      // echo json_encode($data_rujukan);
      $no_telp = $data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'];
      if (empty($data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'])) {
        $no_telp = '00000000';
      }

      if ($data_rujukan['metaData']['code'] == 201) {
        $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
        $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
        $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
        $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
        $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
        $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
      } else if ($data_rujukan['metaData']['code'] == 202) {
        $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
        $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
        $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
        $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
        $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
        $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
      }
    }

    if($data['response']['dpjp']['kdDPJP'] =='0')
    {
	    $data['response']['dpjp']['kdDPJP'] = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['kd_dokter_bpjs'];
      $data['response']['dpjp']['nmDPJP'] = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['nm_dokter_bpjs'];
    }

    if ($data['metaData']['code'] == 200) {
      $insert = $this->_saveSepLocal([
        'no_sep' => $data['response']['noSep'],
        'no_rawat' => $_POST['no_rawat'],
        'tglsep' => $data['response']['tglSep'],
        'tglrujukan' => $data_rujukan['response']['rujukan']['tglKunjungan'],
        'no_rujukan' => $data['response']['noRujukan'],
        'kdppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['kode'],
        'nmppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['nama'],
        'kdppkpelayanan' => $this->settings->get('settings.ppk_bpjs'),
        'nmppkpelayanan' => $this->settings->get('settings.nama_instansi'),
        'jnspelayanan' => $jenis_pelayanan,
        'catatan' => $data['response']['catatan'],
        'diagawal' => $data_rujukan['response']['rujukan']['diagnosa']['kode'],
        'nmdiagnosaawal' => $data_rujukan['response']['rujukan']['diagnosa']['nama'],
        'kdpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['kd_poli_bpjs'],
        'nmpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['nm_poli_bpjs'],
        'klsrawat' =>  $data['response']['klsRawat']['klsRawatHak'],
        'klsnaik' => $data['response']['klsRawat']['klsRawatNaik'] == null ? "" : $data['response']['klsRawat']['klsRawatNaik'],
        'pembiayaan' => $data['response']['klsRawat']['pembiayaan']  == null ? "" : $data['response']['klsRawat']['pembiayaan'],
        'pjnaikkelas' => $data['response']['klsRawat']['penanggungJawab']  == null ? "" : $data['response']['klsRawat']['penanggungJawab'],
        'lakalantas' => '0',
        'user' => $this->_getSafeUserDb('bridging_sep'),
        'nomr' => $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']),
        'nama_pasien' => $data['response']['peserta']['nama'],
        'tanggal_lahir' => $data['response']['peserta']['tglLahir'],
        'peserta' => $data['response']['peserta']['jnsPeserta'],
        'jkel' => $data['response']['peserta']['kelamin'],
        'no_kartu' => $data['response']['peserta']['noKartu'],
        'tglpulang' => '1900-01-01 00:00:00',
        'asal_rujukan' => $_POST['asal_rujukan'],
        'eksekutif' => $data['response']['poliEksekutif'],
        'cob' => '0',
        'notelep' => $no_telp,
        'katarak' => '0',
        'tglkkl' => '1900-01-01',
        'keterangankkl' => '-',
        'suplesi' => '0',
        'no_sep_suplesi' => '-',
        'kdprop' => '-',
        'nmprop' => '-',
        'kdkab' => '-',
        'nmkab' => '-',
        'kdkec' => '-',
        'nmkec' => '-',
        'noskdp' => '0',
        'kddpjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['kd_dokter_bpjs'],
        'nmdpdjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $_POST['kd_dokter'])->oneArray()['nm_dokter_bpjs'],
        'tujuankunjungan' => '0',
        'flagprosedur' => '',
        'penunjang' => '',
        'asesmenpelayanan' => '',
        'kddpjplayanan' => $data['response']['dpjp']['kdDPJP'],
        'nmdpjplayanan' => $data['response']['dpjp']['nmDPJP']
      ], 'bridging_sep');
    }
    // print_r($insert);
    if ($insert) {
      $this->db('bpjs_prb')->save(['no_sep' => $data['response']['noSep'], 'prb' => $data_rujukan['response']['rujukan']['peserta']['informasi']['prolanisPRB']]);
      $this->notify('success', 'Simpan sukes');
      redirect(url([ADMIN, 'vclaim', 'cetaksep', $_POST['no_sep'], convertNoRawat($_POST['no_rawat'])]));
    } else {
      $this->notify('failure', 'Simpan gagal');
      redirect(url([ADMIN, 'vclaim', 'cetaksep', $_POST['no_sep'], convertNoRawat($_POST['no_rawat'])]));
    }
  }

  public function getCariByNoRujukanModal($searchBy, $keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    if ($searchBy == 'RS') {
      $url = 'Rujukan/RS/' . $keyword;
    } else {
      $url = 'Rujukan/' . $keyword;
    }
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      $json = '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
      $this->tpl->set('rujukan', json_encode($json, JSON_PRETTY_PRINT));
      echo $this->tpl->draw(MODULES . '/vclaim/view/admin/rujukan.modal.html', true);
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDiagnosa($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/diagnosa/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    
    if ($json && isset($json['metaData'])) {
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        }
        echo '{
            "metaData": {
                "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
                "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
            },
            "response": ' . $decompress . '}';
    } else {
        echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDiagnosaPrb()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/diagnosaprb';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getObatPrb($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/obatprb/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }


  public function getPoli($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/poli/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getFaskes($kd_faskes = null, $jns_faskes = null)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/faskes/' . $kd_faskes . '/' . $jns_faskes;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDokterDpjp($jnsPelayanan, $tglPelayanan, $spesialis)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $url = $this->api_url . 'referensi/dokter/pelayanan/' . $jnsPelayanan . '/tglPelayanan/' . $tglPelayanan . '/Spesialis/' . $spesialis;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    if ($json != null && isset($json['metaData'])) {
      $code = $json['metaData']['code'] ?? '500';
      $message = $json['metaData']['message'] ?? '';
      $stringDecrypt = stringDecrypt($key, $json['response'] ?? '');
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . ($decompress ?: '""') . '}';
    } else {
      $cleanMsg = $output ? strip_tags(trim($output)) : 'DPJP tidak ditemukan atau koneksi BPJS terputus.';
      echo json_encode([
        'metaData' => [
          'code' => '500',
          'message' => $cleanMsg
        ],
        'response' => null
      ]);
    }
    exit();
  }

  public function getMapingDokter($no_rawat)
  {
    $no_rawat = revertNoRawat($no_rawat);
    $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
    if (!$reg) {
      echo json_encode(['status' => 'error', 'message' => 'Registrasi tidak ditemukan']);
      exit();
    }
    $dokter = $this->db('dokter')->where('kd_dokter', $reg['kd_dokter'])->oneArray();
    $nm_dokter = $dokter ? $dokter['nm_dokter'] : '';
    $maping = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg['kd_dokter'])->oneArray();

    echo json_encode([
      'status' => 'success',
      'kd_dokter' => $reg['kd_dokter'],
      'nm_dokter' => $nm_dokter,
      'kd_dokter_bpjs' => $maping ? $maping['kd_dokter_bpjs'] : '',
      'nm_dokter_bpjs' => $maping ? $maping['nm_dokter_bpjs'] : ''
    ]);
    exit();
  }

  private function _autoKirimAntreanBpjs($postData)
  {
    try {
      $no_rawat = isset($postData['no_rawat']) ? revertNoRawat($postData['no_rawat']) : '';
      if (empty($no_rawat)) {
        return false;
      }

      $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
      if (!$reg_periksa) {
        return false;
      }

      $date = !empty($postData['tglsep']) ? $postData['tglsep'] : date('Y-m-d');
      $tentukan_hari = date('D', strtotime($date));
      $day = array(
        'Sun' => 'AKHAD',
        'Mon' => 'SENIN',
        'Tue' => 'SELASA',
        'Wed' => 'RABU',
        'Thu' => 'KAMIS',
        'Fri' => 'JUMAT',
        'Sat' => 'SABTU'
      );
      $hari = $day[$tentukan_hari];

      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
      $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();

      // Gunakan mapping dokter/poli, jika kosong gunakan data DPJP/Poli dari SEP yang baru saja disimpan
      $kd_dokter_bpjs = $maping_dokter_dpjpvclaim ? $maping_dokter_dpjpvclaim['kd_dokter_bpjs'] : (!empty($postData['kddpjppelayanan']) ? $postData['kddpjppelayanan'] : (!empty($postData['kddpjp']) ? $postData['kddpjp'] : ''));
      $nm_dokter_bpjs = $maping_dokter_dpjpvclaim ? $maping_dokter_dpjpvclaim['nm_dokter_bpjs'] : (!empty($postData['nmdpjppelayanan']) ? $postData['nmdpjppelayanan'] : (!empty($postData['nmdpdjp']) ? $postData['nmdpdjp'] : ''));
      $kd_poli_bpjs = $maping_poli_bpjs ? $maping_poli_bpjs['kd_poli_bpjs'] : (!empty($postData['kdpolitujuan']) ? $postData['kdpolitujuan'] : '');
      $nm_poli_bpjs = $maping_poli_bpjs ? $maping_poli_bpjs['nm_poli_bpjs'] : (!empty($postData['nmpolitujuan']) ? $postData['nmpolitujuan'] : '');

      if (empty($kd_dokter_bpjs) || empty($kd_poli_bpjs)) {
        error_log("_autoKirimAntreanBpjs skipped: missing dokter or poli BPJS code for rawat " . $no_rawat);
        return false;
      }

      $jam_mulai = ($jadwaldokter && !empty($jadwaldokter['jam_mulai'])) ? $jadwaldokter['jam_mulai'] : '08:00:00';
      $jam_selesai = ($jadwaldokter && !empty($jadwaldokter['jam_selesai'])) ? $jadwaldokter['jam_selesai'] : '14:00:00';
      $kuota = ($jadwaldokter && !empty($jadwaldokter['kuota'])) ? intval($jadwaldokter['kuota']) : 30;

      $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
      $minutes = intval($no_urut_reg) * 10;
      $jam_est = date('H:i:s', strtotime('+' . $minutes . ' minutes', strtotime($jam_mulai)));

      $ppk = $this->settings->get('settings.ppk_bpjs');
      $kodebooking = $ppk . convertNorawat($reg_periksa['no_rawat']) . $kd_poli_bpjs . $reg_periksa['no_reg'];

      // Tentukan nomor referensi dan jenis kunjungan
      // 1 = Rujukan FKTP, 2 = Rujukan Internal, 3 = Kontrol, 4 = Rujukan Antar RS
      $jenis_kunjungan = 1;
      $nomorreferensi = !empty($postData['norujukan']) ? $postData['norujukan'] : '';
      if (!empty($postData['noskdp'])) {
        $jenis_kunjungan = 3;
        $nomorreferensi = $postData['noskdp'];
      } elseif (isset($postData['asal_rujukan']) && $postData['asal_rujukan'] == '2') {
        $jenis_kunjungan = 4;
      }

      if (empty($nomorreferensi)) {
        error_log("_autoKirimAntreanBpjs skipped: nomor referensi empty for rawat " . $no_rawat);
        return false;
      }

      $no_rkm_medis = $reg_periksa['no_rkm_medis'];
      $nomorkartu = !empty($postData['no_kartu']) ? $postData['no_kartu'] : $this->core->getPasienInfo('no_peserta', $no_rkm_medis);
      $nik = $this->core->getPasienInfo('no_ktp', $no_rkm_medis);
      $nohp = !empty($postData['notelep']) ? $postData['notelep'] : $this->core->getPasienInfo('no_tlp', $no_rkm_medis);
      if (empty($nohp)) {
        $nohp = '0000000000';
      }

      $dataAntrean = [
        'kodebooking' => $kodebooking,
        'jenispasien' => 'JKN',
        'nomorkartu' => $nomorkartu,
        'nik' => $nik,
        'nohp' => $nohp,
        'kodepoli' => $kd_poli_bpjs,
        'namapoli' => $nm_poli_bpjs,
        'pasienbaru' => ($reg_periksa['stts_daftar'] == 'Baru') ? '1' : '0',
        'norm' => $no_rkm_medis,
        'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
        'kodedokter' => $kd_dokter_bpjs,
        'namadokter' => $nm_dokter_bpjs,
        'jampraktek' => substr($jam_mulai, 0, 5) . '-' . substr($jam_selesai, 0, 5),
        'jeniskunjungan' => $jenis_kunjungan,
        'nomorreferensi' => $nomorreferensi,
        'nomorantrean' => $kd_poli_bpjs . '-' . $reg_periksa['no_reg'],
        'angkaantrean' => $reg_periksa['no_reg'],
        'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'] . ' ' . $jam_est) * 1000,
        'sisakuotajkn' => max(0, $kuota - intval(ltrim($reg_periksa['no_reg'], '0'))),
        'kuotajkn' => $kuota,
        'sisakuotanonjkn' => max(0, $kuota - intval(ltrim($reg_periksa['no_reg'], '0'))),
        'kuotanonjkn' => $kuota,
        'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
      ];

      $payload = json_encode($dataAntrean);
      $antrianUrl = $this->settings->get('jkn_mobile.BpjsAntrianUrl') . 'antrean/add';
      $consId = $this->settings->get('jkn_mobile.BpjsConsID');
      $secKey = $this->settings->get('jkn_mobile.BpjsSecretKey');
      $userKey = $this->settings->get('jkn_mobile.BpjsUserKey');

      if (!empty($antrianUrl) && !empty($consId)) {
        $resp = BpjsService::post($antrianUrl, $payload, $consId, $secKey, $userKey, NULL);
        $resJson = json_decode($resp, true);

        $code = isset($resJson['metadata']['code']) ? $resJson['metadata']['code'] : (isset($resJson['metaData']['code']) ? $resJson['metaData']['code'] : null);
        $msg = isset($resJson['metadata']['message']) ? $resJson['metadata']['message'] : (isset($resJson['metaData']['message']) ? $resJson['metaData']['message'] : 'Response null');

        // 200 = Success, 208 = Kode booking sudah ada di BPJS
        $statusKirim = ($code == 200 || $code == 208) ? 'Sudah' : 'Gagal';
        $this->db('mlite_antrian_referensi')->save([
          'tanggal_periksa' => $reg_periksa['tgl_registrasi'],
          'no_rkm_medis' => $no_rkm_medis,
          'nomor_kartu' => $nomorkartu,
          'nomor_referensi' => $nomorreferensi,
          'kodebooking' => $kodebooking,
          'jenis_kunjungan' => $jenis_kunjungan,
          'status_kirim' => $statusKirim,
          'keterangan' => $code . ': ' . $msg
        ]);
        error_log("_autoKirimAntreanBpjs for rawat {$no_rawat} done with status: {$statusKirim} ({$code}: {$msg})");
        return true;
      }
    } catch (\Throwable $e) {
      error_log("_autoKirimAntreanBpjs error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    }
    return false;
  }

  public function getPropinsi()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/propinsi';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getKabupaten($kdPropinsi)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/kabupaten/propinsi/' . $kdPropinsi;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getKecamatan($kdKabupaten)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/kecamatan/kabupaten/' . $kdKabupaten;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getProcedure($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/procedure/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getKelasRawat()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/kelasrawat';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDokter($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/dokter/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    exit();
  }

  public function getFinger($keyword)
  {
    $dateNow = date('Y-m-d');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . '/SEP/FingerPrint/Peserta/' . $keyword . '/TglPelayanan/'.$dateNow.'';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    exit();
  }

  public function getFrista($keyword)
  {
    $dateNow = date('Y-m-d');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . '/SEP/FingerPrint/Peserta/' . $keyword . '/TglPelayanan/'.$dateNow.'';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    exit();
  }

  public function getApproveFinger($keyword)
  {
    $dateNow = date('Y-m-d');
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $data = [
      'request' => [
        't_sep' => [
          'noKartu' => $keyword,
          'tglSep' => $dateNow,
          'jnsPelayanan' => '2',
          'jnsPengajuan' => '2',
          'keterangan' => 'Approval Finger Manual',
          'user' => $_POST['sep_user']
        ]
      ]
    ];

    $data = json_encode($data);
    $url = $this->api_url . '/Sep/aprovalSEP';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getSpesialistik()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/spesialistik';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getRuangRawat()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/ruangrawat';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCaraKeluar()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/carakeluar';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getPascaPulang()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'referensi/pascapulang';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getByNoKartu($noKartu, $tglPelayananSEP)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Peserta/nokartu/' . $noKartu . '/tglSEP/' . $tglPelayananSEP;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    if ($json != null && isset($json['metaData'])) {
      $code = $json['metaData']['code'] ?? '500';
      $message = $json['metaData']['message'] ?? '';
      $stringDecrypt = stringDecrypt($key, $json['response'] ?? '');
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      echo '{
          "metaData": {
            "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          },
          "response": ' . ($decompress ?: '""') . '}';
    } else {
      $cleanMsg = $output ? strip_tags(trim($output)) : 'Peserta tidak ditemukan atau koneksi BPJS terputus.';
      echo json_encode([
        'metaData' => [
          'code' => '500',
          'message' => $cleanMsg
        ],
        'response' => null
      ]);
    }
    exit();
  }

  public function getByNIK($nik, $tglPelayananSEP)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Peserta/nik/' . $nik . '/tglSEP/' . $tglPelayananSEP;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
        	"metaData": {
        		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
        		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
        	},
        	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postInsertSEP($data = [])
  {
    $data = [
      'request' => [
        't_sep' => [
          'noKartu' => '0001112230666',
          'tglSep' => '2017-10-18',
          'ppkPelayanan' => '0301R001',
          'jnsPelayanan' => '2',
          'klsRawat' => '3',
          'noMR' => '123456',
          'rujukan' => [
            'asalRujukan' => '1',
            'tglRujukan' => '2017-10-17',
            'noRujukan' => '1234567',
            'ppkRujukan' => '00010001'
          ],
          'catatan' => 'test',
          'diagAwal' => 'A00.1',
          'poli' => [
            'tujuan' => 'INT',
            'eksekutif' => '0'
          ],
          'cob' => [
            'cob' => '0'
          ],
          'katarak' => [
            'katarak' => '0'
          ],
          'jaminan' => [
            'lakaLantas' => '0',
            'penjamin' => [
              'penjamin' => '1',
              'tglKejadian' => '2018-08-06',
              'keterangan' => 'kll',
              'suplesi' => [
                'suplesi' => '0',
                'noSepSuplesi' => '0301R0010718V000001',
                'lokasiLaka' => [
                  'kdPropinsi' => '03',
                  'kdKabupaten' => '0050',
                  'kdKecamatan' => '0574'
                ]
              ]
            ]
          ],
          'skdp' => [
            'noSurat' => '000002',
            'kodeDPJP' => '31661'
          ],
          'noTelp' => '081919999',
          'user' => 'Coba Ws'
        ]
      ]
    ];

    $data = json_encode($data);

    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/2.0/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    echo json_encode(htmlspecialchars_array($json));
    exit();
  }

  public function postUpdateSEP($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/2.0/Update';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
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

  public function getDeleteSEP($sep)
  {
    $data = [
      'request' => [
        't_sep' => [
          'noSep' => $sep,
          'user' => 'admin'
        ]
      ]
    ];

    $data = json_encode($data);

    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/2.0/delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postDeleteSuratKontrol()
  {
    header('Content-Type: application/json');
    $no_surkon = $_POST['no_surkon'] ?? '';
    if (empty($no_surkon)) {
      echo json_encode(['status' => 'error', 'message' => 'Nomor surat kontrol tidak boleh kosong']);
      exit();
    }

    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $user = $this->core->getUserInfo('fullname', null, true);

    $data = [
      'request' => [
        't_suratkontrol' => [
          'noSuratKontrol' => $no_surkon,
          'user' => $user
        ]
      ]
    ];

    $data = json_encode($data);
    $url = $this->api_url . 'RencanaKontrol/Delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    if ($json == NULL) {
      echo json_encode(['status' => 'error', 'message' => 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!']);
    } else if (isset($json['metaData']['code']) && $json['metaData']['code'] == 200) {
      $this->db('bridging_surat_kontrol_bpjs')->where('no_surat', $no_surkon)->delete();
      echo json_encode(['status' => 'success', 'message' => 'Surat Kontrol ' . $no_surkon . ' berhasil dihapus']);
    } else {
      $pesan = $json['metaData']['message'] ?? 'Gagal menghapus Surat Kontrol di BPJS';
      echo json_encode(['status' => 'error', 'message' => $pesan]);
    }
    exit();
  }

  public function getCariSEP($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'] ?? 'Unknown error', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getSuplesiJasaRaharja($noKartu, $tglPelayanan)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'sep/JasaRaharja/Suplesi/' . $noKartu . '/tglPelayanan/' . $tglPelayanan;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }
  public function postPengajuanPenjaminanSep($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Sep/pengajuanSEP';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }
  public function postApprovalPenjaminanSep($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Sep/aprovalSEP';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
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

  public function __postUpdateTglPlg($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Sep/updtglplg';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey,  $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
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

  public function postUpdateTglPlg2()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $data = [
      'request' => [
        't_sep' => [
          'noSep' => $_POST['sep'],
          'statusPulang' => $_POST['status_pulang'],
          'noSuratMeninggal' => '',
          'tglMeninggal' => '',
          'tglPulang' => $_POST['tanggal_pulang'],
          'noLPManual' => '',
          'user' => $this->core->getUserInfo('username', null, true)
        ]
      ]
    ];
    $data = json_encode($data);
    $url = $this->api_url . 'SEP/2.0/updtglplg';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey,  $this->user_key, $tStamp);
    $json = json_decode($output, true);
    echo json_encode($json);
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . $code . '",
          		"message": "' . $message . '"
          	},
          	"response": ' . $decompress . '}';
      if($code == '200'){
        $update = $this->db('bridging_sep')->where('no_sep', $_POST['no_sep'])->update([
          'tglpulang' => $_POST['tanggal_pulang']
        ]);
      }
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

  public function getUpdateTglPlg()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $data = [
      'request' => [
        't_sep' => [
          'noSep' => $_POST['no_sep'],
          'statusPulang' => $_POST['status_pulang'],
          'noSuratMeninggal' => '',
          'tglMeninggal' => '',
          'tglPulang' => $_POST['tgl_pulang'],
          'noLPManual' => '',
          'user' => 'Admin'
        ]
      ]
    ];

    $url = $this->api_url . 'Sep/2.0/updtglplg';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey,  $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getInacbgSEP($keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'sep/cbg/' . $keyword;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getSepInternal($noSep)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'SEP/Internal/' . $noSep;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    if($code == '200'){
      $sep_internal = $json['response'];
      $stringDecrypt = stringDecrypt($key, $sep_internal);
      $decompress = "";
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      if ($json != null) {
        echo '{
              "metaData": {
                "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
                "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
              },
              "response": ' . $decompress . '}';
      } else {
        echo json_encode($json);
      }
    } else {
      echo json_encode($json);
    }
    exit();
  }

  public function postInsertRujukan($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Rujukan/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postUpdateRujukan($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Rujukan/update';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postDeleteRujukan($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Rujukan/delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCariByNoRujukan($searchBy, $keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    if ($searchBy == 'RS') {
      $url = 'Rujukan/RS/' . $keyword;
    } else {
      $url = 'Rujukan/' . $keyword;
    }
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $jsonresponse = $json['response'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
            "response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCariByNoKartu($searchBy, $keyword, $multi = false)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $record = $multi ? 'List/' : '';
    if ($searchBy == 'RS') {
      $url = 'Rujukan/RS/' . $record . 'Peserta/' . $keyword;
    } else {
      $url = 'Rujukan/' . $record . 'Peserta/' . $keyword;
    }
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    if ($json != null && isset($json['metaData'])) {
      $code = $json['metaData']['code'] ?? '500';
      $message = $json['metaData']['message'] ?? '';
      $stringDecrypt = stringDecrypt($key, $json['response'] ?? '');
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . ($decompress ?: '""') . '}';
    } else {
      $cleanMsg = $output ? strip_tags(trim($output)) : 'ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS.';
      echo json_encode([
        'metaData' => [
          'code' => '5000',
          'message' => 'ERROR'
        ],
        'response' => $cleanMsg
      ]);
    }
    exit();
  }

  public function getCariByTglRujukan($searchBy, $keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    if ($searchBy == 'RS') {
      $url = 'Rujukan/RS/List/TglRujukan/' . $keyword;
    } else {
      $url = 'Rujukan/List/TglRujukan/' . $keyword;
    }
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));

    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';

      $noKunjungan = [];
      $i = 1;
      foreach ($json['response']['rujukan'] as $key => $value) {
        $row['Nomor'] = $i++;
        $row['noKunjungan'] = '<a href="' . url([ADMIN, 'vclaim', 'caribynorujukanmodal', htmlspecialchars($searchBy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), htmlspecialchars($value['noKunjungan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')]) . '" data-toggle="modal" data-target="#rujukanModal">' . htmlspecialchars($value['noKunjungan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
        $row['Poliklinik'] = '[' . htmlspecialchars($value['poliRujukan']['kode'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '] ' . htmlspecialchars($value['poliRujukan']['nama'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $row['Diagnosa'] = '[' . htmlspecialchars($value['diagnosa']['kode'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '] ' . htmlspecialchars($value['diagnosa']['nama'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $row['Perujuk'] = '[' . htmlspecialchars($value['provPerujuk']['kode'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '] ' . htmlspecialchars($value['provPerujuk']['nama'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $row['Tanggal'] = htmlspecialchars($value['tglKunjungan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $noKunjungan[] = $row;
      }
      echo json_encode(htmlspecialchars_array($noKunjungan));
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCariByTglRujukan_BackUp($searchBy, $keyword)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    if ($searchBy == 'RS') {
      $url = 'Rujukan/RS/List/TglRujukan/' . $keyword;
    } else {
      $url = 'Rujukan/List/TglRujukan/' . $keyword;
    }
    $url = $this->api_url . '' . $url;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo '<pre>'.json_encode($json, JSON_PRETTY_PRINT).'</pre>';
    /*$listRujukan = [];
        $i = 1;
        foreach ($json['response']['rujukan'] as $key=>$value) {
          $row['nomor'] = $i++;
          if ($searchBy == 'RS') {
              $url = 'Rujukan/RS/'.$keyword;
          } else {
              $url = 'Rujukan/'.$keyword;
          }
          $url = $this->api_url.''.$url;
          $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
          $json = json_decode($output, true);
          //echo json_encode(htmlspecialchars_array($json));
          $row['noRujukan'] = $value['noKunjungan'];
          $listRujukan[] = $row;
        }
        echo json_encode($listRujukan);*/

    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postInsertLPK($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'LPK/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postUpdateLPK($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'LPK/update';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postDeleteLPK($data = [])
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'LPK/delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getCariLPK($tglMasuk, $jnsPelayanan)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'LPK/TglMasuk/' . $tglMasuk . '/JnsPelayanan/' . $jnsPelayanan;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
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

  public function getDataKunjungan($tglSep, $jnsPelayanan)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Monitoring/Kunjungan/Tanggal/' . $tglSep . '/JnsPelayanan/' . $jnsPelayanan;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
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

  public function getDataKlaim($tglPulang, $jnsPelayanan, $statusKlaim)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Monitoring/Klaim/Tanggal/' . $tglPulang . '/JnsPelayanan/' . $jnsPelayanan . '/Status/' . $statusKlaim;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getHistoriPelayanan($noKartu, $tglAwal, $tglAkhir)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'monitoring/HistoriPelayanan/NoKartu/' . $noKartu . '/tglMulai/' . $tglAwal . '/tglAkhir/' . $tglAkhir;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    // echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDataKlaimJasaRaharja($tglMulai, $tglAkhir)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'monitoring/JasaRaharja/tglMulai/' . $tglMulai . '/tglAkhir/' . $tglAkhir;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getSPRI($no_kartu, $no_rawat)
  {
    $this->_addHeaderFiles();
    $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
    $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();

    // Fallback dokter jika tabel mapping kosong
    if (empty($maping_dokter_dpjpvclaim)) {
      $tStamp = strval(time());
      $key = $this->consid . $this->secretkey . $tStamp;
      $url = $this->api_url . 'referensi/dokter/pelayanan/1/tglPelayanan/' . date('Y-m-d') . '/Spesialis/GIG';
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      if (!empty($json['response'])) {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
          $arr = json_decode($decompress, true);
          if (!empty($arr['list']) && is_array($arr['list'])) {
            foreach ($arr['list'] as $d) {
              $maping_dokter_dpjpvclaim[] = [
                'kd_dokter_bpjs' => $d['kode'],
                'nm_dokter_bpjs' => $d['nama']
              ];
            }
          }
        }
      }
    }

    // Fallback poli jika tabel mapping kosong
    if (empty($maping_poli_bpjs)) {
      $tStamp = strval(time());
      $key = $this->consid . $this->secretkey . $tStamp;
      $url = $this->api_url . 'referensi/poli/gigi';
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      if (!empty($json['response'])) {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
          $arr = json_decode($decompress, true);
          if (!empty($arr['poli']) && is_array($arr['poli'])) {
            foreach ($arr['poli'] as $p) {
              $maping_poli_bpjs[] = [
                'kd_poli_bpjs' => $p['kode'],
                'nm_poli_bpjs' => $p['nama']
              ];
            }
          }
        }
      }
    }

    $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->where('no_kartu', $no_kartu)->toArray();
    $this->tpl->set('spri', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_pri_bpjs)));
    $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
    $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
    $this->tpl->set('no_kartu', htmlspecialchars($no_kartu, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $this->tpl->set('no_rawat', htmlspecialchars(revertNorawat($no_rawat), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    echo $this->draw('spri.html');
    exit();
  }

  public function getSPRIDisplay($no_kartu, $no_rawat)
  {
    $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->where('no_kartu', $no_kartu)->toArray();
    $this->tpl->set('spri', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_pri_bpjs)));
    echo $this->draw('spri.display.html');
    exit();
  }

  public function getSPRIData($no_surat)
  {
    $bridging_surat_pri_bpjs = $this->db('bridging_surat_pri_bpjs')->where('no_surat', $no_surat)->toArray();
    echo json_encode(!empty($bridging_surat_pri_bpjs[0]) ? $bridging_surat_pri_bpjs[0] : []);
    exit();
  }

  public function getSyncSPRI($no_kartu, $no_rawat)
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $synced = 0;
    $rawat = revertNorawat($no_rawat);
    $cek_rawat = $this->db('reg_periksa')->where('no_rawat', $rawat)->oneArray();
    if (!$cek_rawat) {
      $rawat = null;
    }

    $periods = [
      ['m' => date('m'), 'y' => date('Y')],
      ['m' => date('m', strtotime('-1 month')), 'y' => date('Y', strtotime('-1 month'))]
    ];

    foreach ($periods as $period) {
      foreach ([2, 1] as $filter) {
        $url = $this->api_url . 'RencanaKontrol/ListRencanaKontrol/Bulan/' . $period['m'] . '/Tahun/' . $period['y'] . '/Nokartu/' . $no_kartu . '/filter/' . $filter;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        if (!empty($json['metaData']['code']) && $json['metaData']['code'] == 200 && !empty($json['response'])) {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          if (!empty($stringDecrypt)) {
            $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
            $resp = json_decode($decompress, true);
            if (!empty($resp['list']) && is_array($resp['list'])) {
              foreach ($resp['list'] as $item) {
                $no_surat = $item['noSuratKontrol'] ?? ($item['noSPRI'] ?? '');
                if (!empty($no_surat)) {
                  $exist = $this->db('bridging_surat_pri_bpjs')->where('no_surat', $no_surat)->oneArray();
                  if (!$exist) {
                    $this->db('bridging_surat_pri_bpjs')->save([
                      'no_rawat' => $rawat,
                      'no_kartu' => $no_kartu,
                      'tgl_surat' => $item['tglTerbitKontrol'] ?? date('Y-m-d'),
                      'no_surat' => $no_surat,
                      'tgl_rencana' => $item['tglRencanaKontrol'] ?? date('Y-m-d'),
                      'kd_dokter_bpjs' => $item['kodeDokter'] ?? '',
                      'nm_dokter_bpjs' => $item['namaDokter'] ?? '',
                      'kd_poli_bpjs' => $item['kodePoliTujuan'] ?? '',
                      'nm_poli_bpjs' => $item['namaPoliTujuan'] ?? '',
                      'diagnosa' => '-',
                      'no_sep' => '-'
                    ]);
                    $synced++;
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
      'message' => $synced > 0 ? "Berhasil sinkron $synced SPRI dari server BPJS!" : "Tidak ada SPRI baru yang ditemukan di server BPJS untuk pasien ini."
    ]);
    exit();
  }

  public function postSaveSPRI($no_kartu, $no_rawat)
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $data = [
      'request' => [
        'noKartu' => $no_kartu,
        'kodeDokter' => $_POST['dokter'] ?? '',
        'poliKontrol' => $_POST['poli'] ?? '',
        'tglRencanaKontrol' => $_POST['tanggal_periksa'] ?? '',
        'user' => $_POST['sep_user']
      ]
    ];

    $data = json_encode($data);

    $url = $this->api_url . 'RencanaKontrol/InsertSPRI';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    if ($data == NULL) {
      echo json_encode(['status' => 'error', 'message' => 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!']);
    } else if (isset($data['metaData']['code']) && $data['metaData']['code'] == 200) {
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      $spri = json_decode($decompress, true);
      $no_spri = isset($spri['noSPRI']) ? $spri['noSPRI'] : (isset($spri['noSuratKontrol']) ? $spri['noSuratKontrol'] : '');

      $nm_dokter = '';
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['dokter'])->oneArray();
      if (!empty($maping_dokter_dpjpvclaim['nm_dokter_bpjs'])) {
        $nm_dokter = $maping_dokter_dpjpvclaim['nm_dokter_bpjs'];
      } elseif (!empty($_POST['nm_dokter'])) {
        $nm_dokter = $_POST['nm_dokter'];
      }

      $nm_poli = '';
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $_POST['poli'])->oneArray();
      if (!empty($maping_poli_bpjs['nm_poli_bpjs'])) {
        $nm_poli = $maping_poli_bpjs['nm_poli_bpjs'];
      } elseif (!empty($_POST['nm_poli'])) {
        $nm_poli = $_POST['nm_poli'];
      }

      $rawat = revertNorawat($no_rawat);
      $cek_rawat = $this->db('reg_periksa')->where('no_rawat', $rawat)->oneArray();
      if (!$cek_rawat) {
        $rawat = null;
      }

      $this->db('bridging_surat_pri_bpjs')->save([
        'no_rawat' => $rawat,
        'no_kartu' => $no_kartu,
        'tgl_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
        'no_surat' => $no_spri,
        'tgl_rencana' => $_POST['tanggal_periksa'] ?? date('Y-m-d'),
        'kd_dokter_bpjs' => $_POST['dokter'] ?? '',
        'nm_dokter_bpjs' => $nm_dokter,
        'kd_poli_bpjs' => $_POST['poli'] ?? '',
        'nm_poli_bpjs' => $nm_poli,
        'diagnosa' => '-',
        'no_sep' => '-'
      ]);

      echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => 'SPRI berhasil diterbitkan dengan nomor: ' . $no_spri,
        'no_surat' => $no_spri
      ]);
    } else {
      $pesan = isset($data['metaData']['message']) ? $data['metaData']['message'] : 'Gagal membuat SPRI di BPJS';
      echo json_encode([
        'status' => 'error',
        'code' => $data['metaData']['code'] ?? 500,
        'message' => $pesan
      ]);
    }
    exit();
  }

  public function postUpdateSPRI($no_surat)
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $data = [
      'request' => [
        'noSPRI' => $no_surat,
        'kodeDokter' => $_POST['dokter'] ?? '',
        'poliKontrol' => $_POST['poli'] ?? '',
        'tglRencanaKontrol' => $_POST['tanggal_periksa'] ?? '',
        'user' => $_POST['sep_user']
      ]
    ];

    $data = json_encode($data);
    $url = $this->api_url . 'RencanaKontrol/UpdateSPRI';
    $output = BpjsService::put($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    if ($data == NULL) {
      echo json_encode(['status' => 'error', 'message' => 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!']);
    } else if (isset($data['metaData']['code']) && $data['metaData']['code'] == 200) {
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      $spri = json_decode($decompress, true);
      $no_spri = isset($spri['noSPRI']) ? $spri['noSPRI'] : $no_surat;

      $nm_dokter = '';
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['dokter'])->oneArray();
      if (!empty($maping_dokter_dpjpvclaim['nm_dokter_bpjs'])) {
        $nm_dokter = $maping_dokter_dpjpvclaim['nm_dokter_bpjs'];
      } elseif (!empty($_POST['nm_dokter'])) {
        $nm_dokter = $_POST['nm_dokter'];
      }

      $nm_poli = '';
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $_POST['poli'])->oneArray();
      if (!empty($maping_poli_bpjs['nm_poli_bpjs'])) {
        $nm_poli = $maping_poli_bpjs['nm_poli_bpjs'];
      } elseif (!empty($_POST['nm_poli'])) {
        $nm_poli = $_POST['nm_poli'];
      }

      $this->db('bridging_surat_pri_bpjs')->where('no_surat', $no_surat)->update([
        'tgl_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
        'no_surat' => $no_spri,
        'tgl_rencana' => $_POST['tanggal_periksa'] ?? date('Y-m-d'),
        'kd_dokter_bpjs' => $_POST['dokter'] ?? '',
        'nm_dokter_bpjs' => $nm_dokter,
        'kd_poli_bpjs' => $_POST['poli'] ?? '',
        'nm_poli_bpjs' => $nm_poli,
      ]);
      echo json_encode(['status' => 'success', 'message' => 'SPRI ' . $no_surat . ' berhasil diubah']);
    } else {
      $pesan = isset($data['metaData']['message']) ? $data['metaData']['message'] : 'Gagal mengubah SPRI';
      echo json_encode(['status' => 'error', 'message' => $pesan]);
    }
    exit();
  }
  
  public function postDeleteSPRI($no_surat)
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $data = [
      'request' => [
        't_suratkontrol' => [
          'noSuratKontrol' => $no_surat,
          'user' => $_POST['sep_user']
        ],
      ]
    ];

    $data = json_encode($data);
    $url = $this->api_url . 'RencanaKontrol/Delete';
    $output = BpjsService::delete($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    if ($data == NULL) {
      echo json_encode(['status' => 'error', 'message' => 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!']);
    } else if (isset($data['metaData']['code']) && $data['metaData']['code'] == 200) {
      $this->db('bridging_surat_pri_bpjs')->where('no_surat', $no_surat)->delete();
      echo json_encode(['status' => 'success', 'message' => 'SPRI ' . $no_surat . ' berhasil dihapus']);
    } else {
      $pesan = isset($data['metaData']['message']) ? $data['metaData']['message'] : 'Gagal menghapus SPRI';
      echo json_encode(['status' => 'error', 'message' => $pesan]);
    }
    exit();
  }

  function getAddAntrian($no_rkm_medis, $no_rawat, $nomor_rujukan,  $jenis_kunjungan) {

    try {
        if (empty($no_rkm_medis) || empty($no_rawat) || empty($nomor_rujukan) || empty($jenis_kunjungan)) {
            throw new \Exception("Parameter tidak lengkap.");
        }

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $tentukan_hari=date('D',strtotime(date('Y-m-d')));

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

        $reg_periksa = $this->db('reg_periksa')->where('no_rawat', revertNoRawat($no_rawat))->oneArray();
        if (!$reg_periksa) {
            throw new \Exception("Data registrasi tidak ditemukan.");
        }

        $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
        $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
        $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();

        if (!$maping_dokter_dpjpvclaim || !$maping_poli_bpjs || !$jadwaldokter) {
            throw new \Exception("Data mapping dokter/poli atau jadwal tidak lengkap.");
        }

        $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
        $minutes = $no_urut_reg * 10;
        $cek_kuota['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));

        $kodebooking = $this->settings->get('settings.ppk_bpjs').''.convertNorawat($reg_periksa['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$reg_periksa['no_reg'];

        $nomorreferensi = $nomor_rujukan;

        $data = [
            'kodebooking' => $kodebooking,
            'jenispasien' => 'JKN',
            'nomorkartu' => $this->core->getPasienInfo('no_peserta', $no_rkm_medis),
            'nik' => $this->core->getPasienInfo('no_ktp', $no_rkm_medis),
            'nohp' => $this->core->getPasienInfo('no_tlp', $no_rkm_medis),
            'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
            'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
            'pasienbaru' => '0',
            'norm' => $no_rkm_medis,
            'tanggalperiksa' => $date,
            'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
            'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
            'jampraktek' => substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5),
            'jeniskunjungan' => $jenis_kunjungan,
            'nomorreferensi' => $nomorreferensi,
            'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
            'angkaantrean' => $reg_periksa['no_reg'],
            'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kuota['jam_mulai']) * 1000,
            'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
            'kuotajkn' => intval($jadwaldokter['kuota']),
            'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
            'kuotanonjkn' => intval($jadwaldokter['kuota']),
            'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
        ];
        $request = json_encode($data, JSON_PRETTY_PRINT);
        $data = json_encode($data);

        $tStamp = strval(time());
        $key = $this->settings->get('jkn_mobile.BpjsConsID') . $this->settings->get('jkn_mobile.BpjsSecretKey') . $tStamp;

        $url = $this->settings->get('jkn_mobile.BpjsAntrianUrl').'antrean/add';
        $output = BpjsService::post($url, $data, $this->settings->get('jkn_mobile.BpjsConsID'), $this->settings->get('jkn_mobile.BpjsSecretKey'), $this->settings->get('jkn_mobile.BpjsUserKey'), NULL);
        $data = json_decode($output, true);
        $response = json_encode($data, JSON_PRETTY_PRINT);
        
        if(isset($data['metadata']['code']) && ($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208)) {
          $this->db('mlite_antrian_referensi')->save([
              'tanggal_periksa' => $date,
              'no_rkm_medis' => $no_rkm_medis,
              'nomor_kartu' => $this->core->getPasienInfo('no_peserta', $no_rkm_medis),
              'nomor_referensi' => $nomorreferensi,
              'kodebooking' => $kodebooking,
              'jenis_kunjungan' => $jenis_kunjungan,
              'status_kirim' => 'Sudah',
              'keterangan' => $data['metadata']['code'].': '.$data['metadata']['message']
          ]);
          $status = 'Antrian telah dikirim';
        } else if ($data == null) {
          // Log null response but consider as sent? Maybe should be 'Gagal'
          $this->db('mlite_antrian_referensi')->save([
            'tanggal_periksa' => $date,
            'no_rkm_medis' => $no_rkm_medis,
            'nomor_kartu' => $this->core->getPasienInfo('no_peserta', $no_rkm_medis),
            'nomor_referensi' => $nomorreferensi,
            'kodebooking' => $kodebooking,
            'jenis_kunjungan' => $jenis_kunjungan,
            'status_kirim' => 'Sudah',
            'keterangan' => 'Response null'
          ]);
          $status = 'Antrian telah dikirim (Response Null)';
        } else {
          $status = 'Gagal kirim antrian: ' . (isset($data['metadata']['message']) ? $data['metadata']['message'] : 'Unknown error');
        }

    } catch (\Exception $e) {
        $status = 'Error: ' . $e->getMessage();
        $request = isset($request) ? $request : '';
        $response = isset($response) ? $response : json_encode(['error' => $e->getMessage()]);
    }

    echo $this->draw('add.antrian.html', ['request' => htmlspecialchars($request, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'response' => htmlspecialchars($response, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'status' => htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')]);
    exit();
  }

  public function getSync_SEP($no_kartu, $no_rawat)
  {
    $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
    $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();
    $bridging_sep = $this->db('bridging_sep')
      ->where('no_kartu', $no_kartu)
      ->toArray();
    $this->tpl->set('bridging_sep', $this->tpl->noParse_array(htmlspecialchars_array($bridging_sep)));
    $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
    $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
    $this->tpl->set('no_kartu', $no_kartu);
    $this->tpl->set('no_rawat', $no_rawat);
    echo $this->draw('form.sepvclaim.html');
    exit();
  }

  public function getAproval_SEP($no_kartu)
  {
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('form.aproval.html');
    exit();
  }

  public function getSyncSepDisplay($no_kartu)
  {
    $bridging_sep = $this->db('bridging_sep')
      ->where('no_kartu', $no_kartu)
      ->toArray();
    $this->tpl->set('bridging_sep', $this->tpl->noParse_array(htmlspecialchars_array($bridging_sep)));
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('sync_sep.display.html');
    exit();
  }

  public function getSepPulang($no_sep)
  {
    $this->tpl->set('no_sep', $no_sep);
    echo $this->draw('sep_pulang.html');
    exit();
  }

  public function getAprovalSepDisplay($bln_aproval, $tahun_aproval)
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . 'Sep/persetujuanSEP/list/bulan/' . $bln_aproval . '/tahun/' . $tahun_aproval;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    
    if ($json && isset($json['metaData'])) {
        $code = $json['metaData']['code'];
        $message = $json['metaData']['message'];
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        }
        echo '{
            "metaData": {
                "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
                "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
            },
            "response": ' . $decompress . '}';
    } else {
        echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function postSaveAprovalSEP()
  {
    $date = date('Y-m-d');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $request = [
      'request' => [
        't_sep' => [
          'noKartu' => $_POST['noKartu'],
          'tglSep' => $_POST['tglSep'],
          'jnsPelayanan' => $_POST['jnsPelayanan'],
          'jnsPengajuan' => $_POST['jnsPengajuan'], 
          'keterangan' => $_POST['keterangan'], 
          'user' => $this->core->getUserInfo('username', null, true)
        ]
      ]
    ];

    header('Content-type: application/x-www-form-urlencoded');
    // echo json_encode($request);
    $request = json_encode($request);

    $url = $this->settings->get('settings.BpjsApiUrl') . 'Sep/pengajuanSEP';
    $consid = $this->settings->get('settings.BpjsConsID');
    $secretkey = $this->settings->get('settings.BpjsSecretKey');
    $userkey = $this->settings->get('settings.BpjsUserKey');
    $output = BpjsService::post($url, $request, $consid, $secretkey, $userkey, $tStamp);
    $data = json_decode($output, true);
    // print_r(htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $code = $data['metaData']['code'];
    $message = $data['metaData']['message'];
    
    if ($data && isset($data['metaData'])) {
        $stringDecrypt = stringDecrypt($key, $data['response']);
        $decompress = '""';
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
        }
        $data = '{
            "metaData": {
              "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
              "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
            },
            "response": ' . $decompress . '}';
        $data = json_decode($data, true);
    } else {
        $code = "5000"; // Default error code
        $data = [
            "metaData" => [
              "code" => "5000",
              "message" => "ERROR"
            ],
            "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
    }
    if ($code == 200 or $code == 201) {
      $request = [
        'request' => [
          't_sep' => [
            'noKartu' => $_POST['noKartu'],
            'tglSep' => $_POST['tglSep'],
            'jnsPelayanan' => $_POST['jnsPelayanan'],
            'jnsPengajuan' => $_POST['jnsPengajuan'], 
            'keterangan' => $_POST['keterangan'], 
            'user' => $this->core->getUserInfo('username', null, true)
          ]
        ]
      ];

      header('Content-type: application/x-www-form-urlencoded');
      $request = json_encode($request);

      $url = $this->settings->get('settings.BpjsApiUrl') . 'Sep/aprovalSEP';
      $consid = $this->settings->get('settings.BpjsConsID');
      $secretkey = $this->settings->get('settings.BpjsSecretKey');
      $userkey = $this->settings->get('settings.BpjsUserKey');
      $output = BpjsService::post($url, $request, $consid, $secretkey, $userkey, $tStamp);
      $data = json_decode($output, true);
      // echo $data;
      print_r(htmlspecialchars($output, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
      
      if ($data && isset($data['metaData'])) {
          $code = $data['metaData']['code'];
          $message = $data['metaData']['message'];
          $stringDecrypt = stringDecrypt($key, $data['response']);
          $decompress = '""';
          if (!empty($stringDecrypt)) {
            $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
          }
          $data = '{
              "metaData": {
                "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
                "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
              },
              "response": ' . $decompress . '}';
          $data = json_decode($data, true);
      } else {
          $data = [
              "metaData" => [
                "code" => "5000",
                "message" => "ERROR"
              ],
              "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
      }
    }
    exit();
  }

  public function postSaveSyncSEP()
  {
    $date = date('Y-m-d');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    header('Content-type: text/html');
    $url = $this->settings->get('settings.BpjsApiUrl') . 'SEP/' . $_POST['no_sep'];
    $consid = $this->settings->get('settings.BpjsConsID');
    $secretkey = $this->settings->get('settings.BpjsSecretKey');
    $userkey = $this->settings->get('settings.BpjsUserKey');
    $output = BpjsService::get($url, NULL, $consid, $secretkey, $userkey, $tStamp);
    $data = json_decode($output, true);
    //print_r($output);
    $code = $data['metaData']['code'];
    $message = $data['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $data['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($data != null) {
      $data = '{
          "metaData": {
            "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          },
          "response": ' . $decompress . '}';
      $data = json_decode($data, true);
    } else {
      $data = [
          "metaData" => [
            "code" => "5000",
            "message" => "ERROR"
          ],
          "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
    }

    $url_rujukan = $this->settings->get('settings.BpjsApiUrl') . 'Rujukan/' . $data['response']['noRujukan'];
    if ($_POST['asal_rujukan'] == 2) {
      $url_rujukan = $this->settings->get('settings.BpjsApiUrl') . 'Rujukan/RS/' . $data['response']['noRujukan'];
    }
    $rujukan = BpjsService::get($url_rujukan, NULL, $consid, $secretkey, $userkey, $tStamp);
    $data_rujukan = json_decode($rujukan, true);
    //print_r($rujukan);

    $code = $data_rujukan['metaData']['code'];
    $message = $data_rujukan['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $data_rujukan['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($data_rujukan != null) {
      $data_rujukan = '{
          "metaData": {
            "code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
            "message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          },
          "response": ' . $decompress . '}';
      $data_rujukan = json_decode($data_rujukan, true);
    } else {
      $data_rujukan = [
          "metaData" => [
            "code" => "5000",
            "message" => "ERROR"
          ],
          "response" => "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."];
    }

    $no_telp = $data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'];
    if (empty($data_rujukan['response']['rujukan']['peserta']['mr']['noTelepon'])) {
      $no_telp = '00000000';
    }

    $jenis_pelayanan = '2';
    if ($data['response']['jnsPelayanan'] == 'Rawat Inap') {
      $jenis_pelayanan = '1';
    }

    if ($data_rujukan['metaData']['code'] == 201) {
      $data_rujukan['response']['rujukan']['tglKunjungan'] = $_POST['tgl_kunjungan'];
      $data_rujukan['response']['rujukan']['provPerujuk']['kode'] = $this->settings->get('settings.ppk_bpjs');
      $data_rujukan['response']['rujukan']['provPerujuk']['nama'] = $this->settings->get('settings.nama_instansi');
      $data_rujukan['response']['rujukan']['diagnosa']['kode'] = $_POST['kd_diagnosa'];
      $data_rujukan['response']['rujukan']['diagnosa']['nama'] = $data['response']['diagnosa'];
      $data_rujukan['response']['rujukan']['pelayanan']['kode'] = $jenis_pelayanan;
    }

    if ($data['metaData']['code'] == 200) {
      if ($data['response']['klsRawat']['klsRawatNaik'] === NULL) {
        $data['response']['klsRawat']['klsRawatNaik'] = '';
      }
      if ($data['response']['klsRawat']['pembiayaan'] === NULL) {
        $data['response']['klsRawat']['pembiayaan'] = '';
      }
      if ($data['response']['klsRawat']['penanggungJawab'] === NULL) {
        $data['response']['klsRawat']['penanggungJawab'] = '';
      }
      $insert = $this->_saveSepLocal([
        'no_sep' => $data['response']['noSep'],
        'no_rawat' => $_POST['no_rawat'],
        'tglsep' => $data['response']['tglSep'],
        'tglrujukan' => $data_rujukan['response']['rujukan']['tglKunjungan'],
        'no_rujukan' => $data['response']['noRujukan'],
        'kdppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['kode'],
        'nmppkrujukan' => $data_rujukan['response']['rujukan']['provPerujuk']['nama'],
        'kdppkpelayanan' => $this->settings->get('settings.ppk_bpjs'),
        'nmppkpelayanan' => $this->settings->get('settings.nama_instansi'),
        'jnspelayanan' => $jenis_pelayanan,
        'catatan' => $data['response']['catatan'],
        'diagawal' => $data_rujukan['response']['rujukan']['diagnosa']['kode'],
        'nmdiagnosaawal' => $data_rujukan['response']['rujukan']['diagnosa']['nama'],
        'kdpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['kd_poli_bpjs'],
        'nmpolitujuan' => $this->db('maping_poli_bpjs')->where('kd_poli_rs', $_POST['kd_poli'])->oneArray()['nm_poli_bpjs'],
        'klsrawat' =>  $data['response']['klsRawat']['klsRawatHak'],
        'klsnaik' => $data['response']['klsRawat']['klsRawatNaik'],
        'pembiayaan' => $data['response']['klsRawat']['pembiayaan'],
        'pjnaikkelas' => $data['response']['klsRawat']['penanggungJawab'],
        'lakalantas' => '0',
        'user' => $this->_getSafeUserDb('bridging_sep'),
        'nomr' => $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']),
        'nama_pasien' => $data['response']['peserta']['nama'],
        'tanggal_lahir' => $data['response']['peserta']['tglLahir'],
        'peserta' => $data['response']['peserta']['jnsPeserta'],
        'jkel' => $data['response']['peserta']['kelamin'],
        'no_kartu' => $data['response']['peserta']['noKartu'],
        'tglpulang' => '1900-01-01 00:00:00',
        'asal_rujukan' => $_POST['asal_rujukan'],
        'eksekutif' => $data['response']['poliEksekutif'],
        'cob' => '0',
        'notelep' => $no_telp,
        'katarak' => '0',
        'tglkkl' => '1900-01-01',
        'keterangankkl' => '-',
        'suplesi' => '0',
        'no_sep_suplesi' => '-',
        'kdprop' => '-',
        'nmprop' => '-',
        'kdkab' => '-',
        'nmkab' => '-',
        'kdkec' => '-',
        'nmkec' => '-',
        'noskdp' => '0',
        'kddpjp' => $_POST['kd_dokter'],
        'nmdpdjp' => $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $_POST['kd_dokter'])->oneArray()['nm_dokter_bpjs'],
        'tujuankunjungan' => '0',
        'flagprosedur' => '',
        'penunjang' => '',
        'asesmenpelayanan' => '',
        'kddpjplayanan' => $data['response']['dpjp']['kdDPJP'],
        'nmdpjplayanan' => $data['response']['dpjp']['nmDPJP']
      ], 'bridging_sep');
    }

    if ($insert) {
      $this->db('bpjs_prb')->save(['no_sep' => $data['response']['noSep'], 'prb' => $data_rujukan['response']['rujukan']['peserta']['informasi']['prolanisPRB']]);
      $this->notify('success', 'Simpan sukes');
    } else {
      $this->notify('failure', 'Simpan gagal');
    }
    exit();
  }

  public function getKontrol($no_kartu)
  {
    $this->_addHeaderFiles();
    $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->toArray();
    $maping_poli_bpjs = $this->db('maping_poli_bpjs')->toArray();

    // Fallback dokter jika mapping kosong
    if (empty($maping_dokter_dpjpvclaim)) {
      $tStamp = strval(time());
      $key = $this->consid . $this->secretkey . $tStamp;
      $url = $this->api_url . 'referensi/dokter/pelayanan/2/tglPelayanan/' . date('Y-m-d') . '/Spesialis/GIG';
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      if (!empty($json['response'])) {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
          $arr = json_decode($decompress, true);
          if (!empty($arr['list']) && is_array($arr['list'])) {
            foreach ($arr['list'] as $d) {
              $maping_dokter_dpjpvclaim[] = [
                'kd_dokter_bpjs' => $d['kode'],
                'nm_dokter_bpjs' => $d['nama']
              ];
            }
          }
        }
      }
    }

    // Fallback poli jika mapping kosong
    if (empty($maping_poli_bpjs)) {
      $tStamp = strval(time());
      $key = $this->consid . $this->secretkey . $tStamp;
      $url = $this->api_url . 'referensi/poli/gigi';
      $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
      $json = json_decode($output, true);
      if (!empty($json['response'])) {
        $stringDecrypt = stringDecrypt($key, $json['response']);
        if (!empty($stringDecrypt)) {
          $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
          $arr = json_decode($decompress, true);
          if (!empty($arr['poli']) && is_array($arr['poli'])) {
            foreach ($arr['poli'] as $p) {
              $maping_poli_bpjs[] = [
                'kd_poli_bpjs' => $p['kode'],
                'nm_poli_bpjs' => $p['nama']
              ];
            }
          }
        }
      }
    }

    // Tentukan no_sep asal
    $no_sep = $_GET['no_sep'] ?? '';
    $histori_sep = [];
    if (empty($no_sep)) {
      $sep = $this->db('bridging_sep')->where('no_kartu', $no_kartu)->desc('tglsep')->oneArray();
      if (!empty($sep['no_sep'])) {
        $no_sep = $sep['no_sep'];
      }
    }

    // Ambil histori SEP pasien dari BPJS (90 hari terakhir)
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $tglMulai = date('Y-m-d', strtotime('-90 days'));
    $tglAkhir = date('Y-m-d');
    $url = $this->api_url . 'monitoring/HistoriPelayanan/NoKartu/' . $no_kartu . '/tglMulai/' . $tglMulai . '/tglAkhir/' . $tglAkhir;
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    if (!empty($json['response'])) {
      $stringDecrypt = stringDecrypt($key, $json['response']);
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
        $arr = json_decode($decompress, true);
        if (!empty($arr['histori']) && is_array($arr['histori'])) {
          $histori_sep = $arr['histori'];
          if (empty($no_sep) && !empty($arr['histori'][0]['noSep'])) {
            $no_sep = $arr['histori'][0]['noSep'];
          }
        }
      }
    }

    $seps = $this->db('bridging_sep')->where('no_kartu', $no_kartu)->toArray();
    $sep_nums = array_column($seps, 'no_sep');
    if (!empty($no_sep) && !in_array($no_sep, $sep_nums)) {
      $sep_nums[] = $no_sep;
    }

    if (!empty($sep_nums)) {
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')
        ->where('no_sep', 'IN', $sep_nums)
        ->toArray();
    } else {
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')->toArray();
    }

    $this->tpl->set('no_sep', htmlspecialchars($no_sep, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $this->tpl->set('histori_sep', $this->tpl->noParse_array(htmlspecialchars_array($histori_sep)));
    $this->tpl->set('kontrol', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_kontrol_bpjs)));
    $this->tpl->set('maping_dokter_dpjpvclaim', $this->tpl->noParse_array(htmlspecialchars_array($maping_dokter_dpjpvclaim)));
    $this->tpl->set('maping_poli_bpjs', $this->tpl->noParse_array(htmlspecialchars_array($maping_poli_bpjs)));
    $this->tpl->set('no_kartu', htmlspecialchars($no_kartu, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    echo $this->draw('kontrol.html');
    exit();
  }

  public function getKontrolDisplay($no_kartu)
  {
    $seps = $this->db('bridging_sep')->where('no_kartu', $no_kartu)->toArray();
    $sep_nums = array_column($seps, 'no_sep');

    if (!empty($sep_nums)) {
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')
        ->where('no_sep', 'IN', $sep_nums)
        ->toArray();
    } else {
      $bridging_surat_kontrol_bpjs = $this->db('bridging_surat_kontrol_bpjs')->toArray();
    }

    $this->tpl->set('kontrol', $this->tpl->noParse_array(htmlspecialchars_array($bridging_surat_kontrol_bpjs)));
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('kontrol.display.html');
    exit();
  }

  public function getSyncKontrol($no_kartu)
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $synced = 0;
    $periods = [
      ['m' => date('m'), 'y' => date('Y')],
      ['m' => date('m', strtotime('-1 month')), 'y' => date('Y', strtotime('-1 month'))],
      ['m' => date('m', strtotime('+1 month')), 'y' => date('Y', strtotime('+1 month'))]
    ];

    foreach ($periods as $period) {
      foreach ([2, 1] as $filter) {
        $url = $this->api_url . 'RencanaKontrol/ListRencanaKontrol/Bulan/' . $period['m'] . '/Tahun/' . $period['y'] . '/Nokartu/' . $no_kartu . '/filter/' . $filter;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        if (!empty($json['metaData']['code']) && $json['metaData']['code'] == 200 && !empty($json['response'])) {
          $stringDecrypt = stringDecrypt($key, $json['response']);
          if (!empty($stringDecrypt)) {
            $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent($stringDecrypt);
            $resp = json_decode($decompress, true);
            if (!empty($resp['list']) && is_array($resp['list'])) {
              foreach ($resp['list'] as $item) {
                $no_surat = $item['noSuratKontrol'] ?? '';
                $no_sep = $item['noSepAsalKontrol'] ?? ($item['noSEP'] ?? '');
                if (!empty($no_surat)) {
                  if (!empty($no_sep)) {
                    $chk = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
                    if (!$chk) {
                      $this->db('bridging_sep')->save([
                        'no_sep' => $no_sep,
                        'no_kartu' => $no_kartu,
                        'tglsep' => $item['tglTerbitKontrol'] ?? date('Y-m-d'),
                        'klsnaik' => '', 'pembiayaan' => '', 'pjnaikkelas' => '', 'asal_rujukan' => '1. Faskes 1', 'eksekutif' => '0. Tidak', 'cob' => '0. Tidak', 'notelep' => '-', 'katarak' => '0. Tidak', 'tglkkl' => '0000-00-00', 'keterangankkl' => '-', 'suplesi' => '0. Tidak', 'no_sep_suplesi' => '-', 'kdprop' => '-', 'nmprop' => '-', 'kdkab' => '-', 'nmkab' => '-', 'kdkec' => '-', 'nmkec' => '-', 'noskdp' => '0', 'kddpjp' => '-', 'nmdpdjp' => '-', 'tujuankunjungan' => '0', 'flagprosedur' => '', 'penunjang' => '', 'asesmenpelayanan' => '', 'kddpjplayanan' => '-', 'nmdpjplayanan' => '-'
                      ]);
                    }
                  } else {
                    $no_sep = null;
                  }

                  $exist = $this->db('bridging_surat_kontrol_bpjs')->where('no_surat', $no_surat)->oneArray();
                  if (!$exist) {
                    $this->db('bridging_surat_kontrol_bpjs')->save([
                      'no_sep' => $no_sep,
                      'tgl_surat' => $item['tglTerbitKontrol'] ?? date('Y-m-d'),
                      'no_surat' => $no_surat,
                      'tgl_rencana' => $item['tglRencanaKontrol'] ?? date('Y-m-d'),
                      'kd_dokter_bpjs' => $item['kodeDokter'] ?? '',
                      'nm_dokter_bpjs' => $item['namaDokter'] ?? '',
                      'kd_poli_bpjs' => $item['kodePoliTujuan'] ?? '',
                      'nm_poli_bpjs' => $item['namaPoliTujuan'] ?? ''
                    ]);
                    $synced++;
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
      'message' => $synced > 0 ? "Berhasil sinkron $synced Surat Kontrol dari server BPJS!" : "Tidak ada Surat Kontrol baru yang ditemukan di server BPJS untuk pasien ini."
    ]);
    exit();
  }

  public function postSaveKontrol()
  {
    header('Content-Type: application/json');
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);

    $no_sep = trim($_POST['no_sep'] ?? '');
    $no_surkon = trim($_POST['no_surkon'] ?? '');
    $dokter = trim($_POST['dokter'] ?? '');
    $poli = trim($_POST['poli'] ?? '');
    $tgl_rencana = trim($_POST['tanggal_periksa'] ?? date('Y-m-d'));

    if (empty($no_sep)) {
      echo json_encode(['status' => 'error', 'message' => 'Nomor SEP asal wajib diisi!']);
      exit();
    }
    if (empty($dokter)) {
      echo json_encode(['status' => 'error', 'message' => 'Dokter DPJP wajib dipilih!']);
      exit();
    }
    if (empty($poli)) {
      echo json_encode(['status' => 'error', 'message' => 'Poli tujuan kontrol wajib dipilih!']);
      exit();
    }

    if ($no_surkon == '') {
      $data = [
        'request' => [
          'noSEP' => $no_sep,
          'kodeDokter' => $dokter,
          'poliKontrol' => $poli,
          'tglRencanaKontrol' => $tgl_rencana,
          'user' => $_POST['sep_user']
        ]
      ];
      $statusUrl = 'insert';
      $method = 'post';
    } else {
      $data = [
        'request' => [
          'noSuratKontrol' => $no_surkon,
          'noSEP' => $no_sep,
          'kodeDokter' => $dokter,
          'poliKontrol' => $poli,
          'tglRencanaKontrol' => $tgl_rencana,
          'user' => $_POST['sep_user']
        ]
      ];
      $statusUrl = 'Update';
      $method = 'put';
    }

    $data = json_encode($data);

    $url = $this->api_url . 'RencanaKontrol/' . $statusUrl;
    $output = BpjsService::$method($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);

    if ($data == NULL) {
      echo json_encode(['status' => 'error', 'message' => 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!']);
    } else if (isset($data['metaData']['code']) && $data['metaData']['code'] == 200) {
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      if (!empty($stringDecrypt)) {
        $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      }
      $spri = json_decode($decompress, true);
      $no_surat = !empty($spri['noSuratKontrol']) ? $spri['noSuratKontrol'] : (!empty($no_surkon) ? $no_surkon : '');

      $nm_dokter = '';
      $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $dokter)->oneArray();
      if (!empty($maping_dokter_dpjpvclaim['nm_dokter_bpjs'])) {
        $nm_dokter = $maping_dokter_dpjpvclaim['nm_dokter_bpjs'];
      } elseif (!empty($_POST['nm_dokter'])) {
        $nm_dokter = $_POST['nm_dokter'];
      }

      $nm_poli = '';
      $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $poli)->oneArray();
      if (!empty($maping_poli_bpjs['nm_poli_bpjs'])) {
        $nm_poli = $maping_poli_bpjs['nm_poli_bpjs'];
      } elseif (!empty($_POST['nm_poli'])) {
        $nm_poli = $_POST['nm_poli'];
      }

      // Pastikan bridging_sep memiliki no_sep agar constraint foreign key tidak gagal
      $chk_sep = $this->db('bridging_sep')->where('no_sep', $no_sep)->oneArray();
      if (!$chk_sep) {
        $this->db('bridging_sep')->save([
          'no_sep' => $no_sep,
          'no_kartu' => $_POST['no_kartu'] ?? '',
          'tglsep' => date('Y-m-d'),
          'klsnaik' => '', 'pembiayaan' => '', 'pjnaikkelas' => '', 'asal_rujukan' => '1. Faskes 1', 'eksekutif' => '0. Tidak', 'cob' => '0. Tidak', 'notelep' => '-', 'katarak' => '0. Tidak', 'tglkkl' => '0000-00-00', 'keterangankkl' => '-', 'suplesi' => '0. Tidak', 'no_sep_suplesi' => '-', 'kdprop' => '-', 'nmprop' => '-', 'kdkab' => '-', 'nmkab' => '-', 'kdkec' => '-', 'nmkec' => '-', 'noskdp' => '0', 'kddpjp' => '-', 'nmdpdjp' => '-', 'tujuankunjungan' => '0', 'flagprosedur' => '', 'penunjang' => '', 'asesmenpelayanan' => '', 'kddpjplayanan' => '-', 'nmdpjplayanan' => '-'
        ]);
      }

      if ($no_surkon == '') {
        $this->db('bridging_surat_kontrol_bpjs')->save([
          'no_sep' => $no_sep,
          'tgl_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
          'no_surat' => $no_surat,
          'tgl_rencana' => $tgl_rencana,
          'kd_dokter_bpjs' => $dokter,
          'nm_dokter_bpjs' => $nm_dokter,
          'kd_poli_bpjs' => $poli,
          'nm_poli_bpjs' => $nm_poli
        ]);
      } else {
        $this->db('bridging_surat_kontrol_bpjs')->where('no_surat', $no_surkon)->update([
          'no_sep' => $no_sep,
          'tgl_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
          'no_surat' => $no_surat,
          'tgl_rencana' => $tgl_rencana,
          'kd_dokter_bpjs' => $dokter,
          'nm_dokter_bpjs' => $nm_dokter,
          'kd_poli_bpjs' => $poli,
          'nm_poli_bpjs' => $nm_poli
        ]);
      }

      // Opsional: simpan skdp_bpjs jika no_rkm_medis ada
      if (!empty($_POST['no_rkm_medis'])) {
        $this->db('skdp_bpjs')->save([
          'tahun' => date('Y'),
          'no_rkm_medis' => $_POST['no_rkm_medis'],
          'diagnosa' => $_POST['diagnosa'] ?? '-',
          'terapi' => $_POST['terapi'] ?? '-',
          'alasan1' => $_POST['alasan1'] ?? '-',
          'alasan2' => '',
          'rtl1' => $_POST['rtl1'] ?? '-',
          'rtl2' => '',
          'tanggal_datang' => $tgl_rencana,
          'tanggal_rujukan' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
          'no_antrian' => $this->core->setNoSKDP(),
          'kd_dokter' => !empty($_POST['no_rawat']) ? $this->core->getRegPeriksaInfo('kd_dokter', $_POST['no_rawat']) : '',
          'status' => 'Menunggu'
        ]);
      }

      echo json_encode([
        'status' => 'success',
        'code' => 200,
        'message' => ($no_surkon == '' ? 'Surat Kontrol berhasil dibuat' : 'Surat Kontrol berhasil diubah') . ' dengan nomor: ' . $no_surat,
        'no_surat' => $no_surat
      ]);
    } else {
      $pesan = $data['metaData']['message'] ?? 'Gagal memproses Surat Kontrol di BPJS';
      echo json_encode([
        'status' => 'error',
        'code' => $data['metaData']['code'] ?? 500,
        'message' => $pesan
      ]);
    }
    exit();
  }

  public function getRujukKeluar($no_kartu)
  {
    $this->_addHeaderFiles();
    $rujuk_keluar = $this->db('bridging_rujukan_bpjs')
      ->join('bridging_sep', 'bridging_sep.no_sep=bridging_rujukan_bpjs.no_sep')
      ->where('bridging_sep.no_kartu', $no_kartu)
      ->toArray();
    $this->tpl->set('rujuk_keluar', $this->tpl->noParse_array(htmlspecialchars_array($rujuk_keluar)));
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('rujukkeluar.html');
    exit();
  }

  public function getRujukKeluarDisplay($no_kartu)
  {
    $rujuk_keluar = $this->db('bridging_rujukan_bpjs')
      ->join('bridging_sep', 'bridging_sep.no_sep=bridging_rujukan_bpjs.no_sep')
      ->where('bridging_sep.no_kartu', $no_kartu)
      ->toArray();
    $this->tpl->set('rujuk_keluar', $this->tpl->noParse_array(htmlspecialchars_array($rujuk_keluar)));
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('rujukkeluar.display.html');
    exit();
  }

  public function postSaveRujukKeluar()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);
    $data = [
      'request' => [
        't_rujukan' => [
          'noSep' => $_POST['no_sep'],
          'tglRujukan' => $_POST['tanggal_surat'],
          'ppkDirujuk' => $_POST['sep_asal_rujukan_kode_rujuk'],
          'tglRencanaKunjungan' => $_POST['tanggal_periksa'],
          'jnsPelayanan' => $_POST['jns_rujuk'],
          'catatan' => $_POST['catatan'],
          'diagRujukan' => $_POST['sep_diagnosa_kode_rujuk'],
          'tipeRujukan' => substr($_POST['tipe_rujuk'], 0, 1),
          'poliRujukan' => $_POST['sep_spesialis_kode_rujuk'],
          'user' => $_POST['sep_user']
        ]
      ]
    ];
    $method = 'post';


    $data = json_encode($data);

    $url = $this->api_url . 'Rujukan/2.0/insert';
    $output = BpjsService::$method($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    // echo $data;
    //echo $data['metaData']['message'];
    if ($data == NULL) {
      echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
    } else if ($data['metaData']['code'] == 200) {
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      $spri = json_decode($decompress, true);
      // echo $spri['rujukan']['noRujukan'];

      $bridging_surat_pri_bpjs = $this->db('bridging_rujukan_bpjs')->save([
        'no_sep' => $_POST['no_sep'],
        'tglRujukan' => $_POST['tanggal_surat'],
        'no_rujukan' => $spri['rujukan']['noRujukan'],
        'tglRencanaKunjungan' => $_POST['tanggal_periksa'],
        'ppkDirujuk' => $_POST['sep_asal_rujukan_kode_rujuk'],
        'nm_ppkDirujuk' => $_POST['sep_asal_rujukan_nama_rujuk'],
        'jnsPelayanan' => $_POST['jns_rujuk'],
        'diagRujukan' => $_POST['sep_diagnosa_kode_rujuk'],
        'nama_diagRujukan' => $_POST['sep_diagnosa_nama_rujuk'],
        'tipeRujukan' => $_POST['tanggal_periksa'],
        'poliRujukan' => $_POST['sep_spesialis_kode_rujuk'],
        'nama_poliRujukan' => $_POST['sep_spesialis_nama_rujuk'],
        'user' => $_POST['sep_user'],
        'catatan' => $_POST['catatan'],
      ]);
      if ($bridging_surat_pri_bpjs) {
        echo 'Sukses Simpan';
      }
    } else {
      echo htmlspecialchars($data['metaData']['code'] . ' ' . $data['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getSrb($no_kartu, $no_rawat)
  {
    $this->_addHeaderFiles();
    $no_rawat = revertNorawat($no_rawat);
    $rujuk_keluar = $this->db('bridging_sep')
      ->where('bridging_sep.no_rawat', $no_rawat)
      ->oneArray();
    $reg = $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat);
    $alamat = $this->core->getPasienInfo('alamat', $reg);
    $this->tpl->set('no_kartu', $no_kartu);
    $this->tpl->set('alamat', $alamat);
    $this->tpl->set('no_sep', isset($rujuk_keluar['no_sep']) ? $rujuk_keluar['no_sep'] : '');
    $this->tpl->set('kddpjp', isset($rujuk_keluar['kddpjp']) ? $rujuk_keluar['kddpjp'] : '');
    $this->tpl->set('nmdpjp', isset($rujuk_keluar['nmdpdjp']) ? $rujuk_keluar['nmdpdjp'] : '');
    echo $this->draw('srb.html');
    exit();
  }

  public function getSrbDisplay($no_kartu)
  {
    $rujuk_keluar = $this->db('bridging_srb_bpjs')
      ->join('bridging_sep', 'bridging_sep.no_sep=bridging_srb_bpjs.no_sep')
      ->where('bridging_sep.no_kartu', $no_kartu)
      ->toArray();
    $this->tpl->set('rujuk_keluar', $this->tpl->noParse_array(htmlspecialchars_array($rujuk_keluar)));
    $this->tpl->set('no_kartu', $no_kartu);
    echo $this->draw('srb.display.html');
    exit();
  }

  public function postSaveSrb()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;
    $_POST['sep_user']  = $this->core->getUserInfo('fullname', null, true);
    $data = [
      'request' => [
        't_prb' => [
          'noSep' => $_POST['no_sep'],
          'noKartu' => $_POST['no_peserta'],
          'alamat' => $_POST['alamat'],
          'email' => $_POST['email'],
          'programPRB' => $_POST['sep_diagnosa_kode_srb'],
          'kodeDPJP' => $_POST['dpjp'],
          'keterangan' => $_POST['keterangan'],
          'saran' => $_POST['saran'],
          'user' => '123123123',
          'obat' => [
            [
              'kdObat' => $_POST['sep_kode_obat'],
              'signa1' => $_POST['signa1'],
              'signa2' => $_POST['signa1'],
              'jmlObat' => $_POST['jumlah'],
            ],
          ]
        ]
      ]
    ];
    $method = 'post';


    $data = json_encode($data);
    echo htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $url = $this->api_url . 'PRB/insert';
    $output = BpjsService::$method($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $data = json_decode($output, true);
    // echo $data['metaData']['message'];
    if ($data == NULL) {
      echo 'Koneksi ke server BPJS terputus. Silahkan ulangi beberapa saat lagi!';
    } else if ($data['metaData']['code'] == 200) {
      $stringDecrypt = stringDecrypt($key, $data['response']);
      $decompress = '""';
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
      $spri = json_decode($decompress, true);
      echo htmlspecialchars($spri['noSRB'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

      $bridging_srb_bpjs = $this->db('bridging_srb_bpjs')->save([
        'no_sep' => $_POST['no_sep'],
        'no_srb' => $spri['noSRB'],
        'tgl_srb' => $_POST['tanggal_srb'],
        'alamat' => $_POST['alamat'],
        'email' => $_POST['email'],
        'kodeprogram' => $_POST['sep_diagnosa_kode_srb'],
        'namaprogram' => $_POST['sep_diagnosa_nama_srb'],
        'kodedpjp' => $_POST['dpjp'],
        'nmdpjp' => $_POST['nm_dpjp'],
        'user' => $_POST['sep_user'],
        'keterangan' => $_POST['keterangan'],
        'saran' => $_POST['saran'],
      ]);
      $bridging_srb_bpjs_obat = $this->db('bridging_srb_bpjs_obat')->save([
        'no_sep' => $_POST['no_sep'],
        'no_srb' => $spri['noSRB'],
        'kd_obat' => $_POST['sep_kode_obat'],
        'nm_obat' => $_POST['sep_nama_obat'],
        'jumlah' => $_POST['jumlah'],
        'signa1' => $_POST['signa1'],
        'signa2' => $_POST['signa2'],
      ]);
    } else {
      echo htmlspecialchars($data['metaData']['code'] . ' ' . $data['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDataKontrol($no_kartu,$tanggal)
  {
    $tahun = substr($tanggal,0,4);
    $bulan = substr($tanggal,5,2);
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $url = $this->api_url . '/RencanaKontrol/ListRencanaKontrol/Bulan/'.$bulan.'/Tahun/'.$tahun.'/Nokartu/'.$no_kartu.'/filter/2';
    $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    //echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      echo '{
          	"metaData": {
          		"code": "' . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",
          		"message": "' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"
          	},
          	"response": ' . $decompress . '}';
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDirujuk($no_rawat)
  {
    $bridging_sep = $this->db('bridging_sep')->where('no_rawat', revertNoRawat($no_rawat))->oneArray();
    $dirujuk = $this->db('bridging_rujukan_bpjs')->where('no_sep', $bridging_sep['no_sep'])->toArray();
    echo $this->draw('dirujuk.html', ['bridging_sep' => htmlspecialchars_array(isset_or($bridging_sep, [])), 'dirujuk' => htmlspecialchars_array($dirujuk)]);
    exit();
  }

  public function postDiRujukSave()
  {
    $tStamp = strval(time());
    $key = $this->consid . $this->secretkey . $tStamp;

    $data = [
      'request' => [
        't_rujukan' => [
          "noSep" => $_POST['noSep'],
          "tglRujukan" => $_POST['tglRujukan'],
          "tglRencanaKunjungan" => $_POST['tglRencanaKunjungan'],
          "ppkDirujuk" => $_POST['ppkDirujuk'],
          "jnsPelayanan" => $_POST['jnsPelayanan'],
          "catatan" => $_POST['catatan'],
          "diagRujukan" => $_POST['diagRujukan'],
          "tipeRujukan" => $_POST['tipeRujukan'],
          "poliRujukan" => $_POST['poliRujukan'],
          "user" => $this->core->getUserInfo('fullname', null, true)
        ]
      ]
    ];

    $url = $this->api_url . 'Rujukan/2.0/insert';
    $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
    $json = json_decode($output, true);
    echo json_encode(htmlspecialchars_array($json));
    $code = $json['metaData']['code'];
    $message = $json['metaData']['message'];
    $stringDecrypt = stringDecrypt($key, $json['response']);
    $decompress = '""';
    if (!empty($stringDecrypt)) {
      $decompress = \LZCompressor\LZString::decompressFromEncodedURIComponent(($stringDecrypt));
    }
    if ($json != null) {
      $response_data_rujukan = json_decode($decompress, true);

      if($code == '200') {

        $this->db('bridging_rujukan_bpjs')
        ->save([
          'no_sep' => $_POST['noSep'],
          'tglRujukan' => $_POST['tglRujukan'], 
          'tglRencanaKunjungan' => $_POST['tglRencanaKunjungan'], 
          'ppkDirujuk' => $_POST['ppkDirujuk'], 
          'nm_ppkDirujuk' => $response_data_rujukan['rujukan']['tujuanRujukan']['nama'], 
          'jnsPelayanan' => $_POST['jnsPelayanan'], 
          'catatan' => $_POST['catatan'], 
          'diagRujukan' => $_POST['diagRujukan'], 
          'nama_diagRujukan' => $response_data_rujukan['rujukan']['diagnosa']['nama'], 
          'tipeRujukan' => $_POST['tipeRujukan'], 
          'poliRujukan' => $_POST['poliRujukan'], 
          'nama_poliRujukan' => $response_data_rujukan['rujukan']['poliTujuan']['nama'], 
          'no_rujukan' => $response_data_rujukan['rujukan']['noRujukan'], 
          'user' => $this->core->getUserInfo('fullname', null, true)
        ]);

      }
    } else {
      echo htmlspecialchars($json['metaData']['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    exit();
  }

  public function getDirujukTampil($noSep)
  {
    $bridging_rujukan_bpjs = $this->db('bridging_rujukan_bpjs')->where('no_sep', $noSep)->toArray();
    echo $this->draw('dirujuk.tampil.html', ['dirujuk' => htmlspecialchars_array($bridging_rujukan_bpjs)]);
    exit();
  }

  public function getMappingPoli()
  {
      $this->_addHeaderFiles();
      $poliklinik = $this->db('poliklinik')->where('status','1')->toArray();
      return $this->draw('mappingpoli.html', ['row' => htmlspecialchars_array($this->db('maping_poli_bpjs')->toArray()), 'poliklinik' => htmlspecialchars_array($poliklinik)]);
  }

  public function postPoliklinik_Save()
  {

      $location = url([ADMIN, 'vclaim', 'mappingpoli']);

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
      redirect(url([ADMIN, 'vclaim', 'mappingpoli']));
  }

  public function getMappingDokter()
  {
      $this->_addHeaderFiles();
      $dokter = $this->db('dokter')->where('status','1')->toArray();
      return $this->draw('mappingdokter.html', ['row' => htmlspecialchars_array($this->db('maping_dokter_dpjpvclaim')->toArray()), 'dokter' => htmlspecialchars_array($dokter)]);
  }

  public function postDokter_Save()
  {

      $location = url([ADMIN, 'vclaim', 'mappingdokter']);

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
      redirect(url([ADMIN, 'vclaim', 'mappingdokter']));
  }

  private function _addHeaderFiles()
  {
    $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
    $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
    $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
  }

}

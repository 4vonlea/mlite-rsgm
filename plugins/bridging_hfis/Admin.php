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
        ];
    }

    public function getManage()
    {
        $this->_addHeaderFiles();
        $sub_modules = [
            ['name' => 'Bridging Update HFIS', 'url' => url([ADMIN, 'bridging_hfis', 'updatehfis']), 'icon' => 'calendar-check-o', 'desc' => 'Kirim / Sinkronisasi Jadwal Dokter SIMRS ke BPJS HFIS'],
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

        $firstPoli = $this->db('maping_poli_bpjs')->oneArray();
        $kdPoli = $firstPoli ? $firstPoli['kd_poli_bpjs'] : 'GIG';
        $tanggal = date('Y-m-d');

        $url = $this->bpjsurl . 'jadwaldokter/kodepoli/' . $kdPoli . '/tanggal/' . $tanggal;
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
                foreach ($jadwal_input as $value) {
                    $dokterrs = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $kd_dokter)->oneArray();
                    if (!$dokterrs) continue;

                    $jenis = substr($value['hari'], strpos($value['hari'], "|") + 1);
                    if ($jenis == '') {
                        $jenis = 'pagi';
                    }

                    // Fallback to direct mapping if clinic name does not end in 'pagi' / 'sore'
                    $polirs = $this->db('maping_poli_bpjs')
                        ->join('poliklinik', 'maping_poli_bpjs.kd_poli_rs = poliklinik.kd_poli')
                        ->where('maping_poli_bpjs.kd_poli_bpjs', $kd_poli)
                        ->like('poliklinik.nm_poli', '%' . $jenis)
                        ->oneArray();

                    if (!$polirs) {
                        $polirs = $this->db('maping_poli_bpjs')
                            ->where('kd_poli_bpjs', $kd_poli)
                            ->oneArray();
                    }

                    $kd_poli_rs = $polirs['kd_poli'] ?? $polirs['kd_poli_rs'] ?? null;
                    if (!$kd_poli_rs) continue;

                    $hari = strtok($value['hari'], '|');
                    $day = array(
                        '0' => 'AKHAD',
                        '1' => 'SENIN',
                        '2' => 'SELASA',
                        '3' => 'RABU',
                        '4' => 'KAMIS',
                        '5' => 'JUMAT',
                        '6' => 'SABTU'
                    );
                    $namahari = $day[$hari] ?? 'SENIN';

                    if ($value['buka'] == '' && $value['tutup'] == '') {
                        $checkDulu = $this->db('jadwal')
                            ->where('kd_dokter', $dokterrs['kd_dokter'])
                            ->where('hari_kerja', $namahari)
                            ->where('kd_poli', $kd_poli_rs)
                            ->oneArray();
                        if ($checkDulu) {
                            $this->db('jadwal')
                                ->where('kd_dokter', $dokterrs['kd_dokter'])
                                ->where('hari_kerja', $namahari)
                                ->where('kd_poli', $kd_poli_rs)
                                ->delete();
                        }
                    } else {
                        $checkJadwal = $this->db('jadwal')
                            ->where('kd_dokter', $dokterrs['kd_dokter'])
                            ->where('hari_kerja', $namahari)
                            ->where('kd_poli', $kd_poli_rs)
                            ->oneArray();
                        if ($checkJadwal) {
                            $this->db('jadwal')
                                ->where('kd_dokter', $dokterrs['kd_dokter'])
                                ->where('hari_kerja', $namahari)
                                ->where('kd_poli', $kd_poli_rs)
                                ->update([
                                    'jam_mulai' => $value['buka'],
                                    'jam_selesai' => $value['tutup'],
                                    'kuota' => intval($value['kuota'] ?? 0),
                                ]);
                        } else {
                            $this->db('jadwal')->save([
                                'kd_dokter' => $dokterrs['kd_dokter'],
                                'hari_kerja' => $namahari,
                                'kd_poli' => $kd_poli_rs,
                                'jam_mulai' => $value['buka'],
                                'jam_selesai' => $value['tutup'],
                                'kuota' => intval($value['kuota'] ?? 0),
                            ]);
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
        }

        echo json_encode([
            'status' => ($code == '200') ? 'success' : 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data
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

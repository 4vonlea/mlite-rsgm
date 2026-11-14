<?php

namespace Plugins\Anjungan;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public $assign = [];
    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Display' => 'index',
            'Pemanggil' => 'pemanggil',
            'Pengaturan' => 'settings',
        ];
    }

    public function getManage()
    {
      $sub_modules = [
        ['name' => 'Display', 'url' => url([ADMIN, 'anjungan', 'index']), 'icon' => 'desktop', 'desc' => 'Display-Display Informasi Anjungan'],
        ['name' => 'Pemanggil', 'url' => url([ADMIN, 'anjungan', 'pemanggil']), 'icon' => 'bullhorn', 'desc' => 'Pemanggil Antrian'],
        ['name' => 'Pengaturan', 'url' => url([ADMIN, 'anjungan', 'settings']), 'icon' => 'gear', 'desc' => 'Pengaturan Anjungan'],
      ];
      return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules)]);
    }

    public function getIndex()
    {
        return $this->draw('index.html');
    }

    public function getPemanggil()
    {
      $title = 'Display Antrian Loket';
      $logo  = $this->settings->get('settings.logo');
      $display = '';

      $_username = '';
      $__username = 'Tamu';
      if(isset($_SESSION['mlite_user'])) {
        $_username = $this->core->getUserInfo('fullname', null, true);
        $__username = $this->core->getUserInfo('username');
      }
      $tanggal       = getDayIndonesia(date('Y-m-d')).', '.dateIndonesia(date('Y-m-d'));
      $username      = !empty($_username) ? $_username : $__username;

      $show = isset($_GET['show']) ? $_GET['show'] : "";
      switch($show){
        default:
        break;
        case "panggil_loket":
          $display = 'Panggil Loket';

          $_username = '';
          $__username = 'Tamu';
          if(isset($_SESSION['mlite_user'])) {
            $_username = $this->core->getUserInfo('fullname', null, true);
            $__username = $this->core->getUserInfo('username');
          }
          $tanggal       = getDayIndonesia(date('Y-m-d')).', '.dateIndonesia(date('Y-m-d'));
          $username      = !empty($_username) ? $_username : $__username;

          $setting_antrian_loket = str_replace(",","','", $this->settings->get('anjungan.antrian_loket'));
          $loket = explode(",", $this->settings->get('anjungan.antrian_loket'));
          $namaloket = 'a';
          $panggil_loket = 'panggil_loket';
          $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('postdate', date('Y-m-d'))->desc('kd')->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['noantrian'])) {
            $noantrian = intval($get_antrian['noantrian']);
          }

          $antriloket = $this->settings->get('anjungan.panggil_loket_nomor');
          $tcounter = !empty($antriloket) ? intval($antriloket) : 1;
          if($tcounter <= 0) {
            $tcounter = 1;
          }
          $_tcounter = $tcounter + 1;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $tcounter = $_tcounter;
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Loket',
                    'noantrian' => $tcounter,
                    'loket' => $loketVal,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_loket&loket='.$loketVal));
          }
          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Loket',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_loket&loket='.$jumpLoket));
          }
          if(isset($_GET['no_rkm_medis'])) {
            $this->db('mlite_antrian_loket')->where('noantrian', $_GET['noantrian'])->where('postdate', date('Y-m-d'))->save(['no_rkm_medis' => $_GET['no_rkm_medis']]);
          }
          $hitung_antrian = $this->db('mlite_antrian_loket')
            ->where('type', 'Loket')
            ->like('postdate', date('Y-m-d'))
            ->toArray();
          $counter = strlen($tcounter);
          $xcounter = [];
          for($i=0;$i<$counter;$i++){
            $xcounter[] = '<audio id="suarabel'.$i.'" src="{?=url()?}/plugins/anjungan/suara/'.substr($tcounter,$i,1).'.wav" ></audio>';
          };

        break;
        case "panggil_cs":
          $display = 'Panggil CS';
          $loket = explode(",", $this->settings->get('anjungan.antrian_cs'));
          $namaloket = 'b';
          $panggil_loket = 'panggil_cs';
          $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'CS')->where('postdate', date('Y-m-d'))->desc('kd')->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['noantrian'])) {
            $noantrian = intval($get_antrian['noantrian']);
          }

          $antriloket = $this->settings->get('anjungan.panggil_cs_nomor');
          $tcounter = !empty($antriloket) ? intval($antriloket) : 1;
          if($tcounter <= 0) {
            $tcounter = 1;
          }
          $_tcounter = $tcounter + 1;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $tcounter = $_tcounter;
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'CS',
                    'noantrian' => $tcounter,
                    'loket' => $loketVal,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_cs&loket='.$loketVal));
          }

          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'CS',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_cs&loket='.$jumpLoket));
          }

          $hitung_antrian = $this->db('mlite_antrian_loket')
            ->where('type', 'CS')
            ->like('postdate', date('Y-m-d'))
            ->toArray();
          $counter = strlen($tcounter);
          $xcounter = [];
          for($i=0;$i<$counter;$i++){
            $xcounter[] = '<audio id="suarabel'.$i.'" src="{?=url()?}/plugins/anjungan/suara/'.substr($tcounter,$i,1).'.wav" ></audio>';
          };

        break;
        case "panggil_apotek":
          $display = 'Panggil Apotek';
          $loket = explode(",", $this->settings->get('anjungan.antrian_apotek'));
          $namaloket = 'f';
          $panggil_loket = 'panggil_apotek';
          $get_antrian = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Apotek')->where('postdate', date('Y-m-d'))->desc('kd')->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['noantrian'])) {
            $noantrian = intval($get_antrian['noantrian']);
          }

          $antriloket = $this->settings->get('anjungan.panggil_apotek_nomor');
          $tcounter = !empty($antriloket) ? intval($antriloket) : 1;
          if($tcounter <= 0) {
            $tcounter = 1;
          }
          $_tcounter = $tcounter + 1;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $tcounter = $_tcounter;
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Apotek',
                    'noantrian' => $tcounter,
                    'loket' => $loketVal,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_apotek&loket='.$loketVal));
          }

          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', date('Y-m-d'))
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_nomor')->save(['value' => $tcounter]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', date('Y-m-d'))->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Apotek',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => date('Y-m-d'),
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_apotek&loket='.$jumpLoket));
          }
          $hitung_antrian = $this->db('mlite_antrian_loket')
            ->where('type', 'Apotek')
            ->like('postdate', date('Y-m-d'))
            ->toArray();
          $counter = strlen($tcounter);
          $xcounter = [];
          for($i=0;$i<$counter;$i++){
            $xcounter[] = '<audio id="suarabel'.$i.'" src="{?=url()?}/plugins/anjungan/suara/'.substr($tcounter,$i,1).'.wav" ></audio>';
          };

        break;
      }

      return $this->draw('pemanggil.html', [
        'title' => $title,
        'logo' => $logo,
        'powered' => 'Powered by <a href="https://mlite.id/">mLITE</a>',
        'username' => $username,
        'tanggal' => $tanggal,
        'show' => isset_or($show),
        'loket' => isset_or($loket),
        'namaloket' => isset_or($namaloket),
        'panggil_loket' => isset_or($panggil_loket),
        'antrian' => isset_or($tcounter),
        'hitung_antrian' => isset_or($hitung_antrian),
        'xcounter' => isset_or($xcounter),
        'noantrian' =>isset_or($noantrian),
        'display' => $display
      ]);
    }

    public function getSettings()
    {
        if ($this->core->getUserInfo('role') != 'admin') {
            $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
            redirect(url([ADMIN, 'anjungan', 'index']));
        }
        $this->assign['title'] = 'Pengaturan Modul Anjungan';
        $this->assign['poliklinik'] = $this->_getPoliklinik($this->settings->get('anjungan.display_poli'));
        $this->assign['penjab'] = $this->_getPenjab($this->settings->get('anjungan.carabayar'));

        $this->assign['anjungan'] = htmlspecialchars_array($this->settings('anjungan'));
        return $this->draw('settings.html', ['settings' => htmlspecialchars_array($this->assign)]);
    }

    public function postSaveSettings()
    {
        if ($this->core->getUserInfo('role') != 'admin') {
            $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
            redirect(url([ADMIN, 'anjungan', 'index']));
            exit;
        }

        $anjungan = $_POST['anjungan'] ?? [];

        // Handle multiple select fields safely (avoid PHP 8+ TypeError on null)
        if (isset($anjungan['display_poli']) && is_array($anjungan['display_poli'])) {
            $anjungan['display_poli'] = implode(',', array_filter($anjungan['display_poli']));
        } else {
            $anjungan['display_poli'] = is_string($anjungan['display_poli'] ?? '') ? $anjungan['display_poli'] : '';
        }

        if (isset($anjungan['carabayar']) && is_array($anjungan['carabayar'])) {
            $anjungan['carabayar'] = implode(',', array_filter($anjungan['carabayar']));
        } else {
            $anjungan['carabayar'] = is_string($anjungan['carabayar'] ?? '') ? $anjungan['carabayar'] : '';
        }

        foreach ($anjungan as $key => $val) {
            $val = is_array($val) ? implode(',', $val) : (string)($val ?? '');
            $cek = $this->db('mlite_settings')->where('module', 'anjungan')->where('field', $key)->oneArray();
            if ($cek) {
                $this->db('mlite_settings')->where('module', 'anjungan')->where('field', $key)->save(['value' => $val]);
            } else {
                $this->db('mlite_settings')->save([
                    'module' => 'anjungan',
                    'field' => $key,
                    'value' => $val
                ]);
            }
            $this->settings->set('anjungan', $key, $val);
        }

        $this->notify('success', 'Pengaturan Anjungan berhasil disimpan');
        redirect(url([ADMIN, 'anjungan', 'settings']));
        exit;
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

    private function _getPenjab($kd_pj = null)
    {
        $result = [];
        $rows = $this->db('penjab')->where('status', '1')->toArray();

        if (!$kd_pj) {
            $kd_pjArray = [];
        } else {
            $kd_pjArray = explode(',', $kd_pj);
        }

        foreach ($rows as $row) {
            if (empty($kd_pjArray)) {
                $attr = '';
            } else {
                if (in_array($row['kd_pj'], $kd_pjArray)) {
                    $attr = 'selected';
                } else {
                    $attr = '';
                }
            }
            $result[] = ['kd_pj' => $row['kd_pj'], 'png_jawab' => $row['png_jawab'], 'attr' => $attr];
        }
        return $result;
    }

    public function getResetAnjunganLoket(){
      date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
      $date = date('Y-m-d');
      $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
      if($checkAnjungan){
        $this->db('mlite_antrian_loket')->where('postdate',$date)->delete();
        echo 'Berhasil Reset Antrian Anjungan Loket<br>';
        $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
        if(!$checkAnjungan){
          $this->db('mlite_antrian_loket')->save([
            'type' => 'Loket',
            'noantrian' => '1',
            'postdate' => $date,
            'start_time' => date('H:i:s')
          ]);
        }
        echo 'Berhasil Memperbarui Antrian Anjungan Loket';
      } else {
        echo 'Antrian Anjungan Loket Tidak Ada Data';
      }
      exit();
    }

    public function getResetAnjunganCS(){
      date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
      $date = date('Y-m-d');
      $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
      if($checkAnjungan){
        $this->db('mlite_antrian_loket')->where('postdate',$date)->delete();
        echo 'Berhasil Reset Antrian Anjungan CS<br>';
        $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
        if(!$checkAnjungan){
          $this->db('mlite_antrian_loket')->save([
            'type' => 'CS',
            'noantrian' => '1',
            'postdate' => $date,
            'start_time' => date('H:i:s')
          ]);
        }
        echo 'Berhasil Memperbarui Antrian Anjungan CS';
      } else {
        echo 'Antrian Anjungan CS Tidak Ada Data';
      }
      exit();
    }

    public function getResetAnjunganApotek(){
      date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
      $date = date('Y-m-d');
      $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
      if($checkAnjungan){
        $this->db('mlite_antrian_loket')->where('postdate',$date)->delete();
        echo 'Berhasil Reset Antrian Anjungan Apotek<br>';
        $checkAnjungan = $this->db('mlite_antrian_loket')->where('postdate',$date)->oneArray();
        if(!$checkAnjungan){
          $this->db('mlite_antrian_loket')->save([
            'type' => 'Apotek',
            'noantrian' => '1',
            'postdate' => $date,
            'start_time' => date('H:i:s')
          ]);
        }
        echo 'Berhasil Memperbarui Antrian Anjungan Apotek';
      } else {
        echo 'Antrian Anjungan Apotek Tidak Ada Data';
      }
      exit();
    }

}

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
        $polis = $this->db('poliklinik')->where('status', '1')->asc('nm_poli')->toArray();
        return $this->draw('index.html', [
            'polis' => $polis
        ]);
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
      $date          = date('Y-m-d');

      // Ambil nomor antrean terkini untuk ke-4 kartu navigasi atas
      $loketFirst = 1;
      $loketSecond = 2;
      $loketRaw = $this->settings->get('anjungan.antrian_loket') ?: '1,2';
      $loketArr = array_values(array_filter(array_map('trim', explode(",", $loketRaw))));
      if(!empty($loketArr)) {
        $loketFirst = intval($loketArr[0]);
        if(isset($loketArr[1])) $loketSecond = intval($loketArr[1]);
      }

      $loket1DB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('loket', $loketFirst)->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
      $init_loket1_no = !empty($loket1DB['noantrian']) ? $loket1DB['noantrian'] : 0;

      $loket2DB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('loket', $loketSecond)->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
      $init_card2_no = !empty($loket2DB['noantrian']) ? $loket2DB['noantrian'] : 0;

      $apotekDB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Apotek')->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
      $init_apotek_no = !empty($apotekDB['noantrian']) ? $apotekDB['noantrian'] : 0;

      $geriatriDB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Geriatri')->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
      $init_geriatri_no = !empty($geriatriDB['noantrian']) ? $geriatriDB['noantrian'] : 0;

      $init_poli_no = $this->settings->get('anjungan.panggil_poli_nomor') ?: '000';
      if($this->settings->get('anjungan.panggil_poli_tanggal') != $date) {
        $init_poli_no = '000';
      }

      $show = !empty($_GET['show']) ? $_GET['show'] : "panggil_loket";
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
          $loketRaw = $this->settings->get('anjungan.antrian_loket') ?: '1,2';
          $loket = array_values(array_filter(array_map('trim', explode(",", $loketRaw))));
          if (empty($loket)) {
            $loket = ['1', '2'];
          }
          $activeLoket = isset($_GET['loket']) && intval($_GET['loket']) > 0 ? intval($_GET['loket']) : (isset($loket[0]) ? intval($loket[0]) : 1);
          if (!in_array((string)$activeLoket, $loket)) {
            $loket[] = (string)$activeLoket;
            natsort($loket);
            $loket = array_values($loket);
          }
          $loketFirst = isset($loket[0]) ? intval($loket[0]) : 1;
          $loketSecond = isset($loket[1]) ? intval($loket[1]) : ($loketFirst + 1);

          $namaloket = 'a';
          $panggil_loket = 'panggil_loket';
          $date = date('Y-m-d');

          // Auto-migrasi data check-in poli lama agar tidak mencemari ringkasan antrean loket
          try {
            $this->db()->pdo()->exec("UPDATE mlite_antrian_loket SET type = 'Poli' WHERE type = 'Loket' AND (loket = '0' OR loket IS NULL OR loket = '') AND status = '2' AND no_rkm_medis IS NOT NULL AND no_rkm_medis != ''");
          } catch (\Throwable $e) {}

          $get_antrian = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Loket')->where('postdate', $date)->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['max_no'])) {
            $noantrian = intval($get_antrian['max_no']);
          }

          $calledToday = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Loket')->where('loket', $activeLoket)->where('postdate', $date)->where('status', '>=', 1)->oneArray();
          $tcounter = !empty($calledToday['max_no']) ? intval($calledToday['max_no']) : 0;
          $_tcounter = $tcounter + 1;

          // Nomor antrian aktif saat ini untuk ringkasan header
          $loket1DB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('loket', $loketFirst)->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
          $init_loket1_no = !empty($loket1DB['noantrian']) ? $loket1DB['noantrian'] : 0;

          $loket2DB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Loket')->where('loket', $loketSecond)->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
          $init_card2_no = !empty($loket2DB['noantrian']) ? $loket2DB['noantrian'] : 0;

          $apotekDB = $this->db('mlite_antrian_loket')->select('noantrian')->where('type', 'Apotek')->where('postdate', $date)->where('status', '>=', 1)->desc('kd')->oneArray();
          $init_apotek_no = !empty($apotekDB['noantrian']) ? $apotekDB['noantrian'] : 0;

          $init_geriatri_no = $init_geriatri_no ?? 0;

          $waitingCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Loket')->where('postdate', $date)->where('status', 0)->oneArray();
          $waiting_count = !empty($waitingCount['total']) ? intval($waitingCount['total']) : 0;

          $waitingRows = $this->db('mlite_antrian_loket')
            ->select('noantrian')
            ->where('type', 'Loket')
            ->where('postdate', $date)
            ->where('status', 0)
            ->asc('CAST(noantrian AS UNSIGNED)')
            ->toArray();
          $waiting_numbers = [];
          foreach($waitingRows as $wr) {
            $waiting_numbers[] = 'A-' . sprintf("%03d", intval($wr['noantrian']));
          }
          $next_waiting = !empty($waiting_numbers) ? $waiting_numbers[0] : '-';

          $skippedCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Loket')->where('postdate', $date)->where('status', 4)->oneArray();
          $skipped_count = !empty($skippedCount['total']) ? intval($skippedCount['total']) : 0;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // 1. Cek antrian menunggu berstatus 0 hari ini
            $waiting = $this->db('mlite_antrian_loket')
              ->where('type', 'Loket')
              ->where('postdate', $date)
              ->where('status', 0)
              ->asc('CAST(noantrian AS UNSIGNED)')
              ->oneArray();

            if (!empty($waiting)) {
              $tcounter = intval($waiting['noantrian']);
              $targetKd = $waiting['kd'];
            } else {
              $latest = $this->db('mlite_antrian_loket')
                ->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')
                ->where('type', 'Loket')
                ->where('postdate', $date)
                ->oneArray();
              $tcounter = !empty($latest['max_no']) ? intval($latest['max_no']) + 1 : 1;
              $targetKd = null;
            }

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Loket')
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_tanggal')->save(['value' => $date]);

            if ($targetKd) {
              $this->db('mlite_antrian_loket')->where('kd', $targetKd)->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
              $cek = $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
              if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
              } else {
                $this->db('mlite_antrian_loket')->save([
                  'type' => 'Loket',
                  'noantrian' => $tcounter,
                  'loket' => $loketVal,
                  'postdate' => $date,
                  'start_time' => date('H:i:s'),
                  'end_time' => '00:00:00',
                  'status' => 2
                ]);
              }
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_loket&loket='.$loketVal));
          }
          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini untuk loket ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Loket')
              ->where('loket', $jumpLoket)
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_loket_tanggal')->save(['value' => $date]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Loket')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Loket',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => $date,
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
          $loketRaw = $this->settings->get('anjungan.antrian_cs') ?: '1';
          $loket = array_values(array_filter(array_map('trim', explode(",", $loketRaw))));
          if (empty($loket)) $loket = ['1'];
          $activeLoket = isset($_GET['loket']) && intval($_GET['loket']) > 0 ? intval($_GET['loket']) : (isset($loket[0]) ? intval($loket[0]) : 1);
          if (!in_array((string)$activeLoket, $loket)) {
            $loket[] = (string)$activeLoket;
            natsort($loket);
            $loket = array_values($loket);
          }
          $namaloket = 'b';
          $panggil_loket = 'panggil_cs';
          $date = date('Y-m-d');
          $get_antrian = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'CS')->where('postdate', $date)->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['max_no'])) {
            $noantrian = intval($get_antrian['max_no']);
          }

          $calledToday = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'CS')->where('postdate', $date)->where('status', '>=', 1)->oneArray();
          $tcounter = !empty($calledToday['max_no']) ? intval($calledToday['max_no']) : 0;
          $_tcounter = $tcounter + 1;

          $waitingCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'CS')->where('postdate', $date)->where('status', 0)->oneArray();
          $waiting_count = !empty($waitingCount['total']) ? intval($waitingCount['total']) : 0;

          $waitingRows = $this->db('mlite_antrian_loket')
            ->select('noantrian')
            ->where('type', 'CS')
            ->where('postdate', $date)
            ->where('status', 0)
            ->asc('CAST(noantrian AS UNSIGNED)')
            ->toArray();
          $waiting_numbers = [];
          foreach($waitingRows as $wr) {
            $waiting_numbers[] = 'B-' . sprintf("%03d", intval($wr['noantrian']));
          }
          $next_waiting = !empty($waiting_numbers) ? $waiting_numbers[0] : '-';

          $skippedCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'CS')->where('postdate', $date)->where('status', 4)->oneArray();
          $skipped_count = !empty($skippedCount['total']) ? intval($skippedCount['total']) : 0;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // 1. Cek antrian menunggu berstatus 0 hari ini
            $waiting = $this->db('mlite_antrian_loket')
              ->where('type', 'CS')
              ->where('postdate', $date)
              ->where('status', 0)
              ->asc('CAST(noantrian AS UNSIGNED)')
              ->oneArray();

            if (!empty($waiting)) {
              $tcounter = intval($waiting['noantrian']);
              $targetKd = $waiting['kd'];
            } else {
              $latest = $this->db('mlite_antrian_loket')
                ->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')
                ->where('type', 'CS')
                ->where('postdate', $date)
                ->oneArray();
              $tcounter = !empty($latest['max_no']) ? intval($latest['max_no']) + 1 : 1;
              $targetKd = null;
            }

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'CS')
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_tanggal')->save(['value' => $date]);

            if ($targetKd) {
              $this->db('mlite_antrian_loket')->where('kd', $targetKd)->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
              $cek = $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
              if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
              } else {
                $this->db('mlite_antrian_loket')->save([
                  'type' => 'CS',
                  'noantrian' => $tcounter,
                  'loket' => $loketVal,
                  'postdate' => $date,
                  'start_time' => date('H:i:s'),
                  'end_time' => '00:00:00',
                  'status' => 2
                ]);
              }
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_cs&loket='.$loketVal));
          }

          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini untuk loket ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'CS')
              ->where('loket', $jumpLoket)
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_cs_tanggal')->save(['value' => $date]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'CS')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'CS',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => $date,
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
          $loketRaw = $this->settings->get('anjungan.antrian_apotek') ?: '1';
          $loket = array_values(array_filter(array_map('trim', explode(",", $loketRaw))));
          if (empty($loket)) $loket = ['1'];
          $activeLoket = isset($_GET['loket']) && intval($_GET['loket']) > 0 ? intval($_GET['loket']) : (isset($loket[0]) ? intval($loket[0]) : 1);
          if (!in_array((string)$activeLoket, $loket)) {
            $loket[] = (string)$activeLoket;
            natsort($loket);
            $loket = array_values($loket);
          }
          $namaloket = 'f';
          $panggil_loket = 'panggil_apotek';
          $date = date('Y-m-d');
          $get_antrian = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Apotek')->where('postdate', $date)->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['max_no'])) {
            $noantrian = intval($get_antrian['max_no']);
          }

          $calledToday = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Apotek')->where('postdate', $date)->where('status', '>=', 1)->oneArray();
          $tcounter = !empty($calledToday['max_no']) ? intval($calledToday['max_no']) : 0;
          $_tcounter = $tcounter + 1;

          $waitingCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Apotek')->where('postdate', $date)->where('status', 0)->oneArray();
          $waiting_count = !empty($waitingCount['total']) ? intval($waitingCount['total']) : 0;

          $waitingRows = $this->db('mlite_antrian_loket')
            ->select('noantrian')
            ->where('type', 'Apotek')
            ->where('postdate', $date)
            ->where('status', 0)
            ->asc('CAST(noantrian AS UNSIGNED)')
            ->toArray();
          $waiting_numbers = [];
          foreach($waitingRows as $wr) {
            $waiting_numbers[] = 'F-' . sprintf("%03d", intval($wr['noantrian']));
          }
          $next_waiting = !empty($waiting_numbers) ? $waiting_numbers[0] : '-';

          $skippedCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Apotek')->where('postdate', $date)->where('status', 4)->oneArray();
          $skipped_count = !empty($skippedCount['total']) ? intval($skippedCount['total']) : 0;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // 1. Cek antrian menunggu berstatus 0 hari ini
            $waiting = $this->db('mlite_antrian_loket')
              ->where('type', 'Apotek')
              ->where('postdate', $date)
              ->where('status', 0)
              ->asc('CAST(noantrian AS UNSIGNED)')
              ->oneArray();

            if (!empty($waiting)) {
              $tcounter = intval($waiting['noantrian']);
              $targetKd = $waiting['kd'];
            } else {
              $latest = $this->db('mlite_antrian_loket')
                ->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')
                ->where('type', 'Apotek')
                ->where('postdate', $date)
                ->oneArray();
              $tcounter = !empty($latest['max_no']) ? intval($latest['max_no']) + 1 : 1;
              $targetKd = null;
            }

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Apotek')
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_tanggal')->save(['value' => $date]);

            if ($targetKd) {
              $this->db('mlite_antrian_loket')->where('kd', $targetKd)->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
              $cek = $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
              if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
              } else {
                $this->db('mlite_antrian_loket')->save([
                  'type' => 'Apotek',
                  'noantrian' => $tcounter,
                  'loket' => $loketVal,
                  'postdate' => $date,
                  'start_time' => date('H:i:s'),
                  'end_time' => '00:00:00',
                  'status' => 2
                ]);
              }
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_apotek&loket='.$loketVal));
          }

          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            // Bersihkan antrian pending hari ini untuk loket ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Apotek')
              ->where('loket', $jumpLoket)
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_apotek_tanggal')->save(['value' => $date]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Apotek')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Apotek',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => $date,
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
        case "panggil_geriatri":
          $display = 'Panggil Geriatri';
          $loketRaw = $this->settings->get('anjungan.antrian_geriatri') ?: '1';
          $loket = array_values(array_filter(array_map('trim', explode(",", $loketRaw))));
          if (empty($loket)) $loket = ['1'];
          $activeLoket = isset($_GET['loket']) && intval($_GET['loket']) > 0 ? intval($_GET['loket']) : (isset($loket[0]) ? intval($loket[0]) : 1);
          if (!in_array((string)$activeLoket, $loket)) {
            $loket[] = (string)$activeLoket;
            natsort($loket);
            $loket = array_values($loket);
          }
          $namaloket = 'g';
          $panggil_loket = 'panggil_geriatri';
          $date = date('Y-m-d');
          $get_antrian = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Geriatri')->where('postdate', $date)->oneArray();
          $noantrian = 0;
          if(!empty($get_antrian['max_no'])) {
            $noantrian = intval($get_antrian['max_no']);
          }

          $calledToday = $this->db('mlite_antrian_loket')->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')->where('type', 'Geriatri')->where('postdate', $date)->where('status', '>=', 1)->oneArray();
          $tcounter = !empty($calledToday['max_no']) ? intval($calledToday['max_no']) : 0;
          $_tcounter = $tcounter + 1;

          $waitingCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Geriatri')->where('postdate', $date)->where('status', 0)->oneArray();
          $waiting_count = !empty($waitingCount['total']) ? intval($waitingCount['total']) : 0;

          $waitingRows = $this->db('mlite_antrian_loket')
            ->select('noantrian')
            ->where('type', 'Geriatri')
            ->where('postdate', $date)
            ->where('status', 0)
            ->asc('CAST(noantrian AS UNSIGNED)')
            ->toArray();
          $waiting_numbers = [];
          foreach($waitingRows as $wr) {
            $waiting_numbers[] = 'G-' . sprintf("%03d", intval($wr['noantrian']));
          }
          $next_waiting = !empty($waiting_numbers) ? $waiting_numbers[0] : '-';

          $skippedCount = $this->db('mlite_antrian_loket')->select('COUNT(*) as total')->where('type', 'Geriatri')->where('postdate', $date)->where('status', 4)->oneArray();
          $skipped_count = !empty($skippedCount['total']) ? intval($skippedCount['total']) : 0;

          if((isset($_GET['action']) && $_GET['action'] == 'next') || (isset($_GET['loket']) && isset($_GET['next']))) {
            $loketVal = isset($_GET['loket']) && $_GET['loket'] != '' ? $_GET['loket'] : '1';

            // 1. Cek antrian menunggu berstatus 0 hari ini
            $waiting = $this->db('mlite_antrian_loket')
              ->where('type', 'Geriatri')
              ->where('postdate', $date)
              ->where('status', 0)
              ->asc('CAST(noantrian AS UNSIGNED)')
              ->oneArray();

            if (!empty($waiting)) {
              $tcounter = intval($waiting['noantrian']);
              $targetKd = $waiting['kd'];
            } else {
              $latest = $this->db('mlite_antrian_loket')
                ->select('MAX(CAST(noantrian AS UNSIGNED)) as max_no')
                ->where('type', 'Geriatri')
                ->where('postdate', $date)
                ->oneArray();
              $tcounter = !empty($latest['max_no']) ? intval($latest['max_no']) + 1 : 1;
              $targetKd = null;
            }

            // Bersihkan antrian pending hari ini
            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Geriatri')
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri')->save(['value' => $loketVal]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri_tanggal')->save(['value' => $date]);

            if ($targetKd) {
              $this->db('mlite_antrian_loket')->where('kd', $targetKd)->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
            } else {
              $cek = $this->db('mlite_antrian_loket')->where('type', 'Geriatri')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
              if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 2, 'loket' => $loketVal, 'start_time' => date('H:i:s')]);
              } else {
                $this->db('mlite_antrian_loket')->save([
                  'type' => 'Geriatri',
                  'noantrian' => $tcounter,
                  'loket' => $loketVal,
                  'postdate' => $date,
                  'start_time' => date('H:i:s'),
                  'end_time' => '00:00:00',
                  'status' => 2
                ]);
              }
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_geriatri&loket='.$loketVal));
          }

          if(isset($_GET['antrian']) && $_GET['antrian'] != '') {
            $jumpAntrian = intval($_GET['antrian']);
            if($jumpAntrian <= 0) $jumpAntrian = 1;
            $jumpLoket = isset($_GET['reset']) && $_GET['reset'] != '' ? $_GET['reset'] : (isset($_GET['loket']) ? $_GET['loket'] : '1');
            $tcounter = $jumpAntrian;

            $this->db('mlite_antrian_loket')
              ->where('postdate', $date)
              ->where('type', 'Geriatri')
              ->where('loket', $jumpLoket)
              ->where('status', 1)
              ->save(['status' => 2, 'end_time' => date('H:i:s')]);

            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri')->save(['value' => $jumpLoket]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri_nomor')->save(['value' => $tcounter]);
            $this->db('mlite_settings')->where('module', 'anjungan')->where('field', 'panggil_geriatri_tanggal')->save(['value' => $date]);

            $cek = $this->db('mlite_antrian_loket')->where('type', 'Geriatri')->where('noantrian', $tcounter)->where('postdate', $date)->oneArray();
            if ($cek) {
                $this->db('mlite_antrian_loket')->where('kd', $cek['kd'])->save(['status' => 1, 'loket' => $jumpLoket, 'start_time' => date('H:i:s')]);
            } else {
                $this->db('mlite_antrian_loket')->save([
                    'type' => 'Geriatri',
                    'noantrian' => $tcounter,
                    'loket' => $jumpLoket,
                    'postdate' => $date,
                    'start_time' => date('H:i:s'),
                    'end_time' => '00:00:00',
                    'status' => 1
                ]);
            }
            redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_geriatri&loket='.$jumpLoket));
          }
          $hitung_antrian = $this->db('mlite_antrian_loket')
            ->where('type', 'Geriatri')
            ->like('postdate', date('Y-m-d'))
            ->toArray();
          $counter = strlen($tcounter);
          $xcounter = [];
          for($i=0;$i<$counter;$i++){
            $xcounter[] = '<audio id="suarabel'.$i.'" src="{?=url()?}/plugins/anjungan/suara/'.substr($tcounter,$i,1).'.wav" ></audio>';
          };

        break;
        case "panggil_poli":
          $display = 'Panggil Poliklinik';
          $namaloket = 'poli';
          $panggil_loket = 'panggil_poli';
          $loket = ['1'];
          $activeLoket = 1;

          // Daftar Poliklinik Aktif (kecuali poli dummy '-')
          $daftar_poli = $this->db('poliklinik')->where('status', '1')->where('kd_poli', '!=', '-')->asc('nm_poli')->toArray();
          $kd_poli_aktif = isset($_GET['kd_poli']) && !empty($_GET['kd_poli']) ? $_GET['kd_poli'] : (!empty($daftar_poli) ? $daftar_poli[0]['kd_poli'] : '');

          // Detail Poli Aktif
          $poli_aktif = $this->db('poliklinik')->where('kd_poli', $kd_poli_aktif)->oneArray();
          $nama_poli_aktif = !empty($poli_aktif['nm_poli']) ? $poli_aktif['nm_poli'] : 'Poliklinik';

          // Daftar Dokter Aktif
          $daftar_dokter = $this->db('dokter')->where('status', '1')->asc('nm_dokter')->toArray();
          $kd_dokter_aktif = isset($_GET['kd_dokter']) ? $_GET['kd_dokter'] : '';

          // Pasien Hari Ini di Poli Terpilih
          $qPasien = $this->db('reg_periksa')
            ->select('reg_periksa.no_reg')
            ->select('reg_periksa.no_rawat')
            ->select('reg_periksa.no_rkm_medis')
            ->select('reg_periksa.kd_poli')
            ->select('reg_periksa.kd_dokter')
            ->select('reg_periksa.jam_reg')
            ->select('reg_periksa.stts')
            ->select('reg_periksa.status_lanjut')
            ->select('pasien.nm_pasien')
            ->select('dokter.nm_dokter')
            ->select('penjab.png_jawab')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
            ->where('reg_periksa.tgl_registrasi', $date)
            ->where('reg_periksa.kd_poli', $kd_poli_aktif);

          if(!empty($kd_dokter_aktif)) {
            $qPasien->where('reg_periksa.kd_dokter', $kd_dokter_aktif);
          }
          $antrian_poli_list = $qPasien->asc('reg_periksa.no_reg')->toArray();

          // Cek data kehadiran / check-in dari APM / Loket
          $checkinRM = [];
          try {
            $chkRows = $this->db('mlite_antrian_loket')->select('no_rkm_medis')->where('postdate', $date)->where('status', 2)->toArray();
            foreach($chkRows as $cr) {
              if(!empty($cr['no_rkm_medis'])) $checkinRM[$cr['no_rkm_medis']] = true;
            }
          } catch(\Throwable $e){}

          // Hitung Statistik
          $stat_total = count($antrian_poli_list);
          $stat_menunggu = 0;
          $stat_periksa = 0;
          $stat_sudah = 0;
          $stat_batal = 0;
          $next_pasien = null;
          $current_exam_pasien = null;

          foreach($antrian_poli_list as &$p) {
            $p['is_hadir'] = isset($checkinRM[$p['no_rkm_medis']]) || in_array($p['stts'], ['Berkas Diterima', 'Sudah']);
            if($p['stts'] == 'Belum') {
              $stat_menunggu++;
              if($next_pasien === null) {
                $next_pasien = $p;
              }
            } elseif($p['stts'] == 'Berkas Diterima') {
              $stat_periksa++;
              if($current_exam_pasien === null) {
                $current_exam_pasien = $p;
              }
            } elseif($p['stts'] == 'Sudah') {
              $stat_sudah++;
            } elseif($p['stts'] == 'Batal') {
              $stat_batal++;
            }
          }
          unset($p);

          // Panggilan aktif dari settings
          $set_nomor = $this->settings->get('anjungan.panggil_poli_nomor');
          $set_nama = $this->settings->get('anjungan.panggil_poli_nama');
          $set_dokter = $this->settings->get('anjungan.panggil_poli_dokter');
          $set_kd_poli = $this->settings->get('anjungan.panggil_poli_kd_poli');
          $set_tgl = $this->settings->get('anjungan.panggil_poli_tanggal');

          $nomor_panggil_aktif = '---';
          $nama_panggil_aktif = 'Belum Ada Panggilan';
          $dokter_panggil_aktif = '-';
          $no_rawat_panggil_aktif = '';

          if($set_tgl == $date && $set_kd_poli == $kd_poli_aktif && !empty($set_nomor)) {
            $nomor_panggil_aktif = sprintf("%03d", intval($set_nomor));
            $nama_panggil_aktif = $set_nama;
            $dokter_panggil_aktif = $set_dokter;
            foreach($antrian_poli_list as $pl) {
              if(sprintf("%03d", intval($pl['no_reg'])) == $nomor_panggil_aktif) {
                $no_rawat_panggil_aktif = $pl['no_rawat'];
                break;
              }
            }
          } elseif($current_exam_pasien !== null) {
            $nomor_panggil_aktif = sprintf("%03d", intval($current_exam_pasien['no_reg']));
            $nama_panggil_aktif = $current_exam_pasien['nm_pasien'];
            $dokter_panggil_aktif = $current_exam_pasien['nm_dokter'];
            $no_rawat_panggil_aktif = $current_exam_pasien['no_rawat'];
          } elseif($next_pasien !== null) {
            $nomor_panggil_aktif = sprintf("%03d", intval($next_pasien['no_reg']));
            $nama_panggil_aktif = $next_pasien['nm_pasien'] . ' (Siap Dipanggil)';
            $dokter_panggil_aktif = $next_pasien['nm_dokter'];
            $no_rawat_panggil_aktif = $next_pasien['no_rawat'];
          }

          $tcounter = intval($nomor_panggil_aktif);
          $noantrian = $stat_total;
          $waiting_count = $stat_menunggu;
          $next_waiting = $next_pasien ? sprintf("%03d", intval($next_pasien['no_reg'])) . ' - ' . $next_pasien['nm_pasien'] : '-';
          $waiting_numbers = [];
          foreach($antrian_poli_list as $pl) {
            if($pl['stts'] == 'Belum') {
              $waiting_numbers[] = sprintf("%03d", intval($pl['no_reg'])) . ' (' . $pl['nm_pasien'] . ')';
            }
          }
          $skipped_count = $stat_batal;
          $hitung_antrian = $antrian_poli_list;
          $xcounter = [];
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
        'noantrian' => isset_or($noantrian),
        'waiting_count' => isset_or($waiting_count, 0),
        'waiting_numbers' => isset_or($waiting_numbers, []),
        'next_waiting' => isset_or($next_waiting, '-'),
        'skipped_count' => isset_or($skipped_count, 0),
        'active_loket' => isset_or($activeLoket, 1),
        'loket_first' => isset_or($loketFirst, 1),
        'loket_second' => isset_or($loketSecond, 2),
        'init_loket1_no' => sprintf("%03d", intval(isset_or($init_loket1_no, 0))),
        'init_card2_no' => sprintf("%03d", intval(isset_or($init_card2_no, 0))),
        'init_apotek_no' => sprintf("%03d", intval(isset_or($init_apotek_no, 0))),
        'init_geriatri_no' => sprintf("%03d", intval(isset_or($init_geriatri_no, 0))),
        'init_poli_no' => isset_or($init_poli_no, '000'),
        'daftar_poli' => isset_or($daftar_poli, []),
        'kd_poli_aktif' => isset_or($kd_poli_aktif, ''),
        'nama_poli_aktif' => isset_or($nama_poli_aktif, ''),
        'daftar_dokter' => isset_or($daftar_dokter, []),
        'kd_dokter_aktif' => isset_or($kd_dokter_aktif, ''),
        'antrian_poli_list' => isset_or($antrian_poli_list, []),
        'stat_total' => isset_or($stat_total, 0),
        'stat_menunggu' => isset_or($stat_menunggu, 0),
        'stat_periksa' => isset_or($stat_periksa, 0),
        'stat_sudah' => isset_or($stat_sudah, 0),
        'stat_batal' => isset_or($stat_batal, 0),
        'nomor_panggil_aktif' => isset_or($nomor_panggil_aktif, '---'),
        'nama_panggil_aktif' => isset_or($nama_panggil_aktif, '-'),
        'dokter_panggil_aktif' => isset_or($dokter_panggil_aktif, '-'),
        'no_rawat_panggil_aktif' => isset_or($no_rawat_panggil_aktif, ''),
        'next_pasien' => isset_or($next_pasien, null),
        'display_card2' => $this->settings->get('anjungan.display_card2') ?: 'loket2',
        'nama_loket_1' => $this->settings->get('anjungan.nama_loket_1') ?: 'Loket Pendaftaran 1',
        'nama_loket_2' => $this->settings->get('anjungan.nama_loket_2') ?: 'Loket Pendaftaran 2',
        'sebutan_loket' => $this->settings->get('anjungan.sebutan_loket') ?: 'Loket',
        'sebutan_loket_upper' => strtoupper($this->settings->get('anjungan.sebutan_loket') ?: 'Loket'),
        'daftar_loket' => $loket,
        'display' => $display
      ]);
    }

    public function postPanggilPoli()
    {
      $this->setTemplate(false);
      header('Content-Type: application/json');

      $action = $_POST['action'] ?? 'call';
      $no_rawat = $_POST['no_rawat'] ?? '';
      $no_reg = $_POST['no_reg'] ?? '';
      $nm_pasien = $_POST['nm_pasien'] ?? '';
      $kd_poli = $_POST['kd_poli'] ?? '';
      $nm_poli = $_POST['nm_poli'] ?? '';
      $kd_dokter = $_POST['kd_dokter'] ?? '';
      $nm_dokter = $_POST['nm_dokter'] ?? '';

      $date = date('Y-m-d');

      // Ambil data pasien jika no_rawat ada namun data lain belum lengkap
      if(!empty($no_rawat) && (empty($nm_pasien) || empty($no_reg))) {
        $reg = $this->db('reg_periksa')
          ->select('reg_periksa.*')
          ->select('pasien.nm_pasien')
          ->select('poliklinik.nm_poli')
          ->select('dokter.nm_dokter')
          ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
          ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
          ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
          ->where('reg_periksa.no_rawat', $no_rawat)
          ->oneArray();
        if($reg) {
          $no_reg = $reg['no_reg'];
          $nm_pasien = $reg['nm_pasien'];
          $kd_poli = $reg['kd_poli'];
          $nm_poli = $reg['nm_poli'];
          $kd_dokter = $reg['kd_dokter'];
          $nm_dokter = $reg['nm_dokter'];
        }
      }

      if($action == 'call' || $action == 'recall') {
        $this->settings->set('anjungan.panggil_poli_nomor', sprintf("%03d", intval($no_reg)));
        $this->settings->set('anjungan.panggil_poli_nama', $nm_pasien);
        $this->settings->set('anjungan.panggil_poli_dokter', $nm_dokter);
        $this->settings->set('anjungan.panggil_poli_kd_poli', $kd_poli);
        $this->settings->set('anjungan.panggil_poli_tujuan', $nm_poli);
        $this->settings->set('anjungan.panggil_poli_tanggal', $date);
        $this->settings->set('anjungan.panggil_poli_time', time());

        // Update status pasien ke 'Berkas Diterima' jika masih 'Belum'
        if(!empty($no_rawat)) {
          $curr = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
          if($curr && $curr['stts'] == 'Belum') {
            $this->db('reg_periksa')->where('no_rawat', $no_rawat)->save(['stts' => 'Berkas Diterima']);
          }
        }

        // Catat Task ID 4 untuk BPJS Antrean (Mulai Pelayanan Poli)
        try {
          $regPeriksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
          if($regPeriksa) {
            $norm = $regPeriksa['no_rkm_medis'];
            $tgl = $regPeriksa['tgl_registrasi'];
            $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('no_rkm_medis', $norm)->oneArray();
            if (!$antrol) {
              $p = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
              if ($p && !empty($p['no_peserta'])) {
                $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('nomor_kartu', $p['no_peserta'])->oneArray();
              }
            }
            if ($antrol && !empty($antrol['kodebooking'])) {
              $kb = $antrol['kodebooking'];
              $nowMs = round(microtime(true) * 1000);
              $t4 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kb)->where('taskid', '4')->oneArray();
              if (!$t4) {
                $this->db('mlite_antrian_referensi_taskid')->save([
                  'tanggal_periksa' => $tgl,
                  'nomor_referensi' => $kb,
                  'taskid' => 4,
                  'waktu' => $nowMs,
                  'status' => 'Belum',
                  'keterangan' => 'Panggilan Ruang Poli Anjungan'
                ]);
              }
            }
          }
        } catch(\Throwable $e){}

        echo json_encode([
          'status' => true,
          'action' => $action,
          'no_reg' => sprintf("%03d", intval($no_reg)),
          'nm_pasien' => $nm_pasien,
          'nm_poli' => $nm_poli,
          'nm_dokter' => $nm_dokter,
          'no_rawat' => $no_rawat,
          'message' => 'Memanggil antrian ' . sprintf("%03d", intval($no_reg)) . ' - ' . $nm_pasien
        ]);
        exit();
      }

      if($action == 'finish') {
        if(!empty($no_rawat)) {
          $this->db('reg_periksa')->where('no_rawat', $no_rawat)->save(['stts' => 'Sudah']);

          // Catat Task ID 5 untuk BPJS Antrean (Selesai Pelayanan Poli)
          try {
            $regPeriksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if($regPeriksa) {
              $norm = $regPeriksa['no_rkm_medis'];
              $tgl = $regPeriksa['tgl_registrasi'];
              $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('no_rkm_medis', $norm)->oneArray();
              if (!$antrol) {
                $p = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
                if ($p && !empty($p['no_peserta'])) {
                  $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('nomor_kartu', $p['no_peserta'])->oneArray();
                }
              }
              if ($antrol && !empty($antrol['kodebooking'])) {
                $kb = $antrol['kodebooking'];
                $nowMs = round(microtime(true) * 1000);
                $t5 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kb)->where('taskid', '5')->oneArray();
                if (!$t5) {
                  $this->db('mlite_antrian_referensi_taskid')->save([
                    'tanggal_periksa' => $tgl,
                    'nomor_referensi' => $kb,
                    'taskid' => 5,
                    'waktu' => $nowMs,
                    'status' => 'Belum',
                    'keterangan' => 'Selesai Layanan Poli Anjungan'
                  ]);
                }
              }
            }
          } catch(\Throwable $e){}
        }
        echo json_encode(['status' => true, 'action' => 'finish', 'message' => 'Pelayanan pemeriksaan selesai']);
        exit();
      }

      if($action == 'skip') {
        if(!empty($no_rawat)) {
          $this->db('reg_periksa')->where('no_rawat', $no_rawat)->save(['stts' => 'Batal']);
        }
        echo json_encode(['status' => true, 'action' => 'skip', 'message' => 'Antrian dilewati / ditandai batal']);
        exit();
      }

      if($action == 'hadir') {
        if(!empty($no_rawat)) {
          $this->db('reg_periksa')->where('no_rawat', $no_rawat)->save(['stts' => 'Berkas Diterima']);

          // Catat Task ID 4 untuk BPJS Antrean
          try {
            $regPeriksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if($regPeriksa) {
              $norm = $regPeriksa['no_rkm_medis'];
              $tgl = $regPeriksa['tgl_registrasi'];
              $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('no_rkm_medis', $norm)->oneArray();
              if (!$antrol) {
                $p = $this->db('pasien')->where('no_rkm_medis', $norm)->oneArray();
                if ($p && !empty($p['no_peserta'])) {
                  $antrol = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $tgl)->where('nomor_kartu', $p['no_peserta'])->oneArray();
                }
              }
              if ($antrol && !empty($antrol['kodebooking'])) {
                $kb = $antrol['kodebooking'];
                $nowMs = round(microtime(true) * 1000);
                $t4 = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $kb)->where('taskid', '4')->oneArray();
                if (!$t4) {
                  $this->db('mlite_antrian_referensi_taskid')->save([
                    'tanggal_periksa' => $tgl,
                    'nomor_referensi' => $kb,
                    'taskid' => 4,
                    'waktu' => $nowMs,
                    'status' => 'Belum',
                    'keterangan' => 'Hadir Masuk Poli Anjungan'
                  ]);
                }
              }
            }
          } catch(\Throwable $e){}
        }
        echo json_encode(['status' => true, 'action' => 'hadir', 'message' => 'Pasien ditandai hadir / berkas diterima']);
        exit();
      }

      echo json_encode(['status' => false, 'message' => 'Aksi tidak dikenal']);
      exit();
    }

    public function getAntrianPoliLive()
    {
      $this->setTemplate(false);
      header('Content-Type: application/json');

      $date = date('Y-m-d');
      $kd_poli = $_GET['kd_poli'] ?? '';
      $kd_dokter = $_GET['kd_dokter'] ?? '';

      if(empty($kd_poli)) {
        $firstPoli = $this->db('poliklinik')->where('status', '1')->asc('nm_poli')->oneArray();
        $kd_poli = $firstPoli ? $firstPoli['kd_poli'] : '';
      }

      $qPasien = $this->db('reg_periksa')
        ->select('reg_periksa.no_reg')
        ->select('reg_periksa.no_rawat')
        ->select('reg_periksa.no_rkm_medis')
        ->select('reg_periksa.kd_poli')
        ->select('reg_periksa.kd_dokter')
        ->select('reg_periksa.jam_reg')
        ->select('reg_periksa.stts')
        ->select('reg_periksa.status_lanjut')
        ->select('pasien.nm_pasien')
        ->select('dokter.nm_dokter')
        ->select('penjab.png_jawab')
        ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
        ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
        ->where('reg_periksa.tgl_registrasi', $date)
        ->where('reg_periksa.kd_poli', $kd_poli);

      if(!empty($kd_dokter)) {
        $qPasien->where('reg_periksa.kd_dokter', $kd_dokter);
      }
      $pasien_list = $qPasien->asc('reg_periksa.no_reg')->toArray();

      $checkinRM = [];
      try {
        $chkRows = $this->db('mlite_antrian_loket')->select('no_rkm_medis')->where('postdate', $date)->where('status', 2)->toArray();
        foreach($chkRows as $cr) {
          if(!empty($cr['no_rkm_medis'])) $checkinRM[$cr['no_rkm_medis']] = true;
        }
      } catch(\Throwable $e){}

      $stat_total = count($pasien_list);
      $stat_menunggu = 0;
      $stat_periksa = 0;
      $stat_sudah = 0;
      $stat_batal = 0;
      $next_pasien = null;

      foreach($pasien_list as &$p) {
        $p['is_hadir'] = isset($checkinRM[$p['no_rkm_medis']]) || in_array($p['stts'], ['Berkas Diterima', 'Sudah']);
        if($p['stts'] == 'Belum') {
          $stat_menunggu++;
          if($next_pasien === null) $next_pasien = $p;
        } elseif($p['stts'] == 'Berkas Diterima') {
          $stat_periksa++;
        } elseif($p['stts'] == 'Sudah') {
          $stat_sudah++;
        } elseif($p['stts'] == 'Batal') {
          $stat_batal++;
        }
      }
      unset($p);

      echo json_encode([
        'status' => true,
        'kd_poli' => $kd_poli,
        'patients' => $pasien_list,
        'stats' => [
          'total' => $stat_total,
          'menunggu' => $stat_menunggu,
          'periksa' => $stat_periksa,
          'sudah' => $stat_sudah,
          'batal' => $stat_batal
        ],
        'next' => $next_pasien,
        'active_call' => [
          'nomor' => $this->settings->get('anjungan.panggil_poli_nomor'),
          'nama' => $this->settings->get('anjungan.panggil_poli_nama'),
          'poli' => $this->settings->get('anjungan.panggil_poli_tujuan'),
          'dokter' => $this->settings->get('anjungan.panggil_poli_dokter'),
          'kd_poli' => $this->settings->get('anjungan.panggil_poli_kd_poli'),
          'time' => $this->settings->get('anjungan.panggil_poli_time')
        ]
      ]);
      exit();
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

    public function getResetAnjunganGeriatri(){
      date_default_timezone_set($this->settings->get('settings.timezone') ?: 'Asia/Makassar');
      $date = date('Y-m-d');
      $this->db('mlite_antrian_loket')->where('type', 'Geriatri')->where('postdate', $date)->delete();
      $fields = ['panggil_geriatri', 'panggil_geriatri_nomor', 'panggil_geriatri_tanggal'];
      foreach ($fields as $f) {
        $this->db('mlite_settings')->where('module', 'anjungan')->where('field', $f)->save(['value' => '']);
      }
      echo 'Berhasil Reset Antrian Geriatri';
      redirect(url(ADMIN.'/anjungan/pemanggil/?show=panggil_geriatri'));
    }

}

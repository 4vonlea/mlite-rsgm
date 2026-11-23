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
        'display_card2' => $this->settings->get('anjungan.display_card2') ?: 'loket2',
        'nama_loket_1' => $this->settings->get('anjungan.nama_loket_1') ?: 'Loket Pendaftaran 1',
        'nama_loket_2' => $this->settings->get('anjungan.nama_loket_2') ?: 'Loket Pendaftaran 2',
        'sebutan_loket' => $this->settings->get('anjungan.sebutan_loket') ?: 'Loket',
        'sebutan_loket_upper' => strtoupper($this->settings->get('anjungan.sebutan_loket') ?: 'Loket'),
        'daftar_loket' => $loket,
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

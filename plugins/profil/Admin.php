<?php

namespace Plugins\Profil;

use Systems\AdminModule;

class Admin extends AdminModule
{
    // Property declarations to fix PHP 8.3 deprecation warnings
    public $assign;

    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Biodata' => 'biodata',
            'Presensi Masuk' => 'presensi',
            'Rekap Presensi' => 'rekap_presensi',
            'Jadwal Pegawai' => 'jadwal',
            'Ganti Password' => 'ganti_pass'
        ];
    }

    public function getManage()
    {
        $sub_modules = [
            ['name' => 'Biodata', 'url' => url([ADMIN, 'profil', 'biodata']), 'icon' => 'cubes', 'desc' => 'Biodata Pegawai'],
            ['name' => 'Presensi', 'url' => url([ADMIN, 'profil', 'presensi']), 'icon' => 'cubes', 'desc' => 'Presensi Pegawai'],
            ['name' => 'Rekap Presensi', 'url' => url([ADMIN, 'profil', 'rekap_presensi']), 'icon' => 'cubes', 'desc' => 'Rekap Presensi Pegawai'],
            ['name' => 'Jadwal', 'url' => url([ADMIN, 'profil', 'jadwal']), 'icon' => 'cubes', 'desc' => 'Jadwal Pegawai'],
            ['name' => 'Ganti Password', 'url' => url([ADMIN, 'profil', 'ganti_pass']), 'icon' => 'cubes', 'desc' => 'Ganti Pasword'],
        ];
        $username = $this->core->getUserInfo('username', null, true);
        $cek_profil = $this->db('pegawai')->where('nik', $username)->oneArray();
        if(!$cek_profil) {
          $profil['nama'] = 'Admin Utama';
          $profil['nik'] = 'admin';
        } else {
          $profil['nama'] = $cek_profil['nama'];
          $profil['nik'] = $cek_profil['nik'];
        }
        $tanggal = getDayIndonesia(date('Y-m-d')) . ', ' . dateIndonesia(date('Y-m-d'));
        $presensi = [];
        $absensi = [];
        if($cek_profil) {
          $presensi = [];
          $absensi = [];
          if($this->db('rekap_presensi')->where('id', $cek_profil['id'])->oneArray()){
            $presensi = $this->db('rekap_presensi')->where('id', $cek_profil['id'])->where('photo', '!=', '')->like('jam_datang', date('Y-m') . '%')->toArray();
            $absensi = $this->db('rekap_presensi')->where('id', $cek_profil['id'])->where('photo', '')->like('jam_datang', date('Y-m') . '%')->toArray();
          }
        }
        $fotoURL = url(MODULES . '/kepegawaian/img/default.png');
        if (!empty($cek_profil['photo'])) {
            $fotoURL = WEBAPPS_URL . '/penggajian/' . $cek_profil['photo'];
        }
        return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules), 'profil' => $profil, 'tanggal' => $tanggal, 'presensi' => $presensi, 'absensi' => $absensi, 'fotoURL' => $fotoURL]);
    }

    public function getBiodata()
    {
        $this->_addHeaderFiles();
        $username = $this->core->getUserInfo('username', null, true);

        $row = $this->db('pegawai')->where('nik', $username)->oneArray();
        $this->assign['form'] = $row;
        $this->assign['title'] = 'Edit Biodata';
        $this->assign['jk'] = ['Pria', 'Wanita'];
        $this->assign['departemen'] = $this->db('departemen')->toArray();
        $this->assign['bidang'] = $this->db('bidang')->toArray();
        $this->assign['stts_wp'] = $this->db('stts_wp')->toArray();
        $this->assign['pendidikan'] = $this->db('pendidikan')->toArray();
        $this->assign['jnj_jabatan'] = $this->db('jnj_jabatan')->toArray();

        $this->assign['fotoURL'] = url(WEBAPPS_PATH . '/penggajian/' . ($row['photo'] ?? ''));

        return $this->draw('biodata.html', ['biodata' => htmlspecialchars_array($this->assign)]);
    }

    public function postBiodataSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'profil', 'biodata']);
        } else {
            $location = url([ADMIN, 'profil', 'biodata', $id]);
        }

        if (checkEmptyFields(['nama'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (($photo = isset_or($_FILES['photo']['tmp_name'], false)) || !$id) {
                $img = new \Systems\Lib\Image;

                if (empty($photo) && !$id) {
                    $photo = MODULES . '/profil/img/default.png';
                }
                if ($img->load($photo)) {
                    if ($img->getInfos('width') < $img->getInfos('height')) {
                        $img->crop(0, 0, $img->getInfos('width'), $img->getInfos('width'));
                    } else {
                        $img->crop(0, 0, $img->getInfos('height'), $img->getInfos('height'));
                    }

                    if ($img->getInfos('width') > 512) {
                        $img->resize(512, 512);
                    }

                    if ($id) {
                        $pegawai = $this->db('pegawai')->oneArray($id);
                    }

                    $_POST['photo'] = "pages/pegawai/photo/" . $pegawai['nik'] . "." . $img->getInfos('type');
                }
            }

            if (!$id) {    // new
                $query = $this->db('pegawai')->save($_POST);
            } else {        // edit
                $query = $this->db('pegawai')->where('id', $id)->save($_POST);
            }

            if ($query) {
                if (isset($img) && $img->getInfos('width')) {
                    if (isset($pegawai)) {
                        unlink(WEBAPPS_PATH . "/penggajian/" . $pegawai['photo']);
                    }

                    $img->save(WEBAPPS_PATH . "/penggajian/" . $_POST['photo']);
                }

                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function getJadwal($page = 1)
    {

        $array_hari = array(1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
        $array_bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');

        $this->_addHeaderFiles();
        $perpage = '10';
        $phrase = '';
        if (isset($_GET['s']))
            $phrase = $_GET['s'];

        $status = '1';
        if (isset($_GET['status']))
            $status = $_GET['status'];

        $username = $this->core->getUserInfo('username', null, true);

        // pagination
        if ($this->core->getUserInfo('id') == 1) {
            $totalRecords = $this->db('jadwal_pegawai')
                ->join('pegawai', 'pegawai.id=jadwal_pegawai.id')
                ->where('jadwal_pegawai.tahun', date('Y'))
                ->where('jadwal_pegawai.bulan', date('m'))
                ->like('pegawai.nama', '%' . $phrase . '%')
                ->toArray();
        } else {
            $totalRecords = $this->db('jadwal_pegawai')
                ->join('pegawai', 'pegawai.id=jadwal_pegawai.id')
                ->where('jadwal_pegawai.tahun', date('Y'))
                ->where('jadwal_pegawai.bulan', date('m'))
                ->where('nik', $username)
                // ->like('pegawai.nama', '%'.$phrase.'%')
                ->toArray();
        }
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'profil', 'jadwal', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination', '5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        if ($this->core->getUserInfo('id') == 1) {
            $rows = $this->db('jadwal_pegawai')
                ->join('pegawai', 'pegawai.id=jadwal_pegawai.id')
                ->where('jadwal_pegawai.tahun', date('Y'))
                ->where('jadwal_pegawai.bulan', date('m'))
                ->like('pegawai.nama', '%' . $phrase . '%')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        } else {
            $rows = $this->db('jadwal_pegawai')
                ->join('pegawai', 'pegawai.id=jadwal_pegawai.id')
                ->where('jadwal_pegawai.tahun', date('Y'))
                ->where('jadwal_pegawai.bulan', date('m'))
                ->where('nik', $username)
                // ->like('pegawai.nama', '%'.$phrase.'%')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        }
        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                // $row['editURL'] = url([ADMIN, 'presensi', 'jadwaledit', $row['id']]);
                // $row['delURL']  = url([ADMIN, 'master', 'petugasdelete', $row['nip']]);
                // $row['restoreURL']  = url([ADMIN, 'master', 'petugasrestore', $row['nip']]);
                // $row['viewURL'] = url([ADMIN, 'master', 'petugasview', $row['nip']]);
                $this->assign['list'][] = $row;
            }
        }

        /*
        $year = date('Y');
        $month = date('m');
        $day = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($i = 1; $i < $day + 1; $i++) {
            $i;
        }
        */

        $this->assign['getStatus'] = isset($_GET['status']);
        // $this->assign['addURL'] = url([ADMIN, 'presensi', 'jadwaladd']);
        // $this->assign['printURL'] = url([ADMIN, 'master', 'petugasprint']);

        return $this->draw('jadwal.manage.html', ['jadwal' => htmlspecialchars_array($this->assign), 'array_hari' => $array_hari, 'array_bulan' => $array_bulan]);
    }

    public function getRekap_Presensi($page = 1)
    {
        $this->_addHeaderFiles();
        $perpage = '10';
        $phrase = '';
        if (isset($_GET['s']))
            $phrase = $_GET['s'];

        $status = '1';
        if (isset($_GET['status']))
            $status = $_GET['status'];

        $bulan = date('m');
        if (isset($_GET['b'])) {
            $bulan = $_GET['b'];
        }

        $username = $this->core->getUserInfo('username', null, true);

        if ($this->core->getUserInfo('id') == 1 and isset($_GET['bulan'])) {
            $totalRecords = $this->db('rekap_presensi')
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->like('nama', '%' . $phrase . '%')
                ->orLike('shift', '%' . $phrase . '%')
                ->asc('jam_datang')
                ->toArray();
        } elseif (isset($_GET['bulan'])) {
            $totalRecords = $this->db('rekap_presensi')
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->where('nik', $username)
                ->asc('jam_datang')
                ->toArray();
        } else {
            $totalRecords = $this->db('rekap_presensi')
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->where('nik', $username)
                ->asc('jam_datang')
                ->toArray();
        }
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'profil', 'rekap_presensi', '%d?b=' . $bulan . '&s=' . $phrase]));
        $this->assign['pagination'] = $pagination->nav('pagination', '5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();

        if ($this->core->getUserInfo('id') == 1 and isset($_GET['bulan'])) {
            $rows = $this->db('rekap_presensi')
                ->select([
                    'nama' => 'pegawai.nama',
                    'departemen' => 'pegawai.departemen',
                    'id' => 'rekap_presensi.id',
                    'shift' => 'rekap_presensi.shift',
                    'jam_datang' => 'rekap_presensi.jam_datang',
                    'jam_pulang' => 'rekap_presensi.jam_pulang',
                    'status' => 'rekap_presensi.status',
                    'durasi' => 'rekap_presensi.durasi',
                    'photo' => 'rekap_presensi.photo'
                ])
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->like('nama', '%' . $phrase . '%')
                ->orLike('shift', '%' . $phrase . '%')
                ->asc('jam_datang')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        } elseif (isset($_GET['bulan'])) {
            $rows = $this->db('rekap_presensi')
                ->select([
                    'nama' => 'pegawai.nama',
                    'departemen' => 'pegawai.departemen',
                    'id' => 'rekap_presensi.id',
                    'shift' => 'rekap_presensi.shift',
                    'jam_datang' => 'rekap_presensi.jam_datang',
                    'jam_pulang' => 'rekap_presensi.jam_pulang',
                    'status' => 'rekap_presensi.status',
                    'durasi' => 'rekap_presensi.durasi',
                    'photo' => 'rekap_presensi.photo'
                ])
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->where('nik', $username)
                ->asc('jam_datang')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        } else {
            $rows = $this->db('rekap_presensi')
                ->select([
                    'nama' => 'pegawai.nama',
                    'departemen' => 'pegawai.departemen',
                    'id' => 'rekap_presensi.id',
                    'shift' => 'rekap_presensi.shift',
                    'jam_datang' => 'rekap_presensi.jam_datang',
                    'jam_pulang' => 'rekap_presensi.jam_pulang',
                    'status' => 'rekap_presensi.status',
                    'durasi' => 'rekap_presensi.durasi',
                    'photo' => 'rekap_presensi.photo'
                ])
                ->join('pegawai', 'pegawai.id = rekap_presensi.id')
                ->where('jam_datang', '>', date('Y-' . $bulan) . '-01')
                ->where('jam_datang', '<', date('Y-m-t', mktime(0, 0, 0, $bulan, 1, date('Y'))))
                ->where('nik', $username)
                ->asc('jam_datang')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        }

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['mapURL']  = url([ADMIN, 'profil', 'googlemap', $row['id'], date('Y-m-d', strtotime($row['jam_datang']))]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['getBulan'] = $bulan;
        $this->assign['getUser'] = $username;
        $this->assign['bulan'] = array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        return $this->draw('rekap_presensi.html', ['rekap' => htmlspecialchars_array($this->assign)]);
    }

    public function getGoogleMap($id, $tanggal)
    {
        $geo = $this->db('mlite_geolocation_presensi')->where('id', $id)->where('tanggal', $tanggal)->oneArray();
        $pegawai = $this->db('pegawai')->where('id', $id)->oneArray();

        $this->tpl->set('geo', $geo);
        $this->tpl->set('pegawai', $pegawai);
        echo $this->tpl->draw(MODULES . '/profil/view/admin/google_map.html', true);
        exit();
    }
    public function getPresensi($page = 1)
    {
        $this->_addHeaderFiles();

        $perpage = '10';
        $phrase = '';
        if (isset($_GET['s']))
            $phrase = $_GET['s'];

        $username = $this->core->getUserInfo('username', null, true);

        // pagination
        if ($this->core->getUserInfo('id') == 1) {
            $totalRecords = $this->db('temporary_presensi')
                ->join('pegawai', 'pegawai.id = temporary_presensi.id')
                ->like('nama', '%' . $phrase . '%')
                // ->orLike('jam_datang', '%'.date('Y-m').'%')
                ->asc('jam_datang')
                ->toArray();
        } else {
            $totalRecords = $this->db('temporary_presensi')
                ->join('pegawai', 'pegawai.id = temporary_presensi.id')
                ->where('nik', $username)
                ->like('nama', '%' . $phrase . '%')
                ->asc('jam_datang')
                ->toArray();
        }
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'presensi', 'presensi', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination', '5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        if ($this->core->getUserInfo('id') == 1) {
            $rows = $this->db('temporary_presensi')
                ->select([
                    'nama' => 'pegawai.nama',
                    'id' => 'temporary_presensi.id',
                    'shift' => 'temporary_presensi.shift',
                    'jam_datang' => 'temporary_presensi.jam_datang',
                    'status' => 'temporary_presensi.status',
                    'photo' => 'temporary_presensi.photo'
                ])
                ->join('pegawai', 'pegawai.id = temporary_presensi.id')
                ->like('nama', '%' . $phrase . '%')
                ->asc('jam_datang')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        } else {
            $rows = $this->db('temporary_presensi')
                ->select([
                    'nama' => 'pegawai.nama',
                    'id' => 'temporary_presensi.id',
                    'shift' => 'temporary_presensi.shift',
                    'jam_datang' => 'temporary_presensi.jam_datang',
                    'status' => 'temporary_presensi.status',
                    'photo' => 'temporary_presensi.photo'
                ])
                ->join('pegawai', 'pegawai.id = temporary_presensi.id')
                ->where('nik', $username)
                ->like('nama', '%' . $phrase . '%')
                ->asc('jam_datang')
                ->offset($offset)
                ->limit($perpage)
                ->toArray();
        }
        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['mapURL']  = url([ADMIN, 'profil', 'googlemap', $row['id'], date('Y-m-d', strtotime($row['jam_datang']))]);
                $this->assign['list'][] = $row;
            }
        }

        return $this->draw('presensi.html', ['presensi' => htmlspecialchars_array($this->assign)]);
    }

    public function getGanti_Pass()
    {
        $this->_addHeaderFiles();
        $username = $this->core->getUserInfo('username', null, true);
        $fullname = $this->core->getUserInfo('fullname', null, true);
        $this->assign['username'] = $username;
        $this->assign['fullname'] = !empty($fullname) ? $fullname : $username;
        $this->assign['title'] = 'Ubah Kata Sandi';
        $this->assign['is_forced'] = !empty($_SESSION['mlite_force_change']);

        return $this->draw('ganti_pass.html', ['ganti_pass' => htmlspecialchars_array($this->assign)]);
    }

    public function postGanti_Save($id = null)
    {
        $location = url([ADMIN, 'profil', 'ganti_pass']);

        $pass_lama = isset($_POST['pass_lama']) ? (string)$_POST['pass_lama'] : '';
        $pass_baru = isset($_POST['pass_baru']) ? (string)$_POST['pass_baru'] : '';
        $pass_konfirmasi = isset($_POST['pass_konfirmasi']) ? (string)$_POST['pass_konfirmasi'] : '';

        // 1. Validasi isian kosong
        if (trim($pass_lama) === '' || trim($pass_baru) === '') {
            $this->notify('failure', 'Semua kolom kata sandi wajib diisi.');
            redirect($location);
            return;
        }

        // 2. Verifikasi kata sandi lama
        $userId = $this->core->getUserInfo('id');
        $row_user = $this->db('mlite_users')->where('id', $userId)->oneArray();

        if (!$row_user || !password_verify(trim($pass_lama), $row_user['password'])) {
            $this->notify('failure', 'Kata sandi lama yang Anda masukkan salah.');
            redirect($location);
            return;
        }

        // 3. Kata sandi baru tidak boleh sama dengan default 12345678
        if (trim($pass_baru) === '12345678') {
            $this->notify('failure', 'Kata sandi baru tidak boleh menggunakan kata sandi default (12345678). Buatlah kata sandi pribadi yang aman.');
            redirect($location);
            return;
        }

        // 4. Kata sandi baru tidak boleh sama dengan kata sandi lama
        if ($pass_baru === $pass_lama) {
            $this->notify('failure', 'Kata sandi baru tidak boleh sama dengan kata sandi lama.');
            redirect($location);
            return;
        }

        // 5. Cek kecocokan konfirmasi kata sandi
        if ($pass_baru !== $pass_konfirmasi) {
            $this->notify('failure', 'Konfirmasi kata sandi baru tidak cocok dengan kata sandi baru.');
            redirect($location);
            return;
        }

        // 6. Validasi Kompleksitas Standar Keamanan Global (ISO/IEC 27001):
        // Minimal 8 karakter, kombinasi huruf besar (A-Z), huruf kecil (a-z), angka (0-9), simbol/karakter khusus
        $errors = [];
        if (strlen($pass_baru) < 8) {
            $errors[] = 'Minimal 8 karakter';
        }
        if (!preg_match('/[A-Z]/', $pass_baru)) {
            $errors[] = 'Minimal 1 huruf besar (A-Z)';
        }
        if (!preg_match('/[a-z]/', $pass_baru)) {
            $errors[] = 'Minimal 1 huruf kecil (a-z)';
        }
        if (!preg_match('/[0-9]/', $pass_baru)) {
            $errors[] = 'Minimal 1 angka (0-9)';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $pass_baru)) {
            $errors[] = 'Minimal 1 karakter simbol khusus (!@#$%^&* dll)';
        }

        if (!empty($errors)) {
            $this->notify('failure', 'Kata sandi baru belum memenuhi standar keamanan global (ISO/IEC 27001): ' . implode(', ', $errors) . '.');
            redirect($location);
            return;
        }

        // 7. Simpan kata sandi baru ke database
        $passwordHash = password_hash($pass_baru, PASSWORD_BCRYPT);
        $saveData = ['password' => $passwordHash];

        try {
            $hasCols = $this->db()->pdo()->query("SHOW COLUMNS FROM mlite_users LIKE 'password_changed_at'")->fetch();
            if ($hasCols) {
                $saveData['password_changed_at'] = date('Y-m-d H:i:s');
            }
            $hasOtp = $this->db()->pdo()->query("SHOW COLUMNS FROM mlite_users LIKE 'otp_code'")->fetch();
            if ($hasOtp) {
                $saveData['otp_code'] = null;
                $saveData['otp_expires'] = null;
            }
        } catch (\Throwable $e) {}

        $query = $this->db('mlite_users')->where('id', $userId)->save($saveData);

        if ($query) {
            unset($_SESSION['mlite_force_change']);
            $this->notify('success', 'Kata sandi Anda berhasil diperbarui sesuai standar keamanan global (ISO/IEC 27001). Selamat bertugas!');
            redirect(url([ADMIN, 'dashboard', 'main']));
        } else {
            $this->notify('failure', 'Gagal menyimpan kata sandi. Silakan coba kembali.');
            redirect($location);
        }
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES . '/profil/js/admin/app.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('plugins/profil/css/admin/timeline.min.css'));
        $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));

        // JS
        $this->core->addJs(url('plugins/profil/js/admin/timeline.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'), 'footer');

        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));

        // MODULE SCRIPTS
        $this->core->addJS(url([ADMIN, 'profil', 'javascript']), 'footer');
    }

}

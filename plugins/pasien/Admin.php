<?php
namespace Plugins\Pasien;

use Systems\AdminModule;

class Admin extends AdminModule
{
  private $_uploads = WEBAPPS_PATH . '/berkasrawat/pages/upload';
  protected $assign = [];

  public function navigation()
  {
    return [
      'Kelola' => 'manage',
      'Telusur e-RM' => 'telusur',
    ];
  }

  public function getManage()
  {
    $this->core->addJS(url(MODULES . '/dashboard/js/admin/webcam.js?v={$mlite.version}'));
    $this->_addHeaderFiles();

    if (isset($_POST['cari']))
      $_GET['s'] = $_POST['cari'];
    if (isset($_POST['halaman']))
      $_GET['page'] = $_POST['halaman'];

    $result = $this->apiList();

    if (isset($result['status']) && $result['status'] == 'error') {
      $pasien = [];
      $meta = [
        'page' => 1,
        'per_page' => 10,
        'total' => 0
      ];
    } else {
      $pasien = $result['data'];
      $meta = $result['meta'];
    }

    $halaman = $meta['page'];
    $jumlah_data = $meta['total'];
    $offset = $meta['per_page'];
    $jml_halaman = ceil($jumlah_data / $offset);

    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();

    return $this->draw('manage.html', [
      'pasien' => $pasien,
      'halaman' => $halaman,
      'jumlah_data' => $jumlah_data,
      'jml_halaman' => $jml_halaman,
      'cek_vclaim' => htmlspecialchars_array($cek_vclaim),
      'cek_pcare' => $cek_pcare,
      'offset' => $offset,
      'admin_mode' => $this->settings->get('settings.admin_mode'),
      'mlite_crud_permissions' => $this->core->loadCrudPermissions('pasien'),
      'token' => $_SESSION['token']
    ]);
  }

  public function anyForm()
  {
    $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();
    $usernamePcare = '';
    if ($cek_pcare) {
      $usernamePcare = $this->settings('pcare', 'usernamePcare');
    }
    $penjab = $this->db('penjab')->where('status', '1')->toArray();
    $stts_nikah = array('BELUM MENIKAH', 'MENIKAH', 'JANDA', 'DUDHA', 'JOMBLO');
    $agama = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
    $pnd = array('TS', 'TK', 'SD', 'SMP', 'SMA', 'SLTA/SEDERAJAT', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', '-');
    $keluarga = array('AYAH', 'IBU', 'ISTRI', 'SUAMI', 'SAUDARA', 'ANAK');
    $propinsi = $this->db('propinsi')->where('kd_prop', '!=', 1)->asc('nm_prop')->toArray();
    if (isset($_POST['no_rkm_medis'])) {
      $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
      // Tampilkan tanggal lahir di form dengan format DD-MM-YYYY
      if (!empty($pasien['tgl_lahir']) && $pasien['tgl_lahir'] != '0000-00-00') {
        $pasien['tgl_lahir'] = date('d-m-Y', strtotime($pasien['tgl_lahir']));
      } else {
        $pasien['tgl_lahir'] = '';
      }
      $pasien['propinsi'] = (!empty($pasien['kd_prop']) && $pasien['kd_prop'] != 1)
        ? ($this->db('propinsi')->where('kd_prop', $pasien['kd_prop'])->oneArray() ?: ['nm_prop' => '-'])
        : ['nm_prop' => '-'];
      $pasien['kabupaten'] = (!empty($pasien['kd_kab']) && $pasien['kd_kab'] != 1)
        ? ($this->db('kabupaten')->where('kd_kab', $pasien['kd_kab'])->oneArray() ?: ['nm_kab' => '-'])
        : ['nm_kab' => '-'];
      $pasien['kecamatan'] = (!empty($pasien['kd_kec']) && $pasien['kd_kec'] != 1)
        ? ($this->db('kecamatan')->where('kd_kec', $pasien['kd_kec'])->oneArray() ?: ['nm_kec' => '-'])
        : ['nm_kec' => '-'];
      $pasien['kelurahan'] = (!empty($pasien['kd_kel']) && $pasien['kd_kel'] != '1')
        ? ($this->db('kelurahan')->where('kd_kel', $pasien['kd_kel'])->oneArray() ?: ['nm_kel' => '-'])
        : ['nm_kel' => '-'];
      $kabupaten_list = (!empty($pasien['kd_prop']) && $pasien['kd_prop'] != 1)
        ? $this->db('kabupaten')->like('kd_kab', $pasien['kd_prop'] . '%')->where('kd_kab', '!=', 1)->asc('nm_kab')->toArray()
        : [];
      $kecamatan_list = (!empty($pasien['kd_kab']) && $pasien['kd_kab'] != 1)
        ? $this->db('kecamatan')->like('kd_kec', $pasien['kd_kab'] . '%')->where('kd_kec', '!=', 1)->asc('nm_kec')->toArray()
        : [];
      $kelurahan_list = (!empty($pasien['kd_kec']) && $pasien['kd_kec'] != 1)
        ? $this->db('kelurahan')->like('kd_kel', $pasien['kd_kec'] . '%')->where('kd_kel', '!=', '1')->asc('nm_kel')->toArray()
        : [];
      echo $this->draw('form.html', [
        'pasien' => htmlspecialchars_array($pasien),
        'penjab' => htmlspecialchars_array($penjab),
        'stts_nikah' => htmlspecialchars_array($stts_nikah),
        'agama' => htmlspecialchars_array($agama),
        'pnd' => htmlspecialchars_array($pnd),
        'keluarga' => htmlspecialchars_array($keluarga),
        'propinsi' => $propinsi,
        'kabupaten_list' => $kabupaten_list,
        'kecamatan_list' => $kecamatan_list,
        'kelurahan_list' => $kelurahan_list,
        'no_rkm_medis_baru' => htmlspecialchars($this->core->setNoRM(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'waapitoken' => htmlspecialchars($this->settings->get('wagateway.token'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'waapiphonenumber' => htmlspecialchars($this->settings->get('wagateway.phonenumber'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'admin_mode' => htmlspecialchars($this->settings->get('settings.admin_mode'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'urlUploadPhoto' => url([ADMIN, 'pasien', 'uploadphoto', htmlspecialchars($_POST['no_rkm_medis'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')]),
        'cek_pcare' => htmlspecialchars_array($cek_pcare ?: []),
        'usernamePcare' => htmlspecialchars($usernamePcare, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'mlite_crud_permissions' => htmlspecialchars_array($this->core->loadCrudPermissions('pasien'))
      ]);
    } else {
      $pasien = [
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'no_ktp' => '',
        'jk' => '',
        'tmp_lahir' => '',
        'tgl_lahir' => '',
        'nm_ibu' => '-',
        'alamat' => '',
        'gol_darah' => '-',
        'pekerjaan' => '-',
        'stts_nikah' => '',
        'agama' => 'ISLAM',
        'tgl_daftar' => date('Y-m-d'),
        'no_tlp' => '',
        'umur' => '',
        'pnd' => '-',
        'keluarga' => '',
        'namakeluarga' => '-',
        'kd_pj' => '',
        'no_peserta' => '',
        'kd_kel' => '1',
        'kd_kec' => '1',
        'kd_kab' => '1',
        'pekerjaanpj' => '',
        'alamatpj' => '',
        'kelurahanpj' => '',
        'kecamatanpj' => '',
        'kabupatenpj' => '',
        'perusahaan_pasien' => '',
        'suku_bangsa' => '',
        'bahasa_pasien' => '',
        'cacat_fisik' => '',
        'email' => '-',
        'nip' => '',
        'kd_prop' => '1',
        'propinsipj' => '',
        'propinsi' => ['nm_prop' => '-'],
        'kabupaten' => ['nm_kab' => '-'],
        'kecamatan' => ['nm_kec' => '-'],
        'kelurahan' => ['nm_kel' => '-']
      ];
      echo $this->draw('form.html', [
        'pasien' => $pasien,
        'penjab' => $penjab,
        'stts_nikah' => $stts_nikah,
        'agama' => $agama,
        'pnd' => $pnd,
        'keluarga' => $keluarga,
        'propinsi' => $propinsi,
        'kabupaten_list' => [],
        'kecamatan_list' => [],
        'kelurahan_list' => [],
        'no_rkm_medis_baru' => $this->core->setNoRM(),
        'waapitoken' => $this->settings->get('wagateway.token'),
        'waapiphonenumber' => $this->settings->get('wagateway.phonenumber'),
        'admin_mode' => $this->settings->get('settings.admin_mode'),
        'urlUploadPhoto' => '',
        'cek_pcare' => $cek_pcare,
        'usernamePcare' => $usernamePcare,
        'mlite_crud_permissions' => $this->core->loadCrudPermissions('pasien')
      ]);
    }
    exit();
  }

  public function postMaxid()
  {
    echo $this->core->setNoRM();
    exit();
  }

  public function postSave()
  {
    $mlite_crud_permissions = $this->core->loadCrudPermissions('pasien');
    $_POST['tgl_daftar'] = date('Y-m-d', strtotime($_POST['tgl_daftar']));
    // Tanggal lahir dari form berformat DD-MM-YYYY, simpan ke DB sebagai YYYY-MM-DD
    if (!empty($_POST['tgl_lahir'])) {
      $tglLahirObj = \DateTime::createFromFormat('d-m-Y', trim($_POST['tgl_lahir']));
      if ($tglLahirObj && $tglLahirObj->format('d-m-Y') === trim($_POST['tgl_lahir'])) {
        $_POST['tgl_lahir'] = $tglLahirObj->format('Y-m-d');
      }
    }
    $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
    $cek_prop = !empty($_POST['kd_prop']) ? $this->db('propinsi')->where('kd_prop', $_POST['kd_prop'])->oneArray() : null;
    if (!$cek_prop && !empty($_POST['kd_prop']) && !empty($_POST['nm_prop'])) {
      try {
        $this->db('propinsi')->save(['kd_prop' => $_POST['kd_prop'], 'nm_prop' => $_POST['nm_prop']]);
      } catch (\Exception $e) {}
    }
    $cek_kab = !empty($_POST['kd_kab']) ? $this->db('kabupaten')->where('kd_kab', $_POST['kd_kab'])->oneArray() : null;
    if (!$cek_kab && !empty($_POST['kd_kab']) && !empty($_POST['nm_kab'])) {
      try {
        $this->db('kabupaten')->save(['kd_kab' => $_POST['kd_kab'], 'nm_kab' => $_POST['nm_kab']]);
      } catch (\Exception $e) {}
    }
    $cek_kec = !empty($_POST['kd_kec']) ? $this->db('kecamatan')->where('kd_kec', $_POST['kd_kec'])->oneArray() : null;
    if (!$cek_kec && !empty($_POST['kd_kec']) && !empty($_POST['nm_kec'])) {
      try {
        $this->db('kecamatan')->save(['kd_kec' => $_POST['kd_kec'], 'nm_kec' => $_POST['nm_kec']]);
      } catch (\Exception $e) {}
    }
    if (!empty($_POST['nm_kel'])) {
      $cek_kel = $this->db('kelurahan')->where('nm_kel', $_POST['nm_kel'])->oneArray();
      if (!$cek_kel) {
        try {
          $result = $this->db('kelurahan')->select('kd_kel')->desc('kd_kel')->limit(1)->oneArray();
          $_POST['kd_kel'] = ($result ? $result['kd_kel'] + 1 : 1);
          $this->db('kelurahan')->save(['kd_kel' => $_POST['kd_kel'], 'nm_kel' => $_POST['nm_kel']]);
        } catch (\Exception $e) {}
      } else {
        $_POST['kd_kel'] = $cek_kel['kd_kel'];
      }
    }

    $manual = $_POST['manual'] ?? '0';
    unset($_POST['manual']);

    if (!$pasien) {
      if ($mlite_crud_permissions['can_create'] == 'false') {
        echo json_encode(['status' => 'error', 'msg' => 'Anda tidak memiliki hak akses untuk menambah data!', 'message' => 'Anda tidak memiliki hak akses untuk menambah data!']);
        exit();
      }

      $maxRetries = 5;
      $retryCount = 0;
      $success = false;
      $lastError = '';

      while ($retryCount < $maxRetries && !$success) {
        $this->db()->pdo()->beginTransaction();
        try {
          if ($manual == '0') {
            $_POST['no_rkm_medis'] = $this->core->setNoRM();
          }

          $_POST['tmp_lahir'] = !empty($_POST['tmp_lahir']) ? $_POST['tmp_lahir'] : '-';
          $_POST['umur'] = $this->hitungUmur($_POST['tgl_lahir']);
          $_POST['pekerjaanpj'] = '-';
          $_POST['alamatpj'] = !empty($_POST['alamat']) ? $_POST['alamat'] : '-';
          $_POST['kelurahanpj'] = !empty($_POST['nm_kel']) ? $_POST['nm_kel'] : '-';
          $_POST['kecamatanpj'] = !empty($_POST['nm_kec']) ? $_POST['nm_kec'] : '-';
          $_POST['kabupatenpj'] = !empty($_POST['nm_kab']) ? $_POST['nm_kab'] : '-';
          $_POST['perusahaan_pasien'] = '-';
          $_POST['suku_bangsa'] = '1';
          $_POST['bahasa_pasien'] = '1';
          $_POST['cacat_fisik'] = '1';
          $_POST['nip'] = '-';
          $_POST['propinsipj'] = !empty($_POST['nm_prop']) ? $_POST['nm_prop'] : '-';
          $_POST['email'] = !empty($_POST['email']) ? $_POST['email'] : '-';
          $_POST['namakeluarga'] = !empty($_POST['namakeluarga']) ? $_POST['namakeluarga'] : '-';
          $_POST['nm_ibu'] = !empty($_POST['nm_ibu']) ? $_POST['nm_ibu'] : '-';
          $_POST['pnd'] = !empty($_POST['pnd']) ? $_POST['pnd'] : '-';
          $_POST['kd_prop'] = !empty($_POST['kd_prop']) ? $_POST['kd_prop'] : 1;
          $_POST['kd_kab'] = !empty($_POST['kd_kab']) ? $_POST['kd_kab'] : 1;
          $_POST['kd_kec'] = !empty($_POST['kd_kec']) ? $_POST['kd_kec'] : 1;
          $_POST['kd_kel'] = !empty($_POST['kd_kel']) ? $_POST['kd_kel'] : '1';

          $tempPost = $_POST;
          unset($tempPost['nm_prop']);
          unset($tempPost['nm_kab']);
          unset($tempPost['nm_kec']);
          unset($tempPost['nm_kel']);

          $query = $this->db('pasien')->save($tempPost);

          if ($query) {
            if ($manual == '0') {
              $this->db()->pdo()->prepare("UPDATE set_no_rkm_medis SET no_rkm_medis=?")->execute([$_POST['no_rkm_medis']]);
            }
            $this->db()->pdo()->commit();
            $success = true;
          } else {
            $this->db()->pdo()->rollBack();
            $lastError = 'Gagal menyimpan data pasien.';
            break; // Bukan error duplikat, keluar dari loop
          }
        } catch (\Exception $e) {
          $this->db()->pdo()->rollBack();
          if ($e->getCode() == '23000' && $manual == '0') {
            $retryCount++;
            usleep(100000); // Tunggu 100ms sebelum mencoba lagi
            continue;
          }
          $lastError = $e->getMessage();
          break;
        }
      }

      if ($success) {
        $data['status'] = 'success';
        $data['msg'] = 'Data pendaftaran pasien telah disimpan!';
        $data['message'] = $data['msg'];
        echo json_encode(htmlspecialchars_array($data));
      } else {
        $data['status'] = 'error';
        $data['msg'] = htmlspecialchars($lastError ?: 'Terjadi kesalahan sistem.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data['message'] = $data['msg'];
        echo json_encode(htmlspecialchars_array($data));
      }

    } else {
      if ($mlite_crud_permissions['can_update'] == 'false') {
        echo json_encode(['status' => 'error', 'msg' => 'Anda tidak memiliki hak akses untuk mengubah data!', 'message' => 'Anda tidak memiliki hak akses untuk mengubah data!']);
        exit();
      }
      unset($_POST['nm_prop']);
      unset($_POST['nm_kab']);
      unset($_POST['nm_kec']);
      unset($_POST['nm_kel']);
      $_POST['umur'] = $this->hitungUmur($_POST['tgl_lahir']);
      try {
        $query = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->update($_POST);
        if ($query) {
          $data['status'] = 'success';
          $data['msg'] = 'Data pendaftaran pasien telah disimpan!';
          $data['message'] = $data['msg'];
          echo json_encode(htmlspecialchars_array($data));
        } else {
          $data['status'] = 'error';
          $data['msg'] = 'Gagal mengubah data pasien.';
          $data['message'] = $data['msg'];
          echo json_encode(htmlspecialchars_array($data));
        }
      } catch (\Throwable $e) {
        $data['status'] = 'error';
        $data['msg'] = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $data['message'] = $data['msg'];
        echo json_encode(htmlspecialchars_array($data));
      }

    }

    exit();
  }

  public function getUploadPhoto()
  {
    $no_rkm_medis = parseURL()[2];
    $this->core->addJS(url(MODULES . '/dashboard/js/admin/webcam.js?v={$mlite.version}'));
    return $this->draw('uploadphoto.html', ['no_rkm_medis' => $no_rkm_medis]);
  }

  public function postSavePhoto($no_rkm_medis = null)
  {

    /*if($_FILES['file']['name'] != ''){
        $test = explode('.', $_FILES['file']['name']);
        $extension = end($test);
        $name = $_POST['no_rkm_medis'].'.'.$extension;

        $location = UPLOADS.'/'.$name;
        move_uploaded_file($_FILES['file']['tmp_name'], $location);

        echo '<img src="'.url().'/uploads/'.$name.'" height="100" width="100" />';
    }*/

    if ($no_rkm_medis != null) {
      $_POST['no_rkm_medis'] = $no_rkm_medis;
    }

    $personal_pasien = $this->db('personal_pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();

    if (($photo = isset_or($_FILES['file']['tmp_name'], false)) || !$_POST['no_rkm_medis']) {
      $img = new \Systems\Lib\Image;
      if ($img->load($photo)) {
        if ($img->getInfos('width') < $img->getInfos('height')) {
          $img->crop(0, 0, (int)$img->getInfos('width'), (int)$img->getInfos('width'));
        } else {
          $img->crop(0, 0, (int)$img->getInfos('height'), (int)$img->getInfos('height'));
        }

        if ($img->getInfos('width') > 512) {
          $img->resize(512, 512);
        }

        $gambar = "pages/upload/" . uniqid('photo') . "." . $img->getInfos('type');
        //$gambar = "pages/upload/".$_POST['no_rkm_medis'].".".$img->getInfos('type');
      }

    }

    //if (($photo = isset_or($_FILES['webcam']['tmp_name'], false)) || !$_POST['no_rkm_medis']) {
    if ($photo = isset_or($_FILES['webcam']['tmp_name'], false)) {
      $img = new \Systems\Lib\Image;
      if ($img->load($photo)) {
        if ($img->getInfos('width') < $img->getInfos('height')) {
          $img->crop(0, 0, (int)$img->getInfos('width'), (int)$img->getInfos('width'));
        } else {
          $img->crop(0, 0, (int)$img->getInfos('height'), (int)$img->getInfos('height'));
        }

        if ($img->getInfos('width') > 512) {
          $img->resize(512, 512);
        }

        $gambar = "pages/upload/" . uniqid('photo') . "." . $img->getInfos('type');
        //$gambar = "pages/upload/".$_POST['no_rkm_medis'].".".$img->getInfos('type');
      }

    }

    if ($personal_pasien == 0) {
      $this->db()->pdo()->prepare("INSERT INTO `personal_pasien` (`no_rkm_medis`, `gambar`, `password`) VALUES (?, ?, AES_ENCRYPT(?,'windi'))")->execute([$_POST['no_rkm_medis'], $gambar, $_POST['no_rkm_medis']]);
    } else {
      $this->db('personal_pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->update(['gambar' => $gambar]);
    }

    if (isset($img) && $img->getInfos('width')) {
      if ($personal_pasien) {
        unlink(WEBAPPS_PATH . "/photopasien/" . $personal_pasien['gambar']);
      }

      $img->save(WEBAPPS_PATH . "/photopasien/" . $gambar);

      echo '<img src="' . WEBAPPS_URL . '/photopasien/' . $gambar . '" height="100" width="100" />';

    }

    exit();

  }

  public function postHapus()
  {
    $mlite_crud_permissions = $this->core->loadCrudPermissions('pasien');
    if ($mlite_crud_permissions['can_delete'] == 'false') {
      echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus data!']);
      exit();
    }

    try {
      $query = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->delete();
      if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data']);
      }
    } catch (\Throwable $e) {
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
  }

  public function getCetakKartu($no_rkm_medis)
  {
    $kartu = [
      'settings' => $this->settings('settings'),
      'pasien' => $this->db('pasien')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->oneArray()
    ];

    // Render HTML langsung (tanpa echo & tmp)
    $html = $this->draw('kartu.html', [
      'kartu' => $kartu
    ]);

    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'format' => [100, 70],
      'margin_left' => 4,
      'margin_right' => 4,
      'margin_top' => 4,
      'margin_bottom' => 4
    ]);

    $mpdf->WriteHTML($html);
    $mpdf->Output();

    exit;
  }


  public function getFolder($no_rkm_medis, $no_rawat = '')
  {
    $this->_addHeaderFiles();
    $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
    $reg_periksa = $this->db('reg_periksa')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
      ->where('no_rkm_medis', $no_rkm_medis)
      ->toArray();
    $no_rawat_array = [];
    foreach ($reg_periksa as $row) {
      $no_rawat_array[] = $row['no_rawat'];
    }
    $berkas_digital_perawatan = [];
    if (!empty($no_rawat_array)) {
      $berkas_digital_perawatan = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode = berkas_digital_perawatan.kode')
        ->in('no_rawat', $no_rawat_array)
        ->toArray();
    }
    if ($no_rawat) {
      $berkas_digital_perawatan = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode = berkas_digital_perawatan.kode')
        ->where('no_rawat', revertNoRawat($no_rawat))
        ->toArray();
    }
    $master_berkas_digital = $this->db('master_berkas_digital')->toArray();
    return $this->draw('folder.html', ['pasien' => $pasien, 'reg_periksa' => $reg_periksa, 'berkas_digital_perawatan' => $berkas_digital_perawatan, 'master_berkas_digital' => htmlspecialchars_array($master_berkas_digital)]);
  }

  public function postSaveBerkasDigital()
  {

    if (MULTI_APP) {

      $curl = curl_init();
      $filePath = $_FILES['file']['tmp_name'];

      curl_setopt_array($curl, array(
        CURLOPT_URL => substr(rtrim(WEBAPPS_URL, '/'), 0, strrpos(rtrim(WEBAPPS_URL, '/'), '/')) . '/api/berkasdigital',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('file' => new \CURLFILE($filePath), 'token' => $this->settings->get('api.berkasdigital_key'), 'no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode']),
        CURLOPT_HTTPHEADER => array(),
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      $json = json_decode($response, true);
      if ($json['status'] == 'Success') {
        echo '<br><img src="' . WEBAPPS_URL . '/berkasrawat/' . $json['msg'] . '" width="150" />';
      } else {
        echo 'Gagal menambahkan gambar';
      }

    } else {
      $dir = $this->_uploads;
      $cntr = 0;

      $id = convertNorawat($_POST['no_rawat']);
      $temp = explode(".", $_FILES["file"]["name"]);
      $extension = strtolower(end($temp));
      $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

      if (!in_array($extension, $allowed_extensions)) {
        echo 'Format file tidak diizinkan';
        exit();
      }

      $imgName = time() . $cntr++;
      $lokasi_file = 'pages/upload/' . $id . '_' . $imgName . '.' . $extension;
      $FileName = $id . '_' . $imgName . '.' . $extension;
      $tmpFileName = $_FILES['file']['tmp_name'];
      $uploaded = move_uploaded_file($tmpFileName, $dir . '/' . $FileName);
      if ($uploaded) {
        $query = $this->db('berkas_digital_perawatan')->save(['no_rawat' => $_POST['no_rawat'], 'kode' => $_POST['kode'], 'lokasi_file' => $lokasi_file]);
        if ($query) {
          echo '<br><img src="' . WEBAPPS_URL . '/berkasrawat/' . htmlspecialchars($lokasi_file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" width="150" />';
        }
      } else {
        echo 'Upload gagal';
      }
    }
    exit();

  }

  public function postHapusBerkasDigital()
  {
    if (file_exists(UPLOADS . '/berkasrawat/' . $_POST['lokasi_file'])) {
      $hapus = unlink(UPLOADS . '/berkasrawat/' . $_POST['lokasi_file']);
      if ($hapus) {
        $this->db('berkas_digital_perawatan')->where('lokasi_file', $_POST['lokasi_file'])->delete();
      }
    }
    exit();
  }

  public function getDownloadBerkasDigital()
  {
    $file_path_param = $_GET['lokasi_file'];

    // Basic validation to prevent directory traversal
    if (strpos($file_path_param, '..') !== false || strpos($file_path_param, '/') !== false || strpos($file_path_param, '\\') !== false) {
      echo 'Akses ditolak.';
      exit();
    }

    $file_name = $file_path_param;
    $file_url = WEBAPPS_URL . '/berkasrawat/' . $file_name;

    // Validate that the file exists and is within the allowed directory
    $real_path = realpath(UPLOADS . '/berkasrawat/' . $file_name);
    if ($real_path === false || strpos($real_path, realpath(UPLOADS . '/berkasrawat/')) !== 0) {
      echo 'File tidak ditemukan.';
      exit();
    }

    // Configure.
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $file_name . "\"");

    // Actual download.
    readfile($real_path);

    exit();
  }

  public function getVclaim_ByNoKartu($noKartu, $tglPelayananSEP)
  {
    $url = url([ADMIN, 'vclaim', 'bynokartu', $noKartu, $tglPelayananSEP]);
    echo $this->draw('vclaim.bynokartu.html', ['url' => $url]);
    exit();
  }

  public function getVclaim_ByNIK($nik, $tglPelayananSEP)
  {
    $url = url([ADMIN, 'vclaim', 'bynik', $nik, $tglPelayananSEP]);
    echo $this->draw('vclaim.bynik.html', ['url' => $url]);
    exit();
  }

  public function getPcare_ByNoKartu($noKartu)
  {
    $url = url([ADMIN, 'pcare', 'byjeniskartu', 'noka', $noKartu]);
    echo $this->draw('pcare.bynokartu.html', ['url' => $url]);
    exit();
  }

  public function getPcare_ByNIK($nik)
  {
    $url = url([ADMIN, 'pcare', 'byjeniskartu', 'nik', $nik]);
    echo $this->draw('pcare.bynik.html', ['url' => $url]);
    exit();
  }

  public function getRiwayatPerawatan($no_rkm_medis)
  {
    $riwayat = $this->_getRiwayatData($no_rkm_medis);
    $riwayat['focus_rawat'] = trim((string)($_GET['focus_rawat'] ?? ''));
    $this->tpl->set('riwayat', $this->tpl->noParse_array(htmlspecialchars_array($riwayat)));
    echo $this->draw('riwayat.perawatan.html');
    exit();
  }

  public function getPdfRiwayatPerawatan($no_rkm_medis, $no_rawat = null)
  {
    $username = $this->core->checkAuth('GET');
    if (!$this->core->checkPermission($username, 'can_read', 'pasien')) {
      http_response_code(403);
      echo 'Forbidden';
      exit;
    }

    $data = $this->_getRiwayatData($no_rkm_medis, $no_rawat);
    if (empty($data['pasien'])) {
      http_response_code(404);
      echo 'Data pasien tidak ditemukan';
      exit;
    }

    // 📁 Direktori cache PDF
    $baseDir = BASE_DIR . '/uploads/sertisign';
    if (!is_dir($baseDir)) {
      mkdir($baseDir, 0777, true);
    }

    $safeRawat = preg_replace('/[^A-Za-z0-9]/', '', (string) $no_rawat);
    $fileName = 'Riwayat_Perawatan_' . $data['pasien']['no_rkm_medis'] . '_' . $safeRawat . '.pdf';
    $filePath = $baseDir . '/' . $fileName;

    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'format' => 'A4'
    ]);

    $mpdf->WriteHTML($this->_renderHtmlRiwayat($data));
    $mpdf->Output($filePath, 'F'); // ✅ SIMPAN KE FILE

    // 📤 Tampilkan ke browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));

    readfile($filePath);
    exit;
  }

  private function _renderHtmlRiwayat(array $data)
  {
    $pasien = $data['pasien'];
    $reg = $data['reg_periksa'];

    // helper placeholder
    $emptyRow = function ($colspan, $text = '— Tidak ada data —') {
      return '<tr><td colspan="' . $colspan . '" align="center"><i>' . $text . '</i></td></tr>';
    };

    $html = '
        <style>
            body { font-family: sans-serif; font-size: 9pt; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 8px; }
            th, td { border: 1px solid #000; padding: 3px; vertical-align: top; }
            th { background: #f0f0f0; }
            h2 { text-align:center; margin-bottom: 10px; }
            h3 { margin: 8px 0 4px; background:#ddd; padding:4px; }
            b { display:block; margin-top:6px; }
            .sertisign { display:inline-block; padding:2px 4px; background:#f0f0f0; margin-top:60px; }
        </style>

        <h2>RIWAYAT PERAWATAN PASIEN</h2>

        <h3>Identitas Pasien</h3>
        <table>
            <tr><td>No RM</td><td>' . $pasien['no_rkm_medis'] . '</td><td>Nama</td><td>' . $pasien['nm_pasien'] . '</td></tr>
            <tr><td>NIK</td><td>' . $pasien['no_ktp'] . '</td><td>JK</td><td>' . $pasien['jk'] . '</td></tr>
            <tr><td>TTL</td><td>' . $pasien['tmp_lahir'] . ', ' . $pasien['tgl_lahir'] . '</td><td>Umur</td><td>' . $pasien['umur'] . '</td></tr>
            <tr><td>Alamat</td><td colspan="3">' . $pasien['alamat'] . '</td></tr>
            <tr><td>Agama</td><td>' . $pasien['agama'] . '</td><td>Status Nikah</td><td>' . $pasien['stts_nikah'] . '</td></tr>
            <tr><td>Pekerjaan</td><td>' . $pasien['pekerjaan'] . '</td><td>No Telp</td><td>' . $pasien['no_tlp'] . '</td></tr>
            <tr><td>No Peserta</td><td>' . $pasien['no_peserta'] . '</td><td>Penjamin</td><td>' . $pasien['kd_pj'] . '</td></tr>
        </table>

        <h3>Penanggung Jawab</h3>
        <table>
            <tr><td>Nama</td><td>' . $pasien['namakeluarga'] . '</td><td>Hubungan</td><td>' . $pasien['keluarga'] . '</td></tr>
            <tr><td>Pekerjaan</td><td>' . $pasien['pekerjaanpj'] . '</td><td>Alamat</td><td>' . $pasien['alamatpj'] . '</td></tr>
            <tr><td>Kelurahan</td><td>' . $pasien['kelurahanpj'] . '</td><td>Kecamatan</td><td>' . $pasien['kecamatanpj'] . '</td></tr>
            <tr><td>Kabupaten</td><td>' . $pasien['kabupatenpj'] . '</td><td>Propinsi</td><td>' . $pasien['propinsipj'] . '</td></tr>
        </table>
        ';

    foreach ($reg as $r) {

      $html .= '
            <h3>Kunjungan: ' . $r['no_rawat'] . '</h3>

            <table>
                <tr><td>Tanggal</td><td>' . $r['tgl_registrasi'] . ' ' . $r['jam_reg'] . '</td><td>Status</td><td>' . $r['stts'] . '</td></tr>
                <tr><td>Poli</td><td>' . $r['nm_poli'] . '</td><td>Status Bayar</td><td>' . $r['status_bayar'] . '</td></tr>
                <tr><td>Dokter</td><td colspan="3">' . $r['nm_dokter'] . '</td></tr>
            </table>';

      /* ===== DIAGNOSA ===== */
      $html .= '<b>Diagnosa</b><table>
            <tr><th>Kode</th><th>Nama</th><th>Keterangan</th></tr>';
      if (!empty($r['diagnosa_pasien'])) {
        foreach ($r['diagnosa_pasien'] as $d) {
          $html .= '<tr>
                        <td>' . $d['kd_penyakit'] . '</td>
                        <td>' . $d['nm_penyakit'] . '</td>
                        <td>' . $d['keterangan'] . '</td>
                    </tr>';
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== PROSEDUR ===== */
      $html .= '<b>Prosedur Pasien</b><table>
            <tr><th>Kode</th><th>Deskripsi</th><th>Status</th></tr>';
      if (!empty($r['prosedur_pasien'])) {
        foreach ($r['prosedur_pasien'] as $p) {
          $html .= '<tr>
                        <td>' . $p['kode'] . '</td>
                        <td>' . $p['deskripsi_panjang'] . '</td>
                        <td>' . $p['status'] . '</td>
                    </tr>';
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== PEMERIKSAAN RALAN ===== */
      $html .= '<b>Pemeriksaan Rawat Jalan</b><table>
            <tr><th>Tanggal</th><th>Tensi</th><th>Nadi</th><th>Resp</th><th>GCS</th><th>Keluhan</th></tr>';
      if (!empty($r['pemeriksaan_ralan'])) {
        foreach ($r['pemeriksaan_ralan'] as $p) {
          $html .= '<tr>
                        <td>' . $p['tgl_perawatan'] . ' ' . $p['jam_rawat'] . '</td>
                        <td>' . $p['tensi'] . '</td>
                        <td>' . $p['nadi'] . '</td>
                        <td>' . $p['respirasi'] . '</td>
                        <td>' . $p['gcs'] . '</td>
                        <td>' . $p['keluhan'] . '</td>
                    </tr>';
        }
      } else
        $html .= $emptyRow(6);
      $html .= '</table>';

      /* ===== LAB ===== */
      $html .= '<b>Laboratorium</b><table>
            <tr><th>Pemeriksaan</th><th>Nilai</th><th>Rujukan</th></tr>';
      if (!empty($r['periksa_lab'])) {
        foreach ($r['periksa_lab'] as $lab) {
          foreach ($lab['detail_periksa_lab'] as $d) {
            $html .= '<tr>
                            <td>' . $d['Pemeriksaan'] . '</td>
                            <td>' . $d['nilai'] . ' ' . $d['satuan'] . '</td>
                            <td>' . $d['nilai_rujukan'] . '</td>
                        </tr>';
          }
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== RADIOLOGI ===== */
      $html .= '<b>Radiologi</b><table>
            <tr><th>Jenis</th><th>Hasil</th><th>Keterangan</th></tr>';
      if (!empty($r['periksa_radiologi'])) {
        foreach ($r['periksa_radiologi'] as $rad) {
          foreach ($rad['hasil_radiologi'] as $h) {
            $html .= '<tr>
                            <td>Radiologi</td>
                            <td>' . $h['hasil'] . '</td>
                            <td>' . $h['tgl_periksa'] . ' ' . $h['jam'] . '</td>
                        </tr>';
          }
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== OBAT ===== */
      $html .= '<b>Pemberian Obat</b><table>
            <tr><th>Nama</th><th>Jumlah</th><th>Total</th></tr>';
      if (!empty($r['pemberian_obat'])) {
        foreach ($r['pemberian_obat'] as $o) {
          foreach ($o['data_pemberian_obat'] as $ob) {
            $html .= '<tr>
                            <td>' . $ob['nama_brng'] . '</td>
                            <td>' . $ob['jml'] . '</td>
                            <td>' . number_format($ob['total'], 0, ',', '.') . '</td>
                        </tr>';
          }
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== CATATAN ===== */
      $html .= '<b>Catatan Perawatan</b><table>
            <tr><th>Tanggal</th><th>Catatan</th><th>Petugas</th></tr>';
      if (!empty($r['catatan_perawatan'])) {
        foreach ($r['catatan_perawatan'] as $c) {
          $html .= '<tr>
                        <td>' . $c['tanggal'] . '</td>
                        <td>' . $c['catatan'] . '</td>
                        <td>' . $c['petugas'] . '</td>
                    </tr>';
        }
      } else
        $html .= $emptyRow(3);
      $html .= '</table>';

      /* ===== RESUME ===== */
      if (!empty($r['resume_pasien'])) {
        foreach ($r['resume_pasien'] as $res) {
          $html .= '<b>Resume Medis</b><table>
                    <tr><td>Diagnosa Utama</td><td>' . $res['diagnosa_utama'] . '</td></tr>
                    <tr><td>Prosedur Utama</td><td>' . $res['prosedur_utama'] . '</td></tr>
                    <tr><td>Kondisi Pulang</td><td>' . $res['kondisi_pulang'] . '</td></tr>
                    <tr><td>Dokter</td><td>' . $res['nm_dokter'] . '</td></tr>
                    </table>';
        }
      }

      $html .= '
            <h3>Kunjungan: ' . $r['no_rawat'] . ' (' . $r['tgl_registrasi'] . ')</h3>
            <div class="sertisign">#1A</div>
            ';
    }

    return $html;
  }

  public function apiRiwayatPerawatan($no_rkm_medis = null, $no_rawat = null)
  {
    $username = $this->core->checkAuth('GET');
    if (!$this->core->checkPermission($username, 'can_read', 'pasien')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rkm_medis) {
      return ['status' => 'error', 'message' => 'no_rkm_medis required'];
    }

    if (!$no_rawat && isset($_GET['no_rawat'])) {
      $no_rawat = $_GET['no_rawat'];
    }

    $detail = isset($_GET['detail']) && $_GET['detail'] === 'false' ? false : true;

    $data = $this->_getRiwayatData($no_rkm_medis, $no_rawat, $detail);
    if (!$data['pasien']) {
      return ['status' => 'error', 'message' => 'Pasien not found'];
    }

    if ($no_rawat && empty($data['reg_periksa'])) {
      return ['status' => 'error', 'message' => 'No rawat not found'];
    }

    return ['status' => 'success', 'data' => $data];
  }

  private function _getRiwayatData($no_rkm_medis, $no_rawat = null, $detail = true)
  {
    $riwayat['settings'] = $this->settings('settings');
    $riwayat['pasien'] = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
    $regQuery = $this->db('reg_periksa')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
      ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
      ->where('no_rkm_medis', $no_rkm_medis);

    // 🔹 FILTER JIKA no_rawat DIISI
    if ($no_rawat) {
      $regQuery->where('reg_periksa.no_rawat', revertNorawat($no_rawat));
    }

    $reg_periksa = $regQuery
      ->desc('tgl_registrasi')
      ->desc('jam_reg')
      ->toArray();

    if ($detail == false) {
      $riwayat['reg_periksa'] = $reg_periksa;
      return $riwayat;
    }

    // Batch pre-load Formulir RME yang terisi lintas kunjungan pasien (1 query per tabel RME)
    $pdo = $this->db()->pdo();
    $noRawatList = array_values(array_unique(array_filter(array_column($reg_periksa, 'no_rawat'))));
    $rmeFormsByRawat = [];
    $rsgmTablesCache = [];

    if (!empty($noRawatList) && DBDRIVER == 'mysql') {
      try {
        $existingTables = $this->_getExistingTablesMap($pdo);
        foreach ($existingTables as $tblName => $_unused) {
          if (strpos($tblName, 'rsgm__') === 0) {
            $rsgmTablesCache[] = $tblName;
          }
        }

        if (!empty($existingTables['mlite_rme_form'])) {
          $rmeMetaList = $this->db('mlite_rme_form')
            ->where('status', '1')
            ->asc('kategori')
            ->asc('urutan')
            ->toArray();

          $inPlaceholders = implode(',', array_fill(0, count($noRawatList), '?'));
          $rowsByTable = [];

          foreach ($rmeMetaList as $mf) {
            $tbl = trim((string)($mf['nama_tabel'] ?? ''));
            if ($tbl === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $tbl) || empty($existingTables[$tbl])) {
              continue;
            }
            if (!array_key_exists($tbl, $rowsByTable)) {
              $rowsByTable[$tbl] = [];
              try {
                $stRows = $pdo->prepare("SELECT * FROM `{$tbl}` WHERE `no_rawat` IN ({$inPlaceholders})");
                $stRows->execute($noRawatList);
                foreach ($stRows->fetchAll(\PDO::FETCH_ASSOC) as $entry) {
                  $nrKey = (string)($entry['no_rawat'] ?? '');
                  if ($nrKey !== '') {
                    $rowsByTable[$tbl][$nrKey][] = $entry;
                  }
                }
                foreach ($rowsByTable[$tbl] as $nrKey => &$nrEntries) {
                  usort($nrEntries, function ($a, $b) {
                    $idA = (int)($a['id'] ?? ($a['id_triase'] ?? 0));
                    $idB = (int)($b['id'] ?? ($b['id_triase'] ?? 0));
                    return $idB <=> $idA;
                  });
                }
                unset($nrEntries);
              } catch (\Throwable $e) {
                $rowsByTable[$tbl] = [];
              }
            }

            foreach ($noRawatList as $nrKey) {
              $entries = $rowsByTable[$tbl][$nrKey] ?? [];
              if (empty($entries)) {
                continue;
              }
              $lastRow = null;
              foreach ($entries as $cand) {
                if (empty($cand['deleted_at'])) {
                  $lastRow = $cand;
                  break;
                }
              }
              if ($lastRow === null) {
                $lastRow = $entries[0];
              }
              $isRegisteredRev = class_exists('\Systems\Lib\RmeFormRevision') && \Systems\Lib\RmeFormRevision::isRegisteredTable($tbl);
              if ($isRegisteredRev && is_array($lastRow)) {
                $lastRow = \Systems\Lib\RmeFormRevision::enrichSingleRow($this->core, $tbl, $lastRow);
              }
              $rowId = (int)($lastRow['id'] ?? ($lastRow['id_triase'] ?? 0));
              $routeForm = trim((string)($mf['route_form'] ?? ''));
              $routeCetak = trim((string)($mf['route_cetak'] ?? ''));
              if ($routeCetak === '' && $routeForm !== '') {
                $routeCetak = str_replace('form', 'cetak', $routeForm);
              }
              $hasTte = false;
              foreach ($lastRow as $lk => $lv) {
                if ((strpos((string)$lk, 'tte_') === 0 || strpos((string)$lk, 'ttd_') === 0) && is_string($lv) && preg_match('/^[a-f0-9]{64}$/i', trim($lv))) {
                  $hasTte = true;
                  break;
                }
              }
              if (!$hasTte && !empty($lastRow['form_data']) && preg_match('/[a-f0-9]{64}/i', (string)$lastRow['form_data'])) {
                $hasTte = true;
              }

              $urlForm = $routeForm !== '' ? url([ADMIN, 'form_rme', $routeForm]) . ($rowId > 0 ? '&id=' . $rowId : '') . '&no_rawat=' . urlencode($nrKey) : '';
              $urlCetak = $routeCetak !== '' ? url([ADMIN, 'form_rme', $routeCetak]) . ($rowId > 0 ? '&id=' . $rowId : '') . '&no_rawat=' . urlencode($nrKey) : '';

              $rmeFormsByRawat[$nrKey][] = [
                'id'               => $rowId,
                'kode_form'        => (string)($mf['kode_form'] ?? ''),
                'nama_form'        => (string)($mf['nama_form'] ?? ''),
                'kategori'         => (string)($mf['kategori'] ?? 'Umum'),
                'nama_tabel'       => $tbl,
                'fill_count'       => count($entries),
                'revision_count'   => (int)($lastRow['revision_count'] ?? 0),
                'is_voided'        => !empty($lastRow['is_voided']) ? '1' : '',
                'is_tte_outdated'  => !empty($lastRow['is_tte_outdated']) ? '1' : '',
                'has_tte'          => $hasTte ? '1' : '',
                'last_revised_at'  => (string)($lastRow['last_revised_at_fmt'] ?? ''),
                'last_revised_by'  => (string)($lastRow['last_revised_by'] ?? ''),
                'url_form'         => $urlForm,
                'url_cetak'        => $urlCetak,
                'url_cetak_pdf'    => $urlCetak !== '' ? $urlCetak . '&pdf=1' : '',
                'raw_row'          => $lastRow,
              ];
            }
          }
        }
      } catch (\Throwable $e) {
      }
    }

    $tagsByRawat = (DBDRIVER == 'mysql' && !empty($reg_periksa))
      ? $this->_extractJourneyTagsForVisits($pdo, $reg_periksa)
      : [];

    $totalRmeTerisi = 0;
    $alergiList = [];
    $collectAlergi = function ($val) use (&$alergiList) {
      $val = trim((string)$val);
      if ($val === '' || in_array(strtolower($val), ['-', 'tidak', 'tidak ada', 'none', '0', 'disangkal'], true)) {
        return;
      }
      if (!in_array($val, $alergiList, true)) {
        $alergiList[] = $val;
      }
    };

    $riwayat['reg_periksa'] = [];
    foreach ($reg_periksa as $row) {

      $row['diagnosa_pasien'] = $this->db('diagnosa_pasien')
        ->join('penyakit', 'penyakit.kd_penyakit=diagnosa_pasien.kd_penyakit')
        ->where('no_rawat', $row['no_rawat'])
        ->asc('prioritas')
        ->toArray();
      $row['prosedur_pasien'] = $this->db('prosedur_pasien')
        ->join('icd9', 'icd9.kode=prosedur_pasien.kode')
        ->where('no_rawat', $row['no_rawat'])
        ->asc('prioritas')
        ->toArray();
      $ralanRaw = $this->db('pemeriksaan_ralan')
        ->join('pegawai', 'pegawai.nik=pemeriksaan_ralan.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $row['pemeriksaan_ralan'] = class_exists('\Systems\Lib\SoapRevision')
        ? \Systems\Lib\SoapRevision::enrichRows($this->core, 'pemeriksaan_ralan', $ralanRaw)
        : $ralanRaw;
      $row['rawat_jl_dr'] = $this->db('rawat_jl_dr')
        ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_dr.kd_jenis_prw')
        ->join('dokter', 'dokter.kd_dokter=rawat_jl_dr.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $row['rawat_jl_pr'] = $this->db('rawat_jl_pr')
        ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_pr.kd_jenis_prw')
        ->join('petugas', 'petugas.nip=rawat_jl_pr.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $rawat_jl_drpr_data = $this->db('rawat_jl_drpr')
        ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw=rawat_jl_drpr.kd_jenis_prw')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $row['rawat_jl_drpr'] = [];
      foreach ($rawat_jl_drpr_data as $drpr_item) {
        $dokter = $this->db('dokter')->where('kd_dokter', $drpr_item['kd_dokter'])->oneArray();
        $petugas = $this->db('petugas')->where('nip', $drpr_item['nip'])->oneArray();
        $drpr_item['nm_dokter'] = $dokter['nm_dokter'] ?? '';
        $drpr_item['nama'] = $petugas['nama'] ?? '';
        $row['rawat_jl_drpr'][] = $drpr_item;
      }
      $row['pemeriksaan_ranap'] = [];
      $row['rawat_inap_dr'] = [];
      $row['rawat_inap_pr'] = [];
      $row['rawat_inap_drpr'] = [];

      $ranapRaw = $this->db('pemeriksaan_ranap')
        ->join('pegawai', 'pegawai.nik=pemeriksaan_ranap.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $row['pemeriksaan_ranap'] = class_exists('\Systems\Lib\SoapRevision')
        ? \Systems\Lib\SoapRevision::enrichRows($this->core, 'pemeriksaan_ranap', $ranapRaw)
        : $ranapRaw;
      $row['rawat_inap_dr'] = $this->db('rawat_inap_dr')
        ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_dr.kd_jenis_prw')
        ->join('dokter', 'dokter.kd_dokter=rawat_inap_dr.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $row['rawat_inap_pr'] = $this->db('rawat_inap_pr')
        ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_pr.kd_jenis_prw')
        ->join('petugas', 'petugas.nip=rawat_inap_pr.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      $rawat_inap_drpr_data = $this->db('rawat_inap_drpr')
        ->join('jns_perawatan_inap', 'jns_perawatan_inap.kd_jenis_prw=rawat_inap_drpr.kd_jenis_prw')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_perawatan')
        ->desc('jam_rawat')
        ->toArray();
      foreach ($rawat_inap_drpr_data as $inap_drpr_item) {
        $dokter = $this->db('dokter')->where('kd_dokter', $inap_drpr_item['kd_dokter'])->oneArray();
        $petugas = $this->db('petugas')->where('nip', $inap_drpr_item['nip'])->oneArray();
        $inap_drpr_item['nm_dokter'] = $dokter['nm_dokter'] ?? '';
        $inap_drpr_item['nama'] = $petugas['nama'] ?? '';
        $row['rawat_inap_drpr'][] = $inap_drpr_item;
      }

      $rows_periksa_lab = $this->db('periksa_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_periksa')
        ->desc('jam')
        ->toArray();

      $row['periksa_lab'] = [];
      foreach ($rows_periksa_lab as $value) {
        $value['detail_periksa_lab'] = $this->db('detail_periksa_lab')
          ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
          ->where('detail_periksa_lab.no_rawat', $value['no_rawat'])
          ->where('detail_periksa_lab.kd_jenis_prw', $value['kd_jenis_prw'])
          ->where('tgl_periksa', $value['tgl_periksa'])
          ->where('jam', $value['jam'])
          ->toArray();
        $row['periksa_lab'][] = $value;
      }

      $row['periksa_radiologi'] = [];
      $radiologi_sessions = $this->db('periksa_radiologi')
        ->select(['tgl_periksa', 'jam', 'nip', 'kd_jenis_prw'])
        ->where('periksa_radiologi.no_rawat', $row['no_rawat'])
        ->group('tgl_periksa')
        ->group('jam')
        ->group('nip')
        ->group('kd_jenis_prw')
        ->desc('tgl_periksa')
        ->desc('jam')
        ->toArray();

      foreach ($radiologi_sessions as $radiologi_session) {
        $radiologi_session['no_rawat'] = $row['no_rawat'];
        $radiologi_session['pemeriksaan_radiologi'] = $this->db('periksa_radiologi')
          ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw=periksa_radiologi.kd_jenis_prw')
          ->where('no_rawat', $row['no_rawat'])
          ->where('tgl_periksa', $radiologi_session['tgl_periksa'])
          ->where('jam', $radiologi_session['jam'])
          ->asc('periksa_radiologi.kd_jenis_prw')
          ->toArray();
        $radiologi_session['hasil_radiologi'] = $this->db('hasil_radiologi')
          ->where('no_rawat', $row['no_rawat'])
          ->where('tgl_periksa', $radiologi_session['tgl_periksa'])
          ->where('jam', $radiologi_session['jam'])
          ->toArray();
        $radiologi_session['gambar_radiologi'] = $this->db('gambar_radiologi')
          ->where('no_rawat', $row['no_rawat'])
          ->where('tgl_periksa', $radiologi_session['tgl_periksa'])
          ->where('jam', $radiologi_session['jam'])
          ->toArray();
        $row['periksa_radiologi'][] = $radiologi_session;
      }

      $row['mini_pacs'] = $this->db('mlite_mini_pacs_study')->where('no_rawat', $row['no_rawat'])->toArray();
      $mini_pacs_result = [];
      foreach ($row['mini_pacs'] as $mp) {
        $mp['series'] = $this->db('mlite_mini_pacs_series')->where('study_id', $mp['id'])->toArray();
        foreach ($mp['series'] as &$series) {
          $series['instances'] = $this->db('mlite_mini_pacs_instance')->where('series_id', $series['id'])->toArray();
        }
        $mini_pacs_result[] = $mp;
      }
      $row['mini_pacs'] = $mini_pacs_result;

      $pemberian_obat_sessions = $this->db('detail_pemberian_obat')
        ->select(['tgl_perawatan', 'jam'])
        ->where('no_rawat', $row['no_rawat'])
        ->group('tgl_perawatan')
        ->group('jam')
        ->desc('tgl_perawatan')
        ->desc('jam')
        ->toArray();

      $row['pemberian_obat'] = [];
      foreach ($pemberian_obat_sessions as $obat_session) {
        $obat_session['no_rawat'] = $row['no_rawat'];
        $obat_session['data_pemberian_obat'] = $this->db('detail_pemberian_obat')
          ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
          ->where('detail_pemberian_obat.no_rawat', $row['no_rawat'])
          ->where('detail_pemberian_obat.tgl_perawatan', $obat_session['tgl_perawatan'])
          ->where('detail_pemberian_obat.jam', $obat_session['jam'])
          ->asc('detail_pemberian_obat.kode_brng')
          ->toArray();
        $row['pemberian_obat'][] = $obat_session;
      }

      $row['operasi'] = $this->db('operasi')
        ->join('paket_operasi', 'paket_operasi.kode_paket=operasi.kode_paket')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tgl_operasi')
        ->toArray();

      $row['obat_operasi'] = $this->db('beri_obat_operasi')
        ->join('obatbhp_ok', 'obatbhp_ok.kd_obat=beri_obat_operasi.kd_obat')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['catatan_perawatan'] = $this->db('catatan_perawatan')->where('no_rawat', $row['no_rawat'])->oneArray();
      $row['berkas_digital'] = $this->db('berkas_digital_perawatan')
        ->join('master_berkas_digital', 'master_berkas_digital.kode=berkas_digital_perawatan.kode')
        ->where('no_rawat', $row['no_rawat'])
        ->toArray();

      $row['penilaian_medis_ralan'] = $this->db('penilaian_medis_ralan')
        ->join('dokter', 'dokter.kd_dokter=penilaian_medis_ralan.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->toArray();

      $row['penilaian_medis_igd'] = $this->db('penilaian_medis_igd')
        ->join('dokter', 'dokter.kd_dokter=penilaian_medis_igd.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['penilaian_medis_ranap'] = $this->db('penilaian_medis_ranap')
        ->join('dokter', 'dokter.kd_dokter=penilaian_medis_ranap.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['triase_igd'] = $this->db('mlite_triase_igd')
        ->leftJoin('petugas', 'petugas.nip=mlite_triase_igd.petugas_id')
        ->select('mlite_triase_igd.*, petugas.nama as nama_petugas')
        ->where('mlite_triase_igd.no_rawat', $row['no_rawat'])
        ->toArray();
      foreach ($row['triase_igd'] as &$tr) {
        $tr['ats_items_text'] = !empty($tr['ats_items']) ? implode(', ', json_decode($tr['ats_items'], true) ?: []) : '-';
        $tr['sdcep_items_text'] = !empty($tr['sdcep_items']) ? implode(', ', json_decode($tr['sdcep_items'], true) ?: []) : '-';
        $tr['doa_items_text'] = !empty($tr['doa_items']) ? implode(', ', json_decode($tr['doa_items'], true) ?: []) : '-';
      }

      if (file_exists(UPLOADS . '/lokalis/lokalis_' . convertNoRawat($row['no_rawat']) . '.png')) {
        $row['lokalis'] = url(UPLOADS . '/lokalis/lokalis_' . convertNoRawat($row['no_rawat']) . '.png');
      } else {
        $row['lokalis'] = '';
      }

      $row['penilaian_keperawatan_igd'] = $this->db('penilaian_awal_keperawatan_igd')
        ->join('petugas', 'petugas.nip=penilaian_awal_keperawatan_igd.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['penilaian_awal_keperawatan_ralan'] = $this->db('penilaian_awal_keperawatan_ralan')
        ->join('petugas', 'petugas.nip=penilaian_awal_keperawatan_ralan.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['penilaian_awal_keperawatan_ranap'] = $this->db('penilaian_awal_keperawatan_ranap')
        ->join('petugas', 'petugas.nip=penilaian_awal_keperawatan_ranap.nip1')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['catatan_adime_gizi'] = $this->db('catatan_adime_gizi')
        ->join('petugas', 'petugas.nip=catatan_adime_gizi.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['penilaian_ulang_nyeri'] = $this->db('penilaian_ulang_nyeri')
        ->join('petugas', 'petugas.nip=penilaian_ulang_nyeri.nip')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['resume_pasien'] = $this->db('resume_pasien')
        ->join('dokter', 'dokter.kd_dokter=resume_pasien.kd_dokter')
        ->where('no_rawat', $row['no_rawat'])
        ->toArray();

      $row['laporan_operasi'] = $this->db('laporan_operasi')
        ->where('no_rawat', $row['no_rawat'])
        ->desc('tanggal')
        ->toArray();

      $row['mlite_odontogram'] = $this->db('mlite_odontogram')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->where('tgl_input', $row['tgl_registrasi'])
        ->desc('tgl_input')
        ->toArray();

      $row['rsgm_data'] = [];
      if (!empty($rsgmTablesCache) && DBDRIVER == 'mysql') {
        foreach ($rsgmTablesCache as $rsgm_table) {
          $data = $this->db($rsgm_table)->where('no_rawat', $row['no_rawat'])->toArray();
          if (!empty($data)) {
            $row['rsgm_data'][$rsgm_table] = $data;
          }
        }
      }

      // Kumpulkan alergi lintas sumber klinis
      foreach ($row['pemeriksaan_ralan'] as $pr) {
        $collectAlergi($pr['alergi'] ?? '');
      }
      foreach ($row['pemeriksaan_ranap'] as $pn) {
        $collectAlergi($pn['alergi'] ?? '');
      }
      foreach ($row['penilaian_medis_ralan'] as $pm) {
        $collectAlergi($pm['alergi'] ?? '');
      }
      foreach ($row['penilaian_medis_igd'] as $pm) {
        $collectAlergi($pm['alergi'] ?? '');
      }
      foreach ($row['penilaian_medis_ranap'] as $pm) {
        $collectAlergi($pm['alergi'] ?? '');
      }

      // Attach Form RME terisi untuk kunjungan ini
      $visitRmeForms = [];
      foreach (($rmeFormsByRawat[$row['no_rawat']] ?? []) as $rfItem) {
        $rawRow = $rfItem['raw_row'] ?? [];
        if (is_array($rawRow)) {
          $collectAlergi($rawRow['alergi_obat'] ?? '');
          $collectAlergi($rawRow['alergi_makanan'] ?? '');
          $collectAlergi($rawRow['alergi_lainnya'] ?? '');
          $collectAlergi($rawRow['riwayat_alergi_ket'] ?? '');
          $collectAlergi($rawRow['rpm_alergi_ket'] ?? '');
          $collectAlergi($rawRow['rp_alergi_ket'] ?? '');
        }
        unset($rfItem['raw_row']);
        $visitRmeForms[] = $rfItem;
      }
      $row['rme_forms'] = $visitRmeForms;
      $totalRmeTerisi += count($visitRmeForms);

      $row['no_rawat_clean'] = convertNorawat($row['no_rawat']);
      $row['url_lembar_kerja'] = url([ADMIN, 'form_rme', 'pasien']) . '&no_rawat=' . urlencode($row['no_rawat']);
      $row['url_pdf_kunjungan'] = url([ADMIN, 'pasien', 'pdfriwayatperawatan', $no_rkm_medis, $row['no_rawat_clean']]);

      $row['rme_count'] = count($visitRmeForms);
      $row['soap_count'] = count($row['pemeriksaan_ralan']) + count($row['pemeriksaan_ranap']);
      $row['obat_count'] = count($row['pemberian_obat']) + count($row['obat_operasi']);
      $row['tindakan_count'] = count($row['rawat_jl_dr']) + count($row['rawat_jl_pr']) + count($row['rawat_jl_drpr']) + count($row['rawat_inap_dr']) + count($row['rawat_inap_pr']) + count($row['rawat_inap_drpr']) + count($row['operasi']);
      $row['penunjang_count'] = count($row['periksa_lab']) + count($row['periksa_radiologi']) + count($row['berkas_digital']) + count($row['mlite_odontogram']) + (!empty($row['lokalis']) ? 1 : 0);
      $row['asesmen_count'] = count($row['penilaian_medis_ralan']) + count($row['penilaian_medis_igd']) + count($row['penilaian_medis_ranap']) + count($row['triase_igd']) + count($row['penilaian_keperawatan_igd']) + count($row['penilaian_awal_keperawatan_ralan']) + count($row['penilaian_awal_keperawatan_ranap']) + count($row['catatan_adime_gizi']) + count($row['penilaian_ulang_nyeri']) + count($row['resume_pasien']) + count($row['laporan_operasi']) + count($row['rsgm_data']) + (!empty($row['catatan_perawatan']) ? 1 : 0);

      $diagSummaryParts = [];
      foreach ($row['diagnosa_pasien'] as $dg) {
        $diagSummaryParts[] = trim(($dg['kd_penyakit'] ?? '') . ' ' . ($dg['nm_penyakit'] ?? ''));
      }
      $row['diagnosa_ringkas'] = implode('; ', $diagSummaryParts);
      $row['journey_tags'] = $tagsByRawat[$row['no_rawat']] ?? [];

      $riwayat['reg_periksa'][] = $row;
    }

    $riwayat['summary'] = [
      'total_kunjungan'  => count($riwayat['reg_periksa']),
      'total_rme_terisi' => $totalRmeTerisi,
      'alergi_text'      => implode(', ', $alergiList),
      'alergi_count'     => count($alergiList),
      'has_alergi'       => !empty($alergiList) ? '1' : '',
    ];

    return $riwayat;
  }

  public function postCetak()
  {
    $this->db()->pdo()->exec("DELETE FROM `mlite_temporary`");
    $cari = isset_or($_POST['cari'], '');
    $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-d'));
    $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));

    $stmt = $this->db()->pdo()->prepare("INSERT INTO `mlite_temporary` (
        `temp1`,
        `temp2`,
        `temp3`,
        `temp4`,
        `temp5`,
        `temp6`,
        `temp7`,
        `temp8`,
        `temp9`,
        `temp10`,
        `temp11`,
        `temp12`,
        `temp13`,
        `temp14`,
        `temp15`,
        `temp16`,
        `temp17`,
        `temp18`,
        `temp19`,
        `temp20`,
        `temp21`,
        `temp22`,
        `temp23`,
        `temp24`,
        `temp25`,
        `temp26`,
        `temp27`,
        `temp28`,
        `temp29`,
        `temp30`,
        `temp31`,
        `temp32`,
        `temp33`,
        `temp34`,
        `temp35`,
        `temp36`
      )
      SELECT *
      FROM `pasien`
      WHERE (`no_rkm_medis` LIKE ? OR `nm_pasien` LIKE ? OR `alamat` LIKE ?)
      AND `tgl_daftar` BETWEEN ? AND ?
      ");
    $stmt->execute(['%' . $cari . '%', '%' . $cari . '%', '%' . $cari . '%', $tgl_awal, $tgl_akhir]);

    exit();
  }

  public function anyWilayah()
  {
    $show = isset($_GET['show']) ? $_GET['show'] : "";
    switch ($show) {
      default:
        break;
      case "caripropinsi":
        if (isset($_POST["query"])) {
          $output = '';
          $key = "%" . $_POST["query"] . "%";
          $rows = $this->db('propinsi')->like('nm_prop', $key)->asc('kd_prop')->limit(10)->toArray();
          $output = '';
          if (count($rows)) {
            foreach ($rows as $row) {
              $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["kd_prop"]) . ': ' . htmlspecialchars($row["nm_prop"]) . '</li>';
            }
          }
          echo $output;
        }
        break;
      case "carikabupaten":
        if (isset($_POST["query"])) {
          $output = '';
          $key = "%" . $_POST["query"] . "%";
          $rows = $this->db('kabupaten')->like('nm_kab', $key)->asc('kd_kab')->limit(10)->toArray();
          $output = '';
          if (count($rows)) {
            foreach ($rows as $row) {
              $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["kd_kab"]) . ': ' . htmlspecialchars($row["nm_kab"]) . '</li>';
            }
          }
          echo $output;
        }
        break;
      case "carikecamatan":
        if (isset($_POST["query"])) {
          $output = '';
          $key = "%" . $_POST["query"] . "%";
          $rows = $this->db('kecamatan')->like('nm_kec', $key)->asc('kd_kec')->limit(10)->toArray();
          $output = '';
          if (count($rows)) {
            foreach ($rows as $row) {
              $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["kd_kec"]) . ': ' . htmlspecialchars($row["nm_kec"]) . '</li>';
            }
          }
          echo $output;
        }
        break;
      case "carikelurahan":
        if (isset($_POST["query"])) {
          $output = '';
          $key = "%" . $_POST["query"] . "%";
          $rows = $this->db('kelurahan')->like('nm_kel', $key)->asc('kd_kel')->limit(10)->toArray();
          $output = '';
          if (count($rows)) {
            foreach ($rows as $row) {
              $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["kd_kel"]) . ': ' . htmlspecialchars($row["nm_kel"]) . '</li>';
            }
          }
          echo $output;
        }
        break;
    }
    exit();
  }

  public function apiWilayahjson()
  {
    $show = isset($_GET['show']) ? $_GET['show'] : "";
    switch ($show) {
      case "propinsi":
        return $this->db('propinsi')->where('kd_prop', '!=', 1)->asc('nm_prop')->toArray();
      case "kabupaten":
        $kd_prop = isset($_GET['kd_prop']) ? trim((string)$_GET['kd_prop']) : '';
        if (empty($kd_prop) || $kd_prop === '1') {
          return [];
        }
        return $this->db('kabupaten')->like('kd_kab', $kd_prop . '%')->where('kd_kab', '!=', 1)->asc('nm_kab')->toArray();
      case "kecamatan":
        $kd_kab = isset($_GET['kd_kab']) ? trim((string)$_GET['kd_kab']) : '';
        if (empty($kd_kab) || $kd_kab === '1') {
          return [];
        }
        return $this->db('kecamatan')->like('kd_kec', $kd_kab . '%')->where('kd_kec', '!=', 1)->asc('nm_kec')->toArray();
      case "kelurahan":
        $kd_kec = isset($_GET['kd_kec']) ? trim((string)$_GET['kd_kec']) : '';
        if (empty($kd_kec) || $kd_kec === '1') {
          return [];
        }
        return $this->db('kelurahan')->like('kd_kel', $kd_kec . '%')->where('kd_kel', '!=', '1')->asc('nm_kel')->toArray();
    }
    return [];
  }

  public function hitungUmur($tanggal_lahir)
  {
    $birthDate = new \DateTime($tanggal_lahir);
    $today = new \DateTime("today");
    $umur = "0 Th 0 Bl 0 Hr";
    if ($birthDate < $today) {
      $y = $today->diff($birthDate)->y;
      $m = $today->diff($birthDate)->m;
      $d = $today->diff($birthDate)->d;
      $umur = $y . " Th " . $m . " Bl " . $d . " Hr";
    }
    return $umur;
  }

  public function getExportPDF()
  {
    $query = $_GET['query'] ?? '';
    $tgl_awal = $_GET['tgl_awal'] ?? '';
    $tgl_akhir = $_GET['tgl_akhir'] ?? '';
    $filter = $_GET['filter'] ?? '';

    // Pakai QueryWrapper (lebih aman & konsisten)
    $db = $this->db('pasien');

    if ($tgl_awal !== '' && $tgl_akhir !== '') {
      $db->where('tgl_daftar', '>=', $tgl_awal)->where('tgl_daftar', '<=', $tgl_akhir);
    }

    if ($query !== '') {
      $db->like('nm_pasien', "%{$query}%");
    }

    if ($filter !== '') {
      $db->where('kd_pj', $filter);
    }

    $rows = $db->toArray();

    // Render HTML langsung
    $html = $this->draw('pasien.export.pdf.html', [
      'pasien' => $rows
    ]);

    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'orientation' => 'L'
    ]);

    $mpdf->SetHTMLHeader($this->core->setPrintHeader());
    $mpdf->SetHTMLFooter($this->core->setPrintFooter());

    $mpdf->WriteHTML(
      $this->core->setPrintCss(),
      \Mpdf\HTMLParserMode::HEADER_CSS
    );
    $mpdf->WriteHTML(
      $html,
      \Mpdf\HTMLParserMode::HTML_BODY
    );

    $mpdf->Output();
    exit;
  }

  public function getExportXLS()
  {
    echo "Cetak XLS";
    exit();
  }

  public function getJavascript()
  {
    header('Content-type: text/javascript');
    $this->assign['websocket'] = $this->settings->get('settings.websocket');
    $this->assign['websocket_proxy'] = $this->settings->get('settings.websocket_proxy');
    echo $this->draw(MODULES . '/pasien/js/admin/pasien.js', ['mlite' => htmlspecialchars_array($this->assign)]);
    exit();
  }

  private function _addHeaderFiles()
  {
    $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
    $this->core->addJS(url('assets/jscripts/lightbox/lightbox.min.js'));
    $this->core->addCSS(url('assets/jscripts/lightbox/lightbox.min.css'));
    $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
    $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
    $this->core->addJS(url([ADMIN, 'pasien', 'javascript']), 'footer');
  }

  public function getSettings()
  {
    if ($this->core->getUserInfo('role') != 'admin') {
        $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
        redirect(url([ADMIN, 'pasien', 'uploadphoto']));
    }
    $set_no_rkm_medis = $this->db('set_no_rkm_medis')->oneArray();
    return $this->draw('settings.html', ['set_no_rkm_medis' => $set_no_rkm_medis]);
  }

  public function postSaveSettings()
  {
    if ($this->core->getUserInfo('role') != 'admin') {
        $this->notify('failure', 'Anda tidak memiliki hak akses untuk halaman ini.');
        redirect(url([ADMIN, 'pasien', 'uploadphoto']));
    }
    $this->db()->pdo()->exec("DELETE FROM `set_no_rkm_medis`");
    $set_no_rkm_medis = $this->db('set_no_rkm_medis')->save(['no_rkm_medis' => $_POST['set_no_rkm_medis']]);
    if ($set_no_rkm_medis) {
      $this->notify('success', 'Pengaturan telah disimpan');
    } else {
      $this->notify('error', 'Pengaturan gagal disimpan');
    }
    redirect(url([ADMIN, 'pasien', 'settings']));
  }

  public function getCetakPdf()
  {
    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'orientation' => 'L',
      'setAutoTopMargin' => 'stretch'
    ]);

    // header khusus halaman pertama
    $mpdf->DefHTMLHeaderByName(
      'page-header',
      $this->core->setPrintHeader()
    );
    $mpdf->SetHTMLFooter($this->core->setPrintFooter());

    // ambil data
    $cetak = $this->db('mlite_temporary')->toArray();

    // inject ke template
    $this->tpl->set('cetak', $cetak);

    // render template (PATH BENAR)
    $html = $this->draw('cetak.pasien.html');

    // CSS
    $mpdf->WriteHTML(
      $this->core->setPrintCss(),
      \Mpdf\HTMLParserMode::HEADER_CSS
    );

    // BODY
    $mpdf->WriteHTML(
      $html,
      \Mpdf\HTMLParserMode::HTML_BODY
    );

    $mpdf->Output();
    exit;
  }

  public function getCetakRiwayatMpdf()
  {
    $mpdf = new \Mpdf\Mpdf([
      'mode' => 'utf-8',
      'orientation' => 'L'
    ]);

    $css = '
      <style>
        del { 
          display: none;
        }
        table {
          padding-top: 1cm;
          padding-bottom: 1cm;
          font-size: 10px;
        }
        td, th {
          border-bottom: 1px solid #dddddd;
          padding: 5px;
        }        
        tr:nth-child(even) {
          background-color: #ffffff;
        }
      </style>
      ';

    // $mpdf->SetHTMLHeader($this->core->setPrintHeader());
    // $mpdf->SetHTMLFooter($this->core->setPrintFooter());

    $fileTmp = BASE_DIR . '/admin/tmp/riwayat.perawatan.html';
    $html = file_exists($fileTmp) ? file_get_contents($fileTmp) : '';
    $mpdf->WriteHTML($this->core->setPrintCss(), \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($css);
    $mpdf->WriteHTML($html);

    // Output a PDF file directly to the browser
    $mpdf->Output();
    exit();
  }

  public function getExcel()
  {
    $file = "data.pasien.xls";
    $fileTmp = BASE_DIR . '/admin/tmp/cetak.pasien.html';
    $html = file_exists($fileTmp) ? file_get_contents($fileTmp) : '';
    header("Content-type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=$file");
    echo "<!DOCTYPE html><html><head></head><body>";
    echo $html;
    echo "</body></html>";
    exit();
  }

  /**
   * API Routes
   */
  public function apiList()
  {

    if (isset($_SERVER['HTTP_X_API_KEY'])) {
      $username = $this->core->checkAuth('GET');
      if (!$this->core->checkPermission($username, 'can_read', 'pasien')) {
        return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
      }
    }

    $perpage = intval($_GET['per_page'] ?? 10);
    if ($perpage <= 0)
      $perpage = 10;

    $page = intval($_GET['page'] ?? 1);
    if ($page <= 0)
      $page = 1;

    $offset = ($page - 1) * $perpage;
    $phrase = trim((string) ($_GET['s'] ?? ''));

    $tgl_awal = $_GET['tgl_awal'] ?? '';
    $tgl_akhir = $_GET['tgl_akhir'] ?? '';

    $query = $this->db('pasien')->desc('no_rkm_medis');

    // filter tanggal (AMAN & konsisten)
    if ($tgl_awal !== '' && $tgl_akhir !== '') {
      $query
        ->where('tgl_daftar', '>=', $tgl_awal)
        ->where('tgl_daftar', '<=', $tgl_akhir);
    }

    // filter pencarian (DIGROUP)
    if ($phrase !== '') {
      $query->where(function ($st) use ($phrase) {
        $st->like('no_rkm_medis', "%$phrase%")
          ->orLike('nm_pasien', "%$phrase%")
          ->orLike('alamat', "%$phrase%")
          ->orLike('no_ktp', "%$phrase%")
          ->orLike('no_peserta', "%$phrase%")
          ->orLike('no_tlp', "%$phrase%");
      });
    }

    $total = $query->count();
    $rows = $query->offset($offset)->limit($perpage)->toArray();


    // Add extra data for display
    $pasien = [];
    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $cek_pcare = $this->db('mlite_modules')->where('dir', 'pcare')->oneArray();

    foreach ($rows as $row) {
      // Add extra URLs
      if (!isset($_SERVER['HTTP_X_API_KEY'])) {
        $row['cekbynokartu'] = url([ADMIN, 'pasien', 'vclaim_bynokartu', $row['no_peserta'], date('Y-m-d')]);
        $row['cekbynik'] = url([ADMIN, 'pasien', 'vclaim_bynik', $row['no_ktp'], date('Y-m-d')]);
        $row['pcare_bynokartu'] = url([ADMIN, 'pasien', 'pcare_bynokartu', $row['no_peserta']]);
        $row['pcare_bynik'] = url([ADMIN, 'pasien', 'pcare_bynik', $row['no_ktp']]);
        $row['oral_diagnostic'] = url([ADMIN, 'oral_diagnostic', 'manage']) . '&no_rkm_medis=' . $row['no_rkm_medis'];
        $row['igd'] = url([ADMIN, 'igd', 'manage']) . '&no_rkm_medis=' . $row['no_rkm_medis'];
        $row['rawat_jalan'] = url([ADMIN, 'rawat_jalan', 'manage']) . '&no_rkm_medis=' . $row['no_rkm_medis'];
        $row['riwayatperawatan'] = url([ADMIN, 'pasien', 'riwayatperawatan', $row['no_rkm_medis']]);
        $row['folder'] = url([ADMIN, 'pasien', 'folder', $row['no_rkm_medis']]);
      }

      $pasien[] = $row;
    }

    $meta = [
      'page' => $page,
      'per_page' => $perpage,
      'total' => $total,
    ];

    if (!isset($_SERVER['HTTP_X_API_KEY'])) {
      $meta['cek_vclaim'] = $cek_vclaim ? true : false;
      $meta['cek_pcare'] = $cek_pcare ? true : false;
      $meta['active_modules'] = [
        'oral_diagnostic' => $this->core->ActiveModule('oral_diagnostic'),
        'igd' => $this->core->ActiveModule('igd')
      ];
    }

    return [
      'status' => 'success',
      'data' => $pasien,
      'meta' => $meta
    ];
  }

  public function apiShow($no_rkm_medis = null)
  {
    $username = $this->core->checkAuth('GET');
    if (!$this->core->checkPermission($username, 'can_read', 'pasien')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rkm_medis) {
      return ['status' => 'error', 'message' => 'no_rkm_medis required'];
    }
    $row = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
    if (!$row) {
      return ['status' => 'error', 'message' => 'Not found'];
    }
    return ['status' => 'success', 'data' => $row];
  }

  public function apiCreate()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_create', 'pasien')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    if (empty($input['nm_pasien'])) {
      return ['status' => 'error', 'message' => 'nm_pasien required'];
    }

    if (empty($input['no_rkm_medis'])) {
      $input['no_rkm_medis'] = $this->core->setNoRM();
    } else {
      $exists = $this->db('pasien')->where('no_rkm_medis', $input['no_rkm_medis'])->oneArray();
      if ($exists) {
        return ['status' => 'error', 'message' => 'no_rkm_medis exists'];
      }
    }

    // Set default values for required fields
    $defaults = [
      'nm_ibu' => '-',
      'alamat' => '-',
      'gol_darah' => '-',
      'pekerjaan' => '-',
      'stts_nikah' => 'BELUM MENIKAH',
      'agama' => 'ISLAM',
      'tgl_daftar' => date('Y-m-d'),
      'no_tlp' => '-',
      'umur' => '0 Th 0 Bl 0 Hr',
      'pnd' => '-',
      'keluarga' => 'AYAH',
      'namakeluarga' => '-',
      'kd_pj' => 'UMUM',
      'no_peserta' => '-',
      'kd_kel' => '1',
      'kd_kec' => '1',
      'kd_kab' => '1',
      'pekerjaanpj' => '-',
      'alamatpj' => '-',
      'kelurahanpj' => '-',
      'kecamatanpj' => '-',
      'kabupatenpj' => '-',
      'perusahaan_pasien' => '-',
      'suku_bangsa' => '1',
      'bahasa_pasien' => '1',
      'cacat_fisik' => '1',
      'email' => '-',
      'nip' => '-',
      'kd_prop' => '1',
      'propinsipj' => '-',
      'tmp_lahir' => '-',
      'tgl_lahir' => date('Y-m-d'),
      'jk' => 'L',
      'no_ktp' => '-'
    ];

    foreach ($defaults as $key => $value) {
      if (empty($input[$key])) {
        $input[$key] = $value;
      }
    }

    // Calculate umur if tgl_lahir provided
    if (!empty($input['tgl_lahir'])) {
      $input['umur'] = $this->hitungUmur($input['tgl_lahir']);
    }

    $input['tgl_daftar'] = date('Y-m-d');

    try {
      $saved = $this->db('pasien')->save($input);
      if ($saved) {
        $this->db()->pdo()->exec("UPDATE set_no_rkm_medis SET no_rkm_medis='{$input['no_rkm_medis']}'");
        return ['status' => 'success', 'data' => $input];
      } else {
        $errorInfo = $this->db()->pdo()->errorInfo();
        return ['status' => 'error', 'message' => 'Failed to create: ' . ($errorInfo[2] ?? 'Unknown error')];
      }
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiUpdate($no_rkm_medis = null)
  {
    $username = $this->core->checkAuth('POST'); // Use POST for update if PUT not supported
    if (!$this->core->checkPermission($username, 'can_update', 'pasien')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rkm_medis) {
      return ['status' => 'error', 'message' => 'no_rkm_medis required'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    $exists = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
    if (!$exists) {
      return ['status' => 'error', 'message' => 'Not found'];
    }

    $input['no_rkm_medis'] = $no_rkm_medis;
    $updated = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->update($input);

    if ($updated) {
      return ['status' => 'success', 'message' => 'Updated'];
    } else {
      return ['status' => 'error', 'message' => 'Failed to update'];
    }
  }

  public function apiDelete($no_rkm_medis = null)
  {
    $username = $this->core->checkAuth('DELETE');
    if (!$this->core->checkPermission($username, 'can_delete', 'pasien')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rkm_medis) {
      return ['status' => 'error', 'message' => 'no_rkm_medis required'];
    }

    try {
      $deleted = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->delete();
      if ($deleted) {
        return ['status' => 'success', 'message' => 'Deleted'];
      } else {
        return ['status' => 'error', 'message' => 'Failed to delete'];
      }
    } catch (\Throwable $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  /**
   * Halaman Telusur e-RM berdasarkan kriteria perjalanan & status akhir pasien (MRMIK).
   */
  public function getTelusur()
  {
    $userId = !empty($_SESSION['mlite_user']) ? $this->core->getUserInfo('id') : 0;
    $perms = !empty($userId) ? $this->core->loadCrudPermissions('pasien') : ['can_read' => 'false'];
    if (empty($userId) || ($perms['can_read'] ?? 'false') !== 'true') {
      $this->notify('failure', 'Anda tidak memiliki hak akses membaca data rekam medis pasien.');
      redirect(url([ADMIN, 'dashboard', 'main']));
    }

    $this->_addHeaderFiles();
    $allowedKriteria = ['all', 'pulang', 'dirujuk', 'meninggal', 'operasi', 'icu', 'aps'];
    $initKriteria = strtolower(trim((string)($_GET['kriteria'] ?? 'all')));
    if (!in_array($initKriteria, $allowedKriteria, true)) {
      $initKriteria = 'all';
    }

    return $this->draw('telusur.html', [
      'init_kriteria' => htmlspecialchars($initKriteria, ENT_QUOTES, 'UTF-8'),
      'init_search'   => htmlspecialchars(trim((string)($_GET['q'] ?? '')), ENT_QUOTES, 'UTF-8'),
      'token'         => $_SESSION['token'] ?? '',
      'mlite_crud_permissions' => htmlspecialchars_array($perms),
    ]);
  }

  public function getTelusurdata()
  {
    return $this->anyTelusurdata();
  }

  /**
   * Endpoint JSON Telusur e-RM berdasarkan kriteria perjalanan & status akhir pasien.
   */
  public function anyTelusurdata()
  {
    header('Content-Type: application/json; charset=utf-8');

    $userId = !empty($_SESSION['mlite_user']) ? $this->core->getUserInfo('id') : 0;
    $perms = !empty($userId) ? $this->core->loadCrudPermissions('pasien') : ['can_read' => 'false'];
    if (empty($userId) || ($perms['can_read'] ?? 'false') !== 'true') {
      http_response_code(403);
      echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
      exit();
    }

    $allowedKriteria = ['all', 'pulang', 'dirujuk', 'meninggal', 'operasi', 'icu', 'aps'];
    $kriteria = strtolower(trim((string)($_REQUEST['kriteria'] ?? 'all')));
    if (!in_array($kriteria, $allowedKriteria, true)) {
      $kriteria = 'all';
    }

    $tglAwal = trim((string)($_REQUEST['tgl_awal'] ?? ''));
    $tglAkhir = trim((string)($_REQUEST['tgl_akhir'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) {
      $tglAwal = '';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) {
      $tglAkhir = '';
    }

    $phrase = mb_substr(trim((string)($_REQUEST['q'] ?? '')), 0, 80, 'UTF-8');
    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $perPage = (int)($_REQUEST['per_page'] ?? 15);
    if ($perPage < 10) $perPage = 10;
    if ($perPage > 50) $perPage = 50;
    $offset = ($page - 1) * $perPage;

    try {
      $pdo = $this->db()->pdo();
      $criteriaSql = $this->_buildTelusurCriteriaSql($pdo);

      $baseWhere = ['1=1'];
      $baseBinds = [];
      if ($tglAwal !== '' && $tglAkhir !== '') {
        $baseWhere[] = 'rp.tgl_registrasi BETWEEN ? AND ?';
        $baseBinds[] = $tglAwal;
        $baseBinds[] = $tglAkhir;
      } elseif ($tglAwal !== '') {
        $baseWhere[] = 'rp.tgl_registrasi >= ?';
        $baseBinds[] = $tglAwal;
      } elseif ($tglAkhir !== '') {
        $baseWhere[] = 'rp.tgl_registrasi <= ?';
        $baseBinds[] = $tglAkhir;
      }

      if ($phrase !== '') {
        $like = '%' . $phrase . '%';
        $baseWhere[] = '(rp.no_rawat LIKE ? OR rp.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR pl.nm_poli LIKE ? OR d.nm_dokter LIKE ?)';
        array_push($baseBinds, $like, $like, $like, $like, $like);
      }

      $baseSql = "FROM reg_periksa rp
        INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
        LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
        LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
        LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
        WHERE " . implode(' AND ', $baseWhere);

      $countSql = "SELECT
        COUNT(*) AS cnt_all,
        SUM(CASE WHEN ({$criteriaSql['pulang']}) THEN 1 ELSE 0 END) AS cnt_pulang,
        SUM(CASE WHEN ({$criteriaSql['dirujuk']}) THEN 1 ELSE 0 END) AS cnt_dirujuk,
        SUM(CASE WHEN ({$criteriaSql['meninggal']}) THEN 1 ELSE 0 END) AS cnt_meninggal,
        SUM(CASE WHEN ({$criteriaSql['operasi']}) THEN 1 ELSE 0 END) AS cnt_operasi,
        SUM(CASE WHEN ({$criteriaSql['icu']}) THEN 1 ELSE 0 END) AS cnt_icu,
        SUM(CASE WHEN ({$criteriaSql['aps']}) THEN 1 ELSE 0 END) AS cnt_aps
        {$baseSql}";
      $stCounts = $pdo->prepare($countSql);
      $stCounts->execute($baseBinds);
      $cRow = $stCounts->fetch(\PDO::FETCH_ASSOC) ?: [];
      $counts = [
        'all'       => (int)($cRow['cnt_all'] ?? 0),
        'pulang'    => (int)($cRow['cnt_pulang'] ?? 0),
        'dirujuk'   => (int)($cRow['cnt_dirujuk'] ?? 0),
        'meninggal' => (int)($cRow['cnt_meninggal'] ?? 0),
        'operasi'   => (int)($cRow['cnt_operasi'] ?? 0),
        'icu'       => (int)($cRow['cnt_icu'] ?? 0),
        'aps'       => (int)($cRow['cnt_aps'] ?? 0),
      ];

      $activeCriteriaSql = ($kriteria !== 'all' && isset($criteriaSql[$kriteria]))
        ? " AND ({$criteriaSql[$kriteria]})"
        : '';

      $total = ($kriteria === 'all') ? $counts['all'] : ($counts[$kriteria] ?? 0);

      $dataSql = "SELECT
        rp.no_rawat, rp.no_rkm_medis, rp.tgl_registrasi, rp.jam_reg,
        rp.status_lanjut, rp.stts, rp.status_bayar, rp.kd_poli, rp.kd_dokter,
        p.nm_pasien, p.jk, p.tgl_lahir,
        COALESCE(pl.nm_poli, '-') AS nm_poli,
        COALESCE(d.nm_dokter, '-') AS nm_dokter,
        COALESCE(pj.png_jawab, '-') AS png_jawab
        {$baseSql} {$activeCriteriaSql}
        ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
        LIMIT {$perPage} OFFSET {$offset}";

      $stRows = $pdo->prepare($dataSql);
      $stRows->execute($baseBinds);
      $rows = $stRows->fetchAll(\PDO::FETCH_ASSOC);

      $noRawatList = array_values(array_filter(array_column($rows, 'no_rawat')));
      $tagsByRawat = $this->_extractJourneyTagsForVisits($pdo, $rows);
      $statsByRawat = $this->_collectTelusurVisitStats($pdo, $noRawatList);

      $items = [];
      foreach ($rows as $r) {
        $nr = (string)$r['no_rawat'];
        $noRm = (string)$r['no_rkm_medis'];
        $st = $statsByRawat[$nr] ?? [];
        $items[] = [
          'no_rawat'         => $nr,
          'no_rkm_medis'     => $noRm,
          'nm_pasien'        => (string)($r['nm_pasien'] ?? '-'),
          'jk'               => (string)($r['jk'] ?? ''),
          'tgl_registrasi'   => (string)($r['tgl_registrasi'] ?? ''),
          'jam_reg'          => (string)($r['jam_reg'] ?? ''),
          'status_lanjut'    => (string)($r['status_lanjut'] ?? 'Ralan'),
          'stts'             => (string)($r['stts'] ?? ''),
          'status_bayar'     => (string)($r['status_bayar'] ?? ''),
          'nm_poli'          => (string)($r['nm_poli'] ?? '-'),
          'nm_dokter'        => (string)($r['nm_dokter'] ?? '-'),
          'png_jawab'        => (string)($r['png_jawab'] ?? '-'),
          'tags'             => $tagsByRawat[$nr] ?? [],
          'diagnosa_ringkas' => (string)($st['diagnosa_ringkas'] ?? ''),
          'stts_pulang_list' => $st['stts_pulang_list'] ?? [],
          'rme_count'        => (int)($st['rme_count'] ?? 0),
          'soap_count'       => (int)($st['soap_count'] ?? 0),
          'penunjang_count'  => (int)($st['penunjang_count'] ?? 0),
          'tindakan_count'   => (int)($st['tindakan_count'] ?? 0),
          'url_erm'          => url([ADMIN, 'pasien', 'riwayatperawatan', $noRm]) . '&focus_rawat=' . urlencode($nr),
          'url_lembar_kerja' => url([ADMIN, 'form_rme', 'pasien']) . '&no_rawat=' . urlencode($nr),
          'url_pdf'          => url([ADMIN, 'pasien', 'pdfriwayatperawatan', $noRm, convertNorawat($nr)]),
        ];
      }

      echo json_encode([
        'status'   => 'success',
        'kriteria' => $kriteria,
        'counts'   => $counts,
        'data'     => $items,
        'meta'     => [
          'page'       => $page,
          'per_page'   => $perPage,
          'total'      => $total,
          'total_page' => max(1, (int)ceil($total / $perPage)),
        ],
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
      error_log('[pasien/telusurdata] ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data telusur e-RM.'], JSON_UNESCAPED_UNICODE);
    }
    exit();
  }

  /**
   * Cache daftar tabel yang tersedia di database saat runtime.
   */
  private function _getExistingTablesMap(\PDO $pdo)
  {
    static $cache = null;
    if ($cache !== null) {
      return $cache;
    }
    $cache = [];
    if (DBDRIVER === 'mysql') {
      $st = $pdo->query("SHOW TABLES");
      while ($r = $st->fetch(\PDO::FETCH_NUM)) {
        $cache[(string)$r[0]] = true;
      }
    }
    return $cache;
  }

  /**
   * Single source of truth ekspresi SQL kriteria pasien Telusur e-RM (MRMIK).
   */
  private function _buildTelusurCriteriaSql(\PDO $pdo)
  {
    $tbls = $this->_getExistingTablesMap($pdo);
    $hasKamarInap = !empty($tbls['kamar_inap']);

    $pulangParts = ["rp.stts = 'Sudah'"];
    $dirujukParts = ["rp.stts = 'Dirujuk'"];
    $meninggalParts = ["rp.stts = 'Meninggal'"];
    $apsParts = ["rp.stts = 'Pulang Paksa'"];
    $operasiParts = [];
    $icuParts = [];

    if ($hasKamarInap) {
      $pulangParts[] = "EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat AND ki.stts_pulang IN ('Sehat','Sembuh','Membaik','Atas Persetujuan Dokter'))";
      $dirujukParts[] = "EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat AND ki.stts_pulang = 'Rujuk')";
      $meninggalParts[] = "EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat AND ki.stts_pulang IN ('Meninggal','+'))";
      $apsParts[] = "EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat = rp.no_rawat AND ki.stts_pulang IN ('APS','Pulang Paksa','Atas Permintaan Sendiri'))";
      if (!empty($tbls['kamar']) && !empty($tbls['bangsal'])) {
        $icuParts[] = "EXISTS (SELECT 1 FROM kamar_inap ki INNER JOIN kamar km ON km.kd_kamar = ki.kd_kamar INNER JOIN bangsal bg ON bg.kd_bangsal = km.kd_bangsal WHERE ki.no_rawat = rp.no_rawat AND UPPER(bg.nm_bangsal) LIKE '%ICU%')";
      }
    }

    if (!empty($tbls['formulir_rujukan_rs'])) {
      $dirujukParts[] = "EXISTS (SELECT 1 FROM formulir_rujukan_rs fr WHERE fr.no_rawat = rp.no_rawat)";
    }
    if (!empty($tbls['rm_surat_pulang_aps'])) {
      $apsParts[] = "EXISTS (SELECT 1 FROM rm_surat_pulang_aps aps WHERE aps.no_rawat = rp.no_rawat)";
    }
    foreach (['operasi', 'laporan_operasi', 'rm_bm4_laporan_pembedahan', 'rm_bmi5_bedah_minor'] as $opTbl) {
      if (!empty($tbls[$opTbl])) {
        $alias = 't_' . substr(md5($opTbl), 0, 5);
        $operasiParts[] = "EXISTS (SELECT 1 FROM `{$opTbl}` {$alias} WHERE {$alias}.no_rawat = rp.no_rawat)";
      }
    }
    if (!empty($tbls['kriteria_masuk_keluar_icu'])) {
      $icuParts[] = "EXISTS (SELECT 1 FROM kriteria_masuk_keluar_icu icu WHERE icu.no_rawat = rp.no_rawat)";
    }

    return [
      'pulang'    => implode(' OR ', $pulangParts),
      'dirujuk'   => implode(' OR ', $dirujukParts),
      'meninggal' => implode(' OR ', $meninggalParts),
      'operasi'   => !empty($operasiParts) ? implode(' OR ', $operasiParts) : '1=0',
      'icu'       => !empty($icuParts) ? implode(' OR ', $icuParts) : '1=0',
      'aps'       => implode(' OR ', $apsParts),
    ];
  }

  /**
   * Menghitung tag kriteria perjalanan pasien per kunjungan secara batch & konsisten.
   */
  private function _extractJourneyTagsForVisits(\PDO $pdo, array $visits)
  {
    $noRawatList = array_values(array_unique(array_filter(array_column($visits, 'no_rawat'))));
    if (empty($noRawatList)) {
      return [];
    }

    $tbls = $this->_getExistingTablesMap($pdo);
    $ph = implode(',', array_fill(0, count($noRawatList), '?'));

    $fetchRawatSet = function ($sql) use ($pdo, $noRawatList) {
      $set = [];
      try {
        $st = $pdo->prepare($sql);
        $st->execute($noRawatList);
        while ($nr = $st->fetchColumn()) {
          $set[(string)$nr] = true;
        }
      } catch (\Throwable $e) {
      }
      return $set;
    };

    $dirujukSet = !empty($tbls['formulir_rujukan_rs'])
      ? $fetchRawatSet("SELECT DISTINCT no_rawat FROM formulir_rujukan_rs WHERE no_rawat IN ({$ph})")
      : [];
    $apsSet = !empty($tbls['rm_surat_pulang_aps'])
      ? $fetchRawatSet("SELECT DISTINCT no_rawat FROM rm_surat_pulang_aps WHERE no_rawat IN ({$ph})")
      : [];
    $icuSet = !empty($tbls['kriteria_masuk_keluar_icu'])
      ? $fetchRawatSet("SELECT DISTINCT no_rawat FROM kriteria_masuk_keluar_icu WHERE no_rawat IN ({$ph})")
      : [];

    $operasiSet = [];
    foreach (['operasi', 'laporan_operasi', 'rm_bm4_laporan_pembedahan', 'rm_bmi5_bedah_minor'] as $opTbl) {
      if (!empty($tbls[$opTbl])) {
        $operasiSet += $fetchRawatSet("SELECT DISTINCT no_rawat FROM `{$opTbl}` WHERE no_rawat IN ({$ph})");
      }
    }

    $kamarByRawat = [];
    if (!empty($tbls['kamar_inap'])) {
      try {
        $joinBangsal = (!empty($tbls['kamar']) && !empty($tbls['bangsal']))
          ? "LEFT JOIN kamar km ON km.kd_kamar = ki.kd_kamar LEFT JOIN bangsal bg ON bg.kd_bangsal = km.kd_bangsal"
          : "";
        $selBangsal = $joinBangsal !== '' ? ", COALESCE(bg.nm_bangsal, '') AS nm_bangsal" : ", '' AS nm_bangsal";
        $stKi = $pdo->prepare("SELECT ki.no_rawat, ki.stts_pulang {$selBangsal} FROM kamar_inap ki {$joinBangsal} WHERE ki.no_rawat IN ({$ph})");
        $stKi->execute($noRawatList);
        while ($ki = $stKi->fetch(\PDO::FETCH_ASSOC)) {
          $kamarByRawat[(string)$ki['no_rawat']][] = $ki;
        }
      } catch (\Throwable $e) {
      }
    }

    $result = [];
    foreach ($visits as $v) {
      $nr = (string)($v['no_rawat'] ?? '');
      if ($nr === '') continue;
      $stts = (string)($v['stts'] ?? '');
      $tags = [];
      $added = [];
      $pushTag = function ($key, $label, $tone) use (&$tags, &$added) {
        if (!isset($added[$key])) {
          $added[$key] = true;
          $tags[] = ['key' => $key, 'label' => $label, 'tone' => $tone];
        }
      };

      $kiRows = $kamarByRawat[$nr] ?? [];
      $kiStatuses = array_column($kiRows, 'stts_pulang');
      $hasIcuWard = false;
      foreach ($kiRows as $ki) {
        if (stripos((string)($ki['nm_bangsal'] ?? ''), 'ICU') !== false) {
          $hasIcuWard = true;
          break;
        }
      }

      if ($stts === 'Dirujuk' || in_array('Rujuk', $kiStatuses, true) || !empty($dirujukSet[$nr])) {
        $pushTag('dirujuk', 'Dirujuk', 'amber');
      }
      if ($stts === 'Meninggal' || in_array('Meninggal', $kiStatuses, true) || in_array('+', $kiStatuses, true)) {
        $pushTag('meninggal', 'Meninggal', 'rose');
      }
      if (!empty($operasiSet[$nr])) {
        $pushTag('operasi', 'Operasi', 'indigo');
      }
      if (!empty($icuSet[$nr]) || $hasIcuWard) {
        $pushTag('icu', 'ICU', 'purple');
      }
      if ($stts === 'Pulang Paksa' || !empty(array_intersect($kiStatuses, ['APS', 'Pulang Paksa', 'Atas Permintaan Sendiri'])) || !empty($apsSet[$nr])) {
        $pushTag('aps', 'APS', 'orange');
      }
      if ($stts === 'Sudah' || !empty(array_intersect($kiStatuses, ['Sehat', 'Sembuh', 'Membaik', 'Atas Persetujuan Dokter']))) {
        $pushTag('pulang', 'Pulang', 'emerald');
      } elseif ($stts === 'Dirawat') {
        $pushTag('dirawat', 'Dirawat', 'sky');
      }

      $result[$nr] = $tags;
    }

    return $result;
  }

  /**
   * Menghitung kelengkapan isi e-RM (SOAP, Form RME, Penunjang, Tindakan, Diagnosa) secara batch per halaman.
   */
  private function _collectTelusurVisitStats(\PDO $pdo, array $noRawatList)
  {
    $stats = [];
    foreach ($noRawatList as $nr) {
      $stats[$nr] = [
        'soap_count'       => 0,
        'rme_count'        => 0,
        'penunjang_count'  => 0,
        'tindakan_count'   => 0,
        'diagnosa_ringkas' => '',
        'stts_pulang_list' => [],
      ];
    }
    if (empty($noRawatList)) {
      return $stats;
    }

    $tbls = $this->_getExistingTablesMap($pdo);
    $ph = implode(',', array_fill(0, count($noRawatList), '?'));

    $addCounts = function ($table, $statKey) use ($pdo, $tbls, $ph, $noRawatList, &$stats) {
      if (empty($tbls[$table])) return;
      try {
        $st = $pdo->prepare("SELECT no_rawat, COUNT(*) AS cnt FROM `{$table}` WHERE no_rawat IN ({$ph}) GROUP BY no_rawat");
        $st->execute($noRawatList);
        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
          $nr = (string)$row['no_rawat'];
          if (isset($stats[$nr])) {
            $stats[$nr][$statKey] += (int)$row['cnt'];
          }
        }
      } catch (\Throwable $e) {
      }
    };

    foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $t) {
      $addCounts($t, 'soap_count');
    }
    foreach (['periksa_lab', 'periksa_radiologi'] as $t) {
      $addCounts($t, 'penunjang_count');
    }
    foreach (['rawat_jl_dr', 'rawat_jl_pr', 'rawat_jl_drpr', 'rawat_inap_dr', 'rawat_inap_pr', 'rawat_inap_drpr', 'operasi'] as $t) {
      $addCounts($t, 'tindakan_count');
    }

    if (!empty($tbls['mlite_rme_form'])) {
      try {
        $rmeMeta = $pdo->query("SELECT DISTINCT nama_tabel FROM mlite_rme_form WHERE status = '1'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($rmeMeta as $tblName) {
          $tblName = trim((string)$tblName);
          if ($tblName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $tblName) || empty($tbls[$tblName])) {
            continue;
          }
          try {
            $stR = $pdo->prepare("SELECT DISTINCT no_rawat FROM `{$tblName}` WHERE no_rawat IN ({$ph})");
            $stR->execute($noRawatList);
            while ($nr = $stR->fetchColumn()) {
              $nr = (string)$nr;
              if (isset($stats[$nr])) {
                $stats[$nr]['rme_count']++;
              }
            }
          } catch (\Throwable $e) {
          }
        }
      } catch (\Throwable $e) {
      }
    }

    if (!empty($tbls['diagnosa_pasien']) && !empty($tbls['penyakit'])) {
      try {
        $stD = $pdo->prepare("SELECT dp.no_rawat, dp.kd_penyakit, pk.nm_penyakit
          FROM diagnosa_pasien dp
          LEFT JOIN penyakit pk ON pk.kd_penyakit = dp.kd_penyakit
          WHERE dp.no_rawat IN ({$ph})
          ORDER BY dp.prioritas ASC");
        $stD->execute($noRawatList);
        $diagMap = [];
        while ($dr = $stD->fetch(\PDO::FETCH_ASSOC)) {
          $nr = (string)$dr['no_rawat'];
          $diagMap[$nr][] = trim(($dr['kd_penyakit'] ?? '') . ' ' . ($dr['nm_penyakit'] ?? ''));
        }
        foreach ($diagMap as $nr => $parts) {
          if (isset($stats[$nr])) {
            $stats[$nr]['diagnosa_ringkas'] = implode('; ', array_slice($parts, 0, 2));
          }
        }
      } catch (\Throwable $e) {
      }
    }

    if (!empty($tbls['kamar_inap'])) {
      try {
        $stKi = $pdo->prepare("SELECT no_rawat, stts_pulang FROM kamar_inap WHERE no_rawat IN ({$ph}) AND stts_pulang IS NOT NULL AND stts_pulang != '-'");
        $stKi->execute($noRawatList);
        while ($ki = $stKi->fetch(\PDO::FETCH_ASSOC)) {
          $nr = (string)$ki['no_rawat'];
          if (isset($stats[$nr]) && !in_array($ki['stts_pulang'], $stats[$nr]['stts_pulang_list'], true)) {
            $stats[$nr]['stts_pulang_list'][] = (string)$ki['stts_pulang'];
          }
        }
      } catch (\Throwable $e) {
      }
    }

    return $stats;
  }

}

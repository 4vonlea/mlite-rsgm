<?php
namespace Plugins\Laboratorium;

use Systems\AdminModule;
use Systems\Lib\QRCode;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Admin extends AdminModule
{
  protected array $assign = [];

  public function navigation()
  {
    return [
      'Kelola' => 'manage',
    ];
  }

  public function apiList()
  {
    $username = $this->core->checkAuth('GET');
    if (!$this->core->checkPermission($username, 'can_read', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $draw = $_GET['draw'] ?? 0;
    $start = $_GET['start'] ?? 0;
    $length = $_GET['length'] ?? 10;
    $columnIndex = $_GET['order'][0]['column'] ?? 0;
    $columnName = $_GET['columns'][$columnIndex]['data'] ?? 'no_rawat';
    $columnSortOrder = strtolower($_GET['order'][0]['dir'] ?? 'asc');

    $allowedColumns = ['no_reg', 'jam_reg', 'no_rkm_medis', 'kd_poli', 'kd_dokter', 'stts', 'status_bayar', 'kd_pj', 'nm_pasien', 'nm_dokter', 'nm_poli', 'png_jawab', 'no_rawat'];
    if (!in_array($columnName, $allowedColumns)) {
      $columnName = 'no_rawat';
    }
    if (!in_array($columnSortOrder, ['asc', 'desc'])) {
      $columnSortOrder = 'asc';
    }

    $searchValue = is_array($_GET['search'] ?? null) ? ($_GET['search']['value'] ?? '') : ($_GET['search'] ?? '');

    $tgl_kunjungan = $_GET['tgl_awal'] ?? date('Y-m-d');
    $tgl_kunjungan_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
    $status_periksa = $_GET['status_periksa'] ?? '';
    $type = $_GET['type'] ?? 'ralan';
    $status_bayar = $_GET['status_bayar'] ?? '';

    $params = [];
    if ($type == 'permintaan') {
      $sql = "SELECT permintaan_lab.*, reg_periksa.no_rkm_medis, reg_periksa.umurdaftar, reg_periksa.sttsumur, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.no_tlp, pasien.alamat, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM permintaan_lab 
                    JOIN reg_periksa ON permintaan_lab.no_rawat = reg_periksa.no_rawat 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN dokter ON permintaan_lab.dokter_perujuk = dokter.kd_dokter 
                    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    WHERE permintaan_lab.tgl_permintaan BETWEEN ? AND ?";
      $params[] = $tgl_kunjungan;
      $params[] = $tgl_kunjungan_akhir;

      if ($status_periksa != '') {
        $sql .= " AND permintaan_lab.status = ?";
        $params[] = ucfirst(strtolower($status_periksa));
      }
    } elseif ($type == 'ranap') {
      $sql = "SELECT kamar_inap.*, reg_periksa.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.no_tlp, pasien.alamat, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM kamar_inap 
                    JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar 
                    JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    LEFT JOIN dpjp_ranap ON dpjp_ranap.no_rawat = kamar_inap.no_rawat 
                    LEFT JOIN dokter ON dokter.kd_dokter = dpjp_ranap.kd_dokter 
                    LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                    WHERE 1=1";

      if ($status_periksa == '') {
        $sql .= " AND kamar_inap.stts_pulang = '-'";
      }
      if ($status_periksa == 'belum') {
        $sql .= " AND kamar_inap.stts_pulang = '-' AND kamar_inap.tgl_masuk BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }
      if ($status_periksa == 'selesai') {
        $sql .= " AND kamar_inap.stts_pulang != '-' AND kamar_inap.tgl_masuk BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }
      if ($status_periksa == 'lunas') {
        $sql .= " AND kamar_inap.stts_pulang != '-' AND kamar_inap.tgl_keluar BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }
    } else {
      $sql = "SELECT reg_periksa.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.no_tlp, pasien.alamat, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM reg_periksa 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter 
                    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?";
      $params[] = $tgl_kunjungan;
      $params[] = $tgl_kunjungan_akhir;

      if ($status_periksa == 'belum') {
        $sql .= " AND reg_periksa.stts = 'Belum'";
      }
      if ($status_periksa == 'selesai') {
        $sql .= " AND reg_periksa.stts = 'Sudah'";
      }
      if ($status_periksa == 'lunas') {
        $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar'";
      }
    }

    if (!empty($searchValue)) {
      $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ? OR dokter.nm_dokter LIKE ?)";
      $params[] = "%$searchValue%";
      $params[] = "%$searchValue%";
      $params[] = "%$searchValue%";
      $params[] = "%$searchValue%";
    }

    $stmt = $this->db()->pdo()->prepare($sql);
    $stmt->execute($params);
    $totalRecords = $stmt->rowCount();

    $sql .= " ORDER BY $columnName $columnSortOrder LIMIT $start, $length";

    $stmt = $this->db()->pdo()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
      foreach ($row as $key => $value) {
        if (is_string($value)) {
          $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
      }
      if ($type == 'ranap') {
        $dpjp_ranap = $this->db('dpjp_ranap')
          ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();
        $row['dokter'] = htmlspecialchars_array($dpjp_ranap);
      }
    }
    unset($row);

    return [
      "status" => "success",
      "data" => $rows,
      "meta" => [
        "page" => floor($start / $length) + 1,
        "per_page" => intval($length),
        "total" => $totalRecords
      ]
    ];
  }

  public function apiShow($no_rawat = null)
  {
    $username = $this->core->checkAuth('GET');
    if (!$this->core->checkPermission($username, 'can_read', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rawat) {
      return ['status' => 'error', 'message' => 'No rawat missing'];
    }
    $no_rawat = revertNoRawat($no_rawat);
    $row = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
      ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
      ->where('no_rawat', $no_rawat)
      ->oneArray();

    if ($row) {
      return ['status' => 'success', 'data' => htmlspecialchars_array($row)];
    } else {
      return ['status' => 'error', 'message' => 'Not found'];
    }
  }

  public function apiSave()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_create', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    if (empty($input['no_rkm_medis'])) {
      return ['status' => 'error', 'message' => 'Data incomplete'];
    }

    $input['tgl_registrasi'] = $input['tgl_registrasi'] ?? date('Y-m-d');
    $input['jam_reg'] = $input['jam_reg'] ?? date('H:i:s');
    $input['kd_dokter'] = $input['kd_dokter'] ?? $this->settings->get('settings.pj_laboratorium');

    try {
      if (!$this->db('reg_periksa')->where('no_rawat', $input['no_rawat'])->oneArray()) {
        $input['status_lanjut'] = 'Ralan';
        $input['stts'] = 'Belum';
        $input['status_bayar'] = 'Belum Bayar';
        $input['p_jawab'] = $input['p_jawab'] ?? '-';
        $input['almt_pj'] = $input['almt_pj'] ?? '-';
        $input['hubunganpj'] = $input['hubunganpj'] ?? '-';

        $poliklinik = $this->db('poliklinik')->where('kd_poli', $this->settings('settings', 'laboratorium'))->oneArray();
        $input['biaya_reg'] = $poliklinik['registrasi'];

        $pasien = $this->db('pasien')->where('no_rkm_medis', $input['no_rkm_medis'])->oneArray();

        $birthDate = new \DateTime($pasien['tgl_lahir']);
        $today = new \DateTime("today");
        $umur_daftar = "0";
        $status_umur = 'Hr';
        if ($birthDate < $today) {
          $y = $today->diff($birthDate)->y;
          $m = $today->diff($birthDate)->m;
          $d = $today->diff($birthDate)->d;
          $umur_daftar = $d;
          $status_umur = "Hr";
          if ($y != '0') {
            $umur_daftar = $y;
            $status_umur = "Th";
          }
          if ($y == '0' && $m != '0') {
            $umur_daftar = $m;
            $status_umur = "Bl";
          }
        }

        $input['umurdaftar'] = $umur_daftar;
        $input['sttsumur'] = $status_umur;
        $input['status_poli'] = 'Lama';
        $input['kd_poli'] = $this->settings('settings', 'laboratorium');

        unset($input['kat']);
        $this->db('reg_periksa')->save($input);
        return ['status' => 'created', 'data' => htmlspecialchars_array($input)];
      } else {
        $updateData = [];
        if (isset($input['kd_dokter']))
          $updateData['kd_dokter'] = $input['kd_dokter'];
        if (isset($input['kd_pj']))
          $updateData['kd_pj'] = $input['kd_pj'];

        $this->db('reg_periksa')->where('no_rawat', $input['no_rawat'])->update($updateData);
        return ['status' => 'updated', 'data' => htmlspecialchars_array($updateData)];
      }
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiUpdate($no_rawat = null)
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_update', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rawat) {
      return ['status' => 'error', 'message' => 'No rawat missing'];
    }

    $no_rawat = revertNoRawat($no_rawat);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    try {
      $this->db('reg_periksa')->where('no_rawat', $no_rawat)->update($input);
      return ['status' => 'updated', 'data' => htmlspecialchars_array($input)];
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiDelete($no_rawat = null)
  {
    $username = $this->core->checkAuth('DELETE');
    if (!$this->core->checkPermission($username, 'can_delete', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rawat) {
      return ['status' => 'error', 'message' => 'No rawat missing'];
    }
    $no_rawat = revertNoRawat($no_rawat);

    if (!$this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray()) {
      return ['status' => 'error', 'message' => 'No rawat not found'];
    }

    try {
      $this->db('reg_periksa')->where('no_rawat', $no_rawat)->delete();
      return ['status' => 'deleted', 'no_rawat' => $no_rawat];
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiSaveDetail()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_create', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    if (empty($input['kat']) || empty($input['no_rawat']) || empty($input['kd_jenis_prw'])) {
      return ['status' => 'error', 'message' => 'Data incomplete'];
    }

    try {
      if ($input['kat'] == 'laboratorium') {
        $jml_tindakan = max(1, (int) ($input['jml_tindakan'] ?? 1));
        $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $input['kd_jenis_prw'])->oneArray();
        for ($i = 0; $i < $jml_tindakan; $i++) {
          $jam_periksa = date('H:i:s', strtotime($input['jam_rawat'] . ' +' . ($i * 5) . ' minutes'));
          $periksa_lab = $this->db('periksa_lab')
            ->save([
              'no_rawat' => $input['no_rawat'],
              'nip' => $input['nip'] ?? $this->core->getUserInfo('username', null, true),
              'kd_jenis_prw' => $input['kd_jenis_prw'],
              'tgl_periksa' => $input['tgl_perawatan'],
              'jam' => $jam_periksa,
              'dokter_perujuk' => $input['kode_provider'],
              'bagian_rs' => $jns_perawatan['bagian_rs'],
              'bhp' => $jns_perawatan['bhp'],
              'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
              'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
              'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
              'kso' => $jns_perawatan['kso'],
              'menejemen' => $jns_perawatan['menejemen'],
              'biaya' => $jns_perawatan['total_byr'],
              'kd_dokter' => $input['kd_dokter'] ?? $this->settings->get('settings.pj_laboratorium'),
              'status' => $input['status']
            ]);
        }
        if (isset($periksa_lab) && $periksa_lab) {
          $template_laboratorium = $this->db('template_laboratorium')->where('kd_jenis_prw', $input['kd_jenis_prw'])->toArray();
          foreach ($template_laboratorium as $row) {
            for ($i = 0; $i < $jml_tindakan; $i++) {
              $jam_periksa = date('H:i:s', strtotime($input['jam_rawat'] . ' +' . ($i * 5) . ' minutes'));
              $this->db('detail_periksa_lab')
                ->save([
                  'no_rawat' => $input['no_rawat'],
                  'kd_jenis_prw' => $input['kd_jenis_prw'],
                  'tgl_periksa' => $input['tgl_perawatan'],
                  'jam' => $jam_periksa,
                  'id_template' => $row['id_template'],
                  'nilai' => '',
                  'nilai_rujukan' => $row['nilai_rujukan_ld'],
                  'keterangan' => '',
                  'bagian_rs' => $row['bagian_rs'],
                  'bhp' => $row['bhp'],
                  'bagian_perujuk' => $row['bagian_perujuk'],
                  'bagian_dokter' => $row['bagian_dokter'],
                  'bagian_laborat' => $row['bagian_laborat'],
                  'kso' => $row['kso'],
                  'menejemen' => $row['menejemen'],
                  'biaya_item' => $row['biaya_item']
                ]);
            }
          }
        }
        return ['status' => 'success', 'message' => 'Detail saved'];
      } else {
        return ['status' => 'error', 'message' => 'Category not supported'];
      }
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiDeleteDetail()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_delete', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    if (empty($input['no_rawat']) || empty($input['kd_jenis_prw']) || empty($input['tgl_perawatan']) || empty($input['jam_rawat'])) {
      return ['status' => 'error', 'message' => 'Data incomplete'];
    }

    try {
      $this->db('periksa_lab')
        ->where('no_rawat', $input['no_rawat'])
        ->where('kd_jenis_prw', $input['kd_jenis_prw'])
        ->where('tgl_periksa', $input['tgl_perawatan'])
        ->where('jam', $input['jam_rawat'])
        ->where('status', 'Ralan')
        ->delete();

      $this->db('detail_periksa_lab')
        ->where('no_rawat', $input['no_rawat'])
        ->where('kd_jenis_prw', $input['kd_jenis_prw'])
        ->where('tgl_periksa', $input['tgl_perawatan'])
        ->where('jam', $input['jam_rawat'])
        ->delete();

      return ['status' => 'success', 'message' => 'Detail deleted'];
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiSaveValidasi()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_create', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    if (empty($input['no_rawat']) || empty($input['noorder']) || empty($input['tgl_permintaan']) || empty($input['jam_permintaan'])) {
      return ['status' => 'error', 'message' => 'Data incomplete'];
    }

    try {
      $role = $this->core->getUserInfo('role', $username);
      if ($role != 'admin' && $role != 'laboratorium') {
        return ['status' => 'error', 'message' => 'Hanya petugas laboratorium atau admin yang diperbolehkan memvalidasi permintaan laboratorium.'];
      }

      $permintaan_lab = $this->db('permintaan_lab')->where('no_rawat', $input['no_rawat'])->where('noorder', $input['noorder'])->oneArray();
      $this->db('permintaan_lab')->where('no_rawat', $input['no_rawat'])->where('noorder', $input['noorder'])->save(['tgl_sampel' => date('Y-m-d'), 'jam_sampel' => date('H:i:s')]);
      $permintaan_pemeriksaan_lab = $this->db('permintaan_pemeriksaan_lab')->where('noorder', $input['noorder'])->toArray();

      $nip = $username;
      $kd_dokter = $this->settings->get('settings.pj_laboratorium');
      $status = ucfirst(strtolower($input['status'] ?? 'Ralan'));

      foreach ($permintaan_pemeriksaan_lab as $row) {
        $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
        if (!$jns_perawatan) {
          continue;
        }

        $cek_periksa = $this->db('periksa_lab')
          ->where('no_rawat', $input['no_rawat'])
          ->where('kd_jenis_prw', $row['kd_jenis_prw'])
          ->where('tgl_periksa', $input['tgl_permintaan'])
          ->where('jam', $input['jam_permintaan'])
          ->oneArray();

        if (!$cek_periksa) {
          $this->db('periksa_lab')
            ->save([
              'no_rawat' => $input['no_rawat'],
              'nip' => $nip,
              'kd_jenis_prw' => $row['kd_jenis_prw'],
              'tgl_periksa' => $input['tgl_permintaan'],
              'jam' => $input['jam_permintaan'],
              'dokter_perujuk' => $permintaan_lab['dokter_perujuk'] ?? $kd_dokter,
              'bagian_rs' => $jns_perawatan['bagian_rs'] ?? 0,
              'bhp' => $jns_perawatan['bhp'] ?? 0,
              'tarif_perujuk' => $jns_perawatan['tarif_perujuk'] ?? 0,
              'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'] ?? 0,
              'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'] ?? 0,
              'kso' => $jns_perawatan['kso'] ?? 0,
              'menejemen' => $jns_perawatan['menejemen'] ?? 0,
              'biaya' => $jns_perawatan['total_byr'] ?? 0,
              'kd_dokter' => $kd_dokter,
              'status' => $status,
              'kategori' => 'PK'
            ]);
        }

        $permintaan_detail_permintaan_lab = $this->db('permintaan_detail_permintaan_lab')->where('noorder', $input['noorder'])->where('kd_jenis_prw', $row['kd_jenis_prw'])->toArray();
        if (empty($permintaan_detail_permintaan_lab)) {
          $templates = $this->db('template_laboratorium')->where('kd_jenis_prw', $row['kd_jenis_prw'])->toArray();
        } else {
          $templates = [];
          foreach ($permintaan_detail_permintaan_lab as $row2) {
            $tpl = $this->db('template_laboratorium')->where('kd_jenis_prw', $row2['kd_jenis_prw'])->where('id_template', $row2['id_template'])->oneArray();
            if ($tpl) {
              $templates[] = $tpl;
            }
          }
        }

        foreach ($templates as $tpl) {
          $cek_detail = $this->db('detail_periksa_lab')
            ->where('no_rawat', $input['no_rawat'])
            ->where('kd_jenis_prw', $tpl['kd_jenis_prw'])
            ->where('tgl_periksa', $input['tgl_permintaan'])
            ->where('jam', $input['jam_permintaan'])
            ->where('id_template', $tpl['id_template'])
            ->oneArray();

          if (!$cek_detail) {
            $this->db('detail_periksa_lab')
              ->save([
                'no_rawat' => $input['no_rawat'],
                'kd_jenis_prw' => $tpl['kd_jenis_prw'],
                'tgl_periksa' => $input['tgl_permintaan'],
                'jam' => $input['jam_permintaan'],
                'id_template' => $tpl['id_template'],
                'nilai' => '',
                'nilai_rujukan' => $tpl['nilai_rujukan_ld'] ?? '',
                'keterangan' => '',
                'bagian_rs' => $tpl['bagian_rs'] ?? 0,
                'bhp' => $tpl['bhp'] ?? 0,
                'bagian_perujuk' => $tpl['bagian_perujuk'] ?? 0,
                'bagian_dokter' => $tpl['bagian_dokter'] ?? 0,
                'bagian_laborat' => $tpl['bagian_laborat'] ?? 0,
                'kso' => $tpl['kso'] ?? 0,
                'menejemen' => $tpl['menejemen'] ?? 0,
                'biaya_item' => $tpl['biaya_item'] ?? 0
              ]);
          }
        }
      }
      return ['status' => 'success', 'message' => 'Permintaan validated'];
    } catch (\PDOException $e) {
      $message = $e->getMessage();
      $message = preg_replace('/`[^`]+`\./', '', $message);
      return ['status' => 'error', 'message' => htmlspecialchars_array($message)];
    }
  }

  public function apiShowDetail($status = null, $no_rawat = null, $noorder = null)
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_read', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    if (!$no_rawat) {
      return ['status' => 'error', 'message' => 'No rawat missing'];
    }
    $no_rawat = revertNorawat($no_rawat);
    $status = trim($status);

    $pasien = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
      ->where('no_rawat', $no_rawat)
      ->oneArray();

    $patient_info = [
      'nm_pasien' => $pasien['nm_pasien'] ?? '',
      'no_rkm_medis' => $pasien['no_rkm_medis'] ?? ''
    ];

    try {
      $query = $this->db('permintaan_lab')
        ->join('permintaan_pemeriksaan_lab', 'permintaan_pemeriksaan_lab.noorder = permintaan_lab.noorder')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw = permintaan_pemeriksaan_lab.kd_jenis_prw')
        ->where('permintaan_lab.no_rawat', $no_rawat);

      if ($noorder) {
        $query->where('permintaan_lab.noorder', $noorder);
      }

      $permintaan_lab = $query->toArray();

      return [
        'status' => 'success',
        'patient' => $patient_info,
        'data' => [
          'permintaan_lab' => htmlspecialchars_array($permintaan_lab)
        ]
      ];
    } catch (\PDOException $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function apiSaveNilai()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_update', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    try {
      $hasil = $this->db('detail_periksa_lab')
        ->where('no_rawat', $input['no_rawat'])
        ->where('tgl_periksa', $input['tgl_periksa'])
        ->where('jam', $input['jam'])
        ->where('id_template', $input['id_template'])
        ->save([
          'nilai' => $input['value']
        ]);

      if ($hasil) {
        $nowDate = date('Y-m-d');
        $nowTime = date('H:i:s');
        $this->db('permintaan_lab')
          ->where('no_rawat', $input['no_rawat'])
          ->where('tgl_permintaan', $input['tgl_periksa'])
          ->where('jam_permintaan', $input['jam'])
          ->save([
            'tgl_hasil' => $nowDate,
            'jam_hasil' => $nowTime
          ]);

        // Simpan langsung tgl_selesai ke detail_cetak_lab jika belum ada
        $now = $nowDate . ' ' . $nowTime;
        $sk = $this->db('detail_cetak_lab')->where('no_rawat', $input['no_rawat'])->oneArray();
        if ($sk) {
          $up = [];
          if (empty($sk['tgl_selesai'])) $up['tgl_selesai'] = $now;
          if (empty($sk['tgl_validasi'])) $up['tgl_validasi'] = $now;
          $tgl_reg = !empty($sk['tgl_registrasi']) ? $sk['tgl_registrasi'] : '';
          $tgl_val = !empty($up['tgl_validasi']) ? $up['tgl_validasi'] : (!empty($sk['tgl_validasi']) ? $sk['tgl_validasi'] : $now);
          if (empty($sk['respons_time']) && !empty($tgl_reg) && !empty($tgl_val)) {
            $regTs = strtotime($tgl_reg);
            $valTs = strtotime($tgl_val);
            if ($valTs && $regTs) {
              $diff = abs($valTs - $regTs);
              $hrs = floor($diff / 3600);
              $mins = floor(($diff % 3600) / 60);
              $secs = $diff % 60;
              $up['respons_time'] = sprintf('%d:%02d:%02d', $hrs, $mins, $secs);
            }
          }
          if (!empty($up)) $this->db('detail_cetak_lab')->where('no_rawat', $input['no_rawat'])->save($up);
        } else {
          $this->db('detail_cetak_lab')->save([
            'no_rawat' => $input['no_rawat'],
            'saran' => '',
            'kesan' => '',
            'tgl_selesai' => $now,
            'tgl_validasi' => $now,
            'no_lab' => '',
            'respons_time' => '',
            'jenis_layanan' => 'REGULAR',
            'petugas_pelaksana' => $this->core->getUserInfo('fullname', null, true)
          ]);
        }
        return ['status' => 'success', 'message' => 'Nilai saved'];
      }
      return ['status' => 'error', 'message' => 'Failed to save nilai'];
    } catch (\PDOException $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function apiSaveKeterangan()
  {
    $username = $this->core->checkAuth('POST');
    if (!$this->core->checkPermission($username, 'can_update', 'laboratorium')) {
      return ['status' => 'error', 'message' => 'Invalid User Permission Credentials'];
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input))
      $input = $_POST;

    try {
      $this->db('detail_periksa_lab')
        ->where('no_rawat', $input['no_rawat'])
        ->where('tgl_periksa', $input['tgl_periksa'])
        ->where('jam', $input['jam'])
        ->where('id_template', $input['id_template'])
        ->save([
          'keterangan' => $input['value']
        ]);
      return ['status' => 'success', 'message' => 'Keterangan saved'];
    } catch (\PDOException $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  public function anyManage($type = "ralan")
  {
    $tgl_kunjungan = date('Y-m-d');
    $tgl_kunjungan_akhir = date('Y-m-d');
    $status_periksa = '';
    $status_bayar = '';
    $status_pulang = '';

    if (isset($_POST['periode_rawat_jalan'])) {
      $tgl_kunjungan = $_POST['periode_rawat_jalan'];
    }
    if (isset($_POST['periode_rawat_jalan_akhir'])) {
      $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
    }
    if (isset($_POST['status_periksa'])) {
      $status_periksa = $_POST['status_periksa'];
    }
    if (isset($_POST['status_bayar'])) {
      $status_bayar = $_POST['status_bayar'];
    }
    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_pulang, $status_bayar, $type);
    return $this->draw('manage.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim), 'type' => $type, 'no_rawat_baru' => '', 'no_reg_baru' => '']);
  }

  public function anyDisplay()
  {
    $tgl_kunjungan = date('Y-m-d');
    $tgl_kunjungan_akhir = date('Y-m-d');
    $status_periksa = '';
    $status_bayar = '';
    $status_pulang = '';
    $type = htmlspecialchars(isset_or($_POST['status'], ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if (isset($_POST['periode_rawat_jalan'])) {
      $tgl_kunjungan = $_POST['periode_rawat_jalan'];
    }
    if (isset($_POST['periode_rawat_jalan_akhir'])) {
      $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
    }
    if (isset($_POST['status_periksa'])) {
      $status_periksa = $_POST['status_periksa'];
    }
    $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
    $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_pulang, $status_bayar, $type);
    echo $this->draw('display.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim), 'type' => $type, 'no_rawat_baru' => '', 'no_reg_baru' => '']);
    exit();
  }

  public function _Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa = '', $status_pulang = '', $status_bayar = '', $type = '')
  {
    $this->_addHeaderFiles();

    $this->assign['poliklinik'] = $this->db('poliklinik')->where('status', '1')->toArray();
    $this->assign['dokter'] = $this->db('dokter')->where('status', '1')->toArray();
    $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();
    $this->assign['no_rawat'] = '';
    $this->assign['no_reg'] = '';
    $this->assign['tgl_registrasi'] = date('Y-m-d');
    $this->assign['jam_reg'] = date('H:i:s');

    $params = [];
    $sql = "SELECT reg_periksa.*,
            pasien.*,
            dokter.*,
            poliklinik.*,
            penjab.*
          FROM reg_periksa, pasien, dokter, poliklinik, penjab
          WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
          AND reg_periksa.kd_dokter = dokter.kd_dokter
          AND reg_periksa.kd_poli = poliklinik.kd_poli
          AND reg_periksa.kd_pj = penjab.kd_pj";
    $params[] = $tgl_kunjungan;
    $params[] = $tgl_kunjungan_akhir;

    if ($status_periksa == 'belum') {
      $sql .= " AND reg_periksa.stts = 'Belum'";
    }
    if ($status_periksa == 'selesai') {
      $sql .= " AND reg_periksa.stts = 'Sudah'";
    }
    if ($status_periksa == 'lunas') {
      $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar'";
    }

    if ($type == 'permintaan') {
      $sql = "SELECT permintaan_lab.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM permintaan_lab 
                    JOIN reg_periksa ON permintaan_lab.no_rawat = reg_periksa.no_rawat 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN dokter ON permintaan_lab.dokter_perujuk = dokter.kd_dokter 
                    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    WHERE permintaan_lab.tgl_permintaan BETWEEN ? AND ?";
      $params[] = $tgl_kunjungan;
      $params[] = $tgl_kunjungan_akhir;

      if ($status_periksa != '') {
        $sql .= " AND permintaan_lab.status = ?";
        $params[] = ucfirst(strtolower($status_periksa));
      }
    } elseif ($type == 'ranap') {
      $sql = "SELECT
              kamar_inap.*,
              reg_periksa.*,
              pasien.*,
              kamar.*,
              bangsal.*,
              penjab.*
            FROM
              kamar_inap,
              reg_periksa,
              pasien,
              kamar,
              bangsal,
              penjab
            WHERE
              kamar_inap.no_rawat=reg_periksa.no_rawat
            AND
              reg_periksa.no_rkm_medis=pasien.no_rkm_medis
            AND
              kamar_inap.kd_kamar=kamar.kd_kamar
            AND
              bangsal.kd_bangsal=kamar.kd_bangsal
            AND
              reg_periksa.kd_pj=penjab.kd_pj";
      $params = []; // Reset params for new query

      if ($status_periksa == '') {
        $sql .= " AND kamar_inap.stts_pulang = '-'";
      }
      if ($status_periksa == 'belum') {
        $sql .= " AND kamar_inap.stts_pulang = '-' AND kamar_inap.tgl_masuk BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }
      if ($status_periksa == 'selesai') {
        $sql .= " AND kamar_inap.stts_pulang != '-' AND kamar_inap.tgl_masuk BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }
      if ($status_periksa == 'lunas') {
        $sql .= " AND kamar_inap.stts_pulang != '-' AND kamar_inap.tgl_keluar BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;
      }

    }

    $stmt = $this->db()->pdo()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $this->assign['list'] = [];
    if ($type == 'ranap') {
      foreach ($rows as $row) {
        $dpjp_ranap = $this->db('dpjp_ranap')
          ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
          ->where('no_rawat', $row['no_rawat'])
          ->toArray();
        $row['dokter'] = $dpjp_ranap;
        $this->assign['list'][] = $row;
      }
    } else {
      $this->assign['list'] = $rows;
    }

    if (isset($_POST['no_rawat'])) {
      $this->assign['reg_periksa'] = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
      if ($type == 'permintaan') {
        $sql = "SELECT permintaan_lab.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM permintaan_lab 
                    JOIN reg_periksa ON permintaan_lab.no_rawat = reg_periksa.no_rawat 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN dokter ON permintaan_lab.dokter_perujuk = dokter.kd_dokter 
                    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    WHERE permintaan_lab.tgl_permintaan BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;

        if ($status_periksa != '') {
          $sql .= " AND permintaan_lab.status = ?";
          $params[] = ucfirst(strtolower($status_periksa));
        }
      } elseif ($type == 'ranap') {
        $this->assign['kamar_inap'] = $this->db('kamar_inap')
          ->join('reg_periksa', 'reg_periksa.no_rawat=kamar_inap.no_rawat')
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->join('kamar', 'kamar.kd_kamar=kamar_inap.kd_kamar')
          ->join('dpjp_ranap', 'dpjp_ranap.no_rawat=kamar_inap.no_rawat')
          ->join('dokter', 'dokter.kd_dokter=dpjp_ranap.kd_dokter')
          ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
          ->where('kamar_inap.no_rawat', $_POST['no_rawat'])
          ->oneArray();
      }
    } else {
      $this->assign['reg_periksa'] = [
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'no_reg' => '',
        'no_rawat' => '',
        'tgl_registrasi' => '',
        'jam_reg' => '',
        'kd_dokter' => '',
        'no_rm' => '',
        'kd_poli' => '',
        'p_jawab' => '',
        'almt_pj' => '',
        'hubunganpj' => '',
        'biaya_reg' => '',
        'stts' => '',
        'stts_daftar' => '',
        'status_lanjut' => '',
        'kd_pj' => '',
        'umurdaftar' => '',
        'sttsumur' => '',
        'status_bayar' => '',
        'status_poli' => '',
        'nm_pasien' => '',
        'tgl_lahir' => '',
        'jk' => '',
        'alamat' => '',
        'no_tlp' => '',
        'pekerjaan' => ''
      ];
      if ($type == 'permintaan') {
        $sql = "SELECT permintaan_lab.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab 
                    FROM permintaan_lab 
                    JOIN reg_periksa ON permintaan_lab.no_rawat = reg_periksa.no_rawat 
                    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                    JOIN dokter ON permintaan_lab.dokter_perujuk = dokter.kd_dokter 
                    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli 
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj 
                    WHERE permintaan_lab.tgl_permintaan BETWEEN ? AND ?";
        $params[] = $tgl_kunjungan;
        $params[] = $tgl_kunjungan_akhir;

        if ($status_periksa != '') {
          $sql .= " AND permintaan_lab.status = ?";
          $params[] = ucfirst(strtolower($status_periksa));
        }
      } elseif ($type == 'ranap') {
        $this->assign['reg_periksa'] = [
          'tgl_masuk' => date('Y-m-d'),
          'jam_masuk' => date('H:i:s'),
          'tgl_keluar' => date('Y-m-d'),
          'jam_keluar' => date('H:i:s'),
          'no_rkm_medis' => '',
          'nm_pasien' => '',
          'tgl_lahir' => '',
          'jk' => '',
          'alamat' => '',
          'no_tlp' => '',
          'no_rawat' => '',
          'no_reg' => '',
          'kd_dokter' => '',
          'kd_kamar' => '',
          'kd_pj' => '',
          'diagnosa_awal' => '',
          'diagnosa_akhir' => '',
          'stts_pulang' => '',
          'lama' => ''
        ];
      }
    }
  }

  public function anyForm()
  {

    $this->assign['poliklinik'] = $this->db('poliklinik')->where('status', '1')->toArray();
    $this->assign['dokter'] = $this->db('dokter')->where('status', '1')->toArray();
    $this->assign['penjab'] = $this->db('penjab')->where('status', '1')->toArray();
    $this->assign['no_rawat'] = '';
    $this->assign['no_reg'] = '';
    $this->assign['tgl_registrasi'] = date('Y-m-d');
    $this->assign['jam_reg'] = date('H:i:s');
    if (isset($_POST['no_rawat'])) {
      $no_rawat = htmlspecialchars($_POST['no_rawat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $this->assign['reg_periksa'] = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $no_rawat)
        ->oneArray();
      echo $this->draw('form.html', [
        'rawat_jalan' => htmlspecialchars_array($this->assign),
        'no_rawat_baru' => '',
        'no_reg_baru' => ''
      ]);
    } else {
      $this->assign['reg_periksa'] = [
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'no_reg' => '',
        'no_rawat' => '',
        'tgl_registrasi' => '',
        'jam_reg' => '',
        'kd_dokter' => '',
        'no_rm' => '',
        'kd_poli' => '',
        'p_jawab' => '',
        'almt_pj' => '',
        'hubunganpj' => '',
        'biaya_reg' => '',
        'stts' => '',
        'stts_daftar' => '',
        'status_lanjut' => '',
        'kd_pj' => '',
        'umurdaftar' => '',
        'sttsumur' => '',
        'status_bayar' => '',
        'status_poli' => '',
        'nm_pasien' => '',
        'tgl_lahir' => '',
        'jk' => '',
        'alamat' => '',
        'no_tlp' => '',
        'pekerjaan' => ''
      ];
      echo $this->draw('form.html', [
        'rawat_jalan' => htmlspecialchars_array($this->assign),
        'no_rawat_baru' => '',
        'no_reg_baru' => ''
      ]);
    }
    exit();
  }

  public function anyStatusDaftar()
  {
    if (isset($_POST['no_rkm_medis'])) {
      $no_rkm_medis = htmlspecialchars($_POST['no_rkm_medis'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $rawat = $this->db('reg_periksa')
        ->where('no_rkm_medis', $no_rkm_medis)
        ->where('status_bayar', 'Belum Bayar')
        ->limit(1)
        ->oneArray();
      if ($rawat) {
        $stts_daftar = "Transaki tanggal " . date('Y-m-d', strtotime($rawat['tgl_registrasi'])) . " belum diselesaikan";
        $stts_daftar_hidden = $stts_daftar;
        if ($this->settings->get('settings.cekstatusbayar') == 'false') {
          $stts_daftar_hidden = 'Lama';
        }
        $bg_status = 'text-danger';
      } else {
        $result = $this->db('reg_periksa')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
        if ($result >= 1) {
          $stts_daftar = 'Lama';
          $bg_status = 'text-info';
          $stts_daftar_hidden = $stts_daftar;
        } else {
          $stts_daftar = 'Baru';
          $bg_status = 'text-success';
          $stts_daftar_hidden = $stts_daftar;
        }
      }
      echo $this->draw('stts.daftar.html', ['stts_daftar' => htmlspecialchars($stts_daftar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'stts_daftar_hidden' => htmlspecialchars($stts_daftar_hidden, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'bg_status' => htmlspecialchars($bg_status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')]);
    } else {
      $no_rawat = htmlspecialchars($_POST['no_rawat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $rawat = $this->db('reg_periksa')
        ->where('no_rawat', $no_rawat)
        ->oneArray();
      echo $this->draw('stts.daftar.html', [
        'stts_daftar' => htmlspecialchars($rawat['stts_daftar'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'stts_daftar_hidden' => htmlspecialchars($rawat['stts_daftar'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        'bg_status' => ''
      ]);
    }
    exit();
  }

  public function postSave()
  {
    if (!$this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray()) {

      $_POST['status_lanjut'] = 'Ralan';
      $_POST['stts'] = 'Belum';
      $_POST['status_bayar'] = 'Belum Bayar';
      $_POST['p_jawab'] = '-';
      $_POST['almt_pj'] = '-';
      $_POST['hubunganpj'] = '-';

      $poliklinik = $this->db('poliklinik')->where('kd_poli', $this->settings('settings', 'laboratorium'))->oneArray();

      $_POST['biaya_reg'] = $poliklinik['registrasi'];

      $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();

      $birthDate = new \DateTime($pasien['tgl_lahir']);
      $today = new \DateTime("today");
      $umur_daftar = "0";
      $status_umur = 'Hr';
      if ($birthDate < $today) {
        $y = $today->diff($birthDate)->y;
        $m = $today->diff($birthDate)->m;
        $d = $today->diff($birthDate)->d;
        $umur_daftar = $d;
        $status_umur = "Hr";
        if ($y != '0') {
          $umur_daftar = $y;
          $status_umur = "Th";
        }
        if ($y == '0' && $m != '0') {
          $umur_daftar = $m;
          $status_umur = "Bl";
        }
      }

      $_POST['umurdaftar'] = $umur_daftar;
      $_POST['sttsumur'] = $status_umur;
      $_POST['status_poli'] = 'Lama';
      $_POST['kd_poli'] = $this->settings('settings', 'laboratorium');

      $query = $this->db('reg_periksa')->save($_POST);
    } else {
      $query = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->update([
        'kd_dokter' => $_POST['kd_dokter'],
        'kd_pj' => $_POST['kd_pj']
      ]);
    }
    exit();
  }

  public function anyPasien()
  {
    $pasien = [];
    if (isset($_POST['cari'])) {
      $cari = htmlspecialchars($_POST['cari'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $pasien = $this->db('pasien')
        ->like('no_rkm_medis', '%' . $cari . '%')
        ->orLike('nm_pasien', '%' . $cari . '%')
        ->asc('no_rkm_medis')
        ->limit(5)
        ->toArray();
    }
    echo $this->draw('pasien.html', ['pasien' => htmlspecialchars_array($pasien)]);
    exit();
  }

  public function getAntrian()
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $no_rawat = htmlspecialchars($_GET['no_rawat'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $rawat_jalan = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
      ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
      ->where('no_rawat', $no_rawat)
      ->oneArray();
    echo $this->draw('antrian.html', ['rawat_jalan' => htmlspecialchars_array($rawat_jalan)]);
    exit();
  }

  public function getCetakHasil()
  {
    /* ===============================
     * SETTINGS
     * =============================== */
    $settings = $this->settings('settings');
    $this->tpl->set(
      'settings',
      $this->tpl->noParse_array(htmlspecialchars_array($settings))
    );

    /* ===============================
     * VALIDASI NO RAWAT
     * =============================== */
    $no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';
    if (strpos($no_rawat, '/') === false && strlen($no_rawat) >= 14) {
      $no_rawat = revertNorawat($no_rawat);
    }

    if (empty($no_rawat)) {
      echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h3>Nomor rawat tidak ditemukan.</h3></div>";
      exit;
    }

    /* ===============================
     * PENANGGUNG JAWAB LAB + QR
     * =============================== */
    $pj_lab = $this->db('dokter')
      ->where('kd_dokter', $this->settings->get('settings.pj_laboratorium'))
      ->oneArray();

    $pj_lab_nama = isset($pj_lab['nm_dokter']) ? $pj_lab['nm_dokter'] : 'Dokter Penanggung Jawab';

    $qr = QRCode::getMinimumQRCode(
      $pj_lab_nama,
      QR_ERROR_CORRECT_LEVEL_L
    );
    $im = $qr->createImage(4, 4);
    if (!is_dir(BASE_DIR . '/' . ADMIN . '/tmp')) {
      mkdir(BASE_DIR . '/' . ADMIN . '/tmp', 0777, true);
    }
    imagepng($im, BASE_DIR . '/' . ADMIN . '/tmp/qrcode.png');
    imagedestroy($im);

    $qrCode = url() . "/" . ADMIN . "/tmp/qrcode.png";

    /* ===============================
     * DATA PASIEN & REGISTRASI
     * =============================== */
    $pasien = $this->db('reg_periksa')
      ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
      ->leftJoin('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
      ->where('reg_periksa.no_rawat', $no_rawat)
      ->oneArray();

    if (!$pasien) {
      echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h3>Data pasien tidak ditemukan untuk No. Rawat " . htmlspecialchars($no_rawat, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ".</h3></div>";
      exit;
    }

    $reg_periksa = $this->db('reg_periksa')
      ->where('no_rawat', $no_rawat)
      ->oneArray();

    // Cek kamar inap jika rawat inap
    $kamar = $this->db('kamar_inap')
      ->join('kamar', 'kamar.kd_kamar=kamar_inap.kd_kamar')
      ->join('bangsal', 'bangsal.kd_bangsal=kamar.kd_bangsal')
      ->where('kamar_inap.no_rawat', $no_rawat)
      ->desc('kamar_inap.tgl_masuk')
      ->desc('kamar_inap.jam_masuk')
      ->oneArray();

    $unit_pelayanan = isset($pasien['nm_poli']) ? $pasien['nm_poli'] : '-';
    if ($kamar) {
      $unit_pelayanan = $kamar['nm_bangsal'] . ' (' . $kamar['kd_kamar'] . ')';
    }

    /* ===============================
     * DATA DETAIL CETAK LAB (SARAN, KESAN, DLL)
     * =============================== */
    $sk = $this->db('detail_cetak_lab')
      ->where('no_rawat', $no_rawat)
      ->oneArray();

    $permintaan_lab_order = $this->db('permintaan_lab')
      ->where('no_rawat', $no_rawat)
      ->toArray();

    $sk_kesan = isset($sk['kesan']) ? $sk['kesan'] : '';
    $sk_saran = isset($sk['saran']) ? $sk['saran'] : '';
    $sk_tgl_selesai = isset($sk['tgl_selesai']) ? $sk['tgl_selesai'] : '';
    $sk_tgl_validasi = isset($sk['tgl_validasi']) ? $sk['tgl_validasi'] : '';
    $sk_no_lab = (isset($sk['no_lab']) && $sk['no_lab'] != '') ? $sk['no_lab'] : (isset($permintaan_lab_order[0]['noorder']) ? $permintaan_lab_order[0]['noorder'] : '');
    $sk_respons_time = isset($sk['respons_time']) ? $sk['respons_time'] : '';
    $sk_jenis_layanan = (isset($sk['jenis_layanan']) && $sk['jenis_layanan'] != '') ? $sk['jenis_layanan'] : 'REGULAR';
    $sk_petugas_pelaksana = (isset($sk['petugas_pelaksana']) && $sk['petugas_pelaksana'] != '') ? $sk['petugas_pelaksana'] : $this->core->getUserInfo('fullname', null, true);

    /* ===============================
     * DATA PEMERIKSAAN LAB
     * =============================== */
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $rows_query = $this->db('periksa_lab')
      ->join(
        'jns_perawatan_lab',
        'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw'
      )
      ->leftJoin('dokter', 'dokter.kd_dokter=periksa_lab.dokter_perujuk')
      ->leftJoin('pegawai', 'pegawai.nik=periksa_lab.dokter_perujuk')
      ->where('periksa_lab.no_rawat', $no_rawat);

    if (!empty($status)) {
      $rows_query->where('periksa_lab.status', ucfirst(strtolower($status)));
    }

    if (isset($_GET['tgl_periksa']) && isset($_GET['jam'])) {
      $rows_query->where('periksa_lab.tgl_periksa', $_GET['tgl_periksa'])
                 ->where('periksa_lab.jam', $_GET['jam']);
    }

    $rows = $rows_query->asc('periksa_lab.tgl_periksa')->asc('periksa_lab.jam')->toArray();

    // Fallback tanpa filter status jika data belum ketemu
    if (empty($rows) && !empty($status)) {
      $rows = $this->db('periksa_lab')
        ->join(
          'jns_perawatan_lab',
          'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw'
        )
        ->leftJoin('dokter', 'dokter.kd_dokter=periksa_lab.dokter_perujuk')
        ->leftJoin('pegawai', 'pegawai.nik=periksa_lab.dokter_perujuk')
        ->where('periksa_lab.no_rawat', $no_rawat)
        ->asc('periksa_lab.tgl_periksa')->asc('periksa_lab.jam')->toArray();
    }

    $nm_dokter_perujuk = '-';
    if (!empty($rows)) {
      $first = $rows[0];
      $nm_dokter_perujuk = !empty($first['nm_dokter']) ? $first['nm_dokter'] : (!empty($first['nama']) ? $first['nama'] : (!empty($first['dokter_perujuk']) ? $first['dokter_perujuk'] : '-'));
    }

    /* ===============================
     * FORMAT DATA UNTUK CETAK LAB
     * =============================== */
    // 1. Tanggal Registrasi: berdasarkan waktu klik validasi dari e-laboratorium yg dikirim dokter
    $sk_tgl_registrasi = isset($sk['tgl_registrasi']) ? $sk['tgl_registrasi'] : '';
    if (empty($sk_tgl_registrasi)) {
      if (!empty($permintaan_lab_order[0]['tgl_sampel']) && $permintaan_lab_order[0]['tgl_sampel'] != '0000-00-00') {
        $sk_tgl_registrasi = $permintaan_lab_order[0]['tgl_sampel'] . ' ' . $permintaan_lab_order[0]['jam_sampel'];
      } elseif (!empty($reg_periksa['tgl_registrasi'])) {
        $sk_tgl_registrasi = $reg_periksa['tgl_registrasi'] . ' ' . $reg_periksa['jam_reg'];
      }
    }

    // 2. Tanggal Periksa: diisi manual
    $sk_tgl_periksa = isset($sk['tgl_periksa']) ? $sk['tgl_periksa'] : '';
    if (empty($sk_tgl_periksa)) {
      if (!empty($rows[0]['tgl_periksa'])) {
        $sk_tgl_periksa = $rows[0]['tgl_periksa'] . ' ' . $rows[0]['jam'];
      }
    }

    // 3. Tanggal Validasi: berdasarkan waktu pembubuhan ttd expertise
    $ref_id_lab = 'lab_' . str_replace('/', '', $no_rawat);
    $signature_expertise = null;
    try {
      $signature_expertise = $this->db('mlite_esignatures')
        ->where('ref_type', 'laboratorium_hasil')
        ->where('ref_id', $ref_id_lab)
        ->oneArray();
    } catch (\Throwable $e) {}

    // Tanggal Selesai
    if (empty($sk_tgl_selesai)) {
      if (!empty($permintaan_lab_order[0]['tgl_hasil']) && $permintaan_lab_order[0]['tgl_hasil'] != '0000-00-00') {
        $sk_tgl_selesai = $permintaan_lab_order[0]['tgl_hasil'] . ' ' . $permintaan_lab_order[0]['jam_hasil'];
      } elseif (!empty($sk_tgl_periksa)) {
        $sk_tgl_selesai = $sk_tgl_periksa;
      } else {
        $sk_tgl_selesai = date('Y-m-d H:i:s');
      }
    }

    // 3. Tanggal Validasi: berdasarkan waktu pembubuhan ttd expertise (jika belum, default ke tgl_selesai / waktu sekarang)
    $sk_tgl_validasi = isset($sk['tgl_validasi']) ? $sk['tgl_validasi'] : '';
    if (!empty($signature_expertise['signed_at'])) {
      $sk_tgl_validasi = $signature_expertise['signed_at'];
    } elseif (empty($sk_tgl_validasi)) {
      $sk_tgl_validasi = !empty($sk_tgl_selesai) ? $sk_tgl_selesai : date('Y-m-d H:i:s');
    }

    // Respons Time selalu dihitung dari Tanggal Registrasi (validasi e-lab) hingga Tanggal Validasi (ttd expertise)
    $respons_hitung = $this->hitungResponsTime($sk_tgl_registrasi, $sk_tgl_validasi);
    if ($respons_hitung !== '') {
      $sk_respons_time = $respons_hitung;
    }

    // QR Code untuk TTD Expertise jika sudah tervalidasi
    $qrCodeExpertise = '';
    if (!empty($signature_expertise['signature_hash'])) {
      $verifyUrl = url() . '/esignature/verify/' . $signature_expertise['signature_hash'];
      $qrExp = \Systems\Lib\QRCode::getMinimumQRCode($verifyUrl, QR_ERROR_CORRECT_LEVEL_L);
      $imExp = $qrExp->createImage(4, 4);
      $qrDir = BASE_DIR . '/' . ADMIN . '/tmp';
      if (!is_dir($qrDir)) {
        @mkdir($qrDir, 0755, true);
      }
      $qrPathExp = $qrDir . '/qrcode_lab_' . str_replace('/', '', $no_rawat) . '.png';
      imagepng($imExp, $qrPathExp);
      imagedestroy($imExp);
      $qrCodeExpertise = url() . '/' . ADMIN . '/tmp/qrcode_lab_' . str_replace('/', '', $no_rawat) . '.png?t=' . time();
    }

    // Simpan permanen ke detail_cetak_lab
    if ($sk) {
      $upNeeded = [];
      if (empty($sk['tgl_registrasi']) && !empty($sk_tgl_registrasi)) $upNeeded['tgl_registrasi'] = $sk_tgl_registrasi;
      if (empty($sk['tgl_periksa']) && !empty($sk_tgl_periksa)) $upNeeded['tgl_periksa'] = $sk_tgl_periksa;
      if (!empty($sk_tgl_validasi) && $sk['tgl_validasi'] !== $sk_tgl_validasi) $upNeeded['tgl_validasi'] = $sk_tgl_validasi;
      if (empty($sk['tgl_selesai']) && !empty($sk_tgl_selesai)) $upNeeded['tgl_selesai'] = $sk_tgl_selesai;
      if (empty($sk['no_lab']) && !empty($sk_no_lab)) $upNeeded['no_lab'] = $sk_no_lab;
      if (!empty($sk_respons_time) && (empty($sk['respons_time']) || $sk['respons_time'] !== $sk_respons_time)) $upNeeded['respons_time'] = $sk_respons_time;
      if (empty($sk['jenis_layanan']) && !empty($sk_jenis_layanan)) $upNeeded['jenis_layanan'] = $sk_jenis_layanan;
      if (empty($sk['petugas_pelaksana']) && !empty($sk_petugas_pelaksana)) $upNeeded['petugas_pelaksana'] = $sk_petugas_pelaksana;
      if (!empty($upNeeded)) {
        $this->db('detail_cetak_lab')->where('no_rawat', $no_rawat)->save($upNeeded);
      }
    } else {
      $this->db('detail_cetak_lab')->save([
        'no_rawat' => $no_rawat,
        'tgl_registrasi' => $sk_tgl_registrasi,
        'tgl_periksa' => $sk_tgl_periksa,
        'saran' => $sk_saran,
        'kesan' => $sk_kesan,
        'tgl_selesai' => $sk_tgl_selesai,
        'tgl_validasi' => $sk_tgl_validasi,
        'no_lab' => $sk_no_lab,
        'respons_time' => $sk_respons_time,
        'jenis_layanan' => $sk_jenis_layanan,
        'petugas_pelaksana' => $sk_petugas_pelaksana
      ]);
    }

    $formatTglIndo = function($datetime, $withTime = true) {
      if (empty($datetime) || $datetime == '0000-00-00' || $datetime == '0000-00-00 00:00:00') return '-';
      $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
      ];
      $ts = strtotime($datetime);
      if (!$ts) return $datetime;
      $d = date('d', $ts);
      $m = (int)date('m', $ts);
      $y = date('Y', $ts);
      $time = date('H:i', $ts);
      if ($withTime && (strpos($datetime, ':') !== false || strpos($datetime, 'T') !== false)) {
        return ltrim($d, '0') . ' ' . $bulan[$m] . ' ' . $y . '  ' . $time;
      }
      return ltrim($d, '0') . ' ' . $bulan[$m] . ' ' . $y;
    };

    $ket_klinis = '-';
    if (!empty($permintaan_lab_order[0]['diagnosa_klinis'])) {
      $ket_klinis = $permintaan_lab_order[0]['diagnosa_klinis'];
    } else {
      $diag = $this->db('diagnosa_pasien')
        ->join('penyakit', 'penyakit.kd_penyakit=diagnosa_pasien.kd_penyakit')
        ->where('diagnosa_pasien.no_rawat', $no_rawat)
        ->where('diagnosa_pasien.prioritas', '1')
        ->oneArray();
      if ($diag && !empty($diag['nm_penyakit'])) {
        $ket_klinis = $diag['nm_penyakit'];
      }
    }

    $ttl_umur = (!empty($pasien['tgl_lahir']) ? date('d-m-Y', strtotime($pasien['tgl_lahir'])) : '-') . '/' . (isset($reg_periksa['umurdaftar']) ? $reg_periksa['umurdaftar'] : '') . ' ' . (isset($reg_periksa['sttsumur']) && $reg_periksa['sttsumur'] == 'Th' ? 'Tahun' : (isset($reg_periksa['sttsumur']) ? $reg_periksa['sttsumur'] : ''));
    $jk_label = (isset($pasien['jk']) && $pasien['jk'] == 'L') ? 'Laki-laki' : ((isset($pasien['jk']) && $pasien['jk'] == 'P') ? 'Perempuan' : '-');
    $ruang_alamat = $unit_pelayanan . '/' . (isset($pasien['alamat']) ? $pasien['alamat'] : '-');

    $tgl_registrasi_fmt = !empty($sk_tgl_registrasi) ? $formatTglIndo($sk_tgl_registrasi) : '-';
    $tgl_periksa_fmt = !empty($sk_tgl_periksa) ? $formatTglIndo($sk_tgl_periksa) : '-';
    $tgl_selesai_fmt = !empty($sk_tgl_selesai) ? $formatTglIndo($sk_tgl_selesai) : '-';
    $tgl_validasi_fmt = !empty($sk_tgl_validasi) ? $formatTglIndo($sk_tgl_validasi) : '-';
    $tgl_cetak = $formatTglIndo(!empty($sk_tgl_validasi) ? $sk_tgl_validasi : (!empty($sk_tgl_periksa) ? $sk_tgl_periksa : date('Y-m-d')), false);

    $pj_lab_sip = 'SIP. 500.16.7.2/0520-SIPO-Sp/XII.25/DPMPTSP';
    $petugas_nama = !empty($sk_petugas_pelaksana) ? $sk_petugas_pelaksana : 'Nida Urrahmah, S.Tr.Kes';
    $petugas_sip = 'SIP. 500.16.7.2/0073-SIP-ATLM/IX.25/DPMPTSP';

    $is_male = (isset($pasien['jk']) && strtoupper($pasien['jk']) == 'L');
    $is_adult = (isset($reg_periksa['umurdaftar']) && ((int)$reg_periksa['umurdaftar'] >= 16 || $reg_periksa['sttsumur'] == 'Th'));

    $periksa_lab = [];
    $jumlah_total_lab = 0;
    $no = 1;

    foreach ($rows as $row) {
      $jumlah_total_lab += $row['biaya'];
      $row['nomor'] = $no++;

      $details = $this->db('detail_periksa_lab')
        ->join(
          'template_laboratorium',
          'template_laboratorium.id_template=detail_periksa_lab.id_template'
        )
        ->where('detail_periksa_lab.no_rawat', $no_rawat)
        ->where('detail_periksa_lab.tgl_periksa', $row['tgl_periksa'])
        ->where('detail_periksa_lab.jam', $row['jam'])
        ->where(
          'detail_periksa_lab.kd_jenis_prw',
          $row['kd_jenis_prw']
        )
        ->asc('template_laboratorium.urut')
        ->toArray();

      foreach ($details as &$det) {
        if ($is_male) {
          $rujukan = $is_adult ? $det['nilai_rujukan_ld'] : $det['nilai_rujukan_la'];
        } else {
          $rujukan = $is_adult ? $det['nilai_rujukan_pd'] : $det['nilai_rujukan_pa'];
        }
        if (empty($rujukan)) {
          $rujukan = !empty($det['nilai_rujukan']) ? $det['nilai_rujukan'] : (!empty($det['nilai_rujukan_ld']) ? $det['nilai_rujukan_ld'] : $det['nilai_rujukan_pd']);
        }
        $det['rujukan_aktif'] = $rujukan;

        // Evaluasi apakah hasil berada di luar rentang rujukan (tampilkan panah merah)
        $raw_val = trim((string)$det['nilai']);
        $clean_val = str_replace(',', '.', $raw_val);
        $det['is_abnormal'] = false;
        $det['hasil_formatted'] = $raw_val;

        if (strpos($raw_val, '↓') !== false || strpos($raw_val, '↑') !== false || strpos($raw_val, '*') !== false) {
          $det['is_abnormal'] = true;
        } elseif (is_numeric($clean_val) && !empty($rujukan)) {
          $num = (float)$clean_val;
          if (preg_match('/^([0-9\.]+)\s*[\-\–]\s*([0-9\.]+)$/', trim($rujukan), $m)) {
            $min = (float)$m[1];
            $max = (float)$m[2];
            if ($num < $min) {
              $det['is_abnormal'] = true;
              $det['hasil_formatted'] = $raw_val . ' &darr;';
            } elseif ($num > $max) {
              $det['is_abnormal'] = true;
              $det['hasil_formatted'] = $raw_val . ' &uarr;';
            }
          } elseif (preg_match('/^<\s*([0-9\.]+)$/', trim($rujukan), $m)) {
            if ($num >= (float)$m[1]) {
              $det['is_abnormal'] = true;
              $det['hasil_formatted'] = $raw_val . ' &uarr;';
            }
          } elseif (preg_match('/^>\s*([0-9\.]+)$/', trim($rujukan), $m)) {
            if ($num <= (float)$m[1]) {
              $det['is_abnormal'] = true;
              $det['hasil_formatted'] = $raw_val . ' &darr;';
            }
          }
        }
      }
      unset($det);

      $row['detail_periksa_lab'] = $details;
      $periksa_lab[] = $row;
    }

    /* ===============================
     * FILENAME PDF
     * =============================== */
    $safe_norawat = $this->convertNorawat($no_rawat);
    $safe_kd = !empty($rows[0]['kd_jenis_prw']) ? $rows[0]['kd_jenis_prw'] : 'ALL';
    $safe_tgl = !empty($rows[0]['tgl_periksa']) ? $rows[0]['tgl_periksa'] : date('Y-m-d');
    $filename = $safe_norawat . '_' . $safe_kd . '_' . $safe_tgl;

    if (file_exists(UPLOADS . '/laboratorium/' . $filename . '.pdf')) {
      @unlink(UPLOADS . '/laboratorium/' . $filename . '.pdf');
    }

    /* ===============================
     * INJECT DATA KE TEMPLATE
     * =============================== */
    $this->tpl->set('periksa_lab', $periksa_lab);
    $this->tpl->set('jumlah_total_lab', $jumlah_total_lab);
    $this->tpl->set('qrCode', $qrCode);
    $this->tpl->set('pj_lab', $pj_lab_nama);
    $this->tpl->set('pj_lab_sip', $pj_lab_sip);
    $this->tpl->set('petugas_nama', $petugas_nama);
    $this->tpl->set('petugas_sip', $petugas_sip);
    $this->tpl->set('dokter_perujuk', $nm_dokter_perujuk);
    $this->tpl->set('pasien', $pasien);
    $this->tpl->set('reg_periksa', $reg_periksa);
    $this->tpl->set('unit_pelayanan', $unit_pelayanan);
    $this->tpl->set('ttl_umur', $ttl_umur);
    $this->tpl->set('jk_label', $jk_label);
    $this->tpl->set('ruang_alamat', $ruang_alamat);
    $this->tpl->set('ket_klinis', $ket_klinis);
    $this->tpl->set('tgl_registrasi_fmt', $tgl_registrasi_fmt);
    $this->tpl->set('tgl_periksa_fmt', $tgl_periksa_fmt);
    $this->tpl->set('tgl_selesai_fmt', $tgl_selesai_fmt);
    $this->tpl->set('tgl_validasi_fmt', $tgl_validasi_fmt);
    $this->tpl->set('tgl_cetak', $tgl_cetak);
    $this->tpl->set('filename', $filename);
    $this->tpl->set('no_rawat', $no_rawat);
    $this->tpl->set('wagateway', $this->settings->get('wagateway'));
    $this->tpl->set('sk_kesan', $sk_kesan);
    $this->tpl->set('sk_saran', $sk_saran);
    $this->tpl->set('sk_tgl_selesai', $sk_tgl_selesai);
    $this->tpl->set('sk_tgl_validasi', $sk_tgl_validasi);
    $this->tpl->set('sk_no_lab', $sk_no_lab);
    $this->tpl->set('sk_respons_time', $sk_respons_time);
    $this->tpl->set('sk_jenis_layanan', $sk_jenis_layanan);
    $this->tpl->set('sk_petugas_pelaksana', $sk_petugas_pelaksana);
    $this->tpl->set('signature_expertise', $signature_expertise);
    $this->tpl->set('qrCodeExpertise', $qrCodeExpertise);

    /* ===============================
     * RENDER HTML (PREVIEW LANGSUNG CEPAT)
     * =============================== */
    if (!isset($_GET['action']) || $_GET['action'] != 'pdf') {
      echo $this->draw('cetakhasil.html');
      exit;
    }

    /* ===============================
     * GENERATE PDF ON-DEMAND (action=pdf)
     * =============================== */
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }

    $html = $this->draw('cetakhasil.html');

    try {
      if (!empty($filename) && class_exists('\Mpdf\Mpdf')) {
        $mpdf = new \Mpdf\Mpdf([
          'mode' => 'utf-8',
          'format' => 'A4',
          'orientation' => 'P'
        ]);

        $mpdfHtml = $html;
        // Hapus script dan link css eksternal agar mPDF tidak melakukan HTTP request ke server sendiri
        $mpdfHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $mpdfHtml);
        $mpdfHtml = preg_replace('/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/is', '', $mpdfHtml);

        $baseUrl = url();
        if (!empty($baseUrl)) {
          $mpdfHtml = str_replace($baseUrl . '/assets/', BASE_DIR . '/assets/', $mpdfHtml);
          $mpdfHtml = str_replace($baseUrl . '/' . ADMIN . '/tmp/', BASE_DIR . '/' . ADMIN . '/tmp/', $mpdfHtml);
          $mpdfHtml = str_replace($baseUrl . '/uploads/', BASE_DIR . '/uploads/', $mpdfHtml);
          $mpdfHtml = preg_replace('/(\.(?:png|jpe?g|gif))\?[^"\'\s>]+/i', '$1', $mpdfHtml);
        }

        $mpdf->WriteHTML(
          $this->core->setPrintCss(),
          \Mpdf\HTMLParserMode::HEADER_CSS
        );

        $mpdf->WriteHTML($mpdfHtml, \Mpdf\HTMLParserMode::HTML_BODY);

        // Simpan PDF ke server
        $mpdf->Output(
          UPLOADS . '/laboratorium/' . $filename . '.pdf',
          'F'
        );
        echo "OK";
      }
    } catch (\Throwable $e) {
      error_log("Laboratorium mPDF error: " . $e->getMessage());
    }

    exit;
  }

  public function getCetakPermintaan()
  {
    $settings = $this->settings('settings');
    $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
    $pj_lab = $this->db('dokter')->where('kd_dokter', $this->settings->get('settings.pj_laboratorium'))->oneArray();
    $pj_lab_nama = isset($pj_lab['nm_dokter']) ? $pj_lab['nm_dokter'] : 'dr. Burhanuddin Said, Sp.PK';

    $qr = QRCode::getMinimumQRCode($pj_lab_nama, QR_ERROR_CORRECT_LEVEL_L);
    $im = $qr->createImage(4, 4);
    $qrDir = BASE_DIR . '/' . ADMIN . '/tmp';
    if (!is_dir($qrDir)) {
      @mkdir($qrDir, 0755, true);
    }
    imagepng($im, $qrDir . '/qrcode.png');
    imagedestroy($im);
    $qrCode = url() . "/" . ADMIN . "/tmp/qrcode.png";

    $no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';
    if (strpos($no_rawat, '/') === false && strlen($no_rawat) >= 14) {
      $no_rawat = revertNorawat($no_rawat);
    }
    $noorder = isset($_GET['noorder']) ? trim($_GET['noorder']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    if (empty($no_rawat) && !empty($noorder)) {
      $pl = $this->db('permintaan_lab')->where('noorder', $noorder)->oneArray();
      if ($pl) {
        $no_rawat = $pl['no_rawat'];
        if (empty($status)) $status = $pl['status'];
      }
    }
    if (empty($noorder) && !empty($no_rawat)) {
      $pl = $this->db('permintaan_lab')->where('no_rawat', $no_rawat)->desc('tgl_permintaan')->desc('jam_permintaan')->oneArray();
      if ($pl) {
        $noorder = $pl['noorder'];
        if (empty($status)) $status = $pl['status'];
      }
    }

    $pasien = [];
    if (!empty($no_rawat)) {
      $pasien = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->leftJoin('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->where('reg_periksa.no_rawat', $no_rawat)
        ->oneArray();
    }

    $dokter_perujuk_nama = '-';
    $pl_data = null;
    if (!empty($noorder)) {
      $pl_data = $this->db('permintaan_lab')
        ->leftJoin('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
        ->leftJoin('pegawai', 'pegawai.nik=permintaan_lab.dokter_perujuk')
        ->where('permintaan_lab.noorder', $noorder)
        ->oneArray();
    } elseif (!empty($no_rawat)) {
      $pl_data = $this->db('permintaan_lab')
        ->leftJoin('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
        ->leftJoin('pegawai', 'pegawai.nik=permintaan_lab.dokter_perujuk')
        ->where('permintaan_lab.no_rawat', $no_rawat)
        ->desc('permintaan_lab.tgl_permintaan')
        ->desc('permintaan_lab.jam_permintaan')
        ->oneArray();
    }
    if ($pl_data) {
      $dokter_perujuk_nama = !empty($pl_data['nm_dokter']) ? $pl_data['nm_dokter'] : (!empty($pl_data['nama']) ? $pl_data['nama'] : ($pl_data['dokter_perujuk'] ?? '-'));
    }

    $q = $this->db('permintaan_lab');
    if (!empty($noorder)) {
      $q->where('noorder', $noorder);
    }
    if (!empty($no_rawat)) {
      $q->where('no_rawat', $no_rawat);
    }
    if (!empty($status)) {
      $q->where('status', strtolower($status));
    }
    $rows_permintaan_lab = $q->toArray();

    $permintaan_laboratorium = [];
    foreach ($rows_permintaan_lab as $row) {
      $rows2 = $this->db('permintaan_pemeriksaan_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=permintaan_pemeriksaan_lab.kd_jenis_prw')
        ->where('permintaan_pemeriksaan_lab.noorder', $row['noorder'])
        ->toArray();
      $row['permintaan_pemeriksaan_lab'] = [];
      foreach ($rows2 as $row2) {
        $row2['template_laboratorium'] = $this->db('template_laboratorium')->where('kd_jenis_prw', $row2['kd_jenis_prw'])->toArray();
        $row['permintaan_pemeriksaan_lab'][] = $row2;
      }
      $permintaan_laboratorium[] = $row;
    }

    echo $this->draw('cetakpermintaan.html', [
      'permintaan_laboratorium' => $permintaan_laboratorium,
      'qrCode' => $qrCode,
      'pj_lab' => $pj_lab_nama,
      'dokter_perujuk' => $dokter_perujuk_nama,
      'pasien' => $pasien,
      'no_rawat' => htmlspecialchars($no_rawat, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    ]);
    exit();
  }

  public function postHapus()
  {
    $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->delete();
    exit();
  }

  public function postSaveDetail($type = "ralan")
  {
    if ($_POST['kat'] == 'laboratorium') {
      $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
      
      $jml = 1;
      if (isset($_POST['jml_tindakan'])) {
        $jml = is_array($_POST['jml_tindakan']) ? $_POST['jml_tindakan'][0] : $_POST['jml_tindakan'];
      }
      $jml = (int) $jml;

      $periksa_lab = false;
      for ($i = 0; $i < $jml; $i++) {
        $periksa_lab = $this->db('periksa_lab')
          ->save([
            'no_rawat' => $_POST['no_rawat'],
            'nip' => $this->core->getUserInfo('username', null, true),
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'tgl_periksa' => $_POST['tgl_perawatan'],
            'jam' => date('H:i:s', strtotime($_POST['jam_rawat'] . ' +' . $i . '0 seconds')),
            'dokter_perujuk' => $_POST['kode_provider'],
            'bagian_rs' => $jns_perawatan['bagian_rs'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
            'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
            'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya' => $jns_perawatan['total_byr'],
            'kd_dokter' => $this->settings->get('settings.pj_laboratorium'),
            'status' => $_POST['status']
          ]);
      }
      if ($periksa_lab) {
        $template_laboratorium = $this->db('template_laboratorium')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->toArray();
        foreach ($template_laboratorium as $row) {
          for ($i = 0; $i < $jml; $i++) {
            $this->db('detail_periksa_lab')
              ->save([
                'no_rawat' => $_POST['no_rawat'],
                'kd_jenis_prw' => $_POST['kd_jenis_prw'],
                'tgl_periksa' => $_POST['tgl_perawatan'],
                'jam' => date('H:i:s', strtotime($_POST['jam_rawat'] . ' +' . $i . '0 seconds')),
                'id_template' => $row['id_template'],
                'nilai' => '',
                'nilai_rujukan' => $row['nilai_rujukan_ld'],
                'keterangan' => '',
                'bagian_rs' => $row['bagian_rs'],
                'bhp' => $row['bhp'],
                'bagian_perujuk' => $row['bagian_perujuk'],
                'bagian_dokter' => $row['bagian_dokter'],
                'bagian_laborat' => $row['bagian_laborat'],
                'kso' => $row['kso'],
                'menejemen' => $row['menejemen'],
                'biaya_item' => $row['biaya_item']
              ]);
          }
        }
      }
    }
    exit();
  }

  public function anySaveNilai()
  {
    $hasil = $this->db('detail_periksa_lab')
      ->where('no_rawat', $_REQUEST['no_rawat'])
      ->where('tgl_periksa', $_REQUEST['tgl_periksa'])
      ->where('jam', $_REQUEST['jam'])
      ->where('id_template', $_REQUEST['id_template'])
      ->save([
        'nilai' => $this->bersihkanTeks(isset($_REQUEST['value']) ? $_REQUEST['value'] : '', 200)
      ]);
    if ($hasil) {
      $nowDate = date('Y-m-d');
      $nowTime = date('H:i:s');
      $this->db('permintaan_lab')
        ->where('no_rawat', $_REQUEST['no_rawat'])
        ->where('tgl_permintaan', $_REQUEST['tgl_periksa'])
        ->where('jam_permintaan', $_REQUEST['jam'])
        ->save(
          [
            'tgl_hasil' => $nowDate,
            'jam_hasil' => $nowTime
          ]
        );
    }

    exit();
  }

  public function anySaveKeterangan()
  {
    $this->db('detail_periksa_lab')
      ->where('no_rawat', $_REQUEST['no_rawat'])
      ->where('tgl_periksa', $_REQUEST['tgl_periksa'])
      ->where('jam', $_REQUEST['jam'])
      ->where('id_template', $_REQUEST['id_template'])
      ->save([
        'keterangan' => $this->bersihkanTeks(isset($_REQUEST['value']) ? $_REQUEST['value'] : '', 60)
      ]);
    exit();
  }

  public function anySaveSaranKesan()
  {
    header('Content-Type: application/json');

    $no_rawat = isset($_REQUEST['no_rawat']) ? trim($_REQUEST['no_rawat']) : '';
    $column = isset($_REQUEST['column']) ? trim($_REQUEST['column']) : '';
    $value = isset($_REQUEST['value']) ? (string) $_REQUEST['value'] : '';

    $allowed = ['saran', 'kesan', 'tgl_selesai', 'tgl_validasi', 'no_lab', 'respons_time', 'jenis_layanan', 'petugas_pelaksana', 'tgl_registrasi', 'tgl_periksa'];
    if ($no_rawat === '' || !in_array($column, $allowed, true)) {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
      exit();
    }

    // Validasi & normalisasi nilai sesuai jenis kolom (sesuai panjang kolom di database)
    switch ($column) {
      case 'tgl_registrasi':
      case 'tgl_periksa':
      case 'tgl_selesai':
      case 'tgl_validasi':
        $value = $this->normalisasiWaktu($value);
        if ($value === false) {
          echo json_encode(['status' => 'error', 'message' => 'Format tanggal/jam tidak valid']);
          exit();
        }
        break;
      case 'jenis_layanan':
        $value = strtoupper(trim($value));
        if (!in_array($value, ['REGULAR', 'CITO'], true)) {
          echo json_encode(['status' => 'error', 'message' => 'Jenis layanan tidak valid']);
          exit();
        }
        break;
      case 'respons_time':
        $value = trim($value);
        if ($value !== '' && !preg_match('/^\d{1,5}:[0-5]\d:[0-5]\d$/', $value)) {
          echo json_encode(['status' => 'error', 'message' => 'Format respons time tidak valid']);
          exit();
        }
        break;
      case 'saran':
      case 'kesan':
        $value = $this->bersihkanTeks($value, 300);
        break;
      case 'no_lab':
        $value = $this->bersihkanTeks($value, 50);
        break;
      case 'petugas_pelaksana':
        $value = $this->bersihkanTeks($value, 100);
        break;
    }

    try {
      if (!$this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray()) {
        echo json_encode(['status' => 'error', 'message' => 'Data kunjungan tidak ditemukan']);
        exit();
      }

      $cek = $this->db('detail_cetak_lab')
        ->where('no_rawat', $no_rawat)
        ->oneArray();

      $update = [$column => $value];

      // Catatan: Tgl/Jam Periksa hanya disimpan di detail_cetak_lab (untuk laporan/cetak).
      // JANGAN diubah ke periksa_lab/detail_periksa_lab karena tanggal+jam di sana adalah
      // bagian kunci primer (billing & hasil) dan bisa bentrok/memutus hasil pemeriksaan.

      // Respons Time otomatis = Tgl/Jam Validasi - Tgl/Jam Registrasi
      if (($column === 'tgl_validasi' || $column === 'tgl_registrasi') && !empty($value)) {
        $tgl_reg = ($column === 'tgl_registrasi') ? $value : '';
        $tgl_val = ($column === 'tgl_validasi') ? $value : '';

        if (empty($tgl_reg)) {
          if ($cek && !empty($cek['tgl_registrasi'])) {
            $tgl_reg = $cek['tgl_registrasi'];
          } else {
            $pl = $this->db('permintaan_lab')->where('no_rawat', $no_rawat)->oneArray();
            if ($pl && !empty($pl['tgl_sampel']) && $pl['tgl_sampel'] != '0000-00-00') {
              $tgl_reg = $pl['tgl_sampel'] . ' ' . $pl['jam_sampel'];
            } else {
              $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
              if ($reg && !empty($reg['tgl_registrasi'])) {
                $tgl_reg = $reg['tgl_registrasi'] . ' ' . $reg['jam_reg'];
              }
            }
          }
        }

        if (empty($tgl_val)) {
          if ($cek && !empty($cek['tgl_validasi'])) {
            $tgl_val = $cek['tgl_validasi'];
          } else {
            $tgl_val = date('Y-m-d H:i:s');
          }
        }

        $respons = $this->hitungResponsTime($tgl_reg, $tgl_val);
        if ($respons !== '') {
          $update['respons_time'] = $respons;
        }
      }

      if ($cek) {
        $this->db('detail_cetak_lab')
          ->where('no_rawat', $no_rawat)
          ->save($update);
      } else {
        $data = [
          'no_rawat' => $no_rawat,
          'tgl_registrasi' => '',
          'tgl_periksa' => '',
          'saran' => '',
          'kesan' => '',
          'tgl_selesai' => '',
          'tgl_validasi' => '',
          'no_lab' => '',
          'respons_time' => '',
          'jenis_layanan' => 'REGULAR',
          'petugas_pelaksana' => $this->bersihkanTeks($this->core->getUserInfo('fullname', null, true), 100)
        ];
        foreach ($update as $k => $v) {
          $data[$k] = $v;
        }
        $this->db('detail_cetak_lab')->save($data);
      }
    } catch (\Throwable $e) {
      error_log('Laboratorium savesarankesan error: ' . $e->getMessage());
      echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
      exit();
    }

    echo json_encode(['status' => 'success', 'respons_time' => $update['respons_time'] ?? null]);
    exit();
  }

  /**
   * Bersihkan teks bebas dari klien sebelum disimpan: buang tag HTML, encode karakter berbahaya
   * (nilai disimpan dalam bentuk aman-HTML karena template mencetak apa adanya), ubah baris baru
   * menjadi <br>, dan potong sesuai panjang kolom database.
   */
  private function bersihkanTeks($value, $max = 255)
  {
    $value = trim(strip_tags((string) $value));
    $value = str_replace(['"', "'", '<', '>'], ['&quot;', '&#039;', '&lt;', '&gt;'], $value);
    $value = str_replace(["\r\n", "\r", "\n"], '<br>', $value);
    if (strlen($value) > $max) {
      $value = function_exists('mb_strcut') ? mb_strcut($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
      // Jangan sisakan entity/tag yang terpotong di ujung
      $value = preg_replace('/(&[#a-zA-Z0-9]*|<b?r?)$/', '', $value);
    }
    return $value;
  }

  /**
   * Normalisasi tanggal/jam (mis. 2026-10-27T15:50) ke 'Y-m-d H:i:s'.
   * Mengembalikan '' jika kosong dan false jika format tidak valid.
   */
  private function normalisasiWaktu($value)
  {
    $value = trim((string) $value);
    if ($value === '') {
      return '';
    }
    $ts = strtotime(str_replace('T', ' ', $value));
    return $ts ? date('Y-m-d H:i:s', $ts) : false;
  }

  /**
   * Respons Time = Tgl/Jam Validasi - Tgl/Jam Registrasi, format H:MM:SS ('' jika data belum lengkap).
   */
  private function hitungResponsTime($tgl_registrasi, $tgl_validasi)
  {
    if (empty($tgl_registrasi) || empty($tgl_validasi)) {
      return '';
    }
    $regTs = strtotime($tgl_registrasi);
    $valTs = strtotime($tgl_validasi);
    if (!$regTs || !$valTs) {
      return '';
    }
    $diff = abs($valTs - $regTs);
    return sprintf('%d:%02d:%02d', floor($diff / 3600), floor(($diff % 3600) / 60), $diff % 60);
  }

  public function postHapusLaboratorium()
  {
    $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray();
    if ($reg_periksa['status_bayar'] == 'Sudah Bayar' && $this->core->getUserInfo('role') != 'admin') {
      exit();
    }

    $periksa_lab = $this->db('periksa_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
      ->where('tgl_periksa', $_POST['tgl_perawatan'])
      ->where('jam', $_POST['jam_rawat'])
      ->where('status', 'Ralan')
      ->delete();
    if ($periksa_lab) {
      $this->db('detail_periksa_lab')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_periksa', $_POST['tgl_perawatan'])
        ->where('jam', $_POST['jam_rawat'])
        ->delete();
    }
    exit();
  }

  public function anyRincian()
  {

    $status = !empty($_POST['status']) ? trim($_POST['status']) : '';
    if (empty($status) && !empty($_POST['no_rawat'])) {
      $reg = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray();
      if ($reg && !empty($reg['status_lanjut'])) {
        $status = $reg['status_lanjut'];
      }
    }

    $rows_query = $this->db('permintaan_lab')
      ->join('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
      ->where('no_rawat', $_POST['no_rawat']);
    if (!empty($status)) {
      $rows_query->where('permintaan_lab.status', strtolower($status));
    }
    $rows = $rows_query->toArray();
    if (empty($rows) && !empty($status)) {
      $rows = $this->db('permintaan_lab')
        ->join('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
    }
    $laboratorium = [];
    foreach ($rows as $row) {
      $rows2 = $this->db('permintaan_pemeriksaan_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=permintaan_pemeriksaan_lab.kd_jenis_prw')
        ->where('permintaan_pemeriksaan_lab.noorder', $row['noorder'])
        ->toArray();
      $row['permintaan_pemeriksaan_lab'] = [];
      foreach ($rows2 as $row2) {
        $row2['noorder'] = $row2['noorder'];
        $row2['kd_jenis_prw'] = $row2['kd_jenis_prw'];
        $row2['stts_bayar'] = $row2['stts_bayar'];
        $row2['nm_perawatan'] = $row2['nm_perawatan'];
        $row2['kd_pj'] = $row2['kd_pj'];
        $row2['status'] = $row2['status'];
        $row2['kelas'] = $row2['kelas'];
        $row2['kategori'] = $row2['kategori'];
        $rows3 = $this->db('permintaan_detail_permintaan_lab')->where('noorder', $row2['noorder'])->where('kd_jenis_prw', $row2['kd_jenis_prw'])->toArray();
        $row2['permintaan_detail_permintaan_lab'] = [];
        foreach ($rows3 as $row3) {
          $row3['template_laboratorium'] = $this->db('template_laboratorium')->where('kd_jenis_prw', $row3['kd_jenis_prw'])->where('id_template', $row3['id_template'])->oneArray();
          $row2['permintaan_detail_permintaan_lab'][] = $row3;
        }
        $row['permintaan_pemeriksaan_lab'][] = $row2;
      }
      $laboratorium[] = $row;
    }

    $rows_periksa_lab_query = $this->db('periksa_lab')
      ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
      ->join('dokter', 'dokter.kd_dokter=periksa_lab.dokter_perujuk')
      ->where('no_rawat', $_POST['no_rawat']);
    if (!empty($status)) {
      $rows_periksa_lab_query->where('periksa_lab.status', ucfirst(strtolower($status)));
    }
    $rows_periksa_lab = $rows_periksa_lab_query
      ->asc('periksa_lab.tgl_periksa')
      ->asc('periksa_lab.jam')
      ->toArray();
    if (empty($rows_periksa_lab) && !empty($status)) {
      $rows_periksa_lab = $this->db('periksa_lab')
        ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
        ->join('dokter', 'dokter.kd_dokter=periksa_lab.dokter_perujuk')
        ->where('no_rawat', $_POST['no_rawat'])
        ->asc('periksa_lab.tgl_periksa')
        ->asc('periksa_lab.jam')
        ->toArray();
    }

    $periksa_lab = [];
    $order_lab = [];
    foreach ($rows_periksa_lab as $row) {
      $key = $row['tgl_periksa'] . '_' . $row['jam'];
      if (!isset($order_lab[$key])) {
          $order_lab[$key] = [
              'tgl_periksa' => $row['tgl_periksa'],
              'jam' => $row['jam'],
              'tests' => [],
              'total_biaya' => 0
          ];
      }
      $row['detail_periksa_lab'] = $this->db('detail_periksa_lab')
        ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
        ->where('detail_periksa_lab.no_rawat', $_POST['no_rawat'])
        ->where('detail_periksa_lab.tgl_periksa', $row['tgl_periksa'])
        ->where('detail_periksa_lab.jam', $row['jam'])
        ->where('detail_periksa_lab.kd_jenis_prw', $row['kd_jenis_prw'])
        ->toArray();
      $order_lab[$key]['tests'][] = $row;
      $order_lab[$key]['total_biaya'] += $row['biaya'];
    }

    $no_lab = 1;
    foreach ($order_lab as &$order) {
      foreach ($order['tests'] as &$test) {
        $test['nomor'] = $no_lab++;
        $periksa_lab[] = $test;
      }
    }
    unset($order, $test);

    $pasien = $this->db('pasien')->where('no_rkm_medis', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))->oneArray();
    $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray();

    $sk = $this->db('detail_cetak_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->oneArray();

    $sk_no_lab = (isset($sk['no_lab']) && $sk['no_lab'] != '') ? $sk['no_lab'] : (isset($laboratorium[0]['noorder']) ? $laboratorium[0]['noorder'] : '');

    // 1. Tanggal Registrasi: berdasarkan waktu klik validasi dari e-laboratorium yg dikirim dokter
    $sk_tgl_registrasi = isset($sk['tgl_registrasi']) ? $sk['tgl_registrasi'] : '';
    if (empty($sk_tgl_registrasi)) {
      if (!empty($laboratorium[0]['tgl_sampel']) && $laboratorium[0]['tgl_sampel'] != '0000-00-00') {
        $sk_tgl_registrasi = $laboratorium[0]['tgl_sampel'] . ' ' . $laboratorium[0]['jam_sampel'];
      } elseif (!empty($reg_periksa['tgl_registrasi'])) {
        $sk_tgl_registrasi = $reg_periksa['tgl_registrasi'] . ' ' . $reg_periksa['jam_reg'];
      }
    }

    // 2. Tanggal Periksa: diisi manual oleh petugas lab
    $sk_tgl_periksa = isset($sk['tgl_periksa']) ? $sk['tgl_periksa'] : '';
    if (empty($sk_tgl_periksa)) {
      if (!empty($rows_periksa_lab[0]['tgl_periksa'])) {
        $sk_tgl_periksa = $rows_periksa_lab[0]['tgl_periksa'] . ' ' . $rows_periksa_lab[0]['jam'];
      } else {
        $sk_tgl_periksa = date('Y-m-d H:i:s');
      }
    }
    $sk_tgl_periksa_input = !empty($sk_tgl_periksa) ? date('Y-m-d\TH:i', strtotime($sk_tgl_periksa)) : '';

    // Tanggal Selesai
    $sk_tgl_selesai = isset($sk['tgl_selesai']) ? $sk['tgl_selesai'] : '';
    if (empty($sk_tgl_selesai)) {
      if (!empty($laboratorium[0]['tgl_hasil']) && $laboratorium[0]['tgl_hasil'] != '0000-00-00') {
        $sk_tgl_selesai = $laboratorium[0]['tgl_hasil'] . ' ' . $laboratorium[0]['jam_hasil'];
      } elseif (!empty($sk_tgl_periksa)) {
        $sk_tgl_selesai = $sk_tgl_periksa;
      } else {
        $sk_tgl_selesai = date('Y-m-d H:i:s');
      }
    }

    // 3. Tanggal Validasi: berdasarkan waktu pembubuhan ttd expertise (jika belum, default ke tgl_selesai / waktu sekarang)
    $ref_id_lab = 'lab_' . str_replace('/', '', $_POST['no_rawat']);
    $signature_expertise = null;
    try {
      $signature_expertise = $this->db('mlite_esignatures')
        ->where('ref_type', 'laboratorium_hasil')
        ->where('ref_id', $ref_id_lab)
        ->oneArray();
    } catch (\Throwable $e) {}

    $sk_tgl_validasi = isset($sk['tgl_validasi']) ? $sk['tgl_validasi'] : '';
    if (!empty($signature_expertise['signed_at'])) {
      $sk_tgl_validasi = $signature_expertise['signed_at'];
    } elseif (empty($sk_tgl_validasi)) {
      $sk_tgl_validasi = !empty($sk_tgl_selesai) ? $sk_tgl_selesai : date('Y-m-d H:i:s');
    }

    // Respons time selalu dihitung: tgl_registrasi (validasi e-lab) s.d. tgl_validasi (ttd expertise)
    $sk_respons_time = isset($sk['respons_time']) ? $sk['respons_time'] : '';
    $respons_hitung = $this->hitungResponsTime($sk_tgl_registrasi, $sk_tgl_validasi);
    if ($respons_hitung !== '') {
      $sk_respons_time = $respons_hitung;
    }

    $sk_petugas_pelaksana = (isset($sk['petugas_pelaksana']) && $sk['petugas_pelaksana'] != '') ? $sk['petugas_pelaksana'] : $this->core->getUserInfo('fullname', null, true);
    $sk_jenis_layanan = (isset($sk['jenis_layanan']) && $sk['jenis_layanan'] != '') ? $sk['jenis_layanan'] : 'REGULAR';
    $sk_kesan = isset($sk['kesan']) ? $sk['kesan'] : '';
    $sk_saran = isset($sk['saran']) ? $sk['saran'] : '';

    // Simpan langsung ke detail_cetak_lab jika belum terisi di database
    if (!$sk) {
      $this->db('detail_cetak_lab')->save([
        'no_rawat' => $_POST['no_rawat'],
        'tgl_registrasi' => $sk_tgl_registrasi,
        'tgl_periksa' => $sk_tgl_periksa,
        'saran' => $sk_saran,
        'kesan' => $sk_kesan,
        'tgl_selesai' => $sk_tgl_selesai,
        'tgl_validasi' => $sk_tgl_validasi,
        'no_lab' => $sk_no_lab,
        'respons_time' => $sk_respons_time,
        'jenis_layanan' => $sk_jenis_layanan,
        'petugas_pelaksana' => $sk_petugas_pelaksana
      ]);
    } else {
      $upNeeded = [];
      if (empty($sk['tgl_registrasi']) && !empty($sk_tgl_registrasi)) $upNeeded['tgl_registrasi'] = $sk_tgl_registrasi;
      if (empty($sk['tgl_periksa']) && !empty($sk_tgl_periksa)) $upNeeded['tgl_periksa'] = $sk_tgl_periksa;
      if (!empty($sk_tgl_validasi) && $sk['tgl_validasi'] !== $sk_tgl_validasi) $upNeeded['tgl_validasi'] = $sk_tgl_validasi;
      if (empty($sk['tgl_selesai']) && !empty($sk_tgl_selesai)) $upNeeded['tgl_selesai'] = $sk_tgl_selesai;
      if (empty($sk['no_lab']) && !empty($sk_no_lab)) $upNeeded['no_lab'] = $sk_no_lab;
      if (!empty($sk_respons_time) && (empty($sk['respons_time']) || $sk['respons_time'] !== $sk_respons_time)) $upNeeded['respons_time'] = $sk_respons_time;
      if (empty($sk['jenis_layanan']) && !empty($sk_jenis_layanan)) $upNeeded['jenis_layanan'] = $sk_jenis_layanan;
      if (empty($sk['petugas_pelaksana']) && !empty($sk_petugas_pelaksana)) $upNeeded['petugas_pelaksana'] = $sk_petugas_pelaksana;
      if (!empty($upNeeded)) {
        $this->db('detail_cetak_lab')->where('no_rawat', $_POST['no_rawat'])->save($upNeeded);
      }
    }

    // Format nilai tanggal untuk input datetime-local HTML (YYYY-MM-DDTHH:MM)
    $sk_tgl_selesai_input = !empty($sk_tgl_selesai) ? date('Y-m-d\TH:i', strtotime($sk_tgl_selesai)) : '';
    $sk_tgl_validasi_input = !empty($sk_tgl_validasi) ? date('Y-m-d\TH:i', strtotime($sk_tgl_validasi)) : '';

    $grand_total_biaya = 0;
    foreach ($order_lab as $order) {
        $grand_total_biaya += $order['total_biaya'];
    }

    echo $this->draw('rincian.html', [
      'sk_kesan' => $sk_kesan,
      'sk_saran' => $sk_saran,
      'sk_tgl_registrasi' => $sk_tgl_registrasi,
      'sk_tgl_periksa' => $sk_tgl_periksa,
      'sk_tgl_periksa_input' => $sk_tgl_periksa_input,
      'sk_tgl_selesai' => $sk_tgl_selesai,
      'sk_tgl_validasi' => $sk_tgl_validasi,
      'sk_tgl_selesai_input' => $sk_tgl_selesai_input,
      'sk_tgl_validasi_input' => $sk_tgl_validasi_input,
      'sk_no_lab' => $sk_no_lab,
      'sk_respons_time' => $sk_respons_time,
      'sk_jenis_layanan' => $sk_jenis_layanan,
      'sk_petugas_pelaksana' => $sk_petugas_pelaksana,
      'signature_expertise' => $signature_expertise,
      'periksa_lab' => $periksa_lab,
      'order_lab' => array_values($order_lab),
      'grand_total_biaya' => $grand_total_biaya,
      'no_rawat' => htmlspecialchars($_POST['no_rawat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
      'laboratorium' => htmlspecialchars_array($laboratorium),
      'pasien' => $pasien,
      'reg_periksa' => $reg_periksa
    ]);
    exit();
  }

  public function postHapusPermintaanLaboratorium()
  {
    $this->db('permintaan_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('noorder', $_POST['noorder'])
      ->where('tgl_permintaan', $_POST['tgl_permintaan'])
      ->where('jam_permintaan', $_POST['jam_permintaan'])
      ->where('status', 'Ralan')
      ->delete();
    exit();
  }

  public function getDetailPermintaan($noorder, $kd_jenis_prw)
  {
    $rows = $this->db('permintaan_detail_permintaan_lab')->where('noorder', $noorder)->where('kd_jenis_prw', $kd_jenis_prw)->toArray();
    $detail_permintaan_lab = [];
    foreach ($rows as $row) {
      $row['template_laboratorium'] = $this->db('template_laboratorium')->where('kd_jenis_prw', $row['kd_jenis_prw'])->where('id_template', $row['id_template'])->oneArray();
      $detail_permintaan_lab[] = $row;
    }
    $this->tpl->set('detail', $detail_permintaan_lab);
    echo $this->tpl->draw(MODULES . '/laboratorium/view/admin/details.html', true);
    exit();
  }

  private function getValidDokterPj()
  {
    $kd_dokter = $this->settings->get('settings.pj_laboratorium');
    if (!empty($kd_dokter)) {
      $cek = $this->db('dokter')->where('kd_dokter', $kd_dokter)->oneArray();
      if ($cek) {
        return $kd_dokter;
      }
    }
    $dokter = $this->db('dokter')->where('status', '1')->like('nm_dokter', '%Sp.PK%')->oneArray();
    if (!$dokter) {
      $dokter = $this->db('dokter')->where('status', '1')->oneArray();
    }
    return $dokter['kd_dokter'] ?? '251';
  }

  public function postValidasiPermintaanLab()
  {
    header('Content-Type: application/json');
    try {
      $role = $this->core->getUserInfo('role');
      if ($role != 'admin' && $role != 'laboratorium') {
        echo json_encode(['status' => 'error', 'message' => 'Hanya petugas laboratorium atau admin yang diperbolehkan memvalidasi permintaan laboratorium.']);
        exit();
      }

      $no_rawat = $_POST['no_rawat'] ?? '';
      $noorder = $_POST['noorder'] ?? '';
      $tgl_permintaan = !empty($_POST['tgl_permintaan']) ? $_POST['tgl_permintaan'] : date('Y-m-d');
      $jam_permintaan = !empty($_POST['jam_permintaan']) ? $_POST['jam_permintaan'] : date('H:i:s');

      if (empty($no_rawat) || empty($noorder)) {
        echo json_encode(['status' => 'error', 'message' => 'No rawat atau No order tidak valid']);
        exit();
      }

      $permintaan_lab = $this->db('permintaan_lab')->where('no_rawat', $no_rawat)->where('noorder', $noorder)->oneArray();
      if (!$permintaan_lab) {
        echo json_encode(['status' => 'error', 'message' => 'Data permintaan laboratorium tidak ditemukan']);
        exit();
      }

      // Tanggal/jam permintaan diambil dari data order di database (bukan dari input klien)
      $tgl_permintaan = $permintaan_lab['tgl_permintaan'];
      $jam_permintaan = $permintaan_lab['jam_permintaan'];

      $username = $this->core->getUserInfo('username', null, true);
      $nip = $username;
      $petugas_nama = $this->core->getUserInfo('fullname', null, true) ?: $username;

      // Jika user admin, pastikan record admin ada di tabel petugas agar foreign key periksa_lab tidak gagal
      if ($role == 'admin' || $nip == 'admin') {
        $cek_petugas = $this->db('petugas')->where('nip', $nip)->oneArray();
        if (!$cek_petugas) {
          try {
            $this->core->pdo()->exec("SET FOREIGN_KEY_CHECKS=0;");
            $this->db('petugas')->save([
              'nip' => $nip,
              'nama' => $petugas_nama,
              'jk' => 'L',
              'tmp_lahir' => '-',
              'tgl_lahir' => '1990-01-01',
              'gol_darah' => '-',
              'agama' => 'ISLAM',
              'stts_nikah' => 'MENIKAH',
              'alamat' => '-',
              'kd_jbtn' => '-',
              'no_telp' => '-',
              'status' => '1'
            ]);
          } catch (\Throwable $t) {
            // ignore
          } finally {
            // Selalu aktifkan kembali pengecekan FK pada koneksi ini, walaupun insert gagal
            $this->core->pdo()->exec("SET FOREIGN_KEY_CHECKS=1;");
          }
        }
      }

      // Semua penulisan di bawah ini atomik: jika salah satu gagal, tidak ada data setengah jadi
      $this->db()->pdo()->beginTransaction();

      // Order yang sudah pernah divalidasi (klik ulang) tidak boleh menimpa waktu registrasi
      $sudah_divalidasi = !empty($permintaan_lab['tgl_sampel']) && $permintaan_lab['tgl_sampel'] != '0000-00-00';

      if (!$sudah_divalidasi) {
        // Validasi waktu sampel
        $this->db('permintaan_lab')->where('no_rawat', $no_rawat)->where('noorder', $noorder)->save([
          'tgl_sampel' => date('Y-m-d'),
          'jam_sampel' => date('H:i:s')
        ]);

        // Simpan waktu klik validasi e-lab sebagai tgl_registrasi di detail_cetak_lab.
        // Setiap order baru memulai siklus baru: Periksa diisi default waktu registrasi,
        // sedangkan Selesai/Validasi/Respons Time dikosongkan agar tidak membawa nilai order sebelumnya.
        $now = date('Y-m-d H:i:s');
        $sk = $this->db('detail_cetak_lab')->where('no_rawat', $no_rawat)->oneArray();
        $sk_update = [
          'no_lab' => $noorder,
          'tgl_registrasi' => $now,
          'tgl_periksa' => $now,
          'tgl_selesai' => '',
          'tgl_validasi' => '',
          'respons_time' => '',
          'petugas_pelaksana' => $petugas_nama
        ];
        if ($sk) {
          $this->db('detail_cetak_lab')->where('no_rawat', $no_rawat)->save($sk_update);
        } else {
          $sk_update['no_rawat'] = $no_rawat;
          $sk_update['saran'] = '';
          $sk_update['kesan'] = '';
          $sk_update['jenis_layanan'] = 'REGULAR';
          $this->db('detail_cetak_lab')->save($sk_update);
        }
      }

      $kd_dokter = $this->getValidDokterPj();

      $dokter_perujuk = $permintaan_lab['dokter_perujuk'] ?? '';
      if (empty($dokter_perujuk) || !$this->db('dokter')->where('kd_dokter', $dokter_perujuk)->oneArray()) {
        $dokter_perujuk = $kd_dokter;
      }

      $raw_status = !empty($_POST['status']) ? $_POST['status'] : ($permintaan_lab['status'] ?? 'Ralan');
      $status = ucfirst(strtolower($raw_status));
      if (!in_array($status, ['Ralan', 'Ranap'])) {
        $status = 'Ralan';
      }

      $pasien = $this->db('pasien')
        ->join('reg_periksa', 'reg_periksa.no_rkm_medis=pasien.no_rkm_medis')
        ->where('reg_periksa.no_rawat', $no_rawat)
        ->oneArray();
      $jk = $pasien['jk'] ?? 'L';

      $permintaan_pemeriksaan_lab = $this->db('permintaan_pemeriksaan_lab')->where('noorder', $noorder)->toArray();
      foreach ($permintaan_pemeriksaan_lab as $row) {
        $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
        if (!$jns_perawatan) {
          continue;
        }

        $cek_periksa = $this->db('periksa_lab')
          ->where('no_rawat', $no_rawat)
          ->where('kd_jenis_prw', $row['kd_jenis_prw'])
          ->where('tgl_periksa', $tgl_permintaan)
          ->where('jam', $jam_permintaan)
          ->oneArray();

        if (!$cek_periksa) {
          $this->db('periksa_lab')
            ->save([
              'no_rawat' => $no_rawat,
              'nip' => $nip,
              'kd_jenis_prw' => $row['kd_jenis_prw'],
              'tgl_periksa' => $tgl_permintaan,
              'jam' => $jam_permintaan,
              'dokter_perujuk' => $dokter_perujuk,
              'bagian_rs' => $jns_perawatan['bagian_rs'] ?? 0,
              'bhp' => $jns_perawatan['bhp'] ?? 0,
              'tarif_perujuk' => $jns_perawatan['tarif_perujuk'] ?? 0,
              'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'] ?? 0,
              'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'] ?? 0,
              'kso' => $jns_perawatan['kso'] ?? 0,
              'menejemen' => $jns_perawatan['menejemen'] ?? 0,
              'biaya' => $jns_perawatan['total_byr'] ?? 0,
              'kd_dokter' => $kd_dokter,
              'status' => $status,
              'kategori' => 'PK'
            ]);
        }

        $permintaan_detail_permintaan_lab = $this->db('permintaan_detail_permintaan_lab')
          ->where('noorder', $noorder)
          ->where('kd_jenis_prw', $row['kd_jenis_prw'])
          ->toArray();

        $templates = [];
        if (!empty($permintaan_detail_permintaan_lab)) {
          foreach ($permintaan_detail_permintaan_lab as $det) {
            $tpl = $this->db('template_laboratorium')
              ->where('kd_jenis_prw', $det['kd_jenis_prw'])
              ->where('id_template', $det['id_template'])
              ->oneArray();
            if ($tpl) {
              $templates[] = $tpl;
            }
          }
        } else {
          $templates = $this->db('template_laboratorium')
            ->where('kd_jenis_prw', $row['kd_jenis_prw'])
            ->toArray();
        }

        foreach ($templates as $tpl) {
          $cek_detail = $this->db('detail_periksa_lab')
            ->where('no_rawat', $no_rawat)
            ->where('kd_jenis_prw', $tpl['kd_jenis_prw'])
            ->where('tgl_periksa', $tgl_permintaan)
            ->where('jam', $jam_permintaan)
            ->where('id_template', $tpl['id_template'])
            ->oneArray();

          if (!$cek_detail) {
            $nilai_rujukan = ($jk == 'P')
              ? (!empty($tpl['nilai_rujukan_pd']) ? $tpl['nilai_rujukan_pd'] : $tpl['nilai_rujukan_ld'])
              : (!empty($tpl['nilai_rujukan_ld']) ? $tpl['nilai_rujukan_ld'] : $tpl['nilai_rujukan_pd']);

            $this->db('detail_periksa_lab')
              ->save([
                'no_rawat' => $no_rawat,
                'kd_jenis_prw' => $tpl['kd_jenis_prw'],
                'tgl_periksa' => $tgl_permintaan,
                'jam' => $jam_permintaan,
                'id_template' => $tpl['id_template'],
                'nilai' => '',
                'nilai_rujukan' => $nilai_rujukan ?? '',
                'keterangan' => '',
                'bagian_rs' => $tpl['bagian_rs'] ?? 0,
                'bhp' => $tpl['bhp'] ?? 0,
                'bagian_perujuk' => $tpl['bagian_perujuk'] ?? 0,
                'bagian_dokter' => $tpl['bagian_dokter'] ?? 0,
                'bagian_laborat' => $tpl['bagian_laborat'] ?? 0,
                'kso' => $tpl['kso'] ?? 0,
                'menejemen' => $tpl['menejemen'] ?? 0,
                'biaya_item' => $tpl['biaya_item'] ?? 0
              ]);
          }
        }
      }

      $this->db()->pdo()->commit();

      echo json_encode(['status' => 'success', 'message' => 'Validasi permintaan laboratorium berhasil']);
      exit();
    } catch (\Throwable $e) {
      try {
        if ($this->db()->pdo()->inTransaction()) {
          $this->db()->pdo()->rollBack();
        }
      } catch (\Throwable $t) {
        // abaikan
      }
      error_log('Laboratorium validasi permintaan error: ' . $e->getMessage());
      echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server saat memvalidasi permintaan laboratorium.']);
      exit();
    }
  }

  public function postHapusDetailPermintaan()
  {
    $this->db('permintaan_detail_permintaan_lab')
      ->where('noorder', $_POST['noorder'])
      ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
      ->where('id_template', $_POST['id_template'])
      ->delete();
    exit();
  }

  public function anyLayananLab()
  {
    $layanan = $this->db('jns_perawatan_lab')
      ->where('status', '1')
      ->like('nm_perawatan', '%' . htmlspecialchars($_POST['layanan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '%')
      ->limit(10)
      ->toArray();
    echo $this->draw('layanan.html', ['layanan' => htmlspecialchars_array($layanan)]);
    exit();
  }

  public function postProviderList()
  {

    if (isset($_POST["query"])) {
      $output = '';
      $key = "%" . $_POST["query"] . "%";
      $rows = $this->db('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
      $output = '';
      if (count($rows)) {
        foreach ($rows as $row) {
          $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["kd_dokter"] . ': ' . $row["nm_dokter"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
      }
      echo $output;
    }

    exit();

  }

  public function postProviderList2()
  {

    if (isset($_POST["query"])) {
      $output = '';
      $key = "%" . $_POST["query"] . "%";
      $rows = $this->db('petugas')->like('nama', $key)->limit(10)->toArray();
      $output = '';
      if (count($rows)) {
        foreach ($rows as $row) {
          $output .= '<li class="list-group-item link-class">' . htmlspecialchars($row["nip"] . ': ' . $row["nama"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
      }
      echo $output;
    }

    exit();

  }

  public function postMaxid()
  {
    $urut = $this->db('reg_periksa')
      ->where('tgl_registrasi', date('Y-m-d'))
      ->nextRightNumber('no_rawat', 6);

    $next_no_rawat = date('Y/m/d') . '/' . sprintf('%06d', $urut);

    echo $next_no_rawat;

    exit();
  }

  public function postMaxAntrian()
  {
    $urut = $this->db('reg_periksa')
      ->where('kd_poli', $this->settings('settings', 'laboratorium'))
      ->where('tgl_registrasi', date('Y-m-d'))
      ->nextRightNumber('no_reg', 3);

    echo sprintf('%03d', $urut);

    exit();
  }

  public function convertNorawat($text)
  {
    setlocale(LC_ALL, 'en_EN');
    $text = str_replace('/', '', trim($text));
    return $text;
  }

  public function postKirimEmail()
  {
    $email = $_POST['email'];
    $nama_lengkap = $_POST['receiver'];
    $file = $_POST['file'];
    $this->sendEmail($email, $nama_lengkap, $file);
    exit();
  }

  private function sendEmail($email, $receiver, $file)
  {
    $binary_content = file_get_contents($file);

    if ($binary_content === false) {
      throw new \Exception("Could not fetch remote content from: '$file'");
    }

    $mail = new PHPMailer(true);
    $temp = @file_get_contents(MODULES . "/laboratorium/email/email.send.html");

    $temp = str_replace("{SITENAME}", $this->core->settings->get('settings.nama_instansi'), $temp);
    $temp = str_replace("{ADDRESS}", $this->core->settings->get('settings.alamat') . " - " . $this->core->settings->get('settings.kota'), $temp);
    $temp = str_replace("{TELP}", $this->core->settings->get('settings.nomor_telepon'), $temp);
    //$temp  = str_replace("{NUMBER}", $number, $temp);

    //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // for detailed debug output
    $mail->isSMTP();
    $mail->Host = $this->settings->get('api.apam_smtp_host');
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $this->settings->get('api.apam_smtp_port');

    $mail->Username = $this->settings->get('api.apam_smtp_username');
    $mail->Password = $this->settings->get('api.apam_smtp_password');

    // Sender and recipient settings
    $mail->setFrom($this->core->settings->get('settings.email'), $this->core->settings->get('settings.nama_instansi'));
    $mail->addAddress($email, $receiver);
    $mail->AddStringAttachment($binary_content, "hasil_laboratorium.pdf", $encoding = 'base64', $type = 'application/pdf');

    // Setting the email content
    $mail->IsHTML(true);
    $mail->Subject = "Hasil pemeriksaan laboratorium anda di " . $this->core->settings->get('settings.nama_instansi');
    $mail->Body = $temp;

    $mail->send();

    if (!$mail->send()) {
      echo 'Error: ' . $mail->ErrorInfo;
    } else {
      echo 'Pesan email telah dikirim.';
    }

  }

  public function postCekWaktu()
  {
    echo date('H:i:s');
    exit();
  }

  public function getJavascript()
  {
    header('Content-type: text/javascript');
    $this->assign['websocket'] = $this->settings->get('settings.websocket');
    $this->assign['websocket_proxy'] = $this->settings->get('settings.websocket_proxy');
    echo $this->draw(MODULES . '/laboratorium/js/admin/laboratorium.js', ['mlite' => htmlspecialchars_array($this->assign)]);
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
    $this->core->addJS(url([ADMIN, 'laboratorium', 'javascript']), 'footer');
  }

}

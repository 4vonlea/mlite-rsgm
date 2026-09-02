<?php
namespace Plugins\Apotek_Ralan;

use Systems\AdminModule;

class Admin extends AdminModule
{
    protected array $assign = [];

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
        ];
    }

    public function anyManage()
    {
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa);
        return $this->draw('manage.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim)]);
    }

    public function anyDisplay()
    {
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa);
        echo $this->draw('display.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim)]);
        exit();
    }

    public function _Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa='')
    {
        $this->_addHeaderFiles();

        $this->assign['poliklinik']     = $this->db('poliklinik')->where('status', '1')->toArray();
        $this->assign['dokter']         = $this->db('dokter')->where('status', '1')->toArray();
        $this->assign['penjab']       = $this->db('penjab')->where('status', '1')->toArray();
        $this->assign['no_rawat'] = '';
        $this->assign['no_reg']     = '';
        $this->assign['tgl_registrasi']= date('Y-m-d');
        $this->assign['jam_reg']= date('H:i:s');

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

        if($status_periksa == 'belum') {
          $sql .= " AND reg_periksa.stts = 'Belum'";
        }
        if($status_periksa == 'selesai') {
          $sql .= " AND reg_periksa.stts = 'Sudah'";
        }
        if($status_periksa == 'lunas') {
          $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar'";
        }

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $no_rawats = [];
        foreach ($rows as $row) {
          $no_rawats[] = $row['no_rawat'];
        }

        // Status apotek per no_rawat:
        // 3 = Order (ada resep belum divalidasi)
        // 2 = Skrining / Sudah Validasi (belum diserahkan)
        // 1 = Selesai (sudah serah terima)
        // 0 = Belum ada resep apotek
        $apotek_status = [];
        if (!empty($no_rawats)) {
          $placeholders = implode(',', array_fill(0, count($no_rawats), '?'));
          $sql_status = "SELECT no_rawat,
              SUM(CASE WHEN tgl_perawatan IS NULL OR YEAR(tgl_perawatan) < 1970 THEN 1 ELSE 0 END) AS jml_order,
              SUM(CASE WHEN NOT (tgl_perawatan IS NULL OR YEAR(tgl_perawatan) < 1970)
                       AND (tgl_penyerahan IS NULL OR YEAR(tgl_penyerahan) < 1970) THEN 1 ELSE 0 END) AS jml_validasi,
              SUM(CASE WHEN NOT (tgl_penyerahan IS NULL OR YEAR(tgl_penyerahan) < 1970) THEN 1 ELSE 0 END) AS jml_selesai
            FROM resep_obat
            WHERE status = 'ralan' AND no_rawat IN ($placeholders)
            GROUP BY no_rawat";
          $stmt = $this->db()->pdo()->prepare($sql_status);
          $stmt->execute($no_rawats);
          foreach ($stmt->fetchAll() as $sr) {
            $no_rawat = $sr['no_rawat'];
            if ($sr['jml_order'] > 0) {
              $apotek_status[$no_rawat] = ['prioritas' => 3, 'status' => 'Order', 'warna' => 'danger'];
            } elseif ($sr['jml_validasi'] > 0) {
              $apotek_status[$no_rawat] = ['prioritas' => 2, 'status' => 'Skrining', 'warna' => 'warning'];
            } elseif ($sr['jml_selesai'] > 0) {
              $apotek_status[$no_rawat] = ['prioritas' => 1, 'status' => 'Selesai', 'warna' => 'success'];
            }
          }
        }

        $this->assign['list'] = [];
        foreach ($rows as $row) {
          $st = $apotek_status[$row['no_rawat']] ?? null;
          $row['orderan'] = $st ? $st['prioritas'] : 0;
          $row['apotek_prioritas'] = $row['orderan'];
          $row['apotek_status'] = $st ? $st['status'] : 'Belum';
          $row['apotek_warna'] = $st ? $st['warna'] : 'default';
          $this->assign['list'][] = $row;
        }
        usort($this->assign['list'], function ($a, $b) {
          if ($a['apotek_prioritas'] != $b['apotek_prioritas']) {
            return $b['apotek_prioritas'] - $a['apotek_prioritas'];
          }
          return strcmp(isset($b['tgl_registrasi']) ? $b['tgl_registrasi'] : '', isset($a['tgl_registrasi']) ? $a['tgl_registrasi'] : '');
        });

    }

    public function postSaveDetail()
    {

      if($_POST['kat'] == 'obat') {
        $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kd_jenis_prw'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
        $get_databarang = $this->db('databarang')->where('kode_brng', $_POST['kd_jenis_prw'])->oneArray();

        $this->db('gudangbarang')
          ->where('kode_brng', $_POST['kd_jenis_prw'])
          ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
          ->update([
            'stok' => $get_gudangbarang['stok'] - $_POST['jml']
          ]);

        $this->db('riwayat_barang_medis')
          ->save([
            'kode_brng' => $_POST['kd_jenis_prw'],
            'stok_awal' => $get_gudangbarang['stok'],
            'masuk' => '0',
            'keluar' => $_POST['jml'],
            'stok_akhir' => $get_gudangbarang['stok'] - $_POST['jml'],
            'posisi' => 'Pemberian Obat',
            'tanggal' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'petugas' => $this->core->getUserInfo('fullname', null, true),
            'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => substr($_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat'])), 0, 100)
            ]);

        $this->db('detail_pemberian_obat')
          ->save([
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'no_rawat' => $_POST['no_rawat'],
            'kode_brng' => $_POST['kd_jenis_prw'],
            'h_beli' => $_POST['biaya'],
            'biaya_obat' => $_POST['biaya'],
            'jml' => $_POST['jml'],
            'embalase' => ($_POST['embalase'] !== '' ? $_POST['embalase'] : 0),
            'tuslah' => ($_POST['tuslah'] !== '' ? $_POST['tuslah'] : 0),
            'total' => $_POST['biaya'] * $_POST['jml'],
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : ''
            ]);

          $this->db('aturan_pakai')
          ->save([
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'no_rawat' => $_POST['no_rawat'],
            'kode_brng' => $_POST['kd_jenis_prw'],
            'aturan' => $_POST['aturan_pakai']
          ]);
      }
      if($_POST['kat'] == 'racikan') {
        $no_racik = $this->db('obat_racikan')->where('no_rawat', $_POST['no_rawat'])->where('tgl_perawatan', $_POST['tgl_perawatan'])->count();
        $no_racik = $no_racik+1;
        $this->db('obat_racikan')
          ->save([
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'no_rawat' => $_POST['no_rawat'],
            'no_racik' => $no_racik,
            'nama_racik' => $_POST['nama_racik'],
            'kd_racik' => $_POST['kd_jenis_prw'],
            'jml_dr' => $_POST['jml'],
            'aturan_pakai' => $_POST['aturan_pakai'],
            'keterangan' => $_POST['keterangan']
          ]);
        $_POST['kode_brng'] = json_decode($_POST['kode_brng'], true);
        $_POST['kandungan'] = json_decode($_POST['kandungan'], true);
        for ($i = 0; $i < count($_POST['kode_brng']); $i++) {
          $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'][$i]['value'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
          $kapasitas = $this->db('databarang')->where('kode_brng', $_POST['kode_brng'][$i]['value'])->oneArray();
          $jml = $_POST['jml']*$_POST['kandungan'][$i]['value'];
          $jml = round(($jml/$kapasitas['kapasitas']),1);

          $this->db('gudangbarang')
          ->where('kode_brng', $_POST['kode_brng'][$i]['value'])
          ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
          ->update([
            'stok' => $get_gudangbarang['stok'] - $jml
          ]);

          $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $_POST['kode_brng'][$i]['value'],
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $jml,
              'stok_akhir' => $get_gudangbarang['stok'] - $jml,
              'posisi' => 'Pemberian Obat',
              'tanggal' => $_POST['tgl_perawatan'],
              'jam' => $_POST['jam_rawat'],
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
            ]);

          $this->db('detail_pemberian_obat')
            ->save([
              'tgl_perawatan' => $_POST['tgl_perawatan'],
              'jam' => $_POST['jam_rawat'],
              'no_rawat' => $_POST['no_rawat'],
              'kode_brng' => $_POST['kode_brng'][$i]['value'],
              'h_beli' => $kapasitas['h_beli'],
              'biaya_obat' => $kapasitas['dasar'],
              'jml' => $jml,
              'embalase' => ($_POST['embalase'] !== '' ? $_POST['embalase'] : 0),
              'tuslah' => ($_POST['tuslah'] !== '' ? $_POST['tuslah'] : 0),
              'total' => $kapasitas['dasar'] * $jml,
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => $get_gudangbarang['no_faktur']
            ]);

          $this->db('detail_obat_racikan')
            ->save([
              'tgl_perawatan' => $_POST['tgl_perawatan'],
              'jam' => $_POST['jam_rawat'],
              'no_rawat' => $_POST['no_rawat'],
              'no_racik' => $no_racik,
              'kode_brng' => $_POST['kode_brng'][$i]['value']
            ]);          

        }        
      }
      exit();
    }

    public function postValidasiResep()
    {
      try {
      $this->db()->pdo()->exec("SET SESSION innodb_lock_wait_timeout = 20");
      $tgl_rawat = date('Y-m-d');
      $jam_rawat = date('H:i:s');
      $petugas_login = $this->core->getUserInfo('fullname', null, true);
      $catatan_skrining = isset($_POST['catatan_skrining']) ? strip_tags($_POST['catatan_skrining']) : '';
      $nama_penerima = isset($_POST['nama_penerima']) ? strip_tags($_POST['nama_penerima']) : '';
      $hubungan_penerima = isset($_POST['hubungan_penerima']) ? strip_tags($_POST['hubungan_penerima']) : '';

      if($_POST['penyerahan'] == 'penyerahan') {
        $catatan_penyerahan = isset($_POST['catatan_penyerahan']) ? strip_tags($_POST['catatan_penyerahan']) : '';
        $existing_resep = $this->db('resep_obat')->where('no_resep', $_POST['no_resep'])->oneArray();
        $catatan_sebelum = isset($existing_resep['catatan_skrining']) ? trim($existing_resep['catatan_skrining']) : '';
        $combined_catatan = $catatan_sebelum;
        if ($catatan_penyerahan !== '') {
          $combined_catatan = $catatan_sebelum !== '' ? $catatan_sebelum . ' || Verifikasi: ' . $catatan_penyerahan : 'Verifikasi: ' . $catatan_penyerahan;
        }
        $this->db('resep_obat')->where('no_resep', $_POST['no_resep'])->save([
          'tgl_penyerahan' => $tgl_rawat,
          'jam_penyerahan' => $jam_rawat,
          'petugas_penyerahan' => $petugas_login,
          'catatan_skrining' => $combined_catatan
        ]);
      } else {
        $get_resep_dokter_nonracikan = $this->db('resep_dokter')
          ->select([
              'kode_brng' => 'kode_brng',
              'jml' => 'jml',
              'aturan_pakai' => 'aturan_pakai'
            ])
          ->where('no_resep', $_POST['no_resep'])
          ->toArray();
        $get_resep_dokter_racikan = $this->db('resep_dokter_racikan')
          ->select([
              'no_racik' => 'resep_dokter_racikan.no_racik',
              'nama_racik' => 'resep_dokter_racikan.nama_racik',
              'kd_racik' => 'resep_dokter_racikan.kd_racik',
              'jml_dr' => 'resep_dokter_racikan.jml_dr',
              'keterangan' => 'resep_dokter_racikan.keterangan',
              'kode_brng' => 'kode_brng',
              'jml' => 'jml',
              'aturan_pakai' => 'aturan_pakai'
            ])
          ->join('resep_dokter_racikan_detail', 'resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep AND resep_dokter_racikan.no_racik=resep_dokter_racikan_detail.no_racik')
          ->where('resep_dokter_racikan.no_resep', $_POST['no_resep'])
          ->toArray();
        $get_resep_dokter = array_merge($get_resep_dokter_nonracikan, $get_resep_dokter_racikan);

        $embalaseData = isset($_POST['embalase']) ? json_decode($_POST['embalase'], true) : [];
        $tuslahData = isset($_POST['tuslah']) ? json_decode($_POST['tuslah'], true) : [];
        $jumlahData = isset($_POST['jumlah']) ? json_decode($_POST['jumlah'], true) : [];
        $kandunganData = isset($_POST['kandungan']) ? json_decode($_POST['kandungan'], true) : [];
        $aturanPakaiData = isset($_POST['aturan_pakai']) ? json_decode($_POST['aturan_pakai'], true) : [];

        if(!empty($get_resep_dokter_racikan)) {
            // Group by no_racik to avoid duplicate inserts
            $racikan_unique = [];
            foreach ($get_resep_dokter_racikan as $row) {
                if (!isset($racikan_unique[$row['no_racik']])) {
                    $racikan_unique[$row['no_racik']] = $row;
                }
            }
            
            foreach ($racikan_unique as $racikan) {
                // Ambil aturan pakai dari input jika ada (prioritas), jika tidak gunakan dari database
                $aturan_pakai_racikan = isset($aturanPakaiData[$racikan['kd_racik']]) ? $aturanPakaiData[$racikan['kd_racik']] : $racikan['aturan_pakai'];
                
                $this->db('obat_racikan')->save(
                    [
                        'tgl_perawatan' => $tgl_rawat,
                        'jam' => $jam_rawat,
                        'no_rawat' => $_POST['no_rawat'],
                        'no_racik' => htmlspecialchars_array($racikan)['no_racik'],
                        'nama_racik' => htmlspecialchars_array($racikan)['nama_racik'],
                        'kd_racik' => htmlspecialchars_array($racikan)['kd_racik'],
                        'jml_dr' => htmlspecialchars_array($racikan)['jml_dr'],
                        'aturan_pakai' => $aturan_pakai_racikan,
                        'keterangan' => htmlspecialchars_array($racikan)['keterangan']
                    ]
                );
            }
        }


        foreach ($get_resep_dokter as $item) {

          $jumlah = isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml'];
          $kandungan = isset($kandunganData[$item['kode_brng']]) ? $kandunganData[$item['kode_brng']] : (isset($item['kandungan']) ? $item['kandungan'] : 0);
          
          if (isset($aturanPakaiData[$item['kode_brng']])) {
              $aturan_pakai = $aturanPakaiData[$item['kode_brng']];
          } else {
              $aturan_pakai = isset($item['aturan_pakai']) ? $item['aturan_pakai'] : '';
          }

          if(isset($item['no_racik'])) {
             $jumlah_racik = isset($item['jml_dr']) ? $item['jml_dr'] : (isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml']);
             $jumlah = isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml'];             
          }

          $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $item['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
          $get_databarang = $this->db('databarang')->where('kode_brng', $item['kode_brng'])->oneArray();

          $this->db('gudangbarang')
            ->where('kode_brng', $item['kode_brng'])
            ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->update([
              'stok' => $get_gudangbarang['stok'] - $jumlah
            ]);

          if(isset($item['no_racik'])) {
            $this->db('resep_dokter_racikan')
              ->where('no_resep', $_POST['no_resep'])
              ->where('no_racik', $item['no_racik'])
              ->update([
                'jml_dr' => $jumlah_racik
              ]);
            $this->db('resep_dokter_racikan_detail')
              ->where('no_resep', $_POST['no_resep'])
              ->where('no_racik', $item['no_racik'])
              ->where('kode_brng', $item['kode_brng'])
              ->update([
                'jml' => $jumlah,
                'kandungan' => $kandungan
              ]);
          } else {
            $this->db('resep_dokter')
              ->where('no_resep', $_POST['no_resep'])
              ->where('kode_brng', $item['kode_brng'])
              ->update([
                'jml' => $jumlah
              ]);
          }

          $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $item['kode_brng'],
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $jumlah,
              'stok_akhir' => $get_gudangbarang['stok'] - $jumlah,
              'posisi' => 'Pemberian Obat',
              'tanggal' => $tgl_rawat,
              'jam' => $jam_rawat,
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
            ]);

          $embalase = (isset($embalaseData[$item['kode_brng']]) && $embalaseData[$item['kode_brng']] !== '') ? $embalaseData[$item['kode_brng']] : $this->settings->get('farmasi.embalase');
          $tuslah = (isset($tuslahData[$item['kode_brng']]) && $tuslahData[$item['kode_brng']] !== '') ? $tuslahData[$item['kode_brng']] : $this->settings->get('farmasi.tuslah');

          $this->db('detail_pemberian_obat')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $_POST['no_rawat'],
              'kode_brng' => $item['kode_brng'],
              'h_beli' => $get_databarang['h_beli'],
              'biaya_obat' => $get_databarang['dasar'],
              'jml' => $jumlah,
              'embalase' => $embalase,
              'tuslah' => $tuslah,
              'total' => ($get_databarang['dasar'] * $jumlah) + $embalase + $tuslah,
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => $get_gudangbarang['no_faktur']
            ]);

          $this->db('aturan_pakai')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $_POST['no_rawat'],
              'kode_brng' => $item['kode_brng'],
              'aturan' => $aturan_pakai
            ]);

          if(isset($item['no_racik'])) {
            $this->db('detail_obat_racikan')
              ->save([
                'tgl_perawatan' => $tgl_rawat,
                'jam' => $jam_rawat,
                'no_rawat' => $_POST['no_rawat'],
                'no_racik' => $item['no_racik'],
                'kode_brng' => $item['kode_brng']
              ]);
          }

        }

        $this->db('resep_obat')->where('no_resep', $_POST['no_resep'])->save([
          'tgl_perawatan' => $tgl_rawat,
          'jam' => $jam_rawat,
          'petugas_validasi' => $petugas_login,
          'catatan_skrining' => $catatan_skrining,
        ]);
      }
      exit();
      } catch (\Throwable $e) {
        $logs = date('Y-m-d H:i:s') . ' | no_resep=' . (isset($_POST['no_resep']) ? $_POST['no_resep'] : '-')
          . ' | no_rawat=' . (isset($_POST['no_rawat']) ? $_POST['no_rawat'] : '-')
          . ' | ' . $e->getMessage() . PHP_EOL;
        @file_put_contents(__DIR__ . '/../../tmp/validasi_debug.log', $logs, FILE_APPEND);
        http_response_code(500);
        try { echo json_encode(['error' => $e->getMessage()]); } catch (\Throwable $ignored) {}
        exit();
      }
    }

    public function postValidasiSemuaResep()
    {
      $no_rawat_real = revertNorawat($_POST['no_rawat']);
      $unvalidated = $this->db('resep_obat')
        ->where('no_rawat', $no_rawat_real)
        ->where('tgl_perawatan', '0000-00-00')
        ->toArray();

      $tgl_rawat = date('Y-m-d');
      $jam_rawat = date('H:i:s');
      $petugas_login = $this->core->getUserInfo('fullname', null, true);

      foreach ($unvalidated as $resep) {
        $no_resep = $resep['no_resep'];

        $get_resep_dokter_nonracikan = $this->db('resep_dokter')
          ->select([
              'kode_brng' => 'kode_brng',
              'jml' => 'jml',
              'aturan_pakai' => 'aturan_pakai'
            ])
          ->where('no_resep', $no_resep)
          ->toArray();
          
        $get_resep_dokter_racikan = $this->db('resep_dokter_racikan')
          ->select([
              'no_racik' => 'resep_dokter_racikan.no_racik',
              'nama_racik' => 'resep_dokter_racikan.nama_racik',
              'kd_racik' => 'resep_dokter_racikan.kd_racik',
              'jml_dr' => 'resep_dokter_racikan.jml_dr',
              'keterangan' => 'resep_dokter_racikan.keterangan',
              'kode_brng' => 'kode_brng',
              'jml' => 'jml',
              'aturan_pakai' => 'aturan_pakai'
            ])
          ->join('resep_dokter_racikan_detail', 'resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep AND resep_dokter_racikan.no_racik=resep_dokter_racikan_detail.no_racik')
          ->where('resep_dokter_racikan.no_resep', $no_resep)
          ->toArray();
          
        $get_resep_dokter = array_merge($get_resep_dokter_nonracikan, $get_resep_dokter_racikan);

        if(!empty($get_resep_dokter_racikan)) {
            $racikan_unique = [];
            foreach ($get_resep_dokter_racikan as $row) {
                if (!isset($racikan_unique[$row['no_racik']])) {
                    $racikan_unique[$row['no_racik']] = $row;
                }
            }
            
            foreach ($racikan_unique as $racikan) {
                $this->db('obat_racikan')->save(
                    [
                        'tgl_perawatan' => $tgl_rawat,
                        'jam' => $jam_rawat,
                        'no_rawat' => $no_rawat_real,
                        'no_racik' => htmlspecialchars_array($racikan)['no_racik'],
                        'nama_racik' => htmlspecialchars_array($racikan)['nama_racik'],
                        'kd_racik' => htmlspecialchars_array($racikan)['kd_racik'],
                        'jml_dr' => htmlspecialchars_array($racikan)['jml_dr'],
                        'aturan_pakai' => htmlspecialchars_array($racikan)['aturan_pakai'],
                        'keterangan' => htmlspecialchars_array($racikan)['keterangan']
                    ]
                );
            }
        }

        foreach ($get_resep_dokter as $item) {
          $jumlah = $item['jml'];
          $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $item['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
          $get_databarang = $this->db('databarang')->where('kode_brng', $item['kode_brng'])->oneArray();

          $stok_baru = $get_gudangbarang['stok'] - $jumlah;
          if ($stok_baru < 0) $stok_baru = 0;

          $this->db('gudangbarang')
            ->where('kode_brng', $item['kode_brng'])
            ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->update([
              'stok' => $stok_baru
            ]);

          $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $item['kode_brng'],
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $jumlah,
              'stok_akhir' => $stok_baru,
              'posisi' => 'Pemberian Obat',
              'tanggal' => $tgl_rawat,
              'jam' => $jam_rawat,
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => $no_rawat_real . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat_real) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat_real))
            ]);

          $embalase = $this->settings->get('farmasi.embalase');
          $tuslah = $this->settings->get('farmasi.tuslah');

          $this->db('detail_pemberian_obat')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $no_rawat_real,
              'kode_brng' => $item['kode_brng'],
              'h_beli' => $get_databarang['h_beli'],
              'biaya_obat' => $get_databarang['dasar'],
              'jml' => $jumlah,
              'embalase' => $embalase,
              'tuslah' => $tuslah,
              'total' => ($get_databarang['dasar'] * $jumlah) + $embalase + $tuslah,
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => $get_gudangbarang['no_faktur']
            ]);

          $this->db('aturan_pakai')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $no_rawat_real,
              'kode_brng' => $item['kode_brng'],
              'aturan' => $item['aturan_pakai']
            ]);

          if(isset($item['no_racik'])) {
            $this->db('detail_obat_racikan')
              ->save([
                'tgl_perawatan' => $tgl_rawat,
                'jam' => $jam_rawat,
                'no_rawat' => $no_rawat_real,
                'no_racik' => $item['no_racik'],
                'kode_brng' => $item['kode_brng']
              ]);
          }
        }

        $this->db('resep_obat')->where('no_resep', $no_resep)->save([
          'tgl_perawatan' => $tgl_rawat,
          'jam' => $jam_rawat,
          'petugas_validasi' => $petugas_login,
          'catatan_skrining' => 'Validasi Massal',
        ]);
      }
      
      echo "success";
      exit();
    }

    public function postSerahkanSemuaObat()
    {
      $no_rawat_real = revertNorawat($_POST['no_rawat']);
      $nama_penerima = isset($_POST['nama_penerima']) ? strip_tags($_POST['nama_penerima']) : '';
      $hubungan_penerima = isset($_POST['hubungan_penerima']) ? strip_tags($_POST['hubungan_penerima']) : '';
      
      $tgl_rawat = date('Y-m-d');
      $jam_rawat = date('H:i:s');
      $petugas_login = $this->core->getUserInfo('fullname', null, true);
      
      $validated_undelivered = $this->db('resep_obat')
        ->where('no_rawat', $no_rawat_real)
        ->where('tgl_perawatan', '!=', '0000-00-00')
        ->where('tgl_penyerahan', '0000-00-00')
        ->toArray();

      foreach ($validated_undelivered as $resep) {
        $no_resep = $resep['no_resep'];
        $catatan_serah = $nama_penerima ? 'Diterima oleh: '.$nama_penerima.' ('.$hubungan_penerima.')' : '';
        if (!empty($resep['catatan_skrining'])) {
          $catatan_serah .= ' | ' . $resep['catatan_skrining'];
        }
        
        $this->db('resep_obat')->where('no_resep', $no_resep)->save([
          'tgl_penyerahan' => $tgl_rawat,
          'jam_penyerahan' => $jam_rawat,
          'petugas_penyerahan' => $petugas_login,
          'catatan_skrining' => $catatan_serah
        ]);
      }

      echo "success";
      exit();
    }

    public function postTambahItemResep()
    {
      $no_resep = $_POST['no_resep'];
      $no_rawat = revertNorawat($_POST['no_rawat']);
      $tgl_peresepan = $_POST['tgl_peresepan'];
      $jam_peresepan = $_POST['jam_peresepan'];
      $kode_brng = $_POST['kode_brng'];
      $tipe = isset($_POST['tipe']) ? $_POST['tipe'] : 'nonracikan';
      
      $tgl_rawat = date('Y-m-d');
      $jam_rawat = date('H:i:s');
      
      $embalase = (isset($_POST['embalase']) && $_POST['embalase'] !== '' && $_POST['embalase'] !== null) ? $_POST['embalase'] : $this->settings->get('farmasi.embalase');
      $tuslah = (isset($_POST['tuslah']) && $_POST['tuslah'] !== '' && $_POST['tuslah'] !== null) ? $_POST['tuslah'] : $this->settings->get('farmasi.tuslah');
      
      $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $kode_brng)->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
      $get_databarang = $this->db('databarang')->where('kode_brng', $kode_brng)->oneArray();

      if ($tipe == 'racikan') {
          $no_racik = $_POST['no_racik'];
          $kandungan = $_POST['kandungan'];
          $jml_dr = $_POST['jml_dr']; // Jumlah racikan (bungkus)
          $kapasitas = $get_databarang['kapasitas'] > 0 ? $get_databarang['kapasitas'] : 1;
          
          // Hitung jumlah obat
          $jml = round(($jml_dr * $kandungan) / $kapasitas, 1);
          
          // Kurangi stok
          $this->db('gudangbarang')
            ->where('kode_brng', $kode_brng)
            ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->update([
              'stok' => $get_gudangbarang['stok'] - $jml
            ]);

          // Riwayat
          $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $kode_brng,
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $jml,
              'stok_akhir' => $get_gudangbarang['stok'] - $jml,
              'posisi' => 'Pemberian Obat',
              'tanggal' => $tgl_rawat,
              'jam' => $jam_rawat,
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
            ]);

          // Simpan ke detail racikan
          // $this->db('resep_dokter_racikan_detail')
          //   ->save([
          //       'no_resep' => $no_resep,
          //       'no_racik' => $no_racik,
          //       'kode_brng' => $kode_brng,
          //       'p1' => 1, // Default
          //       'p2' => 1, // Default
          //       'kandungan' => $kandungan,
          //       'jml' => $jml
          //   ]);

          // Simpan ke detail pemberian obat (billing)
          $this->db('detail_pemberian_obat')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $no_rawat,
              'kode_brng' => $kode_brng,
              'h_beli' => $get_databarang['h_beli'],
              'biaya_obat' => $get_databarang['dasar'],
              'jml' => $jml,
              'embalase' => $embalase,
              'tuslah' => $tuslah,
              'total' => ($get_databarang['dasar'] * $jml) + $embalase + $tuslah,
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => $get_gudangbarang['no_faktur']
            ]);

          $this->db('detail_obat_racikan')
            ->save([
              'tgl_perawatan' => $tgl_rawat,
              'jam' => $jam_rawat,
              'no_rawat' => $no_rawat,
              'no_racik' => $no_racik,
              'kode_brng' => $kode_brng
            ]);
            
          header('Content-Type: application/json');
          echo json_encode([
            'kode_brng' => htmlspecialchars($kode_brng, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'nama_brng' => htmlspecialchars($get_databarang['nama_brng'] ?? 'Nama Obat Tidak Ditemukan', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'jml' => htmlspecialchars($jml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'kandungan' => htmlspecialchars($kandungan, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'kapasitas' => htmlspecialchars($kapasitas, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'ralan' => isset($get_databarang['dasar']) ? $get_databarang['dasar'] : 0,
            'embalase' => htmlspecialchars($embalase, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'tuslah' => htmlspecialchars($tuslah, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
          ]);
          exit();

      } else {
          // Logika Non-Racikan (Existing)
          $jml = $_POST['jml'];
          $aturan_pakai = $_POST['aturan_pakai'];
          
          $this->db('gudangbarang')
            ->where('kode_brng', $kode_brng)
            ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->update([
              'stok' => $get_gudangbarang['stok'] - $jml
            ]);

          $this->db('riwayat_barang_medis')
            ->save([
              'kode_brng' => $kode_brng,
              'stok_awal' => $get_gudangbarang['stok'],
              'masuk' => '0',
              'keluar' => $jml,
              'stok_akhir' => $get_gudangbarang['stok'] - $jml,
              'posisi' => 'Pemberian Obat',
              'tanggal' => $tgl_rawat,
              'jam' => $jam_rawat,
              'petugas' => $this->core->getUserInfo('fullname', null, true),
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'status' => 'Simpan',
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
              'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
            ]);

          $this->db('detail_pemberian_obat')
            ->save([
              'tgl_perawatan' => $tgl_peresepan,
              'jam' => $jam_peresepan,
              'no_rawat' => $no_rawat,
              'kode_brng' => $kode_brng,
              'h_beli' => $get_databarang['h_beli'],
              'biaya_obat' => $get_databarang['dasar'],
              'jml' => $jml,
              'embalase' => $embalase,
              'tuslah' => $tuslah,
              'total' => ($get_databarang['dasar'] * $jml) + $embalase + $tuslah,
              'status' => 'Ralan',
              'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
              'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
              'no_faktur' => $get_gudangbarang['no_faktur']
            ]);

          $this->db('aturan_pakai')
            ->save([
              'tgl_perawatan' => $tgl_peresepan,
              'jam' => $jam_peresepan,
              'no_rawat' => $no_rawat,
              'kode_brng' => $kode_brng,
              'aturan' => $aturan_pakai
            ]);

          header('Content-Type: application/json');
          echo json_encode([
            'kode_brng' => htmlspecialchars($kode_brng, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'nama_brng' => htmlspecialchars($get_databarang['nama_brng'] ?? 'Nama Obat Tidak Ditemukan', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'jml' => htmlspecialchars($jml, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'aturan_pakai' => htmlspecialchars($aturan_pakai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'ralan' => isset($get_databarang['dasar']) ? (($get_databarang['dasar'] * $jml) + $embalase + $tuslah) : 0,
            'embalase' => htmlspecialchars($embalase, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'tuslah' => htmlspecialchars($tuslah, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
          ]);
          exit();
      }
    }

    public function postHapusResep()
    {
      if(isset($_POST['kd_jenis_prw'])) {
        $this->db('resep_dokter')
        ->where('no_resep', $_POST['no_resep'])
        ->where('kode_brng', $_POST['kd_jenis_prw'])
        ->delete();
      } else {
        $this->db('resep_obat')
        ->where('no_resep', $_POST['no_resep'])
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('tgl_peresepan', $_POST['tgl_peresepan'])
        ->where('jam_peresepan', $_POST['jam_peresepan'])
        ->delete();
      }

      exit();
    }

    public function postHapusObat()
    {

      $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();

      $this->db('gudangbarang')
        ->where('kode_brng', $_POST['kode_brng'])
        ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
        ->update([
          'stok' => $get_gudangbarang['stok'] + $_POST['jml']
        ]);

      $this->db('riwayat_barang_medis')
        ->save([
          'kode_brng' => $_POST['kode_brng'],
          'stok_awal' => $get_gudangbarang['stok'],
          'masuk' => $_POST['jml'],
          'keluar' => '0',
          'stok_akhir' => $get_gudangbarang['stok'] + $_POST['jml'],
          'posisi' => 'Pemberian Obat',
          'tanggal' => $_POST['tgl_perawatan'],
          'jam' => $_POST['jam'],
          'petugas' => $this->core->getUserInfo('fullname', null, true),
          'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
          'status' => 'Hapus',
          'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
          'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
          'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
        ]);

      $this->db('detail_pemberian_obat')
      ->where('kode_brng', $_POST['kode_brng'])
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('tgl_perawatan', $_POST['tgl_perawatan'])
      ->where('jam', $_POST['jam'])
      ->delete();

      exit();
    }    

    public function postHapusObatRacikan()
    {
      $no_rawat = $_POST['no_rawat'];
      $tgl_perawatan = $_POST['tgl_perawatan'];
      $jam = $_POST['jam'];
      $no_racik = $_POST['no_racik'];

      // 1. Get all items belonging to this specific racikan
      $items_in_racikan = $this->db('detail_obat_racikan')
      ->where('no_rawat', $no_rawat)
      ->where('tgl_perawatan', $tgl_perawatan)
      ->where('jam', $jam)
      ->where('no_racik', $no_racik)
      ->toArray();

      foreach($items_in_racikan as $racikan_item) {
        
        $kode_brng = $racikan_item['kode_brng'];

        // 2. Restore Stock (Best Effort)
        $item_billing = $this->db('detail_pemberian_obat')
        ->where('no_rawat', $no_rawat)
        ->where('tgl_perawatan', $tgl_perawatan)
        ->where('jam', $jam)
        ->where('kode_brng', $kode_brng)
        ->oneArray();

        if ($item_billing) {
            $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $kode_brng)->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();

            $this->db('gudangbarang')
            ->where('kode_brng', $kode_brng)
            ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->update([
              'stok' => $get_gudangbarang['stok'] + $item_billing['jml']
            ]);

            $this->db('riwayat_barang_medis')
              ->save([
                'kode_brng' => $kode_brng,
                'stok_awal' => $get_gudangbarang['stok'],
                'masuk' => $item_billing['jml'],
                'keluar' => '0',
                'stok_akhir' => $get_gudangbarang['stok'] + $item_billing['jml'],
                'posisi' => 'Pemberian Obat',
                'tanggal' => $tgl_perawatan,
                'jam' => $jam,
                'petugas' => $this->core->getUserInfo('fullname', null, true),
                'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                'status' => 'Hapus',
                'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
                'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
                'keterangan' => $no_rawat . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat))
              ]);
        }

        // 3. Delete from detail_pemberian_obat (Unconditional delete based on keys)
        $this->db('detail_pemberian_obat')
        ->where('no_rawat', $no_rawat)
        ->where('tgl_perawatan', $tgl_perawatan)
        ->where('jam', $jam)
        ->where('kode_brng', $kode_brng)
        ->delete();

        // 4. Delete from detail_obat_racikan (per item)
        $this->db('detail_obat_racikan')
        ->where('no_rawat', $no_rawat)
        ->where('tgl_perawatan', $tgl_perawatan)
        ->where('jam', $jam)
        ->where('no_racik', $no_racik)
        ->where('kode_brng', $kode_brng)
        ->delete();
      }

      // 5. Finally delete the parent racikan entry
      $this->db('obat_racikan')
        ->where('no_rawat', $no_rawat)
        ->where('tgl_perawatan', $tgl_perawatan)
        ->where('jam', $jam)
        ->where('no_racik', $no_racik)
        ->delete();

      exit();
    }    

    public function anyRincian()
    {
      // Get patient, diagnoses, and medication history for screening
      $reg_periksa = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();

      $pemeriksaan = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
      
      if (!$pemeriksaan) {
          $pemeriksaan = $this->db('pemeriksaan_ranap')
            ->where('no_rawat', $_POST['no_rawat'])
            ->oneArray();
      }

      if (!$pemeriksaan) {
          $pemeriksaan = $this->db('pemeriksaan_ralan')
            ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ralan.no_rawat')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'] ?? '')
            ->desc('pemeriksaan_ralan.tgl_perawatan')
            ->desc('pemeriksaan_ralan.jam_rawat')
            ->oneArray();
      }
      
      if (!$pemeriksaan) {
          $pemeriksaan = $this->db('pemeriksaan_ranap')
            ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ranap.no_rawat')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'] ?? '')
            ->desc('pemeriksaan_ranap.tgl_perawatan')
            ->desc('pemeriksaan_ranap.jam_rawat')
            ->oneArray();
      }

      $pasien = [
          'no_rkm_medis' => $reg_periksa['no_rkm_medis'] ?? '',
          'nm_pasien' => $reg_periksa['nm_pasien'] ?? '',
          'jk' => ($reg_periksa['jk'] ?? '') == 'L' ? 'Laki-Laki' : (($reg_periksa['jk'] ?? '') == 'P' ? 'Perempuan' : '-'),
          'umur' => ($reg_periksa['umurdaftar'] ?? '') . ' ' . ($reg_periksa['sttsumur'] ?? ''),
          'tgl_lahir' => ($reg_periksa['tgl_lahir'] ?? '') != '' && ($reg_periksa['tgl_lahir'] ?? '') != '0000-00-00' ? $reg_periksa['tgl_lahir'] : '-',
          'alamat' => $reg_periksa['alamat'] ?? '',
          'berat' => !empty($pemeriksaan['berat']) ? $pemeriksaan['berat'] : '-',
          'tinggi' => !empty($pemeriksaan['tinggi']) ? $pemeriksaan['tinggi'] : '-',
          'alergi' => ''
      ];

      $alergi_list = [];
      if (isset($reg_periksa['no_rkm_medis'])) {
          $alergi_ralan = $this->db('pemeriksaan_ralan')
            ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ralan.no_rawat')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'])
            ->where('pemeriksaan_ralan.alergi', '!=', '')
            ->where('pemeriksaan_ralan.alergi', '!=', '-')
            ->toArray();
          foreach ($alergi_ralan as $a) {
              $alergi_list[] = $a['alergi'];
          }
          $alergi_ranap = $this->db('pemeriksaan_ranap')
            ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ranap.no_rawat')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'])
            ->where('pemeriksaan_ranap.alergi', '!=', '')
            ->where('pemeriksaan_ranap.alergi', '!=', '-')
            ->toArray();
          foreach ($alergi_ranap as $a) {
              $alergi_list[] = $a['alergi'];
          }
      }
      $alergi_unique = array_unique(array_filter($alergi_list));
      $pasien['alergi'] = !empty($alergi_unique) ? implode(', ', $alergi_unique) : 'Tidak Ada Alergi';

      $diagnosa = [];
      if (isset($reg_periksa['no_rkm_medis'])) {
          $diagnosa = $this->db('diagnosa_pasien')
            ->select('diagnosa_pasien.*')
            ->select('penyakit.nm_penyakit')
            ->select('reg_periksa.tgl_registrasi')
            ->join('penyakit', 'penyakit.kd_penyakit=diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat=diagnosa_pasien.no_rawat')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'])
            ->desc('reg_periksa.tgl_registrasi')
            ->limit(10)
            ->toArray();
      }

      $riwayat_obat = [];
      if (isset($reg_periksa['no_rkm_medis'])) {
          $riwayat_obat = $this->db('detail_pemberian_obat')
            ->select('detail_pemberian_obat.*')
            ->select('databarang.nama_brng')
            ->select('aturan_pakai.aturan AS aturan_pakai')
            ->select('reg_periksa.tgl_registrasi')
            ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
            ->join('reg_periksa', 'reg_periksa.no_rawat=detail_pemberian_obat.no_rawat')
            ->leftJoin('aturan_pakai', 'aturan_pakai.no_rawat=detail_pemberian_obat.no_rawat AND aturan_pakai.kode_brng=detail_pemberian_obat.kode_brng AND aturan_pakai.tgl_perawatan=detail_pemberian_obat.tgl_perawatan AND aturan_pakai.jam=detail_pemberian_obat.jam')
            ->where('reg_periksa.no_rkm_medis', $reg_periksa['no_rkm_medis'])
            ->desc('detail_pemberian_obat.tgl_perawatan')
            ->desc('detail_pemberian_obat.jam')
            ->toArray();
          $riwayat_obat_grouped = [];
          foreach ($riwayat_obat as $r) {
            if (!isset($riwayat_obat_grouped[$r['no_rawat']])) {
              $riwayat_obat_grouped[$r['no_rawat']] = [
                'no_rawat' => $r['no_rawat'],
                'tgl_registrasi' => $r['tgl_registrasi'] ?? '',
                'items' => []
              ];
            }
            $riwayat_obat_grouped[$r['no_rawat']]['items'][] = $r;
          }
          $riwayat_obat = $riwayat_obat_grouped;
      }

      $racikan_nos = $this->db('resep_dokter_racikan')->select('no_resep')->toArray();
      $racikan_nos = array_column($racikan_nos, 'no_resep');

      $rows = $this->db('resep_obat')
        ->select('resep_obat.*')
        ->select('dokter.nm_dokter')
        ->join('dokter', 'dokter.kd_dokter=resep_obat.kd_dokter')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('resep_obat.status', 'ralan')
        ->group('resep_obat.no_resep')
        ->toArray();

      // Filter out racikan from non-racikan list
      $rows = array_filter($rows, function($row) use ($racikan_nos) {
          return !in_array($row['no_resep'], $racikan_nos);
      });

      $resep = [];
      $jumlah_total_resep = 0;
      $embalase_default = $this->settings->get('farmasi.embalase');
      $tuslah_default = $this->settings->get('farmasi.tuslah');
      $detail_embalase_map = [];
      $detail_rows = $this->db('detail_pemberian_obat')
        ->select('kode_brng')
        ->select('embalase')
        ->select('tuslah')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('status', 'Ralan')
        ->toArray();
      foreach ($detail_rows as $dr) {
        $detail_embalase_map[$dr['kode_brng']] = ['embalase' => $dr['embalase'], 'tuslah' => $dr['tuslah']];
      }
      $aturan_pakai_map = [];
      $aturan_rows = $this->db('aturan_pakai')
        ->select('kode_brng')
        ->select('aturan')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
      foreach ($aturan_rows as $ar) {
        if (!isset($aturan_pakai_map[$ar['kode_brng']])) {
          $aturan_pakai_map[$ar['kode_brng']] = $ar['aturan'];
        }
      }
      foreach ($rows as $row) {
        $bangsal = $this->settings->get('farmasi.deporalan');
        $row['resep_dokter'] = $this->db('resep_dokter')
          ->join('databarang', 'databarang.kode_brng=resep_dokter.kode_brng')
          ->leftJoin('gudangbarang', 'gudangbarang.kode_brng=resep_dokter.kode_brng AND gudangbarang.kd_bangsal = "'.$bangsal.'"')
          ->where('no_resep', $row['no_resep'])
          ->toArray();
        foreach ($row['resep_dokter'] as &$value) {
          $saved = isset($detail_embalase_map[$value['kode_brng']]) ? $detail_embalase_map[$value['kode_brng']] : null;
          $value['embalase'] = $saved ? $saved['embalase'] : $embalase_default;
          $value['tuslah'] = $saved ? $saved['tuslah'] : $tuslah_default;
          $value['aturan_pakai'] = (isset($aturan_pakai_map[$value['kode_brng']]) && $aturan_pakai_map[$value['kode_brng']] !== '') ? $aturan_pakai_map[$value['kode_brng']] : (isset($value['aturan_pakai']) ? $value['aturan_pakai'] : '');
          $value['ralan'] = ($value['jml'] * $value['dasar']) + $value['embalase'] + $value['tuslah'];
          $jumlah_total_resep += floatval($value['ralan']);
        }
        unset($value);

        $row['validasi'] = $this->db('resep_obat')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('tgl_perawatan','!=', $row['tgl_peresepan'])
        ->where('jam', '!=', $row['jam_peresepan'])
        ->where('status', 'ralan')
        ->oneArray();

        $resep[] = $row;
      }

      $rows_racikan = $this->db('resep_obat')
        ->select('resep_obat.*')
        ->select('dokter.nm_dokter')
        ->select('resep_dokter_racikan.no_racik')
        ->select('resep_dokter_racikan.nama_racik')
        ->select('resep_dokter_racikan.kd_racik')
        ->select('resep_dokter_racikan.jml_dr')
        ->select('resep_dokter_racikan.aturan_pakai')
        ->select('resep_dokter_racikan.keterangan')
        ->join('dokter', 'dokter.kd_dokter=resep_obat.kd_dokter')
        ->join('resep_dokter_racikan', 'resep_dokter_racikan.no_resep=resep_obat.no_resep')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('resep_obat.status', 'ralan')
        ->group('resep_obat.no_resep')
        ->group('resep_dokter_racikan.no_racik')
        ->toArray();
      $resep_racikan = [];
      $jumlah_total_resep_racikan = 0;
      foreach ($rows_racikan as $row) {
        $bangsal = $this->settings->get('farmasi.deporalan');
        $row['resep_dokter_racikan_detail'] = $this->db('resep_dokter_racikan_detail')
          ->join('databarang', 'databarang.kode_brng=resep_dokter_racikan_detail.kode_brng')
          ->leftJoin('gudangbarang', 'gudangbarang.kode_brng=resep_dokter_racikan_detail.kode_brng AND gudangbarang.kd_bangsal = "'.$bangsal.'"')
          ->where('no_resep', $row['no_resep'])
          ->where('no_racik', $row['no_racik'])
          ->toArray();
        foreach ($row['resep_dokter_racikan_detail'] as &$value) {
          $saved = isset($detail_embalase_map[$value['kode_brng']]) ? $detail_embalase_map[$value['kode_brng']] : null;
          $value['embalase'] = $saved ? $saved['embalase'] : $embalase_default;
          $value['tuslah'] = $saved ? $saved['tuslah'] : $tuslah_default;
          $value['aturan_pakai'] = (isset($row['aturan_pakai']) && $row['aturan_pakai'] !== '') ? $row['aturan_pakai'] : (isset($value['aturan_pakai']) ? $value['aturan_pakai'] : '');
          $value['ralan'] = ($value['jml'] * $value['dasar']) + $value['embalase'] + $value['tuslah'];
          $jumlah_total_resep_racikan += floatval($value['ralan']);
        }
        unset($value);

        $row['validasi'] = $this->db('resep_obat')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('tgl_perawatan','!=', $row['tgl_peresepan'])
        ->where('jam', '!=', $row['jam_peresepan'])
        ->where('status', 'ralan')
        ->oneArray();

        $resep_racikan[] = $row;
      }

      $query = $this->db()->pdo()->prepare("SELECT * FROM detail_pemberian_obat WHERE no_rawat = ? AND status = 'Ralan'");
      $query->execute([$_POST['no_rawat']]);
      $rows_pemberian_obat = $query->fetchAll();

      // Filter out racikan from non-racikan list (detail_pemberian_obat)
      $obat_racikan_items = $this->db('detail_obat_racikan')
          ->select('kode_brng')
          ->where('no_rawat', $_POST['no_rawat'])
          ->toArray();
      $obat_racikan_items = array_column($obat_racikan_items, 'kode_brng');

      // Filter $rows_pemberian_obat agar tidak menampilkan barang yang sudah ada di racikan
      $rows_pemberian_obat = array_filter($rows_pemberian_obat, function($row) use ($obat_racikan_items) {
          return !in_array($row['kode_brng'], $obat_racikan_items);
      });

      $detail_pemberian_obat = [];
      $jumlah_total_obat = 0;
      foreach ($rows_pemberian_obat as $row) {
        $aturan_pakai = $this->db('aturan_pakai')
        ->where('no_rawat', $row['no_rawat'])
        ->where('kode_brng', $row['kode_brng'])
        ->where('tgl_perawatan', $row['tgl_perawatan'])
        ->where('jam', $row['jam'])
        ->oneArray();
        $row['aturan_pakai'] = $aturan_pakai['aturan'] ?? '';
        $data_barang = $this->db('databarang')->where('kode_brng', $row['kode_brng'])->oneArray();
        $row['nama_brng'] = $data_barang['nama_brng'] ?? '';
        $row['ralan'] = $data_barang['ralan'] ?? 0;
        $jumlah_total_obat += floatval($row['total']);
        $detail_pemberian_obat[] = $row;
      }

      $query2 = $this->db()->pdo()->prepare("SELECT obat_racikan.* FROM obat_racikan WHERE obat_racikan.no_rawat = ?");
      $query2->execute([$_POST['no_rawat']]);
      $rows_pemberian_obat2 = $query2->fetchAll();

      $detail_pemberian_obat2 = [];
      $jumlah_total_obat2 = 0;
      foreach ($rows_pemberian_obat2 as $row) {
        $ingredients_map = $this->db('detail_obat_racikan')
            ->where('no_rawat', $row['no_rawat'])
            ->where('tgl_perawatan', $row['tgl_perawatan'])
            ->where('jam', $row['jam'])
            ->where('no_racik', $row['no_racik'])
            ->toArray();

        $row['detail_pemberian_obat'] = [];

        foreach($ingredients_map as $map) {
             $detail = $this->db('detail_pemberian_obat')
                ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
                ->where('detail_pemberian_obat.no_rawat', $map['no_rawat'])
                ->where('detail_pemberian_obat.kode_brng', $map['kode_brng'])
                ->where('detail_pemberian_obat.tgl_perawatan', $map['tgl_perawatan'])
                ->where('detail_pemberian_obat.jam', $map['jam'])
                ->where('detail_pemberian_obat.status', 'Ralan')
                ->oneArray();
             
             if($detail) {
                 $detail['kandungan'] = isset($map['kandungan']) ? $map['kandungan'] : '';
                 $jumlah_total_obat2 += floatval($detail['total']);
                 $row['detail_pemberian_obat'][] = $detail;
             }
        }

        $detail_pemberian_obat2[] = $row;
      }

      $show_serahkan_semua = false;
      foreach ($resep as $r) {
          if ($r['tgl_perawatan'] !== '0000-00-00' && (!isset($r['tgl_penyerahan']) || $r['tgl_penyerahan'] === '0000-00-00')) {
              $show_serahkan_semua = true;
              break;
          }
      }
      if (!$show_serahkan_semua) {
          foreach ($resep_racikan as $r) {
              if ($r['tgl_perawatan'] !== '0000-00-00' && (!isset($r['tgl_penyerahan']) || $r['tgl_penyerahan'] === '0000-00-00')) {
                  $show_serahkan_semua = true;
                  break;
              }
          }
      }

      echo $this->draw('rincian.html', ['jumlah_total_resep' => $jumlah_total_resep, 'jumlah_total_obat' => $jumlah_total_obat, 'jumlah_total_obat2' => $jumlah_total_obat2, 'resep' => htmlspecialchars_array($resep), 'resep_racikan' => htmlspecialchars_array($resep_racikan), 'jumlah_total_resep_racikan' => $jumlah_total_resep_racikan, 'detail_pemberian_obat' => htmlspecialchars_array($detail_pemberian_obat), 'detail_pemberian_obat_racikan' => htmlspecialchars_array($detail_pemberian_obat2), 'no_rawat' => htmlspecialchars($_POST['no_rawat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'pasien' => htmlspecialchars_array($pasien), 'diagnosa' => htmlspecialchars_array($diagnosa), 'riwayat_obat' => htmlspecialchars_array($riwayat_obat), 'show_serahkan_semua' => $show_serahkan_semua]);
      exit();
    }

    public function anyObat()
    {
      $obat = $this->db('databarang')
        ->join('gudangbarang', 'gudangbarang.kode_brng=databarang.kode_brng')
        ->where('status', '1')
        ->where('gudangbarang.kd_bangsal', $this->settings->get('farmasi.deporalan'))
        ->like('databarang.nama_brng', '%'.htmlspecialchars($_POST['obat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('obat.html', ['obat' => htmlspecialchars_array($obat)]);
      exit();
    }

    public function getAjax()
    {
        header('Content-type: text/html');
        $show = isset($_GET['show']) ? $_GET['show'] : "";
        switch($show){
        	default:
          break;
        case "databarang":
          $rows = $this->db('databarang')
            ->join('gudangbarang', 'gudangbarang.kode_brng=databarang.kode_brng')
            ->where('status', '1')
            ->where('stok', '>', '1')
            ->where('gudangbarang.kd_bangsal', $this->settings->get('farmasi.deporalan'))
            ->like('databarang.nama_brng', '%'.$_GET['nama_brng'].'%')
            ->limit(10)
            ->toArray();

          $array = [];
          foreach ($rows as $row) {
            $array[] = array(
                'kode_brng' => $row['kode_brng'],
                'nama_brng'  => $row['nama_brng'],
                'stok'  => $row['stok'],
                'ralan'  => $row['ralan']
            );
          }
          echo json_encode(htmlspecialchars_array($array), true);
          break;
        }
        exit();
    }

    public function anyRacikan()
    {
      $racikan = $this->db('metode_racik')
        ->like('nm_racik', '%'.htmlspecialchars($_POST['racikan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->toArray();
      echo $this->draw('racikan.html', ['racikan' => htmlspecialchars_array($racikan)]);
      exit();
    }

    public function postAturanPakai()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('master_aturan_pakai')->like('aturan', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["aturan"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["kd_dokter"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').': '.htmlspecialchars($row["nm_dokter"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList2()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('petugas')->like('nama', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["nip"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').': '.htmlspecialchars($row["nama"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function getCetakLabel($kode_brng, $no_rawat, $tgl_peresepan, $jam_peresepan, $tipe)
    {
      $detail_pemberian_obat = [];

      if ($kode_brng === 'all') {

        $rows = $this->db('detail_pemberian_obat')
          ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
          ->join('reg_periksa', 'reg_periksa.no_rawat=detail_pemberian_obat.no_rawat')
          ->where('detail_pemberian_obat.no_rawat', revertNoRawat($no_rawat))
          ->where('detail_pemberian_obat.status', 'Ralan')
          ->toArray();

        foreach ($rows as $row) {
          $aturan = $this->db('aturan_pakai')
            ->where('no_rawat', $row['no_rawat'])
            ->where('kode_brng', $row['kode_brng'])
            ->where('tgl_perawatan', $row['tgl_perawatan'])
            ->where('jam', $row['jam'])
            ->oneArray();

          $row['aturan_pakai'] = $aturan['aturan'] ?? '';
          $row['keterangan']   = '';
          $detail_pemberian_obat[] = $row;
        }

        $rows_racik = $this->db('obat_racikan')
          ->join('reg_periksa', 'reg_periksa.no_rawat=obat_racikan.no_rawat')
          ->where('obat_racikan.no_rawat', revertNoRawat($no_rawat))
          ->toArray();

        foreach ($rows_racik as $row) {
          $detail_pemberian_obat[] = [
            'nama_brng' => $row['nama_racik'],
            'jml'       => $row['jml_dr'],
            'aturan_pakai' => $row['aturan_pakai'],
            'keterangan'   => ''
          ];
        }

      } else {

        if ($tipe === 'nonracikan') {

          $rows = $this->db('detail_pemberian_obat')
            ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
            ->join('reg_periksa', 'reg_periksa.no_rawat=detail_pemberian_obat.no_rawat')
            ->where('detail_pemberian_obat.no_rawat', revertNoRawat($no_rawat))
            ->where('detail_pemberian_obat.status', 'Ralan')
            ->where('detail_pemberian_obat.tgl_perawatan', $tgl_peresepan)
            ->where('detail_pemberian_obat.jam', $jam_peresepan)
            ->where('detail_pemberian_obat.kode_brng', $kode_brng)
            ->toArray();

          foreach ($rows as $row) {
            $aturan = $this->db('aturan_pakai')
              ->where('no_rawat', $row['no_rawat'])
              ->where('kode_brng', $row['kode_brng'])
              ->where('tgl_perawatan', $row['tgl_perawatan'])
              ->where('jam', $row['jam'])
              ->oneArray();

            $row['aturan_pakai'] = $aturan['aturan'] ?? '';
            $row['keterangan']   = '';
            $detail_pemberian_obat[] = $row;
          }
        }

        if ($tipe === 'racikan') {

          $rows = $this->db('obat_racikan')
            ->join('reg_periksa', 'reg_periksa.no_rawat=obat_racikan.no_rawat')
            ->where('obat_racikan.no_rawat', revertNoRawat($no_rawat))
            ->where('obat_racikan.kd_racik', $kode_brng)
            ->where('obat_racikan.tgl_perawatan', $tgl_peresepan)
            ->where('obat_racikan.jam', $jam_peresepan)
            ->toArray();

          foreach ($rows as $row) {
            $detail_pemberian_obat[] = [
              'nama_brng' => $row['nama_racik'],
              'jml'       => $row['jml_dr'],
              'aturan_pakai' => $row['aturan_pakai'],
              'keterangan'   => ''
            ];
          }
        }

      }

      // ==== DATA TAMBAHAN ====
      $tanggal = dateIndonesia(date('Y-m-d'));
      $no_rawat_real = revertNoRawat($no_rawat);
      $no_rm = $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat_real);
      $pasien = $this->core->getPasienInfo('nm_pasien', $no_rm);

      // ==== RENDER HTML ====
      $html = $this->draw('cetak.etiket.html', [
        'pasien'   => $pasien,
        'no_rm'    => $no_rm,
        'tanggal'  => $tanggal,
        'settings' => $this->settings('settings'),
        'farmasi'  => $this->settings('farmasi'),
        'detail'   => htmlspecialchars_array($detail_pemberian_obat)
      ]);
      echo $html;
      exit;
    }

    public function getCetakEresep($no_rawat, $tipe, $tgl_peresepan, $jam_peresepan)
    {
      $no_rawat_real = revertNoRawat($no_rawat);
      $detail_pemberian_obat = [];

      /* ================= NON RACIKAN ================= */
      if ($tipe === 'nonracikan') {

        $resep_obat = $this->db('resep_obat')
          ->where('no_rawat', $no_rawat_real)
          ->oneArray();

        $racikan = $this->db('resep_dokter_racikan_detail')
          ->select('kode_brng')
          ->where('no_resep', $resep_obat['no_resep'] ?? '')
          ->toArray();

        $notIn = array_column($racikan, 'kode_brng');

        $query = $this->db('detail_pemberian_obat')
          ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
          ->join('reg_periksa', 'reg_periksa.no_rawat=detail_pemberian_obat.no_rawat')
          ->where('detail_pemberian_obat.no_rawat', $no_rawat_real)
          ->where('detail_pemberian_obat.status', 'Ralan')
          ->where('detail_pemberian_obat.tgl_perawatan', $tgl_peresepan)
          ->where('detail_pemberian_obat.jam', $jam_peresepan);

        if (!empty($notIn)) {
          $query->notIn('detail_pemberian_obat.kode_brng', $notIn);
        }

        $rows = $query->toArray();

        foreach ($rows as $row) {
          $aturan = $this->db('aturan_pakai')
            ->where('no_rawat', $row['no_rawat'])
            ->where('kode_brng', $row['kode_brng'])
            ->where('tgl_perawatan', $row['tgl_perawatan'])
            ->where('jam', $row['jam'])
            ->oneArray();

          $row['aturan_pakai'] = $aturan['aturan'] ?? '';
          $row['keterangan']   = '';
          $detail_pemberian_obat[] = $row;
        }
      }

      /* ================= RACIKAN ================= */
      if ($tipe === 'racikan') {

        $rows = $this->db('obat_racikan')
          ->join('reg_periksa', 'reg_periksa.no_rawat=obat_racikan.no_rawat')
          ->where('obat_racikan.no_rawat', $no_rawat_real)
          ->where('obat_racikan.tgl_perawatan', $tgl_peresepan)
          ->where('obat_racikan.jam', $jam_peresepan)
          ->toArray();

        foreach ($rows as $row) {
          $detail_pemberian_obat[] = [
            'nama_brng' => $row['nama_racik'],
            'jml'       => $row['jml_dr'],
            'aturan_pakai' => $row['aturan_pakai'],
            'keterangan'   => ''
          ];
        }
      }

      /* ================= DATA RESEP & DOKTER ================= */
      $resep_obat = $this->db('resep_obat')
        ->where('no_rawat', $no_rawat_real)
        ->where('tgl_peresepan', $tgl_peresepan)
        ->where('jam_peresepan', $jam_peresepan)
        ->oneArray();

      if (!$resep_obat) {
        $resep_obat = $this->db('resep_obat')
          ->where('no_rawat', $no_rawat_real)
          ->where('tgl_perawatan', $tgl_peresepan)
          ->where('jam', $jam_peresepan)
          ->oneArray();
      }

      if (!$resep_obat) {
        $resep_obat = $this->db('resep_obat')
          ->where('no_rawat', $no_rawat_real)
          ->oneArray();
      }

      $dokter = [];
      if (!empty($resep_obat['kd_dokter'])) {
        $dokter = $this->db('dokter')
          ->where('kd_dokter', $resep_obat['kd_dokter'])
          ->oneArray();
      } else {
        $kd_dokter = $this->core->getRegPeriksaInfo('kd_dokter', $no_rawat_real);
        if ($kd_dokter) {
          $dokter = $this->db('dokter')
            ->where('kd_dokter', $kd_dokter)
            ->oneArray();
        }
      }

      $tgl_resep_db = $resep_obat['tgl_peresepan'] ?? '';
      if (empty($tgl_resep_db) || $tgl_resep_db === '0000-00-00') {
        $tgl_resep_db = $resep_obat['tgl_perawatan'] ?? '';
      }
      if (empty($tgl_resep_db) || $tgl_resep_db === '0000-00-00') {
        $tgl_resep_db = date('Y-m-d');
      }
      $tanggal = dateIndonesia($tgl_resep_db);

      /* ================= DATA PASIEN ================= */
      $no_rm   = $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat_real);
      $pasien  = $this->core->getPasienInfo('nm_pasien', $no_rm);
      $umur    = $this->core->getRegPeriksaInfo('umurdaftar', $no_rawat_real);
      $sttsumur= $this->core->getRegPeriksaInfo('sttsumur', $no_rawat_real);
      $alamat  = $this->core->getPasienInfo('alamat', $no_rm);
      $tgl_lahir = $this->core->getPasienInfo('tgl_lahir', $no_rm);
      if (empty($tgl_lahir) || $tgl_lahir === '0000-00-00') {
        $tgl_lahir = '-';
      }

      $bb  = '';
      $tb  = '';
      $pemeriksaan = $this->db('pemeriksaan_ralan')
        ->where('no_rawat', $no_rawat_real)
        ->oneArray();
      if (!$pemeriksaan) {
        $pemeriksaan = $this->db('pemeriksaan_ranap')
          ->where('no_rawat', $no_rawat_real)
          ->oneArray();
      }
      if ($pemeriksaan) {
        $bb = !empty($pemeriksaan['berat']) ? $pemeriksaan['berat'] : '';
        $tb = !empty($pemeriksaan['tinggi']) ? $pemeriksaan['tinggi'] : '';
      }

      /* ================= ALERGI PASIEN ================= */
      $alergi = '';
      if (!empty($no_rm)) {
        $alergi_list = [];
        $alergi_ralan = $this->db('pemeriksaan_ralan')
          ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ralan.no_rawat')
          ->where('reg_periksa.no_rkm_medis', $no_rm)
          ->where('pemeriksaan_ralan.alergi', '!=', '')
          ->where('pemeriksaan_ralan.alergi', '!=', '-')
          ->toArray();
        foreach ($alergi_ralan as $a) {
          $alergi_list[] = $a['alergi'];
        }
        $alergi_ranap = $this->db('pemeriksaan_ranap')
          ->join('reg_periksa', 'reg_periksa.no_rawat=pemeriksaan_ranap.no_rawat')
          ->where('reg_periksa.no_rkm_medis', $no_rm)
          ->where('pemeriksaan_ranap.alergi', '!=', '')
          ->where('pemeriksaan_ranap.alergi', '!=', '-')
          ->toArray();
        foreach ($alergi_ranap as $a) {
          $alergi_list[] = $a['alergi'];
        }
        $alergi_unique = array_unique(array_filter($alergi_list));
        $alergi = !empty($alergi_unique) ? implode(', ', $alergi_unique) : 'Tidak Ada Alergi';
      }

      /* ================= CARA BAYAR (BPJS / UMUM) ================= */
      $cara_bayar = '';
      $kd_pj = $this->core->getRegPeriksaInfo('kd_pj', $no_rawat_real);
      if ($kd_pj) {
        $cara_bayar = $this->core->getPenjabInfo('png_jawab', $kd_pj);
      }

      $kd_poli = $this->core->getRegPeriksaInfo('kd_poli', $no_rawat_real);
      $nm_poli = $kd_poli ? $this->core->getPoliklinikInfo('nm_poli', $kd_poli) : '';

      $tgl_peresepan = $resep_obat['tgl_peresepan'] ?? '';
      if (empty($tgl_peresepan) || $tgl_peresepan === '0000-00-00') {
        $tgl_peresepan = $tgl_resep_db;
      }
      $jam_peresepan = $resep_obat['jam_peresepan'] ?? '';

      /* ================= PISAHKAN CATATAN SKRINING & PENYERAHAN ================= */
      $catatan_full = isset($resep_obat['catatan_skrining']) ? $resep_obat['catatan_skrining'] : '';
      $catatan_penyerahan = '';
      if (strpos($catatan_full, ' || Verifikasi: ') !== false) {
        list($catatan_skrining_cetak, $catatan_penyerahan) = explode(' || Verifikasi: ', $catatan_full, 2);
      } elseif (strpos($catatan_full, 'Verifikasi: ') === 0) {
        $catatan_skrining_cetak = '';
        $catatan_penyerahan = substr($catatan_full, strlen('Verifikasi: '));
      } else {
        $catatan_skrining_cetak = $catatan_full;
      }
      $resep_obat['catatan_skrining'] = trim($catatan_skrining_cetak);

      /* ================= RENDER HTML ================= */
      $html = $this->draw('cetak.eresep.html', [
        'pasien'   => $pasien,
        'no_rm'    => $no_rm,
        'umur'     => $umur . ' ' . $sttsumur,
        'tgl_lahir'=> $tgl_lahir,
        'alamat'   => $alamat,
        'bb'       => $bb,
        'tb'       => $tb,
        'alergi'   => $alergi,
        'cara_bayar' => $cara_bayar,
        'nm_poli'  => $nm_poli,
        'catatan_penyerahan' => htmlspecialchars($catatan_penyerahan, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'judul'    => 'Resep Rawat Jalan',
        'tgl_peresepan'  => $tgl_peresepan,
        'jam_peresepan'  => $jam_peresepan,
        'tanggal'  => $tanggal,
        'settings' => $this->settings('settings'),
        'detail'   => htmlspecialchars_array($detail_pemberian_obat),
        'resep_obat' => is_array($resep_obat) ? htmlspecialchars_array($resep_obat) : [],
        'dokter'   => is_array($dokter) ? htmlspecialchars_array($dokter) : []
      ]);

      echo $html;
      exit;
    }


    public function getJavascript()
    {
        header('Content-type: text/javascript');
        $this->assign['websocket'] = $this->settings->get('settings.websocket');
        $this->assign['websocket_proxy'] = $this->settings->get('settings.websocket_proxy');
        echo $this->draw(MODULES.'/apotek_ralan/js/admin/apotek_ralan.js', ['mlite' => htmlspecialchars_array($this->assign)]);
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
        $this->core->addJS(url([ADMIN, 'apotek_ralan', 'javascript']), 'footer');
    }

    public function apiResepList()
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $this->db()->pdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 10;
        $offset = ($page - 1) * $per_page;
        $search = isset($_GET['s']) ? $_GET['s'] : '';
        $tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
        $tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

        $query = $this->db('resep_obat')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = resep_obat.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->leftJoin('dokter', 'dokter.kd_dokter = resep_obat.kd_dokter')
            ->where(function($query) {
                $query->where('resep_obat.status', 'ralan');
            })
            ->where('resep_obat.tgl_peresepan', '>=', $tgl_awal)
            ->where('resep_obat.tgl_peresepan', '<=', $tgl_akhir);

        if ($search) {
             $query->like('resep_obat.no_resep', '%'.$search.'%');
        }

        $total = $query->count();
        $data = $query
            ->select('resep_obat.*')
            ->select('pasien.nm_pasien')
            ->select('pasien.no_rkm_medis')
            ->select('dokter.nm_dokter')
            ->offset($offset)
            ->limit($per_page)
            ->desc('resep_obat.tgl_peresepan')
            ->desc('resep_obat.jam_peresepan')
            ->toArray();

        foreach ($data as &$row) {
            $is_obat = $this->db('resep_dokter')->select('no_resep')->where('no_resep', $row['no_resep'])->oneArray();
            $is_racikan = $this->db('resep_dokter_racikan')->select('no_resep')->where('no_resep', $row['no_resep'])->oneArray();
            
            if ($is_obat && $is_racikan) {
                $row['kategori'] = 'obat,racikan';
            } elseif ($is_obat) {
                $row['kategori'] = 'obat';
            } elseif ($is_racikan) {
                $row['kategori'] = 'racikan';
            } else {
                $row['kategori'] = '-';
            }
        }

        // Optimization: Details are fetched asynchronously on frontend
        // foreach ($data as &$row) {
        //    $row['detail'] = $this->apiShowDetail('obat', $row['no_rawat'], $row['no_resep']);
        //    $row['racikan'] = $this->apiShowDetail('racikan', $row['no_rawat'], $row['no_resep']);
        // }

        return [
            'status' => 'success',
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page
        ];
    }

    public function apiShowDetail($category = null, $no_rawat = null, $no_resep = null)
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $no_rawat = revertNorawat($no_rawat);
        $kategori = trim($category);
        
        if (!$no_resep) {
            $no_resep = isset($_GET['no_resep']) ? $_GET['no_resep'] : null;
        }

        $pasien = $this->db('reg_periksa')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('no_rawat', $no_rawat)
            ->oneArray();

        $patient_info = [
            'nm_pasien' => $pasien['nm_pasien'] ?? '',
            'no_rkm_medis' => $pasien['no_rkm_medis'] ?? ''
        ];

        try {
            if ($kategori == 'obat') {
                $query = $this->db('resep_dokter')
                    ->join('resep_obat', 'resep_obat.no_resep = resep_dokter.no_resep')
                    ->join('databarang', 'databarang.kode_brng = resep_dokter.kode_brng')
                    ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = resep_obat.no_rawat')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('resep_obat.no_rawat', $no_rawat)
                    ->where(function($query) {
                        $query->where('resep_obat.status', 'ralan');
                    });

                if ($no_resep) {
                    $query->where('resep_obat.no_resep', $no_resep);
                }

                $resep_dokter = $query->toArray();

                return [
                    'status' => 'success',
                    'patient' => $patient_info,
                    'data' => $resep_dokter
                ];
            } elseif ($kategori == 'racikan') {
                $query = $this->db('resep_dokter_racikan')
                    ->join('resep_obat', 'resep_obat.no_resep = resep_dokter_racikan.no_resep')
                    ->join('metode_racik', 'metode_racik.kd_racik = resep_dokter_racikan.kd_racik')
                    ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = resep_obat.no_rawat')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('resep_obat.no_rawat', $no_rawat)
                    ->where(function($query) {
                        $query->where('resep_obat.status', 'ralan');
                    });

                if ($no_resep) {
                    $query->where('resep_obat.no_resep', $no_resep);
                }

                $resep_racikan = $query->toArray();

                foreach ($resep_racikan as &$racikan) {
                    $racikan['detail'] = $this->db('resep_dokter_racikan_detail')
                        ->join('databarang', 'databarang.kode_brng = resep_dokter_racikan_detail.kode_brng')
                        ->where('no_resep', $racikan['no_resep'])
                        ->where('no_racik', $racikan['no_racik'])
                        ->toArray();
                }

                return [
                    'status' => 'success',
                    'patient' => $patient_info,
                    'data' => htmlspecialchars_array($resep_racikan)
                ];
            } else {
                return ['status' => 'error', 'message' => 'Category not supported: ' . $kategori];
            }
        } catch (\PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function apiValidasi($no_rawat = null, $no_resep = null)
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $no_rawat = revertNorawat($no_rawat);
        if (!$no_resep) {
            $no_resep = isset($_GET['no_resep']) ? $_GET['no_resep'] : null;
        }

        $detail_pemberian_obat = $this->db('detail_pemberian_obat')
            ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
            ->where('no_rawat', $no_rawat)
            ->where('status', 'Ralan')
            ->toArray();

        // Also fetch obat_racikan
        $obat_racikan = $this->db('obat_racikan')
            ->where('no_rawat', $no_rawat)
            ->toArray();

        return [
            'status' => 'success',
            'data' => [
                'pemberian_obat' => htmlspecialchars_array($detail_pemberian_obat),
                'obat_racikan' => htmlspecialchars_array($obat_racikan)
            ]
        ];
    }

    public function apiSaveValidasi()
    {
        $username = $this->core->checkAuth('POST');
        if (!$this->core->checkPermission($username, 'can_create', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $tgl_rawat = date('Y-m-d');
        $jam_rawat = date('H:i:s');
        
        $no_resep = $input['no_resep'];
        $no_rawat = $input['no_rawat'];

        if(isset($input['penyerahan']) && $input['penyerahan'] == 'penyerahan') {
            $this->db('resep_obat')->where('no_resep', $no_resep)->save(['tgl_penyerahan' => $tgl_rawat, 'jam_penyerahan' => $jam_rawat]);
        } else {
            $get_resep_dokter_nonracikan = $this->db('resep_dokter')
              ->select([
                  'kode_brng' => 'kode_brng',
                  'jml' => 'jml',
                  'aturan_pakai' => 'aturan_pakai'
                ])
              ->where('no_resep', $no_resep)
              ->toArray();
            $get_resep_dokter_racikan = $this->db('resep_dokter_racikan')
              ->select([
                  'no_racik' => 'resep_dokter_racikan.no_racik',
                  'nama_racik' => 'resep_dokter_racikan.nama_racik',
                  'kd_racik' => 'resep_dokter_racikan.kd_racik',
                  'jml_dr' => 'resep_dokter_racikan.jml_dr',
                  'keterangan' => 'resep_dokter_racikan.keterangan',
                  'kode_brng' => 'kode_brng',
                  'jml' => 'jml',
                  'aturan_pakai' => 'aturan_pakai'
                ])
              ->join('resep_dokter_racikan_detail', 'resep_dokter_racikan_detail.no_resep=resep_dokter_racikan.no_resep AND resep_dokter_racikan.no_racik=resep_dokter_racikan_detail.no_racik')
              ->where('resep_dokter_racikan.no_resep', $no_resep)
              ->toArray();
            $get_resep_dokter = array_merge($get_resep_dokter_nonracikan, $get_resep_dokter_racikan);

            $embalaseData = isset($input['embalase']) ? (is_array($input['embalase']) ? $input['embalase'] : json_decode($input['embalase'], true)) : [];
            $tuslahData = isset($input['tuslah']) ? (is_array($input['tuslah']) ? $input['tuslah'] : json_decode($input['tuslah'], true)) : [];
            $jumlahData = isset($input['jumlah']) ? (is_array($input['jumlah']) ? $input['jumlah'] : json_decode($input['jumlah'], true)) : [];
            $kandunganData = isset($input['kandungan']) ? (is_array($input['kandungan']) ? $input['kandungan'] : json_decode($input['kandungan'], true)) : [];
            $aturanPakaiData = isset($input['aturan_pakai']) ? (is_array($input['aturan_pakai']) ? $input['aturan_pakai'] : json_decode($input['aturan_pakai'], true)) : [];

            if(!empty($get_resep_dokter_racikan)) {
                $racikan_unique = [];
                foreach ($get_resep_dokter_racikan as $row) {
                    if (!isset($racikan_unique[$row['no_racik']])) {
                        $racikan_unique[$row['no_racik']] = $row;
                    }
                }
                
                $aturan_pakai_racikan = isset($aturanPakaiData[$racikan['kd_racik']]) ? $aturanPakaiData[$racikan['kd_racik']] : $racikan['aturan_pakai'];

                foreach ($racikan_unique as $racikan) {
                    $this->db('obat_racikan')->save(
                        [
                            'tgl_perawatan' => $tgl_rawat,
                            'jam' => $jam_rawat,
                            'no_rawat' => $no_rawat,
                            'no_racik' => htmlspecialchars_array($racikan)['no_racik'],
                            'nama_racik' => htmlspecialchars_array($racikan)['nama_racik'],
                            'kd_racik' => htmlspecialchars_array($racikan)['kd_racik'],
                            'jml_dr' => htmlspecialchars_array($racikan)['jml_dr'],
                            'aturan_pakai' => $aturan_pakai_racikan,
                            'keterangan' => htmlspecialchars_array($racikan)['keterangan']
                        ]
                    );
                }
            }

            foreach ($get_resep_dokter as $item) {

              $jumlah = isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml'];
              $kandungan = isset($kandunganData[$item['kode_brng']]) ? $kandunganData[$item['kode_brng']] : (isset($item['kandungan']) ? $item['kandungan'] : 0);
              $aturan_pakai = isset($aturanPakaiData[$item['kode_brng']]) ? $aturanPakaiData[$item['kode_brng']] : (isset($item['aturan_pakai']) ? $item['aturan_pakai'] : '');

              if(isset($item['no_racik'])) {
                 $jumlah_racik = isset($item['jml_dr']) ? $item['jml_dr'] : (isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml']);
                 $jumlah = isset($jumlahData[$item['kode_brng']]) ? $jumlahData[$item['kode_brng']] : $item['jml'];
              }

              $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $item['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
              $get_databarang = $this->db('databarang')->where('kode_brng', $item['kode_brng'])->oneArray();

              $this->db('gudangbarang')
                ->where('kode_brng', $item['kode_brng'])
                ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
                ->update([
                  'stok' => $get_gudangbarang['stok'] - $jumlah
                ]);

              if(isset($item['no_racik'])) {
                $this->db('resep_dokter_racikan')
                  ->where('no_resep', $no_resep)
                  ->where('no_racik', $item['no_racik'])
                  ->update([
                    'jml_dr' => $jumlah_racik
                  ]);
                $this->db('resep_dokter_racikan_detail')
                  ->where('no_resep', $no_resep)
                  ->where('no_racik', $item['no_racik'])
                  ->where('kode_brng', $item['kode_brng'])
                  ->update([
                    'jml' => $jumlah,
                    'kandungan' => $kandungan
                  ]);
              } else {
                $this->db('resep_dokter')
                  ->where('no_resep', $no_resep)
                  ->where('kode_brng', $item['kode_brng'])
                  ->update([
                    'jml' => $jumlah
                  ]);
              }

              $this->db('riwayat_barang_medis')
                ->save([
                  'kode_brng' => $item['kode_brng'],
                  'stok_awal' => $get_gudangbarang['stok'],
                  'masuk' => '0',
                  'keluar' => $jumlah,
                  'stok_akhir' => $get_gudangbarang['stok'] - $jumlah,
                  'posisi' => 'Pemberian Obat',
                  'tanggal' => $tgl_rawat,
                  'jam' => $jam_rawat,
                  'petugas' => $this->core->getUserInfo('fullname', null, true),
                  'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                  'status' => 'Simpan',
                  'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
                  'no_faktur' => isset($get_gudangbarang['no_faktur']) ? $get_gudangbarang['no_faktur'] : '',
                  'keterangan' => $no_rawat . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $no_rawat))
                ]);

              $embalase = (isset($embalaseData[$item['kode_brng']]) && $embalaseData[$item['kode_brng']] !== '') ? $embalaseData[$item['kode_brng']] : $this->settings->get('farmasi.embalase');
              $tuslah = (isset($tuslahData[$item['kode_brng']]) && $tuslahData[$item['kode_brng']] !== '') ? $tuslahData[$item['kode_brng']] : $this->settings->get('farmasi.tuslah');

              $this->db('detail_pemberian_obat')
                ->save([
                  'tgl_perawatan' => $tgl_rawat,
                  'jam' => $jam_rawat,
                  'no_rawat' => $no_rawat,
                  'kode_brng' => $item['kode_brng'],
                  'h_beli' => $get_databarang['h_beli'],
                  'biaya_obat' => $get_databarang['dasar'],
                  'jml' => $jumlah,
                  'embalase' => $embalase,
                  'tuslah' => $tuslah,
                  'total' => ($get_databarang['dasar'] * $jumlah) + $embalase + $tuslah,
                  'status' => 'Ralan',
                  'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                  'no_batch' => isset($get_gudangbarang['no_batch']) ? $get_gudangbarang['no_batch'] : '',
                  'no_faktur' => $get_gudangbarang['no_faktur']
                ]);

              $this->db('aturan_pakai')
                ->save([
                  'tgl_perawatan' => $tgl_rawat,
                  'jam' => $jam_rawat,
                  'no_rawat' => $no_rawat,
                  'kode_brng' => $item['kode_brng'],
                  'aturan' => $aturan_pakai
                ]);

              if(isset($item['no_racik'])) {
                $this->db('detail_obat_racikan')
                  ->save([
                    'tgl_perawatan' => $tgl_rawat,
                    'jam' => $jam_rawat,
                    'no_rawat' => $no_rawat,
                    'no_racik' => $item['no_racik'],
                    'kode_brng' => $item['kode_brng']
                  ]);
              }

            }

            $this->db('resep_obat')->where('no_resep', $no_resep)->save(['tgl_perawatan' => $tgl_rawat, 'jam' => $jam_rawat]);
        }
        
        return ['status' => 'success'];
    }

    public function apiSimpanObatResep()
    {
        $username = $this->core->checkAuth('POST');
        if (!$this->core->checkPermission($username, 'can_create', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $check = $this->db('resep_dokter')
            ->where('no_resep', $input['no_resep'])
            ->where('kode_brng', $input['kode_brng'])
            ->oneArray();

        if ($check) {
            $this->db('resep_dokter')
                ->where('no_resep', $input['no_resep'])
                ->where('kode_brng', $input['kode_brng'])
                ->update([
                    'jml' => $input['jml'],
                    'aturan_pakai' => $input['aturan_pakai']
                ]);
        } else {
            $this->db('resep_dokter')
                ->save([
                    'no_resep' => $input['no_resep'],
                    'kode_brng' => $input['kode_brng'],
                    'jml' => $input['jml'],
                    'aturan_pakai' => $input['aturan_pakai']
                ]);
        }

        return ['status' => 'success'];
    }

    public function apiSimpanRacikanResep()
    {
        $username = $this->core->checkAuth('POST');
        if (!$this->core->checkPermission($username, 'can_create', 'apotek_ralan')) {
            return ['status' => 'error', 'message' => 'You do not have permission to access this resource'];
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // 1. Save Header Racikan
        $no_racik = $input['no_racik'];
        $check = $this->db('resep_dokter_racikan')
            ->where('no_resep', $input['no_resep'])
            ->where('no_racik', $no_racik)
            ->oneArray();

        if (!$check) {
            $this->db('resep_dokter_racikan')->save([
                'no_resep' => $input['no_resep'],
                'no_racik' => $no_racik,
                'nama_racik' => $input['nama_racik'],
                'kd_racik' => $input['kd_racik'],
                'jml_dr' => $input['jml_dr'],
                'aturan_pakai' => $input['aturan_pakai'],
                'keterangan' => $input['keterangan']
            ]);
        } else {
            $this->db('resep_dokter_racikan')
                ->where('no_resep', $input['no_resep'])
                ->where('no_racik', $no_racik)
                ->update([
                    'nama_racik' => $input['nama_racik'],
                    'jml_dr' => $input['jml_dr'],
                    'aturan_pakai' => $input['aturan_pakai'],
                    'keterangan' => $input['keterangan']
                ]);
        }

        // 2. Save Ingredients
        $items = isset($input['items']) ? (is_array($input['items']) ? $input['items'] : json_decode($input['items'], true)) : [];
        
        if (is_array($items)) {
            // Delete existing details for this racikan to allow full update
            $this->db('resep_dokter_racikan_detail')
                ->where('no_resep', $input['no_resep'])
                ->where('no_racik', $no_racik)
                ->delete();

            foreach ($items as $item) {
                $this->db('resep_dokter_racikan_detail')->save([
                    'no_resep' => $input['no_resep'],
                    'no_racik' => $no_racik,
                    'kode_brng' => $item['kode_brng'],
                    'p1' => 1,
                    'p2' => 1,
                    'kandungan' => $item['kandungan'],
                    'jml' => $item['jml']
                ]);
            }
        }

        return ['status' => 'success'];
    }

}

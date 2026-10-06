<?php
namespace Plugins\Update_bmt;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Rawat Jalan' => [
                'RM.GD 4 - ASESMEN KEPERAWATAN.ASUHAN GIGI DAN MULUT UGD' => 'manage',
                'RM.RJ-10 - LEMBAR EDUKASI PASIEN DAN KELUARGA TERINTEGRASI' => 'edukasimanage',
            ],
            'Bedah' => [
                'ANS1 - INFORMASI TINDAKAN PEMBIUSAN' => 'ans1manage',
                'ANS2 - PERSETUJUAN TINDAKAN PEMBIUSAN' => 'ans2manage',
                'ANS3 - ASSESMEN PRASEDASI / ANESTESI' => 'ans3manage',
            ]
        ];
    }

    // ============================================================
    // RM.RJ-10 LEMBAR EDUKASI PASIEN DAN KELUARGA TERINTEGRASI
    // ============================================================

    public function anyEdukasimanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $edukasi = $this->db('edukasi_pasien_terintegrasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = edukasi_pasien_terintegrasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('edukasi_pasien_terintegrasi.*, COALESCE(edukasi_pasien_terintegrasi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis')
            ->toArray();

        return $this->draw('edukasipasienter/manage.html', ['edukasi' => $edukasi]);
    }

    public function getEdukasiform()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        $detail = [];

        if (!empty($id)) {
            $data = $this->db('edukasi_pasien_terintegrasi')->where('id', $id)->oneArray();
            if ($data) {
                $detail = $this->db('edukasi_pasien_terintegrasi_detail')
                    ->where('id_edukasi', $id)
                    ->asc('tanggal_jam')
                    ->toArray();

                // Fallback data pasien dari reg_periksa jika kosong
                if (!empty($data['no_rawat']) && empty($data['nama_pasien'])) {
                    $reg = $this->db('reg_periksa')
                        ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                        ->where('reg_periksa.no_rawat', $data['no_rawat'])
                        ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                        ->oneArray();
                    if ($reg) {
                        $data['nama_pasien']   = $reg['nm_pasien'] ?? '';
                        $data['jenis_kelamin'] = $reg['jk'] ?? '';
                        $data['tanggal_lahir'] = $reg['tgl_lahir'] ?? '';
                        $data['no_rkm_medis']  = $reg['no_rkm_medis'] ?? '';
                    }
                }
            }
        }

        return $this->draw('edukasipasienter/form.html', [
            'data'   => $data,
            'detail' => $detail,
        ]);
    }

    public function postEdukasisave()
    {
        try {
            $post = $_POST;
            $id   = !empty($post['id']) ? (int)$post['id'] : null;

            // Data header
            $header = [
                'no_rawat'                             => $post['no_rawat'] ?? '',
                'nama_pasien'                          => $post['nama_pasien'] ?? '',
                'jenis_kelamin'                        => $post['jenis_kelamin'] ?? '',
                'tanggal_lahir'                        => !empty($post['tanggal_lahir']) ? $post['tanggal_lahir'] : null,
                'hambatan_emosi'                       => isset($post['hambatan_emosi']) ? 1 : 0,
                'hambatan_motivasi'                    => isset($post['hambatan_motivasi']) ? 1 : 0,
                'hambatan_tidak_ada'                   => isset($post['hambatan_tidak_ada']) ? 1 : 0,
                'pantangan_hari'                       => $post['pantangan_hari'] ?? '',
                'pantangan_makan'                      => $post['pantangan_makan'] ?? '',
                'pantangan_perawatan'                  => $post['pantangan_perawatan'] ?? '',
                'keterbatasan_fisik'                   => isset($post['keterbatasan_fisik']) ? 1 : 0,
                'keterbatasan_kognitif'                => isset($post['keterbatasan_kognitif']) ? 1 : 0,
                'keterbatasan_tidak_ada'               => isset($post['keterbatasan_tidak_ada']) ? 1 : 0,
                'kemampuan_bisa_membaca'               => isset($post['kemampuan_bisa_membaca']) ? 1 : 0,
                'kemampuan_tidak_bisa_membaca'         => isset($post['kemampuan_tidak_bisa_membaca']) ? 1 : 0,
                'ketersediaan_menerima_edukasi_ya'     => isset($post['ketersediaan_menerima_edukasi_ya']) ? 1 : 0,
                'ketersediaan_menerima_edukasi_tidak'  => isset($post['ketersediaan_menerima_edukasi_tidak']) ? 1 : 0,
                'pendidikan_terakhir'                  => $post['pendidikan_terakhir'] ?? '',
                'bahasa_indonesia'                     => isset($post['bahasa_indonesia']) ? 1 : 0,
                'bahasa_lainnya'                       => $post['bahasa_lainnya'] ?? '',
                'kebutuhan_penerjemah_tidak'           => isset($post['kebutuhan_penerjemah_tidak']) ? 1 : 0,
                'kebutuhan_penerjemah_ya'              => $post['kebutuhan_penerjemah_ya'] ?? '',
                'nama_perawat'                         => $post['nama_perawat'] ?? '',
                'nip_perawat'                          => $post['nip_perawat'] ?? '',
                'tanggal_pengkajian'                   => !empty($post['tanggal_pengkajian']) ? $post['tanggal_pengkajian'] : null,
                'jam_pengkajian'                       => !empty($post['jam_pengkajian']) ? $post['jam_pengkajian'] : null,
                'kebutuhan_edukasi'                    => !empty($post['kebutuhan_edukasi']) ? implode(',', $post['kebutuhan_edukasi']) : '',
            ];

            // Ambil no_rkm_medis dari reg_periksa
            if (!empty($header['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                    ->where('no_rawat', $header['no_rawat'])
                    ->select('no_rkm_medis')
                    ->oneArray();
                if ($reg) {
                    $header['no_rkm_medis'] = $reg['no_rkm_medis'];
                }
            }

            if ($id) {
                $this->db('edukasi_pasien_terintegrasi')->where('id', $id)->save($header);
                // Hapus detail lama
                $this->db('edukasi_pasien_terintegrasi_detail')->where('id_edukasi', $id)->delete();
            } else {
                $this->db('edukasi_pasien_terintegrasi')->save($header);
                $inserted = $this->db('edukasi_pasien_terintegrasi')
                                 ->where('no_rawat', $header['no_rawat'])
                                 ->where('tanggal_pengkajian', $header['tanggal_pengkajian'])
                                 ->where('jam_pengkajian', $header['jam_pengkajian'])
                                 ->oneArray();
                $id = $inserted['id'] ?? null;
            }

            // Simpan detail
            $tglJam   = $post['detail_tanggal_jam']    ?? [];
            $isi      = $post['detail_isi_kebutuhan']  ?? [];
            $pra      = $post['detail_kemampuan_pra']  ?? [];
            $sasaran  = $post['detail_sasaran']        ?? [];
            $metode   = $post['detail_metode']         ?? [];
            $evaluasi = $post['detail_evaluasi']       ?? [];
            $pemberi  = $post['detail_nama_ttd_pemberi']   ?? [];
            $penerima = $post['detail_nama_ttd_penerima']  ?? [];

            foreach ($tglJam as $k => $v) {
                if (empty($v) && empty($isi[$k])) continue;
                $detailRow = [
                    'id_edukasi'           => $id,
                    'tanggal_jam'          => !empty($v) ? date('Y-m-d H:i:s', strtotime($v)) : null,
                    'isi_kebutuhan_edukasi'=> $isi[$k] ?? '',
                    'kemampuan_pra_edukasi'=> $pra[$k] ?? '',
                    'sasaran_edukasi'      => $sasaran[$k] ?? '',
                    'metode_edukasi'       => $metode[$k] ?? '',
                    'evaluasi'             => $evaluasi[$k] ?? '',
                    'nama_ttd_pemberi'     => $pemberi[$k] ?? '',
                    'nama_ttd_penerima'    => $penerima[$k] ?? '',
                ];
                $this->db('edukasi_pasien_terintegrasi_detail')->save($detailRow);
            }

            $this->notify('success', 'Data Edukasi Pasien berhasil disimpan');
            redirect(url([ADMIN, 'update_bmt', 'edukasimanage']));
        } catch (\Exception $e) {
            file_put_contents(BASE_DIR . '/tmp/edukasi_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
            die("Terjadi kesalahan, silakan refresh: " . $e->getMessage());
        }
    }

    public function getEdukasidelete()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('edukasi_pasien_terintegrasi')->where('id', $id)->delete();
            $this->db('edukasi_pasien_terintegrasi_detail')->where('id_edukasi', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'edukasimanage']));
    }

    public function anyEdukasicetak()
    {
        try {
            $id   = $_GET['id'] ?? '';
            $data = $this->db('edukasi_pasien_terintegrasi')->where('id', $id)->oneArray();

            if (!$data) {
                echo '<p>Data tidak ditemukan.</p>';
                exit();
            }

            $detail = $this->db('edukasi_pasien_terintegrasi_detail')
                ->where('id_edukasi', $id)
                ->asc('tanggal_jam')
                ->toArray();

            // Ambil no_rkm_medis & data pasien jika perlu
            if (!empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->oneArray();
                if ($reg) {
                    if (empty($data['no_rkm_medis']))  $data['no_rkm_medis']  = $reg['no_rkm_medis'] ?? '';
                    if (empty($data['nama_pasien']))    $data['nama_pasien']   = $reg['nm_pasien'] ?? '';
                    if (empty($data['jenis_kelamin']))  $data['jenis_kelamin'] = $reg['jk'] ?? '';
                    if (empty($data['tanggal_lahir']))  $data['tanggal_lahir'] = $reg['tgl_lahir'] ?? '';
                }
            }

            // Format tanggal lahir Indonesia
            if (!empty($data['tanggal_lahir'])) {
                $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                        'Juli','Agustus','September','Oktober','November','Desember'];
                $tgl = explode('-', $data['tanggal_lahir']);
                if (count($tgl) == 3) {
                    $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
                } else {
                    $data['tanggal_lahir_indo'] = $data['tanggal_lahir'];
                }
            } else {
                $data['tanggal_lahir_indo'] = '';
            }

            echo $this->draw('edukasipasienter/cetak.html', ['data' => $data, 'detail' => $detail]);
            exit();
        } catch (\Exception $e) {
            file_put_contents(BASE_DIR . '/tmp/edukasi_error_cetak.log', $e->getMessage() . "\n" . $e->getTraceAsString());
            die("Terjadi kesalahan pada Cetak, silakan refresh: " . $e->getMessage());
        }
    }

    // ============================================================
    // ASESMEN GIGI UGD (existing methods below, unchanged)
    // ============================================================

    public function anyManage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $asesmen = $this->db('asesmen_keperawatan_gigi_ugd')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = asesmen_keperawatan_gigi_ugd.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('asesmen_keperawatan_gigi_ugd.*, pasien.nm_pasien, reg_periksa.no_rkm_medis')
            ->toArray();

        return $this->draw('akgmu/manage.html', ['asesmen' => $asesmen]);
    }

    public function getForm()
    {
        $no_rawat = $_GET['no_rawat'] ?? '';
        $data = $this->db('asesmen_keperawatan_gigi_ugd')->where('no_rawat', $no_rawat)->oneArray();

        // Jika ada no_rawat, coba ambil data pasien dari relasi untuk pre-fill
        if (!empty($no_rawat)) {
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                ->oneArray();

            if ($reg) {
                // Hanya isi jika belum ada di data asesmen
                if (empty($data['nama_pasien']))   $data['nama_pasien']   = $reg['nm_pasien'] ?? '';
                if (empty($data['jenis_kelamin'])) $data['jenis_kelamin'] = $reg['jk'] ?? '';
                if (empty($data['tanggal_lahir'])) $data['tanggal_lahir'] = $reg['tgl_lahir'] ?? '';
                $data['no_rkm_medis'] = $reg['no_rkm_medis'] ?? '';
            }
        }

        return $this->draw('akgmu/form.html', ['data' => $data, 'no_rawat' => $no_rawat]);
    }

    // AJAX: ambil info pasien berdasarkan no_rawat
    public function anyPatientinfo()
    {
        $no_rawat = $_GET['no_rawat'] ?? '';
        $no_rawat = trim($no_rawat);
        
        // Jika input hanya angka dan kurang dari 6 digit, otomatis tambahkan nol di depan (format standar No RM)
        if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
            $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
        }

        $result = ['success' => false];

        if (!empty($no_rawat)) {
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                ->desc('reg_periksa.tgl_registrasi')
                ->desc('reg_periksa.jam_reg')
                ->oneArray();

            if ($reg) {
                $result = [
                    'success'       => true,
                    'no_rawat'      => $reg['no_rawat'] ?? '',
                    'nama_pasien'   => $reg['nm_pasien'] ?? '',
                    'jenis_kelamin' => $reg['jk'] ?? '',
                    'tanggal_lahir' => $reg['tgl_lahir'] ?? '',
                    'no_rkm_medis'  => $reg['no_rkm_medis'] ?? '',
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function postSave()
    {
        $data = $_POST;
        if (isset($data['no_rawat'])) {
            $cek = $this->db('asesmen_keperawatan_gigi_ugd')->where('no_rawat', $data['no_rawat'])->oneArray();
            if ($cek) {
                $this->db('asesmen_keperawatan_gigi_ugd')->where('no_rawat', $data['no_rawat'])->save($data);
            } else {
                $data['tanggal'] = date('Y-m-d H:i:s');
                $this->db('asesmen_keperawatan_gigi_ugd')->save($data);
            }
        }
        $this->notify('success', 'Data berhasil disimpan');
        redirect(url([ADMIN, 'update_bmt', 'manage']));
    }

    public function anyCetak()
    {
        $no_rawat = $_GET['no_rawat'] ?? '';
        $data = $this->db('asesmen_keperawatan_gigi_ugd')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = asesmen_keperawatan_gigi_ugd.no_rawat')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_keperawatan_gigi_ugd.nip_dpjp')
            ->leftJoin('petugas', 'petugas.nip = asesmen_keperawatan_gigi_ugd.nip_perawat')
            ->where('asesmen_keperawatan_gigi_ugd.no_rawat', $no_rawat)
            ->select('asesmen_keperawatan_gigi_ugd.*, reg_periksa.no_rkm_medis, dokter.nm_dokter, petugas.nama AS nm_perawat')
            ->oneArray();

        // Fallback no_rkm_medis
        if (empty($data['no_rkm_medis'])) {
            $data['no_rkm_medis'] = '';
        }

        // Format tanggal lahir ke format Indonesia
        if (!empty($data['tanggal_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tanggal_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tanggal_lahir'];
            }
        }

        echo $this->draw('akgmu/cetak.html', ['data' => $data]);
        exit();
    }

    // ============================================================
    // ANS1 - INFORMASI TINDAKAN PEMBIUSAN
    // ============================================================

    public function anyAns1manage()
    {
        // Tangani penghapusan data di dalam method yang sudah terdaftar
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('ans1_tindakan_pembiusan')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'ans1manage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('ans1_tindakan_pembiusan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans1_tindakan_pembiusan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans1_tindakan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->toArray();

        return $this->draw('ans1/manage.html', ['data' => $data]);
    }

    public function getAns1form()
    {
        // AJAX: ambil info pasien untuk bypass blokir hak akses aksi baru
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $no_rawat = trim($no_rawat);
            error_log("AJAX Patient Request: no_rawat received = '$no_rawat'");
            
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
                error_log("Padded no_rawat to: '$no_rawat'");
            }

            $result = ['success' => false];

            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();
                
                error_log("Query reg_periksa result: " . print_r($reg, true));

                if ($reg) {
                    $result = [
                        'success'       => true,
                        'no_rawat'      => $reg['no_rawat'] ?? '',
                        'nama_pasien'   => $reg['nm_pasien'] ?? '',
                        'jenis_kelamin' => $reg['jk'] ?? '',
                        'tanggal_lahir' => $reg['tgl_lahir'] ?? '',
                        'no_rkm_medis'  => $reg['no_rkm_medis'] ?? '',
                    ];
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            error_log("Returning JSON: " . json_encode($result));
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];

        if (!empty($id)) {
            $data = $this->db('ans1_tindakan_pembiusan')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat']) && empty($data['nm_pasien'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->oneArray();
                if ($reg) {
                    $data['nm_pasien']   = $reg['nm_pasien'] ?? '';
                    $data['jk']          = $reg['jk'] ?? '';
                    $data['tgl_lahir']   = $reg['tgl_lahir'] ?? '';
                    $data['no_rkm_medis']= $reg['no_rkm_medis'] ?? '';
                }
            }
        }

        return $this->draw('ans1/form.html', [
            'data'   => $data
        ]);
    }

    public function postAns1save()
    {
        try {
            $post = $_POST;
            $id   = !empty($post['id']) ? (int)$post['id'] : null;

            $data = [
                'no_rawat'                             => $post['no_rawat'] ?? '',
                'tanggal_jam'                          => $post['tanggal_jam'] ?? date('Y-m-d H:i:s'),
                'pemberi_informasi'                    => $post['pemberi_informasi'] ?? '',
                'penerima_informasi_1'                 => $post['penerima_informasi_1'] ?? '',
                'penerima_informasi_2'                 => $post['penerima_informasi_2'] ?? '',
                'status_fisik_asa'                     => $post['status_fisik_asa'] ?? '',
                'dasar_diagnosis_klinis'               => isset($post['dasar_diagnosis_klinis']) ? '1' : '0',
                'dasar_diagnosis_laboratorium'         => isset($post['dasar_diagnosis_laboratorium']) ? '1' : '0',
                'dasar_diagnosis_radiologis'           => isset($post['dasar_diagnosis_radiologis']) ? '1' : '0',
                'dasar_diagnosis_ekg'                  => isset($post['dasar_diagnosis_ekg']) ? '1' : '0',
                'tindakan_umum_intubasi'               => isset($post['tindakan_umum_intubasi']) ? '1' : '0',
                'tindakan_umum_lma'                    => isset($post['tindakan_umum_lma']) ? '1' : '0',
                'tindakan_umum_face_mask'              => isset($post['tindakan_umum_face_mask']) ? '1' : '0',
                'tindakan_umum_tiva'                   => isset($post['tindakan_umum_tiva']) ? '1' : '0',
                'tindakan_regional_spinal'             => isset($post['tindakan_regional_spinal']) ? '1' : '0',
                'tindakan_regional_epidural'           => isset($post['tindakan_regional_epidural']) ? '1' : '0',
                'tindakan_regional_blok_perifer'       => isset($post['tindakan_regional_blok_perifer']) ? '1' : '0',
                'risiko_shock'                         => isset($post['risiko_shock']) ? '1' : '0',
                'risiko_henti_jantung'                 => isset($post['risiko_henti_jantung']) ? '1' : '0',
                'risiko_meninggal'                     => isset($post['risiko_meninggal']) ? '1' : '0',
                'komplikasi_bius_umum'                 => isset($post['komplikasi_bius_umum']) ? '1' : '0',
                'komplikasi_bu_sistem_pernapasan'      => isset($post['komplikasi_bu_sistem_pernapasan']) ? '1' : '0',
                'komplikasi_bu_jantung'                => isset($post['komplikasi_bu_jantung']) ? '1' : '0',
                'komplikasi_bu_sistem_saraf'           => isset($post['komplikasi_bu_sistem_saraf']) ? '1' : '0',
                'komplikasi_bu_tindakan_laringoskopi'  => isset($post['komplikasi_bu_tindakan_laringoskopi']) ? '1' : '0',
                'komplikasi_bu_suhu_tubuh'             => isset($post['komplikasi_bu_suhu_tubuh']) ? '1' : '0',
                'komplikasi_bu_efek_merugikan'         => isset($post['komplikasi_bu_efek_merugikan']) ? '1' : '0',
                'komplikasi_bu_cedera_akibat_posisi'   => isset($post['komplikasi_bu_cedera_akibat_posisi']) ? '1' : '0',
                'komplikasi_bu_muntah'                 => isset($post['komplikasi_bu_muntah']) ? '1' : '0',
                'komplikasi_bu_perut_kembung'          => isset($post['komplikasi_bu_perut_kembung']) ? '1' : '0',
                'komplikasi_bu_tenggorokan_serak'      => isset($post['komplikasi_bu_tenggorokan_serak']) ? '1' : '0',
                'komplikasi_bius_regional'             => isset($post['komplikasi_bius_regional']) ? '1' : '0',
                'komplikasi_br_segera'                 => isset($post['komplikasi_br_segera']) ? '1' : '0',
                'komplikasi_br_penurunan_tekanan_darah'=> isset($post['komplikasi_br_penurunan_tekanan_darah']) ? '1' : '0',
                'komplikasi_br_anestesi_spinal_total'  => isset($post['komplikasi_br_anestesi_spinal_total']) ? '1' : '0',
                'komplikasi_br_reaksi_toksik'          => isset($post['komplikasi_br_reaksi_toksik']) ? '1' : '0',
                'komplikasi_br_reaksi_alergi'          => isset($post['komplikasi_br_reaksi_alergi']) ? '1' : '0',
                'komplikasi_br_lanjutan'               => isset($post['komplikasi_br_lanjutan']) ? '1' : '0',
                'komplikasi_br_nyeri_kepala'           => isset($post['komplikasi_br_nyeri_kepala']) ? '1' : '0',
                'komplikasi_br_nyeri_punggung'         => isset($post['komplikasi_br_nyeri_punggung']) ? '1' : '0',
                'komplikasi_br_tidak_bisa_berkemih'    => isset($post['komplikasi_br_tidak_bisa_berkemih']) ? '1' : '0',
                'komplikasi_br_infeksi'                => isset($post['komplikasi_br_infeksi']) ? '1' : '0',
                'komplikasi_br_cedera_saraf'           => isset($post['komplikasi_br_cedera_saraf']) ? '1' : '0',
                'komplikasi_br_pendarahan'             => isset($post['komplikasi_br_pendarahan']) ? '1' : '0',
                'tata_cara_tindakan'                   => $post['tata_cara_tindakan'] ?? 'Persiapan alat dan obat, pemeriksaan sebelum operasi, tindakan anastesi',
                'indikasi_dan_tujuan'                  => $post['indikasi_dan_tujuan'] ?? 'Memfasilitasi Operasi, menghilangkan rasa sakit saat operasi',
                'prognosis'                            => $post['prognosis'] ?? '',
                'alternatif_tindakan'                  => $post['alternatif_tindakan'] ?? '',
                'lain_lain'                            => $post['lain_lain'] ?? '',
                'tandai_1'                             => isset($post['tandai_1']) ? '1' : '0',
                'tandai_2'                             => isset($post['tandai_2']) ? '1' : '0',
                'tandai_3'                             => isset($post['tandai_3']) ? '1' : '0',
                'tandai_4'                             => isset($post['tandai_4']) ? '1' : '0',
                'tandai_5'                             => isset($post['tandai_5']) ? '1' : '0',
                'tandai_6'                             => isset($post['tandai_6']) ? '1' : '0',
                'tandai_7'                             => isset($post['tandai_7']) ? '1' : '0',
                'tandai_8'                             => isset($post['tandai_8']) ? '1' : '0',
                'tandai_9'                             => isset($post['tandai_9']) ? '1' : '0',
                'tandai_10'                            => isset($post['tandai_10']) ? '1' : '0'
            ];

            if ($id) {
                $this->db('ans1_tindakan_pembiusan')->where('id', $id)->save($data);
                $this->notify('success', 'Data berhasil diupdate');
            } else {
                $this->db('ans1_tindakan_pembiusan')->save($data);
                $this->notify('success', 'Data berhasil disimpan');
            }

            redirect(url([ADMIN, 'update_bmt', 'ans1manage']));
        } catch (\Exception $e) {
            die("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    public function anyAns1hapus()
    {
        $id = $_REQUEST['id'] ?? '';
        if ($id) {
            $this->db('ans1_tindakan_pembiusan')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'ans1manage']));
    }

    public function getAns1cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans1_tindakan_pembiusan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans1_tindakan_pembiusan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans1_tindakan_pembiusan.id', $id)
            ->select('ans1_tindakan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        if (!empty($data['tgl_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        }

        echo $this->draw('ans1/cetak.html', ['data' => $data]);
        exit();
    }

    // ==========================================
    // ANS2 - PERSETUJUAN TINDAKAN PEMBIUSAN
    // ==========================================

    public function anyAns2manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('ans2_persetujuan_pembiusan')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'ans2manage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('ans2_persetujuan_pembiusan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans2_persetujuan_pembiusan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans2_persetujuan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->toArray();

        return $this->draw('ans2/manage.html', ['data' => $data]);
    }

    public function getAns2form()
    {
        // AJAX: ambil info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $no_rawat = trim($no_rawat);
            
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];

            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat, pasien.umur')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = [
                        'success'       => true,
                        'no_rawat'      => $reg['no_rawat'] ?? '',
                        'nama_pasien'   => $reg['nm_pasien'] ?? '',
                        'jenis_kelamin' => $reg['jk'] ?? '',
                        'tanggal_lahir' => $reg['tgl_lahir'] ?? '',
                        'no_rkm_medis'  => $reg['no_rkm_medis'] ?? '',
                        'alamat'        => $reg['alamat'] ?? '',
                        'umur'          => $reg['umur'] ?? ''
                    ];
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];

        if (!empty($id)) {
            $data = $this->db('ans2_persetujuan_pembiusan')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat']) && empty($data['nm_pasien'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->oneArray();

                if ($reg) {
                    $data['nm_pasien'] = $reg['nm_pasien'];
                }
            }
        }

        $this->tpl->set('data', $data);
        return $this->draw('ans2/form.html', ['data' => $data]);
    }

    public function postAns2save()
    {
        try {
            $post = $_POST;
            $id = $post['id'] ?? '';

            // Handle date conversion if needed, but input datetime-local usually returns Y-m-d\TH:i format.
            // MySQL expects Y-m-d H:i:s.
            $tanggal_jam = date('Y-m-d H:i:s', strtotime($post['tanggal_jam']));

            $data = [
                'no_rawat'            => $post['no_rawat'] ?? '',
                'jenis_pernyataan'    => $post['jenis_pernyataan'] ?? 'PERSETUJUAN',
                'pihak_nama'          => $post['pihak_nama'] ?? '',
                'pihak_umur'          => $post['pihak_umur'] ?? '',
                'pihak_jk'            => $post['pihak_jk'] ?? 'L',
                'pihak_alamat'        => $post['pihak_alamat'] ?? '',
                'pihak_sebagai'       => $post['pihak_sebagai'] ?? 'Pasien',
                'pasien_nama_isian'   => $post['pasien_nama_isian'] ?? '',
                'pasien_umur_isian'   => $post['pasien_umur_isian'] ?? '',
                'pasien_jk_isian'     => $post['pasien_jk_isian'] ?? 'L',
                'pasien_alamat_isian' => $post['pasien_alamat_isian'] ?? '',
                'alasan_penolakan'    => $post['alasan_penolakan'] ?? '',
                'tanggal_jam'         => $tanggal_jam,
                'saksi_nama'          => $post['saksi_nama'] ?? '',
                'pihak_rs_nama'       => $post['pihak_rs_nama'] ?? ''
            ];

            if ($id) {
                $this->db('ans2_persetujuan_pembiusan')->where('id', $id)->save($data);
                $this->notify('success', 'Data berhasil diupdate');
            } else {
                $this->db('ans2_persetujuan_pembiusan')->save($data);
                $this->notify('success', 'Data berhasil disimpan');
            }

            redirect(url([ADMIN, 'update_bmt', 'ans2manage']));
        } catch (\Exception $e) {
            die("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    public function getAns2cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans2_persetujuan_pembiusan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans2_persetujuan_pembiusan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans2_persetujuan_pembiusan.id', $id)
            ->select('ans2_persetujuan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        if (!empty($data['tgl_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        }
        
        if (!empty($data['tanggal_jam'])) {
            $tgljam = explode(' ', $data['tanggal_jam']);
            if (count($tgljam) == 2) {
                $tgl = explode('-', $tgljam[0]);
                if (count($tgl) == 3) {
                    $data['tanggal_jam_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
                }
                $jam = explode(':', $tgljam[1]);
                if (count($jam) >= 2) {
                    $data['jam_indo'] = $jam[0] . ':' . $jam[1];
                }
            }
        }

        echo $this->draw('ans2/cetak.html', ['data' => $data]);
        exit();
    }

    // ==========================================
    // ANS3 - ASSESMEN PRASEDASI / ANESTESI
    // ==========================================

    public function anyAns3manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('ans3_assesmen_prasedasi_anestesi')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'ans3manage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('ans3_assesmen_prasedasi_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans3_assesmen_prasedasi_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans3_assesmen_prasedasi_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->toArray();

        return $this->draw('ans3/manage.html', ['data' => $data]);
    }

    public function getAns3form()
    {
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $no_rawat = trim($no_rawat);
            
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];

            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = [
                        'success'       => true,
                        'no_rawat'      => $reg['no_rawat'] ?? '',
                        'nama_pasien'   => $reg['nm_pasien'] ?? '',
                        'jenis_kelamin' => $reg['jk'] ?? '',
                        'tanggal_lahir' => $reg['tgl_lahir'] ?? '',
                        'no_rkm_medis'  => $reg['no_rkm_medis'] ?? '',
                        'umur'          => $reg['umur'] ?? ''
                    ];
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];

        if (!empty($id)) {
            $data = $this->db('ans3_assesmen_prasedasi_anestesi')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
                    ->oneArray();

                if ($reg) {
                    $data['nm_pasien'] = $reg['nm_pasien'];
                    $data['umur_pasien'] = $reg['umur'];
                    $data['jk_pasien'] = $reg['jk'];
                    $data['tgl_lahir'] = $reg['tgl_lahir'];
                }
            }
        }

        $this->tpl->set('data', $data);
        return $this->draw('ans3/form.html', ['data' => $data]);
    }

    public function postAns3save()
    {
        try {
            $post = $_POST;
            $id = $post['id'] ?? '';

            $data = [
                'no_rawat' => $post['no_rawat'] ?? '',
                'diagnosa_prabedah' => $post['diagnosa_prabedah'] ?? '',
                'rencana_tindakan' => $post['rencana_tindakan'] ?? '',
                'tipe_tindakan' => $post['tipe_tindakan'] ?? '',
                'anamnese_dari' => $post['anamnese_dari'] ?? '',
                'riwayat_anestesi' => $post['riwayat_anestesi'] ?? '',
                'riwayat_anestesi_ket' => $post['riwayat_anestesi_ket'] ?? '',
                'komplikasi' => $post['komplikasi'] ?? '',
                'obat_sedang_dikonsumsi' => $post['obat_sedang_dikonsumsi'] ?? '',
                'riwayat_alergi' => $post['riwayat_alergi'] ?? '',
                'riwayat_alergi_ket' => $post['riwayat_alergi_ket'] ?? '',
                
                'bb' => $post['bb'] ?? '',
                'tb' => $post['tb'] ?? '',
                'tanda_vital_td' => $post['tanda_vital_td'] ?? '',
                'tanda_vital_nadi' => $post['tanda_vital_nadi'] ?? '',
                'tanda_vital_rr' => $post['tanda_vital_rr'] ?? '',
                'tanda_vital_suhu' => $post['tanda_vital_suhu'] ?? '',
                'tanda_vital_vas' => $post['tanda_vital_vas'] ?? '',
                
                'bebas' => $post['bebas'] ?? '',
                'protusi_mandibula' => $post['protusi_mandibula'] ?? '',
                'jarak_mentohyoid' => $post['jarak_mentohyoid'] ?? '',
                'jarak_mentohyoid_ket' => $post['jarak_mentohyoid_ket'] ?? '',
                'jarak_thyrohyoid' => $post['jarak_thyrohyoid'] ?? '',
                'jarak_thyrohyoid_ket' => $post['jarak_thyrohyoid_ket'] ?? '',
                'buka_mulut' => $post['buka_mulut'] ?? '',
                'mallampathy' => $post['mallampathy'] ?? '',
                'leher' => $post['leher'] ?? '',
                'gerak_leher' => $post['gerak_leher'] ?? '',
                'obesitas' => $post['obesitas'] ?? '',
                'massa' => $post['massa'] ?? '',
                'gigi_palsu' => $post['gigi_palsu'] ?? '',
                'sulit_ventilasi' => $post['sulit_ventilasi'] ?? '',
                
                'pernafasan_dbn' => $post['pernafasan_dbn'] ?? '',
                'pernafasan_asma' => $post['pernafasan_asma'] ?? '',
                'pernafasan_pneumonia' => $post['pernafasan_pneumonia'] ?? '',
                'pernafasan_ispa' => $post['pernafasan_ispa'] ?? '',
                'pernafasan_tuberkulosis' => $post['pernafasan_tuberkulosis'] ?? '',
                'pernafasan_lainnya' => $post['pernafasan_lainnya'] ?? '',
                'pernafasan_keterangan' => $post['pernafasan_keterangan'] ?? '',
                
                'kardiovaskular_dbn' => $post['kardiovaskular_dbn'] ?? '',
                'kardiovaskular_ekg_abnormal' => $post['kardiovaskular_ekg_abnormal'] ?? '',
                'kardiovaskular_hipertensi' => $post['kardiovaskular_hipertensi'] ?? '',
                'kardiovaskular_disritma' => $post['kardiovaskular_disritma'] ?? '',
                'kardiovaskular_murmur' => $post['kardiovaskular_murmur'] ?? '',
                'kardiovaskular_angina' => $post['kardiovaskular_angina'] ?? '',
                'kardiovaskular_pacemaker' => $post['kardiovaskular_pacemaker'] ?? '',
                'kardiovaskular_chf' => $post['kardiovaskular_chf'] ?? '',
                'kardiovaskular_penyakit_katup' => $post['kardiovaskular_penyakit_katup'] ?? '',
                'kardiovaskular_lainnya' => $post['kardiovaskular_lainnya'] ?? '',
                'kardiovaskular_keterangan' => $post['kardiovaskular_keterangan'] ?? '',
                
                'neuro_dbn' => $post['neuro_dbn'] ?? '',
                'neuro_sakit_kepala' => $post['neuro_sakit_kepala'] ?? '',
                'neuro_penurunan_kesadaran' => $post['neuro_penurunan_kesadaran'] ?? '',
                'neuro_distropi' => $post['neuro_distropi'] ?? '',
                'neuro_parese' => $post['neuro_parese'] ?? '',
                'neuro_parastesia' => $post['neuro_parastesia'] ?? '',
                'neuro_plegi' => $post['neuro_plegi'] ?? '',
                'neuro_kejang' => $post['neuro_kejang'] ?? '',
                'neuro_lainnya' => $post['neuro_lainnya'] ?? '',
                'neuro_keterangan' => $post['neuro_keterangan'] ?? '',
                
                'renal_endokrin_dbn' => $post['renal_endokrin_dbn'] ?? '',
                'renal_diabetes' => $post['renal_diabetes'] ?? '',
                'renal_tiroid' => $post['renal_tiroid'] ?? '',
                'renal_gagal_ginjal' => $post['renal_gagal_ginjal'] ?? '',
                'renal_lainnya' => $post['renal_lainnya'] ?? '',
                'renal_keterangan' => $post['renal_keterangan'] ?? '',
                
                'hepato_dbn' => $post['hepato_dbn'] ?? '',
                'hepato_sirosis' => $post['hepato_sirosis'] ?? '',
                'hepato_hepatitis' => $post['hepato_hepatitis'] ?? '',
                'hepato_obstruksi' => $post['hepato_obstruksi'] ?? '',
                'hepato_ikterus' => $post['hepato_ikterus'] ?? '',
                'hepato_mual_muntah' => $post['hepato_mual_muntah'] ?? '',
                'hepato_lainnya' => $post['hepato_lainnya'] ?? '',
                'hepato_keterangan' => $post['hepato_keterangan'] ?? '',
                
                'lainnya_organ_dbn' => $post['lainnya_organ_dbn'] ?? '',
                'lainnya_neonat' => $post['lainnya_neonat'] ?? '',
                'lainnya_geriatri' => $post['lainnya_geriatri'] ?? '',
                'lainnya_hamil' => $post['lainnya_hamil'] ?? '',
                'lainnya_kanker' => $post['lainnya_kanker'] ?? '',
                'lainnya_anemia' => $post['lainnya_anemia'] ?? '',
                'lainnya_dehidrasi' => $post['lainnya_dehidrasi'] ?? '',
                'lainnya_merokok' => $post['lainnya_merokok'] ?? '',
                'lainnya_alkohol' => $post['lainnya_alkohol'] ?? '',
                'lainnya_pendarahan' => $post['lainnya_pendarahan'] ?? '',
                'lainnya_lainnya' => $post['lainnya_lainnya'] ?? '',
                'lainnya_organ_keterangan' => $post['lainnya_organ_keterangan'] ?? '',
                
                'lab_hb' => $post['lab_hb'] ?? '',
                'lab_hct' => $post['lab_hct'] ?? '',
                'lab_ct' => $post['lab_ct'] ?? '',
                'lab_pt' => $post['lab_pt'] ?? '',
                'lab_leukosit' => $post['lab_leukosit'] ?? '',
                'lab_trombosit' => $post['lab_trombosit'] ?? '',
                'lab_bt' => $post['lab_bt'] ?? '',
                'lab_aptt' => $post['lab_aptt'] ?? '',
                'lab_ureum' => $post['lab_ureum'] ?? '',
                'lab_creatinin' => $post['lab_creatinin'] ?? '',
                'lab_sgot' => $post['lab_sgot'] ?? '',
                'lab_sgpt' => $post['lab_sgpt'] ?? '',
                'lab_albumin' => $post['lab_albumin'] ?? '',
                'lab_globulin' => $post['lab_globulin'] ?? '',
                'lab_bilirubin_direct' => $post['lab_bilirubin_direct'] ?? '',
                'lab_bilirubin_indirect' => $post['lab_bilirubin_indirect'] ?? '',
                'lab_na' => $post['lab_na'] ?? '',
                'lab_k' => $post['lab_k'] ?? '',
                'lab_cl' => $post['lab_cl'] ?? '',
                'lab_gds' => $post['lab_gds'] ?? '',
                'lab_t3' => $post['lab_t3'] ?? '',
                'lab_tsh' => $post['lab_tsh'] ?? '',
                'lab_t4' => $post['lab_t4'] ?? '',
                'lab_pco2' => $post['lab_pco2'] ?? '',
                'lab_be' => $post['lab_be'] ?? '',
                'lab_po2' => $post['lab_po2'] ?? '',
                'lab_sao2' => $post['lab_sao2'] ?? '',
                'lab_lainlain' => $post['lab_lainlain'] ?? '',
                
                'penunjang_ekg' => $post['penunjang_ekg'] ?? '',
                'penunjang_radiologi' => $post['penunjang_radiologi'] ?? '',
                'penunjang_lainlain' => $post['penunjang_lainlain'] ?? '',
                'keterangan_lain' => $post['keterangan_lain'] ?? '',
                
                'ps_asa' => $post['ps_asa'] ?? '',
                'rencana_sedasi' => $post['rencana_sedasi'] ?? '',
                
                'instruksi_puasa' => $post['instruksi_puasa'] ?? '',
                'instruksi_puasa_jam' => $post['instruksi_puasa_jam'] ?? '',
                'instruksi_persiapan_darah' => $post['instruksi_persiapan_darah'] ?? '',
                'instruksi_persiapan_darah_cc' => $post['instruksi_persiapan_darah_cc'] ?? '',
                'instruksi_obat_dihentikan' => $post['instruksi_obat_dihentikan'] ?? '',
                'instruksi_lain1' => $post['instruksi_lain1'] ?? '',
                'instruksi_lain2' => $post['instruksi_lain2'] ?? '',
                'instruksi_lain3' => $post['instruksi_lain3'] ?? '',
                'instruksi_lain4' => $post['instruksi_lain4'] ?? '',
                'instruksi_lain5' => $post['instruksi_lain5'] ?? '',
                
                'tanggal' => $post['tanggal'] ?? '',
                'jam' => $post['jam'] ?? '',
                'dokter_nama' => $post['dokter_nama'] ?? ''
            ];

            foreach ($data as $key => $val) {
                if ($val === '') {
                    $data[$key] = null;
                }
            }

            if ($id) {
                $this->db('ans3_assesmen_prasedasi_anestesi')->where('id', $id)->save($data);
                $this->notify('success', 'Data berhasil diupdate');
            } else {
                $this->db('ans3_assesmen_prasedasi_anestesi')->save($data);
                $this->notify('success', 'Data berhasil disimpan');
            }

            redirect(url([ADMIN, 'update_bmt', 'ans3manage']));
        } catch (\Exception $e) {
            die("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    public function getAns3cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans3_assesmen_prasedasi_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans3_assesmen_prasedasi_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans3_assesmen_prasedasi_anestesi.id', $id)
            ->select('ans3_assesmen_prasedasi_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        if (!empty($data['tgl_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }
        
        if (!empty($data['tanggal'])) {
            $tgl = explode('-', $data['tanggal']);
            if (count($tgl) == 3) {
                $data['tanggal_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }

        echo $this->draw('ans3/cetak.html', ['data' => $data]);
        exit();
    }
}

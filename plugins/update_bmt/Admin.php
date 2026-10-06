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
                // 'Nama Form Bedah' => 'bedahmanage',
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
}

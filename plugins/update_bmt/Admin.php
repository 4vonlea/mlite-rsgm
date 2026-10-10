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
                'ANS4 - RENCANA ANESTESI' => 'ans4manage',
                'LAMPIRAN - SURAT PERNYATAAN PULANG APS' => 'pulangapsmanage',
            ],
            'Ranap' => [
                'RM.RI 01B - HAK DAN KEWAJIBAN PASIEN' => 'ri01bmanage',
                'RM.RI 02 - RINGKASAN MASUK DAN KELUAR' => 'ri02manage',
                'RM.RI 03 - SERAH TERIMA PASIEN' => 'ri03manage',
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

    // ==========================================
    // ANS4 - RENCANA ANESTESI
    // ==========================================

    public function anyAns4manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('ans4_rencana_anestesi')->where('id', $id)->delete();
            $this->db('ans4_evaluasi_premedikasi_detail')->where('id_ans4', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'ans4manage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('ans4_rencana_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans4_rencana_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans4_rencana_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->toArray();

        return $this->draw('ans4/manage.html', ['data' => $data]);
    }

    public function getAns4form()
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
        $detail = [];

        if (!empty($id)) {
            $data = $this->db('ans4_rencana_anestesi')->where('id', $id)->oneArray();
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
            $detail = $this->db('ans4_evaluasi_premedikasi_detail')->where('id_ans4', $id)->toArray();
        }

        $this->tpl->set('data', $data);
        $this->tpl->set('detail', $detail);
        return $this->draw('ans4/form.html', ['data' => $data, 'detail' => $detail]);
    }

    public function postAns4save()
    {
        try {
            $post = $_POST;
            $id = $post['id'] ?? '';
            $data = $post;
            unset($data['id']);
            unset($data['nm_pasien']);
            unset($data['detail_obat']);
            unset($data['detail_dosis']);
            unset($data['detail_jam']);
            unset($data['detail_pelaksana']);

            foreach ($data as $key => $val) {
                if ($val === '') {
                    $data[$key] = null;
                }
            }

            if ($id) {
                $this->db('ans4_rencana_anestesi')->where('id', $id)->save($data);
                $this->notify('success', 'Data berhasil diupdate');
            } else {
                $this->db('ans4_rencana_anestesi')->save($data);
                $inserted = $this->db('ans4_rencana_anestesi')->where('no_rawat', $data['no_rawat'])->desc('id')->oneArray();
                $id = $inserted['id'] ?? null;
                $this->notify('success', 'Data berhasil disimpan');
            }

            if ($id) {
                $this->db('ans4_evaluasi_premedikasi_detail')->where('id_ans4', $id)->delete();
                $obat = $post['detail_obat'] ?? [];
                $dosis = $post['detail_dosis'] ?? [];
                $jam = $post['detail_jam'] ?? [];
                $pelaksana = $post['detail_pelaksana'] ?? [];
                
                foreach ($obat as $k => $v) {
                    if (!empty($v) || !empty($dosis[$k])) {
                        $this->db('ans4_evaluasi_premedikasi_detail')->save([
                            'id_ans4' => $id,
                            'obat_premedikasi' => $v,
                            'dosis' => $dosis[$k] ?? '',
                            'jam' => $jam[$k] ?? '',
                            'pelaksana' => $pelaksana[$k] ?? ''
                        ]);
                    }
                }
            }

            redirect(url([ADMIN, 'update_bmt', 'ans4manage']));
        } catch (\Exception $e) {
            die("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    public function getAns4cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans4_rencana_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans4_rencana_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans4_rencana_anestesi.id', $id)
            ->select('ans4_rencana_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $detail = $this->db('ans4_evaluasi_premedikasi_detail')->where('id_ans4', $id)->toArray();

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

        echo $this->draw('ans4/cetak.html', ['data' => $data, 'detail' => $detail]);
        exit();
    }
    public function getAns6manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('ans6_status_post_anestesi')
            ->join('reg_periksa', 'reg_periksa.no_rawat = ans6_status_post_anestesi.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans6_status_post_anestesi.*, pasien.nm_pasien')
            ->desc('ans6_status_post_anestesi.id')
            ->toArray();
            
        return $this->draw('ans6/manage.html', ['list' => $query]);
    }

    public function getAns6form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        $detail = [];
        
        // Handle ajax request for patient data
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.nm_pasien')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('ans6_status_post_anestesi')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans6_status_post_anestesi.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('ans6_status_post_anestesi.id', $id)
                ->select('ans6_status_post_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
                
            $detail = $this->db('ans6_protokol_nyeri_detail')->where('id_ans6', $id)->toArray();
        }

        return $this->draw('ans6/form.html', ['data' => $data, 'detail' => $detail]);
    }

    public function postAns6save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        $detail_tanggal = $data['detail_tanggal'] ?? [];
        $detail_obat = $data['detail_obat'] ?? [];
        $detail_ttd = $data['detail_ttd'] ?? [];
        
        unset($data['id']);
        unset($data['nm_pasien']);
        unset($data['detail_tanggal']);
        unset($data['detail_obat']);
        unset($data['detail_ttd']);
        
        if (empty($data['tanggal'])) {
            $data['tanggal'] = date('Y-m-d');
        }

        if ($id) {
            $this->db('ans6_status_post_anestesi')->where('id', $id)->save($data);
            $this->db('ans6_protokol_nyeri_detail')->where('id_ans6', $id)->delete();
        } else {
            $query = $this->db('ans6_status_post_anestesi')->save($data);
            $id = $this->db()->lastInsertId();
        }
        
        if (!empty($detail_obat)) {
            foreach ($detail_obat as $key => $val) {
                if (!empty($val)) {
                    $this->db('ans6_protokol_nyeri_detail')->save([
                        'id_ans6' => $id,
                        'tanggal' => $detail_tanggal[$key] ?? '',
                        'obat' => $val,
                        'tanda_tangan_dokter' => $detail_ttd[$key] ?? ''
                    ]);
                }
            }
        }

        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'ans6manage']));
    }

    public function getAns6hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('ans6_status_post_anestesi')->where('id', $id)->delete();
            $this->db('ans6_protokol_nyeri_detail')->where('id_ans6', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'ans6manage']));
    }

    public function getAns6cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans6_status_post_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans6_status_post_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans6_status_post_anestesi.id', $id)
            ->select('ans6_status_post_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $detail = $this->db('ans6_protokol_nyeri_detail')->where('id_ans6', $id)->toArray();

        echo $this->draw('ans6/cetak.html', ['data' => $data, 'detail' => $detail]);
        exit();
    }
    public function getAns7manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('ans7_serah_terima')
            ->join('reg_periksa', 'reg_periksa.no_rawat = ans7_serah_terima.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ans7_serah_terima.*, pasien.nm_pasien')
            ->desc('ans7_serah_terima.id')
            ->toArray();
            
        return $this->draw('ans7/manage.html', ['list' => $query]);
    }

    public function getAns7form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.nm_pasien')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('ans7_serah_terima')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans7_serah_terima.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('ans7_serah_terima.id', $id)
                ->select('ans7_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
        }

        return $this->draw('ans7/form.html', ['data' => $data]);
    }

    public function postAns7save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']);
        
        if ($id) {
            $this->db('ans7_serah_terima')->where('id', $id)->save($data);
        } else {
            $this->db('ans7_serah_terima')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'ans7manage']));
    }

    public function getAns7hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('ans7_serah_terima')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'ans7manage']));
    }

    public function getAns7cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans7_serah_terima')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans7_serah_terima.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ans7_serah_terima.id', $id)
            ->select('ans7_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        echo $this->draw('ans7/cetak.html', ['data' => $data]);
        exit();
    }
    public function getBm1manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('bm1_informasi_tindakan')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bm1_informasi_tindakan.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bm1_informasi_tindakan.*, pasien.nm_pasien')
            ->desc('bm1_informasi_tindakan.id')
            ->toArray();
            
        return $this->draw('bm1/manage.html', ['list' => $query]);
    }

    public function getBm1form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.nm_pasien')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('bm1_informasi_tindakan')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm1_informasi_tindakan.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('bm1_informasi_tindakan.id', $id)
                ->select('bm1_informasi_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
        }

        return $this->draw('bm1/form.html', ['data' => $data]);
    }

    public function postBm1save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']);
        
        if ($id) {
            $this->db('bm1_informasi_tindakan')->where('id', $id)->save($data);
        } else {
            $this->db('bm1_informasi_tindakan')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'bm1manage']));
    }

    public function getBm1hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('bm1_informasi_tindakan')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bm1manage']));
    }

    public function getBm1cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bm1_informasi_tindakan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm1_informasi_tindakan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('bm1_informasi_tindakan.id', $id)
            ->select('bm1_informasi_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_dokumen'])) {
            $tgl = explode('-', $data['tgl_dokumen']);
            if (count($tgl) == 3) {
                $data['tgl_dokumen_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }
        
        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        echo $this->draw('bm1/cetak.html', ['data' => $data]);
        exit();
    }
    public function getBm2manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('bm2_persetujuan_tindakan')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bm2_persetujuan_tindakan.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bm2_persetujuan_tindakan.*, pasien.nm_pasien')
            ->desc('bm2_persetujuan_tindakan.id')
            ->toArray();
            
        return $this->draw('bm2/manage.html', ['list' => $query]);
    }

    public function getBm2form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.*')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien'], 'umur' => $patient['umur'], 'jk' => $patient['jk'], 'alamat' => $patient['alamat']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('bm2_persetujuan_tindakan')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm2_persetujuan_tindakan.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('bm2_persetujuan_tindakan.id', $id)
                ->select('bm2_persetujuan_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
        }

        return $this->draw('bm2/form.html', ['data' => $data]);
    }

    public function postBm2save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']); // we only save nama_pasien_form
        
        if ($id) {
            $this->db('bm2_persetujuan_tindakan')->where('id', $id)->save($data);
        } else {
            $this->db('bm2_persetujuan_tindakan')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'bm2manage']));
    }

    public function getBm2hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('bm2_persetujuan_tindakan')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bm2manage']));
    }

    public function getBm2cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bm2_persetujuan_tindakan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm2_persetujuan_tindakan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('bm2_persetujuan_tindakan.id', $id)
            ->select('bm2_persetujuan_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_dokumen'])) {
            $tgl = explode('-', $data['tgl_dokumen']);
            if (count($tgl) == 3) {
                $data['tgl_dokumen_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }
        
        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        echo $this->draw('bm2/cetak.html', ['data' => $data]);
        exit();
    }
    public function getBm4manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('bm4_laporan_pembedahan')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bm4_laporan_pembedahan.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bm4_laporan_pembedahan.*, pasien.nm_pasien')
            ->desc('bm4_laporan_pembedahan.id')
            ->toArray();
            
        return $this->draw('bm4/manage.html', ['list' => $query]);
    }

    public function getBm4form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.*')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('bm4_laporan_pembedahan')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm4_laporan_pembedahan.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('bm4_laporan_pembedahan.id', $id)
                ->select('bm4_laporan_pembedahan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
        }

        return $this->draw('bm4/form.html', ['data' => $data]);
    }

    public function postBm4save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']);
        
        if ($id) {
            $this->db('bm4_laporan_pembedahan')->where('id', $id)->save($data);
        } else {
            $this->db('bm4_laporan_pembedahan')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'bm4manage']));
    }

    public function getBm4hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('bm4_laporan_pembedahan')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bm4manage']));
    }

    public function getBm4cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bm4_laporan_pembedahan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm4_laporan_pembedahan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('bm4_laporan_pembedahan.id', $id)
            ->select('bm4_laporan_pembedahan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }
        
        if (!empty($data['tgl_pembedahan'])) {
            $tgl = explode('-', $data['tgl_pembedahan']);
            if (count($tgl) == 3) {
                $data['tgl_pembedahan_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }

        echo $this->draw('bm4/cetak.html', ['data' => $data]);
        exit();
    }
    public function getBmi1manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('bmi1_persetujuan_anestesi_lokal')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bmi1_persetujuan_anestesi_lokal.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bmi1_persetujuan_anestesi_lokal.*, pasien.nm_pasien')
            ->desc('bmi1_persetujuan_anestesi_lokal.id')
            ->toArray();
            
        return $this->draw('bmi1/manage.html', ['list' => $query]);
    }

    public function getBmi1form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.*')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien'], 'umur' => $patient['umur'], 'jk' => $patient['jk'], 'alamat' => $patient['alamat']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('bmi1_persetujuan_anestesi_lokal')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi1_persetujuan_anestesi_lokal.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('bmi1_persetujuan_anestesi_lokal.id', $id)
                ->select('bmi1_persetujuan_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk as jk_db, pasien.umur as umur_db')
                ->oneArray();
        }

        return $this->draw('bmi1/form.html', ['data' => $data]);
    }

    public function postBmi1save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']);
        
        if ($id) {
            $this->db('bmi1_persetujuan_anestesi_lokal')->where('id', $id)->save($data);
        } else {
            $this->db('bmi1_persetujuan_anestesi_lokal')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'bmi1manage']));
    }

    public function getBmi1hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('bmi1_persetujuan_anestesi_lokal')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bmi1manage']));
    }

    public function getBmi1cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bmi1_persetujuan_anestesi_lokal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi1_persetujuan_anestesi_lokal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('bmi1_persetujuan_anestesi_lokal.id', $id)
            ->select('bmi1_persetujuan_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk as jk_db, pasien.tgl_lahir, pasien.umur as umur_db')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_dokumen'])) {
            $tgl = explode('-', $data['tgl_dokumen']);
            if (count($tgl) == 3) {
                $data['tgl_dokumen_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }
        
        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        echo $this->draw('bmi1/cetak.html', ['data' => $data]);
        exit();
    }
    public function getBmi4manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));
        
        $query = $this->db('bmi4_pra_anestesi_lokal')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bmi4_pra_anestesi_lokal.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bmi4_pra_anestesi_lokal.*, pasien.nm_pasien')
            ->desc('bmi4_pra_anestesi_lokal.id')
            ->toArray();
            
        return $this->draw('bmi4/manage.html', ['list' => $query]);
    }

    public function getBmi4form()
    {
        $id = $_GET['id'] ?? '';
        $data = [];
        
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('pasien.*')
                ->oneArray();
                
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode(['success' => true, 'no_rawat' => $_GET['no_rawat'], 'nama_pasien' => $patient['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        if ($id) {
            $data = $this->db('bmi4_pra_anestesi_lokal')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi4_pra_anestesi_lokal.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('bmi4_pra_anestesi_lokal.id', $id)
                ->select('bmi4_pra_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
                ->oneArray();
        }

        return $this->draw('bmi4/form.html', ['data' => $data]);
    }

    public function postBmi4save()
    {
        $id = $_POST['id'] ?? '';
        $data = $_POST;
        
        unset($data['id']);
        unset($data['nm_pasien']);
        
        if ($id) {
            $this->db('bmi4_pra_anestesi_lokal')->where('id', $id)->save($data);
        } else {
            $this->db('bmi4_pra_anestesi_lokal')->save($data);
        }
        
        $this->notify('success', 'Simpan berhasil');
        redirect(url([ADMIN, 'update_bmt', 'bmi4manage']));
    }

    public function getBmi4hapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('bmi4_pra_anestesi_lokal')->where('id', $id)->delete();
            $this->notify('success', 'Hapus berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bmi4manage']));
    }

    public function getBmi4cetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bmi4_pra_anestesi_lokal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi4_pra_anestesi_lokal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('bmi4_pra_anestesi_lokal.id', $id)
            ->select('bmi4_pra_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }
        
        if (!empty($data['tanggal_tindakan'])) {
            $tgl = explode('-', $data['tanggal_tindakan']);
            if (count($tgl) == 3) {
                $data['tgl_tindakan_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }

        echo $this->draw('bmi4/cetak.html', ['data' => $data]);
        exit();
    }
    
    // --- BMI5 ---
    public function getBmi5manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));

        $list = $this->db('bmi5_bedah_minor')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bmi5_bedah_minor.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bmi5_bedah_minor.*, pasien.nm_pasien')
            ->desc('bmi5_bedah_minor.id')
            ->toArray();

        $this->tpl->set('list', $list);
        return $this->draw('bmi5/manage.html');
    }

    public function getBmi5form()
    {
        if (isset($_GET['ajax_patient'])) {
            header('Content-Type: application/json');
            $no_rawat = $_GET['no_rawat'];
            $pasien = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('pasien.*')
                ->oneArray();
                
            if ($pasien) {
                echo json_encode(['success' => true, 'nama_pasien' => $pasien['nm_pasien']]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }

        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $data = [];
        if ($id) {
            $data = $this->db('bmi5_bedah_minor')
                ->join('reg_periksa', 'reg_periksa.no_rawat = bmi5_bedah_minor.no_rawat')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->select('bmi5_bedah_minor.*, pasien.nm_pasien')
                ->where('bmi5_bedah_minor.id', $id)
                ->oneArray();
        }
        $this->tpl->set('data', $data);
        return $this->draw('bmi5/form.html');
    }

    public function postBmi5save()
    {
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $data = $_POST;
        unset($data['id']);
        unset($data['nm_pasien']);

        if ($id) {
            $this->db('bmi5_bedah_minor')->where('id', $id)->save($data);
            $this->notify('success', 'Ubah data berhasil');
        } else {
            $this->db('bmi5_bedah_minor')->save($data);
            $this->notify('success', 'Simpan data berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bmi5manage']));
    }

    public function getBmi5hapus()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if ($id) {
            $this->db('bmi5_bedah_minor')->where('id', $id)->delete();
            $this->notify('success', 'Hapus data berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'bmi5manage']));
    }

    public function getBmi5cetak()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $data = $this->db('bmi5_bedah_minor')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bmi5_bedah_minor.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('bmi5_bedah_minor.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, reg_periksa.no_rkm_medis')
            ->where('bmi5_bedah_minor.id', $id)
            ->oneArray();
            
        $data['no_rkm_medis'] = $data['no_rkm_medis'] ?? '';

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }
        
        if (!empty($data['tanggal_pembedahan'])) {
            $tgl = explode('-', $data['tanggal_pembedahan']);
            if (count($tgl) == 3) {
                $data['tgl_pembedahan_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }
        
        if (!empty($data['waktu_mulai'])) {
            $data['waktu_mulai_format'] = date('H:i', strtotime($data['waktu_mulai']));
            $data['waktu_mulai_tgl'] = date('d-m-Y', strtotime($data['waktu_mulai']));
        }
        if (!empty($data['waktu_selesai'])) {
            $data['waktu_selesai_format'] = date('H:i', strtotime($data['waktu_selesai']));
            $data['waktu_selesai_tgl'] = date('d-m-Y', strtotime($data['waktu_selesai']));
        }

        echo $this->draw('bmi5/cetak.html', ['data' => $data]);
        exit();
    }

    // ============================================================
    // RM.RI 01B - HAK DAN KEWAJIBAN PASIEN
    // ============================================================

    public function getRi01bmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('ri01b_hak_kewajiban_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri01b_hak_kewajiban_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri01b_hak_kewajiban_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri01b_hak_kewajiban_pasien.id')
            ->toArray();

        return $this->draw('ri01b/manage.html', ['data' => $data]);
    }

    public function getRi01bform()
    {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }
        
        // AJAX: ambil info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, pasien.pnd, pasien.bahasa_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = array_merge(['success' => true], $reg);
                }
            }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'tgl_lahir' => '',
            'nama_pasien_pj' => '',
            'nama_pemberi_edukasi' => ''
        ];

        if (!empty($id)) {
            $data = $this->db('ri01b_hak_kewajiban_pasien')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->oneArray();

                if ($reg) {
                    $data['nm_pasien'] = $reg['nm_pasien'];
                    $data['tgl_lahir'] = $reg['tgl_lahir'];
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                }
            }
        }

        return $this->draw('ri01b/form.html', ['data' => $data]);
    }

    public function postRi01bsave()
    {
        $id = $_POST['id'] ?? '';
        $data = [
            'no_rawat' => $_POST['no_rawat'] ?? '',
            'tanggal_jam' => date('Y-m-d H:i:s'),
            'nama_pasien_pj' => $_POST['nama_pasien_pj'] ?? '',
            'nama_pemberi_edukasi' => $_POST['nama_pemberi_edukasi'] ?? ''
        ];
        
        if ($id) {
            $this->db('ri01b_hak_kewajiban_pasien')->where('id', $id)->save($data);
            $this->notify('success', 'Data berhasil diupdate');
        } else {
            $this->db('ri01b_hak_kewajiban_pasien')->save($data);
            $this->notify('success', 'Data berhasil disimpan');
        }
        
        redirect(url([ADMIN, 'update_bmt', 'ri01bmanage']));
    }

    public function getRi01bhapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('ri01b_hak_kewajiban_pasien')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'ri01bmanage']));
    }

    public function getRi01bcetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ri01b_hak_kewajiban_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri01b_hak_kewajiban_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri01b_hak_kewajiban_pasien.id', $id)
            ->select('ri01b_hak_kewajiban_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        // Format tanggal lahir
        if (!empty($data['tgl_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        }
        
        if (!empty($data['tanggal_jam'])) {
            $tgljam = explode(' ', $data['tanggal_jam']);
            $tgl = explode('-', $tgljam[0]);
            if (count($tgl) == 3) {
                $data['tanggal_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            }
        }

        echo $this->draw('ri01b/cetak.html', ['data' => $data]);
        exit();
    }


    // ============================================================
    // RM.RI 02 - RINGKASAN MASUK DAN KELUAR
    // ============================================================
    public function getRi02manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri02_ringkasan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02_ringkasan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri02_ringkasan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri02_ringkasan.id')->toArray();
        return $this->draw('ri02/manage.html', ['data' => $data]);
    }
    public function getRi02form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            error_log('AJAX RI02 HIT! no_rawat: ' . $no_rawat);
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('reg_periksa.no_rawat', $no_rawat)->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.gol_darah, pasien.alamat, pasien.pekerjaan, pasien.pnd, pasien.agama, pasien.stts_nikah, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar, kamar.kelas')
                    ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')->oneArray();
                if ($reg) { $result = array_merge(['success' => true], $reg); }
                error_log('AJAX RI02 RESULT: ' . json_encode($result));
            }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json'); echo json_encode($result); exit();
        }
        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '', 'no_rawat' => '', 'no_rkm_medis' => '', 'nm_pasien' => '',
            'dirawat_ke' => '', 'pendidikan' => '', 'agama' => '', 'pekerjaan' => '',
            'nama_wali' => '', 'saksi' => '', 'wali_adalah' => '', 'dikirim_oleh' => '',
            'status_perkawinan' => '', 'cara_penerimaan' => '', 'peserta_phb' => '',
            'ruang_rawat' => '', 'kelas' => '', 'mutasi_ruang' => '', 'mutasi_kelas' => '',
            'bagian' => '', 'sebab_dirawat' => '', 'tgl_masuk' => '', 'jam_masuk' => '',
            'tgl_keluar' => '', 'jam_keluar' => '', 'lama_dirawat' => '', 'diagnosis_masuk' => '',
            'diagnosis_akhir' => '', 'kode_akhir' => '', 'diagnosis_tambahan_1' => '',
            'kode_tambahan_1' => '', 'diagnosis_tambahan_2' => '', 'kode_tambahan_2' => '',
            'komplikasi' => '', 'kode_komplikasi' => '', 'penyebab_luar' => '',
            'nama_operasi' => '', 'gol_operasi' => '', 'jenis_anestesi' => '', 'tgl_operasi' => '',
            'kode_operasi' => '', 'infeksi_nosokomial' => '', 'penyebab_infeksi' => '',
            'imunisasi_didapat' => '', 'imunisasi_selama_dirawat' => '', 'transfusi_darah' => '',
            'transfusi_1' => '', 'transfusi_2' => '', 'transfusi_3' => '', 'keadaan_keluar' => '',
            'sebab_kematian' => '', 'cara_keluar' => '', 'dokter_ruang' => '', 'petugas_admissi' => ''
        ];
        if (!empty($id)) {
            $data = $this->db('ri02_ringkasan')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah, pasien.umur, pasien.alamat, pasien.pnd, pasien.agama, pasien.pekerjaan, pasien.stts_nikah')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        $this->core->addCSS(url('assets/css/jquery-ui.css'));
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'));
        return $this->draw('ri02/form.html', ['data' => $data]);
    }
    public function postRi02save() {
        $id = $_POST['id'] ?? '';
        $data = $_POST; unset($data['id']); unset($data['t']); unset($data['ajax_patient']);
        
        if (isset($data['cara_keluar_keterangan'])) {
            if (strpos($data['cara_keluar'] ?? '', '3. Dirujuk') !== false && !empty($data['cara_keluar_keterangan'])) {
                $keterangan = str_replace('3. Dirujuk ke ', '', $data['cara_keluar_keterangan']);
                $data['cara_keluar'] = '3. Dirujuk ke ' . trim($keterangan);
            }
            unset($data['cara_keluar_keterangan']);
        }

        foreach(['tgl_masuk', 'tgl_keluar', 'tgl_operasi', 'jam_masuk', 'jam_keluar'] as $f) {
            if (isset($data[$f]) && strlen(trim($data[$f])) < 4) {
                unset($data[$f]);
            }
        }

        if ($id) { $this->db('ri02_ringkasan')->where('id', $id)->save($data); $this->notify('success', 'Data diupdate'); } 
        else { $this->db('ri02_ringkasan')->save($data); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02manage']));
    }
    public function getRi02hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri02_ringkasan')->where('id', $id)->delete(); $this->notify('success', 'Dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02manage']));
    }
    public function getRi02cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $data = $this->db('ri02_ringkasan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02_ringkasan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
            ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
            ->where('ri02_ringkasan.id', $id)
            ->select('ri02_ringkasan.*, ri02_ringkasan.tgl_keluar as tanggal_jam, reg_periksa.no_rkm_medis, pasien.*, kamar_inap.kd_kamar, kamar.kelas')
            ->oneArray();
            
        if(empty($data['umur'])) { 
            $data['umur'] = date_diff(date_create($data['tgl_lahir']), date_create('today'))->y . ' th'; 
        }

        echo $this->draw('ri02/cetak.html', ['data' => $data]); exit();
    }

    // ============================================================
    // RM.RI 03 - SERAH TERIMA PASIEN
    // ============================================================
    public function getRi03manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri03_serah_terima')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri03_serah_terima.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri03_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->toArray();
        return $this->draw('ri03/manage.html', ['data' => $data]);
    }
    public function getRi03form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }
        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '',
            'no_rawat' => '',
            'nm_pasien' => '',
            'no_rkm_medis' => '',
            'diagnosa_medis' => '',
            'asal_ruangan' => '',
            'jam' => '',
            'catatan_khusus' => '',
            'perawat_asal' => '',
            'perawat_penerima' => '',
            'ruangan_penerima' => '',
            'daftar_obat' => [],
            'daftar_alat' => [],
            'pemeriksaan_penunjang' => []
        ];
        if ($id) {
            $db_data = $this->db('ri03_serah_terima')->where('id', $id)->oneArray();
            if ($db_data) {
                $data = array_merge($data, $db_data);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
                foreach(['daftar_obat', 'daftar_alat', 'pemeriksaan_penunjang'] as $f) {
                    if (!empty($data[$f]) && is_string($data[$f])) { $data[$f] = json_decode($data[$f], true); }
                }
            }
        }
        return $this->draw('ri03/form.html', ['data' => $data]);
    }
    public function postRi03save() {
        $id = $_POST['id'] ?? '';
        $data = $_POST; 
        unset($data['id']); unset($data['t']); unset($data['ajax_patient']); unset($data['save']);
        
        foreach(['daftar_obat', 'daftar_alat', 'pemeriksaan_penunjang'] as $f) {
            $json_val = $_POST['json_' . $f] ?? '[]';
            $data[$f] = $json_val;
            unset($data['json_' . $f]);
            // Also unset the raw array if it exists to prevent clutter
            unset($data[$f . '_raw']);
        }
        
        if ($id) { $this->db('ri03_serah_terima')->where('id', $id)->save($data); $this->notify('success', 'Data diupdate'); } 
        else { $this->db('ri03_serah_terima')->save($data); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri03manage']));
    }
    public function getRi03hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri03_serah_terima')->where('id', $id)->delete(); $this->notify('success', 'Dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri03manage']));
    }

    public function getRi03cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $data = $this->db('ri03_serah_terima')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri03_serah_terima.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri03_serah_terima.id', $id)
            ->select('ri03_serah_terima.*, reg_periksa.no_rkm_medis, pasien.*')
            ->oneArray();
            
        foreach(['daftar_obat', 'daftar_alat', 'pemeriksaan_penunjang'] as $f) {
            $data[$f] = !empty($data[$f]) ? json_decode($data[$f], true) : [];
        }

        echo $this->draw('ri03/cetak.html', ['data' => $data]); exit();
    }

    // ============================================================
    // RM.RI 04 - TRANSFER PASIEN INTERNAL
    // ============================================================
    public function getRi04manage() {
        $data = $this->db('ri04_transfer_internal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri04_transfer_internal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri04_transfer_internal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri04_transfer_internal.id')
            ->toArray();
        return $this->draw('ri04/manage.html', ['data' => $data]);
    }

    public function getRi04form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }
        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '',
            'unit_tujuan' => '', 'petugas_dihubungi' => '', 'waktu_hub_tgl' => '', 'waktu_hub_jam' => '',
            'waktu_transfer_tgl' => '', 'waktu_transfer_jam' => '', 'alasan_transfer' => '', 'alasan_lainnya' => '',
            'dpjp' => '', 'tgl_masuk' => '', 'ruang_kamar' => '', 'waktu_pindah_tgl' => '', 'waktu_pindah_jam' => '', 'pindah_ke_ruang' => '', 'diagnosis_masuk' => '', 'diagnosis_sekarang' => '',
            'keluhan_utama' => '', 'riwayat_penyakit' => '', 'tensi' => '', 'suhu' => '', 'nadi' => '', 'pernafasan' => '', 'keadaan_umum' => '', 'pemeriksaan_penunjang' => '', 'tindakan_medis' => '', 'pemberian_terapi' => '',
            'alat_terpasang' => [], 'obat_dibawa' => [], 'dokumen_disertakan' => [], 'informasi_diberikan' => [],
            'kategori_transfer' => '',
            'pre_keadaan_umum' => '', 'pre_kesadaran' => '', 'pre_tensi' => '', 'pre_suhu' => '', 'pre_nadi' => '', 'pre_pernafasan' => '', 'pre_catatan' => '',
            'post_keadaan_umum' => '', 'post_kesadaran' => '', 'post_tensi' => '', 'post_suhu' => '', 'post_nadi' => '', 'post_pernafasan' => '', 'post_catatan' => '',
            'waktu_serah_tgl' => '', 'waktu_serah_jam' => '', 'petugas_menyerahkan' => '', 'petugas_menerima' => ''
        ];
        if ($id) {
            $db_data = $this->db('ri04_transfer_internal')->where('id', $id)->oneArray();
            if ($db_data) {
                $data = array_merge($data, $db_data);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
                foreach(['alat_terpasang', 'obat_dibawa', 'dokumen_disertakan', 'informasi_diberikan'] as $f) {
                    if (!empty($data[$f]) && is_string($data[$f])) { $data[$f] = json_decode($data[$f], true); }
                    if (!is_array($data[$f])) { $data[$f] = []; }
                }
            }
        }
        return $this->draw('ri04/form.html', ['data' => $data, 'huruf' => ['a', 'b', 'c', 'd']]);
    }

    public function postRi04save() {
        $id = $_POST['id'] ?? '';
        $data = $_POST; 
        unset($data['id']); unset($data['t']); unset($data['ajax_patient']); unset($data['save']);
        
        foreach(['alat_terpasang', 'obat_dibawa', 'dokumen_disertakan', 'informasi_diberikan'] as $f) {
            $json_val = $_POST['json_' . $f] ?? '[]';
            $data[$f] = $json_val;
            unset($data['json_' . $f]);
            unset($data[$f . '_raw']);
        }
        
        if ($id) { $this->db('ri04_transfer_internal')->where('id', $id)->save($data); $this->notify('success', 'Data diupdate'); } 
        else { $this->db('ri04_transfer_internal')->save($data); $this->notify('success', 'Data ditambahkan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri04manage']));
    }

    public function getRi04hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri04_transfer_internal')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri04manage']));
    }

    public function getRi04cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $data = $this->db('ri04_transfer_internal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri04_transfer_internal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri04_transfer_internal.id', $id)
            ->select('ri04_transfer_internal.*, reg_periksa.no_rkm_medis, pasien.*')
            ->oneArray();
            
        foreach(['alat_terpasang', 'obat_dibawa', 'dokumen_disertakan', 'informasi_diberikan'] as $f) {
            $data[$f] = !empty($data[$f]) ? json_decode($data[$f], true) : [];
            if (!is_array($data[$f])) { $data[$f] = []; }
        }

        $v = function($k) use ($data) { return isset($data[$k]) && $data[$k] !== null ? htmlspecialchars($data[$k]) : ''; };
        $tgl = function($d) { return ($d && $d != '0000-00-00') ? date('d-m-Y', strtotime($d)) : ''; };
        $jam = function($j) { return $j ? substr($j, 0, 5) : ''; };
        $ck = function($b) { return $b ? '&#10004;' : '&nbsp;'; };

        $p = [];
        foreach ($data as $k => $val) { if (!is_array($val)) { $p[$k] = $v($k); } }
        $rm = str_pad((string)($data['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['lp'] = ($data['jk'] ?? '') == 'L' ? 'L' : (($data['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['jk_text'] = ($data['jk'] ?? '') == 'L' ? 'Laki-laki' : (($data['jk'] ?? '') == 'P' ? 'Perempuan' : '');
        foreach (['tgl_lahir', 'tgl_masuk', 'waktu_hub_tgl', 'waktu_transfer_tgl', 'waktu_pindah_tgl', 'waktu_serah_tgl'] as $k) { $p[$k] = $tgl($data[$k] ?? ''); }
        foreach (['waktu_hub_jam', 'waktu_transfer_jam', 'waktu_pindah_jam', 'waktu_serah_jam'] as $k) { $p[$k] = $jam($data[$k] ?? ''); }
        foreach (['pemeriksaan_penunjang', 'tindakan_medis', 'pemberian_terapi'] as $k) { $p[$k] = nl2br($v($k)); }

        $p['ck_klinis'] = $ck(($data['alasan_transfer'] ?? '') == 'Kondisi Klinis');
        $p['ck_permintaan'] = $ck(($data['alasan_transfer'] ?? '') == 'Permintaan pasien / keluarga');
        $p['ck_lainnya'] = $ck(($data['alasan_transfer'] ?? '') == 'Lainnya');
        $p['ck_keluhan'] = $ck(!empty($data['keluhan_utama']));
        $p['ck_riwayat'] = $ck(!empty($data['riwayat_penyakit']));
        $p['ck_ttv'] = $ck(!empty($data['tensi']) || !empty($data['suhu']) || !empty($data['nadi']) || !empty($data['pernafasan']));
        $p['ck_ku'] = $ck(!empty($data['keadaan_umum']));

        for ($i = 0; $i < 4; $i++) {
            $p['alat' . $i . '_nama'] = htmlspecialchars($data['alat_terpasang'][$i]['nama'] ?? '');
            $p['alat' . $i . '_tgl'] = $tgl($data['alat_terpasang'][$i]['tgl'] ?? '');
            $p['obat' . $i . '_nama'] = htmlspecialchars($data['obat_dibawa'][$i]['nama'] ?? '');
            $p['obat' . $i . '_jumlah'] = htmlspecialchars($data['obat_dibawa'][$i]['jumlah'] ?? '');
        }

        $dok = $data['dokumen_disertakan']; $dokStd = ['Rekam Medis', 'Hasil Pemeriksaan Laboratorium', 'Hasil Pemeriksaan Radiologi'];
        $p['ck_dok_rm'] = $ck(in_array($dokStd[0], $dok));
        $p['ck_dok_lab'] = $ck(in_array($dokStd[1], $dok));
        $p['ck_dok_rad'] = $ck(in_array($dokStd[2], $dok));
        $dokLain = array_values(array_filter($dok, function($x) use ($dokStd) { return $x !== '' && !in_array($x, $dokStd); }));
        $p['ck_dok_lain'] = $ck(!empty($dokLain)); $p['dok_lain'] = htmlspecialchars(implode(', ', $dokLain));

        $inf = $data['informasi_diberikan']; $infStd = ['Perubahan Tarif Ruangan (Transfer Internal)', 'Tarif Tindakan / Operan Pemeriksaan'];
        $p['ck_inf_tarif'] = $ck(in_array($infStd[0], $inf));
        $p['ck_inf_tindakan'] = $ck(in_array($infStd[1], $inf));
        $infLain = array_values(array_filter($inf, function($x) use ($infStd) { return $x !== '' && !in_array($x, $infStd); }));
        $p['ck_inf_lain'] = $ck(!empty($infLain)); $p['inf_lain'] = htmlspecialchars(implode(', ', $infLain));

        foreach (['Level 0', 'Level 1', 'Level 2', 'Level 3'] as $i => $lv) { $p['ck_lv' . $i] = $ck(($data['kategori_transfer'] ?? '') == $lv); }

        echo $this->draw('ri04/cetak.html', ['p' => $p]); exit();
    }

    // ============================================================
    // RM.RI 09 - FORM ASESMEN STATUS FUNGSIONAL (BARTHEL INDEX)
    // ============================================================
    private function ri09Items() {
        return [
            1 => ['Makan', 'Feeding', ['Tidak mampu', 'Butuh bantuan memotong, mengoles mentega, dll', 'Mandiri']],
            2 => ['Mandi', 'Bathing', ['Tergantung orang lain', 'Mandiri']],
            3 => ['Perawatan Diri', 'Grooming', ['Membutuhkan bantuan orang lain', 'Mandiri dalam perawatan muka, rambut, gigi, dan bercukur']],
            4 => ['Berpakaian', 'Dressing', ['Tergantung orang lain', 'Sebagian dibantu (misalnya mengancing baju)', 'Mandiri']],
            5 => ['Buang Air Kecil', 'Blader', ['Inkontinensia atau pakai kateter dan tidak dapat mengontrol', 'Kadang Inkontinensia (maks, 1 kali dalam 24 jam)', 'Kontinensia (teratur untuk lebih dari 7 hari)']],
            6 => ['Buang Air Besar', 'Bowels', ['Inkontinensia (tidak teratur atau perlu enema)', 'Kadang Inkontensia (sekali seminggu)', 'Kontinensia (teratur)']],
            7 => ['Penggunaan Toilet', 'Toilet Use', ['Tergantung bantuan orang lain', 'Membutuhkan bantuan, tapi dapat melakukan beberapa hal sendiri', 'Mandiri']],
            8 => ['Transfer', '', ['Tidak mampu – tidak seimbang saat duduk', 'Butuh bantuan untuk bisa duduk (2 orang)', 'Bantuan kecil (1 orang)', 'Mandiri']],
            9 => ['Mobilitas', 'Mobility', ['Immobile (tidak mampu)', 'Menggunakan kursi roda', 'Berjalan dengan bantuan satu orang', 'Mandiri (meskipun menggunakan alat bantu seperti tongkat)']],
            10 => ['Naik Turun Tangga', 'Stairs', ['Tidak mampu', 'Membutuhkan bantuan (alat bantu)', 'Mandiri']],
        ];
    }

    private function ri09Kategori($total) {
        if ($total === null || $total === '') return '';
        $t = (int)$total;
        if ($t >= 20) return 'Mandiri';
        if ($t >= 12) return 'Ketergantungan Ringan';
        if ($t >= 9) return 'Ketergantungan Sedang';
        if ($t >= 5) return 'Ketergantungan Berat';
        return 'Ketergantungan Total';
    }

    public function getRi09manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri09_status_fungsional')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri09_status_fungsional.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri09_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri09_status_fungsional.id')
            ->toArray();
        return $this->draw('ri09/manage.html', ['data' => $data]);
    }

    public function getRi09form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'ruangan' => '',
                 'tanggal' => date('Y-m-d'), 'jam' => date('H:i'), 'total_skor' => '', 'kategori' => '', 'petugas' => ''];
        for ($i = 1; $i <= 10; $i++) { $data['s' . $i] = ''; }

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri09_status_fungsional')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $data['jam'] = substr((string)$data['jam'], 0, 5);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }

        // Build Barthel rows (radio buttons) in PHP to keep the template simple
        $rows = '';
        foreach ($this->ri09Items() as $no => $it) {
            $n = count($it[2]);
            foreach ($it[2] as $skor => $label) {
                $checked = ($data['s' . $no] !== '' && $data['s' . $no] !== null && (int)$data['s' . $no] === $skor) ? 'checked' : '';
                $rows .= '<tr>';
                if ($skor === 0) {
                    $rows .= '<td rowspan="' . $n . '" class="text-center">' . $no . '</td>';
                    $rows .= '<td rowspan="' . $n . '">' . $it[0] . ($it[1] ? ' <i>(' . $it[1] . ')</i>' : '') . '</td>';
                }
                $rows .= '<td><label style="font-weight:normal; margin:0; display:block; cursor:pointer;">'
                       . '<input type="radio" class="ri09-skor" name="s' . $no . '" value="' . $skor . '" ' . $checked . '> ' . htmlspecialchars($label) . '</label></td>';
                $rows .= '<td class="text-center">' . $skor . '</td>';
                $rows .= '</tr>';
            }
        }

        return $this->draw('ri09/form.html', ['data' => $data, 'rows' => $rows]);
    }

    public function postRi09save() {
        $id = $_POST['id'] ?? '';
        $save = [
            'no_rawat' => $_POST['no_rawat'] ?? '',
            'ruangan'  => $_POST['ruangan'] ?? '',
            'tanggal'  => ($_POST['tanggal'] ?? '') ?: null,
            'jam'      => ($_POST['jam'] ?? '') ?: null,
            'petugas'  => $_POST['petugas'] ?? '',
        ];
        $total = 0; $filled = false;
        for ($i = 1; $i <= 10; $i++) {
            $val = $_POST['s' . $i] ?? '';
            if ($val === '' || $val === null) { $save['s' . $i] = null; }
            else { $save['s' . $i] = (int)$val; $total += (int)$val; $filled = true; }
        }
        $save['total_skor'] = $filled ? $total : null;
        $save['kategori'] = $filled ? $this->ri09Kategori($total) : '';

        if ($id) { $this->db('ri09_status_fungsional')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri09_status_fungsional')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri09manage']));
    }

    public function getRi09hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri09_status_fungsional')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri09manage']));
    }

    public function getRi09cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri09_status_fungsional')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri09_status_fungsional.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri09_status_fungsional.id', $id)
            ->select('ri09_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        $p['ruangan'] = htmlspecialchars($d['ruangan'] ?? '');
        $p['tanggal_jam'] = (!empty($d['tanggal']) ? date('d-m-Y', strtotime($d['tanggal'])) : '') . (!empty($d['jam']) ? ' ' . substr($d['jam'], 0, 5) : '');
        $p['total'] = $d['total_skor'] !== null ? $d['total_skor'] : '';
        $p['kategori'] = htmlspecialchars($d['kategori'] ?? '');
        $p['petugas'] = htmlspecialchars($d['petugas'] ?? '');

        $rows = '';
        foreach ($this->ri09Items() as $no => $it) {
            $n = count($it[2]);
            $nilai = ($d['s' . $no] !== null && $d['s' . $no] !== '') ? (int)$d['s' . $no] : null;
            foreach ($it[2] as $skor => $label) {
                $sel = ($nilai !== null && $nilai === $skor);
                $rows .= '<tr>';
                if ($skor === 0) {
                    $rows .= '<td rowspan="' . $n . '">' . $no . '</td>';
                    $rows .= '<td rowspan="' . $n . '">' . $it[0] . ($it[1] ? ' <i>(' . $it[1] . ')</i>' : '') . '</td>';
                }
                $rows .= '<td' . ($sel ? ' class="sel"' : '') . '>' . htmlspecialchars($label) . '</td>';
                $rows .= '<td class="c' . ($sel ? ' sel' : '') . '">' . $skor . '</td>';
                if ($skor === 0) {
                    $rows .= '<td rowspan="' . $n . '" class="c nilai">' . ($nilai !== null ? $nilai : '') . '</td>';
                }
                $rows .= '</tr>';
            }
        }

        $kat = ['Mandiri' => '20', 'Ketergantungan Ringan' => '12-19', 'Ketergantungan Sedang' => '9-11', 'Ketergantungan Berat' => '5-8', 'Ketergantungan Total' => '0-4'];
        $katRows = ''; $first = true;
        foreach ($kat as $label => $range) {
            $on = ($d['kategori'] ?? '') === $label;
            $katRows .= '<tr><td style="width:45px;">' . ($first ? 'Skor' : '') . '</td><td style="width:45px;">' . $range . '</td><td style="width:20px;">:</td><td>'
                      . ($on ? '<span class="circle">' . $label . '</span>' : $label) . '</td></tr>';
            $first = false;
        }

        echo $this->draw('ri09/cetak.html', ['p' => $p, 'rows' => $rows, 'katRows' => $katRows]); exit();
    }

    // ============================================================
    // RM.RI 11B - FORMULIR PEMBERIAN MAKAN PASIEN
    // ============================================================
    public function getRi11bmanage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri11b_pemberian_makan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri11b_pemberian_makan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri11b_pemberian_makan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri11b_pemberian_makan.id')
            ->toArray();
        return $this->draw('ri11b/manage.html', ['data' => $data]);
    }

    public function getRi11bform() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'dpjp_utama' => '',
                 'nutrisionis' => '', 'kamar_kelas' => '', 'tanggal_rawat' => '', 'diagnosa' => '',
                 'bentuk_makanan' => '[]', 'diet' => '[]', 'diet_lainnya' => '', 'detail_pemberian' => '[]',
                 'ttd_nutrisionis' => '', 'ttd_perawat' => ''];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri11b_pemberian_makan')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        foreach (['bentuk_makanan', 'diet', 'detail_pemberian'] as $f) {
            $data[$f] = !empty($data[$f]) && is_string($data[$f]) ? json_decode($data[$f], true) : [];
            if (!is_array($data[$f])) { $data[$f] = []; }
        }
        // pre-fill empty detail rows if new
        if (empty($data['detail_pemberian'])) {
            $data['detail_pemberian'][] = ['tgl' => '', 'p_a' => '', 'p_h' => '', 'p_t' => '', 's_a' => '', 's_h' => '', 's_t' => '', 'm_a' => '', 'm_h' => '', 'm_t' => ''];
        }

        return $this->draw('ri11b/form.html', ['data' => $data]);
    }

    public function postRi11bsave() {
        $id = $_POST['id'] ?? '';
        $save = [
            'no_rawat' => $_POST['no_rawat'] ?? '',
            'dpjp_utama' => $_POST['dpjp_utama'] ?? '',
            'nutrisionis' => $_POST['nutrisionis'] ?? '',
            'kamar_kelas' => $_POST['kamar_kelas'] ?? '',
            'tanggal_rawat' => ($_POST['tanggal_rawat'] ?? '') ?: null,
            'diagnosa' => $_POST['diagnosa'] ?? '',
            'diet_lainnya' => $_POST['diet_lainnya'] ?? '',
            'ttd_nutrisionis' => $_POST['ttd_nutrisionis'] ?? '',
            'ttd_perawat' => $_POST['ttd_perawat'] ?? ''
        ];
        
        foreach (['bentuk_makanan', 'diet', 'detail_pemberian'] as $f) {
            $save[$f] = $_POST['json_' . $f] ?? '[]';
        }

        if ($id) { $this->db('ri11b_pemberian_makan')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri11b_pemberian_makan')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri11bmanage']));
    }

    public function getRi11bhapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri11b_pemberian_makan')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri11bmanage']));
    }

    public function getRi11bcetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri11b_pemberian_makan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri11b_pemberian_makan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri11b_pemberian_makan.id', $id)
            ->select('ri11b_pemberian_makan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        foreach (['bentuk_makanan', 'diet', 'detail_pemberian'] as $f) {
            $d[$f] = !empty($d[$f]) ? json_decode($d[$f], true) : [];
            if (!is_array($d[$f])) { $d[$f] = []; }
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        $p['dpjp_utama'] = htmlspecialchars($d['dpjp_utama'] ?? '');
        $p['nutrisionis'] = htmlspecialchars($d['nutrisionis'] ?? '');
        $p['kamar_kelas'] = htmlspecialchars($d['kamar_kelas'] ?? '');
        $p['tanggal_rawat'] = !empty($d['tanggal_rawat']) && $d['tanggal_rawat'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tanggal_rawat'])) : '';
        $p['diagnosa'] = htmlspecialchars($d['diagnosa'] ?? '');

        $v = function($b) { return $b ? '&#10004;' : '&nbsp;'; };
        
        $b = $d['bentuk_makanan'];
        $p['ck_nasi'] = $v(in_array('Nasi Biasa', $b));
        $p['ck_lunak'] = $v(in_array('Lunak', $b));
        $p['ck_saring'] = $v(in_array('Saring', $b));
        $p['ck_sumsum'] = $v(in_array('Sumsum', $b));
        $p['ck_cair'] = $v(in_array('Cair', $b));

        $t = $d['diet'];
        $p['ck_bebas'] = $v(in_array('Bebas/Biasa', $t));
        $p['ck_tktp'] = $v(in_array('TKTP', $t));
        $p['ck_dm'] = $v(in_array('DM', $t));
        $p['ck_rg'] = $v(in_array('RG', $t));
        
        $dietLain = array_values(array_filter($t, function($x) { return !in_array($x, ['Bebas/Biasa', 'TKTP', 'DM', 'RG']); }));
        $p['ck_lain'] = $v(!empty($dietLain));
        $p['diet_lain'] = htmlspecialchars(implode(', ', $dietLain));

        $p['ttd_nutrisionis'] = htmlspecialchars($d['ttd_nutrisionis'] ?? '');
        $p['ttd_perawat'] = htmlspecialchars($d['ttd_perawat'] ?? '');

        // Splitting detail_pemberian into two chunks for pagination (e.g. 15 rows for page 1, up to 16 rows for page 2)
        $detail = $d['detail_pemberian'];
        while (count($detail) < 31) { $detail[] = ['tgl' => '', 'p_a' => '', 'p_h' => '', 'p_t' => '', 's_a' => '', 's_h' => '', 's_t' => '', 'm_a' => '', 'm_h' => '', 'm_t' => '']; }
        $page1_rows = array_slice($detail, 0, 12);
        $page2_rows = array_slice($detail, 12, 19);

        echo $this->draw('ri11b/cetak.html', ['p' => $p, 'p1' => $page1_rows, 'p2' => $page2_rows]); exit();
    }

    // ============================================================
    // RM.RI 14 - REKONSILIASI OBAT
    // ============================================================
    public function getRi14manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri14_rekonsiliasi_obat')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri14_rekonsiliasi_obat.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri14_rekonsiliasi_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri14_rekonsiliasi_obat.id')
            ->toArray();
        return $this->draw('ri14/manage.html', ['data' => $data]);
    }

    public function getRi14form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'tanggal_mrs' => '', 'dpjp' => '', 'riwayat_alergi' => '',
                 'admisi_detail' => '[]', 'admisi_apoteker' => '',
                 'transfer_ruang_asal' => '', 'transfer_ruang_tujuan' => '', 'transfer_tanggal' => '', 'transfer_detail' => '[]', 'transfer_apoteker' => '',
                 'pulang_detail' => '[]', 'pulang_apoteker' => '',
                 'tanggal_krs' => '', 'status_krs' => ''];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri14_rekonsiliasi_obat')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        $data['admisi_detail'] = !empty($data['admisi_detail']) && is_string($data['admisi_detail']) ? json_decode($data['admisi_detail'], true) : [];
        if (!is_array($data['admisi_detail']) || empty($data['admisi_detail'])) { $data['admisi_detail'] = [['nama'=>'', 'rute'=>'', 'aturan'=>'', 'stat1'=>'', 'stat2'=>'']]; }
        
        $data['transfer_detail'] = !empty($data['transfer_detail']) && is_string($data['transfer_detail']) ? json_decode($data['transfer_detail'], true) : [];
        if (!is_array($data['transfer_detail']) || empty($data['transfer_detail'])) { $data['transfer_detail'] = [['nama'=>'', 'dosis'=>'', 'rute'=>'', 'aturan'=>'', 'stat1'=>'', 'ubah'=>'', 'ket'=>'']]; }
        
        $data['pulang_detail'] = !empty($data['pulang_detail']) && is_string($data['pulang_detail']) ? json_decode($data['pulang_detail'], true) : [];
        if (!is_array($data['pulang_detail']) || empty($data['pulang_detail'])) { $data['pulang_detail'] = [['nama'=>'', 'dosis'=>'', 'rute'=>'', 'aturan'=>'', 'ubah'=>'', 'ket'=>'']]; }

        return $this->draw('ri14/form.html', ['data' => $data]);
    }

    public function postRi14save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'tanggal_mrs' => ($_POST['tanggal_mrs'] ?? '') ?: null,
            'dpjp' => $_POST['dpjp'] ?? '',
            'riwayat_alergi' => $_POST['riwayat_alergi'] ?? '',
            'admisi_apoteker' => $_POST['admisi_apoteker'] ?? '',
            'transfer_ruang_asal' => $_POST['transfer_ruang_asal'] ?? '',
            'transfer_ruang_tujuan' => $_POST['transfer_ruang_tujuan'] ?? '',
            'transfer_tanggal' => ($_POST['transfer_tanggal'] ?? '') ?: null,
            'transfer_apoteker' => $_POST['transfer_apoteker'] ?? '',
            'pulang_apoteker' => $_POST['pulang_apoteker'] ?? '',
            'tanggal_krs' => ($_POST['tanggal_krs'] ?? '') ?: null,
            'status_krs' => $_POST['status_krs'] ?? '',
        ];
        
        foreach (['admisi_detail', 'transfer_detail', 'pulang_detail'] as $f) {
            $save[$f] = $_POST['json_' . $f] ?? '[]';
        }

        if ($id) { $this->db('ri14_rekonsiliasi_obat')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri14_rekonsiliasi_obat')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri14manage']));
    }

    public function getRi14hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri14_rekonsiliasi_obat')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri14manage']));
    }

    public function getRi14cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri14_rekonsiliasi_obat')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri14_rekonsiliasi_obat.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri14_rekonsiliasi_obat.id', $id)
            ->select('ri14_rekonsiliasi_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        foreach (['admisi_detail', 'transfer_detail', 'pulang_detail'] as $f) {
            $d[$f] = !empty($d[$f]) ? json_decode($d[$f], true) : [];
            if (!is_array($d[$f])) { $d[$f] = []; }
            while(count($d[$f]) < 8) {
                $d[$f][] = ['nama' => '', 'dosis' => '', 'rute' => '', 'aturan' => '', 'stat1' => '', 'stat2' => '', 'ubah' => '', 'ket' => ''];
            }
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        $p['tanggal_mrs'] = !empty($d['tanggal_mrs']) && $d['tanggal_mrs'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tanggal_mrs'])) : '';
        $p['dpjp'] = htmlspecialchars($d['dpjp'] ?? '');
        $p['riwayat_alergi'] = htmlspecialchars($d['riwayat_alergi'] ?? '');
        $p['admisi_apoteker'] = htmlspecialchars($d['admisi_apoteker'] ?? '');
        
        $p['transfer_ruang_asal'] = htmlspecialchars($d['transfer_ruang_asal'] ?? '');
        $p['transfer_ruang_tujuan'] = htmlspecialchars($d['transfer_ruang_tujuan'] ?? '');
        $p['transfer_tanggal'] = !empty($d['transfer_tanggal']) && $d['transfer_tanggal'] != '0000-00-00' ? date('d-m-Y', strtotime($d['transfer_tanggal'])) : '';
        $p['transfer_apoteker'] = htmlspecialchars($d['transfer_apoteker'] ?? '');
        
        $p['pulang_apoteker'] = htmlspecialchars($d['pulang_apoteker'] ?? '');
        $p['tanggal_krs'] = !empty($d['tanggal_krs']) && $d['tanggal_krs'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tanggal_krs'])) : '';
        $p['status_krs'] = htmlspecialchars($d['status_krs'] ?? '');

        echo $this->draw('ri14/cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RI 15 - FORMULIR INSTRUKSI MEDIS
    // ============================================================
    public function getRi15manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri15_instruksi_medis')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15_instruksi_medis.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri15_instruksi_medis.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri15_instruksi_medis.id')
            ->toArray();
        return $this->draw('ri15/manage.html', ['data' => $data]);
    }

    public function getRi15form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'riwayat_alergi' => '', 'alergi_terhadap' => ''];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri15_instruksi_medis')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }

        return $this->draw('ri15/form.html', ['data' => $data]);
    }

    public function postRi15save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'riwayat_alergi' => $_POST['riwayat_alergi'] ?? '',
            'alergi_terhadap' => $_POST['alergi_terhadap'] ?? ''
        ];

        if ($id) { $this->db('ri15_instruksi_medis')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri15_instruksi_medis')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15manage']));
    }

    public function getRi15hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri15_instruksi_medis')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15manage']));
    }

    public function getRi15cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri15_instruksi_medis')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15_instruksi_medis.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri15_instruksi_medis.id', $id)
            ->select('ri15_instruksi_medis.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        $p['riwayat_alergi'] = htmlspecialchars($d['riwayat_alergi'] ?? '');
        $p['alergi_terhadap'] = htmlspecialchars($d['alergi_terhadap'] ?? '');

        echo $this->draw('ri15/cetak.html', ['p' => $p]); exit();
    }

    // ============================================================
    // RM.RI 15A - DAFTAR INSTRUKSI MEDIS FARMAKOLOGI
    // ============================================================
    public function getRi15amanage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri15a_instruksi_medis_farmakologi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15a_instruksi_medis_farmakologi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri15a_instruksi_medis_farmakologi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri15a_instruksi_medis_farmakologi.id')
            ->toArray();
        return $this->draw('ri15a/ri15a_manage.html', ['data' => $data]);
    }

    public function getRi15aform() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'riwayat_alergi' => '', 'detail_obat' => '[]'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri15a_instruksi_medis_farmakologi')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $data['detail_obat'] = !empty($data['detail_obat']) && is_string($data['detail_obat']) ? json_decode($data['detail_obat'], true) : [];
        if (!is_array($data['detail_obat']) || empty($data['detail_obat'])) { 
            $data['detail_obat'] = [['tgl_jam'=>'', 'nama'=>'', 'dosis'=>'', 'frekuensi'=>'', 'cara'=>'', 'antibiotik'=>'', 'ttd_dokter'=>'', 'stop_tgl_jam'=>'', 'stop_ttd_dokter'=>'', 'ket'=>'']]; 
        }

        return $this->draw('ri15a/ri15a_form.html', ['data' => $data]);
    }

    public function postRi15asave() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'riwayat_alergi' => $_POST['riwayat_alergi'] ?? '',
            'detail_obat' => $_POST['json_detail_obat'] ?? '[]'
        ];

        if ($id) { $this->db('ri15a_instruksi_medis_farmakologi')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri15a_instruksi_medis_farmakologi')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15amanage']));
    }

    public function getRi15ahapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri15a_instruksi_medis_farmakologi')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15amanage']));
    }

    public function getRi15acetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri15a_instruksi_medis_farmakologi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15a_instruksi_medis_farmakologi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri15a_instruksi_medis_farmakologi.id', $id)
            ->select('ri15a_instruksi_medis_farmakologi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['detail_obat'] = !empty($d['detail_obat']) && is_string($d['detail_obat']) ? json_decode($d['detail_obat'], true) : [];
        if (!is_array($d['detail_obat'])) { $d['detail_obat'] = []; }
        while(count($d['detail_obat']) < 15) {
            $d['detail_obat'][] = ['tgl_jam'=>'', 'nama'=>'', 'dosis'=>'', 'frekuensi'=>'', 'cara'=>'', 'antibiotik'=>'', 'ttd_dokter'=>'', 'stop_tgl_jam'=>'', 'stop_ttd_dokter'=>'', 'ket'=>''];
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        $p['riwayat_alergi'] = htmlspecialchars($d['riwayat_alergi'] ?? '');

        echo $this->draw('ri15a/ri15a_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RI 15B - JADWAL PEMBERIAN OBAT (R)
    // ============================================================
    public function getRi15bmanage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri15b_jadwal_obat')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15b_jadwal_obat.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri15b_jadwal_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri15b_jadwal_obat.id')
            ->toArray();
        return $this->draw('ri15b/ri15b_manage.html', ['data' => $data]);
    }

    public function getRi15bform() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) { $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT); }
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'detail_obat' => '[]'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri15b_jadwal_obat')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $data['detail_obat'] = !empty($data['detail_obat']) && is_string($data['detail_obat']) ? json_decode($data['detail_obat'], true) : [];
        if (!is_array($data['detail_obat']) || empty($data['detail_obat'])) { 
            $data['detail_obat'] = [['nama_obat'=>'', 'dosis'=>'', 'high_alert'=>'Tidak', 'cara'=>'', 'pemberian'=>[]]]; 
        }

        return $this->draw('ri15b/ri15b_form.html', ['data' => $data]);
    }

    public function postRi15bsave() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'detail_obat' => $_POST['json_detail_obat'] ?? '[]'
        ];

        if ($id) { $this->db('ri15b_jadwal_obat')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri15b_jadwal_obat')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15bmanage']));
    }

    public function getRi15bhapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri15b_jadwal_obat')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri15bmanage']));
    }

    public function getRi15bcetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri15b_jadwal_obat')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri15b_jadwal_obat.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri15b_jadwal_obat.id', $id)
            ->select('ri15b_jadwal_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['detail_obat'] = !empty($d['detail_obat']) && is_string($d['detail_obat']) ? json_decode($d['detail_obat'], true) : [];
        if (!is_array($d['detail_obat'])) { $d['detail_obat'] = []; }
        
        while(count($d['detail_obat']) < 5) {
            $d['detail_obat'][] = ['nama_obat'=>'', 'dosis'=>'', 'high_alert'=>'Tidak', 'cara'=>'', 'pemberian'=>[]];
        }

        $table_html = '';
        foreach ($d['detail_obat'] as $k => $obat) {
            $dates = [];
            $grid = [];
            for ($i=0; $i<25; $i++) {
                $grid[$i] = ['jam'=>'', 'tanda'=>'', 'paraf'=>''];
            }
            
            $col_idx = 0;
            if (!empty($obat['pemberian'])) {
                foreach ($obat['pemberian'] as $pem) {
                    if ($col_idx >= 25) break;
                    if (!in_array($pem['tanggal'], $dates)) {
                        $dates[] = $pem['tanggal'];
                    }
                    $grid[$col_idx] = [
                        'jam' => $pem['jam'] ?? '',
                        'tanda' => $pem['tanda'] ?? '',
                        'paraf' => $pem['paraf'] ?? ''
                    ];
                    $col_idx++;
                }
            }
            
            $ha = ($obat['high_alert'] == 'Ya') ? '✓' : '&nbsp;';
            $table_html .= '<tr>
                <td rowspan="3" style="text-align:center; vertical-align:middle; position:relative;">
                    <b>'.htmlspecialchars($obat['nama_obat'] ?? '').'</b><br>'.htmlspecialchars($obat['dosis'] ?? '').'
                    <div class="high-alert-box">'.$ha.'</div>
                    <div style="font-size:8px;">HIGH ALERT</div>
                </td>
                <td rowspan="3" style="text-align:center; vertical-align:middle;">'.htmlspecialchars($obat['cara'] ?? '').'</td>
                <td style="text-align:center; height:20px;">Jam</td>';
            foreach ($grid as $g) { $table_html .= '<td style="width:14px; text-align:center; font-size:7px;">'.htmlspecialchars($g['jam']).'</td>'; }
            $table_html .= '</tr><tr><td style="text-align:center; height:20px;">Tanda</td>';
            foreach ($grid as $g) { $table_html .= '<td style="text-align:center; font-size:7px;">'.htmlspecialchars($g['tanda']).'</td>'; }
            $table_html .= '</tr><tr><td style="text-align:center; height:20px;">Paraf</td>';
            foreach ($grid as $g) { $table_html .= '<td style="text-align:center; font-size:7px;">'.htmlspecialchars($g['paraf']).'</td>'; }
            $table_html .= '</tr>';
        }
        $d['table_html'] = $table_html;

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('ri15b/ri15b_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RI 18 - LEMBAR EDUKASI PASIEN DAN KELUARGA TERINTEGRASI
    // ============================================================
    public function getRi18manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri18_edukasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri18_edukasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri18_edukasi_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri18_edukasi_pasien.id')
            ->toArray();
        return $this->draw('ri18/ri18_manage.html', ['data' => $data]);
    }

    public function getRi18form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.umur, pasien.jk, pasien.alamat, pasien.pnd, pasien.bahasa_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('kamar_inap.tgl_masuk')->desc('kamar_inap.jam_masuk')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'pengkajian' => '{}', 'pelaksanaan' => '[]'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri18_edukasi_pasien')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $data['pengkajian'] = !empty($data['pengkajian']) && is_string($data['pengkajian']) ? json_decode($data['pengkajian'], true) : [];
        if (!is_array($data['pengkajian'])) { $data['pengkajian'] = []; }
        $default_pengkajian = [
            'ketersediaan' => '', 'pendidikan' => '', 'bahasa' => '', 'bahasa_lainnya' => '',
            'hambatan' => [], 'keterbatasan' => [], 'penerjemah' => '', 'penerjemah_bahasa' => '',
            'pantangan_hari' => '', 'pantangan_makan' => '', 'pantangan_dokter' => '',
            'kemampuan_membaca' => '', 'nama_perawat' => '', 'tgl_jam_kaji' => '', 'kebutuhan' => []
        ];
        $data['pengkajian'] = array_merge($default_pengkajian, $data['pengkajian']);

        $data['pelaksanaan'] = !empty($data['pelaksanaan']) && is_string($data['pelaksanaan']) ? json_decode($data['pelaksanaan'], true) : [];
        if (!is_array($data['pelaksanaan']) || empty($data['pelaksanaan'])) { 
            $data['pelaksanaan'] = [['tgl_jam'=>'', 'isi'=>'', 'pra_edukasi'=>'', 'penerima'=>'', 'metode'=>'', 'evaluasi'=>'', 'profesi'=>'', 'nama_pemberi'=>'', 'nama_penerima'=>'']]; 
        }

        return $this->draw('ri18/ri18_form.html', ['data' => $data]);
    }

    public function postRi18save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'pengkajian' => $_POST['json_pengkajian'] ?? '{}',
            'pelaksanaan' => $_POST['json_pelaksanaan'] ?? '[]'
        ];

        if ($id) { $this->db('ri18_edukasi_pasien')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri18_edukasi_pasien')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri18manage']));
    }

    public function getRi18hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri18_edukasi_pasien')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri18manage']));
    }

    public function getRi18cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri18_edukasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri18_edukasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri18_edukasi_pasien.id', $id)
            ->select('ri18_edukasi_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['pengkajian'] = !empty($d['pengkajian']) && is_string($d['pengkajian']) ? json_decode($d['pengkajian'], true) : [];
        if (!is_array($d['pengkajian'])) { $d['pengkajian'] = []; }
        $d['pelaksanaan'] = !empty($d['pelaksanaan']) && is_string($d['pelaksanaan']) ? json_decode($d['pelaksanaan'], true) : [];
        if (!is_array($d['pelaksanaan'])) { $d['pelaksanaan'] = []; }

        while(count($d['pelaksanaan']) < 8) {
            $d['pelaksanaan'][] = ['tgl_jam'=>'', 'isi'=>'', 'pra_edukasi'=>'', 'penerima'=>'', 'metode'=>'', 'evaluasi'=>'', 'profesi'=>'', 'nama_pemberi'=>'', 'nama_penerima'=>''];
        }

        $pelaksanaan_html = '';
        foreach ($d['pelaksanaan'] as $pem) {
            $tgl_jam = '';
            if (!empty($pem['tgl_jam'])) {
                $tgl_jam = date('d-m-Y', strtotime($pem['tgl_jam'])) . '<br>' . date('H:i', strtotime($pem['tgl_jam']));
            }
            
            $ch_pra = '';
            $pra_opts = ['1'=>'Sangat tahu', '2'=>'Perlu diulang', '3'=>'Belum tahu'];
            foreach ($pra_opts as $k => $v) {
                $check = ($pem['pra_edukasi'] == $k) ? '√' : '&nbsp;';
                $ch_pra .= '<div style="margin-bottom:2px;">'.$k.'. <span style="display:inline-block; width:12px; height:12px; border:1px solid #000; text-align:center; line-height:12px; font-weight:bold;">'.$check.'</span> '.$v.'</div>';
            }
            
            $ch_pen = '';
            $pen_opts = ['1'=>'Pasien', '2'=>'Keluarga', '3'=>'Pasien & Keluarga', '4'=>'Lain-lain'];
            foreach ($pen_opts as $k => $v) {
                $check = ($pem['penerima'] == $k) ? '√' : '&nbsp;';
                $ch_pen .= '<div style="margin-bottom:2px;">'.$k.'. <span style="display:inline-block; width:12px; height:12px; border:1px solid #000; text-align:center; line-height:12px; font-weight:bold;">'.$check.'</span> '.$v.'</div>';
            }
            
            $ch_met = '';
            $met_opts = ['1'=>'Membaca', '2'=>'Demonstrasi', '3'=>'Ceramah', '4'=>'Diskusi', '5'=>'Audio Visual'];
            foreach ($met_opts as $k => $v) {
                $check = ($pem['metode'] == $k) ? '√' : '&nbsp;';
                $ch_met .= '<div style="margin-bottom:2px;">'.$k.'. <span style="display:inline-block; width:12px; height:12px; border:1px solid #000; text-align:center; line-height:12px; font-weight:bold;">'.$check.'</span> '.$v.'</div>';
            }
            
            $ch_eval = '';
            $eval_opts = ['1'=>'Mengerti', '2'=>'Mengerti, Mengulang', '3'=>'Mengerti, Mengulang,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mendemonstrasikan', '4'=>'Re-edukasi'];
            foreach ($eval_opts as $k => $v) {
                $check = ($pem['evaluasi'] == $k) ? '√' : '&nbsp;';
                $ch_eval .= '<div style="margin-bottom:2px;">'.$k.'. <span style="display:inline-block; width:12px; height:12px; border:1px solid #000; text-align:center; line-height:12px; font-weight:bold; vertical-align:top;">'.$check.'</span> <span style="display:inline-block; vertical-align:top;">'.$v.'</span></div>';
            }

            $pelaksanaan_html .= '<tr>
                <td style="text-align:center; font-size:10px;">'.$tgl_jam.'</td>
                <td style="font-size:10px; padding:4px;">'.nl2br(htmlspecialchars($pem['isi'])).'</td>
                <td style="font-size:9px; padding:4px;">'.$ch_pra.'</td>
                <td style="font-size:9px; padding:4px;">'.$ch_pen.'</td>
                <td style="font-size:9px; padding:4px;">'.$ch_met.'</td>
                <td style="font-size:9px; padding:4px;">'.$ch_eval.'</td>
                <td style="font-size:9px; padding:4px;">
                    <div>Profesi: '.htmlspecialchars($pem['profesi']).'</div>
                    <div style="height:35px;"></div>
                    <div>('.htmlspecialchars($pem['nama_pemberi']).')</div>
                </td>
                <td style="font-size:9px; vertical-align:bottom; text-align:center;">
                    <div>('.htmlspecialchars($pem['nama_penerima']).')</div>
                </td>
            </tr>';
        }
        $d['pelaksanaan_html'] = $pelaksanaan_html;

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('ri18/ri18_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RI 19 - PERENCANAAN PULANG PASIEN (DISCHARGE PLANNING)
    // ============================================================
    public function getRi19manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri19_discharge_planning')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri19_discharge_planning.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri19_discharge_planning.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri19_discharge_planning.id')
            ->toArray();
        return $this->draw('ri19/ri19_manage.html', ['data' => $data]);
    }

    public function getRi19form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.umur, pasien.jk, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('kamar_inap.tgl_masuk')->desc('kamar_inap.jam_masuk')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri19_discharge_planning')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'tgl_masuk' => '', 'rencana_tgl_pulang' => '', 'rencana_jam_pulang' => '',
            'usia_lanjut' => '', 'hambatan_mobilisasi' => '', 'perawatan_lanjutan' => '', 'tergantung_orang_lain' => '',
            'transportasi' => '', 'pendamping' => '',
            'pengobatan' => [['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'jam'=>'', 'instruksi'=>'']],
            'alat_medis_oksigen' => '', 'alat_medis_ngt' => '', 'alat_medis_tidak_ada' => '',
            'alat_bantu_kursi_roda' => '', 'alat_bantu_tongkat' => '', 'alat_bantu_lain_lain' => '', 'alat_bantu_lain_text' => '',
            'instruksi_rumah' => '',
            'diberi_obat' => '', 'diberi_peralatan' => '', 'diberi_resep' => '', 'diberi_hasil' => '', 'diberi_hasil_text' => '',
            'jk_nama_dokter' => '', 'jk_tgl_jam' => '', 'jk_petugas' => '',
            'id_pasien' => '', 'id_keluarga' => '', 'id_orang_terdekat' => '', 'id_lain_lain' => '', 'id_lain_text' => '',
            'pihak_pasien' => '', 'petugas_menjelaskan' => ''
        ];
        $data['form_data'] = array_merge($default_fd, $fd);
        if (empty($data['form_data']['pengobatan'])) {
            $data['form_data']['pengobatan'] = [['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'jam'=>'', 'instruksi'=>'']];
        }

        return $this->draw('ri19/ri19_form.html', ['data' => $data]);
    }

    public function postRi19save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('ri19_discharge_planning')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri19_discharge_planning')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri19manage']));
    }

    public function getRi19hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri19_discharge_planning')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri19manage']));
    }

    public function getRi19cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri19_discharge_planning')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri19_discharge_planning.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri19_discharge_planning.id', $id)
            ->select('ri19_discharge_planning.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }
        
        while(count($d['form_data']['pengobatan'] ?? []) < 5) {
            $d['form_data']['pengobatan'][] = ['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'jam'=>'', 'instruksi'=>''];
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('ri19/ri19_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RI 20 - RESUME PASIEN PULANG (RI) (R)
    // ============================================================
    public function getRi20manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('ri20_resume_pulang')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri20_resume_pulang.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri20_resume_pulang.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri20_resume_pulang.id')
            ->toArray();
        return $this->draw('ri20/ri20_manage.html', ['data' => $data]);
    }

    public function getRi20form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('kamar_inap.tgl_masuk')->desc('kamar_inap.jam_masuk')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('ri20_resume_pulang')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'ruang_rawat' => '', 'tgl_masuk' => '', 'tgl_keluar' => '',
            'anamnesis' => '', 'riwayat_penyakit' => '', 'pemeriksaan_fisik' => '', 'penemuan_klinik' => '',
            'diagnosa_utama' => '', 'icd_10_utama' => '', 'diagnosa_sekunder' => '', 'icd_10_sekunder' => '',
            'obat_selama_rs' => '', 'tindakan_selama_rs' => '', 'icd_9_tindakan' => '', 'kondisi_pulang' => '',
            'anjuran_kontrol' => '', 'alasan_pulang' => '', 'pulang_permintaan_text' => '',
            'terapi' => [['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'frekuensi'=>'', 'cara'=>'', 'jam1'=>'', 'jam2'=>'', 'jam3'=>'', 'jam4'=>'', 'jam5'=>'', 'jam6'=>'', 'petunjuk'=>'']],
            'nama_dokter' => '', 'nama_pasien_keluarga' => '', 'tgl_ttd' => '', 'jam_ttd' => ''
        ];
        $data['form_data'] = array_merge($default_fd, $fd);
        if (empty($data['form_data']['terapi'])) {
            $data['form_data']['terapi'] = [['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'frekuensi'=>'', 'cara'=>'', 'jam1'=>'', 'jam2'=>'', 'jam3'=>'', 'jam4'=>'', 'jam5'=>'', 'jam6'=>'', 'petunjuk'=>'']];
        }

        return $this->draw('ri20/ri20_form.html', ['data' => $data]);
    }

    public function postRi20save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('ri20_resume_pulang')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('ri20_resume_pulang')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri20manage']));
    }

    public function getRi20hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('ri20_resume_pulang')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri20manage']));
    }

    public function getRi20cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('ri20_resume_pulang')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri20_resume_pulang.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('ri20_resume_pulang.id', $id)
            ->select('ri20_resume_pulang.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }
        
        while(count($d['form_data']['terapi'] ?? []) < 10) {
            $d['form_data']['terapi'][] = ['nama'=>'', 'jumlah'=>'', 'dosis'=>'', 'frekuensi'=>'', 'cara'=>'', 'jam1'=>'', 'jam2'=>'', 'jam3'=>'', 'jam4'=>'', 'jam5'=>'', 'jam6'=>'', 'petunjuk'=>''];
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        $p['alamat'] = htmlspecialchars($d['alamat'] ?? '');
        
        echo $this->draw('ri20/ri20_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RJ - 02A HAK DAN KEWAJIBAN PASIEN
    // ============================================================
    public function getRj02amanage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('rj02a_hak_kewajiban')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj02a_hak_kewajiban.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('rj02a_hak_kewajiban.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('rj02a_hak_kewajiban.id')
            ->toArray();
        return $this->draw('rj02a/rj02a_manage.html', ['data' => $data]);
    }

    public function getRj02aform() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.alamat')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('rj02a_hak_kewajiban')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'tgl_ttd' => '', 'nama_pasien_pj' => '', 'pemberi_edukasi' => ''
        ];
        $data['form_data'] = array_merge($default_fd, $fd);

        return $this->draw('rj02a/rj02a_form.html', ['data' => $data]);
    }

    public function postRj02asave() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('rj02a_hak_kewajiban')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('rj02a_hak_kewajiban')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'rj02amanage']));
    }

    public function getRj02ahapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('rj02a_hak_kewajiban')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'rj02amanage']));
    }

    public function getRj02acetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('rj02a_hak_kewajiban')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj02a_hak_kewajiban.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('rj02a_hak_kewajiban.id', $id)
            ->select('rj02a_hak_kewajiban.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('rj02a/rj02a_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RJ - 07 ASESMEN ULANG NYERI
    // ============================================================
    public function getRj07manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('rj07_asesmen_ulang_nyeri')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj07_asesmen_ulang_nyeri.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('rj07_asesmen_ulang_nyeri.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('rj07_asesmen_ulang_nyeri.id')
            ->toArray();
        return $this->draw('rj07/rj07_manage.html', ['data' => $data]);
    }

    public function getRj07form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.alamat')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('rj07_asesmen_ulang_nyeri')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'asesmen' => [
                ['tgl_jam' => '', 'kondisi_umum' => '', 'skor_nyeri' => '', 'manajemen' => '', 'paraf_nama' => '']
            ]
        ];
        $data['form_data'] = array_merge($default_fd, $fd);
        if (empty($data['form_data']['asesmen'])) {
            $data['form_data']['asesmen'] = [['tgl_jam' => '', 'kondisi_umum' => '', 'skor_nyeri' => '', 'manajemen' => '', 'paraf_nama' => '']];
        }

        return $this->draw('rj07/rj07_form.html', ['data' => $data]);
    }

    public function postRj07save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('rj07_asesmen_ulang_nyeri')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('rj07_asesmen_ulang_nyeri')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'rj07manage']));
    }

    public function getRj07hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('rj07_asesmen_ulang_nyeri')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'rj07manage']));
    }

    public function getRj07cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('rj07_asesmen_ulang_nyeri')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj07_asesmen_ulang_nyeri.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('rj07_asesmen_ulang_nyeri.id', $id)
            ->select('rj07_asesmen_ulang_nyeri.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }

        while(count($d['form_data']['asesmen'] ?? []) < 4) {
            $d['form_data']['asesmen'][] = ['tgl_jam' => '', 'kondisi_umum' => '', 'skor_nyeri' => '', 'manajemen' => '', 'paraf_nama' => ''];
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('rj07/rj07_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RJ - 10 LEMBAR EDUKASI PASIEN DAN KELUARGA TERINTEGRASI
    // ============================================================
    public function getRj10manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('rj10_edukasi_terintegrasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj10_edukasi_terintegrasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('rj10_edukasi_terintegrasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('rj10_edukasi_terintegrasi.id')
            ->toArray();
        return $this->draw('rj10/rj10_manage.html', ['data' => $data]);
    }

    public function getRj10form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.pnd, pasien.bahasa_pasien, pasien.umur')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('rj10_edukasi_terintegrasi')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'hambatan_emosi' => false, 'hambatan_motivasi' => false, 'hambatan_tidak_ada' => false,
            'keyakinan_hari' => '', 'keyakinan_makan' => '', 'keyakinan_lawan_jenis' => false,
            'keterbatasan_fisik' => false, 'keterbatasan_kognitif' => false, 'keterbatasan_tidak_ada' => false,
            'kemampuan_membaca' => '', 'ketersediaan_edukasi' => '', 'pendidikan_terakhir' => '',
            'bahasa' => '', 'bahasa_lainnya' => '', 'penerjemah' => '', 'penerjemah_bahasa' => '',
            'kebutuhan_edukasi' => [], 'nama_perawat' => '', 'tgl_kaji' => '',
            'pelaksanaan' => [
                ['tgl_jam' => '', 'isi_kebutuhan' => '', 'pengetahuan_pra' => '', 'penerima' => '', 'penerima_lain' => '', 'metode' => '', 'evaluasi' => '', 'profesi_pemberi' => '', 'nama_pemberi' => '', 'nama_penerima' => '']
            ]
        ];
        $data['form_data'] = array_merge($default_fd, $fd);
        if (empty($data['form_data']['pelaksanaan'])) {
            $data['form_data']['pelaksanaan'] = [['tgl_jam' => '', 'isi_kebutuhan' => '', 'pengetahuan_pra' => '', 'penerima' => '', 'penerima_lain' => '', 'metode' => '', 'evaluasi' => '', 'profesi_pemberi' => '', 'nama_pemberi' => '', 'nama_penerima' => '']];
        }

        return $this->draw('rj10/rj10_form.html', ['data' => $data]);
    }

    public function postRj10save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('rj10_edukasi_terintegrasi')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('rj10_edukasi_terintegrasi')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'rj10manage']));
    }

    public function getRj10hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('rj10_edukasi_terintegrasi')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'rj10manage']));
    }

    public function getRj10cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('rj10_edukasi_terintegrasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj10_edukasi_terintegrasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('rj10_edukasi_terintegrasi.id', $id)
            ->select('rj10_edukasi_terintegrasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }

        while(count($d['form_data']['pelaksanaan'] ?? []) < 5) {
            $d['form_data']['pelaksanaan'][] = ['tgl_jam' => '', 'isi_kebutuhan' => '', 'pengetahuan_pra' => '', 'penerima' => '', 'penerima_lain' => '', 'metode' => '', 'evaluasi' => '', 'profesi_pemberi' => '', 'nama_pemberi' => '', 'nama_penerima' => ''];
        }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('rj10/rj10_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // RM.RJ - 11 PEMBERIAN INFORMASI
    // ============================================================
    public function getRj11manage() {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $data = $this->db('rj11_pemberian_informasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj11_pemberian_informasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('rj11_pemberian_informasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('rj11_pemberian_informasi.id')
            ->toArray();
        return $this->draw('rj11/rj11_manage.html', ['data' => $data]);
    }

    public function getRj11form() {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $result = ['success' => false];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.umur, pasien.jk, pasien.alamat')
                ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')
                ->oneArray();
            if ($reg) { $result = array_merge(['success' => true], $reg); }
            ob_clean(); echo json_encode($result); exit();
        }

        $data = ['id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '', 'form_data' => '{}'];

        if ($id = $_GET['id'] ?? '') {
            $row = $this->db('rj11_pemberian_informasi')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien')->oneArray();
                if ($reg) { $data = array_merge($data, $reg); }
            }
        }
        
        $fd = !empty($data['form_data']) && is_string($data['form_data']) ? json_decode($data['form_data'], true) : [];
        if (!is_array($fd)) { $fd = []; }
        $default_fd = [
            'pemberi_informasi' => '', 'penerima_informasi' => '',
            'info_1_isi' => '', 'info_1_tanda' => false,
            'info_2_isi' => '', 'info_2_tanda' => false,
            'info_3_isi' => '', 'info_3_tanda' => false,
            'info_4_isi' => '', 'info_4_tanda' => false,
            'info_5_isi' => '', 'info_5_tanda' => false,
            'info_6_isi' => '', 'info_6_tanda' => false,
            'info_7_isi' => '', 'info_7_tanda' => false,
            'info_8_isi' => '', 'info_8_tanda' => false,
            'info_9_isi' => '', 'info_9_tanda' => false,
            'info_10_isi' => '', 'info_10_tanda' => false,
            'info_lain_isi' => '', 'info_lain_tanda' => false,
            'ttd_pemberi' => '', 'ttd_penerima' => '',
            
            'setuju_nama' => '', 'setuju_umur' => '', 'setuju_jk' => '', 'setuju_alamat' => '',
            'setuju_tindakan' => '',
            'setuju_terhadap_nama' => '', 'setuju_terhadap_umur' => '', 'setuju_terhadap_jk' => '', 'setuju_terhadap_alamat' => '',
            'setuju_kota' => 'Banjarmasin', 'setuju_tgl' => '',
            'setuju_menyatakan' => '', 'setuju_saksi1' => '', 'setuju_saksi2' => '',
            
            'tolak_nama' => '', 'tolak_umur' => '', 'tolak_jk' => '', 'tolak_alamat' => '',
            'tolak_tindakan' => '',
            'tolak_terhadap_nama' => '', 'tolak_terhadap_umur' => '', 'tolak_terhadap_jk' => '', 'tolak_terhadap_alamat' => '',
            'tolak_kota' => 'Banjarmasin', 'tolak_tgl' => '',
            'tolak_menyatakan' => '', 'tolak_saksi1' => '', 'tolak_saksi2' => ''
        ];
        $data['form_data'] = array_merge($default_fd, $fd);

        return $this->draw('rj11/rj11_form.html', ['data' => $data]);
    }

    public function postRj11save() {
        $id = $_POST['id'] ?? '';
        $no_rawat = $_POST['no_rawat'] ?? '';
        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) { $no_rawat = $reg['no_rawat']; }
        }
        $save = [
            'no_rawat' => $no_rawat,
            'form_data' => $_POST['json_form_data'] ?? '{}'
        ];

        if ($id) { $this->db('rj11_pemberian_informasi')->where('id', $id)->save($save); $this->notify('success', 'Data diupdate'); }
        else { $this->db('rj11_pemberian_informasi')->save($save); $this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'rj11manage']));
    }

    public function getRj11hapus() {
        if ($id = $_GET['id'] ?? '') { $this->db('rj11_pemberian_informasi')->where('id', $id)->delete(); $this->notify('success', 'Data dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'rj11manage']));
    }

    public function getRj11cetak() {
        if (!$id = $_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        $d = $this->db('rj11_pemberian_informasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = rj11_pemberian_informasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('rj11_pemberian_informasi.id', $id)
            ->select('rj11_pemberian_informasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();
        if (!$d) { exit("Data tidak ditemukan"); }

        $d['form_data'] = !empty($d['form_data']) && is_string($d['form_data']) ? json_decode($d['form_data'], true) : [];
        if (!is_array($d['form_data'])) { $d['form_data'] = []; }

        $p = [];
        $rm = str_pad((string)($d['no_rkm_medis'] ?? ''), 6, ' ', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) { $p['rm' . $i] = trim($rm[$i]) === '' ? '&nbsp;' : $rm[$i]; }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');
        $p['tgl_lahir'] = !empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00' ? date('d-m-Y', strtotime($d['tgl_lahir'])) : '';
        
        echo $this->draw('rj11/rj11_cetak.html', ['p' => $p, 'd' => $d]); exit();
    }

    // ============================================================
    // SURAT PERNYATAAN PULANG APS
    // ============================================================

    public function anyPulangapsmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('surat_pulang_aps')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = surat_pulang_aps.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('surat_pulang_aps.id')
            ->toArray();

        return $this->draw('pulangaps/manage.html', ['data' => $data]);
    }

    public function getPulangapsform()
    {
        if (isset($_GET['ajax_pegawai'])) {
            $q = $_GET['q'] ?? '';
            $d1 = $this->db('dokter')->like('nm_dokter', "%$q%")->orLike('kd_dokter', "%$q%")->limit(10)->select('kd_dokter as id, nm_dokter as text')->toArray();
            $d2 = $this->db('petugas')->like('nama', "%$q%")->orLike('nip', "%$q%")->limit(10)->select('nip as id, nama as text')->toArray();
            ob_clean(); echo json_encode(['results' => array_merge($d1, $d2)]); exit();
        }

        if (isset($_GET['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? '';
            $p = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                ->oneArray();
            if ($p) {
                ob_clean(); echo json_encode(array_merge(['success' => true, 'no_rawat' => $no_rawat], $p)); exit();
            } else {
                ob_clean(); echo json_encode(['success' => false]); exit();
            }
        }

        $id = $_GET['id'] ?? '';
        $data = [];
        if ($id) {
            $data = $this->db('surat_pulang_aps')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = surat_pulang_aps.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk as jk_pasien, pasien.tgl_lahir as tgl_lahir_pasien')
                ->where('surat_pulang_aps.id', $id)
                ->oneArray();
        }
        return $this->draw('pulangaps/form.html', ['data' => $data]);
    }

    public function postPulangapssave()
    {
        $id = $_POST['id'] ?? '';
        $data = [
            'no_rawat' => $_POST['no_rawat'],
            'nama_pihak' => $_POST['nama_pihak'],
            'jk_pihak' => $_POST['jk_pihak'],
            'no_identitas_pihak' => $_POST['no_identitas_pihak'],
            'alamat_pihak' => $_POST['alamat_pihak'],
            'selaku_pihak' => $_POST['selaku_pihak'],
            'alasan_pulang' => $_POST['alasan_pulang'],
            'tgl_surat' => $_POST['tgl_surat'],
            'saksi_rs' => $_POST['saksi_rs'],
            'saksi_pasien' => $_POST['saksi_pasien'],
            'pembuat_pernyataan' => $_POST['pembuat_pernyataan'],
        ];

        if ($id) {
            $this->db('surat_pulang_aps')->where('id', $id)->save($data);
        } else {
            $this->db('surat_pulang_aps')->save($data);
        }

        $this->notify('success', 'Simpan data berhasil');
        redirect(url([ADMIN, 'update_bmt', 'pulangapsmanage']));
    }

    public function getPulangapshapus()
    {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $this->db('surat_pulang_aps')->where('id', $id)->delete();
            $this->notify('success', 'Hapus data berhasil');
        }
        redirect(url([ADMIN, 'update_bmt', 'pulangapsmanage']));
    }

    public function getPulangapscetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) exit('ID tidak ada');
        
        $data = $this->db('surat_pulang_aps')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = surat_pulang_aps.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk as jk_pasien, pasien.tgl_lahir as tgl_lahir_pasien, pasien.umur')
            ->where('surat_pulang_aps.id', $id)
            ->oneArray();
        
        if (!$data) exit('Data tidak ditemukan');

        // Fetch diagnosa from pemeriksaan_ralan or pemeriksaan_ranap
        $diagnosa = '';
        $dx = $this->db('pemeriksaan_ranap')->where('no_rawat', $data['no_rawat'])->select('penilaian')->oneArray();
        if ($dx) {
            $diagnosa = $dx['penilaian'];
        } else {
            $dx2 = $this->db('pemeriksaan_ralan')->where('no_rawat', $data['no_rawat'])->select('penilaian')->oneArray();
            if ($dx2) {
                $diagnosa = $dx2['penilaian'];
            }
        }
        $data['diagnosa'] = $diagnosa;
        
        $bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        if (!empty($data['tgl_surat'])) {
            $ts = explode('-', $data['tgl_surat']);
            $data['tgl_surat_indo'] = (int)$ts[2] . ' ' . $bulan[(int)$ts[1]] . ' ' . $ts[0];
        } else {
            $data['tgl_surat_indo'] = '';
        }

        echo $this->draw('pulangaps/cetak.html', ['data' => $data]);
        exit();
    }
}

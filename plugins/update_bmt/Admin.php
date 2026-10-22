<?php
namespace Plugins\Update_bmt;

use Systems\AdminModule;

class Admin extends AdminModule
{
    // ==========================================
    // LAMPIRAN - ASESMEN MEDIS UMUM IRNA
    // ==========================================

    public function anyAmumanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('asesmen_medis_umum_irna')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'amumanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('asesmen_medis_umum_irna')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = asesmen_medis_umum_irna.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('asesmen_medis_umum_irna.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
            ->toArray();

        return $this->draw('asesmen_medis_umum_irna/manage.html', ['data' => $data]);
    }

    public function getAmuform()
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat, pasien.umur')
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
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'sumber_data' => '', 'kategori_pasien' => '',
            'tanggal_masuk' => '', 'waktu_masuk' => '', 'keluhan_utama' => '', 'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '', 'riwayat_penyakit_keluarga' => '', 'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_keterangan' => '', 'obat_rutin' => '', 'penyakit_menular' => '',
            'penyakit_jantung' => 'Tidak Ada', 'hipertensi' => 'Tidak Ada', 'diabetes' => 'Tidak Ada',
            'haemophilia' => 'Tidak Ada', 'gangguan_ginjal' => 'Tidak Ada', 'gangguan_ginjal_ket' => '',
            'penyakit_sistemik_lainnya' => 'Tidak Ada', 'penyakit_sistemik_lainnya_ket' => '',
            'riwayat_operasi' => 'Tidak Ada', 'riwayat_operasi_ket' => '', 'anak_ke' => '', 'dari_saudara' => '',
            'cara_kelahiran' => '', 'umur_kelahiran' => '', 'kelainan_bawaan' => '', 'kelainan_bawaan_ket' => '',
            'imunisasi_bcg' => '', 'imunisasi_polio' => '', 'imunisasi_dpt' => '', 'imunisasi_campak' => '',
            'imunisasi_hepatitis_b' => '', 'imunisasi_lainnya' => '', 'tumbuh_gigi_pertama' => '',
            'mulai_bisa_duduk' => '', 'berjalan_sendiri' => '', 'mulai_bisa_bicara' => '', 'mulai_bisa_membaca' => '',
            'geriatri_penglihatan' => '', 'geriatri_pendengaran' => '', 'geriatri_berkemih' => '',
            'geriatri_daya_ingat' => '', 'geriatri_bicara' => '', 'geriatri_gigi_palsu' => '', 'geriatri_lainnya' => '',
            'keadaan_umum' => '', 'kesadaran' => '', 'tekanan_darah' => '', 'frekuensi_nadi' => '',
            'frekuensi_napas' => '', 'suhu' => '', 'berat_badan' => '', 'tinggi_badan' => '', 'golongan_darah' => '',
            'nyeri_keluhan' => 'Tidak', 'nyeri_lokasi' => '', 'nyeri_durasi' => '', 'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '', 'nyeri_menjalar' => 'Tidak', 'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '', 'nyeri_hilang' => '', 'nyeri_tindak_lanjut' => '', 'nyeri_konsul_ke' => '',
            'skala_nyeri' => '', 'penunjang_lab' => '', 'penunjang_rontgen' => '', 'penunjang_lainnya' => '',
            'diagnosis' => '', 'rencana_tindak_lanjut' => '', 'tanggal_ttd' => '', 'jam_ttd' => '', 'nip_dpjp' => ''
        ];

        if (!empty($id)) {
            $db_data = $this->db('asesmen_medis_umum_irna')->where('id', $id)->oneArray();
            if ($db_data) { $data = array_merge($data, $db_data); }
            if ($data && !empty($data['no_rawat']) && empty($data['nm_pasien'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
                    ->oneArray();

                if ($reg) {
                    $data['nm_pasien'] = $reg['nm_pasien'];
                }
            }
        }
        
        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('asesmen_medis_umum_irna/form.html', ['data' => $data, 'dokter' => $dokter]);
    }

    public function postAmusave()
    {
        try {
            $post = $_POST;
            $id = $post['id'] ?? '';

            $data = [
                'no_rawat' => $post['no_rawat'] ?? '',
                'sumber_data' => $post['sumber_data'] ?? '',
                'kategori_pasien' => $post['kategori_pasien'] ?? '',
                'tanggal_masuk' => $post['tanggal_masuk'] ?? '',
                'waktu_masuk' => $post['waktu_masuk'] ?? '',
                'keluhan_utama' => $post['keluhan_utama'] ?? '',
                'riwayat_penyakit_sekarang' => $post['riwayat_penyakit_sekarang'] ?? '',
                'riwayat_penyakit_dahulu' => $post['riwayat_penyakit_dahulu'] ?? '',
                'riwayat_penyakit_keluarga' => $post['riwayat_penyakit_keluarga'] ?? '',
                'riwayat_alergi' => $post['riwayat_alergi'] ?? 'Tidak',
                'riwayat_alergi_keterangan' => $post['riwayat_alergi_keterangan'] ?? '',
                'obat_rutin' => $post['obat_rutin'] ?? '',
                'penyakit_menular' => $post['penyakit_menular'] ?? '',
                'penyakit_jantung' => $post['penyakit_jantung'] ?? 'Tidak Ada',
                'hipertensi' => $post['hipertensi'] ?? 'Tidak Ada',
                'diabetes' => $post['diabetes'] ?? 'Tidak Ada',
                'haemophilia' => $post['haemophilia'] ?? 'Tidak Ada',
                'gangguan_ginjal' => $post['gangguan_ginjal'] ?? 'Tidak Ada',
                'gangguan_ginjal_ket' => $post['gangguan_ginjal_ket'] ?? '',
                'penyakit_sistemik_lainnya' => $post['penyakit_sistemik_lainnya'] ?? 'Tidak Ada',
                'penyakit_sistemik_lainnya_ket' => $post['penyakit_sistemik_lainnya_ket'] ?? '',
                'riwayat_operasi' => $post['riwayat_operasi'] ?? 'Tidak Ada',
                'riwayat_operasi_ket' => $post['riwayat_operasi_ket'] ?? '',
                'anak_ke' => $post['anak_ke'] ?: null,
                'dari_saudara' => $post['dari_saudara'] ?: null,
                'cara_kelahiran' => $post['cara_kelahiran'] ?? '',
                'umur_kelahiran' => $post['umur_kelahiran'] ?? '',
                'kelainan_bawaan' => $post['kelainan_bawaan'] ?? '',
                'imunisasi_bcg' => isset($post['imunisasi_bcg']) ? 1 : 0,
                'imunisasi_polio' => $post['imunisasi_polio'] ?: null,
                'imunisasi_dpt' => $post['imunisasi_dpt'] ?: null,
                'imunisasi_campak' => $post['imunisasi_campak'] ?: null,
                'imunisasi_hepatitis_b' => $post['imunisasi_hepatitis_b'] ?: null,
                'imunisasi_lainnya' => $post['imunisasi_lainnya'] ?: null,
                'tumbuh_gigi_pertama' => $post['tumbuh_gigi_pertama'] ?? '',
                'mulai_bisa_duduk' => $post['mulai_bisa_duduk'] ?? '',
                'berjalan_sendiri' => $post['berjalan_sendiri'] ?? '',
                'mulai_bisa_bicara' => $post['mulai_bisa_bicara'] ?? '',
                'mulai_bisa_membaca' => $post['mulai_bisa_membaca'] ?? '',
                'geriatri_penglihatan' => isset($post['geriatri_penglihatan']) ? 1 : 0,
                'geriatri_pendengaran' => isset($post['geriatri_pendengaran']) ? 1 : 0,
                'geriatri_berkemih' => isset($post['geriatri_berkemih']) ? 1 : 0,
                'geriatri_daya_ingat' => isset($post['geriatri_daya_ingat']) ? 1 : 0,
                'geriatri_bicara' => isset($post['geriatri_bicara']) ? 1 : 0,
                'geriatri_gigi_palsu' => isset($post['geriatri_gigi_palsu']) ? 1 : 0,
                'geriatri_lainnya' => $post['geriatri_lainnya'] ?? '',
                'keadaan_umum' => $post['keadaan_umum'] ?? '',
                'kesadaran' => $post['kesadaran'] ?? '',
                'tekanan_darah' => $post['tekanan_darah'] ?? '',
                'frekuensi_nadi' => $post['frekuensi_nadi'] ?? '',
                'frekuensi_napas' => $post['frekuensi_napas'] ?? '',
                'suhu' => $post['suhu'] ?? '',
                'berat_badan' => $post['berat_badan'] ?? '',
                'tinggi_badan' => $post['tinggi_badan'] ?? '',
                'golongan_darah' => $post['golongan_darah'] ?? '',
                'nyeri_keluhan' => $post['nyeri_keluhan'] ?? 'Tidak',
                'nyeri_lokasi' => $post['nyeri_lokasi'] ?? '',
                'nyeri_durasi' => $post['nyeri_durasi'] ?? '',
                'nyeri_frekuensi' => $post['nyeri_frekuensi'] ?? '',
                'nyeri_karakteristik' => $post['nyeri_karakteristik'] ?? '',
                'nyeri_menjalar' => $post['nyeri_menjalar'] ?? 'Tidak',
                'nyeri_menjalar_ket' => $post['nyeri_menjalar_ket'] ?? '',
                'nyeri_memberat' => $post['nyeri_memberat'] ?? '',
                'nyeri_hilang' => $post['nyeri_hilang'] ?? '',
                'nyeri_tindak_lanjut' => $post['nyeri_tindak_lanjut'] ?? '',
                'nyeri_konsul_ke' => $post['nyeri_konsul_ke'] ?? '',
                'skala_nyeri' => $post['skala_nyeri'] ?: null,
                'penunjang_lab' => isset($post['penunjang_lab']) ? 1 : 0,
                'penunjang_rontgen' => isset($post['penunjang_rontgen']) ? 1 : 0,
                'penunjang_lainnya' => $post['penunjang_lainnya'] ?? '',
                'diagnosis' => $post['diagnosis'] ?? '',
                'rencana_tindak_lanjut' => $post['rencana_tindak_lanjut'] ?? '',
                'tanggal_ttd' => !empty($post['waktu_ttd']) ? explode('T', $post['waktu_ttd'])[0] : '',
                'jam_ttd' => !empty($post['waktu_ttd']) ? explode('T', $post['waktu_ttd'])[1] : '',
                'nip_dpjp' => $post['nip_dpjp'] ?? ''
            ];

            if ($id) {
                $this->db('asesmen_medis_umum_irna')->where('id', $id)->save($data);
                $this->notify('success', 'Data berhasil diupdate');
            } else {
                $this->db('asesmen_medis_umum_irna')->save($data);
                $this->notify('success', 'Data berhasil disimpan');
            }

            redirect(url([ADMIN, 'update_bmt', 'amumanage']));
        } catch (\Exception $e) {
            die("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    public function anyAmuhapus()
    {
        $id = $_REQUEST['id'] ?? '';
        if ($id) {
            $this->db('asesmen_medis_umum_irna')->where('id', $id)->delete();
            $this->notify('success', 'Data berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'amumanage']));
    }

    public function getAmucetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('asesmen_medis_umum_irna')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = asesmen_medis_umum_irna.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_medis_umum_irna.nip_dpjp')
            ->where('asesmen_medis_umum_irna.id', $id)
            ->select('asesmen_medis_umum_irna.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, dokter.nm_dokter')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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
        
        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('asesmen_medis_umum_irna/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('AMU_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
    }

    // ==========================================
    // LAMPIRAN - CAIRAN INFUS (RANAP)
    // ==========================================

    public function anyCairaninfusmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_cairan_infus')->where('id', $id)->delete();
            $this->notify('success', 'Data Lampiran Cairan Infus berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'cairaninfusmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_cairan_infus')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_cairan_infus.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_cairan_infus.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
            ->desc('lampiran_cairan_infus.id')
            ->toArray();

        foreach ($rows as &$r) {
            $details = !empty($r['detail_cairan']) ? json_decode($r['detail_cairan'], true) : [];
            $r['total_botol'] = is_array($details) ? count($details) : 0;
            if (empty($r['tanggal'])) {
                $r['tanggal'] = '-';
            }
        }

        return $this->draw('lampiran_cairan_infus/manage.html', ['data' => $rows]);
    }

    public function getCairaninfusform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->leftJoin('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, bangsal.nm_bangsal, kamar.kd_kamar, dokter.nm_dokter')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'ruangan' => '',
            'kd_dokter' => '',
            'dokter' => '',
            'tanggal' => date('Y-m-d'),
            'detail_cairan' => '[]',
            'detail_cairan_parsed' => []
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_cairan_infus')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $data['detail_cairan_parsed'] = !empty($data['detail_cairan']) ? json_decode($data['detail_cairan'], true) : [];
        if (!is_array($data['detail_cairan_parsed'])) {
            $data['detail_cairan_parsed'] = [];
        }

        $dokter_list = $this->db('dokter')->select('kd_dokter, nm_dokter')->toArray();
        $petugas_list = $this->db('petugas')->limit(50)->select('nip, nama')->toArray();
        $current_user_name = $this->core->getUserInfo('fullname') ?? '';

        return $this->draw('lampiran_cairan_infus/form.html', [
            'data' => $data,
            'dokter_list' => $dokter_list,
            'petugas_list' => $petugas_list,
            'current_user_name' => $current_user_name
        ]);
    }

    public function postCairaninfussave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'ruangan' => trim($_POST['ruangan'] ?? ''),
            'dokter' => trim($_POST['dokter'] ?? ''),
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'detail_cairan' => $_POST['detail_cairan'] ?? '[]'
        ];

        if (!empty($id)) {
            $this->db('lampiran_cairan_infus')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Lampiran Cairan Infus berhasil diperbarui');
        } else {
            $this->db('lampiran_cairan_infus')->save($saveData);
            $this->notify('success', 'Data Lampiran Cairan Infus berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'cairaninfusmanage']));
    }

    public function getCairaninfushapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_cairan_infus')->where('id', $id)->delete();
            $this->notify('success', 'Data Lampiran Cairan Infus berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'cairaninfusmanage']));
    }

    public function getCairaninfuscetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_cairan_infus')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_cairan_infus.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_cairan_infus.id', $id)
            ->select('lampiran_cairan_infus.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;
        $data['jk_label'] = ($data['jk'] ?? '') == 'L' ? 'L' : (($data['jk'] ?? '') == 'P' ? 'P' : '');

        if (!empty($data['tgl_lahir'])) {
            $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tanggal_lahir_indo'] = '-';
        }

        $items = !empty($data['detail_cairan']) ? json_decode($data['detail_cairan'], true) : [];
        if (!is_array($items)) {
            $items = [];
        }

        // Tampilkan 25 baris persis seperti form fisik formulir
        $rendered_rows = [];
        $totalRowsToDisplay = max(25, count($items));
        for ($i = 0; $i < $totalRowsToDisplay; $i++) {
            if (isset($items[$i])) {
                $item = $items[$i];
                $tglFormatted = !empty($item['tanggal']) ? date('d-m-Y', strtotime($item['tanggal'])) : '';
                $rendered_rows[] = [
                    'tanggal' => $tglFormatted,
                    'botol' => $item['botol'] ?? '',
                    'cairan_infus' => $item['cairan_infus'] ?? '',
                    'jumlah_tetesan' => $item['jumlah_tetesan'] ?? '',
                    'jam_mulai' => $item['jam_mulai'] ?? '',
                    'tgl_jam_stop' => $item['tgl_jam_stop'] ?? '',
                    'keterangan' => $item['keterangan'] ?? '',
                    'nama' => $item['nama'] ?? ''
                ];
            } else {
                $rendered_rows[] = [
                    'tanggal' => '&nbsp;',
                    'botol' => '&nbsp;',
                    'cairan_infus' => '&nbsp;',
                    'jumlah_tetesan' => '&nbsp;',
                    'jam_mulai' => '&nbsp;',
                    'tgl_jam_stop' => '&nbsp;',
                    'keterangan' => '&nbsp;',
                    'nama' => '&nbsp;'
                ];
            }
        }
        $data['rendered_rows'] = $rendered_rows;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_cairan_infus/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Lampiran_Cairan_Infus_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_cairan_infus/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ==========================================
    // LAMPIRAN - FORM A MANAJER PELAYANAN PASIEN (RANAP)
    // ==========================================

    public function anyFormamppmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_form_a_mpp')->where('id', $id)->delete();
            $this->notify('success', 'Data Lampiran - Form A Manajer Pelayanan Pasien berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'formamppmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_form_a_mpp')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_form_a_mpp.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_form_a_mpp.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
            ->desc('lampiran_form_a_mpp.id')
            ->toArray();

        foreach ($rows as &$r) {
            $cnt = 0;
            $skrining_fields = [
                'skrining_risiko_tinggi', 'skrining_potensi_komplain', 'skrining_kognitif_rendah',
                'skrining_penyakit_kronis', 'skrining_gangguan_mental', 'skrining_biaya_tinggi',
                'skrining_sistem_pembiayaan', 'skrining_los_memanjang', 'skrining_rencana_pemulangan',
                'skrining_multiple_diagnosis', 'skrining_kasus_kompleks'
            ];
            foreach ($skrining_fields as $f) {
                if (!empty($r[$f]) && $r[$f] == 'Ya') {
                    $cnt++;
                }
            }
            $r['total_skrining'] = $cnt;
        }

        return $this->draw('lampiran_form_a_mpp/manage.html', ['data' => $rows]);
    }

    public function getFormamppform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'tgl_hari' => date('d-m-Y'),
            'nama_mpp' => '',
            'nip_mpp' => '',
            'skrining_risiko_tinggi' => 'Tidak',
            'skrining_potensi_komplain' => 'Tidak',
            'skrining_kognitif_rendah' => 'Tidak',
            'skrining_penyakit_kronis' => 'Tidak',
            'skrining_gangguan_mental' => 'Tidak',
            'skrining_biaya_tinggi' => 'Tidak',
            'skrining_sistem_pembiayaan' => 'Tidak',
            'skrining_los_memanjang' => 'Tidak',
            'skrining_rencana_pemulangan' => 'Tidak',
            'skrining_multiple_diagnosis' => 'Tidak',
            'skrining_kasus_kompleks' => 'Tidak',
            'fisik_fungsional' => '',
            'kognitif' => '',
            'pemahaman_kesehatan' => '',
            'kemampuan_menerima_perubahan' => '',
            'dukungan_keluarga' => '',
            'kepatuhan_pasien' => '',
            'perencanaan_lanjutan' => '',
            'finansial_status' => '',
            'asesmen_lain_lain' => '',
            'masalah_biaya' => '',
            'masalah_keluarga' => '',
            'masalah_pemulangan' => '',
            'masalah_pemahaman' => '',
            'masalah_ketidakpatuhan' => '',
            'rencana_edukasi_ppa' => 'Tidak',
            'rencana_koordinasi_bpjs' => 'Tidak',
            'rencana_koordinasi_dpjp' => 'Tidak',
            'rencana_lain_4' => '',
            'rencana_lain_5' => '',
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d')
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_form_a_mpp')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_form_a_mpp/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postFormamppsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $skrining_fields = [
            'skrining_risiko_tinggi', 'skrining_potensi_komplain', 'skrining_kognitif_rendah',
            'skrining_penyakit_kronis', 'skrining_gangguan_mental', 'skrining_biaya_tinggi',
            'skrining_sistem_pembiayaan', 'skrining_los_memanjang', 'skrining_rencana_pemulangan',
            'skrining_multiple_diagnosis', 'skrining_kasus_kompleks'
        ];

        $saveData = [
            'no_rawat' => $no_rawat,
            'tgl_hari' => trim($_POST['tgl_hari'] ?? ''),
            'nama_mpp' => trim($_POST['nama_mpp'] ?? ''),
            'nip_mpp' => trim($_POST['nip_mpp'] ?? ''),
            'fisik_fungsional' => trim($_POST['fisik_fungsional'] ?? ''),
            'kognitif' => trim($_POST['kognitif'] ?? ''),
            'pemahaman_kesehatan' => trim($_POST['pemahaman_kesehatan'] ?? ''),
            'kemampuan_menerima_perubahan' => trim($_POST['kemampuan_menerima_perubahan'] ?? ''),
            'dukungan_keluarga' => trim($_POST['dukungan_keluarga'] ?? ''),
            'kepatuhan_pasien' => trim($_POST['kepatuhan_pasien'] ?? ''),
            'perencanaan_lanjutan' => trim($_POST['perencanaan_lanjutan'] ?? ''),
            'finansial_status' => trim($_POST['finansial_status'] ?? ''),
            'asesmen_lain_lain' => trim($_POST['asesmen_lain_lain'] ?? ''),
            'masalah_biaya' => trim($_POST['masalah_biaya'] ?? ''),
            'masalah_keluarga' => trim($_POST['masalah_keluarga'] ?? ''),
            'masalah_pemulangan' => trim($_POST['masalah_pemulangan'] ?? ''),
            'masalah_pemahaman' => trim($_POST['masalah_pemahaman'] ?? ''),
            'masalah_ketidakpatuhan' => trim($_POST['masalah_ketidakpatuhan'] ?? ''),
            'rencana_edukasi_ppa' => (isset($_POST['rencana_edukasi_ppa']) && $_POST['rencana_edukasi_ppa'] == 'Ya') ? 'Ya' : 'Tidak',
            'rencana_koordinasi_bpjs' => (isset($_POST['rencana_koordinasi_bpjs']) && $_POST['rencana_koordinasi_bpjs'] == 'Ya') ? 'Ya' : 'Tidak',
            'rencana_koordinasi_dpjp' => (isset($_POST['rencana_koordinasi_dpjp']) && $_POST['rencana_koordinasi_dpjp'] == 'Ya') ? 'Ya' : 'Tidak',
            'rencana_lain_4' => trim($_POST['rencana_lain_4'] ?? ''),
            'rencana_lain_5' => trim($_POST['rencana_lain_5'] ?? ''),
            'kota' => trim($_POST['kota'] ?? 'Banjarmasin'),
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d')
        ];

        foreach ($skrining_fields as $f) {
            $saveData[$f] = (isset($_POST[$f]) && $_POST[$f] == 'Ya') ? 'Ya' : 'Tidak';
        }

        if (!empty($id)) {
            $this->db('lampiran_form_a_mpp')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Lampiran - Form A MPP berhasil diperbarui');
        } else {
            $this->db('lampiran_form_a_mpp')->save($saveData);
            $this->notify('success', 'Data Lampiran - Form A MPP berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'formamppmanage']));
    }

    public function getFormampphapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_form_a_mpp')->where('id', $id)->delete();
            $this->notify('success', 'Data Lampiran - Form A MPP berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'formamppmanage']));
    }

    public function getFormamppcetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_form_a_mpp')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_form_a_mpp.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_form_a_mpp.id', $id)
            ->select('lampiran_form_a_mpp.*, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;
        $data['jk_label'] = ($data['jk'] ?? '') == 'L' ? 'L' : (($data['jk'] ?? '') == 'P' ? 'P' : '');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir'])) {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tanggal_lahir_indo'] = '-';
        }

        if (!empty($data['tanggal_ttd'])) {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_form_a_mpp/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Lampiran_Form_A_MPP_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_form_a_mpp/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ==========================================
    // LAMPIRAN - FORM ASESMEN ULANG STATUS FUNGSIONAL (RANAP)
    // ==========================================

    public function anyStatusfungsionalulangmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_asesmen_ulang_status_fungsional')->where('id', $id)->delete();
            $this->notify('success', 'Data Lampiran - Form Asesmen Ulang Status Fungsional berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'statusfungsionalulangmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_asesmen_ulang_status_fungsional')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_asesmen_ulang_status_fungsional.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_asesmen_ulang_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
            ->desc('lampiran_asesmen_ulang_status_fungsional.id')
            ->toArray();

        return $this->draw('lampiran_asesmen_ulang_status_fungsional/manage.html', ['data' => $rows]);
    }

    public function getStatusfungsionalulangform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, bangsal.nm_bangsal, kamar.kd_kamar')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'ruangan' => '',
            'tanggal' => date('Y-m-d'),
            'jam' => date('H:i'),
            'total_skor' => '',
            'kategori' => '',
            'petugas' => '',
            'nip_petugas' => ''
        ];
        for ($i = 1; $i <= 10; $i++) {
            $data['s' . $i] = '';
        }

        if (!empty($id)) {
            $row = $this->db('lampiran_asesmen_ulang_status_fungsional')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $data['jam'] = substr((string)$data['jam'], 0, 5);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_asesmen_ulang_status_fungsional/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postStatusfungsionalulangsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'ruangan' => trim($_POST['ruangan'] ?? ''),
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'jam' => !empty($_POST['jam']) ? $_POST['jam'] : date('H:i:s'),
            'petugas' => trim($_POST['petugas'] ?? ''),
            'nip_petugas' => trim($_POST['nip_petugas'] ?? '')
        ];

        $total = 0;
        $filled = false;
        for ($i = 1; $i <= 10; $i++) {
            $val = $_POST['s' . $i] ?? '';
            if ($val === '' || $val === null) {
                $saveData['s' . $i] = null;
            } else {
                $saveData['s' . $i] = (int)$val;
                $total += (int)$val;
                $filled = true;
            }
        }

        $saveData['total_skor'] = $filled ? $total : null;
        if ($filled) {
            if ($total >= 20) {
                $saveData['kategori'] = 'Mandiri';
            } elseif ($total >= 12) {
                $saveData['kategori'] = 'Ketergantungan Ringan';
            } elseif ($total >= 9) {
                $saveData['kategori'] = 'Ketergantungan Sedang';
            } elseif ($total >= 5) {
                $saveData['kategori'] = 'Ketergantungan Berat';
            } else {
                $saveData['kategori'] = 'Ketergantungan Total';
            }
        } else {
            $saveData['kategori'] = '';
        }

        if (!empty($id)) {
            $this->db('lampiran_asesmen_ulang_status_fungsional')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Asesmen Ulang Status Fungsional berhasil diperbarui');
        } else {
            $this->db('lampiran_asesmen_ulang_status_fungsional')->save($saveData);
            $this->notify('success', 'Data Asesmen Ulang Status Fungsional berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'statusfungsionalulangmanage']));
    }

    public function getStatusfungsionalulanghapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_asesmen_ulang_status_fungsional')->where('id', $id)->delete();
            $this->notify('success', 'Data Asesmen Ulang Status Fungsional berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'statusfungsionalulangmanage']));
    }

    public function getStatusfungsionalulangcetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $d = $this->db('lampiran_asesmen_ulang_status_fungsional')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_asesmen_ulang_status_fungsional.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_asesmen_ulang_status_fungsional.id', $id)
            ->select('lampiran_asesmen_ulang_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$d) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($d['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $p = [];
        $p['id'] = $d['id'];
        $p['no_rawat'] = $d['no_rawat'];
        for ($i = 0; $i < 6; $i++) {
            $p['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $p['nm_pasien'] = htmlspecialchars($d['nm_pasien'] ?? '');
        $p['lp'] = ($d['jk'] ?? '') == 'L' ? 'L' : (($d['jk'] ?? '') == 'P' ? 'P' : 'L/P');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($d['tgl_lahir']) && $d['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $d['tgl_lahir']);
            if (count($tgl) == 3) {
                $p['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $p['tgl_lahir_indo'] = $d['tgl_lahir'];
            }
        } else {
            $p['tgl_lahir_indo'] = '-';
        }

        $p['ruangan'] = htmlspecialchars($d['ruangan'] ?? '');
        $tglJam = '';
        if (!empty($d['tanggal'])) {
            $tglJam .= date('d-m-Y', strtotime($d['tanggal']));
        }
        if (!empty($d['jam'])) {
            $tglJam .= ' ' . substr((string)$d['jam'], 0, 5);
        }
        $p['tanggal_jam'] = $tglJam;
        $p['total'] = $d['total_skor'] !== null ? $d['total_skor'] : '';
        $p['kategori'] = htmlspecialchars($d['kategori'] ?? '');
        $p['petugas'] = htmlspecialchars($d['petugas'] ?? '');

        // Generate Barthel table rows
        $items = [
            1 => ['Makan', 'Feeding', ['Tidak mampu', 'Butuh bantuan memotong, mengoles mentega, dll', 'Mandiri']],
            2 => ['Mandi', 'Bathing', ['Tergantung orang lain', 'Mandiri']],
            3 => ['Perawatan Diri', 'Grooming', ['Membutuhkan bantuan orang lain', 'Mandiri dalam perawatan muka, rambut, gigi, dan bercukur']],
            4 => ['Berpakaian', 'Dressing', ['Tergantung orang lain', 'Sebagian dibantu (misalnya mengancing baju)', 'Mandiri']],
            5 => ['Buang Air Kecil', 'Blader', ['Inkontinensia atau pakai kateter dan tidak dapat mengontrol', 'Kadang Inkontinensia (maks, 1 kali dalam 24 jam)', 'Kontinensia (teratur untuk lebih dari 7 hari)']],
            6 => ['Buang Air Besar', 'Bowels', ['Inkontinensia (tidak teratur atau perlu enema)', 'Kadang Inkontensia (sekali seminggu)', 'Kontinensia (teratur)']],
            7 => ['Penggunaan Toilet', 'Toilet Use', ['Tergantung bantuan orang lain', 'Membutuhkan bantuan, tapi dapat melakukan beberapa hal sendiri', 'Mandiri']],
            8 => ['Transfer', '', ['Tidak mampu – tidak seimbang saat duduk', 'Butuh bantuan untuk bisa duduk (2 orang)', 'Bantuan kecil (1 orang)', 'Mandiri']],
            9 => ['Mobilitas', 'Mobility', ['Immobile (tidak mampu)', 'Menggunakan kursi roda', 'Berjalan dengan bantuan satu orang', 'Mandiri (meskipun menggunakan alat bantu seperti tongkat)']],
            10 => ['Naik Turun Tangga', 'Stairs', ['Tidak mampu', 'Membutuhkan bantuan (alat bantu)', 'Mandiri']]
        ];

        $rows = '';
        foreach ($items as $no => $it) {
            $n = count($it[2]);
            $nilai = ($d['s' . $no] !== null && $d['s' . $no] !== '') ? (int)$d['s' . $no] : null;
            foreach ($it[2] as $skor => $label) {
                $sel = ($nilai !== null && $nilai === $skor);
                $rows .= '<tr>';
                if ($skor === 0) {
                    $rows .= '<td rowspan="' . $n . '" class="c" style="vertical-align: middle; font-weight: bold;">' . $no . '</td>';
                    $rows .= '<td rowspan="' . $n . '" style="vertical-align: middle; font-weight: bold;">' . $it[0] . ($it[1] ? ' <i>(' . $it[1] . ')</i>' : '') . '</td>';
                }
                $rows .= '<td' . ($sel ? ' class="sel"' : '') . '>' . htmlspecialchars($label) . '</td>';
                $rows .= '<td class="c' . ($sel ? ' sel' : '') . '">' . $skor . '</td>';
                if ($skor === 0) {
                    $rows .= '<td rowspan="' . $n . '" class="nilai">' . ($nilai !== null ? $nilai : '') . '</td>';
                }
                $rows .= '</tr>';
            }
        }

        $katList = [
            'Mandiri' => '20',
            'Ketergantungan Ringan' => '12-19',
            'Ketergantungan Sedang' => '9-11',
            'Ketergantungan Berat' => '5-8',
            'Ketergantungan Total' => '0-4'
        ];
        $katRows = '';
        $first = true;
        foreach ($katList as $label => $range) {
            $on = ($d['kategori'] ?? '') === $label;
            $katRows .= '<tr>'
                     . '<td style="width: 35px;">' . ($first ? 'Skor' : '') . '</td>'
                     . '<td style="width: 45px;">' . $range . '</td>'
                     . '<td style="width: 15px; text-align: center;">:</td>'
                     . '<td>' . ($on ? '<span class="circle-badge">' . $label . '</span>' : $label) . '</td>'
                     . '</tr>';
            $first = false;
        }

        // Cek export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $p['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $kop_img = $p['kop_img'];
                $html = $this->draw('lampiran_asesmen_ulang_status_fungsional/cetak.html', ['p' => $p, 'kop_img' => $kop_img, 'rows' => $rows, 'katRows' => $katRows, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Lampiran_Asesmen_Ulang_Status_Fungsional_' . str_replace('/', '_', $p['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $p['kop_img'] = url('assets/img/KOP-SURAT.png');
        $kop_img = $p['kop_img'];
        echo $this->draw('lampiran_asesmen_ulang_status_fungsional/cetak.html', ['p' => $p, 'kop_img' => $kop_img, 'rows' => $rows, 'katRows' => $katRows, 'is_mpdf' => false]);
        exit();
    }

    // ==========================================
    // LAMPIRAN - FORM B CATATAN IMPLEMENTASI MANAJER PELAYANAN PASIEN (MPP) (RANAP)
    // ==========================================

    public function anyFormbmppmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_form_b_mpp')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir B Catatan Implementasi MPP berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'formbmppmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_form_b_mpp')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_form_b_mpp.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_form_b_mpp.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
            ->desc('lampiran_form_b_mpp.id')
            ->toArray();

        return $this->draw('lampiran_form_b_mpp/manage.html', ['data' => $rows]);
    }

    public function getFormbmppform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'tgl_pelaksanaan_1' => date('d-m-Y H:i'),
            'catatan_pelaksanaan' => '',
            'tgl_monitoring_2' => date('d-m-Y H:i'),
            'catatan_monitoring' => '',
            'tgl_fasilitasi_3' => date('d-m-Y H:i'),
            'catatan_fasilitasi' => '',
            'tgl_advokasi_4' => date('d-m-Y H:i'),
            'catatan_advokasi' => '',
            'tgl_hasil_5' => date('d-m-Y H:i'),
            'catatan_hasil' => '',
            'tgl_terminasi_6' => date('d-m-Y H:i'),
            'catatan_terminasi' => '',
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d'),
            'nama_mpp' => '',
            'nip_mpp' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_form_b_mpp')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_form_b_mpp/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postFormbmppsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'tgl_pelaksanaan_1' => trim($_POST['tgl_pelaksanaan_1'] ?? ''),
            'catatan_pelaksanaan' => trim($_POST['catatan_pelaksanaan'] ?? ''),
            'tgl_monitoring_2' => trim($_POST['tgl_monitoring_2'] ?? ''),
            'catatan_monitoring' => trim($_POST['catatan_monitoring'] ?? ''),
            'tgl_fasilitasi_3' => trim($_POST['tgl_fasilitasi_3'] ?? ''),
            'catatan_fasilitasi' => trim($_POST['catatan_fasilitasi'] ?? ''),
            'tgl_advokasi_4' => trim($_POST['tgl_advokasi_4'] ?? ''),
            'catatan_advokasi' => trim($_POST['catatan_advokasi'] ?? ''),
            'tgl_hasil_5' => trim($_POST['tgl_hasil_5'] ?? ''),
            'catatan_hasil' => trim($_POST['catatan_hasil'] ?? ''),
            'tgl_terminasi_6' => trim($_POST['tgl_terminasi_6'] ?? ''),
            'catatan_terminasi' => trim($_POST['catatan_terminasi'] ?? ''),
            'kota' => trim($_POST['kota'] ?? 'Banjarmasin'),
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d'),
            'nama_mpp' => trim($_POST['nama_mpp'] ?? ''),
            'nip_mpp' => trim($_POST['nip_mpp'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_form_b_mpp')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Formulir B Catatan Implementasi MPP berhasil diperbarui');
        } else {
            $this->db('lampiran_form_b_mpp')->save($saveData);
            $this->notify('success', 'Data Formulir B Catatan Implementasi MPP berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'formbmppmanage']));
    }

    public function getFormbmpphapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_form_b_mpp')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir B Catatan Implementasi MPP berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'formbmppmanage']));
    }

    public function getFormbmppcetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_form_b_mpp')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_form_b_mpp.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_form_b_mpp.id', $id)
            ->select('lampiran_form_b_mpp.*, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;
        $data['jk_label'] = ($data['jk'] ?? '') == 'L' ? 'L' : (($data['jk'] ?? '') == 'P' ? 'P' : '');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tanggal_lahir_indo'] = '-';
        }

        if (!empty($data['tanggal_ttd']) && $data['tanggal_ttd'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_form_b_mpp/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Formulir_B_Catatan_Implementasi_MPP_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_form_b_mpp/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN
    // ============================================================

    public function anyKebutuhanprivasimanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_kebutuhan_privasi_pasien')->where('id', $id)->delete();
            $this->notify('success', 'Data Identifikasi Kebutuhan Privasi Pasien berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'kebutuhanprivasimanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_kebutuhan_privasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_kebutuhan_privasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_kebutuhan_privasi_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->desc('lampiran_kebutuhan_privasi_pasien.id')
            ->toArray();

        return $this->draw('lampiran_kebutuhan_privasi_pasien/manage.html', ['data' => $rows]);
    }

    public function getKebutuhanprivasiform()
    {
        // AJAX info pasien & diagnosa
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('penanggung_jawab', 'penanggung_jawab.no_rawat = reg_periksa.no_rawat')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, reg_periksa.p_jawab, reg_periksa.hubunganpj, reg_periksa.almt_pj')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    // Cari diagnosa dari diagnosa_pasien dan penyakit
                    $diag = $this->db('diagnosa_pasien')
                        ->leftJoin('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
                        ->where('diagnosa_pasien.no_rawat', $reg['no_rawat'])
                        ->select('penyakit.nm_penyakit, diagnosa_pasien.kd_penyakit')
                        ->toArray();

                    $diagList = [];
                    foreach ($diag as $d) {
                        $diagList[] = $d['nm_penyakit'] . ' (' . $d['kd_penyakit'] . ')';
                    }
                    $reg['diagnosis_medis'] = implode(', ', $diagList);

                    // Penanggung jawab / keluarga yang bisa dihubungi
                    $pj = '';
                    if (!empty($reg['p_jawab'])) {
                        $pj = $reg['p_jawab'] . (!empty($reg['hubunganpj']) ? ' (' . $reg['hubunganpj'] . ')' : '');
                    }
                    $reg['keluarga_dihubungi'] = $pj;

                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'keluarga_dihubungi' => '',
            'diagnosis_medis' => '',
            'privasi_lawan_jenis' => 'Tidak',
            'keterangan_lawan_jenis' => '',
            'privasi_orang_lain' => 'Tidak',
            'keterangan_orang_lain' => '',
            'privasi_bagian_tubuh' => 'Tidak',
            'keterangan_bagian_tubuh' => '',
            'privasi_informasi_penyakit' => 'Tidak',
            'keterangan_informasi_penyakit' => '',
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d'),
            'nama_petugas' => '',
            'nip_petugas' => '',
            'nama_pasien_keluarga' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_kebutuhan_privasi_pasien')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_kebutuhan_privasi_pasien/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postKebutuhanprivasisave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'keluarga_dihubungi' => trim($_POST['keluarga_dihubungi'] ?? ''),
            'diagnosis_medis' => trim($_POST['diagnosis_medis'] ?? ''),
            'privasi_lawan_jenis' => trim($_POST['privasi_lawan_jenis'] ?? 'Tidak'),
            'keterangan_lawan_jenis' => trim($_POST['keterangan_lawan_jenis'] ?? ''),
            'privasi_orang_lain' => trim($_POST['privasi_orang_lain'] ?? 'Tidak'),
            'keterangan_orang_lain' => trim($_POST['keterangan_orang_lain'] ?? ''),
            'privasi_bagian_tubuh' => trim($_POST['privasi_bagian_tubuh'] ?? 'Tidak'),
            'keterangan_bagian_tubuh' => trim($_POST['keterangan_bagian_tubuh'] ?? ''),
            'privasi_informasi_penyakit' => trim($_POST['privasi_informasi_penyakit'] ?? 'Tidak'),
            'keterangan_informasi_penyakit' => trim($_POST['keterangan_informasi_penyakit'] ?? ''),
            'kota' => trim($_POST['kota'] ?? 'Banjarmasin'),
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d'),
            'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
            'nip_petugas' => trim($_POST['nip_petugas'] ?? ''),
            'nama_pasien_keluarga' => trim($_POST['nama_pasien_keluarga'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_kebutuhan_privasi_pasien')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Identifikasi Kebutuhan Privasi Pasien berhasil diperbarui');
        } else {
            $this->db('lampiran_kebutuhan_privasi_pasien')->save($saveData);
            $this->notify('success', 'Data Identifikasi Kebutuhan Privasi Pasien berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'kebutuhanprivasimanage']));
    }

    public function getKebutuhanprivasihapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_kebutuhan_privasi_pasien')->where('id', $id)->delete();
            $this->notify('success', 'Data Identifikasi Kebutuhan Privasi Pasien berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'kebutuhanprivasimanage']));
    }

    public function getKebutuhanprivasicetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_kebutuhan_privasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_kebutuhan_privasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_kebutuhan_privasi_pasien.id', $id)
            ->select('lampiran_kebutuhan_privasi_pasien.*, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;
        $data['jk_label'] = ($data['jk'] ?? '') == 'L' ? 'Laki-Laki' : (($data['jk'] ?? '') == 'P' ? 'Perempuan' : '-');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tanggal_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tanggal_lahir_indo'] = '-';
        }

        if (!empty($data['tanggal_ttd']) && $data['tanggal_ttd'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_kebutuhan_privasi_pasien/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Formulir_Identifikasi_Kebutuhan_Privasi_Pasien_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_kebutuhan_privasi_pasien/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR MENINGGALKAN RUMAH SAKIT SELAMA PERIODE WAKTU TERTENTU
    // ============================================================

    public function anyMeninggalkanrsmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_meninggalkan_rs_tertentu')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir Meninggalkan RS berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'meninggalkanrsmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_meninggalkan_rs_tertentu')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_meninggalkan_rs_tertentu.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_meninggalkan_rs_tertentu.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->desc('lampiran_meninggalkan_rs_tertentu.id')
            ->toArray();

        return $this->draw('lampiran_meninggalkan_rs_tertentu/manage.html', ['data' => $rows]);
    }

    public function getMeninggalkanrsform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, reg_periksa.p_jawab, reg_periksa.hubunganpj, reg_periksa.almt_pj')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    // Cari ruang perawatan dari kamar_inap jika ada
                    $km = $this->db('kamar_inap')
                        ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                        ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                        ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                        ->desc('kamar_inap.tgl_masuk')
                        ->desc('kamar_inap.jam_masuk')
                        ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                        ->oneArray();

                    if ($km && !empty($km['nm_bangsal'])) {
                        $reg['ruang_perawatan'] = $km['nm_bangsal'] . ' (' . $km['kd_kamar'] . ')';
                    } else {
                        $reg['ruang_perawatan'] = 'Rawat Inap';
                    }

                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tmp_lahir' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => '',
            'nama_pembuat' => '',
            'jk_pembuat' => 'Laki-laki',
            'no_identitas_pembuat' => '',
            'alamat_pembuat' => '',
            'hubungan' => 'Diri Sendiri',
            'hubungan_detail' => '',
            'ruang_perawatan' => '',
            'keperluan' => '',
            'tgl_mulai' => date('Y-m-d'),
            'tgl_selesai' => date('Y-m-d'),
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d'),
            'saksi_rs' => '',
            'nip_saksi_rs' => '',
            'saksi_pasien' => '',
            'pembuat_pernyataan_ttd' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_meninggalkan_rs_tertentu')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_meninggalkan_rs_tertentu/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postMeninggalkanrssave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'nama_pembuat' => trim($_POST['nama_pembuat'] ?? ''),
            'jk_pembuat' => trim($_POST['jk_pembuat'] ?? 'Laki-laki'),
            'no_identitas_pembuat' => trim($_POST['no_identitas_pembuat'] ?? ''),
            'alamat_pembuat' => trim($_POST['alamat_pembuat'] ?? ''),
            'hubungan' => trim($_POST['hubungan'] ?? 'Diri Sendiri'),
            'hubungan_detail' => trim($_POST['hubungan_detail'] ?? ''),
            'ruang_perawatan' => trim($_POST['ruang_perawatan'] ?? ''),
            'keperluan' => trim($_POST['keperluan'] ?? ''),
            'tgl_mulai' => !empty($_POST['tgl_mulai']) ? $_POST['tgl_mulai'] : date('Y-m-d'),
            'tgl_selesai' => !empty($_POST['tgl_selesai']) ? $_POST['tgl_selesai'] : date('Y-m-d'),
            'kota' => trim($_POST['kota'] ?? 'Banjarmasin'),
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d'),
            'saksi_rs' => trim($_POST['saksi_rs'] ?? ''),
            'nip_saksi_rs' => trim($_POST['nip_saksi_rs'] ?? ''),
            'saksi_pasien' => trim($_POST['saksi_pasien'] ?? ''),
            'pembuat_pernyataan_ttd' => trim($_POST['pembuat_pernyataan_ttd'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_meninggalkan_rs_tertentu')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Formulir Meninggalkan RS berhasil diperbarui');
        } else {
            $this->db('lampiran_meninggalkan_rs_tertentu')->save($saveData);
            $this->notify('success', 'Data Formulir Meninggalkan RS berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'meninggalkanrsmanage']));
    }

    public function getMeninggalkanrshapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_meninggalkan_rs_tertentu')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir Meninggalkan RS berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'meninggalkanrsmanage']));
    }

    public function getMeninggalkanrscetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_meninggalkan_rs_tertentu')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_meninggalkan_rs_tertentu.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_meninggalkan_rs_tertentu.id', $id)
            ->select('lampiran_meninggalkan_rs_tertentu.*, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rm;

        $data['jk_pasien_label'] = ($data['jk'] ?? '') == 'L' ? 'laki-laki' : (($data['jk'] ?? '') == 'P' ? 'perempuan' : '-');
        $data['jk_pembuat_label'] = strtolower($data['jk_pembuat'] ?? 'laki-laki');

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $tglIndo = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
                $data['ttl_pasien'] = (!empty($data['tmp_lahir']) ? $data['tmp_lahir'] . ', ' : '') . $tglIndo;
            } else {
                $data['ttl_pasien'] = $data['tgl_lahir'];
            }
        } else {
            $data['ttl_pasien'] = '-';
        }

        // Format tanggal mulai dan selesai
        if (!empty($data['tgl_mulai']) && $data['tgl_mulai'] != '0000-00-00') {
            $tm = explode('-', $data['tgl_mulai']);
            $data['tgl_mulai_indo'] = (count($tm) == 3) ? (int)$tm[2] . ' ' . $bulan[(int)$tm[1]] . ' ' . $tm[0] : $data['tgl_mulai'];
        } else {
            $data['tgl_mulai_indo'] = '.....................';
        }

        if (!empty($data['tgl_selesai']) && $data['tgl_selesai'] != '0000-00-00') {
            $ts = explode('-', $data['tgl_selesai']);
            $data['tgl_selesai_indo'] = (count($ts) == 3) ? (int)$ts[2] . ' ' . $bulan[(int)$ts[1]] . ' ' . $ts[0] : $data['tgl_selesai'];
        } else {
            $data['tgl_selesai_indo'] = '.....................';
        }

        if (!empty($data['tanggal_ttd']) && $data['tanggal_ttd'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_meninggalkan_rs_tertentu/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Formulir_Meninggalkan_RS_Tertentu_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_meninggalkan_rs_tertentu/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR PENGHENTIAN PENGOBATAN
    // ============================================================

    public function anyPenghentianpengobatanmanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_penghentian_pengobatan')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir Penghentian Pengobatan berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'penghentianpengobatanmanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_penghentian_pengobatan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penghentian_pengobatan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('lampiran_penghentian_pengobatan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
            ->desc('lampiran_penghentian_pengobatan.id')
            ->toArray();

        return $this->draw('lampiran_penghentian_pengobatan/manage.html', ['data' => $rows]);
    }

    public function getPenghentianpengobatanform()
    {
        // AJAX info pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, pasien.no_ktp, pasien.no_tlp, reg_periksa.p_jawab, reg_periksa.hubunganpj, reg_periksa.almt_pj')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    // Cari ruang perawatan dari kamar_inap jika ada
                    $km = $this->db('kamar_inap')
                        ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                        ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                        ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                        ->desc('kamar_inap.tgl_masuk')
                        ->desc('kamar_inap.jam_masuk')
                        ->select('bangsal.nm_bangsal, kamar.kelas, kamar.kd_kamar')
                        ->oneArray();

                    if ($km && !empty($km['nm_bangsal'])) {
                        $reg['perawatan_kelas_kamar'] = $km['nm_bangsal'] . ' / Kelas ' . $km['kelas'] . ' / Kamar ' . $km['kd_kamar'];
                    } else {
                        $reg['perawatan_kelas_kamar'] = 'Rawat Inap';
                    }

                    // Ambil angka umur
                    preg_match('/\d+/', $reg['umur'] ?? '', $matches);
                    $reg['umur_angka'] = $matches[0] ?? '';

                    $result = array_merge(['success' => true], $reg);
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
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'umur_pasien' => '',
            'alamat_pasien' => '',
            'nama_pembuat' => '',
            'umur_pembuat' => '',
            'alamat_pembuat' => '',
            'no_telp_pembuat' => '',
            'bukti_diri_pembuat' => '',
            'hubungan' => 'Diri Sendiri',
            'hubungan_detail' => '',
            'bukti_diri_pasien' => '',
            'perawatan_kelas_kamar' => '',
            'alasan_penghentian' => '',
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d'),
            'jam_ttd' => date('H:i'),
            'nama_petugas' => '',
            'nip_petugas' => '',
            'pembuat_pernyataan_ttd' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_penghentian_pengobatan')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur, pasien.alamat, pasien.no_ktp')
                    ->oneArray();
                if ($reg) {
                    $data = array_merge($data, $reg);
                    preg_match('/\d+/', $reg['umur'] ?? '', $matches);
                    $data['umur_pasien'] = $matches[0] ?? '';
                    $data['alamat_pasien'] = $reg['alamat'] ?? '';
                }
            }
        }

        $petugas_list = $this->db('petugas')->limit(100)->select('nip, nama')->toArray();

        return $this->draw('lampiran_penghentian_pengobatan/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list
        ]);
    }

    public function postPenghentianpengobatansave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'nama_pembuat' => trim($_POST['nama_pembuat'] ?? ''),
            'umur_pembuat' => trim($_POST['umur_pembuat'] ?? ''),
            'alamat_pembuat' => trim($_POST['alamat_pembuat'] ?? ''),
            'no_telp_pembuat' => trim($_POST['no_telp_pembuat'] ?? ''),
            'bukti_diri_pembuat' => trim($_POST['bukti_diri_pembuat'] ?? ''),
            'hubungan' => trim($_POST['hubungan'] ?? 'Diri Sendiri'),
            'hubungan_detail' => trim($_POST['hubungan_detail'] ?? ''),
            'bukti_diri_pasien' => trim($_POST['bukti_diri_pasien'] ?? ''),
            'perawatan_kelas_kamar' => trim($_POST['perawatan_kelas_kamar'] ?? ''),
            'alasan_penghentian' => trim($_POST['alasan_penghentian'] ?? ''),
            'kota' => trim($_POST['kota'] ?? 'Banjarmasin'),
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d'),
            'jam_ttd' => trim($_POST['jam_ttd'] ?? date('H:i')),
            'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
            'nip_petugas' => trim($_POST['nip_petugas'] ?? ''),
            'pembuat_pernyataan_ttd' => trim($_POST['pembuat_pernyataan_ttd'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_penghentian_pengobatan')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Formulir Penghentian Pengobatan berhasil diperbarui');
        } else {
            $this->db('lampiran_penghentian_pengobatan')->save($saveData);
            $this->notify('success', 'Data Formulir Penghentian Pengobatan berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'penghentianpengobatanmanage']));
    }

    public function getPenghentianpengobatanhapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_penghentian_pengobatan')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir Penghentian Pengobatan berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'penghentianpengobatanmanage']));
    }

    public function getPenghentianpengobatancetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_penghentian_pengobatan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penghentian_pengobatan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->where('lampiran_penghentian_pengobatan.id', $id)
            ->select('lampiran_penghentian_pengobatan.*, reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat as alamat_pasien, pasien.no_ktp as no_ktp_pasien')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rm;

        // Umur pasien hanya angka
        preg_match('/\d+/', $data['umur'] ?? '', $matches);
        $data['umur_pasien_angka'] = $matches[0] ?? '-';

        // Bukti diri pasien fallback
        if (empty($data['bukti_diri_pasien'])) {
            $data['bukti_diri_pasien'] = !empty($data['no_ktp_pasien']) ? $data['no_ktp_pasien'] : 'RM: ' . $data['no_rkm_medis'];
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tanggal_ttd']) && $data['tanggal_ttd'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_penghentian_pengobatan/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Formulir_Penghentian_Pengobatan_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penghentian_pengobatan/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ)
    // ============================================================

    public function anyPrmrjmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_profil_ringkas_medis_rajal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_profil_ringkas_medis_rajal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_profil_ringkas_medis_rajal.*, COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk')
            ->desc('lampiran_profil_ringkas_medis_rajal.id')
            ->toArray();

        foreach ($rows as &$r) {
            $diag = !empty($r['diagnosa_dpjp']) ? json_decode($r['diagnosa_dpjp'], true) : [];
            $r['total_diagnosa'] = is_array($diag) ? count($diag) : 0;
            $alergi = !empty($r['riwayat_alergi']) ? json_decode($r['riwayat_alergi'], true) : [];
            $r['total_alergi'] = is_array($alergi) ? count($alergi) : 0;
            $obat = !empty($r['obat_obatan']) ? json_decode($r['obat_obatan'], true) : [];
            $r['total_obat'] = is_array($obat) ? count($obat) : 0;
        }

        return $this->draw('lampiran_prmrj/manage.html', ['data' => $rows]);
    }

    public function getPrmrjform()
    {
        // AJAX pencarian pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, dokter.nm_dokter, dokter.kd_dokter')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                    $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                    $result = array_merge(['success' => true], $reg);
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];
        if (!empty($id)) {
            $data = $this->db('lampiran_profil_ringkas_medis_rajal')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_profil_ringkas_medis_rajal.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_profil_ringkas_medis_rajal.id', $id)
                ->select('lampiran_profil_ringkas_medis_rajal.*, COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'tanggal_formulir' => date('Y-m-d'),
                'nm_pasien' => '',
                'jk' => '',
                'tmp_lahir' => '',
                'tgl_lahir' => '',
                'umur' => '',
                'alamat' => '',
                'diagnosa_dpjp' => '[]',
                'riwayat_alergi' => '[]',
                'diagnosa_ranap' => '[]',
                'operasi_tindakan' => '[]',
                'obat_obatan' => '[]',
                'keterangan_tambahan' => '',
                'nama_dokter' => '',
                'nip_dokter' => ''
            ];
        }

        $data['diagnosa_dpjp_list'] = !empty($data['diagnosa_dpjp']) ? json_decode($data['diagnosa_dpjp'], true) : [];
        if (!is_array($data['diagnosa_dpjp_list'])) { $data['diagnosa_dpjp_list'] = []; }

        $data['riwayat_alergi_list'] = !empty($data['riwayat_alergi']) ? json_decode($data['riwayat_alergi'], true) : [];
        if (!is_array($data['riwayat_alergi_list'])) { $data['riwayat_alergi_list'] = []; }

        $data['diagnosa_ranap_list'] = !empty($data['diagnosa_ranap']) ? json_decode($data['diagnosa_ranap'], true) : [];
        if (!is_array($data['diagnosa_ranap_list'])) { $data['diagnosa_ranap_list'] = []; }

        $data['operasi_tindakan_list'] = !empty($data['operasi_tindakan']) ? json_decode($data['operasi_tindakan'], true) : [];
        if (!is_array($data['operasi_tindakan_list'])) { $data['operasi_tindakan_list'] = []; }

        $data['obat_obatan_list'] = !empty($data['obat_obatan']) ? json_decode($data['obat_obatan'], true) : [];
        if (!is_array($data['obat_obatan_list'])) { $data['obat_obatan_list'] = []; }

        $dokter_list = $this->db('dokter')->select('kd_dokter, nm_dokter')->toArray();

        return $this->draw('lampiran_prmrj/form.html', [
            'data' => $data,
            'dokter_list' => $dokter_list
        ]);
    }

    public function postPrmrjsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rawat)) {
            $this->notify('failure', 'No. Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'prmrjform']));
        }

        // Process Diagnosa & DPJP
        $diagnosa_arr = [];
        if (isset($_POST['diag_diagnosa']) && is_array($_POST['diag_diagnosa'])) {
            foreach ($_POST['diag_diagnosa'] as $k => $diag) {
                if (!empty(trim($diag)) || !empty($_POST['diag_dpjp_nama'][$k] ?? '') || !empty($_POST['diag_dpjp_utama_nama'][$k] ?? '')) {
                    $diagnosa_arr[] = [
                        'diagnosa' => trim($diag),
                        'dpjp_nama' => trim($_POST['diag_dpjp_nama'][$k] ?? ''),
                        'dpjp_tgl_mulai' => trim($_POST['diag_dpjp_tgl_mulai'][$k] ?? ''),
                        'dpjp_tgl_akhir' => trim($_POST['diag_dpjp_tgl_akhir'][$k] ?? ''),
                        'dpjp_ttd' => trim($_POST['diag_dpjp_ttd'][$k] ?? ''),
                        'dpjp_utama_nama' => trim($_POST['diag_dpjp_utama_nama'][$k] ?? ''),
                        'dpjp_utama_tgl_mulai' => trim($_POST['diag_dpjp_utama_tgl_mulai'][$k] ?? ''),
                        'dpjp_utama_tgl_akhir' => trim($_POST['diag_dpjp_utama_tgl_akhir'][$k] ?? ''),
                        'dpjp_utama_ttd' => trim($_POST['diag_dpjp_utama_ttd'][$k] ?? ''),
                        'keterangan' => trim($_POST['diag_keterangan'][$k] ?? '')
                    ];
                }
            }
        }

        // Process Alergi
        $alergi_arr = [];
        if (isset($_POST['alergi_tanggal']) && is_array($_POST['alergi_tanggal'])) {
            foreach ($_POST['alergi_tanggal'] as $k => $tgl) {
                $al = trim($_POST['alergi_nama'][$k] ?? '');
                if (!empty($al) || !empty($tgl)) {
                    $alergi_arr[] = [
                        'tanggal' => trim($tgl),
                        'alergi' => $al
                    ];
                }
            }
        }

        // Process Diagnosa Ranap
        $ranap_arr = [];
        if (isset($_POST['ranap_tanggal']) && is_array($_POST['ranap_tanggal'])) {
            foreach ($_POST['ranap_tanggal'] as $k => $tgl) {
                $dg = trim($_POST['ranap_diagnosa'][$k] ?? '');
                if (!empty($dg) || !empty($tgl)) {
                    $ranap_arr[] = [
                        'tanggal' => trim($tgl),
                        'diagnosa' => $dg
                    ];
                }
            }
        }

        // Process Operasi / Tindakan
        $operasi_arr = [];
        if (isset($_POST['operasi_tanggal']) && is_array($_POST['operasi_tanggal'])) {
            foreach ($_POST['operasi_tanggal'] as $k => $tgl) {
                $op = trim($_POST['operasi_tindakan'][$k] ?? '');
                if (!empty($op) || !empty($tgl)) {
                    $operasi_arr[] = [
                        'tanggal' => trim($tgl),
                        'operasi_tindakan' => $op
                    ];
                }
            }
        }

        // Process Obat-obatan
        $obat_arr = [];
        if (isset($_POST['obat_nama']) && is_array($_POST['obat_nama'])) {
            foreach ($_POST['obat_nama'] as $k => $ob) {
                if (!empty(trim($ob))) {
                    $obat_arr[] = [
                        'tanggal_mulai' => trim($_POST['obat_tgl_mulai'][$k] ?? ''),
                        'nama_obat' => trim($ob),
                        'dosis' => trim($_POST['obat_dosis'][$k] ?? ''),
                        'frekuensi' => trim($_POST['obat_frekuensi'][$k] ?? ''),
                        'tanggal_selesai' => trim($_POST['obat_tgl_selesai'][$k] ?? '')
                    ];
                }
            }
        }

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal_formulir' => !empty($_POST['tanggal_formulir']) ? $_POST['tanggal_formulir'] : date('Y-m-d'),
            'diagnosa_dpjp' => json_encode($diagnosa_arr, JSON_UNESCAPED_UNICODE),
            'riwayat_alergi' => json_encode($alergi_arr, JSON_UNESCAPED_UNICODE),
            'diagnosa_ranap' => json_encode($ranap_arr, JSON_UNESCAPED_UNICODE),
            'operasi_tindakan' => json_encode($operasi_arr, JSON_UNESCAPED_UNICODE),
            'obat_obatan' => json_encode($obat_arr, JSON_UNESCAPED_UNICODE),
            'keterangan_tambahan' => trim($_POST['keterangan_tambahan'] ?? ''),
            'nama_dokter' => trim($_POST['nama_dokter'] ?? ''),
            'nip_dokter' => trim($_POST['nip_dokter'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_profil_ringkas_medis_rajal')->where('id', $id)->update($payload);
            $this->notify('success', 'Formulir PRMRJ berhasil diperbarui.');
        } else {
            $this->db('lampiran_profil_ringkas_medis_rajal')->save($payload);
            $this->notify('success', 'Formulir PRMRJ baru berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'prmrjmanage']));
    }

    public function getPrmrjhapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_profil_ringkas_medis_rajal')->where('id', $id)->delete();
            $this->notify('success', 'Data Formulir PRMRJ berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'prmrjmanage']));
    }

    public function getPrmrjcetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_profil_ringkas_medis_rajal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_profil_ringkas_medis_rajal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_profil_ringkas_medis_rajal.id', $id)
            ->select('lampiran_profil_ringkas_medis_rajal.*, COALESCE(lampiran_profil_ringkas_medis_rajal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat as alamat_pasien, pasien.no_ktp as no_ktp_pasien')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rm;
        $data['rm_digits'] = str_split($rm);

        // Umur pasien hanya angka
        preg_match('/\d+/', $data['umur'] ?? '', $matches);
        $data['umur_pasien_angka'] = $matches[0] ?? '-';

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Format tanggal lahir
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tglLahir = explode('-', $data['tgl_lahir']);
            if (count($tglLahir) == 3) {
                $data['tgl_lahir_indo'] = (int)$tglLahir[2] . ' ' . $bulan[(int)$tglLahir[1]] . ' ' . $tglLahir[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Parse JSON lists
        $data['diagnosa_dpjp_list'] = !empty($data['diagnosa_dpjp']) ? json_decode($data['diagnosa_dpjp'], true) : [];
        if (!is_array($data['diagnosa_dpjp_list'])) { $data['diagnosa_dpjp_list'] = []; }

        $data['riwayat_alergi_list'] = !empty($data['riwayat_alergi']) ? json_decode($data['riwayat_alergi'], true) : [];
        if (!is_array($data['riwayat_alergi_list'])) { $data['riwayat_alergi_list'] = []; }

        $data['diagnosa_ranap_list'] = !empty($data['diagnosa_ranap']) ? json_decode($data['diagnosa_ranap'], true) : [];
        if (!is_array($data['diagnosa_ranap_list'])) { $data['diagnosa_ranap_list'] = []; }

        $data['operasi_tindakan_list'] = !empty($data['operasi_tindakan']) ? json_decode($data['operasi_tindakan'], true) : [];
        if (!is_array($data['operasi_tindakan_list'])) { $data['operasi_tindakan_list'] = []; }

        $data['obat_obatan_list'] = !empty($data['obat_obatan']) ? json_decode($data['obat_obatan'], true) : [];
        if (!is_array($data['obat_obatan_list'])) { $data['obat_obatan_list'] = []; }

        // Pad with blank rows for exact physical form grid matching
        // Page 1 Diagnosa grid: at least 16 rows
        $targetDiagRows = max(16, count($data['diagnosa_dpjp_list']));
        $data['padded_diagnosa'] = $data['diagnosa_dpjp_list'];
        while (count($data['padded_diagnosa']) < $targetDiagRows) {
            $data['padded_diagnosa'][] = [
                'diagnosa' => '',
                'dpjp_nama' => '',
                'dpjp_tgl_mulai' => '',
                'dpjp_tgl_akhir' => '',
                'dpjp_ttd' => '',
                'dpjp_utama_nama' => '',
                'dpjp_utama_tgl_mulai' => '',
                'dpjp_utama_tgl_akhir' => '',
                'dpjp_utama_ttd' => '',
                'keterangan' => ''
            ];
        }

        // Page 1 Alergi grid: at least 4 rows
        $targetAlergiRows = max(4, count($data['riwayat_alergi_list']));
        $data['padded_alergi'] = $data['riwayat_alergi_list'];
        while (count($data['padded_alergi']) < $targetAlergiRows) {
            $data['padded_alergi'][] = ['tanggal' => '', 'alergi' => ''];
        }

        // Page 2 Diagnosa Ranap: at least 6 rows
        $targetRanapRows = max(6, count($data['diagnosa_ranap_list']));
        $data['padded_ranap'] = $data['diagnosa_ranap_list'];
        while (count($data['padded_ranap']) < $targetRanapRows) {
            $data['padded_ranap'][] = ['tanggal' => '', 'diagnosa' => ''];
        }

        // Page 2 Operasi: at least 6 rows
        $targetOpRows = max(6, count($data['operasi_tindakan_list']));
        $data['padded_operasi'] = $data['operasi_tindakan_list'];
        while (count($data['padded_operasi']) < $targetOpRows) {
            $data['padded_operasi'][] = ['tanggal' => '', 'operasi_tindakan' => ''];
        }

        // Page 2 Obat: at least 12 rows
        $targetObatRows = max(12, count($data['obat_obatan_list']));
        $data['padded_obat'] = $data['obat_obatan_list'];
        while (count($data['padded_obat']) < $targetObatRows) {
            $data['padded_obat'][] = [
                'tanggal_mulai' => '',
                'nama_obat' => '',
                'dosis' => '',
                'frekuensi' => '',
                'tanggal_selesai' => ''
            ];
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_prmrj/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'PRMRJ_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_prmrj/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR SKRINING DARI LUAR RUMAH SAKIT
    // ============================================================

    public function anySkriningluarrsmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_skrining_luar_rs')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_skrining_luar_rs.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_skrining_luar_rs.*, COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk')
            ->desc('lampiran_skrining_luar_rs.id')
            ->toArray();

        return $this->draw('lampiran_skrining_luar_rs/manage.html', ['data' => $rows]);
    }

    public function getSkriningluarrsform()
    {
        // AJAX pencarian pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
                    ->leftJoin('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, penjab.png_jawab, dokter.nm_dokter')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                    $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                    $result = array_merge(['success' => true], $reg);
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];
        if (!empty($id)) {
            $data = $this->db('lampiran_skrining_luar_rs')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_skrining_luar_rs.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_skrining_luar_rs.id', $id)
                ->select('lampiran_skrining_luar_rs.*, COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'tanggal_skrining' => date('Y-m-d'),
                'jam_skrining' => date('H:i:s'),
                'nm_pasien' => '',
                'jk' => '',
                'tmp_lahir' => '',
                'tgl_lahir' => '',
                'umur' => '',
                'status_pembayaran' => 'BPJS',
                'asal_rujukan' => '',
                'alasan_dirujuk' => '',
                'diagnosa' => '',
                'keadaan_umum' => 'Baik',
                'kesadaran' => 'Compos Mentis',
                'tensi' => '120/80',
                'nadi' => '80',
                'suhu' => '36.5',
                'respirasi' => '20',
                'terapi' => '',
                'tindakan' => '',
                'tujuan' => '["UGD"]',
                'tujuan_lainnya' => '',
                'keterangan_status' => 'TERIMA',
                'alasan_keterangan' => '',
                'nama_petugas' => '',
                'nip_petugas' => ''
            ];
        }

        // Tujuan checklist
        $data['tujuan_list'] = [];
        if (!empty($data['tujuan'])) {
            $decoded = json_decode($data['tujuan'], true);
            if (is_array($decoded)) {
                $data['tujuan_list'] = $decoded;
            } else {
                $data['tujuan_list'] = array_map('trim', explode(',', $data['tujuan']));
            }
        }

        $petugas_list = $this->db('petugas')->select('nip, nama')->toArray();
        $dokter_list = $this->db('dokter')->select('kd_dokter, nm_dokter')->toArray();

        return $this->draw('lampiran_skrining_luar_rs/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list,
            'dokter_list' => $dokter_list
        ]);
    }

    public function postSkriningluarrssave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rawat)) {
            $this->notify('failure', 'No. Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'skriningluarrsform']));
        }

        // Tujuan checkboxes
        $tujuan_arr = isset($_POST['tujuan']) && is_array($_POST['tujuan']) ? $_POST['tujuan'] : [];
        $tujuan_json = json_encode($tujuan_arr, JSON_UNESCAPED_UNICODE);

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal_skrining' => !empty($_POST['tanggal_skrining']) ? $_POST['tanggal_skrining'] : date('Y-m-d'),
            'jam_skrining' => !empty($_POST['jam_skrining']) ? $_POST['jam_skrining'] : date('H:i:s'),
            'status_pembayaran' => trim($_POST['status_pembayaran'] ?? ''),
            'asal_rujukan' => trim($_POST['asal_rujukan'] ?? ''),
            'alasan_dirujuk' => trim($_POST['alasan_dirujuk'] ?? ''),
            'diagnosa' => trim($_POST['diagnosa'] ?? ''),
            'keadaan_umum' => trim($_POST['keadaan_umum'] ?? ''),
            'kesadaran' => trim($_POST['kesadaran'] ?? ''),
            'tensi' => trim($_POST['tensi'] ?? ''),
            'nadi' => trim($_POST['nadi'] ?? ''),
            'suhu' => trim($_POST['suhu'] ?? ''),
            'respirasi' => trim($_POST['respirasi'] ?? ''),
            'terapi' => trim($_POST['terapi'] ?? ''),
            'tindakan' => trim($_POST['tindakan'] ?? ''),
            'tujuan' => $tujuan_json,
            'tujuan_lainnya' => trim($_POST['tujuan_lainnya'] ?? ''),
            'keterangan_status' => in_array($_POST['keterangan_status'] ?? '', ['TERIMA', 'TOLAK']) ? $_POST['keterangan_status'] : 'TERIMA',
            'alasan_keterangan' => trim($_POST['alasan_keterangan'] ?? ''),
            'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
            'nip_petugas' => trim($_POST['nip_petugas'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_skrining_luar_rs')->where('id', $id)->update($payload);
            $this->notify('success', 'Formulir Skrining Luar RS berhasil diperbarui.');
        } else {
            $this->db('lampiran_skrining_luar_rs')->save($payload);
            $this->notify('success', 'Formulir Skrining Luar RS baru berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'skriningluarrsmanage']));
    }

    public function getSkriningluarrshapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_skrining_luar_rs')->where('id', $id)->delete();
            $this->notify('success', 'Formulir Skrining Luar RS berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'skriningluarrsmanage']));
    }

    public function getSkriningluarrscetak()
    {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_skrining_luar_rs')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_skrining_luar_rs.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_skrining_luar_rs.id', $id)
            ->select('lampiran_skrining_luar_rs.*, COALESCE(lampiran_skrining_luar_rs.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rm;
        $data['rm_digits'] = str_split($rm);

        // Umur pasien hanya angka
        preg_match('/\d+/', $data['umur'] ?? '', $matches);
        $data['umur_angka'] = $matches[0] ?? '-';

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Format tanggal lahir
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tglLahir = explode('-', $data['tgl_lahir']);
            if (count($tglLahir) == 3) {
                $data['tgl_lahir_indo'] = (int)$tglLahir[2] . ' ' . $bulan[(int)$tglLahir[1]] . ' ' . $tglLahir[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Format tanggal skrining
        if (!empty($data['tanggal_skrining']) && $data['tanggal_skrining'] != '0000-00-00') {
            $tglSkrining = explode('-', $data['tanggal_skrining']);
            if (count($tglSkrining) == 3) {
                $data['tanggal_skrining_indo'] = (int)$tglSkrining[2] . ' ' . $bulan[(int)$tglSkrining[1]] . ' ' . $tglSkrining[0];
            } else {
                $data['tanggal_skrining_indo'] = $data['tanggal_skrining'];
            }
        } else {
            $data['tanggal_skrining_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Tujuan checklist decoding
        $tujuanArr = [];
        if (!empty($data['tujuan'])) {
            $decoded = json_decode($data['tujuan'], true);
            if (is_array($decoded)) {
                $tujuanArr = $decoded;
            } else {
                $tujuanArr = array_map('trim', explode(',', $data['tujuan']));
            }
        }
        $data['tujuan_list'] = $tujuanArr;
        $data['has_ugd'] = in_array('UGD', $tujuanArr);
        $data['has_ortho'] = in_array('Orthodonsia', $tujuanArr);
        $data['has_pedo'] = in_array('Pedodonsia', $tujuanArr);
        $data['has_bedah_mulut'] = in_array('Bedah Mulut', $tujuanArr);
        $data['has_oral_med'] = in_array('Oral Medicine', $tujuanArr);
        $data['has_perio'] = in_array('Periodonsia', $tujuanArr);
        $data['has_prostho'] = in_array('Prosthodonsia', $tujuanArr);
        $data['has_konservasi'] = in_array('Konservasi Gigi', $tujuanArr);
        $data['has_lainnya'] = in_array('Lainnya', $tujuanArr);

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_skrining_luar_rs/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Skrining_Luar_RS_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_skrining_luar_rs/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - FORMULIR STABILISASI PASIEN SEBELUM TRANSFER
    // ============================================================

    public function anyStabilisasipasienmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_stabilisasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_stabilisasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_stabilisasi_pasien.*, COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk')
            ->desc('lampiran_stabilisasi_pasien.id')
            ->toArray();

        foreach ($rows as &$r) {
            $vital = !empty($r['monitoring_tanda_vital']) ? json_decode($r['monitoring_tanda_vital'], true) : [];
            $r['total_monitoring'] = is_array($vital) ? count($vital) : 0;
        }

        return $this->draw('lampiran_stabilisasi_pasien/manage.html', ['data' => $rows]);
    }

    public function getStabilisasipasienform()
    {
        // AJAX pencarian pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
                    ->leftJoin('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, penjab.png_jawab, dokter.nm_dokter, dokter.kd_dokter')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                    $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                    $result = array_merge(['success' => true], $reg);
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];
        if (!empty($id)) {
            $data = $this->db('lampiran_stabilisasi_pasien')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_stabilisasi_pasien.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_stabilisasi_pasien.id', $id)
                ->select('lampiran_stabilisasi_pasien.*, COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'tanggal_stabilisasi' => date('Y-m-d'),
                'jam_stabilisasi' => date('H:i:s'),
                'nm_pasien' => '',
                'jk' => '',
                'tmp_lahir' => '',
                'tgl_lahir' => '',
                'umur' => '',
                'nama_perawat' => '',
                'nip_perawat' => '',
                'nama_dokter' => '',
                'nip_dokter' => '',
                'diagnosa' => '',
                'keadaan_umum' => 'Sedang',
                'gcs_e' => '4',
                'gcs_v' => '5',
                'gcs_m' => '6',
                'kesadaran_kualitatif' => 'Compos Mentis',
                'pernafasan' => 'Spontan',
                'pernafasan_o2' => '',
                'alat_iv_line' => 1,
                'alat_iv_line_jalur' => '1',
                'alat_ngt' => 0,
                'alat_drain' => 0,
                'alat_kateter' => 0,
                'alat_lain_lain' => '',
                'monitoring_tanda_vital' => '[]'
            ];
        }

        $data['monitoring_list'] = !empty($data['monitoring_tanda_vital']) ? json_decode($data['monitoring_tanda_vital'], true) : [];
        if (!is_array($data['monitoring_list'])) { $data['monitoring_list'] = []; }

        $petugas_list = $this->db('petugas')->select('nip, nama')->toArray();
        $dokter_list = $this->db('dokter')->select('kd_dokter, nm_dokter')->toArray();

        return $this->draw('lampiran_stabilisasi_pasien/form.html', [
            'data' => $data,
            'petugas_list' => $petugas_list,
            'dokter_list' => $dokter_list
        ]);
    }

    public function postStabilisasipasiensave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rawat)) {
            $this->notify('failure', 'No. Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'stabilisasipasienform']));
        }

        // Monitoring tanda-tanda vital array
        $vital_arr = [];
        if (isset($_POST['vital_jam']) && is_array($_POST['vital_jam'])) {
            foreach ($_POST['vital_jam'] as $k => $jam) {
                $td = trim($_POST['vital_td'][$k] ?? '');
                $hr = trim($_POST['vital_hr'][$k] ?? '');
                $rr = trim($_POST['vital_rr'][$k] ?? '');
                $t = trim($_POST['vital_t'][$k] ?? '');
                $ket = trim($_POST['vital_ket'][$k] ?? '');
                if (!empty(trim($jam)) || !empty($td) || !empty($hr) || !empty($rr) || !empty($t) || !empty($ket)) {
                    $vital_arr[] = [
                        'jam' => trim($jam),
                        'td' => $td,
                        'hr' => $hr,
                        'rr' => $rr,
                        't' => $t,
                        'ket' => $ket
                    ];
                }
            }
        }

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal_stabilisasi' => !empty($_POST['tanggal_stabilisasi']) ? $_POST['tanggal_stabilisasi'] : date('Y-m-d'),
            'jam_stabilisasi' => !empty($_POST['jam_stabilisasi']) ? $_POST['jam_stabilisasi'] : date('H:i:s'),
            'nama_perawat' => trim($_POST['nama_perawat'] ?? ''),
            'nip_perawat' => trim($_POST['nip_perawat'] ?? ''),
            'nama_dokter' => trim($_POST['nama_dokter'] ?? ''),
            'nip_dokter' => trim($_POST['nip_dokter'] ?? ''),
            'diagnosa' => trim($_POST['diagnosa'] ?? ''),
            'keadaan_umum' => trim($_POST['keadaan_umum'] ?? 'Sedang'),
            'gcs_e' => trim($_POST['gcs_e'] ?? ''),
            'gcs_v' => trim($_POST['gcs_v'] ?? ''),
            'gcs_m' => trim($_POST['gcs_m'] ?? ''),
            'kesadaran_kualitatif' => trim($_POST['kesadaran_kualitatif'] ?? 'Compos Mentis'),
            'pernafasan' => trim($_POST['pernafasan'] ?? 'Spontan'),
            'pernafasan_o2' => trim($_POST['pernafasan_o2'] ?? ''),
            'alat_iv_line' => isset($_POST['alat_iv_line']) ? 1 : 0,
            'alat_iv_line_jalur' => trim($_POST['alat_iv_line_jalur'] ?? ''),
            'alat_ngt' => isset($_POST['alat_ngt']) ? 1 : 0,
            'alat_drain' => isset($_POST['alat_drain']) ? 1 : 0,
            'alat_kateter' => isset($_POST['alat_kateter']) ? 1 : 0,
            'alat_lain_lain' => trim($_POST['alat_lain_lain'] ?? ''),
            'monitoring_tanda_vital' => json_encode($vital_arr, JSON_UNESCAPED_UNICODE)
        ];

        if (!empty($id)) {
            $this->db('lampiran_stabilisasi_pasien')->where('id', $id)->update($payload);
            $this->notify('success', 'Formulir Stabilisasi Pasien berhasil diperbarui.');
        } else {
            $this->db('lampiran_stabilisasi_pasien')->save($payload);
            $this->notify('success', 'Formulir Stabilisasi Pasien baru berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'stabilisasipasienmanage']));
    }

    public function getStabilisasipasienhapus()
    {
        $id = $_GET['id'] ?? '';
        if (!empty($id)) {
            $this->db('lampiran_stabilisasi_pasien')->where('id', $id)->delete();
            $this->notify('success', 'Formulir Stabilisasi Pasien berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'stabilisasipasienmanage']));
    }

    public function getStabilisasipasiencetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_stabilisasi_pasien')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_stabilisasi_pasien.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_stabilisasi_pasien.id', $id)
            ->select('lampiran_stabilisasi_pasien.*, COALESCE(lampiran_stabilisasi_pasien.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rm;

        // Umur pasien
        preg_match('/\d+/', $data['umur'] ?? '', $matches);
        $data['umur_angka'] = $matches[0] ?? '-';

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Format tanggal stabilisasi
        if (!empty($data['tanggal_stabilisasi']) && $data['tanggal_stabilisasi'] != '0000-00-00') {
            $tglStab = explode('-', $data['tanggal_stabilisasi']);
            if (count($tglStab) == 3) {
                $data['tanggal_stabilisasi_indo'] = (int)$tglStab[2] . ' ' . $bulan[(int)$tglStab[1]] . ' ' . $tglStab[0];
            } else {
                $data['tanggal_stabilisasi_indo'] = $data['tanggal_stabilisasi'];
            }
        } else {
            $data['tanggal_stabilisasi_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Monitoring list decoding
        $vitalList = !empty($data['monitoring_tanda_vital']) ? json_decode($data['monitoring_tanda_vital'], true) : [];
        if (!is_array($vitalList)) { $vitalList = []; }

        // Normalize keterangan field
        $normalizedVital = [];
        foreach ($vitalList as $v) {
            $normalizedVital[] = [
                'jam' => $v['jam'] ?? '',
                'td' => $v['td'] ?? '',
                'hr' => $v['hr'] ?? '',
                'rr' => $v['rr'] ?? '',
                't' => $v['t'] ?? '',
                'ket' => !empty($v['keterangan']) ? $v['keterangan'] : ($v['ket'] ?? '')
            ];
        }

        // Pad with blank rows to match template table (at least 8 rows)
        $targetRows = max(8, count($normalizedVital));
        $paddedVital = $normalizedVital;
        while (count($paddedVital) < $targetRows) {
            $paddedVital[] = ['jam' => '', 'td' => '', 'hr' => '', 'rr' => '', 't' => '', 'ket' => ''];
        }
        $data['vital_rows'] = $paddedVital;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_stabilisasi_pasien/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Stabilisasi_Pasien_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_stabilisasi_pasien/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - INTERVENSI DAN PENGKAJIAN ULANG NYERI
    // ============================================================

    public function anyIntervensinyerimanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_intervensi_pengkajian_nyeri')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_intervensi_pengkajian_nyeri.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_intervensi_pengkajian_nyeri.*, COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->desc('lampiran_intervensi_pengkajian_nyeri.id')
            ->toArray();

        foreach ($rows as &$r) {
            $details = !empty($r['detail_pengkajian']) ? json_decode($r['detail_pengkajian'], true) : [];
            $r['total_catatan'] = is_array($details) ? count($details) : 0;
        }

        return $this->draw('lampiran_intervensi_pengkajian_nyeri/manage.html', ['data' => $rows]);
    }

    public function getIntervensinyeriform($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');

        // AJAX pencarian pasien
        if (isset($_GET['ajax_patient'])) {
            $no_rawat = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                    $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

                    // Cari ruang perawatan dari kamar_inap jika ada
                    $km = $this->db('kamar_inap')
                        ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                        ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                        ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                        ->desc('kamar_inap.tgl_masuk')
                        ->desc('kamar_inap.jam_masuk')
                        ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                        ->oneArray();

                    if ($km && !empty($km['nm_bangsal'])) {
                        $reg['ruang_perawatan'] = $km['nm_bangsal'] . ' (' . $km['kd_kamar'] . ')';
                    } else {
                        $reg['ruang_perawatan'] = 'Rawat Inap';
                    }

                    $result = array_merge(['success' => true], $reg);
                }
            }

            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            exit();
        }

        $data = [];
        if (!empty($id)) {
            $data = $this->db('lampiran_intervensi_pengkajian_nyeri')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_intervensi_pengkajian_nyeri.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_intervensi_pengkajian_nyeri.id', $id)
                ->select('lampiran_intervensi_pengkajian_nyeri.*, COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'nm_pasien' => '',
                'jk' => '',
                'tmp_lahir' => '',
                'tgl_lahir' => '',
                'umur' => '',
                'alamat' => '',
                'instalasi_unit' => '',
                'detail_pengkajian' => '[]',
                'catatan_tambahan' => ''
            ];
        }

        $rawItems = !empty($data['detail_pengkajian']) ? json_decode($data['detail_pengkajian'], true) : [];
        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $items = [];
        foreach ($rawItems as $idx => $it) {
            $items[] = [
                'idx' => $idx,
                'tgl_jam' => $it['tgl_jam'] ?? '',
                'skor_nyeri' => $it['skor_nyeri'] ?? '',
                'td' => $it['td'] ?? '',
                'nadi' => $it['nadi'] ?? '',
                'napas' => $it['napas'] ?? '',
                'suhu' => $it['suhu'] ?? '',
                'obat_nama' => $it['obat_nama'] ?? '',
                'obat_dosis' => $it['obat_dosis'] ?? '',
                'obat_rute' => $it['obat_rute'] ?? '',
                'non_farmakologi' => $it['non_farmakologi'] ?? '',
                'petugas_nama' => $it['petugas_nama'] ?? '',
                'petugas_paraf' => $it['petugas_paraf'] ?? '',
                'waktu_kaji_ulang' => $it['waktu_kaji_ulang'] ?? ''
            ];
        }

        $data['items'] = $items;
        $data['total_items_count'] = count($items);

        return $this->draw('lampiran_intervensi_pengkajian_nyeri/form.html', ['data' => $data]);
    }

    public function postIntervensinyerisave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (!strpos($no_rawat, '/')) {
            $reg = $this->db('reg_periksa')->where('no_rkm_medis', $no_rawat)->desc('tgl_registrasi')->desc('jam_reg')->oneArray();
            if ($reg) {
                $no_rawat = $reg['no_rawat'];
            }
        }

        $no_rkm_medis = '';
        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        if ($reg) {
            $no_rkm_medis = $reg['no_rkm_medis'];
        }

        // Susun array detail record
        $recordInput = $_POST['record'] ?? [];
        $records = [];
        if (is_array($recordInput)) {
            foreach ($recordInput as $row) {
                if (!empty($row['tgl_jam']) || !empty($row['skor_nyeri']) || !empty($row['obat_nama']) || !empty($row['non_farmakologi'])) {
                    $records[] = [
                        'tgl_jam' => trim($row['tgl_jam'] ?? ''),
                        'skor_nyeri' => trim($row['skor_nyeri'] ?? ''),
                        'td' => trim($row['td'] ?? ''),
                        'nadi' => trim($row['nadi'] ?? ''),
                        'napas' => trim($row['napas'] ?? ''),
                        'suhu' => trim($row['suhu'] ?? ''),
                        'obat_nama' => trim($row['obat_nama'] ?? ''),
                        'obat_dosis' => trim($row['obat_dosis'] ?? ''),
                        'obat_rute' => trim($row['obat_rute'] ?? ''),
                        'non_farmakologi' => trim($row['non_farmakologi'] ?? ''),
                        'petugas_nama' => trim($row['petugas_nama'] ?? ''),
                        'petugas_paraf' => trim($row['petugas_paraf'] ?? ''),
                        'waktu_kaji_ulang' => trim($row['waktu_kaji_ulang'] ?? '')
                    ];
                }
            }
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'instalasi_unit' => trim($_POST['instalasi_unit'] ?? ''),
            'detail_pengkajian' => json_encode($records, JSON_UNESCAPED_UNICODE),
            'catatan_tambahan' => trim($_POST['catatan_tambahan'] ?? '')
        ];

        if (!empty($id)) {
            $this->db('lampiran_intervensi_pengkajian_nyeri')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Intervensi dan Pengkajian Ulang Nyeri berhasil diperbarui');
        } else {
            $this->db('lampiran_intervensi_pengkajian_nyeri')->save($saveData);
            $this->notify('success', 'Data Intervensi dan Pengkajian Ulang Nyeri berhasil disimpan');
        }

        redirect(url([ADMIN, 'update_bmt', 'intervensinyerimanage']));
    }

    public function getIntervensinyerihapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!empty($id)) {
            $this->db('lampiran_intervensi_pengkajian_nyeri')->where('id', $id)->delete();
            $this->notify('success', 'Data Intervensi dan Pengkajian Ulang Nyeri berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'intervensinyerimanage']));
    }

    public function getIntervensinyericetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_intervensi_pengkajian_nyeri')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_intervensi_pengkajian_nyeri.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_intervensi_pengkajian_nyeri.id', $id)
            ->select('lampiran_intervensi_pengkajian_nyeri.*, COALESCE(lampiran_intervensi_pengkajian_nyeri.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Decode list records
        $recordList = !empty($data['detail_pengkajian']) ? json_decode($data['detail_pengkajian'], true) : [];
        if (!is_array($recordList)) {
            $recordList = [];
        }

        // Standar baris minimal 20 baris agar pas 1 halaman A4 penuh presisi formulir fisik
        $targetRows = max(20, count($recordList));
        $paddedRecords = $recordList;
        while (count($paddedRecords) < $targetRows) {
            $paddedRecords[] = [
                'tgl_jam' => '&nbsp;',
                'skor_nyeri' => '&nbsp;',
                'td' => '&nbsp;',
                'nadi' => '&nbsp;',
                'napas' => '&nbsp;',
                'suhu' => '&nbsp;',
                'obat_nama' => '&nbsp;',
                'obat_dosis' => '&nbsp;',
                'obat_rute' => '&nbsp;',
                'non_farmakologi' => '&nbsp;',
                'petugas_nama' => '&nbsp;',
                'petugas_paraf' => '&nbsp;',
                'waktu_kaji_ulang' => '&nbsp;'
            ];
        }
        $data['record_rows'] = $paddedRecords;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 4,
                    'margin_bottom' => 4
                ]);
                $html = $this->draw('lampiran_intervensi_pengkajian_nyeri/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Pengkajian_Ulang_Nyeri_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_intervensi_pengkajian_nyeri/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - SURAT PERMINTAAN SECOND OPINION (RANAP)
    // ============================================================

    public function anySecondopinionmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_permintaan_second_opinion')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_second_opinion.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_permintaan_second_opinion.*, COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->desc('lampiran_permintaan_second_opinion.id')
            ->toArray();

        return $this->draw('lampiran_permintaan_second_opinion/manage.html', ['data' => $rows]);
    }

    public function getSecondopinionform($id = null)
    {
        // Handle AJAX Patient Auto-fill
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $noRawat = trim($_GET['no_rawat'] ?? '');
            $patient = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $noRawat)
                ->orWhere('reg_periksa.no_rkm_medis', $noRawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat, bangsal.nm_bangsal, kamar.kd_kamar')
                ->desc('reg_periksa.tgl_registrasi')
                ->oneArray();

            if ($patient) {
                $rmRaw = ltrim($patient['no_rkm_medis'] ?? '', "'");
                $patient['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $ruang = '';
                if (!empty($patient['nm_bangsal'])) {
                    $ruang = $patient['nm_bangsal'] . (!empty($patient['kd_kamar']) ? ' / Kamar ' . $patient['kd_kamar'] : '');
                }
                $patient['ruang_perawatan'] = $ruang;
                echo json_encode(['success' => true, 'data' => $patient]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pasien tidak ditemukan']);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nama_pembuat' => '',
            'jk_pembuat' => 'L',
            'no_identitas_pembuat' => '',
            'umur_pembuat' => '',
            'alamat_pembuat' => '',
            'hubungan' => 'Diri sendiri',
            'hubungan_detail' => '',
            'dokter_tujuan' => '',
            'rs_tujuan' => '',
            'dokumen_penunjang' => '',
            'kota' => 'Banjarmasin',
            'tanggal_ttd' => date('Y-m-d'),
            'nama_petugas' => '',
            'nip_petugas' => '',
            'nama_saksi' => '',
            'pembuat_pernyataan_ttd' => '',
            'tgl_pengembalian_dokumen' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => ''
        ];

        if ($id) {
            $row = $this->db('lampiran_permintaan_second_opinion')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_second_opinion.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_permintaan_second_opinion.id', $id)
                ->select('lampiran_permintaan_second_opinion.*, COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($row) {
                $data = array_merge($data, $row);
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            }
        }

        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_permintaan_second_opinion/form.html', [
            'data' => $data,
            'petugas' => $petugas
        ]);
    }

    public function postSecondopinionsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'secondopinionmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'nama_pembuat' => $_POST['nama_pembuat'] ?? '',
            'jk_pembuat' => $_POST['jk_pembuat'] ?? 'L',
            'no_identitas_pembuat' => $_POST['no_identitas_pembuat'] ?? '',
            'umur_pembuat' => $_POST['umur_pembuat'] ?? '',
            'alamat_pembuat' => $_POST['alamat_pembuat'] ?? '',
            'hubungan' => $_POST['hubungan'] ?? 'Diri sendiri',
            'hubungan_detail' => $_POST['hubungan_detail'] ?? '',
            'dokter_tujuan' => $_POST['dokter_tujuan'] ?? '',
            'rs_tujuan' => $_POST['rs_tujuan'] ?? '',
            'dokumen_penunjang' => $_POST['dokumen_penunjang'] ?? '',
            'kota' => !empty($_POST['kota']) ? $_POST['kota'] : 'Banjarmasin',
            'tanggal_ttd' => !empty($_POST['tanggal_ttd']) ? $_POST['tanggal_ttd'] : date('Y-m-d'),
            'nama_petugas' => $_POST['nama_petugas'] ?? '',
            'nip_petugas' => $_POST['nip_petugas'] ?? '',
            'nama_saksi' => $_POST['nama_saksi'] ?? '',
            'pembuat_pernyataan_ttd' => !empty($_POST['pembuat_pernyataan_ttd']) ? $_POST['pembuat_pernyataan_ttd'] : ($_POST['nama_pembuat'] ?? ''),
            'tgl_pengembalian_dokumen' => $_POST['tgl_pengembalian_dokumen'] ?? ''
        ];

        if ($id) {
            $this->db('lampiran_permintaan_second_opinion')->where('id', $id)->save($saveData);
            $this->setNotify('success', 'Data Formulir Surat Permintaan Second Opinion berhasil diperbarui.');
        } else {
            $this->db('lampiran_permintaan_second_opinion')->save($saveData);
            $this->setNotify('success', 'Data Formulir Surat Permintaan Second Opinion berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'secondopinionmanage']));
    }

    public function getSecondopinionhapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('lampiran_permintaan_second_opinion')->where('id', $id)->delete();
            $this->setNotify('success', 'Data Formulir Surat Permintaan Second Opinion berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'secondopinionmanage']));
    }

    public function getSecondopinioncetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_permintaan_second_opinion')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_second_opinion.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_permintaan_second_opinion.id', $id)
            ->select('lampiran_permintaan_second_opinion.*, COALESCE(lampiran_permintaan_second_opinion.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat as alamat_pasien')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        if (!empty($data['tanggal_ttd']) && $data['tanggal_ttd'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_ttd']);
            if (count($tgl) == 3) {
                $data['tanggal_ttd_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_ttd_indo'] = $data['tanggal_ttd'];
            }
        } else {
            $data['tanggal_ttd_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Parsing dokumen penunjang (per baris)
        $dokumenList = [];
        if (!empty($data['dokumen_penunjang'])) {
            $rawList = explode("\n", str_replace("\r", "", $data['dokumen_penunjang']));
            foreach ($rawList as $item) {
                $item = trim(ltrim(trim($item), '-*•'));
                if ($item !== '') {
                    $dokumenList[] = $item;
                }
            }
        }
        if (count($dokumenList) < 2) {
            while (count($dokumenList) < 2) {
                $dokumenList[] = '';
            }
        }
        $data['dokumen_list'] = $dokumenList;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_permintaan_second_opinion/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Surat_Permintaan_Second_Opinion_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_permintaan_second_opinion/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PENILAIAN KEJADIAN PHLEIBITIS (VISUAL INFUSION PHLEIBITIS / VIP)
    // ============================================================

    public function anyPenilaianphlebitismanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('lampiran_penilaian_kejadian_phlebitis')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_phlebitis.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_penilaian_kejadian_phlebitis.*, COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->desc('lampiran_penilaian_kejadian_phlebitis.id')
            ->toArray();

        $no = 1;
        foreach ($rows as &$r) {
            $r['nomor'] = $no++;
            $rmRaw = ltrim($r['no_rkm_medis'] ?? '', "'");
            $r['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

            $details = !empty($r['detail_penilaian']) ? json_decode($r['detail_penilaian'], true) : [];
            $terpantau = 0;
            $maxSkor = null;

            if (is_array($details)) {
                for ($h = 1; $h <= 9; $h++) {
                    if (isset($details[$h])) {
                        $item = $details[$h];
                        if (!empty($item['tgl']) || ($item['skor'] !== '' && $item['skor'] !== null)) {
                            $terpantau++;
                            if ($item['skor'] !== '' && $item['skor'] !== null) {
                                $sVal = (int)$item['skor'];
                                if ($maxSkor === null || $sVal > $maxSkor) {
                                    $maxSkor = $sVal;
                                }
                            }
                        }
                    }
                }
            }

            $r['hari_terpantau'] = $terpantau;
            $r['max_skor'] = $maxSkor;
        }

        return $this->draw('lampiran_penilaian_kejadian_phlebitis/manage.html', ['data' => $rows]);
    }

    public function getPenilaianphlebitisform($id = null)
    {
        // Handle AJAX Patient Auto-fill
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $noRawat = trim($_GET['no_rawat'] ?? '');
            $patient = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $noRawat)
                ->orWhere('reg_periksa.no_rkm_medis', $noRawat)
                ->desc('reg_periksa.tgl_registrasi')
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, bangsal.nm_bangsal, kamar.kd_kamar')
                ->oneArray();

            if ($patient) {
                $rmRaw = ltrim($patient['no_rkm_medis'] ?? '', "'");
                $patient['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $patient['ruang_perawatan'] = !empty($patient['nm_bangsal']) ? $patient['nm_bangsal'] . ' (' . $patient['kd_kamar'] . ')' : 'Rawat Inap';
                echo json_encode(array_merge(['success' => true], $patient));
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        $data = [];
        if ($id) {
            $data = $this->db('lampiran_penilaian_kejadian_phlebitis')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_phlebitis.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_penilaian_kejadian_phlebitis.id', $id)
                ->select('lampiran_penilaian_kejadian_phlebitis.*, COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'nm_pasien' => '',
                'jk' => '',
                'tgl_lahir' => '',
                'umur' => '',
                'kamar_inap' => '',
                'lokasi_iv_line' => 'Metacarpal Dextra',
                'tgl_pemasangan' => date('Y-m-d'),
                'tipe_kanul' => 'Abocath 22G',
                'catatan' => '',
                'detail_penilaian' => ''
            ];
        } else {
            $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
            $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        }

        $detailArr = !empty($data['detail_penilaian']) ? json_decode($data['detail_penilaian'], true) : [];
        if (!is_array($detailArr)) {
            $detailArr = [];
        }

        $days = [];
        for ($h = 1; $h <= 9; $h++) {
            $item = $detailArr[$h] ?? [];
            $days[] = [
                'hari' => $h,
                'tgl' => $item['tgl'] ?? '',
                'param_checked' => $item['param_checked'] ?? '',
                'skor' => (isset($item['skor']) && $item['skor'] !== '') ? (int)$item['skor'] : '',
                'petugas' => $item['petugas'] ?? '',
                'paraf' => $item['paraf'] ?? '✓'
            ];
        }
        $data['days'] = $days;

        return $this->draw('lampiran_penilaian_kejadian_phlebitis/form.html', ['data' => $data]);
    }

    public function postPenilaianphlebitissave()
    {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            redirect(url([ADMIN, 'update_bmt', 'penilaianphlebitismanage']));
            return;
        }

        $hariPost = $_POST['hari'] ?? [];
        $detailArr = [];
        for ($h = 1; $h <= 9; $h++) {
            $tgl = trim($hariPost[$h]['tgl'] ?? '');
            $paramChecked = trim($hariPost[$h]['param_checked'] ?? '');
            $skor = isset($hariPost[$h]['skor']) && $hariPost[$h]['skor'] !== '' ? (int)$hariPost[$h]['skor'] : '';
            $petugas = trim($hariPost[$h]['petugas'] ?? '');
            $paraf = trim($hariPost[$h]['paraf'] ?? '');

            $detailArr[$h] = [
                'tgl' => $tgl,
                'param_checked' => $paramChecked,
                'skor' => $skor,
                'petugas' => $petugas,
                'paraf' => $paraf
            ];
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => trim($_POST['no_rkm_medis'] ?? ''),
            'kamar_inap' => trim($_POST['kamar_inap'] ?? ''),
            'lokasi_iv_line' => trim($_POST['lokasi_iv_line'] ?? ''),
            'tgl_pemasangan' => !empty($_POST['tgl_pemasangan']) ? $_POST['tgl_pemasangan'] : null,
            'tipe_kanul' => trim($_POST['tipe_kanul'] ?? ''),
            'catatan' => trim($_POST['catatan'] ?? ''),
            'detail_penilaian' => json_encode($detailArr)
        ];

        if ($id) {
            $this->db('lampiran_penilaian_kejadian_phlebitis')->where('id', $id)->save($saveData);
        } else {
            $this->db('lampiran_penilaian_kejadian_phlebitis')->save($saveData);
        }

        redirect(url([ADMIN, 'update_bmt', 'penilaianphlebitismanage']));
    }

    public function getPenilaianphlebitishapus($id)
    {
        if ($id) {
            $this->db('lampiran_penilaian_kejadian_phlebitis')->where('id', $id)->delete();
        }
        redirect(url([ADMIN, 'update_bmt', 'penilaianphlebitismanage']));
    }

    public function getPenilaianphlebitiscetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_penilaian_kejadian_phlebitis')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_phlebitis.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_penilaian_kejadian_phlebitis.id', $id)
            ->select('lampiran_penilaian_kejadian_phlebitis.*, COALESCE(lampiran_penilaian_kejadian_phlebitis.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
            ->oneArray();

        if (!$data) {
            echo "Data penilaian phlebitis tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        $detailArr = !empty($data['detail_penilaian']) ? json_decode($data['detail_penilaian'], true) : [];
        if (!is_array($detailArr)) {
            $detailArr = [];
        }

        for ($h = 1; $h <= 9; $h++) {
            $item = $detailArr[$h] ?? [];
            $tglStr = !empty($item['tgl']) ? date('d/m', strtotime($item['tgl'])) : '.....';
            $data['h' . $h . '_tgl_label'] = 'Tgl ' . $tglStr;

            $pChecked = (int)($item['param_checked'] ?? 0);
            for ($p = 1; $p <= 6; $p++) {
                $data['h' . $h . '_p' . $p] = ($pChecked === $p) ? '&#10003;' : '&nbsp;';
            }

            $skorVal = (isset($item['skor']) && $item['skor'] !== '' && $pChecked > 0) ? $item['skor'] : '&nbsp;';
            $data['h' . $h . '_skor'] = $skorVal;

            $petugasStr = '';
            if (!empty($item['petugas']) && $pChecked > 0) {
                $petugasStr = htmlspecialchars($item['petugas']);
                if (!empty($item['paraf']) && $item['paraf'] == '✓') {
                    $petugasStr .= '<br><span style="font-size: 8pt; color: #27ae60;">&#10003;</span>';
                }
            } else {
                $petugasStr = '&nbsp;';
            }
            $data['h' . $h . '_petugas'] = $petugasStr;
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 4,
                    'margin_bottom' => 4
                ]);
                $html = $this->draw('lampiran_penilaian_kejadian_phlebitis/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Penilaian_Phlebitis_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penilaian_kejadian_phlebitis/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PERMINTAAN PELAYANAN KEROHANIAN
    // ============================================================

    public function anyPelayanankerohanianmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $kerohanian = $this->db('lampiran_permintaan_pelayanan_kerohanian')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_pelayanan_kerohanian.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_permintaan_pelayanan_kerohanian.*, COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_permintaan_pelayanan_kerohanian.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat as alamat_pasien')
            ->desc('lampiran_permintaan_pelayanan_kerohanian.id')
            ->toArray();

        $no = 1;
        foreach ($kerohanian as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';
        }

        return $this->draw('lampiran_permintaan_pelayanan_kerohanian/manage.html', [
            'data' => $kerohanian,
            'kerohanian' => $kerohanian
        ]);
    }

    public function getPelayanankerohanianform($id = null)
    {
        // AJAX: Cari data pasien via No. Rawat / No. RM
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $no_rawat = trim($_GET['no_rawat'] ?? '');

            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($reg) {
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

                // Cari kamar inap bila ada
                $kamar = $this->db('kamar_inap')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                    ->desc('kamar_inap.tgl_masuk')
                    ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                    ->oneArray();

                $reg['ruang_perawatan'] = $kamar ? ($kamar['nm_bangsal'] . ' / ' . $kamar['kd_kamar']) : 'Rawat Inap';

                echo json_encode(['success' => true, 'data' => $reg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pasien tidak ditemukan']);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'kamar_inap' => '',
            'nama_pemohon' => '',
            'tgl_lahir_pemohon' => '',
            'umur_pemohon' => '',
            'alamat_pemohon' => '',
            'hubungan_pasien' => 'Diri Sendiri',
            'agama_kepercayaan' => 'Islam',
            'kebutuhan_khusus' => '',
            'nama_rohaniawan' => '',
            'kota' => 'Banjarmasin',
            'tanggal_surat' => date('Y-m-d'),
            'nama_perawat' => '',
            'nip_perawat' => '',
            'catatan' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => ''
        ];

        if ($id) {
            $row = $this->db('lampiran_permintaan_pelayanan_kerohanian')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_pelayanan_kerohanian.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_permintaan_pelayanan_kerohanian.id', $id)
                ->select('lampiran_permintaan_pelayanan_kerohanian.*, COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($row) {
                $data = array_merge($data, $row);
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            }
        }

        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_permintaan_pelayanan_kerohanian/form.html', [
            'data' => $data,
            'petugas' => $petugas
        ]);
    }

    public function postPelayanankerohaniansave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'pelayanankerohanianmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'nama_pemohon' => $_POST['nama_pemohon'] ?? '',
            'tgl_lahir_pemohon' => !empty($_POST['tgl_lahir_pemohon']) ? $_POST['tgl_lahir_pemohon'] : null,
            'umur_pemohon' => $_POST['umur_pemohon'] ?? '',
            'alamat_pemohon' => $_POST['alamat_pemohon'] ?? '',
            'hubungan_pasien' => $_POST['hubungan_pasien'] ?? 'Diri Sendiri',
            'agama_kepercayaan' => $_POST['agama_kepercayaan'] ?? '',
            'kebutuhan_khusus' => $_POST['kebutuhan_khusus'] ?? '',
            'nama_rohaniawan' => $_POST['nama_rohaniawan'] ?? '',
            'kota' => !empty($_POST['kota']) ? $_POST['kota'] : 'Banjarmasin',
            'tanggal_surat' => !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : date('Y-m-d'),
            'nama_perawat' => $_POST['nama_perawat'] ?? '',
            'nip_perawat' => $_POST['nip_perawat'] ?? '',
            'catatan' => $_POST['catatan'] ?? ''
        ];

        if ($id) {
            $saveData['updated_at'] = date('Y-m-d H:i:s');
            $this->db('lampiran_permintaan_pelayanan_kerohanian')->where('id', $id)->save($saveData);
            $this->setNotify('success', 'Data Permintaan Pelayanan Kerohanian berhasil diperbarui.');
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $saveData['updated_at'] = date('Y-m-d H:i:s');
            $this->db('lampiran_permintaan_pelayanan_kerohanian')->save($saveData);
            $this->setNotify('success', 'Data Permintaan Pelayanan Kerohanian berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'pelayanankerohanianmanage']));
    }

    public function getPelayanankerohanihapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('lampiran_permintaan_pelayanan_kerohanian')->where('id', $id)->delete();
            $this->setNotify('success', 'Data Permintaan Pelayanan Kerohanian berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'pelayanankerohanianmanage']));
    }

    public function getPelayanankerohanicetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_permintaan_pelayanan_kerohanian')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_permintaan_pelayanan_kerohanian.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_permintaan_pelayanan_kerohanian.id', $id)
            ->select('lampiran_permintaan_pelayanan_kerohanian.*, COALESCE(lampiran_permintaan_pelayanan_kerohanian.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat as alamat_pasien')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Tanggal Lahir Pemohon
        if (!empty($data['tgl_lahir_pemohon']) && $data['tgl_lahir_pemohon'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir_pemohon']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_pemohon_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_pemohon_indo'] = $data['tgl_lahir_pemohon'];
            }
        } else {
            $data['tgl_lahir_pemohon_indo'] = '';
        }

        // Tanggal Surat
        if (!empty($data['tanggal_surat']) && $data['tanggal_surat'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_surat']);
            if (count($tgl) == 3) {
                $data['tanggal_surat_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_surat_indo'] = $data['tanggal_surat'];
            }
        } else {
            $data['tanggal_surat_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_permintaan_pelayanan_kerohanian/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Permintaan_Pelayanan_Kerohanian_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_permintaan_pelayanan_kerohanian/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // RML - MONITORING PASIEN KATETER URINE (RANAP)
    // ============================================================

    public function anyMonitoringkateterurinmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $monitoring = $this->db('lampiran_monitoring_pasien_kateter_urin')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_kateter_urin.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_monitoring_pasien_kateter_urin.*, COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_monitoring_pasien_kateter_urin.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat')
            ->desc('lampiran_monitoring_pasien_kateter_urin.id')
            ->toArray();

        $no = 1;
        foreach ($monitoring as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        }

        return $this->draw('lampiran_monitoring_pasien_kateter_urin/manage.html', [
            'data' => $monitoring,
            'monitoring' => $monitoring
        ]);
    }

    public function getMonitoringkateterurinform($id = null)
    {
        // AJAX: Cari data pasien via No. Rawat / No. RM
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $no_rawat = trim($_GET['no_rawat'] ?? '');

            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($reg) {
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

                // Cari kamar inap bila ada
                $kamar = $this->db('kamar_inap')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                    ->desc('kamar_inap.tgl_masuk')
                    ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                    ->oneArray();

                $reg['ruang_perawatan'] = $kamar ? ($kamar['nm_bangsal'] . ' / ' . $kamar['kd_kamar']) : 'Rawat Inap';

                echo json_encode(['success' => true, 'data' => $reg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pasien tidak ditemukan']);
            }
            exit();
        }

        $itemsPencegahan = [
            ['key' => 'pasang', 'label' => 'Pasang', 'icon' => ''],
            ['key' => 'lepas', 'label' => 'Lepas', 'icon' => ''],
            ['key' => 'cuci_tangan', 'label' => 'Cuci tangan sesuai 5 moment', 'icon' => ''],
            ['key' => 'teknik_aseptik', 'label' => 'Pemasangan dengan teknik aseptik', 'icon' => ''],
            ['key' => 'fiksasi', 'label' => 'Fiksasi dg baik', 'icon' => ''],
            ['key' => 'urin_bag_bwh_bladder', 'label' => 'Urin bag di bwh bladder', 'icon' => ''],
            ['key' => 'posisi_kateter', 'label' => 'Posisi kateter baik/tdk tertekuk, terlipat', 'icon' => ''],
            ['key' => 'urin_bag_lantai', 'label' => 'Urin bag tidak menyentuh lantai', 'icon' => ''],
            ['key' => 'tidak_bladder_training', 'label' => 'Tidak bladder training dgn klem', 'icon' => ''],
            ['key' => 'tidak_buka_sambungan', 'label' => 'Tidak membuka sambungan antara cath dan selang urin bag', 'icon' => ''],
            ['key' => 'perineal_hygiene', 'label' => 'Perineal hygiene dg air dan sabun 2x/hari', 'icon' => ''],
            ['key' => 'gelas_ukur_terpisah', 'label' => 'Gelas ukur terpisah antar pasien', 'icon' => ''],
            ['key' => 'ada_indikasi', 'label' => 'Ada indikasi pemakaian kateter urin', 'icon' => '']
        ];

        $itemsGejala = [
            ['key' => 'gejala_demam', 'label' => 'Demam ≥38° C', 'prefix' => 'a.1'],
            ['key' => 'gejala_nyeri_suprapubic', 'label' => 'Nyeri supra-pubic', 'prefix' => 'a.2'],
            ['key' => 'gejala_urgency', 'label' => 'Urgency/anyang2an', 'prefix' => 'a.3'],
            ['key' => 'gejala_frequency', 'label' => 'Frequency/sering kencing', 'prefix' => 'a.4'],
            ['key' => 'gejala_dysuria', 'label' => 'Dysuria/nyeri saatkencing', 'prefix' => 'a.5'],
            ['key' => 'gejala_nyeri_costovertebral', 'label' => 'Nyeri costovertebral angle', 'prefix' => 'a.6'],
            ['key' => 'gejala_kuman_biakan', 'label' => 'Kuman biakan urine ≥10³ atau ≥10⁵/ml', 'prefix' => 'b.'],
            ['key' => 'gejala_pyuria', 'label' => 'Pyuria (≥10 leukosit urin)', 'prefix' => 'c.']
        ];

        $id = $id ?? ($_GET['id'] ?? null);
        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $currBulanTahun = $bulanIndo[(int)date('m')] . ' ' . date('Y');

        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'ruang' => '',
            'bulan_tahun' => $currBulanTahun,
            'tgl_mrs' => date('Y-m-d'),
            'diagnosa' => '',
            'jenis_cath' => 'Folley',
            'jenis_cath_lainnya' => '',
            'no_cath' => '16',
            'no_cath_lainnya' => '',
            'detail_monitoring' => '',
            'detail_monitoring_data' => [],
            'nama_icn' => '',
            'nip_icn' => '',
            'nama_head_nurse' => '',
            'nip_head_nurse' => '',
            'catatan' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => '',
            'alamat' => ''
        ];

        if ($id) {
            $row = $this->db('lampiran_monitoring_pasien_kateter_urin')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_kateter_urin.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_monitoring_pasien_kateter_urin.id', $id)
                ->select('lampiran_monitoring_pasien_kateter_urin.*, COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($row) {
                $data = array_merge($data, $row);
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                if (!empty($data['detail_monitoring'])) {
                    $data['detail_monitoring_data'] = json_decode($data['detail_monitoring'], true) ?: [];
                }
            }
        }

        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_monitoring_pasien_kateter_urin/form.html', [
            'data' => $data,
            'items_pencegahan' => $itemsPencegahan,
            'items_gejala' => $itemsGejala,
            'petugas' => $petugas
        ]);
    }

    public function postMonitoringkateterurinsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'monitoringkateterurinmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'ruang' => $_POST['ruang'] ?? '',
            'bulan_tahun' => $_POST['bulan_tahun'] ?? '',
            'tgl_mrs' => !empty($_POST['tgl_mrs']) ? $_POST['tgl_mrs'] : null,
            'diagnosa' => $_POST['diagnosa'] ?? '',
            'jenis_cath' => $_POST['jenis_cath'] ?? 'Folley',
            'jenis_cath_lainnya' => $_POST['jenis_cath_lainnya'] ?? '',
            'no_cath' => $_POST['no_cath'] ?? '16',
            'no_cath_lainnya' => $_POST['no_cath_lainnya'] ?? '',
            'detail_monitoring' => $_POST['detail_monitoring_json'] ?? '',
            'nama_icn' => $_POST['nama_icn'] ?? '',
            'nip_icn' => $_POST['nip_icn'] ?? '',
            'nama_head_nurse' => $_POST['nama_head_nurse'] ?? '',
            'nip_head_nurse' => $_POST['nip_head_nurse'] ?? '',
            'catatan' => $_POST['catatan'] ?? ''
        ];

        if ($id) {
            $saveData['updated_at'] = date('Y-m-d H:i:s');
            $this->db('lampiran_monitoring_pasien_kateter_urin')->where('id', $id)->save($saveData);
            $this->setNotify('success', 'Data Monitoring Pasien Kateter Urin berhasil diperbarui.');
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $saveData['updated_at'] = date('Y-m-d H:i:s');
            $this->db('lampiran_monitoring_pasien_kateter_urin')->save($saveData);
            $this->setNotify('success', 'Data Monitoring Pasien Kateter Urin berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'monitoringkateterurinmanage']));
    }

    public function getMonitoringkateterurinhapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('lampiran_monitoring_pasien_kateter_urin')->where('id', $id)->delete();
            $this->setNotify('success', 'Data Monitoring Pasien Kateter Urin berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'monitoringkateterurinmanage']));
    }

    public function getMonitoringkateterurincetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? '');
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_monitoring_pasien_kateter_urin')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_kateter_urin.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_monitoring_pasien_kateter_urin.id', $id)
            ->select('lampiran_monitoring_pasien_kateter_urin.*, COALESCE(lampiran_monitoring_pasien_kateter_urin.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat as alamat_pasien')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Tanggal MRS
        if (!empty($data['tgl_mrs']) && $data['tgl_mrs'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_mrs']);
            if (count($tgl) == 3) {
                $data['tgl_mrs_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_mrs_indo'] = $data['tgl_mrs'];
            }
        } else {
            $data['tgl_mrs_indo'] = '-';
        }

        // Parsing detail monitoring grid
        $rawGrid = [];
        if (!empty($data['detail_monitoring'])) {
            $rawGrid = json_decode($data['detail_monitoring'], true) ?: [];
        }

        $itemsPencegahan = [
            ['key' => 'pasang', 'label' => 'Pasang', 'icon' => ''],
            ['key' => 'lepas', 'label' => 'Lepas', 'icon' => ''],
            ['key' => 'cuci_tangan', 'label' => 'Cuci tangan sesuai 5 moment', 'icon' => ''],
            ['key' => 'teknik_aseptik', 'label' => 'Pemasangan dengan teknik aseptik', 'icon' => ''],
            ['key' => 'fiksasi', 'label' => 'Fiksasi dg baik', 'icon' => ''],
            ['key' => 'urin_bag_bwh_bladder', 'label' => 'Urin bag di bwh bladder', 'icon' => ''],
            ['key' => 'posisi_kateter', 'label' => 'Posisi kateter baik/tdk tertekuk, terlipat', 'icon' => ''],
            ['key' => 'urin_bag_lantai', 'label' => 'Urin bag tidak menyentuh lantai', 'icon' => ''],
            ['key' => 'tidak_bladder_training', 'label' => 'Tidak bladder training dgn klem', 'icon' => ''],
            ['key' => 'tidak_buka_sambungan', 'label' => 'Tidak membuka sambungan antara cath dan selang urin bag', 'icon' => ''],
            ['key' => 'perineal_hygiene', 'label' => 'Perineal hygiene dg air dan sabun 2x/hari', 'icon' => ''],
            ['key' => 'gelas_ukur_terpisah', 'label' => 'Gelas ukur terpisah antar pasien', 'icon' => ''],
            ['key' => 'ada_indikasi', 'label' => 'Ada indikasi pemakaian kateter urin', 'icon' => '']
        ];

        $gridPencegahan = [];
        foreach ($itemsPencegahan as $item) {
            $rowObj = $rawGrid[$item['key']] ?? [];
            $days = [];
            for ($d = 1; $d <= 31; $d++) {
                $days[$d] = $rowObj['d' . $d] ?? '';
            }
            $gridPencegahan[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'days' => $days,
                'total' => $rowObj['total'] ?? '',
                'ketr' => $rowObj['ketr'] ?? ''
            ];
        }
        $data['grid_pencegahan'] = $gridPencegahan;

        $itemsGejala = [
            ['key' => 'gejala_demam', 'label' => 'Demam ≥38° C'],
            ['key' => 'gejala_nyeri_suprapubic', 'label' => 'Nyeri supra-pubic'],
            ['key' => 'gejala_urgency', 'label' => 'Urgency/anyang2an'],
            ['key' => 'gejala_frequency', 'label' => 'Frequency/sering kencing'],
            ['key' => 'gejala_dysuria', 'label' => 'Dysuria/nyeri saatkencing'],
            ['key' => 'gejala_nyeri_costovertebral', 'label' => 'Nyeri costovertebral angle'],
            ['key' => 'gejala_kuman_biakan', 'label' => 'Kuman biakan urine ≥10³ atau ≥10⁵/ml'],
            ['key' => 'gejala_pyuria', 'label' => 'Pyuria (≥10 leukosit urin)']
        ];

        $gridGejala = [];
        foreach ($itemsGejala as $item) {
            $rowObj = $rawGrid[$item['key']] ?? [];
            $days = [];
            for ($d = 1; $d <= 31; $d++) {
                $days[$d] = $rowObj['d' . $d] ?? '';
            }
            $gridGejala[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'days' => $days,
                'total' => $rowObj['total'] ?? '',
                'ketr' => $rowObj['ketr'] ?? ''
            ];
        }
        $data['grid_gejala'] = $gridGejala;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4-L',
                    'orientation' => 'L',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_monitoring_pasien_kateter_urin/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Monitoring_Kateter_Urin_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_monitoring_pasien_kateter_urin/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - KONSULTASI INTERNAL (Ranap)
    // ============================================================

    public function anyKonsultasiinternalmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $konsultasi = $this->db('lampiran_konsultasi_internal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_konsultasi_internal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_konsultasi_internal.*, COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_konsultasi_internal.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
            ->desc('lampiran_konsultasi_internal.id')
            ->toArray();

        $no = 1;
        foreach ($konsultasi as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';
        }

        return $this->draw('lampiran_konsultasi_internal/manage.html', [
            'data' => $konsultasi,
            'konsultasi' => $konsultasi
        ]);
    }

    public function getKonsultasiinternalform($id = null)
    {
        // AJAX: Cari data pasien via No. Rawat / No. RM
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $no_rawat = trim($_GET['no_rawat'] ?? '');

            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($reg) {
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

                // Cari kamar inap bila ada
                $kamar = $this->db('kamar_inap')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                    ->desc('kamar_inap.tgl_masuk')
                    ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                    ->oneArray();

                $reg['kamar_inap'] = $kamar ? ($kamar['nm_bangsal'] . ' / ' . $kamar['kd_kamar']) : 'Rawat Inap';

                echo json_encode(['success' => true, 'data' => $reg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pasien tidak ditemukan']);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'kamar_inap' => '',
            'ts_bagian' => '',
            'instalasi' => 'Instalasi Rawat Inap (IRNA)',
            'diagnosa_sementara' => '',
            'konsultasi_diminta' => '',
            'tgl_konsul' => date('Y-m-d'),
            'kd_dokter_pengirim' => '',
            'nama_dokter_pengirim' => '',
            'jawaban_hasil_pemeriksaan' => '',
            'jawaban_diagnosa' => '',
            'jawaban_nasihat' => '',
            'tgl_jawaban' => '',
            'kd_dokter_konsulen' => '',
            'nama_dokter_konsulen' => '',
            'status_konsul' => 'Menunggu Jawaban',
            'catatan' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => ''
        ];

        if ($id) {
            $row = $this->db('lampiran_konsultasi_internal')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_konsultasi_internal.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_konsultasi_internal.id', $id)
                ->select('lampiran_konsultasi_internal.*, COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur')
                ->oneArray();

            if ($row) {
                $data = array_merge($data, $row);
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('lampiran_konsultasi_internal/form.html', [
            'data' => $data,
            'dokter' => $dokter
        ]);
    }

    public function postKonsultasiinternalsave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'konsultasiinternalmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $status_konsul = $_POST['status_konsul'] ?? 'Menunggu Jawaban';
        if (!empty($_POST['jawaban_hasil_pemeriksaan']) || !empty($_POST['jawaban_nasihat'])) {
            $status_konsul = 'Sudah Dijawab';
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'ts_bagian' => $_POST['ts_bagian'] ?? '',
            'instalasi' => $_POST['instalasi'] ?? '',
            'diagnosa_sementara' => $_POST['diagnosa_sementara'] ?? '',
            'konsultasi_diminta' => $_POST['konsultasi_diminta'] ?? '',
            'tgl_konsul' => !empty($_POST['tgl_konsul']) ? $_POST['tgl_konsul'] : date('Y-m-d'),
            'kd_dokter_pengirim' => $_POST['kd_dokter_pengirim'] ?? null,
            'nama_dokter_pengirim' => $_POST['nama_dokter_pengirim'] ?? '',
            'jawaban_hasil_pemeriksaan' => $_POST['jawaban_hasil_pemeriksaan'] ?? '',
            'jawaban_diagnosa' => $_POST['jawaban_diagnosa'] ?? '',
            'jawaban_nasihat' => $_POST['jawaban_nasihat'] ?? '',
            'tgl_jawaban' => !empty($_POST['tgl_jawaban']) ? $_POST['tgl_jawaban'] : null,
            'kd_dokter_konsulen' => $_POST['kd_dokter_konsulen'] ?? null,
            'nama_dokter_konsulen' => $_POST['nama_dokter_konsulen'] ?? '',
            'status_konsul' => $status_konsul,
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_konsultasi_internal')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_konsultasi_internal')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Data Konsultasi Internal berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan data Konsultasi Internal.");
        }

        redirect(url([ADMIN, 'update_bmt', 'konsultasiinternalmanage']));
    }

    public function getKonsultasiinternalhapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_konsultasi_internal')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Data Konsultasi Internal berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus data Konsultasi Internal.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'konsultasiinternalmanage']));
    }

    public function getKonsultasiinternalcetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_konsultasi_internal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_konsultasi_internal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_konsultasi_internal.id', $id)
            ->select('lampiran_konsultasi_internal.*, COALESCE(lampiran_konsultasi_internal.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data Konsultasi Internal tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'konsultasiinternalmanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Tanggal Konsultasi
        if (!empty($data['tgl_konsul']) && $data['tgl_konsul'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_konsul']);
            if (count($tgl) == 3) {
                $data['tgl_konsul_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_konsul_indo'] = $data['tgl_konsul'];
            }
        } else {
            $data['tgl_konsul_indo'] = '';
        }

        // Tanggal Jawaban
        if (!empty($data['tgl_jawaban']) && $data['tgl_jawaban'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_jawaban']);
            if (count($tgl) == 3) {
                $data['tgl_jawaban_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_jawaban_indo'] = $data['tgl_jawaban'];
            }
        } else {
            $data['tgl_jawaban_indo'] = '';
        }

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_konsultasi_internal/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Konsultasi_Internal_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_konsultasi_internal/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - MONITORING PASIEN SELAMA TRANSFER (Ranap)
    // ============================================================

    public function anyMonitoringpasienselamatransfermanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $monitoring = $this->db('lampiran_monitoring_pasien_selama_transfer')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_selama_transfer.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_monitoring_pasien_selama_transfer.*, COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_monitoring_pasien_selama_transfer.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
            ->desc('lampiran_monitoring_pasien_selama_transfer.id')
            ->toArray();

        $no = 1;
        foreach ($monitoring as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';
        }

        return $this->draw('lampiran_monitoring_pasien_selama_transfer/manage.html', [
            'data' => $monitoring,
            'monitoring' => $monitoring
        ]);
    }

    public function getMonitoringpasienselamatransferform($id = null)
    {
        // AJAX: Cari data pasien via No. Rawat / No. RM
        if (isset($_GET['ajax_patient']) && $_GET['ajax_patient'] == '1') {
            header('Content-Type: application/json');
            $no_rawat = trim($_GET['no_rawat'] ?? '');

            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur, pasien.alamat')
                ->oneArray();

            if ($reg) {
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $reg['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);

                // Cari kamar inap bila ada
                $kamar = $this->db('kamar_inap')
                    ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                    ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                    ->where('kamar_inap.no_rawat', $reg['no_rawat'])
                    ->desc('kamar_inap.tgl_masuk')
                    ->select('bangsal.nm_bangsal, kamar.kd_kamar')
                    ->oneArray();

                $reg['kamar_inap'] = $kamar ? ($kamar['nm_bangsal'] . ' / ' . $kamar['kd_kamar']) : 'Rawat Inap';

                echo json_encode(['success' => true, 'data' => $reg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data pasien tidak ditemukan']);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'kamar_inap' => '',
            'tanggal_transfer' => date('Y-m-d'),
            'status_kegawatdaruratan' => 'Kuning',
            'jenis_kasus' => 'Non Trauma',
            'diagnosa' => '',
            'monitoring_keterangan' => '',
            'petugas_transfer' => '',
            'nip_petugas_transfer' => '',
            'jam_berangkat' => date('H:i'),
            'jam_tiba' => date('H:i', time() + 900),
            'detail_monitoring' => '[]',
            'catatan' => '',
            'nm_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'umur' => ''
        ];

        if ($id) {
            $row = $this->db('lampiran_monitoring_pasien_selama_transfer')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_selama_transfer.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_monitoring_pasien_selama_transfer.id', $id)
                ->select('lampiran_monitoring_pasien_selama_transfer.*, COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur')
                ->oneArray();

            if ($row) {
                $data = array_merge($data, $row);
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            }
        }

        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_monitoring_pasien_selama_transfer/form.html', [
            'data' => $data,
            'petugas' => $petugas
        ]);
    }

    public function postMonitoringpasienselamatransfersave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'monitoringpasienselamatransfermanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'tanggal_transfer' => !empty($_POST['tanggal_transfer']) ? $_POST['tanggal_transfer'] : date('Y-m-d'),
            'status_kegawatdaruratan' => $_POST['status_kegawatdaruratan'] ?? 'Kuning',
            'jenis_kasus' => $_POST['jenis_kasus'] ?? 'Non Trauma',
            'diagnosa' => $_POST['diagnosa'] ?? '',
            'monitoring_keterangan' => $_POST['monitoring_keterangan'] ?? '',
            'petugas_transfer' => $_POST['petugas_transfer'] ?? '',
            'nip_petugas_transfer' => $_POST['nip_petugas_transfer'] ?? '',
            'jam_berangkat' => !empty($_POST['jam_berangkat']) ? $_POST['jam_berangkat'] : null,
            'jam_tiba' => !empty($_POST['jam_tiba']) ? $_POST['jam_tiba'] : null,
            'detail_monitoring' => $_POST['detail_monitoring'] ?? '[]',
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_monitoring_pasien_selama_transfer')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_monitoring_pasien_selama_transfer')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Data Monitoring Pasien Selama Transfer berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan data monitoring transfer.");
        }

        redirect(url([ADMIN, 'update_bmt', 'monitoringpasienselamatransfermanage']));
    }

    public function getMonitoringpasienselamatransferhapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_monitoring_pasien_selama_transfer')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Data monitoring transfer berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus data monitoring transfer.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'monitoringpasienselamatransfermanage']));
    }

    public function getMonitoringpasienselamatransfercetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_monitoring_pasien_selama_transfer')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_monitoring_pasien_selama_transfer.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_monitoring_pasien_selama_transfer.id', $id)
            ->select('lampiran_monitoring_pasien_selama_transfer.*, COALESCE(lampiran_monitoring_pasien_selama_transfer.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data monitoring transfer tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'monitoringpasienselamatransfermanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Tanggal Transfer
        if (!empty($data['tanggal_transfer']) && $data['tanggal_transfer'] != '0000-00-00') {
            $tgl = explode('-', $data['tanggal_transfer']);
            if (count($tgl) == 3) {
                $data['tanggal_transfer_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tanggal_transfer_indo'] = $data['tanggal_transfer'];
            }
        } else {
            $data['tanggal_transfer_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
        }

        // Grid Rows
        $gridRows = !empty($data['detail_monitoring']) ? json_decode($data['detail_monitoring'], true) : [];
        if (!is_array($gridRows)) {
            $gridRows = [];
        }

        // Pad to at least 15 rows to visually match the physical form
        $totalRowsToDisplay = max(15, count($gridRows));
        for ($i = count($gridRows); $i < $totalRowsToDisplay; $i++) {
            $gridRows[] = [
                'jam_obs' => '&nbsp;',
                'gcs_pupil_rx' => '&nbsp;',
                'tensi' => '&nbsp;',
                'rr' => '&nbsp;',
                'spo2' => '&nbsp;',
                'temp' => '&nbsp;',
                'jam_tind' => '&nbsp;',
                'hal_penting_tindakan' => '&nbsp;',
                'jam_terapi' => '&nbsp;',
                'terapi_obat_cairan' => '&nbsp;'
            ];
        }
        $data['grid_rows'] = $gridRows;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_monitoring_pasien_selama_transfer/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Monitoring_Transfer_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_monitoring_pasien_selama_transfer/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PENILAIAN DEKUBITUS (DECUBITUS RISK ASSESSMENT)
    // ============================================================

    public function anyPenilaiandekubitusmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $records = $this->db('lampiran_penilaian_dekubitus')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_dekubitus.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_penilaian_dekubitus.*, COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_penilaian_dekubitus.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
            ->desc('lampiran_penilaian_dekubitus.id')
            ->toArray();

        $no = 1;
        foreach ($records as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';

            // Parse detail_penilaian to get summary stats
            $detail = !empty($item['detail_penilaian']) ? json_decode($item['detail_penilaian'], true) : [];
            $hariTerisi = 0;
            $skorTerakhir = null;
            $petugasTerakhir = '-';
            if (is_array($detail)) {
                for ($d = 1; $d <= 9; $d++) {
                    if (isset($detail[$d]) && is_array($detail[$d])) {
                        $rowDay = $detail[$d];
                        if (!empty($rowDay['total_skor'])) {
                            $hariTerisi++;
                            $skorTerakhir = $rowDay['total_skor'];
                            if (!empty($rowDay['perawat'])) {
                                $petugasTerakhir = $rowDay['perawat'];
                            }
                        }
                    }
                }
            }
            $item['hari_terisi'] = $hariTerisi;
            $item['skor_terakhir'] = $skorTerakhir;
            $item['petugas_terakhir'] = $petugasTerakhir;
        }

        return $this->draw('lampiran_penilaian_dekubitus/manage.html', [
            'data' => $records
        ]);
    }

    public function getPenilaiandekubitusform($id = null)
    {
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '-',
            'umur' => '-',
            'tgl_lahir' => '-',
            'kamar_inap' => '',
            'tanggal_penilaian' => date('Y-m-d'),
            'detail_penilaian_raw' => '{}',
            'catatan' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_penilaian_dekubitus')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_dekubitus.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_penilaian_dekubitus.id', $id)
                ->select('lampiran_penilaian_dekubitus.*, COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
                ->oneArray();

            if ($row) {
                $data = $row;
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['umur'] = !empty($data['umur']) ? $data['umur'] : ($data['umur_pasien'] ?? '-');
                $data['jk'] = !empty($data['jk']) ? $data['jk'] : '-';
                $data['detail_penilaian_raw'] = $data['detail_penilaian'] ?? '{}';
            }
        } elseif (isset($_GET['no_rawat'])) {
            $no_rawat = trim($_GET['no_rawat']);
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, bangsal.nm_bangsal, kamar.kd_kamar')
                ->oneArray();

            if ($reg) {
                $data['no_rawat'] = $reg['no_rawat'];
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['nm_pasien'] = $reg['nm_pasien'] ?? '';
                $data['jk'] = $reg['jk'] ?? '-';
                $data['umur'] = $reg['umur'] ?? '-';
                $data['tgl_lahir'] = $reg['tgl_lahir'] ?? '-';
                if (!empty($reg['nm_bangsal'])) {
                    $data['kamar_inap'] = $reg['nm_bangsal'] . ' / ' . $reg['kd_kamar'];
                }
            }
        }

        $petugas = $this->db('petugas')->select('nip, nama')->toArray();
        $daysRange = range(1, 9);

        return $this->draw('lampiran_penilaian_dekubitus/form.html', [
            'data' => $data,
            'petugas' => $petugas,
            'days_range' => $daysRange
        ]);
    }

    public function postPenilaiandekubitussave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'penilaiandekubitusmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'tanggal_penilaian' => !empty($_POST['tanggal_penilaian']) ? $_POST['tanggal_penilaian'] : date('Y-m-d'),
            'detail_penilaian' => $_POST['detail_penilaian'] ?? '{}',
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_penilaian_dekubitus')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_penilaian_dekubitus')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Data Penilaian Dekubitus berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan data penilaian dekubitus.");
        }

        redirect(url([ADMIN, 'update_bmt', 'penilaiandekubitusmanage']));
    }

    public function getPenilaiandekubitushapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_penilaian_dekubitus')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Data penilaian dekubitus berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus data penilaian dekubitus.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'penilaiandekubitusmanage']));
    }

    public function getPenilaiandekubituscetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_penilaian_dekubitus')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_dekubitus.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_penilaian_dekubitus.id', $id)
            ->select('lampiran_penilaian_dekubitus.*, COALESCE(lampiran_penilaian_dekubitus.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data penilaian dekubitus tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'penilaiandekubitusmanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Parse detail_penilaian JSON into days array 1..9
        $detail = !empty($data['detail_penilaian']) ? json_decode($data['detail_penilaian'], true) : [];
        $days = [];
        for ($d = 1; $d <= 9; $d++) {
            $item = $detail[$d] ?? [];
            $tglLabel = '';
            if (!empty($item['tgl'])) {
                $p = explode('-', $item['tgl']);
                if (count($p) == 3) {
                    $tglLabel = $p[2] . '/' . $p[1];
                } else {
                    $tglLabel = $item['tgl'];
                }
            }

            $days[] = [
                'day' => $d,
                'tgl_label' => $tglLabel,
                'kondisi_fisik' => isset($item['kondisi_fisik']) && $item['kondisi_fisik'] !== null && $item['kondisi_fisik'] !== '' ? $item['kondisi_fisik'] : null,
                'kesadaran' => isset($item['kesadaran']) && $item['kesadaran'] !== null && $item['kesadaran'] !== '' ? $item['kesadaran'] : null,
                'aktivitas' => isset($item['aktivitas']) && $item['aktivitas'] !== null && $item['aktivitas'] !== '' ? $item['aktivitas'] : null,
                'mobilitas' => isset($item['mobilitas']) && $item['mobilitas'] !== null && $item['mobilitas'] !== '' ? $item['mobilitas'] : null,
                'inkontinensia' => isset($item['inkontinensia']) && $item['inkontinensia'] !== null && $item['inkontinensia'] !== '' ? $item['inkontinensia'] : null,
                'total_skor' => isset($item['total_skor']) && $item['total_skor'] !== null && $item['total_skor'] !== '' ? $item['total_skor'] : null,
                'perawat' => !empty($item['perawat']) ? $item['perawat'] : ''
            ];
        }
        $data['days'] = $days;

        // Cek jika request export mPDF
        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $html = $this->draw('lampiran_penilaian_dekubitus/cetak.html', ['data' => $data, 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Penilaian_Dekubitus_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penilaian_dekubitus/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PENILAIAN KEJADIAN INFEKSI LUKA OPERASI (ILO)
    // ============================================================

    public function anyPenilaianilomanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $records = $this->db('lampiran_penilaian_kejadian_ilo')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_ilo.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_penilaian_kejadian_ilo.*, COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, COALESCE(pasien.nm_pasien, pasien.nm_pasien as nama_pasien, lampiran_penilaian_kejadian_ilo.no_rawat) as nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
            ->desc('lampiran_penilaian_kejadian_ilo.id')
            ->toArray();

        $no = 1;
        foreach ($records as &$item) {
            $item['nomor'] = $no++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';

            // Parse detail_penilaian to get summary stats
            $detail = !empty($item['detail_penilaian']) ? json_decode($item['detail_penilaian'], true) : [];
            $hariTerisi = 0;
            $skorTerakhir = null;
            $kategoriTerakhir = '-';
            $petugasTerakhir = '-';
            if (is_array($detail)) {
                for ($d = 1; $d <= 10; $d++) {
                    if (isset($detail[$d]) && is_array($detail[$d])) {
                        $rowDay = $detail[$d];
                        if (isset($rowDay['total_skor']) && $rowDay['total_skor'] !== '' && $rowDay['total_skor'] !== null) {
                            $hariTerisi++;
                            $skorTerakhir = (int)$rowDay['total_skor'];
                            if (!empty($rowDay['petugas'])) {
                                $petugasTerakhir = $rowDay['petugas'];
                            }
                            if ($skorTerakhir == 0) {
                                $kategoriTerakhir = 'Tidak Ada Tanda Infeksi';
                            } elseif ($skorTerakhir >= 1 && $skorTerakhir <= 2) {
                                $kategoriTerakhir = 'Tahap Awal Infeksi';
                            } elseif ($skorTerakhir >= 3 && $skorTerakhir <= 4) {
                                $kategoriTerakhir = 'Awal Infeksi';
                            } else {
                                $kategoriTerakhir = 'Stadium Lanjut Infeksi';
                            }
                        }
                    }
                }
            }
            $item['hari_terisi'] = $hariTerisi;
            $item['skor_terakhir'] = $skorTerakhir;
            $item['kategori_terakhir'] = $kategoriTerakhir;
            $item['petugas_terakhir'] = $petugasTerakhir;
        }

        return $this->draw('lampiran_penilaian_kejadian_ilo/manage.html', [
            'data' => $records
        ]);
    }

    public function getPenilaianiloform($id = null)
    {
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '-',
            'umur' => '-',
            'tgl_lahir' => '-',
            'kamar_inap' => '',
            'tanggal_penilaian' => date('Y-m-d'),
            'detail_penilaian_raw' => '{}',
            'catatan' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_penilaian_kejadian_ilo')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_ilo.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_penilaian_kejadian_ilo.id', $id)
                ->select('lampiran_penilaian_kejadian_ilo.*, COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
                ->oneArray();

            if ($row) {
                $data = $row;
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['umur'] = !empty($data['umur']) ? $data['umur'] : ($data['umur_pasien'] ?? '-');
                $data['jk'] = !empty($data['jk']) ? $data['jk'] : '-';
                $data['detail_penilaian_raw'] = $data['detail_penilaian'] ?? '{}';
            }
        } elseif (isset($_GET['no_rawat'])) {
            $no_rawat = trim($_GET['no_rawat']);
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, bangsal.nm_bangsal, kamar.kd_kamar')
                ->oneArray();

            if ($reg) {
                $data['no_rawat'] = $reg['no_rawat'];
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['nm_pasien'] = $reg['nm_pasien'] ?? '';
                $data['jk'] = $reg['jk'] ?? '-';
                $data['umur'] = $reg['umur'] ?? '-';
                $data['tgl_lahir'] = $reg['tgl_lahir'] ?? '-';
                if (!empty($reg['nm_bangsal'])) {
                    $data['kamar_inap'] = $reg['nm_bangsal'] . ' / ' . $reg['kd_kamar'];
                }
            }
        }

        $petugas = $this->db('petugas')->select('nip, nama')->toArray();
        $daysRange = range(1, 10);

        return $this->draw('lampiran_penilaian_kejadian_ilo/form.html', [
            'data' => $data,
            'petugas' => $petugas,
            'days_range' => $daysRange
        ]);
    }

    public function postPenilaianilosave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'penilaianilomanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'tanggal_penilaian' => !empty($_POST['tanggal_penilaian']) ? $_POST['tanggal_penilaian'] : date('Y-m-d'),
            'detail_penilaian' => $_POST['detail_penilaian'] ?? '{}',
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_penilaian_kejadian_ilo')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_penilaian_kejadian_ilo')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Data Penilaian Kejadian ILO berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan data penilaian kejadian ILO.");
        }

        redirect(url([ADMIN, 'update_bmt', 'penilaianilomanage']));
    }

    public function getPenilaianilohapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_penilaian_kejadian_ilo')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Data penilaian kejadian ILO berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus data penilaian kejadian ILO.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'penilaianilomanage']));
    }

    public function getPenilaianilocetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_penilaian_kejadian_ilo')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_ilo.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_penilaian_kejadian_ilo.id', $id)
            ->select('lampiran_penilaian_kejadian_ilo.*, COALESCE(lampiran_penilaian_kejadian_ilo.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data penilaian kejadian ILO tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'penilaianilomanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Parse detail_penilaian JSON into days array 1..10
        $detail = !empty($data['detail_penilaian']) ? json_decode($data['detail_penilaian'], true) : [];
        $days = [];
        for ($d = 1; $d <= 10; $d++) {
            $item = $detail[$d] ?? [];
            $tglLabel = '';
            if (!empty($item['tgl'])) {
                $p = explode('-', $item['tgl']);
                if (count($p) == 3) {
                    $tglLabel = $p[2] . '/' . $p[1];
                } else {
                    $tglLabel = $item['tgl'];
                }
            }

            $param = isset($item['parameter']) && $item['parameter'] !== '' && $item['parameter'] !== null ? (int)$item['parameter'] : null;
            $totalSkor = isset($item['total_skor']) && $item['total_skor'] !== '' && $item['total_skor'] !== null ? (int)$item['total_skor'] : null;
            
            $days[] = [
                'day' => $d,
                'tgl_label' => $tglLabel,
                'parameter' => $param,
                'p1_checked' => ($param === 0 || (isset($item['p1']) && $item['p1'] === '0')),
                'p2_checked' => ($param === 1 || (!empty($item['p2']) && $item['p2'] == '1')),
                'p3_checked' => ($param === 2 || (!empty($item['p3']) && $item['p3'] == '2')),
                'p4_checked' => ($param === 3 || (!empty($item['p4']) && $item['p4'] == '3')),
                'p5_checked' => ($param === 4 || (!empty($item['p5']) && $item['p5'] == '4')),
                'p6_checked' => ($param === 5 || (!empty($item['p6']) && $item['p6'] == '5')),
                'total_skor' => $totalSkor,
                'petugas' => $item['petugas'] ?? ''
            ];
        }
        $data['days'] = $days;

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_penilaian_kejadian_ilo/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 8,
                    'margin_bottom' => 8,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('Penilaian_ILO_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penilaian_kejadian_ilo/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PENILAIAN KEJADIAN INFEKSI SALURAN KEMIH (ISK)
    // ============================================================

    public function anyPenilaianiskmanage()
    {
        $query = $this->db('lampiran_penilaian_kejadian_isk')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_isk.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
            ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
            ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal');

        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $s = '%' . trim($_GET['s']) . '%';
            $query->where('lampiran_penilaian_kejadian_isk.no_rawat', 'LIKE', $s)
                ->orWhere('lampiran_penilaian_kejadian_isk.no_rkm_medis', 'LIKE', $s)
                ->orWhere('pasien.nm_pasien', 'LIKE', $s);
        }

        $records = $query->select('lampiran_penilaian_kejadian_isk.*, 
            COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, 
            pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur as umur_pasien, pasien.umur,
            bangsal.nm_bangsal, kamar.kd_kamar')
            ->desc('lampiran_penilaian_kejadian_isk.id')
            ->toArray();

        $nomor = 1;
        foreach ($records as &$item) {
            $item['nomor'] = $nomor++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            $item['umur'] = !empty($item['umur']) ? $item['umur'] : ($item['umur_pasien'] ?? '-');
            $item['jk'] = !empty($item['jk']) ? $item['jk'] : '-';

            if (empty($item['kamar_inap']) && !empty($item['nm_bangsal'])) {
                $item['kamar_inap'] = $item['nm_bangsal'] . ' / ' . ($item['kd_kamar'] ?? '-');
            }

            // Hitung hari terisi dan skor terakhir
            $detail = !empty($item['detail_penilaian']) ? json_decode($item['detail_penilaian'], true) : [];
            $hariTerisi = 0;
            $skorTerakhir = null;
            $kategoriTerakhir = '-';
            $petugasTerakhir = '';

            if (is_array($detail)) {
                for ($d = 1; $d <= 9; $d++) {
                    if (!empty($detail[$d])) {
                        $dData = $detail[$d];
                        $hasData = !empty($dData['tgl']) || !empty($dData['param_1']) || !empty($dData['param_2']) || !empty($dData['param_3']) || !empty($dData['param_4']) || (isset($dData['total_skor']) && $dData['total_skor'] !== null && $dData['total_skor'] !== '');
                        if ($hasData) {
                            $hariTerisi++;
                            if (isset($dData['total_skor']) && $dData['total_skor'] !== null && $dData['total_skor'] !== '') {
                                $skorTerakhir = (int)$dData['total_skor'];
                            }
                            if (!empty($dData['petugas'])) {
                                $petugasTerakhir = $dData['petugas'];
                            }
                        }
                    }
                }
                if ($skorTerakhir !== null) {
                    if ($skorTerakhir == 0) {
                        $kategoriTerakhir = 'Tanpa Tanda ISK';
                    } elseif ($skorTerakhir == 1) {
                        $kategoriTerakhir = 'Risiko Ringan ISK';
                    } elseif ($skorTerakhir == 2) {
                        $kategoriTerakhir = 'Terindikasi Pediatrik';
                    } else {
                        $kategoriTerakhir = 'Risiko Tinggi ISK';
                    }
                }
            }
            $item['hari_terisi'] = $hariTerisi;
            $item['skor_terakhir'] = $skorTerakhir;
            $item['kategori_terakhir'] = $kategoriTerakhir;
            $item['petugas_terakhir'] = $petugasTerakhir;
        }

        return $this->draw('lampiran_penilaian_kejadian_isk/manage.html', [
            'data' => $records
        ]);
    }

    public function getPenilaianiskform($id = null)
    {
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'jk' => '-',
            'umur' => '-',
            'tgl_lahir' => '-',
            'kamar_inap' => '',
            'tanggal_penilaian' => date('Y-m-d'),
            'detail_penilaian_raw' => '{}',
            'catatan' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_penilaian_kejadian_isk')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_isk.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('lampiran_penilaian_kejadian_isk.id', $id)
                ->select('lampiran_penilaian_kejadian_isk.*, COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.umur')
                ->oneArray();

            if ($row) {
                $data = $row;
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['umur'] = !empty($data['umur']) ? $data['umur'] : ($data['umur_pasien'] ?? '-');
                $data['jk'] = !empty($data['jk']) ? $data['jk'] : '-';
                $data['detail_penilaian_raw'] = $data['detail_penilaian'] ?? '{}';
            }
        } elseif (isset($_GET['no_rawat'])) {
            $no_rawat = trim($_GET['no_rawat']);
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')
                ->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')
                ->leftJoin('bangsal', 'bangsal.kd_bangsal = kamar.kd_bangsal')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, bangsal.nm_bangsal, kamar.kd_kamar')
                ->oneArray();

            if ($reg) {
                $data['no_rawat'] = $reg['no_rawat'];
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['nm_pasien'] = $reg['nm_pasien'] ?? '';
                $data['jk'] = $reg['jk'] ?? '-';
                $data['umur'] = $reg['umur'] ?? '-';
                $data['tgl_lahir'] = $reg['tgl_lahir'] ?? '-';
                if (!empty($reg['nm_bangsal'])) {
                    $data['kamar_inap'] = $reg['nm_bangsal'] . ' / ' . $reg['kd_kamar'];
                }
            }
        }

        $petugas = $this->db('petugas')->select('nip, nama')->toArray();
        $daysRange = range(1, 9);

        return $this->draw('lampiran_penilaian_kejadian_isk/form.html', [
            'data' => $data,
            'petugas' => $petugas,
            'days_range' => $daysRange
        ]);
    }

    public function postPenilaianisksave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'penilaianiskmanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'kamar_inap' => $_POST['kamar_inap'] ?? '',
            'tanggal_penilaian' => !empty($_POST['tanggal_penilaian']) ? $_POST['tanggal_penilaian'] : date('Y-m-d'),
            'detail_penilaian' => $_POST['detail_penilaian'] ?? '{}',
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_penilaian_kejadian_isk')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_penilaian_kejadian_isk')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Data Penilaian Kejadian ISK berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan data penilaian kejadian ISK.");
        }

        redirect(url([ADMIN, 'update_bmt', 'penilaianiskmanage']));
    }

    public function getPenilaianiskhapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_penilaian_kejadian_isk')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Data penilaian kejadian ISK berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus data penilaian kejadian ISK.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'penilaianiskmanage']));
    }

    public function getPenilaianiskcetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_penilaian_kejadian_isk')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_penilaian_kejadian_isk.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_penilaian_kejadian_isk.id', $id)
            ->select('lampiran_penilaian_kejadian_isk.*, COALESCE(lampiran_penilaian_kejadian_isk.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.umur as umur_pasien, pasien.alamat')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data penilaian kejadian ISK tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'penilaianiskmanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        // Parse detail_penilaian JSON into days array 1..9
        $detail = !empty($data['detail_penilaian']) ? json_decode($data['detail_penilaian'], true) : [];
        $days = [];
        for ($d = 1; $d <= 9; $d++) {
            $item = $detail[$d] ?? [];
            $tglLabel = '';
            if (!empty($item['tgl'])) {
                $p = explode('-', $item['tgl']);
                if (count($p) == 3) {
                    $tglLabel = $p[2] . '/' . $p[1];
                } else {
                    $tglLabel = $item['tgl'];
                }
            }

            $totalSkor = isset($item['total_skor']) && $item['total_skor'] !== '' && $item['total_skor'] !== null ? (int)$item['total_skor'] : null;
            
            $days[] = [
                'day' => $d,
                'tgl_label' => $tglLabel,
                'p1_checked' => (!empty($item['param_1']) || !empty($item['p1'])),
                'p2_checked' => (!empty($item['param_2']) || !empty($item['p2'])),
                'p3_checked' => (!empty($item['param_3']) || !empty($item['p3'])),
                'p4_checked' => (!empty($item['param_4']) || !empty($item['p4'])),
                'total_skor' => $totalSkor,
                'petugas' => $item['petugas'] ?? ''
            ];
        }
        $data['days'] = $days;

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_penilaian_kejadian_isk/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 8,
                    'margin_right' => 8,
                    'margin_top' => 6,
                    'margin_bottom' => 6,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('Penilaian_ISK_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penilaian_kejadian_isk/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU
    // ============================================================

    public function anyBersediamenunggumanage()
    {
        $query = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_surat_pernyataan_bersedia_menunggu.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_surat_pernyataan_bersedia_menunggu.no_rkm_medis, reg_periksa.no_rkm_medis)');

        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $s = '%' . trim($_GET['s']) . '%';
            $query->where('lampiran_surat_pernyataan_bersedia_menunggu.no_rawat', 'LIKE', $s)
                ->orWhere('lampiran_surat_pernyataan_bersedia_menunggu.no_rkm_medis', 'LIKE', $s)
                ->orWhere('lampiran_surat_pernyataan_bersedia_menunggu.nama_pasien', 'LIKE', $s)
                ->orWhere('lampiran_surat_pernyataan_bersedia_menunggu.nama_pj', 'LIKE', $s);
        }

        $records = $query->select('lampiran_surat_pernyataan_bersedia_menunggu.*, 
            COALESCE(lampiran_surat_pernyataan_bersedia_menunggu.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, 
            COALESCE(lampiran_surat_pernyataan_bersedia_menunggu.nama_pasien, pasien.nm_pasien) as nama_pasien')
            ->desc('lampiran_surat_pernyataan_bersedia_menunggu.id')
            ->toArray();

        $nomor = 1;
        foreach ($records as &$item) {
            $item['nomor'] = $nomor++;
            $rmRaw = ltrim($item['no_rkm_medis'] ?? '', "'");
            $item['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        }

        return $this->draw('lampiran_surat_pernyataan_bersedia_menunggu/manage.html', [
            'data' => $records
        ]);
    }

    public function getBersediamenungguform($id = null)
    {
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nama_pj' => '',
            'tgl_lahir_pj' => '',
            'jk_pj' => 'Laki-laki',
            'alamat_pj' => '',
            'no_identitas_pj' => '',
            'hubungan' => 'Diri Saya Sendiri',
            'nama_pasien' => '',
            'tgl_lahir_pasien' => '',
            'jk_pasien' => 'Laki-laki',
            'alamat_pasien' => '',
            'ruang_menunggu' => 'ruang perawatan biasa / ruang perawatan intensif',
            'tanggal_surat' => date('Y-m-d'),
            'kd_dokter' => '',
            'nama_dokter' => '',
            'catatan' => ''
        ];

        if (!empty($id)) {
            $row = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')
                ->where('id', $id)
                ->oneArray();

            if ($row) {
                $data = $row;
                $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
            }
        } elseif (isset($_GET['no_rawat'])) {
            $no_rawat = trim($_GET['no_rawat']);
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat, pasien.no_ktp, dokter.nm_dokter')
                ->oneArray();

            if ($reg) {
                $data['no_rawat'] = $reg['no_rawat'];
                $rmRaw = ltrim($reg['no_rkm_medis'] ?? '', "'");
                $data['no_rkm_medis'] = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
                $data['nama_pasien'] = $reg['nm_pasien'] ?? '';
                $data['tgl_lahir_pasien'] = $reg['tgl_lahir'] ?? '';
                $data['jk_pasien'] = ($reg['jk'] == 'P' || $reg['jk'] == 'Perempuan') ? 'Perempuan' : 'Laki-laki';
                $data['alamat_pasien'] = $reg['alamat'] ?? '';
                $data['kd_dokter'] = $reg['kd_dokter'] ?? '';
                $data['nama_dokter'] = $reg['nm_dokter'] ?? '';

                if (!empty($reg['p_jawab'])) {
                    $data['nama_pj'] = $reg['p_jawab'];
                    $data['alamat_pj'] = $reg['almt_pj'] ?? ($reg['alamat'] ?? '');
                    $data['hubungan'] = !empty($reg['hubunganpj']) ? $reg['hubunganpj'] : 'Diri Saya Sendiri';
                } else {
                    $data['nama_pj'] = $reg['nm_pasien'] ?? '';
                    $data['tgl_lahir_pj'] = $reg['tgl_lahir'] ?? '';
                    $data['jk_pj'] = $data['jk_pasien'];
                    $data['alamat_pj'] = $reg['alamat'] ?? '';
                    $data['no_identitas_pj'] = $reg['no_ktp'] ?? '';
                    $data['hubungan'] = 'Diri Saya Sendiri';
                }
            }
        }

        $dokter = $this->db('dokter')->select('kd_dokter, nm_dokter')->toArray();

        return $this->draw('lampiran_surat_pernyataan_bersedia_menunggu/form.html', [
            'data' => $data,
            'dokter' => $dokter
        ]);
    }

    public function postBersediamenunggusave()
    {
        $id = $_POST['id'] ?? '';
        $no_rawat = trim($_POST['no_rawat'] ?? '');

        if (empty($no_rawat)) {
            $this->setNotify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'bersediamenunggumanage']));
        }

        $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $no_rkm_medis = $reg['no_rkm_medis'] ?? ($_POST['no_rkm_medis'] ?? '');

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'nama_pj' => $_POST['nama_pj'] ?? '',
            'tgl_lahir_pj' => !empty($_POST['tgl_lahir_pj']) ? $_POST['tgl_lahir_pj'] : null,
            'jk_pj' => $_POST['jk_pj'] ?? 'Laki-laki',
            'alamat_pj' => $_POST['alamat_pj'] ?? '',
            'no_identitas_pj' => $_POST['no_identitas_pj'] ?? '',
            'hubungan' => $_POST['hubungan'] ?? 'Diri Saya Sendiri',
            'nama_pasien' => $_POST['nama_pasien'] ?? '',
            'tgl_lahir_pasien' => !empty($_POST['tgl_lahir_pasien']) ? $_POST['tgl_lahir_pasien'] : null,
            'jk_pasien' => $_POST['jk_pasien'] ?? 'Laki-laki',
            'alamat_pasien' => $_POST['alamat_pasien'] ?? '',
            'ruang_menunggu' => $_POST['ruang_menunggu'] ?? 'ruang perawatan biasa / ruang perawatan intensif',
            'tanggal_surat' => !empty($_POST['tanggal_surat']) ? $_POST['tanggal_surat'] : date('Y-m-d'),
            'kd_dokter' => $_POST['kd_dokter'] ?? '',
            'nama_dokter' => $_POST['nama_dokter'] ?? '',
            'catatan' => $_POST['catatan'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($id)) {
            $saveData['id'] = $id;
            $result = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')->where('id', $id)->save($saveData);
            $msg = 'diperbarui';
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $result = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')->save($saveData);
            $msg = 'disimpan';
        }

        if ($result) {
            $this->setNotify('success', "Surat Pernyataan Bersedia Menunggu berhasil $msg.");
        } else {
            $this->setNotify('failure', "Gagal menyimpan surat pernyataan bersedia menunggu.");
        }

        redirect(url([ADMIN, 'update_bmt', 'bersediamenunggumanage']));
    }

    public function getBersediamenungguhapus($id)
    {
        if (!empty($id)) {
            $result = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')->where('id', $id)->delete();
            if ($result) {
                $this->setNotify('success', 'Surat pernyataan bersedia menunggu berhasil dihapus.');
            } else {
                $this->setNotify('failure', 'Gagal menghapus surat pernyataan bersedia menunggu.');
            }
        }
        redirect(url([ADMIN, 'update_bmt', 'bersediamenunggumanage']));
    }

    public function getBersediamenunggucetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $data = $this->db('lampiran_surat_pernyataan_bersedia_menunggu')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_surat_pernyataan_bersedia_menunggu.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_surat_pernyataan_bersedia_menunggu.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_surat_pernyataan_bersedia_menunggu.id', $id)
            ->select('lampiran_surat_pernyataan_bersedia_menunggu.*, COALESCE(lampiran_surat_pernyataan_bersedia_menunggu.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data surat pernyataan bersedia menunggu tidak ditemukan!');
            redirect(url([ADMIN, 'update_bmt', 'bersediamenunggumanage']));
        }

        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        // Format tanggal surat
        if (!empty($data['tanggal_surat'])) {
            $ts = strtotime($data['tanggal_surat']);
            $data['tanggal_surat_indo'] = date('d', $ts) . ' ' . ($bulanIndo[(int)date('m', $ts)] ?? date('m', $ts)) . ' ' . date('Y', $ts);
        } else {
            $data['tanggal_surat_indo'] = date('d') . ' ' . ($bulanIndo[(int)date('m')] ?? '') . ' ' . date('Y');
        }

        // Format tgl lahir PJ
        if (!empty($data['tgl_lahir_pj']) && $data['tgl_lahir_pj'] != '0000-00-00') {
            $tp = strtotime($data['tgl_lahir_pj']);
            $data['tgl_lahir_pj_indo'] = date('d', $tp) . ' ' . ($bulanIndo[(int)date('m', $tp)] ?? date('m', $tp)) . ' ' . date('Y', $tp);
        } else {
            $data['tgl_lahir_pj_indo'] = '-';
        }

        // Format tgl lahir Pasien
        if (!empty($data['tgl_lahir_pasien']) && $data['tgl_lahir_pasien'] != '0000-00-00') {
            $tpas = strtotime($data['tgl_lahir_pasien']);
            $data['tgl_lahir_pasien_indo'] = date('d', $tpas) . ' ' . ($bulanIndo[(int)date('m', $tpas)] ?? date('m', $tpas)) . ' ' . date('Y', $tpas);
        } else {
            $data['tgl_lahir_pasien_indo'] = '-';
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_surat_pernyataan_bersedia_menunggu/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 12,
                    'margin_right' => 12,
                    'margin_top' => 10,
                    'margin_bottom' => 10,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('Surat_Pernyataan_Bersedia_Menunggu_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_surat_pernyataan_bersedia_menunggu/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN
    // ============================================================

    public function anyPenundaankelambatanmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = "SELECT p.*, r.no_rkm_medis, pas.nm_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d1.nm_dokter as nama_dokter_pengirim_db,
                         d2.nm_dokter as nama_dokter_dpjp_db
                  FROM lampiran_penundaan_kelambatan_pelayanan p
                  LEFT JOIN reg_periksa r ON r.no_rawat = p.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d1 ON d1.kd_dokter = p.kd_dokter_pengirim
                  LEFT JOIN dokter d2 ON d2.kd_dokter = p.kd_dokter_dpjp
                  ORDER BY p.id DESC";

        $stmt = $this->db()->pdo()->query($query);
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        foreach ($rows as $k => $v) {
            $rows[$k]['nomor'] = $k + 1;
            if (!empty($v['nama_dokter_pengirim_db'])) {
                $rows[$k]['nama_dokter_pengirim'] = $v['nama_dokter_pengirim_db'];
            }
            if (!empty($v['nama_dokter_dpjp_db'])) {
                $rows[$k]['nama_dokter_dpjp'] = $v['nama_dokter_dpjp_db'];
            }
        }

        return $this->draw('lampiran_penundaan_kelambatan_pelayanan/manage.html', ['data' => $rows, 'rows' => $rows]);
    }

    public function getPenundaankelambatanform($id = null)
    {
        $data = [];
        if ($id) {
            $data = $this->db('lampiran_penundaan_kelambatan_pelayanan')
                               ->where('id', $id)
                               ->oneArray();
            if ($data) {
                $pasien = $this->db('reg_periksa')
                                     ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                                     ->where('reg_periksa.no_rawat', $data['no_rawat'])
                                     ->select(['pasien.nm_pasien', 'pasien.tgl_lahir', 'pasien.jk', 'pasien.alamat', 'pasien.no_ktp', 'reg_periksa.no_rkm_medis'])
                                     ->oneArray();
                if ($pasien) {
                    $data['nm_pasien'] = $pasien['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $pasien['tgl_lahir'];
                    $data['jk_pasien'] = $pasien['jk'];
                    $data['alamat_pasien'] = $pasien['alamat'];
                    $data['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    $data['no_ktp_pasien'] = $pasien['no_ktp'];
                }
            }
        } else {
            $data = [
                'id' => '',
                'no_rawat' => $_GET['no_rawat'] ?? '',
                'no_rkm_medis' => '',
                'tanggal' => date('Y-m-d'),
                'jam' => date('H:i:s'),
                'instalasi_unit' => 'Instalasi Bedah Sentral',
                'kd_dokter_pengirim' => '',
                'nama_dokter_pengirim' => '',
                'jenis_pelayanan' => '',
                'status_penerima' => 'Pasien Sendiri',
                'nama_penerima' => '',
                'alamat_penerima' => '',
                'no_identitas_penerima' => '',
                'sebab_kerusakan_alat' => 0,
                'sebab_kondisi_umum_pasien' => 0,
                'sebab_penundaan_penjadwalan' => 0,
                'sebab_pemadaman_listrik' => 0,
                'sebab_tempat_tidur_penuh' => 0,
                'sebab_faktor_penyulit' => 0,
                'keterangan_faktor_penyulit' => '',
                'solusi_dijadwalkan_ulang' => 1,
                'jadwal_akan_datang' => '',
                'solusi_dirujuk' => 0,
                'tujuan_rujukan' => '',
                'solusi_kembali_dokter_pengirim' => 0,
                'kd_dokter_dpjp' => '',
                'nama_dokter_dpjp' => '',
                'kd_pemberi_informasi' => '',
                'nama_pemberi_informasi' => '',
                'catatan' => '',
                'nm_pasien' => '',
                'tgl_lahir_pasien' => '',
                'jk_pasien' => '',
                'alamat_pasien' => '',
                'no_ktp_pasien' => ''
            ];

            if (!empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                                  ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                                  ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                                  ->where('reg_periksa.no_rawat', $data['no_rawat'])
                                  ->select([
                                      'reg_periksa.no_rawat',
                                      'reg_periksa.no_rkm_medis',
                                      'reg_periksa.kd_dokter',
                                      'dokter.nm_dokter',
                                      'reg_periksa.p_jawab',
                                      'reg_periksa.almt_pj',
                                      'reg_periksa.hubunganpj',
                                      'pasien.nm_pasien',
                                      'pasien.tgl_lahir',
                                      'pasien.jk',
                                      'pasien.alamat',
                                      'pasien.no_ktp'
                                  ])
                                  ->oneArray();

                if ($reg) {
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nm_pasien'] = $reg['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $reg['tgl_lahir'];
                    $data['jk_pasien'] = $reg['jk'];
                    $data['alamat_pasien'] = $reg['alamat'];
                    $data['no_ktp_pasien'] = $reg['no_ktp'];
                    $data['kd_dokter_dpjp'] = $reg['kd_dokter'];
                    $data['nama_dokter_dpjp'] = $reg['nm_dokter'];
                    $data['kd_dokter_pengirim'] = $reg['kd_dokter'];
                    $data['nama_dokter_pengirim'] = $reg['nm_dokter'];
                    $data['nama_penerima'] = $reg['nm_pasien'];
                    $data['alamat_penerima'] = $reg['alamat'];
                    $data['no_identitas_penerima'] = $reg['no_ktp'];
                }
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();
        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_penundaan_kelambatan_pelayanan/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'petugas' => $petugas
        ]);
    }

    public function postPenundaankelambatansave()
    {
        $id = $_POST['id'] ?? null;
        $no_rawat = $_POST['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            $this->notify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'penundaankelambatanform']));
        }

        $nama_dokter_pengirim = $_POST['nama_dokter_pengirim'] ?? '';
        if (!empty($_POST['kd_dokter_pengirim'])) {
            $doc1 = $this->db('dokter')->where('kd_dokter', $_POST['kd_dokter_pengirim'])->oneArray();
            if ($doc1) $nama_dokter_pengirim = $doc1['nm_dokter'];
        }

        $nama_dokter_dpjp = $_POST['nama_dokter_dpjp'] ?? '';
        if (!empty($_POST['kd_dokter_dpjp'])) {
            $doc2 = $this->db('dokter')->where('kd_dokter', $_POST['kd_dokter_dpjp'])->oneArray();
            if ($doc2) $nama_dokter_dpjp = $doc2['nm_dokter'];
        }

        $nama_pemberi_informasi = $_POST['nama_pemberi_informasi'] ?? '';
        if (!empty($_POST['kd_pemberi_informasi'])) {
            $pet = $this->db('petugas')->where('nip', $_POST['kd_pemberi_informasi'])->oneArray();
            if ($pet) {
                $nama_pemberi_informasi = $pet['nama'];
            } else {
                $doc3 = $this->db('dokter')->where('kd_dokter', $_POST['kd_pemberi_informasi'])->oneArray();
                if ($doc3) $nama_pemberi_informasi = $doc3['nm_dokter'];
            }
        }

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $_POST['no_rkm_medis'] ?? '',
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'jam' => !empty($_POST['jam']) ? $_POST['jam'] : date('H:i:s'),
            'instalasi_unit' => $_POST['instalasi_unit'] ?? '',
            'kd_dokter_pengirim' => $_POST['kd_dokter_pengirim'] ?? '',
            'nama_dokter_pengirim' => $nama_dokter_pengirim,
            'jenis_pelayanan' => $_POST['jenis_pelayanan'] ?? '',
            'status_penerima' => $_POST['status_penerima'] ?? 'Pasien Sendiri',
            'nama_penerima' => $_POST['nama_penerima'] ?? '',
            'alamat_penerima' => $_POST['alamat_penerima'] ?? '',
            'no_identitas_penerima' => $_POST['no_identitas_penerima'] ?? '',
            'sebab_kerusakan_alat' => isset($_POST['sebab_kerusakan_alat']) ? 1 : 0,
            'sebab_kondisi_umum_pasien' => isset($_POST['sebab_kondisi_umum_pasien']) ? 1 : 0,
            'sebab_penundaan_penjadwalan' => isset($_POST['sebab_penundaan_penjadwalan']) ? 1 : 0,
            'sebab_pemadaman_listrik' => isset($_POST['sebab_pemadaman_listrik']) ? 1 : 0,
            'sebab_tempat_tidur_penuh' => isset($_POST['sebab_tempat_tidur_penuh']) ? 1 : 0,
            'sebab_faktor_penyulit' => isset($_POST['sebab_faktor_penyulit']) ? 1 : 0,
            'keterangan_faktor_penyulit' => $_POST['keterangan_faktor_penyulit'] ?? '',
            'solusi_dijadwalkan_ulang' => isset($_POST['solusi_dijadwalkan_ulang']) ? 1 : 0,
            'jadwal_akan_datang' => $_POST['jadwal_akan_datang'] ?? '',
            'solusi_dirujuk' => isset($_POST['solusi_dirujuk']) ? 1 : 0,
            'tujuan_rujukan' => $_POST['tujuan_rujukan'] ?? '',
            'solusi_kembali_dokter_pengirim' => isset($_POST['solusi_kembali_dokter_pengirim']) ? 1 : 0,
            'kd_dokter_dpjp' => $_POST['kd_dokter_dpjp'] ?? '',
            'nama_dokter_dpjp' => $nama_dokter_dpjp,
            'kd_pemberi_informasi' => $_POST['kd_pemberi_informasi'] ?? '',
            'nama_pemberi_informasi' => $nama_pemberi_informasi,
            'catatan' => $_POST['catatan'] ?? ''
        ];

        if ($id) {
            $this->db('lampiran_penundaan_kelambatan_pelayanan')
                 ->where('id', $id)
                 ->save($payload);
            $this->notify('success', 'Data Informasi Penundaan dan Kelambatan Pelayanan berhasil diperbarui.');
        } else {
            $this->db('lampiran_penundaan_kelambatan_pelayanan')
                 ->save($payload);
            $this->notify('success', 'Data Informasi Penundaan dan Kelambatan Pelayanan berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'penundaankelambatanmanage']));
    }

    public function getPenundaankelambatanhapus($id)
    {
        $this->db('lampiran_penundaan_kelambatan_pelayanan')
             ->where('id', $id)
             ->delete();
        $this->notify('success', 'Data Informasi Penundaan dan Kelambatan Pelayanan berhasil dihapus.');
        redirect(url([ADMIN, 'update_bmt', 'penundaankelambatanmanage']));
    }

    public function getPenundaankelambatancetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $query = "SELECT p.*, r.no_rkm_medis, pas.nm_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d1.nm_dokter as nama_dokter_pengirim_db,
                         d2.nm_dokter as nama_dokter_dpjp_db
                  FROM lampiran_penundaan_kelambatan_pelayanan p
                  LEFT JOIN reg_periksa r ON r.no_rawat = p.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d1 ON d1.kd_dokter = p.kd_dokter_pengirim
                  LEFT JOIN dokter d2 ON d2.kd_dokter = p.kd_dokter_dpjp
                  WHERE p.id = '$id'";

        $stmt = $this->db()->pdo()->query($query);
        $data = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;

        if (!$data) {
            echo "Data tidak ditemukan!";
            exit();
        }

        if (!empty($data['nama_dokter_pengirim_db'])) {
            $data['nama_dokter_pengirim'] = $data['nama_dokter_pengirim_db'];
        }
        if (!empty($data['nama_dokter_dpjp_db'])) {
            $data['nama_dokter_dpjp'] = $data['nama_dokter_dpjp_db'];
        }

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if (!empty($data['tanggal']) && $data['tanggal'] != '0000-00-00') {
            $ts = strtotime($data['tanggal']);
            $data['tanggal_indo'] = date('d', $ts) . ' ' . ($bulanIndo[(int)date('m', $ts)] ?? date('m', $ts)) . ' ' . date('Y', $ts);
        } else {
            $data['tanggal_indo'] = date('d') . ' ' . ($bulanIndo[(int)date('m')] ?? date('m')) . ' ' . date('Y');
        }

        if (!empty($data['jam'])) {
            $data['jam_wita'] = date('H:i', strtotime($data['jam']));
        } else {
            $data['jam_wita'] = date('H:i');
        }

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tpas = strtotime($data['tgl_lahir']);
            $data['tgl_lahir_pasien_indo'] = date('d', $tpas) . ' ' . ($bulanIndo[(int)date('m', $tpas)] ?? date('m', $tpas)) . ' ' . date('Y', $tpas);
        } else {
            $data['tgl_lahir_pasien_indo'] = '-';
        }

        $rmClean = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmClean, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_penundaan_kelambatan_pelayanan/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 12,
                    'margin_right' => 12,
                    'margin_top' => 10,
                    'margin_bottom' => 10,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('Informasi_Penundaan_Kelambatan_Pelayanan_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_penundaan_kelambatan_pelayanan/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - PERSETUJUAN UNTUK TESTING HIV
    // ============================================================

    public function anyPersetujuantestinghivmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = "SELECT h.*, r.no_rkm_medis, pas.nm_pasien, pas.nm_pasien as nama_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d.nm_dokter as nama_dokter_konselor,
                         p.nama as nama_petugas_konselor
                  FROM lampiran_persetujuan_testing_hiv h
                  LEFT JOIN reg_periksa r ON r.no_rawat = h.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d ON d.kd_dokter = h.kd_konselor
                  LEFT JOIN petugas p ON p.nip = h.kd_konselor
                  ORDER BY h.id DESC";

        $stmt = $this->db()->pdo()->query($query);
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        foreach ($rows as $k => $v) {
            $rows[$k]['nomor'] = $k + 1;
            $rows[$k]['nama_pasien'] = !empty($v['nama_pasien']) ? $v['nama_pasien'] : (!empty($v['nm_pasien']) ? $v['nm_pasien'] : '-');
            $rows[$k]['no_rkm_medis'] = !empty($v['no_rkm_medis']) ? $v['no_rkm_medis'] : '-';
            $rows[$k]['jk'] = !empty($v['jk']) ? $v['jk'] : '-';
            if (!empty($v['nama_dokter_konselor'])) {
                $rows[$k]['nama_konselor'] = $v['nama_dokter_konselor'];
            } elseif (!empty($v['nama_petugas_konselor'])) {
                $rows[$k]['nama_konselor'] = $v['nama_petugas_konselor'];
            } else {
                $rows[$k]['nama_konselor'] = '-';
            }
        }

        return $this->draw('lampiran_persetujuan_testing_hiv/manage.html', ['data' => $rows, 'rows' => $rows]);
    }

    public function getPersetujuantestinghivform($id = null)
    {
        $data = [];
        if ($id) {
            $data = $this->db('lampiran_persetujuan_testing_hiv')
                         ->where('id', $id)
                         ->oneArray();
            if ($data) {
                $pasien = $this->db('reg_periksa')
                               ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                               ->where('reg_periksa.no_rawat', $data['no_rawat'])
                               ->select(['pasien.nm_pasien', 'pasien.tgl_lahir', 'pasien.jk', 'pasien.alamat', 'pasien.no_ktp', 'reg_periksa.no_rkm_medis'])
                               ->oneArray();
                if ($pasien) {
                    $data['nm_pasien'] = $pasien['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $pasien['tgl_lahir'];
                    $data['jk_pasien'] = $pasien['jk'];
                    $data['alamat_pasien'] = $pasien['alamat'];
                    $data['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    $data['no_ktp_pasien'] = $pasien['no_ktp'];
                }
            }
        } else {
            $data = [
                'id' => '',
                'no_rawat' => $_GET['no_rawat'] ?? '',
                'no_rkm_medis' => '',
                'kode_klien' => 'VCT/' . date('Y/m') . '/' . rand(100, 999),
                'tanggal' => date('Y-m-d'),
                'tanggal_pelaksanaan' => date('Y-m-d'),
                'nama_pasien' => '',
                'kd_konselor' => '',
                'nama_konselor' => '',
                'status_persetujuan' => 'Setuju',
                'catatan' => '',
                'nm_pasien' => '',
                'tgl_lahir_pasien' => '',
                'jk_pasien' => '',
                'alamat_pasien' => '',
                'no_ktp_pasien' => ''
            ];

            if (!empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                            ->where('reg_periksa.no_rawat', $data['no_rawat'])
                            ->select([
                                'reg_periksa.no_rawat',
                                'reg_periksa.no_rkm_medis',
                                'reg_periksa.kd_dokter',
                                'dokter.nm_dokter',
                                'pasien.nm_pasien',
                                'pasien.tgl_lahir',
                                'pasien.jk',
                                'pasien.alamat',
                                'pasien.no_ktp'
                            ])
                            ->oneArray();

                if ($reg) {
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nm_pasien'] = $reg['nm_pasien'];
                    $data['nama_pasien'] = $reg['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $reg['tgl_lahir'];
                    $data['jk_pasien'] = $reg['jk'];
                    $data['alamat_pasien'] = $reg['alamat'];
                    $data['no_ktp_pasien'] = $reg['no_ktp'];
                    $data['kd_konselor'] = $reg['kd_dokter'];
                    $data['nama_konselor'] = $reg['nm_dokter'];
                }
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();
        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_persetujuan_testing_hiv/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'petugas' => $petugas
        ]);
    }

    public function postPersetujuantestinghivsave()
    {
        $id = $_POST['id'] ?? null;
        $no_rawat = $_POST['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            $this->notify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'persetujuantestinghivform']));
        }

        $nama_konselor = $_POST['nama_konselor'] ?? '';
        if (!empty($_POST['kd_konselor'])) {
            $doc = $this->db('dokter')->where('kd_dokter', $_POST['kd_konselor'])->oneArray();
            if ($doc) {
                $nama_konselor = $doc['nm_dokter'];
            } else {
                $pet = $this->db('petugas')->where('nip', $_POST['kd_konselor'])->oneArray();
                if ($pet) $nama_konselor = $pet['nama'];
            }
        }

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $_POST['no_rkm_medis'] ?? '',
            'kode_klien' => $_POST['kode_klien'] ?? '',
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'tanggal_pelaksanaan' => !empty($_POST['tanggal_pelaksanaan']) ? $_POST['tanggal_pelaksanaan'] : date('Y-m-d'),
            'nama_pasien' => $_POST['nama_pasien'] ?? '',
            'kd_konselor' => $_POST['kd_konselor'] ?? '',
            'nama_konselor' => $nama_konselor,
            'status_persetujuan' => $_POST['status_persetujuan'] ?? 'Setuju',
            'catatan' => $_POST['catatan'] ?? ''
        ];

        if ($id) {
            $this->db('lampiran_persetujuan_testing_hiv')
                 ->where('id', $id)
                 ->save($payload);
            $this->notify('success', 'Data Persetujuan Untuk Testing HIV berhasil diperbarui.');
        } else {
            $this->db('lampiran_persetujuan_testing_hiv')
                 ->save($payload);
            $this->notify('success', 'Data Persetujuan Untuk Testing HIV berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'persetujuantestinghivmanage']));
    }

    public function getPersetujuantestinghivhapus($id)
    {
        $this->db('lampiran_persetujuan_testing_hiv')
             ->where('id', $id)
             ->delete();
        $this->notify('success', 'Data Persetujuan Untuk Testing HIV berhasil dihapus.');
        redirect(url([ADMIN, 'update_bmt', 'persetujuantestinghivmanage']));
    }

    public function getPersetujuantestinghivcetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $query = "SELECT h.*, r.no_rkm_medis, pas.nm_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d.nm_dokter as nama_dokter_konselor,
                         p.nama as nama_petugas_konselor
                  FROM lampiran_persetujuan_testing_hiv h
                  LEFT JOIN reg_periksa r ON r.no_rawat = h.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d ON d.kd_dokter = h.kd_konselor
                  LEFT JOIN petugas p ON p.nip = h.kd_konselor
                  WHERE h.id = '$id'";

        $stmt = $this->db()->pdo()->query($query);
        $data = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;

        if (!$data) {
            echo "Data tidak ditemukan!";
            exit();
        }

        if (!empty($data['nama_dokter_konselor'])) {
            $data['nama_konselor'] = $data['nama_dokter_konselor'];
        } elseif (!empty($data['nama_petugas_konselor'])) {
            $data['nama_konselor'] = $data['nama_petugas_konselor'];
        }

        if (empty($data['nama_pasien']) && !empty($data['nm_pasien'])) {
            $data['nama_pasien'] = $data['nm_pasien'];
        }

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if (!empty($data['tanggal']) && $data['tanggal'] != '0000-00-00') {
            $ts = strtotime($data['tanggal']);
            $data['tanggal_indo'] = date('d', $ts) . ' ' . ($bulanIndo[(int)date('m', $ts)] ?? date('m', $ts)) . ' ' . date('Y', $ts);
        } else {
            $data['tanggal_indo'] = date('d') . ' ' . ($bulanIndo[(int)date('m')] ?? date('m')) . ' ' . date('Y');
        }

        if (!empty($data['tanggal_pelaksanaan']) && $data['tanggal_pelaksanaan'] != '0000-00-00') {
            $tp = strtotime($data['tanggal_pelaksanaan']);
            $data['tanggal_pelaksanaan_indo'] = date('d', $tp) . ' ' . ($bulanIndo[(int)date('m', $tp)] ?? date('m', $tp)) . ' ' . date('Y', $tp);
        } else {
            $data['tanggal_pelaksanaan_indo'] = $data['tanggal_indo'];
        }

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tpas = strtotime($data['tgl_lahir']);
            $data['tgl_lahir_pasien_indo'] = date('d', $tpas) . ' ' . ($bulanIndo[(int)date('m', $tpas)] ?? date('m', $tpas)) . ' ' . date('Y', $tpas);
        } else {
            $data['tgl_lahir_pasien_indo'] = '-';
        }

        $rmClean = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmClean, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_persetujuan_testing_hiv/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 12,
                    'margin_bottom' => 12,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('Persetujuan_Testing_HIV_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_persetujuan_testing_hiv/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    // ============================================================
    // LAMPIRAN - VCT PRA TESTING HIV
    // ============================================================

    public function anyVctpratestinghivmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = "SELECT v.*, r.no_rkm_medis, pas.nm_pasien, pas.nm_pasien as nama_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d.nm_dokter as nama_dokter_konselor,
                         p.nama as nama_petugas_konselor
                  FROM lampiran_vct_pra_testing_hiv v
                  LEFT JOIN reg_periksa r ON r.no_rawat = v.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d ON d.kd_dokter = v.kd_konselor
                  LEFT JOIN petugas p ON p.nip = v.kd_konselor
                  ORDER BY v.id DESC";

        $stmt = $this->db()->pdo()->query($query);
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        foreach ($rows as $k => $v) {
            $rows[$k]['nomor'] = $k + 1;
            $rows[$k]['nama_pasien'] = !empty($v['nama_pasien']) ? $v['nama_pasien'] : (!empty($v['nm_pasien']) ? $v['nm_pasien'] : '-');
            $rows[$k]['no_rkm_medis'] = !empty($v['no_rkm_medis']) ? $v['no_rkm_medis'] : '-';
            $rows[$k]['jk'] = !empty($v['jk']) ? $v['jk'] : '-';
            if (!empty($v['nama_dokter_konselor'])) {
                $rows[$k]['nama_konselor'] = $v['nama_dokter_konselor'];
            } elseif (!empty($v['nama_petugas_konselor'])) {
                $rows[$k]['nama_konselor'] = $v['nama_petugas_konselor'];
            } else {
                $rows[$k]['nama_konselor'] = '-';
            }
        }

        return $this->draw('lampiran_vct_pra_testing_hiv/manage.html', ['data' => $rows, 'rows' => $rows]);
    }

    public function getVctpratestinghivform($id = null)
    {
        $data = [];
        if ($id) {
            $data = $this->db('lampiran_vct_pra_testing_hiv')
                         ->where('id', $id)
                         ->oneArray();
            if ($data) {
                $pasien = $this->db('reg_periksa')
                               ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                               ->where('reg_periksa.no_rawat', $data['no_rawat'])
                               ->select(['pasien.nm_pasien', 'pasien.tgl_lahir', 'pasien.jk', 'pasien.alamat', 'pasien.no_ktp', 'reg_periksa.no_rkm_medis'])
                               ->oneArray();
                if ($pasien) {
                    $data['nm_pasien'] = $pasien['nm_pasien'];
                    $data['nama_pasien'] = $pasien['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $pasien['tgl_lahir'];
                    $data['jk_pasien'] = $pasien['jk'];
                    $data['alamat_pasien'] = $pasien['alamat'];
                    $data['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    $data['no_ktp_pasien'] = $pasien['no_ktp'];
                }
                $data['detail_pajanan_arr'] = !empty($data['detail_pajanan']) ? json_decode($data['detail_pajanan'], true) : [];
                $data['intervensi_konselor_arr'] = !empty($data['intervensi_konselor']) ? json_decode($data['intervensi_konselor'], true) : [];
            }
        } else {
            $data = [
                'id' => '',
                'no_rawat' => $_GET['no_rawat'] ?? '',
                'no_rkm_medis' => '',
                'kode_klien' => 'VCT/' . date('Y/m') . '/' . rand(100, 999),
                'tanggal' => date('Y-m-d'),
                'tanggal_tes_klien' => date('Y-m-d'),
                'tempat_tes' => 'RSGM Gusti Hasan Aman',
                'jenis_klien' => '1',
                'status_hubungan' => '2',
                'jumlah_anak' => '8',
                'umur_klien' => '3',
                'pendidikan' => '4',
                'pekerjaan' => '1',
                'pekerjaan_lain' => '',
                'pasangan_tetap' => '1',
                'status_pasangan' => '3',
                'tgl_tes_terakhir' => '',
                'detail_pajanan' => '',
                'detail_pajanan_arr' => [],
                'tes_ulang_window' => 'TIDAK',
                'tgl_tes_ulang' => '',
                'berisiko_orang_hiv' => '2',
                'klien_hamil' => '2',
                'trimester_kehamilan' => '',
                'kb_teratur' => '1',
                'riwayat_ims' => '2',
                'gejala_tb' => '2',
                'indikasi_bunuh_diri' => '2',
                'riwayat_melukai_diri' => '2',
                'mencederai_orang_lain' => '2',
                'kekerasan_pasangan' => '2',
                'dukungan_personal' => '1',
                'catatan_penyesuaian' => '',
                'intervensi_konselor' => '',
                'intervensi_konselor_arr' => [],
                'catatan_lainnya' => '',
                'hasil_pemeriksaan' => 'Antibody HIV negatif',
                'tanggal_mengetahui' => date('Y-m-d'),
                'kd_konselor' => '',
                'nama_konselor' => '',
                'nm_pasien' => '',
                'tgl_lahir_pasien' => '',
                'jk_pasien' => '',
                'alamat_pasien' => '',
                'no_ktp_pasien' => ''
            ];

            if (!empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                            ->where('reg_periksa.no_rawat', $data['no_rawat'])
                            ->select([
                                'reg_periksa.no_rawat',
                                'reg_periksa.no_rkm_medis',
                                'reg_periksa.kd_dokter',
                                'dokter.nm_dokter',
                                'pasien.nm_pasien',
                                'pasien.tgl_lahir',
                                'pasien.jk',
                                'pasien.alamat',
                                'pasien.no_ktp'
                            ])
                            ->oneArray();

                if ($reg) {
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nm_pasien'] = $reg['nm_pasien'];
                    $data['tgl_lahir_pasien'] = $reg['tgl_lahir'];
                    $data['jk_pasien'] = $reg['jk'];
                    $data['alamat_pasien'] = $reg['alamat'];
                    $data['no_ktp_pasien'] = $reg['no_ktp'];
                    $data['kd_konselor'] = $reg['kd_dokter'];
                    $data['nama_konselor'] = $reg['nm_dokter'];
                    $data['jenis_klien'] = ($reg['jk'] == 'P' || $reg['jk'] == 'Perempuan') ? '2' : '1';
                }
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();
        $petugas = $this->db('petugas')->where('status', '1')->toArray();

        return $this->draw('lampiran_vct_pra_testing_hiv/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'petugas' => $petugas
        ]);
    }

    public function postVctpratestinghivsave()
    {
        $id = $_POST['id'] ?? null;
        $no_rawat = $_POST['no_rawat'] ?? '';

        if (empty($no_rawat)) {
            $this->notify('failure', 'Nomor Rawat wajib diisi!');
            redirect(url([ADMIN, 'update_bmt', 'vctpratestinghivform']));
        }

        $nama_konselor = $_POST['nama_konselor'] ?? '';
        if (!empty($_POST['kd_konselor'])) {
            $doc = $this->db('dokter')->where('kd_dokter', $_POST['kd_konselor'])->oneArray();
            if ($doc) {
                $nama_konselor = $doc['nm_dokter'];
            } else {
                $pet = $this->db('petugas')->where('nip', $_POST['kd_konselor'])->oneArray();
                if ($pet) $nama_konselor = $pet['nama'];
            }
        }

        $detail_pajanan = isset($_POST['pajanan']) && is_array($_POST['pajanan']) ? json_encode($_POST['pajanan']) : ($_POST['detail_pajanan'] ?? '');
        $intervensi_konselor = isset($_POST['intervensi']) && is_array($_POST['intervensi']) ? json_encode($_POST['intervensi']) : ($_POST['intervensi_konselor'] ?? '');

        $payload = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $_POST['no_rkm_medis'] ?? '',
            'kode_klien' => $_POST['kode_klien'] ?? '',
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'tanggal_tes_klien' => !empty($_POST['tanggal_tes_klien']) ? $_POST['tanggal_tes_klien'] : date('Y-m-d'),
            'tempat_tes' => $_POST['tempat_tes'] ?? 'RSGM Gusti Hasan Aman',
            'jenis_klien' => $_POST['jenis_klien'] ?? '1',
            'status_hubungan' => $_POST['status_hubungan'] ?? '2',
            'jumlah_anak' => $_POST['jumlah_anak'] ?? '8',
            'umur_klien' => $_POST['umur_klien'] ?? '3',
            'pendidikan' => $_POST['pendidikan'] ?? '4',
            'pekerjaan' => $_POST['pekerjaan'] ?? '1',
            'pekerjaan_lain' => $_POST['pekerjaan_lain'] ?? '',
            'pasangan_tetap' => $_POST['pasangan_tetap'] ?? '1',
            'status_pasangan' => $_POST['status_pasangan'] ?? '3',
            'tgl_tes_terakhir' => !empty($_POST['tgl_tes_terakhir']) ? $_POST['tgl_tes_terakhir'] : null,
            'detail_pajanan' => $detail_pajanan,
            'tes_ulang_window' => $_POST['tes_ulang_window'] ?? 'TIDAK',
            'tgl_tes_ulang' => !empty($_POST['tgl_tes_ulang']) ? $_POST['tgl_tes_ulang'] : null,
            'berisiko_orang_hiv' => $_POST['berisiko_orang_hiv'] ?? '2',
            'klien_hamil' => $_POST['klien_hamil'] ?? '2',
            'trimester_kehamilan' => $_POST['trimester_kehamilan'] ?? '',
            'kb_teratur' => $_POST['kb_teratur'] ?? '1',
            'riwayat_ims' => $_POST['riwayat_ims'] ?? '2',
            'gejala_tb' => $_POST['gejala_tb'] ?? '2',
            'indikasi_bunuh_diri' => $_POST['indikasi_bunuh_diri'] ?? '2',
            'riwayat_melukai_diri' => $_POST['riwayat_melukai_diri'] ?? '2',
            'mencederai_orang_lain' => $_POST['mencederai_orang_lain'] ?? '2',
            'kekerasan_pasangan' => $_POST['kekerasan_pasangan'] ?? '2',
            'dukungan_personal' => $_POST['dukungan_personal'] ?? '1',
            'catatan_penyesuaian' => $_POST['catatan_penyesuaian'] ?? '',
            'intervensi_konselor' => $intervensi_konselor,
            'catatan_lainnya' => $_POST['catatan_lainnya'] ?? '',
            'hasil_pemeriksaan' => $_POST['hasil_pemeriksaan'] ?? 'Antibody HIV negatif',
            'tanggal_mengetahui' => !empty($_POST['tanggal_mengetahui']) ? $_POST['tanggal_mengetahui'] : date('Y-m-d'),
            'kd_konselor' => $_POST['kd_konselor'] ?? '',
            'nama_konselor' => $nama_konselor
        ];

        if ($id) {
            $this->db('lampiran_vct_pra_testing_hiv')
                 ->where('id', $id)
                 ->save($payload);
            $this->notify('success', 'Data VCT Pra Testing HIV berhasil diperbarui.');
        } else {
            $this->db('lampiran_vct_pra_testing_hiv')
                 ->save($payload);
            $this->notify('success', 'Data VCT Pra Testing HIV berhasil disimpan.');
        }

        redirect(url([ADMIN, 'update_bmt', 'vctpratestinghivmanage']));
    }

    public function getVctpratestinghivhapus($id)
    {
        $this->db('lampiran_vct_pra_testing_hiv')
             ->where('id', $id)
             ->delete();
        $this->notify('success', 'Data VCT Pra Testing HIV berhasil dihapus.');
        redirect(url([ADMIN, 'update_bmt', 'vctpratestinghivmanage']));
    }

    public function getVctpratestinghivcetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }
        $query = "SELECT v.*, r.no_rkm_medis, pas.nm_pasien, pas.tgl_lahir, pas.jk, pas.alamat as alamat_pasien,
                         d.nm_dokter as nama_dokter_konselor,
                         p.nama as nama_petugas_konselor
                  FROM lampiran_vct_pra_testing_hiv v
                  LEFT JOIN reg_periksa r ON r.no_rawat = v.no_rawat
                  LEFT JOIN pasien pas ON pas.no_rkm_medis = r.no_rkm_medis
                  LEFT JOIN dokter d ON d.kd_dokter = v.kd_konselor
                  LEFT JOIN petugas p ON p.nip = v.kd_konselor
                  WHERE v.id = '$id'";

        $stmt = $this->db()->pdo()->query($query);
        $data = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;

        if (!$data) {
            echo "Data tidak ditemukan!";
            exit();
        }

        if (!empty($data['nama_dokter_konselor'])) {
            $data['nama_konselor'] = $data['nama_dokter_konselor'];
        } elseif (!empty($data['nama_petugas_konselor'])) {
            $data['nama_konselor'] = $data['nama_petugas_konselor'];
        }

        if (empty($data['nama_pasien']) && !empty($data['nm_pasien'])) {
            $data['nama_pasien'] = $data['nm_pasien'];
        }

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Format tanggal indo
        $formatDateIndo = function($tgl) use ($bulanIndo) {
            if (!empty($tgl) && $tgl != '0000-00-00') {
                $ts = strtotime($tgl);
                return date('d', $ts) . ' ' . ($bulanIndo[(int)date('m', $ts)] ?? date('m', $ts)) . ' ' . date('Y', $ts);
            }
            return '-';
        };

        // Format tanggal slash (DD / MM / YYYY)
        $formatDateSlash = function($tgl) {
            if (!empty($tgl) && $tgl != '0000-00-00') {
                $ts = strtotime($tgl);
                return date('d / m / Y', $ts);
            }
            return '____ / ____ / ________';
        };

        $data['tanggal_indo'] = $formatDateIndo($data['tanggal']);
        $data['tanggal_slash'] = $formatDateSlash($data['tanggal']);
        $data['tanggal_tes_klien_slash'] = $formatDateSlash($data['tanggal_tes_klien']);
        $data['tgl_tes_terakhir_slash'] = $formatDateSlash($data['tgl_tes_terakhir']);
        $data['tgl_tes_ulang_slash'] = $formatDateSlash($data['tgl_tes_ulang']);
        $data['tanggal_mengetahui_indo'] = $formatDateIndo($data['tanggal_mengetahui']);
        $data['tgl_lahir_pasien_indo'] = $formatDateIndo($data['tgl_lahir']);

        $rmClean = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmClean, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        // Pajanan items list
        $defaultPajanan = [
            'okupasional' => ['nama' => 'Pajanan Okupasional', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'tatto' => ['nama' => 'Tatto, goresan', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'produk_darah' => ['nama' => 'Produk Darah', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'seks_vaginal' => ['nama' => 'Hubungan Seks Vaginal', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'oral_seks' => ['nama' => 'Oral Seks', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'anal_seks' => ['nama' => 'Anal Seks', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'kekerasan_seksual' => ['nama' => 'Riwayat kekerasan Seksual', 'kode' => '2', 'tgl' => '', 'window' => '2'],
            'peralatan_suntik' => ['nama' => 'Bergantian peralatan suntik', 'kode' => '2', 'tgl' => '', 'window' => '2']
        ];
        $parsedPajanan = !empty($data['detail_pajanan']) ? json_decode($data['detail_pajanan'], true) : [];
        if (!is_array($parsedPajanan)) $parsedPajanan = [];

        $pajananRows = [];
        foreach ($defaultPajanan as $k => $def) {
            $item = $parsedPajanan[$k] ?? [];
            $kodeVal = isset($item['kode']) ? $item['kode'] : $def['kode'];
            $tglVal = !empty($item['tgl']) ? $formatDateSlash($item['tgl']) : '____ / ____ / ________';
            $winVal = isset($item['window']) ? $item['window'] : $def['window'];

            $pajananRows[] = [
                'key' => $k,
                'nama' => $def['nama'],
                'kode' => $kodeVal,
                'tgl' => $tglVal,
                'window' => $winVal
            ];
        }
        $data['pajanan_rows'] = $pajananRows;

        // Intervensi konselor list (14 item)
        $defaultIntervensi = [
            'hal2_kerahasiaan' => 'Kerahasiaandan pribadi yang saudara tawarkan pada klien',
            'hal2_dasar_hiv' => 'Informasi dasar tentang HIV dan penularannya',
            'hal2_potensi_keuntungan' => 'Informasi dasar potensi keuntungan dan kesulitan tes ( hukum dsb)',
            'hal2_penilaian_risiko' => 'Penilaian risiko personal',
            'hal2_eksplorasi_hambatan' => 'Eksplorasi dan pemecahan problem hambatan pengurangan risiko',
            'hal2_kondom' => 'Penggunaan kondom, termasuk demonstrasi penggunaan kondom',
            'hal2_kesiapan_status' => 'Kesiapan klien mempelajari status HIV',
            'hal3_eksplorasi_positif' => 'Eksplorasi akan apa yang dilakukan klien ketika hasil tes HIV (+), dan penyesuaian diri yang dimungkinkan ketika hasil tes HIV (+). Termasuk didalamnya penilaian kemungkinan bunuh diri',
            'hal3_pemikiran_reproduksi' => 'Pemikiran klien akan perencanaan reproduksi dan keluarga berencana',
            'hal3_dukungan_keluarga' => 'Eksplorasi kemungkinan dukungan keluarga dan kawan-kawan',
            'hal3_penilaian_risiko2' => 'Penilaian risiko personal',
            'hal3_isu_penting' => 'Isu penting yang dibutuhkan klien lainnya',
            'hal3_info_prosedur' => 'Informasi dasar tentang tes dan prosedur pemeriksaan sampai didapat hasil',
            'hal3_informed_consent' => 'Informed consent untuk melakukan tes HIV'
        ];
        $parsedIntervensi = !empty($data['intervensi_konselor']) ? json_decode($data['intervensi_konselor'], true) : [];
        if (!is_array($parsedIntervensi)) $parsedIntervensi = [];

        $data['intervensi'] = [];
        foreach ($defaultIntervensi as $k => $label) {
            $data['intervensi'][$k] = !empty($parsedIntervensi[$k]) ? true : false;
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1') {
            $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
            $html = $this->draw('lampiran_vct_pra_testing_hiv/cetak.html', ['data' => $data, 'is_mpdf' => true]);
            try {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 12,
                    'margin_right' => 12,
                    'margin_top' => 10,
                    'margin_bottom' => 10,
                ]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('VCT_Pra_Testing_HIV_' . $data['no_rkm_medis'] . '.pdf', 'I');
                exit();
            } catch (\Throwable $e) {
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_vct_pra_testing_hiv/cetak.html', ['data' => $data, 'is_mpdf' => false]);
        exit();
    }

    public function navigation()
    {
        return [
            'Rawat Jalan' => [
                'RM.GD 4 - ASESMEN KEPERAWATAN.ASUHAN GIGI DAN MULUT UGD' => 'manage',
                'RM.RJ-10 - LEMBAR EDUKASI PASIEN DAN KELUARGA TERINTEGRASI' => 'edukasimanage',
                'LAMPIRAN - FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ)' => 'prmrjmanage',
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
                'LAMPIRAN - ASESMEN MEDIS UMUM IRNA' => 'amumanage',
                'LAMPIRAN - CAIRAN INFUS' => 'cairaninfusmanage',
                'LAMPIRAN - FORM A MANAJER PELAYANAN PASIEN' => 'formamppmanage',
                'LAMPIRAN - FORM ASESMEN ULANG STATUS FUNGSIONAL' => 'statusfungsionalulangmanage',
                'LAMPIRAN - FORM B - CATATAN IMPLEMENTASI MANAJER PELAYANAN PASIEN MPP' => 'formbmppmanage',
                'LAMPIRAN - FORMULIR IDENTIFIKASI KEBUTUHAN PRIVASI PASIEN' => 'kebutuhanprivasimanage',
                'LAMPIRAN - FORMULIR MENINGGALKAN RUMAH SAKIT SELAMA PERIODE TERTENTU' => 'meninggalkanrsmanage',
                'LAMPIRAN - FORMULIR PENGHENTIAN PENGOBATAN' => 'penghentianpengobatanmanage',
                'LAMPIRAN - FORMULIR PROFIL RINGKAS MEDIS RAWAT JALAN (PRMRJ)' => 'prmrjmanage',
                'LAMPIRAN - FORMULIR SKRINING DARI LUAR RUMAH SAKIT' => 'skriningluarrsmanage',
                'LAMPIRAN - FORMULIR STABILISASI PASIEN SEBELUM TRANSFER' => 'stabilisasipasienmanage',
                'LAMPIRAN - INTERVENSI DAN PENGKAJIAN ULANG NYERI' => 'intervensinyerimanage',
                'LAMPIRAN - SURAT PERMINTAAN SECOND OPINION' => 'secondopinionmanage',
                'LAMPIRAN - PENILAIAN KEJADIAN PHLEIBITIS' => 'penilaianphlebitismanage',
                'LAMPIRAN - PERMINTAAN PELAYANAN KEROHANIAN' => 'pelayanankerohanianmanage',
                'RML - MONITORING PASIEN KATETER URINE' => 'monitoringkateterurinmanage',
                'LAMPIRAN - KONSULTASI INTERNAL' => 'konsultasiinternalmanage',
                'LAMPIRAN - MONITORING PASIEN SELAMA TRANSFER' => 'monitoringpasienselamatransfermanage',
                'LAMPIRAN - PENILAIAN DEKUBITUS' => 'penilaiandekubitusmanage',
                'LAMPIRAN - PENILAIAN KEJADIAN INFEKSI LUKA OPERASI (ILO)' => 'penilaianilomanage',
                'LAMPIRAN - PENILAIAN KEJADIAN INFEKSI SALURAN KEMIH (ISK)' => 'penilaianiskmanage',
                'LAMPIRAN - SURAT PERNYATAAN BERSEDIA MENUNGGU' => 'bersediamenunggumanage',
                'LAMPIRAN - INFORMASI PENUNDAAN DAN KELAMBATAN PELAYANAN' => 'penundaankelambatanmanage',
                'LAMPIRAN - PERSETUJUAN UNTUK TESTING HIV' => 'persetujuantestinghivmanage',
                'LAMPIRAN - VCT PRA TESTING HIV' => 'vctpratestinghivmanage',
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
                        ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

            $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
            $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
            $html = $this->draw('edukasipasienter/cetak.html', ['data' => $data, 'detail' => $detail, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
            if ($is_mpdf) {
@ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
                $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
                $mpdf->Output('EDUKASI_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
                exit();
            }
            echo $html;
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
            ->select('asesmen_keperawatan_gigi_ugd.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, reg_periksa.no_rkm_medis')
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
                ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
            ->leftJoin('petugas', 'petugas.nip = asesmen_keperawatan_gigi_ugd.nip')
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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('akgmu/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_GD4_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans1_tindakan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'sumber_data' => '', 'kategori_pasien' => '',
            'tanggal_masuk' => '', 'waktu_masuk' => '', 'keluhan_utama' => '', 'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '', 'riwayat_penyakit_keluarga' => '', 'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_keterangan' => '', 'obat_rutin' => '', 'penyakit_menular' => '',
            'penyakit_jantung' => 'Tidak Ada', 'hipertensi' => 'Tidak Ada', 'diabetes' => 'Tidak Ada',
            'haemophilia' => 'Tidak Ada', 'gangguan_ginjal' => 'Tidak Ada', 'gangguan_ginjal_ket' => '',
            'penyakit_sistemik_lainnya' => 'Tidak Ada', 'penyakit_sistemik_lainnya_ket' => '',
            'riwayat_operasi' => 'Tidak Ada', 'riwayat_operasi_ket' => '', 'anak_ke' => '', 'dari_saudara' => '',
            'cara_kelahiran' => '', 'umur_kelahiran' => '', 'kelainan_bawaan' => '', 'kelainan_bawaan_ket' => '',
            'imunisasi_bcg' => '', 'imunisasi_polio' => '', 'imunisasi_dpt' => '', 'imunisasi_campak' => '',
            'imunisasi_hepatitis_b' => '', 'imunisasi_lainnya' => '', 'tumbuh_gigi_pertama' => '',
            'mulai_bisa_duduk' => '', 'berjalan_sendiri' => '', 'mulai_bisa_bicara' => '', 'mulai_bisa_membaca' => '',
            'geriatri_penglihatan' => '', 'geriatri_pendengaran' => '', 'geriatri_berkemih' => '',
            'geriatri_daya_ingat' => '', 'geriatri_bicara' => '', 'geriatri_gigi_palsu' => '', 'geriatri_lainnya' => '',
            'keadaan_umum' => '', 'kesadaran' => '', 'tekanan_darah' => '', 'frekuensi_nadi' => '',
            'frekuensi_napas' => '', 'suhu' => '', 'berat_badan' => '', 'tinggi_badan' => '', 'golongan_darah' => '',
            'nyeri_keluhan' => 'Tidak', 'nyeri_lokasi' => '', 'nyeri_durasi' => '', 'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '', 'nyeri_menjalar' => 'Tidak', 'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '', 'nyeri_hilang' => '', 'nyeri_tindak_lanjut' => '', 'nyeri_konsul_ke' => '',
            'skala_nyeri' => '', 'penunjang_lab' => '', 'penunjang_rontgen' => '', 'penunjang_lainnya' => '',
            'diagnosis' => '', 'rencana_tindak_lanjut' => '', 'tanggal_ttd' => '', 'jam_ttd' => '', 'nip_dpjp' => ''
        ];

        if (!empty($id)) {
            $data = $this->db('ans1_tindakan_pembiusan')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat']) && empty($data['nm_pasien'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
            ->select('ans1_tindakan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans1/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
            @ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            if (ob_get_length()) {
                ob_clean();
            }
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS1_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans2_persetujuan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat, pasien.umur')
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
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'sumber_data' => '', 'kategori_pasien' => '',
            'tanggal_masuk' => '', 'waktu_masuk' => '', 'keluhan_utama' => '', 'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '', 'riwayat_penyakit_keluarga' => '', 'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_keterangan' => '', 'obat_rutin' => '', 'penyakit_menular' => '',
            'penyakit_jantung' => 'Tidak Ada', 'hipertensi' => 'Tidak Ada', 'diabetes' => 'Tidak Ada',
            'haemophilia' => 'Tidak Ada', 'gangguan_ginjal' => 'Tidak Ada', 'gangguan_ginjal_ket' => '',
            'penyakit_sistemik_lainnya' => 'Tidak Ada', 'penyakit_sistemik_lainnya_ket' => '',
            'riwayat_operasi' => 'Tidak Ada', 'riwayat_operasi_ket' => '', 'anak_ke' => '', 'dari_saudara' => '',
            'cara_kelahiran' => '', 'umur_kelahiran' => '', 'kelainan_bawaan' => '', 'kelainan_bawaan_ket' => '',
            'imunisasi_bcg' => '', 'imunisasi_polio' => '', 'imunisasi_dpt' => '', 'imunisasi_campak' => '',
            'imunisasi_hepatitis_b' => '', 'imunisasi_lainnya' => '', 'tumbuh_gigi_pertama' => '',
            'mulai_bisa_duduk' => '', 'berjalan_sendiri' => '', 'mulai_bisa_bicara' => '', 'mulai_bisa_membaca' => '',
            'geriatri_penglihatan' => '', 'geriatri_pendengaran' => '', 'geriatri_berkemih' => '',
            'geriatri_daya_ingat' => '', 'geriatri_bicara' => '', 'geriatri_gigi_palsu' => '', 'geriatri_lainnya' => '',
            'keadaan_umum' => '', 'kesadaran' => '', 'tekanan_darah' => '', 'frekuensi_nadi' => '',
            'frekuensi_napas' => '', 'suhu' => '', 'berat_badan' => '', 'tinggi_badan' => '', 'golongan_darah' => '',
            'nyeri_keluhan' => 'Tidak', 'nyeri_lokasi' => '', 'nyeri_durasi' => '', 'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '', 'nyeri_menjalar' => 'Tidak', 'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '', 'nyeri_hilang' => '', 'nyeri_tindak_lanjut' => '', 'nyeri_konsul_ke' => '',
            'skala_nyeri' => '', 'penunjang_lab' => '', 'penunjang_rontgen' => '', 'penunjang_lainnya' => '',
            'diagnosis' => '', 'rencana_tindak_lanjut' => '', 'tanggal_ttd' => '', 'jam_ttd' => '', 'nip_dpjp' => ''
        ];

        if (!empty($id)) {
            $data = $this->db('ans2_persetujuan_pembiusan')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat']) && empty($data['nm_pasien'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
            ->select('ans2_persetujuan_pembiusan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans2/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS2_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans3_assesmen_prasedasi_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'sumber_data' => '', 'kategori_pasien' => '',
            'tanggal_masuk' => '', 'waktu_masuk' => '', 'keluhan_utama' => '', 'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '', 'riwayat_penyakit_keluarga' => '', 'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_keterangan' => '', 'obat_rutin' => '', 'penyakit_menular' => '',
            'penyakit_jantung' => 'Tidak Ada', 'hipertensi' => 'Tidak Ada', 'diabetes' => 'Tidak Ada',
            'haemophilia' => 'Tidak Ada', 'gangguan_ginjal' => 'Tidak Ada', 'gangguan_ginjal_ket' => '',
            'penyakit_sistemik_lainnya' => 'Tidak Ada', 'penyakit_sistemik_lainnya_ket' => '',
            'riwayat_operasi' => 'Tidak Ada', 'riwayat_operasi_ket' => '', 'anak_ke' => '', 'dari_saudara' => '',
            'cara_kelahiran' => '', 'umur_kelahiran' => '', 'kelainan_bawaan' => '', 'kelainan_bawaan_ket' => '',
            'imunisasi_bcg' => '', 'imunisasi_polio' => '', 'imunisasi_dpt' => '', 'imunisasi_campak' => '',
            'imunisasi_hepatitis_b' => '', 'imunisasi_lainnya' => '', 'tumbuh_gigi_pertama' => '',
            'mulai_bisa_duduk' => '', 'berjalan_sendiri' => '', 'mulai_bisa_bicara' => '', 'mulai_bisa_membaca' => '',
            'geriatri_penglihatan' => '', 'geriatri_pendengaran' => '', 'geriatri_berkemih' => '',
            'geriatri_daya_ingat' => '', 'geriatri_bicara' => '', 'geriatri_gigi_palsu' => '', 'geriatri_lainnya' => '',
            'keadaan_umum' => '', 'kesadaran' => '', 'tekanan_darah' => '', 'frekuensi_nadi' => '',
            'frekuensi_napas' => '', 'suhu' => '', 'berat_badan' => '', 'tinggi_badan' => '', 'golongan_darah' => '',
            'nyeri_keluhan' => 'Tidak', 'nyeri_lokasi' => '', 'nyeri_durasi' => '', 'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '', 'nyeri_menjalar' => 'Tidak', 'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '', 'nyeri_hilang' => '', 'nyeri_tindak_lanjut' => '', 'nyeri_konsul_ke' => '',
            'skala_nyeri' => '', 'penunjang_lab' => '', 'penunjang_rontgen' => '', 'penunjang_lainnya' => '',
            'diagnosis' => '', 'rencana_tindak_lanjut' => '', 'tanggal_ttd' => '', 'jam_ttd' => '', 'nip_dpjp' => ''
        ];

        if (!empty($id)) {
            $data = $this->db('ans3_assesmen_prasedasi_anestesi')->where('id', $id)->oneArray();
            if ($data && !empty($data['no_rawat'])) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
            ->select('ans3_assesmen_prasedasi_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans3/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS3_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans4_rencana_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
            ->select('ans4_rencana_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $detail = $this->db('ans4_evaluasi_premedikasi_detail')->where('id_ans4', $id)->toArray();

        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans4/cetak.html', ['data' => $data, 'detail' => $detail, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS4_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans6_status_post_anestesi.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('ans6_status_post_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('ans6_status_post_anestesi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $detail = $this->db('ans6_protokol_nyeri_detail')->where('id_ans6', $id)->toArray();

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans6/cetak.html', ['data' => $data, 'detail' => $detail, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS6_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ans7_serah_terima.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('ans7_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('ans7_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ans7/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('ANS7_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bm1_informasi_tindakan.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bm1_informasi_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('bm1_informasi_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
        
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bm1/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BM1_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bm2_persetujuan_tindakan.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bm2_persetujuan_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('bm2_persetujuan_tindakan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
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
        
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bm2/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BM2_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bm4_laporan_pembedahan.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bm4_laporan_pembedahan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('bm4_laporan_pembedahan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bm4/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BM4_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bmi1_persetujuan_anestesi_lokal.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bmi1_persetujuan_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_db, pasien.umur as umur_db')
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
            ->select('bmi1_persetujuan_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_db, pasien.tgl_lahir, pasien.umur as umur_db')
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
        
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

        if (!empty($data['tgl_lahir'])) {
            $tgl2 = explode('-', $data['tgl_lahir']);
            if (count($tgl2) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl2[2] . ' ' . $bulan[(int)$tgl2[1]] . ' ' . $tgl2[0];
            }
        }

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bmi1/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BMI1_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bmi4_pra_anestesi_lokal.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bmi4_pra_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur')
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
            ->select('bmi4_pra_anestesi_lokal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }
        
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bmi4/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BMI4_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('bmi5_bedah_minor.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('bmi5_bedah_minor.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
            ->select('bmi5_bedah_minor.*, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, reg_periksa.no_rkm_medis')
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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('bmi5/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('BMI5_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ri01b_hak_kewajiban_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, pasien.pnd, pasien.bahasa_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
            ->select('ri01b_hak_kewajiban_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        // Format tanggal lahir
        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri01b/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI01B_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
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
            ->select('ri02_ringkasan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.gol_darah, pasien.alamat, pasien.pekerjaan, pasien.pnd, pasien.agama, pasien.stts_nikah, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar, kamar.kelas')
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
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah, pasien.umur, pasien.alamat, pasien.pnd, pasien.agama, pasien.pekerjaan, pasien.stts_nikah')->oneArray();
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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri02/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI02_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri03_serah_terima.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri03/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI03_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
    }

    // ============================================================
    // RM.RI 04 - TRANSFER PASIEN INTERNAL
    // ============================================================
    public function getRi04manage() {
        $data = $this->db('ri04_transfer_internal')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri04_transfer_internal.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri04_transfer_internal.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                $reg = $this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri04/cetak.html', ['p' => $p, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI04_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri09_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri09_status_fungsional.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri09/cetak.html', ['p' => $p, 'rows' => $rows, 'katRows' => $katRows, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI09_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri11b_pemberian_makan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri11b_pemberian_makan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri11b/cetak.html', ['p' => $p, 'p1' => $page1_rows, 'p2' => $page2_rows, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI11B_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri14_rekonsiliasi_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri14_rekonsiliasi_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri14/cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI14_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri15_instruksi_medis.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri15_instruksi_medis.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri15/cetak.html', ['p' => $p, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI15_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri15a_instruksi_medis_farmakologi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri15a_instruksi_medis_farmakologi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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

        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri15a/ri15a_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI15A_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri15b_jadwal_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.alamat, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri15b_jadwal_obat.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri15b/ri15b_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI15B_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri18_edukasi_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.umur, pasien.jk, pasien.alamat, pasien.pnd, pasien.bahasa_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri18_edukasi_pasien.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri18/ri18_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI18_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri19_discharge_planning.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.umur, pasien.jk, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri19_discharge_planning.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri19/ri19_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI19_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('ri20_resume_pulang.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar, bangsal.nm_bangsal, kamar.kd_kamar')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('ri20_resume_pulang.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir, pasien.alamat')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('ri20/ri20_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RI20_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('rj02a_hak_kewajiban.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur, pasien.alamat')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('rj02a_hak_kewajiban.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('rj02a/rj02a_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RJ02A_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('rj07_asesmen_ulang_nyeri.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.umur, pasien.alamat')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('rj07_asesmen_ulang_nyeri.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('rj07/rj07_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RJ07_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('rj10_edukasi_terintegrasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.pnd, pasien.bahasa_pasien, pasien.umur')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('rj10_edukasi_terintegrasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('rj10/rj10_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RJ10_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('rj11_pemberian_informasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.umur, pasien.jk, pasien.alamat')
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
                    ->where('reg_periksa.no_rawat', $data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')->oneArray();
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
            ->select('rj11_pemberian_informasi.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
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
        
        $p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('rj11/rj11_cetak.html', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('RM_RJ11_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
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
            ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien')
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
                ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk, pasien.tgl_lahir')
                ->oneArray();
            if ($p) {
                ob_clean(); echo json_encode(array_merge(['success' => true, 'no_rawat' => $no_rawat], $p)); exit();
            } else {
                ob_clean(); echo json_encode(['success' => false]); exit();
            }
        }

        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'no_rkm_medis' => '',
            'jk_pasien' => '', 'tgl_lahir_pasien' => '', 'nama_pihak' => '',
            'jk_pihak' => '', 'no_identitas_pihak' => '', 'alamat_pihak' => '',
            'selaku_pihak' => '', 'alasan_pulang' => '', 'tgl_surat' => '',
            'saksi_rs' => '', 'saksi_pasien' => '', 'pembuat_pernyataan' => ''
        ];
        if ($id) {
            $data = $this->db('surat_pulang_aps')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = surat_pulang_aps.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_pasien, pasien.tgl_lahir as tgl_lahir_pasien')
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
            'saksi_rs' => '', // Dikosongkan agar ditandatangani manual
            'saksi_pasien' => '', // Dikosongkan agar ditandatangani manual
            'pembuat_pernyataan' => $_POST['nama_pihak'],
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            unset($data['created_at']);
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
            ->select('surat_pulang_aps.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_pasien, pasien.tgl_lahir as tgl_lahir_pasien, pasien.umur')
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

        $data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('pulangaps/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {
@ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
if (ob_get_length()) {
    ob_clean();
}
            $mpdf->Output('PULANGAPS_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }
        echo $html;
        exit();
    }

    // ============================================================
    // LAMPIRAN - PERSETUJUAN PROSEDUR TINDAKAN RADIOLOGI (RAJAL)
    // ============================================================

    public function anyPersetujuanradiologimanage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            $id = $_GET['id'];
            $this->db('lampiran_persetujuan_tindakan_radiologi')->where('id', $id)->delete();
            $this->notify('success', 'Data persetujuan tindakan radiologi berhasil dihapus');
            redirect(url([ADMIN, 'update_bmt', 'persetujuanradiologimanage']));
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $data = $this->db('lampiran_persetujuan_tindakan_radiologi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_persetujuan_tindakan_radiologi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_persetujuan_tindakan_radiologi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('lampiran_persetujuan_tindakan_radiologi.*, COALESCE(lampiran_persetujuan_tindakan_radiologi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_pasien')
            ->desc('lampiran_persetujuan_tindakan_radiologi.id')
            ->toArray();

        foreach ($data as $k => $v) {
            $data[$k]['nomor'] = $k + 1;
        }

        return $this->draw('lampiran_persetujuan_tindakan_radiologi/manage.html', ['data' => $data]);
    }

    public function anyPersetujuanradiologiform($id = null)
    {
        // AJAX auto lookup info pasien
        if (isset($_GET['ajax_patient']) || isset($_POST['ajax_patient'])) {
            $no_rawat = $_GET['no_rawat'] ?? ($_POST['no_rawat'] ?? '');
            $no_rawat = trim($no_rawat);
            if (is_numeric($no_rawat) && strlen($no_rawat) < 6) {
                $no_rawat = str_pad($no_rawat, 6, '0', STR_PAD_LEFT);
            }

            $result = ['success' => false];
            if (!empty($no_rawat)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->leftJoin('kelurahan', 'kelurahan.kd_kel = pasien.kd_kel')
                    ->leftJoin('kecamatan', 'kecamatan.kd_kec = pasien.kd_kec')
                    ->leftJoin('kabupaten', 'kabupaten.kd_kab = pasien.kd_kab')
                    ->leftJoin('propinsi', 'propinsi.kd_prop = pasien.kd_prop')
                    ->where('reg_periksa.no_rawat', $no_rawat)
                    ->orWhere('reg_periksa.no_rkm_medis', $no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.alamat, pasien.umur, kelurahan.nm_kel, kecamatan.nm_kec, kabupaten.nm_kab, propinsi.nm_prop')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();

                if ($reg) {
                    $result = [
                        'success'       => true,
                        'no_rawat'      => $reg['no_rawat'] ?? '',
                        'no_rkm_medis'  => $reg['no_rkm_medis'] ?? '',
                        'nm_pasien'     => $reg['nm_pasien'] ?? '',
                        'no_ktp'        => $reg['no_ktp'] ?? '',
                        'jk'            => $reg['jk'] ?? '',
                        'tmp_lahir'     => $reg['tmp_lahir'] ?? '',
                        'tgl_lahir'     => $reg['tgl_lahir'] ?? '',
                        'umur'          => $reg['umur'] ?? '',
                        'alamat'        => $reg['alamat'] ?? '',
                        'nm_kel'        => $reg['nm_kel'] ?? '',
                        'nm_kec'        => $reg['nm_kec'] ?? '',
                        'nm_kab'        => $reg['nm_kab'] ?? '',
                        'nm_prop'       => $reg['nm_prop'] ?? ''
                    ];
                }
            }
            echo json_encode($result);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $_POST;
            $saveData = [
                'no_rawat'                        => trim($post['no_rawat'] ?? ''),
                'no_rkm_medis'                   => trim($post['no_rkm_medis'] ?? ''),
                'nama_penanggung_jawab'          => trim($post['nama_penanggung_jawab'] ?? ''),
                'jk_penanggung_jawab'            => $post['jk_penanggung_jawab'] ?? 'L',
                'tmp_lahir_penanggung_jawab'     => trim($post['tmp_lahir_penanggung_jawab'] ?? ''),
                'tgl_lahir_penanggung_jawab'     => !empty($post['tgl_lahir_penanggung_jawab']) ? $post['tgl_lahir_penanggung_jawab'] : null,
                'usia_penanggung_jawab'          => trim($post['usia_penanggung_jawab'] ?? ''),
                'no_identitas_penanggung_jawab'  => trim($post['no_identitas_penanggung_jawab'] ?? ''),
                'jenis_identitas_penanggung_jawab' => trim($post['jenis_identitas_penanggung_jawab'] ?? 'KTP'),
                'alamat_penanggung_jawab'        => trim($post['alamat_penanggung_jawab'] ?? ''),
                'kelurahan_penanggung_jawab'     => trim($post['kelurahan_penanggung_jawab'] ?? ''),
                'kecamatan_penanggung_jawab'     => trim($post['kecamatan_penanggung_jawab'] ?? ''),
                'kabupaten_penanggung_jawab'     => trim($post['kabupaten_penanggung_jawab'] ?? ''),
                'provinsi_penanggung_jawab'      => trim($post['provinsi_penanggung_jawab'] ?? ''),
                'hubungan_pasien'                => trim($post['hubungan_pasien'] ?? 'Diri Sendiri'),
                'hubungan_pasien_lainnya'        => trim($post['hubungan_pasien_lainnya'] ?? ''),
                'tindakan_radiologi'             => trim($post['tindakan_radiologi'] ?? ''),
                'nama_pasien_terhadap'           => trim($post['nama_pasien_terhadap'] ?? ''),
                'jk_pasien_terhadap'             => $post['jk_pasien_terhadap'] ?? 'L',
                'tmp_lahir_pasien_terhadap'      => trim($post['tmp_lahir_pasien_terhadap'] ?? ''),
                'tgl_lahir_pasien_terhadap'      => !empty($post['tgl_lahir_pasien_terhadap']) ? $post['tgl_lahir_pasien_terhadap'] : null,
                'usia_pasien_terhadap'           => trim($post['usia_pasien_terhadap'] ?? ''),
                'alamat_pasien_terhadap'         => trim($post['alamat_pasien_terhadap'] ?? ''),
                'kelurahan_pasien_terhadap'      => trim($post['kelurahan_pasien_terhadap'] ?? ''),
                'kecamatan_pasien_terhadap'      => trim($post['kecamatan_pasien_terhadap'] ?? ''),
                'kabupaten_pasien_terhadap'      => trim($post['kabupaten_pasien_terhadap'] ?? ''),
                'provinsi_pasien_terhadap'       => trim($post['provinsi_pasien_terhadap'] ?? ''),
                'kota_surat'                     => trim($post['kota_surat'] ?? 'Banjarmasin'),
                'tanggal_surat'                  => !empty($post['tanggal_surat']) ? $post['tanggal_surat'] : date('Y-m-d'),
                'jam_surat'                      => !empty($post['jam_surat']) ? $post['jam_surat'] : date('H:i:s'),
                'nama_yang_menyatakan'           => trim($post['nama_yang_menyatakan'] ?? ''),
                'kd_dokter'                      => trim($post['kd_dokter'] ?? ''),
                'nama_dokter'                    => trim($post['nama_dokter'] ?? ''),
                'nama_saksi_1'                   => trim($post['nama_saksi_1'] ?? ''),
                'nama_saksi_2'                   => trim($post['nama_saksi_2'] ?? '')
            ];

            if ($id) {
                $saveData['updated_at'] = date('Y-m-d H:i:s');
                $this->db('lampiran_persetujuan_tindakan_radiologi')->where('id', $id)->save($saveData);
                $this->notify('success', 'Data persetujuan tindakan radiologi berhasil diperbarui');
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $saveData['updated_at'] = date('Y-m-d H:i:s');
                $this->db('lampiran_persetujuan_tindakan_radiologi')->save($saveData);
                $this->notify('success', 'Data persetujuan tindakan radiologi berhasil disimpan');
            }
            redirect(url([ADMIN, 'update_bmt', 'persetujuanradiologimanage']));
        }

        $defaultData = [
            'id'                               => '',
            'no_rawat'                         => $_GET['no_rawat'] ?? '',
            'no_rkm_medis'                     => '',
            'nama_penanggung_jawab'            => '',
            'jk_penanggung_jawab'              => 'L',
            'tmp_lahir_penanggung_jawab'       => '',
            'tgl_lahir_penanggung_jawab'       => '',
            'usia_penanggung_jawab'            => '',
            'no_identitas_penanggung_jawab'    => '',
            'jenis_identitas_penanggung_jawab' => 'KTP',
            'alamat_penanggung_jawab'          => '',
            'kelurahan_penanggung_jawab'       => '',
            'kecamatan_penanggung_jawab'       => '',
            'kabupaten_penanggung_jawab'       => '',
            'provinsi_penanggung_jawab'        => '',
            'hubungan_pasien'                  => 'Diri Sendiri',
            'hubungan_pasien_lainnya'          => '',
            'tindakan_radiologi'               => '',
            'nama_pasien_terhadap'             => '',
            'jk_pasien_terhadap'               => 'L',
            'tmp_lahir_pasien_terhadap'        => '',
            'tgl_lahir_pasien_terhadap'        => '',
            'usia_pasien_terhadap'             => '',
            'alamat_pasien_terhadap'           => '',
            'kelurahan_pasien_terhadap'        => '',
            'kecamatan_pasien_terhadap'        => '',
            'kabupaten_pasien_terhadap'        => '',
            'provinsi_pasien_terhadap'         => '',
            'kota_surat'                       => 'Banjarmasin',
            'tanggal_surat'                    => date('Y-m-d'),
            'jam_surat'                        => date('H:i'),
            'nama_yang_menyatakan'             => '',
            'kd_dokter'                        => '',
            'nama_dokter'                      => '',
            'nama_saksi_1'                     => '',
            'nama_saksi_2'                     => ''
        ];

        $data = $defaultData;
        if ($id) {
            $row = $this->db('lampiran_persetujuan_tindakan_radiologi')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($defaultData, $row);
            }
        } elseif (!empty($_GET['no_rawat'])) {
            $no_rawat = $_GET['no_rawat'];
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->leftJoin('kelurahan', 'kelurahan.kd_kel = pasien.kd_kel')
                ->leftJoin('kecamatan', 'kecamatan.kd_kec = pasien.kd_kec')
                ->leftJoin('kabupaten', 'kabupaten.kd_kab = pasien.kd_kab')
                ->leftJoin('propinsi', 'propinsi.kd_prop = pasien.kd_prop')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.no_ktp, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir, pasien.alamat, pasien.umur, kelurahan.nm_kel, kecamatan.nm_kec, kabupaten.nm_kab, propinsi.nm_prop')
                ->oneArray();
            if ($reg) {
                $data['no_rawat'] = $reg['no_rawat'] ?? '';
                $data['no_rkm_medis'] = $reg['no_rkm_medis'] ?? '';
                $data['nama_pasien_terhadap'] = $reg['nm_pasien'] ?? '';
                $data['jk_pasien_terhadap'] = $reg['jk'] ?? 'L';
                $data['tmp_lahir_pasien_terhadap'] = $reg['tmp_lahir'] ?? '';
                $data['tgl_lahir_pasien_terhadap'] = $reg['tgl_lahir'] ?? '';
                $data['usia_pasien_terhadap'] = $reg['umur'] ?? '';
                $data['alamat_pasien_terhadap'] = $reg['alamat'] ?? '';
                $data['kelurahan_pasien_terhadap'] = $reg['nm_kel'] ?? '';
                $data['kecamatan_pasien_terhadap'] = $reg['nm_kec'] ?? '';
                $data['kabupaten_pasien_terhadap'] = $reg['nm_kab'] ?? '';
                $data['provinsi_pasien_terhadap'] = $reg['nm_prop'] ?? '';

                // Default pembuat pernyataan diri sendiri
                $data['nama_penanggung_jawab'] = $reg['nm_pasien'] ?? '';
                $data['jk_penanggung_jawab'] = $reg['jk'] ?? 'L';
                $data['tmp_lahir_penanggung_jawab'] = $reg['tmp_lahir'] ?? '';
                $data['tgl_lahir_penanggung_jawab'] = $reg['tgl_lahir'] ?? '';
                $data['usia_penanggung_jawab'] = $reg['umur'] ?? '';
                $data['no_identitas_penanggung_jawab'] = $reg['no_ktp'] ?? '';
                $data['alamat_penanggung_jawab'] = $reg['alamat'] ?? '';
                $data['kelurahan_penanggung_jawab'] = $reg['nm_kel'] ?? '';
                $data['kecamatan_penanggung_jawab'] = $reg['nm_kec'] ?? '';
                $data['kabupaten_penanggung_jawab'] = $reg['nm_kab'] ?? '';
                $data['provinsi_penanggung_jawab'] = $reg['nm_prop'] ?? '';
                $data['hubungan_pasien'] = 'Diri Sendiri';
                $data['nama_yang_menyatakan'] = $reg['nm_pasien'] ?? '';
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('lampiran_persetujuan_tindakan_radiologi/form.html', [
            'data' => $data,
            'id' => $id,
            'dokter' => $dokter
        ]);
    }

    public function getPersetujuanradiologihapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('lampiran_persetujuan_tindakan_radiologi')->where('id', $id)->delete();
            $this->notify('success', 'Data persetujuan tindakan radiologi berhasil dihapus');
        }
        redirect(url([ADMIN, 'update_bmt', 'persetujuanradiologimanage']));
    }

    public function getPersetujuanradiologicetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('lampiran_persetujuan_tindakan_radiologi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = lampiran_persetujuan_tindakan_radiologi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(lampiran_persetujuan_tindakan_radiologi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('lampiran_persetujuan_tindakan_radiologi.id', $id)
            ->select('lampiran_persetujuan_tindakan_radiologi.*, COALESCE(lampiran_persetujuan_tindakan_radiologi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.nm_pasien as nama_pasien, pasien.jk as jk_pasien_db, pasien.tgl_lahir as tgl_lahir_pasien_db')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rm = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = $rm[$i] ?? '&nbsp;';
        }
        $data['no_rkm_medis'] = $rm;

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tanggal Lahir Pasien
        $tglPasien = !empty($data['tgl_lahir_pasien_terhadap']) ? $data['tgl_lahir_pasien_terhadap'] : ($data['tgl_lahir_pasien_db'] ?? '');
        if (!empty($tglPasien) && $tglPasien != '0000-00-00') {
            $tgl = explode('-', $tglPasien);
            if (count($tgl) == 3) {
                $data['tgl_lahir_pasien_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_pasien_indo'] = $tglPasien;
            }
        } else {
            $data['tgl_lahir_pasien_indo'] = '-';
        }

        // Tanggal Lahir Penanggung Jawab
        if (!empty($data['tgl_lahir_penanggung_jawab']) && $data['tgl_lahir_penanggung_jawab'] != '0000-00-00') {
            $tglPj = explode('-', $data['tgl_lahir_penanggung_jawab']);
            if (count($tglPj) == 3) {
                $data['tgl_lahir_pj_indo'] = (int)$tglPj[2] . ' ' . $bulan[(int)$tglPj[1]] . ' ' . $tglPj[0];
            } else {
                $data['tgl_lahir_pj_indo'] = $data['tgl_lahir_penanggung_jawab'];
            }
        } else {
            $data['tgl_lahir_pj_indo'] = '-';
        }

        // Tanggal Surat
        if (!empty($data['tanggal_surat']) && $data['tanggal_surat'] != '0000-00-00') {
            $tglS = explode('-', $data['tanggal_surat']);
            if (count($tglS) == 3) {
                $data['tgl_surat_indo'] = (int)$tglS[2] . ' ' . $bulan[(int)$tglS[1]] . ' ' . $tglS[0];
                $data['tgl_surat_hari'] = (int)$tglS[2];
                $data['tgl_surat_bulan'] = $bulan[(int)$tglS[1]];
                $data['tgl_surat_tahun'] = $tglS[0];
            } else {
                $data['tgl_surat_indo'] = $data['tanggal_surat'];
                $data['tgl_surat_hari'] = date('d');
                $data['tgl_surat_bulan'] = $bulan[(int)date('m')];
                $data['tgl_surat_tahun'] = date('Y');
            }
        } else {
            $data['tgl_surat_indo'] = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');
            $data['tgl_surat_hari'] = date('d');
            $data['tgl_surat_bulan'] = $bulan[(int)date('m')];
            $data['tgl_surat_tahun'] = date('Y');
        }

        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ]);
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_persetujuan_tindakan_radiologi/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                $filename = 'Persetujuan_Tindakan_Radiologi_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
                if (ob_get_length()) {
                    ob_clean();
                }
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to normal HTML view
            }
        }

        $data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_persetujuan_tindakan_radiologi/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();
    }

    public function anyEwsmanage()
    {
        $stmt = $this->db()->pdo()->query(
            "SELECT e.*, 
                    COALESCE(NULLIF(e.nama_pasien, ''), p.nm_pasien, '') AS nm_pasien, 
                    COALESCE(NULLIF(e.jk, ''), p.jk, '') AS jk_val, 
                    p.umur, 
                    COALESCE(NULLIF(e.tgl_lahir, ''), p.tgl_lahir, '') AS tgl_lahir_val
             FROM lampiran_ews e
             LEFT JOIN reg_periksa rp ON e.no_rawat = rp.no_rawat
             LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis OR e.no_rkm_medis = p.no_rkm_medis
             ORDER BY e.id DESC"
        );
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        $data = [];
        foreach ($rows as $k => $r) {
            $r['nomor'] = $k + 1;
            $r['nama_pasien'] = !empty($r['nama_pasien']) ? $r['nama_pasien'] : (!empty($r['nm_pasien']) ? $r['nm_pasien'] : '-');
            $jk = !empty($r['jk']) ? $r['jk'] : ($r['jk_val'] ?? '');
            $r['jk_pasien'] = ($jk == 'L') ? 'Laki-laki' : (($jk == 'P') ? 'Perempuan' : '-');
            $r['umur_pasien'] = $r['umur'] ?? '-';

            // Decode Dewasa
            $dewasa = json_decode($r['detail_dewasa'] ?? '[]', true) ?: [];
            $activeDewasa = array_values(array_filter($dewasa, function($item) {
                return !empty($item['tgl']) || !empty($item['jam']) || !empty($item['respirasi']) || !empty($item['tensi']) || !empty($item['nadi']) || (isset($item['total_skor']) && $item['total_skor'] !== '' && $item['total_skor'] !== null);
            }));
            $r['jml_dewasa'] = count($activeDewasa);
            if (!empty($activeDewasa)) {
                $lastD = end($activeDewasa);
                $skorD = $lastD['total_skor'] ?? 0;
                $isBlueD = !empty($lastD['is_blue']);
                $r['skor_terakhir_dewasa'] = $skorD . ($isBlueD ? ' (BLUE)' : '');
                if ($isBlueD || $skorD >= 7) {
                    $r['label_class_dewasa'] = 'label-danger';
                    $r['status_resiko_dewasa'] = 'Resiko Tinggi';
                } elseif ($skorD >= 5) {
                    $r['label_class_dewasa'] = 'label-warning';
                    $r['status_resiko_dewasa'] = 'Resiko Sedang';
                } elseif ($skorD >= 1) {
                    $r['label_class_dewasa'] = 'label-info';
                    $r['status_resiko_dewasa'] = 'Resiko Ringan';
                } else {
                    $r['label_class_dewasa'] = 'label-success';
                    $r['status_resiko_dewasa'] = 'Normal';
                }
            } else {
                $r['skor_terakhir_dewasa'] = '-';
                $r['label_class_dewasa'] = 'label-default';
                $r['status_resiko_dewasa'] = '-';
            }

            // Decode Anak
            $anak = json_decode($r['detail_anak'] ?? '[]', true) ?: [];
            $activeAnak = array_values(array_filter($anak, function($item) {
                return !empty($item['tgl']) || !empty($item['jam']) || (isset($item['keadaan_umum']) && $item['keadaan_umum'] !== '') || (isset($item['total_skor']) && $item['total_skor'] !== '' && $item['total_skor'] !== null);
            }));
            $r['jml_anak'] = count($activeAnak);
            if (!empty($activeAnak)) {
                $lastA = end($activeAnak);
                $skorA = $lastA['total_skor'] ?? 0;
                $r['skor_terakhir_anak'] = $skorA;
                if ($skorA >= 5) {
                    $r['label_class_anak'] = 'label-danger';
                    $r['status_resiko_anak'] = 'Resiko Tinggi';
                } elseif ($skorA >= 3) {
                    $r['label_class_anak'] = 'label-warning';
                    $r['status_resiko_anak'] = 'Resiko Sedang';
                } else {
                    $r['label_class_anak'] = 'label-success';
                    $r['status_resiko_anak'] = 'Stabil (Skor 0-2)';
                }
            } else {
                $r['skor_terakhir_anak'] = '-';
                $r['label_class_anak'] = 'label-default';
                $r['status_resiko_anak'] = '-';
            }

            $data[] = $r;
        }

        return $this->draw('lampiran_ews/manage.html', ['data' => $data]);
    }

    public function anyEwsform($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);

        // AJAX Pasien Lookup
        if (isset($_GET['ajax_patient']) || isset($_POST['ajax_patient'])) {
            header('Content-Type: application/json');
            $no_rawat = trim($_GET['no_rawat'] ?? ($_POST['no_rawat'] ?? ''));
            $res = ['success' => false];
            if (!empty($no_rawat)) {
                $stmt = $this->db()->pdo()->prepare(
                    "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.jk, p.tgl_lahir, p.umur, b.nm_bangsal, k.kd_kamar
                     FROM reg_periksa rp
                     JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
                     LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat AND ki.stts_pulang = '-'
                     LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                     LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                     WHERE rp.no_rawat = ? OR rp.no_rkm_medis = ?
                     LIMIT 1"
                );
                $stmt->execute([$no_rawat, $no_rawat]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($row) {
                    $res = [
                        'success' => true,
                        'no_rawat' => $row['no_rawat'],
                        'no_rkm_medis' => $row['no_rkm_medis'],
                        'nm_pasien' => $row['nm_pasien'],
                        'jk' => ($row['jk'] == 'L') ? 'Laki-laki' : 'Perempuan',
                        'jk_code' => $row['jk'],
                        'tgl_lahir' => $row['tgl_lahir'],
                        'umur' => $row['umur'],
                        'kamar' => $row['nm_bangsal'] ? $row['nm_bangsal'] . ' (' . $row['kd_kamar'] . ')' : ''
                    ];
                }
            }
            echo json_encode($res);
            exit();
        }

        // POST Simpan
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saveData = [
                'no_rawat' => trim($_POST['no_rawat'] ?? ''),
                'no_rkm_medis' => trim($_POST['no_rkm_medis'] ?? ''),
                'nama_pasien' => trim($_POST['nama_pasien'] ?? ''),
                'tgl_lahir' => trim($_POST['tgl_lahir'] ?? ''),
                'jk' => trim($_POST['jk'] ?? ''),
                'ruangan' => trim($_POST['ruangan'] ?? ''),
                'tanggal' => trim($_POST['tanggal'] ?? date('Y-m-d')),
                'detail_dewasa' => trim($_POST['detail_dewasa'] ?? '[]'),
                'detail_anak' => trim($_POST['detail_anak'] ?? '[]'),
                'catatan' => trim($_POST['catatan'] ?? ''),
                'kd_petugas' => trim($_POST['kd_petugas'] ?? ''),
                'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($id) {
                $this->db('lampiran_ews')->where('id', $id)->save($saveData);
                $this->notify('success', 'Formulir EWS berhasil diperbarui.');
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $this->db('lampiran_ews')->save($saveData);
                $this->notify('success', 'Formulir EWS berhasil ditambahkan.');
            }

            redirect(url([ADMIN, 'update_bmt', 'ewsmanage']));
            exit();
        }

        // Default Data
        $data = [
            'id' => '',
            'no_rawat' => '',
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'nama_pasien' => '',
            'jk_pasien' => '',
            'jk' => '',
            'tgl_lahir' => '',
            'tgl_lahir_pasien' => '',
            'umur_pasien' => '',
            'ruangan' => '',
            'tanggal' => date('Y-m-d'),
            'catatan' => '',
            'kd_petugas' => '',
            'nama_petugas' => '',
            'detail_dewasa_json' => '[]',
            'detail_anak_json' => '[]'
        ];

        if ($id) {
            $stmt = $this->db()->pdo()->prepare(
                "SELECT e.*, 
                        COALESCE(NULLIF(e.nama_pasien, ''), p.nm_pasien, '') AS nm_pasien, 
                        COALESCE(NULLIF(e.jk, ''), p.jk, '') AS jk, 
                        p.umur, 
                        COALESCE(NULLIF(e.tgl_lahir, ''), p.tgl_lahir, '') AS tgl_lahir
                 FROM lampiran_ews e
                 LEFT JOIN reg_periksa rp ON e.no_rawat = rp.no_rawat
                 LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis OR e.no_rkm_medis = p.no_rkm_medis
                 WHERE e.id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $data = array_merge($data, $row);
                $data['nm_pasien'] = $row['nm_pasien'] ?? '';
                $data['nama_pasien'] = $row['nm_pasien'] ?? '';
                $data['jk_pasien'] = ($row['jk'] == 'L') ? 'Laki-laki' : (($row['jk'] == 'P') ? 'Perempuan' : '');
                $data['jk'] = $row['jk'] ?? '';
                $data['tgl_lahir'] = $row['tgl_lahir'] ?? '';
                $data['tgl_lahir_pasien'] = $row['tgl_lahir'] ?? '';
                $data['umur_pasien'] = $row['umur'] ?? '';
                $data['detail_dewasa_json'] = !empty($row['detail_dewasa']) ? $row['detail_dewasa'] : '[]';
                $data['detail_anak_json'] = !empty($row['detail_anak']) ? $row['detail_anak'] : '[]';
            }
        }

        $kop_img = url('assets/img/KOP-SURAT.png');
        return $this->draw('lampiran_ews/form.html', [
            'data' => $data,
            'id' => $id,
            'kop_img' => $kop_img,
            'colsLeft' => range(0, 11),
            'colsRight' => range(12, 23),
            'colsH2' => range(0, 15)
        ]);
    }

    public function getEwshapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('lampiran_ews')->where('id', $id)->delete();
            $this->notify('success', 'Formulir EWS berhasil dihapus.');
        }
        redirect(url([ADMIN, 'update_bmt', 'ewsmanage']));
        exit();
    }

    public function getEwscetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        $stmt = $this->db()->pdo()->prepare(
            "SELECT e.*, 
                    COALESCE(NULLIF(e.nama_pasien, ''), p.nm_pasien, '') AS nm_pasien, 
                    COALESCE(NULLIF(e.jk, ''), p.jk, '') AS jk, 
                    p.umur, 
                    COALESCE(NULLIF(e.tgl_lahir, ''), p.tgl_lahir, '') AS tgl_lahir
             FROM lampiran_ews e
             LEFT JOIN reg_periksa rp ON e.no_rawat = rp.no_rawat
             LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis OR e.no_rkm_medis = p.no_rkm_medis
             WHERE e.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            echo "Data Formulir EWS tidak ditemukan.";
            exit();
        }

        $data = $row;
        $data['jk_l'] = ($row['jk'] == 'L');
        $data['jk_p'] = ($row['jk'] == 'P');
        if (!empty($row['tgl_lahir']) && $row['tgl_lahir'] != '0000-00-00') {
            $tglArr = explode('-', $row['tgl_lahir']);
            $data['tgl_lahir_formatted'] = (count($tglArr) == 3) ? ((int)$tglArr[2] . '/' . (int)$tglArr[1] . '/' . $tglArr[0]) : $row['tgl_lahir'];
        } else {
            $data['tgl_lahir_formatted'] = '-';
        }

        // Decode 24 Kolom Dewasa (12 Kiri, 1 Kolom Skor/BLUE Tengah, 12 Kanan)
        $rawDewasa = json_decode($row['detail_dewasa'] ?? '[]', true) ?: [];
        $dewasaColsLeft = [];
        $dewasaColsRight = [];
        for ($i = 0; $i < 24; $i++) {
            if (isset($rawDewasa[$i])) {
                $c = $rawDewasa[$i];
                $resp = str_replace(['-', '.'], ['_', '_'], $c['respirasi'] ?? '');
                $sat = str_replace(['-', '.'], ['_', '_'], $c['saturasi'] ?? '');
                $sup = $c['suplemen_o2'] ?? '';
                $tens = str_replace(['-', '.'], ['_', '_'], $c['tensi'] ?? '');
                $nad = str_replace(['-', '.'], ['_', '_'], $c['nadi'] ?? '');
                $kes = $c['kesadaran'] ?? '';
                $suh = str_replace(['-', '.'], ['_', '_'], $c['suhu'] ?? '');

                $isBlue = ($resp === 'le_5' || $resp === 'le_8' || $resp === 'ge_35' || $tens === 'le_70' || $nad === 'ge_140' || $nad === 'le_40' || $kes === 'unrespon' || !empty($c['is_blue']));
                $totSkor = isset($c['total_skor']) ? (int)$c['total_skor'] : 0;
                if ($isBlue || $totSkor >= 7) {
                    $katSingkat = 'Tinggi';
                    $kat = 'Resiko Tinggi (Kriteria BLUE)';
                    $adv = 'Resusitasi dan monitoring secara kontinyu oleh dokter jaga dan perawat senior. Aktivasi code blue kegawatan medis (666) respon Tim Medis Emergency (TME) segera, maksimal 10 menit. Informasikan dan konsultasikan ke DPJP.';
                } elseif ($totSkor >= 5) {
                    $katSingkat = 'Sedang';
                    $kat = 'Resiko Sedang';
                    $adv = 'Assessment segera oleh dokter jaga (respon segera, maks 5 menit), konsultasi DPJP dan spesialis terkait, eskalasi perawatan dan monitoring tiap jam, pertimbangkan perawatan dengan monitoring yang sesuai (HCU).';
                } elseif ($totSkor >= 1) {
                    $katSingkat = 'Ringan';
                    $kat = 'Resiko Ringan';
                    $adv = 'Assessment segera oleh perawat senior, respon segera, maks 5 menit, eskalasi perawatan dan frekuensi monitoring per 4-6 jam. Jika diperlukan assessment oleh dokter jaga bangsal.';
                } else {
                    $katSingkat = 'Stabil';
                    $kat = 'Normal / Stabil';
                    $adv = 'Pasien dalam keadaan stabil. Lakukan monitoring tanda vital berkala dan evaluasi rutin setiap shift (per 8 jam) oleh perawat bangsal.';
                }

                $blueText = '<span style="font-size:4.5pt; font-weight:bold;">BLUE</span>';

                $colData = [
                    'tgl' => $c['tgl'] ?? '',
                    'jam' => $c['jam'] ?? '',
                    'total_skor' => isset($c['total_skor']) ? $c['total_skor'] : '',
                    'kategori_singkat' => $katSingkat,
                    'kategori' => $kat,
                    'advis' => $adv,
                    'gds' => $c['gds'] ?? '',
                    'nyeri' => $c['nyeri'] ?? '',
                    'urin' => $c['urin'] ?? '',
                    'row_le_5' => ($resp === 'le_5' || $resp === 'le_8') ? $blueText : '',
                    'row_6_8' => ($resp === '6_8') ? '3' : '',
                    'row_9_11' => ($resp === '9_11') ? '1' : '',
                    'row_12_20' => ($resp === '12_20') ? '0' : '',
                    'row_21_24' => ($resp === '21_24') ? '2' : '',
                    'row_25_34' => ($resp === '25_34') ? '3' : '',
                    'row_ge_35' => ($resp === 'ge_35') ? $blueText : '',
                    'row_ge_96' => ($sat === 'ge_96') ? '0' : '',
                    'row_94_95' => ($sat === '94_95') ? '1' : '',
                    'row_92_93' => ($sat === '92_93') ? '2' : '',
                    'row_le_91' => ($sat === 'le_91') ? '3' : '',
                    'row_suplemen_ya' => ($sup === 'ya') ? '2' : '',
                    'row_ge_220' => ($tens === 'ge_220') ? '3' : '',
                    'row_181_220' => ($tens === '181_220') ? '1' : '',
                    'row_111_180' => ($tens === '111_180') ? '0' : '',
                    'row_101_110' => ($tens === '101_110') ? '1' : '',
                    'row_91_100' => ($tens === '91_100') ? '2' : '',
                    'row_71_90' => ($tens === '71_90') ? '3' : '',
                    'row_le_70' => ($tens === 'le_70') ? $blueText : '',
                    'row_ge_140' => ($nad === 'ge_140') ? $blueText : '',
                    'row_131_140' => ($nad === '131_140') ? '3' : '',
                    'row_111_130' => ($nad === '111_130') ? '2' : '',
                    'row_91_110' => ($nad === '91_110') ? '1' : '',
                    'row_51_90' => ($nad === '51_90') ? '0' : '',
                    'row_41_50' => ($nad === '41_50') ? '1' : '',
                    'row_le_40' => ($nad === 'le_40') ? $blueText : '',
                    'row_sadar' => ($kes === 'sadar') ? '0' : '',
                    'row_nyeri_verbal' => ($kes === 'nyeri_verbal') ? '3' : '',
                    'row_unrespon' => ($kes === 'unrespon') ? $blueText : '',
                    'row_le_35' => ($suh === 'le_35') ? '3' : '',
                    'row_35_1_36' => ($suh === '35_1_36') ? '1' : '',
                    'row_36_1_38' => ($suh === '36_1_38') ? '0' : '',
                    'row_38_1_39' => ($suh === '38_1_39') ? '2' : '',
                    'row_ge_39' => ($suh === 'ge_39') ? '2' : ''
                ];
            } else {
                $colData = [
                    'tgl' => '', 'jam' => '', 'total_skor' => '', 'kategori_singkat' => '', 'kategori' => '', 'advis' => '',
                    'gds' => '', 'nyeri' => '', 'urin' => '',
                    'row_le_5' => '', 'row_6_8' => '', 'row_9_11' => '', 'row_12_20' => '', 'row_21_24' => '', 'row_25_34' => '', 'row_ge_35' => '',
                    'row_ge_96' => '', 'row_94_95' => '', 'row_92_93' => '', 'row_le_91' => '', 'row_suplemen_ya' => '',
                    'row_ge_220' => '', 'row_181_220' => '', 'row_111_180' => '', 'row_101_110' => '', 'row_91_100' => '', 'row_71_90' => '', 'row_le_70' => '',
                    'row_ge_140' => '', 'row_131_140' => '', 'row_111_130' => '', 'row_91_110' => '', 'row_51_90' => '', 'row_41_50' => '', 'row_le_40' => '',
                    'row_sadar' => '', 'row_nyeri_verbal' => '', 'row_unrespon' => '',
                    'row_le_35' => '', 'row_35_1_36' => '', 'row_36_1_38' => '', 'row_38_1_39' => '', 'row_ge_39' => ''
                ];
            }

            if ($i < 12) {
                $dewasaColsLeft[] = $colData;
            } else {
                $dewasaColsRight[] = $colData;
            }
        }

        // Kesimpulan Observasi Terakhir Dewasa
        $data['skor_terakhir_dewasa'] = '';
        $data['kategori_dewasa'] = '';
        $data['advis_dewasa'] = '';
        $activeDewasa = array_values(array_filter($rawDewasa, function($item) {
            return !empty($item['tgl']) || !empty($item['jam']) || !empty($item['respirasi']) || !empty($item['tensi']) || !empty($item['nadi']) || (isset($item['total_skor']) && $item['total_skor'] !== '' && $item['total_skor'] !== null);
        }));
        if (!empty($activeDewasa)) {
            $lastD = end($activeDewasa);
            $isBlueD = ($lastD['respirasi'] ?? '') === 'le_5' || ($lastD['respirasi'] ?? '') === 'le_8' || ($lastD['respirasi'] ?? '') === 'ge_35' || ($lastD['tensi'] ?? '') === 'le_70' || ($lastD['nadi'] ?? '') === 'ge_140' || ($lastD['nadi'] ?? '') === 'le_40' || ($lastD['kesadaran'] ?? '') === 'unrespon' || !empty($lastD['is_blue']);
            $skorD = isset($lastD['total_skor']) ? (int)$lastD['total_skor'] : 0;
            $data['skor_terakhir_dewasa'] = $skorD . ($isBlueD ? ' (BLUE)' : '');
            if ($isBlueD || $skorD >= 7) {
                $data['kategori_dewasa'] = 'Resiko Tinggi / Kriteria BLUE';
                $data['advis_dewasa'] = 'Resusitasi dan monitoring secara kontinyu oleh dokter jaga dan perawat senior. Aktivasi code blue kegawatan medis (666) respon Tim Medis Emergency (TME) segera, maksimal 10 menit. Informasikan dan konsultasikan ke DPJP.';
            } elseif ($skorD >= 5) {
                $data['kategori_dewasa'] = 'Resiko Sedang';
                $data['advis_dewasa'] = 'Assessment segera oleh dokter jaga (respon segera, maks 5 menit), konsultasi DPJP dan spesialis terkait, eskalasi perawatan dan monitoring tiap jam, pertimbangkan perawatan dengan monitoring yang sesuai (HCU).';
            } elseif ($skorD >= 1) {
                $data['kategori_dewasa'] = 'Resiko Ringan';
                $data['advis_dewasa'] = 'Assessment segera oleh perawat senior, respon segera, maks 5 menit, eskalasi perawatan dan frekuensi monitoring per 4-6 jam. Jika diperlukan assessment oleh dokter jaga bangsal.';
            } else {
                $data['kategori_dewasa'] = 'Normal / Stabil';
                $data['advis_dewasa'] = 'Pasien dalam keadaan stabil. Lakukan monitoring tanda vital berkala dan evaluasi rutin setiap shift (per 8 jam) oleh perawat bangsal.';
            }
        }

        // =====================================================================
        // HALAMAN 2: GAMBAR 2 (16 KOLOM OBSERVASI)
        // =====================================================================
        $rawH2 = json_decode($row['detail_anak'] ?? '[]', true) ?: [];
        $h2Cols = [];
        $mark = '●';
        for ($i = 0; $i < 16; $i++) {
            if (isset($rawH2[$i])) {
                $c = $rawH2[$i];
                $ku = isset($c['keadaan_umum']) && $c['keadaan_umum'] !== '' ? (string)$c['keadaan_umum'] : '';
                $kv = isset($c['kardiovaskular']) && $c['kardiovaskular'] !== '' ? (string)$c['kardiovaskular'] : '';
                $resp = isset($c['respirasi']) && $c['respirasi'] !== '' ? (string)$c['respirasi'] : '';
                $totH2 = isset($c['total_skor']) && $c['total_skor'] !== '' ? $c['total_skor'] : '';

                $h2Cols[] = [
                    'tgl' => $c['tgl'] ?? '',
                    'jam' => $c['jam'] ?? '',
                    'total_skor' => $totH2,
                    'row_ku_0' => ($ku === '0') ? $mark : '',
                    'row_ku_1' => ($ku === '1') ? $mark : '',
                    'row_ku_2' => ($ku === '2') ? $mark : '',
                    'row_ku_3' => ($ku === '3') ? $mark : '',
                    'row_kv_0' => ($kv === '0') ? $mark : '',
                    'row_kv_1' => ($kv === '1') ? $mark : '',
                    'row_kv_2' => ($kv === '2') ? $mark : '',
                    'row_kv_3' => ($kv === '3') ? $mark : '',
                    'row_resp_0' => ($resp === '0') ? $mark : '',
                    'row_resp_1' => ($resp === '1') ? $mark : '',
                    'row_resp_2' => ($resp === '2') ? $mark : '',
                    'row_resp_3' => ($resp === '3') ? $mark : ''
                ];
            } else {
                $h2Cols[] = [
                    'tgl' => '', 'jam' => '', 'total_skor' => '',
                    'row_ku_0' => '', 'row_ku_1' => '', 'row_ku_2' => '', 'row_ku_3' => '',
                    'row_kv_0' => '', 'row_kv_1' => '', 'row_kv_2' => '', 'row_kv_3' => '',
                    'row_resp_0' => '', 'row_resp_1' => '', 'row_resp_2' => '', 'row_resp_3' => ''
                ];
            }
        }

        if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
            require_once BASE_DIR . '/vendor/autoload.php';
        }

        // Unduh PDF Gabungan (Page 1: Dewasa, Page 2: Gambar 2)
        if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && class_exists('\Mpdf\Mpdf')) {
            try {
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 5,
                    'margin_right' => 5,
                    'margin_top' => 5,
                    'margin_bottom' => 5
                ]);
                $kop_img = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_ews/cetak.html', [
                    'data' => $data,
                    'kop_img' => $kop_img,
                    'dewasaColsLeft' => $dewasaColsLeft,
                    'dewasaColsRight' => $dewasaColsRight,
                    'h2Cols' => $h2Cols,
                    'is_mpdf' => true
                ]);
                $mpdf->WriteHTML($html);
                $filename = 'EWS_Ranap_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
                if (ob_get_length()) {
                    ob_clean();
                }
                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Throwable $e) {
                // fallback to html view
            }
        }

        // Tampilan Web / HTML Print
        $kop_img = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_ews/cetak.html', [
            'data' => $data,
            'kop_img' => $kop_img,
            'dewasaColsLeft' => $dewasaColsLeft,
            'dewasaColsRight' => $dewasaColsRight,
            'h2Cols' => $h2Cols,
            'is_mpdf' => false
        ]);
        exit();
    }
}



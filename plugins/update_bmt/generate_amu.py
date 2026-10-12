import os

ADMIN_PHP = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php"

# 1. Update Admin.php
with open(ADMIN_PHP, "r") as f:
    content = f.read()

# Add navigation
nav_search = "'LAMPIRAN - SURAT PERNYATAAN PULANG APS' => 'pulangapsmanage',"
nav_replace = nav_search + "\n                'LAMPIRAN - ASESMEN MEDIS UMUM IRNA' => 'amumanage',"
if "'LAMPIRAN - ASESMEN MEDIS UMUM IRNA' => 'amumanage'," not in content:
    content = content.replace(nav_search, nav_replace)

methods = """
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
            ->select('asesmen_medis_umum_irna.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
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
            $data = $this->db('asesmen_medis_umum_irna')->where('id', $id)->oneArray();
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
                'tanggal_ttd' => $post['tanggal_ttd'] ?? '',
                'jam_ttd' => $post['jam_ttd'] ?? '',
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
            ->select('asesmen_medis_umum_irna.*, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, dokter.nm_dokter')
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
        
        echo $this->draw('asesmen_medis_umum_irna/cetak.html', ['data' => $data]);
        exit();
    }
"""
if "anyAmumanage" not in content:
    content = content.replace("class Admin extends AdminModule\n{", "class Admin extends AdminModule\n{" + methods)

with open(ADMIN_PHP, "w") as f:
    f.write(content)

print("Admin.php updated")

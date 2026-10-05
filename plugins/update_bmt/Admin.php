<?php
namespace Plugins\Update_bmt;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Asesmen Gigi UGD' => 'manage',
        ];
    }

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
    public function getPatient_info()
    {
        $no_rawat = $_GET['no_rawat'] ?? '';
        $result = ['success' => false];

        if (!empty($no_rawat)) {
            $reg = $this->db('reg_periksa')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $no_rawat)
                ->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                ->oneArray();

            if ($reg) {
                $result = [
                    'success'       => true,
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
}

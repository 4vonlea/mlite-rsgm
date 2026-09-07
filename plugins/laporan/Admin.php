<?php

namespace Plugins\Laporan;

use Systems\AdminModule;
use Systems\Lib\QueryWrapper;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Laporan TB' => 'laporantb',
            'Laporan SEP BPJS' => 'laporansep',
            'Laporan Antrian Online' => 'laporanantrian',
            '10 Besar Penyakit Ralan' => 'laporanpenyakitralan',
            '10 Besar Penyakit Ranap' => 'laporanpenyakitranap',
            'Laporan Radiologi PACS' => 'laporanradiologipacs',
            'Statistik Kunjungan' => 'laporankunjungan'
        ];
    }

    public function getManage()
    {
        $sub_modules = [
            ['name' => 'Laporan TB', 'url' => url([ADMIN, 'laporan', 'laporantb']), 'icon' => 'fa fa-file-text-o', 'desc' => 'Laporan data tuberkulosis'],
            ['name' => 'Laporan SEP BPJS', 'url' => url([ADMIN, 'laporan', 'laporansep']), 'icon' => 'fa fa-file-text-o', 'desc' => 'Laporan SEP BPJS'],
            ['name' => 'Laporan Antrian Online', 'url' => url([ADMIN, 'laporan', 'laporanantrian']), 'icon' => 'fa fa-file-text-o', 'desc' => 'Laporan antrian online'],
            ['name' => '10 Besar Penyakit Ralan', 'url' => url([ADMIN, 'laporan', 'laporanpenyakitralan']), 'icon' => 'fa fa-bar-chart', 'desc' => 'Laporan 10 besar penyakit rawat jalan'],
            ['name' => '10 Besar Penyakit Ranap', 'url' => url([ADMIN, 'laporan', 'laporanpenyakitranap']), 'icon' => 'fa fa-bar-chart', 'desc' => 'Laporan 10 besar penyakit rawat inap'],
            ['name' => 'Laporan Radiologi PACS', 'url' => url([ADMIN, 'laporan', 'laporanradiologipacs']), 'icon' => 'fa fa-picture-o', 'desc' => 'Laporan status pengiriman rontgen ke Mini PACS'],
            ['name' => 'Statistik Kunjungan', 'url' => url([ADMIN, 'laporan', 'laporankunjungan']), 'icon' => 'fa fa-chart-line', 'desc' => 'Laporan statistik kunjungan pasien lengkap']
        ];

        return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules)]);
    }

    public function anyLaporanTb()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y') . "-01-01");
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y') . "-12-31");

        $query = $this->db('data_tb')
            ->join('reg_periksa', 'reg_periksa.no_rawat = data_tb.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select([
                'data_tb.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'reg_periksa.tgl_registrasi',
                'data_tb.tipe_diagnosis',
                'data_tb.klasifikasi_lokasi_anatomi',
                'data_tb.hasil_akhir_pengobatan',
                'data_tb.tanggal_mulai_pengobatan'
            ])
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->toArray();

        // Handle Excel export
        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            return $this->exportToExcel($query, 'Laporan_TB_' . $tgl_awal . '_' . $tgl_akhir);
        }

        return $this->draw('laporan_tb.html', ['laporan' => $query, 'tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
    }

    public function anyLaporanSep()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y') . "-01-01");
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y') . "-12-31");

        $query = $this->db('bridging_sep')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bridging_sep.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select([
                'bridging_sep.no_sep',
                'bridging_sep.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'bridging_sep.no_kartu',
                'bridging_sep.tglsep',
                'bridging_sep.nmpolitujuan as poli',
                'bridging_sep.nmdiagnosaawal as diagnosa',
                'bridging_sep.jnspelayanan'
            ])
            ->where('bridging_sep.tglsep', '>=', $tgl_awal)
            ->where('bridging_sep.tglsep', '<=', $tgl_akhir)
            ->toArray();

        // Handle Excel export
        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            return $this->exportSepToExcel($query, 'Laporan_SEP_' . $tgl_awal . '_' . $tgl_akhir);
        }

        return $this->draw('laporan_sep.html', ['laporan' => $query, 'tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
    }

    public function anyLaporanAntrian()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y') . "-01-01");
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y') . "-12-31");

        $query = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'poliklinik.nm_poli',
                'dokter.nm_dokter',
                'reg_periksa.status_bayar',
                'reg_periksa.stts'
            ])
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->where('reg_periksa.status_lanjut', '=', 'Ralan')
            ->toArray();

        return $this->draw('laporan_antrian.html', ['laporan' => $query, 'tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
    }

    public function anyLaporanPenyakitRalan()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));

        $query = $this->db('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select([
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'count(diagnosa_pasien.kd_penyakit) as jumlah'
            ])
            ->where('reg_periksa.status_lanjut', 'Ralan')
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->group('diagnosa_pasien.kd_penyakit')
            ->desc('jumlah')
            ->limit(10)
            ->toArray();

        // Handle Excel export
        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            return $this->exportPenyakitToExcel($query, 'Laporan_10_Besar_Penyakit_Ralan_' . $tgl_awal . '_' . $tgl_akhir);
        }

        return $this->draw('laporan_penyakit_ralan.html', ['laporan' => $query, 'tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
    }

    public function anyLaporanPenyakitRanap()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));

        $query = $this->db('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select([
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'count(diagnosa_pasien.kd_penyakit) as jumlah'
            ])
            ->where('reg_periksa.status_lanjut', 'Ranap')
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->group('diagnosa_pasien.kd_penyakit')
            ->desc('jumlah')
            ->limit(10)
            ->toArray();

        // Handle Excel export
        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            return $this->exportPenyakitToExcel($query, 'Laporan_10_Besar_Penyakit_Ranap_' . $tgl_awal . '_' . $tgl_akhir);
        }

        return $this->draw('laporan_penyakit_ranap.html', ['laporan' => $query, 'tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir]);
    }

    public function getCetakPdfSemua()
    {
        $tgl_awal = isset_or($_GET['tgl_awal'], date('Y') . '-01-01');
        $tgl_akhir = isset_or($_GET['tgl_akhir'], date('Y') . '-12-31');

        $query = $this->db('data_tb')
            ->join('reg_periksa', 'reg_periksa.no_rawat = data_tb.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select([
                'data_tb.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'reg_periksa.tgl_registrasi',
                'data_tb.tipe_diagnosis',
                'data_tb.klasifikasi_lokasi_anatomi',
                'data_tb.hasil_akhir_pengobatan',
                'data_tb.tanggal_mulai_pengobatan'
            ])
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->toArray();

        $settings = $this->settings('settings');

        return $this->draw('cetak_pdf_semua.html', [
            'laporan' => $query,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'settings' => $settings
        ]);
    }

    public function getCetakPdfIndividual()
    {
        $no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : null;

        if (!$no_rawat) {
            exit('Parameter no_rawat tidak ditemukan');
        }

        $data = $this->db('data_tb')
            ->join('reg_periksa', 'reg_periksa.no_rawat = data_tb.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->select([
                'data_tb.*',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'pasien.nm_pasien',
                'pasien.jk',
                'pasien.tmp_lahir',
                'pasien.tgl_lahir',
                'pasien.alamat',
                'pasien.no_tlp',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            ])
            ->where('data_tb.no_rawat', $no_rawat)
            ->oneArray();

        if (!$data) {
            exit('Data tidak ditemukan');
        }

        $settings = $this->settings('settings');

        return $this->draw('cetak_pdf_individual.html', [
            'data' => $data,
            'settings' => $settings
        ]);
    }

    public function getCetakPdfSep()
    {
        $tgl_awal = isset_or($_GET['tgl_awal'], date('Y') . '-01-01');
        $tgl_akhir = isset_or($_GET['tgl_akhir'], date('Y') . '-12-31');

        $query = $this->db('bridging_sep')
            ->join('reg_periksa', 'reg_periksa.no_rawat = bridging_sep.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select([
                'bridging_sep.no_sep',
                'bridging_sep.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'bridging_sep.no_kartu',
                'bridging_sep.tglsep',
                'bridging_sep.nmpolitujuan as poli',
                'bridging_sep.nmdiagnosaawal as diagnosa',
                'bridging_sep.jnspelayanan'
            ])
            ->where('bridging_sep.tglsep', '>=', $tgl_awal)
            ->where('bridging_sep.tglsep', '<=', $tgl_akhir)
            ->toArray();

        $settings = $this->settings('settings');

        return $this->draw('cetak_pdf_sep.html', [
            'laporan' => $query,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'settings' => $settings
        ]);
    }

    public function getCetakPdfAntrian()
    {
        $tgl_awal = isset_or($_GET['tgl_awal'], date('Y') . '-01-01');
        $tgl_akhir = isset_or($_GET['tgl_akhir'], date('Y') . '-12-31');

        $query = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->select([
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'pasien.nm_pasien',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'poliklinik.nm_poli',
                'dokter.nm_dokter',
                'reg_periksa.status_bayar',
                'reg_periksa.stts'
            ])
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->where('reg_periksa.status_lanjut', '=', 'Ralan')
            ->toArray();

        $settings = $this->settings('settings');

        return $this->draw('cetak_pdf_antrian.html', [
            'laporan' => $query,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'settings' => $settings
        ]);
    }

    public function getCetakPdfPenyakitRalan()
    {
        $tgl_awal = isset_or($_GET['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_GET['tgl_akhir'], date('Y-m-d'));

        $query = $this->db('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select([
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'count(diagnosa_pasien.kd_penyakit) as jumlah'
            ])
            ->where('reg_periksa.status_lanjut', 'Ralan')
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->group('diagnosa_pasien.kd_penyakit')
            ->desc('jumlah')
            ->limit(10)
            ->toArray();

        $settings = $this->settings('settings');

        echo $this->draw('cetak_pdf_penyakit_ralan.html', [
            'laporan' => $query,
            'tgl_awal' => htmlspecialchars($tgl_awal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'tgl_akhir' => htmlspecialchars($tgl_akhir, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'settings' => htmlspecialchars_array($settings)
        ]);
        exit();
    }

    public function getCetakPdfPenyakitRanap()
    {
        $tgl_awal = isset_or($_GET['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_GET['tgl_akhir'], date('Y-m-d'));

        $query = $this->db('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select([
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'count(diagnosa_pasien.kd_penyakit) as jumlah'
            ])
            ->where('reg_periksa.status_lanjut', 'Ranap')
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir)
            ->group('diagnosa_pasien.kd_penyakit')
            ->desc('jumlah')
            ->limit(10)
            ->toArray();

        $settings = $this->settings('settings');

        echo $this->draw('cetak_pdf_penyakit_ranap.html', [
            'laporan' => $query,
            'tgl_awal' => htmlspecialchars($tgl_awal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'tgl_akhir' => htmlspecialchars($tgl_akhir, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'settings' => htmlspecialchars_array($settings)
        ]);
        exit();
    }

    private function exportToExcel($data, $filename)
    {
        // Set headers for Excel download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Create Excel content using simple CSV format that Excel can read
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM

        // Header row
        $headers = [
            'No',
            'No. Rawat',
            'No. RM',
            'Nama Pasien',
            'Tanggal Registrasi',
            'Tipe Diagnosis',
            'Lokasi Anatomi',
            'Hasil Akhir Pengobatan',
            'Tanggal Mulai Pengobatan'
        ];

        $output .= implode("\t", $headers) . "\n";

        // Data rows
        $no = 1;
        foreach ($data as $row) {
            $dataRow = [
                $no++,
                $row['no_rawat'],
                $row['no_rkm_medis'],
                $row['nm_pasien'],
                $row['tgl_registrasi'],
                $row['tipe_diagnosis'],
                $row['klasifikasi_lokasi_anatomi'],
                $row['hasil_akhir_pengobatan'],
                $row['tanggal_mulai_pengobatan']
            ];

            $output .= implode("\t", $dataRow) . "\n";
        }

        echo $output;
        exit;
    }

    private function exportSepToExcel($data, $filename)
    {
        // Set headers for Excel download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Create Excel content using simple CSV format that Excel can read
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM

        // Header row
        $headers = [
            'No',
            'No. SEP',
            'No. Rawat',
            'No. RM',
            'Nama Pasien',
            'No. Kartu',
            'Tanggal SEP',
            'Poli Tujuan',
            'Diagnosa',
            'Jenis Pelayanan'
        ];

        $output .= implode("\t", $headers) . "\n";

        // Data rows
        $no = 1;
        foreach ($data as $row) {
            $jnsPelayanan = ($row['jnspelayanan'] == '1') ? 'Rawat Inap' : 'Rawat Jalan';

            $dataRow = [
                $no++,
                $row['no_sep'],
                $row['no_rawat'],
                $row['no_rkm_medis'],
                $row['nm_pasien'],
                $row['no_kartu'],
                $row['tglsep'],
                $row['poli'],
                $row['diagnosa'],
                $jnsPelayanan
            ];

            $output .= implode("\t", $dataRow) . "\n";
        }

        echo $output;
        exit;
    }

    private function exportPenyakitToExcel($data, $filename)
    {
        // Set headers for Excel download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Create Excel content using simple CSV format that Excel can read
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM

        // Header row
        $headers = [
            'No',
            'Kode Penyakit',
            'Nama Penyakit',
            'Jumlah'
        ];

        $output .= implode("\t", $headers) . "\n";

        // Data rows
        $no = 1;
        foreach ($data as $row) {
            $dataRow = [
                $no++,
                $row['kd_penyakit'],
                $row['nm_penyakit'],
                $row['jumlah']
            ];

            $output .= implode("\t", $dataRow) . "\n";
        }

        echo $output;
        exit;
    }

    public function anyLaporanRadiologiPacs()
    {
        $this->_addHeaderFiles();
        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-t'));
        $status_pacs = isset_or($_POST['status_pacs'], 'semua');

        $query = $this->db('periksa_radiologi')
            ->join('reg_periksa', 'reg_periksa.no_rawat = periksa_radiologi.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw = periksa_radiologi.kd_jenis_prw')
            ->leftJoin('mlite_mini_pacs_study', 'mlite_mini_pacs_study.no_rawat = periksa_radiologi.no_rawat')
            ->leftJoin('dokter AS dr_rad', 'dr_rad.kd_dokter = periksa_radiologi.kd_dokter')
            ->leftJoin('dokter AS dr_perujuk', 'dr_perujuk.kd_dokter = periksa_radiologi.dokter_perujuk')
            ->leftJoin('petugas', 'petugas.nip = periksa_radiologi.nip')
            ->leftJoin('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
            ->leftJoin('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->select('periksa_radiologi.no_rawat')
            ->select('reg_periksa.no_rkm_medis')
            ->select('pasien.nm_pasien')
            ->select("CONCAT(periksa_radiologi.tgl_periksa, ' ', periksa_radiologi.jam) AS tgl_periksa")
            ->select("GROUP_CONCAT(jns_perawatan_radiologi.nm_perawatan SEPARATOR ', ') AS nm_perawatan")
            ->select('mlite_mini_pacs_study.id AS pacs_id')
            ->select('dr_rad.nm_dokter AS nm_dokter_rad')
            ->select('petugas.nama AS nm_petugas')
            ->select('dr_perujuk.nm_dokter AS nm_dokter_perujuk')
            ->select('reg_periksa.umurdaftar')
            ->select('reg_periksa.sttsumur')
            ->select('SUM(periksa_radiologi.biaya) AS biaya')
            ->select('penjab.png_jawab')
            ->select('poliklinik.nm_poli')
            ->where('periksa_radiologi.tgl_periksa', '>=', $tgl_awal)
            ->where('periksa_radiologi.tgl_periksa', '<=', $tgl_akhir)
            ->group('periksa_radiologi.no_rawat')
            ->group('reg_periksa.no_rkm_medis')
            ->group('pasien.nm_pasien')
            ->group('periksa_radiologi.tgl_periksa')
            ->group('periksa_radiologi.jam')
            ->group('mlite_mini_pacs_study.id')
            ->group('dr_rad.nm_dokter')
            ->group('petugas.nama')
            ->group('dr_perujuk.nm_dokter')
            ->group('reg_periksa.umurdaftar')
            ->group('reg_periksa.sttsumur')
            ->group('penjab.png_jawab')
            ->group('poliklinik.nm_poli')
            ->desc('periksa_radiologi.tgl_periksa');
        
        $data_radiologi = $query->toArray();
        $filtered_data = [];

        foreach ($data_radiologi as $row) {
            $is_terkirim = !empty($row['pacs_id']);
            $row['status_pacs'] = $is_terkirim ? 'Terkirim' : 'Belum Terkirim';
            
            if ($status_pacs == 'terkirim' && !$is_terkirim) continue;
            if ($status_pacs == 'belum' && $is_terkirim) continue;
            
            $filtered_data[] = $row;
        }

        if (isset($_POST['export_excel'])) {
            $this->exportRadiologiPacsToExcel($filtered_data, "Laporan_Radiologi_PACS_{$tgl_awal}_{$tgl_akhir}.xls");
            exit;
        }

        return $this->draw('laporan_radiologi_pacs.html', [
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'status_pacs' => $status_pacs,
            'data_radiologi' => $filtered_data,
            'title' => 'Laporan Radiologi (Status Mini PACS)'
        ]);
    }

    private function exportRadiologiPacsToExcel($data, $filename)
    {
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=$filename");
        header("Pragma: no-cache");
        header("Expires: 0");

        $output = "LAPORAN RADIOLOGI & STATUS MINI PACS\n\n";
        
        $headers = [
            'No',
            'No. Rawat',
            'No. RM',
            'Nama Pasien',
            'Umur',
            'Tgl Periksa',
            'Pemeriksaan',
            'Poli Asal',
            'Dokter Perujuk',
            'Dokter Radiologi',
            'Petugas Radiologi',
            'Tarif / Biaya',
            'Cara Bayar',
            'Status PACS'
        ];

        $output .= implode("\t", $headers) . "\n";

        $no = 1;
        foreach ($data as $row) {
            $dataRow = [
                $no++,
                $row['no_rawat'],
                $row['no_rkm_medis'],
                $row['nm_pasien'],
                $row['umurdaftar'] . ' ' . $row['sttsumur'],
                $row['tgl_periksa'],
                $row['nm_perawatan'],
                $row['nm_poli'],
                $row['nm_dokter_perujuk'],
                $row['nm_dokter_rad'],
                $row['nm_petugas'],
                'Rp ' . number_format($row['biaya'], 0, ',', '.'),
                $row['png_jawab'],
                $row['status_pacs']
            ];

            $output .= implode("\t", $dataRow) . "\n";
        }

        echo $output;
        exit;
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.min.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.plugin.autotable.min.js'));
        $this->core->addJS(url('assets/jscripts/xlsx.js'));
    }

    public function anyLaporankunjungan()
    {
        $this->_addHeaderFiles();

        $tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : (isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d'));
        $tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : (isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'));
        $req_poli = isset($_POST['kd_poli']) ? $_POST['kd_poli'] : (isset($_GET['kd_poli']) ? $_GET['kd_poli'] : '');
        $req_status_rm = isset($_POST['status_rm']) ? $_POST['status_rm'] : (isset($_GET['status_rm']) ? $_GET['status_rm'] : 'tidak_lengkap');

        $req_poli_array = [];
        if (!empty($req_poli)) {
            if (is_array($req_poli)) {
                $req_poli_array = array_filter($req_poli);
            } else {
                $req_poli_array = [$req_poli];
            }
        }

        $startDate = $tgl_awal;
        $endDate = $tgl_akhir;

        $pdo = \Systems\Lib\QueryWrapper::pdo();

        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            return $this->exportRekapitulasiExcel($startDate, $endDate, $req_poli, $pdo);
        }
        if (isset($_POST['export']) && $_POST['export'] == 'kunjungan') {
            return $this->exportLaporanKunjunganExcel($startDate, $endDate, $req_poli, $pdo);
        }
        if (isset($_POST['export']) && $_POST['export'] == 'wilayah') {
            return $this->exportWilayahExcel($startDate, $endDate, $req_poli, $pdo);
        }

        $stats = [
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
        ];

        // Base Query using raw SQL for efficiency
        // Separate 'OD' from regular poli because reg_periksa.kd_poli is overwritten for OD patients
        $has_od = in_array('OD', $req_poli_array);
        $regular_poli = array_values(array_diff($req_poli_array, ['OD']));
        $where = "WHERE reg_periksa.tgl_registrasi >= '$startDate' AND reg_periksa.tgl_registrasi <= '$endDate' AND reg_periksa.stts <> 'Batal'";
        if (!empty($regular_poli) && $has_od) {
            // Both OD and regular poli selected: include regular poli patients AND OD patients
            $in_poli = "'" . implode("','", array_map('addslashes', $regular_poli)) . "'";
            $where .= " AND (reg_periksa.kd_poli IN ($in_poli) OR EXISTS (SELECT 1 FROM mlite_pendaftaran_oral_diagnostic od WHERE od.no_rawat = reg_periksa.no_rawat))";
        } elseif (!empty($regular_poli)) {
            // Only regular poli selected
            $in_poli = "'" . implode("','", array_map('addslashes', $regular_poli)) . "'";
            $where .= " AND reg_periksa.kd_poli IN ($in_poli)";
        } elseif ($has_od) {
            // Only OD selected: only include patients that went through OD
            $where .= " AND EXISTS (SELECT 1 FROM mlite_pendaftaran_oral_diagnostic od WHERE od.no_rawat = reg_periksa.no_rawat)";
        }

        $pdo = \Systems\Lib\QueryWrapper::pdo();

        // 1. Total & Kunjungan (stts_daftar)
        $q_kasus = $pdo->query("SELECT stts_daftar, COUNT(*) as jml FROM reg_periksa $where GROUP BY stts_daftar")->fetchAll(\PDO::FETCH_ASSOC);
        $total = 0;
        $kasus = ['Baru' => 0, 'Lama' => 0];
        foreach ($q_kasus as $row) {
            $val = trim($row['stts_daftar']);
            if (strcasecmp($val, 'Baru') === 0) {
                $kasus['Baru'] += (int) $row['jml'];
            } elseif (strcasecmp($val, 'Lama') === 0) {
                $kasus['Lama'] += (int) $row['jml'];
            }
            $total += (int) $row['jml'];
        }
        $stats['total'] = $total;
        $stats['kasus'] = $kasus;

        // Jenis Kasus (status_poli)
        $q_jenis_kasus = $pdo->query("SELECT status_poli, COUNT(*) as jml FROM reg_periksa $where GROUP BY status_poli")->fetchAll(\PDO::FETCH_ASSOC);
        $jenis_kasus = ['Baru' => 0, 'Lama' => 0];
        foreach ($q_jenis_kasus as $row) {
            $val = trim($row['status_poli']);
            if (strcasecmp($val, 'Baru') === 0) {
                $jenis_kasus['Baru'] += (int) $row['jml'];
            } elseif (strcasecmp($val, 'Lama') === 0) {
                $jenis_kasus['Lama'] += (int) $row['jml'];
            }
        }
        $stats['jenis_kasus'] = $jenis_kasus;

        // 2. Gender
        $q_gender = $pdo->query("SELECT p.jk, COUNT(*) as jml FROM reg_periksa JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis $where GROUP BY p.jk")->fetchAll(\PDO::FETCH_ASSOC);
        $gender = ['L' => 0, 'P' => 0];
        foreach ($q_gender as $row) {
            if (isset($gender[$row['jk']])) {
                $gender[$row['jk']] = (int) $row['jml'];
            }
        }
        $stats['gender'] = $gender;

        // 3. Penjab
        $q_penjab = $pdo->query("SELECT j.png_jawab, COUNT(*) as jml FROM reg_periksa JOIN penjab j ON j.kd_pj = reg_periksa.kd_pj $where GROUP BY j.png_jawab ORDER BY jml DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $stats['penjab'] = array_slice($q_penjab, 0, 10);

        // 6. Distribusi Usia
        $q_umur = $pdo->query("SELECT p.jk, reg_periksa.umurdaftar, reg_periksa.sttsumur, COUNT(*) as jml FROM reg_periksa JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis $where GROUP BY p.jk, reg_periksa.umurdaftar, reg_periksa.sttsumur")->fetchAll(\PDO::FETCH_ASSOC);

        $umur_ranges = [
            '0_5' => ['L' => 0, 'P' => 0],
            '6_11' => ['L' => 0, 'P' => 0],
            '12_15' => ['L' => 0, 'P' => 0],
            '16_25' => ['L' => 0, 'P' => 0],
            '26_35' => ['L' => 0, 'P' => 0],
            '36_45' => ['L' => 0, 'P' => 0],
            '46_55' => ['L' => 0, 'P' => 0],
            '56_65' => ['L' => 0, 'P' => 0],
            '65_plus' => ['L' => 0, 'P' => 0],
        ];

        foreach ($q_umur as $row) {
            $jk = trim($row['jk']);
            if ($jk !== 'L' && $jk !== 'P')
                continue;

            $umur = (int) $row['umurdaftar'];
            $stts = trim($row['sttsumur']);

            // If not year (e.g. Bulan/Hari), it falls in 0-5
            if ($stts !== 'Th') {
                $umur = 0;
            }

            $range = '65_plus';
            if ($umur <= 5)
                $range = '0_5';
            elseif ($umur <= 11)
                $range = '6_11';
            elseif ($umur <= 15)
                $range = '12_15';
            elseif ($umur <= 25)
                $range = '16_25';
            elseif ($umur <= 35)
                $range = '26_35';
            elseif ($umur <= 45)
                $range = '36_45';
            elseif ($umur <= 55)
                $range = '46_55';
            elseif ($umur <= 65)
                $range = '56_65';

            $umur_ranges[$range][$jk] += (int) $row['jml'];
        }
        $stats['umur'] = $umur_ranges;

        // 4. Poli
        $q_poli = $pdo->query("SELECT p.nm_poli, COUNT(*) as jml FROM reg_periksa JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli $where GROUP BY p.nm_poli ORDER BY jml DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $stats['poli'] = $q_poli;

        // 5. Dokter
        $q_dokter = $pdo->query("SELECT d.nm_dokter, COUNT(*) as jml FROM reg_periksa JOIN dokter d ON d.kd_dokter = reg_periksa.kd_dokter $where GROUP BY d.nm_dokter ORDER BY jml DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $stats['dokter'] = $q_dokter;

        // 7. Kunjungan per Unit
        $q_unit = $pdo->query("SELECT IF(od.no_rawat IS NOT NULL, 'OD (Oral Diagnosa)', p.nm_poli) as nm_poli, reg_periksa.stts_daftar, COUNT(*) as jml FROM reg_periksa JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat $where GROUP BY IF(od.no_rawat IS NOT NULL, 'OD (Oral Diagnosa)', p.nm_poli), reg_periksa.stts_daftar")->fetchAll(\PDO::FETCH_ASSOC);
        $kunjungan_unit = [];
        $total_unit = ['Lama' => 0, 'Baru' => 0, 'Total' => 0];
        foreach ($q_unit as $row) {
            $poli = $row['nm_poli'];
            $stts = trim($row['stts_daftar']);

            if (!isset($kunjungan_unit[$poli])) {
                $kunjungan_unit[$poli] = ['Lama' => 0, 'Baru' => 0, 'Total' => 0];
            }

            if (strcasecmp($stts, 'Baru') === 0) {
                $kunjungan_unit[$poli]['Baru'] += (int) $row['jml'];
                $total_unit['Baru'] += (int) $row['jml'];
            } elseif (strcasecmp($stts, 'Lama') === 0) {
                $kunjungan_unit[$poli]['Lama'] += (int) $row['jml'];
                $total_unit['Lama'] += (int) $row['jml'];
            }
            $kunjungan_unit[$poli]['Total'] += (int) $row['jml'];
            $total_unit['Total'] += (int) $row['jml'];
        }

        // Sort by total descending
        uasort($kunjungan_unit, function ($a, $b) {
            return $b['Total'] <=> $a['Total'];
        });

        $stats['kunjungan_unit'] = $kunjungan_unit;
        $stats['total_unit'] = $total_unit;

        // 8. Diagnosa per Unit
        $od_poli_expr = "IF(od.no_rawat IS NOT NULL, 'OD (Oral Diagnosa)', p.nm_poli)";
        $q_diagnosa = $pdo->query("
            SELECT $od_poli_expr as nm_poli, py.kd_penyakit, py.nm_penyakit, COUNT(*) as jml 
            FROM reg_periksa 
            JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli 
            LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat
            JOIN diagnosa_pasien dp ON dp.no_rawat = reg_periksa.no_rawat 
            JOIN penyakit py ON py.kd_penyakit = dp.kd_penyakit 
            $where 
            GROUP BY $od_poli_expr, py.kd_penyakit, py.nm_penyakit 
            ORDER BY nm_poli, jml DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $limit_data = empty($req_poli_array) ? 10 : PHP_INT_MAX;

        $diagnosa_per_poli = [];
        foreach ($q_diagnosa as $row) {
            $poli = $row['nm_poli'];
            if (!isset($diagnosa_per_poli[$poli])) {
                $diagnosa_per_poli[$poli] = ['items' => [], 'total' => 0];
            }
            if (count($diagnosa_per_poli[$poli]['items']) < $limit_data) {
                $diagnosa_per_poli[$poli]['items'][] = [
                    'kd_penyakit' => $row['kd_penyakit'],
                    'nm_penyakit' => $row['nm_penyakit'],
                    'jml' => (int) $row['jml'],
                    'is_total' => false
                ];
            }
            $diagnosa_per_poli[$poli]['total'] += (int) $row['jml'];
        }
        foreach ($diagnosa_per_poli as $poli => $data) {
            $diagnosa_per_poli[$poli]['items'][] = [
                'is_total' => true,
                'total' => $data['total']
            ];
        }
        $stats['diagnosa_per_poli'] = $diagnosa_per_poli;

        // 9. Tindakan per Unit
        $od_poli_expr_t = "IF(od.no_rawat IS NOT NULL, 'OD (Oral Diagnosa)', p.nm_poli)";
        $q_tindakan = $pdo->query("
            SELECT poli, kd_jenis_prw, nm_perawatan, SUM(jml) as jml FROM (
                SELECT $od_poli_expr_t as poli, jp.kd_jenis_prw, jp.nm_perawatan, COUNT(*) as jml 
                FROM reg_periksa JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat JOIN rawat_jl_dr t ON t.no_rawat = reg_periksa.no_rawat JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw $where GROUP BY $od_poli_expr_t, jp.kd_jenis_prw, jp.nm_perawatan
                UNION ALL
                SELECT $od_poli_expr_t as poli, jp.kd_jenis_prw, jp.nm_perawatan, COUNT(*) as jml 
                FROM reg_periksa JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat JOIN rawat_jl_pr t ON t.no_rawat = reg_periksa.no_rawat JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw $where GROUP BY $od_poli_expr_t, jp.kd_jenis_prw, jp.nm_perawatan
                UNION ALL
                SELECT $od_poli_expr_t as poli, jp.kd_jenis_prw, jp.nm_perawatan, COUNT(*) as jml 
                FROM reg_periksa JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat JOIN rawat_jl_drpr t ON t.no_rawat = reg_periksa.no_rawat JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw $where GROUP BY $od_poli_expr_t, jp.kd_jenis_prw, jp.nm_perawatan
            ) AS all_tindakan
            GROUP BY poli, kd_jenis_prw, nm_perawatan
            ORDER BY poli, jml DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $limit_data = empty($req_poli_array) ? 10 : PHP_INT_MAX;

        $tindakan_per_poli = [];
        foreach ($q_tindakan as $row) {
            $poli = $row['poli'];
            if (!isset($tindakan_per_poli[$poli])) {
                $tindakan_per_poli[$poli] = ['items' => [], 'total' => 0];
            }
            if (count($tindakan_per_poli[$poli]['items']) < $limit_data) {
                $tindakan_per_poli[$poli]['items'][] = [
                    'kd_jenis_prw' => $row['kd_jenis_prw'],
                    'nm_perawatan' => $row['nm_perawatan'],
                    'jml' => (int) $row['jml'],
                    'is_total' => false
                ];
            }
            $tindakan_per_poli[$poli]['total'] += (int) $row['jml'];
        }
        foreach ($tindakan_per_poli as $poli => $data) {
            $tindakan_per_poli[$poli]['items'][] = [
                'is_total' => true,
                'total' => $data['total']
            ];
        }
        $stats['tindakan_per_poli'] = $tindakan_per_poli;

        // 10. Geografis - Provinsi
        $stats['provinsi'] = $pdo->query("
            SELECT pr.nm_prop as nama, COUNT(*) as jml 
            FROM reg_periksa 
            JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis 
            JOIN propinsi pr ON pr.kd_prop = p.kd_prop 
            $where 
            GROUP BY pr.nm_prop 
            ORDER BY jml DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Geografis - Kabupaten
        $stats['kabupaten'] = $pdo->query("
            SELECT kb.nm_kab as nama, COUNT(*) as jml 
            FROM reg_periksa 
            JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis 
            JOIN kabupaten kb ON kb.kd_kab = p.kd_kab 
            $where 
            GROUP BY kb.nm_kab 
            ORDER BY jml DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Geografis - Kecamatan
        $stats['kecamatan'] = $pdo->query("
            SELECT kc.nm_kec as nama, COUNT(*) as jml 
            FROM reg_periksa 
            JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis 
            JOIN kecamatan kc ON kc.kd_kec = p.kd_kec 
            $where 
            GROUP BY kc.nm_kec 
            ORDER BY jml DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // Geografis - Kelurahan
        $stats['kelurahan'] = $pdo->query("
            SELECT kl.nm_kel as nama, COUNT(*) as jml 
            FROM reg_periksa 
            JOIN pasien p ON p.no_rkm_medis = reg_periksa.no_rkm_medis 
            JOIN kelurahan kl ON kl.kd_kel = p.kd_kel 
            $where 
            GROUP BY kl.nm_kel 
            ORDER BY jml DESC LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        // c. Rasio Pembatalan Pendaftaran
        $where_batal = "WHERE tgl_registrasi >= '$startDate' AND tgl_registrasi <= '$endDate'";
        if (!empty($req_poli)) {
            $where_batal .= " AND kd_poli = '" . $req_poli . "'";
        }
        $q_batal = $pdo->query("
            SELECT 
                COUNT(*) as total_registrasi,
                SUM(IF(stts = 'Batal', 1, 0)) as total_batal
            FROM reg_periksa
            $where_batal
        ")->fetch(\PDO::FETCH_ASSOC);

        $total_reg = (int) $q_batal['total_registrasi'];
        $total_batal = (int) $q_batal['total_batal'];
        $persen_batal = $total_reg > 0 ? round(($total_batal / $total_reg) * 100, 2) : 0;

        // Fetch patient list matching the filter
        $q_pasien = $pdo->query("
            SELECT 
                reg_periksa.no_rawat,
                reg_periksa.tgl_registrasi,
                reg_periksa.jam_reg,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                pasien.tgl_lahir,
                pasien.jk,
                pasien.alamat,
                IF(od.no_rawat IS NOT NULL, 'OD (Oral Diagnosa)', p.nm_poli) as nm_poli,
                reg_periksa.stts_daftar,
                reg_periksa.status_poli,
                d.nm_dokter,
                penjab.png_jawab,
                TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) as umur
            FROM reg_periksa
            JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli
            LEFT JOIN mlite_pendaftaran_oral_diagnostic od ON od.no_rawat = reg_periksa.no_rawat
            JOIN dokter d ON d.kd_dokter = reg_periksa.kd_dokter
            JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
            $where
            ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.jam_reg DESC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $diagnosa_map = [];
        $tindakan_map = [];
        $perawat_map = [];
        $catatan_map = [];
        $soap_map = [];
        $soap_count = [];
        $earliest_exam = [];
        $icd9_map = [];
        $od_records = [];
        $od_patient_nos = [];
        $all_no_rawat = array_column($q_pasien, 'no_rawat');

        if (!empty($all_no_rawat)) {
            $in_clause = "'" . implode("','", $all_no_rawat) . "'";

            $qd = $pdo->query("
                SELECT dp.no_rawat, py.kd_penyakit, py.nm_penyakit
                FROM diagnosa_pasien dp
                JOIN penyakit py ON py.kd_penyakit = dp.kd_penyakit
                WHERE dp.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qd as $r) {
                $diagnosa_map[$r['no_rawat']][] = $r['kd_penyakit'] . " - " . $r['nm_penyakit'];
            }

            // Fetch ICD-9 (Prosedur) entries
            $qi9 = $pdo->query("
                SELECT pp.no_rawat, pp.kode, i.deskripsi_panjang 
                FROM prosedur_pasien pp 
                JOIN icd9 i ON i.kode = pp.kode 
                WHERE pp.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qi9 as $r) {
                $icd9_map[$r['no_rawat']][] = $r['kode'] . " - " . $r['deskripsi_panjang'];
            }

            $qt = $pdo->query("
                SELECT no_rawat, nm_perawatan FROM (
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_dr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                    UNION ALL
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_pr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                    UNION ALL
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_drpr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                ) all_t
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qt as $r) {
                $tindakan_map[$r['no_rawat']][] = $r['nm_perawatan'];
            }

            // Fetch joint doctor-nurse treatment nurses (Poli assistant nurses)
            $q_drpr_pr = $pdo->query("
                SELECT DISTINCT t.no_rawat, p.nama 
                FROM rawat_jl_drpr t 
                JOIN petugas p ON p.nip = t.nip 
                WHERE t.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $drpr_nurses = [];
            foreach ($q_drpr_pr as $r) {
                $drpr_nurses[$r['no_rawat']][] = $r['nama'];
            }

            // Fetch nurse-only treatment nurses (fallback, excluding CSSD/Pharmacy)
            $q_pr_pr = $pdo->query("
                SELECT DISTINCT t.no_rawat, p.nama 
                FROM rawat_jl_pr t 
                JOIN petugas p ON p.nip = t.nip 
                JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw
                WHERE t.no_rawat IN ($in_clause)
                  AND jp.nm_perawatan NOT LIKE '%pouches%'
                  AND jp.nm_perawatan NOT LIKE '%linen%'
                  AND jp.nm_perawatan NOT LIKE '%embalase%'
                  AND jp.nm_perawatan NOT LIKE '%prescribing%'
                  AND jp.nm_perawatan NOT LIKE '%dispensing%'
                  AND jp.nm_perawatan NOT LIKE '%resep%'
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $pr_nurses = [];
            foreach ($q_pr_pr as $r) {
                $pr_nurses[$r['no_rawat']][] = $r['nama'];
            }

            // Combine based on priority (joint actions first, fallback to nurse-only excluding CSSD)
            $perawat_map = [];
            foreach ($all_no_rawat as $nr) {
                if (!empty($drpr_nurses[$nr])) {
                    $perawat_map[$nr] = $drpr_nurses[$nr];
                } elseif (!empty($pr_nurses[$nr])) {
                    $perawat_map[$nr] = $pr_nurses[$nr];
                }
            }

            // Fetch OD (Oral Diagnosa) patient records
            $q_od = $pdo->query("
                SELECT no_rawat, no_rkm_medis, kd_dokter, tgl_registrasi, stts_daftar, status_poli, kd_pj
                FROM mlite_pendaftaran_oral_diagnostic
                WHERE tgl_registrasi >= '$startDate' AND tgl_registrasi <= '$endDate'
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $od_records = [];
            foreach ($q_od as $r) {
                $od_records[$r['no_rawat']] = $r;
            }
            $od_patient_nos = array_keys($od_records);

            $qc = $pdo->query("SELECT no_rawat, catatan FROM catatan_perawatan WHERE no_rawat IN ($in_clause)")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qc as $r) {
                $catatan_map[$r['no_rawat']][] = $r['catatan'];
            }

            // Fetch SOAP entries
            $q_soap = $pdo->query("
                SELECT 
                    p.no_rawat,
                    p.nip,
                    p.tgl_perawatan,
                    p.jam_rawat,
                    p.tensi,
                    p.suhu_tubuh,
                    p.nadi,
                    p.respirasi,
                    p.tinggi,
                    p.berat,
                    p.gcs,
                    p.alergi,
                    p.lingkar_perut,
                    p.keluhan,
                    p.pemeriksaan,
                    p.penilaian,
                    p.rtl,
                    IF(d.kd_dokter IS NOT NULL, 'Dokter', IF(pt.nip IS NOT NULL, 'Perawat', 'Unknown')) as role
                FROM pemeriksaan_ralan p
                LEFT JOIN dokter d ON d.kd_dokter = p.nip
                LEFT JOIN petugas pt ON pt.nip = p.nip
                WHERE p.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);

            $soap_count = [];
            foreach ($q_soap as $row) {
                $nr = $row['no_rawat'];
                $role = $row['role'];

                // Track SOAP count per role (for OD completeness check)
                $soap_count[$nr][$role] = ($soap_count[$nr][$role] ?? 0) + 1;

                // Track earliest exam date/time for waiting time calculation
                if (!empty($row['tgl_perawatan']) && !empty($row['jam_rawat'])) {
                    $exam_time = strtotime($row['tgl_perawatan'] . ' ' . $row['jam_rawat']);
                    if (!isset($earliest_exam[$nr]) || $exam_time < $earliest_exam[$nr]['timestamp']) {
                        $earliest_exam[$nr] = [
                            'timestamp' => $exam_time,
                            'tgl_perawatan' => $row['tgl_perawatan'],
                            'jam_rawat' => $row['jam_rawat']
                        ];
                    }
                }

                if (!isset($soap_map[$nr][$role])) {
                    $soap_map[$nr][$role] = [
                        'keluhan' => '',
                        'pemeriksaan' => '',
                        'penilaian' => '',
                        'rtl' => '',
                        'tensi' => '',
                        'suhu_tubuh' => '',
                        'nadi' => '',
                        'respirasi' => '',
                        'tinggi' => '',
                        'berat' => '',
                        'gcs' => '',
                        'alergi' => '',
                        'lingkar_perut' => ''
                    ];
                }

                // Merge values
                foreach ($soap_map[$nr][$role] as $field => $val) {
                    $new_val = trim($row[$field] ?? '');
                    $curr_val = trim($val);
                    if (($curr_val === '' || $curr_val === '-') && ($new_val !== '' && $new_val !== '-')) {
                        $soap_map[$nr][$role][$field] = $new_val;
                    } elseif ($curr_val === '') {
                        $soap_map[$nr][$role][$field] = $new_val;
                    }
                }
            }
            // Fetch Operasi (OD) entries
            $qo = $pdo->query("
                SELECT 
                    o.no_rawat,
                    IFNULL(d1.nm_dokter, pt_op1.nama) as dokter_od1,
                    IFNULL(d2.nm_dokter, pt_op2.nama) as dokter_od2,
                    IFNULL(d3.nm_dokter, pt_op3.nama) as dokter_od3,
                    pt1.nama as perawat_od1,
                    pt2.nama as perawat_od2,
                    pt3.nama as perawat_od3
                FROM operasi o
                LEFT JOIN dokter d1 ON d1.kd_dokter = o.operator1
                LEFT JOIN dokter d2 ON d2.kd_dokter = o.operator2
                LEFT JOIN dokter d3 ON d3.kd_dokter = o.operator3
                LEFT JOIN petugas pt_op1 ON pt_op1.nip = o.operator1
                LEFT JOIN petugas pt_op2 ON pt_op2.nip = o.operator2
                LEFT JOIN petugas pt_op3 ON pt_op3.nip = o.operator3
                LEFT JOIN petugas pt1 ON pt1.nip = o.asisten_operator1
                LEFT JOIN petugas pt2 ON pt2.nip = o.asisten_operator2
                LEFT JOIN petugas pt3 ON pt3.nip = o.asisten_operator3
                WHERE o.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $operasi_map = [];
            foreach ($qo as $r) {
                foreach (['dokter_od1', 'dokter_od2', 'dokter_od3'] as $col) {
                    if (!empty($r[$col])) {
                        $operasi_map[$r['no_rawat']]['dokter_od'][] = $r[$col];
                    }
                }
                foreach (['perawat_od1', 'perawat_od2', 'perawat_od3'] as $col) {
                    if (!empty($r[$col])) {
                        $operasi_map[$r['no_rawat']]['perawat_od'][] = $r[$col];
                    }
                }
            }
        }

        $pasien_list = [];
        foreach ($q_pasien as $row) {
            $nr = $row['no_rawat'];
            $pembayaran = $row['png_jawab'];
            if (stripos($row['nm_poli'], 'Integrasi') !== false) {
                $pembayaran = 'COASS';
            }

            // Calculate waiting time
            $waktu_tunggu_text = '-';
            $waktu_tunggu_menit = null;
            $ee = $earliest_exam[$nr] ?? null;
            if ($ee) {
                $reg_time = strtotime($row['tgl_registrasi'] . ' ' . $row['jam_reg']);
                $diff = $ee['timestamp'] - $reg_time;
                if ($diff >= 0) {
                    $waktu_tunggu_menit = round($diff / 60);
                    $jam_reg_formatted = substr($row['jam_reg'], 0, 5);
                    $jam_rawat_formatted = substr($ee['jam_rawat'], 0, 5);
                    $waktu_tunggu_text = $waktu_tunggu_menit . ' Mnt (' . $jam_reg_formatted . ' - ' . $jam_rawat_formatted . ')';
                }
            }

            // Calculate medical record completeness detailed missing items
            $dokter_soap = $soap_map[$nr]['Dokter'] ?? null;
            $perawat_soap = $soap_map[$nr]['Perawat'] ?? null;
            $is_od = in_array($nr, $od_patient_nos);
            $dokter_soap_count = $soap_count[$nr]['Dokter'] ?? 0;
            $perawat_soap_count = $soap_count[$nr]['Perawat'] ?? 0;

            $missing_dokter = [];
            if (!$dokter_soap) {
                $missing_dokter[] = 'SOAP belum diisi';
            } else {
                if (trim($dokter_soap['keluhan']) === '' || trim($dokter_soap['keluhan']) === '-') {
                    $missing_dokter[] = 'Keluhan belum diisi';
                }
                if (trim($dokter_soap['pemeriksaan']) === '' || trim($dokter_soap['pemeriksaan']) === '-') {
                    $missing_dokter[] = 'Pemeriksaan belum diisi';
                }
                if (trim($dokter_soap['penilaian']) === '' || trim($dokter_soap['penilaian']) === '-') {
                    $missing_dokter[] = 'Penilaian (Diagnosis) belum diisi';
                }
                if (trim($dokter_soap['rtl']) === '' || trim($dokter_soap['rtl']) === '-') {
                    $missing_dokter[] = 'RTL belum diisi';
                }
                if (trim($dokter_soap['tensi']) === '' || trim($dokter_soap['tensi']) === '-') {
                    $missing_dokter[] = 'Tensi belum diisi';
                }
                if (trim($dokter_soap['suhu_tubuh']) === '' || trim($dokter_soap['suhu_tubuh']) === '-') {
                    $missing_dokter[] = 'Suhu belum diisi';
                }
                if (trim($dokter_soap['nadi']) === '' || trim($dokter_soap['nadi']) === '-') {
                    $missing_dokter[] = 'Nadi belum diisi';
                }
                if (trim($dokter_soap['respirasi']) === '' || trim($dokter_soap['respirasi']) === '-') {
                    $missing_dokter[] = 'Respirasi belum diisi';
                }
                if (trim($dokter_soap['tinggi']) === '' || trim($dokter_soap['tinggi']) === '-') {
                    $missing_dokter[] = 'Tinggi belum diisi';
                }
                if (trim($dokter_soap['berat']) === '' || trim($dokter_soap['berat']) === '-') {
                    $missing_dokter[] = 'Berat belum diisi';
                }
                if (trim($dokter_soap['gcs']) === '' || trim($dokter_soap['gcs']) === '-') {
                    $missing_dokter[] = 'GCS belum diisi';
                }
            }
            if (empty($diagnosa_map[$nr])) {
                $missing_dokter[] = 'ICD-10 belum diisi';
            }
            if (empty($icd9_map[$nr])) {
                $missing_dokter[] = 'ICD-9 belum diisi';
            }

            $missing_perawat = [];
            if (!$perawat_soap) {
                $missing_perawat[] = 'SOAP belum diisi';
            } else {
                if (trim($perawat_soap['keluhan']) === '' || trim($perawat_soap['keluhan']) === '-') {
                    $missing_perawat[] = 'Keluhan belum diisi';
                }
                if (trim($perawat_soap['tensi']) === '' || trim($perawat_soap['tensi']) === '-') {
                    $missing_perawat[] = 'Tensi belum diisi';
                }
                if (trim($perawat_soap['suhu_tubuh']) === '' || trim($perawat_soap['suhu_tubuh']) === '-') {
                    $missing_perawat[] = 'Suhu belum diisi';
                }
                if (trim($perawat_soap['nadi']) === '' || trim($perawat_soap['nadi']) === '-') {
                    $missing_perawat[] = 'Nadi belum diisi';
                }
                if (trim($perawat_soap['respirasi']) === '' || trim($perawat_soap['respirasi']) === '-') {
                    $missing_perawat[] = 'Respirasi belum diisi';
                }
                if (trim($perawat_soap['tinggi']) === '' || trim($perawat_soap['tinggi']) === '-') {
                    $missing_perawat[] = 'Tinggi belum diisi';
                }
                if (trim($perawat_soap['berat']) === '' || trim($perawat_soap['berat']) === '-') {
                    $missing_perawat[] = 'Berat belum diisi';
                }
                if (trim($perawat_soap['gcs']) === '' || trim($perawat_soap['gcs']) === '-') {
                    $missing_perawat[] = 'GCS belum diisi';
                }
            }

            // RM completeness: OD patients need 2 dokter + 2 perawat SOAP entries (OD + tujuan)
            if ($is_od) {
                $rm_status = ($dokter_soap_count >= 2 && $perawat_soap_count >= 2) ? 'lengkap' : 'tidak';
                // Even if SOAP count is sufficient, still check individual field completeness for the merged data
                if ($rm_status === 'lengkap') {
                    // Still mark as tidak if any required field is missing in merged SOAP
                    if (empty($missing_dokter) && empty($missing_perawat)) {
                        $rm_status = 'lengkap';
                    } else {
                        $rm_status = 'tidak';
                    }
                }
            } else {
                $rm_status = (empty($missing_dokter) && empty($missing_perawat)) ? 'lengkap' : 'tidak';
            }

            // Pre-render HTML for missing doctor items
            $dokter_soap_info = '';
            if ($is_od && $dokter_soap_count < 2) {
                $dokter_soap_info = '<div style="margin-bottom: 5px;"><strong>Dokter:</strong><ul style="margin: 0; padding-left: 12px; color: #f43f5e; list-style-type: square;">';
                $dokter_soap_info .= '<li>SOAP Dokter OD/Poli: ' . $dokter_soap_count . '/2 entri</li>';
                $dokter_soap_info .= '</ul></div>';
            } elseif (empty($missing_dokter)) {
                $dokter_soap_info = '<div style="margin-bottom: 5px; color: #10b981;"><i class="fa fa-check-circle"></i> Dokter: Lengkap</div>';
            } else {
                $dokter_soap_info = '<div style="margin-bottom: 5px;"><strong>Dokter:</strong><ul style="margin: 0; padding-left: 12px; color: #f43f5e; list-style-type: square;">';
                foreach ($missing_dokter as $msg) {
                    $dokter_soap_info .= '<li>' . htmlspecialchars($msg) . '</li>';
                }
                $dokter_soap_info .= '</ul></div>';
            }

            // Pre-render HTML for missing perawat items
            $perawat_soap_info = '';
            if ($is_od && $perawat_soap_count < 2) {
                $perawat_soap_info = '<div><strong>Perawat:</strong><ul style="margin: 0; padding-left: 12px; color: #f43f5e; list-style-type: square;">';
                $perawat_soap_info .= '<li>SOAP Perawat OD/Poli: ' . $perawat_soap_count . '/2 entri</li>';
                $perawat_soap_info .= '</ul></div>';
            } elseif (empty($missing_perawat)) {
                $perawat_soap_info = '<div style="color: #10b981;"><i class="fa fa-check-circle"></i> Perawat: Lengkap</div>';
            } else {
                $perawat_soap_info = '<div><strong>Perawat:</strong><ul style="margin: 0; padding-left: 12px; color: #f43f5e; list-style-type: square;">';
                foreach ($missing_perawat as $msg) {
                    $perawat_soap_info .= '<li>' . htmlspecialchars($msg) . '</li>';
                }
                $perawat_soap_info .= '</ul></div>';
            }

            $dokter_od_list = isset($operasi_map[$nr]['dokter_od']) ? implode(", ", array_unique($operasi_map[$nr]['dokter_od'])) : '-';
            $perawat_od_list = isset($operasi_map[$nr]['perawat_od']) ? implode(", ", array_unique($operasi_map[$nr]['perawat_od'])) : '-';

            $pasien_list[] = [
                'no_rawat' => $row['no_rawat'],
                'tgl_kunjungan' => date('d-m-Y', strtotime($row['tgl_registrasi'])) . ' ' . date('H:i', strtotime($row['jam_reg'])),
                'waktu_tunggu' => $waktu_tunggu_text,
                'waktu_tunggu_menit' => $waktu_tunggu_menit,
                'rm_status' => $rm_status,
                'is_od' => $is_od,
                'dokter_soap_count' => $dokter_soap_count,
                'perawat_soap_count' => $perawat_soap_count,
                'dokter_soap_info' => $dokter_soap_info,
                'perawat_soap_info' => $perawat_soap_info,
                'no_rkm_medis' => $row['no_rkm_medis'],
                'nm_pasien' => $row['nm_pasien'],
                'tgl_lahir' => date('d-m-Y', strtotime($row['tgl_lahir'])),
                'umur' => $row['umur'],
                'jk' => ($row['jk'] == 'L') ? 'L' : 'P',
                'alamat' => $row['alamat'],
                'nm_poli' => $row['nm_poli'],
                'stts_daftar' => strtolower($row['stts_daftar']),
                'status_poli' => strtolower($row['status_poli']),
                'diagnosa' => isset($diagnosa_map[$nr]) ? implode(", ", array_unique($diagnosa_map[$nr])) : '-',
                'tindakan' => isset($tindakan_map[$nr]) ? implode(", ", array_unique($tindakan_map[$nr])) : '-',
                'nm_dokter' => $row['nm_dokter'],
                'perawat' => isset($perawat_map[$nr]) ? implode(", ", array_unique($perawat_map[$nr])) : '-',
                'dokter_od' => $dokter_od_list,
                'perawat_od' => $perawat_od_list,
                'pembayaran' => $pembayaran,
                'catatan' => isset($catatan_map[$nr]) ? implode(", ", array_unique($catatan_map[$nr])) : '-'
            ];
        }

        // Calculate quality indicator statistics based on compiled pasien_list
        $total_diperiksa = 0;
        $kurang_60 = 0;
        $lebih_60 = 0;
        $rm_lengkap = 0;
        $total_kunjungan = count($pasien_list);
        $poli_breakdown = [];

        foreach ($pasien_list as $p) {
            if ($p['waktu_tunggu_menit'] !== null) {
                $total_diperiksa++;
                if ($p['waktu_tunggu_menit'] <= 60) {
                    $kurang_60++;
                } else {
                    $lebih_60++;
                }
            }
            if ($p['rm_status'] === 'lengkap') {
                $rm_lengkap++;
            }

            // Calculate per-poliklinik completeness breakdown
            $nm_p = $p['nm_poli'];
            if (!isset($poli_breakdown[$nm_p])) {
                $poli_breakdown[$nm_p] = [
                    'total' => 0,
                    'lengkap' => 0
                ];
            }
            $poli_breakdown[$nm_p]['total']++;
            if ($p['rm_status'] === 'lengkap') {
                $poli_breakdown[$nm_p]['lengkap']++;
            }
        }

        foreach ($poli_breakdown as $nm_p => $data) {
            $poli_breakdown[$nm_p]['persentase'] = $data['total'] > 0 ? round(($data['lengkap'] / $data['total']) * 100, 2) : 0;
        }
        ksort($poli_breakdown);

        $persen_wt = $total_diperiksa > 0 ? round(($kurang_60 / $total_diperiksa) * 100, 2) : 0;
        $persen_rm = $total_kunjungan > 0 ? round(($rm_lengkap / $total_kunjungan) * 100, 2) : 0;

        $stats['indikator_mutu'] = [
            'waktu_tunggu' => [
                'total' => $total_diperiksa,
                'kurang_60' => $kurang_60,
                'lebih_60' => $lebih_60,
                'persentase' => $persen_wt,
                'target' => 80
            ],
            'rekam_medis' => [
                'total' => $total_kunjungan,
                'lengkap' => $rm_lengkap,
                'tidak_lengkap' => $total_kunjungan - $rm_lengkap,
                'persentase' => $persen_rm,
                'target' => 100,
                'breakdown' => $poli_breakdown
            ],
            'pembatalan' => [
                'total' => $total_reg,
                'batal' => $total_batal,
                'aktif' => $total_reg - $total_batal,
                'persentase' => $persen_batal,
                'target' => 5
            ]
        ];

        // Get poliklinik for dropdown (including OD)
        $poliklinik = $pdo->query("SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli")->fetchAll(\PDO::FETCH_ASSOC);
        // Add OD only if not already in the list
        $has_od_poli = false;
        foreach ($poliklinik as $p) {
            if ($p['kd_poli'] === 'OD') { $has_od_poli = true; break; }
        }
        if (!$has_od_poli) {
            array_unshift($poliklinik, ['kd_poli' => 'OD', 'nm_poli' => 'OD (Oral Diagnosa)']);
        }

        // Filter for table presentation based on Kelengkapan RM
        $filtered_pasien_list = [];
        foreach ($pasien_list as $p) {
            if ($req_status_rm === 'semua') {
                $filtered_pasien_list[] = $p;
            } elseif ($req_status_rm === 'lengkap' && $p['rm_status'] === 'lengkap') {
                $filtered_pasien_list[] = $p;
            } elseif ($req_status_rm === 'tidak_lengkap' && $p['rm_status'] === 'tidak') {
                $filtered_pasien_list[] = $p;
            }
        }
        $pasien_list = $filtered_pasien_list;

        $total_data = count($pasien_list);

        $viewData = [
            'stats' => $stats,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'req_poli' => $req_poli_array,
            'req_status_rm' => $req_status_rm,
            'poliklinik' => $poliklinik,
            'pasien_list' => $pasien_list,
            'total_pasien_list' => $total_data,
        ];

        if (isset($_GET['export']) && $_GET['export'] == 'excel') {
            return $this->exportExcel($viewData);
        }

        return $this->draw('laporan_kunjungan.html', ['laporan' => $viewData]);
    }

    private function exportRekapitulasiExcel($startDate, $endDate, $req_poli, $pdo)
    {
        $periode = date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate));

        $req_poli_array = [];
        if (!empty($req_poli)) {
            if (is_array($req_poli)) {
                $req_poli_array = array_filter($req_poli);
            } else {
                $req_poli_array = [$req_poli];
            }
        }

        $nama_poli = 'Semua Poliklinik';
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $q_poli = $pdo->query("SELECT nm_poli FROM poliklinik WHERE kd_poli IN ($in_poli)")->fetchAll(\PDO::FETCH_ASSOC);
            $nama_poli = implode(", ", array_column($q_poli, 'nm_poli'));
        }

        $filename = "Rekap_Kunjungan_" . date('Ymd', strtotime($startDate)) . "_" . date('Ymd', strtotime($endDate)) . "_" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_poli) . ".xls";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $where = "WHERE reg_periksa.tgl_registrasi >= '$startDate' AND reg_periksa.tgl_registrasi <= '$endDate' AND reg_periksa.stts <> 'Batal'";
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $where .= " AND reg_periksa.kd_poli IN ($in_poli)";
        }

        $query = "
            SELECT 
                reg_periksa.tgl_registrasi, 
                COUNT(*) as total_pasien,
                SUM(IF(pasien.jk = 'L', 1, 0)) as gender_l,
                SUM(IF(pasien.jk = 'P', 1, 0)) as gender_p,
                SUM(IF(reg_periksa.stts_daftar = 'Lama', 1, 0)) as kunjungan_lama,
                SUM(IF(reg_periksa.stts_daftar = 'Baru', 1, 0)) as kunjungan_baru,
                SUM(IF(reg_periksa.status_poli = 'Baru', 1, 0)) as kasus_baru,
                SUM(IF(reg_periksa.status_poli = 'Lama', 1, 0)) as kasus_lama,
                SUM(IF(p.nm_poli LIKE '%Integrasi%', 0, IF(penjab.png_jawab LIKE '%UMUM%', 1, 0))) as umum,
                SUM(IF(p.nm_poli LIKE '%Integrasi%', 0, IF(penjab.png_jawab LIKE '%BPJS%', 1, 0))) as bpjs,
                SUM(IF(p.nm_poli LIKE '%Integrasi%', 1, IF(penjab.png_jawab LIKE '%COASS%', 1, 0))) as coass,
                SUM(IF(p.nm_poli LIKE '%Integrasi%', 0, IF(penjab.png_jawab LIKE '%ASLIN%', 1, 0))) as aslin,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 0 AND 5, 1, 0)) as umur_0_5,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 6 AND 11, 1, 0)) as umur_6_11,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 12 AND 15, 1, 0)) as umur_12_15,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 16 AND 25, 1, 0)) as umur_16_25,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 26 AND 35, 1, 0)) as umur_26_35,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 36 AND 45, 1, 0)) as umur_36_45,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 46 AND 55, 1, 0)) as umur_46_55,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) BETWEEN 56 AND 65, 1, 0)) as umur_56_65,
                SUM(IF(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) > 65, 1, 0)) as umur_65_plus
            FROM reg_periksa 
            JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
            JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli
            $where
            GROUP BY reg_periksa.tgl_registrasi
        ";

        $results = $pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        $data_by_date = [];
        foreach ($results as $row) {
            $data_by_date[$row['tgl_registrasi']] = $row;
        }

        echo '<table border="1" cellpadding="3" cellspacing="0">';
        echo '<tr><td colspan="22"><strong>REKAPITULASI SENSUS HARIAN RAWAT JALAN</strong></td></tr>';
        echo '<tr><td colspan="22"><strong>RUMAH SAKIT GIGI DAN MULUT GUSTI HASAN AMAN</strong></td></tr>';
        echo '<tr><td colspan="22"><strong>PERIODE: ' . $periode . '</strong></td></tr>';
        echo '<tr><td colspan="22"><strong>Poliklinik: ' . $nama_poli . '</strong></td></tr>';
        echo '<tr><td colspan="22"></td></tr>';

        echo '<tr>';
        echo '<td rowspan="2" align="center"><strong>TANGGAL</strong></td>';
        echo '<td rowspan="2" align="center"><strong>TOTAL PASIEN</strong></td>';
        echo '<td colspan="2" align="center"><strong>GENDER</strong></td>';
        echo '<td colspan="2" align="center"><strong>KUNJUNGAN</strong></td>';
        echo '<td colspan="2" align="center"><strong>KASUS</strong></td>';
        echo '<td colspan="4" align="center"><strong>PELAYANAN</strong></td>';
        echo '<td colspan="9" align="center"><strong>UMUR</strong></td>';
        echo '<td rowspan="2" align="center"><strong>KET</strong></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td align="center"><strong>L</strong></td><td align="center"><strong>P</strong></td>';
        echo '<td align="center"><strong>LAMA</strong></td><td align="center"><strong>BARU</strong></td>';
        echo '<td align="center"><strong>KB</strong></td><td align="center"><strong>KL</strong></td>';
        echo '<td align="center"><strong>UMUM</strong></td><td align="center"><strong>BPJS</strong></td><td align="center"><strong>COASS</strong></td><td align="center"><strong>ASLIN</strong></td>';
        echo '<td align="center"><strong>0-5</strong></td><td align="center"><strong>6-11</strong></td><td align="center"><strong>12-15</strong></td><td align="center"><strong>16-25</strong></td><td align="center"><strong>26-35</strong></td><td align="center"><strong>36-45</strong></td><td align="center"><strong>46-55</strong></td><td align="center"><strong>56-65</strong></td><td align="center"><strong>65+</strong></td>';
        echo '</tr>';

        $totals = array_fill(0, 20, 0);

        $currentDate = new \DateTime($startDate);
        $endDateObj = new \DateTime($endDate);

        while ($currentDate <= $endDateObj) {
            $date = $currentDate->format('Y-m-d');
            $d = isset($data_by_date[$date]) ? $data_by_date[$date] : null;

            echo '<tr>';
            echo "<td>" . date('d-m-Y', strtotime($date)) . "</td>";

            if ($d) {
                echo "<td>" . $d['total_pasien'] . "</td>";
                echo "<td>" . $d['gender_l'] . "</td>";
                echo "<td>" . $d['gender_p'] . "</td>";
                echo "<td>" . $d['kunjungan_lama'] . "</td>";
                echo "<td>" . $d['kunjungan_baru'] . "</td>";
                echo "<td>" . $d['kasus_baru'] . "</td>";
                echo "<td>" . $d['kasus_lama'] . "</td>";
                echo "<td>" . $d['umum'] . "</td>";
                echo "<td>" . $d['bpjs'] . "</td>";
                echo "<td>" . $d['coass'] . "</td>";
                echo "<td>" . $d['aslin'] . "</td>";
                echo "<td>" . $d['umur_0_5'] . "</td>";
                echo "<td>" . $d['umur_6_11'] . "</td>";
                echo "<td>" . $d['umur_12_15'] . "</td>";
                echo "<td>" . $d['umur_16_25'] . "</td>";
                echo "<td>" . $d['umur_26_35'] . "</td>";
                echo "<td>" . $d['umur_36_45'] . "</td>";
                echo "<td>" . $d['umur_46_55'] . "</td>";
                echo "<td>" . $d['umur_56_65'] . "</td>";
                echo "<td>" . $d['umur_65_plus'] . "</td>";
                echo "<td>0</td>";

                $totals[0] += $d['total_pasien'];
                $totals[1] += $d['gender_l'];
                $totals[2] += $d['gender_p'];
                $totals[3] += $d['kunjungan_lama'];
                $totals[4] += $d['kunjungan_baru'];
                $totals[5] += $d['kasus_baru'];
                $totals[6] += $d['kasus_lama'];
                $totals[7] += $d['umum'];
                $totals[8] += $d['bpjs'];
                $totals[9] += $d['coass'];
                $totals[10] += $d['aslin'];
                $totals[11] += $d['umur_0_5'];
                $totals[12] += $d['umur_6_11'];
                $totals[13] += $d['umur_12_15'];
                $totals[14] += $d['umur_16_25'];
                $totals[15] += $d['umur_26_35'];
                $totals[16] += $d['umur_36_45'];
                $totals[17] += $d['umur_46_55'];
                $totals[18] += $d['umur_56_65'];
                $totals[19] += $d['umur_65_plus'];
            } else {
                echo "<td>0</td>"; // TOTAL PASIEN
                echo "<td>0</td><td>0</td>"; // GENDER
                echo "<td>0</td><td>0</td>"; // KUNJUNGAN
                echo "<td>0</td><td>0</td>"; // KASUS
                echo "<td>0</td><td>0</td><td>0</td><td>0</td>"; // PELAYANAN
                echo "<td>0</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td><td>0</td>"; // UMUR
                echo "<td>0</td>"; // KET
            }
            echo '</tr>';
            $currentDate->modify('+1 day');
        }

        echo '<tr>';
        echo '<td><strong>BULAN: ' . $periode . '</strong></td>';
        for ($i = 0; $i <= 19; $i++) {
            echo "<td><strong>" . (isset($totals[$i]) ? $totals[$i] : 0) . "</strong></td>";
        }
        echo '<td><strong>0</strong></td>'; // KET
        echo '</tr>';

        echo '</table>';
        exit;
    }

    private function exportLaporanKunjunganExcel($startDate, $endDate, $req_poli, $pdo)
    {
        $req_poli_array = [];
        if (!empty($req_poli)) {
            if (is_array($req_poli)) {
                $req_poli_array = array_filter($req_poli);
            } else {
                $req_poli_array = [$req_poli];
            }
        }

        $nama_poli = 'Semua Poliklinik';
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $q_poli = $pdo->query("SELECT nm_poli FROM poliklinik WHERE kd_poli IN ($in_poli)")->fetchAll(\PDO::FETCH_ASSOC);
            $nama_poli = implode(", ", array_column($q_poli, 'nm_poli'));
        }

        $filename = "Laporan_Kunjungan_" . date('Ymd_His') . "_" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_poli) . ".xls";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $periode_text = date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate));

        $hari_array = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        $hari_ini = $hari_array[date('D')] . ", " . date('j F Y');

        $where = "WHERE reg_periksa.tgl_registrasi >= '$startDate' AND reg_periksa.tgl_registrasi <= '$endDate' AND reg_periksa.stts <> 'Batal'";
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $where .= " AND reg_periksa.kd_poli IN ($in_poli)";
        }

        $query = "
            SELECT 
                reg_periksa.no_rawat,
                reg_periksa.tgl_registrasi,
                reg_periksa.jam_reg,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                pasien.tgl_lahir,
                pasien.jk,
                pasien.alamat,
                p.nm_poli,
                reg_periksa.stts_daftar,
                reg_periksa.status_poli,
                d.nm_dokter,
                penjab.png_jawab,
                TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) as umur
            FROM reg_periksa
            JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli
            JOIN dokter d ON d.kd_dokter = reg_periksa.kd_dokter
            JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
            $where
            ORDER BY reg_periksa.tgl_registrasi ASC, reg_periksa.jam_reg ASC
        ";

        $results = $pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $all_no_rawat = array_column($results, 'no_rawat');

        $diagnosa_map = [];
        $tindakan_map = [];
        $perawat_map = [];
        $catatan_map = [];
        $operasi_map = [];

        if (!empty($all_no_rawat)) {
            $in_clause = "'" . implode("','", $all_no_rawat) . "'";

            $qd = $pdo->query("
                SELECT dp.no_rawat, py.kd_penyakit, py.nm_penyakit
                FROM diagnosa_pasien dp
                JOIN penyakit py ON py.kd_penyakit = dp.kd_penyakit
                WHERE dp.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qd as $r) {
                $diagnosa_map[$r['no_rawat']][] = $r['kd_penyakit'] . " - " . $r['nm_penyakit'];
            }

            $qt = $pdo->query("
                SELECT no_rawat, nm_perawatan FROM (
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_dr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                    UNION ALL
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_pr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                    UNION ALL
                    SELECT t.no_rawat, jp.nm_perawatan FROM rawat_jl_drpr t JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw WHERE t.no_rawat IN ($in_clause)
                ) all_t
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qt as $r) {
                $tindakan_map[$r['no_rawat']][] = $r['nm_perawatan'];
            }

            // Fetch joint doctor-nurse treatment nurses (Poli assistant nurses)
            $q_drpr_pr = $pdo->query("
                SELECT DISTINCT t.no_rawat, p.nama 
                FROM rawat_jl_drpr t 
                JOIN petugas p ON p.nip = t.nip 
                WHERE t.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $drpr_nurses = [];
            foreach ($q_drpr_pr as $r) {
                $drpr_nurses[$r['no_rawat']][] = $r['nama'];
            }

            // Fetch nurse-only treatment nurses (fallback, excluding CSSD/Pharmacy)
            $q_pr_pr = $pdo->query("
                SELECT DISTINCT t.no_rawat, p.nama 
                FROM rawat_jl_pr t 
                JOIN petugas p ON p.nip = t.nip 
                JOIN jns_perawatan jp ON jp.kd_jenis_prw = t.kd_jenis_prw
                WHERE t.no_rawat IN ($in_clause)
                  AND jp.nm_perawatan NOT LIKE '%pouches%'
                  AND jp.nm_perawatan NOT LIKE '%linen%'
                  AND jp.nm_perawatan NOT LIKE '%embalase%'
                  AND jp.nm_perawatan NOT LIKE '%prescribing%'
                  AND jp.nm_perawatan NOT LIKE '%dispensing%'
                  AND jp.nm_perawatan NOT LIKE '%resep%'
            ")->fetchAll(\PDO::FETCH_ASSOC);
            $pr_nurses = [];
            foreach ($q_pr_pr as $r) {
                $pr_nurses[$r['no_rawat']][] = $r['nama'];
            }

            // Combine based on priority (joint actions first, fallback to nurse-only excluding CSSD)
            $perawat_map = [];
            foreach ($all_no_rawat as $nr) {
                if (!empty($drpr_nurses[$nr])) {
                    $perawat_map[$nr] = $drpr_nurses[$nr];
                } elseif (!empty($pr_nurses[$nr])) {
                    $perawat_map[$nr] = $pr_nurses[$nr];
                }
            }

            $qc = $pdo->query("SELECT no_rawat, catatan FROM catatan_perawatan WHERE no_rawat IN ($in_clause)")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qc as $r) {
                $catatan_map[$r['no_rawat']][] = $r['catatan'];
            }

            // Fetch Operasi (OD) entries
            $qo = $pdo->query("
                SELECT 
                    o.no_rawat,
                    IFNULL(d1.nm_dokter, pt_op1.nama) as dokter_od1,
                    IFNULL(d2.nm_dokter, pt_op2.nama) as dokter_od2,
                    IFNULL(d3.nm_dokter, pt_op3.nama) as dokter_od3,
                    pt1.nama as perawat_od1,
                    pt2.nama as perawat_od2,
                    pt3.nama as perawat_od3
                FROM operasi o
                LEFT JOIN dokter d1 ON d1.kd_dokter = o.operator1
                LEFT JOIN dokter d2 ON d2.kd_dokter = o.operator2
                LEFT JOIN dokter d3 ON d3.kd_dokter = o.operator3
                LEFT JOIN petugas pt_op1 ON pt_op1.nip = o.operator1
                LEFT JOIN petugas pt_op2 ON pt_op2.nip = o.operator2
                LEFT JOIN petugas pt_op3 ON pt_op3.nip = o.operator3
                LEFT JOIN petugas pt1 ON pt1.nip = o.asisten_operator1
                LEFT JOIN petugas pt2 ON pt2.nip = o.asisten_operator2
                LEFT JOIN petugas pt3 ON pt3.nip = o.asisten_operator3
                WHERE o.no_rawat IN ($in_clause)
            ")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($qo as $r) {
                foreach (['dokter_od1', 'dokter_od2', 'dokter_od3'] as $col) {
                    if (!empty($r[$col])) {
                        $operasi_map[$r['no_rawat']]['dokter_od'][] = $r[$col];
                    }
                }
                foreach (['perawat_od1', 'perawat_od2', 'perawat_od3'] as $col) {
                    if (!empty($r[$col])) {
                        $operasi_map[$r['no_rawat']]['perawat_od'][] = $r[$col];
                    }
                }
            }
        }

        echo '<table border="1" cellpadding="3" cellspacing="0">';
        echo '<tr><td colspan="18"><strong>REKAPITULASI SENSUS HARIAN</strong></td></tr>';
        echo '<tr><td colspan="18"><strong>RUMAH SAKIT GIGI DAN MULUT GUSTI HASAN AMAN</strong></td></tr>';
        echo '<tr>';
        echo '<td colspan="3">Hari: ' . $hari_ini . '</td>';
        echo '<td colspan="7">Poliklinik: ' . $nama_poli . '</td>';
        echo '<td colspan="8">Periode: ' . $periode_text . '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>TGL KUNJUNGAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NO</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NO. RM</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NAMA PASIEN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>TGL LAHIR</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>UMUR</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JENIS KELAMIN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>ALAMAT</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>UNIT</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JENIS KUNJUNGAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JENIS KASUS</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>DIAGNOSA</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>TINDAKAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>DOKTER POLI</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>PERAWAT POLI</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>COASS</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>PEMBAYARAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>CATATAN</strong></td>';
        echo '</tr>';

        $no = 1;
        foreach ($results as $row) {
            $nr = $row['no_rawat'];
            $tgl_kunjungan = date('d/m/Y', strtotime($row['tgl_registrasi'])) . ' ' . date('H:i', strtotime($row['jam_reg']));
            $tgl_lahir = date('d/m/Y', strtotime($row['tgl_lahir']));
            $jk = ($row['jk'] == 'L') ? 'Laki-laki' : 'Perempuan';

            $diagnosa = isset($diagnosa_map[$nr]) ? implode("\n", array_unique($diagnosa_map[$nr])) : '-';
            $tindakan = isset($tindakan_map[$nr]) ? implode("\n", array_unique($tindakan_map[$nr])) : '-';
            $perawat = isset($perawat_map[$nr]) ? implode(", ", array_unique($perawat_map[$nr])) : '-';
            $catatan = isset($catatan_map[$nr]) ? implode("\n", array_unique($catatan_map[$nr])) : '-';

            $dokter_od_list = isset($operasi_map[$nr]['dokter_od']) ? implode(", ", array_unique($operasi_map[$nr]['dokter_od'])) : '-';
            $perawat_od_list = isset($operasi_map[$nr]['perawat_od']) ? implode(", ", array_unique($operasi_map[$nr]['perawat_od'])) : '-';

            $pembayaran = $row['png_jawab'];
            $coass = '-';
            if (stripos($row['nm_poli'], 'Integrasi') !== false) {
                $pembayaran = 'COASS';
            }

            echo '<tr>';
            echo '<td valign="top">' . $tgl_kunjungan . '</td>';
            echo '<td valign="top" align="center">' . $no++ . '</td>';
            echo '<td valign="top" align="center" style="mso-number-format:\'@\';">' . $row['no_rkm_medis'] . '</td>';
            echo '<td valign="top">' . $row['nm_pasien'] . '</td>';
            echo '<td valign="top" align="center">' . $tgl_lahir . '</td>';
            echo '<td valign="top" align="center">' . $row['umur'] . '</td>';
            echo '<td valign="top">' . $jk . '</td>';
            echo '<td valign="top">' . $row['alamat'] . '</td>';
            echo '<td valign="top">' . $row['nm_poli'] . '</td>';
            echo '<td valign="top">' . strtolower($row['stts_daftar']) . '</td>';
            echo '<td valign="top" align="center">' . strtolower($row['status_poli']) . '</td>';
            echo '<td valign="top">' . nl2br(htmlspecialchars($diagnosa)) . '</td>';
            echo '<td valign="top">' . nl2br(htmlspecialchars($tindakan)) . '</td>';
            echo '<td valign="top">' . $row['nm_dokter'] . '</td>';
            echo '<td valign="top">' . $perawat . '</td>';
            echo '<td valign="top" align="center">' . $coass . '</td>';
            echo '<td valign="top" align="center">' . $pembayaran . '</td>';
            echo '<td valign="top">' . nl2br(htmlspecialchars($catatan)) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        exit;
    }

    private function exportWilayahExcel($startDate, $endDate, $req_poli, $pdo)
    {
        $req_poli_array = [];
        if (!empty($req_poli)) {
            if (is_array($req_poli)) {
                $req_poli_array = array_filter($req_poli);
            } else {
                $req_poli_array = [$req_poli];
            }
        }

        $nama_poli = 'Semua Poliklinik';
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $q_poli = $pdo->query("SELECT nm_poli FROM poliklinik WHERE kd_poli IN ($in_poli)")->fetchAll(\PDO::FETCH_ASSOC);
            $nama_poli = implode(", ", array_column($q_poli, 'nm_poli'));
        }

        $filename = "Data_Wilayah_" . date('Ymd_His') . "_" . preg_replace('/[^A-Za-z0-9]/', '_', $nama_poli) . ".xls";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        $periode_text = date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate));

        $where = "WHERE reg_periksa.tgl_registrasi >= '$startDate' AND reg_periksa.tgl_registrasi <= '$endDate' AND reg_periksa.stts <> 'Batal'";
        if (!empty($req_poli_array)) {
            $in_poli = "'" . implode("','", array_map('addslashes', $req_poli_array)) . "'";
            $where .= " AND reg_periksa.kd_poli IN ($in_poli)";
        }

        $query = "
            SELECT 
                reg_periksa.tgl_registrasi,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                pasien.jk,
                pasien.alamat,
                pr.nm_prop as provinsi,
                kb.nm_kab as kabupaten,
                kc.nm_kec as kecamatan,
                kl.nm_kel as kelurahan,
                p.nm_poli,
                reg_periksa.stts_daftar,
                reg_periksa.status_poli,
                penjab.png_jawab,
                TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) as umur
            FROM reg_periksa
            JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
            JOIN poliklinik p ON p.kd_poli = reg_periksa.kd_poli
            JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
            LEFT JOIN propinsi pr ON pr.kd_prop = pasien.kd_prop
            LEFT JOIN kabupaten kb ON kb.kd_kab = pasien.kd_kab
            LEFT JOIN kecamatan kc ON kc.kd_kec = pasien.kd_kec
            LEFT JOIN kelurahan kl ON kl.kd_kel = pasien.kd_kel
            $where
            ORDER BY pr.nm_prop ASC, kb.nm_kab ASC, kc.nm_kec ASC, kl.nm_kel ASC, reg_periksa.tgl_registrasi ASC
        ";

        $results = $pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        echo '<table border="1" cellpadding="3" cellspacing="0">';
        echo '<tr><td colspan="15"><strong>REKAPITULASI DATA WILAYAH PASIEN</strong></td></tr>';
        echo '<tr><td colspan="15"><strong>RUMAH SAKIT GIGI DAN MULUT GUSTI HASAN AMAN</strong></td></tr>';
        echo '<tr>';
        echo '<td colspan="7">Poliklinik: ' . $nama_poli . '</td>';
        echo '<td colspan="8">Periode: ' . $periode_text . '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NO</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>TGL KUNJUNGAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NO. RM</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>NAMA PASIEN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>UMUR</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JK</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>ALAMAT</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>PROVINSI</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>KABUPATEN/KOTA</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>KECAMATAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>KELURAHAN/DESA</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>POLIKLINIK</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JENIS KUNJUNGAN</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>JENIS KASUS</strong></td>';
        echo '<td align="center" style="background:#e2e8f0;"><strong>PEMBAYARAN</strong></td>';
        echo '</tr>';

        $no = 1;
        foreach ($results as $row) {
            $tgl_kunjungan = date('d/m/Y', strtotime($row['tgl_registrasi']));
            $jk = ($row['jk'] == 'L') ? 'L' : 'P';

            $pembayaran = $row['png_jawab'];
            if (stripos($row['nm_poli'], 'Integrasi') !== false) {
                $pembayaran = 'COASS';
            }

            echo '<tr>';
            echo '<td valign="top" align="center">' . $no++ . '</td>';
            echo '<td valign="top" align="center">' . $tgl_kunjungan . '</td>';
            echo '<td valign="top" align="center" style="mso-number-format:\'@\';">' . $row['no_rkm_medis'] . '</td>';
            echo '<td valign="top">' . $row['nm_pasien'] . '</td>';
            echo '<td valign="top" align="center">' . $row['umur'] . '</td>';
            echo '<td valign="top" align="center">' . $jk . '</td>';
            echo '<td valign="top">' . $row['alamat'] . '</td>';
            echo '<td valign="top">' . $row['provinsi'] . '</td>';
            echo '<td valign="top">' . $row['kabupaten'] . '</td>';
            echo '<td valign="top">' . $row['kecamatan'] . '</td>';
            echo '<td valign="top">' . $row['kelurahan'] . '</td>';
            echo '<td valign="top">' . $row['nm_poli'] . '</td>';
            echo '<td valign="top" align="center">' . strtolower($row['stts_daftar']) . '</td>';
            echo '<td valign="top" align="center">' . strtolower($row['status_poli']) . '</td>';
            echo '<td valign="top" align="center">' . $pembayaran . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        exit;
    }
}
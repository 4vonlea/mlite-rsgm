<?php
$file = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php';
$content = file_get_contents($file);

$search = <<<EOD
        \$id = \$_GET['id'] ?? '';
        \$data = [];
        if (!empty(\$id)) {
EOD;

$replace = <<<EOD
        \$id = \$_GET['id'] ?? '';
        \$data = [
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
        if (!empty(\$id)) {
EOD;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "done";

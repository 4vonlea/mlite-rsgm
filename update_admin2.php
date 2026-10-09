<?php
$file = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php';
$content = file_get_contents($file);

// Remove the ri02a manage, form, save, hapus methods
$content = preg_replace('/public function getRi02amanage.*?public function getRi02acetak/s', 'public function getRi02acetak', $content);

// Update getRi02acetak
$old_acetak = <<<EOD
public function getRi02acetak() {
        if (!\$id = \$_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        \$data = \$this->db('ri02a_pulang_paksa')->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02a_pulang_paksa.no_rawat')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('ri02a_pulang_paksa.id', \$id)->select('ri02a_pulang_paksa.*, reg_periksa.no_rkm_medis, pasien.*')->oneArray();
        echo \$this->draw('ri02a/cetak.html', ['data' => \$data]); exit();
    }
EOD;

$new_acetak = <<<EOD
public function getRi02acetak() {
        if (!\$id = \$_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        \$data = \$this->db('ri02_ringkasan')->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02_ringkasan.no_rawat')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('ri02_ringkasan.id', \$id)->select('ri02_ringkasan.*, ri02_ringkasan.tgl_keluar as tanggal_jam, reg_periksa.no_rkm_medis, pasien.*')->oneArray();
        echo \$this->draw('ri02/cetak_02a.html', ['data' => \$data]); exit();
    }
EOD;
$content = str_replace($old_acetak, $new_acetak, $content);

// Remove getRi02bmanage
$content = preg_replace('/public function getRi02bmanage.*?public function getRi02bcetak/s', 'public function getRi02bcetak', $content);

// Update getRi02bcetak
$old_bcetak = <<<EOD
public function getRi02bcetak() {
        if (!\$no_rawat = \$_GET['no_rawat'] ?? '') { exit("No Rawat tidak ditemukan"); }
        \$data = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')->where('reg_periksa.no_rawat', \$no_rawat)->select('reg_periksa.*, pasien.*, kamar_inap.kd_kamar, kamar.kelas')->oneArray();
        echo \$this->draw('ri02b/cetak.html', ['data' => \$data]); exit();
    }
EOD;

$new_bcetak = <<<EOD
public function getRi02bcetak() {
        if (!\$no_rawat = \$_GET['no_rawat'] ?? '') { exit("No Rawat tidak ditemukan"); }
        \$data = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')->where('reg_periksa.no_rawat', \$no_rawat)->select('reg_periksa.*, pasien.*, kamar_inap.kd_kamar, kamar.kelas')->oneArray();
        
        // Calculate umur if not exists
        if(empty(\$data['umur'])) { \$data['umur'] = date_diff(date_create(\$data['tgl_lahir']), date_create('today'))->y . ' th'; }

        echo \$this->draw('ri02/cetak_02b.html', ['data' => \$data]); exit();
    }
EOD;
$content = str_replace($old_bcetak, $new_bcetak, $content);

file_put_contents($file, $content);
echo "done";
?>

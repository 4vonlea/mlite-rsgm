<?php
$file = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php';
$content = file_get_contents($file);

$search_nav = "'RM.RI 01B - HAK DAN KEWAJIBAN PASIEN' => 'ri01bmanage',";
if (strpos($content, "'RM.RI 02 - ") === false) {
    $replace_nav = $search_nav . "\n                'RM.RI 02 - RINGKASAN MASUK DAN KELUAR' => 'ri02manage',\n                'RM.RI 02A - PEMBEBASAN TANGGUNG JAWAB PASIEN PULANG PAKSA' => 'ri02amanage',\n                'RM.RI 02B - LEMBAR UNTUK MENEMPEL SURAT' => 'ri02bmanage',";
    $content = str_replace($search_nav, $replace_nav, $content);
}

$pos = strrpos($content, '}');
if ($pos !== false) {
    $content = substr_replace($content, '', $pos, 1);
}

$new_methods = <<<EOD
    // ============================================================
    // RM.RI 02 - RINGKASAN MASUK DAN KELUAR
    // ============================================================
    public function getRi02manage() {
        \$this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        \$this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        \$this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        \$data = \$this->db('ri02_ringkasan')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02_ringkasan.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('ri02_ringkasan.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('ri02_ringkasan.id')->toArray();
        return \$this->draw('ri02/manage.html', ['data' => \$data]);
    }
    public function getRi02form() {
        if (isset(\$_GET['ajax_patient'])) {
            \$no_rawat = trim(\$_GET['no_rawat'] ?? '');
            if (is_numeric(\$no_rawat) && strlen(\$no_rawat) < 6) { \$no_rawat = str_pad(\$no_rawat, 6, '0', STR_PAD_LEFT); }
            \$result = ['success' => false];
            if (!empty(\$no_rawat)) {
                \$reg = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', \$no_rawat)->orWhere('reg_periksa.no_rkm_medis', \$no_rawat)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur, pasien.gol_darah, pasien.alamat, pasien.pekerjaan, pasien.pnd, pasien.agama, pasien.stts_nikah')
                    ->desc('reg_periksa.tgl_registrasi')->desc('reg_periksa.jam_reg')->oneArray();
                if (\$reg) { \$result = array_merge(['success' => true], \$reg); }
            }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json'); echo json_encode(\$result); exit();
        }
        \$id = \$_GET['id'] ?? '';
        \$data = [];
        if (!empty(\$id)) {
            \$data = \$this->db('ri02_ringkasan')->where('id', \$id)->oneArray();
            if (\$data && !empty(\$data['no_rawat'])) {
                \$reg = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', \$data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah, pasien.umur, pasien.alamat, pasien.pnd, pasien.agama, pasien.pekerjaan, pasien.stts_nikah')->oneArray();
                if (\$reg) { \$data = array_merge(\$data, \$reg); }
            }
        }
        return \$this->draw('ri02/form.html', ['data' => \$data]);
    }
    public function postRi02save() {
        \$id = \$_POST['id'] ?? '';
        \$data = \$_POST; unset(\$data['id']); unset(\$data['t']); unset(\$data['ajax_patient']);
        if (\$id) { \$this->db('ri02_ringkasan')->where('id', \$id)->save(\$data); \$this->notify('success', 'Data diupdate'); } 
        else { \$this->db('ri02_ringkasan')->save(\$data); \$this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02manage']));
    }
    public function getRi02hapus() {
        if (\$id = \$_GET['id'] ?? '') { \$this->db('ri02_ringkasan')->where('id', \$id)->delete(); \$this->notify('success', 'Dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02manage']));
    }
    public function getRi02cetak() {
        if (!\$id = \$_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        \$data = \$this->db('ri02_ringkasan')->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02_ringkasan.no_rawat')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('ri02_ringkasan.id', \$id)->select('ri02_ringkasan.*, reg_periksa.no_rkm_medis, pasien.*')->oneArray();
        echo \$this->draw('ri02/cetak.html', ['data' => \$data]); exit();
    }

    // ============================================================
    // RM.RI 02A - PEMBEBASAN TANGGUNG JAWAB PASIEN PULANG PAKSA
    // ============================================================
    public function getRi02amanage() {
        \$this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        \$this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        \$this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        \$data = \$this->db('ri02a_pulang_paksa')->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02a_pulang_paksa.no_rawat')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->select('ri02a_pulang_paksa.*, reg_periksa.no_rkm_medis, pasien.nm_pasien')->desc('ri02a_pulang_paksa.id')->toArray();
        return \$this->draw('ri02a/manage.html', ['data' => \$data]);
    }
    public function getRi02aform() {
        if (isset(\$_GET['ajax_patient'])) { 
            \$no_rawat = trim(\$_GET['no_rawat'] ?? '');
            if (is_numeric(\$no_rawat) && strlen(\$no_rawat) < 6) { \$no_rawat = str_pad(\$no_rawat, 6, '0', STR_PAD_LEFT); }
            \$result = ['success' => false];
            if (!empty(\$no_rawat)) {
                \$reg = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', \$no_rawat)->orWhere('reg_periksa.no_rkm_medis', \$no_rawat)->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')->desc('reg_periksa.tgl_registrasi')->oneArray();
                if (\$reg) { \$result = array_merge(['success' => true], \$reg); }
            }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json'); echo json_encode(\$result); exit();
        }
        \$id = \$_GET['id'] ?? ''; \$data = [];
        if (!empty(\$id)) {
            \$data = \$this->db('ri02a_pulang_paksa')->where('id', \$id)->oneArray();
            if (\$data && !empty(\$data['no_rawat'])) {
                \$reg = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.no_rawat', \$data['no_rawat'])->select('reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')->oneArray();
                if (\$reg) { \$data = array_merge(\$data, \$reg); }
            }
        }
        return \$this->draw('ri02a/form.html', ['data' => \$data]);
    }
    public function postRi02asave() {
        \$id = \$_POST['id'] ?? '';
        \$data = ['no_rawat' => \$_POST['no_rawat']??'', 'tanggal_jam' => date('Y-m-d H:i:s'), 'nama_wali' => \$_POST['nama_wali']??'', 'saksi' => \$_POST['saksi']??'', 'wali_adalah' => \$_POST['wali_adalah']??''];
        if (\$id) { \$this->db('ri02a_pulang_paksa')->where('id', \$id)->save(\$data); \$this->notify('success', 'Data diupdate'); } else { \$this->db('ri02a_pulang_paksa')->save(\$data); \$this->notify('success', 'Data disimpan'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02amanage']));
    }
    public function getRi02ahapus() {
        if (\$id = \$_GET['id'] ?? '') { \$this->db('ri02a_pulang_paksa')->where('id', \$id)->delete(); \$this->notify('success', 'Dihapus'); }
        redirect(url([ADMIN, 'update_bmt', 'ri02amanage']));
    }
    public function getRi02acetak() {
        if (!\$id = \$_GET['id'] ?? '') { exit("ID tidak ditemukan"); }
        \$data = \$this->db('ri02a_pulang_paksa')->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ri02a_pulang_paksa.no_rawat')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('ri02a_pulang_paksa.id', \$id)->select('ri02a_pulang_paksa.*, reg_periksa.no_rkm_medis, pasien.*')->oneArray();
        echo \$this->draw('ri02a/cetak.html', ['data' => \$data]); exit();
    }

    // ============================================================
    // RM.RI 02B - LEMBAR UNTUK MENEMPEL SURAT
    // ============================================================
    public function getRi02bmanage() {
        \$this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        \$this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        \$this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        \$data = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, reg_periksa.tgl_registrasi')->desc('reg_periksa.tgl_registrasi')->limit(200)->toArray();
        return \$this->draw('ri02b/manage.html', ['data' => \$data]);
    }
    public function getRi02bcetak() {
        if (!\$no_rawat = \$_GET['no_rawat'] ?? '') { exit("No Rawat tidak ditemukan"); }
        \$data = \$this->db('reg_periksa')->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->leftJoin('kamar_inap', 'kamar_inap.no_rawat = reg_periksa.no_rawat')->leftJoin('kamar', 'kamar.kd_kamar = kamar_inap.kd_kamar')->where('reg_periksa.no_rawat', \$no_rawat)->select('reg_periksa.*, pasien.*, kamar_inap.kd_kamar, kamar.kelas')->oneArray();
        echo \$this->draw('ri02b/cetak.html', ['data' => \$data]); exit();
    }
EOD;

$content .= "\n" . $new_methods . "\n}\n";
file_put_contents($file, $content);
echo "done";

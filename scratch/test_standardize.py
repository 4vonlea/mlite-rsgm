import re

with open('plugins/update_bmt/Admin.php', 'r') as f:
    content = f.read()

# Pattern for the 10 lampiran methods that need kop_img and ob_clean:
# 1. getCairaninfuscetak
# 2. getFormamppcetak
# 3. getStatusfungsionalulangcetak
# 4. getFormbmppcetak
# 5. getKebutuhanprivasicetak
# 6. getMeninggalkanrscetak
# 7. getPenghentianpengobatancetak
# 8. getPrmrjcetak
# 9. getSkriningluarrscetak
# 10. getStabilisasipasiencetak

replacements = [
    # 1. Cairan infus
    (
        r"(\$data\['rendered_rows'\]\s*=\s*\$rendered_rows;\s*)(// Cek jika request export mPDF\s*if \(file_exists\(BASE_DIR \. '/vendor/autoload\.php'\)\) \{\s*require_once BASE_DIR \. '/vendor/autoload\.php';\s*\}\s*if \(isset\(\$_GET\['pdf'\]\) && \$_GET\['pdf'\] == '1' && class_exists\('\\Mpdf\\Mpdf'\)\) \{\s*try \{\s*\$mpdf = new \\Mpdf\\Mpdf\(\[[^\]]+\]\);\s*)(\$html\s*=\s*\$this->draw\('lampiran_cairan_infus/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);\s*\$mpdf->WriteHTML\(\$html\);\s*(\$filename\s*=\s*'Lampiran_Cairan_Infus_[^;]+;\s*\$mpdf->Output\(\$filename,\s*'I'\);\s*exit\(\);\s*\}\s*catch\s*\(\\Throwable\s*\$e\)\s*\{\s*//[^\n]*\s*\}\s*\}\s*)(echo\s*\$this->draw\('lampiran_cairan_infus/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);\s*exit\(\);)",
        r"""\1\2@ini_set('display_errors', '0');
                $data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('lampiran_cairan_infus/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => true]);
                $mpdf->WriteHTML($html);
                if (ob_get_length()) { ob_clean(); }
                \4$data['kop_img'] = url('assets/img/KOP-SURAT.png');
        echo $this->draw('lampiran_cairan_infus/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);
        exit();"""
    ),
]

import re

with open('plugins/update_bmt/Admin.php', 'r') as f:
    content = f.read()

# 1. Update the 10 lampiran methods:
# getCairaninfuscetak
# getFormamppcetak
# getStatusfungsionalulangcetak
# getFormbmppcetak
# getKebutuhanprivasicetak
# getMeninggalkanrscetak
# getPenghentianpengobatancetak
# getPrmrjcetak
# getSkriningluarrscetak
# getStabilisasipasiencetak

lampiran_updates = [
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_cairan_infus/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_form_a_mpp/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_asesmen_ulang_status_fungsional/cetak\.html',\s*\[\s*'p'\s*=>\s*\$p,\s*'rows'\s*=>\s*\$rows,\s*'katRows'\s*=>\s*\$katRows,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$p['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                $kop_img = $p['kop_img'];\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_form_b_mpp/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_kebutuhan_privasi_pasien/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_meninggalkan_rs_tertentu/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_penghentian_pengobatan/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_prmrj/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_skrining_luar_rs/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),
    (
        r"(\$html\s*=\s*\$this->draw\('lampiran_stabilisasi_pasien/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*true\s*\]\);)",
        r"$data['kop_img'] = BASE_DIR . '/assets/img/KOP-SURAT.png';\n                \1"
    ),

    # HTML Preview updates for the 10
    (
        r"(echo\s*\$this->draw\('lampiran_cairan_infus/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_cairan_infus/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_form_a_mpp/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_form_a_mpp/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_asesmen_ulang_status_fungsional/cetak\.html',\s*\[\s*'p'\s*=>\s*\$p,\s*'rows'\s*=>\s*\$rows,\s*'katRows'\s*=>\s*\$katRows,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$p['kop_img'] = url('assets/img/KOP-SURAT.png');\n        $kop_img = $p['kop_img'];\n        echo $this->draw('lampiran_asesmen_ulang_status_fungsional/cetak.html', ['p' => $p, 'kop_img' => $kop_img, 'rows' => $rows, 'katRows' => $katRows, 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_form_b_mpp/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_form_b_mpp/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_kebutuhan_privasi_pasien/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_kebutuhan_privasi_pasien/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_meninggalkan_rs_tertentu/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_meninggalkan_rs_tertentu/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_penghentian_pengobatan/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_penghentian_pengobatan/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_prmrj/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_prmrj/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_skrining_luar_rs/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_skrining_luar_rs/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
    (
        r"(echo\s*\$this->draw\('lampiran_stabilisasi_pasien/cetak\.html',\s*\[\s*'data'\s*=>\s*\$data,\s*'is_mpdf'\s*=>\s*false\s*\]\);)",
        r"$data['kop_img'] = url('assets/img/KOP-SURAT.png');\n        echo $this->draw('lampiran_stabilisasi_pasien/cetak.html', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => false]);"
    ),
]

for pat, rep in lampiran_updates:
    if re.search(pat, content):
        content = re.sub(pat, rep, content, count=1)
        print("Replaced:", pat[:40])
    else:
        print("Not matched:", pat[:40])

# 2. Add @ini_set('display_errors', '0'); before new \Mpdf\Mpdf if not present
def add_ini_set(match):
    prefix = match.group(1)
    mpdf_call = match.group(2)
    if 'display_errors' in prefix:
        return match.group(0)
    indent = re.search(r'\n([ \t]*)$', prefix)
    ind = indent.group(1) if indent else '            '
    return prefix + "@ini_set('display_errors', '0');\n" + ind + mpdf_call

content = re.sub(r'([^\n]+\n)([ \t]*\$mpdf\s*=\s*new\s*\\Mpdf\\Mpdf)', add_ini_set, content)

# 3. Add if (ob_get_length()) { ob_clean(); } before $mpdf->Output if not present
def add_ob_clean(match):
    prefix = match.group(1)
    output_call = match.group(2)
    if 'ob_clean' in prefix:
        return match.group(0)
    indent = re.search(r'\n([ \t]*)$', prefix)
    ind = indent.group(1) if indent else '            '
    return prefix + "if (ob_get_length()) {\n" + ind + "    ob_clean();\n" + ind + "}\n" + ind + output_call

content = re.sub(r'([^\n]+\n)([ \t]*\$mpdf->Output\()', add_ob_clean, content)

with open('plugins/update_bmt/Admin.php', 'w') as f:
    f.write(content)

print("Admin.php successfully updated and saved.")

import re

ADMIN_FILE = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php'

with open(ADMIN_FILE, 'r', encoding='utf-8') as f:
    content = f.read()

# Define methods to upgrade in Batches 2, 3, 4:
UPGRADES = [
    # Batch 2: Bedah & UBS
    {
        "method": "getBm1cetak",
        "code": "BM1",
        "tpl": "bm1/cetak.html",
        "target": "echo $this->draw('bm1/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getBm2cetak",
        "code": "BM2",
        "tpl": "bm2/cetak.html",
        "target": "echo $this->draw('bm2/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getBm4cetak",
        "code": "BM4",
        "tpl": "bm4/cetak.html",
        "target": "echo $this->draw('bm4/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getBmi1cetak",
        "code": "BMI1",
        "tpl": "bmi1/cetak.html",
        "target": "echo $this->draw('bmi1/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getBmi4cetak",
        "code": "BMI4",
        "tpl": "bmi4/cetak.html",
        "target": "echo $this->draw('bmi4/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getBmi5cetak",
        "code": "BMI5",
        "tpl": "bmi5/cetak.html",
        "target": "echo $this->draw('bmi5/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getPulangapscetak",
        "code": "PULANGAPS",
        "tpl": "pulangaps/cetak.html",
        "target": "echo $this->draw('pulangaps/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },

    # Batch 3: IGD & Terintegrasi
    {
        "method": "anyCetak",
        "code": "RM_GD4",
        "tpl": "akgmu/cetak.html",
        "target": "echo $this->draw('akgmu/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "anyEdukasicetak",
        "code": "EDUKASI",
        "tpl": "edukasipasienter/cetak.html",
        "target": "echo $this->draw('edukasipasienter/cetak.html', ['data' => $data, 'detail' => $detail]);\n            exit();",
        "type": "data_detail"
    },
    {
        "method": "getAmucetak",
        "code": "AMU",
        "tpl": "asesmen_medis_umum_irna/cetak.html",
        "target": "echo $this->draw('asesmen_medis_umum_irna/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },

    # Batch 4: Ranap & Rajal
    {
        "method": "getRi01bcetak",
        "code": "RM_RI01B",
        "tpl": "ri01b/cetak.html",
        "target": "echo $this->draw('ri01b/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getRi02cetak",
        "code": "RM_RI02",
        "tpl": "ri02/cetak.html",
        "target": "echo $this->draw('ri02/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getRi03cetak",
        "code": "RM_RI03",
        "tpl": "ri03/cetak.html",
        "target": "echo $this->draw('ri03/cetak.html', ['data' => $data]);\n        exit();",
        "type": "data"
    },
    {
        "method": "getRi04cetak",
        "code": "RM_RI04",
        "tpl": "ri04/cetak.html",
        "target": "echo $this->draw('ri04/cetak.html', ['p' => $p]); exit();",
        "type": "p"
    },
    {
        "method": "getRi09cetak",
        "code": "RM_RI09",
        "tpl": "ri09/cetak.html",
        "target": "echo $this->draw('ri09/cetak.html', ['p' => $p, 'rows' => $rows, 'katRows' => $katRows]); exit();",
        "type": "ri09"
    },
    {
        "method": "getRi11bcetak",
        "code": "RM_RI11B",
        "tpl": "ri11b/cetak.html",
        "target": "echo $this->draw('ri11b/cetak.html', ['p' => $p, 'p1' => $page1_rows, 'p2' => $page2_rows]); exit();",
        "type": "ri11b"
    },
    {
        "method": "getRi14cetak",
        "code": "RM_RI14",
        "tpl": "ri14/cetak.html",
        "target": "echo $this->draw('ri14/cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRi15cetak",
        "code": "RM_RI15",
        "tpl": "ri15/cetak.html",
        "target": "echo $this->draw('ri15/cetak.html', ['p' => $p]); exit();",
        "type": "p"
    },
    {
        "method": "getRi15acetak",
        "code": "RM_RI15A",
        "tpl": "ri15a/ri15a_cetak.html",
        "target": "echo $this->draw('ri15a/ri15a_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRi15bcetak",
        "code": "RM_RI15B",
        "tpl": "ri15b/ri15b_cetak.html",
        "target": "echo $this->draw('ri15b/ri15b_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRi18cetak",
        "code": "RM_RI18",
        "tpl": "ri18/ri18_cetak.html",
        "target": "echo $this->draw('ri18/ri18_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRi19cetak",
        "code": "RM_RI19",
        "tpl": "ri19/ri19_cetak.html",
        "target": "echo $this->draw('ri19/ri19_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRi20cetak",
        "code": "RM_RI20",
        "tpl": "ri20/ri20_cetak.html",
        "target": "echo $this->draw('ri20/ri20_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRj02acetak",
        "code": "RM_RJ02A",
        "tpl": "rj02a/rj02a_cetak.html",
        "target": "echo $this->draw('rj02a/rj02a_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRj07cetak",
        "code": "RM_RJ07",
        "tpl": "rj07/rj07_cetak.html",
        "target": "echo $this->draw('rj07/rj07_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRj10cetak",
        "code": "RM_RJ10",
        "tpl": "rj10/rj10_cetak.html",
        "target": "echo $this->draw('rj10/rj10_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    },
    {
        "method": "getRj11cetak",
        "code": "RM_RJ11",
        "tpl": "rj11/rj11_cetak.html",
        "target": "echo $this->draw('rj11/rj11_cetak.html', ['p' => $p, 'd' => $d]); exit();",
        "type": "p_d"
    }
]

def make_replacement(item):
    code = item["code"]
    tpl = item["tpl"]
    t = item["type"]

    if t == "data":
        return f"""$data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('{tpl}', ['data' => $data, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {{
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('{code}_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
            exit();
        }}
        echo $html;
        exit();"""
    elif t == "data_detail":
        return f"""$data['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
            $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
            $html = $this->draw('{tpl}', ['data' => $data, 'detail' => $detail, 'kop_img' => $data['kop_img'], 'is_mpdf' => $is_mpdf]);
            if ($is_mpdf) {{
                $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
                $mpdf->WriteHTML($html);
                $mpdf->Output('{code}_' . ($data['no_rkm_medis'] ?? 'dokumen') . '.pdf', 'I');
                exit();
            }}
            echo $html;
            exit();"""
    elif t == "p":
        return f"""$p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('{tpl}', ['p' => $p, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {{
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('{code}_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }}
        echo $html;
        exit();"""
    elif t == "p_d":
        return f"""$p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('{tpl}', ['p' => $p, 'd' => $d, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {{
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('{code}_' . ($d['no_rkm_medis'] ?? ($p['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }}
        echo $html;
        exit();"""
    elif t == "ri09":
        return f"""$p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('{tpl}', ['p' => $p, 'rows' => $rows, 'katRows' => $katRows, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {{
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('{code}_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }}
        echo $html;
        exit();"""
    elif t == "ri11b":
        return f"""$p['kop_img'] = !empty($_GET['pdf']) ? (defined('BASE_DIR') ? BASE_DIR . '/assets/img/KOP-SURAT.png' : url('assets/img/KOP-SURAT.png')) : url('assets/img/KOP-SURAT.png');
        $is_mpdf = !empty($_GET['pdf']) && $_GET['pdf'] == '1';
        $html = $this->draw('{tpl}', ['p' => $p, 'p1' => $page1_rows, 'p2' => $page2_rows, 'kop_img' => $p['kop_img'], 'is_mpdf' => $is_mpdf]);
        if ($is_mpdf) {{
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10]);
            $mpdf->WriteHTML($html);
            $mpdf->Output('{code}_' . ($p['no_rkm_medis'] ?? ($data['no_rkm_medis'] ?? 'dokumen')) . '.pdf', 'I');
            exit();
        }}
        echo $html;
        exit();"""

updated_count = 0
for item in UPGRADES:
    target = item["target"]
    if target in content:
        repl = make_replacement(item)
        content = content.replace(target, repl, 1)
        print(f"Upgraded {item['method']}: SUCCESS")
        updated_count += 1
    else:
        print(f"Target NOT FOUND for {item['method']}")

with open(ADMIN_FILE, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"\nTotal upgraded methods in Admin.php: {updated_count}/{len(UPGRADES)}")

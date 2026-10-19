import os
import re

BASE_DIR = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm'
ADMIN_PHP = os.path.join(BASE_DIR, 'plugins/update_bmt/Admin.php')
VIEW_ADMIN_DIR = os.path.join(BASE_DIR, 'plugins/update_bmt/view/admin')

TOOLBAR_CSS = """
{if: empty($is_mpdf)}
@media screen {
    body { background-color: #f4f6f9 !important; padding: 15px 0 !important; }
    .container, .page, .main-box, .main-container { background: #fff !important; box-shadow: 0 2px 10px rgba(0,0,0,0.15) !important; margin: 0 auto 20px auto !important; padding: 8mm 10mm !important; }
}
{/if}
.no-print-toolbar { width: 100%; max-width: 195mm; margin: 0 auto 12px auto; padding: 8px 14px; background: #2c3e50; color: #fff; border-radius: 4px; box-sizing: border-box; }
.btn-toolbar { padding: 5px 12px; border-radius: 3px; border: none; cursor: pointer; font-weight: bold; font-size: 11px; text-decoration: none; display: inline-block; }
.btn-print { background: #27ae60; color: #fff; }
.btn-pdf { background: #e74c3c; color: #fff; margin-left: 5px; }
.btn-close { background: #7f8c8d; color: #fff; margin-left: 5px; }
@media print {
    .no-print-toolbar { display: none !important; }
    body { background: transparent !important; padding: 0 !important; }
    .container, .page, .main-box, .main-container { box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
}
"""

TOOLBAR_HTML = """
    {if: empty($is_mpdf)}
    <div class="no-print-toolbar">
        <button onclick="window.print()" class="btn-toolbar btn-print"><i class="fa fa-print"></i> Cetak Dokumen</button>
        <a href="#" onclick="window.open(window.location.href + (window.location.href.indexOf('?') !== -1 ? (window.location.href.indexOf('pdf=1') !== -1 ? '' : '&pdf=1') : '?pdf=1'), '_blank'); return false;" class="btn-toolbar btn-pdf"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
        <button onclick="window.close()" class="btn-toolbar btn-close"><i class="fa fa-times"></i> Tutup</button>
    </div>
    {/if}
"""

SAFE_KOP = 'src="{if: !empty($kop_img)}{$kop_img}{elseif: !empty($data.kop_img)}{$data.kop_img}{elseif: !empty($p.kop_img)}{$p.kop_img}{elseif: !empty($d.kop_img)}{$d.kop_img}{else}{?=url(\'assets/img/KOP-SURAT.png\')?}{/if}"'

def standardize_cetak_file(filepath):
    if not os.path.exists(filepath):
        return False
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    orig = content
    # 1. Toolbar CSS
    if 'no-print-toolbar' not in content:
        if '</style>' in content:
            content = content.replace('</style>', TOOLBAR_CSS + '\n</style>', 1)
        elif '</head>' in content:
            content = content.replace('</head>', f'<style>{TOOLBAR_CSS}</style>\n</head>', 1)

    # 2. Toolbar HTML
    if 'class="no-print-toolbar"' not in content:
        # Match <body ...>
        m = re.search(r'(<body[^>]*>)', content, re.IGNORECASE)
        if m:
            body_tag = m.group(1)
            content = content.replace(body_tag, body_tag + '\n' + TOOLBAR_HTML, 1)

    # 3. Remove auto print
    content = re.sub(r'onload=[\'"]window\.print\(\)[\'"]', '', content, flags=re.IGNORECASE)
    content = re.sub(r'<script>\s*window\.print\(\);\s*</script>', '', content, flags=re.IGNORECASE)

    # 4. Standardize Kop
    content = re.sub(r'src=[\'"]\{[^\'"]*?KOP-SURAT\.png[^\'"]*?\}[\'"]', SAFE_KOP, content, flags=re.IGNORECASE)
    # Also if src=".../assets/img/KOP-SURAT.png"
    content = re.sub(r'src=[\'"][^\'"]*?/assets/img/KOP-SURAT\.png[\'"]', SAFE_KOP, content, flags=re.IGNORECASE)

    if content != orig:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

def standardize_manage_file(filepath, cetak_route_name):
    if not os.path.exists(filepath):
        return False
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    orig = content
    
    # 1. Safe nama_pasien check in <td>
    # Replace {$value.nm_pasien} with {if: !empty($value.nama_pasien)}{$value.nama_pasien}{elseif: !empty($value.nm_pasien)}{$value.nm_pasien}{else}-{/if}
    if '<td>{$value.nm_pasien}</td>' in content:
        content = content.replace('<td>{$value.nm_pasien}</td>', '<td>{if: !empty($value.nama_pasien)}{$value.nama_pasien}{elseif: !empty($value.nm_pasien)}{$value.nm_pasien}{else}-{/if}</td>')
    if '<td>{$value.nama_pasien}</td>' in content:
        content = content.replace('<td>{$value.nama_pasien}</td>', '<td>{if: !empty($value.nama_pasien)}{$value.nama_pasien}{elseif: !empty($value.nm_pasien)}{$value.nm_pasien}{else}-{/if}</td>')

    # 2. Add Unduh PDF button if not present
    if 'pdf=1' not in content:
        # Find the cetak link and append the PDF button right next to it
        # Patterns of cetak links:
        # <a href="{?=url([ADMIN, 'update_bmt', 'Xcetak'])?}&t=...&id={$value.id}" ...>...</a>
        # <a href="{?=url([ADMIN, 'update_bmt', 'Xcetak', '?id='.$value.id])?}" ...>...</a>
        # <a href="{?=url([ADMIN, 'update_bmt', 'Xcetak'])?}&id={$value.id}" ...>...</a>
        
        # Regex search for the Cetak link
        pattern = r'(<a\s+[^>]*href=[\'"][^\'"]*?' + re.escape(cetak_route_name) + r'[^\'"]*?[\'"][^>]*>.*?</a>)'
        m = re.search(pattern, content, re.IGNORECASE | re.DOTALL)
        if m:
            cetak_link = m.group(1)
            # Create pdf link by modifying cetak_link
            pdf_link = cetak_link
            
            # Make sure it has &pdf=1 inside href
            if 'href="' in pdf_link:
                # check if there's '?id=' or '&id=' or route
                href_match = re.search(r'href="([^"]+)"', pdf_link)
                if href_match:
                    old_href = href_match.group(1)
                    if '?' in old_href:
                        new_href = old_href + '&pdf=1'
                    else:
                        new_href = old_href + '&pdf=1'
                    pdf_link = pdf_link.replace(f'href="{old_href}"', f'href="{new_href}"')
            elif "href='" in pdf_link:
                href_match = re.search(r"href='([^']+)'", pdf_link)
                if href_match:
                    old_href = href_match.group(1)
                    new_href = old_href + '&pdf=1'
                    pdf_link = pdf_link.replace(f"href='{old_href}'", f"href='{new_href}'")

            # Change class to btn-danger
            pdf_link = re.sub(r'class=[\'"][^\'"]*?[\'"]', 'class="btn btn-xs btn-danger"', pdf_link)
            # Change inner text/icon
            pdf_link = re.sub(r'>.*?</a>', '><i class="fa fa-file-pdf-o"></i> Unduh PDF</a>', pdf_link)

            # Insert pdf_link after cetak_link
            content = content.replace(cetak_link, cetak_link + '\n                                    ' + pdf_link, 1)

    # 3. Ensure DataTables
    if 'DataTable' not in content:
        content += """
<script>
    $(document).ready(function() {
        $('table.dataTable, table.table').first().DataTable();
    });
</script>
"""

    if content != orig:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True
    return False

# Target 33 forms configuration:
FORMS = [
    {"dir": "ans1", "cetak_route": "ans1cetak", "prefix": ""},
    {"dir": "ans2", "cetak_route": "ans2cetak", "prefix": ""},
    {"dir": "ans3", "cetak_route": "ans3cetak", "prefix": ""},
    {"dir": "ans4", "cetak_route": "ans4cetak", "prefix": ""},
    {"dir": "ans6", "cetak_route": "ans6cetak", "prefix": ""},
    {"dir": "ans7", "cetak_route": "ans7cetak", "prefix": ""},
    {"dir": "bm1", "cetak_route": "bm1cetak", "prefix": ""},
    {"dir": "bm2", "cetak_route": "bm2cetak", "prefix": ""},
    {"dir": "bm4", "cetak_route": "bm4cetak", "prefix": ""},
    {"dir": "bmi1", "cetak_route": "bmi1cetak", "prefix": ""},
    {"dir": "bmi4", "cetak_route": "bmi4cetak", "prefix": ""},
    {"dir": "bmi5", "cetak_route": "bmi5cetak", "prefix": ""},
    {"dir": "pulangaps", "cetak_route": "pulangapscetak", "prefix": ""},
    {"dir": "akgmu", "cetak_route": "cetak", "prefix": ""},
    {"dir": "edukasipasienter", "cetak_route": "edukasicetak", "prefix": ""},
    {"dir": "asesmen_medis_umum_irna", "cetak_route": "amucetak", "prefix": ""},
    {"dir": "ri01b", "cetak_route": "ri01bcetak", "prefix": ""},
    {"dir": "ri02", "cetak_route": "ri02cetak", "prefix": ""},
    {"dir": "ri03", "cetak_route": "ri03cetak", "prefix": ""},
    {"dir": "ri04", "cetak_route": "ri04cetak", "prefix": ""},
    {"dir": "ri09", "cetak_route": "ri09cetak", "prefix": ""},
    {"dir": "ri11b", "cetak_route": "ri11bcetak", "prefix": ""},
    {"dir": "ri14", "cetak_route": "ri14cetak", "prefix": ""},
    {"dir": "ri15", "cetak_route": "ri15cetak", "prefix": ""},
    {"dir": "ri15a", "cetak_route": "ri15acetak", "prefix": "ri15a_"},
    {"dir": "ri15b", "cetak_route": "ri15bcetak", "prefix": "ri15b_"},
    {"dir": "ri18", "cetak_route": "ri18cetak", "prefix": "ri18_"},
    {"dir": "ri19", "cetak_route": "ri19cetak", "prefix": "ri19_"},
    {"dir": "ri20", "cetak_route": "ri20cetak", "prefix": "ri20_"},
    {"dir": "rj02a", "cetak_route": "rj02acetak", "prefix": "rj02a_"},
    {"dir": "rj07", "cetak_route": "rj07cetak", "prefix": "rj07_"},
    {"dir": "rj10", "cetak_route": "rj10cetak", "prefix": "rj10_"},
    {"dir": "rj11", "cetak_route": "rj11cetak", "prefix": "rj11_"}
]

print("=== Standardizing cetak.html and manage.html for 33 forms ===")
for item in FORMS:
    d = item["dir"]
    prefix = item["prefix"]
    cetak_file = os.path.join(VIEW_ADMIN_DIR, d, f"{prefix}cetak.html")
    manage_file = os.path.join(VIEW_ADMIN_DIR, d, f"{prefix}manage.html")

    c_res = standardize_cetak_file(cetak_file)
    m_res = standardize_manage_file(manage_file, item["cetak_route"])

    print(f"{d:25s} | cetak: {'UPDATED' if c_res else 'OK'} | manage: {'UPDATED' if m_res else 'OK'}")

import os
import re

BASE_DIR = '/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm'
VIEW_ADMIN_DIR = os.path.join(BASE_DIR, 'plugins/update_bmt/view/admin')

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

for item in FORMS:
    d = item["dir"]
    prefix = item["prefix"]
    manage_file = os.path.join(VIEW_ADMIN_DIR, d, f"{prefix}manage.html")
    if not os.path.exists(manage_file):
        continue

    with open(manage_file, 'r', encoding='utf-8') as f:
        content = f.read()

    if 'pdf=1' in content:
        print(f"{d:25s} already has pdf=1")
        continue

    route = item["cetak_route"]
    
    # Match any <a href="..." ...>...</a> that contains the route name
    # We find '<a' then up to '</a>', checking if 'route' is in it
    links = list(re.finditer(r'<a\s+[^>]+>.*?</a>', content, flags=re.DOTALL | re.IGNORECASE))
    found = False
    for m in links:
        link_str = m.group(0)
        if route in link_str:
            # We found the cetak link!
            # Generate pdf link
            # Check how href is written
            href_match = re.search(r'href="([^"]+)"', link_str)
            if href_match:
                orig_href = href_match.group(1)
                # check if there is '?id=' inside url array or &id=
                new_href = orig_href + '&pdf=1'
                pdf_link = link_str.replace(f'href="{orig_href}"', f'href="{new_href}"')
            else:
                href_match2 = re.search(r"href='([^']+)'", link_str)
                if href_match2:
                    orig_href = href_match2.group(1)
                    new_href = orig_href + '&pdf=1'
                    pdf_link = link_str.replace(f"href='{orig_href}'", f"href='{new_href}'")
                else:
                    continue

            # Ensure class has btn-danger
            pdf_link = re.sub(r'class="[^"]+"', 'class="btn btn-xs btn-danger"', pdf_link)
            pdf_link = re.sub(r"class='[^']+'", 'class="btn btn-xs btn-danger"', pdf_link)
            # Ensure target="_blank"
            if 'target="_blank"' not in pdf_link:
                pdf_link = pdf_link.replace('<a ', '<a target="_blank" ', 1)
            # Replace text/icon with Unduh PDF
            pdf_link = re.sub(r'>.*?</a>$', '><i class="fa fa-file-pdf-o"></i> Unduh PDF</a>', pdf_link, flags=re.DOTALL)

            # Insert after link_str
            content = content.replace(link_str, link_str + '\n                                    ' + pdf_link, 1)
            found = True
            break

    if found:
        with open(manage_file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"{d:25s} => ADDED Unduh PDF button")
    else:
        print(f"{d:25s} => Cetak link NOT FOUND for route {route}")

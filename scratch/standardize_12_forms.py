import os
import re

forms = [
    "formulir_rujukan_rs", "general_consent_ranap", "kriteria_masuk_keluar_icu",
    "ri04_keperawatan_ranap", "ri05a_medis_ranap_gigi", "ri05c_dpjp", "ri06a_jatuh_dewasa",
    "ri07_observasi", "ringkasan_riwayat_klinik", "rj_jatuh_nyeri_anak", "surat_pengantar_ranap",
    "surat_pernyataan_jaminan_biaya"
]

for f in forms:
    p = os.path.join("plugins/form_rme/view/admin", f, "form.html")
    if not os.path.exists(p):
        continue
    with open(p, "r", errors="ignore") as fl:
        content = fl.read()

    # 1. Pastikan input nama pasien, nm_pasien, no_rkm_medis readonly
    # Pattern: <input ... name="nama_pasien" ...> or name="nm_pasien" or name="no_rkm_medis"
    for field in ["nama_pasien", "nm_pasien", "no_rkm_medis", "tgl_lahir"]:
        # Find input tags
        def make_readonly(m):
            tag = m.group(0)
            if "readonly" not in tag:
                tag = tag[:-1] + ' readonly style="background-color: #f8fafc; font-weight: 700;">'
            return tag

        content = re.sub(r'<input[^>]*name=["\']' + field + r'["\'][^>]*>', make_readonly, content)

    # 2. Pastikan no_rawat conditionally readonly when filled or from lembar kerja
    # In these forms, if there is a search button next to no_rawat:
    # Check if {if: !empty($data.no_rawat)} wrapper exists
    if "{if: !empty($data.no_rawat)}" not in content and 'name="no_rawat"' in content:
        # Find the no_rawat input or input-group
        m = re.search(r'(<div class="input-group"[^>]*>.*?name=["\']no_rawat["\'].*?<\/div>)', content, re.DOTALL)
        if m:
            full_grp = m.group(1)
            # Create conditional wrapper: if no_rawat is present, show clean readonly input without search button
            wrapped = '{if: !empty($data.no_rawat)}\n' \
                      '    <input type="text" name="no_rawat" id="no_rawat" class="form-control" value="{$data.no_rawat}" readonly style="background-color: #f8fafc; font-weight: bold; color: #1d4ed8;">\n' \
                      '{else}\n' \
                      '    ' + full_grp + '\n' \
                      '{/if}'
            content = content.replace(full_grp, wrapped)
            print(f"[{f}] Wrapped no_rawat input-group with conditional readonly")
        else:
            # Single input without input-group
            m2 = re.search(r'(<input[^>]*name=["\']no_rawat["\'][^>]*>)', content)
            if m2 and "readonly" not in m2.group(1):
                inp = m2.group(1)
                wrapped = '{if: !empty($data.no_rawat)}\n' \
                          '    <input type="text" name="no_rawat" id="no_rawat" class="form-control" value="{$data.no_rawat}" readonly style="background-color: #f8fafc; font-weight: bold; color: #1d4ed8;">\n' \
                          '{else}\n' \
                          '    ' + inp + '\n' \
                          '{/if}'
                content = content.replace(inp, wrapped)
                print(f"[{f}] Wrapped single no_rawat input with conditional readonly")

    with open(p, "w") as fl:
        fl.write(content)

print("Standardized patient demographic inputs in all 12 forms!")

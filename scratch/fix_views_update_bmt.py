import os
import re

folders = [
    "cppt", "formulir_rujukan_rs", "general_consent_ranap", "kriteria_masuk_keluar_icu",
    "ri04_keperawatan_ranap", "ri05a_medis_ranap_gigi", "ri05c_dpjp", "ri06a_jatuh_dewasa",
    "ri07_observasi", "ringkasan_riwayat_klinik", "rj_jatuh_nyeri_anak", "surat_pengantar_ranap",
    "surat_pernyataan_jaminan_biaya"
]

total_replaced = 0
for fld in folders:
    dir_path = os.path.join("plugins/form_rme/view/admin", fld)
    for root, dirs, files in os.walk(dir_path):
        for f in files:
            if f.endswith(".html"):
                p = os.path.join(root, f)
                with open(p, "r", errors="ignore") as fl:
                    content = fl.read()
                new_content = re.sub(r"\[ADMIN,\s*['\"]update_bmt['\"]", "[ADMIN, 'form_rme'", content)
                new_content = new_content.replace("/update_bmt/", "/form_rme/")
                if new_content != content:
                    total_replaced += 1
                    with open(p, "w") as fl:
                        fl.write(new_content)
                    print(f"Replaced update_bmt in {p}")

print(f"Total files updated: {total_replaced}")

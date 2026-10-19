import re

with open('plugins/update_bmt/Admin.php', 'r') as f:
    content = f.read()

targets = [
    'getPenilaianphlebitiscetak',
    'getKonsultasiinternalcetak',
    'getMonitoringpasienselamatransfercetak',
    'getPenilaiandekubituscetak',
    'getPenilaianilocetak',
    'getPenilaianiskcetak',
    'getBersediamenunggucetak',
    'getPenundaankelambatancetak',
    'getPersetujuantestinghivcetak',
    'getVctpratestinghivcetak'
]

for t in targets:
    # Pattern matching: public function target($id)\s*\{
    pattern = rf'public function {t}\s*\(\$id\)\s*\{{'
    replacement = f"""public function {t}($id = null)
    {{
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {{
            echo "ID tidak ditemukan.";
            exit();
        }}"""
    if re.search(pattern, content):
        content = re.sub(pattern, replacement, content, count=1)
        print("Updated:", t)
    else:
        print("Failed to match:", t)

with open('plugins/update_bmt/Admin.php', 'w') as f:
    f.write(content)

print("Admin.php updated.")

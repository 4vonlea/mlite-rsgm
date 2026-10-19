import re

with open('plugins/update_bmt/Admin.php', 'r') as f:
    text = f.read()

pattern = r'public function (get\w+cetak|any\w+cetak)\s*\(.*?\).*?(?=public function|\Z)'
matches = re.finditer(pattern, text, re.DOTALL)

results = []
for m in matches:
    name = m.group(1)
    body = m.group(0)
    has_mpdf = 'Mpdf' in body
    has_base_kop = 'BASE_DIR' in body and 'KOP-SURAT' in body
    results.append((name, has_mpdf, has_base_kop))

print(f'Total cetak methods: {len(results)}')
missing = [r[0] for r in results if not r[2]]
print(f'Methods without BASE_DIR KOP-SURAT: {len(missing)}')
for m in missing:
    print(' -', m)

import re

issues = [
    'getKebutuhanprivasicetak',
    'getPenilaianphlebitiscetak',
    'getPelayanankerohanicetak',
    'getKonsultasiinternalcetak',
    'getMonitoringpasienselamatransfercetak',
    'getPenilaiandekubituscetak',
    'getPenilaianilocetak',
    'getPenilaianiskcetak',
    'getBersediamenunggucetak',
    'getPenundaankelambatancetak',
    'getPersetujuantestinghivcetak',
    'getVctpratestinghivcetak',
    'anyCetak',
    'getAns6cetak',
    'getRj11cetak'
]

with open('plugins/update_bmt/Admin.php') as f:
    text = f.read()

for m in issues:
    regex = r'public function ' + m + r'\s*\((.*?)\)\s*\{([\s\S]*?)(?=public function|\Z)'
    match = re.search(regex, text)
    if match:
        arg = match.group(1).strip()
        body = match.group(2)[:500]
        params = re.findall(r"\$_GET\['[^']+'\]", body)
        db_table = re.findall(r"->db\('[^']+'\)", body)
        print(f"{m:35s} => Arg: {arg:10s} DB: {str(db_table):30s} GET: {params}")
    else:
        print(f"{m:35s} => NOT FOUND")

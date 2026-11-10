import urllib.request
import urllib.parse
import re

BASE_URL = "http://localhost:8000"

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

opener = urllib.request.build_opener(NoRedirect)
data = urllib.parse.urlencode({'username': 'admin', 'password': 'admin', 'login': 'Sign In'}).encode('utf-8')

token = ''
cookie = ''
try:
    r = opener.open(f'{BASE_URL}/admin/index.php', data=data)
except urllib.error.HTTPError as e:
    loc = e.headers.get('Location', '')
    if 't=' in loc:
        token = loc.split('t=')[1]
    cookie = e.headers.get('Set-Cookie', '').split(';')[0]

if not token or not cookie:
    print("Could not obtain token or cookie. Exiting.")
    exit(1)

print(f"Logged in successfully! Token: {token}")

ROUTES = [
    # 24 Lampiran
    "statusfungsionalulangmanage", "cairaninfusmanage", "formamppmanage", "formbmppmanage",
    "intervensinyerimanage", "kebutuhanprivasimanage", "konsultasiinternalmanage",
    "meninggalkanrsmanage", "monitoringkateterurinmanage", "monitoringpasienselamatransfermanage",
    "penghentianpengobatanmanage", "penilaiandekubitusmanage", "penilaianilomanage",
    "penilaianiskmanage", "penilaianphlebitismanage", "penundaankelambatanmanage",
    "pelayanankerohanianmanage", "secondopinionmanage", "persetujuantestinghivmanage",
    "prmrjmanage", "skriningluarrsmanage", "stabilisasipasienmanage",
    "bersediamenunggumanage", "vctpratestinghivmanage",
    # Anestesi
    "ans1manage", "ans2manage", "ans3manage", "ans4manage", "ans6manage", "ans7manage",
    # Bedah
    "bm1manage", "bm2manage", "bm4manage", "bmi1manage", "bmi4manage", "bmi5manage", "pulangapsmanage",
    # IGD & Irna
    "manage", "edukasimanage", "amumanage",
    # Ranap
    "ri01bmanage", "ri02manage", "ri03manage", "ri04manage", "ri09manage", "ri11bmanage", "ri14manage", "ri15manage",
    "ri15amanage", "ri15bmanage", "ri18manage", "ri19manage", "ri20manage",
    # Rajal
    "rj02amanage", "rj07manage", "rj10manage", "rj11manage"
]

print(f"\n=== TESTING ALL {len(ROUTES)} MANAGE PAGES VIA LIVE HTTP REQUEST ===")
success = 0
failed = 0

for route in ROUTES:
    url = f"{BASE_URL}/admin/update_bmt/{route}?t={token}"
    try:
        req = urllib.request.Request(url, headers={'Cookie': cookie})
        resp = urllib.request.urlopen(req, timeout=10)
        body = resp.read().decode('utf-8', errors='ignore')

        has_error = "Fatal error" in body or "Parse error" in body or "Notice:" in body or "Warning:" in body or "Exception" in body
        has_pdf_btn = "pdf=1" in body
        has_data_table = "dataTable" in body or "data-table" in body or "table" in body

        if resp.status == 200 and not has_error:
            success += 1
            print(f"[OK 200] {route:35s} | PDF_Btn: {'YES' if has_pdf_btn else 'NO'} | Size: {len(body):6d} B")
        else:
            failed += 1
            print(f"[FAIL {resp.status}] {route:35s} | Has error: {has_error}")
            if has_error:
                for line in body.splitlines():
                    if any(x in line.lower() for x in ['fatal error', 'parse error', 'warning:', 'notice:', 'exception']):
                        print("    ->", line.strip()[:140])
    except Exception as e:
        failed += 1
        print(f"[ERR] {route:35s} => {e}")

print(f"\n=======================================================")
print(f"LIVE TEST FINAL RESULT: {success} Passed, {failed} Failed out of {len(ROUTES)} routes.")
print(f"=======================================================")

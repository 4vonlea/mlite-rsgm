import urllib.request, urllib.parse, re, sys

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

opener = urllib.request.build_opener(NoRedirect)
data = urllib.parse.urlencode({'username': 'admin', 'password': 'admin', 'login': 'Sign In'}).encode('utf-8')
try:
    r = opener.open('http://localhost:8000/admin/index.php', data=data)
except urllib.error.HTTPError as e:
    loc = e.headers.get('Location', '')
    token = loc.split('t=')[1]
    cookie = e.headers.get('Set-Cookie', '').split(';')[0]

with open('plugins/update_bmt/Admin.php') as f:
    text = f.read()

all_cetak = re.findall(r'public function (\w+cetak)\s*\(', text, re.IGNORECASE)
# Remove duplicates preserving order
methods = []
for m in all_cetak:
    if m not in methods:
        methods.append(m)

print(f"Total methods to test: {len(methods)}")
success_count = 0
fail_count = 0

for idx, m in enumerate(methods, 1):
    # Determine route name (strip get/any and lowercase)
    route = m
    if route.startswith('get'):
        route = route[3:]
    elif route.startswith('any'):
        route = route[3:]
    route = route.lower()
    if route == 'cetak':
        route = 'cetak' # for akgmu

    # Test HTML
    url_html = f'http://localhost:8000/admin/update_bmt/{route}?t={token}&id=1'
    req_html = urllib.request.Request(url_html, headers={'Cookie': cookie})
    try:
        res_html = urllib.request.urlopen(req_html, timeout=10)
        body_html = res_html.read().decode('utf-8', errors='ignore')
        has_tb = 'no-print-toolbar' in body_html
        has_kop = 'KOP-SURAT.png' in body_html or 'kop_img' in body_html
    except Exception as ex:
        print(f"[{idx:2d}/57] {m:32s} (route: {route}) HTML FAIL: {ex}")
        fail_count += 1
        continue

    # Test PDF
    url_pdf = f'http://localhost:8000/admin/update_bmt/{route}?t={token}&id=1&pdf=1'
    req_pdf = urllib.request.Request(url_pdf, headers={'Cookie': cookie})
    try:
        res_pdf = urllib.request.urlopen(req_pdf, timeout=15)
        body_pdf = res_pdf.read()
        is_pdf = body_pdf.startswith(b'%PDF')
        pdf_len = len(body_pdf)
    except Exception as ex:
        print(f"[{idx:2d}/57] {m:32s} (route: {route}) PDF FAIL: {ex}")
        fail_count += 1
        continue

    if res_html.status == 200 and has_tb and res_pdf.status == 200 and is_pdf and pdf_len > 1000:
        print(f"[{idx:2d}/57] {m:32s} => OK (HTML Toolbar: {has_tb}, PDF: {is_pdf}, {pdf_len:,} B)")
        success_count += 1
    else:
        print(f"[{idx:2d}/57] {m:32s} => ISSUE (HTML: {res_html.status}, Toolbar: {has_tb}, PDF: {is_pdf}, Len: {pdf_len})")
        fail_count += 1

print(f"\nSummary: {success_count} Passed, {fail_count} Failed out of {len(methods)}")

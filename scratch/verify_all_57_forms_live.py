import urllib.request, urllib.parse, re, json, subprocess

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

# Query sample IDs for each form from database
php_query = """
define('BASE_DIR', __DIR__);
require 'config.php';
$pdo = new PDO('mysql:host='.DBHOST.';dbname='.DBNAME.';port='.DBPORT, DBUSER, DBPASS);

$map = [
    'getAmucetak' => ['table' => 'asesmen_medis_umum_irna', 'key' => 'id'],
    'getCairaninfuscetak' => ['table' => 'lampiran_cairan_infus', 'key' => 'id'],
    'getFormamppcetak' => ['table' => 'lampiran_form_a_mpp', 'key' => 'id'],
    'getStatusfungsionalulangcetak' => ['table' => 'lampiran_asesmen_ulang_status_fungsional', 'key' => 'id'],
    'getFormbmppcetak' => ['table' => 'lampiran_form_b_mpp', 'key' => 'id'],
    'getKebutuhanprivasicetak' => ['table' => 'lampiran_kebutuhan_privasi_pasien', 'key' => 'id'],
    'getMeninggalkanrscetak' => ['table' => 'lampiran_meninggalkan_rs_tertentu', 'key' => 'id'],
    'getPenghentianpengobatancetak' => ['table' => 'lampiran_penghentian_pengobatan', 'key' => 'id'],
    'getPrmrjcetak' => ['table' => 'lampiran_prmrj', 'key' => 'id'],
    'getSkriningluarrscetak' => ['table' => 'lampiran_skrining_luar_rs', 'key' => 'id'],
    'getStabilisasipasiencetak' => ['table' => 'lampiran_stabilisasi_pasien', 'key' => 'id'],
    'getIntervensinyericetak' => ['table' => 'lampiran_intervensi_pengkajian_ulang_nyeri', 'key' => 'id'],
    'getSecondopinioncetak' => ['table' => 'lampiran_permintaan_second_opinion', 'key' => 'id'],
    'getPenilaianphlebitiscetak' => ['table' => 'lampiran_penilaian_kejadian_phlebitis', 'key' => 'id'],
    'getPelayanankerohanicetak' => ['table' => 'lampiran_permintaan_pelayanan_kerohanian', 'key' => 'id'],
    'getMonitoringkateterurincetak' => ['table' => 'lampiran_monitoring_kateter_urin', 'key' => 'id'],
    'getKonsultasiinternalcetak' => ['table' => 'lampiran_konsultasi_internal', 'key' => 'id'],
    'getMonitoringpasienselamatransfercetak' => ['table' => 'lampiran_monitoring_pasien_selama_transfer', 'key' => 'id'],
    'getPenilaiandekubituscetak' => ['table' => 'lampiran_penilaian_dekubitus', 'key' => 'id'],
    'getPenilaianilocetak' => ['table' => 'lampiran_penilaian_kejadian_ilo', 'key' => 'id'],
    'getPenilaianiskcetak' => ['table' => 'lampiran_penilaian_kejadian_isk', 'key' => 'id'],
    'getBersediamenunggucetak' => ['table' => 'lampiran_surat_pernyataan_bersedia_menunggu', 'key' => 'id'],
    'getPenundaankelambatancetak' => ['table' => 'lampiran_penundaan_kelambatan_pelayanan', 'key' => 'id'],
    'getPersetujuantestinghivcetak' => ['table' => 'lampiran_persetujuan_testing_hiv', 'key' => 'id'],
    'getVctpratestinghivcetak' => ['table' => 'lampiran_vct_pra_testing_hiv', 'key' => 'id'],
    'anyEdukasicetak' => ['table' => 'edukasi_pasien_terintegrasi', 'key' => 'no_rawat'],
    'anyCetak' => ['table' => 'asesmen_keperawatan_gigi_ugd', 'key' => 'no_rawat'],
    'getAns1cetak' => ['table' => 'ans1_informasi_pembiusan', 'key' => 'id'],
    'getAns2cetak' => ['table' => 'ans2_persetujuan_pembiusan', 'key' => 'id'],
    'getAns3cetak' => ['table' => 'ans3_penolakan_pembiusan', 'key' => 'id'],
    'getAns4cetak' => ['table' => 'ans4_rencana_anestesi', 'key' => 'id'],
    'getAns6cetak' => ['table' => 'ans6_status_post_anestesi', 'key' => 'id'],
    'getAns7cetak' => ['table' => 'ans7_pasca_sedasi_rawat_jalan', 'key' => 'id'],
    'getBm1cetak' => ['table' => 'bm1_pra_bedah', 'key' => 'id'],
    'getBm2cetak' => ['table' => 'bm2_penandaan_lokasi_operasi', 'key' => 'id'],
    'getBm4cetak' => ['table' => 'bm4_laporan_pembedahan', 'key' => 'id'],
    'getBmi1cetak' => ['table' => 'bmi1_pengkajian_pra_operasi', 'key' => 'id'],
    'getBmi4cetak' => ['table' => 'bmi4_kebutuhan_edukasi_ruang_bedah', 'key' => 'id'],
    'getBmi5cetak' => ['table' => 'bmi5_safety_checklist', 'key' => 'id'],
    'getRi01bcetak' => ['table' => 'ri01b_penilaian_medis_awal', 'key' => 'id'],
    'getRi02cetak' => ['table' => 'ri02_asesmen_awal_keperawatan', 'key' => 'id'],
    'getRi03cetak' => ['table' => 'ri03_rekonsiliasi_obat', 'key' => 'id'],
    'getRi04cetak' => ['table' => 'ri04_pengkajian_pra_induksi', 'key' => 'id'],
    'getRi09cetak' => ['table' => 'ri09_catatan_observasi_cairan', 'key' => 'id'],
    'getRi11bcetak' => ['table' => 'ri11b_monitoring_pasca_anestesi', 'key' => 'id'],
    'getRi14cetak' => ['table' => 'ri14_catatan_konsultasi_anestesi', 'key' => 'id'],
    'getRi15cetak' => ['table' => 'ri15_catatan_perkembangan_terintegrasi', 'key' => 'id'],
    'getRi15acetak' => ['table' => 'ri15a_evaluasi_keperawatan', 'key' => 'id'],
    'getRi15bcetak' => ['table' => 'ri15b_lembar_konsultasi', 'key' => 'id'],
    'getRi18cetak' => ['table' => 'ri18_asesmen_ulang_gizi', 'key' => 'id'],
    'getRi19cetak' => ['table' => 'ri19_pengawasan_darah', 'key' => 'id'],
    'getRi20cetak' => ['table' => 'ri20_serah_terima_pasien', 'key' => 'id'],
    'getRj02acetak' => ['table' => 'rj02a_asesmen_awal_keperawatan_gigi', 'key' => 'id'],
    'getRj07cetak' => ['table' => 'rj07_surat_rujukan_eksternal', 'key' => 'id'],
    'getRj10cetak' => ['table' => 'rj10_surat_keterangan_dokter', 'key' => 'id'],
    'getRj11cetak' => ['table' => 'rj11_pemberian_informasi', 'key' => 'id'],
    'getPulangapscetak' => ['table' => 'pulangaps_penolakan_anjuran_medis', 'key' => 'id']
];

$res = [];
foreach ($map as $m => $cfg) {
    try {
        $row = $pdo->query("SELECT {$cfg['key']} as val FROM {$cfg['table']} ORDER BY {$cfg['key']} DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $res[$m] = ['key' => $cfg['key'], 'val' => $row ? $row['val'] : '1'];
    } catch (\Exception $e) {
        $res[$m] = ['key' => $cfg['key'], 'val' => '1'];
    }
}
echo json_encode($res);
"""

proc = subprocess.run(['php', '-r', php_query], cwd='.', capture_output=True, text=True)
db_map = json.loads(proc.stdout)

print(f"Loaded dynamic records map for {len(db_map)} methods.")

success_count = 0
fail_count = 0

for idx, (method, cfg) in enumerate(db_map.items(), 1):
    route = method
    if route.startswith('get'):
        route = route[3:]
    elif route.startswith('any'):
        route = route[3:]
    route = route.lower()
    
    param_key = cfg['key']
    param_val = cfg['val']
    
    # 1. HTML preview test
    url_html = f'http://localhost:8000/admin/update_bmt/{route}?t={token}&{param_key}={urllib.parse.quote(str(param_val))}'
    req_html = urllib.request.Request(url_html, headers={'Cookie': cookie})
    try:
        res_html = urllib.request.urlopen(req_html, timeout=10)
        body_html = res_html.read().decode('utf-8', errors='ignore')
        has_tb = 'no-print-toolbar' in body_html
        has_kop = 'KOP-SURAT.png' in body_html or 'kop_img' in body_html
    except Exception as ex:
        print(f"[{idx:2d}/57] {method:35s} (route: {route}) HTML ERROR: {ex}")
        fail_count += 1
        continue

    # 2. PDF generation test
    url_pdf = f'http://localhost:8000/admin/update_bmt/{route}?t={token}&{param_key}={urllib.parse.quote(str(param_val))}&pdf=1'
    req_pdf = urllib.request.Request(url_pdf, headers={'Cookie': cookie})
    try:
        res_pdf = urllib.request.urlopen(req_pdf, timeout=15)
        body_pdf = res_pdf.read()
        is_pdf = body_pdf.startswith(b'%PDF')
        pdf_len = len(body_pdf)
    except Exception as ex:
        print(f"[{idx:2d}/57] {method:35s} (route: {route}) PDF ERROR: {ex}")
        fail_count += 1
        continue

    if res_html.status == 200 and has_tb and res_pdf.status == 200 and is_pdf and pdf_len > 10000:
        print(f"[{idx:2d}/57] {method:35s} => SUCCESS (HTML Toolbar: {has_tb}, PDF: {is_pdf}, Size: {pdf_len:,} B)")
        success_count += 1
    else:
        print(f"[{idx:2d}/57] {method:35s} => FAILED (HTML: {res_html.status}, Toolbar: {has_tb}, PDF: {is_pdf}, Size: {pdf_len})")
        fail_count += 1

print(f"\n==========================================")
print(f"FINAL AUDIT RESULT: {success_count} SUCCESS, {fail_count} FAILED out of {len(db_map)} FORMS")
print(f"==========================================")

import os

CETAK_HTML = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/view/admin/asesmen_medis_umum_irna/cetak.html"
with open(CETAK_HTML, "r") as f:
    content = f.read()

target_start = "<style>"
target_end = "</table>\n</div>"

if target_start in content and target_end in content:
    idx_start = content.find(target_start)
    idx_end = content.find(target_end) + len(target_end)
    old_header = content[idx_start:idx_end]
    
    new_header = """<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>LAMPIRAN - ASESMEN MEDIS UMUM IRNA</title>
<style>
  @page { size: A4; margin: 8mm 10mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; margin: 0; color: #000; }
  .page { width: 190mm; margin: 0 auto; }
  table { border-collapse: collapse; width: 100%; }
  .bx { border: 1.5px solid #000; }
  .bx > tbody > tr > td { border: 1px solid #000; padding: 2px 6px; vertical-align: top; }
  .nob td { border: none !important; padding: 0 2px; }
  .rmbox { display: inline-block; width: 15px; height: 16px; border: 1px solid #000; text-align: center; line-height: 15px; font-size: 11px; vertical-align: middle; }
  .note { font-size: 7.5px; line-height: 1.25; }
  .title { text-align: center; font-weight: bold; font-size: 14px; padding: 5px; background: #ddd; }
  .box { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; text-align: center; line-height: 10px; font-size: 10px; margin-right: 3px; }
  .page-break { page-break-after: always; }
</style>
</head>
<body onload="window.print()">
<div class="page">

<table class="bx">
  <tr>
    <td style="width:57%; text-align:center;"><img src="{?=url()?}/assets/img/KOP-SURAT.png" alt="Kop" style="width:100%; max-height:75px; object-fit:contain;"></td>
    <td style="width:43%; font-size:10px;">
      <table class="nob">
        <tr><td style="width:62px;">No RM</td><td>:
          <span class="rmbox">{$data.rm0}</span><span class="rmbox">{$data.rm1}</span>-<span class="rmbox">{$data.rm2}</span><span class="rmbox">{$data.rm3}</span>-<span class="rmbox">{$data.rm4}</span><span class="rmbox">{$data.rm5}</span>
        </td></tr>
        <tr><td>Nama Pasien</td><td>: {$data.nm_pasien} &nbsp; <b>{$data.lp}</b></td></tr>
        <tr><td>Tanggal Lahir</td><td>: {$data.tanggal_lahir_indo}</td></tr>
      </table>
    </td>
  </tr>
  <tr><td colspan="2" class="title">ASESMEN MEDIS UMUM RAWAT INAP<br><span style="font-size:10px; font-weight:normal;">(dilengkapi dalam waktu 24 jam pertama pasien masuk ruangan)</span></td></tr>
</table>"""

    content = content.replace(old_header, new_header)

target_mid_start = """<div class="page-break"></div>

<div class="header-table">
    <table class="no-border">
        <tr>
            <td width="33%">Nama Pasien: {$data.nm_pasien} {$data.jk}</td>
            <td width="33%" class="center">Tanggal Lahir: {$data.tanggal_lahir_indo}</td>
            <td width="33%" style="text-align:right;">No. RM: {$data.no_rkm_medis}</td>
        </tr>
    </table>
</div>"""
target_mid_end = """<div class="page-break"></div>

<table class="bx">
  <tr>
    <td style="width:57%; text-align:center;"><img src="{?=url()?}/assets/img/KOP-SURAT.png" alt="Kop" style="width:100%; max-height:75px; object-fit:contain;"></td>
    <td style="width:43%; font-size:10px;">
      <table class="nob">
        <tr><td style="width:62px;">No RM</td><td>:
          <span class="rmbox">{$data.rm0}</span><span class="rmbox">{$data.rm1}</span>-<span class="rmbox">{$data.rm2}</span><span class="rmbox">{$data.rm3}</span>-<span class="rmbox">{$data.rm4}</span><span class="rmbox">{$data.rm5}</span>
        </td></tr>
        <tr><td>Nama Pasien</td><td>: {$data.nm_pasien} &nbsp; <b>{$data.lp}</b></td></tr>
        <tr><td>Tanggal Lahir</td><td>: {$data.tanggal_lahir_indo}</td></tr>
      </table>
    </td>
  </tr>
</table>"""

if target_mid_start in content:
    content = content.replace(target_mid_start, target_mid_end)

content = content.replace("<table style=\"border-top:none; margin-top: -1px;\">", "<table class=\"bx\" style=\"border-top:none; margin-top: -1px;\">")
content = content.replace("<table style=\"border-top:none;\">", "<table class=\"bx\" style=\"border-top:none;\">")
content = content.replace("<table>", "<table class=\"bx\">")
content = content.replace("</table>\n<table>", "</table>\n<table class=\"bx\">")

# Add ending tags
content += "\n</div>\n</body>\n</html>"

with open(CETAK_HTML, "w") as f:
    f.write(content)
print("Updated cetak.html")

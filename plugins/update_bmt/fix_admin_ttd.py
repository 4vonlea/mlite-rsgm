import os

ADMIN_PHP = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php"
with open(ADMIN_PHP, "r") as f:
    content = f.read()

target = """                'diagnosis' => $post['diagnosis'] ?? '',
                'rencana_tindak_lanjut' => $post['rencana_tindak_lanjut'] ?? '',
                'tanggal_ttd' => $post['tanggal_ttd'] ?? '',
                'jam_ttd' => $post['jam_ttd'] ?? '',
                'nip_dpjp' => $post['nip_dpjp'] ?? ''"""

replacement = """                'diagnosis' => $post['diagnosis'] ?? '',
                'rencana_tindak_lanjut' => $post['rencana_tindak_lanjut'] ?? '',
                'tanggal_ttd' => !empty($post['waktu_ttd']) ? explode('T', $post['waktu_ttd'])[0] : '',
                'jam_ttd' => !empty($post['waktu_ttd']) ? explode('T', $post['waktu_ttd'])[1] : '',
                'nip_dpjp' => $post['nip_dpjp'] ?? ''"""

if target in content:
    content = content.replace(target, replacement)
    with open(ADMIN_PHP, "w") as f:
        f.write(content)
    print("Admin.php updated")
else:
    print("Target not found")

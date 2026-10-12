import os

ADMIN_PHP = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php"
with open(ADMIN_PHP, "r") as f:
    content = f.read()

target = """        if (!empty($data['tgl_lahir'])) {"""

replacement = """        $rm = str_pad($data['no_rkm_medis'], 6, '0', STR_PAD_LEFT);
        for ($i=0; $i<6; $i++) {
            $data['rm'.$i] = $rm[$i];
        }
        $data['lp'] = $data['jk'] == 'L' ? '(Laki-laki)' : ($data['jk'] == 'P' ? '(Perempuan)' : '');

        if (!empty($data['tgl_lahir'])) {"""

if target in content:
    content = content.replace(target, replacement)
    with open(ADMIN_PHP, "w") as f:
        f.write(content)
    print("Admin.php cetak RM variables added")
else:
    print("Target not found")

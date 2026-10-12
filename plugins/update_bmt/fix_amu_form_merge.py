import os

ADMIN_PHP = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php"
with open(ADMIN_PHP, "r") as f:
    content = f.read()

target = """        if (!empty($id)) {
            $data = $this->db('asesmen_medis_umum_irna')->where('id', $id)->oneArray();"""

replacement = """        if (!empty($id)) {
            $db_data = $this->db('asesmen_medis_umum_irna')->where('id', $id)->oneArray();
            if ($db_data) { $data = array_merge($data, $db_data); }"""

if target in content:
    content = content.replace(target, replacement)
    with open(ADMIN_PHP, "w") as f:
        f.write(content)
    print("Fixed merge")
else:
    print("Not found")

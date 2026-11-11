import subprocess
import re

# 1. Get HEAD plugins/form_rme/Admin.php
head_code = subprocess.check_output(["git", "show", "HEAD:plugins/form_rme/Admin.php"]).decode("utf-8", errors="replace")

# 2. Get origin/form-rme:plugins/update_bmt/Admin.php
origin_code = subprocess.check_output(["git", "show", "origin/form-rme:plugins/update_bmt/Admin.php"]).decode("utf-8", errors="replace")

# Extract the new methods from origin
origin_lines = origin_code.split("\n")
start_idx = None
for idx, l in enumerate(origin_lines):
    if "public function anyCpptmanage" in l:
        start_idx = idx
        break

# Go back slightly to include comment header
while start_idx > 0 and origin_lines[start_idx-1].strip().startswith(("//", "/*", "*", "=")):
    start_idx -= 1

# Extract until end, but omit trailing closing brace of class
new_chunk_lines = origin_lines[start_idx:]
# Find the last closing brace
last_idx = len(new_chunk_lines) - 1
while last_idx >= 0:
    if new_chunk_lines[last_idx].strip() == "}":
        break
    last_idx -= 1

new_methods_code = "\n".join(new_chunk_lines[:last_idx])

# In new_methods_code, replace ADMIN, 'update_bmt' or ADMIN, "update_bmt" with ADMIN, 'form_rme'
new_methods_code = re.sub(r"\[ADMIN,\s*['\"]update_bmt['\"]", "[ADMIN, 'form_rme'", new_methods_code)
# Also replace any literal /update_bmt/ with /form_rme/
new_methods_code = new_methods_code.replace("/update_bmt/", "/form_rme/")

# 3. Apply the 2 small fixes into head_code:
# Fix PRMRJ row counts:
head_code = re.sub(r"\$targetRanapRows\s*=\s*max\(6,", "$targetRanapRows = max(8,", head_code)
head_code = re.sub(r"\$targetOpRows\s*=\s*max\(6,", "$targetOpRows = max(10,", head_code)
head_code = re.sub(r"\$targetObatRows\s*=\s*max\(12,", "$targetObatRows = max(16,", head_code)

# Fix Pulang APS save fields:
old_aps_save = """            'saksi_rs' => '', // Dikosongkan agar ditandatangani manual
            'saksi_pasien' => '', // Dikosongkan agar ditandatangani manual
            'pembuat_pernyataan' => $_POST['nama_pihak'],"""

new_aps_save = """            'saksi_rs' => $_POST['saksi_rs'] ?? '',
            'saksi_pasien' => $_POST['saksi_pasien'] ?? '',
            'pembuat_pernyataan' => !empty($_POST['pembuat_pernyataan']) ? $_POST['pembuat_pernyataan'] : ($_POST['nama_pihak'] ?? ''),
            'ttd_pembuat' => $_POST['ttd_pembuat'] ?? '',
            'ttd_saksi_rs' => $_POST['ttd_saksi_rs'] ?? '',
            'ttd_saksi_pasien' => $_POST['ttd_saksi_pasien'] ?? '',"""

if old_aps_save in head_code:
    head_code = head_code.replace(old_aps_save, new_aps_save)
    print("Replaced Pulang APS save fields successfully")
else:
    print("Warning: old_aps_save exact string not matched!")

# 4. Find the last closing brace of head_code and insert new_methods_code before it
head_lines = head_code.split("\n")
class_close_idx = len(head_lines) - 1
while class_close_idx >= 0:
    if head_lines[class_close_idx].strip() == "}":
        break
    class_close_idx -= 1

assembled_lines = head_lines[:class_close_idx] + [
    "\n    // ============================================================",
    "    // METODE FORMULIR RME DARI PENGEMBANGAN TERBARU",
    "    // ============================================================\n",
    new_methods_code,
    "\n}"
]

assembled_code = "\n".join(assembled_lines)

with open("plugins/form_rme/Admin.php", "w") as f:
    f.write(assembled_code)

print("Wrote assembled plugins/form_rme/Admin.php successfully! Total lines:", len(assembled_lines))

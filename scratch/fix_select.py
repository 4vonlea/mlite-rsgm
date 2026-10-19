import re

with open('plugins/update_bmt/Admin.php', 'r') as f:
    c = f.read()

count = 0
def repl(m):
    global count
    full = m.group(0)
    args = m.group(1)
    if 'as nama_pasien' in args:
        return full
    # Add , pasien.nm_pasien as nama_pasien
    new_args = args.replace('pasien.nm_pasien', 'pasien.nm_pasien, pasien.nm_pasien as nama_pasien', 1)
    count += 1
    return f"select({new_args})"

new_c = re.sub(r'select\(([\'"][^\'"]*?pasien\.nm_pasien[^\'"]*?[\'"])\)', repl, c)

if new_c != c:
    with open('plugins/update_bmt/Admin.php', 'w') as f:
        f.write(new_c)
    print(f"Updated {count} select queries in Admin.php to alias nama_pasien!")
else:
    print("All select queries already have nama_pasien alias!")

import subprocess
import re

with open('/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php') as f:
    content = f.read()

tables = sorted(list(set(re.findall(r"->db\('([a-zA-Z0-9_]+)'\)", content))))
custom_tables = [t for t in tables if t not in ['dokter', 'pemeriksaan_ralan', 'pemeriksaan_ranap', 'petugas', 'reg_periksa', 'pasien', 'kamar_inap']]

print(f"Total custom tables: {len(custom_tables)}")

output_lines = [
    "-- =========================================================",
    "-- MIGRASI DATABASE: TABEL PENDUKUNG UPDATE BMT (mLITE)",
    "-- Database Target: mlitersgm",
    f"-- Total Tabel: {len(custom_tables)}",
    "-- =========================================================",
    "",
    "SET FOREIGN_KEY_CHECKS = 0;",
    ""
]

for t in custom_tables:
    query = f"SHOW CREATE TABLE `{t}`;"
    proc = subprocess.run(
        ['/Applications/XAMPP/xamppfiles/bin/mysql', '-u', 'root', 'mlitersgm', '-N', '-e', query],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True
    )
    if proc.returncode != 0:
        print(f"Error reading table {t}: {proc.stderr}")
        continue
    
    parts = proc.stdout.strip().split('\t')
    if len(parts) >= 2:
        create_sql = parts[1]
        create_sql = create_sql.replace('\\n', '\n')
        create_sql = re.sub(r'CREATE TABLE\s+`', 'CREATE TABLE IF NOT EXISTS `', create_sql, count=1, flags=re.I)
        # remove AUTO_INCREMENT=X to keep clean initial migration
        create_sql = re.sub(r'AUTO_INCREMENT=\d+\s*', '', create_sql)
        output_lines.append(f"-- --------------------------------------------------------")
        output_lines.append(f"-- Struktur tabel `{t}`")
        output_lines.append(f"-- --------------------------------------------------------")
        output_lines.append(create_sql + ";")
        output_lines.append("")

output_lines.append("SET FOREIGN_KEY_CHECKS = 1;")
output_lines.append("")

sql_content = "\n".join(output_lines)

with open('/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/migration_tabel_pendukung_bmt.sql', 'w') as f:
    f.write(sql_content)

with open('/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/migration.sql', 'w') as f:
    f.write(sql_content)

print(f"Berhasil membuat migrasi SQL dengan {len(custom_tables)} tabel.")

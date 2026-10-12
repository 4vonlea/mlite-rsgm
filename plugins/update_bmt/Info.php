<?php
return [
    'name'          =>  'Update Fitur BMT',
    'description'   =>  'Modul untuk update fitur BMT & Rekam Medis (Asesmen, Bedah, Anestesi, Ranap, Rajal, Edukasi Pasien)',
    'author'        =>  'mLITE',
    'category'      =>  'layanan', 
    'version'       =>  '1.1',
    'compatibility' =>  '6.*.*',
    'icon'          =>  'cogs',
    'install'       =>  function () use ($core) {
        $sqlFile = __DIR__ . '/migration.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $core->db()->pdo()->exec($statement);
                    } catch (\Throwable $e) {
                        // ignore if exists
                    }
                }
            }
        }
    },
    'uninstall'     =>  function() use($core) {}
];

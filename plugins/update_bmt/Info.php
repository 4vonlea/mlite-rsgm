<?php
return [
    'name'          =>  'Form RME',
    'description'   =>  'Modul Form Rekam Medis Elektronik (RME)',
    'author'        =>  'mLITE',
    'category'      =>  'rekammedik', 
    'version'       =>  '1.1',
    'compatibility' =>  '6.*.*',
    'icon'          =>  'file-text',
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
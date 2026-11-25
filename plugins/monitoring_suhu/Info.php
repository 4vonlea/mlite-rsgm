<?php

return [
    'name'          => 'Suhu & Kelembaban',
    'description'   => 'Monitoring suhu dan kelembaban per unit (ruangan, kulkas, freezer, inkubator, dll.) berbasis hak akses unit',
    'author'        => 'RSGM Gusti Hasan Aman',
    'category'      => 'manajemen',
    'version'       => '1.1',
    'compatibility' => '6.*.*',
    'icon'          => 'thermometer-half',
    'install'       => function () use ($core) {
        $sqlFile = __DIR__ . '/migration.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $statements = array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $sqlContent))));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $core->db()->pdo()->exec($statement);
                    } catch (\Throwable $e) {
                        // ignore if already exists
                    }
                }
            }
        }
    },
    'uninstall'     => function () use ($core) {
    }
];

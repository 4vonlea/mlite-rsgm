<?php

namespace Systems\Lib;

/**
 * Manajemen Penandaan Koreksi, Jejak Audit, & Pembatalan (VOID) untuk Form RME Rawat Jalan
 * sesuai standar akreditasi MRMIK 8.
 *
 * Menggunakan tabel terpadu `mlite_rme_revisions` (bersama SoapRevision).
 */
class RmeFormRevision
{
    const GRACE_PERIOD_SECONDS = 900; // 15 menit (Opsi B)

    /** @var array<string, bool> Cache in-process agar ALTER TABLE hanya dicek sekali per tabel */
    private static $ensuredTables = [];

    /** @var array<string, array> Katalog dinamis seluruh Form RME (IGD, Rawat Inap, Bedah, Rajal) dari form_rme->getRmeFormMap() */
    private static $dynamicCatalog = [];

    /** @var array<string, string> Peta direktori view -> nama tabel untuk seluruh Form RME */
    private static $viewDirToTable = [];

    /** @var array<string, array> Cache pengecekan kolom tabel */
    private static $tableColsCache = [];

    /**
     * Daftarkan peta seluruh formulir RME (IGD, Ranap, Bedah, Rajal) secara dinamis
     * agar MRMIK 8 mencakup seluruh formulir selain 12 formulir Rajal eksplisit.
     */
    public static function setDynamicCatalog(array $formMap)
    {
        $skipTables = [
            'pemeriksaan_ranap' => true, // Sudah ditangani oleh SoapRevision (composite key)
            'mlite_triase_igd'  => true, // Primary key bukan `id` (id_triase)
        ];

        $explicit = self::registry();
        foreach ($formMap as $info) {
            $tbl = trim((string)($info['table'] ?? ''));
            if ($tbl === '' || isset($skipTables[$tbl]) || !preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
                continue;
            }
            $tpl = trim((string)($info['template'] ?? ''));
            if ($tpl !== '') {
                $dir = explode('/', $tpl)[0];
                if ($dir !== '' && !isset(self::$viewDirToTable[$dir])) {
                    self::$viewDirToTable[$dir] = $tbl;
                }
            }
            if (isset($explicit[$tbl]) || isset(self::$dynamicCatalog[$tbl])) {
                continue;
            }
            $routeManage = strtolower(trim((string)($info['route_manage'] ?? '')));
            $routeForm   = strtolower(trim((string)($info['route_form'] ?? '')));
            $type = preg_replace('/manage$/i', '', $routeManage);
            if ($type === '') {
                $type = preg_replace('/form$/i', '', $routeForm);
            }
            $kode = trim((string)($info['kode'] ?? strtoupper($type)));
            $nama = trim((string)($info['nama'] ?? $kode));

            self::$dynamicCatalog[$tbl] = [
                'type'         => $type,
                'form_code'    => $kode,
                'title'        => $nama,
                'route_manage' => $routeManage,
                'route_form'   => $routeForm,
                'route_cetak'  => strtolower(trim((string)($info['route_cetak'] ?? ''))),
                'tte_cols'     => [],
                'sig_cols'     => [],
                'ref_types'    => array_values(array_filter([$type, strtolower($kode)])),
                'creator_cols' => ['created_by', 'nip', 'nip_petugas', 'kd_dokter', 'petugas', 'user_id'],
                'json_cols'    => ['form_data', 'data_json', 'items', 'detail_json'],
                'labels'       => [],
                'is_dynamic'   => true,
            ];
        }
    }

    public static function getTableByViewDir($dir)
    {
        return self::$viewDirToTable[$dir] ?? '';
    }

    public static function isExplicitRajalTable($table)
    {
        return isset(self::registry()[$table]);
    }

    /**
     * Registry lengkap 12 Formulir RME Rawat Jalan.
     */
    public static function registry()
    {
        return [
            'rm_persetujuan_umum' => [
                'type' => 'persetujuanumum',
                'form_code' => 'RJ02',
                'title' => 'RM.RJ 02 - Formulir Persetujuan Umum',
                'tte_cols' => ['ttd_petugas'],
                'sig_cols' => ['ttd_pasien', 'ttd_petugas'],
                'ref_types' => ['persetujuan_umum_petugas', 'rj02_petugas', 'general_consent'],
                'creator_cols' => ['created_by', 'nip_petugas'],
                'json_cols' => [],
                'labels' => [
                    'pelepasan_info_keluarga_1' => 'Pelepasan Info Keluarga 1',
                    'pelepasan_info_keluarga_2' => 'Pelepasan Info Keluarga 2',
                    'pelepasan_info_keluarga_3' => 'Pelepasan Info Keluarga 3',
                    'tanggal_persetujuan' => 'Tanggal Persetujuan',
                    'penandatangan_hubungan' => 'Hubungan Penandatangan',
                    'penandatangan_nama' => 'Nama Penandatangan',
                    'ttd_pasien' => 'TTD Pasien / Wali',
                    'nama_petugas' => 'Nama Petugas',
                    'nip_petugas' => 'NIP Petugas',
                    'ttd_petugas' => 'TTE Petugas RS',
                ],
            ],
            'rm_rj02a_hak_kewajiban' => [
                'type' => 'rj02a',
                'form_code' => 'RJ02A',
                'title' => 'RM.RJ 02A - Hak dan Kewajiban Pasien',
                'tte_cols' => [],
                'sig_cols' => ['ttd_pasien', 'ttd_edukasi'],
                'ref_types' => ['rj02a_edukasi'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['form_data'],
                'labels' => [
                    'nama_pj' => 'Nama Penanggung Jawab',
                    'hubungan' => 'Hubungan dengan Pasien',
                    'pemberi_edukasi' => 'Pemberi Edukasi',
                    'ttd_pasien' => 'TTD Pasien / Wali',
                    'ttd_edukasi' => 'TTE Pemberi Edukasi',
                ],
            ],
            'rm_rj03_asesmen_keperawatan_gigi' => [
                'type' => 'rj03',
                'form_code' => 'RJ03',
                'title' => 'RM.RJ 03 - Asesmen Keperawatan Gigi Rajal',
                'tte_cols' => ['tte_dpjp', 'tte_perawat_od', 'tte_perawat'],
                'sig_cols' => ['tte_dpjp', 'tte_perawat_od', 'tte_perawat'],
                'ref_types' => ['rj03_dpjp', 'rj03_perawat_od', 'rj03_perawat'],
                'creator_cols' => ['created_by', 'nip_perawat', 'nip_perawat_od', 'kd_dpjp'],
                'json_cols' => [],
                'labels' => [
                    'keluhan_utama' => 'Keluhan Utama',
                    'riwayat_penyakit_sekarang' => 'Riwayat Penyakit Sekarang',
                    'riwayat_penyakit_dahulu' => 'Riwayat Penyakit Dahulu',
                    'riwayat_penyakit_keluarga' => 'Riwayat Penyakit Keluarga',
                    'td' => 'Tekanan Darah',
                    'nadi' => 'Nadi',
                    'rr' => 'Respirasi (RR)',
                    'suhu' => 'Suhu',
                    'bb' => 'Berat Badan',
                    'tb' => 'Tinggi Badan',
                    'masalah_keperawatan' => 'Masalah Keperawatan',
                    'rencana_tindak_lanjut' => 'Rencana Tindak Lanjut',
                    'tte_dpjp' => 'TTE DPJP',
                    'tte_perawat_od' => 'TTE Perawat OD',
                    'tte_perawat' => 'TTE Perawat',
                ],
            ],
            'rm_rj04_asesmen_medis_gigi' => [
                'type' => 'rj04',
                'form_code' => 'RJ04',
                'title' => 'RM.RJ 04 - Asesmen Medis Gigi Rajal',
                'tte_cols' => ['tte_dokter_od', 'tte_dpjp'],
                'sig_cols' => ['tte_dokter_od', 'tte_dpjp'],
                'ref_types' => ['rj04_dokter_od', 'rj04_dpjp'],
                'creator_cols' => ['created_by', 'kd_dpjp', 'kd_dokter_od'],
                'json_cols' => [],
                'labels' => [
                    'keluhan_utama' => 'Keluhan Utama',
                    'riwayat_penyakit_sekarang' => 'Riwayat Penyakit Sekarang',
                    'diagnosis_utama' => 'Diagnosis Utama',
                    'diagnosis_sekunder' => 'Diagnosis Sekunder',
                    'rencana_terapi' => 'Rencana Terapi / Tindakan',
                    'skala_nyeri' => 'Skala Nyeri',
                    'tte_dokter_od' => 'TTE Dokter OD',
                    'tte_dpjp' => 'TTE DPJP',
                ],
            ],
            'rm_rj07_asesmen_ulang_nyeri' => [
                'type' => 'rj07',
                'form_code' => 'RJ07',
                'title' => 'RM.RJ 07 - Asesmen Ulang Nyeri',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['rj07_petugas', 'rj07_perawat', 'rj07_dpjp', 'rj07_petugas_row_0', 'rj07_petugas_row_1', 'rj07_petugas_row_2', 'rj07_petugas_row_3', 'rj07_petugas_row_4'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['form_data'],
                'labels' => [
                    'asesmen' => 'Rincian Asesmen Ulang Nyeri',
                    'tte_petugas' => 'TTE Petugas',
                ],
            ],
            'rm_rj10_edukasi_terintegrasi' => [
                'type' => 'rj10',
                'form_code' => 'RJ10',
                'title' => 'RM.RJ 10 - Edukasi Terintegrasi',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['rj10_petugas', 'rj10_edukasi'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['form_data'],
                'labels' => [],
            ],
            'rm_edukasi_pasien_terintegrasi' => [
                'type' => 'edukasi',
                'form_code' => 'EDUKASI',
                'title' => 'Edukasi Pasien & Keluarga Terintegrasi',
                'tte_cols' => ['ttd_perawat'],
                'sig_cols' => ['ttd_perawat'],
                'ref_types' => ['edukasi_pemberi', 'edukasi_perawat'],
                'creator_cols' => ['created_by', 'nip_perawat'],
                'json_cols' => ['detail_rows'],
                'labels' => [
                    'pendidikan_terakhir' => 'Pendidikan Terakhir',
                    'bahasa_lainnya' => 'Bahasa Lainnya',
                    'kebutuhan_edukasi' => 'Kebutuhan Edukasi',
                    'nama_perawat' => 'Nama Perawat',
                    'ttd_perawat' => 'TTE Perawat',
                    'detail_rows' => 'Rincian Pemberian Edukasi',
                ],
            ],
            'rm_rj11_pemberian_informasi' => [
                'type' => 'rj11',
                'form_code' => 'RJ11',
                'title' => 'RM.RJ 11 - Pemberian Informasi & Persetujuan/Penolakan',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['rj11_dokter', 'rj11_saksi1', 'rj11_saksi2', 'rj11_tolak_dokter', 'rj11_tolak_saksi1', 'rj11_tolak_saksi2'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['form_data'],
                'labels' => [],
            ],
            'rm_lampiran_profil_ringkas_medis_rajal' => [
                'type' => 'prmrj',
                'form_code' => 'L_PRMRJ',
                'title' => 'Profil Ringkas Medis Rawat Jalan (PRMRJ)',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['prmrj_dpjp', 'prmrj_dokter'],
                'creator_cols' => ['created_by', 'nip_dokter'],
                'json_cols' => ['diagnosa_dpjp', 'riwayat_alergi', 'diagnosa_ranap', 'operasi_tindakan', 'obat_obatan'],
                'labels' => [
                    'tanggal_formulir' => 'Tanggal Formulir',
                    'diagnosa_dpjp' => 'Diagnosa DPJP',
                    'riwayat_alergi' => 'Riwayat Alergi',
                    'diagnosa_ranap' => 'Diagnosa Rawat Inap',
                    'operasi_tindakan' => 'Operasi / Tindakan',
                    'obat_obatan' => 'Obat-obatan',
                    'keterangan_tambahan' => 'Keterangan Tambahan',
                    'nama_dokter' => 'Nama Dokter',
                ],
            ],
            'rm_lampiran_persetujuan_tindakan_radiologi' => [
                'type' => 'persetujuanradiologi',
                'form_code' => 'L_RAD',
                'title' => 'Persetujuan Tindakan Radiologi',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['radiologi_dokter', 'persetujuan_radiologi'],
                'creator_cols' => ['created_by', 'kd_dokter'],
                'json_cols' => [],
                'labels' => [
                    'nama_penanggung_jawab' => 'Nama Penanggung Jawab',
                    'hubungan_pasien' => 'Hubungan dengan Pasien',
                    'tindakan_radiologi' => 'Tindakan Radiologi',
                    'nama_yang_menyatakan' => 'Yang Menyatakan',
                    'nama_dokter' => 'Dokter Pelaksana',
                    'nama_saksi_1' => 'Saksi 1',
                    'nama_saksi_2' => 'Saksi 2',
                ],
            ],
            'ringkasan_riwayat_klinik' => [
                'type' => 'ringkasanriwayatklinik',
                'form_code' => 'RINGKASAN_KLINIK',
                'title' => 'Ringkasan Riwayat Klinik',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['ringkasan_klinik'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['riwayat_detail'],
                'labels' => [
                    'alergi_obat' => 'Alergi Obat',
                    'alergi_1' => 'Alergi 1',
                    'alergi_2' => 'Alergi 2',
                    'alergi_3' => 'Alergi 3',
                    'riwayat_detail' => 'Rincian Riwayat Klinik',
                    'catatan_fktp' => 'Catatan FKTP',
                ],
            ],
            'rj_asesmen_jatuh_nyeri_anak' => [
                'type' => 'jatuhanak',
                'form_code' => 'RJ_JATUH_ANAK',
                'title' => 'Asesmen Jatuh & Nyeri Anak',
                'tte_cols' => [],
                'sig_cols' => [],
                'ref_types' => ['jatuh_anak_petugas'],
                'creator_cols' => ['created_by'],
                'json_cols' => ['humpty_dumpty', 'flacc'],
                'labels' => [
                    'instalasi_unit' => 'Instalasi / Unit',
                    'tanggal' => 'Tanggal Asesmen',
                    'jam' => 'Jam Asesmen',
                    'humpty_dumpty' => 'Skor Humpty Dumpty',
                    'humpty_dumpty_total' => 'Total Humpty Dumpty',
                    'humpty_dumpty_kategori' => 'Kategori Risiko Jatuh',
                    'flacc' => 'Skor Nyeri FLACC',
                    'flacc_total' => 'Total FLACC',
                    'flacc_kategori' => 'Kategori Nyeri FLACC',
                    'petugas_nama' => 'Nama Petugas',
                ],
            ],
        ];
    }

    public static function getMetaByTable($table)
    {
        $reg = self::registry();
        if (isset($reg[$table])) {
            return $reg[$table];
        }
        return self::$dynamicCatalog[$table] ?? null;
    }

    public static function getMetaByType($type)
    {
        $type = strtolower(trim((string)$type));
        foreach (self::registry() as $table => $meta) {
            if (strtolower($meta['type']) === $type) {
                $meta['table'] = $table;
                return $meta;
            }
        }
        foreach (self::$dynamicCatalog as $table => $meta) {
            if (strtolower($meta['type']) === $type) {
                $meta['table'] = $table;
                return $meta;
            }
        }
        return null;
    }

    public static function isRegisteredTable($table)
    {
        return isset(self::registry()[$table]) || isset(self::$dynamicCatalog[$table]);
    }

    /**
     * Pastikan tabel `mlite_rme_revisions` dan kolom audit pada tabel formulir tersedia.
     */
    public static function ensureSchema(\PDO $pdo, $table = null)
    {
        SoapRevision::ensureSchema($pdo);
        if ($table && self::isRegisteredTable($table)) {
            self::ensureTableAuditColumns($pdo, $table);
        }
    }

    public static function ensureTableAuditColumns(\PDO $pdo, $table)
    {
        if (!self::isRegisteredTable($table) || isset(self::$ensuredTables[$table])) {
            return;
        }
        try {
            $cols = [];
            $q = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            if ($q) {
                foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                    $cols[$c['Field']] = true;
                }
            }
            $alterCols = [
                'entry_created_at' => "ADD COLUMN `entry_created_at` DATETIME NULL DEFAULT NULL",
                'last_revised_at'  => "ADD COLUMN `last_revised_at` DATETIME NULL DEFAULT NULL",
                'revision_count'   => "ADD COLUMN `revision_count` INT NOT NULL DEFAULT 0",
                'created_by'       => "ADD COLUMN `created_by` VARCHAR(60) NULL DEFAULT NULL",
                'deleted_at'       => "ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL",
                'deleted_by'       => "ADD COLUMN `deleted_by` VARCHAR(120) NULL DEFAULT NULL",
                'void_reason'      => "ADD COLUMN `void_reason` VARCHAR(255) NULL DEFAULT NULL",
            ];
            $adds = [];
            foreach ($alterCols as $col => $ddl) {
                if (!isset($cols[$col])) {
                    $adds[] = $ddl;
                }
            }
            if (!empty($adds)) {
                $pdo->exec("ALTER TABLE `{$table}` " . implode(', ', $adds));
            }
            self::$ensuredTables[$table] = true;
        } catch (\Throwable $e) {
            // Abaikan error race condition atau permission
        }
    }

    private static function getValidTableColumns(\PDO $pdo, $table)
    {
        $cols = [];
        try {
            $q = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            if ($q) {
                foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                    $cols[] = $r['Field'];
                }
            }
        } catch (\Throwable $e) {}
        return $cols;
    }

    public static function getActorMeta($core, $overrideUsername = null)
    {
        $username = $overrideUsername ?: ($core->getUserInfo('username', null, true) ?? ($_SESSION['mlite_user_username'] ?? ''));
        if (empty($username) && !empty($_SESSION['mlite_user'])) {
            try {
                $u = $core->db('mlite_users')->where('id', $_SESSION['mlite_user'])->oneArray();
                if ($u && !empty($u['username'])) {
                    $username = $u['username'];
                }
            } catch (\Throwable $e) {}
        }
        $name = '';
        $role = $core->getUserInfo('role', null, true);
        if (!empty($username)) {
            $name = $core->getPegawaiInfo('nama', $username);
            if (empty($name) || $name === '-') {
                $name = $core->getUserInfo('fullname', null, true);
            }
        }
        return [
            'username' => (string)$username,
            'name'     => !empty($name) ? (string)$name : (string)$username,
            'role'     => !empty($role) ? (string)$role : 'petugas',
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];
    }

    private static function getNoRm(\PDO $pdo, $noRawat)
    {
        if (empty($noRawat)) return null;
        try {
            $stmt = $pdo->prepare("SELECT no_rkm_medis FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
            $stmt->execute([$noRawat]);
            return $stmt->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function logQuery($core, $sql, $binds)
    {
        try {
            if (is_object($core) && isset($core->settings) && is_object($core->settings) && $core->settings->get('settings.log_query') == 'ya') {
                \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Ekstrak seluruh nilai TTE hash 64-hex yang melekat pada row formulir yang tersimpan
     * dan/atau di tabel `mlite_esignatures`.
     */
    public static function findActiveTte(\PDO $pdo, $table, array $existingRow)
    {
        $meta = self::getMetaByTable($table);
        if (!$meta) return null;

        $foundHashes = [];
        $latestSignedAt = '';

        // 1. Cek kolom inline pada tabel
        foreach ($meta['tte_cols'] as $col) {
            $v = trim((string)($existingRow[$col] ?? ''));
            if (preg_match('/^[a-f0-9]{64}$/i', $v)) {
                $foundHashes[] = $v;
            }
        }

        // 2. Cek kolom JSON (form_data pada rj02a, rj07, rj10, rj11) termasuk array bersarang (asesmen / pelaksanaan)
        if (!empty($existingRow['form_data'])) {
            $fd = is_array($existingRow['form_data'])
                ? $existingRow['form_data']
                : json_decode((string)$existingRow['form_data'], true);
            if (is_array($fd)) {
                array_walk_recursive($fd, function ($v) use (&$foundHashes) {
                    if (is_string($v) && preg_match('/^[a-f0-9]{64}$/i', trim($v))) {
                        $foundHashes[] = trim($v);
                    }
                });
            }
        }

        // 3. Cek detail anak edukasi
        if ($table === 'rm_edukasi_pasien_terintegrasi' && !empty($existingRow['id'])) {
            try {
                $q = $pdo->prepare("SELECT tte_pemberi FROM edukasi_pasien_terintegrasi_detail WHERE id_edukasi = ?");
                $q->execute([$existingRow['id']]);
                while ($h = $q->fetchColumn()) {
                    $h = trim((string)$h);
                    if (preg_match('/^[a-f0-9]{64}$/i', $h)) {
                        $foundHashes[] = $h;
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 4. Lookup signed_at & konfirmasi mlite_esignatures
        if (!empty($foundHashes)) {
            $ph = implode(',', array_fill(0, count($foundHashes), '?'));
            try {
                $stmt = $pdo->prepare("SELECT signature_hash, signed_at FROM mlite_esignatures WHERE signature_hash IN ({$ph}) ORDER BY id DESC LIMIT 1");
                $stmt->execute($foundHashes);
                $es = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($es) {
                    return $es;
                }
            } catch (\Throwable $e) {}
            return ['signature_hash' => $foundHashes[0], 'signed_at' => $existingRow['updated_at'] ?? ($existingRow['created_at'] ?? '')];
        }

        // Fallback via ref_type + ref_id (jika hash belum tersalin ke kolom row)
        if (!empty($meta['ref_types'])) {
            $refIds = [];
            if (!empty($existingRow['id'])) $refIds[] = (string)$existingRow['id'];
            if (!empty($existingRow['no_rawat'])) $refIds[] = (string)$existingRow['no_rawat'];
            if (!empty($refIds)) {
                $rtPh = implode(',', array_fill(0, count($meta['ref_types']), '?'));
                $riPh = implode(',', array_fill(0, count($refIds), '?'));
                try {
                    $stmt = $pdo->prepare("SELECT signature_hash, signed_at FROM mlite_esignatures WHERE ref_type IN ({$rtPh}) AND ref_id IN ({$riPh}) ORDER BY id DESC LIMIT 1");
                    $stmt->execute(array_merge($meta['ref_types'], $refIds));
                    $es = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($es) {
                        return $es;
                    }
                } catch (\Throwable $e) {}
            }
        }

        return null;
    }

    /**
     * Evaluasi masa tenggang koreksi 15 menit (Opsi B) untuk formulir RME Rajal.
     */
    public static function checkGracePeriod(\PDO $pdo, $table, array $existingRow, $actorUsername)
    {
        $meta = self::getMetaByTable($table);
        $actor = trim((string)$actorUsername);

        // Deteksi pembuat awal
        $creator = '';
        if ($meta) {
            foreach ($meta['creator_cols'] as $cc) {
                $cand = trim((string)($existingRow[$cc] ?? ''));
                if ($cand !== '') {
                    $creator = $cand;
                    break;
                }
            }
        }
        $sameCreator = ($actor !== '' && $creator !== '' && $creator === $actor);

        // Timestamp entri awal
        $createdStr = '';
        if (!empty($existingRow['entry_created_at'])) {
            $createdStr = (string)$existingRow['entry_created_at'];
        } elseif (!empty($existingRow['created_at']) && $existingRow['created_at'] !== '0000-00-00 00:00:00') {
            $createdStr = (string)$existingRow['created_at'];
        }

        $createdTs = $createdStr !== '' ? strtotime($createdStr) : false;
        $nowTs = time();
        $elapsedSeconds = ($createdTs !== false) ? max(0, $nowTs - $createdTs) : (self::GRACE_PERIOD_SECONDS + 1);
        $elapsedMinutes = (int)floor($elapsedSeconds / 60);
        $minutesLeft = max(0, (int)ceil((self::GRACE_PERIOD_SECONDS - $elapsedSeconds) / 60));

        // Cek TTE
        $tte = self::findActiveTte($pdo, $table, $existingRow);
        $isSigned = !empty($tte);

        // Cek status bayar/pulang
        $isCheckedOut = false;
        if (!empty($existingRow['no_rawat'])) {
            try {
                $stmt = $pdo->prepare("SELECT status_bayar FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
                $stmt->execute([$existingRow['no_rawat']]);
                $reg = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($reg && ($reg['status_bayar'] ?? '') === 'Sudah Bayar') {
                    $isCheckedOut = true;
                }
            } catch (\Throwable $e) {}
        }

        $hasPriorFormalRevision = ((int)($existingRow['revision_count'] ?? 0) > 0);

        $whyParts = [];
        if (!$sameCreator) {
            $whyParts[] = 'diubah oleh petugas/akun berbeda atau data lama tanpa pencatat awal';
        }
        if ($elapsedSeconds > self::GRACE_PERIOD_SECONDS) {
            $whyParts[] = 'telah melewati masa tenggang 15 menit (' . $elapsedMinutes . ' menit sejak entri)';
        }
        if ($isSigned) {
            $whyParts[] = 'formulir sudah ditandatangani secara elektronik (TTE)';
        }
        if ($isCheckedOut) {
            $whyParts[] = 'status kunjungan pasien sudah selesai / lunas bayar';
        }
        if ($hasPriorFormalRevision) {
            $whyParts[] = 'formulir sudah pernah dikoreksi sebelumnya';
        }

        $inGrace = empty($whyParts);

        return [
            'in_grace'        => $inGrace,
            'needs_reason'    => !$inGrace,
            'why'             => $inGrace ? 'Dalam masa koreksi langsung (<= 15 menit)' : 'Koreksi wajib alasan karena ' . implode(', ', $whyParts),
            'elapsed_minutes' => $elapsedMinutes,
            'minutes_left'    => $inGrace ? $minutesLeft : 0,
            'tte_hash'        => $tte['signature_hash'] ?? null,
            'tte_signed_at'   => $tte['signed_at'] ?? '',
        ];
    }

    private static function humanizeField($field, array $labels = [])
    {
        if (isset($labels[$field])) {
            return $labels[$field];
        }
        $clean = str_replace(['_ket', '_id', '_'], ['', '', ' '], $field);
        return ucwords(trim($clean));
    }

    private static function formatValueForDiff($val, $field = '')
    {
        if (is_array($val)) {
            if (empty($val)) return '-';
            $isList = array_keys($val) === range(0, count($val) - 1);
            if ($isList && isset($val[0]) && is_array($val[0])) {
                $summaries = [];
                foreach ($val as $idx => $item) {
                    if (!is_array($item)) {
                        $summaries[] = (string)$item;
                        continue;
                    }
                    $parts = [];
                    foreach ($item as $ik => $iv) {
                        $sVal = trim((string)$iv);
                        if ($sVal === '' || strpos($sVal, 'data:image/') === 0 || preg_match('/^[a-f0-9]{64}$/i', $sVal)) continue;
                        $parts[] = ucwords(str_replace('_', ' ', $ik)) . ': ' . mb_strimwidth($sVal, 0, 45, '...');
                    }
                    if (!empty($parts)) {
                        $summaries[] = 'Baris ' . ($idx + 1) . ' [' . implode(', ', array_slice($parts, 0, 5)) . ']';
                    }
                }
                $joined = implode(' | ', $summaries);
                return $joined !== '' ? mb_strimwidth($joined, 0, 320, '...') : '(' . count($val) . ' baris)';
            }
            $encoded = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return mb_strimwidth((string)$encoded, 0, 300, '...');
        }
        $str = trim((string)$val);
        if ($str === '') return '-';
        if (strpos($str, 'data:image/') === 0) {
            return '[Tanda Tangan / Gambar Digital]';
        }
        if (preg_match('/^[a-f0-9]{64}$/i', $str)) {
            return '[TTE Terverifikasi: ' . substr($str, 0, 12) . '...]';
        }
        if (strlen($str) > 300) {
            return mb_strimwidth($str, 0, 300, '...');
        }
        return $str;
    }

    private static function isSignatureOnlyField($field, $oldVal, $newVal)
    {
        $f = strtolower((string)$field);
        $isSigName = (strpos($f, 'ttd') !== false || strpos($f, 'tte') !== false || strpos($f, 'signature') !== false);
        $oldEmpty = trim((string)$oldVal) === '';
        $newStr = trim((string)$newVal);
        $newIsTteOrSig = (strpos($newStr, 'data:image/') === 0 || preg_match('/^[a-f0-9]{64}$/i', $newStr));
        return ($isSigName || $newIsTteOrSig) && $oldEmpty && $newStr !== '';
    }

    /**
     * Hitung perbedaan field antara data lama & data baru (termasuk isi JSON form_data & sub-tabel).
     */
    public static function computeDiff($table, array $oldRow, array $newRow)
    {
        $meta = self::getMetaByTable($table);
        $labels = $meta['labels'] ?? [];
        $jsonCols = $meta['json_cols'] ?? [];
        $ignored = [
            'id', 'created_at', 'updated_at', 'entry_created_at', 'last_revised_at',
            'revision_count', 'created_by', 'deleted_at', 'deleted_by', 'void_reason',
            'table_name', 'record_key', 'form_code',
        ];

        $diff = [];
        foreach ($newRow as $field => $newVal) {
            if (in_array($field, $ignored, true)) continue;

            if (in_array($field, $jsonCols, true)) {
                $oldArr = is_array($oldRow[$field] ?? null) ? $oldRow[$field] : json_decode((string)($oldRow[$field] ?? '[]'), true);
                $newArr = is_array($newVal) ? $newVal : json_decode((string)$newVal, true);
                if (!is_array($oldArr)) $oldArr = [];
                if (!is_array($newArr)) $newArr = [];

                if ($field === 'form_data') {
                    // Diff per inner key pada JSON form_data (rj02a, rj07, rj10, rj11)
                    $allKeys = array_unique(array_merge(array_keys($oldArr), array_keys($newArr)));
                    foreach ($allKeys as $k) {
                        $ov = $oldArr[$k] ?? '';
                        $nv = $newArr[$k] ?? '';
                        $ovNorm = is_array($ov) ? json_encode($ov, JSON_UNESCAPED_UNICODE) : trim((string)$ov);
                        $nvNorm = is_array($nv) ? json_encode($nv, JSON_UNESCAPED_UNICODE) : trim((string)$nv);
                        if ($ovNorm !== $nvNorm) {
                            $diff[$k] = [
                                'label' => self::humanizeField($k, $labels),
                                'old'   => self::formatValueForDiff($ov, $k),
                                'new'   => self::formatValueForDiff($nv, $k),
                                '_raw_old' => $ovNorm,
                                '_raw_new' => $nvNorm,
                            ];
                        }
                    }
                } else {
                    $ovJson = json_encode($oldArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $nvJson = json_encode($newArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($ovJson !== $nvJson) {
                        $oldCount = count($oldArr);
                        $newCount = count($newArr);
                        $diff[$field] = [
                            'label' => self::humanizeField($field, $labels),
                            'old'   => self::formatValueForDiff($oldArr, $field) . " ({$oldCount} item)",
                            'new'   => self::formatValueForDiff($newArr, $field) . " ({$newCount} item)",
                            '_raw_old' => $ovJson,
                            '_raw_new' => $nvJson,
                        ];
                    }
                }
                continue;
            }

            $oldNorm = trim((string)($oldRow[$field] ?? ''));
            $newNorm = trim((string)$newVal);
            if ($oldNorm !== $newNorm) {
                $diff[$field] = [
                    'label' => self::humanizeField($field, $labels),
                    'old'   => self::formatValueForDiff($oldNorm, $field),
                    'new'   => self::formatValueForDiff($newNorm, $field),
                    '_raw_old' => $oldNorm,
                    '_raw_new' => $newNorm,
                ];
            }
        }

        return $diff;
    }

    /**
     * Cek sebelum update apakah edit atas record $id membutuhkan alasan koreksi (MRMIK 8).
     * Mengembalikan null jika boleh lanjut simpan, atau array hasil `needs_reason` jika wajib alasan.
     */
    public static function prepareBeforeUpdate($core, $table, $id, array $newPayload, array $opts = [])
    {
        $id = (int)$id;
        if ($id <= 0 || !self::isRegisteredTable($table)) {
            return null;
        }

        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Data formulir tidak ditemukan untuk dikoreksi.'];
        }
        if (!empty($existing['deleted_at'])) {
            return ['status' => 'error', 'message' => 'Formulir ini telah dibatalkan (VOID) dan tidak dapat diubah lagi.'];
        }

        // Tambahan detail anak untuk rm_edukasi_pasien_terintegrasi bila dikirim
        $oldCompare = $existing;
        $newCompare = $newPayload;
        if (isset($opts['old_detail_rows'])) {
            $oldCompare['detail_rows'] = $opts['old_detail_rows'];
        }
        if (isset($opts['new_detail_rows'])) {
            $newCompare['detail_rows'] = $opts['new_detail_rows'];
        }

        $diff = self::computeDiff($table, $oldCompare, $newCompare);
        if (empty($diff)) {
            return [
                'status'     => 'noop',
                'existing'   => $existing,
                'diff'       => [],
            ];
        }

        // Apakah perubahan HANYA melampirkan TTE/TTD pertama kali pada field kosong?
        $onlyInitialSignature = true;
        foreach ($diff as $f => $dInfo) {
            if (!self::isSignatureOnlyField($f, $dInfo['_raw_old'] ?? '', $dInfo['_raw_new'] ?? '')) {
                $onlyInitialSignature = false;
                break;
            }
        }

        $actor = self::getActorMeta($core, $opts['actor_username'] ?? null);
        $reason = trim((string)($opts['revision_reason'] ?? ($_POST['revision_reason'] ?? '')));
        $grace = self::checkGracePeriod($pdo, $table, $existing, $actor['username']);

        // Bebaskan jika hanya pembubuhan tanda tangan pertama tanpa mengubah isi klinis dan belum dibatalkan
        if ($onlyInitialSignature && empty($grace['tte_hash'])) {
            $grace['in_grace'] = true;
            $grace['needs_reason'] = false;
            $grace['why'] = 'Pembubuhan tanda tangan / TTE';
        }

        if ($grace['needs_reason'] && $reason === '') {
            $cleanDiff = [];
            foreach ($diff as $fk => $dv) {
                unset($dv['_raw_old'], $dv['_raw_new']);
                $cleanDiff[$fk] = $dv;
            }
            return [
                'status'          => 'needs_reason',
                'action'          => 'amend',
                'why'             => $grace['why'],
                'elapsed_minutes' => $grace['elapsed_minutes'],
                'changed_fields'  => $cleanDiff,
                'table_name'      => $table,
                'record_key'      => (string)$id,
                'message'         => $grace['why'] . '. Wajib mengisi alasan koreksi formulir (MRMIK 8).',
            ];
        }

        return [
            'status'    => 'allow',
            'existing'  => $existing,
            'diff'      => $diff,
            'grace'     => $grace,
            'actor'     => $actor,
            'reason'    => $reason,
        ];
    }

    /**
     * Tandai metadata entri baru tepat setelah INSERT berhasil.
     */
    public static function markCreated($core, $table, $id)
    {
        $id = (int)$id;
        if ($id <= 0 || !self::isRegisteredTable($table)) return;
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);
        $actor = self::getActorMeta($core);
        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare("UPDATE `{$table}` SET entry_created_at = COALESCE(entry_created_at, ?), created_by = COALESCE(NULLIF(created_by, ''), ?), revision_count = COALESCE(revision_count, 0) WHERE id = ?");
            $stmt->execute([$now, $actor['username'], $id]);
        } catch (\Throwable $e) {}
    }

    /**
     * Catat jejak revisi AMEND ke `mlite_rme_revisions` dan perbarui kolom audit setelah UPDATE tabel utama berhasil.
     */
    public static function commitRevision($core, $table, $id, array $prep, array $newSnapshotRow = [])
    {
        $id = (int)$id;
        if ($id <= 0 || empty($prep) || ($prep['status'] ?? '') !== 'allow') return null;

        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);
        $meta = self::getMetaByTable($table);
        if (!$meta) return null;

        $existing = $prep['existing'];
        $grace = $prep['grace'];
        $actor = $prep['actor'];
        $reason = $prep['reason'];
        $diff = $prep['diff'];

        $cleanDiff = [];
        foreach ($diff as $fk => $dv) {
            unset($dv['_raw_old'], $dv['_raw_new']);
            $cleanDiff[$fk] = $dv;
        }

        $now = date('Y-m-d H:i:s');
        $isGrace = !empty($grace['in_grace']) ? 1 : 0;
        $newRevCount = $isGrace ? (int)($existing['revision_count'] ?? 0) : ((int)($existing['revision_count'] ?? 0) + 1);

        $snapshotAfter = $existing;
        foreach ($newSnapshotRow as $k => $v) {
            $snapshotAfter[$k] = $v;
        }
        $snapshotAfter['revision_count'] = $newRevCount;
        if (!$isGrace) {
            $snapshotAfter['last_revised_at'] = $now;
        }

        try {
            // Update kolom audit di tabel utama
            $createdFallback = !empty($existing['entry_created_at'])
                ? $existing['entry_created_at']
                : (!empty($existing['created_at']) && $existing['created_at'] !== '0000-00-00 00:00:00' ? $existing['created_at'] : $now);

            if ($isGrace) {
                $upd = $pdo->prepare("UPDATE `{$table}` SET entry_created_at = COALESCE(entry_created_at, ?) WHERE id = ?");
                $upd->execute([$createdFallback, $id]);
            } else {
                $upd = $pdo->prepare("UPDATE `{$table}` SET entry_created_at = COALESCE(entry_created_at, ?), last_revised_at = ?, revision_count = ? WHERE id = ?");
                $upd->execute([$createdFallback, $now, $newRevCount, $id]);
            }

            $revReason = $reason !== '' ? $reason : 'Perbaikan ketik langsung dalam masa tenggang (<= 15 menit)';
            $noRawat = $existing['no_rawat'] ?? ($newSnapshotRow['no_rawat'] ?? '');
            $noRm = $existing['no_rkm_medis'] ?? ($newSnapshotRow['no_rkm_medis'] ?? self::getNoRm($pdo, $noRawat));

            $revSql = "INSERT INTO `mlite_rme_revisions`
                (module, table_name, record_key, no_rawat, no_rkm_medis, form_code, revision_no, action_type, is_grace_period, reason, changed_fields_json, snapshot_before_json, snapshot_after_json, tte_superseded_hash, actor_username, actor_name, actor_role, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'AMEND', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $revBinds = [
                'form_rme',
                $table,
                (string)$id,
                (string)$noRawat,
                $noRm,
                $meta['form_code'],
                $newRevCount,
                $isGrace,
                $revReason,
                json_encode($cleanDiff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($snapshotAfter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                !$isGrace ? ($grace['tte_hash'] ?? null) : null,
                $actor['username'],
                $actor['name'],
                $actor['role'],
                $actor['ip'],
                $now,
            ];
            $pdo->prepare($revSql)->execute($revBinds);

            return [
                'status'      => 'success',
                'mode'        => $isGrace ? 'grace_edit' : 'amend',
                'revision_no' => $newRevCount,
                'in_grace'    => (bool)$isGrace,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Soft-delete (VOID) record Formulir RME alih-alih DELETE permanen.
     */
    public static function voidRecord($core, $table, $id, $reason = '', $actorOverride = null)
    {
        $id = (int)$id;
        if ($id <= 0 || !self::isRegisteredTable($table)) {
            return ['status' => 'error', 'message' => 'Parameter formulir tidak valid untuk dibatalkan (VOID).'];
        }

        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);
        $meta = self::getMetaByTable($table);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$existing) {
            return ['status' => 'error', 'message' => 'Data formulir tidak ditemukan.'];
        }
        if (!empty($existing['deleted_at'])) {
            return ['status' => 'error', 'message' => 'Formulir ini sudah berstatus dibatalkan (VOID).'];
        }

        $actor = self::getActorMeta($core, $actorOverride);
        $reason = trim((string)$reason);
        $grace = self::checkGracePeriod($pdo, $table, $existing, $actor['username']);

        if ($grace['needs_reason'] && $reason === '') {
            return [
                'status'     => 'needs_reason',
                'action'     => 'void',
                'why'        => $grace['why'],
                'table_name' => $table,
                'record_key' => (string)$id,
                'message'    => $grace['why'] . '. Wajib mengisi alasan pembatalan / VOID formulir (MRMIK 8).',
            ];
        }

        $now = date('Y-m-d H:i:s');
        $isGrace = $grace['in_grace'] ? 1 : 0;
        $finalReason = $reason !== '' ? $reason : 'Dibatalkan dalam masa tenggang koreksi (<= 15 menit)';
        $revNo = (int)($existing['revision_count'] ?? 0) + 1;
        $noRawat = $existing['no_rawat'] ?? '';
        $noRm = $existing['no_rkm_medis'] ?? self::getNoRm($pdo, $noRawat);

        try {
            $pdo->beginTransaction();

            $updSql = "UPDATE `{$table}` SET deleted_at = ?, deleted_by = ?, void_reason = ? WHERE id = ?";
            $updBinds = [$now, $actor['username'], $finalReason, $id];
            $pdo->prepare($updSql)->execute($updBinds);
            self::logQuery($core, $updSql, $updBinds);

            $revSql = "INSERT INTO `mlite_rme_revisions`
                (module, table_name, record_key, no_rawat, no_rkm_medis, form_code, revision_no, action_type, is_grace_period, reason, changed_fields_json, snapshot_before_json, snapshot_after_json, tte_superseded_hash, actor_username, actor_name, actor_role, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'VOID', ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)";
            $revBinds = [
                'form_rme',
                $table,
                (string)$id,
                (string)$noRawat,
                $noRm,
                $meta['form_code'],
                $revNo,
                $isGrace,
                $finalReason,
                json_encode(['_status' => ['label' => 'Status Formulir', 'old' => 'Aktif', 'new' => 'DIBATALKAN (VOID)']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $grace['tte_hash'] ?? null,
                $actor['username'],
                $actor['name'],
                $actor['role'],
                $actor['ip'],
                $now,
            ];
            $pdo->prepare($revSql)->execute($revBinds);

            $pdo->commit();
            return [
                'status'  => 'success',
                'mode'    => 'void',
                'message' => 'Formulir berhasil dibatalkan (VOID) dan dicatat dalam log audit MRMIK 8.',
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 'error', 'message' => 'Gagal membatalkan formulir: ' . $e->getMessage()];
        }
    }

    /**
     * Lengkapi daftar baris formulir RME Rajal dengan metadata penandaan koreksi (MRMIK 8).
     */
    public static function enrichRows($core, $table, array $rows, $actorUsername = '')
    {
        if (empty($rows) || !self::isRegisteredTable($table)) {
            return $rows;
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);
        $meta = self::getMetaByTable($table);
        if ($actorUsername === '') {
            $actorUsername = self::getActorMeta($core)['username'];
        }

        $recordKeys = [];
        foreach ($rows as $r) {
            if (!empty($r['id'])) {
                $recordKeys[] = (string)$r['id'];
            }
        }
        $recordKeys = array_values(array_unique($recordKeys));

        $revByKey = [];
        if (!empty($recordKeys)) {
            $ph = implode(',', array_fill(0, count($recordKeys), '?'));
            $sql = "SELECT * FROM mlite_rme_revisions
                    WHERE table_name = ? AND record_key IN ({$ph})
                      AND ( (action_type = 'AMEND' AND is_grace_period = 0) OR action_type = 'VOID' )
                    ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$table], $recordKeys));
            while ($rev = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $revByKey[$rev['record_key']][] = $rev;
            }
        }

        foreach ($rows as &$row) {
            $key = isset($row['id']) ? (string)$row['id'] : '';
            $row['table_name'] = $table;
            $row['record_key'] = $key;
            $row['form_code'] = $meta['form_code'];
            $row['is_voided'] = !empty($row['deleted_at']);
            $row['void_info'] = $row['is_voided'] ? [
                'deleted_at'     => $row['deleted_at'],
                'deleted_at_fmt' => date('d-m-Y H:i', strtotime($row['deleted_at'])),
                'deleted_by'     => $row['deleted_by'] ?? '',
                'void_reason'    => $row['void_reason'] ?? '-',
            ] : null;

            $revs = ($key !== '' && isset($revByKey[$key])) ? $revByKey[$key] : [];
            $formalAmends = array_values(array_filter($revs, function ($r) {
                return ($r['action_type'] ?? '') === 'AMEND' && (int)($r['is_grace_period'] ?? 0) === 0;
            }));

            $changedMap = [];
            $lastAmend = null;
            foreach ($formalAmends as $rev) {
                $lastAmend = $rev;
                $cf = json_decode($rev['changed_fields_json'] ?? '[]', true);
                if (is_array($cf)) {
                    foreach ($cf as $field => $info) {
                        $changedMap[$field] = [
                            'field'       => $field,
                            'label'       => $info['label'] ?? $field,
                            'old'         => $info['old'] ?? '-',
                            'new'         => $info['new'] ?? '-',
                            'actor_name'  => $rev['actor_name'] ?? $rev['actor_username'],
                            'actor_role'  => $rev['actor_role'] ?? '',
                            'created_at'  => $rev['created_at'],
                            'reason'      => $rev['reason'] ?? '',
                            'revision_no' => (int)($rev['revision_no'] ?? 1),
                        ];
                    }
                }
            }

            $revCount = count($formalAmends);
            $row['revision_count'] = $revCount;
            $row['has_revisions'] = $revCount > 0;
            $row['last_revised_by'] = $lastAmend ? ($lastAmend['actor_name'] ?: $lastAmend['actor_username']) : '';
            $row['last_revised_at_fmt'] = ($lastAmend && !empty($lastAmend['created_at']))
                ? date('d-m-Y H:i', strtotime($lastAmend['created_at']))
                : (!empty($row['last_revised_at']) ? date('d-m-Y H:i', strtotime($row['last_revised_at'])) : '');
            $row['last_revised_reason'] = $lastAmend['reason'] ?? '';
            $row['changed_fields_map'] = $changedMap;
            $row['changed_fields_json'] = json_encode($changedMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $grace = ($key !== '')
                ? self::checkGracePeriod($pdo, $table, $row, $actorUsername)
                : ['in_grace' => true, 'needs_reason' => false, 'minutes_left' => 15, 'why' => '', 'tte_signed_at' => ''];

            $row['in_grace'] = $grace['in_grace'];
            $row['needs_reason'] = $grace['needs_reason'];
            $row['minutes_left'] = $grace['minutes_left'];
            $row['grace_why'] = $grace['why'];

            // Cek status TTE Kedaluwarsa (direvisi setelah TTE dibubuhkan)
            $isTteOutdated = false;
            if ($lastAmend) {
                if (!empty($lastAmend['tte_superseded_hash'])) {
                    $isTteOutdated = true;
                } elseif (!empty($grace['tte_signed_at']) && strtotime($lastAmend['created_at']) > strtotime($grace['tte_signed_at'])) {
                    $isTteOutdated = true;
                }
            }
            $row['is_tte_outdated'] = $isTteOutdated;

            // Format ringkasan koreksi untuk Banner Form & PDF Cetak
            $revSummaryLines = [];
            foreach ($formalAmends as $rev) {
                $cf = json_decode($rev['changed_fields_json'] ?? '[]', true);
                $parts = [];
                if (is_array($cf)) {
                    foreach ($cf as $info) {
                        $oldShort = mb_strimwidth((string)($info['old'] ?? '-'), 0, 45, '...');
                        $newShort = mb_strimwidth((string)($info['new'] ?? '-'), 0, 45, '...');
                        $parts[] = ($info['label'] ?? '') . ': "' . $oldShort . '" -> "' . $newShort . '"';
                    }
                }
                $revSummaryLines[] = [
                    'revision_no' => (int)$rev['revision_no'],
                    'created_at'  => date('d-m-Y H:i', strtotime($rev['created_at'])),
                    'actor_name'  => $rev['actor_name'] ?: $rev['actor_username'],
                    'actor_role'  => $rev['actor_role'] ?: '',
                    'reason'      => $rev['reason'],
                    'diff_text'   => implode('; ', $parts),
                ];
            }
            $row['revisions_summary'] = $revSummaryLines;
        }
        unset($row);

        return $rows;
    }

    public static function enrichSingleRow($core, $table, array $row)
    {
        if (empty($row['id']) || !self::isRegisteredTable($table)) {
            $row['revision_count'] = 0;
            $row['has_revisions'] = false;
            $row['is_voided'] = false;
            $row['is_tte_outdated'] = false;
            $row['needs_reason'] = false;
            $row['changed_fields_map'] = [];
            $row['changed_fields_json'] = '{}';
            $row['revisions_summary'] = [];
            return $row;
        }
        $enriched = self::enrichRows($core, $table, [$row]);
        return $enriched[0] ?? $row;
    }

    /**
     * Endpoint JSON timeline riwayat revisi formulir RME untuk Modal `#rsgmRevModal`.
     */
    public static function outputHistoryJson($core)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $table = trim((string)($_REQUEST['table_name'] ?? ''));
        $recordKey = trim((string)($_REQUEST['record_key'] ?? ''));
        $meta = self::getMetaByTable($table);
        if (!$meta || $recordKey === '') {
            echo json_encode(['status' => 'error', 'message' => 'Parameter formulir tidak valid.']);
            exit();
        }

        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo, $table);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$recordKey]);
        $currentRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$currentRow) {
            echo json_encode(['status' => 'error', 'message' => 'Data formulir tidak ditemukan.']);
            exit();
        }

        $timeline = self::buildTimelineForRecord($core, $pdo, $table, $recordKey, $currentRow, $meta);

        echo json_encode([
            'status'      => 'success',
            'table_name'  => $table,
            'record_key'  => $recordKey,
            'no_rawat'    => $currentRow['no_rawat'] ?? '',
            'is_voided'   => !empty($currentRow['deleted_at']),
            'void_reason' => $currentRow['void_reason'] ?? '',
            'timeline'    => $timeline,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Susun timeline entri awal + riwayat koreksi/void untuk 1 record RME.
     */
    private static function buildTimelineForRecord($core, $pdo, $table, $recordKey, array $currentRow, array $meta)
    {
        $stmtRev = $pdo->prepare("SELECT * FROM mlite_rme_revisions WHERE table_name = ? AND record_key = ? ORDER BY id ASC");
        $stmtRev->execute([$table, (string)$recordKey]);
        $revs = $stmtRev->fetchAll(\PDO::FETCH_ASSOC);

        $creatorUsername = trim((string)($currentRow['created_by'] ?? ''));
        $creatorName = $creatorUsername !== '' ? $core->getPegawaiInfo('nama', $creatorUsername) : '';
        if (empty($creatorName) || $creatorName === '-') {
            $creatorName = $creatorUsername !== '' ? $creatorUsername : ($currentRow['nama_petugas'] ?? $currentRow['nama_perawat'] ?? $currentRow['nama_dokter'] ?? $currentRow['nm_dpjp'] ?? $currentRow['petugas_nama'] ?? 'Petugas');
        }
        $createdAtStr = !empty($currentRow['entry_created_at'])
            ? $currentRow['entry_created_at']
            : (!empty($currentRow['created_at']) && $currentRow['created_at'] !== '0000-00-00 00:00:00' ? $currentRow['created_at'] : date('Y-m-d H:i:s'));

        $timeline = [];
        $timeline[] = [
            'version'     => 'v1 (Entri Awal)',
            'action_type' => 'CREATE',
            'is_grace'    => false,
            'actor_name'  => $creatorName,
            'actor_role'  => $meta['title'] ?? ($meta['form_code'] ?? 'RME'),
            'created_at'  => date('d-m-Y H:i:s', strtotime($createdAtStr)),
            'reason'      => 'Pencatatan awal formulir ' . ($meta['form_code'] ?? ''),
            'changes'     => [],
        ];

        $formalIdx = 1;
        foreach ($revs as $rev) {
            $isGrace = ((int)($rev['is_grace_period'] ?? 0) === 1);
            if ($isGrace && $rev['action_type'] === 'AMEND') {
                continue;
            }

            $cf = json_decode($rev['changed_fields_json'] ?? '[]', true);
            $changesList = [];
            if (is_array($cf)) {
                foreach ($cf as $field => $info) {
                    $changesList[] = [
                        'field' => $field,
                        'label' => $info['label'] ?? $field,
                        'old'   => $info['old'] ?? '-',
                        'new'   => $info['new'] ?? '-',
                    ];
                }
            }

            if ($rev['action_type'] === 'VOID') {
                $versionLabel = 'VOID (Dibatalkan)';
            } else {
                $formalIdx++;
                $versionLabel = 'v' . $formalIdx . ' (Koreksi #' . (int)$rev['revision_no'] . ')';
            }

            $timeline[] = [
                'version'        => $versionLabel,
                'action_type'    => $rev['action_type'],
                'is_grace'       => $isGrace,
                'actor_name'     => $rev['actor_name'] ?: $rev['actor_username'],
                'actor_role'     => $rev['actor_role'] ?: 'Petugas',
                'created_at'     => date('d-m-Y H:i:s', strtotime($rev['created_at'])),
                'reason'         => $rev['reason'],
                'changes'        => $changesList,
                'tte_superseded' => !empty($rev['tte_superseded_hash']),
            ];
        }

        return $timeline;
    }

    /**
     * Endpoint JSON tampilan baca ringkas generik (Opsi 2) untuk semua formulir RME.
     * Hanya menampilkan field yang terisi, tabel JSON bersarang, status TTE, dan riwayat koreksi MRMIK 8.
     */
    public static function outputDetailSummaryJson($core)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');

        $table = trim((string)($_REQUEST['table_name'] ?? ''));
        $recordKey = trim((string)($_REQUEST['record_key'] ?? ($_REQUEST['id'] ?? '')));
        $noRawatReq = trim((string)($_REQUEST['no_rawat'] ?? ''));

        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama tabel formulir tidak valid.']);
            exit();
        }

        $pdo = $core->db()->pdo();

        // Allowlist ketat: hanya tabel dari mlite_rme_form atau registry MRMIK 8 yang ada di database
        $meta = self::getMetaByTable($table);
        $formInfo = null;
        try {
            $stmtForm = $pdo->prepare("SELECT kode_form, nama_form, kategori, route_form, route_cetak, nama_tabel FROM mlite_rme_form WHERE nama_tabel = ? LIMIT 1");
            $stmtForm->execute([$table]);
            $formInfo = $stmtForm->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            $formInfo = null;
        }

        if (!$meta && !$formInfo) {
            echo json_encode(['status' => 'error', 'message' => 'Formulir tidak terdaftar pada sistem RME.']);
            exit();
        }

        $tableExistsStmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $tableExistsStmt->execute([$table]);
        if (!$tableExistsStmt->fetchColumn()) {
            echo json_encode(['status' => 'error', 'message' => 'Tabel formulir tidak ditemukan di database.']);
            exit();
        }

        $tblCols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_COLUMN);
        $pkCol = in_array('id', $tblCols, true) ? 'id' : (in_array('id_triase', $tblCols, true) ? 'id_triase' : '');
        $orderCol = $pkCol !== '' ? $pkCol : (in_array('created_at', $tblCols, true) ? 'created_at' : (in_array('tanggal', $tblCols, true) ? 'tanggal' : ''));

        if ($pkCol !== '' && $recordKey !== '' && ctype_digit($recordKey) && (int)$recordKey > 0) {
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `{$pkCol}` = ? LIMIT 1");
            $stmt->execute([(int)$recordKey]);
        } elseif ($noRawatReq !== '') {
            $orderSql = $orderCol !== '' ? "ORDER BY `{$orderCol}` DESC" : '';
            $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE no_rawat = ? {$orderSql} LIMIT 1");
            $stmt->execute([$noRawatReq]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Parameter record atau no_rawat wajib diisi.']);
            exit();
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Data isian formulir tidak ditemukan.']);
            exit();
        }

        $effectiveId = ($pkCol !== '' && isset($row[$pkCol])) ? (string)$row[$pkCol] : $recordKey;
        $labels = $meta['labels'] ?? [];

        // Enrich metadata revisi & status TTE bila terdaftar MRMIK 8
        $enriched = $meta ? self::enrichSingleRow($core, $table, $row) : $row;

        // Kumpulkan field terisi secara terstruktur (hanya yang tidak kosong)
        $ignoredCols = [
            'id', 'no_rawat', 'no_rkm_medis', 'created_at', 'updated_at', 'entry_created_at',
            'last_revised_at', 'revision_count', 'created_by', 'deleted_at', 'deleted_by', 'void_reason',
            'table_name', 'record_key', 'form_code',
        ];
        // Sembunyikan header metadata duplikat yang sudah tampil di banner pasien
        $redundantIdentityCols = [
            'nm_pasien', 'nama_pasien', 'tgl_lahir', 'tanggal_lahir', 'tgl_lahir_pasien',
            'jk', 'jenis_kelamin', 'umur', 'umur_pasien', 'alamat', 'alamat_pasien',
        ];

        $clinicalFields = [];
        $tablesSection = [];
        $signatureItems = [];

        $processKeyVal = function ($k, $v) use (&$clinicalFields, &$tablesSection, &$signatureItems, $labels, $ignoredCols, $redundantIdentityCols) {
            if (in_array($k, $ignoredCols, true) || in_array($k, $redundantIdentityCols, true)) {
                return;
            }
            if ($v === null) {
                return;
            }

            $label = self::humanizeField($k, $labels);
            $keyLower = strtolower((string)$k);
            $isSigKey = (
                strpos($keyLower, 'ttd_') === 0 || strpos($keyLower, 'tte_') === 0 ||
                strpos($keyLower, '_ttd') !== false || strpos($keyLower, '_tte') !== false ||
                $keyLower === 'signature'
            );

            // Decode JSON otomatis jika isi string berbentuk array/object
            $decoded = null;
            if (is_string($v)) {
                $trimmed = trim($v);
                if ($trimmed === '' || $trimmed === '0000-00-00' || $trimmed === '0000-00-00 00:00:00') {
                    return;
                }
                if (($trimmed[0] === '{' || $trimmed[0] === '[')) {
                    $tmpDec = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($tmpDec)) {
                        $decoded = $tmpDec;
                    }
                }
            } elseif (is_array($v)) {
                $decoded = $v;
            }

            if ($decoded !== null) {
                if (empty($decoded)) {
                    return;
                }
                // Jika form_data root object, ratakan kunci di dalamnya agar mudah dibaca
                if ($keyLower === 'form_data' && array_keys($decoded) !== range(0, count($decoded) - 1)) {
                    foreach ($decoded as $subK => $subV) {
                        self::appendDecodedSummaryEntry($subK, $subV, $labels, $clinicalFields, $tablesSection, $signatureItems);
                    }
                    return;
                }
                self::appendDecodedSummaryEntry($k, $decoded, $labels, $clinicalFields, $tablesSection, $signatureItems);
                return;
            }

            $strVal = trim((string)$v);
            if ($strVal === '' || $strVal === '0000-00-00' || $strVal === '0000-00-00 00:00:00') {
                return;
            }

            if ($isSigKey || strpos($strVal, 'data:image/') === 0 || preg_match('/^[a-f0-9]{64}$/i', $strVal)) {
                $signatureItems[] = [
                    'field'  => $k,
                    'label'  => $label,
                    'status' => preg_match('/^[a-f0-9]{64}$/i', $strVal) ? 'TTE Terverifikasi' : (strpos($strVal, 'data:image/') === 0 ? 'Tanda Tangan Digital Tersimpan' : $strVal),
                    'hash'   => preg_match('/^[a-f0-9]{64}$/i', $strVal) ? substr($strVal, 0, 14) . '...' : '',
                ];
                return;
            }

            $clinicalFields[] = [
                'field' => $k,
                'label' => $label,
                'value' => $strVal === '1' ? 'Ya' : $strVal,
            ];
        };

        foreach ($row as $col => $val) {
            $processKeyVal($col, $val);
        }

        // Ambil informasi penandatangan TTE dari mlite_esignatures bila ada
        $esignatures = [];
        try {
            $hashes = self::collectTteHashes($row, $meta['tte_cols'] ?? []);
            if (!empty($hashes)) {
                $ph = implode(',', array_fill(0, count($hashes), '?'));
                $stEs = $pdo->prepare("SELECT ref_type, signer_role, signer_id, signer_name, signed_at, signature_hash FROM mlite_esignatures WHERE signature_hash IN ({$ph}) ORDER BY signed_at ASC");
                $stEs->execute($hashes);
                foreach ($stEs->fetchAll(\PDO::FETCH_ASSOC) as $es) {
                    $esignatures[] = [
                        'role'        => $es['signer_role'] ?: $es['ref_type'],
                        'signer_name' => $es['signer_name'],
                        'signer_id'   => $es['signer_id'],
                        'signed_at'   => !empty($es['signed_at']) ? date('d-m-Y H:i', strtotime($es['signed_at'])) : '-',
                        'hash_short'  => substr((string)$es['signature_hash'], 0, 14) . '...',
                        'verify_url'  => url(['esignature', 'verify', $es['signature_hash']]),
                    ];
                }
            }
        } catch (\Throwable $e) {
            $esignatures = [];
        }

        $timeline = [];
        if ($meta) {
            $timeline = self::buildTimelineForRecord($core, $pdo, $table, $effectiveId, $row, $meta);
        }

        $title = $formInfo['nama_form'] ?? ($meta['title'] ?? $table);
        $formCode = $formInfo['kode_form'] ?? ($meta['form_code'] ?? strtoupper($table));
        $routeForm = $formInfo['route_form'] ?? '';
        $routeCetak = $formInfo['route_cetak'] ?? ($routeForm !== '' ? str_replace('form', 'cetak', $routeForm) : '');
        $noRawatVal = (string)($row['no_rawat'] ?? $noRawatReq);

        echo json_encode([
            'status' => 'success',
            'meta'   => [
                'table_name'         => $table,
                'record_key'         => $effectiveId,
                'kode_form'          => $formCode,
                'nama_form'          => $title,
                'kategori'           => $formInfo['kategori'] ?? 'RME',
                'no_rawat'           => $noRawatVal,
                'revision_count'     => (int)($enriched['revision_count'] ?? 0),
                'is_voided'          => !empty($enriched['is_voided']),
                'void_reason'        => (string)($enriched['void_reason'] ?? ''),
                'is_tte_outdated'    => !empty($enriched['is_tte_outdated']),
                'last_revised_at'    => (string)($enriched['last_revised_at_fmt'] ?? ''),
                'last_revised_by'    => (string)($enriched['last_revised_by'] ?? ''),
                'last_revised_reason'=> (string)($enriched['last_revised_reason'] ?? ''),
                'url_form'           => $routeForm !== '' ? url([ADMIN, 'form_rme', $routeForm]) . ($effectiveId !== '' ? '&id=' . urlencode($effectiveId) : '') . '&no_rawat=' . urlencode($noRawatVal) : '',
                'url_cetak'          => $routeCetak !== '' ? url([ADMIN, 'form_rme', $routeCetak]) . ($effectiveId !== '' ? '&id=' . urlencode($effectiveId) : '') . '&no_rawat=' . urlencode($noRawatVal) : '',
            ],
            'fields'      => $clinicalFields,
            'tables'      => $tablesSection,
            'signatures'  => $signatureItems,
            'esignatures' => $esignatures,
            'timeline'    => $timeline,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Format sub-array / JSON terstruktur ke daftar field atau tabel ringkas tanpa memotong teks klinis penting.
     */
    private static function appendDecodedSummaryEntry($key, $val, array $labels, array &$clinicalFields, array &$tablesSection, array &$signatureItems)
    {
        $label = self::humanizeField($key, $labels);
        if (!is_array($val)) {
            $strVal = trim((string)$val);
            if ($strVal === '' || $strVal === '0000-00-00') return;
            $keyLower = strtolower((string)$key);
            if (strpos($keyLower, 'ttd') !== false || strpos($keyLower, 'tte') !== false || preg_match('/^[a-f0-9]{64}$/i', $strVal)) {
                $signatureItems[] = [
                    'field'  => $key,
                    'label'  => $label,
                    'status' => preg_match('/^[a-f0-9]{64}$/i', $strVal) ? 'TTE Terverifikasi' : 'Tanda Tangan Tersimpan',
                    'hash'   => preg_match('/^[a-f0-9]{64}$/i', $strVal) ? substr($strVal, 0, 14) . '...' : '',
                ];
                return;
            }
            $clinicalFields[] = [
                'field' => $key,
                'label' => $label,
                'value' => $strVal === '1' ? 'Ya' : $strVal,
            ];
            return;
        }

        if (empty($val)) return;

        // List of associative row objects -> render sebagai mini tabel (mis. asesmen RJ07, detail_rows Edukasi)
        $isList = (array_keys($val) === range(0, count($val) - 1));
        if ($isList && is_array($val[0] ?? null)) {
            $colKeys = [];
            $cleanRows = [];
            foreach ($val as $item) {
                if (!is_array($item)) continue;
                $rowOut = [];
                $hasAny = false;
                foreach ($item as $ik => $iv) {
                    $ikLow = strtolower((string)$ik);
                    if ($ikLow === 'is_new_row' || $ikLow === 'qr_petugas' || $ikLow === 'tte_info') continue;
                    $sv = is_array($iv) ? json_encode($iv, JSON_UNESCAPED_UNICODE) : trim((string)$iv);
                    if ($sv === '') continue;
                    $hasAny = true;
                    if (preg_match('/^[a-f0-9]{64}$/i', $sv)) {
                        $sv = 'TTE (' . substr($sv, 0, 10) . '...)';
                    } elseif (strpos($sv, 'data:image/') === 0) {
                        $sv = '[Tanda Tangan Digital]';
                    }
                    $rowOut[$ik] = $sv;
                    if (!in_array($ik, $colKeys, true)) {
                        $colKeys[] = $ik;
                    }
                }
                if ($hasAny) {
                    $cleanRows[] = $rowOut;
                }
            }
            if (!empty($cleanRows)) {
                $colsMeta = [];
                foreach ($colKeys as $ck) {
                    $colsMeta[] = ['key' => $ck, 'label' => self::humanizeField($ck, $labels)];
                }
                $tablesSection[] = [
                    'field'   => $key,
                    'label'   => $label,
                    'columns' => $colsMeta,
                    'rows'    => $cleanRows,
                ];
            }
            return;
        }

        // Array skalar (mis. multi-checkbox)
        if ($isList) {
            $nonEmpty = array_values(array_filter(array_map('trim', array_map('strval', $val)), fn($x) => $x !== ''));
            if (!empty($nonEmpty)) {
                $clinicalFields[] = [
                    'field' => $key,
                    'label' => $label,
                    'value' => implode(', ', $nonEmpty),
                ];
            }
            return;
        }

        // Associative object
        $parts = [];
        foreach ($val as $sk => $sv) {
            $ssv = is_array($sv) ? json_encode($sv, JSON_UNESCAPED_UNICODE) : trim((string)$sv);
            if ($ssv !== '' && $ssv !== '0') {
                $parts[] = self::humanizeField($sk, $labels) . ': ' . ($ssv === '1' ? 'Ya' : $ssv);
            }
        }
        if (!empty($parts)) {
            $clinicalFields[] = [
                'field' => $key,
                'label' => $label,
                'value' => implode(' • ', $parts),
            ];
        }
    }

    /* =========================================================================
     * INTERCEPTOR GENERIK MRMIK 8 UNTUK SELURUH FORM IGD, RAWAT INAP, & BEDAH
     * ========================================================================= */

    private static function tableHasIdColumn(\PDO $pdo, string $table): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        if (!isset(self::$tableColsCache[$table])) {
            self::$tableColsCache[$table] = self::getValidTableColumns($pdo, $table);
        }
        return in_array('id', self::$tableColsCache[$table], true);
    }

    /**
     * Petakan nama method `form_rme` (*save / *simpan / *hapus / *delete) ke metadata formulir pada getRmeFormMap().
     */
    public static function resolveFormByRoute(array $formMap, string $rawMethod): ?array
    {
        $act = strtolower(trim($rawMethod));
        if ($act === '') {
            return null;
        }
        $adminSkip = [
            'manajemenrmesave', 'simpanaksesrme', 'simpanuserunitrme', 'tambahunitrme', 'hapusunitrme',
            'tambahformrme', 'hapusformrme', 'batchstatusform', 'syncunitsimrs', 'tteverifysave',
            'simpanaksesunit', 'edukasiprint', 'cetakberkas',
        ];
        if (in_array($act, $adminSkip, true)) {
            return null;
        }

        // Alias khusus yang nama method-nya sedikit berbeda dari route_manage
        $specialBaseMap = [
            'pelayanankerohanihapus' => 'pelayanankerohanian',
        ];
        $base = $specialBaseMap[$act] ?? preg_replace('/(save|simpan|hapus|delete)$/i', '', $act);
        if ($base === '' || $base === $act) {
            return null;
        }

        foreach ($formMap as $info) {
            $rm = preg_replace('/manage$/i', '', strtolower((string)($info['route_manage'] ?? '')));
            $rf = preg_replace('/form$/i', '', strtolower((string)($info['route_form'] ?? '')));
            $rc = preg_replace('/cetak$/i', '', strtolower((string)($info['route_cetak'] ?? '')));
            if ($base === $rm || $base === $rf || $base === $rc) {
                return $info;
            }
        }
        return null;
    }

    /**
     * Cegat aksi HAPUS/DELETE pada seluruh formulir IGD, Ranap, & Bedah agar menjadi Soft-Delete (VOID) berjejak MRMIK 8.
     * Mengembalikan true jika sudah ditangani (controller delete permanen tidak perlu dijalankan).
     */
    public static function interceptGenericVoid($core, array $formMap, string $rawMethod, array $params = []): bool
    {
        $act = strtolower(trim($rawMethod));
        if (!preg_match('/(hapus|delete)$/i', $act)) {
            return false;
        }
        self::setDynamicCatalog($formMap);
        $info = self::resolveFormByRoute($formMap, $act);
        if (!$info || empty($info['table'])) {
            return false;
        }
        $table = (string)$info['table'];
        if (self::isExplicitRajalTable($table) || !self::isRegisteredTable($table)) {
            return false;
        }

        $pdo = $core->db()->pdo();
        if (!self::tableHasIdColumn($pdo, $table)) {
            return false;
        }

        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? ($params[0] ?? 0)));
        if ($id <= 0) {
            return false;
        }
        $reason = trim((string)($_POST['void_reason'] ?? ($_GET['void_reason'] ?? '')));
        $res = self::voidRecord($core, $table, $id, $reason);

        $isAjax = !empty($_POST['ajax_void']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }

        $core->setNotify(($res['status'] ?? '') === 'success' ? 'success' : 'failure', $res['message'] ?? 'Proses pembatalan (VOID) selesai.');
        $fallbackUrl = !empty($info['route_manage']) ? url([ADMIN, 'form_rme', $info['route_manage']]) : ($_SERVER['HTTP_REFERER'] ?? url([ADMIN, 'form_rme', 'manajemenrme']));
        redirect($fallbackUrl);
        exit();
    }

    /**
     * Cegat aksi SAVE/SIMPAN pada seluruh formulir IGD, Ranap, & Bedah untuk:
     *  - Memblokir perubahan pada record VOID atau perubahan lewat 15 menit tanpa alasan koreksi (MRMIK 8)
     *  - Mencatat entri awal (`markCreated`) atau snapshot before/after revisi (`commitRevision`) setelah DB selesai ditulis
     */
    public static function interceptGenericSave($core, array $formMap, string $rawMethod): void
    {
        $act = strtolower(trim($rawMethod));
        if (!preg_match('/(save|simpan)$/i', $act)) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        self::setDynamicCatalog($formMap);
        $info = self::resolveFormByRoute($formMap, $act);
        if (!$info || empty($info['table'])) {
            return;
        }
        $table = (string)$info['table'];
        if (self::isExplicitRajalTable($table) || !self::isRegisteredTable($table)) {
            return;
        }

        $pdo = $core->db()->pdo();
        if (!self::tableHasIdColumn($pdo, $table)) {
            return;
        }
        self::ensureSchema($pdo, $table);

        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        $isAjax = !empty($_POST['ajax_save']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($id > 0) {
            $st = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
            $st->execute([$id]);
            $existing = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$existing) {
                return;
            }
            if (!empty($existing['deleted_at'])) {
                $err = ['status' => 'error', 'message' => 'Formulir ini sudah berstatus dibatalkan (VOID) dan tidak dapat diubah lagi (MRMIK 8).'];
                if ($isAjax) {
                    while (ob_get_level()) ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($err, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                $core->setNotify('failure', $err['message']);
                redirect($_SERVER['HTTP_REFERER'] ?? url([ADMIN, 'form_rme', $info['route_manage'] ?? 'manajemenrme']));
                exit();
            }

            $actor = self::getActorMeta($core);
            $grace = self::checkGracePeriod($pdo, $table, $existing, $actor['username']);
            $reason = trim((string)($_POST['revision_reason'] ?? ''));

            if (!empty($grace['needs_reason']) && mb_strlen($reason) < 3) {
                $resp = [
                    'status'     => 'needs_reason',
                    'action'     => 'amend',
                    'why'        => $grace['why'],
                    'table_name' => $table,
                    'record_key' => (string)$id,
                    'message'    => ($grace['why'] ?: 'Perubahan melewati masa tenggang 15 menit') . '. Wajib mengisi alasan koreksi (MRMIK 8).',
                ];
                if ($isAjax) {
                    while (ob_get_level()) ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                $core->setNotify('failure', $resp['message']);
                redirect($_SERVER['HTTP_REFERER'] ?? url([ADMIN, 'form_rme', $info['route_form'] ?? 'manajemenrme']));
                exit();
            }

            // Daftarkan shutdown hook untuk membandingkan snapshot DB sesudah controller selesai menyimpan
            register_shutdown_function(function () use ($core, $pdo, $table, $id, $existing, $actor, $grace, $reason) {
                try {
                    $stAfter = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
                    $stAfter->execute([$id]);
                    $afterRow = $stAfter->fetch(\PDO::FETCH_ASSOC);
                    if (!$afterRow) {
                        return;
                    }
                    $diff = self::computeDiff($table, $existing, $afterRow);
                    if (empty($diff)) {
                        return;
                    }
                    $prep = [
                        'status'   => 'allow',
                        'existing' => $existing,
                        'diff'     => $diff,
                        'grace'    => $grace,
                        'actor'    => $actor,
                        'reason'   => $reason,
                    ];
                    self::commitRevision($core, $table, $id, $prep, $afterRow);
                } catch (\Throwable $e) {
                }
            });
            return;
        }

        // Entri baru (INSERT): tandai entry_created_at & created_by setelah baris baru berhasil disimpan
        $maxIdBefore = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM `{$table}`")->fetchColumn();
        register_shutdown_function(function () use ($core, $pdo, $table, $maxIdBefore) {
            try {
                $stNew = $pdo->prepare("SELECT id FROM `{$table}` WHERE id > ? ORDER BY id DESC LIMIT 1");
                $stNew->execute([$maxIdBefore]);
                $newId = (int)$stNew->fetchColumn();
                if ($newId > 0) {
                    self::markCreated($core, $table, $newId);
                }
            } catch (\Throwable $e) {
            }
        });
    }
}

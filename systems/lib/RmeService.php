<?php

namespace Systems\Lib;

/**
 * Layanan Terpadu Rekam Medis Elektronik (RME):
 *  - Audit Log Akses Baca & Cetak e-RM (mlite_rme_access_logs)
 *  - Review Kelengkapan Rekam Medis / KLPCM Kuantitatif & Kualitatif (mlite_klpcm_reviews)
 *  - Kamus Daftar Singkatan & Simbol Terstandar / Do Not Use (mlite_mrmik_singkatan)
 */
class RmeService
{
    private static $schemaEnsured = false;

    /**
     * Hak akses petugas/admin Rekam Medis (admin & rekammedis).
     */
    public static function canManageRmeAudit($core): bool
    {
        return PasienMergeService::canManageMerge($core);
    }

    /**
     * Pastikan tabel layanan RME tersedia dan seed singkatan standar RSGM bila masih kosong.
     */
    public static function ensureSchema(\PDO $pdo): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        self::$schemaEnsured = true;

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mlite_rme_access_logs` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL DEFAULT 0,
                `username` VARCHAR(60) NOT NULL DEFAULT '',
                `fullname` VARCHAR(120) NOT NULL DEFAULT '',
                `user_role` VARCHAR(40) NOT NULL DEFAULT '',
                `action_type` VARCHAR(40) NOT NULL DEFAULT 'VIEW_RIWAYAT',
                `module` VARCHAR(50) NOT NULL DEFAULT '',
                `route` VARCHAR(120) NOT NULL DEFAULT '',
                `no_rkm_medis` VARCHAR(20) NOT NULL DEFAULT '',
                `nm_pasien` VARCHAR(100) NOT NULL DEFAULT '',
                `no_rawat` VARCHAR(30) NOT NULL DEFAULT '',
                `detail` VARCHAR(255) NOT NULL DEFAULT '',
                `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
                `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_no_rm` (`no_rkm_medis`),
                KEY `idx_no_rawat` (`no_rawat`),
                KEY `idx_username_action` (`username`, `action_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mlite_klpcm_reviews` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `no_rawat` VARCHAR(30) NOT NULL,
                `no_rkm_medis` VARCHAR(20) NOT NULL DEFAULT '',
                `status_review` ENUM('Lengkap','Belum Lengkap','Perlu Revisi','Menunggu Verifikasi RM') NOT NULL DEFAULT 'Belum Lengkap',
                `konsistensi_klinis` TINYINT(1) NOT NULL DEFAULT 0,
                `kejelasan_catatan` TINYINT(1) NOT NULL DEFAULT 0,
                `informed_consent_ok` TINYINT(1) NOT NULL DEFAULT 0,
                `skor_kuantitatif` TINYINT(3) NOT NULL DEFAULT 0,
                `catatan` TEXT NULL,
                `target_ppas` VARCHAR(255) NOT NULL DEFAULT '',
                `returned_at` DATETIME NULL DEFAULT NULL,
                `due_at` DATETIME NULL DEFAULT NULL,
                `resubmitted_at` DATETIME NULL DEFAULT NULL,
                `resubmitted_by` VARCHAR(120) NOT NULL DEFAULT '',
                `resubmit_note` TEXT NULL,
                `reviewer_username` VARCHAR(60) NOT NULL DEFAULT '',
                `reviewer_name` VARCHAR(120) NOT NULL DEFAULT '',
                `reviewed_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_no_rawat` (`no_rawat`),
                KEY `idx_status_review` (`status_review`),
                KEY `idx_reviewed_at` (`reviewed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Upgrade kolom workflow KLPCM secara idempoten bila tabel sudah ada sebelumnya
        try {
            $revCols = [];
            $statusType = '';
            foreach ($pdo->query("SHOW COLUMNS FROM `mlite_klpcm_reviews`")->fetchAll(\PDO::FETCH_ASSOC) as $rc) {
                $revCols[$rc['Field']] = true;
                if ($rc['Field'] === 'status_review') {
                    $statusType = (string)$rc['Type'];
                }
            }
            if (strpos($statusType, 'Menunggu Verifikasi RM') === false) {
                $pdo->exec("ALTER TABLE `mlite_klpcm_reviews` MODIFY COLUMN `status_review` ENUM('Lengkap','Belum Lengkap','Perlu Revisi','Menunggu Verifikasi RM') NOT NULL DEFAULT 'Belum Lengkap'");
            }
            $extraRevCols = [
                'target_ppas'    => "ADD COLUMN `target_ppas` VARCHAR(255) NOT NULL DEFAULT '' AFTER `catatan`",
                'returned_at'    => "ADD COLUMN `returned_at` DATETIME NULL DEFAULT NULL AFTER `target_ppas`",
                'due_at'         => "ADD COLUMN `due_at` DATETIME NULL DEFAULT NULL AFTER `returned_at`",
                'resubmitted_at' => "ADD COLUMN `resubmitted_at` DATETIME NULL DEFAULT NULL AFTER `due_at`",
                'resubmitted_by' => "ADD COLUMN `resubmitted_by` VARCHAR(120) NOT NULL DEFAULT '' AFTER `resubmitted_at`",
                'resubmit_note'  => "ADD COLUMN `resubmit_note` TEXT NULL AFTER `resubmitted_by`",
            ];
            $alters = [];
            foreach ($extraRevCols as $cName => $ddl) {
                if (!isset($revCols[$cName])) {
                    $alters[] = $ddl;
                }
            }
            if (!empty($alters)) {
                $pdo->exec("ALTER TABLE `mlite_klpcm_reviews` " . implode(', ', $alters));
            }
        } catch (\Throwable $e) {
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mlite_klpcm_notifications` (
                `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `no_rawat` VARCHAR(30) NOT NULL,
                `no_rkm_medis` VARCHAR(20) NOT NULL DEFAULT '',
                `nm_pasien` VARCHAR(100) NOT NULL DEFAULT '',
                `target_username` VARCHAR(60) NOT NULL,
                `target_name` VARCHAR(120) NOT NULL DEFAULT '',
                `notif_type` ENUM('RETURNED_TO_PPA','RESUBMITTED_TO_RM') NOT NULL DEFAULT 'RETURNED_TO_PPA',
                `status_review` VARCHAR(40) NOT NULL DEFAULT 'Perlu Revisi',
                `message` TEXT NULL,
                `due_at` DATETIME NULL DEFAULT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` VARCHAR(60) NOT NULL DEFAULT '',
                `created_by_name` VARCHAR(120) NOT NULL DEFAULT '',
                `created_at` DATETIME NOT NULL,
                `resolved_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_target_active` (`target_username`, `is_resolved`, `created_at`),
                KEY `idx_no_rawat` (`no_rawat`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mlite_mrmik_singkatan` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `kode` VARCHAR(50) NOT NULL,
                `kepanjangan` VARCHAR(255) NOT NULL,
                `jenis` ENUM('diperbolehkan','dilarang','simbol') NOT NULL DEFAULT 'diperbolehkan',
                `kategori` VARCHAR(60) NOT NULL DEFAULT 'Umum',
                `alternatif_benar` VARCHAR(255) NOT NULL DEFAULT '',
                `keterangan` TEXT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `created_by` VARCHAR(60) NOT NULL DEFAULT 'system',
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_jenis_kategori` (`jenis`, `kategori`),
                KEY `idx_kode` (`kode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::seedDefaultSingkatan($pdo);
    }

    /* =========================================================================
     * 1. AUDIT LOG AKSES BACA & CETAK e-RM
     * ========================================================================= */

    public static function actionLabels(): array
    {
        return [
            'VIEW_RIWAYAT'   => 'Lihat Riwayat e-RM',
            'PRINT_PDF'      => 'Cetak / Unduh PDF e-RM',
            'VIEW_TELUSUR'   => 'Telusur e-RM Pasien',
            'VIEW_RME_FORM'  => 'Buka Form RME Pasien',
            'PRINT_RME_FORM' => 'Cetak Form RME',
            'VIEW_TIMELINE'  => 'Buka Lembar Kerja / Katalog RME',
            'EXPORT_DATA'    => 'Ekspor Data Rekam Medis',
        ];
    }

    /**
     * Catat akses baca/cetak e-RM secara otomatis dari Systems\Admin::loadModule().
     */
    public static function captureRequestAccess($core, string $moduleName, string $rawMethod, array $params = []): void
    {
        try {
            if (empty($_SESSION['mlite_user'])) {
                return;
            }
            // Jangan catat bila Mode Simulasi / Pratinjau tanpa konteks pasien
            if ((string)($_GET['preview_mode'] ?? '') === '1' || (string)($_POST['preview_mode'] ?? '') === '1') {
                return;
            }

            $mod = strtolower($moduleName);
            $act = strtolower($rawMethod);
            $actionType = '';
            $noRm = '';
            $noRawat = '';
            $detail = '';

            if ($mod === 'pasien') {
                if ($act === 'riwayatperawatan') {
                    $actionType = 'VIEW_RIWAYAT';
                    $noRm = trim((string)($params[0] ?? ($_GET['no_rkm_medis'] ?? '')));
                    $detail = 'Membuka riwayat perawatan lengkap e-RM';
                } elseif ($act === 'pdfriwayatperawatan') {
                    $actionType = 'PRINT_PDF';
                    $noRm = trim((string)($params[0] ?? ($_GET['no_rkm_medis'] ?? '')));
                    $detail = 'Mencetak / mengunduh PDF riwayat perawatan e-RM';
                } elseif ($act === 'telusur' && !empty($_GET['s'])) {
                    $actionType = 'VIEW_TELUSUR';
                    $q = trim((string)$_GET['s']);
                    if (preg_match('/^\d{5,8}$/', $q)) {
                        $noRm = $q;
                    }
                    $detail = 'Pencarian Telusur e-RM: ' . mb_substr($q, 0, 80);
                }
            } elseif ($mod === 'form_rme' || $mod === 'update_bmt') {
                $firstParam = isset($params[0]) ? trim((string)$params[0]) : '';
                $candidateRawat = trim((string)($_GET['no_rawat'] ?? ($_POST['no_rawat'] ?? '')));
                if ($candidateRawat === '' && $firstParam !== '' && preg_match('/^\d{4}[_\/-]\d{2}[_\/-]\d{2}[_\/-]\d+$/', $firstParam)) {
                    $candidateRawat = str_replace('_', '/', $firstParam);
                }
                $candidateId = (int)($_GET['id'] ?? ($_POST['id'] ?? (ctype_digit($firstParam) ? $firstParam : 0)));

                if (in_array($act, ['katalogrme', 'rmehistory', 'pasien'], true) && $candidateRawat !== '') {
                    $actionType = 'VIEW_TIMELINE';
                    $noRawat = $candidateRawat;
                    $detail = 'Membuka lembar kerja / katalog RME (' . $act . ')';
                } elseif (($act === 'cetakberkas' || $act === 'cetak' || preg_match('/(cetak|pdf|print)$/i', $act)) && ($candidateRawat !== '' || $candidateId > 0)) {
                    $actionType = 'PRINT_RME_FORM';
                    $noRawat = $candidateRawat;
                    $detail = $act === 'cetakberkas'
                        ? 'Cetak berkas gabungan Form RME (PDF)'
                        : 'Cetak formulir RME: ' . strtoupper(preg_replace('/(cetak|pdf|print)$/i', '', $act)) . ($candidateId > 0 ? ' (ID #' . $candidateId . ')' : '');
                } elseif (preg_match('/form$/i', $act) && ($candidateRawat !== '' || $candidateId > 0)) {
                    $actionType = 'VIEW_RME_FORM';
                    $noRawat = $candidateRawat;
                    $detail = 'Membuka formulir RME: ' . strtoupper(preg_replace('/form$/i', '', $act)) . ($candidateId > 0 ? ' (ID #' . $candidateId . ')' : '');
                }
            }

            if ($actionType === '') {
                return;
            }

            // Bila hanya punya ID form (tanpa no_rawat), coba resolve cepat dari getRmeFormMap
            if ($noRawat === '' && $noRm === '' && ($mod === 'form_rme' || $mod === 'update_bmt') && isset($candidateId) && $candidateId > 0) {
                $pdo = $core->db()->pdo();
                if (isset($core->module->form_rme) && method_exists($core->module->form_rme, 'getRmeFormMap')) {
                    $fmap = $core->module->form_rme->getRmeFormMap();
                    $base = preg_replace('/(form|cetak)$/i', '', $act);
                    foreach ($fmap as $fi) {
                        $rf = strtolower((string)($fi['route_form'] ?? ''));
                        $rc = strtolower((string)($fi['route_cetak'] ?? ''));
                        if ($rf === $act || $rc === $act || strpos($rf, $base) === 0) {
                            $tbl = (string)($fi['table'] ?? '');
                            if ($tbl !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
                                try {
                                    $st = $pdo->prepare("SELECT no_rawat FROM `{$tbl}` WHERE id = ? LIMIT 1");
                                    $st->execute([$candidateId]);
                                    $foundNr = $st->fetchColumn();
                                    if ($foundNr) {
                                        $noRawat = (string)$foundNr;
                                    }
                                } catch (\Throwable $e) {
                                }
                            }
                            break;
                        }
                    }
                }
            }

            self::logAccess($core, $actionType, $mod, $mod . '/' . $act, $noRm, $noRawat, $detail);
        } catch (\Throwable $e) {
            // Jangan pernah mengganggu alur utama bila logging gagal
        }
    }

    public static function logAccess(
        $core,
        string $actionType,
        string $module,
        string $route,
        string $noRm = '',
        string $noRawat = '',
        string $detail = ''
    ): void {
        try {
            $pdo = $core->db()->pdo();
            self::ensureSchema($pdo);

            $userId = (int)($_SESSION['mlite_user'] ?? 0);
            if ($userId <= 0) {
                return;
            }
            $username = (string)$core->getUserInfo('username', $userId, true);
            $fullname = (string)$core->getUserInfo('fullname', $userId, true);
            $userRole = (string)$core->getUserInfo('role', $userId, true);
            if ($fullname === '') {
                $fullname = $username;
            }

            $noRawat = str_replace('_', '/', trim($noRawat));
            $noRm = trim($noRm);
            $nmPasien = '';

            if ($noRawat !== '' && $noRm === '') {
                $st = $pdo->prepare("SELECT r.no_rkm_medis, p.nm_pasien FROM reg_periksa r LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis WHERE r.no_rawat = ? LIMIT 1");
                $st->execute([$noRawat]);
                $row = $st->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $noRm = (string)($row['no_rkm_medis'] ?? '');
                    $nmPasien = (string)($row['nm_pasien'] ?? '');
                }
            } elseif ($noRm !== '') {
                $st = $pdo->prepare("SELECT nm_pasien FROM pasien WHERE no_rkm_medis = ? LIMIT 1");
                $st->execute([$noRm]);
                $nmPasien = (string)($st->fetchColumn() ?: '');
            }

            // Dedup 5 detik agar request ganda / AJAX cepat tidak duplikat
            $stDup = $pdo->prepare("
                SELECT id FROM mlite_rme_access_logs
                WHERE user_id = ? AND action_type = ? AND route = ? AND no_rkm_medis = ? AND no_rawat = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
                LIMIT 1
            ");
            $stDup->execute([$userId, $actionType, $route, $noRm, $noRawat]);
            if ($stDup->fetchColumn()) {
                return;
            }

            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);

            $ins = $pdo->prepare("
                INSERT INTO mlite_rme_access_logs
                (user_id, username, fullname, user_role, action_type, module, route, no_rkm_medis, nm_pasien, no_rawat, detail, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([
                $userId,
                $username,
                $fullname,
                $userRole,
                $actionType,
                $module,
                $route,
                $noRm,
                $nmPasien,
                $noRawat,
                mb_substr($detail, 0, 250),
                $ip,
                $ua,
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function listAccessLogs(\PDO $pdo, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        self::ensureSchema($pdo);

        $where = ['1=1'];
        $bind = [];

        $tglAwal = trim((string)($filters['tgl_awal'] ?? date('Y-m-d', strtotime('-7 days'))));
        $tglAkhir = trim((string)($filters['tgl_akhir'] ?? date('Y-m-d')));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) {
            $where[] = 'created_at >= ?';
            $bind[] = $tglAwal . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) {
            $where[] = 'created_at <= ?';
            $bind[] = $tglAkhir . ' 23:59:59';
        }

        $actionType = trim((string)($filters['action_type'] ?? ''));
        if ($actionType !== '' && $actionType !== 'all') {
            $where[] = 'action_type = ?';
            $bind[] = $actionType;
        }

        $role = trim((string)($filters['user_role'] ?? ''));
        if ($role !== '' && $role !== 'all') {
            $where[] = 'user_role = ?';
            $bind[] = $role;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(no_rkm_medis LIKE ? OR nm_pasien LIKE ? OR no_rawat LIKE ? OR username LIKE ? OR fullname LIKE ? OR detail LIKE ? OR ip_address LIKE ?)';
            array_push($bind, $like, $like, $like, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        // Summary KPI
        $stSum = $pdo->prepare("
            SELECT
                COUNT(*) AS total_akses,
                SUM(CASE WHEN action_type IN ('PRINT_PDF','PRINT_RME_FORM','EXPORT_DATA') THEN 1 ELSE 0 END) AS total_cetak,
                COUNT(DISTINCT username) AS pengguna_unik,
                COUNT(DISTINCT NULLIF(no_rkm_medis, '')) AS pasien_unik
            FROM mlite_rme_access_logs
            WHERE {$whereSql}
        ");
        $stSum->execute($bind);
        $summary = $stSum->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_akses' => 0,
            'total_cetak' => 0,
            'pengguna_unik' => 0,
            'pasien_unik' => 0,
        ];

        $total = (int)($summary['total_akses'] ?? 0);
        $page = max(1, $page);
        $perPage = max(5, min(200, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $stRows = $pdo->prepare("
            SELECT * FROM mlite_rme_access_logs
            WHERE {$whereSql}
            ORDER BY id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stRows->execute($bind);
        $rows = $stRows->fetchAll(\PDO::FETCH_ASSOC);

        $labels = self::actionLabels();
        foreach ($rows as &$r) {
            $r['action_label'] = $labels[$r['action_type']] ?? $r['action_type'];
        }
        unset($r);

        return [
            'summary' => [
                'total_akses'   => (int)($summary['total_akses'] ?? 0),
                'total_cetak'   => (int)($summary['total_cetak'] ?? 0),
                'pengguna_unik' => (int)($summary['pengguna_unik'] ?? 0),
                'pasien_unik'   => (int)($summary['pasien_unik'] ?? 0),
            ],
            'rows' => $rows,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /* =========================================================================
     * 2. DASHBOARD REVIEW KELENGKAPAN REKAM MEDIS (KLPCM)
     * ========================================================================= */

    public static function getKlpcmFilterOptions(\PDO $pdo): array
    {
        $polis = $pdo->query("SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli ASC")->fetchAll(\PDO::FETCH_ASSOC);
        return ['poliklinik' => $polis ?: []];
    }

    /**
     * Cek apakah suatu nilai field formulir RME dianggap sudah terisi (bukan kosong / placeholder).
     */
    private static function isRmeFieldFilled($val): bool
    {
        if ($val === null) {
            return false;
        }
        if (is_array($val)) {
            foreach ($val as $item) {
                if (self::isRmeFieldFilled($item)) {
                    return true;
                }
            }
            return false;
        }
        $s = trim((string)$val);
        if ($s === '' || $s === '-' || $s === '0000-00-00' || $s === '0000-00-00 00:00:00' || $s === '[]' || $s === '{}' || $s === 'null') {
            return false;
        }
        return true;
    }

    /**
     * Evaluasi kelengkapan pengisian field pada 1 baris formulir RME yang benar-benar diisi pada suatu kunjungan.
     * - Hanya mengecek form yang memang diisi (form yang tidak diisi pada kunjungan tidak ikut dinilai).
     * - Mengabaikan kolom metadata sistem, audit revisi, dan kolom opsional (*_lainnya, *_ket, *_catatan).
     * - Mendeteksi TTE / tanda tangan secara terpisah.
     */
    public static function evaluateRmeRowCompleteness(string $table, array $row, array $formInfo): array
    {
        $ignoredCols = [
            'id' => true, 'id_triase' => true, 'no_rawat' => true, 'no_rkm_medis' => true, 'no_rm' => true,
            'nm_pasien' => true, 'nama_pasien' => true, 'tgl_lahir' => true, 'jk' => true, 'jenis_kelamin' => true,
            'alamat' => true, 'umur' => true, 'created_at' => true, 'updated_at' => true,
            'entry_created_at' => true, 'last_revised_at' => true, 'revision_count' => true,
            'created_by' => true, 'updated_by' => true, 'user_id' => true, 'deleted_at' => true,
            'deleted_by' => true, 'void_reason' => true, 'kop_img' => true, 'unit' => true,
            'kd_poli' => true, 'status' => true,
        ];

        $meta = class_exists('\Systems\Lib\RmeFormRevision') ? RmeFormRevision::getMetaByTable($table) : null;
        $labelMap = is_array($meta['labels'] ?? null) ? $meta['labels'] : [];

        $humanize = function (string $col) use ($labelMap): string {
            if (isset($labelMap[$col])) {
                return (string)$labelMap[$col];
            }
            return ucwords(str_replace('_', ' ', $col));
        };

        $total = 0;
        $filled = 0;
        $missing = [];
        $hasTte = false;

        foreach ($row as $col => $val) {
            $colLower = strtolower((string)$col);
            if (isset($ignoredCols[$colLower])) {
                continue;
            }
            // Cek kolom tanda tangan / TTE secara terpisah
            if (preg_match('/^(ttd_|tte_|tanda_tangan|paraf_|sign_)/i', $colLower) || strpos($colLower, '_ttd') !== false || strpos($colLower, '_tte') !== false) {
                if (self::isRmeFieldFilled($val)) {
                    $hasTte = true;
                }
                continue;
            }
            // Abaikan kolom keterangan opsional / "lainnya" agar form tidak unfairly turun skornya
            if (preg_match('/(_lain|_lainnya|_ket$|_keterangan$|_tambahan$|_notes$|_alasan$|_lain_text$|_detail$)/i', $colLower)) {
                continue;
            }

            // Jika kolom menyimpan JSON terstruktur (form_data / data_json), evaluasi isian di dalamnya
            if (in_array($colLower, ['form_data', 'data_json'], true) && is_string($val)) {
                $decoded = json_decode($val, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
                    if ($isAssoc) {
                        foreach ($decoded as $jk => $jv) {
                            $jkLower = strtolower((string)$jk);
                            if (isset($ignoredCols[$jkLower]) || preg_match('/(_lain|_lainnya|_ket$|_keterangan$|_tambahan$|_notes$)/i', $jkLower)) {
                                continue;
                            }
                            if (preg_match('/^(ttd_|tte_|tanda_tangan|paraf_|sign_)/i', $jkLower)) {
                                if (self::isRmeFieldFilled($jv)) {
                                    $hasTte = true;
                                }
                                continue;
                            }
                            $total++;
                            if (self::isRmeFieldFilled($jv)) {
                                $filled++;
                            } else {
                                $missing[] = $humanize((string)$jk);
                            }
                        }
                        continue;
                    }
                }
            }

            $total++;
            if (self::isRmeFieldFilled($val)) {
                $filled++;
            } else {
                $missing[] = $humanize((string)$col);
            }
        }

        $pct = $total > 0 ? (int)round(($filled / $total) * 100) : 100;
        $rowId = (int)($row['id'] ?? ($row['id_triase'] ?? 0));

        return [
            'id'             => $rowId,
            'kode'           => (string)($formInfo['kode'] ?? strtoupper($table)),
            'nama'           => (string)($formInfo['nama'] ?? $table),
            'table'          => $table,
            'route_form'     => (string)($formInfo['route_form'] ?? ''),
            'route_cetak'    => (string)($formInfo['route_cetak'] ?? ''),
            'filled'         => $filled,
            'total'          => $total,
            'pct'            => $pct,
            'is_complete'    => ($pct >= 80),
            'has_tte'        => $hasTte,
            'missing_fields' => array_slice($missing, 0, 12),
            'missing_count'  => count($missing),
        ];
    }

    /**
     * Evaluasi kuantitatif + kualitatif KLPCM per kunjungan pada periode tertentu,
     * termasuk pengecekan kelengkapan isian masing-masing formulir RME yang diisi pada kunjungan tsb.
     */
    public static function evaluateKlpcm(\PDO $pdo, array $filters = [], int $page = 1, int $perPage = 25, array $rmeFormMap = []): array
    {
        self::ensureSchema($pdo);

        $tglAwal = trim((string)($filters['tgl_awal'] ?? date('Y-m-01')));
        $tglAkhir = trim((string)($filters['tgl_akhir'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) {
            $tglAwal = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) {
            $tglAkhir = date('Y-m-d');
        }

        $where = ['r.tgl_registrasi BETWEEN ? AND ?', "r.stts != 'Batal'"];
        $bind = [$tglAwal, $tglAkhir];

        $statusLanjut = trim((string)($filters['status_lanjut'] ?? 'all'));
        if ($statusLanjut === 'Ralan' || $statusLanjut === 'Ranap') {
            $where[] = 'r.status_lanjut = ?';
            $bind[] = $statusLanjut;
        } elseif ($statusLanjut === 'IGD') {
            $where[] = "r.kd_poli IN ('IGD','UGD','IGDK')";
        }

        $kdPoli = trim((string)($filters['kd_poli'] ?? 'all'));
        if ($kdPoli !== '' && $kdPoli !== 'all') {
            $where[] = 'r.kd_poli = ?';
            $bind[] = $kdPoli;
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(r.no_rawat LIKE ? OR r.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR d.nm_dokter LIKE ?)';
            array_push($bind, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        // Ambil maksimal 600 kunjungan periode untuk akurasi KPI & performa cepat
        $st = $pdo->prepare("
            SELECT
                r.no_rawat, r.tgl_registrasi, r.jam_reg, r.no_rkm_medis, r.kd_poli, r.kd_dokter,
                r.status_lanjut, r.stts,
                p.nm_pasien, p.no_ktp, p.tgl_lahir, p.alamat,
                pol.nm_poli, d.nm_dokter,
                rev.status_review, rev.konsistensi_klinis, rev.kejelasan_catatan,
                rev.informed_consent_ok, rev.catatan AS review_catatan,
                rev.target_ppas, rev.returned_at, rev.due_at,
                rev.resubmitted_at, rev.resubmitted_by, rev.resubmit_note,
                rev.reviewer_name, rev.reviewed_at
            FROM reg_periksa r
            LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
            LEFT JOIN poliklinik pol ON pol.kd_poli = r.kd_poli
            LEFT JOIN dokter d ON d.kd_dokter = r.kd_dokter
            LEFT JOIN mlite_klpcm_reviews rev ON rev.no_rawat = r.no_rawat
            WHERE {$whereSql}
            ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
            LIMIT 600
        ");
        $st->execute($bind);
        $visits = $st->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($visits)) {
            return [
                'summary' => [
                    'total_kunjungan' => 0,
                    'total_lengkap' => 0,
                    'persen_lengkap' => 0,
                    'rata_skor' => 0,
                    'sudah_direview' => 0,
                    'indikator' => [
                        'identitas' => 0,
                        'asesmen_soap' => 0,
                        'diagnosa_icd' => 0,
                        'autentikasi_tte' => 0,
                        'resume_rme' => 0,
                        'tepat_waktu' => 0,
                    ],
                ],
                'per_poli' => [],
                'rows' => [],
                'pagination' => ['page' => 1, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 1],
            ];
        }

        $noRawats = array_column($visits, 'no_rawat');
        $ph = implode(',', array_fill(0, count($noRawats), '?'));

        // Batch query untuk seluruh indikator klinis (hindari N+1!)
        $soapMap = []; // no_rawat => earliest datetime
        foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $sTbl) {
            try {
                $q = $pdo->prepare("SELECT no_rawat, MIN(CONCAT(tgl_perawatan, ' ', jam_rawat)) AS first_dt FROM `{$sTbl}` WHERE no_rawat IN ({$ph}) GROUP BY no_rawat");
                $q->execute($noRawats);
                foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $sr) {
                    $nr = $sr['no_rawat'];
                    if (!isset($soapMap[$nr]) || $sr['first_dt'] < $soapMap[$nr]) {
                        $soapMap[$nr] = $sr['first_dt'];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $diagSet = [];
        try {
            $q = $pdo->prepare("SELECT DISTINCT no_rawat FROM diagnosa_pasien WHERE no_rawat IN ({$ph})");
            $q->execute($noRawats);
            foreach ($q->fetchAll(\PDO::FETCH_COLUMN) as $nr) {
                $diagSet[$nr] = true;
            }
        } catch (\Throwable $e) {
        }

        $tteSet = [];
        try {
            $q = $pdo->prepare("SELECT DISTINCT no_rawat FROM mlite_esignatures WHERE no_rawat IN ({$ph})");
            $q->execute($noRawats);
            foreach ($q->fetchAll(\PDO::FETCH_COLUMN) as $nr) {
                $tteSet[$nr] = true;
            }
        } catch (\Throwable $e) {
        }

        $resumeSet = [];
        $rmeFormsByVisit = []; // no_rawat => list of evaluated filled RME form details
        $rmeFirstDtMap = [];

        // Cek tabel resume_pasien secara terpisah
        try {
            $qRes = $pdo->prepare("SELECT DISTINCT no_rawat FROM resume_pasien WHERE no_rawat IN ({$ph})");
            $qRes->execute($noRawats);
            foreach ($qRes->fetchAll(\PDO::FETCH_COLUMN) as $nr) {
                $resumeSet[$nr] = true;
            }
        } catch (\Throwable $e) {
        }

        // Pindai seluruh tabel Form RME (82+ form Rajal, IGD, Ranap, Bedah) — HANYA menilai form yang benar-benar diisi
        $existingTables = [];
        try {
            $existingTables = array_flip($pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
        }

        if (!empty($rmeFormMap)) {
            $scannedTables = [];
            foreach ($rmeFormMap as $fi) {
                $rtbl = trim((string)($fi['table'] ?? ''));
                if ($rtbl === '' || $rtbl === 'pemeriksaan_ranap' || isset($scannedTables[$rtbl]) || !isset($existingTables[$rtbl])) {
                    continue;
                }
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $rtbl)) {
                    continue;
                }
                $scannedTables[$rtbl] = true;
                try {
                    $q = $pdo->prepare("SELECT * FROM `{$rtbl}` WHERE no_rawat IN ({$ph})");
                    $q->execute($noRawats);
                    $rowsFound = $q->fetchAll(\PDO::FETCH_ASSOC);
                    if (empty($rowsFound)) {
                        continue;
                    }
                    foreach ($rowsFound as $rr) {
                        // Abaikan formulir yang sudah dibatalkan (VOID)
                        if (!empty($rr['deleted_at'])) {
                            continue;
                        }
                        $nr = (string)($rr['no_rawat'] ?? '');
                        if ($nr === '') {
                            continue;
                        }
                        $evalForm = self::evaluateRmeRowCompleteness($rtbl, $rr, $fi);
                        $rmeFormsByVisit[$nr][] = $evalForm;
                        if ($evalForm['has_tte']) {
                            $tteSet[$nr] = true;
                        }
                        if (in_array($rtbl, ['rm_ringkasan_riwayat_klinik', 'rm_lampiran_profil_ringkas_medis_rajal'], true)) {
                            $resumeSet[$nr] = true;
                        }
                        $dtVal = $rr['entry_created_at'] ?? ($rr['created_at'] ?? ($rr['tanggal'] ?? null));
                        if (!empty($dtVal) && $dtVal !== '0000-00-00' && $dtVal !== '0000-00-00 00:00:00') {
                            if (!isset($rmeFirstDtMap[$nr]) || $dtVal < $rmeFirstDtMap[$nr]) {
                                $rmeFirstDtMap[$nr] = $dtVal;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        // Evaluasi tiap kunjungan
        $statusFilter = trim((string)($filters['status_klpcm'] ?? 'all'));
        $evaluated = [];
        $sumIndicators = [
            'identitas' => 0,
            'asesmen_soap' => 0,
            'diagnosa_icd' => 0,
            'autentikasi_tte' => 0,
            'resume_rme' => 0,
            'tepat_waktu' => 0,
        ];
        $poliStats = [];
        $totalLengkap = 0;
        $totalScoreSum = 0;
        $totalReviewed = 0;

        foreach ($visits as $v) {
            $nr = $v['no_rawat'];
            $rmClean = trim((string)($v['no_rkm_medis'] ?? ''));
            $nikClean = preg_replace('/\D/', '', (string)($v['no_ktp'] ?? ''));
            $tglLahir = trim((string)($v['tgl_lahir'] ?? ''));
            $alamat = trim((string)($v['alamat'] ?? ''));

            $okIdentitas = (strlen($rmClean) === 6 && ctype_digit($rmClean)
                && strlen($nikClean) === 16
                && $tglLahir !== '' && $tglLahir !== '0000-00-00'
                && $alamat !== '' && $alamat !== '-');

            $visitRmeForms = $rmeFormsByVisit[$nr] ?? [];
            $visitRmeCount = count($visitRmeForms);
            $visitRmeAvgPct = null;
            if ($visitRmeCount > 0) {
                $sumRmePct = 0;
                foreach ($visitRmeForms as $rf) {
                    $sumRmePct += $rf['pct'];
                }
                $visitRmeAvgPct = (int)round($sumRmePct / $visitRmeCount);
            }

            $okSoap = isset($soapMap[$nr]) || ($visitRmeCount > 0);
            $okDiag = isset($diagSet[$nr]);
            $okTte = isset($tteSet[$nr]);
            // Kelengkapan Form RME: hanya menilai form yang benar-benar diisi pada kunjungan ini (target >= 80% isian field)
            $okResumeRme = ($visitRmeCount > 0) ? ($visitRmeAvgPct >= 80) : isset($resumeSet[$nr]);

            $regTs = strtotime($v['tgl_registrasi'] . ' ' . ($v['jam_reg'] ?: '00:00:00'));
            $firstEntry = $soapMap[$nr] ?? ($rmeFirstDtMap[$nr] ?? null);
            $okTepatWaktu = false;
            if ($firstEntry && $regTs) {
                $entryTs = strtotime($firstEntry);
                if ($entryTs !== false && ($entryTs - $regTs) <= 86400 && ($entryTs - $regTs) >= -3600) {
                    $okTepatWaktu = true;
                }
            }

            $score = ($okIdentitas ? 1 : 0) + ($okSoap ? 1 : 0) + ($okDiag ? 1 : 0)
                   + ($okTte ? 1 : 0) + ($okResumeRme ? 1 : 0) + ($okTepatWaktu ? 1 : 0);
            $pct = (int)round(($score / 6) * 100);

            $reviewStatus = (string)($v['status_review'] ?? '');
            $finalLengkap = ($reviewStatus === 'Lengkap') || ($reviewStatus === '' && $score === 6);

            $rowItem = [
                'no_rawat'       => $nr,
                'no_rawat_slug'  => str_replace('/', '_', $nr),
                'tgl_registrasi' => $v['tgl_registrasi'],
                'jam_reg'        => $v['jam_reg'],
                'no_rkm_medis'   => $rmClean,
                'nm_pasien'      => (string)($v['nm_pasien'] ?? '-'),
                'kd_poli'        => (string)($v['kd_poli'] ?? ''),
                'nm_poli'        => (string)($v['nm_poli'] ?? '-'),
                'nm_dokter'      => (string)($v['nm_dokter'] ?? '-'),
                'status_lanjut'  => (string)($v['status_lanjut'] ?? 'Ralan'),
                'checks' => [
                    'identitas'       => $okIdentitas,
                    'asesmen_soap'    => $okSoap,
                    'diagnosa_icd'    => $okDiag,
                    'autentikasi_tte' => $okTte,
                    'resume_rme'      => $okResumeRme,
                    'tepat_waktu'     => $okTepatWaktu,
                ],
                'rme_count'          => $visitRmeCount,
                'rme_avg_pct'        => $visitRmeAvgPct,
                'rme_forms'          => $visitRmeForms,
                'score'              => $score,
                'pct'                => $pct,
                'is_lengkap'         => $finalLengkap,
                'status_review'      => $reviewStatus,
                'konsistensi_klinis' => (int)($v['konsistensi_klinis'] ?? 0),
                'kejelasan_catatan'  => (int)($v['kejelasan_catatan'] ?? 0),
                'informed_consent_ok'=> (int)($v['informed_consent_ok'] ?? 0),
                'review_catatan'     => (string)($v['review_catatan'] ?? ''),
                'target_ppas'        => (string)($v['target_ppas'] ?? ''),
                'returned_at'        => (string)($v['returned_at'] ?? ''),
                'due_at'             => (string)($v['due_at'] ?? ''),
                'is_overdue'         => (in_array($reviewStatus, ['Perlu Revisi', 'Belum Lengkap'], true) && !empty($v['due_at']) && strtotime((string)$v['due_at']) < time()),
                'resubmitted_at'     => (string)($v['resubmitted_at'] ?? ''),
                'resubmitted_by'     => (string)($v['resubmitted_by'] ?? ''),
                'resubmit_note'      => (string)($v['resubmit_note'] ?? ''),
                'reviewer_name'      => (string)($v['reviewer_name'] ?? ''),
                'reviewed_at'        => (string)($v['reviewed_at'] ?? ''),
            ];

            // Filter berdasarkan status KLPCM
            if ($statusFilter === 'lengkap' && !$finalLengkap) {
                continue;
            }
            if ($statusFilter === 'belum' && $finalLengkap) {
                continue;
            }
            if ($statusFilter === 'reviewed' && $reviewStatus === '') {
                continue;
            }
            if ($statusFilter === 'unreviewed' && $reviewStatus !== '') {
                continue;
            }
            if ($statusFilter === 'menunggu_verifikasi' && $reviewStatus !== 'Menunggu Verifikasi RM') {
                continue;
            }
            if ($statusFilter === 'overdue' && !$rowItem['is_overdue']) {
                continue;
            }

            $evaluated[] = $rowItem;
            foreach ($rowItem['checks'] as $ck => $cv) {
                if ($cv) {
                    $sumIndicators[$ck]++;
                }
            }
            if ($finalLengkap) {
                $totalLengkap++;
            }
            if ($reviewStatus !== '') {
                $totalReviewed++;
            }
            $totalScoreSum += $pct;

            $pk = $rowItem['nm_poli'];
            if (!isset($poliStats[$pk])) {
                $poliStats[$pk] = ['nm_poli' => $pk, 'total' => 0, 'lengkap' => 0, 'sum_pct' => 0];
            }
            $poliStats[$pk]['total']++;
            if ($finalLengkap) {
                $poliStats[$pk]['lengkap']++;
            }
            $poliStats[$pk]['sum_pct'] += $pct;
        }

        $total = count($evaluated);
        $indikatorPct = [];
        foreach ($sumIndicators as $k => $cnt) {
            $indikatorPct[$k] = $total > 0 ? round(($cnt / $total) * 100, 1) : 0;
        }

        $perPoli = [];
        foreach ($poliStats as $ps) {
            $perPoli[] = [
                'nm_poli'   => $ps['nm_poli'],
                'total'     => $ps['total'],
                'lengkap'   => $ps['lengkap'],
                'persen'    => $ps['total'] > 0 ? round(($ps['lengkap'] / $ps['total']) * 100, 1) : 0,
                'rata_skor' => $ps['total'] > 0 ? round($ps['sum_pct'] / $ps['total'], 1) : 0,
            ];
        }
        usort($perPoli, fn($a, $b) => $b['total'] <=> $a['total']);

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $pagedRows = array_slice($evaluated, ($page - 1) * $perPage, $perPage);

        return [
            'summary' => [
                'total_kunjungan' => $total,
                'total_lengkap'   => $totalLengkap,
                'persen_lengkap'  => $total > 0 ? round(($totalLengkap / $total) * 100, 1) : 0,
                'rata_skor'       => $total > 0 ? round($totalScoreSum / $total, 1) : 0,
                'sudah_direview'  => $totalReviewed,
                'indikator'       => $indikatorPct,
            ],
            'per_poli' => array_slice($perPoli, 0, 12),
            'rows'     => $pagedRows,
            'all_rows_for_export' => !empty($filters['_for_export']) ? $evaluated : [],
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * Daftar kandidat PPA terkait suatu kunjungan (DPJP, pengisi SOAP, & pembuat form RME)
     * untuk tujuan pengiriman notifikasi pengembalian berkas KLPCM.
     */
    public static function getVisitPpaCandidates(\PDO $pdo, string $noRawat, array $rmeFormMap = []): array
    {
        $candidates = []; // username => ['username', 'name', 'role_label', 'checked']

        // 1. DPJP Utama dari reg_periksa
        $st = $pdo->prepare("
            SELECT r.kd_dokter, d.nm_dokter, u.fullname AS u_fullname, u.role AS u_role
            FROM reg_periksa r
            LEFT JOIN dokter d ON d.kd_dokter = r.kd_dokter
            LEFT JOIN mlite_users u ON u.username = r.kd_dokter
            WHERE r.no_rawat = ?
            LIMIT 1
        ");
        $st->execute([$noRawat]);
        $reg = $st->fetch(\PDO::FETCH_ASSOC);
        if ($reg && !empty($reg['kd_dokter'])) {
            $uKey = trim((string)$reg['kd_dokter']);
            $candidates[$uKey] = [
                'username'   => $uKey,
                'name'       => (string)($reg['nm_dokter'] ?: ($reg['u_fullname'] ?: $uKey)),
                'role_label' => 'DPJP Utama',
                'checked'    => true,
            ];
        }

        // 2. Petugas / PPA pengisi SOAP kunjungan
        foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $sTbl) {
            try {
                $q = $pdo->prepare("
                    SELECT DISTINCT p.nip, pg.nama AS pg_nama, u.fullname AS u_fullname, u.role AS u_role
                    FROM `{$sTbl}` p
                    LEFT JOIN pegawai pg ON pg.nik = p.nip
                    LEFT JOIN mlite_users u ON u.username = p.nip
                    WHERE p.no_rawat = ? AND p.nip IS NOT NULL AND p.nip != ''
                ");
                $q->execute([$noRawat]);
                foreach ($q->fetchAll(\PDO::FETCH_ASSOC) as $sr) {
                    $uKey = trim((string)$sr['nip']);
                    if ($uKey !== '' && !isset($candidates[$uKey])) {
                        $candidates[$uKey] = [
                            'username'   => $uKey,
                            'name'       => (string)($sr['pg_nama'] ?: ($sr['u_fullname'] ?: $uKey)),
                            'role_label' => 'Pengisi SOAP (' . ($sr['u_role'] ?: 'PPA') . ')',
                            'checked'    => false,
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        // 3. Pembuat form RME (created_by) pada kunjungan ini
        if (!empty($rmeFormMap)) {
            $existingTables = array_flip($pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN));
            $seenTbl = [];
            foreach ($rmeFormMap as $fi) {
                $tbl = trim((string)($fi['table'] ?? ''));
                if ($tbl === '' || $tbl === 'pemeriksaan_ranap' || isset($seenTbl[$tbl]) || !isset($existingTables[$tbl])) {
                    continue;
                }
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) continue;
                $seenTbl[$tbl] = true;
                try {
                    $cols = $pdo->query("SHOW COLUMNS FROM `{$tbl}`")->fetchAll(\PDO::FETCH_COLUMN);
                    if (!in_array('created_by', $cols, true) || !in_array('no_rawat', $cols, true)) continue;
                    $q = $pdo->prepare("SELECT DISTINCT created_by FROM `{$tbl}` WHERE no_rawat = ? AND created_by IS NOT NULL AND created_by != ''");
                    $q->execute([$noRawat]);
                    foreach ($q->fetchAll(\PDO::FETCH_COLUMN) as $cb) {
                        $uKey = trim((string)$cb);
                        if ($uKey !== '' && $uKey !== 'admin' && !isset($candidates[$uKey])) {
                            $stU = $pdo->prepare("SELECT fullname, role FROM mlite_users WHERE username = ? LIMIT 1");
                            $stU->execute([$uKey]);
                            $uRow = $stU->fetch(\PDO::FETCH_ASSOC);
                            $candidates[$uKey] = [
                                'username'   => $uKey,
                                'name'       => (string)(($uRow['fullname'] ?? '') ?: $uKey),
                                'role_label' => 'Pengisi Form ' . ($fi['kode'] ?? 'RME'),
                                'checked'    => true,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        return array_values($candidates);
    }

    public static function saveKlpcmReview($core, array $input): array
    {
        if (!self::canManageRmeAudit($core)) {
            return ['status' => 'error', 'message' => 'Hanya Admin / Petugas Rekam Medis yang dapat menyimpan review KLPCM.'];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $noRawat = trim((string)($input['no_rawat'] ?? ''));
        $noRm = trim((string)($input['no_rkm_medis'] ?? ''));
        $statusReview = trim((string)($input['status_review'] ?? 'Belum Lengkap'));
        if ($noRawat === '') {
            return ['status' => 'error', 'message' => 'Nomor rawat tidak valid.'];
        }
        if (!in_array($statusReview, ['Lengkap', 'Belum Lengkap', 'Perlu Revisi', 'Menunggu Verifikasi RM'], true)) {
            $statusReview = 'Belum Lengkap';
        }

        $konsistensi = !empty($input['konsistensi_klinis']) ? 1 : 0;
        $kejelasan = !empty($input['kejelasan_catatan']) ? 1 : 0;
        $icOk = !empty($input['informed_consent_ok']) ? 1 : 0;
        $skor = max(0, min(100, (int)($input['skor_kuantitatif'] ?? 0)));
        $catatan = trim((string)($input['catatan'] ?? ''));

        $uid = (int)($_SESSION['mlite_user'] ?? 0);
        $uname = (string)$core->getUserInfo('username', $uid, true);
        $fname = (string)$core->getUserInfo('fullname', $uid, true) ?: $uname;

        $isReturnToPpa = in_array($statusReview, ['Perlu Revisi', 'Belum Lengkap'], true);
        $slaHours = (int)($input['sla_hours'] ?? 24);
        if (!in_array($slaHours, [12, 24, 48, 72], true)) {
            $slaHours = 24;
        }
        $returnedAt = $isReturnToPpa ? date('Y-m-d H:i:s') : null;
        $dueAt = $isReturnToPpa ? date('Y-m-d H:i:s', time() + ($slaHours * 3600)) : null;

        // Parse target PPA usernames
        $rawTargets = $input['target_usernames'] ?? [];
        if (is_string($rawTargets)) {
            $rawTargets = array_filter(array_map('trim', explode(',', $rawTargets)));
        } elseif (!is_array($rawTargets)) {
            $rawTargets = [];
        }
        $targetUsernames = array_values(array_unique(array_filter(array_map('trim', $rawTargets))));

        // Resolve nama pasien & DPJP fallback
        $stReg = $pdo->prepare("SELECT r.no_rkm_medis, r.kd_dokter, p.nm_pasien FROM reg_periksa r LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis WHERE r.no_rawat = ? LIMIT 1");
        $stReg->execute([$noRawat]);
        $regInfo = $stReg->fetch(\PDO::FETCH_ASSOC) ?: [];
        if ($noRm === '' && !empty($regInfo['no_rkm_medis'])) {
            $noRm = (string)$regInfo['no_rkm_medis'];
        }
        $nmPasien = (string)($regInfo['nm_pasien'] ?? '');
        if ($isReturnToPpa && empty($targetUsernames) && !empty($regInfo['kd_dokter'])) {
            $targetUsernames[] = trim((string)$regInfo['kd_dokter']);
        }
        $targetPpasStr = implode(',', $targetUsernames);

        $st = $pdo->prepare("
            INSERT INTO mlite_klpcm_reviews
                (no_rawat, no_rkm_medis, status_review, konsistensi_klinis, kejelasan_catatan, informed_consent_ok, skor_kuantitatif, catatan, target_ppas, returned_at, due_at, reviewer_username, reviewer_name, reviewed_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                no_rkm_medis = VALUES(no_rkm_medis),
                status_review = VALUES(status_review),
                konsistensi_klinis = VALUES(konsistensi_klinis),
                kejelasan_catatan = VALUES(kejelasan_catatan),
                informed_consent_ok = VALUES(informed_consent_ok),
                skor_kuantitatif = VALUES(skor_kuantitatif),
                catatan = VALUES(catatan),
                target_ppas = VALUES(target_ppas),
                returned_at = IF(VALUES(returned_at) IS NOT NULL, VALUES(returned_at), returned_at),
                due_at = IF(VALUES(due_at) IS NOT NULL, VALUES(due_at), due_at),
                reviewer_username = VALUES(reviewer_username),
                reviewer_name = VALUES(reviewer_name),
                updated_at = NOW()
        ");
        $st->execute([$noRawat, $noRm, $statusReview, $konsistensi, $kejelasan, $icOk, $skor, $catatan, $targetPpasStr, $returnedAt, $dueAt, $uname, $fname]);

        $notifSentCount = 0;
        if ($statusReview === 'Lengkap') {
            // Selesaikan seluruh notifikasi terbuka untuk kunjungan ini
            $pdo->prepare("UPDATE mlite_klpcm_notifications SET is_resolved = 1, is_read = 1, resolved_at = NOW() WHERE no_rawat = ? AND is_resolved = 0")
                ->execute([$noRawat]);
        } elseif ($isReturnToPpa) {
            // Tutup notifikasi terbuka sebelumnya agar tidak menumpuk ganda, lalu buat notifikasi baru ke PPA target
            $pdo->prepare("UPDATE mlite_klpcm_notifications SET is_resolved = 1, resolved_at = NOW() WHERE no_rawat = ? AND is_resolved = 0")
                ->execute([$noRawat]);

            $msg = $catatan !== '' ? $catatan : 'Mohon melengkapi isian rekam medis & formulir RME sesuai hasil review KLPCM.';
            $insN = $pdo->prepare("
                INSERT INTO mlite_klpcm_notifications
                    (no_rawat, no_rkm_medis, nm_pasien, target_username, target_name, notif_type, status_review, message, due_at, is_read, is_resolved, created_by, created_by_name, created_at)
                VALUES (?, ?, ?, ?, ?, 'RETURNED_TO_PPA', ?, ?, ?, 0, 0, ?, ?, NOW())
            ");
            foreach ($targetUsernames as $tu) {
                $stU = $pdo->prepare("SELECT fullname FROM mlite_users WHERE username = ? LIMIT 1");
                $stU->execute([$tu]);
                $tName = (string)($stU->fetchColumn() ?: $tu);
                $insN->execute([$noRawat, $noRm, $nmPasien, $tu, $tName, $statusReview, $msg, $dueAt, $uname, $fname]);
                $notifSentCount++;
            }
        }

        $msgOut = 'Hasil review KLPCM untuk ' . $noRawat . ' berhasil disimpan.';
        if ($isReturnToPpa && $notifSentCount > 0) {
            $msgOut .= ' Notifikasi pengembalian dikirim ke ' . $notifSentCount . ' PPA (batas ' . $slaHours . ' jam).';
        }
        return ['status' => 'success', 'message' => $msgOut, 'notif_sent' => $notifSentCount];
    }

    /**
     * Konfirmasi oleh PPA bahwa berkas KLPCM sudah dilengkapi -> kirim notifikasi balik ke Komite RM.
     */
    public static function resubmitKlpcmByPpa($core, string $noRawat, string $note = ''): array
    {
        if (empty($_SESSION['mlite_user'])) {
            return ['status' => 'error', 'message' => 'Sesi login diperlukan.'];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $noRawat = trim($noRawat);
        if ($noRawat === '') {
            return ['status' => 'error', 'message' => 'Nomor rawat tidak valid.'];
        }

        $uid = (int)$_SESSION['mlite_user'];
        $uname = (string)$core->getUserInfo('username', $uid, true);
        $fname = (string)$core->getUserInfo('fullname', $uid, true) ?: $uname;
        $noteClean = trim($note);

        $stRev = $pdo->prepare("SELECT * FROM mlite_klpcm_reviews WHERE no_rawat = ? LIMIT 1");
        $stRev->execute([$noRawat]);
        $rev = $stRev->fetch(\PDO::FETCH_ASSOC);
        if (!$rev) {
            return ['status' => 'error', 'message' => 'Data review KLPCM untuk kunjungan ini tidak ditemukan.'];
        }

        $pdo->prepare("
            UPDATE mlite_klpcm_reviews
            SET status_review = 'Menunggu Verifikasi RM',
                resubmitted_at = NOW(),
                resubmitted_by = ?,
                resubmit_note = ?,
                updated_at = NOW()
            WHERE no_rawat = ?
        ")->execute([$fname, $noteClean, $noRawat]);

        // Selesaikan notifikasi RETURNED_TO_PPA untuk no_rawat ini
        $pdo->prepare("UPDATE mlite_klpcm_notifications SET is_resolved = 1, is_read = 1, resolved_at = NOW() WHERE no_rawat = ? AND notif_type = 'RETURNED_TO_PPA' AND is_resolved = 0")
            ->execute([$noRawat]);

        // Ambil nama pasien
        $stP = $pdo->prepare("SELECT r.no_rkm_medis, p.nm_pasien FROM reg_periksa r LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis WHERE r.no_rawat = ? LIMIT 1");
        $stP->execute([$noRawat]);
        $pInfo = $stP->fetch(\PDO::FETCH_ASSOC) ?: [];
        $noRm = (string)($pInfo['no_rkm_medis'] ?? ($rev['no_rkm_medis'] ?? ''));
        $nmPasien = (string)($pInfo['nm_pasien'] ?? '');

        // Kirim notifikasi balik ke Komite RM / Admin
        $msgRm = $fname . ' telah melengkapi berkas e-RM' . ($noteClean !== '' ? ': ' . $noteClean : '. Mohon verifikasi ulang.');
        $pdo->prepare("
            INSERT INTO mlite_klpcm_notifications
                (no_rawat, no_rkm_medis, nm_pasien, target_username, target_name, notif_type, status_review, message, due_at, is_read, is_resolved, created_by, created_by_name, created_at)
            VALUES (?, ?, ?, '__KOMITE_RM__', 'Komite / Petugas RM', 'RESUBMITTED_TO_RM', 'Menunggu Verifikasi RM', ?, NULL, 0, 0, ?, ?, NOW())
        ")->execute([$noRawat, $noRm, $nmPasien, $msgRm, $uname, $fname]);

        return ['status' => 'success', 'message' => 'Status berkas ' . $noRawat . ' berhasil diperbarui menjadi Menunggu Verifikasi RM.'];
    }

    /**
     * Pusat Notifikasi Terpadu untuk Lonceng Navbar (tidak hanya KLPCM):
     *  1. KLPCM (pengembalian ke PPA & konfirmasi balik ke Komite RM)
     *  2. Klinis (Rujukan Internal Poli belum dijawab & Supervisi Koas menunggu verifikasi DPJP)
     *  3. Penunjang (Permintaan Radiologi / Laboratorium baru belum ada hasil — khusus petugas unit terkait)
     */
    public static function listNavbarNotifications($core): array
    {
        if (empty($_SESSION['mlite_user'])) {
            return ['count' => 0, 'counts' => ['all' => 0, 'klpcm' => 0, 'klinis' => 0, 'penunjang' => 0], 'items' => []];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $uid = (int)$_SESSION['mlite_user'];
        $uname = trim((string)$core->getUserInfo('username', $uid, true));
        $role = strtolower(trim((string)$core->getUserInfo('role', $uid, true)));
        $accessStr = (string)$core->getUserInfo('access', $uid, true);
        $accessList = array_filter(array_map('trim', explode(',', $accessStr)));
        $userCap = (string)$core->getUserInfo('cap', $uid, true);
        $poliCodes = array_values(array_filter(array_map('trim', explode(',', $userCap))));
        $isAdmin = ($accessStr === 'all') || ($role === 'admin') || ($uid === 1);
        $isRm = self::canManageRmeAudit($core);

        $items = [];
        $counts = ['all' => 0, 'klpcm' => 0, 'klinis' => 0, 'penunjang' => 0];
        $nowTs = time();

        // 1. NOTIFIKASI KLPCM (mlite_klpcm_notifications)
        $targets = [];
        if ($uname !== '') {
            $targets[] = $uname;
        }
        if ($isRm) {
            $targets[] = '__KOMITE_RM__';
        }
        if (!empty($targets)) {
            $ph = implode(',', array_fill(0, count($targets), '?'));
            $st = $pdo->prepare("
                SELECT * FROM mlite_klpcm_notifications
                WHERE target_username IN ({$ph}) AND is_resolved = 0
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $st->execute($targets);
            foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $isRmNotif = ($r['notif_type'] === 'RESUBMITTED_TO_RM');
                $isOverdue = false;
                $slaText = '';
                if (!empty($r['due_at'])) {
                    $dueTs = strtotime((string)$r['due_at']);
                    if ($dueTs !== false) {
                        $diffH = (int)round(($dueTs - $nowTs) / 3600);
                        if ($diffH < 0) {
                            $isOverdue = true;
                            $slaText = 'Lewat batas ' . abs($diffH) . ' jam';
                        } elseif ($diffH === 0) {
                            $slaText = 'Batas < 1 jam lagi';
                        } else {
                            $slaText = 'Sisa ' . $diffH . ' jam';
                        }
                    }
                }
                $items[] = [
                    'id'                => 'klpcm_' . $r['id'],
                    'category'          => 'klpcm',
                    'category_label'    => 'KLPCM',
                    'notif_type'        => $r['notif_type'],
                    'severity'          => $isRmNotif ? 'info' : ($isOverdue ? 'danger' : 'warning'),
                    'title'             => (string)($r['nm_pasien'] ?: $r['no_rkm_medis']),
                    'no_rkm_medis'      => (string)$r['no_rkm_medis'],
                    'nm_pasien'         => (string)$r['nm_pasien'],
                    'no_rawat'          => (string)$r['no_rawat'],
                    'badge'             => $isRmNotif ? 'Menunggu Verifikasi RM' : ($r['status_review'] . ($slaText !== '' ? ' • ' . $slaText : '')),
                    'message'           => (string)($r['message'] ?: '-'),
                    'created_at'        => (string)$r['created_at'],
                    'action_type'       => $isRmNotif ? 'open_klpcm' : 'klpcm_ppa',
                    'action_url'        => $isRmNotif ? url([ADMIN, 'pasien', 'klpcm']) : '',
                    'action_label'      => $isRmNotif ? 'Verifikasi di KLPCM' : 'Buka e-RM',
                    'is_overdue'        => $isOverdue,
                    'sla_text'          => $slaText,
                    'status_review'     => (string)$r['status_review'],
                ];
                $counts['klpcm']++;
            }
        }

        // 2. NOTIFIKASI KLINIS — Rujukan Internal Poli yang belum dijawab (7 hari terakhir)
        try {
            $rujWhere = ["(ri.jawab_rujukan IS NULL OR TRIM(ri.jawab_rujukan) = '')", "r.tgl_registrasi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"];
            $rujBind = [];
            if (!$isAdmin) {
                $conds = [];
                if ($uname !== '') {
                    $conds[] = 'ri.kd_dokter = ?';
                    $rujBind[] = $uname;
                }
                if (!empty($poliCodes)) {
                    $pPh = implode(',', array_fill(0, count($poliCodes), '?'));
                    $conds[] = "ri.kd_poli IN ({$pPh})";
                    $rujBind = array_merge($rujBind, $poliCodes);
                }
                if (!empty($conds)) {
                    $rujWhere[] = '(' . implode(' OR ', $conds) . ')';
                } else {
                    $rujWhere[] = '1=0';
                }
            }
            $stRuj = $pdo->prepare("
                SELECT ri.no_rawat, ri.kd_poli, ri.isi_rujukan, r.tgl_registrasi, r.jam_reg, r.no_rkm_medis, p.nm_pasien, pol.nm_poli
                FROM mlite_rujukan_internal_poli ri
                INNER JOIN reg_periksa r ON r.no_rawat = ri.no_rawat
                LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
                LEFT JOIN poliklinik pol ON pol.kd_poli = ri.kd_poli
                WHERE " . implode(' AND ', $rujWhere) . "
                ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
                LIMIT 10
            ");
            $stRuj->execute($rujBind);
            foreach ($stRuj->fetchAll(\PDO::FETCH_ASSOC) as $rj) {
                $items[] = [
                    'id'             => 'ruj_' . md5($rj['no_rawat'] . '_' . $rj['kd_poli']),
                    'category'       => 'klinis',
                    'category_label' => 'Rujukan Internal',
                    'notif_type'     => 'RUJUKAN_INTERNAL',
                    'severity'       => 'info',
                    'title'          => (string)($rj['nm_pasien'] ?: $rj['no_rkm_medis']),
                    'no_rkm_medis'   => (string)$rj['no_rkm_medis'],
                    'nm_pasien'      => (string)($rj['nm_pasien'] ?? ''),
                    'no_rawat'       => (string)$rj['no_rawat'],
                    'badge'          => 'Konsul ke ' . ($rj['nm_poli'] ?: $rj['kd_poli']),
                    'message'        => mb_strimwidth(trim((string)($rj['isi_rujukan'] ?: 'Menunggu jawaban konsultasi rujukan internal poli.')), 0, 120, '...'),
                    'created_at'     => trim($rj['tgl_registrasi'] . ' ' . $rj['jam_reg']),
                    'action_type'    => 'open_link',
                    'action_url'     => url([ADMIN, 'rawat_jalan', 'manage']),
                    'action_label'   => 'Buka Rawat Jalan',
                    'is_overdue'     => false,
                ];
                $counts['klinis']++;
            }
        } catch (\Throwable $e) {
        }

        // 3. NOTIFIKASI KLINIS — Supervisi Koas menunggu verifikasi DPJP
        try {
            $supWhere = ["s.status IN ('draft','menunggu','pending')"];
            $supBind = [];
            if (!$isAdmin) {
                if ($uname !== '') {
                    $supWhere[] = 's.dpjp_kode = ?';
                    $supBind[] = $uname;
                } else {
                    $supWhere[] = '1=0';
                }
            }
            $stSup = $pdo->prepare("
                SELECT s.id, s.no_rawat, s.kode_form, s.coass_nama, s.waktu_draft, r.no_rkm_medis, p.nm_pasien
                FROM mlite_rme_supervisi_coass s
                LEFT JOIN reg_periksa r ON r.no_rawat = s.no_rawat
                LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
                WHERE " . implode(' AND ', $supWhere) . "
                ORDER BY s.waktu_draft DESC
                LIMIT 10
            ");
            $stSup->execute($supBind);
            foreach ($stSup->fetchAll(\PDO::FETCH_ASSOC) as $sp) {
                $items[] = [
                    'id'             => 'coass_' . $sp['id'],
                    'category'       => 'klinis',
                    'category_label' => 'Supervisi Koas',
                    'notif_type'     => 'SUPERVISI_COASS',
                    'severity'       => 'warning',
                    'title'          => (string)($sp['nm_pasien'] ?: $sp['no_rawat']),
                    'no_rkm_medis'   => (string)($sp['no_rkm_medis'] ?? ''),
                    'nm_pasien'      => (string)($sp['nm_pasien'] ?? ''),
                    'no_rawat'       => (string)$sp['no_rawat'],
                    'badge'          => 'Form ' . $sp['kode_form'] . ' • Menunggu Verifikasi DPJP',
                    'message'        => 'Draft diisi oleh Koas ' . ($sp['coass_nama'] ?: '-') . ', menunggu verifikasi & TTE DPJP.',
                    'created_at'     => (string)($sp['waktu_draft'] ?? ''),
                    'action_type'    => 'open_link',
                    'action_url'     => url([ADMIN, 'pasien', 'riwayatperawatan', (string)($sp['no_rkm_medis'] ?? '')]),
                    'action_label'   => 'Tinjau e-RM',
                    'is_overdue'     => false,
                ];
                $counts['klinis']++;
            }
        } catch (\Throwable $e) {
        }

        // 4. NOTIFIKASI PENUNJANG — Permintaan Radiologi & Lab baru belum diisi hasil (khusus petugas unit terkait / admin)
        $hasRadAccess = ($role === 'radiologi') || in_array('radiologi', $accessList, true);
        if ($hasRadAccess) {
            try {
                $stRad = $pdo->query("
                    SELECT pr.noorder, pr.no_rawat, pr.tgl_permintaan, pr.jam_permintaan, pr.diagnosa_klinis, r.no_rkm_medis, p.nm_pasien
                    FROM permintaan_radiologi pr
                    LEFT JOIN reg_periksa r ON r.no_rawat = pr.no_rawat
                    LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
                    WHERE pr.tgl_permintaan >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                      AND (pr.tgl_hasil IS NULL OR pr.tgl_hasil = '0000-00-00')
                    ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC
                    LIMIT 8
                ");
                foreach ($stRad->fetchAll(\PDO::FETCH_ASSOC) as $rd) {
                    $items[] = [
                        'id'             => 'rad_' . $rd['noorder'],
                        'category'       => 'penunjang',
                        'category_label' => 'Radiologi',
                        'notif_type'     => 'ORDER_RADIOLOGI',
                        'severity'       => 'info',
                        'title'          => (string)($rd['nm_pasien'] ?: $rd['no_rkm_medis']),
                        'no_rkm_medis'   => (string)($rd['no_rkm_medis'] ?? ''),
                        'nm_pasien'      => (string)($rd['nm_pasien'] ?? ''),
                        'no_rawat'       => (string)$rd['no_rawat'],
                        'badge'          => 'Order Rad #' . $rd['noorder'] . ' • Belum Ada Hasil',
                        'message'        => 'Diagnosa klinis: ' . (trim((string)($rd['diagnosa_klinis'] ?? '')) ?: '-'),
                        'created_at'     => trim($rd['tgl_permintaan'] . ' ' . $rd['jam_permintaan']),
                        'action_type'    => 'open_link',
                        'action_url'     => url([ADMIN, 'radiologi', 'manage']),
                        'action_label'   => 'Buka Radiologi',
                        'is_overdue'     => false,
                    ];
                    $counts['penunjang']++;
                }
            } catch (\Throwable $e) {
            }
        }

        $hasLabAccess = ($role === 'laboratorium') || in_array('laboratorium', $accessList, true);
        if ($hasLabAccess) {
            try {
                $stLab = $pdo->query("
                    SELECT pl.noorder, pl.no_rawat, pl.tgl_permintaan, pl.jam_permintaan, pl.diagnosa_klinis, r.no_rkm_medis, p.nm_pasien
                    FROM permintaan_lab pl
                    LEFT JOIN reg_periksa r ON r.no_rawat = pl.no_rawat
                    LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
                    WHERE pl.tgl_permintaan >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                      AND (pl.tgl_hasil IS NULL OR pl.tgl_hasil = '0000-00-00')
                    ORDER BY pl.tgl_permintaan DESC, pl.jam_permintaan DESC
                    LIMIT 8
                ");
                foreach ($stLab->fetchAll(\PDO::FETCH_ASSOC) as $lb) {
                    $items[] = [
                        'id'             => 'lab_' . $lb['noorder'],
                        'category'       => 'penunjang',
                        'category_label' => 'Laboratorium',
                        'notif_type'     => 'ORDER_LAB',
                        'severity'       => 'info',
                        'title'          => (string)($lb['nm_pasien'] ?: $lb['no_rkm_medis']),
                        'no_rkm_medis'   => (string)($lb['no_rkm_medis'] ?? ''),
                        'nm_pasien'      => (string)($lb['nm_pasien'] ?? ''),
                        'no_rawat'       => (string)$lb['no_rawat'],
                        'badge'          => 'Order Lab #' . $lb['noorder'] . ' • Belum Ada Hasil',
                        'message'        => 'Diagnosa klinis: ' . (trim((string)($lb['diagnosa_klinis'] ?? '')) ?: '-'),
                        'created_at'     => trim($lb['tgl_permintaan'] . ' ' . $lb['jam_permintaan']),
                        'action_type'    => 'open_link',
                        'action_url'     => url([ADMIN, 'laboratorium', 'manage']),
                        'action_label'   => 'Buka Lab',
                        'is_overdue'     => false,
                    ];
                    $counts['penunjang']++;
                }
            } catch (\Throwable $e) {
            }
        }

        usort($items, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        $counts['all'] = count($items);

        return [
            'count'  => $counts['all'],
            'counts' => $counts,
            'is_rm'  => $isRm,
            'items'  => $items,
        ];
    }

    /**
     * Daftar riwayat Form RME yang diisi PPA (beserta skor kelengkapan tiap form & status KLPCM).
     */
    public static function listPpaRmeHistory($core, array $filters = [], int $page = 1, int $perPage = 20, array $rmeFormMap = []): array
    {
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $uid = (int)($_SESSION['mlite_user'] ?? 0);
        $uname = trim((string)$core->getUserInfo('username', $uid, true));
        $isRm = self::canManageRmeAudit($core);

        $scope = trim((string)($filters['scope'] ?? ($isRm ? 'all' : 'mine')));
        if (!$isRm) {
            $scope = 'mine'; // PPA non-admin hanya melihat riwayat miliknya sendiri
        }

        $tglAwal = trim((string)($filters['tgl_awal'] ?? date('Y-m-d', strtotime('-30 days'))));
        $tglAkhir = trim((string)($filters['tgl_akhir'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) $tglAwal = date('Y-m-d', strtotime('-30 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) $tglAkhir = date('Y-m-d');

        // Ambil kunjungan pada rentang tanggal (+ kunjungan yang melibatkan PPA bila scope='mine')
        $visitWhere = ["r.tgl_registrasi BETWEEN ? AND ?", "r.stts != 'Batal'"];
        $visitBind = [$tglAwal, $tglAkhir];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $visitWhere[] = '(r.no_rawat LIKE ? OR r.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ?)';
            array_push($visitBind, $like, $like, $like);
        }

        $stV = $pdo->prepare("
            SELECT r.no_rawat, r.tgl_registrasi, r.jam_reg, r.no_rkm_medis, r.kd_dokter, r.kd_poli, r.status_lanjut,
                   p.nm_pasien, pol.nm_poli, d.nm_dokter,
                   rev.status_review, rev.catatan AS klpcm_catatan, rev.due_at AS klpcm_due_at
            FROM reg_periksa r
            LEFT JOIN pasien p ON p.no_rkm_medis = r.no_rkm_medis
            LEFT JOIN poliklinik pol ON pol.kd_poli = r.kd_poli
            LEFT JOIN dokter d ON d.kd_dokter = r.kd_dokter
            LEFT JOIN mlite_klpcm_reviews rev ON rev.no_rawat = r.no_rawat
            WHERE " . implode(' AND ', $visitWhere) . "
            ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC
            LIMIT 500
        ");
        $stV->execute($visitBind);
        $visits = $stV->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($visits) || empty($rmeFormMap)) {
            return [
                'summary' => ['total_form' => 0, 'rata_kelengkapan' => 0, 'form_lengkap' => 0, 'form_belum' => 0, 'klpcm_revisi' => 0],
                'form_codes' => [],
                'rows' => [],
                'pagination' => ['page' => 1, 'per_page' => $perPage, 'total' => 0, 'total_pages' => 1],
            ];
        }

        $visitMap = [];
        foreach ($visits as $v) {
            $visitMap[$v['no_rawat']] = $v;
        }
        $noRawats = array_keys($visitMap);
        $ph = implode(',', array_fill(0, count($noRawats), '?'));

        // Jika scope='mine', kumpulkan juga kunjungan di mana user mengisi SOAP
        $mySoapVisits = [];
        if ($scope === 'mine' && $uname !== '') {
            foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $sTbl) {
                try {
                    $qs = $pdo->prepare("SELECT DISTINCT no_rawat FROM `{$sTbl}` WHERE no_rawat IN ({$ph}) AND nip = ?");
                    $qs->execute(array_merge($noRawats, [$uname]));
                    foreach ($qs->fetchAll(\PDO::FETCH_COLUMN) as $nr) {
                        $mySoapVisits[$nr] = true;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $kodeFilter = trim((string)($filters['kode_form'] ?? 'all'));
        $statusFilter = trim((string)($filters['status_kelengkapan'] ?? 'all'));

        $existingTables = array_flip($pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN));
        $scannedTables = [];
        $allItems = [];
        $formCodesSet = [];

        foreach ($rmeFormMap as $fi) {
            $rtbl = trim((string)($fi['table'] ?? ''));
            if ($rtbl === '' || $rtbl === 'pemeriksaan_ranap' || isset($scannedTables[$rtbl]) || !isset($existingTables[$rtbl])) {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $rtbl)) continue;
            $scannedTables[$rtbl] = true;
            try {
                $q = $pdo->prepare("SELECT * FROM `{$rtbl}` WHERE no_rawat IN ({$ph})");
                $q->execute($noRawats);
                $rowsFound = $q->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rowsFound as $rr) {
                    if (!empty($rr['deleted_at'])) continue;
                    $nr = (string)($rr['no_rawat'] ?? '');
                    if (!isset($visitMap[$nr])) continue;
                    $v = $visitMap[$nr];

                    $createdBy = trim((string)($rr['created_by'] ?? ''));
                    if ($scope === 'mine' && $uname !== '') {
                        $isMine = (
                            strcasecmp($createdBy, $uname) === 0 ||
                            strcasecmp((string)($v['kd_dokter'] ?? ''), $uname) === 0 ||
                            isset($mySoapVisits[$nr])
                        );
                        if (!$isMine) continue;
                    }

                    $eval = self::evaluateRmeRowCompleteness($rtbl, $rr, $fi);
                    $formCodesSet[$eval['kode']] = $eval['nama'];

                    $klpcmStatus = (string)($v['status_review'] ?? '');
                    $isKlpcmReturned = in_array($klpcmStatus, ['Perlu Revisi', 'Belum Lengkap'], true);

                    if ($kodeFilter !== '' && $kodeFilter !== 'all' && $eval['kode'] !== $kodeFilter) continue;
                    if ($statusFilter === 'lengkap' && $eval['pct'] < 80) continue;
                    if ($statusFilter === 'belum' && $eval['pct'] >= 80) continue;
                    if ($statusFilter === 'klpcm_revisi' && !$isKlpcmReturned) continue;

                    $entryDt = $rr['entry_created_at'] ?? ($rr['created_at'] ?? ($v['tgl_registrasi'] . ' ' . $v['jam_reg']));
                    $allItems[] = [
                        'id'             => $eval['id'],
                        'kode'           => $eval['kode'],
                        'nama'           => $eval['nama'],
                        'route_form'     => $eval['route_form'],
                        'route_cetak'    => $eval['route_cetak'],
                        'no_rawat'       => $nr,
                        'no_rkm_medis'   => (string)$v['no_rkm_medis'],
                        'nm_pasien'      => (string)($v['nm_pasien'] ?? '-'),
                        'nm_poli'        => (string)($v['nm_poli'] ?? '-'),
                        'nm_dokter'      => (string)($v['nm_dokter'] ?? '-'),
                        'tgl_registrasi' => (string)$v['tgl_registrasi'],
                        'entry_dt'       => (string)$entryDt,
                        'created_by'     => $createdBy ?: (string)($v['nm_dokter'] ?? '-'),
                        'filled'         => $eval['filled'],
                        'total'          => $eval['total'],
                        'pct'            => $eval['pct'],
                        'has_tte'        => $eval['has_tte'],
                        'missing_fields' => $eval['missing_fields'],
                        'missing_count'  => $eval['missing_count'],
                        'klpcm_status'   => $klpcmStatus,
                        'klpcm_catatan'  => (string)($v['klpcm_catatan'] ?? ''),
                        'klpcm_due_at'   => (string)($v['klpcm_due_at'] ?? ''),
                        'is_klpcm_returned' => $isKlpcmReturned,
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        usort($allItems, fn($a, $b) => strcmp($b['entry_dt'], $a['entry_dt']));

        $totalForm = count($allItems);
        $sumPct = 0; $cntLengkap = 0; $cntBelum = 0; $cntRevisi = 0;
        foreach ($allItems as $it) {
            $sumPct += $it['pct'];
            if ($it['pct'] >= 80) $cntLengkap++; else $cntBelum++;
            if ($it['is_klpcm_returned']) $cntRevisi++;
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $totalPages = max(1, (int)ceil($totalForm / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $paged = array_slice($allItems, ($page - 1) * $perPage, $perPage);

        ksort($formCodesSet);
        $codesList = [];
        foreach ($formCodesSet as $ck => $cn) {
            $codesList[] = ['kode' => $ck, 'nama' => $cn];
        }

        return [
            'summary' => [
                'total_form'       => $totalForm,
                'rata_kelengkapan' => $totalForm > 0 ? round($sumPct / $totalForm, 1) : 0,
                'form_lengkap'     => $cntLengkap,
                'form_belum'       => $cntBelum,
                'klpcm_revisi'     => $cntRevisi,
            ],
            'form_codes' => $codesList,
            'rows'       => $paged,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $totalForm,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /* =========================================================================
     * 3. KAMUS DAFTAR SINGKATAN & SIMBOL TERSTANDAR
     * ========================================================================= */

    public static function listSingkatan(\PDO $pdo, array $filters = []): array
    {
        self::ensureSchema($pdo);

        $where = ['status = 1'];
        $bind = [];

        $jenis = trim((string)($filters['jenis'] ?? 'all'));
        if (in_array($jenis, ['diperbolehkan', 'dilarang', 'simbol'], true)) {
            $where[] = 'jenis = ?';
            $bind[] = $jenis;
        }

        $kategori = trim((string)($filters['kategori'] ?? 'all'));
        if ($kategori !== '' && $kategori !== 'all') {
            $where[] = 'kategori = ?';
            $bind[] = $kategori;
        }

        $q = trim((string)($filters['search'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(kode LIKE ? OR kepanjangan LIKE ? OR alternatif_benar LIKE ? OR keterangan LIKE ?)';
            array_push($bind, $like, $like, $like, $like);
        }

        $st = $pdo->prepare("SELECT * FROM mlite_mrmik_singkatan WHERE " . implode(' AND ', $where) . " ORDER BY jenis ASC, kategori ASC, kode ASC");
        $st->execute($bind);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        $counts = [
            'diperbolehkan' => 0,
            'dilarang'      => 0,
            'simbol'        => 0,
            'total'         => 0,
        ];
        $cntRows = $pdo->query("SELECT jenis, COUNT(*) AS c FROM mlite_mrmik_singkatan WHERE status = 1 GROUP BY jenis")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($cntRows as $cr) {
            $counts[$cr['jenis']] = (int)$cr['c'];
            $counts['total'] += (int)$cr['c'];
        }

        $cats = $pdo->query("SELECT DISTINCT kategori FROM mlite_mrmik_singkatan WHERE status = 1 ORDER BY kategori ASC")->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'counts'     => $counts,
            'categories' => $cats,
            'rows'       => $rows,
        ];
    }

    public static function saveSingkatan($core, array $input): array
    {
        if (!self::canManageRmeAudit($core)) {
            return ['status' => 'error', 'message' => 'Hanya Admin / Petugas Rekam Medis yang dapat mengubah kamus singkatan.'];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $id = (int)($input['id'] ?? 0);
        $kode = trim((string)($input['kode'] ?? ''));
        $kepanjangan = trim((string)($input['kepanjangan'] ?? ''));
        $jenis = trim((string)($input['jenis'] ?? 'diperbolehkan'));
        $kategori = trim((string)($input['kategori'] ?? 'Umum'));
        $alternatif = trim((string)($input['alternatif_benar'] ?? ''));
        $keterangan = trim((string)($input['keterangan'] ?? ''));

        if ($kode === '' || $kepanjangan === '') {
            return ['status' => 'error', 'message' => 'Singkatan/Simbol dan Arti/Kepanjangan wajib diisi.'];
        }
        if (!in_array($jenis, ['diperbolehkan', 'dilarang', 'simbol'], true)) {
            $jenis = 'diperbolehkan';
        }
        if ($kategori === '') {
            $kategori = 'Umum';
        }
        if ($jenis === 'dilarang' && $alternatif === '') {
            return ['status' => 'error', 'message' => 'Untuk singkatan yang DILARANG, kolom Penulisan yang Benar wajib diisi.'];
        }

        $uid = (int)($_SESSION['mlite_user'] ?? 0);
        $uname = (string)$core->getUserInfo('username', $uid, true) ?: 'admin';

        if ($id > 0) {
            $st = $pdo->prepare("UPDATE mlite_mrmik_singkatan SET kode=?, kepanjangan=?, jenis=?, kategori=?, alternatif_benar=?, keterangan=?, updated_at=NOW() WHERE id=?");
            $st->execute([$kode, $kepanjangan, $jenis, $kategori, $alternatif, $keterangan, $id]);
            return ['status' => 'success', 'message' => 'Entri kamus singkatan berhasil diperbarui.'];
        }

        $st = $pdo->prepare("INSERT INTO mlite_mrmik_singkatan (kode, kepanjangan, jenis, kategori, alternatif_benar, keterangan, status, created_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())");
        $st->execute([$kode, $kepanjangan, $jenis, $kategori, $alternatif, $keterangan, $uname]);
        return ['status' => 'success', 'message' => 'Entri kamus singkatan baru berhasil ditambahkan.'];
    }

    public static function deleteSingkatan($core, int $id): array
    {
        if (!self::canManageRmeAudit($core)) {
            return ['status' => 'error', 'message' => 'Hanya Admin / Petugas Rekam Medis yang dapat menghapus entri kamus singkatan.'];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $st = $pdo->prepare("UPDATE mlite_mrmik_singkatan SET status = 0, updated_at = NOW() WHERE id = ?");
        $st->execute([$id]);
        return ['status' => 'success', 'message' => 'Entri kamus singkatan berhasil dihapus.'];
    }

    /**
     * Seed daftar standar singkatan RSGM & Daftar Singkatan Dilarang (Do Not Use List — ISMP/Kemenkes).
     */
    private static function seedDefaultSingkatan(\PDO $pdo): void
    {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM mlite_mrmik_singkatan")->fetchColumn();
        if ($cnt > 0) {
            return;
        }

        $defaults = [
            // === DIPERBOLEHKAN — UMUM & REKAM MEDIS ===
            ['SOAP', 'Subjective, Objective, Assessment, Plan', 'diperbolehkan', 'Umum & RM', '', 'Format dokumentasi CPPT terintegrasi'],
            ['CPPT', 'Catatan Perkembangan Pasien Terintegrasi', 'diperbolehkan', 'Umum & RM', '', 'Catatan kolaboratif seluruh PPA'],
            ['DPJP', 'Dokter Penanggung Jawab Pelayanan', 'diperbolehkan', 'Umum & RM', '', 'Dokter/dokter gigi penanggung jawab utama pasien'],
            ['PPA', 'Profesional Pemberi Asuhan', 'diperbolehkan', 'Umum & RM', '', 'Dokter, drg, perawat, terapis gigi, apoteker, gizi'],
            ['PRMRJ', 'Profil Ringkas Medis Rawat Jalan', 'diperbolehkan', 'Umum & RM', '', 'Ringkasan klinis pasien rawat jalan kompleks'],
            ['RME', 'Rekam Medis Elektronik', 'diperbolehkan', 'Umum & RM', '', 'Sesuai Permenkes No. 24 Tahun 2022'],
            ['TTE', 'Tanda Tangan Elektronik', 'diperbolehkan', 'Umum & RM', '', 'Autentikasi elektronik PPA'],
            ['MRS', 'Masuk Rumah Sakit', 'diperbolehkan', 'Umum & RM', '', 'Tanggal/waktu admisi rawat inap'],
            ['KRS', 'Keluar Rumah Sakit', 'diperbolehkan', 'Umum & RM', '', 'Tanggal/waktu pemulangan pasien'],
            ['APS', 'Atas Permintaan Sendiri', 'diperbolehkan', 'Umum & RM', '', 'Pulang paksa / menolak rawat inap'],
            ['TD', 'Tekanan Darah', 'diperbolehkan', 'Tanda Vital', '', 'Satuan mmHg'],
            ['HR', 'Heart Rate (Frekuensi Nadi)', 'diperbolehkan', 'Tanda Vital', '', 'Satuan kali/menit'],
            ['RR', 'Respiratory Rate (Frekuensi Napas)', 'diperbolehkan', 'Tanda Vital', '', 'Satuan kali/menit'],
            ['SpO2', 'Saturasi Oksigen Perifer', 'diperbolehkan', 'Tanda Vital', '', 'Satuan persen (%)'],
            ['GCS', 'Glasgow Coma Scale', 'diperbolehkan', 'Tanda Vital', '', 'Skala kesadaran E-V-M'],
            ['EWS', 'Early Warning Score', 'diperbolehkan', 'Tanda Vital', '', 'Skor peringatan dini perburukan klinis'],
            ['KU', 'Keadaan Umum', 'diperbolehkan', 'Tanda Vital', '', 'Baik / Sedang / Lemah'],
            ['CM', 'Compos Mentis', 'diperbolehkan', 'Tanda Vital', '', 'Kesadaran penuh'],

            // === DIPERBOLEHKAN — KEDOKTERAN GIGI & MULUT (RSGM) ===
            ['OD', 'Oral Diagnostic', 'diperbolehkan', 'Kedokteran Gigi', '', 'Poli diagnostik / pemeriksaan awal gigi & mulut'],
            ['OHI-S', 'Oral Hygiene Index - Simplified', 'diperbolehkan', 'Kedokteran Gigi', '', 'Indeks kebersihan mulut (DI-S + CI-S)'],
            ['DMF-T', 'Decayed, Missing, Filled Teeth', 'diperbolehkan', 'Kedokteran Gigi', '', 'Indeks karies gigi permanen'],
            ['def-t', 'decayed, extracted, filled teeth', 'diperbolehkan', 'Kedokteran Gigi', '', 'Indeks karies gigi sulung'],
            ['PSA', 'Perawatan Saluran Akar (Endodontik)', 'diperbolehkan', 'Kedokteran Gigi', '', 'Root Canal Treatment (RCT)'],
            ['RCT', 'Root Canal Treatment', 'diperbolehkan', 'Kedokteran Gigi', '', 'Perawatan saluran akar gigi'],
            ['SC', 'Scaling / Pembersihan Karang Gigi', 'diperbolehkan', 'Kedokteran Gigi', '', 'Tindakan periodonsia'],
            ['EXO', 'Ekstraksi / Pencabutan Gigi', 'diperbolehkan', 'Kedokteran Gigi', '', 'Wajib disertai nomor gigi FDI'],
            ['GIC', 'Glass Ionomer Cement', 'diperbolehkan', 'Kedokteran Gigi', '', 'Bahan restorasi semen ionomer kaca'],
            ['RK', 'Resin Komposit', 'diperbolehkan', 'Kedokteran Gigi', '', 'Bahan tumpatan sewarna gigi'],
            ['OPG', 'Orthopantomogram (Foto Panoramik)', 'diperbolehkan', 'Radiologi Gigi', '', 'Radiografi panoramik rahang & gigi'],
            ['CBCT', 'Cone Beam Computed Tomography', 'diperbolehkan', 'Radiologi Gigi', '', 'Pencitraan 3D maksilofasial'],
            ['TMJ', 'Temporomandibular Joint', 'diperbolehkan', 'Kedokteran Gigi', '', 'Sendi rahang'],
            ['BM', 'Bedah Mulut dan Maksilofasial', 'diperbolehkan', 'Kedokteran Gigi', '', 'Unit / KSM Bedah Mulut'],

            // === DILARANG (DO NOT USE LIST — ISMP / JCI) ===
            ['U / u', 'Unit (sering salah baca menjadi 0, 4, atau cc)', 'dilarang', 'Farmasi & Resep', 'Tulis lengkap: "unit"', 'Risiko overdosis insulin/heparin 10x lipat'],
            ['IU', 'International Unit (sering salah baca menjadi IV atau 10)', 'dilarang', 'Farmasi & Resep', 'Tulis lengkap: "International Unit" atau "unit"', 'Dilarang pada resep, CPPT, dan instruksi obat'],
            ['QD / q.d.', 'Quaque die / setiap hari (sering salah baca menjadi QID)', 'dilarang', 'Farmasi & Resep', 'Tulis: "1x sehari" atau "setiap hari"', 'Risiko pemberian 4x sehari'],
            ['QOD / q.o.d.', 'Every other day (sering salah baca menjadi QD atau QID)', 'dilarang', 'Farmasi & Resep', 'Tulis: "setiap 2 hari sekali"', 'Ambiguitas frekuensi dosis'],
            ['.5 mg (tanpa 0 di depan)', 'Angka desimal tanpa leading zero (terbaca 5 mg)', 'dilarang', 'Farmasi & Resep', 'Tulis: "0,5 mg" (wajib angka 0 sebelum koma)', 'Risiko kesalahan dosis 10x lipat'],
            ['5.0 mg (trailing zero)', 'Angka nol di belakang koma (terbaca 50 mg)', 'dilarang', 'Farmasi & Resep', 'Tulis: "5 mg" (tanpa ,0 di belakang)', 'Risiko kesalahan dosis 10x lipat'],
            ['MS / MSO4 / MgSO4', 'Morfin Sulfat vs Magnesium Sulfat (tertukar)', 'dilarang', 'Farmasi & Resep', 'Tulis nama lengkap: "Morfin Sulfat" atau "Magnesium Sulfat"', 'Obat High-Alert LASA'],
            ['cc', 'Cubic centimeter (sering terbaca "u" / unit)', 'dilarang', 'Farmasi & Resep', 'Tulis: "mL"', 'Standar metrik internasional'],
            ['µg', 'Mikrogram (sering salah baca menjadi mg)', 'dilarang', 'Farmasi & Resep', 'Tulis: "mcg" atau "mikrogram"', 'Risiko kesalahan dosis 1.000x lipat'],
            ['OD / OS / OU (untuk mata)', 'Oculus Dexter / Sinister (rancu dengan Oral Diagnostic di RSGM)', 'dilarang', 'Umum & RM', 'Tulis lengkap: "mata kanan" / "mata kiri" / "kedua mata"', 'Di RSGM, OD digunakan khusus untuk Oral Diagnostic'],

            // === SIMBOL KLINIS & ODONTOGRAM TERSTANDAR ===
            ['+', 'Positif / Ditemukan / Ada', 'simbol', 'Umum & RM', '', 'Contoh: nyeri tekan (+), perkusi (+)'],
            ['-', 'Negatif / Tidak ditemukan', 'simbol', 'Umum & RM', '', 'Contoh: alergi (-), mobilitas (-)'],
            ['↑ / ↓', 'Meningkat / Menurun', 'simbol', 'Tanda Vital', '', 'Perubahan tren klinis / hasil lab'],
            ['11–48 (FDI)', 'Sistem penomoran gigi permanen dua digit FDI', 'simbol', 'Odontogram', '', 'Kuadran 1 (kanan atas) s.d. Kuadran 4 (kanan bawah)'],
            ['51–85 (FDI)', 'Sistem penomoran gigi sulung dua digit FDI', 'simbol', 'Odontogram', '', 'Kuadran 5 (kanan atas) s.d. Kuadran 8 (kanan bawah)'],
            ['car / sou / mis', 'Simbol Odontogram Kemenkes (Caries / Sound / Missing)', 'simbol', 'Odontogram', '', 'Standar Rekam Medis Kedokteran Gigi Kemenkes RI'],
        ];

        $ins = $pdo->prepare("
            INSERT INTO mlite_mrmik_singkatan
                (kode, kepanjangan, jenis, kategori, alternatif_benar, keterangan, status, created_by, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, 'system', NOW())
        ");
        foreach ($defaults as $d) {
            $ins->execute($d);
        }
    }
}


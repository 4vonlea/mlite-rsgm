<?php

namespace Systems\Lib;

/**
 * Layanan Deteksi & Penggabungan Rekam Medis Duplikat (Soft Archive / Opsi A)
 * sesuai standar pengelolaan rekam medis elektronik & PMK 24/2022.
 */
class PasienMergeService
{
    /** @var bool */
    private static $schemaChecked = false;

    /**
     * Pastikan tabel log & arsip penggabungan RM tersedia secara idempoten.
     */
    public static function ensureSchema(\PDO $pdo): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `mlite_pasien_merge_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `source_no_rkm_medis` varchar(15) NOT NULL,
                `target_no_rkm_medis` varchar(15) NOT NULL,
                `source_nm_pasien` varchar(100) NOT NULL DEFAULT '',
                `target_nm_pasien` varchar(100) NOT NULL DEFAULT '',
                `reason` text NOT NULL,
                `fill_empty_biodata` tinyint(1) NOT NULL DEFAULT 0,
                `total_rows_moved` int(11) NOT NULL DEFAULT 0,
                `affected_tables_json` longtext DEFAULT NULL,
                `source_snapshot_json` longtext DEFAULT NULL,
                `target_before_json` longtext DEFAULT NULL,
                `merged_by_username` varchar(100) NOT NULL DEFAULT '',
                `merged_by_name` varchar(150) NOT NULL DEFAULT '',
                `merged_by_role` varchar(50) NOT NULL DEFAULT '',
                `ip_address` varchar(64) DEFAULT '',
                `merged_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_source_rm` (`source_no_rkm_medis`),
                KEY `idx_target_rm` (`target_no_rkm_medis`),
                KEY `idx_merged_at` (`merged_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (\Throwable $e) {
            error_log('[PasienMergeService::ensureSchema] ' . $e->getMessage());
        }
    }

    /**
     * Otorisasi ketat: hanya admin (access=all / role=admin / user id 1) dan role rekammedis.
     */
    public static function canManageMerge($core): bool
    {
        if (empty($_SESSION['mlite_user'])) {
            return false;
        }
        $userId = (int)($_SESSION['mlite_user'] ?? 0);
        if ($userId <= 0) {
            return false;
        }
        $role = strtolower(trim((string)$core->getUserInfo('role', $userId, true)));
        $access = strtolower(trim((string)$core->getUserInfo('access', $userId)));
        return $userId === 1 || $access === 'all' || $role === 'admin' || $role === 'rekammedis';
    }

    /**
     * Cek apakah suatu No. RM sudah digabung (soft-archived).
     */
    public static function getMergeRecord(\PDO $pdo, string $noRm): ?array
    {
        $noRm = trim($noRm);
        if ($noRm === '') {
            return null;
        }
        self::ensureSchema($pdo);
        $st = $pdo->prepare("SELECT * FROM `mlite_pasien_merge_log` WHERE `source_no_rkm_medis` = ? LIMIT 1");
        $st->execute([$noRm]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Ikuti rantai penggabungan (maks 5 hop) hingga menemukan No. RM aktif.
     */
    public static function resolveActiveRm(\PDO $pdo, string $noRm): array
    {
        $orig = trim($noRm);
        $curr = $orig;
        $firstLog = null;
        $visited = [$curr => true];

        for ($i = 0; $i < 5; $i++) {
            $log = self::getMergeRecord($pdo, $curr);
            if (!$log) {
                break;
            }
            if ($firstLog === null) {
                $firstLog = $log;
            }
            $next = trim((string)($log['target_no_rkm_medis'] ?? ''));
            if ($next === '' || isset($visited[$next])) {
                break;
            }
            $visited[$next] = true;
            $curr = $next;
        }

        return [
            'original_rm' => $orig,
            'resolved_rm' => $curr,
            'was_merged'  => ($firstLog !== null && $curr !== $orig),
            'merge_info'  => $firstLog,
        ];
    }

    /**
     * Filter array baris pasien untuk menyembunyikan RM yang sudah digabung.
     */
    public static function filterOutMergedRows(\PDO $pdo, array $rows, string $col = 'no_rkm_medis'): array
    {
        if (empty($rows)) {
            return $rows;
        }
        self::ensureSchema($pdo);
        $rms = [];
        foreach ($rows as $r) {
            if (!empty($r[$col])) {
                $rms[] = (string)$r[$col];
            }
        }
        $rms = array_values(array_unique($rms));
        if (empty($rms)) {
            return $rows;
        }
        $ph = implode(',', array_fill(0, count($rms), '?'));
        $st = $pdo->prepare("SELECT `source_no_rkm_medis` FROM `mlite_pasien_merge_log` WHERE `source_no_rkm_medis` IN ({$ph})");
        $st->execute($rms);
        $mergedMap = [];
        foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $mRm) {
            $mergedMap[(string)$mRm] = true;
        }
        if (empty($mergedMap)) {
            return $rows;
        }
        return array_values(array_filter($rows, function ($r) use ($col, $mergedMap) {
            return empty($mergedMap[(string)($r[$col] ?? '')]);
        }));
    }

    /**
     * Ambil daftar No. RM yang sudah digabung sebagai map [rm => true].
     */
    public static function getMergedRmSet(\PDO $pdo): array
    {
        self::ensureSchema($pdo);
        $rows = $pdo->query("SELECT `source_no_rkm_medis` FROM `mlite_pasien_merge_log`")->fetchAll(\PDO::FETCH_COLUMN);
        $map = [];
        foreach ($rows as $rm) {
            $map[(string)$rm] = true;
        }
        return $map;
    }

    /**
     * Validasi pencegahan duplikat saat pendaftaran pasien baru.
     * Mengembalikan ['action' => 'ok'|'block'|'warn', 'type' => ..., 'message' => ..., 'matches' => [...]]
     */
    public static function checkRegistrationDuplicates(\PDO $pdo, array $input): array
    {
        self::ensureSchema($pdo);

        $nik = trim((string)($input['no_ktp'] ?? ''));
        $bpjs = trim((string)($input['no_peserta'] ?? ''));
        $nama = trim((string)($input['nm_pasien'] ?? ''));
        $tglLahir = trim((string)($input['tgl_lahir'] ?? ''));

        $baseSelect = "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                       FROM `pasien` p
                       LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                       WHERE m.id IS NULL";

        // 1. Cek NIK (minimal 12 digit valid non-zero) -> BLOCK
        $nikDigits = preg_replace('/\s+/', '', $nik);
        if (strlen($nikDigits) >= 12 && !preg_match('/^0+$/', $nikDigits)) {
            $st = $pdo->prepare($baseSelect . " AND TRIM(p.no_ktp) = ? LIMIT 5");
            $st->execute([$nik]);
            $matches = $st->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($matches)) {
                return [
                    'action'  => 'block',
                    'type'    => 'nik',
                    'message' => 'NIK (' . $nik . ') sudah terdaftar pada pasien aktif. Gunakan data pasien yang sudah ada untuk mencegah rekam medis ganda.',
                    'matches' => $matches,
                ];
            }
        }

        // 2. Cek No. BPJS (minimal 11 digit valid non-zero) -> BLOCK
        $bpjsDigits = preg_replace('/\s+/', '', $bpjs);
        if (strlen($bpjsDigits) >= 11 && !preg_match('/^0+$/', $bpjsDigits)) {
            $st = $pdo->prepare($baseSelect . " AND TRIM(p.no_peserta) = ? LIMIT 5");
            $st->execute([$bpjs]);
            $matches = $st->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($matches)) {
                return [
                    'action'  => 'block',
                    'type'    => 'bpjs',
                    'message' => 'Nomor Kartu BPJS (' . $bpjs . ') sudah terdaftar pada pasien aktif. Gunakan data pasien yang sudah ada untuk mencegah rekam medis ganda.',
                    'matches' => $matches,
                ];
            }
        }

        // 3. Cek Nama + Tanggal Lahir -> WARN (boleh lanjut setelah konfirmasi)
        if ($nama !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir) && $tglLahir > '1900-01-01') {
            $st = $pdo->prepare($baseSelect . " AND UPPER(TRIM(p.nm_pasien)) = UPPER(?) AND p.tgl_lahir = ? LIMIT 5");
            $st->execute([$nama, $tglLahir]);
            $matches = $st->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($matches)) {
                return [
                    'action'  => 'warn',
                    'type'    => 'name_dob',
                    'message' => 'Ditemukan pasien aktif dengan Nama dan Tanggal Lahir yang sama. Periksa terlebih dahulu sebelum membuat No. RM baru.',
                    'matches' => $matches,
                ];
            }
        }

        return ['action' => 'ok', 'type' => '', 'message' => '', 'matches' => []];
    }

    /**
     * Hitung ringkasan jumlah grup duplikat aktif per kategori + total riwayat merge.
     */
    public static function getCounts(\PDO $pdo): array
    {
        self::ensureSchema($pdo);

        $nikCount = (int)$pdo->query(
            "SELECT COUNT(*) FROM (
                SELECT TRIM(p.no_ktp) AS k
                FROM `pasien` p
                LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                WHERE m.id IS NULL
                  AND LENGTH(TRIM(p.no_ktp)) >= 12
                  AND TRIM(p.no_ktp) NOT REGEXP '^0+$'
                GROUP BY k
                HAVING COUNT(*) > 1
            ) t"
        )->fetchColumn();

        $bpjsCount = (int)$pdo->query(
            "SELECT COUNT(*) FROM (
                SELECT TRIM(p.no_peserta) AS k
                FROM `pasien` p
                LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                WHERE m.id IS NULL
                  AND LENGTH(TRIM(p.no_peserta)) >= 11
                  AND TRIM(p.no_peserta) NOT REGEXP '^0+$'
                GROUP BY k
                HAVING COUNT(*) > 1
            ) t"
        )->fetchColumn();

        $namaTglCount = (int)$pdo->query(
            "SELECT COUNT(*) FROM (
                SELECT UPPER(TRIM(p.nm_pasien)) AS n, p.tgl_lahir
                FROM `pasien` p
                LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                WHERE m.id IS NULL
                  AND p.tgl_lahir > '1900-01-01'
                  AND TRIM(p.nm_pasien) != ''
                GROUP BY n, p.tgl_lahir
                HAVING COUNT(*) > 1
            ) t"
        )->fetchColumn();

        $anomaliCount = (int)$pdo->query(
            "SELECT COUNT(*)
             FROM `pasien` p
             LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
             WHERE m.id IS NULL
               AND (LENGTH(p.no_rkm_medis) != 6 OR p.no_rkm_medis NOT REGEXP '^[0-9]+$')"
        )->fetchColumn();

        $riwayatCount = (int)$pdo->query("SELECT COUNT(*) FROM `mlite_pasien_merge_log`")->fetchColumn();

        return [
            'nik'      => $nikCount,
            'bpjs'     => $bpjsCount,
            'nama_tgl' => $namaTglCount,
            'anomali'  => $anomaliCount,
            'riwayat'  => $riwayatCount,
        ];
    }

    /**
     * Ambil daftar grup duplikat terpaginasi berdasarkan tab: nik | bpjs | nama_tgl | anomali | riwayat.
     */
    public static function listGroups(\PDO $pdo, string $tab, string $search = '', int $page = 1, int $perPage = 12): array
    {
        self::ensureSchema($pdo);
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;
        $search = trim($search);

        if ($tab === 'riwayat') {
            return self::listMergeHistory($pdo, $search, $page, $perPage);
        }

        if ($tab === 'anomali') {
            return self::listAnomalies($pdo, $search, $page, $perPage);
        }

        $params = [];
        $havingExtra = '';

        if ($tab === 'bpjs') {
            $groupExpr = "TRIM(p.no_peserta)";
            $where = "m.id IS NULL AND LENGTH(TRIM(p.no_peserta)) >= 11 AND TRIM(p.no_peserta) NOT REGEXP '^0+$'";
            $keySql = "TRIM(p.no_peserta) AS group_key, TRIM(p.no_peserta) AS group_label, COUNT(*) AS member_count";
            $groupBy = "GROUP BY group_key";
            if ($search !== '') {
                $havingExtra = " AND (GROUP_CONCAT(p.no_rkm_medis SEPARATOR ' ') LIKE ? OR GROUP_CONCAT(p.nm_pasien SEPARATOR ' ') LIKE ? OR TRIM(p.no_peserta) LIKE ?)";
                $like = '%' . $search . '%';
                $params = [$like, $like, $like];
            }
        } elseif ($tab === 'nama_tgl') {
            $where = "m.id IS NULL AND p.tgl_lahir > '1900-01-01' AND TRIM(p.nm_pasien) != ''";
            $keySql = "CONCAT(UPPER(TRIM(p.nm_pasien)), '|', p.tgl_lahir) AS group_key, CONCAT(UPPER(TRIM(p.nm_pasien)), ' (', p.tgl_lahir, ')') AS group_label, UPPER(TRIM(p.nm_pasien)) AS grp_name, p.tgl_lahir AS grp_dob, COUNT(*) AS member_count";
            $groupBy = "GROUP BY grp_name, grp_dob";
            if ($search !== '') {
                $havingExtra = " AND (GROUP_CONCAT(p.no_rkm_medis SEPARATOR ' ') LIKE ? OR UPPER(TRIM(p.nm_pasien)) LIKE ? OR p.tgl_lahir LIKE ?)";
                $like = '%' . $search . '%';
                $params = [$like, '%' . strtoupper($search) . '%', $like];
            }
        } else {
            // default: nik
            $tab = 'nik';
            $where = "m.id IS NULL AND LENGTH(TRIM(p.no_ktp)) >= 12 AND TRIM(p.no_ktp) NOT REGEXP '^0+$'";
            $keySql = "TRIM(p.no_ktp) AS group_key, TRIM(p.no_ktp) AS group_label, COUNT(*) AS member_count";
            $groupBy = "GROUP BY group_key";
            if ($search !== '') {
                $havingExtra = " AND (GROUP_CONCAT(p.no_rkm_medis SEPARATOR ' ') LIKE ? OR GROUP_CONCAT(p.nm_pasien SEPARATOR ' ') LIKE ? OR TRIM(p.no_ktp) LIKE ?)";
                $like = '%' . $search . '%';
                $params = [$like, $like, $like];
            }
        }

        $countSql = "SELECT COUNT(*) FROM (
            SELECT {$keySql}
            FROM `pasien` p
            LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
            WHERE {$where}
            {$groupBy}
            HAVING COUNT(*) > 1 {$havingExtra}
        ) sub";
        $stCnt = $pdo->prepare($countSql);
        $stCnt->execute($params);
        $total = (int)$stCnt->fetchColumn();

        $listSql = "SELECT {$keySql}
            FROM `pasien` p
            LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
            WHERE {$where}
            {$groupBy}
            HAVING COUNT(*) > 1 {$havingExtra}
            ORDER BY member_count DESC, group_key ASC
            LIMIT {$perPage} OFFSET {$offset}";
        $stList = $pdo->prepare($listSql);
        $stList->execute($params);
        $groups = $stList->fetchAll(\PDO::FETCH_ASSOC);

        $resultGroups = [];
        foreach ($groups as $g) {
            if ($tab === 'nik') {
                $stMem = $pdo->prepare(
                    "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                     FROM `pasien` p
                     LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                     WHERE m.id IS NULL AND TRIM(p.no_ktp) = ?
                     ORDER BY p.tgl_daftar ASC, p.no_rkm_medis ASC"
                );
                $stMem->execute([$g['group_key']]);
            } elseif ($tab === 'bpjs') {
                $stMem = $pdo->prepare(
                    "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                     FROM `pasien` p
                     LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                     WHERE m.id IS NULL AND TRIM(p.no_peserta) = ?
                     ORDER BY p.tgl_daftar ASC, p.no_rkm_medis ASC"
                );
                $stMem->execute([$g['group_key']]);
            } else {
                $stMem = $pdo->prepare(
                    "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                     FROM `pasien` p
                     LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                     WHERE m.id IS NULL AND UPPER(TRIM(p.nm_pasien)) = ? AND p.tgl_lahir = ?
                     ORDER BY p.tgl_daftar ASC, p.no_rkm_medis ASC"
                );
                $stMem->execute([$g['grp_name'], $g['grp_dob']]);
            }
            $members = self::enrichMembersQuickStats($pdo, $stMem->fetchAll(\PDO::FETCH_ASSOC));
            $suggestedMaster = self::pickSuggestedMaster($members);
            $resultGroups[] = [
                'group_key'        => (string)$g['group_key'],
                'group_label'      => (string)$g['group_label'],
                'group_type'       => $tab,
                'suggested_master' => $suggestedMaster,
                'members'          => $members,
            ];
        }

        return [
            'tab'   => $tab,
            'items' => $resultGroups,
            'meta'  => [
                'page'       => $page,
                'per_page'   => $perPage,
                'total'      => $total,
                'total_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * Daftar pasien dengan format No. RM anomali (!= 6 digit atau non-angka) beserta pasangan 6-digitnya.
     */
    private static function listAnomalies(\PDO $pdo, string $search, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $where = "m.id IS NULL AND (LENGTH(p.no_rkm_medis) != 6 OR p.no_rkm_medis NOT REGEXP '^[0-9]+$')";
        $params = [];
        if ($search !== '') {
            $where .= " AND (p.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR p.no_ktp LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        $stCnt = $pdo->prepare("SELECT COUNT(*) FROM `pasien` p LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis WHERE {$where}");
        $stCnt->execute($params);
        $total = (int)$stCnt->fetchColumn();

        $stList = $pdo->prepare(
            "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
             FROM `pasien` p
             LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
             WHERE {$where}
             ORDER BY p.no_rkm_medis ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stList->execute($params);
        $anomalies = $stList->fetchAll(\PDO::FETCH_ASSOC);

        $groups = [];
        foreach ($anomalies as $a) {
            $rm = (string)$a['no_rkm_medis'];
            $digits = preg_replace('/[^0-9]/', '', $rm);
            $members = [$a];
            $seen = [$rm => true];

            // Cari pasangan padded 6-digit (mis. 59761 -> 059761, '055268 -> 055268)
            $candidates = [];
            if ($digits !== '' && strlen($digits) <= 6) {
                $padded = str_pad($digits, 6, '0', STR_PAD_LEFT);
                if (!isset($seen[$padded])) {
                    $candidates[] = $padded;
                }
            }
            if (!empty($candidates)) {
                $ph = implode(',', array_fill(0, count($candidates), '?'));
                $stPair = $pdo->prepare(
                    "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                     FROM `pasien` p
                     LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                     WHERE m.id IS NULL AND p.no_rkm_medis IN ({$ph})"
                );
                $stPair->execute($candidates);
                foreach ($stPair->fetchAll(\PDO::FETCH_ASSOC) as $pair) {
                    if (!isset($seen[$pair['no_rkm_medis']])) {
                        $members[] = $pair;
                        $seen[$pair['no_rkm_medis']] = true;
                    }
                }
            }

            // Jika belum ketemu pasangan 6-digit, coba cari nama persis + tgl lahir sama
            if (count($members) === 1 && !empty($a['nm_pasien']) && !empty($a['tgl_lahir']) && $a['tgl_lahir'] > '1900-01-01') {
                $stName = $pdo->prepare(
                    "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
                     FROM `pasien` p
                     LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
                     WHERE m.id IS NULL AND p.no_rkm_medis != ? AND UPPER(TRIM(p.nm_pasien)) = UPPER(TRIM(?)) AND p.tgl_lahir = ?
                     LIMIT 3"
                );
                $stName->execute([$rm, $a['nm_pasien'], $a['tgl_lahir']]);
                foreach ($stName->fetchAll(\PDO::FETCH_ASSOC) as $pair) {
                    if (!isset($seen[$pair['no_rkm_medis']])) {
                        $members[] = $pair;
                        $seen[$pair['no_rkm_medis']] = true;
                    }
                }
            }

            $members = self::enrichMembersQuickStats($pdo, $members);
            $reasonTag = !preg_match('/^[0-9]+$/', $rm)
                ? 'Karakter non-angka pada No. RM'
                : ('Panjang No. RM ' . strlen($rm) . ' digit (standar 6 digit)');

            $groups[] = [
                'group_key'        => $rm,
                'group_label'      => $rm . ' — ' . $reasonTag,
                'group_type'       => 'anomali',
                'suggested_master' => self::pickSuggestedMaster($members),
                'members'          => $members,
            ];
        }

        return [
            'tab'   => 'anomali',
            'items' => $groups,
            'meta'  => [
                'page'       => $page,
                'per_page'   => $perPage,
                'total'      => $total,
                'total_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * Daftar riwayat log penggabungan RM (terpaginasi, terbaru dulu).
     */
    private static function listMergeHistory(\PDO $pdo, string $search, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where = "(source_no_rkm_medis LIKE ? OR target_no_rkm_medis LIKE ? OR source_nm_pasien LIKE ? OR target_nm_pasien LIKE ? OR reason LIKE ? OR merged_by_name LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like, $like, $like];
        }

        $stCnt = $pdo->prepare("SELECT COUNT(*) FROM `mlite_pasien_merge_log` WHERE {$where}");
        $stCnt->execute($params);
        $total = (int)$stCnt->fetchColumn();

        $st = $pdo->prepare(
            "SELECT id, source_no_rkm_medis, target_no_rkm_medis, source_nm_pasien, target_nm_pasien,
                    reason, fill_empty_biodata, total_rows_moved, affected_tables_json,
                    merged_by_username, merged_by_name, merged_by_role, merged_at
             FROM `mlite_pasien_merge_log`
             WHERE {$where}
             ORDER BY id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $st->execute($params);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $decoded = json_decode((string)($r['affected_tables_json'] ?? '[]'), true);
            $r['affected_tables'] = is_array($decoded) ? $decoded : [];
            unset($r['affected_tables_json']);
        }
        unset($r);

        return [
            'tab'   => 'riwayat',
            'items' => $rows,
            'meta'  => [
                'page'       => $page,
                'per_page'   => $perPage,
                'total'      => $total,
                'total_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * Lengkapi array anggota grup dengan jumlah kunjungan & kunjungan terakhir (1 query batch).
     */
    private static function enrichMembersQuickStats(\PDO $pdo, array $members): array
    {
        if (empty($members)) {
            return [];
        }
        $rms = array_column($members, 'no_rkm_medis');
        $ph = implode(',', array_fill(0, count($rms), '?'));

        $st = $pdo->prepare(
            "SELECT no_rkm_medis, COUNT(*) AS visit_count, MAX(tgl_registrasi) AS last_visit
             FROM `reg_periksa`
             WHERE no_rkm_medis IN ({$ph})
             GROUP BY no_rkm_medis"
        );
        $st->execute($rms);
        $stats = [];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $s) {
            $stats[$s['no_rkm_medis']] = $s;
        }

        foreach ($members as &$m) {
            $rm = (string)$m['no_rkm_medis'];
            $m['visit_count'] = (int)($stats[$rm]['visit_count'] ?? 0);
            $m['last_visit'] = (string)($stats[$rm]['last_visit'] ?? '');
            $m['is_standard_rm'] = (bool)preg_match('/^[0-9]{6}$/', $rm);
        }
        unset($m);
        return $members;
    }

    /**
     * Rekomendasikan RM Utama: prioritaskan format standar 6-digit angka -> kunjungan terbanyak -> daftar terlama.
     */
    private static function pickSuggestedMaster(array $members): string
    {
        if (empty($members)) {
            return '';
        }
        $sorted = $members;
        usort($sorted, function ($a, $b) {
            $stdA = !empty($a['is_standard_rm']) ? 1 : 0;
            $stdB = !empty($b['is_standard_rm']) ? 1 : 0;
            if ($stdA !== $stdB) {
                return $stdB <=> $stdA;
            }
            $vA = (int)($a['visit_count'] ?? 0);
            $vB = (int)($b['visit_count'] ?? 0);
            if ($vA !== $vB) {
                return $vB <=> $vA;
            }
            return strcmp((string)($a['tgl_daftar'] ?? ''), (string)($b['tgl_daftar'] ?? ''));
        });
        return (string)($sorted[0]['no_rkm_medis'] ?? '');
    }

    /**
     * Pencarian pasien aktif (belum di-merge) untuk tab Gabung Manual.
     */
    public static function searchActivePatients(\PDO $pdo, string $q, int $limit = 15): array
    {
        self::ensureSchema($pdo);
        $q = trim($q);
        $limit = max(1, min(30, $limit));
        if ($q === '') {
            return [];
        }
        $like = '%' . $q . '%';
        $st = $pdo->prepare(
            "SELECT p.no_rkm_medis, p.nm_pasien, p.no_ktp, p.no_peserta, p.tgl_lahir, p.jk, p.alamat, p.no_tlp, p.tgl_daftar
             FROM `pasien` p
             LEFT JOIN `mlite_pasien_merge_log` m ON m.source_no_rkm_medis = p.no_rkm_medis
             WHERE m.id IS NULL
               AND (p.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR p.no_ktp LIKE ? OR p.no_peserta LIKE ?)
             ORDER BY (p.no_rkm_medis = ?) DESC, p.tgl_daftar DESC
             LIMIT {$limit}"
        );
        $st->execute([$like, $like, $like, $like, $q]);
        return self::enrichMembersQuickStats($pdo, $st->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Bandingkan 2 No. RM secara mendalam (biodata + hitungan transaksi klinis, penunjang, RME, billing, SEP).
     */
    public static function compareTwo(\PDO $pdo, string $sourceRm, string $targetRm): array
    {
        self::ensureSchema($pdo);
        $sourceRm = trim($sourceRm);
        $targetRm = trim($targetRm);

        if ($sourceRm === '' || $targetRm === '' || $sourceRm === $targetRm) {
            return ['status' => 'error', 'message' => 'Pilih dua Nomor Rekam Medis yang berbeda.'];
        }

        $pSource = self::fetchPatientDetail($pdo, $sourceRm);
        $pTarget = self::fetchPatientDetail($pdo, $targetRm);

        if (!$pSource || !$pTarget) {
            return ['status' => 'error', 'message' => 'Salah satu Nomor Rekam Medis tidak ditemukan di database pasien.'];
        }
        if (self::getMergeRecord($pdo, $sourceRm)) {
            return ['status' => 'error', 'message' => 'RM Sumber (' . $sourceRm . ') sudah pernah digabung sebelumnya.'];
        }
        if (self::getMergeRecord($pdo, $targetRm)) {
            return ['status' => 'error', 'message' => 'RM Utama (' . $targetRm . ') berstatus sudah digabung/arsip. Pilih RM aktif sebagai RM Utama.'];
        }

        $fillable = self::computeFillableBiodata($pSource['pasien'], $pTarget['pasien']);

        return [
            'status'           => 'success',
            'source'           => $pSource,
            'target'           => $pTarget,
            'fillable_biodata' => $fillable,
            'suggested_master' => self::pickSuggestedMaster([
                array_merge($pSource['pasien'], ['visit_count' => $pSource['stats']['kunjungan'], 'is_standard_rm' => (bool)preg_match('/^[0-9]{6}$/', $sourceRm)]),
                array_merge($pTarget['pasien'], ['visit_count' => $pTarget['stats']['kunjungan'], 'is_standard_rm' => (bool)preg_match('/^[0-9]{6}$/', $targetRm)]),
            ]),
        ];
    }

    private static function fetchPatientDetail(\PDO $pdo, string $noRm): ?array
    {
        $st = $pdo->prepare(
            "SELECT no_rkm_medis, nm_pasien, no_ktp, no_peserta, jk, tmp_lahir, tgl_lahir,
                    nm_ibu, alamat, gol_darah, pekerjaan, stts_nikah, agama, tgl_daftar,
                    no_tlp, email, namakeluarga, kd_pj
             FROM `pasien` WHERE `no_rkm_medis` = ? LIMIT 1"
        );
        $st->execute([$noRm]);
        $pasien = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$pasien) {
            return null;
        }

        $stReg = $pdo->prepare(
            "SELECT r.no_rawat, r.tgl_registrasi, r.jam_reg, r.kd_poli, p.nm_poli, r.stts, r.status_bayar
             FROM `reg_periksa` r
             LEFT JOIN `poliklinik` p ON p.kd_poli = r.kd_poli
             WHERE r.no_rkm_medis = ?
             ORDER BY r.tgl_registrasi DESC, r.jam_reg DESC"
        );
        $stReg->execute([$noRm]);
        $visits = $stReg->fetchAll(\PDO::FETCH_ASSOC);
        $noRawats = array_column($visits, 'no_rawat');

        $stats = [
            'kunjungan' => count($visits),
            'soap'      => 0,
            'tindakan'  => 0,
            'radiologi' => 0,
            'lab'       => 0,
            'resep'     => 0,
            'billing'   => 0,
            'berkas'    => 0,
            'rme'       => 0,
            'sep'       => 0,
        ];

        if (!empty($noRawats)) {
            $ph = implode(',', array_fill(0, count($noRawats), '?'));
            $countByRawat = function (string $tbl) use ($pdo, $ph, $noRawats): int {
                try {
                    $q = $pdo->prepare("SELECT COUNT(*) FROM `{$tbl}` WHERE `no_rawat` IN ({$ph})");
                    $q->execute($noRawats);
                    return (int)$q->fetchColumn();
                } catch (\Throwable $e) {
                    return 0;
                }
            };
            $stats['soap']      = $countByRawat('pemeriksaan_ralan') + $countByRawat('pemeriksaan_ranap');
            $stats['tindakan']  = $countByRawat('rawat_jl_dr') + $countByRawat('rawat_jl_pr') + $countByRawat('rawat_jl_drpr') + $countByRawat('operasi');
            $stats['radiologi'] = $countByRawat('periksa_radiologi');
            $stats['lab']       = $countByRawat('periksa_lab');
            $stats['resep']     = $countByRawat('resep_obat');
            $stats['billing']   = $countByRawat('mlite_billing');
            $stats['berkas']    = $countByRawat('berkas_digital_perawatan');
        }

        // Hitung SEP (kolom nomr)
        try {
            $qSep = $pdo->prepare("SELECT COUNT(*) FROM `bridging_sep` WHERE `nomr` = ?");
            $qSep->execute([$noRm]);
            $stats['sep'] = (int)$qSep->fetchColumn();
        } catch (\Throwable $e) {
        }

        // Hitung total baris Form RME yang menyimpan no_rkm_medis
        try {
            $rmeTbls = $pdo->query(
                "SELECT DISTINCT nama_tabel FROM `mlite_rme_form` WHERE nama_tabel IS NOT NULL AND nama_tabel != ''"
            )->fetchAll(\PDO::FETCH_COLUMN);
            $existingCols = [];
            foreach ($pdo->query(
                "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME IN ('no_rkm_medis','no_rawat')"
            )->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                $existingCols[$c['TABLE_NAME']][$c['COLUMN_NAME']] = true;
            }
            $rmeTotal = 0;
            foreach ($rmeTbls as $rt) {
                $rt = trim((string)$rt);
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $rt) || empty($existingCols[$rt])) {
                    continue;
                }
                if (!empty($existingCols[$rt]['no_rkm_medis'])) {
                    $q = $pdo->prepare("SELECT COUNT(*) FROM `{$rt}` WHERE `no_rkm_medis` = ?");
                    $q->execute([$noRm]);
                    $rmeTotal += (int)$q->fetchColumn();
                } elseif (!empty($existingCols[$rt]['no_rawat']) && !empty($noRawats)) {
                    $ph = implode(',', array_fill(0, count($noRawats), '?'));
                    $q = $pdo->prepare("SELECT COUNT(*) FROM `{$rt}` WHERE `no_rawat` IN ({$ph})");
                    $q->execute($noRawats);
                    $rmeTotal += (int)$q->fetchColumn();
                }
            }
            $stats['rme'] = $rmeTotal;
        } catch (\Throwable $e) {
        }

        return [
            'pasien'        => $pasien,
            'stats'         => $stats,
            'recent_visits' => array_slice($visits, 0, 5),
        ];
    }

    private static function isBlankBiodataValue($val): bool
    {
        $s = trim((string)$val);
        return $s === '' || $s === '-' || $s === '0' || $s === '0000-00-00' || preg_match('/^0+$/', $s);
    }

    private static function computeFillableBiodata(array $sourcePasien, array $targetPasien): array
    {
        $labels = [
            'no_ktp'       => 'NIK / No. KTP',
            'no_peserta'   => 'No. Kartu BPJS',
            'no_tlp'       => 'No. Telepon',
            'alamat'       => 'Alamat',
            'email'        => 'Email',
            'tmp_lahir'    => 'Tempat Lahir',
            'tgl_lahir'    => 'Tanggal Lahir',
            'nm_ibu'       => 'Nama Ibu',
            'gol_darah'    => 'Golongan Darah',
            'pekerjaan'    => 'Pekerjaan',
            'namakeluarga' => 'Nama Penanggung Jawab',
        ];
        $fillable = [];
        foreach ($labels as $col => $label) {
            $tVal = $targetPasien[$col] ?? '';
            $sVal = $sourcePasien[$col] ?? '';
            if (self::isBlankBiodataValue($tVal) && !self::isBlankBiodataValue($sVal)) {
                $fillable[] = [
                    'field' => $col,
                    'label' => $label,
                    'value' => trim((string)$sVal),
                ];
            }
        }
        return $fillable;
    }

    /**
     * Eksekusi penggabungan RM secara atomik (Opsi A: Soft Archive).
     */
    public static function executeMerge($core, string $sourceRm, string $targetRm, string $reason, bool $fillEmptyBiodata = true): array
    {
        if (!self::canManageMerge($core)) {
            return ['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menggabungkan rekam medis.'];
        }

        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $sourceRm = trim($sourceRm);
        $targetRm = trim($targetRm);
        $reason = trim($reason);

        if ($sourceRm === '' || $targetRm === '' || $sourceRm === $targetRm) {
            return ['status' => 'error', 'message' => 'RM Sumber dan RM Utama harus diisi dan berbeda.'];
        }
        if (mb_strlen($reason) < 5) {
            return ['status' => 'error', 'message' => 'Alasan penggabungan wajib diisi minimal 5 karakter untuk jejak audit.'];
        }

        $stP = $pdo->prepare("SELECT * FROM `pasien` WHERE `no_rkm_medis` = ? LIMIT 1");
        $stP->execute([$sourceRm]);
        $sourceRow = $stP->fetch(\PDO::FETCH_ASSOC);

        $stP->execute([$targetRm]);
        $targetRow = $stP->fetch(\PDO::FETCH_ASSOC);

        if (!$sourceRow || !$targetRow) {
            return ['status' => 'error', 'message' => 'Data RM Sumber atau RM Utama tidak ditemukan.'];
        }
        if (self::getMergeRecord($pdo, $sourceRm)) {
            return ['status' => 'error', 'message' => 'RM Sumber (' . $sourceRm . ') sudah pernah digabung sebelumnya.'];
        }
        if (self::getMergeRecord($pdo, $targetRm)) {
            return ['status' => 'error', 'message' => 'RM Utama (' . $targetRm . ') sudah berstatus arsip/tergabung.'];
        }

        // Kumpulkan semua tabel dasar (bukan VIEW/ARCHIVE/generated column) yang mereferensikan No. RM
        $targets = [];
        $colRows = $pdo->query(
            "SELECT c.TABLE_NAME, c.COLUMN_NAME, UPPER(COALESCE(t.ENGINE, '')) AS ENGINE_NAME
             FROM information_schema.COLUMNS c
             INNER JOIN information_schema.TABLES t
               ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND t.TABLE_TYPE = 'BASE TABLE'
               AND UPPER(COALESCE(t.ENGINE, 'INNODB')) IN ('INNODB', 'MYISAM', 'ARIA', 'MEMORY')
               AND c.COLUMN_NAME = 'no_rkm_medis'
               AND (c.EXTRA IS NULL OR UPPER(c.EXTRA) NOT LIKE '%GENERATED%')
               AND c.TABLE_NAME NOT IN ('pasien', 'mlite_pasien_merge_log', 'set_no_rkm_medis')
             ORDER BY (c.TABLE_NAME = 'reg_periksa') DESC, c.TABLE_NAME ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($colRows as $cr) {
            $t = (string)$cr['TABLE_NAME'];
            $c = (string)$cr['COLUMN_NAME'];
            $eng = (string)($cr['ENGINE_NAME'] ?? 'INNODB');
            if (preg_match('/^[a-zA-Z0-9_]+$/', $t) && preg_match('/^[a-zA-Z0-9_]+$/', $c)) {
                $targets[] = [$t, $c, $eng];
            }
        }

        // Tambahkan tabel BPJS dengan kolom alias `nomr`
        $aliasRows = $pdo->query(
            "SELECT c.TABLE_NAME, c.COLUMN_NAME, UPPER(COALESCE(t.ENGINE, '')) AS ENGINE_NAME
             FROM information_schema.COLUMNS c
             INNER JOIN information_schema.TABLES t
               ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND t.TABLE_TYPE = 'BASE TABLE'
               AND UPPER(COALESCE(t.ENGINE, 'INNODB')) IN ('INNODB', 'MYISAM', 'ARIA', 'MEMORY')
               AND c.TABLE_NAME IN ('bridging_sep', 'bridging_sep_internal')
               AND c.COLUMN_NAME = 'nomr'
               AND (c.EXTRA IS NULL OR UPPER(c.EXTRA) NOT LIKE '%GENERATED%')"
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($aliasRows as $ar) {
            $t = (string)$ar['TABLE_NAME'];
            $c = (string)$ar['COLUMN_NAME'];
            $eng = (string)($ar['ENGINE_NAME'] ?? 'INNODB');
            if (preg_match('/^[a-zA-Z0-9_]+$/', $t) && preg_match('/^[a-zA-Z0-9_]+$/', $c)) {
                $targets[] = [$t, $c, $eng];
            }
        }

        $affectedTables = [];
        $totalRowsMoved = 0;
        $biodataUpdated = [];
        $currentStep = 'inisialisasi transaksi';

        $pdo->beginTransaction();
        try {
            // 1. Pindahkan seluruh baris dari sourceRm -> targetRm lintas semua tabel
            foreach ($targets as [$tbl, $col, $eng]) {
                $currentStep = "cek data tabel {$tbl}.{$col}";
                $stCount = $pdo->prepare("SELECT COUNT(*) FROM `{$tbl}` WHERE `{$col}` = ?");
                $stCount->execute([$sourceRm]);
                $beforeCnt = (int)$stCount->fetchColumn();
                if ($beforeCnt === 0) {
                    continue;
                }

                $currentStep = "pindah data tabel {$tbl}.{$col}";
                $useSavepoint = ($eng === 'INNODB' || $eng === '');
                if ($useSavepoint) {
                    try {
                        $pdo->exec("SAVEPOINT sp_merge_tbl");
                    } catch (\Throwable $spErr) {
                        $useSavepoint = false;
                    }
                }

                $moved = 0;
                $skipped = 0;
                try {
                    $stUpd = $pdo->prepare("UPDATE `{$tbl}` SET `{$col}` = ? WHERE `{$col}` = ?");
                    $stUpd->execute([$targetRm, $sourceRm]);
                    $moved = $stUpd->rowCount();
                    if ($useSavepoint) {
                        try {
                            $pdo->exec("RELEASE SAVEPOINT sp_merge_tbl");
                        } catch (\Throwable $spRelErr) {
                        }
                    }
                } catch (\PDOException $pe) {
                    if ($useSavepoint) {
                        try {
                            $pdo->exec("ROLLBACK TO SAVEPOINT sp_merge_tbl");
                            $pdo->exec("RELEASE SAVEPOINT sp_merge_tbl");
                        } catch (\Throwable $spRbErr) {
                        }
                    }
                    $sqlState = (string)($pe->errorInfo[0] ?? $pe->getCode());
                    if ($sqlState === '23000') {
                        // Tabel 1-to-1 / unique key bentrok dengan baris yang sudah ada di targetRm
                        $stIgn = $pdo->prepare("UPDATE IGNORE `{$tbl}` SET `{$col}` = ? WHERE `{$col}` = ?");
                        $stIgn->execute([$targetRm, $sourceRm]);
                        $moved = $stIgn->rowCount();
                        $skipped = max(0, $beforeCnt - $moved);
                    } else {
                        throw $pe;
                    }
                }

                $totalRowsMoved += $moved;
                $affectedTables[] = [
                    'table'   => $tbl,
                    'column'  => $col,
                    'found'   => $beforeCnt,
                    'moved'   => $moved,
                    'skipped' => $skipped,
                ];
            }

            // 2. Opsional: lengkapi field biodata kosong di RM Utama dari RM Sumber
            if ($fillEmptyBiodata) {
                $currentStep = 'lengkapi biodata kosong di tabel pasien';
                $fillable = self::computeFillableBiodata($sourceRow, $targetRow);
                if (!empty($fillable)) {
                    $setParts = [];
                    $updVals = [];
                    foreach ($fillable as $f) {
                        $colName = $f['field'];
                        if (!preg_match('/^[a-zA-Z0-9_]+$/', $colName)) {
                            continue;
                        }
                        $setParts[] = "`{$colName}` = ?";
                        $updVals[] = $f['value'];
                        $biodataUpdated[$colName] = $f['value'];
                    }
                    if (!empty($setParts)) {
                        $updVals[] = $targetRm;
                        $sqlUpd = "UPDATE `pasien` SET " . implode(', ', $setParts) . " WHERE `no_rkm_medis` = ?";
                        $pdo->prepare($sqlUpd)->execute($updVals);
                    }
                }
            }

            // 3. Rekam log penggabungan (sekaligus menandai sourceRm sebagai soft-archived)
            $currentStep = 'simpan log mlite_pasien_merge_log';
            $userId = (int)($_SESSION['mlite_user'] ?? 0);
            $actorUsername = mb_substr((string)$core->getUserInfo('username', $userId), 0, 100);
            $actorName = mb_substr((string)($core->getUserInfo('fullname', $userId) ?: $actorUsername), 0, 150);
            $actorRole = mb_substr((string)$core->getUserInfo('role', $userId), 0, 50);
            $ipAddr = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);

            $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
            $affectedJson = json_encode($affectedTables, $jsonFlags);
            $sourceJson = json_encode($sourceRow, $jsonFlags);
            $targetJson = json_encode($targetRow, $jsonFlags);

            $stLog = $pdo->prepare(
                "INSERT INTO `mlite_pasien_merge_log` (
                    source_no_rkm_medis, target_no_rkm_medis, source_nm_pasien, target_nm_pasien,
                    reason, fill_empty_biodata, total_rows_moved, affected_tables_json,
                    source_snapshot_json, target_before_json,
                    merged_by_username, merged_by_name, merged_by_role, ip_address, merged_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stLog->execute([
                $sourceRm,
                $targetRm,
                mb_substr((string)($sourceRow['nm_pasien'] ?? ''), 0, 100),
                mb_substr((string)($targetRow['nm_pasien'] ?? ''), 0, 100),
                $reason,
                $fillEmptyBiodata ? 1 : 0,
                $totalRowsMoved,
                $affectedJson !== false ? $affectedJson : '[]',
                $sourceJson !== false ? $sourceJson : '{}',
                $targetJson !== false ? $targetJson : '{}',
                $actorUsername,
                $actorName,
                $actorRole,
                $ipAddr,
                date('Y-m-d H:i:s'),
            ]);
            $logId = (int)$pdo->lastInsertId();

            $currentStep = 'commit transaksi';
            $pdo->commit();

            return [
                'status'           => 'success',
                'message'          => 'Rekam medis ' . $sourceRm . ' berhasil digabung ke ' . $targetRm . ' (' . $totalRowsMoved . ' baris riwayat dipindahkan).',
                'log_id'           => $logId,
                'source_rm'        => $sourceRm,
                'target_rm'        => $targetRm,
                'total_rows_moved' => $totalRowsMoved,
                'affected_tables'  => $affectedTables,
                'biodata_updated'  => $biodataUpdated,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable $rbErr) {
                }
            }
            $errDetail = trim($e->getMessage());
            error_log('[PasienMergeService::executeMerge] (' . $currentStep . ') ' . $errDetail);
            return [
                'status'  => 'error',
                'message' => 'Gagal menggabungkan rekam medis pada tahap [' . $currentStep . ']: ' . $errDetail . ' Seluruh perubahan telah dibatalkan (rollback).',
            ];
        }
    }
}

<?php
namespace Systems\Lib;

/**
 * Shared helper for SOAP Revision & Soft-Delete (Void) Audit Trail
 * Implements Option B (15-minute typo grace period for same creator before TTE/checkout).
 */
class SoapRevision
{
    const GRACE_PERIOD_SECONDS = 900; // 15 menit

    private static $schemaChecked = false;

    public static function fieldLabels()
    {
        return [
            'tensi'         => 'Tensi (mmHg)',
            'suhu_tubuh'    => 'Suhu Tubuh (°C)',
            'nadi'          => 'Nadi (/mnt)',
            'respirasi'     => 'Respirasi (/mnt)',
            'spo2'          => 'SpO2 (%)',
            'gcs'           => 'GCS (E,V,M)',
            'tinggi'        => 'Tinggi Badan (cm)',
            'berat'         => 'Berat Badan (kg)',
            'lingkar_perut' => 'Lingkar Perut (cm)',
            'kesadaran'     => 'Kesadaran',
            'alergi'        => 'Alergi',
            'keluhan'       => 'Subyektif (Keluhan)',
            'pemeriksaan'   => 'Obyektif (Pemeriksaan)',
            'penilaian'     => 'Assesment (Penilaian/Diagnosa)',
            'rtl'           => 'Plan (Rencana/RTL)',
            'instruksi'     => 'Instruksi',
            'evaluasi'      => 'Evaluasi',
            'kasus_poli'    => 'Jenis Kasus Poli',
        ];
    }

    public static function ensureSchema(\PDO $pdo)
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `mlite_rme_revisions` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `module` varchar(40) NOT NULL DEFAULT 'rawat_jalan',
                  `table_name` varchar(64) NOT NULL,
                  `record_key` varchar(120) NOT NULL,
                  `no_rawat` varchar(30) NOT NULL,
                  `no_rkm_medis` varchar(20) DEFAULT NULL,
                  `form_code` varchar(40) NOT NULL DEFAULT 'SOAP_RALAN',
                  `revision_no` int(11) NOT NULL DEFAULT '1',
                  `action_type` enum('AMEND','VOID') NOT NULL DEFAULT 'AMEND',
                  `is_grace_period` tinyint(1) NOT NULL DEFAULT '0',
                  `reason` varchar(255) NOT NULL,
                  `changed_fields_json` longtext,
                  `snapshot_before_json` longtext,
                  `snapshot_after_json` longtext,
                  `tte_superseded_hash` varchar(64) DEFAULT NULL,
                  `actor_username` varchar(50) NOT NULL,
                  `actor_name` varchar(120) DEFAULT NULL,
                  `actor_role` varchar(40) DEFAULT NULL,
                  `ip_address` varchar(45) DEFAULT NULL,
                  `created_at` datetime NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_tbl_key` (`table_name`,`record_key`),
                  KEY `idx_no_rawat` (`no_rawat`),
                  KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $auditCols = [
                'entry_created_at' => 'DATETIME NULL DEFAULT NULL',
                'last_revised_at'  => 'DATETIME NULL DEFAULT NULL',
                'revision_count'   => 'INT(11) NOT NULL DEFAULT 0',
                'deleted_at'       => 'DATETIME NULL DEFAULT NULL',
                'deleted_by'       => 'VARCHAR(50) NULL DEFAULT NULL',
                'void_reason'      => 'VARCHAR(255) NULL DEFAULT NULL',
            ];
            foreach (['pemeriksaan_ralan', 'pemeriksaan_ranap'] as $tbl) {
                $existing = $pdo->query("SHOW COLUMNS FROM `{$tbl}`")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($auditCols as $col => $def) {
                    if (!in_array($col, $existing, true)) {
                        $pdo->exec("ALTER TABLE `{$tbl}` ADD COLUMN `{$col}` {$def}");
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('SoapRevision ensureSchema error: ' . $e->getMessage());
        }
    }

    public static function normalizeTime($jam)
    {
        $jam = trim((string)$jam);
        if (preg_match('/^\d{2}:\d{2}$/', $jam)) {
            return $jam . ':00';
        }
        return $jam;
    }

    public static function buildRecordKey($noRawat, $tglPerawatan, $jamRawat)
    {
        return trim((string)$noRawat) . '|' . trim((string)$tglPerawatan) . '|' . self::normalizeTime($jamRawat);
    }

    public static function buildTteRefId($noRawat, $tglPerawatan, $jamRawat)
    {
        return str_replace('/', '', trim((string)$noRawat))
            . str_replace('-', '', trim((string)$tglPerawatan))
            . str_replace(':', '', self::normalizeTime($jamRawat));
    }

    public static function findActiveTte(\PDO $pdo, $table, $noRawat, $tglPerawatan, $jamRawat)
    {
        $refType = ($table === 'pemeriksaan_ranap') ? 'soap_ranap' : 'soap_ralan';
        $refId = self::buildTteRefId($noRawat, $tglPerawatan, $jamRawat);
        try {
            $stmt = $pdo->prepare("SELECT * FROM mlite_esignatures WHERE ref_type IN (?, 'cppt_verifikasi') AND ref_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$refType, $refId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function checkGracePeriod(\PDO $pdo, $table, array $existingRow, $actorUsername, $effectiveCreatorNip = null)
    {
        $creatorNip = trim((string)($existingRow['nip'] ?? ''));
        $actor = trim((string)$actorUsername);
        $sameCreator = ($actor !== '' && ($creatorNip === $actor || ($effectiveCreatorNip !== null && $creatorNip === $effectiveCreatorNip)));

        $createdStr = !empty($existingRow['entry_created_at'])
            ? $existingRow['entry_created_at']
            : trim(($existingRow['tgl_perawatan'] ?? '') . ' ' . ($existingRow['jam_rawat'] ?? ''));

        $createdTs = strtotime($createdStr);
        $nowTs = time();
        $elapsedSeconds = ($createdTs !== false) ? max(0, $nowTs - $createdTs) : (self::GRACE_PERIOD_SECONDS + 1);
        $elapsedMinutes = (int)floor($elapsedSeconds / 60);
        $minutesLeft = max(0, (int)ceil((self::GRACE_PERIOD_SECONDS - $elapsedSeconds) / 60));

        // Cek status TTE
        $tte = self::findActiveTte(
            $pdo,
            $table,
            $existingRow['no_rawat'] ?? '',
            $existingRow['tgl_perawatan'] ?? '',
            $existingRow['jam_rawat'] ?? ''
        );
        $isSigned = !empty($tte);

        // Cek status billing / pulang pasien (hanya kunci grace period jika sudah bayar / lunas,
        // agar setting dokter_ralan.set_sudah='ya' saat simpan pertama tidak memutus masa tenggang 15 menit)
        $isCheckedOut = false;
        try {
            $stmt = $pdo->prepare("SELECT status_bayar FROM reg_periksa WHERE no_rawat = ? LIMIT 1");
            $stmt->execute([$existingRow['no_rawat'] ?? '']);
            $reg = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($reg && ($reg['status_bayar'] ?? '') === 'Sudah Bayar') {
                $isCheckedOut = true;
            }
        } catch (\Throwable $e) {
            $isCheckedOut = false;
        }

        // Sudah pernah ada revisi formal sebelumnya -> tetap wajib alasan
        $hasPriorFormalRevision = ((int)($existingRow['revision_count'] ?? 0) > 0);

        $whyParts = [];
        if (!$sameCreator) {
            $whyParts[] = 'diubah oleh PPA/akun berbeda';
        }
        if ($elapsedSeconds > self::GRACE_PERIOD_SECONDS) {
            $whyParts[] = 'telah melewati masa tenggang koreksi 15 menit (' . $elapsedMinutes . ' menit sejak entri)';
        }
        if ($isSigned) {
            $whyParts[] = 'catatan sudah ditandatangani secara elektronik (TTE)';
        }
        if ($isCheckedOut) {
            $whyParts[] = 'status kunjungan pasien sudah selesai / lunas bayar';
        }
        if ($hasPriorFormalRevision) {
            $whyParts[] = 'catatan sudah pernah dikoreksi sebelumnya';
        }

        $inGrace = empty($whyParts);

        return [
            'in_grace'        => $inGrace,
            'needs_reason'    => !$inGrace,
            'why'             => $inGrace ? 'Dalam masa koreksi langsung (<= 15 menit)' : 'Koreksi wajib alasan karena ' . implode(', ', $whyParts),
            'elapsed_minutes' => $elapsedMinutes,
            'minutes_left'    => $inGrace ? $minutesLeft : 0,
            'tte_hash'        => $tte['signature_hash'] ?? null,
        ];
    }

    public static function computeDiff(array $oldRow, array $newRow)
    {
        $labels = self::fieldLabels();
        $diff = [];
        foreach ($labels as $field => $label) {
            if (!array_key_exists($field, $newRow)) {
                continue;
            }
            $oldVal = trim((string)($oldRow[$field] ?? ''));
            $newVal = trim((string)($newRow[$field] ?? ''));
            if ($oldVal !== $newVal) {
                $diff[$field] = [
                    'label' => $label,
                    'old'   => $oldVal === '' ? '-' : $oldVal,
                    'new'   => $newVal === '' ? '-' : $newVal,
                ];
            }
        }
        return $diff;
    }

    private static function getValidTableColumns(\PDO $pdo, $table)
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_COLUMN);
            $cache[$table] = array_values(array_diff($cols, [
                'entry_created_at', 'last_revised_at', 'revision_count',
                'deleted_at', 'deleted_by', 'void_reason'
            ]));
        }
        return $cache[$table];
    }

    private static function getActorMeta($core, $overrideUsername = null)
    {
        $username = $overrideUsername ?: $core->getUserInfo('username', null, true);
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
     * Save or Amend SOAP record with Option B (15-minute grace period).
     */
    public static function saveSoap($core, $module, $table, array $input, array $opts = [])
    {
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        if (!in_array($table, ['pemeriksaan_ralan', 'pemeriksaan_ranap'], true)) {
            return ['status' => 'error', 'message' => 'Tabel SOAP tidak valid.'];
        }

        $noRawat = trim((string)($input['no_rawat'] ?? ''));
        $tglPerawatan = trim((string)($input['tgl_perawatan'] ?? date('Y-m-d')));
        $jamRawat = self::normalizeTime($input['jam_rawat'] ?? date('H:i:s'));

        if ($noRawat === '' || $tglPerawatan === '' || $jamRawat === '') {
            return ['status' => 'error', 'message' => 'Parameter no_rawat, tanggal, dan jam pemeriksaan wajib diisi.'];
        }

        $origTgl = trim((string)($opts['original_tgl_perawatan'] ?? $input['original_tgl_perawatan'] ?? $tglPerawatan));
        $origJam = self::normalizeTime($opts['original_jam_rawat'] ?? $input['original_jam_rawat'] ?? $jamRawat);
        $isEditFlag = !empty($opts['is_edit']) || !empty($input['is_edit']) || (!empty($input['original_tgl_perawatan']) && !empty($input['original_jam_rawat']));
        $reason = trim((string)($opts['revision_reason'] ?? $input['revision_reason'] ?? ''));

        $actor = self::getActorMeta($core, $opts['actor_username'] ?? null);
        $userId = $_SESSION['mlite_user'] ?? 0;
        $isAdmin = ($actor['role'] === 'admin' || $userId == 1);

        $validCols = self::getValidTableColumns($pdo, $table);

        // Normalize payload fields filtered to table columns
        $cleanData = [];
        foreach ($validCols as $col) {
            if (array_key_exists($col, $input)) {
                $cleanData[$col] = $input[$col];
            }
        }
        $cleanData['no_rawat'] = $noRawat;
        $cleanData['tgl_perawatan'] = $tglPerawatan;
        $cleanData['jam_rawat'] = $jamRawat;

        // Provide safe defaults for NOT NULL columns without defaults when missing
        $nonNullDefaults = [
            'tensi' => '-',
            'spo2' => '',
            'kesadaran' => 'Compos Mentis',
            'rtl' => '-',
            'penilaian' => '-',
            'instruksi' => '-',
            'evaluasi' => '-',
            'nip' => $actor['username'],
        ];
        foreach ($nonNullDefaults as $col => $defVal) {
            if (in_array($col, $validCols, true) && !isset($cleanData[$col])) {
                $cleanData[$col] = $defVal;
            }
        }

        // Lookup existing row by original key first (if editing), fallback to target key
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE no_rawat = ? AND tgl_perawatan = ? AND jam_rawat = ? LIMIT 1");
        $stmt->execute([$noRawat, $origTgl, $origJam]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing && ($origTgl !== $tglPerawatan || $origJam !== $jamRawat)) {
            $stmt->execute([$noRawat, $tglPerawatan, $jamRawat]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        // If creating new
        if (!$existing) {
            if ($isEditFlag) {
                return ['status' => 'error', 'message' => 'Data SOAP asli tidak ditemukan untuk dikoreksi.'];
            }
            $now = date('Y-m-d H:i:s');
            $cleanData['entry_created_at'] = $now;
            $cleanData['revision_count'] = 0;

            $cols = array_keys($cleanData);
            $colSql = implode(',', array_map(function ($c) { return "`{$c}`"; }, $cols));
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO `{$table}` ({$colSql}) VALUES ({$placeholders})";
            $binds = array_values($cleanData);

            try {
                $ins = $pdo->prepare($sql);
                $ins->execute($binds);
                self::logQuery($core, $sql, $binds);
                return [
                    'status' => 'success',
                    'mode' => 'create',
                    'record_key' => self::buildRecordKey($noRawat, $tglPerawatan, $jamRawat),
                    'message' => 'Catatan SOAP berhasil disimpan.',
                ];
            } catch (\PDOException $e) {
                return ['status' => 'error', 'message' => 'Gagal menyimpan SOAP: ' . $e->getMessage()];
            }
        }

        // Existing row found
        if (!empty($existing['deleted_at'])) {
            return ['status' => 'error', 'message' => 'Catatan SOAP ini telah dibatalkan (VOID) dan tidak dapat diedit lagi.'];
        }

        // Authorization check
        $effectiveNip = trim((string)($cleanData['nip'] ?? ''));
        if (!$isAdmin && trim((string)$existing['nip']) !== $actor['username'] && trim((string)$existing['nip']) !== $effectiveNip) {
            return ['status' => 'error', 'message' => 'Anda tidak memiliki izin mengubah catatan milik PPA lain.'];
        }

        // Keep original creator NIP & original timestamp unless key deliberately kept same
        $cleanData['nip'] = $existing['nip'];
        $cleanData['tgl_perawatan'] = $existing['tgl_perawatan'];
        $cleanData['jam_rawat'] = $existing['jam_rawat'];

        $diff = self::computeDiff($existing, $cleanData);
        if (empty($diff)) {
            return [
                'status' => 'success',
                'mode' => 'noop',
                'message' => 'Tidak ada perubahan isi catatan SOAP.',
            ];
        }

        $grace = self::checkGracePeriod($pdo, $table, $existing, $actor['username'], $effectiveNip);

        if ($grace['needs_reason'] && $reason === '') {
            return [
                'status'          => 'needs_reason',
                'action'          => 'amend',
                'why'             => $grace['why'],
                'elapsed_minutes' => $grace['elapsed_minutes'],
                'changed_fields'  => $diff,
                'record_key'      => self::buildRecordKey($existing['no_rawat'], $existing['tgl_perawatan'], $existing['jam_rawat']),
                'message'         => $grace['why'] . '. Wajib mengisi alasan koreksi catatan SOAP.',
            ];
        }

        $now = date('Y-m-d H:i:s');
        $isGrace = $grace['in_grace'] ? 1 : 0;
        $newRevCount = $isGrace ? (int)($existing['revision_count'] ?? 0) : ((int)($existing['revision_count'] ?? 0) + 1);
        $formCode = ($table === 'pemeriksaan_ranap') ? 'SOAP_RANAP' : 'SOAP_RALAN';
        $recordKey = self::buildRecordKey($existing['no_rawat'], $existing['tgl_perawatan'], $existing['jam_rawat']);

        // Merge snapshot after
        $snapshotAfter = $existing;
        foreach ($cleanData as $k => $v) {
            $snapshotAfter[$k] = $v;
        }
        $snapshotAfter['revision_count'] = $newRevCount;
        if (!$isGrace) {
            $snapshotAfter['last_revised_at'] = $now;
        }

        try {
            $pdo->beginTransaction();

            // 1. Update main SOAP table
            $updateData = $cleanData;
            unset($updateData['no_rawat'], $updateData['tgl_perawatan'], $updateData['jam_rawat']);
            if (empty($existing['entry_created_at'])) {
                $updateData['entry_created_at'] = trim($existing['tgl_perawatan'] . ' ' . $existing['jam_rawat']);
            }
            if (!$isGrace) {
                $updateData['last_revised_at'] = $now;
                $updateData['revision_count'] = $newRevCount;
            }

            $setParts = [];
            $updBinds = [];
            foreach ($updateData as $col => $val) {
                $setParts[] = "`{$col}` = ?";
                $updBinds[] = $val;
            }
            $updBinds[] = $existing['no_rawat'];
            $updBinds[] = $existing['tgl_perawatan'];
            $updBinds[] = $existing['jam_rawat'];

            $updSql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE no_rawat = ? AND tgl_perawatan = ? AND jam_rawat = ?";
            $pdo->prepare($updSql)->execute($updBinds);
            self::logQuery($core, $updSql, $updBinds);

            // 2. Record revision entry
            $revReason = $reason !== '' ? $reason : 'Perbaikan ketik langsung dalam masa tenggang (<= 15 menit)';
            $revSql = "INSERT INTO `mlite_rme_revisions`
                (module, table_name, record_key, no_rawat, no_rkm_medis, form_code, revision_no, action_type, is_grace_period, reason, changed_fields_json, snapshot_before_json, snapshot_after_json, tte_superseded_hash, actor_username, actor_name, actor_role, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'AMEND', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $revBinds = [
                $module,
                $table,
                $recordKey,
                $existing['no_rawat'],
                self::getNoRm($pdo, $existing['no_rawat']),
                $formCode,
                $newRevCount,
                $isGrace,
                $revReason,
                json_encode($diff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

            $pdo->commit();

            return [
                'status'       => 'success',
                'mode'         => $isGrace ? 'grace_edit' : 'amend',
                'revision_no'  => $newRevCount,
                'record_key'   => $recordKey,
                'in_grace'     => (bool)$isGrace,
                'minutes_left' => $grace['minutes_left'],
                'message'      => $isGrace
                    ? 'Perubahan SOAP disimpan (koreksi langsung dalam masa tenggang 15 menit).'
                    : "Koreksi SOAP berhasil disimpan (Revisi #{$newRevCount}) dengan jejak audit.",
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 'error', 'message' => 'Gagal menyimpan koreksi SOAP: ' . $e->getMessage()];
        }
    }

    /**
     * Soft-delete (VOID) SOAP record instead of permanent DELETE.
     */
    public static function voidSoap($core, $module, $table, $noRawat, $tglPerawatan, $jamRawat, $reason = '', $actorOverride = null)
    {
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        if (!in_array($table, ['pemeriksaan_ralan', 'pemeriksaan_ranap'], true)) {
            return ['status' => 'error', 'message' => 'Tabel SOAP tidak valid.'];
        }

        $noRawat = trim((string)$noRawat);
        $tglPerawatan = trim((string)$tglPerawatan);
        $jamRawat = self::normalizeTime($jamRawat);
        $reason = trim((string)$reason);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE no_rawat = ? AND tgl_perawatan = ? AND jam_rawat = ? LIMIT 1");
        $stmt->execute([$noRawat, $tglPerawatan, $jamRawat]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            return ['status' => 'error', 'message' => 'Catatan SOAP tidak ditemukan.'];
        }
        if (!empty($existing['deleted_at'])) {
            return ['status' => 'error', 'message' => 'Catatan SOAP ini sudah berstatus dibatalkan (VOID).'];
        }

        $actor = self::getActorMeta($core, $actorOverride);
        $userId = $_SESSION['mlite_user'] ?? 0;
        $isAdmin = ($actor['role'] === 'admin' || $userId == 1);

        if (!$isAdmin && trim((string)$existing['nip']) !== $actor['username']) {
            return ['status' => 'error', 'message' => 'Anda tidak berwenang membatalkan catatan milik PPA lain.'];
        }

        $grace = self::checkGracePeriod($pdo, $table, $existing, $actor['username']);
        if ($grace['needs_reason'] && $reason === '') {
            return [
                'status'  => 'needs_reason',
                'action'  => 'void',
                'why'     => $grace['why'],
                'message' => $grace['why'] . '. Wajib mengisi alasan pembatalan catatan SOAP.',
            ];
        }

        $now = date('Y-m-d H:i:s');
        $isGrace = $grace['in_grace'] ? 1 : 0;
        $finalReason = $reason !== '' ? $reason : 'Dibatalkan oleh pembuat dalam masa tenggang (<= 15 menit)';
        $recordKey = self::buildRecordKey($existing['no_rawat'], $existing['tgl_perawatan'], $existing['jam_rawat']);
        $formCode = ($table === 'pemeriksaan_ranap') ? 'SOAP_RANAP' : 'SOAP_RALAN';
        $revNo = (int)($existing['revision_count'] ?? 0) + 1;

        try {
            $pdo->beginTransaction();

            $updSql = "UPDATE `{$table}` SET deleted_at = ?, deleted_by = ?, void_reason = ? WHERE no_rawat = ? AND tgl_perawatan = ? AND jam_rawat = ?";
            $updBinds = [$now, $actor['username'], $finalReason, $existing['no_rawat'], $existing['tgl_perawatan'], $existing['jam_rawat']];
            $pdo->prepare($updSql)->execute($updBinds);
            self::logQuery($core, $updSql, $updBinds);

            $revSql = "INSERT INTO `mlite_rme_revisions`
                (module, table_name, record_key, no_rawat, no_rkm_medis, form_code, revision_no, action_type, is_grace_period, reason, changed_fields_json, snapshot_before_json, snapshot_after_json, tte_superseded_hash, actor_username, actor_name, actor_role, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'VOID', ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)";
            $revBinds = [
                $module,
                $table,
                $recordKey,
                $existing['no_rawat'],
                self::getNoRm($pdo, $existing['no_rawat']),
                $formCode,
                $revNo,
                $isGrace,
                $finalReason,
                json_encode(['_status' => ['label' => 'Status Catatan', 'old' => 'Aktif', 'new' => 'DIBATALKAN (VOID)']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
                'message' => 'Catatan SOAP dibatalkan (VOID). Riwayat asli tetap tersimpan untuk jejak audit.',
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 'error', 'message' => 'Gagal membatalkan catatan SOAP: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch SOAP rows cleanly (supporting both active-only and including VOID rows).
     */
    public static function fetchSoapRows(\PDO $pdo, $table, $noRawat, $includeVoided = true, $orderDir = 'ASC')
    {
        self::ensureSchema($pdo);
        if (!in_array($table, ['pemeriksaan_ralan', 'pemeriksaan_ranap'], true)) {
            return [];
        }
        $dir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $whereVoid = $includeVoided ? '' : ' AND deleted_at IS NULL';
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE no_rawat = ?{$whereVoid} ORDER BY tgl_perawatan {$dir}, jam_rawat {$dir}");
        $stmt->execute([$noRawat]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Enrich SOAP rows with formal revision badges, per-field diffs, Option B grace status, & void flags.
     */
    public static function enrichRows($core, $table, array $rows, $actorUsername = '')
    {
        if (empty($rows)) {
            return [];
        }
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        if ($actorUsername === '') {
            $actorUsername = $core->getUserInfo('username', null, true);
        }

        $noRawats = array_values(array_unique(array_filter(array_column($rows, 'no_rawat'))));
        $revByKey = [];
        if (!empty($noRawats)) {
            $ph = implode(',', array_fill(0, count($noRawats), '?'));
            $sql = "SELECT * FROM mlite_rme_revisions
                    WHERE table_name = ? AND no_rawat IN ({$ph})
                      AND ( (action_type = 'AMEND' AND is_grace_period = 0) OR action_type = 'VOID' )
                    ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$table], $noRawats));
            while ($rev = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $revByKey[$rev['record_key']][] = $rev;
            }
        }

        foreach ($rows as &$row) {
            $key = self::buildRecordKey($row['no_rawat'] ?? '', $row['tgl_perawatan'] ?? '', $row['jam_rawat'] ?? '');
            $row['table_name'] = $table;
            $row['record_key'] = $key;
            $row['is_voided'] = !empty($row['deleted_at']);
            $row['void_info'] = $row['is_voided'] ? [
                'deleted_at'  => $row['deleted_at'],
                'deleted_by'  => $row['deleted_by'] ?? '',
                'void_reason' => $row['void_reason'] ?? '-',
            ] : null;

            $revs = $revByKey[$key] ?? [];
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

            $row['revision_count'] = count($formalAmends);
            $row['has_revisions'] = $row['revision_count'] > 0;
            $row['last_revised_by'] = $lastAmend ? ($lastAmend['actor_name'] ?: $lastAmend['actor_username']) : '';
            $row['last_revised_by_username'] = $lastAmend ? (string)($lastAmend['actor_username'] ?? '') : '';
            $row['last_revised_at_fmt'] = ($lastAmend && !empty($lastAmend['created_at']))
                ? date('d-m-Y H:i', strtotime($lastAmend['created_at']))
                : (!empty($row['last_revised_at']) ? date('d-m-Y H:i', strtotime($row['last_revised_at'])) : '');
            $row['last_revised_reason'] = $lastAmend['reason'] ?? '';
            $row['changed_fields_map'] = $changedMap;
            $row['changed_fields_json'] = json_encode($changedMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Check Option B grace period status for UI hint
            $grace = self::checkGracePeriod($pdo, $table, $row, $actorUsername);
            $row['in_grace'] = $grace['in_grace'];
            $row['needs_reason'] = $grace['needs_reason'];
            $row['minutes_left'] = $grace['minutes_left'];
            $row['grace_why'] = $grace['why'];

            // Format audit summary for CPPT PDF cetak
            $revSummaryLines = [];
            foreach ($formalAmends as $rev) {
                $cf = json_decode($rev['changed_fields_json'] ?? '[]', true);
                $parts = [];
                if (is_array($cf)) {
                    foreach ($cf as $info) {
                        $oldShort = mb_strimwidth((string)($info['old'] ?? '-'), 0, 50, '...');
                        $newShort = mb_strimwidth((string)($info['new'] ?? '-'), 0, 50, '...');
                        $parts[] = ($info['label'] ?? '') . ': "' . $oldShort . '" -> "' . $newShort . '"';
                    }
                }
                $revSummaryLines[] = [
                    'revision_no' => (int)$rev['revision_no'],
                    'created_at'  => date('d-m-Y H:i', strtotime($rev['created_at'])),
                    'actor_name'  => $rev['actor_name'] ?: $rev['actor_username'],
                    'reason'      => $rev['reason'],
                    'changes'     => implode('; ', $parts),
                ];
            }
            $row['revisions_summary'] = $revSummaryLines;

            // TTE validity after correction (selalu ambil TTE terbaru & siapkan sign_ref_id untuk TTE ulang)
            $row['is_tte_outdated'] = false;
            $row['can_resign_tte'] = false;
            if (empty($row['sign_ref_id'])) {
                $row['sign_ref_id'] = self::buildTteRefId($row['no_rawat'] ?? '', $row['tgl_perawatan'] ?? '', $row['jam_rawat'] ?? '');
            }
            if (!empty($row['is_signed'])) {
                $tte = self::findActiveTte($pdo, $table, $row['no_rawat'] ?? '', $row['tgl_perawatan'] ?? '', $row['jam_rawat'] ?? '');
                if ($tte) {
                    if (!empty($tte['signature_hash'])) {
                        $row['signature_hash'] = $tte['signature_hash'];
                    }
                    if (!empty($row['last_revised_at']) && !empty($tte['signed_at']) && strtotime($row['last_revised_at']) > strtotime($tte['signed_at'])) {
                        $row['is_tte_outdated'] = true;
                    }
                }
            }
            if ($row['is_tte_outdated'] && empty($row['is_voided'])) {
                $rowNip = trim((string)($row['nip'] ?? ''));
                $revActor = trim((string)($row['last_revised_by_username'] ?? ''));
                $actUser = trim((string)$actorUsername);
                $effNip = $effectiveCreatorNip !== null ? trim((string)$effectiveCreatorNip) : '';
                $row['can_resign_tte'] = (
                    ($actUser !== '' && ($actUser === $rowNip || $actUser === $revActor)) ||
                    ($effNip !== '' && ($effNip === $rowNip || $effNip === $revActor))
                );
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Endpoint JSON returning complete chronological revision timeline (v1 -> v2 -> ... -> VOID)
     */
    public static function outputHistoryJson($core)
    {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = $core->db()->pdo();
        self::ensureSchema($pdo);

        $table = trim((string)($_REQUEST['table_name'] ?? 'pemeriksaan_ralan'));
        $recordKey = trim((string)($_REQUEST['record_key'] ?? ''));

        if (!in_array($table, ['pemeriksaan_ralan', 'pemeriksaan_ranap'], true) || $recordKey === '') {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            exit();
        }

        $parts = explode('|', $recordKey);
        $noRawat = $parts[0] ?? '';
        $tgl = $parts[1] ?? '';
        $jam = self::normalizeTime($parts[2] ?? '');
        $normalizedKey = self::buildRecordKey($noRawat, $tgl, $jam);

        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE no_rawat = ? AND tgl_perawatan = ? AND jam_rawat = ? LIMIT 1");
        $stmt->execute([$noRawat, $tgl, $jam]);
        $currentRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$currentRow) {
            echo json_encode(['status' => 'error', 'message' => 'Catatan SOAP tidak ditemukan.']);
            exit();
        }

        $revStmt = $pdo->prepare("SELECT * FROM mlite_rme_revisions WHERE table_name = ? AND record_key = ? ORDER BY id ASC");
        $revStmt->execute([$table, $normalizedKey]);
        $revs = $revStmt->fetchAll(\PDO::FETCH_ASSOC);

        $creatorName = $core->getPegawaiInfo('nama', $currentRow['nip']);
        if (empty($creatorName) || $creatorName === '-') {
            $creatorName = $currentRow['nip'];
        }
        $createdAtStr = !empty($currentRow['entry_created_at'])
            ? $currentRow['entry_created_at']
            : trim($currentRow['tgl_perawatan'] . ' ' . $currentRow['jam_rawat']);

        // Determine initial v1 baseline from first revision's snapshot_before_json, else current row
        $v1Snapshot = $currentRow;
        if (!empty($revs) && !empty($revs[0]['snapshot_before_json'])) {
            $decodedFirst = json_decode($revs[0]['snapshot_before_json'], true);
            if (is_array($decodedFirst)) {
                $v1Snapshot = $decodedFirst;
            }
        }

        $timeline = [];
        $timeline[] = [
            'version'     => 'v1 (Entri Awal)',
            'action_type' => 'CREATE',
            'is_grace'    => false,
            'actor_name'  => $creatorName,
            'actor_role'  => 'Pembuat Catatan (' . $currentRow['nip'] . ')',
            'created_at'  => date('d-m-Y H:i:s', strtotime($createdAtStr)),
            'reason'      => 'Pencatatan awal SOAP pasien',
            'changes'     => [],
            'snapshot_summary' => [
                'S' => $v1Snapshot['keluhan'] ?? '-',
                'O' => $v1Snapshot['pemeriksaan'] ?? '-',
                'A' => $v1Snapshot['penilaian'] ?? '-',
                'P' => $v1Snapshot['rtl'] ?? '-',
            ],
        ];

        $formalIdx = 1;
        foreach ($revs as $rev) {
            $isGrace = ((int)($rev['is_grace_period'] ?? 0) === 1);
            // Hide internal grace-period typo edits from formal external timeline unless admin inspects?
            // Wait: if user wants to see grace edits OR only formal amendments:
            // Show formal AMEND & VOID clearly; skip grace edits or tag them softly?
            // Under Option B, grace edits <=15 min are treated as typo fixes without formal revision counter,
            // so only include formal AMEND (is_grace=0) and VOID in the formal correction timeline.
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
                'version'     => $versionLabel,
                'action_type' => $rev['action_type'],
                'is_grace'    => $isGrace,
                'actor_name'  => $rev['actor_name'] ?: $rev['actor_username'],
                'actor_role'  => $rev['actor_role'] ?: 'PPA',
                'created_at'  => date('d-m-Y H:i:s', strtotime($rev['created_at'])),
                'reason'      => $rev['reason'],
                'changes'     => $changesList,
                'tte_superseded' => !empty($rev['tte_superseded_hash']),
            ];
        }

        echo json_encode([
            'status'      => 'success',
            'table_name'  => $table,
            'record_key'  => $normalizedKey,
            'no_rawat'    => $currentRow['no_rawat'],
            'is_voided'   => !empty($currentRow['deleted_at']),
            'void_reason' => $currentRow['void_reason'] ?? '',
            'timeline'    => $timeline,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}

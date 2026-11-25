<?php

namespace Plugins\Monitoring_Suhu;

use Systems\AdminModule;

class Admin extends AdminModule
{
    private static $tablesReady = false;

    public function navigation()
    {
        if ($this->_isSuperAdmin()) {
            return [
                'Monitoring Suhu & RH' => 'manage',
                'Unit & Hak Akses'     => 'unit',
            ];
        }

        // Hanya tampilkan menu di sidebar jika user non-admin terdaftar minimal di 1 unit aktif
        try {
            $userId = $this->_currentUserId();
            if ($userId > 0) {
                $stmt = $this->db()->pdo()->prepare(
                    "SELECT COUNT(*) FROM mlite_monitoring_suhu_akses a
                     INNER JOIN mlite_monitoring_suhu_unit u ON u.id = a.unit_id AND u.aktif = 1
                     WHERE a.user_id = ?"
                );
                $stmt->execute([$userId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    return [
                        'Monitoring Suhu & RH' => 'manage',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Tabel belum dibuat; sembunyikan untuk non-admin
        }

        return [];
    }

    private function _currentUserId()
    {
        return (int)($_SESSION['mlite_user'] ?? 0);
    }

    private function _isSuperAdmin()
    {
        $userId = $this->_currentUserId();
        if ($userId <= 0) {
            return false;
        }
        $role = strtolower(trim((string)$this->core->getUserInfo('role', $userId)));
        return ($role === 'admin' || $userId === 1);
    }

    private function _isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function _denyAccess($message, $forceAjax = false)
    {
        if ($forceAjax || $this->_isAjax()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $this->notify('failure', $message);
        redirect(url([ADMIN, 'dashboard', 'main']));
        exit();
    }

    private function _ensureTables()
    {
        if (self::$tablesReady) {
            return;
        }
        $pdo = $this->db()->pdo();
        $sqlFile = __DIR__ . '/migration.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = str_replace("\r\n", "\n", file_get_contents($sqlFile));
            $statements = array_filter(array_map('trim', explode(";\n", $sqlContent)));
            foreach ($statements as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
        }
        self::$tablesReady = true;
    }

    private function _getAllowedUnits($onlyInput = false)
    {
        $pdo = $this->db()->pdo();
        if ($this->_isSuperAdmin()) {
            $stmt = $pdo->query(
                "SELECT u.*, 'input' AS hak_akses
                 FROM mlite_monitoring_suhu_unit u
                 WHERE u.aktif = 1
                 ORDER BY u.nama_unit ASC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $userId = $this->_currentUserId();
        $sql = "SELECT u.*, a.hak_akses
                FROM mlite_monitoring_suhu_unit u
                INNER JOIN mlite_monitoring_suhu_akses a ON a.unit_id = u.id
                WHERE u.aktif = 1 AND a.user_id = ?";
        if ($onlyInput) {
            $sql .= " AND a.hak_akses = 'input'";
        }
        $sql .= " ORDER BY u.nama_unit ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _getAllowedTitik(array $allowedUnits, $onlyActive = true)
    {
        if (empty($allowedUnits)) {
            return [];
        }
        $unitMap = [];
        $unitIds = [];
        foreach ($allowedUnits as $u) {
            $uid = (int)$u['id'];
            $unitIds[] = $uid;
            $unitMap[$uid] = $u;
        }

        $pdo = $this->db()->pdo();
        $ph = implode(',', array_fill(0, count($unitIds), '?'));
        $sql = "SELECT r.*, u.kode_unit, u.nama_unit
                FROM mlite_monitoring_suhu_ruangan r
                INNER JOIN mlite_monitoring_suhu_unit u ON u.id = r.unit_id
                WHERE r.unit_id IN ($ph)";
        if ($onlyActive) {
            $sql .= " AND r.aktif = 1";
        }
        $sql .= " ORDER BY u.nama_unit ASC, r.aktif DESC, r.nama_ruangan ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($unitIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $uid = (int)$r['unit_id'];
            $r['hak_akses'] = $unitMap[$uid]['hak_akses'] ?? 'view';
        }
        unset($r);

        return $rows;
    }

    private function _requireModuleAccess($forceAjax = false)
    {
        $this->_ensureTables();
        $units = $this->_getAllowedUnits(false);
        if (!$this->_isSuperAdmin() && empty($units)) {
            $this->_denyAccess(
                'Anda belum memiliki hak akses ke unit manapun pada Monitoring Suhu & Kelembaban. Hubungi Administrator / IT untuk penugasan unit.',
                $forceAjax
            );
        }
        return $units;
    }

    private function _requireAdminAccess($forceAjax = false)
    {
        $this->_ensureTables();
        if (!$this->_isSuperAdmin()) {
            $this->_denyAccess('Hanya Administrator / IT yang dapat mengelola daftar unit dan hak akses pengguna.', $forceAjax);
        }
    }

    private function _assertUnitAccess($unitId, $requireInput = false, $forceAjax = false)
    {
        $unitId = (int)$unitId;
        if ($unitId <= 0) {
            $this->_denyAccess('Unit belum dipilih atau tidak valid.', $forceAjax);
        }
        $allowed = $this->_getAllowedUnits($requireInput);
        foreach ($allowed as $u) {
            if ((int)$u['id'] === $unitId) {
                return $u;
            }
        }
        $msg = $requireInput
            ? 'Anda tidak memiliki hak akses pengisian/pengelolaan pada unit tersebut.'
            : 'Anda tidak memiliki hak akses untuk melihat data unit tersebut.';
        $this->_denyAccess($msg, $forceAjax);
        return null;
    }

    private function _assertTitikAccess($titikId, $requireInput = false, $forceAjax = false, $allowInactive = false)
    {
        $titikId = (int)$titikId;
        if ($titikId <= 0) {
            $this->_denyAccess('Titik ukur belum dipilih atau tidak valid.', $forceAjax);
        }
        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare(
            "SELECT r.*, u.kode_unit, u.nama_unit, u.aktif AS unit_aktif
             FROM mlite_monitoring_suhu_ruangan r
             INNER JOIN mlite_monitoring_suhu_unit u ON u.id = r.unit_id
             WHERE r.id = ?"
        );
        $stmt->execute([$titikId]);
        $titik = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$titik || (int)$titik['unit_aktif'] !== 1 || (!$allowInactive && (int)$titik['aktif'] !== 1)) {
            $this->_denyAccess('Titik ukur tidak ditemukan atau sudah dinonaktifkan.', $forceAjax);
        }
        $unit = $this->_assertUnitAccess((int)$titik['unit_id'], $requireInput, $forceAjax);
        $titik['hak_akses'] = $unit['hak_akses'] ?? 'view';
        return $titik;
    }

    private function _evaluateStatus($suhu, $kelembaban, array $titik)
    {
        $mode = $titik['mode_ukur'] ?? 'keduanya';
        $statusSuhu = 'N/A';
        $statusKel = 'N/A';

        if ($mode === 'suhu' || $mode === 'keduanya') {
            $statusSuhu = 'Normal';
            if ($titik['suhu_min'] !== null && $suhu < (float)$titik['suhu_min']) {
                $statusSuhu = 'Rendah';
            } elseif ($titik['suhu_max'] !== null && $suhu > (float)$titik['suhu_max']) {
                $statusSuhu = 'Tinggi';
            }
        }

        if ($mode === 'kelembaban' || $mode === 'keduanya') {
            $statusKel = 'Normal';
            if ($titik['kelembaban_min'] !== null && $kelembaban < (float)$titik['kelembaban_min']) {
                $statusKel = 'Rendah';
            } elseif ($titik['kelembaban_max'] !== null && $kelembaban > (float)$titik['kelembaban_max']) {
                $statusKel = 'Tinggi';
            }
        }

        $isAlert = (
            in_array($statusSuhu, ['Rendah', 'Tinggi'], true) ||
            in_array($statusKel, ['Rendah', 'Tinggi'], true)
        );

        return [
            'status_suhu'        => $statusSuhu,
            'status_kelembaban'  => $statusKel,
            'status_keseluruhan' => $isAlert ? 'Peringatan' : 'Normal',
        ];
    }

    private function _syncUserModuleAccess($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return;
        }
        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT id, access FROM mlite_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            return;
        }

        $rawAccess = trim((string)($user['access'] ?? ''));
        if ($rawAccess === 'all') {
            return;
        }

        $cntStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM mlite_monitoring_suhu_akses a
             INNER JOIN mlite_monitoring_suhu_unit u ON u.id = a.unit_id AND u.aktif = 1
             WHERE a.user_id = ?"
        );
        $cntStmt->execute([$userId]);
        $hasUnit = ((int)$cntStmt->fetchColumn() > 0);

        $modules = array_values(array_filter(array_map('trim', explode(',', $rawAccess)), function ($m) {
            return $m !== '';
        }));

        $changed = false;
        if ($hasUnit && !in_array('monitoring_suhu', $modules, true)) {
            $modules[] = 'monitoring_suhu';
            $changed = true;
        } elseif (!$hasUnit && in_array('monitoring_suhu', $modules, true)) {
            $modules = array_values(array_filter($modules, function ($m) {
                return $m !== 'monitoring_suhu';
            }));
            $changed = true;
        }

        if ($changed) {
            $upd = $pdo->prepare("UPDATE mlite_users SET access = ? WHERE id = ?");
            $upd->execute([implode(',', $modules), $userId]);
        }
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addJS(url('assets/jscripts/Chart.bundle.min.js'));
    }

    private function _detectShiftNow()
    {
        $hour = (int)date('H');
        if ($hour >= 6 && $hour < 12) {
            return 'Pagi';
        } elseif ($hour >= 12 && $hour < 15) {
            return 'Siang';
        } elseif ($hour >= 15 && $hour < 19) {
            return 'Sore';
        }
        return 'Malam';
    }

    public function anyManage()
    {
        $allowedUnits = $this->_requireModuleAccess(false);
        $this->_addHeaderFiles();

        $isAdmin = $this->_isSuperAdmin();
        $pdo = $this->db()->pdo();

        $tglAwal = trim((string)($_REQUEST['tgl_awal'] ?? date('Y-m-01')));
        $tglAkhir = trim((string)($_REQUEST['tgl_akhir'] ?? date('Y-m-d')));
        $unitFilter = (int)($_REQUEST['unit_id'] ?? 0);
        $ruanganFilter = (int)($_REQUEST['ruangan_id'] ?? 0);
        $statusFilter = trim((string)($_REQUEST['status'] ?? 'Semua'));

        if ($tglAwal !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) {
            $tglAwal = date('Y-m-01');
        }
        if ($tglAkhir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) {
            $tglAkhir = date('Y-m-d');
        }
        if (!in_array($statusFilter, ['Semua', 'Normal', 'Peringatan'], true)) {
            $statusFilter = 'Semua';
        }

        $allowedUnitIds = array_map(function ($u) {
            return (int)$u['id'];
        }, $allowedUnits);

        $inputUnits = array_values(array_filter($allowedUnits, function ($u) {
            return ($u['hak_akses'] ?? '') === 'input';
        }));

        if ($unitFilter === 0 && count($allowedUnits) === 1) {
            $unitFilter = (int)$allowedUnits[0]['id'];
        }
        if ($unitFilter > 0 && !in_array($unitFilter, $allowedUnitIds, true)) {
            $this->_denyAccess('Anda tidak memiliki hak akses ke unit yang diminta.', false);
        }

        $allTitik = $this->_getAllowedTitik($allowedUnits, true);
        $filteredTitik = $allTitik;
        if ($unitFilter > 0) {
            $filteredTitik = array_values(array_filter($allTitik, function ($t) use ($unitFilter) {
                return (int)$t['unit_id'] === $unitFilter;
            }));
        }

        $inputTitik = array_values(array_filter($allTitik, function ($t) {
            return ($t['hak_akses'] ?? '') === 'input';
        }));

        $filteredTitikIds = array_map(function ($t) {
            return (int)$t['id'];
        }, $filteredTitik);

        if ($ruanganFilter === 0 && count($filteredTitik) === 1) {
            $ruanganFilter = (int)$filteredTitik[0]['id'];
        }
        if ($ruanganFilter > 0) {
            $foundTitik = null;
            foreach ($allTitik as $t) {
                if ((int)$t['id'] === $ruanganFilter) {
                    $foundTitik = $t;
                    break;
                }
            }
            if (!$foundTitik) {
                $this->_denyAccess('Anda tidak memiliki hak akses ke titik ukur yang diminta.', false);
            }
            if ($unitFilter > 0 && (int)$foundTitik['unit_id'] !== $unitFilter) {
                $ruanganFilter = 0;
            }
        }

        $selectedTitik = null;
        foreach ($filteredTitik as $t) {
            if ((int)$t['id'] === $ruanganFilter) {
                $selectedTitik = $t;
                break;
            }
        }

        // Ambil pembacaan terakhir & ringkasan hari ini per titik ukur
        $today = date('Y-m-d');
        $titikCards = [];
        $countNormalToday = 0;
        $countAlertToday = 0;
        $countUncheckedToday = 0;

        if (!empty($filteredTitikIds)) {
            $ph = implode(',', array_fill(0, count($filteredTitikIds), '?'));
            $latestStmt = $pdo->prepare(
                "SELECT l.*
                 FROM mlite_monitoring_suhu_log l
                 INNER JOIN (
                     SELECT ruangan_id, MAX(CONCAT(tanggal, ' ', jam, '#', LPAD(id, 10, '0'))) AS max_key
                     FROM mlite_monitoring_suhu_log
                     WHERE ruangan_id IN ($ph)
                     GROUP BY ruangan_id
                 ) m ON m.ruangan_id = l.ruangan_id AND CONCAT(l.tanggal, ' ', l.jam, '#', LPAD(l.id, 10, '0')) = m.max_key"
            );
            $latestStmt->execute($filteredTitikIds);
            $latestByTitik = [];
            foreach ($latestStmt->fetchAll(\PDO::FETCH_ASSOC) as $lr) {
                $latestByTitik[(int)$lr['ruangan_id']] = $lr;
            }

            $todayStmt = $pdo->prepare(
                "SELECT ruangan_id, COUNT(*) AS jml, SUM(status_keseluruhan = 'Peringatan') AS jml_alert
                 FROM mlite_monitoring_suhu_log
                 WHERE ruangan_id IN ($ph) AND tanggal = ?
                 GROUP BY ruangan_id"
            );
            $todayStmt->execute(array_merge($filteredTitikIds, [$today]));
            $todayMap = [];
            foreach ($todayStmt->fetchAll(\PDO::FETCH_ASSOC) as $tr) {
                $todayMap[(int)$tr['ruangan_id']] = $tr;
            }

            foreach ($filteredTitik as $t) {
                $tid = (int)$t['id'];
                $last = $latestByTitik[$tid] ?? null;
                $tInfo = $todayMap[$tid] ?? ['jml' => 0, 'jml_alert' => 0];
                $checkedToday = ((int)$tInfo['jml'] > 0);
                $hasAlert = ($last && $last['status_keseluruhan'] === 'Peringatan');

                if (!$checkedToday) {
                    $countUncheckedToday++;
                } elseif ((int)$tInfo['jml_alert'] > 0) {
                    $countAlertToday++;
                } else {
                    $countNormalToday++;
                }

                $t['last_log'] = $last;
                $t['today_count'] = (int)$tInfo['jml'];
                $t['checked_today'] = $checkedToday;
                $t['card_state'] = !$checkedToday ? 'belum' : ($hasAlert ? 'peringatan' : 'normal');
                $titikCards[] = $t;
            }
        }

        // Ambil riwayat pengukuran sesuai filter
        $logs = [];
        $totalLogs = 0;
        $totalAlertLogs = 0;
        if (!empty($filteredTitikIds)) {
            $where = [];
            $params = [];

            if ($ruanganFilter > 0) {
                $where[] = "l.ruangan_id = ?";
                $params[] = $ruanganFilter;
            } else {
                $ph = implode(',', array_fill(0, count($filteredTitikIds), '?'));
                $where[] = "l.ruangan_id IN ($ph)";
                $params = array_merge($params, $filteredTitikIds);
            }

            if ($tglAwal !== '' && $tglAkhir !== '') {
                $where[] = "l.tanggal BETWEEN ? AND ?";
                $params[] = $tglAwal;
                $params[] = $tglAkhir;
            } elseif ($tglAwal !== '') {
                $where[] = "l.tanggal >= ?";
                $params[] = $tglAwal;
            } elseif ($tglAkhir !== '') {
                $where[] = "l.tanggal <= ?";
                $params[] = $tglAkhir;
            }

            if ($statusFilter !== 'Semua') {
                $where[] = "l.status_keseluruhan = ?";
                $params[] = $statusFilter;
            }

            $logStmt = $pdo->prepare(
                "SELECT l.*, r.kode_ruangan, r.nama_ruangan, r.jenis_objek, r.lokasi, r.mode_ukur,
                        r.suhu_min, r.suhu_max, r.kelembaban_min, r.kelembaban_max,
                        u.kode_unit, u.nama_unit
                 FROM mlite_monitoring_suhu_log l
                 INNER JOIN mlite_monitoring_suhu_ruangan r ON r.id = l.ruangan_id
                 INNER JOIN mlite_monitoring_suhu_unit u ON u.id = r.unit_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY l.tanggal DESC, l.jam DESC, l.id DESC
                 LIMIT 500"
            );
            $logStmt->execute($params);
            $rawLogs = $logStmt->fetchAll(\PDO::FETCH_ASSOC);

            $canInputMap = [];
            foreach ($allTitik as $at) {
                $canInputMap[(int)$at['id']] = (($at['hak_akses'] ?? '') === 'input');
            }

            foreach ($rawLogs as $row) {
                $row['can_edit'] = $isAdmin || !empty($canInputMap[(int)$row['ruangan_id']]);
                $logs[] = $row;
                $totalLogs++;
                if ($row['status_keseluruhan'] === 'Peringatan') {
                    $totalAlertLogs++;
                }
            }
        }

        // Data grafik kronologis (60 titik terakhir)
        $chartRows = array_reverse(array_slice($logs, 0, 60));
        $hasSuhuSeries = false;
        $hasKelSeries = false;
        $chartData = [
            'labels'         => [],
            'suhu'           => [],
            'kelembaban'     => [],
            'mode_ukur'      => $selectedTitik ? $selectedTitik['mode_ukur'] : 'mixed',
            'suhu_min'       => ($selectedTitik && $selectedTitik['suhu_min'] !== null) ? (float)$selectedTitik['suhu_min'] : null,
            'suhu_max'       => ($selectedTitik && $selectedTitik['suhu_max'] !== null) ? (float)$selectedTitik['suhu_max'] : null,
            'kelembaban_min' => ($selectedTitik && $selectedTitik['kelembaban_min'] !== null) ? (float)$selectedTitik['kelembaban_min'] : null,
            'kelembaban_max' => ($selectedTitik && $selectedTitik['kelembaban_max'] !== null) ? (float)$selectedTitik['kelembaban_max'] : null,
            'has_suhu'       => false,
            'has_kelembaban' => false,
        ];
        foreach ($chartRows as $cr) {
            $label = date('d/m', strtotime($cr['tanggal'])) . ' ' . substr($cr['jam'], 0, 5);
            if (!$selectedTitik) {
                $label .= ' (' . $cr['kode_ruangan'] . ')';
            }
            $sVal = $cr['suhu'] !== null ? (float)$cr['suhu'] : null;
            $kVal = $cr['kelembaban'] !== null ? (float)$cr['kelembaban'] : null;
            if ($sVal !== null) $hasSuhuSeries = true;
            if ($kVal !== null) $hasKelSeries = true;
            $chartData['labels'][] = $label;
            $chartData['suhu'][] = $sVal;
            $chartData['kelembaban'][] = $kVal;
        }
        $chartData['has_suhu'] = $hasSuhuSeries;
        $chartData['has_kelembaban'] = $hasKelSeries;

        return $this->draw('manage.html', [
            'is_admin'              => $isAdmin,
            'allowed_units'         => $allowedUnits,
            'input_units'           => $inputUnits,
            'can_manage_titik'      => !empty($inputUnits),
            'all_titik'             => $allTitik,
            'filtered_titik'        => $filteredTitik,
            'input_titik'           => $inputTitik,
            'can_input_log'         => !empty($inputTitik),
            'titik_cards'           => $titikCards,
            'logs'                  => $logs,
            'tgl_awal'              => $tglAwal,
            'tgl_akhir'             => $tglAkhir,
            'unit_filter'           => $unitFilter,
            'ruangan_filter'        => $ruanganFilter,
            'selected_titik'        => $selectedTitik,
            'status_filter'         => $statusFilter,
            'total_titik'           => count($filteredTitik),
            'count_normal_today'    => $countNormalToday,
            'count_alert_today'     => $countAlertToday,
            'count_unchecked_today' => $countUncheckedToday,
            'total_logs'            => $totalLogs,
            'total_alert_logs'      => $totalAlertLogs,
            'default_shift'         => $this->_detectShiftNow(),
            'titik_json'            => json_encode($allTitik, JSON_UNESCAPED_UNICODE),
            'chart_json'            => json_encode($chartData, JSON_UNESCAPED_UNICODE),
            'logs_json'             => json_encode($logs, JSON_UNESCAPED_UNICODE),
        ]);
    }

    // ==================== TITIK UKUR (Bisa dikelola Petugas Unit hak 'input' & Admin) ====================

    public function postSaveTitik()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireModuleAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        $unitId = (int)($_POST['unit_id'] ?? 0);
        $kode = strtoupper(trim((string)($_POST['kode_ruangan'] ?? '')));
        $nama = trim((string)($_POST['nama_ruangan'] ?? ''));
        $jenisObjek = trim((string)($_POST['jenis_objek'] ?? 'Ruangan'));
        $lokasi = trim((string)($_POST['lokasi'] ?? ''));
        $modeUkur = trim((string)($_POST['mode_ukur'] ?? 'keduanya'));
        $suhuMinRaw = str_replace(',', '.', trim((string)($_POST['suhu_min'] ?? '')));
        $suhuMaxRaw = str_replace(',', '.', trim((string)($_POST['suhu_max'] ?? '')));
        $kelMinRaw = str_replace(',', '.', trim((string)($_POST['kelembaban_min'] ?? '')));
        $kelMaxRaw = str_replace(',', '.', trim((string)($_POST['kelembaban_max'] ?? '')));
        $kodeAlat = trim((string)($_POST['kode_alat'] ?? ''));
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;

        $unit = $this->_assertUnitAccess($unitId, true, true);

        if ($nama === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nama titik ukur (ruangan/kulkas/alat) wajib diisi.']);
            exit();
        }
        if (!in_array($modeUkur, ['suhu', 'kelembaban', 'keduanya'], true)) {
            $modeUkur = 'keduanya';
        }
        if ($jenisObjek === '') {
            $jenisObjek = 'Ruangan';
        }

        $suhuMin = null;
        $suhuMax = null;
        $kelMin = null;
        $kelMax = null;

        if ($modeUkur === 'suhu' || $modeUkur === 'keduanya') {
            if ($suhuMinRaw === '' || $suhuMaxRaw === '' || !is_numeric($suhuMinRaw) || !is_numeric($suhuMaxRaw)) {
                echo json_encode(['status' => 'error', 'message' => 'Batas standar Suhu Minimal & Maksimal (°C) wajib diisi angka.']);
                exit();
            }
            $suhuMin = round((float)$suhuMinRaw, 2);
            $suhuMax = round((float)$suhuMaxRaw, 2);
            if ($suhuMin >= $suhuMax) {
                echo json_encode(['status' => 'error', 'message' => 'Suhu minimal harus lebih kecil dari suhu maksimal.']);
                exit();
            }
        }

        if ($modeUkur === 'kelembaban' || $modeUkur === 'keduanya') {
            if ($kelMinRaw === '' || $kelMaxRaw === '' || !is_numeric($kelMinRaw) || !is_numeric($kelMaxRaw)) {
                echo json_encode(['status' => 'error', 'message' => 'Batas standar Kelembaban Minimal & Maksimal (%RH) wajib diisi angka.']);
                exit();
            }
            $kelMin = round((float)$kelMinRaw, 2);
            $kelMax = round((float)$kelMaxRaw, 2);
            if ($kelMin >= $kelMax) {
                echo json_encode(['status' => 'error', 'message' => 'Kelembaban minimal harus lebih kecil dari kelembaban maksimal.']);
                exit();
            }
        }

        $pdo = $this->db()->pdo();

        // Buat kode otomatis jika dikosongkan
        if ($kode === '') {
            $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $unit['kode_unit'] ?? 'TU'));
            if ($prefix === '') $prefix = 'TU';
            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM mlite_monitoring_suhu_ruangan WHERE unit_id = ?");
            $cntStmt->execute([$unitId]);
            $nextNum = (int)$cntStmt->fetchColumn() + 1;
            $kode = $prefix . '-' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);
            while (true) {
                $cDup = $pdo->prepare("SELECT id FROM mlite_monitoring_suhu_ruangan WHERE unit_id = ? AND kode_ruangan = ? AND id != ?");
                $cDup->execute([$unitId, $kode, $id]);
                if (!$cDup->fetchColumn()) break;
                $nextNum++;
                $kode = $prefix . '-' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);
            }
        } else {
            $dupStmt = $pdo->prepare("SELECT id FROM mlite_monitoring_suhu_ruangan WHERE unit_id = ? AND kode_ruangan = ? AND id != ?");
            $dupStmt->execute([$unitId, $kode, $id]);
            if ($dupStmt->fetchColumn()) {
                echo json_encode(['status' => 'error', 'message' => 'Kode titik ukur "' . $kode . '" sudah digunakan di unit ini.']);
                exit();
            }
        }

        if ($id > 0) {
            $this->_assertTitikAccess($id, true, true, true);
            $upd = $pdo->prepare(
                "UPDATE mlite_monitoring_suhu_ruangan
                 SET unit_id = ?, kode_ruangan = ?, nama_ruangan = ?, jenis_objek = ?, lokasi = ?,
                     mode_ukur = ?, suhu_min = ?, suhu_max = ?, kelembaban_min = ?, kelembaban_max = ?,
                     kode_alat = ?, keterangan = ?, aktif = ?
                 WHERE id = ?"
            );
            $upd->execute([
                $unitId, $kode, $nama, $jenisObjek, $lokasi,
                $modeUkur, $suhuMin, $suhuMax, $kelMin, $kelMax,
                $kodeAlat, $keterangan, $aktif ? 1 : 0, $id
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Titik ukur "' . $nama . '" berhasil diperbarui.']);
            exit();
        }

        $ins = $pdo->prepare(
            "INSERT INTO mlite_monitoring_suhu_ruangan
             (unit_id, kode_ruangan, nama_ruangan, jenis_objek, lokasi, mode_ukur, suhu_min, suhu_max, kelembaban_min, kelembaban_max, kode_alat, keterangan, aktif, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([
            $unitId, $kode, $nama, $jenisObjek, $lokasi,
            $modeUkur, $suhuMin, $suhuMax, $kelMin, $kelMax,
            $kodeAlat, $keterangan, $aktif ? 1 : 0, $this->_currentUserId()
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Titik ukur "' . $nama . '" berhasil ditambahkan pada ' . $unit['nama_unit'] . '.']);
        exit();
    }

    public function postDeleteTitik()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireModuleAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        $this->_assertTitikAccess($id, true, true, true);

        $pdo = $this->db()->pdo();
        $cntLog = $pdo->prepare("SELECT COUNT(*) FROM mlite_monitoring_suhu_log WHERE ruangan_id = ?");
        $cntLog->execute([$id]);
        if ((int)$cntLog->fetchColumn() > 0) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Titik ukur ini sudah memiliki riwayat pengukuran. Silakan edit dan ubah status menjadi Nonaktif daripada menghapus permanen.'
            ]);
            exit();
        }

        $pdo->prepare("DELETE FROM mlite_monitoring_suhu_ruangan WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Titik ukur berhasil dihapus.']);
        exit();
    }

    // ==================== LOG PENGUKURAN ====================

    public function postSaveLog()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireModuleAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        $ruanganId = (int)($_POST['ruangan_id'] ?? 0);
        $tanggal = trim((string)($_POST['tanggal'] ?? date('Y-m-d')));
        $jam = trim((string)($_POST['jam'] ?? date('H:i')));
        $shift = trim((string)($_POST['shift'] ?? $this->_detectShiftNow()));
        $suhuRaw = str_replace(',', '.', trim((string)($_POST['suhu'] ?? '')));
        $kelembabanRaw = str_replace(',', '.', trim((string)($_POST['kelembaban'] ?? '')));
        $tindakan = trim((string)($_POST['tindakan_perbaikan'] ?? ''));
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));

        $titik = $this->_assertTitikAccess($ruanganId, true, true, false);
        $mode = $titik['mode_ukur'] ?? 'keduanya';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid.']);
            exit();
        }
        if (preg_match('/^\d{2}:\d{2}$/', $jam)) {
            $jam .= ':00';
        }
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $jam)) {
            echo json_encode(['status' => 'error', 'message' => 'Format jam tidak valid (HH:MM).']);
            exit();
        }
        if (!in_array($shift, ['Pagi', 'Siang', 'Sore', 'Malam'], true)) {
            $shift = 'Pagi';
        }

        $suhu = null;
        $kelembaban = null;

        if ($mode === 'suhu' || $mode === 'keduanya') {
            if ($suhuRaw === '' || !is_numeric($suhuRaw)) {
                echo json_encode(['status' => 'error', 'message' => 'Nilai suhu (°C) wajib diisi berupa angka.']);
                exit();
            }
            $suhu = round((float)$suhuRaw, 2);
            if ($suhu < -80 || $suhu > 120) {
                echo json_encode(['status' => 'error', 'message' => 'Nilai suhu di luar rentang wajar (-80 s.d. 120 °C).']);
                exit();
            }
        }

        if ($mode === 'kelembaban' || $mode === 'keduanya') {
            if ($kelembabanRaw === '' || !is_numeric($kelembabanRaw)) {
                echo json_encode(['status' => 'error', 'message' => 'Nilai kelembaban (%RH) wajib diisi berupa angka.']);
                exit();
            }
            $kelembaban = round((float)$kelembabanRaw, 2);
            if ($kelembaban < 0 || $kelembaban > 100) {
                echo json_encode(['status' => 'error', 'message' => 'Nilai kelembaban harus antara 0 s.d. 100 %RH.']);
                exit();
            }
        }

        $eval = $this->_evaluateStatus($suhu, $kelembaban, $titik);
        $userId = $this->_currentUserId();
        $petugasNama = trim((string)$this->core->getUserInfo('fullname', $userId, true));
        if ($petugasNama === '') {
            $petugasNama = trim((string)$this->core->getUserInfo('username', $userId, true));
        }

        $pdo = $this->db()->pdo();

        if ($id > 0) {
            $cek = $pdo->prepare("SELECT * FROM mlite_monitoring_suhu_log WHERE id = ?");
            $cek->execute([$id]);
            $existing = $cek->fetch(\PDO::FETCH_ASSOC);
            if (!$existing) {
                echo json_encode(['status' => 'error', 'message' => 'Data catatan tidak ditemukan.']);
                exit();
            }
            $this->_assertTitikAccess((int)$existing['ruangan_id'], true, true, true);

            $upd = $pdo->prepare(
                "UPDATE mlite_monitoring_suhu_log
                 SET ruangan_id = ?, tanggal = ?, jam = ?, shift = ?, suhu = ?, kelembaban = ?,
                     status_suhu = ?, status_kelembaban = ?, status_keseluruhan = ?,
                     tindakan_perbaikan = ?, keterangan = ?
                 WHERE id = ?"
            );
            $upd->execute([
                $ruanganId, $tanggal, $jam, $shift, $suhu, $kelembaban,
                $eval['status_suhu'], $eval['status_kelembaban'], $eval['status_keseluruhan'],
                $tindakan, $keterangan, $id
            ]);

            echo json_encode([
                'status'             => 'success',
                'message'            => 'Data pengukuran berhasil diperbarui.',
                'status_keseluruhan' => $eval['status_keseluruhan'],
            ]);
            exit();
        }

        $ins = $pdo->prepare(
            "INSERT INTO mlite_monitoring_suhu_log
             (ruangan_id, tanggal, jam, shift, suhu, kelembaban, status_suhu, status_kelembaban, status_keseluruhan, tindakan_perbaikan, keterangan, petugas_id, petugas_nama)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([
            $ruanganId, $tanggal, $jam, $shift, $suhu, $kelembaban,
            $eval['status_suhu'], $eval['status_kelembaban'], $eval['status_keseluruhan'],
            $tindakan, $keterangan, $userId, $petugasNama
        ]);

        echo json_encode([
            'status'             => 'success',
            'message'            => $eval['status_keseluruhan'] === 'Peringatan'
                ? 'Tercatat dengan status PERINGATAN (nilai di luar standar titik ukur).'
                : 'Data pengukuran berhasil disimpan (Normal).',
            'status_keseluruhan' => $eval['status_keseluruhan'],
        ]);
        exit();
    }

    public function postDeleteLog()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireModuleAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID catatan tidak valid.']);
            exit();
        }

        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT * FROM mlite_monitoring_suhu_log WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$existing) {
            echo json_encode(['status' => 'error', 'message' => 'Data catatan tidak ditemukan.']);
            exit();
        }

        $this->_assertTitikAccess((int)$existing['ruangan_id'], true, true, true);

        $pdo->prepare("DELETE FROM mlite_monitoring_suhu_log WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Catatan pengukuran berhasil dihapus.']);
        exit();
    }

    // ==================== ADMIN: KELOLA UNIT & HAK AKSES PER UNIT ====================

    public function anyRuangan()
    {
        redirect(url([ADMIN, 'monitoring_suhu', 'unit']));
        exit();
    }

    public function anyUnit()
    {
        $this->_requireAdminAccess(false);
        $this->_addHeaderFiles();

        $pdo = $this->db()->pdo();
        $units = $pdo->query(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM mlite_monitoring_suhu_ruangan r WHERE r.unit_id = u.id) AS total_titik,
                    (SELECT COUNT(*) FROM mlite_monitoring_suhu_akses a WHERE a.unit_id = u.id) AS total_user,
                    (SELECT COUNT(*) FROM mlite_monitoring_suhu_log l INNER JOIN mlite_monitoring_suhu_ruangan r2 ON r2.id = l.ruangan_id WHERE r2.unit_id = u.id) AS total_log
             FROM mlite_monitoring_suhu_unit u
             ORDER BY u.aktif DESC, u.nama_unit ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $aksesRows = $pdo->query(
            "SELECT a.id, a.unit_id, a.user_id, a.hak_akses, a.created_at,
                    u.username, u.fullname, u.role
             FROM mlite_monitoring_suhu_akses a
             INNER JOIN mlite_users u ON u.id = a.user_id
             ORDER BY u.fullname ASC, u.username ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $aksesByUnit = [];
        foreach ($aksesRows as $ar) {
            $uid = (int)$ar['unit_id'];
            if (!isset($aksesByUnit[$uid])) $aksesByUnit[$uid] = [];
            $aksesByUnit[$uid][] = $ar;
        }

        $titikRows = $pdo->query(
            "SELECT id, unit_id, kode_ruangan, nama_ruangan, jenis_objek, mode_ukur, aktif
             FROM mlite_monitoring_suhu_ruangan
             ORDER BY nama_ruangan ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $titikByUnit = [];
        foreach ($titikRows as $tr) {
            $uid = (int)$tr['unit_id'];
            if (!isset($titikByUnit[$uid])) $titikByUnit[$uid] = [];
            $titikByUnit[$uid][] = $tr;
        }

        foreach ($units as &$u) {
            $uid = (int)$u['id'];
            $u['users'] = $aksesByUnit[$uid] ?? [];
            $u['titik_list'] = $titikByUnit[$uid] ?? [];
        }
        unset($u);

        $users = $pdo->query(
            "SELECT id, username, fullname, role
             FROM mlite_users
             ORDER BY fullname ASC, username ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('unit.html', [
            'units'      => $units,
            'users'      => $users,
            'units_json' => json_encode($units, JSON_UNESCAPED_UNICODE),
            'users_json' => json_encode($users, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function postSaveUnit()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireAdminAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        $kode = strtoupper(trim((string)($_POST['kode_unit'] ?? '')));
        $nama = trim((string)($_POST['nama_unit'] ?? ''));
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;

        if ($kode === '' || $nama === '') {
            echo json_encode(['status' => 'error', 'message' => 'Kode unit dan nama unit wajib diisi.']);
            exit();
        }

        $pdo = $this->db()->pdo();
        $dupStmt = $pdo->prepare("SELECT id FROM mlite_monitoring_suhu_unit WHERE kode_unit = ? AND id != ?");
        $dupStmt->execute([$kode, $id]);
        if ($dupStmt->fetchColumn()) {
            echo json_encode(['status' => 'error', 'message' => 'Kode unit "' . $kode . '" sudah digunakan.']);
            exit();
        }

        if ($id > 0) {
            $upd = $pdo->prepare(
                "UPDATE mlite_monitoring_suhu_unit
                 SET kode_unit = ?, nama_unit = ?, keterangan = ?, aktif = ?
                 WHERE id = ?"
            );
            $upd->execute([$kode, $nama, $keterangan, $aktif ? 1 : 0, $id]);

            $uStmt = $pdo->prepare("SELECT user_id FROM mlite_monitoring_suhu_akses WHERE unit_id = ?");
            $uStmt->execute([$id]);
            foreach ($uStmt->fetchAll(\PDO::FETCH_COLUMN) as $uid) {
                $this->_syncUserModuleAccess((int)$uid);
            }

            echo json_encode(['status' => 'success', 'message' => 'Unit "' . $nama . '" berhasil diperbarui.']);
            exit();
        }

        $ins = $pdo->prepare(
            "INSERT INTO mlite_monitoring_suhu_unit (kode_unit, nama_unit, keterangan, aktif)
             VALUES (?, ?, ?, ?)"
        );
        $ins->execute([$kode, $nama, $keterangan, $aktif ? 1 : 0]);

        echo json_encode(['status' => 'success', 'message' => 'Unit "' . $nama . '" berhasil dibuat.']);
        exit();
    }

    public function postDeleteUnit()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireAdminAccess(true);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID unit tidak valid.']);
            exit();
        }

        $pdo = $this->db()->pdo();
        $cntTitik = $pdo->prepare("SELECT COUNT(*) FROM mlite_monitoring_suhu_ruangan WHERE unit_id = ?");
        $cntTitik->execute([$id]);
        if ((int)$cntTitik->fetchColumn() > 0) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Unit ini masih memiliki titik ukur (ruangan/kulkas/alat). Hapus titik ukurnya terlebih dahulu atau ubah status unit menjadi Nonaktif.'
            ]);
            exit();
        }

        $uStmt = $pdo->prepare("SELECT user_id FROM mlite_monitoring_suhu_akses WHERE unit_id = ?");
        $uStmt->execute([$id]);
        $affectedUsers = $uStmt->fetchAll(\PDO::FETCH_COLUMN);

        $pdo->prepare("DELETE FROM mlite_monitoring_suhu_akses WHERE unit_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM mlite_monitoring_suhu_unit WHERE id = ?")->execute([$id]);

        foreach ($affectedUsers as $uid) {
            $this->_syncUserModuleAccess((int)$uid);
        }

        echo json_encode(['status' => 'success', 'message' => 'Unit berhasil dihapus.']);
        exit();
    }

    public function postSaveAkses()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireAdminAccess(true);

        $unitId = (int)($_POST['unit_id'] ?? 0);
        $userIds = $_POST['user_ids'] ?? [];
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        $singleUserId = (int)($_POST['user_id'] ?? 0);
        if ($singleUserId > 0 && empty($userIds)) {
            $userIds = [$singleUserId];
        }
        $hakAkses = trim((string)($_POST['hak_akses'] ?? 'input'));
        if (!in_array($hakAkses, ['input', 'view'], true)) {
            $hakAkses = 'input';
        }

        if ($unitId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unit wajib dipilih.']);
            exit();
        }
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), function ($v) {
            return $v > 0;
        })));
        if (empty($userIds)) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih minimal 1 pengguna untuk diberi akses unit.']);
            exit();
        }

        $pdo = $this->db()->pdo();
        $cekUnit = $pdo->prepare("SELECT id, nama_unit FROM mlite_monitoring_suhu_unit WHERE id = ?");
        $cekUnit->execute([$unitId]);
        $unit = $cekUnit->fetch(\PDO::FETCH_ASSOC);
        if (!$unit) {
            echo json_encode(['status' => 'error', 'message' => 'Unit tidak ditemukan.']);
            exit();
        }

        $createdBy = $this->_currentUserId();
        $upsert = $pdo->prepare(
            "INSERT INTO mlite_monitoring_suhu_akses (unit_id, user_id, hak_akses, created_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE hak_akses = VALUES(hak_akses)"
        );

        foreach ($userIds as $uid) {
            $upsert->execute([$unitId, $uid, $hakAkses, $createdBy]);
            $this->_syncUserModuleAccess($uid);
        }

        echo json_encode([
            'status'  => 'success',
            'message' => count($userIds) . ' pengguna berhasil diberi akses (' . ($hakAkses === 'input' ? 'Pengisi & Kelola Titik Ukur' : 'Lihat Saja') . ') pada ' . $unit['nama_unit'] . '.'
        ]);
        exit();
    }

    public function postDeleteAkses()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->_requireAdminAccess(true);

        $aksesId = (int)($_POST['id'] ?? 0);
        if ($aksesId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID hak akses tidak valid.']);
            exit();
        }

        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT user_id FROM mlite_monitoring_suhu_akses WHERE id = ?");
        $stmt->execute([$aksesId]);
        $uid = (int)$stmt->fetchColumn();
        if ($uid <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Data hak akses tidak ditemukan.']);
            exit();
        }

        $pdo->prepare("DELETE FROM mlite_monitoring_suhu_akses WHERE id = ?")->execute([$aksesId]);
        $this->_syncUserModuleAccess($uid);

        echo json_encode(['status' => 'success', 'message' => 'Hak akses pengguna pada unit ini berhasil dicabut.']);
        exit();
    }

    // ==================== EKSPOR CSV & CETAK FORMULIR ====================

    public function getExportCsv()
    {
        $allowedUnits = $this->_requireModuleAccess(false);
        $allTitik = $this->_getAllowedTitik($allowedUnits, true);
        $allowedUnitIds = array_map(function ($u) { return (int)$u['id']; }, $allowedUnits);

        $tglAwal = trim((string)($_GET['tgl_awal'] ?? date('Y-m-01')));
        $tglAkhir = trim((string)($_GET['tgl_akhir'] ?? date('Y-m-d')));
        $unitFilter = (int)($_GET['unit_id'] ?? 0);
        $ruanganFilter = (int)($_GET['ruangan_id'] ?? 0);
        $statusFilter = trim((string)($_GET['status'] ?? 'Semua'));

        if ($unitFilter > 0 && !in_array($unitFilter, $allowedUnitIds, true)) {
            $this->_denyAccess('Anda tidak memiliki hak akses untuk mengekspor unit ini.', false);
        }

        $targetTitik = $allTitik;
        if ($unitFilter > 0) {
            $targetTitik = array_values(array_filter($allTitik, function ($t) use ($unitFilter) {
                return (int)$t['unit_id'] === $unitFilter;
            }));
        }
        $targetIds = array_map(function ($t) { return (int)$t['id']; }, $targetTitik);

        if ($ruanganFilter > 0 && !in_array($ruanganFilter, $targetIds, true)) {
            $this->_denyAccess('Anda tidak memiliki hak akses untuk mengekspor titik ukur ini.', false);
        }

        $rows = [];
        if (!empty($targetIds)) {
            $pdo = $this->db()->pdo();
            $where = [];
            $params = [];
            if ($ruanganFilter > 0) {
                $where[] = "l.ruangan_id = ?";
                $params[] = $ruanganFilter;
            } else {
                $ph = implode(',', array_fill(0, count($targetIds), '?'));
                $where[] = "l.ruangan_id IN ($ph)";
                $params = array_merge($params, $targetIds);
            }
            if ($tglAwal !== '' && $tglAkhir !== '') {
                $where[] = "l.tanggal BETWEEN ? AND ?";
                $params[] = $tglAwal;
                $params[] = $tglAkhir;
            }
            if (in_array($statusFilter, ['Normal', 'Peringatan'], true)) {
                $where[] = "l.status_keseluruhan = ?";
                $params[] = $statusFilter;
            }

            $stmt = $pdo->prepare(
                "SELECT l.*, r.kode_ruangan, r.nama_ruangan, r.jenis_objek, r.lokasi, r.mode_ukur,
                        r.suhu_min, r.suhu_max, r.kelembaban_min, r.kelembaban_max,
                        u.kode_unit, u.nama_unit
                 FROM mlite_monitoring_suhu_log l
                 INNER JOIN mlite_monitoring_suhu_ruangan r ON r.id = l.ruangan_id
                 INNER JOIN mlite_monitoring_suhu_unit u ON u.id = r.unit_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY l.tanggal ASC, l.jam ASC, l.id ASC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $filename = 'monitoring_suhu_kelembaban_' . ($tglAwal ?: 'all') . '_sd_' . ($tglAkhir ?: 'all') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'No', 'Tanggal', 'Jam', 'Unit', 'Kode Titik', 'Nama Titik Ukur', 'Jenis', 'Mode Ukur',
            'Suhu (C)', 'Standar Suhu (C)', 'Status Suhu',
            'Kelembaban (%RH)', 'Standar Kelembaban (%RH)', 'Status Kelembaban',
            'Status Keseluruhan', 'Tindakan Perbaikan', 'Keterangan', 'Petugas'
        ]);
        $no = 1;
        foreach ($rows as $r) {
            fputcsv($out, [
                $no++,
                $r['tanggal'],
                substr($r['jam'], 0, 5),
                $r['nama_unit'],
                $r['kode_ruangan'],
                $r['nama_ruangan'],
                $r['jenis_objek'],
                $r['mode_ukur'],
                $r['suhu'] !== null ? $r['suhu'] : '-',
                ($r['suhu_min'] !== null ? $r['suhu_min'] . ' - ' . $r['suhu_max'] : '-'),
                $r['status_suhu'],
                $r['kelembaban'] !== null ? $r['kelembaban'] : '-',
                ($r['kelembaban_min'] !== null ? $r['kelembaban_min'] . ' - ' . $r['kelembaban_max'] : '-'),
                $r['status_kelembaban'],
                $r['status_keseluruhan'],
                $r['tindakan_perbaikan'],
                $r['keterangan'],
                $r['petugas_nama'],
            ]);
        }
        fclose($out);
        exit();
    }

    public function getCetak()
    {
        $allowedUnits = $this->_requireModuleAccess(false);
        $allTitik = $this->_getAllowedTitik($allowedUnits, true);

        $ruanganId = (int)($_GET['ruangan_id'] ?? 0);
        $tglAwal = trim((string)($_GET['tgl_awal'] ?? date('Y-m-01')));
        $tglAkhir = trim((string)($_GET['tgl_akhir'] ?? date('Y-m-d')));

        if ($ruanganId === 0 && !empty($allTitik)) {
            $ruanganId = (int)$allTitik[0]['id'];
        }

        $titik = $this->_assertTitikAccess($ruanganId, false, false, false);

        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM mlite_monitoring_suhu_log
             WHERE ruangan_id = ? AND tanggal BETWEEN ? AND ?
             ORDER BY tanggal ASC, jam ASC, id ASC"
        );
        $stmt->execute([$ruanganId, $tglAwal, $tglAkhir]);
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $mode = $titik['mode_ukur'] ?? 'keduanya';
        $showSuhu = ($mode === 'suhu' || $mode === 'keduanya');
        $showKel = ($mode === 'kelembaban' || $mode === 'keduanya');

        $chartData = [
            'labels'         => [],
            'suhu'           => [],
            'kelembaban'     => [],
            'mode_ukur'      => $mode,
            'show_suhu'      => $showSuhu,
            'show_kel'       => $showKel,
            'suhu_min'       => $titik['suhu_min'] !== null ? (float)$titik['suhu_min'] : null,
            'suhu_max'       => $titik['suhu_max'] !== null ? (float)$titik['suhu_max'] : null,
            'kelembaban_min' => $titik['kelembaban_min'] !== null ? (float)$titik['kelembaban_min'] : null,
            'kelembaban_max' => $titik['kelembaban_max'] !== null ? (float)$titik['kelembaban_max'] : null,
        ];
        $minSuhu = null;
        $maxSuhu = null;
        $minKel = null;
        $maxKel = null;
        $alertCount = 0;

        foreach ($logs as $l) {
            $s = $l['suhu'] !== null ? (float)$l['suhu'] : null;
            $k = $l['kelembaban'] !== null ? (float)$l['kelembaban'] : null;
            $chartData['labels'][] = date('d/m', strtotime($l['tanggal'])) . ' ' . substr($l['jam'], 0, 5);
            $chartData['suhu'][] = $s;
            $chartData['kelembaban'][] = $k;
            if ($s !== null) {
                if ($minSuhu === null || $s < $minSuhu) $minSuhu = $s;
                if ($maxSuhu === null || $s > $maxSuhu) $maxSuhu = $s;
            }
            if ($k !== null) {
                if ($minKel === null || $k < $minKel) $minKel = $k;
                if ($maxKel === null || $k > $maxKel) $maxKel = $k;
            }
            if ($l['status_keseluruhan'] === 'Peringatan') $alertCount++;
        }

        echo $this->draw('cetak.html', [
            'nama_instansi' => $this->settings->get('settings.nama_instansi') ?: 'RSGM Gusti Hasan Aman',
            'alamat'        => $this->settings->get('settings.alamat') ?: '',
            'kota'          => $this->settings->get('settings.kota') ?: 'Banjarmasin',
            'logo'          => $this->settings->get('settings.logo') ?: '',
            'room'          => $titik,
            'show_suhu'     => $showSuhu,
            'show_kel'      => $showKel,
            'logs'          => $logs,
            'tgl_awal'      => $tglAwal,
            'tgl_akhir'     => $tglAkhir,
            'min_suhu'      => $minSuhu,
            'max_suhu'      => $maxSuhu,
            'min_kel'       => $minKel,
            'max_kel'       => $maxKel,
            'alert_count'   => $alertCount,
            'chart_json'    => json_encode($chartData, JSON_UNESCAPED_UNICODE),
            'dicetak_oleh'  => $this->core->getUserInfo('fullname', $this->_currentUserId(), true) ?: $this->core->getUserInfo('username', $this->_currentUserId(), true),
            'dicetak_pada'  => date('d/m/Y H:i'),
        ]);
        exit();
    }
}

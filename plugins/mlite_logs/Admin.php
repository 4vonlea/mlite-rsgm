<?php
namespace Plugins\mLITE_Logs;

use Systems\AdminModule;
use PDOException;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Kelola' => 'manage',
        ];
    }

    private function ensureLogsIndexes()
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $pdo = $this->db()->pdo();
            $indexes = $pdo->query("SHOW INDEX FROM `mlite_query_logs`")->fetchAll(\PDO::FETCH_COLUMN, 2);
            if (!in_array('idx_created_at', $indexes, true)) {
                $pdo->exec("ALTER TABLE `mlite_query_logs` ADD INDEX `idx_created_at` (`created_at`)");
            }
            if (!in_array('idx_username', $indexes, true)) {
                $pdo->exec("ALTER TABLE `mlite_query_logs` ADD INDEX `idx_username` (`username`)");
            }
        } catch (\Throwable $e) {
        }
    }

    private function resolveLogUserNames(array $usernames)
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $usernames), function ($u) {
            return $u !== '';
        })));
        if (empty($clean)) {
            return [];
        }
        $map = [];
        $pdo = $this->db()->pdo();
        $placeholders = implode(',', array_fill(0, count($clean), '?'));

        try {
            $stmt = $pdo->prepare("SELECT nik, nama FROM pegawai WHERE nik IN ($placeholders)");
            $stmt->execute($clean);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                if (!empty($r['nik']) && !empty($r['nama'])) {
                    $map[$r['nik']] = $r['nama'];
                }
            }
        } catch (\Throwable $e) {
        }

        $missing = array_values(array_diff($clean, array_keys($map)));
        if (!empty($missing)) {
            try {
                $ph2 = implode(',', array_fill(0, count($missing), '?'));
                $stmt2 = $pdo->prepare("SELECT username, fullname FROM mlite_users WHERE username IN ($ph2)");
                $stmt2->execute($missing);
                foreach ($stmt2->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                    if (!empty($r['username']) && !empty($r['fullname'])) {
                        $map[$r['username']] = $r['fullname'];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return $map;
    }

    private function formatPreviewText($val, $limit = 160)
    {
        $str = trim((string)$val);
        if ($str === '') {
            return '';
        }
        $str = preg_replace('/\s+/', ' ', $str);
        if (function_exists('mb_strlen') && mb_strlen($str, 'UTF-8') > $limit) {
            return mb_substr($str, 0, $limit, 'UTF-8') . '...';
        }
        if (strlen($str) > $limit) {
            return substr($str, 0, $limit) . '...';
        }
        return $str;
    }

    public function getManage()
    {
        $this->ensureLogsIndexes();
        $this->_addHeaderFiles();
        $logQuerySetting = $this->settings->get('settings.log_query');
        return $this->draw('manage.html', [
            'log_query_enabled' => ($logQuerySetting === 'ya')
        ]);
    }

    public function postData()
    {
        header('Content-Type: application/json; charset=utf-8');
        $draw = (int)($_POST['draw'] ?? 0);
        $row1 = max(0, (int)($_POST['start'] ?? 0));
        $rowperpage = (int)($_POST['length'] ?? 25);
        if ($rowperpage <= 0 || $rowperpage > 200) {
            $rowperpage = 25;
        }

        $allowedColumns = ['id', 'sql_text', 'bindings', 'created_at', 'error_message', 'username'];
        $columnName = 'id';
        $columnSortOrder = 'desc';
        if (isset($_POST['order'][0]['column'])) {
            $columnIndex = (int)$_POST['order'][0]['column'];
            $requestedCol = $_POST['columns'][$columnIndex]['data'] ?? 'id';
            if (in_array($requestedCol, $allowedColumns, true)) {
                $columnName = $requestedCol;
            }
            $requestedDir = strtolower($_POST['order'][0]['dir'] ?? 'desc');
            if (in_array($requestedDir, ['asc', 'desc'], true)) {
                $columnSortOrder = $requestedDir;
            }
        }

        $search_field = $_POST['search_field_mlite_query_logs'] ?? '';
        $search_text = trim((string)($_POST['search_text_mlite_query_logs'] ?? ''));

        $searchQuery = "";
        $bindExactId = null;
        if ($search_text !== '' && in_array($search_field, $allowedColumns, true)) {
            if ($search_field === 'id' && ctype_digit($search_text)) {
                $bindExactId = (int)$search_text;
                $searchQuery .= " AND (`id` = :search_id) ";
            } else {
                $searchQuery .= " AND (`" . $search_field . "` LIKE :search_text) ";
            }
        }

        $pdo = $this->db()->pdo();
        $totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM mlite_query_logs")->fetchColumn();

        if ($searchQuery === '') {
            $totalRecordwithFilter = $totalRecords;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mlite_query_logs WHERE 1=1 $searchQuery");
            if ($bindExactId !== null) {
                $stmt->bindValue(':search_id', $bindExactId, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':search_text', "%{$search_text}%", \PDO::PARAM_STR);
            }
            $stmt->execute();
            $totalRecordwithFilter = (int)$stmt->fetchColumn();
        }

        $sql = "SELECT `id`, `username`, `created_at`, LEFT(`sql_text`, 180) AS `sql_text`, LEFT(`bindings`, 140) AS `bindings`, LEFT(`error_message`, 140) AS `error_message` FROM `mlite_query_logs` WHERE 1=1 $searchQuery ORDER BY `$columnName` $columnSortOrder LIMIT :row_offset, :row_limit";
        $stmt = $pdo->prepare($sql);
        if ($searchQuery !== '') {
            if ($bindExactId !== null) {
                $stmt->bindValue(':search_id', $bindExactId, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':search_text', "%{$search_text}%", \PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':row_offset', $row1, \PDO::PARAM_INT);
        $stmt->bindValue(':row_limit', $rowperpage, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $userMap = $this->resolveLogUserNames(array_column($result, 'username'));

        $data = [];
        foreach ($result as $row) {
            $rawUser = (string)($row['username'] ?? '');
            $displayName = $userMap[$rawUser] ?? ($rawUser !== '' ? $rawUser : 'Sistem');
            $data[] = [
                'id' => (string)$row['id'],
                'username' => $displayName,
                'raw_username' => $rawUser,
                'created_at' => (string)($row['created_at'] ?? ''),
                'sql_text' => $this->formatPreviewText($row['sql_text'] ?? '', 140),
                'bindings' => $this->formatPreviewText($row['bindings'] ?? '', 100),
                'error_message' => $this->formatPreviewText($row['error_message'] ?? '', 100)
            ];
        }

        echo json_encode([
            "draw" => $draw,
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        ], JSON_HEX_TAG | JSON_HEX_AMP);
        exit();
    }

    public function postAksi()
    {
        $act = $_POST['typeact'] ?? '';

        if (!in_array($act, ['add', 'edit', 'del', 'lihat'])) {
            echo json_encode(["status" => "error", "message" => "Aksi tidak dikenali."]);
            exit();
        }

        try {
            if ($act == 'add') {
                $id = $_POST['id'];
$sql_text = $_POST['sql_text'];
$bindings = $_POST['bindings'];
$created_at = $_POST['created_at'];
$error_message = $_POST['error_message'];
$username = $_POST['username'];


                $sql = "INSERT INTO mlite_query_logs VALUES (?, ?, ?, ?, ?, ?)";
                $binds = [$id, $sql_text, $bindings, $created_at, $error_message, $username];
                $stmt = $this->db()->pdo()->prepare($sql);
                $stmt->execute($binds);

                if($this->settings->get('settings.log_query') == 'ya') {
                    \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds);
                }

                echo json_encode(["status" => "success", "message" => "Data berhasil ditambahkan."]);

            } elseif ($act == 'lihat') {
                $search_field = $_POST['search_field_mlite_query_logs'] ?? '';
                $search_text = trim((string)($_POST['search_text_mlite_query_logs'] ?? ''));

                $searchQuery = "";
                $allowedColumns = ['id','sql_text','bindings','created_at','error_message','username'];
                $bindExactId = null;
                if ($search_text !== '' && in_array($search_field, $allowedColumns, true)) {
                    if ($search_field === 'id' && ctype_digit($search_text)) {
                        $bindExactId = (int)$search_text;
                        $searchQuery .= " AND (`id` = :search_id) ";
                    } else {
                        $searchQuery .= " AND (`" . $search_field . "` LIKE :search_text) ";
                    }
                }

                $stmt = $this->db()->pdo()->prepare("SELECT `id`, LEFT(`sql_text`, 300) AS `sql_text`, LEFT(`bindings`, 200) AS `bindings`, `created_at`, LEFT(`error_message`, 200) AS `error_message`, `username` FROM `mlite_query_logs` WHERE 1=1 $searchQuery ORDER BY `id` DESC LIMIT 200");

                if ($searchQuery !== '') {
                    if ($bindExactId !== null) {
                        $stmt->bindValue(':search_id', $bindExactId, \PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue(':search_text', "%$search_text%", \PDO::PARAM_STR);
                    }
                }

                $stmt->execute();
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $userMap = $this->resolveLogUserNames(array_column($result, 'username'));

                $data = [];
                foreach ($result as $row) {
                    $rawUser = (string)($row['username'] ?? '');
                    $data[] = [
                        'id' => (string)$row['id'],
                        'sql_text' => (string)($row['sql_text'] ?? ''),
                        'bindings' => (string)($row['bindings'] ?? ''),
                        'created_at' => (string)($row['created_at'] ?? ''),
                        'error_message' => (string)($row['error_message'] ?? ''),
                        'username' => $userMap[$rawUser] ?? ($rawUser !== '' ? $rawUser : 'Sistem')
                    ];
                }

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP);
            }
        } catch (\PDOException $e) {
            if($this->settings->get('settings.log_query') == 'ya') {            
                if (in_array($act, ['add', 'edit', 'del'])) {
                \Systems\Lib\QueryWrapper::logPdoQuery($sql, $binds, $e->getMessage());   
                } 
            }
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }

        exit();
    }

    public function postPrune()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->core->getUserInfo('id')) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
            exit();
        }
        $days = (int)($_POST['days'] ?? 30);
        if ($days < 0) {
            $days = 30;
        }
        try {
            $pdo = $this->db()->pdo();
            if ($days === 0) {
                $stmt = $pdo->prepare("DELETE FROM `mlite_query_logs`");
                $stmt->execute();
            } else {
                $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $stmt = $pdo->prepare("DELETE FROM `mlite_query_logs` WHERE `created_at` < ?");
                $stmt->execute([$cutoff]);
            }
            $deleted = $stmt->rowCount();
            $remaining = (int)$pdo->query("SELECT COUNT(*) FROM `mlite_query_logs`")->fetchColumn();
            echo json_encode([
                'status' => 'success',
                'deleted' => $deleted,
                'remaining' => $remaining,
                'message' => "Berhasil menghapus {$deleted} baris log lama. Sisa log: {$remaining}."
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function getDetail($id)
    {
        $id = (int)$id;
        $detail = $this->db('mlite_query_logs')->where('id', $id)->toArray();
        $userMap = !empty($detail) ? $this->resolveLogUserNames(array_column($detail, 'username')) : [];
        foreach ($detail as &$d) {
            $rawUser = (string)($d['username'] ?? '');
            $displayUser = $userMap[$rawUser] ?? ($rawUser !== '' ? $rawUser : 'Sistem');
            $d['id'] = htmlspecialchars((string)($d['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['sql_text'] = htmlspecialchars((string)($d['sql_text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['bindings'] = htmlspecialchars((string)($d['bindings'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['created_at'] = htmlspecialchars((string)($d['created_at'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['error_message'] = htmlspecialchars((string)($d['error_message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['username'] = htmlspecialchars($rawUser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $d['display_user'] = htmlspecialchars($displayUser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        unset($d);
        $settings = $this->settings('settings');
        echo $this->draw('detail.html', ['detail' => $detail, 'settings' => $settings]);
        exit();
    }

    public function getChart($type = '', $column = '')
    {
        if ($type == '') {
            $type = 'pie';
        }

        $labels = $this->db('mlite_query_logs')->select('username')->group('username')->toArray();
        $labels = json_encode(array_column($labels, 'username'));
        $datasets = $this->db('mlite_query_logs')->select('COUNT(username)')->group('username')->toArray();
        $datasets = json_encode(array_column($datasets, 'COUNT(username)'));

        if (!empty($column)) {
            $labels = $this->db('mlite_query_logs')->select($column)->group($column)->toArray();
            $labels = json_encode(array_column($labels, $column));
            $datasets = $this->db('mlite_query_logs')->select("COUNT($column)")->group($column)->toArray();
            $datasets = json_encode(array_column($datasets, "COUNT($column)"));
        }

        $database = DBNAME;
        $nama_table = 'mlite_query_logs';

        $stmt = $this->db()->pdo()->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=?");
        $stmt->execute([$database, $nama_table]);
        $result = $stmt->fetchAll();

        echo $this->draw('chart.html', ['type' => $type, 'column' => htmlspecialchars_array($result), 'labels' => $labels, 'datasets' => $datasets]);
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/mlite_logs/css/admin/styles.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        $settings = $this->settings('settings');
        echo $this->draw(MODULES.'/mlite_logs/js/admin/scripts.js', ['settings' => $settings]);
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/datatables.min.css'));
        $this->core->addCSS(url('assets/css/jquery.contextMenu.min.css'));
        $this->core->addJS(url('assets/jscripts/xlsx.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.min.js'));
        $this->core->addJS(url('assets/jscripts/jspdf.plugin.autotable.min.js'));
        $this->core->addJS(url('assets/jscripts/datatables.min.js'));
        $this->core->addJS(url('assets/jscripts/jquery.contextMenu.min.js'));

        $this->core->addCSS(url([ADMIN, 'mlite_logs', 'css']));
        $this->core->addJS(url([ADMIN, 'mlite_logs', 'javascript']), 'footer');
    }
}

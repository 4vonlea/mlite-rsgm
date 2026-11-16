<?php

namespace Plugins\Vedika;

trait ClaimTrait
{
    protected $claimEngine;

    public function getClaimEngine()
    {
        if (!$this->claimEngine) {
            require_once __DIR__ . '/ClaimReadinessEngine.php';
            $this->claimEngine = new ClaimReadinessEngine($this->db(), $this->settings, $this->core);
        }
        return $this->claimEngine;
    }

    /**
     * Helper untuk mendapatkan rentang tanggal aktif (tgl_awal dan tgl_akhir)
     * Fleksibel: Mendukung harian (1 hari), rentang tanggal (jangka tanggal), maupun bulanan (YYYY-MM)
     */
    protected function getActiveDateRange()
    {
        $tgl_awal = $_GET['tgl_awal'] ?? '';
        $tgl_akhir = $_GET['tgl_akhir'] ?? '';
        $periode = $_GET['periode'] ?? '';

        // 1. Jika user menginput periode lama berbentuk YYYY-MM
        if (!empty($periode) && empty($tgl_awal) && empty($tgl_akhir)) {
            $digits = preg_replace('/[^\d]/', '', $periode);
            if (strlen($digits) >= 6) {
                $ym = substr($digits, 0, 4) . '-' . substr($digits, 4, 2);
                return [
                    'tgl_awal' => $ym . '-01',
                    'tgl_akhir' => date('Y-m-t', strtotime($ym . '-01')),
                    'periode' => $ym
                ];
            }
        }

        // 2. Normalisasi input tanggal jika ada
        if (!empty($tgl_awal)) {
            $tgl_awal = date('Y-m-d', strtotime(str_replace('/', '-', $tgl_awal)));
        }
        if (!empty($tgl_akhir)) {
            $tgl_akhir = date('Y-m-d', strtotime(str_replace('/', '-', $tgl_akhir)));
        }

        // 3. Jika salah satu diisi, samakan untuk pengecekan harian 1 hari
        if (!empty($tgl_awal) && empty($tgl_akhir)) {
            $tgl_akhir = $tgl_awal;
        } elseif (empty($tgl_awal) && !empty($tgl_akhir)) {
            $tgl_awal = $tgl_akhir;
        }

        // 4. Jika keduanya kosong (default load pertama kali)
        if (empty($tgl_awal) && empty($tgl_akhir)) {
            $today = date('Y-m-d');
            $curMonth = date('Y-m');
            // Cek apakah ada data di bulan ini
            $chk = $this->db()->pdo()->query("SELECT COUNT(*) FROM reg_periksa WHERE tgl_registrasi LIKE '$curMonth%'")->fetchColumn();
            if ($chk > 0) {
                // Ada transaksi di bulan ini, defaultkan ke hari ini untuk kemudahan cek harian
                $tgl_awal = $today;
                $tgl_akhir = $today;
            } else {
                // Jika database demo belum ada data bulan ini, gunakan tanggal transaksi terakhir
                $maxDate = $this->db()->pdo()->query("SELECT MAX(tgl_registrasi) FROM reg_periksa")->fetchColumn();
                $defDate = !empty($maxDate) ? $maxDate : $today;
                $tgl_awal = $defDate;
                $tgl_akhir = $defDate;
            }
        }

        // Pastikan urutan tgl_awal <= tgl_akhir
        if ($tgl_awal > $tgl_akhir) {
            $tmp = $tgl_awal;
            $tgl_awal = $tgl_akhir;
            $tgl_akhir = $tmp;
        }

        return [
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => substr($tgl_awal, 0, 7)
        ];
    }

    /**
     * Backward-compatible helper untuk mendapatkan periode aktif (YYYY-MM)
     */
    protected function getActivePeriode()
    {
        $range = $this->getActiveDateRange();
        return $range['periode'];
    }

    /**
     * Helper untuk mendapatkan daftar cara bayar BPJS bersih tanpa kutip
     */
    protected function getCleanCarabayar()
    {
        $raw = $this->settings->get('vedika.carabayar', 'BPJ');
        $clean = array_filter(array_map(function($v) {
            return trim(str_replace(["'", '"'], '', $v));
        }, explode(',', $raw)));
        return !empty($clean) ? array_values($clean) : ['BPJ'];
    }

    /**
     * AJAX Endpoint: Cek Kesiapan Klaim Real-Time
     */
    public function anyCheckReadiness($no_rawat = null)
    {
        header('Content-Type: application/json');
        if (!$no_rawat && isset($_GET['no_rawat'])) {
            $no_rawat = $_GET['no_rawat'];
        }
        $no_rawat = str_replace('_', '/', trim((string)$no_rawat));
        if (strpos($no_rawat, '/') === false && strlen($no_rawat) == 14) {
            $no_rawat = revertNorawat($no_rawat);
        }

        if (!$no_rawat) {
            echo json_encode(['status' => 'error', 'message' => 'Nomor rawat tidak disertakan']);
            exit();
        }

        $engine = $this->getClaimEngine();
        $result = $engine->checkPatient($no_rawat);
        $result['missing_text'] = !empty($result['missing_items']) ? implode('; ', $result['missing_items']) : '';
        echo json_encode($result);
        exit();
    }

    /**
     * Sub-Menu 2: Pasien Belum Siap Klaim
     */
    public function anyBelumsiap()
    {
        $this->_addHeaderFiles();
        $cleanPj = $this->getCleanCarabayar();
        $inPlaceholders = implode(',', array_fill(0, count($cleanPj), '?'));

        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $status_lanjut = $_GET['status_lanjut'] ?? '';
        $kd_poli = $_GET['kd_poli'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, pasien.no_peserta, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep, r.no_sep, '-') AS no_sep,
                   COALESCE(r.score_percent, 0) AS score_percent,
                   COALESCE(r.status_readiness, 'belum_lengkap') AS status_readiness,
                   r.missing_items
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            LEFT JOIN mlite_klaim_readiness r ON reg_periksa.no_rawat = r.no_rawat
            WHERE (penjab.kd_pj IN ($inPlaceholders) OR penjab.png_jawab LIKE '%BPJS%')
            AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND (r.status_readiness = 'belum_lengkap' OR r.status_readiness IS NULL)
        ";

        $params = array_merge($cleanPj, [$tgl_awal, $tgl_akhir]);

        if (!empty($status_lanjut)) {
            $sql .= " AND reg_periksa.status_lanjut = ?";
            $params[] = $status_lanjut;
        }
        if (!empty($kd_poli)) {
            $sql .= " AND reg_periksa.kd_poli = ?";
            $params[] = $kd_poli;
        }
        if (!empty($search)) {
            $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Auto-audit on-the-fly untuk pasien yang belum pernah diaudit (cache mlite_klaim_readiness masih kosong/null)
        $engine = null;
        $maxDirectAudit = 25;
        $auditedCount = 0;
        foreach ($rows as $idx => $r) {
            $isUncached = ($r['missing_items'] === null && $r['score_percent'] == 0);
            if ($isUncached) {
                if ($auditedCount < $maxDirectAudit) {
                    if (!$engine) {
                        $engine = $this->getClaimEngine();
                    }
                    $eval = $engine->checkPatient($r['no_rawat']);
                    $rows[$idx]['score_percent'] = $eval['score_percent'];
                    $rows[$idx]['status_readiness'] = $eval['status_readiness'];
                    $rows[$idx]['missing_items'] = !empty($eval['missing_items']) ? implode('; ', $eval['missing_items']) : '';
                    if (!empty($eval['sep']['no_sep'])) {
                        $rows[$idx]['no_sep'] = $eval['sep']['no_sep'];
                    }
                    $rows[$idx]['is_ready'] = ($eval['score_percent'] >= 100);
                    $rows[$idx]['needs_client_audit'] = 0;
                    $auditedCount++;
                } else {
                    $rows[$idx]['needs_client_audit'] = 1;
                    $rows[$idx]['is_ready'] = false;
                }
            } else {
                $rows[$idx]['needs_client_audit'] = 0;
                $rows[$idx]['is_ready'] = ($r['score_percent'] >= 100);
            }
        }

        $polis = $this->db('poliklinik')->where('status', '1')->toArray();

        return $this->draw('belumsiap.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'status_lanjut' => $status_lanjut,
            'kd_poli' => $kd_poli,
            'search' => $search,
            'polis' => $polis,
            'total' => count($rows)
        ]);
    }

    /**
     * Sub-Menu 3: Pasien Siap Klaim
     */
    public function anySiapklaim()
    {
        $this->_addHeaderFiles();
        $cleanPj = $this->getCleanCarabayar();
        $inPlaceholders = implode(',', array_fill(0, count($cleanPj), '?'));

        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $status_lanjut = $_GET['status_lanjut'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, pasien.no_peserta, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep, r.no_sep, '-') AS no_sep,
                   r.score_percent, r.status_readiness,
                   v.verif_admin_status, v.verif_medis_status, v.is_internal_verified,
                   b.jumlah_total AS total_billing
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            JOIN mlite_klaim_readiness r ON reg_periksa.no_rawat = r.no_rawat
            LEFT JOIN mlite_klaim_verifikasi v ON reg_periksa.no_rawat = v.no_rawat
            LEFT JOIN mlite_billing b ON reg_periksa.no_rawat = b.no_rawat
            WHERE (penjab.kd_pj IN ($inPlaceholders) OR penjab.png_jawab LIKE '%BPJS%')
            AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND r.status_readiness IN ('siap_verifikasi', 'terverifikasi', 'siap_grouping', 'siap_batch')
        ";

        $params = array_merge($cleanPj, [$tgl_awal, $tgl_akhir]);

        if (!empty($status_lanjut)) {
            $sql .= " AND reg_periksa.status_lanjut = ?";
            $params[] = $status_lanjut;
        }
        if (!empty($search)) {
            $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Jika data siap klaim masih kosong, periksa apakah tabel readiness belum pernah diisi pada rentang tanggal ini
        if (empty($rows)) {
            $checkReadiness = $this->db()->pdo()->prepare("SELECT COUNT(*) FROM mlite_klaim_readiness WHERE tgl_registrasi BETWEEN ? AND ?");
            $checkReadiness->execute([$tgl_awal, $tgl_akhir]);
            $auditedCount = (int)$checkReadiness->fetchColumn();

            // Jika belum ada pasien yang diaudit sama sekali di rentang ini, lakukan audit otomatis untuk 20 pasien pertama
            if ($auditedCount === 0) {
                $engine = $this->getClaimEngine();
                $findStmt = $this->db()->pdo()->prepare("
                    SELECT reg_periksa.no_rawat 
                    FROM reg_periksa
                    JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                    WHERE (penjab.kd_pj IN ($inPlaceholders) OR penjab.png_jawab LIKE '%BPJS%')
                    AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
                    ORDER BY reg_periksa.tgl_registrasi DESC
                    LIMIT 20
                ");
                $findStmt->execute(array_merge($cleanPj, [$tgl_awal, $tgl_akhir]));
                $toAudit = $findStmt->fetchAll(\PDO::FETCH_COLUMN);

                foreach ($toAudit as $nr) {
                    $engine->checkPatient($nr);
                }

                if (!empty($toAudit)) {
                    $stmt = $this->db()->pdo()->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                }
            }
        }

        // Ambil daftar batch aktif untuk dropdown tambah ke batch
        $batches = $this->db('mlite_klaim_batch')->where('status_batch', 'draft')->toArray();

        return $this->draw('siapklaim.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'status_lanjut' => $status_lanjut,
            'search' => $search,
            'batches' => $batches,
            'total' => count($rows)
        ]);
    }

    /**
     * Audit Massal Kesiapan Klaim BPJS Rentang Tanggal Terpilih
     */
    public function anyAuditperiode()
    {
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $engine = $this->getClaimEngine();
        $cleanPj = $this->getCleanCarabayar();
        $inPlaceholders = implode(',', array_fill(0, count($cleanPj), '?'));

        // Prioritaskan evaluasi pasien yang belum pernah diaudit terlebih dahulu
        $sql = "
            SELECT reg_periksa.no_rawat 
            FROM reg_periksa
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN mlite_klaim_readiness r ON reg_periksa.no_rawat = r.no_rawat
            WHERE (penjab.kd_pj IN ($inPlaceholders) OR penjab.png_jawab LIKE '%BPJS%')
            AND reg_periksa.tgl_registrasi BETWEEN ? AND ?
            ORDER BY (r.id IS NULL) DESC, reg_periksa.tgl_registrasi DESC
            LIMIT 100
        ";
        $params = array_merge($cleanPj, [$tgl_awal, $tgl_akhir]);
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $siap = 0;
        $belum = 0;
        foreach ($patients as $nr) {
            $res = $engine->checkPatient($nr);
            if ($res['status_readiness'] !== 'belum_lengkap') {
                $siap++;
            } else {
                $belum++;
            }
        }

        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'total' => count($patients),
                'siap' => $siap,
                'belum' => $belum,
                'message' => "Audit Selesai: {$siap} Siap Klaim, {$belum} Belum Lengkap."
            ]);
            exit();
        }

        $dateLabel = ($tgl_awal === $tgl_akhir) ? $tgl_awal : "$tgl_awal s/d $tgl_akhir";
        $this->notify('success', "Audit Massal Selesai: Berhasil mengevaluasi " . count($patients) . " data pasien ($dateLabel). Ditemukan {$siap} pasien Siap Klaim dan {$belum} pasien Belum Lengkap.");

        $from = $_GET['from'] ?? '';
        if ($from === 'belumsiap') {
            redirect(url([ADMIN, 'vedika', 'belumsiap', '?tgl_awal=' . $tgl_awal . '&tgl_akhir=' . $tgl_akhir]));
        } else {
            redirect(url([ADMIN, 'vedika', 'siapklaim', '?tgl_awal=' . $tgl_awal . '&tgl_akhir=' . $tgl_akhir]));
        }
    }

    /**
     * Sub-Menu 4: Verifikasi Administrasi
     */
    public function anyVerifadmin()
    {
        $this->_addHeaderFiles();
        if (isset($_POST['save_verif_admin'])) {
            $no_rawat = str_replace('_', '/', $_POST['no_rawat']);
            $no_sep = $_POST['no_sep'] ?? '';
            $status = $_POST['verif_status'] ?? 'approved';
            $notes = $_POST['verif_notes'] ?? '';
            $user = $this->core->getUserInfo('username', null, true);

            $this->getClaimEngine()->saveVerification($no_rawat, $no_sep, 'admin', $status, $notes, $user);
            $this->notify('success', 'Verifikasi Administrasi berhasil disimpan.');
            redirect(url([ADMIN, 'vedika', 'verifadmin']));
        }

        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, pasien.no_peserta, pasien.no_ktp, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep) AS no_sep,
                   COALESCE(s.no_rujukan, si.no_rujukan) AS no_rujukan,
                   COALESCE(v.verif_admin_status, 'pending') AS verif_admin_status,
                   v.verif_admin_notes, v.verif_admin_user, v.verif_admin_at
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            LEFT JOIN mlite_klaim_verifikasi v ON reg_periksa.no_rawat = v.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND penjab.png_jawab LIKE '%BPJS%'
            ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100
        ";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute([$tgl_awal, $tgl_akhir]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('verifadmin.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode
        ]);
    }

    /**
     * Sub-Menu 5: Verifikasi Rekam Medis
     */
    public function anyVerifmedis()
    {
        $this->_addHeaderFiles();
        if (isset($_POST['save_verif_medis'])) {
            $no_rawat = str_replace('_', '/', $_POST['no_rawat']);
            $no_sep = $_POST['no_sep'] ?? '';
            $status = $_POST['verif_status'] ?? 'approved';
            $notes = $_POST['verif_notes'] ?? '';
            $user = $this->core->getUserInfo('username', null, true);

            $this->getClaimEngine()->saveVerification($no_rawat, $no_sep, 'medis', $status, $notes, $user);
            $this->notify('success', 'Verifikasi Medis internal berhasil disimpan.');
            redirect(url([ADMIN, 'vedika', 'verifmedis']));
        }

        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep) AS no_sep,
                   COALESCE(v.verif_medis_status, 'pending') AS verif_medis_status,
                   v.verif_medis_notes, v.verif_medis_user, v.verif_medis_at,
                   (SELECT COUNT(*) FROM diagnosa_pasien WHERE diagnosa_pasien.no_rawat = reg_periksa.no_rawat) AS jml_diag,
                   (SELECT COUNT(*) FROM prosedur_pasien WHERE prosedur_pasien.no_rawat = reg_periksa.no_rawat) AS jml_proc,
                   (SELECT COUNT(*) FROM mlite_odontogram WHERE mlite_odontogram.no_rawat = reg_periksa.no_rawat) AS jml_odonto
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            LEFT JOIN mlite_klaim_verifikasi v ON reg_periksa.no_rawat = v.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND penjab.png_jawab LIKE '%BPJS%'
            ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100
        ";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute([$tgl_awal, $tgl_akhir]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('verifmedis.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode
        ]);
    }

    /**
     * Sub-Menu 6: Coding Diagnosis & Prosedur Terpadu
     */
    public function anyCoding()
    {
        $this->_addHeaderFiles();
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep) AS no_sep
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND penjab.png_jawab LIKE '%BPJS%'
        ";
        $params = [$tgl_awal, $tgl_akhir];

        if (!empty($search)) {
            $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 50";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Append diagnosa & prosedur detail
        foreach ($rows as $k => $row) {
            $dStmt = $this->db()->pdo()->prepare("
                SELECT diagnosa_pasien.*, penyakit.nm_penyakit 
                FROM diagnosa_pasien 
                JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit 
                WHERE diagnosa_pasien.no_rawat = ? 
                ORDER BY diagnosa_pasien.prioritas ASC
            ");
            $dStmt->execute([$row['no_rawat']]);
            $rows[$k]['diagnosas'] = $dStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $pStmt = $this->db()->pdo()->prepare("
                SELECT prosedur_pasien.*, icd9.deskripsi_panjang 
                FROM prosedur_pasien 
                JOIN icd9 ON prosedur_pasien.kode = icd9.kode 
                WHERE prosedur_pasien.no_rawat = ? 
                ORDER BY prosedur_pasien.prioritas ASC
            ");
            $pStmt->execute([$row['no_rawat']]);
            $rows[$k]['prosedurs'] = $pStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        }

        return $this->draw('coding.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'search' => $search
        ]);
    }

    /**
     * Sub-Menu 7: INA-CBG Grouping Hub
     */
    public function anyGrouping()
    {
        $this->_addHeaderFiles();
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];
        $status_grouping = $_GET['status_grouping'] ?? '';
        $search = $_GET['search'] ?? '';

        $eklaim_config = [
            'url' => $this->settings->get('vedika.eklaim_url') ?: 'Belum disetting',
            'key_status' => !empty($this->settings->get('vedika.eklaim_key')) ? 'Terpasang' : 'Belum Terpasang',
            'kelasrs' => $this->settings->get('vedika.eklaim_kelasrs', 'CP'),
            'payor_id' => $this->settings->get('vedika.eklaim_payor_id', '3'),
            'payor_cd' => $this->settings->get('vedika.eklaim_payor_cd', 'JKN')
        ];

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, pasien.no_peserta, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep) AS no_sep
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND penjab.png_jawab LIKE '%BPJS%'
            AND (s.no_sep IS NOT NULL OR si.no_sep IS NOT NULL)
        ";
        $params = [$tgl_awal, $tgl_akhir];

        if (!empty($search)) {
            $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ? OR COALESCE(s.no_sep, si.no_sep) LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rawRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $rows = [];
        $totalSudah = 0;
        $totalBelum = 0;

        foreach ($rawRows as $row) {
            // Cek Diagnosa Utama (ICD-10)
            $dStmt = $this->db()->pdo()->prepare("
                SELECT diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit 
                FROM diagnosa_pasien 
                JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit 
                WHERE diagnosa_pasien.no_rawat = ? AND diagnosa_pasien.prioritas = 1 
                LIMIT 1
            ");
            $dStmt->execute([$row['no_rawat']]);
            $row['diagnosa_utama'] = $dStmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            // Cek Jumlah Prosedur (ICD-9-CM)
            $pStmt = $this->db()->pdo()->prepare("SELECT COUNT(*) FROM prosedur_pasien WHERE no_rawat = ?");
            $pStmt->execute([$row['no_rawat']]);
            $row['total_prosedur'] = (int)$pStmt->fetchColumn();

            // Cek Tarif Billing RS
            $bStmt = $this->db()->pdo()->prepare("SELECT jumlah_total FROM mlite_billing WHERE no_rawat = ? LIMIT 1");
            $bStmt->execute([$row['no_rawat']]);
            $row['tarif_rs'] = (float)($bStmt->fetchColumn() ?: 0);

            // Cek Riwayat Grouping di mlite_eklaim_logs
            $logStmt = $this->db()->pdo()->prepare("
                SELECT method, created_at, response_data 
                FROM mlite_eklaim_logs 
                WHERE nomor_sep = ? 
                ORDER BY id DESC LIMIT 1
            ");
            $logStmt->execute([$row['no_sep']]);
            $latestLog = $logStmt->fetch(\PDO::FETCH_ASSOC);

            // Cek apakah sudah pernah grouping
            $groupCheck = $this->db()->pdo()->prepare("
                SELECT COUNT(*) FROM mlite_eklaim_logs 
                WHERE nomor_sep = ? AND method IN ('grouper_inacbg_1', 'grouper_idrg', 'final_claim', 'set_claim_data')
            ");
            $groupCheck->execute([$row['no_sep']]);
            $isGrouped = ($groupCheck->fetchColumn() > 0);

            $row['is_grouped'] = $isGrouped;
            $row['latest_log'] = $latestLog ?: null;

            if ($isGrouped) {
                $totalSudah++;
            } else {
                $totalBelum++;
            }

            // Filter status_grouping jika diisi
            if ($status_grouping === 'sudah' && !$isGrouped) {
                continue;
            }
            if ($status_grouping === 'belum' && $isGrouped) {
                continue;
            }

            $rows[] = $row;
        }

        return $this->draw('grouping.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'search' => $search,
            'status_grouping' => $status_grouping,
            'eklaim_config' => $eklaim_config,
            'total_pasien' => count($rawRows),
            'total_sudah' => $totalSudah,
            'total_belum' => $totalBelum
        ]);
    }

    /**
     * Sub-Menu 8: Manajemen Dokumen Klaim Digital
     */
    public function anyDokumen()
    {
        $this->_addHeaderFiles();
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                   pasien.nm_pasien, poliklinik.nm_poli, dokter.nm_dokter,
                   COALESCE(s.no_sep, si.no_sep) AS no_sep
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ?
            AND penjab.png_jawab LIKE '%BPJS%'
        ";
        $params = [$tgl_awal, $tgl_akhir];

        if (!empty($search)) {
            $sql .= " AND (reg_periksa.no_rawat LIKE ? OR reg_periksa.no_rkm_medis LIKE ? OR pasien.nm_pasien LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 50";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $k => $row) {
            $bStmt = $this->db()->pdo()->prepare("
                SELECT berkas_digital_perawatan.*, master_berkas_digital.nama 
                FROM berkas_digital_perawatan 
                JOIN master_berkas_digital ON master_berkas_digital.kode = berkas_digital_perawatan.kode 
                WHERE berkas_digital_perawatan.no_rawat = ?
            ");
            $bStmt->execute([$row['no_rawat']]);
            $rows[$k]['berkas'] = $bStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        }

        return $this->draw('dokumen.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'search' => $search
        ]);
    }

    /**
     * Sub-Menu 9: Pengajuan Klaim Kolektif (Batching)
     */
    public function anyKolektif()
    {
        $this->_addHeaderFiles();
        $user = $this->core->getUserInfo('username', null, true);

        // Handle Create Batch
        if (isset($_POST['create_batch'])) {
            $nama_batch = trim($_POST['nama_batch'] ?? '');
            $periode_bulan = trim($_POST['periode_bulan'] ?? date('Y-m'));
            $jenis = $_POST['jenis_pelayanan'] ?? 'Ralan';
            $notes = $_POST['notes'] ?? '';

            $kode_batch = 'BATCH-' . strtoupper(substr($jenis, 0, 3)) . '-' . str_replace('-', '', $periode_bulan) . '-' . substr(uniqid(), -4);

            $stmt = $this->db()->pdo()->prepare("
                INSERT INTO mlite_klaim_batch (
                    kode_batch, nama_batch, periode_bulan, jenis_pelayanan, status_batch, notes, created_at, created_by
                ) VALUES (?, ?, ?, ?, 'draft', ?, NOW(), ?)
            ");
            $stmt->execute([$kode_batch, $nama_batch, $periode_bulan, $jenis, $notes, $user]);

            $this->getClaimEngine()->logAuditTrail('-', '-', 'BATCH_CREATE', 'status_batch', 'none', 'draft', $user, "Membuat batch $kode_batch");
            $this->notify('success', "Batch klaim $kode_batch berhasil dibuat.");
            redirect(url([ADMIN, 'vedika', 'kolektif']));
        }

        // Handle Add Patient to Batch
        if (isset($_POST['add_to_batch'])) {
            $batch_id = intval($_POST['batch_id'] ?? 0);
            $no_rawats = $_POST['selected_rawat'] ?? [];

            if ($batch_id > 0 && is_array($no_rawats) && count($no_rawats) > 0) {
                $batch = $this->db('mlite_klaim_batch')->where('id', $batch_id)->oneArray();
                if ($batch && $batch['status_batch'] === 'draft') {
                    $addedCount = 0;
                    foreach ($no_rawats as $nr) {
                        $nr = str_replace('_', '/', $nr);

                        // Ambil data pasien & kesiapan klaim
                        $check = $this->getClaimEngine()->checkPatient($nr);
                        if ($check['status_readiness'] === 'belum_lengkap') {
                            continue; // Skip pasien yang belum lengkap persyaratannya!
                        }

                        $p = $check['pasien'];
                        $sep = $check['sep']['no_sep'] ?? '-';

                        // Cek apakah SEP sudah ada di batch aktif lain (mencegah klaim ganda)
                        $dupStmt = $this->db()->pdo()->prepare("
                            SELECT COUNT(*) FROM mlite_klaim_batch_detail bd
                            JOIN mlite_klaim_batch b ON bd.batch_id = b.id
                            WHERE bd.no_sep = ? AND b.status_batch != 'completed'
                        ");
                        $dupStmt->execute([$sep]);
                        if ($dupStmt->fetchColumn() > 0) {
                            continue; // Skip duplikasi!
                        }

                        // Simpan ke batch detail
                        $billStmt = $this->db()->pdo()->prepare("SELECT jumlah_total FROM mlite_billing WHERE no_rawat = ? LIMIT 1");
                        $billStmt->execute([$nr]);
                        $tarifRs = $billStmt->fetchColumn() ?: 0;

                        $ins = $this->db()->pdo()->prepare("
                            INSERT IGNORE INTO mlite_klaim_batch_detail (
                                batch_id, no_rawat, no_sep, no_rkm_medis, nama_pasien, tgl_pelayanan,
                                tarif_rs, status_klaim, added_at, added_by
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), ?)
                        ");
                        $ins->execute([
                            $batch_id, $nr, $sep, $p['no_rkm_medis'], $p['nm_pasien'], $p['tgl_registrasi'],
                            $tarifRs, $user
                        ]);
                        $addedCount++;
                    }

                    // Rekalkulasi total batch
                    $this->recalculateBatch($batch_id);

                    $this->notify('success', "$addedCount pasien berhasil ditambahkan ke batch klaim.");
                } else {
                    $this->notify('failure', 'Batch tidak ditemukan atau sudah dikunci.');
                }
            }
            redirect(url([ADMIN, 'vedika', 'kolektif']));
        }

        // Handle Lock Batch
        if (isset($_POST['lock_batch'])) {
            $batch_id = intval($_POST['batch_id'] ?? 0);
            $stmt = $this->db()->pdo()->prepare("
                UPDATE mlite_klaim_batch SET status_batch = 'locked', locked_at = NOW(), locked_by = ? WHERE id = ?
            ");
            $stmt->execute([$user, $batch_id]);
            $this->getClaimEngine()->logAuditTrail('-', '-', 'BATCH_LOCK', 'status_batch', 'draft', 'locked', $user, "Mengunci batch #$batch_id");
            $this->notify('success', 'Batch klaim berhasil dikunci untuk persiapan pengajuan resmi.');
            redirect(url([ADMIN, 'vedika', 'kolektif']));
        }

        // Ambil daftar seluruh batch klaim
        $batches = $this->db('mlite_klaim_batch')->desc('id')->toArray();
        foreach ($batches as &$b) {
            $dStmt = $this->db()->pdo()->prepare("SELECT COUNT(*) FROM mlite_klaim_batch_detail WHERE batch_id = ?");
            $dStmt->execute([$b['id']]);
            $b['detail_count'] = $dStmt->fetchColumn();
        }

        return $this->draw('kolektif.html', [
            'batches' => $batches
        ]);
    }

    /**
     * Hitung ulang total pasien dan tarif dalam batch
     */
    protected function recalculateBatch($batch_id)
    {
        $stmt = $this->db()->pdo()->prepare("
            SELECT COUNT(*) AS total_pasien, SUM(tarif_rs) AS total_rs, SUM(tarif_inacbg) AS total_inacbg 
            FROM mlite_klaim_batch_detail 
            WHERE batch_id = ?
        ");
        $stmt->execute([$batch_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $upd = $this->db()->pdo()->prepare("
            UPDATE mlite_klaim_batch SET 
                total_pasien = ?, 
                total_tarif_rs = ?, 
                total_tarif_inacbg = ? 
            WHERE id = ?
        ");
        $upd->execute([
            $row['total_pasien'] ?: 0,
            $row['total_rs'] ?: 0.00,
            $row['total_inacbg'] ?: 0.00,
            $batch_id
        ]);
    }

    /**
     * Export Batch Klaim ke CSV/Excel
     */
    public function getExportBatch($batch_id = null)
    {
        if (!$batch_id) {
            exit('Batch ID missing');
        }

        $batch = $this->db('mlite_klaim_batch')->where('id', $batch_id)->oneArray();
        if (!$batch) {
            exit('Batch not found');
        }

        $stmt = $this->db()->pdo()->prepare("
            SELECT * FROM mlite_klaim_batch_detail WHERE batch_id = ? ORDER BY id ASC
        ");
        $stmt->execute([$batch_id]);
        $details = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=batch_klaim_' . $batch['kode_batch'] . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['No', 'No SEP', 'No Rawat', 'No RM', 'Nama Pasien', 'Tgl Pelayanan', 'Kode INA-CBG', 'Tarif RS', 'Tarif INA-CBG', 'Status Klaim']);

        $no = 1;
        foreach ($details as $d) {
            fputcsv($out, [
                $no++,
                $d['no_sep'],
                $d['no_rawat'],
                $d['no_rkm_medis'],
                $d['nama_pasien'],
                $d['tgl_pelayanan'],
                $d['kode_inacbg'],
                $d['tarif_rs'],
                $d['tarif_inacbg'],
                $d['status_klaim']
            ]);
        }
        fclose($out);
        exit();
    }

    /**
     * Sub-Menu 10: Monitoring Klaim Terpadu
     */
    public function anyMonitoring()
    {
        $this->_addHeaderFiles();
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        $sql = "
            SELECT 
                r.no_rawat, r.no_sep, r.no_rkm_medis, r.tgl_registrasi, r.status_lanjut,
                p.nm_pasien, pol.nm_poli, dok.nm_dokter,
                r.score_percent, r.status_readiness,
                v.verif_admin_status, v.verif_medis_status, v.is_internal_verified,
                bd.status_klaim, b.kode_batch, b.status_batch
            FROM mlite_klaim_readiness r
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN poliklinik pol ON r.kd_poli = pol.kd_poli
            JOIN dokter dok ON r.kd_dokter = dok.kd_dokter
            LEFT JOIN mlite_klaim_verifikasi v ON r.no_rawat = v.no_rawat
            LEFT JOIN mlite_klaim_batch_detail bd ON r.no_rawat = bd.no_rawat
            LEFT JOIN mlite_klaim_batch b ON bd.batch_id = b.id
            WHERE r.tgl_registrasi BETWEEN ? AND ?
            ORDER BY r.tgl_registrasi DESC LIMIT 100
        ";
        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute([$tgl_awal, $tgl_akhir]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('monitoring.html', [
            'rows' => $rows,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode
        ]);
    }

    /**
     * Sub-Menu 11 & 12: Klaim Pending & Dispute BPJS
     */
    public function anyPending()
    {
        return $this->handleDisputePendingPage('pending');
    }

    public function anyDispute()
    {
        return $this->handleDisputePendingPage('dispute');
    }

    protected function handleDisputePendingPage($type = 'pending')
    {
        $this->_addHeaderFiles();
        $user = $this->core->getUserInfo('username', null, true);

        // Handle Add Issue / Update Response
        if (isset($_POST['save_issue'])) {
            $no_rawat = str_replace('_', '/', $_POST['no_rawat'] ?? '');
            $no_sep = $_POST['no_sep'] ?? '';
            $alasan = $_POST['alasan_bpjs'] ?? '';
            $dokumen_diminta = $_POST['dokumen_diminta'] ?? '';
            $tanggapan_rs = $_POST['tanggapan_rs'] ?? '';
            $status_dispute = $_POST['status_dispute'] ?? 'open';
            $deadline = !empty($_POST['deadline_tgl']) ? $_POST['deadline_tgl'] : date('Y-m-d', strtotime('+14 days'));

            $stmt = $this->db()->pdo()->prepare("
                INSERT INTO mlite_klaim_dispute (
                    no_rawat, no_sep, tipe_masalah, alasan_bpjs, dokumen_diminta, tanggapan_rs,
                    status_dispute, deadline_tgl, pj_user, created_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $no_rawat, $no_sep, $type, $alasan, $dokumen_diminta, $tanggapan_rs,
                $status_dispute, $deadline, $user, $user
            ]);

            $this->getClaimEngine()->logAuditTrail($no_rawat, $no_sep, strtoupper($type) . '_ENTRY', 'status_dispute', 'none', $status_dispute, $user, $alasan);
            $this->notify('success', "Data klaim $type berhasil dicatat.");
            redirect(url([ADMIN, 'vedika', $type]));
        }

        // Ambil daftar klaim pending / dispute
        $stmt = $this->db()->pdo()->prepare("
            SELECT d.*, p.nm_pasien, p.no_rkm_medis, r.status_lanjut, pol.nm_poli
            FROM mlite_klaim_dispute d
            JOIN reg_periksa r ON d.no_rawat = r.no_rawat
            JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
            JOIN poliklinik pol ON r.kd_poli = pol.kd_poli
            WHERE d.tipe_masalah = ?
            ORDER BY d.id DESC
        ");
        $stmt->execute([$type]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('pending_dispute.html', [
            'rows' => $rows,
            'type' => $type,
            'title' => ($type === 'pending') ? 'Manajemen Klaim Pending BPJS' : 'Manajemen Klaim Dispute BPJS'
        ]);
    }

    /**
     * Sub-Menu 13: Klaim Disetujui
     */
    public function anyDisetujui()
    {
        $this->_addHeaderFiles();
        $periode = $this->getActivePeriode();

        $stmt = $this->db()->pdo()->prepare("
            SELECT bd.*, b.kode_batch, b.periode_bulan, r.status_lanjut
            FROM mlite_klaim_batch_detail bd
            JOIN mlite_klaim_batch b ON bd.batch_id = b.id
            JOIN reg_periksa r ON bd.no_rawat = r.no_rawat
            WHERE b.periode_bulan = ? AND bd.status_klaim IN ('disetujui', 'dibayar')
            ORDER BY bd.id DESC
        ");
        $stmt->execute([$periode]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('disetujui.html', [
            'rows' => $rows,
            'periode' => $periode
        ]);
    }

    /**
     * Sub-Menu 14: Monitoring & Rekonsiliasi Pembayaran
     */
    public function anyPembayaran()
    {
        $this->_addHeaderFiles();
        $user = $this->core->getUserInfo('username', null, true);

        if (isset($_POST['save_pembayaran'])) {
            $no_bahp = trim($_POST['no_bahp'] ?? '');
            $tgl_bahp = $_POST['tgl_bahp'] ?? date('Y-m-d');
            $periode = $_POST['periode_klaim'] ?? date('Y-m');
            $batch_id = !empty($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
            $diajukan = floatval($_POST['total_diajukan'] ?? 0);
            $disetujui = floatval($_POST['total_disetujui'] ?? 0);
            $dibayar = floatval($_POST['total_dibayar'] ?? 0);
            $selisih = $diajukan - $dibayar;
            $status = ($dibayar >= $disetujui && $disetujui > 0) ? 'lunas' : 'dibayar_sebagian';
            $tgl_bayar = !empty($_POST['tgl_pembayaran']) ? $_POST['tgl_pembayaran'] : null;
            $notes = $_POST['notes'] ?? '';

            $stmt = $this->db()->pdo()->prepare("
                INSERT INTO mlite_klaim_pembayaran (
                    no_bahp, tgl_bahp, periode_klaim, batch_id, total_diajukan, total_disetujui,
                    total_dibayar, selisih_pembayaran, tgl_pembayaran, status_pembayaran, notes, created_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $no_bahp, $tgl_bahp, $periode, $batch_id, $diajukan, $disetujui,
                $dibayar, $selisih, $tgl_bayar, $status, $notes, $user
            ]);

            $this->getClaimEngine()->logAuditTrail('-', '-', 'PAYMENT_ENTRY', 'status_pembayaran', 'none', $status, $user, "Input BAHP $no_bahp");
            $this->notify('success', 'Data Berita Acara & Pembayaran berhasil disimpan.');
            redirect(url([ADMIN, 'vedika', 'pembayaran']));
        }

        $pembayarans = $this->db('mlite_klaim_pembayaran')->desc('id')->toArray();
        $batches = $this->db('mlite_klaim_batch')->toArray();

        return $this->draw('pembayaran.html', [
            'pembayarans' => $pembayarans,
            'batches' => $batches
        ]);
    }

    /**
     * Sub-Menu 15: Arsip Klaim Digital Historis
     */
    public function anyArsip()
    {
        $this->_addHeaderFiles();
        $q = $_GET['q'] ?? '';
        $rows = [];

        if (!empty($q)) {
            $sql = "
                SELECT reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.status_lanjut,
                       pasien.nm_pasien, pasien.no_peserta, poliklinik.nm_poli, dokter.nm_dokter,
                       COALESCE(s.no_sep, si.no_sep) AS no_sep,
                       b.jumlah_total AS billing_total
                FROM reg_periksa
                JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
                LEFT JOIN bridging_sep_internal si ON reg_periksa.no_rawat = si.no_rawat
                LEFT JOIN mlite_billing b ON reg_periksa.no_rawat = b.no_rawat
                WHERE reg_periksa.no_rawat LIKE ? 
                   OR reg_periksa.no_rkm_medis LIKE ? 
                   OR pasien.nm_pasien LIKE ? 
                   OR s.no_sep LIKE ?
                ORDER BY reg_periksa.tgl_registrasi DESC LIMIT 100
            ";
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute(["%$q%", "%$q%", "%$q%", "%$q%"]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $this->draw('arsip.html', [
            'rows' => $rows,
            'q' => $q
        ]);
    }

    /**
     * Sub-Menu 16: Laporan Klaim Casemix
     */
    public function anyLaporan()
    {
        $this->_addHeaderFiles();
        $dateRange = $this->getActiveDateRange();
        $tgl_awal = $dateRange['tgl_awal'];
        $tgl_akhir = $dateRange['tgl_akhir'];
        $periode = $dateRange['periode'];

        // Rekap per Poliklinik
        $stmtPoli = $this->db()->pdo()->prepare("
            SELECT poliklinik.nm_poli, COUNT(reg_periksa.no_rawat) AS total_kunjungan,
                   COUNT(s.no_sep) AS total_sep,
                   COUNT(CASE WHEN r.status_readiness = 'siap_batch' THEN 1 END) AS siap_klaim
            FROM reg_periksa
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            LEFT JOIN bridging_sep s ON reg_periksa.no_rawat = s.no_rawat
            LEFT JOIN mlite_klaim_readiness r ON reg_periksa.no_rawat = r.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ? AND penjab.png_jawab LIKE '%BPJS%'
            GROUP BY poliklinik.kd_poli
            ORDER BY total_kunjungan DESC
        ");
        $stmtPoli->execute([$tgl_awal, $tgl_akhir]);
        $rekapPoli = $stmtPoli->fetchAll(\PDO::FETCH_ASSOC);

        // Rekap Top 10 Penyakit Gigi/Mulut (RSGM)
        $stmtPenyakit = $this->db()->pdo()->prepare("
            SELECT penyakit.kd_penyakit, penyakit.nm_penyakit, COUNT(*) AS jumlah
            FROM diagnosa_pasien
            JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
            JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
            WHERE reg_periksa.tgl_registrasi BETWEEN ? AND ? AND diagnosa_pasien.prioritas = 1
            GROUP BY diagnosa_pasien.kd_penyakit
            ORDER BY jumlah DESC LIMIT 10
        ");
        $stmtPenyakit->execute([$tgl_awal, $tgl_akhir]);
        $topPenyakit = $stmtPenyakit->fetchAll(\PDO::FETCH_ASSOC);

        return $this->draw('laporan.html', [
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'periode' => $periode,
            'rekapPoli' => $rekapPoli,
            'topPenyakit' => $topPenyakit
        ]);
    }
}


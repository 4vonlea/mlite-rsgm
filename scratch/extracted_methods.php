    public function anyAns5manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            return $this->getAns5hapus($_GET['id']);
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = $this->db('ans5_status_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans5_status_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('ans5_status_anestesi.*, COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
            ->desc('ans5_status_anestesi.id')
            ->toArray();

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        foreach ($query as $k => $v) {
            $query[$k]['nomor'] = $k + 1;
            if (!empty($v['tanggal']) && $v['tanggal'] != '0000-00-00') {
                $p = explode('-', $v['tanggal']);
                $query[$k]['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
            } else {
                $query[$k]['tgl_indo'] = '-';
            }
        }

        $this->tpl->set('data', $query);
        $this->tpl->set('list', $query);
        return $this->draw('ans5/manage.html', ['data' => $query, 'list' => $query]);
    }

    public function getAns5manage()
    {
        return $this->anyAns5manage();
    }

    public function anyAns5form($id = null)
    {
        // Handle AJAX patient lookup
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, pasien.gol_darah')
                ->oneArray();

            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode([
                    'success' => true,
                    'no_rawat' => $patient['no_rawat'],
                    'no_rkm_medis' => $patient['no_rkm_medis'],
                    'nm_pasien' => $patient['nm_pasien'],
                    'jk' => $patient['jk'],
                    'umur' => $patient['umur'],
                    'tgl_lahir' => $patient['tgl_lahir'],
                    'gol_darah' => $patient['gol_darah']
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }

        $data = [];
        if ($id) {
            $data = $this->db('ans5_status_anestesi')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans5_status_anestesi.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('ans5_status_anestesi.id', $id)
                ->select('ans5_status_anestesi.*, COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
                ->oneArray();
        }

        if (empty($data) || !is_array($data)) {
            $data = [];
        }

        $defaults = [
            'id' => '', 'no_rawat' => '', 'no_rkm_medis' => '', 'nm_pasien' => '', 'jk' => '', 'umur' => '',
            'tanggal' => date('Y-m-d'), 'diagnosa_prabedah' => '', 'diagnosa_pascabedah' => '',
            'status_fisik' => '1', 'gol_darah' => '', 'rh' => '+', 'bb' => '', 'tb' => '',
            'tindakan' => '', 'dr_anestesi' => '', 'kd_dokter_anestesi' => '',
            'penata_anestesi' => '', 'dr_operator' => '', 'kd_dokter_operator' => '',
            'waktu_mulai_pembiusan' => '08:00', 'waktu_selesai_pembiusan' => '09:30', 'lama_pembiusan' => '1 Jam 30 Menit',
            'preinduksi_td_sistolik' => '120', 'preinduksi_td_diastolik' => '80', 'preinduksi_nadi' => '78',
            'preinduksi_rr' => '18', 'preinduksi_suhu' => '36.5', 'preinduksi_vas' => '0',
            'grid_data' => '', 'masalah' => '', 'prosedur_loading_cairan' => '', 'prosedur_premedikasi' => '', 'prosedur_maintenance' => '',
            'jenis_anestesi_general' => 'Y', 'jenis_anestesi_general_tipe' => 'GETA',
            'jenis_anestesi_regional' => 'N', 'jenis_anestesi_regional_tipe' => '', 'jenis_anestesi_lain' => '',
            'jalan_napas_nasal' => 'N', 'jalan_napas_facemask' => 'N', 'jalan_napas_lma' => 'N', 'jalan_napas_orotrakeal' => 'Y', 'jalan_napas_lain' => '',
            'posisi_supine' => 'Y', 'posisi_lateral' => 'N', 'posisi_prone' => 'N', 'posisi_lithotomi' => 'N', 'posisi_lain' => '',
            'penyulit_pembedahan' => '', 'keadaan_akhir_pembedahan' => '', 'dokter_anestesi_ttd' => ''
        ];

        foreach ($defaults as $k => $v) {
            if (!isset($data[$k]) || $data[$k] === null) {
                $data[$k] = $v;
            }
        }
        $data['grid_data_json'] = htmlspecialchars($data['grid_data'] ?: '{}', ENT_QUOTES, 'UTF-8');

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        $pasien_list = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->desc('reg_periksa.tgl_registrasi')
            ->limit(30)
            ->toArray();

        $this->tpl->set('data', $data);
        $this->tpl->set('dokter', $dokter);
        $this->tpl->set('pasien_list', $pasien_list);

        return $this->draw('ans5/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'pasien_list' => $pasien_list
        ]);
    }

    public function getAns5form($id = null)
    {
        return $this->anyAns5form($id);
    }

    public function anyAns5save()
    {
        if (empty($_POST)) {
            redirect(url([ADMIN, 'form_rme', 'ans5manage']));
        }

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rkm_medis) && !empty($no_rawat)) {
            $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if ($reg) {
                $no_rkm_medis = $reg['no_rkm_medis'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'jam' => !empty($_POST['jam']) ? $_POST['jam'] : date('H:i:s'),
            'diagnosa_prabedah' => trim($_POST['diagnosa_prabedah'] ?? ''),
            'diagnosa_pascabedah' => trim($_POST['diagnosa_pascabedah'] ?? ''),
            'status_fisik' => trim($_POST['status_fisik'] ?? '1'),
            'gol_darah' => trim($_POST['gol_darah'] ?? ''),
            'rh' => trim($_POST['rh'] ?? '+'),
            'bb' => trim($_POST['bb'] ?? ''),
            'tb' => trim($_POST['tb'] ?? ''),
            'tindakan' => trim($_POST['tindakan'] ?? ''),
            'dr_anestesi' => trim($_POST['dr_anestesi'] ?? ''),
            'kd_dokter_anestesi' => trim($_POST['kd_dokter_anestesi'] ?? ''),
            'penata_anestesi' => trim($_POST['penata_anestesi'] ?? ''),
            'dr_operator' => trim($_POST['dr_operator'] ?? ''),
            'kd_dokter_operator' => trim($_POST['kd_dokter_operator'] ?? ''),
            'waktu_mulai_pembiusan' => trim($_POST['waktu_mulai_pembiusan'] ?? ''),
            'waktu_selesai_pembiusan' => trim($_POST['waktu_selesai_pembiusan'] ?? ''),
            'lama_pembiusan' => trim($_POST['lama_pembiusan'] ?? ''),
            'preinduksi_td_sistolik' => trim($_POST['preinduksi_td_sistolik'] ?? ''),
            'preinduksi_td_diastolik' => trim($_POST['preinduksi_td_diastolik'] ?? ''),
            'preinduksi_nadi' => trim($_POST['preinduksi_nadi'] ?? ''),
            'preinduksi_rr' => trim($_POST['preinduksi_rr'] ?? ''),
            'preinduksi_suhu' => trim($_POST['preinduksi_suhu'] ?? ''),
            'preinduksi_vas' => trim($_POST['preinduksi_vas'] ?? ''),
            'grid_data' => trim($_POST['grid_data'] ?? ''),
            'masalah' => trim($_POST['masalah'] ?? ''),
            'prosedur_loading_cairan' => trim($_POST['prosedur_loading_cairan'] ?? ''),
            'prosedur_premedikasi' => trim($_POST['prosedur_premedikasi'] ?? ''),
            'prosedur_maintenance' => trim($_POST['prosedur_maintenance'] ?? ''),
            'jenis_anestesi_general' => (!empty($_POST['jenis_anestesi_general']) && $_POST['jenis_anestesi_general'] == 'Y') ? 'Y' : 'N',
            'jenis_anestesi_general_tipe' => trim($_POST['jenis_anestesi_general_tipe'] ?? ''),
            'jenis_anestesi_regional' => (!empty($_POST['jenis_anestesi_regional']) && $_POST['jenis_anestesi_regional'] == 'Y') ? 'Y' : 'N',
            'jenis_anestesi_regional_tipe' => trim($_POST['jenis_anestesi_regional_tipe'] ?? ''),
            'jenis_anestesi_lain' => trim($_POST['jenis_anestesi_lain'] ?? ''),
            'jalan_napas_nasal' => (!empty($_POST['jalan_napas_nasal']) && $_POST['jalan_napas_nasal'] == 'Y') ? 'Y' : 'N',
            'jalan_napas_facemask' => (!empty($_POST['jalan_napas_facemask']) && $_POST['jalan_napas_facemask'] == 'Y') ? 'Y' : 'N',
            'jalan_napas_lma' => (!empty($_POST['jalan_napas_lma']) && $_POST['jalan_napas_lma'] == 'Y') ? 'Y' : 'N',
            'jalan_napas_orotrakeal' => (!empty($_POST['jalan_napas_orotrakeal']) && $_POST['jalan_napas_orotrakeal'] == 'Y') ? 'Y' : 'N',
            'jalan_napas_lain' => trim($_POST['jalan_napas_lain'] ?? ''),
            'posisi_supine' => (!empty($_POST['posisi_supine']) && $_POST['posisi_supine'] == 'Y') ? 'Y' : 'N',
            'posisi_lateral' => (!empty($_POST['posisi_lateral']) && $_POST['posisi_lateral'] == 'Y') ? 'Y' : 'N',
            'posisi_prone' => (!empty($_POST['posisi_prone']) && $_POST['posisi_prone'] == 'Y') ? 'Y' : 'N',
            'posisi_lithotomi' => (!empty($_POST['posisi_lithotomi']) && $_POST['posisi_lithotomi'] == 'Y') ? 'Y' : 'N',
            'posisi_lain' => trim($_POST['posisi_lain'] ?? ''),
            'penyulit_pembedahan' => trim($_POST['penyulit_pembedahan'] ?? ''),
            'keadaan_akhir_pembedahan' => trim($_POST['keadaan_akhir_pembedahan'] ?? ''),
            'dokter_anestesi_ttd' => trim($_POST['dokter_anestesi_ttd'] ?? ''),
            'updated_at' => $now
        ];

        if ($id) {
            $this->db('ans5_status_anestesi')->where('id', $id)->save($saveData);
            $this->setNotify('success', 'Data Status Anestesi berhasil diperbarui.');
        } else {
            $saveData['created_at'] = $now;
            $this->db('ans5_status_anestesi')->save($saveData);
            $this->setNotify('success', 'Data Status Anestesi berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'ans5manage']));
    }

    public function postAns5save()
    {
        return $this->anyAns5save();
    }

    public function anyAns5hapus($id = null)
    {
        return $this->getAns5hapus($id);
    }

    public function getAns5hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('ans5_status_anestesi')->where('id', $id)->delete();
            $this->setNotify('success', 'Data Status Anestesi berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'ans5manage']));
    }

    public function anyAns5cetak($id = null)
    {
        return $this->getAns5cetak($id);
    }

    public function getAns5cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('ans5_status_anestesi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = ans5_status_anestesi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('ans5_status_anestesi.id', $id)
            ->select('ans5_status_anestesi.*, COALESCE(ans5_status_anestesi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data Status Anestesi tidak ditemukan!');
            redirect(url([ADMIN, 'form_rme', 'ans5manage']));
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tanggal']) && $data['tanggal'] != '0000-00-00') {
            $p = explode('-', $data['tanggal']);
            $data['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
        } else {
            $data['tgl_indo'] = '-';
        }

        $gridData = [];
        if (!empty($data['grid_data'])) {
            $gridData = json_decode($data['grid_data'], true);
        }
        if (!is_array($gridData)) {
            $gridData = [];
        }

        $chartSvg = $this->renderAns5SvgChart($gridData);
        $gridBuild = $this->buildAns5CetakGrid($gridData, $chartSvg);

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'html_time_headers' => $gridBuild['time_headers'],
            'html_grid_body' => $gridBuild['body'],
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            try {
                $oldErr = error_reporting(0);
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 6,
                    'margin_bottom' => 6,
                    'tempDir' => BASE_DIR . '/tmp'
                ]);

                $html = $this->draw('ans5/cetak.html', $viewParams);
                $mpdf->WriteHTML($html);
                $filename = 'ANS5_Status_Anestesi_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';

                while (ob_get_level()) {
                    ob_end_clean();
                }
                $mpdf->Output($filename, 'I');
                error_reporting($oldErr);
                exit();
            } catch (\Throwable $e) {
                // fallback to HTML
            }
        }

        echo $this->draw('ans5/cetak.html', $viewParams);
        exit();
    }

    private function renderAns5SvgChart($gridData)
    {
        $w = 640;
        $h = 160;
        $padLeft = 32;
        $padRight = 20;
        $padTop = 10;
        $padBottom = 16;
        $chartW = $w - $padLeft - $padRight;
        $chartH = $h - $padTop - $padBottom;
        $minY = 40;
        $maxY = 200;

        $getY = function($val) use ($padTop, $chartH, $minY, $maxY) {
            $val = max($minY, min($maxY, (float)$val));
            return $padTop + $chartH - (($val - $minY) / ($maxY - $minY)) * $chartH;
        };

        $getX = function($colIdx) use ($padLeft, $chartW) {
            return $padLeft + ($colIdx / 15) * $chartW;
        };

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" style="width: 100%; height: 160px; display: block;">';

        // Horizontal grid lines
        $levels = [40, 60, 80, 100, 120, 140, 160, 180, 200];
        foreach ($levels as $l) {
            $y = $getY($l);
            $colStroke = ($l == 100 || $l == 140) ? '#95a5a6' : '#e0e0e0';
            $strokeW = ($l == 100 || $l == 140) ? '1' : '0.6';
            $svg .= '<line x1="' . $padLeft . '" y1="' . $y . '" x2="' . ($padLeft + $chartW) . '" y2="' . $y . '" stroke="' . $colStroke . '" stroke-width="' . $strokeW . '" />';
            $svg .= '<text x="' . ($padLeft - 4) . '" y="' . ($y + 2.5) . '" font-size="7.5" fill="#555" text-anchor="end" font-family="Arial">' . $l . '</text>';
        }

        // Vertical interval lines
        for ($c = 0; $c < 16; $c++) {
            $x = $getX($c);
            $strokeCol = ($c % 4 == 0) ? '#95a5a6' : '#ececec';
            $strokeW = ($c % 4 == 0) ? '1' : '0.6';
            $svg .= '<line x1="' . $x . '" y1="' . $padTop . '" x2="' . $x . '" y2="' . ($padTop + $chartH) . '" stroke="' . $strokeCol . '" stroke-width="' . $strokeW . '" />';
        }

        // Extract points
        $nadiPoints = [];
        $sistolikPoints = [];
        $diastolikPoints = [];
        $suhuPoints = [];
        $events = [];

        $rows = $gridData['rows'] ?? [];

        for ($c = 0; $c < 16; $c++) {
            $x = $getX($c);

            // Nadi
            if (isset($rows['vital_nadi'][$c]) && is_numeric($rows['vital_nadi'][$c])) {
                $ny = $getY($rows['vital_nadi'][$c]);
                $nadiPoints[] = ['x' => $x, 'y' => $ny];
            }

            // Sistolik
            if (isset($rows['vital_sistolik'][$c]) && is_numeric($rows['vital_sistolik'][$c])) {
                $sy = $getY($rows['vital_sistolik'][$c]);
                $sistolikPoints[] = ['x' => $x, 'y' => $sy];
            }

            // Diastolik
            if (isset($rows['vital_diastolik'][$c]) && is_numeric($rows['vital_diastolik'][$c])) {
                $dy = $getY($rows['vital_diastolik'][$c]);
                $diastolikPoints[] = ['x' => $x, 'y' => $dy];
            }

            // Suhu
            if (isset($rows['vital_suhu'][$c]) && is_numeric($rows['vital_suhu'][$c])) {
                $tempVal = (float)$rows['vital_suhu'][$c];
                $ty = $getY(40 + ($tempVal - 35) * 12);
                $suhuPoints[] = ['x' => $x, 'y' => $ty];
            }

            // Event
            if (!empty($rows['vital_event'][$c])) {
                $events[] = ['x' => $x, 'char' => $rows['vital_event'][$c]];
            }
        }

        // Draw connecting polylines
        $makePath = function($pts) {
            $d = '';
            foreach ($pts as $idx => $p) {
                $d .= ($idx == 0 ? 'M' : ' L') . round($p['x'], 1) . ',' . round($p['y'], 1);
            }
            return $d;
        };

        if (count($nadiPoints) > 1) {
            $svg .= '<path d="' . $makePath($nadiPoints) . '" fill="none" stroke="#e74c3c" stroke-width="1.2" />';
        }
        if (count($sistolikPoints) > 1) {
            $svg .= '<path d="' . $makePath($sistolikPoints) . '" fill="none" stroke="#2980b9" stroke-width="1.2" stroke-dasharray="3,2" />';
        }
        if (count($diastolikPoints) > 1) {
            $svg .= '<path d="' . $makePath($diastolikPoints) . '" fill="none" stroke="#27ae60" stroke-width="1.2" stroke-dasharray="3,2" />';
        }
        if (count($suhuPoints) > 1) {
            $svg .= '<path d="' . $makePath($suhuPoints) . '" fill="none" stroke="#f39c12" stroke-width="1" />';
        }

        // Draw markers
        foreach ($nadiPoints as $p) {
            $svg .= '<circle cx="' . round($p['x'], 1) . '" cy="' . round($p['y'], 1) . '" r="2.8" fill="#e74c3c" />';
        }
        foreach ($sistolikPoints as $p) {
            $svg .= '<text x="' . round($p['x'], 1) . '" y="' . round($p['y'] + 3, 1) . '" font-size="10" font-weight="bold" fill="#2980b9" text-anchor="middle" font-family="Arial">∨</text>';
        }
        foreach ($diastolikPoints as $p) {
            $svg .= '<text x="' . round($p['x'], 1) . '" y="' . round($p['y'] + 3, 1) . '" font-size="10" font-weight="bold" fill="#27ae60" text-anchor="middle" font-family="Arial">∧</text>';
        }
        foreach ($suhuPoints as $p) {
            $svg .= '<circle cx="' . round($p['x'], 1) . '" cy="' . round($p['y'], 1) . '" r="2.8" fill="#fff" stroke="#f39c12" stroke-width="1.2" />';
        }
        foreach ($events as $ev) {
            $svg .= '<text x="' . round($ev['x'], 1) . '" y="' . ($padTop + 10) . '" font-size="10" font-weight="bold" fill="#8e44ad" text-anchor="middle" font-family="Arial">' . htmlspecialchars($ev['char']) . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    private function buildAns5CetakGrid($gridData, $chartSvg)
    {
        $times = $gridData['times'] ?? [];
        $rows = $gridData['rows'] ?? [];
        $totals = $gridData['totals'] ?? [];

        // 1. Time headers
        $timeTh = '';
        for ($c = 0; $c < 16; $c++) {
            $tVal = !empty($times[$c]) ? htmlspecialchars($times[$c]) : '';
            if (empty($tVal)) {
                $sub = ($c % 4) * 15;
                $tVal = $sub > 0 ? (string)$sub : '';
            }
            $timeTh .= '<th style="width: 3.8%; text-align: center; font-size: 7px;">' . $tVal . '</th>';
        }

        // Helper to format a table row
        $renderRow = function($id, $label) use ($rows, $totals) {
            $tr = '<tr>';
            $tr .= '<td class="label-col">' . htmlspecialchars($label) . '</td>';
            for ($c = 0; $c < 16; $c++) {
                $val = isset($rows[$id][$c]) ? htmlspecialchars((string)$rows[$id][$c]) : '';
                $tr .= '<td>' . ($val !== '' ? $val : '&nbsp;') . '</td>';
            }
            $tot = isset($totals[$id]) ? htmlspecialchars((string)$totals[$id]) : '';
            $tr .= '<td><strong>' . ($tot !== '' ? $tot : '&nbsp;') . '</strong></td>';
            $tr .= '</tr>';
            return $tr;
        };

        $body = '';

        // Gas Inhalasi
        $body .= $renderRow('gas_o2', 'O2 (L/Mnt)');
        $body .= $renderRow('gas_halotan', 'Halotan (Vol%)');
        $body .= $renderRow('gas_isofluran', 'Isofluran (Vol%)');
        $body .= $renderRow('gas_sevofluran', 'Sevofluran (Vol%)');

        // Obat-Obatan
        $meds = [
            'med_ketorolac' => 'Ketorolac (mg)',
            'med_ondansetron' => 'Ondansetron (mg)',
            'med_ranitidin' => 'Ranitidin (mg)',
            'med_midazolam' => 'Midazolam (mg)',
            'med_fentanyl' => 'Fentanyl (µg)',
            'med_lidokain' => 'Lidokain (mg)',
            'med_ketamin' => 'Ketamin (mg)',
            'med_propofol' => 'Propofol (mg)',
            'med_atracurium' => 'Atracurium (mg)',
            'med_efedrin' => 'Efedrin (µg)',
            'med_obat_lain1' => 'Obat Tambahan 1',
            'med_obat_lain2' => 'Obat Tambahan 2',
            'med_epidural' => 'Epidural / Spinal'
        ];
        foreach ($meds as $mid => $mlbl) {
            $body .= $renderRow($mid, $mlbl);
        }

        // Baris Grafik Tanda Vital
        $body .= '<tr>';
        $body .= '<td style="vertical-align: top; padding: 2px; text-align: left; background: #fff;">';
        $body .= '<table style="width: 100%; border: none; font-size: 6.8px;"><tr>';
        $body .= '<td style="border: none; vertical-align: top; width: 22px; padding: 0; line-height: 1.15;">';
        $body .= '<strong>200</strong><br><strong>180</strong><br><strong>160</strong><br><strong>140</strong><br><strong>120</strong><br><strong>100</strong><br><strong>80</strong><br><strong>60</strong><br><strong>40</strong>';
        $body .= '</td>';
        $body .= '<td style="border: none; vertical-align: top; padding: 0 0 0 2px;">';
        $body .= '<div class="legend-box">';
        $body .= '▼ i.m &nbsp;&nbsp;&nbsp; ↓ i.v<br>';
        $body .= '• nadi &nbsp;&nbsp; ○ suhu<br>';
        $body .= '∨ sistole ∧ diastole<br>';
        $body .= '/// resp spontan<br>';
        $body .= 'vv resp kontrol<br>';
        $body .= 'x anestesi ⊙ operasi<br>';
        $body .= '△ intubasi ▽ ekstubasi<br>';
        $body .= '* kateter epidural';
        $body .= '</div>';
        $body .= '</td>';
        $body .= '</tr></table>';
        $body .= '</td>';
        $body .= '<td colspan="17" style="padding: 0; background: #fff; vertical-align: middle; height: 160px;">';
        $body .= $chartSvg;
        $body .= '</td>';
        $body .= '</tr>';

        // Keterangan Bawah Grafik
        $body .= $renderRow('ket_tanda_kode', 'Tanda (Kode)');
        $body .= $renderRow('ket_rsp', 'Rsp. Spontan / Kontrol');
        $body .= $renderRow('ket_saturasi', 'Saturasi O2 (%)');

        // INPUT CAIRAN
        $body .= '<tr style="background: #f9f9f9;"><td class="label-col" style="background: #f0f0f0;"><strong>INPUT:</strong></td><td colspan="17" style="border: 0.8px solid #000;"></td></tr>';
        $body .= $renderRow('in_rl', 'Ringer Laktat/Asetat (cc)');
        $body .= $renderRow('in_nacl', 'NaCl 0.9% (cc)');
        $body .= $renderRow('in_koloid', 'Koloid / Darah / Lain (cc)');

        // OUTPUT CAIRAN
        $body .= '<tr style="background: #f9f9f9;"><td class="label-col" style="background: #f0f0f0;"><strong>OUTPUT:</strong></td><td colspan="17" style="border: 0.8px solid #000;"></td></tr>';
        $body .= $renderRow('out_darah', 'Pendarahan (cc)');
        $body .= $renderRow('out_urine', 'Urine (cc)');
        $body .= $renderRow('out_lain', 'Output Lain (cc)');

        return [
            'time_headers' => $timeTh,
            'body' => $body
        ];
    }

    public function anyBmi3manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            return $this->getBmi3hapus($_GET['id']);
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = $this->db('bmi3_asesmen_bedah_minor')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi3_asesmen_bedah_minor.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('bmi3_asesmen_bedah_minor.*, COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
            ->desc('bmi3_asesmen_bedah_minor.id')
            ->toArray();

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        foreach ($query as $k => $v) {
            $query[$k]['nomor'] = $k + 1;
            if (!empty($v['tanggal_operasi']) && $v['tanggal_operasi'] != '0000-00-00') {
                $p = explode('-', $v['tanggal_operasi']);
                $query[$k]['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
            } else {
                $query[$k]['tgl_indo'] = '-';
            }
        }

        $this->tpl->set('data', $query);
        $this->tpl->set('list', $query);
        return $this->draw('bmi3/manage.html', ['data' => $query, 'list' => $query]);
    }

    public function getBmi3manage()
    {
        return $this->anyBmi3manage();
    }

    public function anyBmi3form($id = null)
    {
        // Handle AJAX patient lookup
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, pasien.gol_darah')
                ->oneArray();

            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode([
                    'success' => true,
                    'no_rawat' => $patient['no_rawat'],
                    'no_rkm_medis' => $patient['no_rkm_medis'],
                    'nm_pasien' => $patient['nm_pasien'],
                    'jk' => $patient['jk'],
                    'umur' => $patient['umur'],
                    'tgl_lahir' => $patient['tgl_lahir']
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }

        $data = [];
        if ($id) {
            $data = $this->db('bmi3_asesmen_bedah_minor')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi3_asesmen_bedah_minor.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('bmi3_asesmen_bedah_minor.id', $id)
                ->select('bmi3_asesmen_bedah_minor.*, COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
                ->oneArray();
        }

        if (empty($data) || !is_array($data)) {
            $data = [];
        }

        $today = date('Y-m-d');
        $defaults = [
            'id' => '', 'no_rawat' => '', 'no_rkm_medis' => '', 'nm_pasien' => '', 'jk' => '', 'umur' => '', 'tgl_lahir' => '',
            'tanggal_operasi' => $today, 'jam_operasi' => date('H:i'),
            'jenis_anamnesis' => 'Autoanamnesis', 'anamnesis' => '', 'keluhan_utama' => '',
            'rp_hipertensi' => '0', 'rp_diabetes' => '0', 'rp_gastritis' => '0', 'rp_darah' => '0', 'rp_jantung' => '0', 'rp_hati' => '0',
            'rp_ginjal' => '0', 'rp_pengobatan_panjang' => '0', 'rp_operasi' => '0', 'rp_alergi_obat' => '0', 'rp_alergi_makanan' => '0', 'rp_sistemik_lain' => '0', 'rp_tidak_ada' => '1', 'rp_keterangan' => '',
            'riwayat_alergi' => 'Tidak ada riwayat alergi',
            'pengobatan_pengencer_darah' => '0', 'pengobatan_osteoporosis_kanker' => '0', 'pengobatan_antibiotik' => '0', 'pengobatan_analgetik' => '0', 'pengobatan_keterangan' => '',
            'rpk_hipertensi' => '0', 'rpk_diabetes' => '0', 'rpk_gastritis' => '0', 'rpk_darah' => '0', 'rpk_jantung' => '0', 'rpk_hati' => '0',
            'rpk_ginjal' => '0', 'rpk_pengobatan_panjang' => '0', 'rpk_operasi' => '0', 'rpk_alergi_obat' => '0', 'rpk_alergi_makanan' => '0', 'rpk_sistemik_lain' => '0', 'rpk_tidak_ada' => '1', 'rpk_keterangan' => '',
            'fisik_ekstraoral_check' => '1', 'fisik_ekstraoral' => '', 'fisik_intraoral_check' => '1', 'fisik_intraoral' => '',
            'pemeriksaan_penunjang' => '', 'diagnosis' => '', 'rencana_tindakan' => '',
            'kd_dokter' => '', 'nm_dokter' => ''
        ];

        foreach ($defaults as $k => $v) {
            if (!isset($data[$k]) || $data[$k] === null) {
                $data[$k] = $v;
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        $pasien_list = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->desc('reg_periksa.tgl_registrasi')
            ->limit(30)
            ->toArray();

        $this->tpl->set('data', $data);
        $this->tpl->set('dokter', $dokter);
        $this->tpl->set('pasien_list', $pasien_list);

        return $this->draw('bmi3/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'pasien_list' => $pasien_list
        ]);
    }

    public function getBmi3form($id = null)
    {
        return $this->anyBmi3form($id);
    }

    public function anyBmi3save()
    {
        if (empty($_POST)) {
            redirect(url([ADMIN, 'form_rme', 'bmi3manage']));
        }

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rkm_medis) && !empty($no_rawat)) {
            $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if ($reg) {
                $no_rkm_medis = $reg['no_rkm_medis'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal_operasi' => !empty($_POST['tanggal_operasi']) ? $_POST['tanggal_operasi'] : date('Y-m-d'),
            'jam_operasi' => !empty($_POST['jam_operasi']) ? $_POST['jam_operasi'] : date('H:i:s'),

            'jenis_anamnesis' => trim($_POST['jenis_anamnesis'] ?? 'Autoanamnesis'),
            'anamnesis' => trim($_POST['anamnesis'] ?? ''),
            'keluhan_utama' => trim($_POST['keluhan_utama'] ?? ''),

            // Riwayat Penyakit Pasien
            'rp_hipertensi' => !empty($_POST['rp_hipertensi']) ? '1' : '0',
            'rp_diabetes' => !empty($_POST['rp_diabetes']) ? '1' : '0',
            'rp_gastritis' => !empty($_POST['rp_gastritis']) ? '1' : '0',
            'rp_darah' => !empty($_POST['rp_darah']) ? '1' : '0',
            'rp_jantung' => !empty($_POST['rp_jantung']) ? '1' : '0',
            'rp_hati' => !empty($_POST['rp_hati']) ? '1' : '0',
            'rp_ginjal' => !empty($_POST['rp_ginjal']) ? '1' : '0',
            'rp_pengobatan_panjang' => !empty($_POST['rp_pengobatan_panjang']) ? '1' : '0',
            'rp_operasi' => !empty($_POST['rp_operasi']) ? '1' : '0',
            'rp_alergi_obat' => !empty($_POST['rp_alergi_obat']) ? '1' : '0',
            'rp_alergi_makanan' => !empty($_POST['rp_alergi_makanan']) ? '1' : '0',
            'rp_sistemik_lain' => !empty($_POST['rp_sistemik_lain']) ? '1' : '0',
            'rp_tidak_ada' => !empty($_POST['rp_tidak_ada']) ? '1' : '0',
            'rp_keterangan' => trim($_POST['rp_keterangan'] ?? ''),

            // Riwayat Alergi
            'riwayat_alergi' => trim($_POST['riwayat_alergi'] ?? ''),

            // Riwayat Pengobatan
            'pengobatan_pengencer_darah' => !empty($_POST['pengobatan_pengencer_darah']) ? '1' : '0',
            'pengobatan_osteoporosis_kanker' => !empty($_POST['pengobatan_osteoporosis_kanker']) ? '1' : '0',
            'pengobatan_antibiotik' => !empty($_POST['pengobatan_antibiotik']) ? '1' : '0',
            'pengobatan_analgetik' => !empty($_POST['pengobatan_analgetik']) ? '1' : '0',
            'pengobatan_keterangan' => trim($_POST['pengobatan_keterangan'] ?? ''),

            // Riwayat Penyakit Keluarga
            'rpk_hipertensi' => !empty($_POST['rpk_hipertensi']) ? '1' : '0',
            'rpk_diabetes' => !empty($_POST['rpk_diabetes']) ? '1' : '0',
            'rpk_gastritis' => !empty($_POST['rpk_gastritis']) ? '1' : '0',
            'rpk_darah' => !empty($_POST['rpk_darah']) ? '1' : '0',
            'rpk_jantung' => !empty($_POST['rpk_jantung']) ? '1' : '0',
            'rpk_hati' => !empty($_POST['rpk_hati']) ? '1' : '0',
            'rpk_ginjal' => !empty($_POST['rpk_ginjal']) ? '1' : '0',
            'rpk_pengobatan_panjang' => !empty($_POST['rpk_pengobatan_panjang']) ? '1' : '0',
            'rpk_operasi' => !empty($_POST['rpk_operasi']) ? '1' : '0',
            'rpk_alergi_obat' => !empty($_POST['rpk_alergi_obat']) ? '1' : '0',
            'rpk_alergi_makanan' => !empty($_POST['rpk_alergi_makanan']) ? '1' : '0',
            'rpk_sistemik_lain' => !empty($_POST['rpk_sistemik_lain']) ? '1' : '0',
            'rpk_tidak_ada' => !empty($_POST['rpk_tidak_ada']) ? '1' : '0',
            'rpk_keterangan' => trim($_POST['rpk_keterangan'] ?? ''),

            // Pemeriksaan Fisik
            'fisik_ekstraoral_check' => !empty($_POST['fisik_ekstraoral_check']) ? '1' : '0',
            'fisik_ekstraoral' => trim($_POST['fisik_ekstraoral'] ?? ''),
            'fisik_intraoral_check' => !empty($_POST['fisik_intraoral_check']) ? '1' : '0',
            'fisik_intraoral' => trim($_POST['fisik_intraoral'] ?? ''),

            // Penunjang, Diagnosis, Rencana
            'pemeriksaan_penunjang' => trim($_POST['pemeriksaan_penunjang'] ?? ''),
            'diagnosis' => trim($_POST['diagnosis'] ?? ''),
            'rencana_tindakan' => trim($_POST['rencana_tindakan'] ?? ''),

            // Dokter
            'kd_dokter' => trim($_POST['kd_dokter'] ?? ''),
            'nm_dokter' => trim($_POST['nm_dokter'] ?? ''),

            'updated_at' => $now
        ];

        if ($id) {
            $this->db('bmi3_asesmen_bedah_minor')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data BMI3 Asesmen Bedah Minor berhasil diperbarui.');
        } else {
            $saveData['created_at'] = $now;
            $this->db('bmi3_asesmen_bedah_minor')->save($saveData);
            $this->notify('success', 'Data BMI3 Asesmen Bedah Minor berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'bmi3manage']));
    }

    public function postBmi3save()
    {
        return $this->anyBmi3save();
    }

    public function anyBmi3hapus($id = null)
    {
        return $this->getBmi3hapus($id);
    }

    public function getBmi3hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('bmi3_asesmen_bedah_minor')->where('id', $id)->delete();
            $this->notify('success', 'Data BMI3 Asesmen Bedah Minor berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'bmi3manage']));
    }

    public function anyBmi3cetak($id = null)
    {
        return $this->getBmi3cetak($id);
    }

    public function getBmi3cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bmi3_asesmen_bedah_minor')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bmi3_asesmen_bedah_minor.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('bmi3_asesmen_bedah_minor.id', $id)
            ->select('bmi3_asesmen_bedah_minor.*, COALESCE(bmi3_asesmen_bedah_minor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            $this->notify('failure', 'Data Asesmen Bedah Minor tidak ditemukan!');
            redirect(url([ADMIN, 'form_rme', 'bmi3manage']));
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tanggal_operasi']) && $data['tanggal_operasi'] != '0000-00-00') {
            $p = explode('-', $data['tanggal_operasi']);
            $data['tgl_operasi_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
        } else {
            $data['tgl_operasi_indo'] = '-';
        }

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . ($bulan[(int)$tgl[1]] ?? '') . ' ' . $tgl[0];
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Prepare 6 digits for RM box
        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split($padded_rm);
        if (count($rm_digits) < 6) {
            $rm_digits = array_pad($rm_digits, 6, '&nbsp;');
        }

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'rm_digits' => $rm_digits,
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            try {
                $oldErr = error_reporting(0);
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 5,
                    'margin_bottom' => 5,
                    'tempDir' => BASE_DIR . '/tmp'
                ]);

                $html = $this->draw('bmi3/cetak.html', $viewParams);
                $mpdf->WriteHTML($html);
                $filename = 'BMI3_Asesmen_Bedah_Minor_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';

                while (ob_get_level()) {
                    ob_end_clean();
                }

                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Exception $e) {
                error_reporting($oldErr);
                echo "Gagal mengenerate PDF: " . htmlspecialchars($e->getMessage());
                exit();
            }
        }

        echo $this->draw('bmi3/cetak.html', $viewParams);
        exit();
    }

    public function anyBm3manage()
    {
        return $this->getBm3manage();
    }

    public function getBm3manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/js/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/js/dataTables.bootstrap.min.js'));

        $rows = $this->db('bm3_asesmen_pra_bedah_mayor')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm3_asesmen_pra_bedah_mayor.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->leftJoin('dokter', 'dokter.kd_dokter = bm3_asesmen_pra_bedah_mayor.kd_dokter')
            ->select('bm3_asesmen_pra_bedah_mayor.*, COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, dokter.nm_dokter')
            ->desc('bm3_asesmen_pra_bedah_mayor.id')
            ->toArray();

        $data = [];
        $no = 1;
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        foreach ($rows as $row) {
            $row['nomor'] = $no++;
            if (!empty($row['tanggal_pengkajian']) && $row['tanggal_pengkajian'] != '0000-00-00 00:00:00') {
                $dt = explode(' ', $row['tanggal_pengkajian']);
                $p = explode('-', $dt[0]);
                $row['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
                $row['jam_pengkajian'] = substr($dt[1] ?? '', 0, 5);
            } else {
                $row['tgl_indo'] = '-';
                $row['jam_pengkajian'] = '-';
            }
            $data[] = $row;
        }

        return $this->draw('bm3/manage.html', ['data' => $data]);
    }

    public function anyBm3form($id = null)
    {
        return $this->getBm3form($id);
    }

    public function getBm3form($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);

        // AJAX pencarian pasien
        if (isset($_GET['action']) && $_GET['action'] == 'cari_pasien') {
            $q = trim($_GET['q'] ?? '');
            $hasil = [];
            if (!empty($q)) {
                $hasil = $this->db('reg_periksa')
                    ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->whereLike('reg_periksa.no_rawat', "%$q%")
                    ->orWhereLike('reg_periksa.no_rkm_medis', "%$q%")
                    ->orWhereLike('pasien.nm_pasien', "%$q%")
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, reg_periksa.umurdaftar, reg_periksa.sttsumur, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->limit(20)
                    ->toArray();
            }
            header('Content-Type: application/json');
            echo json_encode($hasil);
            exit();
        }

        $data = [];
        if ($id) {
            $data = $this->db('bm3_asesmen_pra_bedah_mayor')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm3_asesmen_pra_bedah_mayor.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->leftJoin('dokter', 'dokter.kd_dokter = bm3_asesmen_pra_bedah_mayor.kd_dokter')
                ->where('bm3_asesmen_pra_bedah_mayor.id', $id)
                ->select('bm3_asesmen_pra_bedah_mayor.*, COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, dokter.nm_dokter')
                ->oneArray();
        }

        if (empty($data)) {
            $data = [
                'id' => '',
                'no_rawat' => '',
                'no_rkm_medis' => '',
                'nm_pasien' => '',
                'jk' => '',
                'umur' => '',
                'tgl_lahir' => '',
                'tanggal_pengkajian' => date('Y-m-d H:i:s'),
                'kd_dokter' => '',
                'nm_dokter' => '',
                'keluhan_utama' => '',
                'alergi_obat' => 'Tidak Ada',
                'merokok' => 'Tidak Merokok',
                'riwayat_penyakit' => '',
                'keadaan_umum' => 'Tampak Sakit Sedang',
                'kesadaran' => 'Compos Mentis',
                'gcs_e' => '4',
                'gcs_v' => '5',
                'gcs_m' => '6',
                'td' => '120/80',
                'nadi' => '80',
                'pernafasan' => '20',
                'suhu' => '36.5',
                'spo2' => '99',
                'lokalis_gigi' => '',
                'ket_lokalis' => '',
                'pemeriksaan_generalis' => 'Kepala: Normocephali. Thorax: Cor S1-S2 m(-), Pulmo vesikuler (+/+). Abdomen: Supel, bising usus (+) normal. Ekstremitas: Edema (-/-).',
                'lab_dpl' => '',
                'lab_hemostatis' => '',
                'lab_lain' => '',
                'kesan_lab' => '',
                'rad_toraks' => '',
                'rad_panoramik' => '',
                'rad_ctscan' => '',
                'rad_lain' => '',
                'kesan_rad' => '',
                'ekg_dilakukan' => 'EKG 12 Lead',
                'kesan_ekg' => 'Normal Sinus Rhythm',
                'penunjang_lain' => '',
                'kesan_penunjang_lain' => '',
                'diagnosis_pra_bedah' => '',
                'rencana_operasi' => '',
                'rencana_anestesi' => 'Umum',
                'evaluasi_masalah' => '',
                'evaluasi_potensi' => '',
                'evaluasi_antisipasi' => '',
                'rencana_kerja_terapi' => "1. Puasa 8 jam pra operasi\n2. Pasang IV line 2 jalur\n3. Inform Consent operasi & anestesi lengkap\n4. Siapkan darah",
                'catatan_penting' => ''
            ];
        }

        // Format tanggal untuk input HTML5 datetime-local
        if (!empty($data['tanggal_pengkajian'])) {
            $data['tanggal_pengkajian_html'] = date('Y-m-d\TH:i', strtotime($data['tanggal_pengkajian']));
        } else {
            $data['tanggal_pengkajian_html'] = date('Y-m-d\TH:i');
        }

        // List dokter spesialis bedah mulut / dokter gigi
        $dokter = $this->db('dokter')
            ->where('status', '1')
            ->select('kd_dokter, nm_dokter')
            ->asc('nm_dokter')
            ->toArray();

        return $this->draw('bm3/form.html', [
            'data' => $data,
            'dokter' => $dokter
        ]);
    }

    public function anyBm3save()
    {
        return $this->postBm3save();
    }

    public function postBm3save()
    {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rawat)) {
            $this->notify('failure', 'No. Rawat wajib diisi!');
            redirect(url([ADMIN, 'form_rme', 'bm3form', $id]));
        }

        if (empty($no_rkm_medis)) {
            $cekPasien = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            $no_rkm_medis = $cekPasien['no_rkm_medis'] ?? '';
        }

        $tanggal_pengkajian = !empty($_POST['tanggal_pengkajian']) ? str_replace('T', ' ', $_POST['tanggal_pengkajian']) . ':00' : date('Y-m-d H:i:s');
        if (strlen($tanggal_pengkajian) > 19) {
            $tanggal_pengkajian = substr($tanggal_pengkajian, 0, 19);
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal_pengkajian' => $tanggal_pengkajian,
            'kd_dokter' => trim($_POST['kd_dokter'] ?? ''),
            'keluhan_utama' => trim($_POST['keluhan_utama'] ?? ''),
            'alergi_obat' => trim($_POST['alergi_obat'] ?? ''),
            'merokok' => trim($_POST['merokok'] ?? ''),
            'riwayat_penyakit' => trim($_POST['riwayat_penyakit'] ?? ''),
            'keadaan_umum' => trim($_POST['keadaan_umum'] ?? ''),
            'kesadaran' => trim($_POST['kesadaran'] ?? ''),
            'gcs_e' => trim($_POST['gcs_e'] ?? ''),
            'gcs_v' => trim($_POST['gcs_v'] ?? ''),
            'gcs_m' => trim($_POST['gcs_m'] ?? ''),
            'td' => trim($_POST['td'] ?? ''),
            'nadi' => trim($_POST['nadi'] ?? ''),
            'pernafasan' => trim($_POST['pernafasan'] ?? ''),
            'suhu' => trim($_POST['suhu'] ?? ''),
            'spo2' => trim($_POST['spo2'] ?? ''),
            'lokalis_gigi' => trim($_POST['lokalis_gigi'] ?? ''),
            'ket_lokalis' => trim($_POST['ket_lokalis'] ?? ''),
            'pemeriksaan_generalis' => trim($_POST['pemeriksaan_generalis'] ?? ''),
            'lab_dpl' => trim($_POST['lab_dpl'] ?? ''),
            'lab_hemostatis' => trim($_POST['lab_hemostatis'] ?? ''),
            'lab_lain' => trim($_POST['lab_lain'] ?? ''),
            'kesan_lab' => trim($_POST['kesan_lab'] ?? ''),
            'rad_toraks' => trim($_POST['rad_toraks'] ?? ''),
            'rad_panoramik' => trim($_POST['rad_panoramik'] ?? ''),
            'rad_ctscan' => trim($_POST['rad_ctscan'] ?? ''),
            'rad_lain' => trim($_POST['rad_lain'] ?? ''),
            'kesan_rad' => trim($_POST['kesan_rad'] ?? ''),
            'ekg_dilakukan' => trim($_POST['ekg_dilakukan'] ?? ''),
            'kesan_ekg' => trim($_POST['kesan_ekg'] ?? ''),
            'penunjang_lain' => trim($_POST['penunjang_lain'] ?? ''),
            'kesan_penunjang_lain' => trim($_POST['kesan_penunjang_lain'] ?? ''),
            'diagnosis_pra_bedah' => trim($_POST['diagnosis_pra_bedah'] ?? ''),
            'rencana_operasi' => trim($_POST['rencana_operasi'] ?? ''),
            'rencana_anestesi' => trim($_POST['rencana_anestesi'] ?? 'Umum'),
            'evaluasi_masalah' => trim($_POST['evaluasi_masalah'] ?? ''),
            'evaluasi_potensi' => trim($_POST['evaluasi_potensi'] ?? ''),
            'evaluasi_antisipasi' => trim($_POST['evaluasi_antisipasi'] ?? ''),
            'rencana_kerja_terapi' => trim($_POST['rencana_kerja_terapi'] ?? ''),
            'catatan_penting' => trim($_POST['catatan_penting'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            $this->db('bm3_asesmen_pra_bedah_mayor')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data Asesmen Pra Bedah Mayor (BM3) berhasil diperbarui!');
        } else {
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $this->db('bm3_asesmen_pra_bedah_mayor')->save($saveData);
            $this->notify('success', 'Data Asesmen Pra Bedah Mayor (BM3) berhasil ditambahkan!');
        }

        redirect(url([ADMIN, 'form_rme', 'bm3manage']));
    }

    public function anyBm3hapus($id = null)
    {
        return $this->getBm3hapus($id);
    }

    public function getBm3hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('bm3_asesmen_pra_bedah_mayor')->where('id', $id)->delete();
            $this->notify('success', 'Data Asesmen Pra Bedah Mayor (BM3) berhasil dihapus!');
        }
        redirect(url([ADMIN, 'form_rme', 'bm3manage']));
    }

    public function anyBm3cetak($id = null)
    {
        return $this->getBm3cetak($id);
    }

    public function getBm3cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bm3_asesmen_pra_bedah_mayor')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm3_asesmen_pra_bedah_mayor.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->leftJoin('dokter', 'dokter.kd_dokter = bm3_asesmen_pra_bedah_mayor.kd_dokter')
            ->where('bm3_asesmen_pra_bedah_mayor.id', $id)
            ->select('bm3_asesmen_pra_bedah_mayor.*, COALESCE(bm3_asesmen_pra_bedah_mayor.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, dokter.nm_dokter')
            ->oneArray();

        if (!$data) {
            $this->notify('failure', 'Data Asesmen Pra Bedah Mayor tidak ditemukan!');
            redirect(url([ADMIN, 'form_rme', 'bm3manage']));
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tanggal_pengkajian']) && $data['tanggal_pengkajian'] != '0000-00-00 00:00:00') {
            $dt = explode(' ', $data['tanggal_pengkajian']);
            $p = explode('-', $dt[0]);
            $data['tanggal_pengkajian_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0] . ' Pukul ' . substr($dt[1] ?? '', 0, 5) . ' WITA';
        } else {
            $data['tanggal_pengkajian_indo'] = '-';
        }

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . ($bulan[(int)$tgl[1]] ?? '') . ' ' . $tgl[0];
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Prepare 6 digits for RM box
        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split($padded_rm);
        if (count($rm_digits) < 6) {
            $rm_digits = array_pad($rm_digits, 6, '&nbsp;');
        }

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $anatomi_file = BASE_DIR . '/assets/img/bm3_anatomi_tubuh.png';
        $anatomi_img = is_file($anatomi_file) ? ($is_pdf ? $anatomi_file : url('assets/img/bm3_anatomi_tubuh.png')) : '';

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'anatomi_img' => $anatomi_img,
            'rm_digits' => $rm_digits,
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            try {
                $oldErr = error_reporting(0);
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 5,
                    'margin_bottom' => 5,
                    'tempDir' => BASE_DIR . '/tmp'
                ]);

                $html = $this->draw('bm3/cetak.html', $viewParams);
                $mpdf->WriteHTML($html);
                $filename = 'BM3_Asesmen_Pra_Bedah_Mayor_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';

                while (ob_get_level()) {
                    ob_end_clean();
                }

                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Exception $e) {
                error_reporting($oldErr);
                echo "Gagal mengenerate PDF: " . htmlspecialchars($e->getMessage());
                exit();
            }
        }

        echo $this->draw('bm3/cetak.html', $viewParams);
        exit();
    }

    public function anyPenandaanlokasibedahmanage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = $this->db('penandaan_lokasi_bedah_mulut')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = penandaan_lokasi_bedah_mulut.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('penandaan_lokasi_bedah_mulut.*, COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
            ->desc('penandaan_lokasi_bedah_mulut.id')
            ->toArray();

        foreach ($query as $k => $v) {
            $query[$k]['nomor'] = $k + 1;
            $markers = !empty($v['markers_anatomi']) ? json_decode($v['markers_anatomi'], true) : [];
            $query[$k]['total_marker'] = is_array($markers) ? count($markers) : 0;

            // Badge gigi disiapkan di sini (template engine tidak mendukung {set:} / loop bersarang)
            $badges = '';
            foreach ($this->penandaanParseGigi($v['gigi_centang'] ?? '') as $g) {
                $badges .= '<span class="label label-danger" style="display: inline-block; margin: 1px 2px; font-size: 11px;"><i class="fa fa-check"></i> ' . htmlspecialchars($g) . '</span>';
            }
            $query[$k]['gigi_badges'] = $badges;
        }

        return $this->draw('penandaan_lokasi_bedah_mulut/manage.html', ['list' => $query, 'data' => $query]);
    }

    public function getPenandaanlokasibedahform()
    {
        // AJAX patient lookup
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, pasien.alamat')
                ->oneArray();
            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode([
                    'success' => true,
                    'no_rawat' => $patient['no_rawat'],
                    'no_rkm_medis' => $patient['no_rkm_medis'],
                    'nm_pasien' => $patient['nm_pasien'],
                    'jk' => $patient['jk'],
                    'umur' => $patient['umur'],
                    'tgl_lahir' => $patient['tgl_lahir'],
                    'alamat' => $patient['alamat']
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        $id = $_GET['id'] ?? '';
        $data = [];
        if ($id) {
            $data = $this->db('penandaan_lokasi_bedah_mulut')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = penandaan_lokasi_bedah_mulut.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('penandaan_lokasi_bedah_mulut.id', $id)
                ->select('penandaan_lokasi_bedah_mulut.*, COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
                ->oneArray();
        }
        if (empty($data) || !is_array($data)) {
            $data = [];
        }
        $defaults = [
            'id' => '', 'no_rawat' => '', 'no_rkm_medis' => '', 'nm_pasien' => '', 'jk' => '',
            'umur' => '', 'tgl_lahir' => '', 'jenis_tindakan' => '', 'gigi_centang' => '',
            'markers_anatomi' => '[]', 'tanggal' => date('Y-m-d'), 'jam' => date('H:i'),
            'nama_pasien_keluarga' => '', 'hubungan_pasien' => 'Pasien', 'kd_dokter' => '',
            'nama_dokter' => '', 'nama_saksi' => '', 'catatan' => ''
        ];
        foreach ($defaults as $key => $def) {
            if (!isset($data[$key]) || $data[$key] === null || $data[$key] === '') {
                $data[$key] = $def;
            }
        }
        if (!empty($data['jam'])) {
            $data['jam'] = substr($data['jam'], 0, 5);
        }

        // Odontogram (centang sekotakan) disiapkan di controller
        $gigiMap = array_fill_keys($this->penandaanParseGigi($data['gigi_centang']), true);
        $odonto = $this->penandaanBuildOdonto($gigiMap, true);

        // Escape seluruh nilai teks agar aman dipakai di atribut value="..." (JSON marker mengandung tanda kutip)
        foreach ($data as $key => $val) {
            if (is_string($val)) {
                $data[$key] = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('penandaan_lokasi_bedah_mulut/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'odonto' => $odonto,
            'anatomi_img' => url('assets/img/penandaan_lokasi_mulut_kepala_clean.png')
        ]);
    }

    public function postPenandaanlokasibedahsave()
    {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rkm_medis) && !empty($no_rawat)) {
            $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if ($reg) $no_rkm_medis = $reg['no_rkm_medis'];
        }

        $kd_dokter = trim($_POST['kd_dokter'] ?? '');
        $nama_dokter = trim($_POST['nama_dokter'] ?? '');
        if (!empty($kd_dokter) && empty($nama_dokter)) {
            $doc = $this->db('dokter')->where('kd_dokter', $kd_dokter)->oneArray();
            if ($doc) $nama_dokter = $doc['nm_dokter'];
        }

        // Validasi gigi: hanya nomor gigi yang ada di odontogram
        $validTeeth = [];
        foreach ($this->penandaanOdontoRows() as $r) {
            $validTeeth = array_merge($validTeeth, $r['kanan'], $r['kiri']);
        }
        $gigi = array_values(array_intersect($this->penandaanParseGigi($_POST['gigi_centang'] ?? ''), array_map('strval', $validTeeth)));

        // Validasi marker anatomi: array {x, y} dalam persen 0-100
        $markers = json_decode($_POST['markers_anatomi'] ?? '[]', true);
        $cleanMarkers = [];
        if (is_array($markers)) {
            foreach ($markers as $m) {
                if (isset($m['x'], $m['y']) && is_numeric($m['x']) && is_numeric($m['y'])) {
                    $cleanMarkers[] = [
                        'x' => round(max(0, min(100, (float)$m['x'])), 2),
                        'y' => round(max(0, min(100, (float)$m['y'])), 2)
                    ];
                }
            }
        }

        $jam = !empty($_POST['jam']) ? $_POST['jam'] : date('H:i:s');
        if (strlen($jam) == 5) {
            $jam .= ':00';
        }

        $data = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'jenis_tindakan' => trim($_POST['jenis_tindakan'] ?? ''),
            'gigi_centang' => implode(',', $gigi),
            'markers_anatomi' => json_encode($cleanMarkers),
            'gambar_penandaan' => '',
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'jam' => $jam,
            'nama_pasien_keluarga' => trim($_POST['nama_pasien_keluarga'] ?? ''),
            'hubungan_pasien' => trim($_POST['hubungan_pasien'] ?? 'Pasien'),
            'kd_dokter' => $kd_dokter,
            'nama_dokter' => $nama_dokter,
            'nama_saksi' => trim($_POST['nama_saksi'] ?? ''),
            'catatan' => trim($_POST['catatan'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            $this->db('penandaan_lokasi_bedah_mulut')->where('id', $id)->save($data);
            $this->setNotify('success', 'Data Penandaan Tepat Lokasi Bedah Mulut berhasil diperbarui!');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db('penandaan_lokasi_bedah_mulut')->save($data);
            $this->setNotify('success', 'Data Penandaan Tepat Lokasi Bedah Mulut berhasil disimpan!');
        }

        redirect(url([ADMIN, 'form_rme', 'penandaanlokasibedahmanage']));
    }

    public function getPenandaanlokasibedahhapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('penandaan_lokasi_bedah_mulut')->where('id', $id)->delete();
            $this->setNotify('success', 'Data penandaan lokasi bedah berhasil dihapus!');
        }
        redirect(url([ADMIN, 'form_rme', 'penandaanlokasibedahmanage']));
    }

    public function getPenandaanlokasibedahcetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('penandaan_lokasi_bedah_mulut')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = penandaan_lokasi_bedah_mulut.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('penandaan_lokasi_bedah_mulut.id', $id)
            ->select('penandaan_lokasi_bedah_mulut.*, COALESCE(penandaan_lokasi_bedah_mulut.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.umur')
            ->oneArray();

        if (!$data) {
            $this->setNotify('failure', 'Data penandaan tepat lokasi bedah mulut tidak ditemukan!');
            redirect(url([ADMIN, 'form_rme', 'penandaanlokasibedahmanage']));
        }

        $rmRaw = ltrim($data['no_rkm_medis'] ?? '', "'");
        $rmPadded = str_pad($rmRaw, 6, '0', STR_PAD_LEFT);
        $data['no_rkm_medis'] = $rmPadded;
        for ($i = 0; $i < 6; $i++) {
            $data['rm' . $i] = substr($rmPadded, $i, 1);
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            if (count($tgl) == 3) {
                $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . $bulan[(int)$tgl[1]] . ' ' . $tgl[0];
            } else {
                $data['tgl_lahir_indo'] = $data['tgl_lahir'];
            }
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        if (!empty($data['tanggal'])) {
            $tglSurat = explode('-', $data['tanggal']);
            if (count($tglSurat) == 3) {
                $data['tgl_surat_indo'] = (int)$tglSurat[2] . ' ' . $bulan[(int)$tglSurat[1]];
                $data['tahun_surat'] = substr($tglSurat[0], 2, 2);
            } else {
                $data['tgl_surat_indo'] = $data['tanggal'];
                $data['tahun_surat'] = date('y');
            }
        } else {
            $data['tgl_surat_indo'] = date('j') . ' ' . $bulan[(int)date('n')];
            $data['tahun_surat'] = date('y');
        }

        $data['jam'] = !empty($data['jam']) ? substr($data['jam'], 0, 5) : '';

        // Odontogram: centang sekotakan (SVG per kotak gigi, berupa <img> data-URI agar didukung mPDF)
        $gigiMap = array_fill_keys($this->penandaanParseGigi($data['gigi_centang'] ?? ''), true);
        $odonto = $this->penandaanBuildOdonto($gigiMap, false);

        // Anatomi: gambar dasar + centang merah dirender server-side (GD)
        $markers = !empty($data['markers_anatomi']) ? json_decode($data['markers_anatomi'], true) : [];
        if (!is_array($markers)) $markers = [];
        $anatomi_src = $this->penandaanRenderAnatomi($markers);

        // Escape teks untuk ditampilkan
        foreach (['nm_pasien', 'jenis_tindakan', 'nama_pasien_keluarga', 'nama_dokter', 'nama_saksi', 'no_rawat'] as $f) {
            $data[$f] = htmlspecialchars($data[$f] ?? '', ENT_QUOTES, 'UTF-8');
        }

        $pdf_url = url([ADMIN, 'form_rme', 'penandaanlokasibedahcetak', $id]) . '&pdf=1';
        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;

        if ($is_pdf) {
            try {
                $oldErr = error_reporting(0);
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8,
                    'tempDir' => BASE_DIR . '/tmp'
                ]);
                $kop_img = BASE_DIR . '/assets/img/KOP-SURAT.png';
                $html = $this->draw('penandaan_lokasi_bedah_mulut/cetak.html', [
                    'data' => $data,
                    'kop_img' => is_file($kop_img) ? $kop_img : '',
                    'anatomi_src' => $anatomi_src,
                    'odonto' => $odonto,
                    'pdf_url' => $pdf_url,
                    'is_mpdf' => true
                ]);
                $mpdf->WriteHTML($html);
                $filename = 'Penandaan_Bedah_Mulut_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';
                while (ob_get_level()) {
                    ob_end_clean();
                }
                $mpdf->Output($filename, 'I');
                error_reporting($oldErr);
                exit();
            } catch (\Throwable $e) {
                // fallback to HTML
            }
        }

        $kop_img = is_file(BASE_DIR . '/assets/img/KOP-SURAT.png') ? url('assets/img/KOP-SURAT.png') : '';
        echo $this->draw('penandaan_lokasi_bedah_mulut/cetak.html', [
            'data' => $data,
            'kop_img' => $kop_img,
            'anatomi_src' => $anatomi_src,
            'odonto' => $odonto,
            'pdf_url' => $pdf_url,
            'is_mpdf' => false
        ]);
        exit();
    }

    private function penandaanParseGigi($str)
    {
        $out = [];
        foreach (explode(',', (string)$str) as $g) {
            $g = trim($g);
            if ($g !== '' && ctype_digit($g)) {
                $out[] = $g;
            }
        }
        return array_values(array_unique($out));
    }

    private function penandaanOdontoRows()
    {
        // Susunan sesuai formulir fisik RSGM
        return [
            ['label' => 'top', 'arrow' => '&#9660;', 'kanan' => [18, 17, 16, 15, 14, 13, 12, 11], 'kiri' => [21, 22, 23, 24, 25, 26, 27, 28]],
            ['label' => 'top', 'arrow' => '', 'kanan' => [55, 54, 53, 52, 51], 'kiri' => [61, 62, 63, 64, 65]],
            ['label' => 'bottom', 'arrow' => '', 'kanan' => [85, 84, 83, 82, 81], 'kiri' => [71, 72, 73, 74, 75]],
            ['label' => 'bottom', 'arrow' => '&#9650;', 'kanan' => [48, 47, 46, 45, 44, 43, 42, 41], 'kiri' => [31, 32, 33, 34, 35, 36, 37, 38]],
        ];
    }

    private function penandaanToothSvg($no, $checked, $interactive = false)
    {
        $s = 'fill="#fff" stroke="#333" stroke-width="1.3"';
        if (in_array($no % 10, [1, 2, 3])) {
            // Gigi anterior (insisivus & kaninus): garis tengah horizontal
            $shape = '<polygon points="1,1 39,1 28,20 12,20" ' . $s . '/>'
                . '<polygon points="39,1 39,39 28,20" ' . $s . '/>'
                . '<polygon points="39,39 1,39 12,20 28,20" ' . $s . '/>'
                . '<polygon points="1,39 1,1 12,20" ' . $s . '/>';
        } else {
            // Gigi posterior (premolar & molar): kotak oklusal di tengah
            $shape = '<polygon points="1,1 39,1 28,12 12,12" ' . $s . '/>'
                . '<polygon points="39,1 39,39 28,28 28,12" ' . $s . '/>'
                . '<polygon points="39,39 1,39 12,28 28,28" ' . $s . '/>'
                . '<polygon points="1,39 1,1 12,12 12,28" ' . $s . '/>'
                . '<rect x="12" y="12" width="16" height="16" ' . $s . '/>';
        }
        $check = ($checked || $interactive)
            ? '<polyline class="tooth-check" points="7,21 16,31 34,7" fill="none" stroke="#e00000" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/>'
            : '';
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="40" height="40">'
            . '<rect x="0.75" y="0.75" width="38.5" height="38.5" fill="#fff" stroke="#000" stroke-width="1.5"/>'
            . $shape . $check . '</svg>';
    }

    private function penandaanBuildOdonto(array $gigiMap, $interactive = false)
    {
        $rows = [];
        foreach ($this->penandaanOdontoRows() as $r) {
            foreach (['kanan', 'kiri'] as $side) {
                $teeth = [];
                foreach ($r[$side] as $no) {
                    $checked = !empty($gigiMap[(string)$no]);
                    $svg = $this->penandaanToothSvg($no, $checked, $interactive);
                    $teeth[] = [
                        'no' => $no,
                        'checked' => $checked,
                        'svg' => $interactive
                            ? $svg
                            : '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" width="21" height="21" alt="' . $no . '">'
                    ];
                }
                $r[$side] = $teeth;
            }
            $rows[] = $r;
        }
        return $rows;
    }

    private function penandaanRenderAnatomi(array $markers)
    {
        $src = BASE_DIR . '/assets/img/penandaan_lokasi_mulut_kepala_clean.png';
        if (!is_file($src)) {
            return '';
        }
        if (!function_exists('imagecreatefrompng')) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($src));
        }

        $base = @imagecreatefrompng($src);
        if (!$base) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($src));
        }
        $w = imagesx($base);
        $h = imagesy($base);
        $k = 2; // upscale agar garis centang tajam saat dicetak
        $img = imagecreatetruecolor($w * $k, $h * $k);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagecopyresampled($img, $base, 0, 0, 0, 0, $w * $k, $h * $k, $w, $h);
        imagedestroy($base);

        $red = imagecolorallocate($img, 225, 0, 0);
        $t = 3 * $k;
        imagesetthickness($img, $t);
        foreach ($markers as $m) {
            if (!isset($m['x'], $m['y'])) continue;
            $cx = (float)$m['x'] / 100 * $w * $k;
            $cy = (float)$m['y'] / 100 * $h * $k;
            // Geometri sama dengan marker di form (titik pusat = posisi klik)
            $pts = [
                [$cx - 9 * $k, $cy],
                [$cx - 3 * $k, $cy + 7 * $k],
                [$cx + 9 * $k, $cy - 8 * $k],
            ];
            imageline($img, (int)$pts[0][0], (int)$pts[0][1], (int)$pts[1][0], (int)$pts[1][1], $red);
            imageline($img, (int)$pts[1][0], (int)$pts[1][1], (int)$pts[2][0], (int)$pts[2][1], $red);
            foreach ($pts as $p) {
                imagefilledellipse($img, (int)$p[0], (int)$p[1], $t, $t, $red);
            }
        }

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function anyBm5manage()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
            return $this->getBm5hapus($_GET['id']);
        }

        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $query = $this->db('bm5_checklist_keselamatan_operasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm5_checklist_keselamatan_operasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->select('bm5_checklist_keselamatan_operasi.*, COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur')
            ->desc('bm5_checklist_keselamatan_operasi.id')
            ->toArray();

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        foreach ($query as $k => $v) {
            $query[$k]['nomor'] = $k + 1;
            if (!empty($v['tanggal']) && $v['tanggal'] != '0000-00-00') {
                $p = explode('-', $v['tanggal']);
                $query[$k]['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
            } else {
                $query[$k]['tgl_indo'] = '-';
            }
        }

        $this->tpl->set('data', $query);
        $this->tpl->set('list', $query);
        return $this->draw('bm5/manage.html', ['data' => $query, 'list' => $query]);
    }

    public function getBm5manage()
    {
        return $this->anyBm5manage();
    }

    public function anyBm5form($id = null)
    {
        // Handle AJAX patient lookup
        if (isset($_GET['ajax_patient']) && !empty($_GET['no_rawat'])) {
            $patient = $this->db('reg_periksa')
                ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
                ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir, pasien.gol_darah')
                ->oneArray();

            header('Content-Type: application/json');
            if ($patient) {
                echo json_encode([
                    'success' => true,
                    'no_rawat' => $patient['no_rawat'],
                    'no_rkm_medis' => $patient['no_rkm_medis'],
                    'nm_pasien' => $patient['nm_pasien'],
                    'jk' => $patient['jk'],
                    'umur' => $patient['umur'],
                    'tgl_lahir' => $patient['tgl_lahir'],
                    'gol_darah' => $patient['gol_darah']
                ]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }

        $data = [];
        if ($id) {
            $data = $this->db('bm5_checklist_keselamatan_operasi')
                ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm5_checklist_keselamatan_operasi.no_rawat')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis)')
                ->where('bm5_checklist_keselamatan_operasi.id', $id)
                ->select('bm5_checklist_keselamatan_operasi.*, COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
                ->oneArray();
        }

        if (empty($data) || !is_array($data)) {
            $data = [];
        }

        $today = date('Y-m-d');
        $nowJam = date('H:i');
        $defaults = [
            'id' => '', 'no_rawat' => '', 'no_rkm_medis' => '', 'nm_pasien' => '', 'jk' => '', 'umur' => '',
            'tanggal' => $today, 'jam' => date('H:i:s'),

            // Sign In
            'sign_in_tanggal' => $today, 'sign_in_jam' => $nowJam,
            'sign_in_identitas' => 'Ya', 'sign_in_informed_consent' => 'Ya', 'sign_in_tanda_operasi' => 'Ya',
            'sign_in_riwayat_alergi' => 'Tidak', 'sign_in_alergi_ket' => '',
            'sign_in_persiapan_alat' => 'Ya', 'sign_in_pemeriksaan_penunjang' => 'Ya',
            'sign_in_dr_operator' => '', 'sign_in_perawat' => '',

            // Time Out
            'time_out_tanggal' => $today, 'time_out_jam' => $nowJam,
            'time_out_perkenalan_tim' => 'Ya', 'time_out_konfirmasi_pasien' => 'Ya',
            'time_out_antibiotik_profilaksis' => 'Ya', 'time_out_tindakan_kritis' => 'Ya',
            'time_out_estimasi_waktu' => '', 'time_out_antisipasi_perdarahan' => '',
            'time_out_sterilitas' => 'Ya', 'time_out_masalah_peralatan' => '',
            'time_out_hasil_radiologi' => 'Ya', 'time_out_dr_operator' => '', 'time_out_perawat' => '',

            // Sign Out
            'sign_out_tanggal' => $today, 'sign_out_jam' => $nowJam,
            'sign_out_konfirmasi_tindakan' => 'Ya', 'sign_out_nama_tindakan' => '',
            'sign_out_kelengkapan_alat' => 'Ya', 'sign_out_pelabelan_spesimen' => 'Tidak Ada',
            'sign_out_masalah_peralatan' => '', 'sign_out_rencana_kontrol' => 'Ya',
            'sign_out_komplikasi_tindakan' => 'Ya', 'sign_out_dr_operator' => '', 'sign_out_perawat' => ''
        ];

        foreach ($defaults as $k => $v) {
            if (!isset($data[$k]) || $data[$k] === null) {
                $data[$k] = $v;
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        $pasien_list = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.tgl_registrasi, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->desc('reg_periksa.tgl_registrasi')
            ->limit(30)
            ->toArray();

        $this->tpl->set('data', $data);
        $this->tpl->set('dokter', $dokter);
        $this->tpl->set('pasien_list', $pasien_list);

        return $this->draw('bm5/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'pasien_list' => $pasien_list
        ]);
    }

    public function getBm5form($id = null)
    {
        return $this->anyBm5form($id);
    }

    public function anyBm5save()
    {
        if (empty($_POST)) {
            redirect(url([ADMIN, 'form_rme', 'bm5manage']));
        }

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rkm_medis) && !empty($no_rawat)) {
            $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if ($reg) {
                $no_rkm_medis = $reg['no_rkm_medis'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tanggal' => !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'),
            'jam' => !empty($_POST['jam']) ? $_POST['jam'] : date('H:i:s'),

            // Sign In
            'sign_in_tanggal' => !empty($_POST['sign_in_tanggal']) ? $_POST['sign_in_tanggal'] : (!empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d')),
            'sign_in_jam' => trim($_POST['sign_in_jam'] ?? ''),
            'sign_in_identitas' => trim($_POST['sign_in_identitas'] ?? 'Ya'),
            'sign_in_informed_consent' => trim($_POST['sign_in_informed_consent'] ?? 'Ya'),
            'sign_in_tanda_operasi' => trim($_POST['sign_in_tanda_operasi'] ?? 'Ya'),
            'sign_in_riwayat_alergi' => trim($_POST['sign_in_riwayat_alergi'] ?? 'Tidak'),
            'sign_in_alergi_ket' => trim($_POST['sign_in_alergi_ket'] ?? ''),
            'sign_in_persiapan_alat' => trim($_POST['sign_in_persiapan_alat'] ?? 'Ya'),
            'sign_in_pemeriksaan_penunjang' => trim($_POST['sign_in_pemeriksaan_penunjang'] ?? 'Ya'),
            'sign_in_dr_operator' => trim($_POST['sign_in_dr_operator'] ?? ''),
            'sign_in_perawat' => trim($_POST['sign_in_perawat'] ?? ''),

            // Time Out
            'time_out_tanggal' => !empty($_POST['time_out_tanggal']) ? $_POST['time_out_tanggal'] : (!empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d')),
            'time_out_jam' => trim($_POST['time_out_jam'] ?? ''),
            'time_out_perkenalan_tim' => trim($_POST['time_out_perkenalan_tim'] ?? 'Ya'),
            'time_out_konfirmasi_pasien' => trim($_POST['time_out_konfirmasi_pasien'] ?? 'Ya'),
            'time_out_antibiotik_profilaksis' => trim($_POST['time_out_antibiotik_profilaksis'] ?? 'Ya'),
            'time_out_tindakan_kritis' => trim($_POST['time_out_tindakan_kritis'] ?? 'Ya'),
            'time_out_estimasi_waktu' => trim($_POST['time_out_estimasi_waktu'] ?? ''),
            'time_out_antisipasi_perdarahan' => trim($_POST['time_out_antisipasi_perdarahan'] ?? ''),
            'time_out_sterilitas' => trim($_POST['time_out_sterilitas'] ?? 'Ya'),
            'time_out_masalah_peralatan' => trim($_POST['time_out_masalah_peralatan'] ?? ''),
            'time_out_hasil_radiologi' => trim($_POST['time_out_hasil_radiologi'] ?? 'Ya'),
            'time_out_dr_operator' => trim($_POST['time_out_dr_operator'] ?? ''),
            'time_out_perawat' => trim($_POST['time_out_perawat'] ?? ''),

            // Sign Out
            'sign_out_tanggal' => !empty($_POST['sign_out_tanggal']) ? $_POST['sign_out_tanggal'] : (!empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d')),
            'sign_out_jam' => trim($_POST['sign_out_jam'] ?? ''),
            'sign_out_konfirmasi_tindakan' => !empty($_POST['sign_out_nama_tindakan']) ? 'Ya' : 'Tidak',
            'sign_out_nama_tindakan' => trim($_POST['sign_out_nama_tindakan'] ?? ''),
            'sign_out_kelengkapan_alat' => trim($_POST['sign_out_kelengkapan_alat'] ?? 'Ya'),
            'sign_out_pelabelan_spesimen' => trim($_POST['sign_out_pelabelan_spesimen'] ?? 'Ya'),
            'sign_out_masalah_peralatan' => trim($_POST['sign_out_masalah_peralatan'] ?? ''),
            'sign_out_rencana_kontrol' => trim($_POST['sign_out_rencana_kontrol'] ?? 'Ya'),
            'sign_out_komplikasi_tindakan' => trim($_POST['sign_out_komplikasi_tindakan'] ?? 'Ya'),
            'sign_out_dr_operator' => trim($_POST['sign_out_dr_operator'] ?? ''),
            'sign_out_perawat' => trim($_POST['sign_out_perawat'] ?? ''),

            'updated_at' => $now
        ];

        if ($id) {
            $this->db('bm5_checklist_keselamatan_operasi')->where('id', $id)->save($saveData);
            $this->notify('success', 'Data BM5 Checklist Keselamatan Pasien Operasi berhasil diperbarui.');
        } else {
            $saveData['created_at'] = $now;
            $this->db('bm5_checklist_keselamatan_operasi')->save($saveData);
            $this->notify('success', 'Data BM5 Checklist Keselamatan Pasien Operasi berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'bm5manage']));
    }

    public function postBm5save()
    {
        return $this->anyBm5save();
    }

    public function anyBm5hapus($id = null)
    {
        return $this->getBm5hapus($id);
    }

    public function getBm5hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('bm5_checklist_keselamatan_operasi')->where('id', $id)->delete();
            $this->notify('success', 'Data BM5 Checklist Keselamatan Pasien Operasi berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'bm5manage']));
    }

    public function anyBm5cetak($id = null)
    {
        return $this->getBm5cetak($id);
    }

    public function getBm5cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id && isset($_SERVER['REQUEST_URI']) && preg_match('/[?&]id=([0-9]+)/', $_SERVER['REQUEST_URI'], $m)) {
            $id = $m[1];
        }
        if (!$id) {
            echo "ID tidak ditemukan.";
            exit();
        }

        $data = $this->db('bm5_checklist_keselamatan_operasi')
            ->leftJoin('reg_periksa', 'reg_periksa.no_rawat = bm5_checklist_keselamatan_operasi.no_rawat')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis)')
            ->where('bm5_checklist_keselamatan_operasi.id', $id)
            ->select('bm5_checklist_keselamatan_operasi.*, COALESCE(bm5_checklist_keselamatan_operasi.no_rkm_medis, reg_periksa.no_rkm_medis) as no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.umur, pasien.tgl_lahir')
            ->oneArray();

        if (!$data) {
            $this->notify('failure', 'Data Checklist Keselamatan Operasi tidak ditemukan!');
            redirect(url([ADMIN, 'form_rme', 'bm5manage']));
        }

        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if (!empty($data['tanggal']) && $data['tanggal'] != '0000-00-00') {
            $p = explode('-', $data['tanggal']);
            $data['tgl_indo'] = (int)$p[2] . ' ' . ($bulan[(int)$p[1]] ?? '') . ' ' . $p[0];
        } else {
            $data['tgl_indo'] = '-';
        }

        if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
            $tgl = explode('-', $data['tgl_lahir']);
            $data['tgl_lahir_indo'] = (int)$tgl[2] . ' ' . ($bulan[(int)$tgl[1]] ?? '') . ' ' . $tgl[0];
        } else {
            $data['tgl_lahir_indo'] = '-';
        }

        // Format dates for stages to d-m-Y if in YYYY-MM-DD format
        if (!empty($data['sign_in_tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['sign_in_tanggal'])) {
            $data['sign_in_tanggal'] = date('d-m-Y', strtotime($data['sign_in_tanggal']));
        }
        if (!empty($data['time_out_tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['time_out_tanggal'])) {
            $data['time_out_tanggal'] = date('d-m-Y', strtotime($data['time_out_tanggal']));
        }
        if (!empty($data['sign_out_tanggal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['sign_out_tanggal'])) {
            $data['sign_out_tanggal'] = date('d-m-Y', strtotime($data['sign_out_tanggal']));
        }

        // Prepare 6 digits for RM box
        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split($padded_rm);
        if (count($rm_digits) < 6) {
            $rm_digits = array_pad($rm_digits, 6, '&nbsp;');
        }

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'rm_digits' => $rm_digits,
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            try {
                $oldErr = error_reporting(0);
                @ini_set('display_errors', '0');
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4-L',
                    'orientation' => 'L',
                    'margin_left' => 6,
                    'margin_right' => 6,
                    'margin_top' => 5,
                    'margin_bottom' => 5,
                    'tempDir' => BASE_DIR . '/tmp'
                ]);

                $html = $this->draw('bm5/cetak.html', $viewParams);
                $mpdf->WriteHTML($html);
                $filename = 'BM5_Checklist_Keselamatan_Operasi_' . str_replace('/', '_', $data['no_rawat']) . '.pdf';

                while (ob_get_level()) {
                    ob_end_clean();
                }

                $mpdf->Output($filename, 'I');
                exit();
            } catch (\Exception $e) {
                error_reporting($oldErr);
                echo "Gagal mengenerate PDF: " . htmlspecialchars($e->getMessage());
            }
        }

        echo $this->draw('bm5/cetak.html', $viewParams);
        exit();
    }

    public function anyRmgd3manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('mlite_triase_igd')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = mlite_triase_igd.no_rkm_medis')
            ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
            ->select('mlite_triase_igd.*, pasien.nm_pasien, pasien.jk, petugas.nama as nama_petugas')
            ->desc('mlite_triase_igd.id_triase')
            ->toArray();

        foreach ($rows as &$r) {
            if (!empty($r['tgl_triase'])) {
                $ts = strtotime($r['tgl_triase']);
                $r['tgl_triase_fmt'] = date('d-m-Y', $ts);
                $r['jam_mulai_fmt'] = date('H:i', $ts) . ' WITA';
            } else {
                $r['tgl_triase_fmt'] = '-';
                $r['jam_mulai_fmt'] = '';
            }
            if (empty($r['nama_petugas'])) {
                $r['nama_petugas'] = $r['petugas_id'] ?? '-';
            }
        }

        return $this->draw('rmgd3_triase/manage.html', ['data' => $rows]);
    }

    public function getRmgd3manage()
    {
        return $this->anyRmgd3manage();
    }

    public function anyRmgd3form($id = null)
    {
        if (isset($_GET['ajax_patient'])) {
            $q = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($q) && strlen($q) < 6) {
                $q = str_pad($q, 6, '0', STR_PAD_LEFT);
            }
            $result = ['success' => false];
            if (!empty($q)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $q)
                    ->orWhere('reg_periksa.no_rkm_medis', $q)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();
                if ($reg) {
                    $result = [
                        'success' => true,
                        'no_rawat' => $reg['no_rawat'],
                        'no_rkm_medis' => $reg['no_rkm_medis'],
                        'nama_pasien' => $reg['nm_pasien'],
                        'jk' => $reg['jk'],
                        'tgl_lahir' => $reg['tgl_lahir']
                    ];
                }
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $no_rawat_param = trim($_GET['no_rawat'] ?? '');

        $data = [
            'id_triase' => '',
            'no_rawat' => $no_rawat_param,
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'tgl_triase_input' => date('Y-m-d\TH:i'),
            'jam_selesai_triase' => date('H:i'),
            'petugas_id' => $this->core->getUserInfo('username', null, true),
            'kesadaran' => 'Compos Mentis',
            'airway' => 'Bebas',
            'breathing' => 'Spontan',
            'circulation' => 'Baik',
            'tekanan_darah' => '',
            'nadi' => '',
            'respirasi' => '',
            'suhu' => '',
            'spo2' => '',
            'gcs_e' => '4',
            'gcs_v' => '5',
            'gcs_m' => '6',
            'kategori' => 'Hijau',
            'skala_triase' => '4',
            'keluhan_utama' => '',
            'diagnosa_awal' => '',
            'ats_skala' => '',
            'ats_items' => '[]',
            'sdcep_skala' => '',
            'sdcep_items' => '[]',
            'doa_status' => 0,
            'doa_items' => '[]',
            'jam_doa' => ''
        ];

        $row = null;
        if (!empty($id)) {
            $row = $this->db('mlite_triase_igd')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = mlite_triase_igd.no_rkm_medis')
                ->where('mlite_triase_igd.id_triase', $id)
                ->select('mlite_triase_igd.*, pasien.nm_pasien')
                ->oneArray();
        } elseif (!empty($no_rawat_param)) {
            $row = $this->db('mlite_triase_igd')
                ->leftJoin('pasien', 'pasien.no_rkm_medis = mlite_triase_igd.no_rkm_medis')
                ->where('mlite_triase_igd.no_rawat', $no_rawat_param)
                ->select('mlite_triase_igd.*, pasien.nm_pasien')
                ->desc('mlite_triase_igd.id_triase')
                ->oneArray();
            if (!$row) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat_param)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien')
                    ->oneArray();
                if ($reg) {
                    $data['no_rawat'] = $reg['no_rawat'];
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nm_pasien'] = $reg['nm_pasien'];
                }
            }
        }

        if ($row) {
            $data = array_merge($data, $row);
            if (!empty($row['tgl_triase'])) {
                $data['tgl_triase_input'] = date('Y-m-d\TH:i', strtotime($row['tgl_triase']));
            }
            if (empty($data['jam_selesai_triase']) && !empty($row['tgl_triase'])) {
                $data['jam_selesai_triase'] = date('H:i', strtotime($row['tgl_triase']));
            }
        }

        $ats_checked = !empty($data['ats_items']) ? (json_decode($data['ats_items'], true) ?: []) : [];
        $sdcep_checked = !empty($data['sdcep_items']) ? (json_decode($data['sdcep_items'], true) ?: []) : [];
        $doa_checked = !empty($data['doa_items']) ? (json_decode($data['doa_items'], true) ?: []) : [];

        $lists = $this->_getTriaseCriteriaLists($ats_checked, $sdcep_checked);
        $doa_map = [
            'tanda_kehidupan' => in_array('Tanda Kehidupan (-)', $doa_checked),
            'pupil' => in_array('Pupil RCL/RCTL (-/-)', $doa_checked),
            'denyut_nadi' => in_array('Denyut Nadi (-)', $doa_checked),
            'ekg_flat' => in_array('EKG flat', $doa_checked),
        ];

        $petugas = $this->db('petugas')->where('status', '1')->toArray();
        $pasien_list = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien')
            ->desc('reg_periksa.tgl_registrasi')
            ->desc('reg_periksa.jam_reg')
            ->limit(40)
            ->toArray();

        return $this->draw('rmgd3_triase/form.html', array_merge([
            'data' => $data,
            'petugas' => $petugas,
            'pasien_list' => $pasien_list,
            'doa_map' => $doa_map
        ], $lists));
    }

    public function getRmgd3form($id = null)
    {
        return $this->anyRmgd3form($id);
    }

    public function anyRmgd3save()
    {
        if (empty($_POST)) {
            redirect(url([ADMIN, 'form_rme', 'rmgd3manage']));
        }

        $id_triase = !empty($_POST['id_triase']) ? (int)$_POST['id_triase'] : null;
        $no_rawat = trim($_POST['no_rawat'] ?? '');
        $no_rkm_medis = trim($_POST['no_rkm_medis'] ?? '');

        if (empty($no_rkm_medis) && !empty($no_rawat)) {
            $reg = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
            if ($reg) {
                $no_rkm_medis = $reg['no_rkm_medis'];
            }
        }

        $tgl_triase = !empty($_POST['tgl_triase']) ? date('Y-m-d H:i:s', strtotime($_POST['tgl_triase'])) : date('Y-m-d H:i:s');
        $jam_selesai = trim($_POST['jam_selesai_triase'] ?? date('H:i'));
        $tgl_selesai = date('Y-m-d', strtotime($tgl_triase)) . ' ' . (strlen($jam_selesai) == 5 ? $jam_selesai . ':00' : $jam_selesai);

        $ats_items = isset($_POST['ats_items']) && is_array($_POST['ats_items']) ? array_values($_POST['ats_items']) : [];
        $sdcep_items = isset($_POST['sdcep_items']) && is_array($_POST['sdcep_items']) ? array_values($_POST['sdcep_items']) : [];
        $doa_items = isset($_POST['doa_items']) && is_array($_POST['doa_items']) ? array_values($_POST['doa_items']) : [];
        $jam_doa = trim($_POST['jam_doa'] ?? '');
        $doa_status = (!empty($doa_items) || !empty($jam_doa)) ? 1 : 0;

        $ats_skala = trim($_POST['ats_skala'] ?? '');
        $skala_triase = trim($_POST['skala_triase'] ?? '');
        if (empty($ats_skala) && !empty($skala_triase)) {
            $ats_skala = 'ATS ' . $skala_triase;
        } elseif (!empty($ats_skala) && empty($skala_triase)) {
            $skala_triase = preg_replace('/[^1-5]/', '', $ats_skala);
        }

        $saveData = [
            'no_rawat' => $no_rawat,
            'no_rkm_medis' => $no_rkm_medis,
            'tgl_triase' => $tgl_triase,
            'jam_selesai_triase' => $jam_selesai,
            'tgl_selesai_triase' => $tgl_selesai,
            'petugas_id' => !empty($_POST['petugas_id']) ? $_POST['petugas_id'] : $this->core->getUserInfo('username', null, true),
            'kesadaran' => !empty($_POST['kesadaran']) ? $_POST['kesadaran'] : 'Compos Mentis',
            'airway' => !empty($_POST['airway']) ? $_POST['airway'] : 'Bebas',
            'breathing' => !empty($_POST['breathing']) ? $_POST['breathing'] : 'Spontan',
            'circulation' => !empty($_POST['circulation']) ? $_POST['circulation'] : 'Baik',
            'tekanan_darah' => $_POST['tekanan_darah'] !== '' ? $_POST['tekanan_darah'] : null,
            'nadi' => $_POST['nadi'] !== '' ? (int)$_POST['nadi'] : null,
            'respirasi' => $_POST['respirasi'] !== '' ? (int)$_POST['respirasi'] : null,
            'suhu' => $_POST['suhu'] !== '' ? $_POST['suhu'] : null,
            'spo2' => $_POST['spo2'] !== '' ? (int)$_POST['spo2'] : null,
            'gcs_e' => $_POST['gcs_e'] !== '' ? (int)$_POST['gcs_e'] : null,
            'gcs_v' => $_POST['gcs_v'] !== '' ? (int)$_POST['gcs_v'] : null,
            'gcs_m' => $_POST['gcs_m'] !== '' ? (int)$_POST['gcs_m'] : null,
            'kategori' => !empty($_POST['kategori']) ? $_POST['kategori'] : 'Hijau',
            'skala_triase' => in_array($skala_triase, ['1','2','3','4','5']) ? $skala_triase : null,
            'keluhan_utama' => $_POST['keluhan_utama'] ?? '',
            'diagnosa_awal' => $_POST['diagnosa_awal'] ?? '',
            'ats_skala' => $ats_skala,
            'ats_items' => json_encode($ats_items, JSON_UNESCAPED_UNICODE),
            'sdcep_skala' => trim($_POST['sdcep_skala'] ?? ''),
            'sdcep_items' => json_encode($sdcep_items, JSON_UNESCAPED_UNICODE),
            'doa_status' => $doa_status,
            'doa_items' => json_encode($doa_items, JSON_UNESCAPED_UNICODE),
            'jam_doa' => $jam_doa,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id_triase) {
            $fields = array_keys($saveData);
            $set_clause = [];
            foreach ($fields as $f) {
                $set_clause[] = "`{$f}` = :{$f}";
            }
            $sql = "UPDATE mlite_triase_igd SET " . implode(', ', $set_clause) . " WHERE id_triase = :id_triase";
            $saveData['id_triase'] = $id_triase;
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute($saveData);
            $this->notify('success', 'Data Lembar Triase (RM.GD 3) berhasil diperbarui.');
        } else {
            $fields = array_keys($saveData);
            $placeholders = ':' . implode(', :', $fields);
            $escaped = array_map(function($f) { return "`{$f}`"; }, $fields);
            $sql = "INSERT INTO mlite_triase_igd (" . implode(', ', $escaped) . ") VALUES (" . $placeholders . ")";
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute($saveData);
            $this->notify('success', 'Data Lembar Triase (RM.GD 3) berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'rmgd3manage']));
    }

    public function postRmgd3save()
    {
        return $this->anyRmgd3save();
    }

    public function anyRmgd3hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('mlite_triase_igd')->where('id_triase', $id)->delete();
            $this->notify('success', 'Data Lembar Triase berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'rmgd3manage']));
    }

    public function getRmgd3hapus($id = null)
    {
        return $this->anyRmgd3hapus($id);
    }

    public function anyRmgd3cetak($id = null)
    {
        return $this->getRmgd3cetak($id);
    }

    public function getRmgd3cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        $no_rawat = $_GET['no_rawat'] ?? null;

        $q = $this->db('mlite_triase_igd')
            ->leftJoin('pasien', 'pasien.no_rkm_medis = mlite_triase_igd.no_rkm_medis')
            ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
            ->select('mlite_triase_igd.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, petugas.nama as nama_petugas');

        if (!empty($id)) {
            $data = $q->where('mlite_triase_igd.id_triase', $id)->oneArray();
        } elseif (!empty($no_rawat)) {
            $data = $q->where('mlite_triase_igd.no_rawat', $no_rawat)->desc('mlite_triase_igd.id_triase')->oneArray();
        } else {
            echo "ID atau No. Rawat tidak ditemukan.";
            exit();
        }

        if (!$data) {
            echo "Data Triase tidak ditemukan.";
            exit();
        }

        if (empty($data['nama_petugas'])) {
            $data['nama_petugas'] = $data['petugas_id'] ?? '-';
        }
        $data['jk_label'] = ($data['jk'] ?? '') == 'L' ? 'L' : (($data['jk'] ?? '') == 'P' ? 'P' : '-');
        $data['tgl_lahir_fmt'] = (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') ? date('d / m / Y', strtotime($data['tgl_lahir'])) : '-';

        if (!empty($data['tgl_triase'])) {
            $ts = strtotime($data['tgl_triase']);
            $data['tgl_kunjungan_fmt'] = date('d / m / Y', $ts);
            $data['jam_mulai_fmt'] = date('H:i', $ts);
            $data['tgl_selesai_fmt'] = date('d / m / Y', $ts);
        } else {
            $data['tgl_kunjungan_fmt'] = '.... / .... / 20....';
            $data['jam_mulai_fmt'] = '........';
            $data['tgl_selesai_fmt'] = '.... / .... / 20....';
        }

        $data['jam_selesai_fmt'] = !empty($data['jam_selesai_triase']) ? $data['jam_selesai_triase'] : $data['jam_mulai_fmt'];

        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split(substr($padded_rm, -6));

        $ats_checked = !empty($data['ats_items']) ? (json_decode($data['ats_items'], true) ?: []) : [];
        $sdcep_checked = !empty($data['sdcep_items']) ? (json_decode($data['sdcep_items'], true) ?: []) : [];
        $doa_checked = !empty($data['doa_items']) ? (json_decode($data['doa_items'], true) ?: []) : [];

        $lists = $this->_getTriaseCriteriaLists($ats_checked, $sdcep_checked);
        $doa_map = [
            'tanda_kehidupan' => in_array('Tanda Kehidupan (-)', $doa_checked),
            'pupil' => in_array('Pupil RCL/RCTL (-/-)', $doa_checked),
            'denyut_nadi' => in_array('Denyut Nadi (-)', $doa_checked),
            'ekg_flat' => in_array('EKG flat', $doa_checked),
        ];

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $viewParams = array_merge([
            'data' => $data,
            'kop_img' => $kop_img,
            'rm_digits' => $rm_digits,
            'doa_map' => $doa_map,
            'is_mpdf' => $is_pdf
        ], $lists);

        if ($is_pdf) {
            $oldErr = error_reporting(0);
            @ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 6,
                'margin_bottom' => 6,
                'tempDir' => BASE_DIR . '/tmp'
            ]);
            $html = $this->draw('rmgd3_triase/cetak.html', $viewParams);
            $mpdf->WriteHTML($html);
            while (ob_get_level()) {
                ob_end_clean();
            }
            $mpdf->Output('RM_GD_03_Lembar_Triase_' . str_replace('/', '_', $data['no_rawat']) . '.pdf', 'I');
            exit();
        }

        echo $this->draw('rmgd3_triase/cetak.html', $viewParams);
        exit();
    }

    public function anyRmgd5manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('asesmen_medis_gigi_ugd')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_medis_gigi_ugd.kd_dokter')
            ->select('asesmen_medis_gigi_ugd.*, dokter.nm_dokter')
            ->desc('asesmen_medis_gigi_ugd.id')
            ->toArray();

        return $this->draw('rmgd5_medis_gigi/manage.html', ['data' => $rows]);
    }

    public function getRmgd5manage()
    {
        return $this->anyRmgd5manage();
    }

    public function anyRmgd5form($id = null)
    {
        if (isset($_GET['ajax_patient'])) {
            $q = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($q) && strlen($q) < 6) {
                $q = str_pad($q, 6, '0', STR_PAD_LEFT);
            }
            $result = ['success' => false];
            if (!empty($q)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $q)
                    ->orWhere('reg_periksa.no_rkm_medis', $q)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.kd_dokter, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();
                if ($reg) {
                    $triase = $this->db('mlite_triase_igd')
                        ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
                        ->where('mlite_triase_igd.no_rawat', $reg['no_rawat'])
                        ->select('mlite_triase_igd.*, petugas.nama as nama_petugas')
                        ->desc('mlite_triase_igd.id_triase')
                        ->oneArray();
                    $pem = $this->db('pemeriksaan_ralan')
                        ->where('no_rawat', $reg['no_rawat'])
                        ->desc('tgl_perawatan')
                        ->desc('jam_rawat')
                        ->oneArray();

                    $result = [
                        'success' => true,
                        'no_rawat' => $reg['no_rawat'],
                        'no_rkm_medis' => $reg['no_rkm_medis'],
                        'nama_pasien' => $reg['nm_pasien'],
                        'jk' => $reg['jk'],
                        'tgl_lahir' => $reg['tgl_lahir'],
                        'gol_darah' => $reg['gol_darah'] ?? '',
                        'kd_dokter' => $reg['kd_dokter'] ?? '',
                        'triase' => $triase ?: null,
                        'pemeriksaan' => $pem ?: null
                    ];
                }
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $no_rawat_param = trim($_GET['no_rawat'] ?? '');

        $data = [
            'id' => '',
            'no_rawat' => $no_rawat_param,
            'no_rkm_medis' => '',
            'nama_pasien' => '',
            'jenis_kelamin' => '',
            'tanggal_lahir' => '',
            'jenis_kasus' => 'Non Trauma',
            'sumber_data' => 'Auto Anamnesis',
            'kategori_pasien' => 'Pasien Dewasa (18-65 tahun)',
            'tanggal_kunjungan' => date('Y-m-d'),
            'pukul_kunjungan' => date('H:i'),
            'triase' => 'Hijau',
            'jam_mulai_triase' => '',
            'jam_selesai_triase' => '',
            'petugas_triase' => '',
            'jn_paten' => 'Ya',
            'jn_obstruksi_parsial' => 'Tidak',
            'jn_stridor' => 'Tidak',
            'jn_snoring' => 'Tidak',
            'jn_gurgling' => 'Tidak',
            'jn_wheezing' => 'Tidak',
            'jn_obstruksi_total' => 'Tidak',
            'jn_trauma_jalan_napas' => 'Tidak',
            'jn_resiko_aspirasi' => 'Tidak',
            'jn_benda_asing' => 'Tidak',
            'jn_kesimpulan' => 'Aman',
            'napas_spontan' => 'Spontan',
            'napas_reguler' => 'Reguler',
            'napas_gerakan_dada' => 'Simetris',
            'napas_jejas_dada' => 'Tidak',
            'napas_jejas_kanan' => 'Tidak',
            'napas_jejas_kiri' => 'Tidak',
            'napas_tipe_normal' => 'Ya',
            'napas_tipe_takipnue' => 'Tidak',
            'napas_tipe_kussmaul' => 'Tidak',
            'napas_tipe_hiperventilasi' => 'Tidak',
            'napas_tipe_biot' => 'Tidak',
            'napas_tipe_cheyne_stokes' => 'Tidak',
            'napas_tipe_apneustic' => 'Tidak',
            'napas_kesimpulan' => 'Aman',
            'sirk_nadi_irama' => 'Reguler',
            'sirk_nadi_kekuatan' => 'Kuat',
            'sirk_kulit_normal' => 'Ya',
            'sirk_kulit_pucat' => 'Tidak',
            'sirk_kulit_jaundice' => 'Tidak',
            'sirk_kulit_sianosis' => 'Tidak',
            'sirk_kulit_berkeringat' => 'Tidak',
            'sirk_akral_suhu' => 'Hangat',
            'sirk_akral_lembab' => 'Kering',
            'sirk_crt' => '< 2 detik',
            'sirk_kesimpulan' => 'Aman',
            'dis_gcs_e' => '4',
            'dis_gcs_m' => '6',
            'dis_gcs_v' => '5',
            'dis_pupil' => 'Isokor',
            'dis_diameter_kanan' => '2',
            'dis_diameter_kiri' => '2',
            'dis_refleks_kanan' => '+',
            'dis_refleks_kiri' => '+',
            'eks_tidak_kelainan' => 'Ya',
            'eks_tidak_tampak_jelas' => 'Tidak',
            'eks_deformitas' => 'Tidak',
            'eks_contusio' => 'Tidak',
            'eks_abrasi' => 'Tidak',
            'eks_penetrasi' => 'Tidak',
            'eks_luka_bakar' => 'Tidak',
            'eks_laserasi' => 'Tidak',
            'eks_ekskoriasi' => 'Tidak',
            'keluhan_utama' => '',
            'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '',
            'riwayat_penyakit_keluarga' => '',
            'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_ket' => '',
            'riwayat_penggunaan_obat' => '',
            'rp_menular_hiv' => 'Tidak',
            'rp_menular_tbc' => 'Tidak',
            'rp_menular_hepatitis' => 'Tidak',
            'rp_menular_lainnya' => 'Tidak',
            'rp_menular_lainnya_ket' => '',
            'rp_jantung' => 'Tidak Ada',
            'rp_hipertensi' => 'Tidak Ada',
            'rp_diabetes' => 'Tidak Ada',
            'rp_haemophilia' => 'Tidak Ada',
            'rp_ginjal' => 'Tidak Ada',
            'rp_ginjal_ket' => '',
            'rp_sistemik_lain' => 'Tidak Ada',
            'rp_sistemik_lain_ket' => '',
            'rp_operasi' => 'Tidak Ada',
            'rp_operasi_ket' => '',
            'anak_ke' => '',
            'anak_dari_saudara' => '',
            'anak_cara_lahir' => '',
            'anak_cara_lahir_ket' => '',
            'anak_umur_lahir' => '',
            'anak_kelainan_bawaan' => '',
            'anak_kelainan_bawaan_ket' => '',
            'imun_bcg' => 'Tidak',
            'imun_campak' => 'Tidak',
            'imun_campak_kali' => '',
            'imun_polio' => 'Tidak',
            'imun_polio_kali' => '',
            'imun_hepatitis_b' => 'Tidak',
            'imun_hepatitis_b_kali' => '',
            'imun_dpt' => 'Tidak',
            'imun_dpt_kali' => '',
            'imun_lainnya' => 'Tidak',
            'imun_lainnya_ket' => '',
            'imun_lainnya_kali' => '',
            'tk_gigi_pertama' => '',
            'tk_duduk' => '',
            'tk_berjalan' => '',
            'tk_bicara' => '',
            'tk_membaca' => '',
            'geriatri_penglihatan' => 'Tidak',
            'geriatri_pendengaran' => 'Tidak',
            'geriatri_berkemih' => 'Tidak',
            'geriatri_daya_ingat' => 'Tidak',
            'geriatri_bicara' => 'Tidak',
            'geriatri_gigi_palsu' => 'Tidak',
            'geriatri_lainnya' => 'Tidak',
            'geriatri_lainnya_ket' => '',
            'keadaan_umum' => 'Baik',
            'kesadaran' => 'Compos Mentis',
            'td' => '',
            'nadi' => '',
            'rr' => '',
            'saturasi' => '',
            'suhu' => '',
            'gol_darah' => '',
            'bb' => '',
            'tb' => '',
            'eo_kepala_leher' => '',
            'eo_kepala' => 'DBN',
            'eo_wajah' => 'Simetris',
            'eo_tmj' => 'DBN',
            'eo_tiroid' => 'DBN',
            'eo_leher' => 'DBN',
            'eo_limfe_submandibular_dextra' => 'DBN',
            'eo_limfe_submandibular_sinistra' => 'DBN',
            'eo_limfe_submentalis' => 'DBN',
            'eo_limfe_cervikalis' => 'DBN',
            'io_elemen_gigi' => '',
            'io_gigi_karies' => 'Tidak',
            'io_karies_superfisial' => 'Tidak',
            'io_karies_media' => 'Tidak',
            'io_karies_profunda' => 'Tidak',
            'io_sisa_akar' => 'Tidak',
            'io_perkusi_sakit' => 'Tidak',
            'io_druk_sakit' => 'Tidak',
            'io_diskolorisasi' => 'Tidak',
            'io_palpasi_sakit' => 'Tidak',
            'io_gingiva' => 'Tidak',
            'io_gingiva_normal' => 'Tidak',
            'io_gingiva_hiperemis' => 'Tidak',
            'io_gingiva_resesi' => 'Tidak',
            'io_kalkulus' => 'Tidak',
            'io_luksasi' => 'Tidak',
            'io_luksasi_derajat' => '',
            'io_fraktur_mahkota' => 'Tidak',
            'io_fraktur_lokasi' => '',
            'io_lainnya' => '',
            'io_tes_termal' => '',
            'io_tes_kavitas' => '',
            'io_tes_penunjang' => '',
            'io_kfile_1_kode' => '',
            'io_kfile_1_mm' => '',
            'io_kfile_2_kode' => '',
            'io_kfile_2_mm' => '',
            'io_kfile_3_kode' => '',
            'io_kfile_3_mm' => '',
            'io_kfile_4_kode' => '',
            'io_kfile_4_mm' => '',
            'io_vitalitas_gigi' => '',
            'nyeri_keluhan' => 'Tidak',
            'nyeri_lokasi' => '',
            'nyeri_durasi' => '',
            'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '',
            'nyeri_menjalar' => 'Tidak',
            'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '',
            'nyeri_hilang' => '',
            'nyeri_tl_edukasi' => 'Tidak',
            'nyeri_tl_terapi' => 'Tidak',
            'nyeri_tl_konsul' => 'Tidak',
            'nyeri_tl_konsul_ke' => '',
            'skala_nyeri' => '0',
            'flacc_wajah' => 0,
            'flacc_kaki' => 0,
            'flacc_aktivitas' => 0,
            'flacc_menangis' => 0,
            'flacc_consolability' => 0,
            'flacc_total' => 0,
            'penunjang_lab' => 'Tidak',
            'penunjang_rontgen' => 'Tidak',
            'penunjang_lain' => 'Tidak',
            'penunjang_lain_ket' => '',
            'diagnosis' => '',
            'diagnosis_banding' => '',
            'rencana_tindak_lanjut' => '',
            'evaluasi_kontrol' => 'Tidak',
            'evaluasi_kontrol_tgl' => '',
            'evaluasi_rujuk' => 'Tidak',
            'evaluasi_rujuk_rs' => '',
            'evaluasi_ranap' => 'Tidak',
            'evaluasi_ranap_ruangan' => '',
            'tanggal_ttd' => date('Y-m-d'),
            'pukul_ttd' => date('H:i'),
            'kd_dokter' => $this->core->getUserInfo('username', null, true)
        ];

        if ($id) {
            $row = $this->db('asesmen_medis_gigi_ugd')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
            }
        } elseif (!empty($no_rawat_param)) {
            $existing = $this->db('asesmen_medis_gigi_ugd')
                ->where('no_rawat', $no_rawat_param)
                ->desc('id')
                ->oneArray();
            if ($existing) {
                $data = array_merge($data, $existing);
            } else {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat_param)
                    ->select('reg_periksa.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah')
                    ->oneArray();
                if ($reg) {
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nama_pasien'] = $reg['nm_pasien'];
                    $data['jenis_kelamin'] = $reg['jk'];
                    $data['tanggal_lahir'] = $reg['tgl_lahir'];
                    $data['gol_darah'] = $reg['gol_darah'] ?? '';
                    if (!empty($reg['kd_dokter'])) {
                        $data['kd_dokter'] = $reg['kd_dokter'];
                    }
                    if (!empty($reg['tgl_lahir'])) {
                        $age = (int)date('Y') - (int)date('Y', strtotime($reg['tgl_lahir']));
                        if ($age < 18) {
                            $data['kategori_pasien'] = 'Pasien Anak (0-<18 tahun)';
                        } elseif ($age > 65) {
                            $data['kategori_pasien'] = 'Pasien Geriatri (>65 tahun)';
                        } else {
                            $data['kategori_pasien'] = 'Pasien Dewasa (18-65 tahun)';
                        }
                    }
                }
                $triase = $this->db('mlite_triase_igd')
                    ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
                    ->where('mlite_triase_igd.no_rawat', $no_rawat_param)
                    ->select('mlite_triase_igd.*, petugas.nama as nama_petugas')
                    ->desc('mlite_triase_igd.id_triase')
                    ->oneArray();
                if ($triase) {
                    $data['triase'] = $triase['kategori'] ?? 'Hijau';
                    if (!empty($triase['tgl_triase'])) {
                        $data['jam_mulai_triase'] = date('H:i', strtotime($triase['tgl_triase']));
                    }
                    $data['jam_selesai_triase'] = $triase['jam_selesai_triase'] ?? $data['jam_mulai_triase'];
                    $data['petugas_triase'] = !empty($triase['nama_petugas']) ? $triase['nama_petugas'] : ($triase['petugas_id'] ?? '');
                    if (!empty($triase['keluhan_utama'])) {
                        $data['keluhan_utama'] = $triase['keluhan_utama'];
                    }
                    if (!empty($triase['tekanan_darah'])) {
                        $data['td'] = $triase['tekanan_darah'];
                    }
                    if (!empty($triase['nadi'])) {
                        $data['nadi'] = $triase['nadi'];
                    }
                    if (!empty($triase['respirasi'])) {
                        $data['rr'] = $triase['respirasi'];
                    }
                    if (!empty($triase['suhu'])) {
                        $data['suhu'] = $triase['suhu'];
                    }
                    if (!empty($triase['spo2'])) {
                        $data['saturasi'] = $triase['spo2'];
                    }
                    if (!empty($triase['gcs_e'])) {
                        $data['dis_gcs_e'] = $triase['gcs_e'];
                    }
                    if (!empty($triase['gcs_m'])) {
                        $data['dis_gcs_m'] = $triase['gcs_m'];
                    }
                    if (!empty($triase['gcs_v'])) {
                        $data['dis_gcs_v'] = $triase['gcs_v'];
                    }
                }
                $pem = $this->db('pemeriksaan_ralan')
                    ->where('no_rawat', $no_rawat_param)
                    ->desc('tgl_perawatan')
                    ->desc('jam_rawat')
                    ->oneArray();
                if ($pem) {
                    if (empty($data['keluhan_utama']) && !empty($pem['keluhan'])) {
                        $data['keluhan_utama'] = $pem['keluhan'];
                    }
                    if (empty($data['td']) && !empty($pem['tensi'])) {
                        $data['td'] = $pem['tensi'];
                    }
                    if (empty($data['nadi']) && !empty($pem['nadi'])) {
                        $data['nadi'] = $pem['nadi'];
                    }
                    if (empty($data['rr']) && !empty($pem['respirasi'])) {
                        $data['rr'] = $pem['respirasi'];
                    }
                    if (empty($data['suhu']) && !empty($pem['suhu_tubuh'])) {
                        $data['suhu'] = $pem['suhu_tubuh'];
                    }
                    if (empty($data['saturasi']) && !empty($pem['spo2'])) {
                        $data['saturasi'] = $pem['spo2'];
                    }
                    if (!empty($pem['berat'])) {
                        $data['bb'] = $pem['berat'];
                    }
                    if (!empty($pem['tinggi'])) {
                        $data['tb'] = $pem['tinggi'];
                    }
                    if (!empty($pem['kesadaran'])) {
                        $data['kesadaran'] = $pem['kesadaran'];
                    }
                    if (!empty($pem['alergi'])) {
                        $data['riwayat_alergi'] = ($pem['alergi'] !== '-' && strtolower($pem['alergi']) !== 'tidak ada') ? 'Ya' : 'Tidak';
                        $data['riwayat_alergi_ket'] = $pem['alergi'];
                    }
                    if (!empty($pem['penilaian'])) {
                        $data['diagnosis'] = $pem['penilaian'];
                    }
                    if (!empty($pem['rtl'])) {
                        $data['rencana_tindak_lanjut'] = $pem['rtl'];
                    }
                }
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('rmgd5_medis_gigi/form.html', [
            'data' => $data,
            'dokter' => $dokter
        ]);
    }

    public function getRmgd5form($id = null)
    {
        return $this->anyRmgd5form($id);
    }

    public function anyRmgd5save()
    {
        $post = $_POST;
        $id = $post['id'] ?? '';
        unset($post['id'], $post['save']);

        $checkboxes = [
            'jn_paten', 'jn_obstruksi_parsial', 'jn_stridor', 'jn_snoring', 'jn_gurgling', 'jn_wheezing',
            'jn_obstruksi_total', 'jn_trauma_jalan_napas', 'jn_resiko_aspirasi', 'jn_benda_asing',
            'napas_jejas_dada', 'napas_jejas_kanan', 'napas_jejas_kiri',
            'napas_tipe_normal', 'napas_tipe_takipnue', 'napas_tipe_kussmaul', 'napas_tipe_hiperventilasi',
            'napas_tipe_biot', 'napas_tipe_cheyne_stokes', 'napas_tipe_apneustic',
            'sirk_kulit_normal', 'sirk_kulit_pucat', 'sirk_kulit_jaundice', 'sirk_kulit_sianosis', 'sirk_kulit_berkeringat',
            'eks_tidak_kelainan', 'eks_tidak_tampak_jelas', 'eks_deformitas', 'eks_contusio', 'eks_abrasi',
            'eks_penetrasi', 'eks_luka_bakar', 'eks_laserasi', 'eks_ekskoriasi',
            'rp_menular_hiv', 'rp_menular_tbc', 'rp_menular_hepatitis', 'rp_menular_lainnya',
            'imun_bcg', 'imun_campak', 'imun_polio', 'imun_hepatitis_b', 'imun_dpt', 'imun_lainnya',
            'geriatri_penglihatan', 'geriatri_pendengaran', 'geriatri_berkemih', 'geriatri_daya_ingat',
            'geriatri_bicara', 'geriatri_gigi_palsu', 'geriatri_lainnya',
            'io_gigi_karies', 'io_karies_superfisial', 'io_karies_media', 'io_karies_profunda',
            'io_sisa_akar', 'io_perkusi_sakit', 'io_druk_sakit', 'io_diskolorisasi', 'io_palpasi_sakit',
            'io_gingiva', 'io_gingiva_normal', 'io_gingiva_hiperemis', 'io_gingiva_resesi',
            'io_kalkulus', 'io_luksasi', 'io_fraktur_mahkota',
            'nyeri_tl_edukasi', 'nyeri_tl_terapi', 'nyeri_tl_konsul',
            'penunjang_lab', 'penunjang_rontgen', 'penunjang_lain',
            'evaluasi_kontrol', 'evaluasi_rujuk', 'evaluasi_ranap'
        ];

        foreach ($checkboxes as $cb) {
            if (!isset($post[$cb])) {
                $post[$cb] = 'Tidak';
            }
        }

        if (empty($post['tanggal_kunjungan'])) {
            $post['tanggal_kunjungan'] = date('Y-m-d');
        }
        if (empty($post['tanggal_lahir'])) {
            $post['tanggal_lahir'] = null;
        }
        if (empty($post['evaluasi_kontrol_tgl'])) {
            $post['evaluasi_kontrol_tgl'] = null;
        }
        if (empty($post['tanggal_ttd'])) {
            $post['tanggal_ttd'] = date('Y-m-d');
        }

        $post['flacc_wajah'] = (int)($post['flacc_wajah'] ?? 0);
        $post['flacc_kaki'] = (int)($post['flacc_kaki'] ?? 0);
        $post['flacc_aktivitas'] = (int)($post['flacc_aktivitas'] ?? 0);
        $post['flacc_menangis'] = (int)($post['flacc_menangis'] ?? 0);
        $post['flacc_consolability'] = (int)($post['flacc_consolability'] ?? 0);
        $post['flacc_total'] = $post['flacc_wajah'] + $post['flacc_kaki'] + $post['flacc_aktivitas'] + $post['flacc_menangis'] + $post['flacc_consolability'];

        if (!empty($id)) {
            $this->db('asesmen_medis_gigi_ugd')->where('id', $id)->save($post);
            $this->notify('success', 'Data Asesmen Medis Gigi UGD berhasil diperbarui.');
        } else {
            $this->db('asesmen_medis_gigi_ugd')->save($post);
            $this->notify('success', 'Data Asesmen Medis Gigi UGD berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'rmgd5manage']));
    }

    public function postRmgd5save()
    {
        return $this->anyRmgd5save();
    }

    public function anyRmgd5hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('asesmen_medis_gigi_ugd')->where('id', $id)->delete();
            $this->notify('success', 'Data Asesmen Medis Gigi UGD berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'rmgd5manage']));
    }

    public function getRmgd5hapus($id = null)
    {
        return $this->anyRmgd5hapus($id);
    }

    public function anyRmgd5cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        $data = $this->db('asesmen_medis_gigi_ugd')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_medis_gigi_ugd.kd_dokter')
            ->where('asesmen_medis_gigi_ugd.id', $id)
            ->select('asesmen_medis_gigi_ugd.*, dokter.nm_dokter')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split(substr($padded_rm, -6));

        $data['tgl_lahir_fmt'] = !empty($data['tanggal_lahir']) ? date('d-m-Y', strtotime($data['tanggal_lahir'])) : '-';
        $data['tgl_kunjungan_fmt'] = !empty($data['tanggal_kunjungan']) ? date('d / m / Y', strtotime($data['tanggal_kunjungan'])) : '.... / .... / 20....';
        $data['tgl_ttd_fmt'] = !empty($data['tanggal_ttd']) ? date('d / m / Y', strtotime($data['tanggal_ttd'])) : '.... / .... / 20....';
        $data['kontrol_tgl_fmt'] = !empty($data['evaluasi_kontrol_tgl']) ? date('d-m-Y', strtotime($data['evaluasi_kontrol_tgl'])) : '........................';

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'rm_digits' => $rm_digits,
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            $oldErr = error_reporting(0);
            @ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 6,
                'margin_bottom' => 6,
                'tempDir' => BASE_DIR . '/tmp'
            ]);
            $html = $this->draw('rmgd5_medis_gigi/cetak.html', $viewParams);
            $mpdf->WriteHTML($html);
            while (ob_get_level()) {
                ob_end_clean();
            }
            $mpdf->Output('RM_GD_05_Asesmen_Medis_Gigi_UGD_' . str_replace('/', '_', $data['no_rawat']) . '.pdf', 'I');
            exit();
        }

        echo $this->draw('rmgd5_medis_gigi/cetak.html', $viewParams);
        exit();
    }

    public function getRmgd5cetak($id = null)
    {
        return $this->anyRmgd5cetak($id);
    }

    public function anyRmgd6manage()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));

        $rows = $this->db('asesmen_medis_umum_ugd')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_medis_umum_ugd.kd_dokter')
            ->select('asesmen_medis_umum_ugd.*, dokter.nm_dokter')
            ->desc('asesmen_medis_umum_ugd.id')
            ->toArray();

        return $this->draw('rmgd6_medis_umum/manage.html', ['data' => $rows]);
    }

    public function getRmgd6manage()
    {
        return $this->anyRmgd6manage();
    }

    public function anyRmgd6form($id = null)
    {
        if (isset($_GET['ajax_patient'])) {
            $q = trim($_GET['no_rawat'] ?? '');
            if (is_numeric($q) && strlen($q) < 6) {
                $q = str_pad($q, 6, '0', STR_PAD_LEFT);
            }
            $result = ['success' => false];
            if (!empty($q)) {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $q)
                    ->orWhere('reg_periksa.no_rkm_medis', $q)
                    ->select('reg_periksa.no_rawat, reg_periksa.no_rkm_medis, reg_periksa.kd_dokter, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah')
                    ->desc('reg_periksa.tgl_registrasi')
                    ->desc('reg_periksa.jam_reg')
                    ->oneArray();
                if ($reg) {
                    $triase = $this->db('mlite_triase_igd')
                        ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
                        ->where('mlite_triase_igd.no_rawat', $reg['no_rawat'])
                        ->select('mlite_triase_igd.*, petugas.nama as nama_petugas')
                        ->desc('mlite_triase_igd.id_triase')
                        ->oneArray();
                    $pem = $this->db('pemeriksaan_ralan')
                        ->where('no_rawat', $reg['no_rawat'])
                        ->desc('tgl_perawatan')
                        ->desc('jam_rawat')
                        ->oneArray();
                    $lokFile = 'lokalis_' . str_replace('/', '', $reg['no_rawat']) . '.png';
                    $lokExists = is_file(UPLOADS . '/lokalis/' . $lokFile);

                    $result = [
                        'success' => true,
                        'no_rawat' => $reg['no_rawat'],
                        'no_rkm_medis' => $reg['no_rkm_medis'],
                        'nama_pasien' => $reg['nm_pasien'],
                        'jk' => $reg['jk'],
                        'tgl_lahir' => $reg['tgl_lahir'],
                        'gol_darah' => $reg['gol_darah'] ?? '',
                        'kd_dokter' => $reg['kd_dokter'] ?? '',
                        'triase' => $triase ?: null,
                        'pemeriksaan' => $pem ?: null,
                        'lokalis_url' => $lokExists ? (url(UPLOADS) . '/lokalis/' . $lokFile) : ''
                    ];
                }
            }
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        $id = $id ?? ($_GET['id'] ?? null);
        $no_rawat_param = trim($_GET['no_rawat'] ?? '');

        $data = [
            'id' => '',
            'no_rawat' => $no_rawat_param,
            'no_rkm_medis' => '',
            'nama_pasien' => '',
            'jenis_kelamin' => '',
            'tanggal_lahir' => '',
            'jenis_kasus' => 'Non Trauma',
            'sumber_data' => 'Auto Anamnesis',
            'kategori_pasien' => 'Pasien Dewasa (18-65 tahun)',
            'tanggal_kunjungan' => date('Y-m-d'),
            'pukul_kunjungan' => date('H:i'),
            'triase' => 'ATS 4',
            'jam_mulai_triase' => '',
            'jam_selesai_triase' => '',
            'petugas_triase' => '',
            'jn_paten' => 'Ya',
            'jn_obstruksi_parsial' => 'Tidak',
            'jn_stridor' => 'Tidak',
            'jn_snoring' => 'Tidak',
            'jn_gurgling' => 'Tidak',
            'jn_wheezing' => 'Tidak',
            'jn_obstruksi_total' => 'Tidak',
            'jn_trauma_jalan_napas' => 'Tidak',
            'jn_resiko_aspirasi' => 'Tidak',
            'jn_benda_asing' => 'Tidak',
            'jn_kesimpulan' => 'Aman',
            'napas_spontan' => 'Spontan',
            'napas_reguler' => 'Reguler',
            'napas_gerakan_dada' => 'Simetris',
            'napas_jejas_dada' => 'Tidak',
            'napas_jejas_kanan' => 'Tidak',
            'napas_jejas_kiri' => 'Tidak',
            'napas_tipe_normal' => 'Ya',
            'napas_tipe_takipnue' => 'Tidak',
            'napas_tipe_kussmaul' => 'Tidak',
            'napas_tipe_hiperventilasi' => 'Tidak',
            'napas_tipe_biot' => 'Tidak',
            'napas_tipe_cheyne_stokes' => 'Tidak',
            'napas_tipe_apneustic' => 'Tidak',
            'napas_kesimpulan' => 'Aman',
            'sirk_nadi_irama' => 'Reguler',
            'sirk_nadi_kekuatan' => 'Kuat',
            'sirk_kulit_normal' => 'Ya',
            'sirk_kulit_pucat' => 'Tidak',
            'sirk_kulit_jaundice' => 'Tidak',
            'sirk_kulit_sianosis' => 'Tidak',
            'sirk_kulit_berkeringat' => 'Tidak',
            'sirk_akral_suhu' => 'Hangat',
            'sirk_akral_lembab' => 'Kering',
            'sirk_crt' => '< 2 detik',
            'sirk_kesimpulan' => 'Aman',
            'dis_gcs_e' => '4',
            'dis_gcs_m' => '6',
            'dis_gcs_v' => '5',
            'dis_pupil' => 'Isokor',
            'dis_diameter_kanan' => '2',
            'dis_diameter_kiri' => '2',
            'dis_refleks_kanan' => '+',
            'dis_refleks_kiri' => '+',
            'eks_tidak_kelainan' => 'Ya',
            'eks_tidak_tampak_jelas' => 'Tidak',
            'eks_deformitas' => 'Tidak',
            'eks_contusio' => 'Tidak',
            'eks_abrasi' => 'Tidak',
            'eks_penetrasi' => 'Tidak',
            'eks_luka_bakar' => 'Tidak',
            'eks_laserasi' => 'Tidak',
            'eks_ekskoriasi' => 'Tidak',
            'keluhan_utama' => '',
            'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '',
            'riwayat_penyakit_keluarga' => '',
            'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_ket' => '',
            'riwayat_penggunaan_obat' => '',
            'rp_menular_hiv' => 'Tidak',
            'rp_menular_tbc' => 'Tidak',
            'rp_menular_hepatitis' => 'Tidak',
            'rp_menular_lainnya' => 'Tidak',
            'rp_menular_lainnya_ket' => '',
            'rp_jantung' => 'Tidak Ada',
            'rp_hipertensi' => 'Tidak Ada',
            'rp_diabetes' => 'Tidak Ada',
            'rp_haemophilia' => 'Tidak Ada',
            'rp_ginjal' => 'Tidak Ada',
            'rp_ginjal_ket' => '',
            'rp_sistemik_lain' => 'Tidak Ada',
            'rp_sistemik_lain_ket' => '',
            'rp_operasi' => 'Tidak Ada',
            'rp_operasi_ket' => '',
            'anak_ke' => '',
            'anak_dari_saudara' => '',
            'anak_cara_lahir' => '',
            'anak_cara_lahir_ket' => '',
            'anak_umur_lahir' => '',
            'anak_kelainan_bawaan' => '',
            'anak_kelainan_bawaan_ket' => '',
            'imun_bcg' => 'Tidak',
            'imun_campak' => 'Tidak',
            'imun_campak_kali' => '',
            'imun_polio' => 'Tidak',
            'imun_polio_kali' => '',
            'imun_hepatitis_b' => 'Tidak',
            'imun_hepatitis_b_kali' => '',
            'imun_dpt' => 'Tidak',
            'imun_dpt_kali' => '',
            'imun_lainnya' => 'Tidak',
            'imun_lainnya_ket' => '',
            'imun_lainnya_kali' => '',
            'tk_gigi_pertama' => '',
            'tk_duduk' => '',
            'tk_berjalan' => '',
            'tk_bicara' => '',
            'tk_membaca' => '',
            'geriatri_penglihatan' => 'Tidak',
            'geriatri_pendengaran' => 'Tidak',
            'geriatri_berkemih' => 'Tidak',
            'geriatri_daya_ingat' => 'Tidak',
            'geriatri_bicara' => 'Tidak',
            'geriatri_gigi_palsu' => 'Tidak',
            'geriatri_lainnya' => 'Tidak',
            'geriatri_lainnya_ket' => '',
            'keadaan_umum' => 'Baik',
            'kesadaran' => 'Compos Mentis',
            'td' => '',
            'nadi' => '',
            'rr' => '',
            'saturasi' => '',
            'suhu' => '',
            'gol_darah' => '',
            'bb' => '',
            'tb' => '',
            'sg_kepala' => 'DBN',
            'sg_mata' => 'DBN',
            'sg_tht' => 'DBN',
            'sg_leher' => 'DBN',
            'sg_thorax' => 'DBN',
            'sg_abdomen' => 'DBN',
            'sg_genitalia' => 'DBN',
            'sg_ekstremitas' => 'DBN',
            'status_lokalis' => '',
            'lokalis_img' => '',
            'nyeri_keluhan' => 'Tidak',
            'nyeri_lokasi' => '',
            'nyeri_durasi' => '',
            'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '',
            'nyeri_menjalar' => 'Tidak',
            'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '',
            'nyeri_hilang' => '',
            'nyeri_tl_edukasi' => 'Tidak',
            'nyeri_tl_terapi' => 'Tidak',
            'nyeri_tl_konsul' => 'Tidak',
            'nyeri_tl_konsul_ke' => '',
            'skala_nyeri' => '0',
            'flacc_wajah' => 0,
            'flacc_kaki' => 0,
            'flacc_aktivitas' => 0,
            'flacc_menangis' => 0,
            'flacc_consolability' => 0,
            'flacc_total' => 0,
            'penunjang_lab' => 'Tidak',
            'penunjang_rontgen' => 'Tidak',
            'penunjang_lain' => 'Tidak',
            'penunjang_lain_ket' => '',
            'diagnosis' => '',
            'diagnosis_banding' => '',
            'rencana_tindak_lanjut' => '',
            'evaluasi_kontrol' => 'Tidak',
            'evaluasi_kontrol_tgl' => '',
            'evaluasi_rujuk' => 'Tidak',
            'evaluasi_rujuk_rs' => '',
            'evaluasi_ranap' => 'Tidak',
            'evaluasi_ranap_ruangan' => '',
            'tanggal_ttd' => date('Y-m-d'),
            'pukul_ttd' => date('H:i'),
            'kd_dokter' => $this->core->getUserInfo('username', null, true)
        ];

        if ($id) {
            $row = $this->db('asesmen_medis_umum_ugd')->where('id', $id)->oneArray();
            if ($row) {
                $data = array_merge($data, $row);
            }
        } elseif (!empty($no_rawat_param)) {
            $existing = $this->db('asesmen_medis_umum_ugd')
                ->where('no_rawat', $no_rawat_param)
                ->desc('id')
                ->oneArray();
            if ($existing) {
                $data = array_merge($data, $existing);
            } else {
                $reg = $this->db('reg_periksa')
                    ->leftJoin('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where('reg_periksa.no_rawat', $no_rawat_param)
                    ->select('reg_periksa.*, pasien.nm_pasien, pasien.jk, pasien.tgl_lahir, pasien.gol_darah')
                    ->oneArray();
                if ($reg) {
                    $data['no_rkm_medis'] = $reg['no_rkm_medis'];
                    $data['nama_pasien'] = $reg['nm_pasien'];
                    $data['jenis_kelamin'] = $reg['jk'];
                    $data['tanggal_lahir'] = $reg['tgl_lahir'];
                    $data['gol_darah'] = $reg['gol_darah'] ?? '';
                    if (!empty($reg['kd_dokter'])) {
                        $data['kd_dokter'] = $reg['kd_dokter'];
                    }
                    if (!empty($reg['tgl_lahir'])) {
                        $age = (int)date('Y') - (int)date('Y', strtotime($reg['tgl_lahir']));
                        if ($age < 18) {
                            $data['kategori_pasien'] = 'Pasien Anak (0-<18 tahun)';
                        } elseif ($age > 65) {
                            $data['kategori_pasien'] = 'Pasien Geriatri (>65 tahun)';
                        } else {
                            $data['kategori_pasien'] = 'Pasien Dewasa (18-65 tahun)';
                        }
                    }
                }
                $triase = $this->db('mlite_triase_igd')
                    ->leftJoin('petugas', 'petugas.nip = mlite_triase_igd.petugas_id')
                    ->where('mlite_triase_igd.no_rawat', $no_rawat_param)
                    ->select('mlite_triase_igd.*, petugas.nama as nama_petugas')
                    ->desc('mlite_triase_igd.id_triase')
                    ->oneArray();
                if ($triase) {
                    if (!empty($triase['ats_skala'])) {
                        $data['triase'] = $triase['ats_skala'];
                    } elseif (!empty($triase['skala_triase'])) {
                        $data['triase'] = 'ATS ' . $triase['skala_triase'];
                    }
                    if (!empty($triase['tgl_triase'])) {
                        $data['jam_mulai_triase'] = date('H:i', strtotime($triase['tgl_triase']));
                    }
                    $data['jam_selesai_triase'] = $triase['jam_selesai_triase'] ?? $data['jam_mulai_triase'];
                    $data['petugas_triase'] = !empty($triase['nama_petugas']) ? $triase['nama_petugas'] : ($triase['petugas_id'] ?? '');
                    if (!empty($triase['keluhan_utama'])) {
                        $data['keluhan_utama'] = $triase['keluhan_utama'];
                    }
                    if (!empty($triase['tekanan_darah'])) {
                        $data['td'] = $triase['tekanan_darah'];
                    }
                    if (!empty($triase['nadi'])) {
                        $data['nadi'] = $triase['nadi'];
                    }
                    if (!empty($triase['respirasi'])) {
                        $data['rr'] = $triase['respirasi'];
                    }
                    if (!empty($triase['suhu'])) {
                        $data['suhu'] = $triase['suhu'];
                    }
                    if (!empty($triase['spo2'])) {
                        $data['saturasi'] = $triase['spo2'];
                    }
                    if (!empty($triase['gcs_e'])) {
                        $data['dis_gcs_e'] = $triase['gcs_e'];
                    }
                    if (!empty($triase['gcs_m'])) {
                        $data['dis_gcs_m'] = $triase['gcs_m'];
                    }
                    if (!empty($triase['gcs_v'])) {
                        $data['dis_gcs_v'] = $triase['gcs_v'];
                    }
                }
                $pem = $this->db('pemeriksaan_ralan')
                    ->where('no_rawat', $no_rawat_param)
                    ->desc('tgl_perawatan')
                    ->desc('jam_rawat')
                    ->oneArray();
                if ($pem) {
                    if (empty($data['keluhan_utama']) && !empty($pem['keluhan'])) {
                        $data['keluhan_utama'] = $pem['keluhan'];
                    }
                    if (empty($data['td']) && !empty($pem['tensi'])) {
                        $data['td'] = $pem['tensi'];
                    }
                    if (empty($data['nadi']) && !empty($pem['nadi'])) {
                        $data['nadi'] = $pem['nadi'];
                    }
                    if (empty($data['rr']) && !empty($pem['respirasi'])) {
                        $data['rr'] = $pem['respirasi'];
                    }
                    if (empty($data['suhu']) && !empty($pem['suhu_tubuh'])) {
                        $data['suhu'] = $pem['suhu_tubuh'];
                    }
                    if (empty($data['saturasi']) && !empty($pem['spo2'])) {
                        $data['saturasi'] = $pem['spo2'];
                    }
                    if (!empty($pem['berat'])) {
                        $data['bb'] = $pem['berat'];
                    }
                    if (!empty($pem['tinggi'])) {
                        $data['tb'] = $pem['tinggi'];
                    }
                    if (!empty($pem['kesadaran'])) {
                        $data['kesadaran'] = $pem['kesadaran'];
                    }
                    if (!empty($pem['alergi'])) {
                        $data['riwayat_alergi'] = ($pem['alergi'] !== '-' && strtolower($pem['alergi']) !== 'tidak ada') ? 'Ya' : 'Tidak';
                        $data['riwayat_alergi_ket'] = $pem['alergi'];
                    }
                    if (!empty($pem['penilaian'])) {
                        $data['diagnosis'] = $pem['penilaian'];
                    }
                    if (!empty($pem['rtl'])) {
                        $data['rencana_tindak_lanjut'] = $pem['rtl'];
                    }
                }
                $pmi = $this->db('penilaian_medis_igd')
                    ->where('no_rawat', $no_rawat_param)
                    ->oneArray();
                if ($pmi) {
                    if (empty($data['keluhan_utama']) && !empty($pmi['keluhan_utama'])) {
                        $data['keluhan_utama'] = $pmi['keluhan_utama'];
                    }
                    if (!empty($pmi['rps'])) {
                        $data['riwayat_penyakit_sekarang'] = $pmi['rps'];
                    }
                    if (!empty($pmi['rpd'])) {
                        $data['riwayat_penyakit_dahulu'] = $pmi['rpd'];
                    }
                    if (!empty($pmi['rpk'])) {
                        $data['riwayat_penyakit_keluarga'] = $pmi['rpk'];
                    }
                    if (!empty($pmi['rpo'])) {
                        $data['riwayat_penggunaan_obat'] = $pmi['rpo'];
                    }
                    if (!empty($pmi['ket_lokalis'])) {
                        $data['status_lokalis'] = $pmi['ket_lokalis'];
                    }
                    if (!empty($pmi['diagnosis'])) {
                        $data['diagnosis'] = $pmi['diagnosis'];
                    }
                    if (!empty($pmi['tata'])) {
                        $data['rencana_tindak_lanjut'] = $pmi['tata'];
                    }
                }
            }
        }

        $lokalis_existing_url = '';
        if (!empty($data['no_rawat'])) {
            $lokFile = 'lokalis_' . str_replace('/', '', $data['no_rawat']) . '.png';
            if (is_file(UPLOADS . '/lokalis/' . $lokFile)) {
                $lokalis_existing_url = url(UPLOADS) . '/lokalis/' . $lokFile;
            }
        }

        $dokter = $this->db('dokter')->where('status', '1')->toArray();

        return $this->draw('rmgd6_medis_umum/form.html', [
            'data' => $data,
            'dokter' => $dokter,
            'lokalis_existing_url' => $lokalis_existing_url
        ]);
    }

    public function getRmgd6form($id = null)
    {
        return $this->anyRmgd6form($id);
    }

    public function anyRmgd6save()
    {
        $post = $_POST;
        $id = $post['id'] ?? '';
        unset($post['id'], $post['save']);

        $checkboxes = [
            'jn_paten', 'jn_obstruksi_parsial', 'jn_stridor', 'jn_snoring', 'jn_gurgling', 'jn_wheezing',
            'jn_obstruksi_total', 'jn_trauma_jalan_napas', 'jn_resiko_aspirasi', 'jn_benda_asing',
            'napas_jejas_dada', 'napas_jejas_kanan', 'napas_jejas_kiri',
            'napas_tipe_normal', 'napas_tipe_takipnue', 'napas_tipe_kussmaul', 'napas_tipe_hiperventilasi',
            'napas_tipe_biot', 'napas_tipe_cheyne_stokes', 'napas_tipe_apneustic',
            'sirk_kulit_normal', 'sirk_kulit_pucat', 'sirk_kulit_jaundice', 'sirk_kulit_sianosis', 'sirk_kulit_berkeringat',
            'eks_tidak_kelainan', 'eks_tidak_tampak_jelas', 'eks_deformitas', 'eks_contusio', 'eks_abrasi',
            'eks_penetrasi', 'eks_luka_bakar', 'eks_laserasi', 'eks_ekskoriasi',
            'rp_menular_hiv', 'rp_menular_tbc', 'rp_menular_hepatitis', 'rp_menular_lainnya',
            'imun_bcg', 'imun_campak', 'imun_polio', 'imun_hepatitis_b', 'imun_dpt', 'imun_lainnya',
            'geriatri_penglihatan', 'geriatri_pendengaran', 'geriatri_berkemih', 'geriatri_daya_ingat',
            'geriatri_bicara', 'geriatri_gigi_palsu', 'geriatri_lainnya',
            'nyeri_tl_edukasi', 'nyeri_tl_terapi', 'nyeri_tl_konsul',
            'penunjang_lab', 'penunjang_rontgen', 'penunjang_lain',
            'evaluasi_kontrol', 'evaluasi_rujuk', 'evaluasi_ranap'
        ];

        foreach ($checkboxes as $cb) {
            if (!isset($post[$cb])) {
                $post[$cb] = 'Tidak';
            }
        }

        if (empty($post['tanggal_kunjungan'])) {
            $post['tanggal_kunjungan'] = date('Y-m-d');
        }
        if (empty($post['tanggal_lahir'])) {
            $post['tanggal_lahir'] = null;
        }
        if (empty($post['evaluasi_kontrol_tgl'])) {
            $post['evaluasi_kontrol_tgl'] = null;
        }
        if (empty($post['tanggal_ttd'])) {
            $post['tanggal_ttd'] = date('Y-m-d');
        }

        // Simpan juga ke UPLOADS/lokalis/lokalis_{no_rawat}.png jika ada lokalis_img
        if (!empty($post['lokalis_img']) && strpos($post['lokalis_img'], 'data:image/png;base64,') === 0 && !empty($post['no_rawat'])) {
            $rawImg = base64_decode(str_replace('data:image/png;base64,', '', $post['lokalis_img']));
            if (!is_dir(UPLOADS . '/lokalis/')) {
                @mkdir(UPLOADS . '/lokalis/', 0777, true);
            }
            $lokFilename = 'lokalis_' . str_replace('/', '', $post['no_rawat']) . '.png';
            @file_put_contents(UPLOADS . '/lokalis/' . $lokFilename, $rawImg);
        }

        $post['flacc_wajah'] = (int)($post['flacc_wajah'] ?? 0);
        $post['flacc_kaki'] = (int)($post['flacc_kaki'] ?? 0);
        $post['flacc_aktivitas'] = (int)($post['flacc_aktivitas'] ?? 0);
        $post['flacc_menangis'] = (int)($post['flacc_menangis'] ?? 0);
        $post['flacc_consolability'] = (int)($post['flacc_consolability'] ?? 0);
        $post['flacc_total'] = $post['flacc_wajah'] + $post['flacc_kaki'] + $post['flacc_aktivitas'] + $post['flacc_menangis'] + $post['flacc_consolability'];

        if (!empty($id)) {
            $this->db('asesmen_medis_umum_ugd')->where('id', $id)->save($post);
            $this->notify('success', 'Data Asesmen Medis Umum UGD berhasil diperbarui.');
        } else {
            $this->db('asesmen_medis_umum_ugd')->save($post);
            $this->notify('success', 'Data Asesmen Medis Umum UGD berhasil disimpan.');
        }

        redirect(url([ADMIN, 'form_rme', 'rmgd6manage']));
    }

    public function postRmgd6save()
    {
        return $this->anyRmgd6save();
    }

    public function anyRmgd6hapus($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        if ($id) {
            $this->db('asesmen_medis_umum_ugd')->where('id', $id)->delete();
            $this->notify('success', 'Data Asesmen Medis Umum UGD berhasil dihapus.');
        }
        redirect(url([ADMIN, 'form_rme', 'rmgd6manage']));
    }

    public function getRmgd6hapus($id = null)
    {
        return $this->anyRmgd6hapus($id);
    }

    public function anyRmgd6cetak($id = null)
    {
        $id = $id ?? ($_GET['id'] ?? null);
        $data = $this->db('asesmen_medis_umum_ugd')
            ->leftJoin('dokter', 'dokter.kd_dokter = asesmen_medis_umum_ugd.kd_dokter')
            ->where('asesmen_medis_umum_ugd.id', $id)
            ->select('asesmen_medis_umum_ugd.*, dokter.nm_dokter')
            ->oneArray();

        if (!$data) {
            echo "Data tidak ditemukan.";
            exit();
        }

        $clean_rm = preg_replace('/[^0-9]/', '', $data['no_rkm_medis'] ?? '');
        $padded_rm = str_pad($clean_rm, 6, '0', STR_PAD_LEFT);
        $rm_digits = str_split(substr($padded_rm, -6));

        $data['tgl_lahir_fmt'] = !empty($data['tanggal_lahir']) ? date('d-m-Y', strtotime($data['tanggal_lahir'])) : '-';
        $data['tgl_kunjungan_fmt'] = !empty($data['tanggal_kunjungan']) ? date('d / m / Y', strtotime($data['tanggal_kunjungan'])) : '.... / .... / 20....';
        $data['tgl_ttd_fmt'] = !empty($data['tanggal_ttd']) ? date('d / m / Y', strtotime($data['tanggal_ttd'])) : '.... / .... / 20....';
        $data['kontrol_tgl_fmt'] = !empty($data['evaluasi_kontrol_tgl']) ? date('d-m-Y', strtotime($data['evaluasi_kontrol_tgl'])) : '........................';

        $is_pdf = isset($_GET['pdf']) && $_GET['pdf'] == 1;
        $kop_file = BASE_DIR . '/assets/img/KOP-SURAT.png';
        $kop_img = is_file($kop_file) ? ($is_pdf ? $kop_file : url('assets/img/KOP-SURAT.png')) : '';

        // Gambar Status Lokalis (dari kolom lokalis_img, atau file uploads/lokalis/lokalis_{no_rawat}.png, atau default anatomi)
        $lokalis_src = '';
        if (!empty($data['lokalis_img'])) {
            $lokalis_src = $data['lokalis_img'];
        } else {
            $lokFile = 'lokalis_' . str_replace('/', '', $data['no_rawat']) . '.png';
            if (is_file(UPLOADS . '/lokalis/' . $lokFile)) {
                $lokalis_src = $is_pdf ? (UPLOADS . '/lokalis/' . $lokFile) : (url(UPLOADS) . '/lokalis/' . $lokFile);
            } else {
                $defLok = BASE_DIR . '/assets/img/bm3_anatomi_tubuh.png';
                if (is_file($defLok)) {
                    $lokalis_src = $is_pdf ? $defLok : url('assets/img/bm3_anatomi_tubuh.png');
                }
            }
        }

        $viewParams = [
            'data' => $data,
            'kop_img' => $kop_img,
            'lokalis_src' => $lokalis_src,
            'rm_digits' => $rm_digits,
            'is_mpdf' => $is_pdf
        ];

        if ($is_pdf) {
            $oldErr = error_reporting(0);
            @ini_set('display_errors', '0');
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 6,
                'margin_bottom' => 6,
                'tempDir' => BASE_DIR . '/tmp'
            ]);
            $html = $this->draw('rmgd6_medis_umum/cetak.html', $viewParams);
            $mpdf->WriteHTML($html);
            while (ob_get_level()) {
                ob_end_clean();
            }
            $mpdf->Output('RM_GD_06_Asesmen_Medis_Umum_UGD_' . str_replace('/', '_', $data['no_rawat']) . '.pdf', 'I');
            exit();
        }

        echo $this->draw('rmgd6_medis_umum/cetak.html', $viewParams);
        exit();
    }

    public function getRmgd6cetak($id = null)
    {
        return $this->anyRmgd6cetak($id);
    }
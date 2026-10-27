<?php

namespace Plugins\Sirs;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Top 10 Penyakit' => 'top10penyakit',
        ];
    }

    public function getManage()
    {
        $sub_modules = [
            ['name' => 'Top 10 Besar Penyakit', 'url' => url([ADMIN, 'sirs', 'top10penyakit']), 'icon' => 'bar-chart', 'desc' => 'Laporan 10 besar penyakit (diagnosa utama) dengan ekspor Excel format SIRS'],
        ];

        return $this->draw('manage.html', ['sub_modules' => htmlspecialchars_array($sub_modules)]);
    }

    public function anyTop10Penyakit()
    {
        $this->_addHeaderFiles();

        $tgl_awal = isset_or($_POST['tgl_awal'], date('Y-m-01'));
        $tgl_akhir = isset_or($_POST['tgl_akhir'], date('Y-m-d'));
        $tipe = isset_or($_POST['tipe'], 'semua');
        if (!in_array($tipe, ['semua', 'Ralan', 'Ranap'], true)) {
            $tipe = 'semua';
        }

        $total = $this->_queryTotal($tgl_awal, $tgl_akhir, $tipe);
        $laporan = $this->_hitungPersen($this->_queryTop10($tgl_awal, $tgl_akhir, $tipe), $total);

        if (isset($_POST['export']) && $_POST['export'] == 'excel') {
            $jenis = ['semua' => 'Semua', 'Ralan' => 'Rawat Jalan', 'Ranap' => 'Rawat Inap'];

            return $this->_exportExcel($laporan, [
                'title' => 'LAPORAN 10 BESAR PENYAKIT (TOP 10)',
                'jenis' => 'Jenis Pelayanan : ' . $jenis[$tipe],
                'periode' => 'Periode : ' . date('d-m-Y', strtotime($tgl_awal)) . ' s.d ' . date('d-m-Y', strtotime($tgl_akhir)),
                'total' => $total,
                'filename' => 'Top_10_Penyakit_SIRS_' . $tipe . '_' . $tgl_awal . '_' . $tgl_akhir,
            ]);
        }

        return $this->draw('top10penyakit.html', [
            'laporan' => $laporan,
            'total' => $total,
            'tgl_awal' => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'tipe' => $tipe,
        ]);
    }

    private function _queryTop10($tgl_awal, $tgl_akhir, $tipe)
    {
        $query = $this->db('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select([
                'diagnosa_pasien.kd_penyakit',
                'penyakit.nm_penyakit',
                'count(*) as jumlah'
            ])
            ->where('diagnosa_pasien.prioritas', '=', 1)
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir);

        if ($tipe !== 'semua') {
            $query->where('reg_periksa.status_lanjut', '=', $tipe);
        }

        return $query
            ->group('diagnosa_pasien.kd_penyakit')
            ->desc('jumlah')
            ->limit(10)
            ->toArray();
    }

    private function _queryTotal($tgl_awal, $tgl_akhir, $tipe)
    {
        $query = $this->db('diagnosa_pasien')
            ->join('reg_periksa', 'reg_periksa.no_rawat = diagnosa_pasien.no_rawat')
            ->select('count(*) as jumlah')
            ->where('diagnosa_pasien.prioritas', '=', 1)
            ->where('reg_periksa.tgl_registrasi', '>=', $tgl_awal)
            ->where('reg_periksa.tgl_registrasi', '<=', $tgl_akhir);

        if ($tipe !== 'semua') {
            $query->where('reg_periksa.status_lanjut', '=', $tipe);
        }

        $result = $query->toArray();

        return $result ? (int) $result[0]['jumlah'] : 0;
    }

    private function _hitungPersen(array $rows, $total)
    {
        foreach ($rows as $key => $row) {
            $jumlah = (int) isset_or($row['jumlah'], 0);
            $persen = ($total > 0) ? $jumlah * 100 / $total : 0;
            $rows[$key]['persen'] = round($persen, 2);
            $rows[$key]['persen_tampil'] = number_format($persen, 2, '.', '') . '%';
        }

        return $rows;
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
    }

    private function _exportExcel(array $rows, array $options)
    {
        $settings = $this->settings('settings');
        $identitas = trim(isset_or($settings['nama_instansi'], '') . ' - ' . isset_or($settings['alamat'], ''));

        $sheetRows = [];
        $boldRows = [];

        $sheetRows[] = [$options['title']];
        $boldRows[] = 0;
        $sheetRows[] = ['Laporan SIRS - ' . $identitas];
        $sheetRows[] = [isset_or($settings['kota'], '')];
        $sheetRows[] = [$options['jenis']];
        $sheetRows[] = [$options['periode']];
        $sheetRows[] = [];
        $sheetRows[] = ['No', 'Kode ICD-10', 'Nama Penyakit / Diagnosa', 'Jumlah Kasus', 'Persentase (%)'];
        $boldRows[] = count($sheetRows) - 1;

        $no = 1;
        foreach ($rows as $row) {
            $sheetRows[] = [
                $no++,
                isset_or($row['kd_penyakit'], ''),
                isset_or($row['nm_penyakit'], ''),
                (int) isset_or($row['jumlah'], 0),
                (float) isset_or($row['persen'], 0),
            ];
        }

        if ($no === 1) {
            $sheetRows[] = ['', '', 'Tidak ada data pada periode tersebut', '', ''];
        } else {
            $sheetRows[] = ['', '', 'Total seluruh diagnosa utama pada periode ini', (int) isset_or($options['total'], 0), 100];
            $boldRows[] = count($sheetRows) - 1;
        }

        $content = $this->_xlsx($sheetRows, $boldRows, [8, 16, 50, 16, 16], 'Top 10 Penyakit', [4]);

        $filename = $options['filename'] . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    private function _xlsx(array $rows, array $boldRows = [], array $colWidths = [], $sheetName = 'Laporan', array $percentCols = [])
    {
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if (!empty($colWidths)) {
            $sheet .= '<cols>';
            foreach (array_values($colWidths) as $i => $width) {
                $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $width . '" customWidth="1"/>';
            }
            $sheet .= '</cols>';
        }

        $sheet .= '<sheetData>';
        foreach (array_values($rows) as $r => $row) {
            $excelRow = $r + 1;
            $bold = in_array($r, $boldRows, true);
            $sheet .= '<row r="' . $excelRow . '">';
            foreach (array_values($row) as $c => $cell) {
                if ($cell === null || $cell === '') {
                    continue;
                }
                $ref = $this->_xlsxColumn($c + 1) . $excelRow;
                if (in_array($c, $percentCols, true) && (is_int($cell) || is_float($cell))) {
                    $sheet .= '<c r="' . $ref . '" s="' . ($bold ? 3 : 2) . '"><v>'
                        . sprintf('%.6F', ((float) $cell) / 100) . '</v></c>';
                } elseif (is_int($cell) || is_float($cell)) {
                    $value = is_int($cell) ? (string) $cell : sprintf('%.6F', (float) $cell);
                    $sheet .= '<c r="' . $ref . '"' . ($bold ? ' s="1"' : '') . '><v>' . $value . '</v></c>';
                } else {
                    $sheet .= '<c r="' . $ref . '"' . ($bold ? ' s="1"' : '') . ' t="inlineStr"><is><t xml:space="preserve">' . $this->_xml((string) $cell) . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $styles .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $styles .= '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>';
        $styles .= '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>';
        $styles .= '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>';
        $styles .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $styles .= '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="10" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="10" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            . '</cellXfs>';
        $styles .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
        $styles .= '</styleSheet>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $workbook .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $workbook .= '<sheets><sheet name="' . $this->_xml(substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets>';
        $workbook .= '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $workbookRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $workbookRels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $workbookRels .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $workbookRels .= '</Relationships>';

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $rootRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rootRels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rootRels .= '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $contentTypes .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $contentTypes .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $contentTypes .= '<Default Extension="xml" ContentType="application/xml"/>';
        $contentTypes .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $contentTypes .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $contentTypes .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $contentTypes .= '</Types>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive;
        if ($zip->open($tmpFile, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpFile);
            return '';
        }
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rootRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $content;
    }

    private function _xlsxColumn($index)
    {
        $letter = '';
        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function _xml($value)
    {
        $value = preg_replace('#[\x00-\x08\x0B\x0C\x0E-\x1F]#', '', $value);

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

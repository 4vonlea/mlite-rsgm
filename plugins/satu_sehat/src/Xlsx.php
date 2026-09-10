<?php

namespace SatuSehat\Src;

class Xlsx
{
    private $sheets = [];

    public function addSheet($name, $rows, $widths = [], $title = null)
    {
        $this->sheets[] = [
            'name' => $name,
            'rows' => $rows,
            'widths' => $widths,
            'title' => $title,
        ];
    }

    public function save($path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $zip = $this->buildZip();
        if ($zip === null) {
            return false;
        }
        $ok = copy($zip, $path);
        @unlink($zip);
        return $ok;
    }

    public function download($filename)
    {
        $zip = $this->buildZip();
        if ($zip === null) {
            return;
        }
        if (PHP_SAPI !== 'cli' && function_exists('header')) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($zip));
            header('Cache-Control: max-age=0');
            header('Pragma: public');
        }
        readfile($zip);
        @unlink($zip);
    }

    private function buildZip()
    {
        if (count($this->sheets) === 0) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmp === false) {
            return null;
        }
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            return null;
        }

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypes());
        $zip->addFromString('_rels/.rels', $this->buildRels());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->buildStyles());
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->buildWorksheet($sheet));
        }
        $zip->close();
        return $tmp;
    }

    private function buildContentTypes()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        for ($i = 0; $i < count($this->sheets); $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '</Types>';
        return $xml;
    }

    private function buildRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function buildWorkbook()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        foreach ($this->sheets as $i => $sheet) {
            $name = $this->esc($sheet['name']);
            $xml .= '<sheet name="' . $name . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        $xml .= '</sheets>';
        $xml .= '</workbook>';
        return $xml;
    }

    private function buildWorkbookRels()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 0; $i < count($this->sheets); $i++) {
            $xml .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $xml .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private function buildStyles()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<numFmts count="3">'
            . '<numFmt numFmtId="164" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="165" formatCode="#,##0"/>'
            . '<numFmt numFmtId="166" formatCode="0.00"/>'
            . '</numFmts>';
        $xml .= '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>';
        $xml .= '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2E6E3F"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>';
        $xml .= '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FF9CA3AF"/></left><right style="thin"><color rgb="FF9CA3AF"/></right><top style="thin"><color rgb="FF9CA3AF"/></top><bottom style="thin"><color rgb="FF9CA3AF"/></bottom><diagonal/></border>'
            . '</borders>';
        $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $xml .= '<cellXfs count="11">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>';
        $xml .= '</styleSheet>';
        return $xml;
    }

    private function buildWorksheet($sheet)
    {
        $rows = $sheet['rows'];
        $widths = $sheet['widths'];
        $title = $sheet['title'];
        $maxCols = max(count($widths), 1);
        $rowOffset = 0;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        $cols = '<cols>';
        if (!empty($widths)) {
            $i = 1;
            foreach ($widths as $w) {
                $cols .= '<col min="' . $i . '" max="' . $i . '" width="' . $w . '" customWidth="1"/>';
                $i++;
            }
        } else {
            $cols .= '<col min="1" max="' . $maxCols . '" width="14" customWidth="1"/>';
        }
        $cols .= '</cols>';
        $xml .= $cols;

        $sheetData = '';
        $mergeRanges = [];
        if ($title !== null && $title !== '') {
            $rowOffset = 1;
            $mergeRanges[] = 'A1:' . $this->colLetter($maxCols) . '1';
            $sheetData .= '<row r="1" ht="24" customHeight="1">';
            $sheetData .= '<c r="A1" s="9" t="inlineStr"><is><t xml:space="preserve">' . $this->esc($title) . '</t></is></c>';
            $sheetData .= '</row>';
        }

        foreach ($rows as $r => $row) {
            $rn = $r + 1 + $rowOffset;
            $rowXml = '<row r="' . $rn . '">';
            for ($c = 1; $c <= $maxCols; $c++) {
                $cell = isset($row[$c - 1]) ? $row[$c - 1] : '';
                if ($cell === null) {
                    $cell = '';
                }
                if (is_array($cell) && !empty($cell['m']) && (int)$cell['m'] > 1) {
                    $mergeRanges[] = $this->colLetter($c) . $rn . ':' . $this->colLetter($c + (int)$cell['m'] - 1) . $rn;
                }
                $rowXml .= $this->buildCell($cell, $this->colLetter($c), $rn);
            }
            $rowXml .= '</row>';
            $sheetData .= $rowXml;
        }

        $xml .= '<sheetData>' . $sheetData . '</sheetData>';
        if (!empty($mergeRanges)) {
            $xml .= '<mergeCells count="' . count($mergeRanges) . '">';
            foreach ($mergeRanges as $mr) {
                $xml .= '<mergeCell ref="' . $mr . '"/>';
            }
            $xml .= '</mergeCells>';
        }
        $xml .= '</worksheet>';
        return $xml;
    }

    private function buildCell($cell, $colRef, $rowIndex)
    {
        if (is_array($cell)) {
            $val = isset($cell['v']) ? $cell['v'] : '';
            $style = isset($cell['s']) ? $cell['s'] : 0;
        } else {
            $val = $cell;
            $style = 0;
        }
        $ref = $colRef . $rowIndex;
        if ($val === null || $val === '') {
            return ($style > 0) ? '<c r="' . $ref . '" s="' . $style . '"/>' : '';
        }
        if (is_int($val) || is_float($val)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $val . '</v></c>';
        }
        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->esc($val) . '</t></is></c>';
    }

    private function colLetter($index)
    {
        $r = '';
        $n = $index;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $r = chr(65 + $m) . $r;
            $n = intval(($n - 1) / 26);
        }
        return $r;
    }

    private function esc($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
<?php

namespace Plugins\Laporan\Src;

class Word
{
    private $filename;

    private $judul = [];
    private $ringkasan = '';
    private $catatan = '';
    private $daftarIsi = [];

    private $blok = [];

    private $relasiGambar = [];
    private $nomorGambar = 0;

    private $maxWidthEmu = 0;
    private $anchorSeq = 0;
    private $dirSampel = null;
    private $batasGambar = 0;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->maxWidthEmu = 5486400; // 6 inch, aman di dalam A4 dengan margin 2 cm
    }

    public function setJudul($institusi, $judul, $subjudul = '')
    {
        $this->judul = [
            'institusi' => (string)$institusi,
            'judul' => (string)$judul,
            'subjudul' => (string)$subjudul
        ];
    }

    public function setRingkasan($teks)
    {
        $this->ringkasan = (string)$teks;
    }

    public function setCatatan($teks)
    {
        $this->catatan = (string)$teks;
    }

    public function setBatasGambar($maks)
    {
        $this->batasGambar = (int)$maks;
    }

    public function tambahDaftarIsi($rows)
    {
        $this->daftarIsi = is_array($rows) ? $rows : [];
    }

    public function tambahPasien($data)
    {
        $anchor = 'pasien_' . $this->anchorFor($data['no_rawat']);
        $this->anchorSeq++;

        $xml = '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="2E6E3F"/></w:pBdr>'
            . '<w:spacing w:before="120" w:after="60"/></w:pPr>';
        $xml .= '<w:bookmarkStart w:id="' . $this->anchorSeq . '" w:name="' . $this->esc($anchor) . '"/>';
        $xml .= '<w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="1F4E2C"/></w:rPr><w:t xml:space="preserve">'
            . $this->esc($data['nama']) . '</w:t></w:r>';
        $xml .= '<w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">  |  RM: '
            . $this->esc($data['rm']) . '</w:t></w:r>';
        $xml .= '<w:bookmarkEnd w:id="' . $this->anchorSeq . '"/></w:p>';

        $meta = [];
        if (!empty($data['no_rawat'])) {
            $meta[] = 'No. Rawat: ' . $data['no_rawat'];
        }
        if (!empty($data['perawatan'])) {
            $meta[] = $data['perawatan'];
        }
        if (!empty($data['tgl'])) {
            $meta[] = $data['tgl'];
        }

        if (!empty($meta)) {
            $xml .= '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr><w:r><w:rPr><w:sz w:val="17"/><w:color w:val="555555"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->esc(implode(' | ', $meta)) . '</w:t></w:r></w:p>';
        }

        $this->blok[] = $xml;
    }

    public function tambahGambar($gambar)
    {
        $sumber = $this->_sumberGambar($gambar);
        if ($sumber === null) {
            return;
        }

        // Setelah batas tercapai, jangan ambil gambar lagi: cukup tulis blok kosong
        // supaya instance yang terlewat tetap terlihat dan bisa dicari manual.
        if ($this->batasGambar > 0 && $this->nomorGambar >= $this->batasGambar) {
            $this->blok[] = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="40" w:after="40"/></w:pPr>'
                . '<w:r><w:rPr><w:i/><w:color w:val="888888"/><w:sz w:val="16"/></w:rPr>'
                . '<w:t xml:space="preserve">[gambar ke-' . ($this->nomorGambar + 1)
                . ' dan seterusnya tidak disertakan karena batas ' . $this->batasGambar
                . ' gambar tercapai - ambil manual bila diperlukan]</w:t></w:r></w:p>';
            return;
        }

        $this->nomorGambar++;
        $ext = !empty($gambar['ext']) ? $gambar['ext'] : 'jpeg';
        $nama = 'image' . $this->nomorGambar . '.' . $ext;
        $rid = 'rIdGambar' . $this->nomorGambar;

        $this->relasiGambar[] = [
            'id' => $rid,
            'target' => 'media/' . $nama,
            'file' => $sumber,
            'mime' => !empty($gambar['mime']) ? $gambar['mime'] : 'image/jpeg'
        ];

        $w = !empty($gambar['w']) ? (int)$gambar['w'] : 0;
        $h = !empty($gambar['h']) ? (int)$gambar['h'] : 0;

        $cx = $this->maxWidthEmu;
        $cy = $this->maxWidthEmu;

        if ($w > 0 && $h > 0) {
            $cx = $w * 9525;
            $cy = $h * 9525;

            if ($cx > $this->maxWidthEmu) {
                $cy = (int)round($cy * ($this->maxWidthEmu / $cx));
                $cx = $this->maxWidthEmu;
            }
        } elseif ($h > 0) {
            $cy = $h * 9525;
        }

        if ($cx < 9525) {
            $cx = 9525;
        }
        if ($cy < 9525) {
            $cy = 9525;
        }

        $did = 1000 + $this->nomorGambar;

        $xml = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="80" w:after="20"/><w:keepNext/></w:pPr><w:r><w:drawing>';
        $xml .= '<wp:inline distT="0" distB="0" distL="0" distR="0">';
        $xml .= '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>';
        $xml .= '<wp:docPr id="' . $did . '" name="Rontgen ' . $this->nomorGambar . '" descr="Gambar rontgen"/>';
        $xml .= '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>';
        $xml .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
        $xml .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $xml .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $xml .= '<pic:nvPicPr><pic:cNvPr id="' . $did . '" name="' . $this->esc($nama) . '"/><pic:cNvPicPr/></pic:nvPicPr>';
        $xml .= '<pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
        $xml .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>';
        $xml .= '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
        $xml .= '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';

        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/><w:pBdr><w:bottom w:val="single" w:sz="4" w:space="4" w:color="BFBFBF"/></w:pBdr></w:pPr>';
        $xml .= '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="666666"/></w:rPr>'
            . '<w:t xml:space="preserve">Gambar ' . $this->nomorGambar . '</w:t></w:r></w:p>';

        $this->blok[] = $xml;
    }

    public function tambahGambarKosong($info)
    {
        $baris = [];
        $baris[] = 'GAMBAR TIDAK TERSEDIA - cari manual';
        $baris[] = 'Instance ID: ' . $info['id'];
        if (!empty($info['sop']) && $info['sop'] !== '-') {
            $baris[] = 'SOP UID: ' . $info['sop'];
        }
        if (!empty($info['path']) && $info['path'] !== '-') {
            $baris[] = 'Path: ' . $info['path'];
        }

        $xml = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="80" w:after="20"/><w:keepNext/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/><w:color w:val="B00000"/><w:sz w:val="18"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($baris[0]) . '</w:t></w:r></w:p>';

        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/>'
            . '<w:pBdr><w:bottom w:val="dashed" w:sz="4" w:space="4" w:color="B00000"/></w:pBdr></w:pPr>';
        $xml .= '<w:r><w:rPr><w:color w:val="B00000"/><w:sz w:val="15"/><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc(implode("\n", array_slice($baris, 1))) . '</w:t></w:r></w:p>';

        $this->blok[] = $xml;
    }

    private function _sumberGambar($gambar)
    {
        if (!empty($gambar['file'])) {
            $f = (string)$gambar['file'];
            if (is_file($f) && is_readable($f) && filesize($f) > 0) {
                return $f;
            }
            return null;
        }

        if (!empty($gambar['binary']) && strlen($gambar['binary']) > 0) {
            $dir = $this->_dirSampel();
            if ($dir === false) {
                return null;
            }
            $ext = !empty($gambar['ext']) ? $gambar['ext'] : 'jpeg';
            $path = $dir . '/' . uniqid('g', true) . '.' . $ext;
            if (@file_put_contents($path, $gambar['binary']) !== false) {
                return $path;
            }
            return null;
        }

        return null;
    }

    private function _dirSampel()
    {
        if ($this->dirSampel === null) {
            $base = sys_get_temp_dir();
            $dir = @tempnam($base, 'wimg_');
            if ($dir === false) {
                $this->dirSampel = false;
                return false;
            }
            @unlink($dir);
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                $this->dirSampel = false;
                return false;
            }
            $this->dirSampel = $dir;
        }

        return $this->dirSampel !== false ? $this->dirSampel : false;
    }

    private function anchorFor($noRawat)
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', (string)$noRawat);
        $slug = trim((string)$slug, '_');

        $anchor = 'pasien_' . $slug;

        // Word membatasi nama bookmark 40 karakter, tambah hash agar tetap unik
        if (strlen($anchor) > 40) {
            $anchor = 'pasien_' . substr($slug, 0, 24) . '_' . substr(md5($slug), 0, 7);
        }

        return $anchor;
    }

    private function esc($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function buildDocument()
    {
        $body = $this->buildHeading();
        $body .= $this->buildDaftarIsi();
        $body .= $this->buildBadan();
        $body .= $this->buildFooter();

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        $xml .= ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $xml .= ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">';
        $xml .= '<w:body>' . $body;
        $xml .= '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>';
        $xml .= '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/>';
        $xml .= '</w:sectPr></w:body></w:document>';

        return $xml;
    }

    private function buildHeading()
    {
        $xml = '';
        $j = $this->judul;

        if (!empty($j['institusi'])) {
            $xml .= $this->paragraf($j['institusi'], 'center', array('b' => true, 'sz' => 28, 'color' => '1F4E2C'), 0, 0);
        }
        if (!empty($j['judul'])) {
            $xml .= $this->paragraf($j['judul'], 'center', array('b' => true, 'sz' => 24), 0, 40);
        }
        if (!empty($j['subjudul'])) {
            $xml .= $this->paragraf($j['subjudul'], 'center', array('sz' => 18, 'color' => '555555'), 0, 120);
        }

        $xml .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr></w:p>';

        return $xml;
    }

    private function buildDaftarIsi()
    {
        if (empty($this->daftarIsi)) {
            return '';
        }

        $xml = $this->paragraf('DAFTAR ISI PASIEN', 'left', array('b' => true, 'sz' => 20, 'color' => '2E6E3F'), 120, 80);
        $xml .= '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';

        $widths = array(420, 3000, 1200, 2600, 1400);
        $xml .= '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:color="9CA3AF"/><w:left w:val="single" w:sz="4" w:color="9CA3AF"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:color="9CA3AF"/><w:right w:val="single" w:sz="4" w:color="9CA3AF"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:color="9CA3AF"/><w:insideV w:val="single" w:sz="4" w:color="9CA3AF"/>';
        $xml .= '</w:tblBorders></w:tblPr><w:tblGrid>';
        foreach ($widths as $w) {
            $xml .= '<w:gridCol w:w="' . $w . '"/>';
        }
        $xml .= '</w:tblGrid>';

        $header = ['No', 'Nama Pasien', 'No. RM', 'Periksa', 'Status Gambar'];
        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($header as $i => $h) {
            $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="E8F0EA"/>'
                . '<w:tcW w:w="' . $widths[$i] . '" w:type="dxa"/></w:tcPr>'
                . $this->paragraf($h, 'left', array('b' => true, 'sz' => 17), 20, 20) . '</w:tc>';
        }
        $xml .= '</w:tr>';

        $no = 0;
        foreach ($this->daftarIsi as $row) {
            $no++;
            $anchor = 'pasien_' . $this->anchorFor($row['no_rawat']);

            $klik = '<w:hyperlink w:anchor="' . $this->esc($anchor) . '" w:history="1">'
                . '<w:r><w:rPr><w:color w:val="0563C1"/><w:u w:val="single"/><w:sz w:val="17"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->esc($row['nama']) . '</w:t></w:r></w:hyperlink>';

            $warn = (strpos($row['status'], 'kosong') !== false);
            $warna = $warn ? 'B00000' : '333333';

            $xml .= '<w:tr>';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[0] . '" w:type="dxa"/></w:tcPr>'
                . $this->paragraf((string)$no, 'left', array('sz' => 17), 20, 20) . '</w:tc>';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[1] . '" w:type="dxa"/></w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:before="20" w:after="20"/></w:pPr>' . $klik . '</w:p></w:tc>';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[2] . '" w:type="dxa"/></w:tcPr>'
                . $this->paragraf($row['rm'], 'left', array('sz' => 17), 20, 20) . '</w:tc>';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[3] . '" w:type="dxa"/></w:tcPr>'
                . $this->paragraf($row['tgl'], 'left', array('sz' => 17), 20, 20) . '</w:tc>';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $widths[4] . '" w:type="dxa"/></w:tcPr>'
                . $this->paragraf($row['status'], 'left', array('sz' => 17, 'color' => $warna), 20, 20) . '</w:tc>';
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        $xml .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr>'
            . '<w:r><w:br w:type="page"/></w:r></w:p>';

        return $xml;
    }

    private function buildBadan()
    {
        if (empty($this->blok)) {
            return $this->paragraf('Tidak ada gambar rontgen PACS yang bisa dimuat untuk periode ini.', 'center', array('b' => true, 'color' => 'B00000'), 200, 0);
        }

        $xml = '';
        $total = count($this->blok);

        foreach ($this->blok as $i => $isi) {
            // Halaman pertama sudah dipisahkan dari daftar isi, jadi patients kedua
            // onwards yang butuh page break tambahan
            if ($i > 0 && $this->mulaiPasienBaru($this->blok, $i)) {
                $xml .= '<w:p><w:pPr><w:spacing w:after="0"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
            }

            $xml .= $isi;
        }

        return $xml;
    }

    private function mulaiPasienBaru($blok, $index)
    {
        // Paragraf pembatas pasien selalu diawali border bawah + warna hijau + bookmark
        return strpos($blok[$index], '<w:pBdr><w:bottom w:val="single" w:sz="6"') !== false;
    }

    private function buildFooter()
    {
        $xml = '';

        if (!empty($this->ringkasan)) {
            $xml .= $this->paragraf($this->ringkasan, 'left', array('sz' => 17, 'color' => '333333'), 200, 40);
        }
        if (!empty($this->catatan)) {
            $xml .= $this->paragraf($this->catatan, 'left', array('sz' => 17, 'color' => 'B00000', 'i' => true), 0, 0);
        }

        return $xml;
    }

    private function paragraf($teks, $align = 'left', $rpr = [], $before = 0, $after = 0)
    {
        $r = '';

        if (!empty($rpr)) {
            $r .= '<w:rPr>';
            if (!empty($rpr['b'])) {
                $r .= '<w:b/>';
            }
            if (!empty($rpr['i'])) {
                $r .= '<w:i/>';
            }
            if (isset($rpr['sz'])) {
                $r .= '<w:sz w:val="' . (int)$rpr['sz'] . '"/><w:szCs w:val="' . (int)$rpr['sz'] . '"/>';
            }
            if (isset($rpr['color'])) {
                $r .= '<w:color w:val="' . $this->esc($rpr['color']) . '"/>';
            }
            $r .= '</w:rPr>';
        }

        // ganti newline menjadi break agar path panjang tidak terpotong
        $parts = explode("\n", (string)$teks);
        $runs = '';
        foreach ($parts as $i => $part) {
            if ($i > 0) {
                $runs .= '<w:r>' . $r . '<w:br/></w:r>';
            }
            $runs .= '<w:r>' . $r . '<w:t xml:space="preserve">' . $this->esc($part) . '</w:t></w:r>';
        }

        return '<w:p><w:pPr><w:jc w:val="' . $this->esc($align) . '"/>'
            . '<w:spacing w:before="' . (int)$before . '" w:after="' . (int)$after . '"/>'
            . $r . '</w:pPr>' . $runs . '</w:p>';
    }

    private function buildContentTypes()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Default Extension="jpeg" ContentType="image/jpeg"/>';
        $xml .= '<Default Extension="jpg" ContentType="image/jpeg"/>';
        $xml .= '<Default Extension="png" ContentType="image/png"/>';
        $xml .= '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>';
        $xml .= '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>';
        $xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $xml .= '</Types>';
        return $xml;
    }

    private function buildRels()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>';
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private function buildCore()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"';
        $xml .= ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"';
        $xml .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $xml .= '<dc:title>' . $this->esc($this->judul['judul'] ?? 'Laporan Gambar Rontgen PACS') . '</dc:title>';
        $xml .= '<dc:creator>mLITE</dc:creator>';
        $xml .= '<cp:lastModifiedBy>mLITE</cp:lastModifiedBy>';
        $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>';
        $xml .= '</cp:coreProperties>';
        return $xml;
    }

    private function buildStyles()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
        $xml .= '<w:docDefaults><w:rPrDefault><w:rPr>';
        $xml .= '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>';
        $xml .= '<w:sz w:val="20"/><w:szCs w:val="20"/>';
        $xml .= '</w:rPr></w:rPrDefault><w:pPrDefault><w:pPr>';
        $xml .= '<w:spacing w:after="60" w:line="240" w:lineRule="auto"/>';
        $xml .= '</w:pPr></w:pPrDefault></w:docDefaults>';
        $xml .= '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>';
        $xml .= '</w:styles>';
        return $xml;
    }

    private function buildDocumentRels()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        foreach ($this->relasiGambar as $rel) {
            $xml .= '<Relationship Id="' . $this->esc($rel['id']) . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"'
                . ' Target="' . $this->esc($rel['target']) . '"/>';
        }

        $xml .= '</Relationships>';
        return $xml;
    }

    public function download()
    {
        $tmp = $this->buildZip();

        if ($tmp === false) {
            throw new \RuntimeException('Gagal membuat arsip Word di server.');
        }

        if (!headers_sent() && PHP_SAPI !== 'cli') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $this->filename . '"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: max-age=0');
            header('Pragma: public');
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        readfile($tmp);
        @unlink($tmp);
        $this->_bersihkanSampel();
    }

    private function _bersihkanSampel()
    {
        if ($this->dirSampel === null || $this->dirSampel === false) {
            return;
        }

        foreach (glob($this->dirSampel . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dirSampel);

        $this->dirSampel = null;
    }

    private function buildZip()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        if ($tmp === false) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            return false;
        }

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypes());
        $zip->addFromString('_rels/.rels', $this->buildRels());
        $zip->addFromString('docProps/core.xml', $this->buildCore());
        $zip->addFromString('word/styles.xml', $this->buildStyles());
        $zip->addFromString('word/document.xml', $this->buildDocument());
        $zip->addFromString('word/_rels/document.xml.rels', $this->buildDocumentRels());

        foreach ($this->relasiGambar as $rel) {
            if (!empty($rel['file']) && is_file($rel['file'])) {
                $zip->addFile($rel['file'], 'word/media/' . basename($rel['target']));
            } else {
                $zip->addFromString('word/media/' . basename($rel['target']), '');
            }
        }

        $zip->close();

        return $tmp;
    }
}
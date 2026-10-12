<?php
if (php_sapi_name() !== 'cli') {
    die("HANYA BISA DIJALANKAN VIA CLI\n");
}

if ($argc < 3) {
    die("Usage: php receiver.php <path_directory> <filename>\n");
}

$dir = $argv[1];
$file = $argv[2];
$filepath = rtrim($dir, '/') . '/' . $file;

if (!file_exists($filepath)) {
    die("File tidak ditemukan: $filepath\n");
}

define('BASE_DIR', realpath(__DIR__ . '/../../'));
require_once BASE_DIR . '/config.php';

try {
    if (defined('DBDRIVER') && DBDRIVER == 'sqlite') {
        $pdo = new PDO("sqlite:" . DBNAME);
    } else {
        $pdo = new PDO("mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=" . DBNAME . ";charset=utf8mb4", DBUSER, DBPASS);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Gagal Konek Database: " . $e->getMessage() . "\n");
}

$binPath = 'export PATH=$PATH:/usr/local/bin:/usr/bin:/opt/homebrew/bin && ';

// EKSTRAKSI METADATA VIA DCMDUMP
$patientId = trim((string) shell_exec($binPath . "dcmdump +P 0010,0020 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$patientName = trim((string) shell_exec($binPath . "dcmdump +P 0010,0010 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$accessionNumber = trim((string) shell_exec($binPath . "dcmdump +P 0008,0050 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$studyIdTag = trim((string) shell_exec($binPath . "dcmdump +P 0020,0010 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$reqProcId = trim((string) shell_exec($binPath . "dcmdump +P 0040,1001 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$stepId = trim((string) shell_exec($binPath . "dcmdump +P 0040,0009 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$studyUid = trim((string) shell_exec($binPath . "dcmdump +P 0020,000D -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$seriesUid = trim((string) shell_exec($binPath . "dcmdump +P 0020,000E -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$sopUid = trim((string) shell_exec($binPath . "dcmdump +P 0008,0018 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$modality = trim((string) shell_exec($binPath . "dcmdump +P 0008,0060 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$studyDate = trim((string) shell_exec($binPath . "dcmdump +P 0008,0020 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$studyTime = trim((string) shell_exec($binPath . "dcmdump +P 0008,0030 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
$studyDescription = trim((string) shell_exec($binPath . "dcmdump +P 0008,1030 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));

if (empty($studyDescription)) {
    $studyDescription = trim((string) shell_exec($binPath . "dcmdump +P 0018,0015 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
}

if (empty($studyDescription)) {
    $studyDescription = trim((string) shell_exec($binPath . "dcmdump +P 0018,1400 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
}

if (empty($studyDescription)) {
    $studyDescription = trim((string) shell_exec($binPath . "dcmdump +P 0032,1033 -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));
}

$seriesDescription = trim((string) shell_exec($binPath . "dcmdump +P 0008,103E -q " . escapeshellarg($filepath) . " | grep -o '\\[.*\\]' | tr -d '[]' | head -n 1"));

if (empty($studyUid) || empty($seriesUid) || empty($sopUid)) {
    die("Gagal ekstrak raw UID dari DICOM\n");
}

// Format studyDate YYYYMMDD ke YYYY-MM-DD (dan StudyTime HHMMSS)
$dateFmt = date('Y-m-d H:i:s');
$justDate = date('Y-m-d');
if (strlen($studyDate) == 8) {
    $timeStr = '00:00:00';
    if (strlen($studyTime) >= 6) {
        $timeStr = substr($studyTime, 0, 2) . ':' . substr($studyTime, 2, 2) . ':' . substr($studyTime, 4, 2);
    }
    $dateFmt = substr($studyDate, 0, 4) . '-' . substr($studyDate, 4, 2) . '-' . substr($studyDate, 6, 2) . ' ' . $timeStr;
    $justDate = substr($studyDate, 0, 4) . '-' . substr($studyDate, 4, 2) . '-' . substr($studyDate, 6, 2);
}

$no_rawat = '';
$noorder = '';

// ===== PENCARIAN CERDAS MULTI-STAGE UNTUK MENAUTKAN FOTO KE ORDER MLITE =====

// Tahap 1: Cocokkan Accession Number ke permintaan_radiologi.noorder
if (!empty($accessionNumber)) {
    $stmt = $pdo->prepare("SELECT no_rawat, noorder FROM permintaan_radiologi WHERE noorder = ? LIMIT 1");
    $stmt->execute([$accessionNumber]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $no_rawat = $row['no_rawat'];
        $noorder = $row['noorder'];
    }
}

// Tahap 1b: Jika Accession Number berisi prefix/suffix atau separator (mis. NOORDER-KDPRW atau RAD-NOORDER)
if (empty($no_rawat) && !empty($accessionNumber) && strpos($accessionNumber, '-') !== false) {
    $parts = explode('-', $accessionNumber);
    foreach ($parts as $p) {
        $p = trim($p);
        if (strlen($p) >= 4) {
            $stmt = $pdo->prepare("SELECT no_rawat, noorder FROM permintaan_radiologi WHERE noorder = ? LIMIT 1");
            $stmt->execute([$p]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $no_rawat = $row['no_rawat'];
                $noorder = $row['noorder'];
                break;
            }
        }
    }
}

// Tahap 1c: Jika Accession Number diisi nomor rawat langsung oleh operator (mis. 2026/01/01/000001 atau 20260101000001)
if (empty($no_rawat) && !empty($accessionNumber)) {
    $cleanAcc = str_replace(['/', '-', ' '], '', $accessionNumber);
    $stmt = $pdo->prepare("SELECT no_rawat FROM reg_periksa WHERE no_rawat = ? OR REPLACE(no_rawat, '/', '') = ? LIMIT 1");
    $stmt->execute([$accessionNumber, $cleanAcc]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $no_rawat = $row['no_rawat'];
        // Cari permintaan radiologi untuk no_rawat ini
        $stmtRad = $pdo->prepare("SELECT noorder FROM permintaan_radiologi WHERE no_rawat = ? ORDER BY tgl_permintaan DESC, jam_permintaan DESC LIMIT 1");
        $stmtRad->execute([$no_rawat]);
        if ($rowRad = $stmtRad->fetch(PDO::FETCH_ASSOC)) {
            $noorder = $rowRad['noorder'];
        }
    }
}

// Tahap 2: Cocokkan tag alternatif DICOM (StudyID / RequestedProcedureID / StepID)
if (empty($no_rawat)) {
    $altTags = array_filter([$studyIdTag, $reqProcId, $stepId]);
    foreach ($altTags as $cand) {
        $cand = trim($cand);
        if (!empty($cand)) {
            $stmt = $pdo->prepare("SELECT no_rawat, noorder FROM permintaan_radiologi WHERE noorder = ? LIMIT 1");
            $stmt->execute([$cand]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $no_rawat = $row['no_rawat'];
                $noorder = $row['noorder'];
                break;
            }
        }
    }
}

// Tahap 3: Cocokkan berdasarkan Patient ID (No RM) + Temporal Match (rentang +/- 3 hari dari studyDate)
if (empty($no_rawat) && !empty($patientId)) {
    $cleanPid = ltrim(trim($patientId), '0');
    $padPid = str_pad(trim($patientId), 6, '0', STR_PAD_LEFT);
    
    // Cari permintaan radiologi milik pasien ini yang paling dekat dengan tanggal studi
    $sqlRad = "SELECT pr.no_rawat, pr.noorder, pr.tgl_permintaan 
               FROM permintaan_radiologi pr 
               JOIN reg_periksa rp ON rp.no_rawat = pr.no_rawat 
               WHERE (rp.no_rkm_medis = ? OR rp.no_rkm_medis = ? OR rp.no_rkm_medis = ?)
                 AND pr.tgl_permintaan BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
               ORDER BY ABS(DATEDIFF(pr.tgl_permintaan, ?)) ASC, pr.jam_permintaan DESC LIMIT 1";
    $stmt = $pdo->prepare($sqlRad);
    $stmt->execute([$patientId, $cleanPid, $padPid, $justDate, $justDate, $justDate]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $no_rawat = $row['no_rawat'];
        $noorder = $row['noorder'];
    }
}

// Tahap 4: Fallback kunjungan terakhir Patient ID jika belum ditemukan
if (empty($no_rawat) && !empty($patientId)) {
    $cleanPid = ltrim(trim($patientId), '0');
    $padPid = str_pad(trim($patientId), 6, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("SELECT no_rawat FROM reg_periksa WHERE no_rkm_medis = ? OR no_rkm_medis = ? OR no_rkm_medis = ? ORDER BY tgl_registrasi DESC, jam_reg DESC LIMIT 1");
    $stmt->execute([$patientId, $cleanPid, $padPid]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $no_rawat = $row['no_rawat'];
        $stmtRad = $pdo->prepare("SELECT noorder FROM permintaan_radiologi WHERE no_rawat = ? ORDER BY tgl_permintaan DESC LIMIT 1");
        $stmtRad->execute([$no_rawat]);
        if ($rowRad = $stmtRad->fetch(PDO::FETCH_ASSOC)) {
            $noorder = $rowRad['noorder'];
        }
    }
}

$linkStatus = !empty($no_rawat) ? 'linked' : 'unlinked';

// 1. CARI ATAU BUAT STUDY
$stmt = $pdo->prepare("SELECT id, no_rawat, noorder FROM mlite_mini_pacs_study WHERE study_instance_uid = ?");
$stmt->execute([$studyUid]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $studyId = $row['id'];

    // Auto-patching jika no_rawat baru ditemukan tapi study sebelumnya masih kosong atau belum linked
    if (!empty($no_rawat) && (empty($row['no_rawat']) || empty($row['noorder']))) {
        $updateStmt = $pdo->prepare("UPDATE mlite_mini_pacs_study SET no_rawat = ?, noorder = ?, patient_id = ?, patient_name = ?, accession_number = ?, link_status = ? WHERE id = ?");
        $updateStmt->execute([$no_rawat, $noorder, $patientId, $patientName, $accessionNumber, 'linked', $studyId]);
    }
} else {
    $desc = $studyDescription ?: "Received via DICOM Receiver SCP";
    
    // Simpan dengan kolom pelengkap jika tersedia
    try {
        $insertStmt = $pdo->prepare("INSERT INTO mlite_mini_pacs_study (no_rawat, noorder, patient_id, patient_name, accession_number, study_instance_uid, study_date, modality, description, link_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$no_rawat, $noorder, $patientId, $patientName, $accessionNumber, $studyUid, $dateFmt, $modality, $desc, $linkStatus]);
        $studyId = $pdo->lastInsertId();
    } catch (\Exception $e) {
        // Fallback jika skema kolom pelengkap belum ada
        $insertStmt = $pdo->prepare("INSERT INTO mlite_mini_pacs_study (no_rawat, study_instance_uid, study_date, modality, description) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$no_rawat, $studyUid, $dateFmt, $modality, $desc]);
        $studyId = $pdo->lastInsertId();
    }
}

// 2. CARI ATAU BUAT SERIES
$stmt = $pdo->prepare("SELECT id FROM mlite_mini_pacs_series WHERE series_instance_uid = ?");
$stmt->execute([$seriesUid]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $seriesId = $row['id'];
} else {
    $insertStmt = $pdo->prepare("INSERT INTO mlite_mini_pacs_series (study_id, series_instance_uid, series_description) VALUES (?, ?, ?)");
    $sDesc = $seriesDescription ?: "Series dari Mesin Modalitas";
    $insertStmt->execute([$studyId, $seriesUid, $sDesc]);
    $seriesId = $pdo->lastInsertId();
}

// 3. RENAME FILE MENJADI FORMAT SOP UID
$newFilepath = $dir . '/' . $sopUid . '.dcm';
if ($filepath !== $newFilepath) {
    if (file_exists($newFilepath)) {
        unlink($newFilepath); // Timpa file lama jika uid sama
    }
    rename($filepath, $newFilepath);
    $filepath = $newFilepath;
}

// 4. INSERT INSTANCE FILE
$stmt = $pdo->prepare("SELECT id FROM mlite_mini_pacs_instance WHERE sop_instance_uid = ?");
$stmt->execute([$sopUid]);
if (!$row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $insertStmt = $pdo->prepare("INSERT INTO mlite_mini_pacs_instance (series_id, sop_instance_uid, file_path) VALUES (?, ?, ?)");
    $insertStmt->execute([$seriesId, $sopUid, $filepath]);
    $instanceId = $pdo->lastInsertId();
} else {
    $updateStmt = $pdo->prepare("UPDATE mlite_mini_pacs_instance SET file_path = ? WHERE sop_instance_uid = ?");
    $updateStmt->execute([$filepath, $sopUid]);
    $instanceId = $row['id'];
}

// 4.5. INSERT METADATA
// Mengekstrak seluruh payload JSON tag dari DICOM menggunakan utilitas bawwa dcmtk
$jsonOutput = shell_exec($binPath . "dcm2json -fc " . escapeshellarg($filepath));
$metadataDecoded = json_decode($jsonOutput, true);

if ($metadataDecoded) {
    try {
        // Hapus metadata usang agar tidak ganda jika ditimpa
        $delStmt = $pdo->prepare("DELETE FROM mlite_mini_pacs_instance_metadata WHERE instance_id = ?");
        $delStmt->execute([$instanceId]);
        
        $metaInsertStmt = $pdo->prepare("INSERT INTO mlite_mini_pacs_instance_metadata (instance_id, tag, name, value) VALUES (?, ?, ?, ?)");
        
        foreach ($metadataDecoded as $tag => $data) {
            $name = isset($data['vr']) ? $data['vr'] : '';
            $valueStr = '';
            
            if (isset($data['Value']) && is_array($data['Value'])) {
                if (isset($data['Value'][0]) && is_array($data['Value'][0])) {
                    if (isset($data['Value'][0]['Alphabetic'])) {
                        $valueStr = $data['Value'][0]['Alphabetic'];
                    } else {
                        $valueStr = json_encode($data['Value']);
                    }
                } else {
                    $valueStr = implode(', ', $data['Value']);
                }
            } elseif (isset($data['InlineBinary'])) {
                $valueStr = '[InlineBinary Data]';
            }
            
            if (strlen($valueStr) > 60000) {
                $valueStr = substr($valueStr, 0, 60000) . '...';
            }
            
            if (strlen($tag) == 8) {
                $formattedTag = substr($tag, 0, 4) . ',' . substr($tag, 4, 4);
            } else {
                $formattedTag = $tag;
            }
            
            if (!empty($formattedTag) && $formattedTag !== '7FE0,0010') { // Skip pixel data itself
                $metaInsertStmt->execute([$instanceId, strtoupper($formattedTag), $name, $valueStr]);
            }
        }
    } catch (Exception $e) {
        echo "Peringatan: Gagal menyimpan instance_metadata (" . $e->getMessage() . ")\n";
    }
}

// 5. GENERATE THUMBNAIL
$thumbnailFile = $dir . '/' . $sopUid . '_thumb.jpg';
$cmdThumb = sprintf(
    $binPath . 'dcmj2pnm +oj +Wn %s %s 2>&1',
    escapeshellarg($filepath),
    escapeshellarg($thumbnailFile)
);
shell_exec($cmdThumb);

echo "DICOM Berhasil diproses: $filepath\n";

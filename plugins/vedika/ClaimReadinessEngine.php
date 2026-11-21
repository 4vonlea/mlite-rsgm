<?php

namespace Plugins\Vedika;

class ClaimReadinessEngine
{
    protected $db;
    protected $settings;
    protected $core;

    public function __construct($db, $settings, $core = null)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->core = $core;
    }

    /**
     * Audit kesiapan klaim pasien secara komprehensif
     * Sesuai regulasi Permenkes 26/2021, Permenkes 3/2023, & Standar RSGM
     */
    public function checkPatient($no_rawat)
    {
        $reg = $this->db->pdo()->prepare("
            SELECT reg_periksa.*, pasien.nm_pasien, pasien.no_peserta, pasien.no_ktp, pasien.jk, pasien.tgl_lahir,
                   poliklinik.nm_poli, dokter.nm_dokter, penjab.png_jawab
            FROM reg_periksa
            JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
            WHERE reg_periksa.no_rawat = ?
            LIMIT 1
        ");
        $reg->execute([$no_rawat]);
        $pasien = $reg->fetch(\PDO::FETCH_ASSOC);

        if (!$pasien) {
            return [
                'status' => 'error',
                'message' => 'Data pasien dan registrasi rawat tidak ditemukan.'
            ];
        }

        $items = [];
        $mandatoryCount = 0;
        $mandatoryPassed = 0;
        $missingItems = [];

        // 1. SURAT ELIGIBILITAS PESERTA (SEP) - WAJIB
        $mandatoryCount++;
        $sepStmt = $this->db->pdo()->prepare("
            SELECT no_sep, tglsep, no_kartu, no_rujukan, diagawal, kdpolitujuan, nmpolitujuan, klsrawat, jnspelayanan
            FROM bridging_sep
            WHERE no_rawat = ?
            UNION
            SELECT no_sep, tglsep, no_kartu, no_rujukan, diagawal, kdpolitujuan, nmpolitujuan, klsrawat, jnspelayanan
            FROM bridging_sep_internal
            WHERE no_rawat = ?
            LIMIT 1
        ");
        $sepStmt->execute([$no_rawat, $no_rawat]);
        $sep = $sepStmt->fetch(\PDO::FETCH_ASSOC);

        // Jika bridging_sep kosong di database lokal, cek Berkas Digital SEP yang diunggah RS (setting vedika.sep / DIG001)
        $is_sep_berkas = false;
        $sep_file = '';
        if (empty($sep['no_sep'])) {
            $kodeSep = ($this->settings && method_exists($this->settings, 'get')) ? $this->settings->get('vedika.sep') : 'DIG001';
            if (empty($kodeSep)) $kodeSep = 'DIG001';
            $bStmt = $this->db->pdo()->prepare("SELECT * FROM berkas_digital_perawatan WHERE no_rawat = ? AND kode = ? LIMIT 1");
            $bStmt->execute([$no_rawat, $kodeSep]);
            $bRow = $bStmt->fetch(\PDO::FETCH_ASSOC);
            if ($bRow) {
                $is_sep_berkas = true;
                $sep_file = $bRow['lokasi_file'];
                $cleanNr = str_replace(['/', '-'], '', $no_rawat);
                $sep = [
                    'no_sep' => 'SEP-' . $cleanNr,
                    'tglsep' => $pasien['tgl_registrasi'],
                    'no_kartu' => $pasien['no_peserta'],
                    'no_rujukan' => '-',
                    'diagawal' => '-',
                    'kdpolitujuan' => $pasien['kd_poli'],
                    'nmpolitujuan' => $pasien['nm_poli'],
                    'klsrawat' => ($pasien['status_lanjut'] === 'Ranap' ? '3' : '-'),
                    'jnspelayanan' => ($pasien['status_lanjut'] === 'Ranap' ? '1' : '2')
                ];
            }
        }

        $is_sep_valid = !empty($sep['no_sep']);
        $sepDetail = null;
        if ($is_sep_valid) {
            if ($is_sep_berkas) {
                $sepDetail = "Scan Berkas Digital SEP: {$sep_file} | Kartu: {$pasien['no_peserta']}";
            } else {
                $sepDetail = "Tgl SEP: {$sep['tglsep']} | Kartu: {$sep['no_kartu']}";
            }
        }

        $items['sep'] = [
            'name' => 'Surat Eligibilitas Peserta (SEP)',
            'category' => 'Administrasi',
            'requirement' => 'Wajib',
            'is_ready' => $is_sep_valid,
            'value' => $is_sep_valid ? ($is_sep_berkas ? 'Berkas SEP Terunggah' : $sep['no_sep']) : 'Belum Diterbitkan',
            'detail' => $sepDetail,
            'reason' => 'Syarat mutlak penjaminan BPJS Kesehatan FKRTL sesuai kontrak kerjasama.',
            'source_module' => $is_sep_berkas ? 'vedika' : 'vclaim',
            'pic' => 'Petugas Admisi / Berkas Rekam Medis',
            'action_label' => $is_sep_valid ? ($is_sep_berkas ? 'Lihat Berkas SEP' : 'Lihat SEP') : 'Buat SEP VClaim',
            'action_url' => $is_sep_berkas ? url([$sep_file]) : url([ADMIN, 'vclaim', 'sep', '?no_rawat=' . $no_rawat])
        ];
        if ($is_sep_valid) {
            $mandatoryPassed++;
        } else {
            $missingItems[] = 'SEP BPJS belum diterbitkan';
        }

        // 2. RESUME MEDIS & DPJP AUTH - WAJIB
        $mandatoryCount++;
        $isRanap = ($pasien['status_lanjut'] === 'Ranap');
        if ($isRanap) {
            $resStmt = $this->db->pdo()->prepare("SELECT * FROM resume_pasien_ranap WHERE no_rawat = ? LIMIT 1");
        } else {
            $resStmt = $this->db->pdo()->prepare("SELECT * FROM resume_pasien WHERE no_rawat = ? LIMIT 1");
        }
        $resStmt->execute([$no_rawat]);
        $resume = $resStmt->fetch(\PDO::FETCH_ASSOC);

        $is_resume_valid = false;
        $is_dpjp_valid = false;
        $resume_source = 'resume_pasien';
        if ($resume) {
            $hasAnamnesis = !empty($resume['keluhan_utama']) || !empty($resume['jalannya_penyakit']);
            $hasDiagnosaResume = !empty($resume['diagnosa_utama']) || !empty($resume['kd_diagnosa_utama']);
            $is_resume_valid = ($hasAnamnesis && $hasDiagnosaResume);
            $is_dpjp_valid = !empty($resume['kd_dokter']);
        }

        // Fallback untuk Rawat Jalan / RSGM: Periksa catatan pemeriksaan EMR (pemeriksaan_ralan) jika resume_pasien belum dicetak
        if (!$is_resume_valid && !$isRanap) {
            $pemStmt = $this->db->pdo()->prepare("
                SELECT * FROM pemeriksaan_ralan 
                WHERE no_rawat = ? 
                ORDER BY jam_rawat DESC LIMIT 1
            ");
            $pemStmt->execute([$no_rawat]);
            $pem = $pemStmt->fetch(\PDO::FETCH_ASSOC);

            if ($pem && (!empty($pem['keluhan']) || !empty($pem['pemeriksaan']) || !empty($pem['penilaian']))) {
                $is_resume_valid = true;
                $is_dpjp_valid = (!empty($pem['nip']) || !empty($pasien['kd_dokter']));
                $resume_source = 'pemeriksaan_ralan';
                $resume = [
                    'keluhan_utama' => $pem['keluhan'],
                    'diagnosa_utama' => $pem['penilaian'] ?: ($pem['pemeriksaan'] ?: 'Tercatat di EMR SOAP'),
                    'kd_dokter' => $pem['nip'] ?: $pasien['kd_dokter']
                ];
            }
        }

        $items['resume'] = [
            'name' => $isRanap ? 'Resume Medis Rawat Inap' : 'Resume Medis Rawat Jalan (SOAP)',
            'category' => 'Klinis',
            'requirement' => 'Wajib',
            'is_ready' => $is_resume_valid,
            'value' => $is_resume_valid ? ($resume_source === 'pemeriksaan_ralan' ? 'Tercatat di EMR Ralan' : 'Lengkap & Terisi') : 'Belum Lengkap',
            'detail' => $resume ? "DPJP: {$resume['kd_dokter']} | Keluhan: " . substr($resume['keluhan_utama'] ?? '-', 0, 40) . '...' : null,
            'reason' => 'Bukti sah kronologis klinis, pemeriksaan fisik, serta terapi dari DPJP.',
            'source_module' => 'rawat_jalan',
            'pic' => 'Dokter Penanggung Jawab Pelayanan (DPJP)',
            'action_label' => 'Edit Resume Medis',
            'action_url' => url([ADMIN, 'vedika', 'resume', convertNorawat($no_rawat)])
        ];
        if ($is_resume_valid) {
            $mandatoryPassed++;
        } else {
            $missingItems[] = 'Resume medis klinis belum lengkap';
        }

        // 3. AUTENTIKASI DPJP
        $items['dpjp'] = [
            'name' => 'Autentikasi DPJP',
            'category' => 'Klinis',
            'requirement' => 'Wajib',
            'is_ready' => $is_dpjp_valid,
            'value' => $is_dpjp_valid ? 'Tervalidasi DPJP' : 'Belum Divalidasi',
            'detail' => $is_dpjp_valid ? "Dokter: {$pasien['nm_dokter']}" : 'Belum ada tanda tangan/verifikasi DPJP',
            'reason' => 'Pertanggungjawaban medis legal dan kepatuhan audit klaim.',
            'source_module' => 'rawat_jalan',
            'pic' => 'Dokter DPJP',
            'action_label' => 'Validasi DPJP',
            'action_url' => url([ADMIN, 'vedika', 'resume', convertNorawat($no_rawat)])
        ];

        // 4. CODING ICD-10 (DIAGNOSIS) - WAJIB
        $mandatoryCount++;
        $diagStmt = $this->db->pdo()->prepare("
            SELECT diagnosa_pasien.*, penyakit.nm_penyakit 
            FROM diagnosa_pasien
            LEFT JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
            WHERE diagnosa_pasien.no_rawat = ?
            ORDER BY diagnosa_pasien.prioritas ASC
        ");
        $diagStmt->execute([$no_rawat]);
        $diagnosas = $diagStmt->fetchAll(\PDO::FETCH_ASSOC);

        $hasPrimaryDiag = false;
        $diagList = [];
        foreach ($diagnosas as $d) {
            if ($d['prioritas'] == 1) {
                $hasPrimaryDiag = true;
            }
            $diagList[] = "{$d['kd_penyakit']} - {$d['nm_penyakit']} (P{$d['prioritas']})";
        }

        $is_icd10_valid = $hasPrimaryDiag;
        $items['icd10'] = [
            'name' => 'Koding Diagnosis (ICD-10)',
            'category' => 'Koding Medis',
            'requirement' => 'Wajib',
            'is_ready' => $is_icd10_valid,
            'value' => $is_icd10_valid ? count($diagnosas) . ' Kode Tercatat' : 'Belum Ada Diagnosis Utama (P1)',
            'detail' => count($diagList) ? implode(', ', $diagList) : 'Kode diagnosis utama ICD-10 belum diinput',
            'reason' => 'Variabel krusial untuk grouping tarif INA-CBG Kemenkes.',
            'source_module' => 'vedika',
            'pic' => 'Petugas Rekam Medis / Koder Casemix',
            'action_label' => 'Kelola Diagnosis ICD-10',
            'action_url' => url([ADMIN, 'vedika', 'ubahdiagnosa', convertNorawat($no_rawat)])
        ];
        if ($is_icd10_valid) {
            $mandatoryPassed++;
        } else {
            $missingItems[] = 'Diagnosis Utama (ICD-10 P1) belum ditentukan';
        }

        // 5. CODING ICD-9-CM (PROSEDUR / TINDAKAN) - KONDISIONAL
        $procStmt = $this->db->pdo()->prepare("
            SELECT prosedur_pasien.*, icd9.deskripsi_panjang
            FROM prosedur_pasien
            LEFT JOIN icd9 ON prosedur_pasien.kode = icd9.kode
            WHERE prosedur_pasien.no_rawat = ?
            ORDER BY prosedur_pasien.prioritas ASC
        ");
        $procStmt->execute([$no_rawat]);
        $prosedurs = $procStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Cek apakah pasien mendapatkan tindakan medis di ralan/ranap
        $actStmt = $this->db->pdo()->prepare("
            SELECT COUNT(*) FROM rawat_jl_dr WHERE no_rawat = ?
        ");
        $actStmt->execute([$no_rawat]);
        $hasClinicalAction = ($actStmt->fetchColumn() > 0);

        $is_icd9_valid = (count($prosedurs) > 0 || !$hasClinicalAction);
        $procList = [];
        foreach ($prosedurs as $p) {
            $procList[] = "{$p['kode']} - {$p['deskripsi_panjang']}";
        }

        $items['icd9'] = [
            'name' => 'Koding Prosedur/Tindakan (ICD-9-CM)',
            'category' => 'Koding Medis',
            'requirement' => $hasClinicalAction ? 'Wajib (Ada Tindakan Klinis)' : 'Kondisional (Hanya Konsultasi)',
            'is_ready' => $is_icd9_valid,
            'value' => count($prosedurs) ? count($prosedurs) . ' Prosedur Tercatat' : ($hasClinicalAction ? 'Belum Dikodekan' : 'Tanpa Tindakan'),
            'detail' => count($procList) ? implode(', ', $procList) : 'Tidak ada kode prosedur ICD-9-CM',
            'reason' => 'Prosedur menentukan kenaikan severity level dan tarif grouping INA-CBG.',
            'source_module' => 'vedika',
            'pic' => 'Petugas Rekam Medis / Koder Casemix',
            'action_label' => 'Kelola Prosedur ICD-9-CM',
            'action_url' => url([ADMIN, 'vedika', 'ubahprosedur', convertNorawat($no_rawat)])
        ];
        if ($hasClinicalAction) {
            $mandatoryCount++;
            if ($is_icd9_valid) {
                $mandatoryPassed++;
            } else {
                $missingItems[] = 'Tindakan klinis belum dikodekan ke ICD-9-CM';
            }
        }

        // 6. VALIDASI KHUSUS RSGM: ODONTOGRAM & PEMERIKSAAN GIGI - KONDISIONAL KHUSUS RSGM
        $isDentalPoli = (
            strpos($pasien['kd_poli'], 'SP') === 0 || 
            strpos($pasien['kd_poli'], 'PG') === 0 ||
            stripos($pasien['nm_poli'], 'Gigi') !== false ||
            stripos($pasien['nm_poli'], 'Mulut') !== false
        );

        $is_odontogram_valid = true;
        if ($isDentalPoli) {
            $mandatoryCount++;
            $odontoStmt = $this->db->pdo()->prepare("SELECT COUNT(*) FROM mlite_odontogram WHERE no_rawat = ? OR no_rkm_medis = ?");
            $odontoStmt->execute([$no_rawat, $pasien['no_rkm_medis']]);
            $odontoCount = (int)$odontoStmt->fetchColumn();

            $is_odontogram_valid = ($odontoCount > 0);
            $odontoValue = $is_odontogram_valid ? 'Odontogram Tercatat' : 'Belum Ada Odontogram';
            $odontoDetail = $is_odontogram_valid ? "Tercatat {$odontoCount} elemen/kondisi gigi" : 'Poli Gigi mewajibkan charting odontogram sesuai Permenkes RSGM';

            // Fallback pemeriksaan klinis gigi jika belum input odontogram grafis
            if (!$is_odontogram_valid) {
                $pemOdontoStmt = $this->db->pdo()->prepare("SELECT pemeriksaan FROM pemeriksaan_ralan WHERE no_rawat = ? AND pemeriksaan IS NOT NULL AND TRIM(pemeriksaan) != '' LIMIT 1");
                $pemOdontoStmt->execute([$no_rawat]);
                $pemExam = $pemOdontoStmt->fetchColumn();
                if (!empty($pemExam)) {
                    $is_odontogram_valid = true;
                    $odontoValue = 'Tercatat di Catatan Klinis Gigi';
                    $odontoDetail = 'Pemeriksaan Klinis: ' . substr($pemExam, 0, 50) . '...';
                }
            }

            $items['odontogram'] = [
                'name' => 'Pemeriksaan Odontogram Dental (RSGM)',
                'category' => 'Klinis Spesifik RSGM',
                'requirement' => 'Wajib (Klinik Spesialis Kedokteran Gigi)',
                'is_ready' => $is_odontogram_valid,
                'value' => $odontoValue,
                'detail' => $odontoDetail,
                'reason' => 'Standar wajib rekam medis kedokteran gigi untuk verifikasi klaim kasus gigi & bedah mulut.',
                'source_module' => 'rawat_jalan',
                'pic' => 'Dokter Gigi DPJP / Perawat Gigi',
                'action_label' => 'Buka Odontogram',
                'action_url' => url([ADMIN, 'rawat_jalan', 'odontogram', convertNorawat($no_rawat)])
            ];
            if ($is_odontogram_valid) {
                $mandatoryPassed++;
            } else {
                $missingItems[] = 'Odontogram pemeriksaan gigi belum dicatat';
            }
        } else {
            $items['odontogram'] = [
                'name' => 'Pemeriksaan Odontogram Dental',
                'category' => 'Klinis Spesifik RSGM',
                'requirement' => 'Tidak Relevan',
                'is_ready' => true,
                'value' => 'Bukan Poli Gigi',
                'detail' => 'Pasien dilayani di poliklinik non-spesialis gigi.',
                'reason' => 'Tidak dipersyaratkan untuk pelayanan non-kedokteran gigi.',
                'source_module' => '-',
                'pic' => '-',
                'action_label' => null,
                'action_url' => null
            ];
        }

        // 7. PEMERIKSAAN PENUNJANG (LABORATORIUM & RADIOLOGI DENTAL) - KONDISIONAL
        $labStmt = $this->db->pdo()->prepare("SELECT COUNT(*) FROM periksa_lab WHERE no_rawat = ?");
        $labStmt->execute([$no_rawat]);
        $labCount = $labStmt->fetchColumn();

        $radStmt = $this->db->pdo()->prepare("SELECT COUNT(*) FROM periksa_radiologi WHERE no_rawat = ?");
        $radStmt->execute([$no_rawat]);
        $radCount = $radStmt->fetchColumn();

        $hasPenunjang = ($labCount > 0 || $radCount > 0);
        $items['penunjang'] = [
            'name' => 'Hasil Penunjang (Lab / Radiologi Dental)',
            'category' => 'Penunjang Medis',
            'requirement' => $hasPenunjang ? 'Wajib Dilampirkan' : 'Kondisional (Bila Ada Indikasi)',
            'is_ready' => true,
            'value' => $hasPenunjang ? "Lab: {$labCount} | Radiologi: {$radCount}" : 'Tidak Ada Permintaan Penunjang',
            'detail' => $hasPenunjang ? 'Pemeriksaan penunjang tercatat pada database rekam medis' : 'Kunjungan ini tidak menyertakan order lab/radiologi',
            'reason' => 'Bukti diagnosa penunjang seperti foto panoramic/periapical pada tindakan bedah mulut.',
            'source_module' => 'radiologi / laboratorium',
            'pic' => 'Petugas Radiologi / Analis Laboratorium',
            'action_label' => 'Lihat Penunjang',
            'action_url' => url([ADMIN, 'vedika', 'pdf', convertNorawat($no_rawat)])
        ];

        // 8. BILLING & RINCIAN BIAYA - WAJIB
        $mandatoryCount++;
        $billStmt = $this->db->pdo()->prepare("SELECT * FROM mlite_billing WHERE no_rawat = ? LIMIT 1");
        $billStmt->execute([$no_rawat]);
        $billing = $billStmt->fetch(\PDO::FETCH_ASSOC);

        $is_billing_valid = !empty($billing['id_billing']);
        $totalBilling = $is_billing_valid ? $billing['jumlah_total'] : 0;

        $items['billing'] = [
            'name' => 'Penutupan Billing & Rincian Biaya RS',
            'category' => 'Keuangan',
            'requirement' => 'Wajib',
            'is_ready' => $is_billing_valid,
            'value' => $is_billing_valid ? 'Rp ' . number_format($totalBilling, 0, ',', '.') : 'Billing Belum Ditutup',
            'detail' => $is_billing_valid ? "Tgl Billing: {$billing['tgl_billing']} {$billing['jam_billing']}" : 'Kasir belum melakukan closing billing perawatan',
            'reason' => 'Perhitungan tarif total rumah sakit sebagai variabel input E-Klaim.',
            'source_module' => 'kasir',
            'pic' => 'Petugas Kasir / Keuangan',
            'action_label' => 'Buka Modul Kasir',
            'action_url' => url([ADMIN, 'kasir', 'billing', '?no_rawat=' . $no_rawat])
        ];
        if ($is_billing_valid) {
            $mandatoryPassed++;
        } else {
            $missingItems[] = 'Billing perawatan belum diselesaikan kasir';
        }

        // 9. BERKAS DIGITAL PERAWATAN
        $berkasStmt = $this->db->pdo()->prepare("
            SELECT berkas_digital_perawatan.*, master_berkas_digital.nama
            FROM berkas_digital_perawatan
            JOIN master_berkas_digital ON master_berkas_digital.kode = berkas_digital_perawatan.kode
            WHERE berkas_digital_perawatan.no_rawat = ?
        ");
        $berkasStmt->execute([$no_rawat]);
        $berkasDigital = $berkasStmt->fetchAll(\PDO::FETCH_ASSOC);

        $items['berkas_digital'] = [
            'name' => 'Dokumen Digital Pendukung',
            'category' => 'Dokumen Digital',
            'requirement' => 'Kondisional',
            'is_ready' => (count($berkasDigital) > 0),
            'value' => count($berkasDigital) . ' Berkas Terunggah',
            'detail' => count($berkasDigital) ? implode(', ', array_column($berkasDigital, 'nama')) : 'Belum ada scan dokumen eksternal diunggah',
            'reason' => 'Dokumen rujukan faskes 1, lembar persetujuan tindakan (informed consent), dsb.',
            'source_module' => 'vedika',
            'pic' => 'Petugas Berkas Rekam Medis',
            'action_label' => 'Upload Berkas Digital',
            'action_url' => url([ADMIN, 'vedika', 'berkasperawatan', convertNorawat($no_rawat)])
        ];

        // 10. VERIFIKASI INTERNAL (ADMINISTRASI & MEDIS)
        $verifStmt = $this->db->pdo()->prepare("SELECT * FROM mlite_klaim_verifikasi WHERE no_rawat = ? LIMIT 1");
        $verifStmt->execute([$no_rawat]);
        $verif = $verifStmt->fetch(\PDO::FETCH_ASSOC);

        $isAdminVerified = ($verif && $verif['verif_admin_status'] === 'approved');
        $isMedisVerified = ($verif && $verif['verif_medis_status'] === 'approved');
        $isInternalVerified = ($isAdminVerified && $isMedisVerified);

        // Perhitungan Skor Persentase
        $scorePercent = round(($mandatoryPassed / max(1, $mandatoryCount)) * 100);

        // Penentuan Status Kesiapan Klaim
        $statusReadiness = 'belum_lengkap';
        if ($scorePercent < 100) {
            $statusReadiness = 'belum_lengkap';
        } elseif (!$isInternalVerified) {
            $statusReadiness = 'siap_verifikasi';
        } else {
            // Cek apakah sudah grouping atau siap grouping
            $eklaimStmt = $this->db->pdo()->prepare("
                SELECT COUNT(*) FROM mlite_eklaim_logs 
                WHERE nomor_sep = ? AND method = 'grouper'
            ");
            $eklaimStmt->execute([$sep['no_sep'] ?? '']);
            $hasGrouped = ($eklaimStmt->fetchColumn() > 0);

            if ($hasGrouped) {
                $statusReadiness = 'siap_batch';
            } else {
                $statusReadiness = 'siap_grouping';
            }
        }

        // Simpan / Perbarui Cache Status di Tabel mlite_klaim_readiness
        $this->saveReadinessCache(
            $no_rawat,
            $sep['no_sep'] ?? null,
            $pasien['no_rkm_medis'],
            $pasien['tgl_registrasi'],
            $pasien['status_lanjut'],
            $pasien['kd_poli'],
            $pasien['kd_dokter'],
            $is_sep_valid ? 1 : 0,
            $is_resume_valid ? 1 : 0,
            $is_dpjp_valid ? 1 : 0,
            $is_icd10_valid ? 1 : 0,
            $is_icd9_valid ? 1 : 0,
            $is_billing_valid ? 1 : 0,
            $is_odontogram_valid ? 1 : 0,
            $hasPenunjang ? 1 : 0,
            (count($berkasDigital) > 0) ? 1 : 0,
            $scorePercent,
            $statusReadiness,
            implode('; ', $missingItems)
        );

        return [
            'status' => 'success',
            'no_rawat' => $no_rawat,
            'pasien' => $pasien,
            'sep' => $sep,
            'score_percent' => $scorePercent,
            'status_readiness' => $statusReadiness,
            'missing_items' => $missingItems,
            'is_internal_verified' => $isInternalVerified,
            'verif_details' => $verif,
            'checklist' => $items
        ];
    }

    /**
     * Cache readiness status ke database agar query list & dashboard sangat cepat
     */
    protected function saveReadinessCache(
        $no_rawat, $no_sep, $no_rkm_medis, $tgl_registrasi, $status_lanjut,
        $kd_poli, $kd_dokter, $is_sep, $is_resume, $is_dpjp, $is_icd10,
        $is_icd9, $is_billing, $is_odonto, $is_penunjang, $is_berkas,
        $score, $status_readiness, $missing
    ) {
        $sql = "
            INSERT INTO mlite_klaim_readiness (
                no_rawat, no_sep, no_rkm_medis, tgl_registrasi, status_lanjut,
                kd_poli, kd_dokter, is_sep_valid, is_resume_valid, is_dpjp_valid,
                is_icd10_valid, is_icd9_valid, is_billing_valid, is_odontogram_valid,
                is_penunjang_valid, is_berkas_valid, score_percent, status_readiness,
                missing_items, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            ) ON DUPLICATE KEY UPDATE
                no_sep = VALUES(no_sep),
                is_sep_valid = VALUES(is_sep_valid),
                is_resume_valid = VALUES(is_resume_valid),
                is_dpjp_valid = VALUES(is_dpjp_valid),
                is_icd10_valid = VALUES(is_icd10_valid),
                is_icd9_valid = VALUES(is_icd9_valid),
                is_billing_valid = VALUES(is_billing_valid),
                is_odontogram_valid = VALUES(is_odontogram_valid),
                is_penunjang_valid = VALUES(is_penunjang_valid),
                is_berkas_valid = VALUES(is_berkas_valid),
                score_percent = VALUES(score_percent),
                status_readiness = VALUES(status_readiness),
                missing_items = VALUES(missing_items),
                updated_at = NOW()
        ";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            $no_rawat, $no_sep, $no_rkm_medis, $tgl_registrasi, $status_lanjut,
            $kd_poli, $kd_dokter, $is_sep, $is_resume, $is_dpjp, $is_icd10,
            $is_icd9, $is_billing, $is_odonto, $is_penunjang, $is_berkas,
            $score, $status_readiness, $missing
        ]);
    }

    /**
     * Audit Trail logger untuk kepatuhan tata kelola klaim
     */
    public function logAuditTrail($no_rawat, $no_sep, $action_type, $field_name, $old_val, $new_val, $user, $notes = '')
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $this->db->pdo()->prepare("
            INSERT INTO mlite_klaim_audit_trail (
                no_rawat, no_sep, action_type, field_name, old_value, new_value, user_username, ip_address, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $no_rawat, $no_sep, $action_type, $field_name,
            is_array($old_val) ? json_encode($old_val) : (string)$old_val,
            is_array($new_val) ? json_encode($new_val) : (string)$new_val,
            $user, $ip, $notes
        ]);
    }

    /**
     * Simpan status Verifikasi Internal (Administrasi atau Medis)
     */
    public function saveVerification($no_rawat, $no_sep, $type, $status, $notes, $user)
    {
        $current = $this->db->pdo()->prepare("SELECT * FROM mlite_klaim_verifikasi WHERE no_rawat = ? LIMIT 1");
        $current->execute([$no_rawat]);
        $row = $current->fetch(\PDO::FETCH_ASSOC);

        $now = date('Y-m-d H:i:s');
        if (!$row) {
            $adminStatus = ($type === 'admin') ? $status : 'pending';
            $adminUser = ($type === 'admin') ? $user : null;
            $adminAt = ($type === 'admin') ? $now : null;
            $adminNotes = ($type === 'admin') ? $notes : null;

            $medisStatus = ($type === 'medis') ? $status : 'pending';
            $medisUser = ($type === 'medis') ? $user : null;
            $medisAt = ($type === 'medis') ? $now : null;
            $medisNotes = ($type === 'medis') ? $notes : null;

            $isVerified = ($adminStatus === 'approved' && $medisStatus === 'approved') ? 1 : 0;

            $stmt = $this->db->pdo()->prepare("
                INSERT INTO mlite_klaim_verifikasi (
                    no_rawat, no_sep, verif_admin_status, verif_admin_user, verif_admin_at, verif_admin_notes,
                    verif_medis_status, verif_medis_user, verif_medis_at, verif_medis_notes,
                    is_internal_verified, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $no_rawat, $no_sep, $adminStatus, $adminUser, $adminAt, $adminNotes,
                $medisStatus, $medisUser, $medisAt, $medisNotes, $isVerified
            ]);
        } else {
            $adminStatus = ($type === 'admin') ? $status : $row['verif_admin_status'];
            $adminUser = ($type === 'admin') ? $user : $row['verif_admin_user'];
            $adminAt = ($type === 'admin') ? $now : $row['verif_admin_at'];
            $adminNotes = ($type === 'admin') ? $notes : $row['verif_admin_notes'];

            $medisStatus = ($type === 'medis') ? $status : $row['verif_medis_status'];
            $medisUser = ($type === 'medis') ? $user : $row['verif_medis_user'];
            $medisAt = ($type === 'medis') ? $now : $row['verif_medis_at'];
            $medisNotes = ($type === 'medis') ? $notes : $row['verif_medis_notes'];

            $isVerified = ($adminStatus === 'approved' && $medisStatus === 'approved') ? 1 : 0;

            $stmt = $this->db->pdo()->prepare("
                UPDATE mlite_klaim_verifikasi SET
                    verif_admin_status = ?, verif_admin_user = ?, verif_admin_at = ?, verif_admin_notes = ?,
                    verif_medis_status = ?, verif_medis_user = ?, verif_medis_at = ?, verif_medis_notes = ?,
                    is_internal_verified = ?, updated_at = NOW()
                WHERE no_rawat = ?
            ");
            $stmt->execute([
                $adminStatus, $adminUser, $adminAt, $adminNotes,
                $medisStatus, $medisUser, $medisAt, $medisNotes,
                $isVerified, $no_rawat
            ]);
        }

        $this->logAuditTrail(
            $no_rawat, $no_sep, 'VERIF_' . strtoupper($type),
            'verif_' . $type . '_status',
            $row ? $row['verif_' . $type . '_status'] : 'none',
            $status,
            $user,
            $notes
        );

        // Refresh cache
        $this->checkPatient($no_rawat);

        return true;
    }
}

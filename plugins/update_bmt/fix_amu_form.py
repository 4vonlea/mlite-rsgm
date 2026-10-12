import os

ADMIN_PHP = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/Admin.php"
with open(ADMIN_PHP, "r") as f:
    content = f.read()

target = """        $id = $_GET['id'] ?? '';
        $data = [];

        if (!empty($id)) {"""

replacement = """        $id = $_GET['id'] ?? '';
        $data = [
            'id' => '', 'no_rawat' => '', 'nm_pasien' => '', 'sumber_data' => '', 'kategori_pasien' => '',
            'tanggal_masuk' => '', 'waktu_masuk' => '', 'keluhan_utama' => '', 'riwayat_penyakit_sekarang' => '',
            'riwayat_penyakit_dahulu' => '', 'riwayat_penyakit_keluarga' => '', 'riwayat_alergi' => 'Tidak',
            'riwayat_alergi_keterangan' => '', 'obat_rutin' => '', 'penyakit_menular' => '',
            'penyakit_jantung' => 'Tidak Ada', 'hipertensi' => 'Tidak Ada', 'diabetes' => 'Tidak Ada',
            'haemophilia' => 'Tidak Ada', 'gangguan_ginjal' => 'Tidak Ada', 'gangguan_ginjal_ket' => '',
            'penyakit_sistemik_lainnya' => 'Tidak Ada', 'penyakit_sistemik_lainnya_ket' => '',
            'riwayat_operasi' => 'Tidak Ada', 'riwayat_operasi_ket' => '', 'anak_ke' => '', 'dari_saudara' => '',
            'cara_kelahiran' => '', 'umur_kelahiran' => '', 'kelainan_bawaan' => '', 'kelainan_bawaan_ket' => '',
            'imunisasi_bcg' => '', 'imunisasi_polio' => '', 'imunisasi_dpt' => '', 'imunisasi_campak' => '',
            'imunisasi_hepatitis_b' => '', 'imunisasi_lainnya' => '', 'tumbuh_gigi_pertama' => '',
            'mulai_bisa_duduk' => '', 'berjalan_sendiri' => '', 'mulai_bisa_bicara' => '', 'mulai_bisa_membaca' => '',
            'geriatri_penglihatan' => '', 'geriatri_pendengaran' => '', 'geriatri_berkemih' => '',
            'geriatri_daya_ingat' => '', 'geriatri_bicara' => '', 'geriatri_gigi_palsu' => '', 'geriatri_lainnya' => '',
            'keadaan_umum' => '', 'kesadaran' => '', 'tekanan_darah' => '', 'frekuensi_nadi' => '',
            'frekuensi_napas' => '', 'suhu' => '', 'berat_badan' => '', 'tinggi_badan' => '', 'golongan_darah' => '',
            'nyeri_keluhan' => 'Tidak', 'nyeri_lokasi' => '', 'nyeri_durasi' => '', 'nyeri_frekuensi' => '',
            'nyeri_karakteristik' => '', 'nyeri_menjalar' => 'Tidak', 'nyeri_menjalar_ket' => '',
            'nyeri_memberat' => '', 'nyeri_hilang' => '', 'nyeri_tindak_lanjut' => '', 'nyeri_konsul_ke' => '',
            'skala_nyeri' => '', 'penunjang_lab' => '', 'penunjang_rontgen' => '', 'penunjang_lainnya' => '',
            'diagnosis' => '', 'rencana_tindak_lanjut' => '', 'tanggal_ttd' => '', 'jam_ttd' => '', 'nip_dpjp' => ''
        ];

        if (!empty($id)) {"""

if target in content:
    content = content.replace(target, replacement)
    with open(ADMIN_PHP, "w") as f:
        f.write(content)
    print("Fixed getAmuform")
else:
    print("Target not found")

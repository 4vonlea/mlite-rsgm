import json

new_forms = [
    {
        "kode_form": "CPPT",
        "nama_form": "RM.GD 07 / RM.RI 05 - Catatan Perkembangan Pasien Terintegrasi (CPPT)",
        "kategori": "Ranap",
        "route_manage": "cpptmanage",
        "route_form": "cpptform",
        "route_cetak": "cpptcetak",
        "nama_tabel": "pemeriksaan_ranap",
        "target_aktor": "medis,paramedis"
    },
    {
        "kode_form": "RUJUKAN",
        "nama_form": "Lampiran - Formulir Rujukan Komunikasi Antar RS",
        "kategori": "Ranap",
        "route_manage": "formulirrujukanmanage",
        "route_form": "formulirrujukanform",
        "route_cetak": "formulirrujukancetak",
        "nama_tabel": "formulir_rujukan_rs",
        "target_aktor": "medis,paramedis"
    },
    {
        "kode_form": "GC_RANAP",
        "nama_form": "RM.RJ 02 - General Consent & Rincian Biaya Rawat Inap",
        "kategori": "Ranap",
        "route_manage": "generalconsentmanage",
        "route_form": "generalconsentform",
        "route_cetak": "generalconsentcetak",
        "nama_tabel": "general_consent_ranap",
        "target_aktor": "pasien,medis,paramedis"
    },
    {
        "kode_form": "KRITERIA_ICU",
        "nama_form": "Lampiran - Kriteria Masuk & Keluar Ruang Rawat Intensif (ICU)",
        "kategori": "Ranap",
        "route_manage": "kriteriaicumanage",
        "route_form": "kriteriaicuform",
        "route_cetak": "kriteriaicucetak",
        "nama_tabel": "kriteria_masuk_keluar_icu",
        "target_aktor": "medis"
    },
    {
        "kode_form": "RI04_KEP",
        "nama_form": "RM.RI 04 - Asesmen Keperawatan Rawat Inap",
        "kategori": "Ranap",
        "route_manage": "rikeperawatanmanage",
        "route_form": "rikeperawatanform",
        "route_cetak": "rikeperawatancetak",
        "nama_tabel": "ri04_asesmen_keperawatan_ranap",
        "target_aktor": "paramedis"
    },
    {
        "kode_form": "RI05A",
        "nama_form": "RM.RI 05A - Asesmen Awal Medis Rawat Inap Gigi & Mulut",
        "kategori": "Ranap",
        "route_manage": "rimedisgigimanage",
        "route_form": "rimedisgigiform",
        "route_cetak": "rimedisgigicetak",
        "nama_tabel": "ri05a_asesmen_medis_ranap_gigi",
        "target_aktor": "medis"
    },
    {
        "kode_form": "RI05C",
        "nama_form": "RM.RI 05C - Lembar Form Konsul / Alih Rawat DPJP",
        "kategori": "Ranap",
        "route_manage": "ri05cmanage",
        "route_form": "ri05cform",
        "route_cetak": "ri05ccetak",
        "nama_tabel": "ri05c_dokter_penanggung_jawab",
        "target_aktor": "medis"
    },
    {
        "kode_form": "RI06A",
        "nama_form": "RM.RI 06A - Asesmen Risiko Jatuh Pasien Dewasa & Geriatri",
        "kategori": "Ranap",
        "route_manage": "jatuhdewasamanage",
        "route_form": "jatuhdewasaform",
        "route_cetak": "jatuhdewasacetak",
        "nama_tabel": "ri06a_asesmen_jatuh_dewasa_geriatri",
        "target_aktor": "paramedis"
    },
    {
        "kode_form": "RI07_OBS",
        "nama_form": "RM.RI 07 - Lembar Observasi Pasien Rawat Inap",
        "kategori": "Ranap",
        "route_manage": "riobservasimanage",
        "route_form": "riobservasiform",
        "route_cetak": "riobservasicetak",
        "nama_tabel": "ri07_observasi_pasien",
        "target_aktor": "paramedis"
    },
    {
        "kode_form": "RINGKASAN_KLINIK",
        "nama_form": "Lampiran - Ringkasan Riwayat Klinik (Evaluasi APS / FKTP)",
        "kategori": "Rajal",
        "route_manage": "ringkasanriwayatklinikmanage",
        "route_form": "ringkasanriwayatklinikform",
        "route_cetak": "ringkasanriwayatklinikcetak",
        "nama_tabel": "ringkasan_riwayat_klinik",
        "target_aktor": "medis"
    },
    {
        "kode_form": "RJ_JATUH_ANAK",
        "nama_form": "Lampiran - Asesmen Risiko Jatuh & Nyeri Anak",
        "kategori": "Rajal",
        "route_manage": "jatuhanakmanage",
        "route_form": "jatuhanakform",
        "route_cetak": "jatuhanakcetak",
        "nama_tabel": "rj_asesmen_jatuh_nyeri_anak",
        "target_aktor": "paramedis"
    },
    {
        "kode_form": "PENGANTAR_RANAP",
        "nama_form": "Lampiran - Surat Pengantar Rawat Inap (Admisi)",
        "kategori": "Ranap",
        "route_manage": "pengantarranapmanage",
        "route_form": "pengantarranapform",
        "route_cetak": "pengantarranapcetak",
        "nama_tabel": "surat_pengantar_ranap",
        "target_aktor": "medis"
    },
    {
        "kode_form": "JAMINAN_BIAYA",
        "nama_form": "Lampiran - Surat Pernyataan Jaminan Pembiayaan",
        "kategori": "Ranap",
        "route_manage": "jaminanbiayamanage",
        "route_form": "jaminanbiayaform",
        "route_cetak": "jaminanbiayacetak",
        "nama_tabel": "surat_pernyataan_jaminan_biaya",
        "target_aktor": "pasien,medis"
    }
]

j_str = json.dumps(new_forms)
php_code = """<?php
define("BASE_DIR", __DIR__);
require_once "config.php";
$pdo = new PDO("mysql:host=" . DBHOST . ";port=" . DBPORT . ";dbname=" . DBNAME, DBUSER, DBPASS);

$json = '""" + j_str.replace("'", "\\'") + """';
$forms = json_decode($json, true);

$inserted = 0;
foreach ($forms as $f) {
    $stmt = $pdo->prepare("SELECT id FROM mlite_rme_form WHERE kode_form = ?");
    $stmt->execute([$f['kode_form']]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO mlite_rme_form (kode_form, nama_form, kategori, route_manage, route_form, route_cetak, nama_tabel, status, target_aktor) VALUES (?, ?, ?, ?, ?, ?, ?, '1', ?)");
        $ins->execute([
            $f['kode_form'],
            $f['nama_form'],
            $f['kategori'],
            $f['route_manage'],
            $f['route_form'],
            $f['route_cetak'],
            $f['nama_tabel'],
            $f['target_aktor']
        ]);
        $inserted++;
        echo "Inserted: " . $f['kode_form'] . "\n";
    } else {
        echo "Exists: " . $f['kode_form'] . "\n";
    }
}
echo "Total inserted: $inserted\n";
"""

with open("scratch/insert_13_forms.php", "w") as f:
    f.write(php_code)

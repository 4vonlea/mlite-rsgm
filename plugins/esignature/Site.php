<?php
namespace Plugins\Esignature;

use Systems\SiteModule;

class Site extends SiteModule
{
    public function routes()
    {
        $this->route('esignature/verify/(:any)', 'getVerify');
    }

    public function getVerify($hash)
    {
        $signature = $this->db('mlite_esignatures')->where('signature_hash', $hash)->oneArray();
        
        $logo = $this->settings->get('settings.logo');
        $nama_instansi = $this->settings->get('settings.nama_instansi');

        if ($signature) {
             $signature['jenis_dokumen'] = 'Dokumen Elektronik';
             $signature['nama_dokumen'] = 'Dokumen Sistem';
             
             if ($signature['ref_type'] == 'soap_ralan') {
                 $signature['jenis_dokumen'] = 'Rekam Medis Elektronik';
                 $signature['nama_dokumen'] = 'Catatan Perkembangan Pasien Terintegrasi (CPPT) Rawat Jalan';
             } elseif ($signature['ref_type'] == 'radiologi_hasil') {
                 $signature['jenis_dokumen'] = 'Rekam Medis Elektronik';
                 $signature['nama_dokumen'] = 'Hasil Pemeriksaan Radiologi';
             } elseif ($signature['ref_type'] == 'soap_ranap') {
                 $signature['jenis_dokumen'] = 'Rekam Medis Elektronik';
                 $signature['nama_dokumen'] = 'Catatan Perkembangan Pasien Terintegrasi (CPPT) Rawat Inap';
             }
             
             $no_rawat_clean = '';
             if ($signature['ref_type'] == 'radiologi_hasil') {
                 $parts = explode('_', $signature['ref_id']);
                 $no_rawat_clean = isset($parts[1]) ? $parts[1] : '';
             } else if (in_array($signature['ref_type'], ['soap_ralan', 'soap_ranap'])) {
                 $no_rawat_clean = substr($signature['ref_id'], 0, -14);
             }
             
             if ($no_rawat_clean) {
                 $pasien = $this->db('reg_periksa')
                    ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
                    ->where("REPLACE(reg_periksa.no_rawat, '/', '')", $no_rawat_clean)
                    ->oneArray();
                 if ($pasien) {
                     $norm = $pasien['no_rkm_medis'];
                     if (strlen($norm) > 2) {
                         $masked_rm = substr($norm, 0, 1) . str_repeat('*', strlen($norm) - 2) . substr($norm, -1);
                     } else {
                         $masked_rm = '***';
                     }
                     $signature['nama_dokumen'] .= ' (No. RM: ' . $masked_rm . ')';
                 }
             }
             
             exit ($this->draw('verify.html', [
                 'signature' => $signature, 
                 'valid' => true,
                 'logo' => $logo,
                 'nama_instansi' => $nama_instansi
             ]));
        } else {
             exit($this->draw('verify.html', [
                 'valid' => false,
                 'logo' => $logo,
                 'nama_instansi' => $nama_instansi
             ]));
        }
    }
}

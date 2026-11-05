<?php
namespace Plugins\Kasir_Rawat_Jalan;

use Systems\AdminModule;
use Systems\Lib\QRCode;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Admin extends AdminModule
{
    public $assign = [];

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
            'Kasir'    => 'shift',
            'Laporan'  => 'report',
        ];
    }

    public function apiBillingList()
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'kasir_rawat_jalan')) {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to access this resource']);
            exit;
        }
        
        $page = $_GET['page'] ?? 1;
        $per_page = $_GET['per_page'] ?? 10;
        $offset = ($page - 1) * $per_page;
        $search = $_GET['s'] ?? '';
        $tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-d');
        $tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');

        $query = $this->db('mlite_billing')
            ->join('reg_periksa', 'reg_periksa.no_rawat = mlite_billing.no_rawat')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
            ->where('mlite_billing.tgl_billing', '>=', $tgl_awal)
            ->where('mlite_billing.tgl_billing', '<=', $tgl_akhir)
            ->like('mlite_billing.kd_billing', 'RJ%');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('reg_periksa.no_rawat', 'LIKE', "%$search%")
                  ->orWhere('pasien.nm_pasien', 'LIKE', "%$search%")
                  ->orWhere('pasien.no_rkm_medis', 'LIKE', "%$search%");
            });
        }

        $total = $query->count();
        $data = $query->select('mlite_billing.*')
            ->select('reg_periksa.no_rkm_medis')
            ->select('reg_periksa.status_bayar')
            ->select('pasien.nm_pasien')
            ->select('poliklinik.nm_poli')
            ->select('dokter.nm_dokter')
            ->select('penjab.png_jawab')
            ->offset($offset)
            ->limit($per_page)
            ->desc('mlite_billing.tgl_billing')
            ->desc('mlite_billing.jam_billing')
            ->toArray();

        foreach ($data as &$row) {
             $row['total_tagihan'] = $row['jumlah_harus_bayar'];
        }
        unset($row);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => htmlspecialchars_array($data),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page
        ]);
        exit;
    }

    public function apiBillingDetail($no_rawat)
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'kasir_rawat_jalan')) {
             echo json_encode(['status' => 'error', 'message' => 'You do not have permission to access this resource']);
             exit;
        }
        $no_rawat = revertNorawat($no_rawat);

        // Basic Info
        $reg_periksa = $this->db('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
            ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
            ->where('reg_periksa.no_rawat', $no_rawat)
            ->oneArray();

        if (!$reg_periksa) {
            echo json_encode(['status' => 'error', 'message' => 'Data not found']);
            exit;
        }

        $details = [];
        $total_biaya = 0;

        // 1. Registrasi
        $poliklinik = $this->db('poliklinik')->where('kd_poli', $reg_periksa['kd_poli'])->oneArray();
        $biaya_reg = ($reg_periksa['stts_daftar'] == 'Lama') ? $poliklinik['registrasilama'] : $poliklinik['registrasi'];
        $details[] = [
            'kategori' => 'Registrasi',
            'nama' => 'Biaya Registrasi',
            'biaya' => $biaya_reg,
            'jumlah' => 1,
            'subtotal' => $biaya_reg
        ];
        $total_biaya += $biaya_reg;

        // 2. Tindakan Dokter
        $tindakan_dr = $this->db('rawat_jl_dr')
            ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')
            ->where('no_rawat', $no_rawat)->toArray();
        foreach ($tindakan_dr as $t) {
            $is_coass = ($t['stts_bayar'] == 'Suspen');
            $details[] = [
                'kategori' => 'Tindakan Dokter',
                'nama' => $t['nm_perawatan'] . ($is_coass ? ' (Tidak Di-claim)' : ''),
                'biaya' => $is_coass ? 0 : $t['biaya_rawat'],
                'biaya_asli' => $t['biaya_rawat'],
                'is_coass' => $is_coass,
                'jumlah' => 1,
                'subtotal' => $is_coass ? 0 : $t['biaya_rawat'],
                'kd_jenis_prw' => $t['kd_jenis_prw'],
                'tgl_perawatan' => $t['tgl_perawatan'],
                'jam_rawat' => $t['jam_rawat'],
                'provider' => 'rawat_jl_dr',
                'type' => 'tindakan'
            ];
            if (!$is_coass) {
                $total_biaya += $t['biaya_rawat'];
            }
        }

        // 3. Tindakan Perawat
        $tindakan_pr = $this->db('rawat_jl_pr')
            ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_pr.kd_jenis_prw')
            ->where('no_rawat', $no_rawat)->toArray();
        foreach ($tindakan_pr as $t) {
            $is_coass = ($t['stts_bayar'] == 'Suspen');
            $details[] = [
                'kategori' => 'Tindakan Perawat',
                'nama' => $t['nm_perawatan'] . ($is_coass ? ' (Tidak Di-claim)' : ''),
                'biaya' => $is_coass ? 0 : $t['biaya_rawat'],
                'biaya_asli' => $t['biaya_rawat'],
                'is_coass' => $is_coass,
                'jumlah' => 1,
                'subtotal' => $is_coass ? 0 : $t['biaya_rawat'],
                'kd_jenis_prw' => $t['kd_jenis_prw'],
                'tgl_perawatan' => $t['tgl_perawatan'],
                'jam_rawat' => $t['jam_rawat'],
                'provider' => 'rawat_jl_pr',
                'type' => 'tindakan'
            ];
            if (!$is_coass) {
                $total_biaya += $t['biaya_rawat'];
            }
        }

        // 4. Tindakan Dokter & Perawat
        $tindakan_drpr = $this->db('rawat_jl_drpr')
            ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_drpr.kd_jenis_prw')
            ->where('no_rawat', $no_rawat)->toArray();
        foreach ($tindakan_drpr as $t) {
            $is_coass = ($t['stts_bayar'] == 'Suspen');
            $details[] = [
                'kategori' => 'Tindakan Dokter & Perawat',
                'nama' => $t['nm_perawatan'] . ($is_coass ? ' (Tidak Di-claim)' : ''),
                'biaya' => $is_coass ? 0 : $t['biaya_rawat'],
                'biaya_asli' => $t['biaya_rawat'],
                'is_coass' => $is_coass,
                'jumlah' => 1,
                'subtotal' => $is_coass ? 0 : $t['biaya_rawat'],
                'kd_jenis_prw' => $t['kd_jenis_prw'],
                'tgl_perawatan' => $t['tgl_perawatan'],
                'jam_rawat' => $t['jam_rawat'],
                'provider' => 'rawat_jl_drpr',
                'type' => 'tindakan'
            ];
            if (!$is_coass) {
                $total_biaya += $t['biaya_rawat'];
            }
        }

        // 5. Obat & BHP
        $obat = $this->db('detail_pemberian_obat')
            ->join('databarang', 'databarang.kode_brng = detail_pemberian_obat.kode_brng')
            ->where('no_rawat', $no_rawat)
            ->where('detail_pemberian_obat.status', 'Ralan')
            ->toArray();
            
        foreach ($obat as $o) {
            $details[] = [
                'kategori' => 'Obat & BHP',
                'nama' => $o['nama_brng'],
                'biaya' => $o['biaya_obat'],
                'jumlah' => $o['jml'],
                'subtotal' => $o['total'] + $o['embalase'] + $o['tuslah'],
                'kode_brng' => $o['kode_brng'],
                'tgl_peresepan' => $o['tgl_perawatan'],
                'jam_peresepan' => $o['jam'],
                'kd_bangsal' => $o['kd_bangsal'],
                'type' => 'obat'
            ];
            $total_biaya += ($o['total'] + $o['embalase'] + $o['tuslah']);
        }

        // 6. Laboratorium
        $lab = $this->db('periksa_lab')
            ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw = periksa_lab.kd_jenis_prw')
            ->where('no_rawat', $no_rawat)
            ->where('periksa_lab.status', 'Ralan')
            ->toArray();
        foreach ($lab as $l) {
            $details[] = [
                'kategori' => 'Laboratorium',
                'nama' => $l['nm_perawatan'],
                'biaya' => $l['biaya'],
                'jumlah' => 1,
                'subtotal' => $l['biaya'],
                'kd_jenis_prw' => $l['kd_jenis_prw'],
                'tgl_perawatan' => $l['tgl_periksa'],
                'jam_rawat' => $l['jam'],
                'type' => 'lab'
            ];
            $total_biaya += $l['biaya'];
        }

        // 7. Radiologi
        $rad = $this->db('periksa_radiologi')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw = periksa_radiologi.kd_jenis_prw')
            ->where('no_rawat', $no_rawat)
            ->where('periksa_radiologi.status', 'Ralan')
            ->toArray();
        foreach ($rad as $r) {
            $details[] = [
                'kategori' => 'Radiologi',
                'nama' => $r['nm_perawatan'],
                'biaya' => $r['biaya'],
                'jumlah' => 1,
                'subtotal' => $r['biaya'],
                'kd_jenis_prw' => $r['kd_jenis_prw'],
                'tgl_perawatan' => $r['tgl_periksa'],
                'jam_rawat' => $r['jam'],
                'type' => 'rad'
            ];
            $total_biaya += $r['biaya'];
        }
        
        // 8. Tambahan Biaya
        $tambahan = $this->db('tambahan_biaya')->where('no_rawat', $no_rawat)->toArray();
        foreach ($tambahan as $t) {
             $details[] = [
                'kategori' => 'Tambahan',
                'nama' => $t['nama_biaya'],
                'biaya' => $t['besar_biaya'],
                'jumlah' => 1,
                'subtotal' => $t['besar_biaya']
            ];
            $total_biaya += $t['besar_biaya'];
        }

        // 9. Obat Pulang
        $resep_pulang = $this->db('resep_pulang')
            ->join('databarang', 'databarang.kode_brng = resep_pulang.kode_brng')
            ->where('resep_pulang.no_rawat', $no_rawat)
            ->toArray();
        foreach ($resep_pulang as $r) {
            $details[] = [
                'kategori' => 'Obat Pulang',
                'nama' => $r['nama_brng'],
                'biaya' => $r['harga'],
                'jumlah' => $r['jml_barang'],
                'subtotal' => $r['total'],
                'kode_brng' => $r['kode_brng'],
                'tanggal' => $r['tanggal'],
                'jam' => $r['jam'],
                'type' => 'obat_pulang'
            ];
            $total_biaya += $r['total'];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'registrasi' => $reg_periksa,
                'details' => $details,
                'total' => $total_biaya
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function apiBilling($no_rawat)
    {
        $username = $this->core->checkAuth('GET');
        if (!$this->core->checkPermission($username, 'can_read', 'kasir_rawat_jalan')) {
             echo json_encode(['status' => 'error', 'message' => 'You do not have permission to access this resource']);
             exit;
        }
        $no_rawat = revertNorawat($no_rawat);
        $all = isset($_GET['all']) && $_GET['all'] == 'true';

        $query = $this->db('mlite_billing')
            ->where('no_rawat', $no_rawat)
            ->like('kd_billing', 'RJ%');

        if (isset($_GET['tgl_awal']) && isset($_GET['tgl_akhir'])) {
            $query->where('tgl_billing', '>=', $_GET['tgl_awal'])
                  ->where('tgl_billing', '<=', $_GET['tgl_akhir']);
        }

        if ($all) {
            $billing = $query
                ->desc('tgl_billing')
                ->desc('jam_billing')
                ->toArray();
        } else {
            $billing = $query
                ->desc('tgl_billing')
                ->desc('jam_billing')
                ->oneArray();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $billing
        ]);
        exit;
    }

    private function _checkSchema()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        try {
            $this->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `mlite_billing_rincian` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `kd_billing` varchar(100) NOT NULL,
              `no_rawat` varchar(17) NOT NULL,
              `kategori` varchar(50) NOT NULL,
              `nama` varchar(100) NOT NULL,
              `tagihan` double NOT NULL DEFAULT 0,
              `bayar` double NOT NULL DEFAULT 0,
              `sisa` double NOT NULL DEFAULT 0,
              `tgl_billing` date NOT NULL,
              `jam_billing` time NOT NULL,
              PRIMARY KEY (`id`),
              KEY `kd_billing` (`kd_billing`),
              KEY `no_rawat` (`no_rawat`),
              KEY `kategori` (`kategori`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        } catch (\Exception $e) {
            // fail-safe
        }
    }

    public function anyManage()
    {
        $this->_checkSchema();
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';
        $status_bayar = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        if(isset($_POST['status_bayar'])) {
          $status_bayar = $_POST['status_bayar'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $cek_laboratorium = $this->db('mlite_modules')->where('dir', 'laboratorium')->oneArray();
        $cek_radiologi = $this->db('mlite_modules')->where('dir', 'radiologi')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_bayar);
        return $this->draw('manage.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim), 'cek_laboratorium' => htmlspecialchars_array($cek_laboratorium), 'cek_radiologi' => htmlspecialchars_array($cek_radiologi)]);
    }

    public function anyDisplay()
    {
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $cek_laboratorium = $this->db('mlite_modules')->where('dir', 'laboratorium')->oneArray();
        $cek_radiologi = $this->db('mlite_modules')->where('dir', 'radiologi')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa);
        echo $this->draw('display.html', ['rawat_jalan' => htmlspecialchars_array($this->assign), 'cek_vclaim' => htmlspecialchars_array($cek_vclaim), 'cek_laboratorium' => htmlspecialchars_array($cek_laboratorium), 'cek_radiologi' => htmlspecialchars_array($cek_radiologi)]);
        exit();
    }

    public function _Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa='')
    {
        $this->_addHeaderFiles();

        $this->assign['kd_billing'] = 'RJ.'.date('d.m.Y.H.i.s');
        $this->assign['poliklinik']     = $this->db('poliklinik')->where('status', '1')->toArray();
        $this->assign['dokter']         = $this->db('dokter')->where('status', '1')->toArray();
        $this->assign['penjab']       = $this->db('penjab')->where('status', '1')->toArray();
        $this->assign['no_rawat'] = '';
        $this->assign['no_reg']     = '';
        $this->assign['tgl_registrasi']= date('Y-m-d');
        $this->assign['jam_reg']= date('H:i:s');
        $this->assign['input_kasir'] = $this->settings('settings', 'input_kasir');

        $sql = "SELECT reg_periksa.*,
            pasien.*,
            dokter.*,
            poliklinik.*,
            penjab.*
          FROM reg_periksa, pasien, dokter, poliklinik, penjab
          WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          AND reg_periksa.tgl_registrasi BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir'
          AND reg_periksa.kd_dokter = dokter.kd_dokter
          AND reg_periksa.kd_poli = poliklinik.kd_poli
          AND reg_periksa.kd_pj = penjab.kd_pj";

        if($status_periksa == 'belum') {
          $sql .= " AND reg_periksa.stts = 'Belum'";
        }
        if($status_periksa == 'selesai') {
          $sql .= " AND reg_periksa.stts = 'Sudah'";
        }
        if($status_periksa == 'lunas') {
          $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar'";
        }

        $stmt = $this->db()->pdo()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assign['list'] = [];
        $pdo = $this->db()->pdo();
        foreach ($rows as $row) {
          $billings = $this->db('mlite_billing')->where('no_rawat', $row['no_rawat'])->like('kd_billing', 'RJ%')->toArray();
          $total_bayar = 0;
          foreach ($billings as $b) {
            $total_bayar += floatval($b['jumlah_bayar']);
          }
          $row['total_bayar'] = $total_bayar;
          $row['jml_faktur'] = count($billings);
          $row['kd_billing'] = 'RJ.'.date('d.m.Y.H.i.s');
          $row['tgl_billing'] = date('Y-m-d H:i');
          $this->assign['list'][] = $row;
        }

    }

    public function postSaveDetail()
    {
      if($_POST['kat'] == 'tindakan') {
        $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
        if($_POST['provider'] == 'rawat_jl_dr') {
          for ($i = 0; $i < $_POST['jml_tindakan']; $i++) {
            $this->db('rawat_jl_dr')->save([
              'no_rawat' => $_POST['no_rawat'],
              'kd_jenis_prw' => $_POST['kd_jenis_prw'],
              'kd_dokter' => $_POST['kode_provider'],
              'tgl_perawatan' => $_POST['tgl_perawatan'],
              'jam_rawat' => date('H:i:s', strtotime($_POST['jam_rawat']. ' +'.$i.'0 seconds')),
              'material' => $jns_perawatan['material'],
              'bhp' => $jns_perawatan['bhp'],
              'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
              'kso' => $jns_perawatan['kso'],
              'menejemen' => $jns_perawatan['menejemen'],
              'biaya_rawat' => $jns_perawatan['total_byrdr'],
              'stts_bayar' => 'Belum'
            ]);
          }
        }
        if($_POST['provider'] == 'rawat_jl_pr') {
          for ($i = 0; $i < $_POST['jml_tindakan']; $i++) {
            $this->db('rawat_jl_pr')->save([
              'no_rawat' => $_POST['no_rawat'],
              'kd_jenis_prw' => $_POST['kd_jenis_prw'],
              'nip' => $_POST['kode_provider2'],
              'tgl_perawatan' => $_POST['tgl_perawatan'],
              'jam_rawat' => date('H:i:s', strtotime($_POST['jam_rawat']. ' +'.$i.'0 seconds')),
              'material' => $jns_perawatan['material'],
              'bhp' => $jns_perawatan['bhp'],
              'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
              'kso' => $jns_perawatan['kso'],
              'menejemen' => $jns_perawatan['menejemen'],
              'biaya_rawat' => $jns_perawatan['total_byrpr'],
              'stts_bayar' => 'Belum'
            ]);
          }
        }
        if($_POST['provider'] == 'rawat_jl_drpr') {
          for ($i = 0; $i < $_POST['jml_tindakan']; $i++) {
            $this->db('rawat_jl_drpr')->save([
              'no_rawat' => $_POST['no_rawat'],
              'kd_jenis_prw' => $_POST['kd_jenis_prw'],
              'kd_dokter' => $_POST['kode_provider'],
              'nip' => $_POST['kode_provider2'],
              'tgl_perawatan' => $_POST['tgl_perawatan'],
              'jam_rawat' => date('H:i:s', strtotime($_POST['jam_rawat']. ' +'.$i.'0 seconds')),
              'material' => $jns_perawatan['material'],
              'bhp' => $jns_perawatan['bhp'],
              'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
              'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
              'kso' => $jns_perawatan['kso'],
              'menejemen' => $jns_perawatan['menejemen'],
              'biaya_rawat' => $jns_perawatan['total_byrdrpr'],
              'stts_bayar' => 'Belum'
            ]);
          }
        }
      }
      if($_POST['kat'] == 'obat') {

        $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kd_jenis_prw'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
        $get_databarang = $this->db('databarang')->where('kode_brng', $_POST['kd_jenis_prw'])->oneArray();

        $this->db('gudangbarang')
          ->where('kode_brng', $_POST['kd_jenis_prw'])
          ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
          ->update([
            'stok' => $get_gudangbarang['stok'] - $_POST['jml']
          ]);

        $this->db('riwayat_barang_medis')
          ->save([
            'kode_brng' => $_POST['kd_jenis_prw'],
            'stok_awal' => $get_gudangbarang['stok'],
            'masuk' => '0',
            'keluar' => $_POST['jml'],
            'stok_akhir' => $get_gudangbarang['stok'] - $_POST['jml'],
            'posisi' => 'Pemberian Obat',
            'tanggal' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'petugas' => $this->core->getUserInfo('fullname', null, true),
            'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
            'status' => 'Simpan',
            'no_batch' => $get_gudangbarang['no_batch'],
            'no_faktur' => $get_gudangbarang['no_faktur'],
            'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
          ]);

        $this->db('detail_pemberian_obat')
          ->save([
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'no_rawat' => $_POST['no_rawat'],
            'kode_brng' => $_POST['kd_jenis_prw'],
            'h_beli' => $get_databarang['h_beli'],
            'biaya_obat' => $_POST['biaya'],
            'jml' => $_POST['jml'],
            'embalase' => '0',
            'tuslah' => '0',
            'total' => $_POST['biaya'] * $_POST['jml'],
            'status' => 'Ralan',
            'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
            'no_batch' => $get_gudangbarang['no_batch'],
            'no_faktur' => $get_gudangbarang['no_faktur']
          ]);

        $this->db('aturan_pakai')
          ->save([
            'tgl_perawatan' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'no_rawat' => $_POST['no_rawat'],
            'kode_brng' => $_POST['kd_jenis_prw'],
            'aturan' => $_POST['aturan_pakai']
          ]);

      }

      if($_POST['kat'] == 'laboratorium') {
        $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
        $this->db('periksa_lab')
          ->save([
            'no_rawat' => $_POST['no_rawat'],
            'nip' => $_POST['kode_provider2'],
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'tgl_periksa' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'dokter_perujuk' => $_POST['kode_provider'],
            'bagian_rs' => $jns_perawatan['bagian_rs'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
            'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
            'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya' => $jns_perawatan['total_byr'],
            'kd_dokter' => $this->settings->get('settings.pj_laboratorium'),
            'status' => 'Ralan'
          ]);
      }

      if($_POST['kat'] == 'radiologi') {
        $jns_perawatan = $this->db('jns_perawatan_radiologi')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
        $this->db('periksa_radiologi')
          ->save([
            'no_rawat' => $_POST['no_rawat'],
            'nip' => $_POST['kode_provider2'],
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'tgl_periksa' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'dokter_perujuk' => $_POST['kode_provider'],
            'bagian_rs' => $jns_perawatan['bagian_rs'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
            'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
            'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya' => $jns_perawatan['total_byr'],
            'kd_dokter' => $this->settings->get('settings.pj_radiologi'),
            'status' => 'Ralan'
          ]);
      }

      if (!empty($_POST['no_rawat'])) {
        $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->update(['status_bayar' => 'Belum Bayar']);
      }

      exit();
    }

    public function postHapusDetail()
    {
      if($_POST['provider'] == 'rawat_jl_dr') {
        $this->db('rawat_jl_dr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      if($_POST['provider'] == 'rawat_jl_pr') {
        $this->db('rawat_jl_pr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      if($_POST['provider'] == 'rawat_jl_drpr') {
        $this->db('rawat_jl_drpr')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_perawatan', $_POST['tgl_perawatan'])
        ->where('jam_rawat', $_POST['jam_rawat'])
        ->delete();
      }
      exit();
    }

    public function anyToggleSuspen()
    {
      return $this->postToggleSuspen();
    }

    public function postToggleSuspen()
    {
      header('Content-Type: application/json');
      $table = $_POST['provider'] ?? '';
      if (in_array($table, ['rawat_jl_dr', 'rawat_jl_pr', 'rawat_jl_drpr'])) {
        $q = $this->db($table)
          ->where('no_rawat', $_POST['no_rawat'])
          ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
          ->where('tgl_perawatan', $_POST['tgl_perawatan'])
          ->where('jam_rawat', $_POST['jam_rawat']);
        
        if (!empty($_POST['kd_dokter']) && in_array($table, ['rawat_jl_dr', 'rawat_jl_drpr'])) {
          $q->where('kd_dokter', $_POST['kd_dokter']);
        }
        if (!empty($_POST['nip']) && in_array($table, ['rawat_jl_pr', 'rawat_jl_drpr'])) {
          $q->where('nip', $_POST['nip']);
        }
        $row = $q->oneArray();

        if ($row) {
          $new_stts = ($row['stts_bayar'] == 'Suspen') ? 'Belum' : 'Suspen';
          $update_q = $this->db($table)
            ->where('no_rawat', $_POST['no_rawat'])
            ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
            ->where('tgl_perawatan', $_POST['tgl_perawatan'])
            ->where('jam_rawat', $_POST['jam_rawat']);
          if (!empty($_POST['kd_dokter']) && in_array($table, ['rawat_jl_dr', 'rawat_jl_drpr'])) {
            $update_q->where('kd_dokter', $_POST['kd_dokter']);
          }
          if (!empty($_POST['nip']) && in_array($table, ['rawat_jl_pr', 'rawat_jl_drpr'])) {
            $update_q->where('nip', $_POST['nip']);
          }
          $update_q->update(['stts_bayar' => $new_stts]);

          echo json_encode(['status' => 'success', 'stts_bayar' => $new_stts]);
          exit();
        }
      }
      echo json_encode(['status' => 'error', 'message' => 'Data tindakan tidak ditemukan']);
      exit();
    }

    public function getCoassKategori($no_rawat)
    {
      $setting = $this->db('mlite_settings')
        ->where('module', 'kasir_coass')
        ->where('field', $no_rawat)
        ->oneArray();
      $cats = [];
      if ($setting && !empty($setting['value'])) {
        $decoded = json_decode($setting['value'], true);
        if (is_array($decoded)) $cats = $decoded;
      }
      
      // Fallback untuk tindakan jika di rawat_jl_dr / pr / drpr sudah berstatus Suspen
      $has_suspen = $this->db('rawat_jl_dr')->where('no_rawat', $no_rawat)->where('stts_bayar', 'Suspen')->oneArray()
        || $this->db('rawat_jl_pr')->where('no_rawat', $no_rawat)->where('stts_bayar', 'Suspen')->oneArray()
        || $this->db('rawat_jl_drpr')->where('no_rawat', $no_rawat)->where('stts_bayar', 'Suspen')->oneArray();
      if ($has_suspen && !isset($cats['tindakan'])) {
        $cats['tindakan'] = true;
      }
      return $cats;
    }

    public function setCoassKategori($no_rawat, array $cats)
    {
      $exists = $this->db('mlite_settings')
        ->where('module', 'kasir_coass')
        ->where('field', $no_rawat)
        ->oneArray();
      if ($exists) {
        $this->db('mlite_settings')
          ->where('module', 'kasir_coass')
          ->where('field', $no_rawat)
          ->update(['value' => json_encode($cats)]);
      } else {
        $this->db('mlite_settings')->save([
          'module' => 'kasir_coass',
          'field' => $no_rawat,
          'value' => json_encode($cats)
        ]);
      }
    }

    public function anyToggleSubCoass()
    {
      return $this->postToggleSubCoass();
    }

    public function postToggleSubCoass()
    {
      header('Content-Type: application/json');
      $no_rawat = $_POST['no_rawat'] ?? '';
      $kategori = $_POST['kategori'] ?? '';

      if (empty($no_rawat) || empty($kategori)) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
        exit();
      }

      $coass_cats = $this->getCoassKategori($no_rawat);
      $is_currently_coass = !empty($coass_cats[$kategori]);

      if ($is_currently_coass) {
        unset($coass_cats[$kategori]);
        $new_state = false;
      } else {
        $coass_cats[$kategori] = true;
        $new_state = true;
      }

      $this->setCoassKategori($no_rawat, $coass_cats);

      // Jika kategori adalah tindakan, sinkronkan juga tabel Khanza (rawat_jl_dr, rawat_jl_pr, rawat_jl_drpr)
      if ($kategori === 'tindakan') {
        $stts = $new_state ? 'Suspen' : 'Belum';
        $this->db('rawat_jl_dr')->where('no_rawat', $no_rawat)->update(['stts_bayar' => $stts]);
        $this->db('rawat_jl_pr')->where('no_rawat', $no_rawat)->update(['stts_bayar' => $stts]);
        $this->db('rawat_jl_drpr')->where('no_rawat', $no_rawat)->update(['stts_bayar' => $stts]);
      }

      // Update status_bayar reg_periksa
      $pdo = $this->db()->pdo();
      $poliklinik = $this->db('poliklinik')->join('reg_periksa', 'reg_periksa.kd_poli=poliklinik.kd_poli')->where('reg_periksa.no_rawat', $no_rawat)->oneArray();
      $biaya_reg = !empty($coass_cats['pendaftaran']) ? 0 : floatval(($poliklinik['stts_daftar'] ?? '') == 'Lama' ? ($poliklinik['registrasilama'] ?? 0) : ($poliklinik['registrasi'] ?? 0));

      $qDr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_dr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
      $qDr->execute([$no_rawat]);
      $total_dr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDr->fetchColumn());

      $qPr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_pr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
      $qPr->execute([$no_rawat]);
      $total_pr = !empty($coass_cats['tindakan']) ? 0 : floatval($qPr->fetchColumn());

      $qDrpr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_drpr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
      $qDrpr->execute([$no_rawat]);
      $total_drpr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDrpr->fetchColumn());

      $qObat = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM detail_pemberian_obat WHERE no_rawat = ? AND status = 'Ralan'");
      $qObat->execute([$no_rawat]);
      $total_obat = !empty($coass_cats['obat']) ? 0 : floatval($qObat->fetchColumn());

      $qLab = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_lab WHERE no_rawat = ? AND status = 'Ralan'");
      $qLab->execute([$no_rawat]);
      $total_lab = !empty($coass_cats['laboratorium']) ? 0 : floatval($qLab->fetchColumn());

      $qRad = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_radiologi WHERE no_rawat = ? AND status = 'Ralan'");
      $qRad->execute([$no_rawat]);
      $total_rad = !empty($coass_cats['radiologi']) ? 0 : floatval($qRad->fetchColumn());

      $qTambahan = $pdo->prepare("SELECT COALESCE(SUM(besar_biaya), 0) FROM tambahan_biaya WHERE no_rawat = ?");
      $qTambahan->execute([$no_rawat]);
      $total_tambahan = !empty($coass_cats['tambahan']) ? 0 : floatval($qTambahan->fetchColumn());

      $total_layanan = $biaya_reg + $total_dr + $total_pr + $total_drpr + $total_obat + $total_lab + $total_rad + $total_tambahan;

      $qByr = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM mlite_billing WHERE no_rawat = ? AND kd_billing LIKE 'RJ%'");
      $qByr->execute([$no_rawat]);
      $total_terbayar = floatval($qByr->fetchColumn());

      $status_bayar = ($total_terbayar >= $total_layanan) ? 'Sudah Bayar' : 'Belum Bayar';
      $this->db('reg_periksa')->where('no_rawat', $no_rawat)->update(['status_bayar' => $status_bayar]);

      echo json_encode([
        'status' => 'success',
        'kategori' => $kategori,
        'is_coass' => $new_state
      ]);
      exit();
    }

    public function postHapusLaboratorium()
    {
      $this->db('periksa_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
      ->where('tgl_periksa', $_POST['tgl_perawatan'])
      ->where('jam', $_POST['jam_rawat'])
      ->where('status', 'Ralan')
      ->delete();
      exit();
    }

    public function postHapusRadiologi()
    {
      $this->db('periksa_radiologi')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
      ->where('tgl_periksa', $_POST['tgl_perawatan'])
      ->where('jam', $_POST['jam_rawat'])
      ->where('status', 'Ralan')
      ->delete();
      exit();
    }

    public function postHapusObat()
    {
      $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $_POST['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();

      $this->db('gudangbarang')
        ->where('kode_brng', $_POST['kode_brng'])
        ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
        ->update([
          'stok' => $get_gudangbarang['stok'] + $_POST['jml']
        ]);

      $this->db('riwayat_barang_medis')
        ->save([
          'kode_brng' => $_POST['kode_brng'],
          'stok_awal' => $get_gudangbarang['stok'],
          'masuk' => $_POST['jml'],
          'keluar' => '0',
          'stok_akhir' => $get_gudangbarang['stok'] + $_POST['jml'],
          'posisi' => 'Pemberian Obat',
          'tanggal' => $_POST['tgl_peresepan'],
          'jam' => $_POST['jam_peresepan'],
          'petugas' => $this->core->getUserInfo('fullname', null, true),
          'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
          'status' => 'Hapus',
          'no_batch' => $get_gudangbarang['no_batch'],
          'no_faktur' => $get_gudangbarang['no_faktur'],
          'keterangan' => $_POST['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $_POST['no_rawat']))
        ]);

      $this->db('detail_pemberian_obat')
        ->where('tgl_perawatan', $_POST['tgl_peresepan'])
        ->where('jam', $_POST['jam_peresepan'])
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kode_brng', $_POST['kode_brng'])
        ->where('jml', $_POST['jml'])
        ->where('status', 'Ralan')
        ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
        ->delete();

      exit();
    }

    public function anyRincian()
    {
        $this->_checkSchema();

      $cek_laboratorium = $this->db('mlite_modules')->where('dir', 'laboratorium')->oneArray();
      $cek_radiologi = $this->db('mlite_modules')->where('dir', 'radiologi')->oneArray();

      $poliklinik = $this->db('poliklinik')
        ->join('reg_periksa', 'reg_periksa.kd_poli=poliklinik.kd_poli')
        ->where('no_rawat', $_POST['no_rawat'])
        ->oneArray();
      if($poliklinik['stts_daftar'] == 'Lama') {
        $poliklinik['registrasi'] = $poliklinik['registrasilama'];
      }

      $pasien = $this->db('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('reg_periksa.no_rawat', $_POST['no_rawat'])
        ->select('pasien.*')
        ->select('reg_periksa.*')
        ->select(['nm_poli' => 'poliklinik.nm_poli'])
        ->select(['nm_dokter' => 'dokter.nm_dokter'])
        ->select(['png_jawab' => 'penjab.png_jawab'])
        ->oneArray();

      $rows_rawat_jl_dr = $this->db('rawat_jl_dr')->where('no_rawat', $_POST['no_rawat'])->toArray();
      $rows_rawat_jl_pr = $this->db('rawat_jl_pr')->where('no_rawat', $_POST['no_rawat'])->toArray();
      $rows_rawat_jl_drpr = $this->db('rawat_jl_drpr')->where('no_rawat', $_POST['no_rawat'])->toArray();

      $jumlah_total = 0;
      $jumlah_total_coass = 0;
      $rawat_jl_dr = [];
      $rawat_jl_pr = [];
      $rawat_jl_drpr = [];
      $no_tindakan = 1;

      if($rows_rawat_jl_dr) {
        foreach ($rows_rawat_jl_dr as $row) {
          $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $row['provider'] = 'Dokter';
          $row['provider_table'] = 'rawat_jl_dr';
          $row['is_coass'] = ($row['stts_bayar'] == 'Suspen');
          if($row['is_coass']) {
            $jumlah_total_coass += $row['biaya_rawat'];
          } else {
            $jumlah_total += $row['biaya_rawat'];
          }
          $rawat_jl_dr[] = $row;
        }
      }

      if($rows_rawat_jl_pr) {
        foreach ($rows_rawat_jl_pr as $row) {
          $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $row['provider'] = 'Perawat';
          $row['provider_table'] = 'rawat_jl_pr';
          $row['is_coass'] = ($row['stts_bayar'] == 'Suspen');
          if($row['is_coass']) {
            $jumlah_total_coass += $row['biaya_rawat'];
          } else {
            $jumlah_total += $row['biaya_rawat'];
          }
          $rawat_jl_pr[] = $row;
        }
      }

      if($rows_rawat_jl_drpr) {
        foreach ($rows_rawat_jl_drpr as $row) {
          $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $row['kd_jenis_prw'])->oneArray();
          $row['nm_perawatan'] = $jns_perawatan['nm_perawatan'];
          $row['provider'] = 'Dokter & Perawat';
          $row['provider_table'] = 'rawat_jl_drpr';
          $row['is_coass'] = ($row['stts_bayar'] == 'Suspen');
          if($row['is_coass']) {
            $jumlah_total_coass += $row['biaya_rawat'];
          } else {
            $jumlah_total += $row['biaya_rawat'];
          }
          $rawat_jl_drpr[] = $row;
        }
      }

      $merge_tindakan = array_merge($rawat_jl_dr, $rawat_jl_pr, $rawat_jl_drpr);
      $tindakan = [];
      foreach ($merge_tindakan as $row) {
        $row['nomor'] = $no_tindakan++;
        $tindakan[] = $row;
      }

      // Collect racikan timestamps to exclude from detail_pemberian_obat
      $exclude_jams = [];
      $racikan_jams = $this->db('detail_obat_racikan')->where('no_rawat', $_POST['no_rawat'])->toArray();
      foreach($racikan_jams as $rj) {
          $exclude_jams[] = $rj['jam'];
      }

      $query_pemberian = $this->db('detail_pemberian_obat')
        ->where('detail_pemberian_obat.no_rawat', $_POST['no_rawat'])
        ->where('detail_pemberian_obat.status', 'Ralan');

      if(!empty($exclude_jams)) {
          $query_pemberian->notIn('jam', $exclude_jams);
      }

      $rows_pemberian_obat = $query_pemberian->toArray();

      $detail_pemberian_obat = [];
      $jumlah_total_obat = 0;
      $jumlah_total_embalase = 0;
      $jumlah_total_tuslah = 0;
      $no_obat = 1;
      foreach ($rows_pemberian_obat as $row) {
        $row['nomor'] = $no_obat++;
        $databarang = $this->db('databarang')->where('kode_brng', $row['kode_brng'])->oneArray();
        $row['nama_brng'] = $databarang['nama_brng'];
        $jumlah_total_obat += floatval($row['total']);
        $jumlah_total_embalase += floatval($row['embalase']);
        $jumlah_total_tuslah += floatval($row['tuslah']);
        $detail_pemberian_obat[] = $row;
      }

      // Fetch detail_obat_racikan linked with riwayat_barang_medis for quantity
      $rows_obat_racikan = $this->db('obat_racikan')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();

      foreach ($rows_obat_racikan as $header) {
        $ingredients_map = $this->db('detail_obat_racikan')
            ->where('no_rawat', $header['no_rawat'])
            ->where('no_racik', $header['no_racik'])
            ->where('tgl_perawatan', $header['tgl_perawatan'])
            ->where('jam', $header['jam'])
            ->toArray();

        $total_racikan = 0;
        $total_embalase_racikan = 0;
        $total_tuslah_racikan = 0;
        $ingredient_rows = [];

        foreach ($ingredients_map as $map) {
            $row = $this->db('detail_pemberian_obat')
                ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
                ->where('detail_pemberian_obat.no_rawat', $map['no_rawat'])
                ->where('detail_pemberian_obat.kode_brng', $map['kode_brng'])
                ->where('detail_pemberian_obat.tgl_perawatan', $map['tgl_perawatan'])
                ->where('detail_pemberian_obat.jam', $map['jam'])
                ->where('detail_pemberian_obat.status', 'Ralan')
                ->oneArray();

            if ($row) {
                $subtotal = $row['total'];
                
                $total_racikan += $subtotal;
                $total_embalase_racikan += $row['embalase'];
                $total_tuslah_racikan += $row['tuslah'];
    
                // Prepare ingredient row (hide prices)
                $row['nomor'] = ''; 
                $row['nama_brng'] = "&nbsp;&nbsp;&nbsp;&nbsp; - " . $row['nama_brng'];
                $row['total'] = 0;
                $row['embalase'] = 0;
                $row['tuslah'] = 0;
                $row['biaya_obat'] = 0;
                $row['jml'] = 0;
                
                $ingredient_rows[] = $row;
            }
        }

        $header_row = [];
        $header_row['nomor'] = $no_obat++;
        $header_row['nama_brng'] = ">> Racikan " . $header['no_racik'] . ": " . $header['nama_racik'] . " (" . $header['jml_dr'] . " Bungkus)";
        $header_row['jml'] = $header['jml_dr'];
        
        // Calculate unit price for display: total / qty
        if ($header['jml_dr'] > 0) {
            $header_row['biaya_obat'] = $total_racikan / $header['jml_dr'];
        } else {
            $header_row['biaya_obat'] = $total_racikan;
        }

        $header_row['total'] = $total_racikan;
        $header_row['embalase'] = $total_embalase_racikan;
        $header_row['tuslah'] = $total_tuslah_racikan;
        $header_row['no_rawat'] = $header['no_rawat'];
        $header_row['tgl_perawatan'] = $header['tgl_perawatan'];
        $header_row['jam'] = $header['jam'];
        $header_row['kode_brng'] = '-'; 
        
        $jumlah_total_obat += $total_racikan;
        $jumlah_total_embalase += $total_embalase_racikan;
        $jumlah_total_tuslah += $total_tuslah_racikan;

        $detail_pemberian_obat[] = $header_row;
        
        foreach ($ingredient_rows as $row) {
            $detail_pemberian_obat[] = $row;
        }
      }
      

      $rows_periksa_lab = $this->db('periksa_lab')
      ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('periksa_lab.status', 'Ralan')
      ->toArray();

      $periksa_lab = [];
      $jumlah_total_lab = 0;
      $no_lab = 1;
      foreach ($rows_periksa_lab as $row) {
        $jumlah_total_lab += $row['biaya'];
        $row['nomor'] = $no_lab++;
        $periksa_lab[] = $row;
      }

      $rows_periksa_radiologi = $this->db('periksa_radiologi')
      ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw=periksa_radiologi.kd_jenis_prw')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('periksa_radiologi.status', 'Ralan')
      ->toArray();

      $periksa_radiologi = [];
      $jumlah_total_radiologi = 0;
      $no_rad = 1;
      foreach ($rows_periksa_radiologi as $row) {
        $jumlah_total_radiologi += $row['biaya'];
        $row['nomor'] = $no_rad++;
        $periksa_radiologi[] = $row;
      }

      $jumlah_total_operasi = 0;
      $operasis = $this->db('operasi')->where('no_rawat', $_POST['no_rawat'])->where('status', 'Ralan')->toArray();
      foreach ($operasis as $operasi) {
        $operasi['jumlah'] = $operasi['biayaoperator1']+$operasi['biayaoperator2']+$operasi['biayaoperator3']+$operasi['biayaasisten_operator1']+$operasi['biayaasisten_operator2']+$operasi['biayadokter_anak']+$operasi['biayaperawaat_resusitas']+$operasi['biayadokter_anestesi']+$operasi['biayaasisten_anestesi']+$operasi['biayabidan']+$operasi['biayaperawat_luar'];
        $jumlah_total_operasi += $operasi['jumlah'];
      }
      $jumlah_total_obat_operasi = 0;
      $obat_operasis = $this->db('beri_obat_operasi')->where('no_rawat', $_POST['no_rawat'])->toArray();
      foreach ($obat_operasis as $obat_operasi) {
        $obat_operasi['harga'] = $obat_operasi['hargasatuan'] * $obat_operasi['jumlah'];
        $jumlah_total_obat_operasi += $obat_operasi['harga'];
      }

      $rows_resep_pulang = $this->db('resep_pulang')
        ->join('databarang', 'databarang.kode_brng=resep_pulang.kode_brng')
        ->where('resep_pulang.no_rawat', $_POST['no_rawat'])
        ->toArray();
      $resep_pulang = [];
      $no_resep_pulang = 1;
      $jumlah_total_obat_pulang = 0;
      foreach ($rows_resep_pulang as $row) {
        $row['nomor'] = $no_resep_pulang++;
        $jumlah_total_obat_pulang += $row['total'];
        $resep_pulang[] = $row;
      }

      $rows_tambahan_biaya = $this->db('tambahan_biaya')
        ->where('no_rawat', $_POST['no_rawat'])
        ->toArray();
      $tambahan_biaya = [];
      $no_tambahan_biaya = 1;
      $jumlah_total_tambahan = 0;
      foreach ($rows_tambahan_biaya as $row) {
        $row['nomor'] = $no_tambahan_biaya++;
        $jumlah_total_tambahan += $row['besar_biaya'];
        $tambahan_biaya[] = $row;
      }

      $no_rawat = $_POST['no_rawat'];

      // Ambil data riwayat billing sebelumnya untuk no_rawat ini
      $riwayat_billing = $this->db('mlite_billing')
        ->leftJoin('mlite_users', 'mlite_users.id = mlite_billing.id_user')
        ->where('no_rawat', $no_rawat)
        ->like('kd_billing', 'RJ%')
        ->asc('id_billing')
        ->select('mlite_billing.*')
        ->select(['nama_petugas' => 'mlite_users.fullname'])
        ->toArray();

      // Lampirkan rincian alokasi per faktur
      foreach ($riwayat_billing as $k => $rb) {
        $riwayat_billing[$k]['rincian_item'] = $this->db('mlite_billing_rincian')
          ->where('kd_billing', $rb['kd_billing'])
          ->where('bayar', '>', 0)
          ->toArray();

        // Bersihkan keterangan agar hanya menampilkan metode bayar (Tunai / Non Tunai) tanpa akhiran jenis layanan
        $ket = trim($rb['keterangan'] ?? 'Tunai');
        if (preg_match('/^(Tunai|Non Tunai)/i', $ket, $m)) {
          $prefix = (strtolower($m[1]) === 'non tunai') ? 'Non Tunai' : 'Tunai';
          $suffix = trim(substr($ket, strlen($m[0])));
          if (empty($suffix) || preg_match('/^-\s*(Semua Layanan|Tindakan|Pendaftaran|Obat.*|Laboratorium|Radiologi|Operasi|Biaya Tambahan|Biaya Lainnya|Pembayaran)$/i', $suffix)) {
            $ket = $prefix;
          }
        }
        $riwayat_billing[$k]['keterangan'] = $ket;
      }

      // Ambil akumulasi pembayaran per pos dari mlite_billing_rincian
      $rincian_sudah_bayar = $this->db('mlite_billing_rincian')
        ->where('no_rawat', $no_rawat)
        ->toArray();

      $sudah_bayar_pos = [
        'pendaftaran' => 0,
        'tindakan' => 0,
        'obat' => 0,
        'laboratorium' => 0,
        'radiologi' => 0,
        'operasi' => 0,
        'obat_pulang' => 0,
        'tambahan' => 0
      ];

      foreach ($rincian_sudah_bayar as $rsb) {
        if (isset($sudah_bayar_pos[$rsb['kategori']])) {
          $sudah_bayar_pos[$rsb['kategori']] += floatval($rsb['bayar']);
        }
      }

      $total_sudah_bayar_sebelumnya = 0;
      foreach ($riwayat_billing as $billing_row) {
        $total_sudah_bayar_sebelumnya += floatval($billing_row['jumlah_bayar']);
      }

      // Fallback untuk riwayat lama yang belum ada di mlite_billing_rincian
      $sum_rincian = array_sum($sudah_bayar_pos);
      if ($sum_rincian == 0 && $total_sudah_bayar_sebelumnya > 0) {
        $sisa_alokasi = $total_sudah_bayar_sebelumnya;
        $tagihan_temp = [
          'pendaftaran' => floatval($poliklinik['registrasi']),
          'radiologi' => floatval($jumlah_total_radiologi),
          'laboratorium' => floatval($jumlah_total_lab),
          'tindakan' => floatval($jumlah_total),
          'obat' => floatval($jumlah_total_obat),
          'operasi' => floatval($jumlah_total_operasi + $jumlah_total_obat_operasi),
          'obat_pulang' => floatval($jumlah_total_obat_pulang),
          'tambahan' => floatval($jumlah_total_tambahan)
        ];
        foreach ($tagihan_temp as $kat => $tag) {
          if ($sisa_alokasi <= 0) break;
          $byr = min($tag, $sisa_alokasi);
          $sudah_bayar_pos[$kat] = $byr;
          $sisa_alokasi -= $byr;
        }
      }

      $coass_kategori = $this->getCoassKategori($no_rawat);

      // 1. Pendaftaran
      $biaya_registrasi_asli = floatval($poliklinik['registrasi']);
      $is_coass_reg = !empty($coass_kategori['pendaftaran']);
      $tagihan_pendaftaran = $is_coass_reg ? 0 : $biaya_registrasi_asli;
      $biaya_registrasi = $tagihan_pendaftaran;
      $coass_registrasi = $is_coass_reg ? $biaya_registrasi_asli : 0;

      // 2. Tindakan
      $is_coass_tindakan = !empty($coass_kategori['tindakan']);
      $biaya_tindakan_asli = $jumlah_total + $jumlah_total_coass;
      if ($is_coass_tindakan) {
        $tagihan_tindakan = 0;
        $coass_tindakan = $biaya_tindakan_asli;
      } else {
        $tagihan_tindakan = $jumlah_total;
        $coass_tindakan = $jumlah_total_coass;
      }

      // 3. Obat & BHP
      $biaya_obat_asli = floatval($jumlah_total_obat);
      $is_coass_obat = !empty($coass_kategori['obat']);
      $tagihan_obat = $is_coass_obat ? 0 : $biaya_obat_asli;
      $coass_obat = $is_coass_obat ? $biaya_obat_asli : 0;

      // 4. Laboratorium
      $biaya_lab_asli = floatval($jumlah_total_lab);
      $is_coass_lab = !empty($coass_kategori['laboratorium']);
      $tagihan_lab = $is_coass_lab ? 0 : $biaya_lab_asli;
      $coass_lab = $is_coass_lab ? $biaya_lab_asli : 0;

      // 5. Radiologi
      $biaya_rad_asli = floatval($jumlah_total_radiologi);
      $is_coass_rad = !empty($coass_kategori['radiologi']);
      $tagihan_rad = $is_coass_rad ? 0 : $biaya_rad_asli;
      $coass_rad = $is_coass_rad ? $biaya_rad_asli : 0;

      // 6. Operasi
      $biaya_operasi_asli = floatval($jumlah_total_operasi + $jumlah_total_obat_operasi);
      $is_coass_operasi = !empty($coass_kategori['operasi']);
      $tagihan_operasi = $is_coass_operasi ? 0 : $biaya_operasi_asli;
      $biaya_operasi_total = $tagihan_operasi;
      $coass_operasi = $is_coass_operasi ? $biaya_operasi_asli : 0;

      // 7. Obat Pulang
      $biaya_obat_pulang_asli = floatval($jumlah_total_obat_pulang);
      $is_coass_obat_pulang = !empty($coass_kategori['obat_pulang']);
      $tagihan_obat_pulang = $is_coass_obat_pulang ? 0 : $biaya_obat_pulang_asli;
      $coass_obat_pulang = $is_coass_obat_pulang ? $biaya_obat_pulang_asli : 0;

      // 8. Tambahan
      $biaya_tambahan_asli = floatval($jumlah_total_tambahan);
      $is_coass_tambahan = !empty($coass_kategori['tambahan']);
      $tagihan_tambahan = $is_coass_tambahan ? 0 : $biaya_tambahan_asli;
      $coass_tambahan = $is_coass_tambahan ? $biaya_tambahan_asli : 0;

      $pos_billing = [
        'pendaftaran' => [
          'nama' => 'Pendaftaran',
          'ada' => ($biaya_registrasi_asli > 0),
          'tagihan_asli' => $biaya_registrasi_asli,
          'tagihan' => $tagihan_pendaftaran,
          'tagihan_coass' => $coass_registrasi,
          'is_coass' => $is_coass_reg,
          'sudah_bayar' => $sudah_bayar_pos['pendaftaran'],
          'sisa' => max(0, $tagihan_pendaftaran - $sudah_bayar_pos['pendaftaran'])
        ],
        'tindakan' => [
          'nama' => 'Tindakan',
          'ada' => !empty($tindakan),
          'tagihan_asli' => $biaya_tindakan_asli,
          'tagihan' => $tagihan_tindakan,
          'tagihan_coass' => $coass_tindakan,
          'is_coass' => $is_coass_tindakan || ($coass_tindakan > 0 && $tagihan_tindakan == 0),
          'sudah_bayar' => $sudah_bayar_pos['tindakan'],
          'sisa' => max(0, $tagihan_tindakan - $sudah_bayar_pos['tindakan'])
        ],
        'obat' => [
          'nama' => 'Obat dan BHP',
          'ada' => !empty($detail_pemberian_obat) || !empty($rows_obat_racikan),
          'tagihan_asli' => $biaya_obat_asli,
          'tagihan' => $tagihan_obat,
          'tagihan_coass' => $coass_obat,
          'is_coass' => $is_coass_obat,
          'sudah_bayar' => $sudah_bayar_pos['obat'],
          'sisa' => max(0, $tagihan_obat - $sudah_bayar_pos['obat'])
        ],
        'laboratorium' => [
          'nama' => 'Laboratorium',
          'ada' => !empty($periksa_lab),
          'tagihan_asli' => $biaya_lab_asli,
          'tagihan' => $tagihan_lab,
          'tagihan_coass' => $coass_lab,
          'is_coass' => $is_coass_lab,
          'sudah_bayar' => $sudah_bayar_pos['laboratorium'],
          'sisa' => max(0, $tagihan_lab - $sudah_bayar_pos['laboratorium'])
        ],
        'radiologi' => [
          'nama' => 'Radiologi',
          'ada' => !empty($periksa_radiologi),
          'tagihan_asli' => $biaya_rad_asli,
          'tagihan' => $tagihan_rad,
          'tagihan_coass' => $coass_rad,
          'is_coass' => $is_coass_rad,
          'sudah_bayar' => $sudah_bayar_pos['radiologi'],
          'sisa' => max(0, $tagihan_rad - $sudah_bayar_pos['radiologi'])
        ],
        'operasi' => [
          'nama' => 'Operasi',
          'ada' => !empty($jumlah_total_operasi) || !empty($biaya_operasi_total),
          'tagihan_asli' => $biaya_operasi_asli,
          'tagihan' => $tagihan_operasi,
          'tagihan_coass' => $coass_operasi,
          'is_coass' => $is_coass_operasi,
          'sudah_bayar' => $sudah_bayar_pos['operasi'],
          'sisa' => max(0, $tagihan_operasi - $sudah_bayar_pos['operasi'])
        ],
        'obat_pulang' => [
          'nama' => 'Obat Pulang',
          'ada' => !empty($resep_pulang),
          'tagihan_asli' => $biaya_obat_pulang_asli,
          'tagihan' => $tagihan_obat_pulang,
          'tagihan_coass' => $coass_obat_pulang,
          'is_coass' => $is_coass_obat_pulang,
          'sudah_bayar' => $sudah_bayar_pos['obat_pulang'],
          'sisa' => max(0, $tagihan_obat_pulang - $sudah_bayar_pos['obat_pulang'])
        ],
        'tambahan' => [
          'nama' => 'Biaya Tambahan',
          'ada' => !empty($tambahan_biaya),
          'tagihan_asli' => $biaya_tambahan_asli,
          'tagihan' => $tagihan_tambahan,
          'tagihan_coass' => $coass_tambahan,
          'is_coass' => $is_coass_tambahan,
          'sudah_bayar' => $sudah_bayar_pos['tambahan'],
          'sisa' => max(0, $tagihan_tambahan - $sudah_bayar_pos['tambahan'])
        ]
      ];

      $grand_total_tagihan = $tagihan_pendaftaran + $tagihan_tindakan + $tagihan_obat + $tagihan_lab + $tagihan_rad + $tagihan_operasi + $tagihan_obat_pulang + $tagihan_tambahan;
      $grand_total_coass = $coass_registrasi + $coass_tindakan + $coass_obat + $coass_lab + $coass_rad + $coass_operasi + $coass_obat_pulang + $coass_tambahan;
      $grand_total_sisa = max(0, $grand_total_tagihan - $total_sudah_bayar_sebelumnya);

      $has_tindakan = !empty($tindakan);
      $has_obat = !empty($detail_pemberian_obat) || !empty($rows_obat_racikan);
      $has_lab = !empty($periksa_lab);
      $has_rad = !empty($periksa_radiologi);
      $has_operasi = !empty($jumlah_total_operasi) || !empty($biaya_operasi_total);
      $has_obat_pulang = !empty($resep_pulang);
      $has_tambahan = !empty($tambahan_biaya);

      $has_layanan_poli = $has_tindakan || $has_obat || $has_lab || $has_rad || $has_operasi || $has_obat_pulang || $has_tambahan;

      $has_pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->where('no_rawat', $no_rawat)->oneArray();
      $has_diagnosa = $this->db('diagnosa_pasien')->where('no_rawat', $no_rawat)->oneArray();
      $stts_periksa = $pasien['stts'] ?? '';

      $has_perawatan_poli = $has_layanan_poli || !empty($has_pemeriksaan_ralan) || !empty($has_diagnosa) || ($stts_periksa == 'Sudah');

      echo $this->draw('rincian.html', [
        'rawat_jl_dr' => $rawat_jl_dr,
        'rawat_jl_pr' => $rawat_jl_pr,
        'rawat_jl_drpr' => $rawat_jl_drpr,
        'tindakan' => $tindakan,
        'jumlah_total' => $tagihan_tindakan,
        'jumlah_total_coass' => $coass_tindakan,
        'jumlah_total_obat' => $jumlah_total_obat,
        'jumlah_total_embalase' => $jumlah_total_embalase,
        'jumlah_total_tuslah' => $jumlah_total_tuslah,
        'poliklinik' => htmlspecialchars_array($poliklinik),
        'biaya_registrasi' => $biaya_registrasi,
        'detail_pemberian_obat' => htmlspecialchars_array($detail_pemberian_obat),
        'periksa_lab' => $periksa_lab,
        'jumlah_total_lab' => $jumlah_total_lab,
        'periksa_radiologi' => $periksa_radiologi,
        'jumlah_total_radiologi' => $jumlah_total_radiologi,
        'jumlah_total_operasi' => $jumlah_total_operasi,
        'jumlah_total_obat_operasi' => $jumlah_total_obat_operasi,
        'biaya_operasi_total' => $biaya_operasi_total,
        'resep_pulang' => htmlspecialchars_array($resep_pulang),
        'jumlah_total_obat_pulang' => $jumlah_total_obat_pulang,
        'tambahan_biaya' => $tambahan_biaya,
        'jumlah_total_tambahan' => $jumlah_total_tambahan,
        'pos_billing' => $pos_billing,
        'coass_kategori' => $coass_kategori,
        'grand_total_coass' => $grand_total_coass,
        'riwayat_billing' => $riwayat_billing,
        'total_sudah_bayar_sebelumnya' => $total_sudah_bayar_sebelumnya,
        'grand_total_tagihan' => $grand_total_tagihan,
        'grand_total_sisa' => $grand_total_sisa,
        'has_layanan_poli' => $has_layanan_poli,
        'has_perawatan_poli' => $has_perawatan_poli,
        'stts_periksa' => $stts_periksa,
        'pasien' => htmlspecialchars_array($pasien ?? []),
        'no_rawat' => htmlspecialchars($_POST['no_rawat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      ]);
      exit();
    }

    public function anyLayanan()
    {
      $layanan = $this->db('jns_perawatan')
        ->where('status', '1')
        ->like('nm_perawatan', '%'.htmlspecialchars($_POST['layanan'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('layanan.html', ['layanan' => htmlspecialchars_array($layanan)]);
      exit();
    }

    public function anyObat()
    {
      $obat = $this->db('databarang')
        ->join('gudangbarang', 'gudangbarang.kode_brng=databarang.kode_brng')
        ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
        ->where('status', '1')
        ->like('databarang.nama_brng', '%'.htmlspecialchars($_POST['obat'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('obat.html', ['obat' => htmlspecialchars_array($obat)]);
      exit();
    }

    public function anyLaboratorium()
    {
      $laboratorium = $this->db('jns_perawatan_lab')
        ->where('status', '1')
        ->like('nm_perawatan', '%'.htmlspecialchars($_POST['laboratorium'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('laboratorium.html', ['laboratorium' => htmlspecialchars_array($laboratorium)]);
      exit();
    }

    public function anyRadiologi()
    {
      $radiologi = $this->db('jns_perawatan_radiologi')
        ->where('status', '1')
        ->like('nm_perawatan', '%'.htmlspecialchars($_POST['radiologi'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('radiologi.html', ['radiologi' => htmlspecialchars_array($radiologi)]);
      exit();
    }

    public function postAturanPakai()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('master_aturan_pakai')->like('aturan', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["aturan"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["kd_dokter"].': '.$row["nm_dokter"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList2()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->db('petugas')->like('nama', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.htmlspecialchars($row["nip"].': '.$row["nama"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postSave()
    {
      $_POST['id_user']	= $this->core->getUserInfo('id');
      $tgl_jurnal = !empty($_POST['tgl_billing']) ? $_POST['tgl_billing'] : date('Y-m-d');

      $parseJumlah = function($val) {
          if (strpos($val, ',') !== false) {
              $val = substr($val, 0, strpos($val, ','));
          }
          return floatval(preg_replace('/[^0-9]/', '', $val));
      };

      if($this->settings('keuangan', 'jurnal_kasir') == 1 && empty($_POST['tidak_diklaim'])) {
          // jurnal_pendaftaran //
          $jumlah_pendaftaran = isset($_POST['jurnal_pendaftaran']) ? $parseJumlah($_POST['jurnal_pendaftaran']) : 0;
          if($jumlah_pendaftaran > 0) {
              $no_jurnal_pendaftaran = $this->core->setNoJurnal();
              $keterangan = $this->db('mlite_rekening')
                  ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_pendaftaran'))
                  ->oneArray();
              $jumlah = $jumlah_pendaftaran;
              
              $query_jurnal_pendaftaran = $this->db('mlite_jurnal')->save([
                  'no_jurnal' => $no_jurnal_pendaftaran,
                  'no_bukti' => $_POST['no_rawat'],
                  'tgl_jurnal' => $tgl_jurnal,
                  'jenis' => 'U',
                  'kegiatan' => $keterangan['nm_rek'] ?? 'Pendaftaran',
                  'keterangan' => ($keterangan['nm_rek'] ?? 'Pendaftaran').' '.$_POST['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
              ]);
              
              if($query_jurnal_pendaftaran) {
                  // DEBET: Kas/Piutang Usaha (Aset bertambah)
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_pendaftaran,
                      'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'), // Setting untuk akun kas
                      'arus_kas' => '1',
                      'debet' => $jumlah,
                      'kredit' => '0'
                  ]);
                  
                  // KREDIT: Pendapatan Pendaftaran (Pendapatan bertambah)
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_pendaftaran,
                      'kd_rek' => $this->settings('keuangan', 'akun_kredit_pendaftaran'),
                      'arus_kas' => '0',
                      'debet' => '0',
                      'kredit' => $jumlah
                  ]);
              }
              unset($_POST['jurnal_pendaftaran']);
          }
          // End jurnal_pendaftaran // 

          // jurnal_tindakan_ralan //
          $jumlah_tindakan = isset($_POST['jurnal_tindakan_ralan']) ? $parseJumlah($_POST['jurnal_tindakan_ralan']) : 0;
          if($jumlah_tindakan > 0) {
              $no_jurnal_tindakan_ralan = $this->core->setNoJurnal();
              $keterangan = $this->db('mlite_rekening')
                  ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_tindakan'))
                  ->oneArray();
              $jumlah = $jumlah_tindakan;
              
              $query_jurnal_tindakan_ralan = $this->db('mlite_jurnal')->save([
                  'no_jurnal' => $no_jurnal_tindakan_ralan,
                  'no_bukti' => $_POST['no_rawat'],
                  'tgl_jurnal' => $tgl_jurnal,
                  'jenis' => 'U',
                  'kegiatan' => $keterangan['nm_rek'] ?? 'Tindakan',
                  'keterangan' => ($keterangan['nm_rek'] ?? 'Tindakan').' '.$_POST['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
              ]);
              
              if($query_jurnal_tindakan_ralan) {
                  // DEBET: Kas/Piutang Usaha
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_tindakan_ralan,
                      'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                      'arus_kas' => '1',
                      'debet' => $jumlah,
                      'kredit' => '0'
                  ]);
                  
                  // KREDIT: Pendapatan Tindakan
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_tindakan_ralan,
                      'kd_rek' => $this->settings('keuangan', 'akun_kredit_tindakan'),
                      'arus_kas' => '0',
                      'debet' => '0',
                      'kredit' => $jumlah
                  ]);
              }
              unset($_POST['jurnal_tindakan_ralan']);
          }
          // End jurnal_tindakan_ralan //

          // jurnal_obat_bhp //
          $jumlah_obat = isset($_POST['jurnal_obat_bhp']) ? $parseJumlah($_POST['jurnal_obat_bhp']) : 0;
          if($jumlah_obat > 0) {
              $no_jurnal_obat_bhp = $this->core->setNoJurnal();
              $keterangan = $this->db('mlite_rekening')
                  ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_obat_bhp'))
                  ->oneArray();
              $jumlah = $jumlah_obat;
              
              $query_jurnal_obat_bhp = $this->db('mlite_jurnal')->save([
                  'no_jurnal' => $no_jurnal_obat_bhp,
                  'no_bukti' => $_POST['no_rawat'],
                  'tgl_jurnal' => $tgl_jurnal,
                  'jenis' => 'U',
                  'kegiatan' => $keterangan['nm_rek'] ?? 'Obat dan BHP',
                  'keterangan' => ($keterangan['nm_rek'] ?? 'Obat dan BHP').' '.$_POST['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
              ]);
              
              if($query_jurnal_obat_bhp) {
                  // DEBET: Kas/Piutang Usaha
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_obat_bhp,
                      'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                      'arus_kas' => '1',
                      'debet' => $jumlah,
                      'kredit' => '0'
                  ]);
                  
                  // KREDIT: Pendapatan Obat dan BHP
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_obat_bhp,
                      'kd_rek' => $this->settings('keuangan', 'akun_kredit_obat_bhp'),
                      'arus_kas' => '0',
                      'debet' => '0',
                      'kredit' => $jumlah
                  ]);
              }
              unset($_POST['jurnal_obat_bhp']);
          }
          // End jurnal_obat_bhp //

          // jurnal_laboratorium //
          $jumlah_lab = isset($_POST['jurnal_laboratorium']) ? $parseJumlah($_POST['jurnal_laboratorium']) : 0;
          if($jumlah_lab > 0) {
              $no_jurnal_laboratorium = $this->core->setNoJurnal();
              $keterangan = $this->db('mlite_rekening')
                  ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_laboratorium'))
                  ->oneArray();
              $jumlah = $jumlah_lab;
              
              $query_jurnal_laboratorium = $this->db('mlite_jurnal')->save([
                  'no_jurnal' => $no_jurnal_laboratorium,
                  'no_bukti' => $_POST['no_rawat'],
                  'tgl_jurnal' => $tgl_jurnal,
                  'jenis' => 'U',
                  'kegiatan' => $keterangan['nm_rek'] ?? 'Laboratorium',
                  'keterangan' => ($keterangan['nm_rek'] ?? 'Laboratorium').' '.$_POST['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
              ]);
              
              if($query_jurnal_laboratorium) {
                  // DEBET: Kas/Piutang Usaha
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_laboratorium,
                      'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                      'arus_kas' => '1',
                      'debet' => $jumlah,
                      'kredit' => '0'
                  ]);
                  
                  // KREDIT: Pendapatan Laboratorium
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_laboratorium,
                      'kd_rek' => $this->settings('keuangan', 'akun_kredit_laboratorium'),
                      'arus_kas' => '0',
                      'debet' => '0',
                      'kredit' => $jumlah
                  ]);
              }
              unset($_POST['jurnal_laboratorium']);
          }
          // End jurnal_laboratorium //

          // jurnal_radiologi//
          $jumlah_radiologi = isset($_POST['jurnal_radiologi']) ? $parseJumlah($_POST['jurnal_radiologi']) : 0;
          if($jumlah_radiologi > 0) {
              $no_jurnal_radiologi = $this->core->setNoJurnal();
              $keterangan = $this->db('mlite_rekening')
                  ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_radiologi'))
                  ->oneArray();
              $jumlah = $jumlah_radiologi;
              
              $query_jurnal_radiologi = $this->db('mlite_jurnal')->save([
                  'no_jurnal' => $no_jurnal_radiologi,
                  'no_bukti' => $_POST['no_rawat'],
                  'tgl_jurnal' => $tgl_jurnal,
                  'jenis' => 'U',
                  'kegiatan' => $keterangan['nm_rek'] ?? 'Radiologi',
                  'keterangan' => ($keterangan['nm_rek'] ?? 'Radiologi').' '.$_POST['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
              ]);
              
              if($query_jurnal_radiologi) {
                  // DEBET: Kas/Piutang Usaha
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_radiologi,
                      'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                      'arus_kas' => '1',
                      'debet' => $jumlah,
                      'kredit' => '0'
                  ]);
                  
                  // KREDIT: Pendapatan Radiologi
                  $this->db('mlite_detailjurnal')->save([
                      'no_jurnal' => $no_jurnal_radiologi,
                      'kd_rek' => $this->settings('keuangan', 'akun_kredit_radiologi'),
                      'arus_kas' => '0',
                      'debet' => '0',
                      'kredit' => $jumlah
                  ]);
              }
              unset($_POST['jurnal_radiologi']);
          }
          // End jurnal_radiologi //
      }

      unset($_POST['jurnal_pendaftaran']);
      unset($_POST['jurnal_tindakan_ralan']);
      unset($_POST['jurnal_obat_bhp']);
      unset($_POST['jurnal_laboratorium']);
      unset($_POST['jurnal_radiologi']);

      $alokasi = isset($_POST['alokasi']) ? json_decode($_POST['alokasi'], true) : [];
      $tidak_diklaim = !empty($_POST['tidak_diklaim']);
      $sisa_setelah_bayar = isset($_POST['sisa_setelah_bayar']) ? floatval(preg_replace('/[^0-9]/', '', $_POST['sisa_setelah_bayar'])) : 0;

      unset($_POST['tidak_diklaim']);
      unset($_POST['alokasi']);
      unset($_POST['pelayanan']);
      unset($_POST['nama_pelayanan']);
      unset($_POST['sisa_setelah_bayar']);
      unset($_POST['catatan_billing']);
      unset($_POST['cara_bayar']);
      unset($_POST['jumlah_total_layanan']);
      unset($_POST['total_sudah_bayar']);

      // Jika transaksi ditandai Tidak Diklaim (Ditanggung Coass)
      if ($tidak_diklaim) {
        $coass_cats = $this->getCoassKategori($_POST['no_rawat']);
        if (!empty($alokasi) && is_array($alokasi)) {
          foreach ($alokasi as $kat => $val) {
            $coass_cats[$kat] = true;
            if ($kat === 'tindakan') {
              $this->db('rawat_jl_dr')->where('no_rawat', $_POST['no_rawat'])->update(['stts_bayar' => 'Suspen']);
              $this->db('rawat_jl_pr')->where('no_rawat', $_POST['no_rawat'])->update(['stts_bayar' => 'Suspen']);
              $this->db('rawat_jl_drpr')->where('no_rawat', $_POST['no_rawat'])->update(['stts_bayar' => 'Suspen']);
            }
          }
        }
        $this->setCoassKategori($_POST['no_rawat'], $coass_cats);
      }

      $kd_billing = !empty($_POST['kd_billing']) ? $_POST['kd_billing'] : ('RJ.' . date('d.m.Y.H.i.s'));
      $cek_kd = $this->db('mlite_billing')->where('kd_billing', $kd_billing)->oneArray();
      if ($cek_kd) {
        $kd_billing = 'RJ.' . date('d.m.Y.H.i.s') . '.' . rand(10, 99);
      }
      $_POST['kd_billing'] = $kd_billing;

      $ket_billing = $_POST['keterangan'] ?? 'Tunai';
      if ($tidak_diklaim && strpos($ket_billing, 'Tidak Di-claim') === false && strpos($ket_billing, 'Ditanggung Coass') === false) {
        $ket_billing = 'Tidak Di-claim' . (!empty($ket_billing) && $ket_billing !== 'Tunai' ? ' - ' . $ket_billing : '');
      }

      $data_billing = [
        'kd_billing' => $_POST['kd_billing'],
        'no_rawat' => $_POST['no_rawat'],
        'jumlah_total' => preg_replace('/[^0-9]/', '', $_POST['jumlah_total'] ?? 0),
        'potongan' => preg_replace('/[^0-9]/', '', $_POST['potongan'] ?? 0),
        'jumlah_harus_bayar' => $tidak_diklaim ? 0 : preg_replace('/[^0-9]/', '', $_POST['jumlah_harus_bayar'] ?? 0),
        'jumlah_bayar' => $tidak_diklaim ? 0 : preg_replace('/[^0-9]/', '', $_POST['jumlah_bayar'] ?? 0),
        'tgl_billing' => $_POST['tgl_billing'],
        'jam_billing' => $_POST['jam_billing'],
        'id_user' => $_POST['id_user'],
        'keterangan' => $ket_billing
      ];

      $query = $this->db('mlite_billing')->save($data_billing);
      if($query) {
        if (!empty($alokasi) && is_array($alokasi)) {
          foreach ($alokasi as $kat => $val) {
            $bayar_pos = floatval($val['bayar'] ?? 0);
            if ($bayar_pos > 0 || floatval($val['tagihan'] ?? 0) > 0 || !empty($val['nama'])) {
              $this->db('mlite_billing_rincian')->save([
                'kd_billing' => $_POST['kd_billing'],
                'no_rawat' => $_POST['no_rawat'],
                'kategori' => $kat,
                'nama' => $val['nama'] ?? $kat,
                'tagihan' => floatval($val['tagihan'] ?? 0),
                'bayar' => $bayar_pos,
                'sisa' => floatval($val['sisa'] ?? 0),
                'tgl_billing' => $_POST['tgl_billing'],
                'jam_billing' => $_POST['jam_billing']
              ]);
            }
          }
        }

        // Cek total tagihan seluruh layanan untuk no_rawat ini
        $no_rawat = $_POST['no_rawat'];
        $poliklinik = $this->db('poliklinik')->join('reg_periksa', 'reg_periksa.kd_poli=poliklinik.kd_poli')->where('reg_periksa.no_rawat', $no_rawat)->oneArray();
        $coass_cats = $this->getCoassKategori($no_rawat);
        $biaya_reg = !empty($coass_cats['pendaftaran']) ? 0 : floatval($poliklinik['registrasi'] ?? 0);

        $pdo = $this->db()->pdo();

        $qDr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_dr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qDr->execute([$no_rawat]);
        $total_dr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDr->fetchColumn());

        $qPr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_pr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qPr->execute([$no_rawat]);
        $total_pr = !empty($coass_cats['tindakan']) ? 0 : floatval($qPr->fetchColumn());

        $qDrpr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_drpr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qDrpr->execute([$no_rawat]);
        $total_drpr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDrpr->fetchColumn());

        $qObat = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM detail_pemberian_obat WHERE no_rawat = ? AND status = 'Ralan'");
        $qObat->execute([$no_rawat]);
        $total_obat = !empty($coass_cats['obat']) ? 0 : floatval($qObat->fetchColumn());

        $qLab = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_lab WHERE no_rawat = ? AND status = 'Ralan'");
        $qLab->execute([$no_rawat]);
        $total_lab = !empty($coass_cats['laboratorium']) ? 0 : floatval($qLab->fetchColumn());

        $qRad = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_radiologi WHERE no_rawat = ? AND status = 'Ralan'");
        $qRad->execute([$no_rawat]);
        $total_rad = !empty($coass_cats['radiologi']) ? 0 : floatval($qRad->fetchColumn());

        $qTambahan = $pdo->prepare("SELECT COALESCE(SUM(besar_biaya), 0) FROM tambahan_biaya WHERE no_rawat = ?");
        $qTambahan->execute([$no_rawat]);
        $total_tambahan = !empty($coass_cats['tambahan']) ? 0 : floatval($qTambahan->fetchColumn());

        $total_layanan = $biaya_reg + $total_dr + $total_pr + $total_drpr + $total_obat + $total_lab + $total_rad + $total_tambahan;

        // Total yang sudah masuk dari mlite_billing (termasuk yang barusan)
        $qByr = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM mlite_billing WHERE no_rawat = ? AND kd_billing LIKE 'RJ%'");
        $qByr->execute([$no_rawat]);
        $total_terbayar = floatval($qByr->fetchColumn());

        $status_bayar = ($total_layanan == 0 || $total_terbayar >= $total_layanan) ? 'Sudah Bayar' : 'Belum Bayar';
        $this->db('reg_periksa')->where('no_rawat', $no_rawat)->update(['status_bayar' => $status_bayar]);

        if ($status_bayar === 'Sudah Bayar') {
          $pdo->prepare("UPDATE rawat_jl_dr SET stts_bayar = 'Sudah' WHERE no_rawat = ? AND stts_bayar = 'Belum'")->execute([$no_rawat]);
          $pdo->prepare("UPDATE rawat_jl_pr SET stts_bayar = 'Sudah' WHERE no_rawat = ? AND stts_bayar = 'Belum'")->execute([$no_rawat]);
          $pdo->prepare("UPDATE rawat_jl_drpr SET stts_bayar = 'Sudah' WHERE no_rawat = ? AND stts_bayar = 'Belum'")->execute([$no_rawat]);
        }
      }
      exit();
    }

    public function anyHapusBilling()
    {
      header('Content-Type: application/json');

      $kd_billing = $_POST['kd_billing'] ?? ($_GET['kd_billing'] ?? '');
      $no_rawat = $_POST['no_rawat'] ?? ($_GET['no_rawat'] ?? '');

      if (empty($kd_billing)) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor faktur pembayaran tidak ditemukan.']);
        exit();
      }

      try {
        $pdo = $this->db()->pdo();

        $checkStmt = $pdo->prepare("SELECT * FROM mlite_billing WHERE kd_billing = ? LIMIT 1");
        $checkStmt->execute([$kd_billing]);
        $billing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$billing) {
          echo json_encode(['status' => 'error', 'message' => 'Data faktur pembayaran tidak ditemukan dalam database.']);
          exit();
        }

        if (empty($no_rawat)) {
          $no_rawat = $billing['no_rawat'];
        }

        // Jika faktur yang dihapus adalah transaksi Tidak Di-claim, kembalikan statusnya
        if (strpos($billing['keterangan'] ?? '', 'Tidak Di-claim') !== false || strpos($billing['keterangan'] ?? '', 'Ditanggung Coass') !== false) {
          $rincian_coass = $pdo->prepare("SELECT kategori FROM mlite_billing_rincian WHERE kd_billing = ?");
          $rincian_coass->execute([$kd_billing]);
          $coass_rows = $rincian_coass->fetchAll(\PDO::FETCH_ASSOC);
          $coass_cats = $this->getCoassKategori($no_rawat);
          foreach ($coass_rows as $cr) {
            unset($coass_cats[$cr['kategori']]);
            if ($cr['kategori'] === 'tindakan') {
              $pdo->prepare("UPDATE rawat_jl_dr SET stts_bayar = 'Belum' WHERE no_rawat = ?")->execute([$no_rawat]);
              $pdo->prepare("UPDATE rawat_jl_pr SET stts_bayar = 'Belum' WHERE no_rawat = ?")->execute([$no_rawat]);
              $pdo->prepare("UPDATE rawat_jl_drpr SET stts_bayar = 'Belum' WHERE no_rawat = ?")->execute([$no_rawat]);
            }
          }
          $this->setCoassKategori($no_rawat, $coass_cats);
        }

        // Hapus data rincian alokasi per sub-layanan jika ada
        $delRincian = $pdo->prepare("DELETE FROM mlite_billing_rincian WHERE kd_billing = ?");
        $delRincian->execute([$kd_billing]);

        // Hapus data billing transaksi
        $delBilling = $pdo->prepare("DELETE FROM mlite_billing WHERE kd_billing = ?");
        $delBilling->execute([$kd_billing]);

        // Hitung ulang akumulasi tagihan dan sisa pembayaran untuk no_rawat ini
        $poliklinik = $this->db('poliklinik')->join('reg_periksa', 'reg_periksa.kd_poli=poliklinik.kd_poli')->where('reg_periksa.no_rawat', $no_rawat)->oneArray();
        $coass_cats = $this->getCoassKategori($no_rawat);
        $biaya_reg = !empty($coass_cats['pendaftaran']) ? 0 : floatval($poliklinik['registrasi'] ?? 0);

        $qDr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_dr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qDr->execute([$no_rawat]);
        $total_dr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDr->fetchColumn());

        $qPr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_pr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qPr->execute([$no_rawat]);
        $total_pr = !empty($coass_cats['tindakan']) ? 0 : floatval($qPr->fetchColumn());

        $qDrpr = $pdo->prepare("SELECT COALESCE(SUM(biaya_rawat), 0) FROM rawat_jl_drpr WHERE no_rawat = ? AND stts_bayar != 'Suspen'");
        $qDrpr->execute([$no_rawat]);
        $total_drpr = !empty($coass_cats['tindakan']) ? 0 : floatval($qDrpr->fetchColumn());

        $qObat = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM detail_pemberian_obat WHERE no_rawat = ? AND status = 'Ralan'");
        $qObat->execute([$no_rawat]);
        $total_obat = !empty($coass_cats['obat']) ? 0 : floatval($qObat->fetchColumn());

        $qLab = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_lab WHERE no_rawat = ? AND status = 'Ralan'");
        $qLab->execute([$no_rawat]);
        $total_lab = !empty($coass_cats['laboratorium']) ? 0 : floatval($qLab->fetchColumn());

        $qRad = $pdo->prepare("SELECT COALESCE(SUM(biaya), 0) FROM periksa_radiologi WHERE no_rawat = ? AND status = 'Ralan'");
        $qRad->execute([$no_rawat]);
        $total_rad = !empty($coass_cats['radiologi']) ? 0 : floatval($qRad->fetchColumn());

        $qTambahan = $pdo->prepare("SELECT COALESCE(SUM(besar_biaya), 0) FROM tambahan_biaya WHERE no_rawat = ?");
        $qTambahan->execute([$no_rawat]);
        $total_tambahan = !empty($coass_cats['tambahan']) ? 0 : floatval($qTambahan->fetchColumn());

        $total_layanan = $biaya_reg + $total_dr + $total_pr + $total_drpr + $total_obat + $total_lab + $total_rad + $total_tambahan;

        // Total yang masih terbayar di mlite_billing
        $qByr = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM mlite_billing WHERE no_rawat = ? AND kd_billing LIKE 'RJ%'");
        $qByr->execute([$no_rawat]);
        $total_terbayar = floatval($qByr->fetchColumn());

        $qBillingCount = $pdo->prepare("SELECT COUNT(*) FROM mlite_billing WHERE no_rawat = ? AND kd_billing LIKE 'RJ%'");
        $qBillingCount->execute([$no_rawat]);
        $hasBilling = ($qBillingCount->fetchColumn() > 0);

        $status_bayar = ($hasBilling && ($total_layanan == 0 || $total_terbayar >= $total_layanan)) ? 'Sudah Bayar' : 'Belum Bayar';
        $updStts = $pdo->prepare("UPDATE reg_periksa SET status_bayar = ? WHERE no_rawat = ?");
        $updStts->execute([$status_bayar, $no_rawat]);

        if (!$hasBilling) {
          $pdo->prepare("UPDATE rawat_jl_dr SET stts_bayar = 'Belum' WHERE no_rawat = ? AND stts_bayar = 'Sudah'")->execute([$no_rawat]);
          $pdo->prepare("UPDATE rawat_jl_pr SET stts_bayar = 'Belum' WHERE no_rawat = ? AND stts_bayar = 'Sudah'")->execute([$no_rawat]);
          $pdo->prepare("UPDATE rawat_jl_drpr SET stts_bayar = 'Belum' WHERE no_rawat = ? AND stts_bayar = 'Sudah'")->execute([$no_rawat]);
        }

        echo json_encode([
          'status' => 'success',
          'message' => 'Riwayat pembayaran dengan No. Faktur <strong>' . htmlspecialchars($kd_billing, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> berhasil dihapus.'
        ]);
        exit();
      } catch (\Throwable $e) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Gagal menghapus pembayaran: ' . $e->getMessage()
        ]);
        exit();
      }
    }

    public function anyHapus_billing()
    {
      return $this->anyHapusBilling();
    }

    public function postHapusBilling()
    {
      return $this->anyHapusBilling();
    }

    public function postHapus_billing()
    {
      return $this->anyHapusBilling();
    }

    public function apiSimpanKasir()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $username = $this->core->checkAuth('POST');
        if (!$this->core->checkPermission($username, 'can_create', 'kasir_rawat_jalan')) {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to access this resource']);
            exit;
        }

        $payload['id_user'] = $this->db('mlite_users')->where('username', $username)->oneArray()['id'];
        $payload['kd_billing'] = 'RJ.'.date('d.m.Y.H.i.s');
        $payload['tgl_billing'] = $payload['tgl_bayar'] ?? date('Y-m-d');
        $payload['jam_billing'] = $payload['jam_bayar'] ?? date('H:i:s');
        
        unset($payload['tgl_bayar']);
        unset($payload['jam_bayar']);

        if($this->settings('keuangan', 'jurnal_kasir') == 1 && empty($_POST['tidak_diklaim'])) {
            // jurnal_pendaftaran //
            if(isset($payload['jurnal_pendaftaran']) && $payload['jurnal_pendaftaran'] != '0' && $payload['jurnal_pendaftaran'] != 0) {
                $no_jurnal_pendaftaran = $this->core->setNoJurnal();
                $keterangan = $this->db('mlite_rekening')
                    ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_pendaftaran'))
                    ->oneArray();
                $jumlah = $payload['jurnal_pendaftaran'];
                
                $query_jurnal_pendaftaran = $this->db('mlite_jurnal')->save([
                    'no_jurnal' => $no_jurnal_pendaftaran,
                    'no_bukti' => $payload['no_rawat'],
                    'tgl_jurnal' => date('Y-m-d'),
                    'jenis' => 'U',
                    'kegiatan' => $keterangan['nm_rek'],
                    'keterangan' => $keterangan['nm_rek'].' '.$payload['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
                ]);
                
                if($query_jurnal_pendaftaran) {
                    // DEBET: Kas/Piutang Usaha (Aset bertambah)
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_pendaftaran,
                        'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'), // Setting untuk akun kas
                        'arus_kas' => '1',
                        'debet' => $jumlah,
                        'kredit' => '0'
                    ]);
                    
                    // KREDIT: Pendapatan Pendaftaran (Pendapatan bertambah)
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_pendaftaran,
                        'kd_rek' => $this->settings('keuangan', 'akun_kredit_pendaftaran'),
                        'arus_kas' => '0',
                        'debet' => '0',
                        'kredit' => $jumlah
                    ]);
                }
            }
            // End jurnal_pendaftaran // 

            // jurnal_tindakan_ralan //
            if(isset($payload['jurnal_tindakan_ralan']) && $payload['jurnal_tindakan_ralan'] != '0' && $payload['jurnal_tindakan_ralan'] != 0) {
                $no_jurnal_tindakan_ralan = $this->core->setNoJurnal();
                $keterangan = $this->db('mlite_rekening')
                    ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_tindakan'))
                    ->oneArray();
                $jumlah = $payload['jurnal_tindakan_ralan'];
                
                $query_jurnal_tindakan_ralan = $this->db('mlite_jurnal')->save([
                    'no_jurnal' => $no_jurnal_tindakan_ralan,
                    'no_bukti' => $payload['no_rawat'],
                    'tgl_jurnal' => date('Y-m-d'),
                    'jenis' => 'U',
                    'kegiatan' => $keterangan['nm_rek'],
                    'keterangan' => $keterangan['nm_rek'].' '.$payload['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
                ]);
                
                if($query_jurnal_tindakan_ralan) {
                    // DEBET: Kas/Piutang Usaha
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_tindakan_ralan,
                        'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                        'arus_kas' => '1',
                        'debet' => $jumlah,
                        'kredit' => '0'
                    ]);
                    
                    // KREDIT: Pendapatan Tindakan
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_tindakan_ralan,
                        'kd_rek' => $this->settings('keuangan', 'akun_kredit_tindakan'),
                        'arus_kas' => '0',
                        'debet' => '0',
                        'kredit' => $jumlah
                    ]);
                }
            }
            // End jurnal_tindakan_ralan //

            // jurnal_obat_bhp //
            if(isset($payload['jurnal_obat_bhp']) && $payload['jurnal_obat_bhp'] != '0' && $payload['jurnal_obat_bhp'] != 0) {
                $no_jurnal_obat_bhp = $this->core->setNoJurnal();
                $keterangan = $this->db('mlite_rekening')
                    ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_obat_bhp'))
                    ->oneArray();
                $jumlah = $payload['jurnal_obat_bhp'];
                
                $query_jurnal_obat_bhp = $this->db('mlite_jurnal')->save([
                    'no_jurnal' => $no_jurnal_obat_bhp,
                    'no_bukti' => $payload['no_rawat'],
                    'tgl_jurnal' => date('Y-m-d'),
                    'jenis' => 'U',
                    'kegiatan' => $keterangan['nm_rek'],
                    'keterangan' => $keterangan['nm_rek'].' '.$payload['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
                ]);
                
                if($query_jurnal_obat_bhp) {
                    // DEBET: Kas/Piutang Usaha
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_obat_bhp,
                        'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                        'arus_kas' => '1',
                        'debet' => $jumlah,
                        'kredit' => '0'
                    ]);
                    
                    // KREDIT: Pendapatan Obat dan BHP
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_obat_bhp,
                        'kd_rek' => $this->settings('keuangan', 'akun_kredit_obat_bhp'),
                        'arus_kas' => '0',
                        'debet' => '0',
                        'kredit' => $jumlah
                    ]);
                }
            }
            // End jurnal_obat_bhp //

            // jurnal_laboratorium //
            if(isset($payload['jurnal_laboratorium']) && $payload['jurnal_laboratorium'] != '0' && $payload['jurnal_laboratorium'] != 0) {
                $no_jurnal_laboratorium = $this->core->setNoJurnal();
                $keterangan = $this->db('mlite_rekening')
                    ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_laboratorium'))
                    ->oneArray();
                $jumlah = $payload['jurnal_laboratorium'];
                
                $query_jurnal_laboratorium = $this->db('mlite_jurnal')->save([
                    'no_jurnal' => $no_jurnal_laboratorium,
                    'no_bukti' => $payload['no_rawat'],
                    'tgl_jurnal' => date('Y-m-d'),
                    'jenis' => 'U',
                    'kegiatan' => $keterangan['nm_rek'],
                    'keterangan' => $keterangan['nm_rek'].' '.$payload['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
                ]);
                
                if($query_jurnal_laboratorium) {
                    // DEBET: Kas/Piutang Usaha
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_laboratorium,
                        'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                        'arus_kas' => '1',
                        'debet' => $jumlah,
                        'kredit' => '0'
                    ]);
                    
                    // KREDIT: Pendapatan Laboratorium
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_laboratorium,
                        'kd_rek' => $this->settings('keuangan', 'akun_kredit_laboratorium'),
                        'arus_kas' => '0',
                        'debet' => '0',
                        'kredit' => $jumlah
                    ]);
                }
            }
            // End jurnal_laboratorium //

            // jurnal_radiologi//
            if(isset($payload['jurnal_radiologi']) && $payload['jurnal_radiologi'] != '0' && $payload['jurnal_radiologi'] != 0) {
                $no_jurnal_radiologi = $this->core->setNoJurnal();
                $keterangan = $this->db('mlite_rekening')
                    ->where('kd_rek', $this->settings('keuangan', 'akun_kredit_radiologi'))
                    ->oneArray();
                $jumlah = $payload['jurnal_radiologi'];
                
                $query_jurnal_radiologi = $this->db('mlite_jurnal')->save([
                    'no_jurnal' => $no_jurnal_radiologi,
                    'no_bukti' => $payload['no_rawat'],
                    'tgl_jurnal' => date('Y-m-d'),
                    'jenis' => 'U',
                    'kegiatan' => $keterangan['nm_rek'],
                    'keterangan' => $keterangan['nm_rek'].' '.$payload['no_rawat'].'. Diposting oleh '.$this->core->getUserInfo('fullname', null, true).'.'
                ]);
                
                if($query_jurnal_radiologi) {
                    // DEBET: Kas/Piutang Usaha
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_radiologi,
                        'kd_rek' => $this->settings('keuangan', 'akun_debet_kas'),
                        'arus_kas' => '1',
                        'debet' => $jumlah,
                        'kredit' => '0'
                    ]);
                    
                    // KREDIT: Pendapatan Radiologi
                    $this->db('mlite_detailjurnal')->save([
                        'no_jurnal' => $no_jurnal_radiologi,
                        'kd_rek' => $this->settings('keuangan', 'akun_kredit_radiologi'),
                        'arus_kas' => '0',
                        'debet' => '0',
                        'kredit' => $jumlah
                    ]);
                }
            }
            // End jurnal_radiologi //
        }

        unset($payload['jurnal_pendaftaran']);
        unset($payload['jurnal_tindakan_ralan']);
        unset($payload['jurnal_obat_bhp']);
        unset($payload['jurnal_laboratorium']);
        unset($payload['jurnal_radiologi']);

        // Pastikan tidak ada field lain yang tidak dikenali oleh tabel mlite_billing
        // Filter payload hanya untuk kolom yang ada (jika kita tahu strukturnya, atau biarkan DB wrapper menangani)
        // Namun, jika DB wrapper menghapus field unknown tanpa error, maka insert bisa berhasil tapi datanya kosong.
        
        // Cek apakah data sudah ada sebelumnya? (Mencegah duplikat jika no_rawat+kd_billing unik?)
        // kd_billing baru digenerate, jadi harusnya unik.

        // echo json_encode(['status' => 'error', 'message' => json_encode($payload)]);
        try {
            $query = $this->db('mlite_billing')->save([
                'kd_billing' => $payload['kd_billing'],
                'no_rawat' => $payload['no_rawat'],
                'jumlah_total' => $payload['jumlah_harus_bayar'],
                'potongan' => $payload['potongan'],
                'jumlah_harus_bayar' => $payload['jumlah_harus_bayar'] - $payload['potongan'],
                'jumlah_bayar' => $payload['bayar'],
                'tgl_billing' => $payload['tgl_billing'],
                'jam_billing' => $payload['jam_billing'], 
                'id_user' => $payload['id_user'],
            ]);
            if($query) {
                $this->db('reg_periksa')->where('no_rawat', $payload['no_rawat'])->update(['status_bayar' => 'Sudah Bayar']);
                echo json_encode(['status' => 'success', 'message' => 'Pembayaran berhasil disimpan']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pembayaran']);
            }
        } catch (\PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function apiHapusItem()
    {
        try {
            $payload = json_decode(file_get_contents('php://input'), true);
            $type = $payload['type'] ?? '';
            
            if ($type === 'tindakan') {
                if($payload['provider'] == 'rawat_jl_dr') {
                    $this->db('rawat_jl_dr')
                    ->where('no_rawat', $payload['no_rawat'])
                    ->where('kd_jenis_prw', $payload['kd_jenis_prw'])
                    ->where('tgl_perawatan', $payload['tgl_perawatan'])
                    ->where('jam_rawat', $payload['jam_rawat'])
                    ->delete();
                }
                if($payload['provider'] == 'rawat_jl_pr') {
                    $this->db('rawat_jl_pr')
                    ->where('no_rawat', $payload['no_rawat'])
                    ->where('kd_jenis_prw', $payload['kd_jenis_prw'])
                    ->where('tgl_perawatan', $payload['tgl_perawatan'])
                    ->where('jam_rawat', $payload['jam_rawat'])
                    ->delete();
                }
                if($payload['provider'] == 'rawat_jl_drpr') {
                    $this->db('rawat_jl_drpr')
                    ->where('no_rawat', $payload['no_rawat'])
                    ->where('kd_jenis_prw', $payload['kd_jenis_prw'])
                    ->where('tgl_perawatan', $payload['tgl_perawatan'])
                    ->where('jam_rawat', $payload['jam_rawat'])
                    ->delete();
                }
            }
            
            if ($type === 'obat') {
                $payload['jml'] = $payload['jml'] ?? $payload['jumlah'] ?? 0;
                $kd_bangsal = $payload['kd_bangsal'] ?? $this->settings->get('farmasi.deporalan');
                $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $payload['kode_brng'])->where('kd_bangsal', $kd_bangsal)->oneArray();

                $this->db('gudangbarang')
                    ->where('kode_brng', $payload['kode_brng'])
                    ->where('kd_bangsal', $kd_bangsal)
                    ->update([
                        'stok' => $get_gudangbarang['stok'] + $payload['jml']
                    ]);

                $this->db('riwayat_barang_medis')
                    ->save([
                        'kode_brng' => $payload['kode_brng'],
                        'stok_awal' => $get_gudangbarang['stok'],
                        'masuk' => $payload['jml'],
                        'keluar' => '0',
                        'stok_akhir' => $get_gudangbarang['stok'] + $payload['jml'],
                        'posisi' => 'Pemberian Obat',
                        'tanggal' => $payload['tgl_peresepan'],
                        'jam' => $payload['jam_peresepan'],
                        'petugas' => $this->core->getUserInfo('fullname', null, true),
                        'kd_bangsal' => $kd_bangsal,
                        'status' => 'Hapus',
                        'no_batch' => $get_gudangbarang['no_batch'],
                        'no_faktur' => $get_gudangbarang['no_faktur'],
                        'keterangan' => $payload['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']))
                    ]);

                $this->db('detail_pemberian_obat')
                    ->where('tgl_perawatan', $payload['tgl_peresepan'])
                    ->where('jam', $payload['jam_peresepan'])
                    ->where('no_rawat', $payload['no_rawat'])
                    ->where('kode_brng', $payload['kode_brng'])
                    ->where('jml', $payload['jml'])
                    ->where('status', 'Ralan')
                    ->where('kd_bangsal', $kd_bangsal)
                    ->delete();
            }
            
            if ($type === 'lab') {
                $this->db('periksa_lab')
                ->where('no_rawat', $payload['no_rawat'])
                ->where('kd_jenis_prw', $payload['kd_jenis_prw'])
                ->where('tgl_periksa', $payload['tgl_perawatan'])
                ->where('jam', $payload['jam_rawat'])
                ->where('status', 'Ralan')
                ->delete();
            }
            
            if ($type === 'rad') {
                $this->db('periksa_radiologi')
                ->where('no_rawat', $payload['no_rawat'])
                ->where('kd_jenis_prw', $payload['kd_jenis_prw'])
                ->where('tgl_periksa', $payload['tgl_perawatan'])
                ->where('jam', $payload['jam_rawat'])
                ->where('status', 'Ralan')
                ->delete();
            }

            echo json_encode(['status' => 'success', 'message' => 'Item berhasil dihapus ' . json_encode($payload)]);

        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function apiSimpanItem()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $kat = $payload['kat'] ?? '';

        try {
            if($kat == 'tindakan') {
                $jns_perawatan = $this->db('jns_perawatan')->where('kd_jenis_prw', $payload['kd_jenis_prw'])->oneArray();
                if($payload['provider'] == 'rawat_jl_dr') {
                    for ($i = 0; $i < $payload['jml_tindakan']; $i++) {
                        $this->db('rawat_jl_dr')->save([
                            'no_rawat' => $payload['no_rawat'],
                            'kd_jenis_prw' => $payload['kd_jenis_prw'],
                            'kd_dokter' => $payload['kode_provider'],
                            'tgl_perawatan' => $payload['tgl_perawatan'],
                            'jam_rawat' => date('H:i:s', strtotime($payload['jam_rawat']. ' +'.$i.'0 seconds')),
                            'material' => $jns_perawatan['material'],
                            'bhp' => $jns_perawatan['bhp'],
                            'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
                            'kso' => $jns_perawatan['kso'],
                            'menejemen' => $jns_perawatan['menejemen'],
                            'biaya_rawat' => $jns_perawatan['total_byrdr'],
                            'stts_bayar' => 'Belum'
                        ]);
                    }
                }
                if($payload['provider'] == 'rawat_jl_pr') {
                    for ($i = 0; $i < $payload['jml_tindakan']; $i++) {
                        $this->db('rawat_jl_pr')->save([
                            'no_rawat' => $payload['no_rawat'],
                            'kd_jenis_prw' => $payload['kd_jenis_prw'],
                            'nip' => $payload['kode_provider2'],
                            'tgl_perawatan' => $payload['tgl_perawatan'],
                            'jam_rawat' => date('H:i:s', strtotime($payload['jam_rawat']. ' +'.$i.'0 seconds')),
                            'material' => $jns_perawatan['material'],
                            'bhp' => $jns_perawatan['bhp'],
                            'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
                            'kso' => $jns_perawatan['kso'],
                            'menejemen' => $jns_perawatan['menejemen'],
                            'biaya_rawat' => $jns_perawatan['total_byrpr'],
                            'stts_bayar' => 'Belum'
                        ]);
                    }
                }
                if($payload['provider'] == 'rawat_jl_drpr') {
                    for ($i = 0; $i < $payload['jml_tindakan']; $i++) {
                        $this->db('rawat_jl_drpr')->save([
                            'no_rawat' => $payload['no_rawat'],
                            'kd_jenis_prw' => $payload['kd_jenis_prw'],
                            'kd_dokter' => $payload['kode_provider'],
                            'nip' => $payload['kode_provider2'],
                            'tgl_perawatan' => $payload['tgl_perawatan'],
                            'jam_rawat' => date('H:i:s', strtotime($payload['jam_rawat']. ' +'.$i.'0 seconds')),
                            'material' => $jns_perawatan['material'],
                            'bhp' => $jns_perawatan['bhp'],
                            'tarif_tindakandr' => $jns_perawatan['tarif_tindakandr'],
                            'tarif_tindakanpr' => $jns_perawatan['tarif_tindakanpr'],
                            'kso' => $jns_perawatan['kso'],
                            'menejemen' => $jns_perawatan['menejemen'],
                            'biaya_rawat' => $jns_perawatan['total_byrdrpr'],
                            'stts_bayar' => 'Belum'
                        ]);
                    }
                }
            }

            if($kat == 'obat') {
                $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $payload['kd_jenis_prw'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
                $get_databarang = $this->db('databarang')->where('kode_brng', $payload['kd_jenis_prw'])->oneArray();

                $this->db('gudangbarang')
                    ->where('kode_brng', $payload['kd_jenis_prw'])
                    ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
                    ->update([
                        'stok' => $get_gudangbarang['stok'] - $payload['jml']
                    ]);

                $this->db('riwayat_barang_medis')
                    ->save([
                        'kode_brng' => $payload['kd_jenis_prw'],
                        'stok_awal' => $get_gudangbarang['stok'],
                        'masuk' => '0',
                        'keluar' => $payload['jml'],
                        'stok_akhir' => $get_gudangbarang['stok'] - $payload['jml'],
                        'posisi' => 'Pemberian Obat',
                        'tanggal' => $payload['tgl_perawatan'],
                        'jam' => $payload['jam_rawat'],
                        'petugas' => $this->core->getUserInfo('fullname', null, true),
                        'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                        'status' => 'Simpan',
                        'no_batch' => $get_gudangbarang['no_batch'],
                        'no_faktur' => $get_gudangbarang['no_faktur'],
                        'keterangan' => $payload['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']))
                    ]);

                $this->db('detail_pemberian_obat')
                    ->save([
                        'tgl_perawatan' => $payload['tgl_perawatan'],
                        'jam' => $payload['jam_rawat'],
                        'no_rawat' => $payload['no_rawat'],
                        'kode_brng' => $payload['kd_jenis_prw'],
                        'h_beli' => $get_databarang['h_beli'],
                        'biaya_obat' => $payload['biaya'],
                        'jml' => $payload['jml'],
                        'embalase' => '0',
                        'tuslah' => '0',
                        'total' => $payload['biaya'] * $payload['jml'],
                        'status' => 'Ralan',
                        'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                        'no_batch' => $get_gudangbarang['no_batch'],
                        'no_faktur' => $get_gudangbarang['no_faktur']
                    ]);

                if (isset($payload['aturan_pakai'])) {
                    $this->db('aturan_pakai')
                    ->save([
                        'tgl_perawatan' => $payload['tgl_perawatan'],
                        'jam' => $payload['jam_rawat'],
                        'no_rawat' => $payload['no_rawat'],
                        'kode_brng' => $payload['kd_jenis_prw'],
                        'aturan' => $payload['aturan_pakai']
                    ]);
                }
            }

            if($kat == 'racikan') {
                $no_racik = $this->db('obat_racikan')->where('no_rawat', $payload['no_rawat'])->where('tgl_perawatan', $payload['tgl_perawatan'])->count();
                $no_racik = $no_racik+1;
                $this->db('obat_racikan')
                  ->save([
                    'tgl_perawatan' => $payload['tgl_perawatan'],
                    'jam' => $payload['jam_rawat'],
                    'no_rawat' => $payload['no_rawat'],
                    'no_racik' => $no_racik,
                    'nama_racik' => $payload['nama_racik'],
                    'kd_racik' => $payload['kd_jenis_prw'],
                    'jml_dr' => $payload['jml'],
                    'aturan_pakai' => $payload['aturan_pakai'],
                    'keterangan' => $payload['keterangan']
                  ]);
                
                $items = $payload['items'] ?? [];
                
                foreach ($items as $item) {
                  $get_gudangbarang = $this->db('gudangbarang')->where('kode_brng', $item['kode_brng'])->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))->oneArray();
                  $kapasitas = $this->db('databarang')->where('kode_brng', $item['kode_brng'])->oneArray();
                  
                  // Calculate total quantity needed: (packets * content per packet) / capacity
                  $jml = $payload['jml'] * $item['kandungan'];
                  $jml = round(($jml / ($kapasitas['kapasitas'] > 0 ? $kapasitas['kapasitas'] : 1)), 1);

                  $this->db('gudangbarang')
                  ->where('kode_brng', $item['kode_brng'])
                  ->where('kd_bangsal', $this->settings->get('farmasi.deporalan'))
                  ->update([
                    'stok' => $get_gudangbarang['stok'] - $jml
                  ]);

                  $this->db('riwayat_barang_medis')
                    ->save([
                      'kode_brng' => $item['kode_brng'],
                      'stok_awal' => $get_gudangbarang['stok'],
                      'masuk' => '0',
                      'keluar' => $jml,
                      'stok_akhir' => $get_gudangbarang['stok'] - $jml,
                      'posisi' => 'Pemberian Obat',
                      'tanggal' => $payload['tgl_perawatan'],
                      'jam' => $payload['jam_rawat'],
                      'petugas' => $this->core->getUserInfo('fullname', null, true),
                      'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                      'status' => 'Simpan',
                      'no_batch' => $get_gudangbarang['no_batch'],
                      'no_faktur' => $get_gudangbarang['no_faktur'],
                      'keterangan' => $payload['no_rawat'] . ' ' . $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']) . ' ' . $this->core->getPasienInfo('nm_pasien', $this->core->getRegPeriksaInfo('no_rkm_medis', $payload['no_rawat']))
                    ]);

                  $this->db('detail_pemberian_obat')
                    ->save([
                      'tgl_perawatan' => $payload['tgl_perawatan'],
                      'jam' => $payload['jam_rawat'],
                      'no_rawat' => $payload['no_rawat'],
                      'kode_brng' => $item['kode_brng'],
                      'h_beli' => $kapasitas['h_beli'],
                      'biaya_obat' => $kapasitas['dasar'],
                      'jml' => $jml,
                      'embalase' => '0',
                      'tuslah' => '0',
                      'total' => $kapasitas['dasar'] * $jml,
                      'status' => 'Ralan',
                      'kd_bangsal' => $this->settings->get('farmasi.deporalan'),
                      'no_batch' => $get_gudangbarang['no_batch'],
                      'no_faktur' => $get_gudangbarang['no_faktur']
                    ]);

                  $this->db('detail_obat_racikan')
                    ->save([
                      'tgl_perawatan' => $payload['tgl_perawatan'],
                      'jam' => $payload['jam_rawat'],
                      'no_rawat' => $payload['no_rawat'],
                      'no_racik' => $no_racik,
                      'kode_brng' => $item['kode_brng']
                    ]);          
                }        
            }

            if($kat == 'laboratorium') {
                $jns_perawatan = $this->db('jns_perawatan_lab')->where('kd_jenis_prw', $payload['kd_jenis_prw'])->oneArray();
                $this->db('periksa_lab')
                  ->save([
                    'no_rawat' => $payload['no_rawat'],
                    'nip' => $payload['kode_provider2'],
                    'kd_jenis_prw' => $payload['kd_jenis_prw'],
                    'tgl_periksa' => $payload['tgl_perawatan'],
                    'jam' => $payload['jam_rawat'],
                    'dokter_perujuk' => $payload['kode_provider'],
                    'bagian_rs' => $jns_perawatan['bagian_rs'],
                    'bhp' => $jns_perawatan['bhp'],
                    'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
                    'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
                    'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
                    'kso' => $jns_perawatan['kso'],
                    'menejemen' => $jns_perawatan['menejemen'],
                    'biaya' => $jns_perawatan['total_byr'],
                    'kd_dokter' => $this->settings->get('settings.pj_laboratorium'),
                    'status' => 'Ralan'
                  ]);
            }

            if($kat == 'radiologi') {
                $jns_perawatan = $this->db('jns_perawatan_radiologi')->where('kd_jenis_prw', $payload['kd_jenis_prw'])->oneArray();
                $this->db('periksa_radiologi')
                  ->save([
                    'no_rawat' => $payload['no_rawat'],
                    'nip' => $payload['kode_provider2'],
                    'kd_jenis_prw' => $payload['kd_jenis_prw'],
                    'tgl_periksa' => $payload['tgl_perawatan'],
                    'jam' => $payload['jam_rawat'],
                    'dokter_perujuk' => $payload['kode_provider'],
                    'bagian_rs' => $jns_perawatan['bagian_rs'],
                    'bhp' => $jns_perawatan['bhp'],
                    'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
                    'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
                    'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
                    'kso' => $jns_perawatan['kso'],
                    'menejemen' => $jns_perawatan['menejemen'],
                    'biaya' => $jns_perawatan['total_byr'],
                    'kd_dokter' => $this->settings->get('settings.pj_radiologi'),
                    'status' => 'Ralan'
                  ]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Item berhasil ditambahkan']);
        } catch (\PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function anyFaktur()
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $show = isset($_GET['show']) ? $_GET['show'] : "";
      switch($show){
       default:
        if($this->db('mlite_billing')->where('no_rawat', $_POST['no_rawat'])->like('kd_billing', 'RJ%')->oneArray()) {
          echo 'OK';
        }
        break;
        case "besar":
        $result = $this->db('mlite_billing')->where('no_rawat', $_GET['no_rawat'])->like('kd_billing', 'RJ%')->desc('id_billing')->oneArray();

        $result_detail['poliklinik'] = $this->db('poliklinik')
          ->join('reg_periksa', 'reg_periksa.kd_poli = poliklinik.kd_poli')
          ->where('reg_periksa.no_rawat', $_GET['no_rawat'])
          ->oneArray();

        $rawat_jl_dr_query = $this->db('rawat_jl_dr')
          ->select('jns_perawatan.nm_perawatan')
          ->select('rawat_jl_dr.stts_bayar')
          ->select(['biaya_rawat' => 'rawat_jl_dr.biaya_rawat'])
          ->select(['jml' => 'COUNT(rawat_jl_dr.kd_jenis_prw)'])
          ->select(['total_biaya_rawat_dr' => 'SUM(rawat_jl_dr.biaya_rawat)'])
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')
          ->where('rawat_jl_dr.no_rawat', $_GET['no_rawat'])
          ->group('jns_perawatan.nm_perawatan, rawat_jl_dr.stts_bayar')
          ->toArray();

        $total_rawat_jl_dr = 0;
        $total_coass_dr = 0;
        $result_detail['rawat_jl_dr'] = [];
        foreach ($rawat_jl_dr_query as $row) {
          if ($row['stts_bayar'] == 'Suspen') {
            $total_coass_dr += $row['total_biaya_rawat_dr'];
            $row['nm_perawatan'] .= ' (Tidak Di-claim)';
            $row['biaya_rawat'] = 0;
            $row['total_biaya_rawat_dr'] = 0;
          } else {
            $total_rawat_jl_dr += $row['biaya_rawat'];
          }
          $result_detail['rawat_jl_dr'][] = $row;
        }

        $rawat_jl_pr_query = $this->db('rawat_jl_pr')
          ->select('jns_perawatan.nm_perawatan')
          ->select('rawat_jl_pr.stts_bayar')
          ->select(['biaya_rawat' => 'rawat_jl_pr.biaya_rawat'])
          ->select(['jml' => 'COUNT(rawat_jl_pr.kd_jenis_prw)'])
          ->select(['total_biaya_rawat_pr' => 'SUM(rawat_jl_pr.biaya_rawat)'])
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_pr.kd_jenis_prw')
          ->where('rawat_jl_pr.no_rawat', $_GET['no_rawat'])
          ->group('jns_perawatan.nm_perawatan, rawat_jl_pr.stts_bayar')
          ->toArray();

        $total_rawat_jl_pr = 0;
        $total_coass_pr = 0;
        $result_detail['rawat_jl_pr'] = [];
        foreach ($rawat_jl_pr_query as $row) {
          if ($row['stts_bayar'] == 'Suspen') {
            $total_coass_pr += $row['total_biaya_rawat_pr'];
            $row['nm_perawatan'] .= ' (Tidak Di-claim)';
            $row['biaya_rawat'] = 0;
            $row['total_biaya_rawat_pr'] = 0;
          } else {
            $total_rawat_jl_pr += $row['biaya_rawat'];
          }
          $result_detail['rawat_jl_pr'][] = $row;
        }

        $rawat_jl_drpr_query = $this->db('rawat_jl_drpr')
          ->select('jns_perawatan.nm_perawatan')
          ->select('rawat_jl_drpr.stts_bayar')
          ->select(['biaya_rawat' => 'rawat_jl_drpr.biaya_rawat'])
          ->select(['jml' => 'COUNT(rawat_jl_drpr.kd_jenis_prw)'])
          ->select(['total_biaya_rawat_drpr' => 'SUM(rawat_jl_drpr.biaya_rawat)'])
          ->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_drpr.kd_jenis_prw')
          ->where('rawat_jl_drpr.no_rawat', $_GET['no_rawat'])
          ->group('jns_perawatan.nm_perawatan, rawat_jl_drpr.stts_bayar')
          ->toArray();

        $total_rawat_jl_drpr = 0;
        $total_coass_drpr = 0;
        $result_detail['rawat_jl_drpr'] = [];
        foreach ($rawat_jl_drpr_query as $row) {
          if ($row['stts_bayar'] == 'Suspen') {
            $total_coass_drpr += $row['total_biaya_rawat_drpr'];
            $row['nm_perawatan'] .= ' (Tidak Di-claim)';
            $row['biaya_rawat'] = 0;
            $row['total_biaya_rawat_drpr'] = 0;
          } else {
            $total_rawat_jl_drpr += $row['biaya_rawat'];
          }
          $result_detail['rawat_jl_drpr'][] = $row;
        }

        $total_coass_all = $total_coass_dr + $total_coass_pr + $total_coass_drpr;
        $this->tpl->set('total_coass_all', $total_coass_all);

        // Exclude racikan items from detail_pemberian_obat
        $exclude_jams = [];
        $racikan_jams = $this->db('detail_obat_racikan')->where('no_rawat', $_GET['no_rawat'])->toArray();
        foreach($racikan_jams as $rj) {
            $exclude_jams[] = $rj['jam'];
        }

        $query_pemberian = $this->db('detail_pemberian_obat')
          ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
          ->where('no_rawat', $_GET['no_rawat'])
          ->where('detail_pemberian_obat.status', 'Ralan');
        
        if(!empty($exclude_jams)) {
            $query_pemberian->notIn('jam', $exclude_jams);
        }

        $result_detail['detail_pemberian_obat'] = $query_pemberian->toArray();

        // Fetch racikan items
        $rows_obat_racikan = $this->db('obat_racikan')
          ->where('no_rawat', $_GET['no_rawat'])
          ->toArray();

        foreach ($rows_obat_racikan as $header) {
          $ingredients_map = $this->db('detail_obat_racikan')
            ->where('no_rawat', $header['no_rawat'])
            ->where('no_racik', $header['no_racik'])
            ->toArray();

          $total_racikan = 0;
          $ingredient_rows = [];

          foreach ($ingredients_map as $map) {
            $row = $this->db('detail_pemberian_obat')
                ->join('databarang', 'databarang.kode_brng=detail_pemberian_obat.kode_brng')
                ->where('detail_pemberian_obat.no_rawat', $map['no_rawat'])
                ->where('detail_pemberian_obat.kode_brng', $map['kode_brng'])
                ->where('detail_pemberian_obat.tgl_perawatan', $map['tgl_perawatan'])
                ->where('detail_pemberian_obat.jam', $map['jam'])
                ->where('detail_pemberian_obat.status', 'Ralan')
                ->oneArray();

            if ($row) {
                $total_racikan += $row['total'];

                $row['nama_brng'] = "&nbsp;&nbsp;&nbsp;&nbsp; - " . $row['nama_brng'];
                $row['total'] = 0;
                $row['jml'] = 0;
                $row['biaya_obat'] = 0;
                $ingredient_rows[] = $row;
            }
          }

          $header_row = [];
          $header_row['nama_brng'] = ">> Racikan " . $header['no_racik'] . ": " . $header['nama_racik'] . " (" . $header['jml_dr'] . " Bungkus)";
          $header_row['jml'] = $header['jml_dr'];
          
          if ($header['jml_dr'] > 0) {
              $header_row['biaya_obat'] = $total_racikan / $header['jml_dr'];
          } else {
              $header_row['biaya_obat'] = $total_racikan;
          }

          $header_row['total'] = $total_racikan;
          
          $result_detail['detail_pemberian_obat'][] = $header_row;
          
          foreach ($ingredient_rows as $row) {
              $result_detail['detail_pemberian_obat'][] = $row;
          }
        }

        $total_detail_pemberian_obat = 0;
        foreach ($result_detail['detail_pemberian_obat'] as $row) {
          $total_detail_pemberian_obat += $row['total'];
        }

        $result_detail['periksa_lab'] = $this->db('periksa_lab')
          ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
          ->where('no_rawat', $_GET['no_rawat'])
          ->where('periksa_lab.status', 'Ralan')
          ->toArray();

        $total_periksa_lab = 0;
        foreach ($result_detail['periksa_lab'] as $row) {
          $total_periksa_lab += $row['biaya'];
        }

        $result_detail['periksa_radiologi'] = $this->db('periksa_radiologi')
          ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw=periksa_radiologi.kd_jenis_prw')
          ->where('no_rawat', $_GET['no_rawat'])
          ->where('periksa_radiologi.status', 'Ralan')
          ->toArray();

        $total_periksa_radiologi = 0;
        foreach ($result_detail['periksa_radiologi'] as $row) {
          $total_periksa_radiologi += $row['biaya'];
        }

        $jumlah_total_operasi = 0;
        $operasis = $this->db('operasi')->join('paket_operasi', 'paket_operasi.kode_paket=operasi.kode_paket')->where('no_rawat', $_GET['no_rawat'])->where('operasi.status', 'Ralan')->toArray();
        $result_detail['operasi'] = [];
        foreach ($operasis as $operasi) {
          $operasi['jumlah'] = $operasi['biayaoperator1']+$operasi['biayaoperator2']+$operasi['biayaoperator3']+$operasi['biayaasisten_operator1']+$operasi['biayaasisten_operator2']+$operasi['biayadokter_anak']+$operasi['biayaperawaat_resusitas']+$operasi['biayadokter_anestesi']+$operasi['biayaasisten_anestesi']+$operasi['biayabidan']+$operasi['biayaperawat_luar'];
          $jumlah_total_operasi += $operasi['jumlah'];
          $result_detail['operasi'][] = $operasi;
        }
        $jumlah_total_obat_operasi = 0;
        $obat_operasis = $this->db('beri_obat_operasi')->join('obatbhp_ok', 'obatbhp_ok.kd_obat=beri_obat_operasi.kd_obat')->where('no_rawat', $_GET['no_rawat'])->toArray();
        $result_detail['obat_operasi'] = [];
        foreach ($obat_operasis as $obat_operasi) {
          $obat_operasi['harga'] = $obat_operasi['hargasatuan'] * $obat_operasi['jumlah'];
          $jumlah_total_obat_operasi += $obat_operasi['harga'];
          $result_detail['obat_operasi'][] = $obat_operasi;
        }

        $result_detail['resep_pulang'] = $this->db('resep_pulang')
          ->join('databarang', 'databarang.kode_brng=resep_pulang.kode_brng')
          ->where('resep_pulang.no_rawat', $_GET['no_rawat'])
          ->toArray();

        $result_detail['tambahan_biaya'] = $this->db('tambahan_biaya')
          ->where('no_rawat', $_GET['no_rawat'])
          ->toArray();

        $coass_cats = $this->getCoassKategori($_GET['no_rawat']);

        // Pendaftaran
        if (!empty($coass_cats['pendaftaran']) && isset($result_detail['poliklinik'])) {
          $reg_biaya = floatval(($result_detail['poliklinik']['stts_daftar'] ?? '') == 'Lama' ? ($result_detail['poliklinik']['registrasilama'] ?? 0) : ($result_detail['poliklinik']['registrasi'] ?? 0));
          $total_coass_all += $reg_biaya;
          $result_detail['poliklinik']['nm_poli'] .= ' (Tidak Di-claim)';
          $result_detail['poliklinik']['registrasi'] = 0;
        }

        // Obat dan BHP
        if (!empty($coass_cats['obat']) && !empty($result_detail['detail_pemberian_obat'])) {
          foreach ($result_detail['detail_pemberian_obat'] as $k => $row) {
            $total_coass_all += floatval($row['total'] ?? 0);
            $result_detail['detail_pemberian_obat'][$k]['biaya_obat'] = 0;
            $result_detail['detail_pemberian_obat'][$k]['total'] = 0;
            $result_detail['detail_pemberian_obat'][$k]['nama_brng'] .= ' (Tidak Di-claim)';
          }
          $total_detail_pemberian_obat = 0;
        }

        // Laboratorium
        if (!empty($coass_cats['laboratorium']) && !empty($result_detail['periksa_lab'])) {
          foreach ($result_detail['periksa_lab'] as $k => $row) {
            $total_coass_all += floatval($row['biaya'] ?? 0);
            $result_detail['periksa_lab'][$k]['biaya'] = 0;
            $result_detail['periksa_lab'][$k]['nm_perawatan'] .= ' (Tidak Di-claim)';
          }
          $total_periksa_lab = 0;
        }

        // Radiologi
        if (!empty($coass_cats['radiologi']) && !empty($result_detail['periksa_radiologi'])) {
          foreach ($result_detail['periksa_radiologi'] as $k => $row) {
            $total_coass_all += floatval($row['biaya'] ?? 0);
            $result_detail['periksa_radiologi'][$k]['biaya'] = 0;
            $result_detail['periksa_radiologi'][$k]['nm_perawatan'] .= ' (Tidak Di-claim)';
          }
          $total_periksa_radiologi = 0;
        }

        // Operasi
        if (!empty($coass_cats['operasi'])) {
          if (!empty($result_detail['operasi'])) {
            foreach ($result_detail['operasi'] as $k => $row) {
              $total_coass_all += floatval($row['jumlah'] ?? 0);
              $result_detail['operasi'][$k]['jumlah'] = 0;
              $result_detail['operasi'][$k]['nm_perawatan'] .= ' (Tidak Di-claim)';
            }
          }
          if (!empty($result_detail['obat_operasi'])) {
            foreach ($result_detail['obat_operasi'] as $k => $row) {
              $total_coass_all += floatval($row['harga'] ?? 0);
              $result_detail['obat_operasi'][$k]['harga'] = 0;
            }
          }
        }

        // Obat Pulang
        if (!empty($coass_cats['obat_pulang']) && !empty($result_detail['resep_pulang'])) {
          foreach ($result_detail['resep_pulang'] as $k => $row) {
            $total_coass_all += floatval($row['total'] ?? 0);
            $result_detail['resep_pulang'][$k]['total'] = 0;
            $result_detail['resep_pulang'][$k]['nama_brng'] .= ' (Tidak Di-claim)';
          }
        }

        // Biaya Tambahan
        if (!empty($coass_cats['tambahan']) && !empty($result_detail['tambahan_biaya'])) {
          foreach ($result_detail['tambahan_biaya'] as $k => $row) {
            $total_coass_all += floatval($row['besar_biaya'] ?? 0);
            $result_detail['tambahan_biaya'][$k]['besar_biaya'] = 0;
            $result_detail['tambahan_biaya'][$k]['nama_biaya'] .= ' (Tidak Di-claim)';
          }
        }

        $this->tpl->set('total_coass_all', $total_coass_all);

        $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $_GET['no_rawat'])->oneArray();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();


        /* ===============================
        * QR CODE
        * =============================== */
        $qr = QRCode::getMinimumQRCode(
            $this->core->getUserInfo('fullname', null, true),
            QR_ERROR_CORRECT_LEVEL_L
        );
        $im = $qr->createImage(4,4);
        imagepng($im, BASE_DIR.'/'.ADMIN.'/tmp/qrcode.png');
        imagedestroy($im);

        $qrCode = url()."/".ADMIN."/tmp/qrcode.png";

        /* ===============================
        * PREPARE TEMPLATE DATA
        * =============================== */
        $riwayat_pembayaran = $this->db('mlite_billing')
          ->where('no_rawat', $_GET['no_rawat'])
          ->like('kd_billing', 'RJ%')
          ->asc('id_billing')
          ->toArray();
        $total_terbayar_semua = 0;
        foreach ($riwayat_pembayaran as $k => $rp) {
          $total_terbayar_semua += floatval($rp['jumlah_bayar']);
          $riwayat_pembayaran[$k]['items'] = $this->db('mlite_billing_rincian')
            ->where('kd_billing', $rp['kd_billing'])
            ->where('bayar', '>', 0)
            ->toArray();
        }
        $this->tpl->set('riwayat_pembayaran', $riwayat_pembayaran);
        $this->tpl->set('total_terbayar_semua', $total_terbayar_semua);

        $this->tpl->set('billing_obat', $this->settings->get('settings.billing_obat'));
        $this->tpl->set('wagateway', $this->settings->get('wagateway'));
        $this->tpl->set('billing', $result);
        $this->tpl->set('total_billing_obat', $total_detail_pemberian_obat);
        $this->tpl->set('billing_besar_detail', $result_detail);
        $this->tpl->set('pasien', $pasien);
        $this->tpl->set('qrCode', $qrCode);
        $this->tpl->set('fullname', $this->core->getUserInfo('fullname', null, true));

        /* ===============================
        * RENDER HTML SEKALI
        * =============================== */
        $html = $this->draw('billing.besar.html');

        /* ===============================
        * GENERATE PDF
        * =============================== */
        if (file_exists(UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf')) {
            unlink(UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf');
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P'
        ]);

        $mpdf->WriteHTML(
            $this->core->setPrintCss(),
            \Mpdf\HTMLParserMode::HEADER_CSS
        );

        $mpdf->WriteHTML($html);

        $mpdf->Output(
            UPLOADS.'/invoices/'.$result['kd_billing'].'.pdf',
            'F'
        );

        /* ===============================
        * OUTPUT HTML (PREVIEW)
        * =============================== */
        echo $html;
        break;
        case "kecil":
        $kd_billing = isset($_GET['kd_billing']) ? $_GET['kd_billing'] : '';
        if (!empty($kd_billing)) {
          $result = $this->db('mlite_billing')->where('kd_billing', $kd_billing)->oneArray();
        } else {
          $result = $this->db('mlite_billing')->where('no_rawat', $_GET['no_rawat'])->like('kd_billing', 'RJ%')->desc('id_billing')->oneArray();
        }
        $no_rawat = $result['no_rawat'] ?? ($_GET['no_rawat'] ?? '');
        $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $no_rawat)->oneArray();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();

        // Ambil rincian alokasi per pos untuk faktur ini
        $rincian_alokasi = [];
        if (!empty($result['kd_billing'])) {
          $rincian_alokasi = $this->db('mlite_billing_rincian')
            ->where('kd_billing', $result['kd_billing'])
            ->where('bayar', '>', 0)
            ->toArray();
        }

        // Cari petugas kasir yang mencatat faktur ini
        $kasir_faktur = $this->db('mlite_users')->where('id', $result['id_user'] ?? 0)->oneArray();
        $nama_kasir = !empty($kasir_faktur['fullname']) ? $kasir_faktur['fullname'] : $this->core->getUserInfo('fullname', null, true);

        // Hitung total akumulasi sampai faktur ini
        $total_bayar_semua = 0;
        $semua_faktur = $this->db('mlite_billing')
          ->where('no_rawat', $no_rawat)
          ->like('kd_billing', 'RJ%')
          ->toArray();
        foreach ($semua_faktur as $sf) {
          if ($sf['id_billing'] <= ($result['id_billing'] ?? 0)) {
            $total_bayar_semua += floatval($sf['jumlah_bayar']);
          }
        }

        $coass_cats = $this->getCoassKategori($no_rawat);
        $total_coass_all = 0;
        $pdo = $this->db()->pdo();

        // Pendaftaran
        if (!empty($coass_cats['pendaftaran'])) {
          $qPol = $pdo->prepare("SELECT registrasi, registrasilama, stts_daftar FROM poliklinik JOIN reg_periksa ON reg_periksa.kd_poli=poliklinik.kd_poli WHERE reg_periksa.no_rawat = ?");
          $qPol->execute([$no_rawat]);
          $pRow = $qPol->fetch(\PDO::FETCH_ASSOC);
          if ($pRow) {
            $regCost = ($pRow['stts_daftar'] == 'Lama') ? $pRow['registrasilama'] : $pRow['registrasi'];
            $total_coass_all += floatval($regCost);
          }
        }

        // Tindakan
        $qCoass = $pdo->prepare("
          SELECT (
            (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat = ? AND stts_bayar = 'Suspen') +
            (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat = ? AND stts_bayar = 'Suspen') +
            (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat = ? AND stts_bayar = 'Suspen')
          ) AS total_coass
        ");
        $qCoass->execute([$no_rawat, $no_rawat, $no_rawat]);
        $total_coass_all += floatval($qCoass->fetchColumn());

        // Obat & BHP
        if (!empty($coass_cats['obat'])) {
          $qOb = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM detail_pemberian_obat WHERE no_rawat = ? AND status = 'Ralan'");
          $qOb->execute([$no_rawat]);
          $total_coass_all += floatval($qOb->fetchColumn());
        }

        // Laboratorium
        if (!empty($coass_cats['laboratorium'])) {
          $qLb = $pdo->prepare("SELECT IFNULL(SUM(biaya),0) FROM periksa_lab WHERE no_rawat = ? AND status = 'Ralan'");
          $qLb->execute([$no_rawat]);
          $total_coass_all += floatval($qLb->fetchColumn());
        }

        // Radiologi
        if (!empty($coass_cats['radiologi'])) {
          $qRd = $pdo->prepare("SELECT IFNULL(SUM(biaya),0) FROM periksa_radiologi WHERE no_rawat = ? AND status = 'Ralan'");
          $qRd->execute([$no_rawat]);
          $total_coass_all += floatval($qRd->fetchColumn());
        }

        // Operasi
        if (!empty($coass_cats['operasi'])) {
          $qOp = $pdo->prepare("SELECT IFNULL(SUM(biayaoperator1+biayaoperator2+biayaoperator3+biayaasisten_operator1+biayaasisten_operator2+biayadokter_anak+biayaperawaat_resusitas+biayadokter_anestesi+biayaasisten_anestesi+biayabidan+biayaperawat_luar),0) FROM operasi WHERE no_rawat = ? AND status = 'Ralan'");
          $qOp->execute([$no_rawat]);
          $total_coass_all += floatval($qOp->fetchColumn());

          $qObOp = $pdo->prepare("SELECT IFNULL(SUM(hargasatuan * jumlah),0) FROM beri_obat_operasi WHERE no_rawat = ?");
          $qObOp->execute([$no_rawat]);
          $total_coass_all += floatval($qObOp->fetchColumn());
        }

        // Obat pulang
        if (!empty($coass_cats['obat_pulang'])) {
          $qOpul = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM resep_pulang WHERE no_rawat = ?");
          $qOpul->execute([$no_rawat]);
          $total_coass_all += floatval($qOpul->fetchColumn());
        }

        // Tambahan
        if (!empty($coass_cats['tambahan'])) {
          $qTb = $pdo->prepare("SELECT IFNULL(SUM(besar_biaya),0) FROM tambahan_biaya WHERE no_rawat = ?");
          $qTb->execute([$no_rawat]);
          $total_coass_all += floatval($qTb->fetchColumn());
        }

        echo $this->draw('billing.kecil.html', [
          'billing' => htmlspecialchars_array($result),
          'pasien' => $pasien,
          'fullname' => $nama_kasir,
          'rincian_alokasi' => $rincian_alokasi,
          'total_bayar_semua' => $total_bayar_semua,
          'total_coass_all' => $total_coass_all,
          'settings' => $this->settings('settings')
        ]);
        break;
      }
      exit();
    }

    public function postKirimEmail() {
      $email = $_POST['email'];
      $nama_lengkap = $_POST['receiver'];
      $file = $_POST['file'];
      $this->sendEmail($email, $nama_lengkap, $file);
      exit();
    }

    private function sendEmail($email, $receiver, $file)
    {
      $binary_content = file_get_contents($file);

      if ($binary_content === false) {
         throw new \Exception("Could not fetch remote content from: '$file'");
      }

	    $mail = new PHPMailer(true);
      $temp  = @file_get_contents(MODULES."/kasir_rawat_jalan/email/email.send.html");

      $temp  = str_replace("{SITENAME}", $this->core->settings->get('settings.nama_instansi'), $temp);
      $temp  = str_replace("{ADDRESS}", $this->core->settings->get('settings.alamat')." - ".$this->core->settings->get('settings.kota'), $temp);
      $temp  = str_replace("{TELP}", $this->core->settings->get('settings.nomor_telepon'), $temp);
      //$temp  = str_replace("{NUMBER}", $number, $temp);

	    //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // for detailed debug output
      $mail->isSMTP();
      $mail->Host = $this->settings->get('api.apam_smtp_host');
      $mail->SMTPAuth = true;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = $this->settings->get('api.apam_smtp_port');

      $mail->Username = $this->settings->get('api.apam_smtp_username');
      $mail->Password = $this->settings->get('api.apam_smtp_password');

      // Sender and recipient settings
      $mail->setFrom($this->core->settings->get('settings.email'), $this->core->settings->get('settings.nama_instansi'));
      $mail->addAddress($email, $receiver);
      $mail->AddStringAttachment($binary_content, "invoice.pdf", $encoding = 'base64', $type = 'application/pdf');

      // Setting the email content
      $mail->IsHTML(true);
      $mail->Subject = "Detail pembayaran anda di ".$this->core->settings->get('settings.nama_instansi');
      $mail->Body = $temp;

      $mail->send();
    }

    public function postCekWaktu()
    {
      echo date('H:i:s');
      exit();
    }

    public function getJavascript(...$args)
    {
        header('Content-type: text/javascript');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $this->draw(MODULES.'/kasir_rawat_jalan/js/admin/kasir_rawat_jalan.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
        $this->core->addJS(url([ADMIN, 'kasir_rawat_jalan', 'javascript']), 'footer');
    }

    public function anyShift()
    {
        $this->_addHeaderFiles();
        $user_id = $this->core->getUserInfo('id');
        $open_shift = $this->db('mlite_kasir_shift')->where('user_id', $user_id)->isNull('waktu_tutup')->desc('id_shift')->oneArray();
        return $this->draw('shift.html', ['open_shift' => $open_shift]);
    }

    public function postOpenKasir()
    {
        $user_id = $this->core->getUserInfo('id');
        $cek = $this->db('mlite_kasir_shift')->where('user_id', $user_id)->isNull('waktu_tutup')->oneArray();
        if ($cek) {
            $this->notify('danger', 'Shift kasir masih terbuka');
            redirect(url([ADMIN, 'kasir_rawat_jalan', 'shift']));
        }
        $kas_awal = floatval($_POST['kas_awal'] ?? 0);
        $this->db('mlite_kasir_shift')->save([
            'user_id' => $user_id,
            'waktu_buka' => date('Y-m-d H:i:s'),
            'waktu_tutup' => null,
            'kas_awal' => $kas_awal,
            'keterangan' => $_POST['keterangan'] ?? ''
        ]);
        $this->notify('success', 'Shift kasir dibuka');
        redirect(url([ADMIN, 'kasir_rawat_jalan', 'shift']));
    }

    public function postCloseKasir()
    {
        $user_id = $this->core->getUserInfo('id');
        $shift = $this->db('mlite_kasir_shift')->where('user_id', $user_id)->isNull('waktu_tutup')->desc('id_shift')->oneArray();
        if (!$shift) {
            $this->notify('danger', 'Tidak ada shift terbuka');
            redirect(url([ADMIN, 'kasir_rawat_jalan', 'shift']));
        }
        $kas_akhir = floatval($_POST['kas_akhir'] ?? 0);
        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(jumlah_harus_bayar),0) as total FROM mlite_billing WHERE id_user = ? AND CONCAT(tgl_billing,' ',jam_billing) BETWEEN ? AND ?");
        $stmt->execute([$user_id, $shift['waktu_buka'], date('Y-m-d H:i:s')]);
        $row = $stmt->fetch();
        $total = floatval($row[0] ?? 0);
        $selisih = $kas_akhir - ($shift['kas_awal'] + $total);
        $this->db('mlite_kasir_shift')->where('id_shift', $shift['id_shift'])->save([
            'waktu_tutup' => date('Y-m-d H:i:s'),
            'kas_akhir' => $kas_akhir,
            'total_transaksi' => $total,
            'selisih' => $selisih
        ]);
        $this->notify('success', 'Shift kasir ditutup');
        redirect(url([ADMIN, 'kasir_rawat_jalan', 'shift']));
    }

    public function anyReport()
    {
        $this->_checkSchema();
        $this->_addHeaderFiles();
        $awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-d').' 00:00:00';
        $akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d').' 23:59:59';
        $penjamin = isset($_GET['penjamin']) ? $_GET['penjamin'] : '';

        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT 
            b.id_user, 
            COALESCE(p.nama, u.fullname, 'Kasir') AS nama_kasir, 
            COUNT(b.id_billing) AS transaksi, 
            IFNULL(SUM(b.jumlah_bayar), 0) AS total, 
            MIN(CONCAT(b.tgl_billing,' ',b.jam_billing)) AS mulai, 
            MAX(CONCAT(b.tgl_billing,' ',b.jam_billing)) AS selesai 
        FROM mlite_billing b 
        LEFT JOIN mlite_users u ON u.id = b.id_user 
        LEFT JOIN pegawai p ON p.nik = u.username 
        WHERE b.kd_billing LIKE 'RJ%' 
        AND CONCAT(b.tgl_billing,' ',b.jam_billing) BETWEEN ? AND ? 
        GROUP BY b.id_user, nama_kasir 
        ORDER BY total DESC");
        $stmt->execute([$awal, $akhir]);
        $rows = $stmt->fetchAll();

        $detailStmt = $pdo->prepare("SELECT 
            b.id_billing,
            b.id_user, 
            COALESCE(p.nama, u.fullname, 'Kasir') AS nama_kasir, 
            b.kd_billing, 
            b.no_rawat, 
            rp.no_rkm_medis, 
            pas.nm_pasien, 
            pol.nm_poli, 
            dok.nm_dokter, 
            pj.png_jawab, 
            b.jumlah_bayar, 
            b.jumlah_harus_bayar,
            CONCAT(b.tgl_billing,' ',b.jam_billing) AS waktu, 
            b.keterangan 
        FROM mlite_billing b 
        LEFT JOIN reg_periksa rp ON rp.no_rawat = b.no_rawat 
        LEFT JOIN pasien pas ON pas.no_rkm_medis = rp.no_rkm_medis 
        LEFT JOIN poliklinik pol ON pol.kd_poli = rp.kd_poli 
        LEFT JOIN dokter dok ON dok.kd_dokter = rp.kd_dokter 
        LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj 
        LEFT JOIN mlite_users u ON u.id = b.id_user 
        LEFT JOIN pegawai p ON p.nik = u.username 
        WHERE b.kd_billing LIKE 'RJ%' 
        AND CONCAT(b.tgl_billing,' ',b.jam_billing) BETWEEN ? AND ? 
        ORDER BY waktu DESC, b.id_billing DESC");
        $detailStmt->execute([$awal, $akhir]);
        $details = $detailStmt->fetchAll(\PDO::FETCH_ASSOC);

        $kd_billings = array_filter(array_column($details, 'kd_billing'));
        $sub_map = [];
        if (!empty($kd_billings)) {
            $inClause = implode(',', array_fill(0, count($kd_billings), '?'));
            $rincianStmt = $pdo->prepare("SELECT kd_billing, kategori, nama, bayar, tagihan, sisa 
                FROM mlite_billing_rincian 
                WHERE kd_billing IN ($inClause) AND bayar > 0 
                ORDER BY id ASC");
            $rincianStmt->execute(array_values($kd_billings));
            while ($r = $rincianStmt->fetch(\PDO::FETCH_ASSOC)) {
                $sub_map[$r['kd_billing']][] = $r;
            }
        }

        foreach ($details as &$d) {
            $kdBil = $d['kd_billing'];
            if (isset($sub_map[$kdBil]) && !empty($sub_map[$kdBil])) {
                $d['sub_rincian'] = $sub_map[$kdBil];
                $parts = [];
                foreach ($sub_map[$kdBil] as $sr) {
                    $parts[] = $sr['nama'] . ': Rp ' . number_format($sr['bayar'], 0, ',', '.');
                }
                $d['sub_rincian_text'] = implode(', ', $parts);
            } else {
                $d['sub_rincian'] = [];
                $cleanKet = $d['keterangan'] ?? 'Tunai';
                if (strpos($cleanKet, ' - ') !== false) {
                    $cleanKet = trim(explode(' - ', $cleanKet, 2)[1]);
                }
                $d['sub_rincian_text'] = $cleanKet;
            }
        }
        unset($d);

        return $this->draw('report.html', ['awal' => $awal, 'akhir' => $akhir, 'penjamin' => $penjamin, 'rows' => $rows, 'details' => $details]);
    }

    public function anyReportExport()
    {
        $awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-d').' 00:00:00';
        $akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d').' 23:59:59';
        $penjamin = isset($_GET['penjamin']) ? $_GET['penjamin'] : '';

        $where_penjamin = "";
        if ($penjamin == 'UMU') {
            $where_penjamin = " AND rp.kd_pj IN ('UMU', '-')";
        } elseif ($penjamin == 'BPJ') {
            $where_penjamin = " AND rp.kd_pj = 'BPJ'";
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="laporan_kasir_'.date('Ymd_His').'.csv"');
        $pdo = $this->db()->pdo();
        $stmt = $pdo->prepare("SELECT 
            b.kd_billing, 
            CONCAT(b.tgl_billing,' ',b.jam_billing) AS waktu,
            b.no_rawat, 
            rp.no_rkm_medis, 
            pas.nm_pasien, 
            pol.nm_poli, 
            dok.nm_dokter, 
            pj.png_jawab, 
            COALESCE(p.nama, u.fullname, 'Kasir') AS nama_kasir, 
            b.jumlah_bayar, 
            b.keterangan 
        FROM mlite_billing b 
        LEFT JOIN reg_periksa rp ON rp.no_rawat = b.no_rawat 
        LEFT JOIN pasien pas ON pas.no_rkm_medis = rp.no_rkm_medis 
        LEFT JOIN poliklinik pol ON pol.kd_poli = rp.kd_poli 
        LEFT JOIN dokter dok ON dok.kd_dokter = rp.kd_dokter 
        LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj 
        LEFT JOIN mlite_users u ON u.id = b.id_user 
        LEFT JOIN pegawai p ON p.nik = u.username 
        WHERE b.kd_billing LIKE 'RJ%' 
        AND CONCAT(b.tgl_billing,' ',b.jam_billing) BETWEEN ? AND ? 
        $where_penjamin 
        ORDER BY waktu ASC");
        $stmt->execute([$awal, $akhir]);
        $rows_data = [];
        $kd_billings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rows_data[] = $row;
            if (!empty($row['kd_billing'])) {
                $kd_billings[] = $row['kd_billing'];
            }
        }

        $sub_map = [];
        if (!empty($kd_billings)) {
            $inClause = implode(',', array_fill(0, count($kd_billings), '?'));
            $rincianStmt = $pdo->prepare("SELECT kd_billing, nama, bayar FROM mlite_billing_rincian WHERE kd_billing IN ($inClause) AND bayar > 0 ORDER BY id ASC");
            $rincianStmt->execute(array_values($kd_billings));
            while ($r = $rincianStmt->fetch(\PDO::FETCH_ASSOC)) {
                $sub_map[$r['kd_billing']][] = $r['nama'] . ' (Rp ' . number_format($r['bayar'], 0, ',', '.') . ')';
            }
        }

        echo "No Faktur,Waktu,No Rawat,No RM,Nama Pasien,Poliklinik,Dokter DPJP,Penjamin,Petugas Kasir,Alokasi Layanan (Sub Rincian),Jumlah Bayar\n";
        foreach ($rows_data as $row) {
            $kdBil = $row['kd_billing'];
            $alokasiText = isset($sub_map[$kdBil]) && !empty($sub_map[$kdBil])
                ? implode('; ', $sub_map[$kdBil])
                : ($row['keterangan'] ?? 'Tunai');

            echo '"' . str_replace('"', '""', $row['kd_billing'] ?? '') . '","'
                . str_replace('"', '""', $row['waktu'] ?? '') . '","'
                . str_replace('"', '""', $row['no_rawat'] ?? '') . '","'
                . str_replace('"', '""', $row['no_rkm_medis'] ?? '') . '","'
                . str_replace('"', '""', $row['nm_pasien'] ?? '') . '","'
                . str_replace('"', '""', $row['nm_poli'] ?? '') . '","'
                . str_replace('"', '""', $row['nm_dokter'] ?? '') . '","'
                . str_replace('"', '""', $row['png_jawab'] ?? '') . '","'
                . str_replace('"', '""', $row['nama_kasir'] ?? '') . '","'
                . str_replace('"', '""', $alokasiText) . '","'
                . ($row['jumlah_bayar'] ?? 0) . "\"\n";
        }
        exit();
    }

    public function anyCetakReport()
    {
        $awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-d').' 00:00:00';
        $akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d').' 23:59:59';
        $penjamin = isset($_GET['penjamin']) ? $_GET['penjamin'] : '';

        $where_penjamin = "";
        if ($penjamin == 'UMU') {
            $where_penjamin = " AND rp.kd_pj IN ('UMU', '-')";
        } elseif ($penjamin == 'BPJ') {
            $where_penjamin = " AND rp.kd_pj = 'BPJ'";
        }

        $pdo = $this->db()->pdo();
        
        $sql = "SELECT 
            b.kd_billing,
            b.no_rawat,
            rp.no_rkm_medis,
            pas.nm_pasien,
            pol.nm_poli,
            dok.nm_dokter,
            b.keterangan AS pembayaran,
            MIN(CONCAT(b.tgl_billing,' ',b.jam_billing)) AS tgl_kunjungan,
            MAX(CONCAT(b.tgl_billing,' ',b.jam_billing)) AS tgl_tagihan,
            b.jumlah_bayar AS tagihan,
            (
                (SELECT IFNULL(SUM(besar_biaya),0) FROM tambahan_biaya WHERE no_rawat=b.no_rawat AND (nama_biaya LIKE '%CSU%' OR nama_biaya LIKE '%CSSD%')) +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND (kd_jenis_prw LIKE 'SDD%' OR kd_jenis_prw LIKE 'SL%' OR kd_jenis_prw LIKE 'STD%' OR kd_jenis_prw LIKE 'STDU%' OR kd_jenis_prw LIKE 'STP%' OR kd_jenis_prw LIKE 'SB%')) +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND (kd_jenis_prw LIKE 'SDD%' OR kd_jenis_prw LIKE 'SL%' OR kd_jenis_prw LIKE 'STD%' OR kd_jenis_prw LIKE 'STDU%' OR kd_jenis_prw LIKE 'STP%' OR kd_jenis_prw LIKE 'SB%')) +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND (kd_jenis_prw LIKE 'SDD%' OR kd_jenis_prw LIKE 'SL%' OR kd_jenis_prw LIKE 'STD%' OR kd_jenis_prw LIKE 'STDU%' OR kd_jenis_prw LIKE 'STP%' OR kd_jenis_prw LIKE 'SB%'))
            ) AS csu,
            (
                (SELECT IFNULL(SUM(besar_biaya),0) FROM tambahan_biaya WHERE no_rawat=b.no_rawat AND LOWER(nama_biaya) LIKE '%laundry%') +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'ULA%') +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'ULA%') +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'ULA%')
            ) AS laundry,
            (SELECT IFNULL(SUM(biaya),0) FROM periksa_radiologi WHERE no_rawat=b.no_rawat AND status='Ralan') AS radiologi,
            (
                (SELECT IFNULL(SUM(total+embalase+tuslah),0) FROM detail_pemberian_obat WHERE no_rawat=b.no_rawat AND status='Ralan') +
                (SELECT IFNULL(SUM(total),0) FROM resep_pulang WHERE no_rawat=b.no_rawat) +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'FART%') +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'FART%') +
                (SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat=b.no_rawat AND stts_bayar != 'Suspen' AND kd_jenis_prw LIKE 'FART%')
            ) AS apotek,
            (SELECT IFNULL(SUM(pl.biaya),0) FROM periksa_lab pl WHERE pl.no_rawat=b.no_rawat AND pl.status='Ralan' AND pl.kd_jenis_prw LIKE 'LABK%') AS lab_klinik,
            (SELECT IFNULL(SUM(pl.biaya),0) FROM periksa_lab pl WHERE pl.no_rawat=b.no_rawat AND pl.status='Ralan' AND pl.kd_jenis_prw LIKE 'LABG%') AS lab_gigi
        FROM mlite_billing b
        LEFT JOIN reg_periksa rp ON rp.no_rawat = b.no_rawat
        LEFT JOIN pasien pas ON pas.no_rkm_medis = rp.no_rkm_medis
        LEFT JOIN poliklinik pol ON pol.kd_poli = rp.kd_poli
        LEFT JOIN dokter dok ON dok.kd_dokter = rp.kd_dokter
        WHERE b.kd_billing LIKE 'RJ%' 
        AND CONCAT(b.tgl_billing,' ',b.jam_billing) BETWEEN ? AND ?
        $where_penjamin
        GROUP BY b.no_rawat, b.kd_billing, rp.no_rkm_medis, pas.nm_pasien, pol.nm_poli, dok.nm_dokter, b.keterangan
        ORDER BY tgl_tagihan ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$awal, $akhir]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $kd_billings = array_filter(array_column($rows, 'kd_billing'));
        if (!empty($kd_billings)) {
            $inClause = implode(',', array_fill(0, count($kd_billings), '?'));
            $rincianStmt = $pdo->prepare("SELECT kd_billing, nama, bayar FROM mlite_billing_rincian WHERE kd_billing IN ($inClause) AND bayar > 0 ORDER BY id ASC");
            $rincianStmt->execute(array_values($kd_billings));
            $sub_map = [];
            while ($r = $rincianStmt->fetch(\PDO::FETCH_ASSOC)) {
                $sub_map[$r['kd_billing']][] = $r['nama'] . ': Rp ' . number_format($r['bayar'], 0, ',', '.');
            }
            foreach ($rows as &$r) {
                $kdBil = $r['kd_billing'];
                if (isset($sub_map[$kdBil]) && !empty($sub_map[$kdBil])) {
                    $r['pembayaran'] = implode(', ', $sub_map[$kdBil]);
                }
            }
            unset($r);
        }

        $tot_tagihan = 0;
        $tot_csu = 0;
        $tot_laundry = 0;
        $tot_rad = 0;
        $tot_apt = 0;
        $tot_lab_klinik = 0;
        $tot_lab_gigi = 0;
        $tot_bersih = 0;

        foreach ($rows as &$row) {
            $bersih = $row['tagihan'] - $row['csu'] - $row['laundry'] - $row['radiologi'] - $row['apotek'] - $row['lab_klinik'] - $row['lab_gigi'];
            $row['nilai_bersih'] = $bersih;

            $tot_tagihan += $row['tagihan'];
            $tot_csu += $row['csu'];
            $tot_laundry += $row['laundry'];
            $tot_rad += $row['radiologi'];
            $tot_apt += $row['apotek'];
            $tot_lab_klinik += $row['lab_klinik'];
            $tot_lab_gigi += $row['lab_gigi'];
            $tot_bersih += $bersih;
        }
        unset($row);

        echo $this->draw('cetak_report.html', [
            'awal' => $awal, 
            'akhir' => $akhir, 
            'rows' => $rows,
            'tot_tagihan' => $tot_tagihan,
            'tot_csu' => $tot_csu,
            'tot_laundry' => $tot_laundry,
            'tot_rad' => $tot_rad,
            'tot_apt' => $tot_apt,
            'tot_lab_klinik' => $tot_lab_klinik,
            'tot_lab_gigi' => $tot_lab_gigi,
            'tot_bersih' => $tot_bersih
        ]);
        exit();
    }
}

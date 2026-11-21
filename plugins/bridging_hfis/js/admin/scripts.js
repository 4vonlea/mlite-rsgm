function ubahhfis() {
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/updatebridgehfis?t=' + (mlite.token || '');
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    var notif = document.getElementById("notif");
    var btn = document.getElementById("btnUbahHfis");

    if (!kd_dokter) {
        alert("Silakan pilih Dokter DPJP BPJS terlebih dahulu!");
        document.getElementById("kd_dokter").focus();
        return;
    }

    if (!kd_poli) {
        alert("Silakan pilih Poliklinik BPJS terlebih dahulu!");
        document.getElementById("kd_poli").focus();
        return;
    }

    var daysConfig = [
        { chk: "senin", val: "1|pagi", dari: "dari1|pagi", sampai: "sampai1|pagi", kuota: "kuotasenin", label: "Senin Pagi" },
        { chk: "selasa", val: "2|pagi", dari: "dari2|pagi", sampai: "sampai2|pagi", kuota: "kuotaselasa", label: "Selasa Pagi" },
        { chk: "rabu", val: "3|pagi", dari: "dari3|pagi", sampai: "sampai3|pagi", kuota: "kuotarabu", label: "Rabu Pagi" },
        { chk: "kamis", val: "4|pagi", dari: "dari4|pagi", sampai: "sampai4|pagi", kuota: "kuotakamis", label: "Kamis Pagi" },
        { chk: "jumat", val: "5|pagi", dari: "dari5|pagi", sampai: "sampai5|pagi", kuota: "kuotajumat", label: "Jumat Pagi" },
        { chk: "sabtu", val: "6|pagi", dari: "dari6|pagi", sampai: "sampai6|pagi", kuota: "kuotasabtu", label: "Sabtu Pagi" },
        { chk: "seninsore", val: "1|sore", dari: "darisore1|sore", sampai: "sampaisore1|sore", kuota: "kuotaseninsore", label: "Senin Sore" },
        { chk: "selasasore", val: "2|sore", dari: "darisore2|sore", sampai: "sampaisore2|sore", kuota: "kuotaselasasore", label: "Selasa Sore" },
        { chk: "rabusore", val: "3|sore", dari: "darisore3|sore", sampai: "sampaisore3|sore", kuota: "kuotarabusore", label: "Rabu Sore" },
        { chk: "kamissore", val: "4|sore", dari: "darisore4|sore", sampai: "sampaisore4|sore", kuota: "kuotakamissore", label: "Kamis Sore" },
        { chk: "jumatsore", val: "5|sore", dari: "darisore5|sore", sampai: "sampaisore5|sore", kuota: "kuotajumatsore", label: "Jumat Sore" },
        { chk: "sabtusore", val: "6|sore", dari: "darisore6|sore", sampai: "sampaisore6|sore", kuota: "kuotasabtusore", label: "Sabtu Sore" }
    ];

    var jadwal = [];
    for (var i = 0; i < daysConfig.length; i++) {
        var cfg = daysConfig[i];
        var elChk = document.getElementById(cfg.chk);
        if (elChk && elChk.checked) {
            var elDari = document.getElementById(cfg.dari);
            var elSampai = document.getElementById(cfg.sampai);
            var elKuota = document.getElementById(cfg.kuota);

            var dariVal = elDari ? elDari.value : '';
            var sampaiVal = elSampai ? elSampai.value : '';
            var kuotaVal = elKuota ? elKuota.value : '0';

            jadwal.push({
                "hari": cfg.val,
                "buka": dariVal,
                "tutup": sampaiVal,
                "kuota": kuotaVal
            });
        }
    }

    if (jadwal.length === 0) {
        alert("Pilih minimal satu hari praktek (centang kotak hari) dan atur jam buka/tutup serta kuota sebelum mengirim!");
        return;
    }

    var payload = {
        "kd_dokter": kd_dokter,
        "kd_poli": kd_poli,
        "jadwal": jadwal
    };

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sedang Mengirim ke BPJS HFIS...';
    }

    if (notif) {
        notif.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Sedang menyinkronkan jadwal dengan server BPJS HFIS...</div>';
    }

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/json');

    http.onload = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-upload"></i> Kirim & Simpan Jadwal ke BPJS HFIS';
        }

        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success' || res.code == 200) {
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-success alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-check-circle"></i> Berhasil Disimpan!</h4>' +
                        '<p>' + (res.message || 'Jadwal dokter berhasil disinkronkan ke server BPJS HFIS dan database SIMRS.') + '</p>' +
                        '</div>';
                }
            } else {
                var errDetail = res.message || 'Gagal memperbarui jadwal di server BPJS.';
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-danger alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-exclamation-triangle"></i> Gagal dari BPJS (Kode ' + (res.code || 'Error') + ')</h4>' +
                        '<p>' + errDetail + '</p>' +
                        '</div>';
                }
            }
        } catch (e) {
            if (notif) {
                notif.innerHTML = '<div class="alert alert-warning alert-dismissible">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<h4><i class="fa fa-info-circle"></i> Respon Server:</h4>' +
                    '<p>' + this.responseText + '</p>' +
                    '</div>';
            }
        }

        if (notif && notif.scrollIntoView) {
            notif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    http.onerror = function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-upload"></i> Kirim & Simpan Jadwal ke BPJS HFIS';
        }
        if (notif) {
            notif.innerHTML = '<div class="alert alert-danger"><i class="fa fa-plug"></i> Gagal menghubungi server SIMRS. Silakan periksa koneksi jaringan Anda.</div>';
        }
    };

    http.send(JSON.stringify(payload));
}

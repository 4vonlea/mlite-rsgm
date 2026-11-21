var daysConfig = [
    { chk: "senin", dayNum: 1, sesi: "pagi", val: "1|pagi", dari: "dari1|pagi", sampai: "sampai1|pagi", kuota: "kuotasenin", label: "Senin Pagi" },
    { chk: "selasa", dayNum: 2, sesi: "pagi", val: "2|pagi", dari: "dari2|pagi", sampai: "sampai2|pagi", kuota: "kuotaselasa", label: "Selasa Pagi" },
    { chk: "rabu", dayNum: 3, sesi: "pagi", val: "3|pagi", dari: "dari3|pagi", sampai: "sampai3|pagi", kuota: "kuotarabu", label: "Rabu Pagi" },
    { chk: "kamis", dayNum: 4, sesi: "pagi", val: "4|pagi", dari: "dari4|pagi", sampai: "sampai4|pagi", kuota: "kuotakamis", label: "Kamis Pagi" },
    { chk: "jumat", dayNum: 5, sesi: "pagi", val: "5|pagi", dari: "dari5|pagi", sampai: "sampai5|pagi", kuota: "kuotajumat", label: "Jumat Pagi" },
    { chk: "sabtu", dayNum: 6, sesi: "pagi", val: "6|pagi", dari: "dari6|pagi", sampai: "sampai6|pagi", kuota: "kuotasabtu", label: "Sabtu Pagi" },
    { chk: "seninsore", dayNum: 1, sesi: "sore", val: "1|sore", dari: "darisore1|sore", sampai: "sampaisore1|sore", kuota: "kuotaseninsore", label: "Senin Sore" },
    { chk: "selasasore", dayNum: 2, sesi: "sore", val: "2|sore", dari: "darisore2|sore", sampai: "sampaisore2|sore", kuota: "kuotaselasasore", label: "Selasa Sore" },
    { chk: "rabusore", dayNum: 3, sesi: "sore", val: "3|sore", dari: "darisore3|sore", sampai: "sampaisore3|sore", kuota: "kuotarabusore", label: "Rabu Sore" },
    { chk: "kamissore", dayNum: 4, sesi: "sore", val: "4|sore", dari: "darisore4|sore", sampai: "sampaisore4|sore", kuota: "kuotakamissore", label: "Kamis Sore" },
    { chk: "jumatsore", dayNum: 5, sesi: "sore", val: "5|sore", dari: "darisore5|sore", sampai: "sampaisore5|sore", kuota: "kuotajumatsore", label: "Jumat Sore" },
    { chk: "sabtusore", dayNum: 6, sesi: "sore", val: "6|sore", dari: "darisore6|sore", sampai: "sampaisore6|sore", kuota: "kuotasabtusore", label: "Sabtu Sore" }
];

function updateCardVisual(chkId, isChecked) {
    var card = document.getElementById("card_" + chkId);
    var badge = document.getElementById("badge_" + chkId);

    if (card) {
        if (isChecked) {
            card.style.background = '#f0fdf4';
            card.style.borderColor = '#22c55e';
            card.style.boxShadow = '0 2px 6px rgba(34, 197, 94, 0.15)';
        } else {
            card.style.background = '#f8fafc';
            card.style.borderColor = '#cbd5e1';
            card.style.boxShadow = 'none';
        }
    }

    if (badge) {
        if (isChecked) {
            badge.className = 'label label-success';
            badge.innerHTML = '<i class="fa fa-check"></i> BUKA';
        } else {
            badge.className = 'label label-default';
            badge.innerHTML = 'TUTUP';
        }
    }
}

function syncKalyptoToggle(el, isChecked) {
    if (window.$ && el) {
        var $toggle = $(el).next('.toggle');
        if ($toggle.length) {
            if (isChecked) {
                $toggle.addClass('checked');
            } else {
                $toggle.removeClass('checked');
            }
        }
    }
}

function setCheckboxState(chkId, isChecked) {
    var el = document.getElementById(chkId);
    if (el) {
        el.checked = !!isChecked;
        if (window.$ && typeof $.fn.prop === 'function') {
            $(el).prop('checked', !!isChecked);
        }
        if (isChecked) {
            el.setAttribute('checked', 'checked');
        } else {
            el.removeAttribute('checked');
        }
        syncKalyptoToggle(el, !!isChecked);
        setTimeout(function() {
            syncKalyptoToggle(el, !!isChecked);
        }, 30);
    }
    updateCardVisual(chkId, !!isChecked);
}

function onCheckboxDayChange(chkId) {
    var el = document.getElementById(chkId);
    if (!el) return;

    // Tunggu event Kalypto selesai mengubah DOM
    setTimeout(function() {
        var isChecked = el.checked;
        if (window.$) {
            var $toggle = $(el).next('.toggle');
            if ($toggle.length) {
                // Jika toggle memiliki class checked tapi el.checked belum sinkron atau sebaliknya
                if ($toggle.hasClass('checked')) {
                    isChecked = true;
                    el.checked = true;
                    $(el).prop('checked', true);
                } else if (!el.checked) {
                    isChecked = false;
                }
                syncKalyptoToggle(el, isChecked);
            }
        }
        updateCardVisual(chkId, isChecked);
    }, 15);
}

function onInputDayChange(chkId) {
    var elDari = null;
    var elSampai = null;
    var elKuota = null;
    for (var i = 0; i < daysConfig.length; i++) {
        if (daysConfig[i].chk === chkId) {
            elDari = document.getElementById(daysConfig[i].dari);
            elSampai = document.getElementById(daysConfig[i].sampai);
            elKuota = document.getElementById(daysConfig[i].kuota);
            break;
        }
    }
    // Jika jam atau kuota diisi oleh petugas, otomatis centang harinya (menjadi BUKA) jika belum dicentang
    if ((elDari && elDari.value) || (elSampai && elSampai.value) || (elKuota && elKuota.value)) {
        var elChk = document.getElementById(chkId);
        if (elChk && !elChk.checked) {
            setCheckboxState(chkId, true);
        }
    }
}

function onDokterPoliChange() {
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    if (kd_dokter && kd_poli) {
        muatJadwalAktif(false);
    } else {
        kosongkanForm();
    }
}

function kosongkanForm() {
    for (var i = 0; i < daysConfig.length; i++) {
        var cfg = daysConfig[i];
        setCheckboxState(cfg.chk, false);
        var elDari = document.getElementById(cfg.dari);
        var elSampai = document.getElementById(cfg.sampai);
        var elKuota = document.getElementById(cfg.kuota);

        if (elDari) elDari.value = '';
        if (elSampai) elSampai.value = '';
        if (elKuota) elKuota.value = '';
    }
    var status = document.getElementById("statusMuatJadwal");
    if (status) status.innerHTML = '';
    var preview = document.getElementById("previewJadwalAktif");
    if (preview) {
        preview.style.display = 'none';
        preview.innerHTML = '';
    }
}

function muatJadwalAktif(isManual) {
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/getjadwalhfis?t=' + (mlite.token || '');
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    var status = document.getElementById("statusMuatJadwal");
    var preview = document.getElementById("previewJadwalAktif");

    if (!kd_dokter || !kd_poli) {
        if (isManual) {
            alert("Silakan pilih Dokter DPJP dan Poliklinik BPJS terlebih dahulu!");
        }
        return;
    }

    if (status) {
        status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memuat jadwal tersimpan di database SIMRS...';
    }

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onload = function () {
        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success') {
                kosongkanForm();

                var list = res.jadwal || [];
                var previewBadges = [];

                for (var j = 0; j < list.length; j++) {
                    var item = list[j];
                    for (var k = 0; k < daysConfig.length; k++) {
                        var cfg = daysConfig[k];
                        if (cfg.dayNum === item.day_num && cfg.sesi === item.sesi) {
                            var elDari = document.getElementById(cfg.dari);
                            var elSampai = document.getElementById(cfg.sampai);
                            var elKuota = document.getElementById(cfg.kuota);

                            setCheckboxState(cfg.chk, true);
                            if (elDari) elDari.value = item.jam_mulai;
                            if (elSampai) elSampai.value = item.jam_selesai;
                            if (elKuota) elKuota.value = item.kuota;

                            previewBadges.push('<strong>' + cfg.label + '</strong>: ' + item.jam_mulai + ' - ' + item.jam_selesai + ' (Kuota: ' + item.kuota + ')');
                            break;
                        }
                    }
                }

                if (list.length > 0) {
                    if (status) {
                        status.innerHTML = '<i class="fa fa-check-circle text-success"></i> Berhasil memuat ' + list.length + ' jadwal aktif dari SIMRS.';
                    }
                    if (preview) {
                        preview.style.display = 'block';
                        preview.innerHTML = '<i class="fa fa-info-circle"></i> <b>Jadwal Aktif Tersimpan di SIMRS:</b> ' + previewBadges.join(' | ') + '<br><small style="color:#64748b;">(Untuk meliburkan/menutup hari tertentu, cukup hilangkan centang kotak hari tersebut lalu klik tombol Kirim & Simpan)</small>';
                    }
                } else {
                    if (status) {
                        status.innerHTML = '<i class="fa fa-spinner fa-spin text-info"></i> Jadwal di SIMRS kosong. Mencoba menarik langsung dari server BPJS HFIS...';
                    }
                    // Auto-pull dari server BPJS HFIS jika di SIMRS belum ada datanya
                    setTimeout(function() {
                        tarikJadwalLangsungBpjs(false);
                    }, 350);
                }
            } else {
                if (status) {
                    status.innerHTML = '<i class="fa fa-exclamation-triangle text-danger"></i> ' + (res.message || 'Gagal memuat jadwal.');
                }
            }
        } catch (e) {
            if (status) {
                status.innerHTML = '<i class="fa fa-exclamation-triangle text-warning"></i> Gagal parsing data jadwal.';
            }
        }
    };

    http.onerror = function () {
        if (status) {
            status.innerHTML = '<i class="fa fa-plug text-danger"></i> Gagal menghubungi server SIMRS.';
        }
    };

    http.send('kd_dokter=' + encodeURIComponent(kd_dokter) + '&kd_poli=' + encodeURIComponent(kd_poli));
}

function tarikJadwalLangsungBpjs(isManual) {
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/tarikjadwalbpjs?t=' + (mlite.token || '');
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    var status = document.getElementById("statusMuatJadwal");
    var preview = document.getElementById("previewJadwalAktif");
    var btn = document.getElementById("btnTarikBpjs");

    if (!kd_dokter || !kd_poli) {
        if (isManual) {
            alert("Silakan pilih Dokter DPJP dan Poliklinik BPJS terlebih dahulu!");
        }
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menghubungi BPJS...';
    }

    if (status) {
        status.innerHTML = '<i class="fa fa-spinner fa-spin text-primary"></i> Sedang menarik jadwal resmi dokter dari server BPJS HFIS...';
    }

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onload = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-cloud-download"></i> <b>Tarik Jadwal Resmi dari BPJS HFIS</b>';
        }

        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success') {
                kosongkanForm();

                var list = res.jadwal || [];
                var previewBadges = [];

                for (var j = 0; j < list.length; j++) {
                    var item = list[j];
                    for (var k = 0; k < daysConfig.length; k++) {
                        var cfg = daysConfig[k];
                        if (cfg.dayNum === item.day_num && cfg.sesi === item.sesi) {
                            var elDari = document.getElementById(cfg.dari);
                            var elSampai = document.getElementById(cfg.sampai);
                            var elKuota = document.getElementById(cfg.kuota);

                            setCheckboxState(cfg.chk, true);
                            if (elDari) elDari.value = item.jam_mulai;
                            if (elSampai) elSampai.value = item.jam_selesai;
                            if (elKuota) elKuota.value = item.kuota;

                            previewBadges.push('<strong>' + cfg.label + '</strong>: ' + item.jam_mulai + ' - ' + item.jam_selesai + ' (Kuota: ' + item.kuota + ')');
                            break;
                        }
                    }
                }

                if (list.length > 0) {
                    if (status) {
                        status.innerHTML = '<i class="fa fa-check-circle text-success"></i> Berhasil menarik ' + list.length + ' jadwal resmi dari BPJS HFIS & disinkronkan ke SIMRS.';
                    }
                    if (preview) {
                        preview.style.display = 'block';
                        preview.innerHTML = '<i class="fa fa-check-circle text-success"></i> <b>Jadwal Resmi dari BPJS HFIS Ditemukan:</b> ' + previewBadges.join(' | ') + '<br><small style="color:#0369a1;">Data di atas telah otomatis disalin ke form di bawah dan database SIMRS lokal. Hari yang praktek otomatis tercentang hijau <b>[ BUKA ]</b>. Untuk menutup/meliburkan hari tertentu, cukup hilangkan centang.</small>';
                    }
                } else {
                    if (status) {
                        status.innerHTML = '<i class="fa fa-info-circle text-warning"></i> Tidak ada jadwal aktif di server BPJS HFIS untuk dokter & poli ini.';
                    }
                    if (preview) {
                        preview.style.display = 'block';
                        preview.innerHTML = '<i class="fa fa-info-circle text-warning"></i> Dokter ini belum memiliki jadwal terdaftar di server BPJS HFIS untuk poli ini. Silakan centang hari praktek dan tentukan jam buka/tutup serta kuota di bawah, lalu klik <b>"Kirim & Simpan Jadwal ke BPJS HFIS"</b> untuk mendaftarkannya.';
                    }
                }
            } else {
                if (status) {
                    status.innerHTML = '<i class="fa fa-exclamation-triangle text-danger"></i> ' + (res.message || 'Gagal menarik data dari BPJS.');
                }
            }
        } catch (e) {
            if (status) {
                status.innerHTML = '<i class="fa fa-exclamation-triangle text-warning"></i> Gagal parsing data respon BPJS.';
            }
        }
    };

    http.onerror = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-cloud-download"></i> <b>Tarik Jadwal Resmi dari BPJS HFIS</b>';
        }
        if (status) {
            status.innerHTML = '<i class="fa fa-plug text-danger"></i> Gagal menghubungi server SIMRS.';
        }
    };

    http.send('kd_dokter=' + encodeURIComponent(kd_dokter) + '&kd_poli=' + encodeURIComponent(kd_poli) + '&simpan_ke_simrs=1');
}

var currentUbahPayload = null;

function toggleBtnEksekusiUbahHfis() {
    var chk = document.getElementById("chkSetujuUbahHfis");
    var btn = document.getElementById("btnEksekusiUbahHfis");
    if (btn) {
        btn.disabled = chk ? !chk.checked : false;
    }
}

function ubahhfis() {
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';

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

    var jadwal = [];
    var activeDaysDesc = [];
    var closedDaysLabels = [];

    for (var i = 0; i < daysConfig.length; i++) {
        var cfg = daysConfig[i];
        var elChk = document.getElementById(cfg.chk);
        var isChecked = false;
        if (elChk) {
            isChecked = elChk.checked || (window.$ && $(elChk).is(':checked')) || (window.$ && $(elChk).next('.toggle').hasClass('checked'));
        }

        var elDari = document.getElementById(cfg.dari);
        var elSampai = document.getElementById(cfg.sampai);
        var elKuota = document.getElementById(cfg.kuota);

        var dariVal = elDari ? elDari.value : '';
        var sampaiVal = elSampai ? elSampai.value : '';
        var kuotaVal = elKuota ? elKuota.value : '0';

        // Hanya sertakan jika dicentang, atau jika jam buka & tutup diisi lengkap
        if (isChecked || (dariVal !== '' && sampaiVal !== '')) {
            jadwal.push({
                "hari": cfg.val,
                "buka": dariVal,
                "tutup": sampaiVal,
                "kuota": kuotaVal
            });
            activeDaysDesc.push({
                label: cfg.label,
                buka: dariVal || '08:00',
                tutup: sampaiVal || '12:00',
                kuota: kuotaVal || '30'
            });
        } else {
            closedDaysLabels.push(cfg.label);
        }
    }

    if (jadwal.length === 0) {
        alert("Pilih minimal satu hari praktek (centang kotak hari) dan atur jam buka/tutup serta kuota sebelum mengirim!");
        return;
    }

    var selDoc = document.getElementById("kd_dokter");
    var docName = selDoc && selDoc.options[selDoc.selectedIndex] ? selDoc.options[selDoc.selectedIndex].text : kd_dokter;
    var selPoli = document.getElementById("kd_poli");
    var poliName = selPoli && selPoli.options[selPoli.selectedIndex] ? selPoli.options[selPoli.selectedIndex].text : kd_poli;

    currentUbahPayload = {
        "kd_dokter": kd_dokter,
        "kd_poli": kd_poli,
        "jadwal": jadwal
    };

    var modalUbah = document.getElementById("modalKonfirmasiUbahHfis");
    if (modalUbah && window.$) {
        var elDocName = document.getElementById("modalUbahDocName");
        var elPoliName = document.getElementById("modalUbahPoliName");
        var elJmlBuka = document.getElementById("modalJumlahHariBuka");
        var elListBuka = document.getElementById("modalListHariBuka");
        var elListTutup = document.getElementById("modalListHariTutup");

        if (elDocName) elDocName.innerText = docName;
        if (elPoliName) elPoliName.innerText = poliName;
        if (elJmlBuka) elJmlBuka.innerText = jadwal.length;

        if (elListBuka) {
            elListBuka.innerHTML = activeDaysDesc.map(function(d) {
                return '<div style="margin-bottom:3px;"><i class="fa fa-check text-success"></i> <b>' + d.label + '</b>: ' + d.buka + ' - ' + d.tutup + ' (Kuota: ' + d.kuota + ')</div>';
            }).join('');
        }

        if (elListTutup) {
            if (closedDaysLabels.length > 0) {
                elListTutup.innerHTML = closedDaysLabels.map(function(lbl) {
                    return '<span class="label label-danger" style="margin-right:4px; margin-bottom:4px; display:inline-block; font-size:10px;">' + lbl + '</span>';
                }).join(' ');
            } else {
                elListTutup.innerHTML = '<span class="text-success"><i class="fa fa-check"></i> Tidak ada (Semua hari praktek aktif)</span>';
            }
        }

        var chkSetuju = document.getElementById("chkSetujuUbahHfis");
        if (chkSetuju) chkSetuju.checked = false;
        toggleBtnEksekusiUbahHfis();

        $('#modalKonfirmasiUbahHfis').modal('show');
        return;
    }

    // Fallback jika modal tidak tersedia
    var confirmMsg = "KONFIRMASI PENGIRIMAN KE BPJS HFIS:\n\n" +
        "Anda akan memperbarui jadwal dokter:\n" +
        "• " + docName + "\n" +
        "• Poliklinik: " + poliName + "\n" +
        "• Jumlah hari aktif: " + jadwal.length + " hari\n\n" +
        "Catatan: Hari yang TIDAK dicentang otomatis menjadi TUTUP / LIBUR di BPJS.\n\n" +
        "Apakah Anda yakin ingin menyimpan perubahan ini ke server BPJS HFIS?";

    if (!confirm(confirmMsg)) {
        return;
    }
    eksekusiUbahHfis();
}

function eksekusiUbahHfis() {
    if (window.$) {
        $('#modalKonfirmasiUbahHfis').modal('hide');
    }

    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/updatebridgehfis?t=' + (mlite.token || '');
    var notif = document.getElementById("notif");
    var btn = document.getElementById("btnUbahHfis");

    if (!currentUbahPayload) return;

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
                setTimeout(function () {
                    muatJadwalAktif(false);
                }, 800);
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

    http.onerror = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-upload"></i> Kirim & Simpan Jadwal ke BPJS HFIS';
        }
        if (notif) {
            notif.innerHTML = '<div class="alert alert-danger"><i class="fa fa-plug"></i> Gagal menghubungi server SIMRS. Silakan periksa koneksi jaringan Anda.</div>';
        }
    };

    http.send(JSON.stringify(currentUbahPayload));
}

// -------------------------------------------------------------
// FITUR OPERASIONAL DARURAT: TUTUP PELAYANAN HARI INI
// -------------------------------------------------------------
function toggleBtnEksekusiTutupHariIni() {
    var chk = document.getElementById("chkSetujuTutupHariIni");
    var btn = document.getElementById("btnEksekusiTutupHariIni");
    if (btn) {
        btn.disabled = chk ? !chk.checked : false;
    }
}

function konfirmasiTutupHariIni() {
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';

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

    var selDoc = document.getElementById("kd_dokter");
    var docName = selDoc && selDoc.options[selDoc.selectedIndex] ? selDoc.options[selDoc.selectedIndex].text : kd_dokter;
    var selPoli = document.getElementById("kd_poli");
    var poliName = selPoli && selPoli.options[selPoli.selectedIndex] ? selPoli.options[selPoli.selectedIndex].text : kd_poli;

    var daysIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var now = new Date();
    var hariNama = daysIndo[now.getDay()];
    var tglFormatted = ('0' + now.getDate()).slice(-2) + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + now.getFullYear();

    var elDoc = document.getElementById("modalTutupDocName");
    var elPoli = document.getElementById("modalTutupPoliName");
    var elTgl = document.getElementById("modalTutupTanggal");

    if (elDoc) elDoc.innerText = docName;
    if (elPoli) elPoli.innerText = poliName;
    if (elTgl) elTgl.innerText = hariNama + ', ' + tglFormatted + ' (Hari Ini)';

    var chk = document.getElementById("chkSetujuTutupHariIni");
    if (chk) chk.checked = false;
    toggleBtnEksekusiTutupHariIni();

    var containerPasien = document.getElementById("modalTutupPasienContainer");
    if (containerPasien) {
        containerPasien.innerHTML = '<div class="text-center text-muted" style="padding:15px; background:#f8fafc; border-radius:6px; border:1px dashed #cbd5e1;">' +
            '<i class="fa fa-spinner fa-spin text-primary"></i> Sedang memeriksa pendaftaran pasien hari ini di server BPJS...</div>';
    }

    if (window.$) {
        $('#modalTutupHariIni').modal('show');
    }

    // Ajax check pasien hari ini di server BPJS
    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/cekpasienhariini?t=' + (mlite.token || '');

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onload = function () {
        if (!containerPasien) return;

        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success') {
                var list = res.data || [];
                if (list.length > 0) {
                    var html = '<div class="alert alert-warning" style="margin-bottom:10px; font-size:12px; padding:10px 14px; border-radius:6px;">' +
                        '<i class="fa fa-warning"></i> <b>DITEMUKAN ' + list.length + ' PASIEN TERDAFTAR HARI INI VIA MOBILE JKN:</b><br>' +
                        '<small>Sesuai regulasi resmi BPJS Kesehatan, antrean pasien ini HARUS dibatalkan resmi melalui sistem BPJS Antrean (<code>antrean/batal</code>) agar nomor rujukan/kontrol pasien <b>TIDAK HANGUS</b>.</small>' +
                        '</div>';

                    html += '<div style="max-height:140px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:10px;">' +
                        '<table class="table table-bordered table-condensed table-striped" style="margin-bottom:0; font-size:11px;">' +
                        '<thead><tr style="background:#f8fafc;"><th>No. Antrean</th><th>Nama Pasien</th><th>No. RM / Kartu</th><th>Jam Pelayanan</th></tr></thead><tbody>';

                    for (var p = 0; p < list.length; p++) {
                        var it = list[p];
                        html += '<tr>' +
                            '<td><b>' + it.noantrean + '</b><br><small class="text-muted">' + it.kodebooking + '</small></td>' +
                            '<td><b>' + it.namapasien + '</b></td>' +
                            '<td>' + (it.norm || '-') + ' / ' + (it.noka || '-') + '</td>' +
                            '<td>' + (it.jampraktek || '-') + '</td>' +
                            '</tr>';
                    }
                    html += '</tbody></table></div>';

                    html += '<div style="background:#eff6ff; border:1px solid #bfdbfe; padding:8px 12px; border-radius:6px; font-size:12px; color:#1e40af;">' +
                        '<label style="cursor:pointer; margin-bottom:0; display:flex; align-items:center;">' +
                        '<input type="checkbox" id="chkBatalAntreanBpjs" checked style="width:16px; height:16px; margin-right:8px;">' +
                        '<span><b>Batalkan otomatis ' + list.length + ' antrean pasien di atas ke server BPJS Antrean</b> (Direkomendasikan)</span>' +
                        '</label></div>';

                    containerPasien.innerHTML = html;
                } else {
                    containerPasien.innerHTML = '<div class="alert alert-success" style="margin-bottom:0; font-size:12px; padding:10px 14px; border-radius:6px;">' +
                        '<i class="fa fa-check-circle"></i> <b>Tidak ada antrean pasien terdaftar untuk dokter ini hari ini via Mobile JKN.</b> Penutupan jadwal aman dilakukan tanpa pembatalan pasien.' +
                        '</div>';
                }
            } else {
                containerPasien.innerHTML = '<div class="alert alert-warning" style="margin-bottom:0; font-size:12px;">' +
                    '<i class="fa fa-info-circle"></i> Catatan BPJS: ' + (res.message || 'Tidak ada data antrean.') +
                    '</div>';
            }
        } catch (e) {
            containerPasien.innerHTML = '<div class="alert alert-warning" style="margin-bottom:0; font-size:12px;">' +
                '<i class="fa fa-info-circle"></i> Tidak dapat memeriksa antrean otomatis. Anda tetap dapat melanjutkan penutupan jadwal.' +
                '</div>';
        }
    };

    http.onerror = function () {
        if (containerPasien) {
            containerPasien.innerHTML = '<div class="alert alert-warning" style="margin-bottom:0; font-size:12px;">' +
                '<i class="fa fa-info-circle"></i> Gagal memeriksa pendaftaran pasien ke BPJS. Anda tetap dapat melanjutkan penutupan jadwal.' +
                '</div>';
        }
    };

    http.send('kd_dokter=' + encodeURIComponent(kd_dokter) + '&kd_poli=' + encodeURIComponent(kd_poli));
}

function eksekusiTutupHariIni() {
    if (window.$) {
        $('#modalTutupHariIni').modal('hide');
    }

    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    var chkBatal = document.getElementById("chkBatalAntreanBpjs");
    var batal_antrean = (chkBatal && !chkBatal.checked) ? 0 : 1;
    var elAlasan = document.getElementById("alasanTutupHariIni");
    var alasan_batal = elAlasan ? elAlasan.value : 'Dokter berhalangan hadir mendadak / sakit';

    var btn = document.getElementById("btnTutupHariIni");
    var notif = document.getElementById("notif");

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menutup Pelayanan...';
    }

    if (notif) {
        notif.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Sedang menutup jadwal pelayanan hari ini di BPJS HFIS dan memproses antrean...</div>';
    }

    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/tutuphariini?t=' + (mlite.token || '');

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onload = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-ban"></i> ⛔ Tutup Pelayanan Hari Ini (Dokter Berhalangan / Sakit)';
        }

        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success') {
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-success alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-check-circle"></i> Pelayanan Berhasil Ditutup!</h4>' +
                        '<p>' + res.message + '</p>' +
                        '</div>';
                }
                setTimeout(function () {
                    muatJadwalAktif(false);
                }, 1000);
            } else {
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-danger alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-exclamation-triangle"></i> Gagal Menutup Pelayanan</h4>' +
                        '<p>' + (res.message || 'Terjadi kesalahan saat memproses penutupan di server BPJS.') + '</p>' +
                        '</div>';
                }
            }
        } catch (e) {
            alert("Respon Server: " + this.responseText);
        }

        if (notif && notif.scrollIntoView) {
            notif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    http.onerror = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-ban"></i> ⛔ Tutup Pelayanan Hari Ini (Dokter Berhalangan / Sakit)';
        }
        alert("Gagal menghubungi server SIMRS. Silakan periksa koneksi.");
    };

    var params = 'kd_dokter=' + encodeURIComponent(kd_dokter) +
        '&kd_poli=' + encodeURIComponent(kd_poli) +
        '&batal_antrean=' + encodeURIComponent(batal_antrean) +
        '&alasan_batal=' + encodeURIComponent(alasan_batal);

    http.send(params);
}

// -------------------------------------------------------------
// FITUR OPERASIONAL: PULIHKAN JADWAL NORMAL SIMRS KE BPJS HFIS
// -------------------------------------------------------------
function toggleBtnEksekusiPulihkan() {
    var chk = document.getElementById("chkSetujuPulihkan");
    var btn = document.getElementById("btnEksekusiPulihkan");
    if (btn) {
        btn.disabled = chk ? !chk.checked : false;
    }
}

function konfirmasiPulihkanJadwal() {
    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';

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

    var selDoc = document.getElementById("kd_dokter");
    var docName = selDoc && selDoc.options[selDoc.selectedIndex] ? selDoc.options[selDoc.selectedIndex].text : kd_dokter;
    var selPoli = document.getElementById("kd_poli");
    var poliName = selPoli && selPoli.options[selPoli.selectedIndex] ? selPoli.options[selPoli.selectedIndex].text : kd_poli;

    var elDoc = document.getElementById("modalPulihkanDocName");
    var elPoli = document.getElementById("modalPulihkanPoliName");
    var elDetail = document.getElementById("modalPulihkanDetailJadwal");

    if (elDoc) elDoc.innerText = docName;
    if (elPoli) elPoli.innerText = poliName;

    if (elDetail) {
        elDetail.innerHTML = '<i class="fa fa-check-circle"></i> Sistem akan membaca seluruh jadwal praktek dokter ini yang tersimpan di database SIMRS lokal dan menyinkronkannya kembali ke server BPJS HFIS.';
    }

    var chk = document.getElementById("chkSetujuPulihkan");
    if (chk) chk.checked = false;
    toggleBtnEksekusiPulihkan();

    if (window.$) {
        $('#modalPulihkanJadwal').modal('show');
    }
}

function eksekusiPulihkanJadwal() {
    if (window.$) {
        $('#modalPulihkanJadwal').modal('hide');
    }

    var kd_dokter = document.getElementById("kd_dokter") ? document.getElementById("kd_dokter").value : '';
    var kd_poli = document.getElementById("kd_poli") ? document.getElementById("kd_poli").value : '';
    var btn = document.getElementById("btnPulihkanJadwal");
    var notif = document.getElementById("notif");

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memulihkan...';
    }

    if (notif) {
        notif.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Sedang memulihkan master jadwal dokter ke server BPJS HFIS...</div>';
    }

    var baseURL = mlite.url + '/' + mlite.admin;
    var url = baseURL + '/bridging_hfis/pulihkanjadwal?t=' + (mlite.token || '');

    var http = new XMLHttpRequest();
    http.open('POST', url, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onload = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-refresh"></i> 🔄 Pulihkan Jadwal Normal SIMRS';
        }

        try {
            var res = JSON.parse(this.responseText);
            if (res.status === 'success') {
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-success alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-check-circle"></i> Jadwal Berhasil Dipulihkan!</h4>' +
                        '<p>' + res.message + '</p>' +
                        '</div>';
                }
                setTimeout(function () {
                    muatJadwalAktif(false);
                }, 800);
            } else {
                if (notif) {
                    notif.innerHTML = '<div class="alert alert-danger alert-dismissible" style="border-radius:4px;">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<h4><i class="fa fa-exclamation-triangle"></i> Gagal Memulihkan Jadwal</h4>' +
                        '<p>' + (res.message || 'Terjadi kesalahan saat memulihkan jadwal.') + '</p>' +
                        '</div>';
                }
            }
        } catch (e) {
            alert("Respon Server: " + this.responseText);
        }

        if (notif && notif.scrollIntoView) {
            notif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    http.onerror = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-refresh"></i> 🔄 Pulihkan Jadwal Normal SIMRS';
        }
        alert("Gagal menghubungi server SIMRS. Silakan periksa koneksi.");
    };

    http.send('kd_dokter=' + encodeURIComponent(kd_dokter) + '&kd_poli=' + encodeURIComponent(kd_poli));
}

// Inisialisasi tampilan kartu & sinkronisasi Kalypto saat dokumen siap
function initCardVisuals() {
    for (var i = 0; i < daysConfig.length; i++) {
        var cfg = daysConfig[i];
        var el = document.getElementById(cfg.chk);
        var isChecked = el ? el.checked : false;
        syncKalyptoToggle(el, isChecked);
        updateCardVisual(cfg.chk, isChecked);
    }
}

if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initCardVisuals();
        setTimeout(initCardVisuals, 150);
    });
} else {
    document.addEventListener("DOMContentLoaded", function() {
        initCardVisuals();
        setTimeout(initCardVisuals, 150);
    });
}

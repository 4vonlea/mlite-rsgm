// sembunyikan notif
$("#notif").hide();

// tombol buka form diklik
$(document).on('click', '#bukaform', function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  $("#form").show().load(baseURL + '/pasien/form?t=' + mlite.token);
  $(this).addClass('is-open').html('<i class="fa fa-times"></i> <span>Tutup Form</span>').val("Tutup Form").attr("id", "tutupform");
});

// tombol tutup form diklik
$(document).on('click', '#tutupform', function (event) {
  event.preventDefault();
  $("#form").hide();
  $(this).removeClass('is-open').html('<i class="fa fa-user-plus"></i> <span>Pasien Baru</span>').val("Buka Form").attr("id", "bukaform");
});

// ketika inputbox no_rm_medis diklik
$(document).on("click", "#no_rkm_medis", function (event) {
  if ($("#norm_manual").is(':checked') || $(this).prop('disabled')) {
    return;
  }
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/pasien/maxid?t=' + mlite.token;
  $.post(url, {}, function (data) {
    $("#no_rkm_medis").val(data);
  });
});

// ketika toggle manual dicentang / dilepas
$(document).on("change", "#norm_manual", function () {
  var isManual = $(this).is(':checked');
  var $rmInput = $("#no_rkm_medis");
  if (isManual) {
    $rmInput.removeClass('rsgm-auto-input').addClass('is-manual');
    $rmInput.attr('placeholder', 'No. RM');
    $rmInput.focus().select();
  } else {
    $rmInput.removeClass('is-manual').addClass('rsgm-auto-input');
    $rmInput.attr('placeholder', 'Otomatis');
    var baseURL = mlite.url + '/' + mlite.admin;
    $.post(baseURL + '/pasien/maxid?t=' + mlite.token, {}, function (data) {
      if (data && !$('#norm_manual').is(':checked')) {
        $rmInput.val(data);
      }
    });
  }
});

// tombol batal diklik
$(document).on("click", "#batal", function (event) {
  bersih();
});

// Sanitasi input real-time agar sesuai standar panjang & format angka
$(document).on('input', '#no_rkm_medis', function () {
  if ($(this).prop('disabled')) return;
  var cleaned = String($(this).val() || '').replace(/[^0-9]/g, '').slice(0, 6);
  if ($(this).val() !== cleaned) $(this).val(cleaned);
});

$(document).on('input', '#no_ktp', function () {
  var cleaned = String($(this).val() || '').replace(/[^0-9]/g, '').slice(0, 16);
  if ($(this).val() !== cleaned) $(this).val(cleaned);
});

$(document).on('input', '#no_peserta', function () {
  var kdPj = String($('select[name=kd_pj]').val() || '').toUpperCase();
  var val = String($(this).val() || '');
  if (kdPj === 'BPJ') {
    var cleaned = val.replace(/[^0-9]/g, '').slice(0, 13);
    if (val !== cleaned) $(this).val(cleaned);
  } else if (val.length > 25) {
    $(this).val(val.slice(0, 25));
  }
});

$(document).on('change', 'select[name=kd_pj]', function () {
  var kdPj = String($(this).val() || '').toUpperCase();
  var $noPeserta = $('#no_peserta');
  if (!$noPeserta.length) return;
  if (kdPj === 'BPJ') {
    $noPeserta.attr('maxlength', '13').attr('inputmode', 'numeric');
    var v = String($noPeserta.val() || '').replace(/[^0-9]/g, '').slice(0, 13);
    $noPeserta.val(v);
  } else {
    $noPeserta.attr('maxlength', '25').removeAttr('inputmode');
  }
});

$(document).on('input', 'input[name=no_tlp]', function () {
  var raw = String($(this).val() || '');
  var hasPlus = raw.charAt(0) === '+';
  var digits = raw.replace(/[^0-9]/g, '').slice(0, hasPlus ? 14 : 15);
  var cleaned = (hasPlus ? '+' : '') + digits;
  if (raw !== cleaned) $(this).val(cleaned);
});

function showFormValidationError(message) {
  var notifEl = document.getElementById('notif');
  if (!notifEl) return;
  notifEl.replaceChildren();
  var errDiv = document.createElement('div');
  errDiv.className = 'alert alert-warning alert-dismissible fade in';
  errDiv.setAttribute('role', 'alert');
  errDiv.style.borderRadius = '0px';
  errDiv.style.marginTop = '-15px';
  errDiv.textContent = message;
  var closeX = document.createElement('button');
  closeX.type = 'button';
  closeX.className = 'close';
  closeX.setAttribute('data-dismiss', 'alert');
  closeX.setAttribute('aria-label', 'Close');
  closeX.textContent = '×';
  errDiv.appendChild(closeX);
  notifEl.appendChild(errDiv);
  $(notifEl).show();
  $('html, body').animate({ scrollTop: 0 }, 200);
}

// tombol simpan diklik
$(document).on("click", "#simpan", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rkm_medis = $.trim($('input:text[name=no_rkm_medis]').val() || '');
  var nm_pasien = $.trim($('input:text[name=nm_pasien]').val() || '');
  var nm_ibu = $.trim($('input:text[name=nm_ibu]').val() || '');
  if (!nm_ibu) { nm_ibu = '-'; }
  var tgl_lahir_raw = $.trim($('#tgl_lahir').val() || '');
  var tgl_lahir = tgl_lahir_raw;
  var tgl_lahir_m = moment(tgl_lahir_raw, ['DD-MM-YYYY', 'D-M-YYYY', 'DD/MM/YYYY', 'D/M/YYYY', 'YYYY-MM-DD'], true);
  if (tgl_lahir_m.isValid()) {
    tgl_lahir = tgl_lahir_m.format('YYYY-MM-DD');
  }
  var jk = $('select[name=jk]').val();
  var gol_darah = $('select[name=gol_darah]').val();
  var stts_nikah = $('select[name=stts_nikah]').val();
  var agama = $('select[name=agama]').val();
  var pekerjaan = $('input:text[name=pekerjaan]').val();
  var no_ktp = $.trim($('input[name=no_ktp]').val() || '');
  var alamat = $('textarea[name=alamat]').val();
  var no_tlp = $.trim($('input:text[name=no_tlp]').val() || '');
  var tgl_daftar = $('input:text[name=tgl_daftar]').val();
  var email = $.trim($('input[name=email]').val() || '');
  var pnd = $('select[name=pnd]').val();
  var keluarga = $('select[name=keluarga]').val();
  var namakeluarga = $('input:text[name=namakeluarga]').val();
  var kd_prop = $('#kd_prop').val();
  var kd_kab = $('#kd_kab').val();
  var kd_kec = $('#kd_kec').val();
  var kd_kel = $('#kd_kel').val();
  var nm_prop = $('#nm_prop').val();
  var nm_kab = $('#nm_kab').val();
  var nm_kec = $('#nm_kec').val();
  var nm_kel = $('#nm_kel').val();
  var kd_pj = $('select[name=kd_pj]').val();
  var no_peserta = $.trim($('input:text[name=no_peserta]').val() || '');
  var manual = $('#norm_manual').prop("checked") ? 1 : 0;
  var isEditRm = $('input:text[name=no_rkm_medis]').prop('disabled');

  var url = baseURL + '/pasien/save?t=' + mlite.token;

  if (manual == 1 && !isEditRm) {
    if (no_rkm_medis === '') {
      showFormValidationError('Nomor Rekam Medis manual masih kosong!');
      return;
    }
    if (!/^[0-9]{1,6}$/.test(no_rkm_medis)) {
      showFormValidationError('Nomor Rekam Medis harus berupa angka maksimal 6 digit (contoh: 061420).');
      return;
    }
    no_rkm_medis = no_rkm_medis.padStart(6, '0');
    $('input:text[name=no_rkm_medis]').val(no_rkm_medis);
  }

  if (nm_pasien === '') {
    showFormValidationError('Nama pasien masih kosong!');
    return;
  }

  if (tgl_lahir_raw === '' || !tgl_lahir_m.isValid()) {
    showFormValidationError('Tanggal lahir wajib diisi dengan format valid (DD-MM-YYYY).');
    return;
  }

  var todayIso = moment().format('YYYY-MM-DD');
  if (tgl_lahir < '1900-01-01' || tgl_lahir > todayIso) {
    showFormValidationError('Tanggal lahir tidak valid (harus antara 01-01-1900 sampai hari ini).');
    return;
  }

  if (jk !== 'L' && jk !== 'P') {
    showFormValidationError('Jenis kelamin belum dipilih!');
    return;
  }

  if (no_ktp !== '' && no_ktp !== '-') {
    if (!/^[0-9]{16}$/.test(no_ktp) || /^0+$/.test(no_ktp)) {
      showFormValidationError('Nomor KTP / NIK harus tepat 16 digit angka yang valid (atau kosongkan jika belum memiliki NIK).');
      return;
    }
  }

  if (no_peserta !== '' && no_peserta !== '-') {
    if (String(kd_pj || '').toUpperCase() === 'BPJ') {
      if (!/^[0-9]{13}$/.test(no_peserta) || /^0+$/.test(no_peserta)) {
        showFormValidationError('Nomor Kartu BPJS harus tepat 13 digit angka yang valid.');
        return;
      }
    } else if (no_peserta.length > 25) {
      showFormValidationError('Nomor Kartu Asuransi maksimal 25 karakter.');
      return;
    }
  }

  if (no_tlp !== '' && no_tlp !== '-') {
    if (!/^\+?[0-9]{9,15}$/.test(no_tlp)) {
      showFormValidationError('Nomor Telepon harus berupa 9 sampai 15 digit angka (boleh diawali tanda +).');
      return;
    }
  }

  if (email !== '' && email !== '-') {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFormValidationError('Format alamat email tidak valid (kosongkan atau isi "-" jika tidak ada).');
      return;
    }
  }

  {
    var payload = {
      no_rkm_medis: no_rkm_medis,
      nm_pasien: nm_pasien,
      nm_ibu: nm_ibu,
      tgl_lahir: tgl_lahir,
      jk: jk,
      gol_darah: gol_darah,
      stts_nikah: stts_nikah,
      agama: agama,
      pekerjaan: pekerjaan,
      no_ktp: no_ktp,
      alamat: alamat,
      no_tlp: no_tlp,
      tgl_daftar: tgl_daftar,
      email: email,
      pnd: pnd,
      keluarga: keluarga,
      namakeluarga: namakeluarga,
      kd_prop: kd_prop,
      kd_kab: kd_kab,
      kd_kec: kd_kec,
      kd_kel: kd_kel,
      nm_prop: nm_prop,
      nm_kab: nm_kab,
      nm_kec: nm_kec,
      nm_kel: nm_kel,
      kd_pj: kd_pj,
      no_peserta: no_peserta,
      manual: manual
    };

    function showDuplicateModal(resp, allowConfirm) {
      var modalId = 'rsgmDuplicatePasienModal';
      var existing = document.getElementById(modalId);
      if (existing && existing.parentNode) {
        existing.parentNode.removeChild(existing);
      }

      var modal = document.createElement('div');
      modal.id = modalId;
      modal.className = 'modal fade';
      modal.setAttribute('tabindex', '-1');
      modal.setAttribute('role', 'dialog');

      var dialog = document.createElement('div');
      dialog.className = 'modal-dialog modal-lg';
      dialog.setAttribute('role', 'document');

      var content = document.createElement('div');
      content.className = 'modal-content';

      var header = document.createElement('div');
      header.className = 'modal-header';
      header.style.background = allowConfirm ? '#fffbeb' : '#fef2f2';
      header.style.borderBottom = '1px solid ' + (allowConfirm ? '#fde68a' : '#fecaca');

      var closeBtn = document.createElement('button');
      closeBtn.type = 'button';
      closeBtn.className = 'close';
      closeBtn.setAttribute('data-dismiss', 'modal');
      closeBtn.textContent = '×';
      header.appendChild(closeBtn);

      var title = document.createElement('h4');
      title.className = 'modal-title';
      title.style.fontWeight = '700';
      title.style.color = allowConfirm ? '#92400e' : '#991b1b';
      title.textContent = allowConfirm
        ? 'Peringatan Potensi Pasien Duplikat (Nama & Tgl Lahir Sama)'
        : 'Pendaftaran Ditolak — Data Identitas Sudah Terdaftar';
      header.appendChild(title);
      content.appendChild(header);

      var body = document.createElement('div');
      body.className = 'modal-body';

      var msgP = document.createElement('p');
      msgP.style.fontSize = '13px';
      msgP.style.color = '#334155';
      msgP.textContent = resp.message || resp.msg || 'Ditemukan data pasien serupa.';
      body.appendChild(msgP);

      var tableWrap = document.createElement('div');
      tableWrap.className = 'table-responsive';
      var tbl = document.createElement('table');
      tbl.className = 'table table-bordered table-striped table-condensed';
      var thead = document.createElement('thead');
      var hTr = document.createElement('tr');
      ['No. RM', 'Nama Pasien', 'Tgl Lahir', 'NIK / BPJS', 'Alamat', 'Aksi'].forEach(function (h) {
        var th = document.createElement('th');
        th.textContent = h;
        hTr.appendChild(th);
      });
      thead.appendChild(hTr);
      tbl.appendChild(thead);

      var tbody = document.createElement('tbody');
      (resp.matches || []).forEach(function (m) {
        var tr = document.createElement('tr');
        var tdRm = document.createElement('td');
        var rmCode = document.createElement('strong');
        rmCode.textContent = m.no_rkm_medis || '-';
        tdRm.appendChild(rmCode);
        tr.appendChild(tdRm);

        var tdNm = document.createElement('td');
        tdNm.textContent = m.nm_pasien || '-';
        tr.appendChild(tdNm);

        var tdDob = document.createElement('td');
        tdDob.textContent = m.tgl_lahir || '-';
        tr.appendChild(tdDob);

        var tdId = document.createElement('td');
        var nikTxt = m.no_ktp && m.no_ktp !== '-' ? ('NIK: ' + m.no_ktp) : '';
        var bpjsTxt = m.no_peserta && m.no_peserta !== '-' ? ('BPJS: ' + m.no_peserta) : '';
        tdId.textContent = [nikTxt, bpjsTxt].filter(Boolean).join(' | ') || '-';
        tr.appendChild(tdId);

        var tdAlmt = document.createElement('td');
        tdAlmt.textContent = m.alamat || '-';
        tr.appendChild(tdAlmt);

        var tdAct = document.createElement('td');
        var useBtn = document.createElement('button');
        useBtn.type = 'button';
        useBtn.className = 'btn btn-xs btn-primary';
        useBtn.textContent = 'Gunakan Pasien Ini';
        useBtn.addEventListener('click', function () {
          $('#' + modalId).modal('hide');
          if ($('#tutupform').length) {
            $('#tutupform').click();
          } else {
            $('#form').hide();
          }
          if ($('#searchInput').length) {
            $('#searchInput').val(m.no_rkm_medis || '');
            if (typeof loadData === 'function') {
              loadData(1);
            }
          }
        });
        tdAct.appendChild(useBtn);
        tr.appendChild(tdAct);
        tbody.appendChild(tr);
      });
      tbl.appendChild(tbody);
      tableWrap.appendChild(tbl);
      body.appendChild(tableWrap);
      content.appendChild(body);

      var footer = document.createElement('div');
      footer.className = 'modal-footer';

      var cancelBtn = document.createElement('button');
      cancelBtn.type = 'button';
      cancelBtn.className = 'btn btn-default';
      cancelBtn.setAttribute('data-dismiss', 'modal');
      cancelBtn.textContent = 'Tutup & Periksa Kembali';
      footer.appendChild(cancelBtn);

      if (allowConfirm) {
        var forceBtn = document.createElement('button');
        forceBtn.type = 'button';
        forceBtn.className = 'btn btn-warning';
        forceBtn.textContent = 'Tetap Simpan Sebagai Pasien Baru';
        forceBtn.addEventListener('click', function () {
          $('#' + modalId).modal('hide');
          payload.confirm_duplicate = 1;
          submitPasienSave();
        });
        footer.appendChild(forceBtn);
      }

      content.appendChild(footer);
      dialog.appendChild(content);
      modal.appendChild(dialog);
      document.body.appendChild(modal);
      $('#' + modalId).modal('show');
    }

    function submitPasienSave() {
      $.post(url, payload, function (data) {
        if (typeof data === 'string') data = JSON.parse(data);

        if (data.status === 'duplicate_block' || data.status === 'duplicate_warn') {
          var warnAudio = new Audio('{?=url()?}/assets/sound/error.mp3');
          warnAudio.play().catch(function () {});
          showDuplicateModal(data, data.status === 'duplicate_warn');
          return;
        }

        var soundName = (data.status === 'success') ? 'success' : 'error';
        var audio = new Audio('{?=url()?}/assets/sound/' + soundName + '.mp3');
        audio.play().catch(function () {});
        if (data.status == 'success') {
          if (typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1) {
            let wsPayload = {
              'action': 'simpan',
              'modul': 'pasien'
            }
            ws.send(JSON.stringify(wsPayload));
          } else {
            loadData();
          }
          bersih();
          $("#status_pendaftaran").hide();
          var notifEl = document.getElementById('notif');
          if (notifEl) {
            notifEl.replaceChildren();
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade in';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.style.borderRadius = '0px';
            alertDiv.style.marginTop = '-15px';
            alertDiv.textContent = 'Data pendaftaran pasien telah disimpan!';
            var closeX = document.createElement('button');
            closeX.type = 'button';
            closeX.className = 'close';
            closeX.setAttribute('data-dismiss', 'alert');
            closeX.setAttribute('aria-label', 'Close');
            closeX.textContent = '×';
            alertDiv.appendChild(closeX);
            notifEl.appendChild(alertDiv);
            $(notifEl).show();
            $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
          }
        }
        if (data.status == 'error') {
          var errorMsg = data.message || data.msg || 'Terjadi kesalahan sistem.';
          var errEl = document.getElementById('notif');
          if (errEl) {
            errEl.replaceChildren();
            var errDiv = document.createElement('div');
            errDiv.className = 'alert alert-danger alert-dismissible fade in';
            errDiv.setAttribute('role', 'alert');
            errDiv.style.borderRadius = '0px';
            errDiv.style.marginTop = '-15px';
            errDiv.appendChild(document.createTextNode('Gagal menyimpan data pendaftaran pasien!'));
            errDiv.appendChild(document.createElement('br'));
            errDiv.appendChild(document.createTextNode(errorMsg));
            var errClose = document.createElement('button');
            errClose.type = 'button';
            errClose.className = 'close';
            errClose.setAttribute('data-dismiss', 'alert');
            errClose.setAttribute('aria-label', 'Close');
            errClose.textContent = '×';
            errDiv.appendChild(errClose);
            errEl.appendChild(errDiv);
            $(errEl).show();
            $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
          }
        }
      });
    }

    submitPasienSave();
  }

});

// ketika baris data diklik
$("#display").on("click", ".edit", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/pasien/form?t=' + mlite.token;
  var no_rkm_medis = $(this).attr("data-no_rkm_medis");

  $.post(url, { no_rkm_medis: no_rkm_medis }, function (data) {
    // tampilkan data
    $("#form").html(data).show();
    $("#bukaform").addClass('is-open').html('<i class="fa fa-times"></i> <span>Tutup Form</span>').val("Tutup Form");
    $("#bukaform").attr("id", "tutupform");
    $("#kartu").removeClass('hidden');
    $("#kirimwa").removeClass('hidden');
    $("#foto").removeClass('hidden');
    $("#hapus").removeClass('hidden');
  });
});

// ketika tombol hapus diklik
$(document).on("click", "#hapus", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/pasien/hapus?t=' + mlite.token;
  var no_rkm_medis = $('input:text[name=no_rkm_medis]').val();

  // tampilkan dialog konfirmasi
  bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function (result) {
    // ketika ditekan tombol ok
    if (result) {
      // mengirimkan perintah penghapusan
      $.post(url, {
        no_rkm_medis: no_rkm_medis
      }, function (data) {
        if (typeof data === 'string') data = JSON.parse(data);

        var audio = new Audio('{?=url()?}/assets/sound/' + data.status + '.mp3');
        audio.play();

        // sembunyikan form, tampilkan data yang sudah di perbaharui, tampilkan notif
        $("#form").hide();
        $("#tutupform").val("Buka Form");
        $("#tutupform").attr("id", "bukaform");
        loadData();

        if (data.status == 'success') {
          $('#notif').html("<div class=\"alert alert-success alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
            "Data pasien telah dihapus!" +
            "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
            "</div>").show();
          $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
        } else {
          var errorMsg = data.message || data.msg || 'Terjadi kesalahan sistem.';
          $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
            "Gagal menghapus data: " + errorMsg +
            "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
            "</div>").show();
          $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
        }
      });
    }
  });
});

// ketika inputbox pencarian diisi
// $('input:text[name=cari]').on('input',function(e){
// Debounce logic or just rely on Enter key in manage.html
// To avoid conflict with apiList implementation in manage.html, we disable this old handler
// if(typeof loadData === 'function') {
//    loadData(1);
// }
// });

// ketika tombol halaman ditekan
// $("#display").on("click", ".halaman",function(event){
// Legacy pagination handler - disabled
/*
var baseURL = mlite.url + '/' + mlite.admin;
event.preventDefault();
var url    = baseURL + '/pasien/display?t=' + mlite.token;
kd_hal  = $(this).attr("data-hal");
var cari = $('input:text[name=cari]').val();
if(cari !='') {
  $.post(url, {halaman: kd_hal, cari: cari} ,function(data) {
    // tampilkan data
    $("#notif").hide();
    $("#display").html(data).show();
  });
} else {
  $.post(url, {halaman: kd_hal} ,function(data) {
    // tampilkan data
    $("#notif").hide();
    $("#display").html(data).show();
  });
}
*/
// });

$("#display").on("click", ".riwayat_perawatan", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rkm_medis = $(this).attr("data-no_rkm_medis");
  window.open(baseURL + '/pasien/riwayatperawatan/' + no_rkm_medis + '?t=' + mlite.token);
});

// ketika tombol cetak kartu ditekan
$(document).on("click", "#kartu", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rkm_medis = $(this).attr("data-no_rkm_medis");
  // window.open(baseURL + '/pasien/kartu?no_rkm_medis=' + no_rkm_medis + '&t=' + mlite.token);
  $("#printModal").modal('show').html('<div style="text-align:center;margin:20px auto;width:50%;height:50%;"><iframe src="' + baseURL + '/pasien/cetakkartu/' + no_rkm_medis + '?t=' + mlite.token + '" frameborder="no" width="100%" height="100%"></iframe></div>');
});

// reset form
function bersih() {
  var baseURL = mlite.url + '/' + mlite.admin;
  var $rmInput = $('input:text[name=no_rkm_medis]');
  $rmInput.val("").removeAttr('disabled').removeClass('is-manual').addClass('rsgm-auto-input').attr('placeholder', 'Otomatis');
  $('#norm_manual').prop('checked', false).removeAttr('disabled');
  
  // Ambil No. RM baru otomatis
  $.post(baseURL + '/pasien/maxid?t=' + mlite.token, {}, function(data) {
    if (data && !$('#norm_manual').is(':checked')) {
      $rmInput.val(data);
    }
  });

  $('input:text[name=nm_pasien]').val("");
  $('#tgl_lahir').val("");
  $('input:text[name=nm_ibu]').val("-");
  $('input:text[name=pekerjaan]').val("-");
  $('input:text[name=no_ktp]').val("");
  $('textarea[name=alamat]').val("");
  $('input:text[name=no_tlp]').val("");
  $('input:text[name=no_peserta]').val("");
  $('input:text[name=namakeluarga]').val("-");
  $('#tgl_daftar').val(moment().format('YYYY-MM-DD'));
  $('#email').val("-");

  // Reset wilayah
  $('#nm_prop').val("");
  $('#nm_kab').val("");
  $('#nm_kec').val("");
  $('#nm_kel').val("");
  $('#kd_prop').val("");
  if (typeof resetWilayahDownstream === 'function') {
    resetWilayahDownstream(2);
  }

  // Reset selects
  try {
    $('select').selectator('destroy');
  } catch (e) { }
  $('select[name=jk]').val("L");
  $('select[name=gol_darah]').val("-");
  $('select[name=stts_nikah]').val("BELUM MENIKAH");
  $('select[name=agama]').val("ISLAM");
  $('select[name=pnd]').val("-");
  $('select[name=keluarga]').val("AYAH");
  $('select').selectator();

  $('#kartu, #kirimwa, #foto, #hapus').addClass('hidden');
}

// dropdown select pada baris data
$(document).click(function (event) {
  $('.dropdown-menu[data-parent]').hide();
});

$(document).on('click', '.table-responsive [data-toggle="dropdown"]', function () {
  if ($('body').hasClass('modal-open')) {
    throw new Error("This solution is not working inside a responsive table inside a modal, you need to find out a way to calculate the modal Z-index and add it to the element")
    return true;
  }

  $buttonGroup = $(this).parent();
  if (!$buttonGroup.attr('data-attachedUl')) {
    var ts = +new Date;
    $ul = $(this).siblings('ul');
    $ul.attr('data-parent', ts);
    $buttonGroup.attr('data-attachedUl', ts);
    $(window).resize(function () {
      $ul.css('display', 'none').data('top');
    });
  } else {
    $ul = $('[data-parent=' + $buttonGroup.attr('data-attachedUl') + ']');
  }
  if (!$buttonGroup.hasClass('open')) {
    $ul.css('display', 'none');
    return;
  }
  dropDownFixPosition($(this).parent(), $ul);
  function dropDownFixPosition(button, dropdown) {
    var dropDownTop = button.offset().top + button.outerHeight();
    dropdown.css('top', dropDownTop - 60 + "px");
    dropdown.css('left', button.offset().left + 7 + "px");
    dropdown.css('position', "absolute");

    dropdown.css('width', dropdown.width());
    dropdown.css('heigt', dropdown.height());
    dropdown.css('display', 'block');
    dropdown.appendTo('body');
  }
});

$('body').on('hidden.bs.modal', '.modal', function () {
  $(this).removeData('bs.modal');
});

$(function (event) {
  if (window.location.href.indexOf("nama") > -1) {
    var baseURL = mlite.url + '/' + mlite.admin;
    event.preventDefault();
    $("#form").show().load(baseURL + '/pasien/form?t=' + mlite.token);
    $("#bukaform").val("Tutup Form");
    $("#bukaform").attr("id", "tutupform");
  }
});

{if: $mlite.websocket == 'ya'}

{if: $mlite.websocket_proxy != ''}
var URL_WEBSOCKET = "{$mlite.websocket_proxy}";
{else}
var URL_WEBSOCKET = "wss://<?php echo $_SERVER['HTTP_HOST'] ?>:3892";
{/if}

var ws = new WebSocket(URL_WEBSOCKET);
var baseURL = mlite.url + '/' + mlite.admin;

ws.onmessage = function (response) {
  try {
    output = JSON.parse(response.data);
    if (output['action'] == 'simpan') {
      if (output['modul'] == 'pasien') {
        if (typeof loadData === 'function') {
          loadData();
        } else {
          $("#pasien #display").show().load(baseURL + '/pasien/display?t=' + mlite.token);
        }
      }
    }
  } catch (e) {
    // console.log(e);
  }
}

ws.onclose = function () {
  // Jika terputus dari websocket server, maka mencoba terhubung kembali.
  var interval_reconnect_ws = setInterval(function () {
    if (ws.readyState != 0) {
      if (ws.readyState == 1) { // readyState = 1 (Open) , berarti sudah terhubung dengan websocket. Maka gak perlu interval lagi.
        clearInterval(interval_reconnect_ws);
      } else {
        ws = new WebSocket(URL_WEBSOCKET);
      }
    }

  }, 5000);
}

{/if}
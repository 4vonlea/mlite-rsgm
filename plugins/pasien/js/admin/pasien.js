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

// tombol simpan diklik
$(document).on("click", "#simpan", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rkm_medis = $('input:text[name=no_rkm_medis]').val();
  var nm_pasien = $('input:text[name=nm_pasien]').val();
  var nm_ibu = $('input:text[name=nm_ibu]').val();
  if (!nm_ibu) { nm_ibu = '-'; }
  var tgl_lahir = $('#tgl_lahir').val();
  var tgl_lahir_m = moment(tgl_lahir, ['DD-MM-YYYY', 'D-M-YYYY', 'DD/MM/YYYY', 'D/M/YYYY', 'YYYY-MM-DD'], true);
  if (tgl_lahir_m.isValid()) {
    tgl_lahir = tgl_lahir_m.format('YYYY-MM-DD');
  }
  var jk = $('select[name=jk]').val();
  var gol_darah = $('select[name=gol_darah]').val();
  var stts_nikah = $('select[name=stts_nikah]').val();
  var agama = $('select[name=agama]').val();
  var pekerjaan = $('input:text[name=pekerjaan]').val();
  var no_ktp = $('input[name=no_ktp]').val();
  var alamat = $('textarea[name=alamat]').val();
  var no_tlp = $('input:text[name=no_tlp]').val();
  var tgl_daftar = $('input:text[name=tgl_daftar]').val();
  var email = $('input:text[name=email]').val();
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
  var no_peserta = $('input:text[name=no_peserta]').val();
  var manual = $('#norm_manual').prop("checked") ? 1 : 0;

  var url = baseURL + '/pasien/save?t=' + mlite.token;

  if(manual == 1 && no_rkm_medis == '') {
    alert('Nomor rekam medis masih kosong!')
  }

  else if (nm_pasien == '') {
    alert('Nama pasien masih kosong!')
  }

  else if (tgl_lahir == '') {
    alert('Tanggal lahir masih kosong!')
  }

  else if (jk == '') {
    alert('Jenis kelamin belum dipilih!')
  }

  else {
    $.post(url, {
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
    }, function (data) {
      // console.log(data);
      if (typeof data === 'string') data = JSON.parse(data);

      var audio = new Audio('{?=url()?}/assets/sound/' + data.status + '.mp3');
      audio.play();
      if (data.status == 'success') {
        if (typeof ws != 'undefined' && typeof ws.readyState != 'undefined' && ws.readyState == 1) {
          let payload = {
            'action': 'simpan',
            'modul': 'pasien'
          }
          ws.send(JSON.stringify(payload));
          // console.log(payload);
        } else {
          loadData();
        }
        bersih();
        $("#status_pendaftaran").hide();
        $('#notif').html("<div class=\"alert alert-success alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Data pendaftaran pasien telah disimpan!" +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
        $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
      }
      if (data.status == 'error') {
        var errorMsg = data.message || data.msg || 'Terjadi kesalahan sistem.';
        $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Gagal menyimpan data pendaftaran pasien!<br>" +
          errorMsg +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
        $(".alert-dismissible").fadeTo(3000, 500).slideUp(500);
      }
    });
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
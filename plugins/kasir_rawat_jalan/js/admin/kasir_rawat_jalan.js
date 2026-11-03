// sembunyikan form dan notif
$("#form_rincian").hide();
$("#notif").hide();
$('#provider').hide();
$('#aturan_pakai').hide();

$('#manage').on('click', '#submit_periode_rawat_jalan', function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/display?t=' + mlite.token;
  var periode_rawat_jalan = $('input:text[name=periode_rawat_jalan]').val();
  var periode_rawat_jalan_akhir = $('input:text[name=periode_rawat_jalan_akhir]').val();

  if (periode_rawat_jalan == '') {
    alert('Tanggal awal masih kosong!')
  }
  if (periode_rawat_jalan_akhir == '') {
    alert('Tanggal akhir masih kosong!')
  }

  $.post(url, { periode_rawat_jalan: periode_rawat_jalan, periode_rawat_jalan_akhir: periode_rawat_jalan_akhir }, function (data) {
    // tampilkan data
    $("#form").show();
    $("#display").html(data).show();
    $("#form_rincian").hide();
    $("#form_soap").hide();
    $("#form_sep").hide();
    $("#notif").hide();
    $("#rincian").hide();
    $("#sep").hide();
    $("#soap").hide();
    $('.periode_rawat_jalan').datetimepicker('remove');
  });

});

$('#manage').on('click', '#belum_periode_rawat_jalan', function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/display?t=' + mlite.token;
  var periode_rawat_jalan = $('input:text[name=periode_rawat_jalan]').val();
  var periode_rawat_jalan_akhir = $('input:text[name=periode_rawat_jalan_akhir]').val();
  var status_periksa = 'belum';

  if (periode_rawat_jalan == '') {
    alert('Tanggal awal masih kosong!')
  }
  if (periode_rawat_jalan_akhir == '') {
    alert('Tanggal akhir masih kosong!')
  }

  $.post(url, { periode_rawat_jalan: periode_rawat_jalan, periode_rawat_jalan_akhir: periode_rawat_jalan_akhir, status_periksa: status_periksa }, function (data) {
    // tampilkan data
    $("#form").show();
    $("#display").html(data).show();
    $("#form_rincian").hide();
    $("#form_soap").hide();
    $("#form_sep").hide();
    $("#notif").hide();
    $("#rincian").hide();
    $("#sep").hide();
    $("#soap").hide();
    $('.periode_rawat_jalan').datetimepicker('remove');
  });

});

$('#manage').on('click', '#selesai_periode_rawat_jalan', function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/display?t=' + mlite.token;
  var periode_rawat_jalan = $('input:text[name=periode_rawat_jalan]').val();
  var periode_rawat_jalan_akhir = $('input:text[name=periode_rawat_jalan_akhir]').val();
  var status_periksa = 'selesai';

  if (periode_rawat_jalan == '') {
    alert('Tanggal awal masih kosong!')
  }
  if (periode_rawat_jalan_akhir == '') {
    alert('Tanggal akhir masih kosong!')
  }

  $.post(url, { periode_rawat_jalan: periode_rawat_jalan, periode_rawat_jalan_akhir: periode_rawat_jalan_akhir, status_periksa: status_periksa }, function (data) {
    // tampilkan data
    $("#form").show();
    $("#display").html(data).show();
    $("#form_rincian").hide();
    $("#form_soap").hide();
    $("#form_sep").hide();
    $("#notif").hide();
    $("#rincian").hide();
    $("#sep").hide();
    $("#soap").hide();
    $('.periode_rawat_jalan').datetimepicker('remove');
  });

});

$('#manage').on('click', '#lunas_periode_rawat_jalan', function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/display?t=' + mlite.token;
  var periode_rawat_jalan = $('input:text[name=periode_rawat_jalan]').val();
  var periode_rawat_jalan_akhir = $('input:text[name=periode_rawat_jalan_akhir]').val();
  var status_periksa = 'lunas';

  if (periode_rawat_jalan == '') {
    alert('Tanggal awal masih kosong!')
  }
  if (periode_rawat_jalan_akhir == '') {
    alert('Tanggal akhir masih kosong!')
  }

  $.post(url, { periode_rawat_jalan: periode_rawat_jalan, periode_rawat_jalan_akhir: periode_rawat_jalan_akhir, status_periksa: status_periksa }, function (data) {
    // tampilkan data
    $("#form").show();
    $("#display").html(data).show();
    $("#form_rincian").hide();
    $("#form_soap").hide();
    $("#form_sep").hide();
    $("#notif").hide();
    $("#rincian").hide();
    $("#sep").hide();
    $("#soap").hide();
    $('.periode_rawat_jalan').datetimepicker('remove');
  });

});

// tombol batal diklik
$("#rincian").on("click", "#selesai", function (event) {
  bersih();
  $("#form_rincian").hide();
  $("#form_soap").hide();
  $("#form").show();
  $("#display").show();
  $("#rincian").hide();
  $("#soap").hide();
});

// ketika baris data diklik
$("#display").on("click", ".layanan_obat", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var no_rawat = $(this).attr("data-no_rawat");
  var no_rkm_medis = $(this).attr("data-no_rkm_medis");
  var nm_pasien = $(this).attr("data-nm_pasien");
  var tgl_registrasi = $(this).attr("data-tgl_registrasi");
  var jam_reg = $(this).attr("data-jam_reg");

  var now = new Date();
  var d = String(now.getDate()).padStart(2, '0');
  var m = String(now.getMonth() + 1).padStart(2, '0');
  var y = now.getFullYear();
  var h = String(now.getHours()).padStart(2, '0');
  var min = String(now.getMinutes()).padStart(2, '0');
  var s = String(now.getSeconds()).padStart(2, '0');
  var newKdBilling = 'RJ.' + d + '.' + m + '.' + y + '.' + h + '.' + min + '.' + s;
  $('input:text[name=kd_billing]').val(newKdBilling);
  $('#tgl_billing').val(y + '-' + m + '-' + d);
  $('#jam_billing').val(h + ':' + min + ':' + s);

  $('input:text[name=no_rawat]').val(no_rawat);
  $('input:text[name=no_rkm_medis]').val(no_rkm_medis);
  $('input:text[name=nm_pasien]').val(nm_pasien);
  $('#tgl_registrasi_hidden').val(tgl_registrasi);
  $('#jam_reg_hidden').val(jam_reg);
  $("#display").hide();

  var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
  $.post(url, {
    no_rawat: no_rawat,
  }, function (data) {
    // tampilkan data
    $("#form_rincian").hide();
    $("#form").hide();
    $("#notif").hide();
    $("#rincian").html(data).show();
  }).fail(function (xhr, status, error) {
    alert("Gagal memuat rincian kasir: " + error + " (Status: " + xhr.status + "). Silakan cek error log server.");
    $("#display").show();
  });
});

// ketika inputbox pencarian diisi
$('input:text[name=layanan]').on('input', function (e) {
  var baseURL = mlite.url + '/' + mlite.admin;
  var url = baseURL + '/kasir_rawat_jalan/layanan?t=' + mlite.token;
  var layanan = $('input:text[name=layanan]').val();

  if (layanan != "") {
    $.post(url, { layanan: layanan }, function (data) {
      // tampilkan data yang sudah di perbaharui
      $("#layanan").html(data).show();
      $("#obat").hide();
      $("#radiologi").hide();
      $("#laboratorium").hide();
      $('#biaya').attr("readonly", true);
      $('#nm_perawatan').attr("readonly", true);
    });
  }

});
// end pencarian

// ketika inputbox pencarian diisi
$('input:text[name=obat]').on('input', function (e) {
  var baseURL = mlite.url + '/' + mlite.admin;
  var url = baseURL + '/kasir_rawat_jalan/obat?t=' + mlite.token;
  var obat = $('input:text[name=obat]').val();

  if (obat != "") {
    $.post(url, { obat: obat }, function (data) {
      // tampilkan data yang sudah di perbaharui
      $("#obat").html(data).show();
      $("#layanan").hide();
      $("#radiologi").hide();
      $("#laboratorium").hide();
      $('#biaya').attr("readonly", true);
      $('#nm_perawatan').attr("readonly", true);
    });
  }

});
// end pencarian

// ketika inputbox pencarian diisi
$('input:text[name=laboratorium]').on('input', function (e) {
  var baseURL = mlite.url + '/' + mlite.admin;
  var url = baseURL + '/kasir_rawat_jalan/laboratorium?t=' + mlite.token;
  var laboratorium = $('input:text[name=laboratorium]').val();

  if (laboratorium != "") {
    $.post(url, { laboratorium: laboratorium }, function (data) {
      // tampilkan data yang sudah di perbaharui
      $("#laboratorium").html(data).show();
      $("#layanan").hide();
      $("#obat").hide();
      $("#radiologi").hide();
      $('#biaya').attr("readonly", true);
      $('#nm_perawatan').attr("readonly", true);
    });
  }

});
// end pencarian

// ketika inputbox pencarian diisi
$('input:text[name=radiologi]').on('input', function (e) {
  var baseURL = mlite.url + '/' + mlite.admin;
  var url = baseURL + '/kasir_rawat_jalan/radiologi?t=' + mlite.token;
  var radiologi = $('input:text[name=radiologi]').val();

  if (radiologi != "") {
    $.post(url, { radiologi: radiologi }, function (data) {
      // tampilkan data yang sudah di perbaharui
      $("#radiologi").html(data).show();
      $("#layanan").hide();
      $("#obat").hide();
      $("#laboratorium").hide();
      $('#biaya').attr("readonly", true);
      $('#nm_perawatan').attr("readonly", true);
    });
  }

});
// end pencarian

// ketika baris data diklik
$("#layanan").on("click", ".pilih_layanan", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var nm_perawatan = $(this).attr("data-nm_perawatan");
  var biaya = $(this).attr("data-biaya");
  var kat = $(this).attr("data-kat");

  $('input:hidden[name=kd_jenis_prw]').val(kd_jenis_prw);
  $('input:text[name=nm_perawatan]').val(nm_perawatan);
  $('input:text[name=biaya]').val(biaya);
  $('input:hidden[name=kat]').val(kat);

  $("#layanan").hide();
  $('#provider').show();
  $('#aturan_pakai').hide();
});

// ketika baris data diklik
$("#obat").on("click", ".pilih_obat", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var kode_brng = $(this).attr("data-kode_brng");
  var nama_brng = $(this).attr("data-nama_brng");
  var biaya = $(this).attr("data-dasar");
  var kat = $(this).attr("data-kat");

  $('input:hidden[name=kd_jenis_prw]').val(kode_brng);
  $('input:text[name=nm_perawatan]').val(nama_brng);
  $('input:text[name=biaya]').val(biaya);
  $('input:hidden[name=kat]').val(kat);

  /*$('#jumlah_jual').val(1);
  var jumlah_jual  = $('input:text[name=jumlah_jual]').val();

  $('#jumlah_jual').removeAttr("disabled");
  $('#potongan').removeAttr("disabled");
  $('#jumlah_jual').focus();

  var total = (Number(harga)) * (Number(jumlah_jual));
  $('input:text[name=total]').val(total);*/

  $('#obat').hide();
  $('#aturan_pakai').show();
  $('#rawat_jl_dr').show();
});

// ketika baris data diklik
$("#laboratorium").on("click", ".pilih_laboratorium", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var nm_perawatan = $(this).attr("data-nm_perawatan");
  var biaya = $(this).attr("data-biaya");
  var kat = $(this).attr("data-kat");

  $('input:hidden[name=kd_jenis_prw]').val(kd_jenis_prw);
  $('input:text[name=nm_perawatan]').val(nm_perawatan);
  $('input:text[name=biaya]').val(biaya);
  $('input:hidden[name=kat]').val(kat);

  $("#laboratorium").hide();
  $('#provider').show();
  $('#aturan_pakai').hide();
});

// ketika baris data diklik
$("#radiologi").on("click", ".pilih_radiologi", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var nm_perawatan = $(this).attr("data-nm_perawatan");
  var biaya = $(this).attr("data-biaya");
  var kat = $(this).attr("data-kat");

  $('input:hidden[name=kd_jenis_prw]').val(kd_jenis_prw);
  $('input:text[name=nm_perawatan]').val(nm_perawatan);
  $('input:text[name=biaya]').val(biaya);
  $('input:hidden[name=kat]').val(kat);

  $("#radiologi").hide();
  $('#provider').show();
  $('#aturan_pakai').hide();
});

// ketika tombol simpan diklik
$("#form_rincian").on("click", "#simpan_rincian", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();

  var no_rawat = $('input:text[name=no_rawat]').val();
  var kd_jenis_prw = $('input:hidden[name=kd_jenis_prw]').val();
  var nm_perawatan = $('input:text[name=nm_perawatan]').val();
  var provider = $('select[name=provider]').val();
  var kode_provider = $('input:text[name=kode_provider]').val();
  var kode_provider2 = $('input:text[name=kode_provider2]').val();
  var tgl_perawatan = $('input:text[name=tgl_billing]').val();
  var jam_rawat = $('input:text[name=jam_billing]').val();
  var biaya = $('input:text[name=biaya]').val();
  var aturan_pakai = $('input:text[name=aturan_pakai]').val();
  var kat = $('input:hidden[name=kat]').val();
  var jml = $('input:text[name=jml]').val();
  var jml_tindakan = $('input:text[name=jml_tindakan]').val();
  // console.log(jml_tindakan);
  var url = baseURL + '/kasir_rawat_jalan/savedetail?t=' + mlite.token;
  $.post(url, {
    no_rawat: no_rawat,
    kd_jenis_prw: kd_jenis_prw,
    nm_perawatan: nm_perawatan,
    provider: provider,
    kode_provider: kode_provider,
    kode_provider2: kode_provider2,
    tgl_perawatan: tgl_perawatan,
    jam_rawat: jam_rawat,
    biaya: biaya,
    aturan_pakai: aturan_pakai,
    kat: kat,
    jml: jml,
    jml_tindakan: jml_tindakan
  }, function (data) {
    console.log(data);
    // tampilkan data
    $("#display").hide();
    var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
    $.post(url, {
      no_rawat: no_rawat,
    }, function (data) {
      // tampilkan data
      $("#rincian").html(data).show();
    });
    $("#rawat_jl_dr").hide();
    $("#rawat_jl_pr").hide();
    $('#biaya').attr("readonly", true);
    $('#nm_perawatan').attr("readonly", true);
    $('select[name=provider]').val("");
    if ($('select[name=provider]').hasClass('selectpicker')) {
        $('select[name=provider]').selectpicker('refresh');
    }
    $('input:hidden[name=kd_jenis_prw]').val("");
    $('input:text[name=nm_perawatan]').val("");
    $('input:hidden[name=kat]').val("");
    $('input:text[name=biaya]').val("");
    $('input:text[name=nama_provider]').val("");
    $('input:text[name=nama_provider2]').val("");
    $('input:text[name=kode_provider]').val("");
    $('input:text[name=kode_provider2]').val("");
    $('input:text[name=jml_tindakan]').val(1);
    $('input:text[name=layanan]').val("");
    $('input:text[name=obat]').val("");
    $("#form_rincian").slideUp(250);
    $("#btn_toggle_form_rincian").html('<i class="fa fa-plus-circle"></i> Input Layanan Kasir').removeClass('btn-warning').addClass('btn-primary');
    $('#notif').html("<div class=\"alert alert-success alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
      "Data pasien telah disimpan!" +
      "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
      "</div>").show();
  });
});

// ketika tombol hapus ditekan
$("#rincian").on("click", ".hapus_detail", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/hapusdetail?t=' + mlite.token;
  var no_rawat = $(this).attr("data-no_rawat");
  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var tgl_perawatan = $(this).attr("data-tgl_perawatan");
  var jam_rawat = $(this).attr("data-jam_rawat");
  var provider = $(this).attr("data-provider");

  // tampilkan dialog konfirmasi
  bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function (result) {
    // ketika ditekan tombol ok
    if (result) {
      // mengirimkan perintah penghapusan
      $.post(url, {
        no_rawat: no_rawat,
        kd_jenis_prw: kd_jenis_prw,
        tgl_perawatan: tgl_perawatan,
        jam_rawat: jam_rawat,
        provider: provider
      }, function (data) {
        var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
        $.post(url, {
          no_rawat: no_rawat,
        }, function (data) {
          // tampilkan data
          $("#rincian").html(data).show();
        });
        $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Data rincian rawat jalan telah dihapus!" +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
      });
    }
  });
});

// ketika tombol hapus ditekan
$("#rincian").on("click", ".hapus_obat", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/hapusobat?t=' + mlite.token;
  var kode_brng = $(this).attr("data-kode_brng");
  var no_resep = $(this).attr("data-no_resep");
  var no_rawat = $(this).attr("data-no_rawat");
  var tgl_peresepan = $(this).attr("data-tgl_peresepan");
  var jam_peresepan = $(this).attr("data-jam_peresepan");
  var jml = $(this).attr("data-jml");

  // tampilkan dialog konfirmasi
  bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function (result) {
    // ketika ditekan tombol ok
    if (result) {
      // mengirimkan perintah penghapusan
      $.post(url, {
        kode_brng: kode_brng,
        no_resep: no_resep,
        no_rawat: no_rawat,
        tgl_peresepan: tgl_peresepan,
        jam_peresepan: jam_peresepan,
        jml: jml
      }, function (data) {
        var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
        $.post(url, {
          no_rawat: no_rawat,
        }, function (data) {
          // tampilkan data
          $("#rincian").html(data).show();
        });
        $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Data rincian obat rawat jalan telah dihapus!" +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
      });
    }
  });
});

// ketika tombol hapus ditekan
$("#rincian").on("click", ".hapus_laboratorium", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/hapuslaboratorium?t=' + mlite.token;
  var no_rawat = $(this).attr("data-no_rawat");
  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var tgl_perawatan = $(this).attr("data-tgl_periksa");
  var jam_rawat = $(this).attr("data-jam_periksa");
  var provider = $(this).attr("data-provider");

  // tampilkan dialog konfirmasi
  bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function (result) {
    // ketika ditekan tombol ok
    if (result) {
      // mengirimkan perintah penghapusan
      $.post(url, {
        no_rawat: no_rawat,
        kd_jenis_prw: kd_jenis_prw,
        tgl_perawatan: tgl_perawatan,
        jam_rawat: jam_rawat,
        provider: provider
      }, function (data) {
        var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
        $.post(url, {
          no_rawat: no_rawat,
        }, function (data) {
          // tampilkan data
          $("#rincian").html(data).show();
        });
        $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Data rincian rawat jalan telah dihapus!" +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
      });
    }
  });
});

// ketika tombol hapus ditekan
$("#rincian").on("click", ".hapus_radiologi", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var url = baseURL + '/kasir_rawat_jalan/hapusradiologi?t=' + mlite.token;
  var no_rawat = $(this).attr("data-no_rawat");
  var kd_jenis_prw = $(this).attr("data-kd_jenis_prw");
  var tgl_perawatan = $(this).attr("data-tgl_periksa");
  var jam_rawat = $(this).attr("data-jam_periksa");
  var provider = $(this).attr("data-provider");

  // tampilkan dialog konfirmasi
  bootbox.confirm("Apakah Anda yakin ingin menghapus data ini?", function (result) {
    // ketika ditekan tombol ok
    if (result) {
      // mengirimkan perintah penghapusan
      $.post(url, {
        no_rawat: no_rawat,
        kd_jenis_prw: kd_jenis_prw,
        tgl_perawatan: tgl_perawatan,
        jam_rawat: jam_rawat,
        provider: provider
      }, function (data) {
        var url = baseURL + '/kasir_rawat_jalan/rincian?t=' + mlite.token;
        $.post(url, {
          no_rawat: no_rawat,
        }, function (data) {
          // tampilkan data
          $("#rincian").html(data).show();
        });
        $('#notif').html("<div class=\"alert alert-danger alert-dismissible fade in\" role=\"alert\" style=\"border-radius:0px;margin-top:-15px;\">" +
          "Data rincian rawat jalan telah dihapus!" +
          "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">&times;</button>" +
          "</div>").show();
      });
    }
  });
});

// Helper pembersih angka nominal
function cleanNominal(val) {
  if (!val) return 0;
  var num = String(val).replace(/[^0-9]/g, '');
  return num ? Number(num) : 0;
}

// Catatan: Logika interaksi Modal Pembayaran Multi-Select & Alokasi terintegrasi langsung di rincian.html.

// Cetak Nota dari Riwayat Sebelumnya
$(document).on("click", ".btn_cetak_riwayat", function (event) {
  event.preventDefault();
  var baseURL = mlite.url + '/' + mlite.admin;
  var kd_billing = $(this).attr("data-kd_billing");
  var no_rawat = $(this).attr("data-no_rawat");
  window.open(baseURL + '/kasir_rawat_jalan/faktur?show=kecil&no_rawat=' + encodeURIComponent(no_rawat) + '&kd_billing=' + encodeURIComponent(kd_billing) + '&t=' + mlite.token);
});

// Cetak Invoice Besar
$(document).on("click", "#cetak_billing", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rawat = $(this).attr("data-no_rawat") || $('input:text[name=no_rawat]').val();
  window.open(baseURL + '/kasir_rawat_jalan/faktur?show=besar&no_rawat=' + encodeURIComponent(no_rawat) + '&t=' + mlite.token);
});

// Cetak Nota Terakhir (Kecil)
$(document).on("click", "#cetak_billing_kecil", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  var no_rawat = $(this).attr("data-no_rawat") || $('input:text[name=no_rawat]').val();
  window.open(baseURL + '/kasir_rawat_jalan/faktur?show=kecil&no_rawat=' + encodeURIComponent(no_rawat) + '&t=' + mlite.token);
});

// Selesai Billing & Kembali ke Daftar Pasien
$(document).on("click", "#selesai_billing", function (event) {
  var baseURL = mlite.url + '/' + mlite.admin;
  event.preventDefault();
  $("#layanan").hide();
  $("#obat").hide();
  bersih();
  $("#rincian").hide();
  $("#form_rincian").hide();
  var url = baseURL + '/kasir_rawat_jalan/display?t=' + mlite.token;
  $.post(url, {}, function (data) {
    $("#display").html(data).show();
    $("#notif").hide();
  });
});

function bersih() {
  $('input:text[name=no_rawat]').val("");
  $('input:text[name=no_rkm_medis]').val("");
  $('input:text[name=nm_pasien]').val("");
  $('input:text[name=tgl_perawatan]').val("{?=date('Y-m-d')?}");
  $('input:text[name=tgl_registrasi]').val("{?=date('Y-m-d')?}");
  $('input:text[name=tgl_lahir]').val("");
  $('input:text[name=jenis_kelamin]').val("");
  $('input:text[name=alamat]').val("");
  $('input:text[name=telepon]').val("");
  $('input:text[name=pekerjaan]').val("");
  $('input:text[name=layanan]').val("");
  $('input:text[name=obat]').val("");
  $('input:text[name=nama_jenis]').val("");
  $('input:text[name=jumlah_jual]').attr("disabled", true);
  $('input:text[name=potongan]').attr("disabled", true);
  $('input:text[name=harga_jual]').val("");
  $('input:text[name=total]').val("");
  $('input:text[name=no_reg]').val("");
}

/* Fungsi formatRupiah */
function formatRupiah(angka, prefix) {
  if (!angka) return '';
  var number_string = angka.replace(/[^,\d]/g, '').toString(),
    split = number_string.split(','),
    sisa = split[0].length % 3,
    rupiah = split[0].substr(0, sisa),
    ribuan = split[0].substr(sisa).match(/\d{3}/gi);

  if (ribuan) {
    separator = sisa ? '.' : '';
    rupiah += separator + ribuan.join('.');
  }

  rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
  return prefix == undefined ? rupiah : (rupiah ? 'Rp.' + rupiah : '');
}

function terbilang(a) {
  var bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

  if (a < 12) {
    var kalimat = bilangan[a];
  } else if (a < 20) {
    var kalimat = bilangan[a - 10] + ' Belas';
  } else if (a < 100) {
    var utama = Math.floor(a / 10);
    var belakang = a % 10;
    var kalimat = bilangan[utama] + ' Puluh ' + bilangan[belakang];
  } else if (a < 200) {
    var kalimat = 'Seratus ' + terbilang(a - 100);
  } else if (a < 1000) {
    var utama = Math.floor(a / 100);
    var belakang = a % 100;
    var kalimat = bilangan[utama] + ' Ratus ' + terbilang(belakang);
  } else if (a < 2000) {
    var kalimat = 'Seribu ' + terbilang(a - 1000);
  } else if (a < 1000000) {
    var utama = Math.floor(a / 1000);
    var belakang = a % 1000;
    var kalimat = terbilang(utama) + ' Ribu ' + terbilang(belakang);
  } else if (a < 1000000000) {
    var utama = Math.floor(a / 1000000);
    var belakang = a % 1000000;
    var kalimat = terbilang(utama) + ' Juta ' + terbilang(belakang);
  } else if (a < 1000000000000) {
    var utama = Math.floor(a / 1000000000);
    var belakang = a % 1000000000;
    var kalimat = terbilang(utama) + ' Milyar ' + terbilang(belakang);
  } else if (a < 1000000000000000) {
    var utama = Math.floor(a / 1000000000000);
    var belakang = a % 1000000000000;
    var kalimat = terbilang(utama) + ' Triliun ' + terbilang(belakang);
  } else {
    var kalimat = '';
  }

  var pisah = kalimat.split(' ');
  var full = [];
  for (var i = 0; i < pisah.length; i++) {
    if (pisah[i] != "") { full.push(pisah[i]); }
  }
  return full.join(' ');
}

// Toggle Form Input Layanan Kasir
$(document).off('click', '#btn_toggle_form_rincian, .btn-trigger-form-rincian').on('click', '#btn_toggle_form_rincian, .btn-trigger-form-rincian', function (e) {
  e.preventDefault();
  var focusTarget = $(this).data('focus') || 'layanan';
  if ($("#form_rincian").is(":visible")) {
    $("#form_rincian").slideUp(250);
    $("#btn_toggle_form_rincian").html('<i class="fa fa-plus-circle"></i> Input Layanan Kasir').removeClass('btn-warning').addClass('btn-primary');
  } else {
    $("#form_rincian").slideDown(250, function () {
      $('html, body').animate({
        scrollTop: $("#form_rincian").offset().top - 20
      }, 300);
      if (focusTarget === 'obat') {
        $('input:text[name=obat]').focus();
      } else {
        $('input:text[name=layanan]').focus();
      }
    });
    $("#btn_toggle_form_rincian").html('<i class="fa fa-chevron-up"></i> Tutup Input Layanan').removeClass('btn-primary').addClass('btn-warning');
  }
});

// Tutup / Batal Form Input Layanan Kasir
$(document).off('click', '#btn_tutup_form_rincian, #btn_batal_form_rincian').on('click', '#btn_tutup_form_rincian, #btn_batal_form_rincian', function (e) {
  e.preventDefault();
  $("#form_rincian").slideUp(250);
  $("#btn_toggle_form_rincian").html('<i class="fa fa-plus-circle"></i> Input Layanan Kasir').removeClass('btn-warning').addClass('btn-primary');
});

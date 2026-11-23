/**
 * Shared Client Helper for SOAP & RME Revision & Soft-Delete (Void)
 * Handles Option B (15-minute typo grace period), field highlights, hover popover, & timeline modal.
 */
(function(window, $) {
  'use strict';
  if (!$) return;

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function parseJsonSafe(raw) {
    if (!raw) return null;
    if (typeof raw === 'object') return raw;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function getBaseAdminUrl() {
    if (typeof mlite !== 'undefined' && mlite.url && mlite.admin) {
      return mlite.url + '/' + mlite.admin;
    }
    return '';
  }

  function getToken() {
    return (typeof mlite !== 'undefined' && mlite.token) ? mlite.token : '';
  }

  function showNotif(message, type) {
    var cls = type === 'error' ? 'alert-danger' : (type === 'warning' ? 'alert-warning' : 'alert-success');
    var $notif = $('#notif');
    if ($notif.length) {
      $notif.html(
        '<div class="alert ' + cls + ' alert-dismissible fade in" role="alert" style="border-radius:0px;margin-top:-15px;">' +
        escapeHtml(message) +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>' +
        '</div>'
      ).show();
      try {
        $notif.find('.alert-dismissible').fadeTo(4000, 500).slideUp(500);
      } catch (e) {}
    }
  }

  // Ensure shared tooltip & modal exist once
  function ensureUiOverlays() {
    if (!$('#rsgmRevTooltip').length) {
      $('body').append('<div id="rsgmRevTooltip" class="rsgm-rev-tooltip" style="display:none;"></div>');
    }
    if (!$('#rsgmRevModal').length) {
      $('body').append(
        '<div class="modal fade" id="rsgmRevModal" tabindex="-1" role="dialog" aria-labelledby="rsgmRevModalLabel">' +
          '<div class="modal-dialog modal-lg" role="document">' +
            '<div class="modal-content rsgm-rev-modal-shell" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 45px rgba(15,23,42,.35);">' +
              '<div class="modal-header" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#fff;padding:15px 20px;border-bottom:1px solid #334155;">' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#ffffff !important;opacity:.9;text-shadow:none;font-size:24px;"><span aria-hidden="true">&times;</span></button>' +
                '<h4 class="modal-title" id="rsgmRevModalLabel" style="font-size:15.5px;font-weight:700;color:#ffffff !important;margin:0;display:flex;align-items:center;gap:8px;">' +
                  '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;background:rgba(251,191,36,.18);color:#fbbf24;"><i class="fa fa-history"></i></span>' +
                  '<span>Riwayat Perubahan &amp; Koreksi Catatan SOAP</span>' +
                  '<span style="font-size:10px;background:#334155;color:#fde68a;padding:2px 8px;border-radius:999px;font-weight:700;letter-spacing:.3px;">AUDIT REVISI</span>' +
                '</h4>' +
                '<div style="font-size:11.5px;color:#94a3b8;margin-top:4px;padding-left:36px;">Jejak audit versi sebelum &amp; sesudah koreksi beserta penanggung jawab PPA</div>' +
              '</div>' +
              '<div class="modal-body" id="rsgmRevModalBody" style="max-height:74vh;overflow-y:auto;background:#f1f5f9;padding:18px 20px;"></div>' +
              '<div class="modal-footer" style="padding:10px 20px;background:#fff;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-size:11px;color:#64748b;"><i class="fa fa-lock"></i> Log revisi bersifat permanen &amp; tidak dapat dihapus</span>' +
                '<button type="button" class="btn btn-default btn-sm" data-dismiss="modal" style="font-weight:600;border-radius:6px;">Tutup</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }
  }

  function showHoverTooltip($pin, info) {
    ensureUiOverlays();
    var $tip = $('#rsgmRevTooltip');
    var oldShort = String(info.old || '-');
    var newShort = String(info.new || '-');
    if (oldShort.length > 140) oldShort = oldShort.substring(0, 140) + '...';
    if (newShort.length > 140) newShort = newShort.substring(0, 140) + '...';

    var html =
      '<div class="rsgm-rev-tip-head">' +
        '<span><i class="fa fa-pencil"></i> Koreksi #' + escapeHtml(info.revision_no || 1) + ' &mdash; ' + escapeHtml(info.label || '') + '</span>' +
      '</div>' +
      '<div class="rsgm-rev-tip-meta">' +
        escapeHtml(info.actor_name || '-') + ' &bull; ' + escapeHtml(info.created_at || '') +
      '</div>' +
      '<div class="rsgm-rev-tip-diff">' +
        '<div><span class="rsgm-rev-tag-old">Semula:</span> <span class="rsgm-rev-txt-old">' + escapeHtml(oldShort) + '</span></div>' +
        '<div style="margin-top:3px;"><span class="rsgm-rev-tag-new">Menjadi:</span> <span class="rsgm-rev-txt-new">' + escapeHtml(newShort) + '</span></div>' +
      '</div>' +
      '<div class="rsgm-rev-tip-reason"><strong>Alasan:</strong> ' + escapeHtml(info.reason || '-') + '</div>' +
      '<div class="rsgm-rev-tip-foot"><i class="fa fa-mouse-pointer"></i> Klik untuk melihat timeline lengkap</div>';

    $tip.html(html).show();

    var rect = $pin[0].getBoundingClientRect();
    var tipW = $tip.outerWidth();
    var tipH = $tip.outerHeight();
    var top = rect.top - tipH - 8;
    if (top < 8) {
      top = rect.bottom + 8;
    }
    var left = rect.left + (rect.width / 2) - (tipW / 2);
    if (left < 10) left = 10;
    if (left + tipW > window.innerWidth - 10) {
      left = window.innerWidth - tipW - 10;
    }
    $tip.css({ top: top + 'px', left: left + 'px' });
  }

  function hideHoverTooltip() {
    $('#rsgmRevTooltip').hide();
  }

  function openHistoryModal(moduleName, tableName, recordKey, focusField) {
    ensureUiOverlays();
    hideHoverTooltip();
    var baseURL = getBaseAdminUrl();
    var url = baseURL + '/' + moduleName + '/soaphistory?t=' + getToken();

    $('#rsgmRevModalBody').html(
      '<div class="text-center" style="padding:30px 10px;color:#64748b;">' +
      '<i class="fa fa-spinner fa-spin fa-2x"></i><br><div style="margin-top:8px;">Memuat riwayat koreksi...</div>' +
      '</div>'
    );
    $('#rsgmRevModal').modal('show');

    $.getJSON(url, { table_name: tableName, record_key: recordKey })
      .done(function(resp) {
        if (!resp || resp.status !== 'success') {
          $('#rsgmRevModalBody').html('<div class="alert alert-danger">' + escapeHtml((resp && resp.message) || 'Gagal memuat riwayat.') + '</div>');
          return;
        }
        var timeline = resp.timeline || [];
        var keyParts = String(recordKey || '').split('|');
        var revTotal = 0;
        var isVoidedDoc = false;
        for (var c = 0; c < timeline.length; c++) {
          if (timeline[c].action_type === 'AMEND') revTotal++;
          if (timeline[c].action_type === 'VOID') isVoidedDoc = true;
        }

        var noRawatDisplay = (keyParts.length >= 3 && keyParts[0]) ? keyParts[0] : (resp.no_rawat || recordKey || '-');
        var waktuDisplay = (keyParts.length >= 3)
          ? $.trim((keyParts[1] || '') + ' ' + (keyParts[2] || ''))
          : ((timeline[0] && timeline[0].created_at) ? timeline[0].created_at : ('#' + recordKey));

        var html = '<div class="rsgm-rev-summary-bar">' +
          '<div class="rsgm-rev-sum-item"><span class="rsgm-rev-sum-lbl">No. Rawat</span><strong>' + escapeHtml(noRawatDisplay) + '</strong></div>' +
          '<div class="rsgm-rev-sum-item"><span class="rsgm-rev-sum-lbl">Waktu Entri</span><strong>' + escapeHtml(waktuDisplay) + '</strong></div>' +
          '<div class="rsgm-rev-sum-item"><span class="rsgm-rev-sum-lbl">Total Koreksi</span><strong>' + revTotal + ' kali</strong></div>' +
          '<div class="rsgm-rev-sum-item"><span class="rsgm-rev-sum-lbl">Status</span>' +
            (isVoidedDoc ? '<span class="rsgm-rev-stat-pill is-void"><i class="fa fa-ban"></i> Dibatalkan (VOID)</span>' : '<span class="rsgm-rev-stat-pill is-active"><i class="fa fa-check-circle"></i> Aktif</span>') +
          '</div>' +
        '</div>';

        html += '<div class="rsgm-rev-timeline">';
        for (var i = timeline.length - 1; i >= 0; i--) {
          var item = timeline[i];
          var act = String(item.action_type || 'AMEND').toLowerCase();
          var nodeIcon = act === 'void' ? 'fa-ban' : (act === 'create' ? 'fa-plus' : 'fa-pencil');

          html += '<div class="rsgm-rev-tl-row action-' + escapeHtml(act) + '">';
          html += '  <div class="rsgm-rev-tl-node"><i class="fa ' + nodeIcon + '"></i></div>';
          html += '  <div class="rsgm-rev-tl-card action-' + escapeHtml(act) + '">';
          html += '    <div class="rsgm-rev-tl-head">';
          html += '      <div class="rsgm-rev-tl-actor">';
          html += '        <span class="rsgm-rev-ver-pill action-' + escapeHtml(act) + '">' + escapeHtml(item.version) + '</span>';
          html += '        <strong>' + escapeHtml(item.actor_name) + '</strong>';
          html += '        <span class="rsgm-rev-role-chip">' + escapeHtml(item.actor_role) + '</span>';
          if (item.tte_superseded) {
            html += '        <span class="rsgm-rev-tte-chip" title="Dokumen sempat memiliki TTE sebelum revisi ini"><i class="fa fa-certificate"></i> Pasca-TTE</span>';
          }
          html += '      </div>';
          html += '      <div class="rsgm-rev-tl-time"><i class="fa fa-clock-o"></i> ' + escapeHtml(item.created_at) + '</div>';
          html += '    </div>';
          html += '    <div class="rsgm-rev-tl-reason"><i class="fa fa-quote-left" style="opacity:.45;margin-right:5px;"></i><strong>Alasan:</strong> ' + escapeHtml(item.reason) + '</div>';

          if (item.changes && item.changes.length) {
            html += '    <div class="rsgm-rev-diff-list">';
            for (var j = 0; j < item.changes.length; j++) {
              var ch = item.changes[j];
              var isFocus = (focusField && ch.field === focusField);
              html += '      <div class="rsgm-rev-diff-item' + (isFocus ? ' is-focused' : '') + '">';
              html += '        <div class="rsgm-rev-diff-lbl">' + escapeHtml(ch.label) + (isFocus ? ' <span class="rsgm-rev-focus-tag">Field Dipilih</span>' : '') + '</div>';
              html += '        <div class="rsgm-rev-diff-cols">';
              html += '          <div class="rsgm-rev-diff-col is-old"><span class="rsgm-rev-col-tag">&minus; Semula</span><div class="rsgm-rev-col-val">' + escapeHtml(ch.old) + '</div></div>';
              html += '          <div class="rsgm-rev-diff-col is-new"><span class="rsgm-rev-col-tag">+ Menjadi</span><div class="rsgm-rev-col-val">' + escapeHtml(ch.new) + '</div></div>';
              html += '        </div>';
              html += '      </div>';
            }
            html += '    </div>';
          } else if (item.snapshot_summary) {
            var s = item.snapshot_summary;
            html += '    <div class="rsgm-rev-v1-grid">' +
              '<div class="rsgm-rev-v1-cell"><span class="rsgm-soap-tag rsgm-soap-tag-s">S</span><div><strong>Subyektif</strong><div>' + escapeHtml(s.S || '-') + '</div></div></div>' +
              '<div class="rsgm-rev-v1-cell"><span class="rsgm-soap-tag rsgm-soap-tag-o">O</span><div><strong>Obyektif</strong><div>' + escapeHtml(s.O || '-') + '</div></div></div>' +
              '<div class="rsgm-rev-v1-cell"><span class="rsgm-soap-tag rsgm-soap-tag-a">A</span><div><strong>Assesment</strong><div>' + escapeHtml(s.A || '-') + '</div></div></div>' +
              '<div class="rsgm-rev-v1-cell"><span class="rsgm-soap-tag rsgm-soap-tag-p">P</span><div><strong>Plan</strong><div>' + escapeHtml(s.P || '-') + '</div></div></div>' +
              '</div>';
          }
          html += '  </div>';
          html += '</div>';
        }
        html += '</div>';
        $('#rsgmRevModalBody').html(html);
      })
      .fail(function() {
        $('#rsgmRevModalBody').html('<div class="alert alert-danger">Gagal menghubungi server untuk memuat riwayat koreksi.</div>');
      });
  }

  function promptReasonModal(opts, onSubmit) {
    var chips = opts.chips || [
      'Salah ketik / typo',
      'Klarifikasi anamnesis dari pasien',
      'Pembaruan hasil pemeriksaan klinis',
      'Penyesuaian rencana / instruksi terapi'
    ];
    var diffHtml = '';
    if (opts.changedFields && Object.keys(opts.changedFields).length) {
      diffHtml += '<div style="max-height:140px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;background:#fff;">' +
        '<table class="table table-condensed" style="margin:0;font-size:11.5px;">' +
        '<thead><tr style="background:#f8fafc;"><th>Field Berubah</th><th>Semula</th><th>Menjadi</th></tr></thead><tbody>';
      $.each(opts.changedFields, function(_, cf) {
        diffHtml += '<tr>' +
          '<td><strong>' + escapeHtml(cf.label) + '</strong></td>' +
          '<td class="text-danger">' + escapeHtml(cf.old) + '</td>' +
          '<td class="text-success">' + escapeHtml(cf.new) + '</td>' +
          '</tr>';
      });
      diffHtml += '</tbody></table></div>';
    }

    var chipHtml = '';
    $.each(chips, function(_, text) {
      chipHtml += '<button type="button" class="btn btn-default btn-xs rsgm-reason-chip" style="margin:0 4px 5px 0;border-radius:12px;">' + escapeHtml(text) + '</button>';
    });

    var msgHtml =
      '<div class="alert ' + (opts.required ? 'alert-warning' : 'alert-info') + '" style="padding:9px 12px;font-size:12px;margin-bottom:10px;">' +
        '<i class="fa fa-shield"></i> ' + escapeHtml(opts.infoText || '') +
      '</div>' +
      diffHtml +
      '<div style="margin-bottom:6px;font-size:12px;font-weight:600;">' +
        escapeHtml(opts.label || 'Alasan Koreksi') + (opts.required ? ' <span class="text-danger">*</span>' : ' <small class="text-muted">(Opsional)</small>') +
      '</div>' +
      '<div style="margin-bottom:6px;">' + chipHtml + '</div>' +
      '<textarea id="rsgm_modal_reason_input" class="form-control" rows="2" placeholder="' + escapeHtml(opts.placeholder || 'Tuliskan alasan perubahan/pembatalan catatan...') + '"></textarea>' +
      '<div id="rsgm_modal_reason_err" class="text-danger" style="font-size:11.5px;margin-top:4px;display:none;"></div>';

    var dialog = bootbox.dialog({
      title: opts.title || 'Konfirmasi Koreksi Rekam Medis',
      message: msgHtml,
      buttons: {
        cancel: {
          label: 'Batal',
          className: 'btn-default'
        },
        confirm: {
          label: opts.confirmLabel || 'Simpan dengan Alasan',
          className: opts.confirmClass || 'btn-primary',
          callback: function() {
            var reason = $.trim($('#rsgm_modal_reason_input').val());
            if (opts.required && reason.length < 3) {
              $('#rsgm_modal_reason_err').text('Alasan wajib diisi minimal 3 karakter untuk jejak audit.').show();
              $('#rsgm_modal_reason_input').focus();
              return false;
            }
            onSubmit(reason);
            return true;
          }
        }
      }
    });

    dialog.on('shown.bs.modal', function() {
      dialog.find('.rsgm-reason-chip').off('click').on('click', function() {
        $('#rsgm_modal_reason_input').val($(this).text()).focus();
        $('#rsgm_modal_reason_err').hide();
      });
    });
  }

  function clearFormHighlight($form) {
    $form.find('.rsgm-rev-input-highlight').removeClass('rsgm-rev-input-highlight');
    $form.find('.rsgm-form-rev-pin').remove();
  }

  function applyFormHighlight($form, changedMap, moduleName, tableName, recordKey) {
    clearFormHighlight($form);
    if (!changedMap || typeof changedMap !== 'object') return;
    $.each(changedMap, function(field, info) {
      var $el = $form.find('[name="' + field + '"]').first();
      if (!$el.length) return;
      $el.addClass('rsgm-rev-input-highlight');
      var $grp = $el.closest('.form-group');
      var $lbl = $grp.find('label').first();
      if ($lbl.length && !$lbl.find('.rsgm-form-rev-pin').length) {
        var $pin = $('<span class="rsgm-rev-pin rsgm-form-rev-pin js-soap-rev-open"><i class="fa fa-history"></i> Dikoreksi</span>');
        $pin.data('revInfo', info);
        $pin.attr('data-module', moduleName);
        $pin.attr('data-table', tableName);
        $pin.attr('data-key', recordKey);
        $pin.attr('data-field', field);
        $lbl.append(' ').append($pin);
      }
    });
  }

  var RSGMSoapRevision = {
    enterEdit: function($btn, moduleName) {
      var $form = $('#form_soap');
      if (!$form.length) return;

      var noRawat = $btn.attr('data-no_rawat') || $('input[name=no_rawat]').val() || '';
      var tgl = $btn.attr('data-tgl_perawatan') || '';
      var jam = $btn.attr('data-jam_rawat') || '';
      var tableName = $btn.attr('data-table_name') || 'pemeriksaan_ralan';
      var recordKey = $btn.attr('data-record_key') || (noRawat + '|' + tgl + '|' + jam);
      var inGrace = $btn.attr('data-in_grace') === '1';
      var needsReason = $btn.attr('data-needs_reason') === '1';
      var minutesLeft = parseInt($btn.attr('data-minutes_left') || '0', 10);
      var graceWhy = $btn.attr('data-grace_why') || '';
      var changedMap = parseJsonSafe($btn.attr('data-changed_fields')) || {};

      $form.data('rsgmSoapEdit', {
        isEdit: true,
        moduleName: moduleName,
        tableName: tableName,
        recordKey: recordKey,
        noRawat: noRawat,
        origTgl: tgl,
        origJam: jam,
        inGrace: inGrace,
        needsReason: needsReason,
        minutesLeft: minutesLeft,
        graceWhy: graceWhy
      });

      // Lock date/time keys while amending existing SOAP note
      $form.find('#soap_tgl_perawatan, input[name=tgl_perawatan]').prop('readonly', true);
      $form.find('#soap_jam_rawat, input[name=jam_rawat]').prop('readonly', true);

      // Update save button label
      var $saveBtn = $('#simpan_soap');
      if ($saveBtn.length) {
        if (!$saveBtn.data('origHtml')) {
          $saveBtn.data('origHtml', $saveBtn.html());
        }
        $saveBtn.html('<i class="fa fa-pencil"></i><span class="hidden-xs"> ' + (needsReason ? 'Simpan Koreksi' : 'Simpan Perubahan') + '</span>');
      }

      // Render Mode Edit Banner
      $('#rsgm_soap_edit_banner').remove();
      var bannerCls = needsReason ? 'alert-warning' : 'alert-info';
      var bannerMsg = needsReason
        ? '<strong>Mode Koreksi Catatan SOAP:</strong> ' + escapeHtml(graceWhy || 'Perubahan wajib menyertakan alasan koreksi dan akan tercatat dalam riwayat versi.')
        : '<strong>Mode Edit Cepat (Masa Tenggang Opsi B):</strong> Anda masih berada dalam jendela koreksi langsung 15 menit (tersisa ~' + (minutesLeft || 1) + ' menit).';

      var $banner = $(
        '<div id="rsgm_soap_edit_banner" class="alert ' + bannerCls + '" style="margin:0 0 12px 0;padding:10px 14px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">' +
          '<div style="font-size:12.5px;"><i class="fa fa-edit"></i> ' + bannerMsg + '</div>' +
          '<div style="display:flex;gap:6px;">' +
            (Object.keys(changedMap).length ? '<button type="button" class="btn btn-default btn-xs js-soap-rev-open" data-module="' + escapeHtml(moduleName) + '" data-table="' + escapeHtml(tableName) + '" data-key="' + escapeHtml(recordKey) + '"><i class="fa fa-history"></i> Lihat Riwayat</button>' : '') +
            '<button type="button" class="btn btn-default btn-xs" id="rsgm_cancel_edit_soap"><i class="fa fa-times"></i> Batal Edit (Entri Baru)</button>' +
          '</div>' +
        '</div>'
      );

      var $targetHost = $form.find('.rsgm-reg-body').eq(1);
      if (!$targetHost.length) {
        $targetHost = $form;
      }
      $targetHost.prepend($banner);

      applyFormHighlight($form, changedMap, moduleName, tableName, recordKey);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    resetEdit: function(clearValues) {
      var $form = $('#form_soap');
      if (!$form.length) return;
      $form.removeData('rsgmSoapEdit');
      $form.find('#soap_tgl_perawatan, input[name=tgl_perawatan]').prop('readonly', false);
      $form.find('#soap_jam_rawat, input[name=jam_rawat]').prop('readonly', false);
      $('#rsgm_soap_edit_banner').remove();
      clearFormHighlight($form);

      var $saveBtn = $('#simpan_soap');
      if ($saveBtn.length && $saveBtn.data('origHtml')) {
        $saveBtn.html($saveBtn.data('origHtml'));
      }

      if (clearValues) {
        $form.find('input:text[name=suhu_tubuh], input:text[name=tensi], input:text[name=nadi], input:text[name=respirasi], input:text[name=tinggi], input:text[name=berat], input:text[name=gcs], input:text[name=alergi], input:text[name=lingkar_perut], input:text[name=spo2]').val('');
        $form.find('textarea[name=keluhan], textarea[name=pemeriksaan], textarea[name=penilaian], textarea[name=rtl], textarea[name=instruksi], textarea[name=evaluasi]').val('');
        var dt = new Date();
        var tgl = dt.getFullYear() + '-' + ('0' + (dt.getMonth() + 1)).slice(-2) + '-' + ('0' + dt.getDate()).slice(-2);
        var jam = ('0' + dt.getHours()).slice(-2) + ':' + ('0' + dt.getMinutes()).slice(-2) + ':' + ('0' + dt.getSeconds()).slice(-2);
        $form.find('#soap_tgl_perawatan, input[name=tgl_perawatan]').val(tgl);
        $form.find('#soap_jam_rawat, input[name=jam_rawat]').val(jam);
      }
    },

    saveSoap: function(moduleName, saveUrl, payload, onComplete) {
      var self = this;
      var $form = $('#form_soap');
      var editState = $form.data('rsgmSoapEdit') || null;
      var postData = $.extend({}, payload);

      if (editState && editState.isEdit) {
        postData.is_edit = '1';
        postData.original_tgl_perawatan = editState.origTgl;
        postData.original_jam_rawat = editState.origJam;
      }

      function doPost(dataToSend) {
        $.post(saveUrl, dataToSend, function(rawResp) {
          var resp = parseJsonSafe(rawResp);
          if (!resp) {
            // Backward compatibility if anything echoes empty body
            self.resetEdit(true);
            if (typeof onComplete === 'function') onComplete(resp);
            showNotif('Data SOAP telah disimpan!', 'success');
            return;
          }

          if (resp.status === 'needs_reason') {
            promptReasonModal({
              title: 'Alasan Koreksi Catatan SOAP',
              infoText: resp.message || 'Perubahan catatan ini wajib disertai alasan koreksi.',
              changedFields: resp.changed_fields || {},
              required: true,
              label: 'Alasan Koreksi Catatan',
              confirmLabel: 'Simpan Koreksi',
              confirmClass: 'btn-warning'
            }, function(reason) {
              dataToSend.revision_reason = reason;
              doPost(dataToSend);
            });
            return;
          }

          if (resp.status === 'error') {
            bootbox.alert({
              title: 'Gagal Menyimpan SOAP',
              message: escapeHtml(resp.message || 'Terjadi kesalahan saat menyimpan SOAP.')
            });
            return;
          }

          self.resetEdit(true);
          if (typeof onComplete === 'function') onComplete(resp);
          showNotif(resp.message || 'Data SOAP telah disimpan!', 'success');
        });
      }

      doPost(postData);
    },

    voidSoap: function($btn, moduleName, hapusUrl, onComplete) {
      var noRawat = $btn.attr('data-no_rawat');
      var tglPerawatan = $btn.attr('data-tgl_perawatan');
      var jamRawat = $btn.attr('data-jam_rawat');
      var needsReason = $btn.attr('data-needs_reason') === '1';
      var graceWhy = $btn.attr('data-grace_why') || '';
      var minutesLeft = parseInt($btn.attr('data-minutes_left') || '0', 10);

      function sendVoid(reason) {
        $.post(hapusUrl, {
          no_rawat: noRawat,
          tgl_perawatan: tglPerawatan,
          jam_rawat: jamRawat,
          void_reason: reason || ''
        }, function(rawResp) {
          var resp = parseJsonSafe(rawResp);
          if (resp && resp.status === 'needs_reason') {
            promptReasonModal({
              title: 'Batalkan Catatan SOAP (VOID)',
              infoText: resp.message || 'Catatan tidak dihapus permanen, melainkan ditandai VOID dengan alasan.',
              required: true,
              label: 'Alasan Pembatalan Catatan',
              chips: ['Salah pilih pasien / kunjungan', 'Duplikasi entri SOAP', 'Kesalahan pencatatan klinis', 'Salah tanggal/jam entri'],
              confirmLabel: 'Batalkan Catatan (VOID)',
              confirmClass: 'btn-danger'
            }, function(r) {
              sendVoid(r);
            });
            return;
          }
          if (resp && resp.status === 'error') {
            bootbox.alert({ title: 'Gagal Membatalkan', message: escapeHtml(resp.message) });
            return;
          }
          if (typeof onComplete === 'function') onComplete(resp);
          showNotif((resp && resp.message) || 'Catatan SOAP dibatalkan (VOID).', 'success');
        });
      }

      promptReasonModal({
        title: 'Konfirmasi Pembatalan Catatan SOAP (VOID)',
        infoText: needsReason
          ? (graceWhy + '. Data tidak dihapus permanen dan tetap tersimpan dengan status VOID.')
          : ('Catatan masih dalam masa koreksi langsung (tersisa ~' + (minutesLeft || 1) + ' menit). Pembatalan tetap tercatat sebagai VOID untuk audit rekam medis.'),
        required: needsReason,
        label: 'Alasan Pembatalan',
        chips: ['Salah pilih pasien / kunjungan', 'Duplikasi entri SOAP', 'Salah input data klinis', 'Akan dibuat ulang oleh PPA terkait'],
        placeholder: needsReason ? 'Wajib diisi alasan pembatalan catatan...' : 'Opsional: tuliskan alasan singkat pembatalan...',
        confirmLabel: 'Ya, Batalkan Catatan (VOID)',
        confirmClass: 'btn-danger'
      }, function(reason) {
        sendVoid(reason);
      });
    },

    decorateContainer: function($root, moduleName) {
      if (!$root || !$root.length) return;
      ensureUiOverlays();

      var resolvedModule = moduleName || $root.find('[data-soap-module]').first().attr('data-soap-module') || '';

      // Map card/row fields for highlights + hover pins
      $root.find('[data-changed-map]').each(function() {
        var $item = $(this);
        var map = parseJsonSafe($item.attr('data-changed-map'));
        if (!map || !Object.keys(map).length) return;

        var tableName = $item.attr('data-table-name') || 'pemeriksaan_ralan';
        var recordKey = $item.attr('data-record-key') || '';
        var itemModule = $item.attr('data-soap-module') || resolvedModule;

        $item.find('[data-rev-field]').each(function() {
          var $target = $(this);
          var fieldList = String($target.attr('data-rev-field') || '').split(',');
          var matchedInfo = null;
          var matchedField = '';
          for (var k = 0; k < fieldList.length; k++) {
            var fn = $.trim(fieldList[k]);
            if (map[fn]) {
              matchedInfo = map[fn];
              matchedField = fn;
              break;
            }
          }
          if (!matchedInfo) return;

          $target.addClass('rsgm-rev-highlight');
          if (!$target.find('.rsgm-rev-pin').length) {
            var isVitem = $target.hasClass('rsgm-cppt-vitem');
            var revNumTxt = matchedInfo.revision_no ? ('#' + matchedInfo.revision_no) : '';
            var pinText = 'Koreksi' + (revNumTxt ? ' ' + revNumTxt : '');
            var $pin = $('<span class="rsgm-rev-pin js-soap-rev-open" title="Arahkan untuk pratinjau Semula \u2192 Menjadi / klik untuk riwayat"><i class="fa fa-history"></i> ' + pinText + '</span>');
            $pin.data('revInfo', matchedInfo);
            $pin.attr('data-module', itemModule);
            $pin.attr('data-table', tableName);
            $pin.attr('data-key', recordKey);
            $pin.attr('data-field', matchedField);

            var $cellLbl = $target.find('.rsgm-cppt-cell-lbl').first();
            if ($cellLbl.length) {
              $cellLbl.append($pin);
            } else if (isVitem) {
              $target.append($pin);
            } else {
              $target.append(' ').append($pin);
            }
          }
        });
      });

      // Handle toggle for voided entries
      $root.off('click.rsgmToggleVoid').on('click.rsgmToggleVoid', '.js-toggle-voided-soap', function() {
        var $btn = $(this);
        var showing = $btn.attr('data-showing') === '1';
        var $voidItems = $root.find('.js-soap-voided-item');
        if (showing) {
          $voidItems.hide();
          $btn.attr('data-showing', '0').removeClass('active');
          $btn.find('.js-void-btn-lbl').text('Tampilkan Dibatalkan');
        } else {
          $voidItems.show();
          $btn.attr('data-showing', '1').addClass('active');
          $btn.find('.js-void-btn-lbl').text('Sembunyikan Dibatalkan');
        }
      });
    },

    promptReason: function(opts, onSubmit) {
      promptReasonModal(opts || {}, onSubmit || function() {});
    },

    openHistory: function(moduleName, tableName, recordKey, focusField) {
      openHistoryModal(moduleName || 'form_rme', tableName, String(recordKey || ''), focusField || '');
    },

    decorateRmeForm: function(meta) {
      if (!meta || !meta.table_name || !meta.id) return;
      var $form = $('form').filter(function() {
        return !$(this).closest('.modal, #frm-rj03-delete, #frm-rj04-delete').length && ($(this).find('input, textarea, select').length >= 3 || $(this).attr('method'));
      }).first();
      if (!$form.length) return;

      var map = meta.changed_fields_map || {};
      var recKey = String(meta.id);
      var tbl = String(meta.table_name);

      // Highlight fields that have been formally amended
      $.each(map, function(field, info) {
        var $targets = $form.find('[data-rev-field="' + field + '"]');
        if (!$targets.length) {
          $targets = $form.find('[name="' + field + '"], [name="' + field + '[]"], .f-' + field + ', #' + field).filter('input, textarea, select');
        }
        if (!$targets.length && field === 'asesmen') {
          $targets = $form.find('#tblAsesmen, .asesmen-row').first();
        }
        $targets.each(function() {
          var $el = $(this);
          if ($el.attr('type') === 'hidden') return;
          $el.addClass('rsgm-rev-input-highlight');
          var $grp = $el.closest('.form-group, td, div.col-md-12, div.col-md-6, div.col-md-4, div.col-md-3, .panel, .box');
          var $lbl = $grp.find('label, .control-label, th').first();
          if ($el.is('#tblAsesmen, .asesmen-row')) {
            $lbl = $el.closest('.rsgm-reg-section, .panel, div').find('h4, h5, label').first();
          }
          if ($lbl.length && !$lbl.find('.rsgm-form-rev-pin[data-field="' + field + '"]').length) {
            var revNumTxt = info.revision_no ? ('#' + info.revision_no) : '';
            var $pin = $('<span class="rsgm-rev-pin rsgm-form-rev-pin js-soap-rev-open" title="Arahkan untuk pratinjau Semula \u2192 Menjadi / klik untuk riwayat"><i class="fa fa-history"></i> Koreksi' + (revNumTxt ? ' ' + revNumTxt : '') + '</span>');
            $pin.data('revInfo', info);
            $pin.attr('data-module', 'form_rme');
            $pin.attr('data-table', tbl);
            $pin.attr('data-key', recKey);
            $pin.attr('data-field', field);
            $lbl.append(' ').append($pin);
          }
        });
      });

      // Disable all controls cleanly if record is VOID
      if (meta.is_voided) {
        $form.find('input, textarea, select, button[type=submit]').not('.js-soap-rev-open, [type=hidden]').prop('disabled', true);
        $('#btn-rme-quick-save, #btn-rme-done, #rme-toggle-autosave').prop('disabled', true).addClass('disabled');
      }

      // Guard form submission (normal POST) when correction requires reason
      $form.off('submit.rsgmRmeRev').on('submit.rsgmRmeRev', function(e) {
        if ($form.data('rsgmAllowSubmitOnce')) {
          $form.removeData('rsgmAllowSubmitOnce');
          return true;
        }
        if (meta.is_voided) {
          e.preventDefault();
          return false;
        }
        if (!meta.needs_reason) return true;
        var existingReason = $.trim($form.find('input[name="revision_reason"]').val() || '');
        if (existingReason.length >= 3) return true;

        e.preventDefault();
        promptReasonModal({
          title: 'Alasan Koreksi Formulir RME',
          infoText: meta.why || 'Perubahan formulir yang sudah lewat masa tenggang 15 menit / ber-TTE wajib disertai alasan koreksi.',
          required: true,
          label: 'Alasan Koreksi Formulir',
          confirmLabel: 'Simpan Koreksi',
          confirmClass: 'btn-warning'
        }, function(reason) {
          var $ri = $form.find('input[name="revision_reason"]');
          if (!$ri.length) {
            $ri = $('<input type="hidden" name="revision_reason">').appendTo($form);
          }
          $ri.val(reason);
          $form.triggerHandler('rme:beforeSave');
          $form.data('rsgmAllowSubmitOnce', true);
          $form[0].submit();
        });
        return false;
      });
    },

    decorateRmeManage: function(metaPayload) {
      if (!metaPayload || !metaPayload.table_name || !metaPayload.rows) return;
      var tbl = String(metaPayload.table_name);
      var rowsById = metaPayload.rows || {};
      var $table = $('table').filter(function() {
        return $(this).find('tbody tr').length > 0 && $(this).find('a[href*="cetak"], a[href*="form"]').length > 0;
      }).first();
      if (!$table.length) return;

      $table.find('tbody tr').each(function() {
        var $tr = $(this);
        var rowId = '';
        var $delTrigger = $tr.find('a[href*="hapus"], a[href*="delete"], button[data-id]').first();
        if ($delTrigger.length) {
          rowId = String($delTrigger.attr('data-id') || '');
          if (!rowId) {
            var href = $delTrigger.attr('href') || '';
            var m = href.match(/[?&]id=(\d+)|\/(\d+)(?:\?|$)/);
            if (m) rowId = m[1] || m[2] || '';
          }
        }
        if (!rowId) {
          var $editLink = $tr.find('a[href*="form"]').first();
          if ($editLink.length) {
            var m2 = ($editLink.attr('href') || '').match(/[?&]id=(\d+)|\/(\d+)(?:\?|$)/);
            if (m2) rowId = m2[1] || m2[2] || '';
          }
        }
        if (!rowId || !rowsById[rowId]) return;

        var info = rowsById[rowId];
        $tr.attr('data-record-key', rowId).attr('data-table-name', tbl);

        // Find display cell (No Rawat or Nama Pasien - second column usually, or cell with strong)
        var $badgeCell = $tr.find('td').eq(1);
        if (!$badgeCell.length) $badgeCell = $tr.find('td').first();

        if (!$badgeCell.find('.rsgm-rme-manage-badges').length) {
          var badgesHtml = '<div class="rsgm-rme-manage-badges" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;align-items:center;">';
          if (info.is_voided) {
            $tr.addClass('rsgm-void-row').css('opacity', '0.72').css('background', '#fef2f2');
            badgesHtml += '<span class="rsgm-soap-rev-badge js-soap-rev-open" style="background:#dc2626;color:#fff;border-color:#b91c1c;" data-module="form_rme" data-table="' + escapeHtml(tbl) + '" data-key="' + escapeHtml(rowId) + '" title="Klik untuk melihat detail pembatalan"><i class="fa fa-ban"></i> DIBATALKAN (VOID)</span>';
            if (info.void_info && info.void_info.void_reason) {
              badgesHtml += '<small style="color:#991b1b;font-size:10.5px;">Alasan: ' + escapeHtml(info.void_info.void_reason) + '</small>';
            }
          } else if (info.has_revisions) {
            badgesHtml += '<button type="button" class="rsgm-soap-rev-badge js-soap-rev-open" data-module="form_rme" data-table="' + escapeHtml(tbl) + '" data-key="' + escapeHtml(rowId) + '" title="Lihat riwayat koreksi"><i class="fa fa-history"></i> Dikoreksi ' + parseInt(info.revision_count, 10) + 'x</button>';
          }
          if (info.is_tte_outdated) {
            badgesHtml += '<span class="rsgm-soap-tte-warn-badge" title="Formulir dikoreksi setelah TTE dibubuhkan — perlu TTE ulang"><i class="fa fa-exclamation-triangle"></i> TTE Sebelum Koreksi</span>';
          }
          badgesHtml += '</div>';
          if (info.is_voided || info.has_revisions || info.is_tte_outdated) {
            $badgeCell.append(badgesHtml);
          }
        }

        // Upgrade delete link/button to capture void_reason
        $tr.find('a[href*="hapus"], a[href*="delete"], .btn-rj03-delete, .btn-rj04-delete').each(function() {
          var $del = $(this);
          if (info.is_voided) {
            $del.addClass('disabled').attr('disabled', true).css('pointer-events', 'none').attr('title', 'Sudah dibatalkan (VOID)');
            return;
          }
          $del.removeAttr('onclick').off('click.rsgmRmeVoid').on('click.rsgmRmeVoid', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var targetUrl = $del.attr('href') || metaPayload.hapus_url || '';
            if (!targetUrl && ($del.hasClass('btn-rj03-delete') || $del.hasClass('btn-rj04-delete'))) {
              targetUrl = $('#frm-rj03-delete').attr('action') || $('#frm-rj04-delete').attr('action') || '';
            }
            if (!targetUrl) return;

            promptReasonModal({
              title: 'Konfirmasi Pembatalan / VOID Formulir RME',
              infoText: info.needs_reason
                ? (info.why + '. Data tidak dihapus permanen, melainkan ditandai VOID dengan alasan.')
                : 'Formulir masih dalam masa tenggang 15 menit. Pembatalan akan tetap dicatat pada log audit (VOID).',
              required: !!info.needs_reason,
              label: 'Alasan Pembatalan / VOID',
              confirmLabel: 'Batalkan (VOID) Data',
              confirmClass: 'btn-danger',
              chips: ['Salah input pasien', 'Duplikasi pengisian formulir', 'Data tidak sesuai kunjungan', 'Batal tindakan']
            }, function(reason) {
              var $f = $('<form method="POST" style="display:none;"></form>').attr('action', targetUrl);
              $f.append($('<input type="hidden" name="id">').val(rowId));
              $f.append($('<input type="hidden" name="void_reason">').val(reason));
              var t = getToken();
              if (t && targetUrl.indexOf('t=') === -1) {
                $f.attr('action', targetUrl + (targetUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + encodeURIComponent(t));
              }
              $('body').append($f);
              $f[0].submit();
            });
            return false;
          });
        });
      });
    }
  };

  // Global delegated handlers for Cancel Edit, Hover Tooltip, and Timeline Modal
  $(document).off('click.rsgmCancelEdit').on('click.rsgmCancelEdit', '#rsgm_cancel_edit_soap', function(e) {
    e.preventDefault();
    RSGMSoapRevision.resetEdit(true);
  });

  $(document).off('mouseenter.rsgmRevTip').on('mouseenter.rsgmRevTip', '.rsgm-rev-pin', function() {
    var info = $(this).data('revInfo');
    if (info) {
      showHoverTooltip($(this), info);
    }
  });

  $(document).off('mouseleave.rsgmRevTip').on('mouseleave.rsgmRevTip', '.rsgm-rev-pin', function() {
    hideHoverTooltip();
  });

  $(document).off('click.rsgmRevOpen').on('click.rsgmRevOpen', '.js-soap-rev-open', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $el = $(this);
    var mod = $el.attr('data-module') || '';
    if (!mod) {
      var m = window.location.pathname.match(/\/admin\/([a-z0-9_]+)\//i) || window.location.pathname.match(/\/([a-z0-9_]+)\/manage/i);
      mod = m ? m[1] : 'rawat_jalan';
    }
    var tbl = $el.attr('data-table') || 'pemeriksaan_ralan';
    var key = $el.attr('data-key') || '';
    var fld = $el.attr('data-field') || '';
    if (key) {
      openHistoryModal(mod, tbl, key, fld);
    }
  });

  // Auto-init revision decorations when Rajal RME metadata scripts are present
  $(document).ready(function() {
    var $formMeta = $('#rsgm-rme-rev-form-meta');
    if ($formMeta.length) {
      var fMeta = parseJsonSafe($formMeta.text());
      if (fMeta) {
        RSGMSoapRevision.decorateRmeForm(fMeta);
      }
    }
    var $manageMeta = $('#rsgm-rme-rev-manage-meta');
    if ($manageMeta.length) {
      var mMeta = parseJsonSafe($manageMeta.text());
      if (mMeta) {
        setTimeout(function() { RSGMSoapRevision.decorateRmeManage(mMeta); }, 60);
        $(document).on('draw.dt', 'table', function() {
          RSGMSoapRevision.decorateRmeManage(mMeta);
        });
      }
    }
  });

  window.RSGMSoapRevision = RSGMSoapRevision;
})(window, window.jQuery);


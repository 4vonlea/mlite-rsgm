jQuery().ready(function () {
    function esc(str) {
        return String(str === null || str === undefined ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var var_tbl_mlite_query_logs = $('#tbl_mlite_query_logs').DataTable({
        'processing': true,
        'serverSide': true,
        'serverMethod': 'post',
        'order': [[0, 'desc']],
        'searching': false,
        'select': { style: 'single' },
        'colReorder': false,
        'scrollX': false,
        'autoWidth': false,
        'pageLength': 25,
        'lengthMenu': [[15, 25, 50, 100], [15, 25, 50, 100]],
        'language': {
            'processing': '<i class="fa fa-spinner fa-spin text-primary"></i> Memuat data...',
            'lengthMenu': 'Tampilkan _MENU_ baris',
            'zeroRecords': 'Tidak ada data log yang cocok',
            'info': 'Menampilkan _START_ - _END_ dari _TOTAL_ log',
            'infoEmpty': 'Belum ada data log',
            'infoFiltered': '(disaring dari _MAX_ total log)',
            'paginate': {
                'first': '&laquo;',
                'last': '&raquo;',
                'next': 'Berikutnya',
                'previous': 'Sebelumnya'
            }
        },
        'ajax': {
            'url': "{?=url([ADMIN,'mlite_logs','data'])?}",
            'dataType': 'json',
            'type': 'POST',
            'data': function (data) {
                data.search_field_mlite_query_logs = $('#search_field_mlite_query_logs').val();
                data.search_text_mlite_query_logs = $('#search_text_mlite_query_logs').val();
                if (typeof mlite !== 'undefined' && mlite.token) {
                    data.t = mlite.token;
                }
            },
            'dataSrc': function (json) {
                if (json && typeof json.iTotalRecords !== 'undefined') {
                    var totalTxt = Number(json.iTotalRecords).toLocaleString('id-ID') + ' total log';
                    if (json.iTotalDisplayRecords !== json.iTotalRecords) {
                        totalTxt = Number(json.iTotalDisplayRecords).toLocaleString('id-ID') + ' cocok / ' + totalTxt;
                    }
                    $('#logs_total_badge').text(totalTxt);
                }
                return json && json.aaData ? json.aaData : [];
            }
        },
        'columns': [
            {
                'data': 'id',
                'render': function (val) {
                    return '<span class="logs-id-chip">#' + esc(val) + '</span>';
                }
            },
            {
                'data': 'username',
                'render': function (val, type, row) {
                    var sub = row && row.raw_username && row.raw_username !== val
                        ? '<div class="logs-user-sub">' + esc(row.raw_username) + '</div>'
                        : '';
                    return '<div class="logs-user-name">' + esc(val) + '</div>' + sub;
                }
            },
            {
                'data': 'created_at',
                'render': function (val) {
                    return '<span class="logs-time">' + esc(val) + '</span>';
                }
            },
            {
                'data': 'sql_text',
                'render': function (val) {
                    return '<code class="logs-code-preview">' + esc(val) + '</code>';
                }
            },
            {
                'data': 'bindings',
                'render': function (val) {
                    if (!val || val === '[]') {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<code class="logs-code-preview logs-code-bind">' + esc(val) + '</code>';
                }
            },
            {
                'data': 'error_message',
                'render': function (val) {
                    if (!val) {
                        return '<span class="logs-ok-pill">OK</span>';
                    }
                    return '<span class="logs-err-pill" title="' + esc(val) + '">' + esc(val) + '</span>';
                }
            }
        ],
        'dom': "<'row'<'col-sm-12'tr>><'logs-dt-footer'lip>"
    });

    function openDetailModalById(id) {
        if (!id) return;
        var baseURL = mlite.url + '/' + mlite.admin;
        var loadURL = baseURL + '/mlite_logs/detail/' + encodeURIComponent(id) + '?t=' + encodeURIComponent(mlite.token || '');
        var modal = $('#modal_detail_mlite_query_logs');
        var modalContent = $('#modal_detail_mlite_query_logs .modal-content');
        modalContent.html('<div class="text-center" style="padding: 36px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p style="margin-top:8px; color:#64748b;">Memuat detail log #' + esc(id) + '...</p></div>');
        modal.modal('show');
        modalContent.load(loadURL);
    }

    // Klik ganda baris langsung buka detail
    $('#tbl_mlite_query_logs tbody').on('dblclick', 'tr', function () {
        var rowData = var_tbl_mlite_query_logs.row(this).data();
        if (rowData && rowData.id) {
            openDetailModalById(rowData.id);
        }
    });

    // Context menu klik kanan
    $.contextMenu({
        selector: '#tbl_mlite_query_logs tbody tr',
        trigger: 'right',
        events: {
            show: function () { $(this).addClass('selected'); },
            hide: function () { $(this).removeClass('selected'); }
        },
        callback: function (key) {
            var data = var_tbl_mlite_query_logs.row(this).data();
            if (key === 'detail' && data && data.id) {
                openDetailModalById(data.id);
            }
        },
        items: {
            'detail': { name: 'Lihat Detail Lengkap', icon: 'fa-eye' },
            'sep1': '---------',
            'quit': { name: 'Tutup', icon: 'fa-close' }
        }
    });

    // Trigger pencarian via tombol, Enter, atau Reset
    $('#search_mlite_query_logs').on('click', function () {
        var_tbl_mlite_query_logs.draw();
    });

    $('#search_text_mlite_query_logs').on('keydown', function (e) {
        if (e.key === 'Enter' || e.which === 13) {
            e.preventDefault();
            var_tbl_mlite_query_logs.draw();
        }
    });

    $('#reset_mlite_query_logs').on('click', function () {
        $('#search_text_mlite_query_logs').val('');
        var_tbl_mlite_query_logs.order([[0, 'desc']]).draw();
    });

    // Tombol Detail
    $('#lihat_detail_mlite_query_logs').on('click', function () {
        var rowData = var_tbl_mlite_query_logs.rows({ selected: true }).data()[0];
        if (rowData && rowData.id) {
            openDetailModalById(rowData.id);
        } else {
            bootbox.alert('Klik salah satu baris log terlebih dahulu (atau klik 2x pada baris).');
        }
    });

    // Tombol Bersihkan Log > 30 Hari
    $('#btn_prune_logs').on('click', function () {
        bootbox.confirm({
            title: 'Bersihkan Log Query Lama',
            message: 'Hapus seluruh entri <code>mlite_query_logs</code> yang usianya lebih dari <strong>30 hari</strong>? Tindakan ini mempercepat loading dan menghemat ukuran database.',
            buttons: {
                confirm: { label: 'Ya, Hapus > 30 Hari', className: 'btn-danger' },
                cancel: { label: 'Batal', className: 'btn-default' }
            },
            callback: function (ok) {
                if (!ok) return;
                $.ajax({
                    url: "{?=url([ADMIN,'mlite_logs','prune'])?}",
                    method: 'POST',
                    dataType: 'json',
                    data: { days: 30, t: mlite.token || '' },
                    success: function (res) {
                        if (res && res.status === 'success') {
                            bootbox.alert(esc(res.message));
                            var_tbl_mlite_query_logs.draw();
                        } else {
                            bootbox.alert('Gagal membersihkan log: ' + esc(res && res.message ? res.message : 'Error'));
                        }
                    },
                    error: function () {
                        bootbox.alert('Terjadi kesalahan koneksi saat membersihkan log.');
                    }
                });
            }
        });
    });

    // Tombol Pratinjau & Ekspor (max 200 terbaru, aman dengan DOM nodes)
    $('#lihat_data_mlite_query_logs').on('click', function () {
        var container = document.getElementById('forTable_mlite_query_logs');
        if (container) {
            container.innerHTML = '<div class="text-center" style="padding:30px;"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></div>';
        }
        $('#modal-title').text('Pratinjau 200 Log Terbaru');
        $('#modal_lihat_mlite_query_logs').modal('show');

        $.ajax({
            url: "{?=url([ADMIN,'mlite_logs','aksi'])?}",
            method: 'POST',
            dataType: 'json',
            data: {
                typeact: 'lihat',
                search_field_mlite_query_logs: $('#search_field_mlite_query_logs').val(),
                search_text_mlite_query_logs: $('#search_text_mlite_query_logs').val(),
                t: mlite.token || ''
            },
            success: function (res) {
                if (!container) return;
                var wrap = document.createElement('div');
                wrap.className = 'table-responsive';
                var tbl = document.createElement('table');
                tbl.id = 'tbl_lihat_mlite_query_logs';
                tbl.className = 'table table-bordered table-striped table-condensed';
                tbl.style.width = '100%';
                tbl.style.fontSize = '12px';

                var thead = document.createElement('thead');
                var trH = document.createElement('tr');
                ['Id', 'Pengguna', 'Waktu', 'SQL Text', 'Bindings', 'Error'].forEach(function (h) {
                    var th = document.createElement('th');
                    th.textContent = h;
                    trH.appendChild(th);
                });
                thead.appendChild(trH);
                tbl.appendChild(thead);

                var tbody = document.createElement('tbody');
                if (Array.isArray(res)) {
                    res.forEach(function (item) {
                        var tr = document.createElement('tr');
                        ['id', 'username', 'created_at', 'sql_text', 'bindings', 'error_message'].forEach(function (k) {
                            var td = document.createElement('td');
                            td.textContent = item && item[k] !== undefined && item[k] !== null ? String(item[k]) : '';
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    });
                }
                tbl.appendChild(tbody);
                wrap.appendChild(tbl);
                container.replaceChildren(wrap);
            }
        });
    });

    $('#export_pdf').on('click', function () {
        var doc = new jsPDF('p', 'pt', 'A4');
        var img = "{?=file_exists(BASE_DIR . '/' . $settings['logo']) ? base64_encode(file_get_contents(BASE_DIR . '/' . $settings['logo'])) : ''?}";
        if (img) {
            doc.addImage(img, 'JPEG', 20, 10, 50, 50);
        }
        doc.setFontSize(20);
        doc.text("{$settings.nama_instansi}", 80, 35, null, null, null);
        doc.setFontSize(10);
        doc.text("{$settings.alamat} - {$settings.kota} - {$settings.propinsi}", 80, 46, null, null, null);
        doc.text("Telepon: {$settings.nomor_telepon} - Email: {$settings.email}", 80, 56, null, null, null);
        doc.line(20, 70, 572, 70, null);
        doc.line(20, 72, 572, 72, null);
        doc.setFontSize(14);
        doc.text("Tabel Data Log Query (200 Terbaru)", 20, 95, null, null, null);
        const totalPagesExp = "{total_pages_count_string}";
        doc.autoTable({
            html: '#tbl_lihat_mlite_query_logs',
            startY: 105,
            margin: { left: 20, right: 20 },
            styles: { fontSize: 8, cellPadding: 4 },
            didDrawPage: function (data) {
                var footerStr = "Page " + doc.internal.getNumberOfPages();
                if (typeof doc.putTotalPages === 'function') {
                    footerStr = footerStr + " of " + totalPagesExp;
                }
                doc.setFontSize(9);
                doc.text(footerStr, data.settings.margin.left, doc.internal.pageSize.height - 10);
            }
        });
        if (typeof doc.putTotalPages === 'function') {
            doc.putTotalPages(totalPagesExp);
        }
        window.open(doc.output('bloburl'), '_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
    });

    $('#export_xlsx').on('click', function () {
        var tbl1 = document.getElementById('tbl_lihat_mlite_query_logs');
        if (!tbl1) return;
        var worksheet_tmp1 = XLSX.utils.table_to_sheet(tbl1);
        var a = XLSX.utils.sheet_to_json(worksheet_tmp1, { header: 1 });
        var worksheet1 = XLSX.utils.json_to_sheet(a, { skipHeader: true });
        var new_workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(new_workbook, worksheet1, 'Data mlite_query_logs');
        XLSX.writeFile(new_workbook, 'mlite_query_logs_terbaru.xls');
    });

    $('#view_chart').on('click', function () {
        var baseURL = mlite.url + '/' + mlite.admin;
        window.open(baseURL + '/mlite_logs/chart?t=' + encodeURIComponent(mlite.token || ''), '_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,modal=yes');
    });
});

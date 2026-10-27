/* 3. CONFIRM BOX
--------------------------------------------------------- */
$(document).on('click touchstart', '[data-confirm]:not(.disabled):not([disabled])', function(evt)
{
    evt.preventDefault();
    var text = $(this).attr('data-confirm');
    var source = $(this);

    bootbox.confirm({
        message: text,
        callback: function(result) {
            if(result)
            {
                if(source.is('[type="submit"]'))
                {
                    $(document).off('click touchstart', '[data-confirm]:not(.disabled):not([disabled])');
                    source.click();
                }
                else if(source.is('a'))
                {
                    $(location).attr('href', source.attr('href'));
                }
            }
        }
    });
});

/* 4. TOOLTIP ACTIVATION
--------------------------------------------------------- */
$(function () {
    $("[data-toggle='tooltip']").tooltip();
    $("[data-toggle='popover']").popover();
});

/* 5. NOTIFICATION
--------------------------------------------------------- */
$(function () {
	if($('#notify').length)
    {
		$('#notify').slideDown(500);
        if($( window ).width() < 768)
            $('#content-wrapper').animate({'top' : '+=46'}, 500);

		setTimeout(function() {
			$('#notify').slideUp(500);
            if($( window ).width() < 768)
                $('#content-wrapper').animate({'top' : '-=46'}, 500);
		}, 8000);
	}
});


/* 7. TINYNAV
--------------------------------------------------------- */
$(function () {
    $('.panel-heading .nav-tabs').tinyNav({
        active: 'active'
    });
});

/* 8. CUSTOM CHECKBOXES & RADIO BUTTONS
--------------------------------------------------------- */
$(':checkbox').kalypto();
$(':radio').kalypto({toggleClass: 'toggleR'});

/* 9. REMOTE MODAL
--------------------------------------------------------- */
$(document).on('click', 'a[data-toggle="modal"]', function(e) {
    var target_modal = $(this).attr('data-target') || $(this).attr('href');
    var remote_content = $(this).attr('href');

    if(!remote_content || remote_content.indexOf('#') === 0) return;

    e.preventDefault();
    var modal = $(target_modal);
    if (!modal.length) return;

    var modalContent = modal.find('.modal-content');
    modalContent.html('<div class="text-center" style="padding:40px;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><p style="margin-top:10px;">Memuat data...</p></div>');
    modal.modal('show');

    modalContent.load(remote_content, function(response, status, xhr) {
        if (status === "error") {
            modalContent.html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Error</h4></div><div class="modal-body"><div class="alert alert-danger">Gagal memuat konten (' + xhr.status + ': ' + xhr.statusText + ')</div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>');
        }
    });

    return false;
});

$(document).on('hidden.bs.modal', '#riwayatModal, #asuransiModal', function () {
    $(this).removeData('bs.modal').find('.modal-content').html('<div class="text-center" style="padding:40px;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><p style="margin-top:10px;">Memuat data...</p></div>');
});

/* 10. CUSTOM SELECT
--------------------------------------------------------- */
$('select').each(function () {
    var options = {
        useDimmer: true,
        useSearch: false,
        labels: {
            search: '...'
        }
    };
    $.each($(this).data(), function (key, value) {
        options[key] = value;
    });
    $(this).selectator(options);
});

function showTime() {
    var today = new Date();
    var curr_hour = today.getHours();
    var curr_minute = today.getMinutes();
    var curr_second = today.getSeconds();
    curr_hour = checkTime(curr_hour);
    curr_minute = checkTime(curr_minute);
    curr_second = checkTime(curr_second);
    document.getElementById('clock').innerHTML=curr_hour + ":" + curr_minute + ":" + curr_second;
}

function checkTime(i) {
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

setInterval(showTime, 500);

$(document).ready(function() {
    $('.list-star').rating({
        showCancel: false,
        readOnly: true
    });
    $('.filter-star').rating({
        showCancel: true,
        cancelValue: null
    });

    $("#dialog-people-delete").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        minWidth: 350,
        minHeight: 170
    });

    $('#show-filters').click( function(){
        $('.search-filter').toggle();
        if( $('.search-filter:visible').size() > 0 ) {
            $('#show-filters').text( jobboard.langs.text_hide_filter );
        } else {
            $('#show-filters').text( jobboard.langs.text_show_filter );
        }
    });

    // tooltips notes
    $.each($('.note'), function(index, value) {
        $(value).osc_tooltip($(value).attr('data-tooltip'), {
            layout: 'gray-tooltip',
            position: {
                x: 'right',
                y: 'middle'
            }
        });
    });

    $("#reall-appl-status").dialog({
        modal: true,
        resizable: false,
        autoOpen: false,
        minHeight: 200
    });
    $("#new_applicant_dialog").dialog({
        modal: true,
        minWidth: 400,
        minHeight: 300,
        resizable: false,
        autoOpen: false
    });

    $("#add-applicant").click(function() {
        $("#new_applicant_dialog").dialog("open");
    });
    $("#cancel-dialog").click(function() {
        $("#new_applicant_dialog").dialog("close");
    });

    $("#add_new_applicant_form").validate({
        onkeyup: false,
        rules: {
            'applicant-name': {
                required: true
            },
            'applicant-email': {
                required: true,
                email: true
            },
            'applicant-phone': {
                required: true
            },
            'applicant-birthday': {
                required: true
            },
            'applicant-sex': {
                required: true
            }
        },
        errorPlacement: function(error,element) {
            $(element).parent().addClass('js-error');
        },
        unhighlight: function(element) {
            $(element).parent().removeClass('js-error');
        },
        submitHandler: function(form){
            $('button[type=submit], input[type=submit]').attr('disabled', 'disabled');
            form.submit();
        }
    });

    jQuery.validator.addMethod("regex_bday", function(value, element) {
        if(/^\d{2}[/]\d{2}[/]\d{4}$/.test(value)) {
            return true;
        } else {
            return false;
        }
    });
    $('#applicant-birthday').rules(
        "add", {
            'regex_bday': true,
            messages: {
                regex_bday: jobboard.langs.invalid_birthday_date
            }
        }
        );
    jQuery.validator.addMethod("file_required", function(value, element) {
        if($("#applicant-attachment")[0].files[0]) {
            return true;
        } else {
            return false;
        }
    });
    $("#applicant-attachment").rules(
        "add", {
            'file_required': true,
            messages: {
                file_required: jobboard.langs.applicant_file_required
            }
        }
        );

    $("#dialog-new_status").dialog({
        autoOpen: false,
        modal: true,
        resizable: false,
        width: 600
    });

    /* SORT TABLE ROWS */
    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };
    $("#dialog-new_status table tbody").sortable({
        helper: fixHelper,
        start: function(event, ui){
            $(ui.item).animate({
                'background-color': '#F1F1F1'
            }, 'fast');
        },
        stop: function(event, ui){
            $(ui.item).animate({
                'background-color': '#ffffff'
            }, 'fast');
        }
    }).disableSelection();

    /* OPEN STATUS MANAGEMENT DIALOG */
    $("#status_manage").on("click", function() {
        $("#dialog-new_status").dialog("open");
    });

    /* CLOSE STATUS MANAGEMENT DIALOG */
    $("#add-new-status-cancel, #dialog-new_status .ui-icon.ui-icon-closethick").on("click", function()
    {
        /* order statuses and saved it into DB */
        var aStatuses = new Array();
        $("#dialog-new_status .table tbody tr td:first-child").each(function() {
            var aStatus = new Array();
            aStatus.push($(this).children().attr("id"));
            aStatus.push($.trim($(this).text()));
            aStatuses.push(aStatus);
        });
        $.post(jobboard.ajax.update_statuses, {
            'statuses' : aStatuses
        });

        $("#dialog-new_status").dialog("close");
        location.reload();
    });

    /* ADD NEW STATUS */
    $("#add-new-status-submit").on("click", function()
    {
        $("#error_list").empty().hide();
        $.post( jobboard.ajax.new_status_applicant,
        {
            'statusName' : $("#new_status_input").val()
        }).done(function(data){
            if(data.match(/already exists/)) {
                $("#error_list").append("<li>" + data + "</li>").show();
            } else {
                var obj = jQuery.parseJSON(data);
                $("#dialog-new_status table").append('<tr><td><label id=' + obj.id +
                    '>' + obj.name + '</label></td><td><a class="delete_status" data-status-id="'
                    + obj.id + '"><div class="icon-delete-status"></div></a></td></tr>');
            }
        });
    });

    /* DELETE STATUS*/
    $("#dialog-new_status .table").on("click", "a.delete_status", function(event)
    {
        var statusId = $(this).data("status-id");
        /* assign old status value to input hidden that will be needed when reassign applicants */
        $("#reallocating-applicants #old-status-delete").attr("value", statusId);
        $.post(jobboard.ajax.get_current_applicants_by_status,
        {
            'statusID' : statusId
        })
        .done(function(num_applicants)
        {
            if(num_applicants > 0)
            {
                /* reset values for current status */
                $("#status-appl-num-info").empty();
                $("#selector-new-statuses").empty();
                $("#status-appl-num-info").append(num_applicants);
                $(".adding-new-status").hide();
                /* get current statuses to reasign */
                $.post( jobboard.ajax.get_current_statuses, {}).done(function(data)
                {
                    /* assing new values to selector */
                    var statuses = jQuery.parseJSON(data);
                    if(statuses.length > 1 ) {
                        $("#selector-new-statuses").append("<option value='-1' selected='selected'>" + $("#unread-option").val()  + "</option>");
                        for(var status in statuses) {
                        if(statusId != statuses[status].id) {
console.log(statusId);
console.log(statuses[status].id);
                                $("#selector-new-statuses").append("<option value='" + statuses[status].id + "'>" + statuses[status].name + "</option>");
                            }
                        }
                    } else {
                        $("#selector-new-statuses").append("<option value='-1'>" + $("#unread-option").val()  + "</option>");
                    }
                    $("#reallocating-applicants").show();
                });
            } else {
                /* IF THERE ARE NO APPLICANTS REMOVE STATUS DIRECTLY */
                $.post( jobboard.ajax.delete_status_applicant,
                {
                    'current_status' : $("#reallocating-applicants #old-status-delete").val()
                }).done(function(data) {
                    $("#" + $("#reallocating-applicants #old-status-delete").val() ).closest('tr').remove();
                });
                $("#reallocating-applicants").hide();
                $(".adding-new-status").show();
            }
        });
    });

    $("#button-reassign-close").on("click", function() {
        $("#reallocating-applicants").hide();
        $(".adding-new-status").show();
    });

    $("#button-reassign").on("click", function()
    {
        $.post( jobboard.ajax.delete_status_applicant,{
            'current_status' : $("#reallocating-applicants #old-status-delete").val(),
            'new_status': $("#reallocating-applicants #selector-new-statuses").val()
        }).done(function(data) {
            $("#" + $("#reallocating-applicants #old-status-delete").val() ).closest('tr').remove();
            $("#reallocating-applicants").hide();
        });
        $("#reallocating-applicants").hide();
        $(".adding-new-status").show();
    });

    $("#dialog-new_status #help-box a").on("click", function() {
        $("#dialog-new_status #help-box").hide();
    });

    $("#dialog-new_status .ico-help").on("click", function(){
        $("#dialog-new_status #help-box").show();
    });

    $("#select-statuses").on("change", function() {
        window.location.href = $("#select-statuses option:selected").data("status-url");
    });

    $(".menu-applicant").on("mouseenter", function() {
        $('#menu-applicant-options').show();
    }).mouseleave(function() {
        $('#menu-applicant-options').hide();
    });

    $("#select-appl-status").on("change", function() {
        var applicant_id = $(this).attr('data-applicant-id');
        var status_id    = $(this).val();

        if($(this).val() != 'default') {
            $.getJSON(jobboard.ajax.applicant_status,
            {
                "applicantId" : applicant_id,
                "status" : status_id
            },
            function(data){
                var background_color = '#FFF0DF';
                var text = '';
                if(status_id != '-1') {
                    background_color = 'white';
                    text = data.name;
                }
                $('tr[data-applicant-id="' + applicant_id + '"]').css("background-color", background_color);
                $('tr[data-applicant-id="' + applicant_id + '"] td:nth-child(6)').empty().append(text);
            });
            $.post(jobboard.ajax.save_applicant_status,
            {
                "applicantID" : applicant_id,
                "statusId"  : status_id
            }, function(data) {});
        }
        $("#reall-appl-status").dialog('close');
    });
});

var applicant = {
    tour: {
        id: "applicant",
        steps: [
        {
            target: 'add-applicant',
            title: jobboard.langs.hopscotch.feature.add_applicant.title,
            content: jobboard.langs.hopscotch.feature.add_applicant.content,
            placement: "left",
            yOffset: "-16px"
        }
        ],
        scrollTopMargin: 100,
        showCloseButton: false,
        i18n: {
            nextBtn: jobboard.langs.hopscotch.i18n.nextBtn,
            prevBtn: jobboard.langs.hopscotch.i18n.prevBtn,
            doneBtn: jobboard.langs.hopscotch.i18n.doneBtn,
            skipBtn: jobboard.langs.hopscotch.i18n.skipBtn,
            closeTooltip: jobboard.langs.hopscotch.i18n.closeTooltip,
            stepNums: [jobboard.langs.hopscotch.feature.add_applicant.bubble]
        }
    }
}

function delete_applicant(id) {
    $("#delete_id").attr("value", id);
    $("#dialog-people-delete").dialog('open');
}

function reassign_applicant(applicant_id, current_status_id, applicant_name) {
    $("#applicant-username").empty().append(' ' + applicant_name);
    $("#reall-appl-status").dialog('open');

    del_statuses_from_select("#select-appl-status");
    add_statuses(current_status_id);

    $("#select-appl-status").attr("data-applicant-id", applicant_id);
}

var del_statuses_from_select = function(select) {
    var values = $(select + " > option").map(function() {
        if($(this).val() != 'default' && $(this).val() != '-1') {
            $(select + " > option[value='"+ $(this).val() +"']").remove();
        }
    });
}

var add_statuses = function(current_status_id) {
    $.post( jobboard.ajax.get_current_statuses, {})
    .done(function(data) {
        /* assing new values to selector */
        var statuses = jQuery.parseJSON(data);
        if(statuses.length > 1 ) {
            for(var status in statuses) {
                if(current_status_id != statuses[status].id) {
                    $("#select-appl-status").append("<option value='" + statuses[status].id + "'>" + statuses[status].name + "</option>");
                }
            }
        }
    });
}
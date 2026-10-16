<script>

    function getLGA(state_id, selected_lga_id){
        var url = '{{ route('get_lga', [':id']) }}';
        url = url.replace(':id', state_id);
        var lga = $('#lga_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                lga.empty();
                lga.append($('<option>', { value: '', text: '' }));
                $.each(resp, function (i, data) {
                    var option = $('<option>', {
                        value: data.id,
                        text: data.name
                    });
                    if (selected_lga_id && String(selected_lga_id) === String(data.id)) {
                        option.attr('selected', 'selected');
                    }
                    lga.append(option);
                });

            }
        })
    }

    function getClassSections(class_id, destination){
        var url = '{{ route('get_class_sections', [':id']) }}';
        url = url.replace(':id', class_id);
        var section = destination ? $(destination) : $('#section_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                //console.log(resp);
                section.empty();
                $.each(resp, function (i, data) {
                    section.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });

            }
        })
    }

    function getClassSubjects(class_id){
        var url = '{{ route('get_class_subjects', [':id']) }}';
        url = url.replace(':id', class_id);
        var section = $('#section_id');
        var subject = $('#subject_id');

        $.ajax({
            dataType: 'json',
            url: url,
            success: function (resp) {
                console.log(resp);
                section.empty();
                subject.empty();
                $.each(resp.sections, function (i, data) {
                    section.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });
                $.each(resp.subjects, function (i, data) {
                    subject.append($('<option>', {
                        value: data.id,
                        text: data.name
                    }));
                });

            }
        })
    }


    {{--Notifications--}}

    @if (session('pop_error'))
    pop({msg : '{{ session('pop_error') }}', type : 'error'});
    @endif

    @if (session('pop_warning'))
    pop({msg : '{{ session('pop_warning') }}', type : 'warning'});
    @endif

 @if (session('pop_success'))
    pop({msg : '{{ session('pop_success') }}', type : 'success', title: 'SUBMISSION NOTICE'});
    @endif

    @if (session('flash_info'))
      flash({msg : '{{ session('flash_info') }}', type : 'info'});
    @endif

    @if (session('flash_success'))
      flash({msg : '{{ session('flash_success') }}', type : 'success'});
    @endif

    @if (session('flash_warning'))
      flash({msg : '{{ session('flash_warning') }}', type : 'warning'});
    @endif

     @if (session('flash_error') || session('flash_danger'))
      flash({msg : '{{ session('flash_error') ?: session('flash_danger') }}', type : 'danger'});
    @endif

    {{--End Notifications--}}

    function pop(data){
        return swal({
            title: data.title ? data.title : 'Oops...',
            text: data.msg,
            icon: data.type
        });
    }

    function flash(data){
        var persistent = data.type === 'danger' || data.type === 'error' || data.type === 'warning';
        new PNotify({
            text: data.msg,
            type: data.type,
            delay: persistent ? 0 : 2000,
            hide: !persistent,
            closer: true,
            sticker: false
        });
    }

    function confirmDelete(id) {
        swal({
            title: "Are you sure?",
            text: "Once deleted, you will not be able to recover this item!",
            icon: "warning",
            buttons: true,
            dangerMode: true
        }).then(function(willDelete){
            if (willDelete) {
             $('form#item-delete-'+id).submit();
            }
        });
    }

    function confirmReset(id) {
        swal({
            title: "Are you sure?",
            text: "This will reset this item to default state",
            icon: "warning",
            buttons: true,
            dangerMode: true
        }).then(function(willDelete){
            if (willDelete) {
             $('form#item-reset-'+id).submit();
            }
        });
    }

    $('form#ajax-reg').on('submit', function(ev){
        if (ev.isDefaultPrevented()) {
            return;
        }
        ev.preventDefault();
        submitWithConfirmation($(this), 'store');
    });

    $('form.ajax-pay').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this), 'store');

//        Retrieve IDS
        var form_id = $(this).attr('id');
        var td_amt = $('td#amt-'+form_id);
        var td_amt_paid = $('td#amt_paid-'+form_id);
        var td_bal = $('td#bal-'+form_id);
        var input = $('#val-'+form_id);

        // Get Values
        var amt = parseInt(td_amt.data('amount'));
        var amt_paid = parseInt(td_amt_paid.data('amount'));
        var amt_input = parseInt(input.val());

//        Update Values
        amt_paid = amt_paid + amt_input;
        var bal = amt - amt_paid;

        td_bal.text(''+bal);
        td_amt_paid.text(''+amt_paid).data('amount', ''+amt_paid);
        input.attr('max', bal);
        bal < 1 ? $('#'+form_id).fadeOut('slow').remove() : '';
    });

    $('form.ajax-store').on('submit', function(ev){
        if (ev.isDefaultPrevented()) {
            return;
        }
        ev.preventDefault();
        var form = $(this);
        submitWithConfirmation(form, 'store', function(){
            var div = form.data('reload');
            div ? reloadDiv(div) : '';
        });
    });

    $('form.ajax-update').on('submit', function(ev){
        ev.preventDefault();
        submitForm($(this));
        var div = $(this).data('reload');
        div ? reloadDiv(div) : '';
    });

    $('.download-receipt').on('click', function(ev){
        ev.preventDefault();
        $.get($(this).attr('href'));
        flash({msg : '{{ 'Download in Progress' }}', type : 'info'});
    });

    function reloadDiv(div){
        var url = window.location.href;
        url = url + ' '+ div;
        $(div).load( url );
    }

    function submitWithConfirmation(form, formType, onConfirmed){
        var submit = function(){
            submitForm(form, formType);
            if (onConfirmed) {
                onConfirmed();
            }
        };

        if (!form.data('confirm-submit')) {
            submit();
            return;
        }

        swal({
            title: form.data('confirm-title') || 'Confirm account creation',
            text: form.data('confirm-message') || 'Create this account now?',
            icon: 'warning',
            buttons: ['Cancel', form.data('confirm-label') || 'Create Account']
        }).then(function(confirmed){
            if (confirmed) {
                submit();
            }
        });
    }

    function submitForm(form, formType){
        var btn = form.find('button[type=submit]');
        disableBtn(btn);
        var ajaxOptions = {
            url:form.attr('action'),
            type:'POST',
            cache:false,
            processData:false,
            dataType:'json',
            contentType:false,
            data:new FormData(form[0])
        };
        var req = $.ajax(ajaxOptions);
        req.done(function(resp){
            var successDialog = null;
            if (resp.ok && resp.msg && form.data('success-popup')) {
                successDialog = pop({msg: resp.msg, type: 'success', title: form.data('success-title') || 'Success'});
            } else {
                resp.ok && resp.msg
                   ? flash({msg:resp.msg, type:'success'})
                   : flash({msg:resp.msg, type:'danger'});
            }
            hideAjaxAlert();
            enableBtn(btn);
            formType == 'store' ? clearForm(form) : '';
            if (resp.ok && resp.url && form.data('follow-success-url')) {
                if (successDialog) {
                    successDialog.then(function(){
                        window.location.assign(resp.url);
                    });
                } else {
                    window.location.assign(resp.url);
                }
                return;
            }
            scrollTo('body');
            return resp;
        });
        req.fail(function(e){
            if (e.status == 422){
                var errors = e.responseJSON.errors;
                displayAjaxErr(errors);
            }
           if(e.status == 500){
               var msg = (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : 'An unexpected server error occurred. Please try again.';
               displayAjaxErr([msg]);
           }
            if(e.status == 404){
               displayAjaxErr([e.status + ' ' + e.statusText + ' - Requested Resource or Record Not Found']);
           }
            enableBtn(btn);
            return e.status;
        });
    }

    function disableBtn(btn){
        var btnText = btn.data('text') ? btn.data('text') : 'Submitting';
        btn.prop('disabled', true).html('<i class="icon-spinner mr-2 spinner"></i>' + btnText);
    }

    function enableBtn(btn){
        var btnText = btn.data('text') ? btn.data('text') : 'Submit Form';
        btn.prop('disabled', false).html(btnText + '<i class="icon-paperplane ml-2"></i>');
    }

    function displayAjaxErr(errors){
        $('#ajax-alert').stop(true, true).show().html(' <div class="alert alert-danger border-0 alert-dismissible" id="ajax-msg"><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>');
        $.each(errors, function(k, v){
            $('#ajax-msg').append('<span><i class="icon-arrow-right5"></i> '+ v +'</span><br/>');
        });
        scrollTo('body');
    }

    function scrollTo(el){
        $('html, body').animate({
            scrollTop:$(el).offset().top
        }, 2000);
    }

    function hideAjaxAlert(){
        $('#ajax-alert').hide();
    }

    function clearForm(form){
        form.find('.select, .select-search').val([]).select2({ placeholder: 'Select...'});
        form.find('input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea').val('');
        form.find('.date-pick').val('');
        form[0].reset();
    }

    document.querySelectorAll('[data-list-filter]').forEach(function(searchInput){
        var table = document.getElementById(searchInput.dataset.listFilter);
        var emptyMessage = document.querySelector('[data-list-filter-empty-for="' + searchInput.dataset.listFilter + '"]');
        if (!table) {
            return;
        }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr.selectable-row'));
        searchInput.addEventListener('input', function(){
            var query = searchInput.value.trim().toLocaleLowerCase();
            var visibleRows = 0;

            rows.forEach(function(row){
                var matches = row.textContent.toLocaleLowerCase().indexOf(query) !== -1;
                row.hidden = !matches;
                if (matches) {
                    visibleRows++;
                } else {
                    row.querySelectorAll('.bulk-select-checkbox:checked').forEach(function(checkbox){
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
            });

            if (emptyMessage) {
                emptyMessage.hidden = !query || visibleRows > 0 || rows.length === 0;
            }
        });
    });

    document.addEventListener('keydown', function(event){
        if (!event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) {
            return;
        }

        var key = event.key.toLowerCase();
        var prefix = key === 'z' ? 'undo-' : (key === 'y' ? 'redo-' : null);
        if (!prefix) {
            return;
        }

        var button = Array.prototype.find.call(
            document.querySelectorAll('button[id^="' + prefix + '"]'),
            function(candidate) {
                return !candidate.disabled && candidate.getClientRects().length > 0;
            }
        );

        if (button) {
            event.preventDefault();
            button.click();
        }
    });



</script>
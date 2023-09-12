(($) => {
    $(window).on('add-balance', function (event, data) {
        console.log('Triggered Add Balance form for ' + data.user_id);
        $(window).trigger('open-wallet-popup', {
            id: "add-balance-popup", reset: true,
            fields: [
                {
                    key: 'user_id',
                    value: data.user_id
                }
            ]
        });
    });

    $(window).on('use-balance', function (event, data) {
        console.log('Triggered Use Balance form for ' + data.user_id);
        $(window).trigger('open-wallet-popup', {
            id: "use-balance-popup", reset: true,
            fields: [
                {
                    key: 'user_id',
                    value: data.user_id
                },
                {
                    key: 'balance',
                    value: data.balance
                }
            ]
        });
    });

    $(window).on('open-wallet-popup', function (event, popup_data) {
        console.log('Triggered open popup ' + popup_data.id);
        let popup_wrap = $('.wallet-popup-wrap'),
            popup = $("#" + popup_data.id);

        $('.wallet-popup.popup-open').removeClass('popup-open');

        if (popup_data.reset) {
            resetPopup(popup);
        }
        if (popup_data.fields) {
            presetPopup(popup, popup_data.fields);
        }

        popup.addClass('popup-open');
        popup_wrap.addClass('open');
    });

    $(window).on('close-wallet-popup', function (event, popup_data) {
        console.log('Triggered close popup ' + popup_data.id);
        let popup_wrap = $('.wallet-popup-wrap');
        $("#" + popup_data.id).removeClass('popup-open');
        if ($('.wallet-popup-wrap .wallet-popup.popup-open').length < 1) {
            popup_wrap.removeClass('open');
        }
    });

    $(document).on('click', '.wallet-popup-close', function (e) {
        $(window).trigger('close-wallet-popup', {id: $(this).closest('.wallet-popup').attr('id')});
    });

    $(document).on('submit', '#add-balance-popup form', function (e) {
        e.preventDefault();
        let form = $(this);
        form.closest('.wallet-popup').find('.error-message').remove();
        makeRequest(
            {
                action: 'add_balance',
                user_id: form.find('[name=user_id]').val(),
                amount: form.find('[name=amount]').val(),
                reason: form.find('[name=reason]').val(),
            },
            (response) => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    form.before('<p class="error-message">' + response.message + '</p>');
                }
            }
        );
    });

    $(document).on('submit', '#use-balance-popup form', function (e) {
        e.preventDefault();
        let form = $(this);
        form.closest('.wallet-popup').find('.error-message').remove();
        makeRequest(
            {
                action: 'use_balance',
                user_id: form.find('[name=user_id]').val(),
                amount: form.find('[name=amount]').val(),
                reason: form.find('[name=reason]').val(),
            },
            (response) => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    form.before('<p class="error-message">' + response.message + '</p>');
                }
            }
        );
    });


    let search_key = '',
        request_timeout = setTimeout(function (){},0);
    $(document).on('input change', '#search-form input[name=search]', function (e) {
        $(this).closest('form').submit();
    });

    $(document).on('submit', '#search-form', function (e) {
        e.preventDefault();
        let form = $(this),
            results = $('.search-results'),
            search = form.find('[name=search]').val().trim();

        if (search_key == search) {
            return;
        }

        search_key = search;

        clearTimeout(request_timeout);

        request_timeout = setTimeout(function (){
            results.slideUp();
            results.html('');
            makeRequest(
                {
                    action: 'wallet_search_user',
                    search: search,
                },
                (response) => {

                    results.html('');
                    if (response.ok) {
                        results.html(response.results);
                    } else {
                        results.html(response.results);
                    }
                    results.slideDown();
                }
            );
        },350);

    });

    let live_request;

    function makeRequest(data, success, error = false, config = {}) {
        if (!error) {
            error = function (response) {
                console.error(response);
            };
        }
        let request_url = config.url ? config.url : ajaxurl,
            method = config.method ? config.method : 'POST';

        if (config.async === true) {
            $.ajax({
                url: request_url,
                type: method,
                data: data,
                success: success,
                error: error
            });
            return;
        }

        if (live_request) live_request.abort();

        $('body').addClass('requestLoading');

        live_request = $.ajax({
            url: request_url,
            type: method,
            data: data,
            success: success,
            error: error
        }).always(function () {
            $('body').removeClass('requestLoading');
        });
    }

    function resetPopup(popup) {
        popup = $(popup);

        popup.find('select,textarea,input:not([type=submit],[type=checkbox],[type=radio])').val('');
        popup.find('input[type=checkbox],input[type=radio]').prop('checked', false);
    }

    function presetPopup(popup, fields) {
        popup = $(popup);
        console.log(fields);

        $.each(fields, function (i, field) {
            popup.find('[name="' + field.key + '"]').val(field.value);
            console.log(`popup.find('[name="${field.key}"]').val(${field.value})`)
            console.log(popup.find('[name="' + field.key + '"]').val())
        });
    }


})(jQuery)
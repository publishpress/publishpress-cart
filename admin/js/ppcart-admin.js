(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */



})( jQuery );

function ppcart_settings(id){
	jQuery(".tab-content").hide();
	jQuery('.settings_tab').removeClass('nav-tab-active');
	jQuery("#content_tab_"+id).slideDown();
    jQuery("#settings_tab_"+id).addClass('nav-tab-active');

    try {
        if (window.localStorage) {
            window.localStorage.setItem('ppcartSettingsMainTab', id);
        }
    } catch (e) {
        // Ignore storage errors.
    }
}

window.ppCartAdminDialog = window.ppCartAdminDialog || {
    confirm: function (options) {
        var settings = jQuery.extend({
            title: '',
            message: '',
            confirmText: 'Continue',
            cancelText: 'Cancel',
            onConfirm: jQuery.noop,
            onCancel: jQuery.noop
        }, options || {});

        if (!jQuery.fn.dialog) {
            if (window.confirm(settings.message)) {
                settings.onConfirm();
            } else {
                settings.onCancel();
            }
            return;
        }

        var confirmed = false;
        var $dialog = jQuery('<div class="ppcart-admin-dialog" tabindex="-1"><p></p></div>');

        $dialog.find('p').text(settings.message);
        $dialog.dialog({
            title: settings.title,
            dialogClass: 'wp-dialog',
            autoOpen: true,
            modal: true,
            closeOnEscape: true,
            width: 480,
            buttons: [
                {
                    text: settings.cancelText,
                    class: 'button',
                    click: function () {
                        jQuery(this).dialog('close');
                    }
                },
                {
                    text: settings.confirmText,
                    class: 'button button-primary',
                    click: function () {
                        confirmed = true;
                        jQuery(this).dialog('close');
                        settings.onConfirm();
                    }
                }
            ],
            close: function () {
                if (!confirmed) {
                    settings.onCancel();
                }

                jQuery(this).dialog('destroy').remove();
            }
        });
    },
    alert: function (options) {
        var settings = jQuery.extend({
            title: '',
            message: '',
            okText: 'OK',
            onClose: jQuery.noop
        }, options || {});

        if (!jQuery.fn.dialog) {
            window.alert(settings.message);
            settings.onClose();
            return;
        }

        var $dialog = jQuery('<div class="ppcart-admin-dialog" tabindex="-1"><p></p></div>');

        $dialog.find('p').text(settings.message);
        $dialog.dialog({
            title: settings.title,
            dialogClass: 'wp-dialog',
            autoOpen: true,
            modal: true,
            closeOnEscape: true,
            width: 480,
            buttons: [
                {
                    text: settings.okText,
                    class: 'button button-primary',
                    click: function () {
                        jQuery(this).dialog('close');
                    }
                }
            ],
            close: function () {
                settings.onClose();
                jQuery(this).dialog('destroy').remove();
            }
        });
    }
};

jQuery(document).on('change','.price',function(){
    let re = /^[0-9.,]+$/;
    let price = jQuery(this).val();
    if (re.test(price)) {

    } else {
        jQuery(this).val("");
    }
});
jQuery(document).ready(function($){
    function initPaymentMethodSubtabs() {
        var i18n = window.ppcart_admin_i18n || {};
        var paymentMethodLabels = {
            cashondelivery: i18n.payment_label_cashondelivery || '',
            stripe: i18n.payment_label_stripe || '',
            paypal: i18n.payment_label_paypal || ''
        };
        var paymentMethodEnableOption = {
            cashondelivery: '_ppcart_cashondelivery_enable',
            stripe: '_ppcart_stripe_enable',
            paypal: '_ppcart_paypal_enable'
        };
        var paymentMeta = null;

        function isValidPaymentView(view) {
            if (view === 'enable') {
                return true;
            }

            return /^method:[a-z0-9_\-]+$/i.test(String(view || ''));
        }

        function getPaymentViewFromUrl() {
            try {
                var url = new URL(window.location.href);
                var view = url.searchParams.get('ppcart_payment_subtab') || '';
                return isValidPaymentView(view) ? view : '';
            } catch (e) {
                return '';
            }
        }

        function getStoredPaymentView() {
            var urlView = getPaymentViewFromUrl();
            if (urlView) {
                return urlView;
            }

            var view = window.localStorage ? window.localStorage.getItem('ppcartPaymentMethodsSubtab') : '';
            if (isValidPaymentView(view)) {
                return view;
            }

            return 'enable';
        }

        function setStoredPaymentView(view) {
            if (!isValidPaymentView(view)) {
                return;
            }

            if (window.localStorage) {
                window.localStorage.setItem('ppcartPaymentMethodsSubtab', view);
            }
        }

        function updateUrlQueryForPaymentView(view) {
            if (!isValidPaymentView(view) || !window.history || !window.history.replaceState) {
                return;
            }

            try {
                var url = new URL(window.location.href);
                url.searchParams.set('ppcart_payment_subtab', view);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {
                // No-op when URL APIs are unavailable.
            }
        }

        function setQueryParamInUrlString(rawUrl, key, value) {
            try {
                var parsed = new URL(rawUrl, window.location.origin);
                parsed.searchParams.set(key, value);
                return parsed.toString();
            } catch (e) {
                return rawUrl;
            }
        }

        function updatePaymentActionLinks(view) {
            if (!isValidPaymentView(view)) {
                return;
            }

            var $container = $('#content_tab_payment_methods');
            if (!$container.length) {
                return;
            }

            $container.find('a[href]').each(function() {
                var href = String($(this).attr('href') || '');
                if (!href) {
                    return;
                }

                if (href.indexOf('ppcsc_action=connect_init') !== -1 && href.indexOf('return_url=') !== -1) {
                    try {
                        var outer = new URL(href, window.location.origin);
                        var returnUrl = outer.searchParams.get('return_url') || '';
                        if (returnUrl) {
                            outer.searchParams.set('return_url', setQueryParamInUrlString(returnUrl, 'ppcart_payment_subtab', view));
                            $(this).attr('href', outer.toString());
                        }
                    } catch (e) {
                        // Keep original href on parse errors.
                    }
                    return;
                }

                if (href.indexOf('ppcart_stripe_connect_disconnect=1') !== -1 || href.indexOf('ppcart_stripe_connect_setup_webhook=1') !== -1) {
                    $(this).attr('href', setQueryParamInUrlString(href, 'ppcart_payment_subtab', view));
                }
            });
        }

        function ucfirst(text) {
            return text.charAt(0).toUpperCase() + text.slice(1);
        }

        function buildPaymentMeta($container) {
            var methods = {};

            $.each(paymentMethodEnableOption, function(methodSlug, enableOptionName) {
                var $enableField = $container.find('[name="' + enableOptionName + '"]');
                var $enableRow = $enableField.closest('tr');
                if (!$enableRow.length) {
                    return;
                }

                var $table = $enableRow.closest('table.form-table');
                var $heading = $table.prevAll('h2').first();
                var $rows = $table.find('tr');

                methods[methodSlug] = {
                    table: $table,
                    heading: $heading,
                    rows: $rows,
                    enableRow: $enableRow,
                    hasSettings: $rows.length > 1
                };
            });

            return {
                methods: methods
            };
        }

        function buildMethodLinks($container, meta) {
            var $linksWrap = $container.find('.ppcart-payment-method-links');
            var existingViews = {};
            $linksWrap.find('.ppcart-payment-subtab-link').each(function() {
                existingViews[String($(this).data('payment-view') || '')] = true;
            });

            $.each(meta.methods, function(methodSlug, methodMeta) {
                if (!methodMeta.hasSettings) {
                    return;
                }

                var label = paymentMethodLabels[methodSlug] || ucfirst(methodSlug.replace(/_/g, ' '));
                var $link = $('<a/>', {
                    href: '#',
                    'class': 'ppcart-payment-subtab-link',
                    'text': label,
                    'data-payment-view': 'method:' + methodSlug
                });

                if (!existingViews['method:' + methodSlug]) {
                    $linksWrap.append($link);
                }
            });
        }

        function getAvailableViews(meta) {
            var views = ['enable'];
            $.each(meta.methods, function(methodSlug, methodMeta) {
                if (methodMeta.hasSettings) {
                    views.push('method:' + methodSlug);
                }
            });

            return views;
        }

        function applyPaymentView(view) {
            var $container = $('#content_tab_payment_methods');
            if (!$container.length) {
                return;
            }

            if (!paymentMeta) {
                paymentMeta = buildPaymentMeta($container);
                buildMethodLinks($container, paymentMeta);
            }

            var availableViews = getAvailableViews(paymentMeta);
            if (availableViews.indexOf(view) === -1) {
                view = 'enable';
            }

            setStoredPaymentView(view);
            updateUrlQueryForPaymentView(view);
            updatePaymentActionLinks(view);

            $container.find('.ppcart-payment-subtab-link').removeClass('is-active');
            $container.find('.ppcart-payment-subtab-link[data-payment-view="' + view + '"]').addClass('is-active');

            $.each(paymentMeta.methods, function(methodSlug, methodMeta) {
                methodMeta.heading.show();
                methodMeta.table.show();
                methodMeta.rows.show();
            });

            if (view === 'enable') {
                $.each(paymentMeta.methods, function(methodSlug, methodMeta) {
                    methodMeta.rows.hide();
                    methodMeta.enableRow.show();
                });
                return;
            }

            var selectedMethod = view.replace('method:', '');
            $.each(paymentMeta.methods, function(methodSlug, methodMeta) {
                if (methodSlug !== selectedMethod) {
                    methodMeta.heading.hide();
                    methodMeta.table.hide();
                    return;
                }

                methodMeta.rows.show();
                methodMeta.enableRow.hide();
            });
        }

        $(document).on('click', '#content_tab_payment_methods .ppcart-payment-subtab-link', function(event) {
            event.preventDefault();
            var view = $(this).data('payment-view');
            if (!view) {
                return;
            }

            applyPaymentView(view);
        });

        $(document).on('click', '#settings_tab_payment_methods', function() {
            window.setTimeout(function() {
                applyPaymentView(getStoredPaymentView());
            }, 0);
        });

        applyPaymentView(getStoredPaymentView());
    }

    jQuery('#content_tab_emails h2:not(:first)').addClass('email_title_trigger');
    jQuery('.email_title_trigger').next('table').hide();
    jQuery("#_ppcart_product_fresh_setup a").click(function(){
        var post_id     = $(this).data('id'),
            ajax_url 	= ppcart_reg_vars.ajax_url,
            wp_nonce	= ppcart_reg_vars.nonce;

        var data = {
            'action': 'ppcart_fresh_product',
            'nonce': wp_nonce,
            'post_id': post_id
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            //console.log(response);
            if( response =='success'){
                jQuery("#_ppcart_product_fresh_setup").remove();
            }
        }).fail(function() {
            alert(ppcart_translate_backend.try_again);
        });
    });

    $('#ppcart-email-type').change(function(){
        var email = $(this).val(),
            link = $('#ppcart-preview-email').attr('href').replace(/\[[a-z_]+\]/, '['+email+']');
        $('#ppcart-preview-email').attr('href',link);
    });

    $("#ppcart-email-send").click(function(){
        var wp_nonce    = ppcart_reg_vars.nonce,
            ajax_url    = ppcart_reg_vars.ajax_url,
            type        = $('#ppcart-email-type').val();

        var data = {
            'action': 'ppcart_send_email_test',
            'nonce': wp_nonce,
            'type': type
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            //console.log(response);
            if(response){
                alert(response);
            }
        }).fail(function() {
            alert(ppcart_translate_backend.try_again);
        });
        return false;
    });

    function getMergeTagTextarea($select) {
        var $textarea = $select.closest('.ppcart-editor-field').find('textarea.wp-editor-area, textarea.ppcart-deferred-editor').first();

        if (!$textarea.length) {
            $textarea = $select.closest('.ppcart-repeater').find('.ridmessage textarea.wp-editor-area, .ridmessage textarea.ppcart-deferred-editor').first();
        }

        return $textarea;
    }

    function insertTextIntoTextarea($textarea, text) {
        var textarea = $textarea.get(0);
        var value = $textarea.val() || '';
        var start;
        var end;

        if (!textarea) {
            return;
        }

        if (typeof textarea.selectionStart === 'number' && typeof textarea.selectionEnd === 'number') {
            start = textarea.selectionStart;
            end = textarea.selectionEnd;
            $textarea.val(value.substring(0, start) + text + value.substring(end));
            textarea.selectionStart = textarea.selectionEnd = start + text.length;
        } else {
            $textarea.val(value + text);
        }

        $textarea.trigger('input').trigger('change');
    }

    function addTextIntoEditor($select, text) {
        var $textarea = getMergeTagTextarea($select);
        var editorId;
        var editor;

        if ($textarea.length) {
            if ($textarea.hasClass('ppcart-deferred-editor') && !$textarea.data('ppcart-editor-initialized')) {
                $textarea.trigger('focusin');
            }

            editorId = $textarea.attr('id');
            editor = editorId && window.tinymce ? window.tinymce.get(editorId) : null;

            if (editor && (typeof editor.isHidden !== 'function' || !editor.isHidden())) {
                editor.execCommand('mceInsertContent', false, text);
                if (typeof editor.save === 'function') {
                    editor.save();
                }
                $textarea.trigger('input').trigger('change');
                return;
            }

            insertTextIntoTextarea($textarea, text);
            return;
        }

        if (window.tinymce && window.tinymce.activeEditor) {
            window.tinymce.activeEditor.execCommand('mceInsertContent', false, text);
        }
    }

    $(document).on('change', '.ppcart-insert-merge-tag', function(){
        if($(this).val() == '') {
            return;
        }

        var text = '{'+$(this).val()+'}';
        addTextIntoEditor($(this), text);
        $(this).val('');
    });

    // Copy to clipboard plan id
    $(document).on('click', '.ridoption_id .input-group .ppcart-plan-id-copy-icon', async function() {
        const $input = $(this).siblings('input');
        try {
            await navigator.clipboard.writeText($input.val());
            $(this).addClass('copied');
            $(this).removeClass('dashicons-admin-page').addClass('dashicons-yes');
            setTimeout(() => {
                $(this).removeClass('copied');
                $(this).removeClass('dashicons-yes').addClass('dashicons-admin-page');
            }, 1000);
        } catch (err) {
            // Fallback for older browsers
            $input[0].select();
            document.execCommand('copy');
            $(this).addClass('copied');
            $(this).removeClass('dashicons-admin-page').addClass('dashicons-yes');
            setTimeout(() => {
                $(this).removeClass('copied');
                $(this).removeClass('dashicons-yes').addClass('dashicons-admin-page');
            }, 1000);
        }
    });

    // Add copy icons to all plan ID fields
    $('.ridoption_id .input-group').each(function() {
        if (!$(this).find('.ppcart-plan-id-copy-icon').length) {
            const $copyIcon = $('<span class="dashicons dashicons-admin-page ppcart-plan-id-copy-icon"></span>');
            $(this).append($copyIcon);
        }
    });

    $('.ridoption_id input, .ridfield_id input, .ridurl_slug input').keyup(function(){
        var val = $(this).val().replace(/\s+/g, '-');
        $(this).val(val);
    });

    $('.ridfield_id input').keyup(function(){
        var val = $(this).val().replace(/[^a-zA-Z0-9\-_]/g, "").toLowerCase();
        $(this).val(val);
    });

    $('.default_field_disabled, .file_hide').each(function(){
        if($(this).is(':checked')) {
            $(this).closest('.ppcart-repeater').addClass('disabled');
        } else {
            $(this).closest('.ppcart-repeater').removeClass('disabled');
        }
    });

    $('.default_field_disabled, .file_hide').change(function(){
        if($(this).is(':checked')) {
            $(this).closest('.ppcart-repeater').addClass('disabled');
        } else {
            $(this).closest('.ppcart-repeater').removeClass('disabled');
        }
    });

    if(jQuery(".settings-options-form table").length) {
        var hash = window.location.hash;
        if(hash) {
            hash = hash.replace('#','');
            ppcart_settings(hash);
        } else {
            var defaultTab = 'general';
            try {
                if (window.localStorage) {
                    var storedTab = window.localStorage.getItem('ppcartSettingsMainTab');
                    if (storedTab && jQuery('#settings_tab_' + storedTab).length) {
                        defaultTab = storedTab;
                    }
                }
            } catch (e) {
                // Keep general tab fallback.
            }

            ppcart_settings(defaultTab);
        }

        initPaymentMethodSubtabs();
    }
    jQuery('.email_title_trigger').on('click',function(){
        if(!jQuery(this).hasClass('active')){
            jQuery('.email_title_trigger').removeClass('active');
            jQuery('.email_title_trigger').next('table').slideUp('slow');
        }
        jQuery(this).toggleClass('active');
        jQuery(this).next('table').slideToggle('slow');
    });
    // Keep the first payment plan ready to edit on both new and existing products.
    var $firstPaymentPlanEditButton = $( '#repeater_ppcart_pay_options > .ppcart-repeater:not(.hidden)' )
        .first()
        .children( '.handle' )
        .children( '.ppcart-btn-edit' );

    $( '.ppcart-repeaters .ppcart-btn-edit' ).not( $firstPaymentPlanEditButton ).click();

    $('.ppcart-color-field').wpColorPicker();
    flatpickr('.datepicker', {enableTime: true,dateFormat: "Y-m-d h:i K",allowInput: true});

    // hide amount recurring field if coupon type = fixed
    $("#repeater_ppcart_coupons [name^=\"_ppcart_coupons[type][\"]").each(function(index){
        if ( $(this).val() == "fixed" ) {
            $(this).closest(".ppcart-repeater-content").find(".ridamount_recurring").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400);
        } else {
            $(this).closest(".ppcart-repeater-content").find(".ridamount_recurring").hide()
        }

        if ( $(this).val() == "fixed" || $(this).val() == "percent" ) {
            $(this).closest(".ppcart-repeater-content").find(".ridduration").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400)
        } else {
            $(this).closest(".ppcart-repeater-content").find(".ridduration").hide()
        }
    });
    $("#repeater_ppcart_coupons [name^=\"_ppcart_coupons[type][\"]").on("change", function(){
        if ( $(this).val() == "fixed" ) {
            $(this).closest(".ppcart-repeater-content").find(".ridamount_recurring").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400);
        } else {
            $(this).closest(".ppcart-repeater-content").find(".ridamount_recurring").hide()
        }

        if ( $(this).val() == "fixed" || $(this).val() == "percent" ) {
            $(this).closest(".ppcart-repeater-content").find(".ridduration").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400)
        } else {
            $(this).closest(".ppcart-repeater-content").find(".ridduration").hide()
        }
    });

    $('.datepicker').change(function(){
        if($(this).val()) {
            $(this).next('.clear-date').show();
        } else {
            $(this).next('.clear-date').hide();
        }
    }).each(function(){
        if($(this).val()) {
            $(this).next('.clear-date').show();
        } else {
            $(this).next('.clear-date').hide();
        }
    });

    $('.datepicker + .clear-date').click(function(){
        $(this).hide().closest('.field-text').find('.datepicker').val('');
        return false;
    });

    $('.ppcart-settings-tabs .required').each(function(){
        $(this).closest('.wrap-field, .ppcart-field.ppcart-row ').find('label').append('<span class="req">*</span>')
    });

    $('.ppcart-tab').eq(0).css({'display':'flex',opacity: 1});
    $('.ppcart-tab-nav a').click(function(){

        var errors = false;
        $('.ppcart-settings-tabs .required:visible').each(function(){
            if($(this).hasClass("plugin-remove_button") && $(this).prev("select").val().length == 0){
                $(this).addClass('error');
                errors = true;
            }else if ($(this).val() == '' && !$(this).hasClass("plugin-remove_button")) {
                $(this).addClass('error');
                errors = true;
            }
        });

        if (errors) {
            alert('Required fields missing');
            return false;
        }

        $('.ppcart-tab-nav, .ppcart-tab').removeClass('active');
        $('.ppcart-tab').hide();
        $(this).parent().addClass('active');
        $($(this).attr('href')).css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400);
        return false;
    });

    // remove error message on keyup
    $('.ppcart-settings-tabs').on('keyup', '.required', function(){
        if ($(this).val() != '') {
            $(this).removeClass('error')
        }
    });
    $('.ppcart-settings-tabs').on('change', '.required', function(){
        if($(this).hasClass("multiple") && $(this).val().length > 0){
            $(this).parent().find(".multiple").removeClass('error');
        }else if ($(this).val() != '') {
            $(this).removeClass('error');
        }
    });

    jQuery('#_ppcart_currency, .form-table #_ppcart_country, #_ppcart_menu_icon').selectize({
        create: true,
        sortField: 'text'
    });

    $('.ppcart-selectize').each(function(){
        $(this).find('option[value=""]').remove();
        var hidden = $(this).closest('.ppcart-repeater.hidden');
        if(hidden.length < 1) {
            var def = $(this).data('placeholder');
            if($(this).hasClass('multiple')) {
                $(this).selectize({plugins: ['remove_button'],allowEmptyOption: true, placeholder: def});
            } else {
                $(this).selectize({allowEmptyOption: true, placeholder: def});
            }
        }
    });

    // mailchimp dropdowns
    if('undefined' !== typeof ppcart_mc_tags) {
        $('.mail_chimp_list_name').each(function(){
            var listId = $(this).val(),
                tags = ppcart_mc_tags[listId],
                $tag_options = $(this).closest('.ppcart-repeater').find('.mail_chimp_list_tags option'),
                $group_options = $(this).closest('.ppcart-repeater').find('.mail_chimp_list_groups option');

            $tag_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (tags === undefined) return;
                var tag_id = $(this).attr('value');
                if(tag_id in tags){
                    $(this).show();
                }
            });

            if('undefined' !== typeof ppcart_mc_groups) {
				var groups = ppcart_mc_groups[listId];
				$group_options.each(function(index){
					if ($(this).val()=='') return;

					$(this).hide();

					if (groups === undefined) return;
					var group_id = $(this).attr('value');
					if(group_id in groups){
						$(this).show();
					}
				});
			}

        });

        $('.mail_chimp_list_name').change(function(){
            var listId = $(this).val(),
                tags = ppcart_mc_tags[listId],
                groups = ppcart_mc_groups[listId],
                $tag_options = $(this).closest('.ppcart-repeater').find('.mail_chimp_list_tags option'),
                $group_options = $(this).closest('.ppcart-repeater').find('.mail_chimp_list_groups option');

            $tag_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (tags === undefined) return;
                var tag_id = $(this).attr('value');
                if(tag_id in tags){
                    $(this).show();
                }
            });

            $group_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (groups === undefined) return;
                var group_id = $(this).attr('value');
                if(group_id in groups){
                    $(this).show();
                }
            });

        });
    }

    var search_ppcart_user = null;
    jQuery(document).on('keyup', '.ppcart-user-search-custom', function(){

        let term = jQuery(this).find('input').val();

        let selectize_cont = jQuery(this).prev('select');

        let data = {
            'action': 'ppcart_json_search_user',
            'term':term,
            'nonce': ppcart_reg_vars.search_user_nonce,
        };

        search_ppcart_user = jQuery.ajax({
            type: 'GET',
            data: data,
            url: ppcart_reg_vars.ajax_url,
            beforeSend : function()    {
                if(search_ppcart_user != null) {
                    search_ppcart_user.abort();
                }
            },
            success: function(response) {
                let $select = selectize_cont.selectize();
                let selectize = $select[0].selectize;
                selectize.clearOptions();
                let terms = [];
                if ( response ) {
                    jQuery.each( response, function( id, text ) {
                        terms.push( { id: id, text: text } );
                        selectize.addOption({text: text, value: id});
                        selectize.refreshOptions();
                    });
                }
            },
            error:function(e){
              // Error
            }
        });
    });

	var lastRefundModalTrigger = null;

	function focusFirstRefundModalControl($modal) {
		window.setTimeout(function() {
			var focusTarget = $modal.find('input, select, textarea, button').filter(':visible').get(0);
			if (focusTarget && typeof focusTarget.focus === 'function') {
				focusTarget.focus();
			}
		}, 30);
	}

	function getRefundModalSettings($modal) {
		return {
			buttonTemplate: $modal.attr('data-ppcart-refund-button-template') || 'Refund {amount}',
			currencySymbol: $modal.attr('data-ppcart-refund-currency-symbol') || '',
			currencyPosition: $modal.attr('data-ppcart-refund-currency-position') || '',
			decimalSeparator: $modal.attr('data-ppcart-refund-decimal-separator') || '.',
			thousandSeparator: $modal.attr('data-ppcart-refund-thousand-separator') || '',
			decimalCount: parseInt($modal.attr('data-ppcart-refund-decimal-count'), 10)
		};
	}

	function normalizeRefundAmount(value, settings) {
		var normalized = String(value || '').trim();
		var decimalCount = isNaN(settings.decimalCount) ? 2 : settings.decimalCount;
		var alternateSeparator = settings.decimalSeparator === ',' ? '.' : ',';
		var alternateIndex;
		var alternateFraction;
		var decimalIndex;
		var amount;

		if (settings.currencySymbol) {
			normalized = normalized.split(settings.currencySymbol).join('');
		}

		normalized = normalized.replace(/\s/g, '').replace(/[^0-9,.\-]/g, '');

		if (!normalized) {
			return 0;
		}

		alternateIndex = normalized.lastIndexOf(alternateSeparator);
		if (normalized.indexOf(settings.decimalSeparator) === -1 && alternateIndex !== -1) {
			alternateFraction = normalized.substring(alternateIndex + 1).replace(/[^0-9]/g, '');
			if (alternateFraction.length > 0 && alternateFraction.length <= decimalCount) {
				normalized = normalized.substring(0, alternateIndex) + settings.decimalSeparator + alternateFraction;
			}
		}

		if (settings.thousandSeparator) {
			normalized = normalized.split(settings.thousandSeparator).join('');
		}

		if (settings.decimalSeparator && settings.decimalSeparator !== '.') {
			normalized = normalized.split(settings.decimalSeparator).join('.');
		}

		decimalIndex = normalized.lastIndexOf('.');
		if (decimalIndex !== -1) {
			normalized = normalized.substring(0, decimalIndex).replace(/[.,]/g, '') + '.' + normalized.substring(decimalIndex + 1).replace(/[.,]/g, '');
		} else {
			normalized = normalized.replace(/[.,]/g, '');
		}

		normalized = normalized.replace(/(?!^)-/g, '');
		amount = parseFloat(normalized);

		if (!isFinite(amount)) {
			return 0;
		}

		return Math.max(0, amount);
	}

	function addRefundThousandsSeparator(number, separator) {
		if (!separator) {
			return number;
		}

		return number.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
	}

	function formatRefundPrice(amount, settings) {
		var decimalCount = isNaN(settings.decimalCount) ? 2 : settings.decimalCount;
		var formattedAmount = parseFloat(amount).toFixed(decimalCount);
		var parts = formattedAmount.split('.');
		var number = addRefundThousandsSeparator(parts[0], settings.thousandSeparator);
		var price;

		if (decimalCount > 0) {
			number += settings.decimalSeparator + (parts[1] || '');
		}

		if (settings.currencyPosition === 'right' || settings.currencyPosition === 'right-space') {
			price = number;
			if (settings.currencyPosition === 'right-space') {
				price += ' ';
			}
			price += settings.currencySymbol;
			return price;
		}

		price = settings.currencySymbol;
		if (settings.currencyPosition === 'left-space') {
			price += ' ';
		}
		price += number;

		return price;
	}

	function updateRefundSubmitLabel($modal) {
		var settings;
		var amount;
		var price;
		var $amountInput;
		var $button;

		if (!$modal.length) {
			return;
		}

		$amountInput = $modal.find('#ppcart_refund_amount');
		$button = $modal.find('.ppcart-refund-submit');

		if (!$amountInput.length || !$button.length) {
			return;
		}

		settings = getRefundModalSettings($modal);
		amount = normalizeRefundAmount($amountInput.val(), settings);
		price = formatRefundPrice(amount, settings);

		$button.text(settings.buttonTemplate.replace('{amount}', price));
	}

	function closeRefundModal() {
		var $modal = $('.ppcart-refund-modal.is-open');
		if (!$modal.length) {
			return;
		}

		$modal.removeClass('is-open').attr('hidden', 'hidden');
		$('body').removeClass('ppcart-order-modal-open');

		if (lastRefundModalTrigger && document.contains(lastRefundModalTrigger)) {
			lastRefundModalTrigger.focus();
		}
		lastRefundModalTrigger = null;
	}

	function openRefundModal(trigger) {
		var $modal = $('.ppcart-refund-modal').first();

		if (!$modal.length) {
			$('.refund_amount_tr').show();
			return;
		}

		lastRefundModalTrigger = trigger || null;
		$modal.removeAttr('hidden').addClass('is-open');
		$('body').addClass('ppcart-order-modal-open');
		updateRefundSubmitLabel($modal);
		focusFirstRefundModalControl($modal);
	}

	$(document).on('click', '#ppcart_refund_items_btn', function(e){
		e.preventDefault();
		openRefundModal(this);
	});

	$(document).on('click', '[data-ppcart-refund-close]', function(e){
		e.preventDefault();
		closeRefundModal();
		$('.refund_amount_tr').hide();
	});

	$(document).on('keydown.ppCartRefundModal', function(e) {
		if (e.key === 'Escape') {
			closeRefundModal();
		}
	});

	$(document).on('input change', '.ppcart-refund-modal #ppcart_refund_amount', function() {
		updateRefundSubmitLabel($(this).closest('.ppcart-refund-modal'));
	});

	// show edit order fields
	if ( $('#edit-order, .ppcart-edit-order, #ppcart-order-details.ppcart-order-workspace').length > 0 ) {
		var $editTriggers = $('#edit-order, .ppcart-edit-order');
		var $normalSortables = $('#normal-sortables');
		var $editMetabox = $('#ppcart-edit-order-details');
		var $orderWorkspace = $('#ppcart-order-details.ppcart-order-workspace');
		var $editFields = $normalSortables.add('#edit-disabled');

		if ($orderWorkspace.length && $editMetabox.length) {
			$editMetabox.insertAfter($orderWorkspace.find('.ppcart-order-hero'));
			var $editHeader = $editMetabox.find('.postbox-header');
			var $editActions = $editMetabox.find('.ppcart-edit-savebar__actions');
			if ($editHeader.length && $editActions.length && !$editMetabox.find('.ppcart-edit-header-actions').length) {
				$editActions.addClass('ppcart-edit-header-actions').appendTo($editHeader);
				$editMetabox.addClass('ppcart-savebar-actions-moved');
			}
			$normalSortables.hide();
			$editFields = $editMetabox;
		}

		$editFields.hide();

		var orderStateAnimationMs = 240;
		var orderStateExitMs = 90;
		var orderStateTimer = null;
		var orderStateClassNames = 'ppcart-order-state-entering ppcart-order-state-exiting';

		function shouldReduceOrderStateMotion() {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		}

		function clearOrderStateTimers() {
			if (orderStateTimer) {
				window.clearTimeout(orderStateTimer);
				orderStateTimer = null;
			}
		}

		function cleanupOrderStateAnimation($shownFields, $hiddenFields) {
			$shownFields.removeClass(orderStateClassNames);
			$hiddenFields.removeClass(orderStateClassNames);
			$('body').removeClass('ppcart-order-state-is-transitioning');
		}

		function swapOrderEditState(showEdit) {
			var $idleFields = $('.edit-hide');

			clearOrderStateTimers();
			$idleFields.add($editFields).removeClass(orderStateClassNames);

			if (shouldReduceOrderStateMotion()) {
				$editFields.toggle(showEdit);
				$idleFields.toggle(!showEdit);
				$('body').toggleClass('ppcart-editing', showEdit);
				cleanupOrderStateAnimation($editFields, $idleFields);
				return;
			}

			$('body').addClass('ppcart-order-state-is-transitioning');

			if (showEdit) {
				$idleFields.hide();
				$editFields.show().addClass('ppcart-order-state-entering');
				$('body').addClass('ppcart-editing');

				orderStateTimer = window.setTimeout(function() {
					cleanupOrderStateAnimation($editFields, $idleFields);
					orderStateTimer = null;
				}, orderStateAnimationMs);
				return;
			}

			$editFields.addClass('ppcart-order-state-exiting');
			orderStateTimer = window.setTimeout(function() {
				$editFields.hide().removeClass(orderStateClassNames);
				$('body').removeClass('ppcart-editing');
				$idleFields.show();
				cleanupOrderStateAnimation($idleFields, $editFields);
				orderStateTimer = null;
			}, orderStateExitMs);
		}

		$editTriggers.click(function(){

			// show payment options for selected product
			find_pay_options();

			swapOrderEditState(true);

			return false;
		});

		$('.ppcart-edit-cancel').click(function(){
			swapOrderEditState(false);

			return false;
		});
	}

	$(document).on('click', '[data-ppcart-copy]', function(e) {
		e.preventDefault();

		var $button = $(this);
		var copyText = $button.attr('data-ppcart-copy');
		var originalText = $button.text();

		function setCopiedState() {
			$button.text('Copied');
			window.setTimeout(function() {
				$button.text(originalText);
			}, 1400);
		}

		function copyWithTextarea() {
			var $textarea = $('<textarea />').val(copyText).attr('readonly', 'readonly').css({
				left: '-9999px',
				position: 'absolute'
			});

			$('body').append($textarea);
			$textarea[0].select();
			document.execCommand('copy');
			$textarea.remove();
			setCopiedState();
		}

		if (window.navigator && window.navigator.clipboard && window.navigator.clipboard.writeText) {
			window.navigator.clipboard.writeText(copyText).then(setCopiedState).catch(copyWithTextarea);
			return;
		}

		copyWithTextarea();
	});

	//resend purchase confirmation email
	$('#resend-purchase-confirmation-email').on('click', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ppcart_resend_purchase_confirmation_email',
                order_id: orderId,
                nonce: ppcart_reg_vars.resend_purchase_confirmation_email_nonce
            },
            success: function(response) {
                alert(response.data);
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    if($('.ppcart-repeater-content .update-plan').length) {
        $('.ppcart-repeater-content .update-plan').each(function(){
            var val = $(this).attr('class').split(" ");
            $.each(val, function (k,e) {
                if(e.startsWith("ob-")) {
                    val = e.replace('ob-', '');
                }
                $(this).val(val);
                return;
            });
        });
    }

    $('.update-plan-product').on('change', function(){
        var post_id     = $(this).closest('.ppcart-tab, .ppcart-repeater').find('.update-plan-product').eq(0).val(),
            $select     = $(this).closest('.ppcart-tab, .ppcart-repeater').find('.update-plan').eq(0),
            ajax_url 	= ppcart_reg_vars.ajax_url,
            wp_nonce	= ppcart_reg_vars.nonce,
            selected    = $(this).val();

        $select.html('<option>...</option>');

        var data = {
            'action': 'ppcart_product_plans',
            'nonce': wp_nonce,
            'post_id': post_id,
            'selected' : selected
        };

        if($(this).hasClass('recurring')) {
            data.type = 'recurring'
        }

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            console.log(response);
            if( response ){
                $select.html(response);
            }
        }).fail(function() {
            alert(ppcart_translate_backend.try_again);
        });
    });

	//.ppcart_refund_btn
	//REFUND
	$(document).on('click', '.ppcart_refund_btn', function(e){

		e.preventDefault();
		var _this 		= $(this);
		var ajax_url 	= ppcart_reg_vars.ajax_url;
		var wp_nonce	= ppcart_reg_vars.nonce;
		var ordertId	= $("#post_ID").val();
        var amount      = $("#ppcart_refund_amount").val();
        if ($('#ppcart_restock_refunded').is(':checked')) {
		 	var restock = 'YES';
		} else {
		  	var restock = 'No';
		}

		var anchor_text = _this.text();

		var shouldProcessRefund = _this.closest('.ppcart-refund-modal').length ? true : confirm(ppcart_translate_backend.process_refund);

		//console.log( ajax_url );
		if ( shouldProcessRefund ) {
			_this.html("<strong> " + ppcart_translate_backend.wait + "</strong>");
			_this.addClass('disabled');
			_this.closest('.ppcart-refund-modal').find('button').prop('disabled', true);

			var data = {
				'action': 'ppcart_order_refund',
				'nonce': wp_nonce,
				'id': ordertId,
				'refund_amount': amount,
				'restock': restock,
			};

			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ajax_url, data, function(response) {
				console.log(response);
				if( response == 'OK' ){
					alert(ppcart_translate_backend.refund_success); //custom message
					location.reload();
				}else{
					_this.html(anchor_text);
					alert(response);
					_this.removeClass('disabled');
					_this.closest('.ppcart-refund-modal').find('button').prop('disabled', false);
				}
			}).fail(function() {
				_this.html(anchor_text);
				alert(ppcart_translate_backend.try_again);
				_this.removeClass('disabled');
				_this.closest('.ppcart-refund-modal').find('button').prop('disabled', false);
			});
		}
	});

    /**
         * Pause Start Subscription
         */
        $('.ppcart_pause_restart').click(function(){

            var confirmMessage = ppcart_translate_backend.confirm_pause_sub;
            var successMessage = ppcart_translate_backend.sub_paused;
            var type = $(this).data('action');

            if(type == 'started'){
                confirmMessage = ppcart_translate_backend.confirm_activate_sub;
                successMessage = ppcart_translate_backend.sub_started;
            }

            if (confirm(confirmMessage)) {
                var ajaxurl = ppcart_reg_vars.ajax_url;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce	= ppcart_reg_vars.nonce;
                var payment_method	= $("#ppcart_payment_method").val();

                var data = {
                    'action': 'ppcart_pause_restart_subscription',
                    'nonce': wp_nonce,
                    'id': id,
                    'payment_method': payment_method,
                    'type':type,
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                jQuery.post(ajaxurl, data, function(response) {
                    console.log(response);
                    if( response == 'OK' ){
                        alert(successMessage); //custom message
                        location.reload();
                    }else{
                            alert(response);
                    }
                }).fail(function() {
                    alert(response.fail);
                });
            } else {
                return false;
            }
    });

	var lastSubscriptionCancelTrigger = null;
	var subscriptionCancelRequestInFlight = false;

	function getSubscriptionCancelModal() {
		return $('.ppcart-subscription-cancel-modal').first();
	}

	function updateSubscriptionCancelRefundState() {
		var $modal = getSubscriptionCancelModal();
		var cancelTiming = $modal.find('input[name="ppcart_subscription_cancel_timing"]:checked').val();
		var disableRefund = cancelTiming === 'period_end';
		var $refundInput = $modal.find('input[name="ppcart_subscription_refund_action"][value="refund"]');

		$refundInput.prop('disabled', disableRefund);
		$refundInput.closest('label').toggleClass('is-disabled', disableRefund);
		if (disableRefund) {
			$modal.find('input[name="ppcart_subscription_refund_action"][value="no_refund"]').prop('checked', true);
		}
	}

	function openSubscriptionCancelModal(trigger) {
		var $modal = getSubscriptionCancelModal();
		var paymentMethod = $("#ppcart_payment_method").val();

		if (paymentMethod !== 'stripe' || !$modal.length) {
			return false;
		}

		lastSubscriptionCancelTrigger = trigger || null;
		$modal.removeAttr('hidden').addClass('is-open');
		$('body').addClass('ppcart-order-modal-open');
		updateSubscriptionCancelRefundState();
		window.setTimeout(function() {
			var focusTarget = $modal.find('input, select, textarea, button').filter(':visible').get(0);
			if (focusTarget && typeof focusTarget.focus === 'function') {
				focusTarget.focus();
			}
		}, 30);
		return true;
	}

	function closeSubscriptionCancelModal() {
		var $modal = getSubscriptionCancelModal();
		if (!$modal.length) {
			return;
		}
		if (subscriptionCancelRequestInFlight) {
			return;
		}

		$modal.removeClass('is-open').attr('hidden', 'hidden');
		$('body').removeClass('ppcart-order-modal-open');
		$modal.find('button').prop('disabled', false);
		$modal.find('.ppcart-subscription-cancel-submit').text(ppcart_translate_backend.cancel_subscription_button || 'Cancel subscription');

		if (lastSubscriptionCancelTrigger && document.contains(lastSubscriptionCancelTrigger)) {
			lastSubscriptionCancelTrigger.focus();
		}
		lastSubscriptionCancelTrigger = null;
	}

	function submitSubscriptionCancellation($button, options) {
		var ajax_url 	= ppcart_reg_vars.ajax_url;
		var wp_nonce	= ppcart_reg_vars.nonce;
		var ordertId	= $("#post_ID").val();
		var anchor_text = $button.text();
		var stripe_subscriber_id =  $("#stripe_ppcart_payment_intent").val();
		var payment_method	= $("#ppcart_payment_method").val();
		var prod_id = $("input[name='_ppcart_product_id']").val();
		var data;

		options = options || {};

		if( stripe_subscriber_id == "" || stripe_subscriber_id == undefined ){
			alert(ppcart_translate_backend.invalid_sub_id);
			return false;
		}
		if (subscriptionCancelRequestInFlight) {
			return false;
		}

		subscriptionCancelRequestInFlight = true;
		$button.html("<strong> " + ppcart_translate_backend.wait + "</strong>");
		$button.addClass('disabled');
		$button.closest('.ppcart-subscription-cancel-modal').find('button').prop('disabled', true);

		data = {
			'action': 'ppcart_unsubscribe_customer',
			'prod_id': prod_id,
			'subscription_id': stripe_subscriber_id,
			'nonce': wp_nonce,
			'id': ordertId,
			'payment_method': payment_method
		};

		if (payment_method === 'stripe') {
			data.cancel_timing = options.cancelTiming || '';
			data.refund_action = options.refundAction || 'no_refund';
		}

		jQuery.post(ajax_url, data, function(response) {
			var responseText = String(response || '');
			var refundFailurePrefix = 'OK_REFUND_FAILED|';
			var refundFailureMessage = '';

			console.log(response);
			if( responseText == 'OK' || responseText.indexOf(refundFailurePrefix) === 0 ){
				if (responseText.indexOf(refundFailurePrefix) === 0) {
					refundFailureMessage = responseText.substring(refundFailurePrefix.length);
					try {
						refundFailureMessage = decodeURIComponent(refundFailureMessage);
					} catch (error) {
						refundFailureMessage = '';
					}
					alert(
						(ppcart_translate_backend.sub_cancel_refund_failed || ppcart_translate_backend.sub_cancel) +
						(refundFailureMessage ? "\n\n" + refundFailureMessage : '')
					);
				} else if (options.cancelTiming === 'period_end') {
					alert(ppcart_translate_backend.sub_cancel_scheduled || ppcart_translate_backend.sub_cancel);
				} else {
					alert(ppcart_translate_backend.sub_cancel);
				}
				location.reload();
			}else{
				subscriptionCancelRequestInFlight = false;
				$button.html(anchor_text);
				alert(response);
				$button.removeClass('disabled');
				$button.closest('.ppcart-subscription-cancel-modal').find('button').prop('disabled', false);
			}
		}).fail(function() {
			subscriptionCancelRequestInFlight = false;
			$button.html(anchor_text);
			alert(ppcart_translate_backend.try_again);
			$button.removeClass('disabled');
			$button.closest('.ppcart-subscription-cancel-modal').find('button').prop('disabled', false);
		});
	}

	$(document).on('change', 'input[name="ppcart_subscription_cancel_timing"]', updateSubscriptionCancelRefundState);

	$(document).on('click', '[data-ppcart-subscription-cancel-close]', function(e){
		e.preventDefault();
		closeSubscriptionCancelModal();
	});

	$(document).on('keydown.ppCartSubscriptionCancelModal', function(e) {
		if (e.key === 'Escape' && getSubscriptionCancelModal().hasClass('is-open')) {
			closeSubscriptionCancelModal();
		}
	});

	$(document).on('click', '.ppcart-subscription-cancel-submit', function(e){
		var $modal = getSubscriptionCancelModal();
		e.preventDefault();
		submitSubscriptionCancellation($(this), {
			cancelTiming: $modal.find('input[name="ppcart_subscription_cancel_timing"]:checked').val(),
			refundAction: $modal.find('input[name="ppcart_subscription_refund_action"]:checked').val()
		});
	});

	//.ppcart_unsubscribe_btn
	//UNSUBSCRIBE
	$(document).on('click', '.ppcart_unsubscribe_btn', function(e){
		var payment_method	= $("#ppcart_payment_method").val();

		e.preventDefault();

		if (payment_method === 'stripe' && openSubscriptionCancelModal(this)) {
			return false;
		}

		if ( confirm(ppcart_translate_backend.confirm_cancel_sub) == true ) {
			submitSubscriptionCancellation($(this));
		}
	});

    // Sync with Stripe
    $(document).on('click', '.ppcart_sync_order', function(e) {
        e.preventDefault();
        var _this = $(this);
        var ajax_url = ppcart_reg_vars.ajax_url;
        var wp_nonce = ppcart_reg_vars.nonce;
        var order_id = _this.data('id');
        var anchor_text = _this.text();

        if (!ajax_url || !wp_nonce) {
            alert((window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_config_error) || ppcart_translate_backend.try_again);
            return;
        }
        if (!order_id) {
            alert((window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_missing_order_id) || ppcart_translate_backend.try_again);
            _this.html(anchor_text);
            _this.removeClass('disabled');
            return;
        }

        if (confirm(ppcart_reg_vars.confirm_sync_stripe || ppcart_translate_backend.confirm_sync_stripe || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_confirm) || ppcart_translate_backend.try_again)) {
            _this.html("<strong>" + (ppcart_reg_vars.wait || ppcart_translate_backend.wait || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_wait) || '') + "</strong>");
            _this.addClass('disabled');

            jQuery.post(ajax_url, {
                'action': 'ppcart_sync_order',
                'nonce': wp_nonce,
                'order_id': order_id
            }, function(response) {
                if (response.success) {
                    alert(response.data.message || ppcart_translate_backend.sub_sync_success || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_success) || ppcart_translate_backend.try_again);
                    location.reload();
                } else {
                    _this.html(anchor_text);
                    alert(response.data.message || ppcart_reg_vars.try_again || ppcart_translate_backend.try_again || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_try_again) || '');
                    _this.removeClass('disabled');
                }
            }).fail(function() {
                _this.html(anchor_text);
                alert(ppcart_reg_vars.try_again || ppcart_translate_backend.try_again || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_try_again) || '');
                _this.removeClass('disabled');
            });
        }
    });

    $(document).on('click', '.ppcart_sync_subscription', function(e) {
        e.preventDefault();
        var _this = $(this);
        var ajax_url = ppcart_reg_vars.ajax_url;
        var wp_nonce = ppcart_reg_vars.nonce;
        var subscription_id = _this.data('id'); // Stripe subscription ID (e.g., sub_1RTmD5CgxOWS1li1K4io5i60)
        var anchor_text = _this.text();

        console.log('Button data-id:', subscription_id);

        if (!ajax_url || !wp_nonce) {
            console.error('Missing AJAX configuration');
            alert((window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_config_error) || ppcart_translate_backend.try_again);
            return;
        }
        if (!subscription_id) {
            console.error('Missing subscription ID');
            alert((window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_missing_subscription_id) || ppcart_translate_backend.try_again);
            _this.html(anchor_text);
            _this.removeClass('disabled');
            return;
        }

        if (confirm(ppcart_reg_vars.confirm_sync_stripe || ppcart_translate_backend.confirm_sync_stripe || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_confirm) || ppcart_translate_backend.try_again)) {
            _this.html("<strong>" + (ppcart_reg_vars.wait || ppcart_translate_backend.wait || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_wait) || '') + "</strong>");
            _this.addClass('disabled');

            var data = {
                'action': 'ppcart_sync_subscription',
                'nonce': wp_nonce,
                'stripe_subscription_id': subscription_id
            };

            console.log('Sending AJAX request:', data);

            jQuery.post(ajax_url, data, function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    alert(response.data.message || ppcart_translate_backend.sub_sync_success || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_success) || ppcart_translate_backend.try_again);
                    location.reload();
                } else {
                    console.log('AJAX error response:', response);
                    _this.html(anchor_text);
                    alert(response.data.message || ppcart_reg_vars.try_again || ppcart_translate_backend.try_again || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_try_again) || '');
                    _this.removeClass('disabled');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX failed:', textStatus, errorThrown, jqXHR.responseText);
                _this.html(anchor_text);
                alert(ppcart_reg_vars.try_again || ppcart_translate_backend.try_again || (window.ppcart_admin_i18n && window.ppcart_admin_i18n.sync_try_again) || '');
                _this.removeClass('disabled');
            });
        }
    });

	//PAYMENT OPTIONS

	//dropdown product change
    $(document).on('change', '#_ppcart_product_id', function(){
        find_pay_options();
    });

    var find_pay_options = function(){
        var _pay_option_div = $("#rid_ppcart_item_name"),
            _pay_option = $("#_ppcart_item_name"),
            _this 		= $('#_ppcart_product_id'),
            _selected 	= _this.find("option:selected"),
            _selected_val  = _selected.val(),
            _selected_option  = _pay_option.val();

		if( _selected_val == undefined ||  _selected_val == null || _selected_val == '' || _selected_val < 1 ){
			return;
		}

		console.log( _selected_val );
		//AJAX REQUEST TO GET PAYMENT OPTIONS
		var ajax_url 	= ppcart_reg_vars.ajax_url;
		var wp_nonce	= ppcart_reg_vars.nonce;
		var data = {
			'action': 'ppcart_get_payment_options',
			'nonce': wp_nonce,
			'productId': _selected_val,
		};

		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajax_url, data, function(response) {
			console.log(response);
			if( response == 'error' ){
				_this.val('');
				_pay_option.val(0);
				alert(ppcart_translate_backend.something_went_wrong);
			}else if( response == 'no_data' || response == '' ){
				_pay_option.val(0);
			}else{
				_pay_option.html(response.data);
                if( _pay_option.find('option[value="'+_selected_option+'"]').length > 0 )
                    _pay_option.val(_selected_option);
			}
		}).fail(function() {
			_pay_option.val(0);
			alert(ppcart_translate_backend.try_again);
		});
	}


    $(document).on('click', '.ppcart-renew-lists', function(e){
		e.preventDefault();

        var _this 		= $(this);
		var ajax_url 	= ppcart_reg_vars.ajax_url;
		var wp_nonce	= ppcart_reg_vars.nonce;
		var anchor_text = _this.text();

        _this.html("<strong>" + ppcart_translate_backend.wait + "</strong>");
        _this.addClass('disabled');

        var data = {
            'action': 'ppcart_renew_integrations_lists',
            'nonce': wp_nonce,
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            console.log(response);
            if( response == 'OK' ){
                alert(ppcart_translate_backend.list_renewed); //custom message
                location.reload();
            }else{
                _this.html(anchor_text);
                alert(response);
                _this.removeClass('disabled');
            }
        }).fail(function() {
            _this.html(anchor_text);
            alert(ppcart_translate_backend.try_again);
            _this.removeClass('disabled');
        });
	});


	function isEmpty(str) {
		return (!str || 0 === str.length || undefined === str);
	}

    // coupon symbol toggle on amount off input
    $("[name^=\"_ppcart_coupons[type][\"]").each(function(){
        var $symbol = $(this).closest('.ppcart-repeater-content').find('.input-prepend,.input-append').eq(0);
        $(this).data('symbol', $symbol.text());
        $(this).data('class', $symbol.attr('class'));

        if($(this).val().includes("percent")) {
            $symbol.text('%').attr('class', 'input-append').next('input').addClass('right-currency');
        }
    });

    $(document).on('change', "[name^=\"_ppcart_coupons[type][\"]", function(){
        var $symbol = $(this).closest('.ppcart-repeater-content').find('.input-prepend,.input-append').eq(0);
        if($(this).val().includes("percent")) {
            $symbol.text('%').attr('class', 'input-append').next('input').addClass('right-currency');
        } else {
            $symbol.text($(this).data('symbol')).attr('class', $(this).data('class'));
            if($(this).data('class') == 'input-prepend') {
                $symbol.next('input').removeClass('right-currency');
            }
        }
    });

    $('input[type=checkbox][name^=_ppcart_upsell],input[type=checkbox][name^=_ppcart_downsell]').each(function(){
        if(!$(this).is(':checked')) {
            $(this).closest('.ppcart-tab').find('.ppcart-field').hide();
            $(this).closest('.ppcart-tab').find('.ppcart-field:first-child').show();
        }
    });
    $('input[type=checkbox][name^=_ppcart_upsell],input[type=checkbox][name^=_ppcart_downsell]').change(function(){
        if(!$(this).is(':checked')) {
            $(this).closest('.ppcart-tab').find('.ppcart-field:not(:first-child)').fadeOut();
            $(this).closest('.ppcart-tab').find('.ppcart-field:first-child').show();
        } else {
            $(this).closest('.ppcart-tab').find('.ppcart-field').fadeIn();
            var type = $(this).closest('.ppcart-tab').find('input[name^=_ppcart_us_prod_type],input[name^=_ppcart_ds_prod_type]').eq(0);
            if(type.is(':checked')) {
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').hide();
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').show();
            } else {
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').show();
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').hide();
            }
        }
    });

    $('input[name^=_ppcart_us_prod_type],input[name^=_ppcart_ds_prod_type]').each(function(){
        var enabled = $(this).closest('.ppcart-tab').find('input[type=checkbox][name^=_ppcart_upsell],input[type=checkbox][name^=_ppcart_downsell]').is(':checked');
        if($(this).is(':checked')) {
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').hide();
            if(enabled) {
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').show();
            }
        } else {
            if(enabled) {
                $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').show();
            }
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').hide();
        }
    });

    $('input[name^=_ppcart_us_prod_type],input[name^=_ppcart_ds_prod_type]').change(function(){
        if($(this).is(':checked')) {
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').hide();
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').show();
        } else {
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_price], [id^=rid_ppcart_us_price]').show();
            $(this).closest('.ppcart-tab').find('[id^=rid_ppcart_ds_plan], [id^=rid_ppcart_us_plan]').hide();
        }
    });

    $('.ridbump,.ridupsell').hide();
    $('.cinput-action').change(function(){
        var $row = $(this).closest('.wrap-fields');
        if($(this).val()=='') {
            $row.find('.wrap-field').not('.ridaction').hide();
        } else {
            $row.find('.ridproduct_type').show();
            $row.find('.cinput-product_type').each(function(){
                if(!$(this).is(":hidden")){
                    var $parent = $(this).closest('.condition'),
                        $plans = $parent.find('.ridplan'),
                        $bumps = $parent.find('.ridbump'),
                        $upsells = $parent.find('.ridupsell');

                    if($(this).val()=='plan') {
                        $bumps.hide();
                        $upsells.hide();
                        $plans.fadeIn(300);
                    } else if($(this).val()=='bump') {
                        $bumps.fadeIn(300);
                        $upsells.hide();
                        $plans.hide();
                    } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
                        $bumps.hide();
                        $upsells.fadeIn(300);
                        $plans.hide();
                    } else {
                        $bumps.hide();
                        $upsells.hide();
                        $plans.hide();
                    }
                }
            });
        }
    });

    // Conditional confirmations

    $('.condition-content .cinput-product_type').each(function(){
        var $parent = $(this).closest('.condition'),
            $plans = $parent.find('.ridplan'),
            $bumps = $parent.find('.ridbump'),
            $upsells = $parent.find('.ridupsell');

        if($(this).val()=='plan') {
            $bumps.hide();
            $upsells.hide();
            $plans.fadeIn(300);
        } else if($(this).val()=='bump') {
            $bumps.fadeIn(300);
            $upsells.hide();
            $plans.hide();
        } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
            $bumps.hide();
            $upsells.fadeIn(300);
            $plans.hide();
        } else {
            $bumps.hide();
            $upsells.hide();
            $plans.hide();
        }
    });

    $(document).on('change', '.condition-content .cinput-product_type', function(){
        var $parent = $(this).closest('.condition'),
            $plans = $parent.find('.ridplan'),
            $bumps = $parent.find('.ridbump'),
            $upsells = $parent.find('.ridupsell');

        if($(this).val()=='plan') {
            $bumps.hide();
            $upsells.hide();
            $plans.fadeIn(300);
        } else if($(this).val()=='bump') {
            $bumps.fadeIn(300);
            $upsells.hide();
            $plans.hide();
        } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
            $bumps.hide();
            $upsells.fadeIn(300);
            $plans.hide();
        } else {
            $bumps.hide();
            $upsells.hide();
            $plans.hide();
        }
    });

    $('.ridcfield_value input').each(function(){
        var value = $(this).closest('.condition-content').find('.ridcfield select').val();
        var descriptions = $(this).next('.description').find('span');
        descriptions.hide();

        if(value=='country') {
            $(this).parent().find('.description .country').show();
        } else if(value=='state') {
            $(this).parent().find('.description .state').show();
        }
    });
    $(document).on('change', '.ridcfield select', function(){
        var value = $(this).val();
        var descriptions = $(this).closest('.condition-content').find('.ridcfield_value .description span');
        descriptions.hide();

        if(value=='country' || value=='state') {
            $(this).closest('.condition-content').find('.ridcfield_value .description span.'+value).show();
        }
    });

    $('select.condition-type').each(function(){
        var rows = $(this).closest('.ppcart-repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='and') {
            rows.removeClass('condition-type-or').addClass('condition-type-and');
        } else {
            rows.removeClass('condition-type-and').addClass('condition-type-or');
        }
    });
    $(document).on('change', '.condition-type', function(){
        var rows = $(this).closest('.ppcart-repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='and') {
            rows.removeClass('condition-type-or').addClass('condition-type-and').fadeIn();
        } else {
            rows.removeClass('condition-type-and').addClass('condition-type-or').fadeIn();
        }
    });

    $('.cinput-action').each(function(){
        var rows = $(this).closest('.ppcart-repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='') {
            $(this).closest('.wrap-fields').find('.wrap-field').not('.ridaction').hide();
        }
    });

    $('.condition-content .cinput-action').each(function(){
        var $parent = $(this).closest('.condition'),
            $fields = $parent.find('[class^=ridcfield]'),
            $plans = $parent.find('.ridplan'),
            $type = $parent.find('.ridproduct_type');
        if($(this).val()=='field-value') {
            $type.hide().find('.cinput-product_type').val('plan');
            $fields.show();
            $plans.hide();
        } else {
            $fields.hide();
            $type.show();
        }
    });

    $(document).on('change', '.condition-content .cinput-action', function(){
        var $parent = $(this).closest('.condition'),
            $fields = $parent.find('[class^=ridcfield]'),
            $plans = $parent.find('.ridplan'),
            $type = $parent.find('.ridproduct_type');
        if($(this).val()=='field-value') {
            $type.hide().find('.cinput-product_type').val('plan').trigger('change');
            $fields.show();
            $plans.hide();
        } else {
            $fields.hide();
            $type.show();
        }
    });

    $(document).on('change','#_ppcart_vat_enable',function(){
        if(this.checked){
            $('#_ppcart_vat_reverse_charge').parents('tr').fadeIn(100);
            $('#_ppcart_vat_merchant_state').parents('tr').fadeIn(100);
            $('#_ppcart_vat_all_eu_businesses').parents('tr').fadeIn(200);
            $('#_ppcart_vat_disable_vies_database_lookup').parents('tr').fadeIn(300);
        }else{
            $('#_ppcart_vat_reverse_charge').parents('tr').fadeOut(300);
            $('#_ppcart_vat_merchant_state').parents('tr').fadeOut(300);
            $('#_ppcart_vat_all_eu_businesses').parents('tr').fadeOut(200);
            $('#_ppcart_vat_disable_vies_database_lookup').parents('tr').fadeOut(100);
        }
    });

    $('#_ppcart_vat_enable').trigger('change');

    $(document).on('change', '[data-ppcart-toggle-encrypt-secrets]', function () {
        var $input = $(this);
        var ajaxUrl = (window.ppcart_reg_vars && ppcart_reg_vars.ajax_url) || window.ajaxurl || '/wp-admin/admin-ajax.php';
        var enabled = $input.is(':checked') ? '1' : '0';
        var i18n = window.ppcart_admin_i18n || {};

        $input.prop('disabled', true);

        $.post(ajaxUrl, {
            action: 'ppcart_set_encrypt_secrets',
            nonce: $input.attr('data-nonce'),
            enabled: enabled
        }).done(function (response) {
            if (response && response.success) {
                if (response.data && response.data.message) {
                    window.ppCartAdminDialog.alert({
                        title: i18n.encrypt_updated_title || 'Encryption updated',
                        message: response.data.message,
                        okText: i18n.ok || 'OK',
                        onClose: function () {
                            window.location.reload();
                        }
                    });
                    return;
                }

                window.location.reload();
                return;
            }

            var message = response && response.data && response.data.message
                ? response.data.message
                : (i18n.encrypt_update_failed || 'Unable to update security encryption setting.');
            window.ppCartAdminDialog.alert({
                title: i18n.encrypt_updated_title || 'Encryption updated',
                message: message,
                okText: i18n.ok || 'OK'
            });
            $input.prop('checked', !$input.is(':checked'));
        }).fail(function () {
            window.ppCartAdminDialog.alert({
                title: i18n.encrypt_updated_title || 'Encryption updated',
                message: i18n.encrypt_update_failed || 'Unable to update security encryption setting.',
                okText: i18n.ok || 'OK'
            });
            $input.prop('checked', !$input.is(':checked'));
        }).always(function () {
            $input.prop('disabled', false);
        });
    });
});

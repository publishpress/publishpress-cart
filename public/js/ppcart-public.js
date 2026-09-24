(function($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
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
    var regExPhone = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/,
        regExEmail = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    function ppcart_parse_json_response(response) {
        var parsed = response;

        if (response && typeof response === 'object') {
            parsed = response;
        } else if (typeof response === 'string') {
            try {
                parsed = JSON.parse(response);
            } catch (e) {
                return { error: response };
            }
        } else {
            return {};
        }

        if (parsed && parsed.success === false && parsed.data && parsed.data.error) {
            return {
                error: parsed.data.error,
                fields: parsed.data.fields,
            };
        }

        return parsed;
    }

    $('.ppcart-open-modal').click(function (e) {
        e.preventDefault();
        var modal = $(this).data('item-id');
        $('#' + modal).addClass('opened');
    });

    $('.closemodal').click(function (e) {
        e.preventDefault();
        var el = $(this).closest('.opened');
        el.removeClass('opened');
    });

    function isValidPhonenumber(value) {
        return (/^\d{7,}$/).test(value.replace(/[\s()+\-\.]|ext/gi, ''));
    }

    function isValidPassword(value) {
        var lowerCaseLetters = /[a-z]/g;
        if (!value.match(lowerCaseLetters)) {
            return false;
        }

        // Validate numbers
        var numbers = /[0-9]/g;
        if (!value.match(numbers)) {
            return false;
        }

        // Validate length
        if (!value.length >= 8) {
            return false;
        }
        return true;
    }

    $(document).ready(function() {

        var originalText;
        var stripeCardComplete = {};
        var checkoutIntentState = {};
        // Payment Element mode keeps a mounted Elements group and its bound intent per form.
        var paymentElement = {};
        var paymentElements = {};

        function getFormWrapperFromElement(element) {
            return '#' + $(element).closest('form').parent().attr('id');
        }

        function getCheckoutIntentState(formId) {
            formId = formId.replace('#', '');

            if ('undefined' === typeof checkoutIntentState[formId]) {
                checkoutIntentState[formId] = {
                    intent: null,
                    fingerprint: '',
                    pending: null,
                    pendingRequest: null,
                    pendingFingerprint: '',
                    preloadTimer: null
                };
            }

            return checkoutIntentState[formId];
        }

        function abortPendingPaymentIntent(formId) {
            var state = getCheckoutIntentState(formId);

            if (state.pendingRequest && state.pendingRequest.readyState !== 4) {
                state.pendingRequest.abort();
            }

            state.pending = null;
            state.pendingRequest = null;
            state.pendingFingerprint = '';
        }

        function clearPreparedPaymentIntent(formId) {
            var state = getCheckoutIntentState(formId);

            state.intent = null;
            state.fingerprint = '';
            abortPendingPaymentIntent(formId);
        }

        function buildPaymentIntentFingerprint(form, ignoredFields) {
            var values = [];
            ignoredFields = ignoredFields || [];

            $.each($('#ppcart-payment-form', form).serializeArray(), function(_, kv) {
                if (kv.name == 'action' || ignoredFields.indexOf(kv.name) !== -1) {
                    return;
                }

                values.push(kv.name + '=' + kv.value);
            });

            return values.sort().join('&');
        }

        function isSubscriptionCheckout(form) {
            return $('.ob-sub:checked', form).length || ($('input[name="ppcart_product_option"]:checked', form).data('installments') && !$('.ob-replace:checked', form).length);
        }


        function ppcartAppendCheckoutCompletionFields(form, orderId, accessToken) {
            if (!form || !orderId) {
                return;
            }

            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'ppcart_order_id');
            hiddenInput.setAttribute('value', orderId);
            form.appendChild(hiddenInput);

            if (accessToken) {
                var accessInput = document.createElement('input');
                accessInput.setAttribute('type', 'hidden');
                accessInput.setAttribute('name', 'ppcart-access');
                accessInput.setAttribute('value', accessToken);
                form.appendChild(accessInput);
            }
        }

        function getCheckoutAmount(form) {
            var amount = parseFloat($('[name="ppcart_amount"]', form).val());

            if (isNaN(amount) || amount <= 0) {
                amount = parseFloat($('input[name="ppcart_product_option"]:checked', form).data('price'));
            }

            return isNaN(amount) ? 0 : amount;
        }

        function isPreloadEligible(form, formId) {
            var method = $('[name="pay-method"]:checked', form).val();
            var amount = getCheckoutAmount(form);

            // Hosted Checkout redirects to Stripe; no local PaymentIntent to preload.
            if (ppcart.is_hosted_checkout) {
                return false;
            }

            // Payment Element mounts its own intent; the card preload path is unused here.
            if (ppcart.is_payment_element) {
                return false;
            }

            if (method && method != 'stripe') {
                return false;
            }

            if (!hasVisibleStripeCard(form) || !stripeCardComplete[formId]) {
                return false;
            }

            if (isSubscriptionCheckout(form) || isNaN(amount) || amount <= 0) {
                return false;
            }

            if (hasIncompleteRequiredField(form)) {
                return false;
            }

            return true;
        }

        function storePaymentIntentState(formId, response, fingerprint) {
            var state = getCheckoutIntentState(formId);

            state.intent = {
                'clientSecret': response.clientSecret,
                'intent_id': response.intent_id,
                'customer_id': response.customer_id,
                'amount': response.amount,
                'prod_id': response.prod_id,
                'ppcart_temp_order_id': response.ppcart_temp_order_id,
                'ppcart_temp_order_token': response.ppcart_temp_order_token,
                'formAction': response.formAction,
                'directConfirmation': response.directConfirmation,
                'ppcart_access': response.ppcart_access,
                'preloadedIntent': response.preloadedIntent
            };
            state.fingerprint = fingerprint;

            clientSecret = response.clientSecret;
            intent_id = response.intent_id;
            customer_id = response.customer_id;
            amount = response.amount;
            ppcart_temp_order_id = response.ppcart_temp_order_id;
            ppcart_temp_order_token = response.ppcart_temp_order_token;
            prod_id = response.prod_id;
            formAction = response.formAction;
            directConfirmation = response.directConfirmation;

            return state.intent;
        }

        function getPreparedPaymentIntent(formId, fingerprint) {
            var state = getCheckoutIntentState(formId);

            if (state.intent && state.fingerprint === fingerprint) {
                return state.intent;
            }

            return false;
        }

        async function preloadPaymentIntent(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;

            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            if (!isPreloadEligible(form, formId)) {
                clearPreparedPaymentIntent(formId);
                return false;
            }

            var errors = await ppcart_validate(formId, false);
            if (errors) {
                clearPreparedPaymentIntent(formId);
                return false;
            }

            var fingerprint = buildPaymentIntentFingerprint(form);
            if (state.intent && state.fingerprint === fingerprint) {
                return state.intent;
            }

            if (state.pending && state.pendingFingerprint === fingerprint) {
                return state.pending;
            }

            if (state.pending) {
                abortPendingPaymentIntent(formId);
            }

            var paramObj = $('#ppcart-payment-form', form).serializeArray();
            paramObj.push(
                { name: "action", value: "ppcart_create_payment_intent" },
                { name: "ppcart_preload_intent", value: "1" }
            );

            var request = $.post(ppcart.ajax, paramObj);
            state.pendingFingerprint = fingerprint;
            state.pendingRequest = request;
            state.pending = request.then(function(res) {
                var response = ppcart_parse_json_response(res);

                if ('undefined' !== typeof response.error || 'undefined' !== typeof response.redirect || 'undefined' === typeof response.clientSecret) {
                    return false;
                }

                if (buildPaymentIntentFingerprint(form) !== fingerprint || !isPreloadEligible(form, formId)) {
                    return false;
                }

                return storePaymentIntentState(formId, response, fingerprint);
            }, function() {
                return false;
            });

            state.pending.always(function() {
                if (state.pendingFingerprint === fingerprint) {
                    state.pending = null;
                    state.pendingRequest = null;
                    state.pendingFingerprint = '';
                }
            });

            return state.pending;
        }

        function schedulePaymentIntentPreload(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;

            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            clearTimeout(state.preloadTimer);
            state.preloadTimer = setTimeout(function() {
                preloadPaymentIntent(form);
            }, 700);
        }

        function hasVisibleStripeCard(form) {
            var method = $('[name="pay-method"]:checked', form).val();

            if (method && method !== 'stripe') {
                return false;
            }

            return $('.ppcart-stripe #card-element', form).length > 0 && $('.ppcart-stripe', form).is(':visible');
        }

        function hasIncompleteRequiredField(form) {
            var incomplete = false;
            var fields = $(form + ' input.required:visible, ' + form + ' select.required:visible, ' + form + ' select.required.selectized');

            fields.each(function() {
                var $field = $(this);
                var type = $field.attr('type');
                var name = $field.attr('name');

                if (type === 'checkbox') {
                    if (name && name.endsWith('[]')) {
                        if ($(form + ' input[name="' + name + '"]:checked').length < 1) {
                            incomplete = true;
                        }
                    } else if (!$field.is(':checked')) {
                        incomplete = true;
                    }
                } else if (type === 'radio') {
                    if (name && $(form + ' input[name="' + name + '"]:checked').length < 1) {
                        incomplete = true;
                    }
                } else if (!$field.val()) {
                    incomplete = true;
                }
            });

            if ($(form + ' input.invalid:visible, ' + form + ' select.invalid:visible, ' + form + ' .selectize-input.invalid:visible').length > 0) {
                incomplete = true;
            }

            return incomplete;
        }

        function updateSubmitButtonState(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;

            var $button = $('#ppcart_card_button', form);
            if (!$button.length || $button.hasClass('running') || !$button.is(':visible')) {
                return;
            }

            var formId = form.replace('#', '');
            var isReady = !hasIncompleteRequiredField(form);

            if (isReady && hasVisibleStripeCard(form)) {
                isReady = !!stripeCardComplete[formId];
            }

            $button.prop('disabled', !isReady);
        }

        function togglePayMethods(form) {
            var method = $('[name="pay-method"]:checked', form).val();

            if (method !== 'stripe') {
                $('.ppcart-stripe', form).fadeOut();
                $('#paypal-button-container', form).fadeIn();
            } else {
                $('.ppcart-stripe', form).fadeIn();
                $('#paypal-button-container', form).fadeOut();
            }
        }

        $(document).on('change', 'input[name="pay-method"]', function() {
            var form = '#' + $(this).closest('form').parent().attr('id');
            togglePayMethods(form);
            clearPreparedPaymentIntent(form);
            updateSubmitButtonState(form);
            schedulePaymentIntentPreload(form);
            // Payment Element: a stripe method needs a mounted element; remount if switched to stripe.
            if (ppcart.is_payment_element && $('[name="pay-method"]:checked', form).val() === 'stripe') {
                schedulePaymentElementRemount(form);
            }
        });

        $(document).on('input change blur', '#ppcart-payment-form input, #ppcart-payment-form select, #ppcart-payment-form textarea', function() {
            var form = getFormWrapperFromElement(this);
            clearPreparedPaymentIntent(form);
            updateSubmitButtonState(form);
            schedulePaymentIntentPreload(form);
        });

        $(document).on('change', 'input[name=ppcart_product_option]', function() {
            var form = '#' + $(this).closest('form').parent().attr('id');
            update_pwyw(form);
            // Plan switch can change intent type/amount; remount the element.
            if (ppcart.is_payment_element) {
                schedulePaymentElementRemount(form);
            }
        });

        function update_pwyw(form) {
            var $selected = $('input[name=ppcart_product_option]:checked', form);
            var dataPrice = $selected.attr('data-price');
            var id = $selected.attr('id');
            var value = $selected.val();

            $('.pwyw-input', form).fadeOut();

            if ($('#' + id, form).siblings('.item-name').find('.pwyw-suggested').length) {
                $('#' + id, form).data('price', dataPrice);
                $('#pwyw-amount-input-' + value, form).val(dataPrice);
                $('#pwyw-input-block-' + value, form).fadeIn().focus();
            } else {
                $('#pwyw-input-block-' + value, form).fadeOut();
            }
        }

        var update_form = async function(wrap_id){

            wrap_id = wrap_id.replace('#', '');

            $(".ppcart-stripe-express",'#'+wrap_id).hide();

            var $item = $('input[name=ppcart_product_option]:checked', '#'+wrap_id);
            if(!$item.closest('.item').hasClass('ppcart-selected')) {
                $('input[name=ppcart_product_option]', '#'+wrap_id).each(function(){
                    $(this).closest('.item').removeClass('ppcart-selected');
                });
                $item.closest('.item').addClass('ppcart-selected');
            }

            let form = document.getElementById(wrap_id);
            form = form.getElementsByTagName('form')[0];

            var paramObj = new FormData(form);
            paramObj.set("action", "ppcart_update_cart_amount");

            if (typeof ppcart_tax_settings !== 'undefined' && $('select[name="country"]', '#'+wrap_id).val() != '') {
                //$('#ppcart_card_button').addClass('running').attr("disabled", true);
                var tax_obj = {},
                    tax_rate_data = {};
                tax_obj.country = $('select[name="country"]', '#'+wrap_id).val() ? $('select[name="country"]', '#'+wrap_id).val() : "";
                tax_obj.city = $('input[name="city"]', '#'+wrap_id).val() ? $('input[name="city"]', '#'+wrap_id).val() : "";
                tax_obj.state = $('select[name="state"]', '#'+wrap_id).val() ? $('select[name="state"]', '#'+wrap_id).val() : "";
                tax_obj.zip = $('input[name="zip"]', '#'+wrap_id).val() ? $('input[name="zip"]', '#'+wrap_id).val() : "";
                tax_obj.vat_number = $('input[name="vat-number"]', '#'+wrap_id).val() ? $('input[name="vat-number"]', '#'+wrap_id).val() : "";
                tax_obj.nonce = ppcart_tax_settings.nonce;
                tax_obj.action = 'get_match_tax_rate';
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: ppcart.ajax,
                    data: tax_obj,
                    success: function(response) {
                        tax_rate_data = response.rates;
                        $('.vat_container', '#'+wrap_id).hide();
                        let vat_error = "Invalid VAT Number";
                        $('#vat_number', '#'+wrap_id).removeClass('valid invalid');
                        $('#vat_number', '#'+wrap_id).parent().find('.error').remove();
                        if (!response.is_valid_vat) {
                            $('#vat_number', '#'+wrap_id).addClass('invalid');
                            if (response.vat_error != "") {
                                vat_error = response.vat_error;
                            }
                            if ($('#vat_number', '#'+wrap_id).parent().find('.error').length == 0) {
                                $('#vat_number', '#'+wrap_id).parent().append('<div class="error">' + vat_error + '</div>')
                            } else {
                                $('#vat_number', '#'+wrap_id).parent().find('.error').html(vat_error);
                            }
                        } else if(tax_obj.vat_number != '') {
                            $('#vat_number', '#'+wrap_id).addClass('valid');
                        }
                        if (response.is_vat) {
                            $('.vat_container', '#'+wrap_id).show();
                        }

                    }
                });
            }

            try {
				const response = await fetch(ppcart.ajax, {
					method: 'POST',
					body:paramObj
				});
				if (response.ok) {
					const resp = await response.json();
					if (resp.order_summary_items) {

                        var container = $('#'+wrap_id).closest("#ppcart-form-container").find('.summary-items'),
                            totalContainer = $('#'+wrap_id).closest("#ppcart-form-container").find('.total');

                        container.empty();
                        totalContainer.empty();

                        $('input[name="ppcart_amount"]', '#'+wrap_id ).val(resp.total);

                        if (resp.total == 0 && typeof resp.sub_summary == 'undefined') {
                            $('.pay-info', '#'+wrap_id).hide();
                        } else {
                            $('.pay-info', '#'+wrap_id).show();
                        }

                        if(resp.total != 0){
                            $(".ppcart-stripe-express",'#'+wrap_id).show();
                        }

						if(resp.order_summary_items.length === 0) {
                            $('.ppcart-stripe-express', '#'+wrap_id).hide();
                            $('#ppcart_card_button', '#'+wrap_id).hide();
                            container.append(
                                $('<div class="item">').append(
                                    $('<span class="ppcart-label">').text(resp.empty)
                                )
                            );
                        } else {
                            $('#ppcart_card_button', '#'+wrap_id).show();
                            resp.order_summary_items.forEach(item => {
                                const itemType = typeof item.type === 'string' ? item.type : '';
                                const $row = $('<div class="item">');
                                if (/^[a-z0-9_-]+$/.test(itemType)) {
                                    $row.addClass(itemType);
                                }

                                const qty = Number(item.quantity);
                                const $label = $('<span class="ppcart-label">');
                                if (item.coupon_id) {
                                    const code = String(item.coupon_id);
                                    let baseText = String(item.name);
                                    if (baseText.endsWith(code)) {
                                        baseText = baseText.slice(0, -code.length);
                                    }
                                    if (qty > 1) {
                                        baseText += ' x ' + qty;
                                    }
                                    $label.text(baseText);
                                    $label.append($('<span class="ppcart-badge">').text(code));
                                } else {
                                    let labelText = String(item.name);
                                    if (qty > 1) {
                                        labelText += ' x ' + qty;
                                    }
                                    $label.text(labelText);
                                }

                                $row.append($label);
                                $row.append($('<span class="price">').text(item.subtotal));
                                container.append($row);
                            });

                            const $totalRhs = $('<div class="total-rhs">').append(
                                $('<span class="price">').text(resp.total_price)
                            );
                            totalContainer.append(
                                $('<span class="ppcart-total-label">').text(resp.total_label)
                            );
                            totalContainer.append($totalRhs);

                            if (typeof resp.sub_summary != 'undefined') {
                                const $small = $('<small>');
                                String(resp.sub_summary).split('\n').forEach((line, index) => {
                                    if (index > 0) {
                                        $small.append(document.createElement('br'));
                                    }
                                    $small.append(document.createTextNode(line));
                                });
                                $totalRhs.append($small);
                            }
                        }
					}
                    initialExpressPayment('#'+wrap_id);
                    updateSubmitButtonState('#'+wrap_id);
                    clearPreparedPaymentIntent(wrap_id);
                    schedulePaymentIntentPreload('#'+wrap_id);
					return resp.success;
				} else {
					alert('Something went wrong.')
				}
			} catch (error) {
				alert('Something went wrong.')
			}
            return false;

        };

        $(document).on('click', '.qty-dec, .qty-inc', function(e) {
            e.preventDefault();
            var input = $(this).closest('.my-4').find('input'),
                val = input.val(),
                form = $(this).closest('form').parent().attr('id');

            if($(this).hasClass('qty-dec') && parseInt(val) > 0) {
                input.val(parseInt(val)-1);
                update_form(form);
            } else if($(this).hasClass('qty-inc')) {
                input.val(parseInt(val)+1);
                update_form(form);
            }
            return false;
        });

        $(document).on('change', 'input[name^="ppcart-orderbump["]', function() {
            var form = $(this).closest('form').parent().attr('id');

            update_form(form);

            if (ppcart.is_payment_element) {
                // Amount-only change: sync the mounted intent instead of remounting.
                schedulePaymentElementAmountSync(form);
            }
        });

        /* Change Vat Customer Type */
        $(document).on('change', 'input[name="vat-number-available"]', function() {
            if (this.checked) {
                $('.vat_number_field').fadeIn(0);
            } else {
                $('.vat_number_field').fadeOut(0);
            }
        });

        // remove error message on focus
        $(document).on('focus', '#ppcart-payment-form input:not([type="checkbox"]):not([type="radio"]), #ppcart-payment-form select', function() {
            $(this).removeClass('invalid valid').siblings('.error').remove();
            $(this).closest('.ppcart-form-group').find('input').removeClass('invalid')
            $(this).closest('.ppcart-form-group').find('.error').remove()

            if ($(this).attr('id') == 'address1') {
                $('.ppcart-address-2 .error').remove();
            }

            if ($(this).hasClass('selectized')) {
                $(this).next('.selectize-control').find('.selectize-input').removeClass('invalid');
            }
        });

        function isProductSingular() {
            var selector = (typeof ppcart !== 'undefined' && ppcart.product_singular_selector)
                ? ppcart.product_singular_selector
                : '.single-ppcart_product';
            return $(selector).length > 0;
        }

        function invalidate_field($field, message) {
            $field.removeClass('invalid').siblings('.error').remove();

            if (($field.attr('type') == 'checkbox' && !$field.is(':checked'))) {
                $field.closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + message + '</div>');
            } else {
                var $el = $field;
                if ($field.attr('id') == 'address1' && isProductSingular()) {
                    $el = $('#ppcart-payment-form #address2');
                }
                $field.addClass('invalid');
                $el.closest('.ppcart-form-group').append('<div class="error">' + message + '</div>');
                if ($field.hasClass('selectized')) {
                    $field.next('.selectize-control').find('.selectize-input').addClass('invalid');
                }
                if ($field.closest('#customer-details.ppcart-checkout-step').length > 0 && $('.step-two').hasClass('ppcart-current')) {
                    $('.steps.step-one a').click();
                }
            }
        }

        // check required on blur
        $(document).on('blur', '#ppcart-payment-form input.required:not([type="checkbox"]):not([type="radio"]), #ppcart-payment-form select.required', function() {
            $(this).removeClass('invalid').siblings('.error').remove();

            if ($(this).val().length < 1) {
                var $el = $(this);
                if ($(this).attr('id') == 'address1' && isProductSingular()) {
                    $el = $('#ppcart-payment-form #address2');
                }
                $(this).addClass('invalid');
                $el.closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
            }
        });

        // Validate checkboxes/radios on change
        $(document).on('change', '#ppcart-payment-form input[type="checkbox"].required, #ppcart-payment-form input[type="radio"].required', function() {
            var name = $(this).attr('name');
            $('#ppcart-payment-form input[name="' + name + '"]').removeClass('invalid').find('.error').remove();
            $('#ppcart-payment-form input[name="' + name + '"]').siblings('.error').remove();

            if ($(this).attr('type') === 'checkbox') {
                if($(this).attr('name').endsWith('[]')) {
                    if ($('#ppcart-payment-form input[name="' + name + '"]:checked').length < 1) {
                        $('#ppcart-payment-form input[name="' + name + '"]').addClass('invalid');
                        $(this).closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                    } else {
                        $('#ppcart-payment-form input[name="' + name + '"]').closest('.ppcart-form-group').find('.error').remove();
                    }
                } else {
                    if (!$(this).is(':checked')) {
                        $(this).closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                    } else {
                        $(this).closest('.checkbox-wrap').removeClass('invalid').find('.error').remove();
                    }
                }
            } else if ($(this).attr('type') === 'radio') {
                if ($('#ppcart-payment-form input[name="' + name + '"]:checked').length < 1) {
                    $(this).closest('.ppcart-form-group').addClass('invalid').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                } else {
                    $('#ppcart-payment-form input[name="' + name + '"]').closest('.ppcart-form-group').find('.error').remove();
                }
            }
        });


        // check username on blur
        $(document).on('blur', '#ppcart-wpuserid', function() {
            $(this).removeClass('invalid valid').siblings('.error').remove();

            if (($(this).val().length > 0)) {
                checkUsername($(this));
            }
        });

        $(document).on('click', '.ppcart-checkout-form-steps .steps', async function(event) {
            event.preventDefault();
            var form_wrapper = $(this).closest('.ppcart-form-wrap').attr('id');
            if ($(this).hasClass('step-two')) {
                var errors = await ppcart_validate(form_wrapper);
                if (errors) {
                    return false;
                } else if (!$('[name="ppcart-lead-captured"]', '#'+form_wrapper).length) {
                    ppcart_do_lead_capture(form_wrapper);
                }
            }

            if ($(this).hasClass('ppcart-current')) {
                return false;
            }

            $('#ppcart-payment-form', '#'+form_wrapper).toggleClass('step-1 step-2');
            $('.ppcart-checkout-form-steps .steps', '#'+form_wrapper).toggleClass('ppcart-current');
            return false;

        });

        async function checkUsername($el) {
            $el.removeClass('valid');

            var paramObj = new FormData();
            paramObj.append('name', $el.val());
            paramObj.append('ppcart-nonce', $('input[name=ppcart-nonce]').val());
            paramObj.append('action', 'ppcart_check_username');

            try {
				const response = await fetch(ppcart.ajax, {
					method: 'POST',
					body:paramObj
				});
				if (response.ok) {
					const resp = await response.json();
					if (resp.success) {
						$el.addClass('valid');
						$('#ppcart_card_button, .ppcart-next-btn').removeAttr("disabled");
					} else{
						$el.addClass('invalid');
						$el.closest('.ppcart-form-group').append('<div class="error">' + resp.data.error + '</div>');
						$('#ppcart_card_button, .ppcart-next-btn').attr("disabled", true);
					}
					return resp.success;
				} else {
					alert('Something went wrong.')
					$el.addClass('invalid');
					$('#ppcart_card_button, .ppcart-next-btn').attr("disabled", true);
				}
			} catch (error) {
				alert('Something went wrong.')
				$el.addClass('invalid');
				$('#ppcart_card_button, .ppcart-next-btn').attr("disabled", true);
			}
            return false;
        }

        // check email address
        $(document).on('blur', '#ppcart-payment-form input[type="email"]', function() {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validEmail = regExEmail.test(val);
            if (!validEmail) {
                $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_email + '</div>');
            }
        });

        // check phone number
        $(document).on('blur', '#ppcart-payment-form input[type="tel"]', function() {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validPhone = isValidPhonenumber(val);
            if (!validPhone) {
                $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_phone + '</div>');
            }
        });

        // check password
        $(document).on('blur', '#ppcart-payment-form input.ppcart-password', function() {
            var val = $(this).val();
            if (val.length) {
                var validPass = isValidPassword(val);
                if (!validPass) {
                    $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_pass + '</div>');
                }
            }
        });

        /* track any change to total amount due
        $(document).on('change', '#ppcart-payment-form input', function() {
            amount = false;
        });*/

        async function ppcart_validate(form_wrapper, showErrors = true) {
            var errors = false;

            if ($('.elementor-editor-active').length > 0) {
                return errors;
            }

            if (showErrors) {
                $(".error").remove();
                $('#' + form_wrapper + ' input').removeClass('invalid');
            }

            var fields = $('#'+form_wrapper+' input.required:visible, #'+form_wrapper+' select.required:visible, #'+form_wrapper+' select.required.selectized');
            fields.each(function () {
                if ($(this).attr('type') == 'checkbox' && !$(this).is(':checked')) {
                    if($(this).attr('name').endsWith('[]')) {
                        if($('#'+form_wrapper+' input[name="'+$(this).attr('name')+'"]:checked').length < 1) {
                            if (showErrors) {
                                $(this).addClass('invalid');
                                if ($(this).closest('.ppcart-form-group').find('.error').length == 0)
                                    $(this).closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                            }
                            errors = true;
                        }
                    } else {
                        if (showErrors) {
                            $(this).closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                        }
                        errors = true;
                    }
                } else if ($(this).attr('type') == 'radio' && !$(this).is(':checked') && $('#'+form_wrapper+' input[name="'+$(this).attr('name')+'"]:checked').length < 1) {
                    if (showErrors) {
                        $(this).addClass('invalid');
                        if ($(this).closest('.ppcart-form-group').find('.error').length == 0)
                            $(this).closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                    }
                    errors = true;
                } else if ($(this).attr('name') == 'pwyw_amount') {
                    var value = $(this).val();
                    var minvalue = $(this).attr('min');
                    if (parseFloat(value) < parseFloat(minvalue)) {
                        if (showErrors) {
                            $(this).addClass('invalid');
                            if ($(this).closest('.ppcart-form-group').find('.error').length == 0)
                                $(this).closest('.ppcart-form-group').append('<div class="error">Please enter an amount greater thank or equal to <span class="ppcart-Price-currencySymbol">$</span>' + parseFloat(minvalue).toFixed(2) + '</div>')
                        }
                        errors = true;
                    }
                } else if ($(this).val().length < 1) {
                    var $el = $(this);
                    if ($(this).attr('id') == 'address1' && isProductSingular()) {
                        $el = $('#'+form_wrapper+' #address2');
                    }
                    if (showErrors) {
                        $(this).addClass('invalid');
                        $el.closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.field_required + '</div>');
                        if ($(this).hasClass('selectized')) {
                            $(this).next('.selectize-control').find('.selectize-input').addClass('invalid');
                        }
                    }
                    errors = true;
                }
            });

            // check username
            if ($('#'+form_wrapper+' #ppcart-wpuserid').length > 0) {
                var $username = $('#'+form_wrapper+' #ppcart-wpuserid');
                if (($username.val().length > 0)) {
                    var check = await checkUsername($username);
                    if (check == false) {
                        errors = true;
                    }
                }
            }

            // check email address
            $('#'+form_wrapper+' input[type="email"]').each(function () {
                if ($(this).val().length) {
                    var validEmail = regExEmail.test($(this).val());
                    if (!validEmail) {
                        if (showErrors) {
                            $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_email + '</div>');
                        }
                        errors = true;
                    }
                }
            });

            // check phone number
            $('#'+form_wrapper+' input[type="phone"]').each(function () {
                if ($(this).val().length) {
                    var validPhone = isValidPhonenumber($(this).val());
                    if (!validPhone) {
                        if (showErrors) {
                            $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_phone + '</div>');
                        }
                        errors = true;
                    }
                }
            });


            // check password
            $('#'+form_wrapper+' input.ppcart-password').each(function () {
                if ($(this).val().length) {
                    var validPass = isValidPassword($(this).val());
                    if (!validPass) {
                        if (showErrors) {
                            $(this).addClass('invalid').closest('.ppcart-form-group').append('<div class="error">' + ppcart_translate_frontend.invalid_pass + '</div>');
                        }
                        errors = true;
                    }
                }
            });

            if($('#'+form_wrapper+' input').closest('.ppcart-form-group').find('.error').length>0){
                errors = true;
            }

            return errors;
        }

        // 2 step form
        $(document).on('click', '.ppcart-next-btn', async function () {
            var form_wrapper = $(this).data('form-wrapper');
            var errors = await ppcart_validate(form_wrapper);
            if (errors) {
                return;
            } else {
                if (!$('[name="ppcart-lead-captured"]', '#'+form_wrapper).length) {
                    ppcart_do_lead_capture(form_wrapper);
                }
                $('#ppcart-payment-form', '#'+form_wrapper).toggleClass('step-1 step-2');
                $('.ppcart-checkout-form-steps .steps', '#'+form_wrapper).toggleClass('ppcart-current');
            }
        });

        function ppcart_do_lead_capture(wrap_id) {
            var form = '#'+wrap_id;
            var paramObj = {};
            $.each($('#ppcart-payment-form', form).serializeArray(), function(_, kv) {
                paramObj[kv.name] = kv.value;
            });

            paramObj['action'] = 'ppcart_capture_lead';

            $.post(ppcart.ajax, paramObj, function(response) {
                //console.log('capturing lead');
                if (response == 'OK') {
                    $('#ppcart-payment-form', form).trigger('ppcart/orderform/lead_captured');
                    var form = document.getElementById(wrap_id);
                    form = form.getElementsByTagName('form')[0];

                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'ppcart-lead-captured');
                    hiddenInput.setAttribute('value', 1);
                    form.appendChild(hiddenInput);
                }
            });
            return true;
        }

        // check pay info
        var $btn,
            $form,
            clientSecret,
            intent_id,
            amount,
            customer_id,
            ppcart_temp_order_id,
            ppcart_temp_order_token,
            prod_id,
            formAction,
            directConfirmation,
            paymentMethodId;

        if ($('.ppcart-stripe #card-element').length > 0 || $('.ppcart-stripe-express #express-pay-element').length > 0) {
            // Create a Stripe client.
            var stripe = Stripe(ppcart_stripe_key[0]);
            var card = {};
        }

        $('.ppcart-form-wrap').each(function(){

            let form_id = $(this).attr('id');
            let form = '#'+form_id;
            stripeCardComplete[form_id] = false;
            updateSubmitButtonState(form);

            // toggle pay methods
            togglePayMethods(form);

            update_pwyw(form);
            update_form(form);
            $('#ppcart-payment-form', form).trigger('ppcart/orderform/ready', [form]);

            // check pay info
            var $btn = $('#ppcart_card_button', form);
                $form = $('#ppcart-payment-form', form);

            if ($('.ppcart-stripe #card-element', form).length > 0 && ppcart.is_payment_element) {

                // Mount a Payment Element bound to a fresh PaymentIntent (one-time) or SetupIntent (subscription).
                mountPaymentElement(form);

                // Elementor popup re-init: namespace per form and off() first so handlers don't stack.
                jQuery(document).off('elementor/popup/show.ppcart-pe-' + form_id).on('elementor/popup/show.ppcart-pe-' + form_id, () => {
                    mountPaymentElement(form);
                    setTimeout(() => {
                        update_pwyw(form);
                        update_form(form_id);
                        $('#ppcart-payment-form', form).trigger('ppcart/orderform/ready', [form]);
                    }, 100);
                });

            } else if ($('.ppcart-stripe #card-element', form).length > 0) {

                $('.ppcart-stripe #card-element', form).each(function(){

                    let form_wrapper = form_id;
                    // Create an instance of Elements.
                    var elements = stripe.elements();
					let $this = "#"+form_wrapper+" #card-element";

                    // Custom styling can be passed to options when creating an Element.
                    // (Note that this demo uses a wider set of styles than the guide below.)
                    var style = {
                        base: {
                            color: '#32325d',
                            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                            fontSmoothing: 'antialiased',
                            fontSize: '16px',
                            '::placeholder': {
                                color: '#aab7c4'
                            }
                        },
                        invalid: {
                            color: '#fa755a',
                            iconColor: '#fa755a'
                        }
                    };

                    // Create an instance of the card Element.
                    card[form_wrapper] = elements.create('card', { style: style });

                    // Add an instance of the card Element into the `card-element` <div>.

                    card[form_wrapper].mount($this);

                    // Handle real-time validation errors from the card Element.
                    card[form_wrapper].addEventListener('change', function (event) {
                        var displayError = document.querySelector("#"+form_wrapper+" #card-errors");
                        stripeCardComplete[form_wrapper] = !!event.complete;
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                        updateSubmitButtonState("#"+form_wrapper);
                        if (event.complete) {
                            schedulePaymentIntentPreload("#"+form_wrapper);
                        } else {
                            clearPreparedPaymentIntent(form_wrapper);
                        }
                    });

                    // Reinitialize Stripe elements for forms inside Elementor popups
                    jQuery(document).on('elementor/popup/show', () => {

                        card[form_wrapper].unmount($this);
                        card[form_wrapper].mount($this);

                        // Handle real-time validation errors from the card Element.
                        card[form_wrapper].addEventListener('change', function (event) {
                            var displayError = document.querySelector("#"+form_wrapper+" #card-errors");
                            stripeCardComplete[form_wrapper] = !!event.complete;
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = '';
                            }
                            updateSubmitButtonState("#"+form_wrapper);
                            if (event.complete) {
                                schedulePaymentIntentPreload("#"+form_wrapper);
                            } else {
                                clearPreparedPaymentIntent(form_wrapper);
                            }
                        });

                        // 100ms delay for open popup animation
                        setTimeout(() => {
                            update_pwyw("#"+form_wrapper);
                            update_form(form_wrapper);
                            $('#ppcart-payment-form', "#"+form_wrapper).trigger('ppcart/orderform/ready', ["#"+form_wrapper]);
                        }, 100);
                    });
                });
            }

            function onloadCallback() {
                grecaptcha.ready(function() {
                    var gsitekey = $(".ppcart-grtoken").attr("data-sitekey");
                    grecaptcha.execute(gsitekey, { action: 'submit' }).then(function(token) {
                        $(".ppcart-grtoken", form).val(token);
                    });
                });
            }

            // Handle form submission.
            $(document).on('click', form + ' #ppcart_card_button', async function(event) {

                $btn = $(this);

                if ($btn.hasClass('running')) {
                    event.preventDefault();
                    return false;
                }

                originalText = $btn.find('span.text').text();
                $btn.addClass('running').attr("disabled", true);
                $btn.find('span.text').text(ppcart_translate_frontend.loading_processing);

                event.preventDefault();
                var form_wrapper = $btn.data('form-wrapper');
                var errors = await ppcart_validate(form_wrapper);
                var form = '#'+form_wrapper;

                if (errors) {
                    alert(ppcart_translate_frontend.missing_required);
                    $btn.removeClass('running').removeAttr("disabled");
                    $btn.find('span.text').text(originalText);
                    updateSubmitButtonState(form);
                    return false;
                }

                if(jQuery(".g-recaptcha", form).length>0){
                    var gcaptchasize = jQuery(".g-recaptcha", form).attr("data-size");
                    if (gcaptchasize == "invisible") {
                        grecaptcha.ready(function() {
                            grecaptcha.execute();
                        });
                    }
                }

                if ($(".ppcart-grtoken", form).length > 0) {
                    grecaptcha.ready(function() {
                        var gsitekey = $(".ppcart-grtoken", form).attr("data-sitekey");
                        grecaptcha.execute(gsitekey, { action: 'submit' }).then(function(token) {
                            $(".ppcart-grtoken", form).val(token);
                        });
                    });
                }

                var checkoutDelay = ($(".ppcart-grtoken", form).length > 0 || jQuery(".g-recaptcha", form).length > 0) ? 1000 : 0;

                setTimeout(function() {

                    var is_subscription = $('.ob-sub:checked', form).length || ($('input[name="ppcart_product_option"]:checked', form).data('installments') && !$('.ob-replace:checked', form).length);
                    var checkoutAmount = getCheckoutAmount(form);

                    if (!$('[name="ppcart_amount"]', form).val() && checkoutAmount > 0) {
                        $('[name="ppcart_amount"]', form).val(checkoutAmount);
                    }

                    if ($('#ppcart-payment-form [name="pay-method"]', form).length > 0) {
                        var payMethod = ($('[name="pay-method"]:checked', form).val() || '').toLowerCase();
                        var amountUnset = !$('[name="ppcart_amount"]', form).val();
                        // Do not treat an unwritten amount as $0 COD when a gateway
                        // other than Stripe/COD is selected (PayPal under load).
                        if (payMethod && payMethod !== 'stripe' && payMethod !== 'cod' && (checkoutAmount != 0 || is_subscription || amountUnset)) {
                            $('#ppcart-payment-form', form).trigger('ppcart/orderform/submit');
                            return false;
                        }
                    }

                    var paramObj = $('#ppcart-payment-form', form).serializeArray();

                    // process free payment, manual payment methods
                    var manual = ($('#ppcart-payment-form [name="pay-method"]:checked', form).val() == 'cod'),
                        plan = $('input[name=ppcart_product_option]:checked', form);

                    if (checkoutAmount == 0 && !is_subscription || manual) {
                        paramObj.push(
                            { name: "action", value: "ppcart_save_order_to_db" },
                            { name: "pay-method", value: "cod" } // change method to "COD", otherwise order will get stuck in "pending" status
                        );

                        $.post(ppcart.ajax, paramObj, function(res) {
                            var response = ppcart_parse_json_response(res);
                            if ('undefined' !== typeof response.error) {
                                alert(response.error);

                                if ('undefined' !== typeof response.fields) {
                                    $.each(response.fields, function(i, item) {
                                        invalidate_field($('[name="' + item['field'] + '"]', form), item['message'])
                                    });
                                }

                                $btn = $('#ppcart_card_button', form);
                                $btn.removeClass('running').removeAttr("disabled");
                                $btn.find('span.text').text(originalText);
                            } else {
                                if ('undefined' !== typeof response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    // var formEl = document.getElementById(form_wrapper).firstChild;

                                    if ('undefined' !== typeof response.formAction) {
                                        $form = $('#ppcart-payment-form', form);
                                        $form.attr('action', response.formAction);
                                    }
                                    ppcartAppendCheckoutCompletionFields($form.get(0), response.order_id, response.ppcart_access);
                                    $form.submit();
                                }
                            }
                        });
                        return false;
                    }

                    // Hosted Checkout: create a Stripe Checkout Session and redirect.
                    if (ppcart.is_hosted_checkout) {
                        var hostedParams = $('#ppcart-payment-form', form).serializeArray();
                        hostedParams.push({ name: "action", value: "ppcart_create_checkout_session" });

                        $.post(ppcart.ajax, hostedParams, function(res) {
                            var response = ppcart_parse_json_response(res);

                            if (response && response.success && response.data && response.data.url) {
                                window.location = response.data.url;
                                return;
                            }

                            var message = (response && response.data && response.data.error)
                                ? response.data.error
                                : (response && response.error ? response.error : ppcart_translate_frontend.missing_required);
                            alert(message);

                            $btn = $('#ppcart_card_button', form);
                            $btn.removeClass('running').removeAttr("disabled");
                            $btn.find('span.text').text(originalText);
                            updateSubmitButtonState(form);
                        });
                        return false;
                    }

                    // Payment Element: confirm inline against the mounted intent.
                    if (ppcart.is_payment_element) {
                        var peState = getCheckoutIntentState(form_wrapper);
                        var peIntent = peState.peIntent;

                        if (!peIntent || !peIntent.clientSecret) {
                            // The element/intent is not ready yet; (re)mount and ask to retry.
                            mountPaymentElement(form);
                            alert(ppcart_translate_frontend.missing_required);
                            $btn.removeClass('running').removeAttr("disabled");
                            $btn.find('span.text').text(originalText);
                            updateSubmitButtonState(form);
                            return false;
                        }

                        // Capture element/intent atomically and suppress remounts while confirm is in flight.
                        var peElements = paymentElements[form_wrapper];
                        peState.confirmInFlight = true;

                        var proceedPaymentElement = function() {
                            confirmPaymentElement(peIntent, form_wrapper, peElements);
                        };

                        // Subscriptions use a SetupIntent (no amount to reconcile).
                        if (is_subscription || peIntent.is_setup_intent === '1') {
                            proceedPaymentElement();
                            return;
                        }

                        // Reconcile the PaymentIntent amount (order bumps) before confirming, then fetchUpdates().
                        var peUpdateParams = $('#ppcart-payment-form', form).serializeArray();
                        peUpdateParams.push(
                            { name: "action", value: "ppcart_update_payment_intent_amt" },
                            { name: "intent_id", value: peIntent.intent_id }
                        );

                        $.post(ppcart.ajax, peUpdateParams, function(amt) {
                            var updatedAmount = $.trim(String(amt));

                            if (updatedAmount !== '' && !isNaN(updatedAmount)) {
                                amount = updatedAmount;
                                peIntent.amount = updatedAmount;
                            } else if (updatedAmount) {
                                peState.confirmInFlight = false;
                                alert(updatedAmount);
                                $btn.removeClass('running').removeAttr("disabled");
                                $btn.find('span.text').text(originalText);
                                return;
                            }

                            if (peElements && typeof peElements.fetchUpdates === 'function') {
                                peElements.fetchUpdates().then(function() {
                                    proceedPaymentElement();
                                });
                            } else {
                                proceedPaymentElement();
                            }
                        }).fail(function() {
                            // Abort rather than confirm against a possibly-stale amount.
                            peState.confirmInFlight = false;
                            displayError(ppcart_translate_frontend.try_again, form_wrapper);
                        });

                        return false;
                    }

                    paramObj.push({name: "action", value: "ppcart_create_payment_intent"});
                    var intentFingerprint = buildPaymentIntentFingerprint(form);

                    var refreshExistingPaymentIntentAmount = function(intent, callback) {
                        if (!$('input[name=order_id]', form).length || intent.amount) {
                            callback();
                            return;
                        }

                        var updateParams = $('#ppcart-payment-form', form).serializeArray();
                        updateParams.push(
                            { name: "action", value: "ppcart_update_payment_intent_amt" },
                            { name: "intent_id", value: intent.intent_id }
                        );

                        $.post(ppcart.ajax, updateParams, function(amt) {
                            var updatedAmount = $.trim(String(amt));

                            if (updatedAmount !== '' && !isNaN(updatedAmount)) {
                                amount = updatedAmount;
                                intent.amount = updatedAmount;
                                callback();
                                return;
                            }

                            if (updatedAmount) {
                                alert(updatedAmount);
                                $btn.removeClass('running').removeAttr("disabled");
                                $btn.find('span.text').text(originalText);
                                return;
                            }

                            callback();
                        }).fail(function() {
                            callback();
                        });
                    };

                    var submitPaymentIntent = function(intent) {
                        if (is_subscription) {
                            // If a previous payment was attempted, get the lastest invoice
                            const paymentRetry = getSubscriptionPaymentRetry(form_wrapper);

                            if (
                                paymentRetry &&
                                (paymentRetry.status === 'requires_payment_method' ||
                                    paymentRetry.status === 'requires_action')
                            ) {
                                const isPaymentRetry = true;
                                // create new payment method & retry payment on invoice with new payment method
                                confirmSubscription(
                                    intent,
                                    isPaymentRetry,
                                    paymentRetry.invoiceId,
                                    form_wrapper
                                );
                            } else {
                                confirmSubscription(intent, false, false, form_wrapper);
                            }
                        } else {
                            refreshExistingPaymentIntentAmount(intent, function() {
                                // create new order in db then send to stripe
                                saveThenConfirm(intent, form_wrapper);
                            });
                        }
                    };

                    var handleCreatePaymentIntentResponse = function(res) {
                        var response = ppcart_parse_json_response(res);
                        if ('undefined' !== typeof response.error) {
                            alert(response.error);
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function(i, item) {
                                    invalidate_field($('[name="' + item['field'] + '"]', form), item['message'])
                                });
                            }
                            $btn.removeClass('running').removeAttr("disabled");
                            $btn.find('span.text').text(originalText);
                            return false;
                        } else {
                            if ('undefined' !== typeof response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                var intent = storePaymentIntentState(form_wrapper, response, intentFingerprint);
                                submitPaymentIntent(intent);
                            }
                        }
                    };

                    var createPaymentIntentNow = function() {
                        $.post(ppcart.ajax, paramObj, function(res) {
                            handleCreatePaymentIntentResponse(res);
                        });
                    };

                    if (is_subscription) {

                        if (!intent_id) {
                            createPaymentIntentNow();
                        } else {
                            submitPaymentIntent({
                                'clientSecret': clientSecret,
                                'intent_id': intent_id,
                                'customer_id': customer_id,
                                'amount': amount,
                                'prod_id':prod_id,
                                'ppcart_temp_order_id':ppcart_temp_order_id,
                                'ppcart_temp_order_token':ppcart_temp_order_token,
                                'formAction':formAction,
                                'directConfirmation':directConfirmation
                            });
                        }

                        return;
                    }

                    var preparedIntent = getPreparedPaymentIntent(form_wrapper, intentFingerprint);
                    if (preparedIntent) {
                        submitPaymentIntent(preparedIntent);
                        return;
                    }

                    var state = getCheckoutIntentState(form_wrapper);
                    if (state.pending && state.pendingFingerprint === intentFingerprint) {
                        state.pending.done(function(intent) {
                            var preparedIntent = getPreparedPaymentIntent(form_wrapper, intentFingerprint);

                            if (intent && preparedIntent) {
                                submitPaymentIntent(preparedIntent);
                            } else {
                                createPaymentIntentNow();
                            }
                        }).fail(function() {
                            createPaymentIntentNow();
                        });
                        return;
                    }

                    createPaymentIntentNow();

                }, checkoutDelay);

            });
        });

        $('.ppcart-cancel-sub').click(function(e) {
            e.preventDefault();

            if (confirm(ppcart_translate_frontend.confirm_cancel_sub)) {
                var ajaxurl = ppcart.ajax;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce = jQuery("#ppcart_nonce").val();
                var subscriber_id = (_this.data('item-id')) ? _this.data('item-id') : $("#ppcart_payment_intent").val();
                var payment_method = jQuery("#ppcart_payment_method").val();
                var prod_id = jQuery("#ppcart_product_id").val();

                var data = {
                    'action': 'ppcart_unsubscribe_customer',
                    'prod_id': prod_id,
                    'subscription_id': subscriber_id,
                    'nonce': wp_nonce,
                    'id': id,
                    'payment_method': payment_method,
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                jQuery.post(ajaxurl, data, function(response) {
                    if (response == 'OK') {
                        alert(ppcart_translate_frontend.sub_cancel); //custom message
                        location.reload();
                    } else {
                        alert(response);
                    }
                }).fail(function() {
                    alert(response.fail);
                });
            } else {
                return false;
            }
        });

        /**
         * Pause Start Subscription
         */
        $('.ppcart-pause-restart-sub').click(function(e){
            e.preventDefault();

            var confirmMessage = ppcart_translate_frontend.confirm_pause_sub;
            var successMessage = ppcart_translate_frontend.sub_paused;
            var type = $(this).data('action');

            if(type == 'started'){
                confirmMessage = ppcart_translate_frontend.confirm_activate_sub;
                successMessage = ppcart_translate_frontend.sub_started;
            }

            if (confirm(confirmMessage)) {
                var ajaxurl = ppcart.ajax;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce	= jQuery("#ppcart_nonce").val();
                var payment_method	= jQuery("#ppcart_payment_method").val();
                var prod_id = jQuery("#ppcart_product_id").val();

                var data = {
                    'action': 'ppcart_pause_restart_subscription',
                    'prod_id': prod_id,
                    'nonce': wp_nonce,
                    'id': id,
                    'payment_method': payment_method,
                    'type':type,
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                jQuery.post(ajaxurl, data, function(response) {
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

        $('input.required, select.required, input[type="checkbox"].required, input[type="radio"].required').on('keyup change', async function () {
            var form = $(this).closest('form').parent().attr('id');
            let $this = "#"+form+" #express-pay-element";
            var errors = await ppcart_validate(form, false);

            if (errors) {
                $($this).css({
                    'pointer-events': 'none',
                    'opacity': '0.5'
                });
            }else{
                $($this).css({
                    'pointer-events': '',
                    'opacity': '1'
                });
            }
        });

        // Express Payment Onload
        function initialExpressPayment(form_wrapper) {
            if ($('.ppcart-stripe-express #express-pay-element', form_wrapper).length > 0) {
                $('.ppcart-stripe-express #express-pay-element', form_wrapper).each(function(){
                    $(this).hide();
                    let $this = form_wrapper+" #express-pay-element";
                    const elements = stripe.elements();
                    const form = form_wrapper.replace('#', '');
                    var product_name = $(form_wrapper + ' [name="ppcart_product_name"]').val();
                    var amount = Math.round(parseFloat($('input[name=ppcart_amount]', form_wrapper).val()) * 100);
                    var country = $('input[name=ppcart_currency_country_code]', form_wrapper).val().toUpperCase(),
                    currency = $('input[name=ppcart_currency_code]', form_wrapper).val().toLowerCase();

                    $($this).css({
                        'pointer-events': 'none',
                        'opacity': '0.5'
                    });

                    if(currency && country){
                        const paymentRequest = stripe.paymentRequest({
                            country: country,
                            currency: currency,
                            total: {
                                label: product_name,
                                amount: amount,
                            },
                            requestPayerName: true,
                            requestPayerEmail: true
                        });

                        ppcart_validate(form, false).then(errors => {
                            if (!errors) {
                                $($this).css({
                                    'pointer-events': '',
                                    'opacity': '1'
                                });
                            }
                        });

                        card[form_wrapper] = elements.create('paymentRequestButton', {
                            paymentRequest: paymentRequest,
                            style: {
                                paymentRequestButton: {
                                type: 'default',
                                theme: 'dark',
                                height: '45px',
                                },
                            },
                        });
                        paymentRequest.canMakePayment().then(result => {
                            console.log('Can make payment result ::', result);
                            if (result && result.link) {
                                // Link is supported
                                card[form_wrapper].mount($this);
                                $($this).show();
                            } else {
                                // Link is not supported (country issue)
                                $($this).hide();

                                // Show custom message or disable the button
                                var message = "Sorry, Express Payment is not supported in your region.";
                                // You can append this message to a specific element in your form or show it in a modal
                                $('#payment-method-message', form_wrapper).text(message).show();

                                // Optionally disable or hide other parts of the UI
                                // $('#ppcart_card_button', form_wrapper).prop('disabled', true);
                            }
                        }).catch(error => {
                            console.error('Error checking payment capability:', error);
                            $($this).hide();

                            // Optional: Show a different message for error scenarios
                            $('#payment-method-message', form_wrapper).text("An error occurred while processing your payment options. Please try again.").show();
                        });
                        paymentRequest.on('paymentmethod', async (ev) => {
                            try {
                                $($this).css({
                                    'pointer-events': 'none',
                                    'opacity': '0.5'
                                });
                                $('#ppcart_card_button', form_wrapper).addClass('running').attr('disabled', 'disabled');
                                // Validation
                                var errors = await ppcart_validate(form);
                                if (errors) {
                                   $($this).css({
                                        'pointer-events': '',
                                        'opacity': '1'
                                    });
                                    $('#ppcart_card_button', form_wrapper).removeClass('running').removeAttr("disabled");
                                    alert(ppcart_translate_frontend.missing_required);
                                    return ev.complete('fail');
                                }
                                if(jQuery(".g-recaptcha", form_wrapper).length>0){
                                    var gcaptchasize = jQuery(".g-recaptcha", form_wrapper).attr("data-size");
                                    if (gcaptchasize == "invisible") {
                                        grecaptcha.ready(function() {
                                            grecaptcha.execute();
                                        });
                                    }
                                }
                                if ($(".ppcart-grtoken", form_wrapper).length > 0) {
                                    grecaptcha.ready(function() {
                                        var gsitekey = $(".ppcart-grtoken", form_wrapper).attr("data-sitekey");
                                        grecaptcha.execute(gsitekey, { action: 'submit' }).then(function(token) {
                                            $(".ppcart-grtoken", form_wrapper).val(token);
                                        });
                                    });
                                }
                                setTimeout(function() {
                                    var is_subscription = $('.ob-sub:checked', form_wrapper).length || ($('input[name="ppcart_product_option"]:checked', form_wrapper).data('installments') && !$('.ob-replace:checked', form_wrapper).length);
                                    var paramObj = $('#ppcart-payment-form', form_wrapper).serializeArray();
                                    var paymentMethod = ev.paymentMethod;
                                    paymentMethodId = paymentMethod['id'];
                                    paramObj.push({name: "action", value: "ppcart_create_payment_intent"});
                                    paramObj.push({name: "ppcart_payment_method_id", value: paymentMethodId});
                                    paramObj.find(input => input.name == 'pay-method').value = "stripe";

                                    if (!intent_id) {
                                        // create new intent if none found
                                        $.post(ppcart.ajax, paramObj, function(res) {
                                            var response = ppcart_parse_json_response(res);
                                            if ('undefined' !== typeof response.error) {
                                                alert(response.error);

                                                if ('undefined' !== typeof response.fields) {
                                                    $.each(response.fields, function(i, item) {
                                                        invalidate_field($('[name="' + item['field'] + '"]', form_wrapper), item['message'])
                                                    });
                                                }

                                                ev.complete('fail');
                                                $($this).css({
                                                    'pointer-events': '',
                                                    'opacity': '1'
                                                });
                                                $('#ppcart_card_button', form_wrapper).removeClass('running').removeAttr("disabled");
                                                return false;
                                            }

                                            response.paymentMethodId = paymentMethodId;
                                            if ('undefined' !== typeof response.redirect) {
                                                window.location.href = response.redirect;
                                            } else {
                                                clientSecret = response.clientSecret;
                                                intent_id = response.intent_id;
                                                customer_id = response.customer_id;
                                                amount = response.amount;
                                                ppcart_temp_order_id = response.ppcart_temp_order_id;
                                                ppcart_temp_order_token = response.ppcart_temp_order_token;
                                                prod_id = response.prod_id;
                                                formAction = response.formAction;
                                                directConfirmation = response.directConfirmation;
                                                if (is_subscription) {
                                                    // Create the subscription
                                                    createSubscription(
                                                        customer_id,
                                                        paymentMethodId,
                                                        false,
                                                        form,
                                                        ev
                                                    );
                                                } else {
                                                    // create new order in db then send to stripe
                                                    $btn = $(form_wrapper + ' #ppcart_card_button').data('form-wrapper');
                                                    saveThenConfirm(response, $btn, true, ev);
                                                }
                                            }
                                        }).fail(function(jqXHR, textStatus, errorThrown) {
                                            ev.complete('fail');
                                            $($this).css('pointer-events', '');
                                            $('#ppcart_card_button', form_wrapper).removeClass('running').removeAttr("disabled");
                                            console.error('Error confirming the payment method:', textStatus, errorThrown);
                                            alert('An unexpected error occurred.');
                                        });
                                    } else {
                                        var intent = {
                                            'clientSecret': clientSecret,
                                            'intent_id': intent_id,
                                            'customer_id': customer_id,
                                            'amount': amount,
                                            'prod_id':prod_id,
                                            'ppcart_temp_order_id':ppcart_temp_order_id,
                                            'ppcart_temp_order_token':ppcart_temp_order_token,
                                            'formAction':formAction,
                                            'directConfirmation':directConfirmation,
                                            'paymentMethodId' : paymentMethodId
                                        };
                                        if (is_subscription) {
                                            // If a previous payment was attempted, get the lastest invoice
                                            const paymentRetry = getSubscriptionPaymentRetry(form);
                                            if (
                                                paymentRetry &&
                                                (paymentRetry.status === 'requires_payment_method' ||
                                                    paymentRetry.status === 'requires_action')
                                            ) {
                                                const isPaymentRetry = true;
                                                // create new payment method & retry payment on invoice with new payment method
                                                confirmSubscription(
                                                    intent,
                                                    isPaymentRetry,
                                                    paymentRetry.invoiceId,
                                                    form,
                                                    ev
                                                );
                                            } else {
                                                confirmSubscription(intent, false, false, form, ev);
                                            }
                                        } else {
                                            if ($('input[name=order_id]').length == 0) {
                                                // create new order in db then send to stripe
                                                $btn = $(form_wrapper + ' #ppcart_card_button').data('form-wrapper');
                                                saveThenConfirm(intent, $btn, true, ev);
                                            } else {
                                                if (!amount) {
                                                    paramObj['action'] = 'ppcart_update_payment_intent_amt';
                                                    paramObj['intent_id'] = intent.intent_id;
                                                    $.post(ppcart.ajax, paramObj, function(amt) {
                                                        if (!isNaN(amt)) {
                                                            amount = amt;
                                                            intent.amount = amt;
                                                        }
                                                    });
                                                }
                                                // create new order in db then send to stripe
                                                confirmCardPayment(intent, form, true, ev);
                                            }
                                        }
                                    }
                                }, 1000);
                            } catch (error) {
                                console.error('Error confirming the payment method:', error);
                                ev.complete('fail');
                                $($this).css('pointer-events', '');
                                $('#ppcart_card_button', form_wrapper).removeClass('running').removeAttr("disabled");
                                alert('An unexpected error occurred.');
                            }
                        });
                    }
                });
            }
        }

        function saveThenConfirm(intent, form_wrapper, isExpresspayment, ev) {
            confirmCardPayment(intent, form_wrapper, isExpresspayment, ev);
        }

        var subscriptionPaymentRetryStorageKey = 'ppcartSubscriptionPaymentRetry';

        function getSubscriptionCheckoutFingerprint(wrap_id) {
            var wrapperId = String(wrap_id || '').replace('#', '');
            var wrapper = document.getElementById(wrapperId);

            if (!wrapper) {
                return '';
            }

            return buildPaymentIntentFingerprint(
                '#' + wrapperId,
                [
                    'g-recaptcha-response',
                    'ppcart-access',
                    'ppcart-lead-captured',
                    'ppcart-nonce',
                    'ppcart_order_id'
                ]
            );
        }

        function getSubscriptionPaymentRetry(wrap_id) {
            var checkoutFingerprint = getSubscriptionCheckoutFingerprint(wrap_id);
            var retry = null;

            try {
                retry = JSON.parse(localStorage.getItem(subscriptionPaymentRetryStorageKey) || 'null');
            } catch (error) {
                retry = null;
            }

            if (
                !checkoutFingerprint ||
                !retry ||
                retry.checkoutFingerprint !== checkoutFingerprint ||
                !retry.invoiceId
            ) {
                clearSubscriptionPaymentRetry();
                return null;
            }

            return retry;
        }

        function createSubscription(customerId, paymentMethodId, invoiceId, wrap_id, ev) {
            var f = document.getElementById(wrap_id);
            f = f.getElementsByTagName('form')[0];
            const checkoutFingerprint = getSubscriptionCheckoutFingerprint(wrap_id);
            const form = new FormData(f);
            form.set('action', 'ppcart_create_subscription');
            form.set('customerId', customerId);
            form.set('paymentMethodId', paymentMethodId);
            if (invoiceId) {
                form.set('invoiceId', invoiceId);
            }
            const params = new URLSearchParams(form);
            return (
                fetch(ppcart.ajax, {
                    method: 'post',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                    },
                    body: params
                })
                .then((response) => {
                    return response.json();
                })
                // If the card is declined, display an error to the user.
                .then((result) => {
                    if (result && result.success === false && result.data) {
                        throw result.data.error || result.data.message || result.data;
                    }
                    if (result.error) {
                        // The card had an error when trying to attach it to a customer
                        throw result.error;
                    }
                    return result;
                })
                // Normalize the result to contain the object returned
                // by Stripe. Add the addional details we need.
                .then((result) => {
                    // Insert the token ID into the form so it gets submitted to the server
                    if ('undefined' !== typeof result.formAction) {
                        f.setAttribute('action', result.formAction);
                    }
                    ppcartAppendCheckoutCompletionFields(f, result.ppcart_order_id, result.ppcart_access);
                    return {
                        // Use the Stripe 'object' property on the
                        // returned result to understand what object is returned.
                        subscription: result,
                        paymentMethodId: paymentMethodId,
                        checkoutFingerprint: checkoutFingerprint,
                    };
                })
                // Some payment methods require a customer to do additional
                // authentication with their financial institution.
                // Eg: 2FA for cards.
                .then(handlePaymentThatRequiresCustomerAction)
                // If attaching this card to a Customer object succeeds,
                // but attempts to charge the customer fail. You will
                // get a requires_payment_method error.
                .then(handleRequiresPaymentMethod)
                // No more actions required. Provision your service for the user.
                .then(function() {
                    if(ev){
                        ev.complete('success');
                    }
                    f.submit();
                })
                .catch((error) => {
                    if(ev){
                        ev.complete('fail');
                    }
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    displayError(error, wrap_id);
                })
            );
        }

        function retryInvoiceWithNewPaymentMethod(
            customerId,
            paymentMethodId,
            invoiceId,
            wrap_id,
            ev
        ) {
            var f = document.getElementById(wrap_id);
            f = f.getElementsByTagName('form')[0];
            const checkoutFingerprint = getSubscriptionCheckoutFingerprint(wrap_id);
            const form = new FormData(f);
            form.set('action', 'ppcart_create_subscription');
            form.set('customerId', customerId);
            form.set('paymentMethodId', paymentMethodId);
            if (invoiceId) {
                form.set('invoiceId', invoiceId);
            }
            const params = new URLSearchParams(form);
            return (
                fetch(ppcart.ajax, {
                    method: 'post',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                    },
                    body: params
                })
                .then((response) => {
                    return response.json();
                })
                // If the card is declined, display an error to the user.
                .then((result) => {
                    if (result && result.success === false && result.data) {
                        throw result.data.error || result.data.message || result.data;
                    }
                    if (result.error) {
                        // The card had an error when trying to attach it to a customer
                        throw result;
                    }
                    return result;
                })
                // Normalize the result to contain the object returned
                // by Stripe. Add the addional details we need.
                .then((result) => {
                    return {
                        // Use the Stripe 'object' property on the
                        // returned result to understand what object is returned.
                        invoice: result,
                        paymentMethodId: paymentMethodId,
                        checkoutFingerprint: checkoutFingerprint,
                        isRetry: true,
                    };
                })
                // Some payment methods require a customer to be on session
                // to complete the payment process. Check the status of the
                // payment intent to handle these actions.
                .then(handlePaymentThatRequiresCustomerAction)
                // No more actions required. Provision your service for the user.
                .then(function() {
                    if(ev){
                        ev.complete('success');
                    }
                    //console.log('result: ' + $('input[name=order_id]').val());
                    f.submit();
                })
                .catch((error) => {
                    if(ev){
                        ev.complete('fail');
                    }
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    displayError(error, wrap_id);
                })
            );
        }

        function getSubscriptionPaymentConfirmation(subscription, invoice) {
            var latestInvoice = invoice || (subscription && subscription.latest_invoice) || null;
            var confirmationSecret = latestInvoice && latestInvoice.confirmation_secret;

            if (confirmationSecret && typeof confirmationSecret === 'object' && confirmationSecret.client_secret) {
                return {
                    clientSecret: confirmationSecret.client_secret,
                    status: confirmationSecret.status || ''
                };
            }

            var paymentIntent = latestInvoice && latestInvoice.payment_intent;

            if (paymentIntent && typeof paymentIntent === 'object') {
                return {
                    clientSecret: paymentIntent.client_secret || '',
                    status: paymentIntent.status || ''
                };
            }

            return null;
        }

        function paymentConfirmationNeedsCustomerAction(paymentConfirmation, subscription, isRetry) {
            if (!paymentConfirmation || !paymentConfirmation.clientSecret) {
                return false;
            }

            var status = paymentConfirmation.status;
            if (status === 'succeeded' || status === 'requires_capture') {
                return false;
            }

            // Invoice.confirmation_secret is { client_secret, type } — no PaymentIntent status.
            if (!status) {
                return true;
            }

            if (
                status === 'requires_action' ||
                status === 'requires_source_action' ||
                status === 'requires_confirmation' ||
                (isRetry === true && status === 'requires_payment_method')
            ) {
                return true;
            }

            return isRetry === true || !!(subscription && subscription.status === 'incomplete');
        }

        function storeSubscriptionPaymentRetry(checkoutFingerprint, subscription, status) {
            var latestInvoice = subscription && subscription.latest_invoice;

            if (!checkoutFingerprint || !latestInvoice || !latestInvoice.id) {
                return;
            }

            localStorage.setItem(subscriptionPaymentRetryStorageKey, JSON.stringify({
                checkoutFingerprint: checkoutFingerprint,
                invoiceId: latestInvoice.id,
                status: status || 'requires_action'
            }));
        }

        function clearSubscriptionPaymentRetry() {
            localStorage.removeItem(subscriptionPaymentRetryStorageKey);
            localStorage.removeItem('latestInvoiceId');
            localStorage.removeItem('latestInvoicePaymentIntentStatus');
        }

        function handlePaymentThatRequiresCustomerAction({
            subscription,
            invoice,
            priceId,
            paymentMethodId,
            checkoutFingerprint,
            isRetry,
        }) {
            let paymentConfirmation = getSubscriptionPaymentConfirmation(subscription, invoice);

            if (!paymentConfirmation) {
                if (isRetry === true || (subscription && subscription.status === 'incomplete')) {
                    throw { error: { message: 'Stripe did not return a client secret for this incomplete subscription.' } };
                }

                return { subscription, priceId, paymentMethodId };
            }

            if (paymentConfirmationNeedsCustomerAction(paymentConfirmation, subscription, isRetry)) {
                storeSubscriptionPaymentRetry(checkoutFingerprint, subscription, paymentConfirmation.status);

                return stripe
                    .confirmCardPayment(paymentConfirmation.clientSecret, {
                        payment_method: paymentMethodId,
                    })
                    .then((result) => {
                        if (result.error) {
                            throw result;
                        } else {
                            if (result.paymentIntent.status === 'succeeded') {
                                clearSubscriptionPaymentRetry();

                                // There's a risk of the customer closing the window before callback
                                // execution. To handle this case, set up a webhook endpoint and
                                // listen to invoice.paid. This webhook endpoint returns an Invoice.
                                return {
                                    priceId: priceId,
                                    subscription: subscription,
                                    invoice: invoice,
                                    paymentMethodId: paymentMethodId,
                                    checkoutFingerprint: checkoutFingerprint,
                                };
                            }
                        }
                    });
            } else {
                return { subscription, priceId, paymentMethodId };
            }
        }

        function handleRequiresPaymentMethod({
            subscription,
            invoice,
            paymentMethodId,
            priceId,
            checkoutFingerprint,
        }) {
            const paymentConfirmation = getSubscriptionPaymentConfirmation(subscription, invoice);

            if (subscription.status === 'active' || subscription.status === 'trialing') {
                clearSubscriptionPaymentRetry();
                return { subscription, priceId, paymentMethodId };
            }

            if (subscription.status === 'incomplete' && !paymentConfirmation) {
                throw { error: { message: 'Stripe did not return a client secret for the incomplete subscription.' } };
            }

            if (
                paymentConfirmation &&
                paymentConfirmation.status === 'requires_payment_method'
            ) {
                storeSubscriptionPaymentRetry(checkoutFingerprint, subscription, paymentConfirmation.status);
                throw { error: { message: 'Your card was declined.' } };
            } else {
                return { subscription, priceId, paymentMethodId };
            }
        }

        function confirmSubscription(intent, isPaymentRetry = false, invoiceId = false, form_wrapper=false, ev) {
            var customer_name = $('#first_name', '#'.form_wrapper).val() + ' ' + $('#last_name', '#'.form_wrapper).val(),
                customer_email = $('#email', '#'.form_wrapper).val();
            stripe.createPaymentMethod({
                type: 'card',
                card: card[form_wrapper],
                billing_details: {
                    name: customer_name,
                    email: customer_email,
                },
            })
                .then((result) => {
                    if (result.error) {
                        displayError(result, form_wrapper);
                    } else {
                        if (isPaymentRetry) {
                            // Update the payment method and retry invoice payment
                            retryInvoiceWithNewPaymentMethod(
                                intent.customer_id,
                                result.paymentMethod.id,
                                invoiceId,
                                form_wrapper,
                                ev
                            );
                        } else {
                            // Create the subscription
                            createSubscription(
                                intent.customer_id,
                                result.paymentMethod.id,
                                false,
                                form_wrapper,
                                ev
                            );
                        }
                    }
                });
        }

        function displayError(event, form_wrapper) {
            if (event.error) {
                alert(event.error.message);
            } else if (event) {
                alert(event);
            }
            var $errBtn = $('#ppcart_card_button', '#'+form_wrapper);
            $errBtn.removeClass('running').removeAttr("disabled");
            // Restore the button label a failed confirm left on "processing".
            if ('undefined' !== typeof originalText && originalText) {
                $errBtn.find('span.text').text(originalText);
            }
            $('#express-pay-element', '#'+form_wrapper).css('pointer-events', '');
        }

        // Payment Element helpers: create an intent (PaymentIntent one-time / SetupIntent subscription) and confirm inline.
        function getPaymentElementAppearance() {
            return {
                theme: 'stripe',
                variables: {
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    colorText: '#32325d',
                    colorDanger: '#fa755a'
                }
            };
        }

        function paymentElementReturnUrl() {
            return window.location.href;
        }

        function schedulePaymentElementRemount(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;
            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            // Never remount while a submit-captured confirm is in flight.
            if (state.confirmInFlight) {
                return;
            }

            clearTimeout(state.peRemountTimer);
            state.peRemountTimer = setTimeout(function() {
                mountPaymentElement(form);
            }, 300);
        }

        function schedulePaymentElementAmountSync(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;
            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            // The submit path reconciles the amount itself before confirming.
            if (state.confirmInFlight) {
                return;
            }

            if (!state.peIntent || !paymentElements[formId]) {
                schedulePaymentElementRemount(form);
                return;
            }

            // SetupIntent has no amount — refresh the fingerprint so popup-reopen doesn't remount.
            if (state.peIntent.is_setup_intent === '1') {
                state.peFingerprint = buildPaymentIntentFingerprint(form);
                return;
            }

            clearTimeout(state.peAmountSyncTimer);
            state.peAmountSyncTimer = setTimeout(function() {
                syncPaymentElementAmount(form);
            }, 300);
        }

        function syncPaymentElementAmount(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;
            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            if (state.confirmInFlight || !state.peIntent || !paymentElements[formId]) {
                return;
            }

            // Don't stack concurrent updates; re-run once after the in-flight one.
            if (state.peAmountSyncInFlight) {
                state.peAmountSyncPending = true;
                return;
            }
            state.peAmountSyncInFlight = true;

            var elements = paymentElements[formId];
            var intent = state.peIntent;
            var fingerprint = buildPaymentIntentFingerprint(form);

            var updateParams = $('#ppcart-payment-form', form).serializeArray();
            updateParams.push(
                { name: "action", value: "ppcart_update_payment_intent_amt" },
                { name: "intent_id", value: intent.intent_id }
            );

            $.post(ppcart.ajax, updateParams, function(amt) {
                var updatedAmount = $.trim(String(amt));

                if (updatedAmount !== '' && !isNaN(updatedAmount)) {
                    amount = updatedAmount;
                    intent.amount = updatedAmount;

                    // Refresh the fingerprint so a later reuse check doesn't remount.
                    state.peFingerprint = fingerprint;

                    if (elements && typeof elements.fetchUpdates === 'function') {
                        elements.fetchUpdates();
                    }
                }
            }).always(function() {
                state.peAmountSyncInFlight = false;

                if (state.peAmountSyncPending) {
                    state.peAmountSyncPending = false;
                    syncPaymentElementAmount(form);
                }
            });
        }

        function mountPaymentElement(form) {
            form = form.charAt(0) === '#' ? form : '#' + form;
            var formId = form.replace('#', '');
            var state = getCheckoutIntentState(formId);

            // Don't remount under an in-flight confirm; it would swap the element/secret.
            if (state.confirmInFlight) {
                return;
            }

            var method = $('[name="pay-method"]:checked', form).val();
            if (method && method !== 'stripe') {
                return;
            }

            if ($('.ppcart-stripe #card-element', form).length === 0) {
                return;
            }

            var mountSelector = '#' + formId + ' #card-element';
            var isSubscription = !!isSubscriptionCheckout(form);

            // Reuse the mounted element when nothing relevant changed (e.g. popup reopen).
            var peFingerprint = buildPaymentIntentFingerprint(form);
            if (state.peIntent && state.peFingerprint === peFingerprint &&
                paymentElement[formId] && $(mountSelector).find('iframe').length > 0) {
                return;
            }

            var paramObj = $('#ppcart-payment-form', form).serializeArray();
            paramObj.push({ name: "action", value: isSubscription ? "ppcart_create_setup_intent" : "ppcart_create_payment_intent" });
            // Flag initial mount so the intent is created before required fields; ppcart_validate runs on submit.
            paramObj.push({ name: "ppcart_pe_mount", value: "1" });

            // Track this mount so a stale in-flight request does not overwrite a newer one.
            var mountToken = (state.peMountToken || 0) + 1;
            state.peMountToken = mountToken;

            $.post(ppcart.ajax, paramObj, function(res) {
                var response = ppcart_parse_json_response(res);

                if (state.peMountToken !== mountToken) {
                    return;
                }

                if (!response || 'undefined' !== typeof response.error || 'undefined' === typeof response.clientSecret) {
                    return;
                }

                // Persist the intent bound to the mounted element for later confirmation.
                state.peFingerprint = peFingerprint;
                state.peIntent = {
                    'clientSecret': response.clientSecret,
                    'intent_id': response.intent_id,
                    'customer_id': response.customer_id,
                    'amount': response.amount,
                    'prod_id': response.prod_id,
                    'ppcart_temp_order_id': response.ppcart_temp_order_id,
                    'ppcart_temp_order_token': response.ppcart_temp_order_token,
                    'formAction': response.formAction,
                    'directConfirmation': response.directConfirmation,
                    'is_setup_intent': response.is_setup_intent
                };

                clientSecret = response.clientSecret;
                intent_id = response.intent_id;
                customer_id = response.customer_id;
                amount = response.amount;
                ppcart_temp_order_id = response.ppcart_temp_order_id;
                ppcart_temp_order_token = response.ppcart_temp_order_token;
                prod_id = response.prod_id;
                formAction = response.formAction;
                directConfirmation = response.directConfirmation;

                if (paymentElement[formId]) {
                    try { paymentElement[formId].unmount(); } catch (e) {}
                }

                paymentElements[formId] = stripe.elements({
                    clientSecret: response.clientSecret,
                    appearance: getPaymentElementAppearance()
                });

                // Name/email come from the checkout form; hide them here but keep other fields 'auto' for Link.
                paymentElement[formId] = paymentElements[formId].create('payment', {
                    fields: {
                        billingDetails: {
                            name: 'never',
                            email: 'never'
                        }
                    }
                });

                // Tag the container so PE-specific CSS can neutralize fixed Card Element heights.
                $(mountSelector).addClass('ppcart-payment-element');

                paymentElement[formId].mount(mountSelector);

                paymentElement[formId].on('change', function(event) {
                    stripeCardComplete[formId] = !!event.complete;
                    updateSubmitButtonState(form);
                });
            });
        }

        function paymentElementBillingDetails() {
            var name = $('#first_name').val() + ' ' + $('#last_name').val();
            var email = $('#email').val();
            var details = {};
            if ($.trim(name)) {
                details.name = $.trim(name);
            }
            if (email) {
                details.email = email;
            }
            return details;
        }

        function confirmPaymentElement(intent, wrap_id, elements) {
            var state = getCheckoutIntentState(wrap_id);
            // Use the submit-captured element group; fall back to the live one.
            elements = elements || paymentElements[wrap_id];

            if (!elements || !intent || !intent.clientSecret) {
                state.confirmInFlight = false;
                displayError(ppcart_translate_frontend.missing_required, wrap_id);
                return;
            }

            var confirmParams = {
                return_url: paymentElementReturnUrl(),
                payment_method_data: {
                    billing_details: paymentElementBillingDetails()
                }
            };

            var isSetupIntent = intent.is_setup_intent === '1' || !!isSubscriptionCheckout('#' + wrap_id);

            if (isSetupIntent) {
                stripe.confirmSetup({
                    elements: elements,
                    confirmParams: confirmParams,
                    redirect: 'if_required'
                }).then(function(result) {
                    state.confirmInFlight = false;
                    if (result.error) {
                        displayError(result, wrap_id);
                        return;
                    }
                    // Bridge to the existing subscription flow using the confirmed method.
                    createSubscription(
                        intent.customer_id,
                        result.setupIntent.payment_method,
                        false,
                        wrap_id
                    );
                });
                return;
            }

            stripe.confirmPayment({
                elements: elements,
                confirmParams: confirmParams,
                redirect: 'if_required'
            }).then(function(result) {
                state.confirmInFlight = false;
                if (result.error) {
                    displayError(result, wrap_id);
                    return;
                }
                completePaymentElementPayment(result.paymentIntent, intent, wrap_id);
            });
        }

        // Mirrors confirmCardPayment's success continuation for the Payment Element.
        function completePaymentElementPayment(paymentIntent, intent, wrap_id) {
            var paramObj = {};
            $.each($('#ppcart-payment-form').serializeArray(), function(_, kv) {
                if (kv.name != 'ppcart-orderbump[]') {
                    paramObj[kv.name] = kv.value;
                }
            });
            $('input[name="ppcart-orderbump[]"]').each(function(key, value) {
                if (this.checked) {
                    paramObj['ppcart-orderbump[' + key + ']'] = $(this).val();
                }
            });
            if (intent.ppcart_temp_order_id) {
                paramObj['ppcart_temp_order_id'] = intent.ppcart_temp_order_id;
            }
            if (intent.ppcart_temp_order_token) {
                paramObj['ppcart_temp_order_token'] = intent.ppcart_temp_order_token;
            }
            paramObj['customer_id'] = intent.customer_id;
            // load_from_post() reads the customer from camelCase 'customerId'; upsells need it for off-session charges.
            paramObj['customerId'] = intent.customer_id;
            paramObj['intent_id'] = intent.intent_id;
            paramObj['amount'] = intent.amount;

            var completeConfirmedPayment = function(response, verifyTempOrder) {
                if (!paymentIntent) {
                    return;
                }
                var data = {
                    'paymentIntent': paymentIntent,
                    'action': 'ppcart_update_stripe_order_status',
                    'response': response,
                    'intent_id': intent.intent_id,
                    'ppcart-nonce': $('input[name=ppcart-nonce]').val()
                };
                if (verifyTempOrder && intent.ppcart_temp_order_id) {
                    data['ppcart_temp_order_id'] = intent.ppcart_temp_order_id;
                }
                if (verifyTempOrder && intent.ppcart_temp_order_token) {
                    data['ppcart_temp_order_token'] = intent.ppcart_temp_order_token;
                }
                if (jQuery('input[name="ppcart-auto-login"]').length == 1) {
                    data['ppcart-auto-login'] = 1;
                }
                $.post(ppcart.ajax, data, function(res) {
                    var response = ppcart_parse_json_response(res);
                    var form = document.getElementById(wrap_id);
                    form = form.getElementsByTagName('form')[0];
                    if ('undefined' !== typeof response.formAction) {
                        form.setAttribute('action', response.formAction);
                    }
                    var orderId = response.order_id || response.ppcart_order_id || response.ppcart_temp_order_id;
                    ppcartAppendCheckoutCompletionFields(form, orderId, response.ppcart_access || intent.ppcart_access);
                    var hiddenInput2 = document.createElement('input');
                    hiddenInput2.setAttribute('type', 'hidden');
                    hiddenInput2.setAttribute('name', 'intent_id');
                    hiddenInput2.setAttribute('value', intent.intent_id);
                    form.appendChild(hiddenInput2);
                    if (intent.amount == false) {
                        intent.amount = response.amount;
                    }
                    form.submit();
                });
            };

            if (intent.ppcart_temp_order_id && intent.formAction) {
                completeConfirmedPayment({
                    'order_id': intent.ppcart_temp_order_id,
                    'amount': intent.amount,
                    'formAction': intent.formAction,
                    'directConfirmation': intent.directConfirmation
                }, true);
                return;
            }

            $.post(ppcart.ajax, paramObj, function(res) {
                var response = ppcart_parse_json_response(res);
                if ('error' in response) {
                    alert(response.error);
                    if ('undefined' !== typeof response.fields) {
                        $.each(response.fields, function(i, item) {
                            invalidate_field($('[name="' + item['field'] + '"]'), item['message']);
                        });
                    }
                    $('#ppcart_card_button', '#'+wrap_id).find('span.text').text(originalText);
                    $('#ppcart_card_button', '#'+wrap_id).removeClass('running').removeAttr("disabled");
                    return false;
                } else {
                    completeConfirmedPayment(response, false);
                }
            });
        }

        function confirmCardPayment(intent, wrap_id, isExpresspayment, ev) {
            var customer_name = $('#first_name').val() + ' ' + $('#last_name').val(),
                customer_email = $('#email').val();
            var confirmCardPaymentCall;
            if(isExpresspayment){
                confirmCardPaymentCall = stripe.confirmCardPayment(
                    intent.clientSecret,
                    {payment_method: intent.paymentMethodId},
                    {handleActions: false}
                );
            }else{
                confirmCardPaymentCall = stripe.confirmCardPayment(intent.clientSecret, {
                    payment_method: {
                        card: card[wrap_id],
                        billing_details: {
                            name: customer_name,
                            email: customer_email,
                        },
                    },
                    setup_future_usage: 'off_session'
                })
            }
            confirmCardPaymentCall.then(function(result) {
                if (result.error) {
                    // Display error.message in your UI.
                    if(isExpresspayment){
                        ev.complete('fail');
                        $('#express-pay-element', '#'+wrap_id).css('pointer-events', '');
                        $('#ppcart_card_button', '#'+wrap_id).removeClass('running').removeAttr("disabled");
                    }else{
                        displayError(result, wrap_id);
                    }
                } else {
                    var paramObj = {};
                    $.each($('#ppcart-payment-form').serializeArray(), function(_, kv) {
                        if (kv.name != 'ppcart-orderbump[]') {
                            paramObj[kv.name] = kv.value;
                            if (kv.name == 'pay-method') {
                                if(isExpresspayment){
                                    paramObj[kv.name] = "stripe";
                                }
                            }
                        }
                    });
                    $('input[name="ppcart-orderbump[]"]').each(function(key, value) {
                        if (this.checked) {
                            paramObj['ppcart-orderbump[' + key + ']'] = $(this).val();
                        }
                    });
                    if (intent.ppcart_temp_order_id) {
                        paramObj['ppcart_temp_order_id'] = intent.ppcart_temp_order_id;
                    }
                    if (intent.ppcart_temp_order_token) {
                        paramObj['ppcart_temp_order_token'] = intent.ppcart_temp_order_token;
                    }
                    paramObj['customer_id'] = intent.customer_id;
                    paramObj['intent_id'] = intent.intent_id;
                    paramObj['amount'] = intent.amount;
                    var completeConfirmedPayment = function(response, verifyTempOrder) {
                        if (result.paymentIntent) {
                            var data = {
                                'paymentIntent': result.paymentIntent,
                                'action': 'ppcart_update_stripe_order_status',
                                'response': response,
                                'intent_id': intent.intent_id,
                                'ppcart-nonce': $('input[name=ppcart-nonce]').val(),
                            };
                            if (verifyTempOrder && intent.ppcart_temp_order_id) {
                                data['ppcart_temp_order_id'] = intent.ppcart_temp_order_id;
                            }
                            if (verifyTempOrder && intent.ppcart_temp_order_token) {
                                data['ppcart_temp_order_token'] = intent.ppcart_temp_order_token;
                            }
                            if (jQuery('input[name="ppcart-auto-login"]').length == 1) {
                                data['ppcart-auto-login'] = 1;
                            }
                            $.post(ppcart.ajax, data, function(res) {
                                //if (!isNaN(order_id)) {
                                // Insert the token ID into the form so it gets submitted to the server
                                if(isExpresspayment){
                                    ev.complete('success');
                                }
                                var response = ppcart_parse_json_response(res);
                                var form = document.getElementById(wrap_id);
                                form = form.getElementsByTagName('form')[0];
                                if ('undefined' !== typeof response.formAction) {
                                    form.setAttribute('action', response.formAction);
                                }
                                var orderId = response.order_id || response.ppcart_order_id || response.ppcart_temp_order_id;
                                ppcartAppendCheckoutCompletionFields(form, orderId, response.ppcart_access || intent.ppcart_access);
                                var hiddenInput2 = document.createElement('input');
                                hiddenInput2.setAttribute('type', 'hidden');
                                hiddenInput2.setAttribute('name', 'intent_id');
                                hiddenInput2.setAttribute('value', intent.intent_id);
                                form.appendChild(hiddenInput2);
                                if (intent.amount == false) {
                                    intent.amount = response.amount
                                }
                                form.submit();
                                //}
                            });
                        }
                    };

                    if (intent.ppcart_temp_order_id && intent.formAction) {
                        completeConfirmedPayment({
                            'order_id': intent.ppcart_temp_order_id,
                            'amount': intent.amount,
                            'formAction': intent.formAction,
                            'directConfirmation': intent.directConfirmation
                        }, true);
                        return;
                    }

                    $.post(ppcart.ajax, paramObj, function(res) {
                        var response = ppcart_parse_json_response(res);
                        if ('error' in response) {
                            alert(response.error);
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function(i, item) {
                                    invalidate_field($('[name="' + item['field'] + '"]'), item['message'])
                                });
                            }
                            if(isExpresspayment){
                                ev.complete('fail');
                            }
                            $('#ppcart_card_button', '#'+wrap_id).find('span.text').text(originalText);
                            $('#ppcart_card_button', '#'+wrap_id).removeClass('running').removeAttr("disabled");
                            return false;
                        } else {
                            completeConfirmedPayment(response, false);
                        }
                    });
                }
            });
        }

        // Handle paypal form submission.
        $(document).on('ppcart/orderform/submit', '#ppcart-payment-form', function(event) {

            var form = '#' + $(this).parent().attr('id');
            if ($('.pay-methods', form).length > 0 && $('[name="pay-method"]:checked', form).val() != 'paypal') {
                return false;
            }
            var paramObj = $('#ppcart-payment-form', form).serializeArray();

            paramObj.push(
                { name: "ppcart_page_id", value: ppcart.page_id },
                { name: "action", value: "ppcart_paypal_request" },
                { name: "cancel_url", value: window.location.href }
            );

            //var is_subscription = $('input[name="ppcart_product_option"]:checked').data('installments');
            $.post(ppcart.ajax, paramObj, function(res) {
                var response = ppcart_parse_json_response(res);
                if ('undefined' !== typeof response.error) {
                    alert(response.error);
                    $('#ppcart_card_button', form).removeClass('running').removeAttr("disabled");
                } else {
                    window.location.href = response.url;
                }
            });
        });
    });

    /**
     * My Account page tabs nested
     */
     jQuery('.ppcart-nav-tabs li a').click(function(event) {
        var tab_id = jQuery(this).attr('href') || '';
        var account = jQuery(this).closest('.ppcart-account-list, .ppcart-my-account');

        event.preventDefault();

        if (tab_id.charAt(0) === '#') {
            tab_id = tab_id.substring(1);
        }

        if (!tab_id) {
            return false;
        }

        if (!account.length) {
            account = jQuery(document);
        }

        account.find('.ppcart-nav-tabs li a').removeClass('active');
        account.find('.tabcontent').removeClass('active');

        jQuery(this).addClass('active');
        account.find("#" + tab_id).addClass('active');

        return false;
    })

    /**
     * Tabs left aligned on my account page
     */
    jQuery('ul.tabs-left li.tablinks').click(function() {
        var tab_id = jQuery(this).attr('data-tab');

        jQuery('ul.tabs-left li.tablinks').removeClass('active');
        jQuery('.tabcontent').removeClass('active');

        jQuery(this).addClass('active');
        jQuery("#" + tab_id).addClass('active');
    })

    /** Edit profile in my account */

    jQuery("#ppcart-edit-profile").click(function(){
        jQuery(".ep_disabled").prop('disabled',false);
        jQuery(".ep_disabled").addClass('enable-input');
        jQuery("#ppcart-edit-profile-cancel, #ppcart-all-subscription-address-wrap").show();
        jQuery("#ppcart-new-password, #ppcart-confirm-new-password").show();
        jQuery(this).hide();
        jQuery("#ppcart-update-profile").show();
    });

    jQuery("#ppcart-edit-profile-cancel").click(function(){
        cancelEditProfile();
    });

    jQuery("#ppcart-update-profile").click(function(){
        jQuery("#ppcart-loader").show();
        jQuery(this).prop('disabled',true);
        var formData = jQuery('#ppcart-update-profile-form').serialize();

        let paramObj = {
            'action': 'ppcart_update_user_profile',
            'form_data':formData,
            'nonce': jQuery('#ppcart_profile_nonce').val()
        };

        $.post(ppcart.ajax, paramObj, function (res) {
            if ('undefined' !== typeof res.error) {
                jQuery("#ppcart-profile-alert").text(res.error).addClass('alert-error');
            } else {
                jQuery("#ppcart-profile-alert").removeClass('alert-error');
                jQuery("#ppcart-profile-alert").text(res.message).addClass('alert-success');
                cancelEditProfile();
            }
            jQuery("#ppcart-update-profile").prop('disabled',false);
            jQuery("#ppcart-loader").hide();
        });

    });

    function cancelEditProfile(){
        jQuery(".ep_disabled").removeClass('enable-input');
        jQuery(".ep_disabled").prop('disabled',true);
        jQuery("#ppcart-edit-profile-cancel, #ppcart-all-subscription-address-wrap").hide();
        jQuery("#ppcart-edit-profile").show();
        jQuery("#ppcart-new-password, #ppcart-confirm-new-password").hide();
        jQuery("#ppcart-update-profile").hide();
        jQuery("#ppcart-profile-alert").remove();
    }

})(jQuery);

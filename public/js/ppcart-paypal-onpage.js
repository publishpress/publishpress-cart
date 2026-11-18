(function($) {
    'use strict';

    var settings = window.ppcart_paypal_onpage || {};
    var i18n = settings.i18n || {};
    var retryMax = parseInt(settings.retryMax, 10) || 8;
    var sdk = null;

    function parseResponse(response) {
        if (response && typeof response === 'object') {
            return response;
        }
        if (typeof response !== 'string') {
            return {};
        }
        try {
            return JSON.parse(response);
        } catch (e) {
            return { error: response };
        }
    }

    function nonce(form) {
        return $('[name="ppcart-nonce"]', form).val() || '';
    }

    function showError(form, message) {
        var $wrap = $(form);
        var $summary = $wrap.find('.ppcart-checkout-error-summary');

        if (!$summary.length) {
            $summary = $('<div class="error ppcart-checkout-error-summary" role="alert"></div>');
            var $anchor = $wrap.find('.ppcart-submit-button-section').first();

            if (!$anchor.length) {
                $anchor = $wrap.find('#ppcart_card_button').closest('.ppcart-form-group');
            }

            if ($anchor.length) {
                $anchor.before($summary);
            } else {
                $wrap.find('#ppcart-payment-form').prepend($summary);
            }
        }

        $summary.text('');
        setTimeout(function() {
            $summary.text(message || i18n.error || '');
        }, 50);
    }

    function restoreButton(form) {
        var $btn = $('#ppcart_card_button', form);
        var label = $btn.data('ppcartPaypalLabel');

        $btn.removeClass('running').removeAttr('disabled');
        if (label) {
            $btn.find('span.text').text(label);
        }
    }

    // The main script replaces the button label with "Processing" on click,
    // so the label is captured in the capture phase, before that handler runs.
    document.addEventListener('click', function(event) {
        var $btn = $(event.target).closest('#ppcart_card_button');

        if ($btn.length && !$btn.hasClass('running')) {
            $btn.data('ppcartPaypalLabel', $btn.find('span.text').text());
        }
    }, true);

    function isSubscription(form) {
        if ($('.ob-sub:checked', form).length) {
            return true;
        }

        var $plan = $('input[name="ppcart_product_option"]:checked', form);

        return $plan.data('val') === 'recurring' && !$('.ob-replace:checked', form).length;
    }

    function dropToRedirect(form) {
        window.ppcartPaypalOnpageReady = false;
        $(document).off('ppcart/orderform/submit.ppcartPaypalOnpage');
        restoreButton(form);
        showError(form, i18n.retry);
    }

    function approve(form, orderId, access, attempt) {
        var params = {
            action: 'ppcart_paypal_onpage_approve',
            'ppcart-nonce': nonce(form),
            ppcart_order_id: orderId,
            access: access
        };

        function retryOrSettle() {
            if (attempt < retryMax) {
                setTimeout(function() {
                    approve(form, orderId, access, attempt + 1);
                }, 2000);
                return;
            }
            restoreButton(form);
            showError(form, i18n.processing);
        }

        $.post(settings.ajax, params, function(res) {
            var response = parseResponse(res);

            if (response.url) {
                window.location.href = response.url;
                return;
            }
            if (response.retry) {
                retryOrSettle();
                return;
            }
            if (response.error) {
                restoreButton(form);
                showError(form, response.error);
                return;
            }
            restoreButton(form);
            showError(form, i18n.processing);
        }).fail(retryOrSettle);
    }

    function onSubmit() {
        var form = '#' + $(this).parent().attr('id');

        if ($('.pay-methods', form).length > 0 && $('[name="pay-method"]:checked', form).val() !== 'paypal') {
            return false;
        }

        var kind = isSubscription(form) ? 'subscription' : 'order';
        var params = $('#ppcart-payment-form', form).serializeArray();
        var context = { orderId: 0, access: '' };

        params.push(
            { name: 'ppcart_page_id', value: window.ppcart ? window.ppcart.page_id : '' },
            { name: 'action', value: 'ppcart_paypal_onpage_create' },
            { name: 'cancel_url', value: window.location.href },
            { name: 'kind', value: kind }
        );

        var created = new Promise(function(resolve, reject) {
            $.post(settings.ajax, params, function(res) {
                var response = parseResponse(res);
                var failure;

                if (response.fallback) {
                    failure = new Error('fallback');
                    failure.ppcartReason = 'fallback';
                    reject(failure);
                    return;
                }
                if (response.error || !(response.orderId || response.subscriptionId)) {
                    failure = new Error(response.error || i18n.error);
                    failure.ppcartReason = 'error';
                    reject(failure);
                    return;
                }

                context.orderId = response.ppcart_order_id;
                context.access = response.access;
                resolve(kind === 'subscription' ? { subscriptionId: response.subscriptionId } : { orderId: response.orderId });
            }).fail(function() {
                var failure = new Error(i18n.error);
                failure.ppcartReason = 'error';
                reject(failure);
            });
        });

        var callbacks = {
            onApprove: function() {
                approve(form, context.orderId, context.access, 1);
            },
            onCancel: function() {
                restoreButton(form);
                showError(form, i18n.cancelled);
            },
            onError: function() {
                restoreButton(form);
                showError(form, i18n.error);
            }
        };

        function onStartFailure(err) {
            if (err && err.ppcartReason === 'error') {
                restoreButton(form);
                showError(form, err.message);
                return;
            }
            dropToRedirect(form);
        }

        try {
            var session = kind === 'subscription'
                ? sdk.createPayPalSubscriptionPaymentSession(callbacks)
                : sdk.createPayPalOneTimePaymentSession(callbacks);
            var started = session.start({ presentationMode: 'auto' }, created);

            if (started && typeof started.then === 'function') {
                started.then(null, onStartFailure);
            }
        } catch (err) {
            created.then(null, function() {});
            onStartFailure(err);
        }

        return false;
    }

    $(function() {
        if (!settings.clientId || !window.Promise || !window.paypal || typeof window.paypal.createInstance !== 'function') {
            return;
        }

        window.paypal.createInstance({
            clientId: settings.clientId,
            components: ['paypal-payments', 'paypal-subscriptions'],
            pageType: 'checkout'
        }).then(function(instance) {
            sdk = instance;
            $(document).on('ppcart/orderform/submit.ppcartPaypalOnpage', '#ppcart-payment-form', onSubmit);
            window.ppcartPaypalOnpageReady = true;
        }, function() {
            window.ppcartPaypalOnpageReady = false;
        });
    });
})(jQuery);

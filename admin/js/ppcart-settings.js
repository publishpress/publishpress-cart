/**
 * PublishPress Cart - Modern Settings UI behaviour.
 *
 * Handles sidebar navigation, search, sticky save bar and panel switching.
 * The legacy ppcart-admin.js still provides compatibility hooks for older
 * settings behaviour; this file owns the modern settings shell.
 */
(function ($) {
    'use strict';

    var STORAGE_KEY = 'ppcartSettingsActiveTab';
    var $page;
    var $panels;
    var $navItems;
    var $form;
    var $savebar;
    var initialFormSnapshot = '';
    var saveBarBound = false;
    var initialised = false;
    var pendingTabRequest = null;
    var lastEmailModalTrigger = null;

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function getActiveTabFromStorage() {
        try {
            if (window.localStorage) {
                return window.localStorage.getItem(STORAGE_KEY) || '';
            }
        } catch (e) {
            // ignore
        }
        return '';
    }

    function setActiveTabInStorage(tab) {
        try {
            if (window.localStorage) {
                window.localStorage.setItem(STORAGE_KEY, tab);
            }
        } catch (e) {
            // ignore
        }
    }

    function getActiveTabId() {
        var $activePanel = $panels && $panels.filter('.is-active');
        if ($activePanel && $activePanel.length) {
            return $activePanel.attr('data-pp-tab') || '';
        }
        return '';
    }

    function syncHttpRefererToTab(tabId) {
        if (!$form || !$form.length) {
            return;
        }

        tabId = tabId || getActiveTabId() || getInitialTabId();
        if (!tabId) {
            return;
        }

        $form.find('input[name="_wp_http_referer"]').val(window.location.pathname + window.location.search + '#' + tabId);
    }

    function activateTab(tabId, options) {
        if (!initialised) {
            // Defer until init runs.
            pendingTabRequest = tabId;
            return;
        }

        options = options || {};
        if (!tabId) {
            return;
        }

        var $panel = $panels.filter('[data-pp-tab="' + tabId + '"]');
        if (!$panel.length) {
            $panel = $panels.first();
            tabId = $panel.attr('data-pp-tab');
        }

        $panels.removeClass('is-active').css('display', '');
        $panel.addClass('is-active').css('display', '');

        $navItems.removeClass('is-active').attr('aria-current', 'false');
        var $activeNav = $navItems.filter('[data-pp-tab-target="' + tabId + '"]');
        $activeNav.addClass('is-active').attr('aria-current', 'page');

        var label = $activeNav.find('.ppcart-settings__nav-label-text').text() || $activeNav.text();
        $page.find('.ppcart-settings__breadcrumb strong').text($.trim(label));

        if (!options.silent) {
            try {
                var newHash = '#' + tabId;
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', newHash);
                } else {
                    window.location.hash = newHash;
                }
            } catch (e) {
                // ignore
            }
        }

        setActiveTabInStorage(tabId);
        syncHttpRefererToTab(tabId);

        applyFilter($page.find('.ppcart-settings__search input').val() || '');

        $(document).trigger('ppcart-settings:tab-changed', [tabId]);
    }

    function getInitialTabId() {
        var hash = (window.location.hash || '').replace(/^#/, '');
        if (hash && $panels.filter('[data-pp-tab="' + hash + '"]').length) {
            return hash;
        }
        var stored = getActiveTabFromStorage();
        if (stored && $panels.filter('[data-pp-tab="' + stored + '"]').length) {
            return stored;
        }
        return $panels.first().attr('data-pp-tab') || 'general';
    }

    function bindNavigation() {
        $navItems.on('click', function (event) {
            event.preventDefault();
            var target = $(this).attr('data-pp-tab-target');
            activateTab(target);
        });

        $(window).on('hashchange', function () {
            var hash = (window.location.hash || '').replace(/^#/, '');
            if (hash && $panels.filter('[data-pp-tab="' + hash + '"]').length) {
                activateTab(hash, { silent: true });
            }
        });
    }

    function getSearchText($element) {
        var $visibleElement = $element.filter(function () {
            return !$(this).closest('[hidden]').length;
        });
        var $textSource = $visibleElement.clone();
        $textSource.find('[hidden]').remove();
        var text = $textSource.text() || '';

        $visibleElement.find('[aria-label], [title], [placeholder]').addBack('[aria-label], [title], [placeholder]').filter(function () {
            return !$(this).closest('[hidden]').length;
        }).each(function () {
            text += ' ' + ($(this).attr('aria-label') || '');
            text += ' ' + ($(this).attr('title') || '');
            text += ' ' + ($(this).attr('placeholder') || '');
        });

        return text.toLowerCase();
    }

    /* -------------------------------------------------------------------------
     * Search filter
     * ---------------------------------------------------------------------*/
    function applyFilter(rawQuery) {
        if (!$page) {
            return;
        }
        var query = $.trim(String(rawQuery || '')).toLowerCase();
        var $activePanel = $panels.filter('.is-active');
        if (!$activePanel.length) {
            return;
        }
        var $rows = $activePanel.find('.form-table tr').not('.ppcart-settings__payment-panel .form-table tr').not('[hidden]');
        var $cards = $activePanel.find('.ppcart-settings__card');
        var $emailTriggers = $activePanel.find('.email_title_trigger');
        var $paymentMethods = $activePanel.find('[data-pp-payment-method]');
        var hasMatch = false;

        if (!query) {
            $rows.removeClass('is-search-hidden is-search-match');
            $cards.removeClass('is-search-hidden');
            $emailTriggers.removeClass('is-search-hidden');
            $paymentMethods.removeClass('is-search-hidden');
            $activePanel.find('.ppcart-settings__empty').remove();
            return;
        }

        $rows.each(function () {
            var $row = $(this);
            var text = getSearchText($row);
            $row.find('input, select, textarea').each(function () {
                text += ' ' + (this.name || '').toLowerCase();
                text += ' ' + (this.value || '').toLowerCase();
            });

            if (text.indexOf(query) !== -1) {
                $row.removeClass('is-search-hidden').addClass('is-search-match');
                hasMatch = true;
            } else {
                $row.addClass('is-search-hidden').removeClass('is-search-match');
            }
        });

        $paymentMethods.each(function () {
            var $method = $(this);
            var text = getSearchText($method);
            var isMatch = text.indexOf(query) !== -1;

            $method.toggleClass('is-search-hidden', !isMatch);
            hasMatch = hasMatch || isMatch;
        });

        if ($paymentMethods.filter('.is-active.is-search-hidden').length) {
            closePaymentPanel({ silent: true });
        }

        $cards.each(function () {
            var $card = $(this);
            var totalRows = $card.find('.form-table tr').not('[hidden]').length;
            var visibleRows = $card.find('.form-table tr').not('[hidden]').not('.is-search-hidden').length;
            if (totalRows > 0 && visibleRows === 0) {
                $card.addClass('is-search-hidden');
            } else {
                $card.removeClass('is-search-hidden');
            }
        });

        $emailTriggers.each(function () {
            var $trigger = $(this);
            var $table = $trigger.next('table.form-table');
            var visibleRows = $table.find('tr').not('.is-search-hidden').length;
            if (visibleRows === 0) {
                $trigger.addClass('is-search-hidden');
            } else {
                $trigger.removeClass('is-search-hidden');
                if (!$trigger.hasClass('active')) {
                    $trigger.addClass('active');
                    $table.show();
                }
            }
        });

        $activePanel.find('.ppcart-settings__empty').remove();
        if (!hasMatch) {
            var i18n = window.ppcartSettingsI18n || {};
            var emptyHtml = '<div class="ppcart-settings__empty">'
                + '<div class="ppcart-settings__empty-icon">'
                + '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>'
                + '</div>'
                + '<h3>' + (i18n.noResults || 'No matching settings') + '</h3>'
                + '<p>' + (i18n.noResultsHint || 'Try a different keyword or clear the search.') + '</p>'
                + '</div>';
            $activePanel.append(emptyHtml);
        }
    }

    function bindSearch() {
        var $input = $page.find('.ppcart-settings__search input');
        if (!$input.length) {
            return;
        }
        var debounce;
        $input.on('input', function () {
            var value = $(this).val();
            window.clearTimeout(debounce);
            debounce = window.setTimeout(function () {
                applyFilter(value);
            }, 120);
        });
        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $(this).val('');
                applyFilter('');
            }
        });
    }

    /* -------------------------------------------------------------------------
     * Tax settings dependencies
     * ---------------------------------------------------------------------*/
    function setHidden($elements, hidden) {
        $elements.each(function () {
            if (hidden) {
                this.setAttribute('hidden', 'hidden');
            } else {
                this.removeAttribute('hidden');
            }
        });
    }

    function syncModalOpenState() {
        if (!$page || !$page.length) {
            return;
        }

        var hasOpenModal = $page.find(
            '.ppcart-settings__modal.is-open, [data-pp-payment-detail]:not([hidden]), [data-pp-integration-detail]:not([hidden])'
        ).length > 0;

        $('body').toggleClass('ppcart-settings-modal-open', hasOpenModal);
    }

    function focusFirstModalControl($scope) {
        window.setTimeout(function () {
            var focusTarget = $scope.find('input, select, textarea, button').filter(':visible').get(0);
            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }
        }, 30);
    }

    function syncTaxSettingsVisibility() {
        var $taxPanel = $('#content_tab_tax');
        if (!$taxPanel.length) {
            return;
        }

        var taxEnabled = $taxPanel.find('#_ppcart_tax_enable').is(':checked');
        var $taxRates = $taxPanel.find('[data-pp-tax-rates]');
        var $taxRatesCard = $taxRates.closest('.ppcart-settings__card');

        setHidden($taxPanel.find('tr.ppcart-settings__tax-dependent'), !taxEnabled);
        setHidden($taxRatesCard, !taxEnabled);

        if ($taxRates.length) {
            setHidden($taxRates.find('[data-pp-tax-rates-table]'), false);
        }
    }

    function bindTaxSettings() {
        var $taxPanel = $('#content_tab_tax');
        if (!$taxPanel.length) {
            return;
        }

        $page.on('change', '#_ppcart_tax_enable', function () {
            syncTaxSettingsVisibility();
            applyFilter($page.find('.ppcart-settings__search input').val() || '');
        });

        $(document).on('ppcart-settings:tab-changed', function (event, tabId) {
            if (tabId === 'tax') {
                syncTaxSettingsVisibility();
            }
        });

        syncTaxSettingsVisibility();
    }

    function syncInvoiceSettingsVisibility() {
        var $invoicePanel = $('#content_tab_invoice');
        if (!$invoicePanel.length) {
            return;
        }

        var customNumberingEnabled = $invoicePanel.find('#_ppcart_enable_invoice_number').is(':checked');
        setHidden($invoicePanel.find('tr.ppcart-settings__invoice-numbering-dependent'), !customNumberingEnabled);
    }

    function bindInvoiceSettings() {
        var $invoicePanel = $('#content_tab_invoice');
        if (!$invoicePanel.length) {
            return;
        }

        $page.on('change', '#_ppcart_enable_invoice_number', function () {
            syncInvoiceSettingsVisibility();
            applyFilter($page.find('.ppcart-settings__search input').val() || '');
        });

        $(document).on('ppcart-settings:tab-changed', function (event, tabId) {
            if (tabId === 'invoice') {
                syncInvoiceSettingsVisibility();
            }
        });

        syncInvoiceSettingsVisibility();
    }

    function syncDebugSettingsVisibility() {
        var $debugPanel = $('#content_tab_debug');
        if (!$debugPanel.length) {
            return;
        }

        var debugEnabled = $debugPanel.find('#_ppcart_enable_debug').is(':checked');
        setHidden($debugPanel.find('[data-pp-debug-meta], [data-pp-debug-actions], [data-pp-debug-log-card]'), !debugEnabled);

        var stripeWebhookLogEnabled = $debugPanel.find('#_ppcart_enable_stripe_webhook_log').is(':checked');
        setHidden(
            $debugPanel.find('[data-pp-stripe-webhook-log-meta], [data-pp-stripe-webhook-log-actions], [data-pp-stripe-webhook-log-card]'),
            !stripeWebhookLogEnabled
        );
    }

    function bindDebugSettings() {
        var $debugPanel = $('#content_tab_debug');
        if (!$debugPanel.length) {
            return;
        }

        $page.on('change', '#_ppcart_enable_debug', function () {
            syncDebugSettingsVisibility();
            applyFilter($page.find('.ppcart-settings__search input').val() || '');
        });

        $page.on('change', '#_ppcart_enable_stripe_webhook_log', function () {
            syncDebugSettingsVisibility();
            applyFilter($page.find('.ppcart-settings__search input').val() || '');
        });

        $(document).on('ppcart-settings:tab-changed', function (event, tabId) {
            if (tabId === 'debug') {
                syncDebugSettingsVisibility();
            }
        });

        syncDebugSettingsVisibility();
    }

    /* -------------------------------------------------------------------------
     * Integration cards
     * ---------------------------------------------------------------------*/
    function activateIntegration($card) {
        if (!$card || !$card.length) {
            return;
        }

        var panelId = $card.attr('data-pp-integration-panel');
        var $integrationPanel = panelId ? $('#' + panelId) : $();
        if (!$integrationPanel.length) {
            return;
        }

        var $layout = $card.closest('.ppcart-settings__integrations-layout');
        var $integrationCards = $page.find('[data-pp-integration-card]');
        var $integrationPanels = $page.find('[data-pp-integration-detail]');

        $integrationCards.removeClass('is-active');
        $page.find('[data-pp-integration-manage]').attr('aria-expanded', 'false');
        $card.addClass('is-active');
        $card.find('[data-pp-integration-manage]').attr('aria-expanded', 'true');

        $layout.addClass('has-active-integration');
        $integrationPanels.attr('hidden', 'hidden');
        $integrationPanel.removeAttr('hidden');
        syncModalOpenState();
        focusFirstModalControl($integrationPanel);
    }

    function closeIntegrationDetails($scope) {
        var $target = $scope && $scope.length ? $scope : $page;

        $target.find('[data-pp-integration-detail]').attr('hidden', 'hidden');
        $target.find('[data-pp-integration-card]').removeClass('is-active');
        $target.find('[data-pp-integration-manage]').attr('aria-expanded', 'false');
        $target
            .filter('.ppcart-settings__integrations-layout')
            .add($target.find('.ppcart-settings__integrations-layout'))
            .removeClass('has-active-integration');

        syncModalOpenState();
    }

    function applyIntegrationFilter() {
        var query = $.trim(String($page.find('[data-pp-integration-search]').val() || '')).toLowerCase();
        var category = $page.find('[data-pp-integration-filter].is-active').attr('data-pp-integration-filter') || 'all';
        var $cards = $page.find('[data-pp-integration-card]');

        $cards.each(function () {
            var $card = $(this);
            var cardCategory = $card.attr('data-pp-integration-category') || '';
            var text = getSearchText($card);
            var matchesCategory = category === 'all' || cardCategory === category;
            var matchesQuery = !query || text.indexOf(query) !== -1;
            var isVisible = matchesCategory && matchesQuery;

            $card.toggleClass('is-search-hidden', !isVisible);
        });

        var $activeVisible = $cards.filter('.is-active').not('.is-search-hidden').first();
        if ($activeVisible.length) {
            activateIntegration($activeVisible);
        } else {
            closeIntegrationDetails($page);
        }
    }

    function bindIntegrations() {
        var $integrationCards = $page.find('[data-pp-integration-card]');
        if (!$integrationCards.length) {
            return;
        }

        $integrationCards.on('click', function (event) {
            if ($(event.target).closest('.ppcart-settings__integration-toggle-wrap, .ppcart-settings__pro-lock').length) {
                return;
            }

            event.preventDefault();
            activateIntegration($(this));
        });

        $integrationCards.on('keydown', function (event) {
            if ($(event.target).closest('[data-pp-integration-manage]').length) {
                return;
            }

            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if ($(event.target).closest('.ppcart-settings__integration-toggle-wrap, .ppcart-settings__pro-lock').length) {
                return;
            }

            event.preventDefault();
            activateIntegration($(this));
        });

        $page.on('click', '.ppcart-settings__integration-toggle-wrap', function (event) {
            event.stopPropagation();
        });

        $page.on('change', '[data-pp-integration-toggle]', function () {
            var $toggle = $(this);
            var integration = $toggle.attr('data-pp-integration-toggle') || '';
            var $toggles = integration ? $page.find('[data-pp-integration-toggle="' + integration + '"]') : $toggle;
            var checked = $toggles.filter(':checked').length > 0;
            var $card = integration ? $page.find('[data-pp-integration-card="' + integration + '"]') : $toggle.closest('[data-pp-integration-card]');

            $card.toggleClass('is-enabled', checked).toggleClass('is-disabled', !checked);
        });

        $page.on('input', '[data-pp-integration-search]', applyIntegrationFilter);
        $page.on('click', '[data-pp-integration-filter]', function (event) {
            event.preventDefault();
            var $filter = $(this);
            $filter.siblings('[data-pp-integration-filter]').removeClass('is-active').attr('aria-selected', 'false');
            $filter.addClass('is-active').attr('aria-selected', 'true');
            applyIntegrationFilter();
        });
        $page.on('click', '[data-pp-integration-close]', function (event) {
            event.preventDefault();
            closeIntegrationDetails($(this).closest('.ppcart-settings__integrations-layout'));
        });

        $page.on('click', '[data-pp-integration-detail]', function (event) {
            if (event.target !== this) {
                return;
            }

            closeIntegrationDetails($(this).closest('.ppcart-settings__integrations-layout'));
        });

        $(document).on('click.ppcartSettingsIntegrationPanelOutside', function (event) {
            if (!$('#content_tab_integrations [data-pp-integration-detail]:not([hidden])').length) {
                return;
            }

            if ($(event.target).closest('.ppcart-settings__integration-panel, [data-pp-integration-card], [data-pp-integration-manage]').length) {
                return;
            }

            closeIntegrationDetails($('#content_tab_integrations'));
        });

        $(document).on('keydown.ppcartSettingsIntegrationPanel', function (event) {
            if (event.key === 'Escape' && $('#content_tab_integrations [data-pp-integration-detail]:not([hidden])').length) {
                closeIntegrationDetails($('#content_tab_integrations'));
            }
        });

        closeIntegrationDetails($page);
    }

    /* -------------------------------------------------------------------------
     * Sticky save bar
     * ---------------------------------------------------------------------*/
    function snapshotForm() {
        if (!$form || !$form.length) {
            return '';
        }
        return $form.find(':input').not('[name="_wp_http_referer"]').serialize();
    }

    function setSavebarVisible(visible) {
        if (!$savebar || !$savebar.length) {
            return;
        }
        $savebar.toggleClass('is-visible', !!visible);
    }

    function setSavingState(isSaving) {
        $page.toggleClass('is-saving', !!isSaving);
        $form.attr('aria-busy', isSaving ? 'true' : 'false');
        $page.find('.ppcart-settings__save-button').attr('aria-disabled', isSaving ? 'true' : 'false');
    }

    function bindSaveBar() {
        if (!$savebar || !$savebar.length || saveBarBound) {
            return;
        }
        saveBarBound = true;
        initialFormSnapshot = snapshotForm();

        var checkChanges = function () {
            window.setTimeout(function () {
                var currentSnapshot = snapshotForm();
                setSavebarVisible(currentSnapshot !== initialFormSnapshot);
            }, 30);
        };

        $page.on('ppcartSettingsChanged', checkChanges);
        $form.on('input change keyup', 'input, select, textarea', checkChanges);

        $form.on('submit', function () {
            syncHttpRefererToTab();
            setSavingState(true);
        });

        $savebar.find('[data-pp-discard]').on('click', function () {
            var msg = (window.ppcartSettingsI18n && window.ppcartSettingsI18n.discardConfirm) || 'Discard your unsaved changes?';
            if (!window.confirm(msg)) {
                return;
            }
            window.location.reload();
        });

        var submitForm = function (e) {
            e.preventDefault();
            syncHttpRefererToTab();
            setSavingState(true);
            // Find the bottom submit control and click it
            // natively so WP's options.php receives the expected `submit` field.
            var submitButton = $form.find('input[type="submit"]').get(0)
                || $form.find('button[type="submit"]').get(0);
            if (submitButton && typeof submitButton.click === 'function') {
                submitButton.click();
            } else if ($form[0] && typeof $form[0].requestSubmit === 'function') {
                $form[0].requestSubmit();
            } else if ($form[0]) {
                $form[0].submit();
            }
        };

        $savebar.find('[data-pp-save]').on('click', submitForm);
        $page.on('click', '.ppcart-settings__integration-panel [data-pp-save]', submitForm);
    }

    /* -------------------------------------------------------------------------
     * Email settings use a modal-based notification manager now. The legacy
     * admin script still adds accordion handlers to email h2 elements, so
     * remove those handlers and keep regular email cards open.
     * ---------------------------------------------------------------------*/
    function refineEmailAccordion() {
        var $emailPanel = $('#content_tab_emails');
        if (!$emailPanel.length) {
            return;
        }

        $emailPanel.find('.email_title_trigger').each(function () {
            var $heading = $(this);
            var $table = $heading.next('table.form-table');

            $heading.removeClass('email_title_trigger active');
            $heading.off('click');
            $heading.css('cursor', '');

            if ($table.length) {
                $table.show().css('display', '');
            }
        });
    }

    function scheduleEmailAccordionRefinement() {
        refineEmailAccordion();
        window.setTimeout(refineEmailAccordion, 0);
        window.setTimeout(refineEmailAccordion, 80);
    }

    function closeEmailModal() {
        var $modal = $page.find('.ppcart-settings__modal.is-open');
        if (!$modal.length) {
            return;
        }

        closeEmailPreview($modal);
        $modal.removeClass('is-open').attr('hidden', 'hidden');
        syncModalOpenState();

        if (lastEmailModalTrigger && document.contains(lastEmailModalTrigger)) {
            lastEmailModalTrigger.focus();
        }
        lastEmailModalTrigger = null;
    }

    function openEmailModal(modalId, trigger) {
        var $modal = $('#' + modalId);
        if (!$modal.length) {
            return;
        }

        closeEmailModal();
        lastEmailModalTrigger = trigger || null;
        $modal.removeAttr('hidden').addClass('is-open');
        syncModalOpenState();
        focusFirstModalControl($modal);
    }

    function bindEmailTemplateModals() {
        $page.on('click', '[data-pp-email-modal-open]', function (event) {
            event.preventDefault();
            openEmailModal($(this).attr('data-pp-email-modal-open'), this);
        });

        $page.on('click', '[data-pp-email-modal-close]', function (event) {
            event.preventDefault();
            closeEmailModal();
        });

        $(document).on('keydown.ppcartSettingsEmailModal', function (event) {
            if (event.key === 'Escape') {
                closeEmailModal();
            }
        });

        $page.on('click', '[data-pp-email-reset-template]', function (event) {
            event.preventDefault();

            var $button = $(this);
            var template = $button.attr('data-pp-email-reset-template') || '';
            var $modal = $button.closest('.ppcart-settings__modal');
            var message = (window.ppcartSettingsI18n && window.ppcartSettingsI18n.resetEmailConfirm) || 'Reset this email template to the built-in default?';

            if (!template || !window.ppcart_reg_vars || !window.ppcart_reg_vars.ajax_url || !window.ppcart_reg_vars.nonce) {
                return;
            }

            if (!window.confirm(message)) {
                return;
            }

            $button.prop('disabled', true).addClass('is-busy');

            $.post(window.ppcart_reg_vars.ajax_url, {
                action: 'ppcart_reset_email_template',
                nonce: window.ppcart_reg_vars.nonce,
                template: template
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                setEmailTemplateFields(response.data.fields || {});
                initialFormSnapshot = snapshotForm();
                setSavebarVisible(false);
            }).always(function () {
                $button.prop('disabled', false).removeClass('is-busy');
            });
        });

        $page.on('click', '[data-pp-email-preview]', function (event) {
            event.preventDefault();
            toggleEmailPreview($(this));
        });
    }

    function getEmailPreviewFieldValue($modal, field) {
        var optionId = $modal.attr('data-pp-email-' + field + '-field') || '';
        var editorId;
        var editor;
        var $field;

        if (!optionId) {
            return '';
        }

        editorId = 'ppcart-' + optionId;
        if (field === 'body' && window.tinymce && window.tinymce.get(editorId)) {
            editor = window.tinymce.get(editorId);
            if (typeof editor.isHidden !== 'function' || !editor.isHidden()) {
                return editor.getContent();
            }
        }

        $field = $modal.find('[name="' + optionId + '"]');
        if (!$field.length) {
            return '';
        }

        return $field.val() || '';
    }

    function getEmailPreviewPanel($modal) {
        var i18n = window.ppcartSettingsI18n || {};
        var title = i18n.previewTitle || 'Email preview';
        var $panel = $modal.find('[data-pp-email-preview-panel]');

        if ($panel.length) {
            return $panel;
        }

        $panel = $(
            '<div class="ppcart-settings__email-preview-panel" data-pp-email-preview-panel hidden>' +
                '<div class="ppcart-settings__email-preview-header">' +
                    '<strong></strong>' +
                '</div>' +
                '<iframe class="ppcart-settings__email-preview-frame" data-pp-email-preview-frame title=""></iframe>' +
            '</div>'
        );

        $panel.find('strong').text(title);
        $panel.find('iframe').attr('title', title);
        $modal.find('.ppcart-settings__modal-body').append($panel);

        return $panel;
    }

    function writeEmailPreviewPanel($modal, html) {
        var $panel = getEmailPreviewPanel($modal);
        var frame = $panel.find('[data-pp-email-preview-frame]').get(0);
        var previewDocument;

        $panel.removeAttr('hidden');

        if (!frame || !frame.contentWindow) {
            return;
        }

        previewDocument = frame.contentWindow.document;
        previewDocument.open();
        previewDocument.write(html);
        previewDocument.close();

        if (typeof $panel.get(0).scrollIntoView === 'function') {
            $panel.get(0).scrollIntoView({ block: 'nearest' });
        }
    }

    function setEmailPreviewButtonState($modal, isOpen) {
        var i18n = window.ppcartSettingsI18n || {};
        var label = isOpen ? (i18n.closePreviewButton || 'Close Preview') : (i18n.previewButton || 'Preview');

        $modal.find('[data-pp-email-preview]').each(function () {
            $(this).text(label).attr('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function closeEmailPreview($modal) {
        $modal.find('[data-pp-email-preview-panel]').attr('hidden', 'hidden');
        setEmailPreviewButtonState($modal, false);
    }

    function toggleEmailPreview($button) {
        var $modal = $button.closest('.ppcart-settings__modal');
        var $panel = $modal.find('[data-pp-email-preview-panel]');

        if ($panel.length && !$panel.is('[hidden]')) {
            closeEmailPreview($modal);
            return;
        }

        previewEmailTemplate($button);
    }

    function previewEmailTemplate($button) {
        var $modal = $button.closest('.ppcart-settings__modal');
        var template = $modal.attr('data-pp-email-template') || '';
        var i18n = window.ppcartSettingsI18n || {};

        if (!template || !window.ppcart_reg_vars || !window.ppcart_reg_vars.ajax_url || !window.ppcart_reg_vars.nonce) {
            window.alert(i18n.previewEmailFailed || 'Email preview could not be generated. Please refresh the page and try again.');
            return;
        }

        writeEmailPreviewPanel($modal, '<!doctype html><html><head><title>Email preview</title></head><body style="font-family: sans-serif; padding: 24px;">' + (i18n.previewLoading || 'Generating preview...') + '</body></html>');
        setEmailPreviewButtonState($modal, true);
        $button.prop('disabled', true).addClass('is-busy');

        $.post(window.ppcart_reg_vars.ajax_url, {
            action: 'ppcart_preview_email_template',
            nonce: window.ppcart_reg_vars.nonce,
            template: template,
            subject: getEmailPreviewFieldValue($modal, 'subject'),
            headline: getEmailPreviewFieldValue($modal, 'headline'),
            body: getEmailPreviewFieldValue($modal, 'body')
        }).done(function (response) {
            if (!$modal.hasClass('is-open')) {
                return;
            }

            if (!response || !response.success || !response.data || !response.data.html) {
                writeEmailPreviewPanel($modal, '<!doctype html><html><head><title>Email preview</title></head><body style="font-family: sans-serif; padding: 24px;">' + (i18n.previewEmailFailed || 'Email preview could not be generated. Please refresh the page and try again.') + '</body></html>');
                return;
            }

            writeEmailPreviewPanel($modal, response.data.html);
        }).fail(function () {
            if (!$modal.hasClass('is-open')) {
                return;
            }

            writeEmailPreviewPanel($modal, '<!doctype html><html><head><title>Email preview</title></head><body style="font-family: sans-serif; padding: 24px;">' + (i18n.previewEmailFailed || 'Email preview could not be generated. Please refresh the page and try again.') + '</body></html>');
        }).always(function () {
            $button.prop('disabled', false).removeClass('is-busy');
        });
    }

    function setEmailTemplateFields(fields) {
        Object.keys(fields).forEach(function (optionId) {
            var field = fields[optionId] || {};
            var value = field.value || '';
            var $field = $form.find('[name="' + optionId + '"]');
            var editorId = 'ppcart-' + optionId;

            if ($field.length) {
                $field.val(value).trigger('input').trigger('change');
            }

            if (window.tinymce && window.tinymce.get(editorId)) {
                window.tinymce.get(editorId).setContent(value);
            }
        });
    }

    /* -------------------------------------------------------------------------
     * Payment methods slide-in panels
     * ---------------------------------------------------------------------*/
    function isValidPaymentView(view) {
        return view === 'enable' || /^method:[a-z0-9_\-]+$/i.test(String(view || ''));
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

    function setStoredPaymentView(view) {
        if (!isValidPaymentView(view)) {
            return;
        }

        try {
            if (window.localStorage) {
                window.localStorage.setItem('ppcartPaymentMethodsSubtab', view);
            }
        } catch (e) {
            // ignore
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

    function updatePaymentUrl(view) {
        if (!isValidPaymentView(view) || !window.history || !window.history.replaceState) {
            return;
        }

        try {
            var url = new URL(window.location.href);
            url.searchParams.set('ppcart_payment_subtab', view);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {
            // ignore
        }
    }

    function updatePaymentActionLinks(method) {
        var view = method ? 'method:' + method : 'enable';
        var $paymentPanel = $('#content_tab_payment_methods');

        if (!$paymentPanel.length || !isValidPaymentView(view)) {
            return;
        }

        $paymentPanel.find('a[href]').each(function () {
            var $link = $(this);
            var href = String($link.attr('href') || '');

            if (!href) {
                return;
            }

            if (href.indexOf('ppcsc_action=connect_init') !== -1 && href.indexOf('return_url=') !== -1) {
                try {
                    var outer = new URL(href, window.location.origin);
                    var returnUrl = outer.searchParams.get('return_url') || '';
                    if (returnUrl) {
                        outer.searchParams.set('return_url', setQueryParamInUrlString(returnUrl, 'ppcart_payment_subtab', view));
                        $link.attr('href', outer.toString());
                    }
                } catch (e) {
                    // Keep original href on parse errors.
                }
                return;
            }

            if (href.indexOf('ppcart_stripe_connect_disconnect=1') !== -1 || href.indexOf('ppcart_stripe_connect_setup_webhook=1') !== -1) {
                $link.attr('href', setQueryParamInUrlString(href, 'ppcart_payment_subtab', view));
            }
        });
    }

    function paymentStatusClassFromLabel(label, enabled) {
        label = String(label || '').toLowerCase();

        if (!enabled) {
            return 'is-disabled';
        }

        if (label.indexOf('test') !== -1 || label.indexOf('sandbox') !== -1) {
            return 'is-test';
        }

        if (label.indexOf('live') !== -1) {
            return 'is-live';
        }

        return 'is-enabled';
    }

    function setPaymentStatus(method, enabled) {
        var $paymentPanel = $('#content_tab_payment_methods');
        var $statuses = $paymentPanel.find('[data-pp-payment-status="' + method + '"]');
        var label = enabled
            ? ($statuses.first().attr('data-enabled-label') || 'Enabled')
            : ($statuses.first().attr('data-disabled-label') || 'Disabled');
        var statusClass = paymentStatusClassFromLabel(label, enabled);

        $statuses
            .text(label)
            .removeClass('is-enabled is-disabled is-test is-live')
            .addClass(statusClass);

        $paymentPanel.find('[data-pp-payment-method="' + method + '"]')
            .toggleClass('is-enabled', enabled)
            .toggleClass('is-disabled', !enabled);
    }

    function setPaymentEnabledLabel(method, label) {
        var $paymentPanel = $('#content_tab_payment_methods');
        var $statuses = $paymentPanel.find('[data-pp-payment-status="' + method + '"]');
        var $toggles = $paymentPanel.find('[data-pp-payment-toggle="' + method + '"]');
        var enabled = $toggles.filter(':checked').length > 0;

        $statuses.attr('data-enabled-label', label);
        setPaymentStatus(method, enabled);
    }

    function applyStripeMode(value) {
        value = value === 'live' ? 'live' : 'test';

        var $paymentPanel = $('#content_tab_payment_methods');
        var $stripePanel = $paymentPanel.find('[data-pp-payment-detail="stripe"]');
        var $modeInput = $stripePanel.find('[data-pp-stripe-mode-value]');
        var previousValue = String($modeInput.val() || '');

        $modeInput.val(value);
        if (previousValue !== value) {
            $modeInput.trigger('input').trigger('change');
        }
        $page.trigger('ppcartSettingsChanged');
        $stripePanel.find('[data-pp-stripe-mode-option]')
            .removeClass('is-active')
            .attr('aria-pressed', 'false');
        $stripePanel.find('[data-pp-stripe-mode-option="' + value + '"]')
            .addClass('is-active')
            .attr('aria-pressed', 'true');

        $stripePanel.find('[data-pp-stripe-connect-mode]').each(function () {
            var $mode = $(this);
            var isVisible = $mode.attr('data-pp-stripe-connect-mode') === value;
            $mode.attr('hidden', isVisible ? null : 'hidden');
        });

        setPaymentEnabledLabel('stripe', value === 'live' ? 'Live mode' : 'Test mode');
    }

    // Maps the Checkout experience radio onto the two stored option pairs; greys out Express Payment.
    function applyStripeCheckoutExperience(value) {
        value = value === 'element' || value === 'hosted' ? value : 'classic';

        $('#_ppcart_stripe_payment_element_enable').val(value === 'element' ? '1' : '0');
        $('#_ppcart_stripe_hosted_checkout_enable').val(value === 'hosted' ? '1' : '0');

        var $wrap = $('[data-pp-stripe-experience]');
        var $express = $('#_ppcart_stripe_express_payment_enable');
        var $expressWrap = $express.closest('.checkbox-wrap');
        var disabled = value !== 'classic';
        var note = '';

        if (value === 'element') {
            note = $wrap.attr('data-note-element') || '';
        } else if (value === 'hosted') {
            note = $wrap.attr('data-note-hosted') || '';
        }

        $expressWrap.toggleClass('is-disabled', disabled);
        $express.attr('aria-disabled', disabled ? 'true' : null);

        var $note = $expressWrap.find('[data-pp-express-note]');
        if (disabled) {
            if (!$note.length) {
                $note = $('<p class="description" data-pp-express-note></p>').appendTo($expressWrap);
            }
            $note.text(note);
        } else {
            $note.remove();
        }
    }

    function syncPaymentModeLabelFromField(field) {
        var $field = $(field);
        var id = $field.attr('id') || '';
        var value = String($field.val() || '');

        if (id === '_ppcart_stripe_api') {
            applyStripeMode(value);
        }

        if (id === '_ppcart_paypal_enable_sandbox') {
            applyPayPalMode(value);
        }
    }

    function getPayPalModeValue() {
        var value = String($('#_ppcart_paypal_enable_sandbox').val() || 'enable');
        return value === 'disable' ? 'disable' : 'enable';
    }

    function applyPayPalMode(value) {
        value = value || getPayPalModeValue();
        value = value === 'disable' ? 'disable' : 'enable';

        var mode = value === 'disable' ? 'live' : 'sandbox';
        var $paymentPanel = $('#content_tab_payment_methods');
        var $paypalPanel = $paymentPanel.find('[data-pp-payment-detail="paypal"]');
        var $modeInput = $paypalPanel.find('[data-pp-paypal-mode-value]');
        var previousValue = String($modeInput.val() || '');

        $modeInput.val(value);
        if (previousValue !== value) {
            $modeInput.trigger('input').trigger('change');
        }
        $page.trigger('ppcartSettingsChanged');
        $paypalPanel.find('[data-pp-paypal-mode-option]')
            .removeClass('is-active')
            .attr('aria-pressed', 'false');
        $paypalPanel.find('[data-pp-paypal-mode-option="' + value + '"]')
            .addClass('is-active')
            .attr('aria-pressed', 'true');

        $paypalPanel.find('[data-pp-paypal-mode-field]').each(function () {
            var $field = $(this);
            var isVisible = $field.attr('data-pp-paypal-mode-field') === mode;
            $field.attr('hidden', isVisible ? null : 'hidden');
        });

        setPaymentEnabledLabel('paypal', value === 'disable' ? 'Live mode' : 'Sandbox');
    }

    function closePaymentPanel(options) {
        options = options || {};

        var $paymentPanel = $('#content_tab_payment_methods');
        var $layout = $paymentPanel.find('.ppcart-settings__payment-layout');

        $layout.removeClass('has-active-payment-panel');
        $paymentPanel.find('[data-pp-payment-method]').removeClass('is-active');
        $paymentPanel.find('[data-pp-payment-detail]').attr('hidden', 'hidden');
        $paymentPanel.find('[data-pp-payment-manage]').attr('aria-expanded', 'false');
        syncModalOpenState();

        if (!options.silent) {
            setStoredPaymentView('enable');
            updatePaymentUrl('enable');
            updatePaymentActionLinks('');
        }
    }

    function openPaymentPanel(method, options) {
        options = options || {};

        var $paymentPanel = $('#content_tab_payment_methods');
        var $layout = $paymentPanel.find('.ppcart-settings__payment-layout');
        var $method = $paymentPanel.find('[data-pp-payment-method="' + method + '"]');
        var panelId = $method.attr('data-pp-payment-panel');
        var $detail = panelId ? $('#' + panelId) : $();

        if (!$layout.length || !$method.length || !$detail.length) {
            return;
        }

        $layout.addClass('has-active-payment-panel');
        $paymentPanel.find('[data-pp-payment-method]').removeClass('is-active');
        $method.addClass('is-active');
        $paymentPanel.find('[data-pp-payment-detail]').attr('hidden', 'hidden');
        $detail.removeAttr('hidden');
        $paymentPanel.find('[data-pp-payment-manage]').attr('aria-expanded', 'false');
        $paymentPanel.find('[data-pp-payment-manage="' + method + '"]').attr('aria-expanded', 'true');
        syncModalOpenState();
        focusFirstModalControl($detail);

        if (!options.silent) {
            setStoredPaymentView('method:' + method);
            updatePaymentUrl('method:' + method);
        }

        updatePaymentActionLinks(method);

        if (method === 'stripe') {
            applyStripeMode(String($('#_ppcart_stripe_api').val() || 'test'));
            applyStripeCheckoutExperience(String($('input[name="ppcart_checkout_experience"]:checked').val() || 'classic'));
        }

        if (method === 'paypal') {
            applyPayPalMode();
        }
    }

    function applyInitialPaymentPanel() {
        var view = getPaymentViewFromUrl();

        if (!view || view === 'enable') {
            closePaymentPanel({ silent: true });
            setStoredPaymentView('enable');
            updatePaymentActionLinks('');
            return;
        }

        openPaymentPanel(view.replace('method:', ''), { silent: true });
    }

    function scheduleInitialPaymentPanel() {
        window.setTimeout(applyInitialPaymentPanel, 0);
        window.setTimeout(applyInitialPaymentPanel, 80);
    }

    function bindPaymentMethods() {
        var $paymentPanel = $('#content_tab_payment_methods');

        if (!$paymentPanel.length || !$paymentPanel.find('[data-pp-payment-method]').length) {
            return;
        }

        $page.on('click', '[data-pp-payment-manage]', function (event) {
            event.preventDefault();
            openPaymentPanel($(this).attr('data-pp-payment-manage'));
        });

        $page.on('click', '[data-pp-payment-method]', function (event) {
            if ($(event.target).closest('[data-pp-payment-toggle], .ppcart-settings__payment-toggle-wrap, [data-pp-payment-manage]').length) {
                return;
            }

            var method = $(this).attr('data-pp-payment-method');
            if ($(this).attr('data-pp-payment-panel')) {
                openPaymentPanel(method);
            }
        });

        $page.on('click', '[data-pp-payment-close]', function (event) {
            event.preventDefault();
            closePaymentPanel();
        });

        $page.on('click', '[data-pp-payment-detail]', function (event) {
            if (event.target !== this) {
                return;
            }

            closePaymentPanel();
        });

        $(document).on('click.ppcartSettingsPaymentPanelOutside', function (event) {
            if (!$('#content_tab_payment_methods [data-pp-payment-detail]:not([hidden])').length) {
                return;
            }

            if ($(event.target).closest('.ppcart-settings__payment-panel, [data-pp-payment-method], [data-pp-payment-manage]').length) {
                return;
            }

            closePaymentPanel();
        });

        $page.on('change', '[data-pp-payment-toggle]', function () {
            var $toggle = $(this);
            var method = $toggle.attr('data-pp-payment-toggle');
            var enabled = $toggle.is(':checked');

            $page.find('[data-pp-payment-toggle="' + method + '"]').not($toggle).prop('checked', enabled);
            setPaymentStatus(method, enabled);
            $page.trigger('ppcartSettingsChanged');
        });

        $page.on('change', '#_ppcart_stripe_api, #_ppcart_paypal_enable_sandbox', function () {
            syncPaymentModeLabelFromField(this);
        });

        $page.on('change', 'input[name="ppcart_checkout_experience"]', function () {
            applyStripeCheckoutExperience(this.value);
            $page.trigger('ppcartSettingsChanged');
        });

        $page.on('click', '[data-pp-stripe-mode-option]', function (event) {
            event.preventDefault();
            applyStripeMode($(this).attr('data-pp-stripe-mode-option'));
        });

        $page.on('click', '[data-pp-paypal-mode-option]', function (event) {
            event.preventDefault();
            applyPayPalMode($(this).attr('data-pp-paypal-mode-option'));
        });

        $(document).on('keydown.ppcartSettingsPaymentPanel', function (event) {
            if (event.key === 'Escape' && $('#content_tab_payment_methods [data-pp-payment-detail]:not([hidden])').length) {
                closePaymentPanel();
            }
        });

        $(document).on('ppcart-settings:tab-changed', function (event, tabId) {
            if (tabId === 'payment_methods') {
                scheduleInitialPaymentPanel();
            }
        });

        scheduleInitialPaymentPanel();
    }

    /* -------------------------------------------------------------------------
     * Maintenance — security encryption migration
     * ---------------------------------------------------------------------*/
    function bindMaintenanceSecrets() {
        var $page = $('.ppcart-settings-page');
        if (!$page.length) {
            return;
        }

        $page.on('click', '[data-ppcart-migrate-secrets]', function (event) {
            event.preventDefault();

            var $button = $(this);
            if ($button.prop('disabled')) {
                return;
            }

            var $result = $page.find('[data-ppcart-migrate-secrets-result]');
            runSecretsMigration($button, $result);
        });

        function runSecretsMigration($button, $result) {
            $button.prop('disabled', true);

            $.post(
                window.ajaxurl || '/wp-admin/admin-ajax.php',
                {
                    action: 'ppcart_migrate_secrets',
                    nonce: $button.attr('data-nonce')
                }
            ).done(function (response) {
                if (response && response.success) {
                    if ($result.length) {
                        $result.removeAttr('hidden').text(response.data.message || 'Migration complete.');
                    }
                    window.location.reload();
                    return;
                }

                var message = response && response.data && response.data.message
                    ? response.data.message
                    : 'Secret encryption migration failed.';
                if ($result.length) {
                    $result.removeAttr('hidden').text(message);
                }
                $button.prop('disabled', false);
            }).fail(function () {
                if ($result.length) {
                    $result.removeAttr('hidden').text('Secret encryption migration failed.');
                }
                $button.prop('disabled', false);
            });
        }
    }

    /* -------------------------------------------------------------------------
     * Maintenance — database schema repair
     * ---------------------------------------------------------------------*/
    function bindMaintenanceDbSchema() {
        var $page = $('.ppcart-settings-page');
        if (!$page.length) {
            return;
        }

        $page.on('click', '[data-ppcart-fix-db-schema]', function (event) {
            event.preventDefault();

            var $button = $(this);
            if ($button.prop('disabled')) {
                return;
            }

            var $result = $page.find('[data-ppcart-fix-db-schema-result]');
            runDbSchemaFix($button, $result);
        });

        function runDbSchemaFix($button, $result) {
            $button.prop('disabled', true);

            $.post(
                window.ajaxurl || '/wp-admin/admin-ajax.php',
                {
                    action: 'ppcart_fix_db_schema',
                    nonce: $button.attr('data-nonce')
                }
            ).done(function (response) {
                if (response && response.success) {
                    if ($result.length) {
                        $result.removeAttr('hidden').text(response.data.message || '');
                    }
                    window.location.reload();
                    return;
                }

                var message = response && response.data && response.data.message
                    ? response.data.message
                    : (window.ppcartSettingsI18n && window.ppcartSettingsI18n.fixDbSchemaFailed
                        ? window.ppcartSettingsI18n.fixDbSchemaFailed
                        : 'Database schema repair failed.');
                if ($result.length) {
                    $result.removeAttr('hidden').text(message);
                }
                $button.prop('disabled', false);
            }).fail(function () {
                if ($result.length) {
                    $result.removeAttr('hidden').text(
                        window.ppcartSettingsI18n && window.ppcartSettingsI18n.fixDbSchemaFailed
                            ? window.ppcartSettingsI18n.fixDbSchemaFailed
                            : 'Database schema repair failed.'
                    );
                }
                $button.prop('disabled', false);
            });
        }
    }

    /* -------------------------------------------------------------------------
     * Init
     * ---------------------------------------------------------------------*/
    function init() {
        if (initialised) {
            return;
        }
        $page = $('.ppcart-settings-page');
        if (!$page.length) {
            return;
        }
        $panels = $page.find('.ppcart-settings__panel');
        $navItems = $page.find('.ppcart-settings__nav-item');
        $form = $page.find('form.settings-options-form');
        $savebar = $page.find('.ppcart-settings__savebar');

        if (!$panels.length || !$navItems.length) {
            return;
        }

        initialised = true;
        $page.addClass('is-js-ready');

        bindNavigation();
        bindSearch();
        bindTaxSettings();
        bindInvoiceSettings();
        bindDebugSettings();
        bindSaveBar();
        bindIntegrations();
        scheduleEmailAccordionRefinement();
        bindEmailTemplateModals();
        bindPaymentMethods();
        bindMaintenanceSecrets();
        bindMaintenanceDbSchema();

        var initialTab = pendingTabRequest || getInitialTabId();
        pendingTabRequest = null;
        activateTab(initialTab, { silent: true });

        $(document).on('ppcart-settings:tab-changed', function (event, tabId) {
            if (tabId === 'emails') {
                scheduleEmailAccordionRefinement();
            }
        });
    }

    /* -------------------------------------------------------------------------
     * Override legacy ppcart_settings as early as possible so legacy code's own
     * jQuery ready handler routes through us instead of the old DOM model.
     * ---------------------------------------------------------------------*/
    var originalPpcartSettings = window.ppcart_settings;
    window.ppcart_settings = function (id) {
        if (document.querySelector('.ppcart-settings-page')) {
            if (initialised) {
                activateTab(id);
            } else {
                pendingTabRequest = id;
            }
            return;
        }
        if (typeof originalPpcartSettings === 'function') {
            originalPpcartSettings(id);
        }
    };

    ready(init);
})(jQuery);

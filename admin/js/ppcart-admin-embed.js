/**
 * Embed tab of the product edit screen: the embed type choice and the copy buttons.
 */
(function (window, document, $) {
    'use strict';

    var data = window.ppcartEmbedAdmin;
    var panel = document.querySelector('[data-ppcart-embed-panel]');

    if (!data || !panel) {
        return;
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(function () {
                return copyWithSelection(text);
            });
        }

        return copyWithSelection(text);
    }

    function copyWithSelection(text) {
        var area = document.createElement('textarea');
        var copied = false;

        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.top = '0';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();

        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(area);

        return copied ? Promise.resolve() : Promise.reject(new Error('copy failed'));
    }

    function selectContents(element) {
        var range = document.createRange();
        var selection = window.getSelection();

        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function showStatus(status, message) {
        window.clearTimeout(status.ppcartTimer);
        status.textContent = message;
        status.ppcartTimer = window.setTimeout(function () {
            status.textContent = '';
        }, 4000);
    }

    function showType(id) {
        Array.prototype.forEach.call(panel.querySelectorAll('[data-ppcart-embed-type]'), function (button) {
            button.setAttribute('aria-pressed', button.getAttribute('data-ppcart-embed-type') === id ? 'true' : 'false');
        });
        Array.prototype.forEach.call(panel.querySelectorAll('[data-ppcart-embed-block]'), function (block) {
            block.hidden = block.getAttribute('data-ppcart-embed-block') !== id;
        });
    }

    var pressed = panel.querySelector('[data-ppcart-embed-type][aria-pressed="true"]');

    if (pressed) {
        panel.classList.add('ppcart-embed-panel--picker');
        showType(pressed.getAttribute('data-ppcart-embed-type'));
    }

    $(panel).on('click', '[data-ppcart-embed-type]', function () {
        showType(this.getAttribute('data-ppcart-embed-type'));
    });

    $(panel).on('click', '[data-ppcart-embed-copy]', function () {
        var block = this.closest('.ppcart-embed-code');
        var code = block.querySelector('[data-ppcart-embed-code]');
        var status = block.querySelector('[data-ppcart-embed-copy-status]');

        copyText(code.textContent).then(
            function () {
                showStatus(status, data.i18n.copied);
            },
            function () {
                selectContents(code);
                showStatus(status, data.i18n.copyFailed);
            }
        );
    });
})(window, document, jQuery);

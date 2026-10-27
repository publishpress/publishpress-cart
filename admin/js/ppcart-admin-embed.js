/**
 * Embed tab of the product edit screen: live preview, popup button code and copy buttons.
 *
 * ppcartEmbedAdmin comes from PPCart_Admin_Embed_Tab::client_data(). Its body classes,
 * CSS variables and validation patterns are built from PPCart_Embed_Appearance, so the
 * preview shows what the embed shell renders after the product is updated.
 */
(function (window, document, $) {
    'use strict';

    var data = window.ppcartEmbedAdmin;
    var panel = document.querySelector('[data-ppcart-embed-panel]');

    if (!data || !panel) {
        return;
    }

    var preview = panel.querySelector('[data-ppcart-embed-preview]');
    var popupCode = panel.querySelector('[data-ppcart-embed-code="popup"]');
    var popupSample = panel.querySelector('[data-ppcart-embed-popup-sample]');
    var buttonTextInput = document.getElementById(data.fields.button_text);
    var patterns = {
        color: new RegExp(data.rules.color),
        fontFamily: new RegExp(data.rules.fontFamily),
        fontUrlForbidden: new RegExp(data.rules.fontUrlForbidden),
        radius: new RegExp(data.rules.radius)
    };

    function fieldValue(id) {
        var input = document.getElementById(id);

        return input ? String(input.value).trim() : '';
    }

    function color(value) {
        return patterns.color.test(value) ? value.toLowerCase() : '';
    }

    function fontFamily(value) {
        return patterns.fontFamily.test(value) ? value : '';
    }

    function fontUrl(value) {
        if (value.indexOf('https://') !== 0 || patterns.fontUrlForbidden.test(value)) {
            return '';
        }

        try {
            new URL(value);
        } catch (error) {
            return '';
        }

        return value;
    }

    function radius(value) {
        return patterns.radius.test(value) && Number(value) <= data.rules.radiusMax ? Number(value) : '';
    }

    function buttonText(value) {
        value = value.replace(/<[^>]*>/g, '').trim();

        return value !== '' && Array.from(value).length <= data.rules.buttonTextMax ? value : '';
    }

    // The same rule per key as PPCart_Embed_Appearance::sanitize(): an invalid value is ''.
    var sanitizers = {
        accent: color,
        text: color,
        background: color,
        font_family: fontFamily,
        font_url: fontUrl,
        radius: radius,
        button_text: buttonText
    };

    function currentAppearance() {
        var appearance = {};

        Object.keys(data.fields).forEach(function (key) {
            appearance[key] = sanitizers[key](fieldValue(data.fields[key]));
        });

        return appearance;
    }

    function applyFont(doc, url) {
        var link = doc.getElementById(data.fontLinkId);

        if (url === '') {
            if (link) {
                link.parentNode.removeChild(link);
            }

            return;
        }

        if (!link) {
            link = doc.createElement('link');
            link.id = data.fontLinkId;
            link.rel = 'stylesheet';
            doc.head.appendChild(link);
        }

        if (link.getAttribute('href') !== url) {
            link.setAttribute('href', url);
        }
    }

    function applyPreview(appearance) {
        var doc = preview ? preview.contentDocument : null;

        if (!doc || !doc.body || !doc.head) {
            return;
        }

        Object.keys(data.styles).forEach(function (key) {
            var style = data.styles[key];
            var value = appearance[key];
            var isSet = value !== '' && value !== undefined;

            doc.body.classList.toggle(style.class, isSet);

            if (isSet) {
                doc.documentElement.style.setProperty(style.variable, value + style.unit);
            } else {
                doc.documentElement.style.removeProperty(style.variable);
            }
        });

        applyFont(doc, appearance.font_url);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Black or white, whichever has the higher WCAG contrast ratio on the background.
    function readableTextColor(background) {
        var channels = [1, 3, 5].map(function (start) {
            var channel = parseInt(background.substr(start, 2), 16) / 255;

            return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
        });
        var luminance = 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];

        return (luminance + 0.05) / 0.05 > 1.05 / (luminance + 0.05) ? '#000000' : '#ffffff';
    }

    function popupLabel(appearance) {
        return appearance.button_text || buttonText(fieldValue(data.product.buttonText)) || data.i18n.buyNow;
    }

    function popupButtonStyle(appearance) {
        var background = appearance.accent || color(fieldValue(data.product.buttonColor)) || '#000000';

        return 'background:' + background + ';'
            + 'color:' + readableTextColor(background) + ';'
            + (appearance.radius === '' ? '' : 'border-radius:' + appearance.radius + 'px;')
            + 'font-family:' + (appearance.font_family || 'inherit') + ';'
            + 'display:inline-block;padding:14px 28px;border:0;cursor:pointer;font-size:16px;font-weight:700;line-height:1.2;';
    }

    function refreshPopupButton(appearance) {
        var style = popupButtonStyle(appearance);
        var label = popupLabel(appearance);

        if (popupCode) {
            popupCode.textContent = '<button type="button" data-ppcart-popup="' + escapeHtml(data.permalink) + '" style="' + escapeHtml(style) + '">'
                + escapeHtml(label) + '</button>\n' + data.loaderTag;
        }

        if (popupSample) {
            popupSample.setAttribute('style', style);
            popupSample.textContent = label;
        }
    }

    function refresh() {
        var appearance = currentAppearance();

        applyPreview(appearance);
        refreshPopupButton(appearance);

        if (buttonTextInput) {
            buttonTextInput.placeholder = buttonText(fieldValue(data.product.buttonText)) || data.i18n.buyNow;
        }
    }

    // Iris fires irischange before it writes the input, and the picker Clear button fires no change.
    function refreshSoon() {
        window.setTimeout(refresh, 0);
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

    // Side by side with the preview when the tab is wide enough; without this script the fields stay above it.
    function moveFieldsBesidePreview() {
        var slot = panel.querySelector('[data-ppcart-embed-fields]');

        Array.prototype.slice.call(panel.parentNode.children).forEach(function (child) {
            if (child.classList.contains('ppcart-field')) {
                slot.appendChild(child);
            }
        });
    }

    var watched = Object.keys(data.fields)
        .map(function (key) {
            return data.fields[key];
        })
        .concat([data.product.buttonColor, data.product.buttonText])
        .map(function (id) {
            return '#' + id;
        })
        .join(',');

    moveFieldsBesidePreview();

    $(document)
        .on('input change', watched, refresh)
        .on('irischange', watched, refreshSoon)
        .on('click', '.wp-picker-clear', refreshSoon);

    if (preview) {
        preview.addEventListener('load', refresh);
    }

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

    refresh();
})(window, document, jQuery);

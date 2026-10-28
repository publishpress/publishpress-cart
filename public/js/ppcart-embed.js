/**
 * Host page loader for the embedded checkout. Vanilla JS, no dependencies.
 *
 * Paste it next to a [data-ppcart-checkout="<product url>"] element for an
 * inline checkout, or next to a [data-ppcart-popup="<product url>"] button or a
 * link to <product url>?ppcart-embed=popup for a popup. The frame side is
 * public/js/ppcart-embed-frame.js.
 */
(function (window, document) {
    'use strict';

    if (window.PPCartEmbed) {
        return;
    }

    var SOURCE = 'ppcart-embed';
    var HOST_SOURCE = 'ppcart-embed-host';
    var FLAG = 'ppcart-embed';
    var START_HEIGHT = 480;

    var frames = [];
    var nextId = 1;
    var popup = null;

    function parseUrl(url) {
        try {
            return new URL(String(url), window.location.href);
        } catch (error) {
            return null;
        }
    }

    function isHttpUrl(url) {
        var parsed = parseUrl(url);

        return !!parsed && (parsed.protocol === 'http:' || parsed.protocol === 'https:');
    }

    function createFrame(url) {
        var parsed = parseUrl(url);
        var iframe = document.createElement('iframe');
        var record;

        parsed.searchParams.set(FLAG, '1');

        iframe.src = parsed.toString();
        iframe.title = 'Checkout';
        iframe.setAttribute('allow', 'payment *');
        iframe.style.width = '100%';
        iframe.style.border = '0';
        iframe.style.display = 'block';
        iframe.style.height = START_HEIGHT + 'px';

        record = { id: String(nextId++), iframe: iframe, origin: parsed.origin };
        frames.push(record);

        // The frame says hello when its script runs; load covers a frame whose hello was missed.
        iframe.addEventListener('load', function () {
            init(record);
        });

        return record;
    }

    function init(record) {
        if (record.iframe.contentWindow) {
            record.iframe.contentWindow.postMessage({ source: HOST_SOURCE, type: 'init', id: record.id }, record.origin);
        }
    }

    function removeFrame(record) {
        var index = frames.indexOf(record);

        if (index !== -1) {
            frames.splice(index, 1);
        }

        if (record.iframe.parentNode) {
            record.iframe.parentNode.removeChild(record.iframe);
        }
    }

    function frameFor(event) {
        var i;

        for (i = 0; i < frames.length; i++) {
            if (frames[i].iframe.contentWindow && event.source === frames[i].iframe.contentWindow && event.origin === frames[i].origin) {
                return frames[i];
            }
        }

        return null;
    }

    function setHeight(record, height) {
        var value = Math.ceil(Number(height));

        if (!isFinite(value) || value <= 0) {
            return;
        }

        record.iframe.style.height = value + 'px';
    }

    window.addEventListener('message', function (event) {
        var data = event.data;
        var record;

        if (!data || data.source !== SOURCE) {
            return;
        }

        record = frameFor(event);

        if (!record) {
            return;
        }

        if (data.type === 'hello') {
            init(record);
            setHeight(record, data.height);
        } else if (data.type === 'resize') {
            setHeight(record, data.height);
        } else if (data.type === 'escape') {
            if (popup && popup.record === record) {
                close();
            }
        } else if (data.type === 'navigate' && typeof data.url === 'string' && isHttpUrl(data.url)) {
            window.location.assign(data.url);
        }
    });

    function scan() {
        var nodes = document.querySelectorAll('[data-ppcart-checkout]');
        var i;
        var url;

        for (i = 0; i < nodes.length; i++) {
            url = nodes[i].getAttribute('data-ppcart-checkout');

            if (nodes[i].getAttribute('data-ppcart-embed-ready') || !isHttpUrl(url)) {
                continue;
            }

            nodes[i].setAttribute('data-ppcart-embed-ready', '1');
            nodes[i].appendChild(createFrame(url).iframe);
        }
    }

    function injectStyles() {
        var style;

        if (document.getElementById('ppcart-embed-styles')) {
            return;
        }

        style = document.createElement('style');
        style.id = 'ppcart-embed-styles';
        style.textContent = [
            '.ppcart-embed-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483000;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box}',
            '.ppcart-embed-dialog{position:relative;box-sizing:border-box;display:flex;flex-direction:column;width:100%;max-width:640px;max-height:calc(100vh - 48px);padding-top:52px;background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden}',
            '.ppcart-embed-dialog iframe{flex:1 1 auto;min-height:0;width:100%;border:0;display:block}',
            '.ppcart-embed-close{position:absolute;top:8px;right:8px;z-index:1;width:36px;height:36px;padding:0;border:0;border-radius:50%;background:rgba(0,0,0,.08);color:#111;font:22px/36px sans-serif;text-align:center;cursor:pointer}',
            '.ppcart-embed-close:hover,.ppcart-embed-close:focus{background:rgba(0,0,0,.16);outline:2px solid #111;outline-offset:2px}',
            '@media (max-width:639px){.ppcart-embed-backdrop{padding:0}.ppcart-embed-dialog{max-width:none;height:100%;max-height:none;border-radius:0}.ppcart-embed-dialog iframe{height:100%!important}}'
        ].join('');
        document.head.appendChild(style);
    }

    function open(url, trigger) {
        var overlay;
        var dialog;
        var closeButton;
        var record;

        if (!isHttpUrl(url)) {
            return;
        }

        close();
        injectStyles();

        overlay = document.createElement('div');
        overlay.className = 'ppcart-embed-backdrop';

        dialog = document.createElement('div');
        dialog.className = 'ppcart-embed-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-label', 'Checkout');

        closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'ppcart-embed-close';
        closeButton.setAttribute('aria-label', 'Close checkout');
        closeButton.textContent = '×';
        closeButton.addEventListener('click', close);

        record = createFrame(url);

        dialog.appendChild(closeButton);
        dialog.appendChild(record.iframe);
        overlay.appendChild(dialog);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                close();
            }
        });

        popup = {
            overlay: overlay,
            record: record,
            trigger: trigger || document.activeElement,
            overflow: document.documentElement.style.overflow
        };

        document.documentElement.style.overflow = 'hidden';
        document.body.appendChild(overlay);
        closeButton.focus();
    }

    function close() {
        var current = popup;

        if (!current) {
            return;
        }

        popup = null;
        removeFrame(current.record);

        if (current.overlay.parentNode) {
            current.overlay.parentNode.removeChild(current.overlay);
        }

        document.documentElement.style.overflow = current.overflow;

        if (current.trigger && typeof current.trigger.focus === 'function') {
            current.trigger.focus();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (popup && (event.key === 'Escape' || event.key === 'Esc')) {
            event.preventDefault();
            close();
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target && event.target.closest
            ? event.target.closest('[data-ppcart-popup], a[href*="ppcart-embed=popup"]')
            : null;
        var url;

        if (!target) {
            return;
        }

        url = target.hasAttribute('data-ppcart-popup') ? target.getAttribute('data-ppcart-popup') : target.getAttribute('href');

        if (!isHttpUrl(url)) {
            return;
        }

        event.preventDefault();
        open(url, target);
    });

    window.PPCartEmbed = { scan: scan, open: open, close: close };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
})(window, document);

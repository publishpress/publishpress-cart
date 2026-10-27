/**
 * Frame bridge of the embedded checkout. Runs inside the iframe that the host
 * page loader (public/js/ppcart-embed.js) creates: it answers the loader's
 * handshake, reports its height, and applies one navigation rule to script
 * navigations (window.ppcartNavigate) and form posts. See the message protocol
 * frame -> host {hello, resize, navigate, escape}, host -> frame {init}.
 */
(function (window, document) {
    'use strict';

    var SOURCE = 'ppcart-embed';
    var HOST_SOURCE = 'ppcart-embed-host';
    var FLAG = 'ppcart-embed';

    function parseUrl(url) {
        try {
            return new URL(String(url));
        } catch (error) {
            return null;
        }
    }

    /**
     * Where a navigation goes. Same origin as the frame stays in the frame and
     * keeps the embed flag; any other origin goes to the top window unchanged;
     * anything but http(s) is ignored (null).
     */
    function route(url, frameOrigin) {
        var parsed = parseUrl(url);

        if (!parsed || (parsed.protocol !== 'http:' && parsed.protocol !== 'https:')) {
            return null;
        }

        if (parsed.origin !== frameOrigin) {
            return { action: 'top', url: String(url) };
        }

        if (!parsed.searchParams.get(FLAG)) {
            parsed.searchParams.set(FLAG, '1');
        }

        return { action: 'frame', url: parsed.toString() };
    }

    window.PPCartEmbedFrame = { route: route };

    var current = parseUrl(window.location.href);

    if (!current || !current.searchParams.get(FLAG)) {
        return;
    }

    var frameOrigin = window.location.origin;
    var hostOrigin = null;
    var frameId = null;
    var heightQueued = false;

    function resolve(url) {
        try {
            return new URL(String(url), window.location.href).toString();
        } catch (error) {
            return '';
        }
    }

    function post(message, targetOrigin) {
        if (window.parent === window) {
            return;
        }

        message.source = SOURCE;

        try {
            window.parent.postMessage(message, targetOrigin);
        } catch (error) {
            // An opaque host origin cannot be a postMessage target; the host then never hears from this frame.
        }
    }

    // scrollHeight is never smaller than the viewport, so a frame sized from it could grow but never shrink.
    function height() {
        return Math.ceil(document.documentElement.getBoundingClientRect().height);
    }

    function sendHeight() {
        if (hostOrigin === null || heightQueued) {
            return;
        }

        heightQueued = true;
        window.requestAnimationFrame(function () {
            heightQueued = false;
            post({ type: 'resize', id: frameId, height: height() }, hostOrigin);
        });
    }

    function hello() {
        post({ type: 'hello', height: height() }, '*');
    }

    window.addEventListener('message', function (event) {
        var data = event.data;

        if (!data || data.source !== HOST_SOURCE || data.type !== 'init' || event.source !== window.parent) {
            return;
        }

        hostOrigin = event.origin;
        frameId = data.id;
        sendHeight();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hello);
    } else {
        hello();
    }

    window.addEventListener('load', sendHeight);

    // Key events inside the frame never reach the host page, so a popup could not close with Esc while the buyer types.
    document.addEventListener('keydown', function (event) {
        if (hostOrigin !== null && (event.key === 'Escape' || event.key === 'Esc')) {
            post({ type: 'escape', id: frameId }, hostOrigin);
        }
    });

    if (typeof window.ResizeObserver === 'function') {
        var observer = new window.ResizeObserver(sendHeight);
        observer.observe(document.documentElement);
        if (document.body) {
            observer.observe(document.body);
        }
    }

    window.ppcartNavigate = function (url) {
        var decision = route(resolve(url), frameOrigin);

        if (!decision) {
            return;
        }

        if (decision.action === 'frame') {
            window.location.assign(decision.url);
            return;
        }

        if (hostOrigin !== null) {
            post({ type: 'navigate', id: frameId, url: decision.url }, hostOrigin);
            return;
        }

        try {
            window.top.location.href = decision.url;
            return;
        } catch (error) {
            // A cross-origin top window that forbids navigation: fall through to the frame.
        }

        window.location.assign(decision.url);
    };

    // Checkout forms carry an input named "action", so form.action is that input, not the URL: read the attribute.
    function applyFormRule(form) {
        var action = form.getAttribute('action');
        var decision = route(resolve(action || window.location.href), frameOrigin);

        if (!decision) {
            return;
        }

        if (decision.action === 'frame') {
            form.setAttribute('action', decision.url);
            return;
        }

        form.setAttribute('target', '_top');
    }

    document.addEventListener('submit', function (event) {
        if (event.target && event.target.tagName === 'FORM') {
            applyFormRule(event.target);
        }
    }, true);

    // A programmatic submit() fires no submit event.
    var nativeSubmit = window.HTMLFormElement.prototype.submit;
    window.HTMLFormElement.prototype.submit = function () {
        applyFormRule(this);
        return nativeSubmit.apply(this, arguments);
    };
})(window, document);

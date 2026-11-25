const config = window.ppcartShoppingCart || {};
let nonce = config.nonce || '';
let currentCart = null;
let drawerTrigger = null;
let oldOverflow = '';
let background = [];
let drawerGeneration = 0;
let pendingCart = null;
const mounted = new WeakSet();
const channel = typeof BroadcastChannel === 'function' ? new BroadcastChannel('ppcart-shopping-cart') : null;

function overlay() {
    return document.querySelector('[data-ppcart-shopping-cart-overlay]');
}

function announce(message) {
    const panel = overlay();
    const result = panel?.querySelector('[data-ppcart-shopping-cart-result]');
    if (result && !panel.hidden) {
        result.textContent = message;
        result.hidden = false;
    }
    const region = document.querySelector('[data-ppcart-shopping-cart-announcement]');
    if (region) {
        region.classList.toggle('ppcart-shopping-cart-floating-result', !panel || panel.hidden);
        region.textContent = '';
        window.setTimeout(() => { region.textContent = message; }, 20);
    }
}

export async function requestShoppingCart(method, path = '', data = undefined) {
    const send = async (withNonce) => {
        const url = new URL(config.restUrl, window.location.href);
        // Plain permalinks carry the route in the rest_route parameter.
        if (url.searchParams.has('rest_route')) url.searchParams.set('rest_route', url.searchParams.get('rest_route') + path);
        else url.pathname += path;
        url.searchParams.set('fragments', ['drawer', 'cart', 'checkout'].filter(name => name === 'drawer' || document.querySelector(name === 'cart' ? '[data-ppcart-cart]' : '[data-ppcart-checkout-summary]')).join(','));
        const headers = { Accept: 'application/json' };
        if (withNonce && nonce) headers['X-WP-Nonce'] = nonce;
        if (data !== undefined) headers['Content-Type'] = 'application/json';
        const response = await fetch(url, {
            method, headers, credentials: 'same-origin', cache: 'no-store',
            body: data === undefined ? undefined : JSON.stringify(data),
        });
        const payload = await response.json();
        return { response, payload };
    };
    let result = await send(method !== 'GET');
    if (!result.response.ok && result.payload.code === 'rest_cookie_invalid_nonce') {
        const refreshed = await requestShoppingCart('GET');
        nonce = refreshed.nonce;
        result = await send(true);
    }
    if (!result.response.ok) {
        const error = new Error(result.payload.message || config.error);
        error.code = result.payload.code;
        error.cart = result.payload.data?.cart;
        error.fields = result.payload.data?.fields || [];
        throw error;
    }
    nonce = result.payload.nonce || nonce;
    return result.payload;
}

function updateCard(form, cart) {
    const product = form.elements.ppcart_add_to_cart.value;
    const plan = form.elements.plan_id;
    const button = form.querySelector('button[type="submit"]');
    if (cart && plan.options) {
        for (const option of plan.options) {
            option.dataset.ppcartInCart = String(cart.items.some(item => item.key === `${product}:${option.value}`));
        }
    }
    const inCart = cart
        ? cart.items.some(item => item.key === `${product}:${plan.value}`)
        : plan.options ? plan.selectedOptions[0]?.dataset.ppcartInCart === 'true' : button.dataset.ppcartInCart === 'true';
    button.dataset.ppcartInCart = String(inCart);
    button.classList.toggle('ppcart-shopping-cart-in-cart', inCart);
    button.textContent = inCart ? config.inCart : config.add;
    const price = form.closest('.ppcart-shop-card')?.querySelector('[data-ppcart-shop-price]');
    const selected = plan.selectedOptions?.[0]?.dataset.ppcartPrice;
    if (price && selected) price.textContent = selected;
}

export function applyShoppingCartResponse(response, root = document) {
    currentCart = response;
    nonce = response.nonce || nonce;
    root.querySelectorAll('[data-ppcart-cart-count]').forEach(node => { node.textContent = response.count; });
    root.querySelectorAll('[data-ppcart-cart-total]').forEach(node => {
        node.innerHTML = response.totals.rows.find(row => row.type === 'total')?.html || '';
    });
    root.querySelectorAll('.ppcart-shopping-cart-add').forEach(form => updateCard(form, response));
    const panel = overlay();
    if (panel && response.fragments?.drawer) {
        const hadFocus = panel.contains(document.activeElement);
        const template = document.createElement('template');
        template.innerHTML = response.fragments.drawer;
        const next = template.content.querySelector('[data-ppcart-drawer]');
        const current = panel.querySelector('[data-ppcart-drawer]');
        // A new drawer element would replay the slide-in animation, so refresh only its contents.
        if (current && next) current.replaceChildren(...next.childNodes);
        else panel.replaceChildren(template.content);
        if (!panel.hidden && hadFocus) panel.querySelector('[data-ppcart-close-drawer]')?.focus();
    }
    if (response.fragments?.checkout) {
        root.querySelectorAll('[data-ppcart-checkout-summary]').forEach(node => {
            const template = document.createElement('template');
            template.innerHTML = response.fragments.checkout;
            node.replaceWith(template.content.firstElementChild);
        });
        root.dispatchEvent(new CustomEvent('ppcart-shopping-cart-checkout-summary', { detail: response }));
    }
    if (response.fragments?.cart) {
        root.querySelectorAll('[data-ppcart-cart]').forEach(node => {
            const template = document.createElement('template');
            template.innerHTML = response.fragments.cart;
            const replacement = template.content.firstElementChild;
            replacement.setAttribute('style', node.getAttribute('style') || '');
            // The server renders the fragment without the block settings, so keep this block's link text.
            const continueText = node.dataset.ppcartContinueText;
            if (continueText !== undefined) {
                replacement.dataset.ppcartContinueText = continueText;
                const link = replacement.querySelector('[data-ppcart-continue]');
                if (link && continueText) link.textContent = continueText;
                else link?.remove();
            }
            node.replaceWith(replacement);
        });
    }
}

// One request at a time: a hover starts the load, and the click that follows reuses it.
function loadShoppingCart() {
    pendingCart ||= requestShoppingCart('GET').finally(() => { pendingCart = null; });
    return pendingCart;
}

function prefetchShoppingCart(event) {
    if (currentCart || pendingCart || !event.target.closest?.('[data-ppcart-open-drawer]')) return;
    loadShoppingCart().then(response => applyShoppingCartResponse(response)).catch(() => {});
}

function showDrawer(trigger) {
    const panel = overlay();
    if (!panel) return;
    if (panel.hidden) {
        drawerTrigger = trigger || document.activeElement;
        const wrapper = drawerTrigger?.closest('.ppcart-shopping-cart');
        panel.setAttribute('style', wrapper?.getAttribute('style') || '');
        oldOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        background = Array.from(document.body.children).filter(node => node !== panel && !node.contains(panel) && !node.hasAttribute('data-ppcart-shopping-cart-announcement')).map(node => [node, node.inert]);
        background.forEach(([node]) => { node.inert = true; });
    }
    panel.hidden = false;
    document.querySelectorAll('[data-ppcart-open-drawer]').forEach(node => node.setAttribute('aria-expanded', 'true'));
    panel.querySelector('[data-ppcart-close-drawer]')?.focus();
}

export async function setShoppingCartDrawerOpen(open, trigger = null) {
    const panel = overlay();
    if (!panel) return;
    const generation = ++drawerGeneration;
    if (!open) {
        panel.hidden = true;
        document.body.style.overflow = oldOverflow;
        background.forEach(([node, inert]) => { node.inert = inert; });
        background = [];
        document.querySelectorAll('[data-ppcart-open-drawer]').forEach(node => node.setAttribute('aria-expanded', 'false'));
        if (drawerTrigger?.isConnected) drawerTrigger.focus();
        return;
    }
    showDrawer(trigger);
    panel.querySelector('[data-ppcart-drawer]')?.setAttribute('aria-busy', 'true');
    try {
        const response = await loadShoppingCart();
        if (generation === drawerGeneration && !panel.hidden) applyShoppingCartResponse(response);
    } catch (error) {
        announce(error.message || config.error);
    } finally {
        panel.querySelector('[data-ppcart-drawer]')?.removeAttribute('aria-busy');
    }
}

function readCount(root) {
    const cookie = document.cookie.split('; ').find(value => value.startsWith('ppcart_cart_count='));
    const count = cookie ? Number(cookie.slice('ppcart_cart_count='.length)) : 0;
    root.querySelectorAll('.ppcart-mini-cart [data-ppcart-cart-count], [data-ppcart-shopping-cart-overlay] [data-ppcart-cart-count]').forEach(node => { node.textContent = Number.isInteger(count) && count > 0 ? count : 0; });
}

async function submit(form) {
    const adding = form.classList.contains('ppcart-shopping-cart-add');
    const button = form.querySelector('button[type="submit"]');
    if (adding && button.dataset.ppcartInCart === 'true') {
        await setShoppingCartDrawerOpen(true, button);
        return;
    }
    button.disabled = true;
    try {
        const response = adding
            ? await requestShoppingCart('POST', '/items', { product_id: Number(form.elements.ppcart_add_to_cart.value), plan_id: form.elements.plan_id.value })
            : await requestShoppingCart('DELETE', `/items/${encodeURIComponent(form.elements.ppcart_remove_from_cart.value)}`);
        applyShoppingCartResponse(response);
        channel?.postMessage('changed');
        if (adding) {
            if (response.after_add === 'mini_cart') showDrawer(button);
            else window.location.assign(response.after_add === 'checkout' ? response.links.checkout : response.links.cart);
        } else if (!overlay()?.hidden) {
            overlay().querySelector('[data-ppcart-close-drawer]')?.focus();
        } else {
            document.querySelector('[data-ppcart-cart] a, [data-ppcart-cart] button')?.focus();
        }
        announce(adding ? config.added : config.removed);
    } catch (error) {
        if (error.cart) applyShoppingCartResponse(error.cart);
        if (error.code === 'ppcart_cart_already_in_cart') {
            showDrawer(button);
            announce(config.already);
        } else if (error.code === 'ppcart_cart_conflict') {
            await setShoppingCartDrawerOpen(true, button);
            announce(error.message);
        } else announce(error.message || config.error);
    } finally {
        button.disabled = false;
    }
}

async function changeQuantity(form, button) {
    if (!button || button.disabled || form.getAttribute('aria-busy') === 'true') return;
    const key = form.elements.ppcart_cart_quantity_key.value;
    const step = button.dataset.ppcartQuantityStep;
    const scope = form.closest('[data-ppcart-shopping-cart-overlay]') ? overlay() : document.querySelector('[data-ppcart-cart]');
    form.setAttribute('aria-busy', 'true');
    try {
        const response = await requestShoppingCart('PATCH', `/items/${encodeURIComponent(key)}`, { quantity: Number(button.value) });
        applyShoppingCartResponse(response);
        channel?.postMessage('changed');
        announce(config.quantity);
    } catch (error) {
        if (error.cart) applyShoppingCartResponse(error.cart);
        announce(error.message || config.error);
    } finally {
        form.removeAttribute('aria-busy');
    }
    const control = Array.from(scope?.querySelectorAll('.ppcart-shopping-cart-quantity') || []).find(node => node.elements.ppcart_cart_quantity_key.value === key);
    const buttons = Array.from(control?.querySelectorAll('button') || []);
    (buttons.find(node => node.dataset.ppcartQuantityStep === step && !node.disabled) || buttons.find(node => !node.disabled))?.focus();
}

async function loadMoreProducts(link) {
    if (link.getAttribute('aria-busy') === 'true') return;
    const shop = link.closest('.ppcart-shop');
    const grid = shop?.querySelector('.ppcart-shop-grid');
    if (!grid) return;
    const index = Array.from(document.querySelectorAll('.ppcart-shop')).indexOf(shop);
    link.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(link.href, { credentials: 'same-origin' });
        if (!response.ok) throw new Error(config.error);
        const next = new DOMParser().parseFromString(await response.text(), 'text/html').querySelectorAll('.ppcart-shop')[index];
        const cards = Array.from(next?.querySelectorAll('.ppcart-shop-grid > .ppcart-shop-card') || []).map(card => document.importNode(card, true));
        if (!cards.length) throw new Error(config.error);
        grid.append(...cards);
        cards.forEach(card => card.querySelectorAll('.ppcart-shopping-cart-add').forEach(form => updateCard(form, currentCart)));
        const more = next.querySelector('.ppcart-shop-load-more');
        const current = link.closest('.ppcart-shop-load-more');
        if (more) current.replaceWith(document.importNode(more, true));
        else current.remove();
        cards[0].querySelector('h3 a')?.focus();
    } catch (error) {
        console.error(error);
        window.location.assign(link.href);
    }
}

export function mountShoppingCart(root = document) {
    if (mounted.has(root)) return;
    mounted.add(root);
    readCount(root);
    root.querySelectorAll('.ppcart-shopping-cart-add').forEach(form => updateCard(form, currentCart));
    root.addEventListener('submit', event => {
        const quantity = event.target.closest('.ppcart-shopping-cart-quantity');
        if (quantity) {
            event.preventDefault();
            void changeQuantity(quantity, event.submitter);
            return;
        }
        const form = event.target.closest('.ppcart-shopping-cart-add, .ppcart-shopping-cart-remove');
        if (!form) return;
        event.preventDefault();
        if (!form.querySelector('button[type="submit"]').disabled) void submit(form);
    });
    root.addEventListener('pointerover', prefetchShoppingCart);
    root.addEventListener('focusin', prefetchShoppingCart);
    root.addEventListener('change', event => {
        const form = event.target.closest('.ppcart-shopping-cart-add');
        if (form) updateCard(form, currentCart);
    });
    root.addEventListener('click', event => {
        const trigger = event.target.closest('[data-ppcart-open-drawer]');
        if (trigger) { event.preventDefault(); void setShoppingCartDrawerOpen(true, trigger); }
        const more = event.target.closest('[data-ppcart-load-more]');
        if (more) { event.preventDefault(); void loadMoreProducts(more); }
        if (event.target.closest('[data-ppcart-close-drawer]') || event.target === overlay()) void setShoppingCartDrawerOpen(false);
    });
    root.addEventListener('keydown', event => {
        const panel = overlay();
        if (!panel || panel.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); void setShoppingCartDrawerOpen(false); }
        if (event.key !== 'Tab') return;
        const focusable = Array.from(panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex="0"]')).filter(node => node.getClientRects().length);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) { event.preventDefault(); first?.focus(); }
    });
}

function stripePaymentAdapter(form) {
    const key = form.dataset.ppcartStripeKey;
    const target = form.querySelector('[data-ppcart-payment-element]');
    if (!key || !target || typeof window.Stripe !== 'function') return null;
    const amount = () => Number(document.querySelector('[data-ppcart-checkout-summary]')?.dataset.ppcartAmount || 0);
    const stripe = window.Stripe(key, { locale: form.dataset.ppcartStripeLocale || 'auto' });
    let elements = null;
    const mount = () => {
        if (elements || amount() < 1) return;
        elements = stripe.elements({ mode: 'payment', amount: amount(), currency: form.dataset.ppcartCurrency, paymentMethodTypes: ['card', 'link'] });
        elements.create('payment', { fields: { billingDetails: { name: 'never', email: 'never' } } }).mount(target);
    };
    mount();
    return {
        refresh() {
            if (elements && amount() > 0) elements.update({ amount: amount() });
            else mount();
        },
        async validate() {
            const { error } = elements ? await elements.submit() : { error: { message: config.error } };
            if (error) throw new Error(error.message);
        },
        async pay(payment, billing) {
            const { error } = await stripe.confirmPayment({
                elements, clientSecret: payment.client_secret, redirect: 'if_required',
                confirmParams: { return_url: payment.return_url || window.location.href, payment_method_data: { billing_details: billing } },
            });
            if (error) throw new Error(error.message);
        },
    };
}

export function mountShoppingCartCheckout(form, paymentAdapter = stripePaymentAdapter(form)) {
    if (mounted.has(form)) return;
    mounted.add(form);
    const button = form.querySelector('button[type="submit"]');
    const errorBox = form.querySelector('[data-ppcart-checkout-error]');
    const paymentBox = form.querySelector('[data-ppcart-checkout-payment]');
    let placed = null;
    const method = () => (new FormData(form)).get('pay-method') || '';
    const free = () => Number(document.querySelector('[data-ppcart-checkout-summary]')?.dataset.ppcartAmount || 0) < 1;
    const fail = (message, fields = []) => {
        errorBox.textContent = message || '';
        errorBox.hidden = !message;
        form.querySelectorAll('[data-ppcart-field]').forEach(node => node.classList.toggle('ppcart-shopping-cart-invalid', fields.includes(node.dataset.ppcartField)));
    };
    const showMethod = () => {
        if (paymentBox) paymentBox.hidden = free();
        form.querySelectorAll('[data-ppcart-pay-method]').forEach(node => { node.hidden = node.dataset.ppcartPayMethod !== method(); });
    };
    const leave = result => window.location.assign(result.url || result.confirmation.url || window.location.href);

    showMethod();
    form.addEventListener('change', event => { if (event.target.name === 'pay-method') showMethod(); });
    document.addEventListener('ppcart-shopping-cart-checkout-summary', event => {
        form.elements.ppcart_cart_revision.value = event.detail.revision;
        showMethod();
        paymentAdapter?.refresh();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');
        fail('');
        try {
            const card = !free() && method() === 'stripe';
            if (card && !paymentAdapter) throw new Error(config.error);
            if (card) await paymentAdapter.validate();
            const data = Object.fromEntries(new FormData(form));
            const sent = JSON.stringify(data);
            const place = async () => {
                if (!placed || placed.sent !== sent) {
                    const result = await requestShoppingCart('POST', '/checkout', data);
                    channel?.postMessage('changed');
                    applyShoppingCartResponse(result.cart);
                    placed = { result, sent: JSON.stringify(Object.fromEntries(new FormData(form))) };
                }
                return placed.result;
            };
            const paypal = !free() && method() === 'paypal' && form.hasAttribute('data-ppcart-paypal-onpage');
            if (paypal) {
                if (typeof window.ppcartPayShoppingCartWithPaypal !== 'function') throw new Error(config.error);
                await window.ppcartPayShoppingCartWithPaypal(place);
            } else {
                await place();
            }
            const { result } = placed;
            if (!['requires_payment', 'requires_approval'].includes(result.status)) return leave(result);
            if (card) await paymentAdapter.pay(result.payment, { name: `${data.first_name || ''} ${data.last_name || ''}`.trim(), email: data.email });
            const paid = await requestShoppingCart('POST', '/checkout/confirm', { order_id: result.order_id, token: result.token });
            channel?.postMessage('changed');
            leave(paid);
        } catch (error) {
            if (error.cart) applyShoppingCartResponse(error.cart);
            if (error.cart && !error.cart.count) return window.location.reload();
            fail(error.message || config.error, error.fields);
        } finally {
            button.disabled = false;
            form.removeAttribute('aria-busy');
        }
    });
}

async function settlePendingOrder(node) {
    try {
        await requestShoppingCart('POST', '/checkout/confirm', { order_id: Number(node.dataset.ppcartPendingOrder), token: node.dataset.ppcartPendingToken });
        window.location.reload();
    } catch (error) {
        // The buyer needs no message: a webhook still settles the order.
        console.error(error);
    }
}

function mountShoppingCartCheckouts() {
    document.querySelectorAll('[data-ppcart-checkout-form]').forEach(form => mountShoppingCartCheckout(form));
    document.querySelectorAll('[data-ppcart-pending-order]').forEach(node => { void settlePendingOrder(node); });
}

channel?.addEventListener('message', event => {
    if (event.data !== 'changed') return;
    currentCart = null;
    readCount(document);
    if ((overlay() && !overlay().hidden) || document.querySelector('[data-ppcart-cart], [data-ppcart-checkout-summary]')) {
        requestShoppingCart('GET').then(response => applyShoppingCartResponse(response)).catch(error => announce(error.message));
    }
});
window.addEventListener('pageshow', () => readCount(document));
const mountAll = () => { mountShoppingCart(); mountShoppingCartCheckouts(); };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountAll);
else mountAll();

/**
 * Local Stripe.js stand-in for REGRESSION_MOCKED_GATEWAYS.
 * Provides a Card Element mount with plain inputs and succeeds confirm/createPaymentMethod.
 */
(function (window) {
  'use strict';

  window.PPCART_REGRESSION_STRIPE_MOCKED = true;

  function nextId(prefix) {
    return prefix + '_mock_' + Math.random().toString(36).slice(2, 10);
  }

  function createCardElement() {
    var listeners = { change: [] };
    var mountedRoot = null;
    var fields = null;

    function readComplete() {
      if (!fields) {
        return false;
      }

      var number = fields.number.value.replace(/\D/g, '');
      var exp = fields.exp.value.trim();
      var cvc = fields.cvc.value.trim();

      return number.length >= 15 && exp.length >= 4 && cvc.length >= 3;
    }

    function emitChange() {
      var complete = readComplete();
      var event = { complete: complete, error: undefined };

      listeners.change.forEach(function (handler) {
        handler(event);
      });
    }

    function mount(selector) {
      var target = typeof selector === 'string' ? document.querySelector(selector) : selector;

      if (!target) {
        return;
      }

      mountedRoot = target;
      target.innerHTML = '';
      target.setAttribute('data-ppcart-stripe-mock', '1');

      var wrap = document.createElement('div');
      wrap.setAttribute('data-testid', 'ppcart-regression-stripe-mock');
      wrap.style.cssText = 'display:flex;flex-direction:column;gap:8px;font-family:Helvetica,Arial,sans-serif;';

      function addField(name, placeholder, testId) {
        var input = document.createElement('input');
        input.type = 'text';
        input.name = name;
        input.placeholder = placeholder;
        input.autocomplete = 'off';
        input.setAttribute('data-testid', testId);
        input.style.cssText = 'width:100%;padding:10px;border:1px solid #cbd2d9;border-radius:4px;box-sizing:border-box;';
        input.addEventListener('input', emitChange);
        input.addEventListener('change', emitChange);
        wrap.appendChild(input);
        return input;
      }

      fields = {
        number: addField('cardnumber', 'Card number', 'ppcart-regression-stripe-cardnumber'),
        exp: addField('exp-date', 'MM / YY', 'ppcart-regression-stripe-exp'),
        cvc: addField('cvc', 'CVC', 'ppcart-regression-stripe-cvc'),
        postal: addField('postal', 'ZIP', 'ppcart-regression-stripe-postal'),
      };

      target.appendChild(wrap);
      emitChange();
    }

    function unmount() {
      if (mountedRoot) {
        mountedRoot.innerHTML = '';
        mountedRoot.removeAttribute('data-ppcart-stripe-mock');
      }
      mountedRoot = null;
      fields = null;
    }

    return {
      mount: mount,
      unmount: unmount,
      addEventListener: function (eventName, handler) {
        if (!listeners[eventName]) {
          listeners[eventName] = [];
        }
        listeners[eventName].push(handler);
      },
      on: function (eventName, handler) {
        this.addEventListener(eventName, handler);
      },
      destroy: unmount,
      _isMock: true,
      _readComplete: readComplete,
    };
  }

  function createElements() {
    return {
      create: function (type) {
        if (type === 'card' || type === 'payment') {
          return createCardElement();
        }

        return createCardElement();
      },
    };
  }

  function Stripe() {
    return {
      elements: function () {
        return createElements();
      },
      confirmCardPayment: function (clientSecret) {
        var intentId = String(clientSecret || '').split('_secret_')[0] || nextId('pi');

        return Promise.resolve({
          paymentIntent: {
            id: intentId,
            object: 'payment_intent',
            client_secret: clientSecret,
            status: 'succeeded',
          },
        });
      },
      confirmCardSetup: function (clientSecret) {
        var intentId = String(clientSecret || '').split('_secret_')[0] || nextId('seti');

        return Promise.resolve({
          setupIntent: {
            id: intentId,
            object: 'setup_intent',
            client_secret: clientSecret,
            status: 'succeeded',
          },
        });
      },
      createPaymentMethod: function () {
        return Promise.resolve({
          paymentMethod: {
            id: nextId('pm'),
            object: 'payment_method',
            type: 'card',
            card: { brand: 'visa', last4: '4242' },
          },
        });
      },
      confirmPayment: function () {
        return Promise.resolve({
          paymentIntent: {
            id: nextId('pi'),
            object: 'payment_intent',
            status: 'succeeded',
          },
        });
      },
    };
  }

  window.Stripe = Stripe;
})(window);

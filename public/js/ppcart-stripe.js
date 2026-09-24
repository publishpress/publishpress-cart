/**
 * Update Subscription payment method
*/

(function ($) {
    'use strict';

    var ppcartStripe = {};
    var cardNumber = '';
    var stripe = '';
    var buttonElem = document.getElementById("ppcart_update_card_button");

    jQuery('#ppcart-update-card-open').click(function (e) {

        e.preventDefault();
        stripe = Stripe(ppcart_stripe_key[0]);
        if(jQuery("#ppcart-update-card-form").length) {
            cardNumber = ppcartStripe.mountStripeCard();
        }
        jQuery('.update-card-modal').addClass('opened');


    });

    jQuery('.closemodal').click(function (e) {
        e.preventDefault();
        jQuery('.update-card-modal').removeClass('opened');
    });


    ppcartStripe.mountStripeCard = function(){

        var elements = stripe.elements();
        var elementStyles = {
            base: {
                color: "#6F6F6F",
                lineHeight: "25px",
            },

            invalid: {
                color: "#E25950",
                "::placeholder": {
                    color: "#FFCCA5",
                },
            },
        };

        // Mount Card inputs to dom elements
        var cardNumber = elements.create("cardNumber", { style: elementStyles });
        cardNumber.mount("#ppcart-card-number");

        var cardExpiry = elements.create("cardExpiry", { style: elementStyles });
        cardExpiry.mount("#ppcart-card-expiry");

        var cardCvc = elements.create("cardCvc", { style: elementStyles });
        cardCvc.mount("#ppcart-card-cvc");

        // Display Card error
        cardNumber.addEventListener("change", function (event) {
            ppcartStripe.validateCard(event, "ppcart-card-error", "ppcart-card-number");
        });

        cardCvc.addEventListener("change", function (event) {
            ppcartStripe.validateCard(event, "ppcart-cvc-error", "ppcart-card-cvc");
        });

        cardExpiry.addEventListener("change", function (event) {
            ppcartStripe.validateCard(event, "ppcart-expiry-error", "ppcart-card-expiry");
        });

        return cardNumber;
    };

    ppcartStripe.updatePaymentMethod = function (cardNumber) {

        const cardHolderName = document.getElementById("ppcart-card-holder-name").value;

        stripe.createPaymentMethod({
            type: "card",
            card: cardNumber,
            billing_details: {
                name: cardHolderName,
            },
        }).then((result) => {

            if(result.error){
                ppcartStripe.hideLoader(buttonElem);

                if (result.error.code == "incomplete_number") {}
                if (result.error.code == "incomplete_expiry") {}
                if (result.error.code == "incomplete_cvc") {}
                if (result.error.code == "card_declined"){
                    document.getElementById("ppcart-card-error").innerHTML = result.error.message;
                    document.getElementById("ppcart-card-error").classList.add("error-label");
                }
            } else {

                ppcartStripe.updateSubscription(result.paymentMethod.id);
            }
        });
    };

    ppcartStripe.updateSubscription = function (payment_method) {

        var subscription_id = jQuery("#ppcart-subscription-id").val();

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: ppcart.ajax,
            data: {
                action: "ppcart_update_stripe_payment_method",
                payment_method: payment_method,
                post_id: subscription_id,
                nonce: jQuery("#ppcart_nonce").val(),
            },
            success: function (response) {

                ppcartStripe.hideLoader(buttonElem);
                console.log(response);

                if ('undefined' !== typeof response.error) {

                    alert(response.error);
                    return false;

                }else{

                    document.getElementById('ppcart-update-card-success').innerHTML = response.message;
                    let url = location.pathname + location.search.replace(/[\?&]action=[^&]+/, '').replace(/^&/, '?')
                    setTimeout(function(){ window.location.href = url; }, 3000);
                }
            },
        });
    };

    ppcartStripe.validateCard = function (event, errElemId, inputElemId) {

        var cardError = document.getElementById(errElemId);
        var cardInput = document.getElementById(inputElemId);

        if (event.error) {
            cardError.innerHTML = event.error.message;
            cardError.classList.add("error-label");
            cardInput.classList.add("error-border");
        } else {
            cardError.innerHTML = "";
            cardInput.classList.remove("error-border");
            cardInput.classList.remove("stripe-error");
        }
    };

    ppcartStripe.validateCardHolder = function(){
        var cardHolderName = document.getElementById("ppcart-card-holder-name").value;
        var carHolderNameInput = document.getElementById('ppcart-card-holder-name');
        var cardHolderErrElem = document.getElementById("ppcart-cardholder-error");

        if(cardHolderName===""){
            ppcartStripe.hideLoader(buttonElem);
            cardHolderErrElem.classList.add("error-label");
            cardHolderErrElem.innerHTML = 'Please enter card holder name.'
            carHolderNameInput.classList.add("error-border");

        }else{

            document.getElementById("ppcart-cardholder-error").innerHTML = '';
            cardHolderErrElem.classList.remove("error-label");
            carHolderNameInput.classList.remove("error-border");

        }
    }

    ppcartStripe.showLoader = function(elem =''){
        document.getElementById("ppcart-preloader").style.display = 'block';
        if(elem){
            elem.setAttribute('disabled','disabled');
        }
    }

    ppcartStripe.hideLoader = function(elem = ''){
        document.getElementById("ppcart-preloader").style.display = 'none';
        if(elem){
            elem.removeAttribute('disabled');
        }
    }


    var updateCardForm = document.getElementById("ppcart-update-card-form");

    if(updateCardForm){

        updateCardForm.addEventListener("submit", function(e) {
            e.preventDefault();
            ppcartStripe.showLoader(buttonElem);
            ppcartStripe.validateCardHolder();
            ppcartStripe.updatePaymentMethod(cardNumber);
        });
    }

    let searchParams = new URLSearchParams(window.location.search)
    if(searchParams.has('ppcart-plan') && searchParams.has('action') && searchParams.get('action') == 'pay') {
        jQuery('#ppcart-update-card-open').click();
    }
})(jQuery);

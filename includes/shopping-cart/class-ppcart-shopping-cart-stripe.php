<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__, 2) . '/public/controllers/payment/traits/trait-ppcart-public-payment-connect.php';

/**
 * The Stripe payment of a cart order: one PaymentIntent for the stored order total.
 */
final class PPCart_Shopping_Cart_Stripe
{
    use PPCart_Public_Payment_Connect_Trait;

    /** @var (callable():object)|null */
    private $client;

    /** @param (callable():object)|null $client Returns the Stripe client. The default uses the site keys. */
    public function __construct(?callable $client = null)
    {
        $this->client = $client;
    }

    public static function available(): bool
    {
        global $ppcart_stripe;

        return is_array($ppcart_stripe) && ! empty($ppcart_stripe['pk']) && ! empty($ppcart_stripe['sk']) && '1' == get_option('_ppcart_stripe_enable');
    }

    /** The amount of an order in the smallest unit of the store currency, as Stripe counts it. */
    public static function amount(float $amount): int
    {
        global $ppcart_currency;

        return (int) ppcart_price_in_cents($amount, $ppcart_currency);
    }

    /**
     * Create the PaymentIntent of a stored cart order and record it on the order.
     *
     * @return array{id:string,client_secret:string}|WP_Error
     */
    public function create_intent(PPCart_Order $order): array|WP_Error
    {
        global $ppcart_currency;

        if (! $this->is_connect_destination_configured()) {
            return new WP_Error('ppcart_cart_payment_unavailable', $this->get_connect_configuration_error_message());
        }
        $amount = self::amount((float) $order->amount);
        $items = count((array) $order->shopping_cart_lines);
        $args = [
            'amount'               => $amount,
            'currency'             => $ppcart_currency,
            /* translators: 1: order ID, 2: number of items in the order. */
            'description'          => sprintf(_n('Order #%1$d (%2$d item)', 'Order #%1$d (%2$d items)', $items, 'publishpress-cart'), $order->id, $items),
            'payment_method_types' => ['card'],
            'metadata'             => [
                'ppcart_checkout_type' => 'cart',
                'ppcart_order_id'      => $order->id,
                'ppcart_item_count'    => $items,
                'origin'               => get_site_url(),
            ],
        ];
        $descriptor = preg_replace('/[^0-9a-zA-Z ]/', '', substr((string) (get_option('_ppcart_stripe_descriptor') ?: get_bloginfo('name')), 0, 22));
        if ('' !== trim((string) $descriptor)) {
            $args['statement_descriptor_suffix'] = $descriptor;
        }
        $args = $this->add_connect_args_to_payment_intent($args, $amount);
        if (! ppcart_stripe_connect_fee_is_valid($args, $amount)) {
            return new WP_Error('ppcart_cart_payment_unavailable', __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart'));
        }

        try {
            // The key gives one intent for one order, also when the request runs twice.
            $intent = $this->client()->paymentIntents->create($args, ['idempotency_key' => 'ppcart-cart-order-' . $order->id]);
        } catch (Exception $e) {
            return new WP_Error('ppcart_cart_payment_failed', sanitize_text_field($e->getMessage()));
        }
        ppcart_update_post_meta($order->id, 'transaction_id', sanitize_text_field($intent->id));

        return ['id' => (string) $intent->id, 'client_secret' => (string) $intent->client_secret];
    }

    /**
     * Read the PaymentIntent of the order from Stripe. When Stripe says it succeeded, store the order as paid.
     * The browser never tells this method that a payment succeeded.
     *
     * @return true|WP_Error True when the order is paid.
     */
    public function settle(PPCart_Order $order): true|WP_Error
    {
        if (in_array($order->status, ['paid', 'completed'], true)) {
            return true;
        }
        $intent_id = (string) $order->transaction_id;
        if (! str_starts_with($intent_id, 'pi_')) {
            return new WP_Error('ppcart_cart_payment_incomplete', __('The payment is not complete.', 'publishpress-cart'));
        }
        try {
            $intent = $this->client()->paymentIntents->retrieve($intent_id);
        } catch (Exception $e) {
            return new WP_Error('ppcart_cart_payment_failed', sanitize_text_field($e->getMessage()));
        }
        if ('succeeded' !== $intent->status || absint(PPCart_Stripe_Sync::metadata($intent, 'ppcart_order_id', 0)) !== absint($order->id)) {
            return new WP_Error('ppcart_cart_payment_incomplete', __('The payment is not complete.', 'publishpress-cart'));
        }

        $order->status = 'paid';
        $order->payment_status = 'succeeded';
        ppcart_store_stripe_owned_record($order);

        return true;
    }

    private function client(): object
    {
        global $ppcart_stripe;

        return $this->client ? ($this->client)() : ppcart_stripe_client($ppcart_stripe['sk']);
    }
}

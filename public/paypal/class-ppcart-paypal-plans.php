<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Lazily created PayPal catalog products and billing plans, cached in one option.
 *
 * One catalog product per Cart product and mode. One plan per billing shape
 * key. Plans carry placeholder prices; every subscription sends real amounts
 * and cycle counts in its plan override.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Plans
{
    /**
     * @var PPCart_Paypal_Rest_Client
     */
    private $client;

    public function __construct(PPCart_Paypal_Rest_Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array  $shape        Billing shape.
     * @param int    $product_id   Cart product ID.
     * @param string $product_name Cart product name.
     * @return string PayPal plan ID, or '' when PayPal refused.
     */
    public function ensure($shape, $product_id, $product_name)
    {
        $plans = $this->read();
        $key   = $this->key($shape, $product_id);
        if (! empty($plans[ $key ]['plan'])) {
            return (string) $plans[ $key ]['plan'];
        }

        $scope   = $this->scope($product_id);
        $name    = mb_substr('' !== trim((string) $product_name) ? trim((string) $product_name) : 'Product ' . $product_id, 0, 127);
        $product = '';
        foreach ($plans as $entry) {
            if (is_array($entry) && ($entry['scope'] ?? '') === $scope && ! empty($entry['product'])) {
                $product = (string) $entry['product'];
                break;
            }
        }

        if ('' === $product) {
            $res = $this->client->request('POST', '/v1/catalogs/products', [
                'name' => $name,
                'type' => 'DIGITAL',
            ]);
            if (! PPCart_Paypal_Sync::ok($res) || empty($res['body']['id'])) {
                return '';
            }
            $product = sanitize_text_field((string) $res['body']['id']);
        }

        $res = $this->client->request('POST', '/v1/billing/plans', [
            'product_id'          => $product,
            'name'                => $name,
            'status'              => 'ACTIVE',
            'billing_cycles'      => PPCart_Paypal_Billing_Shape::plan_billing_cycles($shape),
            'payment_preferences' => [
                'auto_bill_outstanding'     => true,
                'setup_fee_failure_action'  => 'CONTINUE',
                'payment_failure_threshold' => 3,
            ],
        ]);
        if (! PPCart_Paypal_Sync::ok($res) || empty($res['body']['id'])) {
            return '';
        }

        $plan_id       = sanitize_text_field((string) $res['body']['id']);
        $plans[ $key ] = [ 'product' => $product, 'plan' => $plan_id, 'scope' => $scope ];
        $this->write($plans);

        return $plan_id;
    }

    /**
     * Drop every cached entry of a Cart product in this mode, so the next
     * ensure() creates a fresh product and plan.
     *
     * @param array $shape      Billing shape.
     * @param int   $product_id Cart product ID.
     * @return void
     */
    public function forget($shape, $product_id)
    {
        $scope = $this->scope($product_id);
        $plans = $this->read();
        unset($plans[ $this->key($shape, $product_id) ]);
        foreach ($plans as $key => $entry) {
            if (is_array($entry) && ($entry['scope'] ?? '') === $scope) {
                unset($plans[ $key ]);
            }
        }
        $this->write($plans);
    }

    /**
     * Whether a subscription create failed because PayPal does not accept the plan
     * (deleted, inactive, or owned by another account).
     *
     * @param array $res Client result.
     * @return bool
     */
    public function rejected_plan($res)
    {
        if (404 === $res['code']) {
            return true;
        }

        if (! in_array($res['code'], [ 400, 422 ], true)) {
            return false;
        }

        foreach ((array) ($res['body']['details'] ?? []) as $detail) {
            if (is_array($detail) && false !== strpos(strtoupper((string) ($detail['issue'] ?? '')), 'PLAN')) {
                return true;
            }
        }

        return false;
    }

    private function key($shape, $product_id)
    {
        return PPCart_Paypal_Billing_Shape::plan_key($shape, $this->client->mode(), $this->client->client_id(), $product_id);
    }

    private function scope($product_id)
    {
        return $this->client->mode() . ':' . md5($this->client->client_id()) . ':' . (int) $product_id;
    }

    private function read()
    {
        $plans = get_option(PPCart_Paypal_Onpage::OPTION_PLANS, []);

        return is_array($plans) ? $plans : [];
    }

    private function write($plans)
    {
        update_option(PPCart_Paypal_Onpage::OPTION_PLANS, $plans, false);
    }
}

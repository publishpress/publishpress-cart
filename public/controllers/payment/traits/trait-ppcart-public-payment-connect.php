<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Public_Payment_Connect_Trait
{
    public static function get_stripe_connect_config()
    {
        return ppcart_get_stripe_connect_config();
    }

    private function add_connect_args_to_payment_intent($args, $amount_for_stripe)
    {
        $connect = $this->get_stripe_connect_config();

        if (! $connect['enabled']) {
            return $args;
        }

        // OAuth access_token keys belong to the connected account, so destination transfer fields are invalid.
        if (! empty($connect['is_oauth_access_token_key'])) {
            if ($connect['total_fee_percent'] > 0) {
                $application_fee_amount = (int) round(((float) $amount_for_stripe) * ($connect['total_fee_percent'] / 100));
                if ($application_fee_amount < 1) {
                    $application_fee_amount = 1;
                }
                $args['application_fee_amount'] = $application_fee_amount;
            }

            return $args;
        }

        if (empty($connect['destination'])) {
            return $args;
        }

        $args['transfer_data'] = [
            'destination' => $connect['destination'],
        ];

        if ($connect['total_fee_percent'] > 0) {
            $application_fee_amount = (int) round(((float) $amount_for_stripe) * ($connect['total_fee_percent'] / 100));
            if ($application_fee_amount < 1) {
                $application_fee_amount = 1;
            }
            $args['application_fee_amount'] = $application_fee_amount;
        }

        return $args;
    }

    public static function get_stripe_resource_value($resource, $key, $default = null)
    {
        if (is_array($resource)) {
            return array_key_exists($key, $resource) ? $resource[$key] : $default;
        }

        if (is_object($resource)) {
            if (method_exists($resource, 'offsetExists') && $resource->offsetExists($key)) {
                return $resource[$key];
            }

            if (isset($resource->{$key})) {
                return $resource->{$key};
            }
        }

        return $default;
    }

    public static function get_stripe_resource_id($resource)
    {
        if (is_string($resource)) {
            return $resource;
        }

        $id = self::get_stripe_resource_value($resource, 'id', '');

        return is_string($id) ? $id : '';
    }

    private function is_connect_destination_configured()
    {
        $connect = $this->get_stripe_connect_config();
        if (! $connect['enabled']) {
            return true;
        }

        if (! empty($connect['is_oauth_access_token_key'])) {
            return true;
        }

        global $ppcart_stripe;
        $mode = isset($ppcart_stripe['mode']) ? sanitize_text_field((string) $ppcart_stripe['mode']) : 'test';

        if (function_exists('ppcart_get_stripe_platform_credentials_status')) {
            $credentials_status = ppcart_get_stripe_platform_credentials_status($mode);
            if (! empty($credentials_status['is_direct'])) {
                return true;
            }
        }

        return ! empty($connect['destination']);
    }

    private function get_connect_configuration_error_message()
    {
        return __('Stripe Connect is required but not configured correctly. Please contact the site administrator.', 'publishpress-cart');
    }

    private function add_connect_args_to_subscription($args)
    {
        $connect = $this->get_stripe_connect_config();

        if (! $connect['enabled']) {
            return $args;
        }

        // OAuth access_token keys belong to the connected account, so destination transfer fields are invalid.
        if (! empty($connect['is_oauth_access_token_key'])) {
            if ($connect['total_fee_percent'] > 0) {
                $args['application_fee_percent'] = round((float) $connect['total_fee_percent'], 2);
            }

            return $args;
        }

        if (empty($connect['destination'])) {
            return $args;
        }

        $args['transfer_data'] = [
            'destination' => $connect['destination'],
        ];

        if ($connect['total_fee_percent'] > 0) {
            $args['application_fee_percent'] = round((float) $connect['total_fee_percent'], 2);
        }

        return $args;
    }

    private function store_stripe_owned_record($record)
    {
        if (function_exists('ppcart_stripe_sync_context_run') && isset($record->pay_method) && 'stripe' === $record->pay_method) {
            return ppcart_stripe_sync_context_run(
                function () use ($record) {
                    return $record->store();
                }
            );
        }

        return $record->store();
    }
}

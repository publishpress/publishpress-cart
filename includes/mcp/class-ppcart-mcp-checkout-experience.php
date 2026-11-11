<?php

/**
 * Stripe's native combined checkout selector.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Checkout_Experience
{
    private $settings;

    /** @since 1.1.0 @param PPCart_Mcp_Settings $settings Native definitions. */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    private const ELEMENT = '_ppcart_stripe_payment_element_enable';
    private const HOSTED = '_ppcart_stripe_hosted_checkout_enable';

    /** @since 1.1.0 @param bool $write Mutation. @return array */
    public function schema($write)
    {
        return PPCart_Mcp_Input::schema($write ? [
            'experience' => ['type' => 'string', 'enum' => ['classic', 'element', 'hosted']],
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64],
            'dry_run' => ['type' => 'boolean'],
        ] : [], $write ? ['experience', 'revision'] : []);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! current_user_can('manage_options')) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage checkout settings.', 'publishpress-cart'));
        }
        $stored = PPCart_Mcp_Options::snapshots([self::ELEMENT, self::HOSTED]);
        if (is_wp_error($stored)) {
            return $stored;
        }
        foreach ($stored as $snapshot) {
            if ($snapshot['exists'] && ! is_scalar($snapshot['value'])) {
                return PPCart_Mcp_Input::error();
            }
        }
        return ['experience' => ppcart_get_stripe_checkout_experience(),
            'choices' => ['classic', 'element', 'hosted'],
            'revision' => hash('sha256', wp_json_encode($stored))];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read([]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Checkout settings changed. Read them again.', 'publishpress-cart'));
        }
        if (! $this->settings->checkout_experience_writable()) {
            return new WP_Error('ppcart_mcp_locked', __('The native checkout control is locked or unavailable.', 'publishpress-cart'));
        }
        $changes = [self::ELEMENT => 'element' === $input['experience'] ? '1' : '0',
            self::HOSTED => 'hosted' === $input['experience'] ? '1' : '0'];
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'experience' => $input['experience'], 'revision' => $before['revision']];
        }
        $snapshots = PPCart_Mcp_Options::snapshots(array_keys($changes));
        if (is_wp_error($snapshots)) {
            return $snapshots;
        }
        if (! hash_equals($input['revision'], hash('sha256', wp_json_encode($snapshots)))) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Checkout settings changed before saving.', 'publishpress-cart'));
        }
        $written = [];
        foreach ($changes as $id => $value) {
            $previous = PPCart_Mcp_Options::snapshots([$id]);
            if (is_wp_error($previous) || $previous[$id] !== $snapshots[$id]) {
                return new WP_Error('ppcart_mcp_stale_revision', __('Checkout settings changed during the save. Read current settings before retrying.', 'publishpress-cart'));
            }
            $written[$id] = $value;
            update_option($id, $value);
            $actual = PPCart_Mcp_Options::snapshots([$id]);
            if (is_wp_error($actual) || ! $actual[$id]['exists'] || $actual[$id]['value'] !== $value) {
                foreach ($written as $key => $expected) {
                    $current = PPCart_Mcp_Options::snapshots([$key]);
                    if (! is_wp_error($current) && $current[$key]['exists'] && $current[$key]['value'] === $expected) {
                        if ($snapshots[$key]['exists']) {
                            update_option($key, $snapshots[$key]['value']);
                        } else {
                            delete_option($key);
                        }
                    }
                }
                return new WP_Error('ppcart_mcp_save_failed', __('Checkout settings could not be confirmed. Read before retrying.', 'publishpress-cart'));
            }
        }
        return $this->read([]);
    }
}

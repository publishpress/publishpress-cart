<?php

/**
 * Native transactional test emails using synthetic customer data only.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Email_Test
{
    private $settings;

    /** @since 1.1.0 @param PPCart_Mcp_Settings $settings Reviewed native controls. */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        return current_user_can('manage_options') && PPCart_Mcp_Payments::can_manage();
    }

    /** @since 1.1.0 @return array */
    public function types()
    {
        $types = ['registration'];
        foreach (array_keys($this->settings->definitions()) as $id) {
            if (
                preg_match('/^_ppcart_email_([a-z_]+)_enable$/D', $id, $matches) && false === strpos($matches[1], 'affiliate')
                && ! in_array($matches[1], ['commission_paid', 'new_conversion'], true)
            ) {
                $types[] = $matches[1];
            }
        }
        return array_values(array_unique($types));
    }

    /** @since 1.1.0 @return array */
    public function schema()
    {
        return PPCart_Mcp_Input::schema(['type' => ['type' => 'string', 'maxLength' => 100], 'dry_run' => ['type' => 'boolean']], ['type']);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function send($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema());
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot send Cart test emails.', 'publishpress-cart'));
        }
        if (! in_array($input['type'], $this->types(), true)) {
            return PPCart_Mcp_Input::error();
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'type' => $input['type'], 'available_types' => $this->types(),
                'recipient' => __('Native configured administrator/test recipients', 'publishpress-cart')];
        }
        try {
            $data = ppcart_get_mock_email_preview_order_data(0);
            $result = 'registration' === $input['type'] ? ppcart_new_user_notification(false, $data, true)
                : ppcart_notification_send($input['type'], $data, true);
        } catch (Throwable $error) {
            return new WP_Error('ppcart_mcp_email_unconfirmed', __('Test email delivery could not be confirmed. Check delivery before retrying.', 'publishpress-cart'));
        }
        return $result ? ['sent' => true, 'type' => $input['type']]
            : new WP_Error('ppcart_mcp_email_failed', __('The native mail sender did not confirm delivery.', 'publishpress-cart'));
    }
}

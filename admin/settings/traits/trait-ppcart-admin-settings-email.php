<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Settings_Email_Trait
{
    /**
     * Renders a settings email template preview.
     *
     * @return void
     */
    public function preview_email_template()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-email-preview-email-template.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Renders a faithful product notification preview (inbox header + body as sent).
     *
     * @return void
     */
    public function preview_product_notification_email()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-email-preview-product-notification-email.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Sends a test copy of a notification: same composition as the live send, but overrides the recipient, drops Bcc, and flags the subject.
     *
     * @return void
     */
    public function send_product_notification_test()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-email-send-product-notification-test.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Builds a `_ppcart_notifications` entry array from the posted preview/test fields.
     *
     * Nonce and capability are verified by the calling AJAX handler before this runs.
     *
     * @return array
     */
    private function read_notification_post_entry()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in caller; email HTML sanitized just below.
        $message = isset($_POST['body']) ? wp_unslash($_POST['body']) : '';
        $message = function_exists('ppcart_kses_email_html') ? ppcart_kses_email_html($message) : wp_kses_post($message);

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in the calling AJAX handler.
        return [
            'send_to'       => isset($_POST['send_to']) ? sanitize_text_field(wp_unslash($_POST['send_to'])) : 'admin',
            'send_to_email' => isset($_POST['send_to_email']) ? sanitize_text_field(wp_unslash($_POST['send_to_email'])) : '',
            'from_name'     => isset($_POST['from_name']) ? sanitize_text_field(wp_unslash($_POST['from_name'])) : '',
            'from_email'    => isset($_POST['from_email']) ? sanitize_email(wp_unslash($_POST['from_email'])) : '',
            'reply_to'      => isset($_POST['reply_to']) ? sanitize_text_field(wp_unslash($_POST['reply_to'])) : '',
            'bcc'           => isset($_POST['bcc']) ? sanitize_text_field(wp_unslash($_POST['bcc'])) : '',
            'subject'       => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'message'       => $message,
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * Renders the notification preview as an email-client style document (header + body).
     *
     * @param string $from_name  Sender display name.
     * @param string $from_email Sender email address.
     * @param string $to         Resolved sample recipient.
     * @param string $subject    Rendered subject line.
     * @param string $body       Rendered email body HTML (as sent).
     *
     * @return string
     */
    private function render_notification_inbox_preview($from_name, $from_email, $to, $subject, $body)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-email-render-notification-inbox-preview.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Gets sample order data for a product notification preview.
     *
     * @param int $product_id Product post ID.
     *
     * @return array
     */
    private function get_product_notification_preview_order_data($product_id)
    {
        $order_info = ppcart_get_email_preview_order_data();

        $product_name = function_exists('ppcart_get_public_product_name') ? ppcart_get_public_product_name($product_id) : '';

        if ('' === trim((string) $product_name)) {
            $product_name = get_the_title($product_id);
        }

        $order_info['product_id']   = $product_id;
        $order_info['product_name'] = $product_name;

        return $order_info;
    }

    /**
     * Renders an email preview with the same shell used by the email settings UI.
     *
     * @param string $type       Email preview type.
     * @param string $headline   Rendered email headline.
     * @param string $body       Rendered email body.
     * @param array  $order_info Preview order data.
     *
     * @return string
     */
    private function render_email_preview_html($type, $headline, $body, $order_info)
    {
        $atts = [
            'type'       => $type,
            'order_info' => $order_info,
            'headline'   => $headline,
            'body'       => $body,
        ];

        return ppcart_get_email_html($atts);
    }

    /**
     * Resets a settings email template to its default values.
     *
     * @return void
     */
    public function reset_email_template()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/settings-email-reset-email-template.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}

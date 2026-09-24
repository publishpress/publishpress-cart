<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Invoices
{
    public static function generate_invoice_access_token()
    {
        return wp_generate_password(64, false, false);
    }

    public static function ensure_invoice_access_token($order_id)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return '';
        }

        $token = ppcart_get_post_meta($order_id, self::INVOICE_TOKEN_META_KEY, true);
        if (! is_string($token) || '' === $token) {
            $token = self::generate_invoice_access_token();
            ppcart_update_post_meta($order_id, self::INVOICE_TOKEN_META_KEY, $token);
        }

        return $token;
    }

    /**
     * Read the Order access token from the current request.
     *
     * Query argument name stays `token` (same as invoice/receipt PDF links).
     *
     * @return string
     */
    private static function request_order_access_token()
    {
        $token = '';

        if (function_exists('ppcart_filter_input_request')) {
            $filtered = ppcart_filter_input_request('token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (is_string($filtered)) {
                $token = trim($filtered);
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order-access proof, not a state-changing form.
        if ('' === $token && isset($_GET['token'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public order-access proof, not a state-changing form.
            $token = sanitize_text_field(wp_unslash($_GET['token']));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Public order-access proof, not a state-changing form.
        if ('' === $token && isset($_POST['token'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Public order-access proof, not a state-changing form.
            $token = sanitize_text_field(wp_unslash($_POST['token']));
        }

        return $token;
    }

    /**
     * Whether the current visitor may see a public order page or field.
     *
     * Proof is manage_options, logged-in owner, or a matching Order access token
     * in request `token`. Does not mint a token (guessed IDs must not create one).
     *
     * @param int $order_id Order post ID.
     * @return bool
     */
    public static function visitor_can_view($order_id)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $current_user_id = absint(get_current_user_id());
        $owner_id        = absint(ppcart_get_post_meta($order_id, 'user_account', true));
        if ($current_user_id && $owner_id && $current_user_id === $owner_id) {
            return true;
        }

        $provided = self::request_order_access_token();

        return self::token_matches($order_id, $provided);
    }

    /**
     * Compare a supplied access token to stored order meta without minting.
     *
     * @param int    $order_id Order post ID.
     * @param string $token    Provided token.
     * @return bool
     */
    public static function token_matches($order_id, $token)
    {
        $order_id = absint($order_id);
        $token    = is_string($token) ? $token : '';

        if (! $order_id || '' === $token) {
            return false;
        }

        $stored = ppcart_get_post_meta($order_id, self::INVOICE_TOKEN_META_KEY, true);
        $stored = is_string($stored) ? $stored : '';

        if ('' === $stored) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Query args for checkout completion URLs (PayPal return, Pro funnel steps).
     *
     * @param int $order_id Order post ID.
     * @return array<string, string>
     */
    public static function access_arg($order_id)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return [];
        }

        $token = self::ensure_invoice_access_token($order_id);
        if ('' === $token) {
            return [];
        }

        return [
            'ppcart-access' => $token,
        ];
    }

    /**
     * Public thank-you URL with order ID and Order access token.
     *
     * Do not use for My Account links (those stay id-only behind login).
     *
     * @param string $base     Destination URL.
     * @param int    $order_id Order post ID.
     * @return string
     */
    public static function confirmation_url($base, $order_id)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return (string) $base;
        }

        $args = [
            'ppcart-order' => $order_id,
        ];

        $token = self::ensure_invoice_access_token($order_id);
        if ('' !== $token) {
            $args['token'] = $token;
        }

        return add_query_arg($args, $base);
    }

    private static function build_invoice_access_link($order_id, $download, $type)
    {
        $order_id = absint($order_id);
        if (! $order_id) {
            return false;
        }

        $token = self::ensure_invoice_access_token($order_id);
        if ('' === $token) {
            return false;
        }

        return add_query_arg(
            [
            'ppcart-invoice' => $order_id,
            'id'         => $order_id,
            'token'      => $token,
            'dl'         => (int) $download,
            'type'       => $type,
            ],
            home_url('/')
        );
    }

    public function output_invoice($download)
    {
        $this->get_invoice($stream = true, $download);
    }

    public function get_invoice($stream = false, $download = false)
    {
        $order = $this;
        // Pro redefines PPCART_BASE_FILE to the Pro bootstrap. Use the Free plugin directory.
        $file = defined('PPCART_BASE_DIR')
            ? PPCART_BASE_DIR . 'public/partials/invoice-pdf.php'
            : dirname(__DIR__, 3) . '/public/partials/invoice-pdf.php';

        if (! file_exists($file)) {
            return false;
        }

        // Not require_once: a single request may render more than one invoice.
        if (!$stream) {
            return require($file);
        } else {
            require($file);
        }
    }

    public function set_invoice_number()
    {
        if (get_option('_ppcart_enable_invoice_number', false)) {
            $invoice_number = get_option('_ppcart_invoice_start_number', 1);
            $invoice_format = ppcart_get_invoice_format();
            $invoice_sufix = get_option('_ppcart_invoice_sufix', "");
            $invoice_prefix = get_option('_ppcart_invoice_prefix', "");
            $order_number_length = (int)get_option('_ppcart_invoice_length', 0);
            if ($order_number_length) {
                $invoice_number = sprintf("%0{$order_number_length}d", $invoice_number);
            }

            switch ($invoice_format) {
                case 'ppcart_ns':
                    $formatted = $invoice_number . $invoice_sufix;
                    break;
                case 'ppcart_pn':
                    $formatted = $invoice_prefix . $invoice_number;
                    break;
                case 'ppcart_n':
                    $formatted = $invoice_number;
                    break;
                default:
                    $formatted = $invoice_prefix . $invoice_number . $invoice_sufix;
                    break;
            }
            $replacements = [
              '{D}'    => date_i18n('j'),
              '{DD}'   => date_i18n('d'),
              '{M}'    => date_i18n('n'),
              '{MM}'   => date_i18n('m'),
              '{YY}'   => date_i18n('y'),
              '{YYYY}' => date_i18n('Y'),
              '{H}'    => date_i18n('G'),
              '{HH}'   => date_i18n('H'),
              '{N}'    => date_i18n('i'),
              '{S}'    => date_i18n('s'),
            ];

            $formatted_order_number = str_ireplace(array_keys($replacements), $replacements, $formatted);
            $this->invoice_number = $formatted_order_number;
            $invoice_number++;
            update_option('_ppcart_invoice_start_number', $invoice_number);
        }
    }

    public function invoice_link($download = true)
    {
        if ($this->status != 'pending-payment' || $this->pay_method == 'cod') {
            $invoice_id = $this->id;
            $download = (int) $download;
            if (isset($this->ob_parent) || isset($this->us_parent) || isset($this->ds_parent)) {
                if (isset($this->ob_parent)) {
                    $invoice_id = $this->ob_parent;
                } elseif (isset($this->ds_parent)) {
                    $invoice_id = $this->ds_parent;
                } else {
                    $invoice_id = $this->us_parent;
                }
            }
            return self::build_invoice_access_link($invoice_id, $download, 'invoice');
        } else {
            return false;
        }
    }

    public function invoice_link_html($label = false)
    {
        if ($link = $this->invoice_link()) {
            if ($label === false) {
                $label = esc_html__('Download Invoice', 'publishpress-cart');
            } else {
                $label = esc_html((string) $label);
            }
            return '<a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        } else {
            return false;
        }
    }

    public function receipt_link($download = true)
    {
        if ($this->status == 'paid' || $this->status == 'completed' || $this->status == 'refunded') {
            $receipt_id = $this->id;
            $download = (int) $download;
            if (isset($this->ob_parent) || isset($this->us_parent) || isset($this->ds_parent)) {
                if (isset($this->ob_parent)) {
                    $receipt_id = $this->ob_parent;
                } elseif (isset($this->ds_parent)) {
                    $receipt_id = $this->ds_parent;
                } else {
                    $receipt_id = $this->us_parent;
                }
            }
            return self::build_invoice_access_link($receipt_id, $download, 'receipt');
        } else {
            return false;
        }
    }

    public function receipt_link_html($label = false)
    {
        if ($link = $this->receipt_link()) {
            if ($label === false) {
                $label = esc_html__('Download Receipt', 'publishpress-cart');
            }
            return '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        } else {
            return false;
        }
    }
}

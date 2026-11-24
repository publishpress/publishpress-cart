<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The cart checkout form after parsing. It holds the customer fields the Cart
 * settings declare, the cart revision the buyer saw, and the pay method.
 * It never holds a product, an item, or an amount.
 */
final readonly class PPCart_Shopping_Cart_Checkout_Input
{
    /** @param array<string,string> $customer */
    private function __construct(
        private int $seen_revision,
        private array $customer,
        private string $payment_method,
    ) {
    }

    /**
     * @param array<string,array> $fields PPCart_Shopping_Cart_Settings::checkout_fields().
     * @param array               $posted The submitted form, without slashes.
     */
    public static function from_post(array $fields, array $posted): self|WP_Error
    {
        $customer = [];
        $invalid = [];
        foreach ($fields as $name => $field) {
            $value = is_scalar($posted[$name] ?? null) ? trim((string) $posted[$name]) : '';
            $type = $field['type'] ?? 'text';
            if ('checkbox' === $type) {
                $value = in_array($value, ['', '0'], true) ? '' : '1';
            } elseif ('email' === $type) {
                $value = is_email($value) ? sanitize_email($value) : '';
            } else {
                $value = sanitize_text_field($value);
            }
            if ('' === $value) {
                if (! empty($field['required'])) {
                    $invalid[] = $name;
                }
                continue;
            }
            $customer['terms' === $name ? 'ppcart_accept_terms' : $name] = $value;
        }
        if ($invalid) {
            return new WP_Error(
                'ppcart_cart_checkout_invalid',
                __('Check the highlighted fields, then try again.', 'publishpress-cart'),
                ['fields' => $invalid]
            );
        }

        $payment_method = is_scalar($posted['pay-method'] ?? null) ? sanitize_key((string) $posted['pay-method']) : '';
        $customer['pay-method'] = $payment_method;
        return new self(absint($posted['ppcart_cart_revision'] ?? 0), $customer, $payment_method);
    }

    public function seen_revision(): int
    {
        return $this->seen_revision;
    }

    /** @return array<string,string> Keys that PPCart_Order reads from a checkout form. */
    public function customer(): array
    {
        return $this->customer;
    }

    public function payment_method(): string
    {
        return $this->payment_method;
    }
}

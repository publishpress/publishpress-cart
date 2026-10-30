<?php

/**
 * Native order administration fields shared by wp-admin and API clients.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

trait PPCart_Order_Administration
{
    /** @since 1.1.0 @return array */
    public static function admin_customer_fields()
    {
        return ['firstname', 'lastname', 'email', 'vat_number', 'phone', 'country', 'address1', 'address2', 'city', 'state', 'zip', 'company'];
    }

    /** @since 1.1.0 @param array $details Validated administration patch. @return void */
    public function apply_admin_customer_details(array $details)
    {
        foreach (self::admin_customer_fields() as $key) {
            if (! array_key_exists($key, $details)) {
                continue;
            }
            $value = 'email' === $key ? sanitize_email($details[$key]) : sanitize_text_field($details[$key]);
            $this->$key = in_array($key, ['firstname', 'lastname', 'email', 'vat_number'], true) || '' !== $value ? $value : null;
        }
        $this->first_name = $this->firstname;
        $this->last_name = $this->lastname;
        $this->customer_name = trim($this->firstname . ' ' . $this->lastname);
    }
}

<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Sanitize anything
 *
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

class PPCart_Sanitize
{
    /**
     * The data to be sanitized
     *
     * @access private
     * @since 1.0.0
     * @var string
     */
    private $data = '';

    /**
     * The type of data
     *
     * @access private
     * @since 1.0.0
     * @var string
     */
    private $type = '';

    /**
     * Constructor
     */
    public function __construct()
    {

        // Nothing to see here...
    }

    /**
     * Cleans the data
     *
     * @access public
     * @since 1.0.0
     *
     * @uses    sanitize_email()
     * @uses    sanitize_phone()
     * @uses    esc_textarea()
     * @uses    sanitize_text_field()
     * @uses    esc_url()
     *
     * @return  mixed         The sanitized data
     */
    public function clean()
    {
        return include __DIR__ . '/templates/ppcart-sanitize-clean.php';
    }

    private function format_price()
    {
        $thousand_sep = get_option('_ppcart_thousand_separator');
        $decimal_sep = get_option('_ppcart_decimal_separator');

        $price = $this->data;

        if ($price) {
            if ($thousand_sep && strpos($price, $thousand_sep) !== false) {
                $price = $this->is_thousand_separator($price, $thousand_sep, $decimal_sep);
            } elseif ($decimal_sep  != ',') {
                $price = $this->is_thousand_separator($price, ',', $decimal_sep);
            }

            if ($decimal_sep && strpos($price, $decimal_sep) !== false) {
                $price = str_replace($decimal_sep, '.', $price);
            }
        }

        return $price;
    }

    private function is_thousand_separator($price, $thousand_sep, $decimal_sep)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/sanitize-is-thousand-separator.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Checks a date against a format to ensure its validity
     *
     * @link http://www.php.net/manual/en/function.checkdate.php
     *
     * @param string        $date           The date as collected from the form field
     * @param string        $format         The format to check the date against
     * @return  string      A validated, formatted date
     */
    private function validate_date($date, $format = 'Y-m-d H:i:s')
    {

        $version = explode('.', phpversion());

        if (((int) $version[0] >= 5 && (int) $version[1] >= 2 && (int) $version[2] > 17)) {
            $d = DateTime::createFromFormat($format, $date);
        } else {
            $d = new DateTime(gmdate($format, strtotime($date)));
        }

        return $d && $d->format($format) == $date;
    }

    /**
     * Validates a phone number
     *
     * @access private
     * @since 1.0.0
     * @link http://jrtashjian.com/2009/03/code-snippet-validate-a-phone-number/
     * @param string            $phone              A phone number string
     * @return  string|bool     $phone|FALSE        Returns the valid phone number, FALSE if not
     */
    private function sanitize_phone($phone)
    {

        if (empty($phone)) {
            return false;
        }

        if (preg_match('/^[+]?([0-9]?)[(|s|-|.]?([0-9]{3})[)|s|-|.]*([0-9]{3})[s|-|.]*([0-9]{4})$/', $phone)) {
            return trim($phone);
        } // $phone validation

        return false;
    }

    /**
     * Performs general cleaning functions on data
     *
     * @param mixed     $input      Data to be cleaned
     * @return  mixed   $return     The cleaned data
     */
    private function sanitize_random($input)
    {

        $one    = trim($input);
        $two    = stripslashes($one);
        $return = htmlspecialchars($two);

        return $return;
    }

    /**
     * Sets the data class variable
     *
     * @param mixed         $data           The data to sanitize
     */
    public function set_data($data)
    {

        $this->data = $data;
    }

    /**
     * Sets the type class variable
     *
     * @param string        $type           The field type for this data
     */
    public function set_type($type)
    {

        $check = '';

        if (empty($type)) {
            $check = new WP_Error('forgot_type', __('Specify the data type to sanitize.', 'publishpress-cart'));
        }

        if (is_wp_error($check)) {
            wp_die(esc_html($check->get_error_message()), esc_html__('Forgot data type', 'publishpress-cart'));
        }

        $this->type = $type;
    }
}

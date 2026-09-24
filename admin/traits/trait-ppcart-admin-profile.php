<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * User-profile address fields shown on WordPress profile screens.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Profile_Trait
{
    public function show_user_profile_address_fields($user)
    {
        $address = ppcart_get_user_address($user->ID);
        $address['phone'] = ppcart_get_user_phone($user->ID);
        unset($address['address']);

        $labels = [
            'address_1' => esc_html__('Address', 'publishpress-cart'),
            'address_2' => esc_html__('Address Line 2', 'publishpress-cart'),
            'city' => esc_html__('City', 'publishpress-cart'),
            'state' => esc_html__('State', 'publishpress-cart'),
            'zip' => esc_html__('Zip', 'publishpress-cart'),
            'country' => esc_html__('Country', 'publishpress-cart'),
            'phone' => esc_html__('Phone', 'publishpress-cart'),
        ];
        ?>

        <h3><?php esc_html_e('PublishPress Cart', 'publishpress-cart'); ?></h3>

        <table class="form-table">
        <?php foreach ($labels as $key => $label) :
            $address[$key] ??= '';
            $id = $name = ppcart_meta_key($key); ?>
            <tr>
                     <th><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label></th>
                <td>
                    <input type="text"
                              id="<?php echo esc_attr($id); ?>"
                              name="<?php echo esc_attr($name); ?>"
                       value="<?php echo esc_attr($address[$key]) ?? ''; ?>"
                       class="regular-text"
                    />
                </td>
            </tr>
        <?php endforeach; ?>
        </table>
        <?php
    }

    public function update_profile_address_fields($user_id)
    {
        if (! current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (
            ! isset($_POST['_wpnonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-user_' . $user_id)
        ) {
            return false;
        }

        $fields = [
            'address_1',
            'address_2',
            'city',
            'state',
            'zip',
            'country',
            'phone',
        ];

        foreach ($fields as $field) {
            $field_key = '_ppcart_' . $field;
            if (! empty($_POST[ $field_key ])) {
                ppcart_update_user_meta($user_id, $field, sanitize_text_field(wp_unslash($_POST[ $field_key ])));
            }
        }
    }
}

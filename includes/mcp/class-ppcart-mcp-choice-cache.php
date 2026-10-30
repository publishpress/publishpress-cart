<?php

/**
 * Short-lived, user/product-bound metadata snapshots for network-free integration previews.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Choice_Cache
{
    private const GENERATION_OPTION = '_ppcart_mcp_choice_generation';

    /** @since 1.1.0 @param int $id Product. @return string */
    private static function key($id)
    {
        $scope = [get_current_user_id(), $id, get_post_meta($id), get_option(self::GENERATION_OPTION, ''), get_option('active_plugins', [])];
        return 'ppcart_mcp_choices_' . hash_hmac('sha256', wp_json_encode($scope), wp_salt('nonce'));
    }

    /** @since 1.1.0 @param int $id Product. @param array $definitions Reviewed metadata only. @return void */
    public static function put($id, array $definitions)
    {
        $safe = [];
        foreach ($definitions as $key => $definition) {
            $field = array_intersect_key($definition['field'], array_flip(['id', 'type', 'field-type', 'class', 'label', 'selections', 'conditional_logic']));
            if ('heartbeat_invite_id' === $key && is_array($field['selections'] ?? null)) {
                foreach ($field['selections'] as $choice => $label) {
                    $field['selections'][$choice] = sprintf(__('Invitation %s', 'publishpress-cart'), $choice);
                }
            }
            $safe[$key] = ['schema' => $definition['schema'], 'field' => $field];
        }
        set_transient(self::key($id), $safe, 15 * MINUTE_IN_SECONDS);
    }

    /** @since 1.1.0 @param int $id Product. @return array|WP_Error */
    public static function get($id)
    {
        $key = self::key($id);
        if (wp_using_ext_object_cache()) {
            $value = wp_cache_get($key, 'transient');
        } else {
            // Do not delete expired transient options during a preview.
            $expires = get_option('_transient_timeout_' . $key, 0);
            $value = $expires > time() ? get_option('_transient_' . $key, false) : false;
        }
        if (! is_array($value)) {
            return new WP_Error('ppcart_mcp_choices_expired', __('Read product integrations again to refresh native provider choices before previewing or saving.', 'publishpress-cart'));
        }
        return $value;
    }

    /** @since 1.1.0 @return void */
    public static function invalidate()
    {
        update_option(self::GENERATION_OPTION, wp_generate_uuid4(), false);
    }

    /** @since 1.1.0 @param string $option Changed option. @return void */
    public static function option_changed($option)
    {
        if (self::GENERATION_OPTION !== $option && (0 === strpos($option, '_ppcart_') || 0 === strpos($option, 'ppcart_'))) {
            self::invalidate();
        }
    }
}

add_action('updated_option', ['PPCart_Mcp_Choice_Cache', 'option_changed'], 10, 1);
add_action('added_option', ['PPCart_Mcp_Choice_Cache', 'option_changed'], 10, 1);
add_action('deleted_option', ['PPCart_Mcp_Choice_Cache', 'option_changed'], 10, 1);
add_action('upgrader_process_complete', ['PPCart_Mcp_Choice_Cache', 'invalidate'], 10, 0);

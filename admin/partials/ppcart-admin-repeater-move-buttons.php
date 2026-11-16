<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Move up / Move down buttons for one repeater row header.
 *
 * Gives keyboard and single-pointer users the same reordering that dragging the
 * header does. admin/js/ppcart-repeater.js moves the row and renumbers its
 * fields, so the saved order matches what the user sees.
 *
 * Expects $move_testid_prefix (string) to build the data-testid values.
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$move_testid_prefix = isset($move_testid_prefix) ? (string) $move_testid_prefix : 'ppcart-admin-repeater';
?>
<span class="ppcart-repeater-move">
    <button type="button" class="ppcart-repeater-move-btn ppcart-repeater-move-up" data-ppcart-move="up" data-testid="<?php echo esc_attr(ppcart_testid($move_testid_prefix . '-move-up')); ?>">
        <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
        <span class="screen-reader-text"><?php esc_html_e('Move up', 'publishpress-cart'); ?></span>
    </button>
    <button type="button" class="ppcart-repeater-move-btn ppcart-repeater-move-down" data-ppcart-move="down" data-testid="<?php echo esc_attr(ppcart_testid($move_testid_prefix . '-move-down')); ?>">
        <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
        <span class="screen-reader-text"><?php esc_html_e('Move down', 'publishpress-cart'); ?></span>
    </button>
</span>

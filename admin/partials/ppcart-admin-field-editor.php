<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for any WP Editor field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$default_atts = [  'value'      =>  '',
                        'show_tags' =>  false];

$atts = wp_parse_args($atts, $default_atts);

$atts['settings'] ??= [];
$atts['settings']['textarea_name'] = $atts['name'];
$editor_id = ! empty($atts['editor_id']) ? $atts['editor_id'] : 'ppcart-' . $atts['id'];
$testid = ppcart_testid('ppcart-admin-field-' . $editor_id);

if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($editor_id); ?>"><?php

        echo esc_html($atts['label']);

    ?>:

    <?php if (! empty($atts['description'])) : ?>
    <i class="ppcart-tooltip" data-balloon-length="medium" aria-label="<?php echo esc_attr(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?>" data-balloon-pos="up" tabindex="0">?</i>
    <?php endif; ?>

    </label><?php
}

?><div class="ppcart-editor-field" data-ppcart-editor-field data-testid="<?php echo esc_attr($testid); ?>"><?php

if (! empty($atts['defer_editor'])) {
    $editor_settings = $atts['settings'];
    unset($editor_settings['textarea_name']);
    ?><textarea
        class="<?php echo esc_attr(trim((string) ($atts['class'] ?? '') . ' ppcart-deferred-editor')); ?>"
        id="<?php echo esc_attr($editor_id); ?>"
        name="<?php echo esc_attr($atts['name']); ?>"
        data-testid="<?php echo esc_attr($testid . '-textarea'); ?>"
        data-ppcart-editor-settings="<?php echo esc_attr(wp_json_encode($editor_settings)); ?>"
        rows="<?php echo esc_attr($atts['settings']['textarea_rows'] ?? 8); ?>"><?php echo esc_textarea(html_entity_decode($atts['value'])); ?></textarea><?php
} else {
    wp_editor(html_entity_decode($atts['value']), $editor_id, $atts['settings']);
}

?>

<?php if (! empty($atts['description'])) : ?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?></p>
<?php endif; ?>

<?php if ($atts['show_tags']) {
    ppcart_merge_tag_select();
} ?>
</div>

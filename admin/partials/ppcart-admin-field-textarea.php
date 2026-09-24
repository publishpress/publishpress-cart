<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for any textarea field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

$default_atts = [  'type'       =>  'text',
                        'value'     =>  '',
                        'class'     =>  '',
                        'id'        =>  '',
                        'placeholder' =>    '',
                        'cols'      =>  '10',
                        'rows'      =>  '5',
                        'description' =>    '',
                        'note' =>   ''];

$atts = wp_parse_args($atts, $default_atts);
$field_dom_id = ppcart_admin_field_dom_id($atts);
$testid_source = ! empty($atts['id']) ? $atts['id'] : ($atts['name'] ?? '');
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);
if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($field_dom_id); ?>"><?php echo esc_html($atts['label']); ?>
    <?php
    if (! empty($atts['description'])) {
        ?><i class="ppcart-tooltip" data-balloon-length="medium" aria-label="<?php echo esc_attr($atts['description']); ?>" data-balloon-pos="up" tabindex="0">?</i>
    <?php
    } ?>
    </label><?php
}

?><div class="input-group field-<?php echo esc_attr($atts['type']); ?>"><div class="textarea-wrap"><textarea
    class="<?php echo esc_attr($atts['class']); ?>"
    cols="<?php echo esc_attr($atts['cols']); ?>"
    id="<?php echo esc_attr($field_dom_id); ?>"
    name="<?php echo esc_attr($atts['name']); ?>"
    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
    data-testid="<?php echo esc_attr($testid); ?>"
    rows="<?php echo esc_attr($atts['rows']); ?>"><?php

    echo esc_html($atts['value']);

?></textarea>

<?php if (empty($atts['label']) && ! empty($atts['description'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?></p>
<?php endif; ?>

<?php if (! empty($atts['note'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['note'], ENT_QUOTES)); ?></p>
<?php endif; ?>

</div></div>

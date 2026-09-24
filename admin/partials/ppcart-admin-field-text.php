<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Provides the markup for any text field
 *
 * @link https://publishpress.com/publishpress-cart/
 * @since 1.0.0
 *
 * @package PublishPress_Cart
 * @subpackage PublishPress_Cart/admin/partials
 */

global $ppcart_currency_symbol;
$default_atts = [  'type' => 'text',
                        'value' => '',
                        'class' => '',
                        'id' => '',
                        'placeholder' => '',
                     'note' =>  ''];
$atts = wp_parse_args($atts, $default_atts);
$field_dom_id = ppcart_admin_field_dom_id($atts);
$testid_source = ! empty($atts['id']) ? $atts['id'] : ($atts['name'] ?? '');
$testid = ppcart_testid('ppcart-admin-field-' . $testid_source);
$clear_testid = ppcart_testid('ppcart-admin-field-' . $testid_source . '-clear-date');
$right_currency = ($atts['type'] == 'price' && in_array(get_option('_ppcart_currency_position'), ['right', 'right-space'])) ? true : false;
if ($right_currency) {
    $atts['class'] .= ' right-currency';
}

if (! empty($atts['label'])) {
    ?><label for="<?php echo esc_attr($field_dom_id); ?>"><?php echo esc_html($atts['label']); ?>

<?php

if (! empty($atts['description'])) {
    ?><i class="ppcart-tooltip" data-balloon-length="medium" aria-label="<?php echo esc_attr($atts['description']); ?>" data-balloon-pos="up" tabindex="0">?</i>
<?php
} ?>

</label><?php
}

?><div class="input-group field-<?php echo esc_attr($atts['type']); ?>">
    <?php
    if ($atts['type'] == 'price') {
        $atts['type'] = 'text';
        $atts['class'] .= ' price';
        if (!$right_currency) {
            echo '<span class="input-prepend">' . esc_html($ppcart_currency_symbol) . '</span>';
        }
        $atts['value'] = ppcart_format_number($atts['value']);
        if ($atts['placeholder'] === '') {
            $atts['placeholder'] = ppcart_format_number(0);
        }
    }
?>
    <input
    class="<?php echo esc_attr($atts['class']); ?>"
    id="<?php echo esc_attr($field_dom_id); ?>"
    name="<?php echo esc_attr($atts['name']); ?>"
    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
    type="<?php echo esc_attr($atts['type']); ?>"
    data-testid="<?php echo esc_attr($testid); ?>"
    autocomplete="new-password" autocorrect="off" autocapitalize="none"
    <?php if (isset($atts['step'])) :
        ?>step="<?php echo esc_attr($atts['step']); ?>"<?php
    endif; ?>
    autocomplete="off" autocorrect="off" autocapitalize="none"
    <?php if (isset($atts['readonly'])) :
        ?>readonly <?php
    endif; ?>
    <?php if (! empty($atts['data-pp-secret-stored'])) :
        ?>data-pp-secret-stored="1"<?php
    endif; ?>
    value="<?php echo esc_attr($atts['value']); ?>"
    data-lpignore="true" />
    <?php
if ($right_currency) {
    echo '<span class="input-append">' . esc_html($ppcart_currency_symbol) . '</span>';
}
?>
    <?php if ($atts['class'] == 'datepicker') : ?>
        <a href="#" class="clear-date" data-testid="<?php echo esc_attr($clear_testid); ?>"><svg width="16px" enable-background="new 0 0 24 24" version="1.0" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path fill="#b1b1b1" d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z"/></g></svg></a>
    <?php endif; ?>

<?php if (empty($atts['label']) && ! empty($atts['description'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['description'], ENT_QUOTES)); ?></p>
<?php endif; ?>

<?php if (! empty($atts['note'])) :?>
    <p class="description"><?php echo wp_kses_post(wp_specialchars_decode($atts['note'], ENT_QUOTES)); ?></p>
<?php endif; ?>
</div>

<?php

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-shopping-cart-notices" aria-live="polite" aria-atomic="true">
    <?php foreach ($attr['cart']['notices'] as $notice) : ?>
        <p class="ppcart-shopping-cart-notice" data-ppcart-notice="<?php echo esc_attr($notice['code']); ?>"><?php echo esc_html($notice['message']); ?></p>
    <?php endforeach; ?>
</div>

<?php

defined('ABSPATH') || exit;

return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","className":"publishpress-cart-product-simple","layout":{"type":"constrained"}} -->
<main class="wp-block-group publishpress-cart-product-simple">
    <!-- wp:group {"className":"ppcart-product-header","layout":{"type":"constrained"}} -->
    <div class="wp-block-group ppcart-product-header">
        <!-- wp:post-title {"level":1,"className":"ppcart-product-header-title"} /-->
    </div>
    <!-- /wp:group -->
    <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
    <!-- wp:publishpress-cart/checkout-form /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';

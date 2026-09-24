<?php

if (! defined('ABSPATH')) {
    exit;
}

return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":50,"customOverlayColor":"#111111","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","isDark":false,"className":"publishpress-cart-product-template","layout":{"type":"constrained","contentSize":"80%"}} -->
<div class="wp-block-cover is-light publishpress-cart-product-template" style="min-height:100vh">
    <span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#111111"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:columns {"verticalAlignment":"center"} -->
        <div class="wp-block-columns are-vertically-aligned-center">
            <!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
            <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">
                <!-- wp:group {"className":"publishpress-cart-product-content","layout":{"type":"constrained"}} -->
                <div class="wp-block-group publishpress-cart-product-content">
                    <!-- wp:post-title {"level":1,"style":{"color":{"text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}}}} /-->
                    <!-- wp:post-content {"style":{"color":{"text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}}},"layout":{"type":"constrained","justifyContent":"left"}} /-->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:column -->

            <!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
            <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
                <!-- wp:group {"className":"publishpress-cart-product-checkout is-style-default","style":{"color":{"background":"#ffffff"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"radius":"4px"}},"layout":{"type":"constrained"}} -->
                <div class="wp-block-group publishpress-cart-product-checkout is-style-default has-background" style="border-radius:4px;background-color:#ffffff;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
                    <!-- wp:publishpress-cart/checkout-form {"styleSettings":{"density":"compact"}} /-->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->
    </div>
</div>
<!-- /wp:cover -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';

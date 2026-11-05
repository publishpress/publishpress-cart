<?php

defined('ABSPATH') || exit;

class PPCart_Pro_Menu_Previews
{
    private static function features()
    {
        return [
            'upsell-paths' => [__('Offers after checkout', 'publishpress-cart'), ppcart_product_tab_description('ppcart_pro_upsell_path'), ['Course bundle', 'Coaching add-on', 'Download pack'], ppcart_pro_locked_product_field_labels('ppcart_pro_upsell_path')],
            'collections' => [__('Collections', 'publishpress-cart'), __('Collections combine multiple products in a single checkout. Add products to a collection and choose whether to use their existing prices or set custom prices for the collection.', 'publishpress-cart'), ['Creator resources', 'Training library', 'Starter templates']],
            'upgrade-paths' => [__('Subscription upgrades', 'publishpress-cart'), __('Subscription upgrades let customers switch an existing subscription to another product or payment plan. Choose which products and payment plans are available for upgrades or downgrades.', 'publishpress-cart'), ['Starter to Plus', 'Monthly to annual', 'Course to bundle']],
            'global-coupons' => [__('Global Coupons', 'publishpress-cart'), __('Offer customers discounts across products using coupon codes. Set the discount amount or percentage and choose when each coupon can be used.', 'publishpress-cart'), ['WELCOME10', 'SUMMER20', 'MEMBER15'], ppcart_pro_locked_product_field_labels('ppcart_pro_coupons')],
            'affiliates' => [__('Affiliates', 'publishpress-cart'), __('Let affiliates earn commissions for referring customers to your products. Choose whether affiliate sales are enabled and set the commission rules.', 'publishpress-cart'), ['Studio Partner', 'Creator Network', 'Training Partner'], ppcart_pro_locked_product_field_labels('ppcart_pro_affiliates')],
        ];
    }

    public function register_pages()
    {
        if (ppcart_supports('pro')) {
            return;
        }

        foreach (self::features() as $slug => $feature) {
            $hook = add_submenu_page(
                PPCart_Admin_Screens::menu_slug(),
                $feature[0],
                $feature[0],
                ppcart_live_cap('manager_option'),
                'ppcart-pro-preview-' . $slug,
                static function () use ($slug, $feature) {
                    self::render($slug, $feature);
                }
            );
            if ($hook) {
                add_action('load-' . $hook, [self::class, 'enqueue_styles']);
            }
        }
    }

    public static function enqueue_styles()
    {
        wp_enqueue_style('ppcart-pro-locks', plugins_url('css/ppcart-pro-locks.css', __FILE__), [], PPCART_VERSION);
        wp_enqueue_style('ppcart-product-metabox', plugins_url('css/ppcart-product-metabox.css', __FILE__), [], PPCART_VERSION);
        wp_enqueue_style('ppcart-pro-menu-previews', plugins_url('css/ppcart-pro-menu-previews.css', __FILE__), ['dashicons', 'ppcart-pro-locks', 'ppcart-product-metabox'], PPCART_VERSION);
    }

    private static function render($slug, array $feature)
    {
        if (! ppcart_user_can('manager_option') || ppcart_supports('pro')) {
            return;
        }

        [$title, $description, $examples] = $feature;
        ?>
        <div class="wrap ppcart-pro-menu-preview">
            <h1><?php echo esc_html($title); ?></h1>
            <div class="ppcart-pro-preview__stage">
                <div class="ppcart-pro-preview__sample" aria-hidden="true">
                    <div class="ppcart-pro-preview__sample-heading">
                        <strong><?php echo esc_html($title); ?></strong>
                        <span><?php esc_html_e('Add new', 'publishpress-cart'); ?></span>
                    </div>
                    <table class="widefat striped">
                        <thead><tr><th><?php esc_html_e('Name', 'publishpress-cart'); ?></th><th><?php esc_html_e('Status', 'publishpress-cart'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($examples as $example) : ?>
                                <tr><td><?php echo esc_html($example); ?></td><td><?php esc_html_e('Active', 'publishpress-cart'); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The shared promo renderer escapes its own markup.
                echo ppcart_pro_product_preview_card_html([
                    'text'    => $description,
                    'labels'  => $feature[3] ?? [],
                    'context' => 'menu-' . $slug,
                    'class'   => 'ppcart-pro-preview__promo',
                ]);
                ?>
            </div>
        </div>
        <?php
    }
}

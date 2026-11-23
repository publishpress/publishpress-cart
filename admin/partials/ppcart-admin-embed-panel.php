<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The product Embed tab: a choice of three embed types and the code of each one.
 * Printed by PPCart_Admin_Embed_Tab::render_panel(). Without the script, all three
 * codes show.
 *
 * @var bool   $ppcart_embed_published Whether the product is published.
 * @var array  $ppcart_embed_codes     Snippets by id, see PPCart_Admin_Embed_Tab::codes().
 * @var string $ppcart_embed_preview   URL of the embedded checkout.
 */

if (! $ppcart_embed_published) : ?>
    <div class="ppcart-embed-panel" data-testid="ppcart-admin-embed-unpublished">
        <div class="notice notice-info inline">
            <p><?php esc_html_e('Publish this product to get the embed code.', 'publishpress-cart'); ?></p>
        </div>
    </div>
    <?php
    return;
endif;

$ppcart_embed_first = (string) array_key_first($ppcart_embed_codes);
?>
<div class="ppcart-embed-panel" data-ppcart-embed-panel data-testid="ppcart-admin-embed-panel">
    <h3 class="ppcart-embed-panel__step" id="ppcart-embed-type-title"><span aria-hidden="true">1</span><?php esc_html_e('Choose how the checkout shows', 'publishpress-cart'); ?></h3>

    <div class="ppcart-embed-types" role="group" aria-labelledby="ppcart-embed-type-title">
        <?php foreach ($ppcart_embed_codes as $ppcart_embed_code_id => $ppcart_embed_code) : ?>
            <button
                type="button"
                class="ppcart-embed-type"
                aria-pressed="<?php echo $ppcart_embed_first === (string) $ppcart_embed_code_id ? 'true' : 'false'; ?>"
                data-ppcart-embed-type="<?php echo esc_attr($ppcart_embed_code_id); ?>"
                data-testid="<?php echo esc_attr('ppcart-admin-embed-type-' . $ppcart_embed_code_id); ?>">
                <svg class="ppcart-embed-type__art" viewBox="0 0 96 60" aria-hidden="true" focusable="false">
                    <rect class="ppcart-embed-art__page" x="1" y="1" width="94" height="58" rx="5"/>
                    <rect class="ppcart-embed-art__line" x="9" y="9" width="30" height="4" rx="2"/>
                    <?php if ('inline' === $ppcart_embed_code_id) : ?>
                        <rect class="ppcart-embed-art__line" x="9" y="18" width="78" height="3" rx="1.5"/>
                        <rect class="ppcart-embed-art__form" x="9" y="26" width="78" height="26" rx="3"/>
                        <rect class="ppcart-embed-art__field" x="14" y="31" width="68" height="5" rx="2"/>
                        <rect class="ppcart-embed-art__accent" x="14" y="41" width="26" height="6" rx="3"/>
                    <?php elseif ('popup' === $ppcart_embed_code_id) : ?>
                        <rect class="ppcart-embed-art__line" x="9" y="18" width="50" height="3" rx="1.5"/>
                        <rect class="ppcart-embed-art__accent" x="9" y="27" width="26" height="7" rx="3.5"/>
                        <rect class="ppcart-embed-art__shade" x="1" y="1" width="94" height="58" rx="5"/>
                        <rect class="ppcart-embed-art__form" x="28" y="12" width="40" height="38" rx="3"/>
                        <rect class="ppcart-embed-art__field" x="33" y="18" width="30" height="5" rx="2"/>
                        <rect class="ppcart-embed-art__field" x="33" y="27" width="30" height="5" rx="2"/>
                        <rect class="ppcart-embed-art__accent" x="33" y="38" width="18" height="6" rx="3"/>
                    <?php else : ?>
                        <rect class="ppcart-embed-art__line" x="9" y="18" width="78" height="3" rx="1.5"/>
                        <rect class="ppcart-embed-art__line" x="9" y="25" width="60" height="3" rx="1.5"/>
                        <rect class="ppcart-embed-art__link" x="9" y="36" width="44" height="4" rx="2"/>
                        <path class="ppcart-embed-art__cursor" d="M58 38l0 14 4-3.5 2.6 6 2.6-1.1-2.6-6 5.2-.4z"/>
                    <?php endif; ?>
                </svg>
                <span class="ppcart-embed-type__title"><?php echo esc_html($ppcart_embed_code['title']); ?></span>
                <span class="ppcart-embed-type__summary"><?php echo esc_html($ppcart_embed_code['summary']); ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <h3 class="ppcart-embed-panel__step"><span aria-hidden="true">2</span><?php esc_html_e('Copy the code into your website', 'publishpress-cart'); ?></h3>

    <?php foreach ($ppcart_embed_codes as $ppcart_embed_code_id => $ppcart_embed_code) : ?>
        <div class="ppcart-embed-code" data-ppcart-embed-block="<?php echo esc_attr($ppcart_embed_code_id); ?>" data-testid="<?php echo esc_attr('ppcart-admin-embed-code-' . $ppcart_embed_code_id); ?>">
            <div class="ppcart-embed-code__header">
                <h4 class="ppcart-embed-code__title"><?php echo esc_html($ppcart_embed_code['title']); ?></h4>
                <div class="ppcart-embed-code__actions">
                    <span class="ppcart-embed-code__status" aria-live="polite" data-ppcart-embed-copy-status data-testid="<?php echo esc_attr('ppcart-admin-embed-copy-status-' . $ppcart_embed_code_id); ?>"></span>
                    <button
                        type="button"
                        class="button button-primary"
                        aria-label="<?php
                        /* translators: %s: name of the embed code, such as Inline checkout. */
                        echo esc_attr(sprintf(__('Copy %s code', 'publishpress-cart'), $ppcart_embed_code['title']));
                        ?>"
                        data-ppcart-embed-copy
                        data-testid="<?php echo esc_attr('ppcart-admin-embed-copy-' . $ppcart_embed_code_id); ?>"><?php esc_html_e('Copy', 'publishpress-cart'); ?></button>
                </div>
            </div>
            <pre class="ppcart-embed-code__box"><code data-ppcart-embed-code="<?php echo esc_attr($ppcart_embed_code_id); ?>"><?php echo esc_html($ppcart_embed_code['code']); ?></code></pre>
            <p class="ppcart-embed-code__description"><?php echo esc_html($ppcart_embed_code['description']); ?></p>
        </div>
    <?php endforeach; ?>

    <p class="ppcart-embed-panel__preview">
        <a href="<?php echo esc_url($ppcart_embed_preview); ?>" target="_blank" rel="noopener" data-testid="ppcart-admin-embed-preview-link"><?php esc_html_e('Open the embedded checkout in a new tab', 'publishpress-cart'); ?></a>
    </p>
</div>

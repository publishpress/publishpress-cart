<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Display-only part of the product Embed tab: live preview, embed code and help.
 * Printed by PPCart_Admin_Embed_Tab::render_panel().
 *
 * @var bool   $ppcart_embed_published Whether the product is published.
 * @var array  $ppcart_embed_codes     Snippets by id, see PPCart_Admin_Embed_Tab::codes().
 * @var string $ppcart_embed_preview   Same-origin URL of the embedded checkout.
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
?>
<div class="ppcart-embed-panel" data-ppcart-embed-panel data-testid="ppcart-admin-embed-panel">
    <div class="ppcart-embed-panel__layout">
        <div class="ppcart-embed-panel__fields" data-ppcart-embed-fields></div>

        <section class="ppcart-embed-panel__section ppcart-embed-panel__preview" aria-labelledby="ppcart-embed-preview-title">
            <h3 id="ppcart-embed-preview-title" class="ppcart-embed-panel__title"><?php esc_html_e('Preview', 'publishpress-cart'); ?></h3>
            <p class="ppcart-embed-panel__hint"><?php esc_html_e('Your changes show here at once. Update the product to save them.', 'publishpress-cart'); ?></p>
            <div class="ppcart-embed-panel__frame">
                <iframe
                    src="<?php echo esc_url($ppcart_embed_preview); ?>"
                    title="<?php esc_attr_e('Checkout preview', 'publishpress-cart'); ?>"
                    loading="lazy"
                    sandbox="<?php echo esc_attr(PPCart_Admin_Embed_Tab::PREVIEW_SANDBOX); ?>"
                    data-ppcart-embed-preview
                    data-testid="ppcart-admin-embed-preview"></iframe>
            </div>
        </section>
    </div>

    <section class="ppcart-embed-panel__section" aria-labelledby="ppcart-embed-code-title">
        <h3 id="ppcart-embed-code-title" class="ppcart-embed-panel__title"><?php esc_html_e('Embed code', 'publishpress-cart'); ?></h3>

        <?php foreach ($ppcart_embed_codes as $ppcart_embed_code_id => $ppcart_embed_code) : ?>
            <div class="ppcart-embed-code" data-testid="<?php echo esc_attr('ppcart-admin-embed-code-' . $ppcart_embed_code_id); ?>">
                <div class="ppcart-embed-code__header">
                    <div class="ppcart-embed-code__text">
                        <h4 class="ppcart-embed-code__title"><?php echo esc_html($ppcart_embed_code['title']); ?></h4>
                        <p class="ppcart-embed-code__description"><?php echo esc_html($ppcart_embed_code['description']); ?></p>
                    </div>
                    <div class="ppcart-embed-code__actions">
                        <span class="ppcart-embed-code__status" aria-live="polite" data-ppcart-embed-copy-status data-testid="<?php echo esc_attr('ppcart-admin-embed-copy-status-' . $ppcart_embed_code_id); ?>"></span>
                        <button
                            type="button"
                            class="button"
                            aria-label="<?php
                            /* translators: %s: name of the embed code, such as Inline checkout. */
                            echo esc_attr(sprintf(__('Copy %s code', 'publishpress-cart'), $ppcart_embed_code['title']));
                            ?>"
                            data-ppcart-embed-copy
                            data-testid="<?php echo esc_attr('ppcart-admin-embed-copy-' . $ppcart_embed_code_id); ?>"><?php esc_html_e('Copy', 'publishpress-cart'); ?></button>
                    </div>
                </div>
                <?php if ('popup' === $ppcart_embed_code_id) : ?>
                    <div class="ppcart-embed-code__sample" aria-hidden="true"><span data-ppcart-embed-popup-sample></span></div>
                <?php endif; ?>
                <pre class="ppcart-embed-code__box"><code data-ppcart-embed-code="<?php echo esc_attr($ppcart_embed_code_id); ?>"><?php echo esc_html($ppcart_embed_code['code']); ?></code></pre>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="ppcart-embed-panel__section ppcart-embed-panel__help" aria-labelledby="ppcart-embed-help-title">
        <h3 id="ppcart-embed-help-title" class="ppcart-embed-panel__title"><?php esc_html_e('Where to paste the code', 'publishpress-cart'); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e('Showit:', 'publishpress-cart'); ?></strong>
                <?php esc_html_e('Add an Embed Code element and paste the Inline checkout code. Then set the type of that section to "Grow with Content", so the page grows with the form, and keep the element as wide as the section on desktop and on mobile. You can also paste the Popup button code, or set the link of a Showit button to the Popup link on a page that has the loader script.', 'publishpress-cart'); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Other site builders:', 'publishpress-cart'); ?></strong>
                <?php esc_html_e('Paste the code into an HTML block or a custom code block.', 'publishpress-cart'); ?>
            </li>
        </ul>
    </section>
</div>

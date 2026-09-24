<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * Shared PublishPress Cart admin sidebar renderer.
 */

/**
     * Render the support sidebar used on Cart admin pages.
     *
     * @return void
     */
function ppcart_render_admin_sidebar()
{
    ?>
        <div class="pp-column-right">
            <div class="pp-advertisement-right-sidebar">
                <div class="advertisement-box-content postbox pp-advert">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php esc_html_e('Need PublishPress Cart Support?', 'publishpress-cart'); ?></span>
                        </h3>
                    </div>

                    <div class="inside pp-advert">
                        <p>
                            <?php esc_html_e('If you need help or have a new feature request, let us know.', 'publishpress-cart'); ?>
                            <a class="advert-link" href="https://publishpress.com/publishpress-support/" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-sidebar-support')); ?>">
                                <?php esc_html_e('Request Support', 'publishpress-cart'); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                    <path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path>
                                </svg>
                            </a>
                        </p>
                        <p>
                            <?php esc_html_e('Detailed documentation is also available on the plugin website.', 'publishpress-cart'); ?>
                            <a class="advert-link" href="<?php echo esc_url(PPCART_DOCS_URL . 'getting-started/introduction'); ?>" target="_blank" rel="noopener noreferrer" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-sidebar-knowledge-base')); ?>">
                                <?php esc_html_e('View Knowledge Base', 'publishpress-cart'); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                    <path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path>
                                </svg>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
}

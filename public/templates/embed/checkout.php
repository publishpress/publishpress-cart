<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Embedded checkout shell: the bare document a host page frames through
 * public/js/ppcart-embed.js. PPCart_Public_Embed_Controller adds the body classes,
 * the CSS variables, the frame bridge, and the content.
 */

$ppcart_embed = new PPCart_Public_Embed_Controller();

// Render before wp_head(), as block themes do, so the checkout's inline CSS attaches to its stylesheet in the head.
ob_start();
$ppcart_embed->render_content();
$ppcart_embed_content = ob_get_clean();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (! current_theme_supports('title-tag')) : ?>
        <title><?php echo esc_html(wp_get_document_title()); ?></title>
    <?php endif; ?>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <main class="ppcart-embed__main">
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is ppcart_kses_frontend_html(); post content went through the_content.
        echo $ppcart_embed_content;
        ?>
    </main>
    <?php wp_footer(); ?>
</body>

</html>

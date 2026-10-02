<?php

if (! defined('ABSPATH')) {
    exit;
}

$username_label = esc_html__('Username', 'publishpress-cart');
$password_label = esc_html__('Password', 'publishpress-cart');
$remember_label = esc_html__('Remember Me', 'publishpress-cart');
$submit_label   = esc_attr__('Log In', 'publishpress-cart');

$ppcart_account_login_preview_buffer_level = ob_get_level();
$ppcart_account_login_preview_buffer_active = true;
$ppcart_account_login_preview_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_account_login_preview_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_account_login_preview_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
<div id="ppcart-login" class="ppcart-account-form">
    <form name="loginform" id="ppcart-login-form" action="#" method="post" data-testid="ppcart-account-login-form-preview">
        <p class="login-username">
            <label for="user_login"><?php echo esc_html($username_label); ?></label>
            <input type="text" name="log" id="user_login" autocomplete="username" class="input" value="" size="20" data-testid="ppcart-account-login-username-preview" />
        </p>
        <p class="login-password">
            <label for="user_pass"><?php echo esc_html($password_label); ?></label>
            <input type="password" name="pwd" id="user_pass" autocomplete="current-password" spellcheck="false" class="input" value="" size="20" data-testid="ppcart-account-login-password-preview" />
        </p>
        <p class="login-remember">
            <label><input name="rememberme" type="checkbox" id="rememberme" value="forever" data-testid="ppcart-account-login-remember-preview" /> <?php echo esc_html($remember_label); ?></label>
        </p>
        <p class="login-submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php echo esc_attr($submit_label); ?>" data-testid="ppcart-account-login-submit-preview" />
        </p>
    </form>
</div>
<?php
} catch (Throwable $ppcart_account_login_preview_buffer_exception) {
    $ppcart_account_login_preview_buffer_error = $ppcart_account_login_preview_buffer_exception;
} finally {
    $ppcart_account_login_preview_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_account_login_preview_buffer_active && ob_get_level() > $ppcart_account_login_preview_buffer_level + 1) {
        $ppcart_account_login_preview_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_account_login_preview_buffer_exception) {
            $ppcart_account_login_preview_buffer_error = $ppcart_account_login_preview_buffer_error ?? $ppcart_account_login_preview_buffer_exception;
            if (ob_get_level() >= $ppcart_account_login_preview_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_account_login_preview_buffer_active && ob_get_level() === $ppcart_account_login_preview_buffer_level + 1) {
        $ppcart_account_login_preview_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_account_login_preview_buffer_error) {
    throw $ppcart_account_login_preview_buffer_error;
}
$output = $ppcart_account_login_preview_buffer_output;

return $renderer->prepend_login_intro($output, $attributes);

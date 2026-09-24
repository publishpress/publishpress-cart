<?php

if (! defined('ABSPATH')) {
    exit;
}

$username_label = esc_html__('Username', 'publishpress-cart');
$password_label = esc_html__('Password', 'publishpress-cart');
$remember_label = esc_html__('Remember Me', 'publishpress-cart');
$submit_label   = esc_attr__('Log In', 'publishpress-cart');

ob_start();
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
$output = ob_get_clean();

return $renderer->prepend_login_intro($output, $attributes);

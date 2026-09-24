<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying login/password-reset page
 * This template can be overridden by copying it to yourtheme/publishpress-cart/my-account/forms/login-form.php
 */

?>

<?php

if ($attr['action'] == 'reset') : ?>
<div id="ppcart-password-reset-form" class="ppcart-account-form widecolumn">
    <h3><?php esc_html_e('Pick a New Password', 'publishpress-cart'); ?></h3>

    <form name="resetpassform" id="resetpassform" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-reset-password-form')); ?>" action="<?php echo esc_url(site_url('wp-login.php?action=resetpass&key=' . $attr['key'] . '&login=' . rawurlencode($attr['login']))); ?>"
        method="post" autocomplete="off">
        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr($attr['login']); ?>"
            autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr($attr['key']); ?>" />

        <?php if (count($attr['errors']) > 0) : ?>
        <?php foreach ($attr['errors'] as $login_error) : ?>
        <p class="ppcart-account-error">
            <?php echo esc_html($login_error); ?>
        </p>
        <?php endforeach; ?>
        <?php endif; ?>

        <p>
            <label for="pass1"><?php esc_html_e('New password', 'publishpress-cart') ?></label>
            <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-reset-password-new')); ?>" />
        </p>
        <p>
            <label for="pass2"><?php esc_html_e('Repeat new password', 'publishpress-cart') ?></label>
            <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-reset-password-repeat')); ?>" />
        </p>

        <p class="description"><?php echo esc_html(wp_get_password_hint()); ?></p>

        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button" class="button"
                value="<?php echo esc_attr__('Reset Password', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-reset-password-submit')); ?>" />
        </p>
    </form>
</div>

<?php elseif ($attr['action'] == 'lostpassword') : ?>
<div id="ppcart-password-lost-form" class="ppcart-account-form widecolumn">
    <h3><?php esc_html_e('Forgot Your Password?', 'publishpress-cart'); ?></h3>

    <?php if ($attr['lost_password_sent']) : ?>
    <p class="login-info">
        <?php esc_html_e('Check your email for a link to reset your password.', 'publishpress-cart'); ?>
    </p>
    <p><a class="button" href="<?php echo esc_url($attr['login_url']); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-back-to-login')); ?>"><?php echo esc_html__('Back to Login', 'publishpress-cart'); ?></a></p>
    <?php else : ?>
    <p class="description">
        <?php esc_html_e("Please enter your username or email address. You will receive an email message with instructions on how to reset your password.", 'publishpress-cart');?>
    </p>

    <?php if (count($attr['errors']) > 0) : ?>
    <?php foreach ($attr['errors'] as $login_error) : ?>
    <p class="ppcart-account-error"><?php echo esc_html($login_error); ?></p>
    <?php endforeach; ?>
    <?php endif; ?>


    <form id="lostpasswordform" action="<?php echo esc_url(wp_lostpassword_url()); ?>" method="post" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-lost-password-form')); ?>">
        <?php wp_nonce_field('ppcart_lost_password', 'ppcart_lost_password_nonce'); ?>
        <p class="form-row">
            <label for="user_login"><?php esc_html_e('Username or Email Address', 'publishpress-cart'); ?>
                <input type="text" name="user_login" id="user_login" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-lost-password-login')); ?>">
        </p>

        <p class="lostpassword-submit">
            <input type="submit" name="submit" class="lostpassword-button"
                value="<?php echo esc_attr__('Reset Password', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-lost-password-submit')); ?>" />
        </p>
    </form>
    <?php endif; ?>
</div>

<?php else : ?>
<div id="ppcart-login" class="ppcart-account-form">
    <?php if ($attr['password_updated']) : ?>
    <p class="login-info">
        <?php esc_html_e('Your password has been changed. Please sign in below.', 'publishpress-cart'); ?>
    </p>
    <?php endif; ?>

    <!-- Show errors if there are any -->
    <?php if (count($attr['errors']) > 0) : ?>
    <?php foreach ($attr['errors'] as $login_error) : ?>
    <p class="ppcart-account-error">
        <?php echo esc_html($login_error); ?>
    </p>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php
    $args = [
        'redirect' => $attr['login_url'],
        'form_id' => 'ppcart-login-form',
        'label_username' => __('Username', 'publishpress-cart'),
        'label_password' => __('Password', 'publishpress-cart'),
        'label_remember' => __('Remember Me', 'publishpress-cart'),
        'label_log_in' => __('Log In', 'publishpress-cart'),
        'remember' => true,
    ];
echo wp_login_form($args);
?>
</div>

<?php endif; ?>

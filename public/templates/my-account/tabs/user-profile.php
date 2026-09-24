<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_current_user = wp_get_current_user();
$user_phone = ppcart_get_user_phone($ppcart_current_user->ID);
$address = ppcart_get_user_address($ppcart_current_user->ID);
?>
<div class="profile-wrapper ppcart">
    <h4>My Profile</h4>
    <form method="post" id="ppcart-update-profile-form" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-form')); ?>">
        <input type="hidden" id="ppcart_profile_nonce" name="ppcart_profile_nonce" value="<?php echo esc_attr(wp_create_nonce('ppcart_ajax_nonce')); ?>">

        <div class="ppcart-form-group form-field">
            <label><?php esc_html_e('First Name', 'publishpress-cart'); ?>: </label>
            <input type="text" name="first_name" value="<?php echo esc_attr(get_user_meta($ppcart_current_user->ID, 'first_name', true)) ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-first-name')); ?>"/>
        </div>

        <div class="ppcart-form-group form-field">
            <label><?php esc_html_e('Last Name', 'publishpress-cart'); ?>: </label>
            <input type="text" name="last_name" value="<?php echo esc_attr(get_user_meta($ppcart_current_user->ID, 'last_name', true)); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-last-name')); ?>"/>
        </div>

        <div class="ppcart-form-group form-field">
            <label><?php esc_html_e('Email', 'publishpress-cart'); ?>: </label>
            <input type="text" name="email" value="<?php echo esc_attr($ppcart_current_user->user_email); ?>"  class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-email')); ?>"/>
        </div>

        <div class="ppcart-form-group form-field">
            <label><?php esc_html_e('Phone', 'publishpress-cart'); ?>: </label>
            <input type="text" name="_ppcart_phone" placeholder="<?php esc_attr_e('Phone', 'publishpress-cart'); ?>" value="<?php echo esc_attr($user_phone); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-phone')); ?>"/>
        </div>

        <div class="ep-edit-address">
            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('Address', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_address1" placeholder="<?php esc_attr_e('Address', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['address_1'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-address-1')); ?>"/>
            </div>

            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('Address 2', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_address2" placeholder="<?php esc_attr_e('Address 2', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['address_2'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-address-2')); ?>"/>
            </div>

            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('City', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_city" placeholder="<?php esc_attr_e('City', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['city'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-city')); ?>"/>
            </div>

            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('State', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_state" placeholder="<?php esc_attr_e('State', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['state'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-state')); ?>"/>
            </div>

            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('Zip', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_zip" placeholder="<?php esc_attr_e('Zip', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['zip'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-zip')); ?>"/>
            </div>

            <div class="ppcart-form-group form-field">
                <label><?php esc_html_e('Country', 'publishpress-cart'); ?>: </label>
                <input type="text" name="_ppcart_country" placeholder="<?php esc_attr_e('Country', 'publishpress-cart'); ?>" value="<?php echo esc_attr($address['country'] ?? ''); ?>" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-country')); ?>"/>
            </div>
        </div>

        <div id="ppcart-all-subscription-address-wrap" class="ppcart-form-group form-check" style="display: none">
            <label for="ppcart-all-subscription-address" class="custom-checkbox">
                <input type="checkbox" id="ppcart-all-subscription-address" name="ppcart-all-subscription-address" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-all-subscription-address')); ?>">
                <span class="checkmark"></span>
                <?php esc_html_e('Set default address for all active subscriptions', 'publishpress-cart'); ?>
            </label>
        </div>

        <div class="ppcart-form-group form-field" id="ppcart-new-password" style="display:none">
            <label><?php esc_html_e('New Password', 'publishpress-cart'); ?>: </label>
            <input type="password" name="password" placeholder="XXXXXXXXXX" value="" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-new-password')); ?>"/>
        </div>

        <div class="ppcart-form-group form-field" id="ppcart-confirm-new-password" style="display:none">
            <label><?php esc_html_e('Confirm Password', 'publishpress-cart'); ?>: </label>
            <input type="password" name="new_password" placeholder="XXXXXXXXXX" value="" class="ep_disabled" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-confirm-password')); ?>"/>
        </div>

        <div class="form-btn">
            <div id="ppcart-profile-alert"></div>
            <input type="button" class="ppcart-account-action-button" value="<?php esc_attr_e('Edit', 'publishpress-cart'); ?>" id="ppcart-edit-profile" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-edit')); ?>"/>
            <span id="ppcart-loader"><img src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/spinner.gif'); ?>"></span>
            <div class="btn-right">
                <input type="button" class="ppcart-account-action-button btn-save" value="<?php esc_attr_e('Save', 'publishpress-cart'); ?>" id="ppcart-update-profile" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-save')); ?>"/>
                <input type="button" class="ppcart-account-action-button btn-cancel" value="<?php esc_attr_e('Cancel', 'publishpress-cart'); ?>" id="ppcart-edit-profile-cancel" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-profile-cancel')); ?>"/>
            </div>
        </div>
    </form>
</div>

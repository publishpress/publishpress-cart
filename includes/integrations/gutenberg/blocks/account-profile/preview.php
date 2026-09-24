<?php

if (! defined('ABSPATH')) {
    exit;
}

$heading       = esc_html__('My Profile', 'publishpress-cart');
$first_label   = esc_html__('First Name', 'publishpress-cart');
$last_label    = esc_html__('Last Name', 'publishpress-cart');
$email_label   = esc_html__('Email', 'publishpress-cart');
$address_label = esc_html__('Address', 'publishpress-cart');
$edit          = esc_attr__('Edit', 'publishpress-cart');
$save          = esc_attr__('Save', 'publishpress-cart');
$cancel        = esc_attr__('Cancel', 'publishpress-cart');

ob_start();
?>
<div class="profile-wrapper ppcart">
    <h4><?php echo esc_html($heading); ?></h4>
    <form method="post" id="ppcart-update-profile-form" data-testid="ppcart-account-profile-form-preview">
        <div class="ppcart-form-group form-field"><label><?php echo esc_html($first_label); ?>: </label><input type="text" value="Jane" disabled data-testid="ppcart-account-profile-first-name-preview" /></div>
        <div class="ppcart-form-group form-field"><label><?php echo esc_html($last_label); ?>: </label><input type="text" value="Customer" disabled data-testid="ppcart-account-profile-last-name-preview" /></div>
        <div class="ppcart-form-group form-field"><label><?php echo esc_html($email_label); ?>: </label><input type="text" value="jane@example.com" disabled data-testid="ppcart-account-profile-email-preview" /></div>
        <div class="ppcart-form-group form-field"><label><?php echo esc_html($address_label); ?>: </label><input type="text" value="123 Example Street" disabled data-testid="ppcart-account-profile-address-preview" /></div>
        <div class="form-btn">
            <input type="button" class="ppcart-account-action-button" value="<?php echo esc_attr($edit); ?>" id="ppcart-edit-profile" data-testid="ppcart-account-profile-edit-preview" />
            <div class="btn-right">
                <input type="button" class="ppcart-account-action-button btn-save" value="<?php echo esc_attr($save); ?>" id="ppcart-update-profile" data-testid="ppcart-account-profile-save-preview" />
                <input type="button" class="ppcart-account-action-button btn-cancel" value="<?php echo esc_attr($cancel); ?>" id="ppcart-edit-profile-cancel" data-testid="ppcart-account-profile-cancel-preview" />
            </div>
        </div>
    </form>
</div>
<?php
return ob_get_clean();

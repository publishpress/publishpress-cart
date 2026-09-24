<?php

if (! defined('ABSPATH')) {
    exit;
}


$options = ['' => '--' . __('Select Integration', 'publishpress-cart') . '--'];

$activecampaign_url     = get_option('_ppcart_activecampaign_url');
$activecampaign_secret_key = ppcart_get_sensitive_option('_ppcart_activecampaign_secret_key');

//if activecampaign key exists
if ($activecampaign_url &&  $activecampaign_secret_key) {
    $options['activecampaign'] = "ActiveCampaign";
}


// create user
$options['create user'] = __('Create User', 'publishpress-cart');
$options['update user'] = __('Update User', 'publishpress-cart');

//mailchimp
$mailchimp_apikey = ppcart_get_sensitive_option('_ppcart_mailchimp_api');
if ($mailchimp_apikey) {
    $options['mailchimp'] = "MailChimp";
}

if (class_exists('MailPoet\API\API')) {
    $options['mailpoet'] = "MailPoet";
}

$member_vault_api_key   = ppcart_get_sensitive_option('_ppcart_member_vault_api_key');

//if MemberVault token exists
if ($member_vault_api_key) {
    $options['membervault'] = "MemberVault";
}

$sendfox_enable     = ppcart_get_sensitive_option('_ppcart_sendfox_api_key');
//if sendfox enable
if ($sendfox_enable) {
    $options['sendfox'] = "SendFox";
}

return apply_filters('ppcart_integrations', $options);

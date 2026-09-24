<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Mailchimp, ActiveCampaign, and SendFox list/tag helpers for admin settings.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Integrations_Trait
{
    /**
     * Whether Mailchimp API credentials are configured.
     *
     * Previously returned \MailchimpMarketing\ApiClient|null. Free no longer
     * bundles that SDK. This is a boolean alias of ppcart_has_mailchimp_api().
     * Use ppcart_mailchimp_api_request() for API calls. Do not access ->lists.
     *
     * @return bool
     */
    public function mailchimp_authentication()
    {
        return ppcart_has_mailchimp_api();
    }

    public function activecampaign_authentication()
    {
        $activecampaign_url = get_option('_ppcart_activecampaign_url');
        $activecampaign_secret_key = ppcart_get_sensitive_option('_ppcart_activecampaign_secret_key');
        if ($activecampaign_url && $activecampaign_secret_key) {
            return [
                'url' => rtrim($activecampaign_url, '/'),
                'key' => $activecampaign_secret_key,
            ];
        }
        return null;
    }

    public function get_mailchimp_lists($renew = false)
    {
        if (!$renew && $lists = get_option('ppcart_mailchimp_lists')) {
            return $lists;
        } else {
            $lists = [];
            if ($this->mailchimp_authentication()) {
                $result = ppcart_mailchimp_api_request('lists', 'GET', [], ['count' => 100]);
                if (! is_wp_error($result) && isset($result->lists) && ! empty($result->lists)) {
                    foreach ($result->lists as $list) {
                        if (isset($list->id, $list->name)) {
                            $lists[$list->id] = $list->name;
                        }
                    }
                }
            }
            update_option('ppcart_mailchimp_lists', $lists);
            return $lists;
        }
    }

    public function get_mailchimp_tags($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-get-mailchimp-tags.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_mailchimp_groups($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-get-mailchimp-groups.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_activecampaign_lists($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-get-activecampaign-lists.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_activecampaign_tags($renew = false)
    {
        $tags = [];
        $activecampaign = $this->activecampaign_authentication();
        if ($activecampaign) {
            $response = wp_safe_remote_get($activecampaign['url'] . '/api/3/tags?limit=100', [
                'headers' => [
                    'Api-Token' => $activecampaign['key'],
                ],
                'timeout' => 3,
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['tags'])) {
                    foreach ($body['tags'] as $value) {
                        $name = $value['tag'];
                        if (!empty($name)) {
                            $tags[$name] = $name;
                        }
                    }
                }
            }
        }
        update_option('ppcart_activecampaign_tags', $tags);
        return $tags;
    }

    public function get_sendfox_lists($renew = false)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-get-sendfox-lists.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Fetch all list page by page
     */
    private function get_sendfox_list($lists = [], $page = 1)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-ppcart-get-sendfox-list.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function mailchimp_groups_tags()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/integrations-ppcart-mailchimp-groups-tags.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function renew_integrations_lists()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'publishpress-cart'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
            esc_html_e('Ooops, something went wrong, please try again later.', 'publishpress-cart');
            die;
        }
        $mc_lists   = $this->get_mailchimp_lists(true);
        $mc_groups  = $this->get_mailchimp_groups(true);
        $mc_tags    = $this->get_mailchimp_tags(true);
        $ac_lists   = $this->get_activecampaign_lists(true);
        $ac_tags    = $this->get_activecampaign_tags(true);
        $sf_lists   = $this->get_sendfox_lists(true);
        do_action('ppcart_renew_integrations_lists');

        esc_html_e("OK", "publishpress-cart");
        wp_die();
    }
}

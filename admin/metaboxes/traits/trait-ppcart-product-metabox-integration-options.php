<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metabox_Integration_Options_Trait
{
    public function get_rcp_levels()
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context check for current admin post.
        if (!isset($_GET['post'])) {
            return;
        }

        if (function_exists('rcp_get_membership_levels')) {
            $options = ['' => __('-- Select Membership Level --', 'publishpress-cart')];

            $levels    = rcp_get_membership_levels(['status' => 'active']);

            if (! empty($levels)) {
                foreach ($levels as $level) {
                    $options[$level->id] = $level->name;
                }
                return $options;
            }
        }

        return ['' => __('-- no options found --', 'publishpress-cart')];
    }

    public function get_mailchimp_lists()
    {
        $options = ['' => ''];
        $lists = get_option('ppcart_mailchimp_lists');
        if (!empty($lists)) {
            $options = array_merge($options, $lists);
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }

        return $options;
    }

    public function get_mailchimp_tags()
    {

        $options = ['' => ''];
        $mctags = get_option('ppcart_mailchimp_tags');

        if (!empty($mctags)) {
            $options = ['' => ''];
            foreach ($mctags as $list => $tags) {
                $options = array_merge($options, $tags);
            }
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }
        return $options;
    }

    public function get_mailchimp_groups()
    {
        $options = ['' => __('No Groups Found', 'publishpress-cart')];
        $mcgroups = get_option('ppcart_mailchimp_groups');

        if (!empty($mcgroups)) {
            $options = ['' => ''];
            foreach ($mcgroups as $list => $groups) {
                $options = array_merge($options, $groups);
            }
        }

        return $options;
    }

    public function get_activecampaign_lists()
    {
        $lists = get_option('ppcart_activecampaign_lists');

        if (!empty($lists)) {
            $options = $lists;
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }
        return $options;
    }

    public function get_activecampaign_tags()
    {
        $tags = get_option('ppcart_activecampaign_tags');
        if (!empty($tags)) {
            $options = ["" => __("Select", "publishpress-cart")];
            $options += $tags;
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }
        return $options;
    }

    public function get_wlm_levels()
    {

        if (!function_exists('wlmapi_get_levels')) {
            return;
        }

        $levels = wlmapi_get_levels();

        if (!empty($levels['levels']['level'])) {
            $options = ['' => __('-- Select --', 'publishpress-cart')];
            foreach ($levels['levels']['level'] as $level) {
                $options[$level['id']] = $level['name'];
            }
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }
        return $options;
    }

    public function tutor_courses()
    {
        $options = ['' => __('-- none found --', 'publishpress-cart')];

        if (class_exists('\TUTOR\Utils')) {
            $tutor = new \TUTOR\Utils();
            $courses = $tutor->get_courses(0, ['publish', 'pending', 'private']);
            $options = ['' => __('-- select --', 'publishpress-cart')];
            foreach ($courses as $course) {
                $options[$course->ID] = $course->post_title;
            }
        }
        return $options;
    }

    public function get_sendfox_lists()
    {

        $lists = get_option('ppcart_sendfox_lists');
        if (!empty($lists)) {
            $options = ["" => __("Select", "publishpress-cart")];
            $options += $lists;
        } else {
            $options = ['' => __('-- none found --', 'publishpress-cart')];
        }
        return $options;
    }

    public function get_mailpoet_lists()
    {
        $options = ['' => __('-- none found --', 'publishpress-cart')];

        if (class_exists(\MailPoet\API\API::class)) {
            $options = ["" => __("Select", "publishpress-cart")];
            // Get MailPoet API instance
            $mailpoet_api = \MailPoet\API\API::MP('v1');
            // Get available list so that a subscriber can choose in which to subscribe
            $lists = $mailpoet_api->getLists();
            foreach ($lists as $list) {
                $options[$list['id']] = $list['name'];
            }
        }
        return $options;
    }

    public static function fa_icons()
    {
        $options = ['none' => __('none', 'publishpress-cart')];
        $files = require(defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR : dirname(__DIR__, 3) . '/') . 'includes/assets/font-awesome-icons.php';

        if (! is_array($files)) {
            return $options;
        }

        foreach ($files as $file) {
            $name = str_replace('.svg', '', $file);
            $options[$file] = $name;
        }

        return $options;
    }
}

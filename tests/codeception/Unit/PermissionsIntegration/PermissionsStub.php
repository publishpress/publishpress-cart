<?php

namespace PublishPress\Permissions {
    use Tests\Support\WordPressStubContext;

    class API
    {
        public static function getGroups($type, $args)
        {
            return WordPressStubContext::getState('permissions_groups', []);
        }

        public static function getGroup($id, $type)
        {
            $groups = self::getGroups($type, []);
            return $groups[$id] ?? null;
        }

        public static function addGroupUser($group_id, $user_ids)
        {
            return WordPressStubContext::invoke('permissions_membership', ['add', $group_id, $user_ids]);
        }

        public static function removeGroupUser($group_id, $user_ids)
        {
            return WordPressStubContext::invoke('permissions_membership', ['remove', $group_id, $user_ids]);
        }
    }
}

namespace PublishPress\Cart {
    function ppcart_get_order_user_id($order, $create)
    {
        return \Tests\Support\WordPressStubContext::invoke('permissions_order_user', [$order, $create]);
    }
}

namespace {
    if (!function_exists('presspermit')) {
        function presspermit()
        {
            return \Tests\Support\WordPressStubContext::getState('permissions_available', false) ? (object) [] : false;
        }
    }
}

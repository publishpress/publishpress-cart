<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Product_Metaboxes_Message_Fields_Trait
{
    private function set_message_field_groups($save)
    {
        include __DIR__ . '/../field-groups/message-fields.php';
    }
}

<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Renderer_Metadata
{
    private function get_metadata($block_slug)
    {
        $metadata_file = dirname(__DIR__, 2) . '/blocks/' . $block_slug . '/block.json';

        if (! file_exists($metadata_file)) {
            return [];
        }

        $metadata = wp_json_file_decode($metadata_file, ['associative' => true]);

        return is_array($metadata) ? $metadata : [];
    }
}

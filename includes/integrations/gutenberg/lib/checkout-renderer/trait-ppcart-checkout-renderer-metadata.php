<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Checkout_Renderer_Metadata
{
    private function get_default_attributes()
    {
        if (! $this->metadata) {
            $this->metadata = $this->get_metadata();
        }

        $metadata = $this->metadata;
        $defaults = [];

        if (empty($metadata['attributes']) || ! is_array($metadata['attributes'])) {
            return $defaults;
        }

        foreach ($metadata['attributes'] as $name => $schema) {
            if (isset($schema['default'])) {
                $defaults[ $name ] = $schema['default'];
            }
        }

        return $defaults;
    }

    private function get_metadata()
    {
        $metadata_file = dirname(__DIR__, 2) . '/blocks/checkout-form/block.json';

        if (! file_exists($metadata_file)) {
            return [];
        }

        $metadata = wp_json_file_decode($metadata_file, ['associative' => true]);

        return is_array($metadata) ? $metadata : [];
    }
}

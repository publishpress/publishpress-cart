<?php

/**
 * Bounded binary uploads through WordPress into Cart's native secure directory.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Upload
{
    private $permissions;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct($products)
    {
        $this->permissions = new PPCart_Mcp_Products($products);
    }

    /** @since 1.1.0 @return array */
    public function schema()
    {
        return PPCart_Mcp_Input::schema([
            'id' => ['type' => 'integer', 'minimum' => 1],
            'filename' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 200],
            'content_base64' => ['type' => 'string', 'minLength' => 4, 'maxLength' => 2796204],
            'sha256' => ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'],
        ], ['id', 'filename', 'content_base64', 'sha256']);
    }

    /** @since 1.1.0 @param mixed $input Input. @return bool */
    public function permitted($input)
    {
        $input = PPCart_Mcp_Input::object($input);
        return ! is_wp_error($input) && isset($input['id']) && is_int($input['id'])
            && current_user_can('upload_files') && $this->permissions->can_edit($input['id']);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function create($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema());
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted($input)) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot upload a secure product file.', 'publishpress-cart'));
        }
        $bytes = base64_decode($input['content_base64'], true);
        $name = sanitize_file_name($input['filename']);
        $type = wp_check_filetype($name, get_allowed_mime_types());
        if (
            false === $bytes || '' === $bytes || strlen($bytes) > min(2097152, wp_max_upload_size())
            || ! hash_equals($input['sha256'], hash('sha256', $bytes))
            || $name !== $input['filename'] || ! $type['type'] || ! $type['ext']
        ) {
            return PPCart_Mcp_Input::error();
        }
        $files = PPCart_Files::get_active_handler();
        if (! $files) {
            return new WP_Error('ppcart_mcp_unavailable', __('Secure downloads are unavailable.', 'publishpress-cart'));
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $files->setup_directory();
        $uploads = wp_upload_dir();
        $root = trailingslashit($uploads['basedir']) . 'ppcart-uploads';
        if (! is_file($root . '/.htaccess') || ! is_file($root . '/index.html')) {
            return new WP_Error('ppcart_mcp_upload_failed', __('Secure upload directory could not be prepared.', 'publishpress-cart'));
        }
        $temporary = wp_tempnam($name);
        if (! $temporary) {
            return new WP_Error('ppcart_mcp_upload_failed', __('The upload could not be prepared.', 'publishpress-cart'));
        }
        $callback = [$files, 'secure_upload_dir'];
        try {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Plugin-owned bounded temporary upload; removed in finally.
            if (strlen($bytes) !== file_put_contents($temporary, $bytes)) {
                return new WP_Error('ppcart_mcp_upload_failed', __('The upload could not be prepared.', 'publishpress-cart'));
            }
            add_filter('upload_dir', $callback, PHP_INT_MAX);
            try {
                $file = ['name' => $name, 'tmp_name' => $temporary,
                    'size' => strlen($bytes), 'error' => 0, 'type' => $type['type']];
                $upload = wp_handle_sideload($file, ['test_form' => false]);
            } finally {
                remove_filter('upload_dir', $callback, PHP_INT_MAX);
            }
            if (! empty($upload['error'])) {
                return new WP_Error('ppcart_mcp_upload_failed', __('WordPress rejected the uploaded file.', 'publishpress-cart'));
            }
            if (! PPCart_Files::resolve_local_download_path($upload['file'])) {
                wp_delete_file($upload['file']);
                return new WP_Error('ppcart_mcp_upload_failed', __('The file is outside the secure download roots.', 'publishpress-cart'));
            }
            $id = wp_insert_attachment(['post_title' => pathinfo($name, PATHINFO_FILENAME),
                'post_mime_type' => $upload['type'], 'post_status' => 'inherit',
                'post_author' => get_current_user_id(), 'post_parent' => $input['id']], $upload['file'], $input['id'], true);
            if (is_wp_error($id)) {
                wp_delete_file($upload['file']);
                return new WP_Error('ppcart_mcp_upload_failed', __('The secure attachment could not be registered.', 'publishpress-cart'));
            }
            return ['attachment_id' => $id, 'mime_type' => $upload['type'], 'bytes' => strlen($bytes), 'sha256' => $input['sha256']];
        } finally {
            wp_delete_file($temporary);
        }
    }
}

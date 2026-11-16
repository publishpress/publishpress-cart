<?php

/**
 * Checked native order download access administration without bearer links.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Download_Access
{
    /** @since 1.1.0 @param string $operation Operation. @return array */
    public function schema($operation)
    {
        $properties = ['id' => ['type' => 'integer', 'minimum' => 1]];
        if ('read' !== $operation) {
            $properties['download_id'] = ['type' => 'integer', 'minimum' => 1];
            $properties['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $properties['dry_run'] = ['type' => 'boolean'];
            if ('update' === $operation) {
                $properties['changes'] = PPCart_Mcp_Input::schema([
                    'downloads_remaining' => ['type' => ['integer', 'null'], 'minimum' => 0, 'maximum' => 2147483647],
                    'download_expires' => ['type' => ['string', 'null'], 'maxLength' => 19, 'pattern' => '^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$'],
                ]);
            }
        }
        $required = 'read' === $operation ? ['id'] : ['id', 'download_id', 'revision'];
        if ('update' === $operation) {
            $required[] = 'changes';
        }
        return PPCart_Mcp_Input::schema($properties, $required);
    }

    /** @since 1.1.0 @param int $id Order. @return bool */
    private function permitted($id)
    {
        $files = PPCart_Files::get_active_handler();
        $records = new PPCart_Mcp_Records();
        return $files instanceof PPCart_Files && PPCart_Mcp_Payments::can_manage()
            && ! is_wp_error($records->read(['kind' => 'order', 'id' => $id]));
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('read'));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted($input['id'])) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage downloads for this order.', 'publishpress-cart'));
        }
        global $wpdb;
        $files = PPCart_Files::get_active_handler();
        $native = $files->get_download_access_records($input['id'], ['status' => 'all']);
        if ($wpdb->last_error) {
            return new WP_Error('ppcart_mcp_storage_unavailable', __('Download access could not be read.', 'publishpress-cart'));
        }
        $safe = [];
        foreach ($native ?: [] as $row) {
            $safe[] = ['download_id' => (int) $row->download_id, 'product_id' => (int) $row->product_id, 'file_id' => $row->file_id,
                'downloads_remaining' => $row->downloads_remaining, 'download_expires' => $row->download_expires];
        }
        return ['id' => $input['id'], 'downloads' => $safe,
            'revision' => hash_hmac('sha256', wp_json_encode($native ?: []), wp_salt('nonce'))];
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        return $this->write($input, false);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function revoke($input)
    {
        return $this->write($input, true);
    }

    /** @since 1.1.0 @param mixed $input Input. @param bool $revoke Revoke. @return array|WP_Error */
    private function write($input, $revoke)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema($revoke ? 'revoke' : 'update'));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read(['id' => $input['id']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Download access changed. Read it again.', 'publishpress-cart'));
        }
        $files = PPCart_Files::get_active_handler();
        $target = null;
        foreach ($files->get_download_access_records($input['id'], ['status' => 'all']) ?: [] as $row) {
            if ((int) $row->download_id === $input['download_id']) {
                $target = $row;
            }
        }
        if (! $target) {
            return PPCart_Mcp_Input::error();
        }
        $changes = [];
        if (! $revoke) {
            if (! $input['changes']) {
                return PPCart_Mcp_Input::error();
            }
            foreach ($input['changes'] as $key => $value) {
                if ('download_expires' === $key && null !== $value) {
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
                    if (! $date || $date->format('Y-m-d H:i:s') !== $value) {
                        return PPCart_Mcp_Input::error();
                    }
                }
                $changes[$key] = 'downloads_remaining' === $key ? (null === $value ? 'unlimited' : (string) $value) : $value;
            }
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'download_id' => $input['download_id'], 'revoke' => $revoke, 'changes' => (object) $changes];
        }
        $current = $this->read(['id' => $input['id']]);
        if (is_wp_error($current) || ! hash_equals($before['revision'], $current['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Download access changed before saving.', 'publishpress-cart'));
        }
        if ($revoke) {
            $result = $files->revoke_access($input['download_id']);
        } else {
            $result = $files->update_download_access($input['id'], $input['download_id'], $changes, (array) $target);
        }
        if (false === $result || ($revoke && 1 !== $result)) {
            return new WP_Error('ppcart_mcp_save_failed', __('Download access changes could not be confirmed.', 'publishpress-cart'));
        }
        $after = $this->read(['id' => $input['id']]);
        if (is_wp_error($after)) {
            return $after;
        }
        $found = false;
        foreach ($after['downloads'] as $row) {
            if ($input['download_id'] === $row['download_id']) {
                $found = true;
            }
            if ($input['download_id'] !== $row['download_id']) {
                continue;
            }
            if ($revoke) {
                return new WP_Error('ppcart_mcp_save_failed', __('Download revocation could not be confirmed.', 'publishpress-cart'));
            }
            foreach ($changes as $key => $value) {
                if ($row[$key] !== $value) {
                    return new WP_Error('ppcart_mcp_save_failed', __('Download access changed during saving. Read it again.', 'publishpress-cart'));
                }
            }
        }
        if (! $revoke && ! $found) {
            return new WP_Error('ppcart_mcp_save_failed', __('Download access disappeared during saving.', 'publishpress-cart'));
        }
        return $after;
    }
}

<?php

if (! defined('ABSPATH')) {
    exit;
}


$user_id = get_current_user_id();

if (!$user_id) {
    return false;
}

$downloads = [];

$orders = ppcart_get_user_orders($user_id);
if (! is_array($orders)) {
    $orders = [];
}
foreach ($orders as $user_order) {
    if ($order_downloads = $this->get_order_downloads($user_order['ID'])) {
        $downloads = array_merge($downloads, $order_downloads);
    }
}

if ($downloads) : ?>
    <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'publishpress-cart'); ?></th>
                <th><?php esc_html_e('Expires', 'publishpress-cart'); ?></th>
                <th><?php esc_html_e('Downloads Remaining', 'publishpress-cart'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($downloads as $download) : ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($download->url); ?>" target="_blank"><?php echo esc_html($download->name); ?></a>
                </td>
                <td><?php echo esc_html($download->expires); ?></td>
                <td><?php echo esc_html($download->downloads_remaining); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;

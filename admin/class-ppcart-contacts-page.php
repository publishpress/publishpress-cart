<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */

/**
 * The Customer-report-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the Customer-specific stylesheet and JavaScript.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Contacts_Page
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The Nice Name of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $plugin_title    The Nice Name of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $plugin_title, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;
    }

    /**
     * This function introduces the plugin options into a top-level
     * 'CreativCart' menu.
     */
    public function setup_plugin_options_menu()
    {

        add_submenu_page(
            PPCart_Admin_Screens::menu_slug(),
            apply_filters($this->plugin_name . '-settings-page-title', esc_html__('Contacts', 'publishpress-cart')),
            apply_filters($this->plugin_name . '-settings-menu-title', esc_html__('Contacts', 'publishpress-cart')),
            ppcart_live_cap('manager_option'),
            PPCart_Admin_Screens::PAGE_CONTACTS,
            [ $this, 'render_page_contacts' ]
        );
    }
    /**
     * Render the Contacts admin page (customer list and lifetime-value table).
     *
     * @param string $active_tab Unused; kept for settings-page callback compatibility.
     * @return void
     */
    public function render_page_contacts($active_tab = '')
    {
        global $ppcart_currency_symbol, $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Reporting query across orders/postmeta is intentionally SQL-based for grouped contact listing.
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
        $get_user = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_value
                FROM {$wpdb->posts}
                INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
                WHERE {$wpdb->postmeta}.meta_key = %s
                    AND {$wpdb->posts}.post_type IN (" . ppcart_sql_in_post_types('order') . ")
                    AND {$wpdb->posts}.post_status NOT IN (%s, %s)
                ORDER BY {$wpdb->posts}.post_date DESC",
                ppcart_meta_key('email'),
                'trash',
                'auto-draft'
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $user_contact_array = [];

        foreach ($get_user as $post) {
            $user_email   = strtolower($post->meta_value);
            $status       = PPCart_Status_Labels::edit_select_value(PPCart_Status_Labels::logical_from_post($post->ID));
            $total_amount = 0;

            if ('refunded' === ppcart_get_post_meta($post->ID, 'payment_status', true)) {
                $refund_logs_entrie = ppcart_get_post_meta($post->ID, 'refund_log', true);
                $total_amount       = ppcart_get_post_meta($post->ID, 'amount', true);
                if (is_array($refund_logs_entrie)) {
                    $refund_amount_values = array_map(
                        'floatval',
                        array_column($refund_logs_entrie, 'amount')
                    );
                    $refund_amount = array_sum($refund_amount_values);
                    $total_amount  = floatval(ppcart_get_post_meta($post->ID, 'amount', true)) - $refund_amount;
                }
            } elseif ('paid' === $status) {
                $total_amount = ppcart_get_post_meta($post->ID, 'amount', true);
            }

            if ('paid' === $status) {
                $user_contact_array[ $user_email ][] = [ 'id' => $post->ID, 'total_amount' => $total_amount ];
            } else {
                $user_contact_array[ $user_email ][] = [ 'id' => $post->ID, 'total_amount' => 0 ];
            }
        }

        $contact_count  = count($user_contact_array);
        $order_count    = count($get_user);
        $lifetime_value = 0;

        foreach ($user_contact_array as $value) {
            $lifetime_value += array_sum(
                array_map(
                    'floatval',
                    array_column($value, 'total_amount')
                )
            );
        }
        ?>
        <div class="wrap ppcart-contacts-page">
            <div class="ppcart-contacts-header">
                <div class="ppcart-contacts-title">
                    <?php /* translators: %s: plugin title. */ ?>
                    <h1 class="wp-heading-inline"><?php echo esc_html(sprintf(__('%s Contacts', 'publishpress-cart'), apply_filters('ppcart_plugin_title', $this->plugin_title))); ?></h1>
                </div>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg([ 'ppcart-csv-export' => 'contacts', 'type' => 'contact' ], home_url('/')), 'ppcart_csv_export')); ?>" id="customer_csv_export__" class="page-title-action ppcart-contacts-export" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-contacts-export')); ?>">
                    <?php esc_html_e('Export Contacts', 'publishpress-cart'); ?>
                </a>
            </div>

            <div class="ppcart-contact-metrics" aria-label="<?php esc_attr_e('Contact summary', 'publishpress-cart'); ?>">
                <div class="ppcart-contact-metric">
                    <span><?php esc_html_e('Contacts', 'publishpress-cart'); ?></span>
                    <strong><?php echo esc_html(number_format_i18n($contact_count)); ?></strong>
                </div>
                <div class="ppcart-contact-metric">
                    <span><?php esc_html_e('Orders', 'publishpress-cart'); ?></span>
                    <strong><?php echo esc_html(number_format_i18n($order_count)); ?></strong>
                </div>
                <div class="ppcart-contact-metric">
                    <span><?php esc_html_e('Lifetime value', 'publishpress-cart'); ?></span>
                    <strong><?php echo esc_html($ppcart_currency_symbol . number_format($lifetime_value, 2)); ?></strong>
                </div>
            </div>

            <div class="ppcart-reports ppcart-contacts-table-wrap">
                <table id="contacts_table" cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped table-view-list posts ppcart-contacts-table" width="100%">
                    <thead>
                        <tr>
                            <th class="item column-date" style="display: none;"><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                            <th class="item column-email"><?php esc_html_e('Email', 'publishpress-cart'); ?></th>
                            <th class="item column-name"><?php esc_html_e('Name', 'publishpress-cart'); ?></th>
                            <th class="item column-orders"><?php esc_html_e('Orders', 'publishpress-cart'); ?></th>
                            <th class="item column-ltv"><?php esc_html_e('LTV', 'publishpress-cart'); ?></th>
                            <th class="item column-last-order"><?php esc_html_e('Last Order Date', 'publishpress-cart'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                        <?php
                        foreach ($user_contact_array as $key => $value) {
                            $post_id            = $value[0]['id'];
                            $paid_amount_values = array_map(
                                'floatval',
                                array_column($value, 'total_amount')
                            );
                            $paid_amount        = array_sum($paid_amount_values);
                            $num_of_record      = count($value);
                            ?>
                            <tr>
                                <td valign="top" style="display: none;"><?php echo esc_html(get_the_time('Y-m-d h:i:s', $post_id)); ?></td>
                                <td valign="top" class="column-email"><a href="<?php echo esc_url(add_query_arg([ 'page' => PPCart_Admin_Screens::PAGE_CUSTOMER_REPORTS, 'reportstypes' => 'order', 'customerid' => $key ], admin_url('admin.php'))); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-contact-' . $key . '-reports')); ?>"><?php echo esc_html($key); ?></a></td>
                                <td valign="top" class="column-name"><?php echo esc_html(ppcart_get_post_meta($post_id, 'firstname', true) . ' ' . ppcart_get_post_meta($post_id, 'lastname', true)); ?></td>
                                <td valign="top" class="column-orders"><a href="<?php echo esc_url(add_query_arg([ 'post_type' => ppcart_live_post_type('order'), 'order_email' => $key ], admin_url('edit.php'))); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-contact-' . $key . '-orders')); ?>"><?php echo esc_html(number_format_i18n($num_of_record)); ?></a></td>
                                <td valign="top" class="column-ltv"><?php echo esc_html($ppcart_currency_symbol . number_format($paid_amount, 2)); ?></td>
                                <td valign="top" class="column-last-order"><?php echo esc_html(get_the_time('M j, Y', $post_id)); ?></td>
                            </tr>
                            <?php
                        }
        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        $contacts_table_i18n = [
            'searchPlaceholder' => __('Search contacts', 'publishpress-cart'),
            'lengthMenu'        => __('Show _MENU_ contacts', 'publishpress-cart'),
            'info'              => __('_START_ - _END_ of _TOTAL_ contacts', 'publishpress-cart'),
            'infoEmpty'         => __('No contacts found', 'publishpress-cart'),
            'zeroRecords'       => __('No matching contacts found', 'publishpress-cart'),
        ];

        wp_add_inline_script(
            'ppcart-datatables',
            str_replace(
                '__CONTACTS_TABLE_I18N__',
                wp_json_encode($contacts_table_i18n),
                "
            jQuery(document).ready( function ( $ ) {
                var \$contactsTable = $( '#contacts_table' );
                var contactsTableI18n = __CONTACTS_TABLE_I18N__;

                if ( ! $.fn.DataTable || ! \$contactsTable.length ) {
                    return;
                }

                if ( $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable( \$contactsTable[0] ) ) {
                    return;
                }

                \$contactsTable.DataTable( {
                    pageLength: 25,
                    order: [[ 0, 'desc' ]],
                    language: {
                        search: '',
                        searchPlaceholder: contactsTableI18n.searchPlaceholder,
                        lengthMenu: contactsTableI18n.lengthMenu,
                        info: contactsTableI18n.info,
                        infoEmpty: contactsTableI18n.infoEmpty,
                        zeroRecords: contactsTableI18n.zeroRecords
                    }
                } );
            } );
                "
            )
        );
        ?>
        <?php
    }
}

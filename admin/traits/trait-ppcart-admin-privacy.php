<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * WordPress privacy exporter and eraser callbacks for Cart order data.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Privacy_Trait
{
    public function register_erasers($erasers = [])
    {
        $erasers[] = [
            'eraser_friendly_name' => apply_filters('ppcart_plugin_title', $this->plugin_title),
            'callback'               => [$this, 'user_data_eraser'],
        ];

        return $erasers;
    }

    /**
     * Eraser for Plugin user data.
     *
     * @param $email_address
     * @param int $page
     *
     * @return array
     */
    public function user_data_eraser($email_address, $page = 1)
    {

        if (empty($email_address)) {
            return [
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => [],
                'done'           => true,
            ];
        }

        $done = true;
        $messages = [];
        $items_removed  = 0;
        $items_retained = 0;

        $args = [
                'post_type' => array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
                'post_status' => 'any',
                'posts_per_page' => 100,
                'paged' => $page,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Querying order/subscription records by customer email meta is required here.
                'meta_query' => [
                    [
                        'key' => ppcart_meta_key('email'),
                        'value' => $email_address,
                    ],
                ],
            ];
        $results = new WP_Query($args);
        if ($results->max_num_pages > $page) {
            $done = false;
        }

        if ($results->have_posts()) {
            while ($results->have_posts()) {
                $results->the_post();
                $id = get_the_ID();

                $fields = [
                    ppcart_meta_key('firstname') => 'first name',
                    ppcart_meta_key('lastname')  => 'last name',
                    ppcart_meta_key('email')     => 'email',
                    ppcart_meta_key('phone')     => 'phone',
                    ppcart_meta_key('country')   => 'country',
                    ppcart_meta_key('address1')  => 'address',
                    ppcart_meta_key('address2')  => 'address (line 2)',
                    ppcart_meta_key('city')      => 'city',
                    ppcart_meta_key('state')     => 'state',
                    ppcart_meta_key('zip')       => 'zip',
                    ppcart_meta_key('ip_address') => 'IP address',
                ];

                foreach ($fields as $k => $v) {
                    if (ppcart_get_post_meta($id, $k, true)) {
                        ppcart_delete_post_meta($id, $k);
                        if (ppcart_update_post_meta($id, $k, __('[removed]', 'publishpress-cart'), $v)) {
                            $items_removed++;
                        } else {
                            /* translators: 1: field label, 2: order ID. */
                            $messages[] = sprintf(__('There was a problem removing your %1$s from order #%2$d.', 'publishpress-cart'), $v, $id);
                            $items_retained++;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // Returns an array of exported items for this pass, but also a boolean whether this exporter is finished.
        //If not it will be called again with $page increased by 1.
        return [
            'items_removed'  => $items_removed,
            'items_retained' => $items_retained,
            'messages'       => $messages,
            'done'           => $done,
        ];
    }

    public function register_exporter($exporters_array)
    {
        $exporters_array['ppcart_exporter'] = [
            'exporter_friendly_name' => 'PublishPress Cart exporter', // isn't shown anywhere
            'callback' => [$this, 'user_data_exporter'], // name of the callback function which is below
        ];
        return $exporters_array;
    }

    public function user_data_exporter($email_address, $page = 1)
    {

        $export_items = [];
        $done = true;

        $args = [
                'post_type' => array_merge(ppcart_query_post_types('order'), ppcart_query_post_types('subscription')),
                'post_status' => 'any',
                'posts_per_page' => 100,
                'paged' => $page,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Querying order/subscription records by customer email meta is required here.
                'meta_query' => [
                    [
                        'key' => ppcart_meta_key('email'),
                        'value' => $email_address,
                    ],
                ],
            ];
        $results = new WP_Query($args);
        if ($results->max_num_pages > $page) {
            $done = false;
        }

        if ($results->have_posts()) {
            while ($results->have_posts()) {
                $results->the_post();
                $id = get_the_ID();

                $fields = [
                    ppcart_meta_key('firstname') => 'First Name',
                    ppcart_meta_key('lastname')  => 'Last Name',
                    ppcart_meta_key('email')     => 'Email',
                    ppcart_meta_key('phone')     => 'Phone',
                    ppcart_meta_key('country')   => 'Country',
                    ppcart_meta_key('address1')  => 'Address',
                    ppcart_meta_key('address2')  => 'Address (Line 2)',
                    ppcart_meta_key('city')      => 'City',
                    ppcart_meta_key('state')     => 'State',
                    ppcart_meta_key('zip')       => 'Zip',
                    ppcart_meta_key('ip_address') => 'IP Address',
                ];
                $data = [
                    ['name' => 'Order ID', 'value' => get_the_ID()],
                ];
                foreach ($fields as $k => $v) {
                    if ($val = ppcart_get_post_meta($id, $k, true)) {
                        $data[] = [
                                    'name' => $v,
                                    'value' => $val,
                                ];
                    }
                }

                $export_items[] = [
                    'group_id' => 'ppcart-orders',
                    'group_label' => 'PublishPress Cart Orders',
                    'item_id' => 'order-' . get_the_ID(),
                    'data' => $data,
                ];
            }
            wp_reset_postdata();
        }

        // Tell core if we have more orders to work on still
        return [
            'data' => $export_items,
            'done' => $done,
        ];
    }

    public function privacy_declarations()
    {

        $content =
            __(
                '<p class="privacy-policy-tutorial">This sample language includes the basics around what personal data your PublishPress Cart installation may be collecting, storing and sharing, as well as who may have access to that data. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your site will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.</p>
<h2>What we collect and store</h2>
<p>We collect information about you during the checkout process as well as some basic activities such as the dates you make purchases, or cancel your subscriptions with us.</p>
<p>While you visit our site, we’ll track:</p>
<ul>
   <li>— Products you’ve viewed:  we’ll use this to, for example, send you reminders about products you’ve recently viewed</li>
   <li>— IP address: we’ll use this for purposes like tracking which products you\'ve purchased and what discounts you’re eligible for</li>
   <li>— Name, email and physical address: we’ll ask you to enter this so we can communicate with you about your order and deliver your order to you!</li>
</ul>
<p>When you purchase from us, we’ll ask you to provide information including your name, billing/shipping address, email address, phone number, credit card/payment details and optional account information like username and password. We’ll use this information for purposes, such as, to:</p>
<ul>
   <li>— Send you information about your account and order</li>
   <li>— Respond to your requests, including refunds and complaints</li>
   <li>— Process payments and prevent fraud</li>
   <li>— Set up your account for our store</li>
   <li>— Improve our store offerings</li>
   <li>— Send you marketing messages, if you choose to receive them</li>
</ul>
<p>If you create an account, we will store your name, address, email and phone number, which will be used to populate the checkout for future orders.</p>
<p>We generally store information about you for as long as we need the information for the purposes for which we collect and use it, and we are not legally required to continue to keep it. For example, we will store order information for XXX years for tax and accounting purposes. This includes your name, email address and billing/shipping address.</p>
<h2>Who on our team has access</h2>
<p>Members of our team have access to the information you provide us. For example, site Owner/Administrators can access:</p>
<ul>
   <li>— Order information like what was purchased, subscription information, payment dates and amounts, and</li>
   <li>— Customer information like your name, username / email address, and address information.</li>
</ul>
<p>Our team members have access to this information to help fulfill orders, process refunds and support you.</p>
<h2>What we share with others</h2>
<p><em>
  In this section you should list who you’re sharing data with, and for what purpose. This could include, but may not be limited to, analytics/reporting tools, marketing services (such as email services like MailChimp, ActiveCampaign or Kit), payment gateways, and third party embeds.
</em></p>
<p><em>
We share information with third parties who help us provide additional contact services to you; for example – [enter your third party platforms such as Analytics, Email Marketing, or any others and short description of their purpose. If you have a DPA from that service, this would be a good place to include that also.]
</em></p>
<h3>Payments</h3>
<p class="privacy-policy-tutorial">In this subsection you should list which third party payment processors you’re using to take payments on your store since these may handle customer data. We’ve included Stripe as an example, but you should remove this if you’re not using Stripe.</p>
<p>We accept payments through Stripe. When processing payments, some of your data will be passed to Stripe, including information required to process or support the payment, such as the purchase total and billing information.</p>
<p>Please see the <a href="https://stripe.com/privacy">Stripe Privacy Policy</a> for more details.</p>',
                'publishpress-cart'
            );

        wp_add_privacy_policy_content(
            'PublishPress Cart',
            wp_kses_post($content)
        );
    }
}

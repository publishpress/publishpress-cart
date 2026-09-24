<?php

namespace unit\ProductDuplicate;

use Codeception\Test\Unit;
use PPCart_Product_Duplicator;
use Tests\Support\WordPressStubContext;
use UnitTester;

class ProductDuplicatorTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var int
     */
    private $sourceId = 101;

    /**
     * @var int|null
     */
    private $duplicateId;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private $sourceMetaBefore;

    protected function _before(): void
    {
        if (! function_exists('ppcart_meta_key')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-meta.php';
        }

        if (! function_exists('ppcart_live_post_type')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/helpers/ppcart-live.php';
        }

        WordPressStubContext::setState('posts', array());
        WordPressStubContext::setState('meta', array());
        WordPressStubContext::setState('options', array());
        WordPressStubContext::setState('terms', array());
        WordPressStubContext::setState('next_post_id', 2000);

        WordPressStubContext::set(
            'apply_filters',
            function ($hook, $value) {
                return $value;
            }
        );
        WordPressStubContext::set('get_current_user_id', function () {
            return 777;
        });
        WordPressStubContext::set(
            'get_post',
            function ($post_id) {
                $posts = WordPressStubContext::getState('posts', array());

                return isset($posts[$post_id]) ? $posts[$post_id] : null;
            }
        );
        WordPressStubContext::set(
            'get_post_meta',
            function ($post_id, $key = '', $single = false) {
                $meta = WordPressStubContext::getState('meta', array());

                if ('' === $key) {
                    return isset($meta[$post_id]) ? $meta[$post_id] : array();
                }

                if (! isset($meta[$post_id][$key])) {
                    return $single ? '' : array();
                }

                return $single ? $meta[$post_id][$key][0] : $meta[$post_id][$key];
            }
        );
        WordPressStubContext::set(
            'get_option',
            function ($key, $default = false) {
                $options = WordPressStubContext::getState('options', array());

                return array_key_exists($key, $options) ? $options[$key] : $default;
            }
        );
        WordPressStubContext::set(
            'add_post_meta',
            function ($post_id, $key, $value) {
                $meta = WordPressStubContext::getState('meta', array());

                if (! isset($meta[$post_id])) {
                    $meta[$post_id] = array();
                }
                if (! isset($meta[$post_id][$key])) {
                    $meta[$post_id][$key] = array();
                }

                $meta[$post_id][$key][] = $value;
                WordPressStubContext::setState('meta', $meta);

                return true;
            }
        );
        WordPressStubContext::set(
            'wp_insert_post',
            function ($post_data) {
                $posts = WordPressStubContext::getState('posts', array());
                $next_post_id = WordPressStubContext::getState('next_post_id', 2000);
                $next_post_id++;
                WordPressStubContext::setState('next_post_id', $next_post_id);

                $post_data['ID'] = $next_post_id;
                $posts[$next_post_id] = (object) array_merge(
                    array(
                        'post_author' => 0,
                        'post_content' => '',
                        'post_excerpt' => '',
                        'post_parent' => 0,
                        'post_status' => 'draft',
                        'post_title' => '',
                        'post_type' => 'post',
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'menu_order' => 0,
                    ),
                    $post_data
                );
                WordPressStubContext::setState('posts', $posts);

                return $next_post_id;
            }
        );
        WordPressStubContext::set(
            'get_object_taxonomies',
            function ($post_type) {
                return 'ppcart_product' === $post_type
                    ? array('ppcart_product_cat', 'ppcart_product_tag', 'sc_product_visibility')
                    : array();
            }
        );
        WordPressStubContext::set(
            'wp_get_object_terms',
            function ($post_id, $taxonomy) {
                $terms = WordPressStubContext::getState('terms', array());

                return isset($terms[$post_id][$taxonomy]) ? $terms[$post_id][$taxonomy] : array();
            }
        );
        WordPressStubContext::set(
            'wp_set_object_terms',
            function ($post_id, $terms, $taxonomy) {
                $term_state = WordPressStubContext::getState('terms', array());

                if (! isset($term_state[$post_id])) {
                    $term_state[$post_id] = array();
                }

                $term_state[$post_id][$taxonomy] = $terms;
                WordPressStubContext::setState('terms', $term_state);

                return $terms;
            }
        );

        if (! class_exists(PPCart_Product_Duplicator::class, false)) {
            require_once PPCART_PLUGIN_ROOT . 'includes/class-ppcart-product-duplicator.php';
        }

        $this->duplicateId = null;
        $this->sourceMetaBefore = null;
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_098_product_duplicate_setting_defaults_to_enabled(): void
    {
        $this->assertTrue(PPCart_Product_Duplicator::is_enabled());
    }

    public function test_UT_099_product_duplicate_setting_disables_duplicate_action(): void
    {
        $options = WordPressStubContext::getState('options', array());
        $options[PPCart_Product_Duplicator::OPTION_NAME] = '0';
        WordPressStubContext::setState('options', $options);

        $this->assertFalse(PPCart_Product_Duplicator::is_enabled());
    }

    public function test_UT_100_product_duplicate_setting_can_re_enable_duplicate_action(): void
    {
        $options = WordPressStubContext::getState('options', array());
        $options[PPCart_Product_Duplicator::OPTION_NAME] = '1';
        WordPressStubContext::setState('options', $options);

        $this->assertTrue(PPCart_Product_Duplicator::is_enabled());
    }

    public function test_UT_101_duplicate_returns_a_new_post_id_not_an_error(): void
    {
        $this->seedSourceProduct();
        $duplicate_id = PPCart_Product_Duplicator::duplicate($this->sourceId);

        $this->assertFalse(is_wp_error($duplicate_id));
    }

    public function test_UT_102_duplicate_is_a_distinct_post_from_the_source(): void
    {
        $this->seedSourceProduct();
        $duplicate_id = PPCart_Product_Duplicator::duplicate($this->sourceId);

        $this->assertNotSame($this->sourceId, $duplicate_id);
    }

    public function test_UT_103_duplicate_is_created_as_an_sc_product(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame('ppcart_product', $duplicate_post->post_type);
    }

    public function test_UT_104_duplicate_is_created_as_a_draft(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame('draft', $duplicate_post->post_status);
    }

    public function test_UT_105_duplicate_title_is_prefixed_with_copy_of(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame('Copy of Configured Product', $duplicate_post->post_title);
    }

    public function test_UT_106_product_content_is_copied(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame('Source product content.', $duplicate_post->post_content);
    }

    public function test_UT_107_product_excerpt_is_copied(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame('Source product excerpt.', $duplicate_post->post_excerpt);
    }

    public function test_UT_108_product_parent_relationship_is_copied(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame(88, $duplicate_post->post_parent);
    }

    public function test_UT_109_menu_order_is_copied(): void
    {
        $duplicate_post = $this->duplicateConfiguredProduct();

        $this->assertSame(7, $duplicate_post->menu_order);
    }

    public function test_UT_110_payment_plan_rows_are_copied(): void
    {
        $duplicate_plans = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_pay_options', true);

        $this->assertCount(2, $duplicate_plans);
    }

    public function test_UT_111_one_time_payment_plan_option_id_is_preserved(): void
    {
        $duplicate_plans = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_pay_options', true);

        $this->assertSame('plan_one', $duplicate_plans[0]['option_id']);
    }

    public function test_UT_112_recurring_payment_plan_option_id_is_preserved(): void
    {
        $duplicate_plans = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_pay_options', true);

        $this->assertSame('plan_recurring', $duplicate_plans[1]['option_id']);
    }

    public function test_UT_113_recurring_stripe_price_id_is_cleared_on_duplicate(): void
    {
        $duplicate_plans = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_pay_options', true);

        $this->assertSame('', $duplicate_plans[1]['stripe_plan_id']);
    }

    public function test_UT_114_sale_stripe_price_id_is_cleared_on_duplicate(): void
    {
        $duplicate_plans = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_pay_options', true);

        $this->assertSame('', $duplicate_plans[1]['sale_stripe_plan_id']);
    }

    public function test_UT_115_source_payment_plan_stripe_id_is_not_mutated(): void
    {
        $this->duplicateConfiguredProduct();
        $meta = WordPressStubContext::getState('meta', array());
        $source_plans_after = maybe_unserialize($meta[$this->sourceId]['_ppcart_pay_options'][0]);

        $this->assertSame('price_source_recurring', $source_plans_after[1]['stripe_plan_id']);
    }

    public function test_UT_116_integration_service_is_copied(): void
    {
        $duplicate_integrations = get_post_meta(
            $this->duplicateConfiguredProduct()->ID,
            '_ppcart_integrations',
            true
        );

        $this->assertSame('mailchimp', $duplicate_integrations[0]['service']);
    }

    public function test_UT_117_integration_plan_restriction_still_targets_the_copied_plan_option_id(): void
    {
        $duplicate_integrations = get_post_meta(
            $this->duplicateConfiguredProduct()->ID,
            '_ppcart_integrations',
            true
        );

        $this->assertSame(array('plan_recurring'), $duplicate_integrations[0]['int_plan']);
    }

    public function test_UT_118_default_fields_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_default_fields', $duplicate_meta);
    }

    public function test_UT_119_custom_fields_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_custom_fields', $duplicate_meta);
    }

    public function test_UT_120_payment_method_setting_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_disable_stripe', $duplicate_meta);
    }

    public function test_UT_121_coupon_repeater_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_coupons', $duplicate_meta);
    }

    public function test_UT_122_coupon_code_is_copied(): void
    {
        $duplicate_coupons = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_coupons', true);

        $this->assertSame('SAVE10', $duplicate_coupons[0]['code']);
    }

    public function test_UT_123_coupon_stripe_id_is_cleared_on_duplicate(): void
    {
        $duplicate_coupons = get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_coupons', true);

        $this->assertSame('', $duplicate_coupons[0]['stripe_id']);
    }

    public function test_UT_124_source_coupon_stripe_id_is_not_mutated(): void
    {
        $this->duplicateConfiguredProduct();
        $meta = WordPressStubContext::getState('meta', array());

        $this->assertSame('coupon_source', $meta[$this->sourceId]['_ppcart_coupons'][0][0]['stripe_id']);
    }

    public function test_UT_125_order_bump_repeater_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_order_bump_options', $duplicate_meta);
    }

    public function test_UT_126_notification_repeater_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_notifications', $duplicate_meta);
    }

    public function test_UT_127_confirmation_repeater_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_confirmations', $duplicate_meta);
    }

    public function test_UT_130_shipping_enable_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_enable_shipping', $duplicate_meta);
    }

    public function test_UT_131_shipping_amount_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_shipping_single', $duplicate_meta);
    }

    public function test_UT_132_file_download_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_files', $duplicate_meta);
    }

    public function test_UT_133_quantity_field_meta_is_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayHasKey('_ppcart_qty_field', $duplicate_meta);
    }

    public function test_UT_134_featured_image_meta_is_copied(): void
    {
        $this->assertSame(
            '555',
            get_post_meta($this->duplicateConfiguredProduct()->ID, '_thumbnail_id', true)
        );
    }

    public function test_UT_135_multi_value_meta_is_copied_as_multiple_rows(): void
    {
        $this->assertSame(
            array('first', 'second'),
            get_post_meta($this->duplicateConfiguredProduct()->ID, '_ppcart_multi_value_meta')
        );
    }

    public function test_UT_136_edit_lock_meta_is_not_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayNotHasKey('_edit_lock', $duplicate_meta);
    }

    public function test_UT_137_edit_last_meta_is_not_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayNotHasKey('_edit_last', $duplicate_meta);
    }

    public function test_UT_138_old_slug_meta_is_not_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayNotHasKey('_wp_old_slug', $duplicate_meta);
    }

    public function test_UT_139_stripe_product_identity_is_not_copied(): void
    {
        $duplicate_meta = $this->getDuplicateMeta();

        $this->assertArrayNotHasKey('_ppcart_stripe_prod_id', $duplicate_meta);
        $this->assertArrayNotHasKey('_sc_orphan_leftover', $duplicate_meta);
        $this->assertArrayNotHasKey('_sc_stripe_prod_id', $duplicate_meta);
    }

    public function test_UT_140_product_categories_are_copied(): void
    {
        $this->duplicateConfiguredProduct();
        $terms = WordPressStubContext::getState('terms', array());

        $this->assertSame(array(11, 12), $terms[$this->duplicateId]['ppcart_product_cat']);
    }

    public function test_UT_141_product_tags_are_copied(): void
    {
        $this->duplicateConfiguredProduct();
        $terms = WordPressStubContext::getState('terms', array());

        $this->assertSame(array(21), $terms[$this->duplicateId]['ppcart_product_tag']);
    }

    public function test_UT_142_custom_product_taxonomy_terms_are_copied(): void
    {
        $this->duplicateConfiguredProduct();
        $terms = WordPressStubContext::getState('terms', array());

        $this->assertSame(array(31), $terms[$this->duplicateId]['sc_product_visibility']);
    }

    public function test_UT_143_source_product_meta_is_not_mutated(): void
    {
        $this->duplicateConfiguredProduct();
        $meta = WordPressStubContext::getState('meta', array());

        $this->assertSame($this->sourceMetaBefore, $meta[$this->sourceId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDuplicateMeta(): array
    {
        $this->duplicateConfiguredProduct();

        return WordPressStubContext::getState('meta', array())[$this->duplicateId];
    }

    /**
     * @return object
     */
    private function duplicateConfiguredProduct()
    {
        if (null === $this->duplicateId) {
            $this->seedSourceProduct();
            $this->sourceMetaBefore = WordPressStubContext::getState('meta', array())[$this->sourceId];
            $this->duplicateId = PPCart_Product_Duplicator::duplicate($this->sourceId);
        }

        $posts = WordPressStubContext::getState('posts', array());

        return $posts[$this->duplicateId];
    }

    /**
     * @return void
     */
    private function seedSourceProduct(): void
    {
        $source_payment_plans = array(
            array(
                'option_id' => 'plan_one',
                'option_name' => 'One-time plan',
                'product_type' => '',
                'price' => '100.00',
                'stripe_plan_id' => 'price_source_one',
                'sale_stripe_plan_id' => 'price_source_one_sale',
                'hidden' => '1',
            ),
            array(
                'option_id' => 'plan_recurring',
                'option_name' => 'Recurring plan',
                'product_type' => 'recurring',
                'price' => '25.00',
                'interval' => 'month',
                'frequency' => '1',
                'installments' => '12',
                'stripe_plan_id' => 'price_source_recurring',
                'sale_price' => '10.00',
                'sale_stripe_plan_id' => 'price_source_recurring_sale',
            ),
        );

        $source_integrations = array(
            array(
                'service' => 'mailchimp',
                'trigger' => 'purchase',
                'int_plan' => array('plan_recurring'),
                'webhook_url' => 'https://example.test/webhook',
                'conditions' => array(
                    'field' => array('_sc_email'),
                    'rule' => array('not_empty'),
                ),
            ),
        );

        WordPressStubContext::setState(
            'posts',
            array(
                $this->sourceId => (object) array(
                    'ID' => $this->sourceId,
                    'post_author' => 123,
                    'post_content' => 'Source product content.',
                    'post_excerpt' => 'Source product excerpt.',
                    'post_parent' => 88,
                    'post_status' => 'publish',
                    'post_title' => 'Configured Product',
                    'post_type' => 'ppcart_product',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'menu_order' => 7,
                ),
            )
        );

        WordPressStubContext::setState(
            'meta',
            array(
                $this->sourceId => array(
                    '_ppcart_pay_options' => array(serialize($source_payment_plans)),
                    '_ppcart_integrations' => array($source_integrations),
                    '_ppcart_default_fields' => array(
                        array(
                            array(
                                'id' => 'email',
                                'label' => 'Email',
                            ),
                        ),
                    ),
                    '_ppcart_custom_fields' => array(
                        array(
                            array(
                                'id' => 'vat',
                                'type' => 'text',
                            ),
                        ),
                    ),
                    '_ppcart_disable_stripe' => array('1'),
                    '_ppcart_coupons' => array(
                        array(
                            array(
                                'code' => 'SAVE10',
                                'type' => 'percent',
                                'amount' => '10',
                                'stripe_id' => 'coupon_source',
                            ),
                        ),
                    ),
                    '_ppcart_order_bump_options' => array(
                        array(
                            array(
                                'bump_name' => 'Extra course',
                                'price' => '19.00',
                            ),
                        ),
                    ),
                    '_ppcart_notifications' => array(
                        array(
                            array(
                                'notification_name' => 'Team notification',
                                'notification_email' => 'team@example.test',
                            ),
                        ),
                    ),
                    '_ppcart_confirmations' => array(
                        array(
                            array(
                                'name' => 'Recurring thank you',
                                'int_plan' => array('plan_recurring'),
                            ),
                        ),
                    ),
                    '_ppcart_enable_shipping' => array('1'),
                    '_ppcart_shipping_single' => array('9.99'),
                    '_ppcart_files' => array(
                        array(
                            array(
                                'file_id' => 'file-1',
                                'file_url' => 'https://example.test/file.zip',
                            ),
                        ),
                    ),
                    '_ppcart_qty_field' => array('1'),
                    '_thumbnail_id' => array('555'),
                    '_ppcart_stripe_prod_id' => array('prod_source'),
                    '_sc_orphan_leftover' => array('must-not-copy-without-compat-rewrite'),
                    '_edit_lock' => array('123:1'),
                    '_edit_last' => array('5'),
                    '_wp_old_slug' => array('configured-product'),
                    '_ppcart_multi_value_meta' => array('first', 'second'),
                ),
            )
        );

        WordPressStubContext::setState(
            'terms',
            array(
                $this->sourceId => array(
                    'ppcart_product_cat' => array(11, 12),
                    'ppcart_product_tag' => array(21),
                    'sc_product_visibility' => array(31),
                ),
            )
        );
    }
}

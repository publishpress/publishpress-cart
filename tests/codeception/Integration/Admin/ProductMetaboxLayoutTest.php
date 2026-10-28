<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metaboxes;
use ReflectionMethod;

class ProductMetaboxLayoutTest extends WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    private function invoke(string $method, ...$args)
    {
        $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
        $reflection = new ReflectionMethod(PPCart_Product_Metaboxes::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($metaboxes, ...$args);
    }

    private function renderFields(array $fields): string
    {
        ob_start();
        $this->invoke('metabox_fields', $fields);

        return (string) ob_get_clean();
    }

    private function repeater(array $extra = []): array
    {
        return array_merge(
            [
                'class'        => 'ppcart-repeater',
                'id'           => '_ppcart_notifications',
                'label-add'    => 'Add',
                'label-edit'   => 'Edit',
                'label-header' => 'Notification',
                'label-remove' => 'Remove',
                'title-field'  => 'notification_name',
                'type'         => 'repeater',
                'value'        => '',
                'class_size'   => '',
                'fields'       => [
                    [ 'text' => [ 'class' => 'widefat required', 'id' => 'notification_name', 'label' => 'Name', 'type' => 'text', 'value' => '' ] ],
                ],
            ],
            $extra
        );
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_checkbox_help_is_visible_and_its_text_is_a_real_label(): void
    {
        $html = $this->renderFields([
            [
                'type'        => 'checkbox',
                'id'          => '_ppcart_on_sale',
                'label'       => 'On sale',
                'description' => 'Sell at the sale price now.',
                'value'       => '',
                'class'       => '',
                'class_size'  => '',
            ],
        ]);

        $this->assertStringContainsString('<label class="ppcart-label" for="_ppcart_on_sale">On sale</label>', $html);
        $this->assertStringContainsString('aria-describedby="_ppcart_on_sale-help"', $html);
        $this->assertMatchesRegularExpression('/<p class="description ppcart-field-help" id="_ppcart_on_sale-help">Sell at the sale price now\.<\/p>/', $html);
        $this->assertStringNotContainsString('ppcart-tooltip', $html);
        $this->assertStringNotContainsString('aria-role', $html);
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_text_field_help_is_linked_to_the_input(): void
    {
        $html = $this->renderFields([
            [
                'type'        => 'text',
                'id'          => '_ppcart_button_text',
                'label'       => 'Submit Button Text',
                'description' => 'Text on the order button.',
                'value'       => '',
                'class'       => '',
                'class_size'  => '',
            ],
        ]);

        $this->assertStringContainsString('aria-describedby="_ppcart_button_text-help"', $html);
        $this->assertStringContainsString('id="_ppcart_button_text-help">Text on the order button.</p>', $html);
        $this->assertStringNotContainsString('ppcart-tooltip', $html);
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_empty_repeater_shows_an_empty_state_instead_of_a_starter_row(): void
    {
        $html = $this->renderFields([
            $this->repeater([ 'empty_state' => [ 'title' => 'No notifications yet', 'text' => 'Add one.' ] ]),
        ]);

        $this->assertStringContainsString('class="ppcart-repeater-empty"', $html);
        $this->assertStringContainsString('No notifications yet', $html);
        $this->assertSame(1, substr_count($html, '<li class="ppcart-repeater '));
        $this->assertStringContainsString('<li class="ppcart-repeater hidden"', $html);
        $this->assertStringNotContainsString('is-starter', $html);
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_empty_repeater_without_empty_state_keeps_its_starter_row(): void
    {
        $html = $this->renderFields([ $this->repeater() ]);

        $this->assertStringNotContainsString('ppcart-repeater-empty', $html);
        $this->assertStringContainsString('<li class="ppcart-repeater is-starter"', $html);
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_repeater_sections_put_advanced_fields_in_a_closed_details_block(): void
    {
        $html = $this->renderFields([
            $this->repeater([
                'sections' => [
                    'main'     => [ 'fields' => [ 'notification_name' ] ],
                    'advanced' => [ 'title' => 'Advanced', 'type' => 'details', 'fields' => [ 'bcc' ] ],
                ],
                'fields'   => [
                    [ 'text' => [ 'class' => 'widefat', 'id' => 'bcc', 'label' => 'Bcc', 'type' => 'text', 'value' => '' ] ],
                    [ 'text' => [ 'class' => 'widefat', 'id' => 'notification_name', 'label' => 'Name', 'type' => 'text', 'value' => '' ] ],
                ],
            ]),
        ]);

        $this->assertMatchesRegularExpression(
            '/<details class="ppcart-repeater-section ppcart-repeater-section--advanced"><summary class="ppcart-repeater-section__title">Advanced<\/summary>.*?ridbcc/s',
            $html
        );
        $this->assertLessThan(strpos($html, 'ridbcc'), strpos($html, 'ridnotification_name'));
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_section_with_one_field_gets_no_card_or_title(): void
    {
        $field = static function (string $id, string $type): array {
            return [ 'type' => $type, 'id' => $id, 'label' => $id, 'value' => '', 'class' => '', 'class_size' => '', 'description' => '' ];
        };

        ob_start();
        $this->invoke('render_product_tab_sections', 'general', [
            $field('_ppcart_product_name', 'text'),
            $field('_ppcart_hide_title', 'checkbox'),
            $field('_ppcart_header_color', 'text'),
        ]);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('ppcart-section--plain ppcart-section--details', $html);
        $this->assertStringNotContainsString('>Product details</h4>', $html);
        $this->assertStringContainsString('>Page header</h4>', $html);
        $this->assertStringContainsString('class="ppcart-field ppcart-row ppcart-field--checkbox', $html);
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_confirmation_fields_share_one_card_without_a_title(): void
    {
        $field = static function (string $id, string $type): array {
            return [ 'type' => $type, 'id' => $id, 'label' => $id, 'value' => '', 'class' => '', 'class_size' => '', 'description' => '' ];
        };

        ob_start();
        $this->invoke('render_product_tab_sections', 'confirmation', [
            $field('_ppcart_confirmation', 'text'),
            $field('_ppcart_confirmation_message', 'text'),
        ]);
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('<section class="ppcart-section ppcart-section--confirmation"><div class="ppcart-section__body">', $html);
        $this->assertStringNotContainsString('ppcart-section__header', $html);
        $this->assertLessThan(strpos($html, 'rid_ppcart_confirmation_message'), strpos($html, 'id="rid_ppcart_confirmation"'));
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_tab_without_a_group_joins_the_group_of_the_tab_before_it(): void
    {
        $groups = $this->invoke('get_product_setting_tab_groups', [
            'general'       => 'General',
            'pricing'       => 'Pricing',
            'addon_tab'     => 'Add-on',
            'notifications' => 'Emails',
            'coupons'       => 'Coupons',
        ]);

        $this->assertSame([ 'sell', 'after', 'grow' ], array_keys($groups));
        $this->assertSame([ 'general', 'pricing', 'addon_tab' ], array_keys($groups['sell']['tabs']));
        $this->assertSame([ 'notifications' ], array_keys($groups['after']['tabs']));
        $this->assertSame([ 'coupons' ], array_keys($groups['grow']['tabs']));
    }

    /**
     * @test-id IT-394
     */
    public function test_IT_394_tab_groups_filter_can_move_a_tab(): void
    {
        $move = static function ($groups) {
            $groups['grow']['tabs'][] = 'addon_tab';

            return $groups;
        };
        add_filter('ppcart_product_setting_tab_groups', $move);

        try {
            $groups = $this->invoke('get_product_setting_tab_groups', [
                'general'   => 'General',
                'addon_tab' => 'Add-on',
            ]);
        } finally {
            remove_filter('ppcart_product_setting_tab_groups', $move);
        }

        $this->assertSame([ 'sell', 'grow' ], array_keys($groups));
        $this->assertSame([ 'addon_tab' ], array_keys($groups['grow']['tabs']));
    }
}

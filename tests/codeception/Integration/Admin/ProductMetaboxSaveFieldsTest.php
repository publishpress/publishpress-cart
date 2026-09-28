<?php

declare(strict_types=1);

namespace Tests\Integration\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use PPCart_Product_Metaboxes;
use ReflectionMethod;

class ProductMetaboxSaveFieldsTest extends WPTestCase
{
    /**
     * @test-id IT-378
     */
    public function test_IT_378_classless_extension_repeater_field_is_included_in_save_schema(): void
    {
        add_filter('ppcart_product_setting_tab_pricing_fields', [$this, 'add_classless_repeater_field']);

        try {
            $metaboxes = new PPCart_Product_Metaboxes('ppcart', '1.0', 'ppcart_');
            $method = new ReflectionMethod(PPCart_Product_Metaboxes::class, 'get_metabox_fields');
            $method->setAccessible(true);
            $fields = $method->invoke($metaboxes);
        } finally {
            remove_filter('ppcart_product_setting_tab_pricing_fields', [$this, 'add_classless_repeater_field']);
        }

        $extensionRepeater = null;
        foreach ($fields as $field) {
            if ('_ppcart_extension_options' === $field[0]) {
                $extensionRepeater = $field;
                break;
            }
        }

        $this->assertSame(
            ['_ppcart_extension_options', 'repeater', [['extension_optional', 'select']]],
            $extensionRepeater
        );
    }

    /**
     * @param array $fields Product pricing fields.
     * @return array
     */
    public function add_classless_repeater_field($fields): array
    {
        $fields[] = [
            'id' => '_ppcart_extension_options',
            'type' => 'repeater',
            'fields' => [
                [
                    'select' => [
                        'id' => 'extension_optional',
                        'type' => 'select',
                    ],
                ],
            ],
        ];

        return $fields;
    }
}

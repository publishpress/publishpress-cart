<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Embed tab of the product edit screen. Its fields hold the embedded checkout
 * appearance (`_ppcart_embed_*`, read by ppcart_embed_appearance()) and are saved by
 * the product metabox like any other tab.
 */
class PPCart_Admin_Embed_Tab
{
    const TAB_ID = 'embed';

    /**
     * @param array<string, string> $tabs Product settings tabs.
     * @return array<string, string>
     */
    public function register_tab($tabs)
    {
        $tabs                 = (array) $tabs;
        $tabs[ self::TAB_ID ] = __('Embed', 'publishpress-cart');

        return $tabs;
    }

    /**
     * Without the group the fields render but never save.
     *
     * @param array<int, string> $groups Product field groups.
     * @return array<int, string>
     */
    public function register_group($groups)
    {
        $groups   = (array) $groups;
        $groups[] = self::TAB_ID;

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $fields Embed tab fields.
     * @return array<int, array<string, mixed>>
     */
    public function fields($fields)
    {
        $fields = (array) $fields;

        foreach ($this->field_definitions() as $key => $field) {
            $fields[] = array_merge(
                [
                    'id'          => self::field_id($key),
                    'class'       => 'widefat',
                    'class_size'  => '',
                    'placeholder' => '',
                    'type'        => 'text',
                    'value'       => '',
                ],
                $field
            );
        }

        return $fields;
    }

    /**
     * Meta key and DOM id of the field for an appearance key.
     *
     * @param string $key Short key from PPCart_Embed_Appearance::KEYS.
     * @return string
     */
    public static function field_id($key)
    {
        return ppcart_meta_key('embed_' . $key);
    }

    /**
     * One field per appearance key except the layout, which Free does not offer
     * because it has no 2-step template.
     *
     * @return array<string, array<string, mixed>>
     */
    private function field_definitions()
    {
        return [
            'accent'      => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Accent color', 'publishpress-cart'),
                'description' => __('Color of the buttons and the selected options. Empty keeps the Primary Color of the product.', 'publishpress-cart'),
            ],
            'text'        => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Text color', 'publishpress-cart'),
                'description' => __('Color of the labels and the form text. Empty keeps the default color.', 'publishpress-cart'),
            ],
            'background'  => [
                'class'       => 'ppcart-color-field',
                'label'       => __('Background color', 'publishpress-cart'),
                'description' => __('Color behind the form. Empty keeps the background transparent, so the page around the form shows through.', 'publishpress-cart'),
            ],
            'font_family' => [
                'label'       => __('Font family', 'publishpress-cart'),
                'description' => __('A font name or a list of fonts, such as Inter, sans-serif. Empty keeps the default font.', 'publishpress-cart'),
                'placeholder' => 'Inter, sans-serif',
                'datalist'    => [
                    'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
                    '"Helvetica Neue", Helvetica, Arial, sans-serif',
                    'Georgia, "Times New Roman", serif',
                    'Inter, sans-serif',
                    '"Courier New", Courier, monospace',
                ],
            ],
            'font_url'    => [
                'type'        => 'url',
                'label'       => __('Web font URL', 'publishpress-cart'),
                'description' => __('An https stylesheet that loads the font, such as a Google Fonts link. Write the name of the font in Font family too.', 'publishpress-cart'),
                'placeholder' => 'https://fonts.googleapis.com/css2?family=Inter&display=swap',
            ],
            'radius'      => [
                'type'        => 'number',
                'label'       => __('Corner radius', 'publishpress-cart'),
                'description' => __('Roundness of the buttons, fields and boxes, from 0 to 24 pixels. Empty keeps the default corners.', 'publishpress-cart'),
                'min'         => 0,
                'max'         => PPCart_Embed_Appearance::RADIUS_MAX,
                'step'        => 1,
            ],
            'button_text' => [
                'label'       => __('Popup button text', 'publishpress-cart'),
                'description' => __('Label of the button in the popup button code. Empty uses the Submit Button Text of the product, or "Buy now".', 'publishpress-cart'),
            ],
        ];
    }
}

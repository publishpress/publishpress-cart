<?php

defined('ABSPATH') || exit;

class PPCart_Elementor_Widget extends \Elementor\Widget_Base
{
    private $slug;

    public function __construct($data = [], $args = null, $slug = '')
    {
        $this->slug = $slug ?: substr($data['widgetType'] ?? '', strlen('ppcart-'));
        parent::__construct($data, $args);
    }

    public function get_name(): string
    {
        return 'ppcart-' . $this->slug;
    }

    public function get_title(): string
    {
        return PPCart_Layout_Embeds::definitions()[$this->slug] ?? '';
    }

    public function get_icon(): string
    {
        return 'eicon-cart';
    }

    protected function is_dynamic_content(): bool
    {
        return true;
    }

    protected function register_controls(): void
    {
        if (! in_array($this->slug, ['checkout-form', 'account-tab'], true)) {
            return;
        }

        $this->start_controls_section('cart_settings', [
            'label' => __('Cart settings', 'publishpress-cart'),
        ]);

        if ('checkout-form' === $this->slug) {
            $this->add_control('id', [
                'label' => __('Product ID', 'publishpress-cart'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'description' => __('Leave empty to use the current product.', 'publishpress-cart'),
            ]);
            $this->add_control('plan', [
                'label' => __('Payment plan ID', 'publishpress-cart'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]);
            $this->add_control('coupon', [
                'label' => __('Coupon code', 'publishpress-cart'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]);
        } else {
            $this->add_control('component', [
                'label' => __('Account content', 'publishpress-cart'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => PPCart_Layout_Embeds::component_options(),
                'default' => 'account-orders',
            ]);
        }

        $this->end_controls_section();
    }

    protected function render(): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The registered Cart block renders its own HTML.
        echo PPCart_Layout_Embeds::render($this->slug, $this->get_settings_for_display());
    }
}

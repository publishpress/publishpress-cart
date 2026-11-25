<?php

if (! defined('ABSPATH')) {
    exit;
}

final readonly class PPCart_Shopping_Cart_Priced_Line
{
    public function __construct(
        public string $key,
        public int $product_id,
        public string $plan_id,
        public int $quantity,
        public int $max_quantity,
        public float $unit_price,
        public float $subtotal,
        public string $product_name,
        public string $plan_name,
        public int $image_id,
        public string $permalink,
        public string $added_at,
    ) {
    }
}

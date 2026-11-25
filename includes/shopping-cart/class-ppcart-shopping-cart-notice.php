<?php

if (! defined('ABSPATH')) {
    exit;
}

final readonly class PPCart_Shopping_Cart_Notice
{
    public function __construct(
        public string $code,
        public string $key,
        public string $message,
        public ?string $old_price,
        public ?string $new_price,
    ) {
    }
}

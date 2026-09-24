<?php

if (! defined('ABSPATH')) {
    exit;
}


$stripe = $this->stripe;
if (!$this->stripe) {
    return false;
} elseif ($pid = ppcart_get_post_meta($post_id, 'stripe_prod_id', true)) {
    try {
        $product = $stripe->products->retrieve($pid);

        $canonical_product_id = ppcart_stripe_metadata_raw($product, 'ppcart_product_id');
        $resolved_product_id  = ppcart_stripe_metadata($product, 'ppcart_product_id');

        if (! ppcart_stripe_metadata_is_empty($resolved_product_id) && (string) $resolved_product_id !== (string) $post_id) {
            return $this->create_stripe_product($post_id);
        }

        if (ppcart_stripe_metadata_is_empty($canonical_product_id)) {
            $stripe->products->update(
                $product->id,
                [
                    'metadata' => ['ppcart_product_id' => $post_id, 'origin' => get_site_url()],
                ]
            );
        }

        if ($product->name != ppcart_get_public_product_name($post_id)) {
            $stripe->products->update(
                $product->id,
                [
                    'name' => ppcart_get_public_product_name($post_id),
                    'description' => ppcart_get_public_product_name($post_id),
                ]
            );
        }
        return $product;
    } catch (Exception $e) {
        return $this->create_stripe_product($post_id);
    }
}
return $this->create_stripe_product($post_id);

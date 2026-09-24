<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Amounts
{
    public function calculate_final_amounts()
    {

        $this->order_summary_items = [];

        foreach ($this->items as $item) {
            if ($item['item_type'] != 'bundled') {
                $this->order_summary_items[] = [
                    'name'          => apply_filters('ppcart_order_summary_item_name', $item['product_name'], $item),
                    'total_amount'  => (float) $item['subtotal'],
                    'subtotal'      => (float) $item['subtotal'],
                    'type'          => $item['item_type'],
                    'quantity'      => $item['quantity'],
                ];
            }
        }

        if ($this->coupon) {
            if (!$this->coupon['discount_amount']) {
                $this->coupon = null;
                $this->coupon_id = null;
            } else {
                $this->order_summary_items[] = [
                    /* translators: %s: coupon code. */
                    'name'          => apply_filters('ppcart_order_summary_coupon_text', sprintf(__('Coupon %s', 'publishpress-cart'), $this->coupon_id), $this->coupon),
                    'coupon_id'     => $this->coupon_id,
                    'total_amount'  => $this->coupon['discount_amount'],
                    'subtotal'      => $this->coupon['discount_amount'],
                    'type'          => 'discount',
                ];
            }
        }


        if ($this->tax_desc) {
            $title = $this->tax_desc . ' - ' . $this->tax_data->rate . '%';
            if ($this->tax_data->type == 'inclusive') {
                if (!isset($this->tax_data->redeem_vat) || !$this->tax_data->redeem_vat) {
                    $title .= ' (' . __('Included in price', "publishpress-cart") . ')';
                } elseif (isset($this->tax_data->redeem_vat)) {
                    unset($this->tax_data->redeem_vat);
                }
            }

            $this->order_summary_items[] = [
                'name'          => $title,
                'total_amount'  => $this->tax_amount,
                'subtotal'      => $this->tax_amount,
                'type'          => 'tax',
            ];
        }
    }

    public function calculate_pre_tax_amount_from_items()
    {
        $amount = 0;
        $count = 0;
        foreach ($this->items as $item) {
            $amount += (float) $item['subtotal'];
            $count++;
        }
        $this->pre_tax_amount = $amount;
        return $count;
    }

    public function calculate_total_amount_from_items()
    {
        $amount = 0;
        $count = 0;
        foreach ($this->items as $item) {
            $amount += (float) $item['total_amount'];
            $count++;
        }
        $this->amount = $amount;
        return $count;
    }

    public function calculate_tax_amount_from_items()
    {
        $amount = 0;
        $count = 0;
        foreach ($this->items as $item) {
            if (!isset($item['tax_amount']) || !$item['tax_amount']) {
                continue;
            }
            $amount +=  (float) $item['tax_amount'];
            $count++;
        }
        $this->tax_amount = $amount;
        return $count;
    }

    public function setup_tax($ppcart_product)
    {
        do_action('ppcart_order_setup_tax', $this, $ppcart_product);
    }

    public function calculate_tax($_amount, $product_id)
    {
        return apply_filters('ppcart_order_calculate_tax', ['tax' => 0, 'total' => $_amount], $_amount, $product_id, $this);
    }

    public function maybe_apply_tax_to_item($item)
    {
        return apply_filters('ppcart_order_apply_tax_to_item', $item, $this);
    }

    public function divide_money_evenly($dollar_amount, $num_parts, $dollar_amounts = [])
    {
        $total = 0;

        if ($num_parts == 1) {
            return [$dollar_amount];
        }

        for ($i = 0; $i < $num_parts; $i++) {
            if (abs($dollar_amount - $total) != 0) {
                $divided = $dollar_amount / $num_parts;

                if ($divided < 1) {
                    $rounded = 0;
                } else {
                    $rounded = floor($divided);
                }

                if ($rounded == 0) {
                    $total += 0.01;

                    if (isset($dollar_amounts[$i])) {
                        $dollar_amounts[$i] = $dollar_amounts[$i] + 0.01;
                    } else {
                        $dollar_amounts[$i] = 0.01;
                    }
                } else {
                    $total += $rounded;

                    if (isset($dollar_amounts[$i])) {
                        $dollar_amounts[$i] = $dollar_amounts[$i] + $rounded;
                    } else {
                        $dollar_amounts[$i] = $rounded;
                    }
                }
            }
        }

        $difference = $dollar_amount - $total;

        if ($difference > 0) {
            $dollar_amounts = $this->divide_money_evenly((float)(string) $difference, $num_parts, $dollar_amounts);
        }

        foreach ($dollar_amounts as &$dollar_amount) {
            $dollar_amount = strval($dollar_amount);
        }

        return $dollar_amounts;
    }
}

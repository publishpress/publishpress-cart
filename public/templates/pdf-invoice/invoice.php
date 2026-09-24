<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wpdb, $ppcart_currency_symbol;

$template_order = $order;
$ppcart_post_id = $template_order->id;
$sub = $template_order->get_subscription();

if ($pid = ppcart_get_post_meta($ppcart_post_id, 'us_parent', true)) {
    $ppcart_post_id = $pid;
} elseif ($pid = ppcart_get_post_meta($ppcart_post_id, 'us_parent', true)) {
    $ppcart_post_id = $pid;
}
$image_file = get_option('_ppcart_company_logo');

$ppcart_order_model = new PPCart_Order($ppcart_post_id);
$ppcart_order_model = apply_filters('ppcart_order', $ppcart_order_model);
$sub = $ppcart_order_model->get_subscription();

$invoice_number = $ppcart_post_id;
$prefix = '';
if (get_option('_ppcart_enable_invoice_number') && $ppcart_order_model->invoice_number) {
    $invoice_number = $ppcart_order_model->invoice_number;
}
$ppcart_order = (object) $ppcart_order_model->get_data();
$total = 0;
$font = 'Arial, Helvetica, sans-serif;';

if (isset($ppcart_order->main_offer)) { // backwards compatibility
    if (isset($ppcart_order->main_offer["plan"]->initial_payment)) {
        $ppcart_order->main_offer_amt = $ppcart_order->main_offer["plan"]->initial_payment;
    }
}

$company_name = get_option('_ppcart_company_name');
$company_address = get_option('_ppcart_company_address');
$notes = get_option('_ppcart_invoice_notes');

$upload_dir = wp_upload_dir();
$basedir = $upload_dir['basedir'];
$baseurl = $upload_dir['baseurl'];
if ($image_file) {
    $image_file = str_replace($baseurl, $basedir, $image_file);
    $upload_basedir = realpath($basedir);
    $resolved_image_file = realpath($image_file);

    if (
        false === $upload_basedir ||
        false === $resolved_image_file ||
        0 !== strpos(wp_normalize_path($resolved_image_file), trailingslashit(wp_normalize_path($upload_basedir))) ||
        ! is_file($resolved_image_file) ||
        ! is_readable($resolved_image_file)
    ) {
        $image_file = '';
    } else {
        // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reads a confined local uploads file path, not a remote URL.
        $image_file = base64_encode(file_get_contents($resolved_image_file));
        $image_file = '<img src="data:image/png;base64,' . $image_file . '" alt="' . esc_attr($company_name) . '" style="max-width:250px;margin-bottom:40px">';
    }
} else {
    $image_file = '';
}

$is_paid = in_array($ppcart_order->status, ['paid', 'completed']);
$is_refunded = ($ppcart_order->status == 'refunded');
$request_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
// Kept unescaped here on purpose: these labels are escaped where the document is assembled below.
$document_type = ('receipt' === $request_type) ? __('Receipt', 'publishpress-cart') : __('Invoice', 'publishpress-cart');
$document_number_label = ('receipt' === $request_type) ? __('Receipt Number', 'publishpress-cart') : __('Invoice Number', 'publishpress-cart');
$amount_text = ('receipt' === $request_type) ? __('Amount Paid', 'publishpress-cart') : __('Amount Due', 'publishpress-cart');

// Get the product ID from the order object
$product_id = $ppcart_order->product_id;

// Get custom field definitions from the product's post meta
$product_custom_fields = $product_id ? ppcart_get_post_meta($product_id, 'custom_fields', true) : false;

// Get the custom field values submitted with the order
$order_custom_fields = $ppcart_order->custom_fields;

$html = '<table style="width: 100%;font-size: 13px;line-height: 18px;color:#3e3e48;" autosize="1">';

$html .= '<tr class="top">
        <td class="title" style="width: 30%; font-family:' . $font . ';">' . $image_file . '<br>';

$html .= '<span style="color:#818d90; font-weight:bold;font-family:' . $font . ';">' . esc_html__('Bill To', 'publishpress-cart') . ':</span><br>
                    ' . esc_html($ppcart_order->customer_name) . '<br>';
if (isset($ppcart_order->company)) {
    $html .= esc_html($ppcart_order->company) . '<br>';
}
if (isset($ppcart_order->email)) {
    $html .= esc_html($ppcart_order->email) . '<br>';
}
if (isset($ppcart_order->phone)) {
    $html .= esc_html($ppcart_order->phone) . '<br>';
}
$address = ppcart_format_order_address($ppcart_order);
if ($address) {
    // ppcart_format_order_address() escapes each field; only its line breaks are allowed through here.
    $html .= '<br>' . wp_kses($address, ['br' => []]);
}

if (!empty($ppcart_order->vat_number)) {
    $html .= '<br>' . esc_html(apply_filters('ppcart_vat_title', __('VAT Number', 'publishpress-cart'))) . ' ' . esc_html($ppcart_order->vat_number);
}

$html .= '</td>
            <td valign="top" style="width: 70%;text-align: right;clear:both;">
                <p style="font-size: 46px;color: rgb(165,179,183);line-height: 2em;font-family:' . $font . ';">' . esc_html($document_type) . '</p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:' . $font . ';">' . esc_html($document_number_label) . '</span>
                    <br/>
                    <span style="font-family:' . $font . ';">' .  esc_html($invoice_number) . '</span>
                </p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:' . $font . ';">' . esc_html__('Issue Date', 'publishpress-cart') . '</span>
                    <br/>
                    <span style="font-family:' . $font . ';">' . esc_html($ppcart_order->date) . '</span>
                </p>
                <p style="font-family:' . $font . ';">
                    <span style="color:#818d90; font-weight:bold;text-transform: capitalize;">' . esc_html($company_name) . '</span><br>
                    <span style="font-family:' . $font . ';">' . nl2br(wp_kses_post($company_address)) . '</span>
                </p>
            </td>
        </tr>
        <tr class="information">
            <td colspan="2">
                <table style="margin-top:20px;" width="100%" cellspacing="0" cellpadding="13">
                    <tr class="heading">
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:' . $font . ';" width="50%">' . esc_html__('Description', 'publishpress-cart') . '</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:' . $font . ';">' . esc_html__('Qty', 'publishpress-cart') . '</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:' . $font . ';">' . esc_html__('Price', 'publishpress-cart') . '</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:' . $font . ';" align="right">' . esc_html__('Amount', 'publishpress-cart') . '</td>
                    </tr>';
$bg = ['','background: #f1f6f7;'];
$i = 0;

$items = ppcart_get_item_list($ppcart_order->id, $full = true, $qty_col = true);
foreach ($items['items'] as $item) {
    $item['item_type'] ??= '';
    $item['subtotal'] ??= '';
    $item['unit_price'] ??= '';

    $html .= '<tr>
                                <td style="font-family:' . $font . ';' . $bg[$i % 2] . 'border-bottom:0px solid #a5b3b7">' . esc_html($item['product_name']);

    if ($item['price_name']) {
        $html .= ' - ' . esc_html($item['price_name']);
    }

    if (isset($ppcart_order->coupon) && !isset($ppcart_order->coupon_id)) {
        $coupon = is_scalar($ppcart_order->coupon) ? $ppcart_order->coupon : '';
        $html .= ' (coupon ' . esc_html($coupon) . ')';
    }

    if (isset($item['subscription_id'])) {
        $invoice_year = ppcart_maybe_format_date($ppcart_order->date, 'Y');
        $start = ppcart_maybe_format_date($ppcart_order->date);

        if ($invoice_year == ppcart_maybe_format_date($sub->sub_next_bill_date, 'Y')) {
            switch (get_option('date_format')) {
                case 'F j, Y':
                    $start = ppcart_maybe_format_date($ppcart_order->date, 'F j');
                    break;
                case 'Y-m-d':
                    $start = ppcart_maybe_format_date($ppcart_order->date, 'Y-m');
                    break;
                case 'm/d/Y':
                case 'd/m/Y':
                    $start = str_replace('/' . $invoice_year, '', $ppcart_order->date);
                    break;
                default:
                    $start = str_replace($invoice_year, '', $ppcart_order->date);
                    break;
            }
        }
        $html .= '<br>' . esc_html($start) . ' - ' . esc_html(ppcart_maybe_format_date($sub->sub_next_bill_date));
        if ($item['sign_up_fee'] && floatval($item['sign_up_fee']) > 0) {
            $item['subtotal'] -= $item['sign_up_fee'];
            $item['unit_price'] -= ($item['sign_up_fee'] / $sub->quantity);
        }
    }

    $html .= '</td>
                                <td style="font-family:' . $font . ';' . $bg[$i % 2] . 'border-bottom:0px solid #a5b3b7">' . esc_html($item['quantity']) . '</td>
                                <td style="font-family:' . $font . ';' . $bg[$i % 2] . 'border-bottom:0px solid #a5b3b7">' . wp_kses_post(ppcart_format_price($item['unit_price'])) . '</td>
                                <td style="font-family:' . $font . ';' . $bg[$i % 2] . 'border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">' . wp_kses_post(ppcart_format_price($item['subtotal'])) . '</td>
                            </tr>';
    $i++;

    if (isset($item['subscription_id']) && $item['sign_up_fee'] && $item['sign_up_fee'] > 0) {
        $html .= '<tr>
                            <td style="font-family:' . $font . ';' . $bg[$i % 2] . ';border-bottom:0px solid #a5b3b7">' . esc_html__('Sign-up Fee', 'publishpress-cart') . '</td>
                            <td style="font-family:' . $font . ';' . $bg[$i % 2] . ';border-bottom:0px solid #a5b3b7">' . esc_html($sub->quantity) . '</td>
                            <td style="font-family:' . $font . ';' . $bg[$i % 2] . ';border-bottom:0px solid #a5b3b7">' . wp_kses_post(ppcart_format_price($item['sign_up_fee'] / $sub->quantity)) . '</td>
                            <td style="font-family:' . $font . ';' . $bg[$i % 2] . ';border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">' . wp_kses_post(ppcart_format_price($item['sign_up_fee'])) . '</td></tr>';
        $i++;
    }
}

$html .= '
                    <tr>
                        <td style="font-family:' . $font . ';border-top:0.75px solid #6f7b7e" colspan="2"></td>
                        <td style="font-family:' . $font . ';border-top:0.75px solid #6f7b7e; font-weight: bold;">' . esc_html__('Subtotal', 'publishpress-cart') . '</td>
                        <td style="font-family:' . $font . ';border-top:0.75px solid #6f7b7e; font-weight: bold;" align="right">' . wp_kses_post(ppcart_format_price($items['subtotal']['total_amount'])) . '</td>
                    </tr>';
if (isset($items['discounts'])) {
    foreach ($items['discounts'] as $item) {
        $html .= '<tr>
                                <td colspan="2"></td>
                                <td style="font-family:' . $font . ';background:#f1f6f7;">'
                . esc_html__('Coupon:', 'publishpress-cart') . ' ' . wp_kses_post($item['product_name']) . '
                                </td>
                                <td style="font-family:' . $font . ';background:#f1f6f7;" align="right">
                                    -' . wp_kses_post(ppcart_format_price($item['total_amount'])) . '
                                </td>
                            </tr>';
    }
}
if (isset($items['shipping'])) {
    $html .= '<tr>
                            <td colspan="2"></td>
                            <td style="font-family:' . $font . ';background:#f1f6f7;">'
            . esc_html($items['shipping']['product_name']) . '
                            </td>
                            <td style="font-family:' . $font . ';background:#f1f6f7;" align="right">
                                ' . wp_kses_post(ppcart_format_price($items['shipping']['total_amount'])) . '
                            </td>
                        </tr>';
}
if (isset($items['tax']) && $items['tax']['product_name']) {
    $colspan = ($items['tax']['total_amount']) ? '' : 'colspan="2"';
    $html .= '<tr>
                            <td colspan="2"></td>
                            <td style="font-family:' . $font . ';background:#f1f6f7;" ' . $colspan . '>'
            . esc_html($items['tax']['product_name']) . '
                            </td>';
    if ($items['tax']['total_amount']) {
        $html .= '<td style="font-family:' . $font . ';background:#f1f6f7;" align="right">
                                ' . wp_kses_post(ppcart_format_price($items['tax']['total_amount'])) . '
                            </td>';
    }
    $html .= '</tr>';
}
$html .= '
                    <tr>
                        <td colspan="2"></td>
                        <td style="font-family:' . $font . ';background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;">' . esc_html($amount_text) . '</td>
                        <td style="font-family:' . $font . ';background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;" align="right">' . wp_kses_post(ppcart_format_price($items['total']['total_amount'])) . '</td>
                    </tr>';

if ($is_refunded) {
    foreach ($ppcart_order->refund_log as $refund) {
        $html .= '<tr>
                                <td colspan="2"></td>
                                <td style="font-family:' . $font . ';color: #e74c3c;">' . /* translators: %s: refund date. */ sprintf(esc_html__('Refunded on %s', 'publishpress-cart'), esc_html($refund['date'])) . '</td>
                                <td style="font-family:' . $font . ';color: #e74c3c;" align="right">-' . wp_kses_post(ppcart_format_price($refund['amount'])) . '</td>
                            </tr>';
    }
}

if ($notes) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by ppcart_kses_email_html().
    $html .= '<tr>
                        <td colspan="4" style="font-family:' . $font . '; "><br><br><br>
                        <strong style="">' . esc_html__('Notes / Terms', 'publishpress-cart') . '</strong><br>
                        ' . ppcart_kses_email_html(wpautop(wp_specialchars_decode($notes))) . '</td>
                    </tr>';
}

// Check if custom field exist
if ($product_custom_fields && $order_custom_fields) {
    $custom_field_html = '';
    foreach ($product_custom_fields as $field) {
        // Check if it should be included in the invoice
        if (
            isset($field['field_include_in_invoice']) && $field['field_include_in_invoice'] &&
            isset($field['field_id']) &&
            isset($order_custom_fields[$field['field_id']]) &&
            !empty($order_custom_fields[$field['field_id']]['value'])
        ) {
            // Add each custom field with a <p> tag, add margin top after the first <p>
            $label = $order_custom_fields[$field['field_id']]['label'] ?? '';
            $value = $order_custom_fields[$field['field_id']]['value'] ?? '';
            $label = is_scalar($label) ? $label : '';
            $value = is_scalar($value) ? $value : '';
            $custom_field_html .= '<p style="margin: ' . ($custom_field_html ? '6px' : '0') . ' 0 0 0;">' .
               '<strong>' . esc_html($label) . ':</strong> ' .
               esc_html($value) . '</p>';
        }
    }

    // Output the custom fields in a single <tr> if exist
    if ($custom_field_html) {
        $html .= '<tr><td colspan="4" style="font-family:' . $font . ';"><br>' . $custom_field_html . '</td></tr>';
    }
}
$html .= '</table>
            </td>
        </tr>
    </tbody></table>';

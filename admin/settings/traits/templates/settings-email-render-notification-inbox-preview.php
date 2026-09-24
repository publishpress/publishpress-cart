<?php

if (! defined('ABSPATH')) {
    exit;
}


$from_line = trim($from_name . ' <' . $from_email . '>');

if ('' === trim((string) $to)) {
    $to = __('(recipient resolved at send time)', 'publishpress-cart');
}

$styles = '
    *{box-sizing:border-box;}
    body{margin:0;background:#f0f2f5;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#1f2733;padding:20px;}
    .ppcart-inbox{max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e6ec;border-radius:10px;overflow:hidden;box-shadow:0 8px 24px rgba(20,30,45,0.08);}
    .ppcart-inbox__head{padding:18px 24px;border-bottom:1px solid #edf0f4;background:#fbfcfe;}
    .ppcart-inbox__subject{font-size:18px;font-weight:600;line-height:1.35;color:#0f1a2b;margin:0 0 12px;}
    .ppcart-inbox__row{display:flex;font-size:13px;line-height:1.5;color:#5a6472;margin-top:4px;}
    .ppcart-inbox__label{flex:0 0 46px;color:#9aa4b2;text-transform:uppercase;letter-spacing:.04em;font-size:11px;font-weight:600;padding-top:1px;}
    .ppcart-inbox__value{flex:1 1 auto;color:#38414f;word-break:break-word;}
    .ppcart-inbox__body{padding:24px;font-size:14px;line-height:1.6;color:#2a333f;}
    .ppcart-inbox__body p:first-child{margin-top:0;}
    .ppcart-inbox__body p:last-child{margin-bottom:0;}
    .ppcart-inbox__body img{max-width:100%;height:auto;}
';

$doc  = '<!doctype html><html><head><meta charset="utf-8">';
$doc .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
$doc .= '<title>' . esc_html__('Email preview', 'publishpress-cart') . '</title>';
$doc .= '<style>' . $styles . '</style></head><body>';
$doc .= '<div class="ppcart-inbox">';
$doc .= '<div class="ppcart-inbox__head">';
$doc .= '<div class="ppcart-inbox__subject">' . esc_html($subject) . '</div>';
$doc .= '<div class="ppcart-inbox__row"><span class="ppcart-inbox__label">' . esc_html__('From', 'publishpress-cart') . '</span><span class="ppcart-inbox__value">' . esc_html($from_line) . '</span></div>';
$doc .= '<div class="ppcart-inbox__row"><span class="ppcart-inbox__label">' . esc_html__('To', 'publishpress-cart') . '</span><span class="ppcart-inbox__value">' . esc_html($to) . '</span></div>';
$doc .= '</div>';
// Sanitize AFTER personalization: read_notification_post_entry()'s kses ran before ppcart_personalize() spliced in raw order data.
$safe_body = function_exists('ppcart_kses_email_html') ? ppcart_kses_email_html($body) : wp_kses_post($body);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized on the line above.
$doc .= '<div class="ppcart-inbox__body">' . $safe_body . '</div>';
$doc .= '</div></body></html>';

return $doc;

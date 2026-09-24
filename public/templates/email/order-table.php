<?php

if (! defined('ABSPATH')) {
    exit;
}

$email_type = $args['type'];
$items = $args['items'];
$email_order = $args['order'];
$sub = $args['sub'];
?>

<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:10px 20px 30px;font-family:'Lato',sans-serif;" align="left">

  <div style="color: #303030; line-height: 120%; text-align: center; word-wrap: break-word;">


<div>
  <div>
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 10px;background-color: #ffffff;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->

<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div>
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->

<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:5px 0px 0px;font-family:'Lato',sans-serif;" align="left">

  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><strong><span style="font-size: 16px; line-height: 19.2px;"><?php esc_html_e('Order Details', 'publishpress-cart'); ?></span></strong></p>
<p style="font-size: 14px; line-height: 120%;">&nbsp;</p>
<p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><?php esc_html_e('Order Number:', 'publishpress-cart'); ?> <?php echo esc_html($email_order->id); ?><br /><?php esc_html_e('Purchase Date:', 'publishpress-cart'); ?> <?php echo esc_html(get_the_date('', $email_order->id)); ?></span></span></p>
  </div>

      </td>
    </tr>
  </tbody>
</table>

  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
  </div>
</div>
<!--[if (mso)|(IE)]></td><![endif]-->
      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
    </div>
  </div>
</div>



<div>
  <div>
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;text-align: left;font-size: 14px;">
      <table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">

                <tr><td colspan="2" style="padding: 10px 0;">

                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                </td></tr>

                <?php
                $main_amt = $email_order->main_offer_amt;
if ($sub && $sub->sign_up_fee) {
    $main_amt -= $sub->sign_up_fee;
}
$discount = [];
?>

                <?php foreach ($items['items'] as $item) : ?>
                    <?php $item['item_type'] ??= ''; ?>
                    <tr>
                      <td id="main-offer-row" style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <strong>
                        <?php
          echo esc_html($item['product_name']);
                    // Only append price_name for main items with a distinct, non-empty plan name
                    if (isset($item['price_name']) && !empty($item['price_name']) && $item['item_type'] === 'main' && $item['price_name'] !== $item['product_name']) {
                        echo ' - ' . esc_html($item['price_name']);
                    }
                    ?>
                        </strong>
                        <?php if (isset($item['purchase_note'])) : ?>
                          <br><span class="ppcart-purchase-note"><?php echo wp_kses_post($item['purchase_note']); ?></span>
                        <?php endif; ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($item['subtotal']) ? wp_kses_post(ppcart_format_price($item['subtotal'])) : ''; ?>
                      </td>
                    </tr>
                <?php endforeach; ?>

                <tr><td colspan="2" style="padding-top: 10px;">
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td></tr>

                    <tr>
                        <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;text-transform: uppercase;"><?php esc_html_e('Subtotal', 'publishpress-cart'); ?></td>
                        <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right"><?php echo wp_kses_post(ppcart_format_price($items['subtotal']['total_amount'])); ?></td>
                    </tr>

                  <?php if (isset($items['discounts'])) :
                      foreach ($items['discounts'] as $item) :?>
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                          <?php echo esc_html($item['product_name']); ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                          <?php echo '-' . wp_kses_post(ppcart_format_price($item['subtotal'])); ?>
                      </td>
                    </tr>
                      <?php endforeach;
                  endif; ?>

                  <?php if (isset($items['shipping'])) :?>
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <?php echo esc_html($items['shipping']['product_name']); ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($items['shipping']['subtotal']) ? wp_kses_post(ppcart_format_price($items['shipping']['subtotal'])) : ''; ?>
                      </td>
                    </tr>
                  <?php endif; ?>

                  <?php if (isset($items['tax']) && $items['tax']['product_name']) :?>
                    <tr>
                      <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;">
                        <?php echo esc_html($items['tax']['product_name']); ?>
                      </td>
                      <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;" align="right">
                        <?php echo isset($items['tax']['subtotal']) ? wp_kses_post(ppcart_format_price($items['tax']['subtotal'])) : ''; ?>
                      </td>
                    </tr>

                  <tr><td colspan="2">
                    <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                      <tbody>
                        <tr style="vertical-align: top">
                          <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                            <span>&nbsp;</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td></tr>
                  <?php endif; ?>

                <tr>
                    <td style="overflow-wrap:break-word;word-break:break-word;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030;text-transform: uppercase; font-weight: bold;"><?php esc_html_e('Order Total', 'publishpress-cart'); ?></td>
                    <td style="overflow-wrap:normal;word-break:normal;font-family:'Lato',sans-serif;padding: 10px 0;color: #303030; font-weight: bold;" align="right"><?php echo wp_kses_post(ppcart_format_price($email_order->amount)); ?></td>
                </tr>

                <tr><td colspan="2">
                  <table height="0px" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;border-top: 1px dotted #CCC;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%">
                    <tbody>
                      <tr style="vertical-align: top">
                        <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top;font-size: 0px;line-height: 0px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
                          <span>&nbsp;</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                </td></tr>
            </table>
    </div>
  </div>
</div>


<div>
  <div>
    <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
      <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px 10px 5px;background-color: rgba(255,255,255,0);" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:600px;"><tr style="background-color: transparent;"><![endif]-->

<!--[if (mso)|(IE)]><td align="center" width="600" style="width: 600px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;" valign="top"><![endif]-->
<div>
  <div style="width: 100% !important;">
  <!--[if (!mso)&(!IE)]><!--><div style="padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;"><!--<![endif]-->

<?php do_action('ppcart_email_after_order_table', $email_order); ?>

<table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
  <tbody>
    <tr>
      <td style="overflow-wrap:break-word;word-break:break-word;padding:25px 0px 20px;font-family:'Lato',sans-serif;" align="left">

  <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
    <p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;"><span style="line-height: 16.8px; font-size: 14px;"><strong><?php esc_html_e('Customer Information', 'publishpress-cart'); ?></strong><br />

        <?php $customer_info = esc_html($email_order->customer_name) . '<br>';

if (isset($email_order->company)) {
    $customer_info .= esc_html($email_order->company) . '<br>';
}
if (isset($email_order->email)) {
    $customer_info .= esc_html($email_order->email) . '<br>';
}
if (isset($email_order->phone)) {
    $customer_info .= esc_html($email_order->phone) . '<br>';
}

$address = ppcart_format_order_address($email_order);
if ($address) {
    $customer_info .= '<br>' . $address;
}

if (!empty($email_order->vat_number)) {
    $customer_info .= esc_html(apply_filters('ppcart_vat_title', __('VAT Number', 'publishpress-cart'))) . ': ' . esc_html($email_order->vat_number);
}

$customer_info = apply_filters('ppcart_email_template_customer_info', $customer_info, $email_type, $email_order->id);
echo wp_kses_post($customer_info);
?>
        </span></span></p>
  </div>

      </td>
    </tr>
  </tbody>
</table>

  <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
  </div>
</div>
<!--[if (mso)|(IE)]></td><![endif]-->
      <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
    </div>
  </div>
</div>


      </div>

      </td>
    </tr>
  </tbody>
</table>

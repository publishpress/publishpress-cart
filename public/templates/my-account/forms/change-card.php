<?php

if (! defined('ABSPATH')) {
    exit;
}

$ppcart_plan = ppcart_filter_input_request('ppcart-plan', FILTER_VALIDATE_INT);
if ((false === $ppcart_plan || null === $ppcart_plan) && ! empty($attr['plan'])) {
    $ppcart_plan = absint($attr['plan']);
}
$ppcart_plan = $ppcart_plan ? (string) absint($ppcart_plan) : '';
$request_action = ppcart_filter_input_request('action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
?>
<div class="ppcart">
    <div class="modal update-card-modal" role="dialog" aria-modal="true" aria-labelledby="ppcart-update-card-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 id="ppcart-update-card-title"><?php esc_html_e('Update Payment Method', 'publishpress-cart'); ?></h2>
                <button type="button" class="ppcart-btn-close closemodal" aria-label="<?php esc_attr_e('Close update payment method', 'publishpress-cart'); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-close')); ?>"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body">
                <div class="success-msg" id="ppcart-update-card-success"></div>

                <section class="ppcart">
                    <div class="ppcart-section card-details">
                        <form id="ppcart-update-card-form" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-form')); ?>">
                            <div class="ppcart-row">

                                <div class="ppcart-form-group ppcart-col-sm-12">
                                    <label for="ppcart-card-holder-name"><?php esc_html_e('Cardholder Name', 'publishpress-cart'); ?><span class="req" aria-hidden="true">*</span></label>
                                    <label id="ppcart-cardholder-error" class="err-hide"></label>
                                    <input type="text" id="ppcart-card-holder-name" name="card_holder" autocomplete="cc-name" aria-required="true" class="ppcart-form-control required" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-holder-name')); ?>">
                                    <input type="hidden" id="ppcart-subscription-id" name="ppcart_subscription_id" value="<?php echo esc_attr($ppcart_plan); ?>">
                                </div>

                                <div class="ppcart-form-group ppcart-col-sm-12">
                                    <span class="ppcart-card-element-label" id="ppcart-card-number-label"><?php esc_html_e('Card Number', 'publishpress-cart'); ?><span class="req" aria-hidden="true">*</span></span>
                                    <label id="ppcart-card-error" class="err-hide"></label>
                                    <div id="ppcart-card-number" class="ppcart-form-control" aria-labelledby="ppcart-card-number-label" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-number')); ?>"></div>
                                </div>

                                <div class="ppcart-form-group ppcart-col-sm-6">
                                    <span class="ppcart-card-element-label" id="ppcart-card-cvc-label"><?php esc_html_e('Security Code', 'publishpress-cart'); ?><span class="req" aria-hidden="true">*</span></span>
                                    <label id="ppcart-cvc-error" class="err-hide"></label>
                                    <div id="ppcart-card-cvc" class="ppcart-form-control" aria-labelledby="ppcart-card-cvc-label" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-cvc')); ?>"></div>
                                </div>

                                <div class="ppcart-form-group ppcart-col-sm-6">
                                    <span class="ppcart-card-element-label" id="ppcart-card-expiry-label"><?php esc_html_e('Expiry Date', 'publishpress-cart'); ?><span class="req" aria-hidden="true">*</span></span>
                                    <label id="ppcart-expiry-error" class="err-hide"></label>
                                    <div id="ppcart-card-expiry" class="ppcart-form-control" aria-labelledby="ppcart-card-expiry-label" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-expiry')); ?>"></div>
                                </div>

                                <div class="ppcart-form-group ppcart-col-sm-12">
                                    <input type="checkbox" id="ppcart-all-subscription" name="all_subscription" value="1" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-all-subscriptions')); ?>">
                                    <label for="ppcart-all-subscription"><?php esc_html_e('Set default for all active subscriptions', 'publishpress-cart'); ?></label>
                                </div>

                                <div class="ppcart-form-group ppcart-col-sm-12">
                                    <button id="ppcart_update_card_button" type="submit" class="ppcart-btn ppcart-btn-primary ppcart-btn-block" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-update-card-submit')); ?>">
                                        <span>
                                            <?php
                                            if (is_string($request_action) && 'pay' === $request_action) {
                                                esc_html_e('Pay Now and Save Card', 'publishpress-cart');
                                            } else {
                                                esc_html_e('Save Card', 'publishpress-cart');
                                            }
?>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <div class="ppcart-preloader" id="ppcart-preloader">
                    <svg width="48" height="48" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" stroke="#333333">
                        <g fill="none" fill-rule="evenodd">
                            <g transform="translate(1 1)" stroke-width="2">
                                <circle stroke-opacity=".5" cx="18" cy="18" r="18"/>
                                <path d="M36 18c0-9.94-8.06-18-18-18">
                                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/>
                                </path>
                            </g>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

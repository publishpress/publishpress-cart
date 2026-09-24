<?php

if (! defined('ABSPATH')) {
    exit;
}


$data = [];
$data['ID'] = $this->id;
foreach ($this->attrs as $key) {
    $data[$key] = $this->$key;
}

if ($data['user_account']) {
    $data['user'] = get_user_by('id', $data['user_account']);
}

$data['status'] = (in_array($this->status, ['pending-payment','initiated'])) ? 'pending' : $this->status;
$data['status_label'] = $this->get_status();

if ($data['free_trial_days']) {
    $start_date =  gmdate("M j, Y", strtotime(get_the_time('Y-m-d', $this->id) . "+" . $data['free_trial_days'] . " day"));
} else {
    $start_date =  get_the_time('M j, Y', $this->id);
}
$data['start_date'] = $start_date;

$data['next_pay_date'] = '';
if ($data['status'] == 'completed' || $data['status'] == 'paused') {
    $data['next_pay_date'] = "n/a";
} elseif ($nextdate = $data['sub_next_bill_date']) {
    if (is_numeric($nextdate)) {
        $data['next_pay_date'] = get_date_from_gmt(gmdate('Y-m-d H:i:s', $nextdate), 'M j, Y');
    } else {
        $dateTime = DateTime::createFromFormat('Y-m-d', $nextdate);
        if ($dateTime !== false) {
            $data['next_pay_date'] = $dateTime->format('M j, Y');
        }
    }
}

$data['sub_end_date'] ??= '';
if ($data['sub_end_date']) {
    $data['end_date'] = gmdate("M j, Y", strtotime($data['sub_end_date']));
} else {
    $data['end_date'] = false;
}

$data['sub_payment'] = '<span class="ppcart-Price-amount amount">' . ppcart_format_price($data['sub_amount']) . '</span> / ';

// payment without html around currency symbol
$data['sub_payment_plain'] = ppcart_format_price($data['sub_amount'], false) . ' / ';
if ($data['sub_frequency'] > 1) {
    $data['sub_payment'] .= ($data['sub_frequency'] . ' ' . ppcart_pluralize_interval($data['sub_interval']));
    $data['sub_payment_plain'] .= ($data['sub_frequency'] . ' ' . ppcart_pluralize_interval($data['sub_interval']));
} else {
    $data['sub_payment'] .= ppcart_singularize_interval($data['sub_interval']);
    $data['sub_payment_plain'] .= ppcart_singularize_interval($data['sub_interval']);
}

$data['sub_payment_terms'] = $data['sub_payment'];
if ($data['sub_installments'] > 1) {
    $data['sub_payment_terms'] .= ' x ' . $data['sub_installments'];
} elseif (isset($data['sub_end_date'])) {
    ppcart_delete_post_meta($data['ID'], 'sub_end_date');
    unset($data['sub_end_date']);
}

// terms without html around currency symbol
$data['sub_payment_terms_plain'] = $data['sub_payment_plain'];
if ($data['sub_installments'] > 1) {
    $data['sub_payment_terms_plain'] .= ' x ' . $data['sub_installments'];
}

return $data;

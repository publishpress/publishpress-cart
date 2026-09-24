<?php
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'PPCART_STRIPE_WEBHOOK_LOG_DIR', sys_get_temp_dir() . '/ppcart-stripe-webhook-log-test-' . uniqid() );
define( 'PPCART_STRIPE_WEBHOOK_LOG_MAX_BYTES', 1200 );

$ppcart_test_transients = array();
$ppcart_test_actions    = array();
$ppcart_test_options    = array();
$ppcart_test_post_meta  = array();
$ppcart_test_debug_logs = array();
$ppcart_test_order_store_counts = array();
$ppcart_test_records    = array(
    'orders'        => array(),
    'subscriptions' => array(),
);

class PPCart_Order {
    public $id = 0;
    public $pay_method = '';
    public $status = '';
    public $payment_status = '';
    public $transaction_id = '';
    public $refund_log = array();
    public $amount = 0;
    public $subscription_id = 0;
    public $email = '';
    public $first_name = '';
    public $last_name = '';
    public $currency = 'USD';

    public function __construct( $id = 0 ) {
        global $ppcart_test_records;

        if ( is_numeric( $id ) && isset( $ppcart_test_records['orders'][ $id ] ) ) {
            foreach ( $ppcart_test_records['orders'][ $id ] as $key => $value ) {
                $this->$key = $value;
            }
        }
    }

    public static function get_by_trans_id( $transaction_id ) {
        global $ppcart_test_records;

        foreach ( $ppcart_test_records['orders'] as $record ) {
            if ( isset( $record['transaction_id'] ) && $transaction_id === $record['transaction_id'] ) {
                return new self( $record['id'] );
            }
        }

        return false;
    }

    public function store() {
        global $ppcart_test_order_store_counts, $ppcart_test_records;

        $ppcart_test_records['orders'][ $this->id ] = get_object_vars( $this );
        if ( ! isset( $ppcart_test_order_store_counts[ $this->id ] ) ) {
            $ppcart_test_order_store_counts[ $this->id ] = 0;
        }
        $ppcart_test_order_store_counts[ $this->id ]++;
        return $this->id;
    }

    public function set_date_from_timestamp( $timestamp ) {
        $this->date = $timestamp;
    }

    public function get_data() {
        return get_object_vars( $this );
    }
}

class PPCart_Subscription {
    public $id = 0;
    public $pay_method = '';
    public $subscription_id = '';
    public $status = '';
    public $sub_status = '';
    public $sub_next_bill_date = '';
    public $cancel_at = '';
    public $cancel_date = '';
    public $sub_installments = 0;
    public $sub_end_date = '';
    public $amount = 0;
    public $currency = 'USD';
    public $email = '';
    public $first_name = '';
    public $last_name = '';

    public function __construct( $id = 0 ) {
        global $ppcart_test_records;

        if ( is_numeric( $id ) && isset( $ppcart_test_records['subscriptions'][ $id ] ) ) {
            foreach ( $ppcart_test_records['subscriptions'][ $id ] as $key => $value ) {
                $this->$key = $value;
            }
        }
    }

    public static function get_by_sub_id( $subscription_id ) {
        global $ppcart_test_records;

        foreach ( $ppcart_test_records['subscriptions'] as $record ) {
            if ( isset( $record['subscription_id'] ) && $subscription_id === $record['subscription_id'] ) {
                return new self( $record['id'] );
            }
        }

        return false;
    }

    public function store() {
        global $ppcart_test_records;

        $ppcart_test_records['subscriptions'][ $this->id ] = get_object_vars( $this );
        foreach ( get_object_vars( $this ) as $key => $value ) {
            if ( 'id' !== $key && $value ) {
                update_post_meta( $this->id, ppcart_meta_key( $key ), $value );
            }
        }

        return $this->id;
    }

    public function order_count( $status = false ) {
        global $ppcart_test_records;

        $count = 0;
        foreach ( $ppcart_test_records['orders'] as $record ) {
            if ( isset( $record['subscription_id'] ) && (int) $record['subscription_id'] === (int) $this->id ) {
                if ( ! $status || ( isset( $record['status'] ) && $status === $record['status'] ) ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function first_order() {
        global $ppcart_test_records;

        foreach ( $ppcart_test_records['orders'] as $record ) {
            if ( isset( $record['subscription_id'] ) && (int) $record['subscription_id'] === (int) $this->id ) {
                return new PPCart_Order( $record['id'] );
            }
        }

        return false;
    }

    public function new_order() {
        return false;
    }
}

class PPCart_Test_Stripe_Charge_Service {
    private $charge;

    public function __construct( $charge ) {
        $this->charge = $charge;
    }

    public function retrieve() {
        return $this->charge;
    }
}

class PPCart_Test_Stripe_Client {
    public $charges;

    public function __construct( $charge ) {
        $this->charges = new PPCart_Test_Stripe_Charge_Service( $charge );
    }
}

class PPCart_Test_Logger {
    public function log_debug( $message, $level = 0 ) {
        global $ppcart_test_debug_logs;

        $ppcart_test_debug_logs[] = array(
            'message' => $message,
            'level'   => $level,
        );
    }
}

function add_action( $hook, $callback ) {
    global $ppcart_test_actions;
    $ppcart_test_actions[ $hook ] = $callback;
}

function add_filter() {
    return true;
}

function do_action() {
    return true;
}

function wp_next_scheduled() {
    return true;
}

function wp_schedule_event() {
    return true;
}

function get_posts( $args ) {
    global $ppcart_test_get_posts_args;
    $ppcart_test_get_posts_args = $args;
    return array();
}

function get_post_type( $post_id ) {
    global $ppcart_test_records;

    if ( isset( $ppcart_test_records['orders'][ $post_id ] ) ) {
        return 'sc_order';
    }

    if ( isset( $ppcart_test_records['subscriptions'][ $post_id ] ) ) {
        return 'sc_subscription';
    }

    return false;
}

function esc_html__( $text ) {
    return $text;
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function sanitize_text_field( $value ) {
    return is_scalar( $value ) ? (string) $value : $value;
}

function sanitize_key( $key ) {
    return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
}

function absint( $value ) {
    return abs( (int) $value );
}

function get_option( $key, $default = false ) {
    global $ppcart_test_options;
    return isset( $ppcart_test_options[ $key ] ) ? $ppcart_test_options[ $key ] : $default;
}

function update_option( $key, $value ) {
    global $ppcart_test_options;
    $ppcart_test_options[ $key ] = $value;
    return true;
}

function delete_option( $key ) {
    global $ppcart_test_options;
    unset( $ppcart_test_options[ $key ] );
    return true;
}

function get_transient( $key ) {
    global $ppcart_test_transients;
    return isset( $ppcart_test_transients[ $key ] ) ? $ppcart_test_transients[ $key ] : false;
}

function set_transient( $key, $value ) {
    global $ppcart_test_transients;
    $ppcart_test_transients[ $key ] = $value;
    return true;
}

function delete_transient( $key ) {
    global $ppcart_test_transients;
    unset( $ppcart_test_transients[ $key ] );
    return true;
}

function wp_doing_ajax() {
    return false;
}

function get_site_url() {
    return 'https://example.test';
}

function wp_json_encode( $value ) {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone test shim for WordPress' wp_json_encode().
    return json_encode( $value );
}

function ppcart_format_stripe_number( $amount, $currency = 'USD' ) {
    return $amount / 100;
}

function get_post_meta( $post_id, $key, $single = false ) {
    global $ppcart_test_post_meta;

    if ( ! isset( $ppcart_test_post_meta[ $post_id ][ $key ] ) ) {
        return $single ? '' : array();
    }

    return $single ? $ppcart_test_post_meta[ $post_id ][ $key ] : array( $ppcart_test_post_meta[ $post_id ][ $key ] );
}

function update_post_meta( $post_id, $key, $value ) {
    global $ppcart_test_post_meta;
    $ppcart_test_post_meta[ $post_id ][ $key ] = $value;
}

function delete_post_meta( $post_id, $key ) {
    global $ppcart_test_post_meta;
    unset( $ppcart_test_post_meta[ $post_id ][ $key ] );
}

require dirname( __DIR__, 4 ) . '/includes/class-ppcart-stripe-sync.php';

function ppcart_stripe_sync_assert( $condition, $message ) {
    if ( ! $condition ) {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- Standalone CLI smoke test writes failures to STDERR.
        fwrite( STDERR, '[FAIL] ' . $message . PHP_EOL );
        exit( 1 );
    }

    echo esc_html( '[PASS] ' . $message ) . PHP_EOL;
}

function ppcart_stripe_webhook_log_reset() {
    global $ppcart_test_options, $ppcart_test_transients;

    if ( class_exists( 'PPCart_Stripe_Webhook_Logger' ) ) {
        PPCart_Stripe_Webhook_Logger::clear_log_files();
    }

    unset(
        $ppcart_test_options['_ppcart_enable_stripe_webhook_log'],
        $ppcart_test_options['_ppcart_stripe_webhook_log_include_ignored'],
        $ppcart_test_options['_ppcart_stripe_webhook_log_file']
    );
    $ppcart_test_transients = array();
    $_SERVER                 = array();
}

$status_cases = array(
    'active'             => 'active',
    'trialing'           => 'trialing',
    'past_due'           => 'past_due',
    'unpaid'             => 'unpaid',
    'incomplete'         => 'incomplete',
    'incomplete_expired' => 'canceled',
    'canceled'           => 'canceled',
    'paused'             => 'paused',
    'future_status'      => 'past_due',
);

foreach ( $status_cases as $stripe_status => $expected_status ) {
    $subscription = (object) array(
        'status'             => $stripe_status,
        'current_period_end' => 1234567890,
        'cancel_at'          => 0,
    );
    $mapped       = PPCart_Stripe_Sync::map_subscription( $subscription );

    ppcart_stripe_sync_assert(
        $expected_status === $mapped['status'],
        'Stripe status ' . $stripe_status . ' maps to PPC status ' . $expected_status
    );
}

$incomplete_expired = PPCart_Stripe_Sync::map_subscription(
    (object) array(
        'status'             => 'incomplete_expired',
        'current_period_end' => 1234567890,
        'cancel_at'          => 0,
    )
);
ppcart_stripe_sync_assert( 'incomplete_expired' === $incomplete_expired['sub_status'], 'incomplete_expired raw sub_status is preserved' );

$paused = PPCart_Stripe_Sync::map_subscription(
    (object) array(
        'status'             => 'active',
        'current_period_end' => 1234567890,
        'cancel_at'          => 0,
        'pause_collection'   => (object) array(
            'behavior' => 'void',
        ),
    )
);
ppcart_stripe_sync_assert( 'paused' === $paused['status'], 'pause_collection void maps to local paused' );
ppcart_stripe_sync_assert( '' === $paused['sub_next_bill_date'], 'paused subscriptions do not keep a next bill date' );

$canceling = PPCart_Stripe_Sync::map_subscription(
    (object) array(
        'status'             => 'active',
        'current_period_end' => 1234567890,
        'cancel_at'          => 1234567890,
    )
);
ppcart_stripe_sync_assert( '' === $canceling['sub_next_bill_date'], 'cancel_at clears local next bill date' );

$normalized_log = PPCart_Stripe_Sync::normalize_refund_log(
    array(
        array(
            'refundID' => 're_one',
            'amount'   => '1.00',
        ),
        're_two' => array(
            'amount' => '2.00',
        ),
    )
);
ppcart_stripe_sync_assert( isset( $normalized_log['re_one'], $normalized_log['re_two'] ), 'refund log normalizes entries by refund ID' );

$manual_log = PPCart_Stripe_Sync::normalize_refund_log(
    array(
        'manual'       => array(
            'refundID' => 'manual',
            'amount'   => '1.00',
        ),
        'manual_1_2'   => array(
            'refundID' => 'manual',
            'amount'   => '2.00',
        ),
        'manual_1_3'   => array(
            'refundID' => 'manual',
            'amount'   => '3.00',
        ),
    )
);
ppcart_stripe_sync_assert( isset( $manual_log['manual'], $manual_log['manual_1_2'], $manual_log['manual_1_3'] ), 'manual refund log entries keep their unique keys during normalization' );

$event = (object) array( 'id' => 'evt_duplicate' );
ppcart_stripe_sync_assert( PPCart_Stripe_Sync::event_dedupe_gate( $event ), 'first webhook event ID passes dedupe gate' );
ppcart_stripe_sync_assert( ! PPCart_Stripe_Sync::event_dedupe_gate( $event ), 'duplicate webhook event ID is rejected' );
ppcart_stripe_sync_assert( isset( $ppcart_test_transients['ppcart_stripe_evt_evt_duplicate'] ), 'event dedupe uses plugin transient prefix' );

$retry_event = (object) array( 'id' => 'evt_retry' );
PPCart_Stripe_Sync::event_dedupe_gate( $retry_event );
PPCart_Stripe_Sync::release_event_dedupe_gate( $retry_event );
ppcart_stripe_sync_assert( PPCart_Stripe_Sync::event_dedupe_gate( $retry_event ), 'unapplied webhook events can be released for retry' );

$ppcart_debug_logger = new PPCart_Test_Logger();
PPCart_Stripe_Sync::map_subscription(
    (object) array(
        'status'             => 'brand_new_status',
        'current_period_end' => 1234567890,
        'cancel_at'          => 0,
    )
);
ppcart_stripe_sync_assert( false !== strpos( $ppcart_test_debug_logs[0]['message'], 'Unknown Stripe subscription status' ), 'unknown Stripe subscription status is logged' );
$ppcart_debug_logger = null;

ppcart_stripe_sync_assert( ppcart_is_stripe_owned_field( '_ppcart_status' ), 'Stripe-owned field helper recognizes status meta' );
ppcart_stripe_sync_assert( ppcart_is_stripe_owned_field( '_ppcart_payment_status', new PPCart_Order() ), 'order payment status is Stripe-owned' );
ppcart_stripe_sync_assert( ! ppcart_is_stripe_owned_field( '_ppcart_email', new PPCart_Order() ), 'order customer email remains locally editable' );
ppcart_stripe_sync_assert( ppcart_is_stripe_owned_field( '_ppcart_sub_next_bill_date', new PPCart_Subscription() ), 'subscription next bill date is Stripe-owned' );
ppcart_stripe_sync_assert( ! ppcart_is_stripe_owned_field( '_ppcart_first_name', new PPCart_Subscription() ), 'subscription customer first name remains locally editable' );

$ppcart_test_records['orders'][101] = array(
    'id'             => 101,
    'pay_method'     => 'stripe',
    'status'         => 'paid',
    'payment_status' => 'paid',
    'transaction_id' => 'ch_current',
    'refund_log'     => array( 're_current' => array( 'refundID' => 're_current' ) ),
);

$changed_order                 = new PPCart_Order( 101 );
$changed_order->status         = 'refunded';
$changed_order->payment_status = 'refunded';
$changed_order->transaction_id = 'ch_local_change';
$changed_order->refund_log     = array( 're_local_change' => array( 'refundID' => 're_local_change' ) );
$preserved_order               = PPCart_Stripe_Sync::preserve_owned_fields( $changed_order );

ppcart_stripe_sync_assert( 'paid' === $preserved_order->status, 'data guard preserves Stripe-backed order status outside sync context' );
ppcart_stripe_sync_assert( 'paid' === $preserved_order->payment_status, 'data guard preserves Stripe-backed order payment status outside sync context' );
ppcart_stripe_sync_assert( 'ch_current' === $preserved_order->transaction_id, 'data guard preserves Stripe-backed order transaction ID outside sync context' );
ppcart_stripe_sync_assert( isset( $preserved_order->refund_log['re_current'] ), 'data guard preserves Stripe-backed order refund log outside sync context' );

$context_order                 = new PPCart_Order( 101 );
$context_order->status         = 'refunded';
$context_order->payment_status = 'refunded';
$context_order->transaction_id = 'ch_context_change';
$context_order                 = PPCart_Stripe_Sync_Context::run(
    function() use ( $context_order ) {
        return PPCart_Stripe_Sync::preserve_owned_fields( $context_order );
    }
);

ppcart_stripe_sync_assert( 'refunded' === $context_order->status, 'data guard allows order status writes inside sync context' );
ppcart_stripe_sync_assert( 'ch_context_change' === $context_order->transaction_id, 'data guard allows order transaction writes inside sync context' );

$ppcart_test_records['subscriptions'][202] = array(
    'id'                 => 202,
    'pay_method'         => 'stripe',
    'subscription_id'    => 'sub_current',
    'status'             => 'active',
    'sub_status'         => 'active',
    'sub_next_bill_date' => '111',
    'cancel_at'          => '',
    'cancel_date'        => '',
);

$changed_subscription                 = new PPCart_Subscription( 202 );
$changed_subscription->status         = 'canceled';
$changed_subscription->sub_status     = 'canceled';
$changed_subscription->subscription_id = 'sub_local_change';
$changed_subscription->sub_next_bill_date = '222';
$preserved_subscription               = PPCart_Stripe_Sync::preserve_owned_fields( $changed_subscription );

ppcart_stripe_sync_assert( 'active' === $preserved_subscription->status, 'data guard preserves Stripe-backed subscription status outside sync context' );
ppcart_stripe_sync_assert( 'active' === $preserved_subscription->sub_status, 'data guard preserves Stripe-backed subscription sub_status outside sync context' );
ppcart_stripe_sync_assert( 'sub_current' === $preserved_subscription->subscription_id, 'data guard preserves Stripe subscription ID outside sync context' );
ppcart_stripe_sync_assert( '111' === $preserved_subscription->sub_next_bill_date, 'data guard preserves next bill date outside sync context' );

$non_stripe_order                 = new PPCart_Order();
$non_stripe_order->id             = 303;
$non_stripe_order->pay_method     = 'cod';
$non_stripe_order->status         = 'paid';
$non_stripe_order->payment_status = 'paid';
$non_stripe_order                 = PPCart_Stripe_Sync::preserve_owned_fields( $non_stripe_order );
ppcart_stripe_sync_assert( 'paid' === $non_stripe_order->status, 'data guard leaves non-Stripe order status editable' );

$ppcart_test_post_meta[404]['_ppcart_pay_method'] = 'stripe';
ppcart_stripe_sync_assert( ppcart_is_stripe_backed_post( 404 ), 'Stripe-backed post helper detects Stripe pay method meta' );

$ppcart_test_post_meta[405]['_ppcart_stripe_subscription_id'] = 'sub_meta';
ppcart_stripe_sync_assert( ppcart_is_stripe_backed_post( 405 ), 'Stripe-backed post helper detects Stripe subscription meta' );

$ppcart_test_post_meta[406]['_ppcart_subscription_id'] = 'sub_legacy_meta';
ppcart_stripe_sync_assert( ppcart_is_stripe_backed_post( 406 ), 'Stripe-backed post helper detects legacy Stripe subscription meta' );

$resource_event = (object) array(
    'id'   => 'evt_resource',
    'type' => 'customer.subscription.updated',
);
PPCart_Stripe_Sync::mark_resource_event( 202, $resource_event );
ppcart_stripe_sync_assert( 'evt_resource' === $ppcart_test_post_meta[202]['_ppcart_last_stripe_event_id'], 'resource event ID is stored with PublishPress Cart meta prefix' );
ppcart_stripe_sync_assert( 'customer.subscription.updated' === $ppcart_test_post_meta[202]['_ppcart_last_stripe_event_type'], 'resource event type is stored with PublishPress Cart meta prefix' );
ppcart_stripe_sync_assert( ! empty( $ppcart_test_post_meta[202]['_ppcart_last_stripe_sync'] ), 'resource sync timestamp is stored with PublishPress Cart meta prefix' );

ppcart_stripe_webhook_log_reset();
$webhook_log_event = (object) array(
    'id'       => 'evt_log',
    'type'     => 'charge.refunded',
    'livemode' => false,
    'data'     => (object) array(
        'object' => (object) array(
            'object' => 'charge',
            'id'     => 'ch_log',
        ),
    ),
);
PPCart_Stripe_Sync::record_webhook_log(
    $webhook_log_event,
    'not_matched',
    'Could not match order.',
    array(
        'charge_id' => 'ch_log',
        'ignored'   => array( 'nested' ),
    )
);
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( isset( $ppcart_test_options['_ppcart_stripe_webhook_log_file'] ), 'Stripe webhook log lazy-creates the file option' );
ppcart_stripe_sync_assert( isset( $webhook_entries[0] ), 'Stripe webhook log stores the latest event in the file' );
ppcart_stripe_sync_assert( 'evt_log' === $webhook_entries[0]['event_id'], 'Stripe webhook log stores the event ID' );
ppcart_stripe_sync_assert( 'not_matched' === $webhook_entries[0]['status'], 'Stripe webhook log stores the handling status' );
ppcart_stripe_sync_assert( isset( $webhook_entries[0]['timestamp'], $webhook_entries[0]['time_utc'], $webhook_entries[0]['type'], $webhook_entries[0]['object'], $webhook_entries[0]['object_id'], $webhook_entries[0]['livemode'], $webhook_entries[0]['message'], $webhook_entries[0]['context'] ), 'Stripe webhook log stores the JSON Lines schema fields' );
ppcart_stripe_sync_assert( isset( $webhook_entries[0]['context']['charge_id'] ), 'Stripe webhook log stores scalar diagnostic context' );
ppcart_stripe_sync_assert( ! isset( $webhook_entries[0]['context']['ignored'] ), 'Stripe webhook log omits nested context values' );

PPCart_Stripe_Sync::record_webhook_log( $webhook_log_event, 'handled', 'Stripe webhook handler completed.' );
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( 1 === count( $webhook_entries ), 'generic handled rows are skipped when the event already has a final row' );

ppcart_stripe_webhook_log_reset();
$ppcart_test_options['_ppcart_enable_stripe_webhook_log'] = 0;
PPCart_Stripe_Sync::record_webhook_log( $webhook_log_event, 'applied', 'Disabled write.' );
ppcart_stripe_sync_assert( empty( $ppcart_test_options['_ppcart_stripe_webhook_log_file'] ), 'Stripe webhook log does not create a file when disabled' );

ppcart_stripe_webhook_log_reset();
PPCart_Stripe_Sync::record_webhook_log( $webhook_log_event, 'ignored', 'Ignored by default.' );
ppcart_stripe_sync_assert( empty( PPCart_Stripe_Webhook_Logger::read_entries() ), 'ignored Stripe webhook events are not written by default' );

ppcart_stripe_webhook_log_reset();
$ppcart_test_options['_ppcart_stripe_webhook_log_include_ignored'] = 1;
PPCart_Stripe_Sync::record_webhook_log( $webhook_log_event, 'ignored', 'Ignored when enabled.' );
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( isset( $webhook_entries[0] ) && 'ignored' === $webhook_entries[0]['status'], 'ignored Stripe webhook events are written when enabled' );

ppcart_stripe_webhook_log_reset();
PPCart_Stripe_Sync::record_webhook_log( $webhook_log_event, 'skipped', 'Skipped event.' );
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( isset( $webhook_entries[0] ) && 'skipped' === $webhook_entries[0]['status'], 'skipped is a first-class webhook log status' );

ppcart_stripe_webhook_log_reset();
$ppcart_test_records['orders'][407] = array(
    'id'              => 407,
    'pay_method'      => 'stripe',
    'status'          => 'paid',
    'payment_status'  => 'paid',
    'transaction_id'  => 'ch_customer',
    'amount'          => 200,
    'currency'        => 'USD',
    'subscription_id' => 408,
    'email'           => 'customer@example.test',
    'first_name'      => 'First',
    'last_name'       => 'Last',
);
$ppcart_test_records['subscriptions'][408] = array(
    'id'              => 408,
    'pay_method'      => 'stripe',
    'subscription_id' => 'sub_customer',
    'status'          => 'active',
    'amount'          => 200,
    'currency'        => 'USD',
    'email'           => 'customer@example.test',
    'first_name'      => 'First',
    'last_name'       => 'Last',
);
PPCart_Stripe_Sync::record_webhook_log(
    (object) array(
        'id'   => 'evt_customer_context',
        'type' => 'charge.succeeded',
        'data' => (object) array(
            'object' => (object) array(
                'object' => 'charge',
                'id'     => 'ch_customer',
            ),
        ),
    ),
    'handled',
    'Stripe webhook handler completed.',
    array(
        'record_id' => 407,
    )
);
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( 200 === $webhook_entries[0]['context']['amount'], 'Stripe webhook log hydrates order amount from record ID' );
ppcart_stripe_sync_assert( 408 === $webhook_entries[0]['context']['subscription_id'], 'Stripe webhook log hydrates parent subscription ID from order record' );
ppcart_stripe_sync_assert( 'customer@example.test' === $webhook_entries[0]['context']['customer_email'], 'Stripe webhook log hydrates customer email from record ID' );
ppcart_stripe_sync_assert( 'First Last' === $webhook_entries[0]['context']['customer_name'], 'Stripe webhook log hydrates customer name from record ID' );

ppcart_stripe_webhook_log_reset();
for ( $i = 0; $i < 4; $i++ ) {
    PPCart_Stripe_Sync::record_webhook_log(
        (object) array(
            'id'   => 'evt_rotate_' . $i,
            'type' => 'charge.refunded',
            'data' => (object) array(
                'object' => (object) array(
                    'object' => 'charge',
                    'id'     => 'ch_rotate_' . $i,
                ),
            ),
        ),
        'applied',
        'Rotation row.',
        array(
            'padding' => str_repeat( 'x', 500 ),
        )
    );
}
$rotated_file = PPCART_STRIPE_WEBHOOK_LOG_DIR . '/' . $ppcart_test_options['_ppcart_stripe_webhook_log_file'] . '.1';
ppcart_stripe_sync_assert( file_exists( $rotated_file ), 'Stripe webhook log rotates when max size is exceeded' );
PPCart_Stripe_Webhook_Logger::clear_log_files();
ppcart_stripe_sync_assert( ! file_exists( $rotated_file ), 'clearing the Stripe webhook log removes rotated files' );

ppcart_stripe_webhook_log_reset();
$_SERVER = array(
    'REQUEST_METHOD' => 'GET',
    'REMOTE_ADDR'    => '127.0.0.1',
);
PPCart_Stripe_Webhook_Logger::record_rejected_request( 'missing_signature' );
ppcart_stripe_sync_assert( empty( PPCart_Stripe_Webhook_Logger::read_entries() ), 'missing signature on a browser GET is not logged' );

$_SERVER = array(
    'REQUEST_METHOD' => 'POST',
    'CONTENT_TYPE'   => 'application/json',
    'CONTENT_LENGTH' => 50,
    'REMOTE_ADDR'    => '127.0.0.1',
);
PPCart_Stripe_Webhook_Logger::record_rejected_request( 'missing_signature' );
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( isset( $webhook_entries[0] ) && 'rejected' === $webhook_entries[0]['status'], 'missing signature on a JSON POST can log a rejected webhook request' );
ppcart_stripe_sync_assert( ! isset( $webhook_entries[0]['context']['body'] ), 'rejected webhook logging never stores a raw request body' );
PPCart_Stripe_Webhook_Logger::record_rejected_request( 'missing_signature' );
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( 1 === count( $webhook_entries ), 'rejected webhook logging is rate-limited' );

ppcart_stripe_webhook_log_reset();
$ppcart_test_options['_ppcart_stripe_webhook_log_file'] = 'invalid-line-stripe-webhook.log';
if ( ! is_dir( PPCART_STRIPE_WEBHOOK_LOG_DIR ) ) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir -- Standalone test creates an isolated temporary log fixture directory.
    mkdir( PPCART_STRIPE_WEBHOOK_LOG_DIR, 0755, true );
}
file_put_contents( PPCART_STRIPE_WEBHOOK_LOG_DIR . '/invalid-line-stripe-webhook.log', "{bad json\n" ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Standalone test writes a plugin-owned fixture log.
$webhook_entries = PPCart_Stripe_Webhook_Logger::read_entries();
ppcart_stripe_sync_assert( isset( $webhook_entries[0] ) && 'Invalid log line.' === $webhook_entries[0]['message'], 'invalid JSON lines do not break webhook log reading' );

PPCart_Stripe_Sync::mark_sync_failure( 202, new Exception( 'temporary failure' ) );
ppcart_stripe_sync_assert( 1 === $ppcart_test_post_meta[202]['_ppcart_stripe_recon_failures'], 'reconciliation failure count is stored' );
ppcart_stripe_sync_assert( ! empty( $ppcart_test_post_meta[202]['_ppcart_stripe_recon_backoff_until'] ), 'reconciliation backoff timestamp is stored' );
ppcart_stripe_sync_assert( 'temporary failure' === $ppcart_test_post_meta[202]['_ppcart_last_stripe_sync_error'], 'reconciliation last error is stored' );

PPCart_Stripe_Sync::mark_successful_sync( 202 );
ppcart_stripe_sync_assert( ! empty( $ppcart_test_post_meta[202]['_ppcart_last_stripe_sync'] ), 'successful reconciliation stores last sync timestamp' );
ppcart_stripe_sync_assert( ! isset( $ppcart_test_post_meta[202]['_ppcart_stripe_recon_failures'] ), 'successful reconciliation clears failure count' );
ppcart_stripe_sync_assert( ! isset( $ppcart_test_post_meta[202]['_ppcart_stripe_recon_backoff_until'] ), 'successful reconciliation clears backoff' );
ppcart_stripe_sync_assert( ! isset( $ppcart_test_post_meta[202]['_ppcart_last_stripe_sync_error'] ), 'successful reconciliation clears last error' );

ppcart_stripe_sync_assert( false === PPCart_Stripe_Sync::handle_webhook_event( (object) array( 'type' => 'unknown.event' ) ), 'unknown webhook event is ignored' );
ppcart_stripe_sync_assert( isset( $ppcart_test_actions[ PPCart_Stripe_Sync::CRON_HOOK ] ), 'Stripe reconciliation cron hook is registered' );

$ppcart_test_order_store_counts = array();
$ppcart_test_records['orders'][505] = array(
    'id'             => 505,
    'pay_method'     => 'stripe',
    'status'         => 'paid',
    'payment_status' => 'paid',
    'transaction_id' => 'ch_paid_noop',
    'currency'       => 'USD',
);
$paid_charge_noop = (object) array(
    'id'             => 'ch_paid_noop',
    'payment_intent' => 'pi_paid_noop',
    'amount'         => 20000,
    'currency'       => 'usd',
    'refunded'       => false,
    'metadata'       => (object) array(
        'ppcart_product_id' => 123,
        'ppcart_order_id'   => 505,
    ),
);
PPCart_Stripe_Sync::sync_charge_status( $paid_charge_noop );
ppcart_stripe_sync_assert( empty( $ppcart_test_order_store_counts[505] ), 'paid charge sync skips storing an already-synced order' );

$ppcart_test_order_store_counts = array();
$ppcart_test_records['orders'][506] = array(
    'id'             => 506,
    'pay_method'     => 'stripe',
    'status'         => 'paid',
    'payment_status' => 'paid',
    'transaction_id' => 'pi_paid_update',
    'currency'       => 'USD',
);
$paid_charge_update = (object) array(
    'id'             => 'ch_paid_update',
    'payment_intent' => 'pi_paid_update',
    'amount'         => 20000,
    'currency'       => 'usd',
    'refunded'       => false,
    'metadata'       => (object) array(
        'ppcart_product_id' => 123,
        'ppcart_order_id'   => 506,
    ),
);
PPCart_Stripe_Sync::sync_charge_status( $paid_charge_update );
ppcart_stripe_sync_assert( 1 === $ppcart_test_order_store_counts[506], 'paid charge sync still stores when the charge ID changes' );
ppcart_stripe_sync_assert( 'ch_paid_update' === $ppcart_test_records['orders'][506]['transaction_id'], 'paid charge sync updates PaymentIntent transaction ID to charge ID' );

$ppcart_test_order_store_counts = array();
$ppcart_test_records['subscriptions'][508] = array(
    'id'              => 508,
    'pay_method'      => 'stripe',
    'subscription_id' => 'sub_invoice_noop',
    'status'          => 'active',
);
$ppcart_test_records['orders'][507] = array(
    'id'              => 507,
    'pay_method'      => 'stripe',
    'status'          => 'paid',
    'payment_status'  => 'succeeded',
    'transaction_id'  => 'ch_invoice_noop',
    'amount'          => 200,
    'currency'        => 'USD',
    'subscription_id' => 508,
);
$paid_invoice_noop = (object) array(
    'subscription'       => 'sub_invoice_noop',
    'charge'             => 'ch_invoice_noop',
    'payment_intent'     => 'pi_invoice_noop',
    'amount_paid'        => 20000,
    'currency'           => 'usd',
    'status'             => 'paid',
    'status_transitions' => (object) array(
        'finalized_at' => 1234567890,
    ),
    'lines'              => (object) array(
        'data' => array(
            (object) array(
                'subscription_item' => 'si_invoice_noop',
                'metadata'          => (object) array(
                    'ppcart_product_id' => 123,
                    'origin'        => 'https://example.test',
                ),
                'period'            => (object) array(
                    'end' => 3333333333,
                ),
            ),
        ),
    ),
);
PPCart_Stripe_Sync::sync_invoice_resource( $paid_invoice_noop, new PPCart_Subscription( 508 ), 'invoice.payment_succeeded' );
ppcart_stripe_sync_assert( empty( $ppcart_test_order_store_counts[507] ), 'paid invoice sync skips storing an already-synced order' );

$ppcart_test_records['orders'][606] = array(
    'id'             => 606,
    'pay_method'     => 'stripe',
    'status'         => 'refunded',
    'payment_status' => 'refunded',
    'transaction_id' => 'ch_refunded',
    'refund_log'     => array(),
    'currency'       => 'USD',
);
$refunded_charge = (object) array(
    'id'              => 'ch_refunded',
    'amount'          => 1000,
    'amount_refunded' => 1000,
    'currency'        => 'usd',
    'refunded'        => true,
    'metadata'        => (object) array(
        'ppcart_product_id' => 123,
        'ppcart_order_id'   => 606,
    ),
    'refunds'         => (object) array(
        'data' => array(
            (object) array(
                'id'      => 're_refunded',
                'amount'  => 1000,
                'created' => 1234567890,
            ),
        ),
    ),
);
PPCart_Stripe_Sync::sync_charge_status( $refunded_charge, null, new PPCart_Test_Stripe_Client( $refunded_charge ) );
ppcart_stripe_sync_assert( 'refunded' === $ppcart_test_records['orders'][606]['status'], 'charge.succeeded sync does not mark a fully refunded charge paid' );
ppcart_stripe_sync_assert( isset( $ppcart_test_post_meta[606]['_ppcart_refund_log']['re_refunded'] ), 'charge.succeeded sync routes refunded charges through refund log sync' );

$ppcart_test_records['subscriptions'][707] = array(
    'id'                 => 707,
    'pay_method'         => 'stripe',
    'subscription_id'    => 'sub_clear',
    'status'             => 'active',
    'sub_status'         => 'active',
    'sub_next_bill_date' => '999',
    'cancel_at'          => 888,
    'cancel_date'        => '',
);
$ppcart_test_post_meta[707]['_ppcart_sub_next_bill_date'] = '999';
$ppcart_test_post_meta[707]['_ppcart_cancel_at'] = 888;
$stripe_canceled_subscription = (object) array(
    'id'                 => 'sub_clear',
    'status'             => 'canceled',
    'current_period_end' => 999,
    'cancel_at'          => 0,
    'canceled_at'        => 1234567890,
    'metadata'           => (object) array(
        'ppcart_subscription_id' => 707,
    ),
);
PPCart_Stripe_Sync::sync_subscription_resource( $stripe_canceled_subscription, null, null, true );
ppcart_stripe_sync_assert( ! isset( $ppcart_test_post_meta[707]['_ppcart_sub_next_bill_date'] ), 'subscription sync deletes cleared next bill date meta' );
ppcart_stripe_sync_assert( ! isset( $ppcart_test_post_meta[707]['_ppcart_cancel_at'] ), 'subscription sync deletes cleared cancel_at meta' );
ppcart_stripe_sync_assert( 'canceled' === $ppcart_test_post_meta[707]['_ppcart_status'], 'subscription sync still stores the mapped status meta' );

$invoice_line_method = new ReflectionMethod( 'PPCart_Stripe_Sync', 'find_invoice_product_line' );
$invoice_line_method->setAccessible( true );
$invoice = (object) array(
    'lines' => (object) array(
        'data' => array(
            (object) array(
                'metadata' => (object) array(
                    'ppcart_product_id' => 999,
                ),
            ),
            (object) array(
                'subscription_item' => 'si_expected',
                'metadata'          => (object) array(
                    'ppcart_product_id' => 123,
                ),
            ),
        ),
    ),
);
$selected_line = $invoice_line_method->invoke( null, $invoice );
ppcart_stripe_sync_assert( 'si_expected' === $selected_line->subscription_item, 'invoice sync selects the subscription product line' );

$invoice_without_subscription_item = (object) array(
    'lines' => (object) array(
        'data' => array(
            (object) array(
                'metadata' => (object) array(
                    'ppcart_product_id' => 123,
                ),
            ),
        ),
    ),
);
ppcart_stripe_sync_assert( false === $invoice_line_method->invoke( null, $invoice_without_subscription_item ), 'invoice sync ignores non-subscription product lines' );

$ppcart_test_records['subscriptions'][808] = array(
    'id'                 => 808,
    'pay_method'         => 'stripe',
    'subscription_id'    => 'sub_paused_invoice',
    'status'             => 'paused',
    'sub_status'         => 'active',
    'sub_next_bill_date' => '',
);
$paused_invoice = (object) array(
    'lines' => (object) array(
        'data' => array(
            (object) array(
                'subscription_item' => 'si_paused',
                'metadata'          => (object) array(
                    'ppcart_product_id' => 123,
                    'origin'        => 'https://example.test',
                ),
                'period'            => (object) array(
                    'end' => 3333333333,
                ),
            ),
        ),
    ),
);
PPCart_Stripe_Sync::sync_invoice_resource( $paused_invoice, new PPCart_Subscription( 808 ) );
ppcart_stripe_sync_assert( '' === $ppcart_test_records['subscriptions'][808]['sub_next_bill_date'], 'invoice sync does not restore next bill date for paused subscriptions' );

PPCart_Stripe_Sync::reconcile_stale_subscriptions();
ppcart_stripe_sync_assert( 50 === $ppcart_test_get_posts_args['posts_per_page'], 'reconciliation query caps each batch to 50 records' );
ppcart_stripe_sync_assert( isset( $ppcart_test_get_posts_args['meta_query'][1]['relation'] ), 'reconciliation query filters stale sync metadata before fetching records' );
ppcart_stripe_sync_assert( isset( $ppcart_test_get_posts_args['meta_query'][2]['relation'] ), 'reconciliation query filters backoff metadata before fetching records' );

echo PHP_EOL . 'Stripe sync integration smoke test passed.' . PHP_EOL;

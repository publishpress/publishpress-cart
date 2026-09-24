<?php

namespace unit\SubscriptionCancellation;

use Codeception\Test\Unit;
use Exception;
use Tests\Support\WordPressStubContext;
use UnitTester;

class SubscriptionCancellationTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before(): void
    {
        WordPressStubContext::setState('post_meta', array());

        WordPressStubContext::set('add_action', function () {
            return true;
        });
        WordPressStubContext::set('add_filter', function () {
            return true;
        });
        WordPressStubContext::set('add_shortcode', function () {
            return true;
        });
        WordPressStubContext::set(
            'apply_filters',
            function ($hook, $value) {
                return $value;
            }
        );
        WordPressStubContext::set(
            'get_post_meta',
            function ($post_id, $key, $single = false) {
                $meta = WordPressStubContext::getState('post_meta', array());

                if (! isset($meta[$post_id][$key])) {
                    return $single ? '' : array();
                }

                return $single ? $meta[$post_id][$key] : array($meta[$post_id][$key]);
            }
        );
        WordPressStubContext::set(
            'update_post_meta',
            function ($post_id, $key, $value) {
                $meta = WordPressStubContext::getState('post_meta', array());

                if (! isset($meta[$post_id])) {
                    $meta[$post_id] = array();
                }

                $meta[$post_id][$key] = $value;
                WordPressStubContext::setState('post_meta', $meta);

                return true;
            }
        );
        WordPressStubContext::set('wp_rand', function () {
            return 1234;
        });
        WordPressStubContext::set('ppcart_is_pro', function () {
            return false;
        });

        if (! function_exists('ppcart_issue_stripe_subscription_cancellation_refund')) {
            require_once PPCART_PLUGIN_ROOT . 'includes/functions.php';
        }

        if (! class_exists('PPCart_Stripe_Sync', false)) {
            require __DIR__ . '/PPCart_Stripe_SyncStub.php';
        }
    }

    protected function _after(): void
    {
        WordPressStubContext::clear();
        parent::_after();
    }

    public function test_UT_167_cancellation_refund_returns_the_created_stripe_credit_note(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        $credit_note = ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame('cn_test', $credit_note->id);
    }

    public function test_UT_168_cancellation_refund_resolves_the_latest_invoice_through_stripe(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame(1, $stripe->invoices->retrieve_count);
    }

    public function test_UT_169_credit_note_amount_subtracts_prior_post_payment_credit_notes(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame(1500, $stripe->creditNotes->last_args['amount']);
    }

    public function test_UT_170_credit_note_refund_amount_equals_the_credited_amount(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame(1500, $stripe->creditNotes->last_args['refund_amount']);
    }

    public function test_UT_171_credit_note_targets_the_subscription_s_latest_invoice(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame('in_test', $stripe->creditNotes->last_args['invoice']);
    }

    public function test_UT_172_credit_note_carries_the_local_subscription_id_in_metadata(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame('321', $stripe->creditNotes->last_args['metadata']['ppcart_subscription_id']);
    }

    public function test_UT_173_credit_note_creation_is_logged_on_the_subscription_record(): void
    {
        WordPressStubContext::setState('post_meta', array());

        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $meta = WordPressStubContext::getState('post_meta', array());
        $this->assertArrayHasKey(321, $meta);
        $this->assertArrayHasKey('_ppcart_order_log', $meta[321]);
    }

    public function test_UT_174_credit_note_refund_id_is_resolved_through_stripe(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame(1, $stripe->refunds->retrieve_count);
    }

    public function test_UT_175_credit_note_refund_is_synced_into_the_local_record(): void
    {
        \PPCart_Stripe_Sync::$synced_refund_ids = array();

        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_test',
                'amount_paid' => 2000,
                'post_payment_credit_notes_amount' => 500,
            )
        );

        ppcart_issue_stripe_subscription_cancellation_refund(
            (object) array(
                'id' => 321,
                'subscription_id' => 'sub_test',
            ),
            (object) array(
                'id' => 'sub_test',
                'latest_invoice' => 'in_test',
            ),
            $stripe
        );

        $this->assertSame(array('re_test'), \PPCart_Stripe_Sync::$synced_refund_ids);
    }

    public function test_UT_176_cancellation_refund_rejects_invoices_with_nothing_left_to_refund(): void
    {
        $stripe = $this->createStripeClient(
            (object) array(
                'id' => 'in_empty',
                'amount_paid' => 1000,
                'post_payment_credit_notes_amount' => 1000,
            )
        );

        $exception_thrown = false;

        try {
            ppcart_issue_stripe_subscription_cancellation_refund(
                (object) array(
                    'id' => 321,
                    'subscription_id' => 'sub_test',
                ),
                (object) array(
                    'id' => 'sub_empty',
                    'latest_invoice' => 'in_empty',
                ),
                $stripe
            );
        } catch (Exception $e) {
            $exception_thrown = false !== strpos($e->getMessage(), 'no refundable paid amount');
        }

        $this->assertTrue($exception_thrown);
    }

    public function test_UT_177_plain_cancellation_success_response_stays_ok(): void
    {
        $this->assertSame('OK', ppcart_get_subscription_cancel_success_response());
    }

    public function test_UT_178_refund_failure_response_keeps_cancellation_success_and_url_encodes_the_message(): void
    {
        $this->assertSame(
            'OK_REFUND_FAILED|Couldn%27t%20process%20refund',
            ppcart_get_subscription_cancel_success_response("Couldn't process refund")
        );
    }

    /**
     * @param object $invoice
     * @return SubscriptionCancellationStripeClientStub
     */
    private function createStripeClient($invoice)
    {
        return new SubscriptionCancellationStripeClientStub($invoice);
    }
}

class SubscriptionCancellationInvoiceServiceStub
{
    /**
     * @var object
     */
    public $invoice;

    /**
     * @var int
     */
    public $retrieve_count = 0;

    /**
     * @param object $invoice
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @param string $invoice_id
     * @return object
     */
    public function retrieve($invoice_id)
    {
        $this->retrieve_count++;
        $this->invoice->id = $invoice_id;

        return $this->invoice;
    }
}

class SubscriptionCancellationCreditNoteServiceStub
{
    /**
     * @var array<string, mixed>
     */
    public $last_args = array();

    /**
     * @param array<string, mixed> $args
     * @return object
     */
    public function create($args)
    {
        $this->last_args = $args;

        return (object) array(
            'id' => 'cn_test',
            'refund' => 're_test',
        );
    }
}

class SubscriptionCancellationRefundServiceStub
{
    /**
     * @var int
     */
    public $retrieve_count = 0;

    /**
     * @param string $refund_id
     * @return object
     */
    public function retrieve($refund_id)
    {
        $this->retrieve_count++;

        return (object) array(
            'id' => $refund_id,
        );
    }
}

class SubscriptionCancellationStripeClientStub
{
    /**
     * @var SubscriptionCancellationInvoiceServiceStub
     */
    public $invoices;

    /**
     * @var SubscriptionCancellationCreditNoteServiceStub
     */
    public $creditNotes;

    /**
     * @var SubscriptionCancellationRefundServiceStub
     */
    public $refunds;

    /**
     * @param object $invoice
     */
    public function __construct($invoice)
    {
        $this->invoices = new SubscriptionCancellationInvoiceServiceStub($invoice);
        $this->creditNotes = new SubscriptionCancellationCreditNoteServiceStub();
        $this->refunds = new SubscriptionCancellationRefundServiceStub();
    }
}

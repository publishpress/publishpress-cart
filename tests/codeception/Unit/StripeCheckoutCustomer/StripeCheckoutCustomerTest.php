<?php

namespace unit\StripeCheckoutCustomer;

use Codeception\Test\Unit;
use PPCart_Stripe_Checkout_Customer;
use Tests\Support\WordPressStubContext;
use UnitTester;

require_once PPCART_PLUGIN_ROOT . 'includes/stripe/class-ppcart-stripe-checkout-customer.php';
require_once PPCART_PLUGIN_ROOT . 'public/controllers/payment/traits/trait-ppcart-public-payment-customer.php';

/**
 * A guest must not get or change another person's Stripe customer, and an
 * upsell must charge only the payment method that paid the parent order.
 */
class StripeCheckoutCustomerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /** @var int */
    private $currentUserId = 0;

    /** @var array<int, array<string, mixed>> */
    private $userMeta = [];

    /** @var array<int, array<string, mixed>> */
    private $postMeta = [];

    /** @var array<string, mixed> */
    private $transients = [];

    protected function _before(): void
    {
        WordPressStubContext::clear();
        $this->currentUserId = 0;
        $this->userMeta      = [];
        $this->postMeta      = [];
        $this->transients    = [];

        if (! class_exists('WP_Error', false)) {
            class_alias(\Tests\Support\WPError::class, 'WP_Error');
        }

        require_once PPCART_PLUGIN_ROOT . 'includes/stripe/class-ppcart-stripe-checkout-customer.php';
        require_once PPCART_PLUGIN_ROOT . 'public/controllers/payment/traits/trait-ppcart-public-payment-customer.php';

        WordPressStubContext::set('get_current_user_id', function () {
            return $this->currentUserId;
        });
        WordPressStubContext::set('get_user_meta', function ($user_id, $key = '', $single = false) {
            return $this->userMeta[ (int) $user_id ][ $key ] ?? '';
        });
        WordPressStubContext::set('update_user_meta', function ($user_id, $key, $value) {
            $this->userMeta[ (int) $user_id ][ $key ] = $value;
            return true;
        });
        WordPressStubContext::set('get_post_meta', function ($post_id, $key = '', $single = false) {
            return $this->postMeta[ (int) $post_id ][ $key ] ?? '';
        });
        WordPressStubContext::set('update_post_meta', function ($post_id, $key, $value) {
            $this->postMeta[ (int) $post_id ][ $key ] = $value;
            return true;
        });
        WordPressStubContext::set('get_transient', function ($key) {
            return $this->transients[ $key ] ?? false;
        });
        WordPressStubContext::set('set_transient', function ($key, $value) {
            $this->transients[ $key ] = $value;
            return true;
        });

        $GLOBALS['ppcart_stripe'] = [ 'mode' => 'test', 'sk' => 'sk_test_unit' ];
    }

    protected function _after(): void
    {
        unset($GLOBALS['ppcart_stripe']);
        WordPressStubContext::clear();
        parent::_after();
    }

    /**
     * @test-id UT-364
     */
    public function test_UT_364_guest_with_someone_elses_email_gets_a_new_customer(): void
    {
        $stripe  = new FakeStripe();
        $checkout = new CustomerTraitHost();

        $this->assertSame('', $checkout->cached('victim@example.com', 'test'));

        $customer = $checkout->getOrCreate($stripe, [ 'email' => 'victim@example.com', 'name' => 'Eve' ], 'victim@example.com');

        $this->assertSame('cus_new1', $customer->id);
        $this->assertSame([], $stripe->customersService->listCalls, 'Customers must never be looked up by the posted email.');
        $this->assertSame([ [ 'email' => 'victim@example.com', 'name' => 'Eve' ] ], $stripe->customersService->createCalls);
        $this->assertSame([], $this->userMeta, 'A guest customer is not stored on any account.');
    }

    /**
     * @test-id UT-364
     */
    public function test_UT_364_empty_email_is_not_sent_to_stripe(): void
    {
        $stripe = new FakeStripe();

        PPCart_Stripe_Checkout_Customer::create_customer($stripe, [ 'email' => '', 'phone' => ' ', 'name' => ' ' ], 'test');

        $this->assertSame([ [ 'name' => ' ' ] ], $stripe->customersService->createCalls);
    }

    /**
     * @test-id UT-365
     */
    public function test_UT_365_logged_in_user_reuses_only_their_own_live_customer(): void
    {
        $this->currentUserId = 7;
        $stripe              = new FakeStripe();

        $first = PPCart_Stripe_Checkout_Customer::create_customer($stripe, [ 'email' => 'me@example.com' ], 'test');
        $this->assertSame('cus_new1', $first->id);
        $this->assertSame('cus_new1', PPCart_Stripe_Checkout_Customer::get_live_current_user_customer_id($stripe, 'test'));
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_current_user_customer_id('live'), 'Stored per gateway mode.');

        $this->currentUserId = 8;
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_current_user_customer_id('test'), 'Another user does not get it.');

        $this->currentUserId = 7;
        $stripe->customersService->deleted['cus_new1'] = true;
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_live_current_user_customer_id($stripe, 'test'), 'A deleted customer is not reused.');
    }

    /**
     * @test-id UT-366
     */
    public function test_UT_366_checkout_ref_resolves_only_for_the_same_visitor_mode_and_intent(): void
    {
        $ref = PPCart_Stripe_Checkout_Customer::create_ref('cus_guest', 'pi_123', 'test', true);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ref);
        $this->assertSame(
            [ 'customer_id' => 'cus_guest', 'intent_id' => 'pi_123', 'is_new' => true ],
            PPCart_Stripe_Checkout_Customer::read_ref($ref, 'test')
        );
        $this->assertNull(PPCart_Stripe_Checkout_Customer::read_ref($ref, 'live'));
        $this->assertNull(PPCart_Stripe_Checkout_Customer::read_ref('cus_victim', 'test'), 'A posted customer ID is not a reference.');
        $this->assertNull(PPCart_Stripe_Checkout_Customer::read_ref(str_repeat('a', 64), 'test'));

        $this->assertSame('cus_guest', PPCart_Stripe_Checkout_Customer::customer_id_for_order($ref, 'pi_123', 'test'));
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::customer_id_for_order($ref, 'pi_other', 'test'));
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::customer_id_for_order('', 'pi_123', 'test'));

        $this->currentUserId = 3;
        $this->assertNull(PPCart_Stripe_Checkout_Customer::read_ref($ref, 'test'), 'Another WordPress user cannot use the reference.');

        $this->assertSame('', PPCart_Stripe_Checkout_Customer::create_ref('not-a-customer', '', 'test', true));
    }

    /**
     * @test-id UT-367
     */
    public function test_UT_367_subscription_payment_method_from_foreign_setup_intent_is_refused(): void
    {
        $stripe = new FakeStripe();
        $stripe->setupIntentsService->items['seti_1'] = (object) [
            'status'         => 'succeeded',
            'customer'       => 'cus_victim',
            'payment_method' => 'pm_attacker',
            'metadata'       => (object) [ 'origin' => 'https://shop.test' ],
        ];

        $result = PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method(
            $stripe,
            [ 'customer_id' => 'cus_guest', 'intent_id' => 'seti_1', 'is_new' => true ],
            'pm_attacker',
            'https://shop.test'
        );

        $this->assertTrue(is_wp_error($result));
    }

    /**
     * @test-id UT-367
     */
    public function test_UT_367_subscription_uses_the_setup_intent_payment_method_not_the_posted_one(): void
    {
        $stripe = new FakeStripe();
        $stripe->setupIntentsService->items['seti_1'] = (object) [
            'status'         => 'succeeded',
            'customer'       => 'cus_guest',
            'payment_method' => 'pm_confirmed',
            'metadata'       => (object) [ 'origin' => 'https://shop.test' ],
        ];
        $binding = [ 'customer_id' => 'cus_guest', 'intent_id' => 'seti_1', 'is_new' => true ];

        $this->assertSame(
            'pm_confirmed',
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'pm_victim', 'https://shop.test')
        );

        $stripe->setupIntentsService->items['seti_1']->status = 'requires_payment_method';
        $this->assertTrue(is_wp_error(
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'pm_confirmed', 'https://shop.test')
        ));

        $stripe->setupIntentsService->items['seti_1']->status   = 'succeeded';
        $stripe->setupIntentsService->items['seti_1']->metadata = (object) [ 'origin' => 'https://other.test' ];
        $this->assertTrue(is_wp_error(
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'pm_confirmed', 'https://shop.test')
        ));
    }

    /**
     * @test-id UT-367
     */
    public function test_UT_367_posted_payment_method_of_another_customer_is_not_attached(): void
    {
        $stripe  = new FakeStripe();
        $victim  = new FakePaymentMethod('pm_victim', 'cus_victim');
        $fresh   = new FakePaymentMethod('pm_fresh', null);
        $stripe->paymentMethodsService->items = [ 'pm_victim' => $victim, 'pm_fresh' => $fresh ];
        $binding = [ 'customer_id' => 'cus_guest', 'intent_id' => '', 'is_new' => true ];

        $this->assertTrue(is_wp_error(
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'pm_victim', 'https://shop.test')
        ));
        $this->assertSame([], $victim->attachCalls);

        $this->assertSame(
            'pm_fresh',
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'pm_fresh', 'https://shop.test')
        );
        $this->assertSame([ [ 'customer' => 'cus_guest' ] ], $fresh->attachCalls);

        $this->assertTrue(is_wp_error(
            PPCart_Stripe_Checkout_Customer::resolve_subscription_payment_method($stripe, $binding, 'cus_victim', 'https://shop.test')
        ));
    }

    /**
     * @test-id UT-368
     */
    public function test_UT_368_invoice_of_another_customer_is_refused(): void
    {
        $this->assertFalse(PPCart_Stripe_Checkout_Customer::invoice_belongs_to_customer((object) [ 'customer' => 'cus_victim' ], 'cus_guest'));
        $this->assertFalse(PPCart_Stripe_Checkout_Customer::invoice_belongs_to_customer((object) [], 'cus_guest'));
        $this->assertFalse(PPCart_Stripe_Checkout_Customer::invoice_belongs_to_customer((object) [ 'customer' => '' ], ''));
        $this->assertTrue(PPCart_Stripe_Checkout_Customer::invoice_belongs_to_customer((object) [ 'customer' => 'cus_guest' ], 'cus_guest'));
        $this->assertTrue(PPCart_Stripe_Checkout_Customer::invoice_belongs_to_customer(
            (object) [ 'customer' => (object) [ 'id' => 'cus_guest' ] ],
            'cus_guest'
        ));
    }

    /**
     * @test-id UT-369
     */
    public function test_UT_369_upsell_uses_the_parent_payment_method_not_the_customer_default(): void
    {
        $stripe = new FakeStripe();
        $this->postMeta[10]['_ppcart_transaction_id'] = 'pi_parent';
        $stripe->paymentIntentsService->items['pi_parent'] = (object) [
            'status'         => 'succeeded',
            'customer'       => 'cus_guest',
            'payment_method' => 'pm_parent',
        ];

        $this->assertSame('pm_parent', PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, 10, 'cus_guest'));
        $this->assertSame('pm_parent', $this->postMeta[10]['_ppcart_stripe_payment_method_id'], 'The method is recorded on the parent order.');
        $this->assertSame(0, $stripe->customersService->retrieveCount, 'The customer default is never read.');
        $this->assertSame(0, $stripe->paymentMethodsService->listCount, 'Saved cards are never listed.');

        unset($stripe->paymentIntentsService->items['pi_parent']);
        $this->assertSame('pm_parent', PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, 10, 'cus_guest'), 'Stored method is used.');
    }

    /**
     * @test-id UT-369
     */
    public function test_UT_369_upsell_refuses_when_parent_payment_is_not_the_customers(): void
    {
        $stripe = new FakeStripe();
        $this->postMeta[11]['_ppcart_transaction_id'] = 'pi_attacker';
        $stripe->paymentIntentsService->items['pi_attacker'] = (object) [
            'status'         => 'succeeded',
            'customer'       => 'cus_attacker',
            'payment_method' => 'pm_attacker',
        ];

        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, 11, 'cus_victim'));

        $this->postMeta[12]['_ppcart_transaction_id'] = 'pi_unpaid';
        $stripe->paymentIntentsService->items['pi_unpaid'] = (object) [
            'status'         => 'requires_payment_method',
            'customer'       => 'cus_guest',
            'payment_method' => 'pm_x',
        ];
        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, 12, 'cus_guest'));

        $this->assertSame('', PPCart_Stripe_Checkout_Customer::get_order_payment_method_id($stripe, 13, 'cus_guest'), 'No parent payment, no charge.');
        $this->assertSame(0, $stripe->customersService->retrieveCount);
        $this->assertSame(0, $stripe->paymentMethodsService->listCount);
        $this->assertArrayNotHasKey('_ppcart_stripe_payment_method_id', $this->postMeta[11] ?? []);
    }
}

/**
 * Exposes the private customer methods of the payment controller trait.
 */
class CustomerTraitHost
{
    use \PPCart_Public_Payment_Customer_Trait;

    public function cached($email, $mode)
    {
        return $this->get_cached_stripe_customer_id($email, $mode);
    }

    public function getOrCreate($stripe, $args, $email)
    {
        return $this->get_or_create_stripe_customer($stripe, $args, $email);
    }
}

class FakeStripe
{
    public $customers;
    public $setupIntents;
    public $paymentIntents;
    public $paymentMethods;
    public $charges;
    public $customersService;
    public $setupIntentsService;
    public $paymentIntentsService;
    public $paymentMethodsService;

    public function __construct()
    {
        $this->customers      = $this->customersService = new FakeCustomers();
        $this->setupIntents   = $this->setupIntentsService = new FakeRetrievable();
        $this->paymentIntents = $this->paymentIntentsService = new FakeRetrievable();
        $this->paymentMethods = $this->paymentMethodsService = new FakePaymentMethods();
        $this->charges        = new FakeRetrievable();
    }
}

class FakeCustomers
{
    public $createCalls = [];
    public $listCalls = [];
    public $retrieveCount = 0;
    public $deleted = [];
    private $next = 0;

    public function create($args)
    {
        $this->createCalls[] = $args;
        ++$this->next;

        return (object) [ 'id' => 'cus_new' . $this->next ];
    }

    public function all($args)
    {
        $this->listCalls[] = $args;

        return (object) [ 'data' => [ (object) [ 'id' => 'cus_victim' ] ] ];
    }

    public function retrieve($id)
    {
        ++$this->retrieveCount;

        return (object) [ 'id' => $id, 'deleted' => ! empty($this->deleted[ $id ]) ];
    }
}

class FakeRetrievable
{
    public $items = [];

    public function retrieve($id)
    {
        if (! isset($this->items[ $id ])) {
            throw new \RuntimeException('No such object: ' . $id);
        }

        return $this->items[ $id ];
    }
}

class FakePaymentMethods extends FakeRetrievable
{
    public $listCount = 0;

    public function all($args)
    {
        ++$this->listCount;

        return (object) [ 'data' => [ (object) [ 'id' => 'pm_saved_card' ] ] ];
    }
}

class FakePaymentMethod
{
    public $id;
    public $customer;
    public $attachCalls = [];

    public function __construct($id, $customer)
    {
        $this->id       = $id;
        $this->customer = $customer;
    }

    public function attach($args)
    {
        $this->attachCalls[] = $args;
        $this->customer      = $args['customer'];

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration\Checkout;

use lucatume\WPBrowser\TestCase\WPTestCase;
use WPDieException;

/**
 * Checkout offers a login when the buyer's email already has an account
 * (publishpress/publishpress-cart#1289).
 */
class ExistingAccountCheckoutTest extends WPTestCase
{
    private const MEMBER_EMAIL = 'member1289@example.com';
    private const MEMBER_PASSWORD = 'Old-pass-1289';

    /**
     * @var int
     */
    private static $addressCounter = 0;

    /**
     * @var int
     */
    private $productId = 0;

    /**
     * @var int
     */
    private $memberId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('ppcart_do_field')) {
            require_once PPCART_PLUGIN_ROOT . 'public/templates/template-functions.php';
        }

        add_filter('send_auth_cookies', '__return_false');
        wp_set_current_user(0);
        update_option('_ppcart_existing_account_checkout', 'login');

        // A fresh address per test keeps the rate limit counters of other tests out of the way.
        self::$addressCounter++;
        $_SERVER['REMOTE_ADDR'] = '198.51.100.' . self::$addressCounter;

        $this->productId = $this->createProduct([['services' => 'create user', 'user_role' => 'subscriber']]);
        $this->memberId = (int) $this->factory()->user->create([
            'user_email' => self::MEMBER_EMAIL,
            'user_pass'  => self::MEMBER_PASSWORD,
            'role'       => 'subscriber',
        ]);
    }

    protected function tearDown(): void
    {
        remove_filter('send_auth_cookies', '__return_false');
        remove_all_filters('ppcart_existing_account_rate_limit');
        delete_option('_ppcart_existing_account_checkout');
        wp_set_current_user(0);
        $_POST = [];
        unset($GLOBALS['ppcart_product']);

        parent::tearDown();
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_check_email_reports_only_whether_the_email_has_an_account(): void
    {
        $response = $this->checkEmail(self::MEMBER_EMAIL);

        $this->assertTrue($response['success']);
        $this->assertSame(['exists' => true], $response['data']);

        $this->assertSame(['exists' => false], $this->checkEmail('new1289@example.com')['data']);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_check_email_answers_false_when_the_setting_is_off(): void
    {
        update_option('_ppcart_existing_account_checkout', '');

        $this->assertSame(['exists' => false], $this->checkEmail(self::MEMBER_EMAIL)['data']);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_check_email_answers_false_for_a_product_that_creates_no_account(): void
    {
        $plainProduct = $this->createProduct([['services' => 'mailchimp']]);

        $this->assertSame(['exists' => false], $this->checkEmail(self::MEMBER_EMAIL, $plainProduct)['data']);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_a_password_custom_field_makes_the_product_an_account_product(): void
    {
        $product = $this->createProduct([], [['field_id' => 'pass', 'field_type' => 'password', 'field_label' => 'Password']]);

        $this->assertSame(['exists' => true], $this->checkEmail(self::MEMBER_EMAIL, $product)['data']);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_check_email_answers_false_over_the_rate_limit(): void
    {
        add_filter('ppcart_existing_account_rate_limit', static fn() => 1);

        $this->assertSame(['exists' => true], $this->checkEmail(self::MEMBER_EMAIL)['data']);
        $this->assertSame(['exists' => false], $this->checkEmail(self::MEMBER_EMAIL)['data']);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_requests_without_a_valid_nonce_are_rejected(): void
    {
        $check = $this->runAjax('ppcart_ajax_check_email', ['email' => self::MEMBER_EMAIL, 'ppcart-nonce' => 'not-a-nonce']);
        $login = $this->runAjax('ppcart_ajax_checkout_login', ['email' => self::MEMBER_EMAIL, 'password' => self::MEMBER_PASSWORD]);

        $this->assertFalse($check['success']);
        $this->assertArrayNotHasKey('exists', (array) $check['data']);
        $this->assertFalse($login['success']);
        $this->assertSame(0, get_current_user_id());
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_checkout_login_logs_the_buyer_in(): void
    {
        $response = $this->login(self::MEMBER_EMAIL, self::MEMBER_PASSWORD);

        $this->assertTrue($response['success']);
        $this->assertSame($this->memberId, get_current_user_id());
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_checkout_login_failures_share_one_message_and_run_core_login_failure_hooks(): void
    {
        $failedLogins = [];
        $recordFailure = static function ($username) use (&$failedLogins) {
            $failedLogins[] = $username;
        };
        add_action('wp_login_failed', $recordFailure);

        $wrongPassword = $this->login(self::MEMBER_EMAIL, 'not-the-password');
        $unknownEmail = $this->login('nobody1289@example.com', 'not-the-password');
        remove_action('wp_login_failed', $recordFailure);

        $this->assertFalse($wrongPassword['success']);
        $this->assertFalse($unknownEmail['success']);
        $this->assertSame($wrongPassword['data'], $unknownEmail['data']);
        $this->assertSame([self::MEMBER_EMAIL, 'nobody1289@example.com'], $failedLogins);
        $this->assertSame(0, get_current_user_id());
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_checkout_login_stops_over_the_rate_limit(): void
    {
        add_filter('ppcart_existing_account_rate_limit', static fn() => 1);

        $this->login(self::MEMBER_EMAIL, 'not-the-password');
        $response = $this->login(self::MEMBER_EMAIL, self::MEMBER_PASSWORD);

        $this->assertFalse($response['success']);
        $this->assertSame(0, get_current_user_id());
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_account_fields_are_not_required_when_the_email_has_an_account(): void
    {
        $product = ppcart_setup_product($this->createProduct([], [
            ['field_id' => 'pass', 'field_type' => 'password', 'field_label' => 'Password'],
            ['field_id' => 'login', 'field_type' => 'text', 'field_label' => 'Username', 'field_username' => '1'],
        ]));
        $required = ['email', ['pass', 'login', 'company_size']];

        $_POST['email'] = self::MEMBER_EMAIL;
        $this->assertSame(['email', ['company_size']], apply_filters('ppcart_validate_custom_fields', $required, $product));

        $_POST['email'] = 'new1289@example.com';
        $this->assertSame($required, apply_filters('ppcart_validate_custom_fields', $required, $product));

        update_option('_ppcart_existing_account_checkout', '');
        $_POST['email'] = self::MEMBER_EMAIL;
        $this->assertSame($required, apply_filters('ppcart_validate_custom_fields', $required, $product));
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_checkout_for_an_existing_account_keeps_its_password_and_skips_auto_login(): void
    {
        $productId = $this->createProduct([], [['field_id' => 'pass', 'field_type' => 'password', 'field_label' => 'Password']]);
        $order = new \PPCart_Order();
        $order->product_name = 'Existing account order';
        $order->status = 'paid';
        $orderId = (int) $order->store();
        $_POST = ['ppcart_product_id' => (string) $productId, 'ppcart_custom_fields' => ['pass' => 'New-pass-1289']];

        $userId = ppcart_create_user($orderId, self::MEMBER_EMAIL, 'Member', 'Buyer', 'subscriber', false);
        ppcart_update_post_meta($orderId, 'auto_login', 1);

        $this->assertSame($this->memberId, $userId);
        $this->assertSame($this->memberId, (int) ppcart_get_post_meta($orderId, 'user_account', true));
        $this->assertTrue(wp_check_password(self::MEMBER_PASSWORD, get_userdata($this->memberId)->user_pass, $this->memberId));
        $this->assertFalse(ppcart_maybe_auto_login_order_buyer($orderId));
        $this->assertSame(0, get_current_user_id());
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_checkout_form_marks_the_email_field_and_shows_the_notice_markup(): void
    {
        $GLOBALS['ppcart_product'] = ppcart_setup_product($this->productId);
        $html = ppcart_kses_frontend_html($this->renderContactFields());

        $this->assertMatchesRegularExpression('/<input[^>]*id="email"[^>]*data-ppcart-account-check="1"/', $html);
        $this->assertMatchesRegularExpression('/<div class="ppcart-existing-account" aria-live="polite"/', $html);
        $this->assertStringContainsString('ppcart-existing-account-login-toggle', $html);
        $this->assertStringContainsString('ppcart-existing-account-reset', $html);

        update_option('_ppcart_existing_account_checkout', '');
        $off = $this->renderContactFields();

        $this->assertStringNotContainsString('data-ppcart-account-check', $off);
        $this->assertStringNotContainsString('ppcart-existing-account', $off);
    }

    /**
     * @test-id IT-434
     */
    public function test_IT_434_account_fields_carry_the_contract_attribute(): void
    {
        $GLOBALS['ppcart_product'] = ppcart_setup_product($this->createProduct([], [
            ['field_id' => 'login', 'field_type' => 'text', 'field_label' => 'Username', 'field_username' => '1'],
        ]));

        $username = ppcart_kses_frontend_html($this->renderField(['name' => 'ppcart_custom_fields[login]', 'label' => 'Username']));
        $password = ppcart_kses_frontend_html($this->renderField(['name' => 'ppcart_custom_fields[pass]', 'label' => 'Password', 'type' => 'password']));
        $flagged = $this->renderField(['name' => 'ppcart_custom_fields[pin]', 'label' => 'PIN', 'account_field' => true]);
        $other = $this->renderField(['name' => 'ppcart_custom_fields[company]', 'label' => 'Company']);

        $this->assertStringContainsString('data-ppcart-account-field="1"', $username);
        $this->assertStringContainsString('data-ppcart-account-field="1"', $password);
        $this->assertStringContainsString('data-ppcart-account-field="1"', $flagged);
        $this->assertStringNotContainsString('data-ppcart-account-field', $other);
    }

    /**
     * @param array<int, array<string, string>> $integrations
     * @param array<int, array<string, string>> $customFields
     */
    private function createProduct(array $integrations, array $customFields = []): int
    {
        $productId = (int) wp_insert_post([
            'post_type'   => ppcart_live_post_type('product'),
            'post_status' => 'publish',
            'post_title'  => 'Existing account product',
        ]);
        if ($integrations) {
            ppcart_update_post_meta($productId, 'integrations', $integrations);
        }
        if ($customFields) {
            ppcart_update_post_meta($productId, 'custom_fields', $customFields);
        }

        return $productId;
    }

    private function checkEmail(string $email, int $productId = 0): array
    {
        return $this->runAjax('ppcart_ajax_check_email', [
            'email'             => $email,
            'ppcart_product_id' => (string) ($productId ?: $this->productId),
            'ppcart-nonce'      => wp_create_nonce('ppcart_purchase_nonce'),
        ]);
    }

    private function login(string $email, string $password): array
    {
        return $this->runAjax('ppcart_ajax_checkout_login', [
            'email'             => $email,
            'password'          => $password,
            'ppcart_product_id' => (string) $this->productId,
            'ppcart-nonce'      => wp_create_nonce('ppcart_purchase_nonce'),
        ]);
    }

    /**
     * Run an AJAX handler that ends with wp_send_json_*() and decode its response.
     *
     * @param callable-string       $handler Handler function name.
     * @param array<string, string> $post    Request body.
     * @return array{success: bool, data: mixed}
     */
    private function runAjax(string $handler, array $post): array
    {
        $_POST = $post;
        $dieHandler = static function () {
            return static function ($message = '') {
                throw new WPDieException(is_string($message) ? $message : '');
            };
        };
        add_filter('wp_die_ajax_handler', $dieHandler, 999);
        add_filter('wp_die_handler', $dieHandler, 999);
        add_filter('wp_doing_ajax', '__return_true');

        ob_start();
        try {
            $handler();
        } catch (WPDieException $e) {
        } finally {
            $output = (string) ob_get_clean();
            remove_filter('wp_die_ajax_handler', $dieHandler, 999);
            remove_filter('wp_die_handler', $dieHandler, 999);
            remove_filter('wp_doing_ajax', '__return_true');
            $_POST = [];
        }

        $response = json_decode($output, true);
        $this->assertIsArray($response, 'handler sent a JSON response');

        return $response + ['data' => null];
    }

    private function renderContactFields(): string
    {
        ob_start();
        ppcart_do_checkoutform_fields($this->productId, 'show');

        return (string) ob_get_clean();
    }

    private function renderField(array $args): string
    {
        ob_start();
        ppcart_do_field($args);

        return (string) ob_get_clean();
    }
}

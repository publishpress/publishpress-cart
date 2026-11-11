<?php

/**
 * Native manual order administration without checkout or payment impersonation.
 *
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;

class PPCart_Mcp_Order_Admin
{
    private $products;

    /** @since 1.1.0 @param PPCart_Product_Metaboxes $products Native provider. */
    public function __construct($products)
    {
        $this->products = $products;
    }

    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        return PPCart_Mcp_Payments::can_manage() && (new PPCart_Mcp_Records())->permitted('order');
    }

    /** @since 1.1.0 @return array */
    private function fields()
    {
        $fields = [];
        foreach (PPCart_Order::admin_customer_fields() as $key) {
            $fields[$key] = ['type' => 'string', 'maxLength' => 1000];
        }
        $fields['user_account'] = ['type' => 'integer', 'minimum' => 0];
        $fields['status'] = ['type' => 'string', 'enum' => [PPCart_Order::$pending_str, PPCart_Order::$paid_str,
            PPCart_Order::$completed_str, PPCart_Order::$failed_str, PPCart_Order::$uncollect_str, PPCart_Order::$refunded_str]];
        return $fields;
    }

    /** @since 1.1.0 @param string $operation Read/create/update. @return array */
    public function schema($operation)
    {
        $fields = ['id' => ['type' => 'integer', 'minimum' => 1]];
        $required = ['id'];
        if ('read' !== $operation) {
            $details = $this->fields();
            if ('create' === $operation) {
                $details['product_id'] = ['type' => 'integer', 'minimum' => 1];
                $details['plan_id'] = ['type' => 'string', 'minLength' => 1, 'maxLength' => 100];
                $details['quantity'] = ['type' => 'integer', 'minimum' => 1, 'maximum' => 10000];
                $fields = [];
                $required = [];
            } else {
                $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
                $required[] = 'revision';
            }
            $fields['changes'] = PPCart_Mcp_Input::schema($details, 'create' === $operation ? ['product_id', 'plan_id', 'email', 'firstname', 'lastname', 'user_account'] : []);
            $fields['dry_run'] = ['type' => 'boolean'];
            $required[] = 'changes';
        }
        return PPCart_Mcp_Input::schema($fields, $required);
    }

    /** @since 1.1.0 @param int $id Order. @return true|WP_Error */
    private function access($id)
    {
        if (
            ! $this->permitted() || ! ppcart_is_order_post_type(get_post_type($id))
            || ! current_user_can('read_post', $id) || ! current_user_can('edit_post', $id)
        ) {
            return $this->forbidden();
        }
        $order = new PPCart_Order($id);
        foreach (['product_id', 'subscription_id', 'order_parent'] as $key) {
            if ($order->$key && (! get_post((int) $order->$key) || ! current_user_can('read_post', (int) $order->$key) || ! current_user_can('edit_post', (int) $order->$key))) {
                return $this->forbidden();
            }
        }
        if ($order->user_account && (! current_user_can('list_users') || ! current_user_can('edit_user', (int) $order->user_account))) {
            return $this->forbidden();
        }
        return true;
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('read'));
        if (is_wp_error($input)) {
            return $input;
        }
        $valid = $this->access($input['id']);
        if (is_wp_error($valid)) {
            return $valid;
        }
        $order = new PPCart_Order($input['id']);
        $details = [];
        foreach ($this->fields() as $key => $schema) {
            $details[$key] = 'user_account' === $key ? (int) $order->$key : (string) $order->$key;
        }
        return ['id' => $input['id'], 'details' => (object) $details,
            'stripe_owned_status' => ppcart_is_stripe_backed_post($input['id']),
            'revision' => $this->revision($input['id'])];
    }

    /** @since 1.1.0 @param int $id Record. @return string */
    private function revision($id)
    {
        return hash_hmac('sha256', wp_json_encode([get_post($id), get_post_meta($id)]), wp_salt('nonce'));
    }

    /** @since 1.1.0 @param array $changes Patch. @return true|WP_Error */
    private function validate(array $changes)
    {
        if (isset($changes['email']) && ! is_email($changes['email'])) {
            return PPCart_Mcp_Input::error();
        }
        $existing_user = isset($changes['email']) ? email_exists($changes['email']) : 0;
        if ($existing_user && (! current_user_can('list_users') || ! current_user_can('edit_user', (int) $existing_user))) {
            return $this->forbidden();
        }
        if (! empty($changes['country']) && ! array_key_exists($changes['country'], ppcart_countries_list())) {
            return PPCart_Mcp_Input::error();
        }
        if (! empty($changes['user_account']) && (! get_userdata($changes['user_account']) || ! current_user_can('list_users') || ! current_user_can('edit_user', $changes['user_account']))) {
            return $this->forbidden();
        }
        return true;
    }

    /** @since 1.1.0 @param int $id Product. @return true|WP_Error */
    public function integrations($id)
    {
        $stored = ppcart_get_post_meta($id, 'integrations', true);
        if (empty($stored)) {
            return true;
        }
        $service = new PPCart_Mcp_Integrations($this->products);
        $read = $service->read(['id' => $id], true);
        if (is_wp_error($read)) {
            return $read;
        }
        $rows = array_map(static function ($row) {
            return ['row_id' => $row['row_id']];
        }, $read['rows']);
        return $service->update(['id' => $id, 'revision' => $read['revision'], 'rows' => $rows, 'dry_run' => true]);
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function update($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('update'));
        if (is_wp_error($input)) {
            return $input;
        }
        $before = $this->read(['id' => $input['id']]);
        if (is_wp_error($before)) {
            return $before;
        }
        if (! hash_equals($before['revision'], $input['revision'])) {
            return $this->stale();
        }
        $valid = $this->validate($input['changes']);
        if (is_wp_error($valid)) {
            return $valid;
        }
        if (isset($input['changes']['status']) && $before['stripe_owned_status']) {
            return new WP_Error('ppcart_mcp_locked', __('Stripe owns this status. Use native synchronization or payment actions.', 'publishpress-cart'));
        }
        $order = new PPCart_Order($input['id']);
        $order->apply_admin_customer_details($input['changes']);
        if (isset($input['changes']['user_account'])) {
            $order->user_account = $input['changes']['user_account'];
        }
        if (isset($input['changes']['status'])) {
            $valid = $this->integrations((int) $order->product_id);
            if (is_wp_error($valid)) {
                return $valid;
            }
            $order->status = $order->payment_status = $input['changes']['status'];
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'changes' => (object) $input['changes'], 'revision' => $before['revision']];
        }
        if (! hash_equals($input['revision'], $this->revision($input['id']))) {
            return $this->stale();
        }
        try {
            $order->store();
        } catch (Throwable $error) {
            return $this->uncertain();
        }
        $after = $this->read(['id' => $input['id']]);
        if (is_wp_error($after)) {
            return $this->uncertain();
        }
        foreach ($input['changes'] as $key => $value) {
            $expected = 'user_account' === $key ? $value : ('email' === $key ? sanitize_email($value) : sanitize_text_field($value));
            if ($after['details']->$key !== $expected) {
                return $this->uncertain();
            }
        }
        return $after;
    }

    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function create($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema('create'));
        if (is_wp_error($input)) {
            return $input;
        }
        $type = get_post_type_object(ppcart_live_post_type('order'));
        $changes = $input['changes'];
        if (
            ! $this->permitted() || ! $type || ! current_user_can($type->cap->create_posts)
            || ! current_user_can($type->cap->publish_posts) || ! (new PPCart_Mcp_Products($this->products))->can_edit($changes['product_id'])
        ) {
            return $this->forbidden();
        }
        $valid = $this->validate($changes);
        if (is_wp_error($valid)) {
            return $valid;
        }
        $plans = PPCart_Product_Metabox_Option_Sources::get_payment_plans(false, $changes['product_id']);
        if (! array_key_exists($changes['plan_id'], $plans)) {
            return PPCart_Mcp_Input::error();
        }
        $valid = $this->integrations($changes['product_id']);
        if (is_wp_error($valid)) {
            return $valid;
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'changes' => (object) $changes, 'payment_method' => PPCart_Order::$cod_gateway_str,
                'note' => __('Native pricing and tax calculation run on save. No payment is charged.', 'publishpress-cart')];
        }
        global $ppcart_product;
        $previous = $ppcart_product;
        try {
            $ppcart_product = ppcart_setup_product($changes['product_id']);
            $order = new PPCart_Order();
            $payload = $changes + ['first_name' => $changes['firstname'], 'last_name' => $changes['lastname'], 'vat-number' => $changes['vat_number'] ?? '',
                'ppcart_product_id' => $changes['product_id'], 'ppcart_product_option' => $changes['plan_id'], 'ppcart_qty' => $changes['quantity'] ?? 1, 'pay-method' => PPCart_Order::$cod_gateway_str];
            $order->load_from_data($payload);
            $order->apply_admin_customer_details($changes);
            $order->user_account = $changes['user_account'];
            $order->ip_address = '';
            $order->status = $order->payment_status = $changes['status'] ?? PPCart_Order::$pending_str;
            $order = apply_filters('ppcart_after_order_load_from_post', $order);
            $order->store();
            return $order->id ? $this->read(['id' => (int) $order->id]) : $this->uncertain();
        } catch (Throwable $error) {
            return $this->uncertain();
        } finally {
            $ppcart_product = $previous;
        }
    }

    /** @since 1.1.0 @return WP_Error */
    private function forbidden()
    {
        return new WP_Error('ppcart_mcp_forbidden', __('You cannot manage this order or a related object.', 'publishpress-cart'));
    }
    /** @since 1.1.0 @return WP_Error */
    private function stale()
    {
        return new WP_Error('ppcart_mcp_stale_revision', __('The order changed. Read it again.', 'publishpress-cart'));
    }
    /** @since 1.1.0 @return WP_Error */
    private function uncertain()
    {
        return new WP_Error('ppcart_mcp_save_unconfirmed', __('Native order changes could not be confirmed. Inspect the order before retrying.', 'publishpress-cart'));
    }

    /** @since 1.1.0 @return array */
    public function confirmation_schema()
    {
        return PPCart_Mcp_Input::schema(['id' => ['type' => 'integer', 'minimum' => 1],
            'revision' => ['type' => 'string', 'minLength' => 64, 'maxLength' => 64], 'dry_run' => ['type' => 'boolean']], ['id', 'revision']);
    }
    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function resend_confirmation($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->confirmation_schema());
        if (is_wp_error($input)) {
            return $input;
        }
        $read = $this->read(['id' => $input['id']]);
        if (is_wp_error($read)) {
            return $read;
        }
        if (! hash_equals($read['revision'], $input['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('The order changed. Read it before sending.', 'publishpress-cart'));
        }
        if ('paid' !== $read['details']->status || ! is_email($read['details']->email)) {
            return PPCart_Mcp_Input::error();
        }
        if (! get_option('_ppcart_email_confirmation_enable')) {
            return new WP_Error('ppcart_mcp_configuration_locked', __('Enable native confirmation emails before resending.', 'publishpress-cart'));
        }
        if (! empty($input['dry_run'])) {
            return ['dry_run' => true, 'id' => $input['id'], 'uses_native_recipient' => true];
        }
        try {
            $result = ppcart_resend_purchase_confirmation($input['id']);
        } catch (Throwable $error) {
            return new WP_Error('ppcart_mcp_mail_unconfirmed', __('Delivery is unconfirmed. Check delivery before retrying.', 'publishpress-cart'));
        }
        return is_wp_error($result) ? $result : ['sent' => true, 'id' => $input['id']];
    }
}

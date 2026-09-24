<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Debug_Log_Viewer_Group_Matching_Trait
{
    /**
         * Check if a group includes one of the named workflows.
         *
         * @param array $group     Group data.
         * @param array $workflows Workflow names.
         * @return bool
         */
    private static function group_has_workflow($group, $workflows)
    {
        foreach ($group['entries'] as $entry) {
            if (in_array(self::get($entry, 'workflow', ''), $workflows, true)) {
                return true;
            }
        }

        return false;
    }

    /**
         * Check if a group includes one of the named structured events.
         *
         * @param array $group  Group data.
         * @param array $events Event names.
         * @return bool
         */
    private static function group_has_event($group, $events)
    {
        foreach ($group['entries'] as $entry) {
            $context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];
            if (isset($context['event']) && in_array((string) $context['event'], $events, true)) {
                return true;
            }
        }

        return false;
    }

    /**
         * Check if a group covers PaymentIntent setup.
         *
         * @param array $group Group data.
         * @return bool
         */
    private static function group_has_payment_setup_event($group)
    {
        return self::group_has_event(
            $group,
            [
                'checkout.payment_intent.creating',
                'checkout.payment_intent.request_prepared',
                'checkout.payment_intent.created',
                'checkout.stripe_subscription.creating',
                'checkout.stripe_subscription.request_prepared',
                'checkout.stripe_subscription.created',
            ]
        );
    }

    /**
         * Check if a group changed an order from one status to another.
         *
         * @param array  $group Group data.
         * @param string $from  Previous status.
         * @param string $to    Next status.
         * @return bool
         */
    private static function group_has_order_transition($group, $from, $to)
    {
        foreach ($group['entries'] as $entry) {
            $context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];
            if (
                isset($context['previous_status'], $context['status'])
                && (string) $context['previous_status'] === $from
                && (string) $context['status'] === $to
            ) {
                return true;
            }

            if (
                isset($context['previous_status'], $context['next_status'])
                && (string) $context['previous_status'] === $from
                && (string) $context['next_status'] === $to
            ) {
                return true;
            }
        }

        return false;
    }

    /**
         * Check if Stripe updated charge/payment identifiers without a status change.
         *
         * @param array $group Group data.
         * @return bool
         */
    private static function group_has_transaction_update_without_status_change($group)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-group-transaction-update-without-status.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Choose the best workflow label for a group.
         *
         * @param string $current Current workflow.
         * @param string $next    Candidate workflow.
         * @return string
         */
    private static function choose_group_workflow($current, $next)
    {
        $priority = [
            'Checkout'     => 100,
            'Order'        => 90,
            'Subscription' => 80,
            'Stripe'       => 70,
            'Integration'  => 60,
            'Security'     => 50,
            'PayPal'       => 40,
            'Email'        => 30,
            'Download'     => 20,
            'Tax'          => 10,
            'General'      => 0,
        ];

        $current_score = $priority[ $current ] ?? 0;
        $next_score    = $priority[ $next ] ?? 0;

        return $next_score > $current_score ? $next : $current;
    }

    /**
         * Return the more severe level.
         *
         * @param string $current Current level.
         * @param string $next    Candidate level.
         * @return string
         */
    private static function stronger_level($current, $next)
    {
        $priority = [
            'CRITICAL' => 60,
            'FAILURE'  => 50,
            'WARNING'  => 40,
            'UNKNOWN'  => -1,
            'NOTICE'   => 20,
            'STATUS'   => 10,
            'SUCCESS'  => 0,
        ];

        $current = self::normalize_level($current);
        $next    = self::normalize_level($next);

        return $priority[ $next ] > $priority[ $current ] ? $next : $current;
    }
}

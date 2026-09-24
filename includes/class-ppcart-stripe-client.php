<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prefixed stripe-php can hydrate list/search payloads as StripeObject.
 * Services still call ->all() / ->search(); this client promotes those
 * objects to Collection / SearchResult instead of throwing.
 */
class PPCart_Stripe_Client extends \PublishPress\Stripe\StripeClient
{
    public function requestCollection($method, $path, $params, $opts)
    {
        $obj = $this->request($method, $path, $params, $opts);
        $apiMode = \PublishPress\Stripe\Util\Util::getApiMode($path);

        if ('v2' === $apiMode) {
            if ($obj instanceof \PublishPress\Stripe\V2\Collection) {
                return $obj;
            }

            $promoted = $this->promoteStripeObject($obj, \PublishPress\Stripe\V2\Collection::class, $params, false);
            if ($promoted) {
                return $promoted;
            }
        } else {
            if ($obj instanceof \PublishPress\Stripe\Collection) {
                $obj->setFilters($params);

                return $obj;
            }

            $promoted = $this->promoteStripeObject($obj, \PublishPress\Stripe\Collection::class, $params, true);
            if ($promoted) {
                return $promoted;
            }
        }

        $received = is_object($obj) ? get_class($obj) : gettype($obj);

        throw new \PublishPress\Stripe\Exception\UnexpectedValueException(
            'Expected to receive a Stripe list collection. Instead received `' . $received . '`.'
        );
    }

    public function requestSearchResult($method, $path, $params, $opts)
    {
        $obj = $this->request($method, $path, $params, $opts);

        if ($obj instanceof \PublishPress\Stripe\SearchResult) {
            $obj->setFilters($params);

            return $obj;
        }

        $promoted = $this->promoteStripeObject($obj, \PublishPress\Stripe\SearchResult::class, $params, true);
        if ($promoted) {
            return $promoted;
        }

        $received = is_object($obj) ? get_class($obj) : gettype($obj);

        throw new \PublishPress\Stripe\Exception\UnexpectedValueException(
            'Expected to receive a Stripe search result. Instead received `' . $received . '`.'
        );
    }

    /**
     * @param mixed  $obj
     * @param string $class
     * @param array  $params
     * @param bool   $set_filters
     * @return object|null
     */
    private function promoteStripeObject($obj, $class, $params, $set_filters)
    {
        if (! is_object($obj) || ! method_exists($obj, 'toArray')) {
            return null;
        }

        $values = $obj->toArray();
        if (! is_array($values) || ! array_key_exists('data', $values)) {
            return null;
        }

        $promoted = $class::constructFrom($values);
        if ($set_filters && method_exists($promoted, 'setFilters')) {
            $promoted->setFilters($params);
        }

        return $promoted;
    }
}

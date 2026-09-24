<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

/**
 * Test double for product template resolution on confirmation requests.
 */
class PPCartBlockTestConfirmationPublic extends \PPCart_Public_Page_Controller
{
    public function __construct()
    {
    }

    protected function is_order_confirmation_request()
    {
        return true;
    }
}

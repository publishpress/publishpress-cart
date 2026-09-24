<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Minimal WP_Error double for unit tests.
 */
class WPError
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @param string $code
     * @param string $message
     */
    public function __construct(string $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function get_error_message(): string
    {
        return $this->message;
    }
}

if (! class_exists('WP_Error')) {
    class_alias(WPError::class, 'WP_Error');
}

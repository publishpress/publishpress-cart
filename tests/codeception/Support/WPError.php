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
     * @var mixed
     */
    private $data;

    /**
     * @param string $code
     * @param string $message
     * @param mixed  $data
     */
    public function __construct(string $code, string $message, $data = '')
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function get_error_code(): string
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function get_error_data()
    {
        return $this->data;
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

<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * One schema problem detected on a table.
 */
final class PPCart_DB_Schema_Issue
{
    const MISSING_TABLE = 'missing_table';

    const MISSING_COLUMN = 'missing_column';

    const COLUMN_MISMATCH = 'column_mismatch';

    const MISSING_INDEX = 'missing_index';

    const INDEX_MISMATCH = 'index_mismatch';

    /** @var string */
    private $type;

    /** @var string */
    private $table;

    /** @var string */
    private $name;

    /** @var string */
    private $expected;

    /** @var string */
    private $actual;

    /**
     * @param string $type Issue type constant.
     * @param string $table Table name.
     * @param string $name Column or index name.
     * @param string $expected Expected value description.
     * @param string $actual Actual value description.
     */
    public function __construct($type, $table, $name = '', $expected = '', $actual = '')
    {
        $this->type     = (string) $type;
        $this->table    = (string) $table;
        $this->name     = (string) $name;
        $this->expected = (string) $expected;
        $this->actual   = (string) $actual;
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_message()
    {
        switch ($this->type) {
            case self::MISSING_TABLE:
                return __('Table is missing.', 'publishpress-cart');
            case self::MISSING_COLUMN:
                return sprintf(
                    /* translators: %s: column name. */
                    __('Column "%s" is missing.', 'publishpress-cart'),
                    $this->name
                );
            case self::COLUMN_MISMATCH:
                return sprintf(
                    /* translators: 1: column name, 2: expected type, 3: actual type. */
                    __('Column "%1$s" should be %2$s but is %3$s.', 'publishpress-cart'),
                    $this->name,
                    $this->expected,
                    $this->actual
                );
            case self::MISSING_INDEX:
                return sprintf(
                    /* translators: %s: index name. */
                    __('Index "%s" is missing.', 'publishpress-cart'),
                    $this->name
                );
            case self::INDEX_MISMATCH:
                return sprintf(
                    /* translators: 1: index name, 2: expected definition, 3: actual definition. */
                    __('Index "%1$s" should be %2$s but is %3$s.', 'publishpress-cart'),
                    $this->name,
                    $this->expected,
                    $this->actual
                );
        }

        return __('Schema issue detected.', 'publishpress-cart');
    }
}

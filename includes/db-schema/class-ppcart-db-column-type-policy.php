<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Decides whether changing a column from one type to another keeps every stored value.
 *
 * WordPress runs MySQL without strict mode, so a narrowing MODIFY COLUMN truncates
 * data silently instead of failing.
 */
final class PPCart_DB_Column_Type_Policy
{
    private const INTEGER_RANK = [
        'tinyint'   => 1,
        'smallint'  => 2,
        'mediumint' => 3,
        'int'       => 4,
        'integer'   => 4,
        'bigint'    => 5,
    ];

    /** Maximum bytes per text type. */
    private const TEXT_BYTES = [
        'tinytext'   => 255,
        'text'       => 65535,
        'mediumtext' => 16777215,
        'longtext'   => 4294967295,
    ];

    /** utf8mb4 stores up to 4 bytes per character. */
    private const MAX_BYTES_PER_CHAR = 4;

    /**
     * @param string $from Normalized live type (e.g. "varchar(40)", "bigint unsigned").
     * @param string $to Normalized expected type.
     * @return bool
     */
    public function is_lossless_change($from, $to)
    {
        $from_type = $this->parse($from);
        $to_type   = $this->parse($to);

        if (null === $from_type || null === $to_type) {
            return false;
        }

        if ('integer' === $from_type['family'] && 'integer' === $to_type['family']) {
            return $from_type['unsigned'] === $to_type['unsigned'] && $to_type['rank'] >= $from_type['rank'];
        }

        if ('string' === $from_type['family'] && 'string' === $to_type['family']) {
            return $to_type['bytes'] >= $from_type['bytes'];
        }

        return false;
    }

    /**
     * @param string $type Normalized SQL type.
     * @return array{family: string, rank?: int, unsigned?: bool, bytes?: int}|null
     */
    private function parse($type)
    {
        $type = strtolower(trim((string) $type));

        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)(?:\(\d+\))?( unsigned)?$/', $type, $m)) {
            return [
                'family'   => 'integer',
                'rank'     => self::INTEGER_RANK[ $m[1] ],
                'unsigned' => ! empty($m[2]),
            ];
        }

        if (preg_match('/^(?:var)?char\((\d+)\)$/', $type, $m)) {
            return [
                'family' => 'string',
                'bytes'  => (int) $m[1] * self::MAX_BYTES_PER_CHAR,
            ];
        }

        if (isset(self::TEXT_BYTES[ $type ])) {
            return [
                'family' => 'string',
                'bytes'  => self::TEXT_BYTES[ $type ],
            ];
        }

        return null;
    }
}

<?php

/** Native CSV encoding shared by report downloads and MCP. @package PPCart */

defined('ABSPATH') || exit;

function ppcart_csv_escape_cell($value)
{
    $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, 'UTF-8');

    if ('' !== $value && preg_match('/^(?:[=\+\-@\t\r\n]|[\x00-\x20]+[=\+\-@])/', $value)) {
        $value = "'" . $value;
    }

    if (false !== strpbrk($value, "\",\r\n")) {
        $value = '"' . str_replace('"', '""', $value) . '"';
    }

    return $value;
}


function ppcart_csv_row($values)
{
    return implode(',', array_map('ppcart_csv_escape_cell', $values)) . "\n";
}

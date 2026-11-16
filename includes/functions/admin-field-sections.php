<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Splits admin fields into titled sections at render time.
 *
 * The field arrays stay as they are (other code splices them by position);
 * only the display order changes.
 *
 * @package PPCart
 */

/**
 * Sorts field keys into sections.
 *
 * A field listed in a section goes to that section, in the order of the list.
 * A field that no section lists stays right after the field before it. If that
 * field is in a collapsed ("details") section, it goes to the first open
 * section instead, so new fields from add-ons are never hidden.
 *
 * @param array<int|string,string> $field_ids Field key => field id ('' when the field has no id).
 * @param array<string,array>      $sections  Section id => ['fields' => string[], 'type' => 'details'|''].
 * @return array<string,array<int,int|string>> Section id => field keys in display order. Empty sections are left out.
 */
function ppcart_admin_group_fields_by_section(array $field_ids, array $sections)
{
    if ([] === $sections) {
        return [];
    }

    $default_section = null;
    $section_of      = [];
    $rank_of         = [];
    foreach ($sections as $section_id => $section) {
        $section_id = (string) $section_id;
        if (null === $default_section && 'details' !== ($section['type'] ?? '')) {
            $default_section = $section_id;
        }
        foreach (array_values((array) ($section['fields'] ?? [])) as $rank => $field_id) {
            $section_of[ (string) $field_id ] = $section_id;
            $rank_of[ (string) $field_id ]    = (float) $rank;
        }
    }
    if (null === $default_section) {
        $default_section = (string) array_key_first($sections);
    }

    $placed           = [];
    $last_rank        = [];
    $previous_section = $default_section;
    $position         = 0;
    foreach ($field_ids as $key => $field_id) {
        $field_id = (string) $field_id;

        if ('' !== $field_id && isset($section_of[ $field_id ])) {
            $section_id = $section_of[ $field_id ];
            $rank       = $rank_of[ $field_id ];
        } else {
            $section_id = $previous_section;
            if ('details' === ($sections[ $section_id ]['type'] ?? '')) {
                $section_id = $default_section;
            }
            $rank = ($last_rank[ $section_id ] ?? -1.0) + 0.001;
        }

        $placed[ $section_id ][] = [ 'key' => $key, 'rank' => $rank, 'position' => $position++ ];
        $last_rank[ $section_id ] = $rank;
        $previous_section        = $section_id;
    }

    $grouped = [];
    foreach (array_keys($sections) as $section_id) {
        $section_id = (string) $section_id;
        if (empty($placed[ $section_id ])) {
            continue;
        }

        $items = $placed[ $section_id ];
        usort(
            $items,
            static function ($a, $b) {
                if ($a['rank'] === $b['rank']) {
                    return $a['position'] <=> $b['position'];
                }

                return $a['rank'] <=> $b['rank'];
            }
        );
        $grouped[ $section_id ] = array_column($items, 'key');
    }

    return $grouped;
}

/**
 * Groups prepared repeater body fields by section.
 *
 * @param array<int,array{field_type:string,atts:array}> $body_fields Prepared fields from the repeater partial.
 * @param array<string,array>                           $sections    Section map.
 * @return array<string,array<int,array>> Section id => prepared fields.
 */
function ppcart_admin_repeater_group_fields(array $body_fields, array $sections)
{
    $field_ids = [];
    foreach ($body_fields as $key => $prepared) {
        $field_ids[ $key ] = (string) ($prepared['atts']['id'] ?? '');
    }

    $grouped = [];
    foreach (ppcart_admin_group_fields_by_section($field_ids, $sections) as $section_id => $keys) {
        foreach ($keys as $key) {
            $grouped[ $section_id ][] = $body_fields[ $key ];
        }
    }

    return $grouped;
}

<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * JSON-encode a value for safe interpolation inside inline admin JavaScript.
 *
 * @param mixed $value Scalar, array, or bool.
 * @return string
 */
function ppcart_admin_js_literal($value)
{
    return wp_json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
}

/**
 * Normalize a conditional-logic compare token for JavaScript.
 *
 * @param string|null $compare Compare operator from field definition.
 * @return string
 */
function ppcart_admin_js_compare_operator($compare)
{
    $allowed = ['==', '===', '!=', '!==', '<', '>', '<=', '>='];

    if (! isset($compare) || '=' === $compare || '' === $compare) {
        return '==';
    }

    return in_array($compare, $allowed, true) ? $compare : '==';
}

/**
 * Sanitize a string for use as a JavaScript identifier suffix (arr_*).
 *
 * @param string $id Field or row id.
 * @return string
 */
function ppcart_admin_conditional_array_var_name($id)
{
    return preg_replace('/[^A-Za-z0-9_]/', '', (string) $id);
}

/**
 * Build one condition expression for metabox fields (combined with &&).
 *
 * @param array  $rule       Single conditional_logic rule.
 * @param string $arr_suffix Sanitized id for arr_* variables.
 * @param string $mode       metabox|repeater_block.
 * @return string JavaScript boolean expression.
 */
function ppcart_admin_conditional_logic_condition_expr($rule, $arr_suffix, $mode = 'metabox')
{
    $field = isset($rule['field']) ? (string) $rule['field'] : '';
    $compare = isset($rule['compare']) ? $rule['compare'] : '=';

    if ('IN' === $compare || 'NOT IN' === $compare) {
        $values = isset($rule['value']) ? $rule['value'] : [];
        if (! is_array($values)) {
            $values = [$values];
        }
        $var = 'arr_' . $arr_suffix;
        $selector = ppcart_admin_js_literal('#' . $field);

        if ('repeater_block' === $mode) {
            $left = '$(this).val()';
        } else {
            $left = '$(' . $selector . ').val()';
        }

        if ('IN' === $compare) {
            return '(' . $var . '.includes(' . $left . '))';
        }

        return '(!' . $var . '.includes(' . $left . '))';
    }

    $op = ppcart_admin_js_compare_operator($compare);
    $value = isset($rule['value']) ? $rule['value'] : '';

    if (true === $value) {
        $selector = ppcart_admin_js_literal('#' . $field . ':checked');
    } else {
        $selector = ppcart_admin_js_literal('#' . $field);
    }

    if ('repeater_block' === $mode) {
        return '($(' . $selector . ').val() ' . $op . ' ' . ppcart_admin_js_literal($value) . ')';
    }

    return '($(' . $selector . ').val() ' . $op . ' ' . ppcart_admin_js_literal($value) . ')';
}

/**
 * Declare arr_* variables for IN / NOT IN rules.
 *
 * @param array  $rules      conditional_logic list.
 * @param string $arr_suffix Sanitized id for variable names.
 * @return string JavaScript var declarations.
 */
function ppcart_admin_conditional_logic_array_decls($rules, $arr_suffix)
{
    $out = '';

    foreach ($rules as $rule) {
        $compare = isset($rule['compare']) ? $rule['compare'] : '';

        if ('IN' !== $compare && 'NOT IN' !== $compare) {
            continue;
        }

        $values = isset($rule['value']) ? $rule['value'] : [];
        if (! is_array($values)) {
            $values = [$values];
        }

        $var = 'arr_' . $arr_suffix;
        $out .= 'var ' . $var . ' = ' . ppcart_admin_js_literal($values) . ';';
    }

    return $out;
}

/**
 * Product metabox: combined conditions, fadeIn/hide on row id.
 *
 * @param array  $rules   conditional_logic rules.
 * @param string $row_id  DOM id without # (e.g. rid_ppcart_foo or repeater_ppcart_foo).
 * @param string $field_id_for_in  Field atts id used for arr_* suffix (usually $atts['id']).
 * @param string $mode    metabox|repeater_block When repeater_block, IN uses $(this).val().
 * @return string
 */
function ppcart_admin_conditional_logic_js_combined($rules, $row_id, $field_id_for_in, $mode = 'metabox')
{
    if (empty($rules) || ! is_array($rules)) {
        return '';
    }

    $arr_suffix = ppcart_admin_conditional_array_var_name($field_id_for_in);
    $script = ppcart_admin_conditional_logic_array_decls($rules, $arr_suffix);

    $conditions = [];
    foreach ($rules as $rule) {
        $conditions[] = ppcart_admin_conditional_logic_condition_expr($rule, $arr_suffix, $mode);
    }

    if (empty($conditions)) {
        return $script;
    }

    $joined = implode(' && ', $conditions);
    $row_sel = ppcart_admin_js_literal('#' . $row_id);
    $eval = 'if ( ' . $joined . ' ) { $(' . $row_sel . ').fadeIn();} else { $(' . $row_sel . ').hide();}';

    $change_field = '';
    foreach ($rules as $rule) {
        if (! empty($rule['field'])) {
            $change_field = (string) $rule['field'];
        }
    }

    if ('' === $change_field) {
        return $script . $eval;
    }

    $field_sel = ppcart_admin_js_literal('#' . $change_field);
    $script .= $eval . '$(' . $field_sel . ').change(function(){' . $eval . ';});';

    return $script;
}

/**
 * Order edit metabox: one if/change block per rule (legacy behavior).
 *
 * @param array  $rules  conditional_logic rules.
 * @param string $row_id rid-prefixed row id without #.
 * @return string
 */
function ppcart_admin_conditional_logic_js_order_metabox($rules, $row_id)
{
    if (empty($rules) || ! is_array($rules)) {
        return '';
    }

    $script = '';
    $row_sel = ppcart_admin_js_literal('#' . $row_id);

    foreach ($rules as $rule) {
        $field = isset($rule['field']) ? (string) $rule['field'] : '';
        $compare = isset($rule['compare']) ? $rule['compare'] : '=';
        $value = isset($rule['value']) ? $rule['value'] : '';
        $op = ppcart_admin_js_compare_operator($compare);

        if (true === $value) {
            $field_sel = ppcart_admin_js_literal('#' . $field . ':checked');
        } else {
            $field_sel = ppcart_admin_js_literal('#' . $field);
        }

        $condition = 'if ( $(' . $field_sel . ').val() ' . $op . ' ' . ppcart_admin_js_literal($value) . ' ) { $(' . $row_sel . ').fadeIn() } else { $(' . $row_sel . ').hide() }';

        $change_sel = ppcart_admin_js_literal('#' . $field);
        $script .= $condition;
        $script .= '$(' . $change_sel . ').change(function(){';
        $script .= $condition;
        $script .= '});';
    }

    return $script;
}

/**
 * Repeater row conditional logic inside ppcart-admin-field-repeater.php.
 *
 * @param array  $rules       conditional_logic on the inner field.
 * @param string $set_id      Repeater set id (setatts id).
 * @param string $field_row_id Inner field id for .rid* target.
 * @param string $repeater_id Repeater ul id without #.
 * @return string
 */
function ppcart_admin_conditional_logic_js_repeater_rows($rules, $set_id, $field_row_id, $repeater_id)
{
    if (empty($rules) || ! is_array($rules)) {
        return '';
    }

    $arr_suffix = ppcart_admin_conditional_array_var_name($field_row_id);
    $script = ppcart_admin_conditional_logic_array_decls($rules, $arr_suffix);

    $conditions = [];
    foreach ($rules as $rule) {
        $field_key = isset($rule['field']) ? (string) $rule['field'] : '';
        $prefixed = $set_id . '[' . $field_key . ']';
        $fieldname = '[name^="' . $prefixed . '["]';
        $compare = isset($rule['compare']) ? $rule['compare'] : '=';
        $value = isset($rule['value']) ? $rule['value'] : '';

        if ('IN' === $compare || 'NOT IN' === $compare) {
            $var = 'arr_' . $arr_suffix;
            if ('IN' === $compare) {
                $conditions[] = '(' . $var . '.includes($(this).val()))';
            } else {
                $conditions[] = '(!' . $var . '.includes($(this).val()))';
            }
            continue;
        }

        $op = ppcart_admin_js_compare_operator($compare);
        $selector = ppcart_admin_js_literal($fieldname);

        if (true === $value) {
            $eval = '$(this).closest(".ppcart-repeater-content").find(' . ppcart_admin_js_literal($fieldname . ':checked') . ').val()';
        } else {
            $eval = '$(this).closest(".ppcart-repeater-content").find(' . $selector . ').val()';
        }

        $conditions[] = '(' . $eval . ' ' . $op . ' ' . ppcart_admin_js_literal($value) . ')';
    }

    if (empty($conditions)) {
        return $script;
    }

    $joined = implode(' && ', $conditions);
    $safe_row_class = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $field_row_id);
    $row_expr = '$(this).closest(".ppcart-repeater-content").find(".rid' . $safe_row_class . '")';

    $eval = 'if ( ' . $joined . ' ) {
                                        ' . $row_expr . '.css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400)
                                    } else {
                                        ' . $row_expr . '.hide()
                                    }';

    foreach ($rules as $rule) {
        $field_key = isset($rule['field']) ? (string) $rule['field'] : '';
        $prefixed = $set_id . '[' . $field_key . ']';
        $fieldname = '[name^="' . $prefixed . '["]';
        $listener_sel = '#' . $repeater_id . ' ' . $fieldname;
        $listener = ppcart_admin_js_literal($listener_sel);

        $block = $eval . '$(' . $listener . ').change(function(){
                                            ' . $eval . '
                                        });';
        $script .= '$(' . $listener . ').each(function(index){
                                            ' . $block . '
                                        });
                                        ';
    }

    return $script;
}

<?php

if (! defined('ABSPATH')) {
    exit;
}

$templates = [];

$ppcart_tax_row_buffer_level = ob_get_level();
$ppcart_tax_row_buffer_active = true;
$ppcart_tax_row_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_tax_row_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_tax_row_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
    <tr class="tax-tips" data-tip="<?php
        printf(
            /* translators: %s: tax rate ID. */
            esc_attr__('Tax rate ID: %s', 'publishpress-cart'),
            '{{ data.tax_rate_id }}'
        ); ?>" data-id="{{ data.tax_rate_id }}">
        <td class="tax-country">
            <input type="text" value="{{ data.tax_rate_country }}" placeholder="*" name="tax_rate_country[{{ data.tax_rate_id }}]" class="wc_input_country_iso" data-attribute="tax_rate_country" style="text-transform:uppercase" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-country" />
        </td>

        <td class="tax-state">
            <input type="text" value="{{ data.tax_rate_state }}" placeholder="*" name="tax_rate_state[{{ data.tax_rate_id }}]" data-attribute="tax_rate_state" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-state" />
        </td>

        <td class="tax-postcode">
            <input type="text" value="{{ data.tax_rate_postcode }}" placeholder="*" data-name="tax_rate_postcode[{{ data.tax_rate_id }}]" data-attribute="tax_rate_postcode" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-postcode" />
        </td>

        <td class="tax-city">
            <input type="text" value="{{ data.tax_rate_city }}" placeholder="*" data-name="tax_rate_city[{{ data.tax_rate_id }}]" data-attribute="tax_rate_city" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-city" />
        </td>

        <td class="tax-rate">
            <input type="text" value="{{ data.tax_rate }}" placeholder="0" name="tax_rate[{{ data.tax_rate_id }}]" data-attribute="tax_rate" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-rate" />
        </td>

        <td class="tax-title">
            <input type="text" value="{{ data.tax_rate_title }}" name="tax_rate_title[{{ data.tax_rate_id }}]" data-attribute="tax_rate_title" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-title" />
        </td>

        <td class="priority">
            <input type="number" step="1" min="1" value="{{ data.tax_rate_priority }}" name="tax_rate_priority[{{ data.tax_rate_id }}]" data-attribute="tax_rate_priority" data-testid="ppcart-admin-tax-rate-{{ data.tax_rate_id }}-priority" />
        </td>
    </tr>
<?php
} catch (Throwable $ppcart_tax_row_buffer_exception) {
    $ppcart_tax_row_buffer_error = $ppcart_tax_row_buffer_exception;
} finally {
    $ppcart_tax_row_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_tax_row_buffer_active && ob_get_level() > $ppcart_tax_row_buffer_level + 1) {
        $ppcart_tax_row_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_tax_row_buffer_exception) {
            $ppcart_tax_row_buffer_error = $ppcart_tax_row_buffer_error ?? $ppcart_tax_row_buffer_exception;
            if (ob_get_level() >= $ppcart_tax_row_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_tax_row_buffer_active && ob_get_level() === $ppcart_tax_row_buffer_level + 1) {
        $ppcart_tax_row_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_tax_row_buffer_error) {
    throw $ppcart_tax_row_buffer_error;
}
$templates['tmpl-ppcart-tax-table-row'] = $ppcart_tax_row_buffer_output;

$ppcart_tax_empty_buffer_level = ob_get_level();
$ppcart_tax_empty_buffer_active = true;
$ppcart_tax_empty_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_tax_empty_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_tax_empty_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
    <tr>
        <th colspan="7" class="ppcart-settings__tax-rates-empty"><?php esc_html_e('No matching tax rates found.', 'publishpress-cart'); ?></th>
    </tr>
<?php
} catch (Throwable $ppcart_tax_empty_buffer_exception) {
    $ppcart_tax_empty_buffer_error = $ppcart_tax_empty_buffer_exception;
} finally {
    $ppcart_tax_empty_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_tax_empty_buffer_active && ob_get_level() > $ppcart_tax_empty_buffer_level + 1) {
        $ppcart_tax_empty_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_tax_empty_buffer_exception) {
            $ppcart_tax_empty_buffer_error = $ppcart_tax_empty_buffer_error ?? $ppcart_tax_empty_buffer_exception;
            if (ob_get_level() >= $ppcart_tax_empty_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_tax_empty_buffer_active && ob_get_level() === $ppcart_tax_empty_buffer_level + 1) {
        $ppcart_tax_empty_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_tax_empty_buffer_error) {
    throw $ppcart_tax_empty_buffer_error;
}
$templates['tmpl-ppcart-tax-table-row-empty'] = $ppcart_tax_empty_buffer_output;

$ppcart_tax_pagination_buffer_level = ob_get_level();
$ppcart_tax_pagination_buffer_active = true;
$ppcart_tax_pagination_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_tax_pagination_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_tax_pagination_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
    <div class="tablenav">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                    printf(
                        /* translators: %s: number of items. */
                        esc_html__('%s items', 'publishpress-cart'),
                        '{{ data.qty_rates }}'
                    );
    ?>
            </span>
            <span class="pagination-links">

                <a class="tablenav-pages-navspan" data-goto="1" data-testid="ppcart-admin-tax-rates-page-first">
                    <span class="screen-reader-text"><?php esc_html_e('First page', 'publishpress-cart'); ?></span>
                    <span aria-hidden="true">&laquo;</span>
                </a>
                <a class="tablenav-pages-navspan" data-goto="<# print( Math.max( 1, parseInt( data.current_page, 10 ) - 1 ) ) #>" data-testid="ppcart-admin-tax-rates-page-prev">
                    <span class="screen-reader-text"><?php esc_html_e('Previous page', 'publishpress-cart'); ?></span>
                    <span aria-hidden="true">&lsaquo;</span>
                </a>

                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text"><?php esc_html_e('Current page', 'publishpress-cart'); ?></label>
                    <?php
            printf(
                /* translators: 1: current page, 2: total pages. */
                esc_html_x('%1$s of %2$s', 'Pagination', 'publishpress-cart'),
                '<input class="current-page" id="current-page-selector" type="text" name="paged" value="{{ data.current_page }}" size="<# print( data.qty_pages.toString().length ) #>" aria-describedby="table-paging" data-testid="ppcart-admin-tax-rates-page-current">',
                '<span class="total-pages">{{ data.qty_pages }}</span>'
            );
    ?>
                </span>

                <a class="tablenav-pages-navspan" data-goto="<# print( Math.min( data.qty_pages, parseInt( data.current_page, 10 ) + 1 ) ) #>" data-testid="ppcart-admin-tax-rates-page-next">
                    <span class="screen-reader-text"><?php esc_html_e('Next page', 'publishpress-cart'); ?></span>
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
                <a class="tablenav-pages-navspan" data-goto="{{ data.qty_pages }}" data-testid="ppcart-admin-tax-rates-page-last">
                    <span class="screen-reader-text"><?php esc_html_e('Last page', 'publishpress-cart'); ?></span>
                    <span aria-hidden="true">&raquo;</span>
                </a>

            </span>
        </div>
    </div>
<?php
} catch (Throwable $ppcart_tax_pagination_buffer_exception) {
    $ppcart_tax_pagination_buffer_error = $ppcart_tax_pagination_buffer_exception;
} finally {
    $ppcart_tax_pagination_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_tax_pagination_buffer_active && ob_get_level() > $ppcart_tax_pagination_buffer_level + 1) {
        $ppcart_tax_pagination_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_tax_pagination_buffer_exception) {
            $ppcart_tax_pagination_buffer_error = $ppcart_tax_pagination_buffer_error ?? $ppcart_tax_pagination_buffer_exception;
            if (ob_get_level() >= $ppcart_tax_pagination_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_tax_pagination_buffer_active && ob_get_level() === $ppcart_tax_pagination_buffer_level + 1) {
        $ppcart_tax_pagination_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_tax_pagination_buffer_error) {
    throw $ppcart_tax_pagination_buffer_error;
}
$templates['tmpl-ppcart-tax-table-pagination'] = $ppcart_tax_pagination_buffer_output;

return $templates;

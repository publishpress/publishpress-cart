<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div id="ppcart-tax-rates" class="ppcart-settings__tax-rates" data-pp-tax-rates>
    <div class="ppcart-settings__tax-rates-header">
        <div class="ppcart-settings__tax-rates-title-group">
            <h2><?php esc_html_e('Custom Tax Rates', 'publishpress-cart'); ?></h2>
            <p><?php esc_html_e('Define country, state, postcode, and city-specific rates used during checkout.', 'publishpress-cart'); ?></p>
        </div>
        <label id="ppcart-tax-rates-search" class="ppcart-settings__tax-rates-search">
            <span class="screen-reader-text"><?php esc_html_e('Search tax rates', 'publishpress-cart'); ?></span>
            <input type="search" name="search" class="ppcart-tax-rates-search-input" placeholder="<?php esc_attr_e('Search rates...', 'publishpress-cart'); ?>" autocomplete="off" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-search')); ?>">
        </label>
    </div>

    <div class="ppcart-settings__tax-rates-table-wrap" data-pp-tax-rates-table>
        <table class="widefat ppcart_tax_rate_table">
            <thead>
                <tr>
                    <th><a href="https://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank" rel="noreferrer noopener" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-country-code-help')); ?>"><?php esc_html_e('Country code', 'publishpress-cart'); ?></a></th>
                    <th><?php esc_html_e('State code', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Postcode', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('City', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Rate %', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Tax Title', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Priority', 'publishpress-cart'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="7">
                        <div class="ppcart-settings__tax-rates-actions">
                            <span class="ppcart-settings__tax-rates-action-group">
                                <a href="javascript:void(0);" class="button ppcart_button add_new_tax_rate" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-add-row')); ?>"><?php esc_html_e('Insert row', 'publishpress-cart'); ?></a>
                                <a href="javascript:void(0);" class="button ppcart_button remove_selected_tax_rates" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-remove-selected')); ?>"><?php esc_html_e('Remove selected row(s)', 'publishpress-cart'); ?></a>
                            </span>
                            <span class="ppcart-settings__tax-rates-action-group ppcart-settings__tax-rates-action-group--primary">
                                <input type="button" name="save" value="<?php echo esc_attr__('Save Tax Rates', 'publishpress-cart'); ?>" class="button button-primary save_table_rate" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-save')); ?>">
                            </span>
                            <span class="ppcart-settings__tax-rates-action-group">
                                <a href="<?php echo esc_url(admin_url('admin.php?import=ppcart_tax_rate_csv')); ?>" class="button import" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-import')); ?>"><?php esc_html_e('Import CSV', 'publishpress-cart'); ?></a>
                                <a href="#" class="button export export_taxes_rate" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-tax-rates-export')); ?>"><?php esc_html_e('Export CSV', 'publishpress-cart'); ?></a>
                            </span>
                        </div>
                    </th>
                </tr>
            </tfoot>
            <tbody id="ppcart-tax-rates-body">
                <tr>
                    <th colspan="7" class="ppcart-settings__tax-rates-empty"><?php esc_html_e('Loading&hellip;', 'publishpress-cart'); ?></th>
                </tr>
            </tbody>
        </table>
        <div id="ppcart-tax-rates-pagination" class="ppcart-settings__tax-rates-pagination"></div>
    </div>
</div>

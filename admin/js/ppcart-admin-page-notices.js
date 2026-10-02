/* global jQuery */
jQuery(function ($) {
    'use strict';

    var target = $('.ppcart-global-admin-notices').first();
    if (!target.length) {
        return;
    }

    // Core's common.js may already have moved notices after the page heading.
    // Only collect top-level notices, leaving inline form/panel notices in place.
    var selector = '.notice, .updated, .error, .update-nag';
    var notices = $('#wpbody-content').children(selector).add(
        $('.ppcart-settings-page, .ppcart-customer-report-page').children(selector)
    ).not('.inline');

    target.append(notices);
});

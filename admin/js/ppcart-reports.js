(function($) {
    'use strict';

    var DATE_SEPARATOR = ' to ';
    var DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;

    function parseDate(value) {
        var matches = DATE_PATTERN.exec($.trim(value || ''));

        if (!matches) {
            return null;
        }

        var date = new Date(parseInt(matches[1], 10), parseInt(matches[2], 10) - 1, parseInt(matches[3], 10));

        if (
            date.getFullYear() !== parseInt(matches[1], 10) ||
            date.getMonth() !== parseInt(matches[2], 10) - 1 ||
            date.getDate() !== parseInt(matches[3], 10)
        ) {
            return null;
        }

        return date;
    }

    function parseRange(value) {
        if (!value) {
            return [];
        }

        var dates = $.map(value.split(DATE_SEPARATOR), parseDate);

        return dates.length ? dates : [];
    }

    function syncPresetState($buttons, value) {
        $buttons.each(function() {
            var $button = $(this);
            var isActive = $button.data('value') === value;

            $button.toggleClass('is-active', isActive);
            $button.attr('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function positionCalendar(instance) {
        var calendar = instance.calendarContainer;
        var input = instance._input;
        var viewportGap = 12;
        var topGap = viewportGap;

        if (!calendar || !input) {
            return;
        }

        var adminBar = document.getElementById('wpadminbar');

        if (adminBar) {
            topGap = Math.max(topGap, adminBar.getBoundingClientRect().bottom + viewportGap);
        }

        var inputRect = input.getBoundingClientRect();
        var calendarRect = calendar.getBoundingClientRect();
        var left = inputRect.right - calendarRect.width;
        var top = inputRect.bottom + 6;
        var wouldClipBelow = inputRect.bottom + calendarRect.height + viewportGap > window.innerHeight;
        var canOpenAbove = inputRect.top - calendarRect.height - 6 >= topGap;

        left = Math.max(viewportGap, Math.min(left, window.innerWidth - calendarRect.width - viewportGap));

        if (wouldClipBelow && canOpenAbove) {
            top = inputRect.top - calendarRect.height - 6;
            calendar.classList.remove('arrowTop');
            calendar.classList.add('arrowBottom');
        } else if (wouldClipBelow) {
            top = topGap;
            calendar.classList.remove('arrowTop');
            calendar.classList.remove('arrowBottom');
        } else {
            calendar.classList.remove('arrowBottom');
            calendar.classList.add('arrowTop');
        }

        calendar.style.left = (left + window.pageXOffset) + 'px';
        calendar.style.right = 'auto';
        calendar.style.top = (Math.max(topGap, top) + window.pageYOffset) + 'px';
    }

    function queueCalendarPosition(instance) {
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(function() {
                positionCalendar(instance);
            });
            return;
        }

        window.setTimeout(function() {
            positionCalendar(instance);
        }, 0);
    }

    function addPresetControls(instance, $range) {
        var $select = $('#date-select');

        if (!$select.length || instance.calendarContainer.querySelector('.ppcart-date-picker__presets')) {
            return;
        }

        var $presets = $('<div class="ppcart-date-picker__presets" />');

        $select.find('option').each(function() {
            var $option = $(this);
            var value = $option.attr('value');

            if ('custom' === value) {
                return;
            }

            $('<button type="button" class="ppcart-date-picker__preset" aria-pressed="false" />')
                .text($option.text())
                .data('value', value)
                .on('click', function() {
                    var rangeValue = $(this).data('value');

                    if (rangeValue) {
                        instance.setDate(parseRange(rangeValue), false);
                    } else {
                        instance.clear(false);
                    }

                    $range.val(rangeValue);
                    syncPresetState($presets.find('.ppcart-date-picker__preset'), rangeValue);
                })
                .appendTo($presets);
        });

        $(instance.calendarContainer).append($presets);
        syncPresetState($presets.find('.ppcart-date-picker__preset'), $range.val());
    }

    function initFilterSearch(settings) {
        var $wrap = $(settings.wrap);
        var reportsConfig = window.ppcartReports || {};
        var ajaxUrl = reportsConfig.ajaxUrl || window.ajaxurl || '';
        var searchRequest = null;
        var searchTimer = null;
        var searchToken = 0;
        var optionClass = 'ppcart-reports-search-results__option';
        var emptyClass = 'ppcart-reports-search-results__empty';

        if (!$wrap.length) {
            return;
        }

        var $search = $wrap.find(settings.input);
        var $value = $wrap.find(settings.value);
        var $results = $wrap.find(settings.results);
        var $form = $wrap.closest('form');

        $results.appendTo(document.body);

        function hideResults() {
            window.clearTimeout(searchTimer);
            searchToken += 1;

            if (searchRequest) {
                searchRequest.abort();
                searchRequest = null;
            }

            $results.empty().attr('hidden', true);
            $search.attr('aria-expanded', 'false');
        }

        function positionResults() {
            var rect = $search[0].getBoundingClientRect();

            $results.css({
                position: 'fixed',
                left: rect.left + 'px',
                top: (rect.bottom + 4) + 'px',
                width: Math.max(rect.width, 240) + 'px',
                zIndex: 100000
            });
        }

        function selectItem(value, label) {
            $value.val(value);
            $search.val(value ? (label || '') : '');
            hideResults();
        }

        function showStatus(message) {
            $results.html('<li class="' + emptyClass + '"></li>');
            $results.find('li').text(message);
            $results.removeAttr('hidden');
            $search.attr('aria-expanded', 'true');
            positionResults();
        }

        function appendOption(value, label) {
            var $item = $('<li />');
            $('<button type="button" />')
                .addClass(optionClass)
                .text(label)
                .attr('data-value', value)
                .attr('data-label', label)
                .appendTo($item);
            $item.appendTo($results);
        }

        function renderResults(items) {
            var rows = items || [];

            $results.empty();

            if (settings.allLabel) {
                appendOption('', settings.allLabel);
            }

            $.each(rows, function(index, item) {
                var itemValue = item && item[settings.valueKey] ? String(item[settings.valueKey]) : '';

                if (!itemValue) {
                    return;
                }

                appendOption(itemValue, item.text || itemValue);
            });

            if (!rows.length) {
                if (!settings.allLabel) {
                    showStatus(settings.emptyMessage);
                    return;
                }

                $('<li />')
                    .addClass(emptyClass)
                    .text(settings.emptyMessage)
                    .appendTo($results);
            }

            $results.removeAttr('hidden');
            $search.attr('aria-expanded', 'true');
            positionResults();
        }

        function runSearch(term) {
            if (searchRequest) {
                searchRequest.abort();
                searchRequest = null;
            }

            if (!ajaxUrl || (!term && !settings.searchOnEmptyFocus)) {
                hideResults();
                return;
            }

            var token = ++searchToken;

            searchRequest = $.ajax({
                url: ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    action: settings.action,
                    nonce: settings.nonce,
                    term: term
                }
            });

            searchRequest.done(function(response) {
                if (token !== searchToken) {
                    return;
                }

                var items = (response && response.success && response.data) ? response.data : [];
                renderResults(items);
            });

            searchRequest.fail(function(xhr, status) {
                if ('abort' === status || token !== searchToken) {
                    return;
                }

                showStatus(settings.emptyMessage);
            });
        }

        $search.on('input', function() {
            var term = $.trim($search.val());
            $value.val('');

            window.clearTimeout(searchTimer);
            searchToken += 1;

            if (searchRequest) {
                searchRequest.abort();
                searchRequest = null;
            }

            if (!term && !settings.searchOnEmptyFocus) {
                hideResults();
                return;
            }

            showStatus(reportsConfig.searching || 'Searching…');

            searchTimer = window.setTimeout(function() {
                runSearch(term);
            }, 250);
        });

        $search.on('focus', function() {
            if (!settings.searchOnEmptyFocus || !$results.is('[hidden]')) {
                return;
            }

            showStatus(reportsConfig.searching || 'Searching…');
            runSearch($value.val() ? '' : $.trim($search.val()));
        });

        $results.on('mousedown', '.' + optionClass, function(event) {
            event.preventDefault();
        });

        $results.on('click', '.' + optionClass, function(event) {
            event.preventDefault();
            selectItem($(this).attr('data-value'), $(this).attr('data-label'));
        });

        $search.on('keydown', function(event) {
            if ('Escape' === event.key) {
                hideResults();
            }
        });

        $form.on('submit', function() {
            if (typeof settings.onSubmit === 'function') {
                settings.onSubmit($search, $value);
            }

            if (!$.trim($search.val())) {
                $value.val('');
            }

            hideResults();
        });

        $(document).on('click', function(event) {
            if (
                !$wrap.is(event.target)
                && !$wrap.has(event.target).length
                && !$results.is(event.target)
                && !$results.has(event.target).length
            ) {
                hideResults();
            }
        });

        $(window).on('resize scroll', function() {
            if (!$results.is('[hidden]')) {
                positionResults();
            }
        });
    }

    function initReportFilterSearches() {
        var reportsConfig = window.ppcartReports || {};

        initFilterSearch({
            wrap: '.ppcart-reports-customer-search',
            input: '.ppcart-reports-customer-input',
            value: '.ppcart-reports-customer-value',
            results: '.ppcart-reports-customer-results',
            action: 'ppcart_search_report_customers',
            nonce: reportsConfig.searchCustomersNonce,
            valueKey: 'email',
            emptyMessage: reportsConfig.noCustomers || 'No matching customers',
            searchOnEmptyFocus: false,
            onSubmit: function($search, $value) {
                var typed = $.trim($search.val());

                if (!$value.val() && typed.indexOf('@') !== -1) {
                    $value.val(typed.toLowerCase());
                }
            }
        });

        initFilterSearch({
            wrap: '.ppcart-reports-product-search',
            input: '.ppcart-reports-product-input',
            value: '.ppcart-reports-product-value',
            results: '.ppcart-reports-product-results',
            action: 'ppcart_search_report_products',
            nonce: reportsConfig.searchProductsNonce,
            valueKey: 'id',
            emptyMessage: reportsConfig.noProducts || 'No matching products',
            allLabel: reportsConfig.allProducts || 'All products',
            searchOnEmptyFocus: true,
            onSubmit: function($search, $value) {
                var typed = $.trim($search.val());

                if (!$value.val() && /^\d+$/.test(typed)) {
                    $value.val(typed);
                }
            }
        });
    }

    $(function() {
        initReportFilterSearches();

        var $range = $('#reports-range');

        if (!$range.length || 'function' !== typeof window.flatpickr) {
            return;
        }

        window.flatpickr($range[0], {
            mode: 'range',
            dateFormat: 'Y-m-d',
            conjunction: DATE_SEPARATOR,
            defaultDate: parseRange($range.val()),
            disableMobile: true,
            monthSelectorType: 'static',
            position: 'auto right',
            showMonths: window.matchMedia('(max-width: 782px)').matches ? 1 : 2,
            onReady: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add('ppcart-reports-calendar');
                addPresetControls(instance, $range);
                queueCalendarPosition(instance);
            },
            onOpen: function(selectedDates, dateStr, instance) {
                queueCalendarPosition(instance);
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                queueCalendarPosition(instance);
            },
            onYearChange: function(selectedDates, dateStr, instance) {
                queueCalendarPosition(instance);
            },
            onChange: function(selectedDates, dateStr, instance) {
                syncPresetState(
                    $(instance.calendarContainer).find('.ppcart-date-picker__preset'),
                    selectedDates.length > 1 ? dateStr : ''
                );
            },
            onValueUpdate: function(selectedDates, dateStr, instance) {
                syncPresetState(
                    $(instance.calendarContainer).find('.ppcart-date-picker__preset'),
                    dateStr
                );
            }
        });
    });
})(jQuery);

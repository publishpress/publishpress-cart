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

    // ARIA 1.2 combobox: the input keeps focus, options are referenced with
    // aria-activedescendant, and a polite live region announces the result count.
    function initFilterSearch(settings) {
        var $wrap = $(settings.wrap);
        var reportsConfig = window.ppcartReports || {};
        var ajaxUrl = reportsConfig.ajaxUrl || window.ajaxurl || '';
        var searchRequest = null;
        var searchTimer = null;
        var searchToken = 0;
        var optionClass = 'ppcart-reports-search-results__option';
        var emptyClass = 'ppcart-reports-search-results__empty';
        var activeClass = 'is-active';

        if (!$wrap.length) {
            return;
        }

        var $search = $wrap.find(settings.input);
        var $value = $wrap.find(settings.value);
        var $results = $wrap.find(settings.results);
        var $status = $wrap.find(settings.status);
        var $form = $wrap.closest('form');
        var listId = $results.attr('id') || '';

        $search.attr({
            role: 'combobox',
            'aria-autocomplete': 'list',
            'aria-expanded': 'false',
            'aria-controls': listId
        });
        $results.attr('role', 'listbox');

        function announce(message) {
            if (!$status.length) {
                return;
            }

            // Clear first so the same message is read again on a repeat search.
            $status.text('');
            window.setTimeout(function() {
                $status.text(message || '');
            }, 50);
        }

        function getOptions() {
            return $results.children('.' + optionClass);
        }

        function setActive($option) {
            getOptions().removeClass(activeClass).attr('aria-selected', 'false');

            if (!$option || !$option.length) {
                $search.removeAttr('aria-activedescendant');
                return;
            }

            $option.addClass(activeClass).attr('aria-selected', 'true');
            $search.attr('aria-activedescendant', $option.attr('id'));

            var list = $results[0];
            var item = $option[0];

            if (item.offsetTop < list.scrollTop) {
                list.scrollTop = item.offsetTop;
            } else if (item.offsetTop + item.offsetHeight > list.scrollTop + list.clientHeight) {
                list.scrollTop = item.offsetTop + item.offsetHeight - list.clientHeight;
            }
        }

        function moveActive(step) {
            var $options = getOptions();

            if (!$options.length) {
                return;
            }

            var index = $options.index($options.filter('.' + activeClass));
            var next;

            if (index < 0) {
                next = step > 0 ? 0 : $options.length - 1;
            } else {
                next = (index + step + $options.length) % $options.length;
            }

            setActive($options.eq(next));
        }

        function isOpen() {
            return !$results.is('[hidden]');
        }

        function setExpanded() {
            // Only expanded while the listbox holds options a user can choose.
            $search.attr('aria-expanded', isOpen() && getOptions().length ? 'true' : 'false');
        }

        function hideResults() {
            window.clearTimeout(searchTimer);
            searchToken += 1;

            if (searchRequest) {
                searchRequest.abort();
                searchRequest = null;
            }

            $results.empty().attr('hidden', true);
            $search.removeAttr('aria-activedescendant');
            setExpanded();
        }

        function positionResults() {
            var rect = $search[0].getBoundingClientRect();

            // Fixed so the list is not clipped by the filter bar; it stays next to the input in the DOM.
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

        // Status text is shown in the list for sighted users and read from the live region.
        function showStatus(message, speak) {
            $results.empty();
            $('<li />')
                .addClass(emptyClass)
                .attr('aria-hidden', 'true')
                .text(message)
                .appendTo($results);
            $results.removeAttr('hidden');
            $search.removeAttr('aria-activedescendant');
            setExpanded();
            positionResults();

            if (speak) {
                announce(message);
            }
        }

        function appendOption(value, label) {
            var index = getOptions().length;

            $('<li />')
                .addClass(optionClass)
                .attr({
                    id: listId + '-option-' + index,
                    role: 'option',
                    'aria-selected': 'false',
                    'data-value': value,
                    'data-label': label
                })
                .text(label)
                .appendTo($results);
        }

        function resultsMessage(count) {
            var message = 1 === count
                ? (reportsConfig.oneResult || '1 result available.')
                : (reportsConfig.manyResults || '%d results available.').replace('%d', count);

            return message + ' ' + (reportsConfig.resultsHint || 'Use the up and down arrow keys to choose one.');
        }

        function renderResults(items) {
            var rows = items || [];
            var count = 0;

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
                count += 1;
            });

            if (!count) {
                if (!settings.allLabel) {
                    showStatus(settings.emptyMessage, true);
                    return;
                }

                $('<li />')
                    .addClass(emptyClass)
                    .attr('aria-hidden', 'true')
                    .text(settings.emptyMessage)
                    .appendTo($results);
            }

            $results.removeAttr('hidden');
            $search.removeAttr('aria-activedescendant');
            setExpanded();
            positionResults();
            announce(count ? resultsMessage(count) : settings.emptyMessage);
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

                showStatus(settings.emptyMessage, true);
            });
        }

        function openSearch() {
            showStatus(reportsConfig.searching || 'Searching…', false);
            runSearch($value.val() ? '' : $.trim($search.val()));
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

            showStatus(reportsConfig.searching || 'Searching…', false);

            searchTimer = window.setTimeout(function() {
                runSearch(term);
            }, 250);
        });

        $search.on('focus', function() {
            if (!settings.searchOnEmptyFocus || isOpen()) {
                return;
            }

            openSearch();
        });

        $results.on('mousedown', '.' + optionClass, function(event) {
            event.preventDefault();
        });

        $results.on('click', '.' + optionClass, function(event) {
            event.preventDefault();
            selectItem($(this).attr('data-value'), $(this).attr('data-label'));
            $search.trigger('focus');
        });

        $search.on('keydown', function(event) {
            var $active;

            switch (event.key) {
                case 'ArrowDown':
                case 'Down':
                    event.preventDefault();

                    if (!isOpen() || !getOptions().length) {
                        if (event.altKey || !isOpen()) {
                            if (settings.searchOnEmptyFocus || $.trim($search.val())) {
                                openSearch();
                            }
                        }
                        return;
                    }

                    if (!event.altKey) {
                        moveActive(1);
                    }
                    break;

                case 'ArrowUp':
                case 'Up':
                    if (!isOpen() || !getOptions().length) {
                        return;
                    }

                    event.preventDefault();
                    moveActive(-1);
                    break;

                case 'Enter':
                    $active = getOptions().filter('.' + activeClass);

                    // With an option highlighted, Enter picks it; otherwise it submits the filters.
                    if (isOpen() && $active.length) {
                        event.preventDefault();
                        selectItem($active.attr('data-value'), $active.attr('data-label'));
                    }
                    break;

                case 'Escape':
                case 'Esc':
                    if (isOpen()) {
                        event.preventDefault();
                        hideResults();
                    }
                    break;

                case 'Tab':
                    hideResults();
                    break;
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
            ) {
                hideResults();
            }
        });

        $(window).on('resize scroll', function() {
            if (isOpen()) {
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
            status: '.ppcart-reports-customer-status',
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
            status: '.ppcart-reports-product-status',
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

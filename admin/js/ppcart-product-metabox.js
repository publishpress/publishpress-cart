/**
 * Product settings metabox: tab list, required-field check, price type
 * buttons, payment plan summaries and repeater empty states.
 */
(function( $ ) {
    'use strict';

    var i18n = window.ppcartProductMetaboxI18n || {};
    var NOTICE_ID = 'ppcart-required-fields';
    var MAX_LISTED = 8;
    var segmentedCount = 0;
    var problems = [];
    var checking = false;
    var editorNoticeShown = false;

    function t( key, fallback ) {
        return typeof i18n[ key ] === 'string' ? i18n[ key ] : fallback;
    }

    function getRoot() {
        return $( '#ppcart-product-settings .ppcart-settings-tabs' ).first();
    }

    function postId() {
        return $( '#post_ID' ).val() || 'new';
    }

    /* Tabs ------------------------------------------------------------- */

    function getTabs( $root ) {
        return $root.find( '.ppcart-left-col [role="tab"]' );
    }

    function storeTab( panelId ) {
        try {
            window.sessionStorage.setItem( 'ppcartProductTab:' + postId(), panelId );
        } catch ( e ) {}
    }

    function storedTab() {
        try {
            return window.sessionStorage.getItem( 'ppcartProductTab:' + postId() );
        } catch ( e ) {
            return null;
        }
    }

    function selectTab( $root, $tab, focus ) {
        var panel;

        if ( ! $tab.length ) {
            return;
        }

        panel = document.getElementById( $tab.attr( 'aria-controls' ) );
        if ( ! panel ) {
            return;
        }

        getTabs( $root ).attr( { 'aria-selected': 'false', tabindex: '-1' } ).parent().removeClass( 'active' );
        $root.find( '.ppcart-right-col > .ppcart-tab' ).removeClass( 'active' ).css( 'display', '' );

        $tab.attr( { 'aria-selected': 'true', tabindex: '0' } ).parent().addClass( 'active' );
        $( panel ).addClass( 'active' );

        if ( focus ) {
            $tab.trigger( 'focus' );
        }

        storeTab( panel.id );
        $( document ).trigger( 'ppcart:product-tab-shown', [ panel ] );
    }

    function bindTabs( $root ) {
        var $initial = $();
        var hash = window.location.hash;
        var stored = storedTab();

        $root.on( 'click', '.ppcart-left-col [role="tab"]', function( e ) {
            e.preventDefault();
            selectTab( $root, $( this ), false );
        } );

        $root.on( 'keydown', '.ppcart-left-col [role="tab"]', function( e ) {
            var $tabs = getTabs( $root );
            var index = $tabs.index( this );
            var next = null;

            switch ( e.key ) {
                case 'ArrowDown':
                case 'ArrowRight':
                    next = ( index + 1 ) % $tabs.length;
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    next = ( index - 1 + $tabs.length ) % $tabs.length;
                    break;
                case 'Home':
                    next = 0;
                    break;
                case 'End':
                    next = $tabs.length - 1;
                    break;
                case ' ':
                    next = index;
                    break;
                default:
                    return;
            }

            e.preventDefault();
            selectTab( $root, $tabs.eq( next ), true );
        } );

        if ( hash && hash.indexOf( '#ppcart-tab-' ) === 0 ) {
            $initial = getTabs( $root ).filter( '[aria-controls="' + hash.slice( 1 ).replace( /"/g, '' ) + '"]' );
        }
        if ( ! $initial.length && stored ) {
            $initial = getTabs( $root ).filter( '[aria-controls="' + stored.replace( /"/g, '' ) + '"]' );
        }
        if ( $initial.length ) {
            selectTab( $root, $initial, false );
        }
    }

    /* Required fields -------------------------------------------------- */

    function isCheckable( el ) {
        return $( el ).is( 'input, select, textarea, .selectize-control' );
    }

    // A field counts only when nothing between it and its tab panel is hidden on
    // purpose: conditional logic, the repeater template row, a starter row the
    // user has not touched, or a turned-off sale section.
    function isInPlay( el, panel ) {
        var node;

        if ( el.disabled || $( el ).closest( '.ppcart-repeater.hidden, .ppcart-repeater.is-starter' ).length ) {
            return false;
        }

        for ( node = el; node && node !== panel; node = node.parentNode ) {
            // A collapsed repeater row is only a view state; its fields still count.
            if ( node.nodeType !== 1 || node.classList.contains( 'ppcart-repeater-content' ) ) {
                continue;
            }
            if ( node.hidden || node.style.display === 'none' || node.classList.contains( 'hide' ) ) {
                return false;
            }
        }

        return true;
    }

    function isEmpty( el ) {
        var value = $( el ).hasClass( 'selectize-control' ) ? $( el ).prev( 'select, input' ).val() : $( el ).val();

        if ( Array.isArray( value ) ) {
            return value.length === 0;
        }

        return String( value || '' ).trim() === '';
    }

    // Pro's file rows are optional until the user fills one of their fields.
    function isBlankFileRow( el ) {
        var $row = $( el ).closest( '#repeater_ppcart_files > .ppcart-repeater' );

        return $row.length > 0 &&
            ! String( $row.find( '.file_url' ).val() || '' ).trim() &&
            ! String( $row.find( '.file_name' ).val() || '' ).trim();
    }

    function fieldLabel( el ) {
        var $row = $( el ).closest( '.wrap-field, .ppcart-field' );
        var $label = $row.find( 'label' ).first().clone();
        var text;

        $label.find( '.req, .screen-reader-text' ).remove();
        text = $.trim( $label.text() ) || $( el ).attr( 'aria-label' ) || $( el ).attr( 'placeholder' ) || t( 'field', 'Field' );

        return text;
    }

    function problemLabel( problem ) {
        var $tab = $( '#ppcart-tab-link-' + problem.panel.id.replace( /^ppcart-tab-/, '' ) );
        var tabText = $.trim( $tab.find( '.ppcart-tab-nav__label' ).text() );
        var $repeater = $( problem.el ).closest( '.ppcart-repeater' );
        var parts = [ tabText ];

        if ( $repeater.length ) {
            var $title = $repeater.find( '> .handle .title-repeater' );
            var title = $.trim( $title.text() );

            // Rows without their own name share the default title, so number them.
            if ( title === $title.data( 'label' ) ) {
                title += ' ' + ( $repeater.parent().children( '.ppcart-repeater' ).index( $repeater ) + 1 );
            }
            parts.push( title );
        }
        parts.push( fieldLabel( problem.el ) );

        return parts.filter( Boolean ).join( ' › ' );
    }

    function errorId( el ) {
        if ( ! el.ppcartErrorId ) {
            el.ppcartErrorId = 'ppcart-field-error-' + Math.random().toString( 36 ).slice( 2 );
        }

        return el.ppcartErrorId;
    }

    function markError( el ) {
        var id = errorId( el );
        var $el = $( el );
        var $inputGroup = $el.closest( '.input-group' );
        var $error = $( '<p class="ppcart-field-error"></p>' ).attr( 'id', id ).text( t( 'required', 'Enter a value. This field is required.' ) );
        var describedBy = ( $el.attr( 'aria-describedby' ) || '' ).split( ' ' ).filter( Boolean );

        $el.addClass( 'error' ).attr( 'aria-invalid', 'true' );
        if ( describedBy.indexOf( id ) === -1 ) {
            describedBy.unshift( id );
            $el.attr( 'aria-describedby', describedBy.join( ' ' ) );
        }
        if ( document.getElementById( id ) ) {
            return;
        }
        // Short controls sit in a narrow input group, so the message goes below it.
        if ( $inputGroup.length ) {
            $inputGroup.after( $error );
        } else {
            $el.closest( '.wrap-field, .ppcart-field' ).first().append( $error );
        }
    }

    function clearError( el ) {
        var id = el.ppcartErrorId;
        var $el = $( el );
        var describedBy;

        $el.removeClass( 'error' ).removeAttr( 'aria-invalid' );
        if ( ! id ) {
            return;
        }

        $( document.getElementById( id ) ).remove();
        describedBy = ( $el.attr( 'aria-describedby' ) || '' ).split( ' ' ).filter( function( part ) {
            return part && part !== id;
        } );
        if ( describedBy.length ) {
            $el.attr( 'aria-describedby', describedBy.join( ' ' ) );
        } else {
            $el.removeAttr( 'aria-describedby' );
        }
    }

    function revealField( $root, problem ) {
        var $el = $( problem.el );
        var $row = $el.closest( '.ppcart-repeater' );
        var $focusTarget = $el.hasClass( 'selectize-control' ) ? $el.find( 'input' ).first() : $el;

        selectTab( $root, $( '#ppcart-tab-link-' + problem.panel.id.replace( /^ppcart-tab-/, '' ) ), false );
        $el.parents( 'details' ).attr( 'open', true );

        if ( $row.length ) {
            $row.children( '.ppcart-repeater-content' ).show();
            $row.children( '.handle' ).removeClass( 'closed' ).find( '.toggle-arrow' ).removeClass( 'closed' );
            $row.children( '.handle' ).find( '.ppcart-btn-edit' ).attr( 'aria-expanded', 'true' );
        }

        if ( problem.el.scrollIntoView ) {
            problem.el.scrollIntoView( { block: 'center' } );
        }
        $focusTarget.trigger( 'focus' );
    }

    function renderTabCounts( $root ) {
        getTabs( $root ).each( function() {
            var panelId = $( this ).attr( 'aria-controls' );
            var count = problems.filter( function( problem ) {
                return problem.panel.id === panelId;
            } ).length;
            var $count = $( this ).find( '.ppcart-tab-nav__count' );

            if ( count ) {
                $count.empty().append(
                    $( '<span aria-hidden="true"></span>' ).text( count ),
                    $( '<span class="screen-reader-text"></span>' ).text(
                        ( 1 === count ? t( 'oneEmpty', '1 required field is empty' ) : t( 'manyEmpty', '%d required fields are empty' ).replace( '%d', count ) )
                    )
                ).prop( 'hidden', false );
            } else {
                $count.empty().prop( 'hidden', true );
            }
        } );
    }

    function renderNotice( $root ) {
        var $notice = $root.find( '.ppcart-metabox-notice' );
        var $list;

        if ( ! problems.length ) {
            $notice.empty().prop( 'hidden', true );
            return;
        }

        $list = $( '<ul></ul>' );
        problems.slice( 0, MAX_LISTED ).forEach( function( problem ) {
            $( '<a href="#"></a>' ).text( problemLabel( problem ) ).on( 'click', function( e ) {
                e.preventDefault();
                revealField( $root, problem );
            } ).appendTo( $( '<li></li>' ).appendTo( $list ) );
        } );
        if ( problems.length > MAX_LISTED ) {
            $( '<li></li>' ).text( t( 'more', 'and %d more' ).replace( '%d', problems.length - MAX_LISTED ) ).appendTo( $list );
        }

        $notice.empty().append(
            $( '<p></p>' ).append(
                $( '<strong></strong>' ).text( t( 'heading', 'Some required fields are empty.' ) ),
                ' ',
                document.createTextNode( t( 'intro', 'Fill them in, then save the product again.' ) )
            ),
            $list
        ).prop( 'hidden', false );
    }

    function syncEditorNotice( $root ) {
        var notices;

        if ( ! document.body.classList.contains( 'block-editor-page' ) || ! window.wp || ! wp.data || ! wp.data.dispatch ) {
            return;
        }

        notices = wp.data.dispatch( 'core/notices' );
        if ( ! notices ) {
            return;
        }

        if ( ! problems.length ) {
            if ( editorNoticeShown ) {
                editorNoticeShown = false;
                notices.removeNotice( NOTICE_ID );
            }
            return;
        }

        editorNoticeShown = true;
        notices.createNotice(
            'warning',
            t( 'editorNotice', 'Product settings: some required fields are empty.' ),
            {
                id: NOTICE_ID,
                isDismissible: true,
                actions: [ {
                    label: t( 'showFields', 'Show fields' ),
                    onClick: function() {
                        if ( problems.length ) {
                            revealField( $root, problems[ 0 ] );
                        }
                    }
                } ]
            }
        );
    }

    function render( $root ) {
        renderTabCounts( $root );
        renderNotice( $root );
        syncEditorNotice( $root );
    }

    function checkRequired( $root ) {
        if ( checking ) {
            return problems;
        }

        checking = true;
        problems = [];

        $root.find( '.ppcart-right-col > .ppcart-tab' ).each( function() {
            var panel = this;

            $( panel ).find( '.required' ).each( function() {
                if ( ! isCheckable( this ) ) {
                    return;
                }
                if ( ! isInPlay( this, panel ) || isBlankFileRow( this ) || ! isEmpty( this ) ) {
                    clearError( this );
                    return;
                }

                markError( this );
                problems.push( { el: this, panel: panel } );
            } );
        } );

        try {
            render( $root );
        } finally {
            checking = false;
        }

        return problems;
    }

    function clearFixedField( $root, el ) {
        var before = problems.length;

        problems = problems.filter( function( problem ) {
            var target = problem.el;
            var fixed = ( target === el || $( target ).prev( 'select, input' )[ 0 ] === el ) && ! isEmpty( target );

            if ( fixed ) {
                clearError( target );
            }

            return ! fixed;
        } );

        if ( problems.length !== before ) {
            render( $root );
        }
    }

    function bindRequiredCheck( $root ) {
        var wasSaving = false;

        syncRequiredMarks( $root );
        $( '#_ppcart_on_sale, #_ppcart_schedule_sale' ).on( 'change', function() {
            syncRequiredMarks( $root );
        } );

        $root.on( 'input change', 'input, select, textarea', function( e ) {
            // Any user edit makes a starter row a real row, so its fields count.
            if ( e.originalEvent ) {
                $( this ).closest( '.ppcart-repeater.is-starter' ).removeClass( 'is-starter' );
            }
            if ( problems.length ) {
                clearFixedField( $root, this );
            }
        } );

        if ( window.wp && wp.data && wp.data.subscribe && wp.data.select ) {
            wp.data.subscribe( function() {
                var editor;
                var saving;

                // The check dispatches notices, which calls this listener again.
                if ( checking ) {
                    return;
                }

                // An error thrown here would break the block editor, so keep it in this metabox.
                try {
                    editor = wp.data.select( 'core/editor' );
                    if ( ! editor || typeof editor.isSavingPost !== 'function' ) {
                        return;
                    }

                    saving = editor.isSavingPost() && ! editor.isAutosavingPost();
                    if ( saving && ! wasSaving ) {
                        wasSaving = true;
                        checkRequired( $root );
                    }
                    wasSaving = saving;
                } catch ( error ) {
                    if ( window.console ) {
                        window.console.error( error );
                    }
                }
            } );
        }

        $( '#post' ).on( 'submit', function() {
            checkRequired( $root );
        } );

        // On an existing product, show what is still missing from the last save.
        // The load event can fire before this ready callback, so check readyState first.
        if ( window.location.pathname.indexOf( 'post-new.php' ) === -1 ) {
            var checkAfterLoad = function() {
                window.setTimeout( function() {
                    checkRequired( $root );
                }, 0 );
            };

            if ( document.readyState === 'complete' ) {
                checkAfterLoad();
            } else {
                $( window ).on( 'load', checkAfterLoad );
            }
        }
    }

    // Sale fields become required while a sale is on; keep the "*" and aria-required in step.
    function syncRequiredMarks( $root ) {
        $root.find( '.wrap-field, .ppcart-field.ppcart-row' ).each( function() {
            var $field = $( this );
            var $inputs = $field.find( 'input, select, textarea' ).not( '.ppcart-segmented__input' );
            var required = $inputs.filter( '.required' ).length > 0;
            var $label = $field.find( 'label' ).first();

            $inputs.filter( '.required' ).attr( 'aria-required', 'true' );
            $inputs.not( '.required' ).removeAttr( 'aria-required' );

            if ( required && ! $label.children( '.req' ).length ) {
                $label.append( '<span class="req" aria-hidden="true">*</span>' );
            } else if ( ! required ) {
                $label.children( '.req' ).remove();
            }
        } );
    }

    /* Price type buttons ------------------------------------------------ */

    function enhanceSegmented( scope ) {
        $( scope ).find( 'select.ppcart-segmented' ).each( function() {
            var select = this;
            var $select = $( select );
            var $label = $select.closest( '.wrap-field, .ppcart-field' ).find( 'label' ).first();
            var name = 'ppcart-segmented-' + ( ++segmentedCount );
            var $group;

            if ( select.hasAttribute( 'data-ppcart-segmented' ) || $select.closest( '.ppcart-repeater.hidden' ).length ) {
                return;
            }
            select.setAttribute( 'data-ppcart-segmented', '1' );

            if ( ! $label.attr( 'id' ) ) {
                $label.attr( 'id', name + '-label' );
            }

            $group = $( '<div class="ppcart-segmented" role="radiogroup"></div>' ).attr( 'aria-labelledby', $label.attr( 'id' ) );
            Array.prototype.forEach.call( select.options, function( option ) {
                // The form attribute keeps these helper inputs out of the saved post data.
                var $input = $( '<input type="radio" class="ppcart-segmented__input">' ).attr( {
                    name: name,
                    form: 'ppcart-segmented-ui',
                    value: option.value
                } ).prop( 'checked', option.selected );

                $( '<label class="ppcart-segmented__option"></label>' )
                    .append( $input, $( '<span></span>' ).text( option.text ) )
                    .appendTo( $group );
            } );

            $group.on( 'change', 'input', function() {
                $select.val( this.value ).trigger( 'change' );
            } );
            $select.on( 'change', function() {
                $group.find( 'input' ).each( function() {
                    this.checked = this.value === $select.val();
                } );
            } );

            $select.addClass( 'ppcart-segmented-source' ).attr( { 'aria-hidden': 'true', tabindex: '-1' } ).after( $group );
            $label.on( 'click', function( e ) {
                e.preventDefault();
                $group.find( 'input:checked' ).trigger( 'focus' );
            } );
        } );
    }

    /* Payment plan cards ------------------------------------------------ */

    function selectedText( $select ) {
        return $.trim( $select.find( 'option:selected' ).text() );
    }

    function money( $row, field ) {
        var $input = $row.find( 'input[name*="[' + field + ']"]' ).first();
        var value = $.trim( $input.val() || '' );
        var $group = $input.closest( '.input-group' );

        if ( ! value ) {
            return '';
        }
        if ( $group.find( '.input-append' ).length ) {
            return value + $.trim( $group.find( '.input-append' ).text() );
        }

        return $.trim( $group.find( '.input-prepend' ).text() ) + value;
    }

    function updatePlanSummary( $root, row ) {
        var $row = $( row );
        var $summary = $row.children( '.handle' ).find( '.ppcart-repeater-summary' );
        var $type = $row.find( 'select[name*="[product_type]"]' ).first();
        var type = $type.val();
        var parts = [];
        var price = money( $row, 'price' );
        var salePrice = money( $row, 'sale_price' );
        var saleOn = ! $root.hasClass( 'ppcart-sale-off' );

        if ( ! $summary.length ) {
            return;
        }

        if ( 'free' !== type && price ) {
            parts.push( price );
        }
        if ( 'recurring' === type ) {
            parts.push(
                ( selectedText( $row.find( 'select[name*="[frequency]"]' ).first() ) + ' ' +
                selectedText( $row.find( 'select[name*="[interval]"]' ).first() ).toLowerCase() ).trim()
            );
        } else {
            parts.push( selectedText( $type ) );
        }

        $summary.empty().append( $( '<span class="ppcart-repeater-summary__text"></span>' ).text( parts.filter( Boolean ).join( ' · ' ) ) );

        if ( saleOn && salePrice && 'free' !== type ) {
            $summary.append( $( '<span class="ppcart-chip ppcart-chip--sale"></span>' ).text( t( 'sale', 'Sale' ) + ' ' + salePrice ) );
        }
        if ( $row.find( 'input[type="checkbox"][name*="[is_hidden]"]' ).is( ':checked' ) ) {
            $summary.append( $( '<span class="ppcart-chip"></span>' ).text( t( 'hidden', 'Hidden' ) ) );
        }
    }

    function updatePlanSummaries( $root ) {
        $root.find( '#repeater_ppcart_pay_options > .ppcart-repeater' ).not( '.hidden' ).each( function() {
            updatePlanSummary( $root, this );
        } );
    }

    // Hide a plan section (for example "Billing") when conditional logic hid all its fields.
    function updateSections( scope ) {
        $( scope ).find( 'div.ppcart-repeater-section' ).each( function() {
            var shown = $( this ).find( '.wrap-field' ).filter( function() {
                return this.style.display !== 'none' && ! this.classList.contains( 'hide' );
            } ).length;

            $( this ).toggleClass( 'is-empty', 0 === shown );
        } );
    }

    function bindPlans( $root ) {
        var $plans = $root.find( '#repeater_ppcart_pay_options' );

        $root.on( 'input change', '#repeater_ppcart_pay_options :input', function() {
            var row = $( this ).closest( '.ppcart-repeater' )[ 0 ];

            updateSections( row );
            updatePlanSummary( $root, row );
        } );
        $( '#_ppcart_on_sale, #_ppcart_schedule_sale' ).on( 'change', function() {
            updatePlanSummaries( $root );
        } );

        updateSections( $plans );
        updatePlanSummaries( $root );
    }

    /* Repeater rows added or removed --------------------------------------- */

    function watchRepeaters( $root ) {
        if ( ! window.MutationObserver ) {
            return;
        }

        $root.find( 'ul.ppcart-repeaters' ).each( function() {
            var list = this;
            var $empty = $( list ).children( '.ppcart-repeater-empty' );

            new window.MutationObserver( function( mutations ) {
                mutations.forEach( function( mutation ) {
                    Array.prototype.forEach.call( mutation.addedNodes, function( node ) {
                        if ( node.nodeType === 1 && node.classList.contains( 'ppcart-repeater' ) && ! node.classList.contains( 'hidden' ) ) {
                            enhanceSegmented( node );
                            updateSections( node );
                            updatePlanSummary( $root, node );
                        }
                    } );
                } );

                if ( $empty.length ) {
                    $empty.prop( 'hidden', $( list ).children( '.ppcart-repeater' ).not( '.hidden' ).length > 0 );
                }
            } ).observe( list, { childList: true } );
        } );
    }

    $( function() {
        var $root = getRoot();

        if ( ! $root.length ) {
            return;
        }

        bindTabs( $root );
        enhanceSegmented( $root );
        bindPlans( $root );
        watchRepeaters( $root );
        bindRequiredCheck( $root );
    } );

})( jQuery );

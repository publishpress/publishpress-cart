/**
 * Repeaters
 */
(function( $ ) {
	'use strict';

	/**
	 * Clones the hidden field to add another repeater.
	 */
	$('.add-repeater').on( 'click', function( e ) {

		e.preventDefault();
        
        var parent = $(this).closest('.ppcart-repeaters');
        var hidden = parent.find('.ppcart-repeater.hidden');

		var clone = $('.ppcart-repeater.hidden', parent).clone(true);

		clone.removeClass('hidden').children('.ppcart-btn-edit').click();
		clone.insertBefore(hidden);
		clone.find('.ppcart-btn-edit').click();
		clone.find('.ppcart-unique').val(uniqid());
		
        updateNames(parent);
        initializeSelect2(clone.find('.ppcart-selectize'));
        flatpickr(clone.find('.datepicker'), {enableTime: true,dateFormat: "Y-m-d h:i K",allowInput: true});
        initializeDeferredEditors(clone);
        syncRepeaterEnabledState(clone);
        syncNotificationAdvancedState(clone);
        bindNotificationPreviewEditors(clone);
        updateNotificationPreviews(clone);

		var html = '<input class="ppcart-color-field wp-color-picker" id="_ppcart_bump_bg_color" name="_ppcart_bump_bg_color" placeholder="" type="text" autocomplete="new-password" autocorrect="off" autocapitalize="none" value="" data-lpignore="true">';
		clone.find('.wp-picker-container').replaceWith(html)
		clone.find('.ppcart-color-field').wpColorPicker(); 
		return false;

	});
    
	$('.add-condition').on( 'click', function( e ) {

		e.preventDefault();
        
        var parent = $(this).closest('.conditions');
        var hidden = parent.find('.condition.hidden');

		var clone = $('.condition.hidden', parent).clone(true);
		clone.removeClass('hidden');
		clone.insertBefore(hidden);
        updateConditionNames(parent);
        initializeSelect2(clone.find('.ppcart-selectize'));
		parent.find('.remove-condition').show();

		return false;

	});
    
    $('.remove-condition').on('click', function() {

		var parents = $(this).parents('li.condition');
        var list = parents.closest('.conditions');
		var children = list.find('li.condition');

		if ( children.length > 2 ) {
			parents.remove();
			updateConditionNames(list);
		} 

		children = list.find('li.condition');
		if ( children.length == 2 ) {
			list.find('.remove-condition').hide();
		}

		return false;

	});

	function uniqid(prefix = "", random = false) {
		const sec = Date.now() * 1000 + Math.random() * 1000;
		const id = sec.toString(16).replace(/\./g, "").padEnd(14, "0");
		return `${prefix}${id}${random ? `.${Math.trunc(Math.random() * 100000000)}`:""}`;
	};

    function getEditorSettings($textarea) {
        var stored = $textarea.attr('data-ppcart-editor-settings');
        var rows = parseInt($textarea.attr('rows'), 10) || 8;
        var settings = {
            mediaButtons: false,
            quicktags: true,
            tinymce: {
                height: Math.max(190, rows * 26),
                width: '100%',
                wpautop: true
            }
        };

        if (stored) {
            try {
                var parsed = JSON.parse(stored);
                if (parsed && parsed.media_buttons === false) {
                    settings.mediaButtons = false;
                }
                if (parsed && parsed.teeny) {
                    settings.tinymce.toolbar1 = 'bold,italic,underline,blockquote,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,undo,redo';
                }
                if (parsed && parsed.textarea_rows) {
                    settings.tinymce.height = Math.max(190, parseInt(parsed.textarea_rows, 10) * 26);
                }
            } catch (e) {
                // Use defaults when inline settings are unavailable.
            }
        }

        return settings;
    }

    function initializeDeferredEditors(scope, requireVisible) {
        if (!window.wp || !window.wp.editor || typeof window.wp.editor.initialize !== 'function') {
            return;
        }

        $(scope).find('.ppcart-deferred-editor').each(function() {
            var $textarea = $(this);
            if ($textarea.data('ppcart-editor-initialized')) {
                return;
            }

            // The hidden template row must stay a plain textarea for cloning.
            if ($textarea.closest('.ppcart-repeater').hasClass('hidden')) {
                return;
            }

            // Wait until laid out: Gutenberg moving the meta-box DOM wipes a live TinyMCE iframe.
            if (requireVisible) {
                var el = this;
                if (!$textarea.is(':visible') || !el.offsetWidth || !el.offsetHeight) {
                    return;
                }
            }

            var editorId = 'ppcart-repeater-editor-' + uniqid();
            $textarea.attr('id', editorId);
            window.wp.editor.initialize(editorId, getEditorSettings($textarea));
            $textarea.data('ppcart-editor-initialized', true);
            setTimeout(function() {
                bindNotificationPreviewEditors($textarea.closest('.ppcart-repeater'));
                updateNotificationPreview($textarea.closest('.ppcart-repeater'));
            }, 300);
        });
    }

    function getPendingDeferredEditors() {
        return $('.ppcart-deferred-editor').filter(function() {
            var $textarea = $(this);
            if ($textarea.data('ppcart-editor-initialized')) {
                return false;
            }
            if ($textarea.closest('.ppcart-repeater').hasClass('hidden')) {
                return false;
            }
            return true;
        });
    }

    // Polls because Gutenberg mounts/moves the meta-box area after load and the panel may start collapsed.
    function schedulePageLoadDeferredEditors() {
        if (!getPendingDeferredEditors().length) {
            return;
        }

        initializeDeferredEditors(document, true);

        var attempts = 0;
        var maxAttempts = 100; // ~15s at 150ms.
        var timer = setInterval(function() {
            attempts++;
            initializeDeferredEditors(document, true);

            if (!getPendingDeferredEditors().length || attempts >= maxAttempts) {
                clearInterval(timer);
                bindNotificationPreviewEditors(document);
                updateNotificationPreviews(document);
            }
        }, 150);
    }

    function isEditorEditable(editor) {
        try {
            var iframe = editor && editor.iframeElement;
            var doc = iframe && (iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document));
            if (!doc || !doc.body) {
                return false;
            }
            return doc.body.contentEditable === 'true' || doc.designMode === 'on';
        } catch (e) {
            return false;
        }
    }

    function repairEditor($textarea) {
        if (!window.tinymce || !window.wp || !window.wp.editor) {
            return;
        }
        if ($textarea.closest('.ppcart-repeater').hasClass('hidden')) {
            return;
        }
        if ($textarea.data('ppcart-editor-repairing')) {
            return;
        }

        var editorId = $textarea.attr('id');
        var editor = editorId ? window.tinymce.get(editorId) : null;

        if (!editor) {
            return;
        }

        // Text/Code mode intentionally hides TinyMCE; do not treat it as broken.
        if (typeof editor.isHidden === 'function' && editor.isHidden()) {
            return;
        }

        if (isEditorEditable(editor)) {
            return;
        }

        $textarea.data('ppcart-editor-repairing', true);

        var content = '';
        try {
            content = editor.getContent();
        } catch (e) {
            content = $textarea.val() || '';
        }

        window.wp.editor.remove(editorId);
        window.wp.editor.initialize(editorId, getEditorSettings($textarea));
        $textarea.data('ppcart-editor-initialized', true);

        var newEditor = window.tinymce.get(editorId);
        if (newEditor) {
            try {
                newEditor.setContent(content);
                newEditor.focus();
            } catch (e) {
                // Fall back to whatever the textarea holds.
            }
        }

        setTimeout(function() {
            $textarea.removeData('ppcart-editor-repairing');
            var $repeater = $textarea.closest('.ppcart-repeater');
            bindNotificationPreviewEditors($repeater);
            updateNotificationPreview($repeater);
        }, 200);
    }

    function repairEditors(scope) {
        $(scope).find('.ridmessage textarea.wp-editor-area, .ridmessage textarea.ppcart-deferred-editor').each(function() {
            repairEditor($(this));
        });
    }

    function isRepeaterEnabled($repeater) {
        var $input = $repeater.find('.ppcart-repeater-enable-input').first();
        if (!$input.length) {
            return true;
        }
        return $input.is(':checked');
    }

    function syncRepeaterEnabledState(scope) {
        $(scope).each(function() {
            var $repeater = $(this).hasClass('ppcart-repeater') ? $(this) : $(this).find('.ppcart-repeater');
            $repeater.each(function() {
                var $row = $(this);
                if (!$row.find('.ppcart-repeater-enable-input').length) {
                    return;
                }
                $row.toggleClass('is-disabled', !isRepeaterEnabled($row));
            });
        });
    }

    function syncNotificationAdvancedState(scope) {
        $(scope).find('.ppcart-product-notification-advanced-toggle').each(function() {
            var $button = $(this);
            var $repeater = $button.closest('.ppcart-repeater');
            var hasValue = false;

            if ($repeater.hasClass('hidden')) {
                return;
            }

            $repeater.find('.ppcart-product-notification-advanced-field :input').each(function() {
                if ($(this).val()) {
                    hasValue = true;
                    return false;
                }
            });

            setNotificationAdvancedState($repeater, hasValue);
            updateNotificationPreview($repeater);
        });
    }

    function setNotificationAdvancedState($repeater, expanded) {
        $repeater.toggleClass('ppcart-product-notification-advanced-open', expanded);
        $repeater.find('.ppcart-product-notification-advanced-toggle').attr('aria-expanded', expanded ? 'true' : 'false');
    }

    function getNotificationFieldValue($repeater, fieldId) {
        var $field = $repeater.find('.rid' + fieldId + ' :input').not('.ppcart-insert-merge-tag').first();
        return $.trim($field.val() || '');
    }

    function getNotificationMessage($repeater) {
        var $textarea = $repeater.find('.ridmessage textarea.wp-editor-area, .ridmessage textarea.ppcart-deferred-editor').first();
        var editorId = $textarea.attr('id');

        if (editorId && window.tinymce && window.tinymce.get(editorId)) {
            var editor = window.tinymce.get(editorId);

            if (typeof editor.isHidden !== 'function' || !editor.isHidden()) {
                return editor.getContent();
            }
        }

        return $textarea.val() || '';
    }

    // On-demand preview + test-send via one shared modal (a per-row button opens it).
    var activeNotificationRepeater = null;
    var activeNotificationTrigger = null;
    var notificationPreviewRequestToken = 0;

    function notifI18n(key, fallback) {
        var strings = window.ppcartNotificationI18n || {};
        return strings[key] || fallback;
    }

    function escapeNotifText(value) {
        return String(value || '').replace(/[&<>"']/g, function(character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function gatherNotificationFields($repeater) {
        return {
            subject: getNotificationFieldValue($repeater, 'subject'),
            body: getNotificationMessage($repeater),
            from_name: getNotificationFieldValue($repeater, 'from_name'),
            from_email: getNotificationFieldValue($repeater, 'from_email'),
            reply_to: getNotificationFieldValue($repeater, 'reply_to'),
            bcc: getNotificationFieldValue($repeater, 'bcc'),
            send_to: getNotificationFieldValue($repeater, 'send_to'),
            send_to_email: getNotificationFieldValue($repeater, 'send_to_email')
        };
    }

    function getNotificationModal() {
        return $('[data-ppcart-notif-modal]').first();
    }

    function getModalFocusables($modal) {
        return $modal
            .find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')
            .filter(':visible');
    }

    function focusFirstInModal($modal) {
        var $focusables = getModalFocusables($modal);
        if ($focusables.length) {
            $focusables.first().trigger('focus');
        }
    }

    function trapModalTab($modal, e) {
        var $focusables = getModalFocusables($modal);
        var first;
        var last;

        if (!$focusables.length) {
            return;
        }

        first = $focusables.first().get(0);
        last = $focusables.last().get(0);

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function notificationMessageDocument(message) {
        return '<!doctype html><html><head><meta charset="utf-8"><title>' +
            escapeNotifText(notifI18n('previewTitle', 'Email preview')) +
            '</title></head><body style="margin:0;padding:36px 24px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;color:#5a6472;text-align:center;">' +
            escapeNotifText(message) + '</body></html>';
    }

    function writeNotificationModalPreview(html) {
        var frame = getNotificationModal().find('[data-ppcart-notif-modal-frame]').get(0);
        var doc;

        if (!frame || !frame.contentWindow) {
            return;
        }

        doc = frame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }

    function setNotificationModalStatus(message, state) {
        getNotificationModal().find('[data-ppcart-notif-status]')
            .text(message || '')
            .removeClass('is-error is-success is-busy')
            .addClass(state ? 'is-' + state : '');
    }

    function setNotificationModalBusy(isBusy) {
        getNotificationModal().toggleClass('is-busy', !!isBusy);
    }

    function requestNotificationModalPreview() {
        var $repeater = activeNotificationRepeater;
        var token;

        if (!$repeater || !$repeater.length) {
            return;
        }

        if (!window.ppcart_reg_vars || !window.ppcart_reg_vars.ajax_url || !window.ppcart_reg_vars.nonce) {
            writeNotificationModalPreview(notificationMessageDocument(notifI18n('previewFailed', 'Email preview could not be generated. Please refresh the page and try again.')));
            return;
        }

        // Shared iframe: tag each request, ignore stale responses (row switch / quick reopen).
        token = ++notificationPreviewRequestToken;

        writeNotificationModalPreview(notificationMessageDocument(notifI18n('previewLoading', 'Generating preview...')));
        setNotificationModalBusy(true);

        $.post(window.ppcart_reg_vars.ajax_url, $.extend({
            action: 'ppcart_preview_product_notification_email',
            nonce: window.ppcart_reg_vars.nonce,
            product_id: $('#post_ID').val() || $('[name="post_ID"]').val() || ''
        }, gatherNotificationFields($repeater))).done(function(response) {
            if (token !== notificationPreviewRequestToken) {
                return;
            }
            if (!response || !response.success || !response.data || !response.data.html) {
                writeNotificationModalPreview(notificationMessageDocument(notifI18n('previewFailed', 'Email preview could not be generated. Please refresh the page and try again.')));
                return;
            }
            writeNotificationModalPreview(response.data.html);
        }).fail(function() {
            if (token !== notificationPreviewRequestToken) {
                return;
            }
            writeNotificationModalPreview(notificationMessageDocument(notifI18n('previewFailed', 'Email preview could not be generated. Please refresh the page and try again.')));
        }).always(function() {
            if (token === notificationPreviewRequestToken) {
                setNotificationModalBusy(false);
            }
        });
    }

    function openNotificationModal($repeater) {
        var $modal = getNotificationModal();
        var label;

        if (!$modal.length || !$repeater || !$repeater.length) {
            return;
        }

        activeNotificationRepeater = $repeater;

        label = $.trim(getNotificationFieldValue($repeater, 'notification_name'));
        $modal.find('[data-ppcart-notif-modal-title]').text(label || notifI18n('previewTitle', 'Email preview'));
        $modal.find('[data-ppcart-notif-disabled-notice]')
            .prop('hidden', isRepeaterEnabled($repeater));

        setNotificationModalStatus('');
        $modal.removeAttr('hidden').addClass('is-open');
        $('body').addClass('ppcart-notif-modal-open');

        focusFirstInModal($modal);

        requestNotificationModalPreview();
    }

    function closeNotificationModal() {
        var $modal = getNotificationModal();

        if (!$modal.length || !$modal.hasClass('is-open')) {
            return;
        }

        // Invalidate any in-flight preview response.
        notificationPreviewRequestToken++;

        $modal.attr('hidden', 'hidden').removeClass('is-open');
        $('body').removeClass('ppcart-notif-modal-open');
        setNotificationModalStatus('');
        activeNotificationRepeater = null;

        if (activeNotificationTrigger && typeof activeNotificationTrigger.focus === 'function') {
            activeNotificationTrigger.focus();
        }
        activeNotificationTrigger = null;
    }

    function sendNotificationTest() {
        var $repeater = activeNotificationRepeater;
        var $modal = getNotificationModal();
        var $button = $modal.find('[data-ppcart-notif-send-test]');
        var recipient = $.trim($modal.find('[data-ppcart-notif-test-email]').val() || '');

        if (!$repeater || !$repeater.length) {
            return;
        }

        if (!window.ppcart_reg_vars || !window.ppcart_reg_vars.ajax_url || !window.ppcart_reg_vars.nonce) {
            setNotificationModalStatus(notifI18n('testFailed', 'Test email failed to send. Please refresh the page and try again.'), 'error');
            return;
        }

        if (!recipient) {
            setNotificationModalStatus(notifI18n('testNeedEmail', 'Enter an email address to send the test to.'), 'error');
            return;
        }

        $button.prop('disabled', true);
        setNotificationModalStatus(notifI18n('testSending', 'Sending test email...'), 'busy');

        $.post(window.ppcart_reg_vars.ajax_url, $.extend({
            action: 'ppcart_send_product_notification_test',
            nonce: window.ppcart_reg_vars.nonce,
            product_id: $('#post_ID').val() || $('[name="post_ID"]').val() || '',
            test_email: recipient
        }, gatherNotificationFields($repeater))).done(function(response) {
            if (response && response.success) {
                setNotificationModalStatus((response.data && response.data.message) || notifI18n('testSent', 'Test email sent.'), 'success');
            } else {
                setNotificationModalStatus((response && response.data && response.data.message) || notifI18n('testFailed', 'Test email failed to send. Please refresh the page and try again.'), 'error');
            }
        }).fail(function() {
            setNotificationModalStatus(notifI18n('testFailed', 'Test email failed to send. Please refresh the page and try again.'), 'error');
        }).always(function() {
            $button.prop('disabled', false);
        });
    }

    // Move to <body> so position:fixed isn't affected by an ancestor transform / the post form.
    getNotificationModal().appendTo('body');

    $(document).on('click', '[data-ppcart-notif-preview]', function(e) {
        e.preventDefault();
        activeNotificationTrigger = this;
        openNotificationModal($(this).closest('.ppcart-repeater'));
    });

    $(document).on('click', '[data-ppcart-notif-send-test]', function(e) {
        e.preventDefault();
        sendNotificationTest();
    });

    $(document).on('click', '[data-ppcart-notif-modal-close]', function(e) {
        e.preventDefault();
        closeNotificationModal();
    });

    // Enter sends the test instead of submitting the post form.
    $(document).on('keydown', '[data-ppcart-notif-test-email]', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            sendNotificationTest();
        }
    });

    $(document).on('keydown', function(e) {
        var $modal = getNotificationModal();

        if (!$modal.hasClass('is-open')) {
            return;
        }

        if (e.which === 27) {
            closeNotificationModal();
        } else if (e.which === 9) {
            trapModalTab($modal, e);
        }
    });

    // No-ops: preview is on-demand via the modal now, but existing call sites still reference these.
    function updateNotificationPreview() {}
    function updateNotificationPreviews() {}
    function bindNotificationPreviewEditors() {}
    
    function initializeSelect2(selectElementObj) {
        selectElementObj.selectize({plugins: ['remove_button'],allowEmptyOption: true,items:['']});
    }

	/**
	 * Removes the selected repeater.
	 */
	$('.link-remove').on('click', function() {

		var parents = $(this).parents('li.ppcart-repeater');
        var list = parents.closest('.ppcart-repeaters');

		if ( ! parents.hasClass( 'first' ) ) {

			parents.remove();

		}

        updateNames(list);

		return false;

	});

	/**
	 * Shows/hides the selected repeater.
	 */
	$( '.ppcart-btn-edit' ).on( 'click', function() {

		var repeater = $(this).parents( '.ppcart-repeater' );
		var willExpand = $(this).children( '.toggle-arrow' ).hasClass( 'closed' );

		repeater.children( '.ppcart-repeater-content' ).slideToggle( '150' );
		$(this).children( '.toggle-arrow' ).toggleClass( 'closed' );
		$(this).parents( '.handle' ).toggleClass( 'closed' );
		$(this).attr( 'aria-expanded', willExpand ? 'true' : 'false' );

	});

    $(document).on('change', '.ppcart-repeater-enable-input', function() {
        syncRepeaterEnabledState($(this).closest('.ppcart-repeater'));
    });

    $(document).on('click mousedown', '.ppcart-repeater-enable', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', '.ppcart-product-notification-advanced-toggle', function(e) {
        e.preventDefault();
        var $repeater = $(this).closest('.ppcart-repeater');
        setNotificationAdvancedState($repeater, !$repeater.hasClass('ppcart-product-notification-advanced-open'));
        updateNotificationPreview($repeater);
    });

    $(document).on(
        'input change keyup',
        '.ridnotification_name :input, .ridsend_to :input, .ridsend_to_email :input, .ridfrom_name :input, .ridfrom_email :input, .ridsubject :input, .ridmessage textarea, .ridmessage .ppcart-insert-merge-tag',
        function() {
            updateNotificationPreview($(this).closest('.ppcart-repeater'));
        }
    );

    $(document).on('click', '.ridmessage .quicktags-toolbar input, .ridmessage .wp-switch-editor', function() {
        var $repeater = $(this).closest('.ppcart-repeater');
        setTimeout(function() {
            bindNotificationPreviewEditors($repeater);
            updateNotificationPreview($repeater);
        }, 100);
    });

    // Repair editors whose iframe lost its editable state (e.g. after a meta-box DOM move).
    $(document).on('mousedown', '.ridmessage .mce-edit-area, .ridmessage .wp-editor-wrap.tmce-active', function() {
        var $textarea = $(this).closest('.wp-editor-wrap').find('textarea.wp-editor-area, textarea.ppcart-deferred-editor').first();
        if ($textarea.length) {
            repairEditor($textarea);
        }
    });

    // Lazily init deferred textareas the page-load poll never reached (panel collapsed past its window).
    $(document).on('focusin click', 'textarea.ppcart-deferred-editor', function() {
        var $textarea = $(this);

        if ($textarea.data('ppcart-editor-initialized') || $textarea.closest('.ppcart-repeater').hasClass('hidden')) {
            return;
        }

        initializeDeferredEditors($textarea.closest('[data-ppcart-editor-field]').get(0) || $textarea.parent().get(0));

        var editorId = $textarea.attr('id');
        if (editorId && window.tinymce) {
            setTimeout(function() {
                var editor = window.tinymce.get(editorId);
                if (editor && (typeof editor.isHidden !== 'function' || !editor.isHidden())) {
                    editor.focus();
                }
            }, 100);
        }
    });

	/**
	 * Changes the title of the repeater header as you type
	 */
	$( '.repeater-title' ).each(function(){

			var repeater = $(this).parents( '.ppcart-repeater' );
			var fieldval = $(this).val();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			}
	});
    $(function(){

		$( '.repeater-title' ).on( 'keyup', function(){

			var repeater = $(this).parents( '.ppcart-repeater' );
			var fieldval = $(this).val();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			} else {

				repeater_title.text( repeater_title.data('title') );

			}

		});

		$( 'select.repeater-title' ).each(function(){

			var repeater = $(this).parents( '.ppcart-repeater' );
			var fieldval = $(this).find('option:selected').text();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {
				repeater_title.text( fieldval );
			}

		});

		$( 'select.repeater-title' ).on( 'change', function(){

			var repeater = $(this).parents( '.ppcart-repeater' );
			var fieldval = $(this).find('option:selected').text();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			} else {

				repeater_title.text( repeater_title.data('title') );

			}

		});

	});

	/**
	 * Makes the repeaters sortable.
	 */
	$(function() {
        if($( '.ppcart-repeaters' ).length > 0){
            $( '.ppcart-repeaters' ).sortable({
                cursor: 'move',
                handle: '.handle',
                cancel: 'input,textarea,button,select,option,.ppcart-repeater-enable',
                items: '.ppcart-repeater',
                opacity: 0.6,
                stop: function (event, ui) {
                    updateNames($(this));
                    // Sorting moves rows in the DOM, which wipes TinyMCE iframes.
                    var $list = $(this);
                    setTimeout(function() {
                        repairEditors($list);
                    }, 50);
                }
            });
        }
        schedulePageLoadDeferredEditors();
        syncRepeaterEnabledState(document);
        syncNotificationAdvancedState(document);
        bindNotificationPreviewEditors(document);
        updateNotificationPreviews(document);
        setTimeout(function() {
            bindNotificationPreviewEditors(document);
            updateNotificationPreviews(document);
        }, 1000);
	});
    
    function updateNames($list) {
        if($list.attr('id') == 'repeater_ppcart_default_fields' || $list.attr('id') == 'repeater_ppcart_address_fields') return;
        $list.find('.ppcart-repeater:visible').each(function (idx) {
            var $inp = $(this).find(':input');
            $inp.each(function () {
                if (!this.name) {
                    return;
                }

				// console.log(this.name);
                var name = updateIndex(this.name, idx);
                this.name = name; 

                if ($(this).closest('.wp-editor-wrap').length) {
                    return;
                }

                this.id = name;
                $(this).next('label').attr('for',name);
            });
        });
    }

	function updateConditionsIndex(string, idx) {
		// Find the last occurrence of a number enclosed in square brackets
		const regex = /\[conditions\]\[\w*\](?!.*\[conditions\]\[\w+\])/g;
		const matches = string.match(regex);
	  
		if (matches && matches.length > 0) {
		  const lastMatch = matches[matches.length - 1];
	  
		  // Replace the last occurrence with "[x]"
		  const replacedString = string.replace(lastMatch, "[conditions]["+idx+"]");
		  return replacedString;
		}
	  
		return string;
	  }

	function updateIndex(string, idx) {
		// Find the last occurrence of a number enclosed in square brackets

		let regex = /\[\w*\](?!.*\[\w+\])/g;
		
		if(string.includes('[conditions][]')) {
			regex = /\[conditions\]\[\w*\](?!.*\[conditions\]\[\w+\])/g;
		}
		
		const matches = string.match(regex);
	  
		if (matches && matches.length > 0) {
		  const lastMatch = matches[matches.length - 1];
	  
		  // Replace the last occurrence with "[x]"
		  let replacedString = string.replace(lastMatch, "["+idx+"]");
		  if(string.includes('[conditions][]')) {
			replacedString = string.replace(lastMatch, "[conditions]["+idx+"]");
		  }
		  
		  return replacedString;
		}
	  
		return string;
	  }
    
    function updateConditionNames($list) {
        $list.find('.condition:visible').each(function (idx) {
            var $inp = $(this).find(':input');
            $inp.each(function () {
                var name = this.name.replace(/(\[\w*\])$/, '[' + idx + ']');
                this.name = name; 
                this.id = name; 
                $(this).next('label').attr('for',name);
            });
        });
    }

})( jQuery );

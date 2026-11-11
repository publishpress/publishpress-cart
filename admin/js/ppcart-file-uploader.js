(function( $ ) {

	'use strict';

	$(function() {

		var field, upload, remove;

		//Opens the Media Library, assigns chosen file URL to input field, switches links
		$( '.upload-file' ).on( 'click', function( e ) {          

            var parent = $(this).closest('.ppcart-field, .wrap-field, td');            
            field = parent.find( '[data-id="url-file"]' );
            remove = parent.find( '.remove-file' );
            upload = parent.find( '.upload-file' );

			// Stop the anchor's default behavior
			e.preventDefault();
			var file_frame, json;

			if ( undefined !== file_frame ) {
				file_frame.open();
				return;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				button: {
					text: 'Choose File',
				},
				frame: 'select',
				multiple: false,
				title: 'Choose File'
			});

			file_frame.on( 'select', function() {
				json = file_frame.state().get( 'selection' ).first().toJSON();
				if ( 0 > $.trim( json.url.length ) ) {
					return;
				}
				
				/*
				View all the properties in the console available from the returned JSON object
				for ( var property in json ) {
					console.log( property + ': ' + json[ property ] );
				}*/

				field.val( json.url );
				upload.toggleClass( 'hide' );
				remove.toggleClass( 'hide' );
			});

			file_frame.open();
		});

		//Remove value from input, switch links
		$( '.remove-file' ).on( 'click', function( e ) {
			// Stop the anchor's default behavior
			e.preventDefault();
            var parent = $(this).closest('.wrap-field, .ppcart-field, td');
            field = parent.find( '[data-id="url-file"]' );
            remove = parent.find( '.remove-file' );
            upload = parent.find( '.upload-file' );

			// clear the value from the input
			field.val('');
			
			// change the link message
			upload.toggleClass( 'hide' );
			remove.toggleClass( 'hide' );
		});

		// Handle upload
		$( '.ppcart-upload-file' ).on( 'click', function( e ) {
			// Stop the anchor's default behavior
			e.preventDefault();
            var parent = $(this).closest('.wrap-field, .ppcart-field, td');
            parent.find( '[name="async-upload"]' ).click();
		});

        $( '.ppcart-upload-file-field' ).on('change', function(e) {
            e.preventDefault();

            var $fileInput = $(this);
            var file = this.files && this.files[0];
            var $parent = $fileInput.closest('.wrap-field, .ppcart-field, td');
            var labels = window.ppcartUploaderI18n || {};
            var $status = $parent.find('.ppcart-upload-status');

            // Cancelling the file picker does not start an upload.
            if (!file || $fileInput.prop('disabled')) {
                return;
            }
            if (!$status.length) {
                $status = $('<p class="description ppcart-upload-status" role="status" aria-live="polite" aria-atomic="true"></p>');
                $parent.find('.input-group').first().after($status);
            }

            function showStatus(message, state) {
                $status.removeClass('is-error is-success').addClass(state || '').text(message);
            }

            function errorMessage(response) {
                var data = response && response.data;
                if (data && typeof data.message === 'string' && data.message.trim()) {
                    return data.message;
                }
                return labels.failed || 'Upload failed. Please try again.';
            }

            var formData = new FormData();
            formData.append('action', 'upload-attachment');
            formData.append('async-upload', file);
            formData.append('name', file.name);
            formData.append('type', 'ppcart_upload');
            formData.append('_wpnonce', ppcart_reg_vars.media_nonce);

            $fileInput.prop('disabled', true);
            $parent.attr('aria-busy', 'true');
            showStatus(labels.uploading || 'Uploading file…');

            $.ajax({
                url: ppcart_reg_vars.upload_url,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                type: 'POST',
                success: function(response) {
                    if (response && response.success && response.data && typeof response.data.url === 'string' && response.data.url.trim()) {
                        $parent.find('[data-id="url-file"]').val(response.data.url).trigger('input').trigger('change');
                        showStatus(labels.uploaded || 'File uploaded successfully.', 'is-success');
                    } else {
                        showStatus(errorMessage(response), 'is-error');
                    }
                },
                error: function(xhr) {
                    showStatus(errorMessage(xhr.responseJSON), 'is-error');
                },
                complete: function() {
                    // Reset so choosing the same file again can retry a failed upload.
                    $fileInput.val('').prop('disabled', false);
                    $parent.removeAttr('aria-busy');
                }
            });
        });
	});
	
})( jQuery );
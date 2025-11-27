/**
 * Invoice Creator Settings JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var mediaUploader;

        // Upload logo button
        $('#upload_logo_button').on('click', function(e) {
            e.preventDefault();

            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Create the media uploader
            mediaUploader = wp.media({
                title: 'Choose Company Logo',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // When an image is selected, run a callback
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#company_logo').val(attachment.url);
                $('#logo_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                $('#remove_logo_button').show();
            });

            // Open the uploader dialog
            mediaUploader.open();
        });

        // Remove logo button
        $('#remove_logo_button').on('click', function(e) {
            e.preventDefault();
            $('#company_logo').val('');
            $('#logo_preview').html('');
            $(this).hide();
        });
    });

})(jQuery);

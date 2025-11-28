jQuery(document).ready(function($) {
    // Handle status dropdown changes
    $('.invoice-status-select').on('change', function() {
        var $select = $(this);
        var invoiceId = $select.data('invoice-id');
        var nonce = $select.data('nonce');
        var newStatus = $select.val();
        var originalStatus = $select.data('status');

        // Update the data-status attribute and apply the color class
        $select.attr('data-status', newStatus);

        // Send AJAX request to update status
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_invoice_status',
                invoice_id: invoiceId,
                status: newStatus,
                _wpnonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the data-status attribute for styling
                    $select.data('status', newStatus);
                } else {
                    // Revert to original status on error
                    $select.val(originalStatus);
                    $select.attr('data-status', originalStatus);
                    alert(response.data.message || 'Failed to update status.');
                }
            },
            error: function() {
                // Revert to original status on error
                $select.val(originalStatus);
                $select.attr('data-status', originalStatus);
                alert('An error occurred while updating the status.');
            }
        });
    });

    // Set initial data-status attribute for all selects
    $('.invoice-status-select').each(function() {
        var $select = $(this);
        var currentStatus = $select.val();
        $select.attr('data-status', currentStatus);
    });
});

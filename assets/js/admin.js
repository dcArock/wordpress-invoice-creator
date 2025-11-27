/**
 * Invoice Creator Admin JavaScript
 */

(function($) {
    'use strict';

    var InvoiceCreator = {
        itemIndex: 0,

        init: function() {
            this.setupLineItems();
            this.setupAutoCalculations();
            this.setupTitleSync();
            this.updateItemNumbers();
        },

        /**
         * Setup line items functionality
         */
        setupLineItems: function() {
            var self = this;

            // Set initial index based on existing items
            self.itemIndex = $('.line-item').length;

            // Add line item
            $(document).on('click', '#add-line-item', function(e) {
                e.preventDefault();
                self.addLineItem();
            });

            // Remove line item
            $(document).on('click', '.remove-line-item', function(e) {
                e.preventDefault();
                if (confirm(invoiceCreatorData.confirmDelete)) {
                    $(this).closest('.line-item').fadeOut(300, function() {
                        $(this).remove();
                        self.updateItemNumbers();
                        self.calculateTotals();
                    });
                }
            });

            // Update totals when line item price changes
            $(document).on('input', '.line-item-price', function() {
                self.calculateTotals();
            });
        },

        /**
         * Add a new line item
         */
        addLineItem: function() {
            var template = $('#line-item-template').html();
            var newItem = template.replace(/\{\{INDEX\}\}/g, this.itemIndex);
            $('#line-items-container').append(newItem);
            this.itemIndex++;
            this.updateItemNumbers();

            // Focus on the first input of the new item
            $('#line-items-container .line-item:last-child input:first').focus();
        },

        /**
         * Update item numbers
         */
        updateItemNumbers: function() {
            $('.line-item').each(function(index) {
                $(this).find('.item-num').text(index + 1);
            });
        },

        /**
         * Setup auto calculations
         */
        setupAutoCalculations: function() {
            var self = this;

            // Calculate totals when prices change
            $(document).on('input', '.line-item-price', function() {
                self.calculateTotals();
            });

            // Recalculate total due when amount paid changes
            $(document).on('input', '#invoice_amount_paid', function() {
                self.calculateTotalDue();
            });

            // Recalculate total due when total changes
            $(document).on('input', '#invoice_total', function() {
                self.calculateTotalDue();
            });

            // Initial calculation
            this.calculateTotals();
        },

        /**
         * Calculate totals from line items
         */
        calculateTotals: function() {
            var total = 0;

            $('.line-item-price').each(function() {
                var price = parseFloat($(this).val()) || 0;
                total += price;
            });

            $('#invoice_total').val(total.toFixed(2));
            this.calculateTotalDue();
        },

        /**
         * Calculate total due
         */
        calculateTotalDue: function() {
            var total = parseFloat($('#invoice_total').val()) || 0;
            var amountPaid = parseFloat($('#invoice_amount_paid').val()) || 0;
            var totalDue = total - amountPaid;

            $('#invoice_total_due').val(totalDue.toFixed(2));
        },

        /**
         * Sync invoice number to post title
         */
        setupTitleSync: function() {
            // Sync invoice number to title field for WordPress
            $(document).on('input', '#invoice_number', function() {
                var invoiceNumber = $(this).val();
                if (invoiceNumber) {
                    $('#title').val('Invoice ' + invoiceNumber);
                }
            });

            // Set initial title if invoice number exists
            var initialInvoiceNumber = $('#invoice_number').val();
            if (initialInvoiceNumber && !$('#title').val()) {
                $('#title').val('Invoice ' + initialInvoiceNumber);
            }

            // Prevent user from editing the hidden title
            $('#title').on('focus', function() {
                $(this).blur();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('body').hasClass('post-type-invoice')) {
            InvoiceCreator.init();
        }
    });

})(jQuery);

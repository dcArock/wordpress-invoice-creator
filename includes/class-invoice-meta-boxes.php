<?php
/**
 * Invoice Meta Boxes Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_Meta_Boxes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_invoice', array($this, 'save_invoice_meta'), 10, 2);
        add_action('admin_footer', array($this, 'render_custom_buttons'));
        add_filter('redirect_post_location', array($this, 'redirect_after_create'), 10, 2);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Left column meta boxes
        add_meta_box(
            'invoice-details',
            __('Invoice Details', 'invoice-creator'),
            array($this, 'render_invoice_details'),
            'invoice',
            'normal',
            'high'
        );

        add_meta_box(
            'client-details',
            __('Client Details', 'invoice-creator'),
            array($this, 'render_client_details'),
            'invoice',
            'normal',
            'high'
        );

        add_meta_box(
            'invoice-notes',
            __('Notes / Comments', 'invoice-creator'),
            array($this, 'render_notes'),
            'invoice',
            'normal',
            'high'
        );

        // Right column meta boxes
        add_meta_box(
            'line-items',
            __('Line Items', 'invoice-creator'),
            array($this, 'render_line_items'),
            'invoice',
            'side',
            'high'
        );

        add_meta_box(
            'invoice-totals',
            __('Totals', 'invoice-creator'),
            array($this, 'render_totals'),
            'invoice',
            'side',
            'high'
        );
    }

    /**
     * Render invoice details meta box
     */
    public function render_invoice_details($post) {
        wp_nonce_field('invoice_meta_box', 'invoice_meta_box_nonce');

        $invoice_number = get_post_meta($post->ID, '_invoice_number', true);
        $invoice_date = get_post_meta($post->ID, '_invoice_date', true);
        $due_date = get_post_meta($post->ID, '_invoice_due_date', true);

        if (!$invoice_date) {
            $invoice_date = current_time('Y-m-d');
        }
        ?>
        <div class="invoice-field">
            <label for="invoice_number"><?php _e('Invoice Number', 'invoice-creator'); ?></label>
            <input type="text" id="invoice_number" name="invoice_number"
                   value="<?php echo esc_attr($invoice_number); ?>"
                   class="widefat">
            <p class="description"><?php _e('Auto-increments by default, but you can edit it', 'invoice-creator'); ?></p>
        </div>

        <div class="invoice-field">
            <label for="invoice_date"><?php _e('Invoice Date', 'invoice-creator'); ?></label>
            <input type="date" id="invoice_date" name="invoice_date"
                   value="<?php echo esc_attr($invoice_date); ?>"
                   class="widefat">
        </div>

        <div class="invoice-field">
            <label for="invoice_due_date"><?php _e('Due Date', 'invoice-creator'); ?></label>
            <input type="date" id="invoice_due_date" name="invoice_due_date"
                   value="<?php echo esc_attr($due_date); ?>"
                   class="widefat">
        </div>
        <?php
    }

    /**
     * Render client details meta box
     */
    public function render_client_details($post) {
        $client_name = get_post_meta($post->ID, '_invoice_client_name', true);
        $client_email = get_post_meta($post->ID, '_invoice_client_email', true);
        $client_phone = get_post_meta($post->ID, '_invoice_client_phone', true);
        $client_address = get_post_meta($post->ID, '_invoice_client_address', true);
        ?>
        <div class="invoice-field">
            <label for="client_name"><?php _e('Client Name', 'invoice-creator'); ?></label>
            <input type="text" id="client_name" name="client_name"
                   value="<?php echo esc_attr($client_name); ?>"
                   class="widefat" required>
        </div>

        <div class="invoice-field">
            <label for="client_email"><?php _e('Client Email', 'invoice-creator'); ?></label>
            <input type="email" id="client_email" name="client_email"
                   value="<?php echo esc_attr($client_email); ?>"
                   class="widefat">
        </div>

        <div class="invoice-field">
            <label for="client_phone"><?php _e('Client Phone', 'invoice-creator'); ?></label>
            <input type="text" id="client_phone" name="client_phone"
                   value="<?php echo esc_attr($client_phone); ?>"
                   class="widefat">
        </div>

        <div class="invoice-field">
            <label for="client_address"><?php _e('Client Address', 'invoice-creator'); ?></label>
            <textarea id="client_address" name="client_address"
                      rows="4" class="widefat"><?php echo esc_textarea($client_address); ?></textarea>
        </div>
        <?php
    }

    /**
     * Render notes meta box
     */
    public function render_notes($post) {
        $notes = get_post_meta($post->ID, '_invoice_notes', true);
        ?>
        <div class="invoice-field">
            <label for="invoice_notes"><?php _e('Notes / Comments', 'invoice-creator'); ?></label>
            <textarea id="invoice_notes" name="invoice_notes"
                      rows="6" class="widefat"><?php echo esc_textarea($notes); ?></textarea>
            <p class="description"><?php _e('These notes will appear at the bottom of the invoice.', 'invoice-creator'); ?></p>
        </div>
        <?php
    }

    /**
     * Render line items meta box
     */
    public function render_line_items($post) {
        $line_items = get_post_meta($post->ID, '_invoice_line_items', true);
        if (!is_array($line_items)) {
            $line_items = array();
        }
        ?>
        <div id="invoice-line-items">
            <div id="line-items-container">
                <?php
                if (!empty($line_items)) {
                    foreach ($line_items as $index => $item) {
                        $this->render_line_item_row($index, $item);
                    }
                }
                ?>
            </div>
            <button type="button" class="button button-secondary" id="add-line-item">
                <?php _e('+ Add Line Item', 'invoice-creator'); ?>
            </button>
        </div>

        <script type="text/template" id="line-item-template">
            <?php $this->render_line_item_row('{{INDEX}}', array()); ?>
        </script>
        <?php
    }

    /**
     * Render a single line item row
     */
    private function render_line_item_row($index, $item = array()) {
        $title = isset($item['title']) ? $item['title'] : '';
        $description = isset($item['description']) ? $item['description'] : '';
        $price = isset($item['price']) ? $item['price'] : '';
        ?>
        <div class="line-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="line-item-header">
                <span class="line-item-number"><?php _e('Item', 'invoice-creator'); ?> #<span class="item-num"></span></span>
                <button type="button" class="button-link-delete remove-line-item">
                    <?php _e('Remove', 'invoice-creator'); ?>
                </button>
            </div>

            <div class="line-item-fields">
                <div class="invoice-field">
                    <label><?php _e('Service Title', 'invoice-creator'); ?></label>
                    <input type="text" name="line_items[<?php echo esc_attr($index); ?>][title]"
                           value="<?php echo esc_attr($title); ?>"
                           class="widefat" required>
                </div>

                <div class="invoice-field">
                    <label><?php _e('Service Details', 'invoice-creator'); ?></label>
                    <textarea name="line_items[<?php echo esc_attr($index); ?>][description]"
                              rows="3" class="widefat"><?php echo esc_textarea($description); ?></textarea>
                </div>

                <div class="invoice-field">
                    <label><?php _e('Price', 'invoice-creator'); ?></label>
                    <input type="number" name="line_items[<?php echo esc_attr($index); ?>][price]"
                           value="<?php echo esc_attr($price); ?>"
                           step="0.01" min="0" class="widefat line-item-price" required>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render totals meta box
     */
    public function render_totals($post) {
        $total = get_post_meta($post->ID, '_invoice_total', true);
        $amount_paid = get_post_meta($post->ID, '_invoice_amount_paid', true);
        $total_due = get_post_meta($post->ID, '_invoice_total_due', true);
        ?>
        <div class="invoice-totals-inline">
            <div class="invoice-field">
                <label for="invoice_total"><?php _e('Total', 'invoice-creator'); ?></label>
                <input type="number" id="invoice_total" name="invoice_total"
                       value="<?php echo esc_attr($total); ?>"
                       step="0.01" min="0" class="widefat">
            </div>

            <div class="invoice-field">
                <label for="invoice_amount_paid"><?php _e('Paid', 'invoice-creator'); ?></label>
                <input type="number" id="invoice_amount_paid" name="invoice_amount_paid"
                       value="<?php echo esc_attr($amount_paid); ?>"
                       step="0.01" min="0" class="widefat">
            </div>

            <div class="invoice-field">
                <label for="invoice_total_due"><?php _e('Due', 'invoice-creator'); ?></label>
                <input type="number" id="invoice_total_due" name="invoice_total_due"
                       value="<?php echo esc_attr($total_due); ?>"
                       step="0.01" min="0" class="widefat">
            </div>
        </div>
        <p class="description" style="margin-top: 10px;"><?php _e('Auto-calculated (editable)', 'invoice-creator'); ?></p>
        <?php
    }

    /**
     * Save invoice meta data
     */
    public function save_invoice_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['invoice_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['invoice_meta_box_nonce'], 'invoice_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Generate or save invoice number
        if (isset($_POST['invoice_number']) && !empty($_POST['invoice_number'])) {
            // User provided or edited invoice number
            update_post_meta($post_id, '_invoice_number', sanitize_text_field($_POST['invoice_number']));
        } else {
            // Auto-generate if empty
            $invoice_number = get_post_meta($post_id, '_invoice_number', true);
            if (empty($invoice_number)) {
                $next_number = get_option('invoice_creator_next_number', 1);
                $invoice_number = 'INV-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);
                update_post_meta($post_id, '_invoice_number', $invoice_number);
                update_option('invoice_creator_next_number', $next_number + 1);
            }
        }

        // Save invoice details
        if (isset($_POST['invoice_date'])) {
            update_post_meta($post_id, '_invoice_date', sanitize_text_field($_POST['invoice_date']));
        }

        if (isset($_POST['invoice_due_date'])) {
            update_post_meta($post_id, '_invoice_due_date', sanitize_text_field($_POST['invoice_due_date']));
        }

        // Save client details
        if (isset($_POST['client_name'])) {
            update_post_meta($post_id, '_invoice_client_name', sanitize_text_field($_POST['client_name']));
        }

        if (isset($_POST['client_email'])) {
            update_post_meta($post_id, '_invoice_client_email', sanitize_email($_POST['client_email']));
        }

        if (isset($_POST['client_phone'])) {
            update_post_meta($post_id, '_invoice_client_phone', sanitize_text_field($_POST['client_phone']));
        }

        if (isset($_POST['client_address'])) {
            update_post_meta($post_id, '_invoice_client_address', sanitize_textarea_field($_POST['client_address']));
        }

        // Save notes
        if (isset($_POST['invoice_notes'])) {
            update_post_meta($post_id, '_invoice_notes', sanitize_textarea_field($_POST['invoice_notes']));
        }

        // Save line items
        if (isset($_POST['line_items']) && is_array($_POST['line_items'])) {
            $line_items = array();
            foreach ($_POST['line_items'] as $item) {
                if (!empty($item['title'])) {
                    $line_items[] = array(
                        'title' => sanitize_text_field($item['title']),
                        'description' => sanitize_textarea_field($item['description']),
                        'price' => floatval($item['price']),
                    );
                }
            }
            update_post_meta($post_id, '_invoice_line_items', $line_items);
        }

        // Save totals
        if (isset($_POST['invoice_total'])) {
            update_post_meta($post_id, '_invoice_total', floatval($_POST['invoice_total']));
        }

        if (isset($_POST['invoice_amount_paid'])) {
            update_post_meta($post_id, '_invoice_amount_paid', floatval($_POST['invoice_amount_paid']));
        }

        if (isset($_POST['invoice_total_due'])) {
            update_post_meta($post_id, '_invoice_total_due', floatval($_POST['invoice_total_due']));
        }

        // Save status
        if (isset($_POST['invoice_action'])) {
            if ($_POST['invoice_action'] === 'create') {
                update_post_meta($post_id, '_invoice_status', 'sent');
            } elseif ($_POST['invoice_action'] === 'draft') {
                update_post_meta($post_id, '_invoice_status', 'draft');
            }
        }
    }

    /**
     * Render custom buttons
     */
    public function render_custom_buttons() {
        global $post_type, $post;

        if ('invoice' !== $post_type) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Hide default publish box
            $('#submitdiv').hide();

            // Add custom buttons container
            var customButtons = '<div id="invoice-custom-buttons" style="clear:both; padding-top:20px;">' +
                '<input type="hidden" name="invoice_action" id="invoice_action" value="">' +
                '<button type="button" class="button button-large" id="save-draft-btn" style="margin-right:10px;">' +
                '<?php _e('Save as Draft', 'invoice-creator'); ?></button>' +
                '<button type="button" class="button button-primary button-large" id="create-invoice-btn">' +
                '<?php _e('Create Invoice', 'invoice-creator'); ?></button>' +
                '</div>';

            $('#invoice-totals').after(customButtons);

            // Save as draft
            $('#save-draft-btn').on('click', function() {
                $('#invoice_action').val('draft');
                $('#post').submit();
            });

            // Create invoice
            $('#create-invoice-btn').on('click', function() {
                $('#invoice_action').val('create');
                $('#post').submit();
            });
        });
        </script>
        <?php
    }

    /**
     * Redirect after creating invoice
     */
    public function redirect_after_create($location, $post_id) {
        if (get_post_type($post_id) !== 'invoice') {
            return $location;
        }

        // Check if this is a newly created invoice (not an update)
        $is_new = get_post_meta($post_id, '_invoice_created', true);

        if (isset($_POST['invoice_action']) && $_POST['invoice_action'] === 'create' && empty($is_new)) {
            // Mark this invoice as created
            update_post_meta($post_id, '_invoice_created', '1');

            // Store the invoice URL in a transient to open in new tab
            $invoice_url = add_query_arg('print', '1', get_permalink($post_id));
            set_transient('invoice_created_' . get_current_user_id(), $invoice_url, 30);

            // Redirect to All Invoices page
            $location = admin_url('edit.php?post_type=invoice&page=all-invoices&invoice_created=1');
        }

        return $location;
    }
}

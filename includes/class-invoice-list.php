<?php
/**
 * Invoice List Page Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_List_Page {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 5);
        add_action('admin_post_duplicate_invoice', array($this, 'duplicate_invoice'));
        add_action('admin_post_delete_invoice', array($this, 'delete_invoice'));
        add_action('wp_ajax_update_invoice_status', array($this, 'update_invoice_status'));
        add_action('admin_post_publish_draft_invoice', array($this, 'publish_draft_invoice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        // Add "All Invoices" submenu first
        add_submenu_page(
            'edit.php?post_type=invoice',
            __('All Invoices', 'invoice-creator'),
            __('All Invoices', 'invoice-creator'),
            'edit_posts',
            'all-invoices',
            array($this, 'render_page')
        );

        // Add "Create New Invoice" submenu second
        add_submenu_page(
            'edit.php?post_type=invoice',
            __('Create New Invoice', 'invoice-creator'),
            __('Create New Invoice', 'invoice-creator'),
            'edit_posts',
            'post-new.php?post_type=invoice'
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'invoice_page_all-invoices') {
            return;
        }

        wp_enqueue_style(
            'invoice-list-page',
            INVOICE_CREATOR_PLUGIN_URL . 'assets/css/invoice-list.css',
            array(),
            INVOICE_CREATOR_VERSION
        );

        wp_enqueue_script(
            'invoice-list-page',
            INVOICE_CREATOR_PLUGIN_URL . 'assets/js/invoice-list.js',
            array('jquery'),
            INVOICE_CREATOR_VERSION,
            true
        );
    }

    /**
     * Render the page
     */
    public function render_page() {
        // Handle messages
        $message = '';
        if (isset($_GET['duplicated']) && $_GET['duplicated'] === '1') {
            $message = '<div class="notice notice-success is-dismissible"><p>' . __('Invoice duplicated successfully.', 'invoice-creator') . '</p></div>';
        }
        if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            $message = '<div class="notice notice-success is-dismissible"><p>' . __('Invoice deleted successfully.', 'invoice-creator') . '</p></div>';
        }
        if (isset($_GET['status_updated']) && $_GET['status_updated'] === '1') {
            $message = '<div class="notice notice-success is-dismissible"><p>' . __('Invoice status updated successfully.', 'invoice-creator') . '</p></div>';
        }
        if (isset($_GET['invoice_created']) && $_GET['invoice_created'] === '1') {
            $message = '<div class="notice notice-success is-dismissible"><p>' . __('Invoice created successfully.', 'invoice-creator') . '</p></div>';

            // Get the invoice URL from transient and open in new tab
            $invoice_url = get_transient('invoice_created_' . get_current_user_id());
            if ($invoice_url) {
                delete_transient('invoice_created_' . get_current_user_id());
                ?>
                <script type="text/javascript">
                    window.open(<?php echo wp_json_encode($invoice_url); ?>, '_blank');
                </script>
                <?php
            }
        }

        // Get filter and search parameters
        $current_status = isset($_GET['invoice_status']) ? sanitize_text_field($_GET['invoice_status']) : '';
        $search_client = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        // Build query args
        $args = array(
            'post_type' => 'invoice',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft'),
            'orderby' => $orderby === 'invoice_number' ? 'meta_value' : $orderby,
            'order' => $order
        );

        if ($orderby === 'invoice_number') {
            $args['meta_key'] = '_invoice_number';
        }

        // Add meta query for status filter
        if (!empty($current_status)) {
            $args['meta_query'] = array(
                array(
                    'key' => '_invoice_status',
                    'value' => $current_status,
                    'compare' => '='
                )
            );
        }

        // Add meta query for client search
        if (!empty($search_client)) {
            if (isset($args['meta_query'])) {
                $args['meta_query']['relation'] = 'AND';
            } else {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key' => '_invoice_client_name',
                'value' => $search_client,
                'compare' => 'LIKE'
            );
        }

        $invoices = get_posts($args);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('All Invoices', 'invoice-creator'); ?></h1>
            <a href="<?php echo admin_url('post-new.php?post_type=invoice'); ?>" class="page-title-action"><?php _e('Add New', 'invoice-creator'); ?></a>
            <hr class="wp-header-end">

            <?php echo $message; ?>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="filter-by-status" class="screen-reader-text"><?php _e('Filter by status', 'invoice-creator'); ?></label>
                    <select name="invoice_status" id="filter-by-status">
                        <option value=""><?php _e('All statuses', 'invoice-creator'); ?></option>
                        <option value="new" <?php selected($current_status, 'new'); ?>><?php _e('New', 'invoice-creator'); ?></option>
                        <option value="sent" <?php selected($current_status, 'sent'); ?>><?php _e('Sent', 'invoice-creator'); ?></option>
                        <option value="paid" <?php selected($current_status, 'paid'); ?>><?php _e('Paid', 'invoice-creator'); ?></option>
                        <option value="overdue" <?php selected($current_status, 'overdue'); ?>><?php _e('Overdue', 'invoice-creator'); ?></option>
                        <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>><?php _e('Cancelled', 'invoice-creator'); ?></option>
                    </select>
                    <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php _e('Filter', 'invoice-creator'); ?>">
                </div>
                <div class="alignleft actions">
                    <label for="search-client" class="screen-reader-text"><?php _e('Search clients', 'invoice-creator'); ?></label>
                    <input type="search" id="search-client" name="s" value="<?php echo esc_attr($search_client); ?>" placeholder="<?php _e('Search clients...', 'invoice-creator'); ?>">
                    <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'invoice-creator'); ?>">
                </div>
            </div>

            <?php if (empty($invoices)): ?>
                <div class="no-invoices">
                    <p><?php _e('No invoices found. Create your first invoice!', 'invoice-creator'); ?></p>
                    <a href="<?php echo admin_url('post-new.php?post_type=invoice'); ?>" class="button button-primary"><?php _e('Create Invoice', 'invoice-creator'); ?></a>
                </div>
            <?php else: ?>
                <form method="get" action="<?php echo admin_url('edit.php'); ?>">
                    <input type="hidden" name="post_type" value="invoice">
                    <input type="hidden" name="page" value="all-invoices">
                <table class="wp-list-table widefat fixed striped table-view-list invoices-table">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-date-created sortable <?php echo $orderby === 'date' ? 'sorted' : 'desc'; ?> <?php echo $orderby === 'date' ? strtolower($order) : ''; ?>">
                                <a href="<?php echo add_query_arg(array('orderby' => 'date', 'order' => $orderby === 'date' && $order === 'ASC' ? 'DESC' : 'ASC')); ?>">
                                    <span><?php _e('Date Created', 'invoice-creator'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-invoice-number sortable <?php echo $orderby === 'invoice_number' ? 'sorted' : 'desc'; ?> <?php echo $orderby === 'invoice_number' ? strtolower($order) : ''; ?>">
                                <a href="<?php echo add_query_arg(array('orderby' => 'invoice_number', 'order' => $orderby === 'invoice_number' && $order === 'ASC' ? 'DESC' : 'ASC')); ?>">
                                    <span><?php _e('Invoice #', 'invoice-creator'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-client"><?php _e('Client', 'invoice-creator'); ?></th>
                            <th scope="col" class="manage-column column-invoice-date"><?php _e('Invoice Date', 'invoice-creator'); ?></th>
                            <th scope="col" class="manage-column column-due-date"><?php _e('Due Date', 'invoice-creator'); ?></th>
                            <th scope="col" class="manage-column column-total"><?php _e('Total', 'invoice-creator'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php _e('Status', 'invoice-creator'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'invoice-creator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice):
                            $invoice_number = get_post_meta($invoice->ID, '_invoice_number', true);
                            $client_name = get_post_meta($invoice->ID, '_invoice_client_name', true);
                            $invoice_date = get_post_meta($invoice->ID, '_invoice_date', true);
                            $due_date = get_post_meta($invoice->ID, '_invoice_due_date', true);
                            $total_due = get_post_meta($invoice->ID, '_invoice_total_due', true);
                            $status = get_post_meta($invoice->ID, '_invoice_status', true);
                            $status = $status ? $status : 'new';

                            // Get date created (post creation date)
                            $date_created = get_the_date('M d, Y', $invoice->ID);

                            // Format dates
                            if ($invoice_date) {
                                $invoice_date = date('M d, Y', strtotime($invoice_date));
                            }
                            if ($due_date) {
                                $due_date = date('M d, Y', strtotime($due_date));
                            }
                        ?>
                        <tr>
                            <td class="column-date-created">
                                <?php echo esc_html($date_created); ?>
                            </td>
                            <td class="column-invoice-number">
                                <strong><?php echo esc_html($invoice_number); ?></strong>
                                <?php if ($invoice->post_status === 'draft'): ?>
                                    <span class="invoice-draft-badge" style="color: #999; font-size: 12px; margin-left: 8px; font-weight: normal;">DRAFT</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-client">
                                <?php echo esc_html($client_name ? $client_name : '-'); ?>
                            </td>
                            <td class="column-invoice-date">
                                <?php echo esc_html($invoice_date ? $invoice_date : '-'); ?>
                            </td>
                            <td class="column-due-date">
                                <?php echo esc_html($due_date ? $due_date : '-'); ?>
                            </td>
                            <td class="column-total">
                                <?php echo esc_html($total_due ? '$' . number_format((float)$total_due, 2) : '-'); ?>
                            </td>
                            <td class="column-status">
                                <?php if ($invoice->post_status === 'draft'): ?>
                                    <span style="background-color: #999; color: #fff; padding: 4px 10px; border-radius: 3px; font-size: 12px; display: inline-block;"><?php _e('DRAFT', 'invoice-creator'); ?></span>
                                <?php else: ?>
                                    <select class="invoice-status-select" data-invoice-id="<?php echo $invoice->ID; ?>" data-nonce="<?php echo wp_create_nonce('update_invoice_status_' . $invoice->ID); ?>">
                                        <option value="new" <?php selected($status, 'new'); ?>><?php _e('New', 'invoice-creator'); ?></option>
                                        <option value="sent" <?php selected($status, 'sent'); ?>><?php _e('Sent', 'invoice-creator'); ?></option>
                                        <option value="paid" <?php selected($status, 'paid'); ?>><?php _e('Paid', 'invoice-creator'); ?></option>
                                        <option value="overdue" <?php selected($status, 'overdue'); ?>><?php _e('Overdue', 'invoice-creator'); ?></option>
                                        <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Cancelled', 'invoice-creator'); ?></option>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="invoice-actions">
                                    <a href="<?php echo add_query_arg('print', '1', get_permalink($invoice->ID)); ?>"
                                       class="button button-small"
                                       target="_blank"
                                       title="<?php _e('View Invoice', 'invoice-creator'); ?>">
                                        <?php _e('View', 'invoice-creator'); ?>
                                    </a>
                                    <a href="<?php echo get_edit_post_link($invoice->ID); ?>"
                                       class="button button-small"
                                       title="<?php _e('Edit Invoice', 'invoice-creator'); ?>">
                                        <?php _e('Edit', 'invoice-creator'); ?>
                                    </a>
                                    <?php if ($invoice->post_status === 'draft'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=publish_draft_invoice&invoice_id=' . $invoice->ID), 'publish_draft_invoice_' . $invoice->ID); ?>"
                                       class="button button-small button-primary"
                                       title="<?php _e('Publish Invoice', 'invoice-creator'); ?>">
                                        <?php _e('Publish', 'invoice-creator'); ?>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=duplicate_invoice&invoice_id=' . $invoice->ID), 'duplicate_invoice_' . $invoice->ID); ?>"
                                       class="button button-small"
                                       title="<?php _e('Duplicate Invoice', 'invoice-creator'); ?>">
                                        <?php _e('Duplicate', 'invoice-creator'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_invoice&invoice_id=' . $invoice->ID), 'delete_invoice_' . $invoice->ID); ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this invoice?', 'invoice-creator'); ?>');"
                                       title="<?php _e('Delete Invoice', 'invoice-creator'); ?>">
                                        <?php _e('Delete', 'invoice-creator'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Duplicate invoice
     */
    public function duplicate_invoice() {
        if (!isset($_GET['invoice_id']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'invoice-creator'));
        }

        $invoice_id = intval($_GET['invoice_id']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'duplicate_invoice_' . $invoice_id)) {
            wp_die(__('Security check failed.', 'invoice-creator'));
        }

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to duplicate invoices.', 'invoice-creator'));
        }

        $original_post = get_post($invoice_id);

        if (!$original_post || $original_post->post_type !== 'invoice') {
            wp_die(__('Invoice not found.', 'invoice-creator'));
        }

        // Create duplicate
        $new_post = array(
            'post_title' => $original_post->post_title . ' (Copy)',
            'post_status' => 'draft',
            'post_type' => 'invoice',
        );

        $new_post_id = wp_insert_post($new_post);

        if ($new_post_id) {
            // Copy all meta data
            $meta_keys = array(
                '_invoice_date',
                '_invoice_due_date',
                '_invoice_client_name',
                '_invoice_client_email',
                '_invoice_client_phone',
                '_invoice_client_address',
                '_invoice_notes',
                '_invoice_line_items',
                '_invoice_total',
                '_invoice_amount_paid',
                '_invoice_total_due',
                '_invoice_status'
            );

            foreach ($meta_keys as $key) {
                $value = get_post_meta($invoice_id, $key, true);
                if ($value) {
                    update_post_meta($new_post_id, $key, $value);
                }
            }

            // Generate new invoice number
            $next_number = get_option('invoice_creator_next_number', 1);
            $prefix = get_option('invoice_creator_id_prefix', '');
            $formatted_number = str_pad($next_number, 3, '0', STR_PAD_LEFT);
            if (!empty($prefix)) {
                $invoice_number = $prefix . '-' . $formatted_number;
            } else {
                $invoice_number = 'INV-' . $formatted_number;
            }
            update_post_meta($new_post_id, '_invoice_number', $invoice_number);
            update_option('invoice_creator_next_number', $next_number + 1);

            // Redirect to edit page
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id . '&duplicated=1'));
            exit;
        } else {
            wp_die(__('Failed to duplicate invoice.', 'invoice-creator'));
        }
    }

    /**
     * Delete invoice
     */
    public function delete_invoice() {
        if (!isset($_GET['invoice_id']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'invoice-creator'));
        }

        $invoice_id = intval($_GET['invoice_id']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_invoice_' . $invoice_id)) {
            wp_die(__('Security check failed.', 'invoice-creator'));
        }

        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to delete invoices.', 'invoice-creator'));
        }

        $post = get_post($invoice_id);

        if (!$post || $post->post_type !== 'invoice') {
            wp_die(__('Invoice not found.', 'invoice-creator'));
        }

        wp_delete_post($invoice_id, true);

        wp_redirect(admin_url('edit.php?post_type=invoice&page=all-invoices&deleted=1'));
        exit;
    }

    /**
     * Publish draft invoice
     */
    public function publish_draft_invoice() {
        if (!isset($_GET['invoice_id']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'invoice-creator'));
        }

        $invoice_id = intval($_GET['invoice_id']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'publish_draft_invoice_' . $invoice_id)) {
            wp_die(__('Security check failed.', 'invoice-creator'));
        }

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to publish invoices.', 'invoice-creator'));
        }

        $post = get_post($invoice_id);

        if (!$post || $post->post_type !== 'invoice') {
            wp_die(__('Invoice not found.', 'invoice-creator'));
        }

        if ($post->post_status !== 'draft') {
            wp_die(__('This invoice is not a draft.', 'invoice-creator'));
        }

        // Update post status to publish
        wp_update_post(array(
            'ID' => $invoice_id,
            'post_status' => 'publish'
        ));

        // Update invoice status from draft to new
        $current_status = get_post_meta($invoice_id, '_invoice_status', true);
        if ($current_status === 'draft' || empty($current_status)) {
            update_post_meta($invoice_id, '_invoice_status', 'new');
        }

        wp_redirect(admin_url('edit.php?post_type=invoice&page=all-invoices&status_updated=1'));
        exit;
    }

    /**
     * Update invoice status
     */
    public function update_invoice_status() {
        if (!isset($_POST['invoice_id']) || !isset($_POST['status']) || !isset($_POST['_wpnonce'])) {
            wp_send_json_error(array('message' => __('Invalid request.', 'invoice-creator')));
        }

        $invoice_id = intval($_POST['invoice_id']);
        $status = sanitize_text_field($_POST['status']);

        if (!wp_verify_nonce($_POST['_wpnonce'], 'update_invoice_status_' . $invoice_id)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'invoice-creator')));
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to update invoice status.', 'invoice-creator')));
        }

        $post = get_post($invoice_id);

        if (!$post || $post->post_type !== 'invoice') {
            wp_send_json_error(array('message' => __('Invoice not found.', 'invoice-creator')));
        }

        $allowed_statuses = array('new', 'sent', 'paid', 'overdue', 'cancelled');
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'invoice-creator')));
        }

        update_post_meta($invoice_id, '_invoice_status', $status);

        wp_send_json_success(array('message' => __('Invoice status updated successfully.', 'invoice-creator')));
    }
}

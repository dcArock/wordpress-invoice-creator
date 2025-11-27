<?php
/**
 * Invoice Post Type Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_Post_Type {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('manage_invoice_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_invoice_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    /**
     * Register Invoice custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Invoices', 'Post type general name', 'invoice-creator'),
            'singular_name'         => _x('Invoice', 'Post type singular name', 'invoice-creator'),
            'menu_name'             => _x('Invoices', 'Admin Menu text', 'invoice-creator'),
            'name_admin_bar'        => _x('Invoice', 'Add New on Toolbar', 'invoice-creator'),
            'add_new'               => __('Add New', 'invoice-creator'),
            'add_new_item'          => __('Add New Invoice', 'invoice-creator'),
            'new_item'              => __('New Invoice', 'invoice-creator'),
            'edit_item'             => __('Edit Invoice', 'invoice-creator'),
            'view_item'             => __('View Invoice', 'invoice-creator'),
            'all_items'             => __('All Invoices', 'invoice-creator'),
            'search_items'          => __('Search Invoices', 'invoice-creator'),
            'not_found'             => __('No invoices found.', 'invoice-creator'),
            'not_found_in_trash'    => __('No invoices found in Trash.', 'invoice-creator'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'invoice'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array('title'),
        );

        register_post_type('invoice', $args);
    }

    /**
     * Set custom columns for invoice list
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Invoice Number', 'invoice-creator');
        $new_columns['client'] = __('Client Name', 'invoice-creator');
        $new_columns['total'] = __('Total', 'invoice-creator');
        $new_columns['status'] = __('Status', 'invoice-creator');
        $new_columns['date'] = __('Date', 'invoice-creator');
        return $new_columns;
    }

    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'client':
                $client_name = get_post_meta($post_id, '_invoice_client_name', true);
                echo esc_html($client_name ? $client_name : '-');
                break;

            case 'total':
                $total_due = get_post_meta($post_id, '_invoice_total_due', true);
                echo esc_html($total_due ? '$' . number_format((float)$total_due, 2) : '-');
                break;

            case 'status':
                $status = get_post_meta($post_id, '_invoice_status', true);
                $status = $status ? $status : 'draft';
                $status_labels = array(
                    'draft' => __('Draft', 'invoice-creator'),
                    'sent' => __('Sent', 'invoice-creator'),
                    'paid' => __('Paid', 'invoice-creator'),
                );
                echo '<span class="invoice-status invoice-status-' . esc_attr($status) . '">';
                echo esc_html($status_labels[$status]);
                echo '</span>';
                break;
        }
    }
}

<?php
/**
 * Plugin Name: Invoice Creator
 * Plugin URI: https://dcarock.com/wordpress
 * Description: A simple WordPress plugin to create, manage, and print professional invoices.
 * Version: 1.9
 * Author: Chris Arock
 * Author URI: https://dcarock.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: invoice-creator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('INVOICE_CREATOR_VERSION', '1.9');
define('INVOICE_CREATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INVOICE_CREATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once INVOICE_CREATOR_PLUGIN_DIR . 'includes/class-invoice-post-type.php';
require_once INVOICE_CREATOR_PLUGIN_DIR . 'includes/class-invoice-settings.php';
require_once INVOICE_CREATOR_PLUGIN_DIR . 'includes/class-invoice-meta-boxes.php';
require_once INVOICE_CREATOR_PLUGIN_DIR . 'includes/class-invoice-print.php';
require_once INVOICE_CREATOR_PLUGIN_DIR . 'includes/class-invoice-list.php';

/**
 * Main plugin class
 */
class Invoice_Creator {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init() {
        // Initialize classes
        Invoice_Post_Type::get_instance();
        Invoice_Settings::get_instance();
        Invoice_Meta_Boxes::get_instance();
        Invoice_Print::get_instance();
        Invoice_List_Page::get_instance();

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;

        if (('post.php' === $hook || 'post-new.php' === $hook) && 'invoice' === $post_type) {
            wp_enqueue_style(
                'invoice-creator-admin',
                INVOICE_CREATOR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                INVOICE_CREATOR_VERSION
            );

            wp_enqueue_script(
                'invoice-creator-admin',
                INVOICE_CREATOR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                INVOICE_CREATOR_VERSION,
                true
            );

            wp_localize_script('invoice-creator-admin', 'invoiceCreatorData', array(
                'confirmDelete' => __('Are you sure you want to delete this line item?', 'invoice-creator')
            ));
        }

        if ('invoice_page_invoice-settings' === $hook) {
            wp_enqueue_media();
            wp_enqueue_script(
                'invoice-creator-settings',
                INVOICE_CREATOR_PLUGIN_URL . 'assets/js/settings.js',
                array('jquery'),
                INVOICE_CREATOR_VERSION,
                true
            );
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Register post type
        Invoice_Post_Type::register_post_type();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set initial invoice number
        if (!get_option('invoice_creator_next_number')) {
            update_option('invoice_creator_next_number', 1);
        }
    }
}

// Initialize plugin
function invoice_creator_init() {
    return Invoice_Creator::get_instance();
}
add_action('plugins_loaded', 'invoice_creator_init');

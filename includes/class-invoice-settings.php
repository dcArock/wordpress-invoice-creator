<?php
/**
 * Invoice Settings Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'), 15);
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to Invoices menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=invoice',
            __('Invoice Settings', 'invoice-creator'),
            __('Settings', 'invoice-creator'),
            'manage_options',
            'invoice-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('invoice_creator_settings', 'invoice_creator_company_name');
        register_setting('invoice_creator_settings', 'invoice_creator_company_phone');
        register_setting('invoice_creator_settings', 'invoice_creator_company_email');
        register_setting('invoice_creator_settings', 'invoice_creator_company_website');
        register_setting('invoice_creator_settings', 'invoice_creator_company_address');
        register_setting('invoice_creator_settings', 'invoice_creator_company_logo');
        register_setting('invoice_creator_settings', 'invoice_creator_logo_height');
        register_setting('invoice_creator_settings', 'invoice_creator_id_prefix');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle form submission
        if (isset($_POST['invoice_creator_settings_nonce']) &&
            wp_verify_nonce($_POST['invoice_creator_settings_nonce'], 'invoice_creator_settings')) {

            update_option('invoice_creator_company_name', sanitize_text_field($_POST['company_name']));
            update_option('invoice_creator_company_phone', sanitize_text_field($_POST['company_phone']));
            update_option('invoice_creator_company_email', sanitize_email($_POST['company_email']));
            update_option('invoice_creator_company_website', esc_url_raw($_POST['company_website']));
            update_option('invoice_creator_company_address', sanitize_textarea_field($_POST['company_address']));
            update_option('invoice_creator_company_logo', esc_url_raw($_POST['company_logo']));
            update_option('invoice_creator_logo_height', absint($_POST['logo_height']));
            update_option('invoice_creator_id_prefix', sanitize_text_field($_POST['invoice_id_prefix']));

            echo '<div class="notice notice-success is-dismissible"><p>' .
                 __('Settings saved successfully!', 'invoice-creator') . '</p></div>';
        }

        // Get current values
        $company_name = get_option('invoice_creator_company_name', '');
        $company_phone = get_option('invoice_creator_company_phone', '');
        $company_email = get_option('invoice_creator_company_email', '');
        $company_website = get_option('invoice_creator_company_website', '');
        $company_address = get_option('invoice_creator_company_address', '');
        $company_logo = get_option('invoice_creator_company_logo', '');
        $logo_height = get_option('invoice_creator_logo_height', '');
        $id_prefix = get_option('invoice_creator_id_prefix', '');
        ?>

        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('invoice_creator_settings', 'invoice_creator_settings_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="company_name"><?php _e('Company Name', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="company_name" name="company_name"
                                       value="<?php echo esc_attr($company_name); ?>"
                                       class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="company_phone"><?php _e('Phone', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="company_phone" name="company_phone"
                                       value="<?php echo esc_attr($company_phone); ?>"
                                       class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="company_email"><?php _e('Email', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="company_email" name="company_email"
                                       value="<?php echo esc_attr($company_email); ?>"
                                       class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="company_website"><?php _e('Website', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="company_website" name="company_website"
                                       value="<?php echo esc_attr($company_website); ?>"
                                       class="regular-text">
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="company_address"><?php _e('Address', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <textarea id="company_address" name="company_address"
                                          rows="4" class="large-text"><?php echo esc_textarea($company_address); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="company_logo"><?php _e('Company Logo', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="hidden" id="company_logo" name="company_logo"
                                       value="<?php echo esc_url($company_logo); ?>">
                                <button type="button" class="button" id="upload_logo_button">
                                    <?php _e('Upload Logo', 'invoice-creator'); ?>
                                </button>
                                <button type="button" class="button" id="remove_logo_button"
                                        style="<?php echo empty($company_logo) ? 'display:none;' : ''; ?>">
                                    <?php _e('Remove Logo', 'invoice-creator'); ?>
                                </button>
                                <div id="logo_preview" style="margin-top: 10px;">
                                    <?php if ($company_logo): ?>
                                        <img src="<?php echo esc_url($company_logo); ?>"
                                             style="max-width: 200px; height: auto;">
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="logo_height"><?php _e('Logo Height (px)', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="logo_height" name="logo_height"
                                       value="<?php echo esc_attr($logo_height); ?>"
                                       class="small-text" placeholder="200" min="1">
                                <p class="description">
                                    <?php _e('Set the maximum height for your logo in pixels on printed invoices.', 'invoice-creator'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="invoice_id_prefix"><?php _e('Invoice ID Prefix', 'invoice-creator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="invoice_id_prefix" name="invoice_id_prefix"
                                       value="<?php echo esc_attr($id_prefix); ?>"
                                       class="regular-text" placeholder="INV">
                                <p class="description">
                                    <?php _e('This prefix will be added to all invoice IDs (e.g., "INV-001").', 'invoice-creator'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save Settings', 'invoice-creator')); ?>
            </form>
        </div>
        <?php
    }
}

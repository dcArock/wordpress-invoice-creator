<?php
/**
 * Invoice Print Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Invoice_Print {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('template_redirect', array($this, 'maybe_print_invoice'));
    }

    /**
     * Check if we need to print invoice
     */
    public function maybe_print_invoice() {
        if (!is_singular('invoice') || !isset($_GET['print'])) {
            return;
        }

        $this->print_invoice();
        exit;
    }

    /**
     * Print invoice template
     */
    private function print_invoice() {
        global $post;

        // Get invoice data
        $invoice_number = get_post_meta($post->ID, '_invoice_number', true);
        $invoice_date = get_post_meta($post->ID, '_invoice_date', true);
        $due_date = get_post_meta($post->ID, '_invoice_due_date', true);
        $client_name = get_post_meta($post->ID, '_invoice_client_name', true);
        $client_email = get_post_meta($post->ID, '_invoice_client_email', true);
        $client_phone = get_post_meta($post->ID, '_invoice_client_phone', true);
        $client_address = get_post_meta($post->ID, '_invoice_client_address', true);
        $line_items = get_post_meta($post->ID, '_invoice_line_items', true);
        $total = get_post_meta($post->ID, '_invoice_total', true);
        $amount_paid = get_post_meta($post->ID, '_invoice_amount_paid', true);
        $total_due = get_post_meta($post->ID, '_invoice_total_due', true);
        $notes = get_post_meta($post->ID, '_invoice_notes', true);

        // Get company data
        $company_name = get_option('invoice_creator_company_name', '');
        $company_phone = get_option('invoice_creator_company_phone', '');
        $company_email = get_option('invoice_creator_company_email', '');
        $company_website = get_option('invoice_creator_company_website', '');
        $company_address = get_option('invoice_creator_company_address', '');
        $company_logo = get_option('invoice_creator_company_logo', '');

        // Format dates
        if ($invoice_date) {
            $invoice_date = date('F d, Y', strtotime($invoice_date));
        }
        if ($due_date) {
            $due_date = date('F d, Y', strtotime($due_date));
        }

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($invoice_number); ?> - Invoice</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #2d2d2d;
                    padding: 40px;
                    max-width: 900px;
                    margin: 0 auto;
                }

                .invoice-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 40px;
                }

                .company-info {
                    flex: 1;
                }

                .company-logo {
                    max-width: 150px;
                    max-height: 200px;
                    height: auto;
                    margin-bottom: 15px;
                }

                .company-name {
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 8px;
                }

                .company-details {
                    font-size: 13px;
                    line-height: 1.8;
                    color: #5a5a5a;
                }

                .invoice-title-section {
                    text-align: right;
                    flex: 1;
                }

                .invoice-title {
                    font-size: 48px;
                    font-weight: 300;
                    margin-bottom: 20px;
                    color: #2d2d2d;
                }

                .invoice-meta {
                    font-size: 13px;
                    line-height: 1.8;
                }

                .invoice-meta strong {
                    font-weight: 600;
                }

                .invoice-parties {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 40px;
                    gap: 40px;
                }

                .bill-to {
                    flex: 1;
                }

                .bill-to h3 {
                    font-size: 12px;
                    font-weight: 700;
                    text-transform: uppercase;
                    color: #5a5a5a;
                    margin-bottom: 10px;
                }

                .bill-to-content {
                    font-size: 14px;
                    line-height: 1.8;
                }

                .amount-due {
                    font-size: 32px;
                    font-weight: 700;
                    margin: 30px 0;
                    color: #2d2d2d;
                }

                .amount-due-label {
                    font-size: 14px;
                    font-weight: 400;
                    color: #5a5a5a;
                }

                .line-items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 30px 0;
                }

                .line-items-table thead {
                    border-bottom: 2px solid #e0e0e0;
                }

                .line-items-table th {
                    text-align: left;
                    padding: 12px 8px;
                    font-size: 12px;
                    font-weight: 700;
                    text-transform: uppercase;
                    color: #5a5a5a;
                }

                .line-items-table th:last-child,
                .line-items-table td:last-child {
                    text-align: right;
                }

                .line-items-table tbody tr {
                    border-bottom: 1px solid #f0f0f0;
                }

                .line-items-table td {
                    padding: 16px 8px;
                    vertical-align: top;
                }

                .service-title {
                    font-weight: 600;
                    margin-bottom: 4px;
                }

                .service-description {
                    font-size: 13px;
                    color: #5a5a5a;
                    white-space: pre-line;
                }

                .totals-section {
                    margin-top: 30px;
                    display: flex;
                    justify-content: flex-end;
                }

                .totals-table {
                    min-width: 300px;
                }

                .totals-table tr {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                }

                .totals-table .total-row {
                    border-top: 2px solid #2d2d2d;
                    padding-top: 12px;
                    margin-top: 8px;
                    font-weight: 700;
                    font-size: 16px;
                }

                .totals-table .amount-due-row {
                    font-size: 18px;
                    font-weight: 700;
                    color: #2d2d2d;
                }

                .notes-section {
                    margin-top: 50px;
                    padding-top: 30px;
                    border-top: 1px solid #e0e0e0;
                }

                .notes-section h3 {
                    font-size: 14px;
                    font-weight: 700;
                    margin-bottom: 10px;
                }

                .notes-content {
                    font-size: 13px;
                    color: #5a5a5a;
                    white-space: pre-line;
                }

                @media print {
                    body {
                        padding: 0;
                    }

                    @page {
                        margin: 0.5in;
                    }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <!-- Header -->
                <div class="invoice-header">
                    <div class="company-info">
                        <?php if ($company_logo): ?>
                            <img src="<?php echo esc_url($company_logo); ?>" alt="<?php echo esc_attr($company_name); ?>" class="company-logo">
                        <?php endif; ?>

                        <?php if ($company_name): ?>
                            <div class="company-name"><?php echo esc_html($company_name); ?></div>
                        <?php endif; ?>

                        <div class="company-details">
                            <?php if ($company_address): ?>
                                <?php echo nl2br(esc_html($company_address)); ?><br>
                            <?php endif; ?>
                            <?php if ($company_phone): ?>
                                <?php echo esc_html($company_phone); ?><br>
                            <?php endif; ?>
                            <?php if ($company_email): ?>
                                <?php echo esc_html($company_email); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="invoice-title-section">
                        <h1 class="invoice-title">Invoice</h1>
                        <div class="invoice-meta">
                            <strong>Invoice number:</strong> <?php echo esc_html($invoice_number); ?><br>
                            <strong>Date of issue:</strong> <?php echo esc_html($invoice_date); ?><br>
                            <?php if ($due_date): ?>
                                <strong>Date due:</strong> <?php echo esc_html($due_date); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bill To -->
                <div class="invoice-parties">
                    <div class="bill-to">
                        <h3>Bill to</h3>
                        <div class="bill-to-content">
                            <strong><?php echo esc_html($client_name); ?></strong><br>
                            <?php if ($client_address): ?>
                                <?php echo nl2br(esc_html($client_address)); ?><br>
                            <?php endif; ?>
                            <?php if ($client_email): ?>
                                <?php echo esc_html($client_email); ?><br>
                            <?php endif; ?>
                            <?php if ($client_phone): ?>
                                <?php echo esc_html($client_phone); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Amount Due -->
                <div class="amount-due">
                    $<?php echo number_format((float)$total_due, 2); ?> USD
                    <span class="amount-due-label">
                        due <?php echo $due_date ? esc_html($due_date) : 'upon receipt'; ?>
                    </span>
                </div>

                <!-- Line Items -->
                <?php if (!empty($line_items) && is_array($line_items)): ?>
                <table class="line-items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($line_items as $item): ?>
                        <tr>
                            <td>
                                <div class="service-title"><?php echo esc_html($item['title']); ?></div>
                                <?php if (!empty($item['description'])): ?>
                                    <div class="service-description"><?php echo esc_html($item['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>$<?php echo number_format((float)$item['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <!-- Totals -->
                <div class="totals-section">
                    <table class="totals-table">
                        <?php if ($total): ?>
                        <tr>
                            <td>Subtotal</td>
                            <td>$<?php echo number_format((float)$total, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($amount_paid > 0): ?>
                        <tr>
                            <td>Amount Paid</td>
                            <td>-$<?php echo number_format((float)$amount_paid, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr class="amount-due-row">
                            <td>Amount Due</td>
                            <td>$<?php echo number_format((float)$total_due, 2); ?> USD</td>
                        </tr>
                    </table>
                </div>

                <!-- Notes -->
                <?php if ($notes): ?>
                <div class="notes-section">
                    <h3>Notes</h3>
                    <div class="notes-content"><?php echo esc_html($notes); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
    }
}

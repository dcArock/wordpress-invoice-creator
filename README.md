# Invoice Creator WordPress Plugin

A simple and professional WordPress plugin to create, manage, and print invoices directly from your WordPress dashboard.

## Features

- **Custom Invoice Post Type**: Create and manage invoices like WordPress posts
- **Auto-Incrementing Invoice Numbers**: Automatically generates sequential invoice numbers
- **Two-Column Layout**: Optimized editing experience on screens above 1000px
- **Client Management**: Store client details including name, email, phone, and address
- **Line Items**: Add unlimited service line items with title, description, and price
- **Automatic Calculations**: Auto-calculates totals from line items
- **Professional Print Layout**: Clean, print-ready invoice template inspired by modern invoice designs
- **Company Branding**: Add your company logo, name, contact details, and address
- **Draft & Final Invoices**: Save as draft or create final invoices
- **Customizable Settings**: Configure company information from the settings page

## Installation

1. Upload the `invoice-creator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Invoices > Settings** to configure your company information
4. Start creating invoices!

## Usage

### Creating an Invoice

1. Navigate to **Invoices > Add New**
2. Fill in the invoice details:
   - Invoice date (defaults to today)
   - Due date (optional)
3. Add client information:
   - Client name (required)
   - Client email, phone, and address (optional)
4. Add line items:
   - Click **+ Add Line Item**
   - Enter service title, description, and price
   - Add as many items as needed
5. The total will auto-calculate based on line items
6. Enter amount already paid (if any)
7. The total due will auto-calculate
8. Add notes/comments if needed
9. Click **Save as Draft** or **Create Invoice**

### Configuring Company Settings

1. Navigate to **Invoices > Settings**
2. Configure your company information:
   - Company Name
   - Phone Number
   - Email Address
   - Website
   - Physical Address
   - Company Logo (upload an image)
3. Set the next invoice number if needed
4. Click **Save Settings**

### Printing Invoices

1. Open any created invoice
2. Click the **Print Invoice** button
3. A print-friendly version will open in a new window
4. Use your browser's print function (Ctrl/Cmd + P)

## Features Details

### Auto-Incrementing Invoice Numbers
- Invoices are automatically numbered starting from INV-0001
- The numbering increments with each new invoice
- You can customize the starting number in Settings

### Two-Column Layout
- On screens wider than 1000px, the edit screen displays in two columns:
  - **Left Column (40%)**: Invoice details, client details, notes
  - **Right Column (60%)**: Line items and totals
- On smaller screens, it stacks vertically for mobile-friendly editing

### Line Items Management
- Add unlimited line items
- Each item includes:
  - Service Title
  - Service Details/Description
  - Price
- Remove items with a single click
- Totals update automatically as you add/edit items

### Professional Print Template
- Clean, modern design based on professional invoice layouts
- Company branding at the top
- Clear client information
- Itemized service list
- Subtotal and total due breakdown
- Optional notes section
- Print-optimized CSS (removes all WordPress admin elements)

## Technical Details

- **Version**: 1.11
- **Author**: Chris Arock
- **Author URI**: https://dcarock.com
- **Plugin URI**: https://dcarock.com/wordpress
- **Requires WordPress**: 5.0 or higher
- **Tested up to**: 6.4
- **License**: GPL v2 or later

## File Structure

```
invoice-creator/
├── invoice-creator.php          # Main plugin file
├── includes/
│   ├── class-invoice-post-type.php    # Custom post type registration
│   ├── class-invoice-settings.php     # Settings page
│   ├── class-invoice-meta-boxes.php   # Meta boxes for invoice fields
│   └── class-invoice-print.php        # Print template handler
├── assets/
│   ├── css/
│   │   └── admin.css            # Admin styling (2-column layout)
│   └── js/
│       ├── admin.js             # Line items management & calculations
│       └── settings.js          # Settings page (logo upload)
└── README.md                    # This file
```

## Support

For issues, questions, or feature requests, please visit:
- Website: https://dcarock.com
- Email: support@dcarock.com

## Changelog

### 1.11
- **Fixed**: Form submission now works properly using native JavaScript submit
- **Fixed**: Removed wp_update_post() calls from save handler to prevent conflicts
- **Improved**: Set WordPress post status fields directly before form submission
- **Enhanced**: Added console logging for debugging button clicks

### 1.10
- **Fixed**: Invoice save/create buttons now work correctly
- **Improved**: Rewritten button logic for better handling of draft and published invoices
- **Enhanced**: When creating/updating an invoice, it now opens in a new window for printing AND redirects the main window to All Invoices page
- **Enhanced**: Button labels now change based on invoice state:
  - New invoice: "Save as Draft" / "Create Invoice"
  - Draft invoice: "Update Draft" / "Publish Invoice"
  - Published invoice: "Update and Save as Draft" / "Update Invoice"

### 1.9.2
- UI/UX improvements

### 1.9.1
- Add automatic rewrite flush on version update

### 1.0
- Initial release
- Custom invoice post type
- Company settings page
- Line items management
- Auto-calculations
- Print functionality
- Two-column responsive layout

## License

This plugin is licensed under the GPL v2 or later.

Copyright (C) 2025 Chris Arock

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

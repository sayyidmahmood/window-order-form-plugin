Prerequisites
WordPress 5.0 or higher

PHP 7.4 or higher

Write permissions on the uploads directory

Installation Steps
1. Upload Plugin Files
Download the plugin ZIP file from GitHub

In your WordPress admin panel, go to Plugins → Add New → Upload Plugin

Choose the ZIP file and click Install Now

After installation, click Activate Plugin

2. Manual Installation (Alternative)
Extract the plugin ZIP file

Upload the window-order-form folder to your /wp-content/plugins/ directory

In WordPress admin, go to Plugins

Find "Window Order Form" and click Activate

3. Required Image Setup
The plugin uses specific window images that must be uploaded to your media library:

Go to Media → Add New in your WordPress admin

Upload these three images (ensure exact filenames):

1-window.jpg - Image for "1 Window" style

2-column.jpg - Image for "2 Window Door Style"

3-column-window.jpg - Image for "3 Column Window" style

4. Shortcode Usage
Add the form to any page or post using the shortcode:

text
[custom_window_form]
5. Admin Access
After activation, you'll find a new "Window Orders" menu item in your WordPress admin sidebar where you can:

View all orders

Export orders to CSV

Delete orders

Plugin Features
Responsive order form with live preview

PDF quote generation

Database storage of all orders

Admin management interface

CSV export functionality

Support for multiple window configurations

Troubleshooting
Common Issues
PDF generation fails:

Ensure your PHP installation has the required extensions (dom, gd, mbstring)

Check that your uploads directory is writable

Images not displaying:

Verify the three required images are in your media library with exact filenames

Check file permissions on uploaded images

Form not appearing:

Confirm the shortcode is correctly placed: [custom_window_form]

Server Requirements
PHP 7.4 or higher

WordPress 5.0 or higher

PHP extensions: dom, gd, mbstring

Support
For issues or questions, please create an issue on the GitHub repository or contact the plugin author.

Changelog
Version 1.0
Initial release

Form with customer information and window specifications

PDF generation functionality

Admin order management interface

Database storage system
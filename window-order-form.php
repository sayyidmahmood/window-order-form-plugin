<?php
/*
Plugin Name: Window Order Form
Description: A complete window order form with PDF generation and database storage
Version: 1.0
Author: Sayyid Mahmood
Text Domain: window-order-form
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wof_activate_plugin');
register_deactivation_hook(__FILE__, 'wof_deactivate_plugin');

function wof_activate_plugin() {
    // Create database table on activation
    create_window_orders_table();
    
    // Add any other activation tasks here
}

function wof_deactivate_plugin() {
    // Cleanup tasks on deactivation
    // Note: We're not dropping the table to preserve data
}

// Initialize the plugin
add_action('plugins_loaded', 'window_order_form_init');

function window_order_form_init() {
    // Register shortcode
    add_shortcode('custom_window_form', 'custom_window_form_shortcode');
    
    // Handle form submissions
    add_action('init', 'handle_pdf_generation');
    
    // Add admin menu
    add_action('admin_menu', 'window_orders_admin_menu');
    
    // Add CSS
    add_action('wp_head', 'window_order_form_styles');
}

// Database table creation
function create_window_orders_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'window_orders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_name varchar(100) NOT NULL,
        customer_email varchar(100) NOT NULL,
        customer_phone varchar(30) NOT NULL,
        customer_address text NOT NULL,
        location varchar(50) NOT NULL,
        window_data longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Shortcode handler
function custom_window_form_shortcode() {
    ob_start();
    
    if (isset($_POST['generate_pdf'])) {
        display_form_results();
    }
    
    display_window_form();
    
    return ob_get_clean();
}

// Form display function (same as your original)
function display_window_form() {
    ?>
    <div class="custom-window-form-container">
        <form method="post" enctype="multipart/form-data" id="window-order-form">
            <h3>Customer Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name*</label>
                    <input type="text" name="user_name" required>
                </div>
                <div class="form-group">
                    <label>Email*</label>
                    <input type="email" name="user_email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone*</label>
                    <input type="text" name="user_phone" required>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <select name="location_type" required>
                        <option value="">-- Select --</option>
                        <option value="Calicut">Calicut</option>
                        <option value="Ernakulam">Ernakulam</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Address*</label>
                <input type="text" name="user_address" required>
            </div>

            <h3>Window Specifications</h3>
            <div id="window-selections-container">
                <div class="window-selection" data-index="0">
                    <div class="window-specs">
                        <div class="form-group">
                            <label>Window Style*</label>
                            <select name="window_styles[0][style]" class="window-style" required>
                                <option value="">-- Select --</option>
                                <option value="1 Window">1 Window</option>
                                <option value="2 Window Door Style">2 Window Door Style</option>
                                <option value="3 Column Window">3 Column Window</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Width *</label>
                                <input type="number" name="window_styles[0][width]" min="1" step="1" required>
                            </div>
                            <div class="form-group">
                                <label>Height *</label>
                                <input type="number" name="window_styles[0][height]" min="1" step="1" required>
                            </div>
                        </div>
                        
                        <div class="form-group window-notes">
                            <label>Window Notes</label>
                            <textarea name="window_styles[0][notes]" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="window-image-container"></div>
                </div>
            </div>

            <button type="button" id="add-another-window" class="button">+ Add Another Window</button>
            
            <div class="form-submit">
                <button type="submit" name="generate_pdf" class="button primary">Generate Quote</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById("window-selections-container");
        const addButton = document.getElementById("add-another-window");
        
        const imageMap = {
            "1 Window": "<?php echo esc_url(site_url('/wp-content/uploads/1-window.jpg')); ?>",
            "2 Window Door Style": "<?php echo esc_url(site_url('/wp-content/uploads/2-column.jpg')); ?>",
            "3 Column Window": "<?php echo esc_url(site_url('/wp-content/uploads/3-column-window.jpg')); ?>"
        };

        container.addEventListener("change", function(e) {
            if (e.target.classList.contains("window-style")) {
                const selectionDiv = e.target.closest(".window-selection");
                const imageContainer = selectionDiv.querySelector(".window-image-container");
                const selectedValue = e.target.value;
                
                if (selectedValue && imageMap[selectedValue]) {
                    imageContainer.innerHTML = `
                        <div class="window-preview">
                            <img src="${imageMap[selectedValue]}" class="window-preview-image">
                            <div class="window-dimensions">
                                ${selectionDiv.querySelector('[name$="[width]"]').value || '--'}" W × 
                                ${selectionDiv.querySelector('[name$="[height]"]').value || '--'}" H
                            </div>
                        </div>
                    `;
                } else {
                    imageContainer.innerHTML = "";
                }
            }
            
            if (e.target.name && (e.target.name.includes('[width]') || e.target.name.includes('[height]'))) {
                const selectionDiv = e.target.closest(".window-selection");
                const dimDisplay = selectionDiv.querySelector(".window-dimensions");
                if (dimDisplay) {
                    dimDisplay.innerHTML = `
                        ${selectionDiv.querySelector('[name$="[width]"]').value || '--'}" W × 
                        ${selectionDiv.querySelector('[name$="[height]"]').value || '--'}" H
                    `;
                }
            }
        });

        addButton.addEventListener("click", function() {
            const index = document.querySelectorAll(".window-selection").length;
            const newSelection = document.createElement("div");
            newSelection.className = "window-selection";
            newSelection.dataset.index = index;
            newSelection.innerHTML = `
                <div class="window-specs">
                    <div class="form-group">
                        <label>Window Style*</label>
                        <select name="window_styles[${index}][style]" class="window-style" required>
                            <option value="">-- Select --</option>
                            <option value="1 Window">1 Window</option>
                            <option value="2 Window Door Style">2 Window Door Style</option>
                            <option value="3 Column Window">3 Column Window</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Width (inches)*</label>
                            <input type="number" name="window_styles[${index}][width]" min="1" step="1" required>
                        </div>
                        <div class="form-group">
                            <label>Height (inches)*</label>
                            <input type="number" name="window_styles[${index}][height]" min="1" step="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group window-notes">
                        <label>Window Notes</label>
                        <textarea name="window_styles[${index}][notes]" rows="2"></textarea>
                    </div>
                </div>
                <div class="window-image-container"></div>
                <button type="button" class="remove-window button">Remove This Window</button>
            `;
            container.appendChild(newSelection);
            
            newSelection.querySelector(".remove-window").addEventListener("click", function() {
                if (confirm("Are you sure you want to remove this window?")) {
                    container.removeChild(newSelection);
                    document.querySelectorAll(".window-selection").forEach((div, i) => {
                        div.dataset.index = i;
                        div.querySelectorAll('[name]').forEach(field => {
                            field.name = field.name.replace(/\[\d+\]/, `[${i}]`);
                        });
                    });
                }
            });
        });
    });
    </script>
    <?php
}


function display_form_results() {
    if (!isset($_POST['generate_pdf'])) return;
    
    $name = sanitize_text_field($_POST['user_name']);
    $email = sanitize_email($_POST['user_email']);
    $phone = sanitize_text_field($_POST['user_phone']);
    $address = sanitize_text_field($_POST['user_address']);
    $location_type = sanitize_text_field($_POST['location_type']);
    
    // Process window data correctly
    $window_styles = [];
    if (isset($_POST['window_styles']) && is_array($_POST['window_styles'])) {
        foreach ($_POST['window_styles'] as $window) {
            if (!empty($window['style'])) {
                $window_styles[] = [
                    'style' => sanitize_text_field($window['style']),
                    'width' => sanitize_text_field($window['width']),
                    'height' => sanitize_text_field($window['height']),
                    'notes' => sanitize_textarea_field($window['notes'])
                ];
            }
        }
    }
    
    $image_map = [
        "1 Window" => esc_url(site_url('/wp-content/uploads/1-window.jpg')),
        "2 Window Door Style" => esc_url(site_url('/wp-content/uploads/2-column.jpg')),
        "3 Column Window" => esc_url(site_url('/wp-content/uploads/3-column-window.jpg'))
    ];
    ?>
    <div class="window-form-results">
        <h2>Window Order Summary</h2>
        
        <div class="customer-info">
            <h3>Customer Information</h3>
            <div class="info-grid">
                <div><strong>Name:</strong> <?php echo $name; ?></div>
                <div><strong>Email:</strong> <?php echo $email; ?></div>
                <div><strong>Phone:</strong> <?php echo $phone; ?></div>
                <div><strong>Address:</strong> <?php echo $address; ?></div>
                <div><strong>Location:</strong> <?php echo $location_type; ?></div>
            </div>
        </div>
        
        <div class="window-specs-results">
            <h3>Window Specifications</h3>
            <?php if (!empty($window_styles)): ?>
                <div class="windows-grid">
                    <?php foreach ($window_styles as $index => $window): ?>
                        <div class="window-item">
                            <h4>Window #<?php echo $index + 1; ?></h4>
                            <div class="spec-row"><strong>Style:</strong> <?php echo $window['style']; ?></div>
                            <div class="spec-row"><strong>Dimensions:</strong> <?php echo $window['width']; ?>" × <?php echo $window['height']; ?>"</div>
                            
                            <?php if (!empty($window['notes'])): ?>
                                <div class="spec-row notes-row">
                                    <strong>Notes:</strong> <?php echo nl2br($window['notes']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($image_map[$window['style']])): ?>
                                <div class="window-image-preview">
                                    <img src="<?php echo $image_map[$window['style']]; ?>">
                                    <div class="dimensions-badge">
                                        <?php echo $window['width']; ?>" × <?php echo $window['height']; ?>"
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No windows were selected.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function handle_pdf_generation() {
    if (isset($_POST['generate_pdf'])) {
        // Validate and sanitize input data
        $name = sanitize_text_field($_POST['user_name']);
        $email = sanitize_email($_POST['user_email']);
        $phone = sanitize_text_field($_POST['user_phone']);
        $address = sanitize_text_field($_POST['user_address']);
        $location_type = sanitize_text_field($_POST['location_type']);
        
        // Process window data correctly
        $window_styles = [];
        if (isset($_POST['window_styles']) && is_array($_POST['window_styles'])) {
            foreach ($_POST['window_styles'] as $window) {
                if (!empty($window['style'])) {
                    $window_styles[] = [
                        'style' => sanitize_text_field($window['style']),
                        'width' => sanitize_text_field($window['width']),
                        'height' => sanitize_text_field($window['height']),
                        'notes' => sanitize_textarea_field($window['notes'])
                    ];
                }
            }
        }

        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'window_orders';
        
        $window_data = json_encode($window_styles); // Convert window data to JSON
        
        $wpdb->insert(
            $table_name,
            array(
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'customer_address' => $address,
                'location' => $location_type,
                'window_data' => $window_data
            ),
            array(
                '%s', // customer_name
                '%s', // customer_email
                '%s', // customer_phone
                '%s', // customer_address
                '%s', // location
                '%s'  // window_data
            )
        );

        // Load DOMPDF from plugin's lib directory
        require_once plugin_dir_path(__FILE__) . 'libs/dompdf/autoload.inc.php';
        
        // Set options with proper namespace
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        // Create DOMPDF instance with proper namespace
        $dompdf = new \Dompdf\Dompdf($options);
        // ========== END OF UPDATED CODE ==========

        $image_map = [
            "1 Window" => esc_url(site_url('/wp-content/uploads/1-window.jpg')),
            "2 Window Door Style" => esc_url(site_url('/wp-content/uploads/2-column.jpg')),
            "3 Column Window" => esc_url(site_url('/wp-content/uploads/3-column-window.jpg'))
        ];

        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);
        
        $server_image_map = [
            "1 Window" => $base_dir . '1-window.jpg',
            "2 Window Door Style" => $base_dir . '2-column.jpg',
            "3 Column Window" => $base_dir . '3-column-window.jpg'
        ];
        
        $base64_image_map = [];
        foreach ($server_image_map as $style => $path) {
            if (file_exists($path)) {
                $image_data = file_get_contents($path);
                $base64_image_map[$style] = 'data:image/jpeg;base64,' . base64_encode($image_data);
            } else {
                $base64_image_map[$style] = '';
                error_log("Window form image missing: " . $path);
            }
        }

        $html = '
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 20px; }
            .customer-info { margin-bottom: 30px; }
            .info-table { width: 100%; border-collapse: collapse; }
            .info-table td { padding: 8px; border-bottom: 1px solid #eee; }
            .window-item { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
            .window-specs { margin-left: 20px; }
            .spec-row { margin-bottom: 5px; }
            .window-image { max-width: 200px; height: auto; margin-top: 10px; }
            .dimensions { font-style: italic; color: #666; }
            .notes { margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; }
        </style>
        
        <div class="header">
            <h1>Window Order Quote</h1>
            <p>Generated on '.date('F j, Y').'</p>
        </div>
        
        <div class="customer-info">
            <h2>Customer Information</h2>
            <table class="info-table">
                <tr><td><strong>Name:</strong></td><td>'.$name.'</td></tr>
                <tr><td><strong>Email:</strong></td><td>'.$email.'</td></tr>
                <tr><td><strong>Phone:</strong></td><td>'.$phone.'</td></tr>
                <tr><td><strong>Address:</strong></td><td>'.$address.'</td></tr>
                <tr><td><strong>Location:</strong></td><td>'.$location_type.'</td></tr>
            </table>
        </div>
        
        <div class="window-specifications">
            <h2>Window Specifications</h2>';
            
            if (!empty($window_styles)) {
                foreach ($window_styles as $index => $window) {
                    $html .= '
                    <div class="window-item">
                        <h3>Window #'.($index + 1).'</h3>
                        <div class="window-specs">
                            <div class="spec-row"><strong>Style:</strong> '.$window['style'].'</div>
                            <div class="spec-row"><strong>Dimensions:</strong> '.$window['width'].'" × '.$window['height'].'"</div>';
                            
                            if (!empty($window['notes'])) {
                                $html .= '
                                <div class="notes">
                                    <strong>Notes:</strong><br>
                                    '.nl2br($window['notes']).'
                                </div>';
                            }
                            
                            if (isset($base64_image_map[$window['style']]) && !empty($base64_image_map[$window['style']])) {
                                $html .= '
                                <div style="margin-top: 10px;">
                                    <img src="'.$base64_image_map[$window['style']].'" class="window-image">
                                    <div class="dimensions">'.$window['width'].'" × '.$window['height'].'"</div>
                                </div>';
                            }
                            
                            $html .= '
                        </div>
                    </div>';
                }
            } else {
                $html .= '<p>No windows were selected.</p>';
            }
            
        $html .= '</div>';

        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("window-quote-".date('Y-m-d').".pdf", ["Attachment" => 1]);
        exit;
    }
}
add_action('init', 'handle_pdf_generation');

// Admin menu setup (same as your original)
function window_orders_admin_menu() {
    add_menu_page(
        'Window Orders',
        'Window Orders',
        'manage_options',
        'window-orders',
        'display_window_orders_page',
        'dashicons-welcome-widgets-menus',
        30
    );
}
add_action('admin_menu', 'window_orders_admin_menu');

// Admin page display (same as your original)
function display_window_orders_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'window_orders';
    
    // Handle order deletion
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        if (current_user_can('manage_options')) {
            $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success"><p>Order deleted successfully.</p></div>';
        }
    }
    
    // Get all orders
    $orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Window Orders</h1>
        <hr class="wp-header-end">
        
        <a href="<?php echo admin_url('admin.php?page=window-orders&action=export_csv'); ?>" class="button button-primary" style="margin-bottom: 20px;">Export to CSV</a>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Windows</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): 
                        $window_data = json_decode($order->window_data, true);
                        $window_count = is_array($window_data) ? count($window_data) : 0;
                    ?>
                        <tr>
                            <td><?php echo $order->id; ?></td>
                            <td><?php echo esc_html($order->customer_name); ?></td>
                            <td><?php echo esc_html($order->customer_email); ?></td>
                            <td><?php echo esc_html($order->customer_phone); ?></td>
                            <td><?php echo esc_html($order->location); ?></td>
                            <td><?php echo $window_count; ?> window(s)</td>
                            <td><?php echo date('M j, Y g:i a', strtotime($order->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=window-orders&action=view&id=' . $order->id); ?>" class="button">View</a>
                                <a href="<?php echo admin_url('admin.php?page=window-orders&action=delete&id=' . $order->id); ?>" class="button button-danger" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <style>
        .window-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .window-spec {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .button-danger {
            color: #fff;
            background: #dc3232;
            border-color: #dc3232;
        }
        .button-danger:hover {
            background: #a00;
            border-color: #a00;
            color: #fff;
        }
    </style>
    <?php
    
    // View single order
    if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
        $order_id = intval($_GET['id']);
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
        
        if ($order) {
            $window_data = json_decode($order->window_data, true);
            
            echo '<div class="window-details">';
            echo '<h2>Order Details #' . $order->id . '</h2>';
            echo '<p><strong>Customer:</strong> ' . esc_html($order->customer_name) . '</p>';
            echo '<p><strong>Email:</strong> ' . esc_html($order->customer_email) . '</p>';
            echo '<p><strong>Phone:</strong> ' . esc_html($order->customer_phone) . '</p>';
            echo '<p><strong>Address:</strong> ' . esc_html($order->customer_address) . '</p>';
            echo '<p><strong>Location:</strong> ' . esc_html($order->location) . '</p>';
            echo '<p><strong>Date:</strong> ' . date('M j, Y g:i a', strtotime($order->created_at)) . '</p>';
            
            echo '<h3>Window Specifications</h3>';
            
            if (is_array($window_data) && !empty($window_data)) {
                foreach ($window_data as $index => $window) {
                    echo '<div class="window-spec">';
                    echo '<h4>Window #' . ($index + 1) . '</h4>';
                    echo '<p><strong>Style:</strong> ' . esc_html($window['style']) . '</p>';
                    echo '<p><strong>Dimensions:</strong> ' . esc_html($window['width']) . '" × ' . esc_html($window['height']) . '"</p>';
                    
                    if (!empty($window['notes'])) {
                        echo '<p><strong>Notes:</strong><br>' . nl2br(esc_html($window['notes'])) . '</p>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<p>No window data available.</p>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="notice notice-error"><p>Order not found.</p></div>';
        }
    }
    
    // Handle CSV export
    if (isset($_GET['action']) && $_GET['action'] == 'export_csv') {
        if (current_user_can('manage_options')) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="window-orders-' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, array(
                'ID',
                'Customer Name',
                'Email',
                'Phone',
                'Address',
                'Location',
                'Window Count',
                'Date'
            ));
            
            $all_orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
            
            foreach ($all_orders as $order) {
                $window_data = json_decode($order->window_data, true);
                $window_count = is_array($window_data) ? count($window_data) : 0;
                
                fputcsv($output, array(
                    $order->id,
                    $order->customer_name,
                    $order->customer_email,
                    $order->customer_phone,
                    $order->customer_address,
                    $order->location,
                    $window_count,
                    $order->created_at
                ));
            }
            
            fclose($output);
            exit;
        }
    }
}


// CSS styles (same as your original)
function window_order_form_styles() {
    ?>
    <style>
        .custom-window-form-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
        }
        .form-group textarea {
            min-height: 80px;
        }
        .window-notes textarea {
            min-height: 60px;
        }
        .button {
            padding: 10px 20px;
            background: #ee1e27;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
        }
        .button:hover {
            background: #d0d0d0;
        }
        .button.primary {
            background: #2c3e50;
            color: white;
        }
        .button.primary:hover {
            background: #1a252f;
        }
        .window-selection {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }
        .window-image-container {
            margin-top: 15px;
        }
        .window-preview {
            position: relative;
            display: inline-block;
        }
        .window-preview-image {
            max-width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .window-dimensions {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 14px;
        }
        .remove-window {
            margin-top: 10px;
            background: #e74c3c;
            color: white;
        }
        .remove-window:hover {
            background: #c0392b;
        }
        .form-submit {
            margin-top: 25px;
            text-align: center;
        }
        
        /* Results styling */
        .window-form-results {
            max-width: 900px;
            margin: 0 auto 30px;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .windows-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .window-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .spec-row {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #eee;
        }
        .notes-row {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
        .window-image-preview {
            margin-top: 15px;
            position: relative;
        }
        .window-image-preview img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .dimensions-badge {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
    <?php
}


// Helper function to check if images exist
function wof_check_image_exists($image_path) {
    $upload_dir = wp_upload_dir();
    $full_path = trailingslashit($upload_dir['basedir']) . $image_path;
    return file_exists($full_path);
}

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wof_add_settings_link');

function wof_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=window-orders">View Orders</a>';
    array_unshift($links, $settings_link);
    return $links;
}
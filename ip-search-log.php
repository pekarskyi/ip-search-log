<?php
/**
 * Plugin Name: IP Search Log
 * Description: Plugin for logging user search queries
 * Version: 1.0.0
 * Author: Inwebpress
 * Author URI: https://inwebpress.com
 * Plugin URI: https://github.com/pekarskyi/ip-search-log
 * Text Domain: ip-search-log
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin text domain for translations
function ip_search_log_load_textdomain() {
    load_plugin_textdomain('ip-search-log', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ip_search_log_load_textdomain');

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-search-log-list-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-search-log-exporter.php';

// Main plugin class
class IP_Search_Logger {
    // Class properties
    private $table_name;

    // Constructor
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ip_search_log';
        
        // Register hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('pre_get_posts', array($this, 'log_search_query'));
        add_action('wp_ajax_clear_search_log', array($this, 'ajax_clear_search_log'));
        add_action('wp_ajax_export_search_log', array($this, 'ajax_export_search_log'));
        
        // Додаємо посилання на сторінку журналу в таблиці плагінів
        $plugin_basename = plugin_basename(__FILE__);
        add_filter("plugin_action_links_{$plugin_basename}", array($this, 'add_plugin_action_links'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'create_table'));
    }
    
    // Create database table on plugin activation
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            search_query varchar(255) NOT NULL,
            ip_address varchar(100) NOT NULL,
            query_count int NOT NULL DEFAULT 1,
            last_query_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY query_ip (search_query, ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Додаємо посилання на сторінку журналу в таблиці плагінів
    public function add_plugin_action_links($links) {
        $log_link = '<a href="' . admin_url('admin.php?page=ip-search-log') . '">' . __('Log', 'ip-search-log') . '</a>';
        array_unshift($links, $log_link);
        return $links;
    }
    
    // Register admin menu
    public function register_admin_menu() {
        add_menu_page(
            __('IP Search Log', 'ip-search-log'),
            __('IP Search Log', 'ip-search-log'),
            'manage_options',
            'ip-search-log',
            array($this, 'render_admin_page'),
            'dashicons-search'
        );
    }
    
    // Enqueue admin assets
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_ip-search-log' !== $hook) {
            return;
        }
        
        wp_enqueue_style('ip-search-log-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('ip-search-log-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('ip-search-log-admin', 'ipSearchLogData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ip_search_log_nonce'),
        ));
    }
    
    // Render admin page
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Create an instance of the list table
        $list_table = new Search_Log_List_Table();
        $list_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <button id="clear-search-log" class="button button-primary"><?php _e('Clear All Records', 'ip-search-log'); ?></button>
                    <button id="export-search-log" class="button"><?php _e('Export', 'ip-search-log'); ?></button>
                </div>
            </div>
            
            <div id="search-log-message" class="notice" style="display:none;"></div>
            
            <form id="search-log-filter" method="post">
                <?php $list_table->display(); ?>
            </form>
        </div>
        
        <!-- Confirmation modal -->
        <div id="confirm-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-body">
                    <p><?php _e('Are you sure you want to delete all search log entries? This action cannot be undone.', 'ip-search-log'); ?></p>
                </div>
                <div class="modal-footer">
                    <button id="confirm-clear" class="button button-primary"><?php _e('Confirm', 'ip-search-log'); ?></button>
                    <button id="cancel-clear" class="button"><?php _e('Cancel', 'ip-search-log'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Log search query
    public function log_search_query($query) {
        global $wpdb;
        
        // Check if it's a search query
        if ($query->is_main_query() && $query->is_search() && !is_admin()) {
            $search_term = sanitize_text_field($query->get('s'));
            $ip_address = $this->get_client_ip();
            
            // Insert or update search query record
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$this->table_name} (search_query, ip_address) 
                VALUES (%s, %s) 
                ON DUPLICATE KEY UPDATE query_count = query_count + 1, last_query_date = CURRENT_TIMESTAMP",
                $search_term,
                $ip_address
            ));
        }
    }
    
    // AJAX handler for clearing search log
    public function ajax_clear_search_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_search_log_nonce')) {
            wp_send_json_error(__('An error occurred while clearing records.', 'ip-search-log'));
        }
        
        global $wpdb;
        
        // Clear database table
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        if ($result !== false) {
            wp_send_json_success(__('All records have been successfully cleared.', 'ip-search-log'));
        } else {
            wp_send_json_error(__('An error occurred while clearing records.', 'ip-search-log'));
        }
    }
    
    // AJAX handler for exporting search log
    public function ajax_export_search_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_search_log_nonce')) {
            wp_send_json_error(__('An error occurred during export.', 'ip-search-log'));
        }
        
        // Initialize exporter
        $exporter = new Search_Log_Exporter();
        
        // Try XLSX export first - встановлюємо примусово формат XLSX
        $result = $exporter->export_xlsx();
        
        if (isset($result['success']) && $result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'download_url' => $result['file_url'],
                'download_text' => __('Download XLSX', 'ip-search-log')
            ));
        } else {
            // Fall back to CSV export тільки якщо XLSX не вдалося
            $result = $exporter->export_csv();
            if (isset($result['success']) && $result['success']) {
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'download_url' => $result['file_url'],
                    'download_text' => __('Download CSV', 'ip-search-log')
                ));
            } else {
                wp_send_json_error(__('An error occurred during export.', 'ip-search-log'));
            }
        }
    }
    
    // Helper method to get client IP
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }
        
        return '127.0.0.1'; // Default IP if none found
    }
}

// Initialize the plugin
$ip_search_logger = new IP_Search_Logger();

// Adding update check via GitHub
require_once plugin_dir_path( __FILE__ ) . 'updates/github-updater.php';
if ( function_exists( 'ip_search_log_github_updater_init' ) ) {
    ip_search_log_github_updater_init(
        __FILE__,       // Plugin file path
        'pekarskyi',     // Your GitHub username
        '',              // Access token (empty)
        'ip-search-log' // Repository name (optional)
        // Other parameters are determined automatically
    );
} 
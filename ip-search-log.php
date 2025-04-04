<?php
/**
 * Plugin Name: IP Search Log
 * Description: Plugin for logging user search queries
 * Version: 1.1.0
 * Author: Inwebpress
 * Author URI: https://inwebpress.com
 * Plugin URI: https://github.com/pekarskyi/ip-search-log
 * Text Domain: ip-search-log
 * Domain Path: /languages
 * Requires at least: 6.7.0
 * Tested up to: 6.7.2
 * Requires PHP: 7.4
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define log file path
define('IP_SEARCH_LOG_DIR', ABSPATH . 'wp-content/ip-search-log-logs');
define('IP_SEARCH_LOG_FILE', IP_SEARCH_LOG_DIR . '/ip-search-log-logs.log');

// Load plugin text domain for translations
function ip_search_log_load_textdomain() {
    load_plugin_textdomain('ip-search-log', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ip_search_log_load_textdomain');

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-search-log-list-table.php';

// Main plugin class
class IP_Search_Logger {
    // Class properties
    private $table_name;
    private $log_file;

    // Constructor
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ip_search_log';
        $this->log_file = IP_SEARCH_LOG_FILE;
        
        // Register hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('pre_get_posts', array($this, 'log_search_query'));
        add_action('wp_ajax_clear_search_log', array($this, 'ajax_clear_search_log'));
        
        // Додаємо посилання на сторінку журналу в таблиці плагінів
        $plugin_basename = plugin_basename(__FILE__);
        add_filter("plugin_action_links_{$plugin_basename}", array($this, 'add_plugin_action_links'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'create_table'));
    }
    
    // Create database table on plugin activation
    public function create_table() {
        // Переконуємося, що файл логів існує і є доступним для запису
        $this->init_log_file();
        
        // Залишаємо для сумісності
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
    
    // Initialize log file
    private function init_log_file() {
        // Переконуємося, що директорія існує
        if (!file_exists(IP_SEARCH_LOG_DIR)) {
            wp_mkdir_p(IP_SEARCH_LOG_DIR);
            
            // Створюємо index.php для безпеки
            file_put_contents(IP_SEARCH_LOG_DIR . '/index.php', '<?php // Silence is golden');
            
            // Створюємо .htaccess з правилами обмеження доступу
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents(IP_SEARCH_LOG_DIR . '/.htaccess', $htaccess_content);
        } else {
            // Переконуємося, що .htaccess існує і має правильний вміст
            $htaccess_file = IP_SEARCH_LOG_DIR . '/.htaccess';
            if (!file_exists($htaccess_file) || file_get_contents($htaccess_file) !== "Order deny,allow\nDeny from all") {
                $htaccess_content = "Order deny,allow\nDeny from all";
                file_put_contents($htaccess_file, $htaccess_content);
            }
        }
        
        if (!file_exists($this->log_file)) {
            $header = "timestamp,search_query\n";
            file_put_contents($this->log_file, $header);
        }
        
        // Перевіряємо права доступу
        if (!is_writable($this->log_file)) {
            // Спробуємо встановити права на запис
            chmod($this->log_file, 0666);
        }
    }
    
    // Log search query to file
    private function write_to_log($search_term) {
        $this->init_log_file();
        
        $timestamp = current_time('mysql');
        $log_entry = sprintf("%s,%s\n", 
            $timestamp,
            str_replace(',', '\\,', $search_term) // Екрануємо коми
        );
        
        return file_put_contents($this->log_file, $log_entry, FILE_APPEND);
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
        
        // CSS файли
        wp_enqueue_style('ip-search-log-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '1.12.1');
        
        // JS файли
        wp_enqueue_script('jquery-ui-datepicker'); // Вбудований у WordPress
        wp_enqueue_script('ip-search-log-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery', 'jquery-ui-datepicker'), '1.0.0', true);
        
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
                </div>
            </div>
            
            <div id="search-log-message" class="notice" style="display:none;"></div>
            
            <form id="search-log-filter" method="get">
                <input type="hidden" name="page" value="ip-search-log" />
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
        // Check if it's a search query
        if ($query->is_main_query() && $query->is_search() && !is_admin()) {
            $search_term = sanitize_text_field($query->get('s'));
            
            // Записуємо в файл логів
            $this->write_to_log($search_term);
        }
    }
    
    // AJAX handler for clearing search log
    public function ajax_clear_search_log() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ip_search_log_nonce')) {
            wp_send_json_error(__('An error occurred while clearing records.', 'ip-search-log'));
        }
        
        // Очищаємо файл логів, залишаючи заголовок
        $header = "timestamp,search_query\n";
        $result = file_put_contents($this->log_file, $header);
        
        if ($result !== false) {
            wp_send_json_success(__('All records have been successfully cleared.', 'ip-search-log'));
        } else {
            wp_send_json_error(__('An error occurred while clearing records.', 'ip-search-log'));
        }
    }
}

// Adding update check via GitHub
require_once plugin_dir_path( __FILE__ ) . 'updates/github-updater.php';

$github_username = 'pekarskyi'; // Вказуємо ім'я користувача GitHub
$repo_name = 'ip-search-log'; // Вказуємо ім'я репозиторію GitHub, наприклад ip-wp-github-updater
$prefix = 'ip_search_log'; // Встановлюємо унікальний префікс плагіну, наприклад ip_wp_github_updater

// Ініціалізуємо систему оновлення плагіну з GitHub
if ( function_exists( 'ip_github_updater_load' ) ) {
    // Завантажуємо файл оновлювача з нашим префіксом
    ip_github_updater_load($prefix);
    
    // Формуємо назву функції оновлення з префіксу
    $updater_function = $prefix . '_github_updater_init';   
    
    // Після завантаження наша функція оновлення повинна бути доступна
    if ( function_exists( $updater_function ) ) {
        call_user_func(
            $updater_function,
            __FILE__,       // Plugin file path
            $github_username, // Your GitHub username
            '',              // Access token (empty)
            $repo_name       // Repository name (на основі префіксу)
        );
    }
} 

// Initialize the plugin - додаємо ініціалізацію плагіну
$ip_search_logger = new IP_Search_Logger(); 
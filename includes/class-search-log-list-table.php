<?php
/**
 * Class for displaying search queries table
 *
 * @package IP_Search_Log
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// WP_List_Table is not loaded automatically
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Class Search_Log_List_Table
 */
class Search_Log_List_Table extends WP_List_Table {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Search query', 'ip-search-log'),
            'plural'   => __('Search queries', 'ip-search-log'),
            'ajax'     => false
        ));
    }
    
    /**
     * Get table columns
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'search_query'    => __('Query', 'ip-search-log'),
            'last_query_date' => __('Last Query Date', 'ip-search-log'),
            'ip_address'      => __('IP Address', 'ip-search-log'),
            'query_count'     => __('Count', 'ip-search-log')
        );
    }
    
    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'search_query'    => array('search_query', false),
            'last_query_date' => array('last_query_date', true),
            'ip_address'      => array('ip_address', false),
            'query_count'     => array('query_count', false)
        );
    }
    
    /**
     * Message for no items
     */
    public function no_items() {
        _e('No search queries found.', 'ip-search-log');
    }
    
    /**
     * Prepare items for table
     */
    public function prepare_items() {
        global $wpdb;
        
        // Table name
        $table_name = $wpdb->prefix . 'ip_search_log';
        
        // Set column headers
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Pagination settings
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Order settings
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'last_query_date';
        $order = !empty($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'DESC';
        
        // Get data from database
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        // Get total items count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        
        $this->items = $data;
    }
    
    /**
     * Render column content
     *
     * @param array $item
     * @param string $column_name
     * @return string
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'search_query':
                return '<a href="' . esc_url(add_query_arg('s', urlencode($item[$column_name]), home_url('/'))) . '" target="_blank">' . esc_html($item[$column_name]) . '</a>';
            case 'last_query_date':
                $date = new DateTime($item[$column_name]);
                return $date->format(get_option('date_format') . ' ' . get_option('time_format'));
            case 'ip_address':
            case 'query_count':
                return esc_html($item[$column_name]);
            default:
                return '';
        }
    }
} 
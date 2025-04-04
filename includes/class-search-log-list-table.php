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
            'last_query_date' => __('Date', 'ip-search-log'),
            'query_count'     => __('Count', 'ip-search-log')
        );
    }
    
    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        // Сортування для колонок Date і Count
        return array(
            'last_query_date' => array('last_query_date', true), // true - за замовчуванням
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
     * Read data from log file
     *
     * @return array Data from log file
     */
    private function get_log_data() {
        $log_file = IP_SEARCH_LOG_FILE;
        
        if (!file_exists($log_file) || !is_readable($log_file)) {
            return array();
        }
        
        $data = array();
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($lines)) {
            return array();
        }
        
        // Skip header
        array_shift($lines);
        
        // Temporary array for grouping queries (by query and date)
        $grouped_queries = array();
        
        foreach ($lines as $line) {
            $parts = str_getcsv($line);
            if (count($parts) >= 2) { // Now expecting only 2 parts - timestamp and query
                $timestamp = isset($parts[0]) ? $parts[0] : '';
                $search_query = isset($parts[1]) ? str_replace('\\,', ',', $parts[1]) : ''; // Decode commas
                
                // Get only date (without time) for grouping
                $date_only = date('Y-m-d', strtotime($timestamp));
                
                // Convert query to lowercase for grouping
                $query_key = mb_strtolower(trim($search_query));
                
                // Unique key - combination of query and date
                $unique_key = $query_key . '|' . $date_only;
                
                if (!isset($grouped_queries[$unique_key])) {
                    $grouped_queries[$unique_key] = array(
                        'search_query' => $search_query, // Save original query
                        'last_query_date' => $date_only, // Save only date
                        'query_count' => 1
                    );
                } else {
                    // Update only counter, date remains the same
                    $grouped_queries[$unique_key]['query_count']++;
                }
            }
        }
        
        // Convert grouped data to table format
        $id = 1;
        foreach ($grouped_queries as $query) {
            $data[] = array(
                'id' => $id++, // Simulate ID for compatibility
                'search_query' => $query['search_query'],
                'last_query_date' => $query['last_query_date'],
                'query_count' => $query['query_count']
            );
        }
        
        return $data;
    }
    
    /**
     * Display search and filter elements above the table
     */
    public function display_tablenav($which) {
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
            <?php if ($which == 'top'): ?>
                <div class="alignleft actions">
                    <?php $this->display_filter_controls(); ?>
                </div>
            <?php elseif ($which == 'bottom'): ?>
                <div class="alignleft actions">
                    <?php $this->display_per_page_dropdown(); ?>
                </div>
            <?php endif; ?>
            
            <?php
            $this->pagination($which);
            ?>
            <br class="clear" />
        </div>
        <?php
    }
    
    /**
     * Display filter elements
     */
    private function display_filter_controls() {
        $search_value = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '';
        $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '';
        ?>
        <div class="search-box" style="margin-bottom: 10px;">
            <input type="search" id="search-box" name="s" value="<?php echo esc_attr($search_value); ?>" 
                placeholder="<?php esc_attr_e('Search query...', 'ip-search-log'); ?>" />
            
            <input type="text" id="date-from" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
                placeholder="<?php esc_attr_e('Date from...', 'ip-search-log'); ?>" class="date-picker" />
            
            <input type="text" id="date-to" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
                placeholder="<?php esc_attr_e('Date to...', 'ip-search-log'); ?>" class="date-picker" />
            
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Filter', 'ip-search-log'); ?>" />
            
            <?php if (!empty($search_value) || !empty($date_from) || !empty($date_to)): ?>
                <a href="<?php echo admin_url('admin.php?page=ip-search-log'); ?>" class="button">
                    <?php _e('Reset', 'ip-search-log'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Display dropdown for selecting number of items per page
     */
    private function display_per_page_dropdown() {
        $per_page_options = array(10, 20, 50, 100, 200);
        $selected = isset($_REQUEST['per_page']) ? (int) $_REQUEST['per_page'] : 20;
        
        // Make sure the value is in the options list
        if (!in_array($selected, $per_page_options)) {
            $selected = 20; // Default
        }
        
        // Save current filter parameters
        $s = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '';
        $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : '';
        $order = isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : '';
        
        ?>
        <label for="per-page" class="screen-reader-text">
            <?php _e('Number of items per page', 'ip-search-log'); ?>
        </label>
        
        <!-- Save filter parameters in hidden fields -->
        <?php if (!empty($s)): ?>
            <input type="hidden" name="s" value="<?php echo esc_attr($s); ?>" />
        <?php endif; ?>
        
        <?php if (!empty($date_from)): ?>
            <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
        <?php endif; ?>
        
        <?php if (!empty($date_to)): ?>
            <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
        <?php endif; ?>
        
        <?php if (!empty($orderby)): ?>
            <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>" />
        <?php endif; ?>
        
        <?php if (!empty($order)): ?>
            <input type="hidden" name="order" value="<?php echo esc_attr($order); ?>" />
        <?php endif; ?>
        
        <select id="per-page" name="per_page">
            <?php foreach ($per_page_options as $option): ?>
                <option value="<?php echo esc_attr($option); ?>" <?php selected($selected, $option); ?>>
                    <?php 
                    // Translate "Per page" in each option
                    printf(_n('%d', '%d', $option, 'ip-search-log'), $option); 
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" class="button" value="<?php esc_attr_e('Apply', 'ip-search-log'); ?>" />
        <?php
    }
    
    /**
     * Prepare items for table
     */
    public function prepare_items() {
        // Set column headers
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Get data from log file
        $all_data = $this->get_log_data();
        
        // Apply search filters
        $all_data = $this->apply_filters($all_data);
        
        // Pagination settings
        $per_page = isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : 20;
        if ($per_page < 1) {
            $per_page = 20;
        }
        
        // Consider paged parameter when navigating pages
        $current_page = isset($_REQUEST['paged']) ? intval($_REQUEST['paged']) : 1;
        if ($current_page < 1) {
            $current_page = 1;
        }
        
        $total_items = count($all_data);
        
        // Order settings - сортування за замовчуванням за датою
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : 'last_query_date';
        $order = !empty($_REQUEST['order']) ? strtoupper(sanitize_key($_REQUEST['order'])) : 'DESC';
        
        // Переконатися, що order має правильне значення
        if ($order !== 'ASC' && $order !== 'DESC') {
            $order = 'DESC';
        }
        
        // Sort data - оптимізований код сортування
        usort($all_data, function($a, $b) use ($orderby, $order) {
            // Підготуємо значення для порівняння
            $a_val = $a[$orderby];
            $b_val = $b[$orderby];
            
            // Визначаємо тип значення і відповідний метод порівняння
            if ($orderby === 'query_count') {
                // Обов'язково перетворюємо на цілі числа
                $a_val = intval($a_val);
                $b_val = intval($b_val);
                $result = $a_val - $b_val;
            } elseif ($orderby === 'last_query_date') {
                // Перетворюємо на timestamp для порівняння дат
                $a_val = strtotime($a_val);
                $b_val = strtotime($b_val);
                $result = $a_val - $b_val;
            } else {
                // Стандартне текстове порівняння
                $result = strcmp($a_val, $b_val);
            }
            
            // Застосовуємо напрямок сортування
            return $order === 'DESC' ? -$result : $result;
        });
        
        // Slice for pagination
        $offset = ($current_page - 1) * $per_page;
        $data = array_slice($all_data, $offset, $per_page);
        
        // Configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
        
        $this->items = $data;
    }
    
    /**
     * Apply filters to data
     * 
     * @param array $data Input data
     * @return array Filtered data
     */
    private function apply_filters($data) {
        $filtered_data = $data;
        
        // Search by query
        if (!empty($_REQUEST['s'])) {
            $search_term = sanitize_text_field($_REQUEST['s']);
            $filtered_data = array_filter($filtered_data, function($item) use ($search_term) {
                return (stripos($item['search_query'], $search_term) !== false);
            });
        }
        
        // Filter by date "from"
        if (!empty($_REQUEST['date_from'])) {
            $date_from = strtotime(sanitize_text_field($_REQUEST['date_from']));
            if ($date_from) {
                $filtered_data = array_filter($filtered_data, function($item) use ($date_from) {
                    $item_date = strtotime($item['last_query_date']);
                    return $item_date >= $date_from;
                });
            }
        }
        
        // Filter by date "to"
        if (!empty($_REQUEST['date_to'])) {
            $date_to = strtotime(sanitize_text_field($_REQUEST['date_to']) . ' 23:59:59');
            if ($date_to) {
                $filtered_data = array_filter($filtered_data, function($item) use ($date_to) {
                    $item_date = strtotime($item['last_query_date']);
                    return $item_date <= $date_to;
                });
            }
        }
        
        return $filtered_data;
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
                // If date is already in Y-m-d format, just use it
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $item[$column_name])) {
                    return esc_html(date_i18n(get_option('date_format'), strtotime($item[$column_name])));
                }
                // Otherwise format through DateTime
                $date = new DateTime($item[$column_name]);
                return esc_html(date_i18n(get_option('date_format'), strtotime($date->format('Y-m-d'))));
            case 'query_count':
                return esc_html($item[$column_name]);
            default:
                return '';
        }
    }
    
    /**
     * Generate URL for pagination with preserved filter parameters
     */
    public function get_pagination_arg($key) {
        // Get standard value from parent method
        $value = parent::get_pagination_arg($key);
        
        // If this is not a URL, just return the value
        if (strpos($value, 'http') !== 0) {
            return $value;
        }
        
        // Add filter parameters to the link
        $filter_params = array('s', 'date_from', 'date_to', 'per_page', 'orderby', 'order');
        
        foreach ($filter_params as $param) {
            if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) {
                $value = add_query_arg($param, sanitize_text_field($_REQUEST[$param]), $value);
            }
        }
        
        return $value;
    }
    
    /**
     * Helper method for creating pagination URL with our parameters
     */
    private function build_pagination_url($key, $value) {
        $base_url = admin_url('admin.php');
        $url_params = array();
        
        // Basic page parameters
        $url_params['page'] = 'ip-search-log';
        $url_params[$key] = $value;
        
        // Filter parameters
        $filter_params = array('s', 'date_from', 'date_to', 'per_page', 'orderby', 'order');
        
        foreach ($filter_params as $param) {
            if ($param === $key) {
                continue; // Skip current argument, it's already added
            }
            
            if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) {
                $url_params[$param] = sanitize_text_field($_REQUEST[$param]);
            }
        }
        
        return add_query_arg($url_params, $base_url);
    }
    
    /**
     * Replace standard pagination URL with ours preserving filter parameters
     */
    public function pagination($which) {
        if (empty($this->_pagination_args)) {
            return;
        }

        $total_items = $this->_pagination_args['total_items'];
        $total_pages = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if (isset($this->_pagination_args['infinite_scroll'])) {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ('top' === $which && $total_pages > 1) {
            $this->screen->render_screen_reader_content('heading_pagination');
        }

        $output = '<span class="displaying-num">' . sprintf(
            /* translators: %s: Number of items. */
            _n('%s record', '%s records', $total_items, 'ip-search-log'),
            number_format_i18n($total_items)
        ) . '</span>';

        $current = $this->get_pagenum();
        $removable_query_args = wp_removable_query_args();

        $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $current_url = remove_query_arg($removable_query_args, $current_url);

        $page_links = array();
        $total_pages_before = '<span class="paging-input">';
        $total_pages_after = '</span></span>';

        $disable_first = $current <= 1;
        $disable_last = $current >= $total_pages;
        $disable_prev = $current <= 1;
        $disable_next = $current >= $total_pages;

        if ($disable_first) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url($this->build_pagination_url('paged', 1)),
                __('First page'),
                '&laquo;'
            );
        }

        if ($disable_prev) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url($this->build_pagination_url('paged', max(1, $current - 1))),
                __('Previous page'),
                '&lsaquo;'
            );
        }

        if (empty($total_pages)) {
            $page_links[] = '';
        } else {
            $page_links[] = $total_pages_before . sprintf(
                /* translators: 1: Current page, 2: Total pages. */
                _x('%1$s of %2$s', 'paging'),
                '<span class="screen-reader-text">' . __('Current Page') . '</span><span id="table-paging" class="paging-input">' . esc_html($current) . '</span>',
                '<span class="screen-reader-text">' . __('Total Pages') . '</span><span class="total-pages">' . esc_html($total_pages) . '</span>'
            ) . $total_pages_after;
        }

        if ($disable_next) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url($this->build_pagination_url('paged', min($total_pages, $current + 1))),
                __('Next page'),
                '&rsaquo;'
            );
        }

        if ($disable_last) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url($this->build_pagination_url('paged', $total_pages)),
                __('Last page'),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if (! empty($infinite_scroll)) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . implode("\n", $page_links) . '</span>';

        if ($total_pages) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }

        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }
    
    /**
     * Override print_column_headers to fix sorting toggle between ASC/DESC
     *
     * @param bool $with_id Whether to include column IDs
     */
    public function print_column_headers( $with_id = true ) {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        $current_url = remove_query_arg( 'paged', $current_url );

        // Визначаємо поточні параметри сортування
        $current_orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_query_date'; // За замовчуванням last_query_date
        $current_order = isset( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ? 'desc' : 'asc';

        // Якщо orderby не вказано, то за замовчуванням використовуємо DESC для сортування
        if (!isset($_GET['orderby']) && !isset($_GET['order'])) {
            $current_order = 'desc';
        }

        if ( ! empty( $columns['cb'] ) ) {
            static $cb_counter = 1;
            $columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name ) {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden, true ) ) {
                $class[] = 'hidden';
            }

            if ( 'cb' === $column_key ) {
                $class[] = 'check-column';
            } elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
                $class[] = 'num';
            }

            if ( $column_key === $primary ) {
                $class[] = 'column-primary';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( isset( $sortable[$column_key] ) ) {
                // Ця колонка сортована
                $orderby = $sortable[$column_key][0];
                
                // Визначаємо, чи активна поточна колонка для сортування
                $is_current = $orderby === $current_orderby;

                // Визначаємо, який буде напрямок при натисканні
                $order = $is_current && $current_order === 'asc' ? 'desc' : 'asc';
                
                // Додаємо класи для коректного відображення стрілок сортування
                $class[] = 'sortable';
                $class[] = $is_current ? $current_order : 'desc'; // Якщо неактивна, показуємо DESC за замовчуванням
                if ($is_current) {
                    $class[] = 'sorted';
                }

                // Формуємо URL із параметрами сортування та фільтрації
                $query_params = array(
                    'orderby' => $orderby,
                    'order' => $order
                );
                
                // Додаємо інші параметри з URL
                $filter_params = array('s', 'date_from', 'date_to', 'per_page');
                foreach ($filter_params as $param) {
                    if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) {
                        $query_params[$param] = sanitize_text_field($_REQUEST[$param]);
                    }
                }

                $url = add_query_arg($query_params, $current_url);

                // Додаємо назву колонки з посиланням та індикатором сортування
                $aria_sort = $is_current ? 'aria-sort="' . ($current_order === 'asc' ? 'ascending' : 'descending') . '"' : '';
                $column_display_name = '<a href="' . esc_url($url) . '" class="sort-link" ' . $aria_sort . '><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
            }

            // Формуємо атрибут class з усіх класів
            $class = join( ' ', $class );
            
            echo "<$tag $scope $id class=\"$class\">$column_display_name</$tag>";
        }
    }
} 
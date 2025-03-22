<?php
/**
 * Class for exporting search queries to Excel
 *
 * @package IP_Search_Log
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Search_Log_Exporter
 */
class Search_Log_Exporter {
    
    /**
     * Export data to XLSX format using PhpSpreadsheet
     *
     * @return array Export result
     */
    public function export_xlsx() {
        // Check if PhpSpreadsheet exists
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            // Try to autoload it
            if (file_exists(WP_PLUGIN_DIR . '/ip-search-log/vendor/autoload.php')) {
                require_once WP_PLUGIN_DIR . '/ip-search-log/vendor/autoload.php';
            }
            
            // If still not available
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
                return array(
                    'success' => false,
                    'message' => __('Export failed. PhpSpreadsheet library not available.', 'ip-search-log')
                );
            }
        }
        
        try {
            global $wpdb;
            
            // Table name
            $table_name = $wpdb->prefix . 'ip_search_log';
            
            // Get data
            $data = $wpdb->get_results(
                "SELECT search_query, ip_address, last_query_date, query_count 
                FROM $table_name
                ORDER BY last_query_date DESC",
                ARRAY_A
            );
            
            if (empty($data)) {
                return array(
                    'success' => false,
                    'message' => __('No data to export.', 'ip-search-log')
                );
            }
            
            // Create new Spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $sheet->setCellValue('A1', __('Query', 'ip-search-log'));
            $sheet->setCellValue('B1', __('IP Address', 'ip-search-log'));
            $sheet->setCellValue('C1', __('Last Query Date', 'ip-search-log'));
            $sheet->setCellValue('D1', __('Count', 'ip-search-log'));
            
            // Style header row
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);
            
            // Add data
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['search_query']);
                $sheet->setCellValue('B' . $row, $item['ip_address']);
                $sheet->setCellValue('C' . $row, $item['last_query_date']);
                $sheet->setCellValue('D' . $row, $item['query_count']);
                $row++;
            }
            
            // Auto size columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Встановлюємо кодування UTF-8 і властивості
            $spreadsheet->getProperties()->setTitle('Search Log Export')
                                        ->setCreator('IP Search Log Plugin')
                                        ->setLastModifiedBy('IP Search Log Plugin');
            
            // Встановлюємо стилі комірок для правильної підтримки UTF-8
            $sheet->getDefaultRowDimension()->setRowHeight(-1);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/ip-search-log-exports';
            
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
                
                // Create index.php for security instead of .htaccess with deny from all
                file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
            } else {
                // Перевіряємо наявність старого .htaccess файлу і видаляємо його, якщо він існує
                $htaccess_file = $export_dir . '/.htaccess';
                if (file_exists($htaccess_file)) {
                    @unlink($htaccess_file);
                }
                
                // Переконуємося, що у нас є правильний index.php файл
                file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
            }
            
            // Create filename
            $filename = 'search-log-export-' . date('Y-m-d-H-i-s') . '.xlsx';
            $filepath = $export_dir . '/' . $filename;
            
            // Save file
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            // Встановлюємо UTF-8 кодування
            if (method_exists($writer, 'setPreCalculateFormulas')) {
                $writer->setPreCalculateFormulas(false);
            }
            
            // Встановлюємо додаткові налаштування для UTF-8 в XLSX
            if (method_exists($writer, 'setOffice2003Compatibility')) {
                $writer->setOffice2003Compatibility(false);
            }
            
            $writer->save($filepath);
            
            // Get URL
            $file_url = $upload_dir['baseurl'] . '/ip-search-log-exports/' . $filename;
            
            return array(
                'success' => true,
                'message' => __('Export completed successfully.', 'ip-search-log'),
                'file_url' => $file_url
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Export data to CSV format as fallback
     *
     * @return array Export result
     */
    public function export_csv() {
        try {
            global $wpdb;
            
            // Table name
            $table_name = $wpdb->prefix . 'ip_search_log';
            
            // Get data
            $data = $wpdb->get_results(
                "SELECT search_query, ip_address, last_query_date, query_count 
                FROM $table_name
                ORDER BY last_query_date DESC",
                ARRAY_A
            );
            
            if (empty($data)) {
                return array(
                    'success' => false,
                    'message' => __('No data to export.', 'ip-search-log')
                );
            }
            
            // Create uploads directory if it doesn't exist
            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/ip-search-log-exports';
            
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
                
                // Create index.php for security instead of .htaccess with deny from all
                file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
            } else {
                // Перевіряємо наявність старого .htaccess файлу і видаляємо його, якщо він існує
                $htaccess_file = $export_dir . '/.htaccess';
                if (file_exists($htaccess_file)) {
                    @unlink($htaccess_file);
                }
                
                // Переконуємося, що у нас є правильний index.php файл
                file_put_contents($export_dir . '/index.php', '<?php // Silence is golden');
            }
            
            // Create filename
            $filename = 'search-log-export-' . date('Y-m-d-H-i-s') . '.csv';
            $filepath = $export_dir . '/' . $filename;
            
            // Open file
            $file = fopen($filepath, 'w');
            
            // Add BOM для UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($file, array(
                __('Query', 'ip-search-log'),
                __('IP Address', 'ip-search-log'),
                __('Last Query Date', 'ip-search-log'),
                __('Count', 'ip-search-log')
            ), ',', '"', '\\', "\n");
            
            // Add data
            foreach ($data as $item) {
                fputcsv($file, array(
                    $item['search_query'],
                    $item['ip_address'],
                    $item['last_query_date'],
                    $item['query_count']
                ));
            }
            
            // Close file
            fclose($file);
            
            // Get URL
            $file_url = $upload_dir['baseurl'] . '/ip-search-log-exports/' . $filename;
            
            return array(
                'success' => true,
                'message' => __('Export completed successfully.', 'ip-search-log'),
                'file_url' => $file_url
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
} 
<?php

namespace CAMOO_SMS\Export;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @category   class
 *
 * @version    1.0
 */
class Export
{
    protected $db;

    protected $tb_prefix;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
    }

    public function download()
    {
        if (current_user_can('wpcamoosms_setting') && isset($_POST['camoo_sms_export_nonce']) && wp_verify_nonce($_POST['camoo_sms_export_nonce'], 'camoo_sms_export_nonce')) {
            $hData = $this->getData();
            header('Content-type: application/x-msdownload', true, 200);
            header('Content-Disposition: attachment; filename=' . basename($hData['filename']));
            header('Content-Length: ' . strlen($hData['content']));
            header('Connection: close');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $hData['content'];
            exit;
        }
    }

    private function getData()
    {
        if ($type = sanitize_text_field($_POST['export-file-type'])) {
            require WP_CAMOO_SMS_DIR . 'includes/libraries/php-export-data.class.php';

            $file_name = date('Y-m-d_H-i');

            $result = $this->db->get_results("SELECT `ID`,`date`,`name`,`mobile`,`status`,`group_ID` FROM {$this->tb_prefix}camoo_sms_subscribes", ARRAY_A);
            $sFilename = '';

            switch ($type) {
                case 'excel':
                    $sFilename = "{$file_name}.xls";
                    $exporter = new \ExportDataExcel('browser', "{$file_name}.xls");
                    break;

                case 'xml':
                    $sFilename = "{$file_name}.xml";
                    $exporter = new \ExportDataExcel('browser', "{$file_name}.xml");
                    break;

                case 'csv':
                    $sFilename = "{$file_name}.csv";
                    $exporter = new \ExportDataCSV('browser', "{$file_name}.csv");
                    break;

                case 'tsv':
                    $sFilename = "{$file_name}.tsv";
                    $exporter = new \ExportDataTSV('browser', "{$file_name}.tsv");
                    break;
            }

            $exporter->initialize();

            foreach ($result[0] as $key => $col) {
                $columns[] = $key;
            }
            $exporter->addRow($columns);

            foreach ($result as $row) {
                $exporter->addRow($row);
            }

            return ['filename' => basename($sFilename), 'content' => $exporter->getString()];
        }
        wp_die(__('Please select the desired items.', 'wp-camoo-sms'), false, ['back_link' => true]);
    }
}

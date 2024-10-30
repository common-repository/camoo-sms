<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Outbox_List_Table extends \WP_List_Table
{
    public $data;

    protected $db;

    protected $tb_prefix;

    protected $limit;

    protected $count;

    public function __construct()
    {
        global $wpdb;

        //Set parent defaults
        parent::__construct([
            'singular' => 'ID',     //singular name of the listed records
            'plural' => 'ID',    //plural name of the listed records
            'ajax' => false,        //does this table support ajax?
        ]);
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
        $this->count = $this->get_total();
        $this->limit = 50;
        $this->data = $this->get_data();
    }

    public function statusMaps($xStatus)
    {
        $hStatus = [
            'no_status' => 'sent',
            'delivered' => 'success',
            'success' => 'success',
            'scheduled' => 'sent',
            'buffered' => 'sent',
            'sent' => 'sent',
            'expired' => 'Fail',
            'delivery_failed' => 'Fail',
        ];
        if (array_key_exists($xStatus, $hStatus)) {
            return $hStatus[$xStatus];
        }

        return false;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'date':
                return sprintf(
                    __('%s <span class="wpsms-time">Time: %s</span>', 'wp-camoo-sms'),
                    date_i18n('Y-m-d', strtotime($item[$column_name])),
                    date_i18n(
                        'H:i:s',
                        strtotime($item[$column_name])
                    )
                );

            case 'message':
                return $item[$column_name];
            case 'recipient':
                $html = '<details>
						  <summary>' . __('Click to View more...', 'wp-camoo-sms') . '</summary>
						  <p>' . $item[$column_name] . '</p>
						</details>';

                return $html;
            case 'response':
                $html = '<details>
						  <summary>' . __('Click to View more...', 'wp-camoo-sms') . '</summary>
						  <p>' . $item[$column_name] . '</p>
						</details>';

                return $html;
            case 'status':
                if ($status = $this->statusMaps($item[$column_name])) {
                    return '<span class="wp_camoo_sms_status_' . $status . '">' . __($status, 'wp-camoo-sms') . '</span>';
                }

                return '<span class="wp_camoo_sms_status_fail">' . __('Fail', 'wp-camoo-sms') . '</span>';
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function column_sender($item): string
    {
        //Build row actions
        $actions = [
            'resend' => sprintf('<a href="?page=%s&action=%s&ID=%s">' . __('Resend', 'wp-camoo-sms') . '</a>', esc_html($_REQUEST['page']), 'resend', $item['ID']),
            'delete' => sprintf('<a href="?page=%s&action=%s&ID=%s">' . __('Delete', 'wp-camoo-sms') . '</a>', esc_html($_REQUEST['page']), 'delete', $item['ID']),
        ];

        //Return the title contents
        return sprintf(
            '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/
            $item['sender'],
            /*$2%s*/
            $item['ID'],
            /*$3%s*/
            $this->row_actions($actions)
        );
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/
            $item['ID']                //The value of the checkbox should be the record's id
        );
    }

    public function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'sender' => __('Sender', 'wp-camoo-sms'),
            'date' => __('Date', 'wp-camoo-sms'),
            'message' => __('Message', 'wp-camoo-sms'),
            'recipient' => __('Recipient', 'wp-camoo-sms'),
            'response' => __('Response', 'wp-camoo-sms'),
            'status' => __('Status', 'wp-camoo-sms'),
        ];

        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [
            'ID' => ['ID', true],     //true means it's already sorted
            'sender' => ['sender', false],     //true means it's already sorted
            'date' => ['date', false],  //true means it's already sorted
            'message' => ['message', false],   //true means it's already sorted
            'recipient' => ['recipient', false], //true means it's already sorted
            'status' => ['status', false], //true means it's already sorted

        ];

        return $sortable_columns;
    }

    public function get_bulk_actions()
    {
        $actions = [
            'bulk_delete' => __('Delete', 'wp-camoo-sms'),
        ];

        return $actions;
    }

    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        // Search action
        if (isset($_GET['s'])) {
            $prepare = $this->db->prepare("SELECT * from `{$this->tb_prefix}camoo_sms_send` WHERE message LIKE %s OR recipient LIKE %s", '%' . $this->db->esc_like(sanitize_text_field($_GET['s'])) . '%', '%' . $this->db->esc_like(sanitize_text_field($_GET['s'])) . '%');
            $this->data = $this->get_data($prepare);
            $this->count = $this->get_total($prepare);
        }

        // Bulk delete action
        if ('bulk_delete' === $this->current_action()) {
            foreach ($_GET['id'] as $id) {
                $this->db->delete($this->tb_prefix . 'camoo_sms_send', ['ID' => sanitize_key($id)]);
            }
            $this->data = $this->get_data();
            $this->count = $this->get_total();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Items removed.', 'wp-camoo-sms') . '</p></div>';
        }

        // Single delete action
        if ('delete' === $this->current_action()) {
            $this->db->delete($this->tb_prefix . 'camoo_sms_send', ['ID' => sanitize_key($_GET['ID'])]);
            $this->data = $this->get_data();
            $this->count = $this->get_total();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Item removed.', 'wp-camoo-sms') . '</p></div>';
        }

        // Resend sms
        if ('resend' === $this->current_action()) {
            global $oCamooSMS;
            $error = null;
            $result = $this->db->get_row($this->db->prepare("SELECT * from `{$this->tb_prefix}camoo_sms_send` WHERE ID =%s;", sanitize_key($_GET['ID'])));
            $oCamooSMS->to = [$result->recipient];
            $oCamooSMS->msg = $result->message;
            $error = $oCamooSMS->sendSMS();
            if (is_wp_error($error)) {
                echo '<div class="notice notice-error  is-dismissible"><p>' . $error->get_error_message() . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('The SMS sent successfully.', 'wp-camoo-sms') . '</p></div>';
            }
            $this->data = $this->get_data();
            $this->count = $this->get_total();
        }
    }

    public function prepare_items()
    {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->limit;

        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = [$columns, $hidden, $sortable];

        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();

        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * package slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
         * our data. In a real-world implementation, you will probably want to
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $data = $this->data;

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         *
         * In a real-world situation involving a database, you would probably want
         * to handle sorting by passing the 'orderby' and 'order' values directly
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        usort($data, '\CAMOO_SMS\Outbox_List_Table::usort_reorder');

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = $this->count;

        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page),   //WE have to calculate the total number of pages
        ]);
    }

    /**
     * Usort Function
     *
     * @return float|int
     */
    public function usort_reorder($a, $b)
    {
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'date'; //If no sort, default to sender
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc
        $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order

        return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
    }

    //set $per_page item as int number
    public function get_data($query = '')
    {
        $page_number = ($this->get_pagenum() - 1) * $this->limit;
        if (!$query) {
            $query = 'SELECT * FROM `' . $this->tb_prefix . 'camoo_sms_send` ORDER BY `ID` DESC LIMIT ' .
                $this->limit . ' OFFSET ' . $page_number;
        } else {
            $query .= ' LIMIT ' . $this->limit . ' OFFSET ' . $page_number;
        }
        $result = $this->db->get_results($query, ARRAY_A);

        return $result;
    }

    //get total items on different Queries
    public function get_total($query = '')
    {
        if (!$query) {
            $query = 'SELECT * FROM `' . $this->tb_prefix . 'camoo_sms_send`';
        }
        $result = $this->db->get_results($query, ARRAY_A);
        $result = count($result);

        return $result;
    }
}

// Outbox page class
class Outbox
{
    /** Outbox sms admin page */
    public function render_page()
    {
        include_once WP_CAMOO_SMS_DIR . 'includes/admin/outbox/class-wpsms-outbox.php';

        //Create an instance of our package class...
        $list_table = new Outbox_List_Table();

        //Fetch, prepare, sort, and filter our data...
        $list_table->prepare_items();

        include_once WP_CAMOO_SMS_DIR . 'includes/admin/outbox/outbox.php';
    }
}

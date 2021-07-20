<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'MWTSA_Term_Stats_Table' ) ) :

    /**
     * MWTSA_Term_Stats_Table Class
     */
    class MWTSA_Term_Stats_Table extends MWTSA_Stats_Table {

        public $term_id = 0;
        public $term_data = [];
        public $is_grouped = false;

        public function __construct( $args = [] ) {
            $this->term_id   = absint( $_REQUEST['search-term'] );
            $this->term_data = $this->get_term_data();

            if ( ! empty( $_REQUEST['grouped_view'] ) ) {
                $this->is_grouped = true;
            }

            $title = ( ! empty( $_REQUEST['action'] ) && 'delete' == $_REQUEST['action'] ) ?
                        __( 'Terms Deleted', 'search-analytics' ) :
                        __( 'Term `' . $this->term_data['term'] . '` Search Statistics', 'search-analytics' );

            parent::__construct( [
                'title' => $title,
                'ajax'  => false
            ] );
        }

        public function get_term_data() {
            global $wpdb, $mwtsa;

            return $wpdb->get_row( "SELECT * FROM $mwtsa->terms_table_name WHERE id = $this->term_id", 'ARRAY_A' );
        }

        public function display_search_box() {
            // empty on purpose :)
        }

        public function get_columns() {
            $columns = array(
                'date_time' => __( 'Date and Time', 'search-analytics' ),
                'results'   => __( 'Average no. of results', 'search-analytics' )
            );

            if ( $this->is_grouped ) {
                $columns['searches'] = __( 'No. of Searches', 'search-analytics' );
            }

            return $columns;
        }

        public function get_sortable_columns() {
            $sortable_columns = array(
                'date_time' => array( 'datetime', false ),
                'results'   => array( 'results', false )
            );

            if ( $this->is_grouped ) {
                $sortable_columns['searches'] = array( 'searches', false );
            }

            return $sortable_columns;
        }

        public function column_default( $item, $column_name ) {
            switch ( $column_name ) {

                case 'date_time':
                    $current_group_view = ( ! empty( $_REQUEST['grouped_view'] ) ? $_REQUEST['grouped_view'] : 0 );

                    $date_parts = array(
                        get_option( 'date_format' ),
                        get_option( 'time_format' )
                    );

                    $glue = ' ';

                    if ( $current_group_view == 1 ) {
                        $date_parts = array(
                            get_option( 'date_format' )
                        );
                    } elseif ( $current_group_view == 2 ) {
                        $date_parts = array(
                            'h:00 a',
                            'h:59 a'
                        );

                        $glue = ' - ';
                    }

                    echo date_i18n( implode( $glue, $date_parts ), strtotime( $item['datetime'] ) + wp_timezone()->getOffset( new DateTime( $item['datetime'] ) ) );
                    break;
                case 'results':
                    echo number_format( (float) $item['results_count'], 2, '.', '' );
                    break;
                case 'searches':
                    echo $item['count'];
                    break;
                default:
                    echo 'N/A Yet';
            }
        }

        public function usort_reorder( $a, $b ) {
            $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date_time';
            $order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';

            switch ( $orderby ) {
                case 'date_time':
                    $orderby = 'datetime';
                    break;
                case 'results':
                    $orderby = 'results_count';
                    break;
                case 'searches':
                    $orderby = 'count';
                    break;
            }

            $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );

            return ( $order === 'asc' ) ? $result : - $result;
        }

        function get_bulk_actions() {
            return array();
        }

        public function display_group_views() {
            $views   = array();
            $current = ( ! empty( $_REQUEST['grouped_view'] ) ? $_REQUEST['grouped_view'] : 0 );

            $class    = ( $current == 0 ) ? ' class="current"' : '';
            $this_url = remove_query_arg( 'grouped_view' );
            $views[0] = "<a href='$this_url' $class >" . __( 'Not grouped', 'search-analytics' ) . "</a>";

            $this_url = add_query_arg( 'grouped_view', 1 );
            $class    = ( $current == 1 ) ? ' class="current"' : '';
            $views[1] = "<a href='$this_url' $class >" . __( 'By date', 'search-analytics' ) . "</a>";

            $this_url = add_query_arg( 'grouped_view', 2 );
            $class    = ( $current == 2 ) ? ' class="current"' : '';
            $views[2] = "<a href='$this_url' $class >" . __( 'By hour', 'search-analytics' ) . "</a>";

            $this->format_views_list( $views );
        }
    }
endif;
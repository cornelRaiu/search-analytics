<?php
defined( "ABSPATH" ) || exit;

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
			$this->term_id   = (int) $args['search-term'];
			$this->term_data = $this->get_term_data();

			if ( ! $this->term_data ) {
				wp_die( esc_attr__( 'Sorry, there is no resource with that ID.', 'search-analytics' ), 404 );
			}

			if ( ! empty( $_REQUEST['grouped_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->is_grouped = true;
			}

			$title = ( ! empty( $_REQUEST['action'] ) && 'delete' == $_REQUEST['action'] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				esc_attr__( 'Terms Deleted', 'search-analytics' ) :
				/* translators: %s: Currently viewed term */
				sprintf( esc_attr__( 'Term `%s` Search Statistics', 'search-analytics' ), esc_attr( $this->term_data['term'] ) );

			parent::__construct( [
				'title' => $title,
				'ajax'  => false
			] );
		}

		public function get_term_data() {
			global $wpdb, $mwtsa;

			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $mwtsa->terms_table_name WHERE id = %d", (int) $this->term_id ), 'ARRAY_A' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $mwtsa->terms_table_name is hardcoded.
		}

		public function display_search_box() {
			// empty on purpose :)
		}

		public function get_columns() {
			$columns = array(
				'results' => esc_attr__( 'Average no. of results', 'search-analytics' )
			);

			$show_dates_as_utc = (bool) MWTSA_Options::get_option( 'mwtsa_show_dates_as_utc' );

			if ( $show_dates_as_utc ) {
				$columns['date_time_utc'] = esc_attr__( 'Date and Time (UTC)', 'search-analytics' );
			} else {
				$columns['date_time'] = esc_attr__( 'Date and Time', 'search-analytics' );
			}

			if ( $this->is_grouped ) {
				$columns['searches'] = esc_attr__( 'No. of Searches', 'search-analytics' );
			}

			return apply_filters( 'mwtsa_term_stats_table_columns', $columns );
		}

		public function get_sortable_columns() {
			$sortable_columns = array(
				'date_time_utc' => array( 'datetime', false ),
				'date_time'     => array( 'datetime', false ),
				'results'       => array( 'results', false )
			);

			if ( $this->is_grouped ) {
				$sortable_columns['searches'] = array( 'searches', false );
			}

			return apply_filters( 'mwtsa_term_stats_table_sortable_columns', $sortable_columns );
		}

		private function get_date_format() {
			$current_group_view = ! empty( $_REQUEST['grouped_view'] ) ? (int) $_REQUEST['grouped_view'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$date_parts = array(
				get_option( 'date_format' ),
				get_option( 'time_format' )
			);

			$glue = ' ';

			if ( $current_group_view === 1 ) {
				$date_parts = array(
					get_option( 'date_format' )
				);
			} elseif ( $current_group_view === 2 ) {
				$date_parts = array(
					'h:00 a',
					'h:59 a'
				);

				$glue = ' - ';
			}

			return implode( $glue, $date_parts );
		}

		public function column_default( $item, $column_name ) {
			$output = esc_attr__( 'N/A Yet', 'search-analytics' );

			switch ( $column_name ) {

				case 'date_time_utc':
					$output = date_i18n( $this->get_date_format(), strtotime( $item['datetime'] ) );
					break;
				case 'date_time':
					$output = date_i18n( $this->get_date_format(), strtotime( $item['datetime'] ) + wp_timezone()->getOffset( new DateTime( $item['datetime'] ) ) );
					break;
				case 'results':
					$output = number_format( (float) $item['results_count'], 2, '.', '' );
					break;
				case 'searches':
					$output = (int) $item['count'];
					break;
			}

			echo apply_filters( 'mwtsa_term_stats_table_column_output', $output, $column_name, $item );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		public function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date_time'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
			$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended

			switch ( $orderby ) {
				case 'results':
					$orderby = 'results_count';
					break;
				case 'searches':
					$orderby = 'count';
					break;
				case 'date_time':
				default:
					$orderby = 'datetime';
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
			$current = ! empty( $_REQUEST['grouped_view'] ) ? (int) $_REQUEST['grouped_view'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$this_url = remove_query_arg( 'grouped_view' );
			$class    = ( $current === 0 ) ? ' class="current"' : '';
			$views[0] = sprintf( "<a href='%s' %s>%s</a>", esc_url( $this_url ), $class, esc_attr__( 'Not grouped', 'search-analytics' ) );

			$this_url = add_query_arg( 'grouped_view', 1 );
			$class    = ( $current === 1 ) ? ' class="current"' : '';
			$views[1] = sprintf( "<a href='%s' %s>%s</a>", esc_url( $this_url ), $class, esc_attr__( 'By date', 'search-analytics' ) );

			$this_url = add_query_arg( 'grouped_view', 2 );
			$class    = ( $current === 2 ) ? ' class="current"' : '';
			$views[2] = sprintf( "<a href='%s' %s>%s</a>", esc_url( $this_url ), $class, esc_attr__( 'By hour', 'search-analytics' ) );

			$this->format_views_list( $views );
		}
	}
endif;
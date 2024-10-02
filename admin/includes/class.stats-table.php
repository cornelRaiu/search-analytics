<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'MWTSA_Stats_Table' ) ) :

	/**
	 * MWTSA_Stats_Table Class
	 */
	class MWTSA_Stats_Table extends WP_List_Table {

		public function __construct( $args = array() ) {

			parent::__construct( [
				'title' => ( isset( $args['title'] ) ) ? esc_attr( $args['title'] ) : esc_attr__( 'Search Statistics', 'search-analytics' ),
				'ajax'  => isset( $args['ajax'] ) && $args['ajax']
			] );
		}

		public function display_search_box() {
			$this->search_box( 'Search', 'search_id' );
		}

		public function get_this_screen() {
			return 'search-analytics/admin/includes/class.stats';
		}

		public function display_tablenav( $which ) {
			if ( 'top' === $which ):
				?>
                <div class="tablenav mwtsa_tablenav <?php echo esc_attr( $which ); ?>">

					<?php if ( $this->has_items() && ! empty( $this->get_bulk_actions() ) ): ?>
                        <div class="alignleft actions bulkactions">
							<?php $this->bulk_actions( $which ); ?>
                        </div>
					<?php endif;

					$this->extra_tablenav( $which );
					$this->pagination( $which );
					?>

                    <br class="clear"/>
                </div>
			<?php
			endif;
		}


		public function this_title() {
			return '<h2>' . $this->_args['title'] . '</h2>';
		}

		public function get_columns() {
			$columns = array(
				'cb'       => '<input type="checkbox" />',
				'term'     => __( 'Term', 'search-analytics' ),
				'searches' => __( 'No. of Searches', 'search-analytics' ),
				'results'  => __( 'Average no. of results', 'search-analytics' ),
			);

			if ( isset( $_REQUEST['grouped_view'] ) && (int) $_REQUEST['grouped_view'] === 1 ) {
				unset( $columns['searches'] );

				$columns['results']          = __( 'No. of results', 'search-analytics' );
				$columns['last_search_date'] = __( 'Search Date', 'search-analytics' );

				if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_country' ) ) ) {
					$columns['country'] = __( 'Country', 'search-analytics' );
				}

				if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) ) {
					$columns['user'] = __( 'User', 'search-analytics' );
				}
			}

			$show_dates_as_utc = (bool) MWTSA_Options::get_option( 'mwtsa_show_dates_as_utc' );

			if ( $show_dates_as_utc ) {
				$columns['last_search_date_utc'] = __( 'Last search date (UTC)', 'search-analytics' );
			} else {
				$columns['last_search_date'] = __( 'Last search date', 'search-analytics' );
			}

			return apply_filters( 'mwtsa_stats_table_columns', $columns );
		}

		public function get_sortable_columns() {
			$sortable_columns = array(
				'term'                 => array( 'term', false ),
				'searches'             => array( 'searches', false ),
				'results'              => array( 'results', false ),
				'last_search_date_utc' => array( 'last_search_date', false ),
				'last_search_date'     => array( 'last_search_date', false )
			);

			if ( isset( $_REQUEST['grouped_view'] ) && (int) $_REQUEST['grouped_view'] === 1 ) {
				unset( $sortable_columns['searches'] );
			}

			return apply_filters( 'mwtsa_stats_table_sortable_columns', $sortable_columns );
		}

		/**
		 * @deprecated deprecated since version 1.3.6. WIll be removed in version 2.0.0
		 */
		public function column_term( $item ) {
			$actions = array(
				'delete' => sprintf( '<a href="?page=%s&action=%s&search-term=%d">' . esc_attr__( 'Delete', 'search-analytics' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete', (int) $item['id'] ),
				'view'   => sprintf( '<a href="?page=%s&search-term=%d">' . esc_attr__( 'View Details', 'search-analytics' ) . '</a>', esc_attr( $_REQUEST['page'] ), (int) $item['id'] )
			);

			return sprintf( '<a href="?page=%1$s&search-term=%2$d">%3$s</a> %4$s', esc_attr( $_REQUEST['page'] ), (int) $item['id'], esc_attr( $item['term'] ), $this->row_actions( $actions ) );
		}

		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="search-term[]" value="%d" />', (int) $item['id']
			);
		}

		protected function get_bulk_actions() {
			return array(
				'delete' => __( 'Delete', 'search-analytics' )
			);
		}

		function process_bulk_action() {
			global $wpdb, $mwtsa;

			if ( 'delete' === $this->current_action() && ! empty( $_GET['search-term'] ) ) {

				$terms_to_delete    = array_map( 'absint', (array) $_GET['search-term'] );
				$terms_placeholders = implode( ',', array_fill( 0, count( $terms_to_delete ), '%d' ) );

				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $mwtsa->terms_table_name WHERE id IN ($terms_placeholders)",
						$terms_to_delete
					)
				);
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $mwtsa->history_table_name WHERE term_id IN ($terms_placeholders)",
						$terms_to_delete
					)
				);

				wp_die(
					sprintf( '%s <a href="%s">%s</a>',
						esc_attr__( 'Items deleted!', 'search-analytics' ),
						esc_url( add_query_arg( 'result', 'deleted', remove_query_arg( array(
							'action',
							'search-term'
						) ) ) ),
						esc_attr__( 'Go Back!', 'search-analytics' )
					)
				);
			}

		}

		public function column_default( $item, $column_name ) {
			$output = esc_attr__( 'N/A Yet', 'search-analytics' );

			switch ( $column_name ) {
				case 'term':
					$output = sprintf( '<a href="?page=%s&search-term=%s">%s</a>', esc_attr( $_REQUEST['page'] ), (int) $item['id'], esc_attr( $item['term'] ) );
					break;
				case 'searches':
					$output = (int) $item['count'];
					break;
				case 'results':
					$output = number_format( (float) $item['results_count'], 2, '.', '' );
					break;
				case 'last_search_date_utc':
					$output = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['last_search_date'] ) );
					break;
				case 'last_search_date':
					$output = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['last_search_date'] ) + wp_timezone()->getOffset( new DateTime( $item['last_search_date'] ) ) );
					break;
				case 'country':
					if ( empty( $item['country'] ) ) {
						$output = esc_attr__( 'N/A', 'search-analytics' );

						break;
					}

					$item_country = esc_attr( $item['country'] );

					// thanks to: https://stackoverflow.com/a/26307388/3741900 for the nice solution
					if ( extension_loaded( 'intl' ) ) {
						$country_name = Locale::getDisplayRegion( '-' . $item_country, 'en' );
					} else {
						$country_name = strtoupper( $item_country );
					}

					$output = '<div><img src="' . MWTSAI()->plugin_admin_url . 'assets/images/flags/' . $item_country . '.png" alt="' . $country_name . '" />&nbsp;<span>' . ucwords( $country_name ) . '</span></div>';

					break;
				case 'user':
					if ( empty( $item['user_id'] ) ) {
						$output = esc_attr__( 'N/A', 'search-analytics' );

						break;
					}

					$user_data = get_userdata( (int) $item['user_id'] );

					if ( ! $user_data ) {
						$output = esc_attr__( 'N/A', 'search-analytics' );
						break;
					}

					$output = '<a href="' . get_edit_user_link( $user_data->ID ) . '">' . esc_attr( $user_data->user_nicename ) . '</a>';
					break;
			}

			echo apply_filters( 'mwtsa_stats_table_column_output', $output, $column_name, $item );
		}

		public function paginate_results() {

			//TODO: maybe move $per_page option getter to a separate method - not needed yet
			$user   = get_current_user_id();
			$screen = get_current_screen();
			$option = $screen->get_option( 'per_page', 'option' );

			$per_page = get_user_meta( $user, $option, true );

			if ( empty ( $per_page ) || $per_page < 1 ) {
				$per_page = $screen->get_option( 'per_page', 'default' );
			}

			$current_page = $this->get_pagenum();
			$total_items  = count( $this->items );

			$this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page
			) );
		}

		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->process_bulk_action();

			$this->get_items();

			$this->sort_items();

			$this->paginate_results();
		}

		public function sort_items() {
			usort( $this->items, array( &$this, 'usort_reorder' ) );
		}

		public function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'last_search_date';
			$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';

			switch ( $orderby ) {
				case 'term':
					$orderby = 'term';
					break;
				case 'searches':
					$orderby = 'count';
					break;
				case 'results':
					$orderby = 'results_count';
					break;
				case 'last_search_date':
				default:
					$orderby = 'last_search_date';
					break;
			}

			$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );

			return ( $order === 'asc' ) ? $result : - $result;
		}

		public function get_items() {

			$this->items = ( new MWTSA_History_Data )->get_terms_history_data();
		}

		public function extra_tablenav( $which ) {

			if ( $which === 'top' ) {
				$filters_str = '<div id="mwtsa-filters" class="alignleft actions">';
				if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) ) {
					$filters_str .= $this->filter_user();
				}
				$filters_str .= $this->filter_date();
				$filters_str .= sprintf( '<input type="submit" id="mwtsa-filters-submit" class="button" value="%s">', esc_attr__( 'Filter', 'search-analytics' ) );
				$filters_str .= sprintf( '&nbsp; <input type="submit" name="mwtsa-export-csv" class="button" value="%s" />', esc_attr__( 'Export Data', 'search-analytics' ) );
				$filters_str .= '</div>';

				echo $filters_str;
			}
		}

		function filter_date() {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'mwtsa-datepicker-ui' );
			wp_enqueue_script( 'jquery-ui-datepicker' );

			$date_from = isset( $_REQUEST['date_from'] ) ? $_REQUEST['date_from'] : '';
			$date_to   = isset( $_REQUEST['date_to'] ) ? $_REQUEST['date_to'] : '';

			ob_start();
			?>
            <div class="date-interval">
                <input type="text" name="date_from" class="date-picker field-from" placeholder="<?php esc_attr_e( 'Start Date', 'search-analytics' ) ?>" value="<?php echo esc_attr( $date_from ) ?>">
                <span class="dashicons dashicons-minus"></span>
                <input type="text" name="date_to" class="date-picker field-to" placeholder="<?php esc_attr_e( 'End Date', 'search-analytics' ) ?>" value="<?php echo esc_attr( $date_to ) ?>">
            </div>
			<?php

			return ob_get_clean();
		}

		function filter_user() {
			global $wpdb, $mwtsa;
			wp_enqueue_style( 'select2css' );

			$selected_user = isset( $_REQUEST['filter-user'] ) ? (int) $_REQUEST['filter-user'] : 0;

			$users_with_searches = $wpdb->get_results( "SELECT `ID`, `user_nicename` FROM $wpdb->users WHERE `ID` IN ( SELECT DISTINCT(`user_id`) FROM {$mwtsa->history_table_name})" );

			if ( empty( $users_with_searches ) ) {
				return '';
			}

			ob_start();
			?>
            <select class="select2-select" name="filter-user">
                <option value=""><?php esc_attr_e( 'Filter by user ...', 'search-analytics' ) ?></option>
				<?php foreach ( $users_with_searches as $user ) :
					printf( "<option value='%d' %s>%s</option>", (int) $user->ID, selected((int) $user->ID, $selected_user, false), esc_attr( $user->user_nicename ) );
				endforeach; ?>
            </select>
			<?php
			return ob_get_clean();
		}

		public function display_time_views() {
			$views   = [];
			$current = isset( $_REQUEST['period_view'] ) ? (int) $_REQUEST['period_view'] : 3;

			$this_url = add_query_arg( 'period_view', 0 );
			$class    = ( $current === 0 ) ? ' class="current"' : '';
			$views[0] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Last 24 hours', 'search-analytics' ) );

			$this_url = add_query_arg( 'period_view', 1 );
			$class    = ( $current === 1 ) ? ' class="current"' : '';
			$views[1] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Last week', 'search-analytics' ) );

			$this_url = add_query_arg( 'period_view', 2 );
			$class    = ( $current === 2 ) ? ' class="current"' : '';
			$views[2] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Last month', 'search-analytics' ) );

			$this_url = remove_query_arg( 'period_view' );
			$class    = ( $current === 3 ) ? ' class="current"' : '';
			$views[3] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'All time', 'search-analytics' ) );

			$this->format_views_list( $views );
		}

		public function display_results_views() {
			$views   = array();
			$current = ! empty( $_REQUEST['results_view'] ) ? (int) $_REQUEST['results_view'] : 0;

			$class    = ( $current === 0 ) ? ' class="current"' : '';
			$this_url = remove_query_arg( 'results_view' );
			$views[0] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'All', 'search-analytics' ) );

			$class    = ( $current === 1 ) ? ' class="current"' : '';
			$this_url = add_query_arg( 'results_view', 1 );
			$views[1] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Only With Results', 'search-analytics' ) );

			$this_url = add_query_arg( 'results_view', 2 );
			$class    = ( $current === 2 ) ? ' class="current"' : '';
			$views[2] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Only Without Results', 'search-analytics' ) );

			$this->format_views_list( $views );
		}

		public function display_results_grouping() {
			$views   = array();
			$current = ! empty( $_REQUEST['grouped_view'] ) ? (int) $_REQUEST['grouped_view'] : 0;

			$this_url = remove_query_arg( 'grouped_view' );
			$this_url = remove_query_arg( 'orderby', $this_url );
			$this_url = remove_query_arg( 'order', $this_url );
			$class    = ( $current === 0 ) ? ' class="current"' : '';
			$views[0] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'Term', 'search-analytics' ) );

			$this_url = add_query_arg( 'grouped_view', 1 );
			$this_url = remove_query_arg( 'orderby', $this_url );
			$this_url = remove_query_arg( 'order', $this_url );
			$class    = ( $current === 1 ) ? ' class="current"' : '';
			$views[1] = sprintf( "<a href='%s' %s>%s</a>",  esc_url( $this_url ), $class, esc_attr__( 'No Group', 'search-analytics' ) );

			$this->format_views_list( $views );
		}

		public function format_views_list( $views ) {

			$this->screen->render_screen_reader_content( 'heading_views' );

			echo "<ul class='subsubsub'>\n";
			foreach ( $views as $class => $view ) {
				$views[ $class ] = "\t<li class='$class'>$view";
			}
			echo implode( " |</li>\n", $views ) . "</li>\n";
			echo "</ul>";
		}

		public function load_notices() {
			if ( isset( $_GET['result'] ) && $_GET['result'] === 'deleted' ) {
				?>
                <div class="notice updated mwtsa-notice is-dismissible">
                    <p><?php esc_attr_e( 'Search term(s) successfully deleted', 'search-analytics' ); ?></p>
                </div>
				<?php
			}
		}
	}
endif;
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'MWTSA_History_Data' ) ) {

	class MWTSA_History_Data {

		public static function get_terms_history_data () {

			$since = 1;
			$time_unit = '';
			$only_no_results = false;
			$min_results = 0;
			$group = 'term_id';

			if ( isset( $_REQUEST['period_view'] ) ) {
				switch ( $_REQUEST['period_view'] ) {
					case 1 :
						$time_unit = 'week';
						break;
					case 2:
						$time_unit = 'month';
						break;
					case 3:
						$time_unit = '';
						break;
					default:
						$time_unit = 'day';
						break;
				}
			}

			if ( isset( $_REQUEST['results_view'] ) ) {
				switch ( $_REQUEST['results_view'] ) {
					case 1 :
						$min_results = 1;
						break;
					case 2:
						$only_no_results = true;
						break;
					default:
						//no action to be taken here
						break;
				}
			}

			if ( isset( $_REQUEST['grouped_view'] ) ) {
				switch ( $_REQUEST['grouped_view'] ) {
					case 1 :
						$group = 'no_group';
						break;
					default:
						//no action to be taken here
						break;
				}
			}

			$search_str = '';

			if( ! isset( $_REQUEST['search-term'] ) ) {
				$search_str = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
			}


			$args = array(
				'since'             => $since,
				'unit'              => $time_unit,
				'min_results'       => $min_results,
				'search_str'        => $search_str,
				'only_no_results'   => $only_no_results,
				'group'             => $group,
				'date_since'        => ( isset( $_REQUEST['date_from'] ) ) ? $_REQUEST['date_from'] : '',
				'date_until'        => ( isset( $_REQUEST['date_to'] ) ) ? $_REQUEST['date_to'] : ''
			);

			$history = self::run_terms_history_data_query( $args );

			return $history;
		}

		public static function run_terms_history_data_query ( $args ) {

			global $wpdb, $mwtsa;

			$default_args = array(
				'since'             => 1,
				'unit'              => 'day',
				'min_results'       => 0,
				'search_str'        => '',
				'only_no_results'   => false,
				'date_since'        => '',
				'date_until'        => '',
				'return_only_last'  => false,
				'group'             => 'term_id'
			);

			$args = array_merge( $default_args, $args );

			if ( in_array( $args['unit'], array( 'day', 'minute', 'week', 'month' ) ) ) {
				$args['unit'] = strtoupper( $args['unit'] );
			}

			$where = 'WHERE 1=1';

			if ( empty( $args['date_since'] ) && empty( $args['date_until'] ) ) {
				if ( $args['unit'] != '' ) {
					$where .= " AND DATE_SUB( CURDATE(), INTERVAL {$args['since']} {$args['unit']} ) <= h.datetime";
				}
			} else {
				$since = ( empty( $args['date_since'] ) ) ? time() : strtotime( $args['date_since'] );
				$until = ( empty( $args['date_until'] ) ) ? time() : ( strtotime( $args['date_until'] ) + 86399 ); //added 23:59:59 to make sure it includes the "until" day

				$since = date( 'Y-m-d H:i:s', $since );
				$until = date( 'Y-m-d H:i:s', $until );

				$where .= " AND ( h.datetime BETWEEN '$since' AND '$until' )";
			}

			if ( ! isset( $_REQUEST['search-term'] ) && in_array( $args['group'], array( 'term_id', 'no_group' ) ) ) {
				$having = '';
				$group_by = '';

				if ( $args['search_str'] != '' ) {
					$where .= " AND t.term LIKE '%{$args['search_str'] }%'";
				}

				if( ! $args['return_only_last'] ) {

					$results_count_col = 'h.count_posts as results_count';
					$datetime = '`datetime`';
					$count = '';
					$order_by = 'h.count_posts DESC, t.term ASC';

					if ( $args['group'] == 'term_id' ) {
						$results_count_col = 'AVG( h.count_posts ) as results_count';
						$datetime = 'MAX( `datetime` )';
						$count = 'COUNT( h.id ) as `count`,';

						$group_by = 'GROUP BY h.term_id';

				if ( $args['only_no_results']  ) {
							$having = " HAVING results_count = 0";
				}

				if ( $args['min_results'] > 0 ) {
							$having = " HAVING results_count > 0";
				}

						$order_by = '`count` DESC, AVG( h.count_posts ) DESC, t.term ASC';
					} else {

						if ( $args['only_no_results']  ) {
							$where .= " AND h.count_posts = 0";
						}

						if ( $args['min_results'] > 0 ) {
							$where .= " AND h.count_posts > 0";
						}
					}

					$query = "SELECT t.id, t.term, {$count} {$results_count_col}, {$datetime} as last_search_date
		                FROM {$mwtsa->terms_table_name} as t
		                JOIN {$mwtsa->history_table_name} as h ON t.id = h.term_id
		                $where
		                $group_by
		                $having
		                ORDER BY {$order_by}";
				} else {
					$query = "SELECT t.id, t.term, h.count_posts, `datetime` as last_search_date
			                FROM {$mwtsa->terms_table_name} as t
			                JOIN {$mwtsa->history_table_name} as h ON t.id = h.term_id
			                $where
			                ORDER BY `datetime` DESC
			                LIMIT 1";
				}

			} else {

				if ( isset( $_REQUEST['search-term'] ) && $_REQUEST['search-term'] != "" ) {
					$where .= " AND t.id = " . $_REQUEST['search-term'];
				}

				if ( $args['only_no_results']  ) {
					$where .= " AND h.count_posts = 0";
				} elseif ( $args['min_results'] > 0 ) {
					$where .= " AND h.count_posts > 0";
				}

				$additional_fields = '';
				$group_by = '';
				$grouped_view = '';

				if ( isset( $_REQUEST['grouped_view'] ) ) {
					$grouped_view = $_REQUEST['grouped_view'];
				} elseif ( $args['group'] != 'term_id' ) {
					$grouped_view = $args['group'];
				}

				switch ( $grouped_view ) {
					case 'day':
					case 1:
						$group_by = 'GROUP BY DAY(`datetime`)';
						break;
					case 'hour':
					case 2:
						$group_by = 'GROUP BY HOUR(`datetime`)';
						break;
				}

				if ( $group_by != '' ) {
					$additional_fields = ', COUNT( h.id ) as `count`';
				}

				$query = "SELECT h.count_posts as results_count, `datetime` {$additional_fields}
			                FROM {$mwtsa->terms_table_name} as t
			                JOIN {$mwtsa->history_table_name} as h ON t.id = h.term_id
			                $where
			                $group_by
			                ORDER BY `datetime` DESC, results_count DESC";
			}

			$history = $wpdb->get_results( $query, 'ARRAY_A' );

			return $history;
		}

		public static function get_daily_search_count_for_period_chart( $args ) {

			$default_args = array(
				'since'     => 1,
				'unit'      => 'day',
				'format'    => "d/m",
				'group'     => 'day'
			);

			$args = array_merge( $default_args, $args );

			$dates = createDateRange( '-' . $args['since'] . ' ' . $args['unit'], '', $args['format'] );

			$results = self::run_terms_history_data_query( $args );

			$_searches = $searches = array();

			foreach ( $results as $result ) {
				$this_time = date( $args['format'], strtotime( $result['datetime'] ) );
				$_searches[$this_time] = $result['count'];
			}

			foreach ( $dates as $date ) {
				$searches[$date] = ( isset( $_searches[$date] ) ) ? $_searches[$date] : 0;
			}

			return array( $dates, $searches );
		}
	}

}
<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_History_Data' ) ) {

	class MWTSA_History_Data {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		public function get_terms_history_data() {
			$since           = 1;
			$time_unit       = '';
			$only_no_results = false;
			$min_results     = 0;
			$group           = 'term_id';

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

			$search_str = empty( $_REQUEST['search-term'] ) && isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

			$user = isset( $_REQUEST['filter-user'] ) ? (int) $_REQUEST['filter-user'] : 0;

			$args = array(
				'since'           => $since,
				'unit'            => $time_unit,
				'min_results'     => $min_results,
				'search_str'      => $search_str,
				'only_no_results' => $only_no_results,
				'group'           => $group,
				'date_since'      => ( isset( $_REQUEST['date_from'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_from'] ) ) : '',
				'date_until'      => ( isset( $_REQUEST['date_to'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_to'] ) ) : '',
				'user'            => $user
			);

			return $this->run_terms_history_data_query( $args );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		public function run_terms_history_data_query( $args ) {

			global $wpdb;

			// Creates the tables if they are missing; upgrading existing ones is left to the admin.
			MWTSA_Install::activate_single_site( false );

            $instance = MWTSAI();

			$default_args = array(
				'since'            => 1,
				'unit'             => 'day',
				'min_results'      => 0,
				'search_str'       => '',
				'only_no_results'  => false,
				'date_since'       => '',
				'date_until'       => '',
				'return_only_last' => false,
				'group'            => 'term_id',
				'user'             => 0,
				'count'            => -1
			);

			$args = wp_parse_args( $args, $default_args );
			$args = apply_filters( 'mwtsa_run_terms_history_data_query_args', $args );

			$args['since'] = (int) $args['since'];

			if ( in_array( $args['unit'], array( 'minute', 'day', 'week', 'month' ) ) ) {
				$args['unit'] = strtoupper( $args['unit'] );
			} else {
				$args['unit'] = '';
			}

			$where = 'WHERE 1=1';

			if ( ! empty( $args['user'] ) ) {
				$where .= $wpdb->prepare( " AND user_id = %d", (int) $args['user'] );
			}

			if ( empty( $args['date_since'] ) && empty( $args['date_until'] ) ) {
				if ( $args['unit'] != '' ) {
					// Computed in PHP: SQLite (used by Playground) can't do MySQL's "- INTERVAL" date arithmetic.
					// To the minute, so the query text, and with it the cache key, stays the same for a minute.
					$since_datetime = gmdate( 'Y-m-d H:i:00', strtotime( '-' . $args['since'] . ' ' . strtolower( $args['unit'] ) ) );
					$where         .= $wpdb->prepare( ' AND h.datetime >= %s', $since_datetime );
				}
			} else {
				$since = ( empty( $args['date_since'] ) ) ? time() : strtotime( $args['date_since'] );
				$until = ( empty( $args['date_until'] ) ) ? time() : ( strtotime( $args['date_until'] ) + 86399 ); //added 23:59:59 to make sure it includes the "until" day

				// make sure the strtotime call did not return false
				if ( $since && $until ) {
					$since = date( 'Y-m-d H:i:s', $since ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					$until = date( 'Y-m-d H:i:s', $until ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

					$where .= $wpdb->prepare( " AND ( h.datetime BETWEEN %s AND %s )", $since, $until );
				}
			}

			$limit = '';

			if ( $args['count'] > -1 ) {
				$count = (int) $args['count'];
				$limit = " LIMIT $count";
			}

			if ( empty( $_REQUEST['search-term'] ) && in_array( $args['group'], array( 'term_id', 'no_group' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$having   = '';
				$group_by = '';

				if ( $args['search_str'] != '' ) {
					$where .= $wpdb->prepare( " AND t.term LIKE %s", $wpdb->esc_like( $args['search_str'] ) . '%' );
				}

				if ( ! $args['return_only_last'] ) {

					$results_count_col = 'h.count_posts as results_count';
					$datetime          = '`datetime`';
					$count             = '';
					$order_by          = 'h.count_posts DESC, t.term ASC';

					if ( $args['group'] == 'term_id' ) {
						$results_count_col = 'AVG( h.count_posts ) as results_count';
						$datetime          = 'MAX( `datetime` )';
						$count             = 'COUNT( h.id ) as `count`,';

						$group_by = 'GROUP BY h.term_id';

						if ( $args['only_no_results'] ) {
							$having = " HAVING results_count = 0";
						}

						if ( $args['min_results'] > 0 ) {
							$having = " HAVING results_count > 0";
						}

						$order_by = '`count` DESC, AVG( h.count_posts ) DESC, t.term ASC';
					} else {

						if ( $args['only_no_results'] ) {
							$where .= " AND h.count_posts = 0";
						}

						if ( $args['min_results'] > 0 ) {
							$where .= " AND h.count_posts > 0";
						}
					}

					$country = ', h.country';
					$user_id = ', h.user_id';

					if ( $args['group'] == 'term_id' ) {
						$country = '';
						$user_id = '';
					}

					$order_by = apply_filters( 'mwtsa_run_terms_history_order_by', $order_by, $args );

					$query = "SELECT t.id, t.term, $count $results_count_col, $datetime as last_search_date $country $user_id
		                FROM $instance->terms_table_name as t
		                JOIN $instance->history_table_name as h ON t.id = h.term_id
		                $where
		                $group_by
		                $having
		                ORDER BY $order_by
		                $limit";
				} else {
					$query = "SELECT t.id, t.term, h.count_posts, `datetime` as last_search_date
			                FROM $instance->terms_table_name as t
			                JOIN $instance->history_table_name as h ON t.id = h.term_id
			                $where
			                ORDER BY `datetime` DESC
			                LIMIT 1";
				}
			} else {

				if ( ! empty( $_REQUEST['search-term'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$where .= $wpdb->prepare( " AND t.id = %d", (int) $_REQUEST['search-term'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				if ( $args['only_no_results'] ) {
					$where .= " AND h.count_posts = 0";
				} elseif ( $args['min_results'] > 0 ) {
					$where .= " AND h.count_posts > 0";
				}

				$fields       = 'h.count_posts as results_count, `datetime`';
				$group_by     = '';
				$grouped_view = '';

				if ( isset( $_REQUEST['grouped_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$grouped_view = sanitize_text_field( wp_unslash( $_REQUEST['grouped_view'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} elseif ( $args['group'] != 'term_id' ) {
					$grouped_view = sanitize_text_field( wp_unslash( $args['group'] ) );
				}

				switch ( $grouped_view ) {
					case 'day':
					case 1:
						$group_by = 'GROUP BY DATE(`datetime`)';
						break;
					case 'hour':
					case 2:
						$group_by = 'GROUP BY HOUR(`datetime`)';
						break;
				}

				if ( $group_by != '' ) {
					$fields = 'AVG( h.count_posts ) as results_count, MAX( `datetime` ) as `datetime`, COUNT( h.id ) as `count`';
				}

				$query = "SELECT $fields
			                FROM $instance->terms_table_name as t
			                JOIN $instance->history_table_name as h ON t.id = h.term_id
			                $where
			                $group_by
			                ORDER BY `datetime` DESC, results_count DESC
			                $limit";
			}

			apply_filters_deprecated(
				'mwtsa_run_terms_history_data_query',
				array( $query, $args ),
				'1.5.0',
				'mwtsa_run_terms_history_data_query_args',
				'Modifying the raw SQL query is unsafe. Use the mwtsa_run_terms_history_data_query_args filter to modify query arguments instead.'
			);

			return $this->get_cached_results( $query );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the table names come from MWTSAI().

		/**
		 * Totals for the searches from $since up to $until (UTC "Y-m-d H:i:s"; an empty $until means up to now).
		 */
		public function get_period_summary( $since, $until = '' ) {
			global $wpdb;

			$instance = MWTSAI();
			$query    = $wpdb->prepare(
				"SELECT COUNT(*) AS searches, SUM( CASE WHEN count_posts = 0 THEN 1 ELSE 0 END ) AS no_results, COUNT( DISTINCT term_id ) AS terms
				FROM $instance->history_table_name
				WHERE `datetime` >= %s",
				$since
			);

			if ( '' !== $until ) {
				$query .= $wpdb->prepare( ' AND `datetime` < %s', $until );
			}

			$rows = $this->get_cached_results( $query );

			return array(
				'searches'   => empty( $rows ) ? 0 : (int) $rows[0]['searches'],
				'no_results' => empty( $rows ) ? 0 : (int) $rows[0]['no_results'],
				'terms'      => empty( $rows ) ? 0 : (int) $rows[0]['terms'],
			);
		}

		/**
		 * The terms searched most often since $since. With $only_no_results, only the searches that found nothing count.
		 */
		public function get_top_terms( $since, $limit = 5, $only_no_results = false ) {
			global $wpdb;

			$instance = MWTSAI();
			$filter   = $only_no_results ? 'AND h.count_posts = 0' : '';

			return $this->get_cached_results( $wpdb->prepare(
				"SELECT t.id, t.term, COUNT( h.id ) AS searches
				FROM $instance->history_table_name AS h
				JOIN $instance->terms_table_name AS t ON t.id = h.term_id
				WHERE h.`datetime` >= %s $filter
				GROUP BY t.id, t.term
				ORDER BY searches DESC, t.term ASC
				LIMIT %d",
				$since,
				$limit
			) );
		}

		/**
		 * The most recent search (its term and UTC date), or null when nothing was recorded yet.
		 */
		public function get_last_search() {
			$instance = MWTSAI();
			$rows     = $this->get_cached_results(
				"SELECT t.term, h.`datetime`
				FROM $instance->history_table_name AS h
				JOIN $instance->terms_table_name AS t ON t.id = h.term_id
				ORDER BY h.`datetime` DESC, h.id DESC
				LIMIT 1"
			);

			return empty( $rows ) ? null : $rows[0];
		}

		/**
		 * Terms searched more often since $since than from $previous_since to $since. "growth" is the difference.
		 */
		public function get_trending_terms( $since, $previous_since, $limit = 5 ) {
			global $wpdb;

			$instance = MWTSAI();

			return $this->get_cached_results( $wpdb->prepare(
				"SELECT t.id, t.term,
					SUM( CASE WHEN h.`datetime` >= %s THEN 1 ELSE 0 END ) AS searches,
					SUM( CASE WHEN h.`datetime` >= %s THEN 1 ELSE -1 END ) AS growth
				FROM $instance->history_table_name AS h
				JOIN $instance->terms_table_name AS t ON t.id = h.term_id
				WHERE h.`datetime` >= %s
				GROUP BY t.id, t.term
				HAVING growth > 0
				ORDER BY growth DESC, searches DESC, t.term ASC
				LIMIT %d",
				$since,
				$since,
				$previous_since,
				$limit
			) );
		}

		/**
		 * Searches since $since counted per UTC hour, keyed by the hour's Unix timestamp.
		 */
		public function get_hourly_counts( $since ) {
			global $wpdb;

			$instance = MWTSAI();
			// SUBSTR works on MySQL and on SQLite (Playground), unlike HOUR() with a timezone conversion.
			$rows     = $this->get_cached_results( $wpdb->prepare(
				"SELECT SUBSTR( `datetime`, 1, 13 ) AS utc_hour, COUNT(*) AS searches
				FROM $instance->history_table_name
				WHERE `datetime` >= %s
				GROUP BY utc_hour",
				$since
			) );
			$counts   = array();

			foreach ( $rows as $row ) {
				$counts[ strtotime( $row['utc_hour'] . ':00:00 UTC' ) ] = (int) $row['searches'];
			}

			return $counts;
		}

		/**
		 * Every search ever recorded, and the Unix time of the oldest search still in the history (0 if there is none).
		 */
		public function get_lifetime_stats() {
			$instance = MWTSAI();
			$total    = $this->get_cached_results( "SELECT SUM( total_count ) AS searches FROM $instance->terms_table_name" );
			$first    = $this->get_cached_results( "SELECT MIN( `datetime` ) AS first_search FROM $instance->history_table_name" );

			return array(
				'searches'     => empty( $total ) ? 0 : (int) $total[0]['searches'],
				'first_search' => empty( $first[0]['first_search'] ) ? 0 : strtotime( $first[0]['first_search'] . ' UTC' ),
			);
		}

		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * The searches of the last $days days (today included), in the statistics timezone: per day, per weekday and
		 * 4-hour interval ("grid", [0 = Sunday][0 = midnight to 4 AM]), and the busiest weekday and interval.
		 */
		public function get_search_times( $days = 28 ) {
			$timezone = mwtsa_stats_timezone();
			$today    = new DateTime( 'today', $timezone );
			$start    = clone $today;
			$start->modify( '-' . ( $days - 1 ) . ' days' );

			$per_day = array();

			for ( $day = clone $start; $day <= $today; $day->modify( '+1 day' ) ) {
				$per_day[ $day->format( 'Y-m-d' ) ] = 0;
			}

			$grid  = array_fill( 0, 7, array_fill( 0, 6, 0 ) );
			$since = clone $start;
			$since->setTimezone( new DateTimeZone( 'UTC' ) );

			foreach ( $this->get_hourly_counts( $since->format( 'Y-m-d H:i:s' ) ) as $timestamp => $count ) {
				$local = new DateTime( '@' . $timestamp );
				$local->setTimezone( $timezone );
				$date = $local->format( 'Y-m-d' );

				if ( ! isset( $per_day[ $date ] ) ) {
					continue;
				}

				$per_day[ $date ] += $count;
				$grid[ (int) $local->format( 'w' ) ][ (int) floor( (int) $local->format( 'G' ) / 4 ) ] += $count;
			}

			$per_weekday  = array_map( 'array_sum', $grid );
			$per_interval = array();

			for ( $interval = 0; $interval < 6; $interval ++ ) {
				$per_interval[ $interval ] = array_sum( array_column( $grid, $interval ) );
			}

			$total = array_sum( $per_day );

			return array(
				'timezone'         => $timezone,
				'total'            => $total,
				'per_day'          => $per_day,
				'grid'             => $grid,
				'busiest_weekday'  => $total ? array_search( max( $per_weekday ), $per_weekday, true ) : null,
				'busiest_interval' => $total ? array_search( max( $per_interval ), $per_interval, true ) : null,
				'per_weekday'      => $per_weekday,
				'per_interval'     => $per_interval,
			);
		}

		/**
		 * Cached until a new search is saved. The expiry clears keys for time windows that have moved on.
		 */
		private function get_cached_results( $query ) {
			global $wpdb;

			// Creates the tables if they are missing; upgrading existing ones is left to the admin.
			MWTSA_Install::activate_single_site( false );

			$cache_key = md5( $query ) . wp_cache_get_last_changed( 'mwtsa' );
			$results   = wp_cache_get( $cache_key, 'mwtsa' );

			if ( false === $results ) {
				$results = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
				wp_cache_set( $cache_key, $results, 'mwtsa', HOUR_IN_SECONDS );
			}

			return $results;
		}

		public function get_daily_search_count_for_period_chart( $args ) {

			$default_args = array(
				'since'   => 1,
				'unit'    => 'day',
				'format'  => "d/m",
				'group'   => 'day',
				'compare' => false
			);

			$args = wp_parse_args( $args, $default_args );

			$days     = mwtsa_create_date_range( '-' . (int) $args['since'] . ' ' . $args['unit'], '', 'Y-m-d' );
			$dates    = $this->format_chart_days( $days, $args['format'] );
			$searches = array( $this->get_daily_search_counts( $days ) );

			if ( $args['compare'] && ! empty( $days ) ) {
				// The previous period is the same number of days, ending the day before the current one starts.
				$days_count     = count( $days );
				$previous_days  = mwtsa_create_date_range( '-' . ( 2 * $days_count - 1 ) . ' days', '-' . $days_count . ' days', 'Y-m-d' );
				$previous_dates = $this->format_chart_days( $previous_days, $args['format'] );
				$searches[]     = $this->get_daily_search_counts( $previous_days );

				foreach ( $dates as $k => &$date ) {
					/* translators: 1: Initial Date, 2: Compare Date */
					$date = sprintf( esc_attr__( '%1$s vs %2$s', 'search-analytics' ), $previous_dates[ $k ], $date );
				}
				unset( $date );
			}

			return array(
				'dates'    => $dates,
				'searches' => $searches
			);
		}

		public function get_results_for_chart( $args ) {
			$format = isset( $args['format'] ) ? $args['format'] : 'd/m';
			$days   = mwtsa_create_date_range( '-' . (int) $args['since'] . ' ' . $args['unit'], '', 'Y-m-d' );
			$dates  = $this->format_chart_days( $days, $format );

			return array( $dates, array_combine( $dates, $this->get_daily_search_counts( $days ) ) );
		}

		/**
		 * Counts the searches made on each of the given UTC days (Y-m-d), in the same order.
		 */
		protected function get_daily_search_counts( $days ) {
			if ( empty( $days ) ) {
				return array();
			}

			$results = $this->run_terms_history_data_query( array(
				'group'      => 'day',
				'date_since' => reset( $days ),
				'date_until' => end( $days ),
			) );

			$counts = array_fill_keys( $days, 0 );

			foreach ( $results as $result ) {
				$day = gmdate( 'Y-m-d', strtotime( $result['datetime'] ) );

				if ( isset( $counts[ $day ] ) ) {
					$counts[ $day ] = (int) $result['count'];
				}
			}

			return array_values( $counts );
		}

		protected function format_chart_days( $days, $format ) {
			return array_map( function ( $day ) use ( $format ) {
				return gmdate( $format, strtotime( $day ) );
			}, $days );
		}
	}
}
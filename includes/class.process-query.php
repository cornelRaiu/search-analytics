<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Process_Query' ) ) {

	class MWTSA_Process_Query {

		public static function process_wpforo_search_term_action( $args, $items_count, $posts, $sql ) {
			$process = new self();

			if ( apply_filters( 'mwtsa_wpforo_do_not_save_search', false, $args['needle'] ) ) {
				return;
			}

			$process->process_search_term( $args['needle'], $items_count );
		}

		public static function process_rest_api_search_term_action() {
			$process = new self();

			$custom_search_value = $process->get_custom_search_value();

			if ( apply_filters( 'mwtsa_rest_api_do_not_save_search', $custom_search_value === '', $custom_search_value ) ) {
				return;
			}

			$result_count = apply_filters( 'mwtsa_rest_api_result_count', 0, $custom_search_value );

			if ( $result_count === 0 ) {
				// Only the number of matches is needed: fetch a single ID and let the query count the rest.
				$args = array(
					'posts_per_page'      => 1,
					'post_status'         => 'publish',
					'post_type'           => 'any',
					'offset'              => 0,
					'fields'              => 'ids',
					's'                   => $custom_search_value,
					'suppress_filters'    => true,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => false,
				);

				$query        = new WP_Query( apply_filters( 'mwtsa_rest_api_posts_count_query_args', $args ) );
				$result_count = (int) $query->found_posts;
			}

			$process->process_search_term( $custom_search_value, $result_count );
		}

		public static function process_search_term_action() {
			global $wp_query;

			$process = new self();

			$custom_search_value = $process->get_custom_search_value();

			// Paging through the results or loading the search feed is not a new search.
			$do_not_save = ( ! is_search() && $custom_search_value == '' ) || is_admin() || is_paged() || is_feed();

			if ( apply_filters( 'mwtsa_do_not_save_search', $do_not_save, $custom_search_value ) ) {
				return;
			}

			$search_term = $custom_search_value != '' ? $custom_search_value : get_search_query();

			$process->process_search_term( $search_term, apply_filters( 'mwtsa_result_count', $wp_query->found_posts, $search_term ) );
		}

		public function get_custom_search_value() {
			$exclude_custom_search_params = MWTSA_Options::get_option( 'mwtsa_custom_search_url_params' );

			if ( empty( $exclude_custom_search_params ) ) {
				return '';
			}

			$custom_search_params = array_map( 'trim', explode( ',', $exclude_custom_search_params ) );
			foreach ( $custom_search_params as $param ) {
				if ( ! empty( $_REQUEST[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return sanitize_text_field( wp_unslash( $_REQUEST[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			}

			return '';
		}

		public static function normalize_search_term( $term ) {
			return mb_substr( sanitize_text_field( $term ), 0, 100 );
		}

		/**
		 * Looks the visitor's country up with the selected service, at most once per IP per day (an hour after a
		 * failed lookup), and never for private or reserved addresses.
		 *
		 * @return string Lowercase two-letter country code, or '' when unknown.
		 */
		public static function get_country_for_ip( $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return '';
			}

			$use_ip2c = 'ip2c' === MWTSA_Options::get_option( 'mwtsa_geolocation_provider' );

			// ip2c.org only supports IPv4. No fallback: the site owner chose which service receives the addresses.
			if ( $use_ip2c && ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return '';
			}

			$cache_key = 'mwtsa_geo_' . md5( $ip );
			$country   = get_transient( $cache_key );

			if ( false !== $country ) {
				return $country;
			}

			$country = $use_ip2c ? self::get_ip2c_country( $ip ) : self::get_ip_api_country( $ip );

			set_transient( $cache_key, $country, '' === $country ? HOUR_IN_SECONDS : DAY_IN_SECONDS );

			return $country;
		}

		private static function get_ip_api_country( $ip ) {
			// The free endpoint only works over HTTP: https://ip-api.com/docs/api:json
			$response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=49155', array( 'timeout' => 2 ) );
			$details  = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $details && ! empty( $details->status ) && 'fail' !== $details->status && ! empty( $details->countryCode ) ) {
				return self::sanitize_country_code( $details->countryCode );
			}

			return '';
		}

		private static function get_ip2c_country( $ip ) {
			// Answers "1;US;USA;Country name" (https://about.ip2c.org). Not ip2c.org/s: that looks up this server.
			$response = wp_remote_get( 'https://ip2c.org/' . $ip, array( 'timeout' => 2 ) );
			$parts    = explode( ';', trim( wp_remote_retrieve_body( $response ) ) );

			return ( '1' === $parts[0] && isset( $parts[1] ) ) ? self::sanitize_country_code( $parts[1] ) : '';
		}

		private static function sanitize_country_code( $code ) {
			$code = strtolower( trim( $code ) );

			// "zz" means unknown or reserved.
			return ( preg_match( '/^[a-z]{2}$/', $code ) && 'zz' !== $code ) ? $code : '';
		}

		public function process_search_term( $search_term, $count ) {

			$search_term = self::normalize_search_term( $search_term );

			$exclude_search_for_roles = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role' );
			$current_user_roles       = mwtsa_get_current_user_roles();

			$exclude_search_for_roles_after_logout = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role_after_logout' );

			if ( ! empty( $exclude_search_for_roles_after_logout ) ) {
				$current_user_cookie = MWTSA_Cookies::get_cookie_value();

				if ( isset( $current_user_cookie['is_excluded'] ) && $current_user_cookie['is_excluded'] == 1 ) {
					return false;
				}
			}

			if ( is_array( $exclude_search_for_roles ) ) {
				$matching_roles = array_intersect( $exclude_search_for_roles, $current_user_roles );

				if ( count( $matching_roles ) > 0 ) {
					return false;
				}
			}

			$exclude_search_for_ips = MWTSA_Options::get_option( 'mwtsa_exclude_searches_from_ip_addresses' );

			$client_ip = mwtsa_get_current_user_ip();

			if ( ! empty( $exclude_search_for_ips ) ) {
				$ips_list     = array();
				$excluded_ips = explode( ',', $exclude_search_for_ips );

				foreach ( $excluded_ips as $ip ) {
					$ip = trim( $ip );

					if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						$ips_list[] = $ip;
					}
				}

				if ( in_array( $client_ip, $ips_list ) ) {
					return false;
				}
			}

			$exclude_if_contains = MWTSA_Options::get_option( 'mwtsa_exclude_if_string_contains' );

			if ( ! empty( $exclude_if_contains ) ) {
				// Skip empty entries (e.g. a trailing comma): an empty alternative would match every search.
				$excluded_strings = array_filter( array_map( 'trim', explode( ',', $exclude_if_contains ) ), 'strlen' );

				if ( ! empty( $excluded_strings ) ) {
					$match_against = array_map( function ( $excluded_string ) {
						return preg_quote( $excluded_string, '/' );
					}, $excluded_strings );

					// Match the text as typed (front-end terms arrive HTML-escaped); "u" makes "i" work beyond ASCII.
					$subject = wp_specialchars_decode( $search_term, ENT_QUOTES );

					if ( preg_match( '/(' . implode( '|', $match_against ) . ')/iu', $subject ) ) {
						return false;
					}
				}
			}

			if ( apply_filters( 'mwtsa_extra_exclude_conditions', false, $search_term ) ) {
				return false;
			}

			// null: look the country up in save_search_term(), only once the search is actually going to be recorded.
			$country = ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_country' ) ) ? null : '';

			$user_id = 0;

			if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) && is_user_logged_in() ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}

			if ( ! empty( $search_term ) ) {

				$minimum_length_term = MWTSA_Options::get_option( 'mwtsa_minimum_characters' );

				// Characters, not bytes: "кот" is 3 characters but 6 bytes.
				if ( ! empty( $minimum_length_term ) && mb_strlen( $search_term ) < (int) $minimum_length_term ) {
					return false;
				}

				if ( apply_filters( 'mwtsa_exclude_term', false, $search_term ) ) {
					return false;
				}

				return (bool) $this->save_search_term( $search_term, $count, $country, $user_id );
			}

			return false;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- MWTSAI()->terms_table_name and MWTSAI()->history_table_name are hardcoded.
		public function save_search_term( $term, $found_posts, $country = '', $user_id = 0 ) {
			global $wpdb;

			// Also called directly by code that records its own searches, so normalize here too.
			$term = self::normalize_search_term( $term );

			if ( '' === $term ) {
				return false;
			}

			// Creates the tables if they are missing; upgrading existing ones is left to the admin.
			MWTSA_Install::activate_single_site( false );

            $instance = MWTSAI();

			//1. add/update term string
			$existing_term = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT *
				FROM `$instance->terms_table_name`
				WHERE term = %s
				LIMIT 1
				", $term
			) );

			$exclude_doubled_search_for = (int) MWTSA_Options::get_option( 'mwtsa_exclude_doubled_search_for_interval' );

			$current_user_cookie = MWTSA_Cookies::get_cookie_value();

			$term_id = null;

			if ( empty ( $existing_term ) ) {
				$success = $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					"INSERT INTO `$instance->terms_table_name` (`term`, `total_count`)
					VALUES (%s, %d)",
					$term,
					1
				) );

				if ( $success ) {
					$term_id = $wpdb->insert_id;
				}
			} else {

				if ( ! empty ( $exclude_doubled_search_for ) ) {
					if ( isset( $current_user_cookie['search'] ) && isset( $current_user_cookie['search'][ $existing_term->id ] ) && ( $current_user_cookie['search'][ $existing_term->id ] + ( 60 * $exclude_doubled_search_for ) ) > time() ) {
						return false;
					}
				}

				// Increment in the query itself, so concurrent searches for the same term don't overwrite each other.
				$success = $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					"UPDATE `$instance->terms_table_name`
					SET total_count = total_count + 1
					WHERE id = %d
					", $existing_term->id
				) );

				if ( $success ) {
					$term_id = $existing_term->id;
				}
			}

			do_action( 'mwtsa_after_term_save', $term, $term_id, ! empty( $existing_term ) );

			//2. add term timestamp + posts_count - ON term_id
			if ( ! empty( $term_id ) ) {

				$history_term_id = null;

				if ( null === $country ) {
					$country = self::get_country_for_ip( mwtsa_get_current_user_ip() );
				}

				if ( ! empty ( $exclude_doubled_search_for ) ) {
					$current_user_cookie = MWTSA_Cookies::remove_expired_searches( $current_user_cookie, $exclude_doubled_search_for );
					$current_user_cookie['search'][ $term_id ] = time();
					MWTSA_Cookies::set_cookie_value( $current_user_cookie, ( 86400 * 7 ) );
				}

				$success = $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					"INSERT INTO `$instance->history_table_name` (`term_id`, `datetime`, `count_posts`, `country`, `user_id`)
					VALUES (%d, UTC_TIMESTAMP(), %d, %s, %d)",
					$term_id,
					$found_posts,
					$country,
					$user_id
				) );

				if ( $success ) {
					$history_term_id = $wpdb->insert_id;
				}

				do_action( 'mwtsa_after_history_term_save', $history_term_id, $term_id, $found_posts, $country, $user_id );

				wp_cache_set( 'last_changed', microtime(), 'mwtsa' );
			}

			return $success;
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
	}
}
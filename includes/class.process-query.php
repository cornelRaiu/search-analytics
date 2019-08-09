<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Process_Query' ) ) {

	class MWTSA_Process_Query {

		public function __construct() {

			add_action( 'wp', array( $this, 'process_search_term' ), 20 );
		}

		public function process_search_term() {
			global $wp_query;

			if ( ! is_search() || is_admin() ) {
				return false;
			}

			$exclude_search_for_roles = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role' );
			$current_user_roles = mwt_get_current_user_roles();

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

			if ( ! empty( $exclude_search_for_ips ) ) {
				$ips_list = array();
				$excluded_ips = explode( ',', $exclude_search_for_ips );

				foreach ( $excluded_ips as $ip ) {
					$ip = trim( $ip );

					if ( filter_var($ip, FILTER_VALIDATE_IP) ) {
						$ips_list[] = $ip;
					}
				}

				$client_ip = isset( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] : ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );

				if ( in_array( $client_ip, $ips_list ) ) {
					return false;
				}
			}

			$search_term = get_search_query();
			$total_found_posts = $wp_query->found_posts;

			if( ! empty( $search_term ) ) {

				$minimum_length_term = MWTSA_Options::get_option( 'mwtsa_minimum_characters' );

				if ( ! empty( $minimum_length_term ) && strlen( $search_term ) < $minimum_length_term ) {
					return false;
				}

				$this->save_search_term( $search_term, $total_found_posts );
			}
		}

		public function save_search_term( $term, $found_posts ) {
			global $wpdb, $mwtsa;


			//1. add/update term string
			$existing_term = $wpdb->get_row( $wpdb->prepare(
				"SELECT *
				FROM `{$mwtsa->terms_table_name}`
				WHERE term = %s
				LIMIT 1
				", $term
			) );

			$exclude_doubled_search_for = MWTSA_Options::get_option( 'mwtsa_exclude_doubled_search_for_interval' );

			$current_user_cookie = MWTSA_Cookies::get_cookie_value();

			if ( empty ( $existing_term ) ) {
				$success = $wpdb->query( $wpdb->prepare(
					"INSERT INTO `{$mwtsa->terms_table_name}` (`term`, `total_count`)
					VALUES (%s, %d)",
					$term,
					1
				) );

				if ( $success ) {
					$term_id = $wpdb->insert_id;
				}
			} else {
				
				if ( ! empty ( $exclude_doubled_search_for ) ) {
					if ( isset( $current_user_cookie['search'] ) && isset( $current_user_cookie['search'][$existing_term->id] ) && ( $current_user_cookie['search'][$existing_term->id] + ( 60 * $exclude_doubled_search_for ) ) > time() ) {
						return false;
					}
				}

				$total_count = $existing_term->total_count + 1;

				$success = $wpdb->query( $wpdb->prepare(
					"UPDATE `{$mwtsa->terms_table_name}`
					SET total_count = %d
					WHERE term = %s
					LIMIT 1
					", $total_count, $term
				) );

				if ( $success ) {
					$term_id = $existing_term->id;
				}
			}

			//2. add term timestamp + posts_count - ON term_id
			if ( ! empty( $term_id ) ) {

				if ( ! empty ( $exclude_doubled_search_for ) ) {
					$current_user_cookie['search'][$term_id] = time();
					MWTSA_Cookies::set_cookie_value( $current_user_cookie, ( 86400 * 7 ) );
				}

				$success = $wpdb->query( $wpdb->prepare(
					"INSERT INTO `{$mwtsa->history_table_name}` (`term_id`, `datetime`, `count_posts`)
					VALUES (%d, UTC_TIMESTAMP(), %d)",
					$term_id,
					$found_posts
				) );
			}
		}
	}

}

return new MWTSA_Process_Query();
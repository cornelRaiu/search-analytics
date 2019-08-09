<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Cookies' ) ) {

	class MWTSA_Cookies {

		public function __construct() {
			add_action( 'wp_login', array( $this, 'set_is_excluded_cookie_if_needed'), 10, 2 );
			add_action( 'init', array( $this, 'clear_expired_search_history' ) );
		}

		public function clear_expired_search_history() {
			$current_user_cookie = MWTSA_Cookies::get_cookie_value();
			$exclude_doubled_search_for = MWTSA_Options::get_option( 'mwtsa_exclude_doubled_search_for_interval' );
			if ( isset( $current_user_cookie['search'] ) ) {
				foreach ( $current_user_cookie['search'] as $term_id => $time ) {
					if ( ( $time + ( 60 * $exclude_doubled_search_for ) ) < time() ) {
						unset( $current_user_cookie['search'][$term_id] );
					}
				}

				MWTSA_Cookies::set_cookie_value( $current_user_cookie, ( 86400 * 7 ) );
			}
		}

		public function set_is_excluded_cookie_if_needed( $user_login, $user ) {

			$exclude_search_for_roles_after_logout = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role_after_logout' );
			$exclude_search_for_roles = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role' );
			$current_user_roles = mwt_get_user_roles( $user );

			if ( is_array( $exclude_search_for_roles ) && ! empty( $exclude_search_for_roles_after_logout ) ) {
				$matching_roles = array_intersect( $exclude_search_for_roles, $current_user_roles );

				if ( count( $matching_roles ) > 0 ) {
					$current_user_cookie = MWTSA_Cookies::get_cookie_value();
					$current_user_cookie['is_excluded'] = 1;

					self::set_cookie_value( $current_user_cookie, ( 86400 * 7 ) ); //expire in 7 days
					//TODO: maybe make the number of days a setting?
				}
			}
			
		}

		public static function get_cookie_value() {
			return ( isset( $_COOKIE[MWTSAI()->cookie_name] ) ) ? json_decode( wp_unslash( $_COOKIE[MWTSAI()->cookie_name] ), true ) : array();
		}

		public static function set_cookie_value( $value, $expire_delay = MONTH_IN_SECONDS ) {
			$value = json_encode( $value );

			setcookie( MWTSAI()->cookie_name, $value, time() + $expire_delay, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

}

return new MWTSA_Cookies();
<?php
defined("ABSPATH") || exit;

if ( ! function_exists( 'mwtsa_array_val' ) ) {
	function mwtsa_array_val( $arr, $key ) {
		return ( is_array( $arr ) && isset( $arr[ $key ] ) ) ? $arr[ $key ] : false;
	}
}

if ( ! function_exists( 'mwt_array_val' ) ) {
	/**
	 * @deprecated since 1.5.0 use mwtsa_array_val() instead.
	 */
	function mwt_array_val( $arr, $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.5.0', 'mwtsa_array_val' );
		return mwtsa_array_val( $arr, $key );
	}
}

if ( ! function_exists( 'mwtsa_get_current_user_roles' ) ) {
	function mwtsa_get_current_user_roles() {
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			return ( array ) $user->roles;
		}

		return array();
	}
}

if ( ! function_exists( 'mwt_get_current_user_roles' ) ) {
	/**
	 * @deprecated since 1.5.0 use mwtsa_get_current_user_roles() instead.
	 */
	function mwt_get_current_user_roles() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.5.0', 'mwtsa_get_current_user_roles' );
		return mwtsa_get_current_user_roles();
	}
}

if ( ! function_exists( 'mwtsa_get_user_roles' ) ) {
	function mwtsa_get_user_roles( $user ) {
		if ( ! empty( $user ) ) {
			return ( array ) $user->roles;
		}

		return array();
	}
}

if ( ! function_exists( 'mwt_get_user_roles' ) ) {
	/**
	 * @deprecated since 1.5.0 use mwtsa_get_user_roles() instead.
	 */
	function mwt_get_user_roles( $user ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.5.0', 'mwtsa_get_user_roles' );
		return mwtsa_get_user_roles( $user );
	}
}

if ( ! function_exists( 'mwtsa_wp_date_format_to_js_datepicker_format' ) ) {
	function mwtsa_wp_date_format_to_js_datepicker_format( $dateFormat ) {

		$chars = array(
			// Day
			'd' => 'dd',
			'j' => 'd',
			'l' => 'DD',
			'D' => 'D',
			// Month
			'm' => 'mm',
			'n' => 'm',
			'F' => 'MM',
			'M' => 'M',
			// Year
			'Y' => 'yy',
			'y' => 'y',
		);

		return strtr( (string) $dateFormat, $chars );
	}
}

if ( ! function_exists( 'mwt_wp_date_format_to_js_datepicker_format' ) ) {
	/**
	 * @deprecated since 1.4.4 use mwtsa_wp_date_format_to_js_datepicker_format() instead.
	 */
	function mwt_wp_date_format_to_js_datepicker_format( $dateFormat ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.4.4', 'mwtsa_wp_date_format_to_js_datepicker_format' );
		return mwtsa_wp_date_format_to_js_datepicker_format( $dateFormat );
	}
}

if ( ! function_exists( 'mwtsa_create_date_range' ) ) {
	function mwtsa_create_date_range( $startDate, $endDate, $format = "Y-m-d", $include_today = true ) {
		$begin = new DateTime( $startDate );
		$end   = new DateTime( $endDate );

		// Work with whole days: both ends are created microseconds apart, which otherwise adds an extra day.
		$begin->setTime( 0, 0 );
		$end->setTime( 0, 0 );

		if ( $include_today ) {
			$end->add( new DateInterval( 'P1D' ) );
		}

		$range = array();

		try {
			$interval  = new DateInterval( 'P1D' );
			$dateRange = new DatePeriod( $begin, $interval, $end );
			foreach ( $dateRange as $date ) {
				$range[] = $date->format( $format );
			}
		} catch ( Exception $e ) {

		}

		return $range;
	}
}

if ( ! function_exists( 'mwt_create_date_range' ) ) {
	/**
	 * @deprecated since 1.5.0 use mwtsa_create_date_range() instead.
	 */
	function mwt_create_date_range( $startDate, $endDate, $format = "Y-m-d", $include_today = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.5.0', 'mwtsa_create_date_range' );
		return mwtsa_create_date_range( $startDate, $endDate, $format, $include_today );
	}
}

if ( ! function_exists( 'mwtsa_get_current_user_ip' ) ) {
	function mwtsa_get_current_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded_ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$candidate     = trim( $forwarded_ips[0] );
			if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
				$ip = $candidate;
			}
		}

		if ( empty( $ip ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip ? $ip : '*******';
	}
}

if ( ! function_exists( 'mwt_get_current_user_ip' ) ) {
	/**
	 * @deprecated since 1.5.0 use mwtsa_get_current_user_ip() instead.
	 */
	function mwt_get_current_user_ip() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		_deprecated_function( __FUNCTION__, '1.5.0', 'mwtsa_get_current_user_ip' );
		return mwtsa_get_current_user_ip();
	}
}

if ( ! function_exists( 'mwtsa_wp_timezone_string' ) ) {
	function mwtsa_wp_timezone_string() {
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_minutes  = abs( $minutes * 60 );

		return sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_minutes );
	}
}

if ( ! function_exists( 'mwtsa_wp_timezone' ) ) {
	function mwtsa_wp_timezone() {
		return new DateTimeZone( mwtsa_wp_timezone_string() );
	}
}

if ( ! function_exists( 'mwtsa_country_flag_emoji' ) ) {
	/**
	 * Returns the flag emoji for a two-letter country code, or '' for anything else.
	 */
	function mwtsa_country_flag_emoji( $country_code ) {
		$code = strtoupper( trim( (string) $country_code ) );

		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			return '';
		}

		// A flag is the pair of regional indicator symbols for its letters; "A" (65) + 127397 = U+1F1E6.
		return html_entity_decode( '&#' . ( ord( $code[0] ) + 127397 ) . ';&#' . ( ord( $code[1] ) + 127397 ) . ';', ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'mwtsa_apply_list_table_filter' ) ) {
	/**
	 * Applies one of the statistics tables' filters. Search Analytics 2.0 replaces these tables and the filters will
	 * not run there, so sites that use them get a deprecation notice (in debug mode, once per filter per request).
	 */
	function mwtsa_apply_list_table_filter( $hook, $value ) {
		static $notified = array();

		$args = func_get_args();
		array_shift( $args );

		if ( ! isset( $notified[ $hook ] ) && has_filter( $hook ) ) {
			$notified[ $hook ] = true;
			_deprecated_hook( $hook, '1.6.0', '', __( 'The statistics screens are rebuilt in Search Analytics 2.0, where this filter no longer runs.', 'search-analytics' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes the message.
		}

		return apply_filters_ref_array( $hook, $args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- only called with the plugin's own mwtsa_ hooks.
	}
}

if ( ! function_exists( 'mwtsa_current_user_can_view_stats' ) ) {
	/**
	 * Whether the current user has one of the roles allowed to see (and export) the statistics.
	 */
	function mwtsa_current_user_can_view_stats() {
		$options = MWTSA_Options::get_options();

		return count( array_intersect( mwtsa_get_current_user_roles(), (array) $options['mwtsa_display_stats_for_role'] ) ) > 0;
	}
}

if ( ! function_exists( 'mwtsa_current_user_can_manage_settings' ) ) {
	/**
	 * Whether the current user has one of the roles allowed to change the settings and delete search data.
	 */
	function mwtsa_current_user_can_manage_settings() {
		$options = MWTSA_Options::get_options();

		return count( array_intersect( mwtsa_get_current_user_roles(), (array) $options['mwtsa_display_settings_for_role'] ) ) > 0;
	}
}

if ( ! function_exists( 'mwtsa_process_search_term' ) ) {
	/**
	 * Records a search from your own code, such as an AJAX live search, with the same checks as a normal search.
	 * Returns whether it was saved.
	 */
	function mwtsa_process_search_term( $term, $result_count ) {
		return ( new MWTSA_Process_Query() )->process_search_term( $term, (int) $result_count );
	}
}
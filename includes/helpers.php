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
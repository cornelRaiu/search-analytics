<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'mwt_array_val' ) ) {
	function mwt_array_val( $arr, $key ) {
		return ( is_array( $arr ) && isset( $arr[$key] ) ) ? $arr[$key] : false;
	}
}

if ( ! function_exists( 'mwt_get_current_user_roles' ) ) {
	function mwt_get_current_user_roles() {
		if( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$roles = ( array ) $user->roles;
			return $roles;
		}

		return array();
	}
}

if ( ! function_exists( 'mwt_get_user_roles' ) ) {
	function mwt_get_user_roles( $user ) {
		if( ! empty( $user ) ) {
			$roles = ( array ) $user->roles;
			return $roles;
		}

		return array();
	}
}

if ( ! function_exists( 'mwt_wp_date_format_to_js_datepicker_format' ) ) {
	function mwt_wp_date_format_to_js_datepicker_format( $dateFormat ) {

		$chars = array(
			// Day
			'd' => 'dd', 'j' => 'd', 'l' => 'DD', 'D' => 'D',
			// Month
			'm' => 'mm', 'n' => 'm', 'F' => 'MM', 'M' => 'M',
			// Year
			'Y' => 'yy', 'y' => 'y',
		);

		return strtr( (string) $dateFormat, $chars );
	}
}

if ( ! function_exists( 'createDateRange' ) ) {
	function createDateRange( $startDate, $endDate, $format = "Y-m-d" ) {
		$begin = new DateTime($startDate);
		$end = new DateTime($endDate);

		$range = array();


		try {
			$interval = new DateInterval( 'P1D' ); // 1 Day
			$dateRange = new DatePeriod( $begin, $interval, $end );
			foreach ( $dateRange as $date ) {
				$range[] = $date->format( $format );
			}
		} catch ( Exception $e ) {

		}

		return $range;
	}
}

<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Options' ) ) {

	class MWTSA_Options {

		public static $option_name = 'mwtsa_settings';
		public static $existing_options = array();

		public static function init_options() {
			self::$existing_options = get_option( self::$option_name, array() );

			$options = array(
				'mwtsa_display_stats_for_role'               => [
					'administrator'
				],
				'mwtsa_exclude_search_for_role'              => [],
				'mwtsa_exclude_search_for_role_after_logout' => 0,
				'mwtsa_save_search_country'                  => 0,
				'mwtsa_save_search_by_user'                  => 0,
				'mwtsa_exclude_doubled_search_for_interval'  => 0,
				'mwtsa_exclude_searches_from_ip_addresses'   => '',
				'mwtsa_minimum_characters'                   => 1,
				'mwtsa_uninstall'                            => 0,
				'mwtsa_hide_charts'                          => 0,
				'mwtsa_show_dates_as_utc'                    => 1,

				'mwtsa_custom_search_url_params'   => '',
//				'mwtsa_display_did_you_know' => 1,
				'mwtsa_exclude_if_string_contains' => '',

				'chart_default_line_style' => 'basic',
				'chart_default_range'      => '2w'
			);

			foreach ( $options as $key => $option ) {
				if ( ! isset( self::$existing_options[ $key ] ) ) {
					self::$existing_options[ $key ] = $option;
				}
			}
		}

		public static function sanitize_options() {
			$options = self::$existing_options;

			if ( ! isset( $options['mwtsa_display_settings_for_role'] ) || ! in_array( 'administrator', $options['mwtsa_display_settings_for_role'] ) ) {
				self::$existing_options['mwtsa_display_settings_for_role'][] = 'administrator';
			}

			if ( ! isset( $options['mwtsa_display_stats_for_role'] ) || ! in_array( 'administrator', $options['mwtsa_display_stats_for_role'] ) ) {
				self::$existing_options['mwtsa_display_stats_for_role'][] = 'administrator';
			}
		}

		public static function get_options() {
			if ( empty( self::$existing_options ) ) {
				self::init_options();
			}

			self::sanitize_options();

			return self::$existing_options;
		}

		public static function get_option( $name ) {
			if ( empty( self::$existing_options ) ) {
				self::init_options();
			}

			return ( isset( self::$existing_options[ $name ] ) ) ? self::$existing_options[ $name ] : false;
		}

		public static function set_option( $name, $value ) {
			$options = self::get_options();

			$options[ $name ] = $value;

			update_option( self::$option_name, $options );
		}

		public static function set_options( $_options ) {
			$options = self::get_options();

			foreach ( $_options as $name => $_option ) {
				$options[ $name ] = $_option;
			}

			update_option( self::$option_name, $options );
		}
	}
}
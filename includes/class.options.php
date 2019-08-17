<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('MWTSA_Options') ) {

	class MWTSA_Options {

		public static $option_name = 'mwtsa_settings';
		public static $existing_options;

		public static function init_options() {
			self::$existing_options = get_option( self::$option_name );

			$options = array(
				'mwtsa_exclude_search_for_role' => array()
			);

			foreach ( $options as $k => $o ) {
				if ( ! isset( self::$existing_options[$k] ) ) {
					self::$existing_options[$k] = $o;
				}
			}
		}

		public static function get_options() {
			if ( empty( self::$existing_options ) ) {
				self::init_options();
			}

			return self::$existing_options;
		}

		public static function get_option( $name ) {
			if ( empty( self::$existing_options ) ) {
				self::init_options();
			}

			return ( isset( self::$existing_options[$name]) ) ? self::$existing_options[$name] : false;
		}

		public static function set_option ( $name, $value ) {
			$options = self::get_options();

			$options[$name] = $value;

			update_option( self::$option_name, $options );
		}

		public static function set_options ( $_options ) {
			$options = self::get_options();

			foreach ( $_options as $name => $_option ) {
				$options[$name] = $_option;
			}

			update_option( self::$option_name, $options );
		}
	}
}
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Uninstall' ) ) {

	class MWTSA_Uninstall {

		public static function deactivation() {
			global $wpdb, $mwtsa;

			$remove_tables = MWTSA_Options::get_option( 'mwtsa_uninstall' );

			if ( $remove_tables ) {
				$table_name = $mwtsa->history_table_name;
				$sql = "DROP TABLE IF EXISTS $table_name";
				$wpdb->query( $sql );

				$table_name = $mwtsa->terms_table_name;
				$sql = "DROP TABLE IF EXISTS $table_name";
				$wpdb->query( $sql );

				delete_option( "mwtsa_db_version" );
			}

		}
	}

}
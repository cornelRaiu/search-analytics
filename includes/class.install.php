<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Install' ) ) {

	class MWTSA_Install {

		public static function activation() {
			global $wpdb, $mwtsa;

			$mwtsa = MWTSAI();

			$current_db_version = get_option( 'mwtsa_db_version' );

			if( ! empty( $current_db_version ) && $current_db_version == $mwtsa->db_version ) {
				return;
			}

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();

			// Search terms Table
			$table_name = $mwtsa->terms_table_name;

			$sql = "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term VARCHAR(100) NOT NULL,
			  total_count INT(11) UNSIGNED NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";

			// Search History table
			$table_name = $mwtsa->history_table_name;

			$sql .= "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term_id int(11) UNSIGNED NOT NULL,
			  `datetime` DATETIME NOT NULL,
			  count_posts MEDIUMINT(9) UNSIGNED NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta( $sql );

			update_option( 'mwtsa_db_version' , $mwtsa->db_version );
		}
	}

}
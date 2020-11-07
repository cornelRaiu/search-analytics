<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Install' ) ) {

    class MWTSA_Install {

        public static function activation( $network_wide ) {
            global $wpdb;

            if ( $network_wide ) {

                if ( function_exists( 'get_sites' ) && function_exists( 'get_current_network_id' ) ) {
                    $site_ids = get_sites( array( 'fields' => 'ids', 'network_id' => get_current_network_id() ) );
                } else {
                    //fallback for WP < 4.6
                    $site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;" );
                }

                foreach ( $site_ids as $site_id ) {
                    switch_to_blog( $site_id );
                    self::activate_single_site();
                    restore_current_blog();
                }
            } else {
                self::activate_single_site();
            }
        }

        public static function activate_single_site() {
            self::setup_db_tables();
            self::update_options();
        }

        public static function setup_db_tables() {
            global $wpdb, $mwtsa;

            $mwtsa = MWTSAI();

            $current_db_version = get_option( 'mwtsa_db_version' );

            if ( ! empty( $current_db_version ) && $current_db_version == $mwtsa->db_version ) {
                return;
            }

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $charset_collate = $wpdb->get_charset_collate();

            // Search terms Table
            $table_name = $wpdb->prefix . $mwtsa->terms_table_name_no_prefix;
            self::register_table( $table_name );

            $sql = "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term VARCHAR(100) NOT NULL,
			  total_count int(11) UNSIGNED NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";

            // Search History table
            $table_name = $wpdb->prefix . $mwtsa->history_table_name_no_prefix;
            self::register_table( $table_name );

            $sql .= "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term_id int(11) UNSIGNED NOT NULL,
			  `datetime` DATETIME NOT NULL,
			  count_posts MEDIUMINT(9) UNSIGNED NOT NULL,
			  country VARCHAR(50) DEFAULT '',
			  user_id int(11) UNSIGNED NOT NULL DEFAULT '0',
 			  PRIMARY KEY  (id)
			) $charset_collate;";

            dbDelta( $sql );

            update_option( 'mwtsa_db_version', $mwtsa->db_version );
        }

        public static function register_table( $table_name ) {
            global $wpdb;

            if ( in_array( $table_name, $wpdb->tables, true ) ) {
                return;
            }

            $wpdb->{$table_name} = $table_name;
            $wpdb->tables[]      = $table_name;
        }

        public static function update_options() {
            $display_stats    = MWTSA_Options::get_option( 'mwtsa_display_stats_for_role' );
            $display_settings = MWTSA_Options::get_option( 'mwtsa_display_settings_for_role' );

            if ( ! empty( $display_stats ) && empty( $display_settings ) ) {
                MWTSA_Options::set_option( 'mwtsa_display_settings_for_role', $display_stats );
            }
        }
    }
}
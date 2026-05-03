<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Uninstall' ) ) {

    class MWTSA_Uninstall {

        public static function deactivation() {
            global $wpdb;

            $remove_tables = MWTSA_Options::get_option( 'mwtsa_uninstall' );
            if ( empty( $remove_tables ) ) {
                return;
            }

            if ( is_multisite() ) {
                // Retrieve all site IDs from all networks (WordPress >= 4.6 provides easy to use functions for that).
                if ( function_exists( 'get_sites' ) ) {
                    $site_ids = get_sites( array( 'fields' => 'ids' ) );
                } else {
                    $site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs;" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                }

                // Uninstall the plugin for all these sites.
                foreach ( $site_ids as $site_id ) {
                    switch_to_blog( $site_id );
                    self::deactivate_single_site();
                    restore_current_blog();
                }
            } else {
                self::deactivate_single_site();
            }
        }


        public static function deactivate_single_site() {
            global $wpdb;

            $table_name = $wpdb->prefix . MWTSAI()->history_table_name_no_prefix;
            $wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter

            $table_name = $wpdb->prefix . MWTSAI()->terms_table_name_no_prefix;
            $wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter

            delete_option( "mwtsa_db_version" );
        }
    }
}
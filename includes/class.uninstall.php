<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Uninstall' ) ) {

    class MWTSA_Uninstall {

        public static function uninstall() {
            $removed_data = false;

            if ( is_multisite() ) {
                $site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

                foreach ( $site_ids as $site_id ) {
                    switch_to_blog( $site_id );
                    $removed_data = self::uninstall_single_site() || $removed_data;
                    restore_current_blog();
                }
            } else {
                $removed_data = self::uninstall_single_site();
            }

            if ( $removed_data ) {
                delete_metadata( 'user', 0, 'mwtsa_entries_per_page', '', true );
            }
        }

        public static function uninstall_single_site() {
            global $wpdb;

            $options = get_option( 'mwtsa_settings', array() );

            if ( ! is_array( $options ) || empty( $options['mwtsa_uninstall'] ) ) {
                return false;
            }

            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mwt_search_history" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mwt_search_terms" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

            delete_option( 'mwtsa_settings' );
            delete_option( 'mwtsa_db_version' );
            delete_option( 'mwtsa_db_upgrade_lock' );

            // Cached country lookups.
            $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_mwtsa_geo_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_mwtsa_geo_' ) . '%'
            ) );

            return true;
        }
    }
}

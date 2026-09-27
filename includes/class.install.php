<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Install' ) ) {

	class MWTSA_Install {

		const UPGRADE_LOCK = 'mwtsa_db_upgrade_lock';

        //TODO: check if multisite works correctly.
		public static function activation( $network_wide ) {
			global $wpdb, $wp_version;

			if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
				deactivate_plugins( MWTSAI()->plugin_basename );
				/* translators: %s: PHP version */
				wp_die( sprintf( esc_html__( 'Search Analytics for WP cannot be activated. The plugin requires PHP %s or higher', 'search-analytics' ), '5.6' ) );
			}

			if ( version_compare( $wp_version, '4.7', '<' ) ) {
				deactivate_plugins( MWTSAI()->plugin_basename );
				/* translators: %s: WordPress version */
				wp_die( sprintf( esc_html__( 'Search Analytics for WP cannot be activated. The plugin requires WordPress %s or higher', 'search-analytics' ), '4.7' ) );
			}

			if ( $network_wide ) {

				if ( function_exists( 'get_sites' ) && function_exists( 'get_current_network_id' ) ) {
					$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0, 'network_id' => get_current_network_id() ) );
				} else {
					//fallback for WP < 4.6
					$site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;" ); // phpcs:ignore  WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
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

		/**
		 * Plugin updates don't run the activation hook, so pending schema changes are applied on the next admin
		 * page load. AJAX requests are skipped because themes also send them for front-end visitors.
		 */
		public static function maybe_upgrade() {
			if ( wp_doing_ajax() ) {
				return;
			}

			self::activate_single_site();
		}

		/**
		 * @param bool $allow_upgrade Whether existing tables may be altered. Front-end requests pass false: upgrading
		 *                            a large table can take a while, so it only happens from the admin or on
		 *                            activation. Missing tables are always created.
		 */
		public static function activate_single_site( $allow_upgrade = true ) {
			static $activated = array();
			$key = get_current_blog_id() . ( $allow_upgrade ? ':upgrade' : ':create' );
			if ( isset( $activated[ $key ] ) ) {
				return;
			}
			$activated[ $key ] = true;
			self::setup_db_tables( $allow_upgrade );
			self::update_options();
		}

		public static function setup_db_tables( $allow_upgrade = true ) {
			global $wpdb;

			$instance = MWTSAI();

			$current_db_version = get_option( 'mwtsa_db_version' );

			// Also skip a newer schema, e.g. after rolling back from 2.0: this version's dbDelta would shrink its columns.
			if ( ! empty( $current_db_version ) && version_compare( $current_db_version, $instance->db_version, '>=' ) ) {
				return;
			}

			if ( ! empty( $current_db_version ) && ! $allow_upgrade ) {
				return;
			}

			if ( ! self::lock_upgrade() ) {
				return;
			}

			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- adding indexes to a large table can take a while.
			}

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();

			// Search terms Table
			$table_name = $wpdb->prefix . $instance->terms_table_name_no_prefix;

			$sql = "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term VARCHAR(100) NOT NULL,
			  total_count int(11) UNSIGNED NOT NULL,
			  PRIMARY KEY  (id),
			  KEY idx_term_lookup (term)
			) $charset_collate;";

			// Search History table
			$table_name = $wpdb->prefix . $instance->history_table_name_no_prefix;

			$sql .= "CREATE TABLE $table_name (
			  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  term_id int(11) UNSIGNED NOT NULL,
			  `datetime` DATETIME NOT NULL,
			  count_posts MEDIUMINT(9) UNSIGNED NOT NULL,
			  country VARCHAR(50) DEFAULT '',
			  user_id int(11) UNSIGNED NOT NULL DEFAULT '0',
			  PRIMARY KEY  (id),
			  KEY idx_term_id (term_id),
			  KEY idx_datetime (datetime),
			  KEY idx_user_id (user_id)
			) $charset_collate;";

			// The history table's indexes match the 2.0 schema, so its upgrade finds them already in place.
			dbDelta( $sql );

			update_option( 'mwtsa_db_version', $instance->db_version );

			self::unlock_upgrade();
		}

		/**
		 * INSERT IGNORE only succeeds for one request, so concurrent requests never alter the tables twice. A lock
		 * left behind by a request that died mid-upgrade is taken over after 10 minutes.
		 */
		private static function lock_upgrade() {
			global $wpdb;

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- the lock must bypass the options cache.
			$locked = $wpdb->query( $wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
				self::UPGRADE_LOCK,
				time()
			) );

			if ( 1 !== $locked ) {
				$locked = $wpdb->query( $wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value < %d",
					time(),
					self::UPGRADE_LOCK,
					time() - 10 * MINUTE_IN_SECONDS
				) );
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			return 1 === $locked;
		}

		private static function unlock_upgrade() {
			global $wpdb;

			$wpdb->delete( $wpdb->options, array( 'option_name' => self::UPGRADE_LOCK ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Creates the tables for a site added after the plugin was network activated.
		 *
		 * @param WP_Site $new_site
		 */
		public static function initialize_new_site( $new_site ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			if ( ! is_plugin_active_for_network( MWTSAI()->plugin_basename ) ) {
				return;
			}

			switch_to_blog( $new_site->blog_id );
			self::activate_single_site();
			restore_current_blog();
		}

		/**
		 * Drops the plugin's tables together with the site's own when a site is deleted.
		 */
		public static function add_site_tables_to_drop( $tables, $blog_id ) {
			global $wpdb;

			$prefix   = $wpdb->get_blog_prefix( $blog_id );
			$tables[] = $prefix . MWTSAI()->terms_table_name_no_prefix;
			$tables[] = $prefix . MWTSAI()->history_table_name_no_prefix;

			return $tables;
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
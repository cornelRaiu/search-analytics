<?php
/*
Plugin Name: Search Analytics for WP - Site Search Tracking
Plugin URI: https://www.cornelraiu.com/wordpress-plugins/mwt-search-analytics/
Description: See what visitors search for on your site and which searches come up empty. Popular search terms and statistics, stored in your own database.
Version: 1.6.0
Author: Cornel Raiu
Author URI: https://www.cornelraiu.com/
Text Domain: search-analytics
Domain Path: /languages
Requires at least: 4.7
Requires PHP: 5.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

defined("ABSPATH") || exit;

if ( ! defined( 'MWTSA_WORDPRESS_SUPPORT_URL' ) ) {
	define( 'MWTSA_WORDPRESS_SUPPORT_URL', 'https://wordpress.org/support/plugin/search-analytics' );
}

if ( ! defined( 'MWTSA_WORDPRESS_URL' ) ) {
    define('MWTSA_WORDPRESS_URL', 'https://wordpress.org/plugin/search-analytics');
}

if ( ! class_exists( 'MWTSA' ) ) {

	final class MWTSA {

		public $version = '1.6.0';
		public $db_version = '1.2.1';

		public $plugin_dir;
		public $plugin_url;
		public $plugin_basename;
		public $plugin_admin_dir;
		public $plugin_admin_url;
		public $includes_dir;
		public $shortcodes_dir;
		public $terms_table_name_no_prefix;
		public $history_table_name_no_prefix;
		public $terms_table_name;
		public $history_table_name;
		public $cookie_name;
		public $main_option_name;

		protected static $_instance = null;

		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'search-analytics' ), esc_attr( $this->version) );
		}

		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'search-analytics' ), esc_attr( $this->version ) );
		}

		public function __construct() {
			$this->set_constants();
			$this->includes();
			$this->add_actions_and_filters();
		}

		public function set_constants() {
			global $wpdb;
			$this->plugin_dir       = plugin_dir_path( __FILE__ );
			$this->plugin_url       = plugin_dir_url( __FILE__ );
			$this->plugin_basename  = plugin_basename( __FILE__ );
			$this->plugin_admin_dir = $this->plugin_dir . 'admin/';
			$this->plugin_admin_url = $this->plugin_url . 'admin/';
			$this->includes_dir     = $this->plugin_dir . 'includes/';
			$this->shortcodes_dir     = $this->plugin_dir . 'shortcodes/';

			// need this separated for the multisite install/uninstall functions
			$this->terms_table_name_no_prefix   = 'mwt_search_terms';
			$this->history_table_name_no_prefix = 'mwt_search_history';

			$this->terms_table_name   = $wpdb->prefix . $this->terms_table_name_no_prefix;
			$this->history_table_name = $wpdb->prefix . $this->history_table_name_no_prefix;

			$this->cookie_name      = 'wp_mwtsa';
			$this->main_option_name = 'mwtsa_settings';
		}

		public function includes() {
			require_once( $this->includes_dir . 'helpers.php' );
			require_once( $this->includes_dir . 'class.options.php' );
			require_once( $this->includes_dir . 'class.cookies.php' );
			require_once( $this->includes_dir . 'class.history-data.php' );

			if ( is_admin() ) {
				require_once( $this->plugin_admin_dir . 'admin.php' );
			}

			require_once( $this->includes_dir . 'class.install.php' );
			require_once( $this->includes_dir . 'class.uninstall.php' );
			require_once( $this->includes_dir . 'class.process-query.php' );

			require_once( $this->shortcodes_dir . 'class.mwtsa_display_search_stats.php' );
			require_once( $this->shortcodes_dir . 'class.mwtsa_display_latest_searches.php' );
		}

		public function add_actions_and_filters() {
			add_action( 'init', array( 'MWTSA_Display_Search_Stats_Shortcode', 'init' ) );
			add_action( 'init', array( 'MWTSA_Display_Latest_Searches_Shortcode', 'init' ) );

			add_action( 'rest_api_init', array( 'MWTSA_Process_Query', 'process_rest_api_search_term_action' ), 20 );

			add_action( 'wp', array( 'MWTSA_Process_Query', 'process_search_term_action' ), 20 );

			add_action( 'wpforo_search_result_after', array(
				'MWTSA_Process_Query',
				'process_wpforo_search_term_action'
			), 20, 4 );

			add_action( 'admin_init', array( 'MWTSA_Install', 'maybe_upgrade' ) );

			// Core creates a new site's tables on wp_initialize_site at priority 10, so run after it.
			add_action( 'wp_initialize_site', array( 'MWTSA_Install', 'initialize_new_site' ), 11 );
			add_filter( 'wpmu_drop_tables', array( 'MWTSA_Install', 'add_site_tables_to_drop' ), 10, 2 );
			add_action( 'wp_login', array( 'MWTSA_Cookies', 'set_is_excluded_cookie_if_needed' ), 10, 2 );
		}
	}

}

if ( ! function_exists( 'MWTSAI' ) ) {
    function MWTSAI() { //MWTSA Main Instance
        return MWTSA::instance();
    }
}

if ( class_exists( 'MWTSA' ) ) {
    $GLOBALS['mwtsa'] = MWTSAI(); // Added for backwards compatibility

	// Data is only removed when the plugin is deleted (see uninstall.php), never on deactivation.
	register_activation_hook( __FILE__, array( 'MWTSA_Install', 'activation' ) );
}



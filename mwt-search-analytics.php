<?php
/*
Plugin Name: Search Analytics
Plugin URI: https://www.cornelraiu.com/wordpress-plugins/mwt-search-analytics/
Description: Search Analytics will keep history of the search terms used by your users and group them in a set of statistics including the number of posts resulted from that search.
Version: 1.3.1
Author: Cornel Raiu
Author URI: https://www.cornelraiu.com/
Text Domain: mwt-search-analytics
Domain Path: /languages
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'MWTSA' ) ){

	final class MWTSA {

		public $version = '1.3.1';
		public $db_version = '1.1.1';
		public $text_domain = 'mwt-search-analytics';

		public $plugin_dir;
		public $plugin_url;
		public $plugin_admin_dir;
		public $plugin_admin_url;
		public $includes_dir;
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
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->text_domain ), $this->version );
		}

		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', $this->text_domain ), $this->version );
		}

		public function __construct() {
			$this->set_constants();
			$this->includes();
			$this->add_actions_and_filters();
		}

		public function set_constants() {
			global $wpdb;
			$this->plugin_dir = plugin_dir_path( __FILE__ );
			$this->plugin_url = plugin_dir_url(__FILE__);
			$this->plugin_admin_dir = $this->plugin_dir .'admin/';
			$this->plugin_admin_url = $this->plugin_url .'admin/';
			$this->includes_dir = $this->plugin_dir . 'includes/';

			// need this separated for the multisite install/uninstall functions
			$this->terms_table_name_no_prefix = 'mwt_search_terms';
			$this->history_table_name_no_prefix = 'mwt_search_history';

			$this->terms_table_name = $wpdb->prefix . $this->terms_table_name_no_prefix;
			$this->history_table_name = $wpdb->prefix . $this->history_table_name_no_prefix;

			$this->cookie_name = 'wp_mwtsa';
			$this->main_option_name = 'mwtsa_settings';
		}

		public function includes() {
			require_once( $this->includes_dir . 'helpers.php' );
			require_once( $this->includes_dir . 'class.options.php' );
			require_once( $this->includes_dir . 'class.cookies.php' );

			if( is_admin() ) {
				require_once( $this->plugin_admin_dir . 'admin.php' );
			}

			require_once( $this->includes_dir . 'class.install.php' );
			require_once( $this->includes_dir . 'class.uninstall.php' );
			include_once( $this->includes_dir . 'class.process-query.php' );
		}

		public function add_actions_and_filters() {
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'wp_insert_site', array( 'MWTSA_Install', 'activation' ) );

			add_action( 'wp', array( 'MWTSA_Process_Query', 'process_search_term_action' ), 20 );

			add_action( 'wp_login', array( 'MWTSA_Cookies', 'set_is_excluded_cookie_if_needed'), 10, 2 );
			add_action( 'init', array( 'MWTSA_Cookies', 'clear_expired_search_history' ) );
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( $this->text_domain, FALSE, $this->plugin_dir . 'languages/' );
		}
	}

}

if( class_exists( 'MWTSA' ) ) {
	$mwtsa = new MWTSA();
	register_activation_hook( __FILE__, array( 'MWTSA_Install', 'activation' ) );
	register_deactivation_hook( __FILE__, array( 'MWTSA_Uninstall', 'deactivation' ) );
}

if ( ! function_exists( 'MWTSAI' ) ) {
	function MWTSAI() { //MWTSA Main Instance
		return MWTSA::instance();
	}
}

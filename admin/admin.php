<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Admin' ) ) {

	class MWTSA_Admin {

		public function __construct() {
			$this->includes();
			$this->add_actions_and_filters();
            $this->init_menus_and_screens();
		}

		private function includes() {
			include_once( 'includes/class.settings.php' );
			include_once( 'includes/class.dashboard.php' );
			include_once( 'includes/class.charts.php' );
			include_once( 'includes/class.stats.php' );
			include_once( 'includes/class.export-csv.php' );
			include_once( 'includes/class.menu.php' );
			include_once( 'includes/class.review-notice.php' );
			include_once( 'includes/class.pro-waitlist.php' );
			include_once( 'includes/class.search-steer.php' );
			include_once( 'includes/class.extensions.php' );
			include_once( 'includes/class.deactivation-feedback.php' );
		}

		public function add_actions_and_filters() {
			add_action( 'wp_dashboard_setup', array( 'MWTSA_Dashboard', 'init_action' ) );

			add_filter( 'plugin_row_meta', array( 'MWTSA_Dashboard', 'add_plugin_meta_links' ), 10, 2 );

			new MWTSA_Review_Notice();

			add_action( 'admin_init', array( 'MWTSA_Search_Steer', 'save_dismissal' ) );

			new MWTSA_Deactivation_Feedback();
		}

        public function init_menus_and_screens()
        {
            $stats    = new MWTSA_Admin_Stats();
            $settings = new MWTSA_Admin_Settings();

            new MWTSA_Admin_Menu( $stats, $settings );
        }
	}

}

new MWTSA_Admin();

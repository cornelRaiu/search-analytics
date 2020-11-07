<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Admin' ) ) {

    class MWTSA_Admin {

        public function __construct() {
            $this->includes();
            $this->add_actions_and_filters();
        }

        private function includes() {
            include_once( 'includes/class.settings.php' );
            include_once( 'includes/class.dashboard.php' );
            include_once( 'includes/class.charts.php' );
            include_once( 'includes/class.stats.php' );
            include_once( 'includes/class.export-csv.php' );
        }

        public function add_actions_and_filters() {
            add_action( 'wp_dashboard_setup', array( 'MWTSA_Dashboard', 'init_action' ) );
        }
    }

}

return new MWTSA_Admin();
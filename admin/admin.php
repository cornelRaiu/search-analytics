<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('MWTSA_Admin') ) {

	class MWTSA_Admin {

		public function __construct() {
			$this->includes();
			add_action( 'current_screen', array( $this, 'maybe_include' ) );
		}

		private function includes() {
			include_once ('includes/class.settings.php');
			include_once ('includes/class.charts.php');
			include_once ('includes/class.stats.php');
			include_once ('includes/class.export-csv.php');
		}

		public function maybe_include() {
			if ( ! $screen = get_current_screen() ) {
				return;
			}

			switch ( $screen->id ) {
				case 'dashboard':
					include_once ( 'includes/class.dashboard.php' );
					break;
			}
		}
	}

}

return new MWTSA_Admin();
<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Admin_Menu' ) ) {

	class MWTSA_Admin_Menu {

		private $stats;
		private $settings;
		private $view;

		public function __construct( MWTSA_Admin_Stats $stats, MWTSA_Admin_Settings $settings ) {
			$this->stats    = $stats;
			$this->settings = $settings;

			add_action( 'admin_menu', array( $this, 'register' ) );
			add_action( 'init', array( $this, 'redirect_legacy_urls' ) );
		}

		public function register() {
			$stats_roles    = $this->get_stats_roles();
			$settings_roles = $this->get_settings_roles();

			if ( empty( $stats_roles ) && empty( $settings_roles ) ) {
				return;
			}

			$capability = ! empty( $stats_roles ) ? $stats_roles[0] : $settings_roles[0];

			$this->view = add_menu_page(
				__( 'Search Analytics', 'search-analytics' ),
				__( 'Search Analytics', 'search-analytics' ),
				$capability,
				'mwtsa-search-analytics',
				array( $this->stats, 'render_stats_page' ),
				'dashicons-analytics',
				81
			);

			$this->stats->set_view( $this->view );

			if ( ! empty( $stats_roles ) ) {
				add_submenu_page(
					'mwtsa-search-analytics',
					__( 'Statistics', 'search-analytics' ),
					__( 'Statistics', 'search-analytics' ),
					$stats_roles[0],
					'mwtsa-search-analytics',
					array( $this->stats, 'render_stats_page' )
				);

				add_action( "load-{$this->view}", array( $this->stats, 'add_screen_options' ) );
				add_action( "load-{$this->view}", array( $this->stats, 'init_stats_table' ) );
			}

			if ( ! empty( $settings_roles ) ) {
				add_submenu_page(
					'mwtsa-search-analytics',
					__( 'MWT: Search Analytics', 'search-analytics' ),
					__( 'Settings', 'search-analytics' ),
					$settings_roles[0],
					'mwtsa-search-analytics-settings',
					array( $this->settings, 'options_page' )
				);
			}

			// Backward-compat: keep the old Dashboard entry visible and redirect to new location
			if ( ! empty( $stats_roles ) ) {
				add_submenu_page(
					'index.php',
					__( 'Search Analytics', 'search-analytics' ),
					__( 'Search Analytics', 'search-analytics' ),
					$stats_roles[0],
					'mwtsa-search-analytics-old',
					array( $this, 'redirect_to_stats' )
				);
			}

			// Backward-compat: keep the old Settings entry visible and redirect to new location
			if ( ! empty( $settings_roles ) ) {
				add_options_page(
					__( 'MWT: Search Analytics', 'search-analytics' ),
					__( 'MWT: Search Analytics', 'search-analytics' ),
					$settings_roles[0],
					'mwtsa-search-analytics-settings-old',
					array( $this, 'redirect_to_settings' )
				);
			}
		}

		public function redirect_to_stats() {
			wp_safe_redirect( admin_url( 'admin.php?page=mwtsa-search-analytics' ) );
			exit;
		}

		public function redirect_to_settings() {
			wp_safe_redirect( admin_url( 'admin.php?page=mwtsa-search-analytics-settings' ) );
			exit;
		}

		public function redirect_legacy_urls() {
			if ( ! is_admin() || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$page   = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$script = isset( $_SERVER['PHP_SELF'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) : '';

			if ( 'search-analytics/admin/includes/class.stats.php' === $page ) {
				wp_safe_redirect( admin_url( 'admin.php?page=mwtsa-search-analytics' ) );
				exit;
			}

			if ( 'mwtsa-search-analytics' === $page && 'index.php' === $script ) {
				wp_safe_redirect( admin_url( 'admin.php?page=mwtsa-search-analytics' ) );
				exit;
			}

			if ( 'search-analytics' === $page && 'options-general.php' === $script ) {
				wp_safe_redirect( admin_url( 'admin.php?page=mwtsa-search-analytics-settings' ) );
				exit;
			}
		}

		private function get_stats_roles() {
			$options    = MWTSA_Options::get_options();
			$user_roles = mwtsa_get_current_user_roles();
			return array_values( array_intersect( $user_roles, $options['mwtsa_display_stats_for_role'] ) );
		}

		private function get_settings_roles() {
			$options    = MWTSA_Options::get_options();
			$user_roles = mwtsa_get_current_user_roles();
			return array_values( array_intersect( $user_roles, $options['mwtsa_display_settings_for_role'] ) );
		}
	}

}

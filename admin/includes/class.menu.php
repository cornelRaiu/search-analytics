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
			add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
		}

		// "Docs" and "Settings" before "Deactivate" on the Plugins screen.
		public function add_action_links( $links, $file ) {
			if ( MWTSAI()->plugin_basename !== $file ) {
				return $links;
			}

			$new_links = array(
				'docs' => '<a href="' . esc_url( MWTSA_DOCS_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Docs', 'search-analytics' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'search-analytics' ) . '</span></a>',
			);

			if ( ! empty( $this->get_settings_roles() ) ) {
				$new_links['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=mwtsa-search-analytics-settings' ) ) . '">' . esc_html__( 'Settings', 'search-analytics' ) . '</a>';
			}

			return array_merge( $new_links, $links );
		}

		// The plugin's icon as add_menu_page() takes an SVG. WordPress repaints its fills to match the admin color scheme.
		private function get_menu_icon() {
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="44 44 168 168" width="20" height="20">'
				. '<rect x="56" y="52" width="144" height="28" rx="14" fill="#a7aaad"/>'
				. '<rect x="56" y="98" width="104" height="28" rx="14" fill="#a7aaad"/>'
				. '<rect x="56" y="144" width="52" height="28" rx="14" fill="#a7aaad"/>'
				. '<path fill="#a7aaad" fill-rule="evenodd" d="M152 130a28 28 0 1 0 0 56a28 28 0 1 0 0-56zm0 12a16 16 0 1 1 0 32a16 16 0 1 1 0-32z"/>'
				. '<rect x="155.62" y="180" width="50.77" height="14" rx="7" transform="rotate(45 181 187)" fill="#a7aaad"/>'
				. '</svg>';

			return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- the format add_menu_page() expects.
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
				$this->get_menu_icon(),
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

			add_submenu_page(
				'mwtsa-search-analytics',
				__( 'Search Analytics Extensions', 'search-analytics' ),
				__( 'Extensions', 'search-analytics' ),
				'activate_plugins',
				MWTSA_Admin_Extensions::PAGE,
				array( 'MWTSA_Admin_Extensions', 'render' )
			);

			// Backward-compat: keep the old Dashboard entry visible and redirect to new location
			if ( ! empty( $stats_roles ) ) {
				$old_stats_hook = add_submenu_page(
					'index.php',
					__( 'Search Analytics', 'search-analytics' ),
					__( 'Search Analytics', 'search-analytics' ),
					$stats_roles[0],
					'mwtsa-search-analytics-old',
					array( $this, 'redirect_to_stats' )
				);

				// Redirect on load: the page callback runs after the admin header has been sent.
				if ( $old_stats_hook ) {
					add_action( "load-{$old_stats_hook}", array( $this, 'redirect_to_stats' ) );
				}
			}

			// Backward-compat: keep the old Settings entry visible and redirect to new location
			if ( ! empty( $settings_roles ) ) {
				$old_settings_hook = add_options_page(
					__( 'MWT: Search Analytics', 'search-analytics' ),
					__( 'MWT: Search Analytics', 'search-analytics' ),
					$settings_roles[0],
					'mwtsa-search-analytics-settings-old',
					array( $this, 'redirect_to_settings' )
				);

				if ( $old_settings_hook ) {
					add_action( "load-{$old_settings_hook}", array( $this, 'redirect_to_settings' ) );
				}
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

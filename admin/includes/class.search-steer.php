<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Search_Steer' ) ) {

	/**
	 * MWT Search Steer, the author's plugin that adds search keywords to posts: its install state, and the line on the
	 * statistics page shown when the listed searches include some that found nothing.
	 */
	class MWTSA_Search_Steer {

		const SLUG      = 'mwt-search-steer';
		const USER_META = 'mwtsa_search_steer_line';
		const QUERY_ARG = 'mwtsa_search_steer';

		public static function save_dismissal() {
			if ( ! isset( $_GET[ self::QUERY_ARG ], $_GET['page'] ) || 'mwtsa-search-analytics' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			check_admin_referer( self::QUERY_ARG );

			update_user_meta( get_current_user_id(), self::USER_META, 'dismissed' );

			wp_safe_redirect( remove_query_arg( array( self::QUERY_ARG, '_wpnonce' ) ) );
			exit;
		}

		private static function installed_file() {
			foreach ( array_keys( get_plugins() ) as $file ) {
				if ( self::SLUG . '.php' === basename( $file ) ) {
					return $file;
				}
			}

			return '';
		}

		/**
		 * 'unsupported' (below WordPress 6.6 or PHP 7.4), 'missing', 'inactive' or 'active'.
		 */
		public static function get_state() {
			if ( version_compare( PHP_VERSION, '7.4', '<' ) || version_compare( get_bloginfo( 'version' ), '6.6', '<' ) ) {
				return 'unsupported';
			}

			$file = self::installed_file();

			if ( '' === $file ) {
				return 'missing';
			}

			return is_plugin_active( $file ) ? 'active' : 'inactive';
		}

		// On multisite, plugins are installed from the network admin only.
		private static function plugin_admin_url( $path ) {
			return is_multisite() ? network_admin_url( $path ) : admin_url( $path );
		}

		public static function can_install() {
			return current_user_can( 'install_plugins' );
		}

		public static function can_activate() {
			$file = self::installed_file();

			return '' !== $file && current_user_can( 'activate_plugin', $file );
		}

		public static function install_url() {
			return wp_nonce_url( self::plugin_admin_url( 'update.php?action=install-plugin&plugin=' . self::SLUG ), 'install-plugin_' . self::SLUG );
		}

		public static function activate_url() {
			$file = self::installed_file();

			return wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ), 'activate-plugin_' . $file );
		}

		/**
		 * The WordPress.org details of the plugin, in the same window as on the Add Plugins screen.
		 */
		public static function details_link( $text ) {
			add_thickbox();
			wp_enqueue_script( 'plugin-install' );

			$url = self::plugin_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . self::SLUG . '&TB_iframe=true&width=600&height=550' );

			return sprintf( '<a href="%s" class="thickbox open-plugin-details-modal" data-title="MWT Search Steer">%s</a>', esc_url( $url ), esc_html( $text ) );
		}

		/**
		 * @param int  $no_results_terms How many of the listed terms found nothing.
		 * @param bool $only_no_results  Whether the "Only Without Results" view is shown.
		 */
		public static function render( $no_results_terms, $only_no_results ) {
			if ( $no_results_terms < 1 ) {
				return;
			}

			$state = self::get_state();
			$links = array();

			if ( 'active' === $state ) {
				if ( ! $only_no_results ) {
					return;
				}

				$text = __( 'To make these searches find something, add the words as search keywords to the posts that should come up, in the Search Keywords panel of the post editor or in quick edit.', 'search-analytics' );
			} else {
				if ( 'missing' === $state && self::can_install() ) {
					$links[] = self::details_link( __( 'Install MWT Search Steer', 'search-analytics' ) );
				} elseif ( 'inactive' === $state && self::can_activate() ) {
					$links[] = sprintf( '<a href="%s">%s</a>', esc_url( self::activate_url() ), esc_html__( 'Activate MWT Search Steer', 'search-analytics' ) );
				}

				if ( empty( $links ) || 'dismissed' === get_user_meta( get_current_user_id(), self::USER_META, true ) ) {
					return;
				}

				if ( $only_no_results ) {
					$text = __( 'To make these searches find something, you can give a post search keywords with MWT Search Steer, a free plugin by the same author. WordPress search then shows the post for those words.', 'search-analytics' );
				} else {
					/* translators: %s: number of search terms */
					$text = sprintf( _n( '%s search term in this list found nothing.', '%s search terms in this list found nothing.', $no_results_terms, 'search-analytics' ), number_format_i18n( $no_results_terms ) )
					        . ' ' . __( 'To make them find something, you can give a post search keywords with MWT Search Steer, a free plugin by the same author. WordPress search then shows the post for those words.', 'search-analytics' );

					$links[] = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'results_view', 2, remove_query_arg( 'paged' ) ) ), esc_html__( 'Show them', 'search-analytics' ) );
				}

				$links[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( add_query_arg( self::QUERY_ARG, 'dismiss' ), self::QUERY_ARG ) ), esc_html__( 'Dismiss', 'search-analytics' ) );
			}
			?>
            <p class="mwtsa-search-steer">
                <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
                <span><?php echo esc_html( $text ); ?> <?php echo implode( ' | ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></span>
            </p>
			<?php
		}
	}
}

<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Admin_Stats' ) ) {

	class MWTSA_Admin_Stats {

		public $can_see_stats;
		public $can_update_options;
		public $plugin_options;

		private $view;
		private $charts;
        /**
         * @var bool
         */
        private $is_delete;
        /**
         * @var \MWTSA_Stats_Table|\MWTSA_Term_Stats_Table
         */
        private $stats_table;

        public function __construct() {

			$this->view = '';
			$this->set_constants();
			$this->plugin_options = MWTSA_Options::get_options();

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );

			add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );

			if ( empty( $_REQUEST['search-term'] ) && empty ( MWTSA_Options::get_option( 'mwtsa_hide_charts' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->charts = new MWTSA_Admin_Charts();
			}

			include_once( 'class.stats-table.php' );
			include_once( 'class.term-stats.php' );
		}

		public function init_stats_table() {
			$this->is_delete   = ! empty( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->stats_table = ! empty( $_REQUEST['search-term'] ) && ! $this->is_delete ? new MWTSA_Term_Stats_Table( array( 'search-term' => (int) $_REQUEST['search-term'] ) ) : new MWTSA_Stats_Table(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->stats_table->mwtsa_init();

			// Deleting redirects afterwards, so it has to run here, before the admin page starts printing.
			$this->stats_table->process_bulk_action();
		}

		public function add_screen_options() {
			$option = 'per_page';

			$args = array(
				'label'   => __( 'Entries Per Page', 'search-analytics' ),
				'default' => 20,
				'option'  => 'mwtsa_entries_per_page'
			);


			add_screen_option( $option, $args );
		}

		public function set_screen_options( $status, $option, $value ) {
			return ( 'mwtsa_entries_per_page' == $option ) ? $value : $status;

		}

		private function set_constants() {
			$this->can_see_stats = array(
				'administrator'
			);

			$this->can_update_options = array(
				'administrator'
			);
		}

		public function set_view( $view ) {
			$this->view = $view;
		}

		/**
		 * Windows can't draw flag emoji. The polyfill (bundled, no CDN) canvas-tests the browser and only registers
		 * the Twemoji flag font when that test fails, leaving native flags elsewhere untouched.
		 */
		public function print_country_flag_polyfill() {
			$vendor_url = MWTSAI()->plugin_admin_url . 'assets/vendor/country-flag-emoji-polyfill/';
			?>
            <script type="module">
                import { polyfillCountryFlagEmojis } from <?php echo wp_json_encode( esc_url_raw( $vendor_url . 'country-flag-emoji-polyfill.js?ver=0.1.10' ) ); ?>;
                polyfillCountryFlagEmojis( 'Twemoji Country Flags', <?php echo wp_json_encode( esc_url_raw( $vendor_url . 'TwemojiCountryFlags.woff2' ) ); ?> );
            </script>
			<?php
		}

		/**
		 * The screens that use the plugin's stylesheet: statistics, settings and the dashboard widget.
		 */
		private function is_plugin_screen( $hook ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return 'index.php' === $hook || ( '' !== $this->view && $hook === $this->view ) || 'mwtsa-search-analytics-settings' === $page;
		}

		public function load_admin_assets( $hook ) {
			if ( $this->is_plugin_screen( $hook ) ) {
				wp_enqueue_style( 'mwtsa-stats-style', MWTSAI()->plugin_admin_url . 'assets/css/stats-style.css', array(), MWTSAI()->version );
			}

			if ( $hook != $this->view ) {
				return;
			}

			if ( ! empty( $this->charts ) ) {
				$this->charts->load_admin_assets();
			}

			if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_country' ) ) ) {
				add_action( 'admin_print_footer_scripts', array( $this, 'print_country_flag_polyfill' ) );
			}

			if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) ) {
				wp_register_style( 'select2css', MWTSAI()->plugin_admin_url . 'assets/css/select2.min.css', false, '4.0.13' );

				wp_enqueue_script( 'select2', MWTSAI()->plugin_admin_url . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.13', true );
			}

			wp_register_style( 'mwtsa-datepicker-ui', MWTSAI()->plugin_admin_url . 'assets/css/jquery-ui-smoothness.css', array(), '1.14.2' );

			wp_enqueue_script( 'mwtsa-admin-script', MWTSAI()->plugin_admin_url . 'assets/js/admin.js', array(), MWTSAI()->version, true );

			wp_localize_script( 'mwtsa-admin-script', 'mwtsa_admin_obj', array(
					'gmt_offset'  => mwtsa_wp_timezone()->getOffset( new DateTime() ),
					'date_format' => mwtsa_wp_date_format_to_js_datepicker_format( get_option( 'date_format' ) )
				)
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		public function render_stats_page() {
			?>
            <div class="wrap mwtsa-wrapper">
                <div class="mwtsa-2-col">
                    <div class="mwtsa-col-1">
                        <div class="col-content">
							<?php $this->stats_table->load_notices(); ?>
							<?php echo $this->stats_table->this_title();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped ?>
							<?php if ( ! $this->is_delete ) : ?>
                                <div class="mwtsa-filters-groups-wrapper">
                                    <div>
                                        <span class="views-label"><?php esc_html_e( 'Time filters:', 'search-analytics' ) ?></span>
										<?php $this->stats_table->display_time_views(); ?>
                                    </div>
                                    <div>
                                        <span class="views-label"><?php esc_html_e( 'Results filters:', 'search-analytics' ) ?></span>
										<?php $this->stats_table->display_results_views(); ?>
                                    </div>

									<?php if ( ! empty( $_REQUEST['search-term'] ) ) :?>
                                        <div>
                                            <span class="views-label"><?php esc_html_e( 'Group By:', 'search-analytics' ) ?></span>
											<?php $this->stats_table->display_group_views(); ?>
                                        </div>
									<?php else : ?>
                                        <div>
                                            <span class="views-label"><?php esc_html_e( 'Group By:', 'search-analytics' ) ?></span>
											<?php $this->stats_table->display_results_grouping(); ?>
                                        </div>
									<?php endif; ?>
                                </div>
							<?php endif; ?>
							<?php $this->stats_table->prepare_items(); ?>

                            <form method="get">
                                <input type="hidden" name="page" value="<?php echo esc_attr( $this->stats_table->get_this_screen() ) ?>">
								<?php if ( isset ( $_REQUEST['date_from'] ) ): ?>
                                    <input type="hidden" name="date_from" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['date_from'] ) ) ) ?>">
								<?php endif; ?>
								<?php if ( isset ( $_REQUEST['date_to'] ) ): ?>
                                    <input type="hidden" name="date_to" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['date_to'] ) ) ) ?>">
								<?php endif; ?>
								<?php if ( isset ( $_REQUEST['period_view'] ) ): ?>
                                    <input type="hidden" name="period_view" value="<?php echo (int) $_REQUEST['period_view'] ?>">
								<?php endif; ?>
								<?php if ( isset ( $_REQUEST['results_view'] ) ): ?>
                                    <input type="hidden" name="results_view" value="<?php echo (int) $_REQUEST['results_view'] ?>">
								<?php endif; ?>
								<?php if ( isset ( $_REQUEST['grouped_view'] ) ): ?>
                                    <input type="hidden" name="grouped_view" value="<?php echo (int) $_REQUEST['grouped_view'] ?>">
								<?php endif; ?>
								<?php if ( ! empty ( $_REQUEST['search-term'] ) ): ?>
                                    <input type="hidden" name="search-term" value="<?php echo (int) $_REQUEST['search-term'] ?>">
								<?php endif; ?>
								<?php
                                $this->stats_table->display_search_box();
                                $this->stats_table->display();
								?>
                            </form>

                        </div>
						<?php if ( ! empty( $this->charts ) ) {
							$this->charts->render_stats_chart();
						} ?>
                    </div>
                    <div class="mwtsa-col-2">
                        <div class="col-content">
                            <h2><?php esc_html_e( 'Search Analytics', 'search-analytics' ) ?></h2>

                            <h3><?php esc_html_e( 'Changelog', 'search-analytics' ) ?></h3>

                            <p><?php
	                            /* translators: %s: Plugin version */
                                printf( esc_attr__( 'New in version %s', 'search-analytics' ), esc_attr( MWTSAI()->version ) ); ?>
                            </p>

                            <ul class="changelog-list">
                                <li><strong>Structure:</strong> Search data is now only removed when you delete the plugin (with the setting checked), never on deactivation</li>
                                <li>Feature: The country lookup can use ip2c.org instead of ip-api.com</li>
                                <li>Bugfix: The statistics charts were not loading since 1.5.0</li>
                                <li>Bugfix: The charts and the "By date" view mixed up searches from the same day of different months</li>
                                <li>Bugfix: On multisite, deactivating the plugin on one site could remove every site's search data</li>
                                <li>Bugfix: A trailing comma in the "Exclude search" setting excluded every search</li>
                                <li>Bugfix: More accurate counts: real "Last 24 hours" periods, no counting of result pages and feeds, no duplicate terms</li>
                                <li>Bugfix: The roles allowed on the settings page can now save it, and the roles allowed on the statistics page can export</li>
                                <li>Security: Safer CSV exports, a validated tracking cookie and explicit permission checks</li>
                                <li>Optimization: Country flags are now emoji, with a bundled font for browsers that can't draw them</li>
                                <li>Optimization: Database indexes, cached country lookups and no more cookie on every page</li>
                                <li>Deprecations: The statistics table filters are deprecated ahead of the 2.0 rebuild</li>
							</ul>
                            <p><a href="<?php echo esc_url( MWTSA_WORDPRESS_URL ) ?>/#developers" target="_blank"><?php esc_html_e( 'Click here to check the complete log', 'search-analytics' ) ?></a></p>
                            <h3><?php esc_html_e( 'Useful Links', 'search-analytics' ) ?></h3>
                            <ul>
                                <li>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=mwtsa-search-analytics-settings' ) ); ?>"><?php esc_html_e( 'Settings Page', 'search-analytics' ) ?></a>
                                </li>
                                <li>
                                    <a href="<?php echo esc_url( MWTSA_WORDPRESS_SUPPORT_URL ) ?>" target="_blank"><?php esc_html_e( 'Support Forum', 'search-analytics' ) ?></a>
                                </li>
                                <li style="font-weight: bold">
                                    <a href="<?php echo esc_url( MWTSA_WORDPRESS_SUPPORT_URL ) ?>/reviews/#new-post" target="_blank"><?php esc_html_e( 'Rate and review Search Analytics', 'search-analytics' ) ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}


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

		public function load_admin_assets( $hook ) {
			wp_enqueue_style( 'mwtsa-stats-style', MWTSAI()->plugin_admin_url . 'assets/css/stats-style.css', array(), MWTSAI()->version );

			if ( $hook != $this->view ) {
				return;
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
                                <li><strong>Structure:</strong> Changed the main page from `wp-admin/index.php?page=search-analytics/admin/includes/class.stats.php` to `wp-admin/index.php?page=mwtsa-search-analytics`. A proper redirect was added to the old route</li>
                                <li><strong>Structure:</strong> Changed the settings page from `wp-admin/options-general.php?page=search-analytics` to `wp-admin/admin.php?page=mwtsa-search-analytics-settings`. A proper redirect was added to the old route</li>
                                <li><strong>Structure:</strong> Added a new way to access the plugin main page as a section on the sidebar</li>
                                <li>Bugfix: Fix a possible crash in case ip-api.com did not return a valid response</li>
                                <li>Bugfix: Fix potential IP spoofing when running a search with save country on</li>
                                <li>Feature: Added `mwtsa_run_terms_history_data_query_args` filter for changing the args before history data gets queried</li>
                                <li>Feature: Added a link to the statistics page on the dashboard widget</li>
                                <li>Optimization: Security improvements and general code optimization. Fixed Cross-Site Request Forgery (CSRF) vulnerability</li>
                                <li>Optimization: Performance improvements</li>
                                <li>Optimization: Added the select2 and jQuery UI Smoothness theme as assets in the plugin</li>
                                <li>Optimization: Deprecated the global `$mwtsa`. It will be removed in a later version. Use the `MWTSAI()` to get the instance</li>
                                <li>Deprecations: Deprecated the helper functions with `mwt_` prefix and renamed them to the proper prefix `mwtsa_` to prevent possible collisions</li>
                                <li>Deprecations: Deprecated the `mwtsa_run_terms_history_data_query` filter. It could be used by bad actors to modify the query and pass a not sanitized query through</li>
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


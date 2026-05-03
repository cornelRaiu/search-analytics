<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Admin_Stats' ) ) {

	class MWTSA_Admin_Stats {

		public $can_see_stats;
		public $can_update_options;
		public $plugin_options;

		private $view;
		private $charts;

		public function __construct() {

			$this->view = 'dashboard_page_search-analytics/admin/includes/class.stats';
			$this->set_constants();
			$this->plugin_options = MWTSA_Options::get_options();

			add_action( 'init', array( $this, 'mwtsa_init' ) );

			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );

			add_action( "load-$this->view", array( $this, 'add_screen_options' ) );
			add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );

			if ( empty( $_REQUEST['search-term'] ) && empty ( MWTSA_Options::get_option( 'mwtsa_hide_charts' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->charts = new MWTSA_Admin_Charts();
			}

			include_once( 'class.stats-table.php' );
			include_once( 'class.term-stats.php' );
		}

		public function mwtsa_init() {

			if ( isset( $_REQUEST['mwtsa-export-csv'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$columns = array(
					esc_attr__( 'Term ID', 'search-analytics' ),
					esc_attr__( 'Term', 'search-analytics' ),
					esc_attr__( 'Searches', 'search-analytics' ),
					esc_attr__( 'Average Results', 'search-analytics' ),
					esc_attr__( 'Last Search Date', 'search-analytics' )
				);

				if ( ! empty( $_REQUEST['search-term'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$columns = array(
						esc_attr__( 'Average Results', 'search-analytics' ),
						esc_attr__( 'Date and Time', 'search-analytics' )
					);

					if ( ! empty( $_REQUEST['grouped_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$columns[] = esc_attr__( 'Searches', 'search-analytics' );
					}
				}

				$export_csv = new MWTSA_Export_CSV();
				$export_csv->mwtsa_export_to_csv( ( new MWTSA_History_Data )->get_terms_history_data(), '', $columns );
			}
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

		public function add_admin_menu() {
			$this_user_role      = mwt_get_current_user_roles();
			$accepted_user_roles = array_values( array_intersect( $this_user_role, $this->plugin_options['mwtsa_display_stats_for_role'] ) );

			if ( ! isset( $this->plugin_options['mwtsa_display_stats_for_role'] ) || ! empty( $accepted_user_roles ) ) {

                //TODO: change __FILE__ to unique slug - https://docs.wpvip.com/php_codesniffer/warnings/#h-using-file-for-page-registration
				add_submenu_page( 'index.php', __( 'Search Analytics', 'search-analytics' ), __( 'Search Analytics', 'search-analytics' ), $accepted_user_roles[0], __FILE__, array(
					&$this,
					'render_stats_page'
				) );

			}
		}

		public function load_admin_assets( $hook ) {
			global $mwtsa;

			wp_enqueue_style( 'mwtsa-stats-style', $mwtsa->plugin_admin_url . 'assets/css/stats-style.css', array(), $mwtsa->version );

			if ( $hook != $this->view ) {
				return;
			}

			if ( ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) ) {
				wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '4.0.13' );

				wp_enqueue_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), '4.0.13', true );
			}

			wp_register_style( 'mwtsa-datepicker-ui', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css', array(), '1.11.2' );

			wp_enqueue_script( 'mwtsa-admin-script', $mwtsa->plugin_admin_url . 'assets/js/admin.js', array(), $mwtsa->version, true );

			wp_localize_script( 'mwtsa-admin-script', 'mwtsa_admin_obj', array(
					'gmt_offset'  => wp_timezone()->getOffset( new DateTime() ),
					'date_format' => mwt_wp_date_format_to_js_datepicker_format( get_option( 'date_format' ) )
				)
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		public function render_stats_page() {
			global $mwtsa;

			$is_delete   = ! empty( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'];
			$stats_table = ! empty( $_REQUEST['search-term'] ) && ! $is_delete ? new MWTSA_Term_Stats_Table( array( 'search-term' => (int) $_REQUEST['search-term'] ) ) : new MWTSA_Stats_Table();

			?>
            <div class="wrap mwtsa-wrapper">
                <div class="mwtsa-2-col">
                    <div class="mwtsa-col-1">
                        <div class="col-content">
							<?php $stats_table->load_notices(); ?>
							<?php echo $stats_table->this_title();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped ?>
							<?php if ( ! $is_delete ) : ?>
                                <div class="mwtsa-filters-groups-wrapper">
                                    <div>
                                        <span class="views-label"><?php esc_html_e( 'Time filters:', 'search-analytics' ) ?></span>
										<?php $stats_table->display_time_views(); ?>
                                    </div>
                                    <div>
                                        <span class="views-label"><?php esc_html_e( 'Results filters:', 'search-analytics' ) ?></span>
										<?php $stats_table->display_results_views(); ?>
                                    </div>

									<?php if ( ! empty( $_REQUEST['search-term'] ) ) :?>
                                        <div>
                                            <span class="views-label"><?php esc_html_e( 'Group By:', 'search-analytics' ) ?></span>
											<?php $stats_table->display_group_views(); ?>
                                        </div>
									<?php else : ?>
                                        <div>
                                            <span class="views-label"><?php esc_html_e( 'Group By:', 'search-analytics' ) ?></span>
											<?php $stats_table->display_results_grouping(); ?>
                                        </div>
									<?php endif; ?>
                                </div>
							<?php endif; ?>
							<?php $stats_table->prepare_items(); ?>

                            <form method="get">
                                <input type="hidden" name="page" value="<?php echo esc_attr( $stats_table->get_this_screen() ) ?>">
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
								$stats_table->display_search_box();
								$stats_table->display();
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
                                printf( esc_attr__( 'New in version %s', 'search-analytics' ), esc_attr( $mwtsa->version ) ); ?>
                            </p>
                            <ul class="changelog-list">
								<li>Bugfix: Fix country not being saved.</li>
							</ul>
                            <p><a href="https://www.cornelraiu.com/mwt-search-analytics-changelog/" target="_blank"><?php esc_html_e( 'Click here to check the complete log', 'search-analytics' ) ?></a></p>
                            <h3><?php esc_html_e( 'Useful Links', 'search-analytics' ) ?></h3>
                            <ul>
                                <li>
                                    <a href="options-general.php?page=search-analytics"><?php esc_html_e( 'Settings Page', 'search-analytics' ) ?></a>
                                </li>
                                <li>
                                    <a href="<?php echo esc_url( MWTSA_WORDPRESS_URL ) ?>" target="_blank"><?php esc_html_e( 'Support Forum', 'search-analytics' ) ?></a>
                                </li>
                                <li style="font-weight: bold">
                                    <a href="<?php echo esc_url( MWTSA_WORDPRESS_URL ) ?>/reviews/#new-post" target="_blank"><?php esc_html_e( 'Rate and review Search Analytics', 'search-analytics' ) ?></a>
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

return new MWTSA_Admin_Stats();
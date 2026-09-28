<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Admin_Charts' ) ) {

	class MWTSA_Admin_Charts {

		private $nonce_action = 'mwtsa_chart_nonce';

		public function __construct() {
			add_action( 'wp_ajax_render_chart_data', array( $this, 'render_chart_data' ) );
			add_action( 'wp_ajax_save_default_chart_settings', array( $this, 'save_default_chart_settings' ) );
		}

		public function load_admin_assets() {
			wp_enqueue_script( 'mwtsa-chart-bundle-script', MWTSAI()->plugin_admin_url . 'assets/js/chart.bundle.min.js', array( 'jquery' ), MWTSAI()->version, true );

			wp_enqueue_script( 'mwtsa-chart-controller-script', MWTSAI()->plugin_admin_url . 'assets/js/chart-controller.js', array( 'mwtsa-chart-bundle-script' ), MWTSAI()->version, true );

			wp_localize_script( 'mwtsa-chart-controller-script', 'mwtsa_chart_obj', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( $this->nonce_action ),
					'strings'  => array(
						'currentPeriod'  => __( 'Current Period', 'search-analytics' ),
						'previousPeriod' => __( 'Previous Period', 'search-analytics' ),
					)
				)
			);
		}

		public function render_stats_chart() {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( ! empty( $_REQUEST['search-term'] ) || ! empty( MWTSA_Options::get_option( 'mwtsa_hide_charts' ) ) ) {
                return;
            }

            $default_line_style = MWTSA_Options::get_option( 'chart_default_line_style' );
            $default_range = MWTSA_Options::get_option( 'chart_default_range' );

            $line_options = array(
                'basic'   => __( 'Basic Line', 'search-analytics' ),
                'stepped' => __( 'Stepped Line', 'search-analytics' )
            );

            $range_options = array(
                '2w'  => __( '2 Weeks', 'search-analytics' ),
                '2wc' => __( '2 Weeks Comparison', 'search-analytics' ),
                '1m'  => __( '1 Month', 'search-analytics' ),
                '1mc' => __( '1 Month Comparison', 'search-analytics' )
            );
            ?>
            <div class="col-content">

                <h2><?php esc_html_e( "Search Results Charts", 'search-analytics' ) ?></h2>
                <div class="mwtsa-chart-options">
                    <label for="chart-type">
                        <select id="chart-type" onchange="loadCharts()">
                            <?php foreach ( $line_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ) ?>" <?php selected( $value, $default_line_style ) ?>><?php echo esc_html( $label ) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label for="chart-ranges">
                        <select id="chart-ranges" onchange="loadCharts()">
                            <?php foreach ( $range_options as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ) ?>" <?php selected( $value, $default_range ) ?>><?php echo esc_html( $label ) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <span onclick="saveAsDefault()" class="button"><?php esc_html_e( 'Save as default', 'search-analytics' ) ?></span>
                </div>

                <div id="chart-content">
                    <canvas id="mwtsa-stats-chart" width="400" height="100"></canvas>
                </div>
            </div>
            <?php
			$this->render_search_times();
		}

		/**
		 * Searches of the last 4 weeks by day of the week and 4-hour interval, shaded by how busy each slot was.
		 */
		private function render_search_times() {
			global $wp_locale;

			$times         = ( new MWTSA_History_Data() )->get_search_times( 28 );
			$max           = max( array_map( 'max', $times['grid'] ) );
			$start_of_week = (int) get_option( 'start_of_week', 1 );
			?>
            <div class="col-content mwtsa-search-times">
                <h2><?php esc_html_e( 'When people search', 'search-analytics' ); ?></h2>
				<?php if ( ! $max ) : ?>
                    <p><?php esc_html_e( 'No searches in the last 4 weeks.', 'search-analytics' ); ?></p>
				<?php else : ?>
                    <table class="mwtsa-heatmap">
                        <caption>
							<?php
							/* translators: %s: timezone name, e.g. "UTC" */
							echo esc_html( sprintf( __( 'Searches over the last 4 weeks, by day of the week and time of day (%s).', 'search-analytics' ), $times['timezone']->getName() ) );
							?>
                        </caption>
                        <thead>
                            <tr>
                                <td></td>
								<?php for ( $interval = 0; $interval < 6; $interval ++ ) : ?>
                                    <th scope="col" title="<?php echo esc_attr( mwtsa_time_interval_label( $interval ) ); ?>"><?php echo esc_html( sprintf( '%02d–%02d', 4 * $interval, 4 * $interval + 4 ) ); ?></th>
								<?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
							<?php for ( $row = 0; $row < 7; $row ++ ) :
								$weekday = ( $start_of_week + $row ) % 7;
								?>
                                <tr>
                                    <th scope="row"><?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $weekday ) ) ); ?></th>
									<?php foreach ( $times['grid'][ $weekday ] as $interval => $count ) :
										$heat  = $count / $max;
										$style = $count ? 'background-color: rgba(34, 113, 177, ' . round( 0.12 + 0.88 * $heat, 2 ) . ');' . ( $heat > 0.55 ? ' color: #fff;' : '' ) : '';
										/* translators: 1: weekday, 2: time interval, 3: number of searches */
										$title = sprintf( _n( '%1$s, %2$s: %3$s search', '%1$s, %2$s: %3$s searches', $count, 'search-analytics' ), $wp_locale->get_weekday( $weekday ), mwtsa_time_interval_label( $interval ), number_format_i18n( $count ) );
										?>
                                        <td title="<?php echo esc_attr( $title ); ?>" style="<?php echo esc_attr( $style ); ?>"><?php echo $count ? esc_html( number_format_i18n( $count ) ) : ''; ?></td>
									<?php endforeach; ?>
                                </tr>
							<?php endfor; ?>
                        </tbody>
                    </table>
                    <p class="mwtsa-heatmap-busiest">
						<?php
						echo esc_html( sprintf(
							/* translators: 1: weekday, 2: time interval */
							__( 'Busiest day: %1$s. Busiest time: %2$s.', 'search-analytics' ),
							$wp_locale->get_weekday( $times['busiest_weekday'] ),
							mwtsa_time_interval_label( $times['busiest_interval'] )
						) );
						?>
                    </p>
				<?php endif; ?>
            </div>
			<?php
		}

		public function render_chart_data() {
            check_ajax_referer( $this->nonce_action );

			if ( ! mwtsa_current_user_can_view_stats() ) {
				wp_send_json_error( 'Forbidden', 403 );
			}

			$ranges = ! empty( $_REQUEST['chart_ranges'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['chart_ranges'] ) ) : '2w';

			switch ( $ranges ) {
				case '1m':
					$args = array(
						'since'   => 1,
						'unit'    => 'month',
						'compare' => false
					);
					break;
				case '2wc':
					$args = array(
						'since'   => 2,
						'unit'    => 'week',
						'compare' => true
					);
					break;
				case '1mc':
					$args = array(
						'since'   => 1,
						'unit'    => 'month',
						'compare' => true
					);
					break;
				case '2w':
				default:
					$args = array(
						'since'   => 2,
						'unit'    => 'week',
						'compare' => false
					);
					break;

			}

			wp_send_json_success( ( new MWTSA_History_Data )->get_daily_search_count_for_period_chart( $args ) );
		}

		public function save_default_chart_settings() {
			check_ajax_referer( $this->nonce_action );

			if ( ! mwtsa_current_user_can_view_stats() ) {
				wp_send_json_error( 'Forbidden', 403 );
			}

			if ( empty( $_POST['line_style'] ) || empty( $_POST['chart_ranges'] ) ) {
				wp_send_json_error( 'Bad Request!' );
			}

			$chart_ranges = sanitize_text_field( wp_unslash( $_POST['chart_ranges'] ) );
			$line_style   = sanitize_text_field( wp_unslash( $_POST['line_style'] ) );

			if ( ! in_array( $chart_ranges, array( '1m', '1mc', '2w', '2wc' ) ) ) {
				wp_send_json_error( 'Bad Request!' );
			}

			if ( ! in_array( $line_style, array( 'basic', 'stepped' ) ) ) {
				wp_send_json_error( 'Bad Request!' );
			}

			MWTSA_Options::set_options( array(
				'chart_default_range'      => $chart_ranges,
				'chart_default_line_style' => $line_style
			) );

			wp_send_json_success();
		}
	}
}
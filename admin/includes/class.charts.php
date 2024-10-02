<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Admin_Charts' ) ) {

    class MWTSA_Admin_Charts {

		private $nonce_action = 'mwtsa_chart_nonce';

        public function __construct() {
            add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );

            add_action( 'wp_ajax_render_chart_data', array( $this, 'render_chart_data' ) );
            add_action( 'wp_ajax_save_default_chart_settings', array( $this, 'save_default_chart_settings' ) );
        }

        public function load_admin_assets( $hook ) {

            if ( $hook == 'dashboard_page_search-analytics/admin/includes/class.stats' ) {
                wp_enqueue_script( 'mwtsa-chart-bundle-script', MWTSAI()->plugin_admin_url . 'assets/js/chart.bundle.min.js', array( 'jquery' ), MWTSAI()->version );

                wp_enqueue_script( 'mwtsa-chart-controller-script', MWTSAI()->plugin_admin_url . 'assets/js/chart-controller.js', array( 'mwtsa-chart-bundle-script' ) );

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

        }

        public function render_stats_chart() {
            if ( empty( $_REQUEST['search-term'] ) && empty ( MWTSA_Options::get_option( 'mwtsa_hide_charts' ) ) ) :
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
                                    <option value="<?php echo $value ?>" <?php selected( $value, $default_line_style ) ?>><?php echo esc_html( $label ) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label for="chart-ranges">
                            <select id="chart-ranges" onchange="loadCharts()">
                                <?php foreach ( $range_options as $value => $label ) : ?>
                                    <option value="<?php echo $value ?>" <?php selected( $value, $default_range ) ?>><?php echo esc_html( $label ) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <span onclick="saveAsDefault()" class="button"><?php esc_html_e( 'Save as default', 'search-analytics' ) ?></span>
                    </div>

                    <div id="chart-content">
                        <canvas id="mwtsa-stats-chart" width="400" height="100"></canvas>
                    </div>
                </div>
            <?php endif;
        }

        public function render_chart_data() {
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->nonce_action ) ) {
				wp_send_json_error( 'Bad Request!' );
            }

			$ranges = ! empty( $_REQUEST['chart_ranges'] ) ? $_REQUEST['chart_ranges'] : '2w';

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

			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $this->nonce_action ) ) {
				wp_send_json_error( 'Bad Request!' );
			}

			if ( empty( $_POST['line_style'] ) || empty( $_POST['chart_ranges'] ) ) {
				wp_send_json_error( 'Bad Request!' );
			}

			$chart_ranges = $_POST['chart_ranges'];
			$line_style   = $_POST['line_style'];

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
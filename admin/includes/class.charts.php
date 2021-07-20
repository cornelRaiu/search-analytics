<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Admin_Charts' ) ) {

    class MWTSA_Admin_Charts {

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
                        'ajax_url' => admin_url( 'admin-ajax.php' )
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

                    <h2><?php _e( "Search Results Charts", 'search-analytics' ) ?></h2>
                    <div class="mwtsa-chart-options">
                        <label for="chart-type">
                            <select id="chart-type" onchange="loadCharts()">
                                <?php foreach ( $line_options as $value => $label ) : ?>
                                    <option value="<?php echo $value ?>" <?php selected( $value, $default_line_style ) ?>><?php echo $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label for="chart-ranges">
                            <select id="chart-ranges" onchange="loadCharts()">
                                <?php foreach ( $range_options as $value => $label ) : ?>
                                    <option value="<?php echo $value ?>" <?php selected( $value, $default_range ) ?>><?php echo $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <span onclick="saveAsDefault()" class="button"><?php _e( 'Save as default', 'search-analytics' ) ?></span>
                    </div>

                    <div id="chart-content"></div>
                </div>
            <?php endif;
        }

        public function render_chart_data() {


            if ( empty( $_REQUEST['line_style'] ) || empty( $_REQUEST['chart_ranges'] ) ) {
                echo 'Bad Request!';
            }

            $stepped_line = ( $_REQUEST['line_style'] == 'stepped' ) ? 'true' : 'false';

            switch ( $_REQUEST['chart_ranges'] ) {
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

            list( $dates, $history ) = ( new MWTSA_History_Data )->get_daily_search_count_for_period_chart( $args );

            ?>

            <canvas id="mwtsa-stats-chart" width="400" height="100"></canvas>
            <script>
              const ctx = document.getElementById("mwtsa-stats-chart").getContext("2d");
              const stepSize = parseInt( <?php echo ceil( max( $history[0] ) / 15 ) ?> );
              const stackedLine = new Chart(ctx, {
                type: 'line',
                data: {
                  labels: ["<?php echo implode( '", "', $dates ) ?>"],
                  datasets: [
                      <?php foreach ( $history as $k => $set ) :
                      $set = implode( '", "', $set );
                      ?>
                    {
                      label: "<?php echo $k == 1 ? __( 'Previous Period', 'search-analytics' ) : __( 'Current Period', 'search-analytics' ) ?>",
                      data: ["<?php echo $set ?>"],
                      borderColor: [
                        "<?php echo ( $k == 1 ) ? 'rgba(0,0,0,1)' : 'rgba(255,99,132,1)' ?>"
                      ],
                      borderWidth: 1,
                      steppedLine: <?php echo $stepped_line ?>
                    },
                      <?php endforeach; ?>
                  ]
                },
                options: {
                  scales: {
                    yAxes: [{
                      ticks: {
                        beginAtZero: true,
                        callback: function (value) {
                          if (Number.isInteger(value)) {
                            return value;
                          }
                        },
                        stepSize: stepSize
                      }
                    }]
                  },
                  elements: {
                    line: {
                      tension: 0
                    }
                  },
                  tooltips: {
                    mode: 'index'
                  }
                }
              });
            </script>
            <?php

            exit();
        }

        public function save_default_chart_settings() {
            if ( empty( $_REQUEST['line_style'] ) || empty( $_REQUEST['chart_ranges'] ) ) {
                echo 'Bad Request!';
            }

            MWTSA_Options::set_options( array(
                'chart_default_line_style' => sanitize_text_field( $_REQUEST['line_style'] ),
                'chart_default_range'      => sanitize_text_field( $_REQUEST['chart_ranges'] )
            ) );

            exit();
        }
    }
}
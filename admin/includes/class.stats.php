<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

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

            add_action( "load-{$this->view}", array( $this, 'add_screen_options' ) );
            add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );

            if ( empty( $_REQUEST['search-term'] ) && empty ( MWTSA_Options::get_option( 'mwtsa_hide_charts' ) ) ) {
                $this->charts = new MWTSA_Admin_Charts();
            }

            include_once( 'class.stats-table.php' );
            include_once( 'class.term-stats.php' );
        }

        public function mwtsa_init() {

            if ( isset( $_REQUEST['mwtsa-export-csv'] ) ) {
                $columns = array(
                    'Term ID',
                    'Term',
                    'Searches',
                    'Average Results',
                    'Last Search Date'
                );

                if ( ! empty( $_REQUEST['search-term'] ) ) {
                    $columns = array(
                        'Average Results',
                        'Date and Time'
                    );

                    if ( ! empty( $_REQUEST['grouped_view'] ) ) {
                        $columns[] = "Searches";
                    }
                }

                $export_csv = new MWTSA_Export_CSV();
                $export_csv->mwtsa_export_to_csv( ( new MWTSA_History_Data )->get_terms_history_data(), '', $columns );
            }
        }

        public function add_screen_options() {
            $option = 'per_page';

            $args = array(
                'label'   => 'Entries Per Page',
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
            $accepted_user_roles = array_intersect( $this_user_role, $this->plugin_options['mwtsa_display_stats_for_role'] );

            if ( ! isset( $this->plugin_options['mwtsa_display_stats_for_role'] ) || ! empty( $accepted_user_roles ) ) {

                add_submenu_page( 'index.php', __( 'Search Analytics', 'mwt-search-analytics' ), __( 'Search Analytics', 'mwt-search-analytics' ), $accepted_user_roles[0], __FILE__, array(
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
                wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', false, '1.0', 'all' );

                wp_enqueue_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), '1.0', true );
            }

            wp_register_style( 'mwtsa-datepicker-ui', '//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css', array(), '1.11.2' );

            wp_enqueue_script( 'mwtsa-admin-script', $mwtsa->plugin_admin_url . 'assets/js/admin.js', array(), $mwtsa->version );

            wp_localize_script( 'mwtsa-admin-script', 'mwtsa_admin_obj', array(
                    'gmt_offset'  => wp_timezone()->getOffset(new DateTime()),
                    'date_format' => mwt_wp_date_format_to_js_datepicker_format( get_option( 'date_format' ) )
                )
            );
        }

        public function render_stats_page() {
            global $mwtsa;

            $stats_table = ! empty( $_REQUEST['search-term'] ) ? new MWTSA_Term_Stats_Table() : new MWTSA_Stats_Table();

            ?>
            <div class="wrap mwtsa-wrapper">
                <div class="mwtsa-2-col">
                    <div class="mwtsa-col-1">
                        <div class="col-content">
                            <?php echo $stats_table->load_notices(); ?>
                            <?php echo $stats_table->this_title(); ?>
                            <div class="wp-clearfix">
                                <span class="views-label"><?php _e( 'Time filters:', 'mwt-search-analytics' ) ?></span><?php $stats_table->display_time_views(); ?>
                            </div>
                            <div class="wp-clearfix">
                                <span class="views-label"><?php _e( 'Results filters:', 'mwt-search-analytics' ) ?></span><?php $stats_table->display_results_views(); ?>
                            </div>

                            <?php if ( ! empty( $_REQUEST['search-term'] ) ) : ?>
                                <div class="wp-clearfix">
                                    <span class="views-label"><?php _e( 'Group By:', 'mwt-search-analytics' ) ?></span><?php $stats_table->display_group_views(); ?>
                                </div>
                            <?php else : ?>
                                <div class="wp-clearfix">
                                    <span class="views-label"><?php _e( 'Group By:', 'mwt-search-analytics' ) ?></span><?php $stats_table->display_results_grouping(); ?>
                                </div>
                            <?php endif; ?>
                            <?php $stats_table->prepare_items(); ?>

                            <form method="get">
                                <input type="hidden" name="page" value="<?php echo $stats_table->get_this_screen() ?>">
                                <?php if ( isset ( $_REQUEST['date_from'] ) ): ?>
                                    <input type="hidden" name="date_from" value="<?php echo $_REQUEST['date_from'] ?>">
                                <?php endif; ?>
                                <?php if ( isset ( $_REQUEST['date_to'] ) ): ?>
                                    <input type="hidden" name="date_to" value="<?php echo $_REQUEST['date_to'] ?>">
                                <?php endif; ?>
                                <?php if ( isset ( $_REQUEST['period_view'] ) ): ?>
                                    <input type="hidden" name="period_view" value="<?php echo $_REQUEST['period_view'] ?>">
                                <?php endif; ?>
                                <?php if ( isset ( $_REQUEST['results_view'] ) ): ?>
                                    <input type="hidden" name="results_view" value="<?php echo $_REQUEST['results_view'] ?>">
                                <?php endif; ?>
                                <?php if ( isset ( $_REQUEST['grouped_view'] ) ): ?>
                                    <input type="hidden" name="grouped_view" value="<?php echo $_REQUEST['grouped_view'] ?>">
                                <?php endif; ?>
                                <?php if ( ! empty ( $_REQUEST['search-term'] ) ): ?>
                                    <input type="hidden" name="search-term" value="<?php echo $_REQUEST['search-term'] ?>">
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
                            <h2><?php _e( 'Search Analytics', 'mwt-search-analytics' ) ?></h2>

                            <h3><?php _e( 'Changelog', 'mwt-search-analytics' ) ?></h3>

                            <p><?php echo sprintf( __( 'New in version %s', 'mwt-search-analytics' ), $mwtsa->version ); ?></p>
                            <ul class="changelog-list">
                                <li>Bugfix: Fix fatal error for missing `wp_timezone()` in WP < 5.3.0</li>
                            </ul>
                            <h3><?php _e( 'Useful Links', 'mwt-search-analytics' ) ?></h3>
                            <ul>
                                <li>
                                    <a href="options-general.php?page=mwt-search-analytics"><?php _e( 'Settings Page', 'mwt-search-analytics' ) ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

return new MWTSA_Admin_Stats();
<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Dashboard' ) ) {

    class MWTSA_Dashboard {

        public static function init_action() {
            $dashboard = new MWTSA_Dashboard();
            $dashboard->init();
        }

        public function init() {
            $this_user_role      = mwt_get_current_user_roles();
            $plugin_options      = MWTSA_Options::get_options();
            $accepted_user_roles = array_values( array_intersect( $this_user_role, $plugin_options['mwtsa_display_stats_for_role'] ) );

            if ( ! isset( $plugin_options['mwtsa_display_stats_for_role'] ) || ! empty( $accepted_user_roles ) ) {

                wp_add_dashboard_widget( 'mwtsa_last_week_stats_widget', __( 'Last Week Search Stats', 'search-analytics' ), array(
                    $this,
                    'last_week_stats_widget'
                ) );
            }
        }

        public function last_week_stats_widget() {
            $search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( array(
                'since' => 1,
                'unit'  => 'week'
            ) );

            $last_search_result = ( new MWTSA_History_Data )->run_terms_history_data_query( array(
                'since'            => 1,
                'unit'             => 'week',
                'return_only_last' => true
            ) );

            if ( empty( $search_results ) ) {
                _e( 'No search statistics found', 'search-analytics' );

                return;
            }
            $most_searched_term = $search_results[0];
            $last_search_term   = $last_search_result[0];
            $total_searches     = array_sum( array_column( $search_results, 'count' ) );

            echo '<ul class="mwtsa-stats-widget-wrapper">';

            echo '<li><span class="stats-list-label">' . __( "Total Searches:", 'search-analytics' ) . '</span>';
            echo '<span class="stats-list-value">' . absint( $total_searches ) . '</span></li>';

            echo '<li><span class="stats-list-label">' . __( "Most Searched Term:", 'search-analytics' ) . '</span>';
            echo '<span class="stats-list-value">' . esc_attr( $most_searched_term['term'] ) . '</span></li>';

            echo '<li><span class="stats-list-label">' . __( "Most Searched Term Count:", 'search-analytics' ) . '</span>';
            echo '<span class="stats-list-value">' . absint( $most_searched_term['count'] ) . '</span></li>';

            echo '<li><span class="stats-list-label">' . __( "Last Searched Term:", 'search-analytics' ) . '</span>';
            echo '<span class="stats-list-value">' . esc_attr( $last_search_term['term'] ) . '</span></li>';

            echo '<li><span class="stats-list-label">' . __( "Last Searched Date:", 'search-analytics' ) . '</span>';
            echo '<span class="stats-list-value">' . esc_attr( $last_search_term['last_search_date'] ) . '</span></li>';

            echo '</ul>';
        }
    }
}
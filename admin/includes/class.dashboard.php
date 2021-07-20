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

        public function add_plugin_meta_links($meta_fields, $file){
            if ( $file == 'search-analytics/mwt-search-analytics.php' ) {

                $meta_fields[] = "<a href='" . MWTSA_WORDPRESS_URL . "' target='_blank'>" . esc_html__( 'Support Forum', 'search-analytics' ) . "</a>";
                $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";

                $stars = '<i class="mwtsa-rate-stars">';

                for ( $i = 1; $i <= 5; $i++ ) {
                    $stars .= '<a href="' . MWTSA_WORDPRESS_URL . '/reviews/?rate=' . $i . '#new-post" target="_blank">' . $svg . '</a>';
                }

                $stars .= '</i>';

                $meta_fields[] = $stars;

                echo "<style>"
                     .".mwtsa-rate-stars{display:inline-block;color:#ffb900;position:relative;top:3px;}"
                     .".mwtsa-rate-stars a {color:#ffb900;}"
                     .".mwtsa-rate-stars a svg{fill:#ffb900;}"
                     .".mwtsa-rate-stars a:hover svg{fill:#ffb900}"
                     .".mwtsa-rate-stars a:hover ~ a svg {fill:none;}"
                     ."</style>";
            }

            return $meta_fields;
        }
    }
}
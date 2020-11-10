<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'MWTSA_Display_Search_Stats_Shortcode' ) ) {
    class MWTSA_Display_Search_Stats_Shortcode {

        public static function init() {
            add_shortcode( 'mwtsa_display_search_stats', [ __CLASS__, 'render' ] );
        }

        public static function render( $atts ) {
            $atts = shortcode_atts( [
                'unit'                            => 'week',
                'amount'                          => 1,
                'most_searched'                   => true,
                'most_searched_count'             => 5,
                'most_searched_only_with_results' => true,
                'user_searches'                   => true,
                'user_searches_count'             => 5,
                'user_searches_only_with_results' => true,
            ], $atts, 'mwtsa_display_search_stats' );

            $atts['most_searched']                   = filter_var( $atts['most_searched'], FILTER_VALIDATE_BOOLEAN );
            $atts['most_searched_only_with_results'] = filter_var( $atts['most_searched_only_with_results'], FILTER_VALIDATE_BOOLEAN );
            $atts['user_searches']                   = filter_var( $atts['user_searches'], FILTER_VALIDATE_BOOLEAN );
            $atts['user_searches_only_with_results'] = filter_var( $atts['user_searches_only_with_results'], FILTER_VALIDATE_BOOLEAN );

            if ( $atts['most_searched_count'] < 1 ) {
                $atts['most_searched_count'] = 1;
            }

            $data = [
                'most_searched' => [],
                'user_searches' => []
            ];

            if ( $atts['most_searched'] ) {
                $search_args = [
                    'since' => $atts['amount'],
                    'unit'  => $atts['unit']
                ];

                if ( $atts['most_searched_only_with_results'] ) {
                    $search_args['min_results'] = 1;
                }

                $search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $search_args );

                $data['most_searched'] = array_slice( $search_results, 0, $atts['most_searched_count'] );
            }

            if ( $atts['user_searches'] && ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) && is_user_logged_in() ) {

                $user = wp_get_current_user();

                $user_search_args = [
                    'since' => $atts['amount'],
                    'unit'  => $atts['unit'],
                    'user'  => $user->ID
                ];

                if ( $atts['user_searches_only_with_results'] ) {
                    $user_search_args['min_results'] = 1;
                }

                $search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $user_search_args );

                $data['user_searches'] = array_slice( $search_results, 0, $atts['user_searches_count'] );
            }

            ob_start();
            ?>
            <div class="mwtsa-search-stats">
                <ul>
                    <?php if ( count( $data['most_searched'] ) > 0 ) : ?>
                        <li>
                            <p>Most Searched Terms</p>
                            <ul>
                                <?php foreach ( $data['most_searched'] as $term ) : ?>
                                    <li><?php echo $term['term'] ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ( count( $data['user_searches'] ) > 0 ) :
                        $title = ( $atts['user_searches_only_with_results'] ) ? 'Your Last Successful Searches' : 'Your Last Searches'; ?>
                        <li>
                            <p><?php echo $title ?></p>
                            <ul>
                                <?php foreach ( $data['user_searches'] as $term ) : ?>
                                    <li><?php echo $term['term'] ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php
            $html = ob_get_clean();

            return apply_filters( 'mwtsa_display_search_stats_shortcode_output', $html, $atts, $data );
        }
    }
}
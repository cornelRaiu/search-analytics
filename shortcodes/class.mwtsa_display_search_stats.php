<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Display_Search_Stats_Shortcode' ) ) {
    class MWTSA_Display_Search_Stats_Shortcode {

        public static function init() {
            add_shortcode( 'mwtsa_display_search_stats', array( __CLASS__, 'render' ) );
        }

        public static function render( $atts ) {
            $atts = shortcode_atts( array(
                'unit'                            => 'week',
                'amount'                          => 1,
                'most_searched'                   => true,
                'most_searched_count'             => 5,
                'most_searched_only_with_results' => true,
                'user_searches'                   => true,
                'user_searches_count'             => 5,
                'user_searches_only_with_results' => true,
                'wrapper_class'                   => 'mwtsa-search-stats',
            ), $atts, 'mwtsa_display_search_stats' );

            $atts['most_searched']                   = filter_var( $atts['most_searched'], FILTER_VALIDATE_BOOLEAN );
            $atts['most_searched_only_with_results'] = filter_var( $atts['most_searched_only_with_results'], FILTER_VALIDATE_BOOLEAN );
            $atts['user_searches']                   = filter_var( $atts['user_searches'], FILTER_VALIDATE_BOOLEAN );
            $atts['user_searches_only_with_results'] = filter_var( $atts['user_searches_only_with_results'], FILTER_VALIDATE_BOOLEAN );

            if ( $atts['most_searched_count'] < 1 ) {
                $atts['most_searched_count'] = 1;
            }

            $data = [
                'most_searched' => apply_filters( 'mwtsa_display_search_stats_shortcode_most_searched_terms', array() ),
                'user_searches' => apply_filters( 'mwtsa_display_search_stats_shortcode_user_searches_terms', array() )
            ];

            if ( $atts['most_searched'] ) {
                $search_args = array(
                    'since' => $atts['amount'],
                    'unit'  => $atts['unit']
                );

                if ( $atts['most_searched_only_with_results'] ) {
                    $search_args['min_results'] = 1;
                }

                $search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $search_args );

                $data['most_searched'] = array_slice( $search_results, 0, $atts['most_searched_count'] );
            }

            if ( $atts['user_searches'] && ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) && is_user_logged_in() ) {

                $user = wp_get_current_user();

                $user_search_args = array(
                    'since' => $atts['amount'],
                    'unit'  => $atts['unit'],
                    'user'  => $user->ID
                );

                if ( $atts['user_searches_only_with_results'] ) {
                    $user_search_args['min_results'] = 1;
                }

                $search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $user_search_args );

                $data['user_searches'] = array_slice( $search_results, 0, $atts['user_searches_count'] );
            }

            ob_start();
            ?>
            <div class="<?php echo $atts['wrapper_class'] ?>">
                <ul>
                    <?php if ( count( $data['most_searched'] ) > 0 ) : ?>
                        <li>
                            <p><?php _e( 'Most Searched Terms', 'search-analytics' ) ?></p>
                            <ul>
	                            <?php foreach ( $data['most_searched'] as $term ) :
		                            echo apply_filters(
                                        'mwtsa_display_search_stats_shortcode_list_item_output',
			                            sprintf(
				                            '<li>%s</li>',
				                            esc_attr( $term['term'] )
			                            ),
			                            $atts,
                                        'most_searched'
                                    );
	                            endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ( count( $data['user_searches'] ) > 0 ) :
                        $title = ( $atts['user_searches_only_with_results'] ) ?
                            __( 'Your Last Successful Searches', 'search-analytics' ) :
                            __( 'Your Last Searches', 'search-analytics' );
                        ?>
                        <li>
                            <p><?php echo $title ?></p>
                            <ul>
	                            <?php foreach ( $data['user_searches'] as $term ) :
		                            echo apply_filters(
			                            'mwtsa_display_search_stats_shortcode_list_item_output',
			                            sprintf(
				                            '<li>%s</li>',
				                            esc_attr( $term['term'] )
			                            ),
			                            $atts,
			                            'user_searches'
		                            );
	                            endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php

            return apply_filters( 'mwtsa_display_search_stats_shortcode_output', ob_get_clean(), $atts, $data );
        }
    }
}
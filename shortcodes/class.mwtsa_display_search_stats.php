<?php
defined( "ABSPATH" ) || exit;

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
				'most_searched_label'             => '',
				'user_searches'                   => true,
				'user_searches_count'             => 5,
				'user_searches_only_with_results' => true,
				'user_searches_label'             => '',
				'link_terms'                      => false,
				'show_label'                      => true,
				'wrapper_class'                   => 'mwtsa-search-stats',
			), $atts, 'mwtsa_display_search_stats' );

			$atts['most_searched']                   = filter_var( $atts['most_searched'], FILTER_VALIDATE_BOOLEAN );
			$atts['most_searched_only_with_results'] = filter_var( $atts['most_searched_only_with_results'], FILTER_VALIDATE_BOOLEAN );
			$atts['user_searches']                   = filter_var( $atts['user_searches'], FILTER_VALIDATE_BOOLEAN );
			$atts['user_searches_only_with_results'] = filter_var( $atts['user_searches_only_with_results'], FILTER_VALIDATE_BOOLEAN );
			$atts['link_terms']                      = filter_var( $atts['link_terms'], FILTER_VALIDATE_BOOLEAN );
			$atts['show_label']                      = filter_var( $atts['show_label'], FILTER_VALIDATE_BOOLEAN );

			if ( $atts['most_searched_label'] === '' ) {
				$atts['most_searched_label'] = ( $atts['most_searched_only_with_results'] ) ?
					__( 'Most Searched Terms With Results', 'search-analytics' ) :
					__( 'Most Searched Terms', 'search-analytics' );
			}

			if ( $atts['user_searches_label'] === '' ) {
				$atts['user_searches_label'] = ( $atts['user_searches_only_with_results'] ) ?
					__( 'Your Last Successful Searches', 'search-analytics' ) :
					__( 'Your Last Searches', 'search-analytics' );
			}

			if ( $atts['most_searched_count'] < 1 ) {
				$atts['most_searched_count'] = 1;
			}

			$data = [
				'most_searched' => apply_filters( 'mwtsa_display_search_stats_shortcode_most_searched_terms', array() ),
				'user_searches' => apply_filters( 'mwtsa_display_search_stats_shortcode_user_searches_terms', array() )
			];

			if ( $atts['most_searched'] && count( $data['most_searched'] ) === 0 ) {
				$search_args = array(
					'since' => $atts['amount'],
					'unit'  => $atts['unit'],
					'count' => (int) $atts['most_searched_count']
				);

				if ( $atts['most_searched_only_with_results'] ) {
					$search_args['min_results'] = 1;
				}

				$search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $search_args );

				$data['most_searched'] = array_slice( $search_results, 0, (int) $atts['most_searched_count'] );
			}

			if ( $atts['user_searches'] && count( $data['user_searches'] ) === 0 && ! empty( MWTSA_Options::get_option( 'mwtsa_save_search_by_user' ) ) && is_user_logged_in() ) {

				$user = wp_get_current_user();

				$user_search_args = array(
					'since' => $atts['amount'],
					'unit'  => $atts['unit'],
					'user'  => $user->ID,
					'count' => (int) $atts['user_searches_count']
				);

				if ( $atts['user_searches_only_with_results'] ) {
					$user_search_args['min_results'] = 1;
				}

				// The latest first. The query's own order is by number of searches.
				add_filter( 'mwtsa_run_terms_history_order_by', array( 'MWTSA_Display_Latest_Searches_Shortcode', 'order_by_latest' ) );

				$search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $user_search_args );

				remove_filter( 'mwtsa_run_terms_history_order_by', array( 'MWTSA_Display_Latest_Searches_Shortcode', 'order_by_latest' ) );

				$data['user_searches'] = array_slice( $search_results, 0, (int) $atts['user_searches_count'] );
			}

			ob_start();
			?>
            <div class="<?php echo esc_attr( $atts['wrapper_class'] ) ?>">
				<?php if ( $atts['show_label'] ) : ?>
                <ul>
					<?php if ( count( $data['most_searched'] ) > 0 ) : ?>
                        <li>
                            <p><?php echo esc_html( $atts['most_searched_label'] ) ?></p>
                            <ul>
								<?php echo self::list_items( 'most_searched', $data, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </ul>
                        </li>
					<?php endif; ?>
					<?php if ( count( $data['user_searches'] ) > 0 ) : ?>
                        <li>
                            <p><?php echo esc_attr( $atts['user_searches_label'] ) ?></p>
                            <ul>
								<?php echo self::list_items( 'user_searches', $data, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </ul>
                        </li>
					<?php endif; ?>
                </ul>
				<?php else : ?>
					<?php foreach ( array( 'most_searched', 'user_searches' ) as $list ) : ?>
						<?php if ( count( $data[ $list ] ) > 0 ) : ?>
                <ul class="mwtsa-<?php echo esc_attr( str_replace( '_', '-', $list ) ) ?>">
					<?php echo self::list_items( $list, $data, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </ul>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
            </div>
			<?php

			return apply_filters( 'mwtsa_display_search_stats_shortcode_output', ob_get_clean(), $atts, $data );
		}

		private static function list_items( $list, $data, $atts ) {
			$items = '';

			foreach ( $data[ $list ] as $term ) {
				$items .= apply_filters(
					'mwtsa_display_search_stats_shortcode_list_item_output',
					sprintf(
						'<li>%s</li>',
						mwtsa_listed_term_html( $term['term'], $atts['link_terms'] )
					),
					$atts,
					$list,
					$term
				);
			}

			return $items;
		}
	}
}
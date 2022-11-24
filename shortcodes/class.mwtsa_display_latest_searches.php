<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Display_Latest_Searches_Shortcode' ) ) {
	class MWTSA_Display_Latest_Searches_Shortcode {

		public static function init() {
			add_shortcode( 'mwtsa_display_latest_searches', array( __CLASS__, 'render' ) );
		}

		public static function order_by_latest() {
			return "`last_search_date` DESC";
		}

		public static function render( $atts ) {
			$atts = shortcode_atts( array(
				'unit'                            => 'week',
				'amount'                          => 1,
				'count'                           => 10,
				'only_with_results'               => true,
				'wrapper_class'                   => 'mwtsa-latest-searches',
				'label'                           =>  ''
			), $atts, 'mwtsa_display_latest_searches' );

			$atts['only_with_results'] = filter_var( $atts['only_with_results'], FILTER_VALIDATE_BOOLEAN );

			if ( $atts['label'] === '' ) {
				$atts['label'] = ( $atts['only_with_results'] ) ?
					__( 'Recently Searched Terms With Results', 'search-analytics' ) :
					__( 'Recently Searched Terms', 'search-analytics' );
			}

			$search_args = array(
				'since' => (int) $atts['amount'],
				'unit'  => $atts['unit'],
				'group' => 'term_id',
				'count' => $atts['count']
			);

			if ( $atts['only_with_results'] ) {
				$search_args['min_results'] = 1;
			}

			add_filter( 'mwtsa_run_terms_history_order_by', array( __CLASS__, 'order_by_latest' ) );

			$search_results = ( new MWTSA_History_Data )->run_terms_history_data_query( $search_args );

			remove_filter( 'mwtsa_run_terms_history_order_by', array( __CLASS__, 'order_by_latest' ) );

			ob_start();
			?>
            <div class="<?php echo $atts['wrapper_class'] ?>">
				<?php if ( count( $search_results ) > 0 ) : ?>
                    <p><?php echo $atts['label'] ?></p>
                    <ul>
						<?php foreach ( $search_results as $term ) :
							echo apply_filters(
								'mwtsa_display_latest_searches_shortcode_list_item_output',
								sprintf(
									'<li>%s</li>',
									esc_attr( $term['term'] )
								),
								$atts,
								$term
							);
						endforeach; ?>
                    </ul>
				<?php endif; ?>
            </div>
			<?php

			return apply_filters( 'mwtsa_display_latest_searches_shortcode_output', ob_get_clean(), $atts, $search_results );
		}
	}
}
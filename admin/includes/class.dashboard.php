<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Dashboard' ) ) {

	class MWTSA_Dashboard {

		public static function init_action() {
			$dashboard = new MWTSA_Dashboard();
			$dashboard->init();
		}

		public function init() {
			$this_user_role      = mwtsa_get_current_user_roles();
			$plugin_options      = MWTSA_Options::get_options();
			$accepted_user_roles = array_values( array_intersect( $this_user_role, $plugin_options['mwtsa_display_stats_for_role'] ) );

			if ( ! isset( $plugin_options['mwtsa_display_stats_for_role'] ) || ! empty( $accepted_user_roles ) ) {

				wp_add_dashboard_widget( 'mwtsa_last_week_stats_widget', __( 'Search Analytics', 'search-analytics' ), array(
					$this,
					'last_week_stats_widget'
				) );
			}
		}

		public function last_week_stats_widget() {
			global $wp_locale;

			$history   = new MWTSA_History_Data();
			$since     = gmdate( 'Y-m-d H:i:00', time() - WEEK_IN_SECONDS );
			$before    = gmdate( 'Y-m-d H:i:00', time() - 2 * WEEK_IN_SECONDS );
			$current   = $history->get_period_summary( $since );
			$previous  = $history->get_period_summary( $before, $since );
			$last      = $history->get_last_search();
			$times     = $history->get_search_times( 28 );
			$stats_url = admin_url( 'admin.php?page=mwtsa-search-analytics' );
			/* translators: %s: number of searches or terms */
			$previous_label = __( 'Previous 7 days: %s', 'search-analytics' );
			?>
            <div class="mwtsa-dw">
				<?php if ( empty( $last ) ) : ?>
                    <p class="mwtsa-dw-empty"><?php esc_html_e( 'No searches recorded yet. Searches made with your site\'s search box will show up here.', 'search-analytics' ); ?></p>
				<?php else : ?>
                    <div class="mwtsa-dw-heading">
                        <h3><?php esc_html_e( 'Searches', 'search-analytics' ); ?></h3>
                        <span class="mwtsa-dw-period"><?php esc_html_e( 'Last 7 days', 'search-analytics' ); ?></span>
                        <a href="<?php echo esc_url( $stats_url ); ?>" class="mwtsa-dw-icon-link"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'View statistics', 'search-analytics' ); ?></span></a>
                    </div>
                    <ul class="mwtsa-dw-cards">
						<?php
						$this->render_card(
							__( 'Searches', 'search-analytics' ),
							__( 'Searches made in the last 7 days, compared with the 7 days before.', 'search-analytics' ),
							number_format_i18n( $current['searches'] ),
							$this->format_change( $current['searches'], $previous['searches'] ),
							sprintf( $previous_label, number_format_i18n( $previous['searches'] ) )
						);
						$this->render_card(
							__( 'No results', 'search-analytics' ),
							__( 'Searches that found nothing: topics visitors expected to find on your site.', 'search-analytics' ),
							number_format_i18n( $current['no_results'] ),
							$current['searches'] ? number_format_i18n( round( 100 * $current['no_results'] / $current['searches'] ) ) . '%' : '',
							__( 'Share of all searches', 'search-analytics' )
						);
						$this->render_card(
							__( 'Unique terms', 'search-analytics' ),
							__( 'Different search terms used in the last 7 days.', 'search-analytics' ),
							number_format_i18n( $current['terms'] ),
							$this->format_change( $current['terms'], $previous['terms'] ),
							sprintf( $previous_label, number_format_i18n( $previous['terms'] ) )
						);
						$this->render_card(
							__( 'Last search', 'search-analytics' ),
							__( 'The most recent search on your site.', 'search-analytics' ),
							$last['term'],
							/* translators: %s: time since the search, e.g. "5 mins" */
							sprintf( __( '%s ago', 'search-analytics' ), human_time_diff( strtotime( $last['datetime'] . ' UTC' ) ) ),
							get_date_from_gmt( $last['datetime'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
							'mwtsa-dw-card-term'
						);
						?>
                    </ul>
					<?php
					$this->render_daily_bars( array_slice( $times['per_day'], -7, 7, true ) );
					$this->render_term_list(
						__( 'Trending', 'search-analytics' ),
						$history->get_trending_terms( $since, $before ),
						'',
						__( 'No term was searched more often than in the 7 days before.', 'search-analytics' ),
						$stats_url,
						true
					);
					$this->render_term_list(
						__( 'Top searches', 'search-analytics' ),
						$history->get_top_terms( $since ),
						add_query_arg( 'period_view', 1, $stats_url ),
						__( 'No searches in the last 7 days.', 'search-analytics' ),
						$stats_url
					);
					$this->render_term_list(
						__( 'Searches with no results', 'search-analytics' ),
						$history->get_top_terms( $since, 5, true ),
						add_query_arg( array( 'period_view' => 1, 'results_view' => 2 ), $stats_url ),
						__( 'Every search in the last 7 days found something.', 'search-analytics' ),
						$stats_url
					);
					?>
					<?php if ( $times['total'] ) : ?>
                        <div class="mwtsa-dw-heading">
                            <h3><?php esc_html_e( 'When people search', 'search-analytics' ); ?></h3>
                            <span class="mwtsa-dw-period"><?php esc_html_e( 'Last 4 weeks', 'search-analytics' ); ?></span>
                        </div>
                        <ul class="mwtsa-dw-cards">
							<?php
							$busiest_day_searches = $times['per_weekday'][ $times['busiest_weekday'] ];
							$this->render_card(
								__( 'Busiest day', 'search-analytics' ),
								__( 'The day of the week with the most searches over the last 4 weeks.', 'search-analytics' ),
								$wp_locale->get_weekday( $times['busiest_weekday'] ),
								/* translators: %s: number of searches */
								sprintf( _n( '%s search', '%s searches', $busiest_day_searches, 'search-analytics' ), number_format_i18n( $busiest_day_searches ) ),
								'',
								'mwtsa-dw-card-term'
							);
							$this->render_card(
								__( 'Busiest time', 'search-analytics' ),
								__( 'The 4-hour window of the day with the most searches over the last 4 weeks.', 'search-analytics' ),
								mwtsa_time_interval_label( $times['busiest_interval'] ),
								$times['timezone']->getName(),
								__( 'Timezone of the statistics', 'search-analytics' ),
								'mwtsa-dw-card-term'
							);
							?>
                        </ul>
					<?php endif; ?>
				<?php endif; ?>
            </div>
            <div class="mwtsa-dw-footer">
                <a href="<?php echo esc_url( $stats_url ); ?>"><?php esc_html_e( 'Statistics', 'search-analytics' ); ?></a>
				<?php if ( mwtsa_current_user_can_manage_settings() ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=mwtsa-search-analytics-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'search-analytics' ); ?></a>
				<?php endif; ?>
                <a href="<?php echo esc_url( MWTSA_WORDPRESS_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'search-analytics' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'search-analytics' ); ?></span><span class="dashicons dashicons-external" aria-hidden="true"></span></a>
            </div>
			<?php
		}

		private function render_card( $label, $help, $value, $note, $note_title, $class = '' ) {
			?>
            <li class="<?php echo esc_attr( $class ); ?>">
                <span class="mwtsa-dw-label"><?php echo esc_html( $label ); ?><span class="dashicons dashicons-editor-help" title="<?php echo esc_attr( $help ); ?>" aria-hidden="true"></span></span>
                <span class="mwtsa-dw-value"><?php echo esc_html( $value ); ?></span>
				<?php if ( '' !== $note ) : ?>
                    <span class="mwtsa-dw-note" title="<?php echo esc_attr( $note_title ); ?>"><?php echo esc_html( $note ); ?></span>
				<?php endif; ?>
            </li>
			<?php
		}

		/**
		 * With $trending, each term shows how many more searches it had than in the 7 days before.
		 */
		private function render_term_list( $title, $terms, $view_all_url, $empty_text, $stats_url, $trending = false ) {
			?>
            <div class="mwtsa-dw-heading">
                <h3><?php echo esc_html( $title ); ?></h3>
				<?php if ( '' !== $view_all_url ) : ?>
                    <a href="<?php echo esc_url( $view_all_url ); ?>"><?php esc_html_e( 'View all', 'search-analytics' ); ?></a>
				<?php endif; ?>
            </div>
			<?php if ( empty( $terms ) ) : ?>
                <p class="mwtsa-dw-list-empty"><?php echo esc_html( $empty_text ); ?></p>
			<?php else : ?>
                <ol class="mwtsa-dw-terms">
					<?php foreach ( $terms as $term ) : ?>
                        <li>
                            <a href="<?php echo esc_url( add_query_arg( 'search-term', (int) $term['id'], $stats_url ) ); ?>"><?php echo esc_html( $term['term'] ); ?></a>
							<?php if ( $trending ) :
								/* translators: 1: searches in the last 7 days, 2: how many more than in the 7 days before */
								$growth_title = sprintf( __( '%1$s searches, %2$s more than in the 7 days before', 'search-analytics' ), number_format_i18n( $term['searches'] ), number_format_i18n( $term['growth'] ) );
								?>
                                <span class="mwtsa-dw-count mwtsa-dw-growth" title="<?php echo esc_attr( $growth_title ); ?>">+<?php echo esc_html( number_format_i18n( $term['growth'] ) ); ?></span>
							<?php else : ?>
                                <span class="mwtsa-dw-count"><?php echo esc_html( number_format_i18n( $term['searches'] ) ); ?></span>
							<?php endif; ?>
                        </li>
					<?php endforeach; ?>
                </ol>
			<?php endif;
		}

		/**
		 * One bar per day, with the weekday's initial under it.
		 */
		private function render_daily_bars( $per_day ) {
			global $wp_locale;

			$max = max( 1, max( $per_day ) );
			?>
            <ul class="mwtsa-dw-bars" aria-label="<?php esc_attr_e( 'Searches per day', 'search-analytics' ); ?>">
				<?php foreach ( $per_day as $date => $count ) :
					$timestamp = strtotime( $date );
					/* translators: 1: date, 2: number of searches */
					$title = sprintf( _n( '%1$s: %2$s search', '%1$s: %2$s searches', $count, 'search-analytics' ), date_i18n( get_option( 'date_format' ), $timestamp ), number_format_i18n( $count ) );
					?>
                    <li title="<?php echo esc_attr( $title ); ?>">
                        <span class="mwtsa-dw-bar"><span style="height: <?php echo (int) ( $count ? max( 4, round( 100 * $count / $max ) ) : 0 ); ?>%"></span></span>
                        <span class="mwtsa-dw-bar-label" aria-hidden="true"><?php echo esc_html( $wp_locale->get_weekday_initial( $wp_locale->get_weekday( (int) gmdate( 'w', $timestamp ) ) ) ); ?></span>
                        <span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
                    </li>
				<?php endforeach; ?>
            </ul>
			<?php
		}

		/**
		 * The change from $previous to $current in percent, e.g. "+12%", or '' when there is nothing to compare with.
		 */
		private function format_change( $current, $previous ) {
			if ( $previous < 1 ) {
				return '';
			}

			$change = (int) round( 100 * ( $current - $previous ) / $previous );

			return ( $change > 0 ? '+' : '' ) . number_format_i18n( $change ) . '%';
		}

		public static function add_plugin_meta_links( $meta_fields, $file ) {
            if ( $file !== 'search-analytics/mwt-search-analytics.php' ) {
                return $meta_fields;
            }

            $meta_fields[] = "<a href='" . MWTSA_WORDPRESS_SUPPORT_URL . "' target='_blank'>" . esc_html__( 'Support Forum', 'search-analytics' ) . "</a>";
            $svg           = "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";

            $stars = '<i class="mwtsa-rate-stars">';

            for ( $i = 1; $i <= 5; $i ++ ) {
                $stars .= '<a href="' . MWTSA_WORDPRESS_SUPPORT_URL . '/reviews/?rate=' . $i . '#new-post" target="_blank">' . $svg . '</a>';
            }

            $stars .= '</i>';

            $meta_fields[] = $stars;

            echo "<style>"
                 . ".mwtsa-rate-stars{display:inline-block;color:#ffb900;position:relative;top:3px;}"
                 . ".mwtsa-rate-stars a {color:#ffb900;}"
                 . ".mwtsa-rate-stars a svg{fill:#ffb900;}"
                 . ".mwtsa-rate-stars a:hover svg{fill:#ffb900}"
                 . ".mwtsa-rate-stars a:hover ~ a svg {fill:none;}"
                 . "</style>";


			return $meta_fields;
		}
	}
}
<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Review_Notice' ) ) {

	/**
	 * Asks for a review once, on the plugin's own pages, when the plugin has been recording searches for a while.
	 */
	class MWTSA_Review_Notice {

		const USER_META        = 'mwtsa_review_notice';
		const QUERY_ARG        = 'mwtsa_review_notice';
		const MINIMUM_SEARCHES = 50;

		public function __construct() {
			add_action( 'admin_init', array( $this, 'save_answer' ) );
			add_action( 'admin_notices', array( $this, 'render' ) );
		}

		private function is_plugin_page() {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return in_array( $page, array( 'mwtsa-search-analytics', 'mwtsa-search-analytics-settings' ), true );
		}

		/**
		 * "later" asks again in 30 days; "review" and "done" never ask again.
		 */
		public function save_answer() {
			if ( ! isset( $_GET[ self::QUERY_ARG ] ) || ! $this->is_plugin_page() ) {
				return;
			}

			check_admin_referer( self::QUERY_ARG );

			$answer = sanitize_key( wp_unslash( $_GET[ self::QUERY_ARG ] ) );

			update_user_meta( get_current_user_id(), self::USER_META, 'later' === $answer ? time() + 30 * DAY_IN_SECONDS : 'done' );

			if ( 'review' === $answer ) {
				wp_redirect( MWTSA_WORDPRESS_SUPPORT_URL . '/reviews/#new-post' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- a fixed WordPress.org address.
				exit;
			}

			wp_safe_redirect( remove_query_arg( array( self::QUERY_ARG, '_wpnonce' ) ) );
			exit;
		}

		private function answer_url( $answer ) {
			return wp_nonce_url( add_query_arg( self::QUERY_ARG, $answer ), self::QUERY_ARG );
		}

		/**
		 * Not for a user who answered, and not in the first 3 days after an install or update.
		 */
		private function may_ask() {
			$answer = get_user_meta( get_current_user_id(), self::USER_META, true );

			if ( 'done' === $answer || ( is_numeric( $answer ) && time() < (int) $answer ) ) {
				return false;
			}

			$version = get_option( MWTSA_Install::VERSION_INFO );

			return is_array( $version ) && ! empty( $version['since'] ) && time() - (int) $version['since'] >= 3 * DAY_IN_SECONDS;
		}

		public function render() {
			if ( ! $this->is_plugin_page() || ! $this->may_ask() ) {
				return;
			}

			$stats = ( new MWTSA_History_Data() )->get_lifetime_stats();

			if ( $stats['searches'] < self::MINIMUM_SEARCHES || ! $stats['first_search'] || time() - $stats['first_search'] < WEEK_IN_SECONDS ) {
				return;
			}
			?>
            <div class="notice notice-info mwtsa-review-notice">
                <p>
                    <strong>
						<?php
						/* translators: 1: how long, e.g. "2 weeks", 2: number of searches, e.g. "5,000" */
						echo esc_html( sprintf( __( 'You\'ve used Search Analytics for %1$s, and it has recorded over %2$s searches on your site.', 'search-analytics' ), human_time_diff( $stats['first_search'] ), $this->approximate( $stats['searches'] ) ) );
						?>
                    </strong>
                </p>
                <p><?php esc_html_e( 'If it\'s been useful, would you leave a review on WordPress.org? It takes a minute and helps other site owners find the plugin.', 'search-analytics' ); ?></p>
                <p class="mwtsa-review-actions">
                    <a href="<?php echo esc_url( $this->answer_url( 'review' ) ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer" onclick="var notice = this.parentNode.parentNode; setTimeout( function () { notice.style.display = 'none'; }, 200 );"><?php esc_html_e( 'Leave a review', 'search-analytics' ); ?></a>
                    <a href="<?php echo esc_url( $this->answer_url( 'later' ) ); ?>" class="button"><?php esc_html_e( 'Maybe later', 'search-analytics' ); ?></a>
                    <a href="<?php echo esc_url( $this->answer_url( 'done' ) ); ?>"><?php esc_html_e( 'I already did', 'search-analytics' ); ?></a>
                </p>
                <a href="<?php echo esc_url( $this->answer_url( 'done' ) ); ?>" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'search-analytics' ); ?></span></a>
            </div>
			<?php
		}

		/**
		 * Rounds down to a number that reads easily: 5,372 gives "5,000", 12,480 "12,000" and 1,250,000 "1.2 million".
		 */
		private function approximate( $number ) {
			$step    = pow( 10, max( 0, strlen( (string) $number ) - ( $number < 10000 ? 1 : 2 ) ) );
			$rounded = (int) ( floor( $number / $step ) * $step );

			if ( $rounded >= 1000000 ) {
				/* translators: %s: a number of millions, e.g. "1.2" */
				return sprintf( __( '%s million', 'search-analytics' ), number_format_i18n( $rounded / 1000000, $rounded % 1000000 ? 1 : 0 ) );
			}

			return number_format_i18n( $rounded );
		}
	}
}

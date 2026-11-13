<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Deactivation_Feedback' ) ) {

	/**
	 * Asks why the plugin is being deactivated, on the Plugins screen. Answering is optional, and only an answer the
	 * user chooses to send goes to wpsearchanalytics.com.
	 */
	class MWTSA_Deactivation_Feedback {

		const ACTION      = 'mwtsa_deactivation_feedback';
		const SNOOZE_META = 'mwtsa_deactivation_feedback_snooze';
		const PRIVACY_URL = 'https://wpsearchanalytics.com/privacy';

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			// admin_footer runs before the footer scripts are printed; admin_footer-plugins.php runs after them.
			add_action( 'admin_footer', array( $this, 'render' ) );
			add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_answer' ) );
		}

		/**
		 * The answers that are sent. "temporary" is offered too, but nothing is sent for it.
		 */
		private static function get_reasons() {
			return array(
				'stopped-working'  => __( 'It stopped recording searches', 'search-analytics' ),
				'missing-searches' => __( 'It doesn\'t record the searches I need', 'search-analytics' ),
				'better-plugin'    => __( 'I found a better plugin', 'search-analytics' ),
				'no-longer-needed' => __( 'I no longer need it', 'search-analytics' ),
				'broke-site'       => __( 'It broke or slowed down my site', 'search-analytics' ),
			);
		}

		private function should_ask() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			$snoozed_until = (int) get_user_meta( get_current_user_id(), self::SNOOZE_META, true );

			return $snoozed_until < time();
		}

		public function enqueue( $hook ) {
			if ( 'plugins.php' !== $hook || ! $this->should_ask() ) {
				return;
			}

			wp_enqueue_style( 'mwtsa-deactivation-feedback', MWTSAI()->plugin_admin_url . 'assets/css/deactivation-feedback.css', array(), MWTSAI()->version );
			wp_enqueue_script( 'mwtsa-deactivation-feedback', MWTSAI()->plugin_admin_url . 'assets/js/deactivation-feedback.js', array(), MWTSAI()->version, true );
			wp_localize_script( 'mwtsa-deactivation-feedback', 'mwtsaDeactivationFeedback', array(
				'plugin'  => MWTSAI()->plugin_basename,
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
				'labels'  => array(
					'skip'    => __( 'Skip & Deactivate', 'search-analytics' ),
					'send'    => __( 'Send & Deactivate', 'search-analytics' ),
					'plain'   => __( 'Deactivate', 'search-analytics' ),
					'sending' => __( 'Deactivating…', 'search-analytics' ),
				),
			) );
		}

		private function render_support_box() {
			?>
            <div class="mwtsa-df-support">
                <p><?php esc_html_e( 'Sorry about that. Ask in the support forum and we\'ll help you get it working.', 'search-analytics' ); ?></p>
                <a class="button button-small" href="<?php echo esc_url( MWTSA_WORDPRESS_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get help in the support forum', 'search-analytics' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'search-analytics' ); ?></span></a>
            </div>
			<?php
		}

		private function render_details_field( $reason, $label, $placeholder = '' ) {
			$id = 'mwtsa-df-details-' . $reason;
			?>
            <div class="mwtsa-df-details">
                <label for="<?php echo esc_attr( $id ); ?>"<?php echo $placeholder ? ' class="screen-reader-text"' : ''; ?>><?php echo esc_html( $label ); ?></label>
                <input type="text" id="<?php echo esc_attr( $id ); ?>" name="details" maxlength="1000" placeholder="<?php echo esc_attr( $placeholder ); ?>" disabled>
            </div>
			<?php
		}

		public function render() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			if ( ! $screen || 'plugins' !== $screen->base || ! $this->should_ask() ) {
				return;
			}

			$reasons = self::get_reasons() + array(
				'temporary' => __( 'It\'s temporary, I\'m troubleshooting an issue', 'search-analytics' ),
				'other'     => __( 'Other', 'search-analytics' ),
			);
			?>
            <dialog id="mwtsa-df" class="mwtsa-df" aria-labelledby="mwtsa-df-title">
                <form method="dialog">
                    <div class="mwtsa-df-header"><?php esc_html_e( 'Quick feedback', 'search-analytics' ); ?></div>
                    <div class="mwtsa-df-body">
                        <fieldset>
                            <legend id="mwtsa-df-title"><?php esc_html_e( 'If you have a moment, tell us why you are deactivating Search Analytics:', 'search-analytics' ); ?></legend>
							<?php foreach ( $reasons as $reason => $label ) : ?>
                                <div class="mwtsa-df-reason" data-reason="<?php echo esc_attr( $reason ); ?>">
                                    <label><input type="radio" name="reason" value="<?php echo esc_attr( $reason ); ?>"> <?php echo esc_html( $label ); ?></label>
                                    <div class="mwtsa-df-more" hidden>
										<?php
										if ( 'stopped-working' === $reason || 'broke-site' === $reason ) {
											$this->render_support_box();
										} elseif ( 'missing-searches' === $reason ) {
											$this->render_details_field( $reason, __( 'Which searches?', 'search-analytics' ), __( 'Which searches? For example, a live search plugin', 'search-analytics' ) );
										} elseif ( 'better-plugin' === $reason ) {
											$this->render_details_field( $reason, __( 'What\'s the plugin\'s name?', 'search-analytics' ), __( 'What\'s the plugin\'s name?', 'search-analytics' ) );
										} elseif ( 'other' === $reason ) {
											$this->render_details_field( $reason, __( 'Tell us the reason, so we can improve the plugin.', 'search-analytics' ) );
										}
										?>
                                    </div>
                                </div>
							<?php endforeach; ?>
                        </fieldset>
                        <p class="mwtsa-df-note">
							<?php esc_html_e( 'Sending shares your answer, the plugin, WordPress and PHP versions and the site language with wpsearchanalytics.com. Not your site address or your search data.', 'search-analytics' ); ?>
                            <a href="<?php echo esc_url( self::PRIVACY_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy policy', 'search-analytics' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'search-analytics' ); ?></span></a>
                        </p>
                    </div>
                    <div class="mwtsa-df-footer">
                        <label class="mwtsa-df-snooze" hidden><input type="checkbox" name="snooze" value="1"> <?php esc_html_e( 'Don\'t ask again for 24 hours', 'search-analytics' ); ?></label>
                        <button type="submit" class="button mwtsa-df-submit" value="deactivate"><?php esc_html_e( 'Skip & Deactivate', 'search-analytics' ); ?></button>
                        <button type="button" class="button mwtsa-df-cancel"><?php esc_html_e( 'Cancel', 'search-analytics' ); ?></button>
                    </div>
                </form>
            </dialog>
			<?php
		}

		/**
		 * Sends the answer to wpsearchanalytics.com. The page deactivates the plugin whatever the result, so this never
		 * waits more than a few seconds and doesn't report failures.
		 */
		public function handle_answer() {
			check_ajax_referer( self::ACTION );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( null, 403 );
			}

			$reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';

			if ( 'temporary' === $reason ) {
				if ( ! empty( $_POST['snooze'] ) ) {
					update_user_meta( get_current_user_id(), self::SNOOZE_META, time() + DAY_IN_SECONDS );
				}

				wp_send_json_success();
			}

			$details = isset( $_POST['details'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) ) : '';
			$details = function_exists( 'mb_substr' ) ? mb_substr( $details, 0, 1000 ) : substr( $details, 0, 1000 );

			if ( ! array_key_exists( $reason, self::get_reasons() ) && ! ( 'other' === $reason && '' !== $details ) ) {
				wp_send_json_error( null, 400 );
			}

			wp_remote_post( MWTSA_FEEDBACK_URL, array(
				'timeout'    => 3,
				// WordPress's default user agent contains the site address, which this doesn't send.
				'user-agent' => 'SearchAnalyticsWP/' . MWTSAI()->version,
				'headers'    => array( 'Content-Type' => 'application/json' ),
				'body'       => wp_json_encode( array(
					'reason'         => $reason,
					'details'        => $details,
					'plugin_version' => MWTSAI()->version,
					'wp_version'     => get_bloginfo( 'version' ),
					'php_version'    => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
					'locale'         => get_locale(),
				) ),
			) );

			wp_send_json_success();
		}
	}
}

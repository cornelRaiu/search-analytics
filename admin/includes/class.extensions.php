<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Admin_Extensions' ) ) {

	/**
	 * The Extensions page: free plugins by the same author that work alongside Search Analytics.
	 */
	class MWTSA_Admin_Extensions {

		const PAGE = 'mwtsa-search-analytics-extensions';

		private static function get_search_steer_icon() {
			$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">'
				. '<rect width="256" height="256" rx="56" fill="#0e2a47"/>'
				. '<circle cx="108" cy="108" r="64" fill="#fff" fill-opacity="0.08" stroke="#fff" stroke-width="22"/>'
				. '<path d="M156 156 204 204" stroke="#fff" stroke-width="28" stroke-linecap="round"/>'
				. '<g transform="rotate(45 108 108)"><path d="M108 60 123 108H93z" fill="#ffb547"/><path d="M108 156 123 108H93z" fill="#fff" fill-opacity="0.55"/><circle cx="108" cy="108" r="6" fill="#0e2a47"/></g>'
				. '</svg>';

			return 'data:image/svg+xml,' . rawurlencode( $svg );
		}

		private static function get_search_steer_buttons() {
			$state   = MWTSA_Search_Steer::get_state();
			$buttons = array();

			if ( 'active' === $state ) {
				$buttons[] = '<button type="button" class="button button-disabled" disabled="disabled">' . esc_html__( 'Active', 'search-analytics' ) . '</button>';

				if ( current_user_can( 'manage_options' ) ) {
					$buttons[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=mwtss-settings' ) ), esc_html__( 'Settings', 'search-analytics' ) );
				}
			} elseif ( 'inactive' === $state && MWTSA_Search_Steer::can_activate() ) {
				$buttons[] = sprintf( '<a class="button button-primary" href="%s">%s</a>', esc_url( MWTSA_Search_Steer::activate_url() ), esc_html__( 'Activate', 'search-analytics' ) );
			} elseif ( 'missing' === $state && MWTSA_Search_Steer::can_install() ) {
				$buttons[] = sprintf( '<a class="button" href="%s">%s</a>', esc_url( MWTSA_Search_Steer::install_url() ), esc_html__( 'Install Now', 'search-analytics' ) );
			} elseif ( 'unsupported' === $state ) {
				$buttons[] = '<button type="button" class="button button-disabled" disabled="disabled">' . esc_html__( 'Cannot Install', 'search-analytics' ) . '</button>';
			}

			$buttons[] = MWTSA_Search_Steer::details_link( __( 'More Details', 'search-analytics' ) );

			return $buttons;
		}

		public static function render() {
			?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Extensions', 'search-analytics' ); ?></h1>
                <p><?php esc_html_e( 'Free plugins by the same author that work alongside Search Analytics.', 'search-analytics' ); ?></p>

                <div class="wp-list-table widefat plugin-install">
                    <div id="the-list">
                        <div class="plugin-card plugin-card-mwt-search-steer">
                            <div class="plugin-card-top">
                                <div class="name column-name">
                                    <h3>
                                        MWT Search Steer
                                        <img src="<?php echo esc_attr( self::get_search_steer_icon() ); ?>" class="plugin-icon" alt="">
                                    </h3>
                                </div>
                                <div class="action-links">
                                    <ul class="plugin-action-buttons">
										<?php foreach ( self::get_search_steer_buttons() as $button ) : ?>
                                            <li><?php echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built ?></li>
										<?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="desc column-description">
                                    <p><?php esc_html_e( 'Give a post search keywords, and WordPress search shows it for those words, even when they are not in the post. Stop keywords keep a post out of some searches, and any post can be hidden from search.', 'search-analytics' ); ?></p>
                                    <p><?php esc_html_e( 'It works on its own. Search Analytics shows which searches found nothing, and Search Steer makes them find the right post.', 'search-analytics' ); ?></p>
									<?php if ( 'unsupported' === MWTSA_Search_Steer::get_state() ) : ?>
                                        <p><em><?php esc_html_e( 'It needs WordPress 6.6 and PHP 7.4 or later.', 'search-analytics' ); ?></em></p>
									<?php endif; ?>
                                    <p class="authors"><cite><?php esc_html_e( 'By Cornel Raiu', 'search-analytics' ); ?></cite></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}
	}
}

<?php
defined( "ABSPATH" ) || exit;

if ( ! class_exists( 'MWTSA_Pro_Waitlist' ) ) {

	/**
	 * The sidebar box on the statistics and settings pages that links to the Pro waitlist.
	 */
	class MWTSA_Pro_Waitlist {

		public static function render() {
			?>
            <div class="col-content mwtsa-pro">
                <div class="mwtsa-pro-heading">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="36" height="36" aria-hidden="true" focusable="false">
                        <rect width="256" height="256" rx="56" fill="#14213D"/>
                        <rect x="56" y="52" width="144" height="28" rx="14" fill="#FFFFFF"/>
                        <rect x="56" y="98" width="104" height="28" rx="14" fill="#FFFFFF" fill-opacity="0.7"/>
                        <rect x="59.5" y="147.5" width="45" height="21" rx="10.5" fill="none" stroke="#F2A541" stroke-width="7"/>
                        <circle cx="152" cy="158" r="22" fill="none" stroke="#F2A541" stroke-width="12"/>
                        <line x1="168" y1="174" x2="194" y2="200" stroke="#F2A541" stroke-width="14" stroke-linecap="round"/>
                    </svg>
                    <h3><?php esc_html_e( 'Search Analytics Pro', 'search-analytics' ) ?></h3>
                </div>
                <p><?php esc_html_e( 'A Pro version is in the works, with paid features on top of the free plugin: email reports, alerts for searches with no results, WooCommerce search insights and more.', 'search-analytics' ) ?></p>
                <p><?php esc_html_e( 'Pick the ones you would pay for. The most picked get built first, and everyone on the waitlist gets founding-member pricing.', 'search-analytics' ) ?></p>
                <p>
                    <a class="button button-primary" href="<?php echo esc_url( MWTSA_PRO_WAITLIST_URL ) ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Join the waitlist', 'search-analytics' ) ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'search-analytics' ); ?></span><span class="dashicons dashicons-external" aria-hidden="true"></span></a>
                </p>
                <p class="mwtsa-pro-note"><?php esc_html_e( 'The free plugin stays free.', 'search-analytics' ) ?></p>
            </div>
			<?php
		}
	}
}

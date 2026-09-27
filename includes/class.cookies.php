<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Cookies' ) ) {

    class MWTSA_Cookies {

        /**
         * Drops the searches whose duplicate interval has passed. The cookie is pruned whenever it is written, so it
         * stays small without being re-sent on every request (which also kept pages out of full-page caches).
         */
        public static function remove_expired_searches( $cookie, $interval_minutes ) {
            if ( empty( $cookie['search'] ) ) {
                return $cookie;
            }

            foreach ( $cookie['search'] as $term_id => $time ) {
                if ( ( $time + ( 60 * (int) $interval_minutes ) ) < time() ) {
                    unset( $cookie['search'][ $term_id ] );
                }
            }

            return $cookie;
        }

        /**
         * No longer hooked on every request; kept for code that calls it. Only sends the cookie if something changed.
         */
        public static function clear_expired_search_history() {
            $current_user_cookie = self::get_cookie_value();

            if ( empty( $current_user_cookie['search'] ) ) {
                return;
            }

            $cleaned = self::remove_expired_searches( $current_user_cookie, MWTSA_Options::get_option( 'mwtsa_exclude_doubled_search_for_interval' ) );

            if ( $cleaned !== $current_user_cookie ) {
                self::set_cookie_value( $cleaned, ( 86400 * 7 ) );
            }
        }

        public static function set_is_excluded_cookie_if_needed( $user_login, $user ) {

            $exclude_search_for_roles_after_logout = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role_after_logout' );
            $exclude_search_for_roles              = MWTSA_Options::get_option( 'mwtsa_exclude_search_for_role' );
            $current_user_roles                    = mwtsa_get_user_roles( $user );

            if ( ! is_array( $exclude_search_for_roles ) || empty( $exclude_search_for_roles_after_logout ) ) {
                return;
            }


            $matching_roles = array_intersect( $exclude_search_for_roles, $current_user_roles );

            if ( count( $matching_roles ) === 0 ) {
                return;
            }

            $current_user_cookie                = self::get_cookie_value();
            $current_user_cookie['is_excluded'] = 1;

            self::set_cookie_value( $current_user_cookie, ( 86400 * 7 ) ); //expire in 7 days
            //TODO: maybe make the number of days a setting?
        }

        /**
         * The cookie comes from the visitor, so only the fields and types the plugin writes are kept.
         */
        public static function get_cookie_value() {
            $cookie_name = MWTSAI()->cookie_name;

            if ( empty( $_COOKIE[ $cookie_name ] ) || ! is_string( $_COOKIE[ $cookie_name ] ) ) {
                return array();
            }

            $value = json_decode( wp_unslash( $_COOKIE[ $cookie_name ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, validated field by field below.

            if ( ! is_array( $value ) ) {
                return array();
            }

            $cookie = array();

            if ( ! empty( $value['is_excluded'] ) ) {
                $cookie['is_excluded'] = 1;
            }

            if ( isset( $value['search'] ) && is_array( $value['search'] ) ) {
                $cookie['search'] = array();

                foreach ( $value['search'] as $term_id => $time ) {
                    if ( is_numeric( $term_id ) && is_numeric( $time ) ) {
                        $cookie['search'][ absint( $term_id ) ] = absint( $time );
                    }
                }
            }

            return $cookie;
        }

        public static function set_cookie_value( $value, $expire_delay = MONTH_IN_SECONDS ) {
            // A cookie can't be sent once the page has started printing, e.g. from a hook that fires mid-page.
            if ( headers_sent() ) {
                return;
            }

            $name   = MWTSAI()->cookie_name;
            $value  = wp_json_encode( $value );
            $expire = time() + $expire_delay;

            // Only the server reads this cookie, so keep it away from scripts and cross-site requests.
            if ( PHP_VERSION_ID >= 70300 ) {
                setcookie( $name, $value, array(
                    'expires'  => $expire,
                    'path'     => COOKIEPATH,
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ) );
            } else {
                setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
            }
        }
    }
}

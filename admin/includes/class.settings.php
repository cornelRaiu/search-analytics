<?php
defined("ABSPATH") || exit;

if ( ! class_exists( 'MWTSA_Admin_Settings' ) ) {

    class MWTSA_Admin_Settings {

        public $existing_options;

        public function __construct() {
            $this->existing_options = MWTSA_Options::get_options();

            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'mwtsa_settings_init' ) );
        }

        public function add_admin_menu() {

            $this_user_role = mwt_get_current_user_roles();

            $accepted_user_roles = array_values( array_intersect( $this_user_role, $this->existing_options['mwtsa_display_settings_for_role'] ) );

            if ( ! isset( $this->existing_options['mwtsa_display_settings_for_role'] ) || ! empty( $accepted_user_roles ) ) {

                add_options_page(
                    __( 'MWT: Search Analytics', 'search-analytics' ),
                    __( 'MWT: Search Analytics', 'search-analytics' ),
                    $accepted_user_roles[0], 'search-analytics', array(
                        &$this,
                        'options_page'
                    )
                );

            }
        }

        public function mwtsa_settings_init() {

            register_setting( 'mwtsa_general_options', MWTSAI()->main_option_name );

            add_settings_section(
                'mwtsa_general_options_section',
                __( 'Search Filtering Settings', 'search-analytics' ),
                array( &$this, 'settings_section_callback' ),
                'mwtsa_general_options'
            );

            add_settings_field(
                'mwtsa_exclude_search_for_role',
                __( 'Ignore search queries for these user roles:', 'search-analytics' ),
                array( &$this, 'field_exclude_search_for_role_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_field(
                'mwtsa_exclude_search_for_role_after_logout',
                __( 'Ignore search queries for the above user roles even after the user has logged out:', 'search-analytics' ),
                array( &$this, 'field_exclude_search_for_role_after_logout_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_field(
                'mwtsa_exclude_doubled_search_for_interval',
                __( 'Exclude doubled search for same user if interval is lower than ( minutes ):', 'search-analytics' ),
                array( &$this, 'field_exclude_doubled_search_for_interval_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_field(
                'mwtsa_exclude_searches_from_ip_addresses',
                __( 'Exclude searches made from the following IP addresses:', 'search-analytics' ),
                array( &$this, 'field_exclude_searches_from_ip_addresses_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_field(
                'mwtsa_minimum_characters',
                __( 'Only record searches with at least the number of characters', 'search-analytics' ),
                array( &$this, 'field_minimum_characters_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_field(
                'mwtsa_exclude_if_string_contains',
                __( 'Exclude search in case it contains certain substring:', 'search-analytics' ),
                array( &$this, 'field_exclude_if_string_contains_render' ),
                'mwtsa_general_options',
                'mwtsa_general_options_section'
            );

            add_settings_section(
                'mwtsa_display_options_sections',
                __( 'General Settings', 'search-analytics' ),
                array( &$this, 'settings_section_callback' ),
                'mwtsa_general_options'
            );

            add_settings_field(
                'mwtsa_display_settings_for_role',
                __( 'Only display the settings page for these user roles:', 'search-analytics' ),
                array( &$this, 'field_display_settings_for_role_render' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_display_stats_for_role',
                __( 'Only display the statistics page for these user roles:', 'search-analytics' ),
                array( &$this, 'field_display_stats_for_role_render' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_hide_charts',
                __( 'Hide Charts', 'search-analytics' ),
                array( &$this, 'field_hide_charts' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_save_search_country',
                __( 'Save Search Country', 'search-analytics' ),
                array( &$this, 'field_save_search_country' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_custom_search_url_params',
                __( 'Add custom search parameters for recording the searches:', 'search-analytics' ),
                array( &$this, 'field_custom_search_url_params_render' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_save_search_by_user',
                __( 'Save Search By User', 'search-analytics' ),
                array( &$this, 'field_save_search_by_user' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

            add_settings_field(
                'mwtsa_uninstall',
                __( 'Uninstall', 'search-analytics' ),
                array( &$this, 'field_uninstall' ),
                'mwtsa_general_options',
                'mwtsa_display_options_sections'
            );

        }

        public function field_exclude_search_for_role_render() {
            $roles = get_editable_roles();

            if ( ! empty( $roles ) ):
                foreach ( $roles as $role_key => $role ) :
                    ?>
                    <label><input type='checkbox' name='mwtsa_settings[mwtsa_exclude_search_for_role][]' value='<?php echo $role_key ?>' <?php checked( in_array( $role_key, $this->existing_options['mwtsa_exclude_search_for_role'] ), 1 ) ?> /> <?php echo $role['name'] ?>
                    </label><br/>
                <?php
                endforeach;
            endif;
        }

        public function field_exclude_search_for_role_after_logout_render() {
            global $mwtsa;

            if ( ! isset( $this->existing_options['mwtsa_exclude_search_for_role_after_logout'] ) ) {
                $this->existing_options['mwtsa_exclude_search_for_role_after_logout'] = 0;
            }

            ?>
            <label><input type='checkbox' name='mwtsa_settings[mwtsa_exclude_search_for_role_after_logout]' value='1' <?php checked( $this->existing_options['mwtsa_exclude_search_for_role_after_logout'], 1 ) ?> />
                <br/><strong><?php _e( 'Note: this will set a cookie in the browser of the user who logged in and has one of the user roles checked above.<br />This needs to be treated by the site\'s GDPR terms in case it is active for public user roles ( e.g. Subscriber, Client )<br />The cookie name is: ', 'search-analytics' ) ?><?php echo $mwtsa->cookie_name ?></strong></label>
            <br/>
            <?php
        }

        public function field_exclude_doubled_search_for_interval_render() {
            global $mwtsa;

            if ( ! isset( $this->existing_options['mwtsa_exclude_doubled_search_for_interval'] ) ) {
                $this->existing_options['mwtsa_exclude_doubled_search_for_interval'] = 0;
            }

            ?>
            <input type="number" min="0" name="mwtsa_settings[mwtsa_exclude_doubled_search_for_interval]" value="<?php echo $this->existing_options['mwtsa_exclude_doubled_search_for_interval'] ?>"/> <?php _e( '( Note: set to 0 or leave empty to disable it )', 'search-analytics' ) ?>
            <br/>
            <strong><?php _e( 'Note: this will set a cookie in the browser of the user who made any kind of search on the website.<br />This needs to be treated by the site\'s GDPR terms in case it\'s value is a number larger than 0<br />The cookie name is: ', 'search-analytics' ) ?><?php echo $mwtsa->cookie_name ?></strong>
            <?php
        }

        public function field_exclude_searches_from_ip_addresses_render() {
            if ( ! isset( $this->existing_options['mwtsa_exclude_searches_from_ip_addresses'] ) ) {
                $this->existing_options['mwtsa_exclude_searches_from_ip_addresses'] = '';
            }

            $admin_ip = isset( $_SERVER['HTTP_CLIENT_IP'] ) ? $_SERVER['HTTP_CLIENT_IP'] : ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
            ?>
            <input type="text" name="mwtsa_settings[mwtsa_exclude_searches_from_ip_addresses]" value="<?php echo $this->existing_options['mwtsa_exclude_searches_from_ip_addresses'] ?>" placeholder="eg. 127.0.0.1"/> <?php _e( '( Note: separate IP values by comma )', 'search-analytics' ) ?>
            <br/>

            <strong><?php _e( 'Your IP address is: ' . $admin_ip, 'search-analytics' ) ?></strong>
            <?php
        }

        public function field_minimum_characters_render() {

            if ( ! isset( $this->existing_options['mwtsa_minimum_characters'] ) ) {
                $this->existing_options['mwtsa_minimum_characters'] = 0;
            }
            ?>
            <input type="number" min="0" name="mwtsa_settings[mwtsa_minimum_characters]" value="<?php echo $this->existing_options['mwtsa_minimum_characters'] ?>"/> <?php _e( '( Note: set to 0 or leave empty to disable it )', 'search-analytics' ) ?>
            <br/>
            <?php
        }

        public function field_exclude_if_string_contains_render() {

            if ( ! isset( $this->existing_options['mwtsa_exclude_if_string_contains'] ) ) {
                $this->existing_options['mwtsa_exclude_if_string_contains'] = '';
            }

            ?>
            <input type="text" name="mwtsa_settings[mwtsa_exclude_if_string_contains]" value="<?php echo $this->existing_options['mwtsa_exclude_if_string_contains']; ?>" placeholder="eg. text, another one"/> <?php _e( '( Note: enter comma (,) separated strings )', 'search-analytics' ) ?>
            <?php
        }

        public function field_custom_search_url_params_render() {

            if ( ! isset( $this->existing_options['mwtsa_custom_search_url_params'] ) ) {
                $this->existing_options['mwtsa_custom_search_url_params'] = '';
            }

            ?>
            <input type="text" name="mwtsa_settings[mwtsa_custom_search_url_params]" value="<?php echo $this->existing_options['mwtsa_custom_search_url_params']; ?>" placeholder="eg. wpv_post_search"/> <?php _e( '( Note: enter comma (,) separated strings )', 'search-analytics' ) ?>
            <?php
        }

        public function field_display_settings_for_role_render() {
            $roles = get_editable_roles();

            if ( ! empty( $roles ) ) :
                foreach ( $roles as $role_key => $role ) :
                    $is_readonly = ( 'administrator' == $role_key );
                    $is_checked = ! isset( $this->existing_options['mwtsa_display_settings_for_role'] ) || in_array( $role_key, $this->existing_options['mwtsa_display_settings_for_role'] ) || $is_readonly;
                    ?>
                    <label><input type='checkbox' name='mwtsa_settings[mwtsa_display_settings_for_role][]' value='<?php echo $role_key ?>' <?php checked( $is_checked ) ?> <?php if ( $is_readonly ) {
                            echo 'onclick="return false;"';
                        } ?> /> <?php echo $role['name'] ?></label><br/>
                <?php
                endforeach;
            endif;
        }

        public function field_display_stats_for_role_render() {
            $roles              = get_editable_roles();

            if ( ! empty( $roles ) ):
                foreach ( $roles as $role_key => $role ) :
                    $is_readonly = ( 'administrator' == $role_key );
                    $is_checked = ! isset( $this->existing_options['mwtsa_display_stats_for_role'] ) || in_array( $role_key, $this->existing_options['mwtsa_display_stats_for_role'] ) || $is_readonly;
                    ?>
                    <label><input type='checkbox' name='mwtsa_settings[mwtsa_display_stats_for_role][]' value='<?php echo $role_key ?>' <?php checked( $is_checked ) ?> <?php if ( $is_readonly ) {
                            echo 'onclick="return false;"';
                        } ?> /> <?php echo $role['name'] ?></label><br/>
                <?php
                endforeach;
            endif;
        }

        public function field_uninstall() {
            if ( ! isset( $this->existing_options['mwtsa_uninstall'] ) ) {
                $this->existing_options['mwtsa_uninstall'] = 0;
            }
            ?>
            <label><input type='checkbox' name='mwtsa_settings[mwtsa_uninstall]' value='1' <?php checked( $this->existing_options['mwtsa_uninstall'], 1 ) ?> /> <?php _e( 'Remove plugin tables on uninstall', 'search-analytics' ) ?>
            </label><br/>
            <?php
        }

        public function field_hide_charts() {
            if ( ! isset( $this->existing_options['mwtsa_hide_charts'] ) ) {
                $this->existing_options['mwtsa_hide_charts'] = 0;
            }
            ?>
            <label><input type='checkbox' name='mwtsa_settings[mwtsa_hide_charts]' value='1' <?php checked( $this->existing_options['mwtsa_hide_charts'], 1 ) ?> /> <?php _e( 'Hide graphical charts for representing statistics', 'search-analytics' ) ?>
            </label><br/>
            <?php
        }

        public function field_save_search_country() {
            if ( ! isset( $this->existing_options['mwtsa_save_search_country'] ) ) {
                $this->existing_options['mwtsa_save_search_country'] = 0;
            }
            ?>
            <label><input type='checkbox' name='mwtsa_settings[mwtsa_save_search_country]' value='1' <?php checked( $this->existing_options['mwtsa_save_search_country'], 1 ) ?> /> <?php _e( 'Save the country from where the search was launched', 'search-analytics' ) ?>
            </label><br/>
            <strong><?php _e( 'NOTE: this uses the <a href="https://ip-api.com">https://ip-api.com</a> JSON service which is limited to 150 requests per minute. In case you have more than 150 searches per minute on the website, please uncheck this checkbox. <br />In case the site\'s IP got banned, you can go here: <a href="https://ip-api.com/docs/unban">https://ip-api.com/docs/unban</a> and remove the ban.<br />A future version of Search Analytics will come with support for the PRO service of IP-API.com<br /><br />Disclaimer: I am not associated with the IP-API.com service in any way. I am just using it for providing you a way of finding out where the users search content from on your website.', 'search-analytics' ) ?></strong>
            <?php
        }

        public function field_save_search_by_user() {
            if ( ! isset( $this->existing_options['mwtsa_save_search_by_user'] ) ) {
                $this->existing_options['mwtsa_save_search_by_user'] = 0;
            }
            ?>
            <label><input type='checkbox' name='mwtsa_settings[mwtsa_save_search_by_user]' value='1' <?php checked( $this->existing_options['mwtsa_save_search_by_user'], 1 ) ?> /> <?php _e( 'Save the user id of the user who launched the search', 'search-analytics' ) ?>
            </label><br/>
            <strong><?php _e( 'Using this feature will allow you to see which of your registered users searched things on the site.', 'search-analytics' ) ?></strong>
            <?php
        }

        public function settings_section_callback() {

            if ( isset( $_POST['mwtsa_erase_data'] ) && check_admin_referer( 'mwtsa-erase-data' ) ) {
                $this->erase_history();
                $this->data_erased_notice();
            }

            if ( isset( $_POST['mwtsa_erase_old_data'] ) && ! empty( $_POST['mwtsa_data_older_than_days'] ) && check_admin_referer( 'mwtsa-erase-data' ) ) {
                $this->erase_history( absint( $_POST['mwtsa_data_older_than_days'] ) );
                $this->data_erased_notice();
            }

        }

        public function options_page() {

            ?>
            <form action='options.php' method='post'>
                <?php
                settings_fields( 'mwtsa_general_options' );
                do_settings_sections( 'mwtsa_general_options' );
                submit_button();
                ?>
            </form>
            <?php

            $this->erase_history_form();
        }

        function erase_history_form() {
            ?>
            <h3><?php _e( 'Erase History', 'search-analytics' ) ?></h3>

            <table class="form-table erase-history-table">
                <tbody>
                <tr>
                    <th scope="row">Delete all data</th>
                    <td>
                        <form action="" method="post">
                            <?php wp_nonce_field( 'mwtsa-erase-data' ); ?>
                            <p class="submit">
                                <input name="mwtsa_erase_data" class="button-secondary" value="<?php esc_attr_e( 'Erase All Data', 'search-analytics' ) ?>" type="submit" onclick="return confirm( '<?php _e( 'Are you sure you want to delete all data?\n\nClick `OK` to proceed.' ) ?>');"/><br/>
                                <strong><?php _e( 'Warning! Clicking this button will delete all historical search data', 'search-analytics' ) ?></strong>
                            </p>
                        </form>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Delete data older than</th>
                    <td>
                        <form action="" method="post">
                            <?php wp_nonce_field( 'mwtsa-erase-data' ); ?>
                            <p class="submit">
                                <input type="number" name="mwtsa_data_older_than_days" value="90"/> <?php _e( 'days', 'search-analytics' ) ?> &nbsp;
                                <input name="mwtsa_erase_old_data" class="button-secondary" value="<?php esc_attr_e( 'Erase Data', 'search-analytics' ) ?>" type="submit" onclick="return confirm( '<?php _e( 'Are you sure you want to delete the selected data?\n\nClick `OK` to proceed.' ) ?>');"/>
                            </p>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        function erase_history( $older_than = 0 ) {
            global $wpdb, $mwtsa;

            if ( $older_than == 0 ) {
                $wpdb->query( "TRUNCATE `$mwtsa->history_table_name`" );
                $wpdb->query( "TRUNCATE `$mwtsa->terms_table_name`" );
            } else {

                try {
                    $_temp_date = new DateTime( 'today' );
                    $_temp_date->sub( new DateInterval( 'P' . $older_than . 'D' ) );
                    $older_than_datetime = $_temp_date->format( 'Y-m-d H:i:s' );

                    $wpdb->query( $wpdb->prepare( "DELETE FROM `$mwtsa->history_table_name` WHERE `datetime` < %s", $older_than_datetime ) );

                    //TODO: delete recorded terms that no longer have at least 1 entry in the history table ?
                } catch ( Exception $e ) {
                }

            }
        }

        function data_erased_notice() {
            ?>
            <div class="notice updated mwtsa-notice is-dismissible">
                <p><?php _e( 'Historical data successfully erased!', 'search-analytics' ); ?></p>
            </div>
            <?php
        }
    }

}

return new MWTSA_Admin_Settings();
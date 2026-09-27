<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class.uninstall.php';

MWTSA_Uninstall::uninstall();

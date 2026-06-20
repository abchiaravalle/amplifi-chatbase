<?php
/**
 * Uninstall handler: remove plugin options.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'amplifi_chatbase_settings' );

// Clean up any rate-limit transients (best-effort).
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_amplifi\_cb\_rl\_%' OR option_name LIKE '\_transient\_timeout\_amplifi\_cb\_rl\_%'" ); // phpcs:ignore WordPress.DB

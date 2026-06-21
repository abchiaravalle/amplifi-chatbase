<?php
/**
 * Plugin Name:       Amplifi Chatbase
 * Plugin URI:        https://github.com/abchiaravalle/amplifi-chatbase
 * Description:       An elegant, iMessage-style alternate front end for Chatbase. Provides a hero prompt box, an inline chat window, and a glassy popup modal. Fully customizable colors, auto light/dark, and per-shortcode overrides. Your API key never touches the browser.
 * Version:           1.0.0-alpha.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Amplifi
 * Author URI:        https://amplifi.studio
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       amplifi-chatbase
 * Domain Path:       /languages
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'AMPLIFI_CHATBASE_VERSION', '1.0.0-alpha.1' );
define( 'AMPLIFI_CHATBASE_FILE', __FILE__ );
define( 'AMPLIFI_CHATBASE_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMPLIFI_CHATBASE_URL', plugin_dir_url( __FILE__ ) );
define( 'AMPLIFI_CHATBASE_BASENAME', plugin_basename( __FILE__ ) );
define( 'AMPLIFI_CHATBASE_OPTION', 'amplifi_chatbase_settings' );

require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase.php';

/**
 * Boot the plugin.
 *
 * @return Amplifi_Chatbase
 */
function amplifi_chatbase() {
	return Amplifi_Chatbase::instance();
}

amplifi_chatbase();

register_activation_hook( __FILE__, array( 'Amplifi_Chatbase', 'activate' ) );

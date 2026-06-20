<?php
/**
 * Core loader.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class. Wires up settings, shortcodes, assets, and the REST proxy.
 */
final class Amplifi_Chatbase {

	/**
	 * Singleton instance.
	 *
	 * @var Amplifi_Chatbase|null
	 */
	private static $instance = null;

	/**
	 * Settings handler.
	 *
	 * @var Amplifi_Chatbase_Settings
	 */
	public $settings;

	/**
	 * Shortcodes handler.
	 *
	 * @var Amplifi_Chatbase_Shortcodes
	 */
	public $shortcodes;

	/**
	 * Assets handler.
	 *
	 * @var Amplifi_Chatbase_Assets
	 */
	public $assets;

	/**
	 * REST proxy handler.
	 *
	 * @var Amplifi_Chatbase_Rest
	 */
	public $rest;

	/**
	 * Get the singleton.
	 *
	 * @return Amplifi_Chatbase
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init();
	}

	/**
	 * Load dependencies.
	 */
	private function includes() {
		require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase-settings.php';
		require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase-assets.php';
		require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase-shortcodes.php';
		require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase-rest.php';
	}

	/**
	 * Instantiate components and hook things up.
	 */
	private function init() {
		$this->settings   = new Amplifi_Chatbase_Settings();
		$this->assets     = new Amplifi_Chatbase_Assets( $this->settings );
		$this->shortcodes = new Amplifi_Chatbase_Shortcodes( $this->settings, $this->assets );
		$this->rest       = new Amplifi_Chatbase_Rest( $this->settings );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_footer', array( $this, 'render_global_markup' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'amplifi-chatbase', false, dirname( AMPLIFI_CHATBASE_BASENAME ) . '/languages' );
	}

	/**
	 * Render the shared modal container and (optionally) the floating launcher.
	 * These live once per page so any shortcode can drive them.
	 */
	public function render_global_markup() {
		if ( is_admin() ) {
			return;
		}
		$opts = $this->settings->get();

		// Only emit if a chatbot is configured.
		if ( empty( $opts['chatbot_id'] ) ) {
			return;
		}

		$this->assets->mark_needed();

		// Shared modal shell (hidden until opened).
		echo '<div class="amplifi-cb-modal" id="amplifi-cb-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="' . esc_attr( $opts['bot_name'] ) . '"></div>';

		// Optional floating bubble launcher.
		if ( ! empty( $opts['enable_bubble'] ) ) {
			$pos = in_array( $opts['bubble_position'], array( 'left', 'right' ), true ) ? $opts['bubble_position'] : 'right';
			echo '<button type="button" class="amplifi-cb-bubble amplifi-cb-bubble--' . esc_attr( $pos ) . '" data-amplifi-cb-open="modal" aria-label="' . esc_attr__( 'Open chat', 'amplifi-chatbase' ) . '">';
			echo Amplifi_Chatbase_Shortcodes::launcher_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
			echo '</button>';
		}
	}

	/**
	 * Activation: seed default settings if none exist.
	 */
	public static function activate() {
		if ( false === get_option( AMPLIFI_CHATBASE_OPTION ) ) {
			require_once AMPLIFI_CHATBASE_DIR . 'includes/class-amplifi-chatbase-settings.php';
			add_option( AMPLIFI_CHATBASE_OPTION, Amplifi_Chatbase_Settings::defaults() );
		}
	}
}

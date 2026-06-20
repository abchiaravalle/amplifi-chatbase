<?php
/**
 * Assets: registers and conditionally enqueues CSS/JS, and emits the dynamic CSS variables.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end asset manager.
 */
class Amplifi_Chatbase_Assets {

	/**
	 * Settings handler.
	 *
	 * @var Amplifi_Chatbase_Settings
	 */
	private $settings;

	/**
	 * Whether assets are needed on this request.
	 *
	 * @var bool
	 */
	private $needed = false;

	/**
	 * Constructor.
	 *
	 * @param Amplifi_Chatbase_Settings $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * Register front-end assets (not enqueued until needed).
	 */
	public function register() {
		wp_register_style(
			'amplifi-chatbase',
			AMPLIFI_CHATBASE_URL . 'assets/css/chat.css',
			array(),
			AMPLIFI_CHATBASE_VERSION
		);

		wp_register_script(
			'amplifi-chatbase',
			AMPLIFI_CHATBASE_URL . 'assets/js/chat.js',
			array(),
			AMPLIFI_CHATBASE_VERSION,
			true
		);
	}

	/**
	 * Flag that the assets are required on this page.
	 */
	public function mark_needed() {
		$this->needed = true;
		// If we are already past enqueue, enqueue immediately.
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$this->enqueue();
		}
	}

	/**
	 * Enqueue if a shortcode/global marked the page as needing assets.
	 */
	public function maybe_enqueue() {
		if ( $this->needed ) {
			$this->enqueue();
		}
	}

	/**
	 * Actually enqueue and localize.
	 */
	public function enqueue() {
		if ( wp_style_is( 'amplifi-chatbase', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'amplifi-chatbase' );
		wp_enqueue_script( 'amplifi-chatbase' );

		wp_add_inline_style( 'amplifi-chatbase', $this->dynamic_css() );

		$opts = $this->settings->get();

		wp_localize_script(
			'amplifi-chatbase',
			'AmplifiChatbase',
			array(
				'restUrl'    => esc_url_raw( rest_url( Amplifi_Chatbase_Rest::NAMESPACE . '/chat' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'stream'     => ! empty( $opts['stream'] ),
				'persist'    => ! empty( $opts['persist'] ),
				'sound'      => ! empty( $opts['sound'] ),
				'botName'    => $opts['bot_name'],
				'botIcon'    => $opts['bot_icon'],
				'showIcon'   => ! empty( $opts['show_icon'] ),
				'welcome'    => $opts['welcome_message'],
				'placeholder'=> $opts['placeholder'],
				'sendLabel'  => $opts['send_label'],
				'themeMode'  => $opts['theme_mode'],
				'storageKey' => 'amplifi_cb_thread_' . substr( md5( (string) $opts['chatbot_id'] ), 0, 8 ),
				'i18n'       => array(
					'close'      => __( 'Close', 'amplifi-chatbase' ),
					'mute'       => __( 'Mute', 'amplifi-chatbase' ),
					'unmute'     => __( 'Unmute', 'amplifi-chatbase' ),
					'error'      => __( 'Something went wrong. Please try again.', 'amplifi-chatbase' ),
					'typing'     => __( 'Assistant is typing', 'amplifi-chatbase' ),
				),
			)
		);
	}

	/**
	 * Build the dynamic CSS custom properties from settings.
	 *
	 * @return string
	 */
	public function dynamic_css() {
		$o = $this->settings->get();

		$vars = array(
			'--amplifi-cb-accent'        => $o['accent'],
			'--amplifi-cb-user-bubble'   => $o['user_bubble'],
			'--amplifi-cb-user-text'     => $o['user_text'],
			'--amplifi-cb-bot-bubble'    => $o['bot_bubble_light'],
			'--amplifi-cb-bot-text'      => $o['bot_text_light'],
			'--amplifi-cb-bg'            => $o['bg_light'],
			'--amplifi-cb-modal-tint'    => $o['modal_tint'],
			'--amplifi-cb-radius'        => absint( $o['radius'] ) . 'px',
			'--amplifi-cb-font-size'     => absint( $o['font_size'] ) . 'px',
		);

		$dark = array(
			'--amplifi-cb-bot-bubble' => $o['bot_bubble_dark'],
			'--amplifi-cb-bot-text'   => $o['bot_text_dark'],
			'--amplifi-cb-bg'         => $o['bg_dark'],
		);

		$root = ':root{';
		foreach ( $vars as $k => $v ) {
			$root .= $k . ':' . $v . ';';
		}
		$root .= '}';

		$dark_block = '';
		foreach ( $dark as $k => $v ) {
			$dark_block .= $k . ':' . $v . ';';
		}

		$css = $root;

		if ( 'auto' === $o['theme_mode'] ) {
			$css .= '@media (prefers-color-scheme: dark){:root{' . $dark_block . '}}';
		} elseif ( 'dark' === $o['theme_mode'] ) {
			$css .= ':root{' . $dark_block . '}';
		}

		return $css;
	}

	/**
	 * Enqueue admin settings assets on our page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_assets( $hook ) {
		if ( 'settings_page_amplifi-chatbase' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_style(
			'amplifi-chatbase-admin',
			AMPLIFI_CHATBASE_URL . 'admin/css/admin.css',
			array( 'wp-color-picker' ),
			AMPLIFI_CHATBASE_VERSION
		);
		wp_enqueue_script(
			'amplifi-chatbase-admin',
			AMPLIFI_CHATBASE_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			AMPLIFI_CHATBASE_VERSION,
			true
		);
	}
}

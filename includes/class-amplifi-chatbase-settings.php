<?php
/**
 * Settings: storage, defaults, sanitization, and the admin panel.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin options and the admin settings page.
 */
class Amplifi_Chatbase_Settings {

	/**
	 * Cached options.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * Hook up admin menus and registration.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_filter( 'plugin_action_links_' . AMPLIFI_CHATBASE_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// Connection.
			'api_key'            => '',
			'chatbot_id'         => '',
			'api_base'           => 'https://www.chatbase.co',
			'stream'             => 1,

			// Identity.
			'bot_name'           => __( 'Assistant', 'amplifi-chatbase' ),
			'bot_icon'           => '',
			'show_icon'          => 1,
			'welcome_message'    => __( 'Hi! How can I help you today?', 'amplifi-chatbase' ),

			// Theme.
			'theme_mode'         => 'auto', // auto | light | dark.
			'accent'             => '#0A84FF',
			'user_bubble'        => '#0A84FF',
			'user_text'          => '#ffffff',
			'bot_bubble_light'   => '#E9E9EB',
			'bot_text_light'     => '#000000',
			'bot_bubble_dark'    => '#26252A',
			'bot_text_dark'      => '#ffffff',
			'bg_light'           => '#ffffff',
			'bg_dark'            => '#1C1C1E',
			'modal_tint'         => 'rgba(0,0,0,0.35)',

			// Type.
			'font_size'          => 16,
			'radius'             => 20,

			// Behavior.
			'enable_bubble'      => 0,
			'bubble_position'    => 'right',
			'persist'            => 1,
			'sound'              => 0,
			'placeholder'        => __( 'Ask me anything…', 'amplifi-chatbase' ),
			'send_label'         => __( 'Send', 'amplifi-chatbase' ),
		);
	}

	/**
	 * Get merged options.
	 *
	 * @return array
	 */
	public function get() {
		if ( null === $this->cache ) {
			$saved       = get_option( AMPLIFI_CHATBASE_OPTION, array() );
			$this->cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return $this->cache;
	}

	/**
	 * Get a single option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function value( $key, $default = '' ) {
		$opts = $this->get();
		return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
	}

	/**
	 * Register the settings group and sanitizer.
	 */
	public function register() {
		register_setting(
			'amplifi_chatbase_group',
			AMPLIFI_CHATBASE_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize all incoming settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$out      = array();
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$out['api_key']    = isset( $input['api_key'] ) ? trim( sanitize_text_field( $input['api_key'] ) ) : '';
		$out['chatbot_id'] = isset( $input['chatbot_id'] ) ? trim( sanitize_text_field( $input['chatbot_id'] ) ) : '';
		$out['api_base']   = isset( $input['api_base'] ) ? esc_url_raw( $input['api_base'] ) : $defaults['api_base'];
		$out['stream']     = empty( $input['stream'] ) ? 0 : 1;

		$out['bot_name']        = isset( $input['bot_name'] ) ? sanitize_text_field( $input['bot_name'] ) : $defaults['bot_name'];
		$out['bot_icon']        = isset( $input['bot_icon'] ) ? esc_url_raw( $input['bot_icon'] ) : '';
		$out['show_icon']       = empty( $input['show_icon'] ) ? 0 : 1;
		$out['welcome_message'] = isset( $input['welcome_message'] ) ? sanitize_textarea_field( $input['welcome_message'] ) : '';

		$mode                = isset( $input['theme_mode'] ) ? $input['theme_mode'] : 'auto';
		$out['theme_mode']   = in_array( $mode, array( 'auto', 'light', 'dark' ), true ) ? $mode : 'auto';

		foreach ( array( 'accent', 'user_bubble', 'user_text', 'bot_bubble_light', 'bot_text_light', 'bot_bubble_dark', 'bot_text_dark', 'bg_light', 'bg_dark' ) as $color_key ) {
			$out[ $color_key ] = isset( $input[ $color_key ] ) ? $this->sanitize_color( $input[ $color_key ], $defaults[ $color_key ] ) : $defaults[ $color_key ];
		}

		$out['modal_tint'] = isset( $input['modal_tint'] ) ? $this->sanitize_rgba( $input['modal_tint'], $defaults['modal_tint'] ) : $defaults['modal_tint'];

		$out['font_size'] = isset( $input['font_size'] ) ? max( 10, min( 28, absint( $input['font_size'] ) ) ) : $defaults['font_size'];
		$out['radius']    = isset( $input['radius'] ) ? max( 0, min( 40, absint( $input['radius'] ) ) ) : $defaults['radius'];

		$out['enable_bubble']   = empty( $input['enable_bubble'] ) ? 0 : 1;
		$pos                    = isset( $input['bubble_position'] ) ? $input['bubble_position'] : 'right';
		$out['bubble_position'] = in_array( $pos, array( 'left', 'right' ), true ) ? $pos : 'right';
		$out['persist']         = empty( $input['persist'] ) ? 0 : 1;
		$out['sound']           = empty( $input['sound'] ) ? 0 : 1;
		$out['placeholder']     = isset( $input['placeholder'] ) ? sanitize_text_field( $input['placeholder'] ) : '';
		$out['send_label']      = isset( $input['send_label'] ) ? sanitize_text_field( $input['send_label'] ) : $defaults['send_label'];

		return $out;
	}

	/**
	 * Sanitize a hex color, falling back to a default.
	 *
	 * @param string $value   Raw.
	 * @param string $default Fallback.
	 * @return string
	 */
	private function sanitize_color( $value, $default ) {
		$value = sanitize_text_field( $value );
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) {
			return $value;
		}
		return $default;
	}

	/**
	 * Sanitize an rgba()/hex string used for the modal tint.
	 *
	 * @param string $value   Raw.
	 * @param string $default Fallback.
	 * @return string
	 */
	private function sanitize_rgba( $value, $default ) {
		$value = trim( sanitize_text_field( $value ) );
		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) {
			return $value;
		}
		return $default;
	}

	/**
	 * Add the admin menu page.
	 */
	public function add_menu() {
		add_options_page(
			__( 'Amplifi Chatbase', 'amplifi-chatbase' ),
			__( 'Amplifi Chatbase', 'amplifi-chatbase' ),
			'manage_options',
			'amplifi-chatbase',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a Settings link on the plugins list.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'options-general.php?page=amplifi-chatbase' );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'amplifi-chatbase' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Render the settings page (delegates to the view).
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = $this->get();
		require AMPLIFI_CHATBASE_DIR . 'admin/views/settings-page.php';
	}
}

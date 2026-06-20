<?php
/**
 * Shortcodes: hero prompt box, inline chat, and modal trigger.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the three shortcodes.
 */
class Amplifi_Chatbase_Shortcodes {

	/**
	 * Settings handler.
	 *
	 * @var Amplifi_Chatbase_Settings
	 */
	private $settings;

	/**
	 * Assets handler.
	 *
	 * @var Amplifi_Chatbase_Assets
	 */
	private $assets;

	/**
	 * Per-request counter to make unique IDs.
	 *
	 * @var int
	 */
	private $counter = 0;

	/**
	 * Constructor.
	 *
	 * @param Amplifi_Chatbase_Settings $settings Settings.
	 * @param Amplifi_Chatbase_Assets   $assets   Assets.
	 */
	public function __construct( $settings, $assets ) {
		$this->settings = $settings;
		$this->assets   = $assets;

		add_shortcode( 'amplifi_chat_hero', array( $this, 'render_hero' ) );
		add_shortcode( 'amplifi_chat_inline', array( $this, 'render_inline' ) );
		add_shortcode( 'amplifi_chat_modal', array( $this, 'render_modal_trigger' ) );
	}

	/**
	 * Common attribute parsing + per-instance config payload.
	 *
	 * @param array  $atts Shortcode atts.
	 * @param string $type Shortcode type.
	 * @return array { id, config }
	 */
	private function build_config( $atts, $type ) {
		$this->assets->mark_needed();
		$opts = $this->settings->get();

		$atts = shortcode_atts(
			array(
				'accent'      => '',
				'bot'         => '', // Per-shortcode chatbot override (optional).
				'name'        => '',
				'welcome'     => '',
				'placeholder' => '',
				'questions'   => '', // Pipe-separated suggested questions.
				'height'      => '', // Inline height, e.g. 480px.
				'icon'        => '', // 'show' | 'hide' | url.
				'theme'       => '', // auto | light | dark override.
				'open_text'   => '', // Modal trigger button label.
			),
			$atts,
			'amplifi_chat_' . $type
		);

		$this->counter++;
		$id = 'amplifi-cb-' . $type . '-' . $this->counter;

		// Suggested questions: pipe-separated list.
		$questions = array();
		if ( '' !== $atts['questions'] ) {
			foreach ( explode( '|', $atts['questions'] ) as $q ) {
				$q = trim( wp_strip_all_tags( $q ) );
				if ( '' !== $q ) {
					$questions[] = $q;
				}
			}
		}

		// Icon resolution.
		$show_icon = ! empty( $opts['show_icon'] );
		$icon_url  = $opts['bot_icon'];
		if ( 'hide' === $atts['icon'] ) {
			$show_icon = false;
		} elseif ( 'show' === $atts['icon'] ) {
			$show_icon = true;
		} elseif ( '' !== $atts['icon'] && filter_var( $atts['icon'], FILTER_VALIDATE_URL ) ) {
			$show_icon = true;
			$icon_url  = $atts['icon'];
		}

		$config = array(
			'type'        => $type,
			'botName'     => '' !== $atts['name'] ? $atts['name'] : $opts['bot_name'],
			'welcome'     => '' !== $atts['welcome'] ? $atts['welcome'] : $opts['welcome_message'],
			'placeholder' => '' !== $atts['placeholder'] ? $atts['placeholder'] : $opts['placeholder'],
			'questions'   => $questions,
			'showIcon'    => $show_icon,
			'botIcon'     => $icon_url,
			'accent'      => $this->valid_color( $atts['accent'] ),
			'theme'       => in_array( $atts['theme'], array( 'auto', 'light', 'dark' ), true ) ? $atts['theme'] : '',
			'sendLabel'   => $opts['send_label'],
		);

		return array(
			'id'     => $id,
			'config' => $config,
			'atts'   => $atts,
		);
	}

	/**
	 * Validate a hex color or return empty.
	 *
	 * @param string $value Raw.
	 * @return string
	 */
	private function valid_color( $value ) {
		$value = trim( (string) $value );
		return preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ? $value : '';
	}

	/**
	 * Inline style attribute that forces font-size to win over the theme.
	 *
	 * @param array $config Instance config.
	 * @return string
	 */
	private function instance_style( $config ) {
		$style = '';
		if ( ! empty( $config['accent'] ) ) {
			$style .= '--amplifi-cb-accent:' . esc_attr( $config['accent'] ) . ';';
			$style .= '--amplifi-cb-user-bubble:' . esc_attr( $config['accent'] ) . ';';
		}
		return $style;
	}

	/**
	 * Bail-out notice when not configured (admins only).
	 *
	 * @return string
	 */
	private function not_configured_notice() {
		if ( current_user_can( 'manage_options' ) ) {
			return '<div class="amplifi-cb-notice">' . esc_html__( 'Amplifi Chatbase: add your API key and Chatbot ID in Settings → Amplifi Chatbase.', 'amplifi-chatbase' ) . '</div>';
		}
		return '';
	}

	/**
	 * Hero prompt box: animated typing placeholder cycling through questions.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public function render_hero( $atts ) {
		$opts = $this->settings->get();
		if ( empty( $opts['chatbot_id'] ) ) {
			return $this->not_configured_notice();
		}

		$data = $this->build_config( $atts, 'hero' );
		$id   = $data['id'];
		$cfg  = $data['config'];
		$fs   = absint( $opts['font_size'] );

		$json = wp_json_encode( $cfg );

		ob_start();
		?>
		<div class="amplifi-cb-hero" id="<?php echo esc_attr( $id ); ?>" style="<?php echo esc_attr( $this->instance_style( $cfg ) ); ?>" data-amplifi-cb='<?php echo esc_attr( $json ); ?>'>
			<form class="amplifi-cb-hero__form" autocomplete="off">
				<input
					type="text"
					class="amplifi-cb-hero__input"
					style="font-size:<?php echo esc_attr( $fs ); ?>px !important;"
					aria-label="<?php echo esc_attr( $cfg['placeholder'] ); ?>"
				/>
				<button type="submit" class="amplifi-cb-hero__send" aria-label="<?php echo esc_attr( $cfg['sendLabel'] ); ?>">
					<?php echo self::send_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline embedded chat window (fixed height, always open).
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public function render_inline( $atts ) {
		$opts = $this->settings->get();
		if ( empty( $opts['chatbot_id'] ) ) {
			return $this->not_configured_notice();
		}

		$data   = $this->build_config( $atts, 'inline' );
		$id     = $data['id'];
		$cfg    = $data['config'];
		$atts   = $data['atts'];
		$height = '';
		if ( '' !== $atts['height'] && preg_match( '/^\d{2,4}(px|vh|rem|em)?$/', $atts['height'] ) ) {
			$h      = preg_match( '/(px|vh|rem|em)$/', $atts['height'] ) ? $atts['height'] : $atts['height'] . 'px';
			$height = 'height:' . esc_attr( $h ) . ' !important;';
		}

		$json = wp_json_encode( $cfg );

		ob_start();
		?>
		<div class="amplifi-cb amplifi-cb--inline" id="<?php echo esc_attr( $id ); ?>" style="<?php echo esc_attr( $this->instance_style( $cfg ) . $height ); ?>" data-amplifi-cb='<?php echo esc_attr( $json ); ?>'>
			<?php echo self::window_skeleton( $cfg, $opts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Modal trigger button. Opens the shared modal shell rendered in the footer.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public function render_modal_trigger( $atts ) {
		$opts = $this->settings->get();
		if ( empty( $opts['chatbot_id'] ) ) {
			return $this->not_configured_notice();
		}

		$data  = $this->build_config( $atts, 'modal' );
		$cfg   = $data['config'];
		$atts  = $data['atts'];
		$label = '' !== $atts['open_text'] ? $atts['open_text'] : __( 'Chat with us', 'amplifi-chatbase' );
		$json  = wp_json_encode( $cfg );

		ob_start();
		?>
		<button type="button" class="amplifi-cb-trigger" data-amplifi-cb-open="modal" data-amplifi-cb='<?php echo esc_attr( $json ); ?>' style="<?php echo esc_attr( $this->instance_style( $cfg ) ); ?>">
			<?php echo esc_html( $label ); ?>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the reusable chat window skeleton (header + scroll area + composer).
	 *
	 * @param array $cfg  Instance config.
	 * @param array $opts Global options.
	 * @return string
	 */
	public static function window_skeleton( $cfg, $opts ) {
		$fs = absint( $opts['font_size'] );
		ob_start();
		?>
		<div class="amplifi-cb__header">
			<div class="amplifi-cb__identity">
				<?php if ( ! empty( $cfg['showIcon'] ) ) : ?>
					<span class="amplifi-cb__avatar">
						<?php if ( ! empty( $cfg['botIcon'] ) ) : ?>
							<img src="<?php echo esc_url( $cfg['botIcon'] ); ?>" alt="" />
						<?php else : ?>
							<?php echo self::default_avatar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</span>
				<?php endif; ?>
				<span class="amplifi-cb__name" style="font-size:<?php echo esc_attr( $fs ); ?>px !important;"><?php echo esc_html( $cfg['botName'] ); ?></span>
			</div>
			<div class="amplifi-cb__actions">
				<button type="button" class="amplifi-cb__sound" data-amplifi-cb-sound aria-label="<?php esc_attr_e( 'Toggle sound', 'amplifi-chatbase' ); ?>">
					<?php echo self::muted_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>
		<div class="amplifi-cb__scroll" role="log" aria-live="polite"></div>
		<form class="amplifi-cb__composer" autocomplete="off">
			<input
				type="text"
				class="amplifi-cb__input"
				placeholder="<?php echo esc_attr( $cfg['placeholder'] ); ?>"
				style="font-size:<?php echo esc_attr( $fs ); ?>px !important;"
				aria-label="<?php echo esc_attr( $cfg['placeholder'] ); ?>"
			/>
			<button type="submit" class="amplifi-cb__send" aria-label="<?php echo esc_attr( $cfg['sendLabel'] ); ?>">
				<?php echo self::send_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline SVG: send arrow.
	 *
	 * @return string
	 */
	public static function send_icon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3.4 20.4l17.45-7.48a1 1 0 000-1.84L3.4 3.6a.993.993 0 00-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>';
	}

	/**
	 * Inline SVG: default avatar (chat glyph).
	 *
	 * @return string
	 */
	public static function default_avatar() {
		return '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2C6.48 2 2 5.94 2 10.8c0 2.5 1.2 4.74 3.13 6.32-.13 1.2-.6 2.3-1.36 3.18-.2.23-.05.6.25.62 1.7.12 3.4-.4 4.77-1.42.99.27 2.05.42 3.21.42 5.52 0 10-3.94 10-8.8S17.52 2 12 2z"/></svg>';
	}

	/**
	 * Inline SVG: muted speaker (default state).
	 *
	 * @return string
	 */
	public static function muted_icon() {
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3.63 3.63a.996.996 0 000 1.41L7.29 8.7 7 9H4a1 1 0 00-1 1v4a1 1 0 001 1h3l3.29 3.29c.63.63 1.71.18 1.71-.71v-4.17l4.18 4.18c-.49.37-1.02.68-1.6.91-.36.15-.58.53-.58.92 0 .72.73 1.18 1.39.91.8-.33 1.55-.77 2.22-1.31l1.34 1.34a.996.996 0 101.41-1.41L5.05 3.63c-.39-.39-1.02-.39-1.42 0zM19 12c0 .82-.15 1.61-.41 2.34l1.53 1.53A8.95 8.95 0 0021 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zm-7-8l-1.88 1.88L12 7.76zm4.5 8c0-1.77-1.02-3.29-2.5-4.03v1.79l2.48 2.48c.01-.08.02-.16.02-.24z"/></svg>';
	}

	/**
	 * Inline SVG: chat launcher bubble.
	 *
	 * @return string
	 */
	public static function launcher_icon() {
		return '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
	}
}

<?php
/**
 * Admin settings page view.
 *
 * @package Amplifi_Chatbase
 * @var array $opts Current options (provided by render_page()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap amplifi-cb-admin">
	<h1><?php esc_html_e( 'Amplifi Chatbase', 'amplifi-chatbase' ); ?></h1>
	<p class="amplifi-cb-admin__sub"><?php esc_html_e( 'An elegant, iMessage-style front end for your Chatbase assistant. Your API key stays on the server.', 'amplifi-chatbase' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'amplifi_chatbase_group' ); ?>
		<?php $name = AMPLIFI_CHATBASE_OPTION; ?>

		<div class="amplifi-cb-admin__grid">
			<div class="amplifi-cb-admin__main">

				<h2 class="title"><?php esc_html_e( 'Connection', 'amplifi-chatbase' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="acb_api_key"><?php esc_html_e( 'Chatbase API Key', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<input type="password" id="acb_api_key" class="regular-text" name="<?php echo esc_attr( $name ); ?>[api_key]" value="<?php echo esc_attr( $opts['api_key'] ); ?>" autocomplete="off" />
							<button type="button" class="button-link amplifi-cb-reveal" data-target="acb_api_key"><?php esc_html_e( 'Show', 'amplifi-chatbase' ); ?></button>
							<p class="description"><?php esc_html_e( 'Your secret key. Stored server-side and never sent to the browser.', 'amplifi-chatbase' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_chatbot_id"><?php esc_html_e( 'Chatbot ID', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<input type="text" id="acb_chatbot_id" class="regular-text" name="<?php echo esc_attr( $name ); ?>[chatbot_id]" value="<?php echo esc_attr( $opts['chatbot_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Found in your Chatbase dashboard under the chatbot settings.', 'amplifi-chatbase' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_api_base"><?php esc_html_e( 'API Base URL', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<input type="url" id="acb_api_base" class="regular-text" name="<?php echo esc_attr( $name ); ?>[api_base]" value="<?php echo esc_attr( $opts['api_base'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Default: https://www.chatbase.co — change only if instructed.', 'amplifi-chatbase' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Streaming', 'amplifi-chatbase' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[stream]" value="1" <?php checked( $opts['stream'], 1 ); ?> /> <?php esc_html_e( 'Stream responses live (recommended). If unsupported, falls back to fast typing.', 'amplifi-chatbase' ); ?></label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Identity', 'amplifi-chatbase' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="acb_bot_name"><?php esc_html_e( 'Bot Name', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="text" id="acb_bot_name" class="regular-text amplifi-cb-live" data-live="botName" name="<?php echo esc_attr( $name ); ?>[bot_name]" value="<?php echo esc_attr( $opts['bot_name'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bot Icon', 'amplifi-chatbase' ); ?></th>
						<td>
							<input type="url" id="acb_bot_icon" class="regular-text" name="<?php echo esc_attr( $name ); ?>[bot_icon]" value="<?php echo esc_attr( $opts['bot_icon'] ); ?>" placeholder="https://…" />
							<button type="button" class="button amplifi-cb-upload" data-target="acb_bot_icon"><?php esc_html_e( 'Choose', 'amplifi-chatbase' ); ?></button>
							<p><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_icon]" value="1" <?php checked( $opts['show_icon'], 1 ); ?> /> <?php esc_html_e( 'Show bot icon', 'amplifi-chatbase' ); ?></label></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_welcome"><?php esc_html_e( 'Welcome Message', 'amplifi-chatbase' ); ?></label></th>
						<td><textarea id="acb_welcome" class="large-text" rows="2" name="<?php echo esc_attr( $name ); ?>[welcome_message]"><?php echo esc_textarea( $opts['welcome_message'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_placeholder"><?php esc_html_e( 'Input Placeholder', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="text" id="acb_placeholder" class="regular-text" name="<?php echo esc_attr( $name ); ?>[placeholder]" value="<?php echo esc_attr( $opts['placeholder'] ); ?>" /></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Appearance', 'amplifi-chatbase' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="acb_theme_mode"><?php esc_html_e( 'Theme Mode', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<select id="acb_theme_mode" name="<?php echo esc_attr( $name ); ?>[theme_mode]">
								<option value="auto" <?php selected( $opts['theme_mode'], 'auto' ); ?>><?php esc_html_e( 'Auto (follow device)', 'amplifi-chatbase' ); ?></option>
								<option value="light" <?php selected( $opts['theme_mode'], 'light' ); ?>><?php esc_html_e( 'Light', 'amplifi-chatbase' ); ?></option>
								<option value="dark" <?php selected( $opts['theme_mode'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'amplifi-chatbase' ); ?></option>
							</select>
						</td>
					</tr>
					<?php
					$colors = array(
						'accent'           => __( 'Accent / Send Button', 'amplifi-chatbase' ),
						'user_bubble'      => __( 'User Bubble', 'amplifi-chatbase' ),
						'user_text'        => __( 'User Text', 'amplifi-chatbase' ),
						'bot_bubble_light' => __( 'Bot Bubble (Light)', 'amplifi-chatbase' ),
						'bot_text_light'   => __( 'Bot Text (Light)', 'amplifi-chatbase' ),
						'bot_bubble_dark'  => __( 'Bot Bubble (Dark)', 'amplifi-chatbase' ),
						'bot_text_dark'    => __( 'Bot Text (Dark)', 'amplifi-chatbase' ),
						'bg_light'         => __( 'Background (Light)', 'amplifi-chatbase' ),
						'bg_dark'          => __( 'Background (Dark)', 'amplifi-chatbase' ),
					);
					foreach ( $colors as $key => $label ) :
						?>
						<tr>
							<th scope="row"><label for="acb_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td><input type="text" id="acb_<?php echo esc_attr( $key ); ?>" class="amplifi-cb-color amplifi-cb-live" data-live="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $opts[ $key ] ); ?>" data-default-color="<?php echo esc_attr( $opts[ $key ] ); ?>" /></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row"><label for="acb_modal_tint"><?php esc_html_e( 'Modal Backdrop Tint', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<input type="text" id="acb_modal_tint" class="regular-text" name="<?php echo esc_attr( $name ); ?>[modal_tint]" value="<?php echo esc_attr( $opts['modal_tint'] ); ?>" />
							<p class="description"><?php esc_html_e( 'CSS color behind the blur, e.g. rgba(0,0,0,0.35).', 'amplifi-chatbase' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_font_size"><?php esc_html_e( 'Base Font Size (px)', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="number" id="acb_font_size" min="10" max="28" name="<?php echo esc_attr( $name ); ?>[font_size]" value="<?php echo esc_attr( $opts['font_size'] ); ?>" /> <span class="description"><?php esc_html_e( 'Forced inline so your theme cannot override it.', 'amplifi-chatbase' ); ?></span></td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_radius"><?php esc_html_e( 'Bubble Radius (px)', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="number" id="acb_radius" min="0" max="40" name="<?php echo esc_attr( $name ); ?>[radius]" value="<?php echo esc_attr( $opts['radius'] ); ?>" /></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Behavior', 'amplifi-chatbase' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Floating Bubble', 'amplifi-chatbase' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enable_bubble]" value="1" <?php checked( $opts['enable_bubble'], 1 ); ?> /> <?php esc_html_e( 'Show a floating chat button on every page', 'amplifi-chatbase' ); ?></label>
							<p>
								<select name="<?php echo esc_attr( $name ); ?>[bubble_position]">
									<option value="right" <?php selected( $opts['bubble_position'], 'right' ); ?>><?php esc_html_e( 'Bottom right', 'amplifi-chatbase' ); ?></option>
									<option value="left" <?php selected( $opts['bubble_position'], 'left' ); ?>><?php esc_html_e( 'Bottom left', 'amplifi-chatbase' ); ?></option>
								</select>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Persistence', 'amplifi-chatbase' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[persist]" value="1" <?php checked( $opts['persist'], 1 ); ?> /> <?php esc_html_e( 'Remember the conversation between visits (localStorage)', 'amplifi-chatbase' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sound', 'amplifi-chatbase' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[sound]" value="1" <?php checked( $opts['sound'], 1 ); ?> /> <?php esc_html_e( 'Enable subtle send/receive sounds by default (visitors can still mute)', 'amplifi-chatbase' ); ?></label></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Layout & Embedding', 'amplifi-chatbase' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Control how the widget sits inside your page design instead of always looking like a floating chrome-y box. Every shortcode can also override these individually — see the reference on the right.', 'amplifi-chatbase' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="acb_variant"><?php esc_html_e( 'Style', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<select id="acb_variant" name="<?php echo esc_attr( $name ); ?>[variant]">
								<option value="card" <?php selected( $opts['variant'], 'card' ); ?>><?php esc_html_e( 'Card — border + shadow (default)', 'amplifi-chatbase' ); ?></option>
								<option value="minimal" <?php selected( $opts['variant'], 'minimal' ); ?>><?php esc_html_e( 'Minimal — no border/shadow, keeps chrome background', 'amplifi-chatbase' ); ?></option>
								<option value="bare" <?php selected( $opts['variant'], 'bare' ); ?>><?php esc_html_e( 'Bare — transparent, blends flush into the page', 'amplifi-chatbase' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Chrome', 'amplifi-chatbase' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_border]" value="1" <?php checked( $opts['show_border'], 1 ); ?> /> <?php esc_html_e( 'Show border', 'amplifi-chatbase' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_shadow]" value="1" <?php checked( $opts['show_shadow'], 1 ); ?> /> <?php esc_html_e( 'Show drop shadow', 'amplifi-chatbase' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_header]" value="1" <?php checked( $opts['show_header'], 1 ); ?> /> <?php esc_html_e( 'Show header bar (name + mute button)', 'amplifi-chatbase' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_align"><?php esc_html_e( 'Alignment', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<select id="acb_align" name="<?php echo esc_attr( $name ); ?>[align]">
								<option value="left" <?php selected( $opts['align'], 'left' ); ?>><?php esc_html_e( 'Left', 'amplifi-chatbase' ); ?></option>
								<option value="center" <?php selected( $opts['align'], 'center' ); ?>><?php esc_html_e( 'Center', 'amplifi-chatbase' ); ?></option>
								<option value="right" <?php selected( $opts['align'], 'right' ); ?>><?php esc_html_e( 'Right', 'amplifi-chatbase' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_max_width"><?php esc_html_e( 'Max Width (px)', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="number" id="acb_max_width" min="0" max="2000" name="<?php echo esc_attr( $name ); ?>[max_width]" value="<?php echo esc_attr( $opts['max_width'] ); ?>" /> <span class="description"><?php esc_html_e( '0 = no cap (fills its container).', 'amplifi-chatbase' ); ?></span></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Suggested Questions', 'amplifi-chatbase' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Drive engagement with clickable prompts under the welcome message. Leave the pool empty and set per-shortcode questions instead, or fill this in as a site-wide fallback pool.', 'amplifi-chatbase' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="acb_suggest_mode"><?php esc_html_e( 'Mode', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<select id="acb_suggest_mode" name="<?php echo esc_attr( $name ); ?>[suggest_mode]">
								<option value="rotating" <?php selected( $opts['suggest_mode'], 'rotating' ); ?>><?php esc_html_e( 'Rotating — cycles a fresh set of 3 every few seconds (needs 4+ questions)', 'amplifi-chatbase' ); ?></option>
								<option value="static" <?php selected( $opts['suggest_mode'], 'static' ); ?>><?php esc_html_e( 'Static — shows one fixed set', 'amplifi-chatbase' ); ?></option>
								<option value="off" <?php selected( $opts['suggest_mode'], 'off' ); ?>><?php esc_html_e( 'Off', 'amplifi-chatbase' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_rotate_interval"><?php esc_html_e( 'Rotate Every (ms)', 'amplifi-chatbase' ); ?></label></th>
						<td><input type="number" id="acb_rotate_interval" min="1500" max="20000" step="500" name="<?php echo esc_attr( $name ); ?>[rotate_interval]" value="<?php echo esc_attr( $opts['rotate_interval'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="acb_default_questions"><?php esc_html_e( 'Question Pool (fallback)', 'amplifi-chatbase' ); ?></label></th>
						<td>
							<textarea id="acb_default_questions" class="large-text" rows="5" name="<?php echo esc_attr( $name ); ?>[default_questions]" placeholder="<?php esc_attr_e( 'One question per line…', 'amplifi-chatbase' ); ?>"><?php echo esc_textarea( $opts['default_questions'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Used when a shortcode does not specify its own questions="..." attribute. Leave blank if every placement will set its own — this plugin ships with no questions baked in.', 'amplifi-chatbase' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</div>

			<aside class="amplifi-cb-admin__side">
				<div class="amplifi-cb-admin__card">
					<h3><?php esc_html_e( 'Live Preview', 'amplifi-chatbase' ); ?></h3>
					<div class="amplifi-cb-admin__preview" id="amplifi-cb-preview">
						<div class="amplifi-cb-admin__pv-head"><span class="amplifi-cb-admin__pv-name">Assistant</span></div>
						<div class="amplifi-cb-admin__pv-body">
							<div class="amplifi-cb-admin__pv-row amplifi-cb-admin__pv-row--bot"><span class="amplifi-cb-admin__pv-bot"><?php esc_html_e( 'Hi! How can I help?', 'amplifi-chatbase' ); ?></span></div>
							<div class="amplifi-cb-admin__pv-row amplifi-cb-admin__pv-row--user"><span class="amplifi-cb-admin__pv-user"><?php esc_html_e( 'Tell me about pricing', 'amplifi-chatbase' ); ?></span></div>
							<div class="amplifi-cb-admin__pv-row amplifi-cb-admin__pv-row--bot"><span class="amplifi-cb-admin__pv-bot"><?php esc_html_e( 'Happy to walk you through it.', 'amplifi-chatbase' ); ?></span></div>
						</div>
					</div>
				</div>

				<div class="amplifi-cb-admin__card">
					<h3><?php esc_html_e( 'Shortcodes', 'amplifi-chatbase' ); ?></h3>
					<p><strong><?php esc_html_e( 'Hero prompt box', 'amplifi-chatbase' ); ?></strong></p>
					<code class="amplifi-cb-admin__code">[amplifi_chat_hero questions="What do you offer?|How much does it cost?|Do you integrate with X?"]</code>

					<p><strong><?php esc_html_e( 'Inline chat window', 'amplifi-chatbase' ); ?></strong></p>
					<code class="amplifi-cb-admin__code">[amplifi_chat_inline height="520px" questions="Q1|Q2|Q3|Q4" suggest="rotating" variant="minimal" align="center" max_width="480"]</code>

					<p><strong><?php esc_html_e( 'Popup modal trigger', 'amplifi-chatbase' ); ?></strong></p>
					<code class="amplifi-cb-admin__code">[amplifi_chat_modal open_text="Chat with us"]</code>

					<p class="description"><?php esc_html_e( 'Content/behavior overrides: accent, name, welcome, placeholder, icon (show|hide|URL), theme (auto|light|dark), height (inline), open_text (modal).', 'amplifi-chatbase' ); ?></p>
					<p class="description"><?php esc_html_e( 'Suggested questions: questions="Q1|Q2|Q3" (pipe- or newline-separated, falls back to the global pool above), suggest="rotating|static|off", rotate="3000" (ms).', 'amplifi-chatbase' ); ?></p>
					<p class="description"><?php esc_html_e( 'Layout overrides (blend into any design): variant="card|minimal|bare", border="yes|no", shadow="yes|no", header="yes|no", align="left|center|right", max_width="480" (px).', 'amplifi-chatbase' ); ?></p>
				</div>
			</aside>
		</div>
	</form>
</div>

/* Amplifi Chatbase — admin settings behavior */
(function ($) {
	'use strict';

	$(function () {
		// Color pickers with live preview wiring.
		var preview = document.getElementById('amplifi-cb-preview');

		function applyLive(key, value) {
			if (!preview) { return; }
			var map = {
				accent: '--pv-user-bubble',
				user_bubble: '--pv-user-bubble',
				user_text: '--pv-user-text',
				bot_bubble_light: '--pv-bot-bubble',
				bot_text_light: '--pv-bot-text',
				bg_light: '--pv-bg'
			};
			if (key === 'botName') {
				var nameEl = preview.querySelector('.amplifi-cb-admin__pv-name');
				if (nameEl) { nameEl.textContent = value || 'Assistant'; }
				return;
			}
			if (map[key]) {
				preview.style.setProperty(map[key], value);
			}
		}

		$('.amplifi-cb-color').each(function () {
			var $input = $(this);
			$input.wpColorPicker({
				change: function (event, ui) {
					var key = $input.data('live');
					if (key) { applyLive(key, ui.color.toString()); }
				},
				clear: function () {
					var key = $input.data('live');
					if (key) { applyLive(key, ''); }
				}
			});
		});

		// Text live fields (bot name).
		$('.amplifi-cb-live').on('input keyup', function () {
			var key = $(this).data('live');
			if (key) { applyLive(key, $(this).val()); }
		});

		// Initialize preview from current values.
		$('.amplifi-cb-live').each(function () {
			var key = $(this).data('live');
			if (key) { applyLive(key, $(this).val()); }
		});

		// Reveal API key.
		$('.amplifi-cb-reveal').on('click', function () {
			var target = document.getElementById($(this).data('target'));
			if (!target) { return; }
			if (target.type === 'password') {
				target.type = 'text';
				$(this).text('Hide');
			} else {
				target.type = 'password';
				$(this).text('Show');
			}
		});

		// Media uploader for the bot icon.
		$('.amplifi-cb-upload').on('click', function (e) {
			e.preventDefault();
			var targetId = $(this).data('target');
			var frame = wp.media({
				title: 'Select Bot Icon',
				multiple: false,
				library: { type: 'image' },
				button: { text: 'Use this image' }
			});
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				document.getElementById(targetId).value = att.url;
			});
			frame.open();
		});
	});
})(jQuery);

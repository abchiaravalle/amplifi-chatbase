/**
 * Amplifi Chatbase — front-end engine.
 * Vanilla JS, no dependencies. Drives hero, inline, and modal chat instances.
 */
(function () {
	'use strict';

	var CFG = window.AmplifiChatbase || {};
	var SOUND_PREF_KEY = 'amplifi_cb_sound';

	/* ------------------------------------------------------------------ */
	/* Helpers                                                            */
	/* ------------------------------------------------------------------ */

	function el(tag, cls, html) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (html !== undefined) { n.innerHTML = html; }
		return n;
	}

	function escapeHtml(str) {
		var d = document.createElement('div');
		d.textContent = str;
		return d.innerHTML;
	}

	// Minimal, safe markdown-ish: escape first, then linkify + bold + line breaks.
	function format(text) {
		var safe = escapeHtml(text);
		safe = safe.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		safe = safe.replace(
			/(https?:\/\/[^\s<]+)/g,
			'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
		);
		return safe;
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	/* ------------------------------------------------------------------ */
	/* Sound (WebAudio blips, off by default)                             */
	/* ------------------------------------------------------------------ */

	var Sound = {
		on: (localStorage.getItem(SOUND_PREF_KEY) === '1') || (CFG.sound && localStorage.getItem(SOUND_PREF_KEY) === null),
		ctx: null,
		ensure: function () {
			if (!this.ctx && (window.AudioContext || window.webkitAudioContext)) {
				this.ctx = new (window.AudioContext || window.webkitAudioContext)();
			}
		},
		blip: function (freq, dur) {
			if (!this.on) { return; }
			this.ensure();
			if (!this.ctx) { return; }
			var o = this.ctx.createOscillator();
			var g = this.ctx.createGain();
			o.type = 'sine';
			o.frequency.value = freq;
			g.gain.setValueAtTime(0.0001, this.ctx.currentTime);
			g.gain.exponentialRampToValueAtTime(0.12, this.ctx.currentTime + 0.01);
			g.gain.exponentialRampToValueAtTime(0.0001, this.ctx.currentTime + dur);
			o.connect(g);
			g.connect(this.ctx.destination);
			o.start();
			o.stop(this.ctx.currentTime + dur);
		},
		send: function () { this.blip(660, 0.12); },
		receive: function () { this.blip(440, 0.14); },
		toggle: function () {
			this.on = !this.on;
			localStorage.setItem(SOUND_PREF_KEY, this.on ? '1' : '0');
			return this.on;
		}
	};

	/* ------------------------------------------------------------------ */
	/* Chat instance — drives one window (inline or inside modal)         */
	/* ------------------------------------------------------------------ */

	function Chat(root, cfg, opts) {
		this.root = root;
		this.cfg = cfg;
		this.opts = opts || {};
		this.messages = [];
		this.busy = false;
		this.scroll = root.querySelector('.amplifi-cb__scroll');
		this.form = root.querySelector('.amplifi-cb__composer');
		this.input = root.querySelector('.amplifi-cb__input');
		this.soundBtn = root.querySelector('[data-amplifi-cb-sound]');
		this.storageKey = CFG.storageKey + '_' + (cfg.type || 'x');
		this.autoScroll = true;
		this.bind();
		this.restore();
	}

	Chat.prototype.bind = function () {
		var self = this;
		if (this.form) {
			this.form.addEventListener('submit', function (e) {
				e.preventDefault();
				var v = self.input.value.trim();
				if (v) { self.send(v); self.input.value = ''; }
			});
		}
		if (this.soundBtn) {
			this.reflectSound();
			this.soundBtn.addEventListener('click', function () {
				Sound.toggle();
				self.reflectSound();
			});
		}
		if (this.scroll) {
			this.scroll.addEventListener('scroll', function () {
				var nearBottom = self.scroll.scrollHeight - self.scroll.scrollTop - self.scroll.clientHeight < 60;
				self.autoScroll = nearBottom;
			});
		}
	};

	Chat.prototype.reflectSound = function () {
		if (!this.soundBtn) { return; }
		this.soundBtn.classList.toggle('is-on', Sound.on);
		this.soundBtn.setAttribute(
			'aria-label',
			Sound.on ? (CFG.i18n ? CFG.i18n.mute : 'Mute') : (CFG.i18n ? CFG.i18n.unmute : 'Unmute')
		);
	};

	Chat.prototype.restore = function () {
		var restored = false;
		if (CFG.persist) {
			try {
				var raw = localStorage.getItem(this.storageKey);
				if (raw) {
					var saved = JSON.parse(raw);
					if (Array.isArray(saved) && saved.length) {
						this.messages = saved;
						for (var i = 0; i < saved.length; i++) {
							this.paint(saved[i].role, saved[i].content, true);
						}
						restored = true;
					}
				}
			} catch (e) { /* ignore */ }
		}
		if (!restored) {
			if (this.cfg.welcome) {
				this.paint('assistant', this.cfg.welcome, true);
			}
			this.renderSuggestions();
		}
		this.toBottom(true);
	};

	Chat.prototype.persist = function () {
		if (!CFG.persist) { return; }
		try {
			localStorage.setItem(this.storageKey, JSON.stringify(this.messages.slice(-40)));
		} catch (e) { /* quota */ }
	};

	Chat.prototype.renderSuggestions = function () {
		if (!this.cfg.questions || !this.cfg.questions.length) { return; }
		var wrap = el('div', 'amplifi-cb__suggest');
		var self = this;
		this.cfg.questions.forEach(function (q, idx) {
			var chip = el('button', 'amplifi-cb__chip');
			chip.type = 'button';
			chip.textContent = q;
			chip.style.animationDelay = (idx * 0.05) + 's';
			chip.addEventListener('click', function () {
				if (wrap.parentNode) { wrap.parentNode.removeChild(wrap); }
				self.send(q);
			});
			wrap.appendChild(chip);
		});
		this.scroll.appendChild(wrap);
		this.suggestEl = wrap;
	};

	Chat.prototype.clearSuggestions = function () {
		if (this.suggestEl && this.suggestEl.parentNode) {
			this.suggestEl.parentNode.removeChild(this.suggestEl);
			this.suggestEl = null;
		}
	};

	Chat.prototype.paint = function (role, content, noAnim) {
		var row = el('div', 'amplifi-cb__row amplifi-cb__row--' + (role === 'user' ? 'user' : 'bot'));
		var bubble = el('div', 'amplifi-cb__bubble');
		if (noAnim) { bubble.style.animation = 'none'; }
		bubble.innerHTML = format(content);
		row.appendChild(bubble);
		this.scroll.appendChild(row);
		return bubble;
	};

	Chat.prototype.typing = function () {
		var row = el('div', 'amplifi-cb__row amplifi-cb__row--bot');
		row.appendChild(el('div', 'amplifi-cb__bubble amplifi-cb__typing', '<span></span><span></span><span></span>'));
		this.scroll.appendChild(row);
		this.toBottom();
		return row;
	};

	Chat.prototype.toBottom = function (force) {
		if (force || this.autoScroll) {
			this.scroll.scrollTop = this.scroll.scrollHeight;
		}
	};

	Chat.prototype.send = function (text) {
		if (this.busy) { return; }
		this.clearSuggestions();
		this.busy = true;
		this.autoScroll = true;

		this.messages.push({ role: 'user', content: text });
		this.paint('user', text);
		Sound.send();
		this.persist();
		this.toBottom(true);

		var self = this;
		var typingRow = this.typing();

		this.request(
			this.messages,
			function onChunk(partial, bubble) {
				if (typingRow && typingRow.parentNode) {
					typingRow.parentNode.removeChild(typingRow);
					typingRow = null;
				}
				if (!bubble.painted) {
					bubble.painted = self.paint('assistant', '');
				}
				bubble.painted.innerHTML = format(partial);
				self.toBottom();
			},
			function onDone(full, bubble) {
				if (typingRow && typingRow.parentNode) {
					typingRow.parentNode.removeChild(typingRow);
				}
				if (bubble.painted) {
					bubble.painted.innerHTML = format(full);
				} else {
					self.paint('assistant', full || (CFG.i18n ? CFG.i18n.error : 'Error'));
				}
				self.messages.push({ role: 'assistant', content: full });
				self.persist();
				Sound.receive();
				self.busy = false;
				self.toBottom();
			},
			function onError(msg) {
				if (typingRow && typingRow.parentNode) {
					typingRow.parentNode.removeChild(typingRow);
				}
				self.paint('assistant', msg || (CFG.i18n ? CFG.i18n.error : 'Error'));
				self.busy = false;
				self.toBottom();
			}
		);
	};

	// Network: streaming via fetch reader, else JSON. Carries a shared bubble ref.
	Chat.prototype.request = function (messages, onChunk, onDone, onError) {
		var bubbleRef = {};
		var headers = {
			'Content-Type': 'application/json',
			'X-WP-Nonce': CFG.nonce
		};
		var body = JSON.stringify({ messages: messages });

		if (CFG.stream && window.fetch && window.ReadableStream) {
			fetch(CFG.restUrl, { method: 'POST', headers: headers, body: body })
				.then(function (resp) {
					if (!resp.ok || !resp.body) {
						return resp.json().then(function (j) { throw new Error(j.error || 'HTTP ' + resp.status); });
					}
					var reader = resp.body.getReader();
					var decoder = new TextDecoder();
					var acc = '';
					var buf = '';

					function pump() {
						return reader.read().then(function (res) {
							if (res.done) { onDone(acc, bubbleRef); return; }
							buf += decoder.decode(res.value, { stream: true });
							var parts = buf.split('\n\n');
							buf = parts.pop();
							for (var i = 0; i < parts.length; i++) {
								var line = parts[i];
								if (line.indexOf('event: done') !== -1) { onDone(acc, bubbleRef); return; }
								var m = line.match(/^data: (.*)$/m);
								if (m) {
									try {
										var d = JSON.parse(m[1]);
										if (d.error) { onError(d.error); return; }
										if (typeof d.text === 'string') {
											acc += d.text;
											onChunk(acc, bubbleRef);
										}
									} catch (e) { /* skip */ }
								}
							}
							return pump();
						});
					}
					return pump();
				})
				.catch(function (err) { onError(err.message); });
		} else {
			fetch(CFG.restUrl, { method: 'POST', headers: headers, body: body })
				.then(function (resp) { return resp.json().then(function (j) { return { ok: resp.ok, j: j }; }); })
				.then(function (r) {
					if (!r.ok) { onError(r.j.error); return; }
					var full = r.j.text || '';
					// Fake-fast typing reveal when not streaming.
					if (prefersReducedMotion()) {
						onChunk(full, bubbleRef);
						onDone(full, bubbleRef);
						return;
					}
					var i = 0;
					var step = Math.max(2, Math.round(full.length / 60));
					(function reveal() {
						i += step;
						onChunk(full.slice(0, i), bubbleRef);
						if (i < full.length) {
							setTimeout(reveal, 14);
						} else {
							onDone(full, bubbleRef);
						}
					})();
				})
				.catch(function (err) { onError(err.message); });
		}
	};

	Chat.prototype.seed = function (text) {
		// Used by the hero box to send the first message into a fresh window.
		this.send(text);
	};

	/* ------------------------------------------------------------------ */
	/* Modal controller (singleton shell in footer)                       */
	/* ------------------------------------------------------------------ */

	var Modal = {
		shell: null,
		chat: null,
		lastFocus: null,
		mount: function (cfg, seedText) {
			this.shell = document.getElementById('amplifi-cb-modal');
			if (!this.shell) { return; }
			this.lastFocus = document.activeElement;

			// Build panel fresh each open so config can vary per trigger.
			this.shell.innerHTML = '';
			var panel = el('div', 'amplifi-cb-modal__panel');
			panel.innerHTML = window.AmplifiChatbaseSkeleton(cfg);
			// Insert a close button into the header actions.
			var actions = panel.querySelector('.amplifi-cb__actions');
			if (actions) {
				var close = el('button', 'amplifi-cb__close');
				close.type = 'button';
				close.setAttribute('aria-label', CFG.i18n ? CFG.i18n.close : 'Close');
				close.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71L12 12l6.3 6.29-1.42 1.42L10.59 13.4 5.7 18.3 4.29 16.88 10.59 12 4.29 7.12 5.7 5.71l4.89 4.88 4.89-4.88z"/></svg>';
				actions.appendChild(close);
				close.addEventListener('click', Modal.close.bind(Modal));
			}

			if (cfg.accent) {
				panel.style.setProperty('--amplifi-cb-accent', cfg.accent);
				panel.style.setProperty('--amplifi-cb-user-bubble', cfg.accent);
			}

			this.shell.appendChild(panel);
			this.chat = new Chat(panel, cfg, {});

			var self = this;
			// Click outside the panel closes.
			this.shell.addEventListener('click', this._outside = function (e) {
				if (e.target === self.shell) { self.close(); }
			});
			document.addEventListener('keydown', this._esc = function (e) {
				if (e.key === 'Escape') { self.close(); }
			});

			// Open with a frame delay so the transition runs.
			requestAnimationFrame(function () {
				self.shell.classList.add('is-open');
				self.shell.setAttribute('aria-hidden', 'false');
				var input = panel.querySelector('.amplifi-cb__input');
				if (input) { setTimeout(function () { input.focus(); }, 350); }
				if (seedText) { self.chat.seed(seedText); }
			});
		},
		close: function () {
			if (!this.shell) { return; }
			this.shell.classList.remove('is-open');
			this.shell.setAttribute('aria-hidden', 'true');
			if (this._outside) { this.shell.removeEventListener('click', this._outside); }
			if (this._esc) { document.removeEventListener('keydown', this._esc); }
			if (this.lastFocus && this.lastFocus.focus) { this.lastFocus.focus(); }
		}
	};

	/* ------------------------------------------------------------------ */
	/* Hero box — animated typing placeholder cycling through questions   */
	/* ------------------------------------------------------------------ */

	function Hero(root, cfg) {
		this.root = root;
		this.cfg = cfg;
		this.input = root.querySelector('.amplifi-cb-hero__input');
		this.form = root.querySelector('.amplifi-cb-hero__form');
		this.questions = (cfg.questions && cfg.questions.length) ? cfg.questions : [cfg.placeholder || 'Ask me anything…'];
		this.qi = 0;
		this.userTyped = false;
		this.bind();
		this.animate();
	}

	Hero.prototype.bind = function () {
		var self = this;
		this.input.addEventListener('input', function () {
			self.userTyped = self.input.value.length > 0;
		});
		this.input.addEventListener('focus', function () {
			self.stop();
			self.input.placeholder = self.cfg.placeholder || '';
		});
		this.form.addEventListener('submit', function (e) {
			e.preventDefault();
			// Whatever the user typed, else the question currently displayed.
			var text = self.input.value.trim();
			if (!text) { text = self.currentQuestion || self.questions[self.qi] || ''; }
			text = text.trim();
			if (!text) { return; }
			self.stop();
			self.input.value = '';
			self.input.blur();
			Modal.mount(self.cfg, text);
		});
	};

	Hero.prototype.animate = function () {
		if (prefersReducedMotion()) {
			this.input.placeholder = this.questions[0];
			return;
		}
		var self = this;
		this.input.classList.add('is-animating');

		var q = this.questions[this.qi];
		this.currentQuestion = q;
		var pos = 0;
		var mode = 'type';

		function tick() {
			if (self.userTyped || self.stopped) { return; }
			if (mode === 'type') {
				pos++;
				self.input.placeholder = q.slice(0, pos);
				if (pos >= q.length) {
					mode = 'hold';
					self.timer = setTimeout(tick, 1600);
					return;
				}
				self.timer = setTimeout(tick, 55 + Math.random() * 35);
			} else if (mode === 'hold') {
				mode = 'erase';
				self.timer = setTimeout(tick, 30);
			} else { // erase
				pos--;
				self.input.placeholder = q.slice(0, pos);
				if (pos <= 0) {
					self.qi = (self.qi + 1) % self.questions.length;
					q = self.questions[self.qi];
					self.currentQuestion = q;
					mode = 'type';
					self.timer = setTimeout(tick, 400);
					return;
				}
				self.timer = setTimeout(tick, 28);
			}
		}
		this.timer = setTimeout(tick, 600);
	};

	Hero.prototype.stop = function () {
		this.stopped = true;
		if (this.timer) { clearTimeout(this.timer); }
		this.input.classList.remove('is-animating');
	};

	/* ------------------------------------------------------------------ */
	/* Skeleton builder (JS mirror of PHP markup, for modal)              */
	/* ------------------------------------------------------------------ */

	window.AmplifiChatbaseSkeleton = function (cfg) {
		var fs = '';
		var avatar = '';
		if (cfg.showIcon) {
			var inner = cfg.botIcon
				? '<img src="' + escapeHtml(cfg.botIcon) + '" alt="" />'
				: '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 5.94 2 10.8c0 2.5 1.2 4.74 3.13 6.32-.13 1.2-.6 2.3-1.36 3.18-.2.23-.05.6.25.62 1.7.12 3.4-.4 4.77-1.42.99.27 2.05.42 3.21.42 5.52 0 10-3.94 10-8.8S17.52 2 12 2z"/></svg>';
			avatar = '<span class="amplifi-cb__avatar">' + inner + '</span>';
		}
		var soundIcon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3.63 3.63a.996.996 0 000 1.41L7.29 8.7 7 9H4a1 1 0 00-1 1v4a1 1 0 001 1h3l3.29 3.29c.63.63 1.71.18 1.71-.71v-4.17l4.18 4.18c-.49.37-1.02.68-1.6.91-.36.15-.58.53-.58.92 0 .72.73 1.18 1.39.91.8-.33 1.55-.77 2.22-1.31l1.34 1.34a.996.996 0 101.41-1.41L5.05 3.63c-.39-.39-1.02-.39-1.42 0z"/></svg>';
		var sendIcon = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3.4 20.4l17.45-7.48a1 1 0 000-1.84L3.4 3.6a.993.993 0 00-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>';

		return '' +
			'<div class="amplifi-cb__header">' +
				'<div class="amplifi-cb__identity">' + avatar +
					'<span class="amplifi-cb__name">' + escapeHtml(cfg.botName || '') + '</span>' +
				'</div>' +
				'<div class="amplifi-cb__actions">' +
					'<button type="button" class="amplifi-cb__sound" data-amplifi-cb-sound aria-label="sound">' + soundIcon + '</button>' +
				'</div>' +
			'</div>' +
			'<div class="amplifi-cb__scroll" role="log" aria-live="polite"></div>' +
			'<form class="amplifi-cb__composer" autocomplete="off">' +
				'<input type="text" class="amplifi-cb__input" placeholder="' + escapeHtml(cfg.placeholder || '') + '" aria-label="' + escapeHtml(cfg.placeholder || '') + '" />' +
				'<button type="submit" class="amplifi-cb__send" aria-label="' + escapeHtml(cfg.sendLabel || 'Send') + '">' + sendIcon + '</button>' +
			'</form>';
	};

	/* ------------------------------------------------------------------ */
	/* Boot                                                               */
	/* ------------------------------------------------------------------ */

	function parseCfg(node) {
		try { return JSON.parse(node.getAttribute('data-amplifi-cb')); }
		catch (e) { return {}; }
	}

	function boot() {
		// Hero boxes.
		var heroes = document.querySelectorAll('.amplifi-cb-hero');
		Array.prototype.forEach.call(heroes, function (h) {
			new Hero(h, parseCfg(h));
		});

		// Inline windows.
		var inlines = document.querySelectorAll('.amplifi-cb--inline');
		Array.prototype.forEach.call(inlines, function (n) {
			new Chat(n, parseCfg(n), {});
		});

		// Modal triggers (buttons + floating bubble).
		var triggers = document.querySelectorAll('[data-amplifi-cb-open="modal"]');
		Array.prototype.forEach.call(triggers, function (btn) {
			btn.addEventListener('click', function () {
				var cfg = parseCfg(btn);
				if (!cfg || !cfg.type) {
					cfg = {
						type: 'modal',
						botName: CFG.botName,
						welcome: CFG.welcome,
						placeholder: CFG.placeholder,
						showIcon: CFG.showIcon,
						botIcon: CFG.botIcon,
						sendLabel: CFG.sendLabel,
						questions: []
					};
				}
				Modal.mount(cfg, null);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
